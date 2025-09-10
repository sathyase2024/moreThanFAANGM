<?php

namespace WFP\Api;

if (!defined('ABSPATH')) {
    exit;
}

class Routes
{
    public static function register(): void
    {
        register_rest_route('wfp/v1', '/ping', [
            'methods' => 'GET',
            'callback' => function () {
                return [
                    'ok' => true,
                    'version' => defined('WFP_VERSION') ? WFP_VERSION : 'unknown',
                ];
            },
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('wfp/v1', '/dashboard/summary', [
            'methods' => 'GET',
            'callback' => [self::class, 'getDashboardSummary'],
            'permission_callback' => function () {
                return current_user_can('read');
            },
        ]);

        register_rest_route('wfp/v1', '/attendance/clock', [
            'methods' => 'POST',
            'callback' => [self::class, 'postAttendanceClock'],
            'permission_callback' => function () {
                return current_user_can('wfp_clock_attendance');
            },
            'args' => [
                'action' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['in', 'out'],
                ],
                'activity' => [
                    'required' => false,
                    'type' => 'string',
                ],
            ],
        ]);

        // Attendance list for current user (employees)
        register_rest_route('wfp/v1', '/attendance', [
            'methods' => 'GET',
            'callback' => [self::class, 'getMyAttendance'],
            'permission_callback' => function () {
                return current_user_can('wfp_clock_attendance') || current_user_can('read');
            },
            'args' => [
                'limit' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 20,
                ],
                'page' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                ],
            ],
        ]);

        // Leaves: list/create for employee; pending for managers; approve/reject
        register_rest_route('wfp/v1', '/leaves', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'getLeaves'],
                'permission_callback' => function () {
                    return current_user_can('wfp_submit_leave') || current_user_can('wfp_approve_leave');
                },
                'args' => [
                    'status' => [
                        'required' => false,
                        'type' => 'string',
                    ],
                ],
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'createLeave'],
                'permission_callback' => function () {
                    return current_user_can('wfp_submit_leave');
                },
                'args' => [
                    'type' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                    'reason' => [
                        'required' => false,
                        'type' => 'string',
                    ],
                    'start_date' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                    'end_date' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                ],
            ],
        ]);

        register_rest_route('wfp/v1', '/leaves/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [self::class, 'updateLeaveStatus'],
            'permission_callback' => function () {
                return current_user_can('wfp_approve_leave');
            },
            'args' => [
                'status' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['approved', 'rejected'],
                ],
            ],
        ]);
    }

    public static function getDashboardSummary(\WP_REST_Request $req)
    {
        $user_id = get_current_user_id();
        return [
            'user_id' => $user_id,
            'stats' => [
                'today_attendance' => null,
                'pending_leaves' => 0,
                'active_projects' => 0,
                'open_tasks' => 0,
            ],
        ];
    }

    public static function postAttendanceClock(\WP_REST_Request $req)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wfp_attendance';
        $user_id = get_current_user_id();
        $action = $req->get_param('action');
        $activity = $req->get_param('activity');

        if ($action === 'in') {
            $wpdb->insert($table, [
                'user_id' => $user_id,
                'clock_in' => current_time('mysql'),
                'activity' => is_string($activity) ? $activity : null,
            ]);
            return ['status' => 'clocked_in'];
        }

        // Find latest open record to clock out
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND clock_out IS NULL ORDER BY id DESC LIMIT 1",
            $user_id
        ));
        if ($row) {
            $wpdb->update($table, ['clock_out' => current_time('mysql')], ['id' => $row->id]);
            return ['status' => 'clocked_out'];
        }

        return new \WP_Error('wfp_no_clockin', __('No active clock-in found.', 'workflux-pro'), ['status' => 400]);
    }

    public static function getMyAttendance(\WP_REST_Request $req)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wfp_attendance';
        $user_id = get_current_user_id();
        $limit = max(1, (int) $req->get_param('limit'));
        $page = max(1, (int) $req->get_param('page'));
        $offset = ($page - 1) * $limit;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, clock_in, clock_out, activity FROM $table WHERE user_id = %d ORDER BY id DESC LIMIT %d OFFSET %d",
            $user_id,
            $limit,
            $offset
        ), ARRAY_A);

        return [
            'items' => $rows ?: [],
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public static function getLeaves(\WP_REST_Request $req)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wfp_leaves';
        $user_id = get_current_user_id();
        $status = $req->get_param('status');

        if (current_user_can('wfp_approve_leave')) {
            // Simple manager view: list pending by default
            if (!$status) {
                $status = 'pending';
            }
            $query = "SELECT l.id, l.user_id, l.type, l.reason, l.start_date, l.end_date, l.status, l.created_at, u.display_name AS user_name
                      FROM $table l JOIN {$wpdb->users} u ON u.ID = l.user_id
                      WHERE l.status = %s ORDER BY l.id DESC LIMIT 100";
            $rows = $wpdb->get_results($wpdb->prepare($query, $status), ARRAY_A);
            return ['items' => $rows ?: []];
        }

        // Employee: only own leaves
        $query = "SELECT id, type, reason, start_date, end_date, status, created_at FROM $table WHERE user_id = %d ORDER BY id DESC LIMIT 100";
        $rows = $wpdb->get_results($wpdb->prepare($query, $user_id), ARRAY_A);
        return ['items' => $rows ?: []];
    }

    public static function createLeave(\WP_REST_Request $req)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wfp_leaves';
        $user_id = get_current_user_id();
        $type = sanitize_text_field((string) $req->get_param('type'));
        $reason = sanitize_textarea_field((string) $req->get_param('reason'));
        $start = sanitize_text_field((string) $req->get_param('start_date'));
        $end = sanitize_text_field((string) $req->get_param('end_date'));

        if (!$type || !$start || !$end) {
            return new \WP_Error('wfp_bad_request', __('Missing required fields', 'workflux-pro'), ['status' => 400]);
        }

        $wpdb->insert($table, [
            'user_id' => $user_id,
            'type' => $type,
            'reason' => $reason ?: null,
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'pending',
        ]);

        return ['id' => (int) $wpdb->insert_id, 'status' => 'pending'];
    }

    public static function updateLeaveStatus(\WP_REST_Request $req)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wfp_leaves';
        $id = (int) $req->get_param('id');
        $status = (string) $req->get_param('status');
        $approver = get_current_user_id();

        if (!in_array($status, ['approved', 'rejected'], true)) {
            return new \WP_Error('wfp_bad_status', __('Invalid status', 'workflux-pro'), ['status' => 400]);
        }

        $row = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table WHERE id = %d", $id));
        if (!$row) {
            return new \WP_Error('wfp_not_found', __('Leave not found', 'workflux-pro'), ['status' => 404]);
        }

        $wpdb->update($table, [
            'status' => $status,
            'approver_id' => $approver,
        ], ['id' => $id]);

        return ['id' => $id, 'status' => $status];
    }
}


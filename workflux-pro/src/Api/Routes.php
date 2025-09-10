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
}


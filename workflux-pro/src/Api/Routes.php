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

        // Settings (workweek, leave categories)
        register_rest_route('wfp/v1', '/settings', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'getSettings'],
                'permission_callback' => function () {
                    return current_user_can('wfp_manage_settings');
                },
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'saveSettings'],
                'permission_callback' => function () {
                    return current_user_can('wfp_manage_settings');
                },
                'args' => [
                    'workweek_days' => [ 'type' => 'integer', 'required' => true ],
                    'leave_categories' => [ 'type' => 'array', 'required' => false ],
                ],
            ],
        ]);

        // Users (for assignee selection)
        register_rest_route('wfp/v1', '/users', [
            'methods' => 'GET',
            'callback' => [self::class, 'getUsers'],
            'permission_callback' => function () {
                return current_user_can('wfp_manage_projects') || current_user_can('wfp_manage_employees');
            },
        ]);

        // Employees management: list/update type and status
        register_rest_route('wfp/v1', '/employees', [
            'methods' => 'GET',
            'callback' => [self::class, 'getEmployees'],
            'permission_callback' => function () { return current_user_can('wfp_manage_employees'); },
        ]);
        register_rest_route('wfp/v1', '/employees/(?P<id>\\d+)', [
            'methods' => 'POST',
            'callback' => [self::class, 'updateEmployee'],
            'permission_callback' => function () { return current_user_can('wfp_manage_employees'); },
            'args' => [
                'type' => [ 'type' => 'string', 'required' => false ],
                'status' => [ 'type' => 'string', 'required' => false ],
            ],
        ]);

        // Project membership
        register_rest_route('wfp/v1', '/projects/(?P<id>\\d+)/members', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'getProjectMembers'],
                'permission_callback' => function () { return current_user_can('wfp_manage_projects'); },
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'addProjectMember'],
                'permission_callback' => function () { return current_user_can('wfp_manage_projects'); },
                'args' => [ 'user_id' => [ 'type' => 'integer', 'required' => true ], 'role' => [ 'type' => 'string', 'required' => false ] ],
            ],
        ]);
        register_rest_route('wfp/v1', '/projects/(?P<id>\\d+)/members/(?P<user_id>\\d+)', [
            'methods' => 'DELETE',
            'callback' => [self::class, 'removeProjectMember'],
            'permission_callback' => function () { return current_user_can('wfp_manage_projects'); },
        ]);

        // Projects
        register_rest_route('wfp/v1', '/projects', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'getProjects'],
                'permission_callback' => function () { return current_user_can('wfp_manage_projects') || current_user_can('wfp_view_reports'); },
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'createProject'],
                'permission_callback' => function () { return current_user_can('wfp_manage_projects'); },
                'args' => [
                    'name' => [ 'type' => 'string', 'required' => true ],
                    'description' => [ 'type' => 'string', 'required' => false ],
                    'deadline' => [ 'type' => 'string', 'required' => false ],
                ],
            ],
        ]);

        // Tasks
        register_rest_route('wfp/v1', '/tasks', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'getTasks'],
                'permission_callback' => function () { return current_user_can('read'); },
                'args' => [ 'project_id' => [ 'type' => 'integer', 'required' => false ], 'mine' => [ 'type' => 'boolean', 'required' => false ] ],
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'createTask'],
                'permission_callback' => function () { return current_user_can('wfp_manage_tasks') || current_user_can('wfp_manage_projects'); },
                'args' => [
                    'project_id' => [ 'type' => 'integer', 'required' => true ],
                    'assignee_id' => [ 'type' => 'integer', 'required' => false ],
                    'title' => [ 'type' => 'string', 'required' => true ],
                    'description' => [ 'type' => 'string', 'required' => false ],
                    'priority' => [ 'type' => 'string', 'required' => false ],
                    'due_date' => [ 'type' => 'string', 'required' => false ],
                ],
            ],
        ]);

        register_rest_route('wfp/v1', '/tasks/(?P<id>\\d+)/status', [
            'methods' => 'POST',
            'callback' => [self::class, 'updateTaskStatus'],
            'permission_callback' => function () { return current_user_can('wfp_manage_tasks') || current_user_can('wfp_manage_projects'); },
            'args' => [ 'status' => [ 'type' => 'string', 'required' => true ] ],
        ]);

        // Time logs
        register_rest_route('wfp/v1', '/time-logs/start', [
            'methods' => 'POST',
            'callback' => [self::class, 'startTimeLog'],
            'permission_callback' => function () { return current_user_can('read'); },
            'args' => [ 'task_id' => [ 'type' => 'integer', 'required' => true ] ],
        ]);
        register_rest_route('wfp/v1', '/time-logs/stop', [
            'methods' => 'POST',
            'callback' => [self::class, 'stopTimeLog'],
            'permission_callback' => function () { return current_user_can('read'); },
            'args' => [ 'task_id' => [ 'type' => 'integer', 'required' => true ] ],
        ]);

        // Reports
        register_rest_route('wfp/v1', '/reports/time', [
            'methods' => 'GET',
            'callback' => [self::class, 'getTimeReport'],
            'permission_callback' => function () { return current_user_can('wfp_view_reports') || current_user_can('wfp_manage_projects'); },
            'args' => [ 'user_id' => [ 'type' => 'integer', 'required' => false ], 'project_id' => [ 'type' => 'integer', 'required' => false ] ],
        ]);

        // Attendance report (hierarchical)
        register_rest_route('wfp/v1', '/reports/attendance', [
            'methods' => 'GET',
            'callback' => [self::class, 'getAttendanceReport'],
            'permission_callback' => function () { return current_user_can('wfp_view_reports') || current_user_can('wfp_manage_employees') || current_user_can('read'); },
            'args' => [
                'user_id' => [ 'type' => 'integer', 'required' => false ],
                'from' => [ 'type' => 'string', 'required' => false ],
                'to' => [ 'type' => 'string', 'required' => false ],
            ],
        ]);

        // Time logs detailed report (hierarchical)
        register_rest_route('wfp/v1', '/reports/time-logs', [
            'methods' => 'GET',
            'callback' => [self::class, 'getTimeLogsDetailed'],
            'permission_callback' => function () { return current_user_can('wfp_view_reports') || current_user_can('wfp_manage_projects') || current_user_can('read'); },
            'args' => [
                'user_id' => [ 'type' => 'integer', 'required' => false ],
                'project_id' => [ 'type' => 'integer', 'required' => false ],
                'from' => [ 'type' => 'string', 'required' => false ],
                'to' => [ 'type' => 'string', 'required' => false ],
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

    public static function getSettings(\WP_REST_Request $req)
    {
        $workweek = (int) get_option('wfp_workweek_days', 6);
        $cats = get_option('wfp_leave_categories', ['Casual', 'Sick']);
        if (!is_array($cats)) { $cats = []; }
        return [ 'workweek_days' => $workweek, 'leave_categories' => array_values($cats) ];
    }

    public static function saveSettings(\WP_REST_Request $req)
    {
        $workweek = (int) $req->get_param('workweek_days');
        $cats = $req->get_param('leave_categories');
        if ($workweek !== 6 && $workweek !== 7) {
            return new \WP_Error('wfp_bad_setting', __('Workweek must be 6 or 7', 'workflux-pro'), ['status' => 400]);
        }
        if (!is_array($cats)) { $cats = []; }
        $cats = array_values(array_filter(array_map('sanitize_text_field', $cats)));
        update_option('wfp_workweek_days', $workweek);
        update_option('wfp_leave_categories', $cats);
        return ['ok' => true];
    }

    public static function getUsers(\WP_REST_Request $req)
    {
        $users = get_users(['fields' => ['ID', 'display_name']]);
        return array_map(function($u){ return ['id' => (int)$u->ID, 'name' => $u->display_name]; }, $users);
    }

    public static function getEmployees(\WP_REST_Request $req)
    {
        $users = get_users(['fields' => ['ID', 'display_name', 'user_email']]);
        $rows = [];
        foreach ($users as $u) {
            $type = get_user_meta($u->ID, 'wfp_employee_type', true) ?: '';
            $status = get_user_meta($u->ID, 'wfp_status', true) ?: 'pending';
            $rows[] = [ 'id' => (int)$u->ID, 'name' => $u->display_name, 'email' => $u->user_email, 'type' => $type, 'status' => $status ];
        }
        return ['items' => $rows];
    }

    public static function updateEmployee(\WP_REST_Request $req)
    {
        $id = (int) $req->get_param('id');
        if (get_user_by('ID', $id) === false) {
            return new \WP_Error('wfp_no_user', __('User not found', 'workflux-pro'), ['status' => 404]);
        }
        if ($req->get_param('type') !== null) {
            update_user_meta($id, 'wfp_employee_type', sanitize_text_field((string)$req->get_param('type')));
        }
        if ($req->get_param('status') !== null) {
            $status = sanitize_text_field((string)$req->get_param('status'));
            if (!in_array($status, ['active', 'pending', 'disabled'], true)) {
                return new \WP_Error('wfp_bad_status', __('Invalid status', 'workflux-pro'), ['status' => 400]);
            }
            update_user_meta($id, 'wfp_status', $status);
        }
        return ['ok' => true];
    }

    public static function getProjectMembers(\WP_REST_Request $req)
    {
        global $wpdb; $t = $wpdb->prefix . 'wfp_project_members';
        $id = (int) $req->get_param('id');
        $rows = $wpdb->get_results($wpdb->prepare("SELECT user_id, role FROM $t WHERE project_id = %d", $id), ARRAY_A);
        return ['items' => $rows ?: []];
    }

    public static function addProjectMember(\WP_REST_Request $req)
    {
        global $wpdb; $t = $wpdb->prefix . 'wfp_project_members';
        $id = (int) $req->get_param('id');
        $user_id = (int) $req->get_param('user_id');
        $role = sanitize_text_field((string)$req->get_param('role'));
        $wpdb->replace($t, [ 'project_id' => $id, 'user_id' => $user_id, 'role' => $role ?: null ]);
        return ['ok' => true];
    }

    public static function removeProjectMember(\WP_REST_Request $req)
    {
        global $wpdb; $t = $wpdb->prefix . 'wfp_project_members';
        $id = (int) $req->get_param('id');
        $user_id = (int) $req->get_param('user_id');
        $wpdb->delete($t, [ 'project_id' => $id, 'user_id' => $user_id ]);
        return ['ok' => true];
    }

    public static function getProjects(\WP_REST_Request $req)
    {
        global $wpdb; $t = $wpdb->prefix . 'wfp_projects';
        $rows = $wpdb->get_results("SELECT id, name, description, deadline, status, created_by, created_at FROM $t ORDER BY id DESC LIMIT 200", ARRAY_A);
        return ['items' => $rows ?: []];
    }

    public static function createProject(\WP_REST_Request $req)
    {
        global $wpdb; $t = $wpdb->prefix . 'wfp_projects';
        $name = sanitize_text_field((string)$req->get_param('name'));
        $desc = sanitize_textarea_field((string)$req->get_param('description'));
        $deadline = sanitize_text_field((string)$req->get_param('deadline'));
        if (!$name) { return new \WP_Error('wfp_name_required', __('Name required', 'workflux-pro'), ['status' => 400]); }
        $wpdb->insert($t, [ 'name' => $name, 'description' => $desc ?: null, 'deadline' => $deadline ?: null, 'status' => 'active', 'created_by' => get_current_user_id() ]);
        return ['id' => (int)$wpdb->insert_id];
    }

    public static function getTasks(\WP_REST_Request $req)
    {
        global $wpdb; $t = $wpdb->prefix . 'wfp_tasks';
        $project_id = (int) $req->get_param('project_id');
        $mine = (bool) $req->get_param('mine');
        $where = '1=1'; $params = [];
        if ($project_id) { $where .= ' AND project_id = %d'; $params[] = $project_id; }
        if ($mine) { $where .= ' AND assignee_id = %d'; $params[] = get_current_user_id(); }
        $sql = $wpdb->prepare("SELECT id, project_id, assignee_id, title, description, priority, due_date, status, created_at FROM $t WHERE $where ORDER BY id DESC LIMIT 200", $params);
        $rows = $wpdb->get_results($sql, ARRAY_A);
        return ['items' => $rows ?: []];
    }

    public static function createTask(\WP_REST_Request $req)
    {
        global $wpdb; $t = $wpdb->prefix . 'wfp_tasks';
        $project_id = (int) $req->get_param('project_id');
        $assignee_id = (int) $req->get_param('assignee_id');
        $title = sanitize_text_field((string)$req->get_param('title'));
        $description = sanitize_textarea_field((string)$req->get_param('description'));
        $priority = sanitize_text_field((string)$req->get_param('priority')) ?: 'normal';
        $due_date = sanitize_text_field((string)$req->get_param('due_date'));
        if (!$project_id || !$title) { return new \WP_Error('wfp_bad_task', __('Project and title required', 'workflux-pro'), ['status' => 400]); }
        $wpdb->insert($t, [ 'project_id' => $project_id, 'assignee_id' => $assignee_id ?: null, 'title' => $title, 'description' => $description ?: null, 'priority' => $priority, 'due_date' => $due_date ?: null, 'status' => 'todo' ]);
        return ['id' => (int)$wpdb->insert_id];
    }

    public static function updateTaskStatus(\WP_REST_Request $req)
    {
        global $wpdb; $t = $wpdb->prefix . 'wfp_tasks';
        $id = (int) $req->get_param('id');
        $status = sanitize_text_field((string)$req->get_param('status'));
        $wpdb->update($t, [ 'status' => $status ], [ 'id' => $id ]);
        return ['id' => $id, 'status' => $status];
    }

    public static function startTimeLog(\WP_REST_Request $req)
    {
        global $wpdb; $t = $wpdb->prefix . 'wfp_time_logs';
        $task_id = (int) $req->get_param('task_id');
        $user_id = get_current_user_id();
        // Enforce project membership
        $task = $wpdb->get_row($wpdb->prepare('SELECT project_id FROM ' . $wpdb->prefix . 'wfp_tasks WHERE id = %d', $task_id));
        if (!$task) { return new \WP_Error('wfp_no_task', __('Task not found', 'workflux-pro'), ['status' => 404]); }
        if (!self::isManagement()) {
            $pm = $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . $wpdb->prefix . 'wfp_project_members WHERE project_id = %d AND user_id = %d', $task->project_id, $user_id));
            if (!$pm) { return new \WP_Error('wfp_not_member', __('Not a member of this project', 'workflux-pro'), ['status' => 403]); }
        }
        // Close any open logs for this user
        $open = $wpdb->get_row($wpdb->prepare("SELECT id, started_at FROM $t WHERE user_id = %d AND ended_at IS NULL ORDER BY id DESC LIMIT 1", $user_id));
        if ($open) {
            $started = strtotime($open->started_at);
            $dur = (int) round((time() - $started) / 60);
            $wpdb->update($t, [ 'ended_at' => current_time('mysql'), 'duration_minutes' => max(1,$dur) ], [ 'id' => $open->id ]);
        }
        $wpdb->insert($t, [ 'task_id' => $task_id, 'user_id' => $user_id, 'started_at' => current_time('mysql') ]);
        return ['id' => (int)$wpdb->insert_id];
    }

    public static function stopTimeLog(\WP_REST_Request $req)
    {
        global $wpdb; $t = $wpdb->prefix . 'wfp_time_logs';
        $task_id = (int) $req->get_param('task_id');
        $user_id = get_current_user_id();
        $row = $wpdb->get_row($wpdb->prepare("SELECT id, started_at FROM $t WHERE user_id = %d AND task_id = %d AND ended_at IS NULL ORDER BY id DESC LIMIT 1", $user_id, $task_id));
        if (!$row) { return new \WP_Error('wfp_no_open_log', __('No open log', 'workflux-pro'), ['status' => 400]); }
        $started = strtotime($row->started_at);
        $dur = (int) round((time() - $started) / 60);
        $wpdb->update($t, [ 'ended_at' => current_time('mysql'), 'duration_minutes' => max(1,$dur) ], [ 'id' => $row->id ]);
        return ['id' => (int)$row->id, 'duration_minutes' => max(1,$dur) ];
    }

    public static function getTimeReport(\WP_REST_Request $req)
    {
        global $wpdb; $t = $wpdb->prefix . 'wfp_time_logs'; $tasks = $wpdb->prefix . 'wfp_tasks'; $projects = $wpdb->prefix . 'wfp_projects';
        $user_id = (int) $req->get_param('user_id');
        $project_id = (int) $req->get_param('project_id');
        $where = '1=1'; $params = [];
        if ($user_id) { $where .= ' AND l.user_id = %d'; $params[] = $user_id; }
        if ($project_id) { $where .= ' AND t.project_id = %d'; $params[] = $project_id; }
        $sql = $wpdb->prepare("SELECT t.project_id, SUM(l.duration_minutes) AS minutes FROM $t l JOIN $tasks t ON t.id = l.task_id WHERE $where GROUP BY t.project_id", $params);
        $rows = $wpdb->get_results($sql, ARRAY_A);
        return ['items' => $rows ?: []];
    }

    private static function isManagement(): bool
    {
        return current_user_can('wfp_view_reports') || current_user_can('wfp_manage_projects') || current_user_can('wfp_manage_employees') || current_user_can('wfp_manage_settings') || current_user_can('manage_options');
    }

    public static function getAttendanceReport(\WP_REST_Request $req)
    {
        global $wpdb; $t = $wpdb->prefix . 'wfp_attendance';
        $user_id = (int) $req->get_param('user_id');
        $from = $req->get_param('from');
        $to = $req->get_param('to');
        $current = get_current_user_id();

        if (!self::isManagement()) {
            // Employees can only view their own
            $user_id = $current;
        }

        $where = '1=1'; $params = [];
        if ($user_id) { $where .= ' AND a.user_id = %d'; $params[] = $user_id; }
        if ($from) { $where .= ' AND a.clock_in >= %s'; $params[] = $from; }
        if ($to) { $where .= ' AND (a.clock_out IS NULL OR a.clock_out <= %s)'; $params[] = $to; }
        $sql = "SELECT a.id, a.user_id, u.display_name AS user_name, a.clock_in, a.clock_out, a.activity
                FROM $t a JOIN {$wpdb->users} u ON u.ID = a.user_id
                WHERE $where ORDER BY a.id DESC LIMIT 500";
        $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        return ['items' => $rows ?: []];
    }

    public static function getTimeLogsDetailed(\WP_REST_Request $req)
    {
        global $wpdb; $t = $wpdb->prefix . 'wfp_time_logs'; $tasks = $wpdb->prefix . 'wfp_tasks'; $projects = $wpdb->prefix . 'wfp_projects';
        $user_id = (int) $req->get_param('user_id');
        $project_id = (int) $req->get_param('project_id');
        $from = $req->get_param('from');
        $to = $req->get_param('to');
        $current = get_current_user_id();

        if (!self::isManagement()) {
            $user_id = $current;
        }

        $where = '1=1'; $params = [];
        if ($user_id) { $where .= ' AND l.user_id = %d'; $params[] = $user_id; }
        if ($project_id) { $where .= ' AND t.project_id = %d'; $params[] = $project_id; }
        if ($from) { $where .= ' AND l.started_at >= %s'; $params[] = $from; }
        if ($to) { $where .= ' AND (l.ended_at IS NULL OR l.ended_at <= %s)'; $params[] = $to; }
        $sql = "SELECT l.id, l.user_id, u.display_name AS user_name, l.task_id, t.title AS task_title, t.project_id, p.name AS project_name,
                       l.started_at, l.ended_at, l.duration_minutes
                FROM $t l JOIN $tasks t ON t.id = l.task_id JOIN $projects p ON p.id = t.project_id JOIN {$wpdb->users} u ON u.ID = l.user_id
                WHERE $where ORDER BY l.id DESC LIMIT 1000";
        $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        return ['items' => $rows ?: []];
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


# WorkFlux Pro (WordPress Plugin)

Workforce and project management: roles, attendance, leaves, projects, tasks, dashboards.

## Install

1. Zip the `workflux-pro` folder or use the prebuilt zip (if provided).
2. In WordPress Admin → Plugins → Add New → Upload Plugin → select the zip.
3. Activate. The plugin will create roles, capabilities, and tables.

## Shortcodes

- `[wfp_employee_dashboard]`: Employee actions (clock in/out) placeholder.
- `[wfp_admin_dashboard]`: Admin overview placeholder.

## REST API

- GET `/wp-json/wfp/v1/ping` — health check
- GET `/wp-json/wfp/v1/dashboard/summary` — basic summary (stub)
- POST `/wp-json/wfp/v1/attendance/clock` with body `{ "action": "in" | "out", "activity"?: string }`

## Admin Menu

WorkFlux Pro top-level menu with subpages for Employees, Attendance, Leaves, Projects, Reports, Settings.

## Notes

- Data model tables: `wfp_attendance`, `wfp_leaves`, `wfp_projects`, `wfp_tasks`, `wfp_time_logs`.
- This is a foundation; replace stubs with real implementations and UI.
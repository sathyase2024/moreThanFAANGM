# WorkFlux Pro - WordPress Employee Management Plugin

**Developer:** Nagarajarao C R  
**Developed by:** Sri Hayavadhana Info-Tech  
**Version:** 1.0.0  
**Platform:** WordPress  

## Overview

WorkFlux Pro is a comprehensive WordPress plugin designed for employee management with role-based permissions, time tracking, project management, leave management, and reporting capabilities. The plugin provides a complete solution for managing workforce activities in organizations of all sizes.

## Features

### 🔐 Role-Based System
- **Super Admin/Owner:** Full access, manage all employees, projects, leaves, reports
- **Managing Head:** Oversees teams & approvals, manage team activity, approve leaves/external duty, view reports  
- **HR/Manager:** Employee onboarding & approvals, approve leave/external duty, assign projects
- **Project Admin:** Project-level management, manage projects, assign tasks, approve team members
- **Employee:** Clock In/Out, start/stop projects, submit leave requests, external duty requests

### ⏰ Time Tracking
- Employee clock in/out system
- Project-based time tracking
- Task-level time recording
- Real-time status monitoring
- Break time management
- Location-based tracking (optional)
- Weekly/monthly hour summaries

### 📊 Project Management
- Create and manage projects
- Task assignment and tracking
- Project progress monitoring
- Team member assignments
- Project code generation
- Priority and status management
- Time allocation and budget tracking

### 🏖️ Leave Management
- Multiple leave types (Annual, Sick, Personal, Maternity, Paternity, Emergency)
- Leave balance tracking
- Request submission and approval workflow
- Hierarchical approval system
- Leave calendar integration
- Email notifications

### 🚗 External Duty Management
- External duty request submission
- Location and purpose tracking
- Time period specification
- Approval workflow
- Activity monitoring

### 📈 Comprehensive Reporting
- Attendance reports
- Timesheet reports
- Project summary reports
- Employee performance reports
- Leave summary reports
- Department-wise analytics
- Exportable reports (CSV)

### 🎨 Modern UI/UX
- Responsive admin dashboard
- Modern frontend interface
- AJAX-driven interactions
- Mobile-friendly design
- Role-specific dashboards
- Real-time notifications

## Installation

1. **Upload Plugin Files:**
   ```
   /wp-content/plugins/workflux-pro/
   ```

2. **Activate Plugin:**
   - Go to WordPress Admin > Plugins
   - Find "WorkFlux Pro" and click "Activate"

3. **Database Setup:**
   - Plugin automatically creates necessary database tables upon activation

4. **Initial Configuration:**
   - Go to WorkFlux Pro > Settings
   - Configure company details and preferences
   - Set up leave policies and time tracking options

## File Structure

```
workflux-pro/
├── workflux-pro.php              # Main plugin file
├── includes/                     # Core functionality
│   ├── class-database.php        # Database management
│   ├── class-roles.php          # Role management
│   ├── class-permissions.php    # Permission handling
│   ├── class-user-management.php # Employee management
│   ├── class-time-tracking.php  # Time tracking system
│   ├── class-leave-management.php # Leave management
│   ├── class-external-duty.php  # External duty management
│   ├── class-project-management.php # Project management
│   ├── class-reports.php        # Reporting system
│   ├── class-ajax.php           # AJAX handlers
│   └── class-rest-api.php       # REST API endpoints
├── admin/                       # Admin interface
│   ├── class-admin.php          # Admin functionality
│   ├── class-admin-menu.php     # Admin menu system
│   └── class-admin-dashboard.php # Admin dashboard
├── frontend/                    # Frontend interface
│   ├── class-frontend.php       # Frontend functionality
│   └── class-shortcodes.php     # Shortcode system
├── assets/                      # CSS/JS assets
│   ├── css/
│   │   ├── admin.css           # Admin styles
│   │   └── frontend.css        # Frontend styles
│   └── js/
│       ├── admin.js            # Admin JavaScript
│       └── frontend.js         # Frontend JavaScript
└── README.md                   # This file
```

## Database Tables

The plugin creates the following database tables:

- `wp_wfp_employees` - Employee records
- `wp_wfp_projects` - Project information
- `wp_wfp_tasks` - Task management
- `wp_wfp_time_tracking` - Time tracking records
- `wp_wfp_leave_requests` - Leave requests
- `wp_wfp_external_duty` - External duty requests
- `wp_wfp_project_assignments` - Project assignments
- `wp_wfp_notifications` - System notifications
- `wp_wfp_employee_settings` - Employee preferences

## Shortcodes

### Dashboard Shortcode
```php
[workflux_pro_dashboard user_role="employee" show_widgets="true" show_activities="true"]
```

### Time Tracker Shortcode
```php
[workflux_pro_time_tracker show_summary="true" show_project_selector="true"]
```

### Project List Shortcode
```php
[workflux_pro_project_list limit="10" show_tasks="false" status="active"]
```

### Leave Form Shortcode
```php
[workflux_pro_leave_form show_balance="true" redirect_url="/thank-you"]
```

### Profile Shortcode
```php
[workflux_pro_profile show_avatar="true" show_leave_balance="true" show_stats="true"]
```

### Login Shortcode
```php
[workflux_pro_login redirect="/dashboard" show_register_link="false"]
```

## REST API Endpoints

The plugin provides REST API endpoints for mobile app integration:

### Time Tracking
- `POST /wp-json/workflux-pro/v1/time-tracking/clock-in`
- `POST /wp-json/workflux-pro/v1/time-tracking/clock-out`
- `GET /wp-json/workflux-pro/v1/time-tracking/status`

### Projects
- `POST /wp-json/workflux-pro/v1/projects/start`
- `POST /wp-json/workflux-pro/v1/projects/stop`
- `GET /wp-json/workflux-pro/v1/projects/my-projects`

### Leave Management
- `POST /wp-json/workflux-pro/v1/leaves/submit`
- `GET /wp-json/workflux-pro/v1/leaves/balance`

### External Duty
- `POST /wp-json/workflux-pro/v1/external-duty/submit`

### Dashboard & Reports
- `GET /wp-json/workflux-pro/v1/dashboard/data`
- `POST /wp-json/workflux-pro/v1/reports/generate`

## Hooks and Filters

### Actions
```php
// Employee management
do_action('workflux_pro_employee_created', $employee_id, $data);
do_action('workflux_pro_employee_updated', $employee_id, $data);

// Time tracking
do_action('workflux_pro_clock_in', $user_id, $tracking_id);
do_action('workflux_pro_clock_out', $user_id, $tracking_id, $total_hours);

// Project management
do_action('workflux_pro_project_created', $project_id, $data);
do_action('workflux_pro_project_assigned', $project_id, $employee_id, $assigned_by);

// Leave management
do_action('workflux_pro_leave_request_submitted', $request_id, $data);
do_action('workflux_pro_leave_request_approved', $request_id, $approver_id);
```

### Filters
```php
// Leave allocation
apply_filters('workflux_pro_leave_allocation', $allocation, $employee_id, $leave_type);

// Permission checks
apply_filters('workflux_pro_user_can_access', $can_access, $user_id, $capability);

// Dashboard data
apply_filters('workflux_pro_dashboard_data', $data, $user_role, $user_id);
```

## Configuration

### Settings Location
- WordPress Admin > WorkFlux Pro > Settings

### Available Settings
- Company Information
- Time Zone Configuration
- Time Tracking Preferences
- Leave Policies
- Email Notifications
- Mobile App Integration
- Report Preferences

## Frontend Pages

The plugin creates virtual frontend pages accessible at:

- `/workflux-pro/` - Dashboard
- `/workflux-pro/time-tracking/` - Time Tracking Interface
- `/workflux-pro/projects/` - Project Management
- `/workflux-pro/profile/` - User Profile

## Security Features

- Role-based access control
- Nonce verification for all AJAX requests
- Data sanitization and validation
- SQL injection prevention
- XSS protection
- CSRF protection

## Browser Compatibility

- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Server Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- Minimum 128MB PHP memory limit
- Modern web server (Apache/Nginx)

## Support & Documentation

For support and detailed documentation:

- **Developer:** Nagarajarao C R
- **Company:** Sri Hayavadhana Info-Tech
- **Email:** support@srihayavadhana.com
- **Website:** https://srihayavadhana.com

## License

This plugin is proprietary software developed by Sri Hayavadhana Info-Tech. All rights reserved.

## Changelog

### Version 1.0.0
- Initial release
- Complete role-based employee management system
- Time tracking with project integration
- Leave and external duty management
- Comprehensive reporting system
- Modern responsive UI
- REST API integration
- Mobile-friendly design

## Technical Notes

### Performance Optimization
- Efficient database queries with proper indexing
- AJAX-driven interfaces to reduce page loads
- Caching for frequently accessed data
- Optimized CSS and JavaScript assets

### Scalability
- Designed to handle organizations with 100+ employees
- Efficient data structures for large datasets
- Pagination for large data lists
- Background processing for heavy operations

### Integration
- Compatible with popular WordPress themes
- Integration-ready with third-party plugins
- REST API for mobile app development
- Export capabilities for external systems
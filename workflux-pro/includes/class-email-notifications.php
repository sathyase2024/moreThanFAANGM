<?php
/**
 * Email notifications class
 *
 * @package WorkFluxPro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkFlux Pro Email Notifications Class
 */
class WorkFluxPro_Email_Notifications {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_mail_failed', array($this, 'log_mail_error'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Hook into leave management actions
        add_action('workflux_pro_leave_request_submitted', array($this, 'send_leave_request_notification'), 10, 2);
        add_action('workflux_pro_leave_request_approved', array($this, 'send_leave_approval_notification'), 10, 2);
        add_action('workflux_pro_leave_request_rejected', array($this, 'send_leave_rejection_notification'), 10, 2);
        
        // Hook into external duty actions
        add_action('workflux_pro_external_duty_submitted', array($this, 'send_external_duty_notification'), 10, 2);
        add_action('workflux_pro_external_duty_approved', array($this, 'send_external_duty_approval_notification'), 10, 2);
        add_action('workflux_pro_external_duty_rejected', array($this, 'send_external_duty_rejection_notification'), 10, 2);
    }
    
    /**
     * Send leave request notification to approvers
     *
     * @param int $request_id
     * @param array $request_data
     */
    public function send_leave_request_notification($request_id, $request_data) {
        global $wpdb;
        
        // Get request details
        $request = $this->get_leave_request_details($request_id);
        if (!$request) {
            return;
        }
        
        // Get approvers
        $approvers = $this->get_leave_approvers($request->employee_id);
        
        foreach ($approvers as $approver) {
            $this->send_email(
                $approver->user_email,
                __('New Leave Request - Action Required', 'workflux-pro'),
                $this->get_leave_request_email_template($request, $approver),
                'leave_request'
            );
        }
    }
    
    /**
     * Send leave approval notification to employee
     *
     * @param int $request_id
     * @param int $approver_id
     */
    public function send_leave_approval_notification($request_id, $approver_id) {
        $request = $this->get_leave_request_details($request_id);
        if (!$request) {
            return;
        }
        
        $approver = get_userdata($request->approved_by_user_id);
        
        $this->send_email(
            $request->employee_email,
            __('Leave Request Approved', 'workflux-pro'),
            $this->get_leave_approval_email_template($request, $approver),
            'leave_approved'
        );
    }
    
    /**
     * Send leave rejection notification to employee
     *
     * @param int $request_id
     * @param int $approver_id
     */
    public function send_leave_rejection_notification($request_id, $approver_id) {
        $request = $this->get_leave_request_details($request_id);
        if (!$request) {
            return;
        }
        
        $approver = get_userdata($request->approved_by_user_id);
        
        $this->send_email(
            $request->employee_email,
            __('Leave Request Update', 'workflux-pro'),
            $this->get_leave_rejection_email_template($request, $approver),
            'leave_rejected'
        );
    }
    
    /**
     * Get leave request details
     *
     * @param int $request_id
     * @return object|null
     */
    private function get_leave_request_details($request_id) {
        global $wpdb;
        
        $leave_requests_table = $wpdb->prefix . 'wfp_leave_requests';
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT 
                lr.*,
                e.employee_id,
                e.department,
                e.designation,
                u.display_name as employee_name,
                u.user_email as employee_email,
                approver_u.display_name as approver_name,
                approver_u.user_email as approver_email,
                approver_e.user_id as approved_by_user_id
            FROM $leave_requests_table lr
            JOIN $employees_table e ON lr.employee_id = e.id
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            LEFT JOIN $employees_table approver_e ON lr.approved_by = approver_e.id
            LEFT JOIN {$wpdb->users} approver_u ON approver_e.user_id = approver_u.ID
            WHERE lr.id = %d
        ", $request_id));
    }
    
    /**
     * Get leave approvers for an employee
     *
     * @param int $employee_id
     * @return array
     */
    private function get_leave_approvers($employee_id) {
        global $wpdb;
        
        $employees_table = $wpdb->prefix . 'wfp_employees';
        
        // Get employee details
        $employee = $wpdb->get_row($wpdb->prepare("
            SELECT e.*, u.display_name, u.user_email
            FROM $employees_table e
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE e.id = %d
        ", $employee_id));
        
        if (!$employee) {
            return array();
        }
        
        $approvers = array();
        
        // Get direct manager
        if ($employee->manager_id) {
            $manager = $wpdb->get_row($wpdb->prepare("
                SELECT e.*, u.display_name, u.user_email
                FROM $employees_table e
                JOIN {$wpdb->users} u ON e.user_id = u.ID
                WHERE e.id = %d
            ", $employee->manager_id));
            
            if ($manager) {
                $approvers[] = $manager;
            }
        }
        
        // Get HR managers
        $hr_managers = get_users(array(
            'role__in' => array('wfp_hr_manager', 'wfp_managing_head', 'wfp_super_admin'),
            'fields' => array('ID', 'display_name', 'user_email')
        ));
        
        foreach ($hr_managers as $hr_manager) {
            // Avoid duplicates
            $exists = false;
            foreach ($approvers as $approver) {
                if ($approver->user_email === $hr_manager->user_email) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $approvers[] = $hr_manager;
            }
        }
        
        return $approvers;
    }
    
    /**
     * Get leave request email template
     *
     * @param object $request
     * @param object $approver
     * @return string
     */
    private function get_leave_request_email_template($request, $approver) {
        $leave_types = WorkFluxPro_Leave_Management::get_leave_types();
        $leave_type_name = $leave_types[$request->leave_type] ?? $request->leave_type;
        
        $template = $this->get_email_header();
        
        $template .= sprintf('
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2 style="color: #333; margin-top: 0;">New Leave Request</h2>
                <p>Dear %s,</p>
                <p>A new leave request has been submitted and requires your approval:</p>
                
                <div style="background-color: white; padding: 15px; border-radius: 5px; border-left: 4px solid #007cba;">
                    <table style="width: 100%%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold; width: 150px;">Employee:</td>
                            <td style="padding: 8px 0;">%s (%s)</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Department:</td>
                            <td style="padding: 8px 0;">%s</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Position:</td>
                            <td style="padding: 8px 0;">%s</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Leave Type:</td>
                            <td style="padding: 8px 0;">%s</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Start Date:</td>
                            <td style="padding: 8px 0;">%s</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">End Date:</td>
                            <td style="padding: 8px 0;">%s</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Duration:</td>
                            <td style="padding: 8px 0;">%s day(s)</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Reason:</td>
                            <td style="padding: 8px 0;">%s</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Request Date:</td>
                            <td style="padding: 8px 0;">%s</td>
                        </tr>
                    </table>
                </div>
                
                <div style="margin: 20px 0;">
                    <a href="%s" style="background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-right: 10px;">Review Request</a>
                </div>
                
                <p style="color: #666; font-size: 14px; margin-top: 20px;">
                    Please review this request at your earliest convenience. You can approve or reject this request through the WorkFlux Pro dashboard.
                </p>
            </div>',
            esc_html($approver->display_name),
            esc_html($request->employee_name),
            esc_html($request->employee_id),
            esc_html($request->department),
            esc_html($request->designation),
            esc_html($leave_type_name),
            esc_html(date('F j, Y', strtotime($request->start_date))),
            esc_html(date('F j, Y', strtotime($request->end_date))),
            esc_html($request->days_requested),
            esc_html($request->reason ?: 'No reason provided'),
            esc_html(date('F j, Y g:i A', strtotime($request->created_at))),
            esc_url(admin_url('admin.php?page=wfp-leave-management&action=review&request_id=' . $request->id))
        );
        
        $template .= $this->get_email_footer();
        
        return $template;
    }
    
    /**
     * Get leave approval email template
     *
     * @param object $request
     * @param object $approver
     * @return string
     */
    private function get_leave_approval_email_template($request, $approver) {
        $leave_types = WorkFluxPro_Leave_Management::get_leave_types();
        $leave_type_name = $leave_types[$request->leave_type] ?? $request->leave_type;
        
        $template = $this->get_email_header();
        
        $template .= sprintf('
            <div style="background-color: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #c3e6cb;">
                <h2 style="color: #155724; margin-top: 0;">✅ Leave Request Approved</h2>
                <p>Dear %s,</p>
                <p>Great news! Your leave request has been <strong>approved</strong>.</p>
                
                <div style="background-color: white; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;">
                    <table style="width: 100%%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold; width: 150px;">Leave Type:</td>
                            <td style="padding: 8px 0;">%s</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Start Date:</td>
                            <td style="padding: 8px 0;">%s</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">End Date:</td>
                            <td style="padding: 8px 0;">%s</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Duration:</td>
                            <td style="padding: 8px 0;">%s day(s)</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Approved by:</td>
                            <td style="padding: 8px 0;">%s</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Approval Date:</td>
                            <td style="padding: 8px 0;">%s</td>
                        </tr>
                        %s
                    </table>
                </div>
                
                <p style="color: #155724; font-weight: bold; margin: 20px 0;">
                    📅 Your leave has been scheduled. Please ensure all your work responsibilities are properly handed over before your leave begins.
                </p>
                
                <div style="margin: 20px 0;">
                    <a href="%s" style="background-color: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;">View Leave Details</a>
                </div>
            </div>',
            esc_html($request->employee_name),
            esc_html($leave_type_name),
            esc_html(date('F j, Y', strtotime($request->start_date))),
            esc_html(date('F j, Y', strtotime($request->end_date))),
            esc_html($request->days_requested),
            esc_html($approver ? $approver->display_name : 'System'),
            esc_html(date('F j, Y g:i A', strtotime($request->approved_at))),
            $request->comments ? sprintf('<tr><td style="padding: 8px 0; font-weight: bold;">Comments:</td><td style="padding: 8px 0;">%s</td></tr>', esc_html($request->comments)) : '',
            esc_url(admin_url('admin.php?page=wfp-leave-management&action=view&request_id=' . $request->id))
        );
        
        $template .= $this->get_email_footer();
        
        return $template;
    }
    
    /**
     * Get leave rejection email template
     *
     * @param object $request
     * @param object $approver
     * @return string
     */
    private function get_leave_rejection_email_template($request, $approver) {
        $leave_types = WorkFluxPro_Leave_Management::get_leave_types();
        $leave_type_name = $leave_types[$request->leave_type] ?? $request->leave_type;
        
        $template = $this->get_email_header();
        
        $template .= sprintf('
            <div style="background-color: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #f5c6cb;">
                <h2 style="color: #721c24; margin-top: 0;">❌ Leave Request Update</h2>
                <p>Dear %s,</p>
                <p>We regret to inform you that your leave request has been <strong>declined</strong>.</p>
                
                <div style="background-color: white; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;">
                    <table style="width: 100%%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold; width: 150px;">Leave Type:</td>
                            <td style="padding: 8px 0;">%s</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Requested Dates:</td>
                            <td style="padding: 8px 0;">%s to %s</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Duration:</td>
                            <td style="padding: 8px 0;">%s day(s)</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Reviewed by:</td>
                            <td style="padding: 8px 0;">%s</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Review Date:</td>
                            <td style="padding: 8px 0;">%s</td>
                        </tr>
                        %s
                    </table>
                </div>
                
                <p style="color: #721c24; margin: 20px 0;">
                    If you have any questions about this decision or would like to discuss alternative arrangements, please contact your manager or HR department.
                </p>
                
                <div style="margin: 20px 0;">
                    <a href="%s" style="background-color: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;">View Leave Details</a>
                    <a href="%s" style="background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-left: 10px;">Submit New Request</a>
                </div>
            </div>',
            esc_html($request->employee_name),
            esc_html($leave_type_name),
            esc_html(date('F j, Y', strtotime($request->start_date))),
            esc_html(date('F j, Y', strtotime($request->end_date))),
            esc_html($request->days_requested),
            esc_html($approver ? $approver->display_name : 'System'),
            esc_html(date('F j, Y g:i A', strtotime($request->approved_at))),
            $request->comments ? sprintf('<tr><td style="padding: 8px 0; font-weight: bold;">Reason:</td><td style="padding: 8px 0; color: #721c24;">%s</td></tr>', esc_html($request->comments)) : '',
            esc_url(admin_url('admin.php?page=wfp-leave-management&action=view&request_id=' . $request->id)),
            esc_url(admin_url('admin.php?page=wfp-leave-management&action=new'))
        );
        
        $template .= $this->get_email_footer();
        
        return $template;
    }
    
    /**
     * Send email
     *
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param string $type
     * @return bool
     */
    private function send_email($to, $subject, $message, $type = 'notification') {
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->get_from_name() . ' <' . $this->get_from_email() . '>'
        );
        
        // Add subject prefix
        $subject = '[WorkFlux Pro] ' . $subject;
        
        // Log email attempt
        $this->log_email_attempt($to, $subject, $type);
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Get email header
     *
     * @return string
     */
    private function get_email_header() {
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('url');
        
        return sprintf('
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>WorkFlux Pro Notification</title>
            </head>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background-color: #007cba; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
                    <h1 style="margin: 0; font-size: 24px;">WorkFlux Pro</h1>
                    <p style="margin: 5px 0 0 0; opacity: 0.9;">%s</p>
                </div>
                <div style="background-color: #f9f9f9; padding: 0; border-radius: 0 0 8px 8px; border: 1px solid #ddd; border-top: none;">',
            esc_html($site_name)
        );
    }
    
    /**
     * Get email footer
     *
     * @return string
     */
    private function get_email_footer() {
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('url');
        
        return sprintf('
                    <div style="background-color: #f1f1f1; padding: 20px; text-align: center; color: #666; font-size: 14px; border-top: 1px solid #ddd;">
                        <p style="margin: 0;">This email was sent by WorkFlux Pro system at <a href="%s" style="color: #007cba;">%s</a></p>
                        <p style="margin: 10px 0 0 0;">If you have any questions, please contact your HR department.</p>
                        <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                            Developed by Sri Hayavadhana Info-Tech | WorkFlux Pro v%s
                        </p>
                    </div>
                </div>
            </body>
            </html>',
            esc_url($site_url),
            esc_html($site_name),
            defined('WORKFLUX_PRO_VERSION') ? WORKFLUX_PRO_VERSION : '1.0.0'
        );
    }
    
    /**
     * Get from email
     *
     * @return string
     */
    private function get_from_email() {
        return get_option('admin_email');
    }
    
    /**
     * Get from name
     *
     * @return string
     */
    private function get_from_name() {
        return get_bloginfo('name') . ' - WorkFlux Pro';
    }
    
    /**
     * Log email attempt
     *
     * @param string $to
     * @param string $subject
     * @param string $type
     */
    private function log_email_attempt($to, $subject, $type) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'WorkFlux Pro Email: Sending %s to %s - Subject: %s',
                $type,
                $to,
                $subject
            ));
        }
    }
    
    /**
     * Log email error
     *
     * @param WP_Error $wp_error
     */
    public function log_mail_error($wp_error) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WorkFlux Pro Email Error: ' . $wp_error->get_error_message());
        }
    }
    
    /**
     * Send external duty notification (placeholder for future implementation)
     */
    public function send_external_duty_notification($request_id, $request_data) {
        // Implementation similar to leave notifications
        // Can be added in future versions
    }
    
    /**
     * Send external duty approval notification (placeholder)
     */
    public function send_external_duty_approval_notification($request_id, $approver_id) {
        // Implementation similar to leave approvals
    }
    
    /**
     * Send external duty rejection notification (placeholder)
     */
    public function send_external_duty_rejection_notification($request_id, $approver_id) {
        // Implementation similar to leave rejections
    }
}
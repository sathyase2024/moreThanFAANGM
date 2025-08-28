<?php
/**
 * Demo Users Creation Script
 * Creates WordPress users with appropriate roles for demonstration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create demo users for WorkFlux Pro demonstration
 */
function workflux_pro_create_demo_users() {
    
    $demo_users = array(
        // Super Admin / CEO
        array(
            'user_login' => 'ceo.admin',
            'user_email' => 'ceo@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Nagarajarao C R',
            'first_name' => 'Nagarajarao',
            'last_name' => 'C R',
            'role' => 'wfp_super_admin',
            'employee_data' => array(
                'employee_id' => 'EMP2024001',
                'department' => 'Management',
                'designation' => 'CEO & Founder'
            )
        ),
        
        // HR Manager
        array(
            'user_login' => 'hr.manager',
            'user_email' => 'hr@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Priya Sharma',
            'first_name' => 'Priya',
            'last_name' => 'Sharma',
            'role' => 'wfp_hr_manager',
            'employee_data' => array(
                'employee_id' => 'EMP2024002',
                'department' => 'Human Resources',
                'designation' => 'HR Manager'
            )
        ),
        
        // Managing Head / IT Director
        array(
            'user_login' => 'it.director',
            'user_email' => 'itdirector@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Rajesh Kumar',
            'first_name' => 'Rajesh',
            'last_name' => 'Kumar',
            'role' => 'wfp_managing_head',
            'employee_data' => array(
                'employee_id' => 'EMP2024003',
                'department' => 'Information Technology',
                'designation' => 'IT Director'
            )
        ),
        
        // Finance Manager
        array(
            'user_login' => 'finance.manager',
            'user_email' => 'finance@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Anita Desai',
            'first_name' => 'Anita',
            'last_name' => 'Desai',
            'role' => 'wfp_hr_manager',
            'employee_data' => array(
                'employee_id' => 'EMP2024004',
                'department' => 'Finance',
                'designation' => 'Finance Manager'
            )
        ),
        
        // Project Admin / Senior Developer
        array(
            'user_login' => 'senior.dev',
            'user_email' => 'seniordev@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Amit Patel',
            'first_name' => 'Amit',
            'last_name' => 'Patel',
            'role' => 'wfp_project_admin',
            'employee_data' => array(
                'employee_id' => 'EMP2024005',
                'department' => 'Information Technology',
                'designation' => 'Senior Developer'
            )
        ),
        
        // Employee / Frontend Developer
        array(
            'user_login' => 'frontend.dev',
            'user_email' => 'frontend@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Sneha Reddy',
            'first_name' => 'Sneha',
            'last_name' => 'Reddy',
            'role' => 'wfp_employee',
            'employee_data' => array(
                'employee_id' => 'EMP2024006',
                'department' => 'Information Technology',
                'designation' => 'Frontend Developer'
            )
        ),
        
        // Employee / Backend Developer
        array(
            'user_login' => 'backend.dev',
            'user_email' => 'backend@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Vikram Singh',
            'first_name' => 'Vikram',
            'last_name' => 'Singh',
            'role' => 'wfp_employee',
            'employee_data' => array(
                'employee_id' => 'EMP2024007',
                'department' => 'Information Technology',
                'designation' => 'Backend Developer'
            )
        ),
        
        // Marketing Manager
        array(
            'user_login' => 'marketing.manager',
            'user_email' => 'marketing@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Kavya Nair',
            'first_name' => 'Kavya',
            'last_name' => 'Nair',
            'role' => 'wfp_project_admin',
            'employee_data' => array(
                'employee_id' => 'EMP2024008',
                'department' => 'Marketing',
                'designation' => 'Marketing Manager'
            )
        ),
        
        // Sales Manager
        array(
            'user_login' => 'sales.manager',
            'user_email' => 'sales@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Rohit Gupta',
            'first_name' => 'Rohit',
            'last_name' => 'Gupta',
            'role' => 'wfp_project_admin',
            'employee_data' => array(
                'employee_id' => 'EMP2024009',
                'department' => 'Sales',
                'designation' => 'Sales Manager'
            )
        ),
        
        // DevOps Engineer
        array(
            'user_login' => 'devops.engineer',
            'user_email' => 'devops@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Arjun Mehta',
            'first_name' => 'Arjun',
            'last_name' => 'Mehta',
            'role' => 'wfp_employee',
            'employee_data' => array(
                'employee_id' => 'EMP2024010',
                'department' => 'Information Technology',
                'designation' => 'DevOps Engineer'
            )
        ),
        
        // HR Executive
        array(
            'user_login' => 'hr.executive',
            'user_email' => 'hrexec@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Pooja Agarwal',
            'first_name' => 'Pooja',
            'last_name' => 'Agarwal',
            'role' => 'wfp_employee',
            'employee_data' => array(
                'employee_id' => 'EMP2024011',
                'department' => 'Human Resources',
                'designation' => 'HR Executive'
            )
        ),
        
        // Accountant
        array(
            'user_login' => 'accountant',
            'user_email' => 'accounts@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Deepak Joshi',
            'first_name' => 'Deepak',
            'last_name' => 'Joshi',
            'role' => 'wfp_employee',
            'employee_data' => array(
                'employee_id' => 'EMP2024012',
                'department' => 'Finance',
                'designation' => 'Accountant'
            )
        ),
        
        // UI/UX Designer
        array(
            'user_login' => 'ui.designer',
            'user_email' => 'designer@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Neha Verma',
            'first_name' => 'Neha',
            'last_name' => 'Verma',
            'role' => 'wfp_employee',
            'employee_data' => array(
                'employee_id' => 'EMP2024013',
                'department' => 'Information Technology',
                'designation' => 'UI/UX Designer'
            )
        ),
        
        // Digital Marketing Executive
        array(
            'user_login' => 'digital.marketing',
            'user_email' => 'digitalmarketing@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Ravi Krishnan',
            'first_name' => 'Ravi',
            'last_name' => 'Krishnan',
            'role' => 'wfp_employee',
            'employee_data' => array(
                'employee_id' => 'EMP2024014',
                'department' => 'Marketing',
                'designation' => 'Digital Marketing Executive'
            )
        ),
        
        // Sales Executive
        array(
            'user_login' => 'sales.exec',
            'user_email' => 'salesexec@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Manish Tiwari',
            'first_name' => 'Manish',
            'last_name' => 'Tiwari',
            'role' => 'wfp_employee',
            'employee_data' => array(
                'employee_id' => 'EMP2024015',
                'department' => 'Sales',
                'designation' => 'Sales Executive'
            )
        ),
        
        // Junior Developer
        array(
            'user_login' => 'junior.dev',
            'user_email' => 'juniordev@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Aarti Mishra',
            'first_name' => 'Aarti',
            'last_name' => 'Mishra',
            'role' => 'wfp_employee',
            'employee_data' => array(
                'employee_id' => 'EMP2024016',
                'department' => 'Information Technology',
                'designation' => 'Junior Developer'
            )
        ),
        
        // Operations Manager
        array(
            'user_login' => 'operations.manager',
            'user_email' => 'operations@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Suresh Yadav',
            'first_name' => 'Suresh',
            'last_name' => 'Yadav',
            'role' => 'wfp_managing_head',
            'employee_data' => array(
                'employee_id' => 'EMP2024017',
                'department' => 'Operations',
                'designation' => 'Operations Manager'
            )
        ),
        
        // Support Manager
        array(
            'user_login' => 'support.manager',
            'user_email' => 'support@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Sunita Iyer',
            'first_name' => 'Sunita',
            'last_name' => 'Iyer',
            'role' => 'wfp_project_admin',
            'employee_data' => array(
                'employee_id' => 'EMP2024018',
                'department' => 'Customer Support',
                'designation' => 'Support Manager'
            )
        ),
        
        // Support Executive
        array(
            'user_login' => 'support.exec',
            'user_email' => 'supportexec@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Kiran Patil',
            'first_name' => 'Kiran',
            'last_name' => 'Patil',
            'role' => 'wfp_employee',
            'employee_data' => array(
                'employee_id' => 'EMP2024019',
                'department' => 'Customer Support',
                'designation' => 'Support Executive'
            )
        ),
        
        // QA Engineer
        array(
            'user_login' => 'qa.engineer',
            'user_email' => 'qa@srihayavadhana.com',
            'user_pass' => 'Demo@2024',
            'display_name' => 'Rahul Chopra',
            'first_name' => 'Rahul',
            'last_name' => 'Chopra',
            'role' => 'wfp_employee',
            'employee_data' => array(
                'employee_id' => 'EMP2024020',
                'department' => 'Information Technology',
                'designation' => 'QA Engineer'
            )
        )
    );
    
    foreach ($demo_users as $user_data) {
        // Check if user already exists
        if (!username_exists($user_data['user_login']) && !email_exists($user_data['user_email'])) {
            
            // Create WordPress user
            $user_id = wp_insert_user(array(
                'user_login' => $user_data['user_login'],
                'user_email' => $user_data['user_email'],
                'user_pass' => $user_data['user_pass'],
                'display_name' => $user_data['display_name'],
                'first_name' => $user_data['first_name'],
                'last_name' => $user_data['last_name'],
                'role' => 'subscriber' // Will be changed to WorkFlux role
            ));
            
            if (!is_wp_error($user_id)) {
                // Assign WorkFlux role
                $user = new WP_User($user_id);
                $user->remove_role('subscriber');
                $user->add_role($user_data['role']);
                
                // Create employee record if WorkFlux Pro is active
                if (class_exists('WorkFluxPro_User_Management')) {
                    $employee_data = $user_data['employee_data'];
                    $employee_data['user_id'] = $user_id;
                    $employee_data['hire_date'] = date('Y-m-d', strtotime('-' . rand(365, 1095) . ' days'));
                    $employee_data['status'] = 'active';
                    
                    WorkFluxPro_User_Management::create_employee($employee_data);
                }
                
                echo "✓ Created user: " . $user_data['display_name'] . " (" . $user_data['user_login'] . ")\n";
            } else {
                echo "✗ Failed to create user: " . $user_data['display_name'] . "\n";
            }
        } else {
            echo "- User already exists: " . $user_data['user_login'] . "\n";
        }
    }
}

/**
 * Demo login credentials for client presentation
 */
function workflux_pro_get_demo_credentials() {
    return array(
        'Super Admin (CEO)' => array(
            'username' => 'ceo.admin',
            'password' => 'Demo@2024',
            'role' => 'Full System Access',
            'features' => 'All management features, system settings, complete reporting'
        ),
        'Managing Head (IT Director)' => array(
            'username' => 'it.director',
            'password' => 'Demo@2024',
            'role' => 'Team Management',
            'features' => 'Team oversight, leave approvals, team reports, project management'
        ),
        'HR Manager' => array(
            'username' => 'hr.manager',
            'password' => 'Demo@2024',
            'role' => 'Employee Management',
            'features' => 'Employee onboarding, leave management, HR reports'
        ),
        'Project Admin (Senior Developer)' => array(
            'username' => 'senior.dev',
            'password' => 'Demo@2024',
            'role' => 'Project Management',
            'features' => 'Project creation, task assignment, team member approval'
        ),
        'Employee (Frontend Developer)' => array(
            'username' => 'frontend.dev',
            'password' => 'Demo@2024',
            'role' => 'Employee Level',
            'features' => 'Time tracking, leave requests, project participation'
        )
    );
}

// Uncomment the line below to create demo users
// workflux_pro_create_demo_users();
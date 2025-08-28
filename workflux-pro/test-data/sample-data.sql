-- WorkFlux Pro Test Data
-- Sample data for client demonstration
-- Execute this after plugin activation

-- Sample Employees Data
INSERT INTO wp_wfp_employees (user_id, employee_id, department, designation, hire_date, manager_id, status) VALUES
(1, 'EMP2024001', 'Management', 'CEO', '2020-01-15', NULL, 'active'),
(2, 'EMP2024002', 'Human Resources', 'HR Manager', '2020-03-10', 1, 'active'),
(3, 'EMP2024003', 'Information Technology', 'IT Director', '2020-05-20', 1, 'active'),
(4, 'EMP2024004', 'Finance', 'Finance Manager', '2021-02-15', 1, 'active'),
(5, 'EMP2024005', 'Information Technology', 'Senior Developer', '2021-06-01', 3, 'active'),
(6, 'EMP2024006', 'Information Technology', 'Frontend Developer', '2021-09-15', 3, 'active'),
(7, 'EMP2024007', 'Information Technology', 'Backend Developer', '2022-01-10', 3, 'active'),
(8, 'EMP2024008', 'Marketing', 'Marketing Manager', '2022-03-20', 1, 'active'),
(9, 'EMP2024009', 'Sales', 'Sales Manager', '2022-05-15', 1, 'active'),
(10, 'EMP2024010', 'Information Technology', 'DevOps Engineer', '2022-08-01', 3, 'active'),
(11, 'EMP2024011', 'Human Resources', 'HR Executive', '2022-10-15', 2, 'active'),
(12, 'EMP2024012', 'Finance', 'Accountant', '2023-01-20', 4, 'active'),
(13, 'EMP2024013', 'Information Technology', 'UI/UX Designer', '2023-03-15', 3, 'active'),
(14, 'EMP2024014', 'Marketing', 'Digital Marketing Executive', '2023-06-10', 8, 'active'),
(15, 'EMP2024015', 'Sales', 'Sales Executive', '2023-08-25', 9, 'active'),
(16, 'EMP2024016', 'Information Technology', 'Junior Developer', '2023-11-01', 5, 'active'),
(17, 'EMP2024017', 'Operations', 'Operations Manager', '2024-01-15', 1, 'active'),
(18, 'EMP2024018', 'Customer Support', 'Support Manager', '2024-02-20', 1, 'active'),
(19, 'EMP2024019', 'Customer Support', 'Support Executive', '2024-03-10', 18, 'active'),
(20, 'EMP2024020', 'Information Technology', 'QA Engineer', '2024-04-05', 3, 'active');

-- Sample Projects Data
INSERT INTO wp_wfp_projects (name, description, project_code, client, start_date, end_date, estimated_hours, actual_hours, status, priority, created_by, assigned_to) VALUES
('E-Commerce Platform Development', 'Complete e-commerce solution with modern features including payment gateway, inventory management, and customer portal', 'PRJ2024001', 'TechCorp Solutions', '2024-01-15', '2024-06-30', 1200.00, 480.50, 'active', 'high', 3, 5),
('Mobile App - iOS & Android', 'Cross-platform mobile application for customer engagement and service booking', 'PRJ2024002', 'ServiceHub Inc', '2024-02-01', '2024-08-15', 800.00, 320.25, 'active', 'high', 3, 6),
('ERP System Integration', 'Integration of existing ERP system with new modules for enhanced functionality', 'PRJ2024003', 'ManufacturingPro Ltd', '2024-03-01', '2024-09-30', 1500.00, 180.00, 'active', 'medium', 3, 7),
('Website Redesign Project', 'Complete redesign of corporate website with modern UI/UX and responsive design', 'PRJ2024004', 'DesignStudio Agency', '2024-02-15', '2024-05-15', 400.00, 240.75, 'active', 'medium', 3, 13),
('CRM Implementation', 'Custom CRM system development for sales and customer management', 'PRJ2024005', 'SalesForce Pro', '2024-04-01', '2024-10-31', 1000.00, 120.00, 'planning', 'high', 3, 5),
('Data Analytics Dashboard', 'Business intelligence dashboard with real-time analytics and reporting', 'PRJ2024006', 'DataInsights Corp', '2024-03-15', '2024-07-30', 600.00, 80.50, 'active', 'medium', 3, 10),
('Cloud Migration Project', 'Migration of legacy systems to cloud infrastructure with security enhancements', 'PRJ2024007', 'CloudTech Solutions', '2024-05-01', '2024-11-30', 900.00, 45.00, 'planning', 'high', 3, 10),
('Digital Marketing Campaign', 'Comprehensive digital marketing strategy and implementation for Q2-Q3', 'PRJ2024008', 'GrowthHackers Inc', '2024-04-15', '2024-09-15', 300.00, 85.25, 'active', 'medium', 8, 14),
('Internal HR System', 'Development of internal HR management system for employee data and processes', 'PRJ2024009', 'Internal Project', '2024-01-10', '2024-08-31', 800.00, 420.75, 'active', 'low', 2, 5),
('Customer Support Portal', 'Self-service customer support portal with ticketing and knowledge base', 'PRJ2024010', 'SupportTech Ltd', '2024-03-20', '2024-08-20', 500.00, 125.50, 'active', 'medium', 18, 6);

-- Sample Tasks Data
INSERT INTO wp_wfp_tasks (project_id, title, description, assigned_to, estimated_hours, actual_hours, status, priority, due_date, created_by) VALUES
(1, 'Database Schema Design', 'Design comprehensive database schema for e-commerce platform', 5, 40.00, 35.50, 'completed', 'high', '2024-02-15', 3),
(1, 'User Authentication Module', 'Implement secure user registration and login system', 7, 50.00, 45.25, 'completed', 'high', '2024-03-01', 3),
(1, 'Payment Gateway Integration', 'Integrate multiple payment gateways including PayPal and Stripe', 5, 60.00, 28.00, 'in_progress', 'high', '2024-04-15', 3),
(1, 'Product Catalog Management', 'Develop product catalog with categories, filters, and search', 6, 80.00, 72.50, 'completed', 'medium', '2024-03-30', 3),
(1, 'Shopping Cart & Checkout', 'Implement shopping cart functionality and checkout process', 5, 70.00, 25.75, 'in_progress', 'high', '2024-05-15', 3),
(2, 'Mobile App UI Design', 'Create modern and intuitive mobile app interface design', 13, 60.00, 55.00, 'completed', 'high', '2024-03-15', 3),
(2, 'API Development', 'Develop REST APIs for mobile app backend communication', 7, 80.00, 42.25, 'in_progress', 'high', '2024-04-30', 3),
(2, 'Push Notification System', 'Implement push notification service for both iOS and Android', 6, 40.00, 0.00, 'todo', 'medium', '2024-05-30', 3),
(3, 'System Analysis', 'Analyze existing ERP system and identify integration points', 10, 50.00, 45.00, 'completed', 'high', '2024-03-30', 3),
(3, 'Data Migration Planning', 'Plan data migration strategy from legacy to new system', 5, 40.00, 15.50, 'in_progress', 'medium', '2024-05-15', 3),
(4, 'Website Wireframes', 'Create detailed wireframes for all website pages', 13, 30.00, 28.75, 'completed', 'high', '2024-03-15', 3),
(4, 'Frontend Development', 'Develop responsive frontend using modern technologies', 6, 100.00, 85.50, 'in_progress', 'high', '2024-04-30', 3),
(4, 'Content Management Setup', 'Setup and configure content management system', 16, 25.00, 12.25, 'in_progress', 'low', '2024-05-10', 3),
(5, 'Requirements Gathering', 'Gather detailed requirements for CRM system', 2, 30.00, 25.00, 'completed', 'high', '2024-04-15', 3),
(6, 'Dashboard Mockups', 'Create interactive dashboard mockups and prototypes', 13, 35.00, 32.50, 'completed', 'medium', '2024-04-15', 3),
(6, 'Data Visualization Components', 'Develop reusable data visualization components', 6, 50.00, 18.00, 'in_progress', 'medium', '2024-06-01', 3),
(8, 'Marketing Strategy Document', 'Prepare comprehensive digital marketing strategy', 14, 20.00, 18.75, 'completed', 'high', '2024-05-01', 8),
(8, 'Social Media Campaign', 'Execute social media marketing campaign across platforms', 14, 40.00, 22.50, 'in_progress', 'medium', '2024-07-15', 8),
(9, 'HR System Requirements', 'Define requirements for internal HR management system', 11, 25.00, 20.00, 'completed', 'medium', '2024-02-15', 2),
(10, 'Support Portal Design', 'Design user interface for customer support portal', 13, 30.00, 25.50, 'completed', 'medium', '2024-04-30', 18);

-- Sample Project Assignments
INSERT INTO wp_wfp_project_assignments (project_id, employee_id, role, assigned_by, status) VALUES
(1, 5, 'Lead Developer', 3, 'active'),
(1, 6, 'Frontend Developer', 3, 'active'),
(1, 7, 'Backend Developer', 3, 'active'),
(1, 20, 'QA Engineer', 3, 'active'),
(2, 6, 'Mobile Developer', 3, 'active'),
(2, 13, 'UI/UX Designer', 3, 'active'),
(2, 7, 'Backend Developer', 3, 'active'),
(3, 5, 'Integration Specialist', 3, 'active'),
(3, 10, 'DevOps Engineer', 3, 'active'),
(4, 13, 'Lead Designer', 3, 'active'),
(4, 6, 'Frontend Developer', 3, 'active'),
(4, 16, 'Junior Developer', 3, 'active'),
(5, 5, 'Lead Developer', 3, 'active'),
(5, 7, 'Backend Developer', 3, 'active'),
(6, 10, 'Data Engineer', 3, 'active'),
(6, 6, 'Frontend Developer', 3, 'active'),
(7, 10, 'Cloud Architect', 3, 'active'),
(8, 14, 'Digital Marketing Lead', 8, 'active'),
(9, 5, 'System Developer', 2, 'active'),
(9, 11, 'Requirements Analyst', 2, 'active'),
(10, 6, 'Frontend Developer', 18, 'active'),
(10, 19, 'Content Specialist', 18, 'active');

-- Sample Time Tracking Data (Last 30 days)
INSERT INTO wp_wfp_time_tracking (employee_id, project_id, task_id, clock_in, clock_out, break_time, total_hours, description, location, ip_address, status) VALUES
-- Recent entries for active tracking demonstration
(5, 1, 3, '2024-12-21 09:00:00', '2024-12-21 17:30:00', 60, 7.50, 'Working on payment gateway integration - implementing Stripe API', 'Office - Desk 15', '192.168.1.15', 'completed'),
(6, 4, 12, '2024-12-21 09:15:00', '2024-12-21 18:00:00', 45, 8.00, 'Frontend development for website redesign - responsive components', 'Office - Desk 22', '192.168.1.22', 'completed'),
(7, 2, 7, '2024-12-21 08:45:00', '2024-12-21 17:15:00', 30, 8.00, 'API development for mobile app - user authentication endpoints', 'Remote Work', '203.45.67.89', 'completed'),
(13, 2, 6, '2024-12-21 10:00:00', '2024-12-21 17:45:00', 45, 7.00, 'Mobile app UI design - creating user profile screens', 'Office - Design Studio', '192.168.1.35', 'completed'),
(10, 6, 16, '2024-12-21 09:30:00', '2024-12-21 18:15:00', 30, 8.25, 'Data visualization components - chart library integration', 'Office - Desk 8', '192.168.1.08', 'completed'),
(14, 8, 18, '2024-12-21 10:30:00', '2024-12-21 16:30:00', 30, 5.50, 'Social media campaign execution - content creation and posting', 'Remote Work', '110.23.45.67', 'completed'),

-- Yesterday's data
(5, 1, 5, '2024-12-20 08:30:00', '2024-12-20 17:00:00', 45, 7.75, 'Shopping cart functionality - implementing cart persistence', 'Office - Desk 15', '192.168.1.15', 'completed'),
(6, 4, 12, '2024-12-20 09:00:00', '2024-12-20 17:30:00', 60, 7.50, 'Website frontend development - mobile responsiveness', 'Office - Desk 22', '192.168.1.22', 'completed'),
(7, 3, 10, '2024-12-20 09:15:00', '2024-12-20 18:00:00', 30, 8.25, 'ERP integration - data migration scripts development', 'Office - Desk 12', '192.168.1.12', 'completed'),
(20, 1, NULL, '2024-12-20 10:00:00', '2024-12-20 17:00:00', 45, 6.25, 'Quality assurance testing for e-commerce platform', 'Office - QA Lab', '192.168.1.45', 'completed'),
(16, 4, 13, '2024-12-20 09:30:00', '2024-12-20 17:45:00', 30, 7.75, 'Learning and implementing CMS configuration', 'Office - Desk 30', '192.168.1.30', 'completed'),

-- Week's data pattern
(5, 1, 3, '2024-12-19 08:45:00', '2024-12-19 17:30:00', 45, 8.00, 'Payment gateway testing and debugging', 'Office - Desk 15', '192.168.1.15', 'completed'),
(6, 2, 8, '2024-12-19 09:00:00', '2024-12-19 17:00:00', 60, 7.00, 'Mobile app frontend components development', 'Office - Desk 22', '192.168.1.22', 'completed'),
(7, 2, 7, '2024-12-19 08:30:00', '2024-12-19 17:15:00', 30, 8.25, 'API endpoints development and testing', 'Remote Work', '203.45.67.89', 'completed'),
(13, 4, 11, '2024-12-19 10:15:00', '2024-12-19 18:00:00', 45, 7.00, 'Website wireframe refinements and client feedback', 'Office - Design Studio', '192.168.1.35', 'completed'),
(14, 8, 17, '2024-12-19 11:00:00', '2024-12-19 17:30:00', 30, 6.00, 'Marketing strategy document finalization', 'Remote Work', '110.23.45.67', 'completed');

-- Sample Leave Requests
INSERT INTO wp_wfp_leave_requests (employee_id, leave_type, start_date, end_date, days_requested, reason, status, approved_by, approved_at, comments) VALUES
(6, 'annual', '2024-12-25', '2024-12-27', 3.0, 'Christmas vacation with family', 'approved', 3, '2024-12-15 14:30:00', 'Approved for holiday period'),
(14, 'sick', '2024-12-18', '2024-12-19', 2.0, 'Flu symptoms and fever', 'approved', 8, '2024-12-17 16:45:00', 'Get well soon'),
(16, 'personal', '2024-12-30', '2024-12-31', 2.0, 'Year-end personal commitments', 'pending', NULL, NULL, NULL),
(7, 'annual', '2025-01-15', '2025-01-19', 5.0, 'Planned vacation trip', 'pending', NULL, NULL, NULL),
(20, 'sick', '2024-12-16', '2024-12-16', 1.0, 'Medical appointment', 'approved', 3, '2024-12-15 09:15:00', 'Medical leave approved'),
(11, 'annual', '2024-12-23', '2024-12-24', 2.0, 'Holiday break', 'approved', 2, '2024-12-14 11:20:00', 'Holiday leave approved'),
(19, 'personal', '2025-01-10', '2025-01-12', 3.0, 'Family wedding ceremony', 'pending', NULL, NULL, NULL),
(12, 'annual', '2025-02-14', '2025-02-16', 3.0, 'Valentine weekend getaway', 'pending', NULL, NULL, NULL),
(15, 'sick', '2024-12-12', '2024-12-13', 2.0, 'Food poisoning', 'approved', 9, '2024-12-11 18:30:00', 'Take care and rest well'),
(8, 'annual', '2025-01-20', '2025-01-31', 10.0, 'Annual vacation and travel', 'pending', NULL, NULL, NULL);

-- Sample External Duty Requests
INSERT INTO wp_wfp_external_duty (employee_id, purpose, location, start_date, end_date, start_time, end_time, description, status, approved_by, approved_at, comments) VALUES
(5, 'Client Meeting', 'TechCorp Solutions Office, Downtown', '2024-12-23', '2024-12-23', '10:00:00', '15:00:00', 'Project review meeting and requirement discussion for e-commerce platform', 'approved', 3, '2024-12-20 16:30:00', 'Important client meeting approved'),
(8, 'Marketing Conference', 'Convention Center, Business District', '2025-01-15', '2025-01-17', '09:00:00', '17:00:00', 'Digital Marketing Summit 2025 - attending workshops and networking sessions', 'approved', 1, '2024-12-18 10:45:00', 'Professional development approved'),
(14, 'Vendor Meeting', 'AdTech Solutions, City Center', '2024-12-26', '2024-12-26', '14:00:00', '17:00:00', 'Discussion with advertising vendor for upcoming campaigns', 'pending', NULL, NULL, NULL),
(10, 'Cloud Training', 'AWS Training Center', '2025-01-08', '2025-01-10', '09:00:00', '17:00:00', 'AWS Cloud Architecture certification training program', 'approved', 3, '2024-12-19 14:15:00', 'Training will benefit current projects'),
(2, 'HR Conference', 'Hotel Grand Plaza', '2025-02-05', '2025-02-07', '08:30:00', '18:00:00', 'Annual HR Leaders Conference - latest trends in human resource management', 'pending', NULL, NULL, NULL),
(13, 'Design Workshop', 'Creative Hub, Art District', '2025-01-12', '2025-01-12', '10:00:00', '16:00:00', 'UX/UI Design Workshop - Advanced Prototyping Techniques', 'approved', 3, '2024-12-21 09:30:00', 'Will enhance design skills'),
(9, 'Sales Training', 'Sales Academy, Business Park', '2025-01-22', '2025-01-24', '09:00:00', '17:00:00', 'Advanced Sales Techniques and CRM Training', 'pending', NULL, NULL, NULL),
(18, 'Support Summit', 'Customer Care Center', '2025-02-12', '2025-02-14', '09:30:00', '17:30:00', 'Customer Support Excellence Summit - best practices and tools', 'pending', NULL, NULL, NULL);

-- Sample Notifications
INSERT INTO wp_wfp_notifications (user_id, title, message, type, action_url, is_read) VALUES
(3, 'New Leave Request', 'John Smith has submitted a leave request for Annual Leave', 'warning', '/admin.php?page=wfp-leave-management', 0),
(3, 'External Duty Request', 'Sarah Johnson requests external duty for client meeting', 'info', '/admin.php?page=wfp-external-duty', 0),
(5, 'Task Assignment', 'You have been assigned a new task: Shopping Cart & Checkout', 'info', '/admin.php?page=wfp-projects', 1),
(6, 'Project Update', 'Website Redesign Project status updated to In Progress', 'success', '/admin.php?page=wfp-projects', 1),
(14, 'Leave Approved', 'Your sick leave request has been approved', 'success', '/admin.php?page=wfp-leave-management', 1),
(8, 'External Duty Approved', 'Your marketing conference external duty has been approved', 'success', '/admin.php?page=wfp-external-duty', 1),
(1, 'Weekly Report', 'Weekly productivity report is ready for review', 'info', '/admin.php?page=wfp-reports', 0),
(2, 'New Employee', 'New employee onboarding scheduled for next week', 'info', '/admin.php?page=wfp-employees', 0);

-- Sample Employee Settings
INSERT INTO wp_wfp_employee_settings (employee_id, setting_key, setting_value) VALUES
(5, 'notification_email', '1'),
(5, 'notification_browser', '1'),
(5, 'timezone', 'Asia/Kolkata'),
(6, 'notification_email', '1'),
(6, 'working_hours_start', '09:00'),
(6, 'working_hours_end', '18:00'),
(7, 'remote_work_enabled', '1'),
(7, 'notification_email', '1'),
(13, 'timezone', 'Asia/Kolkata'),
(13, 'notification_browser', '1'),
(14, 'remote_work_enabled', '1'),
(14, 'notification_email', '1');
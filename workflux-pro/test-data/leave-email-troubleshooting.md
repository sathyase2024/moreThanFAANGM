# WorkFlux Pro Leave & Email Troubleshooting Guide

## 🚨 Issues Fixed

### **Problem 1:** Leave submission not working
### **Problem 2:** Email notifications not being sent for leave requests and approvals

---

## ✅ **Solutions Implemented**

### **1. Enhanced Leave Validation System**

**Fixed Issues:**
- ❌ Poor error messages
- ❌ Insufficient validation
- ❌ Date validation problems
- ❌ No balance checking

**Solutions Applied:**
- ✅ **Comprehensive validation** with detailed error messages
- ✅ **Date range validation** (past dates, invalid ranges)
- ✅ **Leave balance checking** with specific balance information
- ✅ **Overlap detection** for existing requests
- ✅ **Duration limits** (max 365 days)

### **2. Complete Email Notification System**

**Created:** `/includes/class-email-notifications.php`

**Features Implemented:**
- ✅ **Professional HTML email templates**
- ✅ **Leave request notifications** to approvers
- ✅ **Approval/rejection notifications** to employees
- ✅ **Role-based approver detection**
- ✅ **Hierarchical approval system**
- ✅ **Email logging and error handling**

### **3. Improved Error Handling**

**AJAX Response Enhancement:**
```php
// Before: Simple true/false
if ($result) { /* success */ } else { /* failed */ }

// After: Detailed error handling
if (is_array($result) && isset($result['error'])) {
    // Handle specific validation errors
} elseif ($result) {
    // Success with request ID
} else {
    // Generic failure with logging
}
```

---

## 📧 **Email Notification System**

### **Email Types Implemented:**

1. **Leave Request Notification (to Approvers):**
   - Sent when employee submits leave request
   - Includes all request details
   - Direct link to approval interface
   - Professional HTML template

2. **Leave Approval Notification (to Employee):**
   - Sent when leave is approved
   - Confirmation details
   - Approver information
   - Next steps guidance

3. **Leave Rejection Notification (to Employee):**
   - Sent when leave is rejected
   - Rejection reason
   - Option to submit new request
   - Support contact information

### **Email Template Features:**
- 🎨 **Professional Design** - Clean, responsive HTML templates
- 🏢 **Company Branding** - Site name and logo integration
- 📱 **Mobile Friendly** - Responsive design for all devices
- 🔗 **Action Links** - Direct links to relevant pages
- 📋 **Complete Details** - All relevant information included

### **Approver Detection Logic:**
1. **Direct Manager** - Employee's assigned manager
2. **HR Managers** - Users with HR management roles
3. **Managing Heads** - Department/team leaders
4. **Super Admins** - System administrators

---

## 🧪 **Testing Tools Created**

### **1. Leave Test Tool**
**File:** `/test-data/leave-test-tool.php`

**Features:**
- ✅ **Interactive leave submission testing**
- ✅ **Email notification testing**
- ✅ **Leave balance visualization**
- ✅ **Recent requests display**
- ✅ **Validation scenario testing**

**Access:** `yoursite.com/wp-content/plugins/workflux-pro/test-data/leave-test-tool.php`

### **2. Validation Tests:**
- **Past Date Test** - Ensures past dates are rejected
- **Invalid Range Test** - Start date after end date
- **Missing Fields Test** - Required field validation
- **Balance Check Test** - Insufficient leave balance
- **Overlap Test** - Existing request conflicts

---

## 🔧 **Technical Implementation Details**

### **Enhanced Leave Management Class:**

```php
// Improved validation with detailed errors
public static function submit_request($data) {
    // Comprehensive validation
    if (empty($data['employee_id']) || empty($data['leave_type']) || 
        empty($data['start_date']) || empty($data['end_date'])) {
        return array('error' => __('Required fields are missing', 'workflux-pro'));
    }
    
    // Date validation
    if (strtotime($data['start_date']) < strtotime('today')) {
        return array('error' => __('Cannot request leave for past dates', 'workflux-pro'));
    }
    
    // Balance validation with specific details
    if (!self::has_sufficient_balance($employee->id, $data['leave_type'], $days_requested)) {
        $balance = self::get_leave_balance($employee->id, $data['leave_type']);
        return array('error' => sprintf(
            __('Insufficient leave balance. Available: %s days, Requested: %s days', 'workflux-pro'), 
            $balance, $days_requested
        ));
    }
    
    // ... additional validation
}
```

### **Email Notification Hooks:**
```php
// Automatic email triggers
do_action('workflux_pro_leave_request_submitted', $request_id, $data);
do_action('workflux_pro_leave_request_approved', $request_id, $approver_id);
do_action('workflux_pro_leave_request_rejected', $request_id, $approver_id);
```

### **AJAX Enhancement:**
```php
// Better error handling in AJAX responses
if (is_array($result) && isset($result['error'])) {
    self::send_response(false, null, $result['error']);
} elseif ($result) {
    self::send_response(true, array('request_id' => $result), 
        __('Leave request submitted successfully', 'workflux-pro'));
} else {
    self::send_response(false, null, 
        __('Failed to submit leave request. Please try again.', 'workflux-pro'));
}
```

---

## 🎯 **Quick Testing Procedure**

### **Step 1: Test Leave Submission**
1. Access the leave test tool
2. Fill out leave request form
3. Submit and check for success message
4. Verify request appears in recent requests table

### **Step 2: Test Email Notifications**
1. Use the email test function in the tool
2. Check if test email is received
3. Submit a leave request and check for approval email
4. Check email logs if emails are not received

### **Step 3: Test Validation**
1. Use the quick validation test buttons
2. Verify each test correctly fails with appropriate error messages
3. Test edge cases like same-day requests, maximum duration, etc.

### **Step 4: Test Approval Process**
1. Login as an approver (HR Manager, Managing Head, Super Admin)
2. Navigate to WorkFlux Pro → Leave Management
3. Approve or reject pending requests
4. Verify employee receives notification email

---

## 🔍 **Troubleshooting Common Issues**

### **Issue: Emails not being sent**

**Check:**
1. **WordPress Mail Function**
   ```php
   wp_mail('test@example.com', 'Test', 'Test message')
   ```

2. **SMTP Configuration**
   - Install SMTP plugin if needed
   - Configure with proper SMTP settings

3. **Email Logs**
   - Enable WordPress debugging
   - Check error logs for email failures

### **Issue: Leave requests failing**

**Check:**
1. **User Permissions**
   ```php
   WorkFluxPro_Roles::user_can('wfp_submit_leave_requests')
   ```

2. **Employee Record**
   ```php
   WorkFluxPro_User_Management::get_employee_by_user_id($user_id)
   ```

3. **Database Tables**
   - Verify `wp_wfp_leave_requests` table exists
   - Check table structure and permissions

### **Issue: Validation errors not showing**

**Check:**
1. **AJAX Nonce**
   - Verify nonce is being passed correctly
   - Check nonce validation in AJAX handler

2. **Error Response Format**
   - Ensure JavaScript handles error responses
   - Check browser console for errors

---

## 📋 **Email Configuration Checklist**

### **WordPress Mail Settings:**
- [ ] Admin email address configured
- [ ] Site name properly set
- [ ] SMTP plugin installed (if needed)
- [ ] Email sending tested with `wp_mail()`

### **WorkFlux Pro Email Settings:**
- [ ] Email notifications class loaded
- [ ] Action hooks properly registered
- [ ] Email templates rendering correctly
- [ ] From address and name configured

### **Security & Permissions:**
- [ ] Users have proper WorkFlux roles
- [ ] Approval permissions configured
- [ ] Email logging enabled for debugging
- [ ] Error handling implemented

---

## 📞 **Support Information**

**If you're still experiencing issues:**

1. **Enable Debug Mode:**
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. **Check Error Logs:**
   - `/wp-content/debug.log`
   - Server error logs
   - Email service logs

3. **Test Tools:**
   - Use the leave test tool for systematic testing
   - Check AJAX debug tool for general functionality
   - Run activation check for overall system health

4. **Contact Support:**
   - **Developer:** Nagarajarao C R
   - **Company:** Sri Hayavadhana Info-Tech
   - **Provide:** Error logs, specific error messages, and steps to reproduce

---

## ✅ **Status: RESOLVED**

**Leave Submission:** ✅ Fixed with comprehensive validation  
**Email Notifications:** ✅ Implemented with professional templates  
**Error Handling:** ✅ Enhanced with detailed messages  
**Testing Tools:** ✅ Created for ongoing verification  

**Ready for Production Use! 🚀**
# WorkFlux Pro Security Issues Troubleshooting Guide

## 🔧 Security Check Failed - Solutions

### **Problem:** "Security check failed" when creating projects or adding employees

### **Root Causes & Solutions:**

## 1. **Nonce Verification Issues**

### **Cause:** Missing or incorrect nonce in AJAX requests
**Solution:**
```javascript
// Ensure nonce is properly passed in JavaScript
const requestData = {
    action: 'wfp_create_project',
    nonce: workfluxProAdmin.nonce, // This must be included
    // ... other data
};
```

### **Check:** Verify nonce is being created in admin scripts
**File:** `/includes/class-admin.php` (lines 79-81)
```php
wp_localize_script('workflux-pro-admin', 'workfluxProAdmin', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('workflux_pro_nonce'), // Must match AJAX verification
    // ...
));
```

## 2. **AJAX Hook Registration**

### **Cause:** AJAX actions not properly registered
**Solution:** Ensure all AJAX actions are registered in `class-ajax.php`

**Fixed in:** `/includes/class-ajax.php` (init_hooks method)
```php
private function init_hooks() {
    // Employee management actions
    add_action('wp_ajax_wfp_create_employee', array($this, 'create_employee'));
    add_action('wp_ajax_wfp_update_employee', array($this, 'update_employee'));
    
    // Project management actions
    add_action('wp_ajax_wfp_create_project', array($this, 'create_project'));
    add_action('wp_ajax_wfp_assign_project', array($this, 'assign_project'));
    
    // ... other actions
}
```

## 3. **Permission Verification**

### **Cause:** User doesn't have required permissions
**Check:** Ensure user roles have proper capabilities

**Required Capabilities:**
- `wfp_manage_employees` - For creating/updating employees
- `wfp_manage_projects` - For creating/managing projects
- `wfp_clock_in_out` - For time tracking
- `wfp_track_time` - For project time tracking

## 4. **WordPress User Session**

### **Cause:** User not properly logged in or session expired
**Check:**
```php
if (!is_user_logged_in()) {
    self::send_response(false, null, __('Please log in to continue', 'workflux-pro'));
    wp_die();
}
```

## 🔍 **Quick Diagnostics**

### **Step 1: Check AJAX Registration**
1. Navigate to WordPress Admin → Plugins → Plugin Editor
2. Select "WorkFlux Pro"
3. Open `includes/class-ajax.php`
4. Verify `init_hooks()` method contains your action

### **Step 2: Test Nonce Generation**
1. Open browser developer tools (F12)
2. Go to Console tab
3. Type: `console.log(workfluxProAdmin.nonce)`
4. Should output a valid nonce string

### **Step 3: Check User Permissions**
1. Go to Users → Your Profile
2. Check assigned WorkFlux role
3. Verify role has required capabilities

### **Step 4: Test with Debug Tool**
1. Upload `ajax-debug.php` to `/wp-content/plugins/workflux-pro/test-data/`
2. Access: `yoursite.com/wp-content/plugins/workflux-pro/test-data/ajax-debug.php`
3. Test individual AJAX functions

## 🛠️ **Manual Fixes Applied**

### **Fix 1: Updated AJAX Class** ✅
- Added missing AJAX action registrations
- Improved nonce verification with multiple fallbacks
- Added proper error handling and responses

### **Fix 2: Enhanced Security Verification** ✅
- Check for nonce in multiple $_POST parameters
- Verify user login status
- Improved error messaging

### **Fix 3: Added Missing Methods** ✅
- `create_employee()` - For employee creation
- `update_employee()` - For employee updates
- `start_project_timer()` - For project time tracking
- `stop_project_timer()` - For stopping project timers

## 📝 **Testing Procedure**

### **Test Employee Creation:**
1. Login as Super Admin or HR Manager
2. Navigate to WorkFlux Pro → Employees
3. Click "Add New Employee"
4. Fill required fields
5. Submit form
6. **Expected:** Success message, employee created

### **Test Project Creation:**
1. Login as Super Admin, Managing Head, or Project Admin
2. Navigate to WorkFlux Pro → Projects
3. Click "Add New Project"
4. Fill required fields
5. Submit form
6. **Expected:** Success message, project created

## 🚨 **Common Error Messages & Solutions**

### **Error:** "Security token missing"
**Solution:** Ensure nonce is included in AJAX request data

### **Error:** "Security check failed" 
**Solution:** Verify nonce name matches between generation and verification

### **Error:** "Permission denied"
**Solution:** Check user has required WorkFlux role and capabilities

### **Error:** "Username or email already exists"
**Solution:** Use unique username and email for new employees

### **Error:** "Failed to create employee record"
**Solution:** Check database connectivity and table structure

## 🔐 **Security Best Practices Implemented**

1. **Nonce Verification:** All AJAX requests require valid nonce
2. **User Authentication:** Login status verified for all operations
3. **Role-Based Access:** Permissions checked before each action
4. **Data Sanitization:** All input data properly sanitized
5. **Error Handling:** Secure error messages without system details

## 📞 **Still Having Issues?**

### **Debug Steps:**
1. Enable WordPress debug mode
2. Check error logs in `/wp-content/debug.log`
3. Use browser network tab to inspect AJAX requests
4. Verify database tables exist and are properly structured

### **Contact Information:**
- **Developer:** Nagarajarao C R
- **Company:** Sri Hayavadhana Info-Tech
- **Support:** Available for troubleshooting

## 🎯 **Resolution Status**

✅ **AJAX Hook Registration** - Fixed  
✅ **Nonce Verification** - Enhanced  
✅ **Missing Methods** - Added  
✅ **Permission Checks** - Verified  
✅ **Error Handling** - Improved  
✅ **Debug Tools** - Created  

**Status:** Security issues resolved. Plugin ready for testing.
/**
 * WorkFlux Pro Frontend JavaScript
 * 
 * @package WorkFluxPro
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Main WorkFlux Pro Frontend object
     */
    const WorkFluxProFrontend = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initComponents();
            this.updateTimeDisplays();
            this.startAutoRefresh();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Clock in/out buttons
            $(document).on('click', '#wfp-clock-in-btn, #wfp-clock-in', this.showClockInModal);
            $(document).on('click', '#wfp-clock-out-btn, #wfp-clock-out', this.showClockOutModal);
            
            // Project tracking
            $(document).on('click', '.wfp-start-project', this.handleStartProject);
            $(document).on('click', '.wfp-start-task', this.handleStartTask);
            $(document).on('click', '#wfp-start-project-work', this.handleStartProjectWork);
            
            // Modal controls
            $(document).on('click', '.wfp-modal-close', this.hideModal);
            $(document).on('click', '#wfp-modal-cancel', this.hideModal);
            $(document).on('click', '#wfp-modal-submit', this.handleModalSubmit);
            
            // Form submissions
            $(document).on('submit', '#wfp-leave-request-form', this.handleLeaveRequest);
            $(document).on('submit', '#wfp-external-duty-form', this.handleExternalDutyRequest);
            $(document).on('submit', '#wfp-login-form', this.handleLogin);
            
            // Quick actions
            $(document).on('click', '#wfp-request-leave', this.showLeaveRequestForm);
            $(document).on('click', '#wfp-request-external-duty', this.showExternalDutyForm);
            
            // Navigation active state
            $(document).on('click', '.wfp-nav-link', this.updateNavActiveState);
            
            // Date validation
            $(document).on('change', 'input[name="start_date"], input[name="end_date"]', this.validateDateRange);
        },

        /**
         * Initialize components
         */
        initComponents: function() {
            this.initDatePickers();
            this.initTooltips();
            this.setActiveNavigation();
        },

        /**
         * Initialize date pickers
         */
        initDatePickers: function() {
            if ($.fn.datepicker) {
                $('.wfp-datepicker, input[type="date"]').datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0, // No past dates for requests
                    changeMonth: true,
                    changeYear: true
                });
            }
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('[title]').each(function() {
                const $element = $(this);
                const title = $element.attr('title');
                
                if (title) {
                    $element.removeAttr('title').attr('data-tooltip', title);
                }
            });
        },

        /**
         * Set active navigation
         */
        setActiveNavigation: function() {
            const currentPath = window.location.pathname;
            $('.wfp-nav-link').each(function() {
                if ($(this).attr('href') === currentPath) {
                    $(this).addClass('active');
                }
            });
        },

        /**
         * Update navigation active state
         */
        updateNavActiveState: function() {
            $('.wfp-nav-link').removeClass('active');
            $(this).addClass('active');
        },

        /**
         * Show clock in modal
         */
        showClockInModal: function(e) {
            e.preventDefault();
            
            const modalHtml = `
                <div id="wfp-clock-modal" class="wfp-modal">
                    <div class="wfp-modal-content">
                        <div class="wfp-modal-header">
                            <h3>Clock In</h3>
                            <button type="button" class="wfp-modal-close">&times;</button>
                        </div>
                        <div class="wfp-modal-body">
                            <div class="wfp-form-group">
                                <label for="wfp-location">Location (Optional)</label>
                                <input type="text" id="wfp-location" class="wfp-form-control" placeholder="Enter your current location">
                            </div>
                        </div>
                        <div class="wfp-modal-footer">
                            <button type="button" class="wfp-btn wfp-btn-secondary" id="wfp-modal-cancel">Cancel</button>
                            <button type="button" class="wfp-btn wfp-btn-primary" id="wfp-modal-submit" data-action="clock-in">Clock In</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            $('#wfp-location').focus();
        },

        /**
         * Show clock out modal
         */
        showClockOutModal: function(e) {
            e.preventDefault();
            
            const modalHtml = `
                <div id="wfp-clock-modal" class="wfp-modal">
                    <div class="wfp-modal-content">
                        <div class="wfp-modal-header">
                            <h3>Clock Out</h3>
                            <button type="button" class="wfp-modal-close">&times;</button>
                        </div>
                        <div class="wfp-modal-body">
                            <div class="wfp-form-group">
                                <label for="wfp-description">Work Description</label>
                                <textarea id="wfp-description" class="wfp-form-control" rows="4" placeholder="Describe what you worked on today..."></textarea>
                            </div>
                        </div>
                        <div class="wfp-modal-footer">
                            <button type="button" class="wfp-btn wfp-btn-secondary" id="wfp-modal-cancel">Cancel</button>
                            <button type="button" class="wfp-btn wfp-btn-primary" id="wfp-modal-submit" data-action="clock-out">Clock Out</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            $('#wfp-description').focus();
        },

        /**
         * Handle modal submit
         */
        handleModalSubmit: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const action = $button.data('action');
            
            $button.prop('disabled', true).text(workfluxProFrontend.strings.loading);
            
            switch (action) {
                case 'clock-in':
                    WorkFluxProFrontend.performClockIn();
                    break;
                case 'clock-out':
                    WorkFluxProFrontend.performClockOut();
                    break;
            }
        },

        /**
         * Perform clock in
         */
        performClockIn: function() {
            const location = $('#wfp-location').val();
            
            this.apiRequest('time-tracking/clock-in', {
                location: location
            }, function(response) {
                if (response.success) {
                    WorkFluxProFrontend.showNotification(response.message, 'success');
                    WorkFluxProFrontend.hideModal();
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    WorkFluxProFrontend.showNotification(response.message, 'error');
                    $('#wfp-modal-submit').prop('disabled', false).text('Clock In');
                }
            });
        },

        /**
         * Perform clock out
         */
        performClockOut: function() {
            const description = $('#wfp-description').val();
            
            this.apiRequest('time-tracking/clock-out', {
                description: description
            }, function(response) {
                if (response.success) {
                    WorkFluxProFrontend.showNotification(response.message, 'success');
                    WorkFluxProFrontend.hideModal();
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    WorkFluxProFrontend.showNotification(response.message, 'error');
                    $('#wfp-modal-submit').prop('disabled', false).text('Clock Out');
                }
            });
        },

        /**
         * Handle start project
         */
        handleStartProject: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const projectId = $button.data('project-id');
            
            $button.prop('disabled', true).text(workfluxProFrontend.strings.loading);
            
            WorkFluxProFrontend.apiRequest('projects/start', {
                project_id: projectId
            }, function(response) {
                if (response.success) {
                    WorkFluxProFrontend.showNotification(response.message, 'success');
                    $button.text('Stop Working').removeClass('wfp-start-project').addClass('wfp-stop-project').data('tracking-id', response.data.id);
                } else {
                    WorkFluxProFrontend.showNotification(response.message, 'error');
                }
                $button.prop('disabled', false);
            });
        },

        /**
         * Handle start task
         */
        handleStartTask: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const projectId = $button.data('project-id');
            const taskId = $button.data('task-id');
            
            $button.prop('disabled', true).text(workfluxProFrontend.strings.loading);
            
            WorkFluxProFrontend.apiRequest('projects/start', {
                project_id: projectId,
                task_id: taskId
            }, function(response) {
                if (response.success) {
                    WorkFluxProFrontend.showNotification(response.message, 'success');
                    $button.text('Stop Task').removeClass('wfp-start-task').addClass('wfp-stop-task').data('tracking-id', response.data.id);
                } else {
                    WorkFluxProFrontend.showNotification(response.message, 'error');
                }
                $button.prop('disabled', false);
            });
        },

        /**
         * Handle start project work from selector
         */
        handleStartProjectWork: function(e) {
            e.preventDefault();
            
            const projectId = $('#wfp-project-select').val();
            
            if (!projectId) {
                WorkFluxProFrontend.showNotification('Please select a project', 'error');
                return;
            }
            
            const $button = $(this);
            $button.prop('disabled', true).text(workfluxProFrontend.strings.loading);
            
            WorkFluxProFrontend.apiRequest('projects/start', {
                project_id: projectId
            }, function(response) {
                if (response.success) {
                    WorkFluxProFrontend.showNotification(response.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    WorkFluxProFrontend.showNotification(response.message, 'error');
                    $button.prop('disabled', false).text(workfluxProFrontend.strings.startProject);
                }
            });
        },

        /**
         * Handle leave request form
         */
        handleLeaveRequest: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const formData = $form.serialize();
            
            const $submitBtn = $form.find('button[type="submit"]');
            $submitBtn.prop('disabled', true).text(workfluxProFrontend.strings.loading);
            
            WorkFluxProFrontend.apiRequest('leaves/submit', $form.serializeObject(), function(response) {
                if (response.success) {
                    WorkFluxProFrontend.showNotification(response.message, 'success');
                    $form[0].reset();
                    if ($form.closest('.wfp-modal').length) {
                        WorkFluxProFrontend.hideModal();
                    }
                } else {
                    WorkFluxProFrontend.showNotification(response.message, 'error');
                }
                $submitBtn.prop('disabled', false).text('Submit Request');
            });
        },

        /**
         * Handle external duty request form
         */
        handleExternalDutyRequest: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            
            const $submitBtn = $form.find('button[type="submit"]');
            $submitBtn.prop('disabled', true).text(workfluxProFrontend.strings.loading);
            
            WorkFluxProFrontend.apiRequest('external-duty/submit', $form.serializeObject(), function(response) {
                if (response.success) {
                    WorkFluxProFrontend.showNotification(response.message, 'success');
                    $form[0].reset();
                    if ($form.closest('.wfp-modal').length) {
                        WorkFluxProFrontend.hideModal();
                    }
                } else {
                    WorkFluxProFrontend.showNotification(response.message, 'error');
                }
                $submitBtn.prop('disabled', false).text('Submit Request');
            });
        },

        /**
         * Handle login form
         */
        handleLogin: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const formData = $form.serialize();
            
            const $submitBtn = $form.find('button[type="submit"]');
            $submitBtn.prop('disabled', true).text(workfluxProFrontend.strings.loading);
            
            $.post(workfluxProFrontend.ajaxUrl, {
                action: 'wp_ajax_login',
                ...Object.fromEntries(new URLSearchParams(formData))
            }, function(response) {
                if (response.success) {
                    WorkFluxProFrontend.showNotification('Login successful! Redirecting...', 'success');
                    const redirectUrl = $form.find('input[name="redirect_to"]').val() || window.location.href;
                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 1000);
                } else {
                    WorkFluxProFrontend.showNotification(response.data || 'Login failed', 'error');
                    $submitBtn.prop('disabled', false).text('Log In');
                }
            }).fail(function() {
                WorkFluxProFrontend.showNotification('Login failed. Please try again.', 'error');
                $submitBtn.prop('disabled', false).text('Log In');
            });
        },

        /**
         * Show leave request form
         */
        showLeaveRequestForm: function(e) {
            e.preventDefault();
            
            const modalHtml = `
                <div class="wfp-modal">
                    <div class="wfp-modal-content">
                        <div class="wfp-modal-header">
                            <h3>Submit Leave Request</h3>
                            <button type="button" class="wfp-modal-close">&times;</button>
                        </div>
                        <div class="wfp-modal-body">
                            <form id="wfp-leave-request-form">
                                <div class="wfp-form-group">
                                    <label for="leave_type">Leave Type *</label>
                                    <select name="leave_type" id="leave_type" class="wfp-form-control" required>
                                        <option value="">Select Leave Type</option>
                                        <option value="annual">Annual Leave</option>
                                        <option value="sick">Sick Leave</option>
                                        <option value="personal">Personal Leave</option>
                                        <option value="maternity">Maternity Leave</option>
                                        <option value="paternity">Paternity Leave</option>
                                        <option value="emergency">Emergency Leave</option>
                                    </select>
                                </div>
                                <div class="wfp-form-row">
                                    <div class="wfp-form-group">
                                        <label for="start_date">Start Date *</label>
                                        <input type="date" name="start_date" id="start_date" class="wfp-form-control" required>
                                    </div>
                                    <div class="wfp-form-group">
                                        <label for="end_date">End Date *</label>
                                        <input type="date" name="end_date" id="end_date" class="wfp-form-control" required>
                                    </div>
                                </div>
                                <div class="wfp-form-group">
                                    <label for="reason">Reason</label>
                                    <textarea name="reason" id="reason" class="wfp-form-control" rows="4" placeholder="Please provide a reason for your leave request..."></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="wfp-modal-footer">
                            <button type="button" class="wfp-btn wfp-btn-secondary" id="wfp-modal-cancel">Cancel</button>
                            <button type="submit" form="wfp-leave-request-form" class="wfp-btn wfp-btn-primary">Submit Request</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            WorkFluxProFrontend.initDatePickers();
        },

        /**
         * Show external duty form
         */
        showExternalDutyForm: function(e) {
            e.preventDefault();
            
            const modalHtml = `
                <div class="wfp-modal">
                    <div class="wfp-modal-content">
                        <div class="wfp-modal-header">
                            <h3>Submit External Duty Request</h3>
                            <button type="button" class="wfp-modal-close">&times;</button>
                        </div>
                        <div class="wfp-modal-body">
                            <form id="wfp-external-duty-form">
                                <div class="wfp-form-group">
                                    <label for="purpose">Purpose *</label>
                                    <input type="text" name="purpose" id="purpose" class="wfp-form-control" required placeholder="Meeting, Training, Site Visit, etc.">
                                </div>
                                <div class="wfp-form-group">
                                    <label for="location">Location *</label>
                                    <input type="text" name="location" id="location" class="wfp-form-control" required placeholder="Enter the location you'll be visiting">
                                </div>
                                <div class="wfp-form-row">
                                    <div class="wfp-form-group">
                                        <label for="start_date">Start Date *</label>
                                        <input type="date" name="start_date" id="start_date" class="wfp-form-control" required>
                                    </div>
                                    <div class="wfp-form-group">
                                        <label for="end_date">End Date *</label>
                                        <input type="date" name="end_date" id="end_date" class="wfp-form-control" required>
                                    </div>
                                </div>
                                <div class="wfp-form-row">
                                    <div class="wfp-form-group">
                                        <label for="start_time">Start Time</label>
                                        <input type="time" name="start_time" id="start_time" class="wfp-form-control">
                                    </div>
                                    <div class="wfp-form-group">
                                        <label for="end_time">End Time</label>
                                        <input type="time" name="end_time" id="end_time" class="wfp-form-control">
                                    </div>
                                </div>
                                <div class="wfp-form-group">
                                    <label for="description">Description</label>
                                    <textarea name="description" id="description" class="wfp-form-control" rows="4" placeholder="Additional details about your external duty..."></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="wfp-modal-footer">
                            <button type="button" class="wfp-btn wfp-btn-secondary" id="wfp-modal-cancel">Cancel</button>
                            <button type="submit" form="wfp-external-duty-form" class="wfp-btn wfp-btn-primary">Submit Request</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            WorkFluxProFrontend.initDatePickers();
        },

        /**
         * Hide modal
         */
        hideModal: function(e) {
            if (e && e.target !== e.currentTarget && !$(e.target).hasClass('wfp-modal-close') && $(e.target).attr('id') !== 'wfp-modal-cancel') {
                return;
            }
            
            $('.wfp-modal').fadeOut(300, function() {
                $(this).remove();
            });
        },

        /**
         * Validate date range
         */
        validateDateRange: function() {
            const $form = $(this).closest('form');
            const startDate = $form.find('input[name="start_date"]').val();
            const endDate = $form.find('input[name="end_date"]').val();
            
            if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                WorkFluxProFrontend.showNotification('End date must be after start date', 'error');
                $(this).val('');
            }
        },

        /**
         * Update time displays
         */
        updateTimeDisplays: function() {
            // Update any dynamic time displays
            $('.wfp-current-time').each(function() {
                $(this).text(new Date().toLocaleTimeString());
            });
            
            // Update duration displays
            $('.wfp-duration').each(function() {
                const startTime = $(this).data('start-time');
                if (startTime) {
                    const duration = WorkFluxProFrontend.calculateDuration(startTime);
                    $(this).text(duration);
                }
            });
        },

        /**
         * Calculate duration
         */
        calculateDuration: function(startTime) {
            const start = new Date(startTime);
            const now = new Date();
            const diff = now - start;
            
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            
            return hours + 'h ' + minutes + 'm';
        },

        /**
         * Start auto refresh
         */
        startAutoRefresh: function() {
            // Refresh time displays every minute
            setInterval(() => {
                this.updateTimeDisplays();
            }, 60000);
            
            // Refresh dashboard data every 5 minutes
            setInterval(() => {
                this.refreshDashboardData();
            }, 300000);
        },

        /**
         * Refresh dashboard data
         */
        refreshDashboardData: function() {
            if ($('.wfp-dashboard-widgets').length) {
                this.apiRequest('dashboard/data', {}, function(response) {
                    if (response.success) {
                        WorkFluxProFrontend.updateDashboardWidgets(response.data);
                    }
                });
            }
        },

        /**
         * Update dashboard widgets
         */
        updateDashboardWidgets: function(data) {
            // Update clock status
            if (data.clock_status) {
                const isActive = data.clock_status.is_clocked_in;
                $('.wfp-status-indicator').removeClass('active inactive').addClass(isActive ? 'active' : 'inactive');
            }
            
            // Update hours
            if (data.today_hours !== undefined) {
                $('.wfp-hours-number').text(parseFloat(data.today_hours).toFixed(2));
            }
            
            // Update project count
            if (data.assigned_projects) {
                $('.wfp-count-number').text(data.assigned_projects.length);
            }
        },

        /**
         * API request helper
         */
        apiRequest: function(endpoint, data, callback) {
            const url = workfluxProFrontend.ajaxUrl.replace('admin-ajax.php', 'wp-json/workflux-pro/v1/' + endpoint);
            
            $.ajax({
                url: url,
                type: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', workfluxProFrontend.nonce);
                }
            })
            .done(callback)
            .fail(function(xhr) {
                let message = workfluxProFrontend.strings.error;
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                WorkFluxProFrontend.showNotification(message, 'error');
            });
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            // Remove existing notifications
            $('.wfp-notification').remove();
            
            const $notification = $(`
                <div class="wfp-notification wfp-notification-${type}">
                    <div class="wfp-notification-content">
                        <span class="wfp-notification-message">${message}</span>
                        <button type="button" class="wfp-notification-close">&times;</button>
                    </div>
                </div>
            `);
            
            $('body').append($notification);
            
            // Show notification
            setTimeout(() => {
                $notification.addClass('wfp-notification-show');
            }, 100);
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                $notification.removeClass('wfp-notification-show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            }, 5000);
            
            // Manual close
            $notification.find('.wfp-notification-close').click(function() {
                $notification.removeClass('wfp-notification-show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            });
        }
    };

    /**
     * jQuery serialize object helper
     */
    $.fn.serializeObject = function() {
        const o = {};
        const a = this.serializeArray();
        $.each(a, function() {
            if (o[this.name]) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        WorkFluxProFrontend.init();
    });

})(jQuery);
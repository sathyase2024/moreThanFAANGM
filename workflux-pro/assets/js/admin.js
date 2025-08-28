/**
 * WorkFlux Pro Admin JavaScript
 * 
 * @package WorkFluxPro
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Main WorkFlux Pro Admin object
     */
    const WorkFluxProAdmin = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initDataTables();
            this.initDatePickers();
            this.initCharts();
            this.initModals();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Clock in/out buttons
            $(document).on('click', '#wfp-clock-in', this.handleClockIn);
            $(document).on('click', '#wfp-clock-out', this.handleClockOut);
            
            // Project tracking
            $(document).on('click', '.wfp-start-project', this.handleStartProject);
            $(document).on('click', '.wfp-stop-project', this.handleStopProject);
            
            // Leave management
            $(document).on('click', '.wfp-approve-leave', this.handleApproveLeave);
            $(document).on('click', '.wfp-reject-leave', this.handleRejectLeave);
            $(document).on('click', '#wfp-new-leave-request', this.showLeaveRequestModal);
            
            // External duty
            $(document).on('click', '.wfp-approve-external-duty', this.handleApproveExternalDuty);
            $(document).on('click', '.wfp-reject-external-duty', this.handleRejectExternalDuty);
            $(document).on('click', '#wfp-new-external-duty-request', this.showExternalDutyModal);
            
            // Reports
            $(document).on('submit', '#wfp-generate-report-form', this.handleGenerateReport);
            $(document).on('click', '#wfp-export-csv', this.handleExportCSV);
            
            // Data table search
            $(document).on('input', '#wfp-table-search', this.handleTableSearch);
            
            // Form submissions
            $(document).on('submit', '.wfp-ajax-form', this.handleAjaxForm);
            
            // Modal controls
            $(document).on('click', '.wfp-modal-close, .wfp-modal-backdrop', this.hideModal);
            $(document).on('click', '#wfp-modal-cancel', this.hideModal);
        },

        /**
         * Initialize data tables
         */
        initDataTables: function() {
            $('.wfp-data-table').each(function() {
                const $table = $(this);
                
                // Add sorting capability
                $table.find('th[data-column]').addClass('sortable').click(function() {
                    const column = $(this).data('column');
                    WorkFluxProAdmin.sortTable($table, column);
                });
                
                // Add pagination if needed
                if ($table.data('pagination')) {
                    WorkFluxProAdmin.addPagination($table);
                }
            });
        },

        /**
         * Initialize date pickers
         */
        initDatePickers: function() {
            if ($.fn.datepicker) {
                $('.wfp-datepicker, input[type="date"]').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }
        },

        /**
         * Initialize charts
         */
        initCharts: function() {
            if (typeof Chart !== 'undefined') {
                this.initAttendanceChart();
                this.initProjectStatusChart();
            }
        },

        /**
         * Initialize attendance chart
         */
        initAttendanceChart: function() {
            const canvas = document.getElementById('attendanceChart');
            if (!canvas) return;

            // Get last 7 days data
            this.ajaxRequest('get_attendance_chart_data', {}, function(response) {
                if (response.success) {
                    const ctx = canvas.getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: response.data.labels,
                            datasets: [{
                                label: 'Daily Attendance',
                                data: response.data.values,
                                borderColor: '#0073aa',
                                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            });
        },

        /**
         * Initialize project status chart
         */
        initProjectStatusChart: function() {
            const canvas = document.getElementById('projectStatusChart');
            if (!canvas) return;

            this.ajaxRequest('get_project_status_chart_data', {}, function(response) {
                if (response.success) {
                    const ctx = canvas.getContext('2d');
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: response.data.labels,
                            datasets: [{
                                data: response.data.values,
                                backgroundColor: [
                                    '#0073aa',
                                    '#46b450',
                                    '#ffb900',
                                    '#dc3232',
                                    '#00a0d2'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }
            });
        },

        /**
         * Initialize modals
         */
        initModals: function() {
            // Create modal container if it doesn't exist
            if (!$('#wfp-modal-container').length) {
                $('body').append('<div id="wfp-modal-container"></div>');
            }
        },

        /**
         * Handle clock in
         */
        handleClockIn: function(e) {
            e.preventDefault();
            
            const location = prompt(workfluxProAdmin.strings.enterLocation || 'Enter your location (optional):');
            
            WorkFluxProAdmin.ajaxRequest('wfp_clock_in', {
                location: location || ''
            }, function(response) {
                if (response.success) {
                    WorkFluxProAdmin.showNotification(response.message, 'success');
                    location.reload();
                } else {
                    WorkFluxProAdmin.showNotification(response.message, 'error');
                }
            });
        },

        /**
         * Handle clock out
         */
        handleClockOut: function(e) {
            e.preventDefault();
            
            const description = prompt(workfluxProAdmin.strings.enterDescription || 'Enter work description (optional):');
            
            WorkFluxProAdmin.ajaxRequest('wfp_clock_out', {
                description: description || ''
            }, function(response) {
                if (response.success) {
                    WorkFluxProAdmin.showNotification(response.message, 'success');
                    location.reload();
                } else {
                    WorkFluxProAdmin.showNotification(response.message, 'error');
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
            const taskId = $button.data('task-id') || 0;
            
            WorkFluxProAdmin.ajaxRequest('wfp_start_project', {
                project_id: projectId,
                task_id: taskId
            }, function(response) {
                if (response.success) {
                    WorkFluxProAdmin.showNotification(response.message, 'success');
                    $button.text('Stop Project').removeClass('wfp-start-project').addClass('wfp-stop-project');
                } else {
                    WorkFluxProAdmin.showNotification(response.message, 'error');
                }
            });
        },

        /**
         * Handle stop project
         */
        handleStopProject: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const trackingId = $button.data('tracking-id');
            const description = prompt(workfluxProAdmin.strings.enterDescription || 'Enter work description (optional):');
            
            WorkFluxProAdmin.ajaxRequest('wfp_stop_project', {
                tracking_id: trackingId,
                description: description || ''
            }, function(response) {
                if (response.success) {
                    WorkFluxProAdmin.showNotification(response.message, 'success');
                    $button.text('Start Project').removeClass('wfp-stop-project').addClass('wfp-start-project');
                } else {
                    WorkFluxProAdmin.showNotification(response.message, 'error');
                }
            });
        },

        /**
         * Handle approve leave
         */
        handleApproveLeave: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const requestId = $button.closest('tr').data('request-id');
            const comments = prompt('Enter approval comments (optional):');
            
            WorkFluxProAdmin.ajaxRequest('wfp_approve_leave', {
                request_id: requestId,
                action: 'approved',
                comments: comments || ''
            }, function(response) {
                if (response.success) {
                    WorkFluxProAdmin.showNotification(response.message, 'success');
                    $button.closest('tr').fadeOut();
                } else {
                    WorkFluxProAdmin.showNotification(response.message, 'error');
                }
            });
        },

        /**
         * Handle reject leave
         */
        handleRejectLeave: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const requestId = $button.closest('tr').data('request-id');
            const comments = prompt('Enter rejection reason:');
            
            if (!comments) {
                WorkFluxProAdmin.showNotification('Rejection reason is required', 'error');
                return;
            }
            
            WorkFluxProAdmin.ajaxRequest('wfp_approve_leave', {
                request_id: requestId,
                action: 'rejected',
                comments: comments
            }, function(response) {
                if (response.success) {
                    WorkFluxProAdmin.showNotification(response.message, 'success');
                    $button.closest('tr').fadeOut();
                } else {
                    WorkFluxProAdmin.showNotification(response.message, 'error');
                }
            });
        },

        /**
         * Handle approve external duty
         */
        handleApproveExternalDuty: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const requestId = $button.closest('tr').data('request-id');
            const comments = prompt('Enter approval comments (optional):');
            
            WorkFluxProAdmin.ajaxRequest('wfp_approve_external_duty', {
                request_id: requestId,
                action: 'approved',
                comments: comments || ''
            }, function(response) {
                if (response.success) {
                    WorkFluxProAdmin.showNotification(response.message, 'success');
                    $button.closest('tr').fadeOut();
                } else {
                    WorkFluxProAdmin.showNotification(response.message, 'error');
                }
            });
        },

        /**
         * Handle reject external duty
         */
        handleRejectExternalDuty: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const requestId = $button.closest('tr').data('request-id');
            const comments = prompt('Enter rejection reason:');
            
            if (!comments) {
                WorkFluxProAdmin.showNotification('Rejection reason is required', 'error');
                return;
            }
            
            WorkFluxProAdmin.ajaxRequest('wfp_approve_external_duty', {
                request_id: requestId,
                action: 'rejected',
                comments: comments
            }, function(response) {
                if (response.success) {
                    WorkFluxProAdmin.showNotification(response.message, 'success');
                    $button.closest('tr').fadeOut();
                } else {
                    WorkFluxProAdmin.showNotification(response.message, 'error');
                }
            });
        },

        /**
         * Handle generate report
         */
        handleGenerateReport: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const formData = $form.serialize();
            
            $('#wfp-report-results').html('<div class="wfp-loading-spinner"><div class="wfp-spinner"></div><p>' + workfluxProAdmin.strings.loading + '</p></div>').show();
            
            WorkFluxProAdmin.ajaxRequest('wfp_get_reports', formData, function(response) {
                if (response.success) {
                    WorkFluxProAdmin.renderReport(response.data);
                } else {
                    $('#wfp-report-results').html('<div class="wfp-error">' + response.message + '</div>');
                }
            });
        },

        /**
         * Handle export CSV
         */
        handleExportCSV: function(e) {
            e.preventDefault();
            
            const $table = $(this).closest('.wfp-table-wrapper').find('.wfp-data-table');
            WorkFluxProAdmin.exportTableToCSV($table, 'workflux-pro-export.csv');
        },

        /**
         * Handle table search
         */
        handleTableSearch: function(e) {
            const searchTerm = $(this).val().toLowerCase();
            const $table = $(this).closest('.wfp-table-wrapper').find('.wfp-data-table tbody');
            
            $table.find('tr').each(function() {
                const $row = $(this);
                const text = $row.text().toLowerCase();
                
                if (text.indexOf(searchTerm) > -1) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        },

        /**
         * Handle AJAX form submission
         */
        handleAjaxForm: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const formData = $form.serialize();
            const action = $form.data('action');
            
            $form.find('button[type="submit"]').prop('disabled', true).text(workfluxProAdmin.strings.loading);
            
            WorkFluxProAdmin.ajaxRequest(action, formData, function(response) {
                if (response.success) {
                    WorkFluxProAdmin.showNotification(response.message, 'success');
                    $form[0].reset();
                } else {
                    WorkFluxProAdmin.showNotification(response.message, 'error');
                }
                
                $form.find('button[type="submit"]').prop('disabled', false).text(workfluxProAdmin.strings.save);
            });
        },

        /**
         * Show leave request modal
         */
        showLeaveRequestModal: function(e) {
            e.preventDefault();
            
            const modalHtml = `
                <div class="wfp-modal">
                    <div class="wfp-modal-content">
                        <div class="wfp-modal-header">
                            <h3>Submit Leave Request</h3>
                            <button type="button" class="wfp-modal-close">&times;</button>
                        </div>
                        <div class="wfp-modal-body">
                            <form id="wfp-leave-request-form" class="wfp-ajax-form" data-action="wfp_submit_leave_request">
                                <div class="wfp-form-group">
                                    <label for="leave_type">Leave Type *</label>
                                    <select name="leave_type" id="leave_type" class="wfp-form-control" required>
                                        <option value="">Select Leave Type</option>
                                        <option value="annual">Annual Leave</option>
                                        <option value="sick">Sick Leave</option>
                                        <option value="personal">Personal Leave</option>
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
                                    <textarea name="reason" id="reason" class="wfp-form-control" rows="4"></textarea>
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
            
            $('#wfp-modal-container').html(modalHtml);
            WorkFluxProAdmin.initDatePickers();
        },

        /**
         * Show external duty modal
         */
        showExternalDutyModal: function(e) {
            e.preventDefault();
            
            const modalHtml = `
                <div class="wfp-modal">
                    <div class="wfp-modal-content">
                        <div class="wfp-modal-header">
                            <h3>Submit External Duty Request</h3>
                            <button type="button" class="wfp-modal-close">&times;</button>
                        </div>
                        <div class="wfp-modal-body">
                            <form id="wfp-external-duty-form" class="wfp-ajax-form" data-action="wfp_submit_external_duty">
                                <div class="wfp-form-group">
                                    <label for="purpose">Purpose *</label>
                                    <input type="text" name="purpose" id="purpose" class="wfp-form-control" required>
                                </div>
                                <div class="wfp-form-group">
                                    <label for="location">Location *</label>
                                    <input type="text" name="location" id="location" class="wfp-form-control" required>
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
                                    <textarea name="description" id="description" class="wfp-form-control" rows="4"></textarea>
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
            
            $('#wfp-modal-container').html(modalHtml);
            WorkFluxProAdmin.initDatePickers();
        },

        /**
         * Hide modal
         */
        hideModal: function(e) {
            if (e.target === e.currentTarget || $(e.target).hasClass('wfp-modal-close') || $(e.target).attr('id') === 'wfp-modal-cancel') {
                $('#wfp-modal-container').empty();
            }
        },

        /**
         * Render report
         */
        renderReport: function(data) {
            let html = '<div class="wfp-report-container">';
            html += '<div class="wfp-report-header">';
            html += '<h3>' + data.title + '</h3>';
            html += '<p>From: ' + data.date_range.from + ' To: ' + data.date_range.to + '</p>';
            html += '</div>';
            
            if (data.data && data.data.length > 0) {
                html += '<div class="wfp-report-data">';
                html += this.renderReportTable(data.data);
                html += '</div>';
            }
            
            if (data.summary) {
                html += '<div class="wfp-report-summary">';
                html += '<h4>Summary</h4>';
                html += this.renderSummary(data.summary);
                html += '</div>';
            }
            
            html += '</div>';
            
            $('#wfp-report-results').html(html);
        },

        /**
         * Render report table
         */
        renderReportTable: function(data) {
            if (!data || data.length === 0) return '<p>No data found.</p>';
            
            const firstRow = data[0];
            const columns = Object.keys(firstRow);
            
            let html = '<table class="wfp-data-table">';
            html += '<thead><tr>';
            
            columns.forEach(column => {
                html += '<th>' + this.formatColumnName(column) + '</th>';
            });
            
            html += '</tr></thead><tbody>';
            
            data.forEach(row => {
                html += '<tr>';
                columns.forEach(column => {
                    html += '<td>' + (row[column] || '') + '</td>';
                });
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            
            return html;
        },

        /**
         * Render summary
         */
        renderSummary: function(summary) {
            let html = '<div class="wfp-summary-grid">';
            
            Object.keys(summary).forEach(key => {
                html += '<div class="wfp-summary-item">';
                html += '<span class="wfp-summary-label">' + this.formatColumnName(key) + '</span>';
                html += '<span class="wfp-summary-value">' + summary[key] + '</span>';
                html += '</div>';
            });
            
            html += '</div>';
            
            return html;
        },

        /**
         * Format column name
         */
        formatColumnName: function(name) {
            return name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },

        /**
         * Sort table
         */
        sortTable: function($table, column) {
            const $tbody = $table.find('tbody');
            const rows = $tbody.find('tr').toArray();
            const columnIndex = $table.find('th[data-column="' + column + '"]').index();
            
            rows.sort((a, b) => {
                const aVal = $(a).find('td').eq(columnIndex).text().trim();
                const bVal = $(b).find('td').eq(columnIndex).text().trim();
                
                if ($.isNumeric(aVal) && $.isNumeric(bVal)) {
                    return parseFloat(aVal) - parseFloat(bVal);
                } else {
                    return aVal.localeCompare(bVal);
                }
            });
            
            $tbody.empty().append(rows);
        },

        /**
         * Export table to CSV
         */
        exportTableToCSV: function($table, filename) {
            const csv = [];
            const rows = $table.find('tr');
            
            rows.each(function() {
                const row = [];
                $(this).find('th, td').not('.wfp-actions-column').each(function() {
                    row.push('"' + $(this).text().replace(/"/g, '""') + '"');
                });
                csv.push(row.join(','));
            });
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        },

        /**
         * AJAX request helper
         */
        ajaxRequest: function(action, data, callback) {
            const requestData = {
                action: action,
                nonce: workfluxProAdmin.nonce
            };
            
            if (typeof data === 'string') {
                requestData.data = data;
            } else {
                Object.assign(requestData, data);
            }
            
            $.post(workfluxProAdmin.ajaxUrl, requestData)
                .done(callback)
                .fail(function() {
                    WorkFluxProAdmin.showNotification(workfluxProAdmin.strings.error, 'error');
                });
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            const $notification = $('<div class="wfp-notification wfp-notification-' + type + '">' + message + '</div>');
            
            $('body').append($notification);
            
            setTimeout(() => {
                $notification.addClass('wfp-notification-show');
            }, 100);
            
            setTimeout(() => {
                $notification.removeClass('wfp-notification-show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            }, 4000);
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        WorkFluxProAdmin.init();
    });

})(jQuery);
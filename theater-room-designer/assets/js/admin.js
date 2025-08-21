/**
 * Theater Room Designer - Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        initializeAdmin();
    });
    
    function initializeAdmin() {
        bindAdminEvents();
        loadAdminStats();
    }
    
    function bindAdminEvents() {
        // Export designs button
        $('#trd-export-designs').on('click', exportAllDesigns);
        
        // Clear cache button
        $('#trd-clear-cache').on('click', clearCache);
        
        // View design buttons
        $(document).on('click', '.trd-view-design', function() {
            const designId = $(this).data('id');
            viewDesign(designId);
        });
        
        // Delete design buttons
        $(document).on('click', '.trd-delete-design', function() {
            const designId = $(this).data('id');
            const $row = $(this).closest('tr');
            deleteDesign(designId, $row);
        });
        
        // Settings form enhancements
        enhanceSettingsForm();
    }
    
    function loadAdminStats() {
        // This would typically load real-time stats via AJAX
        // For now, we'll just add some visual enhancements
        $('.trd-stat-card').each(function(index) {
            $(this).delay(index * 200).animate({
                opacity: 1
            }, 300);
        });
    }
    
    function exportAllDesigns() {
        const $button = $('#trd-export-designs');
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Exporting...');
        
        $.post(ajaxurl, {
            action: 'trd_export_all_designs',
            nonce: $('#trd_admin_nonce').val() || wp.ajax.settings.nonce
        })
        .done(function(response) {
            if (response.success) {
                // Create download link
                const blob = new Blob([JSON.stringify(response.data, null, 2)], { 
                    type: 'application/json' 
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'theater-room-designs-export-' + new Date().toISOString().split('T')[0] + '.json';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                showNotice('Designs exported successfully!', 'success');
            } else {
                showNotice('Error exporting designs: ' + response.data, 'error');
            }
        })
        .fail(function() {
            showNotice('Error exporting designs. Please try again.', 'error');
        })
        .always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    }
    
    function clearCache() {
        const $button = $('#trd-clear-cache');
        const originalText = $button.text();
        
        if (!confirm('Are you sure you want to clear the cache? This will remove all cached 3D models and may temporarily slow down the designer.')) {
            return;
        }
        
        $button.prop('disabled', true).text('Clearing...');
        
        $.post(ajaxurl, {
            action: 'trd_clear_cache',
            nonce: $('#trd_admin_nonce').val() || wp.ajax.settings.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice('Cache cleared successfully!', 'success');
            } else {
                showNotice('Error clearing cache: ' + response.data, 'error');
            }
        })
        .fail(function() {
            showNotice('Error clearing cache. Please try again.', 'error');
        })
        .always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    }
    
    function viewDesign(designId) {
        // Open design in a modal or new window
        const modal = createDesignViewModal();
        
        $.post(ajaxurl, {
            action: 'trd_get_design_details',
            design_id: designId,
            nonce: $('#trd_admin_nonce').val() || wp.ajax.settings.nonce
        })
        .done(function(response) {
            if (response.success) {
                populateDesignModal(modal, response.data);
            } else {
                showNotice('Error loading design: ' + response.data, 'error');
            }
        })
        .fail(function() {
            showNotice('Error loading design. Please try again.', 'error');
        });
    }
    
    function deleteDesign(designId, $row) {
        if (!confirm('Are you sure you want to delete this design? This action cannot be undone.')) {
            return;
        }
        
        $.post(ajaxurl, {
            action: 'trd_delete_design',
            design_id: designId,
            nonce: $('#trd_admin_nonce').val() || wp.ajax.settings.nonce
        })
        .done(function(response) {
            if (response.success) {
                $row.fadeOut(300, function() {
                    $(this).remove();
                });
                showNotice('Design deleted successfully.', 'success');
                updateStatsAfterDelete();
            } else {
                showNotice('Error deleting design: ' + response.data, 'error');
            }
        })
        .fail(function() {
            showNotice('Error deleting design. Please try again.', 'error');
        });
    }
    
    function createDesignViewModal() {
        const modal = $(`
            <div id="trd-design-view-modal" class="trd-modal" style="display: block;">
                <div class="trd-modal-content" style="max-width: 800px;">
                    <div class="trd-modal-header">
                        <h3>Design Details</h3>
                        <button class="trd-modal-close">&times;</button>
                    </div>
                    <div class="trd-modal-body">
                        <div id="trd-design-details">Loading...</div>
                    </div>
                    <div class="trd-modal-footer">
                        <button class="button trd-modal-close">Close</button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        modal.find('.trd-modal-close').on('click', function() {
            modal.remove();
        });
        
        modal.on('click', function(e) {
            if (e.target === this) {
                modal.remove();
            }
        });
        
        return modal;
    }
    
    function populateDesignModal(modal, design) {
        const data = JSON.parse(design.room_data);
        const details = modal.find('#trd-design-details');
        
        const html = `
            <div class="trd-design-overview">
                <h4>Design Information</h4>
                <table class="widefat">
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td>${design.design_name}</td>
                    </tr>
                    <tr>
                        <td><strong>Created:</strong></td>
                        <td>${design.created_at}</td>
                    </tr>
                    <tr>
                        <td><strong>Last Modified:</strong></td>
                        <td>${design.updated_at}</td>
                    </tr>
                </table>
            </div>
            
            <div class="trd-design-specs">
                <h4>Room Specifications</h4>
                <div class="trd-specs-grid">
                    <div class="trd-spec-section">
                        <h5>Room Dimensions</h5>
                        <ul>
                            <li>Width: ${data.room.width} ft</li>
                            <li>Length: ${data.room.length} ft</li>
                            <li>Height: ${data.room.height} ft</li>
                            <li>Floor Area: ${(data.room.width * data.room.length).toFixed(1)} sq ft</li>
                        </ul>
                    </div>
                    
                    <div class="trd-spec-section">
                        <h5>Screen Setup</h5>
                        <ul>
                            <li>Type: ${data.screen.type === 'projector' ? 'Projector Screen' : 'TV/Display'}</li>
                            <li>Size: ${data.screen.size}"</li>
                            <li>Position: ${data.screen.position} wall</li>
                        </ul>
                    </div>
                    
                    <div class="trd-spec-section">
                        <h5>Audio System</h5>
                        <ul>
                            <li>Configuration: ${data.speakers.config}</li>
                            <li>Auto Placement: ${data.speakers.autoPlacement ? 'Yes' : 'No'}</li>
                        </ul>
                    </div>
                    
                    <div class="trd-spec-section">
                        <h5>Seating</h5>
                        <ul>
                            <li>Rows: ${data.seating.rows}</li>
                            <li>Seats per Row: ${data.seating.seatsPerRow}</li>
                            <li>Total Seats: ${data.seating.rows * data.seating.seatsPerRow}</li>
                            <li>Type: ${data.seating.type}</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="trd-design-features">
                <h4>Additional Features</h4>
                <ul>
                    <li>Snack Bar: ${data.features.bar ? 'Yes' : 'No'}</li>
                    <li>Ambient Lighting: ${data.features.lighting ? 'Yes' : 'No'}</li>
                    <li>Carpet: ${data.features.carpet ? 'Yes' : 'No'}</li>
                </ul>
            </div>
        `;
        
        details.html(html);
    }
    
    function enhanceSettingsForm() {
        // Add tooltips and help text
        addSettingsTooltips();
        
        // Add form validation
        addSettingsValidation();
        
        // Add preview functionality
        addSettingsPreview();
    }
    
    function addSettingsTooltips() {
        const tooltips = {
            'default_room_width': 'The default width that appears when users create a new room design',
            'default_room_length': 'The default length that appears when users create a new room design',
            'default_room_height': 'The default ceiling height for new room designs',
            'default_screen_size': 'The default screen size in inches for new designs',
            'max_saved_designs': 'Maximum number of designs each user can save (0 for unlimited)',
            'enable_guest_saving': 'Allow non-logged-in users to save their designs locally'
        };
        
        Object.keys(tooltips).forEach(function(id) {
            const $field = $('#' + id);
            if ($field.length) {
                $field.attr('title', tooltips[id]);
                
                // Add visual tooltip icon
                $field.after(`
                    <span class="trd-tooltip-icon" title="${tooltips[id]}">
                        <span class="dashicons dashicons-info"></span>
                    </span>
                `);
            }
        });
    }
    
    function addSettingsValidation() {
        $('form').on('submit', function(e) {
            let isValid = true;
            const errors = [];
            
            // Validate room dimensions
            const width = parseFloat($('#default_room_width').val());
            const length = parseFloat($('#default_room_length').val());
            const height = parseFloat($('#default_room_height').val());
            
            if (width < 8 || width > 50) {
                errors.push('Room width should be between 8 and 50 feet');
                isValid = false;
            }
            
            if (length < 10 || length > 60) {
                errors.push('Room length should be between 10 and 60 feet');
                isValid = false;
            }
            
            if (height < 7 || height > 15) {
                errors.push('Room height should be between 7 and 15 feet');
                isValid = false;
            }
            
            // Validate screen size
            const screenSize = parseInt($('#default_screen_size').val());
            if (screenSize < 32 || screenSize > 150) {
                errors.push('Screen size should be between 32 and 150 inches');
                isValid = false;
            }
            
            // Validate max saved designs
            const maxDesigns = parseInt($('#max_saved_designs').val());
            if (maxDesigns < 0 || maxDesigns > 100) {
                errors.push('Maximum saved designs should be between 0 and 100');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                showNotice('Please correct the following errors:\n• ' + errors.join('\n• '), 'error');
            }
        });
    }
    
    function addSettingsPreview() {
        // Add live preview for default room dimensions
        $('#default_room_width, #default_room_length, #default_room_height').on('input', function() {
            updateRoomPreview();
        });
    }
    
    function updateRoomPreview() {
        const width = parseFloat($('#default_room_width').val()) || 12;
        const length = parseFloat($('#default_room_length').val()) || 16;
        const height = parseFloat($('#default_room_height').val()) || 9;
        
        const area = (width * length).toFixed(1);
        const volume = (width * length * height).toFixed(1);
        
        let previewHtml = `
            <div class="trd-room-preview">
                <strong>Preview:</strong> ${width} × ${length} × ${height} ft 
                (${area} sq ft floor area, ${volume} cubic ft volume)
            </div>
        `;
        
        // Remove existing preview
        $('.trd-room-preview').remove();
        
        // Add new preview
        $('#default_room_height').closest('td').append(previewHtml);
    }
    
    function updateStatsAfterDelete() {
        // Update the total designs counter
        const $totalDesigns = $('.trd-stat-card h3').first();
        const currentCount = parseInt($totalDesigns.text());
        $totalDesigns.text(currentCount - 1);
    }
    
    function showNotice(message, type = 'info') {
        const noticeClass = type === 'error' ? 'notice-error' : 
                           type === 'success' ? 'notice-success' : 
                           type === 'warning' ? 'notice-warning' : 'notice-info';
        
        const notice = $(`
            <div class="notice ${noticeClass} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);
        
        // Remove existing notices
        $('.notice').remove();
        
        // Add new notice
        $('.wrap h1').after(notice);
        
        // Handle dismiss button
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Auto-dismiss after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Scroll to notice
        $('html, body').animate({
            scrollTop: notice.offset().top - 50
        }, 300);
    }
    
    // Add CSS for admin enhancements
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .trd-tooltip-icon {
                margin-left: 5px;
                color: #666;
                cursor: help;
            }
            
            .trd-room-preview {
                margin-top: 8px;
                padding: 8px;
                background: #f0f0f1;
                border-radius: 4px;
                font-size: 13px;
                color: #646970;
            }
            
            .trd-specs-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-top: 15px;
            }
            
            .trd-spec-section h5 {
                margin: 0 0 10px 0;
                color: #23282d;
                border-bottom: 1px solid #c3c4c7;
                padding-bottom: 5px;
            }
            
            .trd-spec-section ul {
                margin: 0;
                padding-left: 20px;
            }
            
            .trd-spec-section li {
                margin-bottom: 5px;
            }
            
            .trd-design-overview,
            .trd-design-specs,
            .trd-design-features {
                margin-bottom: 20px;
            }
            
            .trd-design-overview h4,
            .trd-design-specs h4,
            .trd-design-features h4 {
                margin: 0 0 15px 0;
                color: #23282d;
            }
            
            .trd-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.7);
                z-index: 100000;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .trd-modal-content {
                background: #fff;
                border-radius: 4px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
                max-width: 600px;
                width: 100%;
                max-height: 90vh;
                overflow-y: auto;
            }
            
            .trd-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 15px 20px;
                border-bottom: 1px solid #c3c4c7;
            }
            
            .trd-modal-header h3 {
                margin: 0;
                color: #23282d;
            }
            
            .trd-modal-close {
                background: none;
                border: none;
                font-size: 20px;
                cursor: pointer;
                color: #666;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .trd-modal-close:hover {
                color: #000;
            }
            
            .trd-modal-body {
                padding: 20px;
            }
            
            .trd-modal-footer {
                padding: 15px 20px;
                border-top: 1px solid #c3c4c7;
                text-align: right;
            }
        `)
        .appendTo('head');
    
})(jQuery);
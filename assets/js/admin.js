/**
 * TD Staff Admin JavaScript
 */

(function($) {
    'use strict';
    
    // Initialize admin functionality when DOM is ready
    $(document).ready(function() {
        initHoursGrid();
        initCalDavTest();
        initExceptionsForm();
        initWeekActions();
        initStaffEditValidation();
        initSkillBank();
        
        // Only style skill tags if they exist on this page
        if ($('.td-tech-skill-tag').length > 0) {
            setTimeout(function() {
                styleSkillTags();
            }, 100);
        }
    });

    /**
     * Initialize the work hours grid functionality
     */
    function initHoursGrid() {
        // Toggle entire day on/off
        $('.day-enabled-toggle').on('change', function() {
            var weekday = $(this).closest('.td-tech-day-section').data('weekday');
            var $shiftsContainer = $('.td-tech-shifts-container[data-weekday="' + weekday + '"]');
            var $addButton = $('.td-tech-add-shift[data-weekday="' + weekday + '"]');
            
            if ($(this).is(':checked')) {
                $shiftsContainer.show();
                $addButton.show();
                
                // If no shifts exist, add a default one
                if ($shiftsContainer.find('.td-tech-shift-row').length === 0) {
                    addShiftRow(weekday, '09:00', '17:00');
                }
            } else {
                $shiftsContainer.hide();
                $addButton.hide();
                // Don't remove shifts when unchecking - just hide them
                // This way they'll be preserved if the user re-checks the day
            }
        });

        // Add shift button
        $('.td-tech-add-shift').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var weekday = $(this).data('weekday');
            addShiftRow(weekday, '09:00', '17:00');
        });
        
        // Initialize current state of all day toggles
        $('.day-enabled-toggle').each(function() {
            var weekday = $(this).closest('.td-tech-day-section').data('weekday');
            var $shiftsContainer = $('.td-tech-shifts-container[data-weekday="' + weekday + '"]');
            var $addButton = $('.td-tech-add-shift[data-weekday="' + weekday + '"]');
            
            if ($(this).is(':checked')) {
                $shiftsContainer.show();
                $addButton.show();
            } else {
                $shiftsContainer.hide();
                $addButton.hide();
            }
        });

        // Remove shift button (delegated event)
        $(document).on('click', '.td-tech-remove-shift', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $shiftRow = $(this).closest('.td-tech-shift-row');
            var $shiftsContainer = $shiftRow.closest('.td-tech-shifts-container');
            
            $shiftRow.remove();
            
            // Reindex the remaining shifts
            reindexShifts($shiftsContainer);
            
            // If no shifts left, hide the container and uncheck the day
            if ($shiftsContainer.find('.td-tech-shift-row').length === 0) {
                var weekday = $shiftsContainer.data('weekday');
                $('#day_' + weekday + '_enabled').prop('checked', false).trigger('change');
            }
        });
    }

    /**
     * Initialize skill bank interactions
     */
    function initSkillBank() {
        var $skillsInput = $('#skills');
        var $levelSelect = $('#td-tech-skill-level');
        if ($skillsInput.length === 0) return;

        function parseList() {
            return ($skillsInput.val() || '')
                .split(',')
                .map(function(s){ return s.trim(); })
                .filter(Boolean);
        }

        function hasLabel(currentArr, label) {
            var labelLc = label.toLowerCase();
            return currentArr.some(function(item){
                return item.toLowerCase() === labelLc || item.toLowerCase().startsWith(labelLc + ':');
            });
        }

        function updateChipStates() {
            var current = parseList();
            $('.td-tech-skill-chip').each(function(){
                var label = String($(this).data('skill') || '');
                $(this).toggleClass('is-active', hasLabel(current, label));
            });
        }

        function writeList(arr) {
            $skillsInput.val(arr.join(', '));
            // Trigger events so UI/validators notice
            $skillsInput.trigger('input').trigger('change');
            // Subtle highlight feedback
            $skillsInput.addClass('td-tech-pulse');
            setTimeout(function(){ $skillsInput.removeClass('td-tech-pulse'); }, 300);
            updateChipStates();
            updateButtons(arr);
        }

        function updateButtons(arr) {
            var hasAny = (arr && arr.length > 0);
            $('.td-tech-skill-undo').prop('disabled', !hasAny);
            $('.td-tech-skill-clear').prop('disabled', !hasAny);
        }

        // Add skill from chip
        $(document).on('click', '.td-tech-skill-chip', function(e) {
            e.preventDefault();
            var label = String($(this).data('skill') || '');
            var level = ($levelSelect.val() || '').trim();
            var entry = level ? (label + ':' + level) : label;

            var current = parseList();

            // Toggle: if any entry for label exists, remove all of them; else add with current level
            if (hasLabel(current, label)) {
                current = current.filter(function(item){
                    var low = item.toLowerCase();
                    return low !== label.toLowerCase() && !low.startsWith(label.toLowerCase() + ':');
                });
            } else {
                current.push(entry);
            }

            writeList(current);
        });

        // Clear skills input
        $(document).on('click', '.td-tech-skill-clear', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if ($levelSelect.length) { $levelSelect.val(''); }
            writeList([]);
            $skillsInput.focus();
        });

        // Remove last skill entry
        $(document).on('click', '.td-tech-skill-undo', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var current = parseList();
            if (current.length > 0) {
                current.pop();
                writeList(current);
                $skillsInput.focus();
            }
        });

        // Keep chips in sync if user types manually
        $skillsInput.on('input change', function(){
            updateChipStates();
        });

        // Initialize states on load
        updateChipStates();
        updateButtons(parseList());
    }

    /**
     * Add a new shift row
     */
    function addShiftRow(weekday, startTime, endTime) {
        var $shiftsContainer = $('.td-tech-shifts-container[data-weekday="' + weekday + '"]');
        var shiftIndex = $shiftsContainer.find('.td-tech-shift-row').length;
        
        var shiftHtml = '<div class="td-tech-shift-row">' +
            '<input type="time" ' +
                   'name="day_' + weekday + '_shifts[' + shiftIndex + '][start]" ' +
                   'value="' + startTime + '" ' +
                   'required>' +
            '<span class="td-tech-time-separator">' + (td_tech_admin.strings.to || 'to') + '</span>' +
            '<input type="time" ' +
                   'name="day_' + weekday + '_shifts[' + shiftIndex + '][end]" ' +
                   'value="' + endTime + '" ' +
                   'required>' +
            '<button type="button" class="button button-small td-tech-remove-shift">' +
                (td_tech_admin.strings.remove || 'Remove') +
            '</button>' +
        '</div>';
        
        $shiftsContainer.append(shiftHtml);
    }

    /**
     * Reindex shift names after removal
     */
    function reindexShifts($shiftsContainer) {
        var weekday = $shiftsContainer.data('weekday');
        
        $shiftsContainer.find('.td-tech-shift-row').each(function(index) {
            $(this).find('input[type="time"]').each(function() {
                var name = $(this).attr('name');
                var type = name.indexOf('[start]') !== -1 ? 'start' : 'end';
                $(this).attr('name', 'day_' + weekday + '_shifts[' + index + '][' + type + ']');
            });
        });
    }

    /**
     * Initialize CalDAV connection testing
     */
    function initCalDavTest() {
        $('#td-tech-test-caldav').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $result = $('#td-tech-test-result');
            const staffId = $button.data('staff-id');
            
            // Get form values
            const baseUrl = $('#nc_base_url').val();
            const calendarPath = $('#nc_calendar_path').val();
            const username = $('#nc_username').val();
            const appPassword = $('#nc_app_password').val();
            
            // Allow testing even if some fields are missing; server will fallback to stored credentials when possible
            
            // Disable button and show loading
            $button.prop('disabled', true).text(td_tech_admin.strings.testing || 'Testing...');
            $result.hide();
            
            // Make AJAX request using WordPress AJAX
            $.ajax({
                url: window.tdTechAjax ? window.tdTechAjax.ajaxurl : '/wp-admin/admin-ajax.php',
                method: 'POST',
                data: {
                    action: 'td_tech_test_caldav',
                    nonce: $('#td_tech_nonce').val(),
                    staff_id: staffId,
                    base_url: baseUrl,
                    calendar_path: calendarPath,
                    username: username,
                    app_password: appPassword
                },
                timeout: 10000
            })
            .done(function(response) {
                if (response.success) {
                    showTestResult($result, true, response.data.message);
                } else {
                    showTestResult($result, false, response.data.message);
                }
            })
            .fail(function(xhr) {
                let message = td_tech_admin.strings.connection_test_failed || 'Connection test failed.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    message = xhr.responseJSON.data.message;
                }
                showTestResult($result, false, message);
            })
            .always(function() {
                $button.prop('disabled', false).text(td_tech_admin.strings.test_connection || 'Test Connection');
            });
        });
    }

    /**
     * Show CalDAV test result
     */
    function showTestResult($result, success, message) {
        $result
            .removeClass('success error')
            .addClass(success ? 'success' : 'error')
            .text(message)
            .show();
    }

    /**
     * Initialize exceptions form handling
     */
    function initExceptionsForm() {
        // Delete exception using WordPress AJAX
        $('.td-tech-delete-exception').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm(td_tech_admin.strings.delete_exception_confirm || 'Are you sure you want to delete this exception?')) {
                return;
            }
            
            const $button = $(this);
            const staffId = $button.data('staff-id');
            const exceptionId = $button.data('exception-id');
            const ajaxUrl = window.tdTechAjax ? window.tdTechAjax.ajaxurl : '/wp-admin/admin-ajax.php';
            
            // Disable button during request
            $button.prop('disabled', true);
            
            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                data: {
                    action: 'td_tech_delete_exception',
                    nonce: $('#td_tech_nonce').val(),
                    staff_id: staffId,
                    exception_id: exceptionId
                }
            })
            .done(function(response) {
                if (response.success) {
                    $button.closest('tr').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message || td_tech_admin.strings.delete_exception_failed || 'Failed to delete exception.');
                    $button.prop('disabled', false);
                }
            })
            .fail(function(xhr, status, error) {
                alert(td_tech_admin.strings.delete_exception_failed || 'Failed to delete exception.');
                $button.prop('disabled', false);
            });
        });

        // Convert local datetime to UTC for form submission
        $('#td-tech-exception-form').on('submit', function() {
            const startLocal = $('#exception_start').val();
            const endLocal = $('#exception_end').val();
            
            if (startLocal && endLocal) {
                // Convert to UTC (this is a simplified approach)
                // In a real implementation, you'd want to use the staff's timezone
                const startUtc = new Date(startLocal).toISOString().slice(0, 19);
                const endUtc = new Date(endLocal).toISOString().slice(0, 19);
                
                $('#exception_start_utc').val(startUtc);
                $('#exception_end_utc').val(endUtc);
            }
        });
    }

    /**
     * Initialize week copy/paste and template functionality
     */
    function initWeekActions() {
        let copiedWeekData = null;

        // Copy week button
        $('#td-tech-copy-week').on('click', function() {
            copiedWeekData = captureWeekData();
            $('#td-tech-paste-week').prop('disabled', false);
            
            var $button = $(this);
            $button.text(td_tech_admin.strings.week_copied || '✅ Week Copied').addClass('button-primary');
            setTimeout(function() {
                $button.text(td_tech_admin.strings.copy_week || '📋 Copy Week').removeClass('button-primary');
            }, 2000);
        });

        // Paste week button
        $('#td-tech-paste-week').on('click', function() {
            if (copiedWeekData) {
                if (confirm(td_tech_admin.strings.replace_hours_confirm || 'This will replace all current work hours. Continue?')) {
                    applyWeekData(copiedWeekData);
                }
            }
        });

        // Clear week button
        $('#td-tech-clear-week').on('click', function() {
            if (confirm(td_tech_admin.strings.clear_hours_confirm || 'This will clear all work hours. Continue?')) {
                clearAllHours();
            }
        });

        // Apply template button
        $('#td-tech-apply-template').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var template = $('#td-tech-template-select').val();
            if (template) {
                if (confirm(td_tech_admin.strings.replace_template_confirm || 'This will replace current work hours with the selected template. Continue?')) {
                    applyTemplate(template);
                    $('#td-tech-template-select').val('');
                }
            }
        });
    }

    /**
     * Capture current week data
     */
    function captureWeekData() {
        var weekData = {};
        
        for (var weekday = 0; weekday <= 6; weekday++) {
            var $daySection = $('.td-tech-day-section[data-weekday="' + weekday + '"]');
            var isEnabled = $daySection.find('.day-enabled-toggle').is(':checked');
            
            if (isEnabled) {
                var shifts = [];
                $daySection.find('.td-tech-shift-row').each(function() {
                    var startTime = $(this).find('input[type="time"]').eq(0).val();
                    var endTime = $(this).find('input[type="time"]').eq(1).val();
                    
                    if (startTime && endTime) {
                        shifts.push({
                            start: startTime,
                            end: endTime
                        });
                    }
                });
                
                if (shifts.length > 0) {
                    weekData[weekday] = shifts;
                }
            }
        }
        
        return weekData;
    }

    /**
     * Apply week data to the interface
     */
    function applyWeekData(weekData) {
        // First clear all
        clearAllHours();
        
        // Apply the data
        for (var weekday in weekData) {
            var shifts = weekData[weekday];
            if (shifts && shifts.length > 0) {
                // Enable the day
                var $dayToggle = $('#day_' + weekday + '_enabled');
                $dayToggle.prop('checked', true).trigger('change');
                
                // Clear default shift and add the copied ones
                var $shiftsContainer = $('.td-tech-shifts-container[data-weekday="' + weekday + '"]');
                $shiftsContainer.empty();
                
                for (var i = 0; i < shifts.length; i++) {
                    addShiftRow(weekday, shifts[i].start, shifts[i].end);
                }
            }
        }
    }

    /**
     * Clear all work hours
     */
    function clearAllHours() {
        $('.day-enabled-toggle').prop('checked', false).trigger('change');
    }

    /**
     * Apply predefined templates
     */
    function applyTemplate(template) {
        clearAllHours();
        
        var templateData = {};
        
        switch (template) {
            case 'weekdays-9to5':
                for (var i = 1; i <= 5; i++) { // Monday to Friday
                    templateData[i] = [{start: '09:00', end: '17:00'}];
                }
                break;
                
            case 'weekdays-8to6':
                for (var i = 1; i <= 5; i++) { // Monday to Friday
                    templateData[i] = [{start: '08:00', end: '18:00'}];
                }
                break;
                
            case 'full-week-9to5':
                for (var i = 0; i <= 6; i++) { // All days
                    templateData[i] = [{start: '09:00', end: '17:00'}];
                }
                break;
                
            case 'split-shift':
                for (var i = 1; i <= 5; i++) { // Monday to Friday
                    templateData[i] = [
                        {start: '09:00', end: '12:00'},
                        {start: '13:00', end: '17:00'}
                    ];
                }
                break;
        }
        
        applyWeekData(templateData);
    }

    /**
     * Initialize staff edit form validation
     */
    function initStaffEditValidation() {
        var $form = $('form[method="post"]');
        
        // Only initialize if we're on a staff form page
        if ($('#display_name').length === 0) {
            return;
        }
        
        // Real-time validation - only bind if elements exist
        var $emailField = $('#email');
        if ($emailField.length > 0) {
            $emailField.on('blur', function() {
                validateEmail($(this));
            });
        }
        
        var $phoneField = $('#phone');
        if ($phoneField.length > 0) {
            $phoneField.on('blur', function() {
                validatePhone($(this));
            });
        }
        
        var $caldavUrlField = $('#nc_base_url');
        if ($caldavUrlField.length > 0) {
            $caldavUrlField.on('blur', function() {
                validateCalDAVUrl($(this));
            });
        }
        
        // CalDAV credentials interdependency validation
        var caldavFields = $('#nc_base_url, #nc_calendar_path, #nc_username, #nc_app_password');
        if (caldavFields.length > 0) {
            caldavFields.on('blur change', function() {
                validateCalDAVCredentials();
            });
        }
        
        // Form submission validation
        if ($form.length > 0) {
            $form.on('submit', function(e) {
                if (!validateStaffForm()) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    }
    
    /**
     * Validate email field
     */
    function validateEmail($field) {
        const email = $field.val().trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        clearFieldError($field);
        
        if (email && !emailRegex.test(email)) {
            showFieldError($field, td_tech_admin.strings.email_invalid || 'Please enter a valid email address.');
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate phone field
     */
    function validatePhone($field) {
        const phone = $field.val().trim();
        
        clearFieldError($field);
        
        if (phone) {
            // Basic phone validation - at least 7 digits
            const digitCount = phone.replace(/\D/g, '').length;
            if (digitCount < 7) {
                showFieldError($field, td_tech_admin.strings.phone_invalid || 'Please enter a valid phone number.');
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate CalDAV URL
     */
    function validateCalDAVUrl($field) {
        const url = $field.val().trim();
        
        clearFieldError($field);
        
        if (url) {
            try {
                const parsedUrl = new URL(url);
                if (!['http:', 'https:'].includes(parsedUrl.protocol)) {
                    showFieldError($field, td_tech_admin.strings.url_protocol_invalid || 'URL must use HTTP or HTTPS protocol.');
                    return false;
                }
            } catch (e) {
                showFieldError($field, td_tech_admin.strings.url_invalid || 'Please enter a valid URL.');
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate CalDAV credentials completeness
     */
    function validateCalDAVCredentials() {
        var $baseUrlField = $('#nc_base_url');
        var $calendarPathField = $('#nc_calendar_path');
        var $usernameField = $('#nc_username');
        var $passwordField = $('#nc_app_password');
        
        // Check if CalDAV fields exist on this page
        if ($baseUrlField.length === 0) {
            return true;
        }
        
        var baseUrl = $baseUrlField.val();
        var calendarPath = $calendarPathField.val();
        var username = $usernameField.val();
        var password = $passwordField.val();
        
        baseUrl = baseUrl ? baseUrl.trim() : '';
        calendarPath = calendarPath ? calendarPath.trim() : '';
        username = username ? username.trim() : '';
        password = password ? password.trim() : '';
        
        var hasAny = baseUrl || calendarPath || username || password;
        var hasAll = baseUrl && calendarPath && username && password;
        
        var fields = [$baseUrlField, $calendarPathField, $usernameField, $passwordField];
        
        // Clear all CalDAV field errors first
        for (var i = 0; i < fields.length; i++) {
            clearFieldError(fields[i]);
        }
        
        if (hasAny && !hasAll) {
            var errorMsg = td_tech_admin.strings.caldav_fields_incomplete || 'All CalDAV fields are required when any CalDAV field is provided.';
            for (var i = 0; i < fields.length; i++) {
                var fieldVal = fields[i].val();
                if (!fieldVal || !fieldVal.trim()) {
                    showFieldError(fields[i], errorMsg);
                }
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate entire staff form
     */
    function validateStaffForm() {
        var isValid = true;
        
        // Check if we're on a staff form page
        var $displayNameField = $('#display_name');
        var $emailField = $('#email');
        
        if ($displayNameField.length === 0 || $emailField.length === 0) {
            // Not on a staff form page, validation passes
            return true;
        }
        
        // Required fields
        var displayName = $displayNameField.val();
        var email = $emailField.val();
        
        displayName = displayName ? displayName.trim() : '';
        email = email ? email.trim() : '';
        
        clearFieldError($displayNameField);
        clearFieldError($emailField);
        
        if (!displayName) {
            showFieldError($displayNameField, td_tech_admin.strings.display_name_required || 'Display name is required.');
            isValid = false;
        }
        
        if (!email) {
            showFieldError($emailField, td_tech_admin.strings.email_required || 'Email is required.');
            isValid = false;
        } else if (!validateEmail($emailField)) {
            isValid = false;
        }
        
        // Validate phone if provided
        if ($('#phone').length > 0 && !validatePhone($('#phone'))) {
            isValid = false;
        }
        
        // Validate CalDAV URL if provided
        if ($('#nc_base_url').length > 0 && !validateCalDAVUrl($('#nc_base_url'))) {
            isValid = false;
        }
        
        // Validate CalDAV credentials
        if (!validateCalDAVCredentials()) {
            isValid = false;
        }
        
        // Validate weight if field exists
        var $weightField = $('#weight');
        if ($weightField.length > 0) {
            var weightVal = $weightField.val();
            var weight = parseInt(weightVal) || 1;
            if (weight < 1 || weight > 100) {
                showFieldError($weightField, td_tech_admin.strings.weight_range_invalid || 'Weight must be between 1 and 100.');
                isValid = false;
            }
        }
        
        // Validate cooldown if field exists
        var $cooldownField = $('#cooldown_sec');
        if ($cooldownField.length > 0) {
            var cooldownVal = $cooldownField.val();
            var cooldown = parseInt(cooldownVal) || 0;
            if (cooldown < 0) {
                showFieldError($cooldownField, td_tech_admin.strings.cooldown_negative || 'Cooldown must be non-negative.');
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    /**
     * Show field error
     */
    function showFieldError($field, message) {
        $field.addClass('td-tech-error');
        
        // Remove existing error message
        $field.siblings('.td-tech-error-message').remove();
        
        // Add error message
        $field.after('<div class="td-tech-error-message" style="color: #d63638; font-size: 12px; margin-top: 2px;">' + message + '</div>');
    }
    
    /**
     * Clear field error
     */
    function clearFieldError($field) {
        $field.removeClass('td-tech-error');
        $field.siblings('.td-tech-error-message').remove();
    }
    
    /**
     * Style skill tags based on level
     */
    function styleSkillTags() {
        $('.td-tech-skill-tag').each(function() {
            var $tag = $(this);
            var $levelSpan = $tag.find('.skill-level');
            
            if ($levelSpan.length > 0) {
                var level = $levelSpan.text().replace(':', '').toLowerCase();
                
                switch(level) {
                    case 'beginner':
                        $tag.css('background', 'linear-gradient(135deg, #28a745, #1e7e34)');
                        break;
                    case 'intermediate':
                        $tag.css({
                            'background': 'linear-gradient(135deg, #ffc107, #e0a800)',
                            'color': '#333'
                        });
                        break;
                    case 'advanced':
                        $tag.css('background', 'linear-gradient(135deg, #fd7e14, #e8590c)');
                        break;
                    case 'expert':
                        $tag.css('background', 'linear-gradient(135deg, #dc3545, #c82333)');
                        break;
                    case 'master':
                        $tag.css('background', 'linear-gradient(135deg, #6f42c1, #5a2d91)');
                        break;
                }
            }
        });
    }



})(jQuery);

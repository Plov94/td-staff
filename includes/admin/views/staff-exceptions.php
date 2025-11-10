<?php
/**
 * Staff Exceptions Admin View
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current exceptions
global $wpdb;
$exceptions_table = $wpdb->prefix . 'td_staff_exception';

$exceptions = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$exceptions_table} 
         WHERE staff_id = %d 
         ORDER BY start_utc DESC",
        $staff->id
    ),
    ARRAY_A
);

$exception_objects = [];
foreach ($exceptions as $exception_data) {
    $exception_objects[] = new TD_Staff_Exception($exception_data);
}

// Get timezone for local time display
$staff_tz = new DateTimeZone($staff->timezone);
$utc_tz = new DateTimeZone('UTC');
?>

<div class="wrap td-tech-admin-page">
    <h1>
        <?php _e('Exceptions', 'td-staff'); ?>: 
        <?php echo esc_html($staff->display_name ?: ''); ?>
    </h1>
    
    <p>
            <a href="<?php echo admin_url('admin.php?page=td-staff-edit&id=' . $staff->id); ?>">
                ← <?php _e('Back to Edit Staff Member', 'td-staff'); ?>
        </a>
    </p>
    
    <?php settings_errors('td_tech'); ?>
    
    <!-- Add Exception Form -->
    <div class="td-tech-add-exception">
    <h3><?php _e('Add New Exception', 'td-staff'); ?></h3>
        
        <form method="post" action="" id="td-tech-exception-form">
            <?php wp_nonce_field('td_tech_add_exception'); ?>
            <input type="hidden" name="staff_id" value="<?php echo (int) ($staff->id ?: 0); ?>">
            <input type="hidden" id="exception_start_utc" name="start_utc">
            <input type="hidden" id="exception_end_utc" name="end_utc">
            
            <div class="td-tech-form-row">
                <label for="exception_type"><?php _e('Type:', 'td-staff'); ?></label>
                <select id="exception_type" name="type" required>
                    <option value=""><?php _e('Select type...', 'td-staff'); ?></option>
                    <option value="holiday"><?php _e('Holiday', 'td-staff'); ?></option>
                    <option value="sick"><?php _e('Sick Day', 'td-staff'); ?></option>
                    <option value="custom"><?php _e('Custom', 'td-staff'); ?></option>
                </select>
            </div>
            
            <div class="td-tech-form-row">
                <label for="exception_start"><?php _e('Start:', 'td-staff'); ?></label>
                <input type="datetime-local" id="exception_start" required>
                <small><?php printf(__('Time in %s', 'td-staff'), esc_html($staff->timezone ?: 'Europe/Oslo')); ?></small>
            </div>
            
            <div class="td-tech-form-row">
                <label for="exception_end"><?php _e('End:', 'td-staff'); ?></label>
                <input type="datetime-local" id="exception_end" required>
                <small><?php printf(__('Time in %s', 'td-staff'), esc_html($staff->timezone ?: 'Europe/Oslo')); ?></small>
            </div>
            
            <div class="td-tech-form-row">
                <label for="exception_note"><?php _e('Note:', 'td-staff'); ?></label>
                <input type="text" id="exception_note" name="note" placeholder="<?php _e('Optional note...', 'td-staff'); ?>">
            </div>
            
            <div class="td-tech-form-row">
                    <input type="submit" name="td_tech_add_exception" class="button-primary" 
                           value="<?php _e('Add Exception', 'td-staff'); ?>">
            </div>
        </form>
    </div>
    
    <!-- Existing Exceptions -->
    <div class="td-tech-exceptions-list">
    <h3><?php _e('Current Exceptions', 'td-staff'); ?></h3>
        
        <?php if (empty($exception_objects)): ?>
    <p><?php _e('No exceptions found.', 'td-staff'); ?></p>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Type', 'td-staff'); ?></th>
                    <th><?php _e('Start', 'td-staff'); ?></th>
                    <th><?php _e('End', 'td-staff'); ?></th>
                    <th><?php _e('Duration', 'td-staff'); ?></th>
                    <th><?php _e('Note', 'td-staff'); ?></th>
                    <th><?php _e('Actions', 'td-staff'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exception_objects as $exception): ?>
                <?php
                try {
                    $start_utc = $exception->get_start_utc();
                    $end_utc = $exception->get_end_utc();
                    $start_local = $start_utc->setTimezone($staff_tz);
                    $end_local = $end_utc->setTimezone($staff_tz);
                    $duration = $start_utc->diff($end_utc);
                } catch (Exception $e) {
                    continue; // Skip invalid exceptions
                }
                ?>
                <tr>
                    <td><?php echo esc_html($exception->get_type_name() ?: ''); ?></td>
                    <td>
                        <?php echo esc_html($start_local->format('Y-m-d H:i') ?: ''); ?>
                        <br><small><?php echo esc_html($staff->timezone ?: 'Europe/Oslo'); ?></small>
                    </td>
                    <td>
                        <?php echo esc_html($end_local->format('Y-m-d H:i') ?: ''); ?>
                        <br><small><?php echo esc_html($staff->timezone ?: 'Europe/Oslo'); ?></small>
                    </td>
                    <td>
                        <?php
                        if ($duration->days > 0) {
                            printf(_n('%d day', '%d days', $duration->days, 'td-staff'), $duration->days);
                            if ($duration->h > 0 || $duration->i > 0) {
                                echo ', ';
                            }
                        }
                        if ($duration->h > 0) {
                            printf(_n('%d hour', '%d hours', $duration->h, 'td-staff'), $duration->h);
                            if ($duration->i > 0) {
                                echo ', ';
                            }
                        }
                        if ($duration->i > 0) {
                            printf(_n('%d minute', '%d minutes', $duration->i, 'td-staff'), $duration->i);
                        }
                        ?>
                    </td>
                    <td><?php echo esc_html($exception->note ?: '—'); ?></td>
                    <td>
                            <button type="button" class="button button-small td-tech-delete-exception" 
                                    data-staff-id="<?php echo (int) ($staff->id ?: 0); ?>" 
                                    data-exception-id="<?php echo (int) ($exception->id ?: 0); ?>">
                                <?php _e('Delete', 'td-staff'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
    <h3><?php _e('Related Actions', 'td-staff'); ?></h3>
        <p>
                <a href="<?php echo admin_url('admin.php?page=td-staff-hours&id=' . $staff->id); ?>" 
                   class="button"><?php _e('Manage Work Hours', 'td-staff'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=td-staff'); ?>" 
                   class="button"><?php _e('All Staff', 'td-staff'); ?></a>
        </p>
    </div>
    
    <input type="hidden" id="td_tech_nonce" value="<?php echo wp_create_nonce('wp_rest'); ?>">
</div>

<script>
jQuery(document).ready(function($) {
    // Handle local to UTC conversion for exception form
    $('#td-tech-exception-form').on('submit', function() {
        var startLocal = $('#exception_start').val();
        var endLocal = $('#exception_end').val();
        
        if (startLocal && endLocal) {
            // Create dates and convert to UTC
            // Note: This is a simplified approach for the admin interface
            var startDate = new Date(startLocal);
            var endDate = new Date(endLocal);
            
            // Convert to UTC ISO string and remove 'Z' for MySQL format
            var startUtc = startDate.toISOString().slice(0, 19).replace('T', ' ');
            var endUtc = endDate.toISOString().slice(0, 19).replace('T', ' ');
            
            $('#exception_start_utc').val(startUtc);
            $('#exception_end_utc').val(endUtc);
        }
    });
});
</script>

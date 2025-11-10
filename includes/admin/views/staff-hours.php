<?php
/**
 * Staff Hours Admin View
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current hours
global $wpdb;
$hours_table = $wpdb->prefix . 'td_staff_hours';

$current_hours = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT weekday, start_min, end_min FROM {$hours_table} 
         WHERE staff_id = %d ORDER BY weekday, start_min",
        $staff->id
    ),
    ARRAY_A
);

// Group by weekday
$weekly_hours = [];
foreach ($current_hours as $hour) {
    $weekday = (int) $hour['weekday'];
    if (!isset($weekly_hours[$weekday])) {
        $weekly_hours[$weekday] = [];
    }
    $weekly_hours[$weekday][] = [
        'start_min' => (int) $hour['start_min'],
        'end_min' => (int) $hour['end_min'],
    ];
}

// Days ordered Monday-Saturday, then Sunday for better UX
$weekdays = [
    1 => __('Monday', 'td-staff'),
    2 => __('Tuesday', 'td-staff'),
    3 => __('Wednesday', 'td-staff'),
    4 => __('Thursday', 'td-staff'),
    5 => __('Friday', 'td-staff'),
    6 => __('Saturday', 'td-staff'),
    0 => __('Sunday', 'td-staff'),
];
?>

<div class="wrap td-tech-admin-page">
    <h1>
    <?php _e('Work Hours', 'td-staff'); ?>: 
        <?php echo esc_html($staff->display_name ?? ''); ?>
    </h1>
    
    <p>
        <a href="<?php echo admin_url('admin.php?page=td-staff-edit&id=' . $staff->id); ?>">
            ← <?php _e('Back to Edit Staff Member', 'td-staff'); ?>
        </a>
    </p>
    
    <?php settings_errors('td_tech'); ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('td_tech_save_hours'); ?>
        <input type="hidden" name="staff_id" value="<?php echo (int) ($staff->id ?: 0); ?>">
        
        <div style="margin: 20px 0;">
            <p><?php _e('Set the regular work hours for this staff member. Times are in the staff member\'s local timezone.', 'td-staff'); ?></p>
            <p><strong><?php _e('Timezone:', 'td-staff'); ?></strong> <?php echo esc_html($staff->timezone ?? 'Europe/Oslo'); ?></p>
        </div>
        
        <div class="td-tech-week-actions">
            <button type="button" id="td-tech-copy-week" class="button button-secondary">
                <?php _e('📋 Copy Week', 'td-staff'); ?>
            </button>
            <button type="button" id="td-tech-paste-week" class="button button-secondary" disabled>
                <?php _e('📁 Paste Week', 'td-staff'); ?>
            </button>
            <button type="button" id="td-tech-clear-week" class="button button-secondary">
                <?php _e('🗑️ Clear All', 'td-staff'); ?>
            </button>
            
            <div class="td-tech-week-templates">
                <label for="td-tech-template-select"><?php _e('Quick Templates:', 'td-staff'); ?></label>
                <select id="td-tech-template-select">
                    <option value=""><?php _e('Select template...', 'td-staff'); ?></option>
                    <option value="weekdays-9to5"><?php _e('Weekdays 9-5', 'td-staff'); ?></option>
                    <option value="weekdays-8to6"><?php _e('Weekdays 8-6', 'td-staff'); ?></option>
                    <option value="full-week-9to5"><?php _e('Full Week 9-5', 'td-staff'); ?></option>
                    <option value="split-shift"><?php _e('Split Shift (9-12, 1-5)', 'td-staff'); ?></option>
                </select>
                <button type="button" id="td-tech-apply-template" class="button button-secondary">
                    <?php _e('Apply Template', 'td-staff'); ?>
                </button>
            </div>
        </div>
        
        <div class="td-tech-hours-container">
            <?php foreach ($weekdays as $weekday => $day_name): ?>
            <?php 
            $day_hours = isset($weekly_hours[$weekday]) ? $weekly_hours[$weekday] : [];
            ?>
            <div class="td-tech-day-section" data-weekday="<?php echo $weekday; ?>">
                <div class="td-tech-day-header">
                    <label class="td-tech-day-toggle">
                        <input type="checkbox" class="day-enabled-toggle" 
                               name="day_<?php echo $weekday; ?>_enabled" 
                               id="day_<?php echo $weekday; ?>_enabled"
                               <?php checked(!empty($day_hours)); ?>>
                        <strong><?php echo esc_html($day_name); ?></strong>
                    </label>
                    <button type="button" class="button button-small td-tech-add-shift" 
                            data-weekday="<?php echo $weekday; ?>"
                            <?php echo empty($day_hours) ? 'style="display:none;"' : ''; ?>>
                        <?php _e('+ Add Shift', 'td-staff'); ?>
                    </button>
                </div>
                
                <div class="td-tech-shifts-container" data-weekday="<?php echo $weekday; ?>"
                     <?php echo empty($day_hours) ? 'style="display:none;"' : ''; ?>>
                    <?php if (!empty($day_hours)): ?>
                        <?php foreach ($day_hours as $index => $hour): ?>
                        <div class="td-tech-shift-row">
                            <input type="time" 
                                   name="day_<?php echo $weekday; ?>_shifts[<?php echo $index; ?>][start]" 
                                   value="<?php echo esc_attr(td_tech_minutes_to_time($hour['start_min'])); ?>"
                                   required>
                            <span class="td-tech-time-separator"><?php _e('to', 'td-staff'); ?></span>
                            <input type="time" 
                                   name="day_<?php echo $weekday; ?>_shifts[<?php echo $index; ?>][end]" 
                                   value="<?php echo esc_attr(td_tech_minutes_to_time($hour['end_min'])); ?>"
                                   required>
                            <button type="button" class="button button-small td-tech-remove-shift">
                                <?php _e('Remove', 'td-staff'); ?>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="td-tech-shift-row">
                            <input type="time" 
                                   name="day_<?php echo $weekday; ?>_shifts[0][start]" 
                                   value="09:00"
                                   required>
                            <span class="td-tech-time-separator"><?php _e('to', 'td-staff'); ?></span>
                            <input type="time" 
                                   name="day_<?php echo $weekday; ?>_shifts[0][end]" 
                                   value="17:00"
                                   required>
                            <button type="button" class="button button-small td-tech-remove-shift">
                                <?php _e('Remove', 'td-staff'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="margin: 20px 0;">
            <p class="description">
                <?php _e('Add multiple shifts per day by clicking "Add Shift". Each shift represents a continuous work period.', 'td-staff'); ?>
            </p>
        </div>
        
        <p class="submit">
            <input type="submit" name="td_tech_save_hours" class="button-primary" 
                   value="<?php _e('Save Work Hours', 'td-staff'); ?>">
            <a href="<?php echo admin_url('admin.php?page=td-staff-edit&id=' . $staff->id); ?>" class="button">
                <?php _e('Cancel', 'td-staff'); ?>
            </a>
        </p>
    </form>
    
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
          <h3><?php _e('Related Actions', 'td-staff'); ?></h3>
        <p>
                <a href="<?php echo admin_url('admin.php?page=td-staff-exceptions&id=' . $staff->id); ?>" 
                    class="button"><?php _e('Manage Exceptions', 'td-staff'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=td-staff'); ?>" 
                    class="button"><?php _e('All Staff', 'td-staff'); ?></a>
        </p>
    </div>
</div>

<style>
.td-tech-hours-container {
    margin: 20px 0;
}

.td-tech-day-section {
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 10px;
    background: #fff;
}

.td-tech-day-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f9f9f9;
    border-bottom: 1px solid #ddd;
}

.td-tech-day-toggle {
    margin: 0;
    font-weight: 600;
}

.td-tech-day-toggle input[type="checkbox"] {
    margin-right: 8px;
}

.td-tech-shifts-container {
    padding: 15px;
}

.td-tech-shift-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 3px;
}

.td-tech-shift-row:last-child {
    margin-bottom: 0;
}

.td-tech-shift-row input[type="time"] {
    width: 100px;
}

.td-tech-time-separator {
    color: #666;
    font-weight: 500;
}

.td-tech-add-shift {
    color: #0073aa;
}

.td-tech-remove-shift {
    color: #d63638;
    border-color: #d63638;
}

.td-tech-remove-shift:hover {
    background: #d63638;
    color: white;
}

.td-tech-week-actions {
    background: #f0f0f1;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px;
    margin: 20px 0;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.td-tech-week-templates {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
}

.td-tech-week-templates label {
    font-weight: 600;
    margin: 0;
}

.td-tech-week-templates select {
    min-width: 180px;
}

@media (max-width: 600px) {
    .td-tech-day-header {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    
    .td-tech-shift-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .td-tech-shift-row input[type="time"] {
        width: 120px;
    }
}
</style>

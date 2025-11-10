<?php
/**
 * Staff View Admin Template (Read-Only)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// $staff variable is available from the calling method
?>

<div class="wrap td-tech-admin-page">
    <h1 class="wp-heading-inline">
    <?php printf(__('View Staff Member: %s', 'td-staff'), esc_html($staff->display_name ?? '')); ?>
    </h1>
    
        <a href="<?php echo admin_url('admin.php?page=td-staff'); ?>" class="page-title-action">
            <?php _e('← Back to List', 'td-staff'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <div class="td-tech-view-container">
        <!-- Top row: Information boxes in 2x2 grid -->
        <div class="td-tech-sections-row">
            <!-- Basic Information -->
            <div class="td-tech-info-section">
                <h2><?php _e("Basic Information", "td-staff"); ?></h2>
                <div class="td-tech-info-grid">
                    <div class="td-tech-info-item">
                            <strong><?php _e("Display Name:", "td-staff"); ?></strong>
                        <span><?php echo esc_html($staff->display_name ?? ''); ?></span>
                    </div>
                    
                    <div class="td-tech-info-item">
                        <strong><?php _e("Email:", "td-staff"); ?></strong>
                        <span><?php echo esc_html($staff->email ?? ''); ?></span>
                    </div>
                    
                    <div class="td-tech-info-item">
                            <strong><?php _e("Phone:", "td-staff"); ?></strong>
                        <span><?php echo esc_html($staff->phone ?? "—"); ?></span>
                    </div>
                    
                    <div class="td-tech-info-item">
                        <strong><?php _e("Status:", "td-staff"); ?></strong>
                        <span class="td-tech-status <?php echo $staff->active ? "active" : "inactive"; ?>">
                                <?php echo $staff->active ? __("Active", "td-staff") : __("Inactive", "td-staff"); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- CalDAV Configuration -->
            <div class="td-tech-info-section">
                <h2><?php _e("CalDAV Configuration", "td-staff"); ?></h2>
                <div class="td-tech-info-grid">
                    <div class="td-tech-info-item">
                            <strong><?php _e("CalDAV URL:", "td-staff"); ?></strong>
                        <span><?php echo esc_html($staff->nc_base_url ?? "—"); ?></span>
                    </div>
                    
                    <div class="td-tech-info-item">
                        <strong><?php _e("CalDAV Username:", "td-staff"); ?></strong>
                        <span><?php echo esc_html($staff->nc_username ?? "—"); ?></span>
                    </div>
                    
                    <div class="td-tech-info-item">
                            <strong><?php _e("Calendar Path:", "td-staff"); ?></strong>
                        <span><?php echo esc_html($staff->nc_calendar_path ?? "—"); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Skills -->
            <div class="td-tech-info-section">
                <h2><?php _e("Skills", "td-staff"); ?></h2>
                <div class="td-tech-skills">
                    <?php if (!empty($staff->skills)): ?>
                        <?php foreach ($staff->skills as $skill): ?>
                            <?php if (is_array($skill) && isset($skill['label'])): ?>
                                <?php
                                    // Determine skill color based on level (same logic as staff-list.php)
                                    $level = strtolower($skill['level'] ?? '');
                                    $color_style = '';
                                    switch ($level) {
                                        case 'beginner':
                                            $color_style = 'background: linear-gradient(135deg, #4CAF50, #45a049); color: white;';
                                            break;
                                        case 'intermediate':
                                            $color_style = 'background: linear-gradient(135deg, #FF9800, #f57c00); color: white;';
                                            break;
                                        case 'advanced':
                                            $color_style = 'background: linear-gradient(135deg, #f44336, #d32f2f); color: white;';
                                            break;
                                        case 'expert':
                                            $color_style = 'background: linear-gradient(135deg, #9C27B0, #7B1FA2); color: white;';
                                            break;
                                        default:
                                            $color_style = 'background: linear-gradient(135deg, #2196F3, #1976D2); color: white;';
                                            break;
                                    }
                                ?>
                                <span class="td-tech-skill-tag" <?php echo $color_style ? 'style="' . esc_attr($color_style) . '"' : ''; ?>>
                                    <?php echo esc_html($skill['label']); ?>
                                    <?php if (!empty($skill['level'])): ?>
                                        <small> (<?php echo esc_html($skill['level']); ?>)</small>
                                    <?php endif; ?>
                                </span>
                            <?php elseif (is_string($skill)): ?>
                                <span class="td-tech-skill-tag" style="background: linear-gradient(135deg, #2196F3, #1976D2); color: white;"><?php echo esc_html($skill); ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                            <span class="td-tech-no-skills"><?php _e("No skills assigned", "td-staff"); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Settings -->
            <div class="td-tech-info-section">
                <h2><?php _e("Settings", "td-staff"); ?></h2>
                <div class="td-tech-info-grid">
                    <div class="td-tech-info-item">
                            <strong><?php _e("Timezone:", "td-staff"); ?></strong>
                        <span><?php echo esc_html($staff->timezone ?? 'Europe/Oslo'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Work Hours - Full Width -->
        <div class="td-tech-info-section td-tech-full-width-section">
            <h2><?php _e("Work Hours", "td-staff"); ?></h2>
            <?php
                global $wpdb;
                $hours_table = $wpdb->prefix . "td_staff_hours";
                $work_hours = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT weekday, start_min, end_min FROM {$hours_table} WHERE staff_id = %d ORDER BY weekday, start_min",
                        $staff->id
                    ),
                    OBJECT
                );
                $day_names = [
                        0 => __("Sunday", "td-staff"),
                        1 => __("Monday", "td-staff"),
                        2 => __("Tuesday", "td-staff"),
                        3 => __("Wednesday", "td-staff"),
                        4 => __("Thursday", "td-staff"),
                        5 => __("Friday", "td-staff"),
                        6 => __("Saturday", "td-staff")
                ];
            ?>
            <?php if (!empty($work_hours)): ?>
                <?php
                    // Group work hours by weekday
                    $hours_by_day = [];
                    foreach ($work_hours as $hour) {
                        if (!isset($hours_by_day[$hour->weekday])) {
                            $hours_by_day[$hour->weekday] = [];
                        }
                        $hours_by_day[$hour->weekday][] = $hour;
                    }
                ?>
                <table class="td-tech-data-table">
                    <thead>
                        <tr>
                                <th><?php _e("Day", "td-staff"); ?></th>
                                <th><?php _e("Hours", "td-staff"); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hours_by_day as $weekday => $day_hours): ?>
                        <tr>
                            <td>
                                    <strong><?php echo esc_html($day_names[$weekday] ?? __('Unknown', 'td-staff')); ?></strong>
                            </td>
                            <td>
                                <?php
                                    $time_ranges = [];
                                    foreach ($day_hours as $hour) {
                                        $start_hour = intval($hour->start_min / 60);
                                        $start_min = $hour->start_min % 60;
                                        $end_hour = intval($hour->end_min / 60);
                                        $end_min = $hour->end_min % 60;
                                        $time_ranges[] = sprintf("%02d:%02d - %02d:%02d", $start_hour, $start_min, $end_hour, $end_min);
                                    }
                                    echo implode(", ", $time_ranges);
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="td-tech-empty-message">
                        <?php _e("No work hours configured", "td-staff"); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Exceptions - Full Width -->
        <div class="td-tech-info-section td-tech-full-width-section">
            <h2><?php _e("Exceptions & Time Off", "td-staff"); ?></h2>
            <?php
                $exceptions_table = $wpdb->prefix . "td_staff_exception";
                $exception_rows = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$exceptions_table} WHERE staff_id = %d AND end_utc >= %s ORDER BY start_utc DESC LIMIT 10",
                        $staff->id,
                        current_time("mysql", true)
                    ),
                    ARRAY_A
                );
                $exceptions = [];
                foreach ($exception_rows as $row) {
                    $exceptions[] = new TD_Staff_Exception($row);
                }
            ?>
            <?php if (!empty($exceptions)): ?>
                <table class="td-tech-data-table">
                    <thead>
                        <tr>
                                <th><?php _e("Type", "td-staff"); ?></th>
                                <th><?php _e("Date/Time", "td-staff"); ?></th>
                                <th><?php _e("Note", "td-staff"); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exceptions as $exception): ?>
                        <tr>
                            <td>
                                <span class="td-tech-exception-type td-tech-exception-<?php echo esc_attr($exception->type); ?>">
                                    <?php
                                        switch ($exception->type) {
                                                case "unavailable": _e("Unavailable", "td-staff"); break;
                                                case "holiday": _e("Holiday", "td-staff"); break;
                                                case "sick": _e("Sick Leave", "td-staff"); break;
                                                case "vacation": _e("Vacation", "td-staff"); break;
                                            default: echo esc_html(ucfirst($exception->type));
                                        }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                    try {
                                        $start = new DateTime($exception->start_utc);
                                        $end = new DateTime($exception->end_utc);
                                        if ($start->format("Y-m-d") === $end->format("Y-m-d")) {
                                            echo $start->format("M j, Y");
                                            if ($start->format("H:i") !== "00:00" || $end->format("H:i") !== "23:59") {
                                                echo "<br><small>(" . $start->format("H:i") . " - " . $end->format("H:i") . ")</small>";
                                            }
                                        } else {
                                            echo $start->format("M j") . " - " . $end->format("M j, Y");
                                        }
                                    } catch (Exception $e) {
                                            echo esc_html__('Invalid date', 'td-staff');
                                    }
                                ?>
                            </td>
                            <td>
                                <?php echo esc_html($exception->note ?? "—"); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="td-tech-empty-message">
                        <?php _e("No upcoming exceptions or time-off scheduled", "td-staff"); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Action Buttons -->
        <div class="td-tech-actions">
            <a href="<?php echo admin_url("admin.php?page=td-staff-edit&id=" . $staff->id); ?>"
               class="button button-primary">
                    <?php _e("Edit Staff Member", "td-staff"); ?>
            </a>
            
            <a href="<?php echo admin_url("admin.php?page=td-staff-hours&id=" . $staff->id); ?>"
               class="button button-secondary">
                    <?php _e("Manage Work Hours", "td-staff"); ?>
            </a>
            
            <a href="<?php echo admin_url("admin.php?page=td-staff-exceptions&id=" . $staff->id); ?>"
               class="button button-secondary">
                    <?php _e("Manage Exceptions", "td-staff"); ?>
            </a>
        </div>
    </div>
</div>

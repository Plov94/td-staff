<?php
/**
 * Staff List Admin View
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get staff list with filters
$search = sanitize_text_field($_GET['s'] ?? '');
$active_filter = isset($_GET['active']) ? (int) $_GET['active'] : null;
$skill_filter = sanitize_text_field($_GET['skill'] ?? '');

$args = [];
if ($active_filter !== null) {
    $args['active'] = (bool) $active_filter;
}
if (!empty($skill_filter)) {
    $args['skill'] = $skill_filter;
}

$staff_members = td_tech()->repo()->list($args);

// Filter by search term if provided
if (!empty($search)) {
    $staff_members = array_filter($staff_members, function($staff) use ($search) {
        return stripos($staff->display_name, $search) !== false || 
               stripos($staff->email, $search) !== false;
    });
}

// Get unique skills for filter dropdown
$all_skills = [];
foreach ($staff_members as $staff) {
    $skill_labels = td_tech_get_skill_labels($staff->skills);
    $all_skills = array_merge($all_skills, $skill_labels);
}
$all_skills = array_unique($all_skills);
sort($all_skills);
?>

<div class="wrap td-tech-admin-page">
        <h1 class="wp-heading-inline"><?php _e('Staff Management', 'td-staff'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=td-staff-add'); ?>" class="page-title-action">
        <?php _e('Add New', 'td-staff'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <?php settings_errors('td_tech'); ?>
    
    <!-- Filters -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="td-staff">
            
            <div class="alignleft actions">
                <select name="active">
                    <option value=""><?php _e('All Status', 'td-staff'); ?></option>
                    <option value="1" <?php selected($active_filter, 1); ?>><?php _e('Active', 'td-staff'); ?></option>
                    <option value="0" <?php selected($active_filter, 0); ?>><?php _e('Inactive', 'td-staff'); ?></option>
                </select>
                
                <?php if (!empty($all_skills)): ?>
                <select name="skill">
                    <option value=""><?php _e('All Skills', 'td-staff'); ?></option>
                    <?php foreach ($all_skills as $skill): ?>
                    <option value="<?php echo esc_attr($skill ?: ''); ?>" <?php selected($skill_filter, $skill); ?>>
                        <?php echo esc_html($skill ?: ''); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
                
                <?php submit_button(__('Filter', 'td-staff'), 'secondary', 'filter', false); ?>
            </div>
            
            <div class="alignright">
                <input type="search" name="s" value="<?php echo esc_attr($search ?: ''); ?>" 
              placeholder="<?php _e('Search staff...', 'td-staff'); ?>">
          <?php submit_button(__('Search', 'td-staff'), 'secondary', 'search', false); ?>
            </div>
        </form>
    </div>
    
    <!-- Bulk Actions Form -->
    <form method="post" action="">
        <?php wp_nonce_field('td_tech_bulk_action'); ?>
        
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="action">
                    <option value=""><?php _e('Bulk Actions', 'td-staff'); ?></option>
                    <option value="bulk_deactivate"><?php _e('Deactivate', 'td-staff'); ?></option>
                </select>
                <?php submit_button(__('Apply', 'td-staff'), 'secondary', 'bulk_action', false); ?>
            </div>
        </div>
        
        <!-- Staff Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all">
                    </td>
                    <th class="manage-column column-id"><?php _e('ID', 'td-staff'); ?></th>
                    <th class="manage-column column-name"><?php _e('Name', 'td-staff'); ?></th>
                    <th class="manage-column"><?php _e('Email', 'td-staff'); ?></th>
                    <th class="manage-column"><?php _e('Phone', 'td-staff'); ?></th>
                    <th class="manage-column"><?php _e('Skills', 'td-staff'); ?></th>
                    <th class="manage-column"><?php _e('Status', 'td-staff'); ?></th>
                    <th class="manage-column"><?php _e('Actions', 'td-staff'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($staff_members)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px;">
                        <?php _e('No staff found.', 'td-staff'); ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($staff_members as $staff): ?>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" name="staff[]" value="<?php echo (int) ($staff->id ?: 0); ?>">
                    </th>
                    <td class="column-id"><code><?php echo (int) ($staff->id ?: 0); ?></code></td>
                    <td class="column-name">
                        <strong>
                            <a href="<?php echo admin_url('admin.php?page=td-staff-view&id=' . $staff->id); ?>">
                                <?php echo esc_html($staff->display_name ?: ''); ?>
                            </a>
                        </strong>
                        <div class="row-actions">
                            <span class="edit">
                                <a href="<?php echo admin_url('admin.php?page=td-staff-edit&id=' . $staff->id); ?>">
                                    <?php _e('Edit', 'td-staff'); ?>
                                </a> |
                            </span>
                            <span class="hours">
                                <a href="<?php echo admin_url('admin.php?page=td-staff-hours&id=' . $staff->id); ?>">
                                    <?php _e('Hours', 'td-staff'); ?>
                                </a> |
                            </span>
                            <span class="exceptions">
                                <a href="<?php echo admin_url('admin.php?page=td-staff-exceptions&id=' . $staff->id); ?>">
                                    <?php _e('Exceptions', 'td-staff'); ?>
                                </a>
                            </span>
                        </div>
                    </td>
                    <td><?php echo esc_html($staff->email ?: ''); ?></td>
                    <td><?php echo esc_html($staff->phone ?: '—'); ?></td>
                    <td>
                        <?php if (!empty($staff->skills)): ?>
                            <div class="td-tech-skills">
                                <?php foreach ($staff->skills as $skill): ?>
                                    <?php
                                    // Determine color based on skill level
                                    $level = strtolower($skill['level'] ?? '');
                                    $color_style = '';
                                    switch ($level) {
                                        case 'beginner':
                                            $color_style = 'background: linear-gradient(135deg, #28a745, #1e7e34);';
                                            break;
                                        case 'intermediate':
                                            $color_style = 'background: linear-gradient(135deg, #ffc107, #e0a800); color: #333;';
                                            break;
                                        case 'advanced':
                                            $color_style = 'background: linear-gradient(135deg, #fd7e14, #e8590c);';
                                            break;
                                        case 'expert':
                                            $color_style = 'background: linear-gradient(135deg, #dc3545, #c82333);';
                                            break;
                                        case 'master':
                                            $color_style = 'background: linear-gradient(135deg, #6f42c1, #5a2d91);';
                                            break;
                                        default:
                                            $color_style = ''; // Use default CSS
                                    }
                                    ?>
                                    <span class="td-tech-skill-tag" <?php echo $color_style ? 'style="' . esc_attr($color_style) . '"' : ''; ?>>
                                        <?php echo esc_html($skill['label']); ?>
                                        <?php if (!empty($skill['level'])): ?>
                                            <span class="skill-level">:<?php echo esc_html($skill['level']); ?></span>
                                        <?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($staff->active): ?>
                            <span style="color: green;">●</span> <?php _e('Active', 'td-staff'); ?>
                        <?php else: ?>
                            <span style="color: red;">●</span> <?php _e('Inactive', 'td-staff'); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                                <a href="<?php echo admin_url('admin.php?page=td-staff-edit&id=' . $staff->id); ?>" 
                                    class="button button-small"><?php _e('Edit', 'td-staff'); ?></a>
                                <a href="<?php echo admin_url('admin.php?page=td-staff-hours&id=' . $staff->id); ?>" 
                                    class="button button-small"><?php _e('Hours', 'td-staff'); ?></a>
                                <a href="<?php echo admin_url('admin.php?page=td-staff-exceptions&id=' . $staff->id); ?>" 
                                    class="button button-small"><?php _e('Exceptions', 'td-staff'); ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Select all checkbox functionality
    $('#cb-select-all').on('change', function() {
        $('input[name="staff[]"]').prop('checked', this.checked);
    });
    
    // Update select all when individual checkboxes change
    $('input[name="staff[]"]').on('change', function() {
        var total = $('input[name="staff[]"]').length;
        var checked = $('input[name="staff[]"]:checked').length;
        $('#cb-select-all').prop('checked', total === checked);
    });
});
</script>

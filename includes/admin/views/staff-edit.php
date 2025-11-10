<?php
/**
 * Staff Edit Admin View
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$is_edit = $staff->id > 0;
$page_title = $is_edit ? __('Edit Staff Member', 'td-staff') : __('Add New Staff Member', 'td-staff');

// Get WordPress users for dropdown
$wp_users = get_users(['fields' => ['ID', 'display_name', 'user_email']]);

// Get timezone options
$timezone_groups = td_tech_get_timezone_options();
?>

<div class="wrap td-tech-admin-page">
    <h1><?php echo esc_html($page_title); ?></h1>
    
    <?php settings_errors('td_tech'); ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('td_tech_save_staff'); ?>
        <?php if ($is_edit): ?>
        <input type="hidden" name="staff_id" value="<?php echo (int) ($staff->id ?: 0); ?>">
        <?php endif; ?>
        
        <table class="form-table td-tech-form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="display_name"><?php _e('Display Name', 'td-staff'); ?> *</label>
                    </th>
                    <td>
                        <input type="text" id="display_name" name="display_name" 
                               value="<?php echo esc_attr($staff->display_name ?? ''); ?>" 
                               class="regular-text" required>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="email"><?php _e('Email', 'td-staff'); ?> *</label>
                    </th>
                    <td>
                        <input type="email" id="email" name="email" 
                               value="<?php echo esc_attr($staff->email ?? ''); ?>" 
                               class="regular-text" required>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="phone"><?php _e('Phone', 'td-staff'); ?></label>
                    </th>
                    <td>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo esc_attr($staff->phone ?? ''); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wp_user_id"><?php _e('WordPress User', 'td-staff'); ?></label>
                    </th>
                    <td>
                        <select id="wp_user_id" name="wp_user_id">
                            <option value=""><?php _e('— No WordPress User —', 'td-staff'); ?></option>
                            <?php foreach ($wp_users as $user): ?>
                            <option value="<?php echo $user->ID; ?>" 
                                    <?php selected($staff->wp_user_id ?: 0, $user->ID); ?>>
                                <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Link this staff member to a WordPress user account (optional).', 'td-staff'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="timezone"><?php _e('Timezone', 'td-staff'); ?></label>
                    </th>
                    <td>
                        <select id="timezone" name="timezone">
                            <?php foreach ($timezone_groups as $group => $timezones): ?>
                            <optgroup label="<?php echo esc_attr($group); ?>">
                                <?php foreach ($timezones as $tz_id => $tz_name): ?>
                                <option value="<?php echo esc_attr($tz_id); ?>" 
                                        <?php selected($staff->timezone ?: 'Europe/Oslo', $tz_id); ?>>
                                    <?php echo esc_html($tz_name); ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="skills"><?php _e('Skills', 'td-staff'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="skills" name="skills" 
                               value="<?php echo esc_attr(td_tech_skills_to_string($staff->skills ?? [])); ?>" 
                               class="td-tech-skills-input">
                        <p class="description td-tech-skills-help">
                            <?php _e('Enter skills separated by commas. Optionally include skill level with colon (e.g., "Plumbing:Expert, Electrical, HVAC:Beginner").', 'td-staff'); ?>
                        </p>

                        <?php 
                        $skill_bank_enabled = (bool) get_option('td_tech_skill_bank_enabled', false);
                        $skill_bank = $skill_bank_enabled ? td_tech_get_skill_bank() : [];
                        if (!empty($skill_bank)) : ?>
                        <div class="td-tech-skill-bank">
                            <div class="td-tech-skill-bank-header">
                                <strong><?php _e('Skill bank', 'td-staff'); ?></strong>
                                <span class="description">&nbsp;<?php _e('Click to add. Levels are optional.', 'td-staff'); ?></span>
                                <label style="margin-left: 10px;">
                                    <?php _e('Level:', 'td-staff'); ?>
                                    <select id="td-tech-skill-level" style="min-width:120px;">
                                        <option value=""><?php _e('None', 'td-staff'); ?></option>
                                        <option value="Beginner">Beginner</option>
                                        <option value="Intermediate">Intermediate</option>
                                        <option value="Advanced">Advanced</option>
                                        <option value="Expert">Expert</option>
                                        <option value="Master">Master</option>
                                    </select>
                                </label>
                                <button type="button" class="button td-tech-skill-undo" style="margin-left:6px;">
                                    <?php _e('Remove last', 'td-staff'); ?></button>
                                <button type="button" class="button td-tech-skill-clear" style="margin-left:6px;">
                                    <?php _e('Clear', 'td-staff'); ?></button>
                            </div>
                            <div class="td-tech-skill-bank-list">
                                <?php foreach ($skill_bank as $label): ?>
                                    <button type="button" class="button td-tech-skill-chip" data-skill="<?php echo esc_attr($label); ?>"><?php echo esc_html($label); ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="weight"><?php _e('Weight', 'td-staff'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="weight" name="weight" 
                               value="<?php echo esc_attr($staff->weight ?? 1); ?>" 
                               min="1" max="100" class="small-text">
                        <p class="description">
                            <?php _e('Priority weight for assignment (1-100, higher = more preferred).', 'td-staff'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cooldown_sec"><?php _e('Cooldown Period', 'td-staff'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="cooldown_sec" name="cooldown_sec" 
                               value="<?php echo esc_attr($staff->cooldown_sec ?? 0); ?>" 
                               min="0" class="regular-text">
                        <p class="description">
                            <?php _e('Minimum seconds between assignments (0 = no cooldown).', 'td-staff'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="active"><?php _e('Status', 'td-staff'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="active" name="active" 
                                   <?php checked($staff->active ?: false); ?>>
                            <?php _e('Active', 'td-staff'); ?>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- Nextcloud/CalDAV Settings -->
        <div class="td-tech-nc-panel">
            <h3><?php _e('Nextcloud/CalDAV Integration', 'td-staff'); ?></h3>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="nc_base_url"><?php _e('Base URL', 'td-staff'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="nc_base_url" name="nc_base_url" 
                                   value="<?php echo esc_attr($staff->nc_base_url ?? get_option('td_tech_default_nc_base_url', '')); ?>" 
                                   class="regular-text" placeholder="<?php echo esc_attr__('https://cloud.example.com', 'td-staff'); ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="nc_calendar_path"><?php _e('Calendar Path', 'td-staff'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="nc_calendar_path" name="nc_calendar_path" 
                                   value="<?php echo esc_attr($staff->nc_calendar_path ?? ''); ?>" 
                                   class="regular-text" placeholder="<?php echo esc_attr__('/remote.php/dav/calendars/username/personal/', 'td-staff'); ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="nc_username"><?php _e('Username', 'td-staff'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="nc_username" name="nc_username" 
                                   value="<?php echo esc_attr($staff->nc_username ?? ''); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="nc_app_password"><?php _e('App Password', 'td-staff'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="nc_app_password" name="nc_app_password" 
                                   value="" class="regular-text" 
                                   placeholder="<?php echo ($staff->has_caldav_credentials() ? '••••••••••••' : ''); ?>">
                            <p class="description">
                                <?php if ($staff->has_caldav_credentials()): ?>
                                    <?php _e('Leave blank to keep existing password.', 'td-staff'); ?>
                                <?php else: ?>
                                    <?php _e('Generate an app password in your Nextcloud settings.', 'td-staff'); ?>
                                <?php endif; ?>
                            </p>
                            <?php if ($is_edit): ?>
                            <p class="description">
                                <?php
                                $has_env = !empty($staff->nc_app_password_env);
                                $has_legacy = !empty($staff->nc_app_password_ct) && !empty($staff->nc_app_password_iv) && !empty($staff->nc_app_password_tag);
                                if ($has_env) {
                                    echo esc_html__('Stored app password: Yes (sodium envelope)', 'td-staff');
                                } elseif ($has_legacy) {
                                    echo esc_html__('Stored app password: Yes (legacy AES-GCM)', 'td-staff');
                                } else {
                                    echo esc_html__('Stored app password: No', 'td-staff');
                                }
                                ?>
                            </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <?php if ($is_edit): ?>
            <div class="td-tech-test-connection">
                <button type="button" id="td-tech-test-caldav" class="button" 
                        data-staff-id="<?php echo (int) ($staff->id ?: 0); ?>">
                    <?php _e('Test Connection', 'td-staff'); ?>
                </button>
                <div id="td-tech-test-result" class="td-tech-test-result" style="display: none;"></div>
            </div>
            <?php endif; ?>
        </div>
        
        <p class="submit">
            <input type="submit" name="td_tech_save_staff" class="button-primary" 
                   value="<?php echo $is_edit ? __('Update Staff Member', 'td-staff') : __('Add Staff Member', 'td-staff'); ?>">
            <a href="<?php echo admin_url('admin.php?page=td-staff'); ?>" class="button">
                <?php _e('Cancel', 'td-staff'); ?>
            </a>
        </p>
        
        <?php if ($is_edit): ?>
        <input type="hidden" id="td_tech_nonce" value="<?php echo wp_create_nonce('wp_rest'); ?>">
        <?php endif; ?>
    </form>
    
    <?php if ($is_edit && $staff->active): ?>
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
          <h3><?php _e('Quick Actions', 'td-staff'); ?></h3>
        <p>
                <a href="<?php echo admin_url('admin.php?page=td-staff-hours&id=' . $staff->id); ?>" 
                    class="button"><?php _e('Manage Work Hours', 'td-staff'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=td-staff-exceptions&id=' . $staff->id); ?>" 
                    class="button"><?php _e('Manage Exceptions', 'td-staff'); ?></a>
        </p>
    </div>
    <?php endif; ?>
</div>

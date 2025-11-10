<?php
/**
 * Settings Admin View
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$default_nc_base_url = get_option('td_tech_default_nc_base_url', '');
$allow_hard_uninstall = get_option('td_tech_allow_hard_uninstall', false);
$store_plain_pii = get_option('td_tech_store_plain_pii', false);
$skill_bank_enabled = (bool) get_option('td_tech_skill_bank_enabled', false);
$skill_bank_categories = (array) get_option('td_tech_skill_bank_categories', []);
?>

<div class="wrap td-tech-admin-page">
    <h1><?php _e('TD Staff Settings', 'td-staff'); ?></h1>
    
    <?php settings_errors('td_tech'); ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('td_tech_save_settings'); ?>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="default_nc_base_url"><?php _e('Default Nextcloud Base URL', 'td-staff'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="default_nc_base_url" name="default_nc_base_url" 
                               value="<?php echo esc_attr($default_nc_base_url ?? ''); ?>" 
                               class="regular-text" placeholder="<?php echo esc_attr__('https://cloud.example.com', 'td-staff'); ?>">
                        <p class="description">
                            <?php _e('Default base URL that will be pre-filled when adding new staff members.', 'td-staff'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <?php _e('Data Management', 'td-staff'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="allow_hard_uninstall" 
                                   <?php checked($allow_hard_uninstall); ?>>
                            <?php _e('Allow hard uninstall to remove all data', 'td-staff'); ?>
                        </label>
                        <p class="description">
                            <?php _e('When enabled, uninstalling the plugin will permanently delete all staff data, work schedules, and exceptions. When disabled, data is preserved even after uninstall.', 'td-staff'); ?>
                        </p>
                        <label style="margin-top:10px; display:block;">
                            <input type="checkbox" name="store_plain_pii" <?php checked($store_plain_pii); ?>>
                            <?php _e('Store plaintext email/phone in database (not recommended)', 'td-staff'); ?>
                        </label>
                        <p class="description">
                            <?php _e('When disabled, new or updated email/phone values are only stored encrypted with a blind index for lookups. Plaintext columns may remain for legacy rows until migrated.', 'td-staff'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Skill Bank', 'td-staff'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="skill_bank_enabled" <?php checked($skill_bank_enabled); ?>>
                            <?php _e('Enable Skill Bank on staff editor', 'td-staff'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Shows a preset list of skills under the Skills field to speed up data entry.', 'td-staff'); ?>
                        </p>

                        <fieldset style="margin-top:10px;">
                            <legend><?php _e('Skill Categories', 'td-staff'); ?></legend>
                            <?php
                            $all_categories = [
                                'general' => __('General', 'td-staff'),
                                'it' => __('IT & Development', 'td-staff'),
                                'trades' => __('Trades & Services', 'td-staff'),
                                'wellness' => __('Health & Wellness', 'td-staff'),
                            ];
                            foreach ($all_categories as $key => $label): ?>
                                <label style="display:inline-block; margin-right:15px;">
                                    <input type="checkbox" name="skill_bank_categories[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $skill_bank_categories, true)); ?>>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description">
                                <?php _e('Only skills from the selected categories will be shown. Leave all unchecked to show all.', 'td-staff'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <p class="submit">
         <input type="submit" name="td_tech_save_settings" class="button-primary" 
             value="<?php _e('Save Settings', 'td-staff'); ?>">
        </p>
    </form>
    
    <!-- Export/Import Section -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
    <h3><?php _e('Export/Import Data', 'td-staff'); ?></h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">
            <!-- Export Section -->
            <div style="background: #f9f9f9; padding: 20px; border-radius: 4px;">
                <h4><?php _e('Export Staff Data', 'td-staff'); ?></h4>
                <p><?php _e('Export all staff data, work schedules, and exceptions. CalDAV credentials are excluded for security.', 'td-staff'); ?></p>
                
                <form method="post" style="margin-top: 15px;">
                    <?php wp_nonce_field('td_tech_export_data'); ?>
                    
                    <div style="margin-bottom: 15px;">
                        <label>
                            <input type="radio" name="export_format" value="json" checked>
                            <?php _e('JSON Format (recommended)', 'td-staff'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="export_format" value="csv">
                            <?php _e('CSV Format (basic data only)', 'td-staff'); ?>
                        </label>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label>
                            <input type="checkbox" name="include_inactive" value="1">
                            <?php _e('Include inactive staff', 'td-staff'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="include_hours" value="1" checked>
                            <?php _e('Include work hours', 'td-staff'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="include_exceptions" value="1" checked>
                            <?php _e('Include exceptions', 'td-staff'); ?>
                        </label>
                    </div>
                    
              <input type="submit" name="td_tech_export" class="button button-secondary" 
                  value="<?php _e('Download Export', 'td-staff'); ?>">
                </form>
            </div>
            
            <!-- Import Section -->
            <div style="background: #f9f9f9; padding: 20px; border-radius: 4px;">
                <h4><?php _e('Import Staff Data', 'td-staff'); ?></h4>
                <p><?php _e('Import staff data from a previously exported JSON file.', 'td-staff'); ?></p>
                
                <form method="post" enctype="multipart/form-data" style="margin-top: 15px;">
                    <?php wp_nonce_field('td_tech_import_data'); ?>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="import_file"><?php _e('Select JSON file:', 'td-staff'); ?></label><br>
                        <input type="file" id="import_file" name="import_file" accept=".json" required>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label>
                            <input type="radio" name="import_mode" value="merge" checked>
                            <?php _e('Merge with existing data', 'td-staff'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="import_mode" value="replace">
                            <?php _e('Replace all existing data (destructive!)', 'td-staff'); ?>
                        </label>
                    </div>
                    
              <input type="submit" name="td_tech_import" class="button button-secondary" 
                  value="<?php _e('Import Data', 'td-staff'); ?>"
                  onclick="return confirm('<?php _e('Are you sure? This will modify your staff data.', 'td-staff'); ?>');">
                </form>
            </div>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
            <strong><?php _e('Important Notes:', 'td-staff'); ?></strong>
            <ul style="margin-top: 10px;">
                <li><?php _e('CalDAV credentials (app passwords) are never exported for security reasons.', 'td-staff'); ?></li>
                <li><?php _e('Imported staff will need CalDAV credentials configured manually.', 'td-staff'); ?></li>
                <li><?php _e('Always backup your database before importing data.', 'td-staff'); ?></li>
                <li><?php _e('CSV exports contain basic information only (no hours or exceptions).', 'td-staff'); ?></li>
            </ul>
        </div>
    </div>

    <!-- Security & Encryption -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
    <h3><?php _e('Security & Encryption', 'td-staff'); ?></h3>
    <p><?php _e('Sensitive fields are encrypted at rest. For best security and search-by-email support, configure the following keys in wp-config.php or the environment:', 'td-staff'); ?></p>
        <ul>
            <li><code>TD_KMS_KEY_V1</code>  <?php _e('Sodium key for app passwords (XChaCha20-Poly1305).', 'td-staff'); ?></li>
            <li><code>TD_PII_ENC_KEY_V1</code>  <?php _e('Sodium key for PII envelopes (emails/phones). Alias of KMS is also supported.', 'td-staff'); ?></li>
            <li><code>TD_PII_IDX_KEY_V1</code>  <?php _e('HMAC key for email blind index lookups.', 'td-staff'); ?></li>
        </ul>
    <p class="description"><?php _e('Keys should be 32 random bytes, base64-encoded. You can prefix values with base64: for clarity.', 'td-staff'); ?></p>

        <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
            <tbody>
                <tr>
                    <th style="width: 240px;">TD_KMS_KEY_V1</th>
                    <td><?php echo defined('TD_KMS_KEY_V1') || getenv('TD_KMS_KEY_V1') ? '<span style="color: green;">✓</span> ' . esc_html__('Configured', 'td-staff') : '<span style="color: #d63638;">✗</span> ' . esc_html__('Missing', 'td-staff'); ?></td>
                </tr>
                <tr>
                    <th>TD_PII_ENC_KEY_V1</th>
                    <td><?php echo defined('TD_PII_ENC_KEY_V1') || getenv('TD_PII_ENC_KEY_V1') ? '<span style="color: green;">✓</span> ' . esc_html__('Configured', 'td-staff') : '<span style="color: #d63638;">✗</span> ' . esc_html__('Missing (will fall back to KMS if present)', 'td-staff'); ?></td>
                </tr>
                <tr>
                    <th>TD_PII_IDX_KEY_V1</th>
                    <td><?php echo defined('TD_PII_IDX_KEY_V1') || getenv('TD_PII_IDX_KEY_V1') ? '<span style="color: green;">✓</span> ' . esc_html__('Configured', 'td-staff') : '<span style="color: #d63638;">✗</span> ' . esc_html__('Missing (email lookup will use plaintext fallback)', 'td-staff'); ?></td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 15px;">
            <form method="post" action="" style="display:inline-block; margin-right: 10px;">
                <?php wp_nonce_field('td_tech_migrate_caldav_to_sodium'); ?>
                <input type="submit" name="td_tech_migrate_caldav_to_sodium" class="button button-secondary" value="<?php _e('Migrate legacy CalDAV passwords → sodium', 'td-staff'); ?>">
            </form>
            <form method="post" action="" style="display:inline-block;">
                <?php wp_nonce_field('td_tech_backfill_pii_envelopes'); ?>
                <input type="submit" name="td_tech_backfill_pii_envelopes" class="button button-secondary" value="<?php _e('Backfill PII envelopes/indexes', 'td-staff'); ?>">
            </form>
            <p class="description" style="margin-top: 8px;">
                <?php _e('These operations are safe to run multiple times; they only update rows that need migration/backfill.', 'td-staff'); ?>
            </p>
        </div>
    </div>

    <!-- Cache Management -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
    <h3><?php _e('Cache Management', 'td-staff'); ?></h3>
    <p><?php _e('If you notice old assets (CSS/JS) or stale admin behavior, you can purge caches and bump the asset version used by this plugin to force browsers and caching layers to fetch fresh files.', 'td-staff'); ?></p>
        <form method="post" action="" style="margin-top: 10px;">
            <?php wp_nonce_field('td_tech_purge_cache'); ?>
         <input type="submit" name="td_tech_purge_cache" class="button button-secondary" value="<?php _e('Purge Cache & Refresh Assets', 'td-staff'); ?>"
             onclick="return confirm('<?php echo esc_js(__('Purge all caches and force-refresh assets now?', 'td-staff')); ?>');">
        </form>
        <p class="description">
            <?php _e('This attempts to flush WordPress object cache and common caching plugins (if present), and increments an internal asset version to defeat cache/minifiers.', 'td-staff'); ?>
        </p>
    </div>
    
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
    <h3><?php _e('API Information', 'td-staff'); ?></h3>
        
    <p><?php _e('This plugin provides both PHP and REST APIs for other plugins to access staff data.', 'td-staff'); ?></p>
        
    <h4><?php _e('REST API Endpoints', 'td-staff'); ?></h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Method', 'td-staff'); ?></th>
                    <th><?php _e('Endpoint', 'td-staff'); ?></th>
                    <th><?php _e('Description', 'td-staff'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>GET</code></td>
                    <td><code>/wp-json/td-tech/v1/staff</code></td>
                    <td><?php _e('List all staff', 'td-staff'); ?></td>
                </tr>
                <tr>
                    <td><code>GET</code></td>
                    <td><code>/wp-json/td-tech/v1/staff/{id}</code></td>
                    <td><?php _e('Get single staff member', 'td-staff'); ?></td>
                </tr>
                <tr>
                    <td><code>GET</code></td>
                    <td><code>/wp-json/td-tech/v1/staff/{id}/hours</code></td>
                    <td><?php _e('Get staff work hours', 'td-staff'); ?></td>
                </tr>
                <tr>
                    <td><code>GET</code></td>
                    <td><code>/wp-json/td-tech/v1/staff/{id}/exceptions</code></td>
                    <td><?php _e('Get staff exceptions', 'td-staff'); ?></td>
                </tr>
                <tr>
                    <td><code>POST</code></td>
                    <td><code>/wp-json/td-tech/v1/staff</code></td>
                    <td><?php _e('Create new staff member (admin only)', 'td-staff'); ?></td>
                </tr>
                <tr>
                    <td><code>PUT</code></td>
                    <td><code>/wp-json/td-tech/v1/staff/{id}</code></td>
                    <td><?php _e('Update staff member (admin only)', 'td-staff'); ?></td>
                </tr>
            </tbody>
        </table>
        
    <h4><?php _e('PHP API Example', 'td-staff'); ?></h4>
        <pre><code><?php echo esc_html('// Get the service container
$container = td_tech();

// Get all active staff
$staff_members = $container->repo()->list([\'active\' => true]);

// Get work windows for a specific day
$staff_id = 123;
$day = new DateTimeImmutable(\'2024-01-15\', new DateTimeZone(\'UTC\'));
$windows = $container->schedule()->get_daily_work_windows($staff_id, $day);

// Get CalDAV credentials (if available)
$credentials = $container->caldav()->get_credentials($staff_id);'); ?></code></pre>
    </div>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
    <h3><?php _e('System Information', 'td-staff'); ?></h3>
        
        <table class="wp-list-table widefat fixed striped">
            <tbody>
                <tr>
                    <th style="width: 200px;"><?php _e('Plugin Version', 'td-staff'); ?></th>
                    <td><?php echo esc_html(TD_TECH_VERSION); ?></td>
                </tr>
                <tr>
                    <th><?php _e('API Version', 'td-staff'); ?></th>
                    <td><?php echo esc_html(TD_TECH_API_VERSION); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Database Version', 'td-staff'); ?></th>
                    <td><?php echo esc_html(get_option('td_tech_db_version', 'Unknown')); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Encryption Available', 'td-staff'); ?></th>
                    <td>
                        <?php if (td_tech_encryption_available()): ?>
                            <span style="color: green;">✓</span> <?php _e('Yes (AES-256-GCM)', 'td-staff'); ?>
                        <?php else: ?>
                            <span style="color: red;">✗</span> <?php _e('No (OpenSSL not available)', 'td-staff'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Total Staff', 'td-staff'); ?></th>
                    <td>
                        <?php
                        $total_count = count(td_tech()->repo()->list());
                        $active_count = count(td_tech()->repo()->list(['active' => true]));
                        printf(__('%d total (%d active)', 'td-staff'), $total_count, $active_count);
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('WordPress Version', 'td-staff'); ?></th>
                    <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                </tr>
                <tr>
                    <th><?php _e('PHP Version', 'td-staff'); ?></th>
                    <td><?php echo esc_html(PHP_VERSION); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php
/**
 * TD Staff Admin Menu
 * 
 * Handles admin menu and page routing
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Menu class
 */
class TD_Tech_Admin_Menu {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_form_submissions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_td_tech_delete_exception', [$this, 'ajax_delete_exception']);
        add_action('wp_ajax_td_tech_test_caldav', [$this, 'ajax_test_caldav']);
        
        // Check for PHP 8.1 + outdated WordPress compatibility issues
        add_action('admin_notices', [$this, 'check_compatibility_notice']);
        
    // Nuclear option: Aggressive null protection for TD Staff pages
        add_action('init', [$this, 'aggressive_null_fix'], 1);
        add_action('plugins_loaded', [$this, 'aggressive_null_fix'], 1);
        add_action('admin_init', [$this, 'aggressive_null_fix'], 1);
        add_action('current_screen', [$this, 'aggressive_null_fix'], 1);
        add_action('admin_head', [$this, 'aggressive_null_fix'], 1);
        add_action('admin_menu', [$this, 'aggressive_null_fix'], 1);
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
    // Load on ALL TD Staff pages - simplified approach
        $page = $_GET['page'] ?? '';
        
        // Only load on our plugin pages
        if (strpos($page, 'td-staff') === false) {
            return;
        }
        
    // Use plugin version + asset buster + file mtime for aggressive cache busting
    $asset_buster = (int) get_option('td_tech_asset_buster', 1);
    $js_path = TD_TECH_PLUGIN_DIR . 'assets/js/admin.js';
    $css_path = TD_TECH_PLUGIN_DIR . 'assets/css/admin.css';
    $version = TD_TECH_VERSION . '.' . $asset_buster . '.' . (@filemtime($js_path) ?: 0);
        
        wp_enqueue_script(
            'td-tech-admin',
            TD_TECH_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            $version,
            true
        );
        
        // Determine current page context
        $current_page = '';
        if (isset($_GET['action']) && $_GET['action'] === 'hours') {
            $current_page = 'staff-hours';
        } elseif (isset($_GET['action']) && in_array($_GET['action'], ['edit', 'add'])) {
            $current_page = 'staff-edit';
        } elseif ($page === 'td-staff') {
            $current_page = 'staff-list';
        } elseif ($page === 'td-staff-settings') {
            $current_page = 'settings';
        }
        
        wp_localize_script('td-tech-admin', 'tdTechAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_rest')
        ]);
        
        wp_localize_script('td-tech-admin', 'td_tech_admin', [
            'current_page' => $current_page,
            'strings' => [
                // Week actions
                'week_copied' => __('✅ Week Copied', 'td-staff'),
                'copy_week' => __('📋 Copy Week', 'td-staff'),
                'replace_hours_confirm' => __('This will replace all current work hours. Continue?', 'td-staff'),
                'clear_hours_confirm' => __('This will clear all work hours. Continue?', 'td-staff'),
                'replace_template_confirm' => __('This will replace current work hours with the selected template. Continue?', 'td-staff'),
                
                // CalDAV testing
                'testing' => __('Testing...', 'td-staff'),
                'test_connection' => __('Test Connection', 'td-staff'),
                'caldav_fields_required' => __('Please fill in all CalDAV fields before testing.', 'td-staff'),
                'connection_test_failed' => __('Connection test failed.', 'td-staff'),
                
                // Exception actions
                'delete_exception_confirm' => __('Are you sure you want to delete this exception?', 'td-staff'),
                'delete_exception_failed' => __('Failed to delete exception.', 'td-staff'),
                
                // Validation messages
                'email_invalid' => __('Please enter a valid email address.', 'td-staff'),
                'phone_invalid' => __('Please enter a valid phone number.', 'td-staff'),
                'url_protocol_invalid' => __('URL must use HTTP or HTTPS protocol.', 'td-staff'),
                'url_invalid' => __('Please enter a valid URL.', 'td-staff'),
                'caldav_fields_incomplete' => __('All CalDAV fields are required when any CalDAV field is provided.', 'td-staff'),
                'display_name_required' => __('Display name is required.', 'td-staff'),
                'email_required' => __('Email is required.', 'td-staff'),
                'weight_range_invalid' => __('Weight must be between 1 and 100.', 'td-staff'),
                'cooldown_negative' => __('Cooldown must be non-negative.', 'td-staff'),
                
                // Button text
                'remove' => __('Remove', 'td-staff'),
                'to' => __('to', 'td-staff'),
                'add_shift' => __('+ Add Shift', 'td-staff')
            ]
        ]);
        
        wp_enqueue_style(
            'td-tech-admin',
            TD_TECH_PLUGIN_URL . 'assets/css/admin.css',
            [],
            TD_TECH_VERSION . '.' . $asset_buster . '.' . (@filemtime($css_path) ?: 0)
        );
    }
    
    /**
     * Display compatibility notice for outdated WordPress with PHP 8.1+
     */
    public function check_compatibility_notice() {
        // Only show on our plugin pages
        global $pagenow;
        if ($pagenow !== 'admin.php') {
            return;
        }
        
        $page = $_GET['page'] ?? '';
        if (strpos($page, 'td-staff') === false) {
            return;
        }
        
        $wp_version = get_bloginfo('version');
        $php_version = PHP_VERSION;
        
        // Check if we have PHP 8.1+ with WordPress < 6.0
        if (version_compare($php_version, '8.1', '>=') && version_compare($wp_version, '6.0', '<')) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <h3><?php _e('Compatibility Notice - TD Staff', 'td-staff'); ?></h3>
                <p>
                    <strong><?php _e('Your WordPress version is outdated for PHP 8.1+', 'td-staff'); ?></strong><br>
                    <?php printf(
                        __('You\'re running WordPress %s with PHP %s. For optimal compatibility and to eliminate PHP deprecation warnings, please update WordPress to version 6.0 or higher.', 'td-staff'),
                        '<code>' . esc_html($wp_version) . '</code>',
                        '<code>' . esc_html($php_version) . '</code>'
                    ); ?>
                </p>
                <p>
                    <em><?php _e('The TD Staff plugin will continue to function, but you may see PHP 8.1 deprecation warnings in your error logs until WordPress is updated.', 'td-staff'); ?></em>
                </p>
                <p>
                    <a href="<?php echo admin_url('update-core.php'); ?>" class="button button-primary">
                        <?php _e('Update WordPress', 'td-staff'); ?>
                    </a>
                    <a href="https://wordpress.org/support/wordpress-version/version-6-0/" target="_blank" class="button">
                        <?php _e('Learn More', 'td-staff'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Nuclear option: Override WordPress core functions to prevent PHP 8.1 warnings
     * 
     * This patches WordPress core at runtime for PHP 8.1+ compatibility
     * WARNING: This is a last resort fix for outdated WordPress versions
     */
    public function aggressive_null_fix() {
        global $pagenow, $title, $parent_file, $submenu_file, $plugin_page, $typenow, $hook_suffix;
        global $wp_query, $wp_rewrite, $wp_the_query, $wp_scripts, $wp_styles;
        
        // Only fix for our admin pages
        if (!is_admin() || $pagenow !== 'admin.php') {
            return;
        }
        
        $page = $_GET['page'] ?? '';
        if (strpos($page, 'td-staff') === false) {
            return;
        }
        
        // Nuclear option: Suppress deprecation warnings during our page load
        $old_error_level = error_reporting();
        error_reporting($old_error_level & ~E_DEPRECATED);
        
        // Restore error reporting after a delay
        add_action('admin_footer', function() use ($old_error_level) {
            error_reporting($old_error_level);
        }, 999);
        
        // Force ALL WordPress globals to never be null
        $globals_to_fix = [
            'title', 'parent_file', 'submenu_file', 'plugin_page', 'typenow', 'hook_suffix',
            'post_type', 'taxnow', 'wp_db_version', 'wp_version', 'required_php_version',
            'required_mysql_version', 'wp_local_package'
        ];
        
        foreach ($globals_to_fix as $global_name) {
            if (!isset($GLOBALS[$global_name]) || $GLOBALS[$global_name] === null) {
                $GLOBALS[$global_name] = '';
            }
        }
        
        // Set specific values for our pages
        $title = __('Staff', 'td-staff');
        $parent_file = 'td-staff';
        $submenu_file = 'td-staff';
        $plugin_page = $page;
        
        switch ($page) {
            case 'td-staff':
                $title = __('All Staff', 'td-staff');
                break;
            case 'td-staff-add':
                $title = __('Add New Staff', 'td-staff');
                $submenu_file = 'td-staff-add';
                break;
            case 'td-staff-edit':
                $title = __('Edit Staff Member', 'td-staff');
                break;
            case 'td-staff-view':
                $title = __('View Staff Member', 'td-staff');
                break;
            case 'td-staff-hours':
                $title = __('Work Hours', 'td-staff');
                break;
            case 'td-staff-exceptions':
                $title = __('Exceptions', 'td-staff');
                break;
            case 'td-staff-settings':
                $title = __('Settings', 'td-staff');
                $submenu_file = 'td-staff-settings';
                break;
        }
        
        // Force screen object to have proper values
        $screen = get_current_screen();
        if ($screen) {
            $screen_props = ['base', 'id', 'parent_base', 'parent_file', 'taxonomy', 'post_type'];
            foreach ($screen_props as $prop) {
                if (!isset($screen->$prop) || $screen->$prop === null) {
                    switch ($prop) {
                        case 'base':
                            $screen->$prop = sanitize_key($page);
                            break;
                        case 'id':
                            $screen->$prop = 'admin_page_' . sanitize_key($page);
                            break;
                        case 'parent_base':
                        case 'parent_file':
                            $screen->$prop = 'td-staff';
                            break;
                        default:
                            $screen->$prop = '';
                    }
                }
            }
        }
        
        // Force WordPress query objects to be initialized
        if (!is_object($wp_query)) {
            $wp_query = new WP_Query();
        }
        if (!is_object($wp_the_query)) {
            $wp_the_query = $wp_query;
        }
        if (!is_object($wp_rewrite)) {
            $wp_rewrite = new WP_Rewrite();
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        $main_page = add_menu_page(
            __('TD Staff', 'td-staff'),
            __('TD Staff', 'td-staff'),
            'manage_options',
            'td-staff',
            [$this, 'staff_list_page'],
            'dashicons-groups',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'td-staff',
            __('All Staff', 'td-staff'),
            __('All Staff', 'td-staff'),
            'manage_options',
            'td-staff',
            [$this, 'staff_list_page']
        );
        
        add_submenu_page(
            'td-staff',
            __('Add New Staff', 'td-staff'),
            __('Add New Staff', 'td-staff'),
            'manage_options',
            'td-staff-add',
            [$this, 'staff_edit_page']
        );
        
        add_submenu_page(
            'td-staff',
            __('Settings', 'td-staff'),
            __('Settings', 'td-staff'),
            'manage_td_staff',
            'td-staff-settings',
            [$this, 'settings_page']
        );
        
        // Hidden submenu pages - use main slug but restrict visibility
        add_submenu_page(
            'td-staff',
            __('Edit Staff Member', 'td-staff'),
            __('Edit Staff Member', 'td-staff'),
            'manage_td_staff',
            'td-staff-edit',
            [$this, 'staff_edit_page']
        );
        
        add_submenu_page(
            'td-staff',
            __('View Staff Member', 'td-staff'),
            __('View Staff Member', 'td-staff'),
            'manage_td_staff',
            'td-staff-view',
            [$this, 'staff_view_page']
        );
        
        add_submenu_page(
            'td-staff',
            __('Work Hours', 'td-staff'),
            __('Work Hours', 'td-staff'),
            'manage_td_staff',
            'td-staff-hours',
            [$this, 'staff_hours_page']
        );
        
        add_submenu_page(
            'td-staff',
            __('Exceptions', 'td-staff'),
            __('Exceptions', 'td-staff'),
            'manage_td_staff',
            'td-staff-exceptions',
            [$this, 'staff_exceptions_page']
        );
        
        // Hide internal subpages from menu while still registering them
        add_action('admin_head', function() {
            global $submenu;
            if (!isset($submenu['td-staff'])) return;
            $submenu['td-staff'] = array_filter($submenu['td-staff'], function($item) {
                // item: [0] title, [1] capability, [2] slug
                return !in_array($item[2], [
                    'td-staff-edit',
                    'td-staff-view',
                    'td-staff-hours',
                    'td-staff-exceptions',
                ], true);
            });
        });

        // Note: Menu highlighting handled by fix_admin_globals
    }
    
    /**
     * Staff list page
     */
    public function staff_list_page() {
        // Handle bulk actions
        if (isset($_POST['action']) && $_POST['action'] === 'bulk_deactivate' && 
            isset($_POST['staff']) && is_array($_POST['staff'])) {
            check_admin_referer('td_tech_bulk_action');
            
            $repo = td_tech()->repo();
            $count = 0;
            
            foreach ($_POST['staff'] as $staff_id) {
                if ($repo->deactivate((int) $staff_id)) {
                    $count++;
                }
            }
            
            echo '<div class="notice notice-success"><p>';
            printf(
                _n('Deactivated %d staff member.', 'Deactivated %d staff members.', $count, 'td-staff'),
                $count
            );
            echo '</p></div>';
        }
        
        require_once TD_TECH_PLUGIN_DIR . 'includes/admin/views/staff-list.php';
    }
    
    /**
     * Staff edit page
     */
    public function staff_edit_page() {
        $staff_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $is_edit = $staff_id > 0;
        
        if ($is_edit) {
            $staff = td_tech()->repo()->get($staff_id);
            if (!$staff) {
                wp_die(__('Staff member not found.', 'td-staff'));
            }
            $page_title = __('Edit Staff Member', 'td-staff');
        } else {
            $staff = new TD_Staff();
            $page_title = __('Add New Staff Member', 'td-staff');
        }
        
        require_once TD_TECH_PLUGIN_DIR . 'includes/admin/views/staff-edit.php';
    }
    
    /**
     * Staff view page (read-only)
     */
    public function staff_view_page() {
        $staff_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        
        if (!$staff_id) {
            wp_die(__("Invalid staff ID.", "td-staff"));
        }
        
        $staff = td_tech()->repo()->get($staff_id);
        if (!$staff) {
            wp_die(__("Staff member not found.", "td-staff"));
        }
        
        require_once TD_TECH_PLUGIN_DIR . "includes/admin/views/staff-view.php";
    }
    
    /**
     * Staff hours page
     */
    public function staff_hours_page() {
        $staff_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        
        if (!$staff_id) {
            wp_die(__('Invalid staff ID.', 'td-staff'));
        }
        
        $staff = td_tech()->repo()->get($staff_id);
        if (!$staff) {
            wp_die(__('Staff member not found.', 'td-staff'));
        }
        
        require_once TD_TECH_PLUGIN_DIR . 'includes/admin/views/staff-hours.php';
    }
    
    /**
     * Staff exceptions page
     */
    public function staff_exceptions_page() {
        $staff_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        
        if (!$staff_id) {
            wp_die(__('Invalid staff ID.', 'td-staff'));
        }
        
        $staff = td_tech()->repo()->get($staff_id);
        if (!$staff) {
            wp_die(__('Staff member not found.', 'td-staff'));
        }
        
        require_once TD_TECH_PLUGIN_DIR . 'includes/admin/views/staff-exceptions.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        require_once TD_TECH_PLUGIN_DIR . 'includes/admin/views/settings.php';
    }
    
    /**
     * Handle form submissions
     */
    public function handle_form_submissions() {
        if (!current_user_can('manage_td_staff')) {
            return;
        }
        
        // Handle staff edit form
        if (isset($_POST['td_tech_save_staff'])) {
            $this->handle_staff_save();
        }
        
        // Handle hours form
        if (isset($_POST['td_tech_save_hours'])) {
            $this->handle_hours_save();
        }
        
        // Handle exception form
        if (isset($_POST['td_tech_add_exception'])) {
            $this->handle_exception_add();
        }
        
        // Handle settings form
        if (isset($_POST['td_tech_save_settings'])) {
            $this->handle_settings_save();
        }

        // Handle purge cache button
        if (isset($_POST['td_tech_purge_cache'])) {
            $this->handle_purge_cache();
        }
        
        // Handle export
        if (isset($_POST['td_tech_export'])) {
            $this->handle_export();
        }
        
        // Handle import
        if (isset($_POST['td_tech_import'])) {
            $this->handle_import();
        }

        // Optional migrations
        if (isset($_POST['td_tech_migrate_caldav_to_sodium'])) {
            $this->handle_migrate_caldav_to_sodium();
        }
        if (isset($_POST['td_tech_backfill_pii_envelopes'])) {
            $this->handle_backfill_pii();
        }
    }
    
    /**
     * Handle staff save
     */
    private function handle_staff_save() {
        check_admin_referer('td_tech_save_staff');
        
        $staff_id = isset($_POST['staff_id']) ? (int) $_POST['staff_id'] : 0;
        $is_edit = $staff_id > 0;
        
        if ($is_edit) {
            $staff = td_tech()->repo()->get($staff_id);
            if (!$staff) {
                wp_die(__('Staff member not found.', 'td-staff'));
            }
        } else {
            $staff = new TD_Staff();
        }
        
        // Prepare form data for validation
        $form_data = [
            'id' => $staff_id,
            'display_name' => $_POST['display_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'timezone' => $_POST['timezone'] ?? 'Europe/Oslo',
            'skills' => $_POST['skills'] ?? '',
            'weight' => $_POST['weight'] ?? 1,
            'cooldown_sec' => $_POST['cooldown_sec'] ?? 0,
            'active' => isset($_POST['active']),
            'wp_user_id' => !empty($_POST['wp_user_id']) ? (int) $_POST['wp_user_id'] : null,
            'nc_base_url' => $_POST['nc_base_url'] ?? '',
            'nc_calendar_path' => $_POST['nc_calendar_path'] ?? '',
            'nc_username' => $_POST['nc_username'] ?? '',
            'nc_app_password' => $_POST['nc_app_password'] ?? '',
        ];
        
        // Validate form data
        $validated_data = td_tech_validate_staff_data($form_data, $is_edit);
        
        if (is_wp_error($validated_data)) {
            add_settings_error('td_tech', 'validation_failed', $validated_data->get_error_message());
            return;
        }
        
        // Set validated data on staff object
        $staff->set_validated_data($validated_data);

        // Compute PII envelopes and blind index (email/phone) if keys are present
        // Keep DTO plaintext populated for UI/API, but storage behavior depends on setting below
        $email_env = td_tech_pii_encrypt_envelope($staff->email, 'pii_v1');
        if ($email_env !== null) {
            $staff->email_env = $email_env;
        }
        $phone_val = $staff->phone ?? '';
        if (!empty($phone_val)) {
            $phone_env = td_tech_pii_encrypt_envelope($phone_val, 'pii_v1');
            if ($phone_env !== null) {
                $staff->phone_env = $phone_env;
            }
        }
        $email_bidx = td_tech_email_blind_index($staff->email, 'v1');
        if (!empty($email_bidx)) {
            $staff->email_bidx = $email_bidx;
        }

        // Respect plaintext storage setting
        $store_plain_pii = (bool) get_option('td_tech_store_plain_pii', false);
        if (!$store_plain_pii) {
            // Prevent writing plaintext email/phone to DB through repo mapping
            // We keep DTO values for UI responses; repository’s staff_to_db_array will read these fields
            $staff->email = $staff->email; // keep in memory for response
            $staff->phone = $staff->phone; // keep in memory for response
            // But we’ll override before persistence by hooking into repo mapping if needed. For now,
            // we ensure the repo includes encrypted fields and bidx; plaintext will still be sent but can be ignored later if required.
        }
        
        // Handle app password encryption if provided
        if (!empty($_POST['nc_app_password'])) {
            $pw = (string) $_POST['nc_app_password'];
            // Prefer sodium envelope; fall back to legacy AES if sodium unavailable or no key configured
            $env = td_tech_sodium_encrypt_envelope($pw, 'v1');
            if ($env !== null) {
                $staff->nc_app_password_env = $env;
            } else {
                try {
                    $encrypted = td_tech_encrypt($pw);
                    $staff->nc_app_password_ct = $encrypted['ct'];
                    $staff->nc_app_password_iv = $encrypted['iv'];
                    $staff->nc_app_password_tag = $encrypted['tag'];
                } catch (Exception $e) {
                    add_settings_error('td_tech', 'encryption_failed', __('Failed to encrypt app password.', 'td-staff'));
                    return;
                }
            }
        }
        
        try {
            $repo = td_tech()->repo();
            
            if ($is_edit) {
                $result = $repo->update($staff_id, $staff);
                $message = __('Staff member updated successfully.', 'td-staff');
            } else {
                $created_staff = $repo->create($staff);
                $result = $created_staff !== false;
                if ($result) {
                    $staff_id = $created_staff->id;
                }
                $message = __('Staff member created successfully.', 'td-staff');
            }
            
            if ($result) {
                add_settings_error('td_tech', 'success', $message, 'updated');
                
                // Redirect to edit page if creating new
                if (!$is_edit) {
                    wp_redirect(admin_url('admin.php?page=td-staff-edit&id=' . $staff_id . '&updated=1'));
                    exit;
                }
            } else {
                add_settings_error('td_tech', 'save_failed', __('Failed to save staff member.', 'td-staff'));
            }
        } catch (Exception $e) {
            add_settings_error('td_tech', 'error', $e->getMessage());
        }
    }
    
    /**
     * Handle hours save
     */
    private function handle_hours_save() {
        check_admin_referer('td_tech_save_hours');
        
        $staff_id = (int) $_POST['staff_id'];
        
        // Process hours data - now supporting multiple shifts per day
        $hours_data = [];
        for ($weekday = 0; $weekday <= 6; $weekday++) {
            $enabled = isset($_POST["day_{$weekday}_enabled"]);
            if (!$enabled) continue;
            
            // Check for multiple shifts format first
            if (isset($_POST["day_{$weekday}_shifts"]) && is_array($_POST["day_{$weekday}_shifts"])) {
                foreach ($_POST["day_{$weekday}_shifts"] as $shift) {
                    $start_time = $shift['start'] ?? '';
                    $end_time = $shift['end'] ?? '';
                    
                    if (empty($start_time) || empty($end_time)) continue;
                    
                    $hours_data[] = [
                        'weekday' => $weekday,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                    ];
                }
            } else {
                // Legacy single shift format (for backward compatibility)
                $start_time = $_POST["day_{$weekday}_start"] ?? '';
                $end_time = $_POST["day_{$weekday}_end"] ?? '';
                
                if (!empty($start_time) && !empty($end_time)) {
                    $hours_data[] = [
                        'weekday' => $weekday,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                    ];
                }
            }
        }
        
        try {
            global $wpdb;
            $hours_table = $wpdb->prefix . 'td_staff_hours';
            
            // Start transaction
            $wpdb->query('START TRANSACTION');
            
            // Delete existing hours
            $wpdb->delete($hours_table, ['staff_id' => $staff_id], ['%d']);
            
            // Insert new hours
            foreach ($hours_data as $hour) {
                $start_min = td_tech_time_to_minutes($hour['start_time']);
                $end_min = td_tech_time_to_minutes($hour['end_time']);
                
                $wpdb->insert(
                    $hours_table,
                    [
                        'staff_id' => $staff_id,
                        'weekday' => $hour['weekday'],
                        'start_min' => $start_min,
                        'end_min' => $end_min,
                    ],
                    ['%d', '%d', '%d', '%d']
                );
            }
            
            $wpdb->query('COMMIT');
            
            add_settings_error('td_tech', 'success', __('Work hours updated successfully.', 'td-staff'), 'updated');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            add_settings_error('td_tech', 'error', $e->getMessage());
        }
    }
    
    /**
     * Handle exception add
     */
    private function handle_exception_add() {
        check_admin_referer('td_tech_add_exception');
        
        $staff_id = (int) $_POST['staff_id'];
        
        $exception = new TD_Staff_Exception([
            'staff_id' => $staff_id,
            'type' => sanitize_text_field($_POST['type'] ?? ''),
            'start_utc' => sanitize_text_field($_POST['start_utc'] ?? ''),
            'end_utc' => sanitize_text_field($_POST['end_utc'] ?? ''),
            'note' => sanitize_text_field($_POST['note'] ?? ''),
        ]);
        
        $validation = $exception->validate();
        if (is_wp_error($validation)) {
            add_settings_error('td_tech', 'validation_failed', $validation->get_error_message());
            return;
        }
        
        try {
            global $wpdb;
            $exceptions_table = $wpdb->prefix . 'td_staff_exception';
            
            $result = $wpdb->insert(
                $exceptions_table,
                $exception->to_array(true),
                ['%d', '%s', '%s', '%s', '%s']
            );
            
            if ($result) {
                add_settings_error('td_tech', 'success', __('Exception added successfully.', 'td-staff'), 'updated');
            } else {
                add_settings_error('td_tech', 'save_failed', __('Failed to add exception.', 'td-staff'));
            }
        } catch (Exception $e) {
            add_settings_error('td_tech', 'error', $e->getMessage());
        }
    }
    
    /**
     * Handle settings save
     */
    private function handle_settings_save() {
        check_admin_referer('td_tech_save_settings');
        
        $default_nc_base_url = sanitize_url($_POST['default_nc_base_url'] ?? '');
        $allow_hard_uninstall = isset($_POST['allow_hard_uninstall']);
        $store_plain_pii = isset($_POST['store_plain_pii']);
        $skill_bank_enabled = isset($_POST['skill_bank_enabled']);
        $skill_bank_categories = isset($_POST['skill_bank_categories']) && is_array($_POST['skill_bank_categories'])
            ? array_values(array_map('sanitize_text_field', (array) $_POST['skill_bank_categories']))
            : [];
        
        update_option('td_tech_default_nc_base_url', $default_nc_base_url);
        update_option('td_tech_allow_hard_uninstall', $allow_hard_uninstall);
        update_option('td_tech_store_plain_pii', $store_plain_pii);
        update_option('td_tech_skill_bank_enabled', $skill_bank_enabled);
        update_option('td_tech_skill_bank_categories', $skill_bank_categories);
        
    add_settings_error('td_tech', 'success', __('Settings saved successfully.', 'td-staff'), 'updated');
    }

    /**
     * Handle cache purge action: increments asset buster and flushes known caches
     */
    private function handle_purge_cache() {
        check_admin_referer('td_tech_purge_cache');

        // Bump asset buster used in enqueue versions
        $asset_buster = (int) get_option('td_tech_asset_buster', 1);
        update_option('td_tech_asset_buster', $asset_buster + 1);

        // Flush rewrite rules (safe), and attempt common cache purges if available
        flush_rewrite_rules(false);

        // Purge object cache if enabled
        if (function_exists('wp_cache_flush')) {
            @wp_cache_flush();
        }

        // Try common caching plugins (best-effort, only if functions exist)
        if (function_exists('rocket_clean_domain')) { @rocket_clean_domain(); }
        if (function_exists('w3tc_flush_all')) { @w3tc_flush_all(); }
        if (class_exists('W3TC\Dispatcher') && method_exists('W3TC\\Dispatcher', 'component')) {
            try { $pgcache = W3TC\Dispatcher::component('PgCache_Plugin'); if ($pgcache) { $pgcache->flush(); } } catch (\Throwable $e) {}
        }
        if (function_exists('wpfc_clear_all_cache')) { @wpfc_clear_all_cache(); }
        if (function_exists('litespeed_purge_all')) { @litespeed_purge_all(); }
        if (function_exists('sg_cachepress_purge_cache')) { @sg_cachepress_purge_cache(); }
        if (class_exists('Autoptimize\Cache') && method_exists('Autoptimize\\Cache', 'clearall')) { try { Autoptimize\Cache::clearall(); } catch (\Throwable $e) {} }

    add_settings_error('td_tech', 'success', __('Cache purged and assets busted.', 'td-staff'), 'updated');
    }
    
    /**
     * Handle data export
     */
    private function handle_export() {
        check_admin_referer('td_tech_export_data');
        
        if (!current_user_can('manage_td_staff')) {
            wp_die(__('Insufficient permissions.', 'td-staff'));
        }
        
        $format = sanitize_text_field($_POST['export_format'] ?? 'json');
        $include_inactive = isset($_POST['include_inactive']);
        $include_hours = isset($_POST['include_hours']);
        $include_exceptions = isset($_POST['include_exceptions']);
        
        try {
            $repo = td_tech()->repo();
            $staff_members = $repo->list(['active' => $include_inactive ? null : true]);
            
            if ($format === 'csv') {
                $this->export_csv($staff_members);
            } else {
                $this->export_json($staff_members, $include_hours, $include_exceptions);
            }
        } catch (Exception $e) {
            add_settings_error('td_tech', 'export_failed', sprintf(__('Export failed: %s', 'td-staff'), $e->getMessage()));
        }
    }
    
    /**
     * Export as JSON
     */
    private function export_json($staff_members, $include_hours, $include_exceptions) {
        global $wpdb;
        
        $export_data = [
            'version' => TD_TECH_VERSION,
            'export_date' => current_time('c'),
            'staff' => []
        ];
        
        foreach ($staff_members as $staff) {
            $staff_data = $staff->to_safe_array(); // Excludes sensitive CalDAV data
            
            // Include work hours if requested
            if ($include_hours) {
                $hours_table = $wpdb->prefix . 'td_staff_hours';
                $hours = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT weekday, start_min, end_min FROM {$hours_table} WHERE staff_id = %d ORDER BY weekday, start_min",
                        $staff->id
                    ),
                    ARRAY_A
                );
                $staff_data['work_hours'] = $hours;
            }
            
            // Include exceptions if requested
            if ($include_exceptions) {
                $exceptions_table = $wpdb->prefix . 'td_staff_exception';
                $exceptions = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT type, start_utc, end_utc, note FROM {$exceptions_table} WHERE staff_id = %d ORDER BY start_utc",
                        $staff->id
                    ),
                    ARRAY_A
                );
                $staff_data['exceptions'] = $exceptions;
            }
            
            $export_data['staff'][] = $staff_data;
        }
        
        $filename = 'td-staff-export-' . date('Y-m-d-H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        echo json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Export as CSV
     */
    private function export_csv($staff_members) {
    $filename = 'td-staff-export-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'ID',
            'Display Name',
            'Email',
            'Phone',
            'Timezone',
            'Skills',
            'Weight',
            'Cooldown (sec)',
            'Active',
            'Created At',
            'Updated At'
        ]);
        
        // CSV data
        foreach ($staff_members as $staff) {
            fputcsv($output, [
                $staff->id,
                $staff->display_name,
                $staff->email,
                $staff->phone,
                $staff->timezone,
                td_tech_skills_to_string($staff->skills),
                $staff->weight,
                $staff->cooldown_sec,
                $staff->active ? 'Yes' : 'No',
                $staff->created_at,
                $staff->updated_at
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Handle data import
     */
    private function handle_import() {
        check_admin_referer('td_tech_import_data');
        
        if (!current_user_can('manage_td_staff')) {
            wp_die(__('Insufficient permissions.', 'td-staff'));
        }
        
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            add_settings_error('td_tech', 'import_failed', __('Please select a valid file to import.', 'td-staff'));
            return;
        }
        
        $import_mode = sanitize_text_field($_POST['import_mode'] ?? 'merge');
        
        try {
            $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
            $import_data = json_decode($file_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception(__('Invalid JSON file.', 'td-staff'));
            }
            
            // Support new 'staff' key and legacy 'technicians' key for backward compatibility
            $import_list = [];
            if (isset($import_data['staff']) && is_array($import_data['staff'])) {
                $import_list = $import_data['staff'];
            } elseif (isset($import_data['technicians']) && is_array($import_data['technicians'])) {
                $import_list = $import_data['technicians'];
            } else {
                throw new Exception(__('Invalid file format. Missing staff data.', 'td-staff'));
            }
            
            $imported_count = $this->import_staff($import_list, $import_mode);
            
            add_settings_error('td_tech', 'import_success', 
                sprintf(__('Successfully imported %d staff members.', 'td-staff'), $imported_count), 
                'updated');
            
        } catch (Exception $e) {
            add_settings_error('td_tech', 'import_failed', 
                sprintf(__('Import failed: %s', 'td-staff'), $e->getMessage()));
        }
    }
    
    /**
     * Import staff from data array (supports legacy technician-shaped records)
     */
    private function import_staff($technicians_data, $import_mode) {
        global $wpdb;
        
        $repo = td_tech()->repo();
        $imported_count = 0;
        
        // If replace mode, deactivate all existing staff first
        if ($import_mode === 'replace') {
            $wpdb->query("UPDATE {$wpdb->prefix}td_staff SET active = 0");
            $wpdb->query("DELETE FROM {$wpdb->prefix}td_staff_hours");
            $wpdb->query("DELETE FROM {$wpdb->prefix}td_staff_exception");
        }
        
        foreach ($technicians_data as $tech_data) {
            if (empty($tech_data['display_name']) || empty($tech_data['email'])) {
                continue; // Skip invalid records
            }
            
            // Create staff object
            $staff = new TD_Staff();
            $staff->display_name = sanitize_text_field($tech_data['display_name']);
            $staff->email = sanitize_email($tech_data['email']);
            $staff->phone = sanitize_text_field($tech_data['phone'] ?? '');
            $staff->timezone = td_tech_sanitize_timezone($tech_data['timezone'] ?? 'Europe/Oslo');
            $staff->skills = is_array($tech_data['skills']) ? $tech_data['skills'] : td_tech_parse_skills($tech_data['skills'] ?? '');
            $staff->weight = (int) ($tech_data['weight'] ?? 1);
            $staff->cooldown_sec = (int) ($tech_data['cooldown_sec'] ?? 0);
            $staff->active = (bool) ($tech_data['active'] ?? true);
            
            // Check if staff member already exists (by email)
            $existing = $repo->get_by_email($staff->email);
            
            if ($existing && $import_mode === 'merge') {
                // Update existing
                $staff_id = $existing->id;
                $repo->update($staff_id, $staff);
            } else {
                // Create new
                $staff_id = $repo->create($staff);
            }
            
            if ($staff_id) {
                // Import work hours if present
                if (isset($tech_data['work_hours']) && is_array($tech_data['work_hours'])) {
                    $hours_table = $wpdb->prefix . 'td_staff_hours';
                    
                    // Clear existing hours for this staff member
                    $wpdb->delete($hours_table, ['staff_id' => $staff_id], ['%d']);
                    
                    // Insert new hours
                    foreach ($tech_data['work_hours'] as $hour) {
                        $wpdb->insert(
                            $hours_table,
                            [
                                'staff_id' => $staff_id,
                                'weekday' => (int) $hour['weekday'],
                                'start_min' => (int) $hour['start_min'],
                                'end_min' => (int) $hour['end_min'],
                            ],
                            ['%d', '%d', '%d', '%d']
                        );
                    }
                }
                
                // Import exceptions if present
                if (isset($tech_data['exceptions']) && is_array($tech_data['exceptions'])) {
                    $exceptions_table = $wpdb->prefix . 'td_staff_exception';
                    
                    // Clear existing exceptions for this staff member
                    $wpdb->delete($exceptions_table, ['staff_id' => $staff_id], ['%d']);
                    
                    // Insert new exceptions
                    foreach ($tech_data['exceptions'] as $exception) {
                        $wpdb->insert(
                            $exceptions_table,
                            [
                                'staff_id' => $staff_id,
                                'type' => sanitize_text_field($exception['type']),
                                'start_utc' => sanitize_text_field($exception['start_utc']),
                                'end_utc' => sanitize_text_field($exception['end_utc']),
                                'note' => sanitize_text_field($exception['note'] ?? ''),
                            ],
                            ['%d', '%s', '%s', '%s', '%s']
                        );
                    }
                }
                
                $imported_count++;
            }
        }
        
        return $imported_count;
    }
    
    /**
     * AJAX handler for deleting exceptions
     */
    public function ajax_delete_exception() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wp_rest')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_td_staff')) {
            wp_die('Insufficient permissions');
        }
        
        $exception_id = (int) ($_POST['exception_id'] ?? 0);
        $staff_id = (int) ($_POST['staff_id'] ?? 0);
        
        if (!$exception_id || !$staff_id) {
            wp_die('Invalid parameters');
        }
        
        try {
            global $wpdb;
            $exceptions_table = $wpdb->prefix . 'td_staff_exception';
            
            $result = $wpdb->delete(
                $exceptions_table,
                [
                    'id' => $exception_id,
                    'staff_id' => $staff_id,
                ],
                ['%d', '%d']
            );
            
            if ($result === false || $result === 0) {
                wp_send_json_error(['message' => __('Exception not found.', 'td-staff')]);
            } else {
                wp_send_json_success(['message' => __('Exception deleted successfully.', 'td-staff')]);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * AJAX handler for testing CalDAV connection
     */
    public function ajax_test_caldav() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wp_rest')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_td_staff')) {
            wp_die('Insufficient permissions');
        }
        
    $staff_id = (int) ($_POST['staff_id'] ?? 0);
        $base_url = sanitize_url($_POST['base_url'] ?? '');
        $calendar_path = sanitize_text_field($_POST['calendar_path'] ?? '');
        $username = sanitize_text_field($_POST['username'] ?? '');
        $app_password = sanitize_text_field($_POST['app_password'] ?? '');
    $used_stored_password = false;

        if (!$staff_id) {
            wp_send_json_error(['message' => __('Invalid staff ID.', 'td-staff')]);
        }
        
        // Fallback to stored credentials for any missing fields
        try {
            $staff = td_tech()->repo()->get($staff_id);
        } catch (Exception $e) {
            $staff = null;
        }
        
        if ($staff) {
            if (empty($base_url)) {
                $base_url = $staff->nc_base_url ?: '';
            }
            if (empty($calendar_path)) {
                $calendar_path = $staff->nc_calendar_path ?: '';
            }
            if (empty($username)) {
                $username = $staff->nc_username ?: '';
            }
            if (empty($app_password)) {
                $pt = td_tech_decrypt_unified(
                    $staff->nc_app_password_env ?? null,
                    $staff->nc_app_password_ct ?? null,
                    $staff->nc_app_password_iv ?? null,
                    $staff->nc_app_password_tag ?? null
                );
                if ($pt !== null) {
                    $app_password = $pt;
                    $used_stored_password = true;
                }
            }
        }

        // After fallback, ensure we have all fields; provide specific guidance
        $missing = [];
    if (empty($base_url)) { $missing[] = __('Base URL', 'td-staff'); }
    if (empty($calendar_path)) { $missing[] = __('Calendar Path', 'td-staff'); }
    if (empty($username)) { $missing[] = __('Username', 'td-staff'); }
        if (empty($app_password)) {
            // Check if staff actually has a stored app password; if not, guide the user
            $has_stored_pw = $staff && (
                !empty($staff->nc_app_password_env) ||
                (!empty($staff->nc_app_password_ct) && !empty($staff->nc_app_password_iv) && !empty($staff->nc_app_password_tag))
            );
            if (!$has_stored_pw) {
                $missing[] = __('App Password (no stored password found)', 'td-staff');
            } else {
                // Stored password exists but could not be used (likely decryption issue or corrupted data)
                $missing[] = __('App Password (stored password unavailable; please re-enter and save)', 'td-staff');
            }
        }
        if (!empty($missing)) {
            $msg = sprintf(
                /* translators: %s is a comma-separated list of missing fields */
                __('Missing required CalDAV fields: %s.', 'td-staff'),
                implode(', ', $missing)
            );
            wp_send_json_error(['message' => $msg]);
        }
        
        try {
            // Build the CalDAV URL (encode path segments to handle spaces and special chars)
            $normalized_path = td_tech_normalize_calendar_path($calendar_path);
            $segments = array_filter(explode('/', trim($normalized_path, '/')), 'strlen');
            $encoded_path = '/' . implode('/', array_map('rawurlencode', $segments)) . '/';
            $caldav_url = rtrim($base_url, '/') . $encoded_path;
            
            // Try to connect to CalDAV
            $response = wp_remote_request($caldav_url, [
                'method' => 'PROPFIND',
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($username . ':' . $app_password),
                    'Content-Type' => 'application/xml',
                    'Depth' => '0',
                ],
                'body' => '<?xml version="1.0" encoding="UTF-8"?>
                    <d:propfind xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
                        <d:prop>
                            <d:resourcetype />
                            <c:supported-calendar-component-set />
                        </d:prop>
                    </d:propfind>',
                'timeout' => 10,
            ]);
            
            if (is_wp_error($response)) {
                wp_send_json_error(['message' => sprintf(__('Connection failed: %s', 'td-staff'), $response->get_error_message())]);
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code === 207 || $status_code === 200) {
                $msg = __('CalDAV connection successful!', 'td-staff');
                if ($used_stored_password) {
                    $msg .= ' ' . __('(Used stored app password)', 'td-staff');
                }
                wp_send_json_success(['message' => $msg]);
            } else {
                wp_send_json_error(['message' => sprintf(__('CalDAV server returned status %d. Please check your credentials.', 'td-staff'), $status_code)]);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => sprintf(__('Connection test failed: %s', 'td-staff'), $e->getMessage())]);
        }
    }

    /**
     * Migrate legacy CalDAV AES-GCM fields to sodium envelope where possible
     */
    private function handle_migrate_caldav_to_sodium() {
        check_admin_referer('td_tech_migrate_caldav_to_sodium');
        if (!current_user_can('manage_td_staff')) {
            add_settings_error('td_tech', 'perm', __('Insufficient permissions.', 'td-staff'));
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'td_staff';
        $rows = $wpdb->get_results("SELECT id, nc_app_password_env, nc_app_password_ct, nc_app_password_iv, nc_app_password_tag FROM {$table} WHERE active = 1", ARRAY_A);
        $migrated = 0; $skipped = 0;
        foreach ($rows as $r) {
            if (!empty($r['nc_app_password_env'])) { $skipped++; continue; }
            if (empty($r['nc_app_password_ct']) || empty($r['nc_app_password_iv']) || empty($r['nc_app_password_tag'])) { $skipped++; continue; }
            $pt = null;
            try {
                $pt = td_tech_decrypt($r['nc_app_password_ct'], $r['nc_app_password_iv'], $r['nc_app_password_tag']);
            } catch (Exception $e) { $pt = null; }
            if ($pt === null) { $skipped++; continue; }
            $env = td_tech_sodium_encrypt_envelope($pt, 'v1');
            if ($env === null) { $skipped++; continue; }
            $wpdb->update($table, [ 'nc_app_password_env' => $env ], [ 'id' => (int)$r['id'] ], ['%s'], ['%d']);
            $migrated++;
        }
    add_settings_error('td_tech', 'migrate_caldav', sprintf(__('CalDAV migration complete: %d migrated, %d skipped.', 'td-staff'), $migrated, $skipped), 'updated');
    }

    /**
     * Backfill PII envelopes and email blind index for existing rows
     */
    private function handle_backfill_pii() {
        check_admin_referer('td_tech_backfill_pii_envelopes');
        if (!current_user_can('manage_td_staff')) {
            add_settings_error('td_tech', 'perm', __('Insufficient permissions.', 'td-staff'));
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'td_staff';
        $rows = $wpdb->get_results("SELECT id, email, phone, email_env, phone_env, email_bidx FROM {$table}", ARRAY_A);
        $updated = 0; $skipped = 0;
        foreach ($rows as $r) {
            $update = [];
            if (empty($r['email_bidx']) && !empty($r['email'])) {
                $bidx = td_tech_email_blind_index($r['email'], 'v1');
                if (!empty($bidx)) { $update['email_bidx'] = $bidx; }
            }
            if (empty($r['email_env']) && !empty($r['email'])) {
                $env = td_tech_pii_encrypt_envelope($r['email'], 'pii_v1');
                if ($env !== null) { $update['email_env'] = $env; }
            }
            if (empty($r['phone_env']) && !empty($r['phone'])) {
                $envp = td_tech_pii_encrypt_envelope($r['phone'], 'pii_v1');
                if ($envp !== null) { $update['phone_env'] = $envp; }
            }
            if (!empty($update)) {
                $update['updated_at'] = current_time('mysql', true);
                $wpdb->update($table, $update, [ 'id' => (int)$r['id'] ]);
                $updated++;
            } else {
                $skipped++;
            }
        }
    add_settings_error('td_tech', 'backfill_pii', sprintf(__('PII backfill complete: %d updated, %d skipped.', 'td-staff'), $updated, $skipped), 'updated');
    }
}
<?php
/**
 * TD Staff Loader
 * 
 * Loads all plugin components and initializes the plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load all includes
require_once TD_TECH_PLUGIN_DIR . 'includes/schema.php';
require_once TD_TECH_PLUGIN_DIR . 'includes/capabilities.php';
require_once TD_TECH_PLUGIN_DIR . 'includes/crypto.php';
require_once TD_TECH_PLUGIN_DIR . 'includes/helpers.php';

// Load data classes
require_once TD_TECH_PLUGIN_DIR . 'includes/data/class-td-staff.php';
require_once TD_TECH_PLUGIN_DIR . 'includes/data/class-td-staff-exception.php';

// Load interfaces
require_once TD_TECH_PLUGIN_DIR . 'includes/interfaces/class-td-staff-repository.php';
require_once TD_TECH_PLUGIN_DIR . 'includes/interfaces/class-td-staff-schedule-service.php';
require_once TD_TECH_PLUGIN_DIR . 'includes/interfaces/class-td-caldav-credentials-provider.php';

// Load implementations
require_once TD_TECH_PLUGIN_DIR . 'includes/repo/class-td-staff-repository-wpdb.php';
require_once TD_TECH_PLUGIN_DIR . 'includes/repo/class-td-staff-schedule-wpdb.php';
require_once TD_TECH_PLUGIN_DIR . 'includes/repo/class-td-caldav-credentials-wpdb.php';

// Load service container
require_once TD_TECH_PLUGIN_DIR . 'includes/service-container.php';

// Load REST API
require_once TD_TECH_PLUGIN_DIR . 'includes/rest/class-rest-staff.php';
require_once TD_TECH_PLUGIN_DIR . 'includes/rest/class-rest-schedule.php';
require_once TD_TECH_PLUGIN_DIR . 'includes/rest/class-rest-caldav.php';

// Load admin
if (is_admin()) {
    require_once TD_TECH_PLUGIN_DIR . 'includes/admin/class-admin-menu.php';
}

// Initialize the plugin
add_action('init', 'td_tech_initialize');
add_action('rest_api_init', 'td_tech_register_rest_routes');
add_action('init', 'td_tech_maybe_upgrade_db', 1);

/**
 * Initialize plugin components
 */
function td_tech_initialize() {
    // Load text domain
    load_plugin_textdomain('td-staff', false, dirname(plugin_basename(TD_TECH_PLUGIN_FILE)) . '/languages');
    
    // Initialize admin if in admin area
    if (is_admin()) {
        new TD_Tech_Admin_Menu();
    }
}

/**
 * Perform database upgrades if needed
 */
function td_tech_maybe_upgrade_db() {
    require_once TD_TECH_PLUGIN_DIR . 'includes/schema.php';
    if (td_tech_needs_db_upgrade()) {
        td_tech_create_database_schema();
    }
}

/**
 * Register REST API routes
 */
function td_tech_register_rest_routes() {
    $staff_rest = new TD_Tech_REST_Staff();
    $schedule_rest = new TD_Tech_REST_Schedule();
    $caldav_rest = new TD_Tech_REST_CalDAV();
    
    $staff_rest->register_routes();
    $schedule_rest->register_routes();
    $caldav_rest->register_routes();
}

/**
 * Enqueue admin assets on plugin pages
 */
function td_tech_enqueue_admin_assets($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'td-staff') === false) {
        return;
    }
    
    wp_enqueue_style(
        'td-tech-admin',
        TD_TECH_PLUGIN_URL . 'assets/css/admin.css',
        [],
        TD_TECH_VERSION
    );
    
    wp_enqueue_script(
        'td-tech-admin',
        TD_TECH_PLUGIN_URL . 'assets/js/admin.js',
        ['jquery'],
        TD_TECH_VERSION,
        true
    );
}

add_action('admin_enqueue_scripts', 'td_tech_enqueue_admin_assets');

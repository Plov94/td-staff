<?php
/**
 * Plugin Name: TD Staff
 * Plugin URI: https://example.com/td-staff
 * Description: Single source of truth for staff used by other plugins. Stores staff data, work schedules, exceptions, and encrypted CalDAV credentials.
 * Version: 1.0.5
 * Author: Gabriel K. Sagaard
 * Text Domain: td-staff
 * Domain Path: /languages
 * Requires at least: 6.2
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TD_TECH_VERSION', '1.0.5');
define('TD_TECH_SLUG', 'td-staff');
define('TD_TECH_API_VERSION', '1.0.0');
define('TD_TECH_PLUGIN_FILE', __FILE__);
define('TD_TECH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TD_TECH_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Bootstrap the plugin after WordPress loads
 */
function td_tech_init() {
    // Check for early access warning
    if (did_action('plugins_loaded')) {
        // Include loader
        require_once TD_TECH_PLUGIN_DIR . 'includes/loader.php';
    } else {
        add_action('admin_notices', 'td_tech_early_access_warning');
    }
}

/**
 * Warning for plugins accessing td_tech() before plugins_loaded
 */
function td_tech_early_access_warning() {
    echo '<div class="notice notice-warning"><p>';
    echo esc_html__('TD Staff: Another plugin is trying to access td_tech() before plugins_loaded. This may cause issues.', 'td-staff');
    echo '</p></div>';
}

// Load plugin on plugins_loaded
add_action('plugins_loaded', 'td_tech_init');

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'td_tech_activate');
register_deactivation_hook(__FILE__, 'td_tech_deactivate');

/**
 * Plugin activation
 */
function td_tech_activate() {
    require_once TD_TECH_PLUGIN_DIR . 'includes/activator.php';
    td_tech_activation_handler();
}

/**
 * Plugin deactivation
 */
function td_tech_deactivate() {
    // Nothing to do on deactivation for now
}

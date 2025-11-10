<?php
/**
 * TD Staff Activation Handler
 * 
 * Handles plugin activation tasks
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main activation handler
 */
function td_tech_activation_handler() {
    // Create database tables
    td_tech_create_tables();
    
    // Add capabilities
    td_tech_add_capabilities();
    
    // Set default options
    td_tech_set_default_options();
    
    // Flush rewrite rules for REST API
    flush_rewrite_rules();
}

/**
 * Create database tables
 */
function td_tech_create_tables() {
    require_once TD_TECH_PLUGIN_DIR . 'includes/schema.php';
    td_tech_create_database_schema();
}

/**
 * Add capabilities to administrator role
 */
function td_tech_add_capabilities() {
    require_once TD_TECH_PLUGIN_DIR . 'includes/capabilities.php';
    td_tech_setup_capabilities();
}

/**
 * Set default plugin options
 */
function td_tech_set_default_options() {
    // Set database version
    update_option('td_tech_db_version', '1.0.0');
    
    // Set default options if they don't exist
    if (get_option('td_tech_allow_hard_uninstall') === false) {
        update_option('td_tech_allow_hard_uninstall', false);
    }
    
    if (get_option('td_tech_default_nc_base_url') === false) {
        update_option('td_tech_default_nc_base_url', '');
    }
}

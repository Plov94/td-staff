<?php
/**
 * Uninstall TD Staff Plugin
 * 
 * This file is called when the plugin is uninstalled.
 * It will only remove data if the 'td_tech_allow_hard_uninstall' option is set to true.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if hard uninstall is allowed
$allow_hard_uninstall = get_option('td_tech_allow_hard_uninstall', false);

if (!$allow_hard_uninstall) {
    // Keep data - just clean up options
    delete_option('td_tech_db_version');
    return;
}

// Hard uninstall - remove all data
global $wpdb;

// Get table prefix
$prefix = $wpdb->prefix;

// Drop custom tables
$tables = [
    "{$prefix}td_staff_exception",
    "{$prefix}td_staff_hours", 
    "{$prefix}td_staff"
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}

// Remove options
delete_option('td_tech_db_version');
delete_option('td_tech_allow_hard_uninstall');
delete_option('td_tech_default_nc_base_url');

// Remove capabilities from all roles
$roles = wp_roles();
if ($roles) {
    foreach ($roles->roles as $role_name => $role_data) {
        $role = get_role($role_name);
        if ($role) {
            $role->remove_cap('manage_td_staff');
        }
    }
}

<?php
/**
 * TD Staff Capabilities
 * 
 * Manages user capabilities for the plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Setup capabilities on activation
 */
function td_tech_setup_capabilities() {
    // Add manage_td_staff capability to administrator role
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('manage_td_staff');
    }
}

/**
 * Check if current user can manage staff
 * 
 * @return bool
 */
function td_tech_can_manage() {
    return current_user_can('manage_td_staff');
}

/**
 * Check if current user can read staff data
 * 
 * @return bool
 */
function td_tech_can_read() {
    return current_user_can('read');
}

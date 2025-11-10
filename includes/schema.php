<?php
/**
 * TD Staff Database Schema
 * 
 * Creates and manages database tables
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create database schema
 */
function td_tech_create_database_schema() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    $prefix = $wpdb->prefix;
    
    // Staff table
    $staff_table = "CREATE TABLE {$prefix}td_staff (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        wp_user_id BIGINT UNSIGNED NULL,
        display_name VARCHAR(190) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(64) NULL,
        email_env LONGTEXT NULL,
        phone_env LONGTEXT NULL,
        email_bidx CHAR(64) NULL,
        timezone VARCHAR(64) NOT NULL DEFAULT 'Europe/Oslo',
        skills_json LONGTEXT NULL,
        weight INT NOT NULL DEFAULT 1,
        cooldown_sec INT NOT NULL DEFAULT 0,
        nc_base_url VARCHAR(255) NULL,
        nc_calendar_path VARCHAR(255) NULL,
        nc_username VARCHAR(190) NULL,
    nc_app_password_ct LONGBLOB NULL,
    nc_app_password_iv VARCHAR(64) NULL,
    nc_app_password_tag VARCHAR(64) NULL,
    nc_app_password_env LONGTEXT NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY wp_user_id (wp_user_id),
        KEY email (email),
        UNIQUE KEY email_bidx (email_bidx),
        KEY active (active)
    ) $charset_collate;";
    
    // Staff hours table
    $hours_table = "CREATE TABLE {$prefix}td_staff_hours (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        staff_id BIGINT UNSIGNED NOT NULL,
        weekday TINYINT UNSIGNED NOT NULL,
        start_min INT UNSIGNED NOT NULL,
        end_min INT UNSIGNED NOT NULL,
        PRIMARY KEY (id),
        KEY staff_weekday (staff_id, weekday)
    ) $charset_collate;";
    
    // Staff exceptions table
    $exceptions_table = "CREATE TABLE {$prefix}td_staff_exception (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        staff_id BIGINT UNSIGNED NOT NULL,
        type ENUM('holiday','sick','custom') NOT NULL,
        start_utc DATETIME NOT NULL,
        end_utc DATETIME NOT NULL,
        note VARCHAR(255) NULL,
        PRIMARY KEY (id),
        KEY staff_start (staff_id, start_utc),
        KEY start_utc (start_utc)
    ) $charset_collate;";
    
    // Execute table creation
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    dbDelta($staff_table);
    dbDelta($hours_table);
    dbDelta($exceptions_table);
    
    // Update database version
    update_option('td_tech_db_version', '1.2.0');
}

/**
 * Check if database needs upgrade
 * 
 * @return bool True if upgrade needed
 */
function td_tech_needs_db_upgrade(): bool {
    $current_version = get_option('td_tech_db_version', '0.0.0');
    return version_compare($current_version, '1.2.0', '<');
}

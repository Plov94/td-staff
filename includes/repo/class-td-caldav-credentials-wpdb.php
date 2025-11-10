<?php
/**
 * TD CalDAV Credentials WPDB Implementation
 * 
 * WPDB-based implementation of the CalDAV credentials provider
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CalDAV Credentials WPDB Implementation
 */
class TD_CalDav_Credentials_WPDB implements TD_CalDav_Credentials_Provider {
    
    private $wpdb;
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'td_staff';
    }
    
    /**
     * Get CalDAV credentials for a staff member
     */
    public function get_credentials($staff_id) {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
        "SELECT nc_base_url, nc_calendar_path, nc_username, 
            nc_app_password_ct, nc_app_password_iv, nc_app_password_tag, nc_app_password_env 
                 FROM {$this->table_name} 
                 WHERE id = %d AND active = 1",
                $staff_id
            ),
            ARRAY_A
        );
        
        if (!$row) {
            return null;
        }
        
        // Check if all required fields are present
        if (empty($row['nc_base_url']) || 
            empty($row['nc_calendar_path']) || 
            empty($row['nc_username']) ||
            (empty($row['nc_app_password_env']) && (
                empty($row['nc_app_password_ct']) ||
                empty($row['nc_app_password_iv']) ||
                empty($row['nc_app_password_tag'])
            ))) {
            return null;
        }
        
        // Decrypt the app password using envelope-first
        $app_password = td_tech_decrypt_unified(
            $row['nc_app_password_env'] ?? null,
            $row['nc_app_password_ct'] ?? null,
            $row['nc_app_password_iv'] ?? null,
            $row['nc_app_password_tag'] ?? null
        );
        if ($app_password === null) {
            return null;
        }
        
        return [
            'base_url' => $row['nc_base_url'],
            'calendar_path' => $row['nc_calendar_path'],
            'username' => $row['nc_username'],
            'app_password' => $app_password,
        ];
    }
}

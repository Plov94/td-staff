<?php
/**
 * TD CalDAV Credentials Provider Interface
 * 
 * Defines the contract for CalDAV credentials access
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CalDAV Credentials Provider Interface
 */
interface TD_CalDav_Credentials_Provider {
    
    /**
     * Get CalDAV credentials for a staff member
     * 
     * Returns decrypted credentials if all required fields are present,
     * or null if any required field is missing.
     * 
     * @param int $staff_id Staff member ID
     * @return array{base_url:string,calendar_path:string,username:string,app_password:string}|null
     *               Array of credentials or null if incomplete
     */
    public function get_credentials($staff_id);
}

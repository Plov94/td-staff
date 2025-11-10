<?php
/**
 * TD Staff Repository Interface
 * 
 * Defines the contract for staff data access
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Staff Repository Interface
 */
interface TD_Staff_Repository {
    
    /**
     * Get staff member by ID
     * 
     * @param int $id Staff ID
     * @return TD_Staff|null Staff object or null if not found
     */
    public function get($id);
    
    /**
     * Get staff member by WordPress user ID
     * 
     * @param int $wp_user_id WordPress user ID
     * @return TD_Staff|null Staff object or null if not found
     */
    public function get_by_wp_user($wp_user_id);

    /**
     * Get staff member by email
     * @param string $email
     * @return TD_Staff|null
     */
    public function get_by_email($email);
    
    /**
     * List staff members
     * 
     * @param array $args Optional filters:
     *                    - ids: array of staff IDs to include
     *                    - active: boolean to filter by active status
     *                    - skill: string to filter by skill
     * @return TD_Staff[] Array of staff objects
     */
    public function list($args = []);
    
    /**
     * List staff members by skills
     * 
     * @param array $skills Array of skills to filter by (OR logic)
     * @param array $filters Additional filters (same as list method)
     * @return TD_Staff[] Array of staff objects
     */
    public function list_by_skills($skills = [], $filters = []);
    
    /**
     * Create new staff member
     * 
     * @param TD_Staff $staff Staff object to create
     * @return int New staff ID
     * @throws Exception If creation fails
     */
    public function create($staff);
    
    /**
     * Update existing staff member
     * 
     * @param int $id Staff ID to update
     * @param TD_Staff $staff Staff object with updated data
     * @return bool True on success, false on failure
     */
    public function update($id, $staff);
    
    /**
     * Deactivate staff member
     * 
     * @param int $id Staff ID to deactivate
     * @return bool True on success, false on failure
     */
    public function deactivate($id);
}

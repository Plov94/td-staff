<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress database implementation of the staff repository
 */
class TD_Staff_Repository_WPDB implements TD_Staff_Repository {
    
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'td_staff';
    }
    
    /**
     * Get staff member by ID
     */
    public function get($id) {
        $row = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d AND active = 1",
            $id
        ), ARRAY_A);
        
        if (!$row) {
            return null;
        }
        
        return new TD_Staff($row);
    }
    
    /**
     * Get staff member by WordPress user ID
     */
    public function get_by_wp_user($wp_user_id) {
        $row = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE wp_user_id = %d AND active = 1",
            $wp_user_id
        ), ARRAY_A);
        
        if (!$row) {
            return null;
        }
        
        return new TD_Staff($row);
    }
    
    /**
     * Get staff member by email
     */
    public function get_by_email($email) {
        // Prefer blind index lookup if TD_PII_IDX_KEY_V1 is configured
        $bidx = function_exists('td_tech_email_blind_index') ? td_tech_email_blind_index($email, 'v1') : null;
        if (!empty($bidx)) {
            $row = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE email_bidx = %s AND active = 1",
                $bidx
            ), ARRAY_A);
        } else {
            $row = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE email = %s AND active = 1",
                $email
            ), ARRAY_A);
        }
        
        if (!$row) {
            return null;
        }
        
        return new TD_Staff($row);
    }
    
    /**
     * List staff members
     */
    public function list($args = []) {
        $where_conditions = ['1=1'];
        $where_values = [];
        
        // Filter by IDs
        if (!empty($args['ids']) && is_array($args['ids'])) {
            $placeholders = implode(',', array_fill(0, count($args['ids']), '%d'));
            $where_conditions[] = "id IN ($placeholders)";
            $where_values = array_merge($where_values, $args['ids']);
        }
        
        // Filter by active status
        if (isset($args['active'])) {
            $where_conditions[] = 'active = %d';
            $where_values[] = $args['active'] ? 1 : 0;
        } else {
            $where_conditions[] = 'active = 1';
        }
        
        // Build the query
        $where_clause = implode(' AND ', $where_conditions);
        $query = "SELECT * FROM {$this->table_name} WHERE $where_clause";
        
        if (!empty($where_values)) {
            $query = $this->wpdb->prepare($query, $where_values);
        }
        
        $results = $this->wpdb->get_results($query, ARRAY_A);
        
        if (!$results) {
            return [];
        }
        
        return array_map(function($row) {
            return new TD_Staff($row);
        }, $results);
    }
    
    /**
     * Create a new staff member
     */
    public function create($data) {
        // Convert TD_Staff object to array if needed
        if ($data instanceof TD_Staff) {
            $data = $this->staff_to_db_array($data);
        }
        
        $result = $this->wpdb->insert(
            $this->table_name,
            [
                'wp_user_id' => $data['wp_user_id'] ?? null,
                'display_name' => $data['display_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'email_env' => $data['email_env'] ?? null,
                'phone_env' => $data['phone_env'] ?? null,
                'email_bidx' => $data['email_bidx'] ?? null,
                'timezone' => $data['timezone'] ?? 'Europe/Oslo',
                'skills_json' => $data['skills_json'] ?? null,
                'weight' => $data['weight'] ?? 1,
                'cooldown_sec' => $data['cooldown_sec'] ?? 0,
                'nc_base_url' => $data['nc_base_url'] ?? null,
                'nc_calendar_path' => $data['nc_calendar_path'] ?? null,
                'nc_username' => $data['nc_username'] ?? null,
                'nc_app_password_ct' => $data['nc_app_password_ct'] ?? null,
                'nc_app_password_iv' => $data['nc_app_password_iv'] ?? null,
                'nc_app_password_tag' => $data['nc_app_password_tag'] ?? null,
                'nc_app_password_env' => $data['nc_app_password_env'] ?? null,
                'active' => $data['active'] ?? 1,
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true)
            ],
            [
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                '%s', '%s', '%d', '%d',
                '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s'
            ]
        );
        
        if ($result === false) {
            return false;
        }
        
        return $this->get($this->wpdb->insert_id);
    }
    
    /**
     * Update an existing staff member
     */
    public function update($id, $data) {
        // Convert TD_Staff object to array if needed
        if ($data instanceof TD_Staff) {
            $data = $this->staff_to_db_array($data);
        }
        
        $update_data = [];
        $format = [];
        
        $allowed_fields = [
            'wp_user_id' => '%d',
            'display_name' => '%s',
            'email' => '%s',
            'phone' => '%s',
            'email_env' => '%s',
            'phone_env' => '%s',
            'email_bidx' => '%s',
            'timezone' => '%s',
            'skills_json' => '%s',
            'weight' => '%d',
            'cooldown_sec' => '%d',
            'nc_base_url' => '%s',
            'nc_calendar_path' => '%s',
            'nc_username' => '%s',
            'nc_app_password_ct' => '%s',
            'nc_app_password_iv' => '%s',
            'nc_app_password_tag' => '%s',
            'nc_app_password_env' => '%s',
            'active' => '%d'
        ];
        
        foreach ($data as $field => $value) {
            if (array_key_exists($field, $allowed_fields)) {
                $update_data[$field] = $value;
                $format[] = $allowed_fields[$field];
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $update_data['updated_at'] = current_time('mysql', true);
        $format[] = '%s';
        
        $result = $this->wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $id],
            $format,
            ['%d']
        );
        
        if ($result === false) {
            return false;
        }
        
        return $this->get($id);
    }
    
    /**
     * Delete a staff member (soft delete by setting active = 0)
     */
    public function delete($id) {
        $result = $this->wpdb->update(
            $this->table_name,
            [
                'active' => 0,
                'updated_at' => current_time('mysql', true)
            ],
            ['id' => $id],
            ['%d', '%s'],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Deactivate staff member
     */
    public function deactivate($id) {
        $result = $this->wpdb->update(
            $this->table_name,
            [
                'active' => 0,
                'updated_at' => current_time('mysql', true)
            ],
            ['id' => $id],
            ['%d', '%s'],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * List staff members by skills
     */
    public function list_by_skills($skills = [], $filters = []) {
        if (empty($skills)) {
            return $this->list($filters);
        }
        
        $where_conditions = ['1=1'];
        $where_values = [];
        
        // Add skills filtering - search by both labels and slugs
        $skill_conditions = [];
        foreach ($skills as $skill) {
            $skill_slug = td_tech_normalize_skill_slug($skill);
            
            // Search by original skill name and normalized slug
            $skill_conditions[] = '(skills_json LIKE %s OR skills_json LIKE %s)';
            $where_values[] = '%' . $this->wpdb->esc_like($skill) . '%';
            $where_values[] = '%' . $this->wpdb->esc_like($skill_slug) . '%';
        }
        
        if (!empty($skill_conditions)) {
            $where_conditions[] = '(' . implode(' OR ', $skill_conditions) . ')';
        }
        
        // Apply other filters
        if (isset($filters['active'])) {
            $where_conditions[] = 'active = %d';
            $where_values[] = $filters['active'] ? 1 : 0;
        } else {
            $where_conditions[] = 'active = 1';
        }
        
        if (!empty($filters['ids']) && is_array($filters['ids'])) {
            $placeholders = implode(',', array_fill(0, count($filters['ids']), '%d'));
            $where_conditions[] = "id IN ($placeholders)";
            $where_values = array_merge($where_values, $filters['ids']);
        }
        
        // Build the query
        $where_clause = implode(' AND ', $where_conditions);
        $query = "SELECT * FROM {$this->table_name} WHERE $where_clause";
        
        if (!empty($where_values)) {
            $query = $this->wpdb->prepare($query, $where_values);
        }
        
        $results = $this->wpdb->get_results($query, ARRAY_A);
        
        if (!$results) {
            return [];
        }
        
        return array_map(function($row) {
            return new TD_Staff($row);
        }, $results);
    }
    
    /**
     * Convert TD_Staff object to database array format
     * 
     * @param TD_Staff $staff Staff object
     * @return array Database array format
     */
    private function staff_to_db_array(TD_Staff $staff): array {
        $store_plain = (bool) get_option('td_tech_store_plain_pii', false);
        $email_value = $staff->email;
        $phone_value = $staff->phone;
        if (!$store_plain) {
            if (!empty($staff->email_env)) { $email_value = ''; }
            if (!empty($staff->phone_env)) { $phone_value = ''; }
        }
        return [
            'wp_user_id' => $staff->wp_user_id,
            'display_name' => $staff->display_name,
            'email' => $email_value,
            'phone' => $phone_value,
            'email_env' => $staff->email_env,
            'phone_env' => $staff->phone_env,
            'email_bidx' => $staff->email_bidx,
            'timezone' => $staff->timezone,
            'skills_json' => !empty($staff->skills) ? wp_json_encode($staff->skills) : null,
            'weight' => $staff->weight,
            'cooldown_sec' => $staff->cooldown_sec,
            'nc_base_url' => $staff->nc_base_url,
            'nc_calendar_path' => $staff->nc_calendar_path,
            'nc_username' => $staff->nc_username,
            'nc_app_password_ct' => $staff->nc_app_password_ct,
            'nc_app_password_iv' => $staff->nc_app_password_iv,
            'nc_app_password_tag' => $staff->nc_app_password_tag,
            'active' => $staff->active ? 1 : 0,
        ];
    }
}

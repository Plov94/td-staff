<?php
/**
 * TD Staff REST API - Staff Endpoints
 * 
 * Handles REST API endpoints for staff management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST Staff Controller
 */
class TD_Tech_REST_Staff extends WP_REST_Controller {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->namespace = 'td-tech/v1';
        $this->resource_name = 'staff';
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // GET /staff - List staff
        register_rest_route($this->namespace, '/' . $this->resource_name, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args' => $this->get_collection_params(),
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
                'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
            ],
        ]);
        
        // GET /staff/{id} - Get single staff
        // PUT /staff/{id} - Update staff
        register_rest_route($this->namespace, '/' . $this->resource_name . '/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
                'args' => [
                    'id' => [
                        'description' => __('Staff ID', 'td-staff'),
                        'type' => 'integer',
                        'required' => true,
                    ],
                ],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
                'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
            ],
        ]);
        
        // GET /staff/{id}/caldav - Get CalDAV credentials for staff member
        register_rest_route($this->namespace, '/' . $this->resource_name . '/(?P<id>\d+)/caldav', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_caldav_credentials'],
            'permission_callback' => [$this, 'get_caldav_permissions_check'],
            'args' => [
                'id' => [
                    'description' => __('Staff ID', 'td-staff'),
                    'type' => 'integer',
                    'required' => true,
                ],
            ],
        ]);
    }
    
    /**
     * Get collection of staff members
     */
    public function get_items($request) {
        // Build filters and dispatch to repository appropriately
        $filters = [];

        // Filter by active status
        if (isset($request['active'])) {
            $filters['active'] = rest_sanitize_boolean($request['active']);
        }

        // Filter by IDs
        if (!empty($request['include'])) {
            $filters['ids'] = array_map('intval', (array) $request['include']);
        }

        try {
            $repo = td_tech()->repo();

            // If a skill filter is provided, use the dedicated skills method for accurate matching
            if (!empty($request['skill'])) {
                $skill = sanitize_text_field($request['skill']);
                $staff_members = $repo->list_by_skills([$skill], $filters);
            } else {
                $staff_members = $repo->list($filters);
            }
            
            $data = [];
            foreach ($staff_members as $staff) {
                $data[] = $staff->to_safe_array();
            }
            
            return rest_ensure_response($data);
        } catch (Exception $e) {
            return new WP_Error('td_tech_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Get single staff member
     */
    public function get_item($request) {
        $id = (int) $request['id'];
        
        try {
            $repo = td_tech()->repo();
            $staff = $repo->get($id);
            
            if (!$staff) {
                return new WP_Error('td_tech_not_found', __('Staff member not found.', 'td-staff'), ['status' => 404]);
            }
            
            return rest_ensure_response($staff->to_safe_array());
        } catch (Exception $e) {
            return new WP_Error('td_tech_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Create new staff member
     */
    public function create_item($request) {
        try {
            $staff = new TD_Staff();
            $this->populate_staff_from_request($staff, $request);
            
            // Validate required fields
            $validation = $this->validate_staff($staff, true);
            if (is_wp_error($validation)) {
                return $validation;
            }
            
            $repo = td_tech()->repo();
            $staff_id = $repo->create($staff);
            
            // Get the created staff member
            $created_staff = $repo->get($staff_id);
            
            return rest_ensure_response($created_staff->to_safe_array());
        } catch (Exception $e) {
            return new WP_Error('td_tech_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Update existing staff member
     */
    public function update_item($request) {
        $id = (int) $request['id'];
        
        try {
            $repo = td_tech()->repo();
            $staff = $repo->get($id);
            
            if (!$staff) {
                return new WP_Error('td_tech_not_found', __('Staff member not found.', 'td-staff'), ['status' => 404]);
            }
            
            $this->populate_staff_from_request($staff, $request);
            
            // Validate
            $validation = $this->validate_staff($staff, false);
            if (is_wp_error($validation)) {
                return $validation;
            }
            
            $result = $repo->update($id, $staff);
            
            if (!$result) {
                return new WP_Error('td_tech_update_failed', __('Failed to update staff member.', 'td-staff'), ['status' => 500]);
            }
            
            // Get the updated staff member
            $updated_staff = $repo->get($id);
            
            return rest_ensure_response($updated_staff->to_safe_array());
        } catch (Exception $e) {
            return new WP_Error('td_tech_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Populate staff object from request
     */
    private function populate_staff_from_request(TD_Staff $staff, WP_REST_Request $request) {
        $fields = [
            'wp_user_id', 'display_name', 'email', 'phone', 'timezone', 
            'weight', 'cooldown_sec', 'active', 'nc_base_url', 
            'nc_calendar_path', 'nc_username'
        ];
        
        foreach ($fields as $field) {
            if (isset($request[$field])) {
                $staff->$field = $request[$field];
            }
        }
        
        // Handle skills
        if (isset($request['skills'])) {
            if (is_string($request['skills'])) {
                $staff->skills = td_tech_parse_skills($request['skills']);
            } elseif (is_array($request['skills'])) {
                // Handle array of skill objects or strings
                $skills = [];
                foreach ($request['skills'] as $skill) {
                    if (is_string($skill)) {
                        $skills[] = [
                            'label' => sanitize_text_field($skill),
                            'slug' => td_tech_normalize_skill_slug($skill),
                            'level' => '',
                        ];
                    } elseif (is_array($skill) && isset($skill['label'])) {
                        $skills[] = [
                            'label' => sanitize_text_field($skill['label']),
                            'slug' => td_tech_normalize_skill_slug($skill['label']),
                            'level' => sanitize_text_field($skill['level'] ?? ''),
                        ];
                    }
                }
                $staff->skills = $skills;
            }
        }
        
        // Handle app password encryption
        if (!empty($request['nc_app_password'])) {
            $pw = (string) $request['nc_app_password'];
            $env = td_tech_sodium_encrypt_envelope($pw, 'v1');
            if ($env !== null) {
                $staff->nc_app_password_env = $env;
            } else {
                try {
                    $encrypted = td_tech_encrypt($pw);
                    $staff->nc_app_password_ct = $encrypted['ct'];
                    $staff->nc_app_password_iv = $encrypted['iv'];
                    $staff->nc_app_password_tag = $encrypted['tag'];
                } catch (Exception $e) {
                    // Encryption failed - will be caught in validation
                }
            }
        }

        // PII envelopes and blind index
        if (!empty($staff->email)) {
            $email_env = td_tech_pii_encrypt_envelope($staff->email, 'pii_v1');
            if ($email_env !== null) {
                $staff->email_env = $email_env;
            }
            $bidx = td_tech_email_blind_index($staff->email, 'v1');
            if (!empty($bidx)) {
                $staff->email_bidx = $bidx;
            }
        }
        if (!empty($staff->phone)) {
            $phone_env = td_tech_pii_encrypt_envelope($staff->phone, 'pii_v1');
            if ($phone_env !== null) {
                $staff->phone_env = $phone_env;
            }
        }
    }
    
    /**
     * Validate staff data
     */
    private function validate_staff(TD_Staff $staff, bool $is_create) {
        // Convert staff object to array for validation
        $staff_data = [
            'id' => $staff->id,
            'wp_user_id' => $staff->wp_user_id,
            'display_name' => $staff->display_name,
            'email' => $staff->email,
            'phone' => $staff->phone,
            'timezone' => $staff->timezone,
            'skills' => $staff->skills,
            'weight' => $staff->weight,
            'cooldown_sec' => $staff->cooldown_sec,
            'active' => $staff->active,
            'nc_base_url' => $staff->nc_base_url,
            'nc_calendar_path' => $staff->nc_calendar_path,
            'nc_username' => $staff->nc_username,
            'nc_app_password' => !empty($staff->nc_app_password_ct) ? '***' : '', // Don't pass actual encrypted password
        ];
        
        $validation = td_tech_validate_staff_data($staff_data, !$is_create);
        
        if (is_wp_error($validation)) {
            return new WP_Error($validation->get_error_code(), $validation->get_error_message(), ['status' => 400]);
        }
        
        return true;
    }
    
    /**
     * Permission callback for reading items
     */
    public function get_items_permissions_check($request) {
        return current_user_can('read');
    }
    
    /**
     * Permission callback for reading single item
     */
    public function get_item_permissions_check($request) {
        return current_user_can('read');
    }
    
    /**
     * Permission callback for creating items
     */
    public function create_item_permissions_check($request) {
        return current_user_can('manage_td_staff');
    }
    
    /**
     * Permission callback for updating items
     */
    public function update_item_permissions_check($request) {
        return current_user_can('manage_td_staff');
    }
    
    /**
     * Get CalDAV credentials for staff member
     */
    public function get_caldav_credentials($request) {
        $id = (int) $request['id'];
        
        try {
            $repo = td_tech()->repo();
            $staff = $repo->get($id);
            
            if (!$staff) {
                return new WP_Error('td_tech_not_found', __('Staff member not found.', 'td-staff'), ['status' => 404]);
            }
            
            $caldav_data = $staff->to_caldav_array();
            
            if (!$caldav_data) {
                return new WP_Error('td_tech_no_caldav', __('No CalDAV credentials configured for this staff member.', 'td-staff'), ['status' => 404]);
            }
            
            return rest_ensure_response($caldav_data);
        } catch (Exception $e) {
            return new WP_Error('td_tech_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Permission callback for CalDAV credentials access
     */
    public function get_caldav_permissions_check($request) {
        // Require manage_td_staff capability for accessing sensitive credentials
        return current_user_can('manage_td_staff');
    }
    
    /**
     * Get collection parameters
     */
    public function get_collection_params() {
        return [
            'active' => [
                'description' => __('Filter by active status', 'td-staff'),
                'type' => 'boolean',
            ],
            'skill' => [
                'description' => __('Filter by skill', 'td-staff'),
                'type' => 'string',
            ],
            'include' => [
                'description' => __('Include specific staff IDs', 'td-staff'),
                'type' => 'array',
                'items' => ['type' => 'integer'],
            ],
        ];
    }

    /**
     * Get staff by email callback
     */
    public function get_staff_by_email_callback($request) {
        $email = $request->get_param('email');
        $staff = $this->staff_repository->get_by_email($email);
        
        if (!$staff) {
            return new WP_Error('staff_not_found', 'Staff member not found', ['status' => 404]);
        }
        
        return rest_ensure_response($staff->to_array());
    }
}

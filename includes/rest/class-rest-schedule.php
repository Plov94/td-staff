<?php
/**
 * TD Staff REST API - Schedule Endpoints
 * 
 * Handles REST API endpoints for staff schedules and hours
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST Schedule Controller
 */
class TD_Tech_REST_Schedule extends WP_REST_Controller {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->namespace = 'td-tech/v1';
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // GET /staff/{id}/hours - Get staff work hours
        register_rest_route($this->namespace, '/staff/(?P<staff_id>\d+)/hours', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_hours'],
            'permission_callback' => [$this, 'get_permissions_check'],
            'args' => [
                'staff_id' => [
                    'description' => __('Staff ID', 'td-staff'),
                    'type' => 'integer',
                    'required' => true,
                ],
                // When true (default), include all weekdays (0-6) with empty arrays for days without shifts
                'full_week' => [
                    'description' => __('Include all weekdays with empty arrays', 'td-staff'),
                    'type' => 'boolean',
                    'required' => false,
                    'default' => true,
                ],
            ],
        ]);
        
        // POST /staff/{id}/hours - Update staff work hours
        register_rest_route($this->namespace, '/staff/(?P<staff_id>\d+)/hours', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'update_hours'],
            'permission_callback' => [$this, 'update_permissions_check'],
            'args' => [
                'staff_id' => [
                    'description' => __('Staff ID', 'td-staff'),
                    'type' => 'integer',
                    'required' => true,
                ],
                'hours' => [
                    'description' => __('Weekly hours array', 'td-staff'),
                    'type' => 'array',
                    'required' => true,
                ],
            ],
        ]);
        
        // GET /staff/{id}/exceptions - Get staff exceptions
        register_rest_route($this->namespace, '/staff/(?P<staff_id>\d+)/exceptions', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_exceptions'],
            'permission_callback' => [$this, 'get_permissions_check'],
            'args' => [
                'staff_id' => [
                    'description' => __('Staff ID', 'td-staff'),
                    'type' => 'integer',
                    'required' => true,
                ],
                'from' => [
                    'description' => __('Start date (UTC)', 'td-staff'),
                    'type' => 'string',
                    'format' => 'date-time',
                ],
                'to' => [
                    'description' => __('End date (UTC)', 'td-staff'),
                    'type' => 'string',
                    'format' => 'date-time',
                ],
            ],
        ]);
        
        // POST /staff/{id}/exceptions - Add exception
        register_rest_route($this->namespace, '/staff/(?P<staff_id>\d+)/exceptions', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_exception'],
            'permission_callback' => [$this, 'update_permissions_check'],
            'args' => [
                'staff_id' => [
                    'description' => __('Staff ID', 'td-staff'),
                    'type' => 'integer',
                    'required' => true,
                ],
                'type' => [
                    'description' => __('Exception type', 'td-staff'),
                    'type' => 'string',
                    'enum' => ['holiday', 'sick', 'custom'],
                    'required' => true,
                ],
                'start_utc' => [
                    'description' => __('Start time (UTC)', 'td-staff'),
                    'type' => 'string',
                    'format' => 'date-time',
                    'required' => true,
                ],
                'end_utc' => [
                    'description' => __('End time (UTC)', 'td-staff'),
                    'type' => 'string',
                    'format' => 'date-time',
                    'required' => true,
                ],
                'note' => [
                    'description' => __('Optional note', 'td-staff'),
                    'type' => 'string',
                ],
            ],
        ]);
        
        // DELETE /staff/{id}/exceptions/{exception_id} - Delete exception
        register_rest_route($this->namespace, '/staff/(?P<staff_id>\d+)/exceptions/(?P<exception_id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_exception'],
            'permission_callback' => [$this, 'update_permissions_check'],
            'args' => [
                'staff_id' => [
                    'description' => __('Staff ID', 'td-staff'),
                    'type' => 'integer',
                    'required' => true,
                ],
                'exception_id' => [
                    'description' => __('Exception ID', 'td-staff'),
                    'type' => 'integer',
                    'required' => true,
                ],
            ],
        ]);
    }
    
    /**
     * Get staff work hours
     */
    public function get_hours($request) {
        $staff_id = (int) $request['staff_id'];
        
        try {
            // Verify staff exists
            $staff = td_tech()->repo()->get($staff_id);
            if (!$staff) {
                return new WP_Error('td_tech_not_found', __('Staff member not found.', 'td-staff'), ['status' => 404]);
            }
            
            global $wpdb;
            $hours_table = $wpdb->prefix . 'td_staff_hours';
            
            $hours = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT weekday, start_min, end_min FROM {$hours_table} 
                     WHERE staff_id = %d ORDER BY weekday, start_min",
                    $staff_id
                ),
                ARRAY_A
            );
            
            // Group by weekday and convert minutes to time format
            $weekly_hours = [];
            foreach ($hours as $hour) {
                $weekday = (int) $hour['weekday'];
                if (!isset($weekly_hours[$weekday])) {
                    $weekly_hours[$weekday] = [];
                }
                
                $weekly_hours[$weekday][] = [
                    'start_time' => td_tech_minutes_to_time((int) $hour['start_min']),
                    'end_time' => td_tech_minutes_to_time((int) $hour['end_min']),
                ];
            }
            
            // Ensure consistent shape for consumers: include all weekdays 0..6 with empty arrays when requested (default)
            $include_full_week = isset($request['full_week']) ? rest_sanitize_boolean($request['full_week']) : true;
            if ($include_full_week) {
                for ($d = 0; $d <= 6; $d++) {
                    if (!isset($weekly_hours[$d])) {
                        $weekly_hours[$d] = [];
                    }
                }
                // Keep keys sorted
                ksort($weekly_hours);
            }
            
            return rest_ensure_response($weekly_hours);
        } catch (Exception $e) {
            return new WP_Error('td_tech_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Update staff work hours
     */
    public function update_hours($request) {
        $staff_id = (int) $request['staff_id'];
        $hours_data = $request['hours'];
        
        try {
            // Verify staff exists
            $staff = td_tech()->repo()->get($staff_id);
            if (!$staff) {
                return new WP_Error('td_tech_not_found', __('Staff member not found.', 'td-staff'), ['status' => 404]);
            }
            
            global $wpdb;
            $hours_table = $wpdb->prefix . 'td_staff_hours';
            
            // Start transaction
            $wpdb->query('START TRANSACTION');
            
            try {
                // Delete existing hours
                $wpdb->delete($hours_table, ['staff_id' => $staff_id], ['%d']);
                
                // Insert new hours - support both legacy and new formats
                foreach ($hours_data as $hour) {
                    // Handle both single hour object and array of shifts per day
                    $shifts_to_process = [];
                    
                    // New format: array with 'shifts' key
                    if (isset($hour['shifts']) && is_array($hour['shifts'])) {
                        foreach ($hour['shifts'] as $shift) {
                            $shifts_to_process[] = [
                                'weekday' => $hour['weekday'],
                                'start_time' => $shift['start_time'],
                                'end_time' => $shift['end_time'],
                            ];
                        }
                    } 
                    // Legacy format: single hour object
                    elseif (isset($hour['weekday']) && isset($hour['start_time']) && isset($hour['end_time'])) {
                        $shifts_to_process[] = $hour;
                    }
                    
                    // Process each shift
                    foreach ($shifts_to_process as $shift) {
                        if (empty($shift['weekday']) && $shift['weekday'] !== 0 || 
                            empty($shift['start_time']) || empty($shift['end_time'])) {
                            continue;
                        }
                        
                        $weekday = (int) $shift['weekday'];
                        $start_min = td_tech_time_to_minutes($shift['start_time']);
                        $end_min = td_tech_time_to_minutes($shift['end_time']);
                        
                        if ($weekday < 0 || $weekday > 6 || $start_min >= $end_min) {
                            continue;
                        }
                        
                        $wpdb->insert(
                            $hours_table,
                            [
                                'staff_id' => $staff_id,
                                'weekday' => $weekday,
                                'start_min' => $start_min,
                                'end_min' => $end_min,
                            ],
                            ['%d', '%d', '%d', '%d']
                        );
                    }
                }
                
                $wpdb->query('COMMIT');
                
                return rest_ensure_response(['success' => true]);
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                throw $e;
            }
        } catch (Exception $e) {
            return new WP_Error('td_tech_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Get staff exceptions
     */
    public function get_exceptions($request) {
        $staff_id = (int) $request['staff_id'];
        
        try {
            // Verify staff exists
            $staff = td_tech()->repo()->get($staff_id);
            if (!$staff) {
                return new WP_Error('td_tech_not_found', __('Staff member not found.', 'td-staff'), ['status' => 404]);
            }
            
            // Parse date range
            $from_utc = !empty($request['from']) ? 
                new DateTimeImmutable($request['from']) : 
                new DateTimeImmutable('now', new DateTimeZone('UTC'));
                
            $to_utc = !empty($request['to']) ? 
                new DateTimeImmutable($request['to']) : 
                $from_utc->modify('+1 month');
            
            $schedule_service = td_tech()->schedule();
            $exceptions = $schedule_service->list_exceptions($staff_id, $from_utc, $to_utc);
            
            $data = [];
            foreach ($exceptions as $exception) {
                $data[] = $exception->to_array();
            }
            
            return rest_ensure_response($data);
        } catch (Exception $e) {
            return new WP_Error('td_tech_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Create staff exception
     */
    public function create_exception($request) {
        $staff_id = (int) $request['staff_id'];
        
        try {
            // Verify staff exists
            $staff = td_tech()->repo()->get($staff_id);
            if (!$staff) {
                return new WP_Error('td_tech_not_found', __('Staff member not found.', 'td-staff'), ['status' => 404]);
            }
            
            $exception = new TD_Staff_Exception([
                'staff_id' => $staff_id,
                'type' => sanitize_text_field($request['type']),
                'start_utc' => sanitize_text_field($request['start_utc']),
                'end_utc' => sanitize_text_field($request['end_utc']),
                'note' => !empty($request['note']) ? sanitize_text_field($request['note']) : null,
            ]);
            
            // Validate
            $validation = $exception->validate();
            if (is_wp_error($validation)) {
                return $validation;
            }
            
            global $wpdb;
            $exceptions_table = $wpdb->prefix . 'td_staff_exception';
            
            $result = $wpdb->insert(
                $exceptions_table,
                $exception->to_array(true),
                ['%d', '%s', '%s', '%s', '%s']
            );
            
            if ($result === false) {
                return new WP_Error('td_tech_create_failed', __('Failed to create exception.', 'td-staff'), ['status' => 500]);
            }
            
            $exception->id = $wpdb->insert_id;
            
            return rest_ensure_response($exception->to_array());
        } catch (Exception $e) {
            return new WP_Error('td_tech_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Delete staff exception
     */
    public function delete_exception($request) {
        $staff_id = (int) $request['staff_id'];
        $exception_id = (int) $request['exception_id'];
        
        try {
            global $wpdb;
            $exceptions_table = $wpdb->prefix . 'td_staff_exception';
            
            $result = $wpdb->delete(
                $exceptions_table,
                [
                    'id' => $exception_id,
                    'staff_id' => $staff_id,
                ],
                ['%d', '%d']
            );
            
            if ($result === false || $result === 0) {
                return new WP_Error('td_tech_not_found', __('Exception not found.', 'td-staff'), ['status' => 404]);
            }
            
            return rest_ensure_response(['success' => true]);
        } catch (Exception $e) {
            return new WP_Error('td_tech_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Permission callback for reading
     */
    public function get_permissions_check($request) {
        return current_user_can('read');
    }
    
    /**
     * Permission callback for updating
     */
    public function update_permissions_check($request) {
        return current_user_can('manage_td_staff');
    }
}

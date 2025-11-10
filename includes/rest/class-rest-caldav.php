<?php
/**
 * TD Staff REST API - CalDAV Endpoints
 * 
 * Handles REST API endpoints for CalDAV testing
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST CalDAV Controller
 */
class TD_Tech_REST_CalDAV extends WP_REST_Controller {
    
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
        // POST /staff/{id}/caldav/test - Test CalDAV connection
        register_rest_route($this->namespace, '/staff/(?P<staff_id>\\d+)/caldav/test', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'test_connection'],
            'permission_callback' => [$this, 'test_permissions_check'],
            'args' => [
                'staff_id' => [
                    'description' => __('Staff ID', 'td-staff'),
                    'type' => 'integer',
                    'required' => true,
                ],
                'base_url' => [
                    'description' => __('Nextcloud base URL', 'td-staff'),
                    'type' => 'string',
                    'required' => false,
                ],
                'calendar_path' => [
                    'description' => __('Calendar path', 'td-staff'),
                    'type' => 'string',
                    'required' => false,
                ],
                'username' => [
                    'description' => __('Username', 'td-staff'),
                    'type' => 'string',
                    'required' => false,
                ],
                'app_password' => [
                    'description' => __('App password', 'td-staff'),
                    'type' => 'string',
                    'required' => false,
                ],
            ],
        ]);
    }
    
    /**
     * Test CalDAV connection
     */
    public function test_connection($request) {
        $staff_id = (int) $request['staff_id'];
        
        try {
            // Verify staff exists
            $staff = td_tech()->repo()->get($staff_id);
            if (!$staff) {
                return new WP_Error('td_tech_not_found', __('Staff member not found.', 'td-staff'), ['status' => 404]);
            }

            // Accept provided values or fallback to stored credentials
            $base_url = sanitize_url($request['base_url'] ?? '') ?: ($staff->nc_base_url ?? '');
            $calendar_path = sanitize_text_field($request['calendar_path'] ?? '') ?: ($staff->nc_calendar_path ?? '');
            $username = sanitize_text_field($request['username'] ?? '') ?: ($staff->nc_username ?? '');
            $app_password = sanitize_text_field($request['app_password'] ?? '');

            if (empty($app_password)) {
                $pt = td_tech_decrypt_unified(
                    $staff->nc_app_password_env ?? null,
                    $staff->nc_app_password_ct ?? null,
                    $staff->nc_app_password_iv ?? null,
                    $staff->nc_app_password_tag ?? null
                );
                $app_password = $pt ?? '';
            }

            // Validate inputs after fallback
            $missing = [];
            if (empty($base_url)) { $missing[] = __('Base URL', 'td-staff'); }
            if (empty($calendar_path)) { $missing[] = __('Calendar Path', 'td-staff'); }
            if (empty($username)) { $missing[] = __('Username', 'td-staff'); }
            if (empty($app_password)) { $missing[] = __('App Password', 'td-staff'); }
            if (!empty($missing)) {
                return new WP_Error('td_tech_invalid_params', sprintf(__('Missing required CalDAV fields: %s.', 'td-staff'), implode(', ', $missing)), ['status' => 400]);
            }

            // Normalize and encode calendar path
            $normalized_path = td_tech_normalize_calendar_path($calendar_path);
            $segments = array_filter(explode('/', trim($normalized_path, '/')), 'strlen');
            $encoded_path = '/' . implode('/', array_map('rawurlencode', $segments)) . '/';
            $calendar_url = rtrim($base_url, '/') . $encoded_path;

            // Test connection
            $result = $this->perform_caldav_test($calendar_url, $username, $app_password);

            return rest_ensure_response($result);
        } catch (Exception $e) {
            return new WP_Error('td_tech_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Perform the actual CalDAV test
     */
    private function perform_caldav_test(string $calendar_url, string $username, string $app_password): array {
        // Use WordPress HTTP API for the test
        $args = [
            'method' => 'OPTIONS',
            'timeout' => 5,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $app_password),
                'User-Agent' => 'TD-Staff/1.0.0',
            ],
        ];
        
        $response = wp_remote_request($calendar_url, $args);
        
        if (is_wp_error($response)) {
            return [
                'ok' => false,
                'message' => sprintf(
                    __('Connection failed: %s', 'td-staff'),
                    $response->get_error_message()
                ),
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $headers = wp_remote_retrieve_headers($response);
        
        // Check for successful response or authentication challenge
        if ($status_code === 200 || $status_code === 401) {
            // Check for CalDAV headers
            $dav_header = $headers['dav'] ?? '';
            
            if (strpos($dav_header, 'calendar-access') !== false || 
                strpos($dav_header, '1, 2') !== false) {
                return [
                    'ok' => true,
                    'message' => __('Connection successful! CalDAV server detected.', 'td-staff'),
                ];
            }
            
            // Try a PROPFIND request as fallback
            return $this->try_propfind_test($calendar_url, $username, $app_password);
        }
        
        if ($status_code === 401) {
            return [
                'ok' => false,
                'message' => __('Authentication failed. Please check your username and app password.', 'td-staff'),
            ];
        }
        
        if ($status_code === 404) {
            return [
                'ok' => false,
                'message' => __('Calendar not found. Please check the calendar path.', 'td-staff'),
            ];
        }
        
        return [
            'ok' => false,
            'message' => sprintf(
                __('Unexpected response: HTTP %d', 'td-staff'),
                $status_code
            ),
        ];
    }
    
    /**
     * Try a PROPFIND request as fallback test
     */
    private function try_propfind_test(string $calendar_url, string $username, string $app_password): array {
        $propfind_body = '<?xml version="1.0" encoding="utf-8" ?>
            <D:propfind xmlns:D="DAV:">
                <D:prop>
                    <D:resourcetype />
                </D:prop>
            </D:propfind>';
        
        $args = [
            'method' => 'PROPFIND',
            'timeout' => 5,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $app_password),
                'Content-Type' => 'application/xml',
                'Depth' => '0',
                'User-Agent' => 'TD-Staff/1.0.0',
            ],
            'body' => $propfind_body,
        ];
        
        $response = wp_remote_request($calendar_url, $args);
        
        if (is_wp_error($response)) {
            return [
                'ok' => false,
                'message' => sprintf(
                    __('PROPFIND test failed: %s', 'td-staff'),
                    $response->get_error_message()
                ),
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 207) { // Multi-Status response
                return [
                'ok' => true,
                    'message' => __('Connection successful! Calendar responded to PROPFIND request.', 'td-staff'),
            ];
        }
        
        if ($status_code === 401) {
            return [
                'ok' => false,
                'message' => __('Authentication failed. Please check your username and app password.', 'td-staff'),
            ];
        }
        
        return [
            'ok' => false,
            'message' => sprintf(
                __('PROPFIND test returned HTTP %d. Server may not support CalDAV.', 'td-staff'),
                $status_code
            ),
        ];
    }
    
    /**
     * Permission callback for testing
     */
    public function test_permissions_check($request) {
        return current_user_can('manage_td_staff');
    }
}

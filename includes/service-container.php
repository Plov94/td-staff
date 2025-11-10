<?php
/**
 * TD Staff Service Container
 * 
 * Simple service container for dependency injection
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Service Container class
 */
class TD_Service_Container {
    
    private static $instance = null;
    private $services = [];
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor for singleton
     */
    private function __construct() {
        // Initialize services
    }
    
    /**
     * Get staff repository
     */
    public function repo() {
        if (!isset($this->services['repo'])) {
            $this->services['repo'] = new TD_Staff_Repository_WPDB();
        }
        return $this->services['repo'];
    }
    
    /**
     * Get schedule service
     */
    public function schedule() {
        if (!isset($this->services['schedule'])) {
            $this->services['schedule'] = new TD_Staff_Schedule_WPDB();
        }
        return $this->services['schedule'];
    }
    
    /**
     * Get CalDAV credentials provider
     */
    public function caldav() {
        if (!isset($this->services['caldav'])) {
            $this->services['caldav'] = new TD_CalDav_Credentials_WPDB();
        }
        return $this->services['caldav'];
    }
}

/**
 * Global function to get service container
 * 
 * @return TD_Service_Container
 */
function td_tech() {
    // Check if called too early
    if (!did_action('plugins_loaded')) {
        if (is_admin()) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>';
                echo esc_html__('TD Staff: td_tech() called before plugins_loaded hook. This may cause issues.', 'td-staff');
                echo '</p></div>';
            });
        }
    }
    
    return TD_Service_Container::getInstance();
}

<?php
/**
 * TD Staff Exception Data Class
 * 
 * Data Transfer Object for staff exceptions (holidays, sick days, etc.)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Staff Exception DTO class
 */
class TD_Staff_Exception {
    
    public ?int $id = null;
    public int $staff_id = 0;
    public string $type = ''; // 'holiday', 'sick', 'custom'
    public string $start_utc = ''; // UTC datetime string
    public string $end_utc = ''; // UTC datetime string
    public ?string $note = null;
    
    /**
     * Constructor
     * 
     * @param array|object $data Optional data to populate the object
     */
    public function __construct($data = []) {
        // Handle both arrays and objects
        if (is_object($data)) {
            $data = (array) $data;
        }
        
        if (!is_array($data)) {
            $data = [];
        }
        
        if (!empty($data)) {
            $this->populate($data);
        }
    }
    
    /**
     * Populate object from array
     * 
     * @param array $data Data array
     */
    public function populate($data) {
        // Ensure data is array
        if (is_object($data)) {
            $data = (array) $data;
        }
        
        if (!is_array($data)) {
            return;
        }
        
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                switch ($key) {
                    case 'id':
                        $this->$key = $value ? (int) $value : null;
                        break;
                        
                    case 'staff_id':
                        $this->$key = $value ? (int) $value : 0;
                        break;
                        
                    case 'type':
                    case 'start_utc':
                    case 'end_utc':
                        $this->$key = $value ? (string) $value : '';
                        break;
                        
                    default:
                        $this->$key = $value;
                        break;
                }
            }
        }
    }
    
    /**
     * Convert to array for database storage
     * 
     * @param bool $for_insert Whether this is for insert (excludes id)
     * @return array Data array
     */
    public function to_array($for_insert = false) {
        $data = [
            'staff_id' => $this->staff_id,
            'type' => $this->type,
            'start_utc' => $this->start_utc,
            'end_utc' => $this->end_utc,
            'note' => $this->note,
        ];
        
        if (!$for_insert && $this->id) {
            $data['id'] = $this->id;
        }
        
        return $data;
    }
    
    /**
     * Get start time as DateTimeImmutable
     * 
     * @return DateTimeImmutable
     * @throws Exception If datetime is invalid
     */
    public function get_start_utc() {
        return new DateTimeImmutable($this->start_utc, new DateTimeZone('UTC'));
    }
    
    /**
     * Get end time as DateTimeImmutable
     * 
     * @return DateTimeImmutable
     * @throws Exception If datetime is invalid
     */
    public function get_end_utc() {
        return new DateTimeImmutable($this->end_utc, new DateTimeZone('UTC'));
    }
    
    /**
     * Check if exception overlaps with a time range
     * 
     * @param DateTimeImmutable $start_utc Range start (UTC)
     * @param DateTimeImmutable $end_utc Range end (UTC)
     * @return bool True if overlaps
     */
    public function overlaps($start_utc, $end_utc) {
        try {
            $exception_start = $this->get_start_utc();
            $exception_end = $this->get_end_utc();
            
            return $exception_start < $end_utc && $exception_end > $start_utc;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get human-readable type name
     * 
     * @return string Translated type name
     */
    public function get_type_name() {
        switch ($this->type) {
            case 'holiday':
                return __('Holiday', 'td-staff');
            case 'sick':
                return __('Sick Day', 'td-staff');
            case 'custom':
                return __('Custom', 'td-staff');
            default:
                return $this->type;
        }
    }
    
    /**
     * Validate exception data
     * 
     * @return WP_Error|true True if valid, WP_Error if invalid
     */
    public function validate() {
        // Check required fields
        if (empty($this->staff_id)) {
            return new WP_Error('missing_staff_id', __('Staff ID is required.', 'td-staff'));
        }
        
        if (empty($this->type)) {
            return new WP_Error('missing_type', __('Exception type is required.', 'td-staff'));
        }
        
        if (!in_array($this->type, ['holiday', 'sick', 'custom'])) {
            return new WP_Error('invalid_type', __('Invalid exception type.', 'td-staff'));
        }
        
        if (empty($this->start_utc)) {
            return new WP_Error('missing_start', __('Start time is required.', 'td-staff'));
        }
        
        if (empty($this->end_utc)) {
            return new WP_Error('missing_end', __('End time is required.', 'td-staff'));
        }
        
        // Validate datetime formats
        try {
            $start = new DateTimeImmutable($this->start_utc);
            $end = new DateTimeImmutable($this->end_utc);
            
            if ($start >= $end) {
                return new WP_Error('invalid_range', __('End time must be after start time.', 'td-staff'));
            }
        } catch (Exception $e) {
            return new WP_Error('invalid_datetime', __('Invalid datetime format.', 'td-staff'));
        }
        
        return true;
    }
}

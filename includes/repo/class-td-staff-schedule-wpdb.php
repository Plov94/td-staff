<?php
/**
 * TD Staff Schedule WPDB Implementation
 * 
 * WPDB-based implementation of the staff schedule service
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Staff Schedule WPDB Implementation
 */
class TD_Staff_Schedule_WPDB implements TD_Staff_Schedule_Service {
    
    private $wpdb;
    private $hours_table;
    private $exceptions_table;
    private $staff_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->hours_table = $wpdb->prefix . 'td_staff_hours';
        $this->exceptions_table = $wpdb->prefix . 'td_staff_exception';
        $this->staff_table = $wpdb->prefix . 'td_staff';
    }
    
    /**
     * Get daily work windows for a staff member
     */
    public function get_daily_work_windows($staff_id, $day_utc) {
        // Get staff timezone
        $staff = $this->get_staff($staff_id);
        if (!$staff) {
            return [];
        }
        
        try {
            $staff_tz = new DateTimeZone($staff->timezone);
        } catch (Exception $e) {
            // Fallback to default timezone
            $staff_tz = new DateTimeZone('Europe/Oslo');
        }
        
        // Convert UTC day to staff's local timezone to get the correct weekday
        $local_day = $day_utc->setTimezone($staff_tz);
        $weekday = (int) $local_day->format('w'); // 0=Sunday, 6=Saturday
        
        // Get weekly template for this weekday
        $hours = $this->get_weekly_hours($staff_id, $weekday);
        if (empty($hours)) {
            return [];
        }
        
        $windows = [];
        
        foreach ($hours as $hour) {
            // Skip zero-duration slots
            if ($hour['start_min'] >= $hour['end_min']) {
                continue;
            }
            
            // Convert local minutes to UTC datetime for this specific day
            $start_local = $local_day->setTime(0, 0)->modify("+{$hour['start_min']} minutes");
            $end_local = $local_day->setTime(0, 0)->modify("+{$hour['end_min']} minutes");
            
            // Convert to UTC
            $start_utc = $start_local->setTimezone(new DateTimeZone('UTC'));
            $end_utc = $end_local->setTimezone(new DateTimeZone('UTC'));
            
            // Double-check for zero-duration windows after timezone conversion
            if ($start_utc >= $end_utc) {
                continue;
            }
            
            $windows[] = [$start_utc, $end_utc];
        }
        
        // Apply exceptions to filter out unavailable times
        $windows = $this->apply_exceptions($staff_id, $windows);
        
        // Final validation: ensure no zero-duration windows
        $valid_windows = [];
        foreach ($windows as $window) {
            if ($window[0] < $window[1]) {
                $valid_windows[] = $window;
            }
        }
        
        return $valid_windows;
    }
    
    /**
     * List exceptions for a staff member in a date range
     */
    public function list_exceptions($staff_id, $from_utc, $to_utc) {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->exceptions_table} 
                 WHERE staff_id = %d 
                 AND start_utc < %s 
                 AND end_utc > %s 
                 ORDER BY start_utc ASC",
                $staff_id,
                $to_utc->format('Y-m-d H:i:s'),
                $from_utc->format('Y-m-d H:i:s')
            ),
            ARRAY_A
        );
        
        $exceptions = [];
        foreach ($results as $row) {
            $exceptions[] = new TD_Staff_Exception($row);
        }
        
        return $exceptions;
    }
    
    /**
     * Get exceptions (alias for list_exceptions for TD Booking compatibility)
     */
    public function get_exceptions($staff_id, $from_utc, $to_utc) {
        return $this->list_exceptions($staff_id, $from_utc, $to_utc);
    }
    
    /**
     * Get staff member
     */
    private function get_staff(int $staff_id): ?TD_Staff {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->staff_table} WHERE id = %d",
                $staff_id
            ),
            ARRAY_A
        );
        
        return $row ? new TD_Staff($row) : null;
    }
    
    /**
     * Get weekly hours for a specific weekday (now public)
     */
    public function get_weekly_hours($staff_id, $weekday = null) {
        $query = "SELECT weekday, start_min, end_min FROM {$this->hours_table} 
                 WHERE staff_id = %d";
        $params = [$staff_id];
        
        if ($weekday !== null) {
            $query .= " AND weekday = %d";
            $params[] = $weekday;
        }
        
        $query .= " ORDER BY weekday, start_min ASC";
        
        $hours = $this->wpdb->get_results(
            $this->wpdb->prepare($query, $params),
            ARRAY_A
        );
        
        if ($weekday !== null) {
            // Return just the hours for the specified weekday
            return $hours;
        }
        
        // Group by weekday and convert to expected format
        $weekly_hours = [];
        foreach ($hours as $hour) {
            $day = (int) $hour['weekday'];
            if (!isset($weekly_hours[$day])) {
                $weekly_hours[$day] = [];
            }
            
            $weekly_hours[$day][] = [
                'start_time' => td_tech_minutes_to_time((int) $hour['start_min']),
                'end_time' => td_tech_minutes_to_time((int) $hour['end_min']),
            ];
        }
        
        return $weekly_hours;
    }
    
    /**
     * Apply exceptions to filter out work windows
     */
    private function apply_exceptions($staff_id, $windows) {
        if (empty($windows)) {
            return $windows;
        }
        
        // Get the time range we need to check for exceptions
        $earliest = $windows[0][0];
        $latest = $windows[0][1];
        
        foreach ($windows as $window) {
            if ($window[0] < $earliest) $earliest = $window[0];
            if ($window[1] > $latest) $latest = $window[1];
        }
        
        // Get exceptions that might overlap
        $exceptions = $this->list_exceptions($staff_id, $earliest, $latest);
        
        if (empty($exceptions)) {
            return $windows;
        }
        
        $filtered_windows = [];
        
        foreach ($windows as $window) {
            $window_start = $window[0];
            $window_end = $window[1];
            $window_segments = [[$window_start, $window_end]];
            
            // Apply each exception to split/remove window segments
            foreach ($exceptions as $exception) {
                try {
                    $exc_start = $exception->get_start_utc();
                    $exc_end = $exception->get_end_utc();
                    
                    $new_segments = [];
                    
                    foreach ($window_segments as $segment) {
                        $seg_start = $segment[0];
                        $seg_end = $segment[1];
                        
                        // Check if exception overlaps with this segment
                        if ($exc_start < $seg_end && $exc_end > $seg_start) {
                            // Exception overlaps - split the segment
                            
                            // Add segment before exception (if any)
                            if ($seg_start < $exc_start) {
                                $new_segments[] = [$seg_start, min($exc_start, $seg_end)];
                            }
                            
                            // Add segment after exception (if any)
                            if ($seg_end > $exc_end) {
                                $new_segments[] = [max($exc_end, $seg_start), $seg_end];
                            }
                        } else {
                            // No overlap - keep segment as is
                            $new_segments[] = $segment;
                        }
                    }
                    
                    $window_segments = $new_segments;
                } catch (Exception $e) {
                    // Skip invalid exceptions
                    continue;
                }
            }
            
            // Add remaining segments to filtered windows
            foreach ($window_segments as $segment) {
                // Only add segments that are at least 1 minute long
                if ($segment[1] > $segment[0]->modify('+1 minute')) {
                    $filtered_windows[] = $segment;
                }
            }
        }
        
        // Final validation to ensure no zero-duration windows
        $valid_filtered_windows = [];
        foreach ($filtered_windows as $window) {
            if ($window[0] < $window[1]) {
                $valid_filtered_windows[] = $window;
            }
        }
        
        return $valid_filtered_windows;
    }
}

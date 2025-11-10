<?php
/**
 * TD Staff Schedule Service Interface
 * 
 * Defines the contract for staff scheduling operations
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Staff Schedule Service Interface
 */
interface TD_Staff_Schedule_Service {
    
    /**
     * Get daily work windows for a staff member
     * 
     * This method takes the staff's weekly schedule template and converts it
     * to UTC time windows for a specific day, taking into account the staff's
     * timezone and any exceptions.
     * 
     * @param int $staff_id Staff member ID
     * @param DateTimeImmutable $day_utc Day in UTC to get windows for
     * @return array<int, array{0:DateTimeImmutable,1:DateTimeImmutable}> 
     *               Array of [start_utc, end_utc] time windows
     */
    public function get_daily_work_windows($staff_id, $day_utc);
    
    /**
     * List exceptions for a staff member in a date range
     * 
     * @param int $staff_id Staff member ID
     * @param DateTimeImmutable $from_utc Start date (UTC)
     * @param DateTimeImmutable $to_utc End date (UTC)
     * @return TD_Staff_Exception[] Array of exception objects
     */
    public function list_exceptions($staff_id, $from_utc, $to_utc);
    
    /**
     * Get exceptions (alias for list_exceptions for compatibility)
     * 
     * @param int $staff_id Staff member ID
     * @param DateTimeImmutable $from_utc Start date (UTC)
     * @param DateTimeImmutable $to_utc End date (UTC)
     * @return TD_Staff_Exception[] Array of exception objects
     */
    public function get_exceptions($staff_id, $from_utc, $to_utc);
    
    /**
     * Get weekly hours for a staff member
     * 
     * @param int $staff_id Staff member ID
     * @param int|null $weekday Optional specific weekday (0=Sunday, 6=Saturday)
     * @return array Weekly hours array or specific day hours
     */
    public function get_weekly_hours($staff_id, $weekday = null);
}

<?php
/**
 * TD Staff Helper Functions
 * 
 * Utility functions for common tasks
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Convert local time (hours:minutes) to minutes since midnight
 * 
 * @param string $time Time in format "HH:MM" or "H:MM"
 * @return int Minutes since midnight
 */
function td_tech_time_to_minutes($time) {
    $parts = explode(':', $time);
    if (count($parts) !== 2) {
        return 0;
    }
    
    $hours = (int) $parts[0];
    $minutes = (int) $parts[1];
    
    return ($hours * 60) + $minutes;
}

/**
 * Convert minutes since midnight to time string
 * 
 * @param int $minutes Minutes since midnight
 * @return string Time in format "HH:MM"
 */
function td_tech_minutes_to_time($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    return sprintf('%02d:%02d', $hours, $mins);
}

/**
 * Convert local datetime to UTC datetime for a given timezone
 * 
 * @param string $local_datetime Local datetime string
 * @param string $timezone Timezone identifier (e.g., 'Europe/Oslo')
 * @return DateTimeImmutable UTC datetime
 * @throws Exception If timezone or datetime is invalid
 */
function td_tech_local_to_utc($local_datetime, $timezone) {
    $tz = new DateTimeZone($timezone);
    $local = new DateTimeImmutable($local_datetime, $tz);
    return $local->setTimezone(new DateTimeZone('UTC'));
}

/**
 * Convert UTC datetime to local datetime for a given timezone
 * 
 * @param DateTimeImmutable $utc_datetime UTC datetime
 * @param string $timezone Timezone identifier
 * @return DateTimeImmutable Local datetime
 * @throws Exception If timezone is invalid
 */
function td_tech_utc_to_local($utc_datetime, $timezone) {
    $tz = new DateTimeZone($timezone);
    return $utc_datetime->setTimezone($tz);
}

/**
 * Parse skills from comma-separated string to array of skill objects
 * 
 * Supports both simple strings and "label:level" format
 * Examples: "Plumbing, Electrical:Expert, HVAC:Beginner"
 * 
 * @param string $skills_csv Comma-separated skills
 * @return array Array of skill objects with 'label', 'slug', and 'level'
 */
function td_tech_parse_skills($skills_csv) {
    if (empty($skills_csv)) {
        return [];
    }
    
    $skills = array_map('trim', explode(',', $skills_csv));
    $parsed_skills = [];
    
    foreach ($skills as $skill) {
        if (empty($skill)) {
            continue;
        }
        
        // Check if skill has level (format: "label:level")
        if (strpos($skill, ':') !== false) {
            list($label, $level) = explode(':', $skill, 2);
            $label = trim($label);
            $level = trim($level);
        } else {
            $label = $skill;
            $level = '';
        }
        
        if (!empty($label)) {
            $parsed_skills[] = [
                'label' => $label,
                'slug' => td_tech_normalize_skill_slug($label),
                'level' => $level,
            ];
        }
    }
    
    return $parsed_skills;
}

/**
 * Convert skills array to comma-separated string for display
 * 
 * @param array $skills Array of skill objects or strings
 * @return string Comma-separated skills for display
 */
function td_tech_skills_to_string($skills) {
    if (empty($skills)) {
        return '';
    }
    
    $skill_strings = [];
    
    foreach ($skills as $skill) {
        if (is_string($skill)) {
            // Legacy support for simple strings
            $skill_strings[] = $skill;
        } elseif (is_array($skill) && isset($skill['label'])) {
            // New skill object format
            $display = $skill['label'];
            if (!empty($skill['level'])) {
                $display .= ':' . $skill['level'];
            }
            $skill_strings[] = $display;
        }
    }
    
    return implode(', ', $skill_strings);
}

/**
 * Normalize skill label to lowercase slug for matching
 * 
 * @param string $label Skill label
 * @return string Normalized slug
 */
function td_tech_normalize_skill_slug($label) {
    // Convert to lowercase and replace spaces/special chars with hyphens
    $slug = strtolower($label);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    
    return $slug;
}

/**
 * Get skill labels from skills array (for display purposes)
 * 
 * @param array $skills Array of skill objects
 * @return array Array of skill labels
 */
function td_tech_get_skill_labels($skills) {
    if (empty($skills)) {
        return [];
    }
    
    $labels = [];
    foreach ($skills as $skill) {
        if (is_string($skill)) {
            $labels[] = $skill;
        } elseif (is_array($skill) && isset($skill['label'])) {
            $labels[] = $skill['label'];
        }
    }
    
    return $labels;
}

/**
 * Get skill slugs from skills array (for matching purposes)
 * 
 * @param array $skills Array of skill objects
 * @return array Array of skill slugs
 */
function td_tech_get_skill_slugs($skills) {
    if (empty($skills)) {
        return [];
    }
    
    $slugs = [];
    foreach ($skills as $skill) {
        if (is_string($skill)) {
            $slugs[] = td_tech_normalize_skill_slug($skill);
        } elseif (is_array($skill) && isset($skill['slug'])) {
            $slugs[] = $skill['slug'];
        } elseif (is_array($skill) && isset($skill['label'])) {
            $slugs[] = td_tech_normalize_skill_slug($skill['label']);
        }
    }
    
    return $slugs;
}

/**
 * Get a default bank of common skills (labels only)
 *
 * Filterable via 'td_tech_skill_bank' to customize per site.
 *
 * @return array List of skill labels
 */
function td_tech_get_skill_bank(): array {
    // Categorized defaults
    $defaults = [
        'general' => [
            'Customer Support', 'Project Management', 'Sales', 'Copywriting', 'Graphic Design'
        ],
        'it' => [
            'WordPress Development', 'PHP', 'JavaScript', 'HTML/CSS', 'DevOps', 'QA Testing'
        ],
        'trades' => [
            'Plumbing', 'Electrical', 'HVAC', 'Carpentry', 'Painting', 'Cleaning', 'Landscaping'
        ],
        'wellness' => [
            'Personal Training', 'Physiotherapy', 'Massage Therapy'
        ],
    ];

    /**
     * Filter the default categorized skill bank list.
     *
     * @param array $defaults Associative array category => skills[]
     */
    $defaults = apply_filters('td_tech_skill_bank_categories', $defaults);

    // Optionally filter by selected categories (if any are checked)
    $selected = (array) get_option('td_tech_skill_bank_categories', []);
    $lists = [];
    if (!empty($selected)) {
        foreach ($selected as $cat) {
            if (isset($defaults[$cat])) {
                $lists = array_merge($lists, $defaults[$cat]);
            }
        }
    } else {
        // If none selected, include all
        foreach ($defaults as $skills) {
            $lists = array_merge($lists, $skills);
        }
    }

    // Final flat list, unique and sorted
    $flat = array_values(array_unique($lists));
    sort($flat, SORT_NATURAL | SORT_FLAG_CASE);

    /**
     * Final filter for flat list of skills displayed in the UI
     *
     * @param array $flat List of labels
     */
    return apply_filters('td_tech_skill_bank', $flat);
}

/**
 * Check if skills array contains a specific skill (by slug or label)
 * 
 * @param array $skills Array of skill objects
 * @param string $search_skill Skill to search for (label or slug)
 * @return bool True if skill found
 */
function td_tech_has_skill($skills, $search_skill) {
    if (empty($skills) || empty($search_skill)) {
        return false;
    }
    
    $search_slug = td_tech_normalize_skill_slug($search_skill);
    $skill_slugs = td_tech_get_skill_slugs($skills);
    
    return in_array($search_slug, $skill_slugs);
}

/**
 * Get list of timezone options for select dropdown
 * 
 * @return array Associative array of timezone identifiers => display names
 */
function td_tech_get_timezone_options() {
    $timezones = [];
    
    // Get all timezone identifiers
    $timezone_ids = timezone_identifiers_list();
    
    foreach ($timezone_ids as $tz_id) {
        // Group by continent for better organization
        $parts = explode('/', $tz_id);
        if (count($parts) >= 2) {
            $continent = $parts[0];
            $city = str_replace('_', ' ', $parts[1]);
            
            if (!isset($timezones[$continent])) {
                $timezones[$continent] = [];
            }
            
            $timezones[$continent][$tz_id] = $city;
        } else {
            $timezones['Other'][$tz_id] = $tz_id;
        }
    }
    
    return $timezones;
}

/**
 * Sanitize and validate timezone string
 * 
 * @param string $timezone Timezone identifier
 * @return string Valid timezone identifier or default
 */
function td_tech_sanitize_timezone($timezone) {
    $timezone = sanitize_text_field($timezone);
    
    // Check if timezone is valid
    try {
        new DateTimeZone($timezone);
        return $timezone;
    } catch (Exception $e) {
        return 'Europe/Oslo'; // Default timezone
    }
}

/**
 * Get weekday name by number
 * 
 * @param int $weekday Weekday number (0=Sunday, 6=Saturday)
 * @return string Weekday name
 */
function td_tech_get_weekday_name($weekday) {
    $weekdays = [
    0 => __('Sunday', 'td-staff'),
    1 => __('Monday', 'td-staff'),
    2 => __('Tuesday', 'td-staff'),
    3 => __('Wednesday', 'td-staff'),
    4 => __('Thursday', 'td-staff'),
    5 => __('Friday', 'td-staff'),
    6 => __('Saturday', 'td-staff')
    ];
    
    return $weekdays[$weekday] ?? '';
}

/**
 * Validate email address
 * 
 * @param string $email Email address to validate
 * @return string|WP_Error Valid email or WP_Error on failure
 */
function td_tech_validate_email($email) {
    $email = sanitize_email($email);
    
    if (!is_email($email)) {
    return new WP_Error('invalid_email', __('Invalid email address.', 'td-staff'));
    }
    
    return $email;
}

/**
 * Create a safe filename from a string
 * 
 * @param string $string Input string
 * @return string Safe filename
 */
function td_tech_sanitize_filename(string $string): string {
    // Remove special characters and spaces
    $string = preg_replace('/[^a-zA-Z0-9_-]/', '_', $string);
    
    // Remove multiple underscores
    $string = preg_replace('/_+/', '_', $string);
    
    // Trim underscores from ends
    return trim($string, '_');
}

/**
 * Normalize and validate CalDAV calendar path
 * 
 * @param string $path Calendar path
 * @return string Normalized calendar path
 */
function td_tech_normalize_calendar_path(string $path): string {
    if (empty($path)) {
        return '';
    }
    
    // Remove any leading/trailing whitespace
    $path = trim($path);
    
    // Ensure path starts with /
    if (!str_starts_with($path, '/')) {
        $path = '/' . $path;
    }
    
    // Ensure path ends with /
    if (!str_ends_with($path, '/')) {
        $path = $path . '/';
    }
    
    // Remove double slashes
    $path = preg_replace('/\/+/', '/', $path);
    
    return $path;
}

/**
 * Validate CalDAV base URL
 * 
 * @param string $url Base URL to validate
 * @return string|WP_Error Valid URL or WP_Error on failure
 */
function td_tech_validate_caldav_url(string $url) {
    if (empty($url)) {
        return '';
    }
    
    $url = sanitize_url($url);
    
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
    return new WP_Error('invalid_url', __('Invalid URL format.', 'td-staff'));
    }
    
    $parsed = parse_url($url);
    
    if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
    return new WP_Error('invalid_scheme', __('URL must use HTTP or HTTPS protocol.', 'td-staff'));
    }
    
    if (!isset($parsed['host'])) {
    return new WP_Error('invalid_host', __('URL must include a valid hostname.', 'td-staff'));
    }
    
    // Remove trailing slash for consistency
    return rtrim($url, '/');
}

/**
 * Validate CalDAV credentials are complete when any are provided
 * 
 * @param array $caldav_data CalDAV data array
 * @return true|WP_Error True if valid, WP_Error if invalid
 */
function td_tech_validate_caldav_credentials(array $caldav_data) {
    $has_any = !empty($caldav_data['base_url']) || 
               !empty($caldav_data['calendar_path']) || 
               !empty($caldav_data['username']) || 
               !empty($caldav_data['app_password']);
    
    if (!$has_any) {
        // No CalDAV data provided - this is OK
        return true;
    }
    
    // If any CalDAV field is provided, all required fields must be provided
    if (empty($caldav_data['base_url'])) {
    return new WP_Error('missing_caldav_url', __('CalDAV base URL is required when CalDAV is configured.', 'td-staff'));
    }
    
    if (empty($caldav_data['calendar_path'])) {
    return new WP_Error('missing_caldav_path', __('Calendar path is required when CalDAV is configured.', 'td-staff'));
    }
    
    if (empty($caldav_data['username'])) {
    return new WP_Error('missing_caldav_username', __('Username is required when CalDAV is configured.', 'td-staff'));
    }
    
    if (empty($caldav_data['app_password'])) {
    return new WP_Error('missing_caldav_password', __('App password is required when CalDAV is configured.', 'td-staff'));
    }
    
    return true;
}

/**
 * Validate phone number format
 * 
 * @param string $phone Phone number
 * @param bool $strict Whether to use strict validation or graceful handling
 * @return string|WP_Error Valid phone or WP_Error on failure
 */
function td_tech_validate_phone(string $phone, bool $strict = true) {
    if (empty($phone)) {
        return ''; // Phone is optional
    }
    
    $phone = sanitize_text_field($phone);
    
    // Handle common "empty" values gracefully when not in strict mode
    if (!$strict) {
        $empty_values = ['n/a', 'na', 'none', 'null', '0', '-'];
        if (in_array(strtolower(trim($phone)), $empty_values)) {
            return ''; // Convert to empty string
        }
    }
    
    // Remove common formatting characters
    $cleaned = preg_replace('/[^\d\+\-\(\)\s]/', '', $phone);
    
    // Basic phone number validation (at least 7 digits)
    if (preg_match('/\d{7,}/', $cleaned)) {
        return $phone; // Return original formatted version
    }
    
    if ($strict) {
    return new WP_Error('invalid_phone', __('Invalid phone number format.', 'td-staff'));
    } else {
        // In non-strict mode, return empty string for invalid phones
        return '';
    }
}

/**
 * Validate weight value
 * 
 * @param int|string $weight Weight value
 * @return int Validated weight (1-100)
 */
function td_tech_validate_weight($weight): int {
    $weight = (int) $weight;
    
    if ($weight < 1) {
        return 1;
    }
    
    if ($weight > 100) {
        return 100;
    }
    
    return $weight;
}

/**
 * Validate cooldown seconds
 * 
 * @param int|string $cooldown Cooldown in seconds
 * @return int Validated cooldown (non-negative)
 */
function td_tech_validate_cooldown($cooldown): int {
    $cooldown = (int) $cooldown;
    
    return max(0, $cooldown);
}

/**
 * Comprehensive staff data validation
 * 
 * @param array $staff_data Staff data array
 * @param bool $is_update Whether this is an update (ID may be present)
 * @param bool $strict_validation Whether to use strict validation (for user input) or graceful handling (for database loading)
 * @return array|WP_Error Validated data array or WP_Error
 */
function td_tech_validate_staff_data(array $staff_data, bool $is_update = false, bool $strict_validation = true) {
    $validated = [];
    $errors = [];
    
    // Required fields
    if (empty($staff_data['display_name'])) {
    $errors[] = __('Display name is required.', 'td-staff');
    } else {
        $validated['display_name'] = sanitize_text_field($staff_data['display_name']);
    }
    
    if (empty($staff_data['email'])) {
    $errors[] = __('Email address is required.', 'td-staff');
    } else {
        $email_validation = td_tech_validate_email($staff_data['email']);
        if (is_wp_error($email_validation)) {
            $errors[] = $email_validation->get_error_message();
        } else {
            $validated['email'] = $email_validation;
        }
    }
    
    // Optional fields with validation
    if (!empty($staff_data['phone'])) {
        $phone_validation = td_tech_validate_phone($staff_data['phone'], $strict_validation);
        if (is_wp_error($phone_validation)) {
            $errors[] = $phone_validation->get_error_message();
        } else {
            $validated['phone'] = $phone_validation;
        }
    }
    
    // Timezone
    $validated['timezone'] = td_tech_sanitize_timezone($staff_data['timezone'] ?? 'Europe/Oslo');
    
    // Skills - handle both 'skills' and 'skills_json' (database format)
    if (isset($staff_data['skills_json']) && !empty($staff_data['skills_json'])) {
        // Parse JSON from database
        $skills_data = json_decode($staff_data['skills_json'], true);
        if (is_array($skills_data)) {
            $validated['skills'] = $skills_data;
        } else {
            $validated['skills'] = [];
        }
    } elseif (isset($staff_data['skills'])) {
        if (is_string($staff_data['skills'])) {
            $validated['skills'] = td_tech_parse_skills($staff_data['skills']);
        } elseif (is_array($staff_data['skills'])) {
            $validated['skills'] = $staff_data['skills'];
        }
    }
    
    // Weight and cooldown
    $validated['weight'] = td_tech_validate_weight($staff_data['weight'] ?? 1);
    $validated['cooldown_sec'] = td_tech_validate_cooldown($staff_data['cooldown_sec'] ?? 0);
    
    // Active status
    $validated['active'] = (bool) ($staff_data['active'] ?? true);
    
    // WordPress user ID
    if (!empty($staff_data['wp_user_id'])) {
        $validated['wp_user_id'] = (int) $staff_data['wp_user_id'];
    }
    
    // CalDAV validation
    $caldav_data = [
        'base_url' => $staff_data['nc_base_url'] ?? '',
        'calendar_path' => $staff_data['nc_calendar_path'] ?? '',
        'username' => $staff_data['nc_username'] ?? '',
        'app_password' => $staff_data['nc_app_password'] ?? '',
    ];
    
    $caldav_validation = td_tech_validate_caldav_credentials($caldav_data);
    if (is_wp_error($caldav_validation)) {
        $errors[] = $caldav_validation->get_error_message();
    } else {
        // Validate and normalize CalDAV URL
        if (!empty($caldav_data['base_url'])) {
            $url_validation = td_tech_validate_caldav_url($caldav_data['base_url']);
            if (is_wp_error($url_validation)) {
                $errors[] = $url_validation->get_error_message();
            } else {
                $validated['nc_base_url'] = $url_validation;
            }
        }
        
        // Normalize calendar path
        if (!empty($caldav_data['calendar_path'])) {
            $validated['nc_calendar_path'] = td_tech_normalize_calendar_path($caldav_data['calendar_path']);
        }
        
        // Username and app password
        if (!empty($caldav_data['username'])) {
            $validated['nc_username'] = sanitize_text_field($caldav_data['username']);
        }
        
        if (!empty($caldav_data['app_password'])) {
            $validated['nc_app_password'] = $caldav_data['app_password']; // Don't sanitize password
        }
    }
    
    if (!empty($errors)) {
        return new WP_Error('validation_failed', implode(' ', $errors));
    }
    
    return $validated;
}

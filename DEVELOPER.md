# TD Staff - Developer Documentation

## Overview

TD Staff is a WordPress plugin that serves as the **centralized credential management service** for staff data and CalDAV integration within your WordPress ecosystem. It provides comprehensive PHP and REST APIs for integration with booking plugins and other systems.

**Version**: 1.2.0 (PII Encryption & Envelopes)  
**Updated**: October 2025

## Architecture

### Core Components

- **Service Container** (`TD_Service_Container`): Manages plugin dependencies and provides global access
- **Repository Pattern**: Data access abstraction with interface-based design
- **Data Objects**: Simple PHP classes representing domain entities
- **REST Controllers**: WordPress REST API integration
- **Admin Interface**: Full CRUD operations with AJAX support

### Database Schema

#### `td_staff` Table
Core staff profiles with encrypted PII and CalDAV credentials:
- `id` - Primary key
- `wp_user_id` - Optional link to WordPress user account
- `display_name`, `email`, `phone` - Contact information (plaintext optional; see PII envelopes)
- `email_env`, `phone_env` - Sodium envelope JSON for email/phone at rest
- `email_bidx` - Blind index (hex HMAC-SHA256) for email lookups
- `timezone` - Staff member's timezone (e.g., 'America/New_York')
- `skills_json` - JSON array of rich skill objects with labels, slugs, and levels
- `weight` - Priority for selection algorithms (1-100)
- `cooldown_sec` - Minimum time between appointments
- `active` - Soft delete flag
- `nc_base_url`, `nc_calendar_path`, `nc_username` - CalDAV connection details
- `nc_app_password_env` - Sodium envelope for CalDAV app password (preferred)
- `nc_app_password_ct`, `nc_app_password_iv`, `nc_app_password_tag` - Legacy AES-GCM fields (read-only compatibility)
- `created_at`, `updated_at` - Timestamps

#### `td_staff_hours` Table
Weekly work schedules:
- `staff_id` - Foreign key to td_staff
- `weekday` - Day of week (0=Sunday, 6=Saturday)
- `start_min`, `end_min` - Work hours as minutes since midnight

#### `td_staff_exception` Table
Time-off exceptions (holidays, sick days, etc.):
- `staff_id` - Foreign key to td_staff
- `type` - Exception type (holiday, sick, custom, etc.)
- `start_utc`, `end_utc` - Exception period in UTC
- `note` - Optional description

## PHP API

### Getting Started

```php
// Get the service container (always check plugins_loaded hook first)
if (function_exists('td_tech')) {
    $td = td_tech();
} else {
    // Plugin not available
    return;
}
```

### Staff Repository

```php
$repo = $td->repo();

// Get single staff member
$staff = $repo->get(123);
if ($staff && $staff->active) {
    echo "Name: " . $staff->display_name;
    echo "Email: " . $staff->email;
    echo "Timezone: " . $staff->timezone;
    
    // Enhanced skills system
    foreach ($staff->skills as $skill) {
        echo "Skill: " . $skill['label'] . " (" . $skill['level'] . ")";
    }
    
    // Check CalDAV credentials
    if ($staff->has_caldav_credentials()) {
        echo "CalDAV configured for calendar sync";
    }
}

// List staff with enhanced filters
$active_staff = $repo->list(['active' => true]);
$skilled_staff = $repo->list(['skill' => 'wordpress-development']);
$expert_staff = $repo->list(['skill' => 'php', 'active' => true]);
$limited_list = $repo->list(['limit' => 10, 'offset' => 0]);
$by_ids = $repo->list(['ids' => [1, 2, 3]]);
```

### Schedule Service

```php
$schedule = $td->schedule();

// Get work windows for a specific day
$date = new DateTimeImmutable('2024-01-15');
$windows = $schedule->get_daily_work_windows(123, $date);

foreach ($windows as $window) {
    echo "Available: " . $window['start'] . " to " . $window['end'];
    // Times are in the staff member's timezone
}
```

### CalDAV Credentials (Enhanced)

```php
// Direct access via staff object (recommended)
$staff = $repo->get(123);
$caldav_data = $staff->to_caldav_array(); // Returns null if no credentials

if ($caldav_data) {
    $calendar_url = $caldav_data['calendar_url']; // Pre-built full URL
    $username = $caldav_data['username'];
    $app_password = $caldav_data['app_password']; // Automatically decrypted
    
    // Use for CalDAV operations
    $calendar_client = new CalDAVClient($calendar_url);
    $calendar_client->setCredentials($username, $app_password);
}

// Legacy CalDAV service (still available)
if (current_user_can('manage_td_staff')) {
    $caldav = $td->caldav();
    $credentials = $caldav->get_credentials(123);
}
```

## REST API

All endpoints require appropriate WordPress authentication and capabilities.

### Base URL
```
/wp-json/td-tech/v1/
```

### Staff Management Endpoints

**Core Staff Operations:**
- `GET /staff` - List staff with filtering (`?active=true&skill=wordpress&include=1,2,3`)
- `GET /staff/{id}` - Get single staff member (safe data only)
- `POST /staff` - Create new staff member (requires `manage_td_staff`)
- `PUT /staff/{id}` - Update staff member (requires `manage_td_staff`)

**Enhanced CalDAV Integration:**
- `GET /staff/{id}/caldav` - Get CalDAV credentials (requires `manage_td_staff`)
- `POST /staff/{id}/caldav/test` - Test CalDAV connection (requires `manage_td_staff`). Accepts partial input; missing fields fall back to stored credentials when available. Calendar path is normalized and encoded server-side.

**Staff Data Format:**
```json
{
  "id": 123,
  "wp_user_id": 456,
  "display_name": "John Doe",
  "email": "john@example.com",
  "phone": "+1-555-0123",
  "timezone": "America/New_York",
  "skills": [
    {
      "label": "WordPress Development",
      "slug": "wordpress-development",
      "level": "expert"
    }
  ],
  "weight": 5,
  "cooldown_sec": 300,
  "active": true,
  "created_at": "2025-01-15 10:30:00",
  "updated_at": "2025-09-12 14:22:00"
}
```

**CalDAV Credentials Format:**
```json
{
  "base_url": "https://nextcloud.example.com",
  "calendar_path": "/remote.php/dav/calendars/user/personal/",
  "username": "john@example.com",
  "app_password": "decrypted-app-password",
  "calendar_url": "https://nextcloud.example.com/remote.php/dav/calendars/user/personal/"
}
```

### Schedule Service (PHP Only)

The schedule service is available via PHP API only:
```php
$schedule = td_tech()->schedule();

// Get work windows for specific date
$windows = $schedule->get_daily_work_windows($staff_id, $date_utc);

// Get exceptions in date range  
$exceptions = $schedule->list_exceptions($staff_id, $from_utc, $to_utc);

// Get weekly hours template
$weekly_hours = $schedule->get_weekly_hours($staff_id, $weekday);
```

### Authentication

Use standard WordPress authentication with proper nonces:

```javascript
fetch('/wp-json/td-tech/v1/staff', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
})
```

## Integration Examples

### Complete Booking Plugin Integration

```php
// 1. Find available staff with required skills
$response = wp_remote_get('/wp-json/td-tech/v1/staff?active=true&skill=plumbing');
$staff_list = json_decode(wp_remote_retrieve_body($response), true);

// 2. Check availability for requested time
$schedule = td_tech()->schedule();
foreach ($staff_list as $staff) {
    $work_windows = $schedule->get_daily_work_windows($staff['id'], $requested_date);
    if (has_available_slot($work_windows, $requested_time, $service_duration)) {
        $selected_staff = $staff;
        break;
    }
}

// 3. Create booking and sync to calendar
if ($selected_staff) {
    $booking_id = create_booking($selected_staff['id'], $booking_data);
    
    // Get CalDAV credentials for calendar sync
    $caldav_response = wp_remote_get("/wp-json/td-tech/v1/staff/{$selected_staff['id']}/caldav");
    if (wp_remote_retrieve_response_code($caldav_response) === 200) {
        $caldav_creds = json_decode(wp_remote_retrieve_body($caldav_response), true);
        
        // Create calendar event
        $calendar_client = new CalDAVClient($caldav_creds['calendar_url']);
        $calendar_client->setCredentials($caldav_creds['username'], $caldav_creds['app_password']);
        $calendar_client->createEvent([
            'summary' => 'Service Appointment',
            'start' => $appointment_start,
            'end' => $appointment_end,
            'description' => $booking_details
        ]);
    }
}
```

### Helper Functions

```php
// Skill processing
$skills = td_tech_parse_skills("WordPress, PHP, JavaScript");
$skills_string = td_tech_skills_to_string($skills_array);
$slug = td_tech_normalize_skill_slug("WordPress Development");

// Validation helpers
$valid_phone = td_tech_validate_phone($phone, $strict = true);
$safe_timezone = td_tech_sanitize_timezone($timezone);

// Time conversion
$minutes = td_tech_time_to_minutes("09:30");
$time = td_tech_minutes_to_time(570); // "09:30"
```

## Security

### Encryption
Sensitive data is encrypted using libsodium where available:
- PII envelopes (emails/phones): XChaCha20-Poly1305 with JSON envelope `{alg,kid,c,n}` and key `TD_PII_ENC_KEY_V1`.
- Email blind index: HMAC-SHA256 with key `TD_PII_IDX_KEY_V1` for deterministic lookups.
- CalDAV app passwords: sodium envelope preferred with key `TD_KMS_KEY_V1` (legacy AES-GCM is still readable).

### Access Control
- `manage_td_staff` - Full access including CalDAV credentials (granted to administrators)
- Standard WordPress `read` capability for basic staff data access
- CalDAV credentials API requires `manage_td_staff` capability

### Input Validation
- All user input sanitized and validated through `td_tech_validate_staff_data()`
- SQL queries use prepared statements
- CSRF protection via WordPress nonces
- Skills data validated and normalized

## GDPR Compliance

- **Admin-only access**: Staff personal data only visible to WordPress administrators
- **Encrypted sensitive data**: PII and CalDAV passwords stored with sodium envelopes (fallback to legacy AES-GCM for reads)
- **API data filtering**: REST API uses `to_safe_array()` excluding sensitive CalDAV credentials
- **Controlled access**: CalDAV credentials API requires admin permissions

## Compatibility

- **WordPress**: 6.2+
- **PHP**: 8.0+
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Extensions**: libsodium (preferred), OpenSSL (legacy AES-GCM fallback)
- **Browsers**: Cross-browser admin interface (Firefox, Chrome, Edge, Safari)

## Architecture Benefits

### Centralized Credential Management
- **Single source of truth** for staff and CalDAV credentials
- **Reusable by multiple plugins** (booking, reporting, etc.)
- **Security centralized** - encryption handled in one place
- **Admin experience** - manage staff AND credentials in one interface

### Clean Separation of Concerns
- **TD Staff**: Staff management + secure credential storage
- **Booking Plugins**: Calendar operations + booking logic
- **CalDAV Servers**: Calendar data storage

## License

GPL v2 or later

## Translations

The plugin text domain is `td-staff` and translation files live in `languages/`.

- Template: `languages/td-staff.pot`
- Example locale: `languages/td-staff-nb_NO.po` -> `languages/td-staff-nb_NO.mo`

You can compile `.po` files to `.mo` in two ways:

1) Using GNU gettext (recommended)

Make sure `msgfmt` is installed, then run:

```
make i18n
```

This compiles all `*.po` files under `languages/` to `*.mo`.

2) Using WP-CLI

If you have WP-CLI installed and your WordPress is accessible in this environment:

```
make i18n-wpcli
```

Alternatively, you can run WP-CLI directly:

```
wp i18n make-mo languages languages
```

Output files are picked up automatically at runtime via `load_plugin_textdomain('td-staff', false, 'td-staff/languages')`.

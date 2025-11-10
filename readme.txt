=== TD Staff ===
Contributors: Plov94
Tags: staff, scheduling, caldav, api, booking
Requires at least: 6.2
Tested up to: 6.4
Stable tag: 1.2.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Centralized staff management and CalDAV credential service for WordPress. Store staff profiles, skills, schedules, and encrypted CalDAV credentials—ready for booking plugins.

== Description ==

TD Staff is a WordPress plugin that serves as the centralized service for staff data and CalDAV integration. It’s designed for booking plugins and systems that need secure access to staff information and calendar credentials.

== Key Features ==

* Enhanced Staff Management — profiles with skills (labels, slugs, proficiency levels)
* Secure CalDAV Integration — encrypted credentials (sodium envelopes; legacy AES-GCM read support)
* Schedule Management — timezone-aware hours and exceptions
* REST + PHP APIs — complete API, including CalDAV credentials endpoint
* Admin UI — colored skill tags, connection testing, cross‑browser support
* WordPress Integration — map to users, capability-based access
* GDPR-minded — admin-only personal data, encryption at rest
* Production-ready — tested across modern browsers; multisite friendly

== Enhanced API for Booking Plugin Integration ==

REST API endpoints:
* `GET /wp-json/td-tech/v1/staff` - List staff with filtering (`?active=true&skill=wordpress`)
* `GET /wp-json/td-tech/v1/staff/{id}` - Get single staff member details
* `GET /wp-json/td-tech/v1/staff/{id}/caldav` - Get CalDAV credentials (admin-only)
* `POST /wp-json/td-tech/v1/staff/{id}/caldav/test` - Test CalDAV connection
* Standard CRUD operations with proper permission checks

PHP API:
```php
// Get staff with enhanced skills
$staff = td_tech()->repo()->get(123);
foreach ($staff->skills as $skill) {
    echo $skill['label'] . ' (' . $skill['level'] . ')';
}

// Get CalDAV credentials securely
$caldav_data = $staff->to_caldav_array();
if ($caldav_data) {
    $calendar_url = $caldav_data['calendar_url'];
    $credentials = [$caldav_data['username'], $caldav_data['app_password']];
}

// Check availability
$windows = td_tech()->schedule()->get_daily_work_windows($staff_id, $date);
```

Skills system enhancement:
Staff skills are now rich objects with labels, normalized slugs, and proficiency levels (beginner, intermediate, advanced, expert, master) for better matching and filtering.

Security & GDPR features:
* **Sodium envelopes** for PII (email/phone) and CalDAV app passwords, with blind index for email lookups
* **Admin-only personal data access** - emails/phones only visible to administrators
* **Secure API design** - CalDAV credentials require `manage_td_staff` capability
* **Input validation** - comprehensive sanitization and validation system
* **CSRF protection** - WordPress nonces for all admin operations
* **SQL injection protection** - prepared statements throughout

Centralized architecture:
TD Staff serves as a **credential service** for booking plugins:
- **Staff Management**: Contact info, skills, schedules, WordPress user linking
- **CalDAV Credentials**: Secure storage and API access for calendar integration
- **Booking Plugins**: Get credentials via API, handle calendar operations directly
- **Clean Separation**: Each plugin focuses on its core functionality

Database tables:
* `td_staff` - Core staff member profiles, PII envelopes, and CalDAV credentials
* `td_staff_hours` - Weekly work schedules (per staff member, per weekday)
* `td_staff_exception` - Time-off exceptions (holidays, sick days, custom availability)

== Installation ==

1. Upload plugin files to `/wp-content/plugins/td-staff/`
2. Activate through the Plugins screen
3. Open the Staff menu to configure your team

First-time setup:
1. Staff → Settings: set defaults
2. Staff → Add New: create your first staff member
3. Configure work hours per staff member
4. (Optional) Add CalDAV/Nextcloud credentials and Test Connection

Permissions:
* `manage_td_staff` — full access (admins)
* `read_td_staff` — read-only (editors and above)

CalDAV setup (optional):
1. Create an app password in Nextcloud
2. In the staff profile, enter:
    - Base URL (e.g., https://cloud.example.com)
    - Calendar Path (e.g., /remote.php/dav/calendars/username/calendar-name/)
    - Username + App Password
3. Use Test Connection to verify

== Frequently Asked Questions ==

= Is this plugin compatible with multisite? =
Yes. Each site maintains its own staff data; no network-wide sharing in v1.0.

= How secure are the CalDAV credentials? =
Credentials are encrypted (sodium envelopes preferred; legacy AES-GCM supported). They’re never exposed via public APIs.

= Can other plugins access staff data? =
Yes. That’s the goal. Use the PHP API or REST API.

= What happens to my data if I deactivate the plugin? =
Deactivating keeps data intact. Uninstall removes data only if “Allow hard uninstall” is enabled in settings.

= Can I import existing staff data? =
No built-in importer yet. Use the REST or PHP API for migrations.

= How do work schedules and exceptions work? =
Weekly work hours per day; exceptions override for date ranges (holidays, sick days, custom availability).

= What timezone handling is supported? =
Full timezone awareness. Each staff member has a timezone; storage uses UTC.

= Can I customize the admin interface? =
Standard WordPress hooks and filters. Admin UI follows WP patterns and can be styled with custom CSS.

= What are the minimum requirements? =
* WordPress 6.2+
* PHP 8.0+
* OpenSSL extension (for encryption)
* MySQL/MariaDB with InnoDB

= How do I troubleshoot CalDAV connection issues? =
1) Verify server URL and calendar path  
2) Use an app password  
3) Ensure PROPFIND is supported  
4) Use Test Connection for diagnostics

== Changelog ==

= 1.2.0 =
* **PII Encryption** - Emails and phones encrypted at rest using sodium envelopes; email blind index added for lookups
* **Sodium Migration** - Tools to migrate legacy CalDAV AES-GCM passwords to sodium envelopes; backfill PII envelopes/indexes
* **Settings** - Key status indicators, plaintext PII storage toggle (default off)
* **REST Enhancements** - CalDAV test allows partial input and server-side path normalization/encoding

= 1.1.0 =
* **Enhanced Skills System** - Rich skill objects with labels, slugs, and proficiency levels
* **CalDAV Credentials API** - New `/staff/{id}/caldav` endpoint for booking plugin integration
* **Visual Improvements** - Colored skill tags based on proficiency levels in admin interface
* **Cross-Browser Compatibility** - Fixed JavaScript compatibility issues across Firefox, Chrome, Edge
* **Terminology Updates** - Consistent "staff member" terminology throughout interface
* **Security Enhancements** - Enhanced GDPR compliance and encryption coverage analysis
* **API Enhancements** - Enhanced staff data format with richer skills metadata
* **Admin Polish** - Removed debug messages, improved validation, better error handling
* **Integration Ready** - Complete API documentation and examples for booking plugin integration
* **Database Improvements** - Fixed skills data loading from skills_json column

= 1.0.0 =
* Initial release
* Core staff management with profiles, contact info, and basic skills
* Weekly work schedule configuration with timezone support
* Exception management (holidays, sick days, custom time-off)
* CalDAV/Nextcloud integration with encrypted credential storage
* Complete REST API for integration with other plugins
* Full administrative interface with AJAX-powered operations
* Multisite compatibility with per-site data isolation
* Comprehensive security features and access control
* Full internationalization support (translation-ready)
* PHP 8.0+ and WordPress 6.2+ compatibility
* Database schema with proper indexing and relationships

== Technical Documentation ==

**For Plugin Developers:**

The TD Staff plugin provides a stable API contract for accessing staff data. Here are the main integration points:

**PHP API Examples:**
```php
// Get the service container
$td = td_tech();

// Get a staff profile
$staff = $td->repo()->get(123);
if ($staff && $staff->active) {
    echo "Staff member: " . $staff->display_name;
    echo "Skills: " . implode(', ', $staff->skills);
}

// Check work availability for a specific day
$schedule = $td->schedule();
$date = new DateTimeImmutable('2024-01-15');
$windows = $schedule->get_daily_work_windows(123, $date);

foreach ($windows as $window) {
    echo "Available: " . $window['start'] . " to " . $window['end'];
}

// Get CalDAV credentials (for authorized plugins)
if (current_user_can('manage_td_staff')) {
    $credentials = $td->caldav()->get_credentials(123);
    if ($credentials) {
        // Use for calendar operations
        $cal_url = $credentials['base_url'] . $credentials['calendar_path'];
    }
}
```

**Hook Examples:**
```php
// Customize staff data before save (hypothetical - implement if needed)
add_filter('td_tech_before_save_staff', function($staff_data) {
    // Modify $staff_data
    return $staff_data;
});
```

**Database Schema:**
The plugin creates three main tables with proper foreign key relationships and indexing for performance. All sensitive data (CalDAV passwords) is encrypted at rest.

**REST API Authentication:**
All REST endpoints require proper WordPress authentication and respect the plugin's capability system. Use standard WordPress nonce verification for security.

== Upgrade Notice ==

= 1.0.0 =
Initial release of TD Staff plugin. Provides comprehensive staff management, work scheduling, and CalDAV integration for WordPress sites.

== Support ==

For technical support, feature requests, or bug reports, please contact the plugin author or check the plugin's documentation.

**Known Limitations in v1.1:**
* No built-in data import/export tools (use REST API for migrations)
* CalDAV integration provides credentials only (booking plugins handle calendar operations)
* No recurring exception patterns (each exception must be entered individually)
* Admin interface optimized for desktop (mobile-responsive but not touch-optimized)

**Roadmap:**
Future versions may include data import/export tools, enhanced mobile admin interface, recurring exception patterns, and advanced skill matching algorithms.

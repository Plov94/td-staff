# TD Staff • User Guide

Make managing staff, schedules, and calendar credentials simple. This guide shows you how to set up TD Staff and use it confidently—no jargon required.

Version: 1.2.0 · Updated: October 2025

## Table of contents

- Quick start
- Install and set up
- Manage staff members
- Work hours and exceptions
- Booking plugin integration
- Security and privacy
- Troubleshooting
- Browser support

---

## Quick start

1) Install and activate the plugin.  
2) Add your first staff member (Staff → Add New).  
3) Configure work hours and exceptions.  
4) (Optional) Add CalDAV credentials and test the connection.  
5) Switch your site language to see translations if needed.

> Tip: CalDAV credentials are optional. You can add them later per staff member.

## Install and set up

1. Install the plugin
   - Upload to `/wp-content/plugins/td-staff/` or install via WordPress admin
   - Activate via the Plugins screen
2. Configure settings (Staff → Settings)
   - Default Nextcloud base URL (if using CalDAV)
   - Uninstall behavior (Allow hard uninstall)
   - Security & Encryption status and migrations
3. Permissions
   - Administrators get full access (`manage_td_staff`)
   - CalDAV credentials are admin-only for your safety

> Note: You can always change settings later. Nothing here is permanent.

## Manage staff members

### Add a staff member

1. Go to Staff → Add New
2. Fill in:
   - Display Name (required)
   - Email (required)
   - Phone (optional)
   - Timezone (e.g., “America/New_York”)
   - WordPress User link (optional)
3. Skills
   - Format: `Label:Level` (e.g., `WordPress Development:expert`)
   - Levels: beginner, intermediate, advanced, expert, master
   - Or: simple comma-separated list (`WordPress, PHP, JavaScript`)
   - Displayed as colored tags by level
   - Skill bank: use the preset buttons to quickly add common skills; optionally pick a default level from the dropdown. Click Clear to reset.
4. Advanced
   - Weight: selection priority (1–100)
   - Cooldown: seconds between appointments
   - Active: mark availability

### CalDAV integration (optional)

- Base URL: your Nextcloud/CalDAV server
- Calendar Path: e.g., `/remote.php/dav/calendars/user/personal/`
- Username and App Password (use an app password, not your login password)

Click Test Connection to verify.  
If you previously saved credentials, you can test with password left blank—the stored one will be used.

> Security: Password fields are never auto-filled for safety.

## Work hours and exceptions

### Weekly work hours

1. Staff → All Staff → click a staff member
2. Manage Work Hours
3. Configure:
   - Working days and time ranges
   - Multiple shifts per day
   - Templates (9–5, split shifts, etc.)
   - Copy/Paste weeks between staff

Week tools:
- Copy Week · Paste Week · Clear Week · Apply Template

### Exceptions (time off)

1. From the staff page, click Manage Exceptions
2. Add exception:
   - Type: Holiday, Sick, Personal, Custom
   - Start/End date & time
   - Optional note

> Exceptions always override regular work hours.

## Booking plugin integration

TD Staff acts as a centralized service that booking plugins can use.

What other plugins can access:
- Staff profiles (with skills)
- Work hours and availability windows
- CalDAV credentials for calendar sync
- Real-time availability checks

For site owners: configure staff and credentials once—other plugins can read them via API.

## Security and privacy

Data protection:
- Emails and phones encrypted at rest (sodium envelopes, XChaCha20-Poly1305)
- Email blind index (HMAC) for lookups without revealing the value
- CalDAV app passwords encrypted (sodium, with legacy AES-GCM read support)
- WordPress nonces and capability checks throughout the admin UI

GDPR:
- Admin-only access to personal data
- No CalDAV credentials exposed through public APIs
- Secure handling of sensitive data end-to-end

## Troubleshooting

CalDAV connection issues
1. Confirm server URL and full calendar path
2. Use an app password (not your login password)
3. Ensure the server supports PROPFIND
4. Click Test Connection for detailed errors
5. Previously saved? You can test with password left blank

Encryption keys & migrations
1. See warnings in Settings → Security & Encryption
2. Define keys in `wp-config.php` or hosting environment
3. Then run:
   - Migrate legacy CalDAV passwords → sodium
   - Backfill PII envelopes and indexes
4. You can disable storing plaintext email/phone (recommended). Decrypted values still display in UI/API.

Skills not displaying
1. Use `Label:Level` or comma-separated format
2. Save and refresh the admin page
3. Make sure JavaScript is enabled

Work hours look wrong
1. Check the staff member’s timezone
2. Avoid shifts crossing midnight (use two entries)
3. Verify exceptions for the same day

## Browser support

Tested on:
- Firefox (recommended for CalDAV testing)
- Chrome/Chromium
- Microsoft Edge

---

This guide covers TD Staff v1.2.0 (PII encryption, migrations, enhanced skills, CalDAV). For technical integration details, see `DEVELOPER.md`.

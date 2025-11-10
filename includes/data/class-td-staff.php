<?php
/**
 * TD Staff Data Class
 * 
 * Data Transfer Object for staff members
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Staff DTO class
 */
class TD_Staff {
    
    public ?int $id = null;
    public ?int $wp_user_id = null;
    public string $display_name = '';
    public string $email = '';
    public ?string $phone = null;
    public ?string $email_env = null; // sodium envelope JSON for email
    public ?string $phone_env = null; // sodium envelope JSON for phone
    public ?string $email_bidx = null; // blind index for email lookups
    public string $timezone = 'Europe/Oslo';
    public array $skills = [];
    public int $weight = 1;
    public int $cooldown_sec = 0;
    public ?string $nc_base_url = null;
    public ?string $nc_calendar_path = null;
    public ?string $nc_username = null;
    // Plaintext app password is transient (never persisted or exposed in safe arrays)
    public ?string $nc_app_password = null;
    public ?string $nc_app_password_ct = null;
    public ?string $nc_app_password_iv = null;
    public ?string $nc_app_password_tag = null;
    public ?string $nc_app_password_env = null; // sodium envelope JSON
    public bool $active = true;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    
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
            // Use graceful loading for constructor (usually database data)
            $this->populate($data, false);
        }
    }
    
    /**
     * Populate staff object from array data
     * 
     * @param array $data Data array
     * @param bool $strict_validation Whether to use strict validation (for user input) or graceful loading (for database data)
     * @return TD_Staff Current instance for chaining
     * @throws InvalidArgumentException If validation fails and strict_validation is true
     */
    public function populate(array $data, bool $strict_validation = true): self {
        if ($strict_validation) {
            // Use comprehensive validation for user input
            $validated = td_tech_validate_staff_data($data, !empty($data['id']), true);
            
            if (is_wp_error($validated)) {
                throw new InvalidArgumentException($validated->get_error_message());
            }
            
            // Set validated properties
            $this->id = (int) ($data['id'] ?? 0);
            $this->wp_user_id = $validated['wp_user_id'] ?? null;
            $this->display_name = $validated['display_name'];
            $this->email = $validated['email'];
            $this->phone = $validated['phone'] ?? '';
            $this->timezone = $validated['timezone'];
            $this->weight = $validated['weight'];
            $this->cooldown_sec = $validated['cooldown_sec'];
            $this->active = $validated['active'];
            
            // Handle skills
            $this->skills = $validated['skills'] ?? [];
            
            // Handle NextCloud/CalDAV settings
            $this->nc_base_url = $validated['nc_base_url'] ?? '';
            $this->nc_calendar_path = $validated['nc_calendar_path'] ?? '';
            $this->nc_username = $validated['nc_username'] ?? '';
            $this->nc_app_password = $validated['nc_app_password'] ?? '';
        } else {
            // Use graceful validation for existing database data
            $validated = td_tech_validate_staff_data($data, !empty($data['id']), false);
            
            if (is_wp_error($validated)) {
                // If even graceful validation fails, fall back to basic population
                $this->populate_from_database($data);
            } else {
                // Use validated data
                $this->id = (int) ($data['id'] ?? 0);
                $this->wp_user_id = $validated['wp_user_id'] ?? null;
                $this->display_name = $validated['display_name'];
                $this->email = $validated['email'];
                $this->phone = $validated['phone'] ?? '';
                $this->timezone = $validated['timezone'];
                $this->weight = $validated['weight'];
                $this->cooldown_sec = $validated['cooldown_sec'];
                $this->active = $validated['active'];
                
                // Handle skills
                $this->skills = $validated['skills'] ?? [];
                
                // Handle NextCloud/CalDAV settings
                $this->nc_base_url = $validated['nc_base_url'] ?? '';
                $this->nc_calendar_path = $validated['nc_calendar_path'] ?? '';
                $this->nc_username = $validated['nc_username'] ?? '';
                $this->nc_app_password = $validated['nc_app_password'] ?? '';
            }
        }

        // Always carry over encrypted password fields from database payload when present
        // This applies to both strict and non-strict modes and ensures decryption is possible later
        if (isset($data['nc_app_password_ct'])) {
            $this->nc_app_password_ct = $data['nc_app_password_ct'];
        }
        if (isset($data['nc_app_password_iv'])) {
            $this->nc_app_password_iv = $data['nc_app_password_iv'];
        }
        if (isset($data['nc_app_password_tag'])) {
            $this->nc_app_password_tag = $data['nc_app_password_tag'];
        }
        if (isset($data['nc_app_password_env'])) {
            $this->nc_app_password_env = $data['nc_app_password_env'];
        }
        // Carry PII envelope/index fields if present
        if (isset($data['email_env'])) {
            $this->email_env = $data['email_env'];
        }
        if (isset($data['phone_env'])) {
            $this->phone_env = $data['phone_env'];
        }
        if (isset($data['email_bidx'])) {
            $this->email_bidx = $data['email_bidx'];
        }
        
        return $this;
    }
    
    /**
     * Set properties from already-validated data
     * 
     * @param array $validated_data Already validated data
     * @return TD_Staff Current instance for chaining
     */
    public function set_validated_data(array $validated_data): self {
        // Set properties directly since data is already validated
        $this->id = (int) ($validated_data['id'] ?? $this->id);
        $this->wp_user_id = $validated_data['wp_user_id'] ?? null;
        $this->display_name = $validated_data['display_name'] ?? '';
        $this->email = $validated_data['email'] ?? '';
        $this->phone = $validated_data['phone'] ?? '';
        $this->timezone = $validated_data['timezone'] ?? 'Europe/Oslo';
        $this->weight = $validated_data['weight'] ?? 1;
        $this->cooldown_sec = $validated_data['cooldown_sec'] ?? 0;
        $this->active = $validated_data['active'] ?? true;
        
        // Handle skills
        $this->skills = $validated_data['skills'] ?? [];
        
        // Handle NextCloud/CalDAV settings
        $this->nc_base_url = $validated_data['nc_base_url'] ?? '';
        $this->nc_calendar_path = $validated_data['nc_calendar_path'] ?? '';
        $this->nc_username = $validated_data['nc_username'] ?? '';
        $this->nc_app_password = $validated_data['nc_app_password'] ?? '';
        
        return $this;
    }
    
    /**
     * Populate staff object from database data with graceful handling
     * 
     * @param array $data Database data array
     * @return void
     */
    private function populate_from_database(array $data): void {
        // Basic properties with safe defaults
        $this->id = (int) ($data['id'] ?? 0);
        $this->wp_user_id = isset($data['wp_user_id']) ? (int) $data['wp_user_id'] : null;
        $this->display_name = sanitize_text_field($data['display_name'] ?? '');
        $this->email = sanitize_email($data['email'] ?? '');
        // Handle phone with graceful validation for legacy data
        $phone_raw = sanitize_text_field($data['phone'] ?? '');
        $this->phone = td_tech_validate_phone($phone_raw, false); // Non-strict validation
        $this->timezone = td_tech_sanitize_timezone($data['timezone'] ?? 'Europe/Oslo');
        $this->weight = max(1, min(100, (int) ($data['weight'] ?? 1)));
        $this->cooldown_sec = max(0, (int) ($data['cooldown_sec'] ?? 0));
        $this->active = (bool) ($data['active'] ?? true);
        
        // Handle skills with graceful parsing
        if (isset($data['skills_json']) && !empty($data['skills_json'])) {
            // Parse JSON from database
            $skills_data = json_decode($data['skills_json'], true);
            if (is_array($skills_data)) {
                $this->skills = $this->normalize_skills($skills_data);
            } else {
                $this->skills = [];
            }
        } elseif (isset($data['skills'])) {
            // Handle direct skills data (for backwards compatibility)
            if (is_string($data['skills'])) {
                $this->skills = td_tech_parse_skills($data['skills']);
            } elseif (is_array($data['skills'])) {
                $this->skills = $this->normalize_skills($data['skills']);
            } else {
                $this->skills = [];
            }
        } else {
            $this->skills = [];
        }
        
        // NextCloud/CalDAV settings with safe defaults
        $this->nc_base_url = sanitize_url($data['nc_base_url'] ?? '');
        $this->nc_calendar_path = sanitize_text_field($data['nc_calendar_path'] ?? '');
        $this->nc_username = sanitize_text_field($data['nc_username'] ?? '');
        $this->nc_app_password = $data['nc_app_password'] ?? '';
        $this->nc_app_password_ct = $data['nc_app_password_ct'] ?? '';
        $this->nc_app_password_iv = $data['nc_app_password_iv'] ?? '';
        $this->nc_app_password_tag = $data['nc_app_password_tag'] ?? '';
        // Capture PII envelope fields if present
        $this->email_env = $data['email_env'] ?? null;
        $this->phone_env = $data['phone_env'] ?? null;
        $this->email_bidx = $data['email_bidx'] ?? null;

        // If encrypted PII is available, prefer decrypted values over plaintext columns
        if (!empty($this->email_env)) {
            $pt_email = td_tech_pii_decrypt_envelope($this->email_env);
            if ($pt_email !== null) {
                $this->email = sanitize_email($pt_email);
            }
        }
        if (!empty($this->phone_env)) {
            $pt_phone = td_tech_pii_decrypt_envelope($this->phone_env);
            if ($pt_phone !== null) {
                // Keep using our phone validator to normalize any formatting
                $this->phone = td_tech_validate_phone($pt_phone, false);
            }
        }
    }    /**
     * Convert to array for database storage
     * 
     * @param bool $for_insert Whether this is for insert (excludes id, timestamps)
     * @return array Data array
     */
    public function to_array(bool $for_insert = false): array {
        $data = [
            'wp_user_id' => $this->wp_user_id,
            'display_name' => $this->display_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'timezone' => $this->timezone,
            'skills_json' => json_encode($this->skills),
            'weight' => $this->weight,
            'cooldown_sec' => $this->cooldown_sec,
            'nc_base_url' => $this->nc_base_url,
            'nc_calendar_path' => $this->nc_calendar_path,
            'nc_username' => $this->nc_username,
            'nc_app_password_ct' => $this->nc_app_password_ct,
            'nc_app_password_iv' => $this->nc_app_password_iv,
            'nc_app_password_tag' => $this->nc_app_password_tag,
            'nc_app_password_env' => $this->nc_app_password_env,
            'active' => $this->active ? 1 : 0,
        ];
        
        if (!$for_insert && $this->id) {
            $data['id'] = $this->id;
        }
        
        return $data;
    }
    
    /**
     * Convert to safe array for API responses (excludes sensitive data)
     * 
     * @return array Safe data array
     */
    public function to_safe_array(): array {
        return [
            'id' => $this->id,
            'wp_user_id' => $this->wp_user_id,
            'display_name' => $this->display_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'timezone' => $this->timezone,
            'skills' => $this->skills,
            'weight' => $this->weight,
            'cooldown_sec' => $this->cooldown_sec,
            'active' => $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
    
    /**
     * Check if CalDAV credentials are complete
     * 
     * @return bool True if all required CalDAV fields are present
     */
    public function has_caldav_credentials() {
        $has_password = !empty($this->nc_app_password_env) || (
            !empty($this->nc_app_password_ct) &&
            !empty($this->nc_app_password_iv) &&
            !empty($this->nc_app_password_tag)
        );
        return !empty($this->nc_base_url) &&
               !empty($this->nc_calendar_path) &&
               !empty($this->nc_username) &&
               $has_password;
    }
    
    /**
     * Get CalDAV credentials array for API consumption
     * 
     * @return array|null CalDAV credentials with decrypted password, or null if incomplete
     */
    public function to_caldav_array(): ?array {
        if (!$this->has_caldav_credentials()) {
            return null;
        }
        
        $decrypted_password = td_tech_decrypt_unified(
            $this->nc_app_password_env ?? null,
            $this->nc_app_password_ct ?? null,
            $this->nc_app_password_iv ?? null,
            $this->nc_app_password_tag ?? null
        );
        if ($decrypted_password === null) {
            return null;
        }

        return [
                'base_url' => $this->nc_base_url,
                'calendar_path' => $this->nc_calendar_path,
                'username' => $this->nc_username,
                'app_password' => $decrypted_password,
                'calendar_url' => rtrim($this->nc_base_url, '/') . '/' . ltrim($this->nc_calendar_path, '/')
        ];
    }
    
    /**
     * Normalize skills array to ensure consistent format
     * 
     * @param array $skills Raw skills array
     * @return array Normalized skills array
     */
    private function normalize_skills(array $skills): array {
        $normalized = [];
        
        foreach ($skills as $skill) {
            if (is_string($skill)) {
                // Legacy simple string - convert to skill object
                $normalized[] = [
                    'label' => $skill,
                    'slug' => td_tech_normalize_skill_slug($skill),
                    'level' => '',
                ];
            } elseif (is_array($skill) && isset($skill['label'])) {
                // Already skill object - ensure it has all required fields
                $normalized[] = [
                    'label' => $skill['label'],
                    'slug' => $skill['slug'] ?? td_tech_normalize_skill_slug($skill['label']),
                    'level' => $skill['level'] ?? '',
                ];
            }
        }
        
        return $normalized;
    }
    
    /**
     * Get skill labels for display
     * 
     * @return array Array of skill labels
     */
    public function get_skill_labels(): array {
        return td_tech_get_skill_labels($this->skills);
    }
    
    /**
     * Get skill slugs for matching
     * 
     * @return array Array of skill slugs
     */
    public function get_skill_slugs(): array {
        return td_tech_get_skill_slugs($this->skills);
    }
    
    /**
     * Check if staff has a specific skill
     * 
     * @param string $skill_search Skill to search for
     * @return bool True if has skill
     */
    public function has_skill(string $skill_search): bool {
        return td_tech_has_skill($this->skills, $skill_search);
    }
}

<?php
/**
 * TD Staff Crypto Helper
 * 
 * Handles encryption/decryption of sensitive data like CalDAV credentials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Encrypt plaintext using AES-256-GCM
 * 
 * @param string $plaintext The text to encrypt
 * @return array Array with keys: ct (ciphertext), iv (initialization vector), tag (authentication tag)
 * @throws Exception If encryption fails
 */
function td_tech_encrypt($plaintext) {
    $key = td_tech_get_encryption_key();
    $iv = random_bytes(16);
    $tag = '';
    
    $ciphertext = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );
    
    if ($ciphertext === false) {
        throw new Exception('Encryption failed');
    }
    
    return [
        'ct' => base64_encode($ciphertext),
        'iv' => base64_encode($iv),
        'tag' => base64_encode($tag)
    ];
}

/**
 * Decrypt ciphertext using AES-256-GCM
 * 
 * @param string $ciphertext_b64 Base64 encoded ciphertext
 * @param string $iv_b64 Base64 encoded initialization vector
 * @param string $tag_b64 Base64 encoded authentication tag
 * @return string The decrypted plaintext
 * @throws Exception If decryption fails
 */
function td_tech_decrypt($ciphertext_b64, $iv_b64, $tag_b64) {
    $key = td_tech_get_encryption_key();
    $ciphertext = base64_decode($ciphertext_b64);
    $iv = base64_decode($iv_b64);
    $tag = base64_decode($tag_b64);
    
    if ($ciphertext === false || $iv === false || $tag === false) {
        throw new Exception('Invalid base64 encoding');
    }
    
    $plaintext = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );
    
    if ($plaintext === false) {
        throw new Exception('Decryption failed');
    }
    
    return $plaintext;
}

/**
 * Get encryption key derived from WordPress salts
 * 
 * @return string 32-byte encryption key
 */
function td_tech_get_encryption_key() {
    // Derive key from WordPress security salts
    $salt_data = AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY;
    return hash('sha256', $salt_data, true);
}

/**
 * Check if encryption is available
 * 
 * @return bool
 */
function td_tech_encryption_available() {
    return function_exists('openssl_encrypt') && 
           in_array('aes-256-gcm', openssl_get_cipher_methods());
}

/**
 * NEW: Sodium-based encryption using XChaCha20-Poly1305 with envelope format
 * Envelope JSON:
 * { alg: "xchacha20poly1305-ietf", kid: "v1", c: base64(ciphertext+tag), n: base64(nonce) }
 */

/**
 * Get sodium key by key ID from environment/wp-config.
 * Define TD_KMS_KEY_V1 as base64:... in wp-config.php or env.
 */
function td_tech_get_sodium_key(string $kid = 'v1'): ?string {
    $const = 'TD_KMS_KEY_' . strtoupper($kid);
    $b64 = defined($const) ? constant($const) : getenv($const);
    if (!$b64) {
        return null;
    }
    // Support optional 'base64:' prefix
    if (str_starts_with($b64, 'base64:')) {
        $b64 = substr($b64, 7);
    }
    $key = base64_decode($b64, true);
    if ($key === false || strlen($key) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
        return null;
    }
    return $key;
}

/**
 * Encrypt plaintext with sodium XChaCha20-Poly1305 and return JSON envelope.
 */
function td_tech_sodium_encrypt_envelope(string $plaintext, string $kid = 'v1'): ?string {
    if (!function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')) {
        return null;
    }
    $key = td_tech_get_sodium_key($kid);
    if ($key === null) {
        return null;
    }
    $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
    $aad = '';
    $cipher = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, $aad, $nonce, $key);
    $env = [
        'alg' => 'xchacha20poly1305-ietf',
        'kid' => $kid,
        'c' => base64_encode($cipher),
        'n' => base64_encode($nonce),
    ];
    return wp_json_encode($env);
}

/**
 * Decrypt from envelope JSON (sodium). Returns plaintext or null on failure.
 */
function td_tech_sodium_decrypt_envelope(?string $envelope_json): ?string {
    if (empty($envelope_json) || !function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_decrypt')) {
        return null;
    }
    $env = json_decode($envelope_json, true);
    if (!is_array($env) || ($env['alg'] ?? '') !== 'xchacha20poly1305-ietf') {
        return null;
    }
    $kid = $env['kid'] ?? 'v1';
    $key = td_tech_get_sodium_key($kid);
    if ($key === null) {
        return null;
    }
    $cipher = base64_decode($env['c'] ?? '', true);
    $nonce = base64_decode($env['n'] ?? '', true);
    if ($cipher === false || $nonce === false) {
        return null;
    }
    $aad = '';
    $pt = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($cipher, $aad, $nonce, $key);
    return $pt === false ? null : $pt;
}

/**
 * Unified decrypt helper: try sodium envelope first, then legacy AES-GCM fields.
 */
function td_tech_decrypt_unified(?string $envelope_json, ?string $ct_b64, ?string $iv_b64, ?string $tag_b64): ?string {
    // Try sodium envelope
    $pt = td_tech_sodium_decrypt_envelope($envelope_json);
    if ($pt !== null) {
        return $pt;
    }
    // Fallback to legacy AES-GCM if available
    if (!empty($ct_b64) && !empty($iv_b64) && !empty($tag_b64)) {
        try {
            return td_tech_decrypt($ct_b64, $iv_b64, $tag_b64);
        } catch (Exception $e) {
            return null;
        }
    }
    return null;
}

/**
 * PII encryption helpers
 * - Separate key namespace for PII envelopes (emails/phones)
 * - Blind index using HMAC-SHA256 for lookups (e.g., email)
 */

/**
 * Get sodium key for PII envelopes (kid 'PII_V1'). Define TD_PII_ENC_KEY_V1 in wp-config or env.
 */
function td_tech_get_pii_sodium_key(string $kid = 'pii_v1'): ?string {
    return td_tech_get_sodium_key($kid);
}

/**
 * Encrypt PII with sodium and return envelope JSON. Falls back to null if sodium/key missing.
 */
function td_tech_pii_encrypt_envelope(string $plaintext, string $kid = 'pii_v1'): ?string {
    return td_tech_sodium_encrypt_envelope($plaintext, $kid);
}

/**
 * Decrypt PII envelope JSON to plaintext or null.
 */
function td_tech_pii_decrypt_envelope(?string $envelope_json): ?string {
    return td_tech_sodium_decrypt_envelope($envelope_json);
}

/**
 * Get blind index key for PII lookups. Define TD_PII_IDX_KEY_V1 in wp-config or env.
 */
function td_tech_get_pii_index_key(string $kid = 'v1'): ?string {
    $const = 'TD_PII_IDX_KEY_' . strtoupper($kid);
    $b64 = defined($const) ? constant($const) : getenv($const);
    if (!$b64) {
        return null;
    }
    if (str_starts_with($b64, 'base64:')) {
        $b64 = substr($b64, 7);
    }
    $key = base64_decode($b64, true);
    if ($key === false || strlen($key) < 32) { // allow larger keys, require at least 32 bytes
        return null;
    }
    return $key;
}

/**
 * Compute blind index (hex HMAC-SHA256) for emails.
 * Canonicalize by trim+lowercase.
 */
function td_tech_email_blind_index(string $email, string $kid = 'v1'): ?string {
    $key = td_tech_get_pii_index_key($kid);
    if ($key === null) return null;
    $canon = strtolower(trim($email));
    return hash_hmac('sha256', $canon, $key);
}

/**
 * Compute blind index for phone numbers.
 * If you want lookups, normalize to a simple canonical form first (digits only for now).
 */
function td_tech_phone_blind_index(string $phone, string $kid = 'v1'): ?string {
    $key = td_tech_get_pii_index_key($kid);
    if ($key === null) return null;
    $canon = preg_replace('/[^0-9+]/', '', trim($phone));
    return hash_hmac('sha256', $canon, $key);
}

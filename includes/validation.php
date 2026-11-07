<?php
/**
 * EstateVista India - Reusable Input Validation & Sanitization Suite
 * Defends against SQL Injection, XSS, and invalid payload formats.
 */

if (!function_exists('e')) {
    /**
     * Escape HTML output against XSS
     */
    function e($string): string {
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Deep sanitization for strings and arrays
 */
function sanitize_input($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize_input($value);
        }
        return $data;
    }
    return is_string($data) ? trim(strip_tags($data)) : $data;
}

/**
 * Validate required fields in an input array
 * Returns associative array of errors: ['field_name' => 'Field is required']
 */
function validate_required(array $data, array $requiredFields): array {
    $errors = [];
    foreach ($requiredFields as $field => $label) {
        $fieldName = is_numeric($field) ? $label : $field;
        $fieldLabel = is_numeric($field) ? ucwords(str_replace('_', ' ', $label)) : $label;

        if (!isset($data[$fieldName]) || trim((string)$data[$fieldName]) === '') {
            $errors[$fieldName] = "{$fieldLabel} is required.";
        }
    }
    return $errors;
}

/**
 * Validate email address format
 */
function validate_email(string $email): bool {
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    // Check reasonable length
    if (strlen($email) > 150) {
        return false;
    }
    return true;
}

/**
 * Validate Indian / International phone numbers
 * Accepts 10-digit Indian mobiles, optional +91 or 0 prefix, spaces, dashes
 */
function validate_phone(string $phone): bool {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    // Standard Indian numbers: 10 digits, or 11 with leading 0, or 12 with 91
    if (strlen($clean) === 10) {
        return in_array($clean[0], ['6', '7', '8', '9']);
    } elseif (strlen($clean) === 11 && $clean[0] === '0') {
        return in_array($clean[1], ['6', '7', '8', '9']);
    } elseif (strlen($clean) === 12 && substr($clean, 0, 2) === '91') {
        return in_array($clean[2], ['6', '7', '8', '9']);
    }
    // General international length check
    return strlen($clean) >= 8 && strlen($clean) <= 15;
}

/**
 * Validate numeric value and optional range
 */
function validate_number($value, ?float $min = null, ?float $max = null): bool {
    if (!is_numeric($value)) {
        return false;
    }
    $val = (float)$value;
    if ($min !== null && $val < $min) {
        return false;
    }
    if ($max !== null && $val > $max) {
        return false;
    }
    return true;
}

/**
 * Validate date format (e.g. Y-m-d, Y-m-d H:i:s)
 */
function validate_date(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate string against allowed enum list
 */
function validate_in_array($value, array $allowed): bool {
    return in_array($value, $allowed, true);
}

/**
 * Validate URL string
 */
function validate_url(string $url): bool {
    return (bool)filter_var($url, FILTER_VALIDATE_URL);
}

/**
 * Validate URL slug (lowercase alphanumeric and hyphens)
 */
function validate_slug(string $slug): bool {
    return (bool)preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
}

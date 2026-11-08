<?php
/**
 * EstateVista India - Configuration File
 * Premium Indian Real Estate Portal
 */

// Include guard to prevent duplicate definitions and session corruption
if (defined('ESTATEVISTA_CONFIG_LOADED')) {
    return;
}
define('ESTATEVISTA_CONFIG_LOADED', true);

// Start output buffering early so no unexpected whitespace/notice triggers "headers already sent"
if (!ob_get_level()) {
    ob_start();
}

// 1. Lightweight .env loader (zero external dependencies)
$env_file = dirname(__DIR__) . '/.env';
if (file_exists($env_file) && is_readable($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            // Strip surrounding quotes
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            if (!isset($_ENV[$name])) {
                $_ENV[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }
}

// 2. Application Environment & Secure Error Reporting
$is_render = (
    getenv('RENDER') !== false ||
    getenv('RENDER_EXTERNAL_URL') !== false ||
    getenv('RENDER_SERVICE_ID') !== false
);

$app_env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? ($is_render ? 'production' : 'development'));
if (!defined('APP_ENV')) {
    define('APP_ENV', $app_env);
}

if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}
date_default_timezone_set('Asia/Kolkata');

// 3. Database configuration (Environment-driven with safe fallbacks and DATABASE_URL support)
$db_url = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? null);
if (!empty($db_url) && (str_starts_with($db_url, 'mysql://') || str_starts_with($db_url, 'mysql2://'))) {
    $parsed_db = parse_url($db_url);
    if (!defined('DB_HOST')) define('DB_HOST', $parsed_db['host'] ?? 'localhost');
    if (!defined('DB_PORT')) define('DB_PORT', (string)($parsed_db['port'] ?? '3306'));
    if (!defined('DB_USER')) define('DB_USER', $parsed_db['user'] ?? 'root');
    if (!defined('DB_PASS')) define('DB_PASS', $parsed_db['pass'] ?? '');
    if (!defined('DB_NAME')) define('DB_NAME', ltrim($parsed_db['path'] ?? 'estatevista_db', '/'));
}

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost'));
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306'));
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'estatevista_db'));
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root'));
if (!defined('DB_PASS')) define('DB_PASS', (getenv('DB_PASS') !== false) ? getenv('DB_PASS') : ($_ENV['DB_PASS'] ?? 'root'));
if (!defined('DB_CHARSET')) define('DB_CHARSET', getenv('DB_CHARSET') ?: ($_ENV['DB_CHARSET'] ?? 'utf8mb4'));

// Brand details
if (!defined('SITE_NAME')) define('SITE_NAME', 'EstateVista India');
if (!defined('SITE_TAGLINE')) define('SITE_TAGLINE', "Curated Luxury Living Across India's Finest Addresses");
if (!defined('SITE_DESCRIPTION')) define('SITE_DESCRIPTION', "EstateVista India is the premier portal for ultra-luxury penthouses, golf estates, sea-facing residences, and heritage villas in Mumbai, Delhi NCR, Bengaluru, Hyderabad, and Goa.");
if (!defined('SITE_EMAIL')) define('SITE_EMAIL', 'concierge@estatevistaindia.com');
if (!defined('SITE_PHONE')) define('SITE_PHONE', '+91 22 6985 4000');
if (!defined('SITE_WHATSAPP')) define('SITE_WHATSAPP', '+91 98200 12345');
if (!defined('RERA_REGISTRATION')) define('RERA_REGISTRATION', 'MAHARERA Reg. No: A51900038921 | Certified Luxury Advisory');

// Dynamic Base URL detection (supporting Render, Cloudflare, standard SSL, and reverse proxies)
$is_https = (
    (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') ||
    (!empty($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], '"scheme":"https"'))
);
$protocol = $is_https ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$env_site_url = getenv('SITE_URL') ?: ($_ENV['SITE_URL'] ?? null);
$render_external_url = getenv('RENDER_EXTERNAL_URL') ?: ($_ENV['RENDER_EXTERNAL_URL'] ?? null);

if (!empty($env_site_url)) {
    $base_url = rtrim($env_site_url, '/') . '/';
} elseif (!empty($render_external_url)) {
    $base_url = rtrim($render_external_url, '/') . '/';
} else {
    $doc_root = !empty($_SERVER['DOCUMENT_ROOT']) && is_string($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
    $proj_root = str_replace('\\', '/', realpath(dirname(__DIR__)));

    if (!empty($doc_root) && strpos($proj_root, $doc_root) === 0) {
        $subfolder = substr($proj_root, strlen($doc_root));
    } else {
        $script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (preg_match('#^(/[^/]+?)(?:/admin|/customer|/includes|/config|/scratch|/[^/]+\.php|$)#i', $script_path, $m)) {
            $subfolder = $m[1];
        } else {
            if ($is_render || file_exists('/.dockerenv') || (DIRECTORY_SEPARATOR === '/' && $proj_root === '/var/www/html')) {
                $subfolder = '';
            } else {
                $subfolder = '/Real Estate Website';
            }
        }
    }
    $base_url = rtrim($protocol . $host . '/' . ltrim($subfolder, '/'), '/') . '/';
}

if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}

// Currency Symbol
if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', '₹');
}

// Top Prime Indian Cities for filtering
$PRIME_CITIES = [
    'mumbai' => [
        'name' => 'Mumbai',
        'state' => 'Maharashtra',
        'tagline' => 'Iconic Coastlines & High-Rise Mansions',
        'areas' => ['Worli Sea Face', 'Bandra West', 'Malabar Hill', 'Juhu Beach', 'Altamount Road'],
        'image' => 'https://images.unsplash.com/photo-1570168007204-dfb528c6958f?auto=format&fit=crop&w=800&q=80'
    ],
    'delhi-ncr' => [
        'name' => 'Delhi NCR',
        'state' => 'Delhi / Haryana',
        'tagline' => 'Expansive Golf Estates & Diplomatic Mansions',
        'areas' => ['Golf Course Road (Gurugram)', 'Chanakyapuri', 'Shanti Niketan', 'Golf Course Ext.', 'Aerocity'],
        'image' => 'https://images.unsplash.com/photo-1587474260584-136574528ed5?auto=format&fit=crop&w=800&q=80'
    ],
    'bengaluru' => [
        'name' => 'Bengaluru',
        'state' => 'Karnataka',
        'tagline' => 'Garden Mansions & Ultra-Tech Penthouses',
        'areas' => ['Indiranagar', 'Sadashivanagar', 'Lavelle Road', 'Whitefield', 'Koramangala'],
        'image' => 'https://images.unsplash.com/photo-1596176530529-78163a4f7af2?auto=format&fit=crop&w=800&q=80'
    ],
    'goa' => [
        'name' => 'Goa',
        'state' => 'Goa',
        'tagline' => 'Portuguese Villas & Oceanfront Sanctuaries',
        'areas' => ['Assagao', 'Anjuna', 'Candolim', 'Reis Magos', 'Morjim'],
        'image' => 'https://images.unsplash.com/photo-1512343879784-a960bf40e7f2?auto=format&fit=crop&w=800&q=80'
    ],
    'hyderabad' => [
        'name' => 'Hyderabad',
        'state' => 'Telangana',
        'tagline' => 'Regal Hill Estates & Financial District Towers',
        'areas' => ['Jubilee Hills', 'Banjara Hills', 'Gachibowli', 'Hitec City', 'Kokapet'],
        'image' => 'https://images.unsplash.com/photo-1605276374104-dee2a0ed3cd6?auto=format&fit=crop&w=800&q=80'
    ],
    'pune' => [
        'name' => 'Pune',
        'state' => 'Maharashtra',
        'tagline' => 'Verdant Enclaves & Contemporary Penthouses',
        'areas' => ['Koregaon Park', 'Kalyani Nagar', 'Boat Club Road', 'Baner', 'Aundh'],
        'image' => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=800&q=80'
    ]
];

// Luxury Categories
$LUXURY_CATEGORIES = [
    'penthouses' => ['name' => 'Sky Penthouses', 'icon' => 'fa-building-columns', 'desc' => 'Multi-level high-rise residences with private decks & infinity pools'],
    'villas' => ['name' => 'Golf & Ocean Villas', 'icon' => 'fa-house-chimney-window', 'desc' => 'Expansive gated estate villas with private lawns & curated landscapes'],
    'sea-facing' => ['name' => 'Sea-Facing Suites', 'icon' => 'fa-water', 'desc' => 'Unobstructed Arabian Sea & coastline panoramic residences'],
    'heritage' => ['name' => 'Heritage & Mansions', 'icon' => 'fa-landmark-dome', 'desc' => 'Architecturally distinguished colonial, royal & bespoke estates']
];

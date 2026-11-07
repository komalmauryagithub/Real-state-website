<?php
/**
 * EstateVista India - Dynamic XML Sitemap Generator
 * Automatically indexes all approved luxury listings, city hubs, and core landing pages.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getConnection();

// Core pages
$urls = [
    [
        'loc' => rtrim(BASE_URL, '/') . '/',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'daily',
        'priority' => '1.0'
    ],
    [
        'loc' => BASE_URL . 'properties.php',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'daily',
        'priority' => '0.9'
    ],
    [
        'loc' => BASE_URL . 'list-property.php',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'weekly',
        'priority' => '0.8'
    ]
];

// Prime Metro Hubs
$cities = ['mumbai', 'delhi-ncr', 'bengaluru', 'hyderabad', 'goa', 'pune'];
foreach ($cities as $c) {
    $urls[] = [
        'loc' => BASE_URL . 'properties.php?city=' . urlencode($c),
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'daily',
        'priority' => '0.8'
    ];
}

// Property Categories
$categories = ['penthouses', 'villas', 'apartments', 'plots', 'commercial'];
foreach ($categories as $cat) {
    $urls[] = [
        'loc' => BASE_URL . 'properties.php?type=' . urlencode($cat),
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'weekly',
        'priority' => '0.7'
    ];
}

// Dynamic Approved Properties from MySQL
if ($db) {
    try {
        $stmt = $db->query("SELECT id, slug, updated_at, created_at FROM properties WHERE approval_status = 'approved' ORDER BY id DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lastmod = !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : date('Y-m-d');
            $urls[] = [
                'loc' => BASE_URL . 'property-detail.php?slug=' . urlencode($row['slug']),
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.9'
            ];
        }
    } catch (Exception $e) {
        // Fallback gracefully
    }
}

// Generate XML content
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $u) {
    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
    $xml .= "    <lastmod>" . $u['lastmod'] . "</lastmod>\n";
    $xml .= "    <changefreq>" . $u['changefreq'] . "</changefreq>\n";
    $xml .= "    <priority>" . $u['priority'] . "</priority>\n";
    $xml .= "  </url>\n";
}

$xml .= '</urlset>';

// Save static snapshot to sitemap.xml on every run
@file_put_contents(__DIR__ . '/sitemap.xml', $xml);

// Output if accessed via web
if (php_sapi_name() !== 'cli') {
    header("Content-Type: application/xml; charset=utf-8");
}
echo $xml;

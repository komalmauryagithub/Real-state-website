<?php
/**
 * Header Component
 * EstateVista India
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

if (isset($page_title)) {
    $full_page_title = (strpos($page_title, SITE_NAME) !== false) ? $page_title : $page_title . ' | ' . SITE_NAME;
} else {
    $full_page_title = SITE_NAME . ' | ' . SITE_TAGLINE;
}
$page_desc = isset($page_desc) ? $page_desc : SITE_DESCRIPTION;

// Canonical URL Resolution
if (!isset($canonical_url)) {
    $req_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $canonical_url = rtrim(BASE_URL, '/') . '/' . ltrim(basename($req_path), '/');
    if (!empty($_SERVER['QUERY_STRING']) && basename($req_path) === 'properties.php') {
        // Retain meaningful search query params for properties listing
        $canonical_url .= '?' . $_SERVER['QUERY_STRING'];
    }
}

$og_type = $og_type ?? 'website';
$og_url = $og_url ?? $canonical_url;
$og_image = $og_image ?? (BASE_URL . 'assets/images/og-estatevista.jpg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="theme-color" content="#0B1528">
    
    <title><?= e($full_page_title) ?></title>
    <meta name="description" content="<?= e($page_desc) ?>">
    <meta name="keywords" content="Luxury Indian Real Estate, Mumbai Penthouses, Delhi NCR Golf Estates, Goa Luxury Villas, Bangalore Penthouses, RERA Verified Luxury Properties">
    <meta name="author" content="EstateVista India Private Limited">
    <meta name="robots" content="index, follow">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?= e($canonical_url) ?>">

    <!-- Open Graph Metadata -->
    <meta property="og:locale" content="en_IN">
    <meta property="og:type" content="<?= e($og_type) ?>">
    <meta property="og:title" content="<?= e($full_page_title) ?>">
    <meta property="og:description" content="<?= e($page_desc) ?>">
    <meta property="og:url" content="<?= e($og_url) ?>">
    <meta property="og:site_name" content="<?= SITE_NAME ?>">
    <meta property="og:image" content="<?= e($og_image) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <!-- Twitter Card Metadata -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@EstateVistaIN">
    <meta name="twitter:title" content="<?= e($full_page_title) ?>">
    <meta name="twitter:description" content="<?= e($page_desc) ?>">
    <meta name="twitter:image" content="<?= e($og_image) ?>">

    <!-- Luxury Google Fonts: Cormorant Garamond, Cinzel, Playfair Display & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,400;1,600&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Core Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/listing.css?v=<?= filemtime(__DIR__ . '/../assets/css/listing.css') ?>">
    <?php if (!empty($extra_css)): ?>
        <?php if (is_array($extra_css)): ?>
            <?php foreach ($extra_css as $css_url): ?>
                <link rel="stylesheet" href="<?= e($css_url) ?>">
            <?php endforeach; ?>
        <?php else: ?>
            <link rel="stylesheet" href="<?= e($extra_css) ?>">
        <?php endif; ?>
    <?php endif; ?>

    <!-- Favicon (Inline SVG Luxury Emblem) -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='18' fill='%230B1528'/><text x='50%' y='62%' font-size='42' font-family='serif' font-weight='bold' fill='%23C5A880' text-anchor='middle'>EV</text></svg>">

    <!-- Schema.org Structured Data -->
    <?php if (!empty($custom_json_ld)): ?>
        <script type="application/ld+json">
        <?= is_string($custom_json_ld) ? $custom_json_ld : json_encode($custom_json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
        </script>
    <?php else: ?>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "RealEstateAgent",
            "name": "<?= SITE_NAME ?>",
            "description": "<?= addslashes(SITE_DESCRIPTION) ?>",
            "url": "<?= BASE_URL ?>",
            "logo": "<?= BASE_URL ?>assets/images/logo.png",
            "telephone": "<?= SITE_PHONE ?>",
            "email": "<?= SITE_EMAIL ?>",
            "address": {
                "@type": "PostalAddress",
                "streetAddress": "Tower 2, One World Center, Lower Parel",
                "addressLocality": "Mumbai",
                "addressRegion": "Maharashtra",
                "postalCode": "400013",
                "addressCountry": "IN"
            },
            "areaServed": [
                "Mumbai",
                "Delhi NCR",
                "Bengaluru",
                "Hyderabad",
                "Goa",
                "Pune"
            ],
            "priceRange": "₹5 Cr - ₹150 Cr",
            "knowsAbout": [
                "Luxury Real Estate Advisory",
                "Ultra-Prime Penthouses",
                "Golf Estates",
                "Sea Facing Residences",
                "RERA Compliance"
            ]
        }
        </script>
    <?php endif; ?>

    <script>
        window.BASE_URL = <?= json_encode(BASE_URL) ?>;
    </script>
</head>
<body>

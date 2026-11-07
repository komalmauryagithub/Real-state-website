<?php
/**
 * EstateVista India - Individual Property Details Experience
 * Dynamic luxury residence showcase, interactive gallery lightbox,
 * complete architectural specifications, floor plans, neighborhood connectivity,
 * and high-priority lead acquisition interface.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// -----------------------------------------------------------------------------
// 1. Resolve Property from GET parameter (slug or id)
// -----------------------------------------------------------------------------
$identifier = isset($_GET['slug']) ? trim($_GET['slug']) : (isset($_GET['id']) ? trim($_GET['id']) : '');

// If neither passed, fallback to first flagship residence
if (empty($identifier)) {
    $identifier = 'the-sky-crest-penthouse-worli-sea-face';
}

$current_uid = is_logged_in() ? get_logged_in_user_id() : null;
$property = get_property_by_slug_or_id($identifier, $current_uid);

// -----------------------------------------------------------------------------
// 2. Handle Wishlist Toggle & Inquiry Form Submissions (AJAX & Standard POST)
// -----------------------------------------------------------------------------
$inquiry_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
               || (isset($_GET['action']) && $_GET['action'] === 'inquire');

    // Handle AJAX wishlist toggle from property detail
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_wishlist') {
        if (!is_logged_in()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'need_login' => true, 'message' => 'Please sign in to save residences to your private portfolio.']);
            exit;
        }
        $pid = (int)($_POST['property_id'] ?? ($property['id'] ?? 0));
        $res = toggle_user_saved_property(get_logged_in_user_id(), $pid);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($res);
        exit;
    }

    $post_data = [
        'user_id'        => is_logged_in() ? get_logged_in_user_id() : null,
        'property_id'    => $property ? $property['id'] : ($_POST['property_id'] ?? null),
        'inquiry_type'   => $_POST['inquiry_type'] ?? 'enquiry',
        'full_name'      => $_POST['full_name'] ?? '',
        'email'          => $_POST['email'] ?? '',
        'phone'          => $_POST['phone'] ?? '',
        'city'           => $_POST['city'] ?? ($property['city_name'] ?? ''),
        'budget_range'   => $_POST['budget_range'] ?? ($property ? $property['price_display'] : ''),
        'preferred_time' => $_POST['preferred_time'] ?? '',
        'visit_date'     => $_POST['visit_date'] ?? null,
        'visit_time'     => $_POST['visit_time'] ?? '',
        'message'        => $_POST['message'] ?? ''
    ];

    $inquiry_result = record_property_inquiry($post_data);

    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($inquiry_result);
        exit;
    }
}

// -----------------------------------------------------------------------------
// 3. Fallback / 404 Guard if Property Not Found
// -----------------------------------------------------------------------------
if (!$property) {
    $page_title = 'Residence Not Found | ' . SITE_NAME;
    require_once __DIR__ . '/includes/header.php';
    require_once __DIR__ . '/includes/navbar.php';
    ?>
    <main class="prop-detail-page" style="padding: 6rem 0; text-align: center;">
        <div class="container">
            <div style="max-width: 600px; margin: 0 auto;">
                <div style="font-size: 3rem; color: var(--gold-500); margin-bottom: 1rem;">
                    <i class="fa-solid fa-compass-drafting"></i>
                </div>
                <h1 style="font-family: var(--font-serif); font-size: 2.2rem; color: var(--navy-900); margin-bottom: 1rem;">
                    Residence Not Located
                </h1>
                <p style="color: var(--slate-600); margin-bottom: 2rem; line-height: 1.6;">
                    The residence or penthouse you are seeking may have been leased, privately acquired under non-disclosure, or its identifier has been updated.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="<?= BASE_URL ?>properties.php" class="btn btn-primary">
                        <i class="fa-solid fa-list-ul"></i> Explore Active Listings
                    </a>
                    <a href="<?= BASE_URL ?>" class="btn btn-outline">
                        <i class="fa-solid fa-house"></i> Return to Homepage
                    </a>
                </div>
            </div>
        </div>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// -----------------------------------------------------------------------------
// 4. Data Preparation & Curation
// -----------------------------------------------------------------------------
$prop_title = $property['title'] ?? 'Luxury Residence';
$prop_subtitle = $property['subtitle'] ?? '';
$prop_price = (float)($property['price'] ?? 0);
$prop_price_formatted = !empty($property['price_display']) ? $property['price_display'] : format_inr_price($prop_price);
$prop_purpose = ucfirst($property['purpose'] ?? 'Buy');
$prop_locality = $property['locality'] ?? '';
$prop_address = $property['address'] ?? ($prop_locality . ', ' . ($property['city_name'] ?? ''));
$prop_city = $property['city_name'] ?? 'India';
$prop_state = $property['city_state'] ?? '';
$prop_carpet = (int)($property['carpet_area_sqft'] ?? 0);
$prop_builtup = (int)($property['builtup_area_sqft'] ?? round($prop_carpet * 1.25));
$prop_bhk = (int)($property['bhk'] ?? 0);
$prop_baths = (int)($property['bathrooms'] ?? 0);
$prop_balconies = (int)($property['balconies'] ?? 2);
$prop_floor = !empty($property['floor_number']) ? $property['floor_number'] : 'High Floor';
$prop_total_floors = !empty($property['total_floors']) ? $property['total_floors'] : 45;
$prop_facing = !empty($property['facing']) ? $property['facing'] : 'North-East (Vaastu Compliant)';
$prop_parking = !empty($property['parking_spaces']) ? (int)$property['parking_spaces'] : 2;
$prop_furnishing = ucfirst(str_replace('-', ' ', $property['furnishing'] ?? 'bespoke-furnished'));
$prop_status = str_replace('_', ' ', $property['status'] ?? 'ready_to_move');
$prop_possession = !empty($property['possession_date']) ? $property['possession_date'] : 'Immediate Possession';
$prop_rera = !empty($property['rera_number']) ? $property['rera_number'] : 'RERA-VERIFIED-EXEMPT';
$prop_developer = $property['developer_name'] ?? 'Private Sovereign Promoter';
$prop_developer_year = $property['developer_year'] ?? 1995;

// Price per sqft calculation
$price_per_sqft = ($prop_carpet > 0) ? round($prop_price / $prop_carpet) : 0;
$price_per_sqft_str = ($price_per_sqft > 0) ? '₹ ' . format_inr_number($price_per_sqft) . ' / sq.ft (Carpet)' : '';

// Gallery curation: ensure at least 5 luxury photos for the 5-photo collage
$gallery_list = get_property_gallery_images($property);

// Luxury architectural fillers if fewer than 6 photos
$curated_fillers = [
    'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80'
];

foreach ($curated_fillers as $f) {
    if (count($gallery_list) >= 6) break;
    if (!in_array($f, $gallery_list)) {
        $gallery_list[] = $f;
    }
}

// Floor plan image
$floor_plan_img = !empty($property['floor_plan_image']) 
    ? $property['floor_plan_image'] 
    : 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=80';

// Agent Details
$agent_name = !empty($property['agent_name']) ? $property['agent_name'] : 'Aditya Vardhan Birla';
$agent_title = !empty($property['agent_title']) ? $property['agent_title'] : 'Senior Partner - Ultra-Luxury Acquisitions';
$agent_phone = !empty($property['agent_phone']) ? $property['agent_phone'] : SITE_PHONE;
$agent_email = !empty($property['agent_email']) ? $property['agent_email'] : SITE_EMAIL;
$agent_avatar = !empty($property['agent_avatar']) ? $property['agent_avatar'] : 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80';
$agent_exp = !empty($property['agent_experience']) ? $property['agent_experience'] : '16+ Years in Prime Real Estate';

// Nearby Landmarks
$nearby_landmarks = get_property_nearby_places($property);

// Similar Residences
$similar_properties = get_similar_properties($property['id'], $property['city_id'], $property['category_id'], 3);

// WhatsApp prefilled message
$wa_phone = preg_replace('/[^0-9]/', '', $agent_phone);
if (strlen($wa_phone) === 10) $wa_phone = '91' . $wa_phone;
$wa_text = rawurlencode("Hello EstateVista India, I am interested in viewing \"" . $prop_title . "\" at " . $prop_locality . " (" . $prop_price_formatted . "). Please share the detailed dossier and schedule a consultation.");
$wa_url = "https://wa.me/" . $wa_phone . "?text=" . $wa_text;

// Google Maps Embed URL
$map_query = rawurlencode($prop_address . ', ' . $prop_city . ', India');
$map_embed_url = !empty($property['map_embed_url']) 
    ? $property['map_embed_url'] 
    : "https://maps.google.com/maps?q={$map_query}&t=&z=15&ie=UTF8&iwloc=&output=embed";

// Page metadata & assets
$logged_in_client = get_logged_in_user();
$client_name = $logged_in_client['full_name'] ?? '';
$client_phone = $logged_in_client['phone'] ?? '';
$client_email = $logged_in_client['email'] ?? '';
$is_saved_in_db = ($logged_in_client && !empty($property['id'])) ? is_property_saved_by_user($logged_in_client['id'], (int)$property['id']) : false;

$page_title = $prop_title . ' | ' . $prop_locality . ', ' . $prop_city;
$page_desc = substr(strip_tags($property['description'] ?? ''), 0, 160);
$extra_css = BASE_URL . 'assets/css/detail.css';
$extra_js = BASE_URL . 'assets/js/detail.js';

// SEO & Structured Data Enhancements
$og_type = 'article';
$og_image = !empty($property['cover_image']) ? $property['cover_image'] : null;
$canonical_url = BASE_URL . 'property-detail.php?slug=' . urlencode($property['slug']);

$custom_json_ld = [
    '@context' => 'https://schema.org',
    '@type' => 'RealEstateListing',
    'name' => $prop_title . ' | ' . $prop_locality . ', ' . $prop_city,
    'description' => substr(strip_tags($property['description'] ?? ''), 0, 250),
    'url' => $canonical_url,
    'image' => array_values(array_slice($gallery_list, 0, 5)),
    'datePosted' => $property['created_at'] ?? date('Y-m-d'),
    'price' => (string)$prop_price,
    'priceCurrency' => 'INR',
    'category' => $property['category_name'] ?? 'Luxury Residence',
    'offers' => [
        '@type' => 'Offer',
        'price' => (string)$prop_price,
        'priceCurrency' => 'INR',
        'availability' => 'https://schema.org/InStock',
        'validFrom' => $property['created_at'] ?? date('Y-m-d')
    ],
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $prop_locality,
        'addressLocality' => $prop_city,
        'addressRegion' => $prop_state ?: $prop_city,
        'addressCountry' => 'IN'
    ],
    'amenityFeature' => array_map(function($a) {
        $name = is_array($a) ? ($a['name'] ?? 'Luxury Amenity') : (string)$a;
        return ['@type' => 'LocationFeatureSpecification', 'name' => $name, 'value' => true];
    }, array_slice($property['amenities'] ?? [], 0, 10))
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="prop-detail-page">
    <?php if (($property['approval_status'] ?? 'approved') !== 'approved'): ?>
        <div style="background: #FFFBEB; border-bottom: 1px solid #FDE68A; padding: 1rem 0; color: #92400E;">
            <div class="container" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fa-solid fa-clock-rotate-left" style="font-size: 1.25rem; color: #D97706;"></i>
                    <div>
                        <?php if ($property['approval_status'] === 'pending'): ?>
                            <strong>Private Listing Preview (Pending Review):</strong> This residence is currently undergoing editorial and title curation. It is visible only to you as the listing owner.
                        <?php elseif ($property['approval_status'] === 'rejected'): ?>
                            <strong>Curation Notice (Revisions Requested):</strong> <?= e($property['rejection_reason'] ?: 'Please update the requested property information.') ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>edit-property.php?id=<?= (int)$property['id'] ?>" class="btn btn-sm btn-primary" style="white-space: nowrap; font-size: 0.85rem; padding: 0.4rem 0.9rem;">
                        <i class="fa-solid fa-pen-to-square"></i> Edit Listing
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Breadcrumbs Navigation -->
    <nav class="prop-breadcrumbs-bar" aria-label="Breadcrumbs">
        <div class="container">
            <div class="prop-breadcrumbs">
                <a href="<?= BASE_URL ?>"><i class="fa-solid fa-house"></i> Home</a>
                <span class="separator"><i class="fa-solid fa-chevron-right"></i></span>
                <a href="<?= BASE_URL ?>properties.php">Properties</a>
                <span class="separator"><i class="fa-solid fa-chevron-right"></i></span>
                <a href="<?= BASE_URL ?>properties.php?city=<?= urlencode(strtolower($prop_city)) ?>"><?= e($prop_city) ?></a>
                <span class="separator"><i class="fa-solid fa-chevron-right"></i></span>
                <span class="current"><?= e($prop_title) ?></span>
            </div>
        </div>
    </nav>

    <!-- Property Header & Action Ribbon -->
    <section class="prop-header-section">
        <div class="container">
            <div class="prop-header-top">
                <div>
                    <div class="prop-header-badges">
                        <span class="prop-badge prop-badge-purpose">For <?= e($prop_purpose) ?></span>
                        <?php if (!empty($property['is_verified'])): ?>
                            <span class="prop-badge prop-badge-verified"><i class="fa-solid fa-circle-check"></i> RERA Verified</span>
                        <?php endif; ?>
                        <?php if (!empty($property['is_exclusive'])): ?>
                            <span class="prop-badge prop-badge-exclusive"><i class="fa-solid fa-crown"></i> Exclusive Portfolio</span>
                        <?php endif; ?>
                        <?php if (!empty($property['is_featured'])): ?>
                            <span class="prop-badge prop-badge-featured"><i class="fa-solid fa-star"></i> Featured</span>
                        <?php endif; ?>
                    </div>
                    <h1 class="prop-title"><?= e($prop_title) ?></h1>
                    <?php if (!empty($prop_subtitle)): ?>
                        <p class="prop-subtitle"><?= e($prop_subtitle) ?></p>
                    <?php endif; ?>
                    <div class="prop-address">
                        <i class="fa-solid fa-location-dot"></i>
                        <span><?= e($prop_address) ?></span>
                    </div>
                </div>

                <!-- Quick Action Buttons -->
                <div class="prop-header-actions">
                    <button type="button" class="prop-action-btn" id="btnShareProperty" title="Share this residence">
                        <i class="fa-solid fa-arrow-up-from-bracket"></i>
                        <span>Share</span>
                    </button>
                    <button type="button" class="prop-action-btn <?= $is_saved_in_db ? 'active-saved' : '' ?>" id="btnSaveWishlist" data-prop-id="<?= (int)$property['id'] ?>" title="Save to private portfolio">
                        <i class="<?= $is_saved_in_db ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                        <span><?= $is_saved_in_db ? 'Saved in Portfolio' : 'Save Property' ?></span>
                    </button>
                    <a href="tel:<?= preg_replace('/[^0-9+]/', '', $agent_phone) ?>" class="prop-action-btn" title="Call Senior Acquisition Advisor">
                        <i class="fa-solid fa-phone"></i>
                        <span>Call Advisor</span>
                    </a>
                </div>
            </div>

            <!-- Pricing Bar & Quick Specs Strip -->
            <div class="prop-header-pricing">
                <div>
                    <div class="prop-price-box">
                        <span class="prop-main-price"><?= e($prop_price_formatted) ?></span>
                        <?php if (!empty($price_per_sqft_str)): ?>
                            <span class="prop-price-sqft">(<?= e($price_per_sqft_str) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <div class="prop-price-tax-note">Government stamp duty, GST & registration charges applicable as per state norms</div>
                </div>

                <div class="prop-quick-specs-ribbon">
                    <div class="quick-spec-item">
                        <i class="fa-solid fa-bed"></i>
                        <span><?= (int)$prop_bhk ?> BHK Layout</span>
                    </div>
                    <div class="quick-spec-item">
                        <i class="fa-solid fa-bath"></i>
                        <span><?= (int)$prop_baths ?> Bathrooms</span>
                    </div>
                    <div class="quick-spec-item">
                        <i class="fa-solid fa-vector-square"></i>
                        <span><?= format_inr_number($prop_carpet) ?> sq.ft (Carpet)</span>
                    </div>
                    <div class="quick-spec-item">
                        <i class="fa-solid fa-building"></i>
                        <span><?= e($prop_floor) ?></span>
                    </div>
                    <div class="quick-spec-item">
                        <i class="fa-solid fa-key"></i>
                        <span><?= e($prop_possession) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Large Image Gallery Mosaic & Fullscreen Lightbox Section -->
    <section class="prop-gallery-section">
        <div class="container">
            <div class="prop-gallery-mosaic">
                <!-- Main Large Photo (Tile 1) -->
                <div class="gallery-tile gallery-tile-main" data-gallery-src="<?= e($gallery_list[0]) ?>" data-gallery-idx="0" data-gallery-caption="<?= e($prop_title) ?> - Exterior Elevation">
                    <img src="<?= e($gallery_list[0]) ?>" alt="<?= e($prop_title) ?> - Primary View" loading="eager" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                    <div class="gallery-tile-overlay">
                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                    </div>
                </div>

                <!-- 4 Thumbnail Photos in 2x2 Grid -->
                <div class="gallery-tile-grid">
                    <?php for ($i = 1; $i <= 4; $i++): 
                        $img_src = $gallery_list[$i] ?? $gallery_list[0];
                        $is_last = ($i === 4);
                        $captions = ['Living & Dining Hall', 'Ocean & Skyline View', 'Primary Master Suite', 'Private Infinity Pool & Deck'];
                        $cap = $captions[$i - 1] ?? 'Residence Interior';
                    ?>
                        <div class="gallery-tile <?= $is_last ? 'gallery-more-tile' : '' ?>" data-gallery-src="<?= e($img_src) ?>" data-gallery-idx="<?= $i ?>" data-gallery-caption="<?= e($prop_title) ?> - <?= e($cap) ?>">
                            <img src="<?= e($img_src) ?>" alt="<?= e($prop_title) ?> - <?= e($cap) ?>" loading="lazy" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                            <?php if ($is_last): ?>
                                <div class="gallery-more-overlay">
                                    <i class="fa-solid fa-camera"></i>
                                    <span>View All <?= count($gallery_list) ?> Photos</span>
                                </div>
                            <?php else: ?>
                                <div class="gallery-tile-overlay">
                                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>

                <!-- Floating Bottom Trigger Button -->
                <button type="button" class="gallery-view-all-btn" data-gallery-src="<?= e($gallery_list[0]) ?>" data-gallery-idx="0">
                    <i class="fa-solid fa-images"></i>
                    <span>Explore Full Gallery (<?= count($gallery_list) ?>)</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Main Content & Sticky Inquiry Sidebar Layout -->
    <div class="container">
        <div class="prop-detail-layout">
            <!-- Left Main Column (65%) -->
            <div class="prop-content-main">
                <!-- 1. Key Specifications Grid -->
                <div class="detail-card">
                    <h2 class="detail-section-title">
                        <span>Architectural Dimensions</span>
                        Property Specifications
                    </h2>
                    <div class="specs-matrix">
                        <div class="spec-tile">
                            <div class="spec-icon-box"><i class="fa-solid fa-bed"></i></div>
                            <div class="spec-meta">
                                <span class="spec-label">Configuration</span>
                                <span class="spec-value"><?= (int)$prop_bhk ?> BHK Luxury Residence</span>
                            </div>
                        </div>

                        <div class="spec-tile">
                            <div class="spec-icon-box"><i class="fa-solid fa-bath"></i></div>
                            <div class="spec-meta">
                                <span class="spec-label">Bathrooms</span>
                                <span class="spec-value"><?= (int)$prop_baths ?> En-Suite Baths</span>
                            </div>
                        </div>

                        <div class="spec-tile">
                            <div class="spec-icon-box"><i class="fa-solid fa-sun"></i></div>
                            <div class="spec-meta">
                                <span class="spec-label">Sky Decks / Balconies</span>
                                <span class="spec-value"><?= (int)$prop_balconies ?> Panoramic Decks</span>
                            </div>
                        </div>

                        <div class="spec-tile">
                            <div class="spec-icon-box"><i class="fa-solid fa-ruler-combined"></i></div>
                            <div class="spec-meta">
                                <span class="spec-label">Carpet Area (RERA)</span>
                                <span class="spec-value"><?= format_inr_number($prop_carpet) ?> sq.ft</span>
                            </div>
                        </div>

                        <div class="spec-tile">
                            <div class="spec-icon-box"><i class="fa-solid fa-vector-square"></i></div>
                            <div class="spec-meta">
                                <span class="spec-label">Super Built-Up Area</span>
                                <span class="spec-value"><?= format_inr_number($prop_builtup) ?> sq.ft</span>
                            </div>
                        </div>

                        <div class="spec-tile">
                            <div class="spec-icon-box"><i class="fa-solid fa-stairs"></i></div>
                            <div class="spec-meta">
                                <span class="spec-label">Floor Position</span>
                                <span class="spec-value"><?= e($prop_floor) ?> (Total <?= (int)$prop_total_floors ?> Floors)</span>
                            </div>
                        </div>

                        <div class="spec-tile">
                            <div class="spec-icon-box"><i class="fa-solid fa-compass"></i></div>
                            <div class="spec-meta">
                                <span class="spec-label">Vaastu & Orientation</span>
                                <span class="spec-value"><?= e($prop_facing) ?></span>
                            </div>
                        </div>

                        <div class="spec-tile">
                            <div class="spec-icon-box"><i class="fa-solid fa-couch"></i></div>
                            <div class="spec-meta">
                                <span class="spec-label">Furnishing Status</span>
                                <span class="spec-value"><?= e($prop_furnishing) ?></span>
                            </div>
                        </div>

                        <div class="spec-tile">
                            <div class="spec-icon-box"><i class="fa-solid fa-car"></i></div>
                            <div class="spec-meta">
                                <span class="spec-label">Dedicated Parking</span>
                                <span class="spec-value"><?= (int)$prop_parking ?> Reserved Covered Bays</span>
                            </div>
                        </div>

                        <div class="spec-tile">
                            <div class="spec-icon-box"><i class="fa-solid fa-hourglass-half"></i></div>
                            <div class="spec-meta">
                                <span class="spec-label">Possession Status</span>
                                <span class="spec-value"><?= e($prop_possession) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Property Description & Highlights -->
                <div class="detail-card">
                    <h2 class="detail-section-title">
                        <span>Editorial Curation</span>
                        Residence Overview & Character
                    </h2>

                    <div class="prop-description-lead">
                        "<?= e($prop_subtitle ?: 'A sanctuary of modern palatial elegance crafted for those who command the peak of Indian real estate.') ?>"
                    </div>

                    <div class="prop-description-text">
                        <?= nl2br(e($property['description'] ?? 'An architectural marvel set in the epicenter of prime luxury. Featuring high-volume double ceilings, panoramic floor-to-ceiling soundproof glazing, and bespoke Italian interior finishes.')) ?>
                    </div>

                    <div class="prop-highlights-list">
                        <div class="prop-highlight-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Triple-height grand entrance foyer with private biometric elevator lobby.</span>
                        </div>
                        <div class="prop-highlight-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Unobstructed horizon vistas with certified seismic-compliant engineering.</span>
                        </div>
                        <div class="prop-highlight-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Bespoke master suite with Italian marble double-vanity dressing chambers.</span>
                        </div>
                        <div class="prop-highlight-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Fully automated smart residence with Crestron climate and ambient lighting controls.</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Amenities and Facilities -->
                <div class="detail-card">
                    <h2 class="detail-section-title">
                        <span>Lifestyle Amenities</span>
                        Exclusive Facilities & Privileges
                    </h2>

                    <div class="amenities-grid">
                        <?php if (!empty($property['amenities']) && is_array($property['amenities'])): ?>
                            <?php foreach ($property['amenities'] as $am): ?>
                                <div class="amenity-card">
                                    <div class="amenity-icon-circle">
                                        <i class="fa-solid <?= !empty($am['icon_class']) ? e($am['icon_class']) : 'fa-gem' ?>"></i>
                                    </div>
                                    <div>
                                        <div class="amenity-name"><?= e($am['name']) ?></div>
                                        <div class="amenity-category-tag"><?= e($am['category'] ?? 'Luxury') ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback standard luxury amenities -->
                            <?php 
                            $default_amenities = [
                                ['name' => 'Private Temperature Pool', 'cat' => 'Wellness', 'icon' => 'fa-person-swimming'],
                                ['name' => '24/7 Dedicated Concierge', 'cat' => 'Service', 'icon' => 'fa-bell-concierge'],
                                ['name' => 'Biometric High-Speed Lift', 'cat' => 'Privacy', 'icon' => 'fa-elevator'],
                                ['name' => 'Italian Statuario Marble', 'cat' => 'Finishes', 'icon' => 'fa-gem'],
                                ['name' => 'Sub-Zero & Miele Kitchen', 'cat' => 'Kitchen', 'icon' => 'fa-kitchen-set'],
                                ['name' => 'Smart Home Automation', 'cat' => 'Tech', 'icon' => 'fa-microchip'],
                                ['name' => 'Thermal Spa & Steam Suite', 'cat' => 'Wellness', 'icon' => 'fa-spa'],
                                ['name' => 'Multi-Tier Armed Security', 'cat' => 'Security', 'icon' => 'fa-shield-halved']
                            ];
                            foreach ($default_amenities as $da): ?>
                                <div class="amenity-card">
                                    <div class="amenity-icon-circle"><i class="fa-solid <?= $da['icon'] ?>"></i></div>
                                    <div>
                                        <div class="amenity-name"><?= $da['name'] ?></div>
                                        <div class="amenity-category-tag"><?= $da['cat'] ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 4. Floor Plan Section -->
                <div class="detail-card">
                    <h2 class="detail-section-title">
                        <span>Architectural Schematic</span>
                        Floor Plan & Layout Dimensions
                    </h2>

                    <div class="floor-plan-container">
                        <div class="floor-plan-preview" id="floorPlanTrigger" data-plan-src="<?= e(safe_image_url($floor_plan_img, 'floorplan')) ?>" title="Click to view high-resolution blueprint">
                            <img src="<?= e(safe_image_url($floor_plan_img, 'floorplan')) ?>" alt="<?= e($prop_title) ?> - Floor Plan" loading="lazy" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/floorplan-placeholder.svg';">
                            <div class="floor-plan-zoom-overlay">
                                <i class="fa-solid fa-expand"></i>
                                <span style="font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Expand Blueprint Schematic</span>
                            </div>
                        </div>

                        <div class="floor-plan-details-table">
                            <div class="floor-plan-stat-box">
                                <div class="title">Grand Living & Dining</div>
                                <div class="val">1,250 sq.ft</div>
                            </div>
                            <div class="floor-plan-stat-box">
                                <div class="title">Primary Suite Chamber</div>
                                <div class="val">1,100 sq.ft</div>
                            </div>
                            <div class="floor-plan-stat-box">
                                <div class="title">Wrap-Around Sky Deck</div>
                                <div class="val">480 sq.ft</div>
                            </div>
                            <div class="floor-plan-stat-box">
                                <div class="title">Chef’s Island Kitchen</div>
                                <div class="val">380 sq.ft</div>
                            </div>
                        </div>

                        <div class="floor-plan-cta-bar">
                            <div style="color: var(--slate-300); font-size: 0.825rem;">
                                <i class="fa-solid fa-circle-info" style="color: var(--gold-400); margin-right: 0.35rem;"></i>
                                All structural layouts and carpet dimensions conform to RERA verified architectural drawings.
                            </div>
                            <button type="button" class="btn btn-outline" style="border-color: var(--gold-500); color: var(--gold-300); font-size: 0.8rem; padding: 0.5rem 1rem;" onclick="document.getElementById('floorPlanTrigger').click();">
                                <i class="fa-solid fa-magnifying-glass-plus"></i> View High-Res Blueprint
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 5. Location & Neighborhood Connectivity -->
                <div class="detail-card">
                    <h2 class="detail-section-title">
                        <span>Prime Neighborhood</span>
                        Location & Infrastructure Access
                    </h2>

                    <!-- Google Maps Embed -->
                    <div class="map-embed-wrapper">
                        <iframe src="<?= e($map_embed_url) ?>" loading="lazy" allowfullscreen="" referrerpolicy="no-referrer-when-downgrade" title="Residence Map Location"></iframe>
                    </div>

                    <!-- Categorized Nearby Landmarks -->
                    <div class="nearby-categories-grid">
                        <!-- Schools -->
                        <div class="nearby-cat-group">
                            <div class="nearby-cat-header">
                                <i class="fa-solid fa-graduation-cap"></i>
                                <span>Premier Schools & Academies</span>
                            </div>
                            <ul class="nearby-items-list">
                                <?php foreach ($nearby_landmarks['schools'] as $item): ?>
                                    <li class="nearby-item">
                                        <span class="nearby-item-name"><?= e($item['name']) ?></span>
                                        <span class="nearby-item-dist"><?= e($item['distance']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Healthcare -->
                        <div class="nearby-cat-group">
                            <div class="nearby-cat-header">
                                <i class="fa-solid fa-hospital"></i>
                                <span>Super-Speciality Hospitals</span>
                            </div>
                            <ul class="nearby-items-list">
                                <?php foreach ($nearby_landmarks['hospitals'] as $item): ?>
                                    <li class="nearby-item">
                                        <span class="nearby-item-name"><?= e($item['name']) ?></span>
                                        <span class="nearby-item-dist"><?= e($item['distance']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Transit -->
                        <div class="nearby-cat-group">
                            <div class="nearby-cat-header">
                                <i class="fa-solid fa-train-subway"></i>
                                <span>Transit & Expressways</span>
                            </div>
                            <ul class="nearby-items-list">
                                <?php foreach ($nearby_landmarks['transit'] as $item): ?>
                                    <li class="nearby-item">
                                        <span class="nearby-item-name"><?= e($item['name']) ?></span>
                                        <span class="nearby-item-dist"><?= e($item['distance']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Airports -->
                        <div class="nearby-cat-group">
                            <div class="nearby-cat-header">
                                <i class="fa-solid fa-plane-departure"></i>
                                <span>International & VIP Aviation</span>
                            </div>
                            <ul class="nearby-items-list">
                                <?php foreach ($nearby_landmarks['airports'] as $item): ?>
                                    <li class="nearby-item">
                                        <span class="nearby-item-name"><?= e($item['name']) ?></span>
                                        <span class="nearby-item-dist"><?= e($item['distance']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- 6. Official RERA Compliance & Developer Background -->
                <div class="rera-verification-box">
                    <div class="rera-badge-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div class="rera-meta-info">
                        <div class="rera-label">Official State Real Estate Regulatory Authority</div>
                        <div class="rera-number"><?= e($prop_rera) ?></div>
                        <p>100% Freehold clear title and statutory building clearances verified under state RERA guidelines.</p>
                    </div>
                    <div>
                        <button type="button" class="rera-copy-btn" id="btnCopyRera" data-rera="<?= e($prop_rera) ?>" title="Copy official RERA identifier">
                            <i class="fa-regular fa-copy"></i>
                            <span>Copy RERA No.</span>
                        </button>
                    </div>
                </div>

                <!-- Developer Card -->
                <div class="developer-card">
                    <div class="developer-logo-placeholder">
                        <?= strtoupper(substr($prop_developer, 0, 2)) ?>
                    </div>
                    <div class="developer-info">
                        <h5><?= e($prop_developer) ?></h5>
                        <p>Estd. <?= (int)$prop_developer_year ?> &bull; Renowned for delivering benchmark architectural luxury landmarks across prime Indian metropolitan hubs.</p>
                    </div>
                </div>

                <!-- Dedicated Senior Advisory Agent Card -->
                <div class="agent-card-component">
                    <div class="agent-avatar-box">
                        <img src="<?= e(safe_image_url($agent_avatar, 'avatar')) ?>" alt="<?= e($agent_name) ?>" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/avatar-placeholder.svg';">
                    </div>
                    <div class="agent-card-details">
                        <h5><?= e($agent_name) ?></h5>
                        <div class="title"><?= e($agent_title) ?></div>
                        <p class="exp"><i class="fa-solid fa-award" style="color: var(--gold-600); margin-right: 0.3rem;"></i> <?= e($agent_exp) ?></p>
                        <div class="agent-card-actions">
                            <a href="tel:<?= preg_replace('/[^0-9+]/', '', $agent_phone) ?>" class="agent-btn agent-btn-call">
                                <i class="fa-solid fa-phone"></i> Direct Call
                            </a>
                            <a href="<?= e($wa_url) ?>" target="_blank" rel="noopener noreferrer" class="agent-btn agent-btn-whatsapp">
                                <i class="fa-brands fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Sticky Tabbed Contact & Lead Acquisition Widget (35%) -->
            <aside class="prop-sidebar-sticky" id="inquiryWidget">
                <div class="inquiry-box-widget">
                    <div class="inquiry-box-header">
                        <div class="eyebrow"><i class="fa-solid fa-crown"></i> Private Acquisition Desk</div>
                        <h4>Connect with Concierge</h4>
                    </div>

                    <!-- 3-Tab Navigator -->
                    <div class="inquiry-tabs-nav" role="tablist">
                        <button type="button" class="inquiry-tab-btn active" data-tab="panelEnquire">
                            <i class="fa-solid fa-envelope"></i> Enquire Now
                        </button>
                        <button type="button" class="inquiry-tab-btn" data-tab="panelCallback">
                            <i class="fa-solid fa-phone-volume"></i> Callback
                        </button>
                        <button type="button" class="inquiry-tab-btn" data-tab="panelSiteVisit">
                            <i class="fa-solid fa-calendar-check"></i> Site Visit
                        </button>
                    </div>

                    <div class="inquiry-box-body">
                        <!-- AJAX / Server Response Box -->
                        <div class="inquiry-response-box <?= ($inquiry_result && $inquiry_result['success']) ? 'success' : (($inquiry_result && !$inquiry_result['success']) ? 'error' : '') ?>" style="<?= $inquiry_result ? 'display:block;' : '' ?>">
                            <?php if ($inquiry_result): ?>
                                <div class="inquiry-response-icon">
                                    <i class="fa-solid <?= $inquiry_result['success'] ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                                </div>
                                <div class="inquiry-response-title"><?= $inquiry_result['success'] ? 'Inquiry Confirmed' : 'Submission Note' ?></div>
                                <p class="inquiry-response-msg"><?= e($inquiry_result['message']) ?></p>
                                <?php if (!empty($inquiry_result['id'])): ?>
                                    <span class="inquiry-ref-badge">Reference ID: #EV-<?= str_pad((string)$inquiry_result['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Tab 1: Enquire Now Form -->
                        <div class="inquiry-form-panel active" id="panelEnquire">
                            <form id="formEnquireNow" method="POST" class="inquiry-form">
                                <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
                                <input type="hidden" name="inquiry_type" value="enquiry">

                                <div class="form-group">
                                    <label class="form-label" for="enq_name">Full Legal Name *</label>
                                    <input type="text" id="enq_name" name="full_name" class="form-control" placeholder="e.g. Vikram Singhania" value="<?= e($client_name) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="enq_phone">Phone / Mobile (+91) *</label>
                                    <input type="tel" id="enq_phone" name="phone" class="form-control" placeholder="+91 98200 00000" value="<?= e($client_phone) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="enq_email">Corporate or Private Email *</label>
                                    <input type="email" id="enq_email" name="email" class="form-control" placeholder="name@domain.com" value="<?= e($client_email) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="enq_msg">Confidential Requirements / Queries</label>
                                    <textarea id="enq_msg" name="message" class="form-control" rows="3" placeholder="Please share the complete architectural brochure, legal title documents, and pricing structure."></textarea>
                                </div>

                                <button type="submit" class="btn-submit-inquiry">
                                    <i class="fa-solid fa-paper-plane"></i> Submit Confidential Inquiry
                                </button>
                            </form>
                        </div>

                        <!-- Tab 2: Request Callback Form -->
                        <div class="inquiry-form-panel" id="panelCallback">
                            <form id="formCallback" method="POST" class="inquiry-form">
                                <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
                                <input type="hidden" name="inquiry_type" value="callback">

                                <div class="form-group">
                                    <label class="form-label" for="cb_name">Full Name *</label>
                                    <input type="text" id="cb_name" name="full_name" class="form-control" placeholder="e.g. Vikram Singhania" value="<?= e($client_name) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="cb_phone">Contact Number *</label>
                                    <input type="tel" id="cb_phone" name="phone" class="form-control" placeholder="+91 98200 00000" value="<?= e($client_phone) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Preferred Advisory Window</label>
                                    <div class="time-slot-pills">
                                        <label class="time-slot-label">
                                            <input type="radio" name="preferred_time" value="Morning 10 AM - 1 PM" checked>
                                            <span class="time-slot-pill"><i class="fa-regular fa-sun"></i> Morning (10am–1pm)</span>
                                        </label>
                                        <label class="time-slot-label">
                                            <input type="radio" name="preferred_time" value="Afternoon 1 PM - 5 PM">
                                            <span class="time-slot-pill"><i class="fa-solid fa-sun"></i> Noon (1pm–5pm)</span>
                                        </label>
                                        <label class="time-slot-label">
                                            <input type="radio" name="preferred_time" value="Evening 5 PM - 8 PM">
                                            <span class="time-slot-pill"><i class="fa-regular fa-moon"></i> Evening (5pm–8pm)</span>
                                        </label>
                                        <label class="time-slot-label">
                                            <input type="radio" name="preferred_time" value="Discreet Weekend Call">
                                            <span class="time-slot-pill"><i class="fa-regular fa-calendar"></i> Weekend Only</span>
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn-submit-inquiry">
                                    <i class="fa-solid fa-phone-volume"></i> Request Senior Advisor Call
                                </button>
                            </form>
                        </div>

                        <!-- Tab 3: Schedule VIP Site Visit Form -->
                        <div class="inquiry-form-panel" id="panelSiteVisit">
                            <form id="formSiteVisit" method="POST" class="inquiry-form">
                                <input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
                                <input type="hidden" name="inquiry_type" value="site_visit">

                                <div class="form-group">
                                    <label class="form-label" for="sv_name">Visitor Full Name *</label>
                                    <input type="text" id="sv_name" name="full_name" class="form-control" placeholder="e.g. Vikram Singhania" value="<?= e($client_name) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="sv_phone">Mobile Number *</label>
                                    <input type="tel" id="sv_phone" name="phone" class="form-control" placeholder="+91 98200 00000" value="<?= e($client_phone) ?>" required>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                    <div class="form-group">
                                        <label class="form-label" for="sv_date">Target Date *</label>
                                        <input type="date" id="sv_date" name="visit_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="sv_time">Time Slot</label>
                                        <select id="sv_time" name="visit_time" class="form-control">
                                            <option value="11:00 AM (Morning Tour)">11:00 AM</option>
                                            <option value="02:30 PM (Mid-day Light)">02:30 PM</option>
                                            <option value="05:30 PM (Sunset Horizon)">05:30 PM (Golden Hour)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: var(--slate-700); cursor: pointer;">
                                        <input type="checkbox" name="message" value="Chauffeur Transfer Service Requested" checked>
                                        <span>Request complimentary luxury chauffeur transfer</span>
                                    </label>
                                </div>

                                <button type="submit" class="btn-submit-inquiry">
                                    <i class="fa-solid fa-calendar-check"></i> Book Private VIP Walkthrough
                                </button>
                            </form>
                        </div>

                        <!-- Quick Direct CTAs in Sidebar -->
                        <div class="sidebar-direct-cta">
                            <a href="<?= e($wa_url) ?>" target="_blank" rel="noopener noreferrer" class="sidebar-btn-whatsapp">
                                <i class="fa-brands fa-whatsapp" style="font-size: 1.1rem;"></i>
                                <span>Connect via WhatsApp Direct</span>
                            </a>
                            <a href="tel:<?= preg_replace('/[^0-9+]/', '', $agent_phone) ?>" class="sidebar-btn-call">
                                <i class="fa-solid fa-phone" style="font-size: 0.95rem;"></i>
                                <span>Call Concierge: <?= e($agent_phone) ?></span>
                            </a>
                            <div class="sidebar-trust-badge">
                                <i class="fa-solid fa-lock"></i>
                                <span>Non-Disclosure Agreement Protected &bull; Zero Spam</span>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- Similar Luxury Residences Section -->
    <?php if (!empty($similar_properties)): ?>
        <section class="similar-properties-section">
            <div class="container">
                <div class="section-header" style="text-align: left; margin-bottom: 2.5rem;">
                    <div class="eyebrow"><i class="fa-solid fa-gem"></i> Curated Portfolio</div>
                    <h2 class="section-title">Comparable Prime Residences</h2>
                    <p class="section-subtitle">Explore bespoke penthouses, sky villas, and estates within similar price and architectural standards.</p>
                </div>

                <div class="similar-props-grid">
                    <?php 
                    $saved_main_property = $property;
                    foreach ($similar_properties as $property) {
                        include __DIR__ . '/includes/property-card.php';
                    }
                    $property = $saved_main_property;
                    ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<!-- Fullscreen Image Lightbox Modal -->
<div class="lightbox-modal" id="propLightbox" role="dialog" aria-modal="true" aria-label="Residence Photography Gallery">
    <div class="lightbox-topbar">
        <div class="lightbox-title-box">
            <h4><?= e($prop_title) ?></h4>
            <span><?= e($prop_locality) ?>, <?= e($prop_city) ?></span>
        </div>
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <div class="lightbox-counter" id="lightboxCounter">1 / <?= count($gallery_list) ?></div>
            <button type="button" class="lightbox-close-btn" id="lightboxClose" aria-label="Close Lightbox">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <div class="lightbox-stage">
        <button type="button" class="lightbox-nav-btn lightbox-prev" id="lightboxPrev" aria-label="Previous Photo">
            <i class="fa-solid fa-chevron-left"></i>
        </button>

        <div class="lightbox-image-container">
            <img src="<?= e($gallery_list[0]) ?>" alt="<?= e($prop_title) ?>" class="lightbox-img" id="lightboxImg" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
        </div>

        <button type="button" class="lightbox-nav-btn lightbox-next" id="lightboxNext" aria-label="Next Photo">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

    <div class="lightbox-thumbs-bar" id="lightboxThumbs">
        <!-- Injected via JavaScript -->
    </div>
</div>

<!-- Mobile Sticky Bottom Action Bar -->
<div class="mobile-sticky-actions">
    <div class="mobile-actions-flex">
        <a href="tel:<?= preg_replace('/[^0-9+]/', '', $agent_phone) ?>" class="mobile-btn-call" title="Call Advisor">
            <i class="fa-solid fa-phone"></i> Call
        </a>
        <a href="<?= e($wa_url) ?>" target="_blank" rel="noopener noreferrer" class="mobile-btn-whatsapp" title="WhatsApp Enquiry">
            <i class="fa-brands fa-whatsapp"></i>
        </a>
        <button type="button" class="mobile-btn-enquire" id="mobileBtnEnquire">
            <i class="fa-solid fa-envelope"></i> Enquire Now
        </button>
    </div>
</div>

<!-- Floating Toast Notification -->
<div class="prop-toast" id="propToast" role="status" aria-live="polite">
    <i class="fa-solid fa-circle-check"></i>
    <span class="toast-msg">Operation completed successfully</span>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

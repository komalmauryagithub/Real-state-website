<?php
/**
 * EstateVista India - List Your Property System
 * Multi-Step Submission Wizard for Property Owners, Developers & Channel Partners.
 * Enforces client & server-side validation, secure photo uploads, CSRF protection,
 * and sets status to 'pending' for editorial curation.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Require login to submit property
require_login();
$user = get_logged_in_user();

$categories = get_all_categories();
$cities = get_cities_list();
$amenities = get_all_amenities();

$submission_success = false;
$submission_result = null;
$error_message = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'Security validation failed (CSRF token mismatch). Please refresh the page and submit again.';
    } else {
        $submission_result = create_property_submission($user['id'], $_POST, $_FILES);
        if ($submission_result['success']) {
            $submission_success = true;
        } else {
            $error_message = $submission_result['message'];
        }
    }
}

$page_title = 'List Your Property | ' . SITE_NAME;
$extra_css = BASE_URL . 'assets/css/submission.css';
$extra_js = BASE_URL . 'assets/js/submission.js';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="submission-page">
    <!-- Hero Section -->
    <section class="submission-hero">
        <div class="container">
            <div class="submission-hero-content">
                <div class="submission-hero-eyebrow">
                    <i class="fa-solid fa-crown"></i> Private Seller & Owner Advisory
                </div>
                <h1 class="submission-hero-title">List Your Exclusive Property with EstateVista</h1>
                <p class="submission-hero-desc">
                    Connect directly with India’s most qualified HNIs, CXOs, and global NRI capital. Every submission undergoes discreet white-glove curation and title verification before public publication.
                </p>
            </div>
        </div>
    </section>

    <div class="container">
        <?php if ($submission_success): ?>
            <!-- Success Screen -->
            <div class="submission-card" style="margin-top: -2rem;">
                <div class="submission-success-card">
                    <div class="success-icon-badge">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div class="success-reference-pill">
                        <i class="fa-solid fa-hashtag"></i> Submission ID #<?= e($submission_result['property_id']) ?>
                    </div>
                    <h2 class="success-title">Dossier Submitted for Curation</h2>
                    <p style="font-size: 1.05rem; color: #475569; line-height: 1.6; margin-bottom: 2rem;">
                        Thank you, <strong><?= e($user['full_name']) ?></strong>. Your property 
                        <em>"<?= e($_POST['title'] ?? '') ?>"</em> has been securely lodged with the EstateVista India curation desk.
                    </p>

                    <div class="curation-policy-box" style="text-align: left; margin: 0 auto 2.5rem; max-width: 580px;">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <div class="curation-policy-box-text">
                            <strong>What happens next?</strong><br>
                            Our Senior Acquisitions Partner will verify title documentation, confirm pricing benchmarks, and optimize your visual presentation. Upon approval (typically within 24 business hours), your listing will go live across all EstateVista India channels.
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="<?= BASE_URL ?>property-detail.php?slug=<?= urlencode($submission_result['slug']) ?>" class="btn btn-outline" style="border-color: var(--sub-gold); color: var(--sub-primary);">
                            <i class="fa-solid fa-eye"></i> Preview Private Dossier
                        </a>
                        <a href="<?= BASE_URL ?>dashboard.php?tab=my_properties" class="btn btn-primary">
                            <i class="fa-solid fa-gauge"></i> Manage in Dashboard
                        </a>
                        <a href="<?= BASE_URL ?>list-property.php" class="btn btn-outline" style="border-color: #CBD5E1;">
                            <i class="fa-solid fa-plus"></i> Submit Another Listing
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>

            <!-- Multi-Step Progress Bar -->
            <div class="submission-stepper-wrapper">
                <div class="stepper-progress-fill" id="stepperProgressFill"></div>
                <ul class="submission-stepper">
                    <li class="stepper-step active" data-step="1">
                        <div class="step-circle">1</div>
                        <span class="step-title">Basic Details</span>
                    </li>
                    <li class="stepper-step" data-step="2">
                        <div class="step-circle">2</div>
                        <span class="step-title">Location</span>
                    </li>
                    <li class="stepper-step" data-step="3">
                        <div class="step-circle">3</div>
                        <span class="step-title">Specifications</span>
                    </li>
                    <li class="stepper-step" data-step="4">
                        <div class="step-circle">4</div>
                        <span class="step-title">Amenities</span>
                    </li>
                    <li class="stepper-step" data-step="5">
                        <div class="step-circle">5</div>
                        <span class="step-title">Photos</span>
                    </li>
                    <li class="stepper-step" data-step="6">
                        <div class="step-circle">6</div>
                        <span class="step-title">Review</span>
                    </li>
                </ul>
            </div>

            <!-- Error Banner -->
            <?php if (!empty($error_message)): ?>
                <div class="curation-policy-box" style="background: #FEF2F2; border-color: #FECACA; color: #991B1B; margin: 0 auto 2rem; max-width: 1080px;">
                    <i class="fa-solid fa-circle-exclamation" style="color: #DC2626;"></i>
                    <div class="curation-policy-box-text" style="color: #991B1B;">
                        <strong>Submission Incomplete:</strong> <?= e($error_message) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Multi-Step Form -->
            <div class="submission-card">
                <form id="propertySubmissionForm" method="POST" action="<?= BASE_URL ?>list-property.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                    <div class="submission-body">
                        <!-- =======================================================
                             STEP 1: BASIC DETAILS & PURPOSE
                             ======================================================= -->
                        <div class="step-pane active" data-step="1">
                            <div class="step-header">
                                <div class="step-header-eyebrow">Step 1 of 6</div>
                                <h2 class="step-header-title">Primary Listing Information</h2>
                                <p class="step-header-desc">Define the core classification, transactional intent, and benchmark valuation.</p>
                            </div>

                            <!-- Role Type -->
                            <div class="sub-form-group">
                                <label class="sub-label">You Are Listing As: <span class="required">*</span></label>
                                <div class="radio-card-grid">
                                    <label class="radio-card selected">
                                        <input type="radio" name="owner_role" value="owner" checked>
                                        <div class="radio-card-icon"><i class="fa-solid fa-house-user"></i></div>
                                        <div class="radio-card-title">Property Owner</div>
                                        <div class="radio-card-desc">Direct title holder / family trust</div>
                                    </label>
                                    <label class="radio-card">
                                        <input type="radio" name="owner_role" value="agent">
                                        <div class="radio-card-icon"><i class="fa-solid fa-handshake"></i></div>
                                        <div class="radio-card-title">Channel Partner / Agent</div>
                                        <div class="radio-card-desc">Authorized real estate advisory partner</div>
                                    </label>
                                    <label class="radio-card">
                                        <input type="radio" name="owner_role" value="developer">
                                        <div class="radio-card-icon"><i class="fa-solid fa-city"></i></div>
                                        <div class="radio-card-title">Reputed Developer</div>
                                        <div class="radio-card-desc">Promoter / luxury development desk</div>
                                    </label>
                                </div>
                            </div>

                            <!-- Transaction Purpose -->
                            <div class="sub-form-group">
                                <label class="sub-label">Transaction Purpose: <span class="required">*</span></label>
                                <div class="radio-card-grid">
                                    <label class="radio-card selected">
                                        <input type="radio" name="purpose" value="buy" checked>
                                        <div class="radio-card-icon"><i class="fa-solid fa-key"></i></div>
                                        <div class="radio-card-title">Sell Residence</div>
                                        <div class="radio-card-desc">Capital acquisition / freehold sale</div>
                                    </label>
                                    <label class="radio-card">
                                        <input type="radio" name="purpose" value="rent">
                                        <div class="radio-card-icon"><i class="fa-solid fa-calendar-days"></i></div>
                                        <div class="radio-card-title">Rent / Lease</div>
                                        <div class="radio-card-desc">High-value residential lease</div>
                                    </label>
                                    <label class="radio-card">
                                        <input type="radio" name="purpose" value="commercial">
                                        <div class="radio-card-icon"><i class="fa-solid fa-briefcase"></i></div>
                                        <div class="radio-card-title">Commercial Space</div>
                                        <div class="radio-card-desc">Grade-A office or retail asset</div>
                                    </label>
                                </div>
                            </div>

                            <div class="sub-grid-2">
                                <!-- Title -->
                                <div class="sub-form-group">
                                    <label for="propTitle" class="sub-label">Property Title <span class="required">*</span></label>
                                    <input type="text" id="propTitle" name="title" class="sub-input" placeholder="e.g. The Sky Crest Penthouse at Worli Sea Face" required value="<?= e($_POST['title'] ?? '') ?>">
                                </div>

                                <!-- Category -->
                                <div class="sub-form-group">
                                    <label for="propCategory" class="sub-label">Property Category <span class="required">*</span></label>
                                    <select id="propCategory" name="category_id" class="sub-select" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                                <?= e($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="sub-grid-2">
                                <!-- Price -->
                                <div class="sub-form-group">
                                    <label for="propPriceInput" class="sub-label">Expected Price in INR (₹) <span class="required">*</span></label>
                                    <div class="input-affix-wrapper">
                                        <span class="input-affix">₹</span>
                                        <input type="number" id="propPriceInput" name="price" class="sub-input" placeholder="e.g. 250000000 for 25 Cr" step="1000" min="100000" required value="<?= e($_POST['price'] ?? '') ?>">
                                    </div>
                                    <div class="price-crore-preview" id="priceCrorePreview">
                                        <i class="fa-solid fa-coins"></i> Formatted Value: <strong>—</strong>
                                    </div>
                                </div>

                                <!-- Subtitle / Tagline -->
                                <div class="sub-form-group">
                                    <label for="propSubtitle" class="sub-label">Property Subtitle / Tagline <span class="sub-label-hint">(Optional)</span></label>
                                    <input type="text" id="propSubtitle" name="subtitle" class="sub-input" placeholder="e.g. Duplex Penthouse with 270° Arabian Sea Panoramas" value="<?= e($_POST['subtitle'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- =======================================================
                             STEP 2: LOCATION & NEIGHBORHOOD
                             ======================================================= -->
                        <div class="step-pane" data-step="2">
                            <div class="step-header">
                                <div class="step-header-eyebrow">Step 2 of 6</div>
                                <h2 class="step-header-title">Location & Geographic Position</h2>
                                <p class="step-header-desc">Indian prime buyers prioritize prestige pin codes and neighborhood connectivity.</p>
                            </div>

                            <div class="sub-grid-2">
                                <!-- City -->
                                <div class="sub-form-group">
                                    <label for="propCity" class="sub-label">City <span class="required">*</span></label>
                                    <select id="propCity" name="city_id" class="sub-select" required>
                                        <option value="">Select Metropolitan City</option>
                                        <?php foreach ($cities as $city): ?>
                                            <option value="<?= $city['id'] ?>" <?= (isset($_POST['city_id']) && $_POST['city_id'] == $city['id']) ? 'selected' : '' ?>>
                                                <?= e($city['name']) ?> (<?= e($city['state']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Locality -->
                                <div class="sub-form-group">
                                    <label for="propLocality" class="sub-label">Locality / Neighborhood <span class="required">*</span></label>
                                    <input type="text" id="propLocality" name="locality" class="sub-input" placeholder="e.g. Worli Sea Face / Golf Course Road / Jubilee Hills" required value="<?= e($_POST['locality'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Full Address -->
                            <div class="sub-form-group">
                                <label for="propAddress" class="sub-label">Full Address / Building Name <span class="required">*</span></label>
                                <input type="text" id="propAddress" name="address" class="sub-input" placeholder="e.g. Tower B, The Camellias, DLF Phase 5, Sector 42" required value="<?= e($_POST['address'] ?? '') ?>">
                                <p style="font-size: 0.78rem; color: #64748B; margin-top: 0.35rem;">
                                    <i class="fa-solid fa-lock" style="color: var(--sub-gold);"></i> Exact flat/suite numbers remain strictly confidential until client security clearance.
                                </p>
                            </div>
                        </div>

                        <!-- =======================================================
                             STEP 3: SPECIFICATIONS & FLOOR CONFIGURATION
                             ======================================================= -->
                        <div class="step-pane" data-step="3">
                            <div class="step-header">
                                <div class="step-header-eyebrow">Step 3 of 6</div>
                                <h2 class="step-header-title">Architectural Specifications</h2>
                                <p class="step-header-desc">Detailed room layouts, carpet measurement, and floor elevations.</p>
                            </div>

                            <div class="sub-grid-3">
                                <!-- BHK -->
                                <div class="sub-form-group">
                                    <label for="propBhk" class="sub-label">Bedrooms (BHK) <span class="required">*</span></label>
                                    <select id="propBhk" name="bhk" class="sub-select" required>
                                        <option value="">Select BHK</option>
                                        <?php for ($i = 1; $i <= 7; $i++): ?>
                                            <option value="<?= $i ?>" <?= (isset($_POST['bhk']) && $_POST['bhk'] == $i) ? 'selected' : ($i == 4 ? 'selected' : '') ?>>
                                                <?= $i ?> BHK
                                            </option>
                                        <?php endfor; ?>
                                        <option value="8">8+ BHK Palatial Estate</option>
                                    </select>
                                </div>

                                <!-- Bathrooms -->
                                <div class="sub-form-group">
                                    <label for="propBathrooms" class="sub-label">Bathrooms <span class="required">*</span></label>
                                    <select id="propBathrooms" name="bathrooms" class="sub-select" required>
                                        <option value="">Select Baths</option>
                                        <?php for ($i = 1; $i <= 8; $i++): ?>
                                            <option value="<?= $i ?>" <?= (isset($_POST['bathrooms']) && $_POST['bathrooms'] == $i) ? 'selected' : ($i == 4 ? 'selected' : '') ?>>
                                                <?= $i ?> Bathrooms
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <!-- Balconies -->
                                <div class="sub-form-group">
                                    <label for="propBalconies" class="sub-label">Balconies / Decks</label>
                                    <select id="propBalconies" name="balconies" class="sub-select">
                                        <?php for ($i = 0; $i <= 6; $i++): ?>
                                            <option value="<?= $i ?>" <?= (isset($_POST['balconies']) && $_POST['balconies'] == $i) ? 'selected' : ($i == 3 ? 'selected' : '') ?>>
                                                <?= $i ?> <?= ($i == 1 ? 'Balcony' : 'Balconies') ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="sub-grid-2">
                                <!-- Carpet Area -->
                                <div class="sub-form-group">
                                    <label for="propCarpetArea" class="sub-label">RERA Carpet Area (sq.ft) <span class="required">*</span></label>
                                    <input type="number" id="propCarpetArea" name="carpet_area_sqft" class="sub-input" placeholder="e.g. 4500" min="100" required value="<?= e($_POST['carpet_area_sqft'] ?? '') ?>">
                                </div>

                                <!-- Built-up Area -->
                                <div class="sub-form-group">
                                    <label for="propBuiltupArea" class="sub-label">Super Built-up Area (sq.ft) <span class="sub-label-hint">(Optional)</span></label>
                                    <input type="number" id="propBuiltupArea" name="builtup_area_sqft" class="sub-input" placeholder="e.g. 5800" value="<?= e($_POST['builtup_area_sqft'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="sub-grid-3">
                                <!-- Floor Number -->
                                <div class="sub-form-group">
                                    <label for="propFloorNumber" class="sub-label">Floor Number</label>
                                    <input type="text" id="propFloorNumber" name="floor_number" class="sub-input" placeholder="e.g. 42nd Floor (Duplex)" value="<?= e($_POST['floor_number'] ?? 'Mid-to-High Floor') ?>">
                                </div>

                                <!-- Total Floors -->
                                <div class="sub-form-group">
                                    <label for="propTotalFloors" class="sub-label">Total Floors in Tower</label>
                                    <input type="number" id="propTotalFloors" name="total_floors" class="sub-input" placeholder="e.g. 50" value="<?= e($_POST['total_floors'] ?? 45) ?>">
                                </div>

                                <!-- Facing -->
                                <div class="sub-form-group">
                                    <label for="propFacing" class="sub-label">Facing / Vaastu Orientation</label>
                                    <input type="text" id="propFacing" name="facing" class="sub-input" placeholder="e.g. North-East (Vaastu Compliant)" value="<?= e($_POST['facing'] ?? 'North-East (Vaastu Compliant)') ?>">
                                </div>
                            </div>

                            <div class="sub-grid-3">
                                <!-- Parking Bays -->
                                <div class="sub-form-group">
                                    <label for="propParking" class="sub-label">Covered Parking Bays</label>
                                    <select id="propParking" name="parking_spaces" class="sub-select">
                                        <?php for ($i = 1; $i <= 6; $i++): ?>
                                            <option value="<?= $i ?>" <?= (isset($_POST['parking_spaces']) && $_POST['parking_spaces'] == $i) ? 'selected' : ($i == 3 ? 'selected' : '') ?>>
                                                <?= $i ?> Dedicated Bays
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <!-- Construction Status -->
                                <div class="sub-form-group">
                                    <label for="propStatus" class="sub-label">Construction Status</label>
                                    <select id="propStatus" name="status" class="sub-select">
                                        <option value="ready_to_move" <?= (isset($_POST['status']) && $_POST['status'] == 'ready_to_move') ? 'selected' : '' ?>>Ready to Move / Handover</option>
                                        <option value="under_construction" <?= (isset($_POST['status']) && $_POST['status'] == 'under_construction') ? 'selected' : '' ?>>Under Construction</option>
                                        <option value="immediate" <?= (isset($_POST['status']) && $_POST['status'] == 'immediate') ? 'selected' : '' ?>>Immediate Possession</option>
                                    </select>
                                </div>

                                <!-- Possession Date -->
                                <div class="sub-form-group">
                                    <label for="propPossession" class="sub-label">Possession Date / Handover</label>
                                    <input type="text" id="propPossession" name="possession_date" class="sub-input" placeholder="e.g. Ready for Possession or Q4 2026" value="<?= e($_POST['possession_date'] ?? 'Ready for Possession') ?>">
                                </div>
                            </div>

                            <div class="sub-grid-2">
                                <!-- Furnishing Status -->
                                <div class="sub-form-group">
                                    <label class="sub-label">Furnishing Status: <span class="required">*</span></label>
                                    <div class="pill-grid">
                                        <label class="pill-option">
                                            <input type="radio" name="furnishing" value="bespoke-furnished" checked>
                                            <span class="pill-label"><i class="fa-solid fa-couch"></i> Bespoke Furnished</span>
                                        </label>
                                        <label class="pill-option">
                                            <input type="radio" name="furnishing" value="semi-furnished">
                                            <span class="pill-label"><i class="fa-solid fa-kitchen-set"></i> Semi-Furnished</span>
                                        </label>
                                        <label class="pill-option">
                                            <input type="radio" name="furnishing" value="unfurnished">
                                            <span class="pill-label"><i class="fa-solid fa-border-all"></i> Bare-Shell / Unfurnished</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- RERA Number -->
                                <div class="sub-form-group">
                                    <label for="propRera" class="sub-label">RERA Registration Number</label>
                                    <input type="text" id="propRera" name="rera_number" class="sub-input" placeholder="e.g. PR/MH/RERA/2023/004812 (or 'RERA Exempt')" value="<?= e($_POST['rera_number'] ?? '') ?>">
                                    <p style="font-size: 0.78rem; color: #64748B; margin-top: 0.35rem;">
                                        Leave blank if exempt or currently under state authority verification.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- =======================================================
                             STEP 4: DESCRIPTION & LUXURY AMENITIES
                             ======================================================= -->
                        <div class="step-pane" data-step="4">
                            <div class="step-header">
                                <div class="step-header-eyebrow">Step 4 of 6</div>
                                <h2 class="step-header-title">Narrative & Curated Amenities</h2>
                                <p class="step-header-desc">Articulate the bespoke architectural features and world-class hospitality facilities.</p>
                            </div>

                            <!-- Description -->
                            <div class="sub-form-group">
                                <label for="propDescription" class="sub-label">Property Narrative / Description <span class="required">*</span></label>
                                <textarea id="propDescription" name="description" class="sub-textarea" placeholder="Describe the panoramic vistas, interior ceiling heights, designer materials (Italian marble, Sub-Zero kitchen), neighborhood privacy, and exclusive building privileges..." required><?= e($_POST['description'] ?? '') ?></textarea>
                            </div>

                            <!-- Amenities Grid -->
                            <div class="sub-form-group">
                                <label class="sub-label">Select Luxury Amenities & Facilities:</label>
                                <div class="amenities-selector-grid">
                                    <?php foreach ($amenities as $amenity): ?>
                                        <label class="amenity-chip">
                                            <input type="checkbox" name="amenities[]" value="<?= $amenity['id'] ?>" <?= (isset($_POST['amenities']) && is_array($_POST['amenities']) && in_array($amenity['id'], $_POST['amenities'])) ? 'checked' : '' ?>>
                                            <div class="amenity-chip-box">
                                                <i class="fa-solid <?= e($amenity['icon_class'] ?: 'fa-star') ?>"></i>
                                                <span class="amenity-chip-text"><?= e($amenity['name']) ?></span>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- =======================================================
                             STEP 5: PHOTOGRAPHY & MEDIA UPLOAD
                             ======================================================= -->
                        <div class="step-pane" data-step="5">
                            <div class="step-header">
                                <div class="step-header-eyebrow">Step 5 of 6</div>
                                <h2 class="step-header-title">Photography & Visual Presentation</h2>
                                <p class="step-header-desc">High-resolution imagery creates the primary impression for prospective acquisitions.</p>
                            </div>

                            <div class="sub-form-group">
                                <div class="photo-dropzone" id="photoDropzone">
                                    <div class="photo-dropzone-icon">
                                        <i class="fa-solid fa-cloud-arrow-up"></i>
                                    </div>
                                    <div class="photo-dropzone-title">Click to Browse or Drag & Drop Property Images</div>
                                    <div class="photo-dropzone-subtitle">Supported formats: JPEG, PNG, WebP (Max 5MB per photo). Upload up to 10 images.</div>
                                    <button type="button" class="btn btn-sm btn-outline-gold" style="pointer-events: none;">
                                        <i class="fa-solid fa-images"></i> Select Photographs
                                    </button>
                                    <input type="file" id="propImagesInput" name="property_images[]" multiple accept="image/jpeg,image/png,image/webp" style="display: none;">
                                </div>

                                <div id="imageStepNotice" style="display: none; margin-top: 1rem; color: #D97706; font-size: 0.85rem; font-weight: 600;"></div>

                                <!-- Previews Grid -->
                                <div class="photo-previews-grid" id="photoPreviewsGrid"></div>
                            </div>
                        </div>

                        <!-- =======================================================
                             STEP 6: CONTACT INFORMATION & DOSSIER REVIEW
                             ======================================================= -->
                        <div class="step-pane" data-step="6">
                            <div class="step-header">
                                <div class="step-header-eyebrow">Step 6 of 6</div>
                                <h2 class="step-header-title">Representative Contact & Final Review</h2>
                                <p class="step-header-desc">Verify contact coordinates and review the complete listing dossier before submission.</p>
                            </div>

                            <div class="sub-grid-3">
                                <!-- Contact Name -->
                                <div class="sub-form-group">
                                    <label for="propContactName" class="sub-label">Representative Name <span class="required">*</span></label>
                                    <input type="text" id="propContactName" name="contact_person_name" class="sub-input" placeholder="Full Name" required value="<?= e($_POST['contact_person_name'] ?? $user['full_name']) ?>">
                                </div>

                                <!-- Contact Phone -->
                                <div class="sub-form-group">
                                    <label for="propContactPhone" class="sub-label">Telephone / WhatsApp <span class="required">*</span></label>
                                    <input type="tel" id="propContactPhone" name="contact_person_phone" class="sub-input" placeholder="+91 98765 43210" required value="<?= e($_POST['contact_person_phone'] ?? $user['phone']) ?>">
                                </div>

                                <!-- Contact Email -->
                                <div class="sub-form-group">
                                    <label for="propContactEmail" class="sub-label">Official Email <span class="sub-label-hint">(Optional)</span></label>
                                    <input type="email" id="propContactEmail" name="contact_person_email" class="sub-input" placeholder="name@domain.com" value="<?= e($_POST['contact_person_email'] ?? $user['email']) ?>">
                                </div>
                            </div>

                            <!-- Live Review Summary Card -->
                            <div class="review-summary-card">
                                <div class="review-summary-title">
                                    <i class="fa-solid fa-clipboard-check" style="color: var(--sub-gold);"></i>
                                    <span>Listing Dossier Summary</span>
                                </div>
                                <table class="review-summary-table">
                                    <tr>
                                        <td class="label-col">Property Title:</td>
                                        <td class="val-col" id="revTitle">—</td>
                                    </tr>
                                    <tr>
                                        <td class="label-col">Category & Purpose:</td>
                                        <td class="val-col"><span id="revCategory">—</span> (<span id="revPurpose">BUY</span>)</td>
                                    </tr>
                                    <tr>
                                        <td class="label-col">Expected Valuation:</td>
                                        <td class="val-col" id="revPrice" style="font-weight: 700; color: var(--sub-gold-dark);">—</td>
                                    </tr>
                                    <tr>
                                        <td class="label-col">Location:</td>
                                        <td class="val-col" id="revLocation">—</td>
                                    </tr>
                                    <tr>
                                        <td class="label-col">Configuration:</td>
                                        <td class="val-col" id="revSpecs">—</td>
                                    </tr>
                                    <tr>
                                        <td class="label-col">Furnishing:</td>
                                        <td class="val-col" id="revFurnishing">—</td>
                                    </tr>
                                    <tr>
                                        <td class="label-col">RERA / Registration:</td>
                                        <td class="val-col" id="revRera">—</td>
                                    </tr>
                                    <tr>
                                        <td class="label-col">Representative:</td>
                                        <td class="val-col" id="revContact">—</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Curation Terms Agreement -->
                            <div class="curation-policy-box">
                                <i class="fa-solid fa-shield-halved"></i>
                                <div class="curation-policy-box-text">
                                    <label style="display: flex; gap: 0.75rem; align-items: flex-start; cursor: pointer;">
                                        <input type="checkbox" id="agreeCurationTerms" name="agree_terms" value="1" required style="margin-top: 3px;">
                                        <span>
                                            I solemnly affirm that I am the authorized legal representative or title owner of this property. I acknowledge that the listing will remain <strong>Pending Approval</strong> until EstateVista verifies title deeds and marketing rights in accordance with RERA protocols.
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Wizard Navigation Footer -->
                    <div class="submission-footer">
                        <button type="button" class="btn-step-prev" id="btnStepPrev" style="display: none;">
                            <i class="fa-solid fa-arrow-left"></i> Previous Step
                        </button>
                        <div style="margin-left: auto; display: flex; gap: 1rem;">
                            <button type="button" class="btn-step-next" id="btnStepNext">
                                <span>Save & Continue</span> <i class="fa-solid fa-arrow-right"></i>
                            </button>
                            <button type="submit" class="btn-step-submit" id="btnStepSubmit" style="display: none;">
                                <i class="fa-solid fa-check"></i> Submit Property for Curation
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

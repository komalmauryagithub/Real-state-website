<?php
/**
 * EstateVista India - Edit Submitted Property
 * Allows property owners to update details, add new images, or revise amenities.
 * Enforces strict user authorization (403 Forbidden if not the listing owner).
 * Resets status to 'pending' upon editing so modifications are verified.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Require client authentication
require_login();
$user = get_logged_in_user();

$prop_id = (int)($_GET['id'] ?? ($_POST['property_id'] ?? 0));

// Fetch and verify ownership
$property = get_user_property_for_edit($prop_id, $user['id']);

$error_message = null;
$success_message = null;

// If property does not belong to logged-in user, reject with 403 Forbidden
if (!$property) {
    http_response_code(403);
    $page_title = 'Access Denied (403) | ' . SITE_NAME;
    require_once __DIR__ . '/includes/header.php';
    require_once __DIR__ . '/includes/navbar.php';
    ?>
    <main class="submission-page" style="padding: 6rem 0; text-align: center;">
        <div class="container">
            <div style="max-width: 600px; margin: 0 auto; background: #FFFFFF; padding: 3rem 2rem; border-radius: 16px; border: 1px solid #E2E8F0; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                <div style="width: 70px; height: 70px; border-radius: 50%; background: #FEE2E2; color: #DC2626; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.5rem;">
                    <i class="fa-solid fa-ban"></i>
                </div>
                <h1 style="font-family: var(--font-serif); font-size: 2rem; color: #0B1528; margin-bottom: 1rem;">
                    Access Unauthorized (403)
                </h1>
                <p style="color: #64748B; margin-bottom: 2rem; line-height: 1.6;">
                    You do not have administrative permission to modify this property listing. Only the verified owner who submitted this dossier can make revisions.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <a href="<?= BASE_URL ?>dashboard.php?tab=my_properties" class="btn btn-primary">
                        <i class="fa-solid fa-arrow-left"></i> Return to My Properties
                    </a>
                    <a href="<?= BASE_URL ?>" class="btn btn-outline">
                        <i class="fa-solid fa-house"></i> Home
                    </a>
                </div>
            </div>
        </div>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Handle Update POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'Security validation failed (CSRF token mismatch). Please refresh and try again.';
    } else {
        $update_res = update_property_submission($property['id'], $user['id'], $_POST, $_FILES);
        if ($update_res['success']) {
            header('Location: ' . BASE_URL . 'dashboard.php?tab=my_properties&updated=1');
            exit;
        } else {
            $error_message = $update_res['message'];
        }
    }
}

$categories = get_all_categories();
$cities = get_cities_list();
$amenities = get_all_amenities();
$selected_amenity_ids = $property['amenity_ids'] ?? [];

$existing_gallery = json_decode($property['gallery_images'] ?? '[]', true) ?: [$property['cover_image']];

$page_title = 'Edit Property: ' . $property['title'] . ' | ' . SITE_NAME;
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
                    <i class="fa-solid fa-pen-to-square"></i> Modify Residence Dossier
                </div>
                <h1 class="submission-hero-title">Edit: <?= e($property['title']) ?></h1>
                <p class="submission-hero-desc">
                    Update architectural specifications, pricing, or imagery. Any modifications will be resubmitted to the curation desk for quality verification.
                </p>
            </div>
        </div>
    </section>

    <div class="container">
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
                    <strong>Update Failed:</strong> <?= e($error_message) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Multi-Step Form -->
        <div class="submission-card">
            <form id="propertySubmissionForm" method="POST" action="<?= BASE_URL ?>edit-property.php?id=<?= $property['id'] ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="property_id" value="<?= $property['id'] ?>">

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
                                <label class="radio-card <?= ($property['owner_role'] === 'owner') ? 'selected' : '' ?>">
                                    <input type="radio" name="owner_role" value="owner" <?= ($property['owner_role'] === 'owner') ? 'checked' : '' ?>>
                                    <div class="radio-card-icon"><i class="fa-solid fa-house-user"></i></div>
                                    <div class="radio-card-title">Property Owner</div>
                                    <div class="radio-card-desc">Direct title holder / family trust</div>
                                </label>
                                <label class="radio-card <?= ($property['owner_role'] === 'agent') ? 'selected' : '' ?>">
                                    <input type="radio" name="owner_role" value="agent" <?= ($property['owner_role'] === 'agent') ? 'checked' : '' ?>>
                                    <div class="radio-card-icon"><i class="fa-solid fa-handshake"></i></div>
                                    <div class="radio-card-title">Channel Partner / Agent</div>
                                    <div class="radio-card-desc">Authorized real estate advisory partner</div>
                                </label>
                                <label class="radio-card <?= ($property['owner_role'] === 'developer') ? 'selected' : '' ?>">
                                    <input type="radio" name="owner_role" value="developer" <?= ($property['owner_role'] === 'developer') ? 'checked' : '' ?>>
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
                                <label class="radio-card <?= ($property['purpose'] === 'buy') ? 'selected' : '' ?>">
                                    <input type="radio" name="purpose" value="buy" <?= ($property['purpose'] === 'buy') ? 'checked' : '' ?>>
                                    <div class="radio-card-icon"><i class="fa-solid fa-key"></i></div>
                                    <div class="radio-card-title">Sell Residence</div>
                                    <div class="radio-card-desc">Capital acquisition / freehold sale</div>
                                </label>
                                <label class="radio-card <?= ($property['purpose'] === 'rent') ? 'selected' : '' ?>">
                                    <input type="radio" name="purpose" value="rent" <?= ($property['purpose'] === 'rent') ? 'checked' : '' ?>>
                                    <div class="radio-card-icon"><i class="fa-solid fa-calendar-days"></i></div>
                                    <div class="radio-card-title">Rent / Lease</div>
                                    <div class="radio-card-desc">High-value residential lease</div>
                                </label>
                                <label class="radio-card <?= ($property['purpose'] === 'commercial') ? 'selected' : '' ?>">
                                    <input type="radio" name="purpose" value="commercial" <?= ($property['purpose'] === 'commercial') ? 'checked' : '' ?>>
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
                                <input type="text" id="propTitle" name="title" class="sub-input" required value="<?= e($property['title']) ?>">
                            </div>

                            <!-- Category -->
                            <div class="sub-form-group">
                                <label for="propCategory" class="sub-label">Property Category <span class="required">*</span></label>
                                <select id="propCategory" name="category_id" class="sub-select" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($property['category_id'] == $cat['id']) ? 'selected' : '' ?>>
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
                                    <input type="number" id="propPriceInput" name="price" class="sub-input" step="1000" min="100000" required value="<?= (int)$property['price'] ?>">
                                </div>
                                <div class="price-crore-preview" id="priceCrorePreview">
                                    <i class="fa-solid fa-coins"></i> Formatted Value: <strong><?= e($property['price_display']) ?></strong>
                                </div>
                            </div>

                            <!-- Subtitle / Tagline -->
                            <div class="sub-form-group">
                                <label for="propSubtitle" class="sub-label">Property Subtitle / Tagline</label>
                                <input type="text" id="propSubtitle" name="subtitle" class="sub-input" value="<?= e($property['subtitle'] ?? '') ?>">
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
                                        <option value="<?= $city['id'] ?>" <?= ($property['city_id'] == $city['id']) ? 'selected' : '' ?>>
                                            <?= e($city['name']) ?> (<?= e($city['state']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Locality -->
                            <div class="sub-form-group">
                                <label for="propLocality" class="sub-label">Locality / Neighborhood <span class="required">*</span></label>
                                <input type="text" id="propLocality" name="locality" class="sub-input" required value="<?= e($property['locality']) ?>">
                            </div>
                        </div>

                        <!-- Full Address -->
                        <div class="sub-form-group">
                            <label for="propAddress" class="sub-label">Full Address / Building Name <span class="required">*</span></label>
                            <input type="text" id="propAddress" name="address" class="sub-input" required value="<?= e($property['address']) ?>">
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
                                    <?php for ($i = 1; $i <= 7; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($property['bhk'] == $i) ? 'selected' : '' ?>>
                                            <?= $i ?> BHK
                                        </option>
                                    <?php endfor; ?>
                                    <option value="8" <?= ($property['bhk'] >= 8) ? 'selected' : '' ?>>8+ BHK Palatial Estate</option>
                                </select>
                            </div>

                            <!-- Bathrooms -->
                            <div class="sub-form-group">
                                <label for="propBathrooms" class="sub-label">Bathrooms <span class="required">*</span></label>
                                <select id="propBathrooms" name="bathrooms" class="sub-select" required>
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($property['bathrooms'] == $i) ? 'selected' : '' ?>>
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
                                        <option value="<?= $i ?>" <?= ($property['balconies'] == $i) ? 'selected' : '' ?>>
                                            <?= $i ?> Balconies
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="sub-grid-2">
                            <!-- Carpet Area -->
                            <div class="sub-form-group">
                                <label for="propCarpetArea" class="sub-label">RERA Carpet Area (sq.ft) <span class="required">*</span></label>
                                <input type="number" id="propCarpetArea" name="carpet_area_sqft" class="sub-input" min="100" required value="<?= (int)$property['carpet_area_sqft'] ?>">
                            </div>

                            <!-- Built-up Area -->
                            <div class="sub-form-group">
                                <label for="propBuiltupArea" class="sub-label">Super Built-up Area (sq.ft)</label>
                                <input type="number" id="propBuiltupArea" name="builtup_area_sqft" class="sub-input" value="<?= (int)($property['builtup_area_sqft'] ?? 0) ?>">
                            </div>
                        </div>

                        <div class="sub-grid-3">
                            <!-- Floor Number -->
                            <div class="sub-form-group">
                                <label for="propFloorNumber" class="sub-label">Floor Number</label>
                                <input type="text" id="propFloorNumber" name="floor_number" class="sub-input" value="<?= e($property['floor_number'] ?? '') ?>">
                            </div>

                            <!-- Total Floors -->
                            <div class="sub-form-group">
                                <label for="propTotalFloors" class="sub-label">Total Floors in Tower</label>
                                <input type="number" id="propTotalFloors" name="total_floors" class="sub-input" value="<?= (int)($property['total_floors'] ?? 30) ?>">
                            </div>

                            <!-- Facing -->
                            <div class="sub-form-group">
                                <label for="propFacing" class="sub-label">Facing / Vaastu Orientation</label>
                                <input type="text" id="propFacing" name="facing" class="sub-input" value="<?= e($property['facing'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="sub-grid-3">
                            <!-- Parking Bays -->
                            <div class="sub-form-group">
                                <label for="propParking" class="sub-label">Covered Parking Bays</label>
                                <select id="propParking" name="parking_spaces" class="sub-select">
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($property['parking_spaces'] == $i) ? 'selected' : '' ?>>
                                            <?= $i ?> Dedicated Bays
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Construction Status -->
                            <div class="sub-form-group">
                                <label for="propStatus" class="sub-label">Construction Status</label>
                                <select id="propStatus" name="status" class="sub-select">
                                    <option value="ready_to_move" <?= ($property['status'] === 'ready_to_move') ? 'selected' : '' ?>>Ready to Move / Handover</option>
                                    <option value="under_construction" <?= ($property['status'] === 'under_construction') ? 'selected' : '' ?>>Under Construction</option>
                                    <option value="immediate" <?= ($property['status'] === 'immediate') ? 'selected' : '' ?>>Immediate Possession</option>
                                </select>
                            </div>

                            <!-- Possession Date -->
                            <div class="sub-form-group">
                                <label for="propPossession" class="sub-label">Possession Date / Handover</label>
                                <input type="text" id="propPossession" name="possession_date" class="sub-input" value="<?= e($property['possession_date'] ?? 'Ready for Handover') ?>">
                            </div>
                        </div>

                        <div class="sub-grid-2">
                            <!-- Furnishing Status -->
                            <div class="sub-form-group">
                                <label class="sub-label">Furnishing Status: <span class="required">*</span></label>
                                <div class="pill-grid">
                                    <label class="pill-option">
                                        <input type="radio" name="furnishing" value="bespoke-furnished" <?= ($property['furnishing'] === 'bespoke-furnished') ? 'checked' : '' ?>>
                                        <span class="pill-label"><i class="fa-solid fa-couch"></i> Bespoke Furnished</span>
                                    </label>
                                    <label class="pill-option">
                                        <input type="radio" name="furnishing" value="semi-furnished" <?= ($property['furnishing'] === 'semi-furnished') ? 'checked' : '' ?>>
                                        <span class="pill-label"><i class="fa-solid fa-kitchen-set"></i> Semi-Furnished</span>
                                    </label>
                                    <label class="pill-option">
                                        <input type="radio" name="furnishing" value="unfurnished" <?= ($property['furnishing'] === 'unfurnished') ? 'checked' : '' ?>>
                                        <span class="pill-label"><i class="fa-solid fa-border-all"></i> Bare-Shell / Unfurnished</span>
                                    </label>
                                </div>
                            </div>

                            <!-- RERA Number -->
                            <div class="sub-form-group">
                                <label for="propRera" class="sub-label">RERA Registration Number</label>
                                <input type="text" id="propRera" name="rera_number" class="sub-input" value="<?= e($property['rera_number']) ?>">
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
                            <textarea id="propDescription" name="description" class="sub-textarea" required><?= e($property['description']) ?></textarea>
                        </div>

                        <!-- Amenities Grid -->
                        <div class="sub-form-group">
                            <label class="sub-label">Select Luxury Amenities & Facilities:</label>
                            <div class="amenities-selector-grid">
                                <?php foreach ($amenities as $amenity): ?>
                                    <label class="amenity-chip">
                                        <input type="checkbox" name="amenities[]" value="<?= $amenity['id'] ?>" <?= in_array($amenity['id'], $selected_amenity_ids) ? 'checked' : '' ?>>
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
                            <p class="step-header-desc">Manage existing property photography or upload new high-resolution images.</p>
                        </div>

                        <!-- Existing Images Preview -->
                        <?php if (!empty($existing_gallery)): ?>
                            <div class="sub-form-group">
                                <label class="sub-label">Current Property Photos:</label>
                                <div class="photo-previews-grid">
                                    <?php foreach ($existing_gallery as $idx => $imgUrl): ?>
                                        <div class="photo-preview-item existing-photo-item">
                                            <img src="<?= e(safe_image_url($imgUrl, 'property')) ?>" alt="Property Image <?= $idx + 1 ?>" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                                            <?php if ($idx === 0): ?>
                                                <span class="cover-badge"><i class="fa-solid fa-star"></i> Cover Photo</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="sub-form-group">
                            <label class="sub-label">Upload Additional or Replacement Photos:</label>
                            <div class="photo-dropzone" id="photoDropzone">
                                <div class="photo-dropzone-icon">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                </div>
                                <div class="photo-dropzone-title">Click to Browse or Drag & Drop New Photos</div>
                                <div class="photo-dropzone-subtitle">Supported formats: JPEG, PNG, WebP (Max 5MB per photo).</div>
                                <button type="button" class="btn btn-sm btn-outline-gold" style="pointer-events: none;">
                                    <i class="fa-solid fa-images"></i> Add More Photos
                                </button>
                                <input type="file" id="propImagesInput" name="property_images[]" multiple accept="image/jpeg,image/png,image/webp" style="display: none;">
                            </div>

                            <div class="photo-previews-grid" id="photoPreviewsGrid"></div>
                        </div>
                    </div>

                    <!-- =======================================================
                         STEP 6: CONTACT INFORMATION & DOSSIER REVIEW
                         ======================================================= -->
                    <div class="step-pane" data-step="6">
                        <div class="step-header">
                            <div class="step-header-eyebrow">Step 6 of 6</div>
                            <h2 class="step-header-title">Representative Contact & Re-submission Review</h2>
                            <p class="step-header-desc">Verify contact coordinates before resubmitting listing to the curation desk.</p>
                        </div>

                        <div class="sub-grid-3">
                            <!-- Contact Name -->
                            <div class="sub-form-group">
                                <label for="propContactName" class="sub-label">Representative Name <span class="required">*</span></label>
                                <input type="text" id="propContactName" name="contact_person_name" class="sub-input" required value="<?= e($property['contact_person_name'] ?: $user['full_name']) ?>">
                            </div>

                            <!-- Contact Phone -->
                            <div class="sub-form-group">
                                <label for="propContactPhone" class="sub-label">Telephone / WhatsApp <span class="required">*</span></label>
                                <input type="tel" id="propContactPhone" name="contact_person_phone" class="sub-input" required value="<?= e($property['contact_person_phone'] ?: $user['phone']) ?>">
                            </div>

                            <!-- Contact Email -->
                            <div class="sub-form-group">
                                <label for="propContactEmail" class="sub-label">Official Email</label>
                                <input type="email" id="propContactEmail" name="contact_person_email" class="sub-input" value="<?= e($property['contact_person_email'] ?: $user['email']) ?>">
                            </div>
                        </div>

                        <!-- Live Review Summary Card -->
                        <div class="review-summary-card">
                            <div class="review-summary-title">
                                <i class="fa-solid fa-clipboard-check" style="color: var(--sub-gold);"></i>
                                <span>Updated Listing Dossier Summary</span>
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
                                    <td class="label-col">Representative:</td>
                                    <td class="val-col" id="revContact">—</td>
                                </tr>
                            </table>
                        </div>

                        <!-- Notice -->
                        <div class="curation-policy-box">
                            <i class="fa-solid fa-circle-info"></i>
                            <div class="curation-policy-box-text">
                                <label style="display: flex; gap: 0.75rem; align-items: flex-start; cursor: pointer;">
                                    <input type="checkbox" id="agreeCurationTerms" name="agree_terms" value="1" checked required style="margin-top: 3px;">
                                    <span>
                                        I confirm that the modified information is accurate and truthful. I acknowledge that saving changes will update this listing and reset its status to <strong>Pending Approval</strong> for editorial re-verification.
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
                            <i class="fa-solid fa-floppy-disk"></i> Save & Re-submit Listing
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

<?php
/**
 * EstateVista India - Admin Add New Property
 * Direct luxury listing creation with media upload, amenities, and default approved status.
 */

$page_title = 'Add New Luxury Residence';

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
require_once __DIR__ . '/includes/admin_navbar.php';

require_admin_role(['super_admin', 'admin']);

$db = Database::getConnection();
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process creation
    $title = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $city_id = (int)($_POST['city_id'] ?? 0);
    $locality = trim($_POST['locality'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $purpose = $_POST['purpose'] ?? 'buy';
    $bhk = (int)($_POST['bhk'] ?? 4);
    $bathrooms = (int)($_POST['bathrooms'] ?? 4);
    $carpet_area_sqft = (int)($_POST['carpet_area_sqft'] ?? 0);
    $builtup_area_sqft = !empty($_POST['builtup_area_sqft']) ? (int)$_POST['builtup_area_sqft'] : (int)round($carpet_area_sqft * 1.25);
    $balconies = (int)($_POST['balconies'] ?? 2);
    $floor_number = trim($_POST['floor_number'] ?? '');
    $total_floors = (int)($_POST['total_floors'] ?? 30);
    $furnishing = $_POST['furnishing'] ?? 'bespoke-furnished';
    $facing = trim($_POST['facing'] ?? 'North-East (Vaastu Compliant)');
    $parking_spaces = (int)($_POST['parking_spaces'] ?? 3);
    $status = $_POST['status'] ?? 'ready_to_move';
    $possession_date = trim($_POST['possession_date'] ?? 'Ready for Handover');
    $rera_number = trim($_POST['rera_number'] ?? 'RERA Verified');
    $description = trim($_POST['description'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_exclusive = isset($_POST['is_exclusive']) ? 1 : 0;
    $is_verified = isset($_POST['is_verified']) ? 1 : 1;
    $approval_status = $_POST['approval_status'] ?? 'approved';
    $owner_role = $_POST['owner_role'] ?? 'developer';
    $contact_person_name = trim($_POST['contact_person_name'] ?? $current_admin['name']);
    $contact_person_phone = trim($_POST['contact_person_phone'] ?? SITE_PHONE);
    $contact_person_email = trim($_POST['contact_person_email'] ?? SITE_EMAIL);

    if (empty($title) || $category_id <= 0 || $city_id <= 0 || $price <= 0 || $carpet_area_sqft <= 0) {
        $error_message = 'Please fill out all required fields (Title, Category, City, Price, Carpet Area).';
    } else {
        // Handle images
        $uploaded_images = [];
        if (!empty($_FILES['property_images'])) {
            $uploaded_images = process_property_image_uploads($_FILES['property_images']);
        }

        $cover_image = !empty($uploaded_images) ? $uploaded_images[0] : (trim($_POST['custom_cover_image'] ?? '') ?: 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80');
        $gallery_images = !empty($uploaded_images) ? json_encode($uploaded_images) : json_encode([$cover_image]);

        $base_slug = slugify($title);
        $slug = $base_slug . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
        $price_display = format_inr_price($price);

        try {
            $stmt = $db->prepare("
                INSERT INTO properties (
                    title, slug, subtitle, description, category_id, city_id, locality, address,
                    price, price_display, price_type, purpose, bhk, bathrooms, balconies,
                    carpet_area_sqft, builtup_area_sqft, floor_number, total_floors, furnishing,
                    facing, parking_spaces, status, possession_date, rera_number, is_featured,
                    is_exclusive, is_verified, approval_status, cover_image, gallery_images,
                    contact_person_name, contact_person_phone, contact_person_email, owner_role
                ) VALUES (
                    :title, :slug, :subtitle, :description, :category_id, :city_id, :locality, :address,
                    :price, :price_display, 'sale', :purpose, :bhk, :bathrooms, :balconies,
                    :carpet_area_sqft, :builtup_area_sqft, :floor_number, :total_floors, :furnishing,
                    :facing, :parking_spaces, :status, :possession_date, :rera_number, :is_featured,
                    :is_exclusive, :is_verified, :approval_status, :cover_image, :gallery_images,
                    :contact_person_name, :contact_person_phone, :contact_person_email, :owner_role
                )
            ");

            $stmt->execute([
                ':title' => $title,
                ':slug' => $slug,
                ':subtitle' => trim($_POST['subtitle'] ?? "{$bhk} BHK Luxury Residence in {$locality}"),
                ':description' => $description,
                ':category_id' => $category_id,
                ':city_id' => $city_id,
                ':locality' => $locality,
                ':address' => $address,
                ':price' => $price,
                ':price_display' => $price_display,
                ':purpose' => $purpose,
                ':bhk' => $bhk,
                ':bathrooms' => $bathrooms,
                ':balconies' => $balconies,
                ':carpet_area_sqft' => $carpet_area_sqft,
                ':builtup_area_sqft' => $builtup_area_sqft,
                ':floor_number' => $floor_number,
                ':total_floors' => $total_floors,
                ':furnishing' => $furnishing,
                ':facing' => $facing,
                ':parking_spaces' => $parking_spaces,
                ':status' => $status,
                ':possession_date' => $possession_date,
                ':rera_number' => $rera_number,
                ':is_featured' => $is_featured,
                ':is_exclusive' => $is_exclusive,
                ':is_verified' => $is_verified,
                ':approval_status' => $approval_status,
                ':cover_image' => $cover_image,
                ':gallery_images' => $gallery_images,
                ':contact_person_name' => $contact_person_name,
                ':contact_person_phone' => $contact_person_phone,
                ':contact_person_email' => $contact_person_email,
                ':owner_role' => $owner_role
            ]);

            $newId = (int)$db->lastInsertId();

            // Amenities
            if (!empty($_POST['amenities']) && is_array($_POST['amenities'])) {
                $amStmt = $db->prepare("INSERT IGNORE INTO property_amenities (property_id, amenity_id) VALUES (?, ?)");
                foreach ($_POST['amenities'] as $aid) {
                    $amStmt->execute([$newId, (int)$aid]);
                }
            }

            log_admin_activity($current_admin['id'], $current_admin['name'], 'Created Property', "Added new property listing: '{$title}' (#{$newId})");
            $_SESSION['admin_flash_success'] = "Luxury residence '{$title}' added successfully!";
            header('Location: ' . BASE_URL . 'admin/properties.php');
            exit;
        } catch (PDOException $e) {
            $error_message = 'Failed to create property: ' . $e->getMessage();
        }
    }
}

$categories = get_all_categories();
$cities = get_cities_list();
$amenities = get_all_amenities();
?>

<div style="max-width: 1000px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--adm-navy);">Add Luxury Residence</h2>
            <p style="font-size: 0.85rem; color: var(--adm-text-muted);">Enter architectural dossier specifications for public or private showcase.</p>
        </div>
        <a href="<?= BASE_URL ?>admin/properties.php" class="adm-btn adm-btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back to Properties
        </a>
    </div>

    <?php if ($error_message): ?>
        <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <?= e($error_message) ?>
        </div>
    <?php endif; ?>

    <div class="adm-card">
        <form method="POST" action="<?= BASE_URL ?>admin/property-add.php" enctype="multipart/form-data" class="adm-card-body">
            <!-- 1. Basics -->
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--adm-navy); border-bottom: 1px solid var(--adm-border); padding-bottom: 0.5rem; margin-bottom: 1.25rem;">
                1. Core Residence Details
            </h3>

            <div class="adm-grid-2">
                <div class="adm-form-group">
                    <label class="adm-label">Property Title *</label>
                    <input type="text" name="title" class="adm-input" required placeholder="e.g. The Sky Crest Penthouse at Worli Sea Face" value="<?= e($_POST['title'] ?? '') ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Category *</label>
                    <select name="category_id" class="adm-select" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="adm-grid-3">
                <div class="adm-form-group">
                    <label class="adm-label">Valuation / Price in INR (₹) *</label>
                    <input type="number" name="price" class="adm-input" required placeholder="e.g. 350000000 for 35 Cr" value="<?= e($_POST['price'] ?? '') ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Purpose *</label>
                    <select name="purpose" class="adm-select">
                        <option value="buy">Sell / Buy</option>
                        <option value="rent">Rent / Lease</option>
                        <option value="commercial">Commercial</option>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Listing Curation Status</label>
                    <select name="approval_status" class="adm-select">
                        <option value="approved">Approved & Live</option>
                        <option value="pending">Pending Approval</option>
                        <option value="rejected">Rejected / Revisions</option>
                    </select>
                </div>
            </div>

            <!-- 2. Location -->
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--adm-navy); border-bottom: 1px solid var(--adm-border); padding-bottom: 0.5rem; margin: 1.5rem 0 1.25rem;">
                2. Location & Geographic Address
            </h3>

            <div class="adm-grid-2">
                <div class="adm-form-group">
                    <label class="adm-label">Metropolitan City *</label>
                    <select name="city_id" class="adm-select" required>
                        <option value="">Select City</option>
                        <?php foreach ($cities as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['state']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Locality / Neighborhood *</label>
                    <input type="text" name="locality" class="adm-input" required placeholder="e.g. Worli Sea Face" value="<?= e($_POST['locality'] ?? '') ?>">
                </div>
            </div>

            <div class="adm-form-group">
                <label class="adm-label">Complete Street Address *</label>
                <input type="text" name="address" class="adm-input" required placeholder="Building name, road, prime zone" value="<?= e($_POST['address'] ?? '') ?>">
            </div>

            <!-- 3. Specifications -->
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--adm-navy); border-bottom: 1px solid var(--adm-border); padding-bottom: 0.5rem; margin: 1.5rem 0 1.25rem;">
                3. Architectural Specifications
            </h3>

            <div class="adm-grid-3">
                <div class="adm-form-group">
                    <label class="adm-label">Bedrooms (BHK) *</label>
                    <input type="number" name="bhk" class="adm-input" min="1" max="10" value="4" required>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Bathrooms *</label>
                    <input type="number" name="bathrooms" class="adm-input" min="1" max="10" value="4" required>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Balconies</label>
                    <input type="number" name="balconies" class="adm-input" min="0" max="8" value="3">
                </div>
            </div>

            <div class="adm-grid-3">
                <div class="adm-form-group">
                    <label class="adm-label">Carpet Area (sq.ft) *</label>
                    <input type="number" name="carpet_area_sqft" class="adm-input" required placeholder="e.g. 5200" value="<?= e($_POST['carpet_area_sqft'] ?? '') ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Built-up Area (sq.ft)</label>
                    <input type="number" name="builtup_area_sqft" class="adm-input" placeholder="e.g. 6800">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Furnishing</label>
                    <select name="furnishing" class="adm-select">
                        <option value="bespoke-furnished">Bespoke Furnished</option>
                        <option value="semi-furnished">Semi-Furnished</option>
                        <option value="unfurnished">Unfurnished</option>
                    </select>
                </div>
            </div>

            <div class="adm-grid-3">
                <div class="adm-form-group">
                    <label class="adm-label">Floor Elevation</label>
                    <input type="text" name="floor_number" class="adm-input" placeholder="e.g. 48th Floor (Duplex)">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Total Floors in Tower</label>
                    <input type="number" name="total_floors" class="adm-input" value="50">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Covered Parking Bays</label>
                    <input type="number" name="parking_spaces" class="adm-input" value="4">
                </div>
            </div>

            <div class="adm-grid-2">
                <div class="adm-form-group">
                    <label class="adm-label">Construction Status</label>
                    <select name="status" class="adm-select">
                        <option value="ready_to_move">Ready to Move</option>
                        <option value="under_construction">Under Construction</option>
                        <option value="immediate">Immediate Handover</option>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">RERA Registration Number</label>
                    <input type="text" name="rera_number" class="adm-input" placeholder="e.g. PR/MH/RERA/2026/004812" value="PR/MH/RERA/2026/004812">
                </div>
            </div>

            <!-- Description -->
            <div class="adm-form-group">
                <label class="adm-label">Property Narrative & Highlights</label>
                <textarea name="description" class="adm-textarea" rows="4" placeholder="Articulate the bespoke materials, sweeping views, private elevator lobbies, and exclusive privileges..."></textarea>
            </div>

            <!-- Amenities -->
            <div class="adm-form-group">
                <label class="adm-label">Select Luxury Facilities & Amenities</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.6rem; margin-top: 0.4rem;">
                    <?php foreach ($amenities as $am): ?>
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer; background: #F8FAFC; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid #E2E8F0;">
                            <input type="checkbox" name="amenities[]" value="<?= $am['id'] ?>">
                            <span><?= e($am['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Photography -->
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--adm-navy); border-bottom: 1px solid var(--adm-border); padding-bottom: 0.5rem; margin: 1.5rem 0 1.25rem;">
                4. Visual Imagery & Photography
            </h3>

            <div class="adm-grid-2">
                <div class="adm-form-group">
                    <label class="adm-label">Upload Photographs (Multiple)</label>
                    <input type="file" name="property_images[]" class="adm-input" multiple accept="image/jpeg,image/png,image/webp">
                    <small style="color: #64748B;">Supported: JPEG, PNG, WebP (Max 5MB each)</small>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Or Custom Image URL (Fallback)</label>
                    <input type="url" name="custom_cover_image" class="adm-input" placeholder="https://images.unsplash.com/photo-...">
                </div>
            </div>

            <!-- Toggles -->
            <div style="display: flex; gap: 2rem; margin: 1.5rem 0; flex-wrap: wrap;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="is_featured" value="1" checked>
                    <span>Featured Listing (Homepage Spotlight)</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="is_exclusive" value="1">
                    <span>Exclusive Private Portfolio</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="is_verified" value="1" checked>
                    <span>RERA Verified Badge</span>
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; border-top: 1px solid var(--adm-border); padding-top: 1.5rem; margin-top: 1.5rem;">
                <a href="<?= BASE_URL ?>admin/properties.php" class="adm-btn adm-btn-outline">Cancel</a>
                <button type="submit" class="adm-btn adm-btn-gold">
                    <i class="fa-solid fa-check"></i> Save & Publish Residence
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>

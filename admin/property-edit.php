<?php
/**
 * EstateVista India - Admin Edit Property
 * Modify specifications, manage image gallery, assign curation status & rejection feedback.
 */

$page_title = 'Edit Property Dossier';

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
require_once __DIR__ . '/includes/admin_navbar.php';

require_admin_role(['super_admin', 'admin']);

$db = Database::getConnection();
$propId = (int)($_GET['id'] ?? ($_POST['property_id'] ?? 0));

$stmt = $db->prepare("SELECT * FROM properties WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $propId]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    echo "<div class='adm-card'><div class='adm-card-body'>Property not located. <a href='" . BASE_URL . "admin/properties.php'>Back to Properties</a></div></div>";
    require_once __DIR__ . '/includes/admin_footer.php';
    exit;
}

// Fetch currently attached amenity IDs
$amStmt = $db->prepare("SELECT amenity_id FROM property_amenities WHERE property_id = :pid");
$amStmt->execute([':pid' => $propId]);
$current_amenities = $amStmt->fetchAll(PDO::FETCH_COLUMN);

$error_message = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_property') {
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
    $facing = trim($_POST['facing'] ?? '');
    $parking_spaces = (int)($_POST['parking_spaces'] ?? 3);
    $status = $_POST['status'] ?? 'ready_to_move';
    $possession_date = trim($_POST['possession_date'] ?? 'Ready for Handover');
    $rera_number = trim($_POST['rera_number'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_exclusive = isset($_POST['is_exclusive']) ? 1 : 0;
    $is_verified = isset($_POST['is_verified']) ? 1 : 0;
    $approval_status = $_POST['approval_status'] ?? 'approved';
    $rejection_reason = ($approval_status === 'rejected') ? trim($_POST['rejection_reason'] ?? '') : null;
    $contact_person_name = trim($_POST['contact_person_name'] ?? '');
    $contact_person_phone = trim($_POST['contact_person_phone'] ?? '');
    $contact_person_email = trim($_POST['contact_person_email'] ?? '');

    if (empty($title) || $category_id <= 0 || $city_id <= 0 || $price <= 0 || $carpet_area_sqft <= 0) {
        $error_message = 'Please provide all mandatory fields (Title, Category, City, Price, Carpet Area).';
    } else {
        // Handle new image uploads
        $new_images = [];
        if (!empty($_FILES['property_images'])) {
            $new_images = process_property_image_uploads($_FILES['property_images']);
        }

        $existing_gallery = json_decode($property['gallery_images'] ?? '[]', true) ?: [$property['cover_image']];
        $cover_image = $property['cover_image'];

        // If new photos uploaded, merge with gallery
        if (!empty($new_images)) {
            $existing_gallery = array_values(array_unique(array_merge($new_images, $existing_gallery)));
            $cover_image = $new_images[0];
        }

        // Custom cover image override if provided
        if (!empty($_POST['custom_cover_image'])) {
            $cover_image = trim($_POST['custom_cover_image']);
        }

        $price_display = format_inr_price($price);

        try {
            $upd = $db->prepare("
                UPDATE properties SET
                    title = :title,
                    subtitle = :subtitle,
                    description = :description,
                    category_id = :category_id,
                    city_id = :city_id,
                    locality = :locality,
                    address = :address,
                    price = :price,
                    price_display = :price_display,
                    purpose = :purpose,
                    bhk = :bhk,
                    bathrooms = :bathrooms,
                    balconies = :balconies,
                    carpet_area_sqft = :carpet_area_sqft,
                    builtup_area_sqft = :builtup_area_sqft,
                    floor_number = :floor_number,
                    total_floors = :total_floors,
                    furnishing = :furnishing,
                    facing = :facing,
                    parking_spaces = :parking_spaces,
                    status = :status,
                    possession_date = :possession_date,
                    rera_number = :rera_number,
                    is_featured = :is_featured,
                    is_exclusive = :is_exclusive,
                    is_verified = :is_verified,
                    approval_status = :approval_status,
                    rejection_reason = :rejection_reason,
                    cover_image = :cover_image,
                    gallery_images = :gallery_images,
                    contact_person_name = :contact_person_name,
                    contact_person_phone = :contact_person_phone,
                    contact_person_email = :contact_person_email
                WHERE id = :id
            ");

            $upd->execute([
                ':title' => $title,
                ':subtitle' => trim($_POST['subtitle'] ?? ''),
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
                ':rejection_reason' => $rejection_reason,
                ':cover_image' => $cover_image,
                ':gallery_images' => json_encode($existing_gallery),
                ':contact_person_name' => $contact_person_name,
                ':contact_person_phone' => $contact_person_phone,
                ':contact_person_email' => $contact_person_email,
                ':id' => $propId
            ]);

            // Sync amenities
            $db->prepare("DELETE FROM property_amenities WHERE property_id = ?")->execute([$propId]);
            if (!empty($_POST['amenities']) && is_array($_POST['amenities'])) {
                $insAm = $db->prepare("INSERT IGNORE INTO property_amenities (property_id, amenity_id) VALUES (?, ?)");
                foreach ($_POST['amenities'] as $aid) {
                    $insAm->execute([$propId, (int)$aid]);
                }
            }

            log_admin_activity($current_admin['id'], $current_admin['name'], 'Updated Property', "Updated property #{$propId} ('{$title}') with status '{$approval_status}'");
            $_SESSION['admin_flash_success'] = "Property #{$propId} updated successfully!";
            header('Location: ' . BASE_URL . 'admin/properties.php');
            exit;
        } catch (PDOException $e) {
            $error_message = 'Failed to update property: ' . $e->getMessage();
        }
    }
}

$categories = get_all_categories();
$cities = get_cities_list();
$amenities = get_all_amenities();
$gallery_list = json_decode($property['gallery_images'] ?? '[]', true) ?: [$property['cover_image']];
?>

<div style="max-width: 1000px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--adm-navy);">Edit Property #<?= $property['id'] ?></h2>
            <p style="font-size: 0.85rem; color: var(--adm-text-muted);">Slug: <code><?= e($property['slug']) ?></code></p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="<?= BASE_URL ?>property-detail.php?slug=<?= urlencode($property['slug']) ?>" target="_blank" class="adm-btn adm-btn-outline">
                <i class="fa-solid fa-eye"></i> View Public Dossier
            </a>
            <a href="<?= BASE_URL ?>admin/properties.php" class="adm-btn adm-btn-outline">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <?= e($error_message) ?>
        </div>
    <?php endif; ?>

    <div class="adm-card">
        <form method="POST" action="<?= BASE_URL ?>admin/property-edit.php?id=<?= $property['id'] ?>" enctype="multipart/form-data" class="adm-card-body">
            <input type="hidden" name="action" value="save_property">
            <input type="hidden" name="property_id" value="<?= $property['id'] ?>">

            <!-- Curation Status Banner Box -->
            <div style="background: #F8FAFC; border: 1.5px solid var(--adm-border); border-radius: 10px; padding: 1.25rem; margin-bottom: 1.5rem;">
                <div class="adm-grid-2">
                    <div>
                        <label class="adm-label">Editorial Approval Status</label>
                        <select name="approval_status" class="adm-select" id="approvalStatusSelect">
                            <option value="approved" <?= ($property['approval_status'] === 'approved') ? 'selected' : '' ?>>Approved & Live (Public)</option>
                            <option value="pending" <?= ($property['approval_status'] === 'pending') ? 'selected' : '' ?>>Pending Review (Owner Preview Only)</option>
                            <option value="rejected" <?= ($property['approval_status'] === 'rejected') ? 'selected' : '' ?>>Rejected / Revisions Requested</option>
                        </select>
                    </div>
                    <div>
                        <label class="adm-label">Rejection Reason / Curation Feedback (If rejected)</label>
                        <input type="text" name="rejection_reason" class="adm-input" placeholder="e.g. Please upload clear RERA certificates and floor plan" value="<?= e($property['rejection_reason'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- 1. Basics -->
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--adm-navy); border-bottom: 1px solid var(--adm-border); padding-bottom: 0.5rem; margin-bottom: 1.25rem;">
                1. Core Residence Details
            </h3>

            <div class="adm-grid-2">
                <div class="adm-form-group">
                    <label class="adm-label">Property Title *</label>
                    <input type="text" name="title" class="adm-input" required value="<?= e($property['title']) ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Category *</label>
                    <select name="category_id" class="adm-select" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($property['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="adm-grid-3">
                <div class="adm-form-group">
                    <label class="adm-label">Valuation / Price in INR (₹) *</label>
                    <input type="number" name="price" class="adm-input" required value="<?= (int)$property['price'] ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Purpose *</label>
                    <select name="purpose" class="adm-select">
                        <option value="buy" <?= ($property['purpose'] === 'buy') ? 'selected' : '' ?>>Sell / Buy</option>
                        <option value="rent" <?= ($property['purpose'] === 'rent') ? 'selected' : '' ?>>Rent / Lease</option>
                        <option value="commercial" <?= ($property['purpose'] === 'commercial') ? 'selected' : '' ?>>Commercial</option>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Subtitle / Tagline</label>
                    <input type="text" name="subtitle" class="adm-input" value="<?= e($property['subtitle'] ?? '') ?>">
                </div>
            </div>

            <!-- 2. Location -->
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--adm-navy); border-bottom: 1px solid var(--adm-border); padding-bottom: 0.5rem; margin: 1.5rem 0 1.25rem;">
                2. Location & Address
            </h3>

            <div class="adm-grid-2">
                <div class="adm-form-group">
                    <label class="adm-label">Metropolitan City *</label>
                    <select name="city_id" class="adm-select" required>
                        <?php foreach ($cities as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($property['city_id'] == $c['id']) ? 'selected' : '' ?>>
                                <?= e($c['name']) ?> (<?= e($c['state']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Locality / Zone *</label>
                    <input type="text" name="locality" class="adm-input" required value="<?= e($property['locality']) ?>">
                </div>
            </div>

            <div class="adm-form-group">
                <label class="adm-label">Complete Street Address *</label>
                <input type="text" name="address" class="adm-input" required value="<?= e($property['address']) ?>">
            </div>

            <!-- 3. Specifications -->
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--adm-navy); border-bottom: 1px solid var(--adm-border); padding-bottom: 0.5rem; margin: 1.5rem 0 1.25rem;">
                3. Specifications
            </h3>

            <div class="adm-grid-3">
                <div class="adm-form-group">
                    <label class="adm-label">Bedrooms (BHK) *</label>
                    <input type="number" name="bhk" class="adm-input" min="1" max="10" value="<?= (int)$property['bhk'] ?>" required>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Bathrooms *</label>
                    <input type="number" name="bathrooms" class="adm-input" min="1" max="10" value="<?= (int)$property['bathrooms'] ?>" required>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Balconies</label>
                    <input type="number" name="balconies" class="adm-input" min="0" max="8" value="<?= (int)($property['balconies'] ?? 2) ?>">
                </div>
            </div>

            <div class="adm-grid-3">
                <div class="adm-form-group">
                    <label class="adm-label">Carpet Area (sq.ft) *</label>
                    <input type="number" name="carpet_area_sqft" class="adm-input" required value="<?= (int)$property['carpet_area_sqft'] ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Built-up Area (sq.ft)</label>
                    <input type="number" name="builtup_area_sqft" class="adm-input" value="<?= (int)($property['builtup_area_sqft'] ?? 0) ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Furnishing</label>
                    <select name="furnishing" class="adm-select">
                        <option value="bespoke-furnished" <?= ($property['furnishing'] === 'bespoke-furnished') ? 'selected' : '' ?>>Bespoke Furnished</option>
                        <option value="semi-furnished" <?= ($property['furnishing'] === 'semi-furnished') ? 'selected' : '' ?>>Semi-Furnished</option>
                        <option value="unfurnished" <?= ($property['furnishing'] === 'unfurnished') ? 'selected' : '' ?>>Unfurnished</option>
                    </select>
                </div>
            </div>

            <div class="adm-grid-3">
                <div class="adm-form-group">
                    <label class="adm-label">Floor Elevation</label>
                    <input type="text" name="floor_number" class="adm-input" value="<?= e($property['floor_number'] ?? '') ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Total Floors</label>
                    <input type="number" name="total_floors" class="adm-input" value="<?= (int)($property['total_floors'] ?? 30) ?>">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Covered Parking Bays</label>
                    <input type="number" name="parking_spaces" class="adm-input" value="<?= (int)($property['parking_spaces'] ?? 3) ?>">
                </div>
            </div>

            <div class="adm-grid-2">
                <div class="adm-form-group">
                    <label class="adm-label">Construction Status</label>
                    <select name="status" class="adm-select">
                        <option value="ready_to_move" <?= ($property['status'] === 'ready_to_move') ? 'selected' : '' ?>>Ready to Move</option>
                        <option value="under_construction" <?= ($property['status'] === 'under_construction') ? 'selected' : '' ?>>Under Construction</option>
                        <option value="immediate" <?= ($property['status'] === 'immediate') ? 'selected' : '' ?>>Immediate Handover</option>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">RERA Registration</label>
                    <input type="text" name="rera_number" class="adm-input" value="<?= e($property['rera_number']) ?>">
                </div>
            </div>

            <!-- Description -->
            <div class="adm-form-group">
                <label class="adm-label">Property Narrative</label>
                <textarea name="description" class="adm-textarea" rows="4"><?= e($property['description']) ?></textarea>
            </div>

            <!-- Amenities -->
            <div class="adm-form-group">
                <label class="adm-label">Luxury Amenities & Privileges</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.6rem; margin-top: 0.4rem;">
                    <?php foreach ($amenities as $am): ?>
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer; background: #F8FAFC; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid #E2E8F0;">
                            <input type="checkbox" name="amenities[]" value="<?= $am['id'] ?>" <?= in_array($am['id'], $current_amenities) ? 'checked' : '' ?>>
                            <span><?= e($am['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 4. Media & Gallery Management -->
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--adm-navy); border-bottom: 1px solid var(--adm-border); padding-bottom: 0.5rem; margin: 1.5rem 0 1.25rem;">
                4. Image Gallery Management
            </h3>

            <!-- Existing Gallery Previews -->
            <div style="margin-bottom: 1.25rem;">
                <label class="adm-label">Active Image Gallery:</label>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <?php foreach ($gallery_list as $idx => $img): ?>
                        <div style="width: 120px; height: 90px; border-radius: 8px; overflow: hidden; position: relative; border: 1.5px solid var(--adm-border); background: #0B1528;">
                            <img src="<?= e(safe_image_url($img, 'property')) ?>" alt="Image <?= $idx + 1 ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                            <?php if ($img === $property['cover_image']): ?>
                                <span style="position: absolute; top: 4px; left: 4px; background: var(--adm-gold); color: #000; font-size: 0.65rem; font-weight: 700; padding: 2px 5px; border-radius: 4px;">COVER</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="adm-grid-2">
                <div class="adm-form-group">
                    <label class="adm-label">Add More Images</label>
                    <input type="file" name="property_images[]" class="adm-input" multiple accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Override Cover Image URL</label>
                    <input type="url" name="custom_cover_image" class="adm-input" value="<?= e($property['cover_image']) ?>">
                </div>
            </div>

            <!-- 5. Toggles -->
            <div style="display: flex; gap: 2rem; margin: 1.5rem 0; flex-wrap: wrap;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="is_featured" value="1" <?= $property['is_featured'] ? 'checked' : '' ?>>
                    <span>Featured Listing (Homepage Spotlight)</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="is_exclusive" value="1" <?= $property['is_exclusive'] ? 'checked' : '' ?>>
                    <span>Exclusive Private Portfolio</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="is_verified" value="1" <?= $property['is_verified'] ? 'checked' : '' ?>>
                    <span>RERA Verified Badge</span>
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; border-top: 1px solid var(--adm-border); padding-top: 1.5rem; margin-top: 1.5rem;">
                <a href="<?= BASE_URL ?>admin/properties.php" class="adm-btn adm-btn-outline">Cancel</a>
                <button type="submit" class="adm-btn adm-btn-gold">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>

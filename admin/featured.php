<?php
/**
 * EstateVista India - Featured & Exclusive Listings Curation (Module 13)
 * Manage properties spotlighted on the homepage hero, luxury launches, and exclusive dossiers.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';

require_admin_login();

$db = Database::getConnection();
$success_msg = '';
$error_msg = '';

// Handle POST actions (Toggles)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_admin_csrf()) {
        $error_msg = "Security token expired. Please reload and try again.";
    } else {
        $action = sanitize($_POST['action'] ?? '');
        $property_id = (int)($_POST['property_id'] ?? 0);

        if ($property_id > 0) {
            try {
                if ($action === 'toggle_featured') {
                    $new_val = (int)($_POST['val'] ?? 0);
                    $stmt = $db->prepare("UPDATE properties SET is_featured = :v WHERE id = :id");
                    $stmt->execute([':v' => $new_val, ':id' => $property_id]);
                    log_admin_activity('Toggled Featured Listing', "Property #{$property_id} featured state set to {$new_val}");
                    $success_msg = ($new_val === 1) ? "Property spotlighted on homepage." : "Property removed from homepage spotlight.";
                } elseif ($action === 'toggle_exclusive') {
                    $new_val = (int)($_POST['val'] ?? 0);
                    $stmt = $db->prepare("UPDATE properties SET is_exclusive = :v WHERE id = :id");
                    $stmt->execute([':v' => $new_val, ':id' => $property_id]);
                    log_admin_activity('Toggled Exclusive Portfolio', "Property #{$property_id} exclusive state set to {$new_val}");
                    $success_msg = ($new_val === 1) ? "Property added to Ultra-Luxury Exclusive Portfolio." : "Property removed from Exclusive tier.";
                } elseif ($action === 'toggle_verified') {
                    $new_val = (int)($_POST['val'] ?? 0);
                    $stmt = $db->prepare("UPDATE properties SET is_verified = :v WHERE id = :id");
                    $stmt->execute([':v' => $new_val, ':id' => $property_id]);
                    log_admin_activity('Toggled Verified Badge', "Property #{$property_id} verified state set to {$new_val}");
                    $success_msg = ($new_val === 1) ? "RERA Verified badge granted." : "Verified badge removed.";
                }
            } catch (PDOException $e) {
                $error_msg = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch Featured & Exclusive Properties
$curatedProps = $db->query("
    SELECT p.*, c.name as city_name, cat.name as category_name
    FROM properties p
    LEFT JOIN cities c ON p.city_id = c.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    WHERE (p.is_featured = 1 OR p.is_exclusive = 1) AND p.approval_status = 'approved'
    ORDER BY p.is_featured DESC, p.is_exclusive DESC, p.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch All other approved properties eligible to be featured
$eligibleProps = $db->query("
    SELECT p.id, p.title, p.locality, p.price_display, c.name as city_name, p.cover_image, p.is_featured, p.is_exclusive
    FROM properties p
    LEFT JOIN cities c ON p.city_id = c.id
    WHERE p.approval_status = 'approved' AND (p.is_featured = 0 OR p.is_exclusive = 0)
    ORDER BY p.title ASC
    LIMIT 30
")->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_featured = (int)$db->query("SELECT COUNT(*) FROM properties WHERE is_featured = 1 AND approval_status = 'approved'")->fetchColumn();
$total_exclusive = (int)$db->query("SELECT COUNT(*) FROM properties WHERE is_exclusive = 1 AND approval_status = 'approved'")->fetchColumn();
$total_verified = (int)$db->query("SELECT COUNT(*) FROM properties WHERE is_verified = 1 AND approval_status = 'approved'")->fetchColumn();

$page_title = "Featured & Exclusive Listings";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                    Featured Showcase & Exclusive Portfolio
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                    Curate trophy penthouses, signature waterfront villas, and verified high-value listings shown on the front page.
                </p>
            </div>
            <button class="adm-btn adm-btn-gold" data-adm-modal-target="modalPinProperty">
                <i class="fa-solid fa-crown"></i> Spotlight a Property
            </button>
        </div>

        <!-- Feedback Alerts -->
        <?php if (!empty($success_msg)): ?>
            <div style="background: #ECFDF5; border-left: 4px solid var(--adm-success); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; color: #065F46; font-weight: 500;">
                <i class="fa-solid fa-circle-check"></i> <?= e($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div style="background: #FEF2F2; border-left: 4px solid var(--adm-danger); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; color: #991B1B; font-weight: 500;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= e($error_msg) ?>
            </div>
        <?php endif; ?>

        <!-- KPI Cards -->
        <div class="adm-kpi-grid">
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: #D97706;"><?= $total_featured ?></div>
                    <div class="adm-kpi-label">Homepage Featured Properties</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-gold">
                    <i class="fa-solid fa-star"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: #7C3AED;"><?= $total_exclusive ?></div>
                    <div class="adm-kpi-label">Ultra-Exclusive Portfolio</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-purple">
                    <i class="fa-solid fa-gem"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: #059669;"><?= $total_verified ?></div>
                    <div class="adm-kpi-label">RERA Verified Badged Listings</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-green">
                    <i class="fa-solid fa-shield-check"></i>
                </div>
            </div>
        </div>

        <!-- Curated Properties Table -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title">
                    <i class="fa-solid fa-crown" style="color: var(--adm-gold);"></i>
                    Currently Spotlit & Exclusive Listings (<?= count($curatedProps) ?>)
                </div>
                <div class="adm-search-input-wrap" style="min-width: 250px;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" class="adm-input" data-adm-table-search="tableCurated" placeholder="Search curated listings...">
                </div>
            </div>
            <div class="adm-table-responsive">
                <table class="adm-table" id="tableCurated">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Location & Category</th>
                            <th>Pricing</th>
                            <th>Featured Status</th>
                            <th>Exclusive Tier</th>
                            <th>Verified Badge</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($curatedProps)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem; color: var(--adm-text-muted);">
                                    No properties currently spotlighted. Click "Spotlight a Property" to feature trophy assets.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($curatedProps as $p): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.85rem;">
                                            <img src="<?= e(safe_image_url($p['cover_image'], 'property')) ?>" alt="<?= e($p['title']) ?>"
                                                 style="width: 55px; height: 42px; border-radius: 6px; object-fit: cover; border: 1px solid var(--adm-border);"
                                                 onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                                            <div>
                                                <div style="font-weight: 700; color: var(--adm-navy); max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    <a href="<?= BASE_URL ?>property-detail.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" style="text-decoration: none; color: inherit;">
                                                        <?= e($p['title']) ?>
                                                    </a>
                                                </div>
                                                <div style="font-size: 0.72rem; color: var(--adm-text-muted);"><?= e($p['bhk']) ?> BHK &bull; <?= number_format($p['carpet_area_sqft']) ?> sq.ft.</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--adm-navy);"><?= e($p['city_name']) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--adm-text-muted);"><?= e($p['locality']) ?> &bull; <?= e($p['category_name']) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--adm-navy); font-size: 0.95rem;">
                                            <?= e($p['price_display']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="POST" action="featured.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                            <input type="hidden" name="action" value="toggle_featured">
                                            <input type="hidden" name="property_id" value="<?= $p['id'] ?>">
                                            <?php if ($p['is_featured']): ?>
                                                <input type="hidden" name="val" value="0">
                                                <button type="submit" class="adm-btn adm-btn-sm adm-btn-gold" title="Remove from Featured">
                                                    <i class="fa-solid fa-star"></i> Featured
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="val" value="1">
                                                <button type="submit" class="adm-btn adm-btn-sm adm-btn-outline" title="Pin to Featured">
                                                    <i class="fa-regular fa-star"></i> Make Featured
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" action="featured.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                            <input type="hidden" name="action" value="toggle_exclusive">
                                            <input type="hidden" name="property_id" value="<?= $p['id'] ?>">
                                            <?php if ($p['is_exclusive']): ?>
                                                <input type="hidden" name="val" value="0">
                                                <button type="submit" class="adm-btn adm-btn-sm" style="background: #7C3AED; color: #FFF;" title="Remove Exclusive">
                                                    <i class="fa-solid fa-gem"></i> Exclusive
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="val" value="1">
                                                <button type="submit" class="adm-btn adm-btn-sm adm-btn-outline" title="Mark as Exclusive">
                                                    <i class="fa-regular fa-gem"></i> Standard
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" action="featured.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                            <input type="hidden" name="action" value="toggle_verified">
                                            <input type="hidden" name="property_id" value="<?= $p['id'] ?>">
                                            <?php if ($p['is_verified']): ?>
                                                <input type="hidden" name="val" value="0">
                                                <button type="submit" class="adm-btn adm-btn-sm adm-btn-success" title="Remove Verified Badge">
                                                    <i class="fa-solid fa-check-double"></i> Verified
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="val" value="1">
                                                <button type="submit" class="adm-btn adm-btn-sm adm-btn-outline" title="Verify Property">
                                                    Unverified
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="property-edit.php?id=<?= $p['id'] ?>" class="adm-btn adm-btn-sm adm-btn-outline" title="Edit Full Dossier">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Spotlight a Property -->
<div class="adm-modal-backdrop" id="modalPinProperty">
    <div class="adm-modal" style="max-width: 650px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-crown" style="color: var(--adm-gold);"></i> Spotlight Approved Property</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <div class="adm-modal-body" style="max-height: 480px; overflow-y: auto;">
            <p style="font-size: 0.85rem; color: var(--adm-text-muted); margin-bottom: 1rem;">
                Select any verified live property to spotlight on the EstateVista homepage or upgrade to the Exclusive portfolio:
            </p>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($eligibleProps as $ep): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #F8FAFC; border: 1px solid var(--adm-border); border-radius: 8px;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <img src="<?= e(safe_image_url($ep['cover_image'], 'property')) ?>" alt="<?= e($ep['title']) ?>" style="width: 48px; height: 38px; border-radius: 4px; object-fit: cover;" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                            <div>
                                <div style="font-weight: 700; color: var(--adm-navy); font-size: 0.88rem; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= e($ep['title']) ?>
                                </div>
                                <div style="font-size: 0.74rem; color: var(--adm-text-muted);">
                                    <?= e($ep['locality']) ?>, <?= e($ep['city_name']) ?> &bull; <strong><?= e($ep['price_display']) ?></strong>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.35rem;">
                            <?php if (!$ep['is_featured']): ?>
                                <form method="POST" action="featured.php" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                    <input type="hidden" name="action" value="toggle_featured">
                                    <input type="hidden" name="property_id" value="<?= $ep['id'] ?>">
                                    <input type="hidden" name="val" value="1">
                                    <button type="submit" class="adm-btn adm-btn-sm adm-btn-gold">
                                        <i class="fa-solid fa-star"></i> Feature
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if (!$ep['is_exclusive']): ?>
                                <form method="POST" action="featured.php" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                    <input type="hidden" name="action" value="toggle_exclusive">
                                    <input type="hidden" name="property_id" value="<?= $ep['id'] ?>">
                                    <input type="hidden" name="val" value="1">
                                    <button type="submit" class="adm-btn adm-btn-sm" style="background: #7C3AED; color: #FFF;">
                                        <i class="fa-solid fa-gem"></i> Exclusive
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="adm-modal-footer">
            <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Close</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

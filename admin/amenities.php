<?php
/**
 * EstateVista India - Luxury Amenities & Facilities Management (Module 12)
 * Manage bespoke amenities, wellness specifications, security tags, and icons.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';

require_admin_login();
require_admin_role(['super_admin', 'admin']);

$db = Database::getConnection();
$success_msg = '';
$error_msg = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_admin_csrf()) {
        $error_msg = "Security token expired. Please reload and try again.";
    } else {
        $action = sanitize($_POST['action'] ?? '');

        // 1. Create Amenity
        if ($action === 'create_amenity') {
            $name = sanitize($_POST['name'] ?? '');
            $category = sanitize($_POST['category'] ?? 'General');
            $icon = sanitize($_POST['icon_class'] ?? 'fa-check-circle');

            if (empty($name)) {
                $error_msg = "Amenity name is required.";
            } else {
                try {
                    $chk = $db->prepare("SELECT id FROM amenities WHERE name = :n LIMIT 1");
                    $chk->execute([':n' => $name]);
                    if ($chk->fetch()) {
                        $error_msg = "An amenity with this name already exists.";
                    } else {
                        $stmt = $db->prepare("
                            INSERT INTO amenities (name, category, icon_class)
                            VALUES (:n, :c, :i)
                        ");
                        $stmt->execute([':n' => $name, ':c' => $category, ':i' => $icon]);
                        log_admin_activity('Created Amenity', "Created amenity {$name} ({$category})");
                        $success_msg = "Amenity '{$name}' created successfully.";
                    }
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 2. Edit Amenity
        elseif ($action === 'edit_amenity') {
            $amenity_id = (int)($_POST['amenity_id'] ?? 0);
            $name = sanitize($_POST['name'] ?? '');
            $category = sanitize($_POST['category'] ?? 'General');
            $icon = sanitize($_POST['icon_class'] ?? 'fa-check-circle');

            if ($amenity_id <= 0 || empty($name)) {
                $error_msg = "Amenity ID and name are required.";
            } else {
                try {
                    $stmt = $db->prepare("
                        UPDATE amenities
                        SET name = :n, category = :c, icon_class = :i
                        WHERE id = :id
                    ");
                    $stmt->execute([':n' => $name, ':c' => $category, ':i' => $icon, ':id' => $amenity_id]);
                    log_admin_activity('Updated Amenity', "Updated amenity #{$amenity_id} ({$name})");
                    $success_msg = "Amenity '{$name}' updated successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 3. Delete Amenity
        elseif ($action === 'delete_amenity') {
            $amenity_id = (int)($_POST['amenity_id'] ?? 0);
            if ($amenity_id > 0) {
                try {
                    $stmt = $db->prepare("DELETE FROM amenities WHERE id = :id");
                    $stmt->execute([':id' => $amenity_id]);
                    log_admin_activity('Deleted Amenity', "Deleted amenity #{$amenity_id}");
                    $success_msg = "Amenity deleted successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Error deleting amenity: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Amenities
$amenities = $db->query("SELECT * FROM amenities ORDER BY category ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

$categories_list = ['Wellness', 'Leisure', 'Security', 'Architecture', 'Services', 'Eco & Green', 'General'];

$page_title = "Luxury Amenities & Facilities";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                    Bespoke Amenities & Luxury Features
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                    Maintain ultra-luxury facility specifications, biometric automation features, and concierge services.
                </p>
            </div>
            <button class="adm-btn adm-btn-gold" data-adm-modal-target="modalAddAmenity">
                <i class="fa-solid fa-plus"></i> Add New Amenity
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

        <!-- Amenities Grid / Table -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title">
                    <i class="fa-solid fa-spa" style="color: var(--adm-gold);"></i>
                    Master Amenities Checklist (<?= count($amenities) ?>)
                </div>
                <div class="adm-search-input-wrap" style="min-width: 250px;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" class="adm-input" data-adm-table-search="tableAmenities" placeholder="Quick filter amenities...">
                </div>
            </div>
            <div class="adm-table-responsive">
                <table class="adm-table" id="tableAmenities">
                    <thead>
                        <tr>
                            <th>Icon & Amenity Feature</th>
                            <th>Segment / Category</th>
                            <th>FontAwesome Icon Class</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($amenities as $am): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.85rem;">
                                        <div style="width: 38px; height: 38px; border-radius: 8px; background: #FEF3C7; color: #92400E; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; border: 1px solid #FDE68A;">
                                            <i class="fa-solid <?= e($am['icon_class'] ?: 'fa-check') ?>"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: var(--adm-navy); font-size: 0.92rem;"><?= e($am['name']) ?></div>
                                            <div style="font-size: 0.72rem; color: var(--adm-text-muted);">Amenity ID: #<?= $am['id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="adm-status-badge adm-badge-new" style="background: #F1F5F9; color: #475569; font-weight: 600;">
                                        <?= e($am['category'] ?: 'General') ?>
                                    </span>
                                </td>
                                <td>
                                    <code style="background: #F8FAFC; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; border: 1px solid #E2E8F0; color: var(--adm-navy);">
                                        <?= e($am['icon_class'] ?: 'fa-check-circle') ?>
                                    </code>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 0.35rem;">
                                        <button type="button" class="adm-btn adm-btn-sm adm-btn-outline btn-edit-amenity"
                                            data-id="<?= $am['id'] ?>"
                                            data-name="<?= e($am['name']) ?>"
                                            data-category="<?= e($am['category']) ?>"
                                            data-icon="<?= e($am['icon_class']) ?>"
                                            title="Edit Amenity">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <form method="POST" action="amenities.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                            <input type="hidden" name="action" value="delete_amenity">
                                            <input type="hidden" name="amenity_id" value="<?= $am['id'] ?>">
                                            <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger adm-confirm-delete" data-item-name="Amenity '<?= e($am['name']) ?>'" title="Delete Amenity">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Add Amenity -->
<div class="adm-modal-backdrop" id="modalAddAmenity">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-spa" style="color: var(--adm-gold);"></i> Add Luxury Amenity</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="amenities.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="create_amenity">
            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Amenity / Facility Name *</label>
                    <input type="text" name="name" class="adm-input" required placeholder="e.g. Temperature Controlled Wine Cellar">
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Classification Category *</label>
                        <select name="category" class="adm-select" required>
                            <?php foreach ($categories_list as $catOption): ?>
                                <option value="<?= $catOption ?>"><?= $catOption ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">FontAwesome Icon Class</label>
                        <input type="text" name="icon_class" class="adm-input" value="fa-check-circle" placeholder="e.g. fa-wine-glass, fa-water, fa-shield">
                    </div>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-gold">Add Amenity</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Amenity -->
<div class="adm-modal-backdrop" id="modalEditAmenity">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-pen-to-square" style="color: var(--adm-gold);"></i> Edit Amenity</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="amenities.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="edit_amenity">
            <input type="hidden" name="amenity_id" id="edit_am_id">
            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Amenity Name *</label>
                    <input type="text" name="name" id="edit_am_name" class="adm-input" required>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Category</label>
                        <select name="category" id="edit_am_cat" class="adm-select">
                            <?php foreach ($categories_list as $catOption): ?>
                                <option value="<?= $catOption ?>"><?= $catOption ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Icon Class</label>
                        <input type="text" name="icon_class" id="edit_am_icon" class="adm-input" required>
                    </div>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-navy">Save Amenity</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.btn-edit-amenity').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_am_id').value = this.dataset.id;
        document.getElementById('edit_am_name').value = this.dataset.name;
        document.getElementById('edit_am_cat').value = this.dataset.category;
        document.getElementById('edit_am_icon').value = this.dataset.icon;
        document.getElementById('modalEditAmenity').classList.add('show');
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

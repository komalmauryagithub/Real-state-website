<?php
/**
 * EstateVista India - Property Categories Management (Module 11)
 * Configure luxury categories, typologies, icons, and classification taxonomy.
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

        // 1. Create Category
        if ($action === 'create_category') {
            $name = sanitize($_POST['name'] ?? '');
            $slug = sanitize($_POST['slug'] ?? '');
            $icon = sanitize($_POST['icon_class'] ?? 'fa-building');
            $description = sanitize($_POST['description'] ?? '');
            $display_order = (int)($_POST['display_order'] ?? 0);

            if (empty($name)) {
                $error_msg = "Category name is required.";
            } else {
                if (empty($slug)) {
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
                }

                try {
                    $chk = $db->prepare("SELECT id FROM categories WHERE slug = :s LIMIT 1");
                    $chk->execute([':s' => $slug]);
                    if ($chk->fetch()) {
                        $error_msg = "A category with slug '{$slug}' already exists.";
                    } else {
                        $stmt = $db->prepare("
                            INSERT INTO categories (name, slug, icon_class, description, display_order, created_at)
                            VALUES (:name, :slug, :icon, :desc, :ord, NOW())
                        ");
                        $stmt->execute([
                            ':name' => $name,
                            ':slug' => $slug,
                            ':icon' => $icon,
                            ':desc' => $description,
                            ':ord' => $display_order
                        ]);
                        log_admin_activity('Created Property Category', "Created category {$name}");
                        $success_msg = "Category '{$name}' created successfully.";
                    }
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 2. Edit Category
        elseif ($action === 'edit_category') {
            $cat_id = (int)($_POST['category_id'] ?? 0);
            $name = sanitize($_POST['name'] ?? '');
            $slug = sanitize($_POST['slug'] ?? '');
            $icon = sanitize($_POST['icon_class'] ?? 'fa-building');
            $description = sanitize($_POST['description'] ?? '');
            $display_order = (int)($_POST['display_order'] ?? 0);

            if ($cat_id <= 0 || empty($name)) {
                $error_msg = "Category ID and name are required.";
            } else {
                try {
                    $stmt = $db->prepare("
                        UPDATE categories
                        SET name = :name, slug = :slug, icon_class = :icon, description = :desc, display_order = :ord
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':name' => $name,
                        ':slug' => $slug,
                        ':icon' => $icon,
                        ':desc' => $description,
                        ':ord' => $display_order,
                        ':id' => $cat_id
                    ]);
                    log_admin_activity('Updated Property Category', "Updated category #{$cat_id} ({$name})");
                    $success_msg = "Category '{$name}' updated successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 3. Delete Category
        elseif ($action === 'delete_category') {
            $cat_id = (int)($_POST['category_id'] ?? 0);
            if ($cat_id > 0) {
                try {
                    // Check if properties exist in this category
                    $chk = $db->prepare("SELECT COUNT(*) FROM properties WHERE category_id = :cid");
                    $chk->execute([':cid' => $cat_id]);
                    $cnt = (int)$chk->fetchColumn();
                    if ($cnt > 0) {
                        $error_msg = "Cannot delete this category because {$cnt} properties are classified under it.";
                    } else {
                        $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
                        $stmt->execute([':id' => $cat_id]);
                        log_admin_activity('Deleted Property Category', "Deleted category #{$cat_id}");
                        $success_msg = "Category deleted successfully.";
                    }
                } catch (PDOException $e) {
                    $error_msg = "Error deleting category: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Categories with property counts
$categories = $db->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM properties p WHERE p.category_id = c.id AND p.approval_status = 'approved') as live_properties_count,
           (SELECT COUNT(*) FROM properties p WHERE p.category_id = c.id AND p.approval_status = 'pending') as pending_properties_count
    FROM categories c
    ORDER BY c.display_order ASC, c.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Property Categories & Typologies";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                    Property Categories & Typologies
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                    Curate luxury asset classes, Sky Penthouses, Seafront Estates, Gated Enclaves, and Commercial Portfolios.
                </p>
            </div>
            <button class="adm-btn adm-btn-gold" data-adm-modal-target="modalAddCategory">
                <i class="fa-solid fa-plus"></i> Add New Category
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

        <!-- Categories Table -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title">
                    <i class="fa-solid fa-layer-group" style="color: var(--adm-gold);"></i>
                    Portfolio Typology Taxonomy (<?= count($categories) ?>)
                </div>
            </div>
            <div class="adm-table-responsive">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Icon & Category</th>
                            <th>URL Slug</th>
                            <th>Description</th>
                            <th>Inventory Status</th>
                            <th>Display Order</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.85rem;">
                                        <div style="width: 42px; height: 42px; border-radius: 8px; background: #FEF3C7; color: #92400E; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; border: 1.5px solid #FDE68A;">
                                            <i class="fa-solid <?= e($cat['icon_class'] ?: 'fa-building') ?>"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: var(--adm-navy); font-size: 0.95rem;"><?= e($cat['name']) ?></div>
                                            <div style="font-size: 0.72rem; color: var(--adm-text-muted);">ID: #<?= $cat['id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code style="background: #F1F5F9; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; color: #0B1528;">
                                        <?= e($cat['slug']) ?>
                                    </code>
                                </td>
                                <td>
                                    <div style="font-size: 0.82rem; color: #475569; max-width: 320px;">
                                        <?= e($cat['description'] ?: 'Ultra-luxury asset class with world-class specifications.') ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.84rem;">
                                        <strong style="color: var(--adm-navy);"><?= $cat['live_properties_count'] ?></strong> Approved
                                    </div>
                                    <?php if ($cat['pending_properties_count'] > 0): ?>
                                        <div style="font-size: 0.74rem; color: #D97706; font-weight: 600;">
                                            <?= $cat['pending_properties_count'] ?> Pending
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: var(--adm-navy);">#<?= $cat['display_order'] ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 0.35rem;">
                                        <button type="button" class="adm-btn adm-btn-sm adm-btn-outline btn-edit-category"
                                            data-id="<?= $cat['id'] ?>"
                                            data-name="<?= e($cat['name']) ?>"
                                            data-slug="<?= e($cat['slug']) ?>"
                                            data-icon="<?= e($cat['icon_class']) ?>"
                                            data-desc="<?= e($cat['description']) ?>"
                                            data-order="<?= e($cat['display_order']) ?>"
                                            title="Edit Category">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <form method="POST" action="categories.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger adm-confirm-delete" data-item-name="Category '<?= e($cat['name']) ?>'" title="Delete Category">
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

<!-- Modal: Add Category -->
<div class="adm-modal-backdrop" id="modalAddCategory">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-layer-group" style="color: var(--adm-gold);"></i> Add Luxury Asset Category</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="categories.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="create_category">
            <div class="adm-modal-body">
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Category Name *</label>
                        <input type="text" name="name" class="adm-input" required placeholder="e.g. Golf Course Mansions">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">URL Slug (Auto if blank)</label>
                        <input type="text" name="slug" class="adm-input" placeholder="e.g. golf-course-mansions">
                    </div>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">FontAwesome Icon Class</label>
                        <input type="text" name="icon_class" class="adm-input" value="fa-building" placeholder="fa-building, fa-tree, fa-crown">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Display Order</label>
                        <input type="number" name="display_order" class="adm-input" value="10">
                    </div>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Description</label>
                    <textarea name="description" class="adm-textarea" rows="2" placeholder="Brief overview of properties in this luxury segment..."></textarea>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-gold">Create Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Category -->
<div class="adm-modal-backdrop" id="modalEditCategory">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-pen-to-square" style="color: var(--adm-gold);"></i> Edit Category</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="categories.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="edit_category">
            <input type="hidden" name="category_id" id="edit_cat_id">
            <div class="adm-modal-body">
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Category Name *</label>
                        <input type="text" name="name" id="edit_cat_name" class="adm-input" required>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">URL Slug *</label>
                        <input type="text" name="slug" id="edit_cat_slug" class="adm-input" required>
                    </div>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">FontAwesome Icon Class</label>
                        <input type="text" name="icon_class" id="edit_cat_icon" class="adm-input" required>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Display Order</label>
                        <input type="number" name="display_order" id="edit_cat_order" class="adm-input">
                    </div>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Description</label>
                    <textarea name="description" id="edit_cat_desc" class="adm-textarea" rows="2"></textarea>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-navy">Save Category</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.btn-edit-category').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_cat_id').value = this.dataset.id;
        document.getElementById('edit_cat_name').value = this.dataset.name;
        document.getElementById('edit_cat_slug').value = this.dataset.slug;
        document.getElementById('edit_cat_icon').value = this.dataset.icon;
        document.getElementById('edit_cat_desc').value = this.dataset.desc;
        document.getElementById('edit_cat_order').value = this.dataset.order;
        document.getElementById('modalEditCategory').classList.add('show');
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

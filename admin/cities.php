<?php
/**
 * EstateVista India - Prime Cities & Localities Management (Module 10)
 * Manage metropolitan hubs, state alignments, imagery, and locality tags.
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

        // 1. Create City
        if ($action === 'create_city') {
            $name = sanitize($_POST['name'] ?? '');
            $slug = sanitize($_POST['slug'] ?? '');
            $state = sanitize($_POST['state'] ?? '');
            $tagline = sanitize($_POST['tagline'] ?? '');
            $image_url = sanitize($_POST['image_url'] ?? '');
            $display_order = (int)($_POST['display_order'] ?? 0);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;

            if (empty($name)) {
                $error_msg = "City name is required.";
            } else {
                if (empty($slug)) {
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
                }
                if (empty($image_url)) {
                    $image_url = 'https://images.unsplash.com/photo-1570168007204-dfb528c6958f?auto=format&fit=crop&w=800&q=80';
                }

                try {
                    $chk = $db->prepare("SELECT id FROM cities WHERE slug = :s LIMIT 1");
                    $chk->execute([':s' => $slug]);
                    if ($chk->fetch()) {
                        $error_msg = "A city with slug '{$slug}' already exists.";
                    } else {
                        $stmt = $db->prepare("
                            INSERT INTO cities (name, slug, state, tagline, image_url, display_order, is_featured, created_at)
                            VALUES (:name, :slug, :state, :tagline, :image, :ord, :feat, NOW())
                        ");
                        $stmt->execute([
                            ':name' => $name,
                            ':slug' => $slug,
                            ':state' => $state,
                            ':tagline' => $tagline,
                            ':image' => $image_url,
                            ':ord' => $display_order,
                            ':feat' => $is_featured
                        ]);
                        log_admin_activity('Created Prime City', "Added city {$name} ({$state})");
                        $success_msg = "City '{$name}' added to portfolio.";
                    }
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 2. Edit City
        elseif ($action === 'edit_city') {
            $city_id = (int)($_POST['city_id'] ?? 0);
            $name = sanitize($_POST['name'] ?? '');
            $slug = sanitize($_POST['slug'] ?? '');
            $state = sanitize($_POST['state'] ?? '');
            $tagline = sanitize($_POST['tagline'] ?? '');
            $image_url = sanitize($_POST['image_url'] ?? '');
            $display_order = (int)($_POST['display_order'] ?? 0);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;

            if ($city_id <= 0 || empty($name)) {
                $error_msg = "City ID and name are required.";
            } else {
                try {
                    $stmt = $db->prepare("
                        UPDATE cities
                        SET name = :name, slug = :slug, state = :state, tagline = :tagline,
                            image_url = :image, display_order = :ord, is_featured = :feat
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':name' => $name,
                        ':slug' => $slug,
                        ':state' => $state,
                        ':tagline' => $tagline,
                        ':image' => $image_url,
                        ':ord' => $display_order,
                        ':feat' => $is_featured,
                        ':id' => $city_id
                    ]);
                    log_admin_activity('Updated City', "Updated details for city #{$city_id} ({$name})");
                    $success_msg = "City '{$name}' updated successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 3. Delete City
        elseif ($action === 'delete_city') {
            $city_id = (int)($_POST['city_id'] ?? 0);
            if ($city_id > 0) {
                try {
                    // Check if city has linked properties
                    $chk = $db->prepare("SELECT COUNT(*) FROM properties WHERE city_id = :cid");
                    $chk->execute([':cid' => $city_id]);
                    $cnt = (int)$chk->fetchColumn();
                    if ($cnt > 0) {
                        $error_msg = "Cannot delete this city because it currently hosts {$cnt} property listings.";
                    } else {
                        $stmt = $db->prepare("DELETE FROM cities WHERE id = :id");
                        $stmt->execute([':id' => $city_id]);
                        log_admin_activity('Deleted City', "Deleted city #{$city_id}");
                        $success_msg = "City deleted successfully.";
                    }
                } catch (PDOException $e) {
                    $error_msg = "Error deleting city: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Cities with live property count
$cities = $db->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM properties p WHERE p.city_id = c.id AND p.approval_status = 'approved') as live_property_count,
           (SELECT COUNT(*) FROM properties p WHERE p.city_id = c.id AND p.approval_status = 'pending') as pending_property_count
    FROM cities c
    ORDER BY c.display_order ASC, c.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Cities & Prime Metros";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                    Prime Cities & Luxury Markets
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                    Configure top metropolitan centers, architectural hubs, and regional showcase parameters.
                </p>
            </div>
            <button class="adm-btn adm-btn-gold" data-adm-modal-target="modalAddCity">
                <i class="fa-solid fa-plus"></i> Add New City
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

        <!-- Cities Cards Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <?php foreach ($cities as $c): ?>
                <div class="adm-card" style="margin-bottom: 0; display: flex; flex-direction: column;">
                    <div style="height: 140px; position: relative; overflow: hidden; background: #0B1528;">
                        <img src="<?= e(safe_image_url($c['image_url'], 'city')) ?>" alt="<?= e($c['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.85;" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/city-placeholder.svg';">
                        <div style="position: absolute; bottom: 0.75rem; left: 1rem; right: 1rem; display: flex; justify-content: space-between; align-items: flex-end;">
                            <div>
                                <span style="background: var(--adm-gold); color: #060B14; font-size: 0.7rem; font-weight: 800; padding: 0.2rem 0.5rem; border-radius: 4px; text-transform: uppercase;">
                                    <?= e($c['state']) ?>
                                </span>
                                <h3 style="color: #FFFFFF; font-size: 1.25rem; font-weight: 800; margin-top: 0.25rem; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
                                    <?= e($c['name']) ?>
                                </h3>
                            </div>
                            <?php if ($c['is_featured']): ?>
                                <span style="background: #10B981; color: #FFFFFF; font-size: 0.68rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 12px;">
                                    <i class="fa-solid fa-star"></i> Featured
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="adm-card-body" style="flex: 1; display: flex; flex-direction: column; justify-content: space-between; padding: 1.25rem;">
                        <div>
                            <p style="font-size: 0.82rem; color: var(--adm-text-muted); margin-bottom: 0.75rem; font-style: italic;">
                                "<?= e($c['tagline'] ?: 'Premier Luxury Destination') ?>"
                            </p>
                            <div style="display: flex; gap: 1rem; font-size: 0.82rem; border-top: 1px solid #F1F5F9; padding-top: 0.75rem;">
                                <div>
                                    <strong style="color: var(--adm-navy);"><?= $c['live_property_count'] ?></strong>
                                    <span style="color: var(--adm-text-muted);">Live Properties</span>
                                </div>
                                <?php if ($c['pending_property_count'] > 0): ?>
                                    <div>
                                        <strong style="color: #D97706;"><?= $c['pending_property_count'] ?></strong>
                                        <span style="color: var(--adm-text-muted);">In Queue</span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <strong style="color: var(--adm-navy);">Order #<?= $c['display_order'] ?></strong>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #F1F5F9; padding-top: 0.85rem; margin-top: 1rem;">
                            <a href="<?= BASE_URL ?>properties.php?city=<?= urlencode($c['name']) ?>" target="_blank" style="font-size: 0.8rem; font-weight: 600; color: #2563EB; text-decoration: none;">
                                View Public Showcase <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.7rem;"></i>
                            </a>
                            <div style="display: flex; gap: 0.35rem;">
                                <button type="button" class="adm-btn adm-btn-sm adm-btn-outline btn-edit-city"
                                    data-id="<?= $c['id'] ?>"
                                    data-name="<?= e($c['name']) ?>"
                                    data-slug="<?= e($c['slug']) ?>"
                                    data-state="<?= e($c['state']) ?>"
                                    data-tagline="<?= e($c['tagline']) ?>"
                                    data-image="<?= e($c['image_url']) ?>"
                                    data-order="<?= e($c['display_order']) ?>"
                                    data-featured="<?= $c['is_featured'] ?>"
                                    title="Edit City">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                
                                <form method="POST" action="cities.php" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                    <input type="hidden" name="action" value="delete_city">
                                    <input type="hidden" name="city_id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger adm-confirm-delete" data-item-name="City '<?= e($c['name']) ?>'" title="Delete City">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<!-- Modal: Add New City -->
<div class="adm-modal-backdrop" id="modalAddCity">
    <div class="adm-modal" style="max-width: 550px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-city" style="color: var(--adm-gold);"></i> Add Prime Real Estate City</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="cities.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="create_city">
            <div class="adm-modal-body">
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">City Name *</label>
                        <input type="text" name="name" class="adm-input" required placeholder="e.g. Kolkata">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">State *</label>
                        <input type="text" name="state" class="adm-input" required placeholder="e.g. West Bengal">
                    </div>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">URL Slug (Auto-generated if blank)</label>
                    <input type="text" name="slug" class="adm-input" placeholder="e.g. kolkata">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">City Tagline / Luxury Descriptor</label>
                    <input type="text" name="tagline" class="adm-input" placeholder="e.g. Heritage Mansions & Cosmopolitan Enclaves">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Cover Image URL</label>
                    <input type="url" name="image_url" class="adm-input" placeholder="https://images.unsplash.com/...">
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Display Order (Sort weight)</label>
                        <input type="number" name="display_order" class="adm-input" value="10">
                    </div>
                    <div class="adm-form-group" style="display: flex; align-items: center; margin-top: 1.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 600;">
                            <input type="checkbox" name="is_featured" value="1" checked>
                            Feature on Homepage
                        </label>
                    </div>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-gold">Create City Hub</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit City -->
<div class="adm-modal-backdrop" id="modalEditCity">
    <div class="adm-modal" style="max-width: 550px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-pen-to-square" style="color: var(--adm-gold);"></i> Edit City Hub</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="cities.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="edit_city">
            <input type="hidden" name="city_id" id="edit_city_id">
            <div class="adm-modal-body">
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">City Name *</label>
                        <input type="text" name="name" id="edit_city_name" class="adm-input" required>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">State *</label>
                        <input type="text" name="state" id="edit_city_state" class="adm-input" required>
                    </div>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">URL Slug</label>
                    <input type="text" name="slug" id="edit_city_slug" class="adm-input" required>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Tagline</label>
                    <input type="text" name="tagline" id="edit_city_tagline" class="adm-input">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Cover Image URL</label>
                    <input type="url" name="image_url" id="edit_city_image" class="adm-input" required>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Display Order</label>
                        <input type="number" name="display_order" id="edit_city_order" class="adm-input">
                    </div>
                    <div class="adm-form-group" style="display: flex; align-items: center; margin-top: 1.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 600;">
                            <input type="checkbox" name="is_featured" id="edit_city_featured" value="1">
                            Feature on Homepage
                        </label>
                    </div>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-navy">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.btn-edit-city').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_city_id').value = this.dataset.id;
        document.getElementById('edit_city_name').value = this.dataset.name;
        document.getElementById('edit_city_slug').value = this.dataset.slug;
        document.getElementById('edit_city_state').value = this.dataset.state;
        document.getElementById('edit_city_tagline').value = this.dataset.tagline;
        document.getElementById('edit_city_image').value = this.dataset.image;
        document.getElementById('edit_city_order').value = this.dataset.order;
        document.getElementById('edit_city_featured').checked = (this.dataset.featured === '1');
        document.getElementById('modalEditCity').classList.add('show');
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

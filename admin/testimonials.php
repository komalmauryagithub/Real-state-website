<?php
/**
 * EstateVista India - Client Testimonials Management (Module 14)
 * Curate HNW buyer testimonials, executive endorsements, and luxury homeowner reviews.
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

        // 1. Create Testimonial
        if ($action === 'create_testimonial') {
            $name = sanitize($_POST['client_name'] ?? '');
            $title = sanitize($_POST['client_title'] ?? '');
            $location = sanitize($_POST['location'] ?? 'Mumbai');
            $review = sanitize($_POST['review'] ?? '');
            $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
            $avatar = sanitize($_POST['client_avatar'] ?? '');
            $verified = isset($_POST['verified_buyer']) ? 1 : 0;

            if (empty($name) || empty($review)) {
                $error_msg = "Client name and review testimonial text are required.";
            } else {
                if (empty($avatar)) {
                    $avatar = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=200&q=80';
                }

                try {
                    $stmt = $db->prepare("
                        INSERT INTO testimonials (client_name, client_title, location, review, rating, client_avatar, verified_buyer, created_at)
                        VALUES (:name, :title, :loc, :rev, :rat, :av, :ver, NOW())
                    ");
                    $stmt->execute([
                        ':name' => $name,
                        ':title' => $title,
                        ':loc' => $location,
                        ':rev' => $review,
                        ':rat' => $rating,
                        ':av' => $avatar,
                        ':ver' => $verified
                    ]);
                    log_admin_activity('Created Testimonial', "Added testimonial by {$name}");
                    $success_msg = "Testimonial by {$name} published.";
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 2. Edit Testimonial
        elseif ($action === 'edit_testimonial') {
            $test_id = (int)($_POST['testimonial_id'] ?? 0);
            $name = sanitize($_POST['client_name'] ?? '');
            $title = sanitize($_POST['client_title'] ?? '');
            $location = sanitize($_POST['location'] ?? 'Mumbai');
            $review = sanitize($_POST['review'] ?? '');
            $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
            $avatar = sanitize($_POST['client_avatar'] ?? '');
            $verified = isset($_POST['verified_buyer']) ? 1 : 0;

            if ($test_id <= 0 || empty($name) || empty($review)) {
                $error_msg = "Testimonial ID, client name, and review text are required.";
            } else {
                try {
                    $stmt = $db->prepare("
                        UPDATE testimonials
                        SET client_name = :name, client_title = :title, location = :loc,
                            review = :rev, rating = :rat, client_avatar = :av, verified_buyer = :ver
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':name' => $name,
                        ':title' => $title,
                        ':loc' => $location,
                        ':rev' => $review,
                        ':rat' => $rating,
                        ':av' => $avatar,
                        ':ver' => $verified,
                        ':id' => $test_id
                    ]);
                    log_admin_activity('Updated Testimonial', "Updated testimonial #{$test_id} ({$name})");
                    $success_msg = "Testimonial updated successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 3. Delete Testimonial
        elseif ($action === 'delete_testimonial') {
            $test_id = (int)($_POST['testimonial_id'] ?? 0);
            if ($test_id > 0) {
                try {
                    $stmt = $db->prepare("DELETE FROM testimonials WHERE id = :id");
                    $stmt->execute([':id' => $test_id]);
                    log_admin_activity('Deleted Testimonial', "Deleted testimonial #{$test_id}");
                    $success_msg = "Testimonial removed successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Error deleting testimonial: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Testimonials
$testimonials = $db->query("SELECT * FROM testimonials ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Client Reviews & Endorsements";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                    Client Testimonials & Executive Endorsements
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                    Manage reviews from high-net-worth investors, family offices, and verified trophy home buyers.
                </p>
            </div>
            <button class="adm-btn adm-btn-gold" data-adm-modal-target="modalAddTestimonial">
                <i class="fa-solid fa-plus"></i> Add Testimonial
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

        <!-- Testimonials Cards Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 1.5rem;">
            <?php foreach ($testimonials as $t): ?>
                <div class="adm-card" style="margin-bottom: 0; display: flex; flex-direction: column;">
                    <div class="adm-card-body" style="flex: 1; display: flex; flex-direction: column; justify-content: space-between; padding: 1.5rem;">
                        <div>
                            <!-- Rating Stars -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <div style="color: #F59E0B; font-size: 0.9rem;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fa-solid fa-star <?= ($i <= $t['rating']) ? '' : 'style=\"opacity: 0.2;\"' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($t['verified_buyer']): ?>
                                    <span class="adm-status-badge adm-badge-approved">
                                        <i class="fa-solid fa-circle-check"></i> Verified Buyer
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Quote -->
                            <p style="font-size: 0.88rem; color: #334155; line-height: 1.6; font-style: italic; margin-bottom: 1.25rem;">
                                "<?= e($t['review']) ?>"
                            </p>
                        </div>

                        <div>
                            <!-- Client Info -->
                            <div style="display: flex; align-items: center; gap: 0.85rem; border-top: 1px solid #F1F5F9; padding-top: 1rem;">
                                <img src="<?= e(safe_image_url($t['client_avatar'], 'avatar')) ?>" alt="<?= e($t['client_name']) ?>"
                                     style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--adm-gold);"
                                     onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/avatar-placeholder.svg';">
                                <div style="flex: 1;">
                                    <div style="font-weight: 700; color: var(--adm-navy); font-size: 0.95rem;"><?= e($t['client_name']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--adm-text-muted);">
                                        <?= e($t['client_title']) ?> &bull; <?= e($t['location']) ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.35rem;">
                                    <button type="button" class="adm-btn adm-btn-sm adm-btn-outline btn-edit-test"
                                        data-id="<?= $t['id'] ?>"
                                        data-name="<?= e($t['client_name']) ?>"
                                        data-title="<?= e($t['client_title']) ?>"
                                        data-location="<?= e($t['location']) ?>"
                                        data-review="<?= e($t['review']) ?>"
                                        data-rating="<?= $t['rating'] ?>"
                                        data-avatar="<?= e($t['client_avatar']) ?>"
                                        data-verified="<?= $t['verified_buyer'] ?>"
                                        title="Edit Review">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <form method="POST" action="testimonials.php" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                        <input type="hidden" name="action" value="delete_testimonial">
                                        <input type="hidden" name="testimonial_id" value="<?= $t['id'] ?>">
                                        <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger adm-confirm-delete" data-item-name="Testimonial by '<?= e($t['client_name']) ?>'" title="Delete Review">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<!-- Modal: Add Testimonial -->
<div class="adm-modal-backdrop" id="modalAddTestimonial">
    <div class="adm-modal" style="max-width: 550px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-comment-dots" style="color: var(--adm-gold);"></i> Add Client Testimonial</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="testimonials.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="create_testimonial">
            <div class="adm-modal-body">
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Client Full Name *</label>
                        <input type="text" name="client_name" class="adm-input" required placeholder="e.g. Gautam Singhania">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Title / Designation</label>
                        <input type="text" name="client_title" class="adm-input" placeholder="e.g. Managing Director, Raymond Group">
                    </div>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">City / Location</label>
                        <input type="text" name="location" class="adm-input" value="Worli, South Mumbai">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Rating (Stars)</label>
                        <select name="rating" class="adm-select">
                            <option value="5" selected>5 Stars (Exceptional)</option>
                            <option value="4">4 Stars (Very Good)</option>
                            <option value="3">3 Stars</option>
                        </select>
                    </div>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Client Avatar URL</label>
                    <input type="url" name="client_avatar" class="adm-input" placeholder="https://images.unsplash.com/...">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Endorsement / Review Text *</label>
                    <textarea name="review" class="adm-textarea" rows="3" required placeholder="Detailed quote regarding advisory experience, privacy, and transaction excellence..."></textarea>
                </div>
                <div class="adm-form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 600;">
                        <input type="checkbox" name="verified_buyer" value="1" checked>
                        Mark as Verified HNW Buyer
                    </label>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-gold">Publish Testimonial</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Testimonial -->
<div class="adm-modal-backdrop" id="modalEditTestimonial">
    <div class="adm-modal" style="max-width: 550px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-pen-to-square" style="color: var(--adm-gold);"></i> Edit Testimonial</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="testimonials.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="edit_testimonial">
            <input type="hidden" name="testimonial_id" id="edit_t_id">
            <div class="adm-modal-body">
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Client Name *</label>
                        <input type="text" name="client_name" id="edit_t_name" class="adm-input" required>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Title / Designation</label>
                        <input type="text" name="client_title" id="edit_t_title" class="adm-input">
                    </div>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Location</label>
                        <input type="text" name="location" id="edit_t_loc" class="adm-input">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Rating</label>
                        <select name="rating" id="edit_t_rating" class="adm-select">
                            <option value="5">5 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="3">3 Stars</option>
                        </select>
                    </div>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Client Avatar URL</label>
                    <input type="url" name="client_avatar" id="edit_t_avatar" class="adm-input" required>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Review Text *</label>
                    <textarea name="review" id="edit_t_review" class="adm-textarea" rows="3" required></textarea>
                </div>
                <div class="adm-form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 600;">
                        <input type="checkbox" name="verified_buyer" id="edit_t_verified" value="1">
                        Verified Buyer
                    </label>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-navy">Save Testimonial</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.btn-edit-test').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_t_id').value = this.dataset.id;
        document.getElementById('edit_t_name').value = this.dataset.name;
        document.getElementById('edit_t_title').value = this.dataset.title;
        document.getElementById('edit_t_loc').value = this.dataset.location;
        document.getElementById('edit_t_rating').value = this.dataset.rating;
        document.getElementById('edit_t_avatar').value = this.dataset.avatar;
        document.getElementById('edit_t_review').value = this.dataset.review;
        document.getElementById('edit_t_verified').checked = (this.dataset.verified === '1');
        document.getElementById('modalEditTestimonial').classList.add('show');
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

<?php
/**
 * EstateVista India - Admin Pending Property Approvals Queue
 * Editorial review desk, one-click approve, and rejection with feedback.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';

require_admin_login();
require_admin_role(['super_admin', 'admin']);

$current_admin = get_logged_in_admin();
$db = Database::getConnection();

// Handle Actions (Approve / Reject) BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $propId = (int)($_POST['property_id'] ?? 0);

    if ($propId > 0) {
        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE properties SET approval_status = 'approved', rejection_reason = NULL, is_verified = 1 WHERE id = :id");
            $stmt->execute([':id' => $propId]);
            log_admin_activity($current_admin['id'], $current_admin['name'], 'Curation Approved', "Approved and published property #{$propId}");
            $_SESSION['admin_flash_success'] = "Property #{$propId} has been approved and is now live across the portal.";
        } elseif ($action === 'reject') {
            $reason = trim($_POST['rejection_reason'] ?? 'Editorial revisions requested before publication.');
            $stmt = $db->prepare("UPDATE properties SET approval_status = 'rejected', rejection_reason = :reason WHERE id = :id");
            $stmt->execute([':reason' => $reason, ':id' => $propId]);
            log_admin_activity($current_admin['id'], $current_admin['name'], 'Curation Revisions Requested', "Rejected property #{$propId} with reason: {$reason}");
            $_SESSION['admin_flash_success'] = "Property #{$propId} marked as Revisions Requested. Reason logged for listing owner.";
        }
        header('Location: ' . BASE_URL . 'admin/approvals.php');
        exit;
    }
}

$page_title = 'Pending Property Approvals';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
require_once __DIR__ . '/includes/admin_navbar.php';

// Fetch all pending properties
$stmt = $db->query("
    SELECT p.*, c.name as city_name, cat.name as category_name,
           u.full_name as submitter_name, u.email as submitter_email, u.phone as submitter_phone
    FROM properties p
    LEFT JOIN cities c ON p.city_id = c.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.approval_status = 'pending'
    ORDER BY p.created_at ASC
");
$pending_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="adm-toolbar">
    <div>
        <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--adm-navy);">
            <i class="fa-solid fa-clock-rotate-left" style="color: #D97706;"></i>
            <span>Editorial & Title Verification Queue (<?= count($pending_list) ?>)</span>
        </h2>
        <p style="font-size: 0.85rem; color: var(--adm-text-muted);">
            Examine submitted property dossiers, verify authenticity, and approve for public catalog listing.
        </p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>admin/properties.php" class="adm-btn adm-btn-outline">
            <i class="fa-solid fa-list"></i> View All Properties
        </a>
    </div>
</div>

<?php if (!empty($pending_list)): ?>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <?php foreach ($pending_list as $prop): ?>
            <div class="adm-card" style="margin-bottom: 0;">
                <div class="adm-card-body" style="padding: 1.5rem;">
                    <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: flex-start;">
                        <!-- Thumbnail -->
                        <div style="width: 180px; height: 130px; border-radius: 10px; overflow: hidden; background: #0B1528; flex-shrink: 0;">
                            <img src="<?= e(safe_image_url($prop['cover_image'], 'property')) ?>" alt="<?= e($prop['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                        </div>

                        <!-- Info -->
                        <div style="flex: 1; min-width: 280px;">
                            <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.35rem; flex-wrap: wrap;">
                                <span class="adm-status-badge adm-badge-pending">
                                    <i class="fa-solid fa-clock"></i> Pending Curation
                                </span>
                                <span style="font-size: 0.75rem; font-weight: 700; background: #F1F5F9; color: #475569; padding: 0.2rem 0.55rem; border-radius: 4px;">
                                    <?= e($prop['category_name']) ?> &bull; For <?= strtoupper(e($prop['purpose'])) ?>
                                </span>
                                <span style="font-size: 0.75rem; color: #94A3B8;">
                                    Submitted: <?= format_admin_date($prop['created_at']) ?>
                                </span>
                            </div>

                            <h3 style="font-family: 'Cinzel', serif; font-size: 1.25rem; color: var(--adm-navy); margin: 0 0 0.35rem;">
                                <?= e($prop['title']) ?>
                            </h3>

                            <div style="font-size: 0.85rem; color: #64748B; margin-bottom: 0.5rem;">
                                <i class="fa-solid fa-location-dot" style="color: var(--adm-gold);"></i> 
                                <?= e($prop['locality']) ?>, <?= e($prop['city_name']) ?> &bull; 
                                <strong><?= (int)$prop['bhk'] ?> BHK</strong> &bull; 
                                <?= format_inr_number($prop['carpet_area_sqft']) ?> sq.ft Carpet &bull; 
                                <?= e($prop['furnishing']) ?>
                            </div>

                            <div style="font-size: 0.82rem; background: #F8FAFC; padding: 0.6rem 0.85rem; border-radius: 6px; border: 1px solid #E2E8F0; margin-bottom: 0.75rem; line-height: 1.5;">
                                <strong>Description:</strong> <?= e(substr($prop['description'], 0, 180)) ?>...
                            </div>

                            <div style="display: flex; gap: 1.5rem; font-size: 0.8rem; color: #475569; flex-wrap: wrap;">
                                <div><strong>Expected Price:</strong> <span style="color: #B45309; font-weight: 700;"><?= e($prop['price_display']) ?></span></div>
                                <div><strong>RERA Registration:</strong> <code><?= e($prop['rera_number']) ?></code></div>
                                <div><strong>Submitter:</strong> <?= e($prop['submitter_name'] ?: $prop['contact_person_name']) ?> (<?= e($prop['contact_person_phone']) ?>)</div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div style="display: flex; flex-direction: column; gap: 0.6rem; min-width: 170px;">
                            <a href="<?= BASE_URL ?>property-detail.php?slug=<?= urlencode($prop['slug']) ?>" target="_blank" class="adm-btn adm-btn-outline" style="justify-content: center;">
                                <i class="fa-solid fa-eye"></i> Full Dossier
                            </a>

                            <form method="POST" action="<?= BASE_URL ?>admin/approvals.php">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="property_id" value="<?= $prop['id'] ?>">
                                <button type="submit" class="adm-btn adm-btn-success" style="width: 100%; justify-content: center;">
                                    <i class="fa-solid fa-check"></i> One-Click Approve
                                </button>
                            </form>

                            <button type="button" class="adm-btn adm-btn-danger" data-adm-modal-target="rejectModal<?= $prop['id'] ?>" style="justify-content: center;">
                                <i class="fa-solid fa-xmark"></i> Request Revisions
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rejection Modal for this Property -->
            <div class="adm-modal-backdrop" id="rejectModal<?= $prop['id'] ?>">
                <div class="adm-modal">
                    <form method="POST" action="<?= BASE_URL ?>admin/approvals.php">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="property_id" value="<?= $prop['id'] ?>">

                        <div class="adm-modal-header">
                            <h3 class="adm-modal-title">Request Revisions for Listing #<?= $prop['id'] ?></h3>
                            <button type="button" class="adm-modal-close">&times;</button>
                        </div>
                        <div class="adm-modal-body">
                            <p style="font-size: 0.85rem; color: #64748B; margin-bottom: 1rem;">
                                Please specify the editorial feedback or missing documentation. The owner will see this notice directly in their dashboard.
                            </p>
                            <div class="adm-form-group">
                                <label class="adm-label">Revision Reason / Feedback *</label>
                                <textarea name="rejection_reason" class="adm-textarea" rows="4" required placeholder="e.g. Please provide a clear RERA registration copy and update carpet area measurements as per sanctioned layout."></textarea>
                            </div>
                        </div>
                        <div class="adm-modal-footer">
                            <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                            <button type="submit" class="adm-btn adm-btn-danger">
                                <i class="fa-solid fa-triangle-exclamation"></i> Send Revision Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="adm-card">
        <div class="adm-card-body" style="text-align: center; padding: 4rem 2rem;">
            <div style="width: 70px; height: 70px; border-radius: 50%; background: #ECFDF5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.5rem;">
                <i class="fa-solid fa-check"></i>
            </div>
            <h3 style="font-family: 'Cinzel', serif; font-size: 1.5rem; color: var(--adm-navy); margin-bottom: 0.5rem;">
                Curation Queue is Clear
            </h3>
            <p style="color: #64748B; max-width: 480px; margin: 0 auto 1.5rem;">
                All submitted luxury residences, villas, and penthouses have been verified and approved for public publication.
            </p>
            <a href="<?= BASE_URL ?>admin/properties.php" class="adm-btn adm-btn-gold">
                <i class="fa-solid fa-building"></i> View All Properties
            </a>
        </div>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>

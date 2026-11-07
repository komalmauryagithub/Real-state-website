<?php
/**
 * EstateVista India - Newsletter Subscribers Management (Module 17)
 * Monitor subscriber database, investor dispatch lists, and manage unsubscribes.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';

require_admin_login();
require_admin_role(['super_admin', 'admin']);

$db = Database::getConnection();
$success_msg = '';
$error_msg = '';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = $db->query("
        SELECT id, email, status, subscribed_at 
        FROM newsletter_subscribers 
        ORDER BY subscribed_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $headers = ['Subscriber ID', 'Email Address', 'Subscription Status', 'Subscribed At'];
    export_to_csv('EstateVista_Newsletter_Subscribers_' . date('Y-m-d') . '.csv', $headers, $rows);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_admin_csrf()) {
        $error_msg = "Security token expired. Please reload and try again.";
    } else {
        $action = sanitize($_POST['action'] ?? '');

        // 1. Add Subscriber
        if ($action === 'create_subscriber') {
            $email = sanitize($_POST['email'] ?? '');
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_msg = "A valid email address is required.";
            } else {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO newsletter_subscribers (email, status, subscribed_at)
                        VALUES (:e, 'active', NOW())
                        ON DUPLICATE KEY UPDATE status = 'active'
                    ");
                    $stmt->execute([':e' => $email]);
                    log_admin_activity('Added Newsletter Subscriber', "Subscribed {$email}");
                    $success_msg = "Subscriber {$email} added successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 2. Toggle Status
        elseif ($action === 'toggle_status') {
            $sub_id = (int)($_POST['subscriber_id'] ?? 0);
            $new_status = in_array($_POST['new_status'] ?? '', ['active', 'unsubscribed']) ? $_POST['new_status'] : 'active';
            if ($sub_id > 0) {
                try {
                    $stmt = $db->prepare("UPDATE newsletter_subscribers SET status = :s WHERE id = :id");
                    $stmt->execute([':s' => $new_status, ':id' => $sub_id]);
                    log_admin_activity('Toggled Subscriber Status', "Subscriber #{$sub_id} set to {$new_status}");
                    $success_msg = "Subscriber status updated to " . ucfirst($new_status) . ".";
                } catch (PDOException $e) {
                    $error_msg = "Error updating status: " . $e->getMessage();
                }
            }
        }

        // 3. Delete Subscriber
        elseif ($action === 'delete_subscriber') {
            $sub_id = (int)($_POST['subscriber_id'] ?? 0);
            if ($sub_id > 0) {
                try {
                    $stmt = $db->prepare("DELETE FROM newsletter_subscribers WHERE id = :id");
                    $stmt->execute([':id' => $sub_id]);
                    log_admin_activity('Deleted Subscriber', "Deleted subscriber #{$sub_id}");
                    $success_msg = "Subscriber removed from database.";
                } catch (PDOException $e) {
                    $error_msg = "Error deleting subscriber: " . $e->getMessage();
                }
            }
        }
    }
}

// Search and Filter
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM newsletter_subscribers WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND email LIKE :s";
    $params[':s'] = "%{$search}%";
}

if (!empty($status_filter) && in_array($status_filter, ['active', 'unsubscribed'])) {
    $sql .= " AND status = :st";
    $params[':st'] = $status_filter;
}

$sql .= " ORDER BY subscribed_at DESC";
$subStmt = $db->prepare($sql);
$subStmt->execute($params);
$subscribers = $subStmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_subs = (int)$db->query("SELECT COUNT(*) FROM newsletter_subscribers")->fetchColumn();
$active_subs = (int)$db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'")->fetchColumn();
$unsub_count = (int)$db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'unsubscribed'")->fetchColumn();

$page_title = "Newsletter & Investor Subscribers";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                    Newsletter Subscribers & Private Circulation
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                    Subscribers receiving weekly luxury market digests, off-market launches, and wealth advisories.
                </p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <a href="subscribers.php?export=csv" class="adm-btn adm-btn-gold">
                    <i class="fa-solid fa-file-csv"></i> Export Subscribers CSV
                </a>
                <button class="adm-btn adm-btn-navy" data-adm-modal-target="modalAddSub">
                    <i class="fa-solid fa-plus"></i> Add Subscriber
                </button>
            </div>
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
                    <div class="adm-kpi-val"><?= $total_subs ?></div>
                    <div class="adm-kpi-label">Total Registered Readers</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-blue">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: #059669;"><?= $active_subs ?></div>
                    <div class="adm-kpi-label">Active Delivering Emails</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-green">
                    <i class="fa-solid fa-envelope-circle-check"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: #64748B;"><?= $unsub_count ?></div>
                    <div class="adm-kpi-label">Unsubscribed Readers</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-gold">
                    <i class="fa-solid fa-user-xmark"></i>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="adm-card">
            <div class="adm-card-body" style="padding: 1.25rem;">
                <form method="GET" action="subscribers.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                    <div class="adm-search-input-wrap" style="flex: 1; min-width: 250px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" class="adm-input" placeholder="Search subscriber email address..." value="<?= e($search) ?>">
                    </div>
                    <div style="width: 180px;">
                        <select name="status" class="adm-select" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="active" <?= ($status_filter === 'active') ? 'selected' : '' ?>>Active Only</option>
                            <option value="unsubscribed" <?= ($status_filter === 'unsubscribed') ? 'selected' : '' ?>>Unsubscribed</option>
                        </select>
                    </div>
                    <button type="submit" class="adm-btn adm-btn-navy">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <?php if (!empty($search) || !empty($status_filter)): ?>
                        <a href="subscribers.php" class="adm-btn adm-btn-outline">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Subscribers Table -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title">
                    <i class="fa-solid fa-envelopes-bulk" style="color: var(--adm-gold);"></i>
                    Subscribers List (<?= count($subscribers) ?>)
                </div>
            </div>
            <div class="adm-table-responsive">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Subscriber ID</th>
                            <th>Email Address</th>
                            <th>Status</th>
                            <th>Subscribed Date</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subscribers)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 3rem; color: var(--adm-text-muted);">
                                    No subscribers found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subscribers as $s): ?>
                                <tr>
                                    <td><strong>#<?= $s['id'] ?></strong></td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--adm-navy);"><?= e($s['email']) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($s['status'] === 'active'): ?>
                                            <span class="adm-status-badge adm-badge-active">Active</span>
                                        <?php else: ?>
                                            <span class="adm-status-badge adm-badge-lost">Unsubscribed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.82rem; color: var(--adm-text-muted);">
                                        <?= format_admin_date($s['subscribed_at']) ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; gap: 0.35rem;">
                                            <form method="POST" action="subscribers.php" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="subscriber_id" value="<?= $s['id'] ?>">
                                                <?php if ($s['status'] === 'active'): ?>
                                                    <input type="hidden" name="new_status" value="unsubscribed">
                                                    <button type="submit" class="adm-btn adm-btn-sm adm-btn-outline" title="Unsubscribe">
                                                        <i class="fa-solid fa-ban"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <input type="hidden" name="new_status" value="active">
                                                    <button type="submit" class="adm-btn adm-btn-sm adm-btn-outline" style="color: #10B981;" title="Reactivate">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </form>

                                            <form method="POST" action="subscribers.php" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                                <input type="hidden" name="action" value="delete_subscriber">
                                                <input type="hidden" name="subscriber_id" value="<?= $s['id'] ?>">
                                                <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger adm-confirm-delete" data-item-name="Subscriber '<?= e($s['email']) ?>'" title="Delete Subscriber">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
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

<!-- Modal: Add Subscriber -->
<div class="adm-modal-backdrop" id="modalAddSub">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-paper-plane" style="color: var(--adm-gold);"></i> Add Subscriber</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="subscribers.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="create_subscriber">
            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Investor / Reader Email Address *</label>
                    <input type="email" name="email" class="adm-input" required placeholder="investor@familyoffice.com">
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-gold">Add to Circulation</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

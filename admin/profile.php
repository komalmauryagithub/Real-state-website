<?php
/**
 * EstateVista India - Admin Profile & Security Settings (Module 19)
 * Manage personal administrator credentials, contact information, avatar, and password changes.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';

require_admin_login();

$db = Database::getConnection();
$current_admin = get_logged_admin();
$admin_id = $current_admin['id'];

$success_msg = '';
$error_msg = '';

// Handle POST updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_admin_csrf()) {
        $error_msg = "Security token expired. Please reload and try again.";
    } else {
        $action = sanitize($_POST['action'] ?? '');

        // 1. Update Profile Info
        if ($action === 'update_profile') {
            $name = sanitize($_POST['name'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $avatar = sanitize($_POST['avatar'] ?? '');

            if (empty($name)) {
                $error_msg = "Full name cannot be empty.";
            } else {
                if (empty($avatar)) {
                    $avatar = $current_admin['avatar'];
                }
                try {
                    $stmt = $db->prepare("
                        UPDATE admins 
                        SET name = :name, phone = :phone, avatar = :avatar
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':name' => $name,
                        ':phone' => $phone,
                        ':avatar' => $avatar,
                        ':id' => $admin_id
                    ]);

                    // Refresh session data
                    $_SESSION['admin_user']['name'] = $name;
                    $_SESSION['admin_user']['phone'] = $phone;
                    $_SESSION['admin_user']['avatar'] = $avatar;
                    $current_admin = $_SESSION['admin_user'];

                    log_admin_activity('Updated Own Profile', "Updated profile contact details");
                    $success_msg = "Profile details updated successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 2. Change Password
        elseif ($action === 'change_password') {
            $current_pass = $_POST['current_password'] ?? '';
            $new_pass = $_POST['new_password'] ?? '';
            $confirm_pass = $_POST['confirm_password'] ?? '';

            if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
                $error_msg = "All password fields are required.";
            } elseif ($new_pass !== $confirm_pass) {
                $error_msg = "New password and confirmation password do not match.";
            } elseif (strlen($new_pass) < 6) {
                $error_msg = "New password must be at least 6 characters long.";
            } else {
                try {
                    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE id = :id LIMIT 1");
                    $stmt->execute([':id' => $admin_id]);
                    $hash = $stmt->fetchColumn();

                    if (!$hash || !password_verify($current_pass, $hash)) {
                        $error_msg = "Current password verification failed. Please try again.";
                    } else {
                        $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
                        $upStmt = $db->prepare("UPDATE admins SET password_hash = :p WHERE id = :id");
                        $upStmt->execute([':p' => $new_hash, ':id' => $admin_id]);

                        log_admin_activity('Changed Password', 'Successfully changed account password');
                        $success_msg = "Password changed successfully. Your account is secured.";
                    }
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Personal Activity Logs
$myLogs = $db->prepare("
    SELECT * FROM activity_logs 
    WHERE admin_id = :aid 
    ORDER BY created_at DESC 
    LIMIT 10
");
$myLogs->execute([':aid' => $admin_id]);
$logs = $myLogs->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Admin Profile & Credentials";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Header -->
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                Administrator Profile & Access Security
            </h1>
            <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                Manage your executive dossier, direct phone coordinates, security keys, and account audit logs.
            </p>
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

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; align-items: flex-start;">
            <!-- Left: Identity Card -->
            <div class="adm-card">
                <div class="adm-card-body" style="text-align: center; padding: 2rem 1.5rem;">
                    <div style="width: 100px; height: 100px; border-radius: 50%; margin: 0 auto 1.25rem; overflow: hidden; border: 3px solid var(--adm-gold); box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                        <img src="<?= e(safe_image_url($current_admin['avatar'] ?? null, 'avatar')) ?>" alt="<?= e($current_admin['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/avatar-placeholder.svg';">
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.25rem;">
                        <?= e($current_admin['name']) ?>
                    </h2>
                    <div style="display: inline-block; background: var(--adm-gold-soft); color: #92400E; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; padding: 0.25rem 0.75rem; border-radius: 12px; margin-bottom: 1.25rem;">
                        <i class="fa-solid fa-shield-halved"></i> <?= strtoupper(str_replace('_', ' ', e($current_admin['role']))) ?>
                    </div>

                    <div style="border-top: 1px solid #F1F5F9; padding-top: 1.25rem; text-align: left; font-size: 0.85rem; display: flex; flex-direction: column; gap: 0.75rem;">
                        <div>
                            <span style="color: var(--adm-text-muted); font-size: 0.75rem; display: block;">Official Email</span>
                            <strong style="color: var(--adm-navy);"><?= e($current_admin['email']) ?></strong>
                        </div>
                        <div>
                            <span style="color: var(--adm-text-muted); font-size: 0.75rem; display: block;">Direct Advisory Phone</span>
                            <strong style="color: var(--adm-navy);"><?= e(!empty($current_admin['phone']) ? $current_admin['phone'] : '+91 22 6985 4010') ?></strong>
                        </div>
                        <div>
                            <span style="color: var(--adm-text-muted); font-size: 0.75rem; display: block;">System Account ID</span>
                            <strong style="color: var(--adm-navy);">UID #<?= $admin_id ?></strong>
                        </div>
                        <div>
                            <span style="color: var(--adm-text-muted); font-size: 0.75rem; display: block;">Session Security</span>
                            <span class="adm-status-badge adm-badge-active">256-Bit SSL Encrypted</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Update Details & Password -->
            <div>
                <!-- Form 1: Profile Information -->
                <div class="adm-card">
                    <div class="adm-card-header">
                        <div class="adm-card-title">
                            <i class="fa-solid fa-user-pen" style="color: var(--adm-gold);"></i>
                            Executive Information
                        </div>
                    </div>
                    <div class="adm-card-body">
                        <form method="POST" action="profile.php">
                            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="adm-form-group">
                                <label class="adm-label">Full Name *</label>
                                <input type="text" name="name" class="adm-input" required value="<?= e($current_admin['name'] ?? '') ?>">
                            </div>

                            <div class="adm-grid-2">
                                <div class="adm-form-group">
                                    <label class="adm-label">Official Email (Read-Only)</label>
                                    <input type="email" class="adm-input" readonly value="<?= e($current_admin['email'] ?? '') ?>" style="background: #F8FAFC; color: #64748B; cursor: not-allowed;">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Direct Phone Number</label>
                                    <input type="text" name="phone" class="adm-input" value="<?= e($current_admin['phone'] ?? '') ?>" placeholder="+91 22 6985 4010">
                                </div>
                            </div>

                            <div class="adm-form-group">
                                <label class="adm-label">Avatar Image URL</label>
                                <input type="url" name="avatar" class="adm-input" value="<?= e($current_admin['avatar'] ?? '') ?>" placeholder="https://images.unsplash.com/...">
                            </div>

                            <div style="display: flex; justify-content: flex-end;">
                                <button type="submit" class="adm-btn adm-btn-navy">
                                    <i class="fa-solid fa-floppy-disk"></i> Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Form 2: Change Password -->
                <div class="adm-card">
                    <div class="adm-card-header">
                        <div class="adm-card-title">
                            <i class="fa-solid fa-lock" style="color: var(--adm-gold);"></i>
                            Change Account Password
                        </div>
                    </div>
                    <div class="adm-card-body">
                        <form method="POST" action="profile.php">
                            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                            <input type="hidden" name="action" value="change_password">

                            <div class="adm-form-group">
                                <label class="adm-label">Current Password *</label>
                                <input type="password" name="current_password" class="adm-input" required placeholder="Enter existing password">
                            </div>

                            <div class="adm-grid-2">
                                <div class="adm-form-group">
                                    <label class="adm-label">New Password *</label>
                                    <input type="password" name="new_password" class="adm-input" required placeholder="Minimum 6 characters">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Confirm New Password *</label>
                                    <input type="password" name="confirm_password" class="adm-input" required placeholder="Re-type new password">
                                </div>
                            </div>

                            <div style="display: flex; justify-content: flex-end;">
                                <button type="submit" class="adm-btn adm-btn-gold">
                                    <i class="fa-solid fa-key"></i> Update Security Credentials
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Personal Activity Log -->
                <div class="adm-card">
                    <div class="adm-card-header">
                        <div class="adm-card-title">
                            <i class="fa-solid fa-clock-rotate-left" style="color: var(--adm-gold);"></i>
                            My Recent Audit Trail
                        </div>
                    </div>
                    <div class="adm-card-body">
                        <?php if (empty($logs)): ?>
                            <p style="color: var(--adm-text-muted); font-size: 0.85rem;">No recent activities logged for this account.</p>
                        <?php else: ?>
                            <ul class="adm-activity-list">
                                <?php foreach ($logs as $l): ?>
                                    <li class="adm-activity-item">
                                        <div class="adm-activity-dot"></div>
                                        <div class="adm-activity-title"><?= e($l['action']) ?></div>
                                        <div class="adm-activity-desc"><?= e($l['details']) ?></div>
                                        <div class="adm-activity-time"><?= format_admin_date($l['created_at']) ?> (IP: <?= e($l['ip_address'] ?: '127.0.0.1') ?>)</div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

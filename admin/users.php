<?php
/**
 * EstateVista India - Admin Users Management (Module 4)
 * Manage registered clients, buyers, and investors.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';

require_admin_login();
require_admin_role(['super_admin', 'admin']);

$db = Database::getConnection();
$success_msg = '';
$error_msg = '';

// Handle Actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_admin_csrf()) {
        $error_msg = "Security token expired. Please reload and try again.";
    } else {
        $action = sanitize($_POST['action'] ?? '');

        // 1. Add New User
        if ($action === 'create_user') {
            $name = sanitize($_POST['full_name'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $city = sanitize($_POST['city'] ?? '');
            $budget = sanitize($_POST['preferred_budget'] ?? '');
            $password = $_POST['password'] ?? '';
            $status = in_array($_POST['status'] ?? '', ['active', 'inactive', 'suspended']) ? $_POST['status'] : 'active';

            if (empty($name) || empty($email) || empty($password)) {
                $error_msg = "Full name, email address, and temporary password are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_msg = "Invalid email format.";
            } else {
                try {
                    $chk = $db->prepare("SELECT id FROM users WHERE email = :e LIMIT 1");
                    $chk->execute([':e' => $email]);
                    if ($chk->fetch()) {
                        $error_msg = "A user with this email address already exists.";
                    } else {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $db->prepare("
                            INSERT INTO users (full_name, email, phone, city, preferred_budget, password_hash, status, created_at)
                            VALUES (:name, :email, :phone, :city, :budget, :pwd, :status, NOW())
                        ");
                        $stmt->execute([
                            ':name' => $name,
                            ':email' => $email,
                            ':phone' => $phone,
                            ':city' => $city,
                            ':budget' => $budget,
                            ':pwd' => $hash,
                            ':status' => $status
                        ]);
                        $new_id = $db->lastInsertId();
                        log_admin_activity('Created Client Account', "Created client {$name} ({$email}) with ID #{$new_id}");
                        $success_msg = "Client account created successfully for {$name}.";
                    }
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 2. Edit User
        elseif ($action === 'edit_user') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            $name = sanitize($_POST['full_name'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $city = sanitize($_POST['city'] ?? '');
            $budget = sanitize($_POST['preferred_budget'] ?? '');
            $status = in_array($_POST['status'] ?? '', ['active', 'inactive', 'suspended']) ? $_POST['status'] : 'active';
            $new_password = $_POST['new_password'] ?? '';

            if ($user_id <= 0 || empty($name) || empty($email)) {
                $error_msg = "Client ID, name, and email are required.";
            } else {
                try {
                    // Check duplicate email
                    $chk = $db->prepare("SELECT id FROM users WHERE email = :e AND id != :id LIMIT 1");
                    $chk->execute([':e' => $email, ':id' => $user_id]);
                    if ($chk->fetch()) {
                        $error_msg = "Another client already uses this email address.";
                    } else {
                        if (!empty($new_password)) {
                            $hash = password_hash($new_password, PASSWORD_BCRYPT);
                            $stmt = $db->prepare("
                                UPDATE users 
                                SET full_name = :name, email = :email, phone = :phone, city = :city, 
                                    preferred_budget = :budget, status = :status, password_hash = :pwd, updated_at = NOW()
                                WHERE id = :id
                            ");
                            $stmt->execute([
                                ':name' => $name,
                                ':email' => $email,
                                ':phone' => $phone,
                                ':city' => $city,
                                ':budget' => $budget,
                                ':status' => $status,
                                ':pwd' => $hash,
                                ':id' => $user_id
                            ]);
                        } else {
                            $stmt = $db->prepare("
                                UPDATE users 
                                SET full_name = :name, email = :email, phone = :phone, city = :city, 
                                    preferred_budget = :budget, status = :status, updated_at = NOW()
                                WHERE id = :id
                            ");
                            $stmt->execute([
                                ':name' => $name,
                                ':email' => $email,
                                ':phone' => $phone,
                                ':city' => $city,
                                ':budget' => $budget,
                                ':status' => $status,
                                ':id' => $user_id
                            ]);
                        }
                        log_admin_activity('Updated Client Profile', "Updated client #{$user_id} ({$name})");
                        $success_msg = "Client details updated successfully.";
                    }
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 3. Toggle/Change Status
        elseif ($action === 'change_status') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            $new_status = in_array($_POST['new_status'] ?? '', ['active', 'inactive', 'suspended']) ? $_POST['new_status'] : 'active';
            if ($user_id > 0) {
                try {
                    $stmt = $db->prepare("UPDATE users SET status = :s, updated_at = NOW() WHERE id = :id");
                    $stmt->execute([':s' => $new_status, ':id' => $user_id]);
                    log_admin_activity('Changed Client Status', "Changed client #{$user_id} status to {$new_status}");
                    $success_msg = "Client status updated to " . ucfirst($new_status) . ".";
                } catch (PDOException $e) {
                    $error_msg = "Error updating status: " . $e->getMessage();
                }
            }
        }

        // 4. Delete User
        elseif ($action === 'delete_user') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id > 0) {
                try {
                    // Check if user has properties or inquiries
                    $propCount = $db->prepare("SELECT COUNT(*) FROM properties WHERE user_id = :uid");
                    $propCount->execute([':uid' => $user_id]);
                    if ($propCount->fetchColumn() > 0) {
                        $error_msg = "Cannot delete this client because they have submitted property listings. Set their status to 'Suspended' instead.";
                    } else {
                        // Nullify foreign keys in inquiries if needed
                        $db->prepare("UPDATE inquiries SET user_id = NULL WHERE user_id = :uid")->execute([':uid' => $user_id]);
                        // Delete user
                        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
                        $stmt->execute([':id' => $user_id]);
                        log_admin_activity('Deleted Client Account', "Deleted client account #{$user_id}");
                        $success_msg = "Client account deleted successfully.";
                    }
                } catch (PDOException $e) {
                    $error_msg = "Error deleting user: " . $e->getMessage();
                }
            }
        }
    }
}

// Filters & Query
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$sql = "
    SELECT u.*,
           (SELECT COUNT(*) FROM properties p WHERE p.user_id = u.id) as properties_count,
           (SELECT COUNT(*) FROM inquiries i WHERE i.user_id = u.id) as inquiries_count
    FROM users u
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE :s1 OR u.email LIKE :s2 OR u.phone LIKE :s3 OR u.city LIKE :s4)";
    $term = "%{$search}%";
    $params[':s1'] = $term;
    $params[':s2'] = $term;
    $params[':s3'] = $term;
    $params[':s4'] = $term;
}

if (!empty($status_filter) && in_array($status_filter, ['active', 'inactive', 'suspended'])) {
    $sql .= " AND u.status = :status";
    $params[':status'] = $status_filter;
}

$sql .= " ORDER BY u.created_at DESC";
$usersStmt = $db->prepare($sql);
$usersStmt->execute($params);
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_clients = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$active_clients = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$suspended_clients = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn();
$inquiry_active_clients = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM inquiries WHERE user_id IS NOT NULL")->fetchColumn();

$page_title = "Registered Clients & Investors";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                    Client Accounts & Investors
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                    Manage registered High-Net-Worth buyers, investors, and platform account holders.
                </p>
            </div>
            <button class="adm-btn adm-btn-gold" data-adm-modal-target="modalAddUser">
                <i class="fa-solid fa-user-plus"></i> Add New Client
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
                    <div class="adm-kpi-val"><?= number_format($total_clients) ?></div>
                    <div class="adm-kpi-label">Total Registered Clients</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-blue">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val"><?= number_format($active_clients) ?></div>
                    <div class="adm-kpi-label">Active Portfolios</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-green">
                    <i class="fa-solid fa-user-check"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val"><?= number_format($suspended_clients) ?></div>
                    <div class="adm-kpi-label">Suspended / Inactive</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-gold">
                    <i class="fa-solid fa-user-slash"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val"><?= number_format($inquiry_active_clients) ?></div>
                    <div class="adm-kpi-label">Clients with Inquiries</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-purple">
                    <i class="fa-solid fa-handshake"></i>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="adm-card">
            <div class="adm-card-body" style="padding: 1.25rem;">
                <form method="GET" action="users.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                    <div class="adm-search-input-wrap" style="flex: 1; min-width: 250px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" class="adm-input" placeholder="Search by name, email, phone, city..." value="<?= e($search) ?>">
                    </div>
                    <div style="width: 180px;">
                        <select name="status" class="adm-select" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="active" <?= ($status_filter === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($status_filter === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            <option value="suspended" <?= ($status_filter === 'suspended') ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </div>
                    <button type="submit" class="adm-btn adm-btn-navy">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <?php if (!empty($search) || !empty($status_filter)): ?>
                        <a href="users.php" class="adm-btn adm-btn-outline">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title">
                    <i class="fa-solid fa-address-book" style="color: var(--adm-gold);"></i>
                    Clients Directory (<?= count($users) ?>)
                </div>
                <a href="reports.php?type=users" class="adm-btn adm-btn-sm adm-btn-outline">
                    <i class="fa-solid fa-download"></i> Export Clients CSV
                </a>
            </div>
            <div class="adm-table-responsive">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Client Info</th>
                            <th>Contact Details</th>
                            <th>Location & Budget</th>
                            <th>Activity</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem; color: var(--adm-text-muted);">
                                    <i class="fa-solid fa-user-xmark" style="font-size: 2.5rem; color: #CBD5E1; margin-bottom: 0.75rem; display: block;"></i>
                                    No registered clients found matching your query.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: #EFF6FF; color: #1E40AF; display: flex; align-items: center; justify-content: center; font-weight: 700; border: 1.5px solid #DBEAFE;">
                                                <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 700; color: var(--adm-navy);"><?= e($u['full_name']) ?></div>
                                                <div style="font-size: 0.75rem; color: var(--adm-text-muted);">UID: #<?= $u['id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;"><?= e($u['email']) ?></div>
                                        <div style="font-size: 0.78rem; color: var(--adm-text-muted);"><?= e($u['phone'] ?: 'No Phone') ?></div>
                                    </td>
                                    <td>
                                        <div><i class="fa-solid fa-location-dot" style="color: var(--adm-gold); font-size: 0.75rem;"></i> <?= e($u['city'] ?: 'Not Specified') ?></div>
                                        <div style="font-size: 0.78rem; color: var(--adm-text-muted);"><?= e($u['preferred_budget'] ?: 'Flexible') ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.8rem;">
                                            <span style="font-weight: 600; color: var(--adm-navy);"><?= $u['properties_count'] ?></span> Listings | 
                                            <span style="font-weight: 600; color: var(--adm-navy);"><?= $u['inquiries_count'] ?></span> Inquiries
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($u['status'] === 'active'): ?>
                                            <span class="adm-status-badge adm-badge-active">Active</span>
                                        <?php elseif ($u['status'] === 'inactive'): ?>
                                            <span class="adm-status-badge" style="background: #F1F5F9; color: #64748B;">Inactive</span>
                                        <?php else: ?>
                                            <span class="adm-status-badge adm-badge-rejected">Suspended</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.8rem; color: var(--adm-text-muted);">
                                        <?= format_admin_date($u['created_at'], false) ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; gap: 0.35rem;">
                                            <!-- Edit Button -->
                                            <button type="button" class="adm-btn adm-btn-sm adm-btn-outline btn-edit-user"
                                                data-id="<?= $u['id'] ?>"
                                                data-name="<?= e($u['full_name']) ?>"
                                                data-email="<?= e($u['email']) ?>"
                                                data-phone="<?= e($u['phone']) ?>"
                                                data-city="<?= e($u['city']) ?>"
                                                data-budget="<?= e($u['preferred_budget']) ?>"
                                                data-status="<?= e($u['status']) ?>"
                                                title="Edit Client Profile">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <!-- Status Toggle Form -->
                                            <form method="POST" action="users.php" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                                <input type="hidden" name="action" value="change_status">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <?php if ($u['status'] === 'active'): ?>
                                                    <input type="hidden" name="new_status" value="suspended">
                                                    <button type="submit" class="adm-btn adm-btn-sm adm-btn-outline" style="color: #EF4444;" title="Suspend Client">
                                                        <i class="fa-solid fa-ban"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <input type="hidden" name="new_status" value="active">
                                                    <button type="submit" class="adm-btn adm-btn-sm adm-btn-outline" style="color: #10B981;" title="Activate Client">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </form>

                                            <!-- Delete Button -->
                                            <form method="POST" action="users.php" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger adm-confirm-delete" data-item-name="Client '<?= e($u['full_name']) ?>'" title="Delete Client">
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

<!-- Modal: Add New Client -->
<div class="adm-modal-backdrop" id="modalAddUser">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-user-plus" style="color: var(--adm-gold);"></i> Register New Client</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="users.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="create_user">
            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Full Name *</label>
                    <input type="text" name="full_name" class="adm-input" required placeholder="e.g. Vikramaditya Singhania">
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Email Address *</label>
                        <input type="email" name="email" class="adm-input" required placeholder="client@domain.com">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Phone Number</label>
                        <input type="text" name="phone" class="adm-input" placeholder="+91 98200 12345">
                    </div>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">City of Residence</label>
                        <input type="text" name="city" class="adm-input" placeholder="e.g. Mumbai, Delhi, London">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Investment Budget</label>
                        <input type="text" name="preferred_budget" class="adm-input" placeholder="e.g. ₹10 Cr - ₹25 Cr">
                    </div>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Temporary Password *</label>
                        <input type="password" name="password" class="adm-input" required placeholder="Minimum 6 characters">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Initial Status</label>
                        <select name="status" class="adm-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-gold">Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Client -->
<div class="adm-modal-backdrop" id="modalEditUser">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-user-pen" style="color: var(--adm-gold);"></i> Edit Client Dossier</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="users.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Full Name *</label>
                    <input type="text" name="full_name" id="edit_full_name" class="adm-input" required>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Email Address *</label>
                        <input type="email" name="email" id="edit_email" class="adm-input" required>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Phone Number</label>
                        <input type="text" name="phone" id="edit_phone" class="adm-input">
                    </div>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">City of Residence</label>
                        <input type="text" name="city" id="edit_city" class="adm-input">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Investment Budget</label>
                        <input type="text" name="preferred_budget" id="edit_budget" class="adm-input">
                    </div>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Reset Password (Optional)</label>
                        <input type="password" name="new_password" class="adm-input" placeholder="Leave empty to keep current">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Account Status</label>
                        <select name="status" id="edit_status" class="adm-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
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
document.querySelectorAll('.btn-edit-user').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_user_id').value = this.dataset.id;
        document.getElementById('edit_full_name').value = this.dataset.name;
        document.getElementById('edit_email').value = this.dataset.email;
        document.getElementById('edit_phone').value = this.dataset.phone;
        document.getElementById('edit_city').value = this.dataset.city;
        document.getElementById('edit_budget').value = this.dataset.budget;
        document.getElementById('edit_status').value = this.dataset.status;
        document.getElementById('modalEditUser').classList.add('show');
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

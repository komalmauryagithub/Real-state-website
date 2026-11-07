<?php
/**
 * EstateVista India - Admin Advisory Agents Management (Module 5)
 * Manage private wealth real estate advisors, partners, and sales specialists.
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

        // 1. Add New Agent
        if ($action === 'create_agent') {
            $name = sanitize($_POST['name'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $avatar = sanitize($_POST['avatar'] ?? '');
            $password = $_POST['password'] ?? '';
            $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

            if (empty($name) || empty($email) || empty($password)) {
                $error_msg = "Advisor name, official email, and temporary password are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_msg = "Invalid email format.";
            } else {
                try {
                    $chk = $db->prepare("SELECT id FROM admins WHERE email = :e LIMIT 1");
                    $chk->execute([':e' => $email]);
                    if ($chk->fetch()) {
                        $error_msg = "An administrator or advisor with this email already exists.";
                    } else {
                        if (empty($avatar)) {
                            $avatar = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80';
                        }
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $db->prepare("
                            INSERT INTO admins (name, email, phone, role, avatar, status, password_hash, created_at)
                            VALUES (:name, :email, :phone, 'agent', :avatar, :status, :pwd, NOW())
                        ");
                        $stmt->execute([
                            ':name' => $name,
                            ':email' => $email,
                            ':phone' => $phone,
                            ':avatar' => $avatar,
                            ':status' => $status,
                            ':pwd' => $hash
                        ]);
                        $new_id = $db->lastInsertId();
                        log_admin_activity('Created Advisory Agent', "Enrolled advisor {$name} ({$email}) with ID #{$new_id}");
                        $success_msg = "Advisory Agent {$name} has been enrolled successfully.";
                    }
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 2. Edit Agent
        elseif ($action === 'edit_agent') {
            $agent_id = (int)($_POST['agent_id'] ?? 0);
            $name = sanitize($_POST['name'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $avatar = sanitize($_POST['avatar'] ?? '');
            $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
            $new_password = $_POST['new_password'] ?? '';

            if ($agent_id <= 0 || empty($name) || empty($email)) {
                $error_msg = "Agent ID, name, and email are required.";
            } else {
                try {
                    $chk = $db->prepare("SELECT id FROM admins WHERE email = :e AND id != :id LIMIT 1");
                    $chk->execute([':e' => $email, ':id' => $agent_id]);
                    if ($chk->fetch()) {
                        $error_msg = "Another staff member is already using this email.";
                    } else {
                        if (!empty($new_password)) {
                            $hash = password_hash($new_password, PASSWORD_BCRYPT);
                            $stmt = $db->prepare("
                                UPDATE admins
                                SET name = :name, email = :email, phone = :phone, avatar = :avatar, status = :status, password_hash = :pwd
                                WHERE id = :id AND role = 'agent'
                            ");
                            $stmt->execute([
                                ':name' => $name,
                                ':email' => $email,
                                ':phone' => $phone,
                                ':avatar' => $avatar,
                                ':status' => $status,
                                ':pwd' => $hash,
                                ':id' => $agent_id
                            ]);
                        } else {
                            $stmt = $db->prepare("
                                UPDATE admins
                                SET name = :name, email = :email, phone = :phone, avatar = :avatar, status = :status
                                WHERE id = :id AND role = 'agent'
                            ");
                            $stmt->execute([
                                ':name' => $name,
                                ':email' => $email,
                                ':phone' => $phone,
                                ':avatar' => $avatar,
                                ':status' => $status,
                                ':id' => $agent_id
                            ]);
                        }
                        log_admin_activity('Updated Advisory Agent', "Updated details for advisor #{$agent_id} ({$name})");
                        $success_msg = "Advisor details updated successfully.";
                    }
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 3. Toggle Status
        elseif ($action === 'change_status') {
            $agent_id = (int)($_POST['agent_id'] ?? 0);
            $new_status = in_array($_POST['new_status'] ?? '', ['active', 'inactive']) ? $_POST['new_status'] : 'active';
            if ($agent_id > 0) {
                try {
                    $stmt = $db->prepare("UPDATE admins SET status = :s WHERE id = :id AND role = 'agent'");
                    $stmt->execute([':s' => $new_status, ':id' => $agent_id]);
                    log_admin_activity('Changed Agent Status', "Changed advisor #{$agent_id} status to {$new_status}");
                    $success_msg = "Advisor status updated to " . ucfirst($new_status) . ".";
                } catch (PDOException $e) {
                    $error_msg = "Error updating status: " . $e->getMessage();
                }
            }
        }

        // 4. Delete Agent (Super Admin only)
        elseif ($action === 'delete_agent') {
            if (!admin_has_role('super_admin')) {
                $error_msg = "Only Super Administrators can remove Advisory Agents.";
            } else {
                $agent_id = (int)($_POST['agent_id'] ?? 0);
                if ($agent_id > 0) {
                    try {
                        // Reassign any inquiries currently assigned to this agent to NULL
                        $db->prepare("UPDATE inquiries SET assigned_agent_id = NULL WHERE assigned_agent_id = :id")->execute([':id' => $agent_id]);
                        // Delete
                        $stmt = $db->prepare("DELETE FROM admins WHERE id = :id AND role = 'agent'");
                        $stmt->execute([':id' => $agent_id]);
                        log_admin_activity('Deleted Advisory Agent', "Deleted advisor #{$agent_id}");
                        $success_msg = "Advisory Agent deleted successfully and active leads were unassigned.";
                    } catch (PDOException $e) {
                        $error_msg = "Error deleting agent: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Fetch Agents with Assigned Leads Counts
$agentsStmt = $db->query("
    SELECT a.*,
           (SELECT COUNT(*) FROM inquiries i WHERE i.assigned_agent_id = a.id) as total_leads,
           (SELECT COUNT(*) FROM inquiries i WHERE i.assigned_agent_id = a.id AND COALESCE(i.lead_status, i.status) IN ('new', 'contacted', 'scheduled', 'qualified', 'negotiation')) as active_leads,
           (SELECT COUNT(*) FROM inquiries i WHERE i.assigned_agent_id = a.id AND COALESCE(i.lead_status, i.status) = 'closed') as closed_deals,
           (SELECT COUNT(*) FROM inquiries i WHERE i.assigned_agent_id = a.id AND i.inquiry_type = 'site_visit') as site_visits_count
    FROM admins a
    WHERE a.role = 'agent'
    ORDER BY a.created_at DESC
");
$agents = $agentsStmt->fetchAll(PDO::FETCH_ASSOC);

// KPIs
$total_agents = count($agents);
$active_agents = count(array_filter($agents, fn($ag) => $ag['status'] === 'active'));
$total_assigned_leads = array_sum(array_column($agents, 'total_leads'));
$total_closed_deals = array_sum(array_column($agents, 'closed_deals'));

$page_title = "Advisory Agents & Wealth Managers";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                    Private Wealth Advisory Agents
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                    Elite real estate advisors handling high-net-worth acquisitions, property visits, and client consultations.
                </p>
            </div>
            <button class="adm-btn adm-btn-gold" data-adm-modal-target="modalAddAgent">
                <i class="fa-solid fa-user-plus"></i> Enroll Advisory Agent
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
                    <div class="adm-kpi-val"><?= $total_agents ?></div>
                    <div class="adm-kpi-label">Enrolled Advisors</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-blue">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val"><?= $active_agents ?></div>
                    <div class="adm-kpi-label">Active on Field</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-green">
                    <i class="fa-solid fa-briefcase"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val"><?= $total_assigned_leads ?></div>
                    <div class="adm-kpi-label">Assigned Portfolio Leads</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-gold">
                    <i class="fa-solid fa-comments-dollar"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val"><?= $total_closed_deals ?></div>
                    <div class="adm-kpi-label">Closed Transactions</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-purple">
                    <i class="fa-solid fa-trophy"></i>
                </div>
            </div>
        </div>

        <!-- Agents Table -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title">
                    <i class="fa-solid fa-id-badge" style="color: var(--adm-gold);"></i>
                    Advisory Team Directory (<?= count($agents) ?>)
                </div>
                <div class="adm-search-input-wrap" style="min-width: 260px;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" class="adm-input" data-adm-table-search="tableAgents" placeholder="Quick filter advisors...">
                </div>
            </div>
            <div class="adm-table-responsive">
                <table class="adm-table" id="tableAgents">
                    <thead>
                        <tr>
                            <th>Advisor</th>
                            <th>Contact Channels</th>
                            <th>Role / Title</th>
                            <th>Assigned Pipeline</th>
                            <th>Status</th>
                            <th>Enrolled Date</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($agents)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem; color: var(--adm-text-muted);">
                                    <i class="fa-solid fa-user-tie" style="font-size: 2.5rem; color: #CBD5E1; margin-bottom: 0.75rem; display: block;"></i>
                                    No advisory agents enrolled yet. Click "Enroll Advisory Agent" to add team members.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($agents as $ag): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.85rem;">
                                            <img src="<?= e(safe_image_url($ag['avatar'], 'avatar')) ?>"
                                                 alt="<?= e($ag['name']) ?>"
                                                 style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid var(--adm-gold);"
                                                 onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/avatar-placeholder.svg';">
                                            <div>
                                                <div style="font-weight: 700; color: var(--adm-navy);"><?= e($ag['name']) ?></div>
                                                <div style="font-size: 0.75rem; color: var(--adm-text-muted);">Agent ID: #<?= $ag['id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;"><?= e($ag['email']) ?></div>
                                        <div style="font-size: 0.78rem; color: var(--adm-text-muted);"><i class="fa-solid fa-phone" style="font-size: 0.72rem; color: var(--adm-gold);"></i> <?= e($ag['phone'] ?: '—') ?></div>
                                    </td>
                                    <td>
                                        <span class="adm-status-badge adm-badge-new" style="background: #FEF3C7; color: #92400E;">
                                            <i class="fa-solid fa-shield-halved" style="font-size: 0.7rem;"></i> Luxury Advisor
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.82rem;">
                                            <strong style="color: var(--adm-navy);"><?= $ag['active_leads'] ?></strong> Active Leads / 
                                            <strong style="color: #059669;"><?= $ag['closed_deals'] ?></strong> Closed
                                        </div>
                                        <div style="font-size: 0.72rem; color: var(--adm-text-muted); margin-top: 2px;">
                                            <i class="fa-solid fa-calendar-check" style="color: var(--adm-gold);"></i> <?= $ag['site_visits_count'] ?> Site Visits Led
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($ag['status'] === 'active'): ?>
                                            <span class="adm-status-badge adm-badge-active">Active</span>
                                        <?php else: ?>
                                            <span class="adm-status-badge" style="background: #F1F5F9; color: #64748B;">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.8rem; color: var(--adm-text-muted);">
                                        <?= format_admin_date($ag['created_at'], false) ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; gap: 0.35rem;">
                                            <!-- View Leads in CRM -->
                                            <a href="leads.php?agent_id=<?= $ag['id'] ?>" class="adm-btn adm-btn-sm adm-btn-outline" title="View Assigned Leads">
                                                <i class="fa-solid fa-folder-open"></i>
                                            </a>

                                            <!-- Edit Agent -->
                                            <button type="button" class="adm-btn adm-btn-sm adm-btn-outline btn-edit-agent"
                                                data-id="<?= $ag['id'] ?>"
                                                data-name="<?= e($ag['name']) ?>"
                                                data-email="<?= e($ag['email']) ?>"
                                                data-phone="<?= e($ag['phone']) ?>"
                                                data-avatar="<?= e($ag['avatar']) ?>"
                                                data-status="<?= e($ag['status']) ?>"
                                                title="Edit Advisor Details">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <!-- Toggle Status -->
                                            <form method="POST" action="agents.php" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                                <input type="hidden" name="action" value="change_status">
                                                <input type="hidden" name="agent_id" value="<?= $ag['id'] ?>">
                                                <?php if ($ag['status'] === 'active'): ?>
                                                    <input type="hidden" name="new_status" value="inactive">
                                                    <button type="submit" class="adm-btn adm-btn-sm adm-btn-outline" style="color: #EF4444;" title="Deactivate Advisor">
                                                        <i class="fa-solid fa-user-xmark"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <input type="hidden" name="new_status" value="active">
                                                    <button type="submit" class="adm-btn adm-btn-sm adm-btn-outline" style="color: #10B981;" title="Activate Advisor">
                                                        <i class="fa-solid fa-user-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </form>

                                            <!-- Delete (Super Admin only) -->
                                            <?php if (admin_has_role('super_admin')): ?>
                                                <form method="POST" action="agents.php" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                                    <input type="hidden" name="action" value="delete_agent">
                                                    <input type="hidden" name="agent_id" value="<?= $ag['id'] ?>">
                                                    <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger adm-confirm-delete" data-item-name="Advisor '<?= e($ag['name']) ?>'" title="Delete Advisor">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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

<!-- Modal: Add New Agent -->
<div class="adm-modal-backdrop" id="modalAddAgent">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-user-tie" style="color: var(--adm-gold);"></i> Enroll Advisory Agent</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="agents.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="create_agent">
            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Full Name *</label>
                    <input type="text" name="name" class="adm-input" required placeholder="e.g. Vikramaditya Birla">
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Corporate Email *</label>
                        <input type="email" name="email" class="adm-input" required placeholder="advisors@estatevistaindia.com">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Direct Phone *</label>
                        <input type="text" name="phone" class="adm-input" required placeholder="+91 22 6985 4010">
                    </div>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Profile Avatar URL</label>
                    <input type="url" name="avatar" class="adm-input" placeholder="https://images.unsplash.com/...">
                    <small style="color: var(--adm-text-muted); font-size: 0.75rem;">Leave empty to use high-resolution default executive avatar.</small>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Temporary Password *</label>
                        <input type="password" name="password" class="adm-input" required placeholder="Minimum 6 characters">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Initial Status</label>
                        <select name="status" class="adm-select">
                            <option value="active">Active (Eligible for Leads)</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-gold">Enroll Advisor</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Agent -->
<div class="adm-modal-backdrop" id="modalEditAgent">
    <div class="adm-modal">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-user-pen" style="color: var(--adm-gold);"></i> Edit Advisor Dossier</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="agents.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="edit_agent">
            <input type="hidden" name="agent_id" id="edit_agent_id">
            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Full Name *</label>
                    <input type="text" name="name" id="edit_agent_name" class="adm-input" required>
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Corporate Email *</label>
                        <input type="email" name="email" id="edit_agent_email" class="adm-input" required>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Direct Phone</label>
                        <input type="text" name="phone" id="edit_agent_phone" class="adm-input">
                    </div>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Avatar URL</label>
                    <input type="url" name="avatar" id="edit_agent_avatar" class="adm-input">
                </div>
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Reset Password (Optional)</label>
                        <input type="password" name="new_password" class="adm-input" placeholder="Leave empty to keep current">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Status</label>
                        <select name="status" id="edit_agent_status" class="adm-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-navy">Save Advisor Details</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.btn-edit-agent').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_agent_id').value = this.dataset.id;
        document.getElementById('edit_agent_name').value = this.dataset.name;
        document.getElementById('edit_agent_email').value = this.dataset.email;
        document.getElementById('edit_agent_phone').value = this.dataset.phone;
        document.getElementById('edit_agent_avatar').value = this.dataset.avatar;
        document.getElementById('edit_agent_status').value = this.dataset.status;
        document.getElementById('modalEditAgent').classList.add('show');
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

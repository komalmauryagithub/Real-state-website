<?php
/**
 * EstateVista India - Enquiries & Consultations Management (Module 7)
 * Handle buyer property inquiries, callback requests, and advisory assignments.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';

require_admin_login();

$db = Database::getConnection();
$success_msg = '';
$error_msg = '';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = $db->query("
        SELECT 
            i.id,
            i.inquiry_type,
            i.full_name,
            i.email,
            i.phone,
            i.city,
            i.budget_range,
            COALESCE(p.title, 'General Consultation') as property_title,
            COALESCE(p.locality, '—') as property_locality,
            COALESCE(i.lead_status, i.status) as current_status,
            COALESCE(adm.name, 'Unassigned') as assigned_advisor,
            i.message,
            i.admin_notes,
            i.created_at
        FROM inquiries i
        LEFT JOIN properties p ON i.property_id = p.id
        LEFT JOIN admins adm ON i.assigned_agent_id = adm.id
        WHERE i.inquiry_type IN ('enquiry', 'callback')
        ORDER BY i.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $headers = [
        'Inquiry ID', 'Type', 'Client Name', 'Email', 'Phone', 'City', 'Budget Range',
        'Property', 'Locality', 'Lead Status', 'Assigned Advisor', 'Client Message', 'Admin Notes', 'Date Received'
    ];

    export_to_csv('EstateVista_Enquiries_' . date('Y-m-d') . '.csv', $headers, $rows);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_admin_csrf()) {
        $error_msg = "Security token expired. Please reload and try again.";
    } else {
        $action = sanitize($_POST['action'] ?? '');

        // 1. Update Enquiry Status, Agent, and Notes
        if ($action === 'update_enquiry') {
            $inquiry_id = (int)($_POST['inquiry_id'] ?? 0);
            $lead_status = sanitize($_POST['lead_status'] ?? 'new');
            $assigned_agent_id = !empty($_POST['assigned_agent_id']) ? (int)$_POST['assigned_agent_id'] : null;
            $admin_notes = sanitize($_POST['admin_notes'] ?? '');

            $valid_statuses = ['new', 'contacted', 'scheduled', 'qualified', 'negotiation', 'closed', 'lost'];
            if (!in_array($lead_status, $valid_statuses)) {
                $lead_status = 'new';
            }

            if ($inquiry_id > 0) {
                try {
                    // Sync status to legacy enum column if valid
                    $legacy_status = in_array($lead_status, ['new', 'contacted', 'scheduled', 'closed']) ? $lead_status : 'contacted';

                    $stmt = $db->prepare("
                        UPDATE inquiries
                        SET lead_status = :ls, status = :s, assigned_agent_id = :aid, admin_notes = :notes
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':ls' => $lead_status,
                        ':s' => $legacy_status,
                        ':aid' => $assigned_agent_id,
                        ':notes' => $admin_notes,
                        ':id' => $inquiry_id
                    ]);

                    log_admin_activity('Updated Enquiry', "Updated enquiry #{$inquiry_id} (Status: {$lead_status}, Agent ID: " . ($assigned_agent_id ?: 'Unassigned') . ")");
                    $success_msg = "Enquiry #{$inquiry_id} updated successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 2. Delete Enquiry
        elseif ($action === 'delete_enquiry') {
            require_admin_role(['super_admin', 'admin']);
            $inquiry_id = (int)($_POST['inquiry_id'] ?? 0);
            if ($inquiry_id > 0) {
                try {
                    $stmt = $db->prepare("DELETE FROM inquiries WHERE id = :id");
                    $stmt->execute([':id' => $inquiry_id]);
                    log_admin_activity('Deleted Enquiry', "Deleted enquiry #{$inquiry_id}");
                    $success_msg = "Enquiry #{$inquiry_id} deleted successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Error deleting enquiry: " . $e->getMessage();
                }
            }
        }
    }
}

// Filters & Query
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$type_filter = trim($_GET['type'] ?? '');
$agent_filter = trim($_GET['agent_id'] ?? '');

$sql = "
    SELECT i.*, 
           p.title as property_title, p.slug as property_slug, p.locality as property_locality, p.price_display,
           adm.name as agent_name, adm.avatar as agent_avatar
    FROM inquiries i
    LEFT JOIN properties p ON i.property_id = p.id
    LEFT JOIN admins adm ON i.assigned_agent_id = adm.id
    WHERE i.inquiry_type IN ('enquiry', 'callback')
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (i.full_name LIKE :s1 OR i.email LIKE :s2 OR i.phone LIKE :s3 OR p.title LIKE :s4 OR i.city LIKE :s5)";
    $term = "%{$search}%";
    $params[':s1'] = $term;
    $params[':s2'] = $term;
    $params[':s3'] = $term;
    $params[':s4'] = $term;
    $params[':s5'] = $term;
}

if (!empty($status_filter)) {
    $sql .= " AND COALESCE(i.lead_status, i.status) = :status";
    $params[':status'] = $status_filter;
}

if (!empty($type_filter) && in_array($type_filter, ['enquiry', 'callback'])) {
    $sql .= " AND i.inquiry_type = :type";
    $params[':type'] = $type_filter;
}

if (!empty($agent_filter)) {
    if ($agent_filter === 'unassigned') {
        $sql .= " AND i.assigned_agent_id IS NULL";
    } else {
        $sql .= " AND i.assigned_agent_id = :agent_id";
        $params[':agent_id'] = (int)$agent_filter;
    }
}

$sql .= " ORDER BY i.created_at DESC";
$inquiriesStmt = $db->prepare($sql);
$inquiriesStmt->execute($params);
$inquiries = $inquiriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Assignable agents list
$assignable_agents = get_all_assignable_agents();

// KPI Stats
$total_enquiries = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE inquiry_type IN ('enquiry', 'callback')")->fetchColumn();
$new_count = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE inquiry_type IN ('enquiry', 'callback') AND COALESCE(lead_status, status) = 'new'")->fetchColumn();
$unassigned_count = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE inquiry_type IN ('enquiry', 'callback') AND assigned_agent_id IS NULL")->fetchColumn();
$qualified_count = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE inquiry_type IN ('enquiry', 'callback') AND COALESCE(lead_status, status) = 'qualified'")->fetchColumn();

$page_title = "Client Enquiries & Requests";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                    Client Enquiries & Inbound Requests
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                    Manage real-time luxury property inquiries, callback requests, and assign leads to certified advisory partners.
                </p>
            </div>
            <a href="enquiries.php?export=csv" class="adm-btn adm-btn-gold">
                <i class="fa-solid fa-file-csv"></i> Export Enquiries CSV
            </a>
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
                    <div class="adm-kpi-val"><?= $total_enquiries ?></div>
                    <div class="adm-kpi-label">Total Inquiries Received</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-blue">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: #D97706;"><?= $new_count ?></div>
                    <div class="adm-kpi-label">Unprocessed / New</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-gold">
                    <i class="fa-solid fa-bell"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val"><?= $unassigned_count ?></div>
                    <div class="adm-kpi-label">Unassigned Leads</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-purple">
                    <i class="fa-solid fa-user-clock"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: #059669;"><?= $qualified_count ?></div>
                    <div class="adm-kpi-label">Qualified High-Value Leads</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-green">
                    <i class="fa-solid fa-award"></i>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="adm-card">
            <div class="adm-card-body" style="padding: 1.25rem;">
                <form method="GET" action="enquiries.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                    <div class="adm-search-input-wrap" style="flex: 1; min-width: 250px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" class="adm-input" placeholder="Search by client, property, email, phone..." value="<?= e($search) ?>">
                    </div>
                    <div style="width: 160px;">
                        <select name="type" class="adm-select" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="enquiry" <?= ($type_filter === 'enquiry') ? 'selected' : '' ?>>Property Enquiry</option>
                            <option value="callback" <?= ($type_filter === 'callback') ? 'selected' : '' ?>>Callback Request</option>
                        </select>
                    </div>
                    <div style="width: 170px;">
                        <select name="status" class="adm-select" onchange="this.form.submit()">
                            <option value="">All Stages</option>
                            <option value="new" <?= ($status_filter === 'new') ? 'selected' : '' ?>>New</option>
                            <option value="contacted" <?= ($status_filter === 'contacted') ? 'selected' : '' ?>>Contacted</option>
                            <option value="scheduled" <?= ($status_filter === 'scheduled') ? 'selected' : '' ?>>Scheduled</option>
                            <option value="qualified" <?= ($status_filter === 'qualified') ? 'selected' : '' ?>>Qualified</option>
                            <option value="negotiation" <?= ($status_filter === 'negotiation') ? 'selected' : '' ?>>Negotiation</option>
                            <option value="closed" <?= ($status_filter === 'closed') ? 'selected' : '' ?>>Closed</option>
                            <option value="lost" <?= ($status_filter === 'lost') ? 'selected' : '' ?>>Lost</option>
                        </select>
                    </div>
                    <div style="width: 180px;">
                        <select name="agent_id" class="adm-select" onchange="this.form.submit()">
                            <option value="">All Advisors</option>
                            <option value="unassigned" <?= ($agent_filter === 'unassigned') ? 'selected' : '' ?>>-- Unassigned --</option>
                            <?php foreach ($assignable_agents as $ag): ?>
                                <option value="<?= $ag['id'] ?>" <?= ($agent_filter == $ag['id']) ? 'selected' : '' ?>>
                                    <?= e($ag['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="adm-btn adm-btn-navy">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <?php if (!empty($search) || !empty($status_filter) || !empty($type_filter) || !empty($agent_filter)): ?>
                        <a href="enquiries.php" class="adm-btn adm-btn-outline">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Enquiries Table -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title">
                    <i class="fa-solid fa-list-check" style="color: var(--adm-gold);"></i>
                    Inquiries Roster (<?= count($inquiries) ?>)
                </div>
            </div>
            <div class="adm-table-responsive">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Inquiry #</th>
                            <th>Client Coordinates</th>
                            <th>Type</th>
                            <th>Property / Interest</th>
                            <th>Stage</th>
                            <th>Assigned Advisor</th>
                            <th>Received</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inquiries)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 3rem; color: var(--adm-text-muted);">
                                    <i class="fa-solid fa-inbox" style="font-size: 2.5rem; color: #CBD5E1; margin-bottom: 0.75rem; display: block;"></i>
                                    No client inquiries found matching the specified criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inquiries as $inq): ?>
                                <?php $stage = $inq['lead_status'] ?: $inq['status'] ?: 'new'; ?>
                                <tr>
                                    <td>
                                        <strong style="color: var(--adm-navy);">#<?= $inq['id'] ?></strong>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--adm-navy);"><?= e($inq['full_name']) ?></div>
                                        <div style="font-size: 0.78rem; color: var(--adm-text-muted);">
                                            <?= e($inq['email']) ?> &bull; <?= e($inq['phone']) ?>
                                        </div>
                                        <?php if (!empty($inq['city']) || !empty($inq['budget_range'])): ?>
                                            <div style="font-size: 0.74rem; color: var(--adm-gold); margin-top: 2px;">
                                                <i class="fa-solid fa-location-dot"></i> <?= e($inq['city'] ?: 'India') ?> &bull; Budget: <?= e($inq['budget_range'] ?: 'Flexible') ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($inq['inquiry_type'] === 'callback'): ?>
                                            <span class="adm-status-badge" style="background: #FEF3C7; color: #92400E;">
                                                <i class="fa-solid fa-phone-volume"></i> Callback
                                            </span>
                                            <?php if (!empty($inq['preferred_time'])): ?>
                                                <div style="font-size: 0.72rem; color: var(--adm-text-muted); margin-top: 2px;">
                                                    Slot: <?= e($inq['preferred_time']) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="adm-status-badge" style="background: #EFF6FF; color: #1E40AF;">
                                                <i class="fa-solid fa-envelope"></i> Property
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($inq['property_title'])): ?>
                                            <div style="font-weight: 600; color: var(--adm-navy); max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <a href="<?= BASE_URL ?>property-detail.php?slug=<?= urlencode($inq['property_slug']) ?>" target="_blank" style="text-decoration: none; color: inherit;">
                                                    <?= e($inq['property_title']) ?>
                                                </a>
                                            </div>
                                            <div style="font-size: 0.74rem; color: var(--adm-text-muted);">
                                                <?= e($inq['property_locality']) ?> &bull; <?= e($inq['price_display']) ?>
                                            </div>
                                        <?php else: ?>
                                            <div style="font-weight: 500; color: var(--adm-text-muted);">General Advisory Consultation</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="adm-status-badge adm-badge-<?= $stage ?>">
                                            <?= strtoupper($stage) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($inq['agent_name'])): ?>
                                            <div style="display: flex; align-items: center; gap: 0.4rem;">
                                                <div style="width: 24px; height: 24px; border-radius: 50%; background: #EFF6FF; color: #1E40AF; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700;">
                                                    <?= strtoupper(substr($inq['agent_name'], 0, 1)) ?>
                                                </div>
                                                <span style="font-weight: 600; font-size: 0.82rem;"><?= e($inq['agent_name']) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #94A3B8; font-style: italic; font-size: 0.8rem;">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.78rem; color: var(--adm-text-muted);">
                                        <?= format_admin_date($inq['created_at']) ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <button type="button" class="adm-btn adm-btn-sm adm-btn-gold btn-manage-enquiry"
                                            data-id="<?= $inq['id'] ?>"
                                            data-name="<?= e($inq['full_name']) ?>"
                                            data-email="<?= e($inq['email']) ?>"
                                            data-phone="<?= e($inq['phone']) ?>"
                                            data-type="<?= e($inq['inquiry_type']) ?>"
                                            data-property="<?= e($inq['property_title'] ?: 'General Consultation') ?>"
                                            data-message="<?= e($inq['message']) ?>"
                                            data-stage="<?= e($stage) ?>"
                                            data-agent="<?= e($inq['assigned_agent_id']) ?>"
                                            data-notes="<?= e($inq['admin_notes']) ?>"
                                            title="Manage & Assign">
                                            <i class="fa-solid fa-pen-to-square"></i> Manage
                                        </button>
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

<!-- Modal: Manage Enquiry & Assign Agent -->
<div class="adm-modal-backdrop" id="modalManageEnquiry">
    <div class="adm-modal" style="max-width: 600px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-user-tag" style="color: var(--adm-gold);"></i> Manage Client Lead</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="enquiries.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="update_enquiry">
            <input type="hidden" name="inquiry_id" id="modal_inq_id">
            
            <div class="adm-modal-body">
                <div style="background: #F8FAFC; border: 1px solid var(--adm-border); border-radius: 8px; padding: 1rem; margin-bottom: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <strong id="modal_client_name" style="font-size: 1rem; color: var(--adm-navy);"></strong>
                        <span id="modal_inq_type" class="adm-status-badge adm-badge-new"></span>
                    </div>
                    <div style="font-size: 0.8rem; color: var(--adm-text-muted);">
                        <span id="modal_client_email"></span> &bull; <span id="modal_client_phone"></span>
                    </div>
                    <div style="margin-top: 0.5rem; font-size: 0.84rem; color: var(--adm-navy);">
                        <strong>Subject:</strong> <span id="modal_property_name"></span>
                    </div>
                    <div id="modal_message_box" style="margin-top: 0.5rem; font-size: 0.8rem; color: #475569; background: #FFFFFF; padding: 0.75rem; border-radius: 6px; border: 1px dashed #CBD5E1;"></div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Lead Stage *</label>
                        <select name="lead_status" id="modal_lead_status" class="adm-select" required>
                            <option value="new">New (Fresh Inbound)</option>
                            <option value="contacted">Contacted (Call Placed)</option>
                            <option value="scheduled">Scheduled (Private View)</option>
                            <option value="qualified">Qualified (HNW Confirmed)</option>
                            <option value="negotiation">Negotiation (Term Sheet)</option>
                            <option value="closed">Closed (Deal Booked)</option>
                            <option value="lost">Lost / Unresponsive</option>
                        </select>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-label">Assigned Advisor / Manager</label>
                        <select name="assigned_agent_id" id="modal_assigned_agent" class="adm-select">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($assignable_agents as $ag): ?>
                                <option value="<?= $ag['id'] ?>"><?= e($ag['name']) ?> (<?= ucfirst($ag['role']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Internal Advisory Notes & Activity Log</label>
                    <textarea name="admin_notes" id="modal_admin_notes" class="adm-textarea" rows="3" placeholder="Enter notes from discovery call, special requirements, vaastu preferences, or target price..."></textarea>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-navy">Update Dossier</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.btn-manage-enquiry').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('modal_inq_id').value = this.dataset.id;
        document.getElementById('modal_client_name').textContent = this.dataset.name;
        document.getElementById('modal_client_email').textContent = this.dataset.email;
        document.getElementById('modal_client_phone').textContent = this.dataset.phone;
        document.getElementById('modal_inq_type').textContent = this.dataset.type.toUpperCase();
        document.getElementById('modal_property_name').textContent = this.dataset.property;
        document.getElementById('modal_message_box').textContent = this.dataset.message || 'No additional note supplied by client.';
        document.getElementById('modal_lead_status').value = this.dataset.stage;
        document.getElementById('modal_assigned_agent').value = this.dataset.agent || '';
        document.getElementById('modal_admin_notes').value = this.dataset.notes || '';
        document.getElementById('modalManageEnquiry').classList.add('show');
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

<?php
/**
 * EstateVista India - VIP Site Visit Requests (Module 8)
 * Dedicated calendar and dispatch desk for private property viewings and site visits.
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
            i.visit_date,
            i.visit_time,
            i.full_name as client_name,
            i.email as client_email,
            i.phone as client_phone,
            COALESCE(p.title, 'General Viewing') as property_title,
            COALESCE(p.locality, '—') as locality,
            COALESCE(p.price_display, '—') as property_price,
            COALESCE(i.lead_status, i.status) as visit_status,
            COALESCE(adm.name, 'Unassigned') as escort_advisor,
            i.message as client_notes,
            i.admin_notes,
            i.created_at as booked_on
        FROM inquiries i
        LEFT JOIN properties p ON i.property_id = p.id
        LEFT JOIN admins adm ON i.assigned_agent_id = adm.id
        WHERE i.inquiry_type = 'site_visit'
        ORDER BY i.visit_date ASC, i.visit_time ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $headers = [
        'Visit ID', 'Scheduled Date', 'Time Slot', 'Client Name', 'Email', 'Phone',
        'Property', 'Locality', 'Price', 'Status', 'Escort Advisor', 'Client Notes', 'Internal Notes', 'Booking Date'
    ];

    export_to_csv('EstateVista_Site_Visits_' . date('Y-m-d') . '.csv', $headers, $rows);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_admin_csrf()) {
        $error_msg = "Security token expired. Please reload and try again.";
    } else {
        $action = sanitize($_POST['action'] ?? '');

        if ($action === 'update_site_visit') {
            $inquiry_id = (int)($_POST['inquiry_id'] ?? 0);
            $visit_date = sanitize($_POST['visit_date'] ?? '');
            $visit_time = sanitize($_POST['visit_time'] ?? '');
            $status = sanitize($_POST['status'] ?? 'scheduled');
            $assigned_agent_id = !empty($_POST['assigned_agent_id']) ? (int)$_POST['assigned_agent_id'] : null;
            $admin_notes = sanitize($_POST['admin_notes'] ?? '');

            if ($inquiry_id > 0) {
                try {
                    $stmt = $db->prepare("
                        UPDATE inquiries
                        SET visit_date = :vd, visit_time = :vt, status = :s, lead_status = :ls,
                            assigned_agent_id = :aid, admin_notes = :notes
                        WHERE id = :id AND inquiry_type = 'site_visit'
                    ");
                    $stmt->execute([
                        ':vd' => !empty($visit_date) ? $visit_date : null,
                        ':vt' => $visit_time,
                        ':s' => in_array($status, ['new', 'contacted', 'scheduled', 'closed']) ? $status : 'scheduled',
                        ':ls' => $status,
                        ':aid' => $assigned_agent_id,
                        ':notes' => $admin_notes,
                        ':id' => $inquiry_id
                    ]);

                    log_admin_activity('Updated Site Visit', "Updated site visit #{$inquiry_id} for {$visit_date}");
                    $success_msg = "Site visit #{$inquiry_id} schedule and advisory assignment updated successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}

// Filters & Query
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$date_filter = trim($_GET['date_range'] ?? '');

$sql = "
    SELECT i.*, 
           p.title as property_title, p.slug as property_slug, p.locality as property_locality, p.price_display, p.cover_image,
           adm.name as agent_name, adm.phone as agent_phone, adm.avatar as agent_avatar
    FROM inquiries i
    LEFT JOIN properties p ON i.property_id = p.id
    LEFT JOIN admins adm ON i.assigned_agent_id = adm.id
    WHERE i.inquiry_type = 'site_visit'
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (i.full_name LIKE :s1 OR i.email LIKE :s2 OR i.phone LIKE :s3 OR p.title LIKE :s4)";
    $term = "%{$search}%";
    $params[':s1'] = $term;
    $params[':s2'] = $term;
    $params[':s3'] = $term;
    $params[':s4'] = $term;
}

if (!empty($status_filter)) {
    $sql .= " AND COALESCE(i.lead_status, i.status) = :status";
    $params[':status'] = $status_filter;
}

if ($date_filter === 'today') {
    $sql .= " AND i.visit_date = CURDATE()";
} elseif ($date_filter === 'upcoming') {
    $sql .= " AND i.visit_date >= CURDATE()";
}

$sql .= " ORDER BY COALESCE(i.visit_date, '9999-12-31') ASC, i.created_at DESC";
$visitsStmt = $db->prepare($sql);
$visitsStmt->execute($params);
$visits = $visitsStmt->fetchAll(PDO::FETCH_ASSOC);

// Assignable agents
$assignable_agents = get_all_assignable_agents();

// KPI Stats
$total_visits = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE inquiry_type = 'site_visit'")->fetchColumn();
$pending_visits = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE inquiry_type = 'site_visit' AND COALESCE(lead_status, status) = 'new'")->fetchColumn();
$scheduled_visits = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE inquiry_type = 'site_visit' AND COALESCE(lead_status, status) = 'scheduled'")->fetchColumn();
$today_visits = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE inquiry_type = 'site_visit' AND visit_date = CURDATE()")->fetchColumn();

$page_title = "VIP Site Visits & Property Viewings";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                    VIP Site Visits & Private Viewings
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                    Curated property tour requests, chauffeured appointments, and advisory escort dispatch.
                </p>
            </div>
            <a href="site-visits.php?export=csv" class="adm-btn adm-btn-gold">
                <i class="fa-solid fa-file-csv"></i> Export Visit Schedule CSV
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
                    <div class="adm-kpi-val"><?= $total_visits ?></div>
                    <div class="adm-kpi-label">Total Visit Requests</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-blue">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: #D97706;"><?= $pending_visits ?></div>
                    <div class="adm-kpi-label">Pending Confirmation</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-gold">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: #059669;"><?= $scheduled_visits ?></div>
                    <div class="adm-kpi-label">Confirmed Tours</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-green">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: #2563EB;"><?= $today_visits ?></div>
                    <div class="adm-kpi-label">Today's Scheduled Tours</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-purple">
                    <i class="fa-solid fa-car-side"></i>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="adm-card">
            <div class="adm-card-body" style="padding: 1.25rem;">
                <form method="GET" action="site-visits.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                    <div class="adm-search-input-wrap" style="flex: 1; min-width: 250px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" class="adm-input" placeholder="Search client name, phone, property..." value="<?= e($search) ?>">
                    </div>
                    <div style="width: 170px;">
                        <select name="status" class="adm-select" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="new" <?= ($status_filter === 'new') ? 'selected' : '' ?>>New / Unconfirmed</option>
                            <option value="scheduled" <?= ($status_filter === 'scheduled') ? 'selected' : '' ?>>Confirmed Tour</option>
                            <option value="contacted" <?= ($status_filter === 'contacted') ? 'selected' : '' ?>>Contacted</option>
                            <option value="closed" <?= ($status_filter === 'closed') ? 'selected' : '' ?>>Completed / Closed</option>
                        </select>
                    </div>
                    <div style="width: 170px;">
                        <select name="date_range" class="adm-select" onchange="this.form.submit()">
                            <option value="">All Dates</option>
                            <option value="today" <?= ($date_filter === 'today') ? 'selected' : '' ?>>Today's Visits</option>
                            <option value="upcoming" <?= ($date_filter === 'upcoming') ? 'selected' : '' ?>>Upcoming Visits</option>
                        </select>
                    </div>
                    <button type="submit" class="adm-btn adm-btn-navy">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <?php if (!empty($search) || !empty($status_filter) || !empty($date_filter)): ?>
                        <a href="site-visits.php" class="adm-btn adm-btn-outline">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Site Visits Table -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title">
                    <i class="fa-solid fa-car" style="color: var(--adm-gold);"></i>
                    Site Visits Schedule (<?= count($visits) ?>)
                </div>
            </div>
            <div class="adm-table-responsive">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Tour Date & Slot</th>
                            <th>Client Coordinates</th>
                            <th>Property to Inspect</th>
                            <th>Status</th>
                            <th>Escort Advisor</th>
                            <th>Client Request Notes</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($visits)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem; color: var(--adm-text-muted);">
                                    <i class="fa-solid fa-calendar-xmark" style="font-size: 2.5rem; color: #CBD5E1; margin-bottom: 0.75rem; display: block;"></i>
                                    No site visit bookings match your query.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($visits as $v): ?>
                                <?php $v_status = $v['lead_status'] ?: $v['status'] ?: 'new'; ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 700; color: var(--adm-navy); font-size: 0.95rem;">
                                            <i class="fa-regular fa-calendar" style="color: var(--adm-gold);"></i> 
                                            <?= !empty($v['visit_date']) ? date('d M Y', strtotime($v['visit_date'])) : 'To Be Decided' ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--adm-text-muted); margin-top: 2px;">
                                            <i class="fa-regular fa-clock"></i> <?= e($v['visit_time'] ?: 'Flexible Morning/Afternoon') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--adm-navy);"><?= e($v['full_name']) ?></div>
                                        <div style="font-size: 0.78rem; color: var(--adm-text-muted);"><?= e($v['email']) ?></div>
                                        <div style="font-size: 0.76rem; color: #2563EB; font-weight: 600;"><i class="fa-solid fa-phone"></i> <?= e($v['phone']) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--adm-navy);">
                                            <?= e($v['property_title'] ?: 'Private Portfolio Viewing') ?>
                                        </div>
                                        <div style="font-size: 0.74rem; color: var(--adm-text-muted);">
                                            <?= e($v['property_locality']) ?> &bull; <?= e($v['price_display']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($v_status === 'scheduled'): ?>
                                            <span class="adm-status-badge adm-badge-approved">Confirmed</span>
                                        <?php elseif ($v_status === 'new'): ?>
                                            <span class="adm-status-badge adm-badge-pending">Pending Call</span>
                                        <?php elseif ($v_status === 'closed'): ?>
                                            <span class="adm-status-badge adm-badge-closed">Tour Completed</span>
                                        <?php else: ?>
                                            <span class="adm-status-badge adm-badge-new"><?= strtoupper($v_status) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($v['agent_name'])): ?>
                                            <div style="font-weight: 600; font-size: 0.82rem; color: var(--adm-navy);">
                                                <i class="fa-solid fa-user-tie" style="color: var(--adm-gold);"></i> <?= e($v['agent_name']) ?>
                                            </div>
                                            <div style="font-size: 0.72rem; color: var(--adm-text-muted);"><?= e($v['agent_phone'] ?: '') ?></div>
                                        <?php else: ?>
                                            <span style="color: #94A3B8; font-style: italic; font-size: 0.8rem;">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.78rem; color: #475569; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?= e($v['message'] ?: 'No special instructions') ?>
                                        </div>
                                        <?php if (!empty($v['admin_notes'])): ?>
                                            <div style="font-size: 0.72rem; color: var(--adm-gold); font-weight: 600; margin-top: 2px;">
                                                <i class="fa-solid fa-note-sticky"></i> Note: <?= e(substr($v['admin_notes'], 0, 35)) ?>...
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <button type="button" class="adm-btn adm-btn-sm adm-btn-gold btn-manage-visit"
                                            data-id="<?= $v['id'] ?>"
                                            data-client="<?= e($v['full_name']) ?>"
                                            data-phone="<?= e($v['phone']) ?>"
                                            data-property="<?= e($v['property_title'] ?: 'Portfolio Viewing') ?>"
                                            data-date="<?= e($v['visit_date']) ?>"
                                            data-time="<?= e($v['visit_time']) ?>"
                                            data-status="<?= e($v_status) ?>"
                                            data-agent="<?= e($v['assigned_agent_id']) ?>"
                                            data-notes="<?= e($v['admin_notes']) ?>"
                                            title="Confirm or Reschedule Tour">
                                            <i class="fa-solid fa-clipboard-check"></i> Manage Tour
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

<!-- Modal: Manage Site Visit -->
<div class="adm-modal-backdrop" id="modalManageVisit">
    <div class="adm-modal" style="max-width: 580px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-calendar-check" style="color: var(--adm-gold);"></i> Manage Private Viewing Tour</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="site-visits.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="update_site_visit">
            <input type="hidden" name="inquiry_id" id="modal_visit_id">

            <div class="adm-modal-body">
                <div style="background: #F8FAFC; border: 1px solid var(--adm-border); border-radius: 8px; padding: 1rem; margin-bottom: 1.25rem;">
                    <div style="font-weight: 700; color: var(--adm-navy); font-size: 0.95rem;" id="modal_v_client"></div>
                    <div style="font-size: 0.8rem; color: var(--adm-text-muted);" id="modal_v_property"></div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Confirmed Visit Date</label>
                        <input type="date" name="visit_date" id="modal_v_date" class="adm-input">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Visit Time Slot</label>
                        <input type="text" name="visit_time" id="modal_v_time" class="adm-input" placeholder="e.g. 11:30 AM - 1:00 PM">
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Appointment Status *</label>
                        <select name="status" id="modal_v_status" class="adm-select">
                            <option value="new">Pending Confirmation</option>
                            <option value="scheduled">Confirmed & Scheduled</option>
                            <option value="contacted">Contacted / Rescheduling</option>
                            <option value="closed">Completed Tour</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Escort Private Wealth Advisor</label>
                        <select name="assigned_agent_id" id="modal_v_agent" class="adm-select">
                            <option value="">-- Select Advisor --</option>
                            <?php foreach ($assignable_agents as $ag): ?>
                                <option value="<?= $ag['id'] ?>"><?= e($ag['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Escort & Chauffeur Logistics Notes</label>
                    <textarea name="admin_notes" id="modal_v_notes" class="adm-textarea" rows="3" placeholder="Confirm gate pass entry, security clearance, chauffeur pickup, or key handover..."></textarea>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-gold">Confirm Schedule</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.btn-manage-visit').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('modal_visit_id').value = this.dataset.id;
        document.getElementById('modal_v_client').textContent = this.dataset.client + " (" + this.dataset.phone + ")";
        document.getElementById('modal_v_property').textContent = "Property: " + this.dataset.property;
        document.getElementById('modal_v_date').value = this.dataset.date || '';
        document.getElementById('modal_v_time').value = this.dataset.time || '';
        document.getElementById('modal_v_status').value = this.dataset.status;
        document.getElementById('modal_v_agent').value = this.dataset.agent || '';
        document.getElementById('modal_v_notes').value = this.dataset.notes || '';
        document.getElementById('modalManageVisit').classList.add('show');
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

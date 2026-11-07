<?php
/**
 * EstateVista India - Admin Command Center Dashboard
 * Executive statistics, interactive charts, pending approvals queue,
 * recent inquiries, and real-time activity tracking.
 */

$page_title = 'Executive Dashboard';

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
require_once __DIR__ . '/includes/admin_navbar.php';

$stats = get_admin_dashboard_stats();
$pending_properties = get_admin_pending_properties(6);
$recent_inquiries = get_admin_recent_inquiries(6);
$recent_activities = get_admin_recent_activities(6);
$chart_city_data = get_chart_properties_by_city();
$chart_inq_data = get_chart_inquiries_by_status();
$agents = get_all_assignable_agents();

// Quick action: approve property directly from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_approve') {
    $propId = (int)($_POST['property_id'] ?? 0);
    if ($propId > 0 && admin_has_role(['super_admin', 'admin'])) {
        $db = Database::getConnection();
        $upd = $db->prepare("UPDATE properties SET approval_status = 'approved', is_verified = 1 WHERE id = :id");
        $upd->execute([':id' => $propId]);
        log_admin_activity($current_admin['id'], $current_admin['name'], 'Property Approved', "Approved property listing #{$propId} directly from dashboard.");
        $_SESSION['admin_flash_success'] = "Property #{$propId} approved and is now live publicly.";
        header('Location: ' . BASE_URL . 'admin/index.php');
        exit;
    }
}
?>

<!-- Embedded JSON Data for Chart.js -->
<script type="application/json" id="chartPropertiesCityData">
    <?= json_encode($chart_city_data) ?>
</script>
<script type="application/json" id="chartInquiriesStatusData">
    <?= json_encode($chart_inq_data) ?>
</script>

<!-- Welcome Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-family: 'Cinzel', serif; font-size: 1.6rem; color: var(--adm-navy); margin-bottom: 0.25rem;">
            Welcome back, <?= e(explode(' ', $current_admin['name'])[0]) ?>
        </h2>
        <p style="color: var(--adm-text-muted); font-size: 0.9rem;">
            Executive portfolio oversight, client acquisitions, and editorial curation for EstateVista India.
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="<?= BASE_URL ?>admin/approvals.php" class="adm-btn adm-btn-outline" style="border-color: #FDE68A; background: #FFFBEB; color: #92400E;">
            <i class="fa-solid fa-clock-rotate-left" style="color: #D97706;"></i>
            <span>Pending Approvals (<?= $stats['pending_approvals'] ?>)</span>
        </a>
        <a href="<?= BASE_URL ?>admin/reports.php" class="adm-btn adm-btn-navy">
            <i class="fa-solid fa-file-export"></i>
            <span>Export Reports</span>
        </a>
    </div>
</div>

<!-- 1. Executive KPI Statistics Cards -->
<div class="adm-kpi-grid">
    <!-- Total Portfolio Value -->
    <div class="adm-kpi-card">
        <div>
            <div class="adm-kpi-val" style="color: #B45309;">₹ <?= number_format($stats['total_portfolio_value_cr'], 2) ?> Cr</div>
            <div class="adm-kpi-label">Active Portfolio Value</div>
        </div>
        <div class="adm-kpi-icon adm-kpi-gold">
            <i class="fa-solid fa-gem"></i>
        </div>
    </div>

    <!-- Total Properties -->
    <div class="adm-kpi-card">
        <div>
            <div class="adm-kpi-val"><?= $stats['approved_properties'] ?></div>
            <div class="adm-kpi-label">Live Approved Residences (<?= $stats['total_properties'] ?> total)</div>
        </div>
        <div class="adm-kpi-icon adm-kpi-blue">
            <i class="fa-solid fa-building"></i>
        </div>
    </div>

    <!-- Active Inquiries -->
    <div class="adm-kpi-card">
        <div>
            <div class="adm-kpi-val"><?= $stats['total_inquiries'] ?></div>
            <div class="adm-kpi-label">Total Leads (<?= $stats['new_inquiries'] ?> New Inquiries)</div>
        </div>
        <div class="adm-kpi-icon adm-kpi-green">
            <i class="fa-solid fa-envelope-open-text"></i>
        </div>
    </div>

    <!-- Registered Clients / Owners -->
    <div class="adm-kpi-card">
        <div>
            <div class="adm-kpi-val"><?= $stats['total_users'] ?></div>
            <div class="adm-kpi-label">Registered HNI Clients (<?= $stats['total_owners'] ?> Owners)</div>
        </div>
        <div class="adm-kpi-icon adm-kpi-purple">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>
</div>

<!-- 2. Interactive Charts Row -->
<div class="adm-grid-2" style="margin-bottom: 2rem;">
    <!-- Chart 1: Properties by Prime City -->
    <div class="adm-card" style="margin-bottom: 0;">
        <div class="adm-card-header">
            <h3 class="adm-card-title">
                <i class="fa-solid fa-chart-pie" style="color: var(--adm-gold);"></i>
                <span>Portfolio Distribution by Prime City</span>
            </h3>
            <span style="font-size: 0.78rem; color: var(--adm-text-muted);">Approved Listings</span>
        </div>
        <div class="adm-card-body" style="height: 280px; position: relative;">
            <canvas id="chartPropertiesCity"></canvas>
        </div>
    </div>

    <!-- Chart 2: Leads & Inquiries by Stage -->
    <div class="adm-card" style="margin-bottom: 0;">
        <div class="adm-card-header">
            <h3 class="adm-card-title">
                <i class="fa-solid fa-chart-simple" style="color: var(--adm-gold);"></i>
                <span>Client Acquisition & Inquiries Pipeline</span>
            </h3>
            <span style="font-size: 0.78rem; color: var(--adm-text-muted);">By Lead Stage</span>
        </div>
        <div class="adm-card-body" style="height: 280px; position: relative;">
            <canvas id="chartInquiriesStatus"></canvas>
        </div>
    </div>
</div>

<!-- 3. Pending Approvals Queue (Priority Action) -->
<div class="adm-card">
    <div class="adm-card-header">
        <div>
            <h3 class="adm-card-title">
                <i class="fa-solid fa-clock-rotate-left" style="color: #D97706;"></i>
                <span>Pending Editorial & Title Curation Queue</span>
            </h3>
            <p style="font-size: 0.8rem; color: var(--adm-text-muted); margin: 0.2rem 0 0;">
                Submissions awaiting verification before appearing publicly across EstateVista India.
            </p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>admin/approvals.php" class="adm-btn adm-btn-sm adm-btn-gold">
                <span>View All Approvals (<?= $stats['pending_approvals'] ?>)</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

    <div class="adm-table-responsive">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Property Title</th>
                    <th>Category & Purpose</th>
                    <th>Location</th>
                    <th>Valuation</th>
                    <th>Submitted By</th>
                    <th>Submitted On</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pending_properties)): ?>
                    <?php foreach ($pending_properties as $prop): ?>
                        <tr>
                            <td style="width: 70px;">
                                <div style="width: 56px; height: 42px; border-radius: 6px; overflow: hidden; background: #0B1528;">
                                    <img src="<?= e(safe_image_url($prop['cover_image'], 'property')) ?>" alt="<?= e($prop['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                                </div>
                            </td>
                            <td>
                                <strong style="color: var(--adm-navy);"><?= e($prop['title']) ?></strong>
                                <div style="font-size: 0.75rem; color: #64748B;"><?= (int)$prop['bhk'] ?> BHK &bull; <?= format_inr_number($prop['carpet_area_sqft']) ?> sq.ft</div>
                            </td>
                            <td>
                                <span style="font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.5rem; background: #F1F5F9; border-radius: 4px;">
                                    <?= e($prop['category_name']) ?> (<?= strtoupper(e($prop['purpose'])) ?>)
                                </span>
                            </td>
                            <td><?= e($prop['locality']) ?>, <?= e($prop['city_name']) ?></td>
                            <td><strong style="color: #B45309;"><?= e($prop['price_display']) ?></strong></td>
                            <td>
                                <div><?= e($prop['submitter_name'] ?: ($prop['contact_person_name'] ?: 'Direct Owner')) ?></div>
                                <div style="font-size: 0.75rem; color: #94A3B8;"><?= e($prop['contact_person_phone']) ?></div>
                            </td>
                            <td><?= format_admin_date($prop['created_at'], false) ?></td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="<?= BASE_URL ?>property-detail.php?slug=<?= urlencode($prop['slug']) ?>" target="_blank" class="adm-btn adm-btn-sm adm-btn-outline" title="Inspect Full Dossier">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <?php if (admin_has_role(['super_admin', 'admin'])): ?>
                                    <form method="POST" action="<?= BASE_URL ?>admin/index.php" style="display: inline-block;">
                                        <input type="hidden" name="action" value="quick_approve">
                                        <input type="hidden" name="property_id" value="<?= $prop['id'] ?>">
                                        <button type="submit" class="adm-btn adm-btn-sm adm-btn-success" title="Approve & Publish Immediately">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2.5rem; color: #64748B;">
                            <i class="fa-solid fa-circle-check" style="font-size: 2rem; color: #10B981; margin-bottom: 0.5rem; display: block;"></i>
                            All property submissions have been curated and approved. Zero pending items.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 4. Recent Inquiries and Activity Log Grid -->
<div class="adm-grid-2">
    <!-- Recent Inquiries -->
    <div class="adm-card">
        <div class="adm-card-header">
            <h3 class="adm-card-title">
                <i class="fa-solid fa-envelope-open-text" style="color: var(--adm-gold);"></i>
                <span>Latest Client Enquiries & Callbacks</span>
            </h3>
            <a href="<?= BASE_URL ?>admin/enquiries.php" style="font-size: 0.8rem; color: var(--adm-gold); font-weight: 600; text-decoration: none;">View All &rarr;</a>
        </div>
        <div class="adm-table-responsive">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Type</th>
                        <th>Stage</th>
                        <th>Property</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recent_inquiries)): ?>
                        <?php foreach ($recent_inquiries as $inq): ?>
                            <tr>
                                <td>
                                    <strong><?= e($inq['full_name']) ?></strong>
                                    <div style="font-size: 0.72rem; color: #64748B;"><?= e($inq['phone']) ?></div>
                                </td>
                                <td>
                                    <span style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: #475569;">
                                        <?= str_replace('_', ' ', e($inq['inquiry_type'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php $stage = $inq['lead_status'] ?: 'new'; ?>
                                    <span class="adm-status-badge adm-badge-<?= e($stage) ?>">
                                        <?= strtoupper(e($stage)) ?>
                                    </span>
                                </td>
                                <td style="max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= e($inq['property_title'] ?: 'General Advisory') ?>
                                </td>
                                <td style="font-size: 0.75rem; color: #94A3B8;"><?= format_admin_date($inq['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #94A3B8; padding: 2rem;">No enquiries recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activity Log Feed -->
    <div class="adm-card">
        <div class="adm-card-header">
            <h3 class="adm-card-title">
                <i class="fa-solid fa-list-check" style="color: var(--adm-gold);"></i>
                <span>Command Center Activity Feed</span>
            </h3>
            <span style="font-size: 0.78rem; color: var(--adm-text-muted);">Real-Time Audit Trail</span>
        </div>
        <div class="adm-card-body">
            <ul class="adm-activity-list">
                <?php if (!empty($recent_activities)): ?>
                    <?php foreach ($recent_activities as $act): ?>
                        <li class="adm-activity-item">
                            <div class="adm-activity-dot"></div>
                            <div class="adm-activity-title"><?= e($act['action']) ?> &bull; <span style="font-size: 0.78rem; color: #64748B;"><?= e($act['admin_name']) ?></span></div>
                            <div class="adm-activity-desc"><?= e($act['details']) ?></div>
                            <div class="adm-activity-time"><?= format_admin_date($act['created_at']) ?> (IP: <?= e($act['ip_address']) ?>)</div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li style="color: #94A3B8; text-align: center; padding: 1.5rem;">No recent activities logged.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>

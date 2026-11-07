<?php
/**
 * EstateVista India - Property Owners & Developers Directory (Module 6)
 * Consolidated index of property listers, luxury home sellers, and corporate developers.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';

require_admin_login();
require_admin_role(['super_admin', 'admin']);

$db = Database::getConnection();

// Fetch Owners with aggregated property metrics
$search = trim($_GET['search'] ?? '');
$role_filter = trim($_GET['role'] ?? '');

$sql = "
    SELECT 
        u.id as user_id,
        COALESCE(u.full_name, p.contact_person_name, 'Private Client') as owner_name,
        COALESCE(u.email, p.contact_person_email, '—') as owner_email,
        COALESCE(u.phone, p.contact_person_phone, '—') as owner_phone,
        COALESCE(u.city, 'Mumbai') as owner_city,
        COALESCE(p.owner_role, 'owner') as owner_type,
        COUNT(p.id) as total_listings,
        SUM(CASE WHEN p.approval_status = 'approved' THEN 1 ELSE 0 END) as approved_listings,
        SUM(CASE WHEN p.approval_status = 'pending' THEN 1 ELSE 0 END) as pending_listings,
        SUM(CASE WHEN p.approval_status = 'rejected' THEN 1 ELSE 0 END) as rejected_listings,
        SUM(CASE WHEN p.approval_status = 'approved' THEN p.price ELSE 0 END) as portfolio_valuation,
        MAX(p.created_at) as latest_listing_date
    FROM properties p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE :s1 OR p.contact_person_name LIKE :s2 OR u.email LIKE :s3 OR p.contact_person_email LIKE :s4 OR u.phone LIKE :s5)";
    $term = "%{$search}%";
    $params[':s1'] = $term;
    $params[':s2'] = $term;
    $params[':s3'] = $term;
    $params[':s4'] = $term;
    $params[':s5'] = $term;
}

if (!empty($role_filter) && in_array($role_filter, ['owner', 'agent', 'developer'])) {
    $sql .= " AND p.owner_role = :role";
    $params[':role'] = $role_filter;
}

$sql .= "
    GROUP BY u.id, owner_name, owner_email, owner_phone, owner_city, owner_type
    ORDER BY total_listings DESC, portfolio_valuation DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$owners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary Stats
$total_owners_count = count($owners);
$total_active_listings = array_sum(array_column($owners, 'approved_listings'));
$total_pending_listings = array_sum(array_column($owners, 'pending_listings'));
$total_valuation_sum = array_sum(array_column($owners, 'portfolio_valuation'));
$total_valuation_cr = round($total_valuation_sum / 10000000, 2);

$page_title = "Property Owners & Developers";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                    Property Owners & Developers Directory
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                    Dossiers of luxury homeowners, institutional developers, and certified real estate partners listing on EstateVista.
                </p>
            </div>
            <a href="properties.php" class="adm-btn adm-btn-navy">
                <i class="fa-solid fa-building"></i> View All Properties
            </a>
        </div>

        <!-- KPI Cards -->
        <div class="adm-kpi-grid">
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val"><?= $total_owners_count ?></div>
                    <div class="adm-kpi-label">Active Property Listers</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-blue">
                    <i class="fa-solid fa-house-chimney-user"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val"><?= $total_active_listings ?></div>
                    <div class="adm-kpi-label">Approved Live Listings</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-green">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val"><?= $total_pending_listings ?></div>
                    <div class="adm-kpi-label">In Curation Queue</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-gold">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val">₹<?= number_format($total_valuation_cr, 2) ?> Cr</div>
                    <div class="adm-kpi-label">Total Portfolio Asset Value</div>
                </div>
                <div class="adm-kpi-icon adm-kpi-purple">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="adm-card">
            <div class="adm-card-body" style="padding: 1.25rem;">
                <form method="GET" action="owners.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                    <div class="adm-search-input-wrap" style="flex: 1; min-width: 250px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" class="adm-input" placeholder="Search by name, email, phone..." value="<?= e($search) ?>">
                    </div>
                    <div style="width: 200px;">
                        <select name="role" class="adm-select" onchange="this.form.submit()">
                            <option value="">All Roles</option>
                            <option value="owner" <?= ($role_filter === 'owner') ? 'selected' : '' ?>>Individual Owner</option>
                            <option value="developer" <?= ($role_filter === 'developer') ? 'selected' : '' ?>>Corporate Developer</option>
                            <option value="agent" <?= ($role_filter === 'agent') ? 'selected' : '' ?>>External Broker/Agent</option>
                        </select>
                    </div>
                    <button type="submit" class="adm-btn adm-btn-navy">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <?php if (!empty($search) || !empty($role_filter)): ?>
                        <a href="owners.php" class="adm-btn adm-btn-outline">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Owners Table -->
        <div class="adm-card">
            <div class="adm-card-header">
                <div class="adm-card-title">
                    <i class="fa-solid fa-building-user" style="color: var(--adm-gold);"></i>
                    Registered Owners & Developers (<?= count($owners) ?>)
                </div>
            </div>
            <div class="adm-table-responsive">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Owner / Developer</th>
                            <th>Contact Coordinates</th>
                            <th>Role / Entity Type</th>
                            <th>Listings Breakdown</th>
                            <th>Portfolio Valuation</th>
                            <th>Latest Submission</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($owners)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem; color: var(--adm-text-muted);">
                                    <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; color: #CBD5E1; margin-bottom: 0.75rem; display: block;"></i>
                                    No property owners or developers found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($owners as $ow): ?>
                                <?php $val_cr = round(((float)$ow['portfolio_valuation']) / 10000000, 2); ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.85rem;">
                                            <div style="width: 42px; height: 42px; border-radius: 8px; background: #FEF3C7; color: #92400E; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; border: 1.5px solid #FDE68A;">
                                                <i class="fa-solid <?= $ow['owner_type'] === 'developer' ? 'fa-city' : 'fa-house-user' ?>"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight: 700; color: var(--adm-navy);"><?= e($ow['owner_name']) ?></div>
                                                <div style="font-size: 0.75rem; color: var(--adm-text-muted);"><i class="fa-solid fa-location-dot"></i> <?= e($ow['owner_city']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500; font-size: 0.85rem;"><?= e($ow['owner_email']) ?></div>
                                        <div style="font-size: 0.78rem; color: var(--adm-text-muted);"><i class="fa-solid fa-phone" style="font-size: 0.7rem; color: var(--adm-gold);"></i> <?= e($ow['owner_phone']) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($ow['owner_type'] === 'developer'): ?>
                                            <span class="adm-status-badge adm-badge-new" style="background: #E0E7FF; color: #3730A3;">Corporate Developer</span>
                                        <?php elseif ($ow['owner_type'] === 'agent'): ?>
                                            <span class="adm-status-badge adm-badge-contacted">External Partner</span>
                                        <?php else: ?>
                                            <span class="adm-status-badge adm-badge-approved">Homeowner</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.84rem;">
                                            <strong style="color: var(--adm-navy);"><?= $ow['total_listings'] ?></strong> Total Listings
                                        </div>
                                        <div style="font-size: 0.74rem; color: var(--adm-text-muted); margin-top: 2px;">
                                            <span style="color: #059669; font-weight: 600;"><?= $ow['approved_listings'] ?> Live</span> | 
                                            <span style="color: #D97706; font-weight: 600;"><?= $ow['pending_listings'] ?> Pending</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--adm-navy); font-size: 0.95rem;">
                                            ₹<?= number_format($val_cr, 2) ?> Cr
                                        </div>
                                    </td>
                                    <td style="font-size: 0.8rem; color: var(--adm-text-muted);">
                                        <?= format_admin_date($ow['latest_listing_date'], false) ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if (!empty($ow['user_id'])): ?>
                                            <a href="properties.php?search=<?= urlencode($ow['owner_email']) ?>" class="adm-btn adm-btn-sm adm-btn-outline" title="Inspect Listings">
                                                <i class="fa-solid fa-list-check"></i> View Listings
                                            </a>
                                        <?php else: ?>
                                            <a href="properties.php?search=<?= urlencode($ow['owner_name']) ?>" class="adm-btn adm-btn-sm adm-btn-outline" title="Inspect Listings">
                                                <i class="fa-solid fa-list-check"></i> View Listings
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

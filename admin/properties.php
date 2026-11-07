<?php
/**
 * EstateVista India - Admin Properties Management
 * Filter, search, paginate, approve, reject, feature, and delete luxury properties.
 */

$page_title = 'Properties Management';

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
require_once __DIR__ . '/includes/admin_navbar.php';

$db = Database::getConnection();

// Handle Actions (POST): Toggle Featured, Toggle Exclusive, Delete, Quick Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $propId = (int)($_POST['property_id'] ?? 0);

    if ($propId > 0 && admin_has_role(['super_admin', 'admin'])) {
        if ($action === 'toggle_featured') {
            $stmt = $db->prepare("UPDATE properties SET is_featured = NOT is_featured WHERE id = :id");
            $stmt->execute([':id' => $propId]);
            log_admin_activity($current_admin['id'], $current_admin['name'], 'Toggled Featured', "Toggled featured status for property #{$propId}");
            $_SESSION['admin_flash_success'] = "Featured status updated for property #{$propId}.";
        } elseif ($action === 'toggle_exclusive') {
            $stmt = $db->prepare("UPDATE properties SET is_exclusive = NOT is_exclusive WHERE id = :id");
            $stmt->execute([':id' => $propId]);
            log_admin_activity($current_admin['id'], $current_admin['name'], 'Toggled Exclusive', "Toggled exclusive portfolio status for property #{$propId}");
            $_SESSION['admin_flash_success'] = "Exclusive portfolio status updated for property #{$propId}.";
        } elseif ($action === 'approve') {
            $stmt = $db->prepare("UPDATE properties SET approval_status = 'approved', is_verified = 1 WHERE id = :id");
            $stmt->execute([':id' => $propId]);
            log_admin_activity($current_admin['id'], $current_admin['name'], 'Property Approved', "Approved property #{$propId}");
            $_SESSION['admin_flash_success'] = "Property #{$propId} approved and is live.";
        } elseif ($action === 'delete') {
            // Strictly require super_admin or admin
            $delStmt = $db->prepare("DELETE FROM properties WHERE id = :id");
            $delStmt->execute([':id' => $propId]);
            log_admin_activity($current_admin['id'], $current_admin['name'], 'Property Deleted', "Permanently removed property #{$propId}");
            $_SESSION['admin_flash_success'] = "Property #{$propId} removed permanently.";
        }
        header('Location: ' . BASE_URL . 'admin/properties.php');
        exit;
    }
}

// Filters & Pagination
$city_filter = $_GET['city'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$search_query = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = ["1=1"];
$params = [];

if ($city_filter !== 'all' && is_numeric($city_filter)) {
    $where[] = "p.city_id = :city_id";
    $params[':city_id'] = (int)$city_filter;
}

if ($category_filter !== 'all' && is_numeric($category_filter)) {
    $where[] = "p.category_id = :cat_id";
    $params[':cat_id'] = (int)$category_filter;
}

if ($status_filter !== 'all' && in_array($status_filter, ['approved', 'pending', 'rejected'])) {
    $where[] = "p.approval_status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search_query)) {
    $where[] = "(p.title LIKE :q1 OR p.locality LIKE :q2 OR p.rera_number LIKE :q3 OR p.address LIKE :q4)";
    $qVal = '%' . $search_query . '%';
    $params[':q1'] = $qVal;
    $params[':q2'] = $qVal;
    $params[':q3'] = $qVal;
    $params[':q4'] = $qVal;
}

$whereSql = implode(' AND ', $where);

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM properties p WHERE $whereSql");
foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
$countStmt->execute();
$total_rows = (int)$countStmt->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

// Data Query
$dataSql = "
    SELECT p.*, c.name as city_name, cat.name as category_name,
           u.full_name as submitter_name
    FROM properties p
    LEFT JOIN cities c ON p.city_id = c.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE $whereSql
    ORDER BY p.id DESC
    LIMIT :limit OFFSET :offset
";
$dataStmt = $db->prepare($dataSql);
foreach ($params as $k => $v) $dataStmt->bindValue($k, $v);
$dataStmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$properties = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

$cities = get_cities_list();
$categories = get_all_categories();
?>

<!-- Header Toolbar -->
<div class="adm-toolbar">
    <div>
        <h2 style="font-size: 1.3rem; font-weight: 700; color: var(--adm-navy);">Properties Directory</h2>
        <p style="font-size: 0.85rem; color: var(--adm-text-muted);">
            Showing <?= $total_rows ?> total luxury residences and commercial assets.
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="<?= BASE_URL ?>admin/reports.php?type=properties" class="adm-btn adm-btn-outline">
            <i class="fa-solid fa-file-csv"></i>
            <span>Export CSV</span>
        </a>
        <a href="<?= BASE_URL ?>admin/property-add.php" class="adm-btn adm-btn-gold">
            <i class="fa-solid fa-plus-circle"></i>
            <span>Add New Property</span>
        </a>
    </div>
</div>

<!-- Filter Box -->
<div class="adm-card" style="margin-bottom: 1.5rem;">
    <div class="adm-card-body" style="padding: 1.25rem;">
        <form method="GET" action="<?= BASE_URL ?>admin/properties.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <!-- Search -->
            <div style="flex: 1; min-width: 220px;">
                <label class="adm-label">Search Property</label>
                <div class="adm-search-input-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="q" class="adm-input" placeholder="Title, locality, address, or RERA ID..." value="<?= e($search_query) ?>">
                </div>
            </div>

            <!-- City Filter -->
            <div style="width: 170px;">
                <label class="adm-label">City</label>
                <select name="city" class="adm-select">
                    <option value="all">All Cities</option>
                    <?php foreach ($cities as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($city_filter == $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Category Filter -->
            <div style="width: 180px;">
                <label class="adm-label">Category</label>
                <select name="category" class="adm-select">
                    <option value="all">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($category_filter == $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div style="width: 160px;">
                <label class="adm-label">Approval Status</label>
                <select name="status" class="adm-select">
                    <option value="all">All Statuses</option>
                    <option value="approved" <?= ($status_filter === 'approved') ? 'selected' : '' ?>>Approved & Live</option>
                    <option value="pending" <?= ($status_filter === 'pending') ? 'selected' : '' ?>>Pending Review</option>
                    <option value="rejected" <?= ($status_filter === 'rejected') ? 'selected' : '' ?>>Rejected / Revisions</option>
                </select>
            </div>

            <!-- Submit Filter -->
            <div>
                <button type="submit" class="adm-btn adm-btn-navy">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="<?= BASE_URL ?>admin/properties.php" class="adm-btn adm-btn-outline" title="Reset Filters">
                    <i class="fa-solid fa-rotate-right"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="adm-card">
    <div class="adm-table-responsive">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Cover</th>
                    <th>Residence Details</th>
                    <th>City & Locality</th>
                    <th>Valuation</th>
                    <th>Curation Status</th>
                    <th>Featured / Exclusive</th>
                    <th>Source / Owner</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($properties)): ?>
                    <?php foreach ($properties as $prop): ?>
                        <tr>
                            <td style="width: 60px;">
                                <div style="width: 54px; height: 40px; border-radius: 6px; overflow: hidden; background: #0B1528;">
                                    <img src="<?= e(safe_image_url($prop['cover_image'], 'property')) ?>" alt="<?= e($prop['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                                </div>
                            </td>
                            <td>
                                <strong style="color: var(--adm-navy);"><?= e($prop['title']) ?></strong>
                                <div style="font-size: 0.76rem; color: #64748B;">
                                    <?= (int)$prop['bhk'] ?> BHK &bull; <?= format_inr_number($prop['carpet_area_sqft']) ?> sq.ft &bull; <?= e($prop['category_name']) ?>
                                </div>
                            </td>
                            <td>
                                <div><?= e($prop['locality']) ?></div>
                                <div style="font-size: 0.75rem; color: #94A3B8;"><?= e($prop['city_name']) ?></div>
                            </td>
                            <td>
                                <strong style="color: #B45309;"><?= e($prop['price_display']) ?></strong>
                            </td>
                            <td>
                                <?php if ($prop['approval_status'] === 'approved'): ?>
                                    <span class="adm-status-badge adm-badge-approved"><i class="fa-solid fa-circle-check"></i> Live</span>
                                <?php elseif ($prop['approval_status'] === 'rejected'): ?>
                                    <span class="adm-status-badge adm-badge-rejected"><i class="fa-solid fa-triangle-exclamation"></i> Changes</span>
                                <?php else: ?>
                                    <span class="adm-status-badge adm-badge-pending"><i class="fa-solid fa-clock"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.4rem;">
                                    <form method="POST" action="<?= BASE_URL ?>admin/properties.php" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_featured">
                                        <input type="hidden" name="property_id" value="<?= $prop['id'] ?>">
                                        <button type="submit" class="adm-btn adm-btn-sm <?= $prop['is_featured'] ? 'adm-btn-gold' : 'adm-btn-outline' ?>" title="Toggle Featured">
                                            <i class="fa-solid fa-star"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= BASE_URL ?>admin/properties.php" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_exclusive">
                                        <input type="hidden" name="property_id" value="<?= $prop['id'] ?>">
                                        <button type="submit" class="adm-btn adm-btn-sm <?= $prop['is_exclusive'] ? 'adm-btn-navy' : 'adm-btn-outline' ?>" title="Toggle Exclusive Portfolio">
                                            <i class="fa-solid fa-crown"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 0.8rem;"><?= e($prop['submitter_name'] ?: ($prop['contact_person_name'] ?: 'EstateVista In-House')) ?></div>
                                <div style="font-size: 0.72rem; color: #94A3B8; text-transform: uppercase;"><?= e($prop['owner_role'] ?: 'Admin') ?></div>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="<?= BASE_URL ?>property-detail.php?slug=<?= urlencode($prop['slug']) ?>" target="_blank" class="adm-btn adm-btn-sm adm-btn-outline" title="View Dossier">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>admin/property-edit.php?id=<?= $prop['id'] ?>" class="adm-btn adm-btn-sm adm-btn-outline" title="Edit Property Specifications & Media">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <?php if (admin_has_role(['super_admin', 'admin'])): ?>
                                    <form method="POST" action="<?= BASE_URL ?>admin/properties.php" style="display: inline-block;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="property_id" value="<?= $prop['id'] ?>">
                                        <button type="submit" class="adm-btn adm-btn-sm adm-btn-outline adm-confirm-delete" data-item-name="<?= e($prop['title']) ?>" title="Delete Property Permanently" style="color: #DC2626; border-color: #FECACA;">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem; color: #64748B;">
                            No properties found matching your filter criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--adm-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="font-size: 0.82rem; color: var(--adm-text-muted);">
                Page <?= $page ?> of <?= $total_pages ?> (Total <?= $total_rows ?> properties)
            </div>
            <div style="display: flex; gap: 0.4rem;">
                <?php if ($page > 1): ?>
                    <a href="?p=<?= $page - 1 ?>&city=<?= urlencode($city_filter) ?>&category=<?= urlencode($category_filter) ?>&status=<?= urlencode($status_filter) ?>&q=<?= urlencode($search_query) ?>" class="adm-btn adm-btn-sm adm-btn-outline">
                        &laquo; Prev
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?p=<?= $i ?>&city=<?= urlencode($city_filter) ?>&category=<?= urlencode($category_filter) ?>&status=<?= urlencode($status_filter) ?>&q=<?= urlencode($search_query) ?>" class="adm-btn adm-btn-sm <?= ($i === $page) ? 'adm-btn-gold' : 'adm-btn-outline' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?p=<?= $page + 1 ?>&city=<?= urlencode($city_filter) ?>&category=<?= urlencode($category_filter) ?>&status=<?= urlencode($status_filter) ?>&q=<?= urlencode($search_query) ?>" class="adm-btn adm-btn-sm adm-btn-outline">
                        Next &raquo;
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>

<?php
/**
 * EstateVista India - Assigned Luxury Properties Portfolio
 * High-value inventory managed by advisory wealth managers and agents.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';
require_once __DIR__ . '/includes/crm_functions.php';

require_admin_login();

$db = Database::getConnection();
$logged_admin = get_logged_admin();
$admin_role = get_admin_role();
$success_msg = '';
$error_msg = '';

// ==========================================
// HANDLE ACTIONS (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_admin_csrf()) {
        $error_msg = "Security token expired. Please reload and try again.";
    } else {
        $action = sanitize($_POST['action'] ?? '');

        // Reassign Property to Advisor (Super Admin / Admin only)
        if ($action === 'reassign_property') {
            if (!admin_has_role(['super_admin', 'admin'])) {
                $error_msg = "Unauthorized action. Only administrators can reassign luxury listings.";
            } else {
                $property_id = (int)($_POST['property_id'] ?? 0);
                $agent_id = !empty($_POST['assigned_agent_id']) ? (int)$_POST['assigned_agent_id'] : null;

                if ($property_id > 0) {
                    try {
                        $stmt = $db->prepare("UPDATE properties SET assigned_agent_id = :aid WHERE id = :id");
                        $stmt->execute([':aid' => $agent_id, ':id' => $property_id]);
                        
                        $prop = $db->query("SELECT title FROM properties WHERE id = {$property_id}")->fetch(PDO::FETCH_ASSOC);
                        $pTitle = $prop['title'] ?? "Property #{$property_id}";
                        
                        $agentName = "Unassigned";
                        if ($agent_id) {
                            $agentName = $db->query("SELECT name FROM admins WHERE id = {$agent_id}")->fetchColumn() ?: "Advisor #{$agent_id}";
                        }

                        log_admin_activity('Reassigned Property Advisor', "Reassigned '{$pTitle}' to {$agentName}");
                        $success_msg = "Listing '{$pTitle}' successfully assigned to {$agentName}.";
                    } catch (PDOException $e) {
                        $error_msg = "Database error: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

// ==========================================
// FILTERS & SELECTION
// ==========================================
$search = trim($_GET['search'] ?? '');
$agent_filter = trim($_GET['agent_id'] ?? '');
$city_filter = trim($_GET['city'] ?? '');

if ($admin_role === 'agent') {
    $agent_filter = (string)$logged_admin['id'];
}

$target_agent_id = !empty($agent_filter) ? (int)$agent_filter : null;
$properties = get_crm_agent_properties($target_agent_id);

// In-memory filter for search & city if provided
if (!empty($search) || !empty($city_filter)) {
    $properties = array_filter($properties, function($p) use ($search, $city_filter) {
        $matchSearch = true;
        $matchCity = true;

        if (!empty($search)) {
            $term = strtolower($search);
            $matchSearch = (
                str_contains(strtolower($p['title']), $term) ||
                str_contains(strtolower($p['locality'] ?? ''), $term) ||
                str_contains(strtolower($p['city_name'] ?? ''), $term)
            );
        }

        if (!empty($city_filter)) {
            $matchCity = (strcasecmp($p['city_name'] ?? '', $city_filter) === 0);
        }

        return $matchSearch && $matchCity;
    });
}

// Compute aggregate portfolio statistics
$total_properties_count = count($properties);
$total_portfolio_val = 0;
$total_inquiries_count = 0;
$total_visits_count = 0;

foreach ($properties as $p) {
    $total_portfolio_val += (float)($p['price'] ?? 0);
    $total_inquiries_count += (int)($p['inquiries_count'] ?? 0);
    $total_visits_count += (int)($p['visits_count'] ?? 0);
}

$total_portfolio_cr = round($total_portfolio_val / 10000000, 2);

$assignable_agents = get_all_assignable_agents();
$cities_list = $db->query("SELECT name FROM cities ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

$page_title = "Assigned Luxury Residences Portfolio";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Top Executive Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                    <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; color: var(--adm-gold); background: rgba(197, 168, 128, 0.12); padding: 0.2rem 0.6rem; border-radius: 4px;">
                        Inventory Portfolio
                    </span>
                    <span style="color: var(--adm-text-muted); font-size: 0.8rem;">&bull; Advisory Mandates</span>
                </div>
                <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--adm-navy); margin: 0;">
                    Assigned Luxury Residences Portfolio
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem; margin: 0.25rem 0 0 0;">
                    Prime residential assets currently mandated to your advisory representation.
                </p>
            </div>

            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <a href="crm-dashboard.php" class="adm-btn adm-btn-outline adm-btn-sm">
                    <i class="fa-solid fa-gauge-high"></i> CRM Desk
                </a>
                <a href="leads.php" class="adm-btn adm-btn-navy adm-btn-sm">
                    <i class="fa-solid fa-filter-circle-dollar"></i> Client Pipeline
                </a>
            </div>
        </div>

        <!-- Metric Cards Bar -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="adm-card" style="padding: 1.2rem; border-left: 4px solid var(--adm-navy);">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--adm-text-muted); font-weight: 700;">Mandated Residences</div>
                <div style="font-size: 1.6rem; font-weight: 800; color: var(--adm-navy);"><?= number_format($total_properties_count) ?> Assets</div>
            </div>
            <div class="adm-card" style="padding: 1.2rem; border-left: 4px solid var(--adm-gold);">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--adm-text-muted); font-weight: 700;">Combined Asset Value</div>
                <div style="font-size: 1.6rem; font-weight: 800; color: #92400E;">₹<?= $total_portfolio_cr ?> Cr</div>
            </div>
            <div class="adm-card" style="padding: 1.2rem; border-left: 4px solid #3B82F6;">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--adm-text-muted); font-weight: 700;">Client Inquiries</div>
                <div style="font-size: 1.6rem; font-weight: 800; color: #1E40AF;"><?= number_format($total_inquiries_count) ?> Received</div>
            </div>
            <div class="adm-card" style="padding: 1.2rem; border-left: 4px solid #8B5CF6;">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--adm-text-muted); font-weight: 700;">Private Site Tours</div>
                <div style="font-size: 1.6rem; font-weight: 800; color: #6D28D9;"><?= number_format($total_visits_count) ?> Tours</div>
            </div>
        </div>

        <!-- Feedback Alerts -->
        <?php if (!empty($success_msg)): ?>
            <div style="background: #ECFDF5; border-left: 4px solid var(--adm-success); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; color: #065F46; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-circle-check"></i> <span><?= e($success_msg) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div style="background: #FEF2F2; border-left: 4px solid var(--adm-danger); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; color: #991B1B; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-circle-exclamation"></i> <span><?= e($error_msg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Filter Toolbar -->
        <div class="adm-card" style="margin-bottom: 1.5rem;">
            <div class="adm-card-body" style="padding: 1rem 1.25rem;">
                <form method="GET" action="agent-properties.php" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                    <div class="adm-search-input-wrap" style="flex: 1.5; min-width: 220px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" class="adm-input" placeholder="Search property title, locality..." value="<?= e($search) ?>">
                    </div>

                    <?php if ($admin_role !== 'agent'): ?>
                        <div style="min-width: 190px;">
                            <select name="agent_id" class="adm-select" onchange="this.form.submit()">
                                <option value="">All Mandated Advisors</option>
                                <?php foreach ($assignable_agents as $ag): ?>
                                    <option value="<?= $ag['id'] ?>" <?= ($agent_filter == $ag['id']) ? 'selected' : '' ?>>
                                        <?= e($ag['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div style="min-width: 150px;">
                        <select name="city" class="adm-select" onchange="this.form.submit()">
                            <option value="">All Cities</option>
                            <?php foreach ($cities_list as $cName): ?>
                                <option value="<?= e($cName) ?>" <?= ($city_filter === $cName) ? 'selected' : '' ?>><?= e($cName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="adm-btn adm-btn-navy">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>

                    <?php if (!empty($search) || !empty($city_filter) || (!empty($agent_filter) && $admin_role !== 'agent')): ?>
                        <a href="agent-properties.php" class="adm-btn adm-btn-outline" title="Reset">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Properties Grid -->
        <?php if (empty($properties)): ?>
            <div class="adm-card" style="padding: 4rem 1.5rem; text-align: center;">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: #F1F5F9; color: var(--adm-navy); font-size: 1.8rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto;">
                    <i class="fa-solid fa-building-circle-check"></i>
                </div>
                <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--adm-navy); margin-bottom: 0.35rem;">
                    No Mandated Properties Found
                </h3>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem; max-width: 450px; margin: 0 auto;">
                    There are no luxury residences matching your filter criteria. Assigned properties will appear here automatically.
                </p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.25rem;">
                <?php foreach ($properties as $prop): ?>
                    <?php 
                        $cover = !empty($prop['cover_image']) ? BASE_URL . $prop['cover_image'] : BASE_URL . 'assets/images/property-placeholder.jpg';
                        $pubUrl = BASE_URL . 'property-details.php?slug=' . urlencode($prop['slug'] ?? '');
                    ?>
                    <div class="adm-card" style="display: flex; flex-direction: column; overflow: hidden; border-radius: 10px; transition: transform 0.2s, box-shadow 0.2s;">
                        <!-- Property Image with Badges -->
                        <div style="position: relative; height: 190px; background: #0B1528; overflow: hidden;">
                            <img src="<?= e(safe_image_url($cover, 'property')) ?>" alt="<?= e($prop['title']) ?>" 
                                 style="width: 100%; height: 100%; object-fit: cover; opacity: 0.95; transition: transform 0.3s;"
                                 onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                            
                            <!-- Category Badge -->
                            <div style="position: absolute; top: 10px; left: 10px; background: rgba(11, 21, 40, 0.85); backdrop-filter: blur(4px); color: #FFFFFF; font-size: 0.72rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 4px; text-transform: uppercase;">
                                <?= e($prop['category_name'] ?: 'Luxury') ?> &bull; <?= e($prop['bhk'] ? $prop['bhk'].' BHK' : '') ?>
                            </div>

                            <!-- Price Tag -->
                            <div style="position: absolute; bottom: 10px; left: 10px; background: rgba(197, 168, 128, 0.95); backdrop-filter: blur(4px); color: #0B1528; font-size: 0.88rem; font-weight: 800; padding: 0.3rem 0.7rem; border-radius: 4px;">
                                <?= e($prop['price_display'] ?: 'Price on Request') ?>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div style="padding: 1.2rem; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <!-- Title -->
                                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--adm-navy); margin: 0 0 0.35rem 0; line-height: 1.35;">
                                    <a href="<?= e($pubUrl) ?>" target="_blank" style="color: inherit; text-decoration: none;">
                                        <?= e($prop['title']) ?>
                                    </a>
                                </h3>

                                <!-- Location -->
                                <div style="font-size: 0.8rem; color: var(--adm-text-muted); margin-bottom: 0.75rem;">
                                    <i class="fa-solid fa-location-dot" style="color: var(--adm-gold); font-size: 0.75rem;"></i> 
                                    <?= e($prop['locality'] ?: '') ?><?= !empty($prop['city_name']) ? ', ' . e($prop['city_name']) : '' ?>
                                </div>

                                <!-- Key Specs -->
                                <div style="display: flex; gap: 0.75rem; font-size: 0.78rem; color: #475569; padding-bottom: 0.75rem; border-bottom: 1px solid #F1F5F9; margin-bottom: 0.75rem; flex-wrap: wrap;">
                                    <?php if (!empty($prop['carpet_area_sqft'])): ?>
                                        <span><i class="fa-solid fa-ruler-combined"></i> <?= number_format($prop['carpet_area_sqft']) ?> sq.ft</span>
                                    <?php endif; ?>
                                    <?php if (!empty($prop['bathrooms'])): ?>
                                        <span><i class="fa-solid fa-bath"></i> <?= $prop['bathrooms'] ?> Baths</span>
                                    <?php endif; ?>
                                    <?php if (!empty($prop['construction_status'])): ?>
                                        <span><i class="fa-solid fa-key"></i> <?= ucwords(str_replace('_', ' ', $prop['construction_status'])) ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Inquiry & Visit Counters -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0.75rem;">
                                    <div style="background: #F8FAFC; border: 1px solid var(--adm-border); border-radius: 6px; padding: 0.5rem; text-align: center;">
                                        <div style="font-size: 0.7rem; color: var(--adm-text-muted); text-transform: uppercase; font-weight: 700;">Inquiries</div>
                                        <div style="font-size: 1.15rem; font-weight: 800; color: #1E40AF;"><?= $prop['inquiries_count'] ?></div>
                                    </div>
                                    <div style="background: #F8FAFC; border: 1px solid var(--adm-border); border-radius: 6px; padding: 0.5rem; text-align: center;">
                                        <div style="font-size: 0.7rem; color: var(--adm-text-muted); text-transform: uppercase; font-weight: 700;">Site Visits</div>
                                        <div style="font-size: 1.15rem; font-weight: 800; color: #6D28D9;"><?= $prop['visits_count'] ?></div>
                                    </div>
                                </div>

                                <!-- Assigned Advisor Label -->
                                <?php if ($admin_role !== 'agent'): ?>
                                    <div style="font-size: 0.75rem; color: var(--adm-text-muted); margin-bottom: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                                        <span>Mandated To:</span>
                                        <span style="font-weight: 700; color: var(--adm-navy);">
                                            <i class="fa-solid fa-user-tie" style="color: var(--adm-gold);"></i> <?= e($prop['agent_name'] ?: 'Unassigned') ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Action Buttons -->
                            <div style="display: flex; gap: 0.5rem; align-items: center; border-top: 1px solid #F1F5F9; padding-top: 0.75rem;">
                                <a href="leads.php?search=<?= urlencode($prop['title']) ?>" class="adm-btn adm-btn-sm adm-btn-navy" style="flex: 1; text-align: center;">
                                    <i class="fa-solid fa-users"></i> Leads (<?= $prop['inquiries_count'] ?>)
                                </a>

                                <a href="<?= e($pubUrl) ?>" target="_blank" class="adm-btn adm-btn-sm adm-btn-outline" title="View Public Listing">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>

                                <?php if (admin_has_role(['super_admin', 'admin'])): ?>
                                    <button type="button" class="adm-btn adm-btn-sm adm-btn-gold btn-open-reassign" 
                                            title="Reassign Advisor"
                                            data-id="<?= $prop['id'] ?>"
                                            data-title="<?= e($prop['title']) ?>"
                                            data-agent="<?= $prop['assigned_agent_id'] ?>">
                                        <i class="fa-solid fa-user-pen"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- ==========================================
     MODAL: REASSIGN PROPERTY ADVISOR
     ========================================== -->
<?php if (admin_has_role(['super_admin', 'admin'])): ?>
<div class="adm-modal-backdrop" id="modalReassign">
    <div class="adm-modal" style="max-width: 480px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title">
                <i class="fa-solid fa-user-pen" style="color: var(--adm-gold);"></i> Reassign Luxury Residence Mandate
            </h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="agent-properties.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="reassign_property">
            <input type="hidden" name="property_id" id="modal_p_id">

            <div class="adm-modal-body">
                <div style="background: #F8FAFC; border: 1px solid var(--adm-border); border-radius: 8px; padding: 0.85rem; margin-bottom: 1.25rem;">
                    <div style="font-weight: 700; color: var(--adm-navy);" id="modal_p_title"></div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Select Wealth Advisor *</label>
                    <select name="assigned_agent_id" id="modal_p_agent" class="adm-select" required>
                        <option value="">-- Remove Mandate (Unassigned) --</option>
                        <?php foreach ($assignable_agents as $ag): ?>
                            <option value="<?= $ag['id'] ?>"><?= e($ag['name']) ?> (<?= e($ag['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-navy">Save Mandate Assignment</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.btn-open-reassign').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('modal_p_id').value = this.dataset.id;
        document.getElementById('modal_p_title').textContent = this.dataset.title;
        document.getElementById('modal_p_agent').value = this.dataset.agent || '';
        document.getElementById('modalReassign').classList.add('show');
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

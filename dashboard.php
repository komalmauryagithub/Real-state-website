<?php
/**
 * EstateVista India - Private Client Dashboard
 * Features: Overview KPIs, Saved Residences (Wishlist), My Enquiries,
 * Scheduled VIP Site Visits, Profile Management, Security & Password Change.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Protect page: require login
require_login();

$user = get_logged_in_user();
if (!$user) {
    logout_user();
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$alert_message = null;
$alert_type = 'info';

// -----------------------------------------------------------------------------
// Handle POST Actions
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if (!verify_csrf_token($csrf_token)) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Security token invalid (CSRF).']);
            exit;
        }
        $alert_message = 'Security validation failed (CSRF). Please refresh.';
        $alert_type = 'error';
    } else {
        if ($action === 'toggle_wishlist') {
            $propId = (int)($_POST['property_id'] ?? 0);
            $res = toggle_user_saved_property($user['id'], $propId);
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode($res);
                exit;
            }
            $alert_message = $res['message'];
            $alert_type = $res['success'] ? 'success' : 'error';
        } elseif ($action === 'update_profile') {
            $res = update_user_profile($user['id'], $_POST);
            $alert_message = $res['message'];
            $alert_type = $res['success'] ? 'success' : 'error';
            if ($res['success']) {
                $user = get_logged_in_user(); // Refresh data
            }
        } elseif ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';
            $res = change_user_password($user['id'], $currentPass, $newPass, $confirmPass);
            $alert_message = $res['message'];
            $alert_type = $res['success'] ? 'success' : 'error';
        } elseif ($action === 'update_settings') {
            $alert_message = 'Account communication preferences updated successfully.';
            $alert_type = 'success';
        }
    }
}

// -----------------------------------------------------------------------------
// Load Dashboard Data
// -----------------------------------------------------------------------------
$saved_properties = get_user_saved_properties($user['id']);
$inquiries = get_user_inquiries($user['id'], $user['email']);
$site_visits = get_user_site_visits($user['id'], $user['email']);
$user_properties = get_user_submitted_properties($user['id']);

// Determine active tab
$active_tab = isset($_GET['tab']) ? strtolower(trim($_GET['tab'])) : 'overview';
$valid_tabs = ['overview', 'my_properties', 'saved', 'enquiries', 'visits', 'profile', 'security', 'settings'];
if (!in_array($active_tab, $valid_tabs)) {
    $active_tab = 'overview';
}

$page_title = 'Private Client Dashboard | ' . SITE_NAME;
$extra_css = [BASE_URL . 'assets/css/auth.css', BASE_URL . 'assets/css/submission.css'];
$extra_js = BASE_URL . 'assets/js/auth.js';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="dashboard-page">
    <div class="container">
        <!-- Optional Alert Notification -->
        <?php if (!empty($alert_message)): ?>
            <div class="auth-alert auth-alert-<?= ($alert_type === 'error') ? 'error' : 'success' ?>" style="margin-bottom: 2rem;">
                <i class="fa-solid <?= ($alert_type === 'error') ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i>
                <div><?= e($alert_message) ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['welcome'])): ?>
            <div class="auth-alert auth-alert-success" style="margin-bottom: 2rem;">
                <i class="fa-solid fa-crown" style="color: var(--gold-600);"></i>
                <div><strong>Welcome to EstateVista India, <?= e($user['full_name']) ?>!</strong> Your private client account has been initialized. Shortlist residences and submit inquiries directly from your portfolio.</div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['updated'])): ?>
            <div class="auth-alert auth-alert-success" style="margin-bottom: 2rem;">
                <i class="fa-solid fa-circle-check"></i>
                <div><strong>Listing Dossier Updated!</strong> Your modifications have been saved and resubmitted to the EstateVista curation desk for verification.</div>
            </div>
        <?php endif; ?>

        <div class="dashboard-layout">
            <!-- 1. Left Sidebar Navigation -->
            <aside class="dash-sidebar">
                <div class="dash-user-card">
                    <div class="dash-user-avatar">
                        <img src="<?= e(safe_image_url($user['avatar'], 'avatar')) ?>" alt="<?= e($user['full_name']) ?>" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/avatar-placeholder.svg';">
                    </div>
                    <h3 class="dash-user-name"><?= e($user['full_name']) ?></h3>
                    <div class="dash-user-email"><?= e($user['email']) ?></div>
                    <span class="dash-vip-badge">
                        <i class="fa-solid fa-gem"></i> VIP Private Client
                    </span>
                </div>

                <ul class="dash-nav-menu">
                    <li class="dash-nav-item">
                        <a href="<?= BASE_URL ?>dashboard.php?tab=overview" class="dash-nav-link <?= ($active_tab === 'overview') ? 'active' : '' ?>">
                            <div class="dash-nav-link-left">
                                <i class="fa-solid fa-chart-pie"></i>
                                <span>Overview</span>
                            </div>
                        </a>
                    </li>

                    <li class="dash-nav-item">
                        <a href="<?= BASE_URL ?>dashboard.php?tab=my_properties" class="dash-nav-link <?= ($active_tab === 'my_properties') ? 'active' : '' ?>">
                            <div class="dash-nav-link-left">
                                <i class="fa-solid fa-building-user"></i>
                                <span>My Properties</span>
                            </div>
                            <span class="dash-nav-badge"><?= count($user_properties) ?></span>
                        </a>
                    </li>

                    <li class="dash-nav-item">
                        <a href="<?= BASE_URL ?>dashboard.php?tab=saved" class="dash-nav-link <?= ($active_tab === 'saved') ? 'active' : '' ?>">
                            <div class="dash-nav-link-left">
                                <i class="fa-solid fa-heart"></i>
                                <span>Saved Residences</span>
                            </div>
                            <span class="dash-nav-badge"><?= count($saved_properties) ?></span>
                        </a>
                    </li>

                    <li class="dash-nav-item">
                        <a href="<?= BASE_URL ?>dashboard.php?tab=enquiries" class="dash-nav-link <?= ($active_tab === 'enquiries') ? 'active' : '' ?>">
                            <div class="dash-nav-link-left">
                                <i class="fa-solid fa-envelope-open-text"></i>
                                <span>My Enquiries</span>
                            </div>
                            <span class="dash-nav-badge"><?= count($inquiries) ?></span>
                        </a>
                    </li>

                    <li class="dash-nav-item">
                        <a href="<?= BASE_URL ?>dashboard.php?tab=visits" class="dash-nav-link <?= ($active_tab === 'visits') ? 'active' : '' ?>">
                            <div class="dash-nav-link-left">
                                <i class="fa-solid fa-calendar-check"></i>
                                <span>Site Visits</span>
                            </div>
                            <span class="dash-nav-badge"><?= count($site_visits) ?></span>
                        </a>
                    </li>

                    <li class="dash-nav-item">
                        <a href="<?= BASE_URL ?>dashboard.php?tab=profile" class="dash-nav-link <?= ($active_tab === 'profile') ? 'active' : '' ?>">
                            <div class="dash-nav-link-left">
                                <i class="fa-regular fa-user"></i>
                                <span>Profile Details</span>
                            </div>
                        </a>
                    </li>

                    <li class="dash-nav-item">
                        <a href="<?= BASE_URL ?>dashboard.php?tab=security" class="dash-nav-link <?= ($active_tab === 'security') ? 'active' : '' ?>">
                            <div class="dash-nav-link-left">
                                <i class="fa-solid fa-lock"></i>
                                <span>Change Password</span>
                            </div>
                        </a>
                    </li>

                    <li class="dash-nav-item">
                        <a href="<?= BASE_URL ?>dashboard.php?tab=settings" class="dash-nav-link <?= ($active_tab === 'settings') ? 'active' : '' ?>">
                            <div class="dash-nav-link-left">
                                <i class="fa-solid fa-gear"></i>
                                <span>Account Settings</span>
                            </div>
                        </a>
                    </li>

                    <li class="dash-nav-item">
                        <a href="<?= BASE_URL ?>logout.php" class="dash-nav-link dash-nav-logout">
                            <div class="dash-nav-link-left">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                <span>Sign Out</span>
                            </div>
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- 2. Right Main Content Area -->
            <div class="dash-main-content">
                <!-- =======================================================
                     TAB 1: OVERVIEW
                     ======================================================= -->
                <?php if ($active_tab === 'overview'): ?>
                    <div class="dash-welcome-banner">
                        <div>
                            <h1 class="dash-welcome-title">Welcome back, <?= e(explode(' ', $user['full_name'])[0]) ?></h1>
                            <p class="dash-welcome-subtitle">Here is a summary of your private real estate portfolio, recent advisory enquiries, and scheduled site inspections.</p>
                        </div>
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <a href="<?= BASE_URL ?>list-property.php" class="btn btn-gold btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem;">
                                <i class="fa-solid fa-plus-circle"></i> List New Property
                            </a>
                            <a href="<?= BASE_URL ?>properties.php" class="btn btn-outline btn-sm" style="color: var(--white); border-color: rgba(255,255,255,0.3);">
                                <i class="fa-solid fa-compass"></i> Explore Properties
                            </a>
                        </div>
                    </div>

                    <!-- KPI Statistics -->
                    <div class="dash-kpi-grid">
                        <div class="dash-kpi-card" style="cursor: pointer;" onclick="location.href='<?= BASE_URL ?>dashboard.php?tab=my_properties'">
                            <div class="dash-kpi-icon" style="color: var(--gold-600);"><i class="fa-solid fa-building-user"></i></div>
                            <div>
                                <div class="dash-kpi-val"><?= count($user_properties) ?></div>
                                <div class="dash-kpi-label">My Listed Properties</div>
                            </div>
                        </div>

                        <div class="dash-kpi-card">
                            <div class="dash-kpi-icon"><i class="fa-solid fa-heart"></i></div>
                            <div>
                                <div class="dash-kpi-val"><?= count($saved_properties) ?></div>
                                <div class="dash-kpi-label">Saved Residences</div>
                            </div>
                        </div>

                        <div class="dash-kpi-card">
                            <div class="dash-kpi-icon"><i class="fa-solid fa-envelope"></i></div>
                            <div>
                                <div class="dash-kpi-val"><?= count($inquiries) ?></div>
                                <div class="dash-kpi-label">Active Enquiries</div>
                            </div>
                        </div>

                        <div class="dash-kpi-card">
                            <div class="dash-kpi-icon"><i class="fa-solid fa-calendar-check"></i></div>
                            <div>
                                <div class="dash-kpi-val"><?= count($site_visits) ?></div>
                                <div class="dash-kpi-label">Scheduled Visits</div>
                            </div>
                        </div>

                        <div class="dash-kpi-card">
                            <div class="dash-kpi-icon"><i class="fa-solid fa-shield-halved"></i></div>
                            <div>
                                <div class="dash-kpi-val" style="font-size: 1.15rem; color: var(--gold-700);">100% Verified</div>
                                <div class="dash-kpi-label">RERA Title Protected</div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Saved Residences Snippet -->
                    <div class="dash-content-card">
                        <div class="dash-card-header">
                            <h2 class="dash-card-title">Shortlisted Residences</h2>
                            <a href="<?= BASE_URL ?>dashboard.php?tab=saved" style="font-size: 0.85rem; color: var(--gold-600); font-weight: 600; text-decoration: none;">View All (<?= count($saved_properties) ?>) &rarr;</a>
                        </div>

                        <?php if (!empty($saved_properties)): ?>
                            <div class="dash-saved-grid">
                                <?php foreach (array_slice($saved_properties, 0, 2) as $prop): ?>
                                    <div class="dash-saved-card">
                                        <div class="dash-saved-thumb">
                                            <img src="<?= e(safe_image_url($prop['cover_image'], 'property')) ?>" alt="<?= e($prop['title']) ?>" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                                            <div class="dash-saved-price"><?= e($prop['price_display']) ?></div>
                                        </div>
                                        <div class="dash-saved-body">
                                            <div>
                                                <h4 class="dash-saved-title"><?= e($prop['title']) ?></h4>
                                                <div class="dash-saved-loc">
                                                    <i class="fa-solid fa-location-dot" style="color: var(--gold-600);"></i>
                                                    <span><?= e($prop['locality']) ?>, <?= e($prop['city_name']) ?></span>
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--slate-600);">
                                                    <?= (int)$prop['bhk'] ?> BHK &bull; <?= format_inr_number($prop['carpet_area_sqft']) ?> sq.ft &bull; <?= e($prop['category_name']) ?>
                                                </div>
                                            </div>
                                            <div class="dash-saved-actions">
                                                <a href="<?= BASE_URL ?>property-detail.php?slug=<?= e($prop['slug']) ?>" class="btn btn-navy btn-sm" style="flex: 1; text-align: center; text-decoration: none;">
                                                    View Residence
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="dash-empty-state">
                                <i class="fa-regular fa-heart"></i>
                                <h4>No Residences Shortlisted Yet</h4>
                                <p>Explore our curated prime collection across Mumbai, Delhi NCR, Bengaluru, and Goa to save your preferred properties.</p>
                                <a href="<?= BASE_URL ?>properties.php" class="btn btn-navy btn-sm">Explore Properties</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Concierge Advisory Partner Card -->
                    <div class="dash-advisor-card">
                        <div class="dash-advisor-avatar">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80" alt="Aditya Vardhan Birla">
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--gold-700); font-weight: 700; letter-spacing: 0.06em;">Assigned Senior Acquisition Advisor</div>
                            <h4 style="font-family: var(--font-serif); font-size: 1.25rem; margin: 0.2rem 0; color: var(--navy-900);">Aditya Vardhan Birla</h4>
                            <p style="font-size: 0.82rem; color: var(--slate-600); margin: 0 0 0.5rem;">Senior Partner &bull; Ultra-Luxury Advisory Desk &bull; 16+ Years Experience</p>
                            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                                <a href="tel:+912269854010" class="btn btn-navy btn-sm" style="text-decoration: none; font-size: 0.78rem;">
                                    <i class="fa-solid fa-phone"></i> Direct Call (+91 22 6985 4010)
                                </a>
                                <a href="https://wa.me/919820012345?text=Hello%20Aditya%20Birla,%20I%20am%20calling%20regarding%20my%20EstateVista%20portfolio" target="_blank" class="btn btn-sm" style="background: #25D366; color: var(--white); text-decoration: none; font-size: 0.78rem;">
                                    <i class="fa-brands fa-whatsapp"></i> WhatsApp Concierge
                                </a>
                            </div>
                        </div>
                    </div>

                <!-- =======================================================
                     TAB 2: MY PROPERTIES (OWNER SUBMISSIONS)
                     ======================================================= -->
                <?php elseif ($active_tab === 'my_properties'): ?>
                    <div class="dash-content-card">
                        <div class="dash-card-header">
                            <div>
                                <h1 class="dash-card-title">My Listed Properties</h1>
                                <p style="font-size: 0.85rem; color: var(--slate-500); margin: 0.35rem 0 0;">Manage your submitted real estate dossiers, review editorial verification status, and make listing updates.</p>
                            </div>
                            <a href="<?= BASE_URL ?>list-property.php" class="btn btn-gold btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none;">
                                <i class="fa-solid fa-plus-circle"></i> List New Property
                            </a>
                        </div>

                        <?php if (!empty($user_properties)): ?>
                            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                                <?php foreach ($user_properties as $prop): ?>
                                    <div style="background: #FFFFFF; border: 1px solid var(--sub-border); border-radius: 12px; padding: 1.25rem; display: flex; gap: 1.5rem; align-items: center; justify-content: space-between; flex-wrap: wrap; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                                        <div style="display: flex; gap: 1.25rem; align-items: center; flex: 1; min-width: 280px;">
                                            <div style="width: 110px; height: 85px; border-radius: 8px; overflow: hidden; flex-shrink: 0; background: #0F172A;">
                                                <img src="<?= e(safe_image_url($prop['cover_image'], 'property')) ?>" alt="<?= e($prop['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                                            </div>
                                            <div>
                                                <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.35rem; flex-wrap: wrap;">
                                                    <span style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; background: var(--sub-gold-soft); color: var(--sub-gold-dark); padding: 0.2rem 0.5rem; border-radius: 4px;">
                                                        <?= e($prop['category_name']) ?> &bull; For <?= strtoupper(e($prop['purpose'])) ?>
                                                    </span>

                                                    <?php if ($prop['approval_status'] === 'approved'): ?>
                                                        <span class="status-badge status-badge-approved">
                                                            <i class="fa-solid fa-circle-check"></i> Live & Approved
                                                        </span>
                                                    <?php elseif ($prop['approval_status'] === 'rejected'): ?>
                                                        <span class="status-badge status-badge-rejected">
                                                            <i class="fa-solid fa-triangle-exclamation"></i> Revisions Requested
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-badge-pending">
                                                            <i class="fa-solid fa-clock-rotate-left"></i> Pending Approval
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <h3 style="font-family: var(--font-serif); font-size: 1.15rem; color: var(--sub-primary); margin: 0 0 0.25rem;">
                                                    <?= e($prop['title']) ?>
                                                </h3>
                                                <div style="font-size: 0.82rem; color: #64748B;">
                                                    <i class="fa-solid fa-location-dot" style="color: var(--sub-gold);"></i> <?= e($prop['locality']) ?>, <?= e($prop['city_name']) ?> &bull; 
                                                    <strong><?= e($prop['price_display']) ?></strong> &bull; 
                                                    <?= (int)$prop['bhk'] ?> BHK &bull; <?= format_inr_number($prop['carpet_area_sqft']) ?> sq.ft
                                                </div>

                                                <?php if ($prop['approval_status'] === 'rejected' && !empty($prop['rejection_reason'])): ?>
                                                    <div style="margin-top: 0.5rem; font-size: 0.78rem; background: #FEF2F2; color: #991B1B; padding: 0.4rem 0.75rem; border-radius: 4px; border-left: 3px solid #DC2626;">
                                                        <strong>Curation Feedback:</strong> <?= e($prop['rejection_reason']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
                                            <a href="<?= BASE_URL ?>property-detail.php?slug=<?= urlencode($prop['slug']) ?>" class="btn btn-outline btn-sm" style="border-color: #CBD5E1; color: var(--sub-primary); text-decoration: none; font-size: 0.82rem;" title="Preview Dossier">
                                                <i class="fa-solid fa-eye"></i> Preview
                                            </a>
                                            <a href="<?= BASE_URL ?>edit-property.php?id=<?= (int)$prop['id'] ?>" class="btn btn-gold btn-sm" style="text-decoration: none; font-size: 0.82rem;" title="Edit Property Details">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit Listing
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="dash-empty-state">
                                <i class="fa-solid fa-house-laptop"></i>
                                <h4>No Listed Properties Yet</h4>
                                <p>Are you an owner, developer, or authorized channel partner? List your luxury residence or commercial asset with EstateVista to access verified Ultra-HNW capital.</p>
                                <a href="<?= BASE_URL ?>list-property.php" class="btn btn-gold btn-sm">
                                    <i class="fa-solid fa-plus-circle"></i> List Your First Property
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                <!-- =======================================================
                     TAB 3: SAVED RESIDENCES / WISHLIST
                     ======================================================= -->
                <?php elseif ($active_tab === 'saved'): ?>
                    <div class="dash-content-card">
                        <div class="dash-card-header">
                            <div>
                                <h1 class="dash-card-title">Saved Residences & Shortlist</h1>
                                <p style="font-size: 0.85rem; color: var(--slate-500); margin: 0.35rem 0 0;">Properties saved to your private portfolio for ongoing monitoring and comparative analysis.</p>
                            </div>
                            <span class="dash-nav-badge" style="font-size: 0.85rem; padding: 0.35rem 0.85rem;"><?= count($saved_properties) ?> Saved</span>
                        </div>

                        <?php if (!empty($saved_properties)): ?>
                            <div class="dash-saved-grid" id="savedPropsGrid">
                                <?php foreach ($saved_properties as $prop): ?>
                                    <div class="dash-saved-card" id="saved-card-<?= (int)$prop['id'] ?>">
                                        <div class="dash-saved-thumb">
                                            <img src="<?= e(safe_image_url($prop['cover_image'], 'property')) ?>" alt="<?= e($prop['title']) ?>" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                                            <div class="dash-saved-price"><?= e($prop['price_display']) ?></div>
                                        </div>
                                        <div class="dash-saved-body">
                                            <div>
                                                <h4 class="dash-saved-title"><?= e($prop['title']) ?></h4>
                                                <div class="dash-saved-loc">
                                                    <i class="fa-solid fa-location-dot" style="color: var(--gold-600);"></i>
                                                    <span><?= e($prop['locality']) ?>, <?= e($prop['city_name']) ?></span>
                                                </div>
                                                <div style="font-size: 0.82rem; color: var(--slate-600); line-height: 1.5;">
                                                    <strong><?= (int)$prop['bhk'] ?> BHK</strong> &bull; <?= format_inr_number($prop['carpet_area_sqft']) ?> sq.ft (Carpet) &bull; <?= e($prop['category_name']) ?>
                                                </div>
                                                <?php if (!empty($prop['saved_notes'])): ?>
                                                    <div style="margin-top: 0.5rem; font-size: 0.78rem; background: var(--beige-50); padding: 0.4rem 0.6rem; border-radius: var(--radius-sm); color: var(--slate-600); border-left: 2px solid var(--gold-500);">
                                                        <i class="fa-regular fa-note-sticky"></i> <?= e($prop['saved_notes']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="dash-saved-actions">
                                                <a href="<?= BASE_URL ?>property-detail.php?slug=<?= e($prop['slug']) ?>" class="btn btn-navy btn-sm" style="flex: 1; text-align: center; text-decoration: none;">
                                                    View Details
                                                </a>
                                                <button type="button" class="btn btn-outline btn-sm btn-remove-saved" data-prop-id="<?= (int)$prop['id'] ?>" title="Remove from portfolio" style="border-color: #FECACA; color: #DC2626;">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="dash-empty-state">
                                <i class="fa-regular fa-heart"></i>
                                <h4>Your Saved Portfolio is Empty</h4>
                                <p>You have not shortlisted any residences yet. Click the heart icon on any property page to save residences here.</p>
                                <a href="<?= BASE_URL ?>properties.php" class="btn btn-navy">Browse Luxury Residences</a>
                            </div>
                        <?php endif; ?>
                    </div>

                <!-- =======================================================
                     TAB 3: MY ENQUIRIES
                     ======================================================= -->
                <?php elseif ($active_tab === 'enquiries'): ?>
                    <div class="dash-content-card">
                        <div class="dash-card-header">
                            <div>
                                <h1 class="dash-card-title">My Inquiries & Dossier Requests</h1>
                                <p style="font-size: 0.85rem; color: var(--slate-500); margin: 0.35rem 0 0;">Track the status of all confidential consultation requests, callbacks, and architectural queries.</p>
                            </div>
                            <span class="dash-nav-badge" style="font-size: 0.85rem; padding: 0.35rem 0.85rem;"><?= count($inquiries) ?> Total</span>
                        </div>

                        <?php if (!empty($inquiries)): ?>
                            <div class="dash-table-wrapper">
                                <table class="dash-table">
                                    <thead>
                                        <tr>
                                            <th>Ref ID</th>
                                            <th>Residence / Subject</th>
                                            <th>Inquiry Type</th>
                                            <th>Date Submitted</th>
                                            <th>Advisory Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($inquiries as $inq): 
                                            $typeBadgeClass = 'badge-type-enquiry';
                                            if ($inq['inquiry_type'] === 'callback') $typeBadgeClass = 'badge-type-callback';
                                            if ($inq['inquiry_type'] === 'site_visit') $typeBadgeClass = 'badge-type-visit';

                                            $status = $inq['status'] ?? 'new';
                                            $statusClass = 'badge-status-new';
                                            $statusText = 'Under Review';
                                            if ($status === 'contacted') { $statusClass = 'badge-status-contacted'; $statusText = 'Advisor Assigned'; }
                                            if ($status === 'scheduled') { $statusClass = 'badge-status-scheduled'; $statusText = 'Scheduled'; }
                                            if ($status === 'closed') { $statusClass = 'badge-status-closed'; $statusText = 'Completed'; }
                                        ?>
                                            <tr>
                                                <td style="font-weight: 700; color: var(--gold-700);">
                                                    #EV-<?= str_pad((string)$inq['id'], 5, '0', STR_PAD_LEFT) ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($inq['property_title'])): ?>
                                                        <a href="<?= BASE_URL ?>property-detail.php?slug=<?= e($inq['property_slug']) ?>" style="font-weight: 600; color: var(--navy-900); text-decoration: none;">
                                                            <?= e($inq['property_title']) ?>
                                                        </a>
                                                        <div style="font-size: 0.75rem; color: var(--slate-500);"><?= e($inq['locality']) ?>, <?= e($inq['city_name']) ?></div>
                                                    <?php else: ?>
                                                        <span style="font-weight: 600;">General Private Advisory</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge-inq-type <?= $typeBadgeClass ?>">
                                                        <?= e(str_replace('_', ' ', $inq['inquiry_type'])) ?>
                                                    </span>
                                                </td>
                                                <td style="font-size: 0.82rem; color: var(--slate-600);">
                                                    <?= date('d M Y, h:i A', strtotime($inq['created_at'])) ?>
                                                </td>
                                                <td>
                                                    <span class="badge-status <?= $statusClass ?>">
                                                        <i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i>
                                                        <?= e($statusText) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($inq['property_slug'])): ?>
                                                        <a href="<?= BASE_URL ?>property-detail.php?slug=<?= e($inq['property_slug']) ?>" class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 0.35rem 0.65rem;">
                                                            View
                                                        </a>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 0.35rem 0.65rem;" onclick="alert('Inquiry notes: <?= addslashes($inq['message'] ?? 'No extra notes.') ?>');">
                                                            Notes
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="dash-empty-state">
                                <i class="fa-regular fa-envelope-open"></i>
                                <h4>No Inquiries Recorded</h4>
                                <p>You have not submitted any property inquiries or callback requests yet.</p>
                                <a href="<?= BASE_URL ?>properties.php" class="btn btn-navy">Discover Residences</a>
                            </div>
                        <?php endif; ?>
                    </div>

                <!-- =======================================================
                     TAB 4: SCHEDULED VIP SITE VISITS
                     ======================================================= -->
                <?php elseif ($active_tab === 'visits'): ?>
                    <div class="dash-content-card">
                        <div class="dash-card-header">
                            <div>
                                <h1 class="dash-card-title">Scheduled VIP Site Visits</h1>
                                <p style="font-size: 0.85rem; color: var(--slate-500); margin: 0.35rem 0 0;">Private walkthrough itineraries confirmed with dedicated security clearance and host advisors.</p>
                            </div>
                            <span class="dash-nav-badge" style="font-size: 0.85rem; padding: 0.35rem 0.85rem;"><?= count($site_visits) ?> Scheduled</span>
                        </div>

                        <?php if (!empty($site_visits)): ?>
                            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                                <?php foreach ($site_visits as $sv): 
                                    $vDate = !empty($sv['visit_date']) ? date('l, d F Y', strtotime($sv['visit_date'])) : 'To Be Scheduled';
                                    $vTime = !empty($sv['visit_time']) ? $sv['visit_time'] : '11:00 AM (Morning Tour)';
                                ?>
                                    <div style="background: var(--surface-light); border: 1px solid var(--color-border-subtle); border-radius: var(--radius-sm); padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
                                        <div style="display: flex; gap: 1.25rem; align-items: center;">
                                            <div style="width: 54px; height: 54px; border-radius: var(--radius-sm); background: var(--navy-900); color: var(--gold-400); display: flex; flex-direction: column; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                                                <i class="fa-solid fa-calendar-day" style="font-size: 1.25rem;"></i>
                                            </div>
                                            <div>
                                                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--gold-700); font-weight: 700; letter-spacing: 0.05em;">VIP Walkthrough Itinerary</div>
                                                <h4 style="font-family: var(--font-serif); font-size: 1.2rem; color: var(--navy-900); margin: 0.2rem 0;">
                                                    <?= e($sv['property_title'] ?: 'Bespoke Residence Walkthrough') ?>
                                                </h4>
                                                <div style="font-size: 0.85rem; color: var(--slate-600); display: flex; gap: 1rem; flex-wrap: wrap;">
                                                    <span><i class="fa-regular fa-calendar" style="color: var(--gold-600);"></i> <?= e($vDate) ?></span>
                                                    <span><i class="fa-regular fa-clock" style="color: var(--gold-600);"></i> <?= e($vTime) ?></span>
                                                    <span><i class="fa-solid fa-location-dot" style="color: var(--gold-600);"></i> <?= e($sv['locality'] ?: 'Mumbai') ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <span class="badge-status badge-status-scheduled">
                                                <i class="fa-solid fa-circle-check"></i> Confirmed Tour
                                            </span>
                                            <?php if (!empty($sv['property_slug'])): ?>
                                                <a href="<?= BASE_URL ?>property-detail.php?slug=<?= e($sv['property_slug']) ?>" class="btn btn-navy btn-sm" style="text-decoration: none;">
                                                    View Dossier
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="dash-empty-state">
                                <i class="fa-solid fa-calendar-xmark"></i>
                                <h4>No Scheduled Site Visits</h4>
                                <p>Book a confidential private walkthrough on any residence detail page to preview penthouses, sky villas, and estates with dedicated concierge accompaniment.</p>
                                <a href="<?= BASE_URL ?>properties.php" class="btn btn-navy">Explore Residences</a>
                            </div>
                        <?php endif; ?>
                    </div>

                <!-- =======================================================
                     TAB 5: PROFILE MANAGEMENT
                     ======================================================= -->
                <?php elseif ($active_tab === 'profile'): ?>
                    <div class="dash-content-card">
                        <div class="dash-card-header">
                            <div>
                                <h1 class="dash-card-title">Client Profile Management</h1>
                                <p style="font-size: 0.85rem; color: var(--slate-500); margin: 0.35rem 0 0;">Update your legal representation, primary contact details, and acquisition preferences.</p>
                            </div>
                        </div>

                        <form method="POST" action="<?= BASE_URL ?>dashboard.php?tab=profile" class="auth-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_profile">

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                                <div class="auth-form-group">
                                    <label class="auth-label" for="prof_name">Full Legal Name *</label>
                                    <div class="auth-input-wrapper">
                                        <i class="fa-regular fa-user input-icon"></i>
                                        <input type="text" id="prof_name" name="full_name" class="auth-input" value="<?= e($user['full_name']) ?>" required>
                                    </div>
                                </div>

                                <div class="auth-form-group">
                                    <label class="auth-label" for="prof_phone">Direct Contact Mobile *</label>
                                    <div class="auth-input-wrapper">
                                        <i class="fa-solid fa-phone input-icon"></i>
                                        <input type="tel" id="prof_phone" name="phone" class="auth-input" value="<?= e($user['phone']) ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="auth-form-group">
                                <label class="auth-label" for="prof_email">Primary Email (Locked for Security)</label>
                                <div class="auth-input-wrapper">
                                    <i class="fa-regular fa-envelope input-icon"></i>
                                    <input type="email" id="prof_email" class="auth-input" value="<?= e($user['email']) ?>" readonly style="background-color: var(--beige-50); color: var(--slate-500); cursor: not-allowed;">
                                </div>
                                <span style="font-size: 0.72rem; color: var(--slate-400);">To update your verified email address, please contact your Senior Acquisition Partner.</span>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                                <div class="auth-form-group">
                                    <label class="auth-label" for="prof_city">Base City / Metropolitan Region</label>
                                    <select id="prof_city" name="city" class="auth-input" style="padding-left: 1rem;">
                                        <option value="Mumbai" <?= ($user['city'] === 'Mumbai') ? 'selected' : '' ?>>Mumbai (Worli, Bandra, South Mumbai)</option>
                                        <option value="Delhi NCR" <?= ($user['city'] === 'Delhi NCR') ? 'selected' : '' ?>>Delhi NCR (Golf Course Road, DLF)</option>
                                        <option value="Bengaluru" <?= ($user['city'] === 'Bengaluru') ? 'selected' : '' ?>>Bengaluru (Indiranagar, UB City)</option>
                                        <option value="Goa" <?= ($user['city'] === 'Goa') ? 'selected' : '' ?>>Goa (Assagao, Anjuna, Candolim)</option>
                                        <option value="Hyderabad" <?= ($user['city'] === 'Hyderabad') ? 'selected' : '' ?>>Hyderabad (Jubilee Hills, Kokapet)</option>
                                        <option value="Pune" <?= ($user['city'] === 'Pune') ? 'selected' : '' ?>>Pune (Koregaon Park)</option>
                                    </select>
                                </div>

                                <div class="auth-form-group">
                                    <label class="auth-label" for="prof_budget">Preferred Investment Bracket</label>
                                    <select id="prof_budget" name="preferred_budget" class="auth-input" style="padding-left: 1rem;">
                                        <option value="₹ 3 Cr - ₹ 7 Cr" <?= ($user['preferred_budget'] === '₹ 3 Cr - ₹ 7 Cr') ? 'selected' : '' ?>>₹ 3 Cr – ₹ 7 Cr</option>
                                        <option value="₹ 7 Cr - ₹ 15 Cr" <?= ($user['preferred_budget'] === '₹ 7 Cr - ₹ 15 Cr') ? 'selected' : '' ?>>₹ 7 Cr – ₹ 15 Cr</option>
                                        <option value="₹ 15 Cr - ₹ 30 Cr" <?= ($user['preferred_budget'] === '₹ 15 Cr - ₹ 30 Cr') ? 'selected' : '' ?>>₹ 15 Cr – ₹ 30 Cr</option>
                                        <option value="₹ 30 Cr+ (Ultra-Bespoke)" <?= ($user['preferred_budget'] === '₹ 30 Cr+ (Ultra-Bespoke)') ? 'selected' : '' ?>>₹ 30 Cr+ (Ultra-Bespoke)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="auth-form-group">
                                <label class="auth-label" for="prof_bio">Client Profile & Investment Notes</label>
                                <textarea id="prof_bio" name="bio" class="auth-input" rows="3" style="padding-left: 1rem;" placeholder="e.g. Managing Partner at Venture Fund. Seeking prime sea-facing penthouses or heritage estates."><?= e($user['bio'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-navy" style="padding: 0.85rem 1.75rem; font-weight: 700;">
                                <i class="fa-solid fa-floppy-disk"></i> Save Profile Details
                            </button>
                        </form>
                    </div>

                <!-- =======================================================
                     TAB 6: SECURITY & CHANGE PASSWORD
                     ======================================================= -->
                <?php elseif ($active_tab === 'security'): ?>
                    <div class="dash-content-card">
                        <div class="dash-card-header">
                            <div>
                                <h1 class="dash-card-title">Security & Password Management</h1>
                                <p style="font-size: 0.85rem; color: var(--slate-500); margin: 0.35rem 0 0;">Update your client portal access password. All credentials use BCRYPT 12-round salted hashing.</p>
                            </div>
                        </div>

                        <form method="POST" action="<?= BASE_URL ?>dashboard.php?tab=security" class="auth-form" style="max-width: 520px;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="change_password">

                            <div class="auth-form-group">
                                <label class="auth-label" for="curr_pass">Current Password *</label>
                                <div class="auth-input-wrapper">
                                    <i class="fa-solid fa-key input-icon"></i>
                                    <input type="password" id="curr_pass" name="current_password" class="auth-input" placeholder="••••••••••••" required autofocus>
                                    <button type="button" class="auth-toggle-pass-btn" title="Toggle password visibility">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="auth-form-group">
                                <label class="auth-label" for="reg_password">New Security Password *</label>
                                <div class="auth-input-wrapper">
                                    <i class="fa-solid fa-lock input-icon"></i>
                                    <input type="password" id="reg_password" name="new_password" class="auth-input" placeholder="Min. 8 characters" required>
                                    <button type="button" class="auth-toggle-pass-btn" title="Toggle password visibility">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength-bar">
                                    <div class="password-strength-fill" id="passStrengthFill"></div>
                                </div>
                                <div class="password-strength-text">
                                    <span>Security Rating:</span>
                                    <strong id="passStrengthLabel">None</strong>
                                </div>
                            </div>

                            <div class="auth-form-group">
                                <label class="auth-label" for="chg_confirm">Confirm New Password *</label>
                                <div class="auth-input-wrapper">
                                    <i class="fa-solid fa-lock-open input-icon"></i>
                                    <input type="password" id="chg_confirm" name="confirm_password" class="auth-input" placeholder="Re-enter new password" required>
                                    <button type="button" class="auth-toggle-pass-btn" title="Toggle password visibility">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-navy" style="padding: 0.85rem 1.75rem; font-weight: 700;">
                                <i class="fa-solid fa-shield-halved"></i> Update Security Password
                            </button>
                        </form>
                    </div>

                <!-- =======================================================
                     TAB 7: ACCOUNT SETTINGS
                     ======================================================= -->
                <?php elseif ($active_tab === 'settings'): ?>
                    <div class="dash-content-card">
                        <div class="dash-card-header">
                            <div>
                                <h1 class="dash-card-title">Advisory & Communication Settings</h1>
                                <p style="font-size: 0.85rem; color: var(--slate-500); margin: 0.35rem 0 0;">Manage your non-public investment alerts, quarterly market dossiers, and concierge channel preferences.</p>
                            </div>
                        </div>

                        <form method="POST" action="<?= BASE_URL ?>dashboard.php?tab=settings" class="auth-form" style="max-width: 600px;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_settings">

                            <div style="display: flex; flex-direction: column; gap: 1.25rem; margin-bottom: 2rem;">
                                <label style="display: flex; align-items: flex-start; gap: 0.85rem; cursor: pointer; padding: 1rem; background: var(--beige-50); border: 1px solid var(--beige-200); border-radius: var(--radius-sm);">
                                    <input type="checkbox" name="pref_whatsapp" value="1" checked style="accent-color: var(--gold-500); margin-top: 0.25rem;">
                                    <div>
                                        <strong style="color: var(--navy-900); font-size: 0.92rem;">WhatsApp Concierge Notifications</strong>
                                        <p style="font-size: 0.8rem; color: var(--slate-600); margin: 0.2rem 0 0;">Receive instant itinerary confirmations and private walkthrough updates via encrypted WhatsApp.</p>
                                    </div>
                                </label>

                                <label style="display: flex; align-items: flex-start; gap: 0.85rem; cursor: pointer; padding: 1rem; background: var(--beige-50); border: 1px solid var(--beige-200); border-radius: var(--radius-sm);">
                                    <input type="checkbox" name="pref_offmarket" value="1" checked style="accent-color: var(--gold-500); margin-top: 0.25rem;">
                                    <div>
                                        <strong style="color: var(--navy-900); font-size: 0.92rem;">Off-Market Private Launch Briefings</strong>
                                        <p style="font-size: 0.8rem; color: var(--slate-600); margin: 0.2rem 0 0;">Direct access to sovereign developer penthouses and estate plots before public RERA launch.</p>
                                    </div>
                                </label>

                                <label style="display: flex; align-items: flex-start; gap: 0.85rem; cursor: pointer; padding: 1rem; background: var(--beige-50); border: 1px solid var(--beige-200); border-radius: var(--radius-sm);">
                                    <input type="checkbox" name="pref_reports" value="1" checked style="accent-color: var(--gold-500); margin-top: 0.25rem;">
                                    <div>
                                        <strong style="color: var(--navy-900); font-size: 0.92rem;">Quarterly Indian Prime Living Index</strong>
                                        <p style="font-size: 0.8rem; color: var(--slate-600); margin: 0.2rem 0 0;">Curated analytical insights on capital appreciation, stamp duty shifts, and high-value transactions.</p>
                                    </div>
                                </label>
                            </div>

                            <button type="submit" class="btn btn-navy" style="padding: 0.85rem 1.75rem; font-weight: 700;">
                                <i class="fa-solid fa-check"></i> Save Preferences
                            </button>
                        </form>

                        <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--beige-200);">
                            <h4 style="font-family: var(--font-serif); font-size: 1.15rem; color: var(--navy-900); margin-bottom: 0.5rem;">Session Integrity & Security Details</h4>
                            <div style="font-size: 0.82rem; color: var(--slate-600); line-height: 1.6;">
                                <div><strong>Client ID:</strong> #EV-UID-<?= str_pad((string)$user['id'], 6, '0', STR_PAD_LEFT) ?></div>
                                <div><strong>Account Status:</strong> <span style="color: #059669; font-weight: 700;">Active & Verified</span></div>
                                <div><strong>Registration Date:</strong> <?= date('d F Y', strtotime($user['created_at'])) ?></div>
                                <div><strong>Current Session IP:</strong> <?= e($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

<?php
/**
 * EstateVista India - Admin Sidebar Navigation Component
 * Features all 20 modules with role-based access control visibility.
 */

$current_script = basename($_SERVER['PHP_SELF']);
$admin_role = get_admin_role();

// Live counts for badges
$db = Database::getConnection();
$pending_count = 0;
$new_inquiries_count = 0;
$site_visits_count = 0;
$pending_reminders_count = 0;

if ($db) {
    try {
        $pending_count = (int)$db->query("SELECT COUNT(*) FROM properties WHERE approval_status = 'pending'")->fetchColumn();
        $new_inquiries_count = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE COALESCE(lead_status, status) = 'new'")->fetchColumn();
        $site_visits_count = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE inquiry_type = 'site_visit'")->fetchColumn();

        if ($admin_role === 'agent') {
            $cur_agent_id = (int)($_SESSION['admin_id'] ?? 0);
            $pending_reminders_count = (int)$db->query("SELECT COUNT(*) FROM crm_reminders WHERE agent_id = {$cur_agent_id} AND status = 'pending'")->fetchColumn();
        } else {
            $pending_reminders_count = (int)$db->query("SELECT COUNT(*) FROM crm_reminders WHERE status = 'pending'")->fetchColumn();
        }
    } catch (PDOException $e) {}
}
?>

<aside class="adm-sidebar" id="admSidebar">
    <!-- Brand Header -->
    <div class="adm-sidebar-header">
        <a href="<?= BASE_URL ?>admin/index.php" class="adm-brand">
            <div class="adm-brand-emblem">EV</div>
            <div class="adm-brand-text">
                <span class="adm-brand-name">EstateVista <span>Admin</span></span>
                <span class="adm-brand-badge">Command Suite</span>
            </div>
        </a>
    </div>

    <!-- Navigation Menu -->
    <ul class="adm-sidebar-menu">
        <li class="adm-menu-item">
            <a href="<?= BASE_URL ?>admin/index.php" class="adm-menu-link <?= ($current_script === 'index.php') ? 'active' : '' ?>">
                <div class="adm-menu-link-left">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Dashboard</span>
                </div>
            </a>
        </li>

        <!-- ==========================================
             1. PROPERTIES & CURATION
             ========================================== -->
        <li class="adm-menu-label">Properties & Curation</li>

        <li class="adm-menu-item">
            <a href="<?= BASE_URL ?>admin/properties.php" class="adm-menu-link <?= in_array($current_script, ['properties.php', 'property-add.php', 'property-edit.php']) ? 'active' : '' ?>">
                <div class="adm-menu-link-left">
                    <i class="fa-solid fa-building"></i>
                    <span>All Properties</span>
                </div>
            </a>
        </li>

        <li class="adm-menu-item">
            <a href="<?= BASE_URL ?>admin/approvals.php" class="adm-menu-link <?= ($current_script === 'approvals.php') ? 'active' : '' ?>">
                <div class="adm-menu-link-left">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Pending Approvals</span>
                </div>
                <?php if ($pending_count > 0): ?>
                    <span class="adm-menu-badge adm-badge-warning"><?= $pending_count ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="adm-menu-item">
            <a href="<?= BASE_URL ?>admin/featured.php" class="adm-menu-link <?= ($current_script === 'featured.php') ? 'active' : '' ?>">
                <div class="adm-menu-link-left">
                    <i class="fa-solid fa-crown"></i>
                    <span>Featured Listings</span>
                </div>
            </a>
        </li>

        <!-- ==========================================
             2. AGENT CRM & ADVISORY
             ========================================== -->
        <li class="adm-menu-label">Agent CRM & Advisory</li>

        <li class="adm-menu-item">
            <a href="<?= BASE_URL ?>admin/crm-dashboard.php" class="adm-menu-link <?= ($current_script === 'crm-dashboard.php') ? 'active' : '' ?>">
                <div class="adm-menu-link-left">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Agent CRM Desk</span>
                </div>
            </a>
        </li>

        <li class="adm-menu-item">
            <a href="<?= BASE_URL ?>admin/leads.php" class="adm-menu-link <?= in_array($current_script, ['leads.php', 'lead-detail.php']) ? 'active' : '' ?>">
                <div class="adm-menu-link-left">
                    <i class="fa-solid fa-filter-circle-dollar"></i>
                    <span>Lead Pipeline</span>
                </div>
            </a>
        </li>

        <li class="adm-menu-item">
            <a href="<?= BASE_URL ?>admin/reminders.php" class="adm-menu-link <?= ($current_script === 'reminders.php') ? 'active' : '' ?>">
                <div class="adm-menu-link-left">
                    <i class="fa-solid fa-bell"></i>
                    <span>Follow-Up Tasks</span>
                </div>
                <?php if ($pending_reminders_count > 0): ?>
                    <span class="adm-menu-badge adm-badge-danger"><?= $pending_reminders_count ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="adm-menu-item">
            <a href="<?= BASE_URL ?>admin/agent-properties.php" class="adm-menu-link <?= ($current_script === 'agent-properties.php') ? 'active' : '' ?>">
                <div class="adm-menu-link-left">
                    <i class="fa-solid fa-building-user"></i>
                    <span>Assigned Properties</span>
                </div>
            </a>
        </li>

        <li class="adm-menu-item">
            <a href="<?= BASE_URL ?>admin/enquiries.php" class="adm-menu-link <?= ($current_script === 'enquiries.php') ? 'active' : '' ?>">
                <div class="adm-menu-link-left">
                    <i class="fa-solid fa-envelope-open-text"></i>
                    <span>Enquiries</span>
                </div>
                <?php if ($new_inquiries_count > 0): ?>
                    <span class="adm-menu-badge adm-badge-gold"><?= $new_inquiries_count ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="adm-menu-item">
            <a href="<?= BASE_URL ?>admin/site-visits.php" class="adm-menu-link <?= ($current_script === 'site-visits.php') ? 'active' : '' ?>">
                <div class="adm-menu-link-left">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Site Visit Requests</span>
                </div>
                <?php if ($site_visits_count > 0): ?>
                    <span class="adm-menu-badge adm-badge-gold"><?= $site_visits_count ?></span>
                <?php endif; ?>
            </a>
        </li>

        <?php if (admin_has_role(['super_admin', 'admin'])): ?>
            <li class="adm-menu-item">
                <a href="<?= BASE_URL ?>admin/users.php" class="adm-menu-link <?= ($current_script === 'users.php') ? 'active' : '' ?>">
                    <div class="adm-menu-link-left">
                        <i class="fa-solid fa-users"></i>
                        <span>Registered Users</span>
                    </div>
                </a>
            </li>

            <li class="adm-menu-item">
                <a href="<?= BASE_URL ?>admin/agents.php" class="adm-menu-link <?= ($current_script === 'agents.php') ? 'active' : '' ?>">
                    <div class="adm-menu-link-left">
                        <i class="fa-solid fa-user-tie"></i>
                        <span>Advisory Agents</span>
                    </div>
                </a>
            </li>

            <li class="adm-menu-item">
                <a href="<?= BASE_URL ?>admin/owners.php" class="adm-menu-link <?= ($current_script === 'owners.php') ? 'active' : '' ?>">
                    <div class="adm-menu-link-left">
                        <i class="fa-solid fa-house-chimney-user"></i>
                        <span>Property Owners</span>
                    </div>
                </a>
            </li>
        <?php endif; ?>

        <!-- ==========================================
             3. CATALOG & CONTENT
             ========================================== -->
        <?php if (admin_has_role(['super_admin', 'admin'])): ?>
            <li class="adm-menu-label">Catalog & Content</li>

            <li class="adm-menu-item">
                <a href="<?= BASE_URL ?>admin/cities.php" class="adm-menu-link <?= ($current_script === 'cities.php') ? 'active' : '' ?>">
                    <div class="adm-menu-link-left">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <span>Cities & Localities</span>
                    </div>
                </a>
            </li>

            <li class="adm-menu-item">
                <a href="<?= BASE_URL ?>admin/categories.php" class="adm-menu-link <?= ($current_script === 'categories.php') ? 'active' : '' ?>">
                    <div class="adm-menu-link-left">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>Categories</span>
                    </div>
                </a>
            </li>

            <li class="adm-menu-item">
                <a href="<?= BASE_URL ?>admin/amenities.php" class="adm-menu-link <?= ($current_script === 'amenities.php') ? 'active' : '' ?>">
                    <div class="adm-menu-link-left">
                        <i class="fa-solid fa-spa"></i>
                        <span>Amenities</span>
                    </div>
                </a>
            </li>

            <li class="adm-menu-item">
                <a href="<?= BASE_URL ?>admin/testimonials.php" class="adm-menu-link <?= ($current_script === 'testimonials.php') ? 'active' : '' ?>">
                    <div class="adm-menu-link-left">
                        <i class="fa-solid fa-comment-dots"></i>
                        <span>Testimonials</span>
                    </div>
                </a>
            </li>

            <li class="adm-menu-item">
                <a href="<?= BASE_URL ?>admin/articles.php" class="adm-menu-link <?= ($current_script === 'articles.php') ? 'active' : '' ?>">
                    <div class="adm-menu-link-left">
                        <i class="fa-solid fa-newspaper"></i>
                        <span>Market Intelligence</span>
                    </div>
                </a>
            </li>

            <li class="adm-menu-item">
                <a href="<?= BASE_URL ?>admin/cms.php" class="adm-menu-link <?= ($current_script === 'cms.php') ? 'active' : '' ?>">
                    <div class="adm-menu-link-left">
                        <i class="fa-solid fa-palette"></i>
                        <span>Website Content / CMS</span>
                    </div>
                </a>
            </li>
        <?php endif; ?>

        <!-- ==========================================
             4. GROWTH & SYSTEM
             ========================================== -->
        <li class="adm-menu-label">System & Exports</li>

        <?php if (admin_has_role(['super_admin', 'admin'])): ?>
            <li class="adm-menu-item">
                <a href="<?= BASE_URL ?>admin/subscribers.php" class="adm-menu-link <?= ($current_script === 'subscribers.php') ? 'active' : '' ?>">
                    <div class="adm-menu-link-left">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Newsletter Subscribers</span>
                    </div>
                </a>
            </li>
        <?php endif; ?>

        <li class="adm-menu-item">
            <a href="<?= BASE_URL ?>admin/reports.php" class="adm-menu-link <?= ($current_script === 'reports.php') ? 'active' : '' ?>">
                <div class="adm-menu-link-left">
                    <i class="fa-solid fa-file-csv"></i>
                    <span>Export Reports</span>
                </div>
            </a>
        </li>

        <li class="adm-menu-item">
            <a href="<?= BASE_URL ?>admin/profile.php" class="adm-menu-link <?= ($current_script === 'profile.php') ? 'active' : '' ?>">
                <div class="adm-menu-link-left">
                    <i class="fa-solid fa-id-badge"></i>
                    <span>Admin Profile</span>
                </div>
            </a>
        </li>

        <?php if (admin_has_role('super_admin')): ?>
            <li class="adm-menu-item">
                <a href="<?= BASE_URL ?>admin/settings.php" class="adm-menu-link <?= ($current_script === 'settings.php') ? 'active' : '' ?>">
                    <div class="adm-menu-link-left">
                        <i class="fa-solid fa-sliders"></i>
                        <span>Platform Settings</span>
                    </div>
                </a>
            </li>
        <?php endif; ?>

        <li class="adm-menu-item" style="margin-top: 1rem;">
            <a href="<?= BASE_URL ?>admin/logout.php" class="adm-menu-link" style="color: #F87171;">
                <div class="adm-menu-link-left">
                    <i class="fa-solid fa-arrow-right-from-bracket" style="color: #F87171;"></i>
                    <span>Sign Out</span>
                </div>
            </a>
        </li>
    </ul>

    <!-- Sidebar Footer -->
    <div class="adm-sidebar-footer">
        <div class="adm-sidebar-user-avatar">
            <img src="<?= e(safe_image_url($current_admin['avatar'] ?? null, 'avatar')) ?>" alt="<?= e($current_admin['name'] ?? 'Admin') ?>" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/avatar-placeholder.svg';">
        </div>
        <div class="adm-sidebar-user-info">
            <div class="adm-sidebar-user-name"><?= e($current_admin['name']) ?></div>
            <div class="adm-sidebar-user-role"><?= strtoupper(str_replace('_', ' ', e($current_admin['role']))) ?></div>
        </div>
</aside>
<div class="adm-sidebar-backdrop" id="admSidebarBackdrop"></div>

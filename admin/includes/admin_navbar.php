<?php
/**
 * EstateVista India - Admin Top Navbar Component
 */
$flash_success = $_SESSION['admin_flash_success'] ?? null;
$flash_error = $_SESSION['admin_flash_error'] ?? null;
unset($_SESSION['admin_flash_success'], $_SESSION['admin_flash_error']);

$role_badges = [
    'super_admin' => ['label' => 'Super Admin', 'color' => '#C5A880', 'bg' => 'rgba(197, 168, 128, 0.15)'],
    'admin' => ['label' => 'Operations Admin', 'color' => '#3B82F6', 'bg' => 'rgba(59, 130, 246, 0.15)'],
    'agent' => ['label' => 'Advisory Agent', 'color' => '#10B981', 'bg' => 'rgba(16, 185, 129, 0.15)']
];
$current_role_info = $role_badges[$current_admin['role']] ?? ['label' => 'Admin', 'color' => '#64748B', 'bg' => '#F1F5F9'];
?>

<div class="adm-wrapper">
    <header class="adm-header">
        <div class="adm-header-left">
            <button type="button" class="adm-toggle-btn" id="admSidebarToggle" aria-label="Toggle menu" title="Toggle Sidebar Menu">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <h1 class="adm-header-title">
                <?= e($admin_title) ?>
            </h1>
        </div>

        <div class="adm-header-right">
            <!-- Front Website Link -->
            <a href="<?= BASE_URL ?>" target="_blank" class="adm-front-link" title="Open EstateVista Public Portal">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>Visit Portal</span>
            </a>

            <!-- Quick Action Dropdown -->
            <a href="<?= BASE_URL ?>admin/property-add.php" class="adm-btn adm-btn-sm adm-btn-gold" style="text-decoration: none;">
                <i class="fa-solid fa-plus"></i>
                <span>Add Property</span>
            </a>

            <!-- Admin Role Pill -->
            <div class="adm-role-badge" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 0.35rem 0.75rem; border-radius: 20px; color: <?= $current_role_info['color'] ?>; background: <?= $current_role_info['bg'] ?>; border: 1px solid <?= $current_role_info['color'] ?>;">
                <i class="fa-solid fa-shield-halved"></i> <?= e($current_role_info['label']) ?>
            </div>

            <!-- Profile Shortcut -->
            <a href="<?= BASE_URL ?>admin/profile.php" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: var(--adm-navy);" title="My Profile">
                <img src="<?= e(safe_image_url($current_admin['avatar'] ?? null, 'avatar')) ?>" alt="<?= e($current_admin['name'] ?? 'Admin') ?>" style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1px solid var(--adm-border);" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/avatar-placeholder.svg';">
            </a>
        </div>
    </header>

    <!-- Main Content Body -->
    <main class="adm-content">
        <!-- Flash Messages -->
        <?php if (!empty($flash_success)): ?>
            <div style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                <i class="fa-solid fa-circle-check" style="color: #10B981; font-size: 1.2rem;"></i>
                <div><?= e($flash_success) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($flash_error)): ?>
            <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                <i class="fa-solid fa-circle-exclamation" style="color: #EF4444; font-size: 1.2rem;"></i>
                <div><?= e($flash_error) ?></div>
            </div>
        <?php endif; ?>

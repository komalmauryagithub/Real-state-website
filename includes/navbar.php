<?php
/**
 * Navigation Bar Component
 * EstateVista India
 * 
 * Features:
 * - Top Utility Bar with RERA certification, Phone, WhatsApp
 * - Sticky Navbar with Logo, Buy, Rent, Commercial, Projects, About, Contact, Login, and List Property CTA
 * - Mobile Navigation Drawer
 */

$nav_script_name = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>

<header class="site-header" id="siteHeader">
    <!-- Top Utility Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-inner">
                <div class="top-bar-rera">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span><?= e(RERA_REGISTRATION) ?></span>
                </div>
                <div class="top-bar-actions">
                    <a href="tel:<?= str_replace(' ', '', SITE_PHONE) ?>" class="top-bar-link">
                        <i class="fa-solid fa-phone-volume"></i>
                        <span>Concierge Desk: <?= e(SITE_PHONE) ?></span>
                    </a>
                    <a href="https://wa.me/<?= str_replace(['+', ' '], '', SITE_WHATSAPP) ?>?text=Hello%20EstateVista%20India%20Concierge" target="_blank" class="top-bar-link">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span>WhatsApp Private Desk</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navbar -->
    <nav class="navbar" id="mainNavbar">
        <div class="container">
            <div class="nav-inner">
                <!-- Brand Logo -->
                <a href="<?= BASE_URL ?>" class="brand-logo" aria-label="EstateVista India Home">
                    <div class="brand-emblem" aria-hidden="true">
                        <svg viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg" class="emblem-svg">
                            <rect x="2" y="2" width="40" height="40" rx="8" fill="url(#evEmblemGrad)" stroke="#C5A880" stroke-width="1.2"/>
                            <path d="M13 14H23C25.2 14 26.5 15.3 26.5 17.5C26.5 19.7 25.2 21 23 21H15M13 21H22M13 29H24" stroke="#F1E6D6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22 21L28.5 29M31.5 14L25 29" stroke="#C5A880" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            <defs>
                                <linearGradient id="evEmblemGrad" x1="0" y1="0" x2="44" y2="44" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#142749"/>
                                    <stop offset="1" stop-color="#060B14"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                    <div class="brand-text-block">
                        <span class="brand-name">EstateVista <span>India</span></span>
                        <span class="brand-subtitle">Curated Prime Living</span>
                    </div>
                </a>

                <!-- Nav Menu: Buy, Rent, Commercial, Projects, About, Contact -->
                <ul class="nav-menu">
                    <li>
                        <a href="<?= BASE_URL ?>properties.php?purpose=buy" class="nav-link <?= (isset($_GET['purpose']) && $_GET['purpose'] === 'buy') ? 'active' : '' ?>">Buy</a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>properties.php?purpose=rent" class="nav-link <?= (isset($_GET['purpose']) && $_GET['purpose'] === 'rent') ? 'active' : '' ?>">Rent</a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>properties.php?purpose=commercial" class="nav-link <?= (isset($_GET['purpose']) && $_GET['purpose'] === 'commercial') ? 'active' : '' ?>">Commercial</a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>index.php#luxury-projects" class="nav-link">Projects</a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>index.php#why-estatevista" class="nav-link">About</a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>contact.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'contact.php') ? 'active' : '' ?>">Contact</a>
                    </li>
                </ul>

                <!-- Nav Actions: Login / Dashboard & Register / VIP Consultation -->
                <div class="nav-actions">
                    <div class="nav-actions-desktop">
                        <a href="<?= BASE_URL ?>list-property.php" class="btn btn-sm btn-gold" style="text-decoration: none;" title="List Your Luxury Residence">
                            <i class="fa-solid fa-plus-circle"></i>
                            <span>Post Property</span>
                        </a>
                        <?php if (is_logged_in()): 
                            $nav_user = get_logged_in_user();
                            $first_name = explode(' ', $nav_user['full_name'] ?? 'Client')[0];
                        ?>
                            <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-sm btn-outline-gold" style="text-decoration: none;">
                                <i class="fa-solid fa-crown" style="color: var(--gold-400);"></i>
                                <span><?= e($first_name) ?> (Dashboard)</span>
                            </a>
                            <a href="<?= BASE_URL ?>logout.php" class="btn btn-sm btn-outline" style="border-color: #FECACA; color: #DC2626; text-decoration: none;" title="Sign Out">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            </a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>login.php" class="btn btn-sm btn-outline-gold" style="text-decoration: none;" aria-label="Client Login">
                                <i class="fa-regular fa-user"></i>
                                <span>Sign In</span>
                            </a>
                            <a href="<?= BASE_URL ?>register.php" class="btn btn-sm btn-gold" style="text-decoration: none;" aria-label="Register Client Account">
                                <i class="fa-solid fa-crown"></i>
                                <span>Register</span>
                            </a>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle navigation menu" title="Open Navigation Menu">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- Mobile Navigation Drawer -->
<div class="mobile-nav-drawer" id="mobileNavDrawer">
    <div>
        <div class="mobile-nav-drawer-header">
            <div class="brand-logo">
                <div class="brand-emblem" aria-hidden="true">
                    <svg viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg" class="emblem-svg">
                        <rect x="2" y="2" width="40" height="40" rx="8" fill="#0B1528" stroke="#C5A880" stroke-width="1.2"/>
                        <path d="M13 14H23C25.2 14 26.5 15.3 26.5 17.5C26.5 19.7 25.2 21 23 21H15M13 21H22M13 29H24" stroke="#F1E6D6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M22 21L28.5 29M31.5 14L25 29" stroke="#C5A880" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="brand-text-block">
                    <span class="brand-name">EstateVista <span>India</span></span>
                    <span class="brand-subtitle">Curated Prime Living</span>
                </div>
            </div>
            <button type="button" class="mobile-nav-close" id="mobileNavClose" aria-label="Close menu" title="Close Menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="mobile-nav-links">
            <a href="<?= BASE_URL ?>properties.php?purpose=buy" class="mobile-link">Buy Residences</a>
            <a href="<?= BASE_URL ?>properties.php?purpose=rent" class="mobile-link">Rent Luxury Homes</a>
            <a href="<?= BASE_URL ?>properties.php?purpose=commercial" class="mobile-link">Commercial Spaces</a>
            <a href="<?= BASE_URL ?>properties.php" class="mobile-link">All Properties & Search</a>
            <a href="<?= BASE_URL ?>list-property.php" class="mobile-link" style="color: var(--gold-600); font-weight: 700;"><i class="fa-solid fa-plus-circle"></i> List Your Property</a>
            <a href="<?= BASE_URL ?>index.php#luxury-projects" class="mobile-link">Flagship Projects</a>
            <a href="<?= BASE_URL ?>index.php#prime-cities" class="mobile-link">Explore by City</a>
            <a href="<?= BASE_URL ?>index.php#why-estatevista" class="mobile-link">About EstateVista</a>
            <a href="<?= BASE_URL ?>contact.php" class="mobile-link <?= (basename($_SERVER['PHP_SELF']) === 'contact.php') ? 'active' : '' ?>">Contact Advisory</a>
        </div>
    </div>
    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        <?php if (is_logged_in()): 
            $nav_user = get_logged_in_user();
        ?>
            <div style="background: var(--navy-900); padding: 0.85rem 1rem; border-radius: var(--radius-sm); color: var(--white);">
                <div style="font-size: 0.7rem; color: var(--gold-400); text-transform: uppercase; font-weight: 700;">Signed In Client</div>
                <div style="font-weight: 700; font-size: 0.95rem; margin-top: 0.15rem;"><?= e($nav_user['full_name'] ?? 'Client') ?></div>
                <div style="font-size: 0.75rem; color: var(--slate-400);"><?= e($nav_user['email'] ?? '') ?></div>
            </div>
            <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-gold btn-block" style="width: 100%; text-decoration: none; text-align: center;">
                <i class="fa-solid fa-gauge"></i> Client Dashboard
            </a>
            <a href="<?= BASE_URL ?>logout.php" class="btn btn-outline btn-block" style="width: 100%; border-color: #FECACA; color: #DC2626; text-decoration: none; text-align: center;">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>login.php" class="btn btn-outline-gold btn-block" style="width: 100%; text-decoration: none; text-align: center;">
                <i class="fa-regular fa-user"></i> Client Sign In
            </a>
            <a href="<?= BASE_URL ?>register.php" class="btn btn-gold btn-block" style="width: 100%; text-decoration: none; text-align: center;">
                <i class="fa-solid fa-crown"></i> Register Private Account
            </a>
        <?php endif; ?>
        <p style="font-size: 0.75rem; color: var(--slate-400); text-align: center; margin-top: 0.5rem;">
            <i class="fa-solid fa-shield-halved" style="color: var(--gold-400);"></i> RERA Certified Luxury Portal
    </div>
</div>
<div class="mobile-nav-backdrop" id="mobileNavBackdrop"></div>

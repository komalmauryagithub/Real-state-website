<?php
/**
 * EstateVista India - Website Content Management CMS (Module 16)
 * Modify homepage hero copy, platform statistics, concierge contact info, and disclaimers.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';

require_admin_login();
require_admin_role(['super_admin', 'admin']);

$db = Database::getConnection();
$success_msg = '';
$error_msg = '';

// Handle POST Save CMS Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_admin_csrf()) {
        $error_msg = "Security token expired. Please reload and try again.";
    } else {
        $keys = [
            // Homepage Hero
            'hero_title' => 'cms',
            'hero_subtitle' => 'cms',
            'hero_cta_text' => 'cms',
            // Platform Stats
            'stat_transacted_value' => 'cms',
            'stat_families_housed' => 'cms',
            'stat_satisfaction_rate' => 'cms',
            'stat_prime_metros' => 'cms',
            // Contact & Concierge
            'contact_phone' => 'general',
            'contact_email' => 'general',
            'contact_whatsapp' => 'general',
            'corporate_address' => 'general',
            // Compliance & Legal
            'master_rera_number' => 'compliance',
            'regulatory_disclaimer' => 'compliance',
            // Social Media
            'social_linkedin' => 'social',
            'social_instagram' => 'social',
            'social_youtube' => 'social'
        ];

        try {
            foreach ($keys as $k => $grp) {
                if (isset($_POST[$k])) {
                    $val = sanitize($_POST[$k]);
                    update_system_setting($k, $val, $grp);
                }
            }
            log_admin_activity('Updated Website Content CMS', 'Modified homepage hero, metrics, concierge coordinates, or disclaimers.');
            $success_msg = "Website content and branding configurations updated successfully.";
        } catch (Exception $e) {
            $error_msg = "Error updating content: " . $e->getMessage();
        }
    }
}

// Load current settings values
$hero_title = get_system_setting('hero_title', 'Find a Place That Feels Like Home.');
$hero_subtitle = get_system_setting('hero_subtitle', 'Discover India\'s most prestigious luxury homes, architectural penthouses, and private estates across Mumbai, Delhi NCR, Bengaluru, Hyderabad, Pune, and Goa.');
$hero_cta_text = get_system_setting('hero_cta_text', 'Explore Prime Residences');

$stat_transacted_val = get_system_setting('stat_transacted_value', '₹4,500+ Cr');
$stat_families = get_system_setting('stat_families_housed', '1,200+');
$stat_satisfaction = get_system_setting('stat_satisfaction_rate', '98.7%');
$stat_metros = get_system_setting('stat_prime_metros', '6 Prime Hubs');

$contact_phone = get_system_setting('contact_phone', '+91 22 6985 4000');
$contact_email = get_system_setting('contact_email', 'concierge@estatevistaindia.com');
$contact_whatsapp = get_system_setting('contact_whatsapp', '+91 98200 98200');
$corporate_address = get_system_setting('corporate_address', 'Level 38, The Imperial Financial Centre, Senapati Bapat Marg, Lower Parel, Mumbai 400013, Maharashtra, India');

$master_rera = get_system_setting('master_rera_number', 'MahaRERA Reg. A51900018420');
$disclaimer = get_system_setting('regulatory_disclaimer', 'EstateVista India operates as a certified luxury real estate advisory and facilitation firm compliant with Real Estate (Regulation and Development) Act, 2016.');

$social_linkedin = get_system_setting('social_linkedin', 'https://linkedin.com/company/estatevistaindia');
$social_instagram = get_system_setting('social_instagram', 'https://instagram.com/estatevistaindia');
$social_youtube = get_system_setting('social_youtube', 'https://youtube.com/@estatevistaindia');

$page_title = "Website Content & CMS";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                    Website Content & CMS Suite
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                    Manage public-facing hero headlines, brand metrics, concierge channels, and regulatory certifications.
                </p>
            </div>
            <a href="<?= BASE_URL ?>" target="_blank" class="adm-btn adm-btn-navy">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Preview Live Website
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

        <form method="POST" action="cms.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">

            <!-- Section 1: Hero Section -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <div class="adm-card-title">
                        <i class="fa-solid fa-image" style="color: var(--adm-gold);"></i>
                        Cinematic Hero Section
                    </div>
                </div>
                <div class="adm-card-body">
                    <div class="adm-form-group">
                        <label class="adm-label">Hero Main Heading *</label>
                        <input type="text" name="hero_title" class="adm-input" required value="<?= e($hero_title) ?>">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Hero Subtitle *</label>
                        <textarea name="hero_subtitle" class="adm-textarea" rows="2" required><?= e($hero_subtitle) ?></textarea>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Primary Call to Action (CTA) Button Text</label>
                        <input type="text" name="hero_cta_text" class="adm-input" value="<?= e($hero_cta_text) ?>">
                    </div>
                </div>
            </div>

            <!-- Section 2: Platform Metrics -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <div class="adm-card-title">
                        <i class="fa-solid fa-chart-pie" style="color: var(--adm-gold);"></i>
                        Key Trust & Performance Metrics
                    </div>
                </div>
                <div class="adm-card-body">
                    <div class="adm-grid-2">
                        <div class="adm-form-group">
                            <label class="adm-label">Total Portfolio Transacted Value</label>
                            <input type="text" name="stat_transacted_value" class="adm-input" value="<?= e($stat_transacted_val) ?>">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-label">Discerning Families Housed</label>
                            <input type="text" name="stat_families_housed" class="adm-input" value="<?= e($stat_families) ?>">
                        </div>
                    </div>
                    <div class="adm-grid-2">
                        <div class="adm-form-group">
                            <label class="adm-label">Client Satisfaction Rate</label>
                            <input type="text" name="stat_satisfaction_rate" class="adm-input" value="<?= e($stat_satisfaction) ?>">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-label">Prime Luxury Metros Active</label>
                            <input type="text" name="stat_prime_metros" class="adm-input" value="<?= e($stat_metros) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Concierge Channels -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <div class="adm-card-title">
                        <i class="fa-solid fa-headset" style="color: var(--adm-gold);"></i>
                        Concierge & Advisory Contact Coordinates
                    </div>
                </div>
                <div class="adm-card-body">
                    <div class="adm-grid-3">
                        <div class="adm-form-group">
                            <label class="adm-label">Central Hotline Phone</label>
                            <input type="text" name="contact_phone" class="adm-input" value="<?= e($contact_phone) ?>">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-label">Private WhatsApp Concierge</label>
                            <input type="text" name="contact_whatsapp" class="adm-input" value="<?= e($contact_whatsapp) ?>">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-label">Official Advisory Email</label>
                            <input type="email" name="contact_email" class="adm-input" value="<?= e($contact_email) ?>">
                        </div>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Corporate Headquarters Address</label>
                        <input type="text" name="corporate_address" class="adm-input" value="<?= e($corporate_address) ?>">
                    </div>
                </div>
            </div>

            <!-- Section 4: Regulatory & Social -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <div class="adm-card-title">
                        <i class="fa-solid fa-scale-balanced" style="color: var(--adm-gold);"></i>
                        Regulatory Compliance & Social Channels
                    </div>
                </div>
                <div class="adm-card-body">
                    <div class="adm-grid-2">
                        <div class="adm-form-group">
                            <label class="adm-label">Master RERA Advisory Accreditation Number</label>
                            <input type="text" name="master_rera_number" class="adm-input" value="<?= e($master_rera) ?>">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-label">LinkedIn Corporate Page</label>
                            <input type="url" name="social_linkedin" class="adm-input" value="<?= e($social_linkedin) ?>">
                        </div>
                    </div>
                    <div class="adm-grid-2">
                        <div class="adm-form-group">
                            <label class="adm-label">Instagram Showcase URL</label>
                            <input type="url" name="social_instagram" class="adm-input" value="<?= e($social_instagram) ?>">
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-label">YouTube Architectural Showcase</label>
                            <input type="url" name="social_youtube" class="adm-input" value="<?= e($social_youtube) ?>">
                        </div>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Legal Disclaimer & Statutory Advisory Note</label>
                        <textarea name="regulatory_disclaimer" class="adm-textarea" rows="2"><?= e($disclaimer) ?></textarea>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-bottom: 3rem;">
                <button type="submit" class="adm-btn adm-btn-gold" style="padding: 0.8rem 2rem; font-size: 0.95rem;">
                    <i class="fa-solid fa-floppy-disk"></i> Save CMS Configurations
                </button>
            </div>
        </form>
    <?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

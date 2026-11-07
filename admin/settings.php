<?php
/**
 * EstateVista India - Platform & System Settings (Module 20)
 * Master operational configurations, commission parameters, and infrastructure health.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';

require_admin_login();
require_admin_role('super_admin');

$db = Database::getConnection();
$success_msg = '';
$error_msg = '';

// Handle Settings POST Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_admin_csrf()) {
        $error_msg = "Security token expired. Please reload and try again.";
    } else {
        $action = sanitize($_POST['action'] ?? '');

        if ($action === 'save_settings') {
            $settings_map = [
                'site_name' => 'general',
                'site_tagline' => 'general',
                'currency_symbol' => 'financial',
                'currency_unit' => 'financial',
                'default_commission_pct' => 'financial',
                'support_phone' => 'contact',
                'support_email' => 'contact',
                'support_whatsapp' => 'contact',
                'corporate_office' => 'contact',
                'master_rera_accreditation' => 'compliance',
                'system_timezone' => 'system'
            ];

            try {
                foreach ($settings_map as $key => $group) {
                    if (isset($_POST[$key])) {
                        $val = sanitize($_POST[$key]);
                        update_system_setting($key, $val, $group);
                    }
                }
                log_admin_activity('Updated Platform Settings', 'Modified platform settings and operational parameters.');
                $success_msg = "Master platform settings saved successfully.";
            } catch (Exception $e) {
                $error_msg = "Error updating settings: " . $e->getMessage();
            }
        }

        // Action: Clear Activity Logs older than 60 days
        elseif ($action === 'purge_logs') {
            try {
                $stmt = $db->query("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)");
                $affected = $stmt->rowCount();
                log_admin_activity('Purged Audit Logs', "Purged {$affected} historical logs older than 60 days");
                $success_msg = "Maintenance complete. Cleared {$affected} historical logs.";
            } catch (PDOException $e) {
                $error_msg = "Error purging logs: " . $e->getMessage();
            }
        }
    }
}

// Fetch current values
$site_name = get_system_setting('site_name', 'EstateVista India');
$site_tagline = get_system_setting('site_tagline', 'The Benchmark of Indian Ultra-Luxury Real Estate');
$currency_symbol = get_system_setting('currency_symbol', '₹');
$currency_unit = get_system_setting('currency_unit', 'Crore (Cr) / Lakh (L)');
$commission_pct = get_system_setting('default_commission_pct', '2.0');

$support_phone = get_system_setting('support_phone', '+91 22 6985 4000');
$support_email = get_system_setting('support_email', 'concierge@estatevistaindia.com');
$support_whatsapp = get_system_setting('support_whatsapp', '+91 98200 98200');
$corporate_office = get_system_setting('corporate_office', 'Level 38, The Imperial Financial Centre, Lower Parel, Mumbai 400013');
$master_rera = get_system_setting('master_rera_accreditation', 'MahaRERA Reg. A51900018420');
$system_timezone = get_system_setting('system_timezone', 'Asia/Kolkata');

// System Diagnostics
$mysql_ver = $db->query("SELECT VERSION()")->fetchColumn();
$total_audit_logs = (int)$db->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();

$page_title = "Platform Settings & Diagnostics";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                    Platform Architecture & System Governance
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                    Super Administrator console governing corporate credentials, currency formatting, commission scales, and server telemetry.
                </p>
            </div>
            <span class="adm-status-badge adm-badge-gold" style="padding: 0.45rem 1rem; font-size: 0.8rem;">
                <i class="fa-solid fa-shield-halved"></i> Super Admin Privileges
            </span>
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

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: flex-start;">
            <!-- Left: Settings Forms -->
            <div>
                <form method="POST" action="settings.php">
                    <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                    <input type="hidden" name="action" value="save_settings">

                    <!-- Identity & Financial -->
                    <div class="adm-card">
                        <div class="adm-card-header">
                            <div class="adm-card-title">
                                <i class="fa-solid fa-sliders" style="color: var(--adm-gold);"></i>
                                Brand Identity & Financial Conventions
                            </div>
                        </div>
                        <div class="adm-card-body">
                            <div class="adm-grid-2">
                                <div class="adm-form-group">
                                    <label class="adm-label">Platform Name *</label>
                                    <input type="text" name="site_name" class="adm-input" required value="<?= e($site_name) ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Default Advisory Commission (%)</label>
                                    <input type="number" step="0.1" name="default_commission_pct" class="adm-input" value="<?= e($commission_pct) ?>">
                                </div>
                            </div>
                            <div class="adm-form-group">
                                <label class="adm-label">Brand Tagline</label>
                                <input type="text" name="site_tagline" class="adm-input" value="<?= e($site_tagline) ?>">
                            </div>
                            <div class="adm-grid-2">
                                <div class="adm-form-group">
                                    <label class="adm-label">Currency Symbol</label>
                                    <input type="text" name="currency_symbol" class="adm-input" value="<?= e($currency_symbol) ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Currency Notation Format</label>
                                    <input type="text" name="currency_unit" class="adm-input" value="<?= e($currency_unit) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact & Compliance -->
                    <div class="adm-card">
                        <div class="adm-card-header">
                            <div class="adm-card-title">
                                <i class="fa-solid fa-headset" style="color: var(--adm-gold);"></i>
                                Central Operations & RERA Compliance
                            </div>
                        </div>
                        <div class="adm-card-body">
                            <div class="adm-grid-3">
                                <div class="adm-form-group">
                                    <label class="adm-label">Hotline Phone</label>
                                    <input type="text" name="support_phone" class="adm-input" value="<?= e($support_phone) ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">WhatsApp Channel</label>
                                    <input type="text" name="support_whatsapp" class="adm-input" value="<?= e($support_whatsapp) ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Official Inquiries Email</label>
                                    <input type="email" name="support_email" class="adm-input" value="<?= e($support_email) ?>">
                                </div>
                            </div>
                            <div class="adm-form-group">
                                <label class="adm-label">Corporate Office Headquarters</label>
                                <input type="text" name="corporate_office" class="adm-input" value="<?= e($corporate_office) ?>">
                            </div>
                            <div class="adm-grid-2">
                                <div class="adm-form-group">
                                    <label class="adm-label">Master MahaRERA Registration ID</label>
                                    <input type="text" name="master_rera_accreditation" class="adm-input" value="<?= e($master_rera) ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">System Timezone</label>
                                    <input type="text" name="system_timezone" class="adm-input" value="<?= e($system_timezone) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; margin-bottom: 2rem;">
                        <button type="submit" class="adm-btn adm-btn-gold" style="padding: 0.8rem 2.2rem; font-size: 0.95rem;">
                            <i class="fa-solid fa-floppy-disk"></i> Save System Governance
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right: System Diagnostics & Maintenance -->
            <div>
                <!-- Telemetry Card -->
                <div class="adm-card">
                    <div class="adm-card-header">
                        <div class="adm-card-title">
                            <i class="fa-solid fa-server" style="color: var(--adm-gold);"></i>
                            Infrastructure Health
                        </div>
                    </div>
                    <div class="adm-card-body" style="font-size: 0.84rem;">
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #F1F5F9;">
                            <span style="color: var(--adm-text-muted);">PHP Engine</span>
                            <strong style="color: var(--adm-navy);"><?= PHP_VERSION ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #F1F5F9;">
                            <span style="color: var(--adm-text-muted);">Database Server</span>
                            <strong style="color: var(--adm-navy);">MySQL <?= e($mysql_ver) ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #F1F5F9;">
                            <span style="color: var(--adm-text-muted);">Max Upload Size</span>
                            <strong style="color: var(--adm-navy);"><?= ini_get('upload_max_filesize') ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #F1F5F9;">
                            <span style="color: var(--adm-text-muted);">Memory Allocation</span>
                            <strong style="color: var(--adm-navy);"><?= ini_get('memory_limit') ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #F1F5F9;">
                            <span style="color: var(--adm-text-muted);">Server Time</span>
                            <strong style="color: var(--adm-navy);"><?= date('H:i:s T') ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                            <span style="color: var(--adm-text-muted);">Audit Trail Entries</span>
                            <strong style="color: #2563EB;"><?= number_format($total_audit_logs) ?> logs</strong>
                        </div>
                    </div>
                </div>

                <!-- Database Maintenance -->
                <div class="adm-card">
                    <div class="adm-card-header">
                        <div class="adm-card-title">
                            <i class="fa-solid fa-broom" style="color: var(--adm-gold);"></i>
                            Audit Log Maintenance
                        </div>
                    </div>
                    <div class="adm-card-body">
                        <p style="font-size: 0.82rem; color: var(--adm-text-muted); line-height: 1.5; margin-bottom: 1.25rem;">
                            Purge legacy activity logs older than 60 days to optimize database indexing and query speeds.
                        </p>
                        <form method="POST" action="settings.php">
                            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                            <input type="hidden" name="action" value="purge_logs">
                            <button type="submit" class="adm-btn adm-btn-outline" style="width: 100%; justify-content: center; color: #EF4444; border-color: #FECACA;">
                                <i class="fa-solid fa-trash-can"></i> Purge Logs Older Than 60 Days
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

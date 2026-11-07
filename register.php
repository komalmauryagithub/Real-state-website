<?php
/**
 * EstateVista India - Client Registration
 * Secure account creation with password_hash(PASSWORD_BCRYPT), CSRF protection,
 * and server-side validation.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'Security validation failed (CSRF). Please refresh and submit again.';
    } elseif (empty($_POST['terms_agreed'])) {
        $error_message = 'Please accept the EstateVista Client Privacy Charter and Terms of Advisory.';
    } else {
        $regResult = register_user($_POST);

        if ($regResult['success']) {
            header('Location: ' . BASE_URL . 'dashboard.php?welcome=1');
            exit;
        } else {
            $error_message = $regResult['message'];
        }
    }
}

$page_title = 'Private Client Registration | ' . SITE_NAME;
$extra_css = BASE_URL . 'assets/css/auth.css';
$extra_js = BASE_URL . 'assets/js/auth.js';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="auth-page">
    <div class="auth-wrapper auth-wrapper-wide">
        <div class="auth-card">
            <div class="auth-header">
                <div class="brand-emblem"><span>EV</span></div>
                <h1 class="auth-title">Client Registration</h1>
                <p class="auth-subtitle">Create your private portfolio account to shortlist ultra-luxury residences, schedule discreet walkthroughs, and receive non-public property dossiers.</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="auth-alert auth-alert-error" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div><?= e($error_message) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>register.php" class="auth-form" autocomplete="on">
                <?= csrf_field() ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="auth-form-group">
                        <label class="auth-label" for="reg_name">Full Legal Name *</label>
                        <div class="auth-input-wrapper">
                            <i class="fa-regular fa-user input-icon"></i>
                            <input type="text" id="reg_name" name="full_name" class="auth-input" placeholder="e.g. Vikramaditya Singhania" value="<?= isset($_POST['full_name']) ? e($_POST['full_name']) : '' ?>" required autofocus>
                        </div>
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-label" for="reg_phone">Mobile Number (+91) *</label>
                        <div class="auth-input-wrapper">
                            <i class="fa-solid fa-phone input-icon"></i>
                            <input type="tel" id="reg_phone" name="phone" class="auth-input" placeholder="+91 98200 00000" value="<?= isset($_POST['phone']) ? e($_POST['phone']) : '' ?>" required>
                        </div>
                    </div>
                </div>

                <div class="auth-form-group">
                    <label class="auth-label" for="reg_email">Corporate or Private Email *</label>
                    <div class="auth-input-wrapper">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input type="email" id="reg_email" name="email" class="auth-input" placeholder="name@domain.com" value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="auth-form-group">
                        <label class="auth-label" for="reg_city">City of Residence</label>
                        <select id="reg_city" name="city" class="auth-input" style="padding-left: 1rem;">
                            <option value="Mumbai" <?= (isset($_POST['city']) && $_POST['city'] === 'Mumbai') ? 'selected' : '' ?>>Mumbai (Worli, Bandra, South Mumbai)</option>
                            <option value="Delhi NCR" <?= (isset($_POST['city']) && $_POST['city'] === 'Delhi NCR') ? 'selected' : '' ?>>Delhi NCR (Golf Course Road, DLF)</option>
                            <option value="Bengaluru" <?= (isset($_POST['city']) && $_POST['city'] === 'Bengaluru') ? 'selected' : '' ?>>Bengaluru (Indiranagar, UB City)</option>
                            <option value="Goa" <?= (isset($_POST['city']) && $_POST['city'] === 'Goa') ? 'selected' : '' ?>>Goa (Assagao, Anjuna, Candolim)</option>
                            <option value="Hyderabad" <?= (isset($_POST['city']) && $_POST['city'] === 'Hyderabad') ? 'selected' : '' ?>>Hyderabad (Jubilee Hills, Kokapet)</option>
                            <option value="Pune" <?= (isset($_POST['city']) && $_POST['city'] === 'Pune') ? 'selected' : '' ?>>Pune (Koregaon Park)</option>
                        </select>
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-label" for="reg_budget">Target Investment Bracket</label>
                        <select id="reg_budget" name="preferred_budget" class="auth-input" style="padding-left: 1rem;">
                            <option value="₹ 3 Cr - ₹ 7 Cr">₹ 3 Cr – ₹ 7 Cr</option>
                            <option value="₹ 7 Cr - ₹ 15 Cr" selected>₹ 7 Cr – ₹ 15 Cr</option>
                            <option value="₹ 15 Cr - ₹ 30 Cr">₹ 15 Cr – ₹ 30 Cr</option>
                            <option value="₹ 30 Cr+ (Ultra-Bespoke)">₹ 30 Cr+ (Ultra-Bespoke)</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="auth-form-group">
                        <label class="auth-label" for="reg_password">Security Password *</label>
                        <div class="auth-input-wrapper">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" id="reg_password" name="password" class="auth-input" placeholder="Min. 8 characters" required>
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
                        <label class="auth-label" for="reg_confirm">Confirm Password *</label>
                        <div class="auth-input-wrapper">
                            <i class="fa-solid fa-lock-open input-icon"></i>
                            <input type="password" id="reg_confirm" name="confirm_password" class="auth-input" placeholder="Repeat password" required>
                            <button type="button" class="auth-toggle-pass-btn" title="Toggle password visibility">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div style="margin: 1.25rem 0;">
                    <label style="display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.8rem; color: var(--slate-600); cursor: pointer; line-height: 1.5;">
                        <input type="checkbox" name="terms_agreed" value="1" checked style="accent-color: var(--gold-500); margin-top: 0.2rem;">
                        <span>
                            I agree to the <a href="javascript:void(0)" style="color: var(--gold-600); font-weight: 600;">Terms of Advisory</a> and 
                            <a href="javascript:void(0)" style="color: var(--gold-600); font-weight: 600;">Confidential Non-Disclosure Charter</a>. 
                            All shared information is strictly protected.
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn-auth-submit">
                    <i class="fa-solid fa-crown"></i>
                    <span>Create Private Advisory Account</span>
                </button>

                <div class="auth-footer-links">
                    Already an EstateVista private client? 
                    <a href="<?= BASE_URL ?>login.php">Sign In to Portal &rarr;</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

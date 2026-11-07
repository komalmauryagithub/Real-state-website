<?php
/**
 * EstateVista India - Client Portal Login
 * Session-based authentication with password_verify() and CSRF token protection.
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

if (isset($_GET['registered'])) {
    $success_message = 'Your private client account has been registered. Please sign in below.';
} elseif (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success_message = 'Your password has been reset successfully. Please sign in with your new credentials.';
} elseif (isset($_GET['logged_out'])) {
    $success_message = 'You have safely signed out of your private advisory session.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'Security token validation failed (CSRF). Please refresh and submit again.';
    } else {
        $identity = $_POST['identity'] ?? '';
        $password = $_POST['password'] ?? '';

        $authResult = authenticate_user($identity, $password);

        if ($authResult['success']) {
            $redirect = $_SESSION['redirect_after_login'] ?? (BASE_URL . 'dashboard.php');
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error_message = $authResult['message'];
        }
    }
}

$page_title = 'Private Client Portal Login | ' . SITE_NAME;
$extra_css = BASE_URL . 'assets/css/auth.css';
$extra_js = BASE_URL . 'assets/js/auth.js';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="auth-page">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="brand-emblem"><span>EV</span></div>
                <h1 class="auth-title">Client Portal Login</h1>
                <p class="auth-subtitle">Access your shortlisted residences, saved searches & private investment dossiers.</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="auth-alert auth-alert-error" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div><?= e($error_message) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="auth-alert auth-alert-success" role="status">
                    <i class="fa-solid fa-circle-check"></i>
                    <div><?= e($success_message) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>login.php" class="auth-form" autocomplete="on">
                <?= csrf_field() ?>

                <div class="auth-form-group">
                    <label class="auth-label" for="login_identity">Email Address or Registered Mobile *</label>
                    <div class="auth-input-wrapper">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input type="text" id="login_identity" name="identity" class="auth-input" placeholder="client@domain.com or +91 98200 00000" value="<?= isset($_POST['identity']) ? e($_POST['identity']) : '' ?>" required autofocus>
                    </div>
                </div>

                <div class="auth-form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                        <label class="auth-label" for="login_pass" style="margin-bottom: 0;">Password / Access Key *</label>
                        <a href="<?= BASE_URL ?>forgot-password.php" style="font-size: 0.78rem; color: var(--gold-600); text-decoration: none;">Forgot key?</a>
                    </div>
                    <div class="auth-input-wrapper">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="login_pass" name="password" class="auth-input" placeholder="••••••••••••" required>
                        <button type="button" class="auth-toggle-pass-btn" title="Toggle password visibility" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.8rem; color: var(--slate-600); margin: 1rem 0;">
                    <label style="display: flex; align-items: center; gap: 0.45rem; cursor: pointer;">
                        <input type="checkbox" name="remember" checked style="accent-color: var(--gold-500);">
                        <span>Keep me signed in</span>
                    </label>
                    <span style="color: var(--slate-400); display: flex; align-items: center; gap: 0.35rem;">
                        <i class="fa-solid fa-shield-halved" style="color: var(--gold-500);"></i> 256-bit SSL
                    </span>
                </div>

                <button type="submit" class="btn-auth-submit">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    <span>Sign In to Private Portal</span>
                </button>

                <!-- Demo Account Quick Sign-In Helper -->
                <div class="demo-account-box">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong><i class="fa-solid fa-key" style="color: var(--gold-600);"></i> Demo Client Account:</strong>
                        <button type="button" class="btn-fill-demo" id="btnFillDemo">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Auto Fill
                        </button>
                    </div>
                    <div style="margin-top: 0.3rem; font-size: 0.78rem; color: var(--slate-600);">
                        Email: <code>client@estatevistaindia.com</code> &bull; Pass: <code>Password@123</code>
                    </div>
                </div>

                <div class="auth-footer-links">
                    New client or family office? 
                    <a href="<?= BASE_URL ?>register.php">Request Client Registration &rarr;</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

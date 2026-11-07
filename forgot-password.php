<?php
/**
 * EstateVista India - Forgot Password Recovery
 * Generates cryptographically secure password reset token with 1-hour expiration.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$error_message = '';
$success_message = '';
$reset_info = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'Security validation failed (CSRF). Please refresh and submit again.';
    } else {
        $email = $_POST['email'] ?? '';
        $result = generate_password_reset_token($email);

        if ($result['success']) {
            $success_message = $result['message'];
            if (!empty($result['reset_url'])) {
                $reset_info = $result;
            }
        } else {
            $error_message = $result['message'];
        }
    }
}

$page_title = 'Recover Access Key | ' . SITE_NAME;
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
                <h1 class="auth-title">Account Recovery</h1>
                <p class="auth-subtitle">Enter your registered email address to receive secure password reset instructions.</p>
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

            <?php if ($reset_info): ?>
                <!-- Demonstration & Testing Password Reset Link Callout -->
                <div class="auth-alert auth-alert-info" style="display: block;">
                    <div style="font-weight: 700; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-envelope-open-text"></i> Secure Token Generated (Simulation Mode)
                    </div>
                    <p style="font-size: 0.8rem; margin: 0 0 0.75rem; color: #1E3A8A;">
                        In production, this link is dispatched to <strong><?= e($reset_info['email']) ?></strong> via encrypted SMTP. 
                        Click below to proceed to the secure password reset screen:
                    </p>
                    <a href="<?= e($reset_info['reset_url']) ?>" class="btn btn-navy btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none;">
                        <i class="fa-solid fa-arrow-right"></i> Reset Password for <?= e($reset_info['name']) ?>
                    </a>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>forgot-password.php" class="auth-form">
                <?= csrf_field() ?>

                <div class="auth-form-group">
                    <label class="auth-label" for="rec_email">Registered Email Address *</label>
                    <div class="auth-input-wrapper">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input type="email" id="rec_email" name="email" class="auth-input" placeholder="client@estatevistaindia.com" value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>" required autofocus>
                    </div>
                </div>

                <button type="submit" class="btn-auth-submit">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Send Password Recovery Link</span>
                </button>

                <div class="auth-footer-links">
                    Remembered your password? 
                    <a href="<?= BASE_URL ?>login.php">&larr; Return to Sign In</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

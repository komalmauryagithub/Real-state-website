<?php
/**
 * EstateVista India - Reset Password Form
 * Validates 64-char token, verifies expiration, and securely hashes new password.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$token_valid = false;
$user_info = null;
$error_message = '';
$success_message = '';

$db = Database::getConnection();

// Validate token presence & validity
if (!empty($token)) {
    try {
        $stmt = $db->prepare("
            SELECT id, email, full_name, reset_token_expires 
            FROM users 
            WHERE reset_token = :token AND reset_token_expires > NOW() AND status = 'active'
            LIMIT 1
        ");
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        $user_info = $stmt->fetch();
        if ($user_info) {
            $token_valid = true;
        } else {
            $error_message = 'This password recovery link is invalid or has expired. Please request a new recovery link.';
        }
    } catch (PDOException $e) {
        $error_message = 'Database verification error. Please try again.';
    }
} else {
    $error_message = 'Missing password recovery token.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'Security validation failed (CSRF). Please refresh and submit again.';
    } else {
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $resetResult = reset_password_with_token($token, $newPass, $confirmPass);

        if ($resetResult['success']) {
            header('Location: ' . BASE_URL . 'login.php?reset=success');
            exit;
        } else {
            $error_message = $resetResult['message'];
        }
    }
}

$page_title = 'Set New Security Password | ' . SITE_NAME;
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
                <h1 class="auth-title">Set New Password</h1>
                <?php if ($user_info): ?>
                    <p class="auth-subtitle">Create a new secure password for <strong><?= e($user_info['full_name']) ?></strong> (<?= e($user_info['email']) ?>).</p>
                <?php else: ?>
                    <p class="auth-subtitle">Update your private client access credentials.</p>
                <?php endif; ?>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="auth-alert auth-alert-error" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div><?= e($error_message) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($token_valid): ?>
                <form method="POST" action="<?= BASE_URL ?>reset-password.php?token=<?= e($token) ?>" class="auth-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">

                    <div class="auth-form-group">
                        <label class="auth-label" for="reg_password">New Security Password *</label>
                        <div class="auth-input-wrapper">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" id="reg_password" name="new_password" class="auth-input" placeholder="Min. 8 characters" required autofocus>
                            <button type="button" class="auth-toggle-pass-btn" title="Toggle password visibility">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength-bar">
                            <div class="password-strength-fill" id="passStrengthFill"></div>
                        </div>
                        <div class="password-strength-text">
                            <span>Password Strength:</span>
                            <strong id="passStrengthLabel">None</strong>
                        </div>
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-label" for="reset_confirm">Confirm New Password *</label>
                        <div class="auth-input-wrapper">
                            <i class="fa-solid fa-lock-open input-icon"></i>
                            <input type="password" id="reset_confirm" name="confirm_password" class="auth-input" placeholder="Re-enter new password" required>
                            <button type="button" class="auth-toggle-pass-btn" title="Toggle password visibility">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-auth-submit">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Update Security Password</span>
                    </button>
                </form>
            <?php else: ?>
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="<?= BASE_URL ?>forgot-password.php" class="btn btn-navy" style="display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                        <i class="fa-solid fa-arrow-rotate-right"></i> Request New Password Reset Link
                    </a>
                </div>
            <?php endif; ?>

            <div class="auth-footer-links">
                Return to client portal? 
                <a href="<?= BASE_URL ?>login.php">Sign In &rarr;</a>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

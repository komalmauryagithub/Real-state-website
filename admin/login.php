<?php
/**
 * EstateVista India - Admin Command Center Login
 * Dedicated, separate administrative login interface with CSRF & BCRYPT security.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in as admin, redirect to admin index
if (is_admin_logged_in()) {
    header('Location: ' . BASE_URL . 'admin/index.php');
    exit;
}

$error_message = '';
$success_message = '';

if (isset($_GET['logged_out'])) {
    $success_message = 'Administrative session terminated safely.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error_message = 'Security validation failed (CSRF token mismatch). Please refresh and try again.';
    } else {
        $email = trim($_POST['email'] ?? $_POST['identity'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $error_message = 'Please provide both your administrative email and password.';
        } else {
            $auth = authenticate_admin($email, $password);
            if ($auth['success']) {
                $redirect = $_SESSION['admin_redirect'] ?? (BASE_URL . 'admin/index.php');
                unset($_SESSION['admin_redirect']);
                header('Location: ' . $redirect);
                exit;
            } else {
                $error_message = $auth['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Admin Command Center Login | EstateVista India</title>

    <!-- Google Fonts: Playfair Display & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/assets/css/admin.css">

    <style>
        .admin-login-body {
            background: radial-gradient(circle at top right, #16243E 0%, #060B14 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
        }
        .admin-login-card {
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 460px;
            overflow: hidden;
            border: 1px solid rgba(197, 168, 128, 0.3);
        }
        .admin-login-header {
            background: #0B1528;
            color: #FFFFFF;
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            border-bottom: 2px solid #C5A880;
        }
        .admin-login-logo {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #C5A880 0%, #A68A5E 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Cinzel', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #060B14;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 15px rgba(197, 168, 128, 0.4);
        }
        .admin-login-title {
            font-family: 'Cinzel', serif;
            font-size: 1.45rem;
            letter-spacing: 0.5px;
            margin-bottom: 0.35rem;
        }
        .admin-login-subtitle {
            font-size: 0.8rem;
            color: #C5A880;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
        }
        .admin-login-body-content {
            padding: 2.25rem 2rem;
        }
        .admin-demo-box {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: #475569;
        }
        .admin-demo-title {
            font-weight: 700;
            color: #0B1528;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
    </style>
</head>
<body class="admin-login-body">

<div class="admin-login-card">
    <div class="admin-login-header">
        <div class="admin-login-logo">EV</div>
        <h1 class="admin-login-title">EstateVista <span>India</span></h1>
        <div class="admin-login-subtitle">Command Center Portal</div>
    </div>

    <div class="admin-login-body-content">
        <?php if (!empty($error_message)): ?>
            <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.6rem;">
                <i class="fa-solid fa-circle-exclamation" style="color: #EF4444;"></i>
                <div><?= e($error_message) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.6rem;">
                <i class="fa-solid fa-circle-check" style="color: #10B981;"></i>
                <div><?= e($success_message) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>admin/login.php">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

            <div class="adm-form-group">
                <label for="admEmail" class="adm-label">Administrative Email Address</label>
                <div class="adm-search-input-wrap" style="width: 100%;">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" id="admEmail" name="email" class="adm-input" placeholder="admin@estatevistaindia.com" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>
            </div>

            <div class="adm-form-group">
                <label for="admPassword" class="adm-label">Encrypted Password</label>
                <div class="adm-search-input-wrap" style="width: 100%;">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="admPassword" name="password" class="adm-input" placeholder="••••••••••••" required>
                </div>
            </div>

            <button type="submit" class="adm-btn adm-btn-gold" style="width: 100%; justify-content: center; padding: 0.85rem; font-size: 0.95rem; margin-top: 0.5rem;">
                <i class="fa-solid fa-right-to-bracket"></i>
                <span>Sign In to Command Center</span>
            </button>
        </form>

        <!-- Quick Demo Credentials Box -->
        <div class="admin-demo-box">
            <div class="admin-demo-title">
                <i class="fa-solid fa-key" style="color: #C5A880;"></i>
                <span>Default Admin Credentials (Pre-seeded):</span>
            </div>
            <div style="margin-bottom: 0.35rem;">
                <strong>Super Admin:</strong> <code>admin@estatevistaindia.com</code> / <code>Admin@123</code>
            </div>
            <div style="margin-bottom: 0.35rem;">
                <strong>Operations Admin:</strong> <code>manager@estatevistaindia.com</code> / <code>Admin@123</code>
            </div>
            <div>
                <strong>Advisory Agent:</strong> <code>aditya.birla@estatevistaindia.com</code> / <code>Admin@123</code>
            </div>
        </div>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.8rem; color: #94A3B8;">
            <a href="<?= BASE_URL ?>" style="color: #64748B; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i> Return to Public Website
            </a>
        </div>
    </div>
</div>

</body>
</html>

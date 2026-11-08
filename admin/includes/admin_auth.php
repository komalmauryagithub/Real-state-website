<?php
/**
 * EstateVista India - Admin Authentication & Role-Based Access Control (RBAC)
 * Roles: super_admin, admin, agent
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if (!ob_get_level()) {
    ob_start();
}

/**
 * Check if an admin is currently logged in
 */
function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_user']) && !empty($_SESSION['admin_user']['id']);
}

/**
 * Get the currently logged-in admin user array
 */
function get_logged_in_admin(): ?array {
    return $_SESSION['admin_user'] ?? null;
}

function get_logged_admin(): ?array {
    return get_logged_in_admin();
}

/**
 * Admin CSRF Token Generation and Verification
 */
function generate_admin_csrf(): string {
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf_token'];
}

function verify_admin_csrf(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    if (empty($token)) {
        return false;
    }
    if (!empty($_SESSION['admin_csrf_token']) && hash_equals($_SESSION['admin_csrf_token'], $token)) {
        return true;
    }
    if (!empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

/**
 * Get current admin role (super_admin, admin, agent)
 */
function get_admin_role(): string {
    return $_SESSION['admin_user']['role'] ?? '';
}

/**
 * Check if admin has one of the allowed roles
 * Usage: admin_has_role(['super_admin', 'admin']) or admin_has_role('super_admin')
 */
function admin_has_role($roles): bool {
    if (!is_admin_logged_in()) {
        return false;
    }
    $currentRole = get_admin_role();
    if (is_array($roles)) {
        return in_array($currentRole, $roles, true);
    }
    return $currentRole === $roles;
}

/**
 * Guard page: Require admin login
 */
function require_admin_login(): void {
    if (!is_admin_logged_in()) {
        $currentUrl = $_SERVER['REQUEST_URI'];
        $_SESSION['admin_redirect'] = $currentUrl;
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
}

/**
 * Guard page: Require specific role(s)
 */
function require_admin_role($roles, string $redirect = 'admin/index.php'): void {
    require_admin_login();
    if (!admin_has_role($roles)) {
        $_SESSION['admin_flash_error'] = 'Access Denied: Your account role does not have permission to access this module.';
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
}

/**
 * Authenticate admin with email and password
 */
function authenticate_admin(string $email, string $password): array {
    $email = strtolower(trim($email));
    $db = Database::getConnection();

    if (!$db) {
        return ['success' => false, 'message' => 'Database connection unavailable.'];
    }

    try {
        $stmt = $db->prepare("SELECT * FROM admins WHERE LOWER(email) = :email LIMIT 1");
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $admin = $stmt->fetch();

        if (!$admin) {
            return ['success' => false, 'message' => 'Invalid administrative credentials.'];
        }

        if ($admin['status'] !== 'active') {
            return ['success' => false, 'message' => 'Your administrative account has been deactivated.'];
        }

        if (!password_verify($password, $admin['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid administrative credentials.'];
        }

        // Regenerate session ID for security
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['admin_user'] = [
            'id' => (int)$admin['id'],
            'name' => $admin['name'],
            'email' => $admin['email'],
            'phone' => $admin['phone'],
            'role' => $admin['role'],
            'avatar' => $admin['avatar'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80',
            'created_at' => $admin['created_at']
        ];

        // Update last login
        $upd = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = :id");
        $upd->execute([':id' => $admin['id']]);

        // Log login activity
        log_admin_activity($admin['id'], $admin['name'], 'Admin Login', 'Administrator authenticated successfully.');

        return ['success' => true, 'admin' => $_SESSION['admin_user']];
    } catch (PDOException $e) {
        error_log("Admin authentication error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Authentication error occurred.'];
    }
}

/**
 * Log out admin
 */
function logout_admin(): void {
    if (is_admin_logged_in()) {
        $adm = get_logged_in_admin();
        log_admin_activity($adm['id'], $adm['name'], 'Admin Logout', 'Administrator ended session.');
    }
    unset($_SESSION['admin_user']);
}

/**
 * Log activity to activity_logs table
 */
function log_admin_activity($arg1, $arg2 = null, $arg3 = null, $arg4 = null): void {
    $db = Database::getConnection();
    if (!$db) return;

    $adminId = null;
    $adminName = 'System';
    $action = '';
    $details = '';

    if (is_int($arg1) || (is_numeric($arg1) && $arg2 !== null && $arg3 !== null)) {
        $adminId = (int)$arg1;
        $adminName = (string)$arg2;
        $action = (string)$arg3;
        $details = (string)($arg4 ?? '');
    } else {
        $current = get_logged_in_admin();
        if ($current) {
            $adminId = (int)$current['id'];
            $adminName = (string)$current['name'];
        }
        $action = (string)$arg1;
        $details = (string)($arg2 ?? '');
    }

    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("
            INSERT INTO activity_logs (admin_id, admin_name, action, details, ip_address)
            VALUES (:aid, :aname, :action, :details, :ip)
        ");
        $stmt->execute([
            ':aid' => $adminId,
            ':aname' => $adminName,
            ':action' => $action,
            ':details' => $details,
            ':ip' => $ip
        ]);
    } catch (PDOException $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

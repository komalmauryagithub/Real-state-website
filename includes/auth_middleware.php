<?php
/**
 * EstateVista India - Unified Authentication & RBAC Middleware
 * Supports roles: super_admin, admin, agent, owner, customer
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validation.php';

/**
 * Check if any user is currently authenticated
 */
function is_authenticated(): bool {
    // Check standard user session
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['logged_in'])) {
        return true;
    }
    // Check admin session
    if (!empty($_SESSION['admin_user']) && !empty($_SESSION['admin_user']['id'])) {
        return true;
    }
    return false;
}

/**
 * Get currently authenticated user object from database
 */
function get_authenticated_user(): ?array {
    $db = Database::getConnection();
    if (!$db) return null;

    $userId = 0;
    if (!empty($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
    } elseif (!empty($_SESSION['admin_user']['id'])) {
        $userId = (int)$_SESSION['admin_user']['id'];
    }

    if ($userId <= 0) {
        return null;
    }

    try {
        $stmt = $db->prepare("
            SELECT u.*, r.name as role_name, r.display_name as role_title
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = :id AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        error_log("Error in get_authenticated_user: " . $e->getMessage());
        return null;
    }
}

/**
 * Get current user role string
 */
function get_authenticated_role(): string {
    if (!empty($_SESSION['admin_user']['role'])) {
        return (string)$_SESSION['admin_user']['role'];
    }
    $user = get_authenticated_user();
    return $user['role_name'] ?? 'guest';
}

/**
 * Guard page: Require user to be authenticated
 */
function require_auth(string $redirect = 'login.php'): void {
    if (!is_authenticated()) {
        $_SESSION['auth_redirect'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
        header('Location: ' . BASE_URL . ltrim($redirect, '/'));
        exit;
    }
}

/**
 * Guard page: Require specific role(s)
 * e.g., require_role(['super_admin', 'admin']) or require_role('agent')
 */
function require_role($roles, string $redirect = 'index.php'): void {
    require_auth('login.php');

    $curRole = get_authenticated_role();
    $allowed = is_array($roles) ? $roles : [$roles];

    if (!in_array($curRole, $allowed, true)) {
        header('Location: ' . BASE_URL . ltrim($redirect, '/'));
        exit;
    }
}

/**
 * CSRF Protection Helpers
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log in a user and set session
 */
function login_user(array $user): void {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role_name'] ?? 'customer';

    // If administrative role, also set admin session for admin panel access
    if (in_array($_SESSION['user_role'], ['super_admin', 'admin', 'agent'])) {
        $_SESSION['admin_user'] = [
            'id' => $user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $_SESSION['user_role'],
            'avatar' => $user['avatar'] ?? null
        ];
    }

    record_activity('User Login', "User #{$user['id']} ({$user['email']}) logged in.");
}

/**
 * Log out user and destroy session safely
 */
function logout_user(): void {
    $uid = $_SESSION['user_id'] ?? ($_SESSION['admin_user']['id'] ?? null);
    if ($uid) {
        record_activity('User Logout', "User #{$uid} logged out.");
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Record activity in activity_logs table
 */
function record_activity(string $action, ?string $details = null): bool {
    $db = Database::getConnection();
    if (!$db) return false;

    try {
        $userId = $_SESSION['user_id'] ?? ($_SESSION['admin_user']['id'] ?? null);
        $adminName = $_SESSION['user_name'] ?? ($_SESSION['admin_user']['name'] ?? 'System');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, admin_id, admin_name, action, details, ip_address, created_at)
            VALUES (:uid, :aid, :aname, :action, :details, :ip, NOW())
        ");
        return $stmt->execute([
            ':uid' => $userId,
            ':aid' => $userId,
            ':aname' => $adminName,
            ':action' => substr($action, 0, 100),
            ':details' => $details,
            ':ip' => $ip
        ]);
    } catch (PDOException $e) {
        error_log("Failed to record activity log: " . $e->getMessage());
        return false;
    }
}

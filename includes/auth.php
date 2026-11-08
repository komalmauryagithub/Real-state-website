<?php
/**
 * EstateVista India - Authentication & Session Management Module
 * Implements session security, password hashing (BCRYPT), password verification,
 * CSRF token generation & validation, server-side sanitization, and user authorization.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// -----------------------------------------------------------------------------
// 1. Session Lifecycle Initialization
// -----------------------------------------------------------------------------
function init_auth_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $is_https = (
            (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') ||
            (!empty($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], '"scheme":"https"'))
        );

        if (!headers_sent()) {
            $cookieParams = [
                'lifetime' => 86400 * 7, // 7 days
                'path'     => '/',
                'domain'   => '',
                'secure'   => $is_https,
                'httponly' => true,
                'samesite' => 'Lax'
            ];
            session_set_cookie_params($cookieParams);
            ini_set('session.use_strict_mode', '1');
        }
        @session_start();
    }
}

// Auto-initialize session
init_auth_session();

// -----------------------------------------------------------------------------
// 2. CSRF Token Protection
// -----------------------------------------------------------------------------
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

// -----------------------------------------------------------------------------
// 3. Authentication State Helpers
// -----------------------------------------------------------------------------
function is_logged_in(): bool {
    return !empty($_SESSION['ev_user_id']) && is_numeric($_SESSION['ev_user_id']);
}

function get_logged_in_user_id(): ?int {
    return is_logged_in() ? (int)$_SESSION['ev_user_id'] : null;
}

function get_logged_in_user(): ?array {
    $userId = get_logged_in_user_id();
    if (!$userId) {
        return null;
    }

    $db = Database::getConnection();
    if (!$db) {
        if (!empty($_SESSION['ev_user_id']) || !empty($_SESSION['user_id'])) {
            return [
                'id' => (int)($_SESSION['ev_user_id'] ?? $_SESSION['user_id']),
                'full_name' => $_SESSION['ev_user_name'] ?? 'Demo Client',
                'email' => $_SESSION['ev_user_email'] ?? 'client@estatevistaindia.com',
                'phone' => '+91 98200 99999',
                'role' => 'customer',
                'status' => 'active',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        return null;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND status = 'active' LIMIT 1");
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();
        if ($user) {
            unset($user['password_hash']);
            return $user;
        }
    } catch (PDOException $e) {
        error_log("Error in get_logged_in_user: " . $e->getMessage());
    }

    return null;
}

function login_user(array $user): void {
    // Regenerate session ID to mitigate session fixation attacks
    if (!headers_sent()) {
        session_regenerate_id(true);
    }

    $_SESSION['user_id']       = (int)$user['id'];
    $_SESSION['logged_in']     = true;
    $_SESSION['ev_user_id']    = (int)$user['id'];
    $_SESSION['ev_user_name']  = $user['full_name'];
    $_SESSION['ev_user_email'] = $user['email'];
    $_SESSION['ev_login_time'] = time();

    // Refresh CSRF token on login
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

function require_login(?string $redirect_target = null): void {
    if (!is_logged_in()) {
        $target = $redirect_target ?: $_SERVER['REQUEST_URI'];
        $_SESSION['redirect_after_login'] = $target;
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

// -----------------------------------------------------------------------------
// 4. Registration & Authentication Core Logic
// -----------------------------------------------------------------------------
function register_user(array $data): array {
    $db = Database::getConnection();
    if (!$db) {
        return ['success' => false, 'message' => 'Database connection unavailable.'];
    }

    $fullName  = trim($data['full_name'] ?? '');
    $email     = strtolower(trim($data['email'] ?? ''));
    $phone     = trim($data['phone'] ?? '');
    $password  = $data['password'] ?? '';
    $confirm   = $data['confirm_password'] ?? '';
    $city      = trim($data['city'] ?? 'Mumbai');
    $budget    = trim($data['preferred_budget'] ?? '₹ 7 Cr - ₹ 15 Cr');

    // Validation
    if (mb_strlen($fullName) < 3) {
        return ['success' => false, 'message' => 'Please provide your full legal name (minimum 3 characters).'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please provide a valid corporate or personal email address.'];
    }

    if (empty($phone) || strlen(preg_replace('/[^0-9]/', '', $phone)) < 10) {
        return ['success' => false, 'message' => 'Please provide a valid 10-digit mobile number (+91).'];
    }

    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters long for client account security.'];
    }

    if ($password !== $confirm) {
        return ['success' => false, 'message' => 'Password and confirmation password do not match.'];
    }

    try {
        // Check for duplicate email
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $checkStmt->bindValue(':email', $email, PDO::PARAM_STR);
        $checkStmt->execute();
        if ($checkStmt->fetch()) {
            return ['success' => false, 'message' => 'A client account with this email address already exists. Please sign in instead.'];
        }

        // Secure password hashing
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $defaultAvatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80';

        $stmt = $db->prepare("
            INSERT INTO users (full_name, email, phone, password_hash, city, preferred_budget, avatar, status)
            VALUES (:name, :email, :phone, :hash, :city, :budget, :avatar, 'active')
        ");
        $stmt->bindValue(':name', $fullName, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
        $stmt->bindValue(':hash', $passwordHash, PDO::PARAM_STR);
        $stmt->bindValue(':city', $city, PDO::PARAM_STR);
        $stmt->bindValue(':budget', $budget, PDO::PARAM_STR);
        $stmt->bindValue(':avatar', $defaultAvatar, PDO::PARAM_STR);
        $stmt->execute();

        $userId = (int)$db->lastInsertId();

        // Auto-login new user
        $newUser = [
            'id'        => $userId,
            'full_name' => $fullName,
            'email'     => $email
        ];
        login_user($newUser);

        return [
            'success' => true,
            'user_id' => $userId,
            'message' => 'Welcome to EstateVista India. Your private client account has been created.'
        ];
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create client account. Please try again or contact concierge.'];
    }
}

function authenticate_user(string $identity, string $password): array {
    $identity = trim($identity);
    $db = Database::getConnection();
    if (!$db) {
        // Fallback demo client login when database is not connected
        if (strtolower($identity) === 'client@estatevistaindia.com' && $password === 'Password@123') {
            $demo_user = [
                'id' => 1,
                'full_name' => 'Karan Malhotra',
                'email' => 'client@estatevistaindia.com',
                'phone' => '+91 98200 99999',
                'role' => 'customer',
                'status' => 'active',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80',
                'created_at' => date('Y-m-d H:i:s')
            ];
            login_user($demo_user);
            return [
                'success' => true,
                'user'    => $demo_user,
                'message' => 'Welcome back, ' . htmlspecialchars($demo_user['full_name'], ENT_QUOTES, 'UTF-8')
            ];
        }
        return ['success' => false, 'message' => 'Database connection unavailable.'];
    }

    $identity = trim($identity);
    if (empty($identity) || empty($password)) {
        return ['success' => false, 'message' => 'Please provide your registered email/phone and password.'];
    }

    try {
        // Allow login by email or phone
        $stmt = $db->prepare("
            SELECT * FROM users 
            WHERE (email = :id_email OR phone = :id_phone) AND status = 'active'
            LIMIT 1
        ");
        $stmt->bindValue(':id_email', strtolower($identity), PDO::PARAM_STR);
        $stmt->bindValue(':id_phone', $identity, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials. Please verify your email/phone and password.'];
        }

        // Verify hashed password
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid credentials. Please verify your email/phone and password.'];
        }

        // Login user
        login_user($user);

        return [
            'success' => true,
            'user'    => $user,
            'message' => 'Welcome back, ' . htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8')
        ];
    } catch (PDOException $e) {
        error_log("Authentication error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Authentication service error. Please try again.'];
    }
}

// -----------------------------------------------------------------------------
// 5. Password Reset Logic
// -----------------------------------------------------------------------------
function generate_password_reset_token(string $email): array {
    $db = Database::getConnection();
    if (!$db) return ['success' => false, 'message' => 'Database connection unavailable.'];

    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please provide a valid registered email address.'];
    }

    try {
        $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = :email AND status = 'active' LIMIT 1");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            // For security, do not disclose whether user exists, but indicate instructions dispatched
            return [
                'success' => true,
                'message' => 'If an active client account is associated with this email, password recovery instructions have been dispatched.'
            ];
        }

        // 64-char cryptographically secure token, valid for 1 hour
        $token = bin2hex(random_bytes(32));

        $update = $db->prepare("UPDATE users SET reset_token = :token, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = :id");
        $update->bindValue(':token', $token, PDO::PARAM_STR);
        $update->bindValue(':id', $user['id'], PDO::PARAM_INT);
        $update->execute();

        $resetUrl = BASE_URL . 'reset-password.php?token=' . $token;

        return [
            'success'   => true,
            'token'     => $token,
            'reset_url' => $resetUrl,
            'email'     => $email,
            'name'      => $user['full_name'],
            'message'   => 'Password recovery instructions prepared. Use the secure link to reset your password.'
        ];
    } catch (PDOException $e) {
        error_log("Password reset token error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error initiating password reset.'];
    }
}

function reset_password_with_token(string $token, string $newPassword, string $confirmPassword): array {
    $db = Database::getConnection();
    if (!$db) return ['success' => false, 'message' => 'Database connection unavailable.'];

    $token = trim($token);
    if (empty($token)) {
        return ['success' => false, 'message' => 'Invalid or missing password reset token.'];
    }

    if (strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters long.'];
    }

    if ($newPassword !== $confirmPassword) {
        return ['success' => false, 'message' => 'New password and confirmation password do not match.'];
    }

    try {
        $stmt = $db->prepare("
            SELECT id, email, full_name FROM users 
            WHERE reset_token = :token AND reset_token_expires > NOW() AND status = 'active'
            LIMIT 1
        ");
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'This password reset link is invalid or has expired. Please request a new recovery link.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        $updateStmt = $db->prepare("
            UPDATE users 
            SET password_hash = :hash, reset_token = NULL, reset_token_expires = NULL 
            WHERE id = :id
        ");
        $updateStmt->bindValue(':hash', $newHash, PDO::PARAM_STR);
        $updateStmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
        $updateStmt->execute();

        return [
            'success' => true,
            'message' => 'Your password has been securely updated. You can now sign in with your new credentials.'
        ];
    } catch (PDOException $e) {
        error_log("Reset password execution error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to reset password. Please try again.'];
    }
}

// -----------------------------------------------------------------------------
// 6. User Profile & Password Updates
// -----------------------------------------------------------------------------
function update_user_profile(int $userId, array $data): array {
    $db = Database::getConnection();
    if (!$db) return ['success' => false, 'message' => 'Database unavailable.'];

    $fullName = trim($data['full_name'] ?? '');
    $phone    = trim($data['phone'] ?? '');
    $city     = trim($data['city'] ?? '');
    $budget   = trim($data['preferred_budget'] ?? '');
    $bio      = trim($data['bio'] ?? '');
    $avatar   = trim($data['avatar'] ?? '');

    if (mb_strlen($fullName) < 3) {
        return ['success' => false, 'message' => 'Full name must be at least 3 characters.'];
    }

    try {
        if (!empty($avatar)) {
            $stmt = $db->prepare("
                UPDATE users 
                SET full_name = :name, phone = :phone, city = :city, 
                    preferred_budget = :budget, bio = :bio, avatar = :avatar
                WHERE id = :id
            ");
            $stmt->bindValue(':avatar', $avatar, PDO::PARAM_STR);
        } else {
            $stmt = $db->prepare("
                UPDATE users 
                SET full_name = :name, phone = :phone, city = :city, 
                    preferred_budget = :budget, bio = :bio
                WHERE id = :id
            ");
        }
        $stmt->bindValue(':name', $fullName, PDO::PARAM_STR);
        $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
        $stmt->bindValue(':city', $city, PDO::PARAM_STR);
        $stmt->bindValue(':budget', $budget, PDO::PARAM_STR);
        $stmt->bindValue(':bio', $bio, PDO::PARAM_STR);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $_SESSION['ev_user_name'] = $fullName;

        return ['success' => true, 'message' => 'Profile details updated successfully.'];
    } catch (PDOException $e) {
        error_log("Update profile error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update profile.'];
    }
}

function change_user_password(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): array {
    $db = Database::getConnection();
    if (!$db) return ['success' => false, 'message' => 'Database unavailable.'];

    if (strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters long.'];
    }

    if ($newPassword !== $confirmPassword) {
        return ['success' => false, 'message' => 'New password and confirmation do not match.'];
    }

    try {
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
            return ['success' => false, 'message' => 'Current password entered is incorrect.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $update = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $update->bindValue(':hash', $newHash, PDO::PARAM_STR);
        $update->bindValue(':id', $userId, PDO::PARAM_INT);
        $update->execute();

        return ['success' => true, 'message' => 'Your security password has been updated successfully.'];
    } catch (PDOException $e) {
        error_log("Change password error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to change password.'];
    }
}

// -----------------------------------------------------------------------------
// 7. Saved Properties (Wishlist) Operations
// -----------------------------------------------------------------------------
function toggle_user_saved_property(int $userId, int $propertyId): array {
    $db = Database::getConnection();
    if (!$db) return ['success' => false, 'message' => 'Database unavailable.'];

    try {
        $check = $db->prepare("SELECT id FROM saved_properties WHERE user_id = :uid AND property_id = :pid LIMIT 1");
        $check->bindValue(':uid', $userId, PDO::PARAM_INT);
        $check->bindValue(':pid', $propertyId, PDO::PARAM_INT);
        $check->execute();
        $existing = $check->fetch();

        if ($existing) {
            $del = $db->prepare("DELETE FROM saved_properties WHERE id = :id");
            $del->bindValue(':id', $existing['id'], PDO::PARAM_INT);
            $del->execute();
            return ['success' => true, 'action' => 'removed', 'message' => 'Residence removed from your saved portfolio.'];
        } else {
            $ins = $db->prepare("INSERT INTO saved_properties (user_id, property_id) VALUES (:uid, :pid)");
            $ins->bindValue(':uid', $userId, PDO::PARAM_INT);
            $ins->bindValue(':pid', $propertyId, PDO::PARAM_INT);
            $ins->execute();
            return ['success' => true, 'action' => 'added', 'message' => 'Residence saved to your private portfolio.'];
        }
    } catch (PDOException $e) {
        error_log("Toggle saved property error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update saved portfolio.'];
    }
}

function is_property_saved_by_user(int $userId, int $propertyId): bool {
    $db = Database::getConnection();
    if (!$db) return false;

    try {
        $stmt = $db->prepare("SELECT id FROM saved_properties WHERE user_id = :uid AND property_id = :pid LIMIT 1");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':pid', $propertyId, PDO::PARAM_INT);
        $stmt->execute();
        return (bool)$stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

function get_user_saved_properties(int $userId): array {
    $db = Database::getConnection();
    if (!$db) return [];

    try {
        $stmt = $db->prepare("
            SELECT p.*, c.name AS city_name, c.state AS city_state, c.slug AS city_slug,
                   cat.name AS category_name, cat.slug AS category_slug,
                   sp.created_at AS saved_at, sp.notes AS saved_notes
            FROM saved_properties sp
            JOIN properties p ON sp.property_id = p.id
            JOIN cities c ON p.city_id = c.id
            JOIN categories cat ON p.category_id = cat.id
            WHERE sp.user_id = :uid
            ORDER BY sp.created_at DESC
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get user saved properties error: " . $e->getMessage());
        return [];
    }
}

// -----------------------------------------------------------------------------
// 8. User Enquiries & Site Visits Operations
// -----------------------------------------------------------------------------
function get_user_inquiries(int $userId, string $userEmail = ''): array {
    $db = Database::getConnection();
    if (!$db) return [];

    try {
        $stmt = $db->prepare("
            SELECT inq.*, p.title AS property_title, p.slug AS property_slug, p.cover_image,
                   p.price_display, p.locality, c.name AS city_name
            FROM inquiries inq
            LEFT JOIN properties p ON inq.property_id = p.id
            LEFT JOIN cities c ON p.city_id = c.id
            WHERE inq.user_id = :uid " . (!empty($userEmail) ? "OR inq.email = :email" : "") . "
            ORDER BY inq.id DESC
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        if (!empty($userEmail)) {
            $stmt->bindValue(':email', $userEmail, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get user inquiries error: " . $e->getMessage());
        return [];
    }
}

function get_user_site_visits(int $userId, string $userEmail = ''): array {
    $db = Database::getConnection();
    if (!$db) return [];

    try {
        $stmt = $db->prepare("
            SELECT inq.*, p.title AS property_title, p.slug AS property_slug, p.cover_image,
                   p.price_display, p.locality, p.agent_name, p.agent_phone, c.name AS city_name
            FROM inquiries inq
            LEFT JOIN properties p ON inq.property_id = p.id
            LEFT JOIN cities c ON p.city_id = c.id
            WHERE (inq.user_id = :uid " . (!empty($userEmail) ? "OR inq.email = :email" : "") . ")
              AND inq.inquiry_type = 'site_visit'
            ORDER BY inq.visit_date DESC, inq.id DESC
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        if (!empty($userEmail)) {
            $stmt->bindValue(':email', $userEmail, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get user site visits error: " . $e->getMessage());
        return [];
    }
}

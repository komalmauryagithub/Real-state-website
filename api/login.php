<?php
/**
 * EstateVista India - Modal AJAX Authentication API
 * Verifies credentials, initializes session, and returns JSON status.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}

$identity = sanitize_input($input['identity'] ?? $input['login_identity'] ?? '');
$password = $input['password'] ?? $input['login_pass'] ?? '';

if (empty($identity) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide your registered email/phone and password.'
    ]);
    exit;
}

$authResult = authenticate_user($identity, $password);

if ($authResult['success']) {
    // Record login activity in activity_logs
    if (is_logged_in()) {
        $uid = get_logged_in_user_id();
        try {
            Database::execute("
                INSERT INTO activity_logs (user_id, action, details, ip_address, created_at)
                VALUES (:uid, 'login', 'Client logged in via portal modal', :ip, NOW())
            ", [
                ':uid' => $uid,
                ':ip'  => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (Exception $e) {
            // Ignore activity log failure
        }
    }

    echo json_encode([
        'success'  => true,
        'redirect' => BASE_URL . 'dashboard.php',
        'message'  => 'Welcome back to EstateVista Private Portal. Redirecting to your portfolio...'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $authResult['message']
    ]);
}

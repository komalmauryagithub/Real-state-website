<?php
/**
 * EstateVista India - Newsletter Subscription API
 * Handles subscriber opt-ins to confidential market intelligence reports.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
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

$email = sanitize_input($input['email'] ?? '');

if (!validate_required($email) || !validate_email($email)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid email address.'
    ]);
    exit;
}

try {
    $sql = "
        INSERT INTO newsletter_subscribers (email, status)
        VALUES (:email, 'active')
        ON DUPLICATE KEY UPDATE status = 'active'
    ";
    Database::execute($sql, [':email' => strtolower($email)]);

    // Notification for admin
    $notifSql = "
        INSERT INTO notifications (user_id, title, message, link, type, is_read)
        VALUES (NULL, 'New Newsletter Subscriber', :msg, 'admin/subscribers.php', 'info', 0)
    ";
    Database::execute($notifSql, [
        ':msg' => "Subscriber: {$email}"
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Thank you for subscribing to EstateVista Private Dossier. You will receive our monthly confidential luxury market report.'
    ]);
} catch (PDOException $e) {
    error_log("Newsletter subscription error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while saving your subscription. Please try again.'
    ]);
}

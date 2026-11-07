<?php
/**
 * EstateVista India - Favorites / Wishlist API
 * Synchronizes user shortlist with MySQL favorites (and saved_properties view).
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

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

$propertyId = (int)($input['property_id'] ?? 0);

if ($propertyId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid property identifier.']);
    exit;
}

if (!is_logged_in()) {
    echo json_encode([
        'success'    => false,
        'need_login' => true,
        'message'    => 'Please sign in to save residences to your private portfolio.'
    ]);
    exit;
}

$userId = get_logged_in_user_id();

try {
    // Check if favorite exists
    $existing = Database::fetch("SELECT id FROM favorites WHERE user_id = :uid AND property_id = :pid LIMIT 1", [
        ':uid' => $userId,
        ':pid' => $propertyId
    ]);

    if ($existing) {
        Database::execute("DELETE FROM favorites WHERE id = :id", [':id' => $existing['id']]);
        $isFavorite = false;
        $action = 'removed';
        $message = 'Residence removed from your private portfolio.';
    } else {
        Database::execute("INSERT INTO favorites (user_id, property_id) VALUES (:uid, :pid)", [
            ':uid' => $userId,
            ':pid' => $propertyId
        ]);
        $isFavorite = true;
        $action = 'added';
        $message = 'Residence saved to your private portfolio.';
    }

    $totalCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM favorites WHERE user_id = :uid", [':uid' => $userId]);

    echo json_encode([
        'success'     => true,
        'is_favorite' => $isFavorite,
        'action'      => $action,
        'count'       => $totalCount,
        'message'     => $message
    ]);
} catch (PDOException $e) {
    error_log("Favorite API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Unable to update portfolio shortlist at this time.'
    ]);
}

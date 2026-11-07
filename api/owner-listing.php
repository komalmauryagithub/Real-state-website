<?php
/**
 * EstateVista India - Owner Evaluation & Property Listing API
 * Handles property submission requests from owners/representatives.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';

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

$name = sanitize_input($input['owner_name'] ?? '');
$phone = sanitize_input($input['owner_phone'] ?? '');
$email = sanitize_input($input['owner_email'] ?? '');
$propType = sanitize_input($input['owner_type'] ?? 'Luxury Apartment');
$cityName = sanitize_input($input['owner_city'] ?? 'Mumbai');
$priceRaw = sanitize_input($input['owner_price'] ?? '');
$details = sanitize_input($input['owner_details'] ?? '');

$errors = [];
if (!validate_required($name) || mb_strlen($name) < 2) {
    $errors['owner_name'] = 'Please enter your name.';
}
if (!validate_required($phone) || strlen(preg_replace('/[^0-9]/', '', $phone)) < 10) {
    $errors['owner_phone'] = 'Please enter a valid 10-digit phone number.';
}
if (!validate_required($email) || !validate_email($email)) {
    $errors['owner_email'] = 'Please enter a valid email address.';
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => reset($errors),
        'errors'  => $errors
    ]);
    exit;
}

// Parse price
$priceNumeric = 0.0;
$cleanedPrice = preg_replace('/[^0-9.]/', '', $priceRaw);
if (is_numeric($cleanedPrice)) {
    $pFloat = (float)$cleanedPrice;
    if (stripos($priceRaw, 'cr') !== false) {
        $priceNumeric = $pFloat * 10000000;
    } elseif (stripos($priceRaw, 'lakh') !== false || stripos($priceRaw, 'lac') !== false) {
        $priceNumeric = $pFloat * 100000;
    } else {
        $priceNumeric = $pFloat;
    }
}
if ($priceNumeric <= 0) {
    $priceNumeric = 150000000; // 15 Cr default evaluation
}

// Resolve City ID
$cityRow = Database::fetch("SELECT id FROM cities WHERE name LIKE :name LIMIT 1", [':name' => '%' . $cityName . '%']);
$cityId = $cityRow ? (int)$cityRow['id'] : 1;

// Resolve Category ID
$catRow = Database::fetch("SELECT id FROM property_categories WHERE name LIKE :name LIMIT 1", [':name' => '%' . $propType . '%']);
$categoryId = $catRow ? (int)$catRow['id'] : 1;

$userId = is_logged_in() ? get_logged_in_user_id() : null;
$title = "{$propType} in {$cityName}";
$slug = slugify($title) . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
$priceDisplay = format_inr_price($priceNumeric);

try {
    Database::beginTransaction();

    // 1. Insert into properties table with pending approval
    $propSql = "
        INSERT INTO properties (
            user_id, title, slug, subtitle, description, category_id, city_id,
            locality, address, price, price_display, price_type, purpose, bhk,
            bathrooms, carpet_area_sqft, status, possession_date, rera_number,
            approval_status, cover_image, contact_person_name, contact_person_phone,
            contact_person_email, owner_role
        ) VALUES (
            :user_id, :title, :slug, :subtitle, :description, :cat_id, :city_id,
            :locality, :address, :price, :price_display, 'sale', 'buy', 4,
            4, 4500, 'ready_to_move', 'Ready for Evaluation', 'Verification in Progress',
            'pending', 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80',
            :contact_name, :contact_phone, :contact_email, 'owner'
        )
    ";
    Database::execute($propSql, [
        ':user_id'       => $userId,
        ':title'         => $title,
        ':slug'          => $slug,
        ':subtitle'      => "Private Owner Residence Evaluation - {$cityName}",
        ':description'   => $details ?: "Exclusive residence submitted for private evaluation by owner. Expected valuation: {$priceDisplay}.",
        ':cat_id'        => $categoryId,
        ':city_id'       => $cityId,
        ':locality'      => $cityName . ' Prime',
        ':address'       => "Confidential Property Location, {$cityName}",
        ':price'         => $priceNumeric,
        ':price_display' => $priceDisplay,
        ':contact_name'  => $name,
        ':contact_phone' => $phone,
        ':contact_email' => $email
    ]);
    $propertyId = (int)Database::lastInsertId();

    // 2. Insert into leads table
    $leadSql = "
        INSERT INTO leads (
            user_id, property_id, full_name, email, phone, preferred_city,
            budget_range, deal_value, lead_source, lead_status, notes
        ) VALUES (
            :uid, :pid, :name, :email, :phone, :city,
            :budget, :deal_val, 'Owner Evaluation Request', 'new', :notes
        )
    ";
    Database::execute($leadSql, [
        ':uid'      => $userId,
        ':pid'      => $propertyId,
        ':name'     => $name,
        ':email'    => $email,
        ':phone'    => $phone,
        ':city'     => $cityName,
        ':budget'   => $priceDisplay,
        ':deal_val' => $priceNumeric,
        ':notes'    => "Owner evaluation request: " . ($details ?: "Expected {$priceDisplay}")
    ]);

    // 3. Insert into enquiries table
    $enqSql = "
        INSERT INTO enquiries (
            user_id, property_id, enquiry_type, full_name, email, phone,
            city, budget_range, message, status
        ) VALUES (
            :uid, :pid, 'valuation', :name, :email, :phone,
            :city, :budget, :msg, 'new'
        )
    ";
    Database::execute($enqSql, [
        ':uid'    => $userId,
        ':pid'    => $propertyId,
        ':name'   => $name,
        ':email'  => $email,
        ':phone'  => $phone,
        ':city'   => $cityName,
        ':budget' => $priceDisplay,
        ':msg'    => $details ?: "Owner listing valuation request for {$propType}"
    ]);

    // 4. Compatibility inquiries
    $inqSql = "
        INSERT INTO inquiries (
            user_id, property_id, inquiry_type, full_name, email, phone,
            city, budget_range, message, status, lead_status, lead_source
        ) VALUES (
            :uid, :pid, 'valuation', :name, :email, :phone,
            :city, :budget, :msg, 'new', 'new', 'Owner Evaluation Request'
        )
    ";
    Database::execute($inqSql, [
        ':uid'    => $userId,
        ':pid'    => $propertyId,
        ':name'   => $name,
        ':email'  => $email,
        ':phone'  => $phone,
        ':city'   => $cityName,
        ':budget' => $priceDisplay,
        ':msg'    => $details ?: "Owner listing valuation request for {$propType}"
    ]);

    // 5. Admin Notification
    $notifSql = "
        INSERT INTO notifications (user_id, title, message, link, type, is_read)
        VALUES (NULL, :title, :msg, 'admin/approvals.php', 'lead', 0)
    ";
    Database::execute($notifSql, [
        ':title' => "New Property Evaluation: {$title}",
        ':msg'   => "Submitted by {$name} ({$phone}) with expected valuation {$priceDisplay}."
    ]);

    // 6. Activity log
    $logSql = "
        INSERT INTO activity_logs (user_id, action, details, ip_address, created_at)
        VALUES (:uid, 'submit_evaluation', :details, :ip, NOW())
    ";
    Database::execute($logSql, [
        ':uid'     => $userId,
        ':details' => "Owner listing evaluation submitted: {$title} by {$name}",
        ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ]);

    Database::commit();

    echo json_encode([
        'success'     => true,
        'property_id' => $propertyId,
        'message'     => 'Listing submission received. Our Senior Property Evaluation Partner will contact you within 24 hours under NDA.'
    ]);
} catch (PDOException $e) {
    Database::rollBack();
    error_log("Owner listing API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Unable to submit property evaluation at this time. Please contact advisory directly.'
    ]);
}

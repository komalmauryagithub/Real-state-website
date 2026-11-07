<?php
/**
 * EstateVista India - Inquiry & VIP Consultation API
 * Handles property inquiries, callback requests, and VIP consultations.
 * Synchronizes with enquiries, leads, site_visits, and notifications tables.
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

// Read JSON or POST body
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}

$fullName = sanitize_input($input['full_name'] ?? '');
$email = sanitize_input($input['email'] ?? '');
$phone = sanitize_input($input['phone'] ?? '');
$city = sanitize_input($input['city'] ?? '');
$budget = sanitize_input($input['budget_range'] ?? $input['budget'] ?? '');
$preferredTime = sanitize_input($input['preferred_time'] ?? '');
$message = sanitize_input($input['message'] ?? $input['notes'] ?? '');
$propertyInterest = sanitize_input($input['property_interest'] ?? '');
$propertyId = !empty($input['property_id']) ? (int)$input['property_id'] : null;
$inquiryType = sanitize_input($input['inquiry_type'] ?? 'enquiry');
$visitDate = !empty($input['visit_date']) ? sanitize_input($input['visit_date']) : null;
$visitTime = sanitize_input($input['visit_time'] ?? '');

$allowedTypes = ['enquiry', 'callback', 'valuation', 'brochure', 'general', 'site_visit'];
if (!in_array($inquiryType, $allowedTypes)) {
    $inquiryType = 'enquiry';
}

// Validation
$errors = [];
if (!validate_required($fullName) || mb_strlen($fullName) < 2) {
    $errors['full_name'] = 'Please enter your legal name.';
}
if (!validate_required($phone) || strlen(preg_replace('/[^0-9]/', '', $phone)) < 10) {
    $errors['phone'] = 'Please enter a valid 10-digit telephone number.';
}
if ($inquiryType !== 'callback' && !empty($email) && !validate_email($email)) {
    $errors['email'] = 'Please enter a valid email address.';
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => reset($errors),
        'errors'  => $errors
    ]);
    exit;
}

$userId = is_logged_in() ? get_logged_in_user_id() : null;

// Resolve assigned agent if property is provided
$assignedAgentId = null;
if ($propertyId) {
    $propAgent = Database::fetch("SELECT assigned_agent_id FROM properties WHERE id = :pid LIMIT 1", [':pid' => $propertyId]);
    if ($propAgent && !empty($propAgent['assigned_agent_id'])) {
        $assignedAgentId = (int)$propAgent['assigned_agent_id'];
    }
}

if (!$assignedAgentId) {
    // Default to first senior advisor in agents table
    $defaultAgent = Database::fetch("SELECT id FROM agents WHERE status = 'active' ORDER BY id ASC LIMIT 1");
    if ($defaultAgent) {
        $assignedAgentId = (int)$defaultAgent['id'];
    }
}

if (empty($message) && !empty($propertyInterest)) {
    $message = "Interested in: " . $propertyInterest;
}

try {
    Database::beginTransaction();

    // 1. Insert into enquiries table (primary relational table)
    $enqSql = "
        INSERT INTO enquiries (
            user_id, property_id, assigned_agent_id, enquiry_type,
            full_name, email, phone, city, budget_range, preferred_time,
            message, status
        ) VALUES (
            :user_id, :property_id, :agent_id, :type,
            :name, :email, :phone, :city, :budget, :time,
            :msg, 'new'
        )
    ";
    Database::execute($enqSql, [
        ':user_id'     => $userId,
        ':property_id' => $propertyId,
        ':agent_id'    => $assignedAgentId,
        ':type'        => $inquiryType,
        ':name'        => $fullName,
        ':email'       => $email ?: 'confidential@client.internal',
        ':phone'       => $phone,
        ':city'        => $city ?: null,
        ':budget'      => $budget ?: null,
        ':time'        => $preferredTime ?: null,
        ':msg'         => $message ?: null
    ]);
    $enquiryId = (int)Database::lastInsertId();

    // 2. If inquiry is site_visit, insert into site_visits table
    if ($inquiryType === 'site_visit' && $propertyId && $visitDate) {
        $visitSql = "
            INSERT INTO site_visits (
                user_id, property_id, assigned_agent_id, client_name, client_email,
                client_phone, visit_date, visit_time, special_notes, status
            ) VALUES (
                :user_id, :property_id, :agent_id, :name, :email,
                :phone, :vdate, :vtime, :notes, 'pending'
            )
        ";
        Database::execute($visitSql, [
            ':user_id'     => $userId,
            ':property_id' => $propertyId,
            ':agent_id'    => $assignedAgentId,
            ':name'        => $fullName,
            ':email'       => $email ?: 'confidential@client.internal',
            ':phone'       => $phone,
            ':vdate'       => $visitDate,
            ':vtime'       => $visitTime ?: '11:00 AM',
            ':notes'       => $message ?: 'VIP site visit scheduled via web portal'
        ]);
    }

    // 3. Sync CRM Leads table
    $leadSql = "
        INSERT INTO leads (
            user_id, property_id, assigned_agent_id, full_name, email,
            phone, preferred_city, budget_range, lead_source, lead_status,
            notes
        ) VALUES (
            :user_id, :property_id, :agent_id, :name, :email,
            :phone, :city, :budget, :source, 'new',
            :notes
        )
    ";
    $source = ($inquiryType === 'callback') 
        ? 'Website Callback Request' 
        : (($inquiryType === 'site_visit') ? 'VIP Site Visit Request' : 'Website VIP Consultation');

    Database::execute($leadSql, [
        ':user_id'     => $userId,
        ':property_id' => $propertyId,
        ':agent_id'    => $assignedAgentId,
        ':name'        => $fullName,
        ':email'       => $email ?: null,
        ':phone'       => $phone,
        ':city'        => $city ?: null,
        ':budget'      => $budget ?: null,
        ':source'      => $source,
        ':notes'       => $message ?: 'Inquiry ID #' . $enquiryId
    ]);

    // 4. Insert into compatibility inquiries table
    $inqCompatSql = "
        INSERT INTO inquiries (
            user_id, property_id, inquiry_type, full_name, email, phone, city,
            budget_range, preferred_time, visit_date, visit_time, message, status,
            assigned_agent_id, lead_status, lead_source
        ) VALUES (
            :user_id, :property_id, :type, :name, :email, :phone, :city,
            :budget, :time, :vdate, :vtime, :msg, 'new',
            :agent_id, 'new', :source
        )
    ";
    Database::execute($inqCompatSql, [
        ':user_id'     => $userId,
        ':property_id' => $propertyId,
        ':type'        => $inquiryType,
        ':name'        => $fullName,
        ':email'       => $email ?: 'confidential@client.internal',
        ':phone'       => $phone,
        ':city'        => $city ?: null,
        ':budget'      => $budget ?: null,
        ':time'        => $preferredTime ?: null,
        ':vdate'       => $visitDate,
        ':vtime'       => $visitTime ?: null,
        ':msg'         => $message ?: null,
        ':agent_id'    => $assignedAgentId,
        ':source'      => $source
    ]);

    // 5. Insert notification for advisory desk
    $notifSql = "
        INSERT INTO notifications (user_id, title, message, link, type, is_read)
        VALUES (NULL, :title, :body, :link, 'enquiry', 0)
    ";
    Database::execute($notifSql, [
        ':title' => "New VIP Consultation: {$fullName}",
        ':body'  => "Inquiry for " . ($propertyInterest ?: $city ?: 'Prime Properties') . " from {$phone}",
        ':link'  => "admin/enquiries.php"
    ]);

    // 6. Record in activity_logs
    $logSql = "
        INSERT INTO activity_logs (user_id, action, details, ip_address, created_at)
        VALUES (:uid, 'create_inquiry', :details, :ip, NOW())
    ";
    Database::execute($logSql, [
        ':uid'     => $userId,
        ':details' => "VIP Consultation #{$enquiryId} submitted by {$fullName} ({$phone})",
        ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ]);

    Database::commit();

    echo json_encode([
        'success' => true,
        'id'      => $enquiryId,
        'message' => 'Private dossier registered. Our Senior Acquisition Partner will contact you discreetly within 2 hours.'
    ]);
} catch (PDOException $e) {
    Database::rollBack();
    error_log("Inquiry submission error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Unable to save inquiry at this moment. Please connect with our concierge directly at ' . SITE_PHONE
    ]);
}

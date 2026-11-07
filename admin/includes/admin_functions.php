<?php
/**
 * EstateVista India - Admin Helper Functions
 * Analytics, reporting, activity tracking, and system configurations.
 */

require_once __DIR__ . '/admin_auth.php';

if (!function_exists('e')) {
    function e($string): string {
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitize')) {
    function sanitize(?string $data): string {
        if ($data === null) return '';
        return trim(strip_tags($data));
    }
}

/**
 * Fetch overall executive KPIs for the dashboard
 */
function get_admin_dashboard_stats(): array {
    $db = Database::getConnection();
    $stats = [
        'total_properties' => 0,
        'approved_properties' => 0,
        'pending_approvals' => 0,
        'rejected_properties' => 0,
        'total_portfolio_value_cr' => 0.0,
        'total_users' => 0,
        'total_agents' => 0,
        'total_owners' => 0,
        'total_inquiries' => 0,
        'new_inquiries' => 0,
        'site_visits' => 0,
        'subscribers' => 0,
        'total_articles' => 0
    ];

    if (!$db) return $stats;

    try {
        // Properties breakdown
        $propStmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN approval_status = 'approved' THEN price ELSE 0 END) as total_val
            FROM properties
        ");
        $p = $propStmt->fetch(PDO::FETCH_ASSOC);
        if ($p) {
            $stats['total_properties'] = (int)$p['total'];
            $stats['approved_properties'] = (int)$p['approved'];
            $stats['pending_approvals'] = (int)$p['pending'];
            $stats['rejected_properties'] = (int)$p['rejected'];
            $stats['total_portfolio_value_cr'] = round((float)$p['total_val'] / 10000000, 2);
        }

        // Users count
        $stats['total_users'] = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();

        // Agents count
        $stats['total_agents'] = (int)$db->query("SELECT COUNT(*) FROM admins WHERE role = 'agent'")->fetchColumn();

        // Property Owners count (users with submitted properties)
        $stats['total_owners'] = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM properties WHERE user_id IS NOT NULL")->fetchColumn();

        // Inquiries breakdown
        $inqStmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'new' OR lead_status = 'new' THEN 1 ELSE 0 END) as new_count,
                SUM(CASE WHEN inquiry_type = 'site_visit' THEN 1 ELSE 0 END) as visit_count
            FROM inquiries
        ");
        $iq = $inqStmt->fetch(PDO::FETCH_ASSOC);
        if ($iq) {
            $stats['total_inquiries'] = (int)$iq['total'];
            $stats['new_inquiries'] = (int)$iq['new_count'];
            $stats['site_visits'] = (int)$iq['visit_count'];
        }

        // Newsletter subscribers
        $stats['subscribers'] = (int)$db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'")->fetchColumn();

        // Articles count
        $stats['total_articles'] = (int)$db->query("SELECT COUNT(*) FROM articles")->fetchColumn();

    } catch (PDOException $e) {
        error_log("Error in get_admin_dashboard_stats: " . $e->getMessage());
    }

    return $stats;
}

/**
 * Fetch recent activity logs
 */
function get_admin_recent_activities(int $limit = 8): array {
    $db = Database::getConnection();
    if (!$db) return [];

    try {
        $stmt = $db->prepare("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_admin_recent_activities: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetch recent inquiries for dashboard
 */
function get_admin_recent_inquiries(int $limit = 5): array {
    $db = Database::getConnection();
    if (!$db) return [];

    try {
        $stmt = $db->prepare("
            SELECT i.*, p.title as property_title, p.slug as property_slug,
                   adm.name as agent_name
            FROM inquiries i
            LEFT JOIN properties p ON i.property_id = p.id
            LEFT JOIN admins adm ON i.assigned_agent_id = adm.id
            ORDER BY i.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_admin_recent_inquiries: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetch pending properties queue for dashboard and approvals
 */
function get_admin_pending_properties(int $limit = 10): array {
    $db = Database::getConnection();
    if (!$db) return [];

    try {
        $stmt = $db->prepare("
            SELECT p.*, c.name as city_name, cat.name as category_name, u.full_name as submitter_name, u.email as submitter_email
            FROM properties p
            LEFT JOIN cities c ON p.city_id = c.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.approval_status = 'pending'
            ORDER BY p.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_admin_pending_properties: " . $e->getMessage());
        return [];
    }
}

/**
 * Get Chart Data: Properties Distribution by City
 */
function get_chart_properties_by_city(): array {
    $db = Database::getConnection();
    if (!$db) return [];

    try {
        $stmt = $db->query("
            SELECT c.name as city_name, COUNT(p.id) as property_count
            FROM cities c
            LEFT JOIN properties p ON c.id = p.city_id AND p.approval_status = 'approved'
            GROUP BY c.id
            ORDER BY property_count DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_chart_properties_by_city: " . $e->getMessage());
        return [];
    }
}

/**
 * Get Chart Data: Inquiries Breakdown by Lead Status
 */
function get_chart_inquiries_by_status(): array {
    $db = Database::getConnection();
    if (!$db) return [];

    try {
        $stmt = $db->query("
            SELECT COALESCE(lead_status, 'new') as status_name, COUNT(*) as count
            FROM inquiries
            GROUP BY lead_status
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_chart_inquiries_by_status: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetch all agents and admins for assignment dropdown
 */
function get_all_assignable_agents(): array {
    $db = Database::getConnection();
    if (!$db) return [];

    try {
        $stmt = $db->query("SELECT id, name, role, email, phone FROM admins WHERE status = 'active' ORDER BY role ASC, name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_all_assignable_agents: " . $e->getMessage());
        return [];
    }
}

/**
 * Retrieve setting from database
 */
function get_system_setting(string $key, string $default = ''): string {
    $db = Database::getConnection();
    if (!$db) return $default;

    try {
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = :k LIMIT 1");
        $stmt->execute([':k' => $key]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null) ? (string)$val : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Update or insert setting in database
 */
function update_system_setting(string $key, ?string $value, string $group = 'general'): bool {
    $db = Database::getConnection();
    if (!$db) return false;

    try {
        $stmt = $db->prepare("
            INSERT INTO settings (setting_key, setting_value, setting_group)
            VALUES (:k, :v, :g)
            ON DUPLICATE KEY UPDATE setting_value = :v, setting_group = :g
        ");
        return $stmt->execute([':k' => $key, ':v' => $value, ':g' => $group]);
    } catch (PDOException $e) {
        error_log("Error in update_system_setting: " . $e->getMessage());
        return false;
    }
}

/**
 * Export data array to streaming CSV download
 */
function export_to_csv(string $filename, array $headers, array $rows): void {
    if (ob_get_level()) ob_end_clean();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // Output UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Headers
    fputcsv($output, $headers);

    // Rows
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/**
 * Format timestamp nicely for admin interface
 */
function format_admin_date(?string $datetime, bool $showTime = true): string {
    if (empty($datetime)) return '—';
    $time = strtotime($datetime);
    if (!$time) return '—';
    return $showTime ? date('d M Y, h:i A', $time) : date('d M Y', $time);
}

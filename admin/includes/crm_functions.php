<?php
/**
 * EstateVista India - Real Estate Agent CRM Core Engine & Helper Functions
 * Analytics, pipeline distribution, interaction timelines, and follow-up reminders.
 */

require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/admin_functions.php';

/**
 * Fetch comprehensive Agent CRM KPIs & conversion statistics
 */
function get_crm_agent_stats(?int $agentId = null): array {
    $db = Database::getConnection();
    $stats = [
        'assigned_leads' => 0,
        'new_enquiries' => 0,
        'active_leads' => 0,
        'converted_leads' => 0,
        'lost_leads' => 0,
        'conversion_rate' => 0.0,
        'total_pipeline_val_cr' => 0.0,
        'total_converted_val_cr' => 0.0,
        'reminders_overdue' => 0,
        'reminders_today' => 0,
        'reminders_upcoming' => 0,
        'reminders_total_pending' => 0,
        'site_visits_upcoming' => 0,
        'assigned_properties' => 0,
        'assigned_properties_val_cr' => 0.0
    ];

    if (!$db) return $stats;

    try {
        // 1. Leads & Pipeline breakdown
        $leadSql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN lead_status = 'new' THEN 1 ELSE 0 END) as new_cnt,
                SUM(CASE WHEN lead_status IN ('contacted', 'interested', 'site_visit_scheduled', 'negotiation') THEN 1 ELSE 0 END) as active_cnt,
                SUM(CASE WHEN lead_status = 'converted' THEN 1 ELSE 0 END) as converted_cnt,
                SUM(CASE WHEN lead_status = 'lost' THEN 1 ELSE 0 END) as lost_cnt,
                SUM(CASE WHEN lead_status IN ('contacted', 'interested', 'site_visit_scheduled', 'negotiation', 'new') THEN COALESCE(deal_value, 0) ELSE 0 END) as active_val,
                SUM(CASE WHEN lead_status = 'converted' THEN COALESCE(deal_value, 0) ELSE 0 END) as won_val
            FROM inquiries
            WHERE 1=1
        ";
        if ($agentId !== null) {
            $leadSql .= " AND assigned_agent_id = " . (int)$agentId;
        }

        $res = $db->query($leadSql)->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $stats['assigned_leads'] = (int)$res['total'];
            $stats['new_enquiries'] = (int)$res['new_cnt'];
            $stats['active_leads'] = (int)$res['active_cnt'];
            $stats['converted_leads'] = (int)$res['converted_cnt'];
            $stats['lost_leads'] = (int)$res['lost_cnt'];
            $stats['total_pipeline_val_cr'] = round(((float)$res['active_val']) / 10000000, 2);
            $stats['total_converted_val_cr'] = round(((float)$res['won_val']) / 10000000, 2);

            $closedPool = $stats['converted_leads'] + $stats['lost_leads'];
            if ($closedPool > 0) {
                $stats['conversion_rate'] = round(($stats['converted_leads'] / $closedPool) * 100, 1);
            } elseif ($stats['assigned_leads'] > 0) {
                $stats['conversion_rate'] = round(($stats['converted_leads'] / $stats['assigned_leads']) * 100, 1);
            }
        }

        // 2. Reminders breakdown
        $remSql = "
            SELECT 
                SUM(CASE WHEN remind_at < NOW() AND status = 'pending' THEN 1 ELSE 0 END) as overdue_cnt,
                SUM(CASE WHEN DATE(remind_at) = CURDATE() AND status = 'pending' THEN 1 ELSE 0 END) as today_cnt,
                SUM(CASE WHEN remind_at > NOW() AND DATE(remind_at) > CURDATE() AND status = 'pending' THEN 1 ELSE 0 END) as upcoming_cnt,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_cnt
            FROM crm_reminders
            WHERE 1=1
        ";
        if ($agentId !== null) {
            $remSql .= " AND agent_id = " . (int)$agentId;
        }
        $rem = $db->query($remSql)->fetch(PDO::FETCH_ASSOC);
        if ($rem) {
            $stats['reminders_overdue'] = (int)($rem['overdue_cnt'] ?? 0);
            $stats['reminders_today'] = (int)($rem['today_cnt'] ?? 0);
            $stats['reminders_upcoming'] = (int)($rem['upcoming_cnt'] ?? 0);
            $stats['reminders_total_pending'] = (int)($rem['pending_cnt'] ?? 0);
        }

        // 3. Upcoming Site Visits
        $visSql = "
            SELECT COUNT(*) 
            FROM inquiries 
            WHERE inquiry_type = 'site_visit' 
              AND visit_date >= CURDATE() 
              AND COALESCE(status, 'new') != 'closed'
        ";
        if ($agentId !== null) {
            $visSql .= " AND assigned_agent_id = " . (int)$agentId;
        }
        $stats['site_visits_upcoming'] = (int)$db->query($visSql)->fetchColumn();

        // 4. Assigned Luxury Properties Portfolio
        if ($agentId !== null) {
            $propSql = "
                SELECT COUNT(*) as total_props, SUM(price) as total_val
                FROM properties
                WHERE assigned_agent_id = " . (int)$agentId . " AND approval_status = 'approved'
            ";
            $pr = $db->query($propSql)->fetch(PDO::FETCH_ASSOC);
            if ($pr) {
                $stats['assigned_properties'] = (int)$pr['total_props'];
                $stats['assigned_properties_val_cr'] = round(((float)$pr['total_val']) / 10000000, 2);
            }
        } else {
            $stats['assigned_properties'] = (int)$db->query("SELECT COUNT(*) FROM properties WHERE approval_status = 'approved'")->fetchColumn();
            $totVal = (float)$db->query("SELECT SUM(price) FROM properties WHERE approval_status = 'approved'")->fetchColumn();
            $stats['assigned_properties_val_cr'] = round($totVal / 10000000, 2);
        }

    } catch (PDOException $e) {
        error_log("Error in get_crm_agent_stats: " . $e->getMessage());
    }

    return $stats;
}

/**
 * Fetch CRM Leads with multi-dimensional filtering
 */
function get_crm_leads(array $filters = []): array {
    $db = Database::getConnection();
    if (!$db) return [];

    $sql = "
        SELECT i.*, 
               p.title as property_title, p.slug as property_slug, p.locality as property_locality,
               p.price_display as property_price, p.cover_image as property_image,
               adm.name as agent_name, adm.avatar as agent_avatar, adm.phone as agent_phone,
               (SELECT COUNT(*) FROM lead_notes ln WHERE ln.lead_id = i.id) as notes_count,
               (SELECT MIN(remind_at) FROM crm_reminders cr WHERE cr.lead_id = i.id AND cr.status = 'pending') as next_reminder
        FROM inquiries i
        LEFT JOIN properties p ON i.property_id = p.id
        LEFT JOIN admins adm ON i.assigned_agent_id = adm.id
        WHERE 1=1
    ";
    $params = [];

    // Filter by Agent
    if (!empty($filters['agent_id'])) {
        if ($filters['agent_id'] === 'unassigned') {
            $sql .= " AND i.assigned_agent_id IS NULL";
        } else {
            $sql .= " AND i.assigned_agent_id = :agent_id";
            $params[':agent_id'] = (int)$filters['agent_id'];
        }
    }

    // Filter by Pipeline Stage
    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $sql .= " AND i.lead_status = :status";
        $params[':status'] = $filters['status'];
    }

    // Filter by Preferred City
    if (!empty($filters['city'])) {
        $sql .= " AND (i.preferred_city = :city OR i.city = :city2)";
        $params[':city'] = $filters['city'];
        $params[':city2'] = $filters['city'];
    }

    // Filter by Lead Source
    if (!empty($filters['source'])) {
        $sql .= " AND i.lead_source = :source";
        $params[':source'] = $filters['source'];
    }

    // Filter by Follow-up status
    if (!empty($filters['follow_up'])) {
        if ($filters['follow_up'] === 'overdue') {
            $sql .= " AND i.follow_up_date < NOW() AND i.lead_status NOT IN ('converted', 'lost')";
        } elseif ($filters['follow_up'] === 'today') {
            $sql .= " AND DATE(i.follow_up_date) = CURDATE()";
        } elseif ($filters['follow_up'] === 'upcoming') {
            $sql .= " AND i.follow_up_date > NOW()";
        }
    }

    // Search query
    if (!empty($filters['search'])) {
        $term = "%" . trim($filters['search']) . "%";
        $sql .= " AND (i.full_name LIKE :s1 OR i.email LIKE :s2 OR i.phone LIKE :s3 OR p.title LIKE :s4 OR i.city LIKE :s5)";
        $params[':s1'] = $term;
        $params[':s2'] = $term;
        $params[':s3'] = $term;
        $params[':s4'] = $term;
        $params[':s5'] = $term;
    }

    $sql .= " ORDER BY i.created_at DESC";

    if (!empty($filters['limit'])) {
        $sql .= " LIMIT " . (int)$filters['limit'];
    }

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_crm_leads: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetch complete dossier for a single lead
 */
function get_crm_lead_by_id(int $leadId): ?array {
    $db = Database::getConnection();
    if (!$db) return null;

    try {
        $stmt = $db->prepare("
            SELECT i.*, 
                   p.title as property_title, p.slug as property_slug, p.locality as property_locality,
                   p.price_display as property_price, p.cover_image as property_image, p.price as property_raw_price,
                   p.bhk as property_bhk, p.carpet_area_sqft, c.name as property_city,
                   adm.name as agent_name, adm.avatar as agent_avatar, adm.phone as agent_phone, adm.email as agent_email
            FROM inquiries i
            LEFT JOIN properties p ON i.property_id = p.id
            LEFT JOIN cities c ON p.city_id = c.id
            LEFT JOIN admins adm ON i.assigned_agent_id = adm.id
            WHERE i.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $leadId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (PDOException $e) {
        error_log("Error in get_crm_lead_by_id: " . $e->getMessage());
        return null;
    }
}

/**
 * Fetch interaction timeline notes for a lead
 */
function get_crm_lead_notes(int $leadId): array {
    $db = Database::getConnection();
    if (!$db) return [];

    try {
        $stmt = $db->prepare("
            SELECT ln.*, adm.avatar as agent_avatar
            FROM lead_notes ln
            LEFT JOIN admins adm ON ln.agent_id = adm.id
            WHERE ln.lead_id = :lid
            ORDER BY ln.created_at DESC
        ");
        $stmt->execute([':lid' => $leadId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_crm_lead_notes: " . $e->getMessage());
        return [];
    }
}

/**
 * Add a follow-up note or communication log to a lead
 */
function add_crm_lead_note(int $leadId, ?int $agentId, string $agentName, string $noteType, string $note): bool {
    $db = Database::getConnection();
    if (!$db || empty($note)) return false;

    try {
        $stmt = $db->prepare("
            INSERT INTO lead_notes (lead_id, agent_id, agent_name, note_type, note, created_at)
            VALUES (:lid, :aid, :aname, :ntype, :note, NOW())
        ");
        $res = $stmt->execute([
            ':lid' => $leadId,
            ':aid' => $agentId,
            ':aname' => $agentName,
            ':ntype' => $noteType,
            ':note' => $note
        ]);

        if ($res) {
            log_admin_activity('Logged Follow-up Note', "Added {$noteType} note for lead #{$leadId}");
        }
        return $res;
    } catch (PDOException $e) {
        error_log("Error in add_crm_lead_note: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch follow-up reminders
 */
function get_crm_reminders(array $filters = []): array {
    $db = Database::getConnection();
    if (!$db) return [];

    $sql = "
        SELECT cr.*, 
               i.full_name as client_name, i.phone as client_phone, i.email as client_email,
               i.lead_status, p.title as property_title, p.locality as property_locality,
               adm.name as agent_name, adm.avatar as agent_avatar
        FROM crm_reminders cr
        JOIN inquiries i ON cr.lead_id = i.id
        LEFT JOIN properties p ON i.property_id = p.id
        LEFT JOIN admins adm ON cr.agent_id = adm.id
        WHERE 1=1
    ";
    $params = [];

    if (!empty($filters['agent_id'])) {
        $sql .= " AND cr.agent_id = :aid";
        $params[':aid'] = (int)$filters['agent_id'];
    }

    if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'completed', 'dismissed'])) {
        $sql .= " AND cr.status = :st";
        $params[':st'] = $filters['status'];
    }

    if (!empty($filters['priority']) && in_array($filters['priority'], ['urgent', 'high', 'medium', 'low'])) {
        $sql .= " AND cr.priority = :prio";
        $params[':prio'] = $filters['priority'];
    }

    if (!empty($filters['search'])) {
        $sterm = "%" . trim($filters['search']) . "%";
        $sql .= " AND (cr.title LIKE :s1 OR i.full_name LIKE :s2 OR i.phone LIKE :s3 OR cr.notes LIKE :s4)";
        $params[':s1'] = $sterm;
        $params[':s2'] = $sterm;
        $params[':s3'] = $sterm;
        $params[':s4'] = $sterm;
    }

    if (!empty($filters['timeframe'])) {
        if ($filters['timeframe'] === 'overdue') {
            $sql .= " AND cr.remind_at < NOW() AND cr.status = 'pending'";
        } elseif ($filters['timeframe'] === 'today') {
            $sql .= " AND DATE(cr.remind_at) = CURDATE() AND cr.status = 'pending'";
        } elseif ($filters['timeframe'] === 'upcoming') {
            $sql .= " AND cr.remind_at > NOW() AND DATE(cr.remind_at) > CURDATE() AND cr.status = 'pending'";
        }
    }

    $sql .= " ORDER BY cr.status ASC, cr.remind_at ASC";

    if (!empty($filters['limit'])) {
        $sql .= " LIMIT " . (int)$filters['limit'];
    }

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_crm_reminders: " . $e->getMessage());
        return [];
    }
}

/**
 * Create a new scheduled reminder
 */
function create_crm_reminder(int $leadId, int $agentId, string $title, string $remindAt, string $priority = 'medium', ?string $notes = null): int|bool {
    $db = Database::getConnection();
    if (!$db) return false;

    try {
        $stmt = $db->prepare("
            INSERT INTO crm_reminders (lead_id, agent_id, title, remind_at, priority, status, notes, created_at)
            VALUES (:lid, :aid, :title, :remind_at, :priority, 'pending', :notes, NOW())
        ");
        $res = $stmt->execute([
            ':lid' => $leadId,
            ':aid' => $agentId,
            ':title' => $title,
            ':remind_at' => $remindAt,
            ':priority' => $priority,
            ':notes' => $notes
        ]);

        if ($res) {
            $newRemId = (int)$db->lastInsertId();
            // Sync inquiries follow_up_date
            $upd = $db->prepare("UPDATE inquiries SET follow_up_date = :fdate WHERE id = :id");
            $upd->execute([':fdate' => $remindAt, ':id' => $leadId]);
            log_admin_activity('Scheduled CRM Reminder', "Scheduled reminder '{$title}' for lead #{$leadId}");
            return $newRemId;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error in create_crm_reminder: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark a reminder as completed
 */
function complete_crm_reminder(int $reminderId): bool {
    $db = Database::getConnection();
    if (!$db) return false;

    try {
        $stmt = $db->prepare("UPDATE crm_reminders SET status = 'completed', completed_at = NOW() WHERE id = :id");
        $res = $stmt->execute([':id' => $reminderId]);
        if ($res) {
            log_admin_activity('Completed Reminder', "Marked reminder #{$reminderId} completed");
        }
        return $res;
    } catch (PDOException $e) {
        error_log("Error in complete_crm_reminder: " . $e->getMessage());
        return false;
    }
}

/**
 * Reschedule an existing reminder
 */
function reschedule_crm_reminder(int $reminderId, string $newDate, ?string $notes = null): bool {
    $db = Database::getConnection();
    if (!$db) return false;

    try {
        $curr = $db->query("SELECT lead_id, title FROM crm_reminders WHERE id = {$reminderId}")->fetch(PDO::FETCH_ASSOC);
        if (!$curr) return false;

        $sql = "UPDATE crm_reminders SET remind_at = :rdate, status = 'pending'";
        $params = [
            ':rdate' => $newDate,
            ':id' => $reminderId
        ];

        if ($notes !== null && trim($notes) !== '') {
            $sql .= ", notes = :notes";
            $params[':notes'] = trim($notes);
        }
        $sql .= " WHERE id = :id";

        $stmt = $db->prepare($sql);
        $res = $stmt->execute($params);

        if ($res && !empty($curr['lead_id'])) {
            $upd = $db->prepare("UPDATE inquiries SET follow_up_date = :fdate WHERE id = :id");
            $upd->execute([':fdate' => $newDate, ':id' => $curr['lead_id']]);
            log_admin_activity('Rescheduled Reminder', "Rescheduled reminder #{$reminderId} to {$newDate}");
        }
        return $res;
    } catch (PDOException $e) {
        error_log("Error in reschedule_crm_reminder: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch properties assigned to a specific advisor
 */
function get_crm_agent_properties(?int $agentId = null): array {
    $db = Database::getConnection();
    if (!$db) return [];

    try {
        $sql = "
            SELECT p.*, c.name as city_name, cat.name as category_name,
                   adm.name as agent_name, adm.avatar as agent_avatar,
                   (SELECT COUNT(*) FROM inquiries i WHERE i.property_id = p.id) as inquiries_count,
                   (SELECT COUNT(*) FROM inquiries i WHERE i.property_id = p.id AND i.inquiry_type = 'site_visit') as visits_count
            FROM properties p
            LEFT JOIN cities c ON p.city_id = c.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            LEFT JOIN admins adm ON p.assigned_agent_id = adm.id
            WHERE p.approval_status = 'approved'
        ";
        $params = [];
        if ($agentId !== null && $agentId > 0) {
            $sql .= " AND p.assigned_agent_id = :aid";
            $params[':aid'] = $agentId;
        }
        $sql .= " ORDER BY p.price DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_crm_agent_properties: " . $e->getMessage());
        return [];
    }
}

/**
 * Get Chart Data: Pipeline Stage Counts & Total Deal Values
 */
function get_crm_pipeline_distribution(?int $agentId = null): array {
    $db = Database::getConnection();
    if (!$db) return [];

    $stages = [
        'new' => 'New Discovery',
        'contacted' => 'Contacted',
        'interested' => 'Interested',
        'site_visit_scheduled' => 'Site Visit Scheduled',
        'negotiation' => 'Negotiation',
        'converted' => 'Converted',
        'lost' => 'Lost'
    ];

    $sql = "
        SELECT lead_status, COUNT(*) as count, SUM(COALESCE(deal_value, 0)) as total_val
        FROM inquiries
        WHERE 1=1
    ";
    if ($agentId !== null) {
        $sql .= " AND assigned_agent_id = " . (int)$agentId;
    }
    $sql .= " GROUP BY lead_status";

    $counts = [];
    $values = [];
    foreach ($stages as $k => $v) {
        $counts[$k] = 0;
        $values[$k] = 0.0;
    }

    try {
        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $st = $r['lead_status'];
            if (isset($counts[$st])) {
                $counts[$st] = (int)$r['count'];
                $values[$st] = round(((float)$r['total_val']) / 10000000, 2);
            }
        }
    } catch (PDOException $e) {
        error_log("Error in get_crm_pipeline_distribution: " . $e->getMessage());
    }

    return [
        'stages' => $stages,
        'counts' => $counts,
        'values_cr' => $values
    ];
}

/**
 * Get Chart Data: Leads by Acquisition Source
 */
function get_crm_lead_sources(?int $agentId = null): array {
    $db = Database::getConnection();
    if (!$db) return [];

    $sql = "
        SELECT COALESCE(lead_source, 'Website Enquiry') as source, COUNT(*) as count
        FROM inquiries
        WHERE 1=1
    ";
    if ($agentId !== null) {
        $sql .= " AND assigned_agent_id = " . (int)$agentId;
    }
    $sql .= " GROUP BY source ORDER BY count DESC";

    try {
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_crm_lead_sources: " . $e->getMessage());
        return [];
    }
}

/**
 * Standard 7-stage CRM Pipeline Definitions
 */
function get_crm_pipeline_stages(): array {
    return [
        'new' => [
            'label' => 'New',
            'icon' => 'fa-sparkles',
            'color' => '#3B82F6',
            'badge' => 'adm-badge-new',
            'description' => 'Uncontacted initial inquiries & discovery'
        ],
        'contacted' => [
            'label' => 'Contacted',
            'icon' => 'fa-phone',
            'color' => '#F59E0B',
            'badge' => 'adm-badge-contacted',
            'description' => 'First touchpoint completed via call, WhatsApp, or email'
        ],
        'interested' => [
            'label' => 'Interested',
            'icon' => 'fa-heart',
            'color' => '#6366F1',
            'badge' => 'adm-badge-interested',
            'description' => 'Client expressed interest & verified budget fit'
        ],
        'site_visit_scheduled' => [
            'label' => 'Site Visit Scheduled',
            'icon' => 'fa-calendar-check',
            'color' => '#8B5CF6',
            'badge' => 'adm-badge-site_visit_scheduled',
            'description' => 'Private on-site walkthrough tour scheduled'
        ],
        'negotiation' => [
            'label' => 'Negotiation',
            'icon' => 'fa-comments-dollar',
            'color' => '#D97706',
            'badge' => 'adm-badge-negotiation',
            'description' => 'Pricing, payment plan, and agreement discussion'
        ],
        'converted' => [
            'label' => 'Converted',
            'icon' => 'fa-trophy',
            'color' => '#059669',
            'badge' => 'adm-badge-converted',
            'description' => 'Deal closed, token / sale deed executed'
        ],
        'lost' => [
            'label' => 'Lost',
            'icon' => 'fa-circle-xmark',
            'color' => '#64748B',
            'badge' => 'adm-badge-lost',
            'description' => 'Client disqualified, unresponsive or lost to competitor'
        ]
    ];
}

/**
 * Standard CRM Lead Acquisition Sources
 */
function get_crm_sources_list(): array {
    return [
        'Website Enquiry',
        'Callback Request',
        'Site Visit Booking',
        'Direct Call',
        'Referral',
        'HNI Private Network',
        'Social Campaign',
        'Walk-in Concierge'
    ];
}

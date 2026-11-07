<?php
/**
 * EstateVista India - Leads Management & CRM Pipeline (Module 9)
 * Executive CRM workstation tracking high-value client acquisitions across 7 deal stages.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';
require_once __DIR__ . '/includes/crm_functions.php';

require_admin_login();

$db = Database::getConnection();
$logged_admin = get_logged_admin();
$admin_role = get_admin_role();
$success_msg = '';
$error_msg = '';

$stages = get_crm_pipeline_stages();
$sources_list = get_crm_sources_list();

// ==========================================
// HANDLE ACTIONS (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_admin_csrf()) {
        $error_msg = "Security token expired or invalid. Please reload and try again.";
    } else {
        $action = sanitize($_POST['action'] ?? '');

        // 1. Create Direct Lead
        if ($action === 'create_lead') {
            $name = sanitize($_POST['full_name'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $city = sanitize($_POST['preferred_city'] ?? $_POST['city'] ?? '');
            $budget = sanitize($_POST['budget_range'] ?? '');
            $property_id = !empty($_POST['property_id']) ? (int)$_POST['property_id'] : null;
            $agent_id = !empty($_POST['assigned_agent_id']) ? (int)$_POST['assigned_agent_id'] : null;
            $stage = sanitize($_POST['lead_status'] ?? 'new');
            $source = sanitize($_POST['lead_source'] ?? 'Direct Call');
            $deal_val_cr = (float)($_POST['deal_value_cr'] ?? 0);
            $deal_value = $deal_val_cr > 0 ? ($deal_val_cr * 10000000) : null;
            $follow_up_date = !empty($_POST['follow_up_date']) ? sanitize($_POST['follow_up_date']) : null;
            $notes = sanitize($_POST['admin_notes'] ?? '');

            // For agent role, auto-assign to themselves if not super_admin/admin
            if ($admin_role === 'agent' && empty($agent_id)) {
                $agent_id = $logged_admin['id'];
            }

            if (empty($name) || empty($phone)) {
                $error_msg = "Client full name and contact phone are required.";
            } else {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO inquiries (
                            full_name, email, phone, city, preferred_city, budget_range, deal_value,
                            property_id, assigned_agent_id, lead_status, lead_source, status, 
                            follow_up_date, admin_notes, created_at, updated_at
                        ) VALUES (
                            :name, :email, :phone, :city, :pref_city, :budget, :deal_val,
                            :pid, :aid, :stage, :source, 'new', 
                            :fdate, :notes, NOW(), NOW()
                        )
                    ");
                    $stmt->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':city' => $city,
                        ':pref_city' => $city,
                        ':budget' => $budget,
                        ':deal_val' => $deal_value,
                        ':pid' => $property_id,
                        ':aid' => $agent_id,
                        ':stage' => array_key_exists($stage, $stages) ? $stage : 'new',
                        ':source' => $source,
                        ':fdate' => $follow_up_date,
                        ':notes' => $notes
                    ]);
                    $new_id = (int)$db->lastInsertId();

                    // Log initial interaction note
                    if (!empty($notes)) {
                        add_crm_lead_note(
                            $new_id,
                            $logged_admin['id'],
                            $logged_admin['name'],
                            'general',
                            "Initial Discovery Notes: " . $notes
                        );
                    } else {
                        add_crm_lead_note(
                            $new_id,
                            $logged_admin['id'],
                            $logged_admin['name'],
                            'status_change',
                            "Lead created in stage '{$stage}' via {$source}."
                        );
                    }

                    // Auto-schedule reminder if follow-up date provided
                    if (!empty($follow_up_date)) {
                        create_crm_reminder(
                            $new_id,
                            $agent_id ?: $logged_admin['id'],
                            "Initial Follow-Up: {$name}",
                            $follow_up_date,
                            'high',
                            "Follow-up scheduled at lead onboarding."
                        );
                    }

                    log_admin_activity('Created Lead Dossier', "Created VIP lead #{$new_id} ({$name}) in stage: {$stage}");
                    $success_msg = "VIP Lead dossier created successfully for {$name} (#{$new_id}).";
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 2. Quick Update Stage, Advisor & Follow-up
        elseif ($action === 'quick_update_stage') {
            $lead_id = (int)($_POST['lead_id'] ?? 0);
            $stage = sanitize($_POST['lead_status'] ?? '');
            $agent_id = !empty($_POST['assigned_agent_id']) ? (int)$_POST['assigned_agent_id'] : null;
            $deal_val_cr = (float)($_POST['deal_value_cr'] ?? 0);
            $deal_value = $deal_val_cr > 0 ? ($deal_val_cr * 10000000) : null;
            $follow_up_date = !empty($_POST['follow_up_date']) ? sanitize($_POST['follow_up_date']) : null;
            $notes = sanitize($_POST['admin_notes'] ?? '');

            if ($lead_id > 0 && array_key_exists($stage, $stages)) {
                try {
                    $curr = $db->query("SELECT lead_status, assigned_agent_id, full_name, deal_value FROM inquiries WHERE id = {$lead_id}")->fetch(PDO::FETCH_ASSOC);
                    $old_stage = $curr['lead_status'] ?? 'new';
                    $client_name = $curr['full_name'] ?? "Lead #{$lead_id}";

                    $converted_sql = ($stage === 'converted') ? ", converted_at = NOW()" : "";

                    $stmt = $db->prepare("
                        UPDATE inquiries 
                        SET lead_status = :ls, 
                            assigned_agent_id = :aid, 
                            deal_value = COALESCE(:deal_val, deal_value),
                            follow_up_date = :fdate,
                            admin_notes = :notes,
                            updated_at = NOW()
                            {$converted_sql}
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':ls' => $stage,
                        ':aid' => $agent_id,
                        ':deal_val' => $deal_value,
                        ':fdate' => $follow_up_date,
                        ':notes' => $notes,
                        ':id' => $lead_id
                    ]);

                    // Add note if stage changed or remarks added
                    $noteMsg = "";
                    if ($old_stage !== $stage) {
                        $noteMsg .= "Pipeline moved from '{$stages[$old_stage]['label']}' to '{$stages[$stage]['label']}'. ";
                    }
                    if (!empty($notes)) {
                        $noteMsg .= $notes;
                    }

                    if (!empty($noteMsg)) {
                        add_crm_lead_note(
                            $lead_id,
                            $logged_admin['id'],
                            $logged_admin['name'],
                            'status_change',
                            $noteMsg
                        );
                    }

                    // Create reminder if follow up scheduled
                    if (!empty($follow_up_date)) {
                        create_crm_reminder(
                            $lead_id,
                            $agent_id ?: $logged_admin['id'],
                            "Follow-up: {$client_name}",
                            $follow_up_date,
                            'medium',
                            "Scheduled from Quick Pipeline update."
                        );
                    }

                    log_admin_activity('Updated Lead Stage', "Lead #{$lead_id} moved to {$stage}");
                    $success_msg = "Lead #{$lead_id} ({$client_name}) updated to {$stages[$stage]['label']}.";
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}

// ==========================================
// FILTERS & QUERY PREPARATION
// ==========================================
$view_mode = $_GET['view'] ?? 'pipeline'; // 'pipeline' or 'table'
$search = trim($_GET['search'] ?? '');
$stage_filter = trim($_GET['stage'] ?? '');
$agent_filter = trim($_GET['agent_id'] ?? '');
$city_filter = trim($_GET['city'] ?? '');
$source_filter = trim($_GET['source'] ?? '');
$follow_filter = trim($_GET['follow_up'] ?? '');

// Agent role safety: automatically restrict agent view unless admin
if ($admin_role === 'agent') {
    $agent_filter = (string)$logged_admin['id'];
}

$filters = [
    'search' => $search,
    'status' => $stage_filter,
    'agent_id' => $agent_filter,
    'city' => $city_filter,
    'source' => $source_filter,
    'follow_up' => $follow_filter
];

// Fetch leads using CRM Engine
$all_leads = get_crm_leads($filters);

// ==========================================
// EXPORT TO CSV
// ==========================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (!headers_sent()) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=EstateVista_CRM_Leads_' . date('Ymd_His') . '.csv');
        $output = fopen('php://output', 'w');
        // UTF-8 BOM for Microsoft Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header Row
        fputcsv($output, [
            'Lead ID', 'Full Name', 'Phone', 'Email', 'Preferred City', 
            'Investment Budget', 'Interested Property', 'Pipeline Stage', 
            'Deal Value (Cr)', 'Lead Source', 'Assigned Advisor', 
            'Next Follow-Up', 'Created Date'
        ]);

        foreach ($all_leads as $l) {
            $dealCr = $l['deal_value'] ? round($l['deal_value'] / 10000000, 2) : '0.00';
            fputcsv($output, [
                $l['id'],
                $l['full_name'],
                $l['phone'],
                $l['email'],
                $l['preferred_city'] ?: $l['city'] ?: 'India',
                $l['budget_range'] ?: 'Flexible',
                $l['property_title'] ?: 'General Advisory',
                strtoupper($l['lead_status'] ?: 'NEW'),
                $dealCr,
                $l['lead_source'] ?: 'Website Enquiry',
                $l['agent_name'] ?: 'Unassigned',
                $l['follow_up_date'] ? date('d M Y, h:i A', strtotime($l['follow_up_date'])) : 'None',
                date('d M Y, h:i A', strtotime($l['created_at']))
            ]);
        }
        fclose($output);
        exit;
    }
}

// Group leads by stage for Kanban Pipeline
$grouped_leads = [];
$stage_totals = [];
foreach ($stages as $key => $st) {
    $grouped_leads[$key] = [];
    $stage_totals[$key] = 0.0;
}
foreach ($all_leads as $lead) {
    $st = $lead['lead_status'] ?: 'new';
    if (!isset($grouped_leads[$st])) {
        $grouped_leads['new'][] = $lead;
        $stage_totals['new'] += (float)($lead['deal_value'] ?? 0);
    } else {
        $grouped_leads[$st][] = $lead;
        $stage_totals[$st] += (float)($lead['deal_value'] ?? 0);
    }
}

// Summary Statistics for KPI Pills
$total_leads_count = count($all_leads);
$won_leads_count = count($grouped_leads['converted'] ?? []);
$negotiating_leads_count = count($grouped_leads['negotiation'] ?? []);
$total_pipeline_val = 0;
foreach ($all_leads as $lead) {
    if (!in_array($lead['lead_status'], ['converted', 'lost'])) {
        $total_pipeline_val += (float)($lead['deal_value'] ?? 0);
    }
}
$total_pipeline_val_cr = round($total_pipeline_val / 10000000, 2);

// Auxiliary datasets for dropdowns
$properties_list = $db->query("SELECT id, title, locality, price_display FROM properties WHERE approval_status = 'approved' ORDER BY title ASC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$assignable_agents = get_all_assignable_agents();
$cities_list = $db->query("SELECT name FROM cities ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

$page_title = "Lead Pipeline & CRM Command Desk";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Top Executive Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                    <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; color: var(--adm-gold); background: rgba(197, 168, 128, 0.12); padding: 0.2rem 0.6rem; border-radius: 4px;">
                        Enterprise CRM Module
                    </span>
                    <span style="color: var(--adm-text-muted); font-size: 0.8rem;">&bull; 7 Pipeline Stages</span>
                </div>
                <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--adm-navy); margin: 0;">
                    Lead Pipeline & Client Acquisitions
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem; margin: 0.25rem 0 0 0;">
                    Track luxury home buyer journeys from discovery to high-value escrow closing.
                </p>
            </div>
            
            <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                <!-- View Mode Switcher -->
                <div style="background: #FFFFFF; border: 1px solid var(--adm-border); border-radius: 8px; padding: 0.25rem; display: flex; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                    <a href="leads.php?<?= http_build_query(array_merge($_GET, ['view' => 'pipeline'])) ?>" 
                       class="adm-btn adm-btn-sm <?= $view_mode === 'pipeline' ? 'adm-btn-navy' : 'adm-btn-outline' ?>" style="border: none;">
                        <i class="fa-solid fa-table-columns"></i> Kanban Pipeline
                    </a>
                    <a href="leads.php?<?= http_build_query(array_merge($_GET, ['view' => 'table'])) ?>" 
                       class="adm-btn adm-btn-sm <?= $view_mode === 'table' ? 'adm-btn-navy' : 'adm-btn-outline' ?>" style="border: none;">
                        <i class="fa-solid fa-list"></i> Table View
                    </a>
                </div>

                <!-- Export CSV Button -->
                <a href="leads.php?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" 
                   class="adm-btn adm-btn-outline adm-btn-sm" title="Export Filtered Leads to CSV">
                    <i class="fa-solid fa-file-csv" style="color: #059669;"></i> Export CSV
                </a>

                <!-- Agent CRM Dashboard Quicklink -->
                <a href="crm-dashboard.php" class="adm-btn adm-btn-outline adm-btn-sm">
                    <i class="fa-solid fa-gauge-high"></i> Agent Desk
                </a>

                <!-- New Lead Button -->
                <button type="button" class="adm-btn adm-btn-gold adm-btn-sm" data-adm-modal-target="modalAddLead">
                    <i class="fa-solid fa-user-plus"></i> Onboard Lead
                </button>
            </div>
        </div>

        <!-- Metrics Snapshot Bar -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="adm-card" style="padding: 1rem; border-left: 4px solid var(--adm-navy);">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--adm-text-muted); font-weight: 700;">Total Pipeline Leads</div>
                <div style="font-size: 1.4rem; font-weight: 800; color: var(--adm-navy);"><?= number_format($total_leads_count) ?></div>
            </div>
            <div class="adm-card" style="padding: 1rem; border-left: 4px solid #3B82F6;">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--adm-text-muted); font-weight: 700;">Active Pipeline Value</div>
                <div style="font-size: 1.4rem; font-weight: 800; color: #1E40AF;">₹<?= $total_pipeline_val_cr ?> Cr</div>
            </div>
            <div class="adm-card" style="padding: 1rem; border-left: 4px solid #D97706;">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--adm-text-muted); font-weight: 700;">In Negotiation</div>
                <div style="font-size: 1.4rem; font-weight: 800; color: #D97706;"><?= $negotiating_leads_count ?> Deals</div>
            </div>
            <div class="adm-card" style="padding: 1rem; border-left: 4px solid #059669;">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--adm-text-muted); font-weight: 700;">Converted / Closed</div>
                <div style="font-size: 1.4rem; font-weight: 800; color: #059669;"><?= $won_leads_count ?> Won</div>
            </div>
        </div>

        <!-- Feedback Alerts -->
        <?php if (!empty($success_msg)): ?>
            <div style="background: #ECFDF5; border-left: 4px solid var(--adm-success); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; color: #065F46; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-circle-check"></i> <span><?= e($success_msg) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div style="background: #FEF2F2; border-left: 4px solid var(--adm-danger); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; color: #991B1B; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-circle-exclamation"></i> <span><?= e($error_msg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Multi-Dimensional CRM Filter Toolbar -->
        <div class="adm-card" style="margin-bottom: 1.5rem;">
            <div class="adm-card-body" style="padding: 1rem 1.25rem;">
                <form method="GET" action="leads.php" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                    <input type="hidden" name="view" value="<?= e($view_mode) ?>">
                    
                    <!-- Search query -->
                    <div class="adm-search-input-wrap" style="flex: 1.5; min-width: 220px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" class="adm-input" placeholder="Search name, phone, email, property..." value="<?= e($search) ?>">
                    </div>

                    <!-- Stage Filter -->
                    <div style="min-width: 160px;">
                        <select name="stage" class="adm-select" onchange="this.form.submit()">
                            <option value="">All Pipeline Stages</option>
                            <?php foreach ($stages as $sk => $sm): ?>
                                <option value="<?= $sk ?>" <?= ($stage_filter === $sk) ? 'selected' : '' ?>>
                                    <?= $sm['label'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Agent Filter -->
                    <?php if ($admin_role !== 'agent'): ?>
                        <div style="min-width: 170px;">
                            <select name="agent_id" class="adm-select" onchange="this.form.submit()">
                                <option value="">All Wealth Advisors</option>
                                <option value="unassigned" <?= ($agent_filter === 'unassigned') ? 'selected' : '' ?>>Unassigned Leads</option>
                                <?php foreach ($assignable_agents as $ag): ?>
                                    <option value="<?= $ag['id'] ?>" <?= ($agent_filter == $ag['id']) ? 'selected' : '' ?>>
                                        <?= e($ag['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- City Filter -->
                    <div style="min-width: 140px;">
                        <select name="city" class="adm-select" onchange="this.form.submit()">
                            <option value="">All Cities</option>
                            <?php foreach ($cities_list as $cityName): ?>
                                <option value="<?= e($cityName) ?>" <?= ($city_filter === $cityName) ? 'selected' : '' ?>><?= e($cityName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Lead Source Filter -->
                    <div style="min-width: 160px;">
                        <select name="source" class="adm-select" onchange="this.form.submit()">
                            <option value="">All Lead Sources</option>
                            <?php foreach ($sources_list as $src): ?>
                                <option value="<?= e($src) ?>" <?= ($source_filter === $src) ? 'selected' : '' ?>><?= e($src) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Follow-up status filter -->
                    <div style="min-width: 150px;">
                        <select name="follow_up" class="adm-select" onchange="this.form.submit()">
                            <option value="">All Follow-Ups</option>
                            <option value="overdue" <?= ($follow_filter === 'overdue') ? 'selected' : '' ?>>⚠️ Overdue Only</option>
                            <option value="today" <?= ($follow_filter === 'today') ? 'selected' : '' ?>>📅 Due Today</option>
                            <option value="upcoming" <?= ($follow_filter === 'upcoming') ? 'selected' : '' ?>>⏳ Upcoming</option>
                        </select>
                    </div>

                    <button type="submit" class="adm-btn adm-btn-navy">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>

                    <?php if (!empty($search) || !empty($stage_filter) || (!empty($agent_filter) && $admin_role !== 'agent') || !empty($city_filter) || !empty($source_filter) || !empty($follow_filter)): ?>
                        <a href="leads.php?view=<?= e($view_mode) ?>" class="adm-btn adm-btn-outline" title="Reset all filters">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if ($view_mode === 'pipeline'): ?>
            <!-- ==========================================
                 KANBAN PIPELINE BOARD (7 STAGES)
                 ========================================== -->
            <div class="pipeline-kanban-board">
                <?php foreach ($stages as $stageKey => $sMeta): ?>
                    <?php 
                        $stageLeads = $grouped_leads[$stageKey] ?? []; 
                        $valCr = round(($stage_totals[$stageKey] ?? 0) / 10000000, 2);
                    ?>
                    <div style="flex: 0 0 310px; background: #F8FAFC; border-radius: 12px; border: 1px solid var(--adm-border); display: flex; flex-direction: column; max-height: calc(100vh - 250px); box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                        <!-- Stage Column Header -->
                        <div style="padding: 1rem; border-bottom: 3px solid <?= $sMeta['color'] ?>; background: #FFFFFF; border-radius: 12px 12px 0 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                <div style="display: flex; align-items: center; gap: 0.45rem;">
                                    <i class="fa-solid <?= $sMeta['icon'] ?>" style="color: <?= $sMeta['color'] ?>; font-size: 0.88rem;"></i>
                                    <span style="font-weight: 800; color: var(--adm-navy); font-size: 0.9rem;"><?= $sMeta['label'] ?></span>
                                </div>
                                <span style="background: <?= $sMeta['color'] ?>15; color: <?= $sMeta['color'] ?>; font-weight: 800; font-size: 0.76rem; padding: 0.2rem 0.55rem; border-radius: 20px;">
                                    <?= count($stageLeads) ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.72rem; color: var(--adm-text-muted);">
                                <span>Est. Value:</span>
                                <span style="font-weight: 700; color: var(--adm-navy);">₹<?= $valCr ?> Cr</span>
                            </div>
                        </div>

                        <!-- Column Body / Lead Cards -->
                        <div style="padding: 0.75rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem; flex: 1;">
                            <?php if (empty($stageLeads)): ?>
                                <div style="text-align: center; padding: 2.5rem 1rem; color: #94A3B8; font-size: 0.8rem; font-style: italic;">
                                    No leads in <?= strtolower($sMeta['label']) ?>
                                </div>
                            <?php else: ?>
                                <?php foreach ($stageLeads as $lead): ?>
                                    <?php 
                                        $dealCr = $lead['deal_value'] ? round($lead['deal_value'] / 10000000, 2) : 0;
                                        $prefCity = $lead['preferred_city'] ?: $lead['city'] ?: 'India';
                                        
                                        // Follow up date status
                                        $fStatus = null;
                                        if (!empty($lead['follow_up_date'])) {
                                            $fTime = strtotime($lead['follow_up_date']);
                                            $now = time();
                                            if ($fTime < $now && !in_array($lead['lead_status'], ['converted', 'lost'])) {
                                                $fStatus = 'overdue';
                                            } elseif (date('Y-m-d', $fTime) === date('Y-m-d')) {
                                                $fStatus = 'today';
                                            } else {
                                                $fStatus = 'upcoming';
                                            }
                                        }
                                    ?>
                                    <div class="adm-lead-card"
                                         style="background: #FFFFFF; border-radius: 9px; padding: 1rem; border: 1px solid var(--adm-border); box-shadow: 0 1px 3px rgba(0,0,0,0.03); transition: transform 0.2s, box-shadow 0.2s; position: relative;">
                                        
                                        <!-- Top Row: Lead ID & Source Badge -->
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem;">
                                            <span style="font-size: 0.7rem; font-weight: 700; color: var(--adm-text-muted);">#LEAD-<?= $lead['id'] ?></span>
                                            <span style="font-size: 0.68rem; background: #F1F5F9; color: #475569; padding: 0.15rem 0.45rem; border-radius: 4px; font-weight: 600;">
                                                <?= e($lead['lead_source'] ?: 'Website') ?>
                                            </span>
                                        </div>

                                        <!-- Lead Name -->
                                        <div style="font-weight: 800; color: var(--adm-navy); font-size: 0.95rem; margin-bottom: 0.25rem;">
                                            <a href="lead-detail.php?id=<?= $lead['id'] ?>" style="color: inherit; text-decoration: none;">
                                                <?= e($lead['full_name']) ?>
                                            </a>
                                        </div>

                                        <!-- Direct Communication Links -->
                                        <div style="display: flex; gap: 0.6rem; font-size: 0.75rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                                            <a href="tel:<?= e($lead['phone']) ?>" style="color: #2563EB; font-weight: 600; text-decoration: none;" title="Call Client">
                                                <i class="fa-solid fa-phone" style="font-size: 0.7rem;"></i> <?= e($lead['phone']) ?>
                                            </a>
                                            <?php if (!empty($lead['phone'])): ?>
                                                <?php 
                                                    $cleanPhone = preg_replace('/[^0-9]/', '', $lead['phone']); 
                                                    if (strlen($cleanPhone) === 10) $cleanPhone = '91' . $cleanPhone;
                                                ?>
                                                <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" style="color: #059669; font-weight: 600; text-decoration: none;" title="WhatsApp">
                                                    <i class="fa-brands fa-whatsapp"></i> Chat
                                                </a>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Property & City -->
                                        <?php if (!empty($lead['property_title'])): ?>
                                            <div style="font-size: 0.78rem; color: #334155; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 0.35rem;">
                                                <i class="fa-solid fa-building" style="color: var(--adm-gold); font-size: 0.72rem;"></i> <?= e($lead['property_title']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Budget & Deal Value -->
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; font-size: 0.75rem;">
                                            <span style="color: #64748B;">
                                                <i class="fa-solid fa-location-dot" style="font-size: 0.68rem;"></i> <?= e($prefCity) ?>
                                            </span>
                                            <span style="font-weight: 700; color: #059669;">
                                                <?= $dealCr > 0 ? "₹{$dealCr} Cr Deal" : (e($lead['budget_range']) ?: 'Budget Flexible') ?>
                                            </span>
                                        </div>

                                        <!-- Follow-up Alert Pill -->
                                        <?php if ($fStatus === 'overdue'): ?>
                                            <div style="background: #FEF2F2; color: #DC2626; font-size: 0.7rem; font-weight: 700; padding: 0.25rem 0.5rem; border-radius: 4px; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.35rem;">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Overdue: <?= date('d M, h:i A', strtotime($lead['follow_up_date'])) ?>
                                            </div>
                                        <?php elseif ($fStatus === 'today'): ?>
                                            <div style="background: #FFFBEB; color: #D97706; font-size: 0.7rem; font-weight: 700; padding: 0.25rem 0.5rem; border-radius: 4px; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.35rem;">
                                                <i class="fa-solid fa-clock"></i> Due Today: <?= date('h:i A', strtotime($lead['follow_up_date'])) ?>
                                            </div>
                                        <?php elseif ($fStatus === 'upcoming'): ?>
                                            <div style="background: #EFF6FF; color: #2563EB; font-size: 0.7rem; font-weight: 600; padding: 0.25rem 0.5rem; border-radius: 4px; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.35rem;">
                                                <i class="fa-solid fa-calendar"></i> Next: <?= date('d M, h:i A', strtotime($lead['follow_up_date'])) ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Card Footer: Advisor & Quick Actions -->
                                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #F1F5F9; padding-top: 0.6rem; margin-top: 0.4rem;">
                                            <div style="font-size: 0.74rem; color: var(--adm-text-muted); display: flex; align-items: center; gap: 0.35rem;">
                                                <?php if (!empty($lead['agent_name'])): ?>
                                                    <i class="fa-solid fa-user-tie" style="color: var(--adm-navy);" title="Assigned Advisor"></i>
                                                    <span style="font-weight: 600; color: var(--adm-navy);"><?= e($lead['agent_name']) ?></span>
                                                <?php else: ?>
                                                    <span style="color: #F59E0B; font-weight: 600;"><i class="fa-solid fa-user-slash"></i> Unassigned</span>
                                                <?php endif; ?>
                                            </div>

                                            <div style="display: flex; gap: 0.35rem;">
                                                <button type="button" class="adm-btn adm-btn-sm adm-btn-outline btn-open-quick-modal" 
                                                        style="padding: 0.25rem 0.5rem; font-size: 0.7rem;"
                                                        title="Quick Update Pipeline Stage"
                                                        data-id="<?= $lead['id'] ?>"
                                                        data-name="<?= e($lead['full_name']) ?>"
                                                        data-phone="<?= e($lead['phone']) ?>"
                                                        data-city="<?= e($prefCity) ?>"
                                                        data-budget="<?= e($lead['budget_range']) ?>"
                                                        data-deal="<?= $dealCr ?>"
                                                        data-property="<?= e($lead['property_title'] ?: 'General Advisory') ?>"
                                                        data-stage="<?= e($stageKey) ?>"
                                                        data-agent="<?= e($lead['assigned_agent_id']) ?>"
                                                        data-follow="<?= !empty($lead['follow_up_date']) ? date('Y-m-d\TH:i', strtotime($lead['follow_up_date'])) : '' ?>"
                                                        data-notes="<?= e($lead['admin_notes']) ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                
                                                <a href="lead-detail.php?id=<?= $lead['id'] ?>" class="adm-btn adm-btn-sm adm-btn-navy" 
                                                   style="padding: 0.25rem 0.55rem; font-size: 0.7rem;" title="Open 360° Lead Dossier">
                                                    Dossier &rarr;
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- ==========================================
                 TABLE VIEW
                 ========================================== -->
            <div class="adm-card">
                <div class="adm-table-responsive">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>Lead ID</th>
                                <th>Client Dossier</th>
                                <th>Interested Property</th>
                                <th>Investment Budget</th>
                                <th>Pipeline Stage</th>
                                <th>Lead Source</th>
                                <th>Assigned Advisor</th>
                                <th>Next Follow-Up</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_leads)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 3rem 1rem; color: #94A3B8;">
                                        No leads found matching your criteria. Try adjusting your filters.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_leads as $lead): ?>
                                    <?php 
                                        $stageKey = $lead['lead_status'] ?: 'new';
                                        $dealCr = $lead['deal_value'] ? round($lead['deal_value'] / 10000000, 2) : 0;
                                        $prefCity = $lead['preferred_city'] ?: $lead['city'] ?: 'India';

                                        // Follow-up status
                                        $fStatus = null;
                                        if (!empty($lead['follow_up_date'])) {
                                            $fTime = strtotime($lead['follow_up_date']);
                                            $now = time();
                                            if ($fTime < $now && !in_array($stageKey, ['converted', 'lost'])) {
                                                $fStatus = 'overdue';
                                            } elseif (date('Y-m-d', $fTime) === date('Y-m-d')) {
                                                $fStatus = 'today';
                                            } else {
                                                $fStatus = 'upcoming';
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="lead-detail.php?id=<?= $lead['id'] ?>" style="font-weight: 800; color: var(--adm-navy); text-decoration: none;">
                                                #<?= $lead['id'] ?>
                                            </a>
                                            <div style="font-size: 0.72rem; color: #94A3B8;">
                                                <?= date('d M Y', strtotime($lead['created_at'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: var(--adm-navy); font-size: 0.92rem;">
                                                <a href="lead-detail.php?id=<?= $lead['id'] ?>" style="color: inherit; text-decoration: none;">
                                                    <?= e($lead['full_name']) ?>
                                                </a>
                                            </div>
                                            <div style="font-size: 0.78rem; color: var(--adm-text-muted);">
                                                <a href="tel:<?= e($lead['phone']) ?>" style="color: #2563EB; text-decoration: none;">
                                                    <?= e($lead['phone']) ?>
                                                </a> 
                                                <?php if (!empty($lead['email'])): ?>
                                                    &bull; <?= e($lead['email']) ?>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 0.72rem; color: #64748B;">
                                                <i class="fa-solid fa-location-dot" style="font-size: 0.68rem;"></i> <?= e($prefCity) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($lead['property_title'])): ?>
                                                <div style="font-weight: 600; font-size: 0.85rem; color: var(--adm-navy);">
                                                    <?= e($lead['property_title']) ?>
                                                </div>
                                                <div style="font-size: 0.72rem; color: var(--adm-text-muted);">
                                                    <?= e($lead['property_locality'] ?: '') ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #94A3B8; font-style: italic; font-size: 0.82rem;">General Advisory</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: #059669; font-size: 0.88rem;">
                                                <?= e($lead['budget_range'] ?: 'Flexible') ?>
                                            </div>
                                            <?php if ($dealCr > 0): ?>
                                                <div style="font-size: 0.74rem; font-weight: 800; color: #1E40AF;">
                                                    Est. ₹<?= $dealCr ?> Cr
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="adm-status-badge adm-badge-<?= $stageKey ?>">
                                                <?= strtoupper($stages[$stageKey]['label'] ?? $stageKey) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.75rem; background: #F8FAFC; border: 1px solid var(--adm-border); padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600;">
                                                <?= e($lead['lead_source'] ?: 'Website') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($lead['agent_name'])): ?>
                                                <div style="font-weight: 600; color: var(--adm-navy); font-size: 0.85rem;">
                                                    <i class="fa-solid fa-user-tie" style="color: var(--adm-gold); font-size: 0.75rem;"></i> <?= e($lead['agent_name']) ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #F59E0B; font-weight: 600; font-size: 0.8rem;">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($fStatus === 'overdue'): ?>
                                                <span style="color: #DC2626; font-weight: 700; font-size: 0.78rem;">
                                                    <i class="fa-solid fa-triangle-exclamation"></i> <?= date('d M, h:i A', strtotime($lead['follow_up_date'])) ?>
                                                </span>
                                            <?php elseif ($fStatus === 'today'): ?>
                                                <span style="color: #D97706; font-weight: 700; font-size: 0.78rem;">
                                                    <i class="fa-solid fa-clock"></i> Today <?= date('h:i A', strtotime($lead['follow_up_date'])) ?>
                                                </span>
                                            <?php elseif ($fStatus === 'upcoming'): ?>
                                                <span style="color: #2563EB; font-weight: 600; font-size: 0.78rem;">
                                                    <i class="fa-solid fa-calendar"></i> <?= date('d M, h:i A', strtotime($lead['follow_up_date'])) ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #94A3B8; font-size: 0.78rem;">None Scheduled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                                <button type="button" class="adm-btn adm-btn-sm adm-btn-outline btn-open-quick-modal"
                                                        data-id="<?= $lead['id'] ?>"
                                                        data-name="<?= e($lead['full_name']) ?>"
                                                        data-phone="<?= e($lead['phone']) ?>"
                                                        data-city="<?= e($prefCity) ?>"
                                                        data-budget="<?= e($lead['budget_range']) ?>"
                                                        data-deal="<?= $dealCr ?>"
                                                        data-property="<?= e($lead['property_title'] ?: 'General Advisory') ?>"
                                                        data-stage="<?= e($stageKey) ?>"
                                                        data-agent="<?= e($lead['assigned_agent_id']) ?>"
                                                        data-follow="<?= !empty($lead['follow_up_date']) ? date('Y-m-d\TH:i', strtotime($lead['follow_up_date'])) : '' ?>"
                                                        data-notes="<?= e($lead['admin_notes']) ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <a href="lead-detail.php?id=<?= $lead['id'] ?>" class="adm-btn adm-btn-sm adm-btn-navy" title="Open 360° Lead Dossier">
                                                    Dossier &rarr;
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- ==========================================
     MODAL: ONBOARD NEW VIP LEAD
     ========================================== -->
<div class="adm-modal-backdrop" id="modalAddLead">
    <div class="adm-modal" style="max-width: 650px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title">
                <i class="fa-solid fa-user-plus" style="color: var(--adm-gold);"></i> Onboard New VIP Client Lead
            </h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="leads.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="create_lead">
            
            <div class="adm-modal-body">
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Client Full Name *</label>
                        <input type="text" name="full_name" class="adm-input" required placeholder="e.g. Vikramaditya Singhania">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Contact Phone *</label>
                        <input type="text" name="phone" class="adm-input" required placeholder="+91 98200 12345">
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Email Address</label>
                        <input type="email" name="email" class="adm-input" placeholder="client@domain.com">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Preferred City</label>
                        <input type="text" name="preferred_city" class="adm-input" placeholder="e.g. Mumbai, Bengaluru, Dubai">
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Target Investment Budget</label>
                        <input type="text" name="budget_range" class="adm-input" placeholder="e.g. ₹15 Cr - ₹30 Cr">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Deal Value Estimate (₹ Crores)</label>
                        <input type="number" step="0.01" name="deal_value_cr" class="adm-input" placeholder="e.g. 22.50">
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Target Luxury Property (Optional)</label>
                    <select name="property_id" class="adm-select">
                        <option value="">-- General Luxury Portfolio Advisory --</option>
                        <?php foreach ($properties_list as $pl): ?>
                            <option value="<?= $pl['id'] ?>"><?= e($pl['title']) ?> (<?= e($pl['locality']) ?> &bull; <?= e($pl['price_display']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="adm-grid-3">
                    <div class="adm-form-group">
                        <label class="adm-label">Initial Stage</label>
                        <select name="lead_status" class="adm-select">
                            <?php foreach ($stages as $sk => $sm): ?>
                                <option value="<?= $sk ?>" <?= ($sk === 'new') ? 'selected' : '' ?>><?= $sm['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Lead Acquisition Source</label>
                        <select name="lead_source" class="adm-select">
                            <?php foreach ($sources_list as $src): ?>
                                <option value="<?= e($src) ?>" <?= ($src === 'Direct Call') ? 'selected' : '' ?>><?= e($src) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Assign Advisor</label>
                        <select name="assigned_agent_id" class="adm-select">
                            <?php if ($admin_role !== 'agent'): ?>
                                <option value="">-- Unassigned --</option>
                                <?php foreach ($assignable_agents as $ag): ?>
                                    <option value="<?= $ag['id'] ?>" <?= ($ag['id'] == $logged_admin['id']) ? 'selected' : '' ?>><?= e($ag['name']) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="<?= $logged_admin['id'] ?>"><?= e($logged_admin['name']) ?> (Me)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Initial Follow-up Date & Time</label>
                    <input type="datetime-local" name="follow_up_date" class="adm-input" value="<?= date('Y-m-d\TH:i', strtotime('+1 day 11:00')) ?>">
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Discovery & Client Notes</label>
                    <textarea name="admin_notes" class="adm-textarea" rows="2" placeholder="Client family preferences, sea-view requirements, Vastu alignment, possession urgency..."></textarea>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-gold">
                    <i class="fa-solid fa-check"></i> Onboard Lead & Schedule Reminder
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     MODAL: QUICK UPDATE LEAD PIPELINE & ADVISOR
     ========================================== -->
<div class="adm-modal-backdrop" id="modalQuickUpdateLead">
    <div class="adm-modal" style="max-width: 580px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title">
                <i class="fa-solid fa-arrows-split-up-and-left" style="color: var(--adm-gold);"></i> Quick Pipeline Position Update
            </h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="leads.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="quick_update_stage">
            <input type="hidden" name="lead_id" id="modal_q_lead_id">

            <div class="adm-modal-body">
                <!-- Client Card Header -->
                <div style="background: #F8FAFC; border: 1px solid var(--adm-border); border-radius: 8px; padding: 1rem; margin-bottom: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-weight: 800; color: var(--adm-navy); font-size: 1rem;" id="modal_q_name"></div>
                        <a href="#" id="modal_q_dossier_link" class="adm-btn adm-btn-sm adm-btn-navy" style="font-size: 0.72rem;">
                            View 360° Dossier &rarr;
                        </a>
                    </div>
                    <div style="font-size: 0.8rem; color: var(--adm-text-muted); margin-top: 0.35rem;" id="modal_q_details"></div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Pipeline Stage *</label>
                        <select name="lead_status" id="modal_q_stage" class="adm-select" required>
                            <?php foreach ($stages as $sk => $sm): ?>
                                <option value="<?= $sk ?>"><?= $sm['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Deal Value Estimate (₹ Cr)</label>
                        <input type="number" step="0.01" name="deal_value_cr" id="modal_q_deal" class="adm-input" placeholder="e.g. 15.50">
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Assigned Advisor</label>
                        <select name="assigned_agent_id" id="modal_q_agent" class="adm-select">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($assignable_agents as $ag): ?>
                                <option value="<?= $ag['id'] ?>"><?= e($ag['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Next Follow-Up Date & Time</label>
                        <input type="datetime-local" name="follow_up_date" id="modal_q_follow" class="adm-input">
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Interaction Log / Update Notes</label>
                    <textarea name="admin_notes" id="modal_q_notes" class="adm-textarea" rows="3" placeholder="Summary of client conversation, price objections, viewing schedule feedback..."></textarea>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-navy">
                    <i class="fa-solid fa-floppy-disk"></i> Save Position & Log Activity
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.btn-open-quick-modal').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const id = this.dataset.id;
        document.getElementById('modal_q_lead_id').value = id;
        document.getElementById('modal_q_name').textContent = this.dataset.name + " (" + this.dataset.phone + ")";
        document.getElementById('modal_q_details').textContent = "Property: " + this.dataset.property + " | City: " + this.dataset.city + " | Budget: " + this.dataset.budget;
        document.getElementById('modal_q_stage').value = this.dataset.stage;
        document.getElementById('modal_q_deal').value = this.dataset.deal || '';
        document.getElementById('modal_q_agent').value = this.dataset.agent || '';
        document.getElementById('modal_q_follow').value = this.dataset.follow || '';
        document.getElementById('modal_q_notes').value = this.dataset.notes || '';
        document.getElementById('modal_q_dossier_link').href = 'lead-detail.php?id=' + id;
        
        document.getElementById('modalQuickUpdateLead').classList.add('show');
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

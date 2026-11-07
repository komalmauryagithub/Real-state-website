<?php
/**
 * EstateVista India - Real Estate Agent CRM Dashboard
 * Executive workstation for advisory partners, lead tracking, conversion metrics, and daily action queues.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';
require_once __DIR__ . '/includes/crm_functions.php';

require_admin_login();

$db = Database::getConnection();
$current_admin = get_logged_in_admin();
$admin_role = get_admin_role();

// Determine which agent we are viewing:
// If agent role, force to own id; if super_admin or admin, allow switching via GET param
if ($admin_role === 'agent') {
    $active_agent_id = (int)$current_admin['id'];
} else {
    $active_agent_id = isset($_GET['agent_id']) && $_GET['agent_id'] !== '' ? (int)$_GET['agent_id'] : null;
}

$success_msg = '';
$error_msg = '';

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_admin_csrf()) {
        $error_msg = "Security token expired. Please reload and try again.";
    } else {
        $action = sanitize($_POST['action'] ?? '');

        // 1. Complete Reminder
        if ($action === 'complete_reminder') {
            $rem_id = (int)($_POST['reminder_id'] ?? 0);
            if ($rem_id > 0 && complete_crm_reminder($rem_id)) {
                $success_msg = "Follow-up reminder marked as completed.";
            } else {
                $error_msg = "Failed to update reminder.";
            }
        }

        // 2. Schedule New Reminder
        elseif ($action === 'create_reminder') {
            $lead_id = (int)($_POST['lead_id'] ?? 0);
            $title = sanitize($_POST['title'] ?? '');
            $remind_at = sanitize($_POST['remind_at'] ?? '');
            $priority = sanitize($_POST['priority'] ?? 'medium');
            $notes = sanitize($_POST['notes'] ?? '');
            $agent_for = !empty($_POST['agent_id']) ? (int)$_POST['agent_id'] : ($active_agent_id ?: $current_admin['id']);

            if ($lead_id <= 0 || empty($title) || empty($remind_at)) {
                $error_msg = "Lead selection, reminder title, and date/time are required.";
            } else {
                if (create_crm_reminder($lead_id, $agent_for, $title, $remind_at, $priority, $notes)) {
                    $success_msg = "Follow-up reminder scheduled successfully.";
                } else {
                    $error_msg = "Failed to create reminder.";
                }
            }
        }

        // 3. Create Direct Lead
        elseif ($action === 'create_lead') {
            $name = sanitize($_POST['full_name'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $city = sanitize($_POST['city'] ?? '');
            $pref_city = sanitize($_POST['preferred_city'] ?? $city);
            $budget = sanitize($_POST['budget_range'] ?? '');
            $source = sanitize($_POST['lead_source'] ?? 'Direct Call');
            $stage = sanitize($_POST['lead_status'] ?? 'new');
            $prop_id = !empty($_POST['property_id']) ? (int)$_POST['property_id'] : null;
            $deal_val = !empty($_POST['deal_value']) ? (float)$_POST['deal_value'] * 10000000 : 0.00;
            $notes = sanitize($_POST['notes'] ?? '');
            $assign_to = !empty($_POST['assigned_agent_id']) ? (int)$_POST['assigned_agent_id'] : ($active_agent_id ?: $current_admin['id']);

            if (empty($name) || empty($phone)) {
                $error_msg = "Client full name and phone number are required.";
            } else {
                try {
                    $ins = $db->prepare("
                        INSERT INTO inquiries (
                            full_name, phone, email, city, preferred_city, budget_range,
                            property_id, assigned_agent_id, lead_source, lead_status, deal_value,
                            message, created_at
                        ) VALUES (
                            :name, :phone, :email, :city, :pcity, :budget,
                            :pid, :aid, :source, :status, :deal,
                            :msg, NOW()
                        )
                    ");
                    $ins->execute([
                        ':name' => $name,
                        ':phone' => $phone,
                        ':email' => $email,
                        ':city' => $city,
                        ':pcity' => $pref_city,
                        ':budget' => $budget,
                        ':pid' => $prop_id,
                        ':aid' => $assign_to,
                        ':source' => $source,
                        ':status' => $stage,
                        ':deal' => $deal_val,
                        ':msg' => $notes ?: 'Direct lead entered via Agent CRM'
                    ]);
                    $newLeadId = $db->lastInsertId();

                    // Log initial interaction note
                    if (!empty($notes)) {
                        add_crm_lead_note($newLeadId, $current_admin['id'], $current_admin['name'], 'general', $notes);
                    }

                    log_admin_activity('Created Direct CRM Lead', "Created lead #{$newLeadId} ({$name})");
                    $success_msg = "Direct lead {$name} successfully logged into CRM.";
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Agent CRM KPIs
$crm_stats = get_crm_agent_stats($active_agent_id);

// Fetch Reminders for Today & Overdue
$reminders_queue = get_crm_reminders([
    'agent_id' => $active_agent_id,
    'status' => 'pending',
    'limit' => 6
]);

// Fetch Upcoming Site Visits
$upcoming_visits = $db->query("
    SELECT i.*, p.title as property_title, p.locality as property_locality, p.slug as property_slug,
           adm.name as agent_name
    FROM inquiries i
    LEFT JOIN properties p ON i.property_id = p.id
    LEFT JOIN admins adm ON i.assigned_agent_id = adm.id
    WHERE i.inquiry_type = 'site_visit'
      AND i.visit_date >= CURDATE()
      AND COALESCE(i.status, 'new') != 'closed'
      " . ($active_agent_id ? "AND i.assigned_agent_id = {$active_agent_id}" : "") . "
    ORDER BY i.visit_date ASC, i.visit_time ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Assigned Properties
$assigned_properties = [];
if ($active_agent_id) {
    $assigned_properties = get_crm_agent_properties($active_agent_id);
} else {
    // Show top 6 approved properties overall
    $assigned_properties = $db->query("
        SELECT p.*, c.name as city_name, cat.name as category_name,
               (SELECT COUNT(*) FROM inquiries i WHERE i.property_id = p.id) as inquiries_count
        FROM properties p
        LEFT JOIN cities c ON p.city_id = c.id
        LEFT JOIN categories cat ON p.category_id = cat.id
        WHERE p.approval_status = 'approved'
        ORDER BY p.price DESC
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Recent CRM Interaction Activities
$recent_activities = $db->query("
    SELECT ln.*, i.full_name as client_name, i.lead_status, p.title as property_title
    FROM lead_notes ln
    JOIN inquiries i ON ln.lead_id = i.id
    LEFT JOIN properties p ON i.property_id = p.id
    " . ($active_agent_id ? "WHERE ln.agent_id = {$active_agent_id}" : "") . "
    ORDER BY ln.created_at DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// Chart Data: Pipeline Stage Counts & Values
$pipeline_chart_data = get_crm_pipeline_distribution($active_agent_id);
// Chart Data: Lead Sources Breakdown
$sources_chart_data = get_crm_lead_sources($active_agent_id);

// List of assignable advisors for dropdowns
$assignable_agents = get_all_assignable_agents();

// List of recent leads for reminder modal
$leads_for_picker = get_crm_leads([
    'agent_id' => $active_agent_id,
    'limit' => 30
]);

$page_title = "Agent CRM Command Center";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Header & Agent Switcher -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.25rem;">
                        Real Estate Agent CRM Cockpit
                    </h1>
                    <span class="adm-status-badge adm-badge-gold">
                        <i class="fa-solid fa-chart-line"></i> Live Pipeline
                    </span>
                </div>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                    Portfolio deal flow, client follow-up alerts, luxury site inspections, and conversion performance.
                </p>
            </div>

            <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                <?php if ($admin_role !== 'agent'): ?>
                    <!-- Supervisor Agent Switcher -->
                    <form method="GET" action="crm-dashboard.php" style="display: flex; align-items: center; gap: 0.5rem;">
                        <label style="font-size: 0.82rem; font-weight: 600; color: var(--adm-navy); white-space: nowrap;">
                            <i class="fa-solid fa-user-tie" style="color: var(--adm-gold);"></i> View Advisor:
                        </label>
                        <select name="agent_id" class="adm-select" style="min-width: 220px;" onchange="this.form.submit()">
                            <option value="">-- All Advisory Team --</option>
                            <?php foreach ($assignable_agents as $ag): ?>
                                <option value="<?= $ag['id'] ?>" <?= ($active_agent_id === (int)$ag['id']) ? 'selected' : '' ?>>
                                    <?= e($ag['name']) ?> (<?= ucfirst($ag['role']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>

                <button class="adm-btn adm-btn-outline" data-adm-modal-target="modalScheduleReminder">
                    <i class="fa-solid fa-bell"></i> Set Reminder
                </button>
                <button class="adm-btn adm-btn-gold" data-adm-modal-target="modalNewLead">
                    <i class="fa-solid fa-user-plus"></i> New VIP Lead
                </button>
            </div>
        </div>

        <!-- Feedback Alerts -->
        <?php if (!empty($success_msg)): ?>
            <div style="background: #ECFDF5; border-left: 4px solid var(--adm-success); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; color: #065F46; font-weight: 500;">
                <i class="fa-solid fa-circle-check"></i> <?= e($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div style="background: #FEF2F2; border-left: 4px solid var(--adm-danger); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; color: #991B1B; font-weight: 500;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= e($error_msg) ?>
            </div>
        <?php endif; ?>

        <!-- ==========================================
             1. AGENT CRM STATS CARDS
             ========================================== -->
        <div class="adm-kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));">
            <!-- Assigned Leads -->
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val"><?= number_format($crm_stats['assigned_leads']) ?></div>
                    <div class="adm-kpi-label">Total Assigned Leads</div>
                    <div style="font-size: 0.72rem; color: #2563EB; font-weight: 600; margin-top: 4px;">
                        <?= $crm_stats['active_leads'] ?> in Active Progress
                    </div>
                </div>
                <div class="adm-kpi-icon adm-kpi-blue">
                    <i class="fa-solid fa-users-viewfinder"></i>
                </div>
            </div>

            <!-- New Enquiries -->
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: #D97706;"><?= number_format($crm_stats['new_enquiries']) ?></div>
                    <div class="adm-kpi-label">Uncontacted Enquiries</div>
                    <div style="font-size: 0.72rem; color: var(--adm-text-muted); margin-top: 4px;">
                        Immediate Call SLA &lt; 2h
                    </div>
                </div>
                <div class="adm-kpi-icon adm-kpi-gold">
                    <i class="fa-solid fa-bell-concierge"></i>
                </div>
            </div>

            <!-- Follow-up Reminders -->
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: <?= $crm_stats['reminders_overdue'] > 0 ? '#DC2626' : '#2563EB' ?>;">
                        <?= $crm_stats['reminders_total_pending'] ?>
                    </div>
                    <div class="adm-kpi-label">Pending Follow-ups</div>
                    <div style="font-size: 0.72rem; margin-top: 4px;">
                        <?php if ($crm_stats['reminders_overdue'] > 0): ?>
                            <span style="color: #DC2626; font-weight: 700;"><i class="fa-solid fa-triangle-exclamation"></i> <?= $crm_stats['reminders_overdue'] ?> Overdue</span> | 
                        <?php endif; ?>
                        <span style="color: #D97706; font-weight: 600;"><?= $crm_stats['reminders_today'] ?> Today</span>
                    </div>
                </div>
                <div class="adm-kpi-icon adm-kpi-purple">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
            </div>

            <!-- Site Visits -->
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: #059669;"><?= $crm_stats['site_visits_upcoming'] ?></div>
                    <div class="adm-kpi-label">Upcoming Site Visits</div>
                    <div style="font-size: 0.72rem; color: var(--adm-text-muted); margin-top: 4px;">
                        Chauffeured VIP Tours
                    </div>
                </div>
                <div class="adm-kpi-icon adm-kpi-green">
                    <i class="fa-solid fa-car-side"></i>
                </div>
            </div>

            <!-- Lead Conversion Statistics -->
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val" style="color: #059669;"><?= $crm_stats['conversion_rate'] ?>%</div>
                    <div class="adm-kpi-label">Lead Conversion Rate</div>
                    <div style="font-size: 0.72rem; color: #047857; font-weight: 700; margin-top: 4px;">
                        ₹<?= number_format($crm_stats['total_converted_val_cr'], 2) ?> Cr Won
                    </div>
                </div>
                <div class="adm-kpi-icon adm-kpi-green">
                    <i class="fa-solid fa-trophy"></i>
                </div>
            </div>

            <!-- Assigned Properties Portfolio -->
            <div class="adm-kpi-card">
                <div>
                    <div class="adm-kpi-val"><?= $crm_stats['assigned_properties'] ?></div>
                    <div class="adm-kpi-label">Assigned Residences</div>
                    <div style="font-size: 0.72rem; color: var(--adm-navy); font-weight: 700; margin-top: 4px;">
                        ₹<?= number_format($crm_stats['assigned_properties_val_cr'], 2) ?> Cr Asset Pool
                    </div>
                </div>
                <div class="adm-kpi-icon adm-kpi-blue">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
            </div>
        </div>

        <!-- ==========================================
             2. INTERACTIVE CHARTS & VISUALIZATIONS
             ========================================== -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
            <!-- Pipeline Distribution Chart -->
            <div class="adm-card" style="margin-bottom: 0;">
                <div class="adm-card-header">
                    <div class="adm-card-title">
                        <i class="fa-solid fa-filter-circle-dollar" style="color: var(--adm-gold);"></i>
                        Lead Pipeline Breakdown & Deal Exposure
                    </div>
                    <a href="leads.php" class="adm-btn adm-btn-sm adm-btn-outline">
                        <i class="fa-solid fa-table-columns"></i> Open Full Kanban
                    </a>
                </div>
                <div class="adm-card-body">
                    <div style="height: 270px; position: relative;">
                        <canvas id="crmPipelineChart"></canvas>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 1rem; border-top: 1px solid #F1F5F9; padding-top: 0.75rem; font-size: 0.8rem; color: var(--adm-text-muted);">
                        <span>Total Active Deals in Negotiation: <strong><?= $pipeline_chart_data['counts']['negotiation'] ?? 0 ?></strong></span>
                        <span style="color: #059669; font-weight: 700;">Negotiation Value: ₹<?= number_format($pipeline_chart_data['values_cr']['negotiation'] ?? 0, 2) ?> Cr</span>
                    </div>
                </div>
            </div>

            <!-- Lead Acquisition Sources -->
            <div class="adm-card" style="margin-bottom: 0;">
                <div class="adm-card-header">
                    <div class="adm-card-title">
                        <i class="fa-solid fa-chart-pie" style="color: var(--adm-gold);"></i>
                        Acquisition Channels
                    </div>
                </div>
                <div class="adm-card-body">
                    <div style="height: 270px; position: relative;">
                        <canvas id="crmSourceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================
             3. ACTION QUEUE: REMINDERS & SITE VISITS
             ========================================== -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
            <!-- Follow-up Reminders Queue -->
            <div class="adm-card" style="margin-bottom: 0; display: flex; flex-direction: column;">
                <div class="adm-card-header">
                    <div class="adm-card-title">
                        <i class="fa-solid fa-clock-rotate-left" style="color: var(--adm-gold);"></i>
                        Follow-Up Action Queue
                        <?php if ($crm_stats['reminders_overdue'] > 0): ?>
                            <span class="adm-status-badge adm-badge-rejected" style="font-size: 0.68rem;">
                                <?= $crm_stats['reminders_overdue'] ?> OVERDUE
                            </span>
                        <?php endif; ?>
                    </div>
                    <a href="reminders.php" class="adm-btn adm-btn-sm adm-btn-outline">View All (<?= $crm_stats['reminders_total_pending'] ?>)</a>
                </div>
                <div class="adm-card-body" style="flex: 1; padding: 1.25rem;">
                    <?php if (empty($reminders_queue)): ?>
                        <div style="text-align: center; padding: 2.5rem 1rem; color: var(--adm-text-muted);">
                            <i class="fa-solid fa-circle-check" style="font-size: 2.5rem; color: #10B981; margin-bottom: 0.5rem; display: block;"></i>
                            All follow-up reminders are up to date!
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php foreach ($reminders_queue as $rem): ?>
                                <?php 
                                    $remTime = strtotime($rem['remind_at']);
                                    $isOverdue = ($remTime < time() && $rem['status'] === 'pending');
                                    $isToday = (date('Y-m-d', $remTime) === date('Y-m-d') && $rem['status'] === 'pending');
                                    $boxClass = $isOverdue ? 'overdue' : ($isToday ? 'today' : 'upcoming');
                                ?>
                                <div class="crm-reminder-card <?= $boxClass ?>" style="margin-bottom: 0;">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                            <span class="adm-status-badge adm-priority-<?= $rem['priority'] ?>">
                                                <?= strtoupper($rem['priority']) ?>
                                            </span>
                                            <strong style="color: var(--adm-navy); font-size: 0.88rem;">
                                                <a href="lead-detail.php?id=<?= $rem['lead_id'] ?>" style="text-decoration: none; color: inherit;">
                                                    <?= e($rem['client_name']) ?>
                                                </a>
                                            </strong>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #475569; margin-bottom: 0.25rem;">
                                            <?= e($rem['title']) ?>
                                        </div>
                                        <div style="font-size: 0.72rem; color: var(--adm-text-muted); display: flex; gap: 0.75rem;">
                                            <span>
                                                <i class="fa-regular fa-clock" style="color: <?= $isOverdue ? '#DC2626' : 'var(--adm-gold)' ?>;"></i>
                                                <?= date('d M, h:i A', $remTime) ?>
                                            </span>
                                            <span><i class="fa-solid fa-phone"></i> <?= e($rem['client_phone']) ?></span>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 0.35rem; align-items: center;">
                                        <form method="POST" action="crm-dashboard.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                            <input type="hidden" name="action" value="complete_reminder">
                                            <input type="hidden" name="reminder_id" value="<?= $rem['id'] ?>">
                                            <button type="submit" class="adm-btn adm-btn-sm adm-btn-outline" style="color: #059669; border-color: #A7F3D0;" title="Mark Done">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                        <a href="lead-detail.php?id=<?= $rem['lead_id'] ?>" class="adm-btn adm-btn-sm adm-btn-navy" title="Open Dossier">
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Site Visits -->
            <div class="adm-card" style="margin-bottom: 0; display: flex; flex-direction: column;">
                <div class="adm-card-header">
                    <div class="adm-card-title">
                        <i class="fa-solid fa-car-side" style="color: var(--adm-gold);"></i>
                        Upcoming Site Visits & Private Viewings
                    </div>
                    <a href="site-visits.php" class="adm-btn adm-btn-sm adm-btn-outline">Calendar</a>
                </div>
                <div class="adm-card-body" style="flex: 1; padding: 1.25rem;">
                    <?php if (empty($upcoming_visits)): ?>
                        <div style="text-align: center; padding: 2.5rem 1rem; color: var(--adm-text-muted);">
                            <i class="fa-regular fa-calendar-xmark" style="font-size: 2.5rem; color: #CBD5E1; margin-bottom: 0.5rem; display: block;"></i>
                            No upcoming site visits scheduled for this period.
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php foreach ($upcoming_visits as $v): ?>
                                <div style="background: #F8FAFC; border: 1px solid var(--adm-border); border-radius: 8px; padding: 0.85rem 1rem; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 700; color: var(--adm-navy); font-size: 0.88rem;">
                                            <?= e($v['full_name']) ?>
                                        </div>
                                        <div style="font-size: 0.78rem; color: #334155;">
                                            <i class="fa-solid fa-building" style="color: var(--adm-gold); font-size: 0.7rem;"></i>
                                            <?= e($v['property_title'] ?: 'Bespoke Portfolio Viewing') ?>
                                        </div>
                                        <div style="font-size: 0.72rem; color: var(--adm-text-muted); margin-top: 2px;">
                                            <i class="fa-regular fa-calendar"></i> <?= date('d M Y', strtotime($v['visit_date'])) ?> &bull; 
                                            <i class="fa-regular fa-clock"></i> <?= e($v['visit_time'] ?: 'Morning Slot') ?>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 0.35rem;">
                                        <a href="tel:<?= e($v['phone']) ?>" class="crm-comm-btn crm-comm-call" title="Call Client">
                                            <i class="fa-solid fa-phone"></i>
                                        </a>
                                        <a href="lead-detail.php?id=<?= $v['id'] ?>" class="adm-btn adm-btn-sm adm-btn-gold" title="Dossier">
                                            Manage
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ==========================================
             4. ASSIGNED PROPERTIES & RECENT ACTIVITIES
             ========================================== -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
            <!-- Assigned Luxury Properties -->
            <div class="adm-card" style="margin-bottom: 0;">
                <div class="adm-card-header">
                    <div class="adm-card-title">
                        <i class="fa-solid fa-building" style="color: var(--adm-gold);"></i>
                        Assigned Properties Portfolio (<?= count($assigned_properties) ?>)
                    </div>
                    <a href="agent-properties.php" class="adm-btn adm-btn-sm adm-btn-outline">Full Portfolio Desk</a>
                </div>
                <div class="adm-table-responsive">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Location</th>
                                <th>Price</th>
                                <th>Inquiries</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assigned_properties)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2.5rem; color: var(--adm-text-muted);">
                                        No luxury residences currently assigned to this advisor.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assigned_properties as $ap): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <img src="<?= e(safe_image_url($ap['cover_image'], 'property')) ?>" alt="<?= e($ap['title']) ?>"
                                                     style="width: 48px; height: 36px; border-radius: 4px; object-fit: cover;"
                                                     onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                                                <div style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 700; color: var(--adm-navy);">
                                                    <a href="<?= BASE_URL ?>property-detail.php?slug=<?= urlencode($ap['slug']) ?>" target="_blank" style="text-decoration: none; color: inherit;">
                                                        <?= e($ap['title']) ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="font-size: 0.8rem; color: var(--adm-text-muted);">
                                            <?= e($ap['locality']) ?>, <?= e($ap['city_name']) ?>
                                        </td>
                                        <td style="font-weight: 700; color: var(--adm-navy);">
                                            <?= e($ap['price_display']) ?>
                                        </td>
                                        <td>
                                            <span style="font-weight: 700; color: #2563EB;"><?= $ap['inquiries_count'] ?></span> Inquiries
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="leads.php?search=<?= urlencode($ap['title']) ?>" class="adm-btn adm-btn-sm adm-btn-outline" title="Filter Leads for this Property">
                                                <i class="fa-solid fa-users"></i> Leads
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent CRM Activities & Notes -->
            <div class="adm-card" style="margin-bottom: 0;">
                <div class="adm-card-header">
                    <div class="adm-card-title">
                        <i class="fa-solid fa-list-ul" style="color: var(--adm-gold);"></i>
                        Recent Activities & Interactions
                    </div>
                </div>
                <div class="adm-card-body" style="padding: 1.25rem;">
                    <?php if (empty($recent_activities)): ?>
                        <div style="text-align: center; padding: 2rem 1rem; color: var(--adm-text-muted); font-size: 0.85rem;">
                            No recent customer interactions recorded.
                        </div>
                    <?php else: ?>
                        <ul class="adm-activity-list">
                            <?php foreach ($recent_activities as $ra): ?>
                                <li class="adm-activity-item" style="padding-bottom: 1.2rem;">
                                    <div class="adm-activity-dot"></div>
                                    <div class="adm-activity-title" style="font-size: 0.85rem;">
                                        <a href="lead-detail.php?id=<?= $ra['lead_id'] ?>" style="text-decoration: none; color: inherit;">
                                            <?= e($ra['client_name']) ?>
                                        </a>
                                        <span class="adm-status-badge adm-badge-<?= $ra['lead_status'] ?>" style="font-size: 0.65rem; margin-left: 0.35rem;">
                                            <?= strtoupper(str_replace('_', ' ', $ra['lead_status'])) ?>
                                        </span>
                                    </div>
                                    <div class="adm-activity-desc" style="font-size: 0.78rem; line-height: 1.4;">
                                        <?= e(substr($ra['note'], 0, 75)) ?><?= strlen($ra['note']) > 75 ? '...' : '' ?>
                                    </div>
                                    <div class="adm-activity-time">
                                        <?= format_admin_date($ra['created_at']) ?> &bull; <strong style="color: var(--adm-navy);"><?= e($ra['agent_name']) ?></strong>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- ==========================================
     MODAL: SCHEDULE FOLLOW-UP REMINDER
     ========================================== -->
<div class="adm-modal-backdrop" id="modalScheduleReminder">
    <div class="adm-modal" style="max-width: 550px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-bell" style="color: var(--adm-gold);"></i> Schedule Follow-Up Reminder</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="crm-dashboard.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="create_reminder">
            <?php if ($active_agent_id): ?>
                <input type="hidden" name="agent_id" value="<?= $active_agent_id ?>">
            <?php endif; ?>
            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Target Client / Lead *</label>
                    <select name="lead_id" class="adm-select" required>
                        <option value="">-- Select Client --</option>
                        <?php foreach ($leads_for_picker as $lp): ?>
                            <option value="<?= $lp['id'] ?>">
                                <?= e($lp['full_name']) ?> (<?= e($lp['phone']) ?> &bull; <?= e($lp['budget_range']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Reminder Objective / Task *</label>
                    <input type="text" name="title" class="adm-input" required placeholder="e.g. Discuss revised term sheet with family office">
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Due Date & Time *</label>
                        <input type="datetime-local" name="remind_at" class="adm-input" required value="<?= date('Y-m-d\TH:i', strtotime('+1 day 11:00')) ?>">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Priority</label>
                        <select name="priority" class="adm-select">
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent (Deal Breaker)</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Preparation Notes</label>
                    <textarea name="notes" class="adm-textarea" rows="2" placeholder="Key topics, pricing boundaries, Vaastu inquiries..."></textarea>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-gold">Schedule Follow-Up</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     MODAL: LOG NEW DIRECT VIP LEAD
     ========================================== -->
<div class="adm-modal-backdrop" id="modalNewLead">
    <div class="adm-modal" style="max-width: 620px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-user-plus" style="color: var(--adm-gold);"></i> Onboard VIP Client Lead</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="crm-dashboard.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="create_lead">
            <div class="adm-modal-body">
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Client Full Name *</label>
                        <input type="text" name="full_name" class="adm-input" required placeholder="e.g. Harsh Mariwala">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Phone Number *</label>
                        <input type="text" name="phone" class="adm-input" required placeholder="+91 98200 12345">
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Email Address</label>
                        <input type="email" name="email" class="adm-input" placeholder="client@domain.com">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">City of Residence</label>
                        <input type="text" name="city" class="adm-input" placeholder="e.g. Mumbai, London">
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Preferred City Target</label>
                        <select name="preferred_city" class="adm-select">
                            <option value="Mumbai">Mumbai</option>
                            <option value="Delhi NCR">Delhi NCR</option>
                            <option value="Bengaluru">Bengaluru</option>
                            <option value="Hyderabad">Hyderabad</option>
                            <option value="Pune">Pune</option>
                            <option value="Goa">Goa</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Acquisition Budget</label>
                        <input type="text" name="budget_range" class="adm-input" placeholder="e.g. ₹35 Cr - ₹50 Cr">
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Lead Acquisition Source *</label>
                        <select name="lead_source" class="adm-select">
                            <option value="Direct Call">Direct Phone Call</option>
                            <option value="Website Enquiry">Website Enquiry</option>
                            <option value="Callback Request">Callback Request</option>
                            <option value="Site Visit Booking">Site Visit Booking</option>
                            <option value="Referral">HNI Referral</option>
                            <option value="HNI Private Network">HNI Private Network</option>
                            <option value="Social Campaign">Digital / Instagram Campaign</option>
                            <option value="Walk-in Concierge">Walk-in Concierge Desk</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Initial Pipeline Stage</label>
                        <select name="lead_status" class="adm-select">
                            <option value="new">New Discovery</option>
                            <option value="contacted">Contacted</option>
                            <option value="interested" selected>Interested</option>
                            <option value="site_visit_scheduled">Site Visit Scheduled</option>
                            <option value="negotiation">Negotiation</option>
                            <option value="converted">Converted</option>
                        </select>
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Assign Wealth Advisor</label>
                        <select name="assigned_agent_id" class="adm-select">
                            <?php foreach ($assignable_agents as $ag): ?>
                                <option value="<?= $ag['id'] ?>" <?= ($active_agent_id === (int)$ag['id']) ? 'selected' : '' ?>>
                                    <?= e($ag['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Estimated Deal Value (in ₹ Cr)</label>
                        <input type="number" step="0.5" name="deal_value" class="adm-input" placeholder="e.g. 45.0">
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Discovery Call Notes</label>
                    <textarea name="notes" class="adm-textarea" rows="2" placeholder="Specific requirements: sea view, Vaastu east-facing, private elevator..."></textarea>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-gold">Create Lead Dossier</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     CHART.JS DATA SCRIPTS
     ========================================== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Pipeline Bar Chart
    const pipelineCtx = document.getElementById('crmPipelineChart');
    if (pipelineCtx) {
        const pipelineData = <?= json_encode($pipeline_chart_data) ?>;
        const labels = Object.values(pipelineData.stages);
        const counts = Object.values(pipelineData.counts);
        const values = Object.values(pipelineData.values_cr);

        new Chart(pipelineCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Active Leads',
                        data: counts,
                        backgroundColor: '#0B1528',
                        borderRadius: 6,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Deal Value (₹ Cr)',
                        data: values,
                        backgroundColor: '#C5A880',
                        borderRadius: 6,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { family: "'Plus Jakarta Sans', sans-serif", size: 12 } }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Leads Count' },
                        grid: { color: '#F1F5F9' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Deal Value (₹ Cr)' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }

    // 2. Acquisition Channels Donut
    const sourceCtx = document.getElementById('crmSourceChart');
    if (sourceCtx) {
        const sourceData = <?= json_encode($sources_chart_data) ?>;
        const sLabels = sourceData.map(s => s.source);
        const sCounts = sourceData.map(s => parseInt(s.count, 10));

        new Chart(sourceCtx, {
            type: 'doughnut',
            data: {
                labels: sLabels,
                datasets: [{
                    data: sCounts,
                    backgroundColor: [
                        '#0B1528', '#C5A880', '#2563EB', '#059669', '#D97706', '#7C3AED', '#EC4899', '#64748B'
                    ],
                    borderWidth: 2,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 }, padding: 10 }
                    }
                },
                cutout: '65%'
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

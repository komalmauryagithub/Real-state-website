<?php
/**
 * EstateVista India - Follow-up Reminders Command Desk
 * Proactive task queue for wealth advisors to manage client touchpoints and appointments.
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

// ==========================================
// HANDLE ACTIONS (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_admin_csrf()) {
        $error_msg = "Security token expired. Please reload and try again.";
    } else {
        $action = sanitize($_POST['action'] ?? '');

        // 1. Complete Reminder
        if ($action === 'complete_reminder') {
            $rem_id = (int)($_POST['reminder_id'] ?? 0);
            if ($rem_id > 0 && complete_crm_reminder($rem_id)) {
                $success_msg = "Follow-up reminder #{$rem_id} marked as completed.";
            } else {
                $error_msg = "Failed to update reminder status.";
            }
        }

        // 2. Reschedule Reminder
        elseif ($action === 'reschedule_reminder') {
            $rem_id = (int)($_POST['reminder_id'] ?? 0);
            $new_date = sanitize($_POST['remind_at'] ?? '');
            $notes = sanitize($_POST['notes'] ?? '');

            if ($rem_id > 0 && !empty($new_date)) {
                if (reschedule_crm_reminder($rem_id, $new_date, $notes)) {
                    $success_msg = "Reminder #{$rem_id} rescheduled to " . date('d M Y, h:i A', strtotime($new_date));
                } else {
                    $error_msg = "Unable to reschedule reminder.";
                }
            } else {
                $error_msg = "Please specify a valid future date and time.";
            }
        }

        // 3. Create Reminder
        elseif ($action === 'create_reminder') {
            $lead_id = (int)($_POST['lead_id'] ?? 0);
            $agent_id = !empty($_POST['agent_id']) ? (int)$_POST['agent_id'] : $logged_admin['id'];
            $title = sanitize($_POST['title'] ?? '');
            $remind_at = sanitize($_POST['remind_at'] ?? '');
            $priority = sanitize($_POST['priority'] ?? 'medium');
            $notes = sanitize($_POST['notes'] ?? '');

            if ($lead_id > 0 && !empty($title) && !empty($remind_at)) {
                if (create_crm_reminder($lead_id, $agent_id, $title, $remind_at, $priority, $notes)) {
                    $success_msg = "Follow-up task scheduled successfully.";
                } else {
                    $error_msg = "Failed to schedule follow-up reminder.";
                }
            } else {
                $error_msg = "Client, Title, and Date/Time are required.";
            }
        }
    }
}

// ==========================================
// FILTERS & TAB SELECTION
// ==========================================
$current_tab = $_GET['tab'] ?? 'today'; // 'today', 'overdue', 'upcoming', 'all_pending', 'completed'
$search = trim($_GET['search'] ?? '');
$priority_filter = trim($_GET['priority'] ?? '');
$agent_filter = trim($_GET['agent_id'] ?? '');

if ($admin_role === 'agent') {
    $agent_filter = (string)$logged_admin['id'];
}

// Calculate Live KPI counts
$cur_agent_param = ($admin_role === 'agent') ? $logged_admin['id'] : (!empty($agent_filter) ? (int)$agent_filter : null);
$agent_stats = get_crm_agent_stats($cur_agent_param);

// Fetch counts for tabs
$rem_filters = [
    'agent_id' => $agent_filter,
    'priority' => $priority_filter,
    'search' => $search
];

if ($current_tab === 'today') {
    $rem_filters['timeframe'] = 'today';
    $rem_filters['status'] = 'pending';
} elseif ($current_tab === 'overdue') {
    $rem_filters['timeframe'] = 'overdue';
    $rem_filters['status'] = 'pending';
} elseif ($current_tab === 'upcoming') {
    $rem_filters['timeframe'] = 'upcoming';
    $rem_filters['status'] = 'pending';
} elseif ($current_tab === 'completed') {
    $rem_filters['status'] = 'completed';
} else {
    // all_pending
    $rem_filters['status'] = 'pending';
}

$reminders = get_crm_reminders($rem_filters);

// Data for modal: Active leads dropdown
$lead_sql = "SELECT id, full_name, phone, lead_status FROM inquiries WHERE lead_status NOT IN ('converted', 'lost')";
if ($admin_role === 'agent') {
    $lead_sql .= " AND assigned_agent_id = " . (int)$logged_admin['id'];
}
$lead_sql .= " ORDER BY full_name ASC LIMIT 100";
$active_leads = $db->query($lead_sql)->fetchAll(PDO::FETCH_ASSOC);

$assignable_agents = get_all_assignable_agents();
$stages = get_crm_pipeline_stages();

$page_title = "Follow-Up Reminders & Tasks";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Top Executive Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                    <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; color: var(--adm-gold); background: rgba(197, 168, 128, 0.12); padding: 0.2rem 0.6rem; border-radius: 4px;">
                        Advisor Command Desk
                    </span>
                    <span style="color: var(--adm-text-muted); font-size: 0.8rem;">&bull; Action Queue</span>
                </div>
                <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--adm-navy); margin: 0;">
                    Follow-Up Reminders & Client Touchpoints
                </h1>
                <p style="color: var(--adm-text-muted); font-size: 0.88rem; margin: 0.25rem 0 0 0;">
                    Ensure zero lead leakage with prioritized callback alerts, viewing tours, and contract deadlines.
                </p>
            </div>

            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <a href="leads.php" class="adm-btn adm-btn-outline adm-btn-sm">
                    <i class="fa-solid fa-table-columns"></i> View Pipeline
                </a>
                <button type="button" class="adm-btn adm-btn-gold adm-btn-sm" data-adm-modal-target="modalCreateReminder">
                    <i class="fa-solid fa-bell"></i> Schedule Reminder
                </button>
            </div>
        </div>

        <!-- Metric Cards Bar -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <!-- Overdue -->
            <a href="reminders.php?tab=overdue<?= !empty($agent_filter) ? '&agent_id='.$agent_filter : '' ?>" 
               style="text-decoration: none; display: block;" class="adm-card">
                <div style="padding: 1.2rem; border-left: 4px solid #EF4444; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: #DC2626; font-weight: 700;">Overdue Reminders</div>
                        <div style="font-size: 1.6rem; font-weight: 800; color: #DC2626;"><?= $agent_stats['reminders_overdue'] ?></div>
                    </div>
                    <div style="width: 42px; height: 42px; border-radius: 50%; background: #FEF2F2; display: flex; align-items: center; justify-content: center; color: #EF4444; font-size: 1.1rem;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>
            </a>

            <!-- Due Today -->
            <a href="reminders.php?tab=today<?= !empty($agent_filter) ? '&agent_id='.$agent_filter : '' ?>" 
               style="text-decoration: none; display: block;" class="adm-card">
                <div style="padding: 1.2rem; border-left: 4px solid #F59E0B; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: #D97706; font-weight: 700;">Due Today</div>
                        <div style="font-size: 1.6rem; font-weight: 800; color: #D97706;"><?= $agent_stats['reminders_today'] ?></div>
                    </div>
                    <div style="width: 42px; height: 42px; border-radius: 50%; background: #FFFBEB; display: flex; align-items: center; justify-content: center; color: #F59E0B; font-size: 1.1rem;">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>
            </a>

            <!-- Upcoming -->
            <a href="reminders.php?tab=upcoming<?= !empty($agent_filter) ? '&agent_id='.$agent_filter : '' ?>" 
               style="text-decoration: none; display: block;" class="adm-card">
                <div style="padding: 1.2rem; border-left: 4px solid #3B82F6; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: #2563EB; font-weight: 700;">Upcoming (7 Days)</div>
                        <div style="font-size: 1.6rem; font-weight: 800; color: #2563EB;"><?= $agent_stats['reminders_upcoming'] ?></div>
                    </div>
                    <div style="width: 42px; height: 42px; border-radius: 50%; background: #EFF6FF; display: flex; align-items: center; justify-content: center; color: #3B82F6; font-size: 1.1rem;">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                </div>
            </a>

            <!-- Total Pending -->
            <a href="reminders.php?tab=all_pending<?= !empty($agent_filter) ? '&agent_id='.$agent_filter : '' ?>" 
               style="text-decoration: none; display: block;" class="adm-card">
                <div style="padding: 1.2rem; border-left: 4px solid var(--adm-navy); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--adm-text-muted); font-weight: 700;">Total Pending</div>
                        <div style="font-size: 1.6rem; font-weight: 800; color: var(--adm-navy);"><?= $agent_stats['reminders_total_pending'] ?></div>
                    </div>
                    <div style="width: 42px; height: 42px; border-radius: 50%; background: #F1F5F9; display: flex; align-items: center; justify-content: center; color: var(--adm-navy); font-size: 1.1rem;">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                </div>
            </a>
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

        <!-- Queue Navigation Tabs -->
        <div style="display: flex; gap: 0.5rem; border-bottom: 1px solid var(--adm-border); margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 0.25rem;">
            <a href="reminders.php?tab=today<?= !empty($agent_filter) ? '&agent_id='.$agent_filter : '' ?>" 
               class="adm-btn adm-btn-sm <?= ($current_tab === 'today') ? 'adm-btn-navy' : 'adm-btn-outline' ?>" style="border-radius: 6px 6px 0 0;">
                <i class="fa-solid fa-clock"></i> Due Today (<?= $agent_stats['reminders_today'] ?>)
            </a>
            <a href="reminders.php?tab=overdue<?= !empty($agent_filter) ? '&agent_id='.$agent_filter : '' ?>" 
               class="adm-btn adm-btn-sm <?= ($current_tab === 'overdue') ? 'adm-btn-navy' : 'adm-btn-outline' ?>" style="border-radius: 6px 6px 0 0; <?= ($agent_stats['reminders_overdue'] > 0) ? 'color: #DC2626; border-color: #FECACA;' : '' ?>">
                <i class="fa-solid fa-triangle-exclamation"></i> Overdue (<?= $agent_stats['reminders_overdue'] ?>)
            </a>
            <a href="reminders.php?tab=upcoming<?= !empty($agent_filter) ? '&agent_id='.$agent_filter : '' ?>" 
               class="adm-btn adm-btn-sm <?= ($current_tab === 'upcoming') ? 'adm-btn-navy' : 'adm-btn-outline' ?>" style="border-radius: 6px 6px 0 0;">
                <i class="fa-solid fa-calendar-days"></i> Upcoming
            </a>
            <a href="reminders.php?tab=all_pending<?= !empty($agent_filter) ? '&agent_id='.$agent_filter : '' ?>" 
               class="adm-btn adm-btn-sm <?= ($current_tab === 'all_pending') ? 'adm-btn-navy' : 'adm-btn-outline' ?>" style="border-radius: 6px 6px 0 0;">
                <i class="fa-solid fa-layer-group"></i> All Pending
            </a>
            <a href="reminders.php?tab=completed<?= !empty($agent_filter) ? '&agent_id='.$agent_filter : '' ?>" 
               class="adm-btn adm-btn-sm <?= ($current_tab === 'completed') ? 'adm-btn-navy' : 'adm-btn-outline' ?>" style="border-radius: 6px 6px 0 0;">
                <i class="fa-solid fa-circle-check"></i> Completed History
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="adm-card" style="margin-bottom: 1.5rem;">
            <div class="adm-card-body" style="padding: 1rem 1.25rem;">
                <form method="GET" action="reminders.php" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                    <input type="hidden" name="tab" value="<?= e($current_tab) ?>">

                    <div class="adm-search-input-wrap" style="flex: 1.5; min-width: 220px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" class="adm-input" placeholder="Search task title, client, notes..." value="<?= e($search) ?>">
                    </div>

                    <div style="min-width: 150px;">
                        <select name="priority" class="adm-select" onchange="this.form.submit()">
                            <option value="">All Priorities</option>
                            <option value="urgent" <?= ($priority_filter === 'urgent') ? 'selected' : '' ?>>🔥 Urgent</option>
                            <option value="high" <?= ($priority_filter === 'high') ? 'selected' : '' ?>>⚡ High Priority</option>
                            <option value="medium" <?= ($priority_filter === 'medium') ? 'selected' : '' ?>>Normal Medium</option>
                            <option value="low" <?= ($priority_filter === 'low') ? 'selected' : '' ?>>Low Priority</option>
                        </select>
                    </div>

                    <?php if ($admin_role !== 'agent'): ?>
                        <div style="min-width: 180px;">
                            <select name="agent_id" class="adm-select" onchange="this.form.submit()">
                                <option value="">All Advisors</option>
                                <?php foreach ($assignable_agents as $ag): ?>
                                    <option value="<?= $ag['id'] ?>" <?= ($agent_filter == $ag['id']) ? 'selected' : '' ?>>
                                        <?= e($ag['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="adm-btn adm-btn-navy">
                        <i class="fa-solid fa-filter"></i> Apply
                    </button>

                    <?php if (!empty($search) || !empty($priority_filter) || (!empty($agent_filter) && $admin_role !== 'agent')): ?>
                        <a href="reminders.php?tab=<?= e($current_tab) ?>" class="adm-btn adm-btn-outline" title="Reset">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Reminders List -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php if (empty($reminders)): ?>
                <div class="adm-card" style="padding: 3.5rem 1.5rem; text-align: center;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #ECFDF5; color: #059669; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--adm-navy); margin-bottom: 0.35rem;">
                        No Reminders in This Queue
                    </h3>
                    <p style="color: var(--adm-text-muted); font-size: 0.85rem; max-width: 420px; margin: 0 auto 1.5rem auto;">
                        All scheduled touchpoints for this view are up to date. Schedule a new reminder to stay proactive.
                    </p>
                    <button type="button" class="adm-btn adm-btn-gold" data-adm-modal-target="modalCreateReminder">
                        <i class="fa-solid fa-plus"></i> Schedule New Reminder
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($reminders as $r): ?>
                    <?php 
                        $isOverdue = (strtotime($r['remind_at']) < time() && $r['status'] === 'pending');
                        $isToday = (date('Y-m-d', strtotime($r['remind_at'])) === date('Y-m-d') && $r['status'] === 'pending');
                        $isCompleted = ($r['status'] === 'completed');

                        $cardClass = 'upcoming';
                        if ($isCompleted) $cardClass = 'completed';
                        elseif ($isOverdue) $cardClass = 'overdue';
                        elseif ($isToday) $cardClass = 'today';

                        $leadStage = $r['lead_status'] ?: 'new';
                    ?>
                    <div class="crm-reminder-card <?= $cardClass ?>" 
                         style="background: #FFFFFF; border-radius: 10px; border: 1px solid var(--adm-border); padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; gap: 1.25rem; flex-wrap: wrap;">
                        
                        <!-- Left Details -->
                        <div style="flex: 1; min-width: 300px;">
                            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.35rem; flex-wrap: wrap;">
                                <!-- Priority Badge -->
                                <span class="crm-priority-badge crm-prio-<?= $r['priority'] ?>" style="text-transform: uppercase; font-size: 0.68rem; font-weight: 800; padding: 0.15rem 0.5rem; border-radius: 4px;">
                                    <?php if ($r['priority'] === 'urgent'): ?>🔥 Urgent<?php elseif ($r['priority'] === 'high'): ?>⚡ High Priority<?php else: ?><?= ucfirst($r['priority']) ?><?php endif; ?>
                                </span>

                                <!-- Timing Badge -->
                                <?php if ($isCompleted): ?>
                                    <span style="font-size: 0.72rem; color: #059669; font-weight: 700; background: #ECFDF5; padding: 0.15rem 0.5rem; border-radius: 4px;">
                                        <i class="fa-solid fa-circle-check"></i> Completed on <?= date('d M, h:i A', strtotime($r['completed_at'] ?: $r['created_at'])) ?>
                                    </span>
                                <?php elseif ($isOverdue): ?>
                                    <span style="font-size: 0.72rem; color: #DC2626; font-weight: 700; background: #FEF2F2; padding: 0.15rem 0.5rem; border-radius: 4px;">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Overdue since <?= date('d M, h:i A', strtotime($r['remind_at'])) ?>
                                    </span>
                                <?php elseif ($isToday): ?>
                                    <span style="font-size: 0.72rem; color: #D97706; font-weight: 700; background: #FFFBEB; padding: 0.15rem 0.5rem; border-radius: 4px;">
                                        <i class="fa-solid fa-clock"></i> Due Today at <?= date('h:i A', strtotime($r['remind_at'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="font-size: 0.72rem; color: #2563EB; font-weight: 600; background: #EFF6FF; padding: 0.15rem 0.5rem; border-radius: 4px;">
                                        <i class="fa-solid fa-calendar"></i> Scheduled for <?= date('d M Y, h:i A', strtotime($r['remind_at'])) ?>
                                    </span>
                                <?php endif; ?>

                                <!-- Stage Badge -->
                                <span class="adm-status-badge adm-badge-<?= $leadStage ?>" style="font-size: 0.65rem; padding: 0.1rem 0.4rem;">
                                    <?= strtoupper($stages[$leadStage]['label'] ?? $leadStage) ?>
                                </span>
                            </div>

                            <!-- Reminder Title -->
                            <h4 style="font-size: 1.05rem; font-weight: 700; color: var(--adm-navy); margin: 0 0 0.4rem 0;">
                                <?= e($r['title']) ?>
                            </h4>

                            <!-- Client Coordinates -->
                            <div style="display: flex; gap: 1rem; align-items: center; font-size: 0.8rem; color: #475569; flex-wrap: wrap;">
                                <div>
                                    <i class="fa-solid fa-user" style="color: var(--adm-gold);"></i> 
                                    <strong><a href="lead-detail.php?id=<?= $r['lead_id'] ?>" style="color: inherit; text-decoration: none;"><?= e($r['client_name']) ?></a></strong>
                                </div>
                                <div>
                                    <a href="tel:<?= e($r['client_phone']) ?>" style="color: #2563EB; font-weight: 600; text-decoration: none;">
                                        <i class="fa-solid fa-phone" style="font-size: 0.72rem;"></i> <?= e($r['client_phone']) ?>
                                    </a>
                                </div>
                                <?php if (!empty($r['client_phone'])): ?>
                                    <?php 
                                        $cleanP = preg_replace('/[^0-9]/', '', $r['client_phone']);
                                        if (strlen($cleanP) === 10) $cleanP = '91' . $cleanP;
                                    ?>
                                    <div>
                                        <a href="https://wa.me/<?= $cleanP ?>" target="_blank" style="color: #059669; font-weight: 600; text-decoration: none;">
                                            <i class="fa-brands fa-whatsapp"></i> WhatsApp
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($r['property_title'])): ?>
                                    <div style="color: #64748B;">
                                        <i class="fa-solid fa-building" style="font-size: 0.72rem;"></i> <?= e($r['property_title']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Notes context if present -->
                            <?php if (!empty($r['notes'])): ?>
                                <div style="background: #F8FAFC; border-left: 2px solid var(--adm-gold); padding: 0.45rem 0.75rem; border-radius: 4px; margin-top: 0.5rem; font-size: 0.78rem; color: #334155;">
                                    <i class="fa-solid fa-quote-left" style="color: var(--adm-text-muted); font-size: 0.7rem;"></i> <?= e($r['notes']) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Advisor Attribution -->
                            <?php if ($admin_role !== 'agent' && !empty($r['agent_name'])): ?>
                                <div style="margin-top: 0.4rem; font-size: 0.74rem; color: var(--adm-text-muted);">
                                    <i class="fa-solid fa-user-tie"></i> Advisor: <strong><?= e($r['agent_name']) ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Right Action Controls -->
                        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <?php if (!$isCompleted): ?>
                                <!-- 1-Click Complete Button -->
                                <form method="POST" action="reminders.php" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                    <input type="hidden" name="action" value="complete_reminder">
                                    <input type="hidden" name="reminder_id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="adm-btn adm-btn-sm adm-btn-success" title="Mark Done">
                                        <i class="fa-solid fa-check"></i> Done
                                    </button>
                                </form>

                                <!-- Reschedule Button -->
                                <button type="button" class="adm-btn adm-btn-sm adm-btn-outline btn-open-reschedule"
                                        data-id="<?= $r['id'] ?>"
                                        data-title="<?= e($r['title']) ?>"
                                        data-client="<?= e($r['client_name']) ?>"
                                        data-current="<?= date('Y-m-d\TH:i', strtotime($r['remind_at'])) ?>"
                                        data-notes="<?= e($r['notes']) ?>">
                                    <i class="fa-solid fa-calendar-plus"></i> Reschedule
                                </button>
                            <?php endif; ?>

                            <!-- View Dossier -->
                            <a href="lead-detail.php?id=<?= $r['lead_id'] ?>" class="adm-btn adm-btn-sm adm-btn-navy">
                                Dossier &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- ==========================================
     MODAL: SCHEDULE FOLLOW-UP REMINDER
     ========================================== -->
<div class="adm-modal-backdrop" id="modalCreateReminder">
    <div class="adm-modal" style="max-width: 550px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title">
                <i class="fa-solid fa-bell" style="color: var(--adm-gold);"></i> Schedule Follow-Up Task
            </h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="reminders.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="create_reminder">
            
            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Target Client / Lead *</label>
                    <select name="lead_id" class="adm-select" required>
                        <option value="">-- Select Client Dossier --</option>
                        <?php foreach ($active_leads as $al): ?>
                            <option value="<?= $al['id'] ?>">
                                <?= e($al['full_name']) ?> (<?= e($al['phone']) ?> &bull; <?= strtoupper($al['lead_status']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Follow-Up Action Title *</label>
                    <input type="text" name="title" class="adm-input" required placeholder="e.g. Call regarding sea-facing unit floor plan & token schedule">
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Remind Date & Time *</label>
                        <input type="datetime-local" name="remind_at" class="adm-input" required value="<?= date('Y-m-d\TH:i', strtotime('+1 day 11:00')) ?>">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Priority</label>
                        <select name="priority" class="adm-select">
                            <option value="medium" selected>Normal Medium</option>
                            <option value="high">⚡ High Priority</option>
                            <option value="urgent">🔥 Urgent</option>
                            <option value="low">Low Priority</option>
                        </select>
                    </div>
                </div>

                <?php if ($admin_role !== 'agent'): ?>
                    <div class="adm-form-group">
                        <label class="adm-label">Assign To Advisor</label>
                        <select name="agent_id" class="adm-select">
                            <?php foreach ($assignable_agents as $ag): ?>
                                <option value="<?= $ag['id'] ?>" <?= ($ag['id'] == $logged_admin['id']) ? 'selected' : '' ?>>
                                    <?= e($ag['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="agent_id" value="<?= $logged_admin['id'] ?>">
                <?php endif; ?>

                <div class="adm-form-group">
                    <label class="adm-label">Context Notes</label>
                    <textarea name="notes" class="adm-textarea" rows="2" placeholder="Specific points to discuss or documentation required..."></textarea>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-gold">
                    <i class="fa-solid fa-check"></i> Add Reminder
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     MODAL: RESCHEDULE REMINDER
     ========================================== -->
<div class="adm-modal-backdrop" id="modalReschedule">
    <div class="adm-modal" style="max-width: 500px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title">
                <i class="fa-solid fa-calendar-plus" style="color: var(--adm-gold);"></i> Reschedule Follow-Up
            </h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="reminders.php">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="reschedule_reminder">
            <input type="hidden" name="reminder_id" id="modal_r_id">

            <div class="adm-modal-body">
                <div style="background: #F8FAFC; border: 1px solid var(--adm-border); border-radius: 8px; padding: 0.85rem; margin-bottom: 1.2rem;">
                    <div style="font-weight: 700; color: var(--adm-navy);" id="modal_r_title"></div>
                    <div style="font-size: 0.78rem; color: var(--adm-text-muted);" id="modal_r_client"></div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">New Date & Time *</label>
                    <input type="datetime-local" name="remind_at" id="modal_r_date" class="adm-input" required>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Update Reason / Notes</label>
                    <textarea name="notes" id="modal_r_notes" class="adm-textarea" rows="2" placeholder="Why is this follow-up being moved?"></textarea>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-navy">
                    <i class="fa-solid fa-arrows-rotate"></i> Save Rescheduled Time
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.btn-open-reschedule').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('modal_r_id').value = this.dataset.id;
        document.getElementById('modal_r_title').textContent = this.dataset.title;
        document.getElementById('modal_r_client').textContent = "Client: " + this.dataset.client;
        document.getElementById('modal_r_date').value = this.dataset.current;
        document.getElementById('modal_r_notes').value = this.dataset.notes || '';
        document.getElementById('modalReschedule').classList.add('show');
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

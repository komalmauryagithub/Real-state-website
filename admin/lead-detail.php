<?php
/**
 * EstateVista India - Complete Lead Dossier & Interaction Timeline (Module CRM)
 * Detailed 360-degree client view, communication timeline, reminder scheduling, and stage transitions.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';
require_once __DIR__ . '/includes/crm_functions.php';

require_admin_login();

$db = Database::getConnection();
$current_admin = get_logged_in_admin();
$admin_role = get_admin_role();

$lead_id = (int)($_GET['id'] ?? 0);
if ($lead_id <= 0) {
    header('Location: ' . BASE_URL . 'admin/leads.php');
    exit;
}

$lead = get_crm_lead_by_id($lead_id);
if (!$lead) {
    die("Lead dossier not found.");
}

// RBAC: If role is agent, verify lead is assigned to this agent (or allow Super Admin/Admin)
if ($admin_role === 'agent' && (int)$lead['assigned_agent_id'] !== (int)$current_admin['id']) {
    // Show view-only or restricted notice
}

$success_msg = '';
$error_msg = '';

// Handle POST Mutations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_admin_csrf()) {
        $error_msg = "Security token expired. Please reload and try again.";
    } else {
        $action = sanitize($_POST['action'] ?? '');

        // 1. Add Follow-Up Note / Interaction Log
        if ($action === 'add_note') {
            $note_type = sanitize($_POST['note_type'] ?? 'general');
            $note_text = trim($_POST['note'] ?? '');
            $update_stage = sanitize($_POST['update_stage'] ?? '');
            $sched_date = sanitize($_POST['schedule_follow_up'] ?? '');

            if (empty($note_text)) {
                $error_msg = "Note content cannot be empty.";
            } else {
                if (add_crm_lead_note($lead_id, $current_admin['id'], $current_admin['name'], $note_type, $note_text)) {
                    // Update stage if provided
                    if (!empty($update_stage) && $update_stage !== $lead['lead_status']) {
                        $upStmt = $db->prepare("UPDATE inquiries SET lead_status = :st WHERE id = :id");
                        $upStmt->execute([':st' => $update_stage, ':id' => $lead_id]);

                        add_crm_lead_note($lead_id, $current_admin['id'], $current_admin['name'], 'status_change', 
                            "Pipeline stage transitioned from " . strtoupper($lead['lead_status']) . " to " . strtoupper($update_stage));
                    }

                    // Schedule reminder if date provided
                    if (!empty($sched_date)) {
                        create_crm_reminder($lead_id, $lead['assigned_agent_id'] ?: $current_admin['id'], 
                            "Follow-up: " . substr($note_text, 0, 50), $sched_date, 'medium');
                    }

                    $success_msg = "Interaction log saved to timeline.";
                    $lead = get_crm_lead_by_id($lead_id); // Refresh
                } else {
                    $error_msg = "Failed to log interaction.";
                }
            }
        }

        // 2. Quick Update Stage, Deal Value, and Lost Reason
        elseif ($action === 'update_pipeline_stage') {
            $new_stage = sanitize($_POST['lead_status'] ?? '');
            $deal_val_cr = !empty($_POST['deal_value_cr']) ? (float)$_POST['deal_value_cr'] : 0.0;
            $lost_reason = sanitize($_POST['lost_reason'] ?? '');
            $assigned_agent = !empty($_POST['assigned_agent_id']) ? (int)$_POST['assigned_agent_id'] : null;

            $valid_stages = ['new', 'contacted', 'interested', 'site_visit_scheduled', 'negotiation', 'converted', 'lost'];
            if (in_array($new_stage, $valid_stages)) {
                try {
                    $deal_bytes = $deal_val_cr * 10000000;
                    $converted_at = ($new_stage === 'converted') ? date('Y-m-d H:i:s') : null;

                    $stmt = $db->prepare("
                        UPDATE inquiries SET 
                            lead_status = :st, 
                            deal_value = :deal,
                            lost_reason = :lreason,
                            assigned_agent_id = :aid,
                            converted_at = COALESCE(converted_at, :cat)
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':st' => $new_stage,
                        ':deal' => $deal_bytes,
                        ':lreason' => ($new_stage === 'lost') ? $lost_reason : null,
                        ':aid' => $assigned_agent,
                        ':cat' => $converted_at,
                        ':id' => $lead_id
                    ]);

                    add_crm_lead_note($lead_id, $current_admin['id'], $current_admin['name'], 'status_change', 
                        "Lead updated: Stage set to " . strtoupper(str_replace('_', ' ', $new_stage)) . 
                        ($deal_val_cr > 0 ? " with estimated deal value of ₹{$deal_val_cr} Cr" : ""));

                    $success_msg = "Lead dossier updated successfully.";
                    $lead = get_crm_lead_by_id($lead_id); // Refresh
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }

        // 3. Update Site Visit Appointment
        elseif ($action === 'update_site_visit') {
            $v_date = sanitize($_POST['visit_date'] ?? '');
            $v_time = sanitize($_POST['visit_time'] ?? '');
            $v_status = sanitize($_POST['visit_status'] ?? 'scheduled');
            $v_notes = sanitize($_POST['visit_notes'] ?? '');

            try {
                $stmt = $db->prepare("
                    UPDATE inquiries SET 
                        visit_date = :vdate,
                        visit_time = :vtime,
                        status = :vstatus,
                        admin_notes = :vnotes,
                        lead_status = 'site_visit_scheduled'
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':vdate' => !empty($v_date) ? $v_date : null,
                    ':vtime' => $v_time,
                    ':vstatus' => $v_status,
                    ':vnotes' => $v_notes,
                    ':id' => $lead_id
                ]);

                add_crm_lead_note($lead_id, $current_admin['id'], $current_admin['name'], 'site_visit', 
                    "Site Visit confirmed for {$v_date} at {$v_time}. Logistics: {$v_notes}");

                $success_msg = "Site visit schedule updated.";
                $lead = get_crm_lead_by_id($lead_id); // Refresh
            } catch (PDOException $e) {
                $error_msg = "Database error: " . $e->getMessage();
            }
        }

        // 4. Create Scheduled Reminder
        elseif ($action === 'create_reminder') {
            $title = sanitize($_POST['title'] ?? '');
            $remind_at = sanitize($_POST['remind_at'] ?? '');
            $priority = sanitize($_POST['priority'] ?? 'medium');
            $notes = sanitize($_POST['notes'] ?? '');

            if (!empty($title) && !empty($remind_at)) {
                if (create_crm_reminder($lead_id, $lead['assigned_agent_id'] ?: $current_admin['id'], $title, $remind_at, $priority, $notes)) {
                    $success_msg = "Reminder added to schedule.";
                    $lead = get_crm_lead_by_id($lead_id);
                } else {
                    $error_msg = "Failed to schedule reminder.";
                }
            }
        }

        // 5. Complete Reminder
        elseif ($action === 'complete_reminder') {
            $rem_id = (int)($_POST['reminder_id'] ?? 0);
            if ($rem_id > 0) {
                complete_crm_reminder($rem_id);
                $success_msg = "Reminder marked as completed.";
            }
        }
    }
}

// Fetch Timeline Notes
$timeline_notes = get_crm_lead_notes($lead_id);

// Fetch Lead's Reminders
$reminders = get_crm_reminders(['lead_id' => $lead_id]);

// Filter assignable agents for reassignment
$assignable_agents = get_all_assignable_agents();

$deal_val_cr = round(((float)$lead['deal_value']) / 10000000, 2);

$stages = [
    'new' => 'New Discovery',
    'contacted' => 'Contacted',
    'interested' => 'Interested',
    'site_visit_scheduled' => 'Site Visit Scheduled',
    'negotiation' => 'Negotiation',
    'converted' => 'Converted',
    'lost' => 'Lost'
];

$page_title = "Lead Dossier #" . $lead['id'] . " - " . $lead['full_name'];
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Breadcrumb / Back Link -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <a href="leads.php" class="adm-btn adm-btn-sm adm-btn-outline">
                <i class="fa-solid fa-arrow-left"></i> Back to Leads CRM
            </a>
            <div style="font-size: 0.82rem; color: var(--adm-text-muted);">
                Lead Dossier ID: <strong style="color: var(--adm-navy);">#<?= $lead['id'] ?></strong> &bull;
                Received: <?= format_admin_date($lead['created_at']) ?>
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
             TOP EXECUTIVE SUMMARY BANNER
             ========================================== -->
        <div class="adm-card" style="border-top: 4px solid var(--adm-gold); margin-bottom: 1.5rem;">
            <div class="adm-card-body" style="padding: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem;">
                    <!-- Left: Client Name & Contact Info -->
                    <div style="display: flex; align-items: center; gap: 1.25rem;">
                        <div style="width: 58px; height: 58px; border-radius: 50%; background: #0B1528; color: var(--adm-gold); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; border: 2px solid var(--adm-gold);">
                            <?= strtoupper(substr($lead['full_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.25rem;">
                                <h1 style="font-size: 1.45rem; font-weight: 800; color: var(--adm-navy); margin: 0;">
                                    <?= e($lead['full_name']) ?>
                                </h1>
                                <span class="adm-status-badge adm-badge-<?= $lead['lead_status'] ?>">
                                    <?= strtoupper(str_replace('_', ' ', $lead['lead_status'])) ?>
                                </span>
                                <span class="adm-status-badge adm-badge-new" style="background: #F1F5F9; color: #475569;">
                                    <?= e($lead['lead_source'] ?: 'Website Enquiry') ?>
                                </span>
                            </div>
                            <div style="display: flex; gap: 1.25rem; font-size: 0.85rem; color: var(--adm-text-muted); flex-wrap: wrap;">
                                <span><i class="fa-solid fa-phone" style="color: var(--adm-gold);"></i> <?= e($lead['phone']) ?></span>
                                <span><i class="fa-solid fa-envelope" style="color: var(--adm-gold);"></i> <?= e($lead['email']) ?></span>
                                <span><i class="fa-solid fa-location-dot" style="color: var(--adm-gold);"></i> <?= e($lead['preferred_city'] ?: $lead['city']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Quick Communication Channels -->
                    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                        <a href="tel:<?= e($lead['phone']) ?>" class="crm-comm-btn crm-comm-call">
                            <i class="fa-solid fa-phone"></i> Call Now
                        </a>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $lead['phone']) ?>?text=<?= urlencode('Hello ' . $lead['full_name'] . ', this is ' . $current_admin['name'] . ' from EstateVista India.') ?>" 
                           target="_blank" class="crm-comm-btn crm-comm-whatsapp">
                            <i class="fa-brands fa-whatsapp"></i> WhatsApp
                        </a>
                        <a href="mailto:<?= e($lead['email']) ?>" class="crm-comm-btn crm-comm-email">
                            <i class="fa-solid fa-envelope"></i> Send Email
                        </a>
                        <button type="button" class="adm-btn adm-btn-sm adm-btn-gold" data-adm-modal-target="modalPipelineStage">
                            <i class="fa-solid fa-sliders"></i> Change Stage
                        </button>
                    </div>
                </div>

                <!-- Metrics Strip -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; border-top: 1px solid #F1F5F9; padding-top: 1rem; margin-top: 1.25rem;">
                    <div>
                        <span style="font-size: 0.72rem; color: var(--adm-text-muted); text-transform: uppercase; font-weight: 700; display: block;">Target Budget</span>
                        <strong style="color: var(--adm-navy); font-size: 1rem;"><?= e($lead['budget_range'] ?: 'Flexible') ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 0.72rem; color: var(--adm-text-muted); text-transform: uppercase; font-weight: 700; display: block;">Estimated Deal Value</span>
                        <strong style="color: #059669; font-size: 1rem;">
                            <?= $deal_val_cr > 0 ? "₹{$deal_val_cr} Cr" : "Under Evaluation" ?>
                        </strong>
                    </div>
                    <div>
                        <span style="font-size: 0.72rem; color: var(--adm-text-muted); text-transform: uppercase; font-weight: 700; display: block;">Assigned Wealth Advisor</span>
                        <strong style="color: var(--adm-navy); font-size: 0.95rem;">
                            <i class="fa-solid fa-user-tie" style="color: var(--adm-gold); font-size: 0.85rem;"></i>
                            <?= e($lead['agent_name'] ?: 'Unassigned') ?>
                        </strong>
                    </div>
                    <div>
                        <span style="font-size: 0.72rem; color: var(--adm-text-muted); text-transform: uppercase; font-weight: 700; display: block;">Next Follow-Up Date</span>
                        <?php if (!empty($lead['follow_up_date'])): ?>
                            <?php 
                                $isOverdue = (strtotime($lead['follow_up_date']) < time());
                                $isToday = (date('Y-m-d', strtotime($lead['follow_up_date'])) === date('Y-m-d'));
                            ?>
                            <strong style="color: <?= $isOverdue ? '#DC2626' : ($isToday ? '#D97706' : '#2563EB') ?>; font-size: 0.92rem;">
                                <i class="fa-regular fa-clock"></i> <?= date('d M, h:i A', strtotime($lead['follow_up_date'])) ?>
                                <?= $isOverdue ? '<span style="font-size: 0.7rem; color: #DC2626;">(Overdue)</span>' : '' ?>
                            </strong>
                        <?php else: ?>
                            <span style="color: #94A3B8; font-size: 0.85rem;">No follow-up set</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================
             MAIN DOSSIER LAYOUT (3 COLUMNS)
             ========================================== -->
        <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 1.5rem; align-items: flex-start;">
            <!-- Column 1: Client Details & Interested Property -->
            <div>
                <!-- Interested Property Card -->
                <div class="adm-card">
                    <div class="adm-card-header">
                        <div class="adm-card-title">
                            <i class="fa-solid fa-building" style="color: var(--adm-gold);"></i>
                            Interested Property
                        </div>
                    </div>
                    <div class="adm-card-body" style="padding: 1.25rem;">
                        <?php if (!empty($lead['property_title'])): ?>
                            <div style="border-radius: 8px; overflow: hidden; margin-bottom: 0.75rem; height: 130px; background: #0B1528;">
                                <img src="<?= e(safe_image_url($lead['property_image'], 'property')) ?>" alt="<?= e($lead['property_title']) ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                            </div>
                            <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--adm-navy); margin-bottom: 0.25rem;">
                                <a href="<?= BASE_URL ?>property-detail.php?slug=<?= urlencode($lead['property_slug']) ?>" target="_blank" style="text-decoration: none; color: inherit;">
                                    <?= e($lead['property_title']) ?>
                                </a>
                            </h4>
                            <div style="font-size: 0.8rem; color: var(--adm-text-muted); margin-bottom: 0.5rem;">
                                <i class="fa-solid fa-location-dot"></i> <?= e($lead['property_locality']) ?>, <?= e($lead['property_city']) ?>
                            </div>
                            <div style="font-size: 0.85rem; font-weight: 800; color: #059669; margin-bottom: 0.75rem;">
                                <?= e($lead['property_price']) ?>
                            </div>
                            <div style="font-size: 0.78rem; color: var(--adm-text-muted); border-top: 1px solid #F1F5F9; padding-top: 0.5rem;">
                                <span><?= e($lead['property_bhk']) ?> BHK</span> &bull; 
                                <span><?= number_format($lead['carpet_area_sqft']) ?> sq.ft.</span>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 1.5rem 0.5rem; color: var(--adm-text-muted); font-size: 0.82rem;">
                                <i class="fa-solid fa-house-chimney" style="font-size: 2rem; color: #CBD5E1; margin-bottom: 0.5rem; display: block;"></i>
                                General luxury mandate — no specific property pinned.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Client Message Card -->
                <div class="adm-card">
                    <div class="adm-card-header">
                        <div class="adm-card-title">
                            <i class="fa-solid fa-comment-dots" style="color: var(--adm-gold);"></i>
                            Inquiry Requirements
                        </div>
                    </div>
                    <div class="adm-card-body" style="padding: 1.25rem; font-size: 0.85rem; color: #334155; line-height: 1.6;">
                        <p style="font-style: italic; background: #F8FAFC; padding: 0.85rem; border-radius: 6px; border: 1px dashed #CBD5E1;">
                            "<?= e($lead['message'] ?: 'Client submitted general request for confidential portfolio viewing.') ?>"
                        </p>
                        <?php if (!empty($lead['lost_reason'])): ?>
                            <div style="margin-top: 1rem; padding: 0.75rem; background: #FEF2F2; border-left: 3px solid #EF4444; border-radius: 4px; font-size: 0.8rem; color: #991B1B;">
                                <strong>Reason for Archive / Loss:</strong><br>
                                <?= e($lead['lost_reason']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($lead['converted_at'])): ?>
                            <div style="margin-top: 1rem; padding: 0.75rem; background: #ECFDF5; border-left: 3px solid #10B981; border-radius: 4px; font-size: 0.8rem; color: #065F46;">
                                <strong>Deal Converted On:</strong><br>
                                <?= format_admin_date($lead['converted_at']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Column 2: Center - Interaction Timeline & Log Follow-up Note Form -->
            <div>
                <!-- Log Interaction Note Box -->
                <div class="adm-card" style="margin-bottom: 1.5rem;">
                    <div class="adm-card-header">
                        <div class="adm-card-title">
                            <i class="fa-solid fa-pen-nib" style="color: var(--adm-gold);"></i>
                            Log Follow-Up Interaction & Note
                        </div>
                    </div>
                    <div class="adm-card-body" style="padding: 1.25rem;">
                        <form method="POST" action="lead-detail.php?id=<?= $lead['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                            <input type="hidden" name="action" value="add_note">

                            <div class="adm-grid-2" style="margin-bottom: 0.75rem;">
                                <div class="adm-form-group" style="margin-bottom: 0;">
                                    <label class="adm-label">Interaction Channel *</label>
                                    <select name="note_type" class="adm-select" required>
                                        <option value="call">📞 Discovery Phone Call</option>
                                        <option value="meeting">🤝 In-Person Executive Meeting</option>
                                        <option value="site_visit">🚗 Property Inspection / Tour</option>
                                        <option value="whatsapp">💬 WhatsApp / Messaging Chat</option>
                                        <option value="email">✉️ Formal Email Dispatch</option>
                                        <option value="general">📝 Internal Advisor Dossier Note</option>
                                    </select>
                                </div>
                                <div class="adm-form-group" style="margin-bottom: 0;">
                                    <label class="adm-label">Simultaneously Advance Stage</label>
                                    <select name="update_stage" class="adm-select">
                                        <option value="">-- Keep Current (<?= strtoupper(str_replace('_', ' ', $lead['lead_status'])) ?>) --</option>
                                        <?php foreach ($stages as $sk => $sl): ?>
                                            <option value="<?= $sk ?>"><?= $sl ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="adm-form-group">
                                <label class="adm-label">Interaction Notes & Agreed Next Steps *</label>
                                <textarea name="note" class="adm-textarea" rows="3" required placeholder="Record key conversation highlights, objections addressed, customized pricing proposals, and client timeline..."></textarea>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <label style="font-size: 0.8rem; font-weight: 600; color: var(--adm-navy); white-space: nowrap;">
                                        <i class="fa-regular fa-bell" style="color: var(--adm-gold);"></i> Next Reminder:
                                    </label>
                                    <input type="datetime-local" name="schedule_follow_up" class="adm-input" style="padding: 0.4rem 0.65rem; font-size: 0.8rem;">
                                </div>
                                <button type="submit" class="adm-btn adm-btn-gold">
                                    <i class="fa-solid fa-paper-plane"></i> Save to Timeline
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Complete Interaction Timeline Stream -->
                <div class="adm-card">
                    <div class="adm-card-header">
                        <div class="adm-card-title">
                            <i class="fa-solid fa-timeline" style="color: var(--adm-gold);"></i>
                            Enquiry & Interaction History (<?= count($timeline_notes) ?>)
                        </div>
                    </div>
                    <div class="adm-card-body" style="padding: 1.5rem;">
                        <?php if (empty($timeline_notes)): ?>
                            <div style="text-align: center; padding: 2rem 1rem; color: var(--adm-text-muted); font-size: 0.85rem;">
                                No communication timeline entries logged yet.
                            </div>
                        <?php else: ?>
                            <div class="crm-timeline">
                                <?php foreach ($timeline_notes as $n): ?>
                                    <div class="crm-timeline-item">
                                        <div class="crm-timeline-icon <?= $n['note_type'] ?>">
                                            <?php if ($n['note_type'] === 'call'): ?>
                                                <i class="fa-solid fa-phone"></i>
                                            <?php elseif ($n['note_type'] === 'email'): ?>
                                                <i class="fa-solid fa-envelope"></i>
                                            <?php elseif ($n['note_type'] === 'meeting'): ?>
                                                <i class="fa-solid fa-handshake"></i>
                                            <?php elseif ($n['note_type'] === 'site_visit'): ?>
                                                <i class="fa-solid fa-car-side"></i>
                                            <?php elseif ($n['note_type'] === 'whatsapp'): ?>
                                                <i class="fa-brands fa-whatsapp"></i>
                                            <?php elseif ($n['note_type'] === 'status_change'): ?>
                                                <i class="fa-solid fa-arrows-spin"></i>
                                            <?php else: ?>
                                                <i class="fa-solid fa-note-sticky"></i>
                                            <?php endif; ?>
                                        </div>

                                        <div class="crm-timeline-content">
                                            <div class="crm-timeline-header">
                                                <div class="crm-timeline-author">
                                                    <?= e($n['agent_name']) ?>
                                                    <span style="font-size: 0.72rem; font-weight: 600; color: var(--adm-text-muted); text-transform: uppercase; margin-left: 0.35rem;">
                                                        (<?= e($n['note_type']) ?>)
                                                    </span>
                                                </div>
                                                <div class="crm-timeline-time">
                                                    <?= format_admin_date($n['created_at']) ?>
                                                </div>
                                            </div>
                                            <div class="crm-timeline-body">
                                                <?= nl2br(e($n['note'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Column 3: Right - Reminders & Site Visit Management -->
            <div>
                <!-- Reminders Action Box -->
                <div class="adm-card" style="margin-bottom: 1.5rem;">
                    <div class="adm-card-header">
                        <div class="adm-card-title">
                            <i class="fa-solid fa-bell" style="color: var(--adm-gold);"></i>
                            Follow-Up Tasks
                        </div>
                        <button type="button" class="adm-btn adm-btn-sm adm-btn-outline" data-adm-modal-target="modalAddReminderSingle">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <div class="adm-card-body" style="padding: 1.25rem;">
                        <?php if (empty($reminders)): ?>
                            <div style="text-align: center; padding: 1.5rem 0.5rem; color: var(--adm-text-muted); font-size: 0.8rem;">
                                No scheduled reminders for this client.
                            </div>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <?php foreach ($reminders as $r): ?>
                                    <?php 
                                        $rTime = strtotime($r['remind_at']);
                                        $isOver = ($rTime < time() && $r['status'] === 'pending');
                                        $isTday = (date('Y-m-d', $rTime) === date('Y-m-d') && $r['status'] === 'pending');
                                        $cls = ($r['status'] === 'completed') ? 'completed' : ($isOver ? 'overdue' : ($isTday ? 'today' : 'upcoming'));
                                    ?>
                                    <div class="crm-reminder-card <?= $cls ?>" style="margin-bottom: 0; padding: 0.85rem;">
                                        <div style="flex: 1;">
                                            <div style="display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0.2rem;">
                                                <span class="adm-status-badge adm-priority-<?= $r['priority'] ?>" style="font-size: 0.65rem;">
                                                    <?= strtoupper($r['priority']) ?>
                                                </span>
                                            </div>
                                            <div style="font-weight: 700; color: var(--adm-navy); font-size: 0.84rem;">
                                                <?= e($r['title']) ?>
                                            </div>
                                            <div style="font-size: 0.72rem; color: var(--adm-text-muted); margin-top: 0.2rem;">
                                                <i class="fa-regular fa-clock"></i> <?= date('d M, h:i A', $rTime) ?>
                                            </div>
                                        </div>
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <form method="POST" action="lead-detail.php?id=<?= $lead['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                                                <input type="hidden" name="action" value="complete_reminder">
                                                <input type="hidden" name="reminder_id" value="<?= $r['id'] ?>">
                                                <button type="submit" class="adm-btn adm-btn-sm adm-btn-outline" style="color: #059669; border-color: #A7F3D0;" title="Mark Completed">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #10B981; font-size: 0.75rem;"><i class="fa-solid fa-circle-check"></i> Done</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Site Visit Schedule Box -->
                <div class="adm-card">
                    <div class="adm-card-header">
                        <div class="adm-card-title">
                            <i class="fa-solid fa-car" style="color: var(--adm-gold);"></i>
                            Site Visit Details
                        </div>
                    </div>
                    <div class="adm-card-body" style="padding: 1.25rem;">
                        <form method="POST" action="lead-detail.php?id=<?= $lead['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
                            <input type="hidden" name="action" value="update_site_visit">

                            <div class="adm-form-group">
                                <label class="adm-label">Scheduled Date</label>
                                <input type="date" name="visit_date" class="adm-input" value="<?= e($lead['visit_date']) ?>">
                            </div>

                            <div class="adm-form-group">
                                <label class="adm-label">Preferred Time Slot</label>
                                <input type="text" name="visit_time" class="adm-input" value="<?= e($lead['visit_time']) ?>" placeholder="e.g. 11:30 AM Slot">
                            </div>

                            <div class="adm-form-group">
                                <label class="adm-label">Visit Status</label>
                                <select name="visit_status" class="adm-select">
                                    <option value="new" <?= ($lead['status'] === 'new') ? 'selected' : '' ?>>Pending Confirmation</option>
                                    <option value="scheduled" <?= ($lead['status'] === 'scheduled') ? 'selected' : '' ?>>Confirmed Tour</option>
                                    <option value="closed" <?= ($lead['status'] === 'closed') ? 'selected' : '' ?>>Completed / Closed</option>
                                </select>
                            </div>

                            <div class="adm-form-group">
                                <label class="adm-label">Chauffeur & Access Notes</label>
                                <textarea name="visit_notes" class="adm-textarea" rows="2" placeholder="Gate security pass, guest details..."><?= e($lead['admin_notes']) ?></textarea>
                            </div>

                            <button type="submit" class="adm-btn adm-btn-sm adm-btn-navy" style="width: 100%; justify-content: center;">
                                Update Visit Schedule
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- ==========================================
     MODAL: ADVANCE PIPELINE STAGE
     ========================================== -->
<div class="adm-modal-backdrop" id="modalPipelineStage">
    <div class="adm-modal" style="max-width: 550px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-sliders" style="color: var(--adm-gold);"></i> Update Pipeline Stage & Deal Terms</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="lead-detail.php?id=<?= $lead['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="update_pipeline_stage">
            <div class="adm-modal-body">
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Pipeline Stage *</label>
                        <select name="lead_status" class="adm-select" required id="select_pipeline_stage">
                            <?php foreach ($stages as $sk => $sl): ?>
                                <option value="<?= $sk ?>" <?= ($lead['lead_status'] === $sk) ? 'selected' : '' ?>><?= $sl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Estimated Deal Value (in ₹ Cr)</label>
                        <input type="number" step="0.5" name="deal_value_cr" class="adm-input" value="<?= $deal_val_cr > 0 ? $deal_val_cr : '' ?>" placeholder="e.g. 52.0">
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Assigned Wealth Advisor</label>
                    <select name="assigned_agent_id" class="adm-select">
                        <option value="">-- Unassigned --</option>
                        <?php foreach ($assignable_agents as $ag): ?>
                            <option value="<?= $ag['id'] ?>" <?= ((int)$lead['assigned_agent_id'] === (int)$ag['id']) ? 'selected' : '' ?>>
                                <?= e($ag['name']) ?> (<?= ucfirst($ag['role']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="adm-form-group" id="lost_reason_group" style="<?= ($lead['lead_status'] === 'lost') ? '' : 'display: none;' ?>">
                    <label class="adm-label">Lost Reason (Required if Lost)</label>
                    <input type="text" name="lost_reason" class="adm-input" value="<?= e($lead['lost_reason']) ?>" placeholder="e.g. Postponed acquisition, opted for secondary market">
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-gold">Save Stage Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     MODAL: ADD SCHEDULED REMINDER
     ========================================== -->
<div class="adm-modal-backdrop" id="modalAddReminderSingle">
    <div class="adm-modal" style="max-width: 500px;">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-bell" style="color: var(--adm-gold);"></i> Add Follow-up Task</h3>
            <button type="button" class="adm-modal-close">&times;</button>
        </div>
        <form method="POST" action="lead-detail.php?id=<?= $lead['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= generate_admin_csrf() ?>">
            <input type="hidden" name="action" value="create_reminder">
            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Task Title *</label>
                    <input type="text" name="title" class="adm-input" required placeholder="e.g. Call architect regarding customized layout">
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
                            <option value="urgent">Urgent</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Preparation Notes</label>
                    <textarea name="notes" class="adm-textarea" rows="2" placeholder="Details..."></textarea>
                </div>
            </div>
            <div class="adm-modal-footer">
                <button type="button" class="adm-btn adm-btn-outline" data-adm-modal-close>Cancel</button>
                <button type="submit" class="adm-btn adm-btn-gold">Save Task</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('select_pipeline_stage')?.addEventListener('change', function () {
    const lostGroup = document.getElementById('lost_reason_group');
    if (lostGroup) {
        lostGroup.style.display = (this.value === 'lost') ? 'block' : 'none';
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

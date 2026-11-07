<?php
/**
 * EstateVista India - Reports & Data Export Center (Module 18)
 * Executive intelligence exports for audits, investor presentations, and CRM analysis.
 */

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_functions.php';

require_admin_login();

$db = Database::getConnection();

// Handle On-Demand CSV Exports
$export_type = $_GET['export'] ?? '';

if (!empty($export_type)) {
    // 1. Properties Master Export
    if ($export_type === 'properties') {
        $rows = $db->query("
            SELECT 
                p.id,
                p.title,
                c.name as city,
                p.locality,
                cat.name as category,
                p.price,
                p.price_display,
                p.purpose,
                p.bhk,
                p.bathrooms,
                p.carpet_area_sqft,
                p.furnishing,
                p.status as construction_status,
                p.rera_number,
                p.approval_status,
                CASE WHEN p.is_featured = 1 THEN 'Yes' ELSE 'No' END as is_featured,
                CASE WHEN p.is_exclusive = 1 THEN 'Yes' ELSE 'No' END as is_exclusive,
                CASE WHEN p.is_verified = 1 THEN 'Yes' ELSE 'No' END as is_verified,
                COALESCE(u.full_name, p.contact_person_name, 'Direct Listing') as submitter_name,
                p.created_at
            FROM properties p
            LEFT JOIN cities c ON p.city_id = c.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            LEFT JOIN users u ON p.user_id = u.id
            ORDER BY p.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $headers = [
            'Property ID', 'Title', 'City', 'Locality', 'Category', 'Raw Price (INR)', 'Display Price',
            'Purpose', 'BHK', 'Baths', 'Carpet Area (sqft)', 'Furnishing', 'Construction Status',
            'RERA ID', 'Approval Status', 'Featured', 'Exclusive', 'Verified', 'Submitter', 'Date Listed'
        ];

        export_to_csv('EstateVista_Properties_Portfolio_' . date('Y-m-d') . '.csv', $headers, $rows);
    }

    // 2. Leads & Inquiries Export
    elseif ($export_type === 'leads') {
        $rows = $db->query("
            SELECT 
                i.id,
                i.inquiry_type,
                i.full_name as client_name,
                i.email,
                i.phone,
                i.city,
                i.budget_range,
                COALESCE(p.title, 'General Consultation') as property,
                COALESCE(p.locality, '—') as locality,
                COALESCE(i.lead_status, i.status) as pipeline_stage,
                COALESCE(adm.name, 'Unassigned') as assigned_advisor,
                i.message,
                i.admin_notes,
                i.created_at
            FROM inquiries i
            LEFT JOIN properties p ON i.property_id = p.id
            LEFT JOIN admins adm ON i.assigned_agent_id = adm.id
            ORDER BY i.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $headers = [
            'Lead ID', 'Type', 'Client Name', 'Email', 'Phone', 'City', 'Budget',
            'Property Interest', 'Locality', 'Stage', 'Assigned Advisor', 'Client Message', 'Internal Notes', 'Date'
        ];

        export_to_csv('EstateVista_Leads_CRM_' . date('Y-m-d') . '.csv', $headers, $rows);
    }

    // 3. Site Visits Export
    elseif ($export_type === 'site_visits') {
        $rows = $db->query("
            SELECT 
                i.id,
                i.visit_date,
                i.visit_time,
                i.full_name as client_name,
                i.email,
                i.phone,
                COALESCE(p.title, 'General Viewing') as property,
                COALESCE(p.locality, '—') as locality,
                COALESCE(i.lead_status, i.status) as visit_status,
                COALESCE(adm.name, 'Unassigned') as escort_advisor,
                i.admin_notes,
                i.created_at
            FROM inquiries i
            LEFT JOIN properties p ON i.property_id = p.id
            LEFT JOIN admins adm ON i.assigned_agent_id = adm.id
            WHERE i.inquiry_type = 'site_visit'
            ORDER BY i.visit_date DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $headers = [
            'Visit ID', 'Scheduled Date', 'Time Slot', 'Client Name', 'Email', 'Phone',
            'Property', 'Locality', 'Status', 'Escort Advisor', 'Escort Notes', 'Booked On'
        ];

        export_to_csv('EstateVista_Site_Visits_' . date('Y-m-d') . '.csv', $headers, $rows);
    }

    // 4. Users / Clients Export
    elseif ($export_type === 'users') {
        require_admin_role(['super_admin', 'admin']);
        $rows = $db->query("
            SELECT 
                u.id,
                u.full_name,
                u.email,
                u.phone,
                u.city,
                u.preferred_budget,
                u.status,
                (SELECT COUNT(*) FROM properties p WHERE p.user_id = u.id) as properties_listed,
                (SELECT COUNT(*) FROM inquiries i WHERE i.user_id = u.id) as inquiries_made,
                u.created_at
            FROM users u
            ORDER BY u.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $headers = [
            'User ID', 'Full Name', 'Email', 'Phone', 'City', 'Budget Preference',
            'Account Status', 'Properties Listed', 'Inquiries Made', 'Joined Date'
        ];

        export_to_csv('EstateVista_Clients_Directory_' . date('Y-m-d') . '.csv', $headers, $rows);
    }

    // 5. Subscribers Export
    elseif ($export_type === 'subscribers') {
        require_admin_role(['super_admin', 'admin']);
        $rows = $db->query("
            SELECT id, email, status, subscribed_at 
            FROM newsletter_subscribers 
            ORDER BY subscribed_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Subscriber ID', 'Email Address', 'Status', 'Date Subscribed'];
        export_to_csv('EstateVista_Subscribers_' . date('Y-m-d') . '.csv', $headers, $rows);
    }
}

// Fetch Reporting Metrics
$stats = get_admin_dashboard_stats();

$page_title = "Data Reports & CSV Exports";
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>

<?php require_once __DIR__ . '/includes/admin_navbar.php'; ?>
        <!-- Header -->
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-navy); margin-bottom: 0.35rem;">
                Executive Reports & CSV Intelligence Exports
            </h1>
            <p style="color: var(--adm-text-muted); font-size: 0.88rem;">
                Generate compliance-ready data sheets, CRM registries, portfolio audit files, and subscriber rosters.
            </p>
        </div>

        <!-- Export Cards Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <!-- Report 1: Properties Portfolio -->
            <div class="adm-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                <div class="adm-card-body" style="padding: 1.5rem;">
                    <div style="width: 48px; height: 48px; border-radius: 10px; background: #EFF6FF; color: #2563EB; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem;">
                        <i class="fa-solid fa-building"></i>
                    </div>
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--adm-navy); margin-bottom: 0.35rem;">
                        Property Portfolio Master
                    </h3>
                    <p style="font-size: 0.82rem; color: var(--adm-text-muted); line-height: 1.5; margin-bottom: 1rem;">
                        Complete dossier of all approved, pending, and exclusive properties with RERA IDs, sq.ft. carpet area, pricing in INR, and submitter coordinates.
                    </p>
                    <div style="font-size: 0.78rem; color: var(--adm-navy); font-weight: 700; margin-bottom: 1.25rem;">
                        <i class="fa-solid fa-database" style="color: var(--adm-gold);"></i> <?= $stats['total_properties'] ?> Total Registered Properties
                    </div>
                    <a href="reports.php?export=properties" class="adm-btn adm-btn-navy" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-file-csv"></i> Download Properties CSV
                    </a>
                </div>
            </div>

            <!-- Report 2: Leads & Enquiries -->
            <div class="adm-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                <div class="adm-card-body" style="padding: 1.5rem;">
                    <div style="width: 48px; height: 48px; border-radius: 10px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem;">
                        <i class="fa-solid fa-filter-circle-dollar"></i>
                    </div>
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--adm-navy); margin-bottom: 0.35rem;">
                        Inquiries & CRM Pipeline
                    </h3>
                    <p style="font-size: 0.82rem; color: var(--adm-text-muted); line-height: 1.5; margin-bottom: 1rem;">
                        Export all incoming property inquiries, callback requests, negotiation stages, budgets, assigned advisors, and consultation logs.
                    </p>
                    <div style="font-size: 0.78rem; color: var(--adm-navy); font-weight: 700; margin-bottom: 1.25rem;">
                        <i class="fa-solid fa-comments" style="color: var(--adm-gold);"></i> <?= $stats['total_inquiries'] ?> Total Leads in CRM
                    </div>
                    <a href="reports.php?export=leads" class="adm-btn adm-btn-gold" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-file-csv"></i> Download Leads CSV
                    </a>
                </div>
            </div>

            <!-- Report 3: Site Visits -->
            <div class="adm-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                <div class="adm-card-body" style="padding: 1.5rem;">
                    <div style="width: 48px; height: 48px; border-radius: 10px; background: #F5F3FF; color: #7C3AED; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem;">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--adm-navy); margin-bottom: 0.35rem;">
                        VIP Site Visits Schedule
                    </h3>
                    <p style="font-size: 0.82rem; color: var(--adm-text-muted); line-height: 1.5; margin-bottom: 1rem;">
                        Scheduled physical viewing tours, requested time slots, VIP client contact coordinates, escort advisor assignments, and entry notes.
                    </p>
                    <div style="font-size: 0.78rem; color: var(--adm-navy); font-weight: 700; margin-bottom: 1.25rem;">
                        <i class="fa-solid fa-car-side" style="color: var(--adm-gold);"></i> <?= $stats['site_visits'] ?> Site Visits Recorded
                    </div>
                    <a href="reports.php?export=site_visits" class="adm-btn adm-btn-outline" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-file-csv"></i> Download Site Visits CSV
                    </a>
                </div>
            </div>

            <!-- Report 4: Clients Directory -->
            <?php if (admin_has_role(['super_admin', 'admin'])): ?>
                <div class="adm-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                    <div class="adm-card-body" style="padding: 1.5rem;">
                        <div style="width: 48px; height: 48px; border-radius: 10px; background: #ECFDF5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem;">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--adm-navy); margin-bottom: 0.35rem;">
                            Registered Clients & Investors
                        </h3>
                        <p style="font-size: 0.82rem; color: var(--adm-text-muted); line-height: 1.5; margin-bottom: 1rem;">
                            High-Net-Worth registered account holders, investment preferences, active/suspended states, and associated transaction activity.
                        </p>
                        <div style="font-size: 0.78rem; color: var(--adm-navy); font-weight: 700; margin-bottom: 1.25rem;">
                            <i class="fa-solid fa-id-card" style="color: var(--adm-gold);"></i> <?= $stats['total_users'] ?> Registered Accounts
                        </div>
                        <a href="reports.php?export=users" class="adm-btn adm-btn-navy" style="width: 100%; justify-content: center;">
                            <i class="fa-solid fa-file-csv"></i> Download Clients CSV
                        </a>
                    </div>
                </div>

                <!-- Report 5: Newsletter Subscribers -->
                <div class="adm-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                    <div class="adm-card-body" style="padding: 1.5rem;">
                        <div style="width: 48px; height: 48px; border-radius: 10px; background: #F1F5F9; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem;">
                            <i class="fa-solid fa-paper-plane"></i>
                        </div>
                        <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--adm-navy); margin-bottom: 0.35rem;">
                            Newsletter Circulation List
                        </h3>
                        <p style="font-size: 0.82rem; color: var(--adm-text-muted); line-height: 1.5; margin-bottom: 1rem;">
                            Active and unsubscribed investor newsletter recipients for external email marketing integrations and market intelligence dispatches.
                        </p>
                        <div style="font-size: 0.78rem; color: var(--adm-navy); font-weight: 700; margin-bottom: 1.25rem;">
                            <i class="fa-solid fa-envelope" style="color: var(--adm-gold);"></i> <?= $stats['subscribers'] ?> Active Readers
                        </div>
                        <a href="reports.php?export=subscribers" class="adm-btn adm-btn-outline" style="width: 100%; justify-content: center;">
                            <i class="fa-solid fa-file-csv"></i> Download Subscribers CSV
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

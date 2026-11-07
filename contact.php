<?php
/**
 * EstateVista India - Private Client Advisory & Regional Contact Desks
 * Dedicated contact and advisory appointment portal.
 * Integrates directly with MySQL database: enquiries, leads, and notifications tables.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Private Client Advisory & Regional Offices | EstateVista India";
$page_desc = "Connect with EstateVista's senior property acquisition partners, regional desks in Mumbai, Delhi NCR, Bengaluru, Hyderabad, Pune, and Goa.";

$feedback = [
    'type' => '',
    'message' => ''
];

// Handle direct POST form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'contact_submit') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $feedback = [
            'type' => 'error',
            'message' => 'Security token expired or invalid. Please refresh the page and submit again.'
        ];
    } else {
        $fullName = sanitize_input($_POST['full_name'] ?? '');
        $email    = sanitize_input($_POST['email'] ?? '');
        $phone    = sanitize_input($_POST['phone'] ?? '');
        $city     = sanitize_input($_POST['city'] ?? '');
        $purpose  = sanitize_input($_POST['purpose'] ?? 'buy');
        $budget   = sanitize_input($_POST['budget_range'] ?? '');
        $prefTime = sanitize_input($_POST['preferred_time'] ?? '');
        $message  = sanitize_input($_POST['message'] ?? '');

        if (empty($fullName) || mb_strlen($fullName) < 2) {
            $feedback = ['type' => 'error', 'message' => 'Please enter your complete full name.'];
        } elseif (empty($phone) || strlen(preg_replace('/[^0-9]/', '', $phone)) < 10) {
            $feedback = ['type' => 'error', 'message' => 'Please provide a valid 10-digit mobile number.'];
        } elseif (empty($email) || !validate_email($email)) {
            $feedback = ['type' => 'error', 'message' => 'Please provide a valid email address.'];
        } else {
            // Compose inquiry record
            $inquiry_result = record_property_inquiry([
                'user_id'        => is_logged_in() ? get_logged_in_user_id() : null,
                'property_id'    => null,
                'inquiry_type'   => 'general',
                'full_name'      => $fullName,
                'email'          => $email,
                'phone'          => $phone,
                'city'           => $city,
                'budget_range'   => $budget,
                'preferred_time' => $prefTime,
                'message'        => "[Purpose: " . ucfirst($purpose) . "] " . $message
            ]);

            if (!empty($inquiry_result['success'])) {
                $feedback = [
                    'type' => 'success',
                    'message' => 'Thank you, ' . htmlspecialchars($fullName) . '. Your advisory request has been registered with our senior acquisitions desk. A partner will contact you discreetly within 2 business hours.'
                ];
            } else {
                $feedback = [
                    'type' => 'error',
                    'message' => $inquiry_result['message'] ?? 'Unable to register your request. Please call our concierge desk directly at ' . SITE_PHONE . '.'
                ];
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
/* Scoped luxury styling for Contact Page */
.contact-hero {
    background: linear-gradient(135deg, var(--navy-950, #060D1A) 0%, var(--navy-900, #0B1528) 50%, var(--navy-800, #13223E) 100%);
    color: var(--white);
    padding: 4.5rem 0 3.5rem;
    position: relative;
    border-bottom: 1px solid rgba(197, 168, 128, 0.2);
}
.contact-hero::after {
    content: '';
    position: absolute;
    top: 0; right: 0; bottom: 0; left: 0;
    background: radial-gradient(circle at 85% 20%, rgba(197, 168, 128, 0.08) 0%, transparent 60%);
    pointer-events: none;
}
.contact-hero-content {
    position: relative;
    z-index: 2;
    max-width: 820px;
}
.contact-hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.78rem;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--gold-400);
    font-weight: 700;
    margin-bottom: 1rem;
    padding: 0.35rem 0.85rem;
    background: rgba(197, 168, 128, 0.1);
    border: 1px solid rgba(197, 168, 128, 0.25);
    border-radius: var(--radius-full);
}
.contact-hero-title {
    font-family: var(--font-serif);
    font-size: clamp(2rem, 4vw, 3.25rem);
    font-weight: 700;
    line-height: 1.15;
    margin-bottom: 1rem;
    color: var(--white);
}
.contact-hero-subtitle {
    font-size: 1.05rem;
    line-height: 1.6;
    color: var(--slate-300);
    margin-bottom: 2rem;
}
.contact-quick-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}
.quick-stat-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.quick-stat-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(197, 168, 128, 0.15);
    color: var(--gold-400);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
}
.quick-stat-label {
    font-size: 0.72rem;
    text-transform: uppercase;
    color: var(--slate-400);
    letter-spacing: 0.08em;
}
.quick-stat-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--white);
}

/* Two Column Layout */
.contact-main-grid {
    display: grid;
    grid-template-columns: 1.25fr 0.95fr;
    gap: 2.5rem;
    align-items: start;
}
@media (max-width: 991px) {
    .contact-main-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
}

.contact-card {
    background: var(--white);
    border-radius: var(--radius-md);
    border: 1px solid var(--beige-200);
    box-shadow: 0 10px 30px rgba(11, 21, 40, 0.05);
    padding: 2.5rem;
}
@media (max-width: 576px) {
    .contact-card {
        padding: 1.5rem;
    }
}
.contact-card-header {
    margin-bottom: 1.75rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid var(--beige-200);
}
.contact-card-title {
    font-family: var(--font-serif);
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--navy-900);
    margin-bottom: 0.35rem;
}
.contact-card-desc {
    font-size: 0.9rem;
    color: var(--slate-600);
    margin: 0;
}

/* Form Styles */
.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
}
@media (max-width: 640px) {
    .form-row-2 {
        grid-template-columns: 1fr;
    }
}
.contact-form .form-group {
    margin-bottom: 1.25rem;
}
.contact-form label {
    display: block;
    font-size: 0.82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--navy-900);
    margin-bottom: 0.45rem;
}
.contact-form input,
.contact-form select,
.contact-form textarea {
    width: 100%;
    padding: 0.85rem 1rem;
    font-size: 0.92rem;
    font-family: inherit;
    border: 1px solid var(--beige-300);
    border-radius: var(--radius-sm);
    background: #FAF8F5;
    color: var(--navy-950);
    transition: all var(--transition-fast);
}
.contact-form input:focus,
.contact-form select:focus,
.contact-form textarea:focus {
    outline: none;
    border-color: var(--gold-500);
    background: var(--white);
    box-shadow: 0 0 0 3px rgba(197, 168, 128, 0.2);
}

/* Sidebar Channels Card */
.concierge-box {
    background: linear-gradient(145deg, var(--navy-900) 0%, var(--navy-950) 100%);
    border-radius: var(--radius-md);
    padding: 2.25rem;
    color: var(--white);
    border: 1px solid rgba(197, 168, 128, 0.3);
    margin-bottom: 2rem;
    box-shadow: 0 12px 36px rgba(11, 21, 40, 0.12);
}
.concierge-channel {
    display: flex;
    align-items: center;
    gap: 1.15rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(197, 168, 128, 0.15);
    border-radius: var(--radius-sm);
    margin-bottom: 0.85rem;
    text-decoration: none;
    color: var(--white);
    transition: all var(--transition-fast);
}
.concierge-channel:hover {
    background: rgba(197, 168, 128, 0.12);
    border-color: var(--gold-400);
    transform: translateY(-2px);
    color: var(--white);
}
.concierge-channel-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--gold-500);
    color: var(--navy-950);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}
.concierge-channel-info h4 {
    font-size: 0.95rem;
    font-weight: 700;
    margin: 0 0 0.15rem 0;
    color: var(--white);
}
.concierge-channel-info p {
    font-size: 0.8rem;
    color: var(--slate-300);
    margin: 0;
}

/* Regional Desks Grid */
.desks-section {
    background: var(--beige-100);
    padding: 5rem 0;
    border-top: 1px solid var(--beige-200);
}
.regional-desks-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.75rem;
    margin-top: 2.5rem;
}
@media (max-width: 991px) {
    .regional-desks-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 640px) {
    .regional-desks-grid {
        grid-template-columns: 1fr;
    }
}
.desk-card {
    background: var(--white);
    border-radius: var(--radius-md);
    border: 1px solid var(--beige-200);
    overflow: hidden;
    transition: all var(--transition-normal);
    display: flex;
    flex-direction: column;
}
.desk-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 32px rgba(11, 21, 40, 0.08);
    border-color: var(--gold-400);
}
.desk-card-media {
    position: relative;
    height: 150px;
    overflow: hidden;
}
.desk-card-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}
.desk-card:hover .desk-card-media img {
    transform: scale(1.05);
}
.desk-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: rgba(11, 21, 40, 0.85);
    color: var(--gold-400);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 0.25rem 0.65rem;
    border-radius: var(--radius-xs);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(197, 168, 128, 0.3);
}
.desk-card-body {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.desk-city {
    font-family: var(--font-serif);
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--navy-900);
    margin-bottom: 0.25rem;
}
.desk-role {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--gold-600);
    font-weight: 700;
    margin-bottom: 0.85rem;
}
.desk-address {
    font-size: 0.85rem;
    color: var(--slate-600);
    line-height: 1.5;
    margin-bottom: 1.25rem;
    flex: 1;
}
.desk-contacts {
    padding-top: 1rem;
    border-top: 1px solid var(--beige-200);
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}
.desk-contact-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--navy-900);
    font-weight: 600;
    text-decoration: none;
    transition: color var(--transition-fast);
}
.desk-contact-link:hover {
    color: var(--gold-600);
}

/* FAQ Accordion */
.faq-section {
    padding: 5rem 0;
    background: var(--white);
}
.faq-accordion {
    max-width: 800px;
    margin: 2.5rem auto 0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.faq-item {
    border: 1px solid var(--beige-200);
    border-radius: var(--radius-sm);
    overflow: hidden;
    background: #FAF8F5;
    transition: border-color var(--transition-fast);
}
.faq-item.active {
    border-color: var(--gold-500);
    background: var(--white);
}
.faq-question {
    padding: 1.25rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    font-family: var(--font-serif);
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--navy-900);
    user-select: none;
}
.faq-question i {
    font-size: 0.85rem;
    color: var(--gold-600);
    transition: transform var(--transition-normal);
}
.faq-item.active .faq-question i {
    transform: rotate(180deg);
}
.faq-answer {
    padding: 0 1.5rem 1.25rem;
    font-size: 0.92rem;
    color: var(--slate-600);
    line-height: 1.6;
    display: none;
}
.faq-item.active .faq-answer {
    display: block;
}
</style>

<main>
    <!-- =========================================================================
         1. Hero Header
         ========================================================================= -->
    <section class="contact-hero">
        <div class="container">
            <div class="contact-hero-content">
                <div class="contact-hero-eyebrow">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Institutional Client Advisory</span>
                </div>
                <h1 class="contact-hero-title">Connect with Our Private Acquisition Advisory</h1>
                <p class="contact-hero-subtitle">
                    Whether you are acquiring a signature sea-facing penthouse, seeking Grade-A commercial yield assets, or listing an ultra-prime estate, our senior partners are at your service under strict confidentiality.
                </p>

                <div class="contact-quick-stats">
                    <div class="quick-stat-item">
                        <div class="quick-stat-icon"><i class="fa-solid fa-phone"></i></div>
                        <div>
                            <div class="quick-stat-label">Direct Concierge</div>
                            <div class="quick-stat-value"><?= e(SITE_PHONE) ?></div>
                        </div>
                    </div>
                    <div class="quick-stat-item">
                        <div class="quick-stat-icon"><i class="fa-brands fa-whatsapp"></i></div>
                        <div>
                            <div class="quick-stat-label">WhatsApp Priority</div>
                            <div class="quick-stat-value">+91 98200 12345</div>
                        </div>
                    </div>
                    <div class="quick-stat-item">
                        <div class="quick-stat-icon"><i class="fa-solid fa-building-columns"></i></div>
                        <div>
                            <div class="quick-stat-label">Regional Desks</div>
                            <div class="quick-stat-value">6 Prime Indian Metros</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================================================
         2. Main Advisory Form & Concierge Channels
         ========================================================================= -->
    <section class="section section-beige" style="padding: 4rem 0;">
        <div class="container">
            <div class="contact-main-grid">

                <!-- Column 1: Consultation Request Form -->
                <div class="contact-card">
                    <div class="contact-card-header">
                        <div style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--gold-600); font-weight: 700; margin-bottom: 0.35rem;">
                            Direct Partner Engagement
                        </div>
                        <h2 class="contact-card-title">Request Confidential Consultation</h2>
                        <p class="contact-card-desc">
                            Complete the brief advisory profile below. A senior sector director will reach out within 2 hours.
                        </p>
                    </div>

                    <?php if (!empty($feedback['message'])): ?>
                        <div style="margin-bottom: 1.5rem; padding: 1rem 1.25rem; border-radius: var(--radius-sm); font-size: 0.9rem; display: flex; align-items: flex-start; gap: 0.75rem; <?= $feedback['type'] === 'success' ? 'background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46;' : 'background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B;' ?>">
                            <i class="fa-solid <?= $feedback['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>" style="font-size: 1.15rem; margin-top: 0.1rem;"></i>
                            <div><?= e($feedback['message']) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="contact.php" method="POST" class="contact-form" id="mainContactForm">
                        <input type="hidden" name="action" value="contact_submit">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="contact_name">Full Legal Name *</label>
                                <input type="text" id="contact_name" name="full_name" placeholder="e.g. Vikram Singhania" required value="<?= e($_POST['full_name'] ?? (is_logged_in() ? get_logged_in_user()['full_name'] : '')) ?>">
                            </div>
                            <div class="form-group">
                                <label for="contact_phone">Mobile / WhatsApp Number *</label>
                                <input type="tel" id="contact_phone" name="phone" placeholder="+91 98200 00000" required value="<?= e($_POST['phone'] ?? (is_logged_in() ? get_logged_in_user()['phone'] : '')) ?>">
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="contact_email">Corporate or Personal Email *</label>
                                <input type="email" id="contact_email" name="email" placeholder="name@domain.com" required value="<?= e($_POST['email'] ?? (is_logged_in() ? get_logged_in_user()['email'] : '')) ?>">
                            </div>
                            <div class="form-group">
                                <label for="contact_purpose">Advisory Interest *</label>
                                <select id="contact_purpose" name="purpose" required>
                                    <option value="buy" <?= (isset($_GET['purpose']) && $_GET['purpose'] === 'buy') ? 'selected' : '' ?>>Buy Luxury Residence / Penthouse</option>
                                    <option value="rent" <?= (isset($_GET['purpose']) && $_GET['purpose'] === 'rent') ? 'selected' : '' ?>>Rent High-End Villa / Residence</option>
                                    <option value="commercial" <?= (isset($_GET['purpose']) && $_GET['purpose'] === 'commercial') ? 'selected' : '' ?>>Grade-A Commercial Space / Office</option>
                                    <option value="sell">List My Property for Sale / Lease</option>
                                    <option value="general">Portfolio Investment & General Advisory</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="contact_city">Target Metro / Micro-Market</label>
                                <select id="contact_city" name="city">
                                    <option value="Mumbai">Mumbai (Worli, Bandra, Lower Parel, BKC)</option>
                                    <option value="Delhi NCR">Delhi NCR (DLF Phase 5 Gurugram, Shanti Niketan)</option>
                                    <option value="Bengaluru">Bengaluru (Lavelle Rd, Indiranagar, Whitefield)</option>
                                    <option value="Hyderabad">Hyderabad (Jubilee Hills, HITEC City, Kokapet)</option>
                                    <option value="Pune">Pune (Koregaon Park, Boat Club Road)</option>
                                    <option value="Goa">Goa (Assagao, Anjuna, Candolim)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="contact_budget">Target Investment Bracket</label>
                                <select id="contact_budget" name="budget_range">
                                    <option value="₹ 3 Cr - ₹ 7 Cr">₹ 3 Cr – ₹ 7 Cr</option>
                                    <option value="₹ 7 Cr - ₹ 15 Cr" selected>₹ 7 Cr – ₹ 15 Cr</option>
                                    <option value="₹ 15 Cr - ₹ 30 Cr">₹ 15 Cr – ₹ 30 Cr</option>
                                    <option value="₹ 30 Cr+">₹ 30 Cr+ (Ultra-Bespoke)</option>
                                    <option value="Rental ₹ 2L - ₹ 10L/mo">Rental: ₹ 2L – ₹ 10L / month</option>
                                    <option value="Commercial Yield">Commercial Yield Asset</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="contact_time">Preferred Communication Window</label>
                            <select id="contact_time" name="preferred_time">
                                <option value="Morning (10:00 AM - 1:00 PM)">Morning: 10:00 AM – 1:00 PM</option>
                                <option value="Afternoon (1:00 PM - 5:00 PM)" selected>Afternoon: 1:00 PM – 5:00 PM</option>
                                <option value="Evening (5:00 PM - 8:00 PM)">Evening: 5:00 PM – 8:00 PM</option>
                                <option value="Discreet WhatsApp First">Discreet WhatsApp Message First</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="contact_message">Specific Acquisition Requirements or Notes</label>
                            <textarea id="contact_message" name="message" rows="4" placeholder="Detail any architectural specifications, sea-views, private elevators, preferred towers, or possession timelines..."></textarea>
                        </div>

                        <div style="margin-top: 1.75rem;">
                            <button type="submit" class="btn btn-gold btn-lg" style="width: 100%; justify-content: center; font-size: 1rem;">
                                <i class="fa-solid fa-lock"></i>
                                <span>Submit Confidential Request</span>
                            </button>
                        </div>

                        <div style="margin-top: 1rem; text-align: center; font-size: 0.78rem; color: var(--slate-500); display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                            <i class="fa-solid fa-shield-halved" style="color: var(--gold-600);"></i>
                            <span>Non-disclosure guaranteed. We never broadcast client data to external third parties.</span>
                        </div>
                    </form>
                </div>

                <!-- Column 2: Direct Concierge & Advisory Highlights -->
                <div>
                    <!-- Instant Channels Box -->
                    <div class="concierge-box">
                        <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.15em; color: var(--gold-400); font-weight: 700; margin-bottom: 0.35rem;">
                            Immediate Priority Desk
                        </div>
                        <h3 style="font-family: var(--font-serif); font-size: 1.6rem; margin-bottom: 0.5rem; color: var(--white);">
                            Direct Concierge Channels
                        </h3>
                        <p style="font-size: 0.85rem; color: var(--slate-300); margin-bottom: 1.5rem; line-height: 1.5;">
                            For active acquisition mandates, immediate site inspections, or owner listings requiring urgent consultation:
                        </p>

                        <a href="tel:<?= str_replace(' ', '', SITE_PHONE) ?>" class="concierge-channel">
                            <div class="concierge-channel-icon"><i class="fa-solid fa-phone"></i></div>
                            <div class="concierge-channel-info">
                                <h4>Voice Concierge</h4>
                                <p><?= e(SITE_PHONE) ?> (Toll-Free Direct)</p>
                            </div>
                        </a>

                        <a href="https://wa.me/919820012345?text=Hello%20EstateVista%20Advisory%2C%20I%20would%20like%20to%20request%20a%20private%20consultation." target="_blank" class="concierge-channel">
                            <div class="concierge-channel-icon" style="background: #25D366; color: #fff;"><i class="fa-brands fa-whatsapp"></i></div>
                            <div class="concierge-channel-info">
                                <h4>WhatsApp Advisory</h4>
                                <p>+91 98200 12345 (Encrypted Desk)</p>
                            </div>
                        </a>

                        <a href="mailto:<?= SITE_EMAIL ?>" class="concierge-channel">
                            <div class="concierge-channel-icon"><i class="fa-solid fa-envelope"></i></div>
                            <div class="concierge-channel-info">
                                <h4>Confidential Email</h4>
                                <p><?= SITE_EMAIL ?></p>
                            </div>
                        </a>

                        <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.8rem; color: var(--slate-400); display: flex; align-items: center; justify-content: space-between;">
                            <span>Operating Hours:</span>
                            <span style="color: var(--white); font-weight: 600;">Mon – Sat: 9:30 AM – 7:30 PM IST</span>
                        </div>
                    </div>

                    <!-- EstateVista Advantage Card -->
                    <div class="contact-card" style="padding: 2rem;">
                        <h3 style="font-family: var(--font-serif); font-size: 1.35rem; color: var(--navy-900); margin-bottom: 1.25rem;">
                            The EstateVista Standard
                        </h3>

                        <div style="display: flex; flex-direction: column; gap: 1.15rem;">
                            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(197, 168, 128, 0.15); color: var(--gold-600); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.9rem;">
                                    <i class="fa-solid fa-certificate"></i>
                                </div>
                                <div>
                                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--navy-900); margin: 0 0 0.2rem 0;">100% RERA & Legal Title Diligence</h4>
                                    <p style="font-size: 0.82rem; color: var(--slate-600); margin: 0; line-height: 1.45;">Every featured residence undergoes strict ownership title verification, encumbrance check, and state RERA audit.</p>
                                </div>
                            </div>

                            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(197, 168, 128, 0.15); color: var(--gold-600); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.9rem;">
                                    <i class="fa-solid fa-eye-slash"></i>
                                </div>
                                <div>
                                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--navy-900); margin: 0 0 0.2rem 0;">Off-Market & Pre-Launch Inventory</h4>
                                    <p style="font-size: 0.82rem; color: var(--slate-600); margin: 0; line-height: 1.45;">Exclusive access to private developer allotments, ultra-luxury sky villas, and discreet distress buyouts not publicly marketed.</p>
                                </div>
                            </div>

                            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(197, 168, 128, 0.15); color: var(--gold-600); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.9rem;">
                                    <i class="fa-solid fa-user-tie"></i>
                                </div>
                                <div>
                                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--navy-900); margin: 0 0 0.2rem 0;">Single Senior Advisory Partner</h4>
                                    <p style="font-size: 0.82rem; color: var(--slate-600); margin: 0; line-height: 1.45;">No repetitive call centers. You work directly with a designated acquisition director from initial screening to key handover.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- =========================================================================
         3. Regional Advisory Desks (6 Prime Indian Metros)
         ========================================================================= -->
    <section class="desks-section">
        <div class="container">
            <div style="text-align: center; max-width: 700px; margin: 0 auto;">
                <div class="eyebrow" style="margin-bottom: 0.5rem;"><i class="fa-solid fa-compass"></i> National Footprint</div>
                <h2 class="section-title">Regional Advisory Desks & Offices</h2>
                <p class="section-subtitle" style="margin: 0 auto;">
                    Physical consultative suites staffed by licensed real estate advocates and luxury acquisition specialists.
                </p>
            </div>

            <div class="regional-desks-grid">
                <!-- Desk 1: Mumbai -->
                <div class="desk-card">
                    <div class="desk-card-media">
                        <img src="https://images.unsplash.com/photo-1570168007204-dfb528c6958f?auto=format&fit=crop&w=800&q=80" alt="Mumbai Headquarters" loading="lazy" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/city-placeholder.svg';">
                        <span class="desk-badge">Corporate HQ</span>
                    </div>
                    <div class="desk-card-body">
                        <h3 class="desk-city">Mumbai Headquarters</h3>
                        <div class="desk-role">Western India Private Client Desk</div>
                        <p class="desk-address">
                            Level 18, Tower 2, One World Center, 841 Senapati Bapat Marg, Lower Parel, Mumbai 400013
                        </p>
                        <div class="desk-contacts">
                            <a href="tel:+912269854000" class="desk-contact-link">
                                <i class="fa-solid fa-phone" style="color: var(--gold-600);"></i>
                                <span>+91 22 6985 4000</span>
                            </a>
                            <a href="mailto:mumbai@estatevistaindia.com" class="desk-contact-link">
                                <i class="fa-solid fa-envelope" style="color: var(--gold-600);"></i>
                                <span>mumbai@estatevistaindia.com</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Desk 2: Delhi NCR -->
                <div class="desk-card">
                    <div class="desk-card-media">
                        <img src="https://images.unsplash.com/photo-1587474260584-136574528ed5?auto=format&fit=crop&w=800&q=80" alt="Delhi NCR Desk" loading="lazy" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/city-placeholder.svg';">
                        <span class="desk-badge">Northern Desk</span>
                    </div>
                    <div class="desk-card-body">
                        <h3 class="desk-city">Delhi NCR Advisory</h3>
                        <div class="desk-role">Lutyens & DLF Phase 5 Division</div>
                        <p class="desk-address">
                            Suite 902, Two Horizon Center, Golf Course Road, DLF Phase 5, Sector 43, Gurugram 122002
                        </p>
                        <div class="desk-contacts">
                            <a href="tel:+911244589200" class="desk-contact-link">
                                <i class="fa-solid fa-phone" style="color: var(--gold-600);"></i>
                                <span>+91 124 458 9200</span>
                            </a>
                            <a href="mailto:delhi@estatevistaindia.com" class="desk-contact-link">
                                <i class="fa-solid fa-envelope" style="color: var(--gold-600);"></i>
                                <span>delhi@estatevistaindia.com</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Desk 3: Bengaluru -->
                <div class="desk-card">
                    <div class="desk-card-media">
                        <img src="https://images.unsplash.com/photo-1596176530529-78163a4f7af2?auto=format&fit=crop&w=800&q=80" alt="Bengaluru Desk" loading="lazy" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/city-placeholder.svg';">
                        <span class="desk-badge">Tech Capital Desk</span>
                    </div>
                    <div class="desk-card-body">
                        <h3 class="desk-city">Bengaluru Private Desk</h3>
                        <div class="desk-role">UB City & Founder Wealth Advisory</div>
                        <p class="desk-address">
                            Level 7, Concorde Block, UB City, 24 Vittal Mallya Road, Bengaluru 560001
                        </p>
                        <div class="desk-contacts">
                            <a href="tel:+918041207800" class="desk-contact-link">
                                <i class="fa-solid fa-phone" style="color: var(--gold-600);"></i>
                                <span>+91 80 4120 7800</span>
                            </a>
                            <a href="mailto:bangalore@estatevistaindia.com" class="desk-contact-link">
                                <i class="fa-solid fa-envelope" style="color: var(--gold-600);"></i>
                                <span>bangalore@estatevistaindia.com</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Desk 4: Hyderabad -->
                <div class="desk-card">
                    <div class="desk-card-media">
                        <img src="https://images.unsplash.com/photo-1605007493699-ce65834f8a00?auto=format&fit=crop&w=800&q=80" alt="Hyderabad Desk" loading="lazy" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/city-placeholder.svg';">
                        <span class="desk-badge">Telangana Hub</span>
                    </div>
                    <div class="desk-card-body">
                        <h3 class="desk-city">Hyderabad Advisory</h3>
                        <div class="desk-role">Jubilee Hills & Financial District</div>
                        <p class="desk-address">
                            Road No. 36, Opposite Metro Pillar 1650, Jubilee Hills, Hyderabad 500033
                        </p>
                        <div class="desk-contacts">
                            <a href="tel:+914048506700" class="desk-contact-link">
                                <i class="fa-solid fa-phone" style="color: var(--gold-600);"></i>
                                <span>+91 40 4850 6700</span>
                            </a>
                            <a href="mailto:hyderabad@estatevistaindia.com" class="desk-contact-link">
                                <i class="fa-solid fa-envelope" style="color: var(--gold-600);"></i>
                                <span>hyderabad@estatevistaindia.com</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Desk 5: Pune -->
                <div class="desk-card">
                    <div class="desk-card-media">
                        <img src="https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=800&q=80" alt="Pune Desk" loading="lazy" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/city-placeholder.svg';">
                        <span class="desk-badge">Commercial Hub</span>
                    </div>
                    <div class="desk-card-body">
                        <h3 class="desk-city">Pune Prime Living</h3>
                        <div class="desk-role">Boat Club Road & KP Advisory</div>
                        <p class="desk-address">
                            Plaza Tower, North Main Road, Koregaon Park, Pune 411001
                        </p>
                        <div class="desk-contacts">
                            <a href="tel:+912067203400" class="desk-contact-link">
                                <i class="fa-solid fa-phone" style="color: var(--gold-600);"></i>
                                <span>+91 20 6720 3400</span>
                            </a>
                            <a href="mailto:pune@estatevistaindia.com" class="desk-contact-link">
                                <i class="fa-solid fa-envelope" style="color: var(--gold-600);"></i>
                                <span>pune@estatevistaindia.com</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Desk 6: Goa -->
                <div class="desk-card">
                    <div class="desk-card-media">
                        <img src="https://images.unsplash.com/photo-1512343879784-a960bf40e7f2?auto=format&fit=crop&w=800&q=80" alt="Goa Villa Desk" loading="lazy" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/city-placeholder.svg';">
                        <span class="desk-badge">Coastal Estate Desk</span>
                    </div>
                    <div class="desk-card-body">
                        <h3 class="desk-city">Goa Private Villa Desk</h3>
                        <div class="desk-role">Assagao & North Goa Heritage</div>
                        <p class="desk-address">
                            Villa Estate, Badem Road, Assagao, Bardez, Goa 403507
                        </p>
                        <div class="desk-contacts">
                            <a href="tel:+918322951100" class="desk-contact-link">
                                <i class="fa-solid fa-phone" style="color: var(--gold-600);"></i>
                                <span>+91 832 295 1100</span>
                            </a>
                            <a href="mailto:goa@estatevistaindia.com" class="desk-contact-link">
                                <i class="fa-solid fa-envelope" style="color: var(--gold-600);"></i>
                                <span>goa@estatevistaindia.com</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================================================
         4. Frequently Asked Questions (FAQ)
         ========================================================================= -->
    <section class="faq-section">
        <div class="container">
            <div style="text-align: center; max-width: 650px; margin: 0 auto;">
                <div class="eyebrow" style="margin-bottom: 0.5rem;"><i class="fa-solid fa-circle-question"></i> Client Inquiries</div>
                <h2 class="section-title">Frequently Asked Questions</h2>
                <p class="section-subtitle" style="margin: 0 auto;">
                    Essential guidance regarding institutional real estate advisory, confidentiality, and transaction workflows.
                </p>
            </div>

            <div class="faq-accordion">
                <div class="faq-item active">
                    <div class="faq-question">
                        <span>How does EstateVista ensure discreet privacy during high-value acquisitions?</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        All acquisitions above ₹ 5 Crore are handled through designated senior partners under enforceable Non-Disclosure Agreements (NDAs). Buyer identities are protected during preliminary shortlisting, private property inspections are conducted with exclusive access, and title due diligence is managed through certified legal partners.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Can NRI and overseas clients complete property acquisitions remotely?</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Yes. EstateVista provides an end-to-end NRI Desk that facilitates legal Power of Attorney (PoA) drafting, FEMA compliance, NRE/NRO account verification, 3D architectural walkthroughs, and virtual notary verification with state registrar offices.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Does EstateVista charge advisory fees to buyers and lessees?</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        For primary developer residences and partner new launches, our advisory services are zero-fee to the buyer. For secondary resale acquisitions, heritage estates, and bespoke tenant commercial leasing, standard institutional advisory retainers and statutory success fees apply upon title closure.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>How do I list an ultra-luxury residence or commercial floor privately?</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        You can submit your property through our <a href="<?= BASE_URL ?>list-property.php" style="color: var(--gold-600); font-weight: 700;">List Your Property</a> portal or select "Private Off-Market Listing" in the advisory form above. Our valuation director will inspect the asset, coordinate architectural photography, and present it directly to vetted family offices and private clients.
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
// Simple Interactive FAQ Accordion
document.addEventListener('DOMContentLoaded', () => {
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        question.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            faqItems.forEach(i => i.classList.remove('active'));
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

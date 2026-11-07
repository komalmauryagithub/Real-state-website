<?php
/**
 * Footer Component & Reusable Modals
 * EstateVista India
 */

require_once __DIR__ . '/../config/config.php';
?>

<!-- 1. VIP Consultation Modal -->
<div class="modal-overlay" id="vipModalOverlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-content">
        <button type="button" class="modal-close-btn" id="modalCloseBtn" aria-label="Close dialog">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div style="margin-bottom: 1.5rem;">
            <div class="eyebrow"><i class="fa-solid fa-crown"></i> Private Advisory</div>
            <h3 id="modalTitle" style="font-size: 1.75rem; margin-bottom: 0.5rem;">Request Discreet Consultation</h3>
            <p style="font-size: 0.85rem; color: var(--slate-500); margin: 0;">
                Connect directly with our senior luxury acquisition partners under strict non-disclosure agreement.
            </p>
        </div>

        <form id="vipInquiryForm" action="javascript:void(0)" method="POST">
            <input type="hidden" name="property_interest" id="modalPropertyInterest" value="General Luxury Advisory">
            <input type="hidden" name="inquiry_type" value="enquiry">

            <div class="modal-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="inq_name">Full Name *</label>
                    <input type="text" id="inq_name" name="full_name" class="form-control" placeholder="e.g. Vikram Singhania" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="inq_phone">Phone / WhatsApp *</label>
                    <input type="tel" id="inq_phone" name="phone" class="form-control" placeholder="+91 98200 00000" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="inq_email">Corporate or Private Email *</label>
                <input type="email" id="inq_email" name="email" class="form-control" placeholder="name@domain.com" required>
            </div>

            <div class="modal-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="inq_city">City of Interest</label>
                    <select id="inq_city" name="city" class="form-control">
                        <option value="Mumbai">Mumbai (Worli, Bandra, South Mumbai)</option>
                        <option value="Delhi NCR">Delhi NCR (Golf Course Rd Gurugram)</option>
                        <option value="Bengaluru">Bengaluru (Indiranagar, Whitefield)</option>
                        <option value="Goa">Goa (Assagao, Anjuna, Candolim)</option>
                        <option value="Hyderabad">Hyderabad (Jubilee Hills)</option>
                        <option value="Pune">Pune (Koregaon Park)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="inq_budget">Target Investment Bracket</label>
                    <select id="inq_budget" name="budget_range" class="form-control">
                        <option value="₹ 3 Cr - ₹ 7 Cr">₹ 3 Cr – ₹ 7 Cr</option>
                        <option value="₹ 7 Cr - ₹ 15 Cr">₹ 7 Cr – ₹ 15 Cr</option>
                        <option value="₹ 15 Cr - ₹ 30 Cr">₹ 15 Cr – ₹ 30 Cr</option>
                        <option value="₹ 30 Cr+">₹ 30 Cr+ (Ultra-Bespoke)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="inq_notes">Specific Architectural Preferences</label>
                <textarea id="inq_notes" name="message" class="form-control" rows="3" placeholder="e.g. Sea-facing terrace, minimum 4 bedrooms, private elevator, ready for immediate possession..."></textarea>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-gold" id="inquirySubmitBtn" style="width: 100%;">
                    <i class="fa-solid fa-lock"></i>
                    <span>Submit Confidential Request</span>
                </button>
            </div>

            <div id="modalFormFeedback" style="display: none; margin-top: 1rem; padding: 0.85rem; border-radius: var(--radius-sm); font-size: 0.85rem; text-align: center;"></div>
        </form>
    </div>
</div>

<!-- 2. Client Login Modal -->
<div class="modal-overlay" id="loginModalOverlay" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
    <div class="modal-content" style="max-width: 480px;">
        <button type="button" class="modal-close-btn" id="loginModalCloseBtn" aria-label="Close dialog">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div style="text-align: center; margin-bottom: 1.75rem;">
            <div class="brand-emblem" style="margin: 0 auto 0.85rem auto;"><span>EV</span></div>
            <h3 id="loginModalTitle" style="font-size: 1.6rem; margin-bottom: 0.35rem;">EstateVista Client Portal</h3>
            <p style="font-size: 0.85rem; color: var(--slate-500); margin: 0;">Access your shortlisted residences, saved searches & private dossiers.</p>
        </div>

        <form id="clientLoginForm" action="javascript:void(0)">
            <div class="form-group">
                <label class="form-label" for="login_identity">Email Address or Mobile Number *</label>
                <input type="text" id="login_identity" class="form-control" placeholder="name@domain.com or +91 98200 00000" required>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem;">
                    <label class="form-label" for="login_pass" style="margin-bottom: 0;">Password / Access Key *</label>
                    <a href="javascript:void(0)" style="font-size: 0.75rem; color: var(--gold-600);">Forgot key?</a>
                </div>
                <input type="password" id="login_pass" class="form-control" placeholder="••••••••••••" required>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; font-size: 0.8rem; color: var(--slate-600);">
                <label style="display: flex; align-items: center; gap: 0.45rem; cursor: pointer;">
                    <input type="checkbox" checked style="accent-color: var(--gold-500);">
                    <span>Remember this device</span>
                </label>
                <span style="color: var(--slate-400);">256-bit SSL</span>
            </div>

            <button type="submit" class="btn btn-navy" id="loginSubmitBtn" style="width: 100%; margin-bottom: 1rem;">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                <span>Sign In to Private Portal</span>
            </button>

            <button type="button" class="btn btn-white" style="width: 100%; border: 1px solid var(--beige-300);" onclick="alert('Google Workspace Sign-In initiated. In production, this redirects to OAuth 2.0.');">
                <i class="fa-brands fa-google" style="color: #EA4335;"></i>
                <span>Continue with Google</span>
            </button>

            <div id="loginFeedback" style="display: none; margin-top: 1rem; padding: 0.75rem; border-radius: var(--radius-sm); font-size: 0.85rem; text-align: center;"></div>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.82rem; color: var(--slate-500);">
                New client or family office? 
                <a href="javascript:void(0)" class="open-inquiry-modal" style="color: var(--gold-600); font-weight: 600;">Request Private Access</a>
            </div>
        </form>
    </div>
</div>

<!-- 3. Property Owner Listing Modal -->
<div class="modal-overlay" id="ownerModalOverlay" role="dialog" aria-modal="true" aria-labelledby="ownerModalTitle">
    <div class="modal-content" style="max-width: 640px;">
        <button type="button" class="modal-close-btn" id="ownerModalCloseBtn" aria-label="Close dialog">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div style="margin-bottom: 1.5rem;">
            <div class="eyebrow"><i class="fa-solid fa-house-chimney"></i> Property Owners</div>
            <h3 id="ownerModalTitle" style="font-size: 1.75rem; margin-bottom: 0.5rem;">List Your Luxury Residence</h3>
            <p style="font-size: 0.85rem; color: var(--slate-500); margin: 0;">
                Reach qualified ultra-high-net-worth buyers and family offices with discreet, institutional marketing.
            </p>
        </div>

        <form id="ownerListingForm" action="javascript:void(0)">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="owner_name">Owner / Representative Name *</label>
                    <input type="text" id="owner_name" name="owner_name" class="form-control" placeholder="e.g. Sameer Munjal" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="owner_phone">Contact Phone *</label>
                    <input type="tel" id="owner_phone" name="owner_phone" class="form-control" placeholder="+91 98200 00000" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="owner_email">Email Address *</label>
                    <input type="email" id="owner_email" name="owner_email" class="form-control" placeholder="sameer@domain.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="owner_type">Property Category *</label>
                    <select id="owner_type" name="owner_type" class="form-control" required>
                        <option value="Sky Penthouse">Sky Penthouse</option>
                        <option value="Luxury Apartment">Luxury Apartment</option>
                        <option value="Independent Villa">Independent Villa / Bungalow</option>
                        <option value="Estate Plot">Estate Plot / Land</option>
                        <option value="Commercial Space">Grade-A Commercial Space</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="owner_city">Location / City *</label>
                    <select id="owner_city" name="owner_city" class="form-control" required>
                        <option value="Mumbai">Mumbai</option>
                        <option value="Delhi NCR">Delhi NCR (Gurugram / Delhi)</option>
                        <option value="Bengaluru">Bengaluru</option>
                        <option value="Goa">Goa</option>
                        <option value="Hyderabad">Hyderabad</option>
                        <option value="Pune">Pune</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="owner_price">Expected Valuation (₹) *</label>
                    <input type="text" id="owner_price" name="owner_price" class="form-control" placeholder="e.g. ₹ 18.5 Cr" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="owner_details">Property Details & Key Highlights</label>
                <textarea id="owner_details" name="owner_details" class="form-control" rows="3" placeholder="Tower name, locality, carpet area, bedrooms, view, furnishing status..."></textarea>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-gold" id="ownerSubmitBtn" style="width: 100%;">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Submit Residence for Listing Evaluation</span>
                </button>
            </div>

            <div id="ownerFeedback" style="display: none; margin-top: 1rem; padding: 0.85rem; border-radius: var(--radius-sm); font-size: 0.85rem; text-align: center;"></div>
        </form>
    </div>
</div>

<!-- =========================================================================
     16. Premium Multi-Column Footer
     ========================================================================= -->
<footer class="site-footer" id="contact-footer">
    <div class="container">
        <div class="footer-main-grid">
            <!-- Brand Column -->
            <div class="footer-col">
                <div class="brand-logo" style="margin-bottom: 1.25rem;">
                    <div class="brand-emblem" aria-hidden="true">
                        <svg viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg" class="emblem-svg">
                            <rect x="2" y="2" width="40" height="40" rx="8" fill="#0B1528" stroke="#C5A880" stroke-width="1.2"/>
                            <path d="M13 14H23C25.2 14 26.5 15.3 26.5 17.5C26.5 19.7 25.2 21 23 21H15M13 21H22M13 29H24" stroke="#F1E6D6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22 21L28.5 29M31.5 14L25 29" stroke="#C5A880" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="brand-text-block">
                        <span class="brand-name" style="font-size: 1.25rem;">EstateVista <span>India</span></span>
                        <span class="brand-subtitle">Curated Prime Living</span>
                    </div>
                </div>
                <p class="footer-brand-p">
                    India’s benchmark real estate portal for ultra-prime residences, sky penthouses, golf estates, and commercial suites. Curated for discerning buyers, CXOs, NRI investors, and family offices.
                </p>
                <div class="footer-social-links">
                    <a href="https://linkedin.com" target="_blank" class="social-icon-btn" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                    <a href="https://instagram.com" target="_blank" class="social-icon-btn" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="https://youtube.com" target="_blank" class="social-icon-btn" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                    <a href="https://twitter.com" target="_blank" class="social-icon-btn" aria-label="X / Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                </div>
            </div>

            <!-- Quick Navigation -->
            <div class="footer-col">
                <h4>Navigation</h4>
                <div class="footer-links">
                    <a href="<?= BASE_URL ?>properties.php?purpose=buy">Buy Luxury Residences</a>
                    <a href="<?= BASE_URL ?>properties.php?purpose=rent">Rent High-End Homes</a>
                    <a href="<?= BASE_URL ?>properties.php?purpose=commercial">Commercial Spaces</a>
                    <a href="<?= BASE_URL ?>index.php#luxury-projects">Residential Projects</a>
                    <a href="<?= BASE_URL ?>list-property.php">List Your Property</a>
                    <a href="<?= BASE_URL ?>contact.php">Contact Advisory & Offices</a>
                </div>
            </div>

            <!-- Explore Cities -->
            <div class="footer-col">
                <h4>Prime Metros</h4>
                <div class="footer-links">
                    <a href="#prime-cities">Mumbai (Worli, Bandra, Malabar Hill)</a>
                    <a href="#prime-cities">Delhi NCR (Golf Course Rd Gurugram)</a>
                    <a href="#prime-cities">Bengaluru (Indiranagar, Whitefield)</a>
                    <a href="#prime-cities">Hyderabad (Jubilee Hills, Kokapet)</a>
                    <a href="#prime-cities">Goa (Assagao, Anjuna, Candolim)</a>
                    <a href="#prime-cities">Pune (Koregaon Park, Kalyani Nagar)</a>
                </div>
            </div>

            <!-- Concierge & Physical Advisory Desks -->
            <div class="footer-col">
                <h4>Contact & Offices</h4>
                <div class="footer-links" style="gap: 1rem;">
                    <div>
                        <strong style="color: var(--white); font-size: 0.85rem;"><i class="fa-solid fa-location-dot" style="color: var(--gold-400); margin-right: 0.4rem;"></i> Mumbai Headquarters:</strong>
                        <p style="font-size: 0.8rem; margin: 0; color: var(--slate-400);">Tower 2, One World Center, Lower Parel, Mumbai 400013</p>
                    </div>
                    <div>
                        <strong style="color: var(--white); font-size: 0.85rem;"><i class="fa-solid fa-location-dot" style="color: var(--gold-400); margin-right: 0.4rem;"></i> NCR Advisory Desk:</strong>
                        <p style="font-size: 0.8rem; margin: 0; color: var(--slate-400);">Horizon Center, Golf Course Road, DLF Phase 5, Gurugram 122002</p>
                    </div>
                    <div>
                        <strong style="color: var(--white); font-size: 0.85rem;"><i class="fa-solid fa-location-dot" style="color: var(--gold-400); margin-right: 0.4rem;"></i> Bengaluru Desk:</strong>
                        <p style="font-size: 0.8rem; margin: 0; color: var(--slate-400);">Concorde Block, UB City, Vittal Mallya Rd, Bengaluru 560001</p>
                    </div>
                    <div style="margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.35rem;">
                        <a href="tel:<?= str_replace(' ', '', SITE_PHONE) ?>" style="color: var(--gold-400); font-weight: 600; font-size: 0.85rem;">
                            <i class="fa-solid fa-phone"></i> <?= e(SITE_PHONE) ?>
                        </a>
                        <a href="mailto:<?= SITE_EMAIL ?>" style="color: var(--slate-300); font-size: 0.85rem;">
                            <i class="fa-solid fa-envelope"></i> <?= SITE_EMAIL ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Bar with RERA Statutory Disclaimer -->
        <div class="footer-bottom">
            <div class="footer-bottom-inner">
                <div>
                    &copy; <?= date('Y') ?> <?= SITE_NAME ?> Private Limited. All rights reserved.
                </div>
                <div class="footer-rera-tag">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>MAHARERA: A51900038921 | HRERA: 114/2022 | KarRERA: PRM/KA/170916</span>
                </div>
                <div style="display: flex; gap: 1.5rem;">
                    <a href="javascript:void(0)" style="color: var(--slate-400);">Privacy Charter</a>
                    <a href="javascript:void(0)" style="color: var(--slate-400);">Terms of Advisory</a>
                    <a href="javascript:void(0)" style="color: var(--slate-400);">RERA Compliance</a>
                </div>
            </div>
            <div style="margin-top: 1.25rem; font-size: 0.68rem; line-height: 1.5; color: var(--slate-600); text-align: justify;">
                Statutory Regulatory Disclaimer: EstateVista India operates as a registered real estate advisory entity. All project elevations, floor layouts, dimensions, and amenities showcased are curated for illustrative representation in accordance with developer disclosures. Intending buyers and lessees are advised to independently inspect title documents and verify registration numbers on respective state RERA web portals before executing any commitment.
            </div>
        </div>
    </div>
</footer>

<!-- Vanilla JS Scripts -->
<script src="<?= BASE_URL ?>assets/js/main.js?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
<script src="<?= BASE_URL ?>assets/js/search-filter.js?v=<?= filemtime(__DIR__ . '/../assets/js/search-filter.js') ?>"></script>
<script src="<?= BASE_URL ?>assets/js/properties.js?v=<?= filemtime(__DIR__ . '/../assets/js/properties.js') ?>"></script>
<?php if (!empty($extra_js)): ?>
<script src="<?= e($extra_js) ?>"></script>
<?php endif; ?>
</body>
</html>

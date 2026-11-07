<?php
/**
 * EstateVista India - Homepage
 * Premium Indian Real Estate Portal
 * 
 * 16 Core Sections:
 * 1. Premium Sticky Navbar (navbar.php)
 * 2. Large Cinematic Hero Section
 * 3. Hero Heading: "Find a Place That Feels Like Home."
 * 4. Subtitle focused on discovering premium properties across India
 * 5. Advanced Property Search Panel (Buy / Rent / Commercial, City, Type, Budget, Search)
 * 6. Featured Properties Section (Premium Property Cards)
 * 7. Explore Properties by City (Mumbai, Delhi NCR, Bengaluru, Hyderabad, Pune, Goa)
 * 8. Luxury Residential Projects Section
 * 9. Why Choose EstateVista Section
 * 10. Property Categories: Apartments, Villas, Penthouses, Plots, Commercial Spaces
 * 11. New Launches & Exclusive Listings
 * 12. Testimonials Section
 * 13. Real Estate Insights & Articles Section
 * 14. Professional CTA Section for Property Owners to List Properties
 * 15. Newsletter Subscription Section
 * 16. Premium Multi-Column Footer with Navigation, Contact, Socials (footer.php)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'EstateVista India | Curated Luxury Real Estate Across India';
$page_desc = 'Discover luxury apartments, bespoke villas, sea-facing penthouses, and prime commercial spaces across Mumbai, Delhi NCR, Bengaluru, Hyderabad, Pune, and Goa. RERA verified.';

// Fetch Data Layers
$featured_properties = get_featured_properties(6);
$cities_list         = get_cities_list();
$luxury_projects     = get_luxury_projects();
$categories_list     = get_property_categories();
$new_launches        = get_new_launches();
$testimonials        = get_testimonials_list();
$articles            = get_articles();

// Include Header & Sticky Navbar
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main>
    <!-- =========================================================================
         2. Large Cinematic Hero Section
         3. Hero Heading: "Find a Place That Feels Like Home."
         4. Subtitle Focused on Discovering Premium Properties Across India
         5. Advanced Property Search Panel (Buy / Rent / Commercial)
         ========================================================================= -->
    <section class="hero-section" id="hero">
        <div class="hero-backdrop">
            <img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=2000&q=85" 
                 alt="Luxury Indian Real Estate Architecture" 
                 class="hero-backdrop-img">
            <div class="hero-overlay"></div>
        </div>

        <div class="container" style="position: relative; z-index: 5;">
            <div class="hero-content">
                <div class="hero-pill">
                    <i class="fa-solid fa-crown"></i>
                    <span>India’s Ultra-Prime Residential & Commercial Advisory</span>
                </div>
                
                <!-- 3. Exact Hero Heading -->
                <h1 class="hero-title">
                    Find a Place That <em>Feels Like Home.</em>
                </h1>
                
                <!-- 4. Subtitle Focused on Premium Properties Across India -->
                <p class="hero-desc">
                    Discover an extraordinary collection of luxury apartments, bespoke golf villas, sea-facing duplex penthouses, and prime commercial spaces across Mumbai, Delhi NCR, Bengaluru, Hyderabad, Pune, and Goa.
                </p>

                <!-- Quick Location Chips -->
                <div class="hero-quick-cities">
                    <span class="hero-quick-label">Prime Metros:</span>
                    <a href="#prime-cities" class="quick-city-chip">Worli, Mumbai</a>
                    <a href="#prime-cities" class="quick-city-chip">Golf Course Rd, Gurugram</a>
                    <a href="#prime-cities" class="quick-city-chip">Indiranagar, Bengaluru</a>
                    <a href="#prime-cities" class="quick-city-chip">Jubilee Hills, Hyderabad</a>
                    <a href="#prime-cities" class="quick-city-chip">Koregaon Park, Pune</a>
                    <a href="#prime-cities" class="quick-city-chip">Assagao, Goa</a>
                </div>
            </div>

            <!-- 5. Advanced Property Search Panel -->
            <div class="search-console-container">
                <div class="search-tabs">
                    <button type="button" class="search-tab-btn active" data-tab="buy">
                        <i class="fa-solid fa-key" style="margin-right: 0.35rem;"></i> Buy
                    </button>
                    <button type="button" class="search-tab-btn" data-tab="rent">
                        <i class="fa-solid fa-calendar-days" style="margin-right: 0.35rem;"></i> Rent
                    </button>
                    <button type="button" class="search-tab-btn" data-tab="commercial">
                        <i class="fa-solid fa-briefcase" style="margin-right: 0.35rem;"></i> Commercial
                    </button>
                </div>

                <div class="search-console-box">
                    <form id="heroSearchForm" method="GET" action="properties.php">
                        <input type="hidden" name="purpose" id="heroPurposeInput" value="buy">
                        <div class="search-inputs-grid">
                            <!-- City -->
                            <div class="search-field">
                                <label for="heroCitySelect"><i class="fa-solid fa-location-dot"></i> City</label>
                                <select name="city" id="heroCitySelect">
                                    <option value="all">All Prime Cities</option>
                                    <option value="mumbai">Mumbai (Worli, Bandra, South Mumbai)</option>
                                    <option value="delhi-ncr">Delhi NCR (Golf Course Rd Gurugram)</option>
                                    <option value="bengaluru">Bengaluru (Indiranagar, Whitefield)</option>
                                    <option value="hyderabad">Hyderabad (Jubilee Hills, Kokapet)</option>
                                    <option value="pune">Pune (Koregaon Park, Kalyani Nagar)</option>
                                    <option value="goa">Goa (Assagao, Anjuna, Candolim)</option>
                                </select>
                            </div>

                            <!-- Property Type -->
                            <div class="search-field">
                                <label for="heroTypeSelect"><i class="fa-solid fa-building-columns"></i> Property Type</label>
                                <select name="type" id="heroTypeSelect">
                                    <option value="all">All Property Types</option>
                                    <option value="apartments">Apartments</option>
                                    <option value="villas">Villas</option>
                                    <option value="sky-penthouses">Penthouses</option>
                                    <option value="plots">Plots</option>
                                    <option value="commercial">Commercial Spaces</option>
                                </select>
                            </div>

                            <!-- Budget Range -->
                            <div class="search-field">
                                <label for="heroBudgetSelect"><i class="fa-solid fa-indian-rupee-sign"></i> Budget Range</label>
                                <select name="budget_bracket" id="heroBudgetSelect">
                                    <option value="all">Any Budget Bracket</option>
                                    <option value="under-15cr">Under ₹ 15.00 Cr</option>
                                    <option value="15cr-25cr">₹ 15.00 Cr – ₹ 25.00 Cr</option>
                                    <option value="above-25cr">₹ 25.00 Cr+ (Ultra-Luxury)</option>
                                </select>
                            </div>

                            <!-- Search Button -->
                            <div>
                                <button type="submit" class="btn btn-gold btn-lg" style="width: 100%;">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <span>Search</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Authority Metrics Bar -->
    <section class="section section-dark" id="milestone-stats" style="padding-top: 3.25rem; padding-bottom: 3.25rem; border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
        <div class="container">
            <div class="grid grid-4">
                <div class="stat-card">
                    <div class="stat-number" data-target="4500" data-prefix="₹ " data-suffix="+ Cr">₹ 4,500+ Cr</div>
                    <div class="stat-label">Luxury Portfolio Transacted</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" data-target="450" data-prefix="" data-suffix="+ Families">450+ Families</div>
                    <div class="stat-label">CXO & UHNW Clients Advised</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" data-target="100" data-prefix="" data-suffix="%">100%</div>
                    <div class="stat-label">RERA Title Due Diligence</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" data-target="6" data-prefix="" data-suffix=" Metros">6 Metros</div>
                    <div class="stat-label">Key Indian Luxury Hubs</div>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================================================
         10. Property Categories Section: Apartments, Villas, Penthouses, Plots, Commercial Spaces
         ========================================================================= -->
    <section class="section section-beige" id="property-categories">
        <div class="container">
            <div class="section-header text-center">
                <div class="eyebrow">Architectural Classes</div>
                <h2 class="section-title">Explore by Property Category</h2>
                <p class="section-subtitle" style="margin: 0 auto;">
                    Tailored residential and corporate asset classes curated to match distinct lifestyle and investment objectives.
                </p>
            </div>

            <div class="grid-categories">
                <?php foreach ($categories_list as $cat): ?>
                    <a href="#curated-collection" class="category-card" data-category-filter="<?= e($cat['slug']) ?>">
                        <img src="<?= e($cat['image']) ?>" alt="<?= e($cat['name']) ?> Real Estate" class="category-card-bg" loading="lazy" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                        <div class="category-card-overlay"></div>
                        <div class="category-card-content">
                            <div class="category-icon">
                                <i class="fa-solid <?= e($cat['icon']) ?>"></i>
                            </div>
                            <h3 class="category-title"><?= e($cat['name']) ?></h3>
                            <p class="category-subtitle"><?= e($cat['subtitle']) ?></p>
                            <span class="category-count"><?= e($cat['count']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- =========================================================================
         6. Featured Properties Section (Premium Property Cards)
         ========================================================================= -->
    <section class="section section-white" id="curated-collection">
        <div class="container">
            <div class="section-header-row">
                <div>
                    <div class="eyebrow">Signature Portfolio</div>
                    <h2 class="section-title">Featured Luxury Properties</h2>
                    <p class="section-subtitle">
                        Every property undergoes 30-year title verification, structural audit, and state RERA compliance before publication.
                    </p>
                </div>

                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span id="collectionCountBadge" style="font-size: var(--text-xs); font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: var(--gold-700); background: var(--beige-100); padding: 0.45rem 1rem; border-radius: var(--radius-full); border: 1px solid var(--beige-300);">
                        <?= count($featured_properties) ?> Properties Available
                    </span>
                    <button type="button" class="btn btn-outline-gold btn-sm open-inquiry-modal" data-subject="Private Off-Market Dossier">
                        <i class="fa-solid fa-lock"></i> Off-Market Book
                    </button>
                </div>
            </div>

            <!-- Category Filter Tabs -->
            <div class="collection-filter-bar">
                <button type="button" class="collection-filter-btn active" data-filter="all">All Properties</button>
                <button type="button" class="collection-filter-btn" data-filter="sky-penthouses">Penthouses</button>
                <button type="button" class="collection-filter-btn" data-filter="villas">Villas</button>
                <button type="button" class="collection-filter-btn" data-filter="sea-facing">Sea-Facing</button>
                <button type="button" class="collection-filter-btn" data-filter="heritage-mansions">Mansions</button>
            </div>

            <!-- Dynamic Search Notification Banner -->
            <div id="searchNoticeBanner" style="display: none;"></div>

            <!-- Property Cards Grid -->
            <div class="grid-properties" id="propertiesGrid">
                <?php foreach ($featured_properties as $property): ?>
                    <?php include __DIR__ . '/includes/property-card.php'; ?>
                <?php endforeach; ?>
            </div>

            <div style="text-align: center; margin-top: 3.5rem;">
                <button type="button" class="btn btn-navy btn-lg open-inquiry-modal" data-subject="Request Complete 2026 Property Portfolio">
                    <i class="fa-solid fa-book-bookmark"></i>
                    <span>Download Full 2026 Portfolio Catalog (PDF)</span>
                </button>
            </div>
        </div>
    </section>

    <!-- =========================================================================
         7. Explore Properties by City (Mumbai, Delhi NCR, Bengaluru, Hyderabad, Pune, Goa)
         ========================================================================= -->
    <section class="section section-beige" id="prime-cities">
        <div class="container">
            <div class="section-header text-center">
                <div class="eyebrow">Prime Addresses</div>
                <h2 class="section-title">Explore Properties by City</h2>
                <p class="section-subtitle" style="margin: 0 auto;">
                    Prime residential and commercial enclaves across India’s most influential wealth centers.
                </p>
            </div>

            <div class="grid-cities">
                <?php foreach ($cities_list as $city): ?>
                    <div class="city-card">
                        <img src="<?= e($city['image_url']) ?>" alt="<?= e($city['name']) ?> Luxury Real Estate" class="city-card-img" loading="lazy" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/city-placeholder.svg';">
                        <div class="city-card-overlay"></div>
                        <div class="city-card-content">
                            <span class="city-property-count"><?= (int)$city['property_count'] ?> Prime Listings</span>
                            <h3 class="city-name"><?= e($city['name']) ?></h3>
                            <p class="city-tagline"><?= e($city['tagline'] ?? $city['state']) ?></p>
                            <a href="properties.php?city=<?= urlencode($city['slug'] ?? strtolower($city['name'])) ?>" class="city-explore-link">
                                <span>Explore Residences</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- =========================================================================
         8. Luxury Residential Projects Section
         ========================================================================= -->
    <section class="section section-white" id="luxury-projects">
        <div class="container">
            <div class="section-header-row">
                <div>
                    <div class="eyebrow">Landmark Developments</div>
                    <h2 class="section-title">Luxury Residential Projects</h2>
                    <p class="section-subtitle">
                        Iconic gated master-developments crafted by India’s most renowned developers including DLF Luxury, Lodha, Total Environment, and Oberoi.
                    </p>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-gold open-inquiry-modal" data-subject="Project Consultation">
                        <i class="fa-solid fa-building"></i> View All Developments
                    </button>
                </div>
            </div>

            <div class="grid grid-2">
                <?php foreach ($luxury_projects as $proj): ?>
                    <div class="project-card">
                        <div class="project-banner-wrapper">
                            <img src="<?= e($proj['banner_image']) ?>" alt="<?= e($proj['title']) ?>" class="project-banner-img" loading="lazy" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                            <?php 
                                $statusClass = ($proj['status'] === 'Ready to Handover') ? 'tag-ready' : 'tag-construction';
                            ?>
                            <span class="project-status-tag <?= $statusClass ?>">
                                <i class="fa-solid <?= ($proj['status'] === 'Ready to Handover') ? 'fa-circle-check' : 'fa-clock' ?>"></i>
                                <?= e($proj['status']) ?>
                            </span>
                        </div>
                        <div class="project-body">
                            <div class="project-dev"><?= e($proj['developer_name'] ?? 'Premier Developer') ?></div>
                            <h3 class="project-title"><?= e($proj['title']) ?></h3>
                            <div class="project-location">
                                <i class="fa-solid fa-location-dot"></i>
                                <span><?= e($proj['locality']) ?>, <?= e($proj['city_name']) ?></span>
                            </div>

                            <div class="project-specs-row">
                                <div class="project-spec-item">
                                    <span class="project-spec-label">Configuration</span>
                                    <span class="project-spec-val"><?= e($proj['configuration']) ?></span>
                                </div>
                                <div class="project-spec-item">
                                    <span class="project-spec-label">Possession</span>
                                    <span class="project-spec-val"><?= e($proj['possession_date']) ?></span>
                                </div>
                                <div class="project-spec-item">
                                    <span class="project-spec-label">RERA Reg.</span>
                                    <span class="project-spec-val" style="font-size: 0.72rem;"><?= e($proj['rera_id']) ?></span>
                                </div>
                            </div>

                            <p class="project-highlights-text">
                                <?= e($proj['highlights']) ?>
                            </p>

                            <div class="project-footer">
                                <div class="project-price-block">
                                    <span class="project-price-label">Investment</span>
                                    <span class="project-price-val"><?= e($proj['starting_price_display']) ?></span>
                                </div>
                                <button type="button" class="btn btn-gold btn-sm open-inquiry-modal" data-property="<?= e($proj['title']) ?>" data-subject="Project Dossier: <?= e($proj['title']) ?>">
                                    <span>Download Brochure</span>
                                    <i class="fa-solid fa-arrow-down-to-bracket"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- =========================================================================
         11. New Launches and Exclusive Listings
         ========================================================================= -->
    <section class="section section-dark" id="new-launches">
        <div class="container">
            <div class="section-header text-center">
                <div class="eyebrow" style="color: var(--gold-400);">Priority Allocations</div>
                <h2 class="section-title">New Launches & Exclusive Listings</h2>
                <p class="section-subtitle" style="margin: 0 auto; color: var(--slate-300);">
                    Be the first to access pre-market inventory and developer launch allotments before public release.
                </p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 2rem;">
                <?php foreach ($new_launches as $launch): ?>
                    <div class="launch-card">
                        <div class="launch-media">
                            <img src="<?= e($launch['image']) ?>" alt="<?= e($launch['title']) ?>" class="launch-img" loading="lazy" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                            <span class="launch-badge">
                                <i class="fa-solid fa-bolt" style="margin-right: 0.35rem;"></i> <?= e($launch['badge']) ?>
                            </span>
                        </div>
                        <div class="launch-info">
                            <div>
                                <div class="launch-dev-tag"><?= e($launch['developer_name']) ?></div>
                                <h3 class="launch-title"><?= e($launch['title']) ?></h3>
                                <div class="launch-location">
                                    <i class="fa-solid fa-location-dot" style="color: var(--gold-400); margin-right: 0.4rem;"></i>
                                    <span><?= e($launch['locality']) ?>, <?= e($launch['city_name']) ?></span>
                                </div>

                                <div class="launch-details-grid">
                                    <div class="launch-detail-item">
                                        <span>Typology</span>
                                        <strong><?= e($launch['configuration']) ?></strong>
                                    </div>
                                    <div class="launch-detail-item">
                                        <span>Carpet Area</span>
                                        <strong><?= e($launch['carpet_area']) ?></strong>
                                    </div>
                                    <div class="launch-detail-item">
                                        <span>Timeline</span>
                                        <strong><?= e($launch['possession']) ?></strong>
                                    </div>
                                    <div class="launch-detail-item">
                                        <span>RERA ID</span>
                                        <strong style="font-size: 0.75rem;"><?= e($launch['rera_id']) ?></strong>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.25rem;">
                                <div>
                                    <span style="font-size: 0.7rem; text-transform: uppercase; color: var(--slate-400); letter-spacing: 0.1em; display: block;">Launch Price</span>
                                    <strong style="font-family: var(--font-heading); font-size: 1.5rem; color: var(--gold-400);"><?= e($launch['starting_price']) ?></strong>
                                </div>
                                <button type="button" class="btn btn-gold open-inquiry-modal" data-property="<?= e($launch['title']) ?>" data-subject="Priority Pre-Launch Allocation: <?= e($launch['title']) ?>">
                                    <i class="fa-solid fa-crown"></i>
                                    <span>Register Priority Allocation</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- =========================================================================
         9. Why Choose EstateVista Section
         ========================================================================= -->
    <section class="section section-beige" id="why-estatevista">
        <div class="container">
            <div class="section-header text-center">
                <div class="eyebrow">The Institutional Standard</div>
                <h2 class="section-title">Why Choose EstateVista India</h2>
                <p class="section-subtitle" style="margin: 0 auto;">
                    We are not mass aggregators. EstateVista functions as a discreet, fiduciary real estate advisory partner for India's business leaders and global NRIs.
                </p>
            </div>

            <div class="grid grid-3">
                <div class="advisory-card">
                    <span class="advisory-card-num">01</span>
                    <div class="advisory-icon-box">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <h3>100% RERA & Legal Due Diligence</h3>
                    <p>
                        Every property on our platform is evaluated through our rigorous 30-year title search protocol, encumbrance auditing, sanctioned master-plan compliance, and escrow account verification.
                    </p>
                </div>

                <div class="advisory-card">
                    <span class="advisory-card-num">02</span>
                    <div class="advisory-icon-box">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <h3>Zero Brokerage on Direct Developer Portfolios</h3>
                    <p>
                        Enjoy zero brokerage fees on developer direct primary market allocations. Benefit from direct institutional access, preferred pricing brackets, and executive priority floor selections.
                    </p>
                </div>

                <div class="advisory-card">
                    <span class="advisory-card-num">03</span>
                    <div class="advisory-icon-box">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <h3>Discreet Off-Market Inventory</h3>
                    <p>
                        Access unlisted trophy penthouses and sea-facing estates traded exclusively under non-disclosure agreements, shielded from public portal visibility to protect buyer privacy.
                    </p>
                </div>

                <div class="advisory-card">
                    <span class="advisory-card-num">04</span>
                    <div class="advisory-icon-box">
                        <i class="fa-solid fa-gem"></i>
                    </div>
                    <h3>Dedicated Luxury Relationship Partner</h3>
                    <p>
                        Each investor is assigned a Senior Luxury Acquisition Advisor who coordinates private site visits, architect evaluations, developer CXO meetings, and bespoke registry paperwork.
                    </p>
                </div>

                <div class="advisory-card">
                    <span class="advisory-card-num">05</span>
                    <div class="advisory-icon-box">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h3>Capital Growth & Rental Yield Advisory</h3>
                    <p>
                        Benefit from proprietary transaction data on South Mumbai micro-markets, Golf Course Road capital appreciation trends, and high-yield holiday rental projections across North Goa.
                    </p>
                </div>

                <div class="advisory-card">
                    <span class="advisory-card-num">06</span>
                    <div class="advisory-icon-box">
                        <i class="fa-solid fa-file-contract"></i>
                    </div>
                    <h3>End-to-End Registration & Tax Concierge</h3>
                    <p>
                        From Section 54 capital gains structuring to Sub-Registrar deed execution, franking, and stamp duty payments, our legal desk manages the entire acquisition lifecycle smoothly.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================================================
         12. Testimonials Section
         ========================================================================= -->
    <section class="section section-white" id="testimonials">
        <div class="container">
            <div class="section-header text-center">
                <div class="eyebrow">Client Testimonials</div>
                <h2 class="section-title">Trusted by India’s Discerning Leaders</h2>
                <p class="section-subtitle" style="margin: 0 auto;">
                    Read perspectives from entrepreneurs, venture partners, and NRI leaders who entrusted their milestone acquisitions to EstateVista.
                </p>
            </div>

            <div class="grid grid-3">
                <?php foreach ($testimonials as $t): ?>
                    <div class="testimonial-card">
                        <div>
                            <div class="testimonial-quote-icon">
                                <i class="fa-solid fa-quote-left"></i>
                            </div>
                            <p class="testimonial-text">
                                "<?= e($t['review']) ?>"
                            </p>
                        </div>
                        <div class="testimonial-author">
                            <img src="<?= e(safe_image_url($t['client_avatar'], 'avatar')) ?>" alt="<?= e($t['client_name']) ?>" class="author-avatar" loading="lazy" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/avatar-placeholder.svg';">
                            <div class="author-info">
                                <h4><?= e($t['client_name']) ?></h4>
                                <p><?= e($t['client_title']) ?> • <?= e($t['location']) ?></p>
                                <?php if (!empty($t['verified_buyer'])): ?>
                                    <span class="verified-buyer-badge">
                                        <i class="fa-solid fa-circle-check"></i> Verified Acquisition
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- =========================================================================
         13. Real Estate Insights & Articles Section
         ========================================================================= -->
    <section class="section section-beige" id="insights">
        <div class="container">
            <div class="section-header-row">
                <div>
                    <div class="eyebrow">Market Intelligence</div>
                    <h2 class="section-title">Real Estate Insights & Research</h2>
                    <p class="section-subtitle">
                        Quarterly market reports, regulatory analysis, and micro-market investment perspectives from our in-house advisory desk.
                    </p>
                </div>
                <div>
                    <a href="javascript:void(0)" class="btn btn-outline-gold" onclick="alert('Viewing archive of EstateVista Quarterly Intelligence Reports.');">
                        <i class="fa-solid fa-newspaper"></i> View All Articles
                    </a>
                </div>
            </div>

            <div class="grid grid-3">
                <?php foreach ($articles as $art): ?>
                    <article class="article-card">
                        <div class="article-thumb-wrapper">
                            <img src="<?= e($art['image_url']) ?>" alt="<?= e($art['title']) ?>" class="article-thumb" loading="lazy" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                            <span class="article-category-pill"><?= e($art['category']) ?></span>
                        </div>
                        <div class="article-body">
                            <div class="article-meta">
                                <span><i class="fa-regular fa-calendar"></i> <?= e($art['publish_date']) ?></span>
                                <span><i class="fa-regular fa-clock"></i> <?= e($art['read_time']) ?></span>
                            </div>
                            <h3 class="article-title"><?= e($art['title']) ?></h3>
                            <p class="article-excerpt"><?= e($art['excerpt']) ?></p>
                            <a href="javascript:void(0)" class="article-read-link" onclick="alert('Full report access is included in the EstateVista Private Dossier.');">
                                <span>Read Full Analysis</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- =========================================================================
         14. Professional CTA Section for Property Owners to List Properties
         ========================================================================= -->
    <section class="section section-white" id="list-property">
        <div class="container">
            <div class="owner-cta-card">
                <div class="owner-cta-content">
                    <div class="eyebrow" style="color: var(--gold-400);"><i class="fa-solid fa-award"></i> For Property Owners</div>
                    <h2>Are You a Property Owner? List Your Exclusive Residence with EstateVista</h2>
                    <p>
                        Present your penthouse, independent villa, or prime commercial asset to India’s most qualified network of family offices, CXOs, and global NRI buyers under strict confidentiality.
                    </p>

                    <div class="owner-benefits-list">
                        <div class="owner-benefit-item">
                            <div class="owner-benefit-icon"><i class="fa-solid fa-check"></i></div>
                            <div class="owner-benefit-text">
                                <strong>0% Upfront Listing Fee</strong>
                                <span>Pay only upon successful registration and deed completion.</span>
                            </div>
                        </div>
                        <div class="owner-benefit-item">
                            <div class="owner-benefit-icon"><i class="fa-solid fa-user-check"></i></div>
                            <div class="owner-benefit-text">
                                <strong>Pre-Vetted HNW Buyers Only</strong>
                                <span>Zero casual inquiries or spam; only verified buyers with pre-cleared capital.</span>
                            </div>
                        </div>
                        <div class="owner-benefit-item">
                            <div class="owner-benefit-icon"><i class="fa-solid fa-shield-halved"></i></div>
                            <div class="owner-benefit-text">
                                <strong>Discreet Confidential Marketing</strong>
                                <span>Choose unlisted private placement protected by Non-Disclosure Agreements.</span>
                            </div>
                        </div>
                        <div class="owner-benefit-item">
                            <div class="owner-benefit-icon"><i class="fa-solid fa-camera"></i></div>
                            <div class="owner-benefit-text">
                                <strong>Bespoke Architectural Media</strong>
                                <span>Complimentary high-definition photography, 4K video tours, and floor layout digitizing.</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <a href="<?= BASE_URL ?>list-property.php" class="btn btn-gold btn-lg" style="text-decoration: none;">
                            <i class="fa-solid fa-plus-circle"></i>
                            <span>List Your Property with EstateVista</span>
                        </a>
                    </div>
                </div>

                <div class="owner-cta-card-badge">
                    <div style="font-size: 3rem; color: var(--gold-400); margin-bottom: 0.5rem;">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                    <div style="font-family: var(--font-heading); font-size: 2.25rem; font-weight: 700; color: var(--white); margin-bottom: 0.25rem;">
                        ₹ 4,500+ Cr
                    </div>
                    <p style="font-size: 0.85rem; color: var(--slate-300); margin-bottom: 1.5rem;">
                        Cumulative Luxury Asset Value Transacted Across Mumbai, Delhi NCR, and Bengaluru.
                    </p>
                    <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.25rem; font-size: 0.78rem; color: var(--gold-300);">
                        <i class="fa-solid fa-phone"></i> Direct Owner Advisory Desk: <?= e(SITE_PHONE) ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =========================================================================
         15. Newsletter Subscription Section
         ========================================================================= -->
    <section class="section section-beige" id="newsletter">
        <div class="container">
            <div class="newsletter-card">
                <div class="newsletter-icon">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
                <h3>Stay Ahead with the EstateVista Private Dossier</h3>
                <p>
                    Receive curated monthly market intelligence, newly sanctioned RERA project releases, and confidential off-market listings delivered discreetly to your inbox.
                </p>

                <form class="newsletter-form-inline" id="mainNewsletterForm" action="javascript:void(0)">
                    <input type="email" name="email" placeholder="Enter your corporate or personal email..." required aria-label="Email Address">
                    <button type="submit" class="btn btn-gold">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Subscribe</span>
                    </button>
                </form>
                <div id="newsletterFeedback" style="display: none; margin-top: 1rem; font-size: 0.85rem; padding: 0.75rem 1.25rem; border-radius: var(--radius-sm); max-width: 550px; margin-left: auto; margin-right: auto;"></div>

                <div style="margin-top: 1.25rem; font-size: 0.75rem; color: var(--slate-400); display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <i class="fa-solid fa-shield-halved" style="color: var(--gold-500);"></i>
                    <span>We respect your privacy. No spam. One-click unsubscribe at any time.</span>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
// 16. Premium Multi-Column Footer with Navigation, Contact, Socials
require_once __DIR__ . '/includes/footer.php';
?>

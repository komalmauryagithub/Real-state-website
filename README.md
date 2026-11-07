# EstateVista India — Luxury Real Estate Portal

A bespoke, production-ready Indian real estate platform crafted with **HTML5, CSS3, Vanilla JavaScript, PHP 8+, and MySQL**.

Designed specifically to capture the prestige, discretion, and elegance of India’s ultra-prime residential and commercial market (Mumbai, Delhi NCR, Bengaluru, Hyderabad, Pune, and Goa).

---

## 🎨 Design System & Palette

- **Deep Navy Blue:** `#060B14`, `#0B1528`, `#102142` (Conveys institutional security, prestige, and quiet luxury)
- **Subtle Champagne Gold:** `#C5A880`, `#AA8C5A`, `#F8F4EC` (Used deliberately for micro-accents, badges, and CTAs)
- **Warm Beige & Ivory:** `#FAF8F5`, `#F5F0E8`, `#EAE1D3` (High-end architectural softness; replaces harsh gray backgrounds)
- **Crisp White & Neutrals:** `#FFFFFF`, `#1E293B`, `#64748B`
- **Typography:**
  - **Headings & Display:** *Playfair Display* & *Cormorant Garamond* (Editorial, bespoke serif)
  - **Body & UI:** *Plus Jakarta Sans* (Clean, legible geometric sans-serif)
- **Icons:** Font Awesome 6 Pro-grade icons loaded via CDN.

---

## 🏛️ All 16 Homepage Sections Built & Integrated

1. **Premium Sticky Navigation Bar:**
   - Brand Logo with gold monogram emblem.
   - Links: **Buy**, **Rent**, **Commercial**, **Projects**, **About**, **Contact**.
   - **Login** button (with interactive Client Portal Modal) + **Post Property** CTA.
   - Top utility bar with MahaRERA number, Concierge direct phone, and WhatsApp Private Desk.
   - Responsive mobile navigation drawer.

2. **Cinematic Hero Section:**
   - High-definition architectural dusk imagery with subtle vignette gradient.
   - Micro-badge: *"India’s Ultra-Prime Residential & Commercial Advisory"*.
   - Quick location chips (Worli, Golf Course Rd, Indiranagar, Jubilee Hills, Koregaon Park, Assagao).

3. **Hero Heading:**
   - `"Find a Place That Feels Like Home."`

4. **Curated Subtitle:**
   - Focused on discovering luxury apartments, bespoke villas, sea-facing penthouses, and prime commercial spaces across India.

5. **Advanced Property Search Panel:**
   - **Buy / Rent / Commercial** interactive tabs.
   - **City** selector (Mumbai, Delhi NCR, Bengaluru, Hyderabad, Pune, Goa).
   - **Property Type** selector (Apartments, Villas, Penthouses, Plots, Commercial Spaces).
   - **Budget Range** selector in authentic INR formatting (Under ₹ 15 Cr, ₹ 15 Cr – ₹ 25 Cr, ₹ 25 Cr+, or monthly rental brackets).
   - **Search** button triggering smooth filtered view and match count banner.

6. **Featured Properties Section:**
   - High-end cards with RERA verified badges, Indian pricing (`₹ 34.50 Cr`, `₹ 28.50 Cr`, etc.), BHK, baths, carpet area in sq.ft, local storage wishlist heart, and quick inquiry buttons.

7. **Explore Properties by City:**
   - Image-based cards for **Mumbai, Delhi NCR, Bengaluru, Hyderabad, Pune, and Goa** with localized taglines and active listing counts.

8. **Luxury Residential Projects Section:**
   - Landmark gated developments: *The Camellias DLF*, *Lodha Altamount*, *Pursuit of a Radical Rhapsody by Total Environment*, and *Oberoi Three Sixty West*.
   - Project status tags (`Ready for Handover`, `Under Construction`), configurations, and brochure downloads.

9. **Why Choose EstateVista Section:**
   - 6 institutional pillars: 100% RERA & Legal Due Diligence, Zero Brokerage on Direct Developer Portfolios, Discreet Off-Market Inventory, Dedicated Luxury Relationship Partner, Capital Growth & Rental Yield Advisory, and End-to-End Registration & Tax Concierge.

10. **Property Categories Section:**
    - Dedicated 5-class visual grid: **Apartments**, **Villas**, **Penthouses**, **Plots**, and **Commercial Spaces** with custom icons and property counts.

11. **New Launches & Exclusive Listings:**
    - Highlighting *DLF Privana West*, *Lodha World One Reserve*, and *Prestige Ocean Towers* with Priority Booking tags, carpet areas, launch prices, and priority registration.

12. **Testimonials Section:**
    - Verified reviews from Private Equity Managing Partners, Tech Founders, and Design Directors praising the white-glove advisory experience.

13. **Real Estate Insights & Articles Section:**
    - Market intelligence: *Mumbai vs Gurgaon Ultra-HNW Trends*, *RERA Legal Checklist for Villa Acquisitions*, and *The Rise of Branded Residences in India*.

14. **Professional CTA for Property Owners to List Properties:**
    - *"Are You a Property Owner? List Your Exclusive Residence with EstateVista"*.
    - Highlights: 0% Upfront Listing Fee, Verified HNW Buyers Only, Strict NDA Confidentiality, and Dedicated Relationship Partner.
    - Triggers the dedicated **Property Owner Listing Modal**.

15. **Newsletter Subscription Section:**
    - *"Stay Ahead with the EstateVista Private Dossier"* with clean inline email form and privacy guarantee.

16. **Premium Multi-Column Footer:**
    - Brand summary, Navigation links, Prime Metros, Physical Advisory Desks in Mumbai (Lower Parel), Gurugram (Golf Course Rd), and Bengaluru (UB City).
    - Social links (LinkedIn, Instagram, YouTube, X).
    - Statutory RERA Regulatory Disclaimer and copyright.

---

## 🔍 Dedicated Property Listing & Advanced Search (`properties.php`)

A comprehensive, MySQL-driven luxury property search engine supporting dynamic filtering, shareable GET parameters, and interactive view modes:

### Key Listing Features:
1. **Grid and List View Toggle:** Seamlessly switch between responsive multi-column card grid and expansive horizontal list view. Choice is stored in `localStorage` and URL parameters (`?view=list`).
2. **Interactive Image Gallery Previews:** Each property card features in-card carousel arrows (`<`, `>`) and dot indicators to preview 3–4 high-resolution photos directly without leaving the listing page.
3. **Property Information:** Displays Property Title, Locality, City, authentic INR price in Crores/Lakhs, BHK, Bathrooms, and Carpet Area in sq.ft.
4. **Badges:** Property type pill, green-checked `RERA Verified` badge, `Exclusive` / `Featured` badge.
5. **Interactive Shortlist:** Heart button toggles favorites in browser `localStorage`.
6. **Detailed Modal / Inquiry:** "View Details" button triggers the VIP Advisory modal pre-filled with the property.
7. **Accurate Pagination:** Full pagination engine with preserved search filters across page jumps (`?page=2`).
8. **Multi-Parameter Sorting:**
   - Relevance & Featured First (`?sort=relevance`)
   - Price: Low to High (`?sort=price_asc`)
   - Price: High to Low (`?sort=price_desc`)
   - Newest First (`?sort=newest`)

### Search Filters Supported:
- **Transaction Purpose:** `purpose=buy`, `purpose=rent`, `purpose=commercial`
- **City:** `city=mumbai`, `city=delhi-ncr`, `city=bengaluru`, `city=hyderabad`, `city=pune`, `city=goa`
- **Locality:** `locality=worli`, `locality=golf+course+road`, etc.
- **Property Type:** `type=sky-penthouses`, `type=villas`, `type=apartments`, `type=plots`, `type=commercial`
- **Price Range:** `min_price=100000000&max_price=300000000` (or `budget_bracket=15cr-25cr`)
- **BHK Configurations:** `bhk[]=3&bhk[]=4&bhk[]=5` (supports multi-BHK arrays)
- **Carpet Area in Sq.Ft:** `min_area=3000&max_area=10000`
- **Furnishing Status:** `furnishing=bespoke-furnished`, `semi-furnished`, `unfurnished`
- **Construction Status:** `status=ready_to_move`, `status=under_construction`
- **Possession Timeline:** `possession=Immediate`, `possession=2026`, `possession=2027`
- **Verified Only:** `verified=1`

### Sample Shareable URLs:
- **All Mumbai Penthouses (Price High to Low):**  
  `http://localhost/Real%20Estate%20Website/properties.php?purpose=buy&city=mumbai&type=sky-penthouses&sort=price_desc`
- **Luxury Rentals on Golf Course Road & Mumbai in List View:**  
  `http://localhost/Real%20Estate%20Website/properties.php?purpose=rent&view=list`
- **Grade-A Commercial Spaces:**  
  `http://localhost/Real%20Estate%20Website/properties.php?purpose=commercial`
- **Verified 4 & 5 BHK Residences in Ready-to-Move Status:**  
  `http://localhost/Real%20Estate%20Website/properties.php?verified=1&status=ready_to_move&bhk[]=4&bhk[]=5`

---

## 📁 Enterprise Architecture & Production Modules

```
Real Estate Website/
├── .env.example              # Environment template for production deployment
├── .htaccess                 # Apache server lockdown (security headers, caching, 404 routing)
├── 404.php                   # Custom luxury 404 error page with quick search and concierge
├── robots.txt                # Search engine crawler directives
├── sitemap.php               # Dynamic XML sitemap generator
├── sitemap.xml               # Static XML sitemap snapshot
├── DEPLOYMENT.md             # Complete step-by-step production hosting & deployment guide
├── README.md                 # Project architecture documentation
├── index.php                 # 16-section luxury homepage
├── properties.php            # Dedicated property search & filter engine (Buy/Rent/Commercial)
├── property-detail.php       # Individual property showcase (lightbox, floorplans, enquiry forms, Schema.org)
├── list-property.php         # Multi-step property submission for owners and agents
├── edit-property.php         # Owner listing editing & revision console
├── dashboard.php             # Private customer portal (shortlist, active enquiries, site visits)
├── login.php / register.php  # Customer authentication with bcrypt hashing & CSRF protection
├── admin/                    # Administrative Command Center & Real Estate Agent CRM
│   ├── index.php             # Executive KPI dashboard with interactive charts
│   ├── crm-dashboard.php     # Real Estate Agent CRM (leads pipeline, metrics, reminders)
│   ├── leads.php             # 7-stage CRM lead pipeline (Kanban drag-and-drop & table view)
│   ├── properties.php        # Property management & editorial curation
│   ├── approvals.php         # Pending owner listing approval workflow
│   ├── enquiries.php         # Unified client inquiries and callback management
│   ├── site-visits.php       # Chauffeur & VIP site visit scheduling
│   ├── cities.php            # Prime metro hubs & locality management
│   ├── categories.php        # Residential & commercial category administration
│   ├── amenities.php         # Luxury amenity registry
│   ├── subscribers.php       # Confidential newsletter subscriber management
│   └── settings.php          # Platform configuration & audit log viewer
├── api/                      # Clean RESTful endpoints
│   ├── inquiry.php           # VIP consultations & property enquiries
│   ├── newsletter.php        # Newsletter subscriptions
│   ├── favorite.php          # Wishlist synchronization
│   ├── owner-listing.php     # Owner valuation & listing submissions
│   └── login.php             # Async modal authentication
├── config/
│   ├── config.php            # .env loader, environment error suppression, brand constants
│   ├── database.php          # Singleton PDO connection with transaction support
│   └── .htaccess             # Denies direct web access to configuration
├── database/
│   ├── schema.sql            # Clean DDL for all 21 relational tables & foreign keys
│   ├── seed.sql              # Clean DML seed data with luxury residences, agents & users
│   └── .htaccess             # Denies direct web access to database dumps
├── includes/
│   ├── header.php            # HTML <head>, Canonical URL, OpenGraph, Schema.org JSON-LD
│   ├── navbar.php            # Sticky header with Buy, Rent, Commercial, Projects, Login
│   ├── footer.php            # Multi-column footer, Login Modal, Owner Modal, VIP Modal
│   ├── property-card.php     # Property card with carousel preview, lazy loading, async decoding
│   ├── functions.php         # Parameterized search, transaction sync, currency formatters
│   ├── auth.php / auth_middleware.php # RBAC authorization & session security
│   └── upload.php            # Secure file upload engine (MIME detection, random hash filenames)
├── uploads/                  # Media directory with .htaccess prohibiting script execution
└── scratch/                  # Automated verification test suites
    ├── master_qa_suite.php   # Master QA suite (39 functional, SEO, and security tests)
    └── test_full_site_render.php # Renders all 18 frontend and administrative pages
```

---

## 🚀 Running & Verifying Locally

1. Place the project folder in your Apache web root:
   `c:\xampp\htdocs\Real Estate Website\`
2. Ensure Apache and MySQL are running in your XAMPP Control Panel.
3. Run the automated Master QA Suite:
   ```bash
   php scratch/master_qa_suite.php
   php scratch/test_full_site_render.php
   ```
4. Access the portal in your browser:
   - **Homepage:** `http://localhost/Real%20Estate%20Website/`
   - **Properties Search:** `http://localhost/Real%20Estate%20Website/properties.php`
   - **Customer Portal:** `http://localhost/Real%20Estate%20Website/login.php` (User: `client@estatevistaindia.com` / `Client@123`)
   - **Admin Command Center:** `http://localhost/Real%20Estate%20Website/admin/` (User: `admin@estatevistaindia.com` / `Admin@123`)
   - **Agent CRM:** `http://localhost/Real%20Estate%20Website/admin/crm-dashboard.php` (User: `aditya.birla@estatevistaindia.com` / `Agent@123`)
   - **404 Page:** `http://localhost/Real%20Estate%20Website/404.php`
   - **XML Sitemap:** `http://localhost/Real%20Estate%20Website/sitemap.xml`



<?php
/**
 * EstateVista India - Dedicated Property Listing & Search Page
 * 
 * Features:
 * - Dynamic filtering by Purpose (Buy/Rent/Commercial), City, Locality, Type, Price, BHK, Area, Furnishing, Status, Possession, Verified
 * - MySQL-backed parameterized search engine
 * - Grid and List view toggle
 * - Image gallery previews on cards
 * - Sorting by Relevance, Price Low-High, Price High-Low, Newest
 * - Shareable GET URL parameters
 * - Responsive filter sidebar with mobile drawer
 * - Complete pagination system
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Parse GET Parameters safely
$purpose      = isset($_GET['purpose']) ? trim($_GET['purpose']) : '';
$city         = isset($_GET['city']) ? trim($_GET['city']) : '';
$locality     = isset($_GET['locality']) ? trim($_GET['locality']) : '';
$type         = isset($_GET['type']) ? trim($_GET['type']) : '';
$min_price    = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : '';
$max_price    = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : '';
$budget_bracket = isset($_GET['budget_bracket']) ? trim($_GET['budget_bracket']) : '';
if (!empty($budget_bracket) && empty($min_price) && empty($max_price)) {
    if ($budget_bracket === 'under-15cr') {
        $max_price = 150000000;
    } elseif ($budget_bracket === '15cr-25cr') {
        $min_price = 150000000;
        $max_price = 250000000;
    } elseif ($budget_bracket === 'above-25cr') {
        $min_price = 250000000;
    }
}
$bhk          = isset($_GET['bhk']) ? (is_array($_GET['bhk']) ? $_GET['bhk'] : explode(',', $_GET['bhk'])) : [];
$min_area     = isset($_GET['min_area']) && is_numeric($_GET['min_area']) ? (int)$_GET['min_area'] : '';
$max_area     = isset($_GET['max_area']) && is_numeric($_GET['max_area']) ? (int)$_GET['max_area'] : '';
$furnishing   = isset($_GET['furnishing']) ? trim($_GET['furnishing']) : '';
$status       = isset($_GET['status']) ? trim($_GET['status']) : '';
$possession   = isset($_GET['possession']) ? trim($_GET['possession']) : '';
$verified     = !empty($_GET['verified']) ? 1 : 0;
$sort         = isset($_GET['sort']) ? trim($_GET['sort']) : 'relevance';
$view         = isset($_GET['view']) && in_array($_GET['view'], ['grid', 'list']) ? $_GET['view'] : 'grid';
$page         = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page     = 6;

// Build filter array
$filters = [
    'purpose'    => $purpose,
    'city'       => $city,
    'locality'   => $locality,
    'type'       => $type,
    'min_price'  => $min_price,
    'max_price'  => $max_price,
    'bhk'        => $bhk,
    'min_area'   => $min_area,
    'max_area'   => $max_area,
    'furnishing' => $furnishing,
    'status'     => $status,
    'possession' => $possession,
    'verified'   => $verified
];

// Query MySQL
$search_results = search_properties_advanced($filters, $sort, $page, $per_page);
$properties     = $search_results['properties'];
$total_count        = (int)$search_results['total'];
$total_pages_count  = (int)$search_results['pages'];
$current_page_idx   = (int)$search_results['current_page'];

// Helper to build URL with modified parameter
if (!function_exists('build_query_url')) {
    function build_query_url(array $overrides = []): string {
        $params = $_GET;
        foreach ($overrides as $k => $v) {
            if ($v === null) {
                unset($params[$k]);
            } else {
                $params[$k] = $v;
            }
        }
        return 'properties.php?' . http_build_query($params);
    }
}

// Prepare dynamic Page Title
$title_parts = [];
if (!empty($purpose)) {
    $title_parts[] = ucfirst($purpose);
}
if (!empty($type)) {
    $title_parts[] = ucwords(str_replace('-', ' ', $type));
} else {
    $title_parts[] = 'Luxury Residences & Properties';
}
if (!empty($city) && $city !== 'all') {
    $title_parts[] = 'in ' . ucwords($city);
} else {
    $title_parts[] = 'Across India';
}
$page_title = implode(' ', $title_parts);
$page_desc = "Explore curated luxury properties for sale and lease across Mumbai, Delhi NCR, Bengaluru, Hyderabad, Pune, and Goa. RERA verified.";

// Available cities and categories for filters
$cities_list = get_cities_list();
$categories_list = get_property_categories();

// Include Header & Sticky Navbar
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main>
    <!-- =========================================================================
         Listing Hero & Breadcrumb Header
         ========================================================================= -->
    <section class="listing-hero-header">
        <div class="container">
            <!-- Breadcrumbs -->
            <nav class="listing-breadcrumb" aria-label="Breadcrumb">
                <a href="<?= BASE_URL ?>"><i class="fa-solid fa-house"></i> Home</a>
                <span>/</span>
                <a href="<?= BASE_URL ?>properties.php">Properties</a>
                <?php if (!empty($purpose)): ?>
                    <span>/</span>
                    <span style="color: var(--white); text-transform: capitalize;"><?= e($purpose) ?></span>
                <?php endif; ?>
                <?php if (!empty($city) && $city !== 'all'): ?>
                    <span>/</span>
                    <span style="color: var(--gold-400); text-transform: capitalize;"><?= e($city) ?></span>
                <?php endif; ?>
            </nav>

            <h1 class="listing-hero-title"><?= e($page_title) ?></h1>
            <p class="listing-hero-subtitle">
                Explore handpicked architectural landmarks, penthouses, and gated estates verified by our legal and RERA advisory desks.
            </p>

            <!-- Active Filter Chips -->
            <?php 
                $active_filters = [];
                if (!empty($purpose)) $active_filters['purpose'] = 'Purpose: ' . ucfirst($purpose);
                if (!empty($city) && $city !== 'all') $active_filters['city'] = 'City: ' . ucwords($city);
                if (!empty($locality)) $active_filters['locality'] = 'Locality: ' . $locality;
                if (!empty($type) && $type !== 'all') $active_filters['type'] = 'Type: ' . ucwords(str_replace('-', ' ', $type));
                if (!empty($min_price)) $active_filters['min_price'] = 'Min: ' . format_inr_price($min_price);
                if (!empty($max_price)) $active_filters['max_price'] = 'Max: ' . format_inr_price($max_price);
                if (!empty($bhk)) $active_filters['bhk'] = 'BHK: ' . implode(', ', (array)$bhk);
                if (!empty($furnishing)) $active_filters['furnishing'] = 'Furnishing: ' . ucwords(str_replace('-', ' ', $furnishing));
                if (!empty($status)) $active_filters['status'] = 'Status: ' . ucwords(str_replace('_', ' ', $status));
                if (!empty($verified)) $active_filters['verified'] = 'RERA Verified Only';
            ?>

            <?php if (!empty($active_filters)): ?>
                <div class="active-filter-chips">
                    <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--gold-400); font-weight: 700;">
                        Active Filters:
                    </span>
                    <?php foreach ($active_filters as $key => $label): ?>
                        <span class="filter-chip">
                            <?= e($label) ?>
                            <a href="<?= build_query_url([$key => null, 'page' => 1]) ?>" title="Remove filter" aria-label="Remove filter">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        </span>
                    <?php endforeach; ?>
                    <a href="properties.php" class="filter-chip-clear-all">Reset All</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- =========================================================================
         Main Listing Section (Sidebar + Results)
         ========================================================================= -->
    <section class="section section-beige" style="padding-top: 2rem;">
        <div class="container">
            <!-- Mobile Filter Trigger Bar -->
            <div class="mobile-filter-bar">
                <button type="button" class="btn btn-navy btn-sm" id="mobileFilterTrigger" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="4" y1="21" x2="4" y2="14"></line>
                        <line x1="4" y1="10" x2="4" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12" y2="3"></line>
                        <line x1="20" y1="21" x2="20" y2="16"></line>
                        <line x1="20" y1="12" x2="20" y2="3"></line>
                        <line x1="1" y1="14" x2="7" y2="14"></line>
                        <line x1="9" y1="8" x2="15" y2="8"></line>
                        <line x1="17" y1="16" x2="23" y2="16"></line>
                    </svg>
                    <span>Filter Properties (<?= count($active_filters) ?> Active)</span>
                </button>
                <div style="font-size: var(--text-sm); font-weight: 600; color: var(--navy-900);">
                    <?= $total_count ?> Residences
                </div>
            </div>

            <div class="listing-layout">
                <!-- =============================================================
                     1. Filter Sidebar Panel
                     ============================================================= -->
                <aside class="filter-sidebar" id="filterSidebar">
                    <form method="GET" action="properties.php" id="advancedFilterForm">
                        <!-- Preserve view and sort -->
                        <input type="hidden" name="view" value="<?= e($view) ?>">
                        <input type="hidden" name="sort" value="<?= e($sort) ?>">

                        <div class="filter-sidebar-header">
                            <h2 class="filter-sidebar-title">
                                <i class="fa-solid fa-sliders"></i>
                                <span>Refine Search</span>
                            </h2>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <a href="properties.php" class="filter-reset-link">Reset</a>
                                <button type="button" class="mobile-nav-close" id="closeFilterDrawerBtn" style="display: none; font-size: 1.25rem;">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>

                        <!-- 1. Purpose (Buy / Rent / Commercial) -->
                        <div class="filter-group">
                            <label class="filter-group-label">Transaction Purpose</label>
                            <input type="hidden" name="purpose" id="filter_purpose" value="<?= e($purpose) ?>">
                            <div class="purpose-pill-selector">
                                <div class="purpose-pill-btn <?= empty($purpose) ? 'active' : '' ?>" data-purpose="">
                                    All
                                </div>
                                <div class="purpose-pill-btn <?= ($purpose === 'buy') ? 'active' : '' ?>" data-purpose="buy">
                                    Buy
                                </div>
                                <div class="purpose-pill-btn <?= ($purpose === 'rent') ? 'active' : '' ?>" data-purpose="rent">
                                    Rent
                                </div>
                                <div class="purpose-pill-btn <?= ($purpose === 'commercial') ? 'active' : '' ?>" data-purpose="commercial">
                                    Commercial
                                </div>
                            </div>
                        </div>

                        <!-- 2. City -->
                        <div class="filter-group">
                            <label class="filter-group-label" for="filter_city">City / Metro</label>
                            <select name="city" id="filter_city" class="form-control" style="cursor: pointer;">
                                <option value="all">All Prime Cities</option>
                                <?php foreach ($cities_list as $c): ?>
                                    <option value="<?= e($c['slug']) ?>" <?= (strtolower($city) === strtolower($c['slug'])) ? 'selected' : '' ?>>
                                        <?= e($c['name']) ?> (<?= e($c['state']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- 3. Locality -->
                        <div class="filter-group">
                            <label class="filter-group-label" for="filter_locality">Locality / Address</label>
                            <div style="position: relative;">
                                <input type="text" name="locality" id="filter_locality" class="form-control" placeholder="e.g. Worli, Golf Course Rd, Assagao..." value="<?= e($locality) ?>">
                                <i class="fa-solid fa-magnifying-glass" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--slate-400); font-size: 0.85rem;"></i>
                            </div>
                        </div>

                        <!-- 4. Property Type -->
                        <div class="filter-group">
                            <label class="filter-group-label" for="filter_type">Property Type</label>
                            <select name="type" id="filter_type" class="form-control" style="cursor: pointer;">
                                <option value="all">All Architectural Types</option>
                                <option value="sky-penthouses" <?= ($type === 'sky-penthouses') ? 'selected' : '' ?>>Sky Penthouses</option>
                                <option value="villas" <?= ($type === 'villas') ? 'selected' : '' ?>>Luxury Villas & Estates</option>
                                <option value="apartments" <?= ($type === 'apartments') ? 'selected' : '' ?>>Luxury Apartments</option>
                                <option value="sea-facing" <?= ($type === 'sea-facing') ? 'selected' : '' ?>>Sea-Facing Suites</option>
                                <option value="heritage-mansions" <?= ($type === 'heritage-mansions') ? 'selected' : '' ?>>Heritage Mansions</option>
                                <option value="plots" <?= ($type === 'plots') ? 'selected' : '' ?>>Estate Plots / Land</option>
                                <option value="commercial" <?= ($type === 'commercial') ? 'selected' : '' ?>>Commercial Corporate Suites</option>
                            </select>
                        </div>

                        <!-- 5. BHK Configuration -->
                        <div class="filter-group">
                            <label class="filter-group-label">BHK Configuration</label>
                            <div class="bhk-pill-grid">
                                <?php 
                                    $bhk_options = ['1' => '1 BHK', '2' => '2 BHK', '3' => '3 BHK', '4' => '4 BHK', '5' => '5+ BHK'];
                                    foreach ($bhk_options as $bhk_val => $bhk_lbl): 
                                        $is_bhk_checked = in_array($bhk_val, (array)$bhk);
                                ?>
                                    <label class="bhk-checkbox-label">
                                        <input type="checkbox" name="bhk[]" value="<?= $bhk_val ?>" <?= $is_bhk_checked ? 'checked' : '' ?>>
                                        <span class="bhk-pill"><?= $bhk_lbl ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 6. Price Range in INR -->
                        <div class="filter-group">
                            <label class="filter-group-label">Price Range (₹ INR)</label>
                            <div class="min-max-inputs">
                                <div class="min-max-field">
                                    <span>Min Price (₹)</span>
                                    <input type="number" name="min_price" placeholder="Min ₹" value="<?= e($min_price) ?>" step="500000">
                                </div>
                                <div class="min-max-field">
                                    <span>Max Price (₹)</span>
                                    <input type="number" name="max_price" placeholder="Max ₹" value="<?= e($max_price) ?>" step="1000000">
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.35rem; margin-top: 0.6rem; flex-wrap: wrap;">
                                <button type="button" class="btn btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.7rem; background: var(--beige-100); border: 1px solid var(--beige-300);" onclick="document.getElementsByName('min_price')[0].value='50000000'; document.getElementsByName('max_price')[0].value='150000000';">
                                    ₹5 Cr–₹15 Cr
                                </button>
                                <button type="button" class="btn btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.7rem; background: var(--beige-100); border: 1px solid var(--beige-300);" onclick="document.getElementsByName('min_price')[0].value='150000000'; document.getElementsByName('max_price')[0].value='300000000';">
                                    ₹15 Cr–₹30 Cr
                                </button>
                                <button type="button" class="btn btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.7rem; background: var(--beige-100); border: 1px solid var(--beige-300);" onclick="document.getElementsByName('min_price')[0].value='300000000'; document.getElementsByName('max_price')[0].value='';">
                                    ₹30 Cr+
                                </button>
                            </div>
                        </div>

                        <!-- 7. Area Range in Sq.Ft -->
                        <div class="filter-group">
                            <label class="filter-group-label">Carpet Area Range (Sq.Ft)</label>
                            <div class="min-max-inputs">
                                <div class="min-max-field">
                                    <span>Min Sq.Ft</span>
                                    <input type="number" name="min_area" placeholder="e.g. 2000" value="<?= e($min_area) ?>" step="250">
                                </div>
                                <div class="min-max-field">
                                    <span>Max Sq.Ft</span>
                                    <input type="number" name="max_area" placeholder="e.g. 10000" value="<?= e($max_area) ?>" step="500">
                                </div>
                            </div>
                        </div>

                        <!-- 8. Furnishing Status -->
                        <div class="filter-group">
                            <label class="filter-group-label">Furnishing Status</label>
                            <label class="filter-option">
                                <input type="radio" name="furnishing" value="" <?= empty($furnishing) ? 'checked' : '' ?>>
                                <span>All Furnishing</span>
                            </label>
                            <label class="filter-option">
                                <input type="radio" name="furnishing" value="bespoke-furnished" <?= ($furnishing === 'bespoke-furnished') ? 'checked' : '' ?>>
                                <span>Bespoke Designer Furnished</span>
                            </label>
                            <label class="filter-option">
                                <input type="radio" name="furnishing" value="semi-furnished" <?= ($furnishing === 'semi-furnished') ? 'checked' : '' ?>>
                                <span>Semi-Furnished</span>
                            </label>
                            <label class="filter-option">
                                <input type="radio" name="furnishing" value="unfurnished" <?= ($furnishing === 'unfurnished') ? 'checked' : '' ?>>
                                <span>Bare Shell / Unfurnished</span>
                            </label>
                        </div>

                        <!-- 9. Ready to Move / Under Construction -->
                        <div class="filter-group">
                            <label class="filter-group-label">Construction Status</label>
                            <label class="filter-option">
                                <input type="radio" name="status" value="" <?= empty($status) ? 'checked' : '' ?>>
                                <span>All Statuses</span>
                            </label>
                            <label class="filter-option">
                                <input type="radio" name="status" value="ready_to_move" <?= ($status === 'ready_to_move') ? 'checked' : '' ?>>
                                <span>Ready to Move / Immediate</span>
                            </label>
                            <label class="filter-option">
                                <input type="radio" name="status" value="under_construction" <?= ($status === 'under_construction') ? 'checked' : '' ?>>
                                <span>Under Construction</span>
                            </label>
                        </div>

                        <!-- 10. Possession Timeline -->
                        <div class="filter-group">
                            <label class="filter-group-label" for="filter_possession">Possession Timeline</label>
                            <select name="possession" id="filter_possession" class="form-control" style="cursor: pointer;">
                                <option value="all">Any Possession Date</option>
                                <option value="Immediate" <?= ($possession === 'Immediate') ? 'selected' : '' ?>>Immediate Possession</option>
                                <option value="2026" <?= ($possession === '2026') ? 'selected' : '' ?>>Within 2026</option>
                                <option value="2027" <?= ($possession === '2027') ? 'selected' : '' ?>>2027 Onwards</option>
                            </select>
                        </div>

                        <!-- 11. Verified Properties Only -->
                        <div class="filter-group">
                            <label class="toggle-switch-label">
                                <span style="display: flex; align-items: center; gap: 0.4rem;">
                                    <i class="fa-solid fa-circle-check" style="color: #10B981;"></i> RERA Verified Only
                                </span>
                                <div class="toggle-switch">
                                    <input type="checkbox" name="verified" value="1" <?= (!empty($verified)) ? 'checked' : '' ?>>
                                    <span class="slider-round"></span>
                                </div>
                            </label>
                        </div>

                        <!-- Submit & Reset Buttons -->
                        <div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem;">
                            <button type="submit" class="btn btn-gold" style="width: 100%;">
                                <i class="fa-solid fa-check"></i>
                                <span>Apply Filters</span>
                            </button>
                            <a href="properties.php" class="btn btn-white" style="width: 100%; text-align: center; border: 1px solid var(--beige-300);">
                                <span>Reset All Filters</span>
                            </a>
                        </div>
                    </form>
                </aside>

                <!-- =============================================================
                     2. Results & Toolbar Column
                     ============================================================= -->
                <div class="listing-results-column">
                    <!-- Toolbar (Counts, Sorting, View Toggle) -->
                    <div class="listing-toolbar">
                        <div class="results-count-text">
                            Showing <strong><?= count($properties) ?></strong> of <strong><?= $total_count ?></strong> Properties
                        </div>

                        <div class="toolbar-controls">
                            <!-- Sorting -->
                            <div class="sorting-wrapper">
                                <span>Sort By:</span>
                                <select id="sortSelect" class="sort-select" aria-label="Sort Properties">
                                    <option value="relevance" <?= ($sort === 'relevance') ? 'selected' : '' ?>>Relevance & Featured</option>
                                    <option value="price_asc" <?= ($sort === 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
                                    <option value="price_desc" <?= ($sort === 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
                                    <option value="newest" <?= ($sort === 'newest') ? 'selected' : '' ?>>Newest First</option>
                                </select>
                            </div>

                            <!-- View Toggle (Grid / List) -->
                            <div class="view-toggle-group">
                                <button type="button" class="view-toggle-btn <?= ($view === 'grid') ? 'active' : '' ?>" data-view="grid" title="Grid View" aria-label="Switch to grid view">
                                    <i class="fa-solid fa-table-cells-large"></i>
                                </button>
                                <button type="button" class="view-toggle-btn <?= ($view === 'list') ? 'active' : '' ?>" data-view="list" title="List View" aria-label="Switch to list view">
                                    <i class="fa-solid fa-list-ul"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Properties Grid / List Container -->
                    <?php if (!empty($properties)): ?>
                        <div class="properties-container <?= ($view === 'list') ? 'list-view' : 'grid-view' ?>" id="propertiesListingContainer">
                            <?php foreach ($properties as $property): ?>
                                <?php include __DIR__ . '/includes/property-card.php'; ?>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages_count > 1): ?>
                            <div class="pagination-container">
                                <!-- Previous Button -->
                                <a href="<?= ($current_page_idx > 1) ? build_query_url(['page' => $current_page_idx - 1]) : 'javascript:void(0)' ?>" 
                                   class="page-btn <?= ($current_page_idx <= 1) ? 'disabled' : '' ?>" 
                                   aria-label="Previous Page">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>

                                <!-- Page Numbers -->
                                <?php for ($p = 1; $p <= $total_pages_count; $p++): ?>
                                    <a href="<?= build_query_url(['page' => $p]) ?>" 
                                       class="page-btn <?= ($p === $current_page_idx) ? 'active' : '' ?>">
                                        <?= $p ?>
                                    </a>
                                <?php endfor; ?>

                                <!-- Next Button -->
                                <a href="<?= ($current_page_idx < $total_pages_count) ? build_query_url(['page' => $current_page_idx + 1]) : 'javascript:void(0)' ?>" 
                                   class="page-btn <?= ($current_page_idx >= $total_pages_count) ? 'disabled' : '' ?>" 
                                   aria-label="Next Page">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Empty State when no results found -->
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="fa-solid fa-house-chimney-crack"></i>
                            </div>
                            <h3 class="empty-state-title">No Residences Match Your Exact Criteria</h3>
                            <p class="empty-state-desc">
                                We could not find properties matching your current filter configuration. Try expanding your price bracket, selecting alternative BHK configurations, or reset your filters.
                            </p>
                            <a href="properties.php" class="btn btn-gold btn-lg">
                                <i class="fa-solid fa-rotate-left"></i>
                                <span>Reset All Filters</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
// Include Footer & Closing scripts
require_once __DIR__ . '/includes/footer.php';
?>

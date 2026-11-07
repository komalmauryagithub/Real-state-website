<?php
/**
 * EstateVista India - Custom Luxury 404 Error Experience
 */
http_response_code(404);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "404 - Residence Not Found";
$page_desc = "The architectural dossier, private estate, or page you are attempting to access is no longer active or has been moved.";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="page-404-wrapper" style="min-height: 75vh; display: flex; align-items: center; justify-content: center; background: radial-gradient(circle at 50% 20%, #152238 0%, #0B1528 100%); color: var(--white); padding: 5rem 1.5rem; position: relative; overflow: hidden;">
    <!-- Subtle Architectural Ambient Watermark -->
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-family: var(--font-heading); font-size: clamp(10rem, 25vw, 24rem); font-weight: 700; color: rgba(197, 168, 128, 0.03); pointer-events: none; z-index: 0; line-height: 1; user-select: none;">
        404
    </div>

    <div class="container" style="position: relative; z-index: 1; text-align: center; max-width: 760px; margin: 0 auto;">
        <!-- Luxury Emblem Crest -->
        <div style="width: 74px; height: 74px; margin: 0 auto 1.75rem; border-radius: 50%; background: rgba(197, 168, 128, 0.12); border: 1px solid rgba(197, 168, 128, 0.35); display: flex; align-items: center; justify-content: center; color: var(--gold-400); font-size: 2rem; box-shadow: 0 0 35px rgba(197, 168, 128, 0.15);">
            <i class="fa-solid fa-compass-drafting"></i>
        </div>

        <!-- Subtitle Badge -->
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(197, 168, 128, 0.25); border-radius: 50px; padding: 0.4rem 1.25rem; margin-bottom: 1.5rem;">
            <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--gold-400);"></span>
            <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.14em; color: var(--gold-300); font-weight: 600;">
                Dossier Unreachable &bull; Code 404
            </span>
        </div>

        <h1 style="font-family: var(--font-heading); font-size: clamp(2.2rem, 5vw, 3.25rem); font-weight: 600; line-height: 1.2; margin-bottom: 1.25rem; color: var(--white);">
            The Residence You Seek Is Not Listed
        </h1>

        <p style="font-size: 1.05rem; color: var(--slate-300); line-height: 1.7; margin-bottom: 2.5rem; max-width: 620px; margin-left: auto; margin-right: auto;">
            The architectural estate, sky penthouse, or advisory dossier you are trying to view might have been privately acquired under non-disclosure, repositioned, or the address URL was miskeyed.
        </p>

        <!-- Quick Search Bar -->
        <form action="<?= BASE_URL ?>properties.php" method="GET" style="display: flex; max-width: 540px; margin: 0 auto 2.5rem; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(197, 168, 128, 0.3); border-radius: 50px; padding: 0.35rem 0.4rem; backdrop-filter: blur(8px);">
            <div style="display: flex; align-items: center; padding-left: 1.25rem; color: var(--gold-400);">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <input type="text" name="locality" placeholder="Search by locality, city, or landmark..." style="flex: 1; background: transparent; border: none; padding: 0.75rem 1rem; color: var(--white); font-size: 0.95rem; outline: none;" aria-label="Search properties">
            <button type="submit" class="btn btn-gold btn-sm" style="border-radius: 50px; padding: 0.65rem 1.4rem;">
                Search
            </button>
        </form>

        <!-- Action Links -->
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 3rem;">
            <a href="<?= BASE_URL ?>" class="btn btn-gold">
                <i class="fa-solid fa-house"></i>
                <span>Return to Homepage</span>
            </a>
            <a href="<?= BASE_URL ?>properties.php" class="btn btn-outline" style="color: var(--white); border-color: rgba(255, 255, 255, 0.25);">
                <i class="fa-solid fa-list-ul"></i>
                <span>Browse Curated Portfolio</span>
            </a>
            <a href="javascript:void(0)" class="btn btn-white open-inquiry-modal" data-subject="Private Residence Inquiry (404 Desk)">
                <i class="fa-solid fa-headset"></i>
                <span>Speak to Private Concierge</span>
            </a>
        </div>

        <!-- Curated Direct Destinations -->
        <div style="border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 2rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--gold-400); margin-bottom: 1rem; font-weight: 600;">
                Explore Prime Regional Portfolios
            </div>
            <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
                <a href="<?= BASE_URL ?>properties.php?city=mumbai" class="filter-chip">
                    <i class="fa-solid fa-building" style="color: var(--gold-400);"></i> Mumbai Coastline
                </a>
                <a href="<?= BASE_URL ?>properties.php?city=delhi-ncr" class="filter-chip">
                    <i class="fa-solid fa-flag" style="color: var(--gold-400);"></i> Delhi NCR Golf Estates
                </a>
                <a href="<?= BASE_URL ?>properties.php?city=bengaluru" class="filter-chip">
                    <i class="fa-solid fa-tree" style="color: var(--gold-400);"></i> Bengaluru Mansions
                </a>
                <a href="<?= BASE_URL ?>properties.php?city=goa" class="filter-chip">
                    <i class="fa-solid fa-water" style="color: var(--gold-400);"></i> Goa Oceanfront Villas
                </a>
                <a href="<?= BASE_URL ?>properties.php?city=hyderabad" class="filter-chip">
                    <i class="fa-solid fa-gem" style="color: var(--gold-400);"></i> Hyderabad Regal Hills
                </a>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

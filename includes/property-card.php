<?php
/**
 * Reusable Property Card Component with Gallery Previews
 * EstateVista India
 * 
 * Expected $property array with keys:
 * - id, title, slug, subtitle, price, price_display, bhk, bathrooms, carpet_area_sqft,
 * - locality, city_name, category_name, category_slug, rera_number, cover_image, is_exclusive, is_verified, is_featured
 */

if (!isset($property) || !is_array($property)) {
    return;
}

$p_id = $property['id'] ?? 0;
$p_title = $property['title'] ?? 'Exclusive Residence';
$p_slug = $property['slug'] ?? 'property-' . $p_id;
$p_locality = $property['locality'] ?? '';
$p_city = $property['city_name'] ?? 'India';
$p_price = !empty($property['price_display']) ? $property['price_display'] : (isset($property['price']) ? format_inr_price($property['price']) : 'Price on Request');
$p_bhk = (int)($property['bhk'] ?? 0);
$p_baths = (int)($property['bathrooms'] ?? 0);
$p_area = isset($property['carpet_area_sqft']) ? format_inr_number($property['carpet_area_sqft']) : '5,000';
$p_category = $property['category_name'] ?? 'Luxury Estate';
$p_cat_slug = $property['category_slug'] ?? 'all';
$p_rera = $property['rera_number'] ?? 'RERA Registered';
$is_exclusive = !empty($property['is_exclusive']);
$is_featured = !empty($property['is_featured']);
$is_verified = !isset($property['is_verified']) || !empty($property['is_verified']);

// Retrieve gallery images (array of 3-4 photos)
$gallery_imgs = get_property_gallery_images($property);
?>

<article class="property-card" data-category="<?= e($p_cat_slug) ?>" data-city="<?= strtolower(e($p_city)) ?>" data-price="<?= (int)($property['price'] ?? 0) ?>" id="property-card-<?= $p_id ?>">
    <!-- Thumbnail & Gallery Preview Carousel -->
    <div class="property-thumb-wrapper">
        <div class="gallery-slider" data-card-id="<?= $p_id ?>">
            <div class="gallery-track">
                <?php foreach ($gallery_imgs as $idx => $img_url): ?>
                    <div class="gallery-slide">
                        <img src="<?= e($img_url) ?>" alt="<?= e($p_title) ?> - Photo <?= $idx + 1 ?>" class="property-thumb" loading="lazy" decoding="async" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/property-placeholder.svg';">
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($gallery_imgs) > 1): ?>
                <button type="button" class="gallery-arrow prev" aria-label="Previous photo">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button type="button" class="gallery-arrow next" aria-label="Next photo">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
                <div class="gallery-dots">
                    <?php for ($i = 0; $i < count($gallery_imgs); $i++): ?>
                        <span class="gallery-dot <?= ($i === 0) ? 'active' : '' ?>"></span>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Badges -->
        <div class="property-badges">
            <?php if ($is_exclusive): ?>
                <span class="badge badge-exclusive">
                    <i class="fa-solid fa-crown"></i> Exclusive
                </span>
            <?php elseif ($is_featured): ?>
                <span class="badge badge-featured">
                    <i class="fa-solid fa-star"></i> Featured
                </span>
            <?php endif; ?>
            <span class="badge" style="background: rgba(11,21,40,0.85); color: var(--gold-300); border: 1px solid rgba(197,168,128,0.3);">
                <?= e($p_category) ?>
            </span>
        </div>

        <?php if ($is_verified): ?>
            <span class="badge-rera" title="<?= e($p_rera) ?>">
                <i class="fa-solid fa-circle-check"></i> RERA Verified
            </span>
        <?php endif; ?>

        <!-- Favorite Button -->
        <button type="button" class="property-fav-btn" data-property-id="<?= $p_id ?>" title="Add to Shortlist" aria-label="Shortlist residence">
            <i class="fa-regular fa-heart"></i>
        </button>
    </div>

    <!-- Body Information -->
    <div class="property-body">
        <div class="property-category"><?= e($p_category) ?></div>
        
        <h3 class="property-title">
            <a href="<?= BASE_URL ?>property-detail.php?slug=<?= e($p_slug) ?>" class="property-title-link">
                <?= e($p_title) ?>
            </a>
        </h3>

        <div class="property-location">
            <i class="fa-solid fa-location-dot"></i>
            <span><?= e($p_locality) ?>, <?= e($p_city) ?></span>
        </div>

        <!-- Specifications -->
        <div class="property-specs">
            <?php if ($p_bhk > 0): ?>
                <div class="spec-item" title="<?= $p_bhk ?> Bedrooms">
                    <i class="fa-solid fa-bed"></i>
                    <span><?= $p_bhk ?> BHK</span>
                </div>
            <?php else: ?>
                <div class="spec-item" title="Commercial / Estate Space">
                    <i class="fa-solid fa-building"></i>
                    <span>Grade-A</span>
                </div>
            <?php endif; ?>

            <?php if ($p_baths > 0): ?>
                <div class="spec-item" title="<?= $p_baths ?> En-suite Bathrooms">
                    <i class="fa-solid fa-bath"></i>
                    <span><?= $p_baths ?> Baths</span>
                </div>
            <?php else: ?>
                <div class="spec-item" title="Private Restrooms">
                    <i class="fa-solid fa-restroom"></i>
                    <span>Executive</span>
                </div>
            <?php endif; ?>

            <div class="spec-item" title="Carpet Area in Square Feet">
                <i class="fa-solid fa-vector-square"></i>
                <span><?= $p_area ?> sq.ft</span>
            </div>
        </div>

        <!-- Footer & Action -->
        <div class="property-footer">
            <div class="property-price">
                <span class="price-label"><?= (isset($property['purpose']) && $property['purpose'] === 'rent') ? 'Monthly Rental' : 'Investment' ?></span>
                <span class="price-value"><?= e($p_price) ?></span>
            </div>

            <a href="<?= BASE_URL ?>property-detail.php?slug=<?= e($p_slug) ?>" class="property-view-btn">
                <span>View Details</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</article>

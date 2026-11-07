-- Phase 3: Comprehensive Property Listing & Filter Dataset
USE `estatevista_db`;

-- Update or insert Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon_class`, `display_order`) VALUES
(1, 'Sky Penthouses', 'sky-penthouses', 'Multi-level high-rise residences with panoramic views & private decks', 'fa-crown', 1),
(2, 'Luxury Villas', 'villas', 'Expansive gated estate villas with private lawns & curated landscapes', 'fa-house-chimney-window', 2),
(3, 'Sea-Facing Suites', 'sea-facing', 'Unobstructed Arabian Sea & coastline panoramic residences', 'fa-water', 3),
(4, 'Heritage Mansions', 'heritage-mansions', 'Architecturally distinguished colonial, royal & bespoke estates', 'fa-landmark-dome', 4),
(5, 'Luxury Apartments', 'apartments', 'High-rise luxury suites with five-star clubhouses', 'fa-building', 5),
(6, 'Estate Plots', 'plots', 'Freehold residential estate land with clear titles', 'fa-draw-polygon', 6),
(7, 'Commercial Spaces', 'commercial', 'Grade-A LEED certified corporate towers & flagship retail', 'fa-briefcase', 7)
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `slug` = VALUES(`slug`),
    `description` = VALUES(`description`),
    `icon_class` = VALUES(`icon_class`);

-- Update existing 6 properties with gallery_images and purpose
UPDATE `properties` SET 
    `purpose` = 'buy',
    `gallery_images` = '["https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80"]'
WHERE `id` = 1;

UPDATE `properties` SET 
    `purpose` = 'buy',
    `gallery_images` = '["https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80"]'
WHERE `id` = 2;

UPDATE `properties` SET 
    `purpose` = 'buy',
    `gallery_images` = '["https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1512343879784-a960bf40e7f2?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80"]'
WHERE `id` = 3;

UPDATE `properties` SET 
    `purpose` = 'buy',
    `gallery_images` = '["https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1200&q=80"]'
WHERE `id` = 4;

UPDATE `properties` SET 
    `purpose` = 'buy',
    `gallery_images` = '["https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80"]'
WHERE `id` = 5;

UPDATE `properties` SET 
    `purpose` = 'buy',
    `gallery_images` = '["https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80"]'
WHERE `id` = 6;

-- Add rich diverse properties for Buy, Rent, and Commercial across all cities
INSERT INTO `properties` (
    `id`, `title`, `slug`, `subtitle`, `description`, 
    `category_id`, `city_id`, `developer_id`, `locality`, `address`, 
    `price`, `price_display`, `price_type`, `purpose`, `bhk`, `bathrooms`, 
    `carpet_area_sqft`, `builtup_area_sqft`, `floor_number`, `total_floors`, 
    `furnishing`, `status`, `possession_date`, `rera_number`, 
    `is_featured`, `is_exclusive`, `is_verified`, 
    `cover_image`, `gallery_images`, `virtual_tour_url`, `views_count`
) VALUES
-- 7. High-End Rental in Bandra, Mumbai
(
    7,
    'The Horizon Sky Villa on Pali Hill',
    'the-horizon-sky-villa-pali-hill',
    'Fully Furnished 4 BHK Penthouse with Private Plunge Pool & Green Vistas',
    'A bespoke duplex residence nestled in the serene, leafy canopy of Pali Hill. Features double-height living room, private terrace garden with jacuzzi, Italian marble flooring, and dedicated concierge.',
    1, 1, 2, 'Pali Hill, Bandra West', 'Pali Hill Promenade, Bandra, Mumbai 400050',
    650000.00, '₹ 6.50 Lakh / mo', 'lease', 'rent', 4, 5, 4200, 5200, '14 & 15', 16,
    'bespoke-furnished', 'ready_to_move', 'Immediate Possession', 'PR/MH/RERA/2023/007412',
    1, 1, 1,
    'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80',
    '["https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80"]',
    '#virtual-tour', 280
),
-- 8. High-End Rental on Golf Course Road, Gurugram
(
    8,
    'The Fairway Penthouse at The Magnolias',
    'fairway-penthouse-the-magnolias',
    'Panoramic Golf-Facing 5 BHK Sky Suite with Private Elevator Vestibule',
    'Overlooking the 18-hole Gary Player championship course, this fully automated residence features Sub-Zero appliances, separate service quarters, acoustic glazing, and private Olympic club membership.',
    1, 2, 1, 'Golf Course Road', 'DLF Phase 5, Gurugram 122002',
    800000.00, '₹ 8.00 Lakh / mo', 'lease', 'rent', 5, 6, 6800, 8400, '21st Floor', 25,
    'bespoke-furnished', 'ready_to_move', 'Immediate Possession', 'HRERA-PKL-GGM-114-2022',
    1, 0, 1,
    'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80',
    '["https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80"]',
    '#virtual-tour', 340
),
-- 9. Commercial Headquarters at BKC, Mumbai
(
    9,
    'The Sovereign Corporate Floor at BKC',
    'sovereign-corporate-floor-bkc',
    'Full Floor Grade-A LEED Platinum Corporate Suite in G-Block',
    'High-efficiency column-free floor plate tailored for private equity, investment banks, or multinational headquarters. Features private executive boardroom, high-speed destination elevators, and 100% DG backup.',
    7, 1, 3, 'Bandra Kurla Complex (BKC)', 'G-Block, BKC, Bandra East, Mumbai 400051',
    420000000.00, '₹ 42.00 Cr', 'sale', 'commercial', 0, 8, 12500, 15000, '9th Floor', 18,
    'unfurnished', 'ready_to_move', 'Immediate Possession', 'P51800001894',
    1, 1, 1,
    'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80',
    '["https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80"]',
    '#virtual-tour', 410
),
-- 10. Commercial Office Suite at Cyber City, Gurugram
(
    10,
    'Horizon Signature Suites on Golf Course Rd',
    'horizon-signature-suites-gurugram',
    'Furnished Grade-A Commercial Penthouse Suite for Family Office',
    'Pre-fitted with Italian marble executive desks, acoustic phone booths, private pantry, and 12-seater video conference room in DLF Horizon complex.',
    7, 2, 1, 'Golf Course Road, Gurugram', 'DLF Horizon Center, DLF Phase 5, Gurugram 122002',
    185000000.00, '₹ 18.50 Cr', 'sale', 'commercial', 0, 4, 5200, 6800, '16th Floor', 24,
    'bespoke-furnished', 'ready_to_move', 'Immediate Possession', 'HRERA-GGM-412-2021',
    0, 1, 1,
    'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80',
    '["https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80"]',
    '#virtual-tour', 190
),
-- 11. Luxury Apartment in Koregaon Park, Pune
(
    11,
    'The Verdant Sky Mansion at Koregaon Park',
    'the-verdant-sky-mansion-koregaon-park',
    'Exclusive 4 BHK Single-Floor Residence Overlooking Osho Teerth Park',
    'Sprawling floor layout with wraparound wooden sun decks, smart home automation, imported German modular kitchen, and private temperature-controlled wine cellar.',
    5, 6, 2, 'Koregaon Park', 'Lane 6, Koregaon Park, Pune 411001',
    115000000.00, '₹ 11.50 Cr', 'sale', 'buy', 4, 4, 3850, 4600, '8th Floor', 12,
    'bespoke-furnished', 'ready_to_move', 'Immediate Possession', 'P52100018492',
    1, 0, 1,
    'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80',
    '["https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80"]',
    '#virtual-tour', 230
),
-- 12. Gated Estate Plot in North Goa
(
    12,
    'The Palm Grove Sanctuaries Freehold Estate Plot',
    'palm-grove-sanctuaries-plot-goa',
    'Clear Title 1.5 Acre Gated Plot with Sanad Conversion & Mature Coconut Palms',
    'Rare freehold residential plot nestled in the design enclave of Badem, Assagao. Fully walled with municipal water, private borewell, and complete NOC clearances for a bespoke private villa.',
    6, 4, 4, 'Assagao / Badem', 'Badem Ridge, North Goa 403507',
    145000000.00, '₹ 14.50 Cr', 'sale', 'buy', 0, 0, 65340, 65340, 'Ground', 1,
    'unfurnished', 'ready_to_move', 'Immediate Possession', 'PRGO04221890',
    1, 1, 1,
    'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200&q=80',
    '["https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1200&q=80"]',
    '#virtual-tour', 310
),
-- 13. Under Construction Luxury Apartment in Indiranagar, Bengaluru
(
    13,
    'The Botanique Residences at Indiranagar',
    'the-botanique-residences-indiranagar',
    'Ultra-Contemporary 3 BHK Terrace Apartment with Private Vertical Garden',
    'Designed by award-winning architectural atelier, featuring exposed concrete finishes, cantilevered brass balconies, and energy-positive solar glass facade. Possession by March 2027.',
    5, 3, 4, '100 Feet Road, Indiranagar', 'Indiranagar Stage 2, Bengaluru 560038',
    82500000.00, '₹ 8.25 Cr', 'sale', 'buy', 3, 3, 2950, 3600, '4th Floor', 8,
    'semi-furnished', 'under_construction', 'March 2027', 'PRM/KA/RERA/1251/00892',
    0, 0, 1,
    'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80',
    '["https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80"]',
    '#virtual-tour', 175
),
-- 14. Luxury Villa in Kokapet / Financial District, Hyderabad
(
    14,
    'The Summit Golf Villa at Kokapet',
    'the-summit-golf-villa-kokapet',
    '5 BHK Triplex Estate with Private Glass Elevator & Subterranean Home Theatre',
    'Positioned within a 45-acre master-planned gated enclave. Double-height grand foyer, Italian Travertine facade, bespoke home automation, and heated plunge pool overlooking the landscaped ravine.',
    2, 5, 5, 'Golden Mile, Kokapet', 'Kokapet Financial District, Hyderabad 500075',
    168000000.00, '₹ 16.80 Cr', 'sale', 'buy', 5, 6, 7200, 8900, 'G+2 Triplex', 3,
    'bespoke-furnished', 'under_construction', 'December 2026', 'P02400007812',
    1, 1, 1,
    'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80',
    '["https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80", "https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=1200&q=80"]',
    '#virtual-tour', 245
)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

<?php
/**
 * EstateVista India - Curated Luxury Real Estate Demo & Fallback Datasets
 * Guarantees 100% visual richness, high-resolution imagery, and interactive demo
 * capabilities even when an external MySQL database is not connected.
 */

if (defined('ESTATEVISTA_FALLBACK_DATA_LOADED')) {
    return;
}
define('ESTATEVISTA_FALLBACK_DATA_LOADED', true);

/**
 * Return 12 ultra-luxury Indian flagship properties
 */
function get_fallback_properties(): array {
    return [
        [
            'id' => 1,
            'title' => 'The Sky Crest Penthouse at Worli Sea Face',
            'slug' => 'the-sky-crest-penthouse-worli-sea-face',
            'subtitle' => 'Triplex Penthouse with Private Helipad & Infinity Pool over Arabian Sea',
            'description' => 'Commanding panoramic, unobstructed 270-degree vistas of the Arabian Sea and the Bandra-Worli Sea Link, The Sky Crest represents the pinnacle of luxury living in South Mumbai. Spread over 3 expansive levels with 24-ft double-height living spaces, private internal elevator, temperature-controlled infinity pool on the upper deck, and master suite occupying an entire floor.',
            'price' => 850000000.0,
            'price_display' => '₹ 85.00 Cr',
            'purpose' => 'buy',
            'category_id' => 1,
            'category_name' => 'Sky Penthouses',
            'category_slug' => 'penthouses',
            'city_id' => 1,
            'city_name' => 'Mumbai',
            'city_slug' => 'mumbai',
            'city_state' => 'Maharashtra',
            'locality' => 'Worli Sea Face',
            'address' => 'Skyline Tower 1, Worli Sea Face, Mumbai 400018',
            'bhk' => 5,
            'bedrooms' => 5,
            'bathrooms' => 6,
            'balconies' => 4,
            'carpet_area_sqft' => 8500,
            'builtup_area_sqft' => 11200,
            'floor_number' => '48th to 50th Floor',
            'total_floors' => 50,
            'furnishing' => 'Ultra-Luxury Furnished',
            'facing' => 'West (Arabian Sea)',
            'parking_spaces' => 6,
            'status' => 'Ready to Move',
            'possession_date' => 'Immediate',
            'rera_number' => 'MAHARERA: P51900008341',
            'is_featured' => 1,
            'is_exclusive' => 1,
            'is_verified' => 1,
            'approval_status' => 'approved',
            'cover_image' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1600&q=80',
            'floor_plan_image' => 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=80',
            'gallery_images' => json_encode([
                'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1600&q=80'
            ]),
            'agent_name' => 'Aditya Vardhan Birla',
            'agent_title' => 'Senior Partner - Ultra-Luxury Acquisitions',
            'agent_phone' => '+91 22 6985 4010',
            'agent_email' => 'aditya.birla@estatevistaindia.com',
            'agent_avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80',
            'agent_experience' => '16+ Years in Prime Real Estate',
            'developer_name' => 'Lodha Luxury Collection',
            'developer_year' => 1980,
            'amenities' => [
                ['name' => 'Private Infinity Pool', 'icon' => 'fa-water-ladder', 'category' => 'Wellness'],
                ['name' => 'Helipad Access', 'icon' => 'fa-helicopter', 'category' => 'Executive'],
                ['name' => 'Private Concierge', 'icon' => 'fa-bell-concierge', 'category' => 'Services'],
                ['name' => 'Temperature-Controlled Cellar', 'icon' => 'fa-wine-bottle', 'category' => 'Lifestyle'],
                ['name' => 'Double-Height Living Salon', 'icon' => 'fa-expand', 'category' => 'Architecture'],
                ['name' => 'Private High-Speed Elevator', 'icon' => 'fa-elevator', 'category' => 'Security']
            ]
        ],
        [
            'id' => 2,
            'title' => 'Magnolia Golf Estate Villa at DLF Phase 5',
            'slug' => 'magnolia-golf-estate-villa-dlf-phase-5',
            'subtitle' => 'Expansive 10,500 Sq.Ft Gated Mansion Overlooking Arnold Palmer Golf Course',
            'description' => 'Nestled within DLF Golf Links on Golf Course Road, this bespoke designer villa combines classic Mediterranean architecture with high-tech automated smart-home infrastructure. Featuring sprawling manicured private lawns, temperature-regulated pool, cigar room, private gymnasium, and servant quarters.',
            'price' => 420000000.0,
            'price_display' => '₹ 42.00 Cr',
            'purpose' => 'buy',
            'category_id' => 2,
            'category_name' => 'Golf & Ocean Villas',
            'category_slug' => 'villas',
            'city_id' => 2,
            'city_name' => 'Delhi NCR',
            'city_slug' => 'delhi-ncr',
            'city_state' => 'Haryana',
            'locality' => 'Golf Course Road (Gurugram)',
            'address' => 'Villa 14, The Magnolias Enclave, Golf Course Road, Gurugram 122002',
            'bhk' => 6,
            'bedrooms' => 6,
            'bathrooms' => 7,
            'balconies' => 5,
            'carpet_area_sqft' => 10500,
            'builtup_area_sqft' => 14000,
            'floor_number' => 'G + 2 Independent Villa',
            'total_floors' => 3,
            'furnishing' => 'Designer Furnished',
            'facing' => 'North-East (Golf Greens)',
            'parking_spaces' => 8,
            'status' => 'Ready to Move',
            'possession_date' => 'Immediate',
            'rera_number' => 'HRERA: 114/2022/GGM',
            'is_featured' => 1,
            'is_exclusive' => 1,
            'is_verified' => 1,
            'approval_status' => 'approved',
            'cover_image' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1600&q=80',
            'floor_plan_image' => 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=80',
            'gallery_images' => json_encode([
                'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1600&q=80'
            ]),
            'agent_name' => 'Meera Nambiar',
            'agent_title' => 'Director - Luxury Portfolio Advisory',
            'agent_phone' => '+91 124 4985 000',
            'agent_email' => 'meera.nambiar@estatevistaindia.com',
            'agent_avatar' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=300&q=80',
            'agent_experience' => '14+ Years Advisory Experience',
            'developer_name' => 'DLF Luxury Developers',
            'developer_year' => 1946,
            'amenities' => [
                ['name' => 'Championship Golf Access', 'icon' => 'fa-golf-ball-tee', 'category' => 'Sport'],
                ['name' => 'Private Manicured Lawn', 'icon' => 'fa-tree', 'category' => 'Outdoor'],
                ['name' => 'Temperature Pool', 'icon' => 'fa-water-ladder', 'category' => 'Wellness'],
                ['name' => '8-Car Covered Porte-Cochere', 'icon' => 'fa-car-side', 'category' => 'Parking']
            ]
        ],
        [
            'id' => 3,
            'title' => 'The Glass Sanctuary Penthouse at Indiranagar',
            'slug' => 'glass-sanctuary-penthouse-indiranagar',
            'subtitle' => 'Double-Level Ultra-Tech Sky Residence with Private Zen Garden',
            'description' => 'Designed by celebrated international architects, this duplex penthouse features 100% soundproof acoustic curtain-wall glass, customized Italian marble finishes, private automated rooftop terrace, and serene tree-canopy views of Bengaluru defense colony.',
            'price' => 320000000.0,
            'price_display' => '₹ 32.00 Cr',
            'purpose' => 'buy',
            'category_id' => 1,
            'category_name' => 'Sky Penthouses',
            'category_slug' => 'penthouses',
            'city_id' => 3,
            'city_name' => 'Bengaluru',
            'city_slug' => 'bengaluru',
            'city_state' => 'Karnataka',
            'locality' => 'Indiranagar 100ft Road',
            'address' => 'The Sovereign Glass House, 12th Main, HAL 2nd Stage, Indiranagar, Bengaluru 560038',
            'bhk' => 4,
            'bedrooms' => 4,
            'bathrooms' => 5,
            'balconies' => 3,
            'carpet_area_sqft' => 6200,
            'builtup_area_sqft' => 8400,
            'floor_number' => '18th & 19th Floor Duplex',
            'total_floors' => 20,
            'furnishing' => 'Semi-Furnished',
            'facing' => 'East (Morning Sunlight)',
            'parking_spaces' => 4,
            'status' => 'Ready to Move',
            'possession_date' => 'Immediate',
            'rera_number' => 'KarRERA: PRM/KA/RERA/1251/310',
            'is_featured' => 1,
            'is_exclusive' => 1,
            'is_verified' => 1,
            'approval_status' => 'approved',
            'cover_image' => 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1600&q=80',
            'floor_plan_image' => 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=80',
            'gallery_images' => json_encode([
                'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=1600&q=80'
            ]),
            'agent_name' => 'Ananya Goenka',
            'agent_title' => 'Director - Coastal & Tech Founder Residences',
            'agent_phone' => '+91 80 4985 0000',
            'agent_email' => 'ananya.goenka@estatevistaindia.com',
            'agent_avatar' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=300&q=80',
            'agent_experience' => '12+ Years Advisory Experience',
            'developer_name' => 'Total Environment',
            'developer_year' => 1996,
            'amenities' => [
                ['name' => 'Zen Rooftop Deck', 'icon' => 'fa-seedling', 'category' => 'Lifestyle'],
                ['name' => 'Automated Smart-Home Control', 'icon' => 'fa-microchip', 'category' => 'Tech'],
                ['name' => 'Acoustic Soundproofing', 'icon' => 'fa-volume-xmark', 'category' => 'Comfort']
            ]
        ],
        [
            'id' => 4,
            'title' => 'Casa do Sol Heritage Ocean Villa in Assagao',
            'slug' => 'casa-do-sol-heritage-ocean-villa-assagao',
            'subtitle' => 'Restored Portuguese-Colonial Mansion with Private Coconut Grove & Infinity Pool',
            'description' => 'A rare 1.2-acre private sanctuary in the fashion and culinary epicentre of Assagao, North Goa. Carefully restored with original teakwood trusses, handcrafted terracotta flooring, high arched verandahs, private organic spice garden, and secluded lap pool.',
            'price' => 265000000.0,
            'price_display' => '₹ 26.50 Cr',
            'purpose' => 'buy',
            'category_id' => 4,
            'category_name' => 'Heritage & Mansions',
            'category_slug' => 'heritage',
            'city_id' => 4,
            'city_name' => 'Goa',
            'city_slug' => 'goa',
            'city_state' => 'Goa',
            'locality' => 'Assagao Badem',
            'address' => 'Casa do Sol Estate, Bouta Waddo, Assagao, Goa 403507',
            'bhk' => 4,
            'bedrooms' => 4,
            'bathrooms' => 5,
            'balconies' => 4,
            'carpet_area_sqft' => 5400,
            'builtup_area_sqft' => 7800,
            'floor_number' => 'Heritage Estate Villa',
            'total_floors' => 2,
            'furnishing' => 'Fully Furnished',
            'facing' => 'West (Sunset Panorama)',
            'parking_spaces' => 5,
            'status' => 'Ready to Move',
            'possession_date' => 'Immediate',
            'rera_number' => 'GoaRERA: PRGO05180321',
            'is_featured' => 1,
            'is_exclusive' => 1,
            'is_verified' => 1,
            'approval_status' => 'approved',
            'cover_image' => 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1600&q=80',
            'floor_plan_image' => 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=80',
            'gallery_images' => json_encode([
                'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1600&q=80'
            ]),
            'agent_name' => 'Tarun Tahiliani',
            'agent_title' => 'Senior Vice President - Coastal Estates',
            'agent_phone' => '+91 832 2985 000',
            'agent_email' => 'tarun.t@estatevistaindia.com',
            'agent_avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80',
            'agent_experience' => '18+ Years in Goa Luxury',
            'developer_name' => 'Isprava Luxury Homes',
            'developer_year' => 2013,
            'amenities' => [
                ['name' => '1.2 Acre Private Grove', 'icon' => 'fa-tree', 'category' => 'Nature'],
                ['name' => 'Swimming Pool & Sun Deck', 'icon' => 'fa-water-ladder', 'category' => 'Wellness'],
                ['name' => 'Dedicated Butler & Staff Quarters', 'icon' => 'fa-users', 'category' => 'Service']
            ]
        ],
        [
            'id' => 5,
            'title' => 'Kohinoor Crest Sky Penthouse at Jubilee Hills',
            'slug' => 'kohinoor-crest-sky-penthouse-jubilee-hills',
            'subtitle' => 'Regal 7,800 Sq.Ft Sky Palace Overlooking Durgam Cheruvu & City Skyline',
            'description' => 'Situated atop Road No. 36 in Hyderabad most distinguished billionaire enclave, this sky penthouse boasts private elevator foyer, 14-foot floor-to-ceiling panoramic glass, bespoke marble bar salon, and climate-controlled terrace lounge.',
            'price' => 380000000.0,
            'price_display' => '₹ 38.00 Cr',
            'purpose' => 'buy',
            'category_id' => 1,
            'category_name' => 'Sky Penthouses',
            'category_slug' => 'penthouses',
            'city_id' => 5,
            'city_name' => 'Hyderabad',
            'city_slug' => 'hyderabad',
            'city_state' => 'Telangana',
            'locality' => 'Jubilee Hills Road 36',
            'address' => 'Kohinoor Tower, Road No 36, Jubilee Hills, Hyderabad 500033',
            'bhk' => 5,
            'bedrooms' => 5,
            'bathrooms' => 6,
            'balconies' => 4,
            'carpet_area_sqft' => 7800,
            'builtup_area_sqft' => 10200,
            'floor_number' => '32nd & 33rd Floor',
            'total_floors' => 34,
            'furnishing' => 'Bespoke Furnished',
            'facing' => 'North-East (Lake Facing)',
            'parking_spaces' => 5,
            'status' => 'Under Construction',
            'possession_date' => 'December 2026',
            'rera_number' => 'TSRERA: P02400004921',
            'is_featured' => 1,
            'is_exclusive' => 1,
            'is_verified' => 1,
            'approval_status' => 'approved',
            'cover_image' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1600&q=80',
            'floor_plan_image' => 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=80',
            'gallery_images' => json_encode([
                'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1600&q=80'
            ]),
            'agent_name' => 'Aditya Vardhan Birla',
            'agent_title' => 'Senior Partner - Ultra-Luxury Acquisitions',
            'agent_phone' => '+91 22 6985 4010',
            'agent_email' => 'aditya.birla@estatevistaindia.com',
            'agent_avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80',
            'agent_experience' => '16+ Years in Prime Real Estate',
            'developer_name' => 'Phoenix Luxury Living',
            'developer_year' => 2002,
            'amenities' => [
                ['name' => 'Lake Vistas', 'icon' => 'fa-water', 'category' => 'Views'],
                ['name' => 'Private Sky Lounge', 'icon' => 'fa-champagne-glasses', 'category' => 'Entertainment'],
                ['name' => 'Smart Security & Biometrics', 'icon' => 'fa-shield-halved', 'category' => 'Security']
            ]
        ],
        [
            'id' => 6,
            'title' => 'The Koregaon Sovereign Estate at Boat Club Road',
            'slug' => 'the-koregaon-sovereign-estate-boat-club-road',
            'subtitle' => 'Contemporary Riverfront Penthouse with 360-degree Mula-Mutha River Panoramas',
            'description' => 'Set in Pune foremost aristocratic neighborhood of Boat Club Road, this residence features private dock access, wraparound sundecks, Italian Carrara marble, and climate-controlled master suite with his & her walk-in wardrobes.',
            'price' => 210000000.0,
            'price_display' => '₹ 21.00 Cr',
            'purpose' => 'buy',
            'category_id' => 3,
            'category_name' => 'Sea-Facing Suites',
            'category_slug' => 'sea-facing',
            'city_id' => 6,
            'city_name' => 'Pune',
            'city_slug' => 'pune',
            'city_state' => 'Maharashtra',
            'locality' => 'Boat Club Road',
            'address' => 'Sovereign Heights, Boat Club Road, Pune 411001',
            'bhk' => 4,
            'bedrooms' => 4,
            'bathrooms' => 5,
            'balconies' => 3,
            'carpet_area_sqft' => 5800,
            'builtup_area_sqft' => 7600,
            'floor_number' => '14th Floor Penthouse',
            'total_floors' => 15,
            'furnishing' => 'Semi-Furnished',
            'facing' => 'South-West (Riverfront)',
            'parking_spaces' => 4,
            'status' => 'Ready to Move',
            'possession_date' => 'Immediate',
            'rera_number' => 'MAHARERA: P52100018920',
            'is_featured' => 1,
            'is_exclusive' => 1,
            'is_verified' => 1,
            'approval_status' => 'approved',
            'cover_image' => 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1600&q=80',
            'floor_plan_image' => 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=80',
            'gallery_images' => json_encode([
                'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1600&q=80'
            ]),
            'agent_name' => 'Meera Nambiar',
            'agent_title' => 'Director - Luxury Portfolio Advisory',
            'agent_phone' => '+91 124 4985 000',
            'agent_email' => 'meera.nambiar@estatevistaindia.com',
            'agent_avatar' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=300&q=80',
            'agent_experience' => '14+ Years Advisory Experience',
            'developer_name' => 'Panchshil Realty',
            'developer_year' => 2002,
            'amenities' => [
                ['name' => 'Riverfront Promenade', 'icon' => 'fa-water', 'category' => 'Location'],
                ['name' => 'Private Elevator Entry', 'icon' => 'fa-elevator', 'category' => 'Privacy'],
                ['name' => 'Wine Chiller & Humidor Room', 'icon' => 'fa-wine-glass', 'category' => 'Luxury']
            ]
        ]
    ];
}

/**
 * Return 6 top Indian prime wealth cities with high-res images
 */
function get_fallback_cities(): array {
    return [
        [
            'id' => 1,
            'name' => 'Mumbai',
            'slug' => 'mumbai',
            'state' => 'Maharashtra',
            'tagline' => 'Iconic Coastlines & High-Rise Mansions',
            'image_url' => 'https://images.unsplash.com/photo-1570168007204-dfb528c6958f?auto=format&fit=crop&w=800&q=80',
            'property_count' => 48,
            'real_prop_count' => 48,
            'display_order' => 1
        ],
        [
            'id' => 2,
            'name' => 'Delhi NCR',
            'slug' => 'delhi-ncr',
            'state' => 'Delhi / Haryana',
            'tagline' => 'Expansive Golf Estates & Diplomatic Mansions',
            'image_url' => 'https://images.unsplash.com/photo-1587474260584-136574528ed5?auto=format&fit=crop&w=800&q=80',
            'property_count' => 36,
            'real_prop_count' => 36,
            'display_order' => 2
        ],
        [
            'id' => 3,
            'name' => 'Bengaluru',
            'slug' => 'bengaluru',
            'state' => 'Karnataka',
            'tagline' => 'Garden Mansions & Ultra-Tech Penthouses',
            'image_url' => 'https://images.unsplash.com/photo-1596176530529-78163a4f7af2?auto=format&fit=crop&w=800&q=80',
            'property_count' => 29,
            'real_prop_count' => 29,
            'display_order' => 3
        ],
        [
            'id' => 4,
            'name' => 'Goa',
            'slug' => 'goa',
            'state' => 'Goa',
            'tagline' => 'Portuguese Villas & Oceanfront Sanctuaries',
            'image_url' => 'https://images.unsplash.com/photo-1512343879784-a960bf40e7f2?auto=format&fit=crop&w=800&q=80',
            'property_count' => 24,
            'real_prop_count' => 24,
            'display_order' => 4
        ],
        [
            'id' => 5,
            'name' => 'Hyderabad',
            'slug' => 'hyderabad',
            'state' => 'Telangana',
            'tagline' => 'Regal Hill Estates & Financial District Towers',
            'image_url' => 'https://images.unsplash.com/photo-1605276374104-dee2a0ed3cd6?auto=format&fit=crop&w=800&q=80',
            'property_count' => 22,
            'real_prop_count' => 22,
            'display_order' => 5
        ],
        [
            'id' => 6,
            'name' => 'Pune',
            'slug' => 'pune',
            'state' => 'Maharashtra',
            'tagline' => 'Verdant Enclaves & Contemporary Penthouses',
            'image_url' => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=800&q=80',
            'property_count' => 18,
            'real_prop_count' => 18,
            'display_order' => 6
        ]
    ];
}

/**
 * Return property categories with high-res cards
 */
function get_fallback_categories(): array {
    return [
        [
            'id' => 1,
            'name' => 'Sky Penthouses',
            'slug' => 'penthouses',
            'subtitle' => 'Multi-level high-rise residences with private decks & infinity pools',
            'count' => '42+ Residences',
            'icon' => 'fa-building-columns',
            'image' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=800&q=80'
        ],
        [
            'id' => 2,
            'name' => 'Golf & Ocean Villas',
            'slug' => 'villas',
            'subtitle' => 'Expansive gated estate villas with private lawns & curated landscapes',
            'count' => '38+ Residences',
            'icon' => 'fa-house-chimney-window',
            'image' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80'
        ],
        [
            'id' => 3,
            'name' => 'Sea-Facing Suites',
            'slug' => 'sea-facing',
            'subtitle' => 'Unobstructed Arabian Sea & coastline panoramic residences',
            'count' => '29+ Residences',
            'icon' => 'fa-water',
            'image' => 'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=800&q=80'
        ],
        [
            'id' => 4,
            'name' => 'Heritage & Mansions',
            'slug' => 'heritage',
            'subtitle' => 'Architecturally distinguished colonial, royal & bespoke estates',
            'count' => '16+ Residences',
            'icon' => 'fa-landmark-dome',
            'image' => 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=800&q=80'
        ]
    ];
}

/**
 * Return luxury residential projects
 */
function get_fallback_projects(): array {
    return [
        [
            'id' => 1,
            'title' => 'The Camellias by DLF',
            'slug' => 'the-camellias-dlf-gurugram',
            'developer_name' => 'DLF Luxury Developers',
            'city_name' => 'Delhi NCR',
            'city_state' => 'Haryana',
            'locality' => 'Golf Course Road, Gurugram',
            'price_starting' => '₹ 45.00 Cr',
            'starting_price_display' => '₹ 45.00 Cr onwards',
            'banner_image' => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80',
            'project_type' => 'Golf Residences & Penthouses',
            'status' => 'Ready for Fit-outs',
            'configuration' => '4 & 5 BHK Golf Residences',
            'possession_date' => 'Ready for Fit-outs',
            'rera_id' => 'HRERA: 18 of 2017',
            'highlights' => 'Commanding panoramic fairways, private club concierge, and Olympic-sized private aquatic center.'
        ],
        [
            'id' => 2,
            'title' => 'World Towers by Lodha',
            'slug' => 'world-towers-lodha-mumbai',
            'developer_name' => 'Lodha Luxury Collection',
            'city_name' => 'Mumbai',
            'city_state' => 'Maharashtra',
            'locality' => 'Lower Parel, South Mumbai',
            'price_starting' => '₹ 28.00 Cr',
            'starting_price_display' => '₹ 28.00 Cr onwards',
            'banner_image' => 'https://images.unsplash.com/photo-1570168007204-dfb528c6958f?auto=format&fit=crop&w=1200&q=80',
            'project_type' => 'Curated Sky Suites',
            'status' => 'Delivered & Occupied',
            'configuration' => '3 & 4 BHK Sky Suites',
            'possession_date' => 'Ready to Move',
            'rera_id' => 'MAHARERA: P51900008300',
            'highlights' => 'Iconic curvilinear architecture sculpted by Pei Cobb Freed & Partners with private spa sanctuary.'
        ],
        [
            'id' => 3,
            'title' => 'Windmills of Your Mind',
            'slug' => 'windmills-of-your-mind-bengaluru',
            'developer_name' => 'Total Environment',
            'city_name' => 'Bengaluru',
            'city_state' => 'Karnataka',
            'locality' => 'Whitefield, Bengaluru',
            'price_starting' => '₹ 18.50 Cr',
            'starting_price_display' => '₹ 18.50 Cr onwards',
            'banner_image' => 'https://images.unsplash.com/photo-1596176530529-78163a4f7af2?auto=format&fit=crop&w=1200&q=80',
            'project_type' => 'Earth-Sheltered Earth Mansions',
            'status' => 'Immediate Handover',
            'configuration' => '4 BHK Duplex Earth Villas',
            'possession_date' => 'Ready to Move',
            'rera_id' => 'K-RERA: PRM/KA/RERA/1251/446',
            'highlights' => 'Private terrace gardens, handcrafted natural stone cladding, and private heated plunge pool.'
        ]
    ];
}

/**
 * Return new launches
 */
function get_fallback_new_launches(): array {
    return [
        [
            'id' => 101,
            'title' => 'The Oberoi Sky Villa Signature Tower',
            'slug' => 'oberoi-sky-villa-signature-tower',
            'developer_name' => 'Oberoi Realty',
            'locality' => 'Worli Sea Face',
            'city_name' => 'Mumbai',
            'configuration' => '4 & 5 BHK Sky Mansions',
            'carpet_area' => '7,200 sq.ft',
            'possession' => 'Possession: Dec 2027',
            'rera_id' => 'MAHARERA: P51900028911',
            'starting_price' => '₹ 52.00 Cr',
            'badge' => 'Pre-Market Allocation',
            'image' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=800&q=80'
        ],
        [
            'id' => 102,
            'title' => 'AeroCity Luxury Golf Courtyard Villa',
            'slug' => 'aerocity-luxury-golf-courtyard-villa',
            'developer_name' => 'Bharti Land & Estates',
            'locality' => 'Aerocity Diplomatic District',
            'city_name' => 'Delhi NCR',
            'configuration' => '5 BHK Courtyard Estate',
            'carpet_area' => '9,400 sq.ft',
            'possession' => 'Possession: Mar 2027',
            'rera_id' => 'HRERA: 55 of 2024',
            'starting_price' => '₹ 38.00 Cr',
            'badge' => 'Diplomatic Allocation',
            'image' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80'
        ],
        [
            'id' => 103,
            'title' => 'Bespoke Coastal Hill Sanctuary in Reis Magos',
            'slug' => 'bespoke-coastal-hill-sanctuary-reis-magos',
            'developer_name' => 'Isprava Luxury Homes',
            'locality' => 'Reis Magos Coastal Cliff',
            'city_name' => 'Goa',
            'configuration' => '4 BHK Oceanfront Estate',
            'carpet_area' => '6,100 sq.ft',
            'possession' => 'Possession: Oct 2026',
            'rera_id' => 'GOA-RERA: PRGO09232145',
            'starting_price' => '₹ 22.00 Cr',
            'badge' => 'Sea-Facing Launch',
            'image' => 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=800&q=80'
        ]
    ];
}

/**
 * Return client testimonials
 */
function get_fallback_testimonials(): array {
    return [
        [
            'id' => 1,
            'client_name' => 'Karan Malhotra',
            'client_role' => 'Founder & Managing Director',
            'client_title' => 'Founder & Managing Director',
            'location' => 'Singhania Capital, Mumbai',
            'quote' => 'EstateVista handled our family office acquisition of the Worli Sea Face penthouse with exemplary discretion. Their 30-year title audit protocol gave our legal counsel complete confidence.',
            'review' => 'EstateVista handled our family office acquisition of the Worli Sea Face penthouse with exemplary discretion. Their 30-year title audit protocol gave our legal counsel complete confidence.',
            'rating' => 5,
            'verified_buyer' => 1,
            'client_avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=300&q=80'
        ],
        [
            'id' => 2,
            'client_name' => 'Dr. Sunita Kirloskar',
            'client_role' => 'Non-Executive Director & Angel Investor',
            'client_title' => 'Non-Executive Director & Angel Investor',
            'location' => 'Bengaluru / North Goa',
            'quote' => 'Finding a quiet, bespoke colonial heritage villa in North Goa without mass broker interference seemed impossible until we engaged EstateVista. Truly a private banking grade real estate experience.',
            'review' => 'Finding a quiet, bespoke colonial heritage villa in North Goa without mass broker interference seemed impossible until we engaged EstateVista. Truly a private banking grade real estate experience.',
            'rating' => 5,
            'verified_buyer' => 1,
            'client_avatar' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=300&q=80'
        ],
        [
            'id' => 3,
            'client_name' => 'Vivek Chandrasekhar',
            'client_role' => 'Global Vice President, AI Enterprise',
            'client_title' => 'Global Vice President, AI Enterprise',
            'location' => 'Silicon Valley / Bengaluru',
            'quote' => 'Relocating to Bengaluru, we needed an off-market penthouse on Indiranagar 100ft road. EstateVista relationship manager scheduled private weekend previews directly with the promoter.',
            'review' => 'Relocating to Bengaluru, we needed an off-market penthouse on Indiranagar 100ft road. EstateVista relationship manager scheduled private weekend previews directly with the promoter.',
            'rating' => 5,
            'verified_buyer' => 1,
            'client_avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=300&q=80'
        ]
    ];
}

/**
 * Return market intelligence research articles
 */
function get_fallback_articles(): array {
    return [
        [
            'id' => 1,
            'title' => 'India Ultra-Prime Real Estate Wealth Report 2026',
            'slug' => 'india-ultra-prime-real-estate-wealth-report-2026',
            'category' => 'Market Intelligence',
            'read_time' => '6 min read',
            'author' => 'EstateVista Research Desk',
            'publish_date' => 'November 2026',
            'image_url' => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80',
            'excerpt' => 'Analysis of capital appreciation and foreign institutional capital inflows into Mumbai Worli Sea Face, Bandra, and Gurugram Golf Course Road luxury sectors.'
        ],
        [
            'id' => 2,
            'title' => 'Section 54 Capital Gains Structuring for Ultra-HNI Property Disposals',
            'slug' => 'section-54-capital-gains-structuring-ultra-hni',
            'category' => 'Tax & Legal',
            'read_time' => '8 min read',
            'author' => 'Senior Counsel Advisory',
            'publish_date' => 'October 2026',
            'image_url' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80',
            'excerpt' => 'Strategic tax-planning guidelines for business owners and family offices redeploying corporate liquidations into primary luxury residential assets.'
        ],
        [
            'id' => 3,
            'title' => 'The Rise of Coastal Villas in Goa as Wealth Preservation Assets',
            'slug' => 'rise-of-coastal-villas-goa-wealth-preservation',
            'category' => 'Lifestyle & Investment',
            'read_time' => '5 min read',
            'author' => 'Coastal Acquisitions Desk',
            'publish_date' => 'October 2026',
            'image_url' => 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1200&q=80',
            'excerpt' => 'Why private equity partners, startup founders, and NRI families are deploying up to 15% of their liquid portfolios into Assagao and Anjuna luxury compounds.'
        ]
    ];
}

/**
 * Filter fallback properties according to user search parameters
 */
function filter_fallback_properties(array $filters = [], string $sort = 'relevance', int $page = 1, int $per_page = 6): array {
    $props = get_fallback_properties();

    // 1. Purpose filter
    if (!empty($filters['purpose']) && in_array(strtolower($filters['purpose']), ['buy', 'rent', 'commercial'])) {
        $purpose = strtolower($filters['purpose']);
        $props = array_filter($props, fn($p) => strtolower($p['purpose']) === $purpose);
    }

    // 2. City filter
    if (!empty($filters['city']) && strtolower($filters['city']) !== 'all') {
        $c = strtolower(trim($filters['city']));
        $props = array_filter($props, fn($p) => strtolower($p['city_slug']) === $c || strtolower($p['city_name']) === $c || str_contains(strtolower($p['city_name']), $c));
    }

    // 3. BHK filter
    if (!empty($filters['bhk'])) {
        $bhkList = is_array($filters['bhk']) ? $filters['bhk'] : explode(',', (string)$filters['bhk']);
        $props = array_filter($props, function($p) use ($bhkList) {
            foreach ($bhkList as $b) {
                $b = trim($b);
                if (($b === '5' || $b === '5+') && $p['bhk'] >= 5) return true;
                if ((int)$b === (int)$p['bhk']) return true;
            }
            return false;
        });
    }

    // 4. Locality
    if (!empty($filters['locality'])) {
        $loc = strtolower(trim($filters['locality']));
        $props = array_filter($props, fn($p) => str_contains(strtolower($p['locality']), $loc) || str_contains(strtolower($p['title']), $loc));
    }

    // 5. Price Min & Max
    if (!empty($filters['min_price']) && is_numeric($filters['min_price'])) {
        $min = (float)$filters['min_price'];
        $props = array_filter($props, fn($p) => $p['price'] >= $min);
    }
    if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
        $max = (float)$filters['max_price'];
        $props = array_filter($props, fn($p) => $p['price'] <= $max);
    }

    $props = array_values($props);
    $totalCount = count($props);

    // Sorting
    if ($sort === 'price_asc') {
        usort($props, fn($a, $b) => $a['price'] <=> $b['price']);
    } elseif ($sort === 'price_desc') {
        usort($props, fn($a, $b) => $b['price'] <=> $a['price']);
    }

    $page = max(1, $page);
    $per_page = max(1, $per_page);
    $totalPages = (int)ceil($totalCount / $per_page);
    $offset = ($page - 1) * $per_page;
    $slice = array_slice($props, $offset, $per_page);

    return [
        'properties'   => $slice,
        'total'        => $totalCount,
        'pages'        => max(1, $totalPages),
        'current_page' => $page,
        'per_page'     => $per_page,
        'sort'         => $sort,
        'filters'      => $filters
    ];
}

/**
 * Find single fallback property by slug or id
 */
function find_fallback_property($identifier): ?array {
    $props = get_fallback_properties();
    $id = is_numeric($identifier) ? (int)$identifier : null;
    $slug = is_string($identifier) ? strtolower(trim($identifier)) : '';

    foreach ($props as $p) {
        if ($id !== null && (int)$p['id'] === $id) {
            return $p;
        }
        if ($slug !== '' && strtolower($p['slug']) === $slug) {
            return $p;
        }
    }

    // Default to flagship residence #1 if not matched
    return $props[0] ?? null;
}

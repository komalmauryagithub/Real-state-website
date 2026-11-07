<?php
/**
 * EstateVista India - Core Helper Functions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

/**
 * Format raw INR number into Indian Crore / Lakh string
 * e.g., 345000000 -> "₹ 34.50 Cr", 8500000 -> "₹ 85.00 Lakh"
 */
function format_inr_price($amount, $showSymbol = true): string {
    $symbol = $showSymbol ? CURRENCY_SYMBOL . ' ' : '';
    if (!is_numeric($amount)) {
        return $symbol . $amount;
    }
    
    $amount = (float)$amount;
    if ($amount >= 10000000) { // 1 Crore = 10,000,000
        $crores = $amount / 10000000;
        return $symbol . number_format($crores, 2) . ' Cr';
    } elseif ($amount >= 100000) { // 1 Lakh = 100,000
        $lakhs = $amount / 100000;
        return $symbol . number_format($lakhs, 2) . ' Lakh';
    } else {
        return $symbol . number_format($amount);
    }
}

/**
 * Format Indian number with standard comma separation (1,50,000)
 */
function format_inr_number($num): string {
    $num = (int)$num;
    $str = (string)$num;
    $len = strlen($str);
    
    if ($len <= 3) {
        return $str;
    }
    
    $last3 = substr($str, -3);
    $rest = substr($str, 0, $len - 3);
    
    // Group remaining into sets of 2
    $restArr = [];
    while (strlen($rest) > 2) {
        $restArr[] = substr($rest, -2);
        $rest = substr($rest, 0, strlen($rest) - 2);
    }
    if (strlen($rest) > 0) {
        $restArr[] = $rest;
    }
    
    $restArr = array_reverse($restArr);
    return implode(',', $restArr) . ',' . $last3;
}

if (!function_exists('e')) {
    /**
     * Sanitize output string
     */
    function e($string): string {
        return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Fetch featured luxury properties
 */
function get_featured_properties(int $limit = 6): array {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("
                SELECT p.*, c.name AS city_name, c.state AS city_state, 
                       cat.name AS category_name, cat.slug AS category_slug,
                       dev.name AS developer_name
                FROM properties p
                JOIN cities c ON p.city_id = c.id
                JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN developers dev ON p.developer_id = dev.id
                WHERE p.is_featured = 1 AND p.approval_status = 'approved'
                ORDER BY p.price DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Query error in get_featured_properties: " . $e->getMessage());
        }
    }

    return [];
}

/**
 * Fetch all prime cities
 */
function get_cities_list(): array {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->query("
                SELECT c.*, COUNT(p.id) as real_prop_count 
                FROM cities c 
                LEFT JOIN properties p ON c.id = p.city_id AND p.approval_status = 'approved'
                GROUP BY c.id 
                ORDER BY c.display_order ASC, c.id ASC
            ");
            $results = $stmt->fetchAll();
            if (!empty($results)) {
                return $results;
            }
        } catch (PDOException $e) {
            error_log("Query error in get_cities_list: " . $e->getMessage());
        }
    }
    
    return [];
}

/**
 * Fetch client testimonials
 */
function get_testimonials_list(): array {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->query("SELECT * FROM testimonials WHERE verified_buyer = 1 ORDER BY id ASC LIMIT 3");
            $res = $stmt->fetchAll();
            if (!empty($res)) return $res;
        } catch (PDOException $e) {
            error_log("Testimonials query error: " . $e->getMessage());
        }
    }
    
    return [];
}


/**
 * Fetch Luxury Residential Projects
 */
function get_luxury_projects(): array {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->query("
                SELECT pr.*, d.name AS developer_name, c.name AS city_name, c.state AS city_state
                FROM projects pr
                LEFT JOIN developers d ON pr.developer_id = d.id
                JOIN cities c ON pr.city_id = c.id
                WHERE pr.is_featured = 1
                ORDER BY pr.id ASC
            ");
            $res = $stmt->fetchAll();
            if (!empty($res)) return $res;
        } catch (PDOException $e) {
            error_log("Query error in get_luxury_projects: " . $e->getMessage());
        }
    }

    return [];
}

/**
 * Fetch Market Insights & Intelligence Articles
 */
function get_articles(): array {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->query("
                SELECT bp.id, bp.title, bp.slug, bp.category, bp.read_time, bp.author,
                       bp.publish_date, bp.image_url, bp.excerpt
                FROM blog_posts bp
                WHERE bp.status = 'published'
                ORDER BY bp.id ASC
                LIMIT 3
            ");
            $res = $stmt->fetchAll();
            if (!empty($res)) {
                $fallbackImages = [
                    1 => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80',
                    2 => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80',
                    3 => 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1200&q=80'
                ];
                foreach ($res as &$art) {
                    $img = $art['image_url'];
                    if (empty($img) || (!str_starts_with($img, 'http://') && !str_starts_with($img, 'https://') && !file_exists(__DIR__ . '/../' . $img))) {
                        $art['image_url'] = $fallbackImages[$art['id']] ?? 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80';
                    }
                }
                return $res;
            }
        } catch (PDOException $e) {
            error_log("Query error in get_articles: " . $e->getMessage());
        }
    }

    return [];
}

/**
 * Fetch Property Categories directly from MySQL
 */
function get_property_categories(): array {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->query("
                SELECT pc.*, COUNT(p.id) as real_count
                FROM property_categories pc
                LEFT JOIN properties p ON pc.id = p.category_id AND p.approval_status = 'approved'
                GROUP BY pc.id
                ORDER BY pc.display_order ASC, pc.id ASC
            ");
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                $categoryImages = [
                    'apartments'        => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=800&q=80',
                    'villas'            => 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=800&q=80',
                    'penthouses'        => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=800&q=80',
                    'sky-penthouses'    => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=800&q=80',
                    'sea-facing'        => 'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=800&q=80',
                    'heritage-mansions' => 'https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=800&q=80',
                    'plots'             => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=800&q=80',
                    'commercial'        => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80'
                ];
                $formatted = [];
                foreach ($rows as $row) {
                    $slug = $row['slug'];
                    $formatted[] = [
                        'id'        => $row['id'],
                        'name'      => $row['name'],
                        'slug'      => $slug,
                        'subtitle'  => $row['description'] ?: 'Curated Luxury Residences',
                        'count'     => ($row['real_count'] > 0 ? $row['real_count'] . '+ Residences' : 'Exclusive Portfolio'),
                        'icon'      => $row['icon_class'] ?: 'fa-building',
                        'image'     => $categoryImages[$slug] ?? 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=800&q=80'
                    ];
                }
                return $formatted;
            }
        } catch (PDOException $e) {
            error_log("Categories query error in get_property_categories: " . $e->getMessage());
        }
    }
    return [];
}

/**
 * Fetch New Launches & Exclusive Listings dynamically from MySQL
 */
function get_new_launches(): array {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->query("
                SELECT p.*, c.name AS city_name, cat.name AS category_name, dev.name AS developer_name
                FROM properties p
                JOIN cities c ON p.city_id = c.id
                JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN developers dev ON p.developer_id = dev.id
                WHERE p.status = 'under_construction' AND p.approval_status = 'approved'
                ORDER BY p.price DESC
                LIMIT 3
            ");
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                $launches = [];
                foreach ($rows as $r) {
                    $launches[] = [
                        'id'             => $r['id'],
                        'title'          => $r['title'],
                        'slug'           => $r['slug'],
                        'locality'       => $r['locality'],
                        'city_name'      => $r['city_name'],
                        'developer_name' => $r['developer_name'] ?: 'EstateVista Signature Collection',
                        'configuration'  => $r['bhk'] . ' BHK Ultra-Luxury Residences',
                        'carpet_area'    => number_format($r['carpet_area_sqft']) . ' sq.ft',
                        'starting_price' => $r['price_display'] . ' onwards',
                        'rera_id'        => $r['rera_number'],
                        'badge'          => ($r['is_exclusive'] ? 'Exclusive Pre-Launch' : 'Priority Booking'),
                        'possession'     => 'Possession: ' . ($r['possession_date'] ?: 'Under Construction'),
                        'image'          => $r['cover_image']
                    ];
                }
                return $launches;
            }
        } catch (PDOException $e) {
            error_log("Error in get_new_launches: " . $e->getMessage());
        }
    }
    return [];
}

/**
 * Safe Image URL Resolver & Robust Fallback Handler
 * 
 * Guarantees a fully-qualified, valid URL for any image stored in the DB,
 * uploaded by users/admins, or referenced across the site.
 * 
 * @param string|null $url Raw URL or relative file path from DB/form
 * @param string $type 'property' | 'avatar' | 'city' | 'floorplan' | 'article'
 * @return string Guaranteed web-accessible image URL
 */
function safe_image_url(?string $url, string $type = 'property'): string {
    $fallbacks = [
        'property'  => BASE_URL . 'assets/images/property-placeholder.svg',
        'avatar'    => BASE_URL . 'assets/images/avatar-placeholder.svg',
        'city'      => BASE_URL . 'assets/images/city-placeholder.svg',
        'floorplan' => BASE_URL . 'assets/images/floorplan-placeholder.svg',
        'article'   => BASE_URL . 'assets/images/property-placeholder.svg'
    ];
    $fallback = $fallbacks[$type] ?? $fallbacks['property'];

    if (empty($url) || !is_string($url)) {
        return $fallback;
    }

    $url = trim($url);
    if ($url === '') {
        return $fallback;
    }

    // Already an absolute URL with scheme or data URI
    if (preg_match('/^(?:https?:|\/\/|data:image\/)/i', $url)) {
        return $url;
    }

    // Already prefixed with BASE_URL
    if (str_starts_with($url, BASE_URL)) {
        return $url;
    }

    // Clean leading slashes and append BASE_URL
    return BASE_URL . ltrim($url, '/');
}

/**
 * Helper to get array of gallery images for a property
 */
function get_property_gallery_images($property): array {
    $images = [];

    // 1. Cover Image (should always be primary if valid)
    if (!empty($property['cover_image'])) {
        $cover = trim((string)$property['cover_image']);
        if ($cover !== '') {
            $images[] = safe_image_url($cover, 'property');
        }
    }

    // 2. Check if property has pre-parsed gallery_images JSON
    if (!empty($property['gallery_images'])) {
        $decoded = is_array($property['gallery_images']) 
            ? $property['gallery_images'] 
            : json_decode($property['gallery_images'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $img) {
                if (is_string($img) && trim($img) !== '') {
                    $img = trim($img);
                    if (str_starts_with($img, 'assets/') || str_starts_with($img, 'uploads/')) {
                        if (!file_exists(dirname(__DIR__) . '/' . $img)) continue;
                    }
                    $safeUrl = safe_image_url($img, 'property');
                    if (!in_array($safeUrl, $images)) {
                        $images[] = $safeUrl;
                    }
                }
            }
        }
    }

    // 3. If empty, check property_images table
    if (count($images) <= 1 && !empty($property['id'])) {
        $db = Database::getConnection();
        if ($db) {
            try {
                $stmt = $db->prepare("SELECT image_url FROM property_images WHERE property_id = :pid ORDER BY display_order ASC, id ASC");
                $stmt->bindValue(':pid', (int)$property['id'], PDO::PARAM_INT);
                $stmt->execute();
                $dbImgs = $stmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($dbImgs as $img) {
                    $img = trim((string)$img);
                    if ($img === '') continue;
                    if (str_starts_with($img, 'assets/') || str_starts_with($img, 'uploads/')) {
                        if (!file_exists(dirname(__DIR__) . '/' . $img)) continue;
                    }
                    $safeUrl = safe_image_url($img, 'property');
                    if (!in_array($safeUrl, $images)) {
                        $images[] = $safeUrl;
                    }
                }
            } catch (PDOException $e) {
                // Ignore and proceed to fallback
            }
        }
    }

    // 4. Fallback palettes by property purpose or type to guarantee 4 crystal-clear images
    $purpose = strtolower($property['purpose'] ?? 'buy');
    $curated_fallbacks = [
        'commercial' => [
            'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1600&q=80',
            'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1600&q=80',
            'https://images.unsplash.com/photo-1497215728101-856f4ea42174?auto=format&fit=crop&w=1600&q=80',
            'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1600&q=80'
        ],
        'rent' => [
            'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1600&q=80',
            'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1600&q=80',
            'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1600&q=80',
            'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1600&q=80'
        ],
        'buy' => [
            'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1600&q=80',
            'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1600&q=80',
            'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1600&q=80',
            'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1600&q=80'
        ]
    ];

    $palette = $curated_fallbacks[$purpose] ?? $curated_fallbacks['buy'];

    if (empty($images)) {
        return $palette;
    }

    // Ensure at least 3 photos for smooth carousel preview
    if (count($images) < 3) {
        foreach ($palette as $pImg) {
            if (!in_array($pImg, $images)) {
                $images[] = $pImg;
            }
            if (count($images) >= 4) break;
        }
    }

    return array_values(array_unique($images));
}

/**
 * Advanced Dynamic Property Search with MySQL & Safe Parameter Binding
 */
function search_properties_advanced(array $filters = [], string $sort = 'relevance', int $page = 1, int $per_page = 6): array {
    $db = Database::getConnection();

    $page = max(1, $page);
    $per_page = max(1, $per_page);
    $offset = ($page - 1) * $per_page;

    if ($db) {
        try {
            $where = ["1=1", "p.approval_status = 'approved'"];
            $params = [];

            // 1. Purpose (Buy / Rent / Commercial)
            if (!empty($filters['purpose']) && in_array(strtolower($filters['purpose']), ['buy', 'rent', 'commercial'])) {
                $where[] = "p.purpose = :purpose";
                $params[':purpose'] = strtolower($filters['purpose']);
            }

            // 2. City Filter (by slug or id)
            if (!empty($filters['city']) && strtolower($filters['city']) !== 'all') {
                if (is_numeric($filters['city'])) {
                    $where[] = "p.city_id = :city_id";
                    $params[':city_id'] = (int)$filters['city'];
                } else {
                    $where[] = "(LOWER(c.slug) = :city_slug OR LOWER(c.name) LIKE :city_name)";
                    $params[':city_slug'] = strtolower($filters['city']);
                    $params[':city_name'] = '%' . strtolower($filters['city']) . '%';
                }
            }

            // 3. Locality Filter (text search)
            if (!empty($filters['locality'])) {
                $where[] = "(p.locality LIKE :loc1 OR p.address LIKE :loc2 OR p.title LIKE :loc3)";
                $locVal = '%' . trim($filters['locality']) . '%';
                $params[':loc1'] = $locVal;
                $params[':loc2'] = $locVal;
                $params[':loc3'] = $locVal;
            }

            // 4. Property Type / Category
            if (!empty($filters['type']) && strtolower($filters['type']) !== 'all') {
                $type = strtolower($filters['type']);
                $where[] = "(LOWER(cat.slug) = :cat_slug OR LOWER(cat.name) LIKE :cat_name)";
                $params[':cat_slug'] = $type;
                $params[':cat_name'] = '%' . $type . '%';
            }

            // 5. Price Min & Max (INR)
            if (!empty($filters['min_price']) && is_numeric($filters['min_price'])) {
                $where[] = "p.price >= :min_price";
                $params[':min_price'] = (float)$filters['min_price'];
            }
            if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
                $where[] = "p.price <= :max_price";
                $params[':max_price'] = (float)$filters['max_price'];
            }

            // 6. BHK Configuration (supports multi-select array or single value)
            if (!empty($filters['bhk'])) {
                $bhkList = is_array($filters['bhk']) ? $filters['bhk'] : explode(',', (string)$filters['bhk']);
                $bhkClauses = [];
                foreach ($bhkList as $idx => $b) {
                    $b = trim($b);
                    if ($b === '5' || $b === '5+') {
                        $bhkClauses[] = "p.bhk >= 5";
                    } elseif (is_numeric($b)) {
                        $paramKey = ":bhk_" . $idx;
                        $bhkClauses[] = "p.bhk = $paramKey";
                        $params[$paramKey] = (int)$b;
                    }
                }
                if (!empty($bhkClauses)) {
                    $where[] = "(" . implode(" OR ", $bhkClauses) . ")";
                }
            }

            // 7. Area in sq.ft Min & Max
            if (!empty($filters['min_area']) && is_numeric($filters['min_area'])) {
                $where[] = "p.carpet_area_sqft >= :min_area";
                $params[':min_area'] = (int)$filters['min_area'];
            }
            if (!empty($filters['max_area']) && is_numeric($filters['max_area'])) {
                $where[] = "p.carpet_area_sqft <= :max_area";
                $params[':max_area'] = (int)$filters['max_area'];
            }

            // 8. Furnishing Status
            if (!empty($filters['furnishing']) && in_array($filters['furnishing'], ['bespoke-furnished', 'semi-furnished', 'unfurnished'])) {
                $where[] = "p.furnishing = :furnishing";
                $params[':furnishing'] = $filters['furnishing'];
            }

            // 9. Ready to move / Under construction
            if (!empty($filters['status']) && in_array($filters['status'], ['ready_to_move', 'under_construction'])) {
                $where[] = "p.status = :status";
                $params[':status'] = $filters['status'];
            }

            // 10. Possession Date
            if (!empty($filters['possession']) && $filters['possession'] !== 'all') {
                $where[] = "p.possession_date LIKE :possession";
                $params[':possession'] = '%' . trim($filters['possession']) . '%';
            }

            // 11. Verified Listings Only
            if (!empty($filters['verified'])) {
                $where[] = "p.is_verified = 1";
            }

            $whereSql = implode(" AND ", $where);

            // Sorting
            switch ($sort) {
                case 'price_asc':
                    $orderSql = "p.price ASC";
                    break;
                case 'price_desc':
                    $orderSql = "p.price DESC";
                    break;
                case 'newest':
                    $orderSql = "p.created_at DESC, p.id DESC";
                    break;
                case 'relevance':
                default:
                    $orderSql = "p.is_featured DESC, p.is_exclusive DESC, p.price DESC";
                    break;
            }

            // Count total matching
            $countSql = "
                SELECT COUNT(*) AS total
                FROM properties p
                JOIN cities c ON p.city_id = c.id
                JOIN categories cat ON p.category_id = cat.id
                WHERE $whereSql
            ";
            $countStmt = $db->prepare($countSql);
            foreach ($params as $key => $val) {
                $countStmt->bindValue($key, $val);
            }
            $countStmt->execute();
            $totalCount = (int)$countStmt->fetchColumn();

            // Fetch records
            $dataSql = "
                SELECT p.*, c.name AS city_name, c.state AS city_state, c.slug AS city_slug,
                       cat.name AS category_name, cat.slug AS category_slug,
                       dev.name AS developer_name
                FROM properties p
                JOIN cities c ON p.city_id = c.id
                JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN developers dev ON p.developer_id = dev.id
                WHERE $whereSql
                ORDER BY $orderSql
                LIMIT :limit OFFSET :offset
            ";
            $dataStmt = $db->prepare($dataSql);
            foreach ($params as $key => $val) {
                $dataStmt->bindValue($key, $val);
            }
            $dataStmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
            $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $dataStmt->execute();
            $properties = $dataStmt->fetchAll();

            $totalPages = (int)ceil($totalCount / $per_page);

            return [
                'properties'   => $properties,
                'total'        => $totalCount,
                'pages'        => max(1, $totalPages),
                'current_page' => $page,
                'per_page'     => $per_page,
                'sort'         => $sort,
                'filters'      => $filters
            ];
        } catch (PDOException $e) {
            error_log("Advanced search error: " . $e->getMessage());
        }
    }

    // Return empty results if no match or error
    return [
        'properties'   => [],
        'total'        => 0,
        'pages'        => 1,
        'current_page' => 1,
        'per_page'     => $per_page,
        'sort'         => $sort,
        'filters'      => $filters
    ];
}

/**
 * Fetch Single Property by Slug or ID with Developer, City, and Amenities
 */
function get_property_by_slug_or_id($identifier, ?int $allow_user_id = null): ?array {
    $db = Database::getConnection();
    if ($db) {
        try {
            $is_numeric = is_numeric($identifier);
            $authClause = $allow_user_id !== null 
                ? "(p.approval_status = 'approved' OR p.user_id = :allowed_uid)" 
                : "p.approval_status = 'approved'";
            $sql = "
                SELECT p.*, c.name AS city_name, c.state AS city_state, c.slug AS city_slug,
                       cat.name AS category_name, cat.slug AS category_slug,
                       dev.name AS developer_name, dev.established_year AS developer_year
                FROM properties p
                JOIN cities c ON p.city_id = c.id
                JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN developers dev ON p.developer_id = dev.id
                WHERE " . ($is_numeric ? "p.id = :id" : "p.slug = :slug") . "
                  AND $authClause
                LIMIT 1
            ";
            $stmt = $db->prepare($sql);
            if ($is_numeric) {
                $stmt->bindValue(':id', (int)$identifier, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':slug', (string)$identifier, PDO::PARAM_STR);
            }
            if ($allow_user_id !== null) {
                $stmt->bindValue(':allowed_uid', $allow_user_id, PDO::PARAM_INT);
            }
            $stmt->execute();
            $property = $stmt->fetch();

            if ($property) {
                // Fetch amenities
                $amenityStmt = $db->prepare("
                    SELECT a.* 
                    FROM amenities a 
                    JOIN property_amenities pa ON a.id = pa.amenity_id 
                    WHERE pa.property_id = :pid
                    ORDER BY a.category ASC, a.name ASC
                ");
                $amenityStmt->bindValue(':pid', $property['id'], PDO::PARAM_INT);
                $amenityStmt->execute();
                $property['amenities'] = $amenityStmt->fetchAll();

                // Fetch images from property_images table
                $imgStmt = $db->prepare("
                    SELECT image_url, caption, is_cover, display_order 
                    FROM property_images 
                    WHERE property_id = :pid 
                    ORDER BY display_order ASC, id ASC
                ");
                $imgStmt->bindValue(':pid', $property['id'], PDO::PARAM_INT);
                $imgStmt->execute();
                $dbImages = $imgStmt->fetchAll();
                if (!empty($dbImages)) {
                    $property['photos'] = $dbImages;
                    $property['gallery_images'] = json_encode(array_column($dbImages, 'image_url'));
                }

                // Fetch assigned agent from agents table if present
                if (!empty($property['assigned_agent_id'])) {
                    $agentStmt = $db->prepare("SELECT * FROM agents WHERE id = :aid LIMIT 1");
                    $agentStmt->bindValue(':aid', $property['assigned_agent_id'], PDO::PARAM_INT);
                    $agentStmt->execute();
                    $agentRow = $agentStmt->fetch();
                    if ($agentRow) {
                        $property['agent_name'] = $agentRow['name'];
                        $property['agent_title'] = $agentRow['title'];
                        $property['agent_phone'] = $agentRow['phone'];
                        $property['agent_email'] = $agentRow['email'];
                        if (!empty($agentRow['avatar_url'])) {
                            $property['agent_avatar'] = $agentRow['avatar_url'];
                        }
                        $property['agent_experience'] = ($agentRow['experience_years'] ?? 16) . '+ Years in Prime Real Estate';
                    }
                }

                return $property;
            }
        } catch (PDOException $e) {
            error_log("Query error in get_property_by_slug_or_id: " . $e->getMessage());
        }
    }

    return null;
}

/**
 * Fetch Similar Properties for Property Detail Page
 */
function get_similar_properties($property_id, $city_id = 0, $category_id = 0, int $limit = 3): array {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("
                SELECT p.*, c.name AS city_name, c.state AS city_state, c.slug AS city_slug,
                       cat.name AS category_name, cat.slug AS category_slug,
                       dev.name AS developer_name
                FROM properties p
                JOIN cities c ON p.city_id = c.id
                JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN developers dev ON p.developer_id = dev.id
                WHERE p.id != :pid AND p.approval_status = 'approved' AND (p.city_id = :cid OR p.category_id = :catid)
                ORDER BY p.is_featured DESC, p.price DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':pid', (int)$property_id, PDO::PARAM_INT);
            $stmt->bindValue(':cid', (int)$city_id, PDO::PARAM_INT);
            $stmt->bindValue(':catid', (int)$category_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $res = $stmt->fetchAll();
            if (!empty($res)) return $res;
        } catch (PDOException $e) {
            error_log("Query error in get_similar_properties: " . $e->getMessage());
        }
    }

    return [];
}

/**
 * Categorized Nearby Landmarks & Distances
 */
function get_property_nearby_places(array $property): array {
    $city = strtolower($property['city_name'] ?? '');
    $locality = strtolower($property['locality'] ?? '');

    if (strpos($city, 'mumbai') !== false) {
        return [
            'schools' => [
                ['name' => 'Cathedral & John Connon School', 'distance' => '4.2 km / 12 mins'],
                ['name' => 'The Aditya Birla World Academy', 'distance' => '3.5 km / 10 mins'],
                ['name' => 'Bombay Scottish School', 'distance' => '6.8 km / 18 mins']
            ],
            'hospitals' => [
                ['name' => 'Breach Candy Hospital', 'distance' => '3.8 km / 10 mins'],
                ['name' => 'Jaslok Hospital & Research Centre', 'distance' => '4.5 km / 12 mins'],
                ['name' => 'Hinduja Healthcare Surgical', 'distance' => '5.2 km / 15 mins']
            ],
            'transit' => [
                ['name' => 'Bandra-Worli Sea Link Interchange', 'distance' => '1.2 km / 3 mins'],
                ['name' => 'Mumbai Coastal Road Expressway', 'distance' => '800 m / 2 mins'],
                ['name' => 'Worli Metro Station (Line 3 Aqua Line)', 'distance' => '1.5 km / 4 mins']
            ],
            'airports' => [
                ['name' => 'Chhatrapati Shivaji Maharaj International T2', 'distance' => '14.5 km / 25 mins via Sea Link'],
                ['name' => 'Juhu Aerodrome (Private Aviation Hub)', 'distance' => '12.8 km / 22 mins']
            ]
        ];
    } elseif (strpos($city, 'delhi') !== false || strpos($locality, 'gurugram') !== false || strpos($locality, 'golf course') !== false) {
        return [
            'schools' => [
                ['name' => 'The Shri Ram School, Moulsari', 'distance' => '3.2 km / 8 mins'],
                ['name' => 'Heritage Xperiential Learning School', 'distance' => '4.8 km / 12 mins'],
                ['name' => 'DPS International Edge', 'distance' => '5.5 km / 14 mins']
            ],
            'hospitals' => [
                ['name' => 'Fortis Memorial Research Institute', 'distance' => '3.5 km / 10 mins'],
                ['name' => 'Medanta The Medicity', 'distance' => '7.2 km / 18 mins'],
                ['name' => 'Max Super Speciality Hospital', 'distance' => '4.1 km / 11 mins']
            ],
            'transit' => [
                ['name' => 'Sector 42-43 Rapid Metro Station', 'distance' => '900 m / 3 mins'],
                ['name' => 'Golf Course Road 16-Lane Expressway', 'distance' => '500 m / 1 min'],
                ['name' => 'Cyber City Rapid Metro Interchange', 'distance' => '4.2 km / 8 mins']
            ],
            'airports' => [
                ['name' => 'Indira Gandhi International Airport (T3)', 'distance' => '16.5 km / 20 mins via NH-48'],
                ['name' => 'General Aviation Terminal (Private Jets)', 'distance' => '17.2 km / 22 mins']
            ]
        ];
    } elseif (strpos($city, 'goa') !== false) {
        return [
            'schools' => [
                ['name' => 'Sharada Mandir School, Miramar', 'distance' => '18 km / 30 mins'],
                ['name' => 'Montfort Academy, Corlim', 'distance' => '12 km / 20 mins']
            ],
            'hospitals' => [
                ['name' => 'Manipal Hospital Goa, Dona Paula', 'distance' => '22 km / 35 mins'],
                ['name' => 'Bosio Hospital, Candolim', 'distance' => '6.5 km / 12 mins'],
                ['name' => 'Vision Multispecialty, Mapusa', 'distance' => '7.8 km / 14 mins']
            ],
            'transit' => [
                ['name' => 'Assagao Design High Street & Dining', 'distance' => '400 m / 2 mins'],
                ['name' => 'Vagator & Anjuna Beaches', 'distance' => '4.5 km / 10 mins'],
                ['name' => 'NH-66 Mumbai-Goa Highway Interchange', 'distance' => '5.2 km / 9 mins']
            ],
            'airports' => [
                ['name' => 'Manohar International Airport (MOPA - GOX)', 'distance' => '24.5 km / 32 mins'],
                ['name' => 'Dabolim Airport (GOI - South Goa)', 'distance' => '42 km / 55 mins via Atal Setu']
            ]
        ];
    } else {
        // Standard Metropolitan luxury landmarks (Bengaluru, Hyderabad, Pune)
        return [
            'schools' => [
                ['name' => 'International Baccalaureate World School', 'distance' => '3.5 km / 10 mins'],
                ['name' => 'The Heritage Academy & Day School', 'distance' => '4.8 km / 12 mins']
            ],
            'hospitals' => [
                ['name' => 'Apollo Super Speciality Hospital', 'distance' => '3.2 km / 8 mins'],
                ['name' => 'Manipal Healthcare Institute', 'distance' => '4.6 km / 12 mins']
            ],
            'transit' => [
                ['name' => 'Elevated Metro Expressway Station', 'distance' => '1.1 km / 3 mins'],
                ['name' => 'Central Business District Promenade', 'distance' => '2.8 km / 7 mins']
            ],
            'airports' => [
                ['name' => 'International Airport Terminal T1 & T2', 'distance' => '28 km / 35 mins via Expressway']
            ]
        ];
    }
}

/**
 * Record Inquiry in MySQL Relational Architecture
 * Synchronizes enquiries, site_visits, leads, inquiries, notifications, and activity_logs.
 */
function record_property_inquiry(array $data): array {
    $db = Database::getConnection();
    if (!$db) {
        return [
            'success' => false,
            'message' => 'Database connection unavailable. Please contact the concierge directly.'
        ];
    }

    $user_id        = !empty($data['user_id']) ? (int)$data['user_id'] : (is_logged_in() ? get_logged_in_user_id() : null);
    $property_id    = !empty($data['property_id']) ? (int)$data['property_id'] : null;
    $inquiry_type   = in_array($data['inquiry_type'] ?? '', ['enquiry', 'callback', 'site_visit', 'valuation', 'brochure', 'general']) ? $data['inquiry_type'] : 'enquiry';
    $full_name      = trim($data['full_name'] ?? '');
    $email          = trim($data['email'] ?? '');
    $phone          = trim($data['phone'] ?? '');
    $city           = trim($data['city'] ?? '');
    $budget_range   = trim($data['budget_range'] ?? '');
    $preferred_time = trim($data['preferred_time'] ?? '');
    $visit_date     = !empty($data['visit_date']) ? trim($data['visit_date']) : null;
    $visit_time     = trim($data['visit_time'] ?? '');
    $message        = trim($data['message'] ?? '');

    if (empty($full_name) || empty($phone) || (empty($email) && $inquiry_type !== 'callback')) {
        return [
            'success' => false,
            'message' => 'Please provide your full name, phone number, and a valid email.'
        ];
    }

    // Resolve assigned agent if property exists
    $assigned_agent_id = null;
    if ($property_id) {
        $pRow = Database::fetch("SELECT assigned_agent_id FROM properties WHERE id = :pid LIMIT 1", [':pid' => $property_id]);
        if ($pRow && !empty($pRow['assigned_agent_id'])) {
            $assigned_agent_id = (int)$pRow['assigned_agent_id'];
        }
    }
    if (!$assigned_agent_id) {
        $defAgent = Database::fetch("SELECT id FROM agents WHERE status = 'active' ORDER BY id ASC LIMIT 1");
        if ($defAgent) {
            $assigned_agent_id = (int)$defAgent['id'];
        }
    }

    try {
        Database::beginTransaction();

        // 1. Insert into enquiries table (canonical relational table)
        Database::execute("
            INSERT INTO enquiries (
                user_id, property_id, assigned_agent_id, enquiry_type,
                full_name, email, phone, city, budget_range,
                preferred_time, message, status
            ) VALUES (
                :uid, :pid, :aid, :type,
                :name, :email, :phone, :city, :budget,
                :time, :msg, 'new'
            )
        ", [
            ':uid'    => $user_id,
            ':pid'    => $property_id,
            ':aid'    => $assigned_agent_id,
            ':type'   => $inquiry_type,
            ':name'   => $full_name,
            ':email'  => $email ?: 'confidential@client.internal',
            ':phone'  => $phone,
            ':city'   => $city ?: null,
            ':budget' => $budget_range ?: null,
            ':time'   => $preferred_time ?: null,
            ':msg'    => $message ?: null
        ]);
        $enquiryId = (int)Database::lastInsertId();

        // 2. Insert into site_visits table if type is site_visit
        if ($inquiry_type === 'site_visit' && $property_id && $visit_date) {
            Database::execute("
                INSERT INTO site_visits (
                    user_id, property_id, assigned_agent_id, client_name, client_email,
                    client_phone, visit_date, visit_time, special_notes, status
                ) VALUES (
                    :uid, :pid, :aid, :name, :email,
                    :phone, :vdate, :vtime, :notes, 'pending'
                )
            ", [
                ':uid'   => $user_id,
                ':pid'   => $property_id,
                ':aid'   => $assigned_agent_id,
                ':name'  => $full_name,
                ':email' => $email ?: 'confidential@client.internal',
                ':phone' => $phone,
                ':vdate' => $visit_date,
                ':vtime' => $visit_time ?: '11:00 AM',
                ':notes' => $message ?: 'VIP site visit scheduled via property details page'
            ]);
        }

        // 3. Sync CRM leads table
        $leadSource = ($inquiry_type === 'callback') 
            ? 'Property Callback Request' 
            : (($inquiry_type === 'site_visit') ? 'Property Site Visit Request' : 'Property Enquiry Desk');

        Database::execute("
            INSERT INTO leads (
                user_id, property_id, assigned_agent_id, full_name, email,
                phone, preferred_city, budget_range, lead_source, lead_status,
                notes
            ) VALUES (
                :uid, :pid, :aid, :name, :email,
                :phone, :city, :budget, :source, 'new',
                :notes
            )
        ", [
            ':uid'    => $user_id,
            ':pid'    => $property_id,
            ':aid'    => $assigned_agent_id,
            ':name'   => $full_name,
            ':email'  => $email ?: null,
            ':phone'  => $phone,
            ':city'   => $city ?: null,
            ':budget' => $budget_range ?: null,
            ':source' => $leadSource,
            ':notes'  => $message ?: 'Enquiry #' . $enquiryId
        ]);

        // 4. Compatibility inquiries table
        Database::execute("
            INSERT INTO inquiries (
                user_id, property_id, inquiry_type, full_name, email, phone, city, budget_range,
                preferred_time, visit_date, visit_time, message, status, assigned_agent_id, lead_status, lead_source
            ) VALUES (
                :uid, :pid, :type, :name, :email, :phone, :city, :budget,
                :time, :vdate, :vtime, :msg, 'new', :aid, 'new', :source
            )
        ", [
            ':uid'    => $user_id,
            ':pid'    => $property_id,
            ':type'   => in_array($inquiry_type, ['enquiry', 'callback', 'valuation', 'brochure', 'general', 'site_visit']) ? $inquiry_type : 'enquiry',
            ':name'   => $full_name,
            ':email'  => $email ?: 'confidential@client.internal',
            ':phone'  => $phone,
            ':city'   => $city ?: null,
            ':budget' => $budget_range ?: null,
            ':time'   => $preferred_time ?: null,
            ':vdate'  => $visit_date,
            ':vtime'  => $visit_time ?: null,
            ':msg'    => $message ?: null,
            ':aid'    => $assigned_agent_id,
            ':source' => $leadSource
        ]);

        // 5. Notifications
        Database::execute("
            INSERT INTO notifications (user_id, title, message, link, type, is_read)
            VALUES (NULL, :title, :msg, 'admin/enquiries.php', 'enquiry', 0)
        ", [
            ':title' => "New Inquiry: {$full_name}",
            ':msg'   => "Inquiry for Property #{$property_id} ({$phone})"
        ]);

        // 6. Activity log
        Database::execute("
            INSERT INTO activity_logs (user_id, action, details, ip_address, created_at)
            VALUES (:uid, 'create_inquiry', :details, :ip, NOW())
        ", [
            ':uid'     => $user_id,
            ':details' => "Inquiry #{$enquiryId} submitted for Property #{$property_id} by {$full_name}",
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);

        Database::commit();

        $type_messages = [
            'enquiry'    => 'Thank you for your confidential inquiry. Our Senior Acquisition Partner will contact you within 2 hours.',
            'callback'   => 'Your callback request has been scheduled. An advisor will connect with you at your chosen time slot.',
            'site_visit' => 'Your private VIP site visit has been submitted. Our concierge team will reach out to confirm your itinerary and security clearance.'
        ];

        return [
            'success' => true,
            'id'      => $enquiryId,
            'message' => $type_messages[$inquiry_type] ?? 'Inquiry registered successfully.'
        ];
    } catch (PDOException $e) {
        Database::rollBack();
        error_log("Failed to insert inquiry: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to save inquiry. Please call concierge directly at ' . SITE_PHONE
        ];
    }
}

/**
 * Generate clean URL slug from string
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    if (function_exists('iconv')) {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'residence-' . time() : $text;
}

/**
 * Fetch all property categories
 */
function get_all_categories(): array {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->query("SELECT * FROM categories ORDER BY id ASC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Categories query error: " . $e->getMessage());
        }
    }
    return [];
}

/**
 * Fetch all available luxury amenities
 */
function get_all_amenities(): array {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->query("SELECT * FROM amenities ORDER BY category ASC, name ASC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Amenities query error: " . $e->getMessage());
        }
    }
    return [];
}

/**
 * Securely process and validate property image uploads
 * Checks MIME type, file extension, file size (max 5MB), and moves to upload dir
 */
function process_property_image_uploads(array $files): array {
    $uploaded_paths = [];
    $target_dir = ROOT_PATH . '/assets/uploads/properties/';

    if (!is_dir($target_dir)) {
        @mkdir($target_dir, 0755, true);
    }

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5 MB

    // Normalize $_FILES structure for multiple or single files
    $normalized_files = [];
    if (isset($files['name']) && is_array($files['name'])) {
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK && $files['size'][$i] > 0) {
                $normalized_files[] = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i],
                ];
            }
        }
    } elseif (isset($files['name']) && !is_array($files['name']) && $files['error'] === UPLOAD_ERR_OK && $files['size'] > 0) {
        $normalized_files[] = $files;
    }

    foreach ($normalized_files as $file) {
        if ($file['size'] > $max_size) {
            continue; // Skip oversized files
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_extensions)) {
            continue; // Skip disallowed extension
        }

        // Verify MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_mimes)) {
            continue; // Skip spoofed/disallowed mime type
        }

        // Generate cryptographically secure unique filename
        $unique_name = 'prop_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        $destination = $target_dir . $unique_name;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $uploaded_paths[] = 'assets/uploads/properties/' . $unique_name;
        }
    }

    return $uploaded_paths;
}

/**
 * Create new property submission
 * Default approval_status is 'pending'
 */
function create_property_submission(int $userId, array $data, array $files = []): array {
    $db = Database::getConnection();
    if (!$db) {
        return ['success' => false, 'message' => 'Database connection unavailable.'];
    }

    // Required fields validation
    $title = trim($data['title'] ?? '');
    $category_id = (int)($data['category_id'] ?? 0);
    $purpose = strtolower(trim($data['purpose'] ?? 'buy'));
    if (!in_array($purpose, ['buy', 'rent', 'commercial'])) {
        $purpose = 'buy';
    }
    $price = (float)($data['price'] ?? 0);
    $city_id = (int)($data['city_id'] ?? 0);
    $locality = trim($data['locality'] ?? '');
    $address = trim($data['address'] ?? '');
    $bhk = (int)($data['bhk'] ?? 0);
    $bathrooms = (int)($data['bathrooms'] ?? 0);
    $carpet_area_sqft = (int)($data['carpet_area_sqft'] ?? 0);
    $contact_person_name = trim($data['contact_person_name'] ?? '');
    $contact_person_phone = trim($data['contact_person_phone'] ?? '');
    $contact_person_email = trim($data['contact_person_email'] ?? '');
    $owner_role = in_array($data['owner_role'] ?? '', ['owner', 'agent', 'developer']) ? $data['owner_role'] : 'owner';

    if (empty($title) || strlen($title) < 5) {
        return ['success' => false, 'message' => 'Property title must be at least 5 characters.'];
    }
    if ($category_id <= 0) {
        return ['success' => false, 'message' => 'Please select a valid property category.'];
    }
    if ($city_id <= 0) {
        return ['success' => false, 'message' => 'Please select a city.'];
    }
    if (empty($locality)) {
        return ['success' => false, 'message' => 'Please enter the locality or neighborhood.'];
    }
    if (empty($address)) {
        return ['success' => false, 'message' => 'Please enter the property street address.'];
    }
    if ($price <= 0) {
        return ['success' => false, 'message' => 'Please enter a valid price in INR.'];
    }
    if ($bhk <= 0) {
        return ['success' => false, 'message' => 'Please specify the number of bedrooms (BHK).'];
    }
    if ($bathrooms <= 0) {
        return ['success' => false, 'message' => 'Please specify the number of bathrooms.'];
    }
    if ($carpet_area_sqft <= 0) {
        return ['success' => false, 'message' => 'Please specify carpet area in sq.ft.'];
    }
    if (empty($contact_person_name) || empty($contact_person_phone)) {
        return ['success' => false, 'message' => 'Please provide contact person name and telephone number.'];
    }

    // Optional specifications
    $subtitle = trim($data['subtitle'] ?? '');
    if (empty($subtitle)) {
        $subtitle = "{$bhk} BHK Luxury Residence in {$locality}";
    }
    $balconies = (int)($data['balconies'] ?? 2);
    $builtup_area_sqft = !empty($data['builtup_area_sqft']) ? (int)$data['builtup_area_sqft'] : (int)round($carpet_area_sqft * 1.25);
    $furnishing = in_array($data['furnishing'] ?? '', ['unfurnished', 'semi-furnished', 'bespoke-furnished']) 
        ? $data['furnishing'] 
        : 'bespoke-furnished';
    $floor_number = trim($data['floor_number'] ?? 'Mid-to-High Floor');
    $total_floors = !empty($data['total_floors']) ? (int)$data['total_floors'] : 25;
    $facing = trim($data['facing'] ?? 'North-East (Vaastu Compliant)');
    $parking_spaces = (int)($data['parking_spaces'] ?? 2);
    $status = in_array($data['status'] ?? '', ['ready_to_move', 'under_construction', 'immediate']) 
        ? $data['status'] 
        : 'ready_to_move';
    $possession_date = trim($data['possession_date'] ?? 'Ready for Handover');
    if (empty($possession_date)) $possession_date = 'Ready for Handover';
    $rera_number = trim($data['rera_number'] ?? '');
    if (empty($rera_number)) {
        $rera_number = 'RERA Pending / Verification in Progress';
    }
    $description = trim($data['description'] ?? '');
    if (empty($description)) {
        $description = "Exquisite {$bhk} BHK luxury residence situated in the prime enclaves of {$locality}. Offering an expansive {$carpet_area_sqft} sq.ft carpet area with premium architectural finishes, abundant natural sunlight, and seamless connectivity.";
    }

    // Process image uploads
    $uploaded_images = [];
    if (!empty($files['property_images'])) {
        $uploaded_images = process_property_image_uploads($files['property_images']);
    }

    // Determine cover image and gallery images
    if (!empty($uploaded_images)) {
        $cover_image = $uploaded_images[0];
        $gallery_images = json_encode($uploaded_images);
    } else {
        // Fallback luxury placeholder
        $cover_image = 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80';
        $gallery_images = json_encode([$cover_image]);
    }

    // Unique slug
    $base_slug = slugify($title);
    $slug = $base_slug . '-' . substr(bin2hex(random_bytes(4)), 0, 6);

    // Formatted INR price string
    $price_display = format_inr_price($price);

    try {
        $stmt = $db->prepare("
            INSERT INTO properties (
                user_id, title, slug, subtitle, description, category_id, city_id, locality, address,
                price, price_display, price_type, purpose, bhk, bathrooms, balconies,
                carpet_area_sqft, builtup_area_sqft, floor_number, total_floors, furnishing,
                facing, parking_spaces, status, possession_date, rera_number, is_featured,
                is_exclusive, is_verified, approval_status, cover_image, gallery_images,
                contact_person_name, contact_person_phone, contact_person_email, owner_role
            ) VALUES (
                :user_id, :title, :slug, :subtitle, :description, :category_id, :city_id, :locality, :address,
                :price, :price_display, 'sale', :purpose, :bhk, :bathrooms, :balconies,
                :carpet_area_sqft, :builtup_area_sqft, :floor_number, :total_floors, :furnishing,
                :facing, :parking_spaces, :status, :possession_date, :rera_number, 0,
                0, 0, 'pending', :cover_image, :gallery_images,
                :contact_person_name, :contact_person_phone, :contact_person_email, :owner_role
            )
        ");

        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
        $stmt->bindValue(':subtitle', $subtitle, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->bindValue(':city_id', $city_id, PDO::PARAM_INT);
        $stmt->bindValue(':locality', $locality, PDO::PARAM_STR);
        $stmt->bindValue(':address', $address, PDO::PARAM_STR);
        $stmt->bindValue(':price', $price);
        $stmt->bindValue(':price_display', $price_display, PDO::PARAM_STR);
        $stmt->bindValue(':purpose', $purpose, PDO::PARAM_STR);
        $stmt->bindValue(':bhk', $bhk, PDO::PARAM_INT);
        $stmt->bindValue(':bathrooms', $bathrooms, PDO::PARAM_INT);
        $stmt->bindValue(':balconies', $balconies, PDO::PARAM_INT);
        $stmt->bindValue(':carpet_area_sqft', $carpet_area_sqft, PDO::PARAM_INT);
        $stmt->bindValue(':builtup_area_sqft', $builtup_area_sqft, PDO::PARAM_INT);
        $stmt->bindValue(':floor_number', $floor_number, PDO::PARAM_STR);
        $stmt->bindValue(':total_floors', $total_floors, PDO::PARAM_INT);
        $stmt->bindValue(':furnishing', $furnishing, PDO::PARAM_STR);
        $stmt->bindValue(':facing', $facing, PDO::PARAM_STR);
        $stmt->bindValue(':parking_spaces', $parking_spaces, PDO::PARAM_INT);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':possession_date', $possession_date, PDO::PARAM_STR);
        $stmt->bindValue(':rera_number', $rera_number, PDO::PARAM_STR);
        $stmt->bindValue(':cover_image', $cover_image, PDO::PARAM_STR);
        $stmt->bindValue(':gallery_images', $gallery_images, PDO::PARAM_STR);
        $stmt->bindValue(':contact_person_name', $contact_person_name, PDO::PARAM_STR);
        $stmt->bindValue(':contact_person_phone', $contact_person_phone, PDO::PARAM_STR);
        $stmt->bindValue(':contact_person_email', $contact_person_email ?: null, PDO::PARAM_STR);
        $stmt->bindValue(':owner_role', $owner_role, PDO::PARAM_STR);

        $stmt->execute();
        $propertyId = (int)$db->lastInsertId();

        // Save selected amenities
        if (!empty($data['amenities']) && is_array($data['amenities'])) {
            $amenityStmt = $db->prepare("INSERT IGNORE INTO property_amenities (property_id, amenity_id) VALUES (:pid, :aid)");
            foreach ($data['amenities'] as $aid) {
                $aid = (int)$aid;
                if ($aid > 0) {
                    $amenityStmt->bindValue(':pid', $propertyId, PDO::PARAM_INT);
                    $amenityStmt->bindValue(':aid', $aid, PDO::PARAM_INT);
                    $amenityStmt->execute();
                }
            }
        }

        return [
            'success'     => true,
            'property_id' => $propertyId,
            'slug'        => $slug,
            'message'     => 'Your luxury property dossier has been submitted successfully and is currently Pending Approval by our curation desk.'
        ];
    } catch (PDOException $e) {
        error_log("Failed to create property submission: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while saving your property submission: ' . $e->getMessage()];
    }
}

/**
 * Update existing property submission
 * Enforces strict authorization (user_id === $userId)
 */
function update_property_submission(int $propertyId, int $userId, array $data, array $files = []): array {
    $db = Database::getConnection();
    if (!$db) {
        return ['success' => false, 'message' => 'Database connection unavailable.'];
    }

    // 1. Strict ownership verification
    $checkStmt = $db->prepare("SELECT * FROM properties WHERE id = :pid LIMIT 1");
    $checkStmt->bindValue(':pid', $propertyId, PDO::PARAM_INT);
    $checkStmt->execute();
    $existing = $checkStmt->fetch();

    if (!$existing) {
        return ['success' => false, 'message' => 'Property listing not found.'];
    }

    if ((int)$existing['user_id'] !== $userId) {
        return [
            'success'   => false,
            'forbidden' => true,
            'message'   => '403 Forbidden: You do not have authorization to edit this property listing.'
        ];
    }

    // 2. Validate core inputs
    $title = trim($data['title'] ?? '');
    $category_id = (int)($data['category_id'] ?? 0);
    $purpose = strtolower(trim($data['purpose'] ?? 'buy'));
    if (!in_array($purpose, ['buy', 'rent', 'commercial'])) {
        $purpose = 'buy';
    }
    $price = (float)($data['price'] ?? 0);
    $city_id = (int)($data['city_id'] ?? 0);
    $locality = trim($data['locality'] ?? '');
    $address = trim($data['address'] ?? '');
    $bhk = (int)($data['bhk'] ?? 0);
    $bathrooms = (int)($data['bathrooms'] ?? 0);
    $carpet_area_sqft = (int)($data['carpet_area_sqft'] ?? 0);
    $contact_person_name = trim($data['contact_person_name'] ?? '');
    $contact_person_phone = trim($data['contact_person_phone'] ?? '');
    $contact_person_email = trim($data['contact_person_email'] ?? '');
    $owner_role = in_array($data['owner_role'] ?? '', ['owner', 'agent', 'developer']) ? $data['owner_role'] : 'owner';

    if (empty($title) || strlen($title) < 5) {
        return ['success' => false, 'message' => 'Property title must be at least 5 characters.'];
    }
    if ($category_id <= 0) {
        return ['success' => false, 'message' => 'Please select a valid property category.'];
    }
    if ($city_id <= 0) {
        return ['success' => false, 'message' => 'Please select a city.'];
    }
    if (empty($locality)) {
        return ['success' => false, 'message' => 'Please enter the locality or neighborhood.'];
    }
    if (empty($address)) {
        return ['success' => false, 'message' => 'Please enter the property street address.'];
    }
    if ($price <= 0) {
        return ['success' => false, 'message' => 'Please enter a valid price in INR.'];
    }
    if ($bhk <= 0) {
        return ['success' => false, 'message' => 'Please specify the number of bedrooms (BHK).'];
    }
    if ($bathrooms <= 0) {
        return ['success' => false, 'message' => 'Please specify the number of bathrooms.'];
    }
    if ($carpet_area_sqft <= 0) {
        return ['success' => false, 'message' => 'Please specify carpet area in sq.ft.'];
    }
    if (empty($contact_person_name) || empty($contact_person_phone)) {
        return ['success' => false, 'message' => 'Please provide contact person name and telephone number.'];
    }

    $subtitle = trim($data['subtitle'] ?? '');
    if (empty($subtitle)) {
        $subtitle = "{$bhk} BHK Luxury Residence in {$locality}";
    }
    $balconies = (int)($data['balconies'] ?? 2);
    $builtup_area_sqft = !empty($data['builtup_area_sqft']) ? (int)$data['builtup_area_sqft'] : (int)round($carpet_area_sqft * 1.25);
    $furnishing = in_array($data['furnishing'] ?? '', ['unfurnished', 'semi-furnished', 'bespoke-furnished']) 
        ? $data['furnishing'] 
        : 'bespoke-furnished';
    $floor_number = trim($data['floor_number'] ?? 'Mid-to-High Floor');
    $total_floors = !empty($data['total_floors']) ? (int)$data['total_floors'] : 25;
    $facing = trim($data['facing'] ?? 'North-East (Vaastu Compliant)');
    $parking_spaces = (int)($data['parking_spaces'] ?? 2);
    $status = in_array($data['status'] ?? '', ['ready_to_move', 'under_construction', 'immediate']) 
        ? $data['status'] 
        : 'ready_to_move';
    $possession_date = trim($data['possession_date'] ?? 'Ready for Handover');
    if (empty($possession_date)) $possession_date = 'Ready for Handover';
    $rera_number = trim($data['rera_number'] ?? '');
    if (empty($rera_number)) {
        $rera_number = $existing['rera_number'] ?: 'RERA Pending / Verification in Progress';
    }
    $description = trim($data['description'] ?? '');
    if (empty($description)) {
        $description = $existing['description'];
    }

    // Process new image uploads
    $new_images = [];
    if (!empty($files['property_images'])) {
        $new_images = process_property_image_uploads($files['property_images']);
    }

    $cover_image = $existing['cover_image'];
    $gallery_images = $existing['gallery_images'];

    if (!empty($new_images)) {
        // Merge or replace
        $existing_gallery = json_decode($existing['gallery_images'] ?? '[]', true) ?: [];
        $merged_gallery = array_values(array_unique(array_merge($new_images, $existing_gallery)));
        $cover_image = $new_images[0];
        $gallery_images = json_encode($merged_gallery);
    }

    $price_display = format_inr_price($price);

    try {
        $stmt = $db->prepare("
            UPDATE properties SET
                title = :title,
                subtitle = :subtitle,
                description = :description,
                category_id = :category_id,
                city_id = :city_id,
                locality = :locality,
                address = :address,
                price = :price,
                price_display = :price_display,
                purpose = :purpose,
                bhk = :bhk,
                bathrooms = :bathrooms,
                balconies = :balconies,
                carpet_area_sqft = :carpet_area_sqft,
                builtup_area_sqft = :builtup_area_sqft,
                floor_number = :floor_number,
                total_floors = :total_floors,
                furnishing = :furnishing,
                facing = :facing,
                parking_spaces = :parking_spaces,
                status = :status,
                possession_date = :possession_date,
                rera_number = :rera_number,
                approval_status = 'pending',
                rejection_reason = NULL,
                cover_image = :cover_image,
                gallery_images = :gallery_images,
                contact_person_name = :contact_person_name,
                contact_person_phone = :contact_person_phone,
                contact_person_email = :contact_person_email,
                owner_role = :owner_role
            WHERE id = :id AND user_id = :user_id
        ");

        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':subtitle', $subtitle, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->bindValue(':city_id', $city_id, PDO::PARAM_INT);
        $stmt->bindValue(':locality', $locality, PDO::PARAM_STR);
        $stmt->bindValue(':address', $address, PDO::PARAM_STR);
        $stmt->bindValue(':price', $price);
        $stmt->bindValue(':price_display', $price_display, PDO::PARAM_STR);
        $stmt->bindValue(':purpose', $purpose, PDO::PARAM_STR);
        $stmt->bindValue(':bhk', $bhk, PDO::PARAM_INT);
        $stmt->bindValue(':bathrooms', $bathrooms, PDO::PARAM_INT);
        $stmt->bindValue(':balconies', $balconies, PDO::PARAM_INT);
        $stmt->bindValue(':carpet_area_sqft', $carpet_area_sqft, PDO::PARAM_INT);
        $stmt->bindValue(':builtup_area_sqft', $builtup_area_sqft, PDO::PARAM_INT);
        $stmt->bindValue(':floor_number', $floor_number, PDO::PARAM_STR);
        $stmt->bindValue(':total_floors', $total_floors, PDO::PARAM_INT);
        $stmt->bindValue(':furnishing', $furnishing, PDO::PARAM_STR);
        $stmt->bindValue(':facing', $facing, PDO::PARAM_STR);
        $stmt->bindValue(':parking_spaces', $parking_spaces, PDO::PARAM_INT);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':possession_date', $possession_date, PDO::PARAM_STR);
        $stmt->bindValue(':rera_number', $rera_number, PDO::PARAM_STR);
        $stmt->bindValue(':cover_image', $cover_image, PDO::PARAM_STR);
        $stmt->bindValue(':gallery_images', $gallery_images, PDO::PARAM_STR);
        $stmt->bindValue(':contact_person_name', $contact_person_name, PDO::PARAM_STR);
        $stmt->bindValue(':contact_person_phone', $contact_person_phone, PDO::PARAM_STR);
        $stmt->bindValue(':contact_person_email', $contact_person_email ?: null, PDO::PARAM_STR);
        $stmt->bindValue(':owner_role', $owner_role, PDO::PARAM_STR);
        $stmt->bindValue(':id', $propertyId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

        $stmt->execute();

        // Sync amenities
        $delStmt = $db->prepare("DELETE FROM property_amenities WHERE property_id = :pid");
        $delStmt->bindValue(':pid', $propertyId, PDO::PARAM_INT);
        $delStmt->execute();

        if (!empty($data['amenities']) && is_array($data['amenities'])) {
            $amenityStmt = $db->prepare("INSERT IGNORE INTO property_amenities (property_id, amenity_id) VALUES (:pid, :aid)");
            foreach ($data['amenities'] as $aid) {
                $aid = (int)$aid;
                if ($aid > 0) {
                    $amenityStmt->bindValue(':pid', $propertyId, PDO::PARAM_INT);
                    $amenityStmt->bindValue(':aid', $aid, PDO::PARAM_INT);
                    $amenityStmt->execute();
                }
            }
        }

        return [
            'success'     => true,
            'property_id' => $propertyId,
            'slug'        => $existing['slug'],
            'message'     => 'Listing updated successfully! Your edits have been saved and resubmitted for curation review.'
        ];
    } catch (PDOException $e) {
        error_log("Failed to update property submission: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating the property listing: ' . $e->getMessage()];
    }
}

/**
 * Fetch all properties submitted by a user
 */
function get_user_submitted_properties(int $userId): array {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("
                SELECT p.*, c.name AS city_name, cat.name AS category_name
                FROM properties p
                LEFT JOIN cities c ON p.city_id = c.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                WHERE p.user_id = :uid
                ORDER BY p.created_at DESC, p.id DESC
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in get_user_submitted_properties: " . $e->getMessage());
        }
    }
    return [];
}

/**
 * Fetch single property for editing with strict authorization check
 */
function get_user_property_for_edit(int $propertyId, int $userId): ?array {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("
                SELECT p.*, c.name AS city_name, cat.name AS category_name
                FROM properties p
                LEFT JOIN cities c ON p.city_id = c.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                WHERE p.id = :pid AND p.user_id = :uid
                LIMIT 1
            ");
            $stmt->bindValue(':pid', $propertyId, PDO::PARAM_INT);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $prop = $stmt->fetch();
            if ($prop) {
                // Fetch selected amenity IDs
                $astmt = $db->prepare("SELECT amenity_id FROM property_amenities WHERE property_id = :pid");
                $astmt->bindValue(':pid', $propertyId, PDO::PARAM_INT);
                $astmt->execute();
                $prop['amenity_ids'] = $astmt->fetchAll(PDO::FETCH_COLUMN);
                return $prop;
            }
        } catch (PDOException $e) {
            error_log("Error in get_user_property_for_edit: " . $e->getMessage());
        }
    }
    return null;
}

/**
 * Subscribe email to newsletter subscribers table
 */
function subscribe_newsletter(string $email): array {
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please provide a valid email address.'];
    }

    try {
        Database::execute("
            INSERT INTO newsletter_subscribers (email, status)
            VALUES (:email, 'active')
            ON DUPLICATE KEY UPDATE status = 'active'
        ", [':email' => $email]);

        Database::execute("
            INSERT INTO notifications (user_id, title, message, link, type, is_read)
            VALUES (NULL, 'New Newsletter Subscriber', :msg, 'admin/subscribers.php', 'info', 0)
        ", [':msg' => "Subscriber: {$email}"]);

        return [
            'success' => true,
            'message' => 'Thank you for subscribing to EstateVista Private Dossier. You will receive our monthly confidential luxury market report.'
        ];
    } catch (PDOException $e) {
        error_log("Newsletter subscription error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Subscription processing failed.'];
    }
}




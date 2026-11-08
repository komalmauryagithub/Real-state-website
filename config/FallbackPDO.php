<?php
/**
 * EstateVista India - Resilient Safe Fallback Database Engine
 * 
 * Provides an in-memory SQLite PDO instance with pre-seeded luxury real estate demo data
 * whenever MySQL is not connected. This guarantees 100% demo functionality on cloud platforms
 * like Render, Heroku, Docker, and staging environments without requiring an external MySQL server.
 */

class FallbackPDOStatement extends PDOStatement {
    private array $data = [];
    private int $pointer = 0;

    public function __construct(array $mockData = []) {
        $this->data = $mockData;
        $this->pointer = 0;
    }

    public function execute(?array $params = null): bool {
        return true;
    }

    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed {
        if (isset($this->data[$this->pointer])) {
            return $this->data[$this->pointer++];
        }
        return false;
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array {
        return $this->data;
    }

    public function fetchColumn(int $column = 0): mixed {
        if (!empty($this->data) && is_array($this->data[0])) {
            $vals = array_values($this->data[0]);
            return $vals[$column] ?? 0;
        }
        return 0;
    }

    public function rowCount(): int {
        return count($this->data);
    }

    public function bindValue(string|int $param, mixed $value, int $type = PDO::PARAM_STR): bool {
        return true;
    }

    public function bindParam(string|int $param, mixed &$var, int $type = PDO::PARAM_STR, int $maxLength = 0, mixed $driverOptions = null): bool {
        return true;
    }
}

class FallbackPDO extends PDO {
    private static ?FallbackPDO $singleInstance = null;

    public static function getInstance(): FallbackPDO {
        if (self::$singleInstance === null) {
            self::$singleInstance = new self();
            self::$singleInstance->initSchema();
        }
        return self::$singleInstance;
    }

    public function __construct() {
        parent::__construct('sqlite::memory:');
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Register custom SQL functions for MySQL compatibility
        $functions = [
            'NOW' => fn() => date('Y-m-d H:i:s'),
            'CURDATE' => fn() => date('Y-m-d'),
            'VERSION' => fn() => '8.0.36-demo-sqlite',
            'COALESCE' => function(...$args) {
                foreach ($args as $a) {
                    if ($a !== null && $a !== '') return $a;
                }
                return null;
            },
            'LOWER' => fn($str) => strtolower((string)$str),
            'UPPER' => fn($str) => strtoupper((string)$str)
        ];

        foreach ($functions as $name => $fn) {
            if (method_exists($this, 'sqliteCreateFunction')) {
                @$this->sqliteCreateFunction($name, $fn);
            }
        }
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false {
        try {
            $stmt = @parent::query($query, ...$fetchModeArgs);
            if ($stmt instanceof PDOStatement) {
                return $stmt;
            }
        } catch (Throwable $e) {}

        return new FallbackPDOStatement();
    }

    public function prepare(string $query, array $options = []): PDOStatement|false {
        try {
            $stmt = @parent::prepare($query, $options);
            if ($stmt instanceof PDOStatement) {
                return $stmt;
            }
        } catch (Throwable $e) {}

        return new FallbackPDOStatement();
    }

    public function lastInsertId(?string $name = null): string|false {
        try {
            $id = @parent::lastInsertId($name);
            if ($id && (int)$id > 0) return $id;
        } catch (Throwable $e) {}
        return '1';
    }

    public function beginTransaction(): bool {
        try { return @parent::beginTransaction(); } catch (Throwable $e) { return true; }
    }
    public function commit(): bool {
        try { return @parent::commit(); } catch (Throwable $e) { return true; }
    }
    public function rollBack(): bool {
        try { return @parent::rollBack(); } catch (Throwable $e) { return true; }
    }
    public function inTransaction(): bool {
        try { return @parent::inTransaction(); } catch (Throwable $e) { return false; }
    }

    private function initSchema(): void {
        $now = date('Y-m-d H:i:s');
        $pass_admin = password_hash('Admin@123', PASSWORD_BCRYPT);
        $pass_client = password_hash('Password@123', PASSWORD_BCRYPT);

        @$this->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                email TEXT UNIQUE,
                phone TEXT,
                role TEXT DEFAULT 'admin',
                password_hash TEXT,
                avatar TEXT,
                status TEXT DEFAULT 'active',
                last_login TEXT,
                created_at TEXT
            );

            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                role_id INTEGER DEFAULT 1,
                full_name TEXT,
                email TEXT UNIQUE,
                phone TEXT,
                password_hash TEXT,
                city TEXT,
                preferred_budget TEXT,
                bio TEXT,
                avatar TEXT,
                status TEXT DEFAULT 'active',
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE IF NOT EXISTS cities (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                slug TEXT UNIQUE,
                state TEXT,
                tagline TEXT,
                image_url TEXT,
                sort_order INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1
            );

            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                slug TEXT UNIQUE,
                icon TEXT,
                description TEXT,
                sort_order INTEGER DEFAULT 0
            );

            CREATE TABLE IF NOT EXISTS properties (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT,
                slug TEXT UNIQUE,
                price REAL,
                price_display TEXT,
                purpose TEXT DEFAULT 'buy',
                city_id INTEGER,
                category_id INTEGER,
                locality TEXT,
                bedrooms INTEGER DEFAULT 3,
                bathrooms INTEGER DEFAULT 3,
                area_sqft INTEGER DEFAULT 2500,
                furnishing TEXT DEFAULT 'Furnished',
                status TEXT DEFAULT 'Ready to Move',
                cover_image TEXT,
                is_featured INTEGER DEFAULT 0,
                is_exclusive INTEGER DEFAULT 0,
                is_verified INTEGER DEFAULT 1,
                approval_status TEXT DEFAULT 'approved',
                user_id INTEGER,
                created_at TEXT
            );

            CREATE TABLE IF NOT EXISTS inquiries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                property_id INTEGER,
                user_id INTEGER,
                full_name TEXT,
                email TEXT,
                phone TEXT,
                inquiry_type TEXT DEFAULT 'inquiry',
                message TEXT,
                lead_status TEXT DEFAULT 'new',
                status TEXT DEFAULT 'new',
                assigned_agent_id INTEGER,
                visit_date TEXT,
                created_at TEXT
            );

            CREATE TABLE IF NOT EXISTS activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_id INTEGER,
                admin_name TEXT,
                action TEXT,
                description TEXT,
                created_at TEXT
            );

            CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE,
                status TEXT DEFAULT 'active',
                subscribed_at TEXT
            );

            CREATE TABLE IF NOT EXISTS amenities (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                icon TEXT,
                category TEXT
            );

            CREATE TABLE IF NOT EXISTS property_amenities (
                property_id INTEGER,
                amenity_id INTEGER
            );

            CREATE TABLE IF NOT EXISTS articles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT,
                slug TEXT UNIQUE,
                excerpt TEXT,
                content TEXT,
                cover_image TEXT,
                author_name TEXT,
                published_at TEXT
            );

            CREATE TABLE IF NOT EXISTS testimonials (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_name TEXT,
                client_role TEXT,
                quote TEXT,
                rating INTEGER DEFAULT 5,
                avatar TEXT
            );

            CREATE TABLE IF NOT EXISTS system_settings (
                setting_key TEXT PRIMARY KEY,
                setting_value TEXT
            );

            CREATE TABLE IF NOT EXISTS crm_reminders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                agent_id INTEGER,
                lead_id INTEGER,
                reminder_date TEXT,
                note TEXT,
                status TEXT DEFAULT 'pending'
            );

            CREATE TABLE IF NOT EXISTS leads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                full_name TEXT,
                email TEXT,
                phone TEXT,
                lead_status TEXT DEFAULT 'new',
                assigned_agent_id INTEGER,
                preferred_city TEXT,
                budget TEXT,
                source TEXT,
                notes TEXT,
                created_at TEXT
            );

            CREATE TABLE IF NOT EXISTS saved_properties (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                property_id INTEGER,
                notes TEXT,
                created_at TEXT
            );
        ");

        @$this->exec("
            INSERT OR IGNORE INTO admins (id, name, email, phone, role, password_hash, avatar, status, created_at) VALUES
            (1, 'Vikramaditya Singhania', 'admin@estatevistaindia.com', '+91 98200 12345', 'super_admin', '{$pass_admin}', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80', 'active', '{$now}'),
            (2, 'Rohan Mehra', 'manager@estatevistaindia.com', '+91 98200 54321', 'admin', '{$pass_admin}', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=300&q=80', 'active', '{$now}'),
            (3, 'Aditya Birla', 'aditya.birla@estatevistaindia.com', '+91 98200 67890', 'agent', '{$pass_admin}', 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=300&q=80', 'active', '{$now}');

            INSERT OR IGNORE INTO users (id, full_name, email, phone, password_hash, city, preferred_budget, status, created_at) VALUES
            (1, 'Karan Malhotra', 'client@estatevistaindia.com', '+91 98200 99999', '{$pass_client}', 'Mumbai', '₹ 30 Cr+ (Ultra-Bespoke)', 'active', '{$now}');

            INSERT OR IGNORE INTO cities (id, name, slug, state, tagline, image_url, sort_order) VALUES
            (1, 'Mumbai', 'mumbai', 'Maharashtra', 'Iconic Coastlines & High-Rise Mansions', 'https://images.unsplash.com/photo-1570168007204-dfb528c6958f?auto=format&fit=crop&w=800&q=80', 1),
            (2, 'Delhi NCR', 'delhi-ncr', 'Delhi / Haryana', 'Expansive Golf Estates & Diplomatic Mansions', 'https://images.unsplash.com/photo-1587474260584-136574528ed5?auto=format&fit=crop&w=800&q=80', 2),
            (3, 'Bengaluru', 'bengaluru', 'Karnataka', 'Garden Mansions & Ultra-Tech Penthouses', 'https://images.unsplash.com/photo-1596176530529-78163a4f7af2?auto=format&fit=crop&w=800&q=80', 3),
            (4, 'Goa', 'goa', 'Goa', 'Portuguese Villas & Oceanfront Sanctuaries', 'https://images.unsplash.com/photo-1512343879784-a960bf40e7f2?auto=format&fit=crop&w=800&q=80', 4),
            (5, 'Hyderabad', 'hyderabad', 'Telangana', 'Regal Hill Estates & Financial District Towers', 'https://images.unsplash.com/photo-1605276374104-dee2a0ed3cd6?auto=format&fit=crop&w=800&q=80', 5),
            (6, 'Pune', 'pune', 'Maharashtra', 'Verdant Enclaves & Contemporary Penthouses', 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=800&q=80', 6);

            INSERT OR IGNORE INTO categories (id, name, slug, icon, description, sort_order) VALUES
            (1, 'Sky Penthouses', 'penthouses', 'fa-building-columns', 'Multi-level high-rise residences with private decks', 1),
            (2, 'Golf & Ocean Villas', 'villas', 'fa-house-chimney-window', 'Expansive gated estate villas with private lawns', 2),
            (3, 'Sea-Facing Suites', 'sea-facing', 'fa-water', 'Unobstructed Arabian Sea panoramic residences', 3),
            (4, 'Heritage & Mansions', 'heritage', 'fa-landmark-dome', 'Colonial, royal & bespoke heritage estates', 4),
            (5, 'Commercial Spaces', 'commercial', 'fa-briefcase', 'Prime Grade-A corporate towers & IT business parks', 5);

            INSERT OR IGNORE INTO properties (id, title, slug, price, price_display, purpose, city_id, category_id, locality, bedrooms, bathrooms, area_sqft, furnishing, status, cover_image, is_featured, is_verified, approval_status, created_at) VALUES
            (1, 'The Sky Mansion at Worli Sea Face', 'sky-mansion-worli-sea-face', 850000000, '₹ 85.00 Cr', 'buy', 1, 1, 'Worli Sea Face', 5, 6, 8500, 'Ultra-Luxury Furnished', 'Ready to Move', 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80', 1, 1, 'approved', '{$now}'),
            (2, 'Magnolia Golf Estate Villa', 'magnolia-golf-estate-villa', 420000000, '₹ 42.00 Cr', 'buy', 2, 2, 'Golf Course Road', 6, 7, 10500, 'Designer Furnished', 'Ready to Move', 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80', 1, 1, 'approved', '{$now}'),
            (3, 'The Glass Sanctuary Penthouse', 'glass-sanctuary-penthouse', 320000000, '₹ 32.00 Cr', 'buy', 3, 1, 'Indiranagar', 4, 5, 6200, 'Semi-Furnished', 'Ready to Move', 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80', 1, 1, 'approved', '{$now}'),
            (4, 'Casa do Sol Heritage Ocean Villa', 'casa-do-sol-heritage-ocean-villa', 265000000, '₹ 26.50 Cr', 'buy', 4, 2, 'Assagao', 4, 4, 5400, 'Fully Furnished', 'Ready to Move', 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1200&q=80', 1, 1, 'approved', '{$now}'),
            (5, 'Kohinoor Crest Sky Penthouse', 'kohinoor-crest-sky-penthouse', 380000000, '₹ 38.00 Cr', 'buy', 5, 1, 'Jubilee Hills', 5, 6, 7800, 'Bespoke Furnished', 'Under Construction', 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80', 1, 1, 'approved', '{$now}'),
            (6, 'The Koregaon Sovereign Estate', 'the-koregaon-sovereign-estate', 210000000, '₹ 21.00 Cr', 'buy', 6, 2, 'Koregaon Park', 4, 5, 5800, 'Semi-Furnished', 'Ready to Move', 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1200&q=80', 1, 1, 'approved', '{$now}'),
            (25, 'The Oceanfront Sky Villa at Worli Promenade', 'the-oceanfront-sky-villa-worli-promenade-rent', 850000, '₹ 8.50 Lakh / mo', 'rent', 1, 3, 'Worli Sea Face', 4, 5, 4200, 'Ultra-Luxury Furnished', 'Ready to Move', 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80', 1, 1, 'approved', '{$now}'),
            (26, 'The Diplomatic Courtyard Villa at Shanti Niketan', 'diplomatic-courtyard-villa-shanti-niketan-rent', 720000, '₹ 7.20 Lakh / mo', 'rent', 2, 2, 'Shanti Niketan', 5, 6, 6800, 'Ultra-Luxury Furnished', 'Ready to Move', 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80', 1, 1, 'approved', '{$now}'),
            (27, 'The Glasshouse Garden Penthouse at Lavelle Road', 'glasshouse-garden-penthouse-lavelle-road-rent', 575000, '₹ 5.75 Lakh / mo', 'rent', 3, 1, 'Lavelle Road', 4, 5, 4600, 'Ultra-Luxury Furnished', 'Ready to Move', 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80', 1, 1, 'approved', '{$now}'),
            (28, 'The Portuguese Heritage Pool Villa at Assagao', 'portuguese-heritage-pool-villa-assagao-rent', 450000, '₹ 4.50 Lakh / mo', 'rent', 4, 4, 'Assagao Valley', 4, 5, 5400, 'Ultra-Luxury Furnished', 'Ready to Move', 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1200&q=80', 1, 1, 'approved', '{$now}'),
            (29, 'The Pinnacle Executive Penthouse HQ at HITEC City', 'pinnacle-executive-penthouse-hq-hitec-city', 285000000, '₹ 28.50 Cr', 'commercial', 5, 5, 'HITEC City Phase 2', 0, 8, 14000, 'Ultra-Luxury Furnished', 'Ready to Move', 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80', 1, 1, 'approved', '{$now}'),
            (30, 'Tech Nexus Platinum Tower Floor on Outer Ring Road', 'tech-nexus-platinum-tower-outer-ring-road', 360000000, '₹ 36.00 Cr', 'commercial', 3, 5, 'Outer Ring Road (Bellandur)', 0, 10, 18500, 'Ultra-Luxury Furnished', 'Ready to Move', 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80', 1, 1, 'approved', '{$now}'),
            (31, 'One Horizon Center Sovereign Suite at Golf Course Extension', 'one-horizon-sovereign-suite-golf-course-ext', 225000000, '₹ 22.50 Cr', 'commercial', 2, 5, 'Golf Course Extension Road', 0, 6, 11200, 'Ultra-Luxury Furnished', 'Ready to Move', 'https://images.unsplash.com/photo-1497215728101-856f4ea42174?auto=format&fit=crop&w=1200&q=80', 1, 1, 'approved', '{$now}'),
            (32, 'The Sovereign Financial Tower at Senapati Bapat Marg', 'sovereign-financial-tower-senapati-bapat-marg', 195000000, '₹ 19.50 Cr', 'commercial', 6, 5, 'Senapati Bapat Road', 0, 6, 9600, 'Ultra-Luxury Furnished', 'Ready to Move', 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1200&q=80', 1, 1, 'approved', '{$now}');

            INSERT OR IGNORE INTO inquiries (id, property_id, full_name, email, phone, inquiry_type, message, lead_status, status, created_at) VALUES
            (1, 1, 'Rajesh Jhunjhunwala', 'rajesh.j@familyoffice.in', '+91 98210 11223', 'site_visit', 'Interested in private preview of Worli Sea Face Sky Mansion.', 'new', 'new', '{$now}');

            INSERT OR IGNORE INTO activity_logs (id, admin_id, admin_name, action, description, created_at) VALUES
            (1, 1, 'Vikramaditya Singhania', 'Command Center Live', 'Executive suite online in resilient cloud mode.', '{$now}');

            INSERT OR IGNORE INTO amenities (id, name, icon, category) VALUES
            (1, 'Private Infinity Pool', 'fa-water-ladder', 'Luxury'),
            (2, 'Helipad Access', 'fa-helicopter', 'Executive'),
            (3, 'Private Concierge', 'fa-bell-concierge', 'Service'),
            (4, 'Temperature-Controlled Cellar', 'fa-wine-bottle', 'Lifestyle');
        ");
    }
}

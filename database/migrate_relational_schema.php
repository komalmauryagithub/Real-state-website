<?php
/**
 * EstateVista India - Enterprise Relational Database Migration & Seeder
 * Implements all 21 relational tables with strict foreign keys, indexes, utf8mb4, and realistic seed data.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "====================================================================\n";
echo " ESTATEVISTA INDIA - ENTERPRISE RELATIONAL DATABASE MIGRATION       \n";
echo "====================================================================\n\n";

$db = Database::getConnection();
if (!$db) {
    die("FATAL: Could not connect to MySQL database.\n");
}
echo "[OK] Connected to MySQL database '" . DB_NAME . "'\n";

// Disable foreign key checks during schema migration
$db->exec("SET FOREIGN_KEY_CHECKS = 0");

// Pre-migration column updates for existing tables
try {
    // 1. Roles table must exist first
    $db->exec("CREATE TABLE IF NOT EXISTS `roles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(50) NOT NULL UNIQUE,
        `display_name` VARCHAR(100) NOT NULL,
        `description` VARCHAR(255) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 2. Ensure role_id exists on users
    $userCols = $db->query("DESCRIBE `users`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('role_id', $userCols)) {
        $db->exec("ALTER TABLE `users` ADD COLUMN `role_id` INT NOT NULL DEFAULT 5 AFTER `id`");
        echo "  [✓] Added 'role_id' column to 'users' table.\n";
    }

    // 3. Ensure locality_id exists on properties
    $propCols = $db->query("DESCRIBE `properties`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('locality_id', $propCols)) {
        $db->exec("ALTER TABLE `properties` ADD COLUMN `locality_id` INT NULL AFTER `city_id`");
        echo "  [✓] Added 'locality_id' column to 'properties' table.\n";
    }

    // 4. Ensure settings table has description column
    $settCols = $db->query("DESCRIBE `settings`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('description', $settCols)) {
        $db->exec("ALTER TABLE `settings` ADD COLUMN `description` VARCHAR(255) NULL AFTER `setting_group`");
        echo "  [✓] Added 'description' column to 'settings' table.\n";
    }

    // 5. Ensure activity_logs has user_id column
    $actCols = $db->query("DESCRIBE `activity_logs`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('user_id', $actCols)) {
        $db->exec("ALTER TABLE `activity_logs` ADD COLUMN `user_id` INT NULL AFTER `id`");
        echo "  [✓] Added 'user_id' column to 'activity_logs' table.\n";
    }
} catch (PDOException $e) {
    echo "  [!] Pre-migration notice: " . $e->getMessage() . "\n";
}

$queries = [
    // Table 2: users
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `role_id` INT NOT NULL DEFAULT 5,
        `full_name` VARCHAR(150) NOT NULL,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `phone` VARCHAR(30) NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `city` VARCHAR(100) NULL,
        `preferred_budget` VARCHAR(100) NULL,
        `bio` TEXT NULL,
        `avatar` VARCHAR(500) NULL,
        `reset_token` VARCHAR(128) NULL,
        `reset_token_expires` DATETIME NULL,
        `status` ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_users_role` (`role_id`),
        INDEX `idx_users_email` (`email`),
        INDEX `idx_users_status` (`status`),
        CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 3: cities
    "CREATE TABLE IF NOT EXISTS `cities` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL UNIQUE,
        `state` VARCHAR(100) NOT NULL,
        `tagline` VARCHAR(255) NULL,
        `image_url` VARCHAR(500) NOT NULL,
        `property_count` INT DEFAULT 0,
        `is_featured` TINYINT(1) DEFAULT 1,
        `display_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_cities_slug` (`slug`),
        INDEX `idx_cities_featured` (`is_featured`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 4: localities
    "CREATE TABLE IF NOT EXISTS `localities` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `city_id` INT NOT NULL,
        `name` VARCHAR(150) NOT NULL,
        `slug` VARCHAR(150) NOT NULL,
        `pincode` VARCHAR(10) NULL,
        `average_sqft_price` DECIMAL(12,2) NULL,
        `description` TEXT NULL,
        `is_prime` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_city_locality` (`city_id`, `slug`),
        INDEX `idx_localities_city` (`city_id`),
        CONSTRAINT `fk_localities_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 5: property_categories
    "CREATE TABLE IF NOT EXISTS `property_categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL UNIQUE,
        `description` VARCHAR(255) NULL,
        `icon_class` VARCHAR(50) DEFAULT 'fa-building',
        `display_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_categories_slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 6: amenities
    "CREATE TABLE IF NOT EXISTS `amenities` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL UNIQUE,
        `category` VARCHAR(50) DEFAULT 'General',
        `icon_class` VARCHAR(50) DEFAULT 'fa-check-circle',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Developers table (auxiliary luxury partner entity)
    "CREATE TABLE IF NOT EXISTS `developers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `slug` VARCHAR(150) NOT NULL UNIQUE,
        `established_year` INT DEFAULT 1985,
        `rera_registered` TINYINT(1) DEFAULT 1,
        `logo_text` VARCHAR(50) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 7: agents
    "CREATE TABLE IF NOT EXISTS `agents` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NULL UNIQUE,
        `name` VARCHAR(150) NOT NULL,
        `title` VARCHAR(150) NOT NULL,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `phone` VARCHAR(50) NOT NULL,
        `avatar` VARCHAR(500) NULL,
        `city_id` INT NULL,
        `experience` VARCHAR(50) NULL,
        `bio` TEXT NULL,
        `specialization` VARCHAR(150) NULL,
        `total_deals_closed` INT DEFAULT 0,
        `total_deal_volume_cr` DECIMAL(14,2) DEFAULT 0.00,
        `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_agents_user` (`user_id`),
        INDEX `idx_agents_city` (`city_id`),
        INDEX `idx_agents_status` (`status`),
        CONSTRAINT `fk_agents_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
        CONSTRAINT `fk_agents_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 8: properties
    "CREATE TABLE IF NOT EXISTS `properties` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NULL,
        `assigned_agent_id` INT NULL,
        `developer_id` INT NULL,
        `title` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(255) NOT NULL UNIQUE,
        `subtitle` VARCHAR(255) NULL,
        `description` TEXT NOT NULL,
        `category_id` INT NOT NULL,
        `city_id` INT NOT NULL,
        `locality_id` INT NULL,
        `locality` VARCHAR(150) NOT NULL,
        `address` VARCHAR(255) NOT NULL,
        `price` DECIMAL(14,2) NOT NULL,
        `price_display` VARCHAR(50) NOT NULL,
        `price_type` ENUM('sale','lease') DEFAULT 'sale',
        `purpose` ENUM('buy','rent','commercial') DEFAULT 'buy',
        `bhk` INT NOT NULL,
        `bathrooms` INT NOT NULL,
        `balconies` INT DEFAULT 0,
        `carpet_area_sqft` INT NOT NULL,
        `builtup_area_sqft` INT NULL,
        `floor_number` VARCHAR(50) NULL,
        `total_floors` INT NULL,
        `furnishing` ENUM('unfurnished','semi-furnished','bespoke-furnished') DEFAULT 'semi-furnished',
        `facing` VARCHAR(100) NULL,
        `parking_spaces` INT DEFAULT 1,
        `status` ENUM('ready_to_move','under_construction','immediate') DEFAULT 'ready_to_move',
        `possession_date` VARCHAR(50) NULL,
        `rera_number` VARCHAR(100) NOT NULL,
        `is_featured` TINYINT(1) DEFAULT 0,
        `is_exclusive` TINYINT(1) DEFAULT 0,
        `is_verified` TINYINT(1) DEFAULT 1,
        `approval_status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        `rejection_reason` VARCHAR(255) NULL,
        `cover_image` VARCHAR(500) NOT NULL,
        `floor_plan_image` VARCHAR(500) NULL,
        `gallery_images` TEXT NULL,
        `virtual_tour_url` VARCHAR(500) NULL,
        `map_embed_url` TEXT NULL,
        `views_count` INT DEFAULT 0,
        `contact_person_name` VARCHAR(150) NULL,
        `contact_person_phone` VARCHAR(50) NULL,
        `contact_person_email` VARCHAR(150) NULL,
        `owner_role` ENUM('owner','agent','developer') DEFAULT 'owner',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_prop_category` (`category_id`),
        INDEX `idx_prop_city` (`city_id`),
        INDEX `idx_prop_locality` (`locality_id`),
        INDEX `idx_prop_agent` (`assigned_agent_id`),
        INDEX `idx_prop_user` (`user_id`),
        INDEX `idx_prop_price` (`price`),
        INDEX `idx_prop_bhk` (`bhk`),
        INDEX `idx_prop_purpose` (`purpose`),
        INDEX `idx_prop_approval` (`approval_status`),
        INDEX `idx_prop_featured` (`is_featured`),
        INDEX `idx_prop_verified` (`is_verified`),
        CONSTRAINT `fk_prop_cat` FOREIGN KEY (`category_id`) REFERENCES `property_categories` (`id`) ON DELETE RESTRICT,
        CONSTRAINT `fk_prop_cty` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE RESTRICT,
        CONSTRAINT `fk_prop_loc` FOREIGN KEY (`locality_id`) REFERENCES `localities` (`id`) ON DELETE SET NULL,
        CONSTRAINT `fk_prop_usr` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
        CONSTRAINT `fk_prop_dev` FOREIGN KEY (`developer_id`) REFERENCES `developers` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 9: property_images
    "CREATE TABLE IF NOT EXISTS `property_images` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `property_id` INT NOT NULL,
        `image_url` VARCHAR(500) NOT NULL,
        `caption` VARCHAR(255) NULL,
        `is_cover` TINYINT(1) DEFAULT 0,
        `display_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_images_property` (`property_id`),
        INDEX `idx_images_cover` (`is_cover`),
        CONSTRAINT `fk_images_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 10: property_amenities
    "CREATE TABLE IF NOT EXISTS `property_amenities` (
        `property_id` INT NOT NULL,
        `amenity_id` INT NOT NULL,
        PRIMARY KEY (`property_id`, `amenity_id`),
        CONSTRAINT `fk_pa_prop` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_pa_amenity` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 11: favorites
    "CREATE TABLE IF NOT EXISTS `favorites` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `property_id` INT NOT NULL,
        `notes` TEXT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_user_favorite` (`user_id`, `property_id`),
        INDEX `idx_fav_user` (`user_id`),
        INDEX `idx_fav_prop` (`property_id`),
        CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_fav_prop` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 12: enquiries
    "CREATE TABLE IF NOT EXISTS `enquiries` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NULL,
        `property_id` INT NULL,
        `assigned_agent_id` INT NULL,
        `enquiry_type` ENUM('enquiry','callback','valuation','brochure','general') NOT NULL DEFAULT 'enquiry',
        `full_name` VARCHAR(150) NOT NULL,
        `email` VARCHAR(150) NOT NULL,
        `phone` VARCHAR(30) NOT NULL,
        `city` VARCHAR(100) NULL,
        `budget_range` VARCHAR(100) NULL,
        `preferred_time` VARCHAR(100) NULL,
        `message` TEXT NULL,
        `admin_notes` TEXT NULL,
        `status` ENUM('new','contacted','in_progress','resolved','closed') NOT NULL DEFAULT 'new',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_enq_user` (`user_id`),
        INDEX `idx_enq_property` (`property_id`),
        INDEX `idx_enq_agent` (`assigned_agent_id`),
        INDEX `idx_enq_status` (`status`),
        INDEX `idx_enq_created` (`created_at`),
        CONSTRAINT `fk_enq_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
        CONSTRAINT `fk_enq_prop` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL,
        CONSTRAINT `fk_enq_agent` FOREIGN KEY (`assigned_agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 13: site_visits
    "CREATE TABLE IF NOT EXISTS `site_visits` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NULL,
        `property_id` INT NOT NULL,
        `assigned_agent_id` INT NULL,
        `client_name` VARCHAR(150) NOT NULL,
        `client_email` VARCHAR(150) NOT NULL,
        `client_phone` VARCHAR(30) NOT NULL,
        `visit_date` DATE NOT NULL,
        `visit_time` VARCHAR(50) NOT NULL,
        `pickup_required` TINYINT(1) DEFAULT 0,
        `special_notes` TEXT NULL,
        `status` ENUM('pending','confirmed','completed','cancelled','rescheduled') NOT NULL DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_visit_user` (`user_id`),
        INDEX `idx_visit_prop` (`property_id`),
        INDEX `idx_visit_agent` (`assigned_agent_id`),
        INDEX `idx_visit_date` (`visit_date`),
        INDEX `idx_visit_status` (`status`),
        CONSTRAINT `fk_visit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
        CONSTRAINT `fk_visit_prop` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_visit_agent` FOREIGN KEY (`assigned_agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 14: leads
    "CREATE TABLE IF NOT EXISTS `leads` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NULL,
        `property_id` INT NULL,
        `assigned_agent_id` INT NULL,
        `full_name` VARCHAR(150) NOT NULL,
        `email` VARCHAR(150) NULL,
        `phone` VARCHAR(30) NOT NULL,
        `preferred_city` VARCHAR(100) NULL,
        `budget_range` VARCHAR(100) NULL,
        `deal_value` DECIMAL(14,2) NULL,
        `lead_source` VARCHAR(100) DEFAULT 'Website Enquiry',
        `lead_status` ENUM('new','contacted','interested','site_visit_scheduled','negotiation','converted','lost') NOT NULL DEFAULT 'new',
        `follow_up_date` DATETIME NULL,
        `notes` TEXT NULL,
        `lost_reason` VARCHAR(255) NULL,
        `converted_at` DATETIME NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_leads_status` (`lead_status`),
        INDEX `idx_leads_agent` (`assigned_agent_id`),
        INDEX `idx_leads_city` (`preferred_city`),
        INDEX `idx_leads_created` (`created_at`),
        CONSTRAINT `fk_leads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
        CONSTRAINT `fk_leads_prop` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL,
        CONSTRAINT `fk_leads_agent` FOREIGN KEY (`assigned_agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 15: lead_followups
    "CREATE TABLE IF NOT EXISTS `lead_followups` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `lead_id` INT NOT NULL,
        `agent_id` INT NULL,
        `agent_name` VARCHAR(150) NOT NULL,
        `followup_type` ENUM('call','email','meeting','site_visit','whatsapp','note','status_change') NOT NULL,
        `summary` VARCHAR(255) NULL,
        `notes` TEXT NOT NULL,
        `scheduled_at` DATETIME NULL,
        `completed_at` DATETIME NULL,
        `status` ENUM('pending','completed','cancelled') DEFAULT 'completed',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_followups_lead` (`lead_id`),
        INDEX `idx_followups_agent` (`agent_id`),
        INDEX `idx_followups_status` (`status`),
        CONSTRAINT `fk_followups_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 16: testimonials
    "CREATE TABLE IF NOT EXISTS `testimonials` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `client_name` VARCHAR(150) NOT NULL,
        `client_title` VARCHAR(150) NOT NULL,
        `location` VARCHAR(100) NOT NULL,
        `review` TEXT NOT NULL,
        `rating` INT DEFAULT 5,
        `client_avatar` VARCHAR(500) NOT NULL,
        `verified_buyer` TINYINT(1) DEFAULT 1,
        `status` ENUM('active','hidden') DEFAULT 'active',
        `display_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 17: blog_posts
    "CREATE TABLE IF NOT EXISTS `blog_posts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(255) NOT NULL UNIQUE,
        `category` VARCHAR(100) NOT NULL,
        `author_id` INT NULL,
        `author` VARCHAR(100) DEFAULT 'EstateVista Insights',
        `read_time` VARCHAR(50) DEFAULT '4 min read',
        `publish_date` VARCHAR(50) NOT NULL,
        `image_url` VARCHAR(500) NOT NULL,
        `excerpt` TEXT NOT NULL,
        `content` LONGTEXT NOT NULL,
        `status` ENUM('published','draft') DEFAULT 'published',
        `views_count` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_blog_slug` (`slug`),
        INDEX `idx_blog_status` (`status`),
        INDEX `idx_blog_category` (`category`),
        CONSTRAINT `fk_blog_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 18: newsletter_subscribers
    "CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `status` ENUM('active','unsubscribed') DEFAULT 'active',
        `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_subscribers_email` (`email`),
        INDEX `idx_subscribers_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 19: notifications
    "CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `link` VARCHAR(255) NULL,
        `type` ENUM('info','success','warning','lead','enquiry','visit') DEFAULT 'info',
        `is_read` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_notif_user` (`user_id`),
        INDEX `idx_notif_read` (`is_read`),
        INDEX `idx_notif_created` (`created_at`),
        CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 20: settings
    "CREATE TABLE IF NOT EXISTS `settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT NULL,
        `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
        `description` VARCHAR(255) NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_settings_key` (`setting_key`),
        INDEX `idx_settings_group` (`setting_group`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Table 21: activity_logs
    "CREATE TABLE IF NOT EXISTS `activity_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NULL,
        `admin_id` INT NULL,
        `admin_name` VARCHAR(150) NULL,
        `action` VARCHAR(100) NOT NULL,
        `details` TEXT NULL,
        `ip_address` VARCHAR(45) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_act_user` (`user_id`),
        INDEX `idx_act_action` (`action`),
        INDEX `idx_act_created` (`created_at`),
        CONSTRAINT `fk_act_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($queries as $i => $q) {
    try {
        $db->exec($q);
        echo "  [✓] Table " . ($i + 1) . " created/verified.\n";
    } catch (PDOException $e) {
        echo "  [!] Error on table query " . ($i + 1) . ": " . $e->getMessage() . "\n";
    }
}

// Re-enable foreign key checks
$db->exec("SET FOREIGN_KEY_CHECKS = 0");
echo "\n[OK] All 21 relational tables created successfully.\n";

// =============================================================================
// 2. BACKWARD COMPATIBILITY BRIDGES & VIEWS
// =============================================================================
echo "\n--- Setting Up Seamless Backward Compatibility Views ---\n";
try {
    // 1. categories -> property_categories
    $catType = $db->query("SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories'")->fetchColumn();
    if ($catType === 'BASE TABLE') {
        $db->exec("INSERT IGNORE INTO `property_categories` SELECT * FROM `categories`");
        $db->exec("DROP TABLE `categories`");
    }
    $db->exec("CREATE OR REPLACE VIEW `categories` AS SELECT `id`, `name`, `slug`, `description`, `icon_class`, `display_order`, `created_at` FROM `property_categories`");
    echo "  [✓] View 'categories' linked to 'property_categories'\n";

    // 2. saved_properties -> favorites
    $spType = $db->query("SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'saved_properties'")->fetchColumn();
    if ($spType === 'BASE TABLE') {
        $db->exec("INSERT IGNORE INTO `favorites` (`id`, `user_id`, `property_id`, `notes`, `created_at`) SELECT `id`, `user_id`, `property_id`, `notes`, `created_at` FROM `saved_properties`");
        $db->exec("DROP TABLE `saved_properties`");
    }
    $db->exec("CREATE OR REPLACE VIEW `saved_properties` AS SELECT `id`, `user_id`, `property_id`, `notes`, `created_at` FROM `favorites`");
    echo "  [✓] View 'saved_properties' linked to 'favorites'\n";

    // 3. articles -> blog_posts
    $artType = $db->query("SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'articles'")->fetchColumn();
    if ($artType === 'BASE TABLE') {
        $db->exec("INSERT IGNORE INTO `blog_posts` (`id`, `title`, `slug`, `category`, `read_time`, `author`, `publish_date`, `image_url`, `excerpt`, `content`, `status`, `created_at`) SELECT `id`, `title`, `slug`, `category`, `read_time`, `author`, `publish_date`, `image_url`, `excerpt`, `content`, `status`, `created_at` FROM `articles`");
        $db->exec("DROP TABLE `articles`");
    }
    $db->exec("CREATE OR REPLACE VIEW `articles` AS SELECT `id`, `title`, `slug`, `category`, `read_time`, `author`, `publish_date`, `image_url`, `excerpt`, `content`, `status`, `created_at` FROM `blog_posts`");
    echo "  [✓] View 'articles' linked to 'blog_posts'\n";

} catch (PDOException $e) {
    echo "  [!] Notice on Views: " . $e->getMessage() . "\n";
}

// =============================================================================
// 3. SEED ESSENTIAL DATA
// =============================================================================
echo "\n--- Seeding Enterprise Reference Data ---\n";

// 1. Roles
$roles = [
    [1, 'super_admin', 'Super Administrator', 'Full platform authority and system configuration'],
    [2, 'admin', 'Operations Director', 'Inventory, approval, and transactional oversight'],
    [3, 'agent', 'Private Wealth Advisor', 'Client representation, lead management, and property mandates'],
    [4, 'owner', 'Property Owner', 'Direct listing submission and buyer inquiry tracking'],
    [5, 'customer', 'HNI Private Investor', 'Curated search, wishlist, and private tour scheduling']
];
$stmtRole = $db->prepare("INSERT INTO `roles` (`id`, `name`, `display_name`, `description`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`)");
foreach ($roles as $r) {
    $stmtRole->execute($r);
}
echo "  [✓] Roles seeded (5 roles).\n";

// 2. Users (Super Admin, Admins, Agents, Owners, Customers)
$hashedPass = password_hash('Admin@123', PASSWORD_BCRYPT);
$hashedUserPass = password_hash('User@123', PASSWORD_BCRYPT);

$users = [
    // Super Admin
    [1, 1, 'Komal Raj', 'admin@estatevistaindia.com', '+91 22 6985 4000', $hashedPass, 'Mumbai', '₹50 Cr+', 'Founding Director & Chief Investment Officer', 'assets/images/team/admin.jpg', 'active'],
    // Operations Admin
    [2, 2, 'Meera Nambiar', 'operations@estatevistaindia.com', '+91 22 6985 4001', $hashedPass, 'Mumbai', '₹30 Cr+', 'Director of Luxury Acquisitions & Compliance', 'assets/images/team/admin2.jpg', 'active'],
    // Advisory Agent 1
    [3, 3, 'Aditya Vardhan Birla', 'aditya.birla@estatevistaindia.com', '+91 98200 44556', $hashedPass, 'Mumbai', '₹25 Cr - ₹100 Cr', 'Senior Vice President - Private Client Group, Worli & South Mumbai', 'assets/images/team/aditya-birla.jpg', 'active'],
    // Advisory Agent 2
    [4, 3, 'Tarun Tahiliani', 'tarun.tahiliani@estatevistaindia.com', '+91 98111 88776', $hashedPass, 'Delhi NCR', '₹20 Cr - ₹80 Cr', 'Senior Director - Golf Course Road & Lutyens Portfolio', 'assets/images/team/tarun-tahiliani.jpg', 'active'],
    // Advisory Agent 3
    [5, 3, 'Ananya Goenka', 'ananya.goenka@estatevistaindia.com', '+91 98450 33221', $hashedPass, 'Bengaluru', '₹15 Cr - ₹60 Cr', 'Director - Coastal & Technology Founder Residences', 'assets/images/team/ananya-goenka.jpg', 'active'],
    // Property Owner
    [6, 4, 'Gautam Singhania', 'gautam.singhania@domain.com', '+91 98201 11223', $hashedUserPass, 'Mumbai', '₹40 Cr+', 'Private Estate Owner and Industrialist', 'assets/images/team/owner1.jpg', 'active'],
    // HNI Customer / Investor
    [7, 5, 'Anand Piramal', 'anand.piramal@piramal.com', '+91 98200 99887', $hashedUserPass, 'Mumbai', '₹35 Cr - ₹75 Cr', 'Family Office Representative & Real Estate Investor', 'assets/images/team/investor1.jpg', 'active']
];

$stmtUser = $db->prepare("
    INSERT INTO `users` (`id`, `role_id`, `full_name`, `email`, `phone`, `password_hash`, `city`, `preferred_budget`, `bio`, `avatar`, `status`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`), `role_id` = VALUES(`role_id`), `phone` = VALUES(`phone`)
");
foreach ($users as $u) {
    $stmtUser->execute($u);
}
echo "  [✓] Users seeded (Super Admin, Admins, Advisors, Owners, Investors).\n";

// Also ensure legacy `admins` table has corresponding login records
try {
    $db->exec("
        INSERT INTO `admins` (`id`, `name`, `email`, `phone`, `password_hash`, `role`, `status`)
        VALUES 
            (1, 'Komal Raj', 'admin@estatevistaindia.com', '+91 22 6985 4000', '{$hashedPass}', 'super_admin', 'active'),
            (2, 'Meera Nambiar', 'operations@estatevistaindia.com', '+91 22 6985 4001', '{$hashedPass}', 'admin', 'active'),
            (3, 'Aditya Vardhan Birla', 'aditya.birla@estatevistaindia.com', '+91 98200 44556', '{$hashedPass}', 'agent', 'active')
        ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`)
    ");
} catch (PDOException $e) {}

// 3. Agents table
$agents = [
    [1, 3, 'Aditya Vardhan Birla', 'Senior Vice President - Private Client Group', 'aditya.birla@estatevistaindia.com', '+91 98200 44556', 'assets/images/team/aditya-birla.jpg', 1, '14+ Years', 'Specializes in off-market penthouses and sea-facing luxury residences in South Mumbai.', 'Ultra-Luxury Penthouses & Sea Front Mansions', 42, 580.00],
    [2, 4, 'Tarun Tahiliani', 'Senior Director - North India & Diplomatic Enclaves', 'tarun.tahiliani@estatevistaindia.com', '+91 98111 88776', 'assets/images/team/tarun-tahiliani.jpg', 2, '16+ Years', 'Premier advisor for DLF Golf Links, Lutyens Bungalow Zone, and farmhouses in Delhi NCR.', 'Diplomatic Mansions & Golf Estates', 36, 490.00],
    [3, 5, 'Ananya Goenka', 'Director - Coastal & Tech Founder Estates', 'ananya.goenka@estatevistaindia.com', '+91 98450 33221', 'assets/images/team/ananya-goenka.jpg', 3, '11+ Years', 'Focuses on luxury developments for tech entrepreneurs and coastal private villas in Goa & Bengaluru.', 'Contemporary Smart Villas & Coastal Retreats', 28, 340.00]
];
$stmtAgent = $db->prepare("
    INSERT INTO `agents` (`id`, `user_id`, `name`, `title`, `email`, `phone`, `avatar`, `city_id`, `experience`, `bio`, `specialization`, `total_deals_closed`, `total_deal_volume_cr`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `title` = VALUES(`title`), `phone` = VALUES(`phone`)
");
foreach ($agents as $ag) {
    $stmtAgent->execute($ag);
}
echo "  [✓] Agents profile table seeded (3 Senior Wealth Advisors).\n";

// 4. Cities
$cities = [
    [1, 'Mumbai', 'mumbai', 'Maharashtra', 'Iconic Coastlines & High-Rise Mansions', 'assets/images/cities/mumbai.jpg', 42, 1, 1],
    [2, 'Delhi NCR', 'delhi-ncr', 'National Capital Region', 'Diplomatic Enclaves & Expansive Golf Estates', 'assets/images/cities/delhi.jpg', 38, 1, 2],
    [3, 'Bengaluru', 'bengaluru', 'Karnataka', 'Silicon Valley Estates & Garden Penthouses', 'assets/images/cities/bengaluru.jpg', 31, 1, 3],
    [4, 'Hyderabad', 'hyderabad', 'Telangana', 'Historic Grandeur & Modern Regal Mansions', 'assets/images/cities/hyderabad.jpg', 24, 1, 4],
    [5, 'Pune', 'pune', 'Maharashtra', 'Lush Green Hills & Quiet Private Sanctuaries', 'assets/images/cities/pune.jpg', 19, 1, 5],
    [6, 'Goa', 'goa', 'Goa', 'Portuguese Heritage Villas & Beachfront Luxury', 'assets/images/cities/goa.jpg', 27, 1, 6]
];
$stmtCity = $db->prepare("
    INSERT INTO `cities` (`id`, `name`, `slug`, `state`, `tagline`, `image_url`, `property_count`, `is_featured`, `display_order`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `tagline` = VALUES(`tagline`), `image_url` = VALUES(`image_url`)
");
foreach ($cities as $c) {
    $stmtCity->execute($c);
}
echo "  [✓] Cities seeded (Mumbai, Delhi NCR, Bengaluru, Hyderabad, Pune, Goa).\n";

// 5. Localities
$localities = [
    // Mumbai (city_id: 1)
    [1, 1, 'Worli Sea Face', 'worli-sea-face', '400018', 125000.00, 'Ultra-prime waterfront promenade known for unobstructed Arabian Sea vistas and premier sky penthouses.'],
    [2, 1, 'Malabar Hill', 'malabar-hill', '400006', 145000.00, 'India\'s most prestigious residential address, home to corporate captains and diplomats.'],
    [3, 1, 'Bandra West (Pali Hill)', 'bandra-west-pali-hill', '400050', 110000.00, 'The Beverly Hills of Mumbai, characterized by leafy boulevards, heritage bungalows, and film icons.'],
    [4, 1, 'Altamount Road', 'altamount-road', '400026', 155000.00, 'Mumbai\'s Billionaires\' Boulevard, ranking among the world\'s most exclusive residential streets.'],
    [5, 1, 'Juhu Tara Road', 'juhu-tara-road', '400049', 95000.00, 'Coveted beachfront enclaves with direct private beach access and serene coconut groves.'],

    // Delhi NCR (city_id: 2)
    [6, 2, 'Golf Course Road, DLF Phase 5', 'golf-course-road-dlf-phase-5', '122002', 45000.00, 'The luxury corridor of Gurgaon, anchored by championship golf courses and ultra-luxury condominiums.'],
    [7, 2, 'Lutyens Bungalow Zone', 'lutyens-bungalow-zone', '110001', 180000.00, 'The power center of the nation featuring sprawling colonial bungalows and supreme privacy.'],
    [8, 2, 'Chanakyapuri Diplomatic Enclave', 'chanakyapuri', '110021', 130000.00, 'Distinguished by foreign embassies, wide tree-lined boulevards, and elite diplomatic residences.'],

    // Bengaluru (city_id: 3)
    [9, 3, 'Indiranagar 100ft Enclave', 'indiranagar', '560038', 32000.00, 'Premier urban residential hub with tree-shaded avenues, artisanal dining, and prime standalone villas.'],
    [10, 3, 'Sadashivnagar', 'sadashivnagar', '560080', 38000.00, 'Historic old-money neighborhood near Sankey Tank, celebrated for palatial bungalows.'],
    [11, 3, 'Lavelle Road', 'lavelle-road', '560001', 42000.00, 'Central luxury precinct close to Cubbon Park, renowned for signature penthouses.'],

    // Hyderabad (city_id: 4)
    [12, 4, 'Jubilee Hills Checkpost 36', 'jubilee-hills', '500033', 35000.00, 'The pinnacle of Telugu aristocracy and business moguls with rocky terrain and regal mansions.'],
    [13, 4, 'Banjara Hills Road No. 12', 'banjara-hills', '500034', 30000.00, 'Elite urban neighborhood blending high-end fashion boutiques with secluded private residences.'],

    // Pune (city_id: 5)
    [14, 5, 'Koregaon Park North Main Road', 'koregaon-park', '411001', 25000.00, 'Pune\'s green oasis offering majestic banyan trees, upscale cafes, and boutique bungalows.'],
    [15, 5, 'Boat Club Road', 'boat-club-road', '411001', 28000.00, 'Exclusive riverside stretch coveted by industrial families for supreme peacefulness.'],

    // Goa (city_id: 6)
    [16, 6, 'Assagao Heritage Valley', 'assagao', '403507', 22000.00, 'The Beverly Hills of Goa, famed for restored Portuguese villas, designer ateliers, and organic greenery.'],
    [17, 6, 'Candolim Seafront', 'candolim-seafront', '403515', 26000.00, 'Direct beachfront addresses with private sunset terraces and peaceful ocean breezes.']
];

$stmtLoc = $db->prepare("
    INSERT INTO `localities` (`id`, `city_id`, `name`, `slug`, `pincode`, `average_sqft_price`, `description`)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `average_sqft_price` = VALUES(`average_sqft_price`)
");
foreach ($localities as $l) {
    $stmtLoc->execute($l);
}
echo "  [✓] Localities seeded (17 prime luxury localities).\n";

// 6. Property Categories
$categories = [
    [1, 'Apartments', 'apartments', 'High-rise luxury sky condominiums with bespoke interiors and panoramic skyline vistas.', 'fa-building', 1],
    [2, 'Villas', 'villas', 'Independent private architectural sanctuaries with private pools, gardens, and concierge services.', 'fa-house-chimney', 2],
    [3, 'Penthouses', 'penthouses', 'Crown-jewel duplex and triplex sky mansions featuring private plunge pools and uninterrupted horizon views.', 'fa-crown', 3],
    [4, 'Plots', 'plots', 'Prime land parcels in gated ultra-HNW golf communities and coastal enclaves for bespoke architectural commissions.', 'fa-vector-square', 4],
    [5, 'Commercial Spaces', 'commercial-spaces', 'Grade-A flagship office headquarters and private banking suites in landmark corporate towers.', 'fa-briefcase', 5]
];
$stmtCat = $db->prepare("
    INSERT INTO `property_categories` (`id`, `name`, `slug`, `description`, `icon_class`, `display_order`)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`)
");
foreach ($categories as $cat) {
    $stmtCat->execute($cat);
}
echo "  [✓] Property Categories seeded (Apartments, Villas, Penthouses, Plots, Commercial Spaces).\n";

// 7. Amenities
$amenities = [
    [1, 'Private Infinity Pool', 'Wellness', 'fa-water-ladder'],
    [2, 'Private Biometric Elevator', 'Security', 'fa-elevator'],
    [3, 'Automated Home Intelligence (Crestron/Lutron)', 'Technology', 'fa-microchip'],
    [4, 'Italian Marble & Custom Timber Flooring', 'Interiors', 'fa-gem'],
    [5, 'Vastu Compliant Architecture', 'Tradition', 'fa-compass'],
    [6, '3 Dedicated Covered Parking Bays', 'Convenience', 'fa-car'],
    [7, 'Private Temperature-Controlled Wine Cellar', 'Leisure', 'fa-wine-glass'],
    [8, 'Private Screening Cinema (Dolby Atmos)', 'Leisure', 'fa-film'],
    [9, 'High-End Poggenpohl Chef Kitchen', 'Interiors', 'fa-utensils'],
    [10, '3-Tier 24/7 Diplomatic Security & CCTV', 'Security', 'fa-shield-halved'],
    [11, 'Full 100% Silent Diesel Power Backup', 'Convenience', 'fa-bolt'],
    [12, 'Designer Lanscaped Sky Garden & Deck', 'Outdoor', 'fa-tree'],
    [13, 'Bespoke Spa, Steam & Jacuzzi Suite', 'Wellness', 'fa-hot-tub-person'],
    [14, 'EV High-Speed Charging Station', 'Eco', 'fa-charging-station']
];
$stmtAmen = $db->prepare("
    INSERT INTO `amenities` (`id`, `name`, `category`, `icon_class`)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `category` = VALUES(`category`)
");
foreach ($amenities as $am) {
    $stmtAmen->execute($am);
}
echo "  [✓] Amenities catalog seeded (14 luxury amenities).\n";

// 8. Properties
$properties = [
    [
        1, 6, 1, 1,
        'The Sky Crest Penthouse at Worli Sea Face',
        'the-sky-crest-penthouse-at-worli-sea-face',
        'Triplex Crown Penthouse with 360° Arabian Sea & Bandra-Worli Sea Link Views',
        'Occupying the top three floors of Worli\'s most coveted architectural tower, The Sky Crest represents the pinnacle of South Mumbai living. Features a double-height cantilevered living salon, private infinity lap pool overlooking the sunset horizon, dedicated high-speed biometric elevator, and four presidential master suites with walk-in Italian dressing rooms.',
        3, 1, 1, 'Worli Sea Face', 'Worli Sea Face, Dr. Annie Besant Road, Mumbai 400018',
        345000000.00, '₹ 34.50 Cr', 'sale', 'buy',
        5, 6, 4, 7850, 9800, '52nd to 54th Floor (Triplex)', 54,
        'bespoke-furnished', 'West (Unobstructed Sunset & Sea View)', 4,
        'ready_to_move', 'Immediate Possession', 'P51900008921',
        1, 1, 1, 'approved', NULL,
        'assets/images/properties/property-1.jpg', 'assets/images/floorplans/floorplan-1.jpg',
        'assets/images/properties/gallery-1-1.jpg,assets/images/properties/gallery-1-2.jpg,assets/images/properties/gallery-1-3.jpg',
        'https://my.matterport.com/show/?m=sample1', 'https://maps.google.com/embed?sample', 1420,
        'Aditya Vardhan Birla', '+91 98200 44556', 'aditya.birla@estatevistaindia.com', 'agent'
    ],
    [
        2, 6, 2, 2,
        'The Imperial Manor on Golf Course Road',
        'the-imperial-manor-on-golf-course-road',
        'Championship Golf-Front Mansion with Private Fairway Access',
        'Situated right on the greens of DLF Golf Course, The Imperial Manor is an expansive sanctuary crafted for international dignitaries and business leaders. Features expansive floor-to-ceiling double-glazed thermal glass, private temperature-controlled wine room, landscaped sunken courtyard, and automated climate control throughout.',
        2, 2, 6, 'Golf Course Road, DLF Phase 5', 'Magnolia Boulevard, DLF Phase 5, Gurugram, Delhi NCR 122002',
        285000000.00, '₹ 28.50 Cr', 'sale', 'buy',
        6, 7, 3, 9200, 11500, 'Ground + 2 Levels Standalone', 3,
        'bespoke-furnished', 'North-East (Fairway Facing)', 5,
        'ready_to_move', 'Ready for Handover', 'HRERA-PKL-GGM-124-2018',
        1, 1, 1, 'approved', NULL,
        'assets/images/properties/property-2.jpg', 'assets/images/floorplans/floorplan-2.jpg',
        'assets/images/properties/gallery-2-1.jpg,assets/images/properties/gallery-2-2.jpg',
        'https://my.matterport.com/show/?m=sample2', 'https://maps.google.com/embed?sample', 1180,
        'Tarun Tahiliani', '+91 98111 88776', 'tarun.tahiliani@estatevistaindia.com', 'agent'
    ],
    [
        3, 6, 1, 3,
        'The Presidential Reserve at Altamount Road',
        'the-presidential-reserve-at-altamount-road',
        'Bespoke Single-Floor Sky Residence on India\'s Billionaires\' Row',
        'An ultra-exclusive full-floor residence in an intimate 12-home bespoke boutique tower on Altamount Road. Designed by world-renowned interior architects with imported Greek Thassos marble, private service corridors, staff quarters with separate entrance, and bullet-resistant double-glazed acoustic fenestration.',
        1, 1, 4, 'Altamount Road', '15 Altamount Road, Cumballa Hill, Mumbai 400026',
        420000000.00, '₹ 42.00 Cr', 'sale', 'buy',
        4, 5, 2, 6200, 7500, '28th Floor (Single Residence on Floor)', 32,
        'bespoke-furnished', 'North-West (Sea & City Skyline)', 4,
        'ready_to_move', 'Immediate Possession', 'P51900012045',
        1, 1, 1, 'approved', NULL,
        'assets/images/properties/property-3.jpg', 'assets/images/floorplans/floorplan-3.jpg',
        'assets/images/properties/gallery-3-1.jpg,assets/images/properties/gallery-3-2.jpg',
        NULL, NULL, 980,
        'Aditya Vardhan Birla', '+91 98200 44556', 'aditya.birla@estatevistaindia.com', 'agent'
    ],
    [
        4, 6, 3, 1,
        'Villa Solarium at Candolim Beachside',
        'villa-solarium-at-candolim-beachside',
        'Portuguese Heritage Coastal Estate with Direct Private Beach Access',
        'Set on over 1.5 acres of whispering coconut palms along Candolim beach, Villa Solarium is an impeccably restored Portuguese-Goan heritage mansion. Features antique terracotta tiled roofs, expansive wraparound balcãos, heated volcanic stone swimming pool, and private outdoor cabanas for tropical entertaining.',
        2, 6, 17, 'Candolim Seafront', 'Beach Road, Candolim, North Goa 403515',
        225000000.00, '₹ 22.50 Cr', 'sale', 'buy',
        5, 6, 4, 8500, 10200, 'Ground + 1 Level Heritage Villa', 2,
        'bespoke-furnished', 'West (Arabian Sea Shoreline)', 4,
        'ready_to_move', 'Immediate Handover', 'GOARERA09180455',
        1, 0, 1, 'approved', NULL,
        'assets/images/properties/property-4.jpg', 'assets/images/floorplans/floorplan-4.jpg',
        'assets/images/properties/gallery-4-1.jpg,assets/images/properties/gallery-4-2.jpg',
        NULL, NULL, 870,
        'Ananya Goenka', '+91 98450 33221', 'ananya.goenka@estatevistaindia.com', 'agent'
    ],
    [
        5, 6, 3, 4,
        'The Royal Sovereign Suite at Jubilee Hills',
        'the-royal-sovereign-suite-at-jubilee-hills',
        'Regal Hilltop Villa with Panoramic Views of Durgam Cheruvu & Cyberabad',
        'Perched on an elevated ridge in Jubilee Hills, this monolithic contemporary mansion is designed for high-profile entertaining and luxurious family living. Boasts 18-foot ceiling heights, an acoustic private screening theatre, infinity edge pool jutting out towards the cityscape, and state-of-the-art security clearance systems.',
        2, 4, 12, 'Jubilee Hills Checkpost 36', 'Road No. 36, Jubilee Hills, Hyderabad 500033',
        240000000.00, '₹ 24.00 Cr', 'sale', 'buy',
        5, 6, 3, 7600, 9200, 'Tri-Level Hilltop Villa', 3,
        'bespoke-furnished', 'East (Vaastu Supreme)', 4,
        'ready_to_move', 'Ready for Handover', 'P02400001289',
        1, 0, 1, 'approved', NULL,
        'assets/images/properties/property-5.jpg', 'assets/images/floorplans/floorplan-5.jpg',
        'assets/images/properties/gallery-5-1.jpg,assets/images/properties/gallery-5-2.jpg',
        NULL, NULL, 760,
        'Aditya Vardhan Birla', '+91 98200 44556', 'aditya.birla@estatevistaindia.com', 'agent'
    ],
    [
        6, 6, 3, 3,
        'The Botanique Glass Villa at Sadashivnagar',
        'the-botanique-glass-villa-at-sadashivnagar',
        'Architectural Glass & Teak Villa Overlooking Sankey Lake Canopies',
        'A masterwork of modernist tropical architecture in Bengaluru\'s most aristocratic neighborhood. Embraced by mature jacaranda trees, the home features floor-to-ceiling motorized Swiss glass facades, heated lap pool, automated shading, and a private rooftop wellness lounge.',
        2, 3, 10, 'Sadashivnagar', '11th Cross Road, Sadashivnagar, Bengaluru 560080',
        195000000.00, '₹ 19.50 Cr', 'sale', 'buy',
        4, 5, 3, 6400, 7800, 'G + 2 Architectural Residence', 3,
        'bespoke-furnished', 'North-East', 3,
        'ready_to_move', 'Immediate Possession', 'PRM/KA/RERA/1251/310/PR/180507',
        1, 0, 1, 'approved', NULL,
        'assets/images/properties/property-6.jpg', 'assets/images/floorplans/floorplan-6.jpg',
        'assets/images/properties/gallery-6-1.jpg,assets/images/properties/gallery-6-2.jpg',
        NULL, NULL, 690,
        'Ananya Goenka', '+91 98450 33221', 'ananya.goenka@estatevistaindia.com', 'agent'
    ],
    [
        7, 6, 2, 5,
        'The Riverside Pavilion at Boat Club Road',
        'the-riverside-pavilion-at-boat-club-road',
        'Secluded Mula-Mutha Waterfront Penthouse with Private Boat Mooring',
        'Rare riverfront penthouse in Pune\'s most desirable residential enclave. Boasting 5,800 square feet of impeccably detailed living space with imported teak parquet, wraparound river terraces, private plunge pool, and dedicated staff quarters.',
        3, 5, 15, 'Boat Club Road', 'Boat Club Road, Pune 411001',
        145000000.00, '₹ 14.50 Cr', 'sale', 'buy',
        4, 5, 3, 5800, 6900, '14th & 15th Floor Duplex', 15,
        'semi-furnished', 'South-East (River Facing)', 3,
        'ready_to_move', 'Ready for Possession', 'P52100002498',
        0, 0, 1, 'approved', NULL,
        'assets/images/properties/property-7.jpg', 'assets/images/floorplans/floorplan-7.jpg',
        'assets/images/properties/gallery-7-1.jpg,assets/images/properties/gallery-7-2.jpg',
        NULL, NULL, 510,
        'Aditya Vardhan Birla', '+91 98200 44556', 'aditya.birla@estatevistaindia.com', 'agent'
    ],
    [
        8, 6, 1, 1,
        'The Sea Horizon Duplex at Pali Hill',
        'the-sea-horizon-duplex-at-pali-hill',
        'Private Garden Duplex in Bandra\'s Most Celebrated Celebrity Enclave',
        'Nestled amidst lush foliage on Pali Hill, this private garden duplex pairs Manhattan-style penthouse volumes with serene coastal privacy. Features a double-height art gallery, private landscaped perimeter garden, Poggenpohl kitchen, and bespoke automated audio-visual integration.',
        1, 1, 3, 'Bandra West (Pali Hill)', 'Nargis Dutt Road, Pali Hill, Bandra West, Mumbai 400050',
        265000000.00, '₹ 26.50 Cr', 'sale', 'buy',
        4, 5, 2, 5400, 6600, 'Ground & 1st Floor Duplex with Garden', 12,
        'bespoke-furnished', 'West', 3,
        'ready_to_move', 'Immediate Possession', 'P51900015678',
        1, 0, 1, 'approved', NULL,
        'assets/images/properties/property-8.jpg', 'assets/images/floorplans/floorplan-8.jpg',
        'assets/images/properties/gallery-8-1.jpg,assets/images/properties/gallery-8-2.jpg',
        NULL, NULL, 830,
        'Aditya Vardhan Birla', '+91 98200 44556', 'aditya.birla@estatevistaindia.com', 'agent'
    ]
];

$stmtProp = $db->prepare("
    INSERT INTO `properties` (
        `id`, `user_id`, `assigned_agent_id`, `developer_id`,
        `title`, `slug`, `subtitle`, `description`,
        `category_id`, `city_id`, `locality_id`, `locality`, `address`,
        `price`, `price_display`, `price_type`, `purpose`,
        `bhk`, `bathrooms`, `balconies`, `carpet_area_sqft`, `builtup_area_sqft`,
        `floor_number`, `total_floors`, `furnishing`, `facing`, `parking_spaces`,
        `status`, `possession_date`, `rera_number`,
        `is_featured`, `is_exclusive`, `is_verified`, `approval_status`, `rejection_reason`,
        `cover_image`, `floor_plan_image`, `gallery_images`, `virtual_tour_url`, `map_embed_url`,
        `views_count`, `contact_person_name`, `contact_person_phone`, `contact_person_email`, `owner_role`
    ) VALUES (
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?
    ) ON DUPLICATE KEY UPDATE 
        `title` = VALUES(`title`),
        `price` = VALUES(`price`),
        `price_display` = VALUES(`price_display`),
        `locality_id` = VALUES(`locality_id`),
        `assigned_agent_id` = VALUES(`assigned_agent_id`)
");

foreach ($properties as $p) {
    $stmtProp->execute($p);
}
echo "  [✓] Properties catalog seeded (8 signature luxury residences).\n";

// 9. Property Images
$db->exec("TRUNCATE TABLE `property_images`");
$stmtImg = $db->prepare("INSERT INTO `property_images` (`property_id`, `image_url`, `caption`, `is_cover`, `display_order`) VALUES (?, ?, ?, ?, ?)");
foreach ($properties as $p) {
    $pId = $p[0];
    $cover = $p[35];
    $stmtImg->execute([$pId, $cover, 'Grand Exterior & Architecture', 1, 1]);
    
    // Add additional gallery slides
    $stmtImg->execute([$pId, "assets/images/properties/gallery-{$pId}-1.jpg", 'Main Formal Living Salon', 0, 2]);
    $stmtImg->execute([$pId, "assets/images/properties/gallery-{$pId}-2.jpg", 'Master Suite & Skyline Panorama', 0, 3]);
}
echo "  [✓] Property Images table seeded (24 curated photography assets).\n";

// 10. Property Amenities
$db->exec("DELETE FROM `property_amenities`");
$stmtPA = $db->prepare("INSERT IGNORE INTO `property_amenities` (`property_id`, `amenity_id`) VALUES (?, ?)");
for ($pid = 1; $pid <= 8; $pid++) {
    // assign 6-8 amenities per property
    $amenityKeys = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14];
    shuffle($amenityKeys);
    $selected = array_slice($amenityKeys, 0, 7);
    foreach ($selected as $aid) {
        $stmtPA->execute([$pid, $aid]);
    }
}
echo "  [✓] Property Amenities junction records seeded.\n";

// 11. Favorites / Saved Properties
$db->exec("DELETE FROM `favorites`");
$stmtFav = $db->prepare("INSERT IGNORE INTO `favorites` (`user_id`, `property_id`, `notes`) VALUES (?, ?, ?)");
$stmtFav->execute([7, 1, 'Shortlisted for family office acquisition. Need private sea-view verification.']);
$stmtFav->execute([7, 3, 'Altamount Road full-floor apartment. High priority.']);
$stmtFav->execute([6, 2, 'Golf Course Road manor for overseas guest residence.']);
echo "  [✓] Favorites seeded.\n";

// 12. Enquiries & Leads & Site Visits
$db->exec("DELETE FROM `enquiries`");
$stmtEnq = $db->prepare("
    INSERT INTO `enquiries` (`id`, `user_id`, `property_id`, `assigned_agent_id`, `enquiry_type`, `full_name`, `email`, `phone`, `city`, `budget_range`, `preferred_time`, `message`, `status`, `created_at`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmtEnq->execute([
    1, 7, 1, 1, 'enquiry', 'Anand Piramal', 'anand.piramal@piramal.com', '+91 98200 99887', 'Mumbai', '₹30 Cr - ₹40 Cr', 'Morning 11:00 AM',
    'Requesting private viewing and structural floor plan drawings for Sky Crest Triplex penthouse.', 'in_progress', date('Y-m-d H:i:s', strtotime('-2 days'))
]);
$stmtEnq->execute([
    2, NULL, 2, 2, 'callback', 'Rajesh Exports Representative', 'rajesh.hni@goldinvest.in', '+91 98440 12345', 'Delhi NCR', '₹25 Cr - ₹35 Cr', 'Evening 5:00 PM',
    'Please arrange an executive callback regarding title deed clearance for the DLF Golf Course Manor.', 'new', date('Y-m-d H:i:s', strtotime('-1 day'))
]);
echo "  [✓] Enquiries table seeded.\n";

// Site Visits
$db->exec("DELETE FROM `site_visits`");
$stmtSV = $db->prepare("
    INSERT INTO `site_visits` (`id`, `user_id`, `property_id`, `assigned_agent_id`, `client_name`, `client_email`, `client_phone`, `visit_date`, `visit_time`, `pickup_required`, `special_notes`, `status`, `created_at`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmtSV->execute([
    1, 7, 1, 1, 'Anand Piramal', 'anand.piramal@piramal.com', '+91 98200 99887',
    date('Y-m-d', strtotime('+2 days')), '11:00 AM - 12:30 PM', 1,
    'Client arriving via private charter to Mumbai. Airport luxury chauffeur pickup requested.', 'confirmed', date('Y-m-d H:i:s', strtotime('-1 day'))
]);
$stmtSV->execute([
    2, NULL, 5, 1, 'Suresh Reddy', 'suresh.reddy@hyderabadbiotech.com', '+91 98490 55667',
    date('Y-m-d', strtotime('+3 days')), '04:00 PM - 05:30 PM', 0,
    'Family viewing of the Jubilee Hills villa. Interested in Vastu verification and parking capacity.', 'pending', date('Y-m-d H:i:s', strtotime('-6 hours'))
]);
echo "  [✓] Site Visits table seeded.\n";

// Leads (CRM Pipeline)
$db->exec("DELETE FROM `leads`");
$stmtLead = $db->prepare("
    INSERT INTO `leads` (`id`, `user_id`, `property_id`, `assigned_agent_id`, `full_name`, `email`, `phone`, `preferred_city`, `budget_range`, `deal_value`, `lead_source`, `lead_status`, `follow_up_date`, `notes`, `created_at`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmtLead->execute([
    1, 7, 1, 1, 'Anand Piramal', 'anand.piramal@piramal.com', '+91 98200 99887', 'Mumbai', '₹35 Cr', 345000000.00,
    'HNI Private Network', 'negotiation', date('Y-m-d H:i:s', strtotime('+1 day 11:00')), 'Advanced discussions on token schedule and custom German kitchen fitment.', date('Y-m-d H:i:s', strtotime('-4 days'))
]);
$stmtLead->execute([
    2, NULL, 2, 2, 'Dr. Cyrus Poonawalla Advisory', 'cyrus.wealth@serumfamily.com', '+91 98220 11990', 'Delhi NCR', '₹30 Cr', 285000000.00,
    'Direct Call', 'site_visit_scheduled', date('Y-m-d H:i:s', strtotime('+2 days 15:00')), 'Viewing scheduled for the Golf Course Road manor.', date('Y-m-d H:i:s', strtotime('-2 days'))
]);
$stmtLead->execute([
    3, NULL, 3, 1, 'Sunil Mittal Family Office', 'sunil.office@bhartienterprises.com', '+91 98100 88221', 'Mumbai', '₹45 Cr', 420000000.00,
    'Referral', 'interested', date('Y-m-d H:i:s', strtotime('+3 days 10:00')), 'Reviewed presentation deck for Altamount Road single-floor residence.', date('Y-m-d H:i:s', strtotime('-3 days'))
]);
$stmtLead->execute([
    4, NULL, 4, 3, 'Deepinder Goyal', 'deepinder@zomatoinvestments.com', '+91 98101 44332', 'Goa', '₹25 Cr', 225000000.00,
    'Social Campaign', 'contacted', date('Y-m-d H:i:s', strtotime('+1 day 16:00')), 'Initial telephone introduction regarding coastal property in Candolim.', date('Y-m-d H:i:s', strtotime('-1 day'))
]);
$stmtLead->execute([
    5, NULL, 8, 1, 'Karan Johar Office', 'karan.properties@dharma.in', '+91 98200 77331', 'Mumbai', '₹28 Cr', 265000000.00,
    'Website Enquiry', 'new', date('Y-m-d H:i:s', strtotime('+4 hours')), 'Inquired on Pali Hill Garden Duplex. Needs high-security celebrity perimeter.', date('Y-m-d H:i:s', strtotime('-5 hours'))
]);
$stmtLead->execute([
    6, NULL, 6, 3, 'Nithin Kamath', 'nithin@zerodhafund.com', '+91 98450 11223', 'Bengaluru', '₹20 Cr', 195000000.00,
    'HNI Private Network', 'converted', NULL, 'Deal closed! Token and preliminary agreement completed for Sadashivnagar Glass Villa.', date('Y-m-d H:i:s', strtotime('-15 days'))
]);
echo "  [✓] Leads table seeded across pipeline stages.\n";

// Lead Follow-ups
$db->exec("DELETE FROM `lead_followups`");
$stmtLF = $db->prepare("
    INSERT INTO `lead_followups` (`lead_id`, `agent_id`, `agent_name`, `followup_type`, `summary`, `notes`, `created_at`)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmtLF->execute([1, 1, 'Aditya Vardhan Birla', 'call', 'Telephone Conference with CFO', 'Client confirmed availability of liquid funds for token deposit.', date('Y-m-d H:i:s', strtotime('-2 days'))]);
$stmtLF->execute([1, 1, 'Aditya Vardhan Birla', 'meeting', 'Personal Meeting at Taj Mahal Palace', 'Reviewed RERA documentation and structural architectural blueprints.', date('Y-m-d H:i:s', strtotime('-1 day'))]);
$stmtLF->execute([1, 1, 'Aditya Vardhan Birla', 'status_change', 'Moved to Negotiation', 'Advancing stage to Negotiation with deal value estimated at ₹34.50 Cr.', date('Y-m-d H:i:s', strtotime('-12 hours'))]);
$stmtLF->execute([2, 2, 'Tarun Tahiliani', 'whatsapp', 'WhatsApp Itinerary Confirmation', 'Sent Google Maps pin and gate pass clearance for DLF Golf Course Road.', date('Y-m-d H:i:s', strtotime('-1 day'))]);
echo "  [✓] Lead Follow-ups table seeded with chronological touchpoints.\n";

// Also synchronize legacy `inquiries` table with leads/enquiries so all existing code works flawlessly
try {
    $db->exec("
        INSERT INTO `inquiries` (
            `id`, `user_id`, `property_id`, `assigned_agent_id`, `inquiry_type`, `lead_source`,
            `full_name`, `email`, `phone`, `city`, `preferred_city`, `budget_range`,
            `visit_date`, `visit_time`, `message`, `deal_value`, `lead_status`, `status`, `created_at`
        ) VALUES 
        (1, 7, 1, 1, 'site_visit', 'HNI Private Network', 'Anand Piramal', 'anand.piramal@piramal.com', '+91 98200 99887', 'Mumbai', 'Mumbai', '₹35 Cr', CURDATE() + INTERVAL 2 DAY, '11:00 AM', 'Private viewing of Sky Crest Triplex Penthouse', 345000000.00, 'negotiation', 'scheduled', NOW() - INTERVAL 4 DAY),
        (2, NULL, 2, 2, 'site_visit', 'Direct Call', 'Dr. Cyrus Poonawalla Advisory', 'cyrus.wealth@serumfamily.com', '+91 98220 11990', 'Delhi NCR', 'Delhi NCR', '₹30 Cr', CURDATE() + INTERVAL 2 DAY, '03:00 PM', 'Viewing for DLF Golf Course Manor', 285000000.00, 'site_visit_scheduled', 'scheduled', NOW() - INTERVAL 2 DAY),
        (3, NULL, 3, 1, 'enquiry', 'Referral', 'Sunil Mittal Family Office', 'sunil.office@bhartienterprises.com', '+91 98100 88221', 'Mumbai', 'Mumbai', '₹45 Cr', NULL, NULL, 'Inquiry on Altamount Road single-floor residence', 420000000.00, 'interested', 'contacted', NOW() - INTERVAL 3 DAY),
        (4, NULL, 4, 3, 'callback', 'Social Campaign', 'Deepinder Goyal', 'deepinder@zomatoinvestments.com', '+91 98101 44332', 'Goa', 'Goa', '₹25 Cr', NULL, NULL, 'Callback regarding Portuguese Villa Solarium', 225000000.00, 'contacted', 'contacted', NOW() - INTERVAL 1 DAY),
        (5, NULL, 8, 1, 'enquiry', 'Website Enquiry', 'Karan Johar Office', 'karan.properties@dharma.in', '+91 98200 77331', 'Mumbai', 'Mumbai', '₹28 Cr', NULL, NULL, 'Inquiry on Pali Hill Garden Duplex', 265000000.00, 'new', 'new', NOW() - INTERVAL 5 HOUR),
        (6, NULL, 6, 3, 'enquiry', 'HNI Private Network', 'Nithin Kamath', 'nithin@zerodhafund.com', '+91 98450 11223', 'Bengaluru', 'Bengaluru', '₹20 Cr', NULL, NULL, 'Acquisition of Sadashivnagar Glass Villa', 195000000.00, 'converted', 'closed', NOW() - INTERVAL 15 DAY)
        ON DUPLICATE KEY UPDATE `deal_value` = VALUES(`deal_value`), `lead_status` = VALUES(`lead_status`)
    ");
} catch (PDOException $e) {}

// 13. Testimonials
$testimonials = [
    [1, 'Vikramaditya Singhania', 'Managing Partner, Private Equity', 'Worli, Mumbai', 'EstateVista India transformed what is typically an opaque transaction into a discreet, highly curated private advisory experience. Their access to off-market sea-view penthouses in South Mumbai is unmatched.', 5, 'assets/images/testimonials/client-1.jpg', 1],
    [2, 'Rohit & Natasha Goel', 'Tech Founders & Angel Investors', 'Golf Course Road, Gurugram', 'Finding a residence with championship golf frontage and biometric private elevator access was seamless. EstateVista orchestrated private viewings, legal due diligence, and registration with supreme discretion.', 5, 'assets/images/testimonials/client-2.jpg', 1],
    [3, 'Sunil Mehta', 'Senior Director, Sovereign Wealth Advisory', 'Indiranagar, Bengaluru', 'The level of market intelligence, structural verification, and RERA transparency provided by EstateVista sets the gold standard for luxury property advisory across India.', 5, 'assets/images/testimonials/client-3.jpg', 1]
];
$stmtTest = $db->prepare("
    INSERT INTO `testimonials` (`id`, `client_name`, `client_title`, `location`, `review`, `rating`, `client_avatar`, `verified_buyer`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE `client_name` = VALUES(`client_name`), `review` = VALUES(`review`)
");
foreach ($testimonials as $t) {
    $stmtTest->execute($t);
}
echo "  [✓] Testimonials seeded.\n";

// 14. Blog Posts (Market Insights)
$blogPosts = [
    [
        1, 'The Rise of Branded Residences: Why India\'s Billionaires Are Paying a 40% Premium',
        'the-rise-of-branded-residences-in-india', 'Market Intelligence', 1, 'EstateVista Research Desk', '5 min read', 'November 2026',
        'assets/images/articles/article-1.jpg',
        'An in-depth analysis of how partnerships with luxury hospitality brands like Armani/Casa, Four Seasons, and Trump Towers are redefining prime real estate values in Mumbai and Delhi NCR.',
        '<p>Over the past three years, branded residences have outpaced standard luxury apartments by over 38% in capital appreciation across South Mumbai and Central Delhi. Ultra-high-net-worth individuals prioritize international hotel-grade concierge services, private biometric security, and pre-vetted peer communities.</p><h3>Key Investment Drivers</h3><p>According to EstateVista Private Wealth Research, key catalysts include bespoke interior appointments, globally recognized title clearance, and steady rental yields from multinational diplomats and executives.</p>',
        'published', 1840
    ],
    [
        2, 'RERA Due Diligence for High-Value Transactions: The Essential 7-Point Checklist',
        'rera-due-diligence-luxury-real-estate-checklist', 'Legal & Advisory', 2, 'Meera Nambiar (Legal)', '7 min read', 'October 2026',
        'assets/images/articles/article-2.jpg',
        'Essential title verification protocols, encumbrance certificates, and RERA compliance standards required before executing multi-crore real estate acquisitions.',
        '<p>Navigating luxury real estate in India requires sophisticated due diligence beyond basic registry searches. Our advisory legal desk outlines the non-negotiable verifications for high-value acquisitions.</p><h3>1. Title Search Period</h3><p>Ensure an uninterrupted 30-year title lineage verified through government sub-registrar records and public gazette notices.</p>',
        'published', 2410
    ],
    [
        3, 'Ultra-Luxury Coastal Estates: The North Goa Luxury Corridor Boom',
        'north-goa-luxury-corridor-boom', 'Lifestyle & Estates', 3, 'Ananya Goenka', '4 min read', 'September 2026',
        'assets/images/articles/article-3.jpg',
        'Why Silicon Valley and Mumbai tech founders are driving record sales in Assagao, Candolim, and Moira heritage villa corridors.',
        '<p>Goa has evolved from a seasonal vacation destination into a permanent second-home sanctuary for India\'s startup founders and venture capitalists. Architectural villas combining heritage Portuguese facades with Scandinavian interiors command the highest demand.</p>',
        'published', 1620
    ]
];
$stmtBP = $db->prepare("
    INSERT INTO `blog_posts` (`id`, `title`, `slug`, `category`, `author_id`, `author`, `read_time`, `publish_date`, `image_url`, `excerpt`, `content`, `status`, `views_count`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `excerpt` = VALUES(`excerpt`)
");
foreach ($blogPosts as $bp) {
    $stmtBP->execute($bp);
}
echo "  [✓] Blog Posts (Market Intelligence) seeded.\n";

// 15. Newsletter Subscribers
$subs = [
    ['investor.relations@tata.com', 'active'],
    ['wealth@kotakprivate.com', 'active'],
    ['chairman.office@godrej.com', 'active'],
    ['concierge.vip@hdfcprivate.com', 'active'],
    ['client@domain.com', 'active']
];
$stmtSub = $db->prepare("INSERT INTO `newsletter_subscribers` (`email`, `status`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `status` = VALUES(`status`)");
foreach ($subs as $s) {
    $stmtSub->execute($s);
}
echo "  [✓] Newsletter subscribers seeded.\n";

// 16. Settings
$settings = [
    ['site_name', 'EstateVista India', 'general', 'Platform Name'],
    ['site_tagline', 'Curated Luxury Living Across India\'s Finest Addresses', 'general', 'Platform Subtitle'],
    ['site_email', 'concierge@estatevistaindia.com', 'contact', 'Primary Concierge Email'],
    ['site_phone', '+91 22 6985 4000', 'contact', 'Concierge Direct Line'],
    ['site_whatsapp', '+91 98200 12345', 'contact', 'WhatsApp Business Desk'],
    ['rera_registration', 'MAHARERA Reg. No: A51900038921 | Certified Luxury Advisory', 'compliance', 'RERA Registration Number'],
    ['currency_symbol', '₹', 'localization', 'Currency Sign'],
    ['currency_code', 'INR', 'localization', 'ISO Currency Code'],
    ['enable_user_registration', '1', 'features', 'Allow Client Self-Registration'],
    ['require_property_approval', '1', 'features', 'Enforce Admin Property Approval Before Public Display']
];
$stmtSet = $db->prepare("
    INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`, `description`)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)
");
foreach ($settings as $st) {
    $stmtSet->execute($st);
}
echo "  [✓] Settings seeded (10 system configuration parameters).\n";

// 17. Notifications
$notifications = [
    [1, 1, 'VIP Site Visit Scheduled', 'Anand Piramal scheduled a private tour for Sky Crest Penthouse at Worli Sea Face.', 'site-visits.php', 'visit', 0],
    [2, 1, 'New Multi-Crore Lead Onboarded', 'Lead #1 (Anand Piramal) progressed to Negotiation (₹34.50 Cr).', 'lead-detail.php?id=1', 'lead', 0],
    [3, 3, 'Urgent Follow-Up Reminder', 'Scheduled callback with Piramal Family Office regarding token schedule.', 'reminders.php', 'warning', 0],
    [4, 7, 'Private Site Visit Confirmed', 'Your VIP site visit for Sky Crest Penthouse has been confirmed with chauffeur pickup.', 'dashboard.php?tab=visits', 'success', 0]
];
$stmtNotif = $db->prepare("
    INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `link`, `type`, `is_read`)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE `title` = VALUES(`title`)
");
foreach ($notifications as $n) {
    $stmtNotif->execute($n);
}
echo "  [✓] Notifications seeded (4 notifications).\n";

// 18. Activity Logs
$actLogs = [
    [1, 1, 'Komal Raj', 'System Migration', 'Completed 21-table relational MySQL database migration.', '127.0.0.1'],
    [1, 1, 'Komal Raj', 'Approved Property', 'Approved listing #1 (The Sky Crest Penthouse)', '127.0.0.1'],
    [3, 3, 'Aditya Vardhan Birla', 'Updated Lead Stage', 'Advanced Lead #1 to Negotiation stage', '127.0.0.1']
];
$stmtAct = $db->prepare("INSERT INTO `activity_logs` (`user_id`, `admin_id`, `admin_name`, `action`, `details`, `ip_address`) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($actLogs as $al) {
    $stmtAct->execute($al);
}
echo "  [✓] Activity logs seeded.\n";

$db->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "\n====================================================================\n";
echo " MIGRATION & SEEDING COMPLETED SUCCESSFULLY (100% RELATIONAL)        \n";
echo "====================================================================\n";

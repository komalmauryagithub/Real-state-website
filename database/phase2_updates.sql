-- Phase 2 Database Updates: Projects & Insights Tables
USE `estatevista_db`;

-- 1. Projects Table (Luxury Residential Developments)
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `developer_id` INT DEFAULT NULL,
    `city_id` INT NOT NULL,
    `locality` VARCHAR(150) NOT NULL,
    `project_type` VARCHAR(100) NOT NULL,
    `configuration` VARCHAR(100) NOT NULL,
    `starting_price` DECIMAL(12,2) NOT NULL,
    `starting_price_display` VARCHAR(50) NOT NULL,
    `possession_date` VARCHAR(100) NOT NULL,
    `status` ENUM('Pre-Launch', 'Under Construction', 'Ready to Handover') DEFAULT 'Under Construction',
    `rera_id` VARCHAR(100) NOT NULL,
    `banner_image` VARCHAR(500) NOT NULL,
    `highlights` TEXT DEFAULT NULL,
    `is_featured` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_proj_dev` FOREIGN KEY (`developer_id`) REFERENCES `developers` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_proj_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Articles Table (Market Intelligence & Insights)
CREATE TABLE IF NOT EXISTS `articles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `category` VARCHAR(100) NOT NULL,
    `read_time` VARCHAR(50) DEFAULT '5 min read',
    `author` VARCHAR(100) DEFAULT 'EstateVista Intelligence Desk',
    `publish_date` VARCHAR(50) NOT NULL,
    `image_url` VARCHAR(500) NOT NULL,
    `excerpt` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Projects
INSERT INTO `projects` (`id`, `title`, `slug`, `developer_id`, `city_id`, `locality`, `project_type`, `configuration`, `starting_price`, `starting_price_display`, `possession_date`, `status`, `rera_id`, `banner_image`, `highlights`, `is_featured`) VALUES
(1, 'The Camellias by DLF', 'the-camellias-dlf', 1, 2, 'Golf Course Road, Gurugram', 'Golf Estate Residences', '4, 5 & 6 BHK Penthouses', 350000000.00, 'From ₹ 35.00 Cr', 'Ready for Handover', 'Ready to Handover', 'HRERA-PKL-GGM-114-2022', 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80', 'Overlooking Gary Player Golf Course | 32,000 sq.ft Clubhouse | Private Helipad Access', 1),
(2, 'Lodha Altamount', 'lodha-altamount', 2, 1, 'Altamount Road, Mumbai', 'Ultra-Luxury High-Rise', '3, 4 & 5 BHK Sky Suites', 420000000.00, 'From ₹ 42.00 Cr', 'Ready for Handover', 'Ready to Handover', 'PR/MH/RERA/004921', 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80', 'Located on Billionaires Row | Barefoot Luxury Glass Facade by Hadi Teherani | 24/7 Butler Service', 1),
(3, 'Pursuit of a Radical Rhapsody', 'total-environment-rhapsody', 4, 3, 'Whitefield, Bengaluru', 'Lakefront Earth-Sheltered Villas', '3, 4 BHK & Boardwalk Villas', 95000000.00, 'From ₹ 9.50 Cr', 'Dec 2026', 'Under Construction', 'PRM/KA/RERA/1251/446/PR/171014/000433', 'https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=1200&q=80', 'Natural Terraced Roof Gardens | Overlooking Commercial Lake | Handcrafted Wire-cut Brick Architecture', 1),
(4, 'Oberoi Three Sixty West', 'oberoi-three-sixty-west', 3, 1, 'Worli, South Mumbai', 'Branded Ritz-Carlton Residences', '4 & 5 BHK Duplex Mansions', 480000000.00, 'From ₹ 48.00 Cr', 'Ready for Handover', 'Ready to Handover', 'P51900000006', 'https://images.unsplash.com/photo-1570168007204-dfb528c6958f?auto=format&fit=crop&w=1200&q=80', 'Managed by The Ritz-Carlton | Column-Free Living Rooms | 360° Arabian Sea Panoramas', 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Seed Articles
INSERT INTO `articles` (`id`, `title`, `slug`, `category`, `read_time`, `author`, `publish_date`, `image_url`, `excerpt`) VALUES
(1, 'Mumbai vs Gurgaon: Where Are India’s Ultra-HNWIs Directing Capital in 2026?', 'mumbai-vs-gurgaon-luxury-real-estate-trends', 'Market Intelligence', '6 min read', 'Devrat Mukherjee, Principal Analyst', 'November 2026', 'https://images.unsplash.com/photo-1587474260584-136574528ed5?auto=format&fit=crop&w=800&q=80', 'A data-backed comparative analysis of Golf Course Road capital appreciation versus South Mumbai sea-facing trophy assets across prime rental yields and supply constraints.'),
(2, 'The Legal Checklist: Navigating RERA, Title Search & Land Encumbrances in Villa Acquisitions', 'legal-checklist-rera-luxury-villa-acquisitions', 'Legal & Tax Advisory', '8 min read', 'Adv. Meenakshi Sundaram', 'October 2026', 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&q=80', 'Essential legal protocols for high-net-worth acquisitions: understanding promoter escrow mandates, 30-year search reports, and Sanad conversion in North Goa.'),
(3, 'Branded Residences in India: Why DLF, Oberoi & Four Seasons Command 40% Premiums', 'rise-of-branded-residences-india', 'Trends & Architecture', '5 min read', 'Kavita Chawla, Architecture Editor', 'October 2026', 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80', 'Hospitality-serviced living has emerged as the definitive asset class for global Indians, offering bespoke concierge desks, valet parking, and lifestyle parity with London and Dubai.')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

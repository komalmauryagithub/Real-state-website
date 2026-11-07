-- Phase 4: Property Detail Enhancements & Inquiries Updates
USE `estatevista_db`;

-- Add detail columns to properties if they don't exist
ALTER TABLE `properties`
ADD COLUMN IF NOT EXISTS `balconies` INT DEFAULT 2 AFTER `bathrooms`,
ADD COLUMN IF NOT EXISTS `facing` VARCHAR(100) DEFAULT 'North-East (Vaastu Compliant)' AFTER `furnishing`,
ADD COLUMN IF NOT EXISTS `parking_spaces` INT DEFAULT 3 AFTER `facing`,
ADD COLUMN IF NOT EXISTS `floor_plan_image` VARCHAR(500) DEFAULT 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=80' AFTER `cover_image`,
ADD COLUMN IF NOT EXISTS `map_embed_url` TEXT DEFAULT NULL AFTER `virtual_tour_url`,
ADD COLUMN IF NOT EXISTS `agent_name` VARCHAR(150) DEFAULT 'Aditya Vardhan Birla' AFTER `views_count`,
ADD COLUMN IF NOT EXISTS `agent_title` VARCHAR(150) DEFAULT 'Senior Partner - Ultra-Luxury Acquisitions' AFTER `agent_name`,
ADD COLUMN IF NOT EXISTS `agent_phone` VARCHAR(50) DEFAULT '+91 22 6985 4010' AFTER `agent_title`,
ADD COLUMN IF NOT EXISTS `agent_email` VARCHAR(150) DEFAULT 'aditya.birla@estatevistaindia.com' AFTER `agent_phone`,
ADD COLUMN IF NOT EXISTS `agent_avatar` VARCHAR(500) DEFAULT 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80' AFTER `agent_email`,
ADD COLUMN IF NOT EXISTS `agent_experience` VARCHAR(50) DEFAULT '16+ Years in Prime Real Estate' AFTER `agent_avatar`,
ADD COLUMN IF NOT EXISTS `nearby_places` TEXT DEFAULT NULL AFTER `agent_experience`;

-- Add columns to inquiries table
ALTER TABLE `inquiries`
ADD COLUMN IF NOT EXISTS `inquiry_type` ENUM('enquiry', 'callback', 'site_visit') DEFAULT 'enquiry' AFTER `property_id`,
ADD COLUMN IF NOT EXISTS `visit_date` DATE DEFAULT NULL AFTER `preferred_time`,
ADD COLUMN IF NOT EXISTS `visit_time` VARCHAR(50) DEFAULT NULL AFTER `visit_date`;

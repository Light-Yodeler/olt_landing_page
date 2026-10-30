-- Skema referensi database Ocean Lombok Tour (MariaDB 10.6+ / MySQL 8+).
-- Sumber kebenaran tetap migration Laravel. File ini dipakai untuk review struktur.
-- Tabel bawaan Laravel (sessions, cache, cache_locks, jobs, job_batches, failed_jobs,
-- migrations) dibuat oleh migration default dan tidak ditulis ulang di sini.
-- password_reset_tokens sengaja tidak dipakai karena alur reset password via email ditiadakan.
--
-- Kolom JSON bilingual berformat {"en": "...", "id": "..."} (spatie/laravel-translatable).
-- Kolom JSON list bilingual berformat {"en": ["...", "..."], "id": ["...", "..."]}.

SET NAMES utf8mb4;

-- PRD 3.2.1: login username + password, wajib ganti password saat login pertama
CREATE TABLE `users` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `username` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
    `remember_token` varchar(100) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PRD 3.2.2 + 3.2.4 + 3.1.4 + 3.1.8 + NFR SEO: satu baris pengaturan situs (id = 1)
CREATE TABLE `site_settings` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    -- Identitas & Brand
    `logo_path` varchar(255) DEFAULT NULL,
    `favicon_path` varchar(255) DEFAULT NULL,
    `company_name` json NOT NULL,
    `tagline` json DEFAULT NULL,
    `whatsapp_number` varchar(20) NOT NULL,          -- format internasional tanpa +, contoh 6287865432190
    `whatsapp_default_message` json DEFAULT NULL,
    `address` varchar(500) DEFAULT NULL,
    `instagram_url` varchar(255) DEFAULT NULL,
    `facebook_url` varchar(255) DEFAULT NULL,
    `tiktok_url` varchar(255) DEFAULT NULL,
    `tripadvisor_url` varchar(255) DEFAULT NULL,
    `google_maps_url` varchar(500) DEFAULT NULL,
    `footer_copyright` json DEFAULT NULL,
    -- Hero & Kartu Sambutan
    `hero_badge` json DEFAULT NULL,
    `hero_headline` json DEFAULT NULL,
    `hero_description` json DEFAULT NULL,
    `hero_highlights` json DEFAULT NULL,             -- list bilingual, 4 butir keunggulan kartu
    -- Seksi Tentang Kami
    `about_badge` json DEFAULT NULL,                 -- contoh {"en":"[ ABOUT OUR SERVICE ]","id":"[ TENTANG LAYANAN KAMI ]"}
    `about_title` json DEFAULT NULL,
    `about_body` json DEFAULT NULL,
    -- Seksi Banner Ajakan Konsultasi Bawah (Consultation CTA Banner)
    `cta_title` json DEFAULT NULL,
    `cta_description` json DEFAULT NULL,
    `cta_button_text` json DEFAULT NULL,
    `cta_whatsapp_message` json DEFAULT NULL,
    -- SEO Global
    `meta_title` json DEFAULT NULL,
    `meta_description` json DEFAULT NULL,
    `og_image_path` varchar(255) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PRD 3.2.3: Hero Slideshow
CREATE TABLE `hero_slides` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `image_path` varchar(255) NOT NULL,
    `caption` json DEFAULT NULL,                     -- caption internal & alt text (bilingual {"en":"...","id":"..."})
    `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `hero_slides_active_order_index` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PRD 3.2.5: Layanan Kami
CREATE TABLE `services` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `category_code` json NOT NULL,                   -- contoh {"en":"BIKE RENTAL","id":"SEWA MOTOR"}
    `icon` varchar(50) NOT NULL,                     -- nama ikon Lucide, contoh bike, car, mountain, waves
    `title` json NOT NULL,
    `description` json NOT NULL,
    `features` json DEFAULT NULL,                    -- list bilingual array {"en":["..."],"id":["..."]}
    `button_text` json DEFAULT NULL,
    `whatsapp_message` json DEFAULT NULL,
    `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `services_active_order_index` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PRD 3.2.6: Destinasi Wisata
CREATE TABLE `destinations` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `image_path` varchar(255) NOT NULL,
    `name` json NOT NULL,
    `category_badge` json DEFAULT NULL,              -- contoh {"en":"Trekking & Mountain","id":"Trekking & Gunung"}
    `description` json NOT NULL,
    `location` json DEFAULT NULL,                    -- contoh {"en":"East & North Lombok","id":"Lombok Timur & Utara"}
    `button_text` json DEFAULT NULL,                 -- contoh {"en":"Inquire Tour →","id":"Tanya Tur →"}
    `whatsapp_message` json DEFAULT NULL,
    `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `destinations_active_order_index` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PRD 3.2.7: 3 Pilar Standar Pelayanan
CREATE TABLE `standards` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `title` json NOT NULL,
    `description` json NOT NULL,
    `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `standards_active_order_index` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PRD 3.2.7: FAQ Accordion
CREATE TABLE `faqs` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `question` json NOT NULL,
    `answer` json NOT NULL,
    `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `faqs_active_order_index` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

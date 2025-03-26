-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 26, 2025 at 03:40 PM
-- Wersja serwera: 8.3.0
-- Wersja PHP: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `crimcity`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `bars`
--

DROP TABLE IF EXISTS `bars`;
CREATE TABLE IF NOT EXISTS `bars` (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_id` int NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` float NOT NULL,
  `entry_fee` float DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `character_id` (`character_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `bar_drugs`
--

DROP TABLE IF EXISTS `bar_drugs`;
CREATE TABLE IF NOT EXISTS `bar_drugs` (
  `bar_id` int NOT NULL,
  `drug_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `price` float NOT NULL,
  PRIMARY KEY (`bar_id`,`drug_id`),
  KEY `drug_id` (`drug_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `buildings`
--

DROP TABLE IF EXISTS `buildings`;
CREATE TABLE IF NOT EXISTS `buildings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_id` int NOT NULL,
  `type_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` int NOT NULL DEFAULT '1',
  `last_collection` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_buildings_character` (`character_id`),
  KEY `idx_buildings_type` (`type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `building_production`
--

DROP TABLE IF EXISTS `building_production`;
CREATE TABLE IF NOT EXISTS `building_production` (
  `id` int NOT NULL AUTO_INCREMENT,
  `building_id` int NOT NULL,
  `amount` int NOT NULL,
  `collected_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_building_production_building` (`building_id`),
  KEY `idx_building_production_time` (`collected_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `building_types`
--

DROP TABLE IF EXISTS `building_types`;
CREATE TABLE IF NOT EXISTS `building_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `level_required` int NOT NULL DEFAULT '1',
  `base_cost` int NOT NULL,
  `production_time` int NOT NULL DEFAULT '3600',
  `production_amount` int NOT NULL DEFAULT '100',
  `upgrade_cost_multiplier` int NOT NULL DEFAULT '1000',
  `max_owned` int NOT NULL DEFAULT '3',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_building_types_level` (`level_required`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `building_types`
--

INSERT INTO `building_types` (`id`, `name`, `description`, `level_required`, `base_cost`, `production_time`, `production_amount`, `upgrade_cost_multiplier`, `max_owned`, `created_at`) VALUES
(1, 'Laboratorium metamfetaminy', 'Podstawowe laboratorium do produkcji metamfetaminy.', 1, 5000, 1800, 50, 1000, 3, '2025-03-23 13:58:07'),
(2, 'Plantacja marihuany', 'Miejsce do hodowli marihuany.', 5, 15000, 3600, 100, 2000, 2, '2025-03-23 13:58:07'),
(3, 'Laboratorium kokainy', 'Zaawansowane laboratorium do przetwarzania kokainy.', 10, 50000, 7200, 250, 5000, 1, '2025-03-23 13:58:07');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `characters`
--

DROP TABLE IF EXISTS `characters`;
CREATE TABLE IF NOT EXISTS `characters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(20) NOT NULL,
  `cash` bigint NOT NULL DEFAULT '1000',
  `max_health` int NOT NULL DEFAULT '100',
  `current_health` int NOT NULL DEFAULT '100',
  `max_energy` int NOT NULL DEFAULT '100',
  `current_energy` int NOT NULL DEFAULT '100',
  `level` int NOT NULL DEFAULT '1',
  `experience` int NOT NULL DEFAULT '0',
  `in_jail` tinyint(1) NOT NULL DEFAULT '0',
  `jail_until` datetime DEFAULT NULL,
  `in_hospital` tinyint(1) NOT NULL DEFAULT '0',
  `hospital_until` datetime DEFAULT NULL,
  `last_energy_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_health_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` datetime DEFAULT CURRENT_TIMESTAMP,
  `respect_points` int NOT NULL DEFAULT '0',
  `defense` int NOT NULL DEFAULT '0',
  `attack` int NOT NULL DEFAULT '0',
  `base_health` int NOT NULL DEFAULT '100',
  `base_energy` int NOT NULL DEFAULT '100',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(50) DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_characters_level` (`level`),
  KEY `idx_characters_respect` (`respect_points`),
  KEY `idx_characters_last_activity` (`last_activity`),
  KEY `idx_characters_stats` (`attack`,`defense`),
  KEY `idx_characters_defense` (`defense`),
  KEY `idx_characters_health` (`current_health`,`max_health`),
  KEY `idx_characters_energy` (`current_energy`,`max_energy`),
  KEY `idx_characters_status` (`in_jail`,`in_hospital`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `characters`
--

INSERT INTO `characters` (`id`, `user_id`, `name`, `cash`, `max_health`, `current_health`, `max_energy`, `current_energy`, `level`, `experience`, `in_jail`, `jail_until`, `in_hospital`, `hospital_until`, `last_energy_update`, `last_health_update`, `last_activity`, `respect_points`, `defense`, `attack`, `base_health`, `base_energy`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(6, 13, 'Administrator', 1000, 100, 100, 100, 100, 1, 0, 0, NULL, 0, NULL, '2025-03-25 16:02:42', '2025-03-25 16:02:42', '2025-03-25 16:02:42', 0, 20, 0, 100, 100, '2025-03-25 16:02:42', '2025-03-23 10:28:10', 'System', 'PanKrowa'),
(7, 14, 'Krowek', 1000000, 100, 100, 100, 100, 1, 0, 0, NULL, 0, NULL, '2025-03-25 17:07:30', '2025-03-25 17:07:30', '2025-03-25 17:07:30', 0, 10, 0, 50, 50, '2025-03-25 17:07:30', '2025-03-23 10:28:10', 'System', 'PanKrowa');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `character_drugs`
--

DROP TABLE IF EXISTS `character_drugs`;
CREATE TABLE IF NOT EXISTS `character_drugs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_id` int NOT NULL,
  `drug_id` int NOT NULL,
  `quantity` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `character_id` (`character_id`),
  KEY `drug_id` (`drug_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `character_drugs`
--

INSERT INTO `character_drugs` (`id`, `character_id`, `drug_id`, `quantity`, `created_at`, `updated_at`) VALUES
(1, 6, 1, 0, '2025-03-25 16:06:00', '2025-03-25 16:06:46'),
(2, 6, 1, 1, '2025-03-25 16:10:57', '2025-03-25 16:10:57'),
(3, 6, 1, 1, '2025-03-25 16:11:45', '2025-03-25 16:11:45'),
(4, 6, 1, 20, '2025-03-25 16:11:55', '2025-03-25 16:11:55'),
(5, 7, 1, 20, '2025-03-26 14:30:48', '2025-03-26 14:30:48');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `character_drug_tolerance`
--

DROP TABLE IF EXISTS `character_drug_tolerance`;
CREATE TABLE IF NOT EXISTS `character_drug_tolerance` (
  `character_id` int NOT NULL,
  `drug_id` int NOT NULL,
  `tolerance` float NOT NULL DEFAULT '0',
  `last_used` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`character_id`,`drug_id`),
  KEY `drug_id` (`drug_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `character_equipment`
--

DROP TABLE IF EXISTS `character_equipment`;
CREATE TABLE IF NOT EXISTS `character_equipment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_id` int NOT NULL,
  `equipment_id` int NOT NULL,
  `equipped` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_character_equipment` (`character_id`,`equipment_id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `idx_character_equip` (`character_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `character_items`
--

DROP TABLE IF EXISTS `character_items`;
CREATE TABLE IF NOT EXISTS `character_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_character_items_char` (`character_id`),
  KEY `idx_character_items_item` (`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `character_logs`
--

DROP TABLE IF EXISTS `character_logs`;
CREATE TABLE IF NOT EXISTS `character_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_id` int NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_id` (`character_id`)
) ENGINE=InnoDB AUTO_INCREMENT=429 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `character_logs`
--

INSERT INTO `character_logs` (`id`, `character_id`, `action`, `details`, `created_at`, `created_by`) VALUES
(1, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(2, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(3, 6, 'defense_update', '{\"base\": 0, \"level\": 2, \"stats\": 0, \"total\": 2, \"equipment\": 0}', '2025-03-23 10:28:10', 'PanKrowa'),
(4, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(5, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(6, 6, 'defense_update', '{\"base\": 2, \"level\": 2, \"stats\": 0, \"total\": 4, \"equipment\": 0}', '2025-03-23 10:28:10', 'PanKrowa'),
(7, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(8, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(9, 6, 'defense_update', '{\"base\": 4, \"level\": 2, \"stats\": 0, \"total\": 6, \"equipment\": 0}', '2025-03-23 10:28:10', 'PanKrowa'),
(10, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(11, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(12, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(13, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(14, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(15, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(16, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(17, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(18, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(19, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(20, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(21, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(22, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(23, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(24, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(25, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(26, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(27, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(28, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(29, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(30, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(31, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(32, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(33, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(34, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(35, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(36, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(37, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(38, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(39, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(40, 6, 'defense_update', '{\"base\": 6, \"level\": 2, \"stats\": 0, \"total\": 8, \"equipment\": 0}', '2025-03-23 10:28:10', 'PanKrowa'),
(41, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(42, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(43, 6, 'defense_update', '{\"base\": 8, \"level\": 2, \"stats\": 0, \"total\": 10, \"equipment\": 0}', '2025-03-23 10:28:10', 'PanKrowa'),
(44, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(45, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(46, 6, 'defense_update', '{\"base\": 10, \"level\": 2, \"stats\": 0, \"total\": 12, \"equipment\": 0}', '2025-03-23 10:28:10', 'PanKrowa'),
(47, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(48, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(49, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(50, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(51, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(52, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(53, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(54, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(55, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(56, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(57, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(58, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(59, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(60, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(61, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(62, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(63, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(64, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(65, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(66, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(67, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(68, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(69, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(70, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(71, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(72, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(73, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(74, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(75, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(76, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(77, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(78, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(79, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(80, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(81, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(82, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(83, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(84, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(85, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(86, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(87, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(88, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(89, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(90, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(91, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(92, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(93, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(94, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(95, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(96, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(97, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(98, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(99, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(100, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(101, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(102, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(103, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(104, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(105, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(106, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(107, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(108, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(109, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(110, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(111, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(112, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(113, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(114, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(115, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(116, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(117, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(118, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(119, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(120, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(121, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(122, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(123, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(124, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(125, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(126, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(127, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(128, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(129, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(130, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(131, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(132, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(133, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(134, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(135, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(136, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(137, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(138, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(139, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(140, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(141, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(142, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(143, 6, 'cash_update', '{\"amount\": 1000}', '2025-03-23 10:28:10', 'PanKrowa'),
(144, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(145, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(146, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(147, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(148, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(149, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(150, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(151, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(152, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(153, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(154, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(155, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(156, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(157, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(158, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(159, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(160, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(161, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(162, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(163, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(164, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(165, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(166, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(167, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(168, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(169, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(170, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(171, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(172, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(173, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(174, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(175, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(176, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(177, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(178, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(179, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(180, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(181, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(182, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(183, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(184, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(185, 6, 'defense_update', '{\"base\": 12, \"level\": 2, \"stats\": 0, \"total\": 14, \"equipment\": 0}', '2025-03-23 10:28:10', 'PanKrowa'),
(186, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(187, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(188, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(189, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(190, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(191, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(192, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(193, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(194, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(195, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(196, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(197, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(198, 7, 'defense_update', '{\"base\": 0, \"level\": 2, \"stats\": 0, \"total\": 2, \"equipment\": 0}', '2025-03-23 10:28:10', 'PanKrowa'),
(199, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(200, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(201, 7, 'defense_update', '{\"base\": 2, \"level\": 2, \"stats\": 0, \"total\": 4, \"equipment\": 0}', '2025-03-23 10:28:10', 'PanKrowa'),
(202, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(203, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(204, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(205, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(206, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(207, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(208, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(209, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(210, 6, 'defense_update', '{\"base\": 14, \"level\": 2, \"stats\": 0, \"total\": 16, \"equipment\": 0}', '2025-03-23 10:28:10', 'PanKrowa'),
(211, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(212, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(213, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(214, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(215, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(216, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(217, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(218, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(219, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(220, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(221, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(222, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(223, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(224, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(225, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(226, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(227, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(228, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(229, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(230, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(231, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(232, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(233, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(234, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(235, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(236, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(237, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(238, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(239, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(240, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(241, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(242, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(243, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(244, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(245, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(246, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(247, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(248, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(249, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(250, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(251, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(252, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(253, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(254, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(255, 6, 'defense_update', '{\"base\": 16, \"level\": 2, \"stats\": 0, \"total\": 18, \"equipment\": 0}', '2025-03-23 10:28:10', 'PanKrowa'),
(256, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(257, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(258, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(259, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(260, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(261, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(262, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(263, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(264, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(265, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(266, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(267, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(268, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(269, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(270, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(271, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(272, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(273, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(274, 6, 'defense_update', '{\"base\": 18, \"level\": 2, \"stats\": 0, \"total\": 20, \"equipment\": 0}', '2025-03-23 10:28:10', 'PanKrowa'),
(275, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(276, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(277, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(278, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(279, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(280, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(281, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(282, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(283, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(284, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(285, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(286, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(287, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(288, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(289, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(290, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(291, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(292, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(293, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(294, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(295, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(296, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(297, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(298, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(299, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(300, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(301, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(302, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(303, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(304, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(305, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(306, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(307, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(308, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(309, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(310, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(311, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(312, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(313, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(314, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(315, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(316, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(317, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(318, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(319, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(320, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(321, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(322, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(323, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(324, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(325, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(326, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(327, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(328, 6, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(329, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(330, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(331, 7, 'defense_update', '{\"base\": 4, \"level\": 2, \"stats\": 0, \"total\": 6, \"equipment\": 0}', '2025-03-23 10:28:10', 'PanKrowa'),
(332, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(333, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(334, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(335, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(336, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(337, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(338, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(339, 7, 'defense_update', '{\"base\": 6, \"level\": 2, \"stats\": 0, \"total\": 8, \"equipment\": 0}', '2025-03-23 10:28:10', 'PanKrowa'),
(340, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(341, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(342, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(343, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(344, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(345, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(346, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(347, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(348, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(349, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(350, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(351, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(352, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(353, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(354, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(355, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(356, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(357, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(358, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(359, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(360, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(361, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(362, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(363, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(364, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(365, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(366, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(367, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(368, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(369, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(370, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(371, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(372, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(373, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(374, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(375, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(376, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(377, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(378, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(379, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(380, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(381, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(382, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(383, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(384, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(385, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(386, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(387, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(388, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(389, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(390, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(391, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(392, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(393, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(394, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(395, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(396, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(397, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(398, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(399, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(400, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(401, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(402, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(403, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(404, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(405, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(406, 7, 'defense_update', '{\"base\": 8, \"level\": 2, \"stats\": 0, \"total\": 10, \"equipment\": 0}', '2025-03-23 10:28:10', 'PanKrowa'),
(407, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(408, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(409, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(410, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(411, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(412, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(413, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(414, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(415, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(416, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(417, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(418, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(419, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(420, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(421, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(422, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(423, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(424, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(425, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(426, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(427, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa'),
(428, 7, 'load_character', '[]', '2025-03-23 10:28:10', 'PanKrowa');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `character_prostitutes`
--

DROP TABLE IF EXISTS `character_prostitutes`;
CREATE TABLE IF NOT EXISTS `character_prostitutes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_id` int NOT NULL,
  `prostitute_id` int NOT NULL,
  `last_collection` datetime DEFAULT CURRENT_TIMESTAMP,
  `total_earned` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `prostitute_id` (`prostitute_id`),
  KEY `idx_character_prost` (`character_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `character_stats`
--

DROP TABLE IF EXISTS `character_stats`;
CREATE TABLE IF NOT EXISTS `character_stats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_id` int NOT NULL,
  `strength` int NOT NULL DEFAULT '0',
  `agility` int NOT NULL DEFAULT '0',
  `endurance` int NOT NULL DEFAULT '0',
  `intelligence` int NOT NULL DEFAULT '0',
  `tolerance` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `character_id` (`character_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `clubs`
--

DROP TABLE IF EXISTS `clubs`;
CREATE TABLE IF NOT EXISTS `clubs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_id` int NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` float NOT NULL,
  `entry_fee` float DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `character_id` (`character_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clubs`
--

INSERT INTO `clubs` (`id`, `character_id`, `name`, `price`, `entry_fee`, `created_at`) VALUES
(1, 0, 'Club Alpha', 20000, 50, '2025-03-26 14:20:02'),
(2, 0, 'Club Beta', 30000, 75, '2025-03-26 14:20:02'),
(3, 0, 'Club Gamma', 25000, 60, '2025-03-26 14:20:02');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `club_drugs`
--

DROP TABLE IF EXISTS `club_drugs`;
CREATE TABLE IF NOT EXISTS `club_drugs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `club_id` int NOT NULL,
  `drug_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` float NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `club_id` (`club_id`),
  KEY `drug_id` (`drug_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `club_drugs`
--

INSERT INTO `club_drugs` (`id`, `club_id`, `drug_id`, `quantity`, `price`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 10, 100, '2025-03-25 16:06:46', '2025-03-25 16:06:46');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `club_drug_sales`
--

DROP TABLE IF EXISTS `club_drug_sales`;
CREATE TABLE IF NOT EXISTS `club_drug_sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `club_id` int NOT NULL,
  `drug_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price_per_unit` float NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `club_id` (`club_id`),
  KEY `drug_id` (`drug_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `club_visits`
--

DROP TABLE IF EXISTS `club_visits`;
CREATE TABLE IF NOT EXISTS `club_visits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `club_id` int NOT NULL,
  `character_id` int NOT NULL,
  `visited_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `club_id` (`club_id`),
  KEY `character_id` (`character_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `combat_logs`
--

DROP TABLE IF EXISTS `combat_logs`;
CREATE TABLE IF NOT EXISTS `combat_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attacker_id` int NOT NULL,
  `defender_id` int NOT NULL,
  `damage` int NOT NULL,
  `weapon_used` varchar(50) DEFAULT NULL,
  `armor_used` varchar(50) DEFAULT NULL,
  `money_stolen` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `attacker_id` (`attacker_id`),
  KEY `defender_id` (`defender_id`),
  KEY `idx_combat_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `config`
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE IF NOT EXISTS `config` (
  `key` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  `description` text,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `config`
--

INSERT INTO `config` (`key`, `value`, `description`, `updated_at`) VALUES
('ENERGY_REGEN_RATE', '1', 'Ilość energii regenerowanej na minutę', '2025-03-23 11:50:48'),
('EXP_PER_LEVEL', '1000', 'Ilość doświadczenia potrzebna na poziom', '2025-03-23 11:50:48'),
('HEALTH_REGEN_RATE', '1', 'Ilość zdrowia regenerowanego na minutę', '2025-03-23 11:50:48'),
('HOSPITAL_HEAL_COST', '100', 'Koszt leczenia za 1 punkt zdrowia', '2025-03-23 11:50:48'),
('HOSPITAL_HEAL_TIME', '300', 'Czas leczenia w sekundach za 1 punkt zdrowia', '2025-03-23 11:50:48'),
('INITIAL_CASH', '1000', 'Początkowa ilość gotówki dla nowego gracza', '2025-03-23 11:50:48'),
('JAIL_BRIBE_BASE_COST', '1000', 'Podstawowy koszt przekupstwa', '2025-03-23 11:50:48'),
('JAIL_ESCAPE_BASE_CHANCE', '30', 'Podstawowa szansa na ucieczkę (%)', '2025-03-23 11:50:48'),
('MARKET_LISTING_DURATION', '72', 'Czas trwania oferty na rynku (godziny)', '2025-03-23 11:50:48'),
('MAX_MARKET_LISTINGS', '10', 'Maksymalna ilość ofert na rynku per gracz', '2025-03-23 11:50:48');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `config_constants`
--

DROP TABLE IF EXISTS `config_constants`;
CREATE TABLE IF NOT EXISTS `config_constants` (
  `key` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  `type` enum('int','float','string','bool') NOT NULL DEFAULT 'string',
  `description` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `config_constants`
--

INSERT INTO `config_constants` (`key`, `value`, `type`, `description`, `created_at`, `updated_at`) VALUES
('BUILDING_MAX_LEVEL', '10', 'int', 'Maksymalny poziom budynku', '2025-03-23 13:04:42', '2025-03-23 13:04:42'),
('HOSPITAL_HEAL_COST', '100', 'int', 'Koszt leczenia za 1 punkt zdrowia', '2025-03-23 13:04:42', '2025-03-23 13:04:42'),
('HOSPITAL_HEAL_TIME', '300', 'int', 'Czas leczenia w sekundach za 1 punkt zdrowia', '2025-03-23 13:04:42', '2025-03-23 13:04:42'),
('JAIL_BRIBE_BASE_COST', '1000', 'int', 'Podstawowy koszt przekupstwa', '2025-03-23 13:04:42', '2025-03-23 13:04:42'),
('JAIL_ESCAPE_BASE_CHANCE', '30', 'int', 'Podstawowa szansa na ucieczkę (%)', '2025-03-23 13:04:42', '2025-03-23 13:04:42');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `drugs`
--

DROP TABLE IF EXISTS `drugs`;
CREATE TABLE IF NOT EXISTS `drugs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `energy_boost` int NOT NULL,
  `addiction_rate` float NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `level_required` int NOT NULL DEFAULT '0',
  `dealer_price` float NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `initial_energy_effect` float NOT NULL DEFAULT '0',
  `energy_effect_50_tolerance` float NOT NULL DEFAULT '0',
  `energy_effect_100_tolerance` float NOT NULL DEFAULT '0',
  `tolerance_increase_per_use` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `drugs`
--

INSERT INTO `drugs` (`id`, `name`, `energy_boost`, `addiction_rate`, `created_at`, `updated_at`, `level_required`, `dealer_price`, `description`, `initial_energy_effect`, `energy_effect_50_tolerance`, `energy_effect_100_tolerance`, `tolerance_increase_per_use`) VALUES
(1, 'Weed', 10, 5, '2025-03-25 16:01:28', '2025-03-25 16:01:28', 1, 20, 'A mild drug that gives a small boost of energy.', 0, 0, 0, 0),
(2, 'Cocaine', 50, 25, '2025-03-25 16:01:28', '2025-03-25 16:01:28', 5, 100, 'A strong stimulant that gives a high boost of energy.', 0, 0, 0, 0),
(3, 'Ecstasy', 30, 15, '2025-03-25 16:01:28', '2025-03-25 16:01:28', 3, 50, 'A party drug that increases energy and euphoria.', 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `equipment`
--

DROP TABLE IF EXISTS `equipment`;
CREATE TABLE IF NOT EXISTS `equipment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('weapon','armor') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `attack` int NOT NULL DEFAULT '0',
  `defense` int NOT NULL DEFAULT '0',
  `price` int NOT NULL,
  `level_required` int NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `name`, `type`, `description`, `attack`, `defense`, `price`, `level_required`, `created_at`, `updated_at`) VALUES
(1, 'Nóż', 'weapon', 'Podstawowa broń do walki wręcz', 5, 0, 1000, 1, '2025-03-23 14:55:33', '2025-03-23 14:55:33'),
(2, 'Pistolet', 'weapon', 'Standardowa broń palna', 10, 0, 5000, 5, '2025-03-23 14:55:33', '2025-03-23 14:55:33'),
(3, 'UZI', 'weapon', 'Broń automatyczna', 15, 0, 15000, 10, '2025-03-23 14:55:33', '2025-03-23 14:55:33'),
(4, 'Karabin snajperski', 'weapon', 'Broń dalekiego zasięgu', 25, 0, 50000, 15, '2025-03-23 14:55:33', '2025-03-23 14:55:33'),
(5, 'Kamizelka', 'armor', 'Podstawowa ochrona', 0, 5, 2000, 1, '2025-03-23 14:55:33', '2025-03-23 14:55:33'),
(6, 'Kevlar', 'armor', 'Zaawansowana ochrona', 0, 10, 10000, 5, '2025-03-23 14:55:33', '2025-03-23 14:55:33'),
(7, 'Pancerz wojskowy', 'armor', 'Ciężki pancerz', 0, 20, 30000, 10, '2025-03-23 14:55:33', '2025-03-23 14:55:33'),
(8, 'Taktyczny kombinezon', 'armor', 'Najlepsze zabezpieczenie', 5, 25, 100000, 15, '2025-03-23 14:55:33', '2025-03-23 14:55:33');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `fight_logs`
--

DROP TABLE IF EXISTS `fight_logs`;
CREATE TABLE IF NOT EXISTS `fight_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attacker_id` int NOT NULL,
  `defender_id` int NOT NULL,
  `winner_id` int NOT NULL,
  `damage_dealt` int NOT NULL,
  `experience_gained` int NOT NULL,
  `money_stolen` int NOT NULL DEFAULT '0',
  `respect_gained` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `attacker_id` (`attacker_id`),
  KEY `defender_id` (`defender_id`),
  KEY `winner_id` (`winner_id`),
  KEY `idx_fight_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `game_config`
--

DROP TABLE IF EXISTS `game_config`;
CREATE TABLE IF NOT EXISTS `game_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `game_config`
--

INSERT INTO `game_config` (`id`, `name`, `value`, `description`, `created_at`) VALUES
(1, 'MAX_ROBBERY_ATTEMPTS', '3', 'Maksymalna liczba prób napadu w ciągu godziny', '2025-03-23 13:41:30'),
(2, 'ROBBERY_BASE_EXPERIENCE', '50', 'Podstawowe doświadczenie za udany napad', '2025-03-23 13:41:30'),
(3, 'ROBBERY_LEVEL_BONUS', '10', 'Bonus do doświadczenia za każdy poziom lokacji', '2025-03-23 13:41:30'),
(4, 'ROBBERY_FAIL_ENERGY_RETURN', '50', 'Procent energii zwracanej przy nieudanym napadzie', '2025-03-23 13:41:30'),
(5, 'MAX_CLUB_VISITS', '5', 'Maksymalna liczba wizyt w klubie na godzinę', '2025-03-23 13:51:54'),
(6, 'CLUB_ENERGY_BASE', '10', 'Podstawowa ilość energii otrzymywana w klubie', '2025-03-23 13:51:54'),
(7, 'CLUB_ENERGY_MULTIPLIER', '2', 'Mnożnik energii bazowej dla droższych drinków', '2025-03-23 13:51:54'),
(8, 'MAX_BUILDINGS_PER_TYPE', '3', 'Maksymalna liczba budynków danego typu', '2025-03-23 13:58:08'),
(9, 'MIN_BUILDING_PRODUCTION_TIME', '1800', 'Minimalny czas produkcji (w sekundach)', '2025-03-23 13:58:08'),
(10, 'MAX_BUILDING_LEVEL', '10', 'Maksymalny poziom budynku', '2025-03-23 13:58:08'),
(11, 'MARKET_FEE_PERCENT', '5', 'Procent prowizji od transakcji na rynku', '2025-03-23 14:07:07'),
(12, 'MAX_MARKET_LISTINGS', '10', 'Maksymalna liczba ofert jednego gracza', '2025-03-23 14:07:07'),
(13, 'MIN_MARKET_PRICE', '1', 'Minimalna cena przedmiotu na rynku', '2025-03-23 14:07:07'),
(14, 'MAX_MARKET_PRICE', '1000000', 'Maksymalna cena przedmiotu na rynku', '2025-03-23 14:07:07'),
(15, 'MAX_EQUIPMENT_SLOTS', '4', 'Maksymalna liczba założonego ekwipunku', '2025-03-23 14:16:46'),
(16, 'MAX_PROSTITUTES', '10', 'Maksymalna liczba prostytutek', '2025-03-23 14:16:46'),
(17, 'MAX_CLUB_DRUGS', '5', 'Maksymalna liczba rodzajów narkotyków w klubie', '2025-03-23 14:16:46'),
(18, 'CLUB_INCOME_INTERVAL', '3600', 'Częstotliwość naliczania dochodów z klubu (w sekundach)', '2025-03-23 14:16:46'),
(19, 'DRUG_ADDICTION_CHECK_INTERVAL', '86400', 'Częstotliwość sprawdzania uzależnienia (w sekundach)', '2025-03-23 14:16:46'),
(20, 'PROSTITUTE_COLLECTION_INTERVAL', '3600', 'Częstotliwość zbierania zarobków od prostytutek (w sekundach)', '2025-03-23 14:16:46'),
(21, 'MAX_CLUBS', '3', 'Maksymalna liczba klubów', '2025-03-23 14:55:34');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `hospital_logs`
--

DROP TABLE IF EXISTS `hospital_logs`;
CREATE TABLE IF NOT EXISTS `hospital_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_id` int NOT NULL,
  `health_restored` int NOT NULL DEFAULT '0',
  `healing_time` int NOT NULL DEFAULT '0',
  `cost` int NOT NULL,
  `type` enum('healing','injury','disease') NOT NULL DEFAULT 'healing',
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `character_id` (`character_id`),
  KEY `idx_hospital_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `items`
--

DROP TABLE IF EXISTS `items`;
CREATE TABLE IF NOT EXISTS `items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `damage` int NOT NULL DEFAULT '0',
  `defense` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_items_type` (`type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `type_id`, `name`, `description`, `damage`, `defense`, `created_at`) VALUES
(1, 1, 'Nóż', 'Podstawowa broń do walki wręcz', 5, 0, '2025-03-23 14:07:06'),
(2, 1, 'Pistolet', 'Standardowa broń palna', 10, 0, '2025-03-23 14:07:06'),
(3, 2, 'Kamizelka', 'Podstawowa ochrona', 0, 5, '2025-03-23 14:07:06'),
(4, 2, 'Kevlar', 'Zaawansowana ochrona', 0, 10, '2025-03-23 14:07:06'),
(5, 3, 'Metamfetamina', 'Czysty produkt', 0, 0, '2025-03-23 14:07:06'),
(6, 3, 'Marihuana', 'Wysokiej jakości zioło', 0, 0, '2025-03-23 14:07:06'),
(7, 4, 'Zestaw laboratoryjny', 'Potrzebny do produkcji', 0, 0, '2025-03-23 14:07:06'),
(8, 4, 'Materiały hodowlane', 'Do uprawy roślin', 0, 0, '2025-03-23 14:07:06');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `item_types`
--

DROP TABLE IF EXISTS `item_types`;
CREATE TABLE IF NOT EXISTS `item_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `item_types`
--

INSERT INTO `item_types` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Broń', 'Przedmioty służące do walki', '2025-03-23 14:07:06'),
(2, 'Pancerz', 'Przedmioty zapewniające ochronę', '2025-03-23 14:07:06'),
(3, 'Narkotyki', 'Różne rodzaje narkotyków', '2025-03-23 14:07:06'),
(4, 'Narzędzia', 'Przedmioty potrzebne do produkcji', '2025-03-23 14:07:06');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `jail_logs`
--

DROP TABLE IF EXISTS `jail_logs`;
CREATE TABLE IF NOT EXISTS `jail_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_id` int NOT NULL,
  `action_type` enum('arrest','escape','release','bribe') NOT NULL,
  `sentence_hours` int NOT NULL DEFAULT '0',
  `escape_chance` int NOT NULL DEFAULT '0',
  `bribe_amount` int NOT NULL DEFAULT '0',
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `released_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `character_id` (`character_id`),
  KEY `idx_jail_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `market_listings`
--

DROP TABLE IF EXISTS `market_listings`;
CREATE TABLE IF NOT EXISTS `market_listings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `seller_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_market_listings_seller` (`seller_id`),
  KEY `idx_market_listings_item` (`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `market_transactions`
--

DROP TABLE IF EXISTS `market_transactions`;
CREATE TABLE IF NOT EXISTS `market_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `listing_id` int NOT NULL,
  `item_id` int NOT NULL,
  `seller_id` int NOT NULL,
  `buyer_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_market_transactions_listing` (`listing_id`),
  KEY `idx_market_transactions_seller` (`seller_id`),
  KEY `idx_market_transactions_buyer` (`buyer_id`),
  KEY `idx_market_transactions_item` (`item_id`),
  KEY `idx_market_transactions_time` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `nightclubs`
--

DROP TABLE IF EXISTS `nightclubs`;
CREATE TABLE IF NOT EXISTS `nightclubs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `level_required` int NOT NULL DEFAULT '1',
  `available_drugs` json NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nightclubs_level` (`level_required`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nightclubs`
--

INSERT INTO `nightclubs` (`id`, `name`, `description`, `level_required`, `available_drugs`, `created_at`) VALUES
(1, 'Neon Dreams', 'Popularny klub dla początkujących imprezowiczów. Muzyka elektroniczna i przystępne ceny.', 1, '{\"piwo\": 100, \"wódka\": 200}', '2025-03-23 13:51:54'),
(2, 'Purple Haze', 'Ekskluzywny klub z długą historią. Znany z mocnych drinków i klimatycznej muzyki.', 5, '{\"whisky\": 500, \"wódka\": 300}', '2025-03-23 13:51:54'),
(3, 'Golden Mile', 'Najdroższy klub w mieście. Tylko dla elit.', 10, '{\"whisky\": 1000, \"szampan\": 2000}', '2025-03-23 13:51:54');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `nightclub_visits`
--

DROP TABLE IF EXISTS `nightclub_visits`;
CREATE TABLE IF NOT EXISTS `nightclub_visits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nightclub_id` int NOT NULL,
  `character_id` int NOT NULL,
  `drug_used` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_cost` int NOT NULL,
  `energy_gained` int NOT NULL,
  `visited_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nightclub_visits_char` (`character_id`),
  KEY `idx_nightclub_visits_club` (`nightclub_id`),
  KEY `idx_nightclub_visits_time` (`visited_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `prostitutes`
--

DROP TABLE IF EXISTS `prostitutes`;
CREATE TABLE IF NOT EXISTS `prostitutes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `hourly_income` int NOT NULL,
  `price` int NOT NULL,
  `level_required` int NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prostitutes`
--

INSERT INTO `prostitutes` (`id`, `name`, `description`, `hourly_income`, `price`, `level_required`, `created_at`, `updated_at`) VALUES
(1, 'Początkująca', 'Niedoświadczona, ale chętna do nauki', 100, 5000, 1, '2025-03-23 14:55:33', '2025-03-23 14:55:33'),
(2, 'Doświadczona', 'Zna się na rzeczy', 300, 15000, 5, '2025-03-23 14:55:33', '2025-03-23 14:55:33'),
(3, 'Ekskluzywna', 'Najwyższa jakość usług', 1000, 50000, 10, '2025-03-23 14:55:33', '2025-03-23 14:55:33'),
(4, 'Gwiazda', 'Celebrytka w branży', 2500, 100000, 15, '2025-03-23 14:55:33', '2025-03-23 14:55:33');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `robberies`
--

DROP TABLE IF EXISTS `robberies`;
CREATE TABLE IF NOT EXISTS `robberies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `location_id` int NOT NULL,
  `character_id` int NOT NULL,
  `status` enum('available','in_progress','completed','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_robberies_status` (`status`),
  KEY `idx_robberies_character` (`character_id`),
  KEY `idx_robberies_location` (`location_id`),
  KEY `idx_robberies_availability` (`status`,`character_id`,`location_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `robbery_locations`
--

DROP TABLE IF EXISTS `robbery_locations`;
CREATE TABLE IF NOT EXISTS `robbery_locations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_level` int NOT NULL DEFAULT '1',
  `min_cash` int NOT NULL,
  `max_cash` int NOT NULL,
  `energy_cost` int NOT NULL,
  `success_chance` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_robbery_locations_level` (`min_level`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `robbery_locations`
--

INSERT INTO `robbery_locations` (`id`, `name`, `min_level`, `min_cash`, `max_cash`, `energy_cost`, `success_chance`, `created_at`) VALUES
(1, 'Sklep spożywczy', 1, 100, 500, 5, 80, '2025-03-23 13:41:29'),
(2, 'Bank lokalny', 5, 500, 2500, 10, 60, '2025-03-23 13:41:29'),
(3, 'Jubiler', 10, 1000, 5000, 15, 50, '2025-03-23 13:41:29'),
(4, 'Konwój', 15, 2500, 10000, 20, 40, '2025-03-23 13:41:29'),
(5, 'Bank narodowy', 20, 5000, 25000, 25, 30, '2025-03-23 13:41:29');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `robbery_logs`
--

DROP TABLE IF EXISTS `robbery_logs`;
CREATE TABLE IF NOT EXISTS `robbery_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `robbery_id` int NOT NULL,
  `character_id` int NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `cash_gained` int NOT NULL DEFAULT '0',
  `experience_gained` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_robbery_logs_character` (`character_id`),
  KEY `idx_robbery_logs_robbery` (`robbery_id`),
  KEY `idx_robbery_logs_success` (`success`),
  KEY `idx_robbery_logs_created` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `training_logs`
--

DROP TABLE IF EXISTS `training_logs`;
CREATE TABLE IF NOT EXISTS `training_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_id` int NOT NULL,
  `stat_trained` varchar(20) NOT NULL,
  `amount_gained` int NOT NULL,
  `energy_spent` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `character_id` (`character_id`),
  KEY `idx_training_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `created_by`, `last_login`) VALUES
(13, 'Administrator', 'pankrowa7@gmail.com', '$2y$10$cS4jHgwLv58NNB6WXGssVONCoSo/aTHsnCgWFV42m/uh.7B9DfT72', '2025-03-25 16:02:42', 'System', '2025-03-26 14:49:25'),
(14, 'Krowek', 'Elemtd@gmail.com', '$2y$10$Gl3VYexKUNw31KHtnSB.beBkZKWWarkz542OxGKkAILrDBL6mVsI.', '2025-03-25 17:07:30', 'System', '2025-03-26 15:29:32');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `characters`
--
ALTER TABLE `characters`
  ADD CONSTRAINT `fk_character_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `character_equipment`
--
ALTER TABLE `character_equipment`
  ADD CONSTRAINT `character_equipment_ibfk_1` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `character_equipment_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`);

--
-- Constraints for table `character_logs`
--
ALTER TABLE `character_logs`
  ADD CONSTRAINT `character_logs_ibfk_1` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `character_prostitutes`
--
ALTER TABLE `character_prostitutes`
  ADD CONSTRAINT `character_prostitutes_ibfk_1` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `character_prostitutes_ibfk_2` FOREIGN KEY (`prostitute_id`) REFERENCES `prostitutes` (`id`);

--
-- Constraints for table `character_stats`
--
ALTER TABLE `character_stats`
  ADD CONSTRAINT `character_stats_ibfk_1` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `combat_logs`
--
ALTER TABLE `combat_logs`
  ADD CONSTRAINT `combat_logs_ibfk_1` FOREIGN KEY (`attacker_id`) REFERENCES `characters` (`id`),
  ADD CONSTRAINT `combat_logs_ibfk_2` FOREIGN KEY (`defender_id`) REFERENCES `characters` (`id`);

--
-- Constraints for table `fight_logs`
--
ALTER TABLE `fight_logs`
  ADD CONSTRAINT `fight_logs_ibfk_1` FOREIGN KEY (`attacker_id`) REFERENCES `characters` (`id`),
  ADD CONSTRAINT `fight_logs_ibfk_2` FOREIGN KEY (`defender_id`) REFERENCES `characters` (`id`),
  ADD CONSTRAINT `fight_logs_ibfk_3` FOREIGN KEY (`winner_id`) REFERENCES `characters` (`id`);

--
-- Constraints for table `hospital_logs`
--
ALTER TABLE `hospital_logs`
  ADD CONSTRAINT `hospital_logs_ibfk_1` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jail_logs`
--
ALTER TABLE `jail_logs`
  ADD CONSTRAINT `jail_logs_ibfk_1` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `training_logs`
--
ALTER TABLE `training_logs`
  ADD CONSTRAINT `training_logs_ibfk_1` FOREIGN KEY (`character_id`) REFERENCES `characters` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

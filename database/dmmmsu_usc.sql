-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 20, 2026 at 02:58 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dmmmsu_usc`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int(11) NOT NULL,
  `label` varchar(80) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `label`, `start_date`, `end_date`, `is_active`, `is_archived`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '2026-2027', '2026-06-01', '2027-05-31', 1, 0, NULL, '2026-08-19 23:22:41', '2026-08-20 04:13:46'),
(2, 'Legacy / Imported', '2000-01-01', '2000-12-31', 0, 1, NULL, '2026-08-19 23:22:41', '2026-08-20 04:13:48'),
(3, '2027-2028', '2027-06-11', '2028-05-31', 0, 0, 1, '2026-08-20 03:22:13', '2026-08-20 04:16:00');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(80) NOT NULL,
  `email` varchar(180) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` varchar(40) NOT NULL DEFAULT 'admin',
  `campus` varchar(20) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `status_reason` varchar(255) DEFAULT NULL,
  `status_changed_at` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `force_password_change` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_activity_at` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `two_factor_secret` text DEFAULT NULL,
  `security_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `profile_image`, `password_hash`, `full_name`, `role`, `campus`, `status`, `status_reason`, `status_changed_at`, `archived_at`, `force_password_change`, `created_by`, `approved_at`, `approved_by`, `last_login`, `last_activity_at`, `password_changed_at`, `two_factor_enabled`, `two_factor_secret`, `security_note`, `created_at`, `updated_at`) VALUES
(1, 'admin', NULL, 'uploads/admin-profiles/profile_cef24d3d36209f274e3b.png', '$2y$12$aZf604ZmyKV3rSh1RzogfOU64vZiCA4zmS6sUmbneNHtqtie12a6y', 'System Administrator', 'admin', NULL, 'active', NULL, '2026-08-20 09:19:38', NULL, 0, NULL, '2026-08-20 09:19:38', 1, '2026-08-19 22:50:47', '2026-08-20 19:53:21', NULL, 1, 'enc:v1:xTcKK/YwvLwzXjllLH0SNFLELbKq8PXJ7wXu5Ajk0pZftrpSX8FubQJmrIewPDyBbFrLbEPeAomEj1Un', NULL, '2026-08-15 10:10:33', '2026-08-20 11:53:21'),
(2, 'usc', NULL, 'uploads/admin-profiles/profile_b77ff1beae3e6ca4c846.jpg', '$2y$10$YjpiLaap0BV8V205FoF9S.vWUN9NDmZIz1TdpBr47lEeElJiQIr2i', 'University Student Council', 'usc', NULL, 'active', NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-08-20 19:41:27', '2026-08-20 19:49:28', NULL, 0, NULL, NULL, '2026-08-17 10:49:11', '2026-08-20 11:49:28'),
(3, 'sasdirector', NULL, NULL, '$2y$10$mc/oYYcF0uCPrGP1xI1zkOZTwV/xLkl6eK.Gau5o6.3zWxuYmTCo6', 'Director', 'sas_director', NULL, 'active', NULL, '2026-08-20 18:25:37', NULL, 0, NULL, '2026-08-20 18:25:37', 1, NULL, NULL, NULL, 0, NULL, NULL, '2026-08-17 10:49:45', '2026-08-20 10:25:37'),
(4, 'mlucsashead', NULL, NULL, '$2y$10$lg3eTR5a3SrYhmPXb.FdrOMMhtNVIdyCP0UJnJQuO.cydriBflb1a', 'Mid La Union Campus', 'campus_sas_head', 'MLUC', 'active', NULL, '2026-08-20 18:29:42', NULL, 0, NULL, '2026-08-20 18:29:42', 1, NULL, NULL, NULL, 0, NULL, NULL, '2026-08-17 10:50:29', '2026-08-20 10:29:42'),
(5, 'slucadviser', NULL, NULL, '$2y$10$lMS2iYyZeLsQuwnERKe8QeOjk0zePVeDQrweLJxQhvVj/Kl3VE.na', 'SLUC CSBO Adviser', 'sbo_adviser', 'SLUC', 'active', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-08-17 10:51:10', '2026-08-17 10:58:25'),
(6, 'sluccsbo', NULL, 'uploads/admin-profiles/profile_24608fd2ed025765067e.jpg', '$2y$10$yElDrG9TYxspmGm4ie6quOu0AZiJGvGUKCGl5XYR4T70pFkj39sre', 'SLUC CSBO', 'campus_sbo', 'SLUC', 'active', NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-08-20 17:26:15', '2026-08-20 19:48:25', NULL, 0, NULL, NULL, '2026-08-17 10:51:52', '2026-08-20 11:48:25'),
(7, 'nlucsashead', NULL, NULL, '$2y$10$HqMbppg7D0iP/S49JcBmtuFR2mQ2PsqQH8.rQAXowDIMB5LzTm4TK', 'NLUC SAS Head', 'campus_sas_head', 'NLUC', 'active', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-08-17 10:59:58', '2026-08-17 10:59:58'),
(8, 'slucsashead', NULL, NULL, '$2y$10$pU4fUkmEhv8N2KC55jpbN.i3aMiA.66iGOWkd3juXp0AdfxBSTSdC', 'SLUC SAS Head', 'campus_sas_head', 'SLUC', 'active', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-08-17 11:00:30', '2026-08-17 11:00:30'),
(9, 'oussashead', NULL, NULL, '$2y$10$Z/Jlp0egde0/yyDQKdlbYeL9jIp1DC4wngTSGv21wvqQVf5vpWZGu', 'OUS SAS Head', 'campus_sas_head', 'OUS', 'active', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-08-17 11:00:56', '2026-08-17 11:00:56'),
(10, 'nlucsboadviser', NULL, NULL, '$2y$10$5npryspOgBszwi.fNYNKiOjs.hVKGzcbjhqCsYN020Plv4n0Abg6K', 'North La Union Campus', 'sbo_adviser', 'NLUC', 'active', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-08-17 11:01:53', '2026-08-18 11:50:40'),
(11, 'nluccsbo', NULL, 'uploads/admin-profiles/profile_600ee8c9f6f9570fcafc.jpg', '$2y$10$0fresQFEWVltQC9TY486OOTyZ0wbGHoJiSLpbe89aTDK10T3t6inK', 'NLUC CSBO', 'campus_sbo', 'NLUC', 'active', NULL, NULL, NULL, 1, 1, NULL, NULL, NULL, NULL, '2026-08-18 23:14:10', 0, NULL, NULL, '2026-08-18 15:14:10', '2026-08-18 15:14:10'),
(12, 'ouscsbo', NULL, 'uploads/admin-profiles/profile_241c9aa88e57f7704d53.jpg', '$2y$10$B/cnrGrJyTPxVRMznCiLSOvspLD1Ww1zokZyAzU8SbvJEnUmbDPTm', 'OUS CSBO', 'campus_sbo', 'OUS', 'active', NULL, NULL, NULL, 1, 1, NULL, NULL, NULL, NULL, '2026-08-18 23:14:56', 0, NULL, NULL, '2026-08-18 15:14:56', '2026-08-18 15:14:56'),
(13, 'mluccsbo', NULL, 'uploads/admin-profiles/profile_ef4d9048e985def314b6.jpg', '$2y$10$1Ln6/x7/LLKaSYRH/YMkvedyihD0udJqj1zPALWvnRjMJJIEYnE3C', 'MLUC CSBO', 'campus_sbo', 'MLUC', 'active', NULL, NULL, NULL, 1, 1, NULL, NULL, NULL, NULL, '2026-08-18 23:15:39', 0, NULL, NULL, '2026-08-18 15:15:39', '2026-08-18 15:16:09');

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_logs`
--

CREATE TABLE `admin_activity_logs` (
  `id` bigint(20) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `admin_name` varchar(150) DEFAULT NULL,
  `role` varchar(40) DEFAULT NULL,
  `campus` varchar(20) DEFAULT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(80) NOT NULL,
  `description` varchar(500) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_values` longtext DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `prev_hash` char(64) DEFAULT NULL,
  `record_hash` char(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_activity_logs`
--

INSERT INTO `admin_activity_logs` (`id`, `admin_id`, `admin_name`, `role`, `campus`, `module`, `action`, `description`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `prev_hash`, `record_hash`, `created_at`) VALUES
(1, 1, 'USC Administrator', 'admin', NULL, 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 1, NULL, NULL, NULL, NULL, '0000000000000000000000000000000000000000000000000000000000000000', '04289c53466031a22628a529629f007b9a2838564f709ae52955542958bd62ee', '2026-08-17 12:50:00'),
(2, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 6, NULL, NULL, NULL, NULL, '04289c53466031a22628a529629f007b9a2838564f709ae52955542958bd62ee', 'd5af8d5bff6d6bb4ff0195daa3e8b7a7aabb831045d7d647f3a382f871f1efa3', '2026-08-17 14:02:44'),
(3, 1, 'USC Administrator', 'admin', NULL, 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 1, NULL, NULL, NULL, NULL, 'd5af8d5bff6d6bb4ff0195daa3e8b7a7aabb831045d7d647f3a382f871f1efa3', '35a04a9ff092be3c71ae054a653cac3b5fbfc9af1131326308403767e23bc5d1', '2026-08-17 15:10:31'),
(4, 1, 'USC Administrator', 'admin', NULL, 'auth', 'Signed out', 'Administrator signed out', 'admin', 1, NULL, NULL, NULL, NULL, '35a04a9ff092be3c71ae054a653cac3b5fbfc9af1131326308403767e23bc5d1', 'ebb2bb0c58ba0392240690b6bea3998c86c8576e4e2d163e662ef8034ddf1d96', '2026-08-17 15:10:52'),
(5, 1, 'USC Administrator', 'admin', NULL, 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 1, NULL, NULL, NULL, NULL, 'ebb2bb0c58ba0392240690b6bea3998c86c8576e4e2d163e662ef8034ddf1d96', '8f98f883a2d2935d3f0212b887b10b9789f2d5a81584238e93a14ba8d478660e', '2026-08-17 15:14:09'),
(6, 1, 'USC Administrator', 'admin', NULL, 'auth', 'Signed out', 'Administrator signed out', 'admin', 1, NULL, NULL, NULL, NULL, '8f98f883a2d2935d3f0212b887b10b9789f2d5a81584238e93a14ba8d478660e', 'ce15d24dd9df2f62cd18783f37e15d922ee7578ca25d25dac62aac11ee6e61bd', '2026-08-17 15:29:06'),
(7, 1, 'USC Administrator', 'admin', NULL, 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 1, NULL, NULL, NULL, NULL, 'ce15d24dd9df2f62cd18783f37e15d922ee7578ca25d25dac62aac11ee6e61bd', '05fcd68a0075abd708d641e36ab56a6cdb3fe9c84253a1033ff4094980ee7077', '2026-08-17 15:34:23'),
(8, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'Your voice deserves a clear channel.', 'hero_slide', 1, NULL, NULL, NULL, NULL, '05fcd68a0075abd708d641e36ab56a6cdb3fe9c84253a1033ff4094980ee7077', '7a6a91bf216b30f1e515965450c7035c4b7ce3c4e33ac4ae4534e40916b4fd96', '2026-08-18 02:01:23'),
(9, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'Your voice deserves a clear channel.', 'hero_slide', 1, NULL, NULL, NULL, NULL, '7a6a91bf216b30f1e515965450c7035c4b7ce3c4e33ac4ae4534e40916b4fd96', '50332685d0db402eb24ca0f6b8c75bfa22f6daf7df98c32bb843d02b2962c014', '2026-08-18 02:02:36'),
(10, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'Your voice deserves a clear channel.', 'hero_slide', 1, NULL, NULL, NULL, NULL, '50332685d0db402eb24ca0f6b8c75bfa22f6daf7df98c32bb843d02b2962c014', 'caa6fb72a560aade7308dbf18ebaf6515cecb464f28064a246f2be1bea93a0be', '2026-08-18 02:07:38'),
(11, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'Your voice deserves a clear channel.', 'hero_slide', 1, NULL, NULL, NULL, NULL, 'caa6fb72a560aade7308dbf18ebaf6515cecb464f28064a246f2be1bea93a0be', '796a8f3d4d173f813a24aac51c6680eb6587e2054de92309620bd67da33fc597', '2026-08-18 02:07:58'),
(12, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'Your voice deserves a clear channel.', 'hero_slide', 1, NULL, NULL, NULL, NULL, '796a8f3d4d173f813a24aac51c6680eb6587e2054de92309620bd67da33fc597', 'b6a6030556109299a0a2e1d2e1b828d66f41e40fb3ae3e57d0bd9daca3205aa2', '2026-08-18 02:24:40'),
(13, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'Your voice deserves a clear channel.', 'hero_slide', 1, NULL, NULL, NULL, NULL, 'b6a6030556109299a0a2e1d2e1b828d66f41e40fb3ae3e57d0bd9daca3205aa2', '5f01827e8c7178c70f53a7e34abfb1ff4bdc6f07ac646250e4808e44fce77f26', '2026-08-18 02:25:14'),
(14, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '5f01827e8c7178c70f53a7e34abfb1ff4bdc6f07ac646250e4808e44fce77f26', '8b5e18687785870cf2f7cff2f53fb5ebb4a799c2a0132fbb462dd86109bc756e', '2026-08-18 03:44:09'),
(15, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '8b5e18687785870cf2f7cff2f53fb5ebb4a799c2a0132fbb462dd86109bc756e', '5e79644b70c0e1a26734d0ba695d1692709fb812fd711b7a1adc730c17d0bab2', '2026-08-18 03:44:12'),
(16, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '5e79644b70c0e1a26734d0ba695d1692709fb812fd711b7a1adc730c17d0bab2', '0464e0c0200b114c247ce0ff2eb9bac8780ae9c9e32871d6eb35e701b70ad568', '2026-08-18 03:53:03'),
(17, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '0464e0c0200b114c247ce0ff2eb9bac8780ae9c9e32871d6eb35e701b70ad568', '5e1b7e718a4a5586dea9263668cc3d12d2a6a17a3d33e23fd3019fbc6e0e7af0', '2026-08-18 03:53:28'),
(18, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '5e1b7e718a4a5586dea9263668cc3d12d2a6a17a3d33e23fd3019fbc6e0e7af0', '324d9c7df77d792e234f02b2d7e1c4af7f1d650ccaefb16d42b072ba9e907e34', '2026-08-18 03:54:05'),
(19, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '324d9c7df77d792e234f02b2d7e1c4af7f1d650ccaefb16d42b072ba9e907e34', '03ec237388740d8b75d98c8710a9fe4009d6272ca676f36fcf7c79c56b2e4651', '2026-08-18 03:54:24'),
(20, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '03ec237388740d8b75d98c8710a9fe4009d6272ca676f36fcf7c79c56b2e4651', '57c79b588e5b0cf6bf77db843d1fe271bebeec5fdbe31a9dfb526ad44fa150d1', '2026-08-18 03:59:43'),
(21, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '57c79b588e5b0cf6bf77db843d1fe271bebeec5fdbe31a9dfb526ad44fa150d1', 'd623978dc2a5bab045e80566d5046c9594615197fb96f51857c2c6411800d30d', '2026-08-18 04:09:05'),
(22, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, 'd623978dc2a5bab045e80566d5046c9594615197fb96f51857c2c6411800d30d', '671d8f2a6f816429c6ec303ead3e2940e2a75abcbe6dc8cad6f1118ccf43808b', '2026-08-18 04:11:47'),
(23, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '671d8f2a6f816429c6ec303ead3e2940e2a75abcbe6dc8cad6f1118ccf43808b', '0bcd91979f25c7a23c8dce0007537dad24f562a1dda5398bd0c38ca1fd11d6e0', '2026-08-18 04:11:54'),
(24, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '0bcd91979f25c7a23c8dce0007537dad24f562a1dda5398bd0c38ca1fd11d6e0', 'b9c6bb9253bced6b2424af3917f05871be97ef61db17c823df495e02d965c88e', '2026-08-18 04:14:24'),
(25, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, 'b9c6bb9253bced6b2424af3917f05871be97ef61db17c823df495e02d965c88e', '2200e5fd34553e0437c242577f5470c4b1d2a638ab137407bf4140c6c38af77e', '2026-08-18 04:21:02'),
(26, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '2200e5fd34553e0437c242577f5470c4b1d2a638ab137407bf4140c6c38af77e', '19e71cbb47ca9beb26b249b2bc750d3003a185e13ac58305267416da102808a6', '2026-08-18 04:27:23'),
(27, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '19e71cbb47ca9beb26b249b2bc750d3003a185e13ac58305267416da102808a6', '8fbd55621f7b2cc06bb4e9839b8d60d2a5c1328ccacc4cee6f77ec643bca4930', '2026-08-18 04:28:05'),
(28, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '8fbd55621f7b2cc06bb4e9839b8d60d2a5c1328ccacc4cee6f77ec643bca4930', '244c3f22487f517fd77d444746b2ae83d5e4e95e84c365a5f850e6b5b91b0361', '2026-08-18 04:28:15'),
(29, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '244c3f22487f517fd77d444746b2ae83d5e4e95e84c365a5f850e6b5b91b0361', '338a3021a386699bf8dcc0639218486e6edbda3da376e883b252564875763a7c', '2026-08-18 04:34:09'),
(30, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '338a3021a386699bf8dcc0639218486e6edbda3da376e883b252564875763a7c', '9e9ef1c5f3fd840c195e6873864210259cc0b38b086891876a735ec2458656c4', '2026-08-18 04:37:25'),
(31, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '9e9ef1c5f3fd840c195e6873864210259cc0b38b086891876a735ec2458656c4', 'cb2496d68cf3feb4e6f86b18f25912aef085fb67cc2878bf13d1eee5a1326b32', '2026-08-18 04:40:36'),
(32, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, 'cb2496d68cf3feb4e6f86b18f25912aef085fb67cc2878bf13d1eee5a1326b32', 'ce81ada02585bfae5797cacc88ec6f9bfe851f5fc291f1774fa8d3fe616dd8c2', '2026-08-18 04:45:51'),
(33, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, 'ce81ada02585bfae5797cacc88ec6f9bfe851f5fc291f1774fa8d3fe616dd8c2', 'd9666b40380e2abbb446f7a58a04fe76a647e62eadcfe8128a58ce6c5bad8e8c', '2026-08-18 04:46:31'),
(34, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, 'd9666b40380e2abbb446f7a58a04fe76a647e62eadcfe8128a58ce6c5bad8e8c', 'c2688f3cf7d361381eb981769832872dfd0a90aac7bf8234744500adbb537aa8', '2026-08-18 04:46:39'),
(35, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, 'c2688f3cf7d361381eb981769832872dfd0a90aac7bf8234744500adbb537aa8', 'ca0c42904a4ab528981f5818eeef2d4014724d87b8d52d41e56da56f4ccb0e78', '2026-08-18 04:56:53'),
(36, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, 'ca0c42904a4ab528981f5818eeef2d4014724d87b8d52d41e56da56f4ccb0e78', 'd046682e606115b962c7599e11136776bd4f10520ba0a7711c9014d963578736', '2026-08-18 04:57:09'),
(37, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, 'd046682e606115b962c7599e11136776bd4f10520ba0a7711c9014d963578736', '4006b5484cfc40c528ac4a37512a0b1fcd592bf76f52bb452ba93ee3f95756cd', '2026-08-18 04:57:21'),
(38, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '4006b5484cfc40c528ac4a37512a0b1fcd592bf76f52bb452ba93ee3f95756cd', '90b95c6832ddbba70a81146da6624dedae6bc006089b59ddfb713cd28d2a4d7b', '2026-08-18 04:57:39'),
(39, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '90b95c6832ddbba70a81146da6624dedae6bc006089b59ddfb713cd28d2a4d7b', 'b7f2734aa4821cd7602a52e82f9e32561fe6d9d3060073a7d22e7624b9f2de8f', '2026-08-18 05:02:14'),
(40, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, 'b7f2734aa4821cd7602a52e82f9e32561fe6d9d3060073a7d22e7624b9f2de8f', 'b464668a163a8ff96654ce969685ca35d6908affdf7d8958ff320b66d4d0c2bc', '2026-08-18 05:04:47'),
(41, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, 'b464668a163a8ff96654ce969685ca35d6908affdf7d8958ff320b66d4d0c2bc', 'ff9a9d7dc76e96c7d56fc1cebd3c51d7000da52316e2d37240844eac0db22a57', '2026-08-18 05:05:00'),
(42, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, 'ff9a9d7dc76e96c7d56fc1cebd3c51d7000da52316e2d37240844eac0db22a57', '048f0c2678c7cdf52c1a7bb49e5f27182fffb178b25644fe1b555a85f4906559', '2026-08-18 05:05:28'),
(43, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '048f0c2678c7cdf52c1a7bb49e5f27182fffb178b25644fe1b555a85f4906559', '12a1abbf908ba7968fd86d41192aedc16e7564729c5badbda0f245d3f51804f4', '2026-08-18 05:13:23'),
(44, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '12a1abbf908ba7968fd86d41192aedc16e7564729c5badbda0f245d3f51804f4', 'a26ac6955a5a7142b89f0f6f376eea8eebe468871202da530f3c2905f4ccd10f', '2026-08-18 05:13:28'),
(45, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, 'a26ac6955a5a7142b89f0f6f376eea8eebe468871202da530f3c2905f4ccd10f', '7f5975a914d6282288d1f8476076f99aa0d676bd5eddd842b8229371ed71f695', '2026-08-18 05:13:41'),
(46, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '7f5975a914d6282288d1f8476076f99aa0d676bd5eddd842b8229371ed71f695', '01a0345a456aaec311552e33abb9c9b4223f701496523d36e7fdbd97c34b4f02', '2026-08-18 05:19:59'),
(47, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '01a0345a456aaec311552e33abb9c9b4223f701496523d36e7fdbd97c34b4f02', 'f945e87623dcce90cf51d76e2fdbe9b853dd1f37d7e594c7f5d921510daa09e5', '2026-08-18 05:20:28'),
(48, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Created hero slide', 'WOOOWWWWW', 'hero_slide', 5, NULL, NULL, NULL, NULL, 'f945e87623dcce90cf51d76e2fdbe9b853dd1f37d7e594c7f5d921510daa09e5', '1173592cc0d3e0815ab5b0e394fde17a8a7d4f5625251b450f65b31d15feaf15', '2026-08-18 05:21:49'),
(49, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOOOWWWWW', 'hero_slide', 5, NULL, NULL, NULL, NULL, '1173592cc0d3e0815ab5b0e394fde17a8a7d4f5625251b450f65b31d15feaf15', '05962174b3f396185c6637fc46f5a6f976c1232c0473cd2a5d2e9b681fab45f6', '2026-08-18 05:22:09'),
(50, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOOOWWWWW', 'hero_slide', 5, NULL, NULL, NULL, NULL, '05962174b3f396185c6637fc46f5a6f976c1232c0473cd2a5d2e9b681fab45f6', '46472e126dab312fe372d89f861d2861112329f87308da78a312e612b74cc102', '2026-08-18 05:23:35'),
(51, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Removed hero slide', 'Hero slide #5', 'hero_slide', 5, NULL, NULL, NULL, NULL, '46472e126dab312fe372d89f861d2861112329f87308da78a312e612b74cc102', 'ee6fde4be84c17d11cda86fe98683d7700fa9f79d40e280db8ca1029a13aa44a', '2026-08-18 05:24:18'),
(52, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, 'ee6fde4be84c17d11cda86fe98683d7700fa9f79d40e280db8ca1029a13aa44a', '9bf47bb72eef7ff7f2612853adaa282cbb5fc82ff89de5aa8529e08b23a86cc4', '2026-08-18 05:24:40'),
(53, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '9bf47bb72eef7ff7f2612853adaa282cbb5fc82ff89de5aa8529e08b23a86cc4', '820d07ce804e7139cd1b2529c9b0b5c7cd772d5c17d4abefe8e5606393464dfc', '2026-08-18 05:25:20'),
(54, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'USC records, easier to access.', 'hero_slide', 2, NULL, NULL, NULL, NULL, '820d07ce804e7139cd1b2529c9b0b5c7cd772d5c17d4abefe8e5606393464dfc', 'd1df23cf3bc56f3cbb7143dc843c5ad46f522c8eff2b178bfb99837737073964', '2026-08-18 05:25:31'),
(55, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'Your voice deserves a clear channel.', 'hero_slide', 1, NULL, NULL, NULL, NULL, 'd1df23cf3bc56f3cbb7143dc843c5ad46f522c8eff2b178bfb99837737073964', '7878cd6213c1b85816bf92fe241dc3689d4b5ef539fbfd12eb5931e26e4e15f9', '2026-08-18 05:25:48'),
(56, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'Your voice deserves a clear channel.', 'hero_slide', 1, NULL, NULL, NULL, NULL, '7878cd6213c1b85816bf92fe241dc3689d4b5ef539fbfd12eb5931e26e4e15f9', '443f36cb212d2f25caac4cf59fb61b30d8df39b916b214bc747c2e607bfb7e85', '2026-08-18 05:25:50'),
(57, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '443f36cb212d2f25caac4cf59fb61b30d8df39b916b214bc747c2e607bfb7e85', '174d8cb8a73eba0789fd74764808d6392d867f2a934c3e1a8dec34f121d692a7', '2026-08-18 05:26:38'),
(58, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '174d8cb8a73eba0789fd74764808d6392d867f2a934c3e1a8dec34f121d692a7', '394f794c06aa20e4f0f00ec8eaa55f81f43d92f07a958f4ea393668dd3bb1229', '2026-08-18 05:33:54'),
(59, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '394f794c06aa20e4f0f00ec8eaa55f81f43d92f07a958f4ea393668dd3bb1229', '2b152e4fa4ac85f2bca44ef9f1d745d8ab34f6370b0ed2ac7e08fbbdfbd02ea6', '2026-08-18 05:34:05'),
(60, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'Your voice deserves a clear channel.', 'hero_slide', 1, NULL, NULL, NULL, NULL, '2b152e4fa4ac85f2bca44ef9f1d745d8ab34f6370b0ed2ac7e08fbbdfbd02ea6', 'fbb6a0fd2b2aaa51a860f43bd2a43832d110bfea31ea487efbc9251cc87c1b8d', '2026-08-18 05:37:47'),
(61, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, 'fbb6a0fd2b2aaa51a860f43bd2a43832d110bfea31ea487efbc9251cc87c1b8d', '2b2e352c15415ddd261060ad8474f08fdbcd5da577052a997a19a90fef1de885', '2026-08-18 05:38:50'),
(62, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Reordered hero slides', 'Updated homepage hero slide order.', 'hero_slide', NULL, NULL, NULL, NULL, NULL, '2b2e352c15415ddd261060ad8474f08fdbcd5da577052a997a19a90fef1de885', '9d2f6c19fb8e75903e62071a6983119bb595f353bbf3fe056aea8acc1403546a', '2026-08-18 05:44:51'),
(63, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Reordered hero slides', 'Updated homepage hero slide order.', 'hero_slide', NULL, NULL, NULL, NULL, NULL, '9d2f6c19fb8e75903e62071a6983119bb595f353bbf3fe056aea8acc1403546a', 'f66bc223e275fa1e2ea1ca264f4348c96a89c56bf4a4068c5d8b2d54bb075cb7', '2026-08-18 05:44:54'),
(64, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Reordered hero slides', 'Updated homepage hero slide order.', 'hero_slide', NULL, NULL, NULL, NULL, NULL, 'f66bc223e275fa1e2ea1ca264f4348c96a89c56bf4a4068c5d8b2d54bb075cb7', '76bffca1698b38feae2786afcbec25e3027587c87d91e3430cc6a6eadd679e30', '2026-08-18 05:44:57'),
(65, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Reordered hero slides', 'Updated homepage hero slide order.', 'hero_slide', NULL, NULL, NULL, NULL, NULL, '76bffca1698b38feae2786afcbec25e3027587c87d91e3430cc6a6eadd679e30', '352e90a777ad35e35ad22177a9a91f8a074d25c8f4a3d69af7819e4b0f83fa2c', '2026-08-18 05:45:01'),
(66, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '352e90a777ad35e35ad22177a9a91f8a074d25c8f4a3d69af7819e4b0f83fa2c', 'cb19103e4e42e6ab5837a4ce7b10292c295c2aa63f7041b6bd4d569f5fb386ec', '2026-08-18 05:45:18'),
(67, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, 'cb19103e4e42e6ab5837a4ce7b10292c295c2aa63f7041b6bd4d569f5fb386ec', '4c13647c74bbc5fa33c2574f620a36309d7a82b5419d7e7fa6ea989946041fe3', '2026-08-18 05:45:29'),
(68, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '4c13647c74bbc5fa33c2574f620a36309d7a82b5419d7e7fa6ea989946041fe3', '6e1ea60474100e207003208e2917fc6507b822935ca4bf83762e198f615045ec', '2026-08-18 05:45:37'),
(69, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '6e1ea60474100e207003208e2917fc6507b822935ca4bf83762e198f615045ec', '1e02bf3240051d88cd8515e167b6c9f5a942b1ba381cc2dfc78c291a38978657', '2026-08-18 05:46:19'),
(70, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '1e02bf3240051d88cd8515e167b6c9f5a942b1ba381cc2dfc78c291a38978657', 'a8aee72f7b5eba23208fa79ce9c44670091d57120ba18b2c12ee4baa3bbd1ab0', '2026-08-18 05:46:37'),
(71, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, 'a8aee72f7b5eba23208fa79ce9c44670091d57120ba18b2c12ee4baa3bbd1ab0', '41ba0805162bd308f00b6ee24b8aa556eb4bac9d51b4d63b439face62e4de300', '2026-08-18 05:55:05'),
(72, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '41ba0805162bd308f00b6ee24b8aa556eb4bac9d51b4d63b439face62e4de300', '7faefdd6ba723aca3cb9d3ef52917e89f43b81e196aa3d31fbcb77269940c004', '2026-08-18 05:55:58'),
(73, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '7faefdd6ba723aca3cb9d3ef52917e89f43b81e196aa3d31fbcb77269940c004', '3678eebabac5315501854d4798bd5f305731fb72f8d10dbac50b9af66e15eec3', '2026-08-18 06:01:18'),
(74, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '3678eebabac5315501854d4798bd5f305731fb72f8d10dbac50b9af66e15eec3', 'eb1a2d895bd049fb06c0a052c4e3229d3394f2c9509bf612211fa4f754146353', '2026-08-18 06:13:42'),
(75, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, 'eb1a2d895bd049fb06c0a052c4e3229d3394f2c9509bf612211fa4f754146353', '23112e57607812cc8f8e06e133e6e70642cb0d8ae36c68fa59eb3b338abd36a4', '2026-08-18 06:13:50'),
(76, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '23112e57607812cc8f8e06e133e6e70642cb0d8ae36c68fa59eb3b338abd36a4', '00726f50a195eea39ba1550df0e026548cc7378826e2f2a720838401232b92b1', '2026-08-18 06:17:13'),
(77, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '00726f50a195eea39ba1550df0e026548cc7378826e2f2a720838401232b92b1', '7b2c378e1e40308b512dabb245a700abe1c6bf8d6549f6f0bd254f959eca757e', '2026-08-18 06:17:32'),
(78, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '7b2c378e1e40308b512dabb245a700abe1c6bf8d6549f6f0bd254f959eca757e', '0e727281784e5cb03bf5d8381637f9c2b08a6086d5165f34e5cd23d33a07bc25', '2026-08-18 06:18:00'),
(79, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '0e727281784e5cb03bf5d8381637f9c2b08a6086d5165f34e5cd23d33a07bc25', '870ae97018ee20fd764a849c7f7ca2da0efa15a9b3d0c8efddd254d42813ee45', '2026-08-18 06:18:11'),
(80, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '870ae97018ee20fd764a849c7f7ca2da0efa15a9b3d0c8efddd254d42813ee45', 'b552fed6fa0d7bcb73bd950e039c8f61727ce8bb47a22797b29b5813088351c9', '2026-08-18 06:18:50'),
(81, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, 'b552fed6fa0d7bcb73bd950e039c8f61727ce8bb47a22797b29b5813088351c9', 'dc8e61d7c127948ce6e82f3291362a840522f170da72122eb4cd32f6e3d2d62b', '2026-08-18 06:18:56'),
(82, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, 'dc8e61d7c127948ce6e82f3291362a840522f170da72122eb4cd32f6e3d2d62b', 'aeb74ee7f5be1f6a410b2e578f19bafe053db55184a83045ab109d5cdaa43698', '2026-08-18 06:21:58'),
(83, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, 'aeb74ee7f5be1f6a410b2e578f19bafe053db55184a83045ab109d5cdaa43698', 'ce4901145493d54a042f729440be81408d3cf36cea65eb822cedfc1db72e3430', '2026-08-18 06:22:30'),
(84, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Reordered hero slides', 'Updated homepage hero slide order.', 'hero_slide', NULL, NULL, NULL, NULL, NULL, 'ce4901145493d54a042f729440be81408d3cf36cea65eb822cedfc1db72e3430', '33246ed4b7c562080c5c5844b4dfb1a3e55d534f010c94131316136aa310a8ed', '2026-08-18 06:22:57'),
(85, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Reordered hero slides', 'Updated homepage hero slide order.', 'hero_slide', NULL, NULL, NULL, NULL, NULL, '33246ed4b7c562080c5c5844b4dfb1a3e55d534f010c94131316136aa310a8ed', 'dcaf145a0bc670299f7f80075671faaf31f77ba82bfb3185a97b47bb5055a94e', '2026-08-18 06:22:59'),
(86, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Submitted', 'concern', 1, NULL, NULL, NULL, NULL, 'dcaf145a0bc670299f7f80075671faaf31f77ba82bfb3185a97b47bb5055a94e', 'e485b1ffe3680126f8474e1f46f3995181c43c2022e69b9bfca46b1870c60ce6', '2026-08-18 06:39:39'),
(87, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Submitted', 'concern', 1, NULL, NULL, NULL, NULL, 'e485b1ffe3680126f8474e1f46f3995181c43c2022e69b9bfca46b1870c60ce6', 'c8f41d3453335568460c33401a685d9b13747ca19a38d91937b96b181c36ea59', '2026-08-18 07:04:25'),
(88, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Submitted', 'concern', 1, NULL, NULL, NULL, NULL, 'c8f41d3453335568460c33401a685d9b13747ca19a38d91937b96b181c36ea59', 'e1f1a0a030c8049dfa82f9f8c63408dddf0a017eb2e73c5e55c51abf0a4210cf', '2026-08-18 07:04:30'),
(89, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Submitted', 'concern', 1, NULL, NULL, NULL, NULL, 'e1f1a0a030c8049dfa82f9f8c63408dddf0a017eb2e73c5e55c51abf0a4210cf', '434eac6884d55583c6d1336d609eae3c585f9a11e36b3baa8811146d739cc8d3', '2026-08-18 07:08:36'),
(90, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Submitted', 'concern', 1, NULL, NULL, NULL, NULL, '434eac6884d55583c6d1336d609eae3c585f9a11e36b3baa8811146d739cc8d3', 'a6c979b4e5ee2ab72f94a01e7457d75a3f966f4fe30a9fbb40c2ac77e9f9057b', '2026-08-18 07:08:41'),
(91, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Submitted', 'concern', 1, NULL, NULL, NULL, NULL, 'a6c979b4e5ee2ab72f94a01e7457d75a3f966f4fe30a9fbb40c2ac77e9f9057b', '133a2736ac17df60dd72aa94afceea8874ef0b1b70b549123a1835c597cf6905', '2026-08-18 07:08:46'),
(92, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Submitted', 'concern', 1, NULL, NULL, NULL, NULL, '133a2736ac17df60dd72aa94afceea8874ef0b1b70b549123a1835c597cf6905', 'c35564f262a500c448d138a55e1642d990e739ed84460a6fda63231088a62a53', '2026-08-18 07:09:03'),
(93, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Received', 'concern', 1, NULL, NULL, NULL, NULL, 'c35564f262a500c448d138a55e1642d990e739ed84460a6fda63231088a62a53', '30b89d80fd8303d87f7877682b1ad2019573f168116b8d960dd742ffe37bdc21', '2026-08-18 07:09:14'),
(94, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Received', 'concern', 1, NULL, NULL, NULL, NULL, '30b89d80fd8303d87f7877682b1ad2019573f168116b8d960dd742ffe37bdc21', 'bcc85e6ee2b8b40d88bdb1fa70e10603453efc32ab9c281065af66d0f535a742', '2026-08-18 07:09:25'),
(95, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Received', 'concern', 1, NULL, NULL, NULL, NULL, 'bcc85e6ee2b8b40d88bdb1fa70e10603453efc32ab9c281065af66d0f535a742', 'cde41ee1e6da682701184e2ad4eca84cbe85ba1d967b91d809c1a12f97b8a118', '2026-08-18 07:09:34'),
(96, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Submitted', 'concern', 1, NULL, NULL, NULL, NULL, 'cde41ee1e6da682701184e2ad4eca84cbe85ba1d967b91d809c1a12f97b8a118', '9b053e9ef862eb7154c6adc101ecb844d91dd5c643cc7241e32f05a68e5f207e', '2026-08-18 07:09:42'),
(97, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Submitted', 'concern', 1, NULL, NULL, NULL, NULL, '9b053e9ef862eb7154c6adc101ecb844d91dd5c643cc7241e32f05a68e5f207e', '852d003e635365bf2fc9d73088e68eb50d3e786d53ac2ab4cf852e59688fc05e', '2026-08-18 07:09:45'),
(98, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Submitted', 'concern', 1, NULL, NULL, NULL, NULL, '852d003e635365bf2fc9d73088e68eb50d3e786d53ac2ab4cf852e59688fc05e', '78b99acbc793429759234d16686728338c478687ba6b883086f7e1d1a5f2170e', '2026-08-18 07:09:49'),
(99, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Submitted', 'concern', 1, NULL, NULL, NULL, NULL, '78b99acbc793429759234d16686728338c478687ba6b883086f7e1d1a5f2170e', 'e36038ece26d2940f37c67b6eff6c91d35298125b228847d274b96e67eb29a11', '2026-08-18 07:09:53'),
(100, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Submitted', 'concern', 1, NULL, NULL, NULL, NULL, 'e36038ece26d2940f37c67b6eff6c91d35298125b228847d274b96e67eb29a11', '53067d0169b3910b0b834059c5f964eb14547b6111a67c05305117090e3179a9', '2026-08-18 07:09:58'),
(101, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Submitted', 'concern', 1, NULL, NULL, NULL, NULL, '53067d0169b3910b0b834059c5f964eb14547b6111a67c05305117090e3179a9', '9c3cc0026576301248dbe6ee771ed86c50e3275a51ce6fe569e8fd309a914fd4', '2026-08-18 07:10:00'),
(102, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, NULL, NULL, '9c3cc0026576301248dbe6ee771ed86c50e3275a51ce6fe569e8fd309a914fd4', 'cabca2da18afb5b1117fc774cfb365afb35a7715ed8f93b3a8993a175cbbd976', '2026-08-18 08:12:54'),
(103, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Git Basic Operation Guide (1).pdf', 'media', 1, NULL, NULL, NULL, NULL, 'cabca2da18afb5b1117fc774cfb365afb35a7715ed8f93b3a8993a175cbbd976', '3f7bd2cd1bd9b5604ad0838f33e436d40674debe3414f8d61b9e201d00fef74f', '2026-08-18 10:15:58'),
(104, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Git Basic Operation Guide (1).pdf', 'media', 1, NULL, NULL, NULL, NULL, '3f7bd2cd1bd9b5604ad0838f33e436d40674debe3414f8d61b9e201d00fef74f', '0cd7c8721a9a544e7e9b88d6e12d426b9eba49b0d54572ff63fa91cc7c0ca3a7', '2026-08-18 10:16:09'),
(105, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Requested USC promotion', 'Open University System · Your campus voice deserves a clear channel.', 'hero_promotion', 1, NULL, NULL, NULL, NULL, '0cd7c8721a9a544e7e9b88d6e12d426b9eba49b0d54572ff63fa91cc7c0ca3a7', '25d52d1d5e9b13bd6424aadd2fe7077b95720f80abcc6b43b32a76fa80d84a58', '2026-08-18 11:03:36'),
(106, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Withdrew USC promotion', 'Open University System · Request #1', 'hero_promotion', 1, NULL, NULL, NULL, NULL, '25d52d1d5e9b13bd6424aadd2fe7077b95720f80abcc6b43b32a76fa80d84a58', '7bd3ef65474f744e6b418d4fc980a61413c4eb8431a51abdaab9146abed9b8f2', '2026-08-18 11:04:58'),
(107, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Requested USC promotion', 'Open University System · Your campus voice deserves a clear channel.', 'hero_promotion', 2, NULL, NULL, NULL, NULL, '7bd3ef65474f744e6b418d4fc980a61413c4eb8431a51abdaab9146abed9b8f2', 'e07c3433d043d348eade86ef206578d22c01702a614f823e001ed2afa7f8a2f7', '2026-08-18 11:11:48'),
(108, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Rejected promoted slide', 'Open University System · Your campus voice deserves a clear channel.', 'hero_promotion', 2, NULL, NULL, NULL, NULL, 'e07c3433d043d348eade86ef206578d22c01702a614f823e001ed2afa7f8a2f7', '1eb608e6b866ed009b6b2e517e0ce22d0cb99bf8056f32c384180abb8feeed6e', '2026-08-18 11:12:06'),
(109, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'homepage', 'Requested USC promotion', 'South La Union Campus · Your campus voice deserves a clear channel.', 'hero_promotion', 3, NULL, NULL, NULL, NULL, '1eb608e6b866ed009b6b2e517e0ce22d0cb99bf8056f32c384180abb8feeed6e', '24badfbb18f2dad1f3d96b274d9361bba9291b349cc3c70400d71f735d743a75', '2026-08-18 11:46:24'),
(110, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'homepage', 'Withdrew USC promotion', 'South La Union Campus · Request #3', 'hero_promotion', 3, NULL, NULL, NULL, NULL, '24badfbb18f2dad1f3d96b274d9361bba9291b349cc3c70400d71f735d743a75', '05e496bd94d1e82ee4b860bca5266197e66f8dcff3992ad2138180aa5ec20056', '2026-08-18 11:46:52'),
(111, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Updated account', 'Mid La Union Campus', 'admin', 4, NULL, NULL, NULL, NULL, '05e496bd94d1e82ee4b860bca5266197e66f8dcff3992ad2138180aa5ec20056', 'b97590de64beccb98f17339a9aa6c4f8959af23560d397f00953d9cea13b1a18', '2026-08-18 11:50:03'),
(112, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Updated account', 'North La Union Campus', 'admin', 10, NULL, NULL, NULL, NULL, 'b97590de64beccb98f17339a9aa6c4f8959af23560d397f00953d9cea13b1a18', 'f7850bc409f5b5b0ef04639ceb62c0e1f65c877e91a7d06113b550ee174a287b', '2026-08-18 11:50:40'),
(113, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Updated account', 'USC Administrator', 'admin', 1, NULL, NULL, NULL, NULL, 'f7850bc409f5b5b0ef04639ceb62c0e1f65c877e91a7d06113b550ee174a287b', '8203e817a3a2868d51421f557d777d643ab2ea093f2ce2fb62ff91a2269b810c', '2026-08-18 12:01:27'),
(114, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Updated own profile', 'USC Administrator', 'admin', 1, NULL, NULL, NULL, NULL, '8203e817a3a2868d51421f557d777d643ab2ea093f2ce2fb62ff91a2269b810c', 'e3720015254abd7798badac6bea5131ed9e18d7dae34b2b3cc0679bc8114039b', '2026-08-18 12:06:29'),
(115, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'accounts', 'Updated own profile', 'SLUC CSBO', 'admin', 6, NULL, NULL, NULL, NULL, 'e3720015254abd7798badac6bea5131ed9e18d7dae34b2b3cc0679bc8114039b', 'df12636825f9d03997b7873e017473100719f71df654132f92c63acfc1c980bd', '2026-08-18 12:07:21'),
(116, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'accounts', 'Updated own profile', 'SLUC CSBO', 'admin', 6, NULL, NULL, NULL, NULL, 'df12636825f9d03997b7873e017473100719f71df654132f92c63acfc1c980bd', 'd414fc5013b6654192a578a1604e20718663a940c8ae1da7554aa77752ee79ba', '2026-08-18 12:20:59'),
(117, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Updated account', 'USC Administrator', 'admin', 1, NULL, NULL, NULL, NULL, 'd414fc5013b6654192a578a1604e20718663a940c8ae1da7554aa77752ee79ba', 'da034273adc326f455b54efba859415ba095919b583c5ec479fc284a26254a5a', '2026-08-18 12:21:23'),
(118, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Updated own profile', 'USC Administrator', 'admin', 1, NULL, NULL, NULL, NULL, 'da034273adc326f455b54efba859415ba095919b583c5ec479fc284a26254a5a', '32bfae95012365a7e10b4daf660787ca9e2c7267e48106ae51dc2401a86eca1d', '2026-08-18 12:21:55'),
(119, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'accounts', 'Updated own profile', 'SLUC CSBO', 'admin', 6, NULL, NULL, NULL, NULL, '32bfae95012365a7e10b4daf660787ca9e2c7267e48106ae51dc2401a86eca1d', '066c5e515dec5a064cc73c5cc5f8eb87f7224240a6664a46416a934ecec8274a', '2026-08-18 12:28:34'),
(120, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Updated account', 'USC Administrator', 'admin', 1, NULL, NULL, NULL, NULL, '066c5e515dec5a064cc73c5cc5f8eb87f7224240a6664a46416a934ecec8274a', '686c7a24fa77fec9df8abf3565921c07dbabafa4181237ffeedd3250dace970d', '2026-08-18 12:29:07'),
(121, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Updated own profile', 'USC Administrator', 'admin', 1, NULL, NULL, NULL, NULL, '686c7a24fa77fec9df8abf3565921c07dbabafa4181237ffeedd3250dace970d', '582f598e773f6db8169a94cd0ef72b37980dd3c261930db89abced4b23744d77', '2026-08-18 12:29:46'),
(122, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'accounts', 'Updated own profile', 'SLUC CSBO', 'admin', 6, NULL, NULL, NULL, NULL, '582f598e773f6db8169a94cd0ef72b37980dd3c261930db89abced4b23744d77', 'a0b2af9fa45f01da0497733369e8bcce08468d776ba2e3d3e144424472e91870', '2026-08-18 12:31:58'),
(123, 1, 'USC Administrator', 'admin', 'USC', 'system', 'Changed portal context', 'ALL → USC', NULL, NULL, '{\"portal\":\"ALL\"}', '{\"portal\":\"USC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'a0b2af9fa45f01da0497733369e8bcce08468d776ba2e3d3e144424472e91870', '480ce176f636455c55a4b0212fb11b111668bd2933293168e061333ca6176772', '2026-08-18 13:17:19'),
(124, 1, 'USC Administrator', 'admin', 'NLUC', 'system', 'Changed portal context', 'USC → NLUC', NULL, NULL, '{\"portal\":\"USC\"}', '{\"portal\":\"NLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '480ce176f636455c55a4b0212fb11b111668bd2933293168e061333ca6176772', '0e529db302468f32fc4785ab4c478df6e3e01e2eab8194cc42a4493e633eb480', '2026-08-18 13:17:25'),
(125, 1, 'USC Administrator', 'admin', 'USC', 'system', 'Changed portal context', 'NLUC → USC', NULL, NULL, '{\"portal\":\"NLUC\"}', '{\"portal\":\"USC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '0e529db302468f32fc4785ab4c478df6e3e01e2eab8194cc42a4493e633eb480', 'f41f32732ce1ec98c5d66f06bce0641bf7d8641d31fbafb10b1e77e114e8281e', '2026-08-18 13:20:09'),
(126, 1, 'USC Administrator', 'admin', NULL, 'system', 'Changed portal context', 'USC → ALL', NULL, NULL, '{\"portal\":\"USC\"}', '{\"portal\":\"ALL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'f41f32732ce1ec98c5d66f06bce0641bf7d8641d31fbafb10b1e77e114e8281e', 'e0b1ae2044faeae42760308adbc3b8314baf8d6ded4faad6e1aa6537614a9d23', '2026-08-18 13:20:15'),
(127, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Updated own profile', 'USC Administrator', 'admin', 1, '{\"full_name\":\"USC Administrator\",\"username\":\"admin\",\"email\":null}', '{\"full_name\":\"USC Administrator\",\"username\":\"admin\",\"email\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'e0b1ae2044faeae42760308adbc3b8314baf8d6ded4faad6e1aa6537614a9d23', '6ede61cd7c1b900da60c7d1d4bc5297a88c34bd744d889fd859176c5e5b2446c', '2026-08-18 13:42:01'),
(128, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Updated account', 'USC Administrator', 'admin', 1, '{\"full_name\":\"USC Administrator\",\"username\":\"admin\",\"email\":null,\"role\":\"admin\",\"campus\":null,\"status\":\"active\"}', '{\"full_name\":\"USC Administrator\",\"username\":\"admin\",\"email\":null,\"role\":\"admin\",\"campus\":null,\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '6ede61cd7c1b900da60c7d1d4bc5297a88c34bd744d889fd859176c5e5b2446c', '04abd77ae8847839852501cc156bee1062da5f942aa8a5c501cda69c64f9d8ea', '2026-08-18 15:11:50'),
(129, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Updated account', 'USC Administrator', 'admin', 1, '{\"full_name\":\"USC Administrator\",\"username\":\"admin\",\"email\":null,\"role\":\"admin\",\"campus\":null,\"status\":\"active\"}', '{\"full_name\":\"USC Administrator\",\"username\":\"admin\",\"email\":null,\"role\":\"admin\",\"campus\":null,\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '04abd77ae8847839852501cc156bee1062da5f942aa8a5c501cda69c64f9d8ea', '36e86836d83b95d1270deef871bf6f46ef4a726f21ad4c12019ddb6786ac3413', '2026-08-18 15:12:08'),
(130, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Updated account', 'University Student Council', 'admin', 2, '{\"full_name\":\"University Student Council\",\"username\":\"usc\",\"email\":null,\"role\":\"usc\",\"campus\":null,\"status\":\"active\"}', '{\"full_name\":\"University Student Council\",\"username\":\"usc\",\"email\":null,\"role\":\"usc\",\"campus\":null,\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '36e86836d83b95d1270deef871bf6f46ef4a726f21ad4c12019ddb6786ac3413', 'db0befffcd8f8d3af4f1dd7c9cd5d281231b8326257a2d22b1fb834ffe9b3ddb', '2026-08-18 15:12:24'),
(131, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Updated account', 'SLUC CSBO', 'admin', 6, '{\"full_name\":\"SLUC CSBO\",\"username\":\"sluccsbo\",\"email\":null,\"role\":\"campus_sbo\",\"campus\":\"SLUC\",\"status\":\"active\"}', '{\"full_name\":\"SLUC CSBO\",\"username\":\"sluccsbo\",\"email\":null,\"role\":\"campus_sbo\",\"campus\":\"SLUC\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'db0befffcd8f8d3af4f1dd7c9cd5d281231b8326257a2d22b1fb834ffe9b3ddb', 'ee72279192323e2edb3c3536d5bd2375e660c11ca244d19cba6d04ab88bf0dc2', '2026-08-18 15:12:48'),
(132, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Created account', 'NLUC CSBO', 'admin', 11, NULL, '{\"full_name\":\"NLUC CSBO\",\"username\":\"nluccsbo\",\"email\":null,\"role\":\"campus_sbo\",\"campus\":\"NLUC\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'ee72279192323e2edb3c3536d5bd2375e660c11ca244d19cba6d04ab88bf0dc2', '6cdc1cd331081b16d9fc07e3bcbf2c2a1a8d2d97be71f8f716d6396df6bce697', '2026-08-18 15:14:11'),
(133, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Created account', 'OUS CSBO', 'admin', 12, NULL, '{\"full_name\":\"OUS CSBO\",\"username\":\"ouscsbo\",\"email\":null,\"role\":\"campus_sbo\",\"campus\":\"OUS\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '6cdc1cd331081b16d9fc07e3bcbf2c2a1a8d2d97be71f8f716d6396df6bce697', '4224d6ccf6dcc10e1498ca962b559d272637e9f4983bcc51bd9de5ec5bbe3f5e', '2026-08-18 15:14:56'),
(134, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Created account', 'MLUC CSBO', 'admin', 13, NULL, '{\"full_name\":\"MLUC CSBO\",\"username\":\"mluccsbo\",\"email\":null,\"role\":\"campus_sbo\",\"campus\":\"MLUC\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '4224d6ccf6dcc10e1498ca962b559d272637e9f4983bcc51bd9de5ec5bbe3f5e', '32b9ec8a0dd3221f4ec8ff08bb609ef05689735358160361463154c4e5bf2419', '2026-08-18 15:15:39'),
(135, 1, 'USC Administrator', 'admin', NULL, 'accounts', 'Updated account', 'MLUC CSBO', 'admin', 13, '{\"full_name\":\"MLUC CSBO\",\"username\":\"mluccsbo\",\"email\":null,\"role\":\"campus_sbo\",\"campus\":\"MLUC\",\"status\":\"active\"}', '{\"full_name\":\"MLUC CSBO\",\"username\":\"mluccsbo\",\"email\":null,\"role\":\"campus_sbo\",\"campus\":\"MLUC\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '32b9ec8a0dd3221f4ec8ff08bb609ef05689735358160361463154c4e5bf2419', 'eb744ae844ce2235fb559fced2967f7c158f1110f467476b89a41dd053af0067', '2026-08-18 15:16:09'),
(136, 1, 'USC Administrator', 'admin', NULL, 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'eb744ae844ce2235fb559fced2967f7c158f1110f467476b89a41dd053af0067', '7618e63abf0e38a01e83423c68b31e5e10d98dd0ea083402a8c56b5dbd3d16a8', '2026-08-19 04:00:29'),
(137, 1, 'USC Administrator', 'admin', NULL, 'system', 'Ran database migrations', 'Applied 2026081900, 2026081901, 2026081902, 2026081903', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '7618e63abf0e38a01e83423c68b31e5e10d98dd0ea083402a8c56b5dbd3d16a8', 'ee9e63e52f1ee860ac7fbb0b81812b8b39d34692dc6386dcc32244e127d64608', '2026-08-19 04:00:48'),
(138, 1, 'USC Administrator', 'admin', NULL, 'reports', 'Exported report summary', 'Operational report exported for Last 6 months', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'ee9e63e52f1ee860ac7fbb0b81812b8b39d34692dc6386dcc32244e127d64608', '6b0b7721a2723bfa1e6aa2e6f54552fd14d92fb8f996d53fd7e7764ebb5500ae', '2026-08-19 04:03:37'),
(139, 1, 'USC Administrator', 'admin', NULL, 'reports', 'Exported privacy-safe concern data', 'Identity fields masked · 1 records', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '6b0b7721a2723bfa1e6aa2e6f54552fd14d92fb8f996d53fd7e7764ebb5500ae', '66950aecfa1ceb5f1fb8534352c1ecf4d16343484fb2dd1dcf6e260e853e9f8a', '2026-08-19 04:03:46'),
(140, 1, 'USC Administrator', 'admin', NULL, 'privacy', 'Exported identity', 'Sensitive E-Sumbong identity access recorded', 'concern', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '66950aecfa1ceb5f1fb8534352c1ecf4d16343484fb2dd1dcf6e260e853e9f8a', 'b216ecf74b2cf9823ea69f2aeedc0716dd86aa1774ce6aee8c639ae7a5e93d76', '2026-08-19 04:04:07'),
(141, 1, 'USC Administrator', 'admin', NULL, 'privacy', 'Exported concern identities', 'Authorized case export · 1 records', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'b216ecf74b2cf9823ea69f2aeedc0716dd86aa1774ce6aee8c639ae7a5e93d76', '8ba2df007089869457f19233ebf4a307249bcfefe7ee02d901654cc7077a7b0c', '2026-08-19 04:04:07'),
(142, 1, 'USC Administrator', 'admin', NULL, 'settings', 'Updated system settings', 'Updated global portal and security configuration', NULL, NULL, '{\"portal_name\":\"University Student Council\",\"university_name\":\"Don Mariano Marcos Memorial State University\",\"usc_email\":\"usc@dmmmsu.edu.ph\",\"usc_facebook\":\"https://www.facebook.com/usc.dmmmsu\",\"maintenance_mode\":\"0\",\"default_upload_limit\":\"10\",\"login_max_attempts\":\"5\",\"login_lockout_minutes\":\"5\",\"session_idle_hours\":\"12\",\"password_max_age_days\":\"180\",\"dormant_account_days\":\"90\",\"security_require_2fa_for_admin\":\"0\",\"esumbong_sla_urgent_hours\":\"24\",\"esumbong_sla_high_hours\":\"48\",\"esumbong_sla_normal_hours\":\"120\",\"esumbong_sla_low_hours\":\"168\",\"privacy_retention_days\":\"730\",\"backup_reminder_days\":\"7\"}', '{\"portal_name\":\"University Student Council\",\"university_name\":\"Don Mariano Marcos Memorial State University\",\"usc_email\":\"usc@dmmmsu.edu.ph\",\"usc_facebook\":\"https://www.facebook.com/usc.dmmmsu\",\"maintenance_mode\":\"0\",\"default_upload_limit\":\"10\",\"login_max_attempts\":\"5\",\"login_lockout_minutes\":\"5\",\"session_idle_hours\":\"12\",\"password_max_age_days\":\"180\",\"dormant_account_days\":\"90\",\"security_require_2fa_for_admin\":\"0\",\"esumbong_sla_urgent_hours\":\"24\",\"esumbong_sla_high_hours\":\"48\",\"esumbong_sla_normal_hours\":\"120\",\"esumbong_sla_low_hours\":\"168\",\"privacy_retention_days\":\"730\",\"backup_reminder_days\":\"7\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '8ba2df007089869457f19233ebf4a307249bcfefe7ee02d901654cc7077a7b0c', '61a8886b9479267960aaca0a0a36018325c80a23ef803d6d5ba4fcf8cc6469c4', '2026-08-19 04:11:21'),
(143, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'usc.png · SHA-256 68a280056e95', 'media', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '61a8886b9479267960aaca0a0a36018325c80a23ef803d6d5ba4fcf8cc6469c4', '87386136da0110851541c5fa9ff932f72b30a8bb700642b569ef765eff88a9a6', '2026-08-19 05:19:42'),
(144, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'usc.png', 'media', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '87386136da0110851541c5fa9ff932f72b30a8bb700642b569ef765eff88a9a6', '3157a7515786637e897167a23c68d77b0ca347ff896708d73105e525a7b86a66', '2026-08-19 05:20:26');
INSERT INTO `admin_activity_logs` (`id`, `admin_id`, `admin_name`, `role`, `campus`, `module`, `action`, `description`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `prev_hash`, `record_hash`, `created_at`) VALUES
(145, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'University Student Council · WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '3157a7515786637e897167a23c68d77b0ca347ff896708d73105e525a7b86a66', '6a7d43a65f74ee989acb08026dc34bf1b2f467c0753538a8f510833862fc36b7', '2026-08-19 05:21:34'),
(146, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'University Student Council · WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '6a7d43a65f74ee989acb08026dc34bf1b2f467c0753538a8f510833862fc36b7', '0a353db30bcc4c8de666b8ee14ffe84cd292428aaef58958752f3c76c4c726d6', '2026-08-19 05:21:58'),
(147, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'University Student Council · WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '0a353db30bcc4c8de666b8ee14ffe84cd292428aaef58958752f3c76c4c726d6', '6f4ff5100d80a20d0102d1e65f0eaf1be14ef25e47eecd22408ce48680f8d5d7', '2026-08-19 05:22:27'),
(148, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'University Student Council · WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '6f4ff5100d80a20d0102d1e65f0eaf1be14ef25e47eecd22408ce48680f8d5d7', 'a4e94a3f6707d1501cd207e8e0f1ea829c541af6a97dce3152267f57c9faa852', '2026-08-19 05:22:47'),
(149, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'University Student Council · WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'a4e94a3f6707d1501cd207e8e0f1ea829c541af6a97dce3152267f57c9faa852', 'b4ef9f4ffc2880eae51474861f511d501aece2bf94de5344cfdc65fcc081ec74', '2026-08-19 05:24:08'),
(150, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'b4ef9f4ffc2880eae51474861f511d501aece2bf94de5344cfdc65fcc081ec74', '73ebf39150b4e9832ef26874ac4c97c82366ce0cc160fd37bef54497729957d6', '2026-08-19 05:25:21'),
(151, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'auth', 'Signed out', 'Administrator signed out', 'admin', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '73ebf39150b4e9832ef26874ac4c97c82366ce0cc160fd37bef54497729957d6', 'fda839b51b1732e03cfe3c1b6a926484d5e1c6b3ee8e2cdb349d0e7f9ab0b48d', '2026-08-19 05:33:11'),
(152, 1, 'USC Administrator', 'admin', 'MLUC', 'system', 'Changed portal context', 'ALL → MLUC', NULL, NULL, '{\"portal\":\"ALL\"}', '{\"portal\":\"MLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'fda839b51b1732e03cfe3c1b6a926484d5e1c6b3ee8e2cdb349d0e7f9ab0b48d', 'ce2b8667b41aebc6c66f157b5b152f08937f288318339437fd735fa44ae54ad7', '2026-08-19 06:07:46'),
(153, 1, 'USC Administrator', 'admin', 'NLUC', 'system', 'Changed portal context', 'MLUC → NLUC', NULL, NULL, '{\"portal\":\"MLUC\"}', '{\"portal\":\"NLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'ce2b8667b41aebc6c66f157b5b152f08937f288318339437fd735fa44ae54ad7', '06655b2ae833de95bd415d1c568334dbbb5ba7392b8c626f525a555c943069b5', '2026-08-19 06:07:48'),
(154, 1, 'USC Administrator', 'admin', NULL, 'system', 'Changed portal context', 'NLUC → ALL', NULL, NULL, '{\"portal\":\"NLUC\"}', '{\"portal\":\"ALL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '06655b2ae833de95bd415d1c568334dbbb5ba7392b8c626f525a555c943069b5', 'a3142582ddd776af328df4f4a350ccf467ab3c769bbdcd31e121729d51b98239', '2026-08-19 06:07:50'),
(155, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'University Student Council · WOWWWWWWW AMAZING', 'hero_slide', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'a3142582ddd776af328df4f4a350ccf467ab3c769bbdcd31e121729d51b98239', '6898e8b56cf629c1806819979c2a794be3c1ef1925047402e7b6c1cb7616457e', '2026-08-19 06:19:58'),
(156, 1, 'USC Administrator', 'admin', NULL, 'privacy', 'Viewed student identity', 'Sensitive E-Sumbong identity access recorded', 'concern', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '6898e8b56cf629c1806819979c2a794be3c1ef1925047402e7b6c1cb7616457e', 'f19bf1282395b9169eecfd733e3444f5da9dc5b7e8498dc9d27cce680fbdd211', '2026-08-19 06:22:31'),
(157, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-264015 · Notes updated', 'concern', 1, '{\"status\":\"Submitted\",\"priority\":\"Normal\",\"assigned_scope\":\"USC\",\"assigned_to\":2}', '{\"status\":\"Submitted\",\"priority\":\"Normal\",\"assigned_scope\":\"USC\",\"assigned_to\":2,\"due_at\":\"2026-08-20 20:27:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'f19bf1282395b9169eecfd733e3444f5da9dc5b7e8498dc9d27cce680fbdd211', '95af35e31f23c2ba11efa2e277416a6d3375775d441df32228296ed82d164218', '2026-08-19 06:24:58'),
(158, 1, 'USC Administrator', 'admin', NULL, 'privacy', 'Viewed student identity', 'Sensitive E-Sumbong identity access recorded', 'concern', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '95af35e31f23c2ba11efa2e277416a6d3375775d441df32228296ed82d164218', 'fd34b6d44730f601176ef7778de7e22a8f934c22a72a23f8d66ffb891a435a85', '2026-08-19 06:25:10'),
(159, 1, 'USC Administrator', 'admin', NULL, 'privacy', 'Viewed student identity', 'Sensitive E-Sumbong identity access recorded', 'concern', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'fd34b6d44730f601176ef7778de7e22a8f934c22a72a23f8d66ffb891a435a85', '080078745da58a9295b56cc4c2835394ef9b1876fa1ad6459bc3e9d29d54d906', '2026-08-19 06:27:37'),
(160, 1, 'USC Administrator', 'admin', NULL, 'privacy', 'Viewed student identity', 'Sensitive E-Sumbong identity access recorded', 'concern', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '080078745da58a9295b56cc4c2835394ef9b1876fa1ad6459bc3e9d29d54d906', '21154a71c6a95e4d7d2aee0402b7b33daa82accf51d96dc55ff559c623bff45e', '2026-08-19 06:29:56'),
(161, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Referred concern', 'ES-2026-264015 · Route USC → MLUC · Assignee changed', 'concern', 1, '{\"status\":\"Submitted\",\"priority\":\"Normal\",\"assigned_scope\":\"USC\",\"assigned_to\":2}', '{\"status\":\"Submitted\",\"priority\":\"Normal\",\"assigned_scope\":\"MLUC\",\"assigned_to\":3,\"due_at\":\"2026-08-20 20:27:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '21154a71c6a95e4d7d2aee0402b7b33daa82accf51d96dc55ff559c623bff45e', '7e389525bcef8c0e66685fa3499c9505f6d9d34e7d707aedc4674807c393dc46', '2026-08-19 06:30:33'),
(162, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Referred concern', 'ES-2026-264015 · Route MLUC → USC', 'concern', 1, '{\"status\":\"Submitted\",\"priority\":\"Normal\",\"assigned_scope\":\"MLUC\",\"assigned_to\":3}', '{\"status\":\"Submitted\",\"priority\":\"Normal\",\"assigned_scope\":\"USC\",\"assigned_to\":3,\"due_at\":\"2026-08-20 20:27:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '7e389525bcef8c0e66685fa3499c9505f6d9d34e7d707aedc4674807c393dc46', 'fcad947246f492687615a3d48a778da4b6d1079c42f4e605336470bdd92ee2dc', '2026-08-19 06:30:53'),
(163, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Referred concern', 'ES-2026-264015 · Route USC → SLUC · Assignee changed', 'concern', 1, '{\"status\":\"Submitted\",\"priority\":\"Normal\",\"assigned_scope\":\"USC\",\"assigned_to\":3}', '{\"status\":\"Submitted\",\"priority\":\"Normal\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6,\"due_at\":\"2026-08-20 20:27:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'fcad947246f492687615a3d48a778da4b6d1079c42f4e605336470bdd92ee2dc', 'da15d1ebbcf5e7ec984ca87629748d8f1dc6b97551c20ed80e7c43f083b541d5', '2026-08-19 06:31:28'),
(164, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'da15d1ebbcf5e7ec984ca87629748d8f1dc6b97551c20ed80e7c43f083b541d5', '6619e1f2e5591ed9a08d00f305fcc1754e40fa4010c5258093f8899856a7e965', '2026-08-19 06:31:49'),
(165, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'concerns', 'Changed status', 'ES-2026-264015 · Status Submitted → Received', 'concern', 1, '{\"status\":\"Submitted\",\"priority\":\"Normal\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6}', '{\"status\":\"Received\",\"priority\":\"Normal\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6,\"due_at\":\"2026-08-20 20:27:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '6619e1f2e5591ed9a08d00f305fcc1754e40fa4010c5258093f8899856a7e965', '0effa01a3b615d297e138ff6ece25187cf274ca8c9030bd61992bf1aeeada91f', '2026-08-19 06:32:28'),
(166, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Untitled desi2222gn.png · SHA-256 67fa5bf2c03a', 'media', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '0effa01a3b615d297e138ff6ece25187cf274ca8c9030bd61992bf1aeeada91f', '317095f4fb116af21a6f4258ede9d6779726374119777e2f02862e1e6a7677db', '2026-08-19 06:38:41'),
(167, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'git-cheat-sheet-education.pdf · SHA-256 469157b16528', 'media', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '317095f4fb116af21a6f4258ede9d6779726374119777e2f02862e1e6a7677db', '4057db9c01385ccbf758d7daa802a2c5cf63df62a3be022caa99959a9520ea11', '2026-08-19 06:38:47'),
(168, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'bagulin_agritech.csv · SHA-256 3b840f75bf86', 'media', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '4057db9c01385ccbf758d7daa802a2c5cf63df62a3be022caa99959a9520ea11', 'cb51a5a5e432e71b0469bf39570511d1159bb8a473b5431d4d2a462f702a0c4b', '2026-08-19 06:38:54'),
(169, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'bagulin_agritech.csv', 'media', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'cb51a5a5e432e71b0469bf39570511d1159bb8a473b5431d4d2a462f702a0c4b', '2508a5dbdd0a2185d82fb498ce6ed9dfadf6c6c5721b5ed17df330d484798362', '2026-08-19 06:48:52'),
(170, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'git-cheat-sheet-education.pdf', 'media', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2508a5dbdd0a2185d82fb498ce6ed9dfadf6c6c5721b5ed17df330d484798362', 'be87f73ae3cd1e027bbafd8b96f6dadea3b6a87a9b04f1d10cd77e5d504164b3', '2026-08-19 06:48:54'),
(171, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Untitled desi2222gn.png', 'media', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'be87f73ae3cd1e027bbafd8b96f6dadea3b6a87a9b04f1d10cd77e5d504164b3', 'e89b327054d65608f9ac2080ec311a4b94ce08418fb5459c191a24324b8cbd85', '2026-08-19 06:48:56'),
(172, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Untitled desi2222gn.png · SHA-256 67fa5bf2c03a', 'media', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'e89b327054d65608f9ac2080ec311a4b94ce08418fb5459c191a24324b8cbd85', 'f68ba7efeb29454cb68cd5a3eeeb46c6d3e38da05ae3b31cda1005298b9845fc', '2026-08-19 06:49:03'),
(173, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Git Basic Operation Guide (1).pdf · SHA-256 92c4796953e7', 'media', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'f68ba7efeb29454cb68cd5a3eeeb46c6d3e38da05ae3b31cda1005298b9845fc', '379f196270ea44438bdaabd1402da4d0bfa3fa54eb92bac2dd9bcdbe98e0fd4e', '2026-08-19 06:49:12'),
(174, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'FarmRoot_manuscript2 (1).docx · SHA-256 13bc8e5ab8c5', 'media', 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '379f196270ea44438bdaabd1402da4d0bfa3fa54eb92bac2dd9bcdbe98e0fd4e', '1cf092a66e331666fddc596eeb028251e20e2db05a8c25a8f25751b50f762f37', '2026-08-19 06:49:24'),
(175, 1, 'USC Administrator', 'admin', NULL, 'media', 'Verified media asset', 'Git Basic Operation Guide (1).pdf · file present', 'media', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '1cf092a66e331666fddc596eeb028251e20e2db05a8c25a8f25751b50f762f37', '7252a590a2448b76c9eb679cf77885690a8dadc27386ad56f8f87a1c9c80d0fd', '2026-08-19 06:49:27'),
(176, 1, 'USC Administrator', 'admin', NULL, 'media', 'Verified media asset', 'FarmRoot_manuscript2 (1).docx · file present', 'media', 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '7252a590a2448b76c9eb679cf77885690a8dadc27386ad56f8f87a1c9c80d0fd', '973fc95f2f7333d507506d071a93d7eca8be9ee0716261fc942df54227aa10d1', '2026-08-19 06:49:34'),
(177, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Untitled desi2222gn.png', 'media', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '973fc95f2f7333d507506d071a93d7eca8be9ee0716261fc942df54227aa10d1', '49be7d9c058b9bc2d68cbfbc2c553aba363196c2cc4e922549efeaa7f9202dac', '2026-08-19 06:49:42'),
(178, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'FarmRoot_manuscript2 (1).docx', 'media', 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '49be7d9c058b9bc2d68cbfbc2c553aba363196c2cc4e922549efeaa7f9202dac', 'd7126bc26a6cf5d3ec176c0251a786670b5a8fb29e4eef255aa4604426ca8ca2', '2026-08-19 06:49:43'),
(179, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Git Basic Operation Guide (1).pdf', 'media', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'd7126bc26a6cf5d3ec176c0251a786670b5a8fb29e4eef255aa4604426ca8ca2', 'b52a87016b166103b4c227406d478f26ac23bba068d1c1766963ac1b3d6bfcbc', '2026-08-19 06:49:55'),
(180, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Untitled desi2222gn.png · SHA-256 67fa5bf2c03a', 'media', 9, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'b52a87016b166103b4c227406d478f26ac23bba068d1c1766963ac1b3d6bfcbc', '752ed7e966bc618cfcbcddd25f162a5babc00604e23d46e5d849c1607c75a050', '2026-08-19 06:54:37'),
(181, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', '774868512_1535642465242219_9039202900835858923_n (1).jpg · SHA-256 3c3c10e53a6b', 'media', 10, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '752ed7e966bc618cfcbcddd25f162a5babc00604e23d46e5d849c1607c75a050', 'acbe7e063d53d3be29265b769f9e8c8fa21cc7e8985bb1b38fac003fc8c7d01a', '2026-08-19 06:54:41'),
(182, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', '761747527_1526379522835180_6287043047617718657_n.jpg · SHA-256 da9da4604c6c', 'media', 11, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'acbe7e063d53d3be29265b769f9e8c8fa21cc7e8985bb1b38fac003fc8c7d01a', 'df5a44d280f9ed24dbe0d3950b0b261860c2931b0a810647f2915ba9b5ecbb06', '2026-08-19 06:54:47'),
(183, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'git-cheat-sheet-education.pdf · SHA-256 469157b16528', 'media', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'df5a44d280f9ed24dbe0d3950b0b261860c2931b0a810647f2915ba9b5ecbb06', '6b33bfc807002c64e426f85578c1485752b66b7d5f38c350e9d0bf5cb8331059', '2026-08-19 06:54:52'),
(184, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Reformat vs.docx · SHA-256 dbe68ba07cc8', 'media', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '6b33bfc807002c64e426f85578c1485752b66b7d5f38c350e9d0bf5cb8331059', 'aa9dd5d25dcfbe3d41ac8db3117d5522bb2acbef32e96356fb95a747d71ec760', '2026-08-19 06:54:56'),
(185, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Reformat vs.docx', 'media', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'aa9dd5d25dcfbe3d41ac8db3117d5522bb2acbef32e96356fb95a747d71ec760', '3c51254832b0bad28e208fab58e743e93771604b284e5eb15a8eeedf374059fd', '2026-08-19 06:55:27'),
(186, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'git-cheat-sheet-education.pdf', 'media', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '3c51254832b0bad28e208fab58e743e93771604b284e5eb15a8eeedf374059fd', '29953e8a580f2a7b5bf8fb502f3ffdc1293e530a24a0d8b805073bc115ce777b', '2026-08-19 06:55:29'),
(187, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', '761747527_1526379522835180_6287043047617718657_n.jpg', 'media', 11, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '29953e8a580f2a7b5bf8fb502f3ffdc1293e530a24a0d8b805073bc115ce777b', 'd1d0eeda2626e5db55c192847353dfeb637c7921c5f86131c7aa4449845946ba', '2026-08-19 06:55:31'),
(188, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', '774868512_1535642465242219_9039202900835858923_n (1).jpg', 'media', 10, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'd1d0eeda2626e5db55c192847353dfeb637c7921c5f86131c7aa4449845946ba', '07dbe242b36050e43f4475e3d531b9e0265ab7ef2c6218b49097617d49bc1b68', '2026-08-19 06:55:32'),
(189, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Untitled desi2222gn.png', 'media', 9, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '07dbe242b36050e43f4475e3d531b9e0265ab7ef2c6218b49097617d49bc1b68', '9c57dad2b8e9b11399df2f902c7d8816678de99b4f9a4428ebc09f19393f9ff0', '2026-08-19 06:55:34'),
(190, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Operational Plan.pptx · SHA-256 07c3f8366019', 'media', 14, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '9c57dad2b8e9b11399df2f902c7d8816678de99b4f9a4428ebc09f19393f9ff0', '78e463b8bd2a1a9acf4f346b492475cbd8e6a0457dbb4a5e6b4057b9fdff2023', '2026-08-19 06:59:39'),
(191, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Operational Plan.pptx', 'media', 14, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '78e463b8bd2a1a9acf4f346b492475cbd8e6a0457dbb4a5e6b4057b9fdff2023', '3252e62b9190af5507ca4b393d25c5c155e26e5ac0c8277c32f299d6f812831a', '2026-08-19 07:02:20'),
(192, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Untitled desi2222gn.png · SHA-256 67fa5bf2c03a', 'media', 15, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '3252e62b9190af5507ca4b393d25c5c155e26e5ac0c8277c32f299d6f812831a', 'd8faa636e02b04e9a82521fee784919be9f6977b286fe2f5e2182c2dd8404331', '2026-08-19 07:02:25'),
(193, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', '774868512_1535642465242219_9039202900835858923_n (1).jpg · SHA-256 3c3c10e53a6b', 'media', 16, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'd8faa636e02b04e9a82521fee784919be9f6977b286fe2f5e2182c2dd8404331', '3946600faf5c553c6348d0bbd44a024c77121c3850c30d50c5a8d9ff5bb724db', '2026-08-19 07:02:32'),
(194, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', '749300662_993920943483283_6310725844511423514_n.jpg · SHA-256 2708406f7993', 'media', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '3946600faf5c553c6348d0bbd44a024c77121c3850c30d50c5a8d9ff5bb724db', '4a43a9db37a97491b8c797712b35404a8b788a2630684e4fe96557c7ef871856', '2026-08-19 07:02:36'),
(195, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'FarmRoot_manuscript2 (1).docx · SHA-256 13bc8e5ab8c5', 'media', 18, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '4a43a9db37a97491b8c797712b35404a8b788a2630684e4fe96557c7ef871856', 'a42c5041690a519d2e1733afaa4632abacea801ea00b27c29aa02b7c49d06473', '2026-08-19 07:02:46'),
(196, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Untitled desi2222gn.png · duplicate content detected · SHA-256 67fa5bf2c03a', 'media', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'a42c5041690a519d2e1733afaa4632abacea801ea00b27c29aa02b7c49d06473', '2736b8e84fe9b1495c84b63a64d30092a894459e3e69ce995f824fb09157f8df', '2026-08-19 07:02:58'),
(197, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', '774868512_1535642465242219_9039202900835858923_n (1).jpg · duplicate content detected · SHA-256 3c3c10e53a6b', 'media', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2736b8e84fe9b1495c84b63a64d30092a894459e3e69ce995f824fb09157f8df', '51221f0ae2ad6b940bff4dbe9cb318782f18b4c72dd06ad25570a6e6e45d4406', '2026-08-19 07:03:19'),
(198, 1, 'USC Administrator', 'admin', NULL, 'media', 'Verified media asset', 'Untitled desi2222gn.png · file present', 'media', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '51221f0ae2ad6b940bff4dbe9cb318782f18b4c72dd06ad25570a6e6e45d4406', '572ca429e5caf44f233ceefd200e38f21a550f899884cce29c81be9ab2fb5658', '2026-08-19 07:03:37'),
(199, 1, 'USC Administrator', 'admin', NULL, 'media', 'Verified media asset', 'Untitled desi2222gn.png · file present', 'media', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '572ca429e5caf44f233ceefd200e38f21a550f899884cce29c81be9ab2fb5658', '21c8d0c7fff2eb604484d3dcb6b226a87e0d1a780e86c53f0e379c06c5b41a46', '2026-08-19 07:03:39'),
(200, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Untitled desi2222gn.png', 'media', 15, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '21c8d0c7fff2eb604484d3dcb6b226a87e0d1a780e86c53f0e379c06c5b41a46', '10b8f4d4895dd4f8b8f9fc3b843def18e2116127203da9f6040a550592507962', '2026-08-19 07:12:02'),
(201, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', '774868512_1535642465242219_9039202900835858923_n (1).jpg', 'media', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '10b8f4d4895dd4f8b8f9fc3b843def18e2116127203da9f6040a550592507962', 'c248f52f940248b3b7620d9cb6880e140824a3aecba8ad7c4a8ca9b26acf7eda', '2026-08-19 07:12:03'),
(202, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Untitled desi2222gn.png', 'media', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'c248f52f940248b3b7620d9cb6880e140824a3aecba8ad7c4a8ca9b26acf7eda', '7e2ed793d28899e5ce3d86402720c425667fdb8d4c05582c9119abf7d5e397bd', '2026-08-19 07:12:05'),
(203, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'FarmRoot_manuscript2 (1).docx', 'media', 18, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '7e2ed793d28899e5ce3d86402720c425667fdb8d4c05582c9119abf7d5e397bd', '3f4d5754fda63821c210ee60877769977cc68d42b887533387ef78df65441dc1', '2026-08-19 07:12:06'),
(204, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', '749300662_993920943483283_6310725844511423514_n.jpg', 'media', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '3f4d5754fda63821c210ee60877769977cc68d42b887533387ef78df65441dc1', '27ada9e0b13eeb9cee9599b10dd6f51a33c965ddb5fdf295dcefa1d046f507af', '2026-08-19 07:12:07'),
(205, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', '774868512_1535642465242219_9039202900835858923_n (1).jpg', 'media', 16, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '27ada9e0b13eeb9cee9599b10dd6f51a33c965ddb5fdf295dcefa1d046f507af', '245c41cbda0f6a9f5462c1e560ad666aabcb41d6e467cfeb93bc38bf1885f037', '2026-08-19 07:12:10'),
(206, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Untitled desi2222gn.png · SHA-256 67fa5bf2c03a', 'media', 21, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '245c41cbda0f6a9f5462c1e560ad666aabcb41d6e467cfeb93bc38bf1885f037', '5bdfb58ddce289bd4653a9640a575b454721e3b29ba575583b02c375871ddfa9', '2026-08-19 07:12:36'),
(207, 1, 'USC Administrator', 'admin', 'USC', 'system', 'Changed portal context', 'ALL → USC', NULL, NULL, '{\"portal\":\"ALL\"}', '{\"portal\":\"USC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '5bdfb58ddce289bd4653a9640a575b454721e3b29ba575583b02c375871ddfa9', '5308a4bb12d62a70b50b99dfe7abef88292bda5742057c77b7a15d24ab919d15', '2026-08-19 07:21:42'),
(208, 1, 'USC Administrator', 'admin', NULL, 'system', 'Changed portal context', 'USC → ALL', NULL, NULL, '{\"portal\":\"USC\"}', '{\"portal\":\"ALL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '5308a4bb12d62a70b50b99dfe7abef88292bda5742057c77b7a15d24ab919d15', 'e636f0321577dfa70d525f8e724edb88a080450cc0a73518c509fbe3289854ab', '2026-08-19 07:21:44'),
(209, 1, 'USC Administrator', 'admin', 'MLUC', 'system', 'Changed portal context', 'ALL → MLUC', NULL, NULL, '{\"portal\":\"ALL\"}', '{\"portal\":\"MLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'e636f0321577dfa70d525f8e724edb88a080450cc0a73518c509fbe3289854ab', 'c71393a78ca289525e13ebc3879433ed5b3b2c1caf784e4fb2400d3e4da967f4', '2026-08-19 07:21:55'),
(210, 1, 'USC Administrator', 'admin', NULL, 'system', 'Changed portal context', 'MLUC → ALL', NULL, NULL, '{\"portal\":\"MLUC\"}', '{\"portal\":\"ALL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'c71393a78ca289525e13ebc3879433ed5b3b2c1caf784e4fb2400d3e4da967f4', '136cd2556e2fbf96091eb00c61365dffcfaa4dc84b1a6be4f1e5d3490fc998b4', '2026-08-19 07:22:00'),
(211, 1, 'USC Administrator', 'admin', NULL, 'system', 'Ran database migrations', 'Applied 2026081905', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '136cd2556e2fbf96091eb00c61365dffcfaa4dc84b1a6be4f1e5d3490fc998b4', '70f8fc210749f9265b58c0c744e2e0a4c7b11003262747b63f2529752e3e2890', '2026-08-19 07:23:44'),
(212, 1, 'USC Administrator', 'admin', NULL, 'system', 'Ran database migrations', 'Database already current', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '70f8fc210749f9265b58c0c744e2e0a4c7b11003262747b63f2529752e3e2890', '8786be3de8afc255a25f01600ff0d63e4943f34b98c56b7b117d285c90249102', '2026-08-19 07:24:57'),
(213, 1, 'USC Administrator', 'admin', 'NLUC', 'system', 'Changed portal context', 'ALL → NLUC', NULL, NULL, '{\"portal\":\"ALL\"}', '{\"portal\":\"NLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '8786be3de8afc255a25f01600ff0d63e4943f34b98c56b7b117d285c90249102', '7ad36c15e9639cb11c2ca17ec5f0fb495f5a813a89a499c8f374d97fcd0c7d7d', '2026-08-19 07:28:54'),
(214, 1, 'USC Administrator', 'admin', 'USC', 'system', 'Changed portal context', 'NLUC → USC', NULL, NULL, '{\"portal\":\"NLUC\"}', '{\"portal\":\"USC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '7ad36c15e9639cb11c2ca17ec5f0fb495f5a813a89a499c8f374d97fcd0c7d7d', '186e1afb937994602e1dd4a5d846cf637d2940e0b65332e1622202eca616f33e', '2026-08-19 07:28:59'),
(215, 1, 'USC Administrator', 'admin', NULL, 'system', 'Changed portal context', 'USC → ALL', NULL, NULL, '{\"portal\":\"USC\"}', '{\"portal\":\"ALL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '186e1afb937994602e1dd4a5d846cf637d2940e0b65332e1622202eca616f33e', '531809f32ded2cf8e8be6f7c70317354c104f2cc874b4256c450edec51e8c74b', '2026-08-19 07:29:01'),
(216, 1, 'USC Administrator', 'admin', NULL, 'reports', 'Exported report summary', 'Operational report exported for Last 6 months', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '531809f32ded2cf8e8be6f7c70317354c104f2cc874b4256c450edec51e8c74b', '6b21c92b8e95bfda3c8ed7cda79f69bbf1595026725002259ce8e836ff06deb3', '2026-08-19 07:31:48'),
(217, 1, 'USC Administrator', 'admin', NULL, 'reports', 'Exported activity log', 'Exported filtered audit log as CSV', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '6b21c92b8e95bfda3c8ed7cda79f69bbf1595026725002259ce8e836ff06deb3', '5c2005bedb3884550da85ad2ec01bf745490fc7c805baf1460d715caefda44c5', '2026-08-19 07:51:19'),
(218, 1, 'USC Administrator', 'admin', NULL, 'media', 'Permanently deleted media', '774868512_1535642465242219_9039202900835858923_n (1).jpg', 'media', 16, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '5c2005bedb3884550da85ad2ec01bf745490fc7c805baf1460d715caefda44c5', '757ca5fc25b2cbdd468ea4108fd1da35ac13fff4a8aacefb5f50c3eb237b3b45', '2026-08-19 07:52:32'),
(219, 1, 'USC Administrator', 'admin', NULL, 'media', 'Permanently deleted media', '749300662_993920943483283_6310725844511423514_n.jpg', 'media', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '757ca5fc25b2cbdd468ea4108fd1da35ac13fff4a8aacefb5f50c3eb237b3b45', '0007e62b449de8c9015cd634a390b4373056e593ea31eca16d089fb542cebd5b', '2026-08-19 07:52:34'),
(220, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Untitled desi2222gn.png', 'media', 21, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '0007e62b449de8c9015cd634a390b4373056e593ea31eca16d089fb542cebd5b', '4d8a49e77b4d26a6740bf0dcfb110eddf196caf2bf762f460618abcb8094e0bd', '2026-08-19 07:55:16'),
(221, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Untitled desi2222gn.png · SHA-256 67fa5bf2c03a', 'media', 22, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '4d8a49e77b4d26a6740bf0dcfb110eddf196caf2bf762f460618abcb8094e0bd', 'c4c7dccf54fe6329053761cc4ae12872f0e5a0026e99f16def36e448ffdf97d5', '2026-08-19 08:23:54'),
(222, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Untitled desi2222gn.png', 'media', 22, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'c4c7dccf54fe6329053761cc4ae12872f0e5a0026e99f16def36e448ffdf97d5', '2c7cb8f74f72580b75865040bf7e173fee4770febdf325350ce8acd92d71e84b', '2026-08-19 08:28:18'),
(223, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Untitled desi2222gn.png · SHA-256 67fa5bf2c03a', 'media', 23, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2c7cb8f74f72580b75865040bf7e173fee4770febdf325350ce8acd92d71e84b', 'f38f1cfd64566e666a40b9a150c271ad2dd4cabb9063c57e967988cad2d0be0b', '2026-08-19 08:28:30'),
(224, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Untitled desi2222gn.png', 'media', 23, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'f38f1cfd64566e666a40b9a150c271ad2dd4cabb9063c57e967988cad2d0be0b', '0da4dbe13bb7daa3e30cb8a4a912dfb69588c8cc8ad56c30b5f086576e23687b', '2026-08-19 08:28:39'),
(225, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Untitled desi2222gn.png · SHA-256 67fa5bf2c03a', 'media', 24, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '0da4dbe13bb7daa3e30cb8a4a912dfb69588c8cc8ad56c30b5f086576e23687b', 'e328c709e463b26355dcc18d55cf58450308c29df8ea67c6fa17d184785f19d7', '2026-08-19 08:32:49'),
(226, 1, 'USC Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Untitled desi2222gn.png', 'media', 24, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'e328c709e463b26355dcc18d55cf58450308c29df8ea67c6fa17d184785f19d7', 'd5f8498dc433342f53b246590ae61a34647cd797c1743a387305abba0b89f05d', '2026-08-19 08:33:00'),
(227, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'd5f8498dc433342f53b246590ae61a34647cd797c1743a387305abba0b89f05d', '5f192ec2b2930b7035cf65835a58e3bf5bc466701e8815690ef5efe9ce06e0dd', '2026-08-19 09:30:22'),
(228, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'University Student Council · Your voice deserves a clear channel.', 'hero_slide', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '5f192ec2b2930b7035cf65835a58e3bf5bc466701e8815690ef5efe9ce06e0dd', '9dcb460613254369001c19675fbcc1dbdd596fa0e50e4bfb68c91bafc7239921', '2026-08-19 10:12:35'),
(229, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'University Student Council · Your voice deserves a clear channel.', 'hero_slide', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '9dcb460613254369001c19675fbcc1dbdd596fa0e50e4bfb68c91bafc7239921', 'a0d8a7440d50967c291a45545322a2d3823d873ecf796548336c00877d37d5d3', '2026-08-19 10:12:42'),
(230, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Removed hero slide', 'University Student Council · Hero slide #4', 'hero_slide', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'a0d8a7440d50967c291a45545322a2d3823d873ecf796548336c00877d37d5d3', '64a0271680f527f084686633f87d93dc7c8d1e0cc98086e899406376ac3f1803', '2026-08-19 10:13:11'),
(231, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'auth', 'Signed out', 'Administrator signed out', 'admin', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '64a0271680f527f084686633f87d93dc7c8d1e0cc98086e899406376ac3f1803', 'ae0526ff408aba2a37b40e2b304f9d70d2717050da80966ecc47196858875586', '2026-08-19 11:04:00'),
(232, 1, 'USC Administrator', 'admin', 'USC', 'system', 'Changed portal context', 'ALL → USC', NULL, NULL, '{\"portal\":\"ALL\"}', '{\"portal\":\"USC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'ae0526ff408aba2a37b40e2b304f9d70d2717050da80966ecc47196858875586', 'e66b3c4d3c04d92663e076342ff2370970f427d9dda2da98803c30b4074b56aa', '2026-08-19 11:04:38'),
(233, 1, 'USC Administrator', 'admin', 'NLUC', 'system', 'Changed portal context', 'USC → NLUC', NULL, NULL, '{\"portal\":\"USC\"}', '{\"portal\":\"NLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'e66b3c4d3c04d92663e076342ff2370970f427d9dda2da98803c30b4074b56aa', '78f03fa8785f2a7ddb66e4913b408d995b03d20c93077d8b8f3f8a145e5dc592', '2026-08-19 11:04:40'),
(234, 1, 'USC Administrator', 'admin', 'MLUC', 'system', 'Changed portal context', 'NLUC → MLUC', NULL, NULL, '{\"portal\":\"NLUC\"}', '{\"portal\":\"MLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '78f03fa8785f2a7ddb66e4913b408d995b03d20c93077d8b8f3f8a145e5dc592', '309629494b1bf126e16c941e79266f29f0c9e04eed9ce48ff0d53c36650685a8', '2026-08-19 11:04:42'),
(235, 1, 'USC Administrator', 'admin', NULL, 'system', 'Changed portal context', 'MLUC → ALL', NULL, NULL, '{\"portal\":\"MLUC\"}', '{\"portal\":\"ALL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '309629494b1bf126e16c941e79266f29f0c9e04eed9ce48ff0d53c36650685a8', 'c76a03a3d7ac5ac8c7676e0ff1a02edf0646a0ba2d73997f0ac9e4aff617957e', '2026-08-19 11:04:49'),
(236, 1, 'USC Administrator', 'admin', NULL, 'auth', 'Signed out', 'Administrator signed out', 'admin', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'c76a03a3d7ac5ac8c7676e0ff1a02edf0646a0ba2d73997f0ac9e4aff617957e', 'b9125ad45190f63f31f2ac26c3bb5c782bc33e8e224f7ba599ffc1370f015a25', '2026-08-19 11:41:59'),
(237, 1, 'USC Administrator', 'admin', NULL, 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'b9125ad45190f63f31f2ac26c3bb5c782bc33e8e224f7ba599ffc1370f015a25', '2abc8173017cb0648f5ff068af93bf0f20be29756381127ce3d4fee85213eaf4', '2026-08-19 11:42:12'),
(238, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'University Student Council · Your voice deserves a clear channel.', 'hero_slide', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2abc8173017cb0648f5ff068af93bf0f20be29756381127ce3d4fee85213eaf4', '1b1ab2469621b0d70323800b2f703b8b1c91072b7e3b2298b7eb92036fc7eb36', '2026-08-19 11:43:23'),
(239, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'University Student Council · Your voice deserves a clear channel.', 'hero_slide', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '1b1ab2469621b0d70323800b2f703b8b1c91072b7e3b2298b7eb92036fc7eb36', '3f03c7cb278527202021a5e38b34e371b8a79391c6fb826760b7f77bed793ed1', '2026-08-19 11:43:49'),
(240, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'University Student Council · Your voice deserves a clear channel.', 'hero_slide', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '3f03c7cb278527202021a5e38b34e371b8a79391c6fb826760b7f77bed793ed1', '3f827dfd0f83b13e16a92adcb848b380f8b057bd75a4dd4f52a0dcfde86e83e0', '2026-08-19 11:44:03'),
(241, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Updated hero slide', 'University Student Council · Your voice deserves a clear channel.', 'hero_slide', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '3f827dfd0f83b13e16a92adcb848b380f8b057bd75a4dd4f52a0dcfde86e83e0', 'c7c3bbf85495a300a7919e1f8c718f8c15188b23f85ea95cf7d0c910af7c0029', '2026-08-19 11:44:07'),
(242, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Untitled (1).png · SHA-256 3a63609b2c2b', 'media', 25, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'c7c3bbf85495a300a7919e1f8c718f8c15188b23f85ea95cf7d0c910af7c0029', '6a2c4930020c9907a1ac683df337cf99bae7d426ed00609ad13e8bed700bc7ad', '2026-08-19 11:44:42'),
(243, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Git Basic Operation Guide (1).pdf · SHA-256 92c4796953e7', 'media', 26, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '6a2c4930020c9907a1ac683df337cf99bae7d426ed00609ad13e8bed700bc7ad', '35e9a6e3c88c6813363bcdeed3a44b48d6fbbcf51e1ab6d0c2f028a369317c0b', '2026-08-19 11:44:50'),
(244, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', 'Operational Plan.pptx · SHA-256 07c3f8366019', 'media', 27, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '35e9a6e3c88c6813363bcdeed3a44b48d6fbbcf51e1ab6d0c2f028a369317c0b', '8c5bae43ffb7955cc838db11205f8272ca415d2788735b3732fbd86867436bf5', '2026-08-19 11:44:56'),
(245, 1, 'USC Administrator', 'admin', NULL, 'media', 'Uploaded media', '774868512_1535642465242219_9039202900835858923_n (1).jpg · SHA-256 3c3c10e53a6b', 'media', 28, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '8c5bae43ffb7955cc838db11205f8272ca415d2788735b3732fbd86867436bf5', 'b8d5bc453ccb859ba970786f3b0ee60a54fe59344f32cb31caed879a68a8b783', '2026-08-19 11:45:04'),
(246, 1, 'USC Administrator', 'admin', NULL, 'privacy', 'Viewed student identity', 'Sensitive E-Sumbong identity access recorded', 'concern', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'b8d5bc453ccb859ba970786f3b0ee60a54fe59344f32cb31caed879a68a8b783', 'a26fa42b12d13c20e77d873bf68ce48a8be0c9ac3afe0629e5984591d087a378', '2026-08-19 11:45:25'),
(247, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Referred concern', 'ES-2026-25A625 · Status Submitted → Received · Priority Normal → High · Route USC → MLUC · Assignee changed', 'concern', 2, '{\"status\":\"Submitted\",\"priority\":\"Normal\",\"assigned_scope\":\"USC\",\"assigned_to\":null}', '{\"status\":\"Received\",\"priority\":\"High\",\"assigned_scope\":\"MLUC\",\"assigned_to\":13,\"due_at\":\"2026-08-24 19:39:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'a26fa42b12d13c20e77d873bf68ce48a8be0c9ac3afe0629e5984591d087a378', '01d081be3eac46345df54bc97cc6e1f5fe9e6bc6d47050e1350a0cff9666c1d7', '2026-08-19 11:46:00'),
(248, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Referred concern', 'ES-2026-25A625 · Status Received → Under Review · Route MLUC → USC · Assignee changed', 'concern', 2, '{\"status\":\"Received\",\"priority\":\"High\",\"assigned_scope\":\"MLUC\",\"assigned_to\":13}', '{\"status\":\"Under Review\",\"priority\":\"High\",\"assigned_scope\":\"USC\",\"assigned_to\":3,\"due_at\":\"2026-08-24 19:39:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '01d081be3eac46345df54bc97cc6e1f5fe9e6bc6d47050e1350a0cff9666c1d7', '32f702af6c906384d28f5f6478c8dda8d2b88c792d0ae242fb4182dd3fce263e', '2026-08-19 11:46:20'),
(249, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Changed status', 'ES-2026-25A625 · Status Under Review → Referred', 'concern', 2, '{\"status\":\"Under Review\",\"priority\":\"High\",\"assigned_scope\":\"USC\",\"assigned_to\":3}', '{\"status\":\"Referred\",\"priority\":\"High\",\"assigned_scope\":\"USC\",\"assigned_to\":3,\"due_at\":\"2026-08-24 19:39:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '32f702af6c906384d28f5f6478c8dda8d2b88c792d0ae242fb4182dd3fce263e', '5b8452a44b6e1d856cccf3880f411dbf95bad53cf3e92917b1ce613e63ee57db', '2026-08-19 11:46:44'),
(250, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Changed status', 'ES-2026-25A625 · Status Referred → Resolved', 'concern', 2, '{\"status\":\"Referred\",\"priority\":\"High\",\"assigned_scope\":\"USC\",\"assigned_to\":3}', '{\"status\":\"Resolved\",\"priority\":\"High\",\"assigned_scope\":\"USC\",\"assigned_to\":3,\"due_at\":\"2026-08-24 19:39:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '5b8452a44b6e1d856cccf3880f411dbf95bad53cf3e92917b1ce613e63ee57db', '306f92b41fdbaeba30d3ffe6f4d582dc40fef637eb525862f58dfc362317e48b', '2026-08-19 11:47:03'),
(251, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-25A625 · Notes updated', 'concern', 2, '{\"status\":\"Resolved\",\"priority\":\"High\",\"assigned_scope\":\"USC\",\"assigned_to\":3}', '{\"status\":\"Resolved\",\"priority\":\"High\",\"assigned_scope\":\"USC\",\"assigned_to\":3,\"due_at\":\"2026-08-24 19:39:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '306f92b41fdbaeba30d3ffe6f4d582dc40fef637eb525862f58dfc362317e48b', '8c50e6d466859fa03e16239e66193ac8bc9f2b4529572e3338a54cec575f88c4', '2026-08-19 11:47:18'),
(252, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Referred concern', 'ES-2026-25A625 · Status Resolved → Referred · Route USC → SLUC · Assignee changed', 'concern', 2, '{\"status\":\"Resolved\",\"priority\":\"High\",\"assigned_scope\":\"USC\",\"assigned_to\":3}', '{\"status\":\"Referred\",\"priority\":\"High\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6,\"due_at\":\"2026-08-24 19:39:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '8c50e6d466859fa03e16239e66193ac8bc9f2b4529572e3338a54cec575f88c4', 'f824d76409d42e3f7cd4c23f5d286f28f048bb6eb63daa76efd013a10ac0d6bf', '2026-08-19 11:47:50');
INSERT INTO `admin_activity_logs` (`id`, `admin_id`, `admin_name`, `role`, `campus`, `module`, `action`, `description`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `prev_hash`, `record_hash`, `created_at`) VALUES
(253, 1, 'USC Administrator', 'admin', 'USC', 'system', 'Changed portal context', 'ALL → USC', NULL, NULL, '{\"portal\":\"ALL\"}', '{\"portal\":\"USC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'f824d76409d42e3f7cd4c23f5d286f28f048bb6eb63daa76efd013a10ac0d6bf', 'b89d86b334535332cb2fa07be088ad85c998b2df637d2fd095901e599ddbe9cc', '2026-08-19 11:50:06'),
(254, 1, 'USC Administrator', 'admin', 'NLUC', 'system', 'Changed portal context', 'USC → NLUC', NULL, NULL, '{\"portal\":\"USC\"}', '{\"portal\":\"NLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'b89d86b334535332cb2fa07be088ad85c998b2df637d2fd095901e599ddbe9cc', '11ce9cc243d55ce0ddb391ff3a9e9a6638a565e83be5add04d5fb30c92eb80d6', '2026-08-19 11:50:08'),
(255, 1, 'USC Administrator', 'admin', 'MLUC', 'system', 'Changed portal context', 'NLUC → MLUC', NULL, NULL, '{\"portal\":\"NLUC\"}', '{\"portal\":\"MLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '11ce9cc243d55ce0ddb391ff3a9e9a6638a565e83be5add04d5fb30c92eb80d6', 'de934cceec38b822ed89e51c68f4272d6607f36015117923a95605fff38881cb', '2026-08-19 11:50:11'),
(256, 1, 'USC Administrator', 'admin', 'SLUC', 'system', 'Changed portal context', 'MLUC → SLUC', NULL, NULL, '{\"portal\":\"MLUC\"}', '{\"portal\":\"SLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'de934cceec38b822ed89e51c68f4272d6607f36015117923a95605fff38881cb', 'ba40afa01d65943d965582ba9e04d0e03a246dd9cd2f1937eb3e0706783e716a', '2026-08-19 11:50:13'),
(257, 1, 'USC Administrator', 'admin', 'OUS', 'system', 'Changed portal context', 'SLUC → OUS', NULL, NULL, '{\"portal\":\"SLUC\"}', '{\"portal\":\"OUS\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'ba40afa01d65943d965582ba9e04d0e03a246dd9cd2f1937eb3e0706783e716a', '069e2961b4ae95cea52714a70923006946356a75b367d4c334717cd1eeba33c4', '2026-08-19 11:50:16'),
(258, 1, 'USC Administrator', 'admin', NULL, 'system', 'Changed portal context', 'OUS → ALL', NULL, NULL, '{\"portal\":\"OUS\"}', '{\"portal\":\"ALL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '069e2961b4ae95cea52714a70923006946356a75b367d4c334717cd1eeba33c4', 'cce3fda296b44cdba01542322ae20bae5f36b8bc2cbecb82b6497ede896d83a2', '2026-08-19 11:50:21'),
(259, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'cce3fda296b44cdba01542322ae20bae5f36b8bc2cbecb82b6497ede896d83a2', '6b314474bc92d97c628ddcc835805f0fdbdcc7ecf35fc042c963f88ee9a3ea10', '2026-08-19 11:51:01'),
(260, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'homepage', 'Requested USC promotion', 'South La Union Campus · Your campus voice deserves a clear channel.', 'hero_promotion', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '6b314474bc92d97c628ddcc835805f0fdbdcc7ecf35fc042c963f88ee9a3ea10', '9f3daf0a110f42b22cfd0cb0bccf97a3c8cd3fd651733ad97638baa31e797376', '2026-08-19 11:51:24'),
(261, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'homepage', 'Withdrew USC promotion', 'South La Union Campus · Request #4', 'hero_promotion', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '9f3daf0a110f42b22cfd0cb0bccf97a3c8cd3fd651733ad97638baa31e797376', '41bdd86e84574cf2c7600f138c30d4f6f5846cea5aa324bdeea0d766ab595962', '2026-08-19 11:51:37'),
(262, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'homepage', 'Requested USC promotion', 'South La Union Campus · Your campus voice deserves a clear channel.', 'hero_promotion', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '41bdd86e84574cf2c7600f138c30d4f6f5846cea5aa324bdeea0d766ab595962', '8ec78af9c1a59feb64e66b7064f5f0133c5f63613f55ec218fafe53df054f718', '2026-08-19 11:51:43'),
(263, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Rejected promoted slide', 'South La Union Campus · Your campus voice deserves a clear channel.', 'hero_promotion', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '8ec78af9c1a59feb64e66b7064f5f0133c5f63613f55ec218fafe53df054f718', '4a920724fab4b2c2a9bdc46727d721df9cf23126c149a16fdf9c1b7039cef5fa', '2026-08-19 11:52:00'),
(264, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'news', 'Saved publication', '𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫 · review', 'post', 10, '{\"title\":\"𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫\",\"status\":\"published\",\"portal\":\"SLUC\"}', '{\"title\":\"𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫\",\"status\":\"review\",\"portal\":\"SLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '4a920724fab4b2c2a9bdc46727d721df9cf23126c149a16fdf9c1b7039cef5fa', '8a28782e0894a8be6962eae3476e0c1a132e7046d637618279b754f7a4a14fbe', '2026-08-19 11:52:26'),
(265, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'media', 'Uploaded media', 'usc.png · SHA-256 68a280056e95', 'media', 29, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '8a28782e0894a8be6962eae3476e0c1a132e7046d637618279b754f7a4a14fbe', '1eb79e462a826e73d8790a7332889af7a25515a723bcdd83f7fa658f94dd347e', '2026-08-19 11:52:39'),
(266, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'privacy', 'Viewed student identity', 'Sensitive E-Sumbong identity access recorded', 'concern', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '1eb79e462a826e73d8790a7332889af7a25515a723bcdd83f7fa658f94dd347e', '919b542a90a3bf78b1884f039c90f4de27dd8879396b6b4071f910fd527b50fa', '2026-08-19 11:53:02'),
(267, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'concerns', 'Changed status', 'ES-2026-25A625 · Status Referred → In Progress', 'concern', 2, '{\"status\":\"Referred\",\"priority\":\"High\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6}', '{\"status\":\"In Progress\",\"priority\":\"High\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6,\"due_at\":\"2026-08-24 19:39:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '919b542a90a3bf78b1884f039c90f4de27dd8879396b6b4071f910fd527b50fa', '53d290175c38af068cf4f5f7a177303199cd1a267f2b89b32b557ed74bf6b8e1', '2026-08-19 11:53:14'),
(268, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'concerns', 'Changed status', 'ES-2026-25A625 · Status In Progress → Action Taken', 'concern', 2, '{\"status\":\"In Progress\",\"priority\":\"High\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6}', '{\"status\":\"Action Taken\",\"priority\":\"High\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6,\"due_at\":\"2026-08-24 19:39:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '53d290175c38af068cf4f5f7a177303199cd1a267f2b89b32b557ed74bf6b8e1', '59644427d2a8df7c2a7a685584a5186a58cf0c3b1a54c620527bfb7aa5e56a2f', '2026-08-19 11:53:30'),
(269, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Changed status', 'ES-2026-25A625 · Status Action Taken → Resolved', 'concern', 2, '{\"status\":\"Action Taken\",\"priority\":\"High\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6}', '{\"status\":\"Resolved\",\"priority\":\"High\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6,\"due_at\":\"2026-08-24 19:39:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '59644427d2a8df7c2a7a685584a5186a58cf0c3b1a54c620527bfb7aa5e56a2f', '048c8e73bcc249a0334afd878159b59a3c30a6ff9bbfb58bab4a07764456b3ca', '2026-08-19 11:53:55'),
(270, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'auth', 'Signed out', 'Administrator signed out', 'admin', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '048c8e73bcc249a0334afd878159b59a3c30a6ff9bbfb58bab4a07764456b3ca', '33680e919951d14d9de8f14fc04de68a72179e379a03bf7b0b56eb214f628df6', '2026-08-19 12:02:48'),
(271, 1, 'USC Administrator', 'admin', 'SLUC', 'system', 'Changed portal context', 'ALL → SLUC', NULL, NULL, '{\"portal\":\"ALL\"}', '{\"portal\":\"SLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '33680e919951d14d9de8f14fc04de68a72179e379a03bf7b0b56eb214f628df6', 'a222fca96c308666aea1ed6de09c5e836d9c6f0524ec827ca93465e2269f736d', '2026-08-19 12:12:06'),
(272, 1, 'USC Administrator', 'admin', 'SLUC', 'news', 'Saved publication', '𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫 · published', 'post', 10, '{\"title\":\"𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫\",\"status\":\"review\",\"portal\":\"SLUC\"}', '{\"title\":\"𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫\",\"status\":\"published\",\"portal\":\"SLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'a222fca96c308666aea1ed6de09c5e836d9c6f0524ec827ca93465e2269f736d', 'ae23c026169a15fb70fbf8ef3064887cbb54b0b631b0b9ff990712da385852cd', '2026-08-19 12:12:22'),
(273, 1, 'USC Administrator', 'admin', 'SLUC', 'auth', 'Signed out', 'Administrator signed out', 'admin', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'ae23c026169a15fb70fbf8ef3064887cbb54b0b631b0b9ff990712da385852cd', '08ae978ea3d733e790e975665ff2635e7274457dc853b35d9ee7035814b2ce38', '2026-08-19 12:16:21'),
(274, 1, 'USC Administrator', 'admin', NULL, 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '08ae978ea3d733e790e975665ff2635e7274457dc853b35d9ee7035814b2ce38', '54f5e89d49fd2507b99b73e63a6f4d520847970998f6029b9aa8a68a6d2b3ba9', '2026-08-19 12:16:34'),
(275, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Created hero slide', 'University Student Council · dsdada', 'hero_slide', 18, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '54f5e89d49fd2507b99b73e63a6f4d520847970998f6029b9aa8a68a6d2b3ba9', 'cdeae715eaa9d460f54195cf9650fa0cb0ff527e2864b1d393562bc0ea3831c2', '2026-08-19 12:18:17'),
(276, 1, 'USC Administrator', 'admin', NULL, 'homepage', 'Removed hero slide', 'University Student Council · Hero slide #18', 'hero_slide', 18, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'cdeae715eaa9d460f54195cf9650fa0cb0ff527e2864b1d393562bc0ea3831c2', '68eea32c52034d3c92ee76c462a527a5c26e93fe2e9e2684c40271a76d1011ce', '2026-08-19 12:18:38'),
(277, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Changed status', 'ES-2026-DF4A7B · Status Submitted → Received', 'concern', 3, '{\"status\":\"Submitted\",\"priority\":\"Normal\",\"assigned_scope\":\"USC\",\"assigned_to\":null}', '{\"status\":\"Received\",\"priority\":\"Normal\",\"assigned_scope\":\"USC\",\"assigned_to\":null,\"due_at\":\"2026-08-24 20:14:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '68eea32c52034d3c92ee76c462a527a5c26e93fe2e9e2684c40271a76d1011ce', '12da4126324ee1bc5a4c8f17bf4998a04ee9a07e7839b639b47648d218fc31d5', '2026-08-19 12:19:30'),
(278, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Reassigned concern', 'ES-2026-DF4A7B · Status Received → Referred · Assignee changed', 'concern', 3, '{\"status\":\"Received\",\"priority\":\"Normal\",\"assigned_scope\":\"USC\",\"assigned_to\":null}', '{\"status\":\"Referred\",\"priority\":\"Normal\",\"assigned_scope\":\"USC\",\"assigned_to\":3,\"due_at\":\"2026-08-24 20:14:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '12da4126324ee1bc5a4c8f17bf4998a04ee9a07e7839b639b47648d218fc31d5', 'ea754344d58e856ab99f388db6398887c3bc01e89551cf775ecd70fb99a7e787', '2026-08-19 12:19:52'),
(279, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Referred concern', 'ES-2026-DF4A7B · Route USC → SLUC · Assignee changed', 'concern', 3, '{\"status\":\"Referred\",\"priority\":\"Normal\",\"assigned_scope\":\"USC\",\"assigned_to\":3}', '{\"status\":\"Referred\",\"priority\":\"Normal\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6,\"due_at\":\"2026-08-24 20:14:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'ea754344d58e856ab99f388db6398887c3bc01e89551cf775ecd70fb99a7e787', '25191a7b960b898f3dc12981e6e00177845c185f7de7802e56271a0557c34820', '2026-08-19 12:20:13'),
(280, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '25191a7b960b898f3dc12981e6e00177845c185f7de7802e56271a0557c34820', 'c1bccfbf72210481a22d8eaf70def055dde3f8e3a3312cbabaabab584e968341', '2026-08-19 12:20:28'),
(281, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'concerns', 'Changed status', 'ES-2026-DF4A7B · Status Referred → In Progress', 'concern', 3, '{\"status\":\"Referred\",\"priority\":\"Normal\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6}', '{\"status\":\"In Progress\",\"priority\":\"Normal\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6,\"due_at\":\"2026-08-24 20:14:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'c1bccfbf72210481a22d8eaf70def055dde3f8e3a3312cbabaabab584e968341', '1522dd0d1f3d5e03d1fb0374a2ec85e20f2d2479eed5755fb80cc7e35afae010', '2026-08-19 12:20:54'),
(282, 1, 'USC Administrator', 'admin', NULL, 'privacy', 'Viewed student identity', 'Sensitive E-Sumbong identity access recorded', 'concern', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '1522dd0d1f3d5e03d1fb0374a2ec85e20f2d2479eed5755fb80cc7e35afae010', 'f21985de4964c50a839e0bcef812f47b046b6cfcafb1a41145cfc8953e9b1789', '2026-08-19 12:21:12'),
(283, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'concerns', 'Changed status', 'ES-2026-DF4A7B · Status In Progress → Action Taken', 'concern', 3, '{\"status\":\"In Progress\",\"priority\":\"Normal\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6}', '{\"status\":\"Action Taken\",\"priority\":\"Normal\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6,\"due_at\":\"2026-08-24 20:14:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'f21985de4964c50a839e0bcef812f47b046b6cfcafb1a41145cfc8953e9b1789', '32f0a8754cb732071ae68d094b849cc28b2dc0e16b5d26b79c6d665bf91ebed2', '2026-08-19 12:21:25'),
(284, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Changed status', 'ES-2026-DF4A7B · Status Action Taken → Resolved', 'concern', 3, '{\"status\":\"Action Taken\",\"priority\":\"Normal\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6}', '{\"status\":\"Resolved\",\"priority\":\"Normal\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6,\"due_at\":\"2026-08-24 20:14:00\",\"follow_up_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '32f0a8754cb732071ae68d094b849cc28b2dc0e16b5d26b79c6d665bf91ebed2', 'be2458d12bdf50bd982b87a59994ebbd02b11dc270ce965148bfa548fdb81584', '2026-08-19 12:21:46'),
(285, 1, 'USC Administrator', 'admin', 'USC', 'system', 'Changed portal context', 'ALL → USC', NULL, NULL, '{\"portal\":\"ALL\"}', '{\"portal\":\"USC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'be2458d12bdf50bd982b87a59994ebbd02b11dc270ce965148bfa548fdb81584', 'a5206f9e7b4b744f33eca5c332bd9fd747efdbadeb532800d16b2dfbb705c850', '2026-08-19 12:23:14'),
(286, 1, 'USC Administrator', 'admin', 'NLUC', 'system', 'Changed portal context', 'USC → NLUC', NULL, NULL, '{\"portal\":\"USC\"}', '{\"portal\":\"NLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'a5206f9e7b4b744f33eca5c332bd9fd747efdbadeb532800d16b2dfbb705c850', 'eb5c91bb0162640c2c0823f335986ed5e18b033af5d2f04b6c9bb73d86f7ced9', '2026-08-19 12:23:16'),
(287, 1, 'USC Administrator', 'admin', 'MLUC', 'system', 'Changed portal context', 'NLUC → MLUC', NULL, NULL, '{\"portal\":\"NLUC\"}', '{\"portal\":\"MLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'eb5c91bb0162640c2c0823f335986ed5e18b033af5d2f04b6c9bb73d86f7ced9', '41fa5ce2dd36ab602583202b6a9cf0982b4b98b85d3e18c61d888745dc48fdaa', '2026-08-19 12:23:18'),
(288, 1, 'USC Administrator', 'admin', 'SLUC', 'system', 'Changed portal context', 'MLUC → SLUC', NULL, NULL, '{\"portal\":\"MLUC\"}', '{\"portal\":\"SLUC\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '41fa5ce2dd36ab602583202b6a9cf0982b4b98b85d3e18c61d888745dc48fdaa', 'f295bbc7a93300b9ca85f74a282c2d9bebb65563a75e212c2f59a1ec8132c090', '2026-08-19 12:23:20'),
(289, 1, 'USC Administrator', 'admin', 'OUS', 'system', 'Changed portal context', 'SLUC → OUS', NULL, NULL, '{\"portal\":\"SLUC\"}', '{\"portal\":\"OUS\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'f295bbc7a93300b9ca85f74a282c2d9bebb65563a75e212c2f59a1ec8132c090', '38e5e97dd8a668800619d65261dc75a0ce7feb7ecdbb2551c2b38c1f0da6d84b', '2026-08-19 12:23:22'),
(290, 1, 'USC Administrator', 'admin', NULL, 'system', 'Changed portal context', 'OUS → ALL', NULL, NULL, '{\"portal\":\"OUS\"}', '{\"portal\":\"ALL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '38e5e97dd8a668800619d65261dc75a0ce7feb7ecdbb2551c2b38c1f0da6d84b', 'eaebba391b5627fc907f405e7cfe2d1ca6c994a747031c5d7f43e4e212ef8e5d', '2026-08-19 12:23:24'),
(291, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'homepage', 'Updated hero slide', 'South La Union Campus · Your campus voice deserves a clear channel.', 'hero_slide', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'eaebba391b5627fc907f405e7cfe2d1ca6c994a747031c5d7f43e4e212ef8e5d', '35777affc6a1753eeab1f292c613fb1306466f0eb6c38c74c543d69f3211d8a0', '2026-08-19 13:53:57'),
(292, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'homepage', 'Updated hero slide', 'South La Union Campus · Your campus voice deserves a clear channel.', 'hero_slide', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '35777affc6a1753eeab1f292c613fb1306466f0eb6c38c74c543d69f3211d8a0', '1007d329d0ebcb9d5424c1a9e934b923d1d0e78649d1454083838fdb438ea2a8', '2026-08-19 13:54:05'),
(293, 1, 'USC Administrator', 'admin', NULL, 'auth', 'Signed out', 'Administrator signed out', 'admin', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '1007d329d0ebcb9d5424c1a9e934b923d1d0e78649d1454083838fdb438ea2a8', '4a015a171b09770cfb430917518350bbc61306c149038a82544e98d6a716df67', '2026-08-19 14:45:31'),
(294, NULL, NULL, 'admin', NULL, 'auth', 'Failed sign-in', 'Failed sign-in attempt for admin', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '4a015a171b09770cfb430917518350bbc61306c149038a82544e98d6a716df67', '372f1242b503b8aeec933b148002f79f1bb946f4121f70ff6f3f2498e0a4e79c', '2026-08-19 14:45:40'),
(295, 1, 'USC Administrator', 'admin', NULL, 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '372f1242b503b8aeec933b148002f79f1bb946f4121f70ff6f3f2498e0a4e79c', '9fef630e14c6e7e6b8782ca21ef145cca8ba1ba94c1eae3face4c07465425e45', '2026-08-19 14:45:49'),
(296, 1, 'USC Administrator', 'admin', NULL, 'security', 'Enabled two-step verification', 'Authenticator verification enabled and recovery codes generated', 'admin', 1, '{\"two_factor\":false}', '{\"two_factor\":true,\"recovery_codes\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '9fef630e14c6e7e6b8782ca21ef145cca8ba1ba94c1eae3face4c07465425e45', 'b92bff5f1438e2171a45c02ac5f49ecef5404a1af479ac67eec0838062b144d2', '2026-08-19 14:48:31'),
(297, 1, 'USC Administrator', 'admin', NULL, 'auth', 'Signed out', 'Administrator signed out', 'admin', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'b92bff5f1438e2171a45c02ac5f49ecef5404a1af479ac67eec0838062b144d2', '248d0ae2f282c6c8b182ec4a54b007a8a282bf1abc76959bfa24e7a356ee4fb5', '2026-08-19 14:50:09'),
(298, 1, 'USC Administrator', 'admin', NULL, 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '248d0ae2f282c6c8b182ec4a54b007a8a282bf1abc76959bfa24e7a356ee4fb5', '95e85d448ece2027b221eeb31259af04919f43f21a593cd9eb7c0deef48a6bcc', '2026-08-19 14:50:47'),
(299, 1, 'USC Administrator', 'admin', NULL, 'system', 'Ran database migrations', 'Applied 2026081906, 2026081907, 2026081908, 2026081909, 2026081910, 2026081911, 2026081912, 2026081913, 2026081914, 2026081915, 2026081916', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '95e85d448ece2027b221eeb31259af04919f43f21a593cd9eb7c0deef48a6bcc', '6fe8e880a7943373264efbe0a6e933b2f27e5387c426aa4fc337f88c049aa9c6', '2026-08-19 23:22:41'),
(300, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-25A625 · Notes updated', 'concern', 2, '{\"status\":\"Resolved\",\"priority\":\"High\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6}', '{\"status\":\"Resolved\",\"priority\":\"High\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6,\"due_at\":\"2026-08-24 19:39:00\",\"follow_up_at\":null,\"resolution_summary\":\"WOOOWWW\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '6fe8e880a7943373264efbe0a6e933b2f27e5387c426aa4fc337f88c049aa9c6', '5149234a3e38ee2a2d360eca420821504ec3fd6d625e0a6a315a8dd77cad3deb', '2026-08-20 00:52:46'),
(301, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Updated concern', 'ES-2026-25A625 · Notes updated', 'concern', 2, '{\"status\":\"Resolved\",\"priority\":\"High\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6}', '{\"status\":\"Resolved\",\"priority\":\"High\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6,\"due_at\":\"2026-08-24 19:39:00\",\"follow_up_at\":null,\"resolution_summary\":\"WOOOWWW\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '5149234a3e38ee2a2d360eca420821504ec3fd6d625e0a6a315a8dd77cad3deb', '52f7528e5265f5a0f050908880f4dd5e5b93c2e8e128fb7f585ccb4b1b4d6486', '2026-08-20 00:53:09'),
(302, 1, 'USC Administrator', 'admin', NULL, 'concerns', 'Changed status', 'ES-2026-25A625 · Status Resolved → Closed', 'concern', 2, '{\"status\":\"Resolved\",\"priority\":\"High\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6}', '{\"status\":\"Closed\",\"priority\":\"High\",\"assigned_scope\":\"SLUC\",\"assigned_to\":6,\"due_at\":\"2026-08-24 19:39:00\",\"follow_up_at\":null,\"resolution_summary\":\"WOOOWWW\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '52f7528e5265f5a0f050908880f4dd5e5b93c2e8e128fb7f585ccb4b1b4d6486', 'ce52b65837e220315fcb7a5c0a818d5279c7fb7e7ba2eb7ff0efc132a0a9ecb8', '2026-08-20 00:53:17'),
(303, 1, 'USC Administrator', 'admin', NULL, 'privacy', 'Downloaded concern evidence', 'Sensitive E-Sumbong identity access recorded', 'concern', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'ce52b65837e220315fcb7a5c0a818d5279c7fb7e7ba2eb7ff0efc132a0a9ecb8', 'ff7afed4db15cb3cf9d6cea171b6711747fb4ae992c2761bc928ad399813392d', '2026-08-20 00:53:48'),
(304, 1, 'System Administrator', 'admin', NULL, 'accounts', 'Updated account', 'System Administrator', 'admin', 1, '{\"full_name\":\"USC Administrator\",\"username\":\"admin\",\"email\":null,\"role\":\"admin\",\"campus\":null,\"status\":\"active\"}', '{\"full_name\":\"System Administrator\",\"username\":\"admin\",\"email\":null,\"role\":\"admin\",\"campus\":null,\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'ff7afed4db15cb3cf9d6cea171b6711747fb4ae992c2761bc928ad399813392d', 'f73f45b1d735c773ff7127804c194381fe4ba45c6786e33a0dcbe585559ddcdf', '2026-08-20 01:19:38'),
(305, 1, 'System Administrator', 'admin', NULL, 'system', 'Ran database migrations', 'Applied 2026082001', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'f73f45b1d735c773ff7127804c194381fe4ba45c6786e33a0dcbe585559ddcdf', '2f9a60df9534462263e80643a3a7238801652f2c66fe3cc4dd8de386fb57a4d4', '2026-08-20 01:57:43'),
(306, 1, 'System Administrator', 'admin', NULL, 'system', 'Verified backup', 'dmmmsu_usc_20260820_095743_database.sql · Checksum and structure verified.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2f9a60df9534462263e80643a3a7238801652f2c66fe3cc4dd8de386fb57a4d4', '8478b5480a1412b21414846313ab3985fbf0c4a98feb3d209da8479a6dc95f8c', '2026-08-20 01:58:17'),
(307, 1, 'System Administrator', 'admin', NULL, 'system', 'Ran scheduled maintenance', 'Published 0 scheduled item(s); processed 0 email(s)', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '8478b5480a1412b21414846313ab3985fbf0c4a98feb3d209da8479a6dc95f8c', '55da2bfa4cb2fcdf114ad19301da8c01b0bc02c137260860175aa2aa0f141a86', '2026-08-20 01:58:23'),
(308, 1, 'System Administrator', 'admin', NULL, 'system', 'Ran scheduled maintenance', 'Published 0 scheduled item(s); processed 0 email(s)', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '55da2bfa4cb2fcdf114ad19301da8c01b0bc02c137260860175aa2aa0f141a86', '9cc5e5252c9f0d2096437d6632f193426e4337a2a0ca86b8a7ec16f550fb3868', '2026-08-20 01:58:30'),
(309, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Michael Dean Malonzo · President', 'officer', 1, NULL, '{\"portal_code\":\"USC\",\"academic_year_id\":1,\"photo_added\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '9cc5e5252c9f0d2096437d6632f193426e4337a2a0ca86b8a7ec16f550fb3868', '2a155923df6a634b75caed7639450c62d12d08696070a804bd8bb650b3287288', '2026-08-20 02:02:32'),
(310, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'Michael Dean Malonzo · President', 'officer', 1, '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\"}', '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\",\"photo_updated\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2a155923df6a634b75caed7639450c62d12d08696070a804bd8bb650b3287288', 'e08e935b08ab1456c0adc93b08abe4f372f15a5bd5209c0ce54faf3d642fd551', '2026-08-20 02:08:02'),
(311, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Francis Paul Sagad · Vice President', 'officer', 2, NULL, '{\"portal_code\":\"USC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'e08e935b08ab1456c0adc93b08abe4f372f15a5bd5209c0ce54faf3d642fd551', '27cd5482f56094715304a91a4b943b568c9b48787dd3cb82770b42cbbd35f71f', '2026-08-20 02:09:11'),
(312, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Joeleiyane Sotelo · Ethical Standards Officer', 'officer', 3, NULL, '{\"portal_code\":\"USC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '27cd5482f56094715304a91a4b943b568c9b48787dd3cb82770b42cbbd35f71f', '671a47f8350bfb633edaf18b18cf06b806dc0a43eaac0b40aba4ada7655577e1', '2026-08-20 02:10:41'),
(313, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'Francis Paul Sagad · Vice President', 'officer', 2, '{\"full_name\":\"Francis Paul Sagad\",\"position_title\":\"Vice President\",\"portal_code\":\"USC\"}', '{\"full_name\":\"Francis Paul Sagad\",\"position_title\":\"Vice President\",\"portal_code\":\"USC\",\"photo_updated\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '671a47f8350bfb633edaf18b18cf06b806dc0a43eaac0b40aba4ada7655577e1', 'd0e7adedd9e6e0001cca6fba420c8df2cf2e65be9a67fb07ab330d691bab9876', '2026-08-20 02:16:52'),
(314, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'Joeleiyane Sotelo · Ethical Standards Officer', 'officer', 3, '{\"full_name\":\"Joeleiyane Sotelo\",\"position_title\":\"Ethical Standards Officer\",\"portal_code\":\"USC\"}', '{\"full_name\":\"Joeleiyane Sotelo\",\"position_title\":\"Ethical Standards Officer\",\"portal_code\":\"USC\",\"photo_updated\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'd0e7adedd9e6e0001cca6fba420c8df2cf2e65be9a67fb07ab330d691bab9876', 'f87d60f5db68f74f26946365cd9f81d11ac1398fa79467f666adb89b082edb66', '2026-08-20 02:16:58'),
(315, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Cheery Macapagong · Executive Secretary', 'officer', 4, NULL, '{\"portal_code\":\"USC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'f87d60f5db68f74f26946365cd9f81d11ac1398fa79467f666adb89b082edb66', '9619708ee86887558da5128b7997870ca8e96b26835fb029c5ed29d99a472b72', '2026-08-20 02:17:30'),
(316, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'Allen Mike Hubbard · Student Affairs and Services', 'officer', 4, '{\"full_name\":\"Cheery Macapagong\",\"position_title\":\"Executive Secretary\",\"portal_code\":\"USC\"}', '{\"full_name\":\"Allen Mike Hubbard\",\"position_title\":\"Student Affairs and Services\",\"portal_code\":\"USC\",\"photo_updated\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '9619708ee86887558da5128b7997870ca8e96b26835fb029c5ed29d99a472b72', 'e2b47a6150574c86ea54c354e76703e2e31e5223136494c59e52cba1e33191c3', '2026-08-20 02:21:10'),
(317, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'Allen Mike Hubbard · Student Affairs and Services', 'officer', 4, '{\"full_name\":\"Allen Mike Hubbard\",\"position_title\":\"Student Affairs and Services\",\"portal_code\":\"USC\"}', '{\"full_name\":\"Allen Mike Hubbard\",\"position_title\":\"Student Affairs and Services\",\"portal_code\":\"USC\",\"photo_updated\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'e2b47a6150574c86ea54c354e76703e2e31e5223136494c59e52cba1e33191c3', '2ccf612263a972f1d2d8a26ed2cfc86562e4ca8649a6386cdee45f3b8c5087ae', '2026-08-20 02:21:39'),
(318, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Cheery Macapagong · Executive Secretary', 'officer', 5, NULL, '{\"portal_code\":\"USC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2ccf612263a972f1d2d8a26ed2cfc86562e4ca8649a6386cdee45f3b8c5087ae', '61570e35030144d359c3f633b9bc4371df1eaea782deb772f6471ecb8b739aa3', '2026-08-20 02:23:05'),
(319, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'Cheery Macapagong · Executive Secretary', 'officer', 5, '{\"full_name\":\"Cheery Macapagong\",\"position_title\":\"Executive Secretary\",\"portal_code\":\"USC\"}', '{\"full_name\":\"Cheery Macapagong\",\"position_title\":\"Executive Secretary\",\"portal_code\":\"USC\",\"photo_updated\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '61570e35030144d359c3f633b9bc4371df1eaea782deb772f6471ecb8b739aa3', 'c638143e400cd6e2b4260e82bb735782e1764cf55c8a8b8daa3cdd1d9060996e', '2026-08-20 02:23:17'),
(320, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'Allen Mike Hubbard · Student Affairs and Services', 'officer', 4, '{\"full_name\":\"Allen Mike Hubbard\",\"position_title\":\"Student Affairs and Services\",\"portal_code\":\"USC\"}', '{\"full_name\":\"Allen Mike Hubbard\",\"position_title\":\"Student Affairs and Services\",\"portal_code\":\"USC\",\"photo_updated\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'c638143e400cd6e2b4260e82bb735782e1764cf55c8a8b8daa3cdd1d9060996e', '828349198b552de9309fb6e3223ad16a2e890f580911042a32701783b2a321ca', '2026-08-20 02:23:23'),
(321, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Rod Mark Fernandez · Student Information and Communications Technology', 'officer', 6, NULL, '{\"portal_code\":\"USC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '828349198b552de9309fb6e3223ad16a2e890f580911042a32701783b2a321ca', 'a8c7db03cdb3a2d4dbd522708500e903223909bceeebfc77ecc59f2028adc321', '2026-08-20 02:23:55'),
(322, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Jhulia Ellaine Duran · Health and Wellness', 'officer', 7, NULL, '{\"portal_code\":\"USC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'a8c7db03cdb3a2d4dbd522708500e903223909bceeebfc77ecc59f2028adc321', '0bd7a857e367437c70e2b5f15c7bad489c57073f5b9f7950a154704d1b4a83b7', '2026-08-20 02:24:20'),
(323, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Marean Lexy Aspiras · Environmental Affairs', 'officer', 8, NULL, '{\"portal_code\":\"USC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '0bd7a857e367437c70e2b5f15c7bad489c57073f5b9f7950a154704d1b4a83b7', '7ac9c0764f3c4c01dbfc662dc3f53b10818ae6832ebefc216c4182b807eea1aa', '2026-08-20 02:25:08'),
(324, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Psyche Andrei Liclican · Gender and Development', 'officer', 9, NULL, '{\"portal_code\":\"USC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '7ac9c0764f3c4c01dbfc662dc3f53b10818ae6832ebefc216c4182b807eea1aa', '4a28c75b3ee53a54c083bdabc6b328f6b9eb9b47b649da1c7a479352e3968602', '2026-08-20 02:26:01'),
(325, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Edvir Dave Asprec · Sports and Youth Development', 'officer', 10, NULL, '{\"portal_code\":\"USC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '4a28c75b3ee53a54c083bdabc6b328f6b9eb9b47b649da1c7a479352e3968602', '5bf5581803b9d94075ef89be955f37505d5500dbe20077569368616068128b85', '2026-08-20 02:26:20'),
(326, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'John Paul Vincent Estacio · Budget and Finance', 'officer', 11, NULL, '{\"portal_code\":\"USC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '5bf5581803b9d94075ef89be955f37505d5500dbe20077569368616068128b85', 'f428afa03d81ca7efbd09ee4d4f29b3abd5ea95c8cceac93055cda30e2f7a568', '2026-08-20 02:26:43'),
(327, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Ray Jasper Jacaban · Ways and Means', 'officer', 12, NULL, '{\"portal_code\":\"USC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'f428afa03d81ca7efbd09ee4d4f29b3abd5ea95c8cceac93055cda30e2f7a568', '58e7e95ce19b26997a899ad72134349ae880e3916f307b317937a0a1c77ca564', '2026-08-20 02:27:08'),
(328, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'George Rexy Vincent Bacani · Audit Commissioner', 'officer', 13, NULL, '{\"portal_code\":\"USC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '58e7e95ce19b26997a899ad72134349ae880e3916f307b317937a0a1c77ca564', '6a663bceba5a592704cba26abcb2df04efdb69813ca221e1ce46b471bed0d557', '2026-08-20 02:27:27'),
(329, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'George Rexy Vincent Bacani · Audit Commissioner', 'officer', 13, '{\"full_name\":\"George Rexy Vincent Bacani\",\"position_title\":\"Audit Commissioner\",\"portal_code\":\"USC\"}', '{\"full_name\":\"George Rexy Vincent Bacani\",\"position_title\":\"Audit Commissioner\",\"portal_code\":\"USC\",\"photo_updated\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '6a663bceba5a592704cba26abcb2df04efdb69813ca221e1ce46b471bed0d557', 'e7747cf81043862d091307a4afae2d5855c15597d5ebbd41ead2986a5cd26c54', '2026-08-20 02:29:02'),
(330, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Christian Soriano · Linkages', 'officer', 14, NULL, '{\"portal_code\":\"USC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'e7747cf81043862d091307a4afae2d5855c15597d5ebbd41ead2986a5cd26c54', 'e22f43dc2e9881b67cb2cff5e6e2f916494f7d313813cb9f9c87708a3ff05852', '2026-08-20 02:31:24'),
(331, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'George Rexy Vincent Bacani · Audit Commissioner', 'officer', 13, '{\"full_name\":\"George Rexy Vincent Bacani\",\"position_title\":\"Audit Commissioner\",\"portal_code\":\"USC\"}', '{\"full_name\":\"George Rexy Vincent Bacani\",\"position_title\":\"Audit Commissioner\",\"portal_code\":\"USC\",\"photo_updated\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'e22f43dc2e9881b67cb2cff5e6e2f916494f7d313813cb9f9c87708a3ff05852', 'd09c25fd51d4404506895981c7b7294f86c284bbcce3f74e5cf8d40848b799c8', '2026-08-20 02:35:45'),
(332, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'Michael Dean Malonzo · President', 'officer', 1, '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\"}', '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\",\"photo_updated\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'd09c25fd51d4404506895981c7b7294f86c284bbcce3f74e5cf8d40848b799c8', 'cd33b5009ffb7f4a3a2a2cd74d9867ec708556945dbad603156fb243f7a713a3', '2026-08-20 02:37:51'),
(333, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'Michael Dean Malonzo · President', 'officer', 1, '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\"}', '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\",\"photo_updated\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'cd33b5009ffb7f4a3a2a2cd74d9867ec708556945dbad603156fb243f7a713a3', '01992455739e5a36e32841ec55b5c8f3321e987d04e3c5240c9ae9db84a67758', '2026-08-20 02:38:09'),
(334, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Edrian A. Rocaberte · Chairperson', 'officer', 15, NULL, '{\"portal_code\":\"SLUC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '01992455739e5a36e32841ec55b5c8f3321e987d04e3c5240c9ae9db84a67758', '1373bf7c45240b838f254ef59f7512fbb836ad443e6af51212af694affe50b95', '2026-08-20 03:02:03'),
(335, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Hermes J. Mendoza · Vice Chairperson', 'officer', 16, NULL, '{\"portal_code\":\"SLUC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '1373bf7c45240b838f254ef59f7512fbb836ad443e6af51212af694affe50b95', '406ca363c29f463ca92956da29ffc8034c9e687da56ca1b69419d5e4283c6115', '2026-08-20 03:02:22'),
(336, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Shannen C. Villacorte · Chairperson', 'officer', 17, NULL, '{\"portal_code\":\"MLUC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '406ca363c29f463ca92956da29ffc8034c9e687da56ca1b69419d5e4283c6115', 'e146851664302edd977061c291e0bac3db503490b06349f96f3c5cf129c8a9fe', '2026-08-20 03:04:56'),
(337, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Jeremiah O. Hipol · Vice Chairperson', 'officer', 18, NULL, '{\"portal_code\":\"MLUC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'e146851664302edd977061c291e0bac3db503490b06349f96f3c5cf129c8a9fe', '8213aea29ff8fc7d682830ba652566f549dfb4aeda1032d776beb64374a6d803', '2026-08-20 03:05:12'),
(338, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Harold D. Valdez · Chairperson', 'officer', 19, NULL, '{\"portal_code\":\"NLUC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '8213aea29ff8fc7d682830ba652566f549dfb4aeda1032d776beb64374a6d803', '620e4009f8265edbff6a326945fa8e95237e0ec3376a9813ed1a4824f137323f', '2026-08-20 03:05:44'),
(339, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Trisha Mae I. Murillo · Vice Chairperson', 'officer', 20, NULL, '{\"portal_code\":\"NLUC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '620e4009f8265edbff6a326945fa8e95237e0ec3376a9813ed1a4824f137323f', '65bbde4680395f068efa237da3f2cf3d7e0ad07513d078e4e270e0aca2dc717a', '2026-08-20 03:06:16'),
(340, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Jomerson B. Celeste · Chairperson', 'officer', 21, NULL, '{\"portal_code\":\"OUS\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '65bbde4680395f068efa237da3f2cf3d7e0ad07513d078e4e270e0aca2dc717a', '70ded2e44ccdad91095e9becfefb8c9c71b9bcd26647f40fae6cb60e3c66d2a2', '2026-08-20 03:06:40'),
(341, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'Jenelyn R. Saltiban · Vice Chairperson', 'officer', 22, NULL, '{\"portal_code\":\"OUS\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '70ded2e44ccdad91095e9becfefb8c9c71b9bcd26647f40fae6cb60e3c66d2a2', '01291086a4878254996fe4541ed505d9b4249c3b297bc3dbaf11af14a73f33ca', '2026-08-20 03:06:59'),
(342, 1, 'System Administrator', 'admin', NULL, 'governance', 'Created academic year', '2027-2028', 'academic_year', 3, NULL, '{\"label\":\"2027-2028\",\"start_date\":\"2027-06-11\",\"end_date\":\"2028-05-31\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '01291086a4878254996fe4541ed505d9b4249c3b297bc3dbaf11af14a73f33ca', 'ea5155b561a1ffa271b4ae9c35d4b81a4656934c438465b85e537edaa06e4b79', '2026-08-20 03:22:13'),
(343, 1, 'System Administrator', 'admin', NULL, 'governance', 'Changed active academic year', '2027-2028', 'academic_year', 3, NULL, '{\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'ea5155b561a1ffa271b4ae9c35d4b81a4656934c438465b85e537edaa06e4b79', '42ae2706803b0b8a67a6b807112a3363748a3e7cf74972ee206a7fd2220b3aab', '2026-08-20 03:22:38'),
(344, 1, 'System Administrator', 'admin', NULL, 'governance', 'Changed active academic year', '2026-2027', 'academic_year', 1, NULL, '{\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '42ae2706803b0b8a67a6b807112a3363748a3e7cf74972ee206a7fd2220b3aab', 'bdf6dd1181c80c9286b25416a076d86cf79658454f51d0143f0d9bafa81307b4', '2026-08-20 03:23:17'),
(345, 1, 'System Administrator', 'admin', NULL, 'governance', 'Archived officer record', 'George Rexy Vincent Bacani · Audit Commissioner', 'officer', 13, '{\"is_archived\":0}', '{\"is_archived\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'bdf6dd1181c80c9286b25416a076d86cf79658454f51d0143f0d9bafa81307b4', 'dd93710f38a4119211040683814709d5ab68dce934f42b83ad4c6c3b56e8ae18', '2026-08-20 03:37:59'),
(346, 1, 'System Administrator', 'admin', NULL, 'governance', 'Added officer record', 'George Rexy Vincent Bacani · Audit Commissioner', 'officer', 23, NULL, '{\"portal_code\":\"USC\",\"academic_year_id\":1,\"photo_added\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'dd93710f38a4119211040683814709d5ab68dce934f42b83ad4c6c3b56e8ae18', '1fb0a59513fcec815dbf5d8dacb312e6de7c0d1e9f65223d33466cc567362ae9', '2026-08-20 03:45:06');
INSERT INTO `admin_activity_logs` (`id`, `admin_id`, `admin_name`, `role`, `campus`, `module`, `action`, `description`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `prev_hash`, `record_hash`, `created_at`) VALUES
(347, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'Michael Dean Malonzo · President', 'officer', 1, '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\"}', '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\",\"photo_updated\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '1fb0a59513fcec815dbf5d8dacb312e6de7c0d1e9f65223d33466cc567362ae9', '6d17860c6a2528c279195b05eaa259eb5137c8dc88164a15bc8428d2ac24c219', '2026-08-20 03:47:26'),
(348, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'Michael Dean Malonzo · President', 'officer', 1, '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\"}', '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\",\"photo_updated\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '6d17860c6a2528c279195b05eaa259eb5137c8dc88164a15bc8428d2ac24c219', 'b85c6a49cdbec9fa1e9f30bdc10bde528642739ef3dd6de32bdf7d03c6894e6a', '2026-08-20 03:49:06'),
(349, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'Michael Dean Malonzo · President', 'officer', 1, '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\"}', '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\",\"photo_updated\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'b85c6a49cdbec9fa1e9f30bdc10bde528642739ef3dd6de32bdf7d03c6894e6a', '8454af5f92f740ac25cb216adb1fabdf9e80715e7170ee88c075abecbea4736d', '2026-08-20 03:49:20'),
(350, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'Michael Dean Malonzo · President', 'officer', 1, '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\"}', '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\",\"photo_updated\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '8454af5f92f740ac25cb216adb1fabdf9e80715e7170ee88c075abecbea4736d', 'ec4c0ffc67e4d0abd9b41325d5b2bf6c2a6b5c66d642c0e95375dde93998ed24', '2026-08-20 03:53:03'),
(351, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'Michael Dean Malonzo · President', 'officer', 1, '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\"}', '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\",\"photo_updated\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'ec4c0ffc67e4d0abd9b41325d5b2bf6c2a6b5c66d642c0e95375dde93998ed24', '3394fd9f970a178f7eb755ce53596075a5223b426db83f0103e81f2037dd9301', '2026-08-20 03:53:12'),
(352, 1, 'System Administrator', 'admin', NULL, 'governance', 'Updated officer record', 'Michael Dean Malonzo · President', 'officer', 1, '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\"}', '{\"full_name\":\"Michael Dean Malonzo\",\"position_title\":\"President\",\"portal_code\":\"USC\",\"photo_updated\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '3394fd9f970a178f7eb755ce53596075a5223b426db83f0103e81f2037dd9301', '9d23e7ba1cf91c931cce13217417b5a821cadee31405ada0759862e285d6998f', '2026-08-20 03:53:20'),
(353, 1, 'System Administrator', 'admin', NULL, 'governance', 'Restored academic year', 'Legacy / Imported', 'academic_year', 2, '{\"is_archived\":1}', '{\"is_archived\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '9d23e7ba1cf91c931cce13217417b5a821cadee31405ada0759862e285d6998f', '64ee66e42ca5b985f40963932182fde51ebccfd78cdfc85cc9adaa5da94b7d63', '2026-08-20 04:09:04'),
(354, 1, 'System Administrator', 'admin', NULL, 'governance', 'Archived academic year', 'Legacy / Imported', 'academic_year', 2, '{\"is_archived\":0}', '{\"is_archived\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '64ee66e42ca5b985f40963932182fde51ebccfd78cdfc85cc9adaa5da94b7d63', 'e02391b86e330d4c6f7b66e8cd63e103bb892859fdb616b8825b26dd5763a3a4', '2026-08-20 04:09:09'),
(355, 1, 'System Administrator', 'admin', NULL, 'governance', 'Restored academic year', 'Legacy / Imported', 'academic_year', 2, '{\"is_archived\":1}', '{\"is_archived\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'e02391b86e330d4c6f7b66e8cd63e103bb892859fdb616b8825b26dd5763a3a4', '5eee388b33d9e3b9e346a5bc34bfcc16c763c4bc03fdf56949a460aeace304d2', '2026-08-20 04:12:13'),
(356, 1, 'System Administrator', 'admin', NULL, 'governance', 'Archived academic year', 'Legacy / Imported', 'academic_year', 2, '{\"is_archived\":0}', '{\"is_archived\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '5eee388b33d9e3b9e346a5bc34bfcc16c763c4bc03fdf56949a460aeace304d2', '9ace4bd304d0a280d74ae695be40d53b97537edaeba868bc1ac9beb5e3439d08', '2026-08-20 04:12:16'),
(357, 1, 'System Administrator', 'admin', NULL, 'governance', 'Archived academic year', '2027-2028', 'academic_year', 3, '{\"is_archived\":0}', '{\"is_archived\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '9ace4bd304d0a280d74ae695be40d53b97537edaeba868bc1ac9beb5e3439d08', '01d2b75e53202c04a42acbb8798f6fd086d61b6a1f36f199d29f001598603436', '2026-08-20 04:12:29'),
(358, 1, 'System Administrator', 'admin', NULL, 'governance', 'Restored academic year', '2027-2028', 'academic_year', 3, '{\"is_archived\":1}', '{\"is_archived\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '01d2b75e53202c04a42acbb8798f6fd086d61b6a1f36f199d29f001598603436', 'ebdf5674031190b7eec708c52edfcd4b0c8f6ec354c63b26b4eaf49fdb6cffd0', '2026-08-20 04:12:43'),
(359, 1, 'System Administrator', 'admin', NULL, 'governance', 'Changed active academic year', '2027-2028', 'academic_year', 3, NULL, '{\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'ebdf5674031190b7eec708c52edfcd4b0c8f6ec354c63b26b4eaf49fdb6cffd0', '19f666b62f4c7b9f7416b688327f2b90aaf1944b82425e97524832bdfc05faf7', '2026-08-20 04:12:47'),
(360, 1, 'System Administrator', 'admin', NULL, 'governance', 'Archived academic year', '2026-2027', 'academic_year', 1, '{\"is_archived\":0}', '{\"is_archived\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '19f666b62f4c7b9f7416b688327f2b90aaf1944b82425e97524832bdfc05faf7', '2fada5eb263074322afd0b11cca641aece0bb8d10cb7c5eb59109e91eb3a02b5', '2026-08-20 04:12:50'),
(361, 1, 'System Administrator', 'admin', NULL, 'governance', 'Restored academic year', '2026-2027', 'academic_year', 1, '{\"is_archived\":1}', '{\"is_archived\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2fada5eb263074322afd0b11cca641aece0bb8d10cb7c5eb59109e91eb3a02b5', '89642530847722d4aed7c784aa367a8b0873607931315f6bbabecff44d72e04b', '2026-08-20 04:13:07'),
(362, 1, 'System Administrator', 'admin', NULL, 'governance', 'Changed active academic year', '2026-2027', 'academic_year', 1, NULL, '{\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '89642530847722d4aed7c784aa367a8b0873607931315f6bbabecff44d72e04b', '11eb83e745a74867fd24d0f4451482cf72519ca126def0e0a91e231200acad19', '2026-08-20 04:13:09'),
(363, 1, 'System Administrator', 'admin', NULL, 'governance', 'Restored academic year', 'Legacy / Imported', 'academic_year', 2, '{\"is_archived\":1}', '{\"is_archived\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '11eb83e745a74867fd24d0f4451482cf72519ca126def0e0a91e231200acad19', 'fb119e5f798593b44e00f1105a29f7d8db20318ca597ad69987af81da55b7501', '2026-08-20 04:13:33'),
(364, 1, 'System Administrator', 'admin', NULL, 'governance', 'Changed active academic year', 'Legacy / Imported', 'academic_year', 2, NULL, '{\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'fb119e5f798593b44e00f1105a29f7d8db20318ca597ad69987af81da55b7501', 'c1d64d42a21e28694288d6b026bc9d369f9d7643117c69c046c8b7c6cfdd9e83', '2026-08-20 04:13:42'),
(365, 1, 'System Administrator', 'admin', NULL, 'governance', 'Changed active academic year', '2026-2027', 'academic_year', 1, NULL, '{\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'c1d64d42a21e28694288d6b026bc9d369f9d7643117c69c046c8b7c6cfdd9e83', '83eceb76b1eca2b1ffffa4be26bb56f5d6fc517ea3a9472992875231e17597e8', '2026-08-20 04:13:46'),
(366, 1, 'System Administrator', 'admin', NULL, 'governance', 'Archived academic year', 'Legacy / Imported', 'academic_year', 2, '{\"is_archived\":0}', '{\"is_archived\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '83eceb76b1eca2b1ffffa4be26bb56f5d6fc517ea3a9472992875231e17597e8', 'bbbb6393fa31dc17cf188fcb2f5f5bbc4c6f08fe31c5b09a4508b9ae6032802e', '2026-08-20 04:13:48'),
(367, 1, 'System Administrator', 'admin', NULL, 'governance', 'Archived academic year', '2027-2028', 'academic_year', 3, '{\"is_archived\":0}', '{\"is_archived\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'bbbb6393fa31dc17cf188fcb2f5f5bbc4c6f08fe31c5b09a4508b9ae6032802e', '7cf6a13b9f10a5d2260a9887cf09b9eae6ca1a83cc002ac8f5a8a45b661c096d', '2026-08-20 04:15:51'),
(368, 1, 'System Administrator', 'admin', NULL, 'governance', 'Restored academic year', '2027-2028', 'academic_year', 3, '{\"is_archived\":1}', '{\"is_archived\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '7cf6a13b9f10a5d2260a9887cf09b9eae6ca1a83cc002ac8f5a8a45b661c096d', 'cff1deb059710f59236ab645ac5a23f8246636ee8e15efa0c524e79c63fb647f', '2026-08-20 04:16:00'),
(369, 1, 'System Administrator', 'admin', NULL, 'news', 'Saved publication', '𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋 · published', 'post', 11, '{\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"status\":\"published\",\"portal\":\"USC\"}', '{\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"status\":\"published\",\"portal\":\"USC\",\"scheduled_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'cff1deb059710f59236ab645ac5a23f8246636ee8e15efa0c524e79c63fb647f', 'cfe532dbc35691e4178baceee19b6e16caa005d82e1fd22b952f622d9f114459', '2026-08-20 05:04:08'),
(370, 1, 'System Administrator', 'admin', NULL, 'news', 'Saved publication', '𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋 · published', 'post', 11, '{\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"status\":\"published\",\"portal\":\"USC\"}', '{\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"status\":\"published\",\"portal\":\"USC\",\"scheduled_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'cfe532dbc35691e4178baceee19b6e16caa005d82e1fd22b952f622d9f114459', 'b56d668719f46e6037ed1753ffab9698f5ce02a3d492cb21be7a9e9930eccacd', '2026-08-20 05:04:39'),
(371, 1, 'System Administrator', 'admin', NULL, 'news', 'Saved publication', '𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋 · published', 'post', 11, '{\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"status\":\"published\",\"portal\":\"USC\"}', '{\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"status\":\"published\",\"portal\":\"USC\",\"scheduled_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'b56d668719f46e6037ed1753ffab9698f5ce02a3d492cb21be7a9e9930eccacd', 'c980cb811f0c287c2481e9d325fa1b2a7c47592aef5d06a00da5bd4d23b42d6e', '2026-08-20 05:18:51'),
(372, 1, 'System Administrator', 'admin', NULL, 'news', 'Saved publication', '𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋 · published', 'post', 11, '{\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"status\":\"published\",\"portal\":\"USC\"}', '{\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"status\":\"published\",\"portal\":\"USC\",\"scheduled_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'c980cb811f0c287c2481e9d325fa1b2a7c47592aef5d06a00da5bd4d23b42d6e', 'dac79948dbbb6f9c0499b40da5b781b5e25d9a69d41ff525123be0da48997239', '2026-08-20 05:31:49'),
(373, 1, 'System Administrator', 'admin', NULL, 'news', 'Saved publication', '𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋 · published', 'post', 11, '{\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"status\":\"published\",\"portal\":\"USC\"}', '{\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"status\":\"published\",\"portal\":\"USC\",\"scheduled_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'dac79948dbbb6f9c0499b40da5b781b5e25d9a69d41ff525123be0da48997239', '57290a498286483b53e6ab187e7aa0f9cd2c8390b83aa8605062e62b95b48284', '2026-08-20 05:32:02'),
(374, 1, 'System Administrator', 'admin', NULL, 'news', 'Saved publication', '𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋 · published', 'post', 11, '{\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"status\":\"published\",\"portal\":\"USC\"}', '{\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"status\":\"published\",\"portal\":\"USC\",\"scheduled_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '57290a498286483b53e6ab187e7aa0f9cd2c8390b83aa8605062e62b95b48284', '1e231fa881bb48651d07182a3efe5880baca7e46fc9d6b4fecee8c323e4ec0ac', '2026-08-20 05:32:14'),
(375, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '1e231fa881bb48651d07182a3efe5880baca7e46fc9d6b4fecee8c323e4ec0ac', '7d5587ec31ca063fae0b9f12eaf397b6eedabd1df487eed0f09ad2b619690ef0', '2026-08-20 05:32:47'),
(376, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'news', 'Saved publication', '𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫 · review', 'post', 10, '{\"title\":\"𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫\",\"status\":\"published\",\"portal\":\"SLUC\"}', '{\"title\":\"𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫\",\"status\":\"review\",\"portal\":\"SLUC\",\"scheduled_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '7d5587ec31ca063fae0b9f12eaf397b6eedabd1df487eed0f09ad2b619690ef0', 'a3c9a4e26ecd010e962121f672786f2ac5121baa942bb92c5e8be1a77c29a657', '2026-08-20 05:33:11'),
(377, 1, 'System Administrator', 'admin', NULL, 'content', 'Created announcement', 'Fixx', 'announcement', 1, NULL, '{\"priority\":\"warning\",\"status\":\"active\",\"portal_code\":\"ALL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'a3c9a4e26ecd010e962121f672786f2ac5121baa942bb92c5e8be1a77c29a657', '583b8f371c1f3b4693feacb1c1736ec3955979899a4b9be56a0b609cb66453f6', '2026-08-20 05:35:09'),
(378, 1, 'System Administrator', 'admin', NULL, 'content', 'Updated announcement', 'Fixx', 'announcement', 1, '{\"title\":\"Fixx\",\"priority\":\"warning\",\"status\":\"active\"}', '{\"title\":\"Fixx\",\"priority\":\"warning\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '583b8f371c1f3b4693feacb1c1736ec3955979899a4b9be56a0b609cb66453f6', 'ca1b5d93c0c67f050c8683d2b78f63589fb29863effa6438b18af0ca5b8c3c11', '2026-08-20 05:48:27'),
(379, 1, 'System Administrator', 'admin', NULL, 'content', 'Updated announcement', 'Fixx', 'announcement', 1, '{\"title\":\"Fixx\",\"priority\":\"warning\",\"status\":\"active\"}', '{\"title\":\"Fixx\",\"priority\":\"warning\",\"status\":\"draft\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'ca1b5d93c0c67f050c8683d2b78f63589fb29863effa6438b18af0ca5b8c3c11', 'e322aa8a631837a267e2f37a0909ae243081e99edc75e4fc1adb5670470c2a6a', '2026-08-20 05:53:53'),
(380, 1, 'System Administrator', 'admin', NULL, 'content', 'Updated announcement', 'Fixx', 'announcement', 1, '{\"title\":\"Fixx\",\"priority\":\"warning\",\"status\":\"draft\"}', '{\"title\":\"Fixx\",\"priority\":\"warning\",\"status\":\"draft\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'e322aa8a631837a267e2f37a0909ae243081e99edc75e4fc1adb5670470c2a6a', '09a7fc3e8c0849b9c90e55700f8829aace05d198664430c181e5ce8e568c5bc4', '2026-08-20 05:56:10'),
(381, 1, 'System Administrator', 'admin', NULL, 'content', 'Updated announcement', 'Fixx', 'announcement', 1, '{\"title\":\"Fixx\",\"priority\":\"warning\",\"status\":\"draft\"}', '{\"title\":\"Fixx\",\"priority\":\"warning\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '09a7fc3e8c0849b9c90e55700f8829aace05d198664430c181e5ce8e568c5bc4', '60bdc15f54486b55255021b3b672e7273fb9a5ee6b2dc8864bf04a65069ff556', '2026-08-20 05:56:27'),
(382, 1, 'System Administrator', 'admin', NULL, 'content', 'Updated announcement', 'Announcement', 'announcement', 1, '{\"title\":\"Fixx\",\"priority\":\"warning\",\"status\":\"active\"}', '{\"title\":\"Announcement\",\"priority\":\"info\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '60bdc15f54486b55255021b3b672e7273fb9a5ee6b2dc8864bf04a65069ff556', '74df0235680bec406e2d8818e9b30fbdc542cc2b71f49d1ba2b2322139896c73', '2026-08-20 05:56:48'),
(383, 1, 'System Administrator', 'admin', NULL, 'content', 'Updated announcement', 'Announcement', 'announcement', 1, '{\"title\":\"Announcement\",\"priority\":\"info\",\"status\":\"active\"}', '{\"title\":\"Announcement\",\"priority\":\"emergency\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '74df0235680bec406e2d8818e9b30fbdc542cc2b71f49d1ba2b2322139896c73', '37591a2119b0b6fc99a6da6b81fe3859715213efb97d29d0d2d18a1b0d20ad39', '2026-08-20 05:57:28'),
(384, 1, 'System Administrator', 'admin', NULL, 'content', 'Updated announcement', 'Announcement', 'announcement', 1, '{\"title\":\"Announcement\",\"priority\":\"emergency\",\"status\":\"active\"}', '{\"title\":\"Announcement\",\"priority\":\"emergency\",\"status\":\"draft\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '37591a2119b0b6fc99a6da6b81fe3859715213efb97d29d0d2d18a1b0d20ad39', '4c84d26ac67369d09a44851777ceca6aacfe239435f6564c24ba4139cd38ff8b', '2026-08-20 05:58:13'),
(385, 1, 'System Administrator', 'admin', NULL, 'content', 'Updated announcement', 'Announcement', 'announcement', 1, '{\"title\":\"Announcement\",\"priority\":\"emergency\",\"status\":\"draft\"}', '{\"title\":\"Announcement\",\"priority\":\"emergency\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '4c84d26ac67369d09a44851777ceca6aacfe239435f6564c24ba4139cd38ff8b', '86bdb5ef00c8b27901d1ee75b73802f241e45404879cb0279bffcdfd79030e93', '2026-08-20 05:59:14'),
(386, 1, 'System Administrator', 'admin', NULL, 'content', 'Updated announcement', 'Scheduled System Maintenance', 'announcement', 1, '{\"title\":\"Announcement\",\"priority\":\"emergency\",\"status\":\"active\"}', '{\"title\":\"Scheduled System Maintenance\",\"priority\":\"info\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '86bdb5ef00c8b27901d1ee75b73802f241e45404879cb0279bffcdfd79030e93', '60812cba947a27a4fb130372cbf2e0879d1ea04ae2fa4b00e9f171d444e70e7c', '2026-08-20 06:01:18'),
(387, 1, 'System Administrator', 'admin', NULL, 'content', 'Updated announcement', 'Scheduled System Maintenance', 'announcement', 1, '{\"title\":\"Scheduled System Maintenance\",\"priority\":\"info\",\"status\":\"active\"}', '{\"title\":\"Scheduled System Maintenance\",\"priority\":\"info\",\"status\":\"draft\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '60812cba947a27a4fb130372cbf2e0879d1ea04ae2fa4b00e9f171d444e70e7c', '82dd56cf001b17f4544d301f607c9536af56290045adf3124c1cfdcbf010bfab', '2026-08-20 06:02:15'),
(388, 1, 'System Administrator', 'admin', NULL, 'content', 'Created announcement', 'Watch', 'announcement', 2, NULL, '{\"priority\":\"info\",\"status\":\"active\",\"portal_code\":\"ALL\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '82dd56cf001b17f4544d301f607c9536af56290045adf3124c1cfdcbf010bfab', '151f2f7a443dd7c3a6929c9f73f790bfa53f0c13da25685b9fc8584d947aaca3', '2026-08-20 06:03:36'),
(389, 1, 'System Administrator', 'admin', NULL, 'content', 'Updated announcement', 'Click here to proceed and learn more', 'announcement', 2, '{\"title\":\"Watch\",\"priority\":\"info\",\"status\":\"active\"}', '{\"title\":\"Click here to proceed and learn more\",\"priority\":\"info\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '151f2f7a443dd7c3a6929c9f73f790bfa53f0c13da25685b9fc8584d947aaca3', '819fc76cc9f0d33b02e2bd8fff9c767c757ce06840b36f0c8af9de3d752a908e', '2026-08-20 06:07:55'),
(390, 1, 'System Administrator', 'admin', NULL, 'content', 'Updated announcement', 'Click here to proceed and learn more', 'announcement', 2, '{\"title\":\"Click here to proceed and learn more\",\"priority\":\"info\",\"status\":\"active\"}', '{\"title\":\"Click here to proceed and learn more\",\"priority\":\"info\",\"status\":\"draft\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '819fc76cc9f0d33b02e2bd8fff9c767c757ce06840b36f0c8af9de3d752a908e', '09efaf1918d6b0c86efb3c0a730802227b046dc21ff198f284c342a05d478eab', '2026-08-20 06:08:36'),
(391, 1, 'System Administrator', 'admin', NULL, 'media', 'Moved media to trash', '774868512_1535642465242219_9039202900835858923_n (1).jpg', 'media', 28, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '09efaf1918d6b0c86efb3c0a730802227b046dc21ff198f284c342a05d478eab', 'b6d6b45fe197edb7c12c453eee7504d9f3ea9c93bbe581305c3d4965008bd9bb', '2026-08-20 06:26:59'),
(392, 1, 'System Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Operational Plan.pptx', 'media', 27, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'b6d6b45fe197edb7c12c453eee7504d9f3ea9c93bbe581305c3d4965008bd9bb', '3b1d69df031d2222bf83b6db4b4434dce2790f1e4f65fa91ffd02e57e34ef910', '2026-08-20 06:27:02'),
(393, 1, 'System Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Git Basic Operation Guide (1).pdf', 'media', 26, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '3b1d69df031d2222bf83b6db4b4434dce2790f1e4f65fa91ffd02e57e34ef910', 'ffbd8579e222256779c44fb2f2068abc5273c3141a66335387786218b3e1721b', '2026-08-20 06:27:04'),
(394, 1, 'System Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'Untitled (1).png', 'media', 25, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'ffbd8579e222256779c44fb2f2068abc5273c3141a66335387786218b3e1721b', '60ac3471f0a4d9624140461a794cf383272b470c2b90251f4a6938ccc2258007', '2026-08-20 06:27:07'),
(395, 1, 'System Administrator', 'admin', NULL, 'media', 'Uploaded media', 'a5911c34-507c-45eb-8877-7a1ae4b4e44a.jpg · SHA-256 0e24792e4759', 'media', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '60ac3471f0a4d9624140461a794cf383272b470c2b90251f4a6938ccc2258007', '9896567fadb08a6caa0843c975fb1a05efe9fc99da824e4fdf2da3f239a0e197', '2026-08-20 06:27:11'),
(396, 1, 'System Administrator', 'admin', NULL, 'media', 'Uploaded media', '8e4c2001-f9fc-4449-bf9b-4de2ae840359.jpg · SHA-256 1f9b46f12651', 'media', 31, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '9896567fadb08a6caa0843c975fb1a05efe9fc99da824e4fdf2da3f239a0e197', '5db5836fce5a8dafb8c70bf579d85fcf38ef6d44754c9ba6d3eeea1e45e0c8cc', '2026-08-20 06:27:15'),
(397, 1, 'System Administrator', 'admin', NULL, 'media', 'Uploaded media', '302618321_471782971628179_3505756978854698203_n.jpg · SHA-256 d78c69662829', 'media', 32, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '5db5836fce5a8dafb8c70bf579d85fcf38ef6d44754c9ba6d3eeea1e45e0c8cc', '1b5d2bce195353ba2fcaf5f5c96b6b871f9ed1350c75d7a75730892b290326d6', '2026-08-20 06:27:19'),
(398, 1, 'System Administrator', 'admin', NULL, 'media', 'Uploaded media', '532792c7-4f96-4868-a6d8-0d643428ba06.jpg · SHA-256 53e05ec67f56', 'media', 33, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '1b5d2bce195353ba2fcaf5f5c96b6b871f9ed1350c75d7a75730892b290326d6', 'b27ae957ec8de572fcf291091e5ecaca7065098988eec325f60d56d7fea24fc2', '2026-08-20 06:27:22'),
(399, 1, 'System Administrator', 'admin', NULL, 'media', 'Uploaded media', '02ad437f-d407-4052-9bc4-498864a4a3c5.jpg · SHA-256 7ec9e48fd2ce', 'media', 34, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'b27ae957ec8de572fcf291091e5ecaca7065098988eec325f60d56d7fea24fc2', 'a5ecccc3bfe6735cc74779d88aad6fa721a7b03d3a6d99a175cb996fa37238a9', '2026-08-20 06:27:25'),
(400, 1, 'System Administrator', 'admin', NULL, 'media', 'Uploaded media', 'usc.png · SHA-256 68a280056e95', 'media', 35, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'a5ecccc3bfe6735cc74779d88aad6fa721a7b03d3a6d99a175cb996fa37238a9', '5ee4b3277ea07a791c531b04dbe8571d11f1db6431fcbe743413ff145beb4fe8', '2026-08-20 06:27:29'),
(401, 1, 'System Administrator', 'admin', NULL, 'media', 'Verified media asset', '532792c7-4f96-4868-a6d8-0d643428ba06.jpg · file present', 'media', 33, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '5ee4b3277ea07a791c531b04dbe8571d11f1db6431fcbe743413ff145beb4fe8', '381842094c13b9f6bfee3562fd583effbc3325e9e924abf869806fce79fd3a7d', '2026-08-20 06:27:36'),
(402, 1, 'System Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'usc.png', 'media', 35, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '381842094c13b9f6bfee3562fd583effbc3325e9e924abf869806fce79fd3a7d', '9c2ab5ca6ad0407a3b9448c3ea1b987558f18a5d62692aa2b803b8cb90127e81', '2026-08-20 06:28:59'),
(403, 1, 'System Administrator', 'admin', NULL, 'media', 'Moved media to trash', '02ad437f-d407-4052-9bc4-498864a4a3c5.jpg', 'media', 34, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '9c2ab5ca6ad0407a3b9448c3ea1b987558f18a5d62692aa2b803b8cb90127e81', '00c7f163c397b9717ee4877570220262065fc9bea035e6f7ea6f0b730a00b9ae', '2026-08-20 06:29:01'),
(404, 1, 'System Administrator', 'admin', NULL, 'media', 'Moved media to trash', '532792c7-4f96-4868-a6d8-0d643428ba06.jpg', 'media', 33, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '00c7f163c397b9717ee4877570220262065fc9bea035e6f7ea6f0b730a00b9ae', 'b8e5c1cc4a96f565f1e4ceabf3577dd1c5538cb2fb2485fc26e13ae281c8f3e2', '2026-08-20 06:29:03'),
(405, 1, 'System Administrator', 'admin', NULL, 'security', 'Verified audit integrity', '404 audit records verified.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'b8e5c1cc4a96f565f1e4ceabf3577dd1c5538cb2fb2485fc26e13ae281c8f3e2', 'efd679b62f18dd9473d8c2eb58e275ad8e2fbfc296eacac5a7f7f15044d0a547', '2026-08-20 08:26:21'),
(406, 1, 'System Administrator', 'admin', NULL, 'media', 'Optimized media assets', '0 image(s) reduced; 0 thumbnail(s) generated', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'efd679b62f18dd9473d8c2eb58e275ad8e2fbfc296eacac5a7f7f15044d0a547', '2de9a9474ccc0e916a8e75b968435759fcc982f74cbcf73cb818dd142b1cebdd', '2026-08-20 08:36:19'),
(407, 1, 'System Administrator', 'admin', NULL, 'security', 'Verified audit integrity', '406 audit records verified.', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2de9a9474ccc0e916a8e75b968435759fcc982f74cbcf73cb818dd142b1cebdd', 'c07e710ab63472df745204816be691845b4d0e22f249b8c37c4775445eb1b9b8', '2026-08-20 08:36:34'),
(408, 1, 'System Administrator', 'admin', NULL, 'media', 'Moved media to trash', '302618321_471782971628179_3505756978854698203_n.jpg', 'media', 32, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'c07e710ab63472df745204816be691845b4d0e22f249b8c37c4775445eb1b9b8', 'ed18152397072c90af3fd2fee4a2983478b66adbb8f5a9399f385b290db00652', '2026-08-20 08:37:45'),
(409, 1, 'System Administrator', 'admin', NULL, 'media', 'Moved media to trash', '8e4c2001-f9fc-4449-bf9b-4de2ae840359.jpg', 'media', 31, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'ed18152397072c90af3fd2fee4a2983478b66adbb8f5a9399f385b290db00652', '78c55c00f0175f43b748b7466d3add118868210af3c59f22adc0965856daf467', '2026-08-20 08:37:47'),
(410, 1, 'System Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'a5911c34-507c-45eb-8877-7a1ae4b4e44a.jpg', 'media', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '78c55c00f0175f43b748b7466d3add118868210af3c59f22adc0965856daf467', '9acb8fa89a487eefc4484f3bc5c333f1bf84d4885d9bcb8bae68c7653b0e99e9', '2026-08-20 08:37:49'),
(411, 1, 'System Administrator', 'admin', NULL, 'media', 'Moved media to trash', 'usc.png', 'media', 29, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '9acb8fa89a487eefc4484f3bc5c333f1bf84d4885d9bcb8bae68c7653b0e99e9', '247378a784e8ad596a34f6efb77728a06cfbefa0ebc7ec80dd15dd285c421890', '2026-08-20 08:37:53'),
(412, 1, 'System Administrator', 'admin', NULL, 'privacy', 'Reviewed retention status', 'Sensitive E-Sumbong identity access recorded', 'concern', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '247378a784e8ad596a34f6efb77728a06cfbefa0ebc7ec80dd15dd285c421890', '6ec15db5585ecc052b265e03f1ec404b46e4ea32c062aa8a641ccc762c0748bf', '2026-08-20 09:07:08'),
(413, 1, 'System Administrator', 'admin', NULL, 'privacy', 'Reviewed E-Sumbong retention', 'ES-2026-264015 · reviewed-retain', 'concern', 1, '{\"privacy_review_status\":\"pending\"}', '{\"privacy_review_status\":\"reviewed-retain\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '6ec15db5585ecc052b265e03f1ec404b46e4ea32c062aa8a641ccc762c0748bf', '6ef359c5955dc71bbe3e569fd7a679f73a94ea12c1ba0fb5f2d1d7381169d5a0', '2026-08-20 09:07:08'),
(414, 1, 'System Administrator', 'admin', NULL, 'privacy', 'Viewed student identity', 'Sensitive E-Sumbong identity access recorded', 'concern', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '6ef359c5955dc71bbe3e569fd7a679f73a94ea12c1ba0fb5f2d1d7381169d5a0', '7342f3ac4007abe47486c11a05678c9c0b6ee0e594f1b23ed09f4fc94253d2af', '2026-08-20 09:07:30'),
(415, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'auth', 'Signed out', 'Administrator signed out', 'admin', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '7342f3ac4007abe47486c11a05678c9c0b6ee0e594f1b23ed09f4fc94253d2af', '6c9cc8de60e45b63460c3b92269ad905921d2720c48c26a4228e96cdb14d6adc', '2026-08-20 09:25:59'),
(416, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '6c9cc8de60e45b63460c3b92269ad905921d2720c48c26a4228e96cdb14d6adc', 'c8692c751e4efc9b8ad3792741caa04ec70b59344e638e7ae5f385cb5e2e1f87', '2026-08-20 09:26:15'),
(417, 1, 'System Administrator', 'admin', NULL, 'settings', 'Updated system settings', 'Updated global portal and security configuration', NULL, NULL, '{\"portal_name\":\"University Student Council\",\"university_name\":\"Don Mariano Marcos Memorial State University\",\"usc_email\":\"usc@dmmmsu.edu.ph\",\"usc_facebook\":\"https://www.facebook.com/usc.dmmmsu\",\"maintenance_mode\":\"0\",\"default_upload_limit\":\"10\",\"login_max_attempts\":\"5\",\"login_lockout_minutes\":\"5\",\"session_idle_hours\":\"12\",\"password_max_age_days\":\"180\",\"dormant_account_days\":\"90\",\"security_require_2fa_for_admin\":\"0\",\"security_2fa_required_roles\":\"admin\",\"password_history_count\":\"5\",\"esumbong_sla_urgent_hours\":\"24\",\"esumbong_sla_high_hours\":\"48\",\"esumbong_sla_normal_hours\":\"120\",\"esumbong_sla_low_hours\":\"168\",\"privacy_retention_days\":\"730\",\"esumbong_attachment_max_files\":\"3\",\"esumbong_attachment_max_mb\":\"5\",\"backup_reminder_days\":\"7\",\"auto_publish_scheduled\":\"1\",\"auto_backup_enabled\":\"0\",\"auto_backup_type\":\"database\",\"auto_backup_interval_hours\":\"24\",\"auto_backup_retention_count\":\"14\",\"notification_email_enabled\":\"0\",\"esumbong_email_updates_enabled\":\"1\",\"email_from_name\":\"DMMMSU USC\",\"email_from_address\":\"usc@dmmmsu.edu.ph\",\"notification_retention_days\":\"180\",\"storage_warning_percent\":\"85\",\"storage_critical_percent\":\"95\",\"offsite_backup_enabled\":\"0\",\"offsite_backup_path\":\"\",\"media_auto_optimize\":\"1\",\"media_thumbnail_enabled\":\"1\",\"security_rate_limit_tracking_per_hour\":\"30\",\"security_rate_limit_uploads_per_hour\":\"60\"}', '{\"portal_name\":\"University Student Council\",\"university_name\":\"Don Mariano Marcos Memorial State University\",\"usc_email\":\"usc@dmmmsu.edu.ph\",\"usc_facebook\":\"https://www.facebook.com/usc.dmmmsu\",\"maintenance_mode\":\"0\",\"default_upload_limit\":\"10\",\"login_max_attempts\":\"5\",\"login_lockout_minutes\":\"5\",\"session_idle_hours\":\"12\",\"password_max_age_days\":\"180\",\"dormant_account_days\":\"90\",\"security_require_2fa_for_admin\":\"0\",\"security_2fa_required_roles\":\"admin\",\"password_history_count\":\"5\",\"esumbong_sla_urgent_hours\":\"24\",\"esumbong_sla_high_hours\":\"48\",\"esumbong_sla_normal_hours\":\"120\",\"esumbong_sla_low_hours\":\"168\",\"privacy_retention_days\":\"730\",\"esumbong_attachment_max_files\":\"3\",\"esumbong_attachment_max_mb\":\"5\",\"backup_reminder_days\":\"7\",\"auto_publish_scheduled\":\"1\",\"auto_backup_enabled\":\"0\",\"auto_backup_type\":\"database\",\"auto_backup_interval_hours\":\"24\",\"auto_backup_retention_count\":\"14\",\"notification_email_enabled\":\"0\",\"esumbong_email_updates_enabled\":\"1\",\"email_from_name\":\"DMMMSU USC\",\"email_from_address\":\"usc@dmmmsu.edu.ph\",\"notification_retention_days\":\"180\",\"storage_warning_percent\":\"85\",\"storage_critical_percent\":\"95\",\"offsite_backup_enabled\":\"0\",\"offsite_backup_path\":\"\",\"media_auto_optimize\":\"1\",\"media_thumbnail_enabled\":\"1\",\"security_rate_limit_tracking_per_hour\":\"30\",\"security_rate_limit_uploads_per_hour\":\"60\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'c8692c751e4efc9b8ad3792741caa04ec70b59344e638e7ae5f385cb5e2e1f87', '3aa7dc818c652851de99d2ca08e5a3106cdee9529cafbd7b745d1eed467b7249', '2026-08-20 10:07:37'),
(418, 1, 'System Administrator', 'admin', NULL, 'accounts', 'Updated account', 'Director', 'admin', 3, '{\"full_name\":\"SAS Director\",\"username\":\"sasdirector\",\"email\":null,\"role\":\"sas_director\",\"campus\":null,\"status\":\"active\"}', '{\"full_name\":\"Director\",\"username\":\"sasdirector\",\"email\":null,\"role\":\"sas_director\",\"campus\":null,\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '3aa7dc818c652851de99d2ca08e5a3106cdee9529cafbd7b745d1eed467b7249', '563d1945ed20014441bfda07abef04b7fba3ccf2de753bd59c9edae56e5adeff', '2026-08-20 10:25:37'),
(419, 1, 'System Administrator', 'admin', NULL, 'accounts', 'Updated account', 'Mid La Union Campus', 'admin', 4, '{\"full_name\":\"Mid La Union Campus\",\"username\":\"mlucsashead\",\"email\":null,\"role\":\"campus_sas_head\",\"campus\":\"MLUC\",\"status\":\"active\"}', '{\"full_name\":\"Mid La Union Campus\",\"username\":\"mlucsashead\",\"email\":null,\"role\":\"campus_sas_head\",\"campus\":\"MLUC\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '563d1945ed20014441bfda07abef04b7fba3ccf2de753bd59c9edae56e5adeff', 'b8916269fcabd050418138d0feaaa01e3fc42b02c0cd7221d7616ff1fe0177ea', '2026-08-20 10:29:42'),
(420, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'news', 'Saved publication', '𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫 · published', 'post', 10, '{\"title\":\"𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫\",\"status\":\"review\",\"portal\":\"SLUC\"}', '{\"title\":\"𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫\",\"status\":\"published\",\"portal\":\"SLUC\",\"scheduled_at\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'b8916269fcabd050418138d0feaaa01e3fc42b02c0cd7221d7616ff1fe0177ea', '52b82117dd976a2eac292e4c28534d76f244e019a495dc3a8e10a838bcb08d61', '2026-08-20 10:38:08'),
(421, 1, 'System Administrator', 'admin', NULL, 'concerns', 'Created response template', 'sssd', 'response_template', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '52b82117dd976a2eac292e4c28534d76f244e019a495dc3a8e10a838bcb08d61', '60f0c5e89dfba2539edf7488774218d84ed4db2fffc6594c5dc30a48d6e9e52a', '2026-08-20 11:20:12'),
(422, 1, 'System Administrator', 'admin', NULL, 'concerns', 'Deactivated response template', 'sssd', 'response_template', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '60f0c5e89dfba2539edf7488774218d84ed4db2fffc6594c5dc30a48d6e9e52a', '15eb46651dc6be73555347dc5d36b5b7f707e648536fea338f421362848b2654', '2026-08-20 11:20:21'),
(423, 1, 'System Administrator', 'admin', NULL, 'privacy', 'Viewed student identity', 'Sensitive E-Sumbong identity access recorded', 'concern', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '15eb46651dc6be73555347dc5d36b5b7f707e648536fea338f421362848b2654', '49bb10fb5c420dd43326b51394e1c37545abe9f60cb22161b5f6187edff19db0', '2026-08-20 11:30:16'),
(424, 6, 'SLUC CSBO', 'campus_sbo', 'SLUC', 'privacy', 'Viewed student identity', 'Sensitive E-Sumbong identity access recorded', 'concern', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '49bb10fb5c420dd43326b51394e1c37545abe9f60cb22161b5f6187edff19db0', '68e4bf86c0c790bfcc69445c28c330e11caae95665afcb08fe16755f44505cbb', '2026-08-20 11:33:40'),
(425, 2, 'University Student Council', 'usc', NULL, 'auth', 'Signed in', 'Administrator signed in successfully', 'admin', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '68e4bf86c0c790bfcc69445c28c330e11caae95665afcb08fe16755f44505cbb', '0f3f83a817aa18076a343d55fbe9550da58bc69e914945d1012fedbf3a5f1115', '2026-08-20 11:41:27');

-- --------------------------------------------------------

--
-- Table structure for table `admin_dashboard_widgets`
--

CREATE TABLE `admin_dashboard_widgets` (
  `admin_id` int(11) NOT NULL,
  `widget_key` varchar(60) NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_login_attempts`
--

CREATE TABLE `admin_login_attempts` (
  `attempt_key` char(64) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `username` varchar(150) DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_login_events`
--

CREATE TABLE `admin_login_events` (
  `id` bigint(20) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `username` varchar(150) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `result` varchar(20) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `device_label` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_login_events`
--

INSERT INTO `admin_login_events` (`id`, `admin_id`, `username`, `event_type`, `result`, `reason`, `ip_address`, `user_agent`, `device_label`, `created_at`) VALUES
(1, 6, 'sluccsbo', 'login', 'success', 'Authenticated successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'Edge on Windows', '2026-08-20 05:32:47'),
(2, 6, 'SLUC CSBO', 'logout', 'success', 'Administrator signed out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'Edge on Windows', '2026-08-20 09:25:59'),
(3, 6, 'sluccsbo', 'login', 'success', 'Authenticated successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'Edge on Windows', '2026-08-20 09:26:15'),
(4, 2, 'usc', 'login', 'success', 'Authenticated successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'Chrome on Windows', '2026-08-20 11:41:27');

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` bigint(20) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `role_target` varchar(40) DEFAULT NULL,
  `campus_target` varchar(20) DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `message` varchar(500) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `kind` varchar(30) NOT NULL DEFAULT 'info',
  `category` varchar(40) NOT NULL DEFAULT 'general',
  `group_key` varchar(120) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `dismissed_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`id`, `admin_id`, `role_target`, `campus_target`, `title`, `message`, `link`, `kind`, `category`, `group_key`, `is_read`, `dismissed_at`, `expires_at`, `created_at`) VALUES
(1, NULL, NULL, NULL, 'Concern status changed', 'ES-2026-264015 is now Received', 'concerns.php?id=1', 'info', 'general', NULL, 0, NULL, NULL, '2026-08-18 07:09:14'),
(2, NULL, NULL, NULL, 'Concern status changed', 'ES-2026-264015 is now Submitted', 'concerns.php?id=1', 'info', 'general', NULL, 1, NULL, NULL, '2026-08-18 07:09:42'),
(3, NULL, 'usc', NULL, 'Homepage promotion request', 'Open University System requested USC homepage display for “Your campus voice deserves a clear channel.”.', 'hero.php?portal=USC#promotion-1', 'warning', 'general', NULL, 0, NULL, NULL, '2026-08-18 11:03:36'),
(4, NULL, 'admin', NULL, 'Homepage promotion request', 'Open University System requested USC homepage display for “Your campus voice deserves a clear channel.”.', 'hero.php?portal=USC#promotion-1', 'warning', 'general', NULL, 0, NULL, NULL, '2026-08-18 11:03:36'),
(5, NULL, 'usc', NULL, 'Homepage request withdrawn', 'Open University System withdrew a pending USC homepage promotion request.', 'hero.php?portal=USC', 'info', 'general', NULL, 0, NULL, NULL, '2026-08-18 11:04:58'),
(6, NULL, 'usc', NULL, 'Homepage promotion request', 'Open University System requested USC homepage display for “Your campus voice deserves a clear channel.”.', 'hero.php?portal=USC#promotion-2', 'warning', 'general', NULL, 0, NULL, NULL, '2026-08-18 11:11:48'),
(7, NULL, 'admin', NULL, 'Homepage promotion request', 'Open University System requested USC homepage display for “Your campus voice deserves a clear channel.”.', 'hero.php?portal=USC#promotion-2', 'warning', 'general', NULL, 0, NULL, NULL, '2026-08-18 11:11:48'),
(8, NULL, NULL, 'OUS', 'USC homepage request declined', 'USC declined the homepage promotion request for “Your campus voice deserves a clear channel.”.', 'hero.php?portal=OUS', 'danger', 'general', NULL, 0, NULL, NULL, '2026-08-18 11:12:06'),
(9, NULL, 'usc', NULL, 'Homepage promotion request', 'South La Union Campus requested USC homepage display for “Your campus voice deserves a clear channel.”.', 'hero.php?portal=USC&view=promotions#promotion-3', 'warning', 'general', NULL, 0, NULL, NULL, '2026-08-18 11:46:24'),
(10, NULL, 'admin', NULL, 'Homepage promotion request', 'South La Union Campus requested USC homepage display for “Your campus voice deserves a clear channel.”.', 'hero.php?portal=USC&view=promotions#promotion-3', 'warning', 'general', NULL, 0, NULL, NULL, '2026-08-18 11:46:24'),
(11, NULL, 'usc', NULL, 'Homepage request withdrawn', 'South La Union Campus withdrew a pending USC homepage promotion request.', 'hero.php?portal=USC&view=promotions', 'info', 'general', NULL, 0, NULL, NULL, '2026-08-18 11:46:52'),
(12, 11, NULL, NULL, 'Complete your account setup', 'Your account uses a temporary password. Create a new password after your first sign in.', 'profile.php?force_password=1', 'warning', 'security', NULL, 0, NULL, NULL, '2026-08-18 15:14:11'),
(13, 12, NULL, NULL, 'Complete your account setup', 'Your account uses a temporary password. Create a new password after your first sign in.', 'profile.php?force_password=1', 'warning', 'security', NULL, 0, NULL, NULL, '2026-08-18 15:14:57'),
(14, 13, NULL, NULL, 'Complete your account setup', 'Your account uses a temporary password. Create a new password after your first sign in.', 'profile.php?force_password=1', 'warning', 'security', NULL, 0, NULL, NULL, '2026-08-18 15:15:40'),
(15, NULL, NULL, 'MLUC', 'Concern referred to MLUC', 'ES-2026-264015 was routed from USC to MLUC', 'concerns.php?id=1', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 06:30:33'),
(16, NULL, 'admin', NULL, 'Concern routing changed', 'ES-2026-264015 was routed from USC to MLUC', 'concerns.php?id=1', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 06:30:33'),
(17, NULL, NULL, 'USC', 'Concern referred to USC', 'ES-2026-264015 was routed from MLUC to USC', 'concerns.php?id=1', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 06:30:53'),
(18, NULL, 'admin', NULL, 'Concern routing changed', 'ES-2026-264015 was routed from MLUC to USC', 'concerns.php?id=1', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 06:30:53'),
(19, NULL, NULL, 'SLUC', 'Concern referred to SLUC', 'ES-2026-264015 was routed from USC to SLUC', 'concerns.php?id=1', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 06:31:28'),
(20, NULL, 'admin', NULL, 'Concern routing changed', 'ES-2026-264015 was routed from USC to SLUC', 'concerns.php?id=1', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 06:31:28'),
(21, NULL, NULL, 'SLUC', 'Concern status changed', 'ES-2026-264015 is now Received', 'concerns.php?id=1', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 06:32:28'),
(22, NULL, 'admin', NULL, 'Concern status changed', 'ES-2026-264015 is now Received · SLUC', 'concerns.php?id=1', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 06:32:28'),
(23, NULL, NULL, 'USC', 'New E-Sumbong concern', 'ES-2026-25A625 · Fix', 'concerns.php?id=2', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:39:51'),
(24, NULL, 'admin', NULL, 'New E-Sumbong concern', 'ES-2026-25A625 · Fix · USC portal', 'concerns.php?id=2', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:39:51'),
(25, NULL, NULL, 'MLUC', 'Concern referred to MLUC', 'ES-2026-25A625 was routed from USC to MLUC', 'concerns.php?id=2', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:46:00'),
(26, NULL, 'admin', NULL, 'Concern routing changed', 'ES-2026-25A625 was routed from USC to MLUC', 'concerns.php?id=2', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:46:00'),
(27, NULL, NULL, 'USC', 'Concern referred to USC', 'ES-2026-25A625 was routed from MLUC to USC', 'concerns.php?id=2', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:46:20'),
(28, NULL, 'admin', NULL, 'Concern routing changed', 'ES-2026-25A625 was routed from MLUC to USC', 'concerns.php?id=2', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:46:20'),
(29, NULL, NULL, 'USC', 'Concern status changed', 'ES-2026-25A625 is now Referred', 'concerns.php?id=2', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:46:44'),
(30, NULL, 'admin', NULL, 'Concern status changed', 'ES-2026-25A625 is now Referred · USC', 'concerns.php?id=2', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:46:44'),
(31, NULL, NULL, 'USC', 'Concern status changed', 'ES-2026-25A625 is now Resolved', 'concerns.php?id=2', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:47:03'),
(32, NULL, 'admin', NULL, 'Concern status changed', 'ES-2026-25A625 is now Resolved · USC', 'concerns.php?id=2', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:47:03'),
(33, NULL, NULL, 'SLUC', 'Concern referred to SLUC', 'ES-2026-25A625 was routed from USC to SLUC', 'concerns.php?id=2', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:47:50'),
(34, NULL, 'admin', NULL, 'Concern routing changed', 'ES-2026-25A625 was routed from USC to SLUC', 'concerns.php?id=2', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:47:50'),
(35, NULL, 'usc', NULL, 'Homepage promotion request', 'South La Union Campus requested USC homepage display for “Your campus voice deserves a clear channel.”.', 'hero.php?portal=USC&view=promotions#promotion-4', 'warning', 'promotions', NULL, 0, NULL, NULL, '2026-08-19 11:51:24'),
(36, NULL, 'admin', NULL, 'Homepage promotion request', 'South La Union Campus requested USC homepage display for “Your campus voice deserves a clear channel.”.', 'hero.php?portal=USC&view=promotions#promotion-4', 'warning', 'promotions', NULL, 0, NULL, NULL, '2026-08-19 11:51:24'),
(37, NULL, 'usc', NULL, 'Homepage request withdrawn', 'South La Union Campus withdrew a pending USC homepage promotion request.', 'hero.php?portal=USC&view=promotions', 'info', 'promotions', NULL, 0, NULL, NULL, '2026-08-19 11:51:37'),
(38, NULL, 'usc', NULL, 'Homepage promotion request', 'South La Union Campus requested USC homepage display for “Your campus voice deserves a clear channel.”.', 'hero.php?portal=USC&view=promotions#promotion-5', 'warning', 'promotions', NULL, 0, NULL, NULL, '2026-08-19 11:51:43'),
(39, NULL, 'admin', NULL, 'Homepage promotion request', 'South La Union Campus requested USC homepage display for “Your campus voice deserves a clear channel.”.', 'hero.php?portal=USC&view=promotions#promotion-5', 'warning', 'promotions', NULL, 0, NULL, NULL, '2026-08-19 11:51:43'),
(40, NULL, NULL, 'SLUC', 'USC homepage request declined', 'USC declined the homepage promotion request for “Your campus voice deserves a clear channel.”.', 'hero.php?portal=SLUC&view=promotions', 'danger', 'promotions', NULL, 0, NULL, NULL, '2026-08-19 11:52:00'),
(41, NULL, 'campus_sas_head', 'SLUC', 'Publication ready for review', '𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫 is waiting for publishing review.', 'posts.php?edit=10', 'info', 'content', NULL, 0, NULL, NULL, '2026-08-19 11:52:26'),
(42, NULL, NULL, 'SLUC', 'Concern status changed', 'ES-2026-25A625 is now In Progress', 'concerns.php?id=2', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:53:14'),
(43, NULL, 'admin', NULL, 'Concern status changed', 'ES-2026-25A625 is now In Progress · SLUC', 'concerns.php?id=2', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:53:14'),
(44, NULL, NULL, 'SLUC', 'Concern status changed', 'ES-2026-25A625 is now Action Taken', 'concerns.php?id=2', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:53:30'),
(45, NULL, 'admin', NULL, 'Concern status changed', 'ES-2026-25A625 is now Action Taken · SLUC', 'concerns.php?id=2', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:53:30'),
(46, NULL, NULL, 'SLUC', 'Concern status changed', 'ES-2026-25A625 is now Resolved', 'concerns.php?id=2', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:53:55'),
(47, NULL, 'admin', NULL, 'Concern status changed', 'ES-2026-25A625 is now Resolved · SLUC', 'concerns.php?id=2', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 11:53:55'),
(48, NULL, NULL, 'USC', 'New E-Sumbong concern', 'ES-2026-DF4A7B · Fix', 'concerns.php?id=3', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 12:14:33'),
(49, NULL, 'admin', NULL, 'New E-Sumbong concern', 'ES-2026-DF4A7B · Fix · USC portal', 'concerns.php?id=3', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 12:14:33'),
(50, NULL, NULL, 'USC', 'Concern status changed', 'ES-2026-DF4A7B is now Received', 'concerns.php?id=3', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 12:19:30'),
(51, NULL, 'admin', NULL, 'Concern status changed', 'ES-2026-DF4A7B is now Received · USC', 'concerns.php?id=3', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 12:19:30'),
(52, NULL, NULL, 'USC', 'Concern status changed', 'ES-2026-DF4A7B is now Referred', 'concerns.php?id=3', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 12:19:52'),
(53, NULL, 'admin', NULL, 'Concern status changed', 'ES-2026-DF4A7B is now Referred · USC', 'concerns.php?id=3', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 12:19:52'),
(54, NULL, NULL, 'SLUC', 'Concern referred to SLUC', 'ES-2026-DF4A7B was routed from USC to SLUC', 'concerns.php?id=3', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 12:20:13'),
(55, NULL, 'admin', NULL, 'Concern routing changed', 'ES-2026-DF4A7B was routed from USC to SLUC', 'concerns.php?id=3', 'warning', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 12:20:13'),
(56, NULL, NULL, 'SLUC', 'Concern status changed', 'ES-2026-DF4A7B is now In Progress', 'concerns.php?id=3', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 12:20:54'),
(57, NULL, 'admin', NULL, 'Concern status changed', 'ES-2026-DF4A7B is now In Progress · SLUC', 'concerns.php?id=3', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 12:20:54'),
(58, NULL, NULL, 'SLUC', 'Concern status changed', 'ES-2026-DF4A7B is now Action Taken', 'concerns.php?id=3', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 12:21:25'),
(59, NULL, 'admin', NULL, 'Concern status changed', 'ES-2026-DF4A7B is now Action Taken · SLUC', 'concerns.php?id=3', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 12:21:25'),
(60, NULL, NULL, 'SLUC', 'Concern status changed', 'ES-2026-DF4A7B is now Resolved', 'concerns.php?id=3', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 12:21:46'),
(61, NULL, 'admin', NULL, 'Concern status changed', 'ES-2026-DF4A7B is now Resolved · SLUC', 'concerns.php?id=3', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-19 12:21:46'),
(62, NULL, NULL, 'SLUC', 'Concern status changed', 'ES-2026-25A625 is now Closed', 'concerns.php?id=2', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-20 00:53:17'),
(63, NULL, 'admin', NULL, 'Concern status changed', 'ES-2026-25A625 is now Closed · SLUC', 'concerns.php?id=2', 'info', 'concerns', NULL, 0, NULL, NULL, '2026-08-20 00:53:17'),
(64, NULL, 'campus_sas_head', 'SLUC', 'Publication ready for review', '𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫 is waiting for publishing review.', 'posts.php?edit=10', 'info', 'content', NULL, 0, NULL, NULL, '2026-08-20 05:33:11');

-- --------------------------------------------------------

--
-- Table structure for table `admin_notification_preferences`
--

CREATE TABLE `admin_notification_preferences` (
  `admin_id` int(11) NOT NULL,
  `category` varchar(40) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_password_history`
--

CREATE TABLE `admin_password_history` (
  `id` bigint(20) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_permission_overrides`
--

CREATE TABLE `admin_permission_overrides` (
  `admin_id` int(11) NOT NULL,
  `permission` varchar(100) NOT NULL,
  `allowed` tinyint(1) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_recovery_codes`
--

CREATE TABLE `admin_recovery_codes` (
  `id` bigint(20) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `code_hash` char(64) NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--

CREATE TABLE `admin_sessions` (
  `id` bigint(20) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `session_key_hash` char(64) NOT NULL,
  `php_session_hash` char(64) DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `last_seen_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_sessions`
--

INSERT INTO `admin_sessions` (`id`, `admin_id`, `session_key_hash`, `php_session_hash`, `ip_address`, `user_agent`, `created_at`, `last_seen_at`, `revoked_at`) VALUES
(1, 1, 'c9140b64927ef9129966aa6df3d9d31288c60c6ed0a2365d1de3d2a8025eca06', 'fc253b05ec976ebf4752ca575f0d8d84a809413717a8194238d615b89d41cf12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 21:13:34', '2026-08-19 04:36:46', NULL),
(2, 6, '1601d94a5e4e13b75b8862b7e7d491a9a225f0fe6371dc54bc20258ce93165e5', '3ecf96874bfbf94b50b190a1ab30dae340474941b60523124ac80b91427c93d3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-18 21:19:43', '2026-08-18 23:16:47', NULL),
(3, 1, 'b1ade7953e31475563978f2403dd0bdb08796f2653d188debaa45c15de2bf830', '67d78b28442435a22a4d30a6b4be8baf4f077116dcba952ab0b10360a34afcd3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:00:29', '2026-08-19 19:04:09', '2026-08-19 19:41:59'),
(4, 6, '579ba57e8d843eb1bcb0b8138d205a30012cd178b59c10f132997cceffdebec7', 'ca60ad2a6e06da30daf6caf80dc9fab18d7b7fa15a29e802076199dddb4e8f1f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 13:25:21', '2026-08-19 13:32:19', '2026-08-19 13:33:11'),
(5, 6, '23117d99b3014185c705c124fd2a8934d897914d8b461f514cb3be78a9eed48d', '77c1c70cd0ed783f9c563c78c3f7a31b17ef05fb79e0f1e70d83333fe43dcfd4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 14:31:49', '2026-08-19 14:31:49', NULL),
(6, 6, '39474dc82c178594d12f8a3fc0ce94e48f258e5437027325276b9bb2cbecf365', 'd0a40df1b16c8b3435b08370ad1505b6ef261c439fda0a6e6565c97ed9cbbf64', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 17:30:22', '2026-08-19 19:03:35', '2026-08-19 19:04:00'),
(7, 1, 'c37ca1a6f81f7dd6510b0ce18b1d06193d9df6861cfbd74bc7e4182e5dc31db0', '7726b99c31817427084b10ca018c67cf081bb7378cd1e53e9259359130eb8254', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 19:42:12', '2026-08-19 20:12:06', '2026-08-19 20:16:21'),
(8, 6, '765b323e485782bf1eb7d9eaa3459ee1f5f5aac38664520c21aa4714d9b757bc', 'e12cbbc005b2304abbc676fac618174ea79913f444c4ecb6eba04e715a5981b6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 19:51:01', '2026-08-19 20:02:47', '2026-08-19 20:02:48'),
(9, 1, '1787aead94b86c6538d0d6b2573e1e09db3b55b1742573046587e7677a603774', 'd0ac8c83f58cc308d346e70b22cdb90435910b3e8ffed7fc4d52f064194dc59d', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 20:16:34', '2026-08-19 22:45:22', '2026-08-19 22:45:31'),
(10, 6, 'e7ac7d74df14d5169d727ca0fb304bb81a3af34b38f4837bc5a53327e709ea67', '6883666ac49249c423920130f064e1c59477a6feb0480bef93a6d162f4478ff9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 20:20:28', '2026-08-20 11:36:23', NULL),
(11, 1, 'b47f529a54e1ac530c6d854f17ac37a698acd36ed64e03758e2b691b4b35de6a', '01e86dacd707ca827db421b3f481d369102bdbd8675a9078ea26c4ef370ddaf2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 22:45:49', '2026-08-19 22:49:45', '2026-08-19 22:50:09'),
(12, 1, 'f48ed640c24c6b253e7f693faafdc93baa3453766c11172ff4c1e81f51ca4e26', 'f7dee8eac9bb0aa9b881d70535819831f03fe731b9d439a106189cbc6059aa81', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 22:50:47', '2026-08-20 19:54:52', NULL),
(13, 6, 'b15d0139a920892ea514fe723745533d200b378970e4219c9bbef74264f31ead', 'abf9c0792574a1926a9e794ff0963c1a7adba66fd70f3150875b9c260685446d', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-20 13:32:47', '2026-08-20 17:25:52', '2026-08-20 17:25:59'),
(14, 6, 'c600f3c424806722a0ff4c3ce70ce850810109394b179613f18f004989213679', 'ef6994e23819664abf5133534924b3c002daf71a5ad288d725fb2c05d5f60ca3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-20 17:26:15', '2026-08-20 19:48:25', NULL),
(15, 2, 'ef1f9b8aa7549c9874bf296a7ee9a4d4cabcb2b5b294c08c5082491e05dae4da', '7c6dcb22229e382825312c1dc26cb6d64849de946ba9f1bf3d17a44a36bcc74c', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 19:41:27', '2026-08-20 19:49:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `portal_code` varchar(10) NOT NULL DEFAULT 'ALL',
  `title` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'info',
  `audience` varchar(20) NOT NULL DEFAULT 'public',
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `link_label` varchar(80) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `academic_year_id`, `portal_code`, `title`, `message`, `priority`, `audience`, `status`, `starts_at`, `ends_at`, `link_label`, `link_url`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'ALL', 'Scheduled System Maintenance', 'Please be advised that the system will undergo scheduled maintenance to improve performance and reliability. Some services may be temporarily unavailable during this period.\r\n\r\nThank you for your patience and understanding.', 'info', 'public', 'draft', NULL, NULL, NULL, NULL, 1, 1, '2026-08-20 05:35:09', '2026-08-20 06:02:15'),
(2, 1, 'ALL', 'Click here to proceed and learn more', 'We’re making improvements to provide you with a better and smoother experience. Some features may be temporarily unavailable while updates are being applied.', 'info', 'public', 'draft', NULL, NULL, 'Click here to proceed and learn more', 'https://www.facebook.com/usc.dmmmsu', 1, 1, '2026-08-20 06:03:36', '2026-08-20 06:08:36');

-- --------------------------------------------------------

--
-- Table structure for table `backup_history`
--

CREATE TABLE `backup_history` (
  `id` bigint(20) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `backup_type` varchar(30) NOT NULL DEFAULT 'database',
  `file_size` bigint(20) NOT NULL DEFAULT 0,
  `checksum_sha256` char(64) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `restored_by` int(11) DEFAULT NULL,
  `restored_at` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verification_status` varchar(30) DEFAULT NULL,
  `verification_message` varchar(500) DEFAULT NULL,
  `offsite_copied_at` datetime DEFAULT NULL,
  `offsite_path` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `backup_history`
--

INSERT INTO `backup_history` (`id`, `filename`, `backup_type`, `file_size`, `checksum_sha256`, `created_by`, `created_at`, `restored_by`, `restored_at`, `verified_at`, `verification_status`, `verification_message`, `offsite_copied_at`, `offsite_path`) VALUES
(1, 'dmmmsu_usc_20260819_152344_database.sql', 'database', 178513, '8053a6cbc20135cd4775ec472c3e45b60e694368028f1c6f875bba86670f8e03', 1, '2026-08-19 07:23:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'dmmmsu_usc_20260819_152457_database.sql', 'database', 179532, '1da00a670d7dc9ce155af99028c8d55321938fac9a6f948c6b5f6db91c60895d', 1, '2026-08-19 07:24:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'dmmmsu_usc_20260820_072238_database.sql', 'database', 261669, '7091a6b9106c85229207e80a106345eccf4747e6f1027f1f97e92a0e3c045e2c', 1, '2026-08-19 23:22:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'dmmmsu_usc_20260820_095743_database.sql', 'database', 348947, '65cdd7c3e123bc4fdc56b0a425f6fac8a49f38311315983717ae12f3e26e00d4', 1, '2026-08-20 01:57:43', NULL, NULL, '2026-08-20 09:58:17', 'verified', 'Checksum and structure verified.', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `concerns`
--

CREATE TABLE `concerns` (
  `id` int(11) NOT NULL,
  `reference_code` varchar(40) NOT NULL,
  `tracking_token_hash` char(64) DEFAULT NULL,
  `tracking_token_hint` varchar(24) DEFAULT NULL,
  `campus` varchar(50) NOT NULL,
  `college` varchar(180) DEFAULT NULL,
  `source_portal` varchar(10) NOT NULL DEFAULT 'USC',
  `assigned_scope` varchar(10) NOT NULL DEFAULT 'USC',
  `academic_year_id` int(11) DEFAULT NULL,
  `concern_type` varchar(50) NOT NULL,
  `student_urgency` varchar(20) DEFAULT NULL,
  `student_name` varchar(150) DEFAULT NULL,
  `student_id` varchar(80) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `anonymous` tinyint(1) DEFAULT 0,
  `sensitivity` varchar(20) NOT NULL DEFAULT 'standard',
  `status` enum('Submitted','Received','Under Review','Referred','In Progress','Action Taken','Resolved','Closed') NOT NULL DEFAULT 'Submitted',
  `priority` varchar(20) NOT NULL DEFAULT 'Normal',
  `assigned_to` int(11) DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `follow_up_at` datetime DEFAULT NULL,
  `first_response_at` datetime DEFAULT NULL,
  `last_public_update_at` datetime DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `internal_note` text DEFAULT NULL,
  `resolution_summary` text DEFAULT NULL,
  `escalation_reason` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `retention_until` date DEFAULT NULL,
  `privacy_review_status` varchar(30) NOT NULL DEFAULT 'pending',
  `privacy_reviewed_at` datetime DEFAULT NULL,
  `privacy_reviewed_by` int(11) DEFAULT NULL,
  `sensitive_view_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `concerns`
--

INSERT INTO `concerns` (`id`, `reference_code`, `tracking_token_hash`, `tracking_token_hint`, `campus`, `college`, `source_portal`, `assigned_scope`, `academic_year_id`, `concern_type`, `student_name`, `student_id`, `subject`, `message`, `anonymous`, `sensitivity`, `status`, `priority`, `assigned_to`, `due_at`, `follow_up_at`, `first_response_at`, `last_public_update_at`, `admin_note`, `internal_note`, `resolution_summary`, `escalation_reason`, `resolved_at`, `retention_until`, `privacy_review_status`, `privacy_reviewed_at`, `privacy_reviewed_by`, `sensitive_view_count`, `created_at`, `updated_at`) VALUES
(1, 'ES-2026-264015', NULL, NULL, 'MLUC — Mid La Union Campus', NULL, 'USC', 'SLUC', 1, 'Feedback', 'George', '211-1835-2', 'Fix', 'The system is bad', 0, 'standard', 'Received', 'Normal', 6, '2026-08-20 20:27:00', NULL, '2026-08-19 14:24:58', '2026-08-19 14:24:58', 'Your concern has been referred to the appropriate office/unit for further review and action.', 'Routing changed from USC to SLUC.\r\nRouting changed from MLUC to USC.\r\nRouting changed from USC to MLUC.', NULL, NULL, NULL, NULL, 'reviewed-retain', '2026-08-20 17:07:08', 1, 6, '2026-08-15 12:27:50', '2026-08-20 09:07:08'),
(2, 'ES-2026-25A625', NULL, NULL, 'Mid La Union Campus', 'College of Information Technology (CIT)', 'USC', 'SLUC', 1, 'Concern', 'Bacani, George Rexy Vincent Z.', '211-1835-2', 'Fix', 'The system is ugly.....', 0, 'standard', 'Closed', 'High', 6, '2026-08-24 19:39:00', NULL, '2026-08-19 19:45:59', '2026-08-19 19:53:30', 'Action has been taken on this concern. Please review the latest status and public note on your tracking page.', 'Routing changed from USC to SLUC.\r\nRouting changed from MLUC to USC.\r\nRouting changed from USC to MLUC.', 'WOOOWWW', '', '2026-08-19 19:53:55', '2028-08-18', 'pending', NULL, NULL, 6, '2026-08-19 11:39:51', '2026-08-20 11:33:40'),
(3, 'ES-2026-DF4A7B', NULL, NULL, 'Mid La Union Campus', 'College of Information Technology (CIT)', 'USC', 'SLUC', 1, 'Suggestion', 'Bacani, George Rexy Vincent Z.', '211-1835-2', 'Fix', 'Fix the system, unorganized.', 0, 'standard', 'Resolved', 'Normal', 6, '2026-08-24 20:14:00', NULL, '2026-08-19 20:19:30', '2026-08-19 20:21:25', 'Action has been taken on this concern. Please review the latest status and public note on your tracking page.', 'Routing changed from USC to SLUC.', NULL, NULL, '2026-08-19 20:21:46', '2028-08-18', 'pending', NULL, NULL, 1, '2026-08-19 12:14:33', '2026-08-19 23:22:41');

-- --------------------------------------------------------

--
-- Table structure for table `concern_attachments`
--

CREATE TABLE `concern_attachments` (
  `id` bigint(20) NOT NULL,
  `concern_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(120) NOT NULL,
  `file_size` bigint(20) NOT NULL DEFAULT 0,
  `sha256` char(64) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `source` varchar(20) NOT NULL DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `concern_attachments`
--


-- --------------------------------------------------------

--
-- Table structure for table `concern_history`
--

CREATE TABLE `concern_history` (
  `id` bigint(20) NOT NULL,
  `concern_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `actor_name` varchar(150) DEFAULT NULL,
  `action` varchar(80) NOT NULL DEFAULT 'Updated concern',
  `old_status` varchar(40) DEFAULT NULL,
  `status` varchar(40) NOT NULL,
  `old_assigned_scope` varchar(10) DEFAULT NULL,
  `assigned_scope` varchar(10) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `public_note` text DEFAULT NULL,
  `internal_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `concern_history`
--

INSERT INTO `concern_history` (`id`, `concern_id`, `admin_id`, `actor_name`, `action`, `old_status`, `status`, `old_assigned_scope`, `assigned_scope`, `assigned_to`, `public_note`, `internal_note`, `created_at`) VALUES
(1, 1, 1, NULL, 'Updated concern', NULL, 'Submitted', NULL, NULL, NULL, '', '', '2026-08-18 06:39:39'),
(2, 1, 1, NULL, 'Updated concern', NULL, 'Submitted', NULL, NULL, NULL, '', '', '2026-08-18 07:04:25'),
(3, 1, 1, NULL, 'Updated concern', NULL, 'Submitted', NULL, NULL, NULL, '', '', '2026-08-18 07:04:30'),
(4, 1, 1, NULL, 'Updated concern', NULL, 'Submitted', NULL, NULL, NULL, '', '', '2026-08-18 07:08:36'),
(5, 1, 1, NULL, 'Updated concern', NULL, 'Submitted', NULL, NULL, NULL, '', '', '2026-08-18 07:08:41'),
(6, 1, 1, NULL, 'Updated concern', NULL, 'Submitted', NULL, NULL, NULL, '', '', '2026-08-18 07:08:46'),
(7, 1, 1, NULL, 'Updated concern', NULL, 'Submitted', NULL, NULL, NULL, '', '', '2026-08-18 07:09:03'),
(8, 1, 1, NULL, 'Updated concern', NULL, 'Received', NULL, NULL, NULL, '', '', '2026-08-18 07:09:14'),
(9, 1, 1, NULL, 'Updated concern', NULL, 'Received', NULL, NULL, NULL, '', '', '2026-08-18 07:09:25'),
(10, 1, 1, NULL, 'Updated concern', NULL, 'Received', NULL, NULL, NULL, '', '', '2026-08-18 07:09:34'),
(11, 1, 1, NULL, 'Updated concern', NULL, 'Submitted', NULL, NULL, NULL, '', '', '2026-08-18 07:09:42'),
(12, 1, 1, NULL, 'Updated concern', NULL, 'Submitted', NULL, NULL, NULL, '', '', '2026-08-18 07:09:45'),
(13, 1, 1, NULL, 'Updated concern', NULL, 'Submitted', NULL, NULL, NULL, '', '', '2026-08-18 07:09:49'),
(14, 1, 1, NULL, 'Updated concern', NULL, 'Submitted', NULL, NULL, NULL, '', '', '2026-08-18 07:09:53'),
(15, 1, 1, NULL, 'Updated concern', NULL, 'Submitted', NULL, NULL, NULL, '', '', '2026-08-18 07:09:58'),
(16, 1, 1, NULL, 'Updated concern', NULL, 'Submitted', NULL, NULL, NULL, '', '', '2026-08-18 07:10:00'),
(17, 1, 1, 'USC Administrator', 'Updated concern', 'Submitted', 'Submitted', 'USC', 'USC', 2, 'Your concern has been referred to the appropriate office/unit for further review and action.', '', '2026-08-19 06:24:58'),
(18, 1, 1, 'USC Administrator', 'Referred concern', 'Submitted', 'Submitted', 'USC', 'MLUC', 3, 'Your concern has been referred to the appropriate office/unit for further review and action.', 'Routing changed from USC to MLUC.', '2026-08-19 06:30:33'),
(19, 1, 1, 'USC Administrator', 'Referred concern', 'Submitted', 'Submitted', 'MLUC', 'USC', 3, 'Your concern has been referred to the appropriate office/unit for further review and action.', 'Routing changed from MLUC to USC.\nRouting changed from USC to MLUC.', '2026-08-19 06:30:53'),
(20, 1, 1, 'USC Administrator', 'Referred concern', 'Submitted', 'Submitted', 'USC', 'SLUC', 6, 'Your concern has been referred to the appropriate office/unit for further review and action.', 'Routing changed from USC to SLUC.\nRouting changed from MLUC to USC.\r\nRouting changed from USC to MLUC.', '2026-08-19 06:31:28'),
(21, 1, 6, 'SLUC CSBO', 'Changed status', 'Submitted', 'Received', 'SLUC', 'SLUC', 6, 'Your concern has been referred to the appropriate office/unit for further review and action.', 'Routing changed from USC to SLUC.\r\nRouting changed from MLUC to USC.\r\nRouting changed from USC to MLUC.', '2026-08-19 06:32:28'),
(22, 2, 1, 'USC Administrator', 'Referred concern', 'Submitted', 'Received', 'USC', 'MLUC', 13, 'Your concern has been received and is now under review. We will update this tracking page when additional action is recorded.', 'Routing changed from USC to MLUC.', '2026-08-19 11:45:59'),
(23, 2, 1, 'USC Administrator', 'Referred concern', 'Received', 'Under Review', 'MLUC', 'USC', 3, 'Your concern has been received and is now under review. We will update this tracking page when additional action is recorded.', 'Routing changed from MLUC to USC.\nRouting changed from USC to MLUC.', '2026-08-19 11:46:20'),
(24, 2, 1, 'USC Administrator', 'Changed status', 'Under Review', 'Referred', 'USC', 'USC', 3, 'Your concern has been referred to the appropriate office/unit for further review and action.', 'Routing changed from MLUC to USC.\r\nRouting changed from USC to MLUC.', '2026-08-19 11:46:44'),
(25, 2, 1, 'USC Administrator', 'Changed status', 'Referred', 'Resolved', 'USC', 'USC', 3, 'Your concern has been referred to the appropriate office/unit for further review and action.', 'Routing changed from MLUC to USC.\r\nRouting changed from USC to MLUC.', '2026-08-19 11:47:03'),
(26, 2, 1, 'USC Administrator', 'Updated concern', 'Resolved', 'Resolved', 'USC', 'USC', 3, 'Your concern has been received and is now under review. We will update this tracking page when additional action is recorded.', 'Routing changed from MLUC to USC.\r\nRouting changed from USC to MLUC.', '2026-08-19 11:47:18'),
(27, 2, 1, 'USC Administrator', 'Referred concern', 'Resolved', 'Referred', 'USC', 'SLUC', 6, 'Your concern has been referred to the appropriate office/unit for further review and action.', 'Routing changed from USC to SLUC.\nRouting changed from MLUC to USC.\r\nRouting changed from USC to MLUC.', '2026-08-19 11:47:50'),
(28, 2, 6, 'SLUC CSBO', 'Changed status', 'Referred', 'In Progress', 'SLUC', 'SLUC', 6, 'Your concern has been received and is now under review. We will update this tracking page when additional action is recorded.', 'Routing changed from USC to SLUC.\r\nRouting changed from MLUC to USC.\r\nRouting changed from USC to MLUC.', '2026-08-19 11:53:14'),
(29, 2, 6, 'SLUC CSBO', 'Changed status', 'In Progress', 'Action Taken', 'SLUC', 'SLUC', 6, 'Action has been taken on this concern. Please review the latest status and public note on your tracking page.', 'Routing changed from USC to SLUC.\r\nRouting changed from MLUC to USC.\r\nRouting changed from USC to MLUC.', '2026-08-19 11:53:30'),
(30, 2, 1, 'USC Administrator', 'Changed status', 'Action Taken', 'Resolved', 'SLUC', 'SLUC', 6, 'Action has been taken on this concern. Please review the latest status and public note on your tracking page.', 'Routing changed from USC to SLUC.\r\nRouting changed from MLUC to USC.\r\nRouting changed from USC to MLUC.', '2026-08-19 11:53:55'),
(31, 3, 1, 'USC Administrator', 'Changed status', 'Submitted', 'Received', 'USC', 'USC', NULL, '', '', '2026-08-19 12:19:30'),
(32, 3, 1, 'USC Administrator', 'Reassigned concern', 'Received', 'Referred', 'USC', 'USC', 3, 'Your concern has been referred to the appropriate office/unit for further review and action.', '', '2026-08-19 12:19:52'),
(33, 3, 1, 'USC Administrator', 'Referred concern', 'Referred', 'Referred', 'USC', 'SLUC', 6, 'Your concern has been referred to the appropriate office/unit for further review and action.', 'Routing changed from USC to SLUC.', '2026-08-19 12:20:13'),
(34, 3, 6, 'SLUC CSBO', 'Changed status', 'Referred', 'In Progress', 'SLUC', 'SLUC', 6, 'Your concern has been received and is now under review. We will update this tracking page when additional action is recorded.', 'Routing changed from USC to SLUC.', '2026-08-19 12:20:54'),
(35, 3, 6, 'SLUC CSBO', 'Changed status', 'In Progress', 'Action Taken', 'SLUC', 'SLUC', 6, 'Action has been taken on this concern. Please review the latest status and public note on your tracking page.', 'Routing changed from USC to SLUC.', '2026-08-19 12:21:25'),
(36, 3, 1, 'USC Administrator', 'Changed status', 'Action Taken', 'Resolved', 'SLUC', 'SLUC', 6, 'Action has been taken on this concern. Please review the latest status and public note on your tracking page.', 'Routing changed from USC to SLUC.', '2026-08-19 12:21:46'),
(37, 2, 1, 'USC Administrator', 'Updated concern', 'Resolved', 'Resolved', 'SLUC', 'SLUC', 6, 'Action has been taken on this concern. Please review the latest status and public note on your tracking page.', 'Routing changed from USC to SLUC.\r\nRouting changed from MLUC to USC.\r\nRouting changed from USC to MLUC.', '2026-08-20 00:52:46'),
(38, 2, 1, 'USC Administrator', 'Updated concern', 'Resolved', 'Resolved', 'SLUC', 'SLUC', 6, 'Action has been taken on this concern. Please review the latest status and public note on your tracking page.', 'Routing changed from USC to SLUC.\r\nRouting changed from MLUC to USC.\r\nRouting changed from USC to MLUC.', '2026-08-20 00:53:09'),
(39, 2, 1, 'USC Administrator', 'Changed status', 'Resolved', 'Closed', 'SLUC', 'SLUC', 6, 'Action has been taken on this concern. Please review the latest status and public note on your tracking page.', 'Routing changed from USC to SLUC.\r\nRouting changed from MLUC to USC.\r\nRouting changed from USC to MLUC.', '2026-08-20 00:53:17');

-- --------------------------------------------------------

--
-- Table structure for table `concern_private_identity`
--

CREATE TABLE `concern_private_identity` (
  `concern_id` int(11) NOT NULL,
  `student_name_cipher` longtext DEFAULT NULL,
  `student_id_cipher` longtext DEFAULT NULL,
  `contact_email_cipher` longtext DEFAULT NULL,
  `encryption_version` varchar(20) NOT NULL DEFAULT 'v1',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `concern_private_identity`
--

INSERT INTO `concern_private_identity` (`concern_id`, `student_name_cipher`, `student_id_cipher`, `contact_email_cipher`, `encryption_version`, `created_at`, `updated_at`) VALUES
(1, 'enc:v1:s/FzVwV+3dr18EOOugyWhQniX9JI8T+Ix9fQ2gIHQxpMbQ==', 'enc:v1:MuvkliqGUs6kfOOKAPjmgLZ8bhU/a9d0mDwMo+54upnxAtzHDBI=', NULL, 'v1', '2026-08-19 23:22:39', '2026-08-19 23:22:39'),
(2, 'enc:v1:RRxRTdaRggnDtSUFT05fMSm1ju7bKvNuEL+7n4sgZK2zeJtpbLgObIt79S10uyQKrnU0VHVE31rhBA==', 'enc:v1:HNHwdslmIeFH3MTX5CvLzw1vn3kT3uIGjPPVv8BcrFh7xRrs+1I=', NULL, 'v1', '2026-08-19 23:22:39', '2026-08-19 23:22:39'),
(3, 'enc:v1:wPKhxKwOP5IXzlI5nJ3ScgJ9gc2tFNluExYKQSVuoYJDQv6Lnn/DfSacqndW+kp3e3n63VVywG226A==', 'enc:v1:OnZYf3pMijZ+6WPfS5GTJ01GxbJ3D8V7+hDZCabv4/V9SPQLv0A=', NULL, 'v1', '2026-08-19 23:22:39', '2026-08-19 23:22:39');

-- --------------------------------------------------------

--
-- Table structure for table `concern_response_templates`
--

CREATE TABLE `concern_response_templates` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `response_text` text NOT NULL,
  `concern_type` varchar(50) DEFAULT NULL,
  `campus` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `concern_response_templates`
--

INSERT INTO `concern_response_templates` (`id`, `title`, `response_text`, `concern_type`, `campus`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Acknowledgement', 'Your concern has been received and is now under review. We will update this tracking page when additional action is recorded.', NULL, NULL, 1, NULL, '2026-08-19 04:00:48', '2026-08-19 04:00:48'),
(2, 'Referred to responsible unit', 'Your concern has been referred to the appropriate office/unit for further review and action.', NULL, NULL, 1, NULL, '2026-08-19 04:00:48', '2026-08-19 04:00:48'),
(3, 'Action completed', 'Action has been taken on this concern. Please review the latest status and public note on your tracking page.', NULL, NULL, 1, NULL, '2026-08-19 04:00:48', '2026-08-19 04:00:48'),
(4, 'Need more details', 'We need a few more details to proceed with this concern. Please check the latest update and provide the requested information if available.', NULL, NULL, 1, 1, '2026-08-20 10:51:52', '2026-08-20 10:51:52'),
(5, 'Case closed', 'This concern has been closed. Thank you for using E-Sumbong. If you still need help, you may submit a new concern with updated details.', NULL, NULL, 1, 1, '2026-08-20 10:51:52', '2026-08-20 10:51:52'),
(6, 'sssd', 'dasdasdas', 'Concern', NULL, 0, 1, '2026-08-20 11:20:12', '2026-08-20 11:20:21'),
(7, 'Under review', 'Your concern is currently under review. We are checking the details and coordinating the next appropriate action.', NULL, NULL, 1, NULL, '2026-08-20 11:29:54', '2026-08-20 11:29:54'),
(8, 'Follow-up sent', 'A follow-up has been sent to the responsible office or unit. We will update your tracking page once a response or action is recorded.', NULL, NULL, 1, NULL, '2026-08-20 11:29:54', '2026-08-20 11:29:54'),
(9, 'Awaiting responsible unit', 'Your concern is awaiting feedback or action from the responsible office or unit. We are continuing to monitor its progress.', NULL, NULL, 1, NULL, '2026-08-20 11:29:54', '2026-08-20 11:29:54'),
(10, 'Scheduled for action', 'Your concern has been scheduled for action. Please continue checking your tracking page for the next recorded update.', NULL, NULL, 1, NULL, '2026-08-20 11:29:54', '2026-08-20 11:29:54'),
(11, 'Resolved', 'Your concern has been resolved based on the action taken by the responsible office or unit. Please review the latest public update for details.', NULL, NULL, 1, NULL, '2026-08-20 11:29:54', '2026-08-20 11:29:54');

-- --------------------------------------------------------

--
-- Table structure for table `hero_promotion_requests`
--

CREATE TABLE `hero_promotion_requests` (
  `id` bigint(20) NOT NULL,
  `source_slide_id` int(11) NOT NULL,
  `source_portal` varchar(10) NOT NULL,
  `target_portal` varchar(10) NOT NULL DEFAULT 'USC',
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `request_note` text DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `requested_by_name` varchar(150) DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_by_name` varchar(150) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `display_from` datetime DEFAULT NULL,
  `display_until` datetime DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hero_promotion_requests`
--

INSERT INTO `hero_promotion_requests` (`id`, `source_slide_id`, `source_portal`, `target_portal`, `status`, `request_note`, `review_note`, `requested_by`, `requested_by_name`, `requested_at`, `reviewed_by`, `reviewed_by_name`, `reviewed_at`, `display_from`, `display_until`, `display_order`, `updated_at`) VALUES
(1, 15, 'OUS', 'USC', 'withdrawn', NULL, NULL, 1, 'USC Administrator', '2026-08-18 11:03:35', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-18 11:04:58'),
(2, 15, 'OUS', 'USC', 'rejected', NULL, NULL, 1, 'USC Administrator', '2026-08-18 11:11:47', 1, 'USC Administrator', '2026-08-18 19:12:06', NULL, NULL, 1, '2026-08-18 11:12:06'),
(3, 12, 'SLUC', 'USC', 'withdrawn', NULL, NULL, 6, 'SLUC CSBO', '2026-08-18 11:46:24', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-18 11:46:51'),
(4, 12, 'SLUC', 'USC', 'withdrawn', NULL, NULL, 6, 'SLUC CSBO', '2026-08-19 11:51:24', NULL, NULL, NULL, NULL, NULL, 1, '2026-08-19 11:51:37'),
(5, 12, 'SLUC', 'USC', 'rejected', NULL, NULL, 6, 'SLUC CSBO', '2026-08-19 11:51:43', 1, 'USC Administrator', '2026-08-19 19:52:00', NULL, NULL, 1, '2026-08-19 11:52:00');

-- --------------------------------------------------------

--
-- Table structure for table `hero_slides`
--

CREATE TABLE `hero_slides` (
  `id` int(11) NOT NULL,
  `portal_code` varchar(10) NOT NULL DEFAULT 'USC',
  `theme` varchar(30) NOT NULL DEFAULT 'green',
  `eyebrow` varchar(160) NOT NULL,
  `title` varchar(220) NOT NULL,
  `description` text NOT NULL,
  `primary_label` varchar(80) DEFAULT NULL,
  `primary_url` varchar(255) DEFAULT NULL,
  `secondary_label` varchar(80) DEFAULT NULL,
  `secondary_url` varchar(255) DEFAULT NULL,
  `button_mode` varchar(20) NOT NULL DEFAULT 'both',
  `panel_type` varchar(30) NOT NULL DEFAULT 'esumbong',
  `panel_kicker` varchar(80) DEFAULT NULL,
  `panel_status` varchar(40) DEFAULT NULL,
  `panel_title` varchar(180) DEFAULT NULL,
  `panel_description` text DEFAULT NULL,
  `panel_image` varchar(255) DEFAULT NULL,
  `panel_media_id` int(11) DEFAULT NULL,
  `background_image` varchar(255) DEFAULT NULL,
  `background_media_id` int(11) DEFAULT NULL,
  `background_position_x` tinyint(3) UNSIGNED NOT NULL DEFAULT 50,
  `background_position_y` tinyint(3) UNSIGNED NOT NULL DEFAULT 50,
  `display_mode` varchar(30) NOT NULL DEFAULT 'standard',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hero_slides`
--

INSERT INTO `hero_slides` (`id`, `portal_code`, `theme`, `eyebrow`, `title`, `description`, `primary_label`, `primary_url`, `secondary_label`, `secondary_url`, `button_mode`, `panel_type`, `panel_kicker`, `panel_status`, `panel_title`, `panel_description`, `panel_image`, `panel_media_id`, `background_image`, `background_media_id`, `background_position_x`, `background_position_y`, `display_mode`, `sort_order`, `is_active`, `updated_at`) VALUES
(1, 'USC', 'green', 'USC DIGITAL STUDENT SERVICES', 'Your voice deserves a clear channel.', 'Submit student concerns through E-Sumbong and follow their progress through one simple USC service portal.', 'Open E-Sumbong', 'esumbong.php', 'Track Concern', 'track.php', 'both', 'card', 'E-SUMBONG', 'ONLINE', 'Speak. Submit. Be heard.', 'A direct, organized channel for concerns, suggestions, complaints, and feedback.', NULL, NULL, 'uploads/hero/usc_background_c55848e1c895a452.jpg', NULL, 50, 75, 'standard', 1, 1, '2026-08-19 11:43:49'),
(2, 'USC', 'green', 'TRANSPARENCY • ACCOUNTABILITY • LEADERSHIP', 'USC records, easier to access.', 'Browse accomplishment reports, attendance records, policy updates, and official resolutions through TALA.', 'Browse TALA', 'tala.php', 'View Updates', 'updates.php', 'both', 'none', 'TALA ARCHIVE', 'PUBLIC', 'USC Records', 'Accomplishment reports, attendance reports, policies, and resolutions.', NULL, NULL, NULL, NULL, 50, 76, 'standard', 3, 1, '2026-08-18 06:22:59'),
(3, 'USC', 'blue', 'USC NEWS & UPDATES', 'Stay informed without the clutter.', 'See important USC announcements and campus updates in one focused news feed.', 'Latest Updates', '#latest', 'All News', 'updates.php', 'both', 'card', 'USC NEWS', 'LIVE', 'Stay connected.', 'Important student information in one place.', NULL, NULL, NULL, NULL, 50, 50, 'standard', 2, 1, '2026-08-18 06:22:59'),
(6, 'NLUC', 'green', 'NORTH LA UNION CAMPUS • STUDENT SERVICES', 'Your campus voice deserves a clear channel.', 'Access student services, submit concerns, and follow updates for North La Union Campus through its dedicated portal.', 'Open E-Sumbong', 'esumbong.php?campus=NLUC', 'Track Concern', 'track.php?campus=NLUC', 'both', 'card', 'NORTH LA UNION CAMPUS', 'ONLINE', 'North La Union Campus', 'A dedicated student-services homepage for this campus.', NULL, NULL, NULL, NULL, 50, 50, 'standard', 1, 1, '2026-08-18 10:36:19'),
(7, 'NLUC', 'tala', 'TRANSPARENCY • ACCOUNTABILITY', 'Campus records, easier to access.', 'Browse accomplishment reports, attendance records, policy updates, and resolutions published by North La Union Campus.', 'Browse TALA', 'tala.php?campus=NLUC', 'View Updates', 'updates.php?campus=NLUC', 'both', 'card', 'TALA ARCHIVE', 'PUBLIC', 'North La Union Campus Records', 'Campus transparency records in one accessible archive.', NULL, NULL, NULL, NULL, 50, 50, 'standard', 2, 1, '2026-08-18 10:36:19'),
(8, 'NLUC', 'blue', 'NORTH LA UNION CAMPUS NEWS & UPDATES', 'Stay connected to your campus.', 'See announcements, service notices, campus stories, and council updates from North La Union Campus.', 'Latest Updates', '#latest', 'All News', 'updates.php?campus=NLUC', 'both', 'card', 'CAMPUS NEWS', 'LIVE', 'Stay connected.', 'Important campus information in one place.', NULL, NULL, NULL, NULL, 50, 50, 'standard', 3, 1, '2026-08-18 10:36:19'),
(9, 'MLUC', 'green', 'MID LA UNION CAMPUS • STUDENT SERVICES', 'Your campus voice deserves a clear channel.', 'Access student services, submit concerns, and follow updates for Mid La Union Campus through its dedicated portal.', 'Open E-Sumbong', 'esumbong.php?campus=MLUC', 'Track Concern', 'track.php?campus=MLUC', 'both', 'card', 'MID LA UNION CAMPUS', 'ONLINE', 'Mid La Union Campus', 'A dedicated student-services homepage for this campus.', NULL, NULL, NULL, NULL, 50, 50, 'standard', 1, 1, '2026-08-18 10:36:19'),
(10, 'MLUC', 'tala', 'TRANSPARENCY • ACCOUNTABILITY', 'Campus records, easier to access.', 'Browse accomplishment reports, attendance records, policy updates, and resolutions published by Mid La Union Campus.', 'Browse TALA', 'tala.php?campus=MLUC', 'View Updates', 'updates.php?campus=MLUC', 'both', 'card', 'TALA ARCHIVE', 'PUBLIC', 'Mid La Union Campus Records', 'Campus transparency records in one accessible archive.', NULL, NULL, NULL, NULL, 50, 50, 'standard', 2, 1, '2026-08-18 10:36:19'),
(11, 'MLUC', 'blue', 'MID LA UNION CAMPUS NEWS & UPDATES', 'Stay connected to your campus.', 'See announcements, service notices, campus stories, and council updates from Mid La Union Campus.', 'Latest Updates', '#latest', 'All News', 'updates.php?campus=MLUC', 'both', 'card', 'CAMPUS NEWS', 'LIVE', 'Stay connected.', 'Important campus information in one place.', NULL, NULL, NULL, NULL, 50, 50, 'standard', 3, 1, '2026-08-18 10:36:19'),
(12, 'SLUC', 'green', 'SOUTH LA UNION CAMPUS • STUDENT SERVICES', 'Your campus voice deserves a clear channel.', 'Access student services, submit concerns, and follow updates for South La Union Campus through its dedicated portal.', 'Open E-Sumbong', 'esumbong.php?campus=SLUC', 'Track Concern', 'track.php?campus=SLUC', 'both', 'card', 'SOUTH LA UNION CAMPUS', 'ONLINE', 'South La Union Campus', 'A dedicated student-services homepage for this campus.', NULL, NULL, NULL, NULL, 50, 76, 'standard', 1, 1, '2026-08-19 13:53:57'),
(13, 'SLUC', 'tala', 'TRANSPARENCY • ACCOUNTABILITY', 'Campus records, easier to access.', 'Browse accomplishment reports, attendance records, policy updates, and resolutions published by South La Union Campus.', 'Browse TALA', 'tala.php?campus=SLUC', 'View Updates', 'updates.php?campus=SLUC', 'both', 'card', 'TALA ARCHIVE', 'PUBLIC', 'South La Union Campus Records', 'Campus transparency records in one accessible archive.', NULL, NULL, NULL, NULL, 50, 50, 'standard', 2, 1, '2026-08-18 10:36:19'),
(14, 'SLUC', 'blue', 'SOUTH LA UNION CAMPUS NEWS & UPDATES', 'Stay connected to your campus.', 'See announcements, service notices, campus stories, and council updates from South La Union Campus.', 'Latest Updates', '#latest', 'All News', 'updates.php?campus=SLUC', 'both', 'card', 'CAMPUS NEWS', 'LIVE', 'Stay connected.', 'Important campus information in one place.', NULL, NULL, NULL, NULL, 50, 50, 'standard', 3, 1, '2026-08-18 10:36:19'),
(15, 'OUS', 'green', 'OPEN UNIVERSITY SYSTEM • STUDENT SERVICES', 'Your campus voice deserves a clear channel.', 'Access student services, submit concerns, and follow updates for Open University System through its dedicated portal.', 'Open E-Sumbong', 'esumbong.php?campus=OUS', 'Track Concern', 'track.php?campus=OUS', 'both', 'card', 'OPEN UNIVERSITY SYSTEM', 'ONLINE', 'Open University System', 'A dedicated student-services homepage for this campus.', NULL, NULL, NULL, NULL, 50, 50, 'standard', 1, 1, '2026-08-18 10:36:19'),
(16, 'OUS', 'tala', 'TRANSPARENCY • ACCOUNTABILITY', 'Campus records, easier to access.', 'Browse accomplishment reports, attendance records, policy updates, and resolutions published by Open University System.', 'Browse TALA', 'tala.php?campus=OUS', 'View Updates', 'updates.php?campus=OUS', 'both', 'card', 'TALA ARCHIVE', 'PUBLIC', 'Open University System Records', 'Campus transparency records in one accessible archive.', NULL, NULL, NULL, NULL, 50, 50, 'standard', 2, 1, '2026-08-18 10:36:19'),
(17, 'OUS', 'blue', 'OPEN UNIVERSITY SYSTEM NEWS & UPDATES', 'Stay connected to your campus.', 'See announcements, service notices, campus stories, and council updates from Open University System.', 'Latest Updates', '#latest', 'All News', 'updates.php?campus=OUS', 'both', 'card', 'CAMPUS NEWS', 'LIVE', 'Stay connected.', 'Important campus information in one place.', NULL, NULL, NULL, NULL, 50, 50, 'standard', 3, 1, '2026-08-18 10:36:19');

-- --------------------------------------------------------

--
-- Table structure for table `media_library`
--

CREATE TABLE `media_library` (
  `id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `sha256` char(64) DEFAULT NULL,
  `last_verified_at` datetime DEFAULT NULL,
  `optimized_at` datetime DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `campus` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `media_library`
--


-- --------------------------------------------------------

--
-- Table structure for table `notification_email_outbox`
--

CREATE TABLE `notification_email_outbox` (
  `id` bigint(20) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `concern_id` int(11) DEFAULT NULL,
  `recipient` varchar(190) NOT NULL,
  `subject` varchar(190) NOT NULL,
  `body` text NOT NULL,
  `category` varchar(40) NOT NULL DEFAULT 'general',
  `status` varchar(30) NOT NULL DEFAULT 'queued',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_error` varchar(500) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `officers`
--

CREATE TABLE `officers` (
  `id` int(11) NOT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `portal_code` varchar(10) NOT NULL DEFAULT 'USC',
  `full_name` varchar(150) NOT NULL,
  `position_title` varchar(150) NOT NULL,
  `office_name` varchar(150) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `officers`
--

INSERT INTO `officers` (`id`, `academic_year_id`, `portal_code`, `full_name`, `position_title`, `office_name`, `email`, `photo_path`, `start_date`, `end_date`, `is_archived`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'USC', 'Michael Dean Malonzo', 'President', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-08-20 02:02:32', '2026-08-20 03:53:20'),
(2, 1, 'USC', 'Francis Paul Sagad', 'Vice President', NULL, NULL, NULL, NULL, NULL, 0, 1, 1, '2026-08-20 02:09:11', '2026-08-20 02:16:52'),
(3, 1, 'USC', 'Joeleiyane Sotelo', 'Ethical Standards Officer', NULL, NULL, NULL, NULL, NULL, 0, 2, 1, '2026-08-20 02:10:41', '2026-08-20 02:16:58'),
(4, 1, 'USC', 'Allen Mike Hubbard', 'Student Affairs and Services', NULL, NULL, NULL, NULL, NULL, 0, 5, 1, '2026-08-20 02:17:30', '2026-08-20 02:21:39'),
(5, 1, 'USC', 'Cheery Macapagong', 'Executive Secretary', NULL, NULL, NULL, NULL, NULL, 0, 4, 1, '2026-08-20 02:23:05', '2026-08-20 02:23:17'),
(6, 1, 'USC', 'Rod Mark Fernandez', 'Student Information and Communications Technology', NULL, NULL, NULL, NULL, NULL, 0, 6, 1, '2026-08-20 02:23:55', '2026-08-20 02:23:55'),
(7, 1, 'USC', 'Jhulia Ellaine Duran', 'Health and Wellness', NULL, NULL, NULL, NULL, NULL, 0, 7, 1, '2026-08-20 02:24:20', '2026-08-20 02:24:20'),
(8, 1, 'USC', 'Marean Lexy Aspiras', 'Environmental Affairs', NULL, NULL, NULL, NULL, NULL, 0, 8, 1, '2026-08-20 02:25:08', '2026-08-20 02:25:08'),
(9, 1, 'USC', 'Psyche Andrei Liclican', 'Gender and Development', NULL, NULL, NULL, NULL, NULL, 0, 10, 1, '2026-08-20 02:26:01', '2026-08-20 02:26:01'),
(10, 1, 'USC', 'Edvir Dave Asprec', 'Sports and Youth Development', NULL, NULL, NULL, NULL, NULL, 0, 11, 1, '2026-08-20 02:26:20', '2026-08-20 02:26:20'),
(11, 1, 'USC', 'John Paul Vincent Estacio', 'Budget and Finance', NULL, NULL, NULL, NULL, NULL, 0, 12, 1, '2026-08-20 02:26:43', '2026-08-20 02:26:43'),
(12, 1, 'USC', 'Ray Jasper Jacaban', 'Ways and Means', NULL, NULL, NULL, NULL, NULL, 0, 13, 1, '2026-08-20 02:27:08', '2026-08-20 02:27:08'),
(13, 1, 'USC', 'George Rexy Vincent Bacani', 'Audit Commissioner', NULL, NULL, NULL, NULL, NULL, 1, 14, 1, '2026-08-20 02:27:27', '2026-08-20 03:37:59'),
(14, 1, 'USC', 'Christian Soriano', 'Linkages', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-08-20 02:31:24', '2026-08-20 02:31:24'),
(15, 1, 'SLUC', 'Edrian A. Rocaberte', 'Chairperson', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-08-20 03:02:03', '2026-08-20 03:02:03'),
(16, 1, 'SLUC', 'Hermes J. Mendoza', 'Vice Chairperson', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-08-20 03:02:22', '2026-08-20 03:02:22'),
(17, 1, 'MLUC', 'Shannen C. Villacorte', 'Chairperson', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-08-20 03:04:56', '2026-08-20 03:04:56'),
(18, 1, 'MLUC', 'Jeremiah O. Hipol', 'Vice Chairperson', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-08-20 03:05:12', '2026-08-20 03:05:12'),
(19, 1, 'NLUC', 'Harold D. Valdez', 'Chairperson', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-08-20 03:05:44', '2026-08-20 03:05:44'),
(20, 1, 'NLUC', 'Trisha Mae I. Murillo', 'Vice Chairperson', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-08-20 03:06:16', '2026-08-20 03:06:16'),
(21, 1, 'OUS', 'Jomerson B. Celeste', 'Chairperson', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-08-20 03:06:40', '2026-08-20 03:06:40'),
(22, 1, 'OUS', 'Jenelyn R. Saltiban', 'Vice Chairperson', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-08-20 03:06:59', '2026-08-20 03:06:59'),
(23, 1, 'USC', 'George Rexy Vincent Bacani', 'Audit Commissioner', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, '2026-08-20 03:45:06', '2026-08-20 03:45:06');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `category` enum('usc','nluc','mluc','sluc','ous') NOT NULL DEFAULT 'usc',
  `label` varchar(100) DEFAULT 'USC',
  `image` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_university_featured` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('draft','review','published','archived') NOT NULL DEFAULT 'published',
  `published_at` datetime DEFAULT current_timestamp(),
  `scheduled_at` datetime DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `published_by` int(11) DEFAULT NULL,
  `scheduled_by` int(11) DEFAULT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `title`, `slug`, `excerpt`, `content`, `category`, `label`, `image`, `is_featured`, `status`, `published_at`, `scheduled_at`, `review_note`, `published_by`, `scheduled_by`, `academic_year_id`, `created_at`, `updated_at`, `deleted_at`, `deleted_by`) VALUES
(1, 'Welcome to the USC Student Services and Updates Portal', 'welcome-to-the-usc-student-services-and-updates-portal', 'The USC main page is now your central source for student-service announcements, campus updates, E-Sumbong notices, TALA publications, and university-wide information.', 'Welcome to the USC Student Services and Updates Portal.', 'usc', 'USC Main', NULL, 1, 'published', '2026-08-15 09:00:00', NULL, NULL, NULL, NULL, 1, '2026-08-15 10:10:33', '2026-08-19 23:22:41', NULL, NULL),
(2, 'E-Sumbong opens a direct channel for student concerns and feedback', 'e-sumbong-opens-a-direct-channel-for-student-concerns-and-feedback', 'Students can submit concerns, complaints, suggestions, and feedback and receive a tracking reference.', 'E-Sumbong is now available through the USC portal.', 'usc', 'Student Services', NULL, 0, 'published', '2026-08-14 09:00:00', NULL, NULL, NULL, NULL, 1, '2026-08-15 10:10:33', '2026-08-19 23:22:41', NULL, NULL),
(3, 'MLUC launches its dedicated campus student-services page', 'mluc-launches-its-dedicated-campus-student-services-page', 'Mid La Union Campus now has a separate red-themed portal connected to the USC main site.', 'MLUC campus page announcement.', 'mluc', 'MLUC', NULL, 0, 'published', '2026-08-13 09:00:00', NULL, NULL, NULL, NULL, 1, '2026-08-15 10:10:33', '2026-08-19 23:22:41', NULL, NULL),
(4, 'Wow', 'wow', 'Nice', 'Wowwww', 'usc', 'USC Main', NULL, 1, 'published', '2026-08-15 13:02:00', NULL, NULL, NULL, NULL, 1, '2026-08-15 11:02:42', '2026-08-19 23:22:41', NULL, NULL),
(5, 'Nice', 'nice', 'WWWW', 'SSSSSS', 'nluc', 'NLUC Main', NULL, 0, 'published', '2026-08-15 13:03:00', NULL, NULL, NULL, NULL, 1, '2026-08-15 11:04:07', '2026-08-19 23:22:41', NULL, NULL),
(6, 'SHHHH', 'shhhh', 'DSADADA', 'DSDSDSDSD', 'mluc', 'MLUC Main', NULL, 0, 'published', '2026-08-15 13:04:00', NULL, NULL, NULL, NULL, 1, '2026-08-15 11:04:25', '2026-08-19 23:22:41', NULL, NULL),
(7, 'SSSSDDD', 'ssssddd', 'DADASDA', 'SDASDASDASD', 'ous', 'OUS Main', NULL, 0, 'published', '2026-08-15 13:04:00', NULL, NULL, NULL, NULL, 1, '2026-08-15 11:04:41', '2026-08-19 23:22:41', NULL, NULL),
(8, 'SDASDSADSA', 'sdasdsadsa', 'DASDASDASD', 'SADASDASDA', 'sluc', 'SLUC Main', NULL, 0, 'published', '2026-08-15 13:04:00', NULL, NULL, NULL, NULL, 1, '2026-08-15 11:04:54', '2026-08-19 23:22:41', NULL, NULL),
(10, '𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫', 'publication', 'Subsequent to the election of former Chairperson Michael Dean M. Malonzo to the University Student Council (USC), and pursuant to CSBO Resolution No. 2, Series of 2026–2027, former Vice Chairperson Edrian D. Rocaberte assumed the office of Chairperson of the CSBO. Due to the said succession, the Office of the Vice Chairperson became vacant.\r\n\r\nConsequently, in accordance with the Student Handbook, Article XI - Vacancies and Succession, Section 2, a Special Election was held on July 15, 2026, during which Hermes Aldrich J. Mendoza was elected as the new Vice Chairperson to fill the said vacancy.\r\n\r\nThrough the implementation of succession process and conduct of Special Election, the CSBO is further reiterating its stand on having a consistent and stable leadership that will provide quality service to the SLUC studentry for the academic year 2026-2027.\r\n\r\n#CSBOxCSC\r\n#TatakSLUCian', '', 'sluc', 'SLUC', NULL, 1, 'published', '2026-08-15 13:16:00', NULL, '', 6, NULL, 1, '2026-08-15 11:16:57', '2026-08-20 10:38:08', NULL, NULL),
(11, '𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋', 'publication-2', 'The University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.', 'Isang Mapagpalayang Araw.\r\n\r\nThe University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\r\n\r\nPlease be informed that the USC has been actively coordinating and communicating with the University Administration regarding these concerns. We recognize the importance of ensuring the safety and welfare of all students, faculty members, and personnel, especially during adverse weather conditions.\r\n\r\nHowever, we would also like to clarify that the declaration of class suspensions is governed by existing university policies, guidelines, and protocols. Such decisions undergo proper assessment and due process and cannot be implemented solely upon request or preference. While the USC serves as the voice of the student body and continuously relays student concerns to the appropriate offices, the authority to declare suspensions remains subject to established institutional procedures.\r\n\r\nFurthermore, we would like to clarify that the anonymous post currently circulating regarding this matter did not originate from the University Student Council. As a student government, we uphold the principles of transparency, accountability, and responsible communication. We release official statements through our authorized platforms and ensure that all information disseminated by the USC bears proper accountability and representation.\r\n\r\nIn the meantime, the USC strongly encourages students to prioritize their safety. If you believe that traveling to campus poses a risk to your well-being due to weather conditions or other related circumstances, please exercise your judgment accordingly. We advise affected students to communicate with their respective instructors and respectfully seek consideration regarding their situation.\r\n\r\nThe USC remains committed to monitoring developments, relaying student concerns to the administration, and advocating for the welfare of the student body. We ask everyone to remain vigilant, stay informed through official university channels, and prioritize safety at all times.', 'usc', 'USC', NULL, 1, 'published', '2026-08-15 13:22:00', NULL, '', 1, NULL, 1, '2026-08-15 11:23:42', '2026-08-20 05:31:49', NULL, NULL),
(15, 'E-Sumbong opens a direct channel for student concerns and feedback', 'e-sumbong-opens-a-direct-channel-for-student-concerns-and-feedback-2', 'Students can submit concerns, complaints, suggestions, and feedback and receive a tracking reference.', 'E-Sumbong is now available through the USC portal.', 'usc', 'Student Services', NULL, 0, 'published', '2026-08-14 09:00:00', NULL, NULL, NULL, NULL, 1, '2026-08-19 10:25:08', '2026-08-19 23:22:41', NULL, NULL),
(17, 'Welcome to the USC Student Services and Updates Portal', 'welcome-to-the-usc-student-services-and-updates-portal-2', 'The USC main page is now your central source for student-service announcements, campus updates, E-Sumbong notices and university-wide information.', 'Welcome to the USC Student Services and Updates Portal.', 'usc', 'USC Main', NULL, 1, 'published', '2026-08-15 09:00:00', NULL, NULL, NULL, NULL, 1, '2026-08-19 10:39:26', '2026-08-19 23:22:41', NULL, NULL),
(29, 'Welcome to the USC Student Services and Updates Portal', NULL, 'The USC main page is now your central source for student-service announcements, campus updates, E-Sumbong notices and university-wide information.', 'Welcome to the USC Student Services and Updates Portal.', 'usc', 'USC Main', NULL, 1, 'published', '2026-08-15 09:00:00', NULL, NULL, NULL, NULL, NULL, '2026-08-20 12:19:57', '2026-08-20 12:19:57', NULL, NULL),
(30, 'E-Sumbong opens a direct channel for student concerns and feedback', NULL, 'Students can submit concerns, complaints, suggestions, and feedback and receive a tracking reference.', 'E-Sumbong is now available through the USC portal.', 'usc', 'Student Services', NULL, 0, 'published', '2026-08-14 09:00:00', NULL, NULL, NULL, NULL, NULL, '2026-08-20 12:19:57', '2026-08-20 12:19:57', NULL, NULL),
(31, 'MLUC launches its dedicated campus student-services page', NULL, 'Mid La Union Campus now has a separate red-themed portal connected to the USC main site.', 'MLUC campus page announcement.', 'mluc', 'MLUC', NULL, 0, 'published', '2026-08-13 09:00:00', NULL, NULL, NULL, NULL, NULL, '2026-08-20 12:19:57', '2026-08-20 12:19:57', NULL, NULL),
(32, 'Welcome to the USC Student Services and Updates Portal', NULL, 'The USC main page is now your central source for student-service announcements, campus updates, E-Sumbong notices and university-wide information.', 'Welcome to the USC Student Services and Updates Portal.', 'usc', 'USC Main', NULL, 1, 'published', '2026-08-15 09:00:00', NULL, NULL, NULL, NULL, NULL, '2026-08-20 12:24:39', '2026-08-20 12:24:39', NULL, NULL),
(33, 'E-Sumbong opens a direct channel for student concerns and feedback', NULL, 'Students can submit concerns, complaints, suggestions, and feedback and receive a tracking reference.', 'E-Sumbong is now available through the USC portal.', 'usc', 'Student Services', NULL, 0, 'published', '2026-08-14 09:00:00', NULL, NULL, NULL, NULL, NULL, '2026-08-20 12:24:39', '2026-08-20 12:24:39', NULL, NULL),
(34, 'MLUC launches its dedicated campus student-services page', NULL, 'Mid La Union Campus now has a separate red-themed portal connected to the USC main site.', 'MLUC campus page announcement.', 'mluc', 'MLUC', NULL, 0, 'published', '2026-08-13 09:00:00', NULL, NULL, NULL, NULL, NULL, '2026-08-20 12:24:39', '2026-08-20 12:24:39', NULL, NULL),
(35, 'Welcome to the USC Student Services and Updates Portal', NULL, 'The USC main page is now your central source for student-service announcements, campus updates, E-Sumbong notices and university-wide information.', 'Welcome to the USC Student Services and Updates Portal.', 'usc', 'USC Main', NULL, 1, 'published', '2026-08-15 09:00:00', NULL, NULL, NULL, NULL, NULL, '2026-08-20 12:47:56', '2026-08-20 12:47:56', NULL, NULL),
(36, 'E-Sumbong opens a direct channel for student concerns and feedback', NULL, 'Students can submit concerns, complaints, suggestions, and feedback and receive a tracking reference.', 'E-Sumbong is now available through the USC portal.', 'usc', 'Student Services', NULL, 0, 'published', '2026-08-14 09:00:00', NULL, NULL, NULL, NULL, NULL, '2026-08-20 12:47:56', '2026-08-20 12:47:56', NULL, NULL),
(37, 'MLUC launches its dedicated campus student-services page', NULL, 'Mid La Union Campus now has a separate red-themed portal connected to the USC main site.', 'MLUC campus page announcement.', 'mluc', 'MLUC', NULL, 0, 'published', '2026-08-13 09:00:00', NULL, NULL, NULL, NULL, NULL, '2026-08-20 12:47:56', '2026-08-20 12:47:56', NULL, NULL),
(38, 'Welcome to the USC Student Services and Updates Portal', NULL, 'The USC main page is now your central source for student-service announcements, campus updates, E-Sumbong notices and university-wide information.', 'Welcome to the USC Student Services and Updates Portal.', 'usc', 'USC Main', NULL, 1, 'published', '2026-08-15 09:00:00', NULL, NULL, NULL, NULL, NULL, '2026-08-20 12:49:39', '2026-08-20 12:49:39', NULL, NULL),
(39, 'E-Sumbong opens a direct channel for student concerns and feedback', NULL, 'Students can submit concerns, complaints, suggestions, and feedback and receive a tracking reference.', 'E-Sumbong is now available through the USC portal.', 'usc', 'Student Services', NULL, 0, 'published', '2026-08-14 09:00:00', NULL, NULL, NULL, NULL, NULL, '2026-08-20 12:49:39', '2026-08-20 12:49:39', NULL, NULL),
(40, 'MLUC launches its dedicated campus student-services page', NULL, 'Mid La Union Campus now has a separate red-themed portal connected to the USC main site.', 'MLUC campus page announcement.', 'mluc', 'MLUC', NULL, 0, 'published', '2026-08-13 09:00:00', NULL, NULL, NULL, NULL, NULL, '2026-08-20 12:49:39', '2026-08-20 12:49:39', NULL, NULL);

-- University-wide Featured placement is intentionally separate from each portal's local Featured setting.
-- USC or the System Administrator selects stories for the public All Featured section from the admin interface.

-- --------------------------------------------------------

--
-- Table structure for table `post_images`
--

CREATE TABLE `post_images` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `media_id` int(11) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_images`
--

INSERT INTO `post_images` (`id`, `post_id`, `file_path`, `original_name`, `media_id`, `sort_order`, `created_at`) VALUES
(37, 11, 'uploads/posts/post_11_f847ce647630b875.jpg', '761747527_1526379522835180_6287043047617718657_n.jpg', NULL, 0, '2026-08-16 04:42:05'),
(38, 10, 'uploads/posts/post_10_bf07da6f66e6625d.jpg', '749300662_993920943483283_6310725844511423514_n.jpg', NULL, 0, '2026-08-16 04:42:15'),
(39, 3, 'uploads/posts/post_3_1937072533b16a31.jpg', '774868512_1535642465242219_9039202900835858923_n (1).jpg', NULL, 0, '2026-08-16 04:42:25'),
(40, 7, 'uploads/posts/post_7_23b9f93bcd1e2a1b.jpg', '764845230_1528522139287585_374589350318008315_n.jpg', NULL, 0, '2026-08-16 04:42:35'),
(41, 6, 'uploads/posts/post_6_45a3320d5ba6329b.jpg', '764845230_1528522139287585_374589350318008315_n.jpg', NULL, 0, '2026-08-16 04:43:35'),
(42, 5, 'uploads/posts/post_5_a821879f5a61867e.jpg', '774868512_1535642465242219_9039202900835858923_n (1).jpg', NULL, 0, '2026-08-16 04:43:47'),
(43, 2, 'uploads/posts/post_2_2f3f7ee69a714800.jpg', '761747527_1526379522835180_6287043047617718657_n.jpg', NULL, 0, '2026-08-16 04:44:30'),
(44, 1, 'uploads/posts/post_1_a6237a240d2e7e11.jpg', '764845230_1528522139287585_374589350318008315_n.jpg', NULL, 0, '2026-08-16 04:44:38'),
(45, 4, 'uploads/posts/post_4_cde073ad8cd8b0e6.jpg', '774868512_1535642465242219_9039202900835858923_n (1).jpg', NULL, 0, '2026-08-16 04:44:45'),
(46, 8, 'uploads/posts/post_8_f88361b4a0a5b87f.jpg', '774868512_1535642465242219_9039202900835858923_n (1).jpg', NULL, 0, '2026-08-16 04:44:56');

-- --------------------------------------------------------

--
-- Table structure for table `post_videos`
--

CREATE TABLE `post_videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `media_id` int(11) DEFAULT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_post_videos_post_id` (`post_id`),
  KEY `idx_post_videos_media_id` (`media_id`),
  CONSTRAINT `usc_fk_post_videos_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `usc_fk_post_videos_media` FOREIGN KEY (`media_id`) REFERENCES `media_library` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_revisions`
--

CREATE TABLE `post_revisions` (
  `id` bigint(20) NOT NULL,
  `post_id` int(11) NOT NULL,
  `revision_no` int(11) NOT NULL,
  `snapshot_json` longtext NOT NULL,
  `change_note` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_revisions`
--

INSERT INTO `post_revisions` (`id`, `post_id`, `revision_no`, `snapshot_json`, `change_note`, `created_by`, `created_at`) VALUES
(1, 11, 1, '{\"post\":{\"id\":11,\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"slug\":\"publication-2\",\"excerpt\":\"Isang Mapagpalayang Araw.\\r\\n\\r\\nThe University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\\r\\n\\r\\nPlease be informed that the USC has been actively coordinating and communicating with the University Administration regarding these concerns. We recognize the importance of ensuring the safety and welfare of all students, faculty members, and personnel, especially during adverse weather conditions.\\r\\n\\r\\nHowever, we would also like to clarify that the declaration of class suspensions is governed by existing university policies, guidelines, and protocols. Such decisions undergo proper assessment and due process and cannot be implemented solely upon request or preference. While the USC serves as the voice of the student body and continuously relays student concerns to the appropriate offices, the authority to declare suspensions remains subject to established institutional procedures.\\r\\n\\r\\nFurthermore, we would like to clarify that the anonymous post currently circulating regarding this matter did not originate from the University Student Council. As a student government, we uphold the principles of transparency, accountability, and responsible communication. We release official statements through our authorized platforms and ensure that all information disseminated by the USC bears proper accountability and representation.\\r\\n\\r\\nIn the meantime, the USC strongly encourages students to prioritize their safety. If you believe that traveling to campus poses a risk to your well-being due to weather conditions or other related circumstances, please exercise your judgment accordingly. We advise affected students to communicate with their respective instructors and respectfully seek consideration regarding their situation.\\r\\n\\r\\nThe USC remains committed to monitoring developments, relaying student concerns to the administration, and advocating for the welfare of the student body. We ask everyone to remain vigilant, stay informed through official university channels, and prioritize safety at all times.\",\"content\":\"\",\"category\":\"usc\",\"label\":\"USC Main\",\"image\":null,\"is_featured\":1,\"status\":\"published\",\"published_at\":\"2026-08-15 13:22:00\",\"scheduled_at\":null,\"review_note\":null,\"published_by\":null,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:23:42\",\"updated_at\":\"2026-08-20 07:22:41\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":37,\"post_id\":11,\"file_path\":\"uploads/posts/post_11_f847ce647630b875.jpg\",\"original_name\":\"761747527_1526379522835180_6287043047617718657_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:05\"}]}', 'Snapshot before edit', 1, '2026-08-20 05:04:08'),
(2, 11, 2, '{\"post\":{\"id\":11,\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"slug\":\"publication-2\",\"excerpt\":\"Isang Mapagpalayang Araw.\\r\\n\\r\\nThe University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\",\"content\":\"Isang Mapagpalayang Araw.\\r\\n\\r\\nThe University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\\r\\n\\r\\nPlease be informed that the USC has been actively coordinating and communicating with the University Administration regarding these concerns. We recognize the importance of ensuring the safety and welfare of all students, faculty members, and personnel, especially during adverse weather conditions.\\r\\n\\r\\nHowever, we would also like to clarify that the declaration of class suspensions is governed by existing university policies, guidelines, and protocols. Such decisions undergo proper assessment and due process and cannot be implemented solely upon request or preference. While the USC serves as the voice of the student body and continuously relays student concerns to the appropriate offices, the authority to declare suspensions remains subject to established institutional procedures.\\r\\n\\r\\nFurthermore, we would like to clarify that the anonymous post currently circulating regarding this matter did not originate from the University Student Council. As a student government, we uphold the principles of transparency, accountability, and responsible communication. We release official statements through our authorized platforms and ensure that all information disseminated by the USC bears proper accountability and representation.\\r\\n\\r\\nIn the meantime, the USC strongly encourages students to prioritize their safety. If you believe that traveling to campus poses a risk to your well-being due to weather conditions or other related circumstances, please exercise your judgment accordingly. We advise affected students to communicate with their respective instructors and respectfully seek consideration regarding their situation.\\r\\n\\r\\nThe USC remains committed to monitoring developments, relaying student concerns to the administration, and advocating for the welfare of the student body. We ask everyone to remain vigilant, stay informed through official university channels, and prioritize safety at all times.\",\"category\":\"usc\",\"label\":\"USC Main\",\"image\":null,\"is_featured\":1,\"status\":\"published\",\"published_at\":\"2026-08-15 13:22:00\",\"scheduled_at\":null,\"review_note\":\"\",\"published_by\":1,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:23:42\",\"updated_at\":\"2026-08-20 13:04:08\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":37,\"post_id\":11,\"file_path\":\"uploads/posts/post_11_f847ce647630b875.jpg\",\"original_name\":\"761747527_1526379522835180_6287043047617718657_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:05\"}]}', 'Saved publication', 1, '2026-08-20 05:04:08'),
(3, 11, 3, '{\"post\":{\"id\":11,\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"slug\":\"publication-2\",\"excerpt\":\"Isang Mapagpalayang Araw.\\r\\n\\r\\nThe University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\",\"content\":\"Isang Mapagpalayang Araw.\\r\\n\\r\\nThe University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\\r\\n\\r\\nPlease be informed that the USC has been actively coordinating and communicating with the University Administration regarding these concerns. We recognize the importance of ensuring the safety and welfare of all students, faculty members, and personnel, especially during adverse weather conditions.\\r\\n\\r\\nHowever, we would also like to clarify that the declaration of class suspensions is governed by existing university policies, guidelines, and protocols. Such decisions undergo proper assessment and due process and cannot be implemented solely upon request or preference. While the USC serves as the voice of the student body and continuously relays student concerns to the appropriate offices, the authority to declare suspensions remains subject to established institutional procedures.\\r\\n\\r\\nFurthermore, we would like to clarify that the anonymous post currently circulating regarding this matter did not originate from the University Student Council. As a student government, we uphold the principles of transparency, accountability, and responsible communication. We release official statements through our authorized platforms and ensure that all information disseminated by the USC bears proper accountability and representation.\\r\\n\\r\\nIn the meantime, the USC strongly encourages students to prioritize their safety. If you believe that traveling to campus poses a risk to your well-being due to weather conditions or other related circumstances, please exercise your judgment accordingly. We advise affected students to communicate with their respective instructors and respectfully seek consideration regarding their situation.\\r\\n\\r\\nThe USC remains committed to monitoring developments, relaying student concerns to the administration, and advocating for the welfare of the student body. We ask everyone to remain vigilant, stay informed through official university channels, and prioritize safety at all times.\",\"category\":\"usc\",\"label\":\"USC Main\",\"image\":null,\"is_featured\":1,\"status\":\"published\",\"published_at\":\"2026-08-15 13:22:00\",\"scheduled_at\":null,\"review_note\":\"\",\"published_by\":1,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:23:42\",\"updated_at\":\"2026-08-20 13:04:08\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":37,\"post_id\":11,\"file_path\":\"uploads/posts/post_11_f847ce647630b875.jpg\",\"original_name\":\"761747527_1526379522835180_6287043047617718657_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:05\"}]}', 'Snapshot before edit', 1, '2026-08-20 05:04:38'),
(4, 11, 4, '{\"post\":{\"id\":11,\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"slug\":\"publication-2\",\"excerpt\":\"The University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\",\"content\":\"Isang Mapagpalayang Araw.\\r\\n\\r\\nThe University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\\r\\n\\r\\nPlease be informed that the USC has been actively coordinating and communicating with the University Administration regarding these concerns. We recognize the importance of ensuring the safety and welfare of all students, faculty members, and personnel, especially during adverse weather conditions.\\r\\n\\r\\nHowever, we would also like to clarify that the declaration of class suspensions is governed by existing university policies, guidelines, and protocols. Such decisions undergo proper assessment and due process and cannot be implemented solely upon request or preference. While the USC serves as the voice of the student body and continuously relays student concerns to the appropriate offices, the authority to declare suspensions remains subject to established institutional procedures.\\r\\n\\r\\nFurthermore, we would like to clarify that the anonymous post currently circulating regarding this matter did not originate from the University Student Council. As a student government, we uphold the principles of transparency, accountability, and responsible communication. We release official statements through our authorized platforms and ensure that all information disseminated by the USC bears proper accountability and representation.\\r\\n\\r\\nIn the meantime, the USC strongly encourages students to prioritize their safety. If you believe that traveling to campus poses a risk to your well-being due to weather conditions or other related circumstances, please exercise your judgment accordingly. We advise affected students to communicate with their respective instructors and respectfully seek consideration regarding their situation.\\r\\n\\r\\nThe USC remains committed to monitoring developments, relaying student concerns to the administration, and advocating for the welfare of the student body. We ask everyone to remain vigilant, stay informed through official university channels, and prioritize safety at all times.\",\"category\":\"usc\",\"label\":\"USC Main\",\"image\":null,\"is_featured\":1,\"status\":\"published\",\"published_at\":\"2026-08-15 13:22:00\",\"scheduled_at\":null,\"review_note\":\"\",\"published_by\":1,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:23:42\",\"updated_at\":\"2026-08-20 13:04:38\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":37,\"post_id\":11,\"file_path\":\"uploads/posts/post_11_f847ce647630b875.jpg\",\"original_name\":\"761747527_1526379522835180_6287043047617718657_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:05\"}]}', 'Saved publication', 1, '2026-08-20 05:04:39'),
(5, 11, 5, '{\"post\":{\"id\":11,\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"slug\":\"publication-2\",\"excerpt\":\"The University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\",\"content\":\"Isang Mapagpalayang Araw.\\r\\n\\r\\nThe University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\\r\\n\\r\\nPlease be informed that the USC has been actively coordinating and communicating with the University Administration regarding these concerns. We recognize the importance of ensuring the safety and welfare of all students, faculty members, and personnel, especially during adverse weather conditions.\\r\\n\\r\\nHowever, we would also like to clarify that the declaration of class suspensions is governed by existing university policies, guidelines, and protocols. Such decisions undergo proper assessment and due process and cannot be implemented solely upon request or preference. While the USC serves as the voice of the student body and continuously relays student concerns to the appropriate offices, the authority to declare suspensions remains subject to established institutional procedures.\\r\\n\\r\\nFurthermore, we would like to clarify that the anonymous post currently circulating regarding this matter did not originate from the University Student Council. As a student government, we uphold the principles of transparency, accountability, and responsible communication. We release official statements through our authorized platforms and ensure that all information disseminated by the USC bears proper accountability and representation.\\r\\n\\r\\nIn the meantime, the USC strongly encourages students to prioritize their safety. If you believe that traveling to campus poses a risk to your well-being due to weather conditions or other related circumstances, please exercise your judgment accordingly. We advise affected students to communicate with their respective instructors and respectfully seek consideration regarding their situation.\\r\\n\\r\\nThe USC remains committed to monitoring developments, relaying student concerns to the administration, and advocating for the welfare of the student body. We ask everyone to remain vigilant, stay informed through official university channels, and prioritize safety at all times.\",\"category\":\"usc\",\"label\":\"USC Main\",\"image\":null,\"is_featured\":1,\"status\":\"published\",\"published_at\":\"2026-08-15 13:22:00\",\"scheduled_at\":null,\"review_note\":\"\",\"published_by\":1,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:23:42\",\"updated_at\":\"2026-08-20 13:04:38\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":37,\"post_id\":11,\"file_path\":\"uploads/posts/post_11_f847ce647630b875.jpg\",\"original_name\":\"761747527_1526379522835180_6287043047617718657_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:05\"}]}', 'Snapshot before edit', 1, '2026-08-20 05:18:51'),
(6, 11, 6, '{\"post\":{\"id\":11,\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"slug\":\"publication-2\",\"excerpt\":\"The University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\",\"content\":\"Isang Mapagpalayang Araw.\\r\\n\\r\\nThe University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\\r\\n\\r\\nPlease be informed that the USC has been actively coordinating and communicating with the University Administration regarding these concerns. We recognize the importance of ensuring the safety and welfare of all students, faculty members, and personnel, especially during adverse weather conditions.\\r\\n\\r\\nHowever, we would also like to clarify that the declaration of class suspensions is governed by existing university policies, guidelines, and protocols. Such decisions undergo proper assessment and due process and cannot be implemented solely upon request or preference. While the USC serves as the voice of the student body and continuously relays student concerns to the appropriate offices, the authority to declare suspensions remains subject to established institutional procedures.\\r\\n\\r\\nFurthermore, we would like to clarify that the anonymous post currently circulating regarding this matter did not originate from the University Student Council. As a student government, we uphold the principles of transparency, accountability, and responsible communication. We release official statements through our authorized platforms and ensure that all information disseminated by the USC bears proper accountability and representation.\\r\\n\\r\\nIn the meantime, the USC strongly encourages students to prioritize their safety. If you believe that traveling to campus poses a risk to your well-being due to weather conditions or other related circumstances, please exercise your judgment accordingly. We advise affected students to communicate with their respective instructors and respectfully seek consideration regarding their situation.\\r\\n\\r\\nThe USC remains committed to monitoring developments, relaying student concerns to the administration, and advocating for the welfare of the student body. We ask everyone to remain vigilant, stay informed through official university channels, and prioritize safety at all times.\",\"category\":\"usc\",\"label\":\"USC Main\",\"image\":null,\"is_featured\":1,\"status\":\"published\",\"published_at\":\"2026-08-15 13:22:00\",\"scheduled_at\":null,\"review_note\":\"\",\"published_by\":1,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:23:42\",\"updated_at\":\"2026-08-20 13:04:38\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":37,\"post_id\":11,\"file_path\":\"uploads/posts/post_11_f847ce647630b875.jpg\",\"original_name\":\"761747527_1526379522835180_6287043047617718657_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:05\"}]}', 'Saved publication', 1, '2026-08-20 05:18:51'),
(7, 11, 7, '{\"post\":{\"id\":11,\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"slug\":\"publication-2\",\"excerpt\":\"The University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\",\"content\":\"Isang Mapagpalayang Araw.\\r\\n\\r\\nThe University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\\r\\n\\r\\nPlease be informed that the USC has been actively coordinating and communicating with the University Administration regarding these concerns. We recognize the importance of ensuring the safety and welfare of all students, faculty members, and personnel, especially during adverse weather conditions.\\r\\n\\r\\nHowever, we would also like to clarify that the declaration of class suspensions is governed by existing university policies, guidelines, and protocols. Such decisions undergo proper assessment and due process and cannot be implemented solely upon request or preference. While the USC serves as the voice of the student body and continuously relays student concerns to the appropriate offices, the authority to declare suspensions remains subject to established institutional procedures.\\r\\n\\r\\nFurthermore, we would like to clarify that the anonymous post currently circulating regarding this matter did not originate from the University Student Council. As a student government, we uphold the principles of transparency, accountability, and responsible communication. We release official statements through our authorized platforms and ensure that all information disseminated by the USC bears proper accountability and representation.\\r\\n\\r\\nIn the meantime, the USC strongly encourages students to prioritize their safety. If you believe that traveling to campus poses a risk to your well-being due to weather conditions or other related circumstances, please exercise your judgment accordingly. We advise affected students to communicate with their respective instructors and respectfully seek consideration regarding their situation.\\r\\n\\r\\nThe USC remains committed to monitoring developments, relaying student concerns to the administration, and advocating for the welfare of the student body. We ask everyone to remain vigilant, stay informed through official university channels, and prioritize safety at all times.\",\"category\":\"usc\",\"label\":\"USC Main\",\"image\":null,\"is_featured\":1,\"status\":\"published\",\"published_at\":\"2026-08-15 13:22:00\",\"scheduled_at\":null,\"review_note\":\"\",\"published_by\":1,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:23:42\",\"updated_at\":\"2026-08-20 13:04:38\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":37,\"post_id\":11,\"file_path\":\"uploads/posts/post_11_f847ce647630b875.jpg\",\"original_name\":\"761747527_1526379522835180_6287043047617718657_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:05\"}]}', 'Snapshot before edit', 1, '2026-08-20 05:31:49'),
(8, 11, 8, '{\"post\":{\"id\":11,\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"slug\":\"publication-2\",\"excerpt\":\"The University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\",\"content\":\"Isang Mapagpalayang Araw.\\r\\n\\r\\nThe University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\\r\\n\\r\\nPlease be informed that the USC has been actively coordinating and communicating with the University Administration regarding these concerns. We recognize the importance of ensuring the safety and welfare of all students, faculty members, and personnel, especially during adverse weather conditions.\\r\\n\\r\\nHowever, we would also like to clarify that the declaration of class suspensions is governed by existing university policies, guidelines, and protocols. Such decisions undergo proper assessment and due process and cannot be implemented solely upon request or preference. While the USC serves as the voice of the student body and continuously relays student concerns to the appropriate offices, the authority to declare suspensions remains subject to established institutional procedures.\\r\\n\\r\\nFurthermore, we would like to clarify that the anonymous post currently circulating regarding this matter did not originate from the University Student Council. As a student government, we uphold the principles of transparency, accountability, and responsible communication. We release official statements through our authorized platforms and ensure that all information disseminated by the USC bears proper accountability and representation.\\r\\n\\r\\nIn the meantime, the USC strongly encourages students to prioritize their safety. If you believe that traveling to campus poses a risk to your well-being due to weather conditions or other related circumstances, please exercise your judgment accordingly. We advise affected students to communicate with their respective instructors and respectfully seek consideration regarding their situation.\\r\\n\\r\\nThe USC remains committed to monitoring developments, relaying student concerns to the administration, and advocating for the welfare of the student body. We ask everyone to remain vigilant, stay informed through official university channels, and prioritize safety at all times.\",\"category\":\"usc\",\"label\":\"USC\",\"image\":null,\"is_featured\":1,\"status\":\"published\",\"published_at\":\"2026-08-15 13:22:00\",\"scheduled_at\":null,\"review_note\":\"\",\"published_by\":1,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:23:42\",\"updated_at\":\"2026-08-20 13:31:49\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":37,\"post_id\":11,\"file_path\":\"uploads/posts/post_11_f847ce647630b875.jpg\",\"original_name\":\"761747527_1526379522835180_6287043047617718657_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:05\"}]}', 'Saved publication', 1, '2026-08-20 05:31:49'),
(9, 11, 9, '{\"post\":{\"id\":11,\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"slug\":\"publication-2\",\"excerpt\":\"The University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\",\"content\":\"Isang Mapagpalayang Araw.\\r\\n\\r\\nThe University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\\r\\n\\r\\nPlease be informed that the USC has been actively coordinating and communicating with the University Administration regarding these concerns. We recognize the importance of ensuring the safety and welfare of all students, faculty members, and personnel, especially during adverse weather conditions.\\r\\n\\r\\nHowever, we would also like to clarify that the declaration of class suspensions is governed by existing university policies, guidelines, and protocols. Such decisions undergo proper assessment and due process and cannot be implemented solely upon request or preference. While the USC serves as the voice of the student body and continuously relays student concerns to the appropriate offices, the authority to declare suspensions remains subject to established institutional procedures.\\r\\n\\r\\nFurthermore, we would like to clarify that the anonymous post currently circulating regarding this matter did not originate from the University Student Council. As a student government, we uphold the principles of transparency, accountability, and responsible communication. We release official statements through our authorized platforms and ensure that all information disseminated by the USC bears proper accountability and representation.\\r\\n\\r\\nIn the meantime, the USC strongly encourages students to prioritize their safety. If you believe that traveling to campus poses a risk to your well-being due to weather conditions or other related circumstances, please exercise your judgment accordingly. We advise affected students to communicate with their respective instructors and respectfully seek consideration regarding their situation.\\r\\n\\r\\nThe USC remains committed to monitoring developments, relaying student concerns to the administration, and advocating for the welfare of the student body. We ask everyone to remain vigilant, stay informed through official university channels, and prioritize safety at all times.\",\"category\":\"usc\",\"label\":\"USC\",\"image\":null,\"is_featured\":1,\"status\":\"published\",\"published_at\":\"2026-08-15 13:22:00\",\"scheduled_at\":null,\"review_note\":\"\",\"published_by\":1,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:23:42\",\"updated_at\":\"2026-08-20 13:31:49\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":37,\"post_id\":11,\"file_path\":\"uploads/posts/post_11_f847ce647630b875.jpg\",\"original_name\":\"761747527_1526379522835180_6287043047617718657_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:05\"}]}', 'Snapshot before edit', 1, '2026-08-20 05:32:02'),
(10, 11, 10, '{\"post\":{\"id\":11,\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"slug\":\"publication-2\",\"excerpt\":\"The University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\",\"content\":\"Isang Mapagpalayang Araw.\\r\\n\\r\\nThe University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\\r\\n\\r\\nPlease be informed that the USC has been actively coordinating and communicating with the University Administration regarding these concerns. We recognize the importance of ensuring the safety and welfare of all students, faculty members, and personnel, especially during adverse weather conditions.\\r\\n\\r\\nHowever, we would also like to clarify that the declaration of class suspensions is governed by existing university policies, guidelines, and protocols. Such decisions undergo proper assessment and due process and cannot be implemented solely upon request or preference. While the USC serves as the voice of the student body and continuously relays student concerns to the appropriate offices, the authority to declare suspensions remains subject to established institutional procedures.\\r\\n\\r\\nFurthermore, we would like to clarify that the anonymous post currently circulating regarding this matter did not originate from the University Student Council. As a student government, we uphold the principles of transparency, accountability, and responsible communication. We release official statements through our authorized platforms and ensure that all information disseminated by the USC bears proper accountability and representation.\\r\\n\\r\\nIn the meantime, the USC strongly encourages students to prioritize their safety. If you believe that traveling to campus poses a risk to your well-being due to weather conditions or other related circumstances, please exercise your judgment accordingly. We advise affected students to communicate with their respective instructors and respectfully seek consideration regarding their situation.\\r\\n\\r\\nThe USC remains committed to monitoring developments, relaying student concerns to the administration, and advocating for the welfare of the student body. We ask everyone to remain vigilant, stay informed through official university channels, and prioritize safety at all times.\",\"category\":\"usc\",\"label\":\"USC\",\"image\":null,\"is_featured\":1,\"status\":\"published\",\"published_at\":\"2026-08-15 13:22:00\",\"scheduled_at\":null,\"review_note\":\"\",\"published_by\":1,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:23:42\",\"updated_at\":\"2026-08-20 13:31:49\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":37,\"post_id\":11,\"file_path\":\"uploads/posts/post_11_f847ce647630b875.jpg\",\"original_name\":\"761747527_1526379522835180_6287043047617718657_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:05\"}]}', 'Saved publication', 1, '2026-08-20 05:32:02'),
(11, 11, 11, '{\"post\":{\"id\":11,\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"slug\":\"publication-2\",\"excerpt\":\"The University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\",\"content\":\"Isang Mapagpalayang Araw.\\r\\n\\r\\nThe University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\\r\\n\\r\\nPlease be informed that the USC has been actively coordinating and communicating with the University Administration regarding these concerns. We recognize the importance of ensuring the safety and welfare of all students, faculty members, and personnel, especially during adverse weather conditions.\\r\\n\\r\\nHowever, we would also like to clarify that the declaration of class suspensions is governed by existing university policies, guidelines, and protocols. Such decisions undergo proper assessment and due process and cannot be implemented solely upon request or preference. While the USC serves as the voice of the student body and continuously relays student concerns to the appropriate offices, the authority to declare suspensions remains subject to established institutional procedures.\\r\\n\\r\\nFurthermore, we would like to clarify that the anonymous post currently circulating regarding this matter did not originate from the University Student Council. As a student government, we uphold the principles of transparency, accountability, and responsible communication. We release official statements through our authorized platforms and ensure that all information disseminated by the USC bears proper accountability and representation.\\r\\n\\r\\nIn the meantime, the USC strongly encourages students to prioritize their safety. If you believe that traveling to campus poses a risk to your well-being due to weather conditions or other related circumstances, please exercise your judgment accordingly. We advise affected students to communicate with their respective instructors and respectfully seek consideration regarding their situation.\\r\\n\\r\\nThe USC remains committed to monitoring developments, relaying student concerns to the administration, and advocating for the welfare of the student body. We ask everyone to remain vigilant, stay informed through official university channels, and prioritize safety at all times.\",\"category\":\"usc\",\"label\":\"USC\",\"image\":null,\"is_featured\":1,\"status\":\"published\",\"published_at\":\"2026-08-15 13:22:00\",\"scheduled_at\":null,\"review_note\":\"\",\"published_by\":1,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:23:42\",\"updated_at\":\"2026-08-20 13:31:49\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":37,\"post_id\":11,\"file_path\":\"uploads/posts/post_11_f847ce647630b875.jpg\",\"original_name\":\"761747527_1526379522835180_6287043047617718657_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:05\"}]}', 'Snapshot before edit', 1, '2026-08-20 05:32:14'),
(12, 11, 12, '{\"post\":{\"id\":11,\"title\":\"𝐎𝐅𝐅𝐈𝐂𝐈𝐀𝐋 𝐒𝐓𝐀𝐓𝐄𝐌𝐄𝐍𝐓 𝐎𝐅 𝐓𝐇𝐄 𝐔𝐍𝐈𝐕𝐄𝐑𝐒𝐈𝐓𝐘 𝐒𝐓𝐔𝐃𝐄𝐍𝐓 𝐂𝐎𝐔𝐍𝐂𝐈𝐋\",\"slug\":\"publication-2\",\"excerpt\":\"The University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\",\"content\":\"Isang Mapagpalayang Araw.\\r\\n\\r\\nThe University Student Council (USC) acknowledges the concerns raised by students regarding the current weather conditions and the possibility of class suspension due to heavy rainfall.\\r\\n\\r\\nPlease be informed that the USC has been actively coordinating and communicating with the University Administration regarding these concerns. We recognize the importance of ensuring the safety and welfare of all students, faculty members, and personnel, especially during adverse weather conditions.\\r\\n\\r\\nHowever, we would also like to clarify that the declaration of class suspensions is governed by existing university policies, guidelines, and protocols. Such decisions undergo proper assessment and due process and cannot be implemented solely upon request or preference. While the USC serves as the voice of the student body and continuously relays student concerns to the appropriate offices, the authority to declare suspensions remains subject to established institutional procedures.\\r\\n\\r\\nFurthermore, we would like to clarify that the anonymous post currently circulating regarding this matter did not originate from the University Student Council. As a student government, we uphold the principles of transparency, accountability, and responsible communication. We release official statements through our authorized platforms and ensure that all information disseminated by the USC bears proper accountability and representation.\\r\\n\\r\\nIn the meantime, the USC strongly encourages students to prioritize their safety. If you believe that traveling to campus poses a risk to your well-being due to weather conditions or other related circumstances, please exercise your judgment accordingly. We advise affected students to communicate with their respective instructors and respectfully seek consideration regarding their situation.\\r\\n\\r\\nThe USC remains committed to monitoring developments, relaying student concerns to the administration, and advocating for the welfare of the student body. We ask everyone to remain vigilant, stay informed through official university channels, and prioritize safety at all times.\",\"category\":\"usc\",\"label\":\"USC\",\"image\":null,\"is_featured\":1,\"status\":\"published\",\"published_at\":\"2026-08-15 13:22:00\",\"scheduled_at\":null,\"review_note\":\"\",\"published_by\":1,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:23:42\",\"updated_at\":\"2026-08-20 13:31:49\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":37,\"post_id\":11,\"file_path\":\"uploads/posts/post_11_f847ce647630b875.jpg\",\"original_name\":\"761747527_1526379522835180_6287043047617718657_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:05\"}]}', 'Saved publication', 1, '2026-08-20 05:32:14'),
(13, 10, 1, '{\"post\":{\"id\":10,\"title\":\"𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫\",\"slug\":\"publication\",\"excerpt\":\"Subsequent to the election of former Chairperson Michael Dean M. Malonzo to the University Student Council (USC), and pursuant to CSBO Resolution No. 2, Series of 2026–2027, former Vice Chairperson Edrian D. Rocaberte assumed the office of Chairperson of the CSBO. Due to the said succession, the Office of the Vice Chairperson became vacant.\\r\\n\\r\\nConsequently, in accordance with the Student Handbook, Article XI - Vacancies and Succession, Section 2, a Special Election was held on July 15, 2026, during which Hermes Aldrich J. Mendoza was elected as the new Vice Chairperson to fill the said vacancy.\\r\\n\\r\\nThrough the implementation of succession process and conduct of Special Election, the CSBO is further reiterating its stand on having a consistent and stable leadership that will provide quality service to the SLUC studentry for the academic year 2026-2027.\\r\\n\\r\\n#CSBOxCSC\\r\\n#TatakSLUCian\",\"content\":\"\",\"category\":\"sluc\",\"label\":\"SLUC Main\",\"image\":null,\"is_featured\":1,\"status\":\"published\",\"published_at\":\"2026-08-15 13:16:00\",\"scheduled_at\":null,\"review_note\":null,\"published_by\":null,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:16:57\",\"updated_at\":\"2026-08-20 07:22:41\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":38,\"post_id\":10,\"file_path\":\"uploads/posts/post_10_bf07da6f66e6625d.jpg\",\"original_name\":\"749300662_993920943483283_6310725844511423514_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:15\"}]}', 'Snapshot before edit', 6, '2026-08-20 05:33:11'),
(14, 10, 2, '{\"post\":{\"id\":10,\"title\":\"𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫\",\"slug\":\"publication\",\"excerpt\":\"Subsequent to the election of former Chairperson Michael Dean M. Malonzo to the University Student Council (USC), and pursuant to CSBO Resolution No. 2, Series of 2026–2027, former Vice Chairperson Edrian D. Rocaberte assumed the office of Chairperson of the CSBO. Due to the said succession, the Office of the Vice Chairperson became vacant.\\r\\n\\r\\nConsequently, in accordance with the Student Handbook, Article XI - Vacancies and Succession, Section 2, a Special Election was held on July 15, 2026, during which Hermes Aldrich J. Mendoza was elected as the new Vice Chairperson to fill the said vacancy.\\r\\n\\r\\nThrough the implementation of succession process and conduct of Special Election, the CSBO is further reiterating its stand on having a consistent and stable leadership that will provide quality service to the SLUC studentry for the academic year 2026-2027.\\r\\n\\r\\n#CSBOxCSC\\r\\n#TatakSLUCian\",\"content\":\"\",\"category\":\"sluc\",\"label\":\"SLUC\",\"image\":null,\"is_featured\":1,\"status\":\"review\",\"published_at\":\"2026-08-15 13:16:00\",\"scheduled_at\":null,\"review_note\":\"\",\"published_by\":null,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:16:57\",\"updated_at\":\"2026-08-20 13:33:11\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":38,\"post_id\":10,\"file_path\":\"uploads/posts/post_10_bf07da6f66e6625d.jpg\",\"original_name\":\"749300662_993920943483283_6310725844511423514_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:15\"}]}', 'Saved publication', 6, '2026-08-20 05:33:11'),
(15, 10, 3, '{\"post\":{\"id\":10,\"title\":\"𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫\",\"slug\":\"publication\",\"excerpt\":\"Subsequent to the election of former Chairperson Michael Dean M. Malonzo to the University Student Council (USC), and pursuant to CSBO Resolution No. 2, Series of 2026–2027, former Vice Chairperson Edrian D. Rocaberte assumed the office of Chairperson of the CSBO. Due to the said succession, the Office of the Vice Chairperson became vacant.\\r\\n\\r\\nConsequently, in accordance with the Student Handbook, Article XI - Vacancies and Succession, Section 2, a Special Election was held on July 15, 2026, during which Hermes Aldrich J. Mendoza was elected as the new Vice Chairperson to fill the said vacancy.\\r\\n\\r\\nThrough the implementation of succession process and conduct of Special Election, the CSBO is further reiterating its stand on having a consistent and stable leadership that will provide quality service to the SLUC studentry for the academic year 2026-2027.\\r\\n\\r\\n#CSBOxCSC\\r\\n#TatakSLUCian\",\"content\":\"\",\"category\":\"sluc\",\"label\":\"SLUC\",\"image\":null,\"is_featured\":1,\"status\":\"review\",\"published_at\":\"2026-08-15 13:16:00\",\"scheduled_at\":null,\"review_note\":\"\",\"published_by\":null,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:16:57\",\"updated_at\":\"2026-08-20 13:33:11\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":38,\"post_id\":10,\"file_path\":\"uploads/posts/post_10_bf07da6f66e6625d.jpg\",\"original_name\":\"749300662_993920943483283_6310725844511423514_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:15\"}]}', 'Snapshot before edit', 6, '2026-08-20 10:38:08'),
(16, 10, 4, '{\"post\":{\"id\":10,\"title\":\"𝐑𝐨𝐜𝐚𝐛𝐞𝐫𝐭𝐞 𝐒𝐮𝐜𝐜𝐞𝐞𝐝𝐬 𝐚𝐬 𝐂𝐒𝐁𝐎 𝐂𝐡𝐚𝐢𝐫𝐩𝐞𝐫𝐬𝐨𝐧; 𝐌𝐞𝐧𝐝𝐨𝐳𝐚 𝐄𝐥𝐞𝐜𝐭𝐞𝐝 𝐚𝐬 𝐍𝐞𝐰 𝐕𝐢𝐜𝐞 𝐂𝐡𝐚𝐢𝐫\",\"slug\":\"publication\",\"excerpt\":\"Subsequent to the election of former Chairperson Michael Dean M. Malonzo to the University Student Council (USC), and pursuant to CSBO Resolution No. 2, Series of 2026–2027, former Vice Chairperson Edrian D. Rocaberte assumed the office of Chairperson of the CSBO. Due to the said succession, the Office of the Vice Chairperson became vacant.\\r\\n\\r\\nConsequently, in accordance with the Student Handbook, Article XI - Vacancies and Succession, Section 2, a Special Election was held on July 15, 2026, during which Hermes Aldrich J. Mendoza was elected as the new Vice Chairperson to fill the said vacancy.\\r\\n\\r\\nThrough the implementation of succession process and conduct of Special Election, the CSBO is further reiterating its stand on having a consistent and stable leadership that will provide quality service to the SLUC studentry for the academic year 2026-2027.\\r\\n\\r\\n#CSBOxCSC\\r\\n#TatakSLUCian\",\"content\":\"\",\"category\":\"sluc\",\"label\":\"SLUC\",\"image\":null,\"is_featured\":1,\"status\":\"published\",\"published_at\":\"2026-08-15 13:16:00\",\"scheduled_at\":null,\"review_note\":\"\",\"published_by\":6,\"scheduled_by\":null,\"academic_year_id\":1,\"created_at\":\"2026-08-15 19:16:57\",\"updated_at\":\"2026-08-20 18:38:08\",\"deleted_at\":null,\"deleted_by\":null},\"images\":[{\"id\":38,\"post_id\":10,\"file_path\":\"uploads/posts/post_10_bf07da6f66e6625d.jpg\",\"original_name\":\"749300662_993920943483283_6310725844511423514_n.jpg\",\"media_id\":null,\"sort_order\":0,\"created_at\":\"2026-08-16 12:42:15\"}]}', 'Saved publication', 6, '2026-08-20 10:38:08');

-- --------------------------------------------------------

--
-- Table structure for table `privacy_access_log`
--

CREATE TABLE `privacy_access_log` (
  `id` bigint(20) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `concern_id` int(11) NOT NULL,
  `action` varchar(60) NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `privacy_access_log`
--

INSERT INTO `privacy_access_log` (`id`, `admin_id`, `concern_id`, `action`, `purpose`, `ip_address`, `created_at`) VALUES
(1, 1, 1, 'Exported identity', 'Authorized E-Sumbong case export', '::1', '2026-08-19 04:04:07'),
(2, 1, 1, 'Viewed student identity', 'Case review', '::1', '2026-08-19 06:22:31'),
(3, 1, 1, 'Viewed student identity', 'Case review', '::1', '2026-08-19 06:25:09'),
(4, 1, 1, 'Viewed student identity', 'Case review', '::1', '2026-08-19 06:27:37'),
(5, 1, 1, 'Viewed student identity', 'Case review', '::1', '2026-08-19 06:29:56'),
(6, 1, 2, 'Viewed student identity', 'Case review', '::1', '2026-08-19 11:45:25'),
(7, 6, 2, 'Viewed student identity', 'Case review', '::1', '2026-08-19 11:53:02'),
(8, 1, 3, 'Viewed student identity', 'Case review', '::1', '2026-08-19 12:21:12'),
(9, 1, 2, 'Downloaded concern evidence', 'Case evidence review', '::1', '2026-08-20 00:53:48'),
(10, 1, 1, 'Reviewed retention status', 'Retain record', '::1', '2026-08-20 09:07:08'),
(11, 1, 2, 'Viewed student identity', 'Case review', '::1', '2026-08-20 09:07:30'),
(12, 1, 2, 'Viewed student identity', 'Case review', '::1', '2026-08-20 11:30:16'),
(13, 6, 2, 'Viewed student identity', 'Case review', '::1', '2026-08-20 11:33:40');

-- --------------------------------------------------------

--
-- Table structure for table `role_permission_overrides`
--

CREATE TABLE `role_permission_overrides` (
  `role` varchar(40) NOT NULL,
  `permission` varchar(100) NOT NULL,
  `allowed` tinyint(1) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `version` varchar(30) NOT NULL,
  `description` varchar(255) NOT NULL,
  `safety_backup_filename` varchar(255) DEFAULT NULL,
  `applied_by` int(11) DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `result` varchar(30) NOT NULL DEFAULT 'applied',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schema_migrations`
--

INSERT INTO `schema_migrations` (`version`, `description`, `safety_backup_filename`, `applied_by`, `duration_ms`, `result`, `applied_at`) VALUES
('2026081900', 'Consolidate legacy runtime schema upgrades into the explicit Migration Center', NULL, 1, NULL, 'applied', '2026-08-19 04:00:47'),
('2026081901', 'Administration reliability, case workflow, privacy, media verification, and backup metadata', NULL, 1, NULL, 'applied', '2026-08-19 04:00:48'),
('2026081902', 'Operational defaults, SLA targets, retention settings, and starter response templates', NULL, 1, NULL, 'applied', '2026-08-19 04:00:48'),
('2026081903', 'Reporting and audit performance indexes', NULL, 1, NULL, 'applied', '2026-08-19 04:00:48'),
('2026081905', 'Restore global upload-limit scope after Media Library-specific limit correction', NULL, 1, NULL, 'applied', '2026-08-19 07:23:44'),
('2026081906', 'Add relational foreign keys for administration, concerns, media, privacy, and recovery records', 'dmmmsu_usc_20260820_072238_database.sql', 1, NULL, 'applied', '2026-08-19 23:22:38'),
('2026081907', 'Connect login-attempt security records to administrator accounts', 'dmmmsu_usc_20260820_072238_database.sql', 1, NULL, 'applied', '2026-08-19 23:22:38'),
('2026081908', 'Repair any partially imported foreign-key relationships without deleting records', 'dmmmsu_usc_20260820_072238_database.sql', 1, NULL, 'applied', '2026-08-19 23:22:38'),
('2026081909', 'Advanced E-Sumbong privacy, secure tracking, evidence attachments, publication revisions, recovery codes, and backup verification', 'dmmmsu_usc_20260820_072238_database.sql', 1, NULL, 'applied', '2026-08-19 23:22:40'),
('2026081910', 'Performance indexes for public newsroom search and operational dashboards', 'dmmmsu_usc_20260820_072238_database.sql', 1, NULL, 'applied', '2026-08-19 23:22:40'),
('2026081911', 'Optional encrypted student contact email for E-Sumbong status notifications', 'dmmmsu_usc_20260820_072238_database.sql', 1, NULL, 'applied', '2026-08-19 23:22:40'),
('2026081912', 'Pending-by-default administrator account approval policy', 'dmmmsu_usc_20260820_072238_database.sql', 1, NULL, 'applied', '2026-08-19 23:22:40'),
('2026081913', 'Governance, academic-year history, configurable permissions, announcements, login history, storage optimization, and disaster-recovery metadata', 'dmmmsu_usc_20260820_072238_database.sql', 1, 956, 'applied', '2026-08-19 23:22:41'),
('2026081914', 'Create a current academic year plus a legacy bucket and classify existing records without deleting or rewriting content', 'dmmmsu_usc_20260820_072238_database.sql', 1, 40, 'applied', '2026-08-19 23:22:41'),
('2026081915', 'Backfill tamper-evident audit hash chain for existing activity records', 'dmmmsu_usc_20260820_072238_database.sql', 1, 283, 'applied', '2026-08-19 23:22:41'),
('2026081916', 'Additional operational indexes for announcements, login security, search, and long-term university records', 'dmmmsu_usc_20260820_072238_database.sql', 1, 33, 'applied', '2026-08-19 23:22:41'),
('2026082001', 'Add optional officer portrait photos for the public Meet the Officers roster', 'dmmmsu_usc_20260820_095743_database.sql', 1, 61, 'applied', '2026-08-20 01:57:43');

-- --------------------------------------------------------

--
-- Table structure for table `submission_rate_limits`
--

CREATE TABLE `submission_rate_limits` (
  `bucket_key` char(64) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `window_started_at` datetime NOT NULL,
  `last_attempt_at` datetime NOT NULL,
  `blocked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `submission_rate_limits`
--

INSERT INTO `submission_rate_limits` (`bucket_key`, `admin_id`, `attempts`, `window_started_at`, `last_attempt_at`, `blocked_until`) VALUES
('173bc7bfbf6fb958e10e4ac251ff316493bfd92617996c7e1195f83770879111', NULL, 6, '2026-08-20 14:27:11', '2026-08-20 14:27:29', NULL),
('7be101e2b97a8f65ba11f2cc1a614602d0d318654ac10b17eecdf5fb3e385bb4', NULL, 2, '2026-08-20 16:01:50', '2026-08-20 16:14:06', NULL),
('bbb4319e0677cd560f1954877143bc5bb8a1399b5651365497d901f08bf4d26b', NULL, 1, '2026-08-20 16:36:19', '2026-08-20 16:36:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `updated_by`, `updated_at`) VALUES
('active_academic_year_id', '1', 1, '2026-08-20 04:13:46'),
('auto_backup_enabled', '0', 1, '2026-08-20 10:07:37'),
('auto_backup_interval_hours', '24', 1, '2026-08-20 10:07:37'),
('auto_backup_retention_count', '14', 1, '2026-08-20 10:07:37'),
('auto_backup_type', 'database', 1, '2026-08-20 10:07:37'),
('auto_publish_scheduled', '1', 1, '2026-08-20 10:07:37'),
('backup_reminder_days', '7', 1, '2026-08-19 04:11:21'),
('csp_enforcement', 'report-only', NULL, '2026-08-19 04:00:48'),
('default_upload_limit', '10', 1, '2026-08-19 04:11:21'),
('dormant_account_days', '90', 1, '2026-08-19 04:11:21'),
('email_from_address', 'usc@dmmmsu.edu.ph', 1, '2026-08-20 10:07:37'),
('email_from_name', 'DMMMSU USC', 1, '2026-08-20 10:07:37'),
('esumbong_attachment_max_files', '3', 1, '2026-08-20 10:07:37'),
('esumbong_attachment_max_mb', '5', 1, '2026-08-20 10:07:37'),
('esumbong_email_updates_enabled', '1', 1, '2026-08-20 10:07:37'),
('esumbong_sla_high_hours', '48', 1, '2026-08-19 04:11:21'),
('esumbong_sla_low_hours', '168', 1, '2026-08-19 04:11:21'),
('esumbong_suggestion_feedback_cooldown_hours', '72', NULL, '2026-09-05 11:04:00'),
('esumbong_sla_normal_hours', '120', 1, '2026-08-19 04:11:21'),
('esumbong_sla_urgent_hours', '24', 1, '2026-08-19 04:11:21'),
('esumbong_tracking_token_required', '1', NULL, '2026-08-19 23:22:39'),
('login_lockout_minutes', '5', 1, '2026-08-19 04:11:21'),
('login_max_attempts', '5', 1, '2026-08-19 04:11:21'),
('maintenance_last_backup_at', '', NULL, '2026-08-19 23:22:39'),
('maintenance_last_run_at', '2026-08-20 09:58:30', 1, '2026-08-20 01:58:30'),
('maintenance_last_verification_at', '', NULL, '2026-08-19 23:22:39'),
('maintenance_mode', '0', 1, '2026-08-19 04:11:21'),
('media_auto_optimize', '1', 1, '2026-08-20 10:07:37'),
('media_thumbnail_enabled', '1', 1, '2026-08-20 10:07:37'),
('notification_email_enabled', '0', 1, '2026-08-20 10:07:37'),
('notification_retention_days', '180', 1, '2026-08-20 10:07:37'),
('offsite_backup_enabled', '0', 1, '2026-08-20 10:07:37'),
('offsite_backup_path', '', 1, '2026-08-20 10:07:37'),
('password_history_count', '5', 1, '2026-08-20 10:07:37'),
('password_max_age_days', '180', 1, '2026-08-19 04:11:21'),
('portal_name', 'University Student Council', 1, '2026-08-19 04:11:21'),
('privacy_encrypt_new_identity', '1', NULL, '2026-08-19 23:22:39'),
('privacy_notice_version', '2026-08-19', NULL, '2026-08-19 04:00:48'),
('privacy_retention_days', '730', 1, '2026-08-19 04:11:21'),
('public_network_status', '1', NULL, '2026-08-19 23:22:40'),
('security_2fa_required_roles', 'admin', 1, '2026-08-20 10:07:37'),
('security_rate_limit_tracking_per_hour', '30', 1, '2026-08-20 10:07:37'),
('security_rate_limit_uploads_per_hour', '60', 1, '2026-08-20 10:07:37'),
('security_require_2fa_for_admin', '0', 1, '2026-08-19 04:11:21'),
('session_idle_hours', '12', 1, '2026-08-19 04:11:21'),
('storage_critical_percent', '95', 1, '2026-08-20 10:07:37'),
('storage_warning_percent', '85', 1, '2026-08-20 10:07:37'),
('university_name', 'Don Mariano Marcos Memorial State University', 1, '2026-08-19 04:11:21'),
('usc_email', 'usc@dmmmsu.edu.ph', 1, '2026-08-19 04:11:21'),
('usc_facebook', 'https://www.facebook.com/usc.dmmmsu', 1, '2026-08-19 04:11:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `label` (`label`),
  ADD KEY `idx_academic_year_active` (`is_active`,`is_archived`,`start_date`),
  ADD KEY `usc_fk_academic_created_by` (`created_by`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_admin_status_activity` (`status`,`last_activity_at`),
  ADD KEY `fk_admins_created_by` (`created_by`),
  ADD KEY `usc_fk_admins_approved_by` (`approved_by`),
  ADD KEY `idx_admin_lifecycle` (`status`,`status_changed_at`,`archived_at`);

--
-- Indexes for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_created` (`created_at`),
  ADD KEY `idx_activity_admin` (`admin_id`),
  ADD KEY `idx_activity_module` (`module`),
  ADD KEY `idx_activity_module_created` (`module`,`created_at`),
  ADD KEY `idx_activity_hash` (`record_hash`);

--
-- Indexes for table `admin_dashboard_widgets`
--
ALTER TABLE `admin_dashboard_widgets`
  ADD PRIMARY KEY (`admin_id`,`widget_key`);

--
-- Indexes for table `admin_login_attempts`
--
ALTER TABLE `admin_login_attempts`
  ADD PRIMARY KEY (`attempt_key`),
  ADD KEY `idx_admin_login_lock` (`locked_until`),
  ADD KEY `idx_admin_login_admin` (`admin_id`);

--
-- Indexes for table `admin_login_events`
--
ALTER TABLE `admin_login_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_events_admin` (`admin_id`,`created_at`),
  ADD KEY `idx_login_events_result` (`result`,`created_at`),
  ADD KEY `idx_login_events_ip` (`ip_address`,`created_at`);

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_admin` (`admin_id`,`is_read`),
  ADD KEY `idx_notifications_role` (`role_target`,`is_read`),
  ADD KEY `idx_notifications_campus` (`campus_target`,`is_read`),
  ADD KEY `idx_notification_group` (`group_key`,`created_at`),
  ADD KEY `idx_notification_category` (`category`,`is_read`,`created_at`),
  ADD KEY `idx_notifications_expiry` (`expires_at`,`dismissed_at`,`created_at`);

--
-- Indexes for table `admin_notification_preferences`
--
ALTER TABLE `admin_notification_preferences`
  ADD PRIMARY KEY (`admin_id`,`category`);

--
-- Indexes for table `admin_password_history`
--
ALTER TABLE `admin_password_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_history_admin` (`admin_id`,`created_at`);

--
-- Indexes for table `admin_permission_overrides`
--
ALTER TABLE `admin_permission_overrides`
  ADD PRIMARY KEY (`admin_id`,`permission`),
  ADD KEY `usc_fk_admin_permission_updated_by` (`updated_by`);

--
-- Indexes for table `admin_recovery_codes`
--
ALTER TABLE `admin_recovery_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recovery_admin` (`admin_id`,`used_at`);

--
-- Indexes for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_key_hash` (`session_key_hash`),
  ADD KEY `idx_admin_sessions_admin` (`admin_id`,`revoked_at`,`last_seen_at`),
  ADD KEY `idx_sessions_active_seen` (`revoked_at`,`last_seen_at`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_announcements_public` (`status`,`audience`,`portal_code`,`starts_at`,`ends_at`),
  ADD KEY `idx_announcements_year` (`academic_year_id`,`status`),
  ADD KEY `usc_fk_announcements_created_by` (`created_by`),
  ADD KEY `usc_fk_announcements_updated_by` (`updated_by`);

--
-- Indexes for table `backup_history`
--
ALTER TABLE `backup_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_backup_created` (`created_at`),
  ADD KEY `fk_backups_created_by` (`created_by`),
  ADD KEY `fk_backups_restored_by` (`restored_by`),
  ADD KEY `idx_backup_verify` (`verification_status`,`verified_at`);

--
-- Indexes for table `concerns`
--
ALTER TABLE `concerns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_code` (`reference_code`),
  ADD KEY `idx_concerns_source_portal` (`source_portal`),
  ADD KEY `idx_concerns_assigned_scope` (`assigned_scope`),
  ADD KEY `idx_concern_work_queue` (`assigned_scope`,`status`,`due_at`,`priority`),
  ADD KEY `idx_concern_assignee` (`assigned_to`,`status`,`due_at`),
  ADD KEY `idx_concern_created` (`created_at`),
  ADD KEY `idx_concern_tracking` (`reference_code`,`tracking_token_hash`),
  ADD KEY `idx_concern_privacy_review` (`privacy_review_status`,`retention_until`),
  ADD KEY `usc_fk_concern_privacy_reviewer` (`privacy_reviewed_by`),
  ADD KEY `idx_concern_status_created` (`status`,`created_at`),
  ADD KEY `idx_concern_followup` (`status`,`follow_up_at`),
  ADD KEY `idx_concerns_academic_year` (`academic_year_id`,`status`,`created_at`);

--
-- Indexes for table `concern_attachments`
--
ALTER TABLE `concern_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_concern_attachment_case` (`concern_id`,`created_at`),
  ADD KEY `usc_fk_concern_attachments_admin` (`uploaded_by`);

--
-- Indexes for table `concern_history`
--
ALTER TABLE `concern_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_concern_history` (`concern_id`,`created_at`),
  ADD KEY `fk_concern_history_admin` (`admin_id`),
  ADD KEY `fk_concern_history_assignee` (`assigned_to`);

--
-- Indexes for table `concern_private_identity`
--
ALTER TABLE `concern_private_identity`
  ADD PRIMARY KEY (`concern_id`);

--
-- Indexes for table `concern_response_templates`
--
ALTER TABLE `concern_response_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_response_template_scope` (`campus`,`is_active`),
  ADD KEY `fk_response_templates_created_by` (`created_by`);

--
-- Indexes for table `hero_promotion_requests`
--
ALTER TABLE `hero_promotion_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hero_promo_source` (`source_slide_id`,`source_portal`,`status`),
  ADD KEY `idx_hero_promo_target` (`target_portal`,`status`,`display_order`),
  ADD KEY `fk_promo_requested_by` (`requested_by`),
  ADD KEY `fk_promo_reviewed_by` (`reviewed_by`);

--
-- Indexes for table `hero_slides`
--
ALTER TABLE `hero_slides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hero_portal_order` (`portal_code`,`sort_order`,`id`),
  ADD KEY `fk_hero_panel_media` (`panel_media_id`),
  ADD KEY `fk_hero_background_media` (`background_media_id`);

--
-- Indexes for table `media_library`
--
ALTER TABLE `media_library`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_media_created` (`created_at`),
  ADD KEY `idx_media_campus` (`campus`),
  ADD KEY `idx_media_sha256` (`sha256`),
  ADD KEY `fk_media_uploaded_by` (`uploaded_by`),
  ADD KEY `fk_media_deleted_by` (`deleted_by`),
  ADD KEY `idx_media_optimized` (`optimized_at`,`created_at`);

--
-- Indexes for table `notification_email_outbox`
--
ALTER TABLE `notification_email_outbox`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_outbox` (`status`,`created_at`),
  ADD KEY `idx_email_outbox_admin` (`admin_id`,`created_at`),
  ADD KEY `idx_email_outbox_concern` (`concern_id`,`created_at`);

--
-- Indexes for table `officers`
--
ALTER TABLE `officers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_officers_scope_year` (`portal_code`,`academic_year_id`,`is_archived`,`sort_order`),
  ADD KEY `usc_fk_officers_year` (`academic_year_id`),
  ADD KEY `usc_fk_officers_created_by` (`created_by`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_posts_slug` (`slug`),
  ADD KEY `idx_posts_status_category_date` (`status`,`category`,`published_at`),
  ADD KEY `fk_posts_deleted_by` (`deleted_by`),
  ADD KEY `idx_posts_public_schedule` (`status`,`scheduled_at`,`published_at`,`category`),
  ADD KEY `usc_fk_posts_published_by` (`published_by`),
  ADD KEY `usc_fk_posts_scheduled_by` (`scheduled_by`),
  ADD KEY `idx_posts_category_status_slug` (`category`,`status`,`slug`),
  ADD KEY `idx_posts_academic_year` (`academic_year_id`,`status`,`published_at`),
  ADD KEY `idx_posts_university_featured` (`is_university_featured`,`status`,`published_at`);

--
-- Indexes for table `post_images`
--
ALTER TABLE `post_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_post_images_post_id` (`post_id`),
  ADD KEY `idx_post_images_media_id` (`media_id`);

--
-- Indexes for table `post_revisions`
--
ALTER TABLE `post_revisions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_post_revision` (`post_id`,`revision_no`),
  ADD KEY `idx_post_revision_created` (`post_id`,`created_at`),
  ADD KEY `usc_fk_post_revisions_admin` (`created_by`);

--
-- Indexes for table `privacy_access_log`
--
ALTER TABLE `privacy_access_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_privacy_concern` (`concern_id`,`created_at`),
  ADD KEY `idx_privacy_admin` (`admin_id`,`created_at`);

--
-- Indexes for table `role_permission_overrides`
--
ALTER TABLE `role_permission_overrides`
  ADD PRIMARY KEY (`role`,`permission`),
  ADD KEY `usc_fk_role_permission_updated_by` (`updated_by`);

--
-- Indexes for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  ADD PRIMARY KEY (`version`),
  ADD KEY `fk_migrations_applied_by` (`applied_by`);

--
-- Indexes for table `submission_rate_limits`
--
ALTER TABLE `submission_rate_limits`
  ADD PRIMARY KEY (`bucket_key`),
  ADD KEY `idx_rate_limit_admin` (`admin_id`,`last_attempt_at`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`),
  ADD KEY `fk_settings_updated_by` (`updated_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=426;

--
-- AUTO_INCREMENT for table `admin_login_events`
--
ALTER TABLE `admin_login_events`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `admin_password_history`
--
ALTER TABLE `admin_password_history`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_recovery_codes`
--
ALTER TABLE `admin_recovery_codes`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `backup_history`
--
ALTER TABLE `backup_history`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `concerns`
--
ALTER TABLE `concerns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `concern_attachments`
--
ALTER TABLE `concern_attachments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `concern_history`
--
ALTER TABLE `concern_history`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `concern_response_templates`
--
ALTER TABLE `concern_response_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `hero_promotion_requests`
--
ALTER TABLE `hero_promotion_requests`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `hero_slides`
--
ALTER TABLE `hero_slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `media_library`
--
ALTER TABLE `media_library`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_email_outbox`
--
ALTER TABLE `notification_email_outbox`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `officers`
--
ALTER TABLE `officers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `post_images`
--
ALTER TABLE `post_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `post_revisions`
--
ALTER TABLE `post_revisions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `privacy_access_log`
--
ALTER TABLE `privacy_access_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD CONSTRAINT `usc_fk_academic_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `fk_admins_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `usc_fk_admins_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD CONSTRAINT `fk_activity_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `admin_dashboard_widgets`
--
ALTER TABLE `admin_dashboard_widgets`
  ADD CONSTRAINT `usc_fk_dashboard_widgets_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `admin_login_attempts`
--
ALTER TABLE `admin_login_attempts`
  ADD CONSTRAINT `fk_login_attempts_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `admin_login_events`
--
ALTER TABLE `admin_login_events`
  ADD CONSTRAINT `usc_fk_login_events_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD CONSTRAINT `fk_notifications_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `admin_notification_preferences`
--
ALTER TABLE `admin_notification_preferences`
  ADD CONSTRAINT `fk_notification_preferences_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `admin_password_history`
--
ALTER TABLE `admin_password_history`
  ADD CONSTRAINT `usc_fk_password_history_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `admin_permission_overrides`
--
ALTER TABLE `admin_permission_overrides`
  ADD CONSTRAINT `usc_fk_admin_permission_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `usc_fk_admin_permission_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `admin_recovery_codes`
--
ALTER TABLE `admin_recovery_codes`
  ADD CONSTRAINT `usc_fk_recovery_codes_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD CONSTRAINT `fk_sessions_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `usc_fk_announcements_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `usc_fk_announcements_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `usc_fk_announcements_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `backup_history`
--
ALTER TABLE `backup_history`
  ADD CONSTRAINT `fk_backups_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_backups_restored_by` FOREIGN KEY (`restored_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `concerns`
--
ALTER TABLE `concerns`
  ADD CONSTRAINT `fk_concerns_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `usc_fk_concern_privacy_reviewer` FOREIGN KEY (`privacy_reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `usc_fk_concerns_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `concern_attachments`
--
ALTER TABLE `concern_attachments`
  ADD CONSTRAINT `usc_fk_concern_attachments_admin` FOREIGN KEY (`uploaded_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `usc_fk_concern_attachments_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `concern_history`
--
ALTER TABLE `concern_history`
  ADD CONSTRAINT `fk_concern_history_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_concern_history_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_concern_history_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `concern_private_identity`
--
ALTER TABLE `concern_private_identity`
  ADD CONSTRAINT `usc_fk_private_identity_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `concern_response_templates`
--
ALTER TABLE `concern_response_templates`
  ADD CONSTRAINT `fk_response_templates_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `hero_promotion_requests`
--
ALTER TABLE `hero_promotion_requests`
  ADD CONSTRAINT `fk_promo_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_promo_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `usc_fk_promo_source_slide` FOREIGN KEY (`source_slide_id`) REFERENCES `hero_slides` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `hero_slides`
--
ALTER TABLE `hero_slides`
  ADD CONSTRAINT `fk_hero_background_media` FOREIGN KEY (`background_media_id`) REFERENCES `media_library` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hero_panel_media` FOREIGN KEY (`panel_media_id`) REFERENCES `media_library` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `media_library`
--
ALTER TABLE `media_library`
  ADD CONSTRAINT `fk_media_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_media_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `notification_email_outbox`
--
ALTER TABLE `notification_email_outbox`
  ADD CONSTRAINT `usc_fk_email_outbox_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `usc_fk_email_outbox_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `officers`
--
ALTER TABLE `officers`
  ADD CONSTRAINT `usc_fk_officers_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `usc_fk_officers_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `fk_posts_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `usc_fk_posts_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `usc_fk_posts_published_by` FOREIGN KEY (`published_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `usc_fk_posts_scheduled_by` FOREIGN KEY (`scheduled_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `post_images`
--
ALTER TABLE `post_images`
  ADD CONSTRAINT `fk_post_images_media` FOREIGN KEY (`media_id`) REFERENCES `media_library` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_post_images_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_revisions`
--
ALTER TABLE `post_revisions`
  ADD CONSTRAINT `usc_fk_post_revisions_admin` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `usc_fk_post_revisions_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `privacy_access_log`
--
ALTER TABLE `privacy_access_log`
  ADD CONSTRAINT `fk_privacy_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_privacy_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `role_permission_overrides`
--
ALTER TABLE `role_permission_overrides`
  ADD CONSTRAINT `usc_fk_role_permission_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  ADD CONSTRAINT `fk_migrations_applied_by` FOREIGN KEY (`applied_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `submission_rate_limits`
--
ALTER TABLE `submission_rate_limits`
  ADD CONSTRAINT `usc_fk_rate_limit_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `fk_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

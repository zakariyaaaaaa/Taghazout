-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 20 mai 2026 à 04:40
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `taghazout`
--

-- --------------------------------------------------------

--
-- Structure de la table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `activities`
--

INSERT INTO `activities` (`id`, `name`, `description`, `price`, `duration`, `location`, `image`, `rating`, `created_at`) VALUES
(2, 'Yoga sur la plage', 'Séance de yoga au lever du soleil', 100.00, '1h', 'Anchor Point', 'act_6a0c5c619f4e4.jpg', 4.5, '2026-05-14 00:23:02'),
(3, 'Randonnée', 'Randonnée dans les collines d\'Agadir', 150.00, '4h', 'Taghazout', 'act_6a0c5c073a87f.jpg', 4.3, '2026-05-14 00:23:02'),
(4, 'Sandboarding', 'Vivez une expérience amusante en glissant sur les magnifiques dunes de sable.', 200.00, '1h', 'Taghazout', 'act_6a0c5bd0b422a.jpg', 4.2, '2026-05-16 12:49:25');

-- --------------------------------------------------------

--
-- Structure de la table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('hotel','surf') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `check_in` date DEFAULT NULL,
  `check_out` date DEFAULT NULL,
  `guests` int(11) DEFAULT 1,
  `total_price` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `type`, `reference_id`, `check_in`, `check_out`, `guests`, `total_price`, `status`, `created_at`) VALUES
(8, 2, 'hotel', 2, '2026-05-16', '2026-05-21', 1, 900.00, 'accepted', '2026-05-15 13:23:55'),
(9, 2, 'hotel', 2, '2026-05-22', '2026-05-30', 1, 1440.00, 'accepted', '2026-05-15 13:33:08'),
(10, 2, 'hotel', 1, '2026-05-23', '2026-05-27', 1, 1400.00, 'accepted', '2026-05-15 13:38:14'),
(11, 3, 'hotel', 2, '2026-05-16', '2026-05-17', 1, 180.00, 'accepted', '2026-05-16 09:10:15'),
(12, 3, 'hotel', 3, '2026-05-16', '2026-05-29', 8, 8450.00, 'accepted', '2026-05-16 09:36:27'),
(13, 3, 'hotel', 1, '2026-05-16', '2026-05-29', 1, 4550.00, 'accepted', '2026-05-16 09:37:39'),
(14, 3, 'surf', 1, '2026-05-16', '2026-05-17', 1, 300.00, 'accepted', '2026-05-16 09:52:37'),
(15, 3, 'hotel', 3, '2032-06-16', '2039-11-17', 1, 1761500.00, 'pending', '2026-05-16 09:54:41'),
(16, 4, 'surf', 1, '2026-05-30', '2026-06-02', 1, 900.00, 'pending', '2026-05-16 10:16:44'),
(17, 4, 'surf', 3, '2026-05-25', '2026-05-27', 1, 1200.00, 'pending', '2026-05-16 10:18:43'),
(18, 4, 'surf', 3, '2026-05-20', '2026-05-20', 1, 600.00, 'pending', '2026-05-16 10:54:18'),
(19, 4, 'surf', 3, '2026-05-26', '2026-05-26', 1, 600.00, 'pending', '2026-05-16 10:54:42'),
(20, 4, 'hotel', 2, '2026-05-20', '2026-05-29', 1, 1620.00, 'pending', '2026-05-16 10:56:23'),
(21, 4, 'surf', 1, '2026-05-16', '2026-05-16', 2, 600.00, 'pending', '2026-05-16 11:00:00'),
(22, 4, 'surf', 1, '2026-06-12', '2026-06-12', 1, 300.00, 'pending', '2026-05-16 11:04:12'),
(23, 4, 'surf', 1, '2026-05-25', '2026-05-25', 1, 300.00, 'pending', '2026-05-16 11:04:27'),
(24, 4, 'hotel', 2, '2026-06-01', '2026-06-02', 1, 180.00, 'rejected', '2026-05-16 11:11:41'),
(25, 4, 'surf', 1, '2026-05-23', '2026-05-23', 1, 300.00, 'accepted', '2026-05-16 11:17:42'),
(26, 4, '', 1, '2026-05-16', '2026-05-16', 1, 200.00, 'accepted', '2026-05-16 11:23:26'),
(27, 4, 'surf', 4, '2026-05-22', '2026-05-22', 1, 22.00, 'accepted', '2026-05-16 15:30:24'),
(28, 4, 'hotel', 3, '2026-07-08', '2028-10-11', 1, 536900.00, 'accepted', '2026-05-17 16:29:49'),
(29, 6, 'hotel', 3, '2026-05-20', '2026-10-29', 1, 105300.00, 'rejected', '2026-05-17 19:22:57'),
(30, 6, 'hotel', 3, '2026-05-17', '2026-05-18', 1, 650.00, 'accepted', '2026-05-17 19:23:31'),
(31, 6, 'hotel', 3, '2027-01-01', '2027-01-02', 1, 650.00, 'rejected', '2026-05-18 14:57:22'),
(32, 4, 'hotel', 5, '2026-05-18', '2026-05-19', 1, 1000.00, 'rejected', '2026-05-18 15:15:57'),
(33, 4, 'surf', 3, '2026-05-18', '2026-05-18', 1, 100.00, 'pending', '2026-05-18 19:44:19'),
(34, 4, 'surf', 5, '2026-05-18', '2026-05-18', 1, 1.00, 'pending', '2026-05-18 20:04:26'),
(35, 4, '', 5, '2026-05-19', '2026-05-19', 1, 200.00, 'accepted', '2026-05-18 20:49:53'),
(36, 4, '', 1, '2026-05-20', '2026-05-20', 1, 200.00, 'accepted', '2026-05-19 09:02:44'),
(37, 8, '', 3, '2026-05-19', '2026-05-19', 1, 150.00, 'accepted', '2026-05-19 17:12:35'),
(38, 8, 'hotel', 3, '2026-05-20', '2026-05-22', 1, 1300.00, 'pending', '2026-05-20 00:11:37');

-- --------------------------------------------------------

--
-- Structure de la table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('hotel','activity','surf','restaurant') DEFAULT NULL,
  `reference_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `type`, `reference_id`, `created_at`) VALUES
(6, 3, 'hotel', 1, '2026-05-18 17:58:11'),
(7, 4, 'surf', 5, '2026-05-18 20:04:40'),
(8, 4, 'hotel', 3, '2026-05-18 21:02:28'),
(9, 4, 'restaurant', 4, '2026-05-19 12:38:44'),
(10, 4, 'activity', 3, '2026-05-19 12:38:59'),
(11, 8, 'hotel', 3, '2026-05-20 00:26:00');

-- --------------------------------------------------------

--
-- Structure de la table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `category` enum('hotel','activity','surf','restaurant','general') DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `hotels`
--

CREATE TABLE `hotels` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `stars` int(11) DEFAULT 3,
  `type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `hotels`
--

INSERT INTO `hotels` (`id`, `name`, `description`, `price`, `location`, `image`, `rating`, `stars`, `type`, `created_at`) VALUES
(1, 'Hyatt Regency Taghazout', 'Hyatt Regency Taghazout offre une expérience de luxe en bord de mer avec des chambres élégantes, une ambiance raffinée et une vue exceptionnelle sur l’océan, idéale pour la détente et le confort à Taghazout.', 350.00, 'Taghazout', 'hotel_1778934602_312.jpg', 3.5, 4, 'riad', '2026-05-14 00:23:02'),
(2, 'Auberge Taghazout', 'Auberge Taghazout offre un séjour confortable dans une ambiance calme, à proximité de la plage, alliant détente et charme de Taghazout.', 180.00, 'Taghazout', 'hotel_1778934423_435.jpg', 3.0, 3, 'auberge', '2026-05-14 00:23:02'),
(3, 'Hilton Taghazout Bay', 'The Hilton Taghazout Bay Beach Resort & Spa is located in Taghazout Bay, Morocco, just 4 km from the village of Taghazout and 2 km from the Tazegzout Golf. The resort features 170 modern rooms, multiple dining options, a spa, and various recreational activities such as tennis courts and a children\'s club. Guests can enjoy a buffet breakfast, a variety of international cuisine, and access to a private beach. The hotel is well-equipped for families, offering amenities for both parents and children, making it an ideal choice for a relaxing and family-friendly vacation.', 650.00, 'Taghazout Bay', 'hotel_1778934036_733.jpg', 4.5, 5, 'villa', '2026-05-14 00:23:02');

-- --------------------------------------------------------

--
-- Structure de la table `loyalty_history`
--

CREATE TABLE `loyalty_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `loyalty_history`
--

INSERT INTO `loyalty_history` (`id`, `user_id`, `points`, `reason`, `created_at`) VALUES
(1, 3, 567, 'Réservation #1', '2026-05-15 09:22:19'),
(2, 3, 6, 'Réservation #2', '2026-05-15 09:58:50'),
(3, 3, 3, 'Réservation #3', '2026-05-15 11:22:21'),
(4, 2, 28, 'Réservation #4', '2026-05-15 11:23:55'),
(5, 2, 6, 'Réservation #5', '2026-05-15 11:47:22'),
(6, 2, 73, 'Réservation #6', '2026-05-15 12:01:25'),
(7, 2, 39, 'Réservation #7', '2026-05-15 13:16:00'),
(8, 2, 9, 'Réservation #8', '2026-05-15 13:23:55'),
(9, 2, 14, 'Réservation #9', '2026-05-15 13:33:08'),
(10, 2, 14, 'Réservation #10', '2026-05-15 13:38:14'),
(11, 2, 14, 'Réservation #10 confirmée', '2026-05-16 02:54:20'),
(12, 3, 1, 'Réservation #11', '2026-05-16 09:10:15'),
(13, 3, 84, 'Réservation #12', '2026-05-16 09:36:28'),
(14, 3, 45, 'Réservation #13', '2026-05-16 09:37:39'),
(15, 3, 3, 'Réservation surf #14', '2026-05-16 09:52:37'),
(16, 3, 17615, 'Réservation #15', '2026-05-16 09:54:41'),
(17, 4, 9, 'Réservation #16', '2026-05-16 10:16:44'),
(18, 4, 12, 'Réservation #17', '2026-05-16 10:18:43'),
(19, 4, 6, 'Réservation #18', '2026-05-16 10:54:18'),
(20, 4, 6, 'Réservation #19', '2026-05-16 10:54:42'),
(21, 4, 16, 'Réservation #20', '2026-05-16 10:56:23'),
(22, 4, 6, 'Réservation #21', '2026-05-16 11:00:00'),
(23, 4, 3, 'Réservation #22', '2026-05-16 11:04:12'),
(24, 4, 3, 'Réservation #23', '2026-05-16 11:04:27'),
(25, 4, 1, 'Réservation #24', '2026-05-16 11:11:41'),
(26, 4, 3, 'Réservation #25', '2026-05-16 11:17:42'),
(27, 4, 2, 'Réservation activité #26', '2026-05-16 11:23:26'),
(28, 3, 45, 'Réservation #13 confirmée', '2026-05-16 12:04:57'),
(29, 4, 5369, 'Réservation #28', '2026-05-17 16:29:49'),
(30, 6, 1053, 'Réservation #29', '2026-05-17 19:22:57'),
(31, 6, 6, 'Réservation #30', '2026-05-17 19:23:31'),
(32, 6, 6, 'Réservation #31', '2026-05-18 14:57:22'),
(33, 4, 10, 'Réservation #32', '2026-05-18 15:15:57'),
(34, 4, 1, 'Réservation #33', '2026-05-18 19:44:19'),
(35, 4, 2, 'Réservation activité #35', '2026-05-18 20:49:54'),
(36, 4, 10, 'Réservation #32 confirmée', '2026-05-18 20:59:54'),
(37, 4, 2, 'Réservation activité #36', '2026-05-19 09:02:44'),
(38, 8, 1, 'Réservation activité #37', '2026-05-19 17:12:35'),
(39, 8, 13, 'Réservation #38', '2026-05-20 00:11:37'),
(40, 8, 13, 'Réservation #38 confirmée', '2026-05-20 02:26:14');

-- --------------------------------------------------------

--
-- Structure de la table `loyalty_redemptions`
--

CREATE TABLE `loyalty_redemptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `coupon_code` varchar(20) NOT NULL,
  `points_used` int(11) NOT NULL,
  `status` enum('active','used','expired') DEFAULT 'active',
  `used_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `loyalty_rewards`
--

CREATE TABLE `loyalty_rewards` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('discount','free_night','free_surf','coupon') NOT NULL DEFAULT 'discount',
  `points_cost` int(11) NOT NULL,
  `value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `value_type` enum('fixed','percent') DEFAULT 'percent',
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `loyalty_rewards`
--

INSERT INTO `loyalty_rewards` (`id`, `title`, `description`, `type`, `points_cost`, `value`, `value_type`, `image`, `stock`, `is_active`, `created_at`) VALUES
(1, 'Réduction 10%', 'Obtenez 10% de réduction sur votre prochaine réservation hôtel.', 'discount', 100, 10.00, 'percent', NULL, NULL, 1, '2026-05-16 12:31:37'),
(2, 'Réduction 20%', 'Obtenez 20% de réduction sur votre prochaine réservation hôtel.', 'discount', 180, 20.00, 'percent', NULL, NULL, 1, '2026-05-16 12:31:37'),
(3, 'Réduction 50 MAD', '50 MAD de réduction sur votre prochaine réservation.', 'discount', 80, 50.00, 'fixed', NULL, NULL, 1, '2026-05-16 12:31:37'),
(4, 'Réduction 100 MAD', '100 MAD de réduction sur votre prochaine réservation.', 'discount', 150, 100.00, 'fixed', NULL, NULL, 1, '2026-05-16 12:31:37'),
(5, '1 Nuit Gratuite', 'Une nuit offerte dans un hôtel partenaire (valeur jusqu\'à 500 MAD).', 'free_night', 500, 500.00, 'fixed', NULL, NULL, 1, '2026-05-16 12:31:37'),
(6, 'Cours Surf Gratuit', 'Un cours de surf offert pour 1 personne.', 'free_surf', 300, 300.00, 'fixed', NULL, NULL, 1, '2026-05-16 12:31:37');

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 3, '✅ Réservation confirmée', 'Votre réservation #1 est en attente de confirmation.', 1, '2026-05-15 09:22:19'),
(2, 3, '✅ Réservation confirmée', 'Votre réservation #2 est en attente de confirmation.', 1, '2026-05-15 09:58:50'),
(3, 3, '✅ Réservation confirmée', 'Votre réservation #3 est en attente de confirmation.', 1, '2026-05-15 11:22:21'),
(4, 2, '✅ Réservation confirmée', 'Votre réservation #4 est en attente de confirmation.', 1, '2026-05-15 11:23:55'),
(5, 2, '✅ Réservation confirmée', 'Votre réservation #5 est en attente de confirmation.', 1, '2026-05-15 11:47:22'),
(6, 2, '✅ Réservation confirmée', 'Votre réservation #6 est en attente de confirmation.', 1, '2026-05-15 12:01:25'),
(7, 2, '✅ Réservation confirmée', 'Votre réservation #7 est en attente de confirmation.', 1, '2026-05-15 13:16:00'),
(8, 2, '✅ Réservation confirmée', 'Votre réservation #8 est en attente de confirmation.', 1, '2026-05-15 13:23:55'),
(9, 2, '✅ Paiement confirmé', 'Votre paiement pour la réservation #8 a été confirmé.', 1, '2026-05-15 13:31:12'),
(10, 2, '✅ Réservation confirmée', 'Votre réservation #9 est en attente de confirmation.', 1, '2026-05-15 13:33:08'),
(11, 2, '✅ Paiement confirmé', 'Votre paiement pour la réservation #9 a été confirmé.', 1, '2026-05-15 13:34:01'),
(12, 2, '✅ Réservation confirmée', 'Votre réservation #10 est en attente de confirmation.', 1, '2026-05-15 13:38:14'),
(13, 2, '✅ Paiement confirmé', 'Votre paiement pour la réservation #10 a été confirmé.', 1, '2026-05-15 13:39:09'),
(14, 2, '✅ Réservation confirmée', 'Votre réservation #10 a été confirmée par l\'administrateur.', 1, '2026-05-16 02:54:20'),
(15, 3, '✅ Réservation confirmée', 'Votre réservation #11 est en attente de confirmation.', 1, '2026-05-16 09:10:15'),
(16, 3, '✅ Paiement confirmé', 'Votre paiement pour la réservation #11 a été confirmé.', 1, '2026-05-16 09:10:32'),
(17, 3, '✅ Réservation confirmée', 'Votre réservation #12 est en attente de confirmation.', 1, '2026-05-16 09:36:28'),
(18, 3, '✅ Paiement confirmé', 'Votre paiement pour la réservation #12 a été confirmé.', 1, '2026-05-16 09:36:46'),
(19, 3, '✅ Réservation confirmée', 'Votre réservation #13 est en attente de confirmation.', 1, '2026-05-16 09:37:39'),
(20, 3, '???? Réservation surf confirmée', 'Votre réservation surf #14 est en attente de confirmation.', 1, '2026-05-16 09:52:37'),
(21, 3, '✅ Paiement confirmé', 'Votre paiement pour la réservation #14 a été confirmé.', 1, '2026-05-16 09:52:55'),
(22, 3, '✅ Réservation confirmée', 'Votre réservation #15 est en attente de confirmation.', 1, '2026-05-16 09:54:41'),
(23, 4, '✅ Réservation confirmée', 'Votre réservation #16 est en attente de confirmation.', 1, '2026-05-16 10:16:44'),
(24, 4, '✅ Réservation confirmée', 'Votre réservation #17 est en attente de confirmation.', 1, '2026-05-16 10:18:43'),
(25, 4, '???? Réservation surf reçue', 'Votre réservation #18 est en attente de confirmation.', 1, '2026-05-16 10:54:18'),
(26, 4, '???? Réservation surf reçue', 'Votre réservation #19 est en attente de confirmation.', 1, '2026-05-16 10:54:42'),
(27, 4, '✅ Réservation confirmée', 'Votre réservation #20 est en attente de confirmation.', 1, '2026-05-16 10:56:23'),
(28, 4, '???? Réservation surf reçue', 'Votre réservation #21 est en attente de confirmation.', 1, '2026-05-16 11:00:00'),
(29, 4, '???? Réservation surf reçue', 'Votre réservation #22 est en attente de confirmation.', 1, '2026-05-16 11:04:12'),
(30, 4, '???? Réservation surf reçue', 'Votre réservation #23 est en attente de confirmation.', 1, '2026-05-16 11:04:27'),
(31, 4, '✅ Réservation confirmée', 'Votre réservation #24 est en attente de confirmation.', 1, '2026-05-16 11:11:41'),
(32, 4, '???? Réservation surf reçue', 'Votre réservation #25 est en attente de confirmation.', 1, '2026-05-16 11:17:42'),
(33, 4, '✅ Paiement confirmé', 'Votre paiement pour la réservation #25 a été confirmé.', 1, '2026-05-16 11:17:56'),
(34, 4, '???? Réservation activité reçue', 'Votre réservation #26 est en attente de confirmation.', 1, '2026-05-16 11:23:26'),
(35, 4, '✅ Paiement confirmé', 'Votre paiement pour la réservation #26 a été confirmé.', 1, '2026-05-16 11:23:38'),
(36, 4, '❌ Réservation annulée', 'Votre réservation #24 pour « Auberge Taghazout » a été annulée.', 1, '2026-05-16 11:26:07'),
(37, 3, '✅ Réservation confirmée', 'Votre réservation #13 a été confirmée par l\'administrateur.', 1, '2026-05-16 12:04:57'),
(38, 4, '???? Réservation surf reçue', 'Votre réservation #27 est en attente de confirmation.', 1, '2026-05-16 15:30:24'),
(39, 4, '✅ Paiement confirmé', 'Votre paiement pour la réservation #27 a été confirmé.', 1, '2026-05-16 15:30:49'),
(40, 4, '✅ Réservation confirmée', 'Votre réservation #28 est en attente de confirmation.', 1, '2026-05-17 16:29:49'),
(41, 4, '✅ Paiement confirmé', 'Votre paiement pour la réservation #28 a été confirmé.', 1, '2026-05-17 16:30:01'),
(42, 6, '✅ Réservation confirmée', 'Votre réservation #29 est en attente de confirmation.', 1, '2026-05-17 19:22:57'),
(43, 6, '✅ Réservation confirmée', 'Votre réservation #30 est en attente de confirmation.', 1, '2026-05-17 19:23:31'),
(44, 6, '✅ Paiement confirmé', 'Votre paiement pour la réservation #30 a été confirmé.', 1, '2026-05-17 19:23:44'),
(45, 6, '✅ Réservation confirmée', 'Votre réservation #31 est en attente de confirmation.', 1, '2026-05-18 14:57:22'),
(46, 6, '❌ Réservation annulée', 'Votre réservation #29 pour « Hilton Taghazout Bay » a été annulée.', 1, '2026-05-18 14:58:09'),
(47, 4, '✅ Réservation confirmée', 'Votre réservation #32 est en attente de confirmation.', 1, '2026-05-18 15:15:57'),
(48, 4, '✅ Paiement confirmé', 'Votre paiement pour la réservation #32 a été confirmé.', 1, '2026-05-18 15:16:13'),
(49, 4, '❌ Réservation refusée', 'Votre réservation #32 pour «  » a été refusée. Contactez-nous pour plus d\'informations.', 1, '2026-05-18 15:19:07'),
(50, 4, '???? Réservation surf reçue', 'Votre réservation #33 est en attente de confirmation.', 1, '2026-05-18 19:44:19'),
(51, 4, '???? Réservation surf reçue', 'Votre réservation #34 est en attente de confirmation.', 1, '2026-05-18 20:04:26'),
(52, 4, '???? Réservation activité reçue', 'Votre réservation #35 est en attente de confirmation.', 1, '2026-05-18 20:49:54'),
(53, 4, '✅ Paiement confirmé', 'Votre paiement pour la réservation #35 a été confirmé.', 1, '2026-05-18 20:50:15'),
(54, 6, '❌ Réservation refusée', 'Votre réservation #31 a été refusée. Contactez-nous pour plus d\'informations.', 0, '2026-05-18 20:59:45'),
(55, 4, '✅ Réservation confirmée', 'Votre réservation #32 pour «  » a été confirmée. Vous avez gagné 10 points fidélité.', 1, '2026-05-18 20:59:54'),
(56, 4, '❌ Réservation refusée', 'Votre réservation #32 pour «  » a été refusée. Contactez-nous pour plus d\'informations.', 1, '2026-05-18 21:00:03'),
(57, 4, '???? Réservation activité reçue', 'Votre réservation #36 est en attente de confirmation.', 1, '2026-05-19 09:02:44'),
(58, 4, '✅ Paiement confirmé', 'Votre paiement pour la réservation #36 a été confirmé.', 1, '2026-05-19 09:03:15'),
(59, 8, '???? Réservation activité reçue', 'Votre réservation #37 est en attente de confirmation.', 1, '2026-05-19 17:12:35'),
(60, 8, '✅ Paiement confirmé', 'Votre paiement pour la réservation #37 a été confirmé.', 1, '2026-05-19 17:12:50'),
(61, 8, '✅ Réservation confirmée', 'Votre réservation #38 est en attente de confirmation.', 1, '2026-05-20 00:11:37'),
(62, 8, '✅ Réservation confirmée', 'Votre réservation #38 a été confirmée par l\'administrateur.', 0, '2026-05-20 02:26:14'),
(63, 8, '❌ Réservation refusée', 'Votre réservation #38 pour « Hilton Taghazout Bay » a été refusée. Contactez-nous pour plus d\'informations.', 0, '2026-05-20 02:26:27');

-- --------------------------------------------------------

--
-- Structure de la table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `method` enum('stripe','paypal','cash') DEFAULT 'cash',
  `status` enum('pending','paid','failed') DEFAULT 'pending',
  `transaction_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `restaurants`
--

CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `cuisine` varchar(100) DEFAULT NULL,
  `price_range` enum('cheap','moderate','expensive') DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `restaurants`
--

INSERT INTO `restaurants` (`id`, `name`, `description`, `cuisine`, `price_range`, `location`, `image`, `rating`, `created_at`) VALUES
(1, 'La Sqala', 'Restaurant vue sur mer', 'Marocaine', 'moderate', 'Taghazout', 'resto_6a0c6f5e6491e.jpg', 4.6, '2026-05-14 00:23:02'),
(2, 'Windy Bay Restaurant', 'Snacks et jus frais', 'Internationale', 'cheap', 'Taghazout Beach', 'resto_6a0c6e702e3a1.jpg', 4.3, '2026-05-14 00:23:02'),
(3, 'Ocean Breeze', 'Poissons frais et grillades', 'Fruits de mer', 'expensive', 'Taghazout Bay', 'resto_6a0c6def97467.jpg', 4.8, '2026-05-14 00:23:02');

-- --------------------------------------------------------

--
-- Structure de la table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('hotel','activity','surf','restaurant') DEFAULT NULL,
  `reference_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `type`, `reference_id`, `rating`, `comment`, `created_at`) VALUES
(1, 2, 'hotel', 1, 3, 'ddxdxdxxfdxfd', '2026-05-14 22:18:39'),
(2, 3, 'hotel', 3, 4, 'bien', '2026-05-15 08:59:36'),
(3, 6, 'hotel', 3, 5, 'Nice vues', '2026-05-18 14:56:11'),
(4, 8, 'hotel', 3, 5, 'Goood hotel ????????', '2026-05-20 00:31:09'),
(5, 8, 'hotel', 2, 3, '❤️❤️❤️❤️', '2026-05-20 00:32:18'),
(6, 8, 'hotel', 1, 4, '????????????????', '2026-05-20 00:32:44'),
(7, 2, 'hotel', 3, 4, 'Bravo ????????', '2026-05-20 00:36:48');

-- --------------------------------------------------------

--
-- Structure de la table `surf_courses`
--

CREATE TABLE `surf_courses` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `level` enum('beginner','intermediate','advanced') DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `max_students` int(11) DEFAULT 10,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `surf_courses`
--

INSERT INTO `surf_courses` (`id`, `title`, `description`, `price`, `level`, `duration`, `max_students`, `image`, `created_at`) VALUES
(1, 'Surf Débutant', 'Apprendre les bases du surf', 300.00, 'beginner', '3 jours', 8, 'surf_6a0c5ac6f217a.jpg', '2026-05-14 00:23:02'),
(2, 'Surf Intermédiaire', 'Améliorer votre technique', 450.00, 'intermediate', '3 jours', 6, 'surf_6a0c5aba0b184.jpg', '2026-05-14 00:23:02'),
(3, 'Surf Avancé', 'Maîtriser les vagues', 100.00, 'advanced', '3 jours', 4, 'surf_6a0c5aaea12a3.jpg', '2026-05-14 00:23:02');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default.png',
  `role` enum('user','admin') DEFAULT 'user',
  `loyalty_points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `avatar`, `role`, `loyalty_points`, `created_at`) VALUES
(2, 'Zakariya_9f', 'zakariyaboulane1@gmail.com', '$2y$10$5KbQ6Ix8bROvSEdVGciVeOmbN3iHGTgUJAnBAmndS30k//wnJtBzu', '', 'user_2_1779237332.jpg', 'user', 197, '2026-05-14 09:01:08'),
(3, 'soukaina_99', 'soukaina_99@gmail.com', '$2y$10$oi.onJmEWJhyXGp5T3gvquX6Z63uCq/O043pKeDKxQDw8vWaDxika', '+21260000000', 'user_3_1779237508.jpeg', 'user', 18369, '2026-05-14 22:20:33'),
(4, 'Admin', 'admin@taghazout.com', '$2y$10$9SBpuVgcNA7cOx456dtRaepV.5PYtzuZjOw4376xShXpaqUD46BZi', NULL, 'default.png', 'admin', 5461, '2026-05-15 14:21:17'),
(5, 'souhail', 'souhail@gmail.com', '$2y$10$hXfhgTJsbIStQ0vukGE35uS83Hhp8XfhvjsoFnaxoLj1Zqb/uvz8K', NULL, 'default.png', 'user', 0, '2026-05-15 18:31:48'),
(6, 'hanane', 'hanane@gmail.com', '$2y$10$Dzwh3Km.ZWJV5K03NLGz9um6lr2g1Bkk8SP1Dw5AsYyVBkDAzY.MW', '', 'user_6_1779091986.jpg', 'user', 1065, '2026-05-17 18:41:29'),
(7, 'soukaina', 'soukaina99@gmail.com', '$2y$10$sXbwZkyRqlOHrP6URKqL7.x8/1.sfqasAyLnpLbBI9I7nTd0KQ6/q', NULL, 'default.png', 'user', 0, '2026-05-18 15:04:07'),
(8, 'zakariya', 'zakariyaboutlane1@gmail.com', '$2y$10$Y8pkggzPXE.OPgPUzERyVOTirPOgYtgizr9epnlJdoPIUlK/HyAnm', 'zakariyaboutlane1@gm', 'user_8_1779236795.jpeg', 'user', 27, '2026-05-19 17:10:11');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `loyalty_history`
--
ALTER TABLE `loyalty_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `loyalty_redemptions`
--
ALTER TABLE `loyalty_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `coupon_code` (`coupon_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reward_id` (`reward_id`);

--
-- Index pour la table `loyalty_rewards`
--
ALTER TABLE `loyalty_rewards`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Index pour la table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `surf_courses`
--
ALTER TABLE `surf_courses`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT pour la table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `loyalty_history`
--
ALTER TABLE `loyalty_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT pour la table `loyalty_redemptions`
--
ALTER TABLE `loyalty_redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `loyalty_rewards`
--
ALTER TABLE `loyalty_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT pour la table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `surf_courses`
--
ALTER TABLE `surf_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `loyalty_history`
--
ALTER TABLE `loyalty_history`
  ADD CONSTRAINT `loyalty_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `loyalty_redemptions`
--
ALTER TABLE `loyalty_redemptions`
  ADD CONSTRAINT `loyalty_redemptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loyalty_redemptions_ibfk_2` FOREIGN KEY (`reward_id`) REFERENCES `loyalty_rewards` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

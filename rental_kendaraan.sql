-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 10 Jun 2025 pada 04.05
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rental_kendaraan`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `is_read`, `created_at`) VALUES
(1, 'Alice Cooper', 'alice@example.com', 'Vehicle Availability', 'I would like to know if the Toyota Camry is available next week.', 0, '2025-05-18 10:44:07'),
(2, 'Bob Wilson', 'bob@example.com', 'Pricing Inquiry', 'What are your rates for long-term rental?', 0, '2025-05-18 10:44:07'),
(3, 'Carol Davis', 'carol@example.com', 'Driver Request', 'Do you provide drivers for all vehicles?', 1, '2025-05-18 10:44:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `status` enum('available','assigned','off') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `drivers`
--

INSERT INTO `drivers` (`id`, `name`, `phone`, `license_number`, `status`, `created_at`, `updated_at`) VALUES
(1, 'John Doe', '081234567891', 'SIM-123456', 'available', '2025-05-18 10:44:07', '2025-05-24 09:39:33'),
(2, 'Jane Smith', '081234567892', 'SIM-234567', 'assigned', '2025-05-18 10:44:07', '2025-05-24 09:42:31'),
(3, 'Mike Johnson', '081234567893', 'SIM-345678', 'assigned', '2025-05-18 10:44:07', '2025-05-20 14:38:20'),
(4, 'David Wilson', '081234567894', 'SIM-456789', 'available', '2025-05-18 10:44:07', '2025-06-04 14:44:34'),
(5, 'Lisa Anderson', '081234567895', 'SIM-567890', 'assigned', '2025-05-18 10:44:07', '2025-05-20 12:29:44'),
(6, 'Robert Taylor', '081234567896', 'SIM-678901', 'assigned', '2025-05-18 10:44:07', '2025-05-20 14:27:34'),
(7, 'Emily Martinez', '081234567897', 'SIM-789012', 'assigned', '2025-05-18 10:44:07', '2025-06-01 17:42:55'),
(8, 'James Thompson', '081234567898', 'SIM-890123', 'assigned', '2025-05-18 10:44:07', '2025-05-20 12:39:04'),
(9, 'Andre Mantaf', '08123123123', 'SIM-999SIP', 'assigned', '2025-05-25 18:11:47', '2025-06-03 14:28:49'),
(10, 'Wirs 88', '08444444444', 'SIM 88888', 'available', '2025-06-01 17:58:43', '2025-06-01 17:58:43'),
(11, 'Tralala Trelele', '089099999', 'SIM OK KKK', 'available', '2025-06-03 06:49:51', '2025-06-03 06:49:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_notes`
--

CREATE TABLE `order_notes` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `note_type` enum('general','issue','payment','vehicle','driver','customer') NOT NULL DEFAULT 'general',
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `order_notes`
--

INSERT INTO `order_notes` (`id`, `order_id`, `admin_id`, `note`, `note_type`, `is_private`, `created_at`) VALUES
(1, 1, 1, 'Customer requested early pickup', 'general', 0, '2025-05-18 10:44:07'),
(2, 1, 1, 'Vehicle returned with minor scratches', 'vehicle', 1, '2025-05-18 10:44:07'),
(3, 2, 1, 'Customer paid in full', 'payment', 0, '2025-05-18 10:44:07'),
(4, 3, 1, 'Driver assigned: John Doe', 'driver', 0, '2025-05-18 10:44:07'),
(5, 4, 1, 'Waiting for payment confirmation', 'payment', 1, '2025-05-18 10:44:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rental_orders`
--

CREATE TABLE `rental_orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `is_out_of_town` tinyint(1) NOT NULL DEFAULT 0,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','ongoing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','refunded') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','transfer','credit_card') NOT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rental_orders`
--

INSERT INTO `rental_orders` (`id`, `user_id`, `vehicle_id`, `driver_id`, `is_out_of_town`, `start_date`, `end_date`, `total_price`, `status`, `payment_status`, `payment_method`, `payment_proof`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 1, 0, '2024-03-01 08:00:00', '2024-03-03 17:00:00', 1500000.00, 'completed', 'paid', 'transfer', NULL, '2025-05-18 10:44:07', '2025-05-20 14:26:33'),
(2, 2, 3, 2, 0, '2024-03-05 09:00:00', '2024-03-07 18:00:00', 3000000.00, 'completed', 'paid', 'cash', NULL, '2025-05-18 10:44:07', '2025-05-18 13:23:37'),
(3, 3, 2, 3, 0, '2024-03-10 10:00:00', '2024-03-12 19:00:00', 2100000.00, 'ongoing', 'paid', 'cash', NULL, '2025-05-18 10:44:07', '2025-05-18 10:44:07'),
(4, 4, 4, 4, 0, '2024-03-15 11:00:00', '2024-03-17 20:00:00', 2400000.00, 'pending', 'pending', 'transfer', NULL, '2025-05-18 10:44:07', '2025-05-18 10:44:07'),
(5, 2, 15, 6, 0, '2025-05-21 18:46:00', '2025-05-23 18:46:00', 4800000.00, 'completed', 'pending', 'cash', NULL, '2025-05-18 11:46:31', '2025-05-18 16:15:27'),
(6, 2, 14, 8, 0, '2025-05-21 20:08:00', '2025-05-24 20:08:00', 7200000.00, 'ongoing', 'pending', 'cash', NULL, '2025-05-18 13:08:44', '2025-05-20 12:40:01'),
(7, 2, 2, 5, 0, '2025-05-20 20:41:00', '2025-05-24 20:41:00', 9600000.00, 'ongoing', 'pending', 'transfer', 'uploads/payment_proof/proof_6829e49c193ca.png', '2025-05-18 13:46:04', '2025-05-20 12:30:05'),
(8, 2, 6, 8, 0, '2025-05-21 20:46:00', '2025-05-24 20:46:00', 7200000.00, 'completed', 'pending', 'transfer', 'uploads/payment_proof/proof_6829e4bb801e6.png', '2025-05-18 13:46:35', '2025-05-20 12:31:26'),
(9, 2, 5, 3, 0, '2025-05-21 22:41:00', '2025-05-23 22:41:00', 4800000.00, 'completed', 'pending', 'cash', NULL, '2025-05-18 15:44:21', '2025-05-18 16:51:13'),
(10, 2, 5, 5, 0, '2025-05-29 22:44:00', '2025-05-31 22:44:00', 4800000.00, 'cancelled', 'pending', 'transfer', 'uploads/payment_proof/proof_682a00871d328.png', '2025-05-18 15:45:11', '2025-05-18 16:14:49'),
(11, 2, 12, 2, 0, '2025-05-29 23:21:00', '2025-05-31 23:21:00', 4800000.00, 'completed', 'pending', 'transfer', 'uploads/payment_proof/proof_682a092b44fa8.png', '2025-05-18 16:22:03', '2025-05-18 16:40:08'),
(12, 2, 7, 6, 0, '2025-05-28 21:26:00', '2025-05-29 21:27:00', 2500000.00, 'ongoing', 'pending', 'cash', NULL, '2025-05-20 14:27:12', '2025-05-20 14:27:41'),
(13, 2, 10, 3, 0, '2025-05-26 21:37:00', '2025-05-27 21:37:00', 2400000.00, 'ongoing', 'pending', 'cash', NULL, '2025-05-20 14:37:56', '2025-05-20 14:38:27'),
(14, 2, 8, 7, 0, '2025-05-24 21:41:00', '2025-05-25 21:41:00', 2400000.00, 'completed', 'pending', 'cash', NULL, '2025-05-20 14:41:58', '2025-05-20 14:44:04'),
(15, 2, 6, 1, 0, '2025-05-23 11:42:00', '2025-05-24 11:42:00', 2400000.00, 'completed', 'pending', 'cash', NULL, '2025-05-22 04:42:30', '2025-05-24 09:39:33'),
(16, 2, 3, 2, 0, '2025-05-26 16:40:00', '2025-05-28 16:41:00', 4900000.00, 'ongoing', 'pending', 'cash', NULL, '2025-05-24 09:41:25', '2025-05-24 09:42:38'),
(17, 2, 13, 4, 0, '2025-05-28 21:31:00', '2025-05-30 21:31:00', 4800000.00, 'completed', 'pending', 'cash', NULL, '2025-05-27 14:31:49', '2025-05-27 14:36:39'),
(18, 2, 13, 4, 0, '2025-06-03 14:38:00', '2025-06-04 14:38:00', 2400000.00, 'completed', 'pending', 'transfer', 'uploads/payment_proof/proof_683c037eba44a.png', '2025-06-01 07:38:38', '2025-06-01 14:46:37'),
(19, 2, 16, 9, 0, '2025-06-12 14:38:00', '2025-06-13 14:39:00', 10000000.00, 'completed', 'pending', 'transfer', 'uploads/payment_proof/proof_683c03b070cbd.png', '2025-06-01 07:39:28', '2025-06-01 14:46:32'),
(20, 2, 5, 9, 0, '2025-06-05 00:22:00', '2025-06-06 00:22:00', 2400000.00, 'completed', 'pending', 'transfer', 'uploads/payment_proof/proof_683c8c5d8001e.png', '2025-06-01 17:22:37', '2025-06-01 17:37:39'),
(21, 2, 15, NULL, 0, '2025-06-05 00:38:00', '2025-06-05 03:38:00', 300000.00, 'completed', 'pending', 'transfer', 'uploads/payment_proof/proof_683c9047f20e9.png', '2025-06-01 17:39:19', '2025-06-01 17:41:40'),
(22, 2, 5, 7, 0, '2025-06-07 00:41:00', '2025-06-09 00:42:00', 4900000.00, 'ongoing', 'pending', 'transfer', 'uploads/payment_proof/proof_683c910d6c8bd.png', '2025-06-01 17:42:37', '2025-06-01 17:43:03'),
(23, 2, 15, 9, 1, '2025-06-05 16:27:00', '2025-06-06 16:27:00', 2880000.00, 'completed', 'pending', 'transfer', 'uploads/payment_proof/proof_683ec03196d88.png', '2025-06-03 09:28:17', '2025-06-03 09:31:49'),
(24, 2, 15, 9, 1, '2025-06-05 19:08:00', '2025-06-07 19:08:00', 5760000.00, 'completed', 'pending', 'transfer', 'uploads/payment_proof/proof_683ee5cb81e89.png', '2025-06-03 12:08:43', '2025-06-03 12:30:19'),
(25, 7, 6, 9, 0, '2025-06-03 20:56:00', '2025-06-04 00:56:00', 400000.00, 'completed', 'pending', 'transfer', 'uploads/payment_proof/proof_683ef128387d2.png', '2025-06-03 12:57:12', '2025-06-03 13:06:59'),
(26, 7, 15, 9, 0, '2025-06-06 21:22:00', '2025-06-08 21:22:00', 4800000.00, 'ongoing', 'pending', 'cash', NULL, '2025-06-03 14:23:01', '2025-06-03 14:28:52'),
(27, 7, 9, 4, 1, '2025-06-05 21:38:00', '2025-06-07 21:38:00', 5760000.00, 'completed', 'pending', 'transfer', 'uploads/payment_proof/proof_68405a8c03714.jpeg', '2025-06-04 14:39:08', '2025-06-04 14:44:34'),
(28, 7, 18, NULL, 0, '2025-06-12 09:00:00', '2025-06-12 14:00:00', 300000.00, 'pending', 'pending', 'cash', NULL, '2025-06-10 02:01:04', '2025-06-10 02:01:04'),
(29, 7, 18, NULL, 1, '2025-06-13 09:04:00', '2025-06-13 14:04:00', 360000.00, 'pending', 'pending', 'cash', NULL, '2025-06-10 02:04:17', '2025-06-10 02:04:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rental_returns`
--

CREATE TABLE `rental_returns` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `return_date` datetime NOT NULL,
  `condition_notes` text DEFAULT NULL,
  `additional_charges` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `late_hours` int(11) DEFAULT 0,
  `late_fee` decimal(10,2) DEFAULT 0.00,
  `pickup_option` enum('jemput','antar_sendiri') DEFAULT 'antar_sendiri',
  `pickup_fee` decimal(10,2) DEFAULT 0.00,
  `return_payment_method` enum('cash','transfer') DEFAULT NULL,
  `return_payment_proof` varchar(255) DEFAULT NULL,
  `total_additional_fee` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','verified','completed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `rental_returns`
--

INSERT INTO `rental_returns` (`id`, `order_id`, `return_date`, `condition_notes`, `additional_charges`, `created_at`, `updated_at`, `late_hours`, `late_fee`, `pickup_option`, `pickup_fee`, `return_payment_method`, `return_payment_proof`, `total_additional_fee`, `status`) VALUES
(1, 5, '2025-05-23 18:12:00', NULL, 0.00, '2025-05-18 16:13:49', '2025-05-18 16:13:49', 0, 0.00, '', 20000.00, 'cash', NULL, 20000.00, 'pending'),
(2, 11, '2025-06-03 23:36:00', NULL, 0.00, '2025-05-18 16:36:29', '2025-05-18 16:36:29', 72, 0.00, '', 0.00, 'cash', NULL, 0.00, 'pending'),
(3, 9, '2025-05-23 20:40:00', NULL, 0.00, '2025-05-18 16:40:56', '2025-05-18 16:40:56', 0, 0.00, '', 0.00, 'transfer', 'uploads/payment_proof/return_proof_1747586456.png', 0.00, 'pending'),
(4, 8, '2025-05-23 18:50:00', NULL, 0.00, '2025-05-20 11:51:30', '2025-05-20 11:51:30', 0, 0.00, '', 0.00, 'cash', NULL, 0.00, 'pending'),
(5, 7, '2025-05-20 19:30:00', NULL, 0.00, '2025-05-20 12:31:47', '2025-05-20 12:31:47', 0, 0.00, '', 20000.00, 'cash', NULL, 20000.00, 'pending'),
(6, 6, '2025-05-25 19:40:00', NULL, 0.00, '2025-05-20 12:41:27', '2025-05-20 12:41:27', 24, 0.00, '', 0.00, 'transfer', 'uploads/payment_proof/return_proof_1747744887.png', 0.00, 'pending'),
(7, 1, '2024-06-21 21:24:00', NULL, 0.00, '2025-05-20 14:25:40', '2025-05-20 14:25:40', 2644, 0.00, '', 20000.00, 'cash', NULL, 20000.00, 'pending'),
(8, 12, '2025-05-30 21:29:00', NULL, 0.00, '2025-05-20 14:33:25', '2025-05-20 14:33:25', 24, 0.00, '', 0.00, 'cash', NULL, 0.00, 'pending'),
(9, 13, '2025-05-28 21:38:00', NULL, 0.00, '2025-05-20 14:38:49', '2025-05-20 14:38:49', 24, 0.00, 'jemput', 20000.00, 'cash', NULL, 20000.00, 'pending'),
(10, 14, '2025-05-27 21:42:00', NULL, 0.00, '2025-05-20 14:43:17', '2025-05-20 14:43:17', 48, 4801666.67, 'jemput', 20000.00, 'cash', NULL, 4821666.67, 'pending'),
(11, 15, '2025-05-24 16:38:00', NULL, 0.00, '2025-05-24 09:39:02', '2025-05-24 09:39:02', 5, 493333.33, 'jemput', 20000.00, 'transfer', 'uploads/payment_proof/return_proof_1748079542.png', 513333.33, 'pending'),
(12, 16, '2025-05-27 16:42:00', NULL, 0.00, '2025-05-24 09:49:14', '2025-05-24 09:49:14', 0, 0.00, 'antar_sendiri', 0.00, 'cash', NULL, 0.00, 'pending'),
(13, 17, '2025-05-30 21:34:00', NULL, 0.00, '2025-05-27 14:35:54', '2025-05-27 14:35:54', 0, 5000.00, 'jemput', 20000.00, 'transfer', 'uploads/payment_proof/return_proof_1748356554.png', 25000.00, 'pending'),
(14, 19, '2025-06-01 14:41:00', NULL, 0.00, '2025-06-01 07:41:43', '2025-06-01 07:41:43', 0, 0.00, 'jemput', 20000.00, 'transfer', 'uploads/payment_proof/return_proof_1748763703.png', 20000.00, 'pending'),
(15, 20, '2025-06-09 00:36:00', NULL, 0.00, '2025-06-01 17:36:53', '2025-06-01 17:36:53', 72, 7223333.33, 'antar_sendiri', 0.00, 'transfer', 'uploads/payment_proof/return_proof_1748799413.png', 7223333.33, 'pending'),
(16, 21, '2025-06-02 00:40:00', NULL, 0.00, '2025-06-01 17:41:13', '2025-06-01 17:41:13', 0, 0.00, 'jemput', 20000.00, 'transfer', 'uploads/payment_proof/return_proof_1748799673.png', 20000.00, 'pending'),
(17, 22, '2025-06-02 00:43:00', NULL, 0.00, '2025-06-01 17:43:32', '2025-06-01 17:43:32', 0, 0.00, 'jemput', 20000.00, 'transfer', 'uploads/payment_proof/return_proof_1748799812.png', 20000.00, 'pending'),
(18, 23, '2025-06-03 16:31:00', NULL, 0.00, '2025-06-03 09:31:31', '2025-06-03 09:31:31', 0, 0.00, 'jemput', 20000.00, 'transfer', 'uploads/payment_proof/return_proof_1748943091.jpg', 20000.00, 'pending'),
(19, 24, '2025-06-08 18:28:00', NULL, 0.00, '2025-06-03 12:29:50', '2025-06-03 12:29:50', 23, 2333333.33, 'jemput', 20000.00, 'transfer', 'uploads/payment_proof/return_proof_1748953790.png', 2353333.33, 'pending'),
(20, 25, '2025-06-05 22:00:00', NULL, 0.00, '2025-06-03 13:02:27', '2025-06-03 13:02:27', 45, 4506666.67, 'jemput', 20000.00, 'transfer', 'uploads/payment_proof/return_proof_1748955747.jpeg', 4526666.67, 'pending'),
(21, 26, '2025-06-10 21:33:00', NULL, 0.00, '2025-06-03 14:30:49', '2025-06-03 14:30:49', 48, 4818333.33, 'antar_sendiri', 0.00, 'cash', NULL, 4818333.33, 'pending'),
(22, 27, '2025-06-08 21:41:00', NULL, 0.00, '2025-06-04 14:43:09', '2025-06-04 14:43:09', 24, 2405000.00, 'jemput', 20000.00, 'transfer', 'uploads/payment_proof/return_proof_1749048189.jpg', 2425000.00, 'pending');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@rental.com', '$2y$10$hENitgudcDtXq8t7uVgZ9..bPveGirXmCkgSxQxtPHkhZKq.A7akG', '081234567890', 'admin', '2025-05-18 10:44:06', '2025-05-21 18:17:33'),
(2, 'John Smith 01', 'john@example.com', '$2y$10$6qoEk87PLWJHojXImGmes.p67tzmURgP/EymbBxK2sWXH2Hitzdxy', '081234567891', 'user', '2025-05-18 10:44:07', '2025-06-01 17:53:52'),
(3, 'Sarah Johnson', 'sarah@example.com', '$2y$10$hENitgudcDtXq8t7uVgZ9..bPveGirXmCkgSxQxtPHkhZKq.A7akG', '081234567892', 'user', '2025-05-18 10:44:07', '2025-05-18 11:21:20'),
(4, 'Michael Brown', 'michael@example.com', '$2y$10$hENitgudcDtXq8t7uVgZ9..bPveGirXmCkgSxQxtPHkhZKq.A7akG', '081234567893', 'user', '2025-05-18 10:44:07', '2025-05-18 11:21:07'),
(5, 'Opal', 'opal@gmail.com', '$2y$10$hENitgudcDtXq8t7uVgZ9..bPveGirXmCkgSxQxtPHkhZKq.A7akG', '08223991381', 'admin', '2025-05-18 11:08:08', '2025-05-18 11:09:44'),
(6, 'Ucup Keren', 'Ucup@keren.com', '$2y$10$f9qLUsFTVP9IfVUup8v3CO4CoN7hTJtvrKoGx9SvEDzdf6JgNo4vm', '08111111111', 'user', '2025-06-03 03:44:05', '2025-06-03 12:44:46'),
(7, 'Kelompok 4', 'kel4@contoh.com', '$2y$10$afKnC56rMAIdsSfwQsBIa.5M805mo5bdCP2YPVlw98qEvXVOtUydC', '083444444444', 'user', '2025-06-03 12:51:59', '2025-06-03 12:51:59'),
(8, 'test 4', 'test4@test.com', '$2y$10$cXo7xuC9h.pb1efOKtd53erVuveiYJZkN7n5AeYWe7..YdGI3zN0O', '08777777777', 'user', '2025-06-04 04:11:17', '2025-06-04 04:11:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `vehicle_type_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `price_per_hour` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `status` enum('available','rented','maintenance') NOT NULL DEFAULT 'available',
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `vehicles`
--

INSERT INTO `vehicles` (`id`, `vehicle_type_id`, `name`, `plate_number`, `price_per_hour`, `description`, `price_per_day`, `status`, `image`, `created_at`, `updated_at`) VALUES
(1, 1, 'Toyota Camry', 'B 1234 ABC', 100000.00, 'Comfortable sedan with excellent fuel efficiency', 500000.00, 'available', 'camry.png', '2025-05-18 10:44:07', '2025-05-25 15:55:03'),
(2, 2, 'Honda CR-V', 'B 2345 DEF', 100000.00, 'Spacious SUV with modern features', 700000.00, 'rented', 'mobil2.png', '2025-05-18 10:44:07', '2025-05-25 15:55:08'),
(3, 3, 'Toyota Alphard', 'B 3456 GHI', 100000.00, 'Luxurious minivan perfect for family trips', 1000000.00, 'rented', 'alphard.png', '2025-05-18 10:44:07', '2025-05-25 15:55:13'),
(4, 4, 'Mazda MX-5', 'B 4567 JKL', 100000.00, 'Sporty convertible for an exciting drive', 800000.00, 'available', 'mx5.png', '2025-05-18 10:44:07', '2025-05-25 15:55:17'),
(5, 5, 'Mercedes-Benz S-Class', 'B 5678 MNO', 100000.00, 'Premium luxury sedan with advanced features', 1500000.00, 'rented', 'sclass.png', '2025-05-18 10:44:07', '2025-06-01 17:42:55'),
(6, 1, 'Honda City', 'B 6789 PQR', 100000.00, 'Efficient sedan perfect for daily commute', 400000.00, 'available', 'motor1.png', '2025-05-18 10:44:07', '2025-06-03 13:06:59'),
(7, 1, 'Toyota Corolla', 'B 7890 STU', 100000.00, 'Reliable sedan with great fuel economy', 450000.00, 'rented', 'corolla.png', '2025-05-18 10:44:07', '2025-05-25 15:55:33'),
(8, 2, 'Toyota Fortuner', 'B 8901 VWX', 100000.00, 'Powerful SUV for adventurous trips', 800000.00, 'available', 'fortuner.png', '2025-05-18 10:44:07', '2025-05-25 16:13:53'),
(9, 2, 'Mitsubishi Pajero', 'B 9012 YZ', 100000.00, 'Rugged SUV for off-road adventures', 900000.00, 'available', 'pajero.png', '2025-05-18 10:44:07', '2025-06-04 14:44:34'),
(10, 3, 'Toyota Hiace', 'B 0123 ABC', 100000.00, 'Versatile van for group transportation', 750000.00, 'rented', 'hiace.png', '2025-05-18 10:44:07', '2025-05-25 15:55:45'),
(11, 3, 'Mercedes-Benz Sprinter', 'B 1234 DEF', 100000.00, 'Premium van for executive transport', 1200000.00, 'available', 'bus1.png', '2025-05-18 10:44:07', '2025-05-25 15:55:49'),
(12, 4, 'Porsche 911', 'B 2345 GHI', 100000.00, 'Iconic sports car with unmatched performance', 2000000.00, 'available', '911.png', '2025-05-18 10:44:07', '2025-05-25 04:16:55'),
(13, 4, 'Ferrari F8', 'B 3456 JKL', 100000.00, 'Exotic supercar for the ultimate driving experience', 3000000.00, 'available', 'f8.png', '2025-05-18 10:44:07', '2025-06-01 14:46:37'),
(14, 5, 'BMW 7 Series', 'B 4567 MNO', 100000.00, 'Luxury sedan with cutting-edge technology', 1800000.00, 'rented', '7series.png', '2025-05-18 10:44:07', '2025-05-25 15:55:59'),
(15, 5, 'Audi A8', 'B 5678 PQR', 100000.00, 'Premium sedan with quattro all-wheel drive', 1700000.00, 'rented', 'a8.png', '2025-05-18 10:44:07', '2025-06-03 14:28:49'),
(16, 5, 'Rolls Royce Spectre', 'B 3918 NPL', 400000.00, 'Mobil Mahal tahan banting', 0.00, 'available', 'spectre.png', '2025-05-25 18:10:32', '2025-06-01 14:46:32'),
(18, 4, 'test2', 'B 4444 MIM', 60000.00, 'test 2', 0.00, 'available', '683c96b985965.png', '2025-06-01 18:06:49', '2025-06-01 18:06:49'),
(19, 4, 'testing 3', 'B 8888 CIM', 49000.00, 'oke nih', 0.00, 'available', '683e9a968a962.png', '2025-06-03 06:47:50', '2025-06-03 06:47:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `vehicle_types`
--

CREATE TABLE `vehicle_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `vehicle_types`
--

INSERT INTO `vehicle_types` (`id`, `name`, `description`, `icon`, `created_at`, `updated_at`) VALUES
(1, 'Sedan', 'Comfortable and fuel-efficient vehicles for city driving', 'fas fa-car', '2025-05-18 10:44:06', '2025-05-18 10:44:06'),
(2, 'SUV', 'Spacious vehicles perfect for family trips', 'fas fa-truck-monster', '2025-05-18 10:44:06', '2025-05-18 10:44:06'),
(3, 'Minivan', 'Large vehicles ideal for group transportation', 'fas fa-van-shuttle', '2025-05-18 10:44:06', '2025-05-18 10:44:06'),
(4, 'Sports Car', 'High-performance vehicles for an exciting driving experience', 'fas fa-car-side', '2025-05-18 10:44:06', '2025-05-18 10:44:06'),
(5, 'Luxury Car', 'Premium vehicles with advanced features and comfort', 'fas fa-car-alt', '2025-05-18 10:44:06', '2025-05-18 10:44:06');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_number` (`license_number`);

--
-- Indeks untuk tabel `order_notes`
--
ALTER TABLE `order_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indeks untuk tabel `rental_orders`
--
ALTER TABLE `rental_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indeks untuk tabel `rental_returns`
--
ALTER TABLE `rental_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rental_returns_order_id` (`order_id`),
  ADD KEY `idx_rental_returns_return_date` (`return_date`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate_number` (`plate_number`),
  ADD KEY `vehicles_ibfk_1` (`vehicle_type_id`);

--
-- Indeks untuk tabel `vehicle_types`
--
ALTER TABLE `vehicle_types`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `order_notes`
--
ALTER TABLE `order_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `rental_orders`
--
ALTER TABLE `rental_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT untuk tabel `rental_returns`
--
ALTER TABLE `rental_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `vehicle_types`
--
ALTER TABLE `vehicle_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `order_notes`
--
ALTER TABLE `order_notes`
  ADD CONSTRAINT `order_notes_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `rental_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_notes_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `rental_orders`
--
ALTER TABLE `rental_orders`
  ADD CONSTRAINT `rental_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rental_orders_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rental_orders_ibfk_3` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `rental_returns`
--
ALTER TABLE `rental_returns`
  ADD CONSTRAINT `rental_returns_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `rental_orders` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

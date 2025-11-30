-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 30, 2025 at 11:47 PM
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
-- Database: `logistic_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `atribuiri`
--

CREATE TABLE `atribuiri` (
  `id` int(11) NOT NULL,
  `expeditie_id` int(11) NOT NULL,
  `transportator_id` int(11) NOT NULL,
  `pret_final` decimal(10,2) NOT NULL,
  `moneda` enum('EUR','RON') DEFAULT 'EUR',
  `numar_camion` varchar(20) DEFAULT NULL,
  `nume_sofer` varchar(100) DEFAULT NULL,
  `detalii_sofer` text DEFAULT NULL,
  `data_atribuire` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expedities`
--

CREATE TABLE `expedities` (
  `id` int(11) NOT NULL,
  `nume_client` varchar(100) NOT NULL,
  `destinatie` varchar(100) NOT NULL,
  `produs` varchar(100) NOT NULL,
  `tarif_tinta` decimal(10,2) DEFAULT NULL,
  `moneda` enum('EUR','RON') DEFAULT 'EUR',
  `data_transport` date DEFAULT NULL,
  `status` enum('planificata','in_licitatie','atribuita','finalizata','anulata') DEFAULT 'planificata',
  `data_creare` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `licitatii`
--

CREATE TABLE `licitatii` (
  `id` int(11) NOT NULL,
  `expeditie_id` int(11) NOT NULL,
  `status` enum('active','finalizata','anulata') DEFAULT 'active',
  `data_start` datetime DEFAULT current_timestamp(),
  `data_finalizare` datetime DEFAULT NULL,
  `termen_ore` int(11) DEFAULT 24,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oferte`
--

CREATE TABLE `oferte` (
  `id` int(11) NOT NULL,
  `licitatie_id` int(11) NOT NULL,
  `transportator_id` int(11) NOT NULL,
  `pret_oferit` decimal(10,2) NOT NULL,
  `moneda` enum('EUR','RON') DEFAULT 'EUR',
  `status` enum('activă','câștigătoare','respinsă') DEFAULT 'activă',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('manager','transportator','supervizor') NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `email`, `role`, `company_name`, `phone`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'manager_test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager@test.com', 'manager', 'Compania Test Manager', '0722000000', 1, '2025-11-30 21:53:58', '2025-11-30 21:53:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `atribuiri`
--
ALTER TABLE `atribuiri`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expeditie_id` (`expeditie_id`),
  ADD KEY `transportator_id` (`transportator_id`);

--
-- Indexes for table `expedities`
--
ALTER TABLE `expedities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `licitatii`
--
ALTER TABLE `licitatii`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expeditie_id` (`expeditie_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `oferte`
--
ALTER TABLE `oferte`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_licitatie_transportator` (`licitatie_id`,`transportator_id`),
  ADD KEY `transportator_id` (`transportator_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `atribuiri`
--
ALTER TABLE `atribuiri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expedities`
--
ALTER TABLE `expedities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `licitatii`
--
ALTER TABLE `licitatii`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `oferte`
--
ALTER TABLE `oferte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `atribuiri`
--
ALTER TABLE `atribuiri`
  ADD CONSTRAINT `atribuiri_ibfk_1` FOREIGN KEY (`expeditie_id`) REFERENCES `expedities` (`id`),
  ADD CONSTRAINT `atribuiri_ibfk_2` FOREIGN KEY (`transportator_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `expedities`
--
ALTER TABLE `expedities`
  ADD CONSTRAINT `expedities_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `licitatii`
--
ALTER TABLE `licitatii`
  ADD CONSTRAINT `licitatii_ibfk_1` FOREIGN KEY (`expeditie_id`) REFERENCES `expedities` (`id`),
  ADD CONSTRAINT `licitatii_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `oferte`
--
ALTER TABLE `oferte`
  ADD CONSTRAINT `oferte_ibfk_1` FOREIGN KEY (`licitatie_id`) REFERENCES `licitatii` (`id`),
  ADD CONSTRAINT `oferte_ibfk_2` FOREIGN KEY (`transportator_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

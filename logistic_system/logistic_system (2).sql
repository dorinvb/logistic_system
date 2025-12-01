-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 01, 2025 at 08:13 AM
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
  `licitatie_id` int(11) NOT NULL,
  `transportator_id` int(11) NOT NULL,
  `pret_final` decimal(10,2) NOT NULL,
  `moneda` enum('EUR','RON') DEFAULT 'EUR',
  `numar_camion` varchar(20) DEFAULT NULL,
  `numar_remorca` varchar(20) DEFAULT NULL,
  `nume_sofer` varchar(100) DEFAULT NULL,
  `prenume_sofer` varchar(100) DEFAULT NULL,
  `tip_document` enum('CI','PASAPORT') DEFAULT 'CI',
  `numar_document` varchar(20) DEFAULT NULL,
  `data_atribuire` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('confirmata','in_asteptare','anulata') DEFAULT 'in_asteptare',
  `adr_verificat` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `autorizatii_adr`
--

CREATE TABLE `autorizatii_adr` (
  `id` int(11) NOT NULL,
  `transportator_id` int(11) NOT NULL,
  `numar_autorizatie` varchar(50) NOT NULL,
  `data_emitere` date NOT NULL,
  `data_expirare` date NOT NULL,
  `poza_autorizatie` varchar(255) DEFAULT NULL,
  `este_valabila` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `autorizatii_adr`
--

INSERT INTO `autorizatii_adr` (`id`, `transportator_id`, `numar_autorizatie`, `data_emitere`, `data_expirare`, `poza_autorizatie`, `este_valabila`, `created_at`, `updated_at`) VALUES
(1, 3, '1234567890', '2024-08-03', '2028-08-02', 'ADR_3_1764570715.JPG', 1, '2025-12-01 06:31:55', '2025-12-01 06:31:55');

-- --------------------------------------------------------

--
-- Table structure for table `expedities`
--

CREATE TABLE `expedities` (
  `id` int(11) NOT NULL,
  `nume_client` varchar(100) NOT NULL,
  `destinatie` varchar(100) NOT NULL,
  `produs` varchar(100) NOT NULL,
  `necesita_adr` tinyint(1) DEFAULT 0,
  `tarif_tinta` decimal(10,2) DEFAULT NULL,
  `moneda` enum('EUR','RON') DEFAULT 'EUR',
  `data_transport` date DEFAULT NULL,
  `status` enum('planificata','in_licitatie','atribuita','finalizata','anulata') DEFAULT 'planificata',
  `data_creare` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expedities`
--

INSERT INTO `expedities` (`id`, `nume_client`, `destinatie`, `produs`, `necesita_adr`, `tarif_tinta`, `moneda`, `data_transport`, `status`, `data_creare`, `created_by`) VALUES
(1, 'GOLDEN STAR', 'DORNESTI', 'NPK', 0, 800.00, 'RON', '2025-12-02', 'planificata', '2025-11-30 23:00:05', 1),
(2, 'AZOCHIM', 'glogovat', 'AN cu adr', 0, 200.00, 'RON', '2025-12-05', 'in_licitatie', '2025-11-30 23:08:17', 1),
(3, 'BIZ SOLUTION', 'cARACAL', 'NPK', 1, 100.00, 'RON', '2025-12-03', 'planificata', '2025-11-30 23:53:09', 1),
(4, 'BIZ SOLUTION', 'cARACAL', 'AN  ', 1, 150.00, 'RON', '2025-12-04', 'in_licitatie', '2025-12-01 06:14:50', 1);

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
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `licitatii`
--

INSERT INTO `licitatii` (`id`, `expeditie_id`, `status`, `data_start`, `data_finalizare`, `termen_ore`, `created_by`, `created_at`) VALUES
(1, 2, 'active', '2025-12-01 02:02:06', NULL, 24, 1, '2025-12-01 00:02:06'),
(2, 4, 'active', '2025-12-01 08:18:55', NULL, 24, 1, '2025-12-01 06:18:55');

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

--
-- Dumping data for table `oferte`
--

INSERT INTO `oferte` (`id`, `licitatie_id`, `transportator_id`, `pret_oferit`, `moneda`, `status`, `created_at`) VALUES
(1, 1, 2, 180.00, 'RON', 'activă', '2025-12-01 00:03:16'),
(2, 2, 3, 155.00, 'RON', 'activă', '2025-12-01 06:32:37'),
(3, 1, 3, 205.00, 'RON', 'activă', '2025-12-01 06:34:07');

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
(1, 'manager_test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager@test.com', 'manager', 'Compania Test Manager', '0722000000', 1, '2025-11-30 21:53:58', '2025-11-30 21:53:58'),
(2, 'transportrapid', '$2y$10$9Y9sRJ3RNWtzzO/p.pbPXOIz6WozAnWhAjVfS9jniKrwajD9FglKq', 'transportrapidsrl@gmail.com', 'transportator', 'Trans Rapid SRL', '0700000001', 1, '2025-11-30 23:29:34', '2025-11-30 23:29:34'),
(3, 'alphalogistics', '$2y$10$Wkj0rct7DZEubkmyO48JvOgg4SB2Mpkp33CtwULtr.RckFMTD9Vya', 'alphalogistics@gmail.com', 'transportator', 'Alpha Logistics', '0700000002', 1, '2025-12-01 06:17:22', '2025-12-01 06:17:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `atribuiri`
--
ALTER TABLE `atribuiri`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expeditie_id` (`expeditie_id`),
  ADD KEY `licitatie_id` (`licitatie_id`),
  ADD KEY `transportator_id` (`transportator_id`);

--
-- Indexes for table `autorizatii_adr`
--
ALTER TABLE `autorizatii_adr`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_autorizatie_transportator` (`transportator_id`,`numar_autorizatie`);

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
-- AUTO_INCREMENT for table `autorizatii_adr`
--
ALTER TABLE `autorizatii_adr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expedities`
--
ALTER TABLE `expedities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `licitatii`
--
ALTER TABLE `licitatii`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `oferte`
--
ALTER TABLE `oferte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `atribuiri`
--
ALTER TABLE `atribuiri`
  ADD CONSTRAINT `atribuiri_ibfk_1` FOREIGN KEY (`expeditie_id`) REFERENCES `expedities` (`id`),
  ADD CONSTRAINT `atribuiri_ibfk_2` FOREIGN KEY (`licitatie_id`) REFERENCES `licitatii` (`id`),
  ADD CONSTRAINT `atribuiri_ibfk_3` FOREIGN KEY (`transportator_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `autorizatii_adr`
--
ALTER TABLE `autorizatii_adr`
  ADD CONSTRAINT `autorizatii_adr_ibfk_1` FOREIGN KEY (`transportator_id`) REFERENCES `users` (`id`);

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

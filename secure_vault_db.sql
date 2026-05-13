-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2026 at 07:58 PM
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
-- Database: `secure_vault_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `encrypted_filename` varchar(255) NOT NULL,
  `file_hash` varchar(64) NOT NULL,
  `digital_signature` text NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `user_id`, `original_filename`, `encrypted_filename`, `file_hash`, `digital_signature`, `uploaded_at`) VALUES
(1, 2, 'Final Project-software-anu.pdf', 'vault_6a04a0f95e44b.enc', '1e7b1848b21eb0c5dbac40c51b25d3b4ac690bda053cce883a2994fdf4cae100', 'ef5296bf11298babc156f51dec5348c1f44c71440d9272a85e346afc2f8591f2', '2026-05-13 16:04:09'),
(2, 2, 'Final Project - Data Integrity and Authentication.pdf', 'vault_6a04a192060bd.enc', 'b9a1cf77deb2c96570a301e997851cfc8df301c26ed1406e211cde9beaedd81d', '7ad7a446e7e8ffa4d2e47f347b9b7655cc042a631c990cb531827feac3a22c34', '2026-05-13 16:06:42'),
(3, 3, 'Final Project - Data Integrity and Authentication.pdf', 'vault_6a04b99d56417.enc', 'b9a1cf77deb2c96570a301e997851cfc8df301c26ed1406e211cde9beaedd81d', '7ad7a446e7e8ffa4d2e47f347b9b7655cc042a631c990cb531827feac3a22c34', '2026-05-13 17:49:17'),
(4, 3, 'Final Project - Data Integrity and Authentication.pdf', 'vault_6a04ba2dd1462.enc', 'b9a1cf77deb2c96570a301e997851cfc8df301c26ed1406e211cde9beaedd81d', '7ad7a446e7e8ffa4d2e47f347b9b7655cc042a631c990cb531827feac3a22c34', '2026-05-13 17:51:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','manager','user') DEFAULT 'user',
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `two_factor_secret`, `created_at`) VALUES
(2, 'mahmoud', 'mahmoud@gmail.com', '$2y$10$ank8/xEWR5rCWIlnm2Bc.ui17i0UCwVXatHo7ZfJq8KbwhSjN8g2e', 'admin', '6DZ46ZRPC6XEWYFF', '2026-05-13 16:00:08'),
(3, 'Mahmoudmohaa', 'Mahmoudmohaa@github.local', '$2y$10$jbrPBWUkUwIrgRTg1AbBgeQReO/IZ/JKbn6HkRuE9dOEN0vJ0xBUa', 'user', 'N3HJVISLUYPAHD5P', '2026-05-13 17:43:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

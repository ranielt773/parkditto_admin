-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 08, 2025 at 07:54 AM
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
-- Database: `parkditto`
--

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parking_space_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `parking_space_id`, `message`, `rating`, `created_at`, `status`) VALUES
(30, 6, 1, 'Maganda ang parking space! Malinis at maayos ang mga slot.', 5, '2024-01-15 00:30:00', 'approved'),
(31, 6, 1, 'Mabilis ang process ng parking. Maganda ang security.', 4, '2024-01-16 06:20:00', 'approved'),
(36, 5, 1, 'Friendly ang guards. Madaling maghanap ng slot.', 4, '2025-10-01 02:25:00', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `parking_availability`
--

CREATE TABLE `parking_availability` (
  `id` int(11) NOT NULL,
  `parking_space_id` int(11) NOT NULL,
  `vehicle_type` varchar(20) NOT NULL,
  `floor` varchar(20) NOT NULL,
  `slot_number` int(11) NOT NULL,
  `is_occupied` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parking_owners`
--

CREATE TABLE `parking_owners` (
  `id` int(11) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_owners`
--

INSERT INTO `parking_owners` (`id`, `firstname`, `lastname`, `username`, `email`, `password`, `date_created`) VALUES
(1, 'Juan', 'Dela Cruz', 'juan_dc', 'juan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-08-29 05:47:49'),
(2, 'Maria', 'Santos', 'maria_s', 'maria@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-08-29 05:47:49'),
(3, 'Pedro', 'Reyes', 'pedro_r', 'pedro@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-08-29 05:47:49'),
(4, 'Juan', 'Dela Cruz', 'juanowner', 'juan.delacruz@email.com', 'password123', '2025-08-29 06:13:03'),
(5, 'Maria', 'Santos', 'mariaowner', 'maria.santos@email.com', 'password123', '2025-08-29 06:13:03'),
(6, 'Pedro', 'Reyes', 'pedroowner', 'pedro.reyes@email.com', 'password123', '2025-08-29 06:13:03'),
(7, 'Ana', 'Gonzales', 'anaowner', 'ana.gonzales@email.com', 'password123', '2025-08-29 06:13:03'),
(8, 'Luis', 'Torres', 'luisowner', 'luis.torres@email.com', 'password123', '2025-08-29 06:13:03'),
(10, 'asd', 'asassd', 'jerome', 'leadsasdsddmin@leadsagri.app', '$2y$10$qz/6X5OUtp4u9kT.9uPV3.KXoBqzgi3UKingOqAMfRUG5OEiSB/5S', '2025-09-30 01:46:56');

-- --------------------------------------------------------

--
-- Table structure for table `parking_spaces`
--

CREATE TABLE `parking_spaces` (
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `total_spaces` int(11) NOT NULL,
  `available_spaces` int(11) NOT NULL,
  `vehicle_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`vehicle_types`)),
  `floors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`floors`)),
  `floor_capacity` longtext DEFAULT NULL,
  `available_per_floor` longtext DEFAULT NULL,
  `occupied_slots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`occupied_slots`)),
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_url` varchar(500) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_spaces`
--

INSERT INTO `parking_spaces` (`id`, `partner_id`, `name`, `address`, `latitude`, `longitude`, `total_spaces`, `available_spaces`, `vehicle_types`, `floors`, `floor_capacity`, `available_per_floor`, `occupied_slots`, `date_created`, `image_url`) VALUES
(1, 1, 'Calauan Public Market Parking', 'Public Market, Calauan, Laguna', 14.14864389, 121.31482124, 58, 54, '{\"Car\":{\"total\":24,\"available\":24},\"Motorcycle\":{\"total\":30,\"available\":30},\"Mini Truck\":{\"total\":4,\"available\":4}}', '[\"Ground\", \"2nd Floor\", \"3rd Floor\"]', '{\"Ground\":{\"Car\":10,\"Motorcycle\":15,\"Mini Truck\":2},\"2nd Floor\":{\"Car\":10,\"Motorcycle\":10,\"Mini Truck\":1},\"3rd Floor\":{\"Car\":4,\"Motorcycle\":5,\"Mini Truck\":1}}', '{\"Ground\":{\"Car\":10,\"Motorcycle\":15,\"Mini Truck\":2},\"2nd Floor\":{\"Car\":10,\"Motorcycle\":10,\"Mini Truck\":1},\"3rd Floor\":{\"Car\":4,\"Motorcycle\":5,\"Mini Truck\":1}}', '{\"Car\":{\"Ground\":[1],\"2nd Floor\":[],\"3rd Floor\":[]},\"Motorcycle\":{\"Ground\":[],\"2nd Floor\":[],\"3rd Floor\":[]},\"Mini Truck\":{\"Ground\":[]}}', '2025-08-28 21:47:49', 'uploads/parking_spaces/default_parking.jpg'),
(33, 1, 'Leads Agru', 'alauan lagunakljkl', 14.17781308, 121.31495256, 16, 16, '{\"Car\":{\"total\":5,\"available\":5},\"Motorcycle\":{\"total\":10,\"available\":10},\"Mini Truck\":{\"total\":1,\"available\":1}}', '[\"Ground\"]', '{\"Ground\":{\"Car\":5,\"Motorcycle\":10,\"Mini Truck\":1}}', '{\"Ground\":{\"Car\":5,\"Motorcycle\":10,\"Mini Truck\":1}}', '{\"Car\":{\"Ground\":[]},\"Motorcycle\":{\"Ground\":[]},\"Mini Truck\":{\"Ground\":[]}}', '2025-10-02 06:45:33', 'uploads/parking_spaces/default_parking.jpg'),
(35, 3, 'sdsad', 'asdassdasd', 14.17678760, 121.31363380, 12, 12, '{\"Car\":{\"total\":2,\"available\":2},\"Motorcycle\":{\"total\":10,\"available\":10},\"Mini Truck\":{\"total\":0,\"available\":0}}', '[\"Ground\"]', '{\"Ground\":{\"Car\":2,\"Motorcycle\":10,\"Mini Truck\":0}}', '{\"Ground\":{\"Car\":2,\"Motorcycle\":10,\"Mini Truck\":0}}', '{\"Car\":{\"Ground\":[]},\"Motorcycle\":{\"Ground\":[]},\"Mini Truck\":{\"Ground\":[]}}', '2025-10-06 02:41:52', 'uploads/parking_spaces/default_parking.jpg'),
(36, 1, 'asdasd', 'asdasasd', 14.18218189, 121.31542632, 12, 12, '{\"Car\":{\"total\":0,\"available\":0},\"Motorcycle\":{\"total\":12,\"available\":12},\"Mini Truck\":{\"total\":0,\"available\":0}}', '[\"Ground\"]', '{\"Ground\":{\"Car\":0,\"Motorcycle\":12,\"Mini Truck\":0}}', '{\"Ground\":{\"Car\":0,\"Motorcycle\":12,\"Mini Truck\":0}}', '{\"Car\":{\"Ground\":[]},\"Motorcycle\":{\"Ground\":[]},\"Mini Truck\":{\"Ground\":[]}}', '2025-10-06 02:53:53', 'uploads/parking_spaces/default_parking.jpg'),
(37, 7, 'asd', 'asdasd', 14.17589909, 121.31536102, 6, 6, '{\"Car\":{\"total\":0,\"available\":0},\"Motorcycle\":{\"total\":2,\"available\":2},\"Mini Truck\":{\"total\":4,\"available\":4}}', '[\"Ground\"]', '{\"Ground\":{\"Car\":0,\"Motorcycle\":2,\"Mini Truck\":4}}', '{\"Ground\":{\"Car\":0,\"Motorcycle\":2,\"Mini Truck\":4}}', '{\"Car\":{\"Ground\":[]},\"Motorcycle\":{\"Ground\":[]},\"Mini Truck\":{\"Ground\":[]}}', '2025-10-06 03:06:42', 'uploads/parking_spaces/default_parking.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `parking_space_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lot_number` varchar(20) NOT NULL,
  `floor` varchar(50) DEFAULT NULL,
  `vehicle_type` varchar(20) DEFAULT NULL,
  `transaction_type` enum('booking','reservation') NOT NULL,
  `arrival_time` datetime NOT NULL,
  `departure_time` datetime DEFAULT NULL,
  `expiry_time` datetime DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `duration_type` enum('hourly','daily','weekly','monthly','yearly') DEFAULT NULL,
  `duration_value` int(11) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `status` enum('pending','ongoing','cancelled','completed') DEFAULT 'pending',
  `storm_pass` enum('Active','None') NOT NULL,
  `ref_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `parking_space_id`, `user_id`, `lot_number`, `floor`, `vehicle_type`, `transaction_type`, `arrival_time`, `departure_time`, `expiry_time`, `amount`, `duration_type`, `duration_value`, `payment_method`, `status`, `storm_pass`, `ref_number`, `created_at`) VALUES
(92, 1, 5, '1', 'Ground', 'Car', 'booking', '2025-10-06 08:20:37', NULL, '2025-11-06 08:20:37', 2500.00, 'monthly', 1, 'Bank Transfer', 'ongoing', 'Active', '8240963751', '2025-10-06 06:20:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `display_photo` varchar(255) DEFAULT NULL,
  `id_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `type` enum('user','staff','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `display_photo`, `id_picture`, `created_at`, `type`) VALUES
(5, 'jerome', 'jerome.estrada101@gmail.com', '$2y$10$Ak68de6QuzeW80blgLsiQ.jOht6HqK7rWwJiOyOa15YwmYWJrYFLC', 'jetome', 'rstrada', 'uploads/68ba3f732a481_5.jpg', 'uploads/68ba3f7392347_5.jpg', '2025-09-02 23:48:38', 'admin'),
(6, 'rtaleon', 'rtaleon773@gmail.com', '$2y$10$H9Of4Vdt8/lJPdxBjzLBZOhxFh.JMCpF8L3q24P47NSbu/.6fih3G', 'Raniel', 'Taleon', 'uploads/68ba7e3674fd8_4.jpg', 'uploads/68be29c0014eb_4.jpg', '2025-08-28 18:18:48', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parking_space_id` (`parking_space_id`);

--
-- Indexes for table `parking_availability`
--
ALTER TABLE `parking_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slot` (`parking_space_id`,`vehicle_type`,`floor`,`slot_number`);

--
-- Indexes for table `parking_owners`
--
ALTER TABLE `parking_owners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `parking_spaces`
--
ALTER TABLE `parking_spaces`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partner_id` (`partner_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_number` (`ref_number`),
  ADD KEY `parking_space_id` (`parking_space_id`);

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
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `parking_availability`
--
ALTER TABLE `parking_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `parking_owners`
--
ALTER TABLE `parking_owners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `parking_spaces`
--
ALTER TABLE `parking_spaces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`parking_space_id`) REFERENCES `parking_spaces` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parking_availability`
--
ALTER TABLE `parking_availability`
  ADD CONSTRAINT `parking_availability_ibfk_1` FOREIGN KEY (`parking_space_id`) REFERENCES `parking_spaces` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`parking_space_id`) REFERENCES `parking_spaces` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

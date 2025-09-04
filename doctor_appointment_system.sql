-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 12, 2025 at 10:02 PM
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
-- Database: `doctor_appointment_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@hospital.com', '2025-05-30 08:51:09');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `symptoms` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `consultation_fee` decimal(10,2) NOT NULL,
  `experience_years` int(11) NOT NULL,
  `qualification` varchar(200) NOT NULL,
  `profile_image` varchar(255) DEFAULT 'default_doctor.jpg',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `name`, `email`, `password`, `specialization`, `phone`, `address`, `consultation_fee`, `experience_years`, `qualification`, `profile_image`, `status`, `created_at`) VALUES
(1, 'Dr. John Smith', 'john@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cardiology', '1234567890', '123 Medical St, City', 500.00, 10, 'MBBS, MD Cardiology', 'default_doctor.jpg', 'active', '2025-05-30 08:51:09'),
(2, 'Dr. Sarah Johnson', 'sarah@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dermatology', '1234567891', '124 Medical St, City', 400.00, 8, 'MBBS, MD Dermatology', 'default_doctor.jpg', 'active', '2025-05-30 08:51:09'),
(3, 'Dr. Michael Brown', 'michael@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Orthopedics', '1234567892', '125 Medical St, City', 600.00, 12, 'MBBS, MS Orthopedics', 'default_doctor.jpg', 'active', '2025-05-30 08:51:09'),
(4, 'Dr. Emily Davis', 'emily@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pediatrics', '1234567893', '126 Medical St, City', 350.00, 6, 'MBBS, MD Pediatrics', 'default_doctor.jpg', 'active', '2025-05-30 08:51:09');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_availability`
--

CREATE TABLE `doctor_availability` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('available','unavailable') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_availability`
--

INSERT INTO `doctor_availability` (`id`, `doctor_id`, `day_of_week`, `start_time`, `end_time`, `status`) VALUES
(1, 1, 'Monday', '09:00:00', '17:00:00', 'available'),
(2, 1, 'Tuesday', '09:00:00', '17:00:00', 'available'),
(3, 1, 'Wednesday', '09:00:00', '17:00:00', 'available'),
(4, 1, 'Thursday', '09:00:00', '17:00:00', 'available'),
(5, 1, 'Friday', '09:00:00', '17:00:00', 'available'),
(6, 2, 'Monday', '10:00:00', '18:00:00', 'available'),
(7, 2, 'Tuesday', '10:00:00', '18:00:00', 'available'),
(8, 2, 'Wednesday', '10:00:00', '18:00:00', 'available'),
(9, 2, 'Thursday', '10:00:00', '18:00:00', 'available'),
(10, 2, 'Friday', '10:00:00', '18:00:00', 'available'),
(11, 3, 'Monday', '08:00:00', '16:00:00', 'available'),
(12, 3, 'Tuesday', '08:00:00', '16:00:00', 'available'),
(13, 3, 'Wednesday', '08:00:00', '16:00:00', 'available'),
(14, 3, 'Thursday', '08:00:00', '16:00:00', 'available'),
(15, 3, 'Saturday', '08:00:00', '14:00:00', 'available'),
(16, 4, 'Monday', '11:00:00', '19:00:00', 'available'),
(17, 4, 'Tuesday', '11:00:00', '19:00:00', 'available'),
(18, 4, 'Wednesday', '11:00:00', '19:00:00', 'available'),
(19, 4, 'Thursday', '11:00:00', '19:00:00', 'available'),
(20, 4, 'Friday', '11:00:00', '19:00:00', 'available');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `emergency_contact` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `name`, `email`, `password`, `phone`, `address`, `date_of_birth`, `gender`, `blood_group`, `emergency_contact`, `created_at`) VALUES
(1, 'Sample Patient', 'patient@example.com', '$2y$10$IOsACqiidu9hpNKHrfeYcuNGasoI8dFffjs6E.IXmU3I2km/lo3HW', '0771234567', '123 Sample Street, Sample City', '1990-01-01', 'female', 'A+', '0771234567', '2025-06-18 17:08:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD CONSTRAINT `doctor_availability_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
-- Added for forgot password functionality
--

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `user_type` enum('patient','doctor','admin') NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_type` (`email`,`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Add temporary password tracking columns to existing tables
-- For forgot password functionality
--

-- Add is_temp_password column to patients table
ALTER TABLE `patients` ADD COLUMN IF NOT EXISTS `is_temp_password` TINYINT(1) DEFAULT 0;

-- Add is_temp_password column to doctors table  
ALTER TABLE `doctors` ADD COLUMN IF NOT EXISTS `is_temp_password` TINYINT(1) DEFAULT 0;

-- Add is_temp_password column to admin table
ALTER TABLE `admin` ADD COLUMN IF NOT EXISTS `is_temp_password` TINYINT(1) DEFAULT 0;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

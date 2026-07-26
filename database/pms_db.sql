-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 23, 2026 at 07:19 AM
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
-- Database: `pms_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_booking_reference` (OUT `ref` VARCHAR(20))   BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE new_ref VARCHAR(20);
    
    REPEAT
        SET new_ref = CONCAT(
            'BK',
            DATE_FORMAT(CURDATE(), '%y%m%d'),
            LPAD(FLOOR(RAND() * 10000), 4, '0')
        );
        SELECT COUNT(*) INTO done FROM online_bookings WHERE booking_reference = new_ref;
    UNTIL done = 0 END REPEAT;
    
    SET ref = new_ref;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sync_booking_to_pms` (IN `booking_id_param` INT)   BEGIN
    DECLARE v_guest_name VARCHAR(100);
    DECLARE v_room_id INT;
    DECLARE v_check_in DATE;
    DECLARE v_check_out DATE;
    DECLARE v_nights INT;
    DECLARE v_adults INT;
    DECLARE v_children INT;
    DECLARE v_room_rate DECIMAL(10,2);
    DECLARE v_email VARCHAR(100);
    DECLARE v_phone VARCHAR(20);
    DECLARE v_nationality VARCHAR(50);
    DECLARE v_requests TEXT;
    DECLARE v_pms_id INT;
    
    -- Get booking data
    SELECT 
        guest_name, room_id, check_in, check_out, 
        num_of_nights, adults, children, room_rate,
        guest_email, guest_phone, guest_nationality, special_requests
    INTO 
        v_guest_name, v_room_id, v_check_in, v_check_out,
        v_nights, v_adults, v_children, v_room_rate,
        v_email, v_phone, v_nationality, v_requests
    FROM online_bookings 
    WHERE booking_id = booking_id_param 
    AND synced_to_pms = 0;
    
    -- Insert into PMS reservations table
    INSERT INTO reservations (
        guest_name, room_id, check_in, check_out,
        num_of_nights, adults, children, room_rate,
        status, email, mobile, nationality,
        booking_source, special_requests
    ) VALUES (
        v_guest_name, v_room_id, v_check_in, v_check_out,
        v_nights, v_adults, v_children, v_room_rate,
        'Pending', v_email, v_phone, v_nationality,
        'Online Booking', v_requests
    );
    
    SET v_pms_id = LAST_INSERT_ID();
    
    -- Update online booking with PMS ID
    UPDATE online_bookings 
    SET synced_to_pms = 1, 
        pms_reservation_id = v_pms_id,
        booking_status = 'confirmed'
    WHERE booking_id = booking_id_param;
    
    -- Return the PMS reservation ID
    SELECT v_pms_id as pms_reservation_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agent_commission_log`
--

CREATE TABLE `agent_commission_log` (
  `log_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `booking_amount` decimal(10,2) NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL,
  `commission_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `paid_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `allotments`
--

CREATE TABLE `allotments` (
  `allotment_id` int(11) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `total_blocked` int(11) NOT NULL,
  `rooms_picked` int(11) DEFAULT 0,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `release_date` date NOT NULL,
  `status` enum('Active','Released','Expired') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `allotments`
--

INSERT INTO `allotments` (`allotment_id`, `company_name`, `room_type`, `total_blocked`, `rooms_picked`, `date_from`, `date_to`, `release_date`, `status`, `notes`, `created_at`) VALUES
(1, 'jetwing', 'Apartment', 3, 0, '2026-06-29', '2026-06-30', '2026-07-01', 'Expired', '', '2026-06-30 08:52:27'),
(2, 'jetwing', 'Deluxe Room', 5, 0, '2026-07-07', '2026-07-14', '2026-07-12', 'Expired', '', '2026-07-07 14:00:40');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `att_id` int(11) NOT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `status` enum('present','absent','leave') DEFAULT NULL,
  `overtime_hours` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `user_id`, `username`, `action`, `description`, `timestamp`) VALUES
(1, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-06-25 09:06:19'),
(2, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-06-25 09:06:56'),
(3, 1, 'admin', 'BACKUP', 'Downloaded full database backup', '2026-06-25 09:07:36'),
(4, 2, 'pubudu', 'LOGIN', 'User successfully logged in from login page', '2026-06-25 09:08:26'),
(5, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-06-25 09:11:55'),
(6, 1, 'admin', 'ROOM_RATE', 'Updated prices for Room Type ID: 1', '2026-06-25 09:15:35'),
(7, 1, 'admin', 'ROOM_RATE', 'Updated prices for Room Type ID: 1', '2026-06-25 09:22:37'),
(8, 1, 'admin', 'ADD_CATEGORY', 'Created new room category: Apartment', '2026-06-25 09:24:39'),
(9, 1, 'admin', 'ADD_ROOM', 'Added room number: 111 (Deluxe Room)', '2026-06-25 09:25:19'),
(10, 1, 'admin', 'SETTINGS', 'Updated Hotel Profile & Tax Configurations', '2026-06-25 09:29:08'),
(11, 1, 'admin', 'ADD_PROMO', 'Created promo code: NEWYEAR (5%)', '2026-06-25 09:32:54'),
(12, 1, 'admin', 'ADD_PROMO', 'Created promo code: VESAK (5%)', '2026-06-25 09:37:16'),
(13, 1, 'admin', 'CHECKOUT', 'Checked out Guest from Room: 108', '2026-06-25 10:31:12'),
(14, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-06-26 17:04:40'),
(15, 1, 'admin', 'CHECKOUT', 'Checked out Guest from Room: 108', '2026-06-26 17:08:50'),
(16, 1, 'admin', 'BACKUP', 'Downloaded full database backup', '2026-06-26 17:12:53'),
(17, 2, 'pubudu', 'LOGIN', 'User successfully logged in from login page', '2026-06-26 17:13:25'),
(18, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-06-30 07:42:17'),
(19, 1, 'admin', 'BACKUP', 'Downloaded full database backup', '2026-06-30 08:10:29'),
(20, 1, 'admin', 'CHECKOUT', 'Checked out Guest from Room: 107', '2026-06-30 08:47:42'),
(21, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-03 11:27:42'),
(22, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-03 11:27:42'),
(23, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-07 12:17:56'),
(24, 1, 'admin', 'CHECKOUT', 'Checked out Guest from Room: 104', '2026-07-07 17:59:44'),
(25, 1, 'admin', 'CHECKOUT', 'Checked out Guest from Room: 109', '2026-07-07 17:59:47'),
(26, 1, 'admin', 'CHECKOUT', 'Checked out Guest from Room: 106', '2026-07-07 18:15:46'),
(27, 1, 'admin', 'CHECKOUT', 'Checked out Guest from Room: 111', '2026-07-07 18:15:51'),
(28, 1, 'admin', 'CHECKOUT', 'Quick checked out Guest from Room: 107', '2026-07-07 18:44:27'),
(29, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-08 03:19:51'),
(30, 1, 'admin', 'BULK_CHECKOUT', 'Bulk checked out 2 guests', '2026-07-08 03:23:30'),
(31, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-08 03:39:59'),
(32, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-08 03:50:21'),
(33, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-08 04:13:27'),
(34, 1, 'admin', 'BACKUP', 'Downloaded full database backup', '2026-07-08 04:24:05'),
(35, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-08 16:57:48'),
(36, 1, 'admin', 'CHECKOUT', 'Quick checked out Guest from Room: 108', '2026-07-08 17:01:50'),
(37, 1, 'admin', 'CHECKOUT', 'Quick checked out Guest from Room: 105', '2026-07-08 18:20:08'),
(38, 1, 'admin', 'CHECKOUT', 'Quick checked out Guest from Room: 102', '2026-07-08 18:23:05'),
(39, 1, 'admin', 'CHECKOUT', 'Quick checked out Guest from Room: 108', '2026-07-08 18:37:28'),
(40, 6, 'chamod', 'LOGIN', 'User successfully logged in from login page', '2026-07-08 18:38:57'),
(41, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-09 03:41:04'),
(42, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-09 06:22:39'),
(43, 1, 'admin', 'CHECKOUT', 'Quick checked out Guest from Room: 101', '2026-07-09 06:23:38'),
(44, 1, 'admin', 'BACKUP', 'Downloaded full database backup', '2026-07-09 06:28:18'),
(45, 1, 'admin', 'CHECKOUT', 'Quick checked out Guest from Room: 108', '2026-07-09 06:30:52'),
(46, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-09 09:42:29'),
(47, 1, 'admin', 'ROOM_RATE', 'Updated prices for Room Type ID: 1', '2026-07-09 10:10:48'),
(48, 1, 'admin', 'ADD_CATEGORY', 'Created new room category: change room', '2026-07-09 10:11:54'),
(49, 1, 'admin', 'ADD_ROOM', 'Added room number: 112', '2026-07-09 10:12:44'),
(50, 1, 'admin', 'ADD_CATEGORY', 'Created new room category: change room', '2026-07-09 10:19:49'),
(51, 1, 'admin', 'ADD_CATEGORY', 'Created new room category: change room', '2026-07-09 10:19:57'),
(52, 1, 'admin', 'RATE_UPDATE', 'Updated rate for reservation #45 to LKR 8000', '2026-07-09 10:42:56'),
(53, 1, 'admin', 'RATE_UPDATE', 'Updated rate for reservation #45 to LKR 7000', '2026-07-09 10:45:23'),
(54, 1, 'admin', 'RATE_UPDATE', 'Updated rate for reservation #45 to LKR 6000', '2026-07-09 10:56:25'),
(55, 1, 'admin', 'FOLIO_POST', 'Posted charge of LKR 5000 for reservation #45', '2026-07-09 10:58:20'),
(56, 1, 'admin', 'RATE_UPDATE', 'Updated rate for reservation #48 to LKR 5000', '2026-07-09 11:05:02'),
(57, 1, 'admin', 'FOLIO_POST', 'Posted payment of LKR 5000 for reservation #48', '2026-07-09 11:05:47'),
(58, 1, 'admin', 'RATE_UPDATE', 'Updated rate for reservation #46 to LKR 9000', '2026-07-09 11:06:38'),
(59, 1, 'admin', 'FOLIO_POST', 'Posted charge of LKR 5000 for reservation #49', '2026-07-09 11:22:38'),
(60, 1, 'admin', 'FOLIO_POST', 'Posted charge of LKR 400 for reservation #45', '2026-07-09 11:23:13'),
(61, 1, 'admin', 'RATE_UPDATE', 'Updated rate for reservation #50 to LKR 1500', '2026-07-09 11:47:23'),
(62, 1, 'admin', 'RATE_UPDATE', 'Updated rate for reservation #45 to LKR 850', '2026-07-09 11:48:34'),
(63, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-11 07:36:28'),
(64, 7, 'piyumi', 'LOGIN', 'User successfully logged in from login page', '2026-07-11 07:40:00'),
(65, 7, 'piyumi', 'ROOM_RATE', 'Updated prices for Room Type ID: 2', '2026-07-11 07:55:43'),
(66, 7, 'piyumi', 'RATE_UPDATE', 'Updated rate for reservation #51 to LKR 13000', '2026-07-11 07:59:01'),
(67, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-12 04:21:36'),
(68, 1, 'admin', 'FOLIO_POST', 'Posted advance of LKR 5000 for reservation #52', '2026-07-12 05:14:28'),
(69, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-12 08:15:33'),
(70, 8, 'hiruni', 'LOGIN', 'User successfully logged in from login page', '2026-07-12 08:17:45'),
(71, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-12 11:55:29'),
(72, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-12 14:15:27'),
(73, 6, 'chamod', 'LOGIN', 'User successfully logged in from login page', '2026-07-12 14:15:43'),
(74, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-12 14:37:26'),
(75, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-17 17:32:44'),
(76, 7, 'piyumi', 'LOGIN', 'User successfully logged in from login page', '2026-07-17 17:33:51'),
(77, 7, 'piyumi', 'CHECKOUT', 'Quick checked out Guest from Room: 105', '2026-07-17 17:38:31'),
(78, 7, 'piyumi', 'CHECKOUT', 'Quick checked out Guest from Room: 107', '2026-07-17 17:39:01'),
(79, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-17 18:03:53'),
(80, 6, 'chamod', 'LOGIN', 'User successfully logged in from login page', '2026-07-17 18:17:27'),
(81, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-17 18:19:52'),
(82, 9, 'nadun', 'LOGIN', 'User successfully logged in from login page', '2026-07-17 18:20:54'),
(83, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-17 18:29:43'),
(84, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-18 04:44:00'),
(85, 1, 'admin', 'FOLIO_POST', 'Posted advance of LKR 1500 for reservation #61', '2026-07-18 05:08:48'),
(86, 1, 'admin', 'FOLIO_POST', 'Posted charge of LKR 8000 for reservation #59', '2026-07-18 05:31:33'),
(87, 1, 'admin', 'FOLIO_POST', 'Posted payment of LKR 5000 for reservation #59', '2026-07-18 05:32:30'),
(88, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #58 back to Pending', '2026-07-18 09:51:13'),
(89, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-18 09:57:53'),
(90, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-18 09:58:24'),
(91, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-18 10:02:10'),
(92, 6, 'chamod', 'LOGIN', 'User successfully logged in from login page', '2026-07-18 10:02:23'),
(93, 6, 'chamod', 'FOLIO_POST', 'Posted advance of LKR 10000 for reservation #60', '2026-07-18 10:09:57'),
(94, 6, 'chamod', 'FOLIO_POST', 'Posted payment of LKR 7000 for reservation #60', '2026-07-18 10:10:27'),
(95, 6, 'chamod', 'MOVE_TO_ARRIVALS', 'Moved reservation #59 back to Pending', '2026-07-18 10:10:52'),
(96, 6, 'chamod', 'FOLIO_POST', 'Posted payment of LKR 20000 for reservation #65', '2026-07-18 10:39:24'),
(97, 6, 'chamod', 'CHECKOUT', 'Quick checked out Guest from Room: 110', '2026-07-18 10:40:07'),
(98, 6, 'chamod', 'BULK_CHECKOUT', 'Bulk checked out 1 guests', '2026-07-18 10:40:54'),
(99, 6, 'chamod', 'MOVE_TO_ARRIVALS', 'Moved reservation #45 back to Pending', '2026-07-18 10:50:05'),
(100, 6, 'chamod', 'FOLIO_POST', 'Posted payment of LKR 7000 for reservation #66', '2026-07-18 10:51:57'),
(101, 6, 'chamod', 'FOLIO_POST', 'Posted payment of LKR 8000 for reservation #63', '2026-07-18 10:59:22'),
(102, 6, 'chamod', 'FOLIO_POST', 'Posted rebate of LKR 20000 for reservation #63', '2026-07-18 10:59:53'),
(103, 6, 'chamod', 'FOLIO_POST', 'Posted payment of LKR 7000 for reservation #68', '2026-07-18 11:12:35'),
(104, 6, 'chamod', 'FOLIO_POST', 'Posted advance of LKR 5000 for reservation #68', '2026-07-18 11:13:20'),
(105, 6, 'chamod', 'FOLIO_POST', 'Posted charge of LKR 5000 for reservation #68', '2026-07-18 11:13:48'),
(106, 6, 'chamod', 'FOLIO_POST', 'Posted rebate of LKR 1000 for reservation #68', '2026-07-18 11:14:01'),
(107, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-20 09:06:44'),
(108, 1, 'admin', 'CHECKOUT', 'Quick checked out Guest from Room: 105', '2026-07-20 09:07:54'),
(109, 1, 'admin', 'BULK_CHECKOUT', 'Bulk checked out 9 guests', '2026-07-20 09:08:32'),
(110, 1, 'admin', 'FOLIO_POST', 'Posted payment of LKR 8500 for reservation #70', '2026-07-20 09:12:20'),
(111, 1, 'admin', 'FOLIO_POST', 'Posted rebate of LKR 500 for reservation #70', '2026-07-20 10:19:03'),
(112, 1, 'admin', 'FOLIO_POST', 'Posted advance of LKR 5000 for reservation #70', '2026-07-20 10:44:37'),
(113, 1, 'admin', 'VOID_POSTING', 'Voided transaction #58 (Amount: LKR 5000.00, Type: payment) - Reason: wrong guest', '2026-07-20 10:55:43'),
(114, 1, 'admin', 'VOID_POSTING', 'Voided transaction #55 (Amount: LKR 8500.00, Type: payment) - Reason: fgfgg', '2026-07-20 10:56:14'),
(115, 1, 'admin', 'FOLIO_POST', 'Posted payment of LKR 11500 for reservation #70', '2026-07-20 11:18:52'),
(116, 1, 'admin', 'CHECKOUT', 'Quick checked out Guest from Room: 108', '2026-07-20 11:28:43'),
(117, 1, 'admin', 'FOLIO_RESET', 'Reset folio for reservation #71, re-added room charge LKR 14000', '2026-07-20 13:48:21'),
(118, 1, 'admin', 'CARD_PAYMENT', 'Card payment of LKR 14000 for reservation #71. Card: ****8547, Holder: pubudu', '2026-07-20 14:15:31'),
(119, 1, 'admin', 'CURRENCY_EXCHANGE', 'Exchanged 100 USD to LKR 31000 for reservation #71', '2026-07-20 15:05:00'),
(120, 1, 'admin', 'CURRENCY_EXCHANGE', 'Exchanged 100 USD to LKR 31000 for reservation #71', '2026-07-20 15:05:53'),
(121, 1, 'admin', 'ROOM_STATUS', 'Changed room 111 from cleaning to occupied', '2026-07-20 15:29:23'),
(122, 1, 'admin', 'ROOM_STATUS', 'Changed room 112 from maintenance to available', '2026-07-20 15:30:16'),
(123, 1, 'admin', 'ROOM_STATUS', 'Changed room 112 from available to cleaning', '2026-07-20 15:37:06'),
(124, 1, 'admin', 'FOLIO_POST', 'Posted payment of LKR 62000 for reservation #71', '2026-07-20 15:51:14'),
(125, 1, 'admin', 'FOLIO_POST', 'Posted charge of LKR 124000 for reservation #71', '2026-07-20 15:51:36'),
(126, 1, 'admin', 'CHECKOUT', 'Quick checked out Guest from Room: 110', '2026-07-20 15:51:56'),
(127, 1, 'admin', 'FOLIO_RESET', 'Reset folio for reservation #68, re-added room charge LKR 14000', '2026-07-20 15:52:46'),
(128, 1, 'admin', 'FOLIO_POST', 'Posted payment of LKR 14000 for reservation #68', '2026-07-20 15:52:57'),
(129, 1, 'admin', 'CHECKOUT', 'Quick checked out Guest from Room: 111', '2026-07-20 15:53:23'),
(130, 1, 'admin', 'ROOM_STATUS', 'Room 101 changed from occupied to available', '2026-07-20 15:54:10'),
(131, 1, 'admin', 'ROOM_STATUS', 'Room 102 changed from cleaning to available (Reason: fgfgg)', '2026-07-20 15:54:26'),
(132, 1, 'admin', 'LAUNDRY', 'Laundry order #2 - Saree (Dry Clean) x 1 = LKR 12', '2026-07-20 15:55:31'),
(133, 1, 'admin', 'ROOM_STATUS', 'Room 103 changed from occupied to available (Reason: wrong guest)', '2026-07-20 16:09:33'),
(134, 1, 'admin', 'ROOM_STATUS', 'Room 105 changed from cleaning to available', '2026-07-20 16:18:52'),
(135, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-21 04:13:27'),
(136, 1, 'admin', 'ROOM_STATUS', 'Room 101 changed from available to maintenance', '2026-07-21 04:32:18'),
(137, 1, 'admin', 'ROOM_STATUS', 'Room 101 changed from maintenance to available', '2026-07-21 04:45:21'),
(138, 1, 'admin', 'ROOM_STATUS', 'Room 102 changed from available to occupied', '2026-07-21 04:51:35'),
(139, 1, 'admin', 'ROOM_STATUS', 'Room 101 changed from available to maintenance', '2026-07-21 06:18:38'),
(140, 1, 'admin', 'ROOM_STATUS', 'Room 112 changed from cleaning to available', '2026-07-21 06:18:42'),
(141, 1, 'admin', 'ROOM_STATUS', 'Room 108 changed from cleaning to maintenance', '2026-07-21 06:51:22'),
(142, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-21 06:56:50'),
(143, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-21 07:08:49'),
(144, 1, 'admin', 'FOLIO_POST', 'Posted payment of LKR 14000 for reservation #72', '2026-07-21 07:14:11'),
(145, 1, 'admin', 'FOLIO_POST', 'Posted rebate of LKR 1000 for reservation #72', '2026-07-21 07:14:46'),
(146, 1, 'admin', 'FOLIO_POST', 'Posted charge of LKR 1000 for reservation #72', '2026-07-21 07:15:58'),
(147, 1, 'admin', 'FOLIO_POST', 'Posted payment of LKR 2000 for reservation #72', '2026-07-21 07:16:13'),
(148, 1, 'admin', 'CURRENCY_EXCHANGE', 'Exchanged 500 AED to LKR 42000 for reservation #72', '2026-07-21 07:17:37'),
(149, 1, 'admin', 'FOLIO_POST', 'Posted payment of LKR 37000 for reservation #72', '2026-07-21 07:20:54'),
(150, 1, 'admin', 'FOLIO_POST', 'Posted charge of LKR 74000 for reservation #72', '2026-07-21 07:21:15'),
(151, 1, 'admin', 'ROOM_STATUS', 'Room 101 changed from maintenance to available', '2026-07-21 07:25:09'),
(152, 1, 'admin', 'ROOM_STATUS', 'Room 102 changed from occupied to available', '2026-07-21 07:38:41'),
(153, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 07:50:15'),
(154, 1, 'admin', 'ROOM_STATUS', 'Room 104 changed from cleaning to available', '2026-07-21 08:00:38'),
(155, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 08:03:04'),
(156, 1, 'admin', 'ROOM_STATUS', 'Room 111 changed from available to cleaning', '2026-07-21 08:03:43'),
(157, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 08:04:30'),
(158, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 08:08:32'),
(159, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 08:15:25'),
(160, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 08:49:08'),
(161, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 08:49:15'),
(162, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 08:49:22'),
(163, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 08:49:32'),
(164, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 08:51:39'),
(165, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 08:51:56'),
(166, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 08:55:03'),
(167, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 08:55:10'),
(168, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 08:55:17'),
(169, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 08:55:54'),
(170, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 08:58:21'),
(171, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 09:03:00'),
(172, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 09:04:49'),
(173, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 09:09:17'),
(174, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 09:09:23'),
(175, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 09:10:33'),
(176, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 09:10:56'),
(177, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 09:12:37'),
(178, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 09:12:44'),
(179, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 09:16:06'),
(180, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 09:16:26'),
(181, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 09:18:20'),
(182, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 09:18:27'),
(183, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 09:18:32'),
(184, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 09:20:06'),
(185, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 09:20:11'),
(186, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 09:21:37'),
(187, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 09:21:42'),
(188, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 09:24:42'),
(189, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 09:24:47'),
(190, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 09:24:55'),
(191, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 09:26:56'),
(192, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 09:28:10'),
(193, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 09:28:16'),
(194, 1, 'admin', 'ROOM_STATUS', 'Room 111 changed from available to occupied', '2026-07-21 09:29:50'),
(195, 1, 'admin', 'ROOM_STATUS', 'Room 111 changed from occupied to cleaning', '2026-07-21 09:33:04'),
(196, 1, 'admin', 'ROOM_STATUS', 'Room 111 changed from cleaning to available', '2026-07-21 09:33:28'),
(197, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 111', '2026-07-21 09:33:38'),
(198, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #72 back to Pending', '2026-07-21 09:33:48'),
(199, 1, 'admin', 'ROOM_STATUS', 'Room 111 changed from available to occupied', '2026-07-21 09:35:01'),
(200, 1, 'admin', 'ROOM_STATUS', 'Room 111 changed from occupied to available', '2026-07-21 09:40:32'),
(201, 1, 'admin', 'FOLIO_RESET', 'Reset folio for reservation #72, re-added room charge LKR 14000', '2026-07-21 09:41:22'),
(202, 1, 'admin', 'FOLIO_POST', 'Posted payment of LKR 14000 for reservation #72', '2026-07-21 09:41:57'),
(203, 1, 'admin', 'CHECKOUT', 'Quick checked out Guest from Room: 111', '2026-07-21 09:42:09'),
(204, 1, 'admin', 'ROOM_STATUS', 'Room 113 changed from occupied to available', '2026-07-21 09:49:05'),
(205, 1, 'admin', 'CHECKIN', 'Checked in pubudu sathsara to Room 113', '2026-07-21 09:49:16'),
(206, 1, 'admin', 'FOLIO_POST', 'Posted payment of LKR 14000 for reservation #73', '2026-07-21 09:49:32'),
(207, 1, 'admin', 'CHECKOUT', 'Quick checked out Guest from Room: 113', '2026-07-21 09:49:40'),
(208, 1, 'admin', 'ROOM_STATUS', 'Room 104 changed from occupied to available', '2026-07-21 09:56:51'),
(209, 1, 'admin', 'FOLIO_POST', 'Posted payment of LKR 8500 for reservation #74', '2026-07-21 09:57:28'),
(210, 1, 'admin', 'CHECKOUT', 'Checked out Guest from Room: 104', '2026-07-21 09:57:41'),
(211, 1, 'admin', 'ROOM_STATUS', 'Room 101 changed from available to cleaning', '2026-07-21 10:29:26'),
(212, 1, 'admin', 'ROOM_STATUS', 'Room 120 changed from available to cleaning', '2026-07-21 10:32:17'),
(213, 1, 'admin', 'ROOM_STATUS', 'Room 149 changed from available to occupied', '2026-07-21 10:32:23'),
(214, 1, 'admin', 'ROOM_STATUS', 'Room 101 changed from cleaning to available', '2026-07-21 10:37:00'),
(215, 1, 'admin', 'ROOM_STATUS', 'Room 104 changed from cleaning to maintenance', '2026-07-21 10:46:19'),
(216, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-21 15:09:10'),
(217, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-21 15:09:24'),
(218, 1, 'admin', 'ADD_ROOM', 'Added room number: 150', '2026-07-21 15:09:58'),
(219, 1, 'admin', 'ROOM_STATUS', 'Room 115 changed from occupied to available', '2026-07-21 16:35:32'),
(220, 1, 'admin', 'CHECKIN', 'Checked in nisansala to Room 115', '2026-07-21 16:35:42'),
(221, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-22 04:47:17'),
(222, 1, 'admin', 'ROOM_STATUS', 'Room 105 changed from occupied to available', '2026-07-22 05:09:51'),
(223, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #78 back to Pending', '2026-07-22 05:18:27'),
(224, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #79 back to Pending', '2026-07-22 05:37:27'),
(225, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #77 back to Pending', '2026-07-22 05:43:16'),
(226, 1, 'admin', 'ROOM_STATUS', 'Room 109 changed from occupied to available', '2026-07-22 05:44:48'),
(227, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #80 back to Pending', '2026-07-22 05:45:36'),
(228, 1, 'admin', 'ROOM_STATUS', 'Room 101 changed from occupied to available', '2026-07-22 05:46:55'),
(229, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #81 back to Pending', '2026-07-22 05:52:19'),
(230, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #81 back to Pending', '2026-07-22 07:09:18'),
(231, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #80 back to Pending', '2026-07-22 07:09:22'),
(232, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #81 back to Pending', '2026-07-22 07:10:32'),
(233, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #80 back to Pending', '2026-07-22 07:10:36'),
(234, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #81 back to Pending', '2026-07-22 07:21:10'),
(235, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #80 back to Pending', '2026-07-22 07:21:17'),
(236, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #81 back to Pending', '2026-07-22 09:25:33'),
(237, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #80 back to Pending', '2026-07-22 09:25:37'),
(238, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #81 back to Pending', '2026-07-22 10:42:23'),
(239, 1, 'admin', 'ROOM_STATUS', 'Room 104 changed from maintenance to available', '2026-07-22 12:36:22'),
(240, 1, 'admin', 'ROOM_STATUS', 'Room 101 changed from occupied to available', '2026-07-22 12:36:30'),
(241, 1, 'admin', 'ROOM_STATUS', 'Room 110 changed from cleaning to available', '2026-07-22 12:36:41'),
(242, 1, 'admin', 'ROOM_STATUS', 'Room 106 changed from cleaning to available', '2026-07-22 13:11:07'),
(243, 1, 'admin', 'ROOM_STATUS', 'Room 108 changed from occupied to available', '2026-07-22 13:11:17'),
(244, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-22 14:39:52'),
(245, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-22 15:50:18'),
(246, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #78 back to Pending', '2026-07-22 16:07:04'),
(247, 1, 'admin', 'MOVE_TO_ARRIVALS', 'Moved reservation #80 back to Pending', '2026-07-22 16:07:25'),
(248, 1, 'admin', 'ROOM_STATUS', 'Room 105 changed from available to cleaning', '2026-07-22 16:08:14'),
(249, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-22 16:26:31'),
(250, 1, 'admin', 'CURRENCY_EXCHANGE', 'Exchanged 100 EUR to LKR 34000 for reservation #81', '2026-07-22 16:34:57'),
(251, 1, 'admin', 'ROOM_STATUS', 'Room 101 changed from available to occupied', '2026-07-22 16:35:45'),
(252, 1, 'admin', 'ADD_ROOM', 'Added room number: 151', '2026-07-22 16:38:30'),
(253, 1, 'admin', 'LOGIN', 'User successfully logged in from login page', '2026-07-23 03:54:58'),
(254, 1, 'admin', 'FOLIO_RESET', 'Reset folio for reservation #81, re-added room charge LKR 14000', '2026-07-23 03:56:48'),
(255, 1, 'admin', 'FOLIO_POST', 'Posted payment of LKR 14000 for reservation #81', '2026-07-23 03:57:13'),
(256, 1, 'admin', 'CHECKOUT', 'Checked out Guest from Room: 101', '2026-07-23 03:57:21'),
(257, 1, 'admin', 'FOLIO_POST', 'Posted payment of LKR 8000 for reservation #82', '2026-07-23 03:57:40'),
(258, 1, 'admin', 'CHECKOUT', 'Checked out Guest from Room: 105', '2026-07-23 03:57:49'),
(259, 1, 'admin', 'CHECKOUT', 'Checked out Guest from Room: 114', '2026-07-23 03:58:11'),
(260, 1, 'admin', 'ROOM_STATUS', 'Room 102 changed from occupied to available', '2026-07-23 04:44:53');

-- --------------------------------------------------------

--
-- Table structure for table `credit_debit_notes`
--

CREATE TABLE `credit_debit_notes` (
  `note_id` int(11) NOT NULL,
  `res_id` int(11) NOT NULL,
  `folio_id` int(11) NOT NULL,
  `note_type` enum('credit','debit') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text NOT NULL,
  `reference_trans_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `status` enum('active','voided') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `credit_debit_notes`
--

INSERT INTO `credit_debit_notes` (`note_id`, `res_id`, `folio_id`, `note_type`, `amount`, `reason`, `reference_trans_id`, `created_at`, `created_by`, `status`) VALUES
(2, 70, 27, 'debit', 3000.00, 'extra bed', 62, '2026-07-20 16:47:36', 1, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `deposit_refunds`
--

CREATE TABLE `deposit_refunds` (
  `refund_id` int(11) NOT NULL,
  `res_id` int(11) NOT NULL,
  `folio_id` int(11) NOT NULL,
  `deposit_amount` decimal(10,2) NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `refund_date` datetime DEFAULT current_timestamp(),
  `processed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `alt_body` text DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `emp_id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `base_salary` decimal(10,2) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `status` enum('active','resigned') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exchange_rates`
--

CREATE TABLE `exchange_rates` (
  `rate_id` int(11) NOT NULL,
  `currency_code` varchar(10) NOT NULL,
  `currency_name` varchar(50) NOT NULL,
  `symbol` varchar(5) DEFAULT NULL,
  `rate_to_lkr` decimal(10,4) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exchange_rates`
--

INSERT INTO `exchange_rates` (`rate_id`, `currency_code`, `currency_name`, `symbol`, `rate_to_lkr`, `status`, `updated_at`) VALUES
(1, 'USD', 'US Dollar', '$', 310.0000, 'active', '2026-07-20 15:01:38'),
(2, 'EUR', 'Euro', '€', 340.0000, 'active', '2026-07-20 15:01:38'),
(3, 'GBP', 'British Pound', '£', 400.0000, 'active', '2026-07-20 15:01:38'),
(4, 'AED', 'UAE Dirham', 'د.إ', 84.0000, 'active', '2026-07-20 15:01:38'),
(5, 'AUD', 'Australian Dollar', 'A$', 205.0000, 'active', '2026-07-20 15:01:38'),
(6, 'CAD', 'Canadian Dollar', 'C$', 225.0000, 'active', '2026-07-20 15:01:38'),
(7, 'CHF', 'Swiss Franc', 'Fr', 350.0000, 'active', '2026-07-20 15:01:38'),
(8, 'JPY', 'Japanese Yen', '¥', 2.0000, 'active', '2026-07-20 15:01:38'),
(9, 'SAR', 'Saudi Riyal', 'ر.س', 83.0000, 'active', '2026-07-20 15:01:38'),
(10, 'SGD', 'Singapore Dollar', 'S$', 230.0000, 'active', '2026-07-20 15:01:38');

-- --------------------------------------------------------

--
-- Table structure for table `exchange_transactions`
--

CREATE TABLE `exchange_transactions` (
  `exchange_id` int(11) NOT NULL,
  `res_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `currency` varchar(10) NOT NULL,
  `amount_given` decimal(10,2) NOT NULL,
  `exchange_rate` decimal(10,4) NOT NULL,
  `amount_received` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exchange_transactions`
--

INSERT INTO `exchange_transactions` (`exchange_id`, `res_id`, `room_id`, `currency`, `amount_given`, `exchange_rate`, `amount_received`, `notes`, `created_by`, `created_at`) VALUES
(1, 71, 10, 'USD', 100.00, 310.0000, 31000.00, '', 1, '2026-07-20 20:35:00'),
(2, 71, 10, 'USD', 100.00, 310.0000, 31000.00, '', 1, '2026-07-20 20:35:53'),
(3, 72, 11, 'AED', 500.00, 84.0000, 42000.00, '', 1, '2026-07-21 12:47:37'),
(4, 81, 1, 'EUR', 100.00, 340.0000, 34000.00, '', 1, '2026-07-22 22:04:57');

-- --------------------------------------------------------

--
-- Table structure for table `folios`
--

CREATE TABLE `folios` (
  `folio_id` int(11) NOT NULL,
  `res_id` int(11) NOT NULL,
  `status` enum('Open','Closed','Settled') DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `folios`
--

INSERT INTO `folios` (`folio_id`, `res_id`, `status`, `created_at`) VALUES
(1, 29, 'Open', '2026-07-08 11:50:03'),
(2, 34, 'Open', '2026-07-08 11:50:03'),
(3, 37, 'Open', '2026-07-08 11:50:03'),
(4, 24, 'Open', '2026-07-08 11:50:03'),
(5, 26, 'Open', '2026-07-08 11:50:03'),
(6, 38, 'Open', '2026-07-08 11:50:03'),
(7, 18, 'Open', '2026-07-08 11:50:03'),
(8, 23, 'Open', '2026-07-08 11:50:03'),
(9, 25, 'Open', '2026-07-08 11:50:03'),
(10, 27, 'Open', '2026-07-08 11:50:03'),
(11, 35, 'Open', '2026-07-08 11:50:03'),
(12, 19, 'Open', '2026-07-08 11:50:03'),
(13, 30, 'Open', '2026-07-08 11:50:03'),
(14, 31, 'Open', '2026-07-08 11:50:03'),
(15, 32, 'Open', '2026-07-08 11:50:03'),
(16, 33, 'Open', '2026-07-08 11:50:03'),
(17, 17, 'Open', '2026-07-08 11:50:03'),
(18, 36, 'Open', '2026-07-08 11:50:03'),
(19, 20, 'Open', '2026-07-08 11:50:03'),
(20, 22, 'Open', '2026-07-08 11:50:03'),
(21, 28, 'Open', '2026-07-08 11:50:03'),
(22, 21, 'Open', '2026-07-08 11:50:03'),
(23, 42, 'Open', '2026-07-09 06:43:12'),
(24, 44, 'Open', '2026-07-09 09:45:30'),
(25, 45, 'Open', '2026-07-09 10:00:10'),
(26, 48, 'Open', '2026-07-09 10:34:04'),
(27, 70, 'Open', '2026-07-20 11:17:36'),
(30, 86, 'Settled', '2026-07-23 04:42:29');

-- --------------------------------------------------------

--
-- Table structure for table `folio_transactions`
--

CREATE TABLE `folio_transactions` (
  `transaction_id` int(11) NOT NULL,
  `res_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `folio_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `trans_type` enum('charge','payment','advance','rebate','void') DEFAULT 'charge',
  `reference_no` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('active','voided') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `folio_transactions`
--

INSERT INTO `folio_transactions` (`transaction_id`, `res_id`, `amount`, `created_at`, `folio_id`, `description`, `trans_type`, `reference_no`, `created_by`, `status`) VALUES
(1, NULL, 5000.00, '2026-07-08 13:45:56', 3, 'Laundry', 'charge', NULL, NULL, 'active'),
(2, NULL, 2000.00, '2026-07-08 13:53:16', 18, 'Pool Bar', 'charge', NULL, NULL, 'active'),
(3, NULL, 20000.00, '2026-07-08 13:54:36', 3, 'Room Charge', 'charge', NULL, NULL, 'active'),
(4, NULL, 5000.00, '2026-07-08 13:54:54', 18, 'Room Charge', 'charge', NULL, NULL, 'active'),
(5, NULL, 500.00, '2026-07-08 13:59:13', 3, 'Room Service', 'charge', NULL, NULL, 'active'),
(9, NULL, 10000.00, '2026-07-09 06:43:24', 23, 'Excursions', 'charge', NULL, NULL, 'active'),
(10, NULL, 4000.00, '2026-07-09 06:53:43', 23, 'Room Service', 'charge', NULL, NULL, 'active'),
(11, NULL, 4000.00, '2026-07-09 06:58:24', 23, 'Room Service', 'charge', NULL, NULL, 'active'),
(12, NULL, 100.00, '2026-07-09 06:58:43', 23, 'Payment (Cash/Card)', 'charge', NULL, NULL, 'active'),
(13, NULL, 20000.00, '2026-07-09 07:27:56', 23, 'Spa', 'charge', NULL, NULL, 'active'),
(14, NULL, 5000.00, '2026-07-09 09:47:02', 24, 'Room Charge', 'charge', NULL, NULL, 'active'),
(15, NULL, 2000.00, '2026-07-09 09:47:28', 24, 'Payment (Cash/Card)', 'payment', NULL, NULL, 'active'),
(16, NULL, 8000.00, '2026-07-09 10:44:57', 25, 'Room Charge', 'charge', NULL, NULL, 'active'),
(17, 45, 5000.00, '2026-07-09 10:58:20', NULL, 'room charge', 'charge', '', 1, 'active'),
(18, 48, 5000.00, '2026-07-09 11:05:47', NULL, 'room charge', 'payment', '', 1, 'active'),
(19, 49, 5000.00, '2026-07-09 11:22:38', NULL, 'room charge', 'charge', '', 1, 'active'),
(20, 45, 400.00, '2026-07-09 11:23:13', NULL, '', 'charge', '', 1, 'active'),
(21, 52, 5000.00, '2026-07-12 05:14:28', NULL, '', 'advance', '', 1, 'active'),
(22, 61, 1500.00, '2026-07-18 05:08:48', NULL, '', 'advance', '', 1, 'active'),
(25, 62, 28000.00, '2026-07-18 09:44:52', NULL, 'Room Charge (1 nights @ LKR 28,000.00)', 'charge', 'AUTO', 1, 'active'),
(26, 60, 10000.00, '2026-07-18 10:09:57', NULL, '', 'advance', '', 6, 'active'),
(27, 60, 7000.00, '2026-07-18 10:10:27', NULL, '', 'payment', '', 6, 'active'),
(28, 42, 28000.00, '2026-07-18 10:23:04', NULL, 'Room Charge (1 nights @ LKR 28000.00)', 'charge', 'AUTO_FIX', 1, 'active'),
(29, 44, 28000.00, '2026-07-18 10:23:04', NULL, 'Room Charge (1 nights @ LKR 28000.00)', 'charge', 'AUTO_FIX', 1, 'active'),
(30, 46, 8500.00, '2026-07-18 10:23:04', NULL, 'Room Charge (1 nights @ LKR 8500.00)', 'charge', 'AUTO_FIX', 1, 'active'),
(31, 47, 8500.00, '2026-07-18 10:23:04', NULL, 'Room Charge (1 nights @ LKR 8500.00)', 'charge', 'AUTO_FIX', 1, 'active'),
(32, 51, 26000.00, '2026-07-18 10:23:04', NULL, 'Room Charge (2 nights @ LKR 13000.00)', 'charge', 'AUTO_FIX', 1, 'active'),
(33, 60, 14000.00, '2026-07-18 10:23:04', NULL, 'Room Charge (1 nights @ LKR 14000.00)', 'charge', 'AUTO_FIX', 1, 'active'),
(34, 61, 28000.00, '2026-07-18 10:23:04', NULL, 'Room Charge (1 nights @ LKR 28000.00)', 'charge', 'AUTO_FIX', 1, 'active'),
(35, 63, 28000.00, '2026-07-18 10:23:04', NULL, 'Room Charge (1 nights @ LKR 28000.00)', 'charge', 'AUTO_FIX', 1, 'active'),
(44, 66, 7000.00, '2026-07-18 10:51:57', NULL, '', 'payment', '', 6, 'active'),
(45, 63, 8000.00, '2026-07-18 10:59:22', NULL, '', 'payment', '', 6, 'active'),
(46, 63, 20000.00, '2026-07-18 10:59:53', NULL, '', 'rebate', '', 6, 'active'),
(47, 68, 14000.00, '2026-07-18 11:11:52', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 6, 'voided'),
(48, 68, 7000.00, '2026-07-18 11:12:35', NULL, '', 'payment', '', 6, 'voided'),
(49, 68, 5000.00, '2026-07-18 11:13:20', NULL, '', 'advance', '', 6, 'voided'),
(50, 68, 5000.00, '2026-07-18 11:13:48', NULL, 'pool', 'charge', '', 6, 'voided'),
(51, 68, 1000.00, '2026-07-18 11:14:01', NULL, '', 'rebate', '', 6, 'voided'),
(52, 69, 8500.00, '2026-07-18 11:22:07', NULL, 'Room Charge (1 nights @ LKR 8,500.00)', 'charge', 'AUTO', 6, 'active'),
(53, 67, 28000.00, '2026-07-18 11:25:28', NULL, 'Room Charge (1 nights @ LKR 28,000.00)', 'charge', 'AUTO', 6, 'active'),
(54, 70, 8500.00, '2026-07-20 09:11:42', NULL, 'Room Charge (1 nights @ LKR 8,500.00)', 'charge', 'AUTO', 1, 'active'),
(55, 70, 8500.00, '2026-07-20 09:12:20', NULL, '', 'payment', '', 1, 'voided'),
(56, 70, 500.00, '2026-07-20 10:19:03', NULL, 'discount', 'rebate', '', 1, 'active'),
(57, 70, 5000.00, '2026-07-20 10:44:37', NULL, '', 'advance', '', 1, 'active'),
(58, 70, 5000.00, '2026-07-20 10:45:25', NULL, 'Deposit Refund', 'payment', '', 1, 'voided'),
(59, 70, 500.00, '2026-07-20 11:06:38', NULL, 'Service: Room Service', 'charge', 'SERVICE', 1, 'active'),
(60, 70, 1000.00, '2026-07-20 11:07:53', NULL, 'pool', 'charge', '', 1, 'active'),
(61, 70, 3000.00, '2026-07-20 11:15:30', NULL, 'Debit Note: extra bed', 'charge', 'pubudu', 1, 'active'),
(62, 70, 3000.00, '2026-07-20 11:17:36', 27, 'Debit Note: extra bed', 'charge', 'pubudu', 1, 'active'),
(63, 70, 11500.00, '2026-07-20 11:18:52', NULL, '', 'payment', '', 1, 'active'),
(64, 71, 14000.00, '2026-07-20 11:29:49', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(65, 71, 14000.00, '2026-07-20 13:48:21', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO_RESET', 1, 'active'),
(66, 71, 14000.00, '2026-07-20 14:15:31', NULL, 'Card Payment (****8547, 10/26) - pubudu', 'payment', 'CARD', 1, 'active'),
(67, 71, 31000.00, '2026-07-20 15:05:00', NULL, 'Currency Exchange: 100 USD @ 310 = LKR 31000', 'payment', 'EXCHANGE', 1, 'active'),
(68, 71, 31000.00, '2026-07-20 15:05:53', NULL, 'Currency Exchange: 100 USD @ 310 = LKR 31000', 'payment', 'EXCHANGE', 1, 'active'),
(69, 71, 62000.00, '2026-07-20 15:51:14', NULL, '', 'payment', '', 1, 'active'),
(70, 71, 124000.00, '2026-07-20 15:51:36', NULL, '', 'charge', '', 1, 'active'),
(71, 68, 14000.00, '2026-07-20 15:52:46', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO_RESET', 1, 'active'),
(72, 68, 14000.00, '2026-07-20 15:52:57', NULL, '', 'payment', '', 1, 'active'),
(73, 72, 14000.00, '2026-07-21 07:12:33', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(74, 72, 14000.00, '2026-07-21 07:14:11', NULL, '', 'payment', '', 1, 'voided'),
(75, 72, 1000.00, '2026-07-21 07:14:46', NULL, '', 'rebate', '', 1, 'voided'),
(76, 72, 1000.00, '2026-07-21 07:15:58', NULL, 'pool', 'charge', '', 1, 'voided'),
(77, 72, 2000.00, '2026-07-21 07:16:13', NULL, '', 'payment', '', 1, 'voided'),
(78, 72, 42000.00, '2026-07-21 07:17:37', NULL, 'Currency Exchange: 500 AED @ 84 = LKR 42000', 'payment', 'EXCHANGE', 1, 'voided'),
(79, 72, 5000.00, '2026-07-21 07:18:35', NULL, 'discount', 'rebate', '', 1, 'voided'),
(80, 72, 37000.00, '2026-07-21 07:20:54', NULL, '', 'payment', '', 1, 'voided'),
(81, 72, 74000.00, '2026-07-21 07:21:15', NULL, '', 'charge', '', 1, 'voided'),
(82, 72, 14000.00, '2026-07-21 08:49:08', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(83, 72, 14000.00, '2026-07-21 08:49:22', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(84, 72, 14000.00, '2026-07-21 08:51:39', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(85, 72, 14000.00, '2026-07-21 08:55:03', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(86, 72, 14000.00, '2026-07-21 08:55:17', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(87, 72, 14000.00, '2026-07-21 08:58:21', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(88, 72, 14000.00, '2026-07-21 09:04:49', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(89, 72, 14000.00, '2026-07-21 09:09:23', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(90, 72, 14000.00, '2026-07-21 09:10:56', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(91, 72, 14000.00, '2026-07-21 09:12:44', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(92, 72, 14000.00, '2026-07-21 09:16:26', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(93, 72, 14000.00, '2026-07-21 09:18:27', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(94, 72, 14000.00, '2026-07-21 09:20:06', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(95, 72, 14000.00, '2026-07-21 09:21:37', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(96, 72, 14000.00, '2026-07-21 09:24:42', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(97, 72, 14000.00, '2026-07-21 09:24:55', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(98, 72, 14000.00, '2026-07-21 09:28:10', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(99, 72, 14000.00, '2026-07-21 09:33:38', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(100, 72, 14000.00, '2026-07-21 09:41:22', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO_RESET', 1, 'active'),
(101, 72, 14000.00, '2026-07-21 09:41:57', NULL, '', 'payment', '', 1, 'active'),
(102, 73, 14000.00, '2026-07-21 09:49:16', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'active'),
(103, 73, 14000.00, '2026-07-21 09:49:32', NULL, '', 'payment', '', 1, 'active'),
(104, 74, 8500.00, '2026-07-21 09:57:07', NULL, 'Room Charge (1 nights @ LKR 8,500.00)', 'charge', 'AUTO', 1, 'active'),
(105, 74, 8500.00, '2026-07-21 09:57:28', NULL, '', 'payment', '', 1, 'active'),
(106, 77, 8500.00, '2026-07-21 16:35:42', NULL, 'Room Charge (1 nights @ LKR 8,500.00)', 'charge', 'AUTO', 1, 'active'),
(107, 82, 8000.00, '2026-07-22 16:03:42', NULL, 'Room Charge (1 nights @ LKR 8,000.00)', 'charge', 'AUTO', 1, 'active'),
(108, 81, 14000.00, '2026-07-22 16:05:33', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO', 1, 'voided'),
(109, 78, 28000.00, '2026-07-22 16:05:42', NULL, 'Room Charge (1 nights @ LKR 28,000.00)', 'charge', 'AUTO', 1, 'active'),
(114, 81, 34000.00, '2026-07-22 16:34:57', NULL, 'Currency Exchange: 100 EUR @ 340 = LKR 34000', 'payment', 'EXCHANGE', 1, 'voided'),
(115, 81, 14000.00, '2026-07-23 03:56:48', NULL, 'Room Charge (1 nights @ LKR 14,000.00)', 'charge', 'AUTO_RESET', 1, 'active'),
(116, 81, 14000.00, '2026-07-23 03:57:13', NULL, '', 'payment', '', 1, 'active'),
(117, 82, 8000.00, '2026-07-23 03:57:40', NULL, '', 'payment', '', 1, 'active'),
(118, 86, 8000.00, '2026-07-23 04:42:29', 30, 'Room Charge - 1 nights @ LKR 8,000', 'charge', NULL, 1, 'active'),
(119, 86, 8000.00, '2026-07-23 04:42:29', 30, 'Online Payment - Card', 'payment', NULL, 1, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `guest_services`
--

CREATE TABLE `guest_services` (
  `service_id` int(11) NOT NULL,
  `res_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `service_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','assigned','in_progress','completed','cancelled') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `service_charge` decimal(10,2) DEFAULT 0.00,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guest_services`
--

INSERT INTO `guest_services` (`service_id`, `res_id`, `room_id`, `service_type`, `description`, `priority`, `status`, `assigned_to`, `service_charge`, `completed_at`, `created_at`, `created_by`) VALUES
(1, 70, 8, 'Room Service', 'xmvnmxnmxnvxmnvxmnxm', 'high', 'pending', NULL, 500.00, NULL, '2026-07-20 16:36:38', 1);

-- --------------------------------------------------------

--
-- Table structure for table `hotel_settings`
--

CREATE TABLE `hotel_settings` (
  `id` int(11) NOT NULL,
  `hotel_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `tax_percentage` decimal(5,2) DEFAULT 0.00,
  `service_charge_percentage` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotel_settings`
--

INSERT INTO `hotel_settings` (`id`, `hotel_name`, `address`, `phone`, `tax_percentage`, `service_charge_percentage`) VALUES
(1, 'Araliya Beach Resort', 'Galle Road, Hikkaduwa, Sri Lanka', '+94 91 234 5678', 10.00, 5.00);

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping_staff`
--

CREATE TABLE `housekeeping_staff` (
  `staff_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `housekeeping_staff`
--

INSERT INTO `housekeeping_staff` (`staff_id`, `full_name`, `contact`, `is_active`) VALUES
(1, 'Saman Kumara', '071-1234567', 1),
(2, 'Nimal Perera', '072-7654321', 1),
(3, 'Saman Kumara', '071-1234567', 1),
(4, 'Nimal Perera', '072-7654321', 1),
(5, 'Kamal Silva', '077-9876543', 1),
(6, 'pubudu', '0716333475', 1);

-- --------------------------------------------------------

--
-- Table structure for table `laundry_items`
--

CREATE TABLE `laundry_items` (
  `item_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laundry_items`
--

INSERT INTO `laundry_items` (`item_id`, `item_name`, `price`) VALUES
(1, 'Shirt (Wash & Iron)', 5.00),
(2, 'Trouser (Wash & Iron)', 6.50),
(3, 'Saree (Dry Clean)', 12.00),
(4, 'Towel (Large)', 3.50),
(5, 'Shirt (Wash & Iron)', 5.00),
(6, 'Trouser (Wash & Iron)', 6.50),
(7, 'Saree (Dry Clean)', 12.00),
(8, 'Blouse (Wash)', 4.00),
(9, 'Towel (Large)', 3.50);

-- --------------------------------------------------------

--
-- Table structure for table `laundry_orders`
--

CREATE TABLE `laundry_orders` (
  `order_id` int(11) NOT NULL,
  `order_type` enum('Walk-In','Room') NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `res_id` int(11) DEFAULT NULL,
  `walk_in_name` varchar(100) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_status` enum('Pending','Processing','Completed') DEFAULT 'Pending',
  `posted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laundry_orders`
--

INSERT INTO `laundry_orders` (`order_id`, `order_type`, `room_id`, `res_id`, `walk_in_name`, `total_amount`, `order_status`, `posted_at`) VALUES
(1, 'Walk-In', NULL, NULL, 'PUBUDU', 3.50, 'Pending', '2026-07-18 05:15:28'),
(2, 'Walk-In', NULL, NULL, 'PUBUDU', 12.00, 'Pending', '2026-07-20 15:55:31');

-- --------------------------------------------------------

--
-- Table structure for table `laundry_order_items`
--

CREATE TABLE `laundry_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laundry_order_items`
--

INSERT INTO `laundry_order_items` (`id`, `order_id`, `item_id`, `quantity`, `price`) VALUES
(1, 1, 4, 1, 3.50),
(2, 2, 3, 1, 12.00);

-- --------------------------------------------------------

--
-- Table structure for table `nationalities`
--

CREATE TABLE `nationalities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nationalities`
--

INSERT INTO `nationalities` (`id`, `name`) VALUES
(1, 'Sri Lankan'),
(2, 'Indian'),
(3, 'British'),
(4, 'American'),
(5, 'Australian'),
(6, 'German'),
(7, 'French'),
(8, 'Chinese'),
(9, 'Japanese'),
(10, 'Russian'),
(11, 'Canadian'),
(12, 'Maldivian'),
(13, 'Italian'),
(14, 'Spanish'),
(15, 'Dutch');

-- --------------------------------------------------------

--
-- Table structure for table `online_bookings`
--

CREATE TABLE `online_bookings` (
  `booking_id` int(11) NOT NULL,
  `booking_reference` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `room_id` int(11) NOT NULL,
  `guest_name` varchar(100) NOT NULL,
  `guest_email` varchar(100) NOT NULL,
  `guest_phone` varchar(20) NOT NULL,
  `guest_nationality` varchar(50) DEFAULT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `num_of_nights` int(11) DEFAULT 1,
  `adults` int(11) DEFAULT 1,
  `children` int(11) DEFAULT 0,
  `room_rate` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'LKR',
  `payment_status` enum('pending','paid','failed','refunded','partial') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `booking_status` enum('pending','confirmed','cancelled','checked_in','checked_out','no_show') DEFAULT 'pending',
  `special_requests` text DEFAULT NULL,
  `booking_source` varchar(50) DEFAULT 'online',
  `travel_agent_id` int(11) DEFAULT NULL,
  `agent_commission` decimal(10,2) DEFAULT 0.00,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `synced_to_pms` tinyint(1) DEFAULT 0,
  `pms_reservation_id` int(11) DEFAULT NULL,
  `sync_attempts` int(11) DEFAULT 0,
  `sync_error` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `online_bookings`
--

INSERT INTO `online_bookings` (`booking_id`, `booking_reference`, `user_id`, `room_id`, `guest_name`, `guest_email`, `guest_phone`, `guest_nationality`, `check_in`, `check_out`, `num_of_nights`, `adults`, `children`, `room_rate`, `total_amount`, `currency`, `payment_status`, `payment_method`, `transaction_id`, `booking_status`, `special_requests`, `booking_source`, `travel_agent_id`, `agent_commission`, `ip_address`, `user_agent`, `created_at`, `updated_at`, `synced_to_pms`, `pms_reservation_id`, `sync_attempts`, `sync_error`) VALUES
(1, 'BK26070001', 1, 1, 'Test Guest', 'guest@example.com', '0712345678', NULL, '2026-07-27', '2026-07-29', 2, 2, 0, 15000.00, 30000.00, 'LKR', 'paid', NULL, NULL, 'confirmed', NULL, 'online', NULL, 0.00, NULL, NULL, '2026-07-22 11:04:13', NULL, 0, NULL, 0, NULL),
(2, 'BK2607220856', 3, 1, 'pubudu sathsara', 'harshanapubudusathsara@gmail.com', '0716333475', 'Sri Lankan', '2026-07-22', '2026-07-23', 1, 2, 0, 8000.00, 8000.00, 'LKR', 'pending', NULL, NULL, 'cancelled', '', 'online', NULL, 0.00, NULL, NULL, '2026-07-22 14:27:06', '2026-07-22 14:31:21', 0, NULL, 0, NULL),
(3, 'BK2607225952', 3, 1, 'pubudu sathsara', 'harshanapubudusathsara@gmail.com', '0716333475', 'Sri Lankan', '2026-07-22', '2026-07-23', 1, 2, 0, 8000.00, 8000.00, 'LKR', 'paid', 'card', NULL, 'confirmed', '', 'online', NULL, 0.00, NULL, NULL, '2026-07-22 14:28:46', '2026-07-22 14:29:28', 0, NULL, 0, NULL),
(4, 'BK2607227847', 5, 1, 'pubudu sathsara', 'harshana@gmail.com', '0716333475', 'Sri Lankan', '2026-07-22', '2026-07-23', 1, 2, 0, 8000.00, 8000.00, 'LKR', 'paid', 'card', NULL, 'cancelled', '', 'online', NULL, 0.00, NULL, NULL, '2026-07-22 14:46:24', '2026-07-22 14:47:05', 0, NULL, 0, NULL),
(5, 'BK2607229058', 3, 4, 'pubudu sathsara', 'harshanapubudusathsara@gmail.com', '0740526690', 'Sri Lankan', '2026-07-22', '2026-07-23', 1, 2, 0, 8000.00, 8000.00, 'LKR', 'pending', NULL, NULL, 'pending', '', 'online', NULL, 0.00, NULL, NULL, '2026-07-22 14:56:41', NULL, 0, NULL, 0, NULL),
(6, 'BK2607220699', 3, 4, 'pubudu sathsara', 'harshanapubudusathsara@gmail.com', '0740526690', 'Sri Lankan', '2026-07-22', '2026-07-23', 1, 2, 0, 8000.00, 8000.00, 'LKR', 'paid', 'card', NULL, 'confirmed', '', 'online', NULL, 0.00, NULL, NULL, '2026-07-22 15:56:54', '2026-07-22 15:57:49', 0, NULL, 0, NULL),
(7, 'BK2607226006', 3, 3, 'pubudu sathsara', 'harshanapubudusathsara@gmail.com', '0740526690', 'Sri Lankan', '2026-07-22', '2026-07-23', 1, 2, 0, 8000.00, 8000.00, 'LKR', 'paid', 'card', NULL, 'confirmed', '', 'online', NULL, 0.00, NULL, NULL, '2026-07-22 16:00:30', '2026-07-22 16:00:49', 0, NULL, 0, NULL),
(8, 'BK2607220612', 3, 3, 'pubudu sathsara', 'harshanapubudusathsara@gmail.com', '0740526690', 'Sri Lankan', '2026-07-22', '2026-07-23', 1, 2, 0, 8000.00, 8000.00, 'LKR', 'paid', 'card', NULL, 'confirmed', '', 'online', NULL, 0.00, NULL, NULL, '2026-07-22 16:01:18', '2026-07-22 16:01:37', 0, NULL, 0, NULL),
(9, 'BK2607224777', 3, 5, 'pubudu sathsara', 'harshanapubudusathsara@gmail.com', '0740526690', 'Sri Lankan', '2026-07-22', '2026-07-23', 1, 2, 0, 8000.00, 8000.00, 'LKR', 'paid', 'card', NULL, 'confirmed', '', 'online', NULL, 0.00, NULL, NULL, '2026-07-22 16:03:01', '2026-07-22 16:03:18', 1, 82, 0, NULL),
(10, 'BK2607228658', 3, 126, 'pubudu sathsara', 'harshanapubudusathsara@gmail.com', '0740526690', 'Sri Lankan', '2026-07-22', '2026-07-23', 1, 2, 0, 28000.00, 28000.00, 'LKR', 'paid', 'card', NULL, 'confirmed', '', 'online', NULL, 0.00, NULL, NULL, '2026-07-22 16:10:44', '2026-07-22 16:11:01', 1, 83, 0, NULL),
(11, 'BK2607220503', 3, 3, 'pubudu sathsara', 'harshanapubudusathsara@gmail.com', '0740526690', 'Sri Lankan', '2026-07-22', '2026-07-23', 1, 2, 0, 8000.00, 8000.00, 'LKR', 'pending', NULL, NULL, 'pending', '', 'online', NULL, 0.00, NULL, NULL, '2026-07-22 16:22:45', NULL, 0, NULL, 0, NULL),
(12, 'BK2607225439', 3, 107, 'pubudu sathsara', 'harshanapubudusathsara@gmail.com', '0740526690', 'Sri Lankan', '2026-07-22', '2026-07-23', 1, 2, 0, 8000.00, 8000.00, 'LKR', 'paid', 'card', NULL, 'confirmed', '', 'online', NULL, 0.00, NULL, NULL, '2026-07-22 16:24:46', '2026-07-22 16:26:16', 1, 84, 0, NULL),
(13, 'BK2607233476', 3, 2, 'pubudu sathsara', 'harshanapubudusathsara@gmail.com', '0740526690', 'Sri Lankan', '2026-07-23', '2026-07-24', 1, 2, 0, 8000.00, 8000.00, 'LKR', 'paid', 'card', NULL, 'confirmed', '', 'online', NULL, 0.00, NULL, NULL, '2026-07-23 04:41:58', '2026-07-23 04:42:29', 1, 86, 0, NULL);

--
-- Triggers `online_bookings`
--
DELIMITER $$
CREATE TRIGGER `after_online_booking_cancel` AFTER UPDATE ON `online_bookings` FOR EACH ROW BEGIN
    IF NEW.booking_status = 'cancelled' AND OLD.booking_status != 'cancelled' THEN
        -- Mark room as available for cancelled dates
        UPDATE room_availability 
        SET is_available = 1 
        WHERE room_id = NEW.room_id 
        AND date BETWEEN NEW.check_in AND DATE_SUB(NEW.check_out, INTERVAL 1 DAY);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_online_booking_insert` AFTER INSERT ON `online_bookings` FOR EACH ROW BEGIN
    -- Mark room as unavailable for booked dates
    UPDATE room_availability 
    SET is_available = 0 
    WHERE room_id = NEW.room_id 
    AND date BETWEEN NEW.check_in AND DATE_SUB(NEW.check_out, INTERVAL 1 DAY);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `online_users`
--

CREATE TABLE `online_users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `user_type` enum('guest','travel_agent','admin') DEFAULT 'guest',
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `online_users`
--

INSERT INTO `online_users` (`user_id`, `email`, `password`, `full_name`, `phone`, `country`, `user_type`, `is_verified`, `verification_token`, `reset_token`, `reset_token_expiry`, `created_at`, `last_login`) VALUES
(1, 'guest@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test Guest', '0712345678', 'Sri Lanka', 'guest', 1, NULL, NULL, NULL, '2026-07-22 11:04:13', NULL),
(2, 'agent@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Travels', '0712345679', 'Sri Lanka', 'travel_agent', 1, NULL, NULL, NULL, '2026-07-22 11:04:13', NULL),
(3, 'harshanapubudusathsara@gmail.com', '$2y$10$fy3N2uwCZ8l0hq0bB3Vq7.YHCHVyQmecvT082EMuxSPYjE9er63/u', 'pubudu sathsara', '0740526690', NULL, 'guest', 0, '0b38e2b40d459c4e8cb5fbc584d765285c7fa7fe5dcd04ba86bc5f2249e5b943', '7c5276cf6a8d5ab046dfdd912dce7512cf51f481566da0b9659d3415758e371c', '2026-07-22 18:14:27', '2026-07-22 13:27:25', '2026-07-23 04:40:07'),
(4, 'piyumi@gmail.com', '$2y$10$5g7agzzmZOpDvopugG4Ebe2hYwNmOanXx98IKo70DFdpFv.CNRLum', 'piyumi', '0716333475', NULL, 'travel_agent', 1, NULL, NULL, NULL, '2026-07-22 14:05:21', NULL),
(5, 'harshana@gmail.com', '$2y$10$O9qnXDRA/8vadd4/evgr3OhIucq63S7FT4g7.0TQ7MYbiuCqWTdP6', 'pubudu sathsara', '0740526690', NULL, 'guest', 1, NULL, NULL, NULL, '2026-07-22 14:44:56', '2026-07-22 14:45:26'),
(6, 'shehansumith1@gmail.com', '$2y$10$EpIRNk.qoZrxe5Tvfjwo4e8gON3M92ClEHrmUk0l.4VwhNCdZ.Tae', 'shehan', '0716333475', NULL, 'guest', 0, '65ce5ff92448a33faf383fa4e51b09e2c229ab0f700ebbbe0b5129c2e3f5d123', NULL, '2026-07-24 07:10:24', '2026-07-23 05:10:24', NULL),
(7, 'pubukavimusic@gmail.com', '$2y$10$vcktL3oXTZKpxQf/BhMUe.ZuDeoISZCSu0QSYIxQfnvYNPnK664Z2', 'pubudu sathsara', '0740526690', NULL, 'guest', 0, 'd458782f7d0a5a5bf0e54c5040ece6a2ba4d91cc9f911b97d2e9f6c5bdcbc222', NULL, '2026-07-24 07:14:01', '2026-07-23 05:14:01', NULL),
(8, 'harshanapubudu@gmail.com', '$2y$10$F8yD3Aa.ZeFzYX.6EqgSgu6/WFbj.SIiB/aQFGIuLmtsqoQsgCmfa', 'pubudu sathsara', '', NULL, 'guest', 1, NULL, NULL, NULL, '2026-07-23 05:17:29', '2026-07-23 05:18:13');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `transaction_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'LKR',
  `payment_method` varchar(50) NOT NULL,
  `card_last4` varchar(4) DEFAULT NULL,
  `card_brand` varchar(20) DEFAULT NULL,
  `card_expiry` varchar(7) DEFAULT NULL,
  `stripe_payment_intent_id` varchar(100) DEFAULT NULL,
  `stripe_client_secret` varchar(255) DEFAULT NULL,
  `paypal_order_id` varchar(100) DEFAULT NULL,
  `paypal_payer_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','succeeded','failed','refunded','disputed') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_transactions`
--

INSERT INTO `payment_transactions` (`transaction_id`, `booking_id`, `amount`, `currency`, `payment_method`, `card_last4`, `card_brand`, `card_expiry`, `stripe_payment_intent_id`, `stripe_client_secret`, `paypal_order_id`, `paypal_payer_id`, `status`, `error_message`, `created_at`, `completed_at`) VALUES
(1, 3, 8000.00, 'LKR', 'card', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'succeeded', NULL, '2026-07-22 14:29:28', '2026-07-22 14:29:28'),
(2, 4, 8000.00, 'LKR', 'card', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'succeeded', NULL, '2026-07-22 14:46:52', '2026-07-22 14:46:52'),
(3, 6, 8000.00, 'LKR', 'card', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'succeeded', NULL, '2026-07-22 15:57:49', '2026-07-22 15:57:49'),
(4, 7, 8000.00, 'LKR', 'card', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'succeeded', NULL, '2026-07-22 16:00:49', '2026-07-22 16:00:49'),
(5, 8, 8000.00, 'LKR', 'card', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'succeeded', NULL, '2026-07-22 16:01:37', '2026-07-22 16:01:37'),
(6, 9, 8000.00, 'LKR', 'card', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'succeeded', NULL, '2026-07-22 16:03:18', '2026-07-22 16:03:18'),
(7, 10, 28000.00, 'LKR', 'card', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'succeeded', NULL, '2026-07-22 16:11:01', '2026-07-22 16:11:01'),
(8, 12, 8000.00, 'LKR', 'card', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'succeeded', NULL, '2026-07-22 16:26:16', '2026-07-22 16:26:16'),
(9, 13, 8000.00, 'LKR', 'card', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'succeeded', NULL, '2026-07-23 04:42:29', '2026-07-23 04:42:29');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `pay_id` int(11) NOT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `month` varchar(20) DEFAULT NULL,
  `gross_salary` decimal(10,2) DEFAULT NULL,
  `deductions` decimal(10,2) DEFAULT NULL,
  `net_salary` decimal(10,2) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promo_codes`
--

CREATE TABLE `promo_codes` (
  `code_id` int(11) NOT NULL,
  `code_name` varchar(20) NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL,
  `expiry_date` date NOT NULL,
  `status` varchar(10) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promo_codes`
--

INSERT INTO `promo_codes` (`code_id`, `code_name`, `discount_percentage`, `expiry_date`, `status`) VALUES
(1, 'WELCOME10', 10.00, '2026-12-31', 'Active'),
(2, 'NEWYEAR', 5.00, '2026-06-26', 'Active'),
(3, 'VESAK', 5.00, '2026-06-25', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `rebates`
--

CREATE TABLE `rebates` (
  `rebate_id` int(11) NOT NULL,
  `res_id` int(11) NOT NULL,
  `folio_id` int(11) NOT NULL,
  `rebate_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `res_id` int(11) NOT NULL,
  `res_no` varchar(50) DEFAULT NULL,
  `guest_name` varchar(100) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `allotment_id` int(11) DEFAULT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `num_of_nights` int(11) DEFAULT 1,
  `status` enum('Pending','Checked-In','Checked-Out','Cancelled') DEFAULT 'Pending',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(100) DEFAULT NULL,
  `passport_no` varchar(100) DEFAULT NULL,
  `booking_source` varchar(100) DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `rate_code` varchar(50) DEFAULT NULL,
  `package_name` varchar(50) DEFAULT NULL,
  `market_segment` varchar(50) DEFAULT NULL,
  `business_segment` varchar(50) DEFAULT NULL,
  `sales_person` varchar(50) DEFAULT NULL,
  `voucher_no` varchar(50) DEFAULT NULL,
  `tour_no` varchar(50) DEFAULT NULL,
  `guest_payment_mode` varchar(30) DEFAULT 'Guest',
  `visit_purpose` varchar(50) DEFAULT NULL,
  `bill_payment_lkr_sscl` tinyint(1) DEFAULT 0,
  `company_credit_enable` tinyint(1) DEFAULT 0,
  `gender` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `adults` int(11) DEFAULT 1,
  `children` int(11) DEFAULT 0,
  `bed_type` varchar(20) DEFAULT NULL,
  `room_category` varchar(50) DEFAULT NULL,
  `meal_plan` varchar(50) DEFAULT NULL,
  `rate_basis` varchar(20) DEFAULT NULL,
  `arrive_for` varchar(50) DEFAULT NULL,
  `leave_after` varchar(50) DEFAULT NULL,
  `expected_arrival_time` time DEFAULT NULL,
  `expected_departure_time` time DEFAULT NULL,
  `room_rate` decimal(10,2) DEFAULT NULL,
  `total_charge` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'LKR',
  `payment_method` varchar(30) DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `transport_req` tinyint(1) DEFAULT 0,
  `vehicle_no` varchar(20) DEFAULT NULL,
  `group_no` varchar(50) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`res_id`, `res_no`, `guest_name`, `room_id`, `allotment_id`, `check_in`, `check_out`, `num_of_nights`, `status`, `is_deleted`, `deleted_at`, `deleted_by`, `passport_no`, `booking_source`, `company_name`, `rate_code`, `package_name`, `market_segment`, `business_segment`, `sales_person`, `voucher_no`, `tour_no`, `guest_payment_mode`, `visit_purpose`, `bill_payment_lkr_sscl`, `company_credit_enable`, `gender`, `dob`, `nationality`, `email`, `mobile`, `adults`, `children`, `bed_type`, `room_category`, `meal_plan`, `rate_basis`, `arrive_for`, `leave_after`, `expected_arrival_time`, `expected_departure_time`, `room_rate`, `total_charge`, `currency`, `payment_method`, `special_requests`, `transport_req`, `vehicle_no`, `group_no`, `contact_no`, `created_by`) VALUES
(17, NULL, 'pubudu sathsara', 8, NULL, '2026-06-25', '2026-06-26', 1, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Maldivian', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(18, NULL, 'pubudu sathsara', 6, NULL, '2026-06-25', '2026-06-26', 1, 'Pending', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Maldivian', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(19, NULL, 'kasun', 7, NULL, '2026-06-30', '2026-07-01', 1, 'Checked-Out', 1, '2026-07-08 23:52:16', 'admin', '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'pubudu', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Chinese', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, 'sea view', 0, NULL, NULL, NULL, NULL),
(20, NULL, 'shehan', 9, NULL, '2026-06-30', '2026-07-01', 1, '', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Spanish', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(21, NULL, 'shehan', 11, NULL, '2026-06-30', '2026-07-01', 1, 'Checked-Out', 1, '2026-07-08 23:52:16', 'admin', '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Canadian', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(22, NULL, 'piyumi', 9, NULL, '2026-06-30', '2026-07-01', 1, 'Checked-Out', 1, '2026-07-08 23:52:16', 'admin', '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'German', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(23, NULL, 'pubudu sathsara', 6, NULL, '2026-07-03', '2026-07-04', 1, 'Cancelled', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Chinese', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(24, NULL, 'dileepa', 4, NULL, '2026-07-07', '2026-07-08', 1, 'Checked-Out', 1, '2026-07-08 23:52:16', 'admin', '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Indian', 'dileepa@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(25, NULL, 'pubudu sathsara', 6, NULL, '2026-07-07', '2026-07-08', 1, '', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Canadian', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(26, NULL, 'pubudu sathsara', 5, NULL, '2026-07-07', '2026-07-08', 1, '', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Italian', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(27, NULL, 'pubudu sathsara', 6, NULL, '2026-07-07', '2026-07-08', 1, 'Checked-Out', 1, '2026-07-08 23:51:15', 'admin', '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Australian', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(28, NULL, 'pubudu sathsara', 10, NULL, '2026-07-07', '2026-07-08', 1, '', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Italian', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(29, NULL, 'pubudu sathsara', 1, NULL, '2026-07-07', '2026-07-08', 1, '', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Chinese', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(30, NULL, 'pubudu sathsara', 7, NULL, '2026-07-07', '2026-07-08', 1, '', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Japanese', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(31, NULL, 'pubudu sathsara', 7, NULL, '2026-07-07', '2026-07-08', 1, '', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Canadian', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(32, NULL, 'pubudu sathsara', 7, NULL, '2026-07-07', '2026-07-08', 1, '', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'French', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(33, NULL, 'pubudu sathsara', 7, NULL, '2026-07-07', '2026-07-08', 1, 'Checked-Out', 1, '2026-07-08 22:49:40', 'admin', '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Japanese', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(34, NULL, 'pubudu sathsara', 2, NULL, '2026-07-08', '2026-07-09', 1, 'Checked-Out', 1, '2026-07-09 11:54:26', 'admin', '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Canadian', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(35, NULL, 'pubudu sathsara', 6, NULL, '2026-07-08', '2026-07-09', 1, 'Checked-Out', 1, '2026-07-09 11:54:26', 'admin', '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'French', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(36, NULL, 'pubudu sathsara', 8, NULL, '2026-07-08', '2026-07-09', 1, 'Checked-Out', 1, '2026-07-09 11:54:26', 'admin', '200329422258', 'Agoda', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Canadian', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(37, NULL, 'kalani', 2, NULL, '2026-07-08', '2026-07-09', 1, 'Checked-Out', 1, '2026-07-09 11:54:26', 'admin', '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Maldivian', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(38, NULL, 'kuluni', 5, NULL, '2026-07-08', '2026-07-09', 1, 'Checked-Out', 1, '2026-07-09 11:54:26', 'admin', '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Russian', '', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(39, NULL, 'pubudu sathsara', 8, NULL, '2026-07-08', '2026-07-09', 1, 'Checked-Out', 1, '2026-07-09 11:53:56', 'admin', '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Canadian', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(40, NULL, 'pubudu sathsara', 1, NULL, '2026-07-09', '2026-07-10', 1, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Japanese', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(41, NULL, 'nishni', 8, NULL, '2026-07-09', '2026-07-09', 1, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'French', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(42, NULL, 'bakaa', 9, NULL, '2026-07-09', '2026-07-10', 1, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Female', NULL, 'Japanese', '', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 28000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(43, NULL, 'pubudu sathsara', 8, NULL, '2026-07-08', '2026-07-09', 1, 'Pending', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Russian', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(44, NULL, 'pubudu sathsara', 9, NULL, '2026-07-09', '2026-07-10', 1, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Russian', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 28000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(45, NULL, 'pubudu sathsara', 8, NULL, '2026-07-09', '2026-07-10', 1, 'Pending', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Female', NULL, 'Russian', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 850.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(46, NULL, 'pubudu sathsara', 8, NULL, '2026-07-09', '2026-07-10', 1, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Female', NULL, 'Russian', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 8500.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(47, NULL, 'pubudu sathsara', 8, NULL, '2026-07-09', '2026-07-10', 1, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Female', NULL, 'Russian', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 8500.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(48, NULL, 'kavinda', 8, NULL, '2026-07-09', '2026-07-10', 1, 'Pending', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Japanese', '', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(49, NULL, 'yehan', 8, NULL, '2026-07-09', '2026-07-10', 1, 'Pending', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Canadian', '', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 0.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(50, NULL, 'radessha', 7, NULL, '2026-07-09', '2026-07-10', 1, 'Pending', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Russian', '', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 1500.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(51, NULL, 'pubudu sathsara', 11, NULL, '2026-07-11', '2026-07-13', 1, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Maldivian', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 13000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(52, NULL, 'chamod', 5, NULL, '2026-07-12', '2026-07-13', 1, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Female', NULL, 'Russian', '', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', NULL, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(53, NULL, 'tharaka', 6, NULL, '2026-07-12', '2026-07-13', 1, 'Pending', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Russian', '', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', NULL, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(54, NULL, 'pubudu sathsara', 7, NULL, '2026-07-17', '2026-07-18', 1, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Maldivian', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', NULL, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(55, NULL, 'pubudu sathsara', 10, NULL, '2026-07-17', '2026-07-18', 1, 'Pending', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Russian', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', NULL, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, NULL),
(56, NULL, 'pubudu sathsara', 9, NULL, '2026-07-17', '2026-07-18', 0, 'Pending', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Chinese', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', NULL, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 7),
(57, NULL, 'dilshan ', 8, NULL, '2026-07-17', '2026-07-18', 0, 'Pending', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'German', '', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', NULL, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 6),
(60, NULL, 'malsha', 7, NULL, '2026-07-18', '2026-07-19', 0, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Japanese', '', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 14000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(61, NULL, 'ishani ', 6, NULL, '2026-07-18', '2026-07-19', 0, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Female', NULL, 'Chinese', '', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 28000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(62, NULL, 'hiruni', 9, NULL, '2026-07-18', '2026-07-19', 0, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Chinese', '', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 28000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(63, NULL, 'pubudu sathsara', 6, NULL, '2026-07-18', '2026-07-19', 0, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Japanese', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 28000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(64, NULL, 'chathu', 10, NULL, '2026-07-18', '2026-07-19', 0, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'German', '', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', NULL, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 6),
(66, NULL, 'vbvbvbvvbbbv', 10, NULL, '2026-07-18', '2026-07-19', 0, 'Pending', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Russian', '', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 14000.00, 0.00, 'LKR', NULL, 'gnnnnnn', 0, NULL, NULL, NULL, 6),
(67, NULL, 'sachini', 5, NULL, '2026-07-18', '2026-07-19', 0, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Russian', '', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 28000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 6),
(68, NULL, 'pubudu sathsara', 11, NULL, '2026-07-18', '2026-07-19', 0, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'French', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 14000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 6),
(69, NULL, 'piyumi ', 8, NULL, '2026-07-18', '2026-07-19', 0, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Maldivian', '', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 8500.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 6),
(70, NULL, 'pubudu sathsara', 8, NULL, '2026-07-20', '2026-07-21', 0, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Canadian', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 8500.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(71, NULL, 'pubudu sathsara', 10, NULL, '2026-07-20', '2026-07-21', 0, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Japanese', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 14000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(72, NULL, 'pubudu sathsara', 11, NULL, '2026-07-21', '2026-07-22', 0, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'Aitken Spence Travels', 'AB - FIT LOCAL', 'STANDARD', 'Middle East Luxury', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Maldivian', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Full Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 14000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(73, NULL, 'pubudu sathsara', 89, NULL, '2026-07-21', '2026-07-22', 0, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Japanese', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 14000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(74, NULL, 'pubudu sathsara', 4, NULL, '2026-07-21', '2026-07-22', 0, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Canadian', 'harshanapubudusathsara@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 8500.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(75, NULL, 'Test Guest', 1, NULL, '2026-07-01', '2026-07-02', 1, 'Cancelled', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Guest', NULL, 0, 0, NULL, NULL, 'Sri Lankan', NULL, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 8000.00, 0.00, 'LKR', NULL, NULL, 0, NULL, NULL, NULL, NULL),
(76, NULL, 'tharuka', 8, NULL, '2026-07-21', '2026-07-22', 0, 'Cancelled', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Canadian', '', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 8500.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(77, NULL, 'nisansala', 91, NULL, '2026-07-21', '2026-07-22', 0, 'Pending', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Maldivian', '', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 8500.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(78, NULL, 'pubudu sathsara', 5, NULL, '2026-07-22', '2026-07-23', 0, '', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'British', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 28000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(79, NULL, 'pubudu sathsara', 90, NULL, '2026-07-22', '2026-07-23', 0, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Australian', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 28000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(80, NULL, 'pubudu sathsara', 9, NULL, '2026-07-22', '2026-07-23', 0, 'Pending', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'French', 'harshanapubudusathsara@gmail.com', '0716333475', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 28000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(81, NULL, 'pubudu sathsara', 1, NULL, '2026-07-22', '2026-07-23', 0, 'Checked-Out', 0, NULL, NULL, '200329422258', 'Direct', 'INDIVIDUAL / FIT', 'AB - FIT LOCAL', 'STANDARD', 'Sri Lankan', 'FIT LOCAL', 'NA', '', '', 'Guest', 'LEISURE', 1, 0, 'Male', NULL, 'Italian', 'shehansumith1@gmail.com', '', 1, 0, NULL, NULL, 'Half Board', NULL, 'Dinner', 'Breakfast', '14:00:00', '12:00:00', 14000.00, 0.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(82, 'RES2607220009', 'pubudu sathsara', 5, NULL, '2026-07-22', '2026-07-23', 1, 'Checked-Out', 0, NULL, NULL, NULL, 'Online Booking', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Guest', NULL, 0, 0, NULL, NULL, 'Sri Lankan', 'harshanapubudusathsara@gmail.com', '0740526690', 2, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 8000.00, 8000.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1),
(86, 'RES2607230013', 'pubudu sathsara', 2, NULL, '2026-07-23', '2026-07-24', 1, 'Checked-In', 0, NULL, NULL, NULL, 'Online Booking', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Guest', NULL, 0, 0, NULL, NULL, 'Sri Lankan', 'harshanapubudusathsara@gmail.com', '0740526690', 2, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 8000.00, 16000.00, 'LKR', NULL, '', 0, NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type` varchar(50) DEFAULT NULL,
  `status` enum('available','occupied','cleaning','maintenance') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_number`, `room_type`, `status`) VALUES
(1, '101', 'Deluxe', 'cleaning'),
(2, '102', 'Deluxe', 'occupied'),
(3, '103', 'Standard', 'available'),
(4, '104', 'Standard', 'available'),
(5, '105', 'Suite', 'cleaning'),
(6, '106', 'Suite', 'available'),
(7, '107', 'Deluxe', 'cleaning'),
(8, '108', 'Standard', 'available'),
(9, '109', 'Suite', 'available'),
(10, '110', 'Deluxe', 'available'),
(11, '111', 'Deluxe Room', 'cleaning'),
(12, '112', 'Standard Room', 'available'),
(89, '113', 'Deluxe', 'cleaning'),
(90, '114', 'Suite', 'cleaning'),
(91, '115', 'Standard', 'available'),
(92, '116', 'Deluxe', 'available'),
(93, '117', 'Suite', 'available'),
(94, '118', 'Standard', 'occupied'),
(95, '119', 'Deluxe', 'available'),
(96, '120', 'Suite', 'cleaning'),
(97, '121', 'Standard', 'available'),
(98, '122', 'Deluxe', 'available'),
(99, '123', 'Suite', 'available'),
(100, '124', 'Standard', 'available'),
(101, '125', 'Deluxe', 'available'),
(102, '126', 'Suite', 'available'),
(103, '127', 'Standard', 'available'),
(104, '128', 'Deluxe', 'available'),
(105, '129', 'Suite', 'available'),
(106, '130', 'Standard', 'available'),
(107, '131', 'Deluxe', 'occupied'),
(108, '132', 'Suite', 'available'),
(109, '133', 'Standard', 'available'),
(110, '134', 'Deluxe', 'available'),
(111, '135', 'Suite', 'available'),
(112, '136', 'Standard', 'available'),
(113, '137', 'Deluxe', 'available'),
(114, '138', 'Suite', 'available'),
(115, '139', 'Standard', 'available'),
(116, '140', 'Deluxe', 'available'),
(117, '141', 'Suite', 'available'),
(118, '142', 'Standard', 'available'),
(119, '143', 'Deluxe', 'available'),
(120, '144', 'Suite', 'available'),
(121, '145', 'Standard', 'available'),
(122, '146', 'Deluxe', 'available'),
(123, '147', 'Suite', 'available'),
(124, '148', 'Standard', 'available'),
(125, '149', 'Deluxe', 'occupied'),
(126, '150', 'Luxury Suite', 'occupied');

-- --------------------------------------------------------

--
-- Table structure for table `room_availability`
--

CREATE TABLE `room_availability` (
  `availability_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `blocked_reason` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_availability`
--

INSERT INTO `room_availability` (`availability_id`, `room_id`, `date`, `is_available`, `blocked_reason`, `updated_at`) VALUES
(1, 1, '2026-07-22', 1, NULL, '2026-07-22 14:47:05'),
(2, 2, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(3, 3, '2026-07-22', 0, NULL, '2026-07-22 16:00:30'),
(4, 4, '2026-07-22', 0, NULL, '2026-07-22 14:56:41'),
(5, 5, '2026-07-22', 0, NULL, '2026-07-22 16:03:01'),
(6, 6, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(7, 7, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(8, 8, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(9, 9, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(10, 10, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(11, 11, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(12, 12, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(13, 89, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(14, 90, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(15, 91, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(16, 92, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(17, 93, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(18, 94, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(19, 95, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(20, 96, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(21, 97, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(22, 98, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(23, 99, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(24, 100, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(25, 101, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(26, 102, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(27, 103, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(28, 104, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(29, 105, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(30, 106, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(31, 107, '2026-07-22', 0, NULL, '2026-07-22 16:24:46'),
(32, 108, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(33, 109, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(34, 110, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(35, 111, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(36, 112, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(37, 113, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(38, 114, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(39, 115, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(40, 116, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(41, 117, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(42, 118, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(43, 119, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(44, 120, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(45, 121, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(46, 122, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(47, 123, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(48, 124, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(49, 125, '2026-07-22', 1, NULL, '2026-07-22 11:04:12'),
(50, 126, '2026-07-22', 0, NULL, '2026-07-22 16:10:44'),
(51, 1, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(52, 2, '2026-07-23', 0, NULL, '2026-07-23 04:41:58'),
(53, 3, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(54, 4, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(55, 5, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(56, 6, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(57, 7, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(58, 8, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(59, 9, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(60, 10, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(61, 11, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(62, 12, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(63, 89, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(64, 90, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(65, 91, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(66, 92, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(67, 93, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(68, 94, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(69, 95, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(70, 96, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(71, 97, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(72, 98, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(73, 99, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(74, 100, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(75, 101, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(76, 102, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(77, 103, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(78, 104, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(79, 105, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(80, 106, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(81, 107, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(82, 108, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(83, 109, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(84, 110, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(85, 111, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(86, 112, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(87, 113, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(88, 114, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(89, 115, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(90, 116, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(91, 117, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(92, 118, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(93, 119, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(94, 120, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(95, 121, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(96, 122, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(97, 123, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(98, 124, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(99, 125, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(100, 126, '2026-07-23', 1, NULL, '2026-07-22 11:04:12'),
(101, 1, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(102, 2, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(103, 3, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(104, 4, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(105, 5, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(106, 6, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(107, 7, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(108, 8, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(109, 9, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(110, 10, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(111, 11, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(112, 12, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(113, 89, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(114, 90, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(115, 91, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(116, 92, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(117, 93, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(118, 94, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(119, 95, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(120, 96, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(121, 97, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(122, 98, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(123, 99, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(124, 100, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(125, 101, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(126, 102, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(127, 103, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(128, 104, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(129, 105, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(130, 106, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(131, 107, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(132, 108, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(133, 109, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(134, 110, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(135, 111, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(136, 112, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(137, 113, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(138, 114, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(139, 115, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(140, 116, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(141, 117, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(142, 118, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(143, 119, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(144, 120, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(145, 121, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(146, 122, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(147, 123, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(148, 124, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(149, 125, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(150, 126, '2026-07-24', 1, NULL, '2026-07-22 11:04:12'),
(151, 1, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(152, 2, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(153, 3, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(154, 4, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(155, 5, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(156, 6, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(157, 7, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(158, 8, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(159, 9, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(160, 10, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(161, 11, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(162, 12, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(163, 89, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(164, 90, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(165, 91, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(166, 92, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(167, 93, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(168, 94, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(169, 95, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(170, 96, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(171, 97, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(172, 98, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(173, 99, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(174, 100, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(175, 101, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(176, 102, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(177, 103, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(178, 104, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(179, 105, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(180, 106, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(181, 107, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(182, 108, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(183, 109, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(184, 110, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(185, 111, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(186, 112, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(187, 113, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(188, 114, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(189, 115, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(190, 116, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(191, 117, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(192, 118, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(193, 119, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(194, 120, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(195, 121, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(196, 122, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(197, 123, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(198, 124, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(199, 125, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(200, 126, '2026-07-25', 1, NULL, '2026-07-22 11:04:12'),
(201, 1, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(202, 2, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(203, 3, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(204, 4, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(205, 5, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(206, 6, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(207, 7, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(208, 8, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(209, 9, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(210, 10, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(211, 11, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(212, 12, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(213, 89, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(214, 90, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(215, 91, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(216, 92, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(217, 93, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(218, 94, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(219, 95, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(220, 96, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(221, 97, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(222, 98, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(223, 99, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(224, 100, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(225, 101, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(226, 102, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(227, 103, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(228, 104, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(229, 105, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(230, 106, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(231, 107, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(232, 108, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(233, 109, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(234, 110, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(235, 111, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(236, 112, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(237, 113, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(238, 114, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(239, 115, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(240, 116, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(241, 117, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(242, 118, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(243, 119, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(244, 120, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(245, 121, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(246, 122, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(247, 123, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(248, 124, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(249, 125, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(250, 126, '2026-07-26', 1, NULL, '2026-07-22 11:04:12'),
(251, 1, '2026-07-27', 0, NULL, '2026-07-22 11:04:13'),
(252, 2, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(253, 3, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(254, 4, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(255, 5, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(256, 6, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(257, 7, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(258, 8, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(259, 9, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(260, 10, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(261, 11, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(262, 12, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(263, 89, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(264, 90, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(265, 91, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(266, 92, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(267, 93, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(268, 94, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(269, 95, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(270, 96, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(271, 97, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(272, 98, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(273, 99, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(274, 100, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(275, 101, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(276, 102, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(277, 103, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(278, 104, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(279, 105, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(280, 106, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(281, 107, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(282, 108, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(283, 109, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(284, 110, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(285, 111, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(286, 112, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(287, 113, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(288, 114, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(289, 115, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(290, 116, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(291, 117, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(292, 118, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(293, 119, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(294, 120, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(295, 121, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(296, 122, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(297, 123, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(298, 124, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(299, 125, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(300, 126, '2026-07-27', 1, NULL, '2026-07-22 11:04:12'),
(301, 1, '2026-07-28', 0, NULL, '2026-07-22 11:04:13'),
(302, 2, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(303, 3, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(304, 4, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(305, 5, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(306, 6, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(307, 7, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(308, 8, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(309, 9, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(310, 10, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(311, 11, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(312, 12, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(313, 89, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(314, 90, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(315, 91, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(316, 92, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(317, 93, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(318, 94, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(319, 95, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(320, 96, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(321, 97, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(322, 98, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(323, 99, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(324, 100, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(325, 101, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(326, 102, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(327, 103, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(328, 104, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(329, 105, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(330, 106, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(331, 107, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(332, 108, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(333, 109, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(334, 110, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(335, 111, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(336, 112, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(337, 113, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(338, 114, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(339, 115, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(340, 116, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(341, 117, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(342, 118, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(343, 119, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(344, 120, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(345, 121, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(346, 122, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(347, 123, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(348, 124, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(349, 125, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(350, 126, '2026-07-28', 1, NULL, '2026-07-22 11:04:12'),
(351, 1, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(352, 2, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(353, 3, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(354, 4, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(355, 5, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(356, 6, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(357, 7, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(358, 8, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(359, 9, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(360, 10, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(361, 11, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(362, 12, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(363, 89, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(364, 90, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(365, 91, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(366, 92, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(367, 93, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(368, 94, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(369, 95, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(370, 96, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(371, 97, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(372, 98, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(373, 99, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(374, 100, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(375, 101, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(376, 102, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(377, 103, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(378, 104, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(379, 105, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(380, 106, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(381, 107, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(382, 108, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(383, 109, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(384, 110, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(385, 111, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(386, 112, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(387, 113, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(388, 114, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(389, 115, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(390, 116, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(391, 117, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(392, 118, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(393, 119, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(394, 120, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(395, 121, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(396, 122, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(397, 123, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(398, 124, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(399, 125, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(400, 126, '2026-07-29', 1, NULL, '2026-07-22 11:04:12'),
(401, 1, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(402, 2, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(403, 3, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(404, 4, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(405, 5, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(406, 6, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(407, 7, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(408, 8, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(409, 9, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(410, 10, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(411, 11, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(412, 12, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(413, 89, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(414, 90, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(415, 91, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(416, 92, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(417, 93, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(418, 94, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(419, 95, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(420, 96, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(421, 97, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(422, 98, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(423, 99, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(424, 100, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(425, 101, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(426, 102, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(427, 103, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(428, 104, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(429, 105, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(430, 106, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(431, 107, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(432, 108, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(433, 109, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(434, 110, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(435, 111, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(436, 112, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(437, 113, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(438, 114, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(439, 115, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(440, 116, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(441, 117, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(442, 118, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(443, 119, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(444, 120, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(445, 121, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(446, 122, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(447, 123, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(448, 124, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(449, 125, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(450, 126, '2026-07-30', 1, NULL, '2026-07-22 11:04:12'),
(451, 1, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(452, 2, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(453, 3, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(454, 4, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(455, 5, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(456, 6, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(457, 7, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(458, 8, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(459, 9, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(460, 10, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(461, 11, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(462, 12, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(463, 89, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(464, 90, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(465, 91, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(466, 92, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(467, 93, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(468, 94, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(469, 95, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(470, 96, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(471, 97, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(472, 98, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(473, 99, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(474, 100, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(475, 101, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(476, 102, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(477, 103, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(478, 104, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(479, 105, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(480, 106, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(481, 107, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(482, 108, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(483, 109, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(484, 110, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(485, 111, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(486, 112, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(487, 113, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(488, 114, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(489, 115, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(490, 116, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(491, 117, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(492, 118, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(493, 119, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(494, 120, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(495, 121, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(496, 122, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(497, 123, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(498, 124, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(499, 125, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(500, 126, '2026-07-31', 1, NULL, '2026-07-22 11:04:12'),
(501, 1, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(502, 2, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(503, 3, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(504, 4, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(505, 5, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(506, 6, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(507, 7, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(508, 8, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(509, 9, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(510, 10, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(511, 11, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(512, 12, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(513, 89, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(514, 90, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(515, 91, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(516, 92, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(517, 93, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(518, 94, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(519, 95, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(520, 96, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(521, 97, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(522, 98, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(523, 99, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(524, 100, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(525, 101, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(526, 102, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(527, 103, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(528, 104, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(529, 105, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(530, 106, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(531, 107, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(532, 108, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(533, 109, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(534, 110, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(535, 111, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(536, 112, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(537, 113, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(538, 114, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(539, 115, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(540, 116, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(541, 117, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(542, 118, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(543, 119, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(544, 120, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(545, 121, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(546, 122, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(547, 123, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(548, 124, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(549, 125, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(550, 126, '2026-08-01', 1, NULL, '2026-07-22 11:04:12'),
(551, 1, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(552, 2, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(553, 3, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(554, 4, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(555, 5, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(556, 6, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(557, 7, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(558, 8, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(559, 9, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(560, 10, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(561, 11, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(562, 12, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(563, 89, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(564, 90, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(565, 91, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(566, 92, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(567, 93, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(568, 94, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(569, 95, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(570, 96, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(571, 97, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(572, 98, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(573, 99, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(574, 100, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(575, 101, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(576, 102, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(577, 103, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(578, 104, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(579, 105, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(580, 106, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(581, 107, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(582, 108, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(583, 109, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(584, 110, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(585, 111, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(586, 112, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(587, 113, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(588, 114, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(589, 115, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(590, 116, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(591, 117, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(592, 118, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(593, 119, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(594, 120, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(595, 121, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(596, 122, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(597, 123, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(598, 124, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(599, 125, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(600, 126, '2026-08-02', 1, NULL, '2026-07-22 11:04:12'),
(601, 1, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(602, 2, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(603, 3, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(604, 4, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(605, 5, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(606, 6, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(607, 7, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(608, 8, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(609, 9, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(610, 10, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(611, 11, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(612, 12, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(613, 89, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(614, 90, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(615, 91, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(616, 92, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(617, 93, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(618, 94, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(619, 95, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(620, 96, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(621, 97, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(622, 98, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(623, 99, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(624, 100, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(625, 101, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(626, 102, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(627, 103, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(628, 104, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(629, 105, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(630, 106, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(631, 107, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(632, 108, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(633, 109, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(634, 110, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(635, 111, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(636, 112, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(637, 113, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(638, 114, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(639, 115, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(640, 116, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(641, 117, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(642, 118, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(643, 119, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(644, 120, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(645, 121, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(646, 122, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(647, 123, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(648, 124, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(649, 125, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(650, 126, '2026-08-03', 1, NULL, '2026-07-22 11:04:12'),
(651, 1, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(652, 2, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(653, 3, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(654, 4, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(655, 5, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(656, 6, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(657, 7, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(658, 8, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(659, 9, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(660, 10, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(661, 11, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(662, 12, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(663, 89, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(664, 90, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(665, 91, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(666, 92, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(667, 93, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(668, 94, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(669, 95, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(670, 96, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(671, 97, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(672, 98, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(673, 99, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(674, 100, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(675, 101, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(676, 102, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(677, 103, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(678, 104, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(679, 105, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(680, 106, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(681, 107, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(682, 108, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(683, 109, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(684, 110, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(685, 111, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(686, 112, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(687, 113, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(688, 114, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(689, 115, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(690, 116, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(691, 117, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(692, 118, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(693, 119, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(694, 120, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(695, 121, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(696, 122, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(697, 123, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(698, 124, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(699, 125, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(700, 126, '2026-08-04', 1, NULL, '2026-07-22 11:04:12'),
(701, 1, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(702, 2, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(703, 3, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(704, 4, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(705, 5, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(706, 6, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(707, 7, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(708, 8, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(709, 9, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(710, 10, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(711, 11, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(712, 12, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(713, 89, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(714, 90, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(715, 91, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(716, 92, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(717, 93, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(718, 94, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(719, 95, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(720, 96, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(721, 97, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(722, 98, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(723, 99, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(724, 100, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(725, 101, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(726, 102, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(727, 103, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(728, 104, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(729, 105, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(730, 106, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(731, 107, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(732, 108, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(733, 109, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(734, 110, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(735, 111, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(736, 112, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(737, 113, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(738, 114, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(739, 115, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(740, 116, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(741, 117, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(742, 118, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(743, 119, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(744, 120, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(745, 121, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(746, 122, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(747, 123, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(748, 124, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(749, 125, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(750, 126, '2026-08-05', 1, NULL, '2026-07-22 11:04:12'),
(751, 1, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(752, 2, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(753, 3, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(754, 4, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(755, 5, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(756, 6, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(757, 7, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(758, 8, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(759, 9, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(760, 10, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(761, 11, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(762, 12, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(763, 89, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(764, 90, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(765, 91, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(766, 92, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(767, 93, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(768, 94, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(769, 95, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(770, 96, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(771, 97, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(772, 98, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(773, 99, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(774, 100, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(775, 101, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(776, 102, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(777, 103, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(778, 104, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(779, 105, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(780, 106, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(781, 107, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(782, 108, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(783, 109, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(784, 110, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(785, 111, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(786, 112, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(787, 113, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(788, 114, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(789, 115, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(790, 116, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(791, 117, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(792, 118, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(793, 119, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(794, 120, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(795, 121, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(796, 122, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(797, 123, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(798, 124, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(799, 125, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(800, 126, '2026-08-06', 1, NULL, '2026-07-22 11:04:12'),
(801, 1, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(802, 2, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(803, 3, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(804, 4, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(805, 5, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(806, 6, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(807, 7, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(808, 8, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(809, 9, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(810, 10, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(811, 11, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(812, 12, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(813, 89, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(814, 90, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(815, 91, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(816, 92, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(817, 93, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(818, 94, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(819, 95, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(820, 96, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(821, 97, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(822, 98, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(823, 99, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(824, 100, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(825, 101, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(826, 102, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(827, 103, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(828, 104, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(829, 105, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(830, 106, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(831, 107, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(832, 108, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(833, 109, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(834, 110, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(835, 111, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(836, 112, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(837, 113, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(838, 114, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(839, 115, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(840, 116, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(841, 117, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(842, 118, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(843, 119, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(844, 120, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(845, 121, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(846, 122, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(847, 123, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(848, 124, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(849, 125, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(850, 126, '2026-08-07', 1, NULL, '2026-07-22 11:04:12'),
(851, 1, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(852, 2, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(853, 3, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(854, 4, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(855, 5, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(856, 6, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(857, 7, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(858, 8, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(859, 9, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(860, 10, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(861, 11, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(862, 12, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(863, 89, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(864, 90, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(865, 91, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(866, 92, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(867, 93, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(868, 94, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(869, 95, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(870, 96, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(871, 97, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(872, 98, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(873, 99, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(874, 100, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(875, 101, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(876, 102, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(877, 103, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(878, 104, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(879, 105, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(880, 106, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(881, 107, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(882, 108, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(883, 109, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(884, 110, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(885, 111, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(886, 112, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(887, 113, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(888, 114, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(889, 115, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(890, 116, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(891, 117, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(892, 118, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(893, 119, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(894, 120, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(895, 121, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(896, 122, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(897, 123, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(898, 124, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(899, 125, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(900, 126, '2026-08-08', 1, NULL, '2026-07-22 11:04:12'),
(901, 1, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(902, 2, '2026-08-09', 1, NULL, '2026-07-22 11:04:12');
INSERT INTO `room_availability` (`availability_id`, `room_id`, `date`, `is_available`, `blocked_reason`, `updated_at`) VALUES
(903, 3, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(904, 4, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(905, 5, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(906, 6, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(907, 7, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(908, 8, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(909, 9, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(910, 10, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(911, 11, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(912, 12, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(913, 89, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(914, 90, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(915, 91, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(916, 92, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(917, 93, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(918, 94, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(919, 95, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(920, 96, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(921, 97, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(922, 98, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(923, 99, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(924, 100, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(925, 101, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(926, 102, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(927, 103, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(928, 104, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(929, 105, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(930, 106, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(931, 107, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(932, 108, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(933, 109, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(934, 110, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(935, 111, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(936, 112, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(937, 113, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(938, 114, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(939, 115, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(940, 116, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(941, 117, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(942, 118, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(943, 119, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(944, 120, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(945, 121, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(946, 122, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(947, 123, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(948, 124, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(949, 125, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(950, 126, '2026-08-09', 1, NULL, '2026-07-22 11:04:12'),
(951, 1, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(952, 2, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(953, 3, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(954, 4, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(955, 5, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(956, 6, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(957, 7, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(958, 8, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(959, 9, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(960, 10, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(961, 11, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(962, 12, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(963, 89, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(964, 90, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(965, 91, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(966, 92, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(967, 93, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(968, 94, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(969, 95, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(970, 96, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(971, 97, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(972, 98, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(973, 99, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(974, 100, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(975, 101, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(976, 102, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(977, 103, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(978, 104, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(979, 105, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(980, 106, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(981, 107, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(982, 108, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(983, 109, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(984, 110, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(985, 111, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(986, 112, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(987, 113, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(988, 114, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(989, 115, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(990, 116, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(991, 117, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(992, 118, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(993, 119, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(994, 120, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(995, 121, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(996, 122, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(997, 123, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(998, 124, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(999, 125, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(1000, 126, '2026-08-10', 1, NULL, '2026-07-22 11:04:12'),
(1001, 1, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1002, 2, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1003, 3, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1004, 4, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1005, 5, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1006, 6, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1007, 7, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1008, 8, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1009, 9, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1010, 10, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1011, 11, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1012, 12, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1013, 89, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1014, 90, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1015, 91, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1016, 92, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1017, 93, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1018, 94, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1019, 95, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1020, 96, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1021, 97, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1022, 98, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1023, 99, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1024, 100, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1025, 101, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1026, 102, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1027, 103, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1028, 104, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1029, 105, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1030, 106, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1031, 107, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1032, 108, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1033, 109, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1034, 110, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1035, 111, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1036, 112, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1037, 113, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1038, 114, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1039, 115, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1040, 116, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1041, 117, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1042, 118, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1043, 119, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1044, 120, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1045, 121, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1046, 122, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1047, 123, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1048, 124, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1049, 125, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1050, 126, '2026-08-11', 1, NULL, '2026-07-22 11:04:12'),
(1051, 1, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1052, 2, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1053, 3, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1054, 4, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1055, 5, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1056, 6, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1057, 7, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1058, 8, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1059, 9, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1060, 10, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1061, 11, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1062, 12, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1063, 89, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1064, 90, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1065, 91, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1066, 92, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1067, 93, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1068, 94, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1069, 95, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1070, 96, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1071, 97, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1072, 98, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1073, 99, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1074, 100, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1075, 101, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1076, 102, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1077, 103, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1078, 104, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1079, 105, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1080, 106, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1081, 107, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1082, 108, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1083, 109, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1084, 110, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1085, 111, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1086, 112, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1087, 113, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1088, 114, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1089, 115, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1090, 116, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1091, 117, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1092, 118, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1093, 119, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1094, 120, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1095, 121, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1096, 122, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1097, 123, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1098, 124, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1099, 125, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1100, 126, '2026-08-12', 1, NULL, '2026-07-22 11:04:12'),
(1101, 1, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1102, 2, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1103, 3, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1104, 4, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1105, 5, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1106, 6, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1107, 7, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1108, 8, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1109, 9, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1110, 10, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1111, 11, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1112, 12, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1113, 89, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1114, 90, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1115, 91, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1116, 92, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1117, 93, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1118, 94, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1119, 95, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1120, 96, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1121, 97, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1122, 98, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1123, 99, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1124, 100, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1125, 101, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1126, 102, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1127, 103, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1128, 104, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1129, 105, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1130, 106, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1131, 107, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1132, 108, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1133, 109, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1134, 110, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1135, 111, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1136, 112, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1137, 113, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1138, 114, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1139, 115, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1140, 116, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1141, 117, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1142, 118, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1143, 119, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1144, 120, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1145, 121, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1146, 122, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1147, 123, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1148, 124, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1149, 125, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1150, 126, '2026-08-13', 1, NULL, '2026-07-22 11:04:12'),
(1151, 1, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1152, 2, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1153, 3, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1154, 4, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1155, 5, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1156, 6, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1157, 7, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1158, 8, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1159, 9, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1160, 10, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1161, 11, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1162, 12, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1163, 89, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1164, 90, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1165, 91, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1166, 92, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1167, 93, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1168, 94, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1169, 95, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1170, 96, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1171, 97, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1172, 98, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1173, 99, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1174, 100, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1175, 101, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1176, 102, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1177, 103, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1178, 104, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1179, 105, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1180, 106, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1181, 107, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1182, 108, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1183, 109, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1184, 110, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1185, 111, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1186, 112, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1187, 113, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1188, 114, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1189, 115, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1190, 116, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1191, 117, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1192, 118, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1193, 119, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1194, 120, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1195, 121, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1196, 122, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1197, 123, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1198, 124, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1199, 125, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1200, 126, '2026-08-14', 1, NULL, '2026-07-22 11:04:12'),
(1201, 1, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1202, 2, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1203, 3, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1204, 4, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1205, 5, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1206, 6, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1207, 7, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1208, 8, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1209, 9, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1210, 10, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1211, 11, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1212, 12, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1213, 89, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1214, 90, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1215, 91, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1216, 92, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1217, 93, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1218, 94, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1219, 95, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1220, 96, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1221, 97, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1222, 98, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1223, 99, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1224, 100, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1225, 101, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1226, 102, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1227, 103, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1228, 104, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1229, 105, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1230, 106, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1231, 107, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1232, 108, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1233, 109, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1234, 110, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1235, 111, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1236, 112, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1237, 113, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1238, 114, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1239, 115, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1240, 116, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1241, 117, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1242, 118, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1243, 119, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1244, 120, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1245, 121, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1246, 122, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1247, 123, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1248, 124, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1249, 125, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1250, 126, '2026-08-15', 1, NULL, '2026-07-22 11:04:12'),
(1251, 1, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1252, 2, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1253, 3, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1254, 4, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1255, 5, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1256, 6, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1257, 7, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1258, 8, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1259, 9, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1260, 10, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1261, 11, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1262, 12, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1263, 89, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1264, 90, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1265, 91, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1266, 92, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1267, 93, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1268, 94, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1269, 95, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1270, 96, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1271, 97, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1272, 98, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1273, 99, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1274, 100, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1275, 101, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1276, 102, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1277, 103, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1278, 104, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1279, 105, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1280, 106, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1281, 107, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1282, 108, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1283, 109, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1284, 110, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1285, 111, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1286, 112, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1287, 113, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1288, 114, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1289, 115, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1290, 116, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1291, 117, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1292, 118, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1293, 119, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1294, 120, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1295, 121, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1296, 122, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1297, 123, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1298, 124, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1299, 125, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1300, 126, '2026-08-16', 1, NULL, '2026-07-22 11:04:12'),
(1301, 1, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1302, 2, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1303, 3, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1304, 4, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1305, 5, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1306, 6, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1307, 7, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1308, 8, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1309, 9, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1310, 10, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1311, 11, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1312, 12, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1313, 89, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1314, 90, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1315, 91, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1316, 92, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1317, 93, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1318, 94, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1319, 95, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1320, 96, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1321, 97, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1322, 98, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1323, 99, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1324, 100, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1325, 101, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1326, 102, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1327, 103, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1328, 104, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1329, 105, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1330, 106, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1331, 107, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1332, 108, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1333, 109, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1334, 110, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1335, 111, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1336, 112, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1337, 113, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1338, 114, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1339, 115, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1340, 116, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1341, 117, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1342, 118, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1343, 119, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1344, 120, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1345, 121, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1346, 122, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1347, 123, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1348, 124, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1349, 125, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1350, 126, '2026-08-17', 1, NULL, '2026-07-22 11:04:12'),
(1351, 1, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1352, 2, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1353, 3, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1354, 4, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1355, 5, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1356, 6, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1357, 7, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1358, 8, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1359, 9, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1360, 10, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1361, 11, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1362, 12, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1363, 89, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1364, 90, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1365, 91, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1366, 92, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1367, 93, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1368, 94, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1369, 95, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1370, 96, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1371, 97, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1372, 98, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1373, 99, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1374, 100, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1375, 101, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1376, 102, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1377, 103, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1378, 104, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1379, 105, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1380, 106, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1381, 107, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1382, 108, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1383, 109, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1384, 110, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1385, 111, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1386, 112, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1387, 113, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1388, 114, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1389, 115, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1390, 116, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1391, 117, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1392, 118, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1393, 119, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1394, 120, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1395, 121, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1396, 122, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1397, 123, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1398, 124, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1399, 125, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1400, 126, '2026-08-18', 1, NULL, '2026-07-22 11:04:12'),
(1401, 1, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1402, 2, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1403, 3, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1404, 4, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1405, 5, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1406, 6, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1407, 7, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1408, 8, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1409, 9, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1410, 10, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1411, 11, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1412, 12, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1413, 89, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1414, 90, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1415, 91, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1416, 92, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1417, 93, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1418, 94, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1419, 95, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1420, 96, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1421, 97, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1422, 98, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1423, 99, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1424, 100, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1425, 101, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1426, 102, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1427, 103, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1428, 104, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1429, 105, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1430, 106, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1431, 107, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1432, 108, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1433, 109, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1434, 110, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1435, 111, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1436, 112, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1437, 113, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1438, 114, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1439, 115, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1440, 116, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1441, 117, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1442, 118, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1443, 119, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1444, 120, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1445, 121, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1446, 122, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1447, 123, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1448, 124, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1449, 125, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1450, 126, '2026-08-19', 1, NULL, '2026-07-22 11:04:12'),
(1451, 1, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1452, 2, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1453, 3, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1454, 4, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1455, 5, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1456, 6, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1457, 7, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1458, 8, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1459, 9, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1460, 10, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1461, 11, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1462, 12, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1463, 89, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1464, 90, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1465, 91, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1466, 92, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1467, 93, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1468, 94, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1469, 95, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1470, 96, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1471, 97, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1472, 98, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1473, 99, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1474, 100, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1475, 101, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1476, 102, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1477, 103, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1478, 104, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1479, 105, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1480, 106, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1481, 107, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1482, 108, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1483, 109, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1484, 110, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1485, 111, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1486, 112, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1487, 113, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1488, 114, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1489, 115, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1490, 116, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1491, 117, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1492, 118, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1493, 119, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1494, 120, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1495, 121, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1496, 122, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1497, 123, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1498, 124, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1499, 125, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1500, 126, '2026-08-20', 1, NULL, '2026-07-22 11:04:12'),
(1501, 1, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1502, 2, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1503, 3, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1504, 4, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1505, 5, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1506, 6, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1507, 7, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1508, 8, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1509, 9, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1510, 10, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1511, 11, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1512, 12, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1513, 89, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1514, 90, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1515, 91, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1516, 92, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1517, 93, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1518, 94, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1519, 95, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1520, 96, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1521, 97, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1522, 98, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1523, 99, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1524, 100, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1525, 101, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1526, 102, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1527, 103, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1528, 104, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1529, 105, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1530, 106, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1531, 107, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1532, 108, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1533, 109, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1534, 110, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1535, 111, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1536, 112, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1537, 113, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1538, 114, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1539, 115, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1540, 116, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1541, 117, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1542, 118, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1543, 119, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1544, 120, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1545, 121, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1546, 122, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1547, 123, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1548, 124, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1549, 125, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1550, 126, '2026-08-21', 1, NULL, '2026-07-22 11:04:12'),
(1551, 1, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1552, 2, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1553, 3, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1554, 4, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1555, 5, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1556, 6, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1557, 7, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1558, 8, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1559, 9, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1560, 10, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1561, 11, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1562, 12, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1563, 89, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1564, 90, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1565, 91, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1566, 92, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1567, 93, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1568, 94, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1569, 95, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1570, 96, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1571, 97, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1572, 98, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1573, 99, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1574, 100, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1575, 101, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1576, 102, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1577, 103, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1578, 104, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1579, 105, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1580, 106, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1581, 107, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1582, 108, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1583, 109, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1584, 110, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1585, 111, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1586, 112, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1587, 113, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1588, 114, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1589, 115, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1590, 116, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1591, 117, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1592, 118, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1593, 119, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1594, 120, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1595, 121, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1596, 122, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1597, 123, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1598, 124, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1599, 125, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1600, 126, '2026-08-22', 1, NULL, '2026-07-22 11:04:12'),
(1601, 1, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1602, 2, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1603, 3, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1604, 4, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1605, 5, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1606, 6, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1607, 7, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1608, 8, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1609, 9, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1610, 10, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1611, 11, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1612, 12, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1613, 89, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1614, 90, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1615, 91, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1616, 92, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1617, 93, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1618, 94, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1619, 95, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1620, 96, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1621, 97, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1622, 98, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1623, 99, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1624, 100, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1625, 101, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1626, 102, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1627, 103, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1628, 104, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1629, 105, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1630, 106, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1631, 107, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1632, 108, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1633, 109, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1634, 110, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1635, 111, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1636, 112, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1637, 113, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1638, 114, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1639, 115, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1640, 116, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1641, 117, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1642, 118, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1643, 119, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1644, 120, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1645, 121, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1646, 122, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1647, 123, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1648, 124, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1649, 125, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1650, 126, '2026-08-23', 1, NULL, '2026-07-22 11:04:12'),
(1651, 1, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1652, 2, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1653, 3, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1654, 4, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1655, 5, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1656, 6, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1657, 7, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1658, 8, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1659, 9, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1660, 10, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1661, 11, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1662, 12, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1663, 89, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1664, 90, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1665, 91, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1666, 92, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1667, 93, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1668, 94, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1669, 95, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1670, 96, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1671, 97, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1672, 98, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1673, 99, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1674, 100, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1675, 101, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1676, 102, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1677, 103, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1678, 104, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1679, 105, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1680, 106, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1681, 107, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1682, 108, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1683, 109, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1684, 110, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1685, 111, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1686, 112, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1687, 113, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1688, 114, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1689, 115, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1690, 116, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1691, 117, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1692, 118, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1693, 119, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1694, 120, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1695, 121, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1696, 122, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1697, 123, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1698, 124, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1699, 125, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1700, 126, '2026-08-24', 1, NULL, '2026-07-22 11:04:12'),
(1701, 1, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1702, 2, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1703, 3, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1704, 4, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1705, 5, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1706, 6, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1707, 7, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1708, 8, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1709, 9, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1710, 10, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1711, 11, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1712, 12, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1713, 89, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1714, 90, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1715, 91, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1716, 92, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1717, 93, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1718, 94, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1719, 95, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1720, 96, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1721, 97, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1722, 98, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1723, 99, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1724, 100, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1725, 101, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1726, 102, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1727, 103, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1728, 104, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1729, 105, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1730, 106, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1731, 107, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1732, 108, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1733, 109, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1734, 110, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1735, 111, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1736, 112, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1737, 113, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1738, 114, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1739, 115, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1740, 116, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1741, 117, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1742, 118, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1743, 119, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1744, 120, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1745, 121, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1746, 122, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1747, 123, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1748, 124, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1749, 125, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1750, 126, '2026-08-25', 1, NULL, '2026-07-22 11:04:12'),
(1751, 1, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1752, 2, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1753, 3, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1754, 4, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1755, 5, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1756, 6, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1757, 7, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1758, 8, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1759, 9, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1760, 10, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1761, 11, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1762, 12, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1763, 89, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1764, 90, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1765, 91, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1766, 92, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1767, 93, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1768, 94, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1769, 95, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1770, 96, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1771, 97, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1772, 98, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1773, 99, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1774, 100, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1775, 101, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1776, 102, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1777, 103, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1778, 104, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1779, 105, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1780, 106, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1781, 107, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1782, 108, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1783, 109, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1784, 110, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1785, 111, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1786, 112, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1787, 113, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1788, 114, '2026-08-26', 1, NULL, '2026-07-22 11:04:12');
INSERT INTO `room_availability` (`availability_id`, `room_id`, `date`, `is_available`, `blocked_reason`, `updated_at`) VALUES
(1789, 115, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1790, 116, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1791, 117, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1792, 118, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1793, 119, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1794, 120, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1795, 121, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1796, 122, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1797, 123, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1798, 124, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1799, 125, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1800, 126, '2026-08-26', 1, NULL, '2026-07-22 11:04:12'),
(1801, 1, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1802, 2, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1803, 3, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1804, 4, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1805, 5, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1806, 6, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1807, 7, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1808, 8, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1809, 9, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1810, 10, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1811, 11, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1812, 12, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1813, 89, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1814, 90, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1815, 91, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1816, 92, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1817, 93, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1818, 94, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1819, 95, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1820, 96, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1821, 97, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1822, 98, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1823, 99, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1824, 100, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1825, 101, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1826, 102, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1827, 103, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1828, 104, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1829, 105, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1830, 106, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1831, 107, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1832, 108, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1833, 109, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1834, 110, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1835, 111, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1836, 112, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1837, 113, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1838, 114, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1839, 115, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1840, 116, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1841, 117, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1842, 118, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1843, 119, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1844, 120, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1845, 121, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1846, 122, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1847, 123, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1848, 124, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1849, 125, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1850, 126, '2026-08-27', 1, NULL, '2026-07-22 11:04:12'),
(1851, 1, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1852, 2, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1853, 3, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1854, 4, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1855, 5, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1856, 6, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1857, 7, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1858, 8, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1859, 9, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1860, 10, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1861, 11, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1862, 12, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1863, 89, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1864, 90, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1865, 91, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1866, 92, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1867, 93, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1868, 94, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1869, 95, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1870, 96, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1871, 97, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1872, 98, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1873, 99, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1874, 100, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1875, 101, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1876, 102, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1877, 103, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1878, 104, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1879, 105, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1880, 106, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1881, 107, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1882, 108, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1883, 109, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1884, 110, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1885, 111, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1886, 112, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1887, 113, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1888, 114, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1889, 115, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1890, 116, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1891, 117, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1892, 118, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1893, 119, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1894, 120, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1895, 121, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1896, 122, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1897, 123, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1898, 124, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1899, 125, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1900, 126, '2026-08-28', 1, NULL, '2026-07-22 11:04:12'),
(1901, 1, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1902, 2, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1903, 3, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1904, 4, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1905, 5, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1906, 6, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1907, 7, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1908, 8, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1909, 9, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1910, 10, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1911, 11, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1912, 12, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1913, 89, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1914, 90, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1915, 91, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1916, 92, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1917, 93, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1918, 94, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1919, 95, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1920, 96, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1921, 97, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1922, 98, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1923, 99, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1924, 100, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1925, 101, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1926, 102, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1927, 103, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1928, 104, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1929, 105, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1930, 106, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1931, 107, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1932, 108, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1933, 109, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1934, 110, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1935, 111, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1936, 112, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1937, 113, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1938, 114, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1939, 115, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1940, 116, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1941, 117, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1942, 118, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1943, 119, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1944, 120, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1945, 121, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1946, 122, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1947, 123, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1948, 124, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1949, 125, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1950, 126, '2026-08-29', 1, NULL, '2026-07-22 11:04:12'),
(1951, 1, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1952, 2, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1953, 3, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1954, 4, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1955, 5, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1956, 6, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1957, 7, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1958, 8, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1959, 9, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1960, 10, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1961, 11, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1962, 12, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1963, 89, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1964, 90, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1965, 91, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1966, 92, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1967, 93, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1968, 94, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1969, 95, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1970, 96, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1971, 97, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1972, 98, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1973, 99, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1974, 100, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1975, 101, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1976, 102, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1977, 103, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1978, 104, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1979, 105, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1980, 106, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1981, 107, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1982, 108, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1983, 109, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1984, 110, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1985, 111, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1986, 112, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1987, 113, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1988, 114, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1989, 115, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1990, 116, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1991, 117, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1992, 118, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1993, 119, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1994, 120, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1995, 121, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1996, 122, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1997, 123, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1998, 124, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(1999, 125, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(2000, 126, '2026-08-30', 1, NULL, '2026-07-22 11:04:12'),
(2001, 1, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2002, 2, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2003, 3, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2004, 4, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2005, 5, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2006, 6, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2007, 7, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2008, 8, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2009, 9, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2010, 10, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2011, 11, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2012, 12, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2013, 89, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2014, 90, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2015, 91, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2016, 92, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2017, 93, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2018, 94, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2019, 95, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2020, 96, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2021, 97, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2022, 98, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2023, 99, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2024, 100, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2025, 101, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2026, 102, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2027, 103, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2028, 104, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2029, 105, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2030, 106, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2031, 107, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2032, 108, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2033, 109, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2034, 110, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2035, 111, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2036, 112, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2037, 113, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2038, 114, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2039, 115, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2040, 116, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2041, 117, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2042, 118, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2043, 119, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2044, 120, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2045, 121, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2046, 122, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2047, 123, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2048, 124, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2049, 125, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2050, 126, '2026-08-31', 1, NULL, '2026-07-22 11:04:12'),
(2051, 1, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2052, 2, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2053, 3, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2054, 4, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2055, 5, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2056, 6, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2057, 7, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2058, 8, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2059, 9, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2060, 10, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2061, 11, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2062, 12, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2063, 89, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2064, 90, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2065, 91, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2066, 92, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2067, 93, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2068, 94, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2069, 95, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2070, 96, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2071, 97, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2072, 98, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2073, 99, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2074, 100, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2075, 101, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2076, 102, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2077, 103, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2078, 104, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2079, 105, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2080, 106, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2081, 107, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2082, 108, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2083, 109, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2084, 110, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2085, 111, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2086, 112, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2087, 113, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2088, 114, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2089, 115, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2090, 116, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2091, 117, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2092, 118, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2093, 119, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2094, 120, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2095, 121, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2096, 122, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2097, 123, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2098, 124, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2099, 125, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2100, 126, '2026-09-01', 1, NULL, '2026-07-22 11:04:12'),
(2101, 1, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2102, 2, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2103, 3, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2104, 4, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2105, 5, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2106, 6, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2107, 7, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2108, 8, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2109, 9, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2110, 10, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2111, 11, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2112, 12, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2113, 89, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2114, 90, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2115, 91, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2116, 92, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2117, 93, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2118, 94, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2119, 95, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2120, 96, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2121, 97, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2122, 98, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2123, 99, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2124, 100, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2125, 101, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2126, 102, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2127, 103, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2128, 104, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2129, 105, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2130, 106, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2131, 107, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2132, 108, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2133, 109, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2134, 110, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2135, 111, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2136, 112, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2137, 113, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2138, 114, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2139, 115, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2140, 116, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2141, 117, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2142, 118, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2143, 119, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2144, 120, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2145, 121, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2146, 122, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2147, 123, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2148, 124, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2149, 125, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2150, 126, '2026-09-02', 1, NULL, '2026-07-22 11:04:12'),
(2151, 1, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2152, 2, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2153, 3, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2154, 4, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2155, 5, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2156, 6, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2157, 7, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2158, 8, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2159, 9, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2160, 10, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2161, 11, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2162, 12, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2163, 89, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2164, 90, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2165, 91, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2166, 92, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2167, 93, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2168, 94, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2169, 95, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2170, 96, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2171, 97, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2172, 98, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2173, 99, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2174, 100, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2175, 101, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2176, 102, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2177, 103, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2178, 104, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2179, 105, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2180, 106, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2181, 107, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2182, 108, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2183, 109, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2184, 110, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2185, 111, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2186, 112, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2187, 113, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2188, 114, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2189, 115, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2190, 116, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2191, 117, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2192, 118, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2193, 119, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2194, 120, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2195, 121, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2196, 122, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2197, 123, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2198, 124, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2199, 125, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2200, 126, '2026-09-03', 1, NULL, '2026-07-22 11:04:12'),
(2201, 1, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2202, 2, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2203, 3, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2204, 4, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2205, 5, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2206, 6, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2207, 7, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2208, 8, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2209, 9, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2210, 10, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2211, 11, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2212, 12, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2213, 89, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2214, 90, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2215, 91, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2216, 92, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2217, 93, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2218, 94, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2219, 95, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2220, 96, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2221, 97, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2222, 98, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2223, 99, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2224, 100, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2225, 101, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2226, 102, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2227, 103, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2228, 104, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2229, 105, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2230, 106, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2231, 107, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2232, 108, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2233, 109, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2234, 110, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2235, 111, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2236, 112, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2237, 113, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2238, 114, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2239, 115, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2240, 116, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2241, 117, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2242, 118, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2243, 119, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2244, 120, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2245, 121, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2246, 122, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2247, 123, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2248, 124, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2249, 125, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2250, 126, '2026-09-04', 1, NULL, '2026-07-22 11:04:12'),
(2251, 1, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2252, 2, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2253, 3, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2254, 4, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2255, 5, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2256, 6, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2257, 7, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2258, 8, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2259, 9, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2260, 10, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2261, 11, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2262, 12, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2263, 89, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2264, 90, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2265, 91, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2266, 92, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2267, 93, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2268, 94, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2269, 95, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2270, 96, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2271, 97, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2272, 98, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2273, 99, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2274, 100, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2275, 101, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2276, 102, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2277, 103, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2278, 104, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2279, 105, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2280, 106, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2281, 107, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2282, 108, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2283, 109, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2284, 110, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2285, 111, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2286, 112, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2287, 113, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2288, 114, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2289, 115, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2290, 116, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2291, 117, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2292, 118, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2293, 119, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2294, 120, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2295, 121, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2296, 122, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2297, 123, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2298, 124, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2299, 125, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2300, 126, '2026-09-05', 1, NULL, '2026-07-22 11:04:12'),
(2301, 1, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2302, 2, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2303, 3, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2304, 4, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2305, 5, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2306, 6, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2307, 7, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2308, 8, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2309, 9, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2310, 10, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2311, 11, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2312, 12, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2313, 89, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2314, 90, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2315, 91, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2316, 92, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2317, 93, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2318, 94, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2319, 95, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2320, 96, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2321, 97, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2322, 98, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2323, 99, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2324, 100, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2325, 101, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2326, 102, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2327, 103, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2328, 104, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2329, 105, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2330, 106, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2331, 107, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2332, 108, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2333, 109, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2334, 110, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2335, 111, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2336, 112, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2337, 113, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2338, 114, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2339, 115, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2340, 116, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2341, 117, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2342, 118, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2343, 119, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2344, 120, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2345, 121, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2346, 122, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2347, 123, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2348, 124, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2349, 125, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2350, 126, '2026-09-06', 1, NULL, '2026-07-22 11:04:12'),
(2351, 1, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2352, 2, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2353, 3, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2354, 4, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2355, 5, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2356, 6, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2357, 7, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2358, 8, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2359, 9, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2360, 10, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2361, 11, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2362, 12, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2363, 89, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2364, 90, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2365, 91, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2366, 92, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2367, 93, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2368, 94, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2369, 95, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2370, 96, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2371, 97, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2372, 98, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2373, 99, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2374, 100, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2375, 101, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2376, 102, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2377, 103, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2378, 104, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2379, 105, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2380, 106, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2381, 107, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2382, 108, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2383, 109, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2384, 110, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2385, 111, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2386, 112, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2387, 113, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2388, 114, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2389, 115, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2390, 116, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2391, 117, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2392, 118, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2393, 119, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2394, 120, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2395, 121, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2396, 122, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2397, 123, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2398, 124, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2399, 125, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2400, 126, '2026-09-07', 1, NULL, '2026-07-22 11:04:12'),
(2401, 1, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2402, 2, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2403, 3, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2404, 4, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2405, 5, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2406, 6, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2407, 7, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2408, 8, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2409, 9, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2410, 10, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2411, 11, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2412, 12, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2413, 89, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2414, 90, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2415, 91, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2416, 92, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2417, 93, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2418, 94, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2419, 95, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2420, 96, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2421, 97, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2422, 98, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2423, 99, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2424, 100, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2425, 101, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2426, 102, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2427, 103, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2428, 104, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2429, 105, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2430, 106, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2431, 107, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2432, 108, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2433, 109, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2434, 110, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2435, 111, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2436, 112, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2437, 113, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2438, 114, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2439, 115, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2440, 116, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2441, 117, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2442, 118, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2443, 119, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2444, 120, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2445, 121, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2446, 122, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2447, 123, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2448, 124, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2449, 125, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2450, 126, '2026-09-08', 1, NULL, '2026-07-22 11:04:12'),
(2451, 1, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2452, 2, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2453, 3, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2454, 4, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2455, 5, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2456, 6, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2457, 7, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2458, 8, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2459, 9, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2460, 10, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2461, 11, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2462, 12, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2463, 89, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2464, 90, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2465, 91, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2466, 92, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2467, 93, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2468, 94, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2469, 95, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2470, 96, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2471, 97, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2472, 98, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2473, 99, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2474, 100, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2475, 101, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2476, 102, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2477, 103, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2478, 104, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2479, 105, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2480, 106, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2481, 107, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2482, 108, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2483, 109, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2484, 110, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2485, 111, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2486, 112, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2487, 113, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2488, 114, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2489, 115, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2490, 116, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2491, 117, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2492, 118, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2493, 119, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2494, 120, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2495, 121, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2496, 122, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2497, 123, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2498, 124, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2499, 125, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2500, 126, '2026-09-09', 1, NULL, '2026-07-22 11:04:12'),
(2501, 1, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2502, 2, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2503, 3, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2504, 4, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2505, 5, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2506, 6, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2507, 7, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2508, 8, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2509, 9, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2510, 10, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2511, 11, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2512, 12, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2513, 89, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2514, 90, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2515, 91, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2516, 92, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2517, 93, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2518, 94, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2519, 95, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2520, 96, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2521, 97, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2522, 98, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2523, 99, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2524, 100, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2525, 101, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2526, 102, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2527, 103, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2528, 104, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2529, 105, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2530, 106, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2531, 107, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2532, 108, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2533, 109, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2534, 110, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2535, 111, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2536, 112, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2537, 113, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2538, 114, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2539, 115, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2540, 116, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2541, 117, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2542, 118, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2543, 119, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2544, 120, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2545, 121, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2546, 122, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2547, 123, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2548, 124, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2549, 125, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2550, 126, '2026-09-10', 1, NULL, '2026-07-22 11:04:12'),
(2551, 1, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2552, 2, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2553, 3, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2554, 4, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2555, 5, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2556, 6, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2557, 7, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2558, 8, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2559, 9, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2560, 10, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2561, 11, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2562, 12, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2563, 89, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2564, 90, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2565, 91, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2566, 92, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2567, 93, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2568, 94, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2569, 95, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2570, 96, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2571, 97, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2572, 98, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2573, 99, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2574, 100, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2575, 101, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2576, 102, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2577, 103, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2578, 104, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2579, 105, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2580, 106, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2581, 107, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2582, 108, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2583, 109, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2584, 110, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2585, 111, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2586, 112, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2587, 113, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2588, 114, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2589, 115, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2590, 116, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2591, 117, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2592, 118, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2593, 119, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2594, 120, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2595, 121, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2596, 122, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2597, 123, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2598, 124, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2599, 125, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2600, 126, '2026-09-11', 1, NULL, '2026-07-22 11:04:12'),
(2601, 1, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2602, 2, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2603, 3, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2604, 4, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2605, 5, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2606, 6, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2607, 7, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2608, 8, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2609, 9, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2610, 10, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2611, 11, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2612, 12, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2613, 89, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2614, 90, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2615, 91, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2616, 92, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2617, 93, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2618, 94, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2619, 95, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2620, 96, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2621, 97, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2622, 98, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2623, 99, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2624, 100, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2625, 101, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2626, 102, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2627, 103, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2628, 104, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2629, 105, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2630, 106, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2631, 107, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2632, 108, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2633, 109, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2634, 110, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2635, 111, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2636, 112, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2637, 113, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2638, 114, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2639, 115, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2640, 116, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2641, 117, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2642, 118, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2643, 119, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2644, 120, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2645, 121, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2646, 122, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2647, 123, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2648, 124, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2649, 125, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2650, 126, '2026-09-12', 1, NULL, '2026-07-22 11:04:12'),
(2651, 1, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2652, 2, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2653, 3, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2654, 4, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2655, 5, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2656, 6, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2657, 7, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2658, 8, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2659, 9, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2660, 10, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2661, 11, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2662, 12, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2663, 89, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2664, 90, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2665, 91, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2666, 92, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2667, 93, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2668, 94, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2669, 95, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2670, 96, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2671, 97, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2672, 98, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2673, 99, '2026-09-13', 1, NULL, '2026-07-22 11:04:12');
INSERT INTO `room_availability` (`availability_id`, `room_id`, `date`, `is_available`, `blocked_reason`, `updated_at`) VALUES
(2674, 100, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2675, 101, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2676, 102, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2677, 103, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2678, 104, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2679, 105, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2680, 106, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2681, 107, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2682, 108, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2683, 109, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2684, 110, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2685, 111, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2686, 112, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2687, 113, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2688, 114, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2689, 115, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2690, 116, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2691, 117, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2692, 118, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2693, 119, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2694, 120, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2695, 121, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2696, 122, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2697, 123, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2698, 124, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2699, 125, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2700, 126, '2026-09-13', 1, NULL, '2026-07-22 11:04:12'),
(2701, 1, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2702, 2, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2703, 3, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2704, 4, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2705, 5, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2706, 6, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2707, 7, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2708, 8, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2709, 9, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2710, 10, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2711, 11, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2712, 12, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2713, 89, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2714, 90, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2715, 91, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2716, 92, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2717, 93, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2718, 94, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2719, 95, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2720, 96, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2721, 97, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2722, 98, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2723, 99, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2724, 100, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2725, 101, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2726, 102, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2727, 103, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2728, 104, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2729, 105, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2730, 106, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2731, 107, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2732, 108, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2733, 109, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2734, 110, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2735, 111, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2736, 112, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2737, 113, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2738, 114, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2739, 115, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2740, 116, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2741, 117, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2742, 118, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2743, 119, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2744, 120, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2745, 121, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2746, 122, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2747, 123, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2748, 124, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2749, 125, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2750, 126, '2026-09-14', 1, NULL, '2026-07-22 11:04:12'),
(2751, 1, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2752, 2, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2753, 3, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2754, 4, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2755, 5, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2756, 6, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2757, 7, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2758, 8, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2759, 9, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2760, 10, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2761, 11, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2762, 12, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2763, 89, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2764, 90, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2765, 91, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2766, 92, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2767, 93, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2768, 94, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2769, 95, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2770, 96, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2771, 97, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2772, 98, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2773, 99, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2774, 100, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2775, 101, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2776, 102, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2777, 103, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2778, 104, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2779, 105, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2780, 106, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2781, 107, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2782, 108, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2783, 109, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2784, 110, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2785, 111, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2786, 112, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2787, 113, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2788, 114, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2789, 115, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2790, 116, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2791, 117, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2792, 118, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2793, 119, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2794, 120, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2795, 121, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2796, 122, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2797, 123, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2798, 124, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2799, 125, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2800, 126, '2026-09-15', 1, NULL, '2026-07-22 11:04:12'),
(2801, 1, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2802, 2, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2803, 3, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2804, 4, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2805, 5, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2806, 6, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2807, 7, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2808, 8, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2809, 9, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2810, 10, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2811, 11, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2812, 12, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2813, 89, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2814, 90, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2815, 91, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2816, 92, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2817, 93, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2818, 94, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2819, 95, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2820, 96, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2821, 97, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2822, 98, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2823, 99, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2824, 100, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2825, 101, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2826, 102, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2827, 103, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2828, 104, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2829, 105, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2830, 106, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2831, 107, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2832, 108, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2833, 109, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2834, 110, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2835, 111, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2836, 112, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2837, 113, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2838, 114, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2839, 115, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2840, 116, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2841, 117, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2842, 118, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2843, 119, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2844, 120, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2845, 121, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2846, 122, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2847, 123, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2848, 124, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2849, 125, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2850, 126, '2026-09-16', 1, NULL, '2026-07-22 11:04:12'),
(2851, 1, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2852, 2, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2853, 3, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2854, 4, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2855, 5, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2856, 6, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2857, 7, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2858, 8, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2859, 9, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2860, 10, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2861, 11, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2862, 12, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2863, 89, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2864, 90, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2865, 91, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2866, 92, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2867, 93, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2868, 94, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2869, 95, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2870, 96, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2871, 97, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2872, 98, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2873, 99, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2874, 100, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2875, 101, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2876, 102, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2877, 103, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2878, 104, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2879, 105, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2880, 106, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2881, 107, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2882, 108, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2883, 109, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2884, 110, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2885, 111, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2886, 112, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2887, 113, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2888, 114, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2889, 115, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2890, 116, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2891, 117, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2892, 118, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2893, 119, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2894, 120, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2895, 121, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2896, 122, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2897, 123, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2898, 124, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2899, 125, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2900, 126, '2026-09-17', 1, NULL, '2026-07-22 11:04:12'),
(2901, 1, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2902, 2, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2903, 3, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2904, 4, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2905, 5, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2906, 6, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2907, 7, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2908, 8, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2909, 9, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2910, 10, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2911, 11, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2912, 12, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2913, 89, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2914, 90, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2915, 91, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2916, 92, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2917, 93, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2918, 94, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2919, 95, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2920, 96, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2921, 97, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2922, 98, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2923, 99, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2924, 100, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2925, 101, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2926, 102, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2927, 103, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2928, 104, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2929, 105, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2930, 106, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2931, 107, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2932, 108, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2933, 109, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2934, 110, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2935, 111, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2936, 112, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2937, 113, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2938, 114, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2939, 115, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2940, 116, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2941, 117, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2942, 118, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2943, 119, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2944, 120, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2945, 121, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2946, 122, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2947, 123, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2948, 124, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2949, 125, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2950, 126, '2026-09-18', 1, NULL, '2026-07-22 11:04:12'),
(2951, 1, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2952, 2, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2953, 3, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2954, 4, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2955, 5, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2956, 6, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2957, 7, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2958, 8, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2959, 9, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2960, 10, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2961, 11, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2962, 12, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2963, 89, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2964, 90, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2965, 91, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2966, 92, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2967, 93, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2968, 94, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2969, 95, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2970, 96, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2971, 97, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2972, 98, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2973, 99, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2974, 100, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2975, 101, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2976, 102, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2977, 103, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2978, 104, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2979, 105, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2980, 106, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2981, 107, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2982, 108, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2983, 109, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2984, 110, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2985, 111, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2986, 112, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2987, 113, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2988, 114, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2989, 115, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2990, 116, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2991, 117, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2992, 118, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2993, 119, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2994, 120, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2995, 121, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2996, 122, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2997, 123, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2998, 124, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(2999, 125, '2026-09-19', 1, NULL, '2026-07-22 11:04:12'),
(3000, 126, '2026-09-19', 1, NULL, '2026-07-22 11:04:12');

-- --------------------------------------------------------

--
-- Table structure for table `room_status_history`
--

CREATE TABLE `room_status_history` (
  `history_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `assigned_staff_id` int(11) DEFAULT NULL,
  `changed_by` varchar(50) DEFAULT 'Reception',
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_status_history`
--

INSERT INTO `room_status_history` (`history_id`, `room_id`, `old_status`, `new_status`, `assigned_staff_id`, `changed_by`, `changed_at`) VALUES
(1, 1, 'cleaning', 'maintenance', NULL, 'Receptionist', '2026-07-12 12:38:44'),
(2, 10, 'occupied', 'maintenance', NULL, 'Receptionist', '2026-07-12 12:39:36'),
(3, 11, 'occupied', 'occupied', 3, 'Receptionist', '2026-07-12 12:40:28'),
(4, 6, 'occupied', 'available', NULL, 'Receptionist', '2026-07-12 12:41:45'),
(5, 1, 'maintenance', 'occupied', NULL, 'Receptionist', '2026-07-12 12:44:21'),
(6, 11, 'occupied', 'maintenance', NULL, 'Receptionist', '2026-07-12 12:51:54'),
(7, 12, 'available', 'cleaning', NULL, 'Receptionist', '2026-07-12 14:27:03'),
(8, 9, 'occupied', 'cleaning', NULL, 'Receptionist', '2026-07-12 14:37:37'),
(9, 8, 'occupied', 'maintenance', NULL, 'Receptionist', '2026-07-17 17:40:09'),
(10, 5, 'cleaning', 'available', NULL, 'Receptionist', '2026-07-17 18:21:58'),
(11, 2, 'cleaning', 'cleaning', NULL, 'Receptionist', '2026-07-17 18:24:28'),
(12, 10, 'occupied', 'available', NULL, 'Receptionist', '2026-07-17 18:29:57'),
(13, 10, 'available', 'occupied', NULL, 'Receptionist', '2026-07-17 18:30:45'),
(14, 10, 'occupied', 'available', NULL, 'Receptionist', '2026-07-17 18:33:21'),
(15, 9, 'occupied', 'cleaning', NULL, 'Receptionist', '2026-07-17 18:35:33'),
(16, 10, 'available', 'cleaning', NULL, 'Receptionist', '2026-07-18 05:14:35'),
(17, 12, 'cleaning', 'maintenance', NULL, 'Receptionist', '2026-07-20 15:17:56'),
(18, 11, 'cleaning', 'occupied', NULL, 'admin', '2026-07-20 15:29:23'),
(19, 12, 'maintenance', 'available', NULL, 'admin', '2026-07-20 15:30:16'),
(20, 12, 'available', 'cleaning', NULL, 'admin', '2026-07-20 15:37:06'),
(21, 1, 'occupied', 'available', NULL, 'admin', '2026-07-20 15:54:10'),
(22, 2, 'cleaning', 'available', NULL, 'admin', '2026-07-20 15:54:26'),
(23, 3, 'occupied', 'available', NULL, 'admin', '2026-07-20 16:09:33'),
(24, 5, 'cleaning', 'available', NULL, 'admin', '2026-07-20 16:18:52'),
(25, 1, 'available', 'maintenance', NULL, 'admin', '2026-07-21 04:32:18'),
(26, 1, 'maintenance', 'available', NULL, 'admin', '2026-07-21 04:45:21'),
(27, 2, 'available', 'occupied', NULL, 'admin', '2026-07-21 04:51:35'),
(28, 1, 'available', 'maintenance', NULL, 'admin', '2026-07-21 06:18:38'),
(29, 12, 'cleaning', 'available', NULL, 'admin', '2026-07-21 06:18:42'),
(30, 8, 'cleaning', 'maintenance', NULL, 'admin', '2026-07-21 06:51:22'),
(31, 1, 'maintenance', 'available', NULL, 'admin', '2026-07-21 07:25:09'),
(32, 2, 'occupied', 'available', NULL, 'admin', '2026-07-21 07:38:41'),
(33, 4, 'cleaning', 'available', NULL, 'admin', '2026-07-21 08:00:38'),
(34, 11, 'available', 'cleaning', NULL, 'admin', '2026-07-21 08:03:43'),
(35, 11, 'available', 'occupied', NULL, 'admin', '2026-07-21 09:09:23'),
(36, 11, 'available', 'occupied', NULL, 'admin', '2026-07-21 09:10:56'),
(37, 11, 'available', 'occupied', NULL, 'admin', '2026-07-21 09:12:44'),
(38, 11, 'available', 'occupied', NULL, 'admin', '2026-07-21 09:20:06'),
(39, 11, 'available', 'occupied', NULL, 'admin', '2026-07-21 09:21:37'),
(40, 11, 'available', 'occupied', NULL, 'admin', '2026-07-21 09:24:42'),
(41, 11, 'available', 'occupied', NULL, 'admin', '2026-07-21 09:24:55'),
(42, 11, 'available', 'occupied', NULL, 'admin', '2026-07-21 09:28:10'),
(43, 11, 'available', 'occupied', NULL, 'admin', '2026-07-21 09:29:50'),
(44, 11, 'occupied', 'cleaning', NULL, 'admin', '2026-07-21 09:33:04'),
(45, 11, 'cleaning', 'available', NULL, 'admin', '2026-07-21 09:33:28'),
(46, 11, 'available', 'occupied', NULL, 'admin', '2026-07-21 09:33:38'),
(47, 11, 'available', 'occupied', NULL, 'admin', '2026-07-21 09:35:01'),
(48, 11, 'occupied', 'available', NULL, 'admin', '2026-07-21 09:40:32'),
(49, 11, 'available', 'occupied', NULL, 'System', '2026-07-21 09:40:50'),
(50, 89, 'occupied', 'available', NULL, 'admin', '2026-07-21 09:49:05'),
(51, 89, 'available', 'occupied', NULL, 'admin', '2026-07-21 09:49:16'),
(52, 4, 'occupied', 'available', NULL, 'admin', '2026-07-21 09:56:51'),
(53, 4, 'available', 'occupied', NULL, 'System', '2026-07-21 09:57:07'),
(54, 1, 'available', 'cleaning', NULL, 'admin', '2026-07-21 10:29:26'),
(55, 96, 'available', 'cleaning', NULL, 'admin', '2026-07-21 10:32:17'),
(56, 125, 'available', 'occupied', NULL, 'admin', '2026-07-21 10:32:23'),
(57, 1, 'cleaning', 'available', NULL, 'admin', '2026-07-21 10:37:00'),
(58, 4, 'cleaning', 'maintenance', NULL, 'admin', '2026-07-21 10:46:19'),
(59, 91, 'occupied', 'available', NULL, 'admin', '2026-07-21 16:35:32'),
(60, 91, 'available', 'occupied', NULL, 'admin', '2026-07-21 16:35:42'),
(61, 5, 'occupied', 'available', NULL, 'admin', '2026-07-22 05:09:51'),
(62, 9, 'occupied', 'available', NULL, 'admin', '2026-07-22 05:44:48'),
(63, 1, 'occupied', 'available', NULL, 'admin', '2026-07-22 05:46:55'),
(64, 4, 'maintenance', 'available', NULL, 'admin', '2026-07-22 12:36:22'),
(65, 1, 'occupied', 'available', NULL, 'admin', '2026-07-22 12:36:30'),
(66, 10, 'cleaning', 'available', NULL, 'admin', '2026-07-22 12:36:41'),
(67, 6, 'cleaning', 'available', NULL, 'admin', '2026-07-22 13:11:07'),
(68, 8, 'occupied', 'available', NULL, 'admin', '2026-07-22 13:11:17'),
(69, 5, 'available', 'cleaning', NULL, 'admin', '2026-07-22 16:08:14'),
(70, 1, 'available', 'occupied', NULL, 'admin', '2026-07-22 16:35:45'),
(71, 2, 'occupied', 'available', NULL, 'admin', '2026-07-23 04:44:53');

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

CREATE TABLE `room_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `season_price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `short_code` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`type_id`, `type_name`, `base_price`, `season_price`, `description`, `image`, `short_code`) VALUES
(1, 'Standard Room', 8500.00, 13000.00, 'Basic amenities with AC', NULL, 'STA'),
(2, 'Deluxe Room', 14000.00, 20000.00, 'Sea view with balcony and mini-bar', NULL, 'DEL'),
(3, 'Luxury Suite', 28000.00, 38000.00, 'Private jacuzzi and king size bed', NULL, 'LUX'),
(4, 'Apartment', 20000.00, 30000.00, '2 living rooms with mini kitchen', NULL, 'APA'),
(5, 'change room', 4000.00, 6000.00, 'no beds', NULL, 'CHA'),
(6, 'change room', 4000.00, 6000.00, 'no beds', NULL, 'CHA'),
(7, 'change room', 4000.00, 6000.00, 'no beds', NULL, 'CHA');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_postings`
--

CREATE TABLE `schedule_postings` (
  `schedule_id` int(11) NOT NULL,
  `res_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `frequency` enum('daily','weekly','monthly','one-time') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `last_posted` date DEFAULT NULL,
  `next_post_date` date DEFAULT NULL,
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `traces`
--

CREATE TABLE `traces` (
  `trace_id` int(11) NOT NULL,
  `res_id` int(11) DEFAULT NULL,
  `department` enum('Front Desk','Housekeeping','F&B','Maintenance','Accounts','General') DEFAULT 'General',
  `trace_date` date NOT NULL,
  `description` text NOT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Completed','Cancelled') DEFAULT 'Pending',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `traces`
--

INSERT INTO `traces` (`trace_id`, `res_id`, `department`, `trace_date`, `description`, `assigned_to`, `status`, `created_by`, `created_at`) VALUES
(1, 20, 'Front Desk', '2026-07-01', 'VIP', 'FRONT', 'Completed', 'admin', '2026-06-30 09:06:16'),
(2, 18, 'General', '2026-06-30', 'VIP N', 'FRONT', 'Completed', 'admin', '2026-06-30 09:13:56'),
(3, 61, 'Housekeeping', '2026-07-18', 'HONEYMOON', 'SJDKSJKDJ', 'Pending', 'admin', '2026-07-18 05:12:03');

-- --------------------------------------------------------

--
-- Table structure for table `travel_agents`
--

CREATE TABLE `travel_agents` (
  `agent_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 10.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `travel_agents`
--

INSERT INTO `travel_agents` (`agent_id`, `user_id`, `company_name`, `contact_person`, `phone`, `email`, `address`, `commission_rate`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 2, 'John Travels & Tours', 'John Doe', '0712345679', 'agent@example.com', NULL, 10.00, 1, '2026-07-22 11:04:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','receptionist') DEFAULT 'receptionist',
  `nic` varchar(20) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `username`, `password`, `role`, `nic`, `phone`, `gender`, `email`, `created_at`) VALUES
(1, 'System Administrator', 'admin', '$2y$10$4LTlgc8vJ6i1wnHgAVFE7e4tiXmumccMvddvMAKyql2Csv6l/tArO', 'admin', NULL, NULL, NULL, NULL, '2026-06-22 15:22:32'),
(6, 'chamod', 'chamod', '$2y$10$uMlZTWkPHAw7m1U.8LE/5uUVPRTaaffhOz4rnBnQgfn6A7L83vn7G', 'receptionist', '196812002560', '0740526690', 'Female', NULL, '2026-07-08 18:27:53'),
(7, 'pubudu sathsara', 'piyumi', '$2y$10$o5x732qcFEORb4H.wMC3COipmQaGxoskS6cIiJp1ENrnDCbn6Bil.', 'admin', '196812002560', '0740526690', 'Female', NULL, '2026-07-11 07:38:58'),
(8, 'hiruni ekanayaka', 'hiruni', '$2y$10$EqP1ayx.nDFOCdoOIKHBsulCytEBJfDTciwWrlNdw7RK68IMZOK.2', 'receptionist', '196812002560', '0740526690', 'Male', NULL, '2026-07-12 08:17:00'),
(9, 'nadun', 'nadun', '$2y$10$c2iocvJsz5zv5lj9yqurF.AmWdd5W50aqr.pGVl6lqtjj9NmhncSG', 'manager', '196812002560', '0740526690', 'Male', NULL, '2026-07-17 18:20:34');

-- --------------------------------------------------------

--
-- Table structure for table `void_postings`
--

CREATE TABLE `void_postings` (
  `void_id` int(11) NOT NULL,
  `trans_id` int(11) NOT NULL,
  `folio_id` int(11) NOT NULL,
  `void_reason` text NOT NULL,
  `voided_by` int(11) NOT NULL,
  `void_date` datetime DEFAULT current_timestamp(),
  `supervisor_approved` tinyint(1) DEFAULT 0,
  `approval_date` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_agent_performance`
-- (See below for the actual view)
--
CREATE TABLE `v_agent_performance` (
`agent_id` int(11)
,`company_name` varchar(100)
,`total_bookings` bigint(21)
,`total_revenue` decimal(32,2)
,`total_commission` decimal(32,2)
,`avg_booking_value` decimal(14,6)
,`checked_in_bookings` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_online_booking_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_online_booking_summary` (
`booking_date` date
,`total_bookings` bigint(21)
,`total_revenue` decimal(32,2)
,`paid_bookings` decimal(22,0)
,`pending_bookings` decimal(22,0)
,`failed_bookings` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `website_settings`
--

CREATE TABLE `website_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `group` varchar(50) DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `website_settings`
--

INSERT INTO `website_settings` (`id`, `setting_key`, `setting_value`, `group`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Araliya Beach Resort & Spa', 'general', '2026-07-22 11:04:12', NULL),
(2, 'site_url', 'http://localhost/hotel_management_structure', 'general', '2026-07-22 11:04:12', NULL),
(3, 'contact_email', 'info@araliyaresort.com', 'contact', '2026-07-22 11:04:12', NULL),
(4, 'contact_phone', '+94 91 234 5678', 'contact', '2026-07-22 11:04:12', NULL),
(5, 'contact_address', 'Galle Road, Unawatuna, Sri Lanka', 'contact', '2026-07-22 11:04:12', NULL),
(6, 'currency', 'LKR', 'general', '2026-07-22 11:04:12', NULL),
(7, 'currency_symbol', 'Rs.', 'general', '2026-07-22 11:04:12', NULL),
(8, 'stripe_public_key', 'pk_test_xxx', 'payment', '2026-07-22 11:04:12', NULL),
(9, 'stripe_secret_key', 'sk_test_xxx', 'payment', '2026-07-22 11:04:12', NULL),
(10, 'paypal_client_id', 'xxx', 'payment', '2026-07-22 11:04:12', NULL),
(11, 'paypal_secret', 'xxx', 'payment', '2026-07-22 11:04:12', NULL),
(12, 'commission_rate_default', '10.00', 'agents', '2026-07-22 11:04:12', NULL),
(13, 'booking_early_days', '365', 'booking', '2026-07-22 11:04:12', NULL),
(14, 'booking_max_days', '30', 'booking', '2026-07-22 11:04:12', NULL),
(15, 'min_advance_booking', '0', 'booking', '2026-07-22 11:04:12', NULL),
(16, 'max_advance_booking', '365', 'booking', '2026-07-22 11:04:12', NULL);

-- --------------------------------------------------------

--
-- Structure for view `v_agent_performance`
--
DROP TABLE IF EXISTS `v_agent_performance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_agent_performance`  AS SELECT `ta`.`agent_id` AS `agent_id`, `ta`.`company_name` AS `company_name`, count(`ob`.`booking_id`) AS `total_bookings`, sum(`ob`.`total_amount`) AS `total_revenue`, sum(`ob`.`agent_commission`) AS `total_commission`, avg(`ob`.`total_amount`) AS `avg_booking_value`, sum(case when `ob`.`booking_status` = 'checked_in' then 1 else 0 end) AS `checked_in_bookings` FROM (`travel_agents` `ta` left join `online_bookings` `ob` on(`ta`.`agent_id` = `ob`.`travel_agent_id`)) WHERE `ob`.`booking_status` <> 'cancelled' OR `ob`.`booking_status` is null GROUP BY `ta`.`agent_id` ORDER BY sum(`ob`.`total_amount`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_online_booking_summary`
--
DROP TABLE IF EXISTS `v_online_booking_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_online_booking_summary`  AS SELECT cast(`online_bookings`.`created_at` as date) AS `booking_date`, count(0) AS `total_bookings`, sum(`online_bookings`.`total_amount`) AS `total_revenue`, sum(case when `online_bookings`.`payment_status` = 'paid' then 1 else 0 end) AS `paid_bookings`, sum(case when `online_bookings`.`payment_status` = 'pending' then 1 else 0 end) AS `pending_bookings`, sum(case when `online_bookings`.`payment_status` = 'failed' then 1 else 0 end) AS `failed_bookings` FROM `online_bookings` GROUP BY cast(`online_bookings`.`created_at` as date) ORDER BY cast(`online_bookings`.`created_at` as date) DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `agent_commission_log`
--
ALTER TABLE `agent_commission_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `agent_id` (`agent_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `idx_agent_status` (`agent_id`,`status`);

--
-- Indexes for table `allotments`
--
ALTER TABLE `allotments`
  ADD PRIMARY KEY (`allotment_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`att_id`),
  ADD KEY `emp_id` (`emp_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `credit_debit_notes`
--
ALTER TABLE `credit_debit_notes`
  ADD PRIMARY KEY (`note_id`),
  ADD KEY `credit_debit_notes_ibfk_1` (`res_id`),
  ADD KEY `credit_debit_notes_ibfk_2` (`folio_id`),
  ADD KEY `credit_debit_notes_ibfk_3` (`reference_trans_id`);

--
-- Indexes for table `deposit_refunds`
--
ALTER TABLE `deposit_refunds`
  ADD PRIMARY KEY (`refund_id`),
  ADD KEY `res_id` (`res_id`),
  ADD KEY `folio_id` (`folio_id`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `idx_created_status` (`created_at`,`status`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`emp_id`);

--
-- Indexes for table `exchange_rates`
--
ALTER TABLE `exchange_rates`
  ADD PRIMARY KEY (`rate_id`),
  ADD UNIQUE KEY `currency_code` (`currency_code`);

--
-- Indexes for table `exchange_transactions`
--
ALTER TABLE `exchange_transactions`
  ADD PRIMARY KEY (`exchange_id`),
  ADD KEY `res_id` (`res_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `folios`
--
ALTER TABLE `folios`
  ADD PRIMARY KEY (`folio_id`),
  ADD KEY `res_id` (`res_id`);

--
-- Indexes for table `folio_transactions`
--
ALTER TABLE `folio_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `fk_folio_id` (`folio_id`),
  ADD KEY `idx_res_id` (`res_id`),
  ADD KEY `idx_trans_type` (`trans_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_folio_user` (`created_by`);

--
-- Indexes for table `guest_services`
--
ALTER TABLE `guest_services`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `guest_services_ibfk_1` (`res_id`),
  ADD KEY `guest_services_ibfk_2` (`room_id`);

--
-- Indexes for table `hotel_settings`
--
ALTER TABLE `hotel_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `housekeeping_staff`
--
ALTER TABLE `housekeeping_staff`
  ADD PRIMARY KEY (`staff_id`);

--
-- Indexes for table `laundry_items`
--
ALTER TABLE `laundry_items`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `laundry_orders`
--
ALTER TABLE `laundry_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `res_id` (`res_id`);

--
-- Indexes for table `laundry_order_items`
--
ALTER TABLE `laundry_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `nationalities`
--
ALTER TABLE `nationalities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `online_bookings`
--
ALTER TABLE `online_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `booking_reference` (`booking_reference`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `travel_agent_id` (`travel_agent_id`),
  ADD KEY `payment_status` (`payment_status`),
  ADD KEY `booking_status` (`booking_status`),
  ADD KEY `synced_to_pms` (`synced_to_pms`),
  ADD KEY `idx_check_in_out` (`check_in`,`check_out`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `online_users`
--
ALTER TABLE `online_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email_status` (`email`,`is_verified`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`pay_id`),
  ADD KEY `emp_id` (`emp_id`);

--
-- Indexes for table `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`code_id`),
  ADD UNIQUE KEY `code_name` (`code_name`);

--
-- Indexes for table `rebates`
--
ALTER TABLE `rebates`
  ADD PRIMARY KEY (`rebate_id`),
  ADD KEY `res_id` (`res_id`),
  ADD KEY `folio_id` (`folio_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`res_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `allotment_id` (`allotment_id`),
  ADD KEY `idx_status_deleted_checkout` (`status`,`is_deleted`,`check_out`),
  ADD KEY `fk_reservations_created_by` (`created_by`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `room_availability`
--
ALTER TABLE `room_availability`
  ADD PRIMARY KEY (`availability_id`),
  ADD UNIQUE KEY `room_date` (`room_id`,`date`),
  ADD KEY `date` (`date`);

--
-- Indexes for table `room_status_history`
--
ALTER TABLE `room_status_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `assigned_staff_id` (`assigned_staff_id`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `schedule_postings`
--
ALTER TABLE `schedule_postings`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `schedule_postings_ibfk_1` (`res_id`);

--
-- Indexes for table `traces`
--
ALTER TABLE `traces`
  ADD PRIMARY KEY (`trace_id`),
  ADD KEY `res_id` (`res_id`);

--
-- Indexes for table `travel_agents`
--
ALTER TABLE `travel_agents`
  ADD PRIMARY KEY (`agent_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `void_postings`
--
ALTER TABLE `void_postings`
  ADD PRIMARY KEY (`void_id`),
  ADD KEY `trans_id` (`trans_id`),
  ADD KEY `folio_id` (`folio_id`);

--
-- Indexes for table `website_settings`
--
ALTER TABLE `website_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agent_commission_log`
--
ALTER TABLE `agent_commission_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `allotments`
--
ALTER TABLE `allotments`
  MODIFY `allotment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `att_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=261;

--
-- AUTO_INCREMENT for table `credit_debit_notes`
--
ALTER TABLE `credit_debit_notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `deposit_refunds`
--
ALTER TABLE `deposit_refunds`
  MODIFY `refund_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `emp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exchange_rates`
--
ALTER TABLE `exchange_rates`
  MODIFY `rate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `exchange_transactions`
--
ALTER TABLE `exchange_transactions`
  MODIFY `exchange_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `folios`
--
ALTER TABLE `folios`
  MODIFY `folio_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `folio_transactions`
--
ALTER TABLE `folio_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT for table `guest_services`
--
ALTER TABLE `guest_services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hotel_settings`
--
ALTER TABLE `hotel_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `housekeeping_staff`
--
ALTER TABLE `housekeeping_staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `laundry_items`
--
ALTER TABLE `laundry_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `laundry_orders`
--
ALTER TABLE `laundry_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `laundry_order_items`
--
ALTER TABLE `laundry_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `nationalities`
--
ALTER TABLE `nationalities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `online_bookings`
--
ALTER TABLE `online_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `online_users`
--
ALTER TABLE `online_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `pay_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `code_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rebates`
--
ALTER TABLE `rebates`
  MODIFY `rebate_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `res_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT for table `room_availability`
--
ALTER TABLE `room_availability`
  MODIFY `availability_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3001;

--
-- AUTO_INCREMENT for table `room_status_history`
--
ALTER TABLE `room_status_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `schedule_postings`
--
ALTER TABLE `schedule_postings`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `traces`
--
ALTER TABLE `traces`
  MODIFY `trace_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `travel_agents`
--
ALTER TABLE `travel_agents`
  MODIFY `agent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `void_postings`
--
ALTER TABLE `void_postings`
  MODIFY `void_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `website_settings`
--
ALTER TABLE `website_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `agent_commission_log`
--
ALTER TABLE `agent_commission_log`
  ADD CONSTRAINT `fk_commission_agent` FOREIGN KEY (`agent_id`) REFERENCES `travel_agents` (`agent_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_commission_booking` FOREIGN KEY (`booking_id`) REFERENCES `online_bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`emp_id`);

--
-- Constraints for table `credit_debit_notes`
--
ALTER TABLE `credit_debit_notes`
  ADD CONSTRAINT `credit_debit_notes_ibfk_1` FOREIGN KEY (`res_id`) REFERENCES `reservations` (`res_id`),
  ADD CONSTRAINT `credit_debit_notes_ibfk_2` FOREIGN KEY (`folio_id`) REFERENCES `folios` (`folio_id`),
  ADD CONSTRAINT `credit_debit_notes_ibfk_3` FOREIGN KEY (`reference_trans_id`) REFERENCES `folio_transactions` (`transaction_id`);

--
-- Constraints for table `deposit_refunds`
--
ALTER TABLE `deposit_refunds`
  ADD CONSTRAINT `deposit_refunds_ibfk_1` FOREIGN KEY (`res_id`) REFERENCES `reservations` (`res_id`),
  ADD CONSTRAINT `deposit_refunds_ibfk_2` FOREIGN KEY (`folio_id`) REFERENCES `folios` (`folio_id`);

--
-- Constraints for table `exchange_transactions`
--
ALTER TABLE `exchange_transactions`
  ADD CONSTRAINT `exchange_transactions_ibfk_1` FOREIGN KEY (`res_id`) REFERENCES `reservations` (`res_id`),
  ADD CONSTRAINT `exchange_transactions_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);

--
-- Constraints for table `folios`
--
ALTER TABLE `folios`
  ADD CONSTRAINT `folios_ibfk_1` FOREIGN KEY (`res_id`) REFERENCES `reservations` (`res_id`) ON DELETE CASCADE;

--
-- Constraints for table `folio_transactions`
--
ALTER TABLE `folio_transactions`
  ADD CONSTRAINT `fk_folio_id` FOREIGN KEY (`folio_id`) REFERENCES `folios` (`folio_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_folio_reservation` FOREIGN KEY (`res_id`) REFERENCES `reservations` (`res_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_folio_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `folio_transactions_ibfk_1` FOREIGN KEY (`res_id`) REFERENCES `reservations` (`res_id`);

--
-- Constraints for table `guest_services`
--
ALTER TABLE `guest_services`
  ADD CONSTRAINT `guest_services_ibfk_1` FOREIGN KEY (`res_id`) REFERENCES `reservations` (`res_id`),
  ADD CONSTRAINT `guest_services_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);

--
-- Constraints for table `laundry_orders`
--
ALTER TABLE `laundry_orders`
  ADD CONSTRAINT `laundry_orders_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `laundry_orders_ibfk_2` FOREIGN KEY (`res_id`) REFERENCES `reservations` (`res_id`) ON DELETE SET NULL;

--
-- Constraints for table `laundry_order_items`
--
ALTER TABLE `laundry_order_items`
  ADD CONSTRAINT `laundry_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `laundry_orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `laundry_order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `laundry_items` (`item_id`);

--
-- Constraints for table `online_bookings`
--
ALTER TABLE `online_bookings`
  ADD CONSTRAINT `fk_online_booking_user` FOREIGN KEY (`user_id`) REFERENCES `online_users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `online_bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`emp_id`);

--
-- Constraints for table `rebates`
--
ALTER TABLE `rebates`
  ADD CONSTRAINT `rebates_ibfk_1` FOREIGN KEY (`res_id`) REFERENCES `reservations` (`res_id`),
  ADD CONSTRAINT `rebates_ibfk_2` FOREIGN KEY (`folio_id`) REFERENCES `folios` (`folio_id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_reservations_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`allotment_id`) REFERENCES `allotments` (`allotment_id`);

--
-- Constraints for table `room_availability`
--
ALTER TABLE `room_availability`
  ADD CONSTRAINT `fk_avail_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE;

--
-- Constraints for table `room_status_history`
--
ALTER TABLE `room_status_history`
  ADD CONSTRAINT `room_status_history_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_status_history_ibfk_2` FOREIGN KEY (`assigned_staff_id`) REFERENCES `housekeeping_staff` (`staff_id`) ON DELETE SET NULL;

--
-- Constraints for table `schedule_postings`
--
ALTER TABLE `schedule_postings`
  ADD CONSTRAINT `schedule_postings_ibfk_1` FOREIGN KEY (`res_id`) REFERENCES `reservations` (`res_id`);

--
-- Constraints for table `traces`
--
ALTER TABLE `traces`
  ADD CONSTRAINT `traces_ibfk_1` FOREIGN KEY (`res_id`) REFERENCES `reservations` (`res_id`);

--
-- Constraints for table `travel_agents`
--
ALTER TABLE `travel_agents`
  ADD CONSTRAINT `fk_agent_user` FOREIGN KEY (`user_id`) REFERENCES `online_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `void_postings`
--
ALTER TABLE `void_postings`
  ADD CONSTRAINT `void_postings_ibfk_1` FOREIGN KEY (`trans_id`) REFERENCES `folio_transactions` (`transaction_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `void_postings_ibfk_2` FOREIGN KEY (`folio_id`) REFERENCES `folios` (`folio_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

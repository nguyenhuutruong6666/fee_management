-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3306
-- Thời gian đã tạo: Th10 01, 2025 lúc 01:09 PM
-- Phiên bản máy phục vụ: 10.4.11-MariaDB
-- Phiên bản PHP: 7.4.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `db_fee_management`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `fee_cashbook`
--

CREATE TABLE `fee_cashbook` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `transaction_type` enum('Thu','Chi') DEFAULT 'Thu',
  `transaction_date` datetime DEFAULT current_timestamp(),
  `amount` decimal(10,2) NOT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `fee_generation_log`
--

CREATE TABLE `fee_generation_log` (
  `id` int(11) NOT NULL,
  `policy_id` int(11) NOT NULL,
  `run_by` int(11) NOT NULL,
  `cycle_label` varchar(50) DEFAULT NULL,
  `total_success` int(11) DEFAULT 0,
  `total_failed` int(11) DEFAULT 0,
  `run_time` datetime DEFAULT current_timestamp(),
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `fee_generation_log`
--

INSERT INTO `fee_generation_log` (`id`, `policy_id`, `run_by`, `cycle_label`, `total_success`, `total_failed`, `run_time`, `note`) VALUES
(1, 1, 1, '01/2025', 36, 0, '2025-11-01 17:10:52', 'Sinh nghĩa vụ đoàn phí kỳ 01/2025 hoàn tất.');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `fee_obligation`
--

CREATE TABLE `fee_obligation` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `policy_id` int(11) NOT NULL,
  `period_label` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('Chưa nộp','Đã nộp','Quá hạn','Miễn giảm') DEFAULT 'Chưa nộp',
  `reference_code` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `fee_obligation`
--

INSERT INTO `fee_obligation` (`id`, `user_id`, `policy_id`, `period_label`, `amount`, `due_date`, `status`, `reference_code`, `created_at`, `updated_at`) VALUES
(1, 7, 1, '01/2025', '500.00', '2025-11-16', 'Chưa nộp', 'DV-123456789-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(2, 22, 1, '01/2025', '500.00', '2025-11-16', 'Chưa nộp', 'DV-100000014-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(3, 32, 1, '01/2025', '500.00', '2025-11-16', 'Chưa nộp', 'DV-100000024-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(4, 33, 1, '01/2025', '500.00', '2025-11-16', 'Chưa nộp', 'DV-100000025-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(5, 3, 1, '01/2025', '1000.00', '2025-11-16', 'Chưa nộp', 'DV-003456789-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(6, 10, 1, '01/2025', '1000.00', '2025-11-16', 'Chưa nộp', 'DV-123456789a-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(7, 20, 1, '01/2025', '1000.00', '2025-11-16', 'Chưa nộp', 'DV-100000012-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(8, 21, 1, '01/2025', '1000.00', '2025-11-16', 'Chưa nộp', 'DV-100000013-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(9, 2, 1, '01/2025', '2000.00', '2025-11-16', 'Chưa nộp', 'DV-002345678-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(10, 23, 1, '01/2025', '2000.00', '2025-11-16', 'Chưa nộp', 'DV-100000015-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(11, 5, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-005678901-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(12, 9, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-7251000000-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(13, 11, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-987654321a-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(14, 12, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000003-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(15, 13, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000004-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(16, 14, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000005-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(17, 15, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000006-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(18, 16, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000007-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(19, 17, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000008-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(20, 18, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000009-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(21, 19, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000010-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(22, 24, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000016-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(23, 25, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000017-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(24, 26, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000018-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(25, 27, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000019-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(26, 28, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000020-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(27, 29, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000021-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(28, 30, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000022-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(29, 34, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000026-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(30, 35, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000027-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(31, 36, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000028-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(32, 37, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000029-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(33, 38, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000030-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(34, 39, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000031-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(35, 40, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000032-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52'),
(36, 41, 1, '01/2025', '3000.00', '2025-11-16', 'Chưa nộp', 'DV-100000033-01/2025', '2025-11-01 17:10:52', '2025-11-01 17:10:52');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `fee_payment`
--

CREATE TABLE `fee_payment` (
  `id` int(11) NOT NULL,
  `obligation_id` int(11) NOT NULL,
  `payer_id` int(11) NOT NULL,
  `collector_id` int(11) DEFAULT NULL,
  `payment_method` varchar(100) NOT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Success','Failed','Need review','Canceled') DEFAULT 'Pending',
  `receipt_id` int(11) DEFAULT NULL,
  `transaction_code` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `fee_payment`
--

INSERT INTO `fee_payment` (`id`, `obligation_id`, `payer_id`, `collector_id`, `payment_method`, `payment_date`, `amount`, `status`, `receipt_id`, `transaction_code`, `note`, `created_at`, `updated_at`) VALUES
(1, 11, 5, NULL, 'VietQR', '2025-11-01 18:39:22', '4000.00', 'Pending', NULL, 'TXN-6905f16ad1e1d', '', '2025-11-01 18:39:22', '2025-11-01 18:58:09');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `fee_policy`
--

CREATE TABLE `fee_policy` (
  `id` int(11) NOT NULL,
  `policy_name` varchar(255) NOT NULL,
  `cycle` varchar(50) NOT NULL,
  `due_day` int(11) DEFAULT 15,
  `due_type` varchar(50) DEFAULT 'tháng',
  `standard_amount` decimal(10,2) NOT NULL,
  `status` enum('Draft','Active','Inactive') DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `fee_policy`
--

INSERT INTO `fee_policy` (`id`, `policy_name`, `cycle`, `due_day`, `due_type`, `standard_amount`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Chính sách đoàn phí 2025 - Kỳ I', 'Tháng', 15, 'tháng', '3000.00', 'Active', 1, '2025-11-01 16:41:35', '2025-11-01 16:41:35'),
(2, 'test', 'Năm', 15, 'năm', '10000000.00', 'Active', 1, '2025-11-01 18:18:41', '2025-11-01 18:24:56');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `fee_policy_history`
--

CREATE TABLE `fee_policy_history` (
  `id` int(11) NOT NULL,
  `policy_id` int(11) NOT NULL,
  `applied_from` date NOT NULL,
  `applied_to` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `fee_policy_history`
--

INSERT INTO `fee_policy_history` (`id`, `policy_id`, `applied_from`, `applied_to`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-01-01', NULL, 1, '2025-11-01 16:41:50', '2025-11-01 16:41:50'),
(2, 2, '2025-11-01', NULL, 0, '2025-11-01 18:18:41', '2025-11-01 18:18:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `fee_policy_rule`
--

CREATE TABLE `fee_policy_rule` (
  `id` int(11) NOT NULL,
  `policy_id` int(11) NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `fee_policy_rule`
--

INSERT INTO `fee_policy_rule` (`id`, `policy_id`, `role_name`, `amount`, `note`, `created_at`, `updated_at`) VALUES
(1, 1, 'BCH Trường', '1000.00', 'Giảm 2000đ/tháng', '2025-11-01 16:42:05', '2025-11-01 16:42:05'),
(2, 1, 'BCH Khoa', '2000.00', 'Giảm 1000đ/tháng', '2025-11-01 16:42:05', '2025-11-01 16:42:05'),
(3, 1, 'BCH Chi đoàn', '2500.00', 'Giảm 500đ/tháng', '2025-11-01 16:42:05', '2025-11-01 16:42:05'),
(4, 2, 'BCH Trường', '1333000.00', NULL, '2025-11-01 18:18:41', '2025-11-01 18:18:41'),
(5, 2, 'BCH Khoa', '233000.00', NULL, '2025-11-01 18:18:41', '2025-11-01 18:18:41'),
(6, 2, 'BCH Chi đoàn', '33333300.00', NULL, '2025-11-01 18:18:41', '2025-11-01 18:18:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `fee_receipt`
--

CREATE TABLE `fee_receipt` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `receipt_code` varchar(100) DEFAULT NULL,
  `issue_date` datetime DEFAULT current_timestamp(),
  `issued_by` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `status` enum('Issued','Canceled') DEFAULT 'Issued',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `organization_units`
--

CREATE TABLE `organization_units` (
  `id` int(11) NOT NULL,
  `unit_name` varchar(255) NOT NULL,
  `unit_level` enum('Truong','Khoa','ChiDoan') NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `leader_id` int(11) DEFAULT NULL,
  `deputy_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `organization_units`
--

INSERT INTO `organization_units` (`id`, `unit_name`, `unit_level`, `parent_id`, `leader_id`, `deputy_id`, `created_at`, `updated_at`) VALUES
(1, 'Trường Đại học Sư phạm Hà Nội', 'Truong', NULL, NULL, NULL, '2025-11-01 05:02:17', '2025-11-01 05:02:17'),
(2, 'Khoa Toán - Tin', 'Khoa', 1, NULL, NULL, '2025-11-01 05:02:17', '2025-11-01 05:02:17'),
(4, 'Chi đoàn K72A1', 'ChiDoan', 2, NULL, NULL, '2025-11-01 05:02:17', '2025-11-01 09:08:55'),
(8, 'Khoa CNTT', 'Khoa', 1, NULL, NULL, '2025-11-01 05:10:05', '2025-11-01 05:17:15'),
(9, 'Chi đoàn K72E4', 'ChiDoan', 8, NULL, NULL, '2025-11-01 05:11:36', '2025-11-01 09:09:46'),
(12, 'Chi đoàn K72E2', 'ChiDoan', 8, NULL, NULL, '2025-11-01 05:16:20', '2025-11-01 09:09:29'),
(13, 'Chi đoàn K72E1', 'ChiDoan', 8, NULL, NULL, '2025-11-01 05:16:27', '2025-11-01 09:09:35'),
(15, 'Chi đoàn K72E3', 'ChiDoan', 8, NULL, NULL, '2025-11-01 09:08:07', '2025-11-01 09:09:40'),
(16, 'Chi đoàn K72A2', 'ChiDoan', 2, NULL, NULL, '2025-11-01 09:09:59', '2025-11-01 09:09:59'),
(17, 'Chi đoàn K72A3', 'ChiDoan', 2, NULL, NULL, '2025-11-01 09:10:04', '2025-11-01 09:10:04'),
(18, 'Chi đoàn K72A4', 'ChiDoan', 2, NULL, NULL, '2025-11-01 09:10:09', '2025-11-01 09:10:09'),
(19, 'Khoa Ngữ Văn', 'Khoa', 1, NULL, NULL, '2025-11-01 09:10:39', '2025-11-01 09:10:39'),
(20, 'Chi đoàn K72B1', 'ChiDoan', 19, NULL, NULL, '2025-11-01 09:11:12', '2025-11-01 09:11:24'),
(21, 'Chi đoàn K72B2', 'ChiDoan', 19, NULL, NULL, '2025-11-01 09:11:17', '2025-11-01 09:11:29'),
(22, 'Chi đoàn K72B3', 'ChiDoan', 19, NULL, NULL, '2025-11-01 09:11:34', '2025-11-01 09:11:34'),
(23, 'Chi đoàn K72B4', 'ChiDoan', 19, NULL, NULL, '2025-11-01 09:11:41', '2025-11-01 09:11:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `role`
--

CREATE TABLE `role` (
  `id` int(11) NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `role`
--

INSERT INTO `role` (`id`, `role_name`, `createdAt`, `updatedAt`) VALUES
(1, 'Quản trị viên', '2025-11-01 09:59:40', '2025-11-01 09:59:40'),
(2, 'BCH Trường', '2025-11-01 09:59:40', '2025-11-01 09:59:40'),
(3, 'BCH Khoa', '2025-11-01 09:59:40', '2025-11-01 09:59:40'),
(4, 'BCH Chi đoàn', '2025-11-01 09:59:40', '2025-11-01 09:59:40'),
(5, 'Đoàn viên', '2025-11-01 09:59:40', '2025-11-01 10:11:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `userId` int(11) NOT NULL,
  `userName` varchar(255) NOT NULL,
  `fullName` varchar(255) DEFAULT NULL,
  `gender` enum('M','F','O') DEFAULT 'O',
  `identifyCard` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `birthDate` date DEFAULT NULL,
  `joinDate` date DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `isAdmin` tinyint(1) DEFAULT 0,
  `createdAt` datetime DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`userId`, `userName`, `fullName`, `gender`, `identifyCard`, `email`, `password`, `birthDate`, `joinDate`, `unit`, `isAdmin`, `createdAt`, `updatedAt`) VALUES
(1, 'admin', 'Admin Hệ thống nha', 'M', '001234567', 'admin@doan.org', '123456', '1990-01-01', '2025-11-23', '1', 1, '2025-11-01 10:00:16', '2025-11-01 13:52:19'),
(2, 'bchtruong', 'Nguyễn Hữu Trường', 'M', '002345678', 'truong@doan.org', '123456', '1998-02-02', '2025-11-23', '1', 0, '2025-11-01 10:00:16', '2025-11-01 12:36:17'),
(3, 'bchkhoa', 'Nguyễn Hữu Trường', 'F', '003456789', 'khoa@doan.org', '123456', '1999-03-03', '2025-11-23', '8', 0, '2025-11-01 10:00:16', '2025-11-01 12:36:43'),
(5, 'doanvien', 'Nguyễn Hữu Trường', 'F', '005678901', 'stu725105179@hnue.edu.vn', '123456', '2002-05-05', '2025-11-23', '4', 0, '2025-11-01 10:00:16', '2025-11-01 12:25:22'),
(7, 'khoa001', 'Nguyen Van Khoa', 'M', '123456789', 'khoa001@hnue.edu.vn', '123456', '2001-05-12', '2018-03-20', '9', 0, '2025-11-01 10:47:06', '2025-11-01 16:06:52'),
(9, 'test', 'Phạm Thị Hồng', 'M', '7251000000', 'echteam04@gmail.com', '123456', '2025-11-07', '2025-11-22', '12', 0, '2025-11-01 13:13:49', '2025-11-01 13:13:49'),
(10, 'sv01', 'Nguyen Van A', 'M', '123456789a', 'sv01@hnue.edu.vn', '123456', '2001-01-01', '2020-03-26', '8', 0, '2025-11-01 13:24:25', '2025-11-01 13:24:25'),
(11, 'sv02', 'Tran Thi B', 'F', '987654321a', 'sv02@hnue.edu.vn', '123456', '2002-04-15', '2021-02-20', '14', 0, '2025-11-01 13:24:25', '2025-11-01 13:24:25'),
(12, 'sv03', 'Le Van C', 'M', '100000003', 'sv03@hnue.edu.vn', '123456', '2001-05-12', '2020-09-01', '13', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(13, 'sv04', 'Pham Thi D', 'F', '100000004', 'sv04@hnue.edu.vn', '123456', '2002-08-20', '2021-09-10', '12', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(14, 'sv05', 'Hoang Van E', 'M', '100000005', 'sv05@hnue.edu.vn', '123456', '2000-12-05', '2019-08-15', '4', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(15, 'sv06', 'Nguyen Thi F', 'F', '100000006', 'sv06@hnue.edu.vn', '123456', '2001-03-30', '2020-11-20', '2', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(16, 'sv07', 'Tran Van G', 'M', '100000007', 'sv07@hnue.edu.vn', '123456', '1999-07-07', '2018-10-01', '8', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(17, 'sv08', 'Le Thi H', 'F', '100000008', 'sv08@hnue.edu.vn', '123456', '2001-09-09', '2020-12-12', '9', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(18, 'sv09', 'Pham Van I', 'M', '100000009', 'sv09@hnue.edu.vn', '123456', '2000-02-14', '2019-03-20', '9', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(19, 'sv10', 'Ho Thi K', 'F', '100000010', 'sv10@hnue.edu.vn', '123456', '2002-06-18', '2021-06-01', '16', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(20, 'khoa002', 'Tran Thi Khoa', 'F', '100000012', 'khoa002@hnue.edu.vn', '123456', '1989-11-20', '2011-04-10', '2', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(21, 'bchkhoa1', 'BCH Khoa 1', 'M', '100000013', 'bchkhoa1@doan.org', '123456', '1985-01-05', '2005-09-01', '2', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(22, 'bchchidoan1', 'BCH ChiDoan 1', 'F', '100000014', 'bchcd1@doan.org', '123456', '1990-02-02', '2015-05-10', '13', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(23, 'bchtruong1', 'BCH Truong 1', 'M', '100000015', 'bchtruong1@doan.org', '123456', '1978-08-08', '2000-01-01', '1', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(24, 'doanvien01', 'Doan Vien 01', 'M', '100000016', 'doanvien01@hnue.edu.vn', '123456', '2000-10-10', '2019-10-10', '12', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(25, 'doanvien02', 'Doan Vien 02', 'F', '100000017', 'doanvien02@hnue.edu.vn', '123456', '2001-02-02', '2020-02-20', '4', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(26, 'sv11', 'Le Thi L', 'F', '100000018', 'sv11@hnue.edu.vn', '123456', '2001-12-12', '2020-07-07', '17', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(27, 'sv12', 'Tran Van M', 'M', '100000019', 'sv12@hnue.edu.vn', '123456', '2002-01-15', '2021-01-20', '18', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(28, 'sv13', 'Pham Thi N', 'F', '100000020', 'sv13@hnue.edu.vn', '123456', '2000-03-03', '2019-05-05', '16', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(29, 'sv14', 'Nguyen Van O', 'M', '100000021', 'sv14@hnue.edu.vn', '123456', '2001-04-04', '2020-04-04', '19', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(30, 'lecturer01', 'Giang Vien 01', 'M', '100000022', 'gv01@hnue.edu.vn', '123456', '1980-09-09', '2008-09-01', '8', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(32, 'officer1', 'Nhan Vien Phi', 'F', '100000024', 'officer1@doan.org', '123456', '1992-07-07', '2016-07-10', '13', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(33, 'officer2', 'Nhan Vien 2', 'M', '100000025', 'officer2@doan.org', '123456', '1991-08-08', '2015-08-15', '15', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(34, 'member21', 'Thanh Vien 21', 'F', '100000026', 'member21@hnue.edu.vn', '123456', '2002-09-09', '2021-09-09', '12', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(35, 'member22', 'Thanh Vien 22', 'M', '100000027', 'member22@hnue.edu.vn', '123456', '2001-11-11', '2020-11-11', '13', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(36, 'member23', 'Thanh Vien 23', 'F', '100000028', 'member23@hnue.edu.vn', '123456', '2002-12-12', '2021-12-12', '9', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(37, 'member24', 'Thanh Vien 24', 'M', '100000029', 'member24@hnue.edu.vn', '123456', '2000-05-05', '2019-05-05', '15', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(38, 'member25', 'Thanh Vien 25', 'F', '100000030', 'member25@hnue.edu.vn', '123456', '2001-06-06', '2020-06-06', '13', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(39, 'member26', 'Thanh Vien 26', 'M', '100000031', 'member26@hnue.edu.vn', '123456', '2002-07-07', '2021-07-07', '4', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(40, 'member27', 'Thanh Vien 27', 'F', '100000032', 'member27@hnue.edu.vn', '123456', '2001-08-08', '2020-08-08', '17', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(41, 'member28', 'Thanh Vien 28', 'M', '100000033', 'member28@hnue.edu.vn', '123456', '2000-09-09', '2019-09-09', '18', 0, '2025-11-01 16:21:09', '2025-11-01 16:21:09');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_role`
--

CREATE TABLE `user_role` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `user_role`
--

INSERT INTO `user_role` (`id`, `user_id`, `role_id`, `createdAt`, `updatedAt`) VALUES
(1, 1, 1, '2025-11-01 10:01:23', '2025-11-01 10:01:23'),
(2, 2, 2, '2025-11-01 10:01:23', '2025-11-01 10:01:23'),
(3, 3, 3, '2025-11-01 10:01:23', '2025-11-01 10:01:23'),
(5, 5, 5, '2025-11-01 10:01:23', '2025-11-01 10:01:23'),
(7, 7, 4, '2025-11-01 10:47:06', '2025-11-01 12:48:05'),
(9, 9, 5, '2025-11-01 13:13:49', '2025-11-01 13:13:49'),
(10, 10, 3, '2025-11-01 13:24:25', '2025-11-01 13:24:25'),
(11, 11, 5, '2025-11-01 13:24:25', '2025-11-01 13:24:25'),
(12, 12, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(13, 13, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(14, 14, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(15, 15, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(16, 16, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(17, 17, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(18, 18, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(19, 19, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(20, 20, 3, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(21, 21, 3, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(22, 22, 4, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(23, 23, 2, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(24, 24, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(25, 25, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(26, 26, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(27, 27, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(28, 28, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(29, 29, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(30, 30, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(32, 32, 4, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(33, 33, 4, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(34, 34, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(35, 35, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(36, 36, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(37, 37, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(38, 38, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(39, 39, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(40, 40, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09'),
(41, 41, 5, '2025-11-01 16:21:09', '2025-11-01 16:21:09');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `fee_cashbook`
--
ALTER TABLE `fee_cashbook`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cashbook_payment` (`payment_id`),
  ADD KEY `fk_cashbook_user` (`recorded_by`);

--
-- Chỉ mục cho bảng `fee_generation_log`
--
ALTER TABLE `fee_generation_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_policy` (`policy_id`);

--
-- Chỉ mục cho bảng `fee_obligation`
--
ALTER TABLE `fee_obligation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_code` (`reference_code`),
  ADD KEY `fk_obligation_user` (`user_id`),
  ADD KEY `fk_obligation_policy` (`policy_id`);

--
-- Chỉ mục cho bảng `fee_payment`
--
ALTER TABLE `fee_payment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_code` (`transaction_code`),
  ADD KEY `fk_payment_obligation` (`obligation_id`),
  ADD KEY `fk_payment_payer` (`payer_id`),
  ADD KEY `fk_payment_collector` (`collector_id`);

--
-- Chỉ mục cho bảng `fee_policy`
--
ALTER TABLE `fee_policy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_policy_creator` (`created_by`);

--
-- Chỉ mục cho bảng `fee_policy_history`
--
ALTER TABLE `fee_policy_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_history_policy` (`policy_id`);

--
-- Chỉ mục cho bảng `fee_policy_rule`
--
ALTER TABLE `fee_policy_rule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rule_policy` (`policy_id`);

--
-- Chỉ mục cho bảng `fee_receipt`
--
ALTER TABLE `fee_receipt`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_code` (`receipt_code`),
  ADD KEY `fk_receipt_payment` (`payment_id`),
  ADD KEY `fk_receipt_user` (`issued_by`);

--
-- Chỉ mục cho bảng `organization_units`
--
ALTER TABLE `organization_units`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_parent_unit` (`parent_id`),
  ADD KEY `fk_leader` (`leader_id`),
  ADD KEY `fk_deputy` (`deputy_id`);

--
-- Chỉ mục cho bảng `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userId`),
  ADD UNIQUE KEY `userName` (`userName`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `user_role`
--
ALTER TABLE `user_role`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `fee_cashbook`
--
ALTER TABLE `fee_cashbook`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `fee_generation_log`
--
ALTER TABLE `fee_generation_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `fee_obligation`
--
ALTER TABLE `fee_obligation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT cho bảng `fee_payment`
--
ALTER TABLE `fee_payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `fee_policy`
--
ALTER TABLE `fee_policy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `fee_policy_history`
--
ALTER TABLE `fee_policy_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `fee_policy_rule`
--
ALTER TABLE `fee_policy_rule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `fee_receipt`
--
ALTER TABLE `fee_receipt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `organization_units`
--
ALTER TABLE `organization_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT cho bảng `role`
--
ALTER TABLE `role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT cho bảng `user_role`
--
ALTER TABLE `user_role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `fee_cashbook`
--
ALTER TABLE `fee_cashbook`
  ADD CONSTRAINT `fk_cashbook_payment` FOREIGN KEY (`payment_id`) REFERENCES `fee_payment` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cashbook_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`userId`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `fee_generation_log`
--
ALTER TABLE `fee_generation_log`
  ADD CONSTRAINT `fk_log_policy` FOREIGN KEY (`policy_id`) REFERENCES `fee_policy` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `fee_obligation`
--
ALTER TABLE `fee_obligation`
  ADD CONSTRAINT `fk_obligation_policy` FOREIGN KEY (`policy_id`) REFERENCES `fee_policy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_obligation_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `fee_payment`
--
ALTER TABLE `fee_payment`
  ADD CONSTRAINT `fk_payment_collector` FOREIGN KEY (`collector_id`) REFERENCES `users` (`userId`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payment_obligation` FOREIGN KEY (`obligation_id`) REFERENCES `fee_obligation` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payment_payer` FOREIGN KEY (`payer_id`) REFERENCES `users` (`userId`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `fee_policy`
--
ALTER TABLE `fee_policy`
  ADD CONSTRAINT `fk_policy_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`userId`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `fee_policy_history`
--
ALTER TABLE `fee_policy_history`
  ADD CONSTRAINT `fk_history_policy` FOREIGN KEY (`policy_id`) REFERENCES `fee_policy` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `fee_policy_rule`
--
ALTER TABLE `fee_policy_rule`
  ADD CONSTRAINT `fk_rule_policy` FOREIGN KEY (`policy_id`) REFERENCES `fee_policy` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `fee_receipt`
--
ALTER TABLE `fee_receipt`
  ADD CONSTRAINT `fk_receipt_payment` FOREIGN KEY (`payment_id`) REFERENCES `fee_payment` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_receipt_user` FOREIGN KEY (`issued_by`) REFERENCES `users` (`userId`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `organization_units`
--
ALTER TABLE `organization_units`
  ADD CONSTRAINT `fk_deputy` FOREIGN KEY (`deputy_id`) REFERENCES `users` (`userId`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_leader` FOREIGN KEY (`leader_id`) REFERENCES `users` (`userId`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_parent_unit` FOREIGN KEY (`parent_id`) REFERENCES `organization_units` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `user_role`
--
ALTER TABLE `user_role`
  ADD CONSTRAINT `user_role_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_role_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

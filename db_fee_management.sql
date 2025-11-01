-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3306
-- Thời gian đã tạo: Th10 01, 2025 lúc 09:14 AM
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
(4, 'Chi đoàn Tin học 1', 'ChiDoan', 2, NULL, NULL, '2025-11-01 05:02:17', '2025-11-01 05:02:17'),
(8, 'Khoa CNTT', 'Khoa', 1, NULL, NULL, '2025-11-01 05:10:05', '2025-11-01 05:17:15'),
(9, 'Chi đoàn k72E4', 'ChiDoan', 8, NULL, NULL, '2025-11-01 05:11:36', '2025-11-01 05:11:36'),
(12, 'Chi đoàn k72E2', 'ChiDoan', 8, NULL, NULL, '2025-11-01 05:16:20', '2025-11-01 05:16:41'),
(13, 'Chi đoàn k72E1', 'ChiDoan', 8, NULL, NULL, '2025-11-01 05:16:27', '2025-11-01 05:16:27'),
(14, 'Chi đoàn k72E3', 'ChiDoan', 8, NULL, NULL, '2025-11-01 05:16:35', '2025-11-01 05:16:35');

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
(7, 'khoa001', 'Nguyen Van Khoa', 'M', '123456789', 'khoa001@hnue.edu.vn', '123456', '2001-05-12', '2018-03-20', '14', 0, '2025-11-01 10:47:06', '2025-11-01 13:08:03'),
(9, 'test', 'Phạm Thị Hồng', 'M', '7251000000', 'echteam04@gmail.com', '123456', '2025-11-07', '2025-11-22', '12', 0, '2025-11-01 13:13:49', '2025-11-01 13:13:49'),
(10, 'sv01', 'Nguyen Van A', 'M', '123456789a', 'sv01@hnue.edu.vn', '123456', '2001-01-01', '2020-03-26', '8', 0, '2025-11-01 13:24:25', '2025-11-01 13:24:25'),
(11, 'sv02', 'Tran Thi B', 'F', '987654321a', 'sv02@hnue.edu.vn', '123456', '2002-04-15', '2021-02-20', '14', 0, '2025-11-01 13:24:25', '2025-11-01 13:24:25');

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
(11, 11, 5, '2025-11-01 13:24:25', '2025-11-01 13:24:25');

--
-- Chỉ mục cho các bảng đã đổ
--

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
-- AUTO_INCREMENT cho bảng `organization_units`
--
ALTER TABLE `organization_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `role`
--
ALTER TABLE `role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `user_role`
--
ALTER TABLE `user_role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Các ràng buộc cho các bảng đã đổ
--

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

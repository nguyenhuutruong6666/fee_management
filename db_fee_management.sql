-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3306
-- Thời gian đã tạo: Th10 31, 2025 lúc 07:17 PM
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
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `userId` int(11) NOT NULL,
  `userName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('Admin','BCH_Truong','BCH_Khoa','BCH_ChiDoan','DoanVien') COLLATE utf8mb4_unicode_ci DEFAULT 'DoanVien',
  `fullName` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('M','F','O') COLLATE utf8mb4_unicode_ci DEFAULT 'O' COMMENT 'M: Nam, F: Nữ, O: Khác',
  `birthDate` date DEFAULT NULL,
  `joinDate` date DEFAULT NULL COMMENT 'Ngày vào Đoàn',
  `unit` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tên đơn vị: Chi đoàn / Khoa / Trường',
  `isAdmin` tinyint(1) DEFAULT 0,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`userId`, `userName`, `password`, `role`, `fullName`, `email`, `gender`, `birthDate`, `joinDate`, `unit`, `isAdmin`, `createdAt`, `updatedAt`) VALUES
(1, 'admin', '123456', 'Admin', 'Nguyễn Quản Trị', 'admin@doan.vn', 'F', '1990-01-28', '2010-03-26', 'Trường a', 1, '2025-10-31 18:02:39', '2025-10-31 18:07:37'),
(2, 'bchtruong', '123456', 'BCH_Truong', 'Trần Thị Trường', 'truong@doan.vn', 'F', '1988-05-12', '2012-03-26', 'BCH Trường', 0, '2025-10-31 18:02:39', '2025-10-31 18:02:39'),
(3, 'bchkhoa', '123456', 'BCH_Khoa', 'Ngô Văn Khoa', 'khoa@doan.vn', 'M', '1995-07-15', '2013-03-26', 'Khoa CNTT', 0, '2025-10-31 18:02:39', '2025-10-31 18:02:39'),
(4, 'bchcd', '123456', 'BCH_ChiDoan', 'Lê Minh Chi Đoàn', 'chidoan@doan.vn', 'M', '2000-09-20', '2016-03-26', 'Chi đoàn A1', 0, '2025-10-31 18:02:39', '2025-10-31 18:02:39'),
(5, 'doanvien', '123456', 'DoanVien', 'Phạm Thị Hồng', 'stu725105179@hnue.edu.vn', 'O', '2002-02-18', '2018-03-31', 'Chi đoàn A1', 0, '2025-10-31 18:02:39', '2025-10-31 18:14:42');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userId`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

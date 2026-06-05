-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 05, 2026 lúc 10:15 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `lab4`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `order_id` varchar(50) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `address` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `payment` varchar(50) NOT NULL,
  `total` int(11) NOT NULL,
  `status` varchar(50) DEFAULT 'Đã đặt',
  `created_at` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`order_id`, `fullname`, `phone`, `email`, `address`, `note`, `payment`, `total`, `status`, `created_at`) VALUES
('NM563269', 'quân lac', '0869742820', 'df@gmail.com', 'cdfvgbhnjmgf', 'dcfvgbhnjhgf', 'cod', 250000, 'Đang giao', '5/6/2026'),
('NM648468', 'ádsfsf', '0869734820', 'scdfvgb@gmail.com', 'sdcfvgbhjkhg', 'sdafghjgfd', 'cod', 250000, 'Đã đặt', '5/6/2026');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

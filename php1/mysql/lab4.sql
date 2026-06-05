-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 05, 2026 lúc 10:27 AM
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

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` varchar(50) NOT NULL,
  `product_id` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `description`) VALUES
(1, 'NM648468', '05555', 1, 250000, 'mạcna'),
(2, 'NM563269', '04444', 1, 250000, 'ầ');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `product_id` varchar(20) NOT NULL,
  `description` text NOT NULL,
  `price` varchar(100) NOT NULL,
  `image` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`product_id`, `description`, `price`, `image`) VALUES
('01111', 'cvsv', '250.000', 'uploads/1779869612_cach-cai-hinh-nen-may-tinh-khong-bi-mo_1280x720-800-resize.jpg'),
('04444', 'ầ', '250.000', 'uploads/1779869493_cach-cai-hinh-nen-may-tinh-khong-bi-mo_1280x720-800-resize.jpg'),
('05555', 'mạcna', '250.000', 'uploads/1779868125_cach-cai-hinh-nen-may-tinh-khong-bi-mo_1280x720-800-resize.jpg'),
('06666', 'sdvs', '250.000', 'uploads/1779868158_cach-cai-hinh-nen-may-tinh-khong-bi-mo_1280x720-800-resize.jpg'),
('0999', 'zxcvcsz', '250.000', 'uploads/1779869600_cach-cai-hinh-nen-may-tinh-khong-bi-mo_1280x720-800-resize.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `username`, `password`, `reset_token`, `reset_expires`, `created_at`) VALUES
(1, 'Quân', 'ebanlmqpk04165@gmail.com', 'ebanlmqpk04165', '$2y$10$fyc3cStsJvUF58.XJefw0uscPda7vKJy.2jbqOZMxd/ZfLnCbKAAK', NULL, NULL, '2026-05-29 07:38:31');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

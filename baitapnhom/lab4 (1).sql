-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 12, 2026 at 06:29 AM
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
-- Database: `lab4`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `address` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `payment` varchar(50) NOT NULL,
  `total` int(11) NOT NULL,
  `status` varchar(50) DEFAULT 'Đã đặt',
  `cancel_reason` text DEFAULT NULL,
  `created_at` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `fullname`, `phone`, `email`, `address`, `note`, `payment`, `total`, `status`, `cancel_reason`, `created_at`) VALUES
('DH507860', NULL, 'dsfg', '0942553130', 'abc@gmail.com', '7 nguyễn tất thành', 'vgfdh', 'cod', 500000, 'Hoàn tất', NULL, '8/6/2026'),
('DH692396', NULL, 'an', '0942553130', 'abc@gmail.com', '7 nguyễn tất thành', 'd', 'cod', 500000, 'Hoàn tất', NULL, '7/6/2026'),
('NM416386', NULL, 'an', '0942553130', 'abc@gmail.com', '7 nguyễn tất thành', 'abc', 'cod', 500000, 'Hoàn tất', NULL, '6/6/2026'),
('NM563269', NULL, 'quân lac', '0869742820', 'df@gmail.com', 'cdfvgbhnjmgf', 'dcfvgbhnjhgf', 'cod', 250000, 'Hoàn tất', NULL, '5/6/2026'),
('NM648468', NULL, 'ádsfsf', '0869734820', 'scdfvgb@gmail.com', 'sdcfvgbhjkhg', 'sdafghjgfd', 'cod', 250000, 'Đã hủy', NULL, '5/6/2026'),
('NM749911', NULL, 'an', '0942553130', 'abc@gmail.com', '7 nguyễn tất thành', 'abc', 'momo', 1000000, 'Đã hủy', NULL, '5/6/2026'),
('NM799695', NULL, 'an', '0942553130', 'abc@gmail.com', '7 nguyễn tất thành', 'qe', 'cod', 250000, 'Đã hủy', NULL, '7/6/2026'),
('NM813135', NULL, 'tx', '0942553130', 'abc@gmail.com', '7 nguyễn tất thành', 'xgv', 'cod', 250000, 'Đã hủy', NULL, '8/6/2026'),
('NM871030', 2, 'an', '0942553130', 'abc@gmail.com', '7 nguyễn tất thành', 'à', 'cod', 750000, 'Đã đặt', NULL, '10/6/2026'),
('NM925438', NULL, 'abc', '0942553130', 'abc@gmail.com', '7 nguyễn tất thành', 'cb', 'cod', 500000, 'Hoàn tất', NULL, '8/6/2026');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
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
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `description`) VALUES
(1, 'NM648468', '05555', 1, 250000, 'mạcna'),
(2, 'NM563269', '04444', 1, 250000, 'ầ'),
(4, 'NM749911', '0999', 3, 250000, 'zxcvcsz'),
(5, 'NM749911', '06666', 1, 250000, 'sdvs'),
(6, 'NM416386', '0999', 1, 250000, 'zxcvcsz'),
(7, 'NM416386', '06666', 1, 250000, 'sdvs'),
(8, 'NM799695', '01111', 1, 250000, 'cvsv'),
(9, 'DH692396', '0999', 1, 250000, 'zxcvcsz'),
(10, 'DH692396', '06666', 1, 250000, 'sdvs'),
(11, 'NM925438', 'vs01', 1, 500000, 'abc'),
(12, 'NM813135', '0999', 1, 250000, 'zxcvcsz'),
(13, 'DH507860', '0999', 1, 250000, 'zxcvcsz'),
(14, 'DH507860', '06666', 1, 250000, 'sdvs'),
(15, 'NM871030', 'vs01', 1, 500000, 'abc'),
(16, 'NM871030', '06666', 1, 250000, 'sdvs');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` varchar(20) NOT NULL,
  `description` text NOT NULL,
  `price` varchar(100) NOT NULL,
  `image` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `description`, `price`, `image`) VALUES
('01111', 'cvsv', '250.000', 'uploads/1780648705_ĐTNN.png'),
('05555', 'abcd', '250.000', 'uploads/1780648678_ủa.jpg'),
('06666', 'sdvs', '250.000', 'uploads/1780648669_FOC.jpg'),
('0999', 'zxcvcsz', '250.000', 'uploads/1780648661_anh nen.jpg'),
('SP003e', 'abc', '250.000', 'uploads/1781238485_sitemap.png'),
('vs01', 'abc', '500.000', 'uploads/1780650027_banh_xe_cuoc_doi.png');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'user',
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `username`, `password`, `role`, `reset_token`, `reset_expires`, `created_at`) VALUES
(1, 'Quân', 'ebanlmqpk04165@gmail.com', 'ebanlmqpk04165', '$2y$10$fyc3cStsJvUF58.XJefw0uscPda7vKJy.2jbqOZMxd/ZfLnCbKAAK', 'admin', NULL, NULL, '2026-05-29 07:38:31'),
(2, 'an', 'dinhan27107@gmail.com', 'admin', '$2y$10$nqqAfeqE.HikiNhNWew5v.pEAuiSmj8PTVhpmlvPHkYrtDjGeB8ii', 'user', NULL, NULL, '2026-06-10 08:42:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_orders_users` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

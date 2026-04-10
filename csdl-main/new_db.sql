-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
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
-- Cơ sở dữ liệu: `new_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart`
--

CREATE TABLE `cart` (
  `cartID` bigint(20) UNSIGNED NOT NULL,
  `userID` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `cart`
--

INSERT INTO `cart` (`cartID`, `userID`, `created_at`, `updated_at`) VALUES
(4, 8, '2025-11-19 02:59:52', '2025-11-19 02:59:52'),
(6, 18, '2025-11-22 11:02:28', '2025-11-22 11:02:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart_details`
--

CREATE TABLE `cart_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cartID` bigint(20) UNSIGNED NOT NULL,
  `productDetailID` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `cart_details`
--

INSERT INTO `cart_details` (`id`, `cartID`, `productDetailID`, `quantity`, `created_at`, `updated_at`) VALUES
(89, 4, 3, 1, '2025-11-23 13:08:11', '2025-11-23 13:08:11');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `categoryID` bigint(20) UNSIGNED NOT NULL,
  `categoryName` varchar(255) NOT NULL,
  `categoryImage` varchar(255) DEFAULT NULL,
  `categoryDesc` text DEFAULT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`categoryID`, `categoryName`, `categoryImage`, `categoryDesc`, `isDeleted`, `created_at`, `updated_at`) VALUES
(1, 'Áo Thun', 'categories/lNFAW0IWvPCLrQ41TYd2lpdYQCpTmleZehohS8GK.png', 'Áo thun dễ phối và phù hợp với nhiều phong cách thời trang.', 0, '2025-06-07 18:56:46', '2025-07-02 08:22:32'),
(2, 'Quần Jeans', 'categories/nZkZ4lgSKo8F6qkwRkpjsqOos0VTAtPxW6WDAHQ5.png', 'Quần jeans mang phong cách trẻ trung, năng động với các form dáng slimfit, straight hoặc baggy', 0, '2025-06-07 18:57:00', '2025-07-02 08:23:44'),
(3, 'Áo Hoodie', 'categories/pYxqUz1gq7Bq9FOQRORSIRgDun1gcdO7jD38dbSN.jpg', 'Áo hoodie form rộng, có nón và dây rút điều chỉnh, phù hợp cho thời tiết se lạnh.', 0, '2025-06-09 09:07:51', '2025-07-02 08:23:03'),
(4, 'Áo Gile', 'categories/5KG9vKRbGisWQ44Nud6zaRw2G3Q4DBuzR4R2iuul.jpg', 'Áo gile thiết kế gọn nhẹ, thường được mặc ngoài áo sơ mi hoặc áo thun dài tay', 0, '2025-06-09 20:59:51', '2025-07-02 08:23:33'),
(5, 'Quần Short', 'categories/x4QmwlV2LaqHZT323A8RHZNwzHSq9mhPmPZXexmP.jpg', 'Quần short có chiều dài trên gối, thiết kế đơn giản hoặc được thêu logo tinh tế.', 0, '2025-06-23 22:42:27', '2025-07-02 08:23:13'),
(8, 'Áo Khoác', 'categories/c12G5sJmtGpy2SnqWW3P81BA2qQoOf5JFoudn1F1.jpg', 'Áo Khoác mỏng nhẹ, ấm', 0, '2025-11-23 13:17:19', '2025-11-23 13:17:19');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `colors`
--

CREATE TABLE `colors` (
  `colorId` bigint(20) UNSIGNED NOT NULL,
  `colorName` varchar(255) NOT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `colors`
--

INSERT INTO `colors` (`colorId`, `colorName`, `isDeleted`, `created_at`, `updated_at`) VALUES
(1, 'Đỏ', 0, NULL, NULL),
(2, 'Xanh nước', 0, NULL, NULL),
(3, 'Hồng', 0, NULL, NULL),
(4, 'Tím ', 0, NULL, NULL),
(5, 'Đen', 0, NULL, NULL),
(6, 'Trắng', 0, NULL, NULL),
(7, 'Xanh lá', 0, NULL, NULL),
(8, 'Cam', 0, NULL, NULL),
(9, 'Be', 0, NULL, NULL),
(10, 'Đỏ', 0, NULL, NULL),
(11, 'Xanh nước', 0, NULL, NULL),
(12, 'Hồng', 0, NULL, NULL),
(13, 'Tím ', 0, NULL, NULL),
(14, 'Đen', 0, NULL, NULL),
(15, 'Trắng', 0, NULL, NULL),
(16, 'Xanh lá', 0, NULL, NULL),
(17, 'Cam', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `comment_and_rate`
--

CREATE TABLE `comment_and_rate` (
  `idComment` bigint(20) UNSIGNED NOT NULL,
  `cusID` bigint(20) UNSIGNED NOT NULL,
  `productID` bigint(20) UNSIGNED NOT NULL,
  `contentComment` text NOT NULL,
  `rate` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `discount_programs`
--

CREATE TABLE `discount_programs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percent','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `discount_programs`
--

INSERT INTO `discount_programs` (`id`, `name`, `description`, `discount_type`, `discount_value`, `max_discount`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(4, 'Black Firday', 'Sale 30%', 'fixed', 200000.00, 250000.00, '2025-11-22 00:00:00', '2025-11-27 00:00:00', '2025-11-23 13:07:37', '2025-11-23 13:09:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2025_05_10_075915_create_status_table', 1),
(2, '2025_05_24_105227_create_sizes_table', 2),
(3, '2025_05_24_105244_create_colors_table', 3),
(4, '2025_05_10_075853_create_payment_table', 4),
(5, '0001_01_01_000000_create_users_table', 5),
(6, '0001_01_01_000001_create_cache_table', 5),
(7, '0001_01_01_000002_create_jobs_table', 5),
(8, '2025_05_10_073845_create_categories_table', 5),
(9, '2025_05_10_073938_create_products_table', 5),
(10, '2025_05_10_074028_create_product_images_table', 5),
(11, '2025_05_10_074221_create_cart_table', 5),
(12, '2025_05_24_105257_create_product_details_table', 6),
(13, '2025_05_10_075608_create_cart_details_table', 7),
(14, '2025_05_10_075643_create_orders_table', 7),
(15, '2025_05_10_075833_create_order_details_table', 8),
(16, '2025_05_10_080008_create_discount_programs_table', 8),
(17, '2025_06_12_053339_add_shipping_code_to_orders_table', 9),
(18, '2025_06_12_140803_create_comment_and_rate_table', 10),
(19, '2025_07_02_092427_add_discount_program_id_to_orders_table', 11),
(20, '2025_07_02_171508_remove_productid_from_cart_details_table', 12);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `orderID` bigint(20) UNSIGNED NOT NULL,
  `cusID` bigint(20) UNSIGNED NOT NULL,
  `adminID` bigint(20) UNSIGNED DEFAULT NULL,
  `payID` bigint(20) UNSIGNED NOT NULL,
  `staID` bigint(20) UNSIGNED NOT NULL,
  `discount_program_id` bigint(20) UNSIGNED DEFAULT NULL,
  `orderPhoneNumber` varchar(255) DEFAULT NULL,
  `shipping_street` varchar(255) DEFAULT NULL,
  `shipping_ward` varchar(255) DEFAULT NULL,
  `shipping_district` varchar(255) DEFAULT NULL,
  `shipping_city` varchar(255) DEFAULT NULL,
  `totalPrice` decimal(12,2) NOT NULL DEFAULT 0.00,
  `isPayment` tinyint(1) NOT NULL DEFAULT 0,
  `shipping_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`orderID`, `cusID`, `adminID`, `payID`, `staID`, `discount_program_id`, `orderPhoneNumber`, `shipping_street`, `shipping_ward`, `shipping_district`, `shipping_city`, `totalPrice`, `isPayment`, `shipping_code`, `created_at`, `updated_at`) VALUES
(95, 8, NULL, 1, 4, NULL, '0335244686', 'T.p Lào Cai', 'Lào Cai', 'Lào Cai', 'Lào Cai', 300000.00, 1, 'SPXVN05044243730B', '2025-11-17 11:08:25', '2025-11-17 11:08:50'),
(96, 8, NULL, 1, 5, NULL, '0335244686', 'T.p Lào Cai', 'Lào Cai', 'Lào Cai', 'Lào Cai', 300000.00, 0, NULL, '2025-11-19 03:00:02', '2025-11-22 11:30:08'),
(105, 18, NULL, 1, 5, NULL, '0335244686', 'T.p Lào Cai', 'Lào Cai', 'Lào Cai', 'Lào Cai', 450000.00, 0, NULL, '2025-11-22 11:03:39', '2025-11-22 11:30:06'),
(106, 18, NULL, 1, 5, NULL, '0335244686', 'T.p Lào Cai', 'Lào Cai', 'Lào Cai', 'Lào Cai', 450000.00, 0, NULL, '2025-11-22 11:03:46', '2025-11-22 11:30:04'),
(107, 8, NULL, 1, 5, NULL, '0335244686', 'T.p Lào Cai', 'Lào Cai', 'Lào Cai', 'Lào Cai', 450000.00, 0, NULL, '2025-11-22 11:29:39', '2025-11-22 11:30:03'),
(108, 8, NULL, 1, 4, NULL, '0335244686', 'T.p Lào Cai', 'Lào Cai', 'Lào Cai', 'Lào Cai', 300000.00, 1, 'SPXVN05044243730B', '2025-11-23 12:58:32', '2025-11-23 23:20:08');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_details`
--

CREATE TABLE `order_details` (
  `orderID` bigint(20) UNSIGNED NOT NULL,
  `productDetailID` bigint(20) UNSIGNED NOT NULL,
  `orderQuantity` int(11) NOT NULL,
  `unitPrice` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_details`
--

INSERT INTO `order_details` (`orderID`, `productDetailID`, `orderQuantity`, `unitPrice`, `created_at`, `updated_at`) VALUES
(95, 3, 1, 300000.00, '2025-11-17 11:08:25', '2025-11-17 11:08:25'),
(96, 4, 1, 300000.00, '2025-11-19 03:00:02', '2025-11-19 03:00:02'),
(105, 10, 1, 450000.00, '2025-11-22 11:03:39', '2025-11-22 11:03:39'),
(106, 10, 1, 450000.00, '2025-11-22 11:03:46', '2025-11-22 11:03:46'),
(107, 10, 1, 450000.00, '2025-11-22 11:29:39', '2025-11-22 11:29:39'),
(108, 3, 1, 300000.00, '2025-11-23 12:58:32', '2025-11-23 12:58:32');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payments`
--

CREATE TABLE `payments` (
  `paymentID` bigint(20) UNSIGNED NOT NULL,
  `payMethod` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `payments`
--

INSERT INTO `payments` (`paymentID`, `payMethod`, `created_at`, `updated_at`) VALUES
(1, 'Thanh toán khi nhận hàng (COD)', NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `productID` bigint(20) UNSIGNED NOT NULL,
  `productCode` varchar(255) NOT NULL,
  `productName` varchar(255) NOT NULL,
  `productBuyPrice` decimal(12,2) NOT NULL,
  `productSellPrice` decimal(12,2) NOT NULL,
  `productForGender` tinyint(1) NOT NULL,
  `productDesc` text NOT NULL,
  `cateID` bigint(20) UNSIGNED NOT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`productID`, `productCode`, `productName`, `productBuyPrice`, `productSellPrice`, `productForGender`, `productDesc`, `cateID`, `isDeleted`, `created_at`, `updated_at`) VALUES
(1, 'AT0006', 'Áo Thun In Chữ Wave To The Beach Form Relax AT174 Màu Trắng', 200000.00, 300000.00, 0, 'Mẫu áo thun với thiết kế trẻ trung, in chữ và họa tiết sóng biển độc đáo, mang phong cách năng động và phóng khoáng. Form áo relax rộng rãi giúp thoải mái khi mặc, phù hợp cho các chuyến đi chơi, dạo phố hoặc mặc hằng ngày.', 1, 0, '2025-06-07 18:58:45', '2025-07-02 06:11:20'),
(2, 'AH0002', 'Áo Hoodie Phối Bo Sọc Thêu Logo Sọc Ở Cổ Tay Form Regular AH007 Màu Be', 200000.00, 300000.00, 0, 'New', 3, 0, '2025-06-09 09:09:42', '2025-07-02 06:08:00'),
(3, 'QJ0003', 'Quần Jean Thêu Chữ M Form Slimfit QJ111 Màu Xanh', 300000.00, 436000.00, 0, 'Quần jean nam form slimfit ôm gọn cơ thể, tôn dáng nhưng vẫn dễ vận động. Thiết kế thêu chữ \"M\" tạo điểm nhấn trẻ trung. Màu xanh denim cổ điển dễ phối đồ với áo thun, sơ mi hay hoodie.', 2, 0, '2025-06-09 20:43:35', '2025-07-02 06:12:20'),
(4, 'AG0002', 'Áo Ghile Thun Caro Thêu Logo Form Regular AG001 Màu Đen', 300000.00, 450000.00, 1, 'New', 4, 0, '2025-06-09 21:00:20', '2025-07-02 06:08:07'),
(5, 'QS0007', 'Quần Short Trang Trí Đường Diễu 2 Bên Túi Hộp Form Straight QS073 Màu Be Đậm', 150000.00, 200000.00, 0, 'Mẫu quần short kaki năng động, thiết kế túi hộp thực dụng và đường chỉ diễu nổi giúp tổng thể khỏe khoắn, đậm chất thời trang đường phố. Form straight (ống suông) phù hợp với nhiều dáng người, thích hợp đi chơi, dạo phố, du lịch.', 5, 0, '2025-06-23 22:40:02', '2025-11-23 20:51:59'),
(6, 'AT0007', 'Áo Thun Wash Loang In Chữ Hopeless Dream Form Regular AT172 Màu Be', 200000.00, 300000.00, 0, 'Chiếc áo thun với tông màu loang be nhẹ nhàng kết hợp cùng dòng chữ nghệ thuật \"Hopeless Dream\" tạo điểm nhấn tinh tế. Chất liệu cotton thoáng mát, form regular ôm nhẹ cơ thể nhưng không quá bó, phù hợp với cả nam và nữ theo phong cách tối giản.', 1, 0, '2025-06-23 22:41:35', '2025-07-02 06:12:08'),
(7, 'QS0003', 'Quần Short Jeans Thêu Classic Preppy Form Straight QS061 Màu Be', 315000.00, 415000.00, 0, 'Mang phong cách preppy cổ điển kết hợp với chất liệu jeans cao cấp, mẫu quần short QS061 màu be là lựa chọn hoàn hảo cho những ai yêu thích sự đơn giản, thanh lịch nhưng vẫn cá tính.', 5, 0, '2025-07-02 06:19:10', '2025-07-02 06:19:10'),
(8, 'QS0006', 'Quần Short Regular Tag Kim Loại QS030 Màu Rêu', 175000.00, 275000.00, 1, 'Mang phong cách trẻ trung, năng động và không kém phần cá tính, Quần Short Regular Tag Kim Loại QS030 Màu Rêu là lựa chọn hoàn hảo cho những ngày hè sôi động.', 5, 0, '2025-07-02 17:57:36', '2025-07-02 17:58:20'),
(9, 'AT0009', 'Áo Thun Cổ Tròn  Kaki In Chữ Stay Strong Form Regular AT162 Màu Đen', 275000.00, 375000.00, 0, 'Thể hiện cá tính và tinh thần tích cực với Áo Thun Cổ Tròn In Chữ \"Stay Strong\" AT162. Thiết kế đơn giản nhưng đầy ý nghĩa, chiếc áo mang thông điệp truyền cảm hứng.', 1, 0, '2025-07-03 04:32:22', '2025-11-23 20:14:27'),
(11, 'AK0003', 'Áo Khoác Nam Biker AK200', 240000.00, 500000.00, 0, 'Phù hợp với các bạn Nam có hình thể cân đối, thanh mãnh.', 8, 0, '2025-11-23 13:18:09', '2025-11-23 20:03:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_details`
--

CREATE TABLE `product_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `prdID` bigint(20) UNSIGNED NOT NULL,
  `sizeId` bigint(20) UNSIGNED NOT NULL,
  `colorId` bigint(20) UNSIGNED NOT NULL,
  `productQuantity` int(11) NOT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product_details`
--

INSERT INTO `product_details` (`id`, `prdID`, `sizeId`, `colorId`, `productQuantity`, `isDeleted`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 0, 0, '2025-06-07 19:17:13', '2025-06-21 02:44:33'),
(2, 1, 2, 2, 0, 0, '2025-06-07 19:17:13', '2025-11-17 10:54:41'),
(3, 1, 3, 3, 22, 0, '2025-06-07 19:17:13', '2025-11-23 12:58:32'),
(4, 2, 1, 1, 7, 0, '2025-06-09 09:10:16', '2025-11-22 11:30:08'),
(5, 2, 2, 2, 28, 0, '2025-06-09 09:10:16', '2025-06-19 20:29:58'),
(6, 2, 3, 3, 38, 0, '2025-06-09 09:10:16', '2025-06-19 20:17:22'),
(7, 3, 1, 1, 6, 0, '2025-06-09 20:43:46', '2025-07-02 02:41:32'),
(8, 3, 2, 2, 26, 0, '2025-06-09 20:43:46', '2025-11-17 11:06:26'),
(9, 3, 3, 3, 40, 0, '2025-06-09 20:43:46', '2025-06-09 20:43:46'),
(10, 4, 1, 1, 15, 0, '2025-06-09 21:00:36', '2025-11-22 11:30:06'),
(11, 4, 2, 2, 30, 0, '2025-06-09 21:00:36', '2025-06-09 21:00:36'),
(12, 4, 3, 3, 36, 0, '2025-06-09 21:00:36', '2025-07-02 02:35:10'),
(13, 5, 1, 1, 33, 0, '2025-06-23 22:41:05', '2025-07-02 11:49:02'),
(14, 5, 2, 2, 60, 0, '2025-06-23 22:41:05', '2025-06-23 22:41:06'),
(15, 5, 3, 3, 80, 0, '2025-06-23 22:41:05', '2025-06-23 22:41:06'),
(16, 6, 1, 1, 15, 0, '2025-06-24 00:59:41', '2025-07-02 11:56:36'),
(17, 6, 2, 2, 29, 0, '2025-06-24 00:59:41', '2025-06-30 04:52:58'),
(18, 6, 3, 3, 40, 0, '2025-06-24 00:59:41', '2025-06-24 00:59:41'),
(19, 7, 1, 1, 18, 0, '2025-07-02 06:19:23', '2025-07-02 11:35:02'),
(20, 7, 1, 2, 30, 0, '2025-07-02 06:19:23', '2025-07-02 16:59:06'),
(21, 7, 1, 3, 30, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(22, 7, 1, 4, 25, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(23, 7, 1, 5, 15, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(24, 7, 1, 6, 50, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(25, 7, 1, 7, 18, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(26, 7, 1, 8, 40, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(27, 7, 1, 9, 28, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(28, 7, 2, 1, 30, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(29, 7, 2, 2, 22, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(30, 7, 2, 3, 41, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(31, 7, 2, 4, 35, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(32, 7, 2, 5, 20, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(33, 7, 2, 6, 47, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(34, 7, 2, 7, 16, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(35, 7, 2, 8, 32, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(36, 7, 2, 9, 29, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(37, 7, 3, 1, 25, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(38, 7, 3, 2, 30, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(39, 7, 3, 3, 40, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(40, 7, 3, 4, 45, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(41, 7, 3, 5, 20, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(42, 7, 3, 6, 36, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(43, 7, 3, 7, 18, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(44, 7, 3, 8, 38, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(45, 7, 3, 9, 22, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(46, 7, 4, 1, 21, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(47, 7, 4, 2, 33, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(48, 7, 4, 3, 40, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(49, 7, 4, 4, 27, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(50, 7, 4, 5, 19, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(51, 7, 4, 6, 44, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(52, 7, 4, 7, 30, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(53, 7, 4, 8, 26, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(54, 7, 4, 9, 15, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(55, 7, 5, 1, 30, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(56, 7, 5, 2, 34, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(57, 7, 5, 3, 48, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(58, 7, 5, 4, 29, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(59, 7, 5, 5, 13, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(60, 7, 5, 6, 39, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(61, 7, 5, 7, 17, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(62, 7, 5, 8, 45, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(63, 7, 5, 9, 25, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(64, 7, 6, 1, 26, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(65, 7, 6, 2, 40, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(66, 7, 6, 3, 31, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(67, 7, 6, 4, 35, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(68, 7, 6, 5, 20, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(69, 7, 6, 6, 28, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(70, 7, 6, 7, 33, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(71, 7, 6, 8, 41, 0, '2025-07-02 06:19:23', '2025-07-02 06:19:23'),
(72, 7, 6, 9, 28, 0, '2025-07-02 06:19:24', '2025-07-02 08:07:23'),
(73, 8, 1, 1, 20, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(74, 8, 1, 2, 35, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(75, 8, 1, 3, 30, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(76, 8, 1, 4, 25, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(77, 8, 1, 5, 15, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(78, 8, 1, 6, 50, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(79, 8, 1, 7, 18, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(80, 8, 1, 8, 40, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(81, 8, 1, 9, 28, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(82, 8, 2, 1, 30, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(83, 8, 2, 2, 22, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(84, 8, 2, 3, 41, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(85, 8, 2, 4, 35, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(86, 8, 2, 5, 20, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(87, 8, 2, 6, 47, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(88, 8, 2, 7, 16, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(89, 8, 2, 8, 32, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(90, 8, 2, 9, 29, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(91, 8, 3, 1, 25, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(92, 8, 3, 2, 30, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(93, 8, 3, 3, 40, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(94, 8, 3, 4, 45, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(95, 8, 3, 5, 20, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(96, 8, 3, 6, 36, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(97, 8, 3, 7, 18, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(98, 8, 3, 8, 38, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(99, 8, 3, 9, 22, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(100, 8, 4, 1, 19, 0, '2025-07-02 17:58:56', '2025-07-03 04:26:12'),
(101, 8, 4, 2, 33, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(102, 8, 4, 3, 40, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(103, 8, 4, 4, 27, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(104, 8, 4, 5, 19, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(105, 8, 4, 6, 44, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(106, 8, 4, 7, 30, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(107, 8, 4, 8, 26, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(108, 8, 4, 9, 15, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(109, 8, 5, 1, 30, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(110, 8, 5, 2, 34, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(111, 8, 5, 3, 48, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(112, 8, 5, 4, 29, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(113, 8, 5, 5, 13, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(114, 8, 5, 6, 39, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(115, 8, 5, 7, 17, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(116, 8, 5, 8, 45, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(117, 8, 5, 9, 25, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(118, 8, 6, 1, 26, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(119, 8, 6, 2, 40, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(120, 8, 6, 3, 31, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(121, 8, 6, 4, 35, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(122, 8, 6, 5, 20, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(123, 8, 6, 6, 28, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(124, 8, 6, 7, 33, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(125, 8, 6, 8, 41, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(126, 8, 6, 9, 30, 0, '2025-07-02 17:58:56', '2025-07-02 17:58:56'),
(127, 9, 1, 1, 20, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(128, 9, 1, 2, 35, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(129, 9, 1, 3, 30, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(130, 9, 1, 4, 25, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(131, 9, 1, 5, 15, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(132, 9, 1, 6, 50, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(133, 9, 1, 7, 18, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(134, 9, 1, 8, 40, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(135, 9, 1, 9, 28, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(136, 9, 2, 1, 30, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(137, 9, 2, 2, 22, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(138, 9, 2, 3, 41, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(139, 9, 2, 4, 35, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(140, 9, 2, 5, 20, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(141, 9, 2, 6, 47, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(142, 9, 2, 7, 16, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(143, 9, 2, 8, 32, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(144, 9, 2, 9, 29, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(145, 9, 3, 1, 25, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(146, 9, 3, 2, 30, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(147, 9, 3, 3, 40, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(148, 9, 3, 4, 43, 0, '2025-07-03 04:33:04', '2025-07-03 04:39:10'),
(149, 9, 3, 5, 20, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(150, 9, 3, 6, 36, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(151, 9, 3, 7, 18, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(152, 9, 3, 8, 38, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(153, 9, 3, 9, 22, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(154, 9, 4, 1, 21, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(155, 9, 4, 2, 33, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(156, 9, 4, 3, 40, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(157, 9, 4, 4, 27, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(158, 9, 4, 5, 19, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(159, 9, 4, 6, 44, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(160, 9, 4, 7, 30, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(161, 9, 4, 8, 26, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(162, 9, 4, 9, 15, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(163, 9, 5, 1, 30, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(164, 9, 5, 2, 34, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(165, 9, 5, 3, 48, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(166, 9, 5, 4, 29, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(167, 9, 5, 5, 13, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(168, 9, 5, 6, 39, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(169, 9, 5, 7, 17, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(170, 9, 5, 8, 45, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(171, 9, 5, 9, 25, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(172, 9, 6, 1, 26, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(173, 9, 6, 2, 40, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(174, 9, 6, 3, 31, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(175, 9, 6, 4, 35, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(176, 9, 6, 5, 20, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(177, 9, 6, 6, 28, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(178, 9, 6, 7, 33, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(179, 9, 6, 8, 41, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04'),
(180, 9, 6, 9, 30, 0, '2025-07-03 04:33:04', '2025-07-03 04:33:04');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_images`
--

CREATE TABLE `product_images` (
  `imageID` bigint(20) UNSIGNED NOT NULL,
  `prdID` bigint(20) UNSIGNED NOT NULL,
  `imageLink` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product_images`
--

INSERT INTO `product_images` (`imageID`, `prdID`, `imageLink`, `created_at`, `updated_at`) VALUES
(1, 1, 'product_images/0MkOIaz6MvAv8MF8wMFWnKS7vMi5zrDzU1sWujci.jpg', '2025-06-07 18:59:28', '2025-06-07 18:59:28'),
(2, 1, 'product_images/aAHTr9W3loBsIGnQ8ZTJOElAs5z1ymWJ9lhgqn9t.jpg', '2025-06-07 18:59:28', '2025-06-07 18:59:28'),
(3, 1, 'product_images/kKbYgjURUuVPad1hhe4t77L0ahDq32PNzKxF1gtJ.jpg', '2025-06-07 18:59:28', '2025-06-07 18:59:28'),
(4, 1, 'product_images/yx0JCer39fP3rmWKC2dqvXCjChL8Gf3Xig2WCndE.jpg', '2025-06-07 18:59:28', '2025-06-07 18:59:28'),
(5, 1, 'product_images/YWJcCoCF2I4fW9IEskyJlDxhaoEUvea0zhliI3bs.jpg', '2025-06-07 18:59:28', '2025-06-07 18:59:28'),
(7, 2, 'product_images/OeVfkXJd3BYhcxM6ob4bRx4uw86DklAYduDfB9X9.jpg', '2025-06-09 09:09:52', '2025-06-09 09:09:52'),
(8, 2, 'product_images/4GgcK313rRHXG1JTqkZn7TZIOht7ep5LZkpzNVRu.jpg', '2025-06-09 09:09:52', '2025-06-09 09:09:52'),
(9, 2, 'product_images/uxxqBmrKEzvT33tJNcYBvfeEw3DQvCRzBJSTrNNQ.jpg', '2025-06-09 09:09:52', '2025-06-09 09:09:52'),
(11, 3, 'product_images/BT6OhzSozlN7DHBgJFQaP9REA01R1FVFDUxlChzh.jpg', '2025-06-09 20:44:03', '2025-06-09 20:44:03'),
(12, 3, 'product_images/g2TMosF3qnY6HQeGC8mDTEtJAeaI36fHnvjBCrq9.jpg', '2025-06-09 20:44:03', '2025-06-09 20:44:03'),
(13, 3, 'product_images/mlBOJ8kMEHcgwN5LJQxB31h7msaecsAwU2yEbDTy.jpg', '2025-06-09 20:44:03', '2025-06-09 20:44:03'),
(14, 3, 'product_images/BKyHozhYbaKPIvZQAE6l4CQfVQi6meO21GEyBsFz.jpg', '2025-06-09 20:44:03', '2025-06-09 20:44:03'),
(15, 4, 'product_images/dXyFAApXINU6n0iCR3NU5E9XxaDDBgl32rHPHhcz.jpg', '2025-06-09 21:00:49', '2025-06-09 21:00:49'),
(16, 4, 'product_images/crzoBdF3n5zgVim9WdeopMFUjU1APkT34TpDXamB.jpg', '2025-06-09 21:00:49', '2025-06-09 21:00:49'),
(17, 4, 'product_images/f1zT1aJe9nQepSMubLcsss4WlDaGHqsmQAh1no5O.jpg', '2025-06-09 21:00:49', '2025-06-09 21:00:49'),
(18, 4, 'product_images/Vp1wMTAi2cZRjVCn9i9m8OjtFVFIH2SWEqNHihid.png', '2025-06-09 21:00:49', '2025-06-09 21:00:49'),
(19, 4, 'product_images/SAPZrPYNUwMxd81HjANjqs9ujxVJd50BiwxfoV3d.png', '2025-06-09 21:00:49', '2025-06-09 21:00:49'),
(29, 6, 'product_images/Fp4ASnFdgli7LzKDSzZhn6Q0F7zSIbFJfwq111eX.jpg', '2025-06-23 22:41:48', '2025-06-23 22:41:48'),
(30, 6, 'product_images/oY9io9NYz9gsKABzxMPtlC0kHqGE418hErqEM8Ih.jpg', '2025-06-23 22:41:48', '2025-06-23 22:41:48'),
(31, 6, 'product_images/pjA3FFcovaF1h1dowLsbsT85pSZwUj3Sh6OIDTXf.jpg', '2025-06-23 22:41:48', '2025-06-23 22:41:48'),
(35, 7, 'product_images/uG5cAq0m9zut9njeqmzD0W4UKldn5mUGJoaxZgm1.jpg', '2025-07-02 06:20:25', '2025-07-02 06:20:25'),
(36, 7, 'product_images/bshEz1vPrvIRCi0S6z81GftLGoMvvt61HUwZf0ZC.jpg', '2025-07-02 06:20:25', '2025-07-02 06:20:25'),
(37, 7, 'product_images/gKwRjj62IqtPWBgCxeCIUzgcj3DDozgkMLSZFe3r.jpg', '2025-07-02 06:20:25', '2025-07-02 06:20:25'),
(38, 7, 'product_images/IvYQbaRYVjwblcFMhdDceFWlBQ8H5PLS3FlGYUbm.jpg', '2025-07-02 06:20:25', '2025-07-02 06:20:25'),
(40, 7, 'product_images/C2raDQClDgPI4mxEBbzPQ8oDKEuYPfN9lHx6hXUV.jpg', '2025-07-02 06:20:25', '2025-07-02 06:20:25'),
(43, 6, 'product_images/oFz2e74wjBazlmHoLFH8G6rjkMTX7L7ZBp1oDkJS.jpg', '2025-07-02 06:24:41', '2025-07-02 06:24:41'),
(44, 5, 'product_images/nWOxD13ANRe6JF37kfonPuQ2PGrBr0BUSS7jB64C.jpg', '2025-07-02 11:40:00', '2025-07-02 11:40:00'),
(45, 5, 'product_images/nmU6UsdgjSBiB6WrS912gDN9ywTQPQ0gBaq1Z7mm.jpg', '2025-07-02 11:40:00', '2025-07-02 11:40:00'),
(46, 5, 'product_images/gmy53ey5itr1GZHaXi9LU3KOC4cbC7AOsS89KX4K.jpg', '2025-07-02 11:40:00', '2025-07-02 11:40:00'),
(47, 5, 'product_images/I2jmM2XhSQzhrJdiIFSpOmBi1gpaaLD4bx7abwpO.jpg', '2025-07-02 11:40:00', '2025-07-02 11:40:00'),
(48, 5, 'product_images/bUJuCdDJuw57C8dNhBo2uyOngEwENJf1vONEzI6X.jpg', '2025-07-02 11:40:00', '2025-07-02 11:40:00'),
(49, 8, 'product_images/Vfndb2P8Dvq8FZ2lgcM0C6nGpv57E0mtVfrbq3zE.jpg', '2025-07-02 17:57:49', '2025-07-02 17:57:49'),
(50, 8, 'product_images/v9uNOeMlajVGNzjHls90glfxwWXOFZDor4VEtUpo.jpg', '2025-07-02 17:57:49', '2025-07-02 17:57:49'),
(51, 8, 'product_images/72jbovUjLgjAlDpX5DAVwkQmRJ7COA9dj2xzGCOj.jpg', '2025-07-02 17:57:49', '2025-07-02 17:57:49'),
(52, 8, 'product_images/VkAlgRWG9PfFHEiwqJmuQduYQjYWhS1bwJ5KT8nM.jpg', '2025-07-02 17:57:49', '2025-07-02 17:57:49'),
(53, 8, 'product_images/nqoTLNHsY5UI7Aydlm4gTDgSV952bjwFKxeiRftP.jpg', '2025-07-02 17:57:49', '2025-07-02 17:57:49'),
(54, 9, 'product_images/xDclg7BY6hyVREnPehsi0XMbidYhqFIYV50EuS9r.jpg', '2025-07-03 04:32:36', '2025-07-03 04:32:36'),
(55, 9, 'product_images/klQPPz9TQlGA6uvYNTA4EL5lmwLDxcHW8wGd44gh.jpg', '2025-07-03 04:32:36', '2025-07-03 04:32:36'),
(56, 9, 'product_images/J0kWxAb1s31sGTXiE1OwASobFlnGAjGcjyI2OdPU.jpg', '2025-07-03 04:32:36', '2025-07-03 04:32:36'),
(57, 9, 'product_images/Q0RoE9WGboQ1b6rC8xapcAIq9CPqwjSdy9ImUZN1.jpg', '2025-07-03 04:32:36', '2025-07-03 04:32:36'),
(59, 9, 'product_images/Ft8gnEml5vbzoQkr51gmLv5GjCedyl7jIMJACD7j.jpg', '2025-07-03 04:33:33', '2025-07-03 04:33:33'),
(62, 11, 'product_images/y97jSXKxRQLNC0hdRyv9I4vTwqJf0jrxRIJF7Ec9.jpg', '2025-11-23 13:18:47', '2025-11-23 13:18:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('3FFolCwoLUPQ9oCm1V1UqtDaUclnkMDHy0Zn4Kqb', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoidXoyRGRrTzhpN0xWbVJmU3o3dE5NaDBGVFBWS0lDb1ZtUGs5ejFYMyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozNDoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL29yZGVyLW1hbmFnZSI7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjQzOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvc2hvd1Byb2R1Y3Q/c2VhcmNoPUFUIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1763967830),
('QXjCewMLlz4sXqf84Ncf75ZtR8PAX6zgbY1TeAqy', 8, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQjhpNUVzQ0VRa0RMTUxNeFhwR292M2dSN3F0UUdkVVh2aE5qQTNIOSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQ/bW9udGg9MTEiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo4O30=', 1763956759);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sizes`
--

CREATE TABLE `sizes` (
  `sizeId` bigint(20) UNSIGNED NOT NULL,
  `sizeName` varchar(255) NOT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `sizes`
--

INSERT INTO `sizes` (`sizeId`, `sizeName`, `isDeleted`, `created_at`, `updated_at`) VALUES
(1, 'S', 0, NULL, NULL),
(2, 'M', 0, NULL, NULL),
(3, 'L', 0, NULL, NULL),
(4, 'XL', 0, NULL, NULL),
(5, 'XXL', 0, NULL, NULL),
(6, 'XXXL', 0, NULL, NULL),
(7, 'S', 0, NULL, NULL),
(8, 'M', 0, NULL, NULL),
(9, 'L', 0, NULL, NULL),
(10, 'XL', 0, NULL, NULL),
(11, 'XXL', 0, NULL, NULL),
(12, 'XXXL', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `status`
--

CREATE TABLE `status` (
  `statusID` bigint(20) UNSIGNED NOT NULL,
  `statusValue` varchar(255) NOT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `status`
--

INSERT INTO `status` (`statusID`, `statusValue`, `isDeleted`, `created_at`, `updated_at`) VALUES
(1, 'Đang chờ duyệt', 0, NULL, NULL),
(2, 'Đã duyệt', 0, NULL, NULL),
(3, 'Đang giao hàng', 0, NULL, NULL),
(4, 'Đã giao hàng', 0, NULL, NULL),
(5, 'Đã hủy', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(10) DEFAULT NULL,
  `street_address` varchar(100) DEFAULT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `role` enum('admin','customer') NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `name`, `email`, `phone`, `street_address`, `ward`, `district`, `city`, `role`, `email_verified_at`, `password`, `isDeleted`, `remember_token`, `created_at`, `updated_at`) VALUES
(8, 'Langhia1808', NULL, 'trunng103@gmail.com', NULL, NULL, NULL, NULL, NULL, 'admin', NULL, '$2y$12$a63tnl2I0czB8SNwNrVLpONe83ZnsEMfJODZTx8IT5TMFdssxZogi', 0, NULL, '2025-11-17 10:53:10', '2025-11-17 10:53:10'),
(9, 'user_1763402715', 'Thiều Văn Long', 'user1763402715@example.com', '0335244686', NULL, NULL, NULL, NULL, 'customer', NULL, '$2y$12$79g267jsheZzScIpf5KaXOhIVioMM9hayKzTX1YCxLp1A/PnYJcOS', 0, NULL, '2025-11-17 11:05:15', '2025-11-23 11:44:32'),
(10, 'nghĩalc123', 'La Trung Nghĩa', 'kurobakarma@gmail.com', '1951941056', 'Lao Cai', NULL, NULL, NULL, 'customer', NULL, '$2y$12$5uIgwTTKH2NEZb74jcGxEOcLuxdOOop1s6GcRjp99Vxii3jDgQhnO', 0, NULL, NULL, NULL),
(11, 'teolc123', 'La Trung Nghẽo', 'ad@gmail.com', '0709292929', 'TQB', NULL, NULL, NULL, 'admin', NULL, '$2y$12$LAEnahWkyBEqdBxc6nCIiuJpXPUDdX/dRZ/qkfx7KJXK4VRAQyCaq', 0, NULL, NULL, NULL),
(18, 'traivoidaodat1', NULL, 'la@gmail.com', NULL, NULL, NULL, NULL, NULL, 'customer', NULL, '$2y$12$b516gRBlS.5i7Zr2C3IHy.peOofjU10HmVOp3pfxyJ5sfywWeiSzG', 0, NULL, '2025-11-22 11:02:08', '2025-11-22 11:02:08');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Chỉ mục cho bảng `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Chỉ mục cho bảng `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cartID`),
  ADD KEY `cart_userid_foreign` (`userID`);

--
-- Chỉ mục cho bảng `cart_details`
--
ALTER TABLE `cart_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_details_cartid_foreign` (`cartID`),
  ADD KEY `cart_details_productdetailid_foreign` (`productDetailID`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`categoryID`);

--
-- Chỉ mục cho bảng `colors`
--
ALTER TABLE `colors`
  ADD PRIMARY KEY (`colorId`);

--
-- Chỉ mục cho bảng `comment_and_rate`
--
ALTER TABLE `comment_and_rate`
  ADD PRIMARY KEY (`idComment`),
  ADD KEY `comment_and_rate_cusid_foreign` (`cusID`),
  ADD KEY `comment_and_rate_productid_foreign` (`productID`);

--
-- Chỉ mục cho bảng `discount_programs`
--
ALTER TABLE `discount_programs`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Chỉ mục cho bảng `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Chỉ mục cho bảng `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`orderID`),
  ADD KEY `orders_cusid_foreign` (`cusID`),
  ADD KEY `orders_payid_foreign` (`payID`),
  ADD KEY `orders_adminid_foreign` (`adminID`),
  ADD KEY `orders_staid_foreign` (`staID`),
  ADD KEY `orders_discount_program_id_foreign` (`discount_program_id`);

--
-- Chỉ mục cho bảng `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`orderID`,`productDetailID`),
  ADD KEY `order_details_productdetailid_foreign` (`productDetailID`);

--
-- Chỉ mục cho bảng `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Chỉ mục cho bảng `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`paymentID`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`productID`),
  ADD KEY `products_cateid_foreign` (`cateID`);

--
-- Chỉ mục cho bảng `product_details`
--
ALTER TABLE `product_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_details_prdid_foreign` (`prdID`),
  ADD KEY `product_details_sizeid_foreign` (`sizeId`),
  ADD KEY `product_details_colorid_foreign` (`colorId`);

--
-- Chỉ mục cho bảng `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`imageID`),
  ADD KEY `product_images_prdid_foreign` (`prdID`);

--
-- Chỉ mục cho bảng `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Chỉ mục cho bảng `sizes`
--
ALTER TABLE `sizes`
  ADD PRIMARY KEY (`sizeId`);

--
-- Chỉ mục cho bảng `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`statusID`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `cart`
--
ALTER TABLE `cart`
  MODIFY `cartID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `cart_details`
--
ALTER TABLE `cart_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `categoryID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `colors`
--
ALTER TABLE `colors`
  MODIFY `colorId` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `comment_and_rate`
--
ALTER TABLE `comment_and_rate`
  MODIFY `idComment` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `discount_programs`
--
ALTER TABLE `discount_programs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `orderID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT cho bảng `payments`
--
ALTER TABLE `payments`
  MODIFY `paymentID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `productID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `product_details`
--
ALTER TABLE `product_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT cho bảng `product_images`
--
ALTER TABLE `product_images`
  MODIFY `imageID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT cho bảng `sizes`
--
ALTER TABLE `sizes`
  MODIFY `sizeId` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `status`
--
ALTER TABLE `status`
  MODIFY `statusID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_userid_foreign` FOREIGN KEY (`userID`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `cart_details`
--
ALTER TABLE `cart_details`
  ADD CONSTRAINT `cart_details_cartid_foreign` FOREIGN KEY (`cartID`) REFERENCES `cart` (`cartID`),
  ADD CONSTRAINT `cart_details_productdetailid_foreign` FOREIGN KEY (`productDetailID`) REFERENCES `product_details` (`id`);

--
-- Các ràng buộc cho bảng `comment_and_rate`
--
ALTER TABLE `comment_and_rate`
  ADD CONSTRAINT `comment_and_rate_cusid_foreign` FOREIGN KEY (`cusID`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_and_rate_productid_foreign` FOREIGN KEY (`productID`) REFERENCES `products` (`productID`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_adminid_foreign` FOREIGN KEY (`adminID`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_cusid_foreign` FOREIGN KEY (`cusID`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_discount_program_id_foreign` FOREIGN KEY (`discount_program_id`) REFERENCES `discount_programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_payid_foreign` FOREIGN KEY (`payID`) REFERENCES `payments` (`paymentID`),
  ADD CONSTRAINT `orders_staid_foreign` FOREIGN KEY (`staID`) REFERENCES `status` (`statusID`);

--
-- Các ràng buộc cho bảng `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_orderid_foreign` FOREIGN KEY (`orderID`) REFERENCES `orders` (`orderID`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_productdetailid_foreign` FOREIGN KEY (`productDetailID`) REFERENCES `product_details` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_cateid_foreign` FOREIGN KEY (`cateID`) REFERENCES `categories` (`categoryID`);

--
-- Các ràng buộc cho bảng `product_details`
--
ALTER TABLE `product_details`
  ADD CONSTRAINT `product_details_colorid_foreign` FOREIGN KEY (`colorId`) REFERENCES `colors` (`colorId`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_details_prdid_foreign` FOREIGN KEY (`prdID`) REFERENCES `products` (`productID`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_details_sizeid_foreign` FOREIGN KEY (`sizeId`) REFERENCES `sizes` (`sizeId`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_prdid_foreign` FOREIGN KEY (`prdID`) REFERENCES `products` (`productID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

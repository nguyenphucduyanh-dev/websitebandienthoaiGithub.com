-- =============================================
-- DATABASE: websitebandienthoai
-- Tác giả: nguyenphucduyanh-dev
-- Mô tả: Website bán điện thoại PHP/MySQL
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS `websitebandienthoai`;
CREATE DATABASE `websitebandienthoai` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `websitebandienthoai`;

-- ---------------------------------------------
-- Bảng: categories (Phân loại sản phẩm)
-- ---------------------------------------------
CREATE TABLE `categories` (
    `category_id`   INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(100) NOT NULL,
    `description`   TEXT DEFAULT NULL,
    `image`         VARCHAR(255) DEFAULT NULL,
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Bảng: products (Sản phẩm)
-- Lưu ý: import_price = giá nhập bình quân
--         profit_rate  = tỷ lệ lợi nhuận (VD: 0.20 = 20%)
--         selling_price = import_price * (1 + profit_rate) → tính động
-- ---------------------------------------------
CREATE TABLE `products` (
    `product_id`     INT AUTO_INCREMENT PRIMARY KEY,
    `category_id`    INT NOT NULL,
    `product_name`   VARCHAR(255) NOT NULL,
    `slug`           VARCHAR(255) DEFAULT NULL,
    `description`    TEXT DEFAULT NULL,
    `image`          VARCHAR(255) DEFAULT NULL,
    `import_price`   DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Giá nhập bình quân hiện tại',
    `profit_rate`    DECIMAL(5,4) NOT NULL DEFAULT 0.2000 COMMENT 'Tỷ lệ lợi nhuận, VD: 0.20 = 20%',
    `stock_quantity` INT NOT NULL DEFAULT 0 COMMENT 'Số lượng tồn kho',
    `brand`          VARCHAR(100) DEFAULT NULL,
    `specifications`  JSON DEFAULT NULL COMMENT 'Thông số kỹ thuật dạng JSON',
    `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_category` (`category_id`),
    INDEX `idx_product_name` (`product_name`),
    INDEX `idx_brand` (`brand`),
    INDEX `idx_stock` (`stock_quantity`),

    CONSTRAINT `fk_products_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Bảng: inventory_log (Lịch sử nhập hàng)
-- Phục vụ tính giá bình quân & truy vết
-- ---------------------------------------------
CREATE TABLE `inventory_log` (
    `log_id`              INT AUTO_INCREMENT PRIMARY KEY,
    `product_id`          INT NOT NULL,
    `quantity_before`     INT NOT NULL DEFAULT 0 COMMENT 'Tồn kho trước khi nhập',
    `import_price_before` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Giá nhập BQ trước khi nhập',
    `quantity_imported`   INT NOT NULL COMMENT 'Số lượng nhập mới',
    `unit_import_price`   DECIMAL(15,2) NOT NULL COMMENT 'Đơn giá nhập lần này',
    `import_price_after`  DECIMAL(15,2) NOT NULL COMMENT 'Giá nhập BQ sau khi nhập',
    `quantity_after`      INT NOT NULL COMMENT 'Tồn kho sau khi nhập',
    `supplier`            VARCHAR(255) DEFAULT NULL,
    `note`                TEXT DEFAULT NULL,
    `created_by`          INT DEFAULT NULL COMMENT 'ID admin thực hiện',
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_inv_product` (`product_id`),
    INDEX `idx_inv_date` (`created_at`),

    CONSTRAINT `fk_inventory_product`
        FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Bảng: users (Người dùng)
-- ---------------------------------------------
CREATE TABLE `users` (
    `user_id`        INT AUTO_INCREMENT PRIMARY KEY,
    `username`       VARCHAR(50) NOT NULL UNIQUE,
    `email`          VARCHAR(100) NOT NULL UNIQUE,
    `password_hash`  VARCHAR(255) NOT NULL,
    `full_name`      VARCHAR(150) NOT NULL,
    `phone`          VARCHAR(20) DEFAULT NULL,
    `address`        TEXT DEFAULT NULL COMMENT 'Địa chỉ mặc định',
    `avatar`         VARCHAR(255) DEFAULT NULL,
    `role`           ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Bảng: orders (Đơn hàng)
-- ---------------------------------------------
CREATE TABLE `orders` (
    `order_id`         INT AUTO_INCREMENT PRIMARY KEY,
    `order_code`       VARCHAR(30) NOT NULL UNIQUE COMMENT 'Mã đơn hàng hiển thị, VD: DH20260330001',
    `user_id`          INT NOT NULL,
    `receiver_name`    VARCHAR(150) NOT NULL,
    `receiver_phone`   VARCHAR(20) NOT NULL,
    `shipping_address`  TEXT NOT NULL,
    `payment_method`   ENUM('cash', 'bank_transfer', 'online') NOT NULL DEFAULT 'cash',
    `total_amount`     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status`           ENUM('pending', 'confirmed', 'shipping', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    `note`             TEXT DEFAULT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_order_user` (`user_id`),
    INDEX `idx_order_status` (`status`),
    INDEX `idx_order_date` (`created_at`),

    CONSTRAINT `fk_orders_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Bảng: order_details (Chi tiết đơn hàng)
-- ---------------------------------------------
CREATE TABLE `order_details` (
    `detail_id`     INT AUTO_INCREMENT PRIMARY KEY,
    `order_id`      INT NOT NULL,
    `product_id`    INT NOT NULL,
    `product_name`  VARCHAR(255) NOT NULL COMMENT 'Lưu tên SP tại thời điểm mua',
    `quantity`      INT NOT NULL DEFAULT 1,
    `unit_price`    DECIMAL(15,2) NOT NULL COMMENT 'Giá bán tại thời điểm mua',
    `subtotal`      DECIMAL(15,2) NOT NULL COMMENT 'quantity * unit_price',

    INDEX `idx_detail_order` (`order_id`),
    INDEX `idx_detail_product` (`product_id`),

    CONSTRAINT `fk_details_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT `fk_details_product`
        FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DỮ LIỆU MẪU
-- =============================================

INSERT INTO `categories` (`category_name`, `description`) VALUES
('Smartphone',    'Điện thoại thông minh'),
('Tablet',        'Máy tính bảng'),
('Feature Phone', 'Điện thoại phổ thông'),
('Phụ kiện',      'Phụ kiện điện thoại');

INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `phone`, `address`, `role`) VALUES
('admin',    'admin@shop.vn',    '$2y$10$dummyHashAdmin1234567890abcdefghijklmnop', 'Quản Trị Viên', '0901234567', '123 Nguyễn Huệ, Q.1, TP.HCM', 'admin'),
('duyanh',   'duyanh@gmail.com', '$2y$10$dummyHashUser01234567890abcdefghijklmnop', 'Nguyễn Phúc Duy Anh', '0912345678', '456 Lê Lợi, Q.1, TP.HCM', 'customer');

INSERT INTO `products` (`category_id`, `product_name`, `slug`, `description`, `image`, `import_price`, `profit_rate`, `stock_quantity`, `brand`) VALUES
(1, 'iPhone 15 Pro Max 256GB',  'iphone-15-pro-max-256gb',  'Điện thoại Apple iPhone 15 Pro Max', 'iphone15promax.jpg', 28000000, 0.2000, 50, 'Apple'),
(1, 'Samsung Galaxy S24 Ultra',  'samsung-galaxy-s24-ultra',  'Điện thoại Samsung Galaxy S24 Ultra', 'galaxys24ultra.jpg', 25000000, 0.1800, 40, 'Samsung'),
(1, 'Xiaomi 14 Pro',             'xiaomi-14-pro',             'Điện thoại Xiaomi 14 Pro',            'xiaomi14pro.jpg',    10000000, 0.2200, 60, 'Xiaomi'),
(2, 'iPad Air M2',               'ipad-air-m2',               'Máy tính bảng Apple iPad Air M2',     'ipadairm2.jpg',      15000000, 0.2000, 30, 'Apple'),
(3, 'Nokia 3210 4G',             'nokia-3210-4g',             'Điện thoại Nokia 3210 phiên bản 4G',  'nokia3210.jpg',       1200000, 0.2500, 100, 'Nokia');

SET FOREIGN_KEY_CHECKS = 1;

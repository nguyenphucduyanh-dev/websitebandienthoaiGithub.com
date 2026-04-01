-- ============================================================
--  Phone Shop Database Schema
--  Engine: InnoDB | Charset: utf8mb4
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100)     NOT NULL,
    `slug`        VARCHAR(120)     NOT NULL,
    `description` TEXT,
    `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. products
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
    `id`             INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `category_id`    INT UNSIGNED        NOT NULL,
    `name`           VARCHAR(200)        NOT NULL,
    `slug`           VARCHAR(220)        NOT NULL,
    `description`    TEXT,
    `image`          VARCHAR(255),
    -- Giá nhập bình quân hiện tại (cập nhật mỗi lần nhập kho)
    `import_price`   DECIMAL(15, 2)      NOT NULL DEFAULT 0.00,
    -- Tỷ lệ lợi nhuận, ví dụ 0.15 = 15%
    `profit_rate`    DECIMAL(5, 4)       NOT NULL DEFAULT 0.1500,
    -- Giá bán = import_price * (1 + profit_rate)  — stored as generated column
    `selling_price`  DECIMAL(15, 2)      GENERATED ALWAYS AS
                         (ROUND(`import_price` * (1 + `profit_rate`), 0)) STORED,
    `stock_quantity` INT UNSIGNED        NOT NULL DEFAULT 0,
    `is_active`      TINYINT(1)          NOT NULL DEFAULT 1,
    `created_at`     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_products_slug` (`slug`),
    KEY `idx_products_category` (`category_id`),
    KEY `idx_products_selling_price` (`selling_price`),
    CONSTRAINT `fk_products_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `full_name`       VARCHAR(120)    NOT NULL,
    `email`           VARCHAR(180)    NOT NULL,
    `phone`           VARCHAR(20),
    `password_hash`   VARCHAR(255)    NOT NULL,
    -- Địa chỉ mặc định (dùng cho checkout)
    `default_address` VARCHAR(500),
    `role`            ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. orders
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`          INT UNSIGNED    NOT NULL,
    `shipping_address` VARCHAR(500)    NOT NULL,
    -- COD | BANK_TRANSFER | ONLINE
    `payment_method`   ENUM('COD','BANK_TRANSFER','ONLINE') NOT NULL DEFAULT 'COD',
    `payment_status`   ENUM('pending','paid','failed')      NOT NULL DEFAULT 'pending',
    `order_status`     ENUM('pending','confirmed','shipping','delivered','cancelled')
                                        NOT NULL DEFAULT 'pending',
    `total_amount`     DECIMAL(15, 2)  NOT NULL DEFAULT 0.00,
    `note`             TEXT,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_orders_user` (`user_id`),
    KEY `idx_orders_status` (`order_status`),
    KEY `idx_orders_created` (`created_at`),
    CONSTRAINT `fk_orders_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. order_details
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_details` (
    `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `order_id`     INT UNSIGNED    NOT NULL,
    `product_id`   INT UNSIGNED    NOT NULL,
    -- Snapshot giá tại thời điểm đặt hàng
    `unit_price`   DECIMAL(15, 2)  NOT NULL,
    `quantity`     INT UNSIGNED    NOT NULL DEFAULT 1,
    `subtotal`     DECIMAL(15, 2)  GENERATED ALWAYS AS (`unit_price` * `quantity`) STORED,
    PRIMARY KEY (`id`),
    KEY `idx_order_details_order`   (`order_id`),
    KEY `idx_order_details_product` (`product_id`),
    CONSTRAINT `fk_order_details_order`
        FOREIGN KEY (`order_id`)   REFERENCES `orders`   (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_order_details_product`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. inventory_log  — lịch sử nhập kho, dùng tính giá bình quân
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inventory_log` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `product_id`      INT UNSIGNED    NOT NULL,
    -- Số lượng & giá của lô nhập này
    `import_quantity` INT UNSIGNED    NOT NULL,
    `import_price`    DECIMAL(15, 2)  NOT NULL,
    -- Snapshot trước / sau khi nhập (hỗ trợ audit)
    `stock_before`    INT UNSIGNED    NOT NULL DEFAULT 0,
    `avg_price_before` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `avg_price_after`  DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `supplier`        VARCHAR(200),
    `note`            TEXT,
    `created_by`      INT UNSIGNED,   -- admin user id
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_inv_log_product` (`product_id`),
    KEY `idx_inv_log_created` (`created_at`),
    CONSTRAINT `fk_inv_log_product`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_inv_log_admin`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  Sample seed data
-- ============================================================
INSERT INTO `categories` (`name`, `slug`, `description`) VALUES
('iPhone',    'iphone',    'Điện thoại Apple iPhone'),
('Samsung',   'samsung',   'Điện thoại Samsung Galaxy'),
('Xiaomi',    'xiaomi',    'Điện thoại Xiaomi / Redmi'),
('OPPO',      'oppo',      'Điện thoại OPPO'),
('Phụ kiện',  'phu-kien',  'Ốp lưng, cáp sạc, tai nghe...');

INSERT INTO `users` (`full_name`, `email`, `phone`, `password_hash`, `default_address`, `role`) VALUES
('Admin',        'admin@phoneshop.vn',   '0901000001',
 '$2y$12$exampleHashAdmin',  '123 Lê Lợi, Q.1, TP.HCM', 'admin'),
('Nguyễn Văn A', 'customer@example.com', '0912345678',
 '$2y$12$exampleHashCust',   '456 Nguyễn Trãi, Q.5, TP.HCM', 'customer');

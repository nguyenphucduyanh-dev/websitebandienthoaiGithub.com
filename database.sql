-- ============================================================
-- Website Bán Điện Thoại - database.sql
-- Tác giả: nguyenphucduyanh-dev
-- ============================================================

CREATE DATABASE IF NOT EXISTS websitebandienthoai
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE websitebandienthoai;

-- ------------------------------------------------------------
-- 1. categories
-- ------------------------------------------------------------
CREATE TABLE categories (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(100) NOT NULL,
    slug      VARCHAR(120) NOT NULL UNIQUE,
    created_at DATETIME    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. users
-- ------------------------------------------------------------
CREATE TABLE users (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name    VARCHAR(150) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    phone        VARCHAR(20),
    address      TEXT,
    role         ENUM('customer','admin') DEFAULT 'customer',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. products
-- ------------------------------------------------------------
CREATE TABLE products (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id    INT UNSIGNED NOT NULL,
    name           VARCHAR(200) NOT NULL,
    slug           VARCHAR(220) NOT NULL UNIQUE,
    description    TEXT,
    image          VARCHAR(255),
    import_price   DECIMAL(15,2) NOT NULL DEFAULT 0,   -- giá nhập bình quân
    profit_rate    DECIMAL(5,4)  NOT NULL DEFAULT 0.20, -- 0.20 = 20%
    stock_quantity INT UNSIGNED  NOT NULL DEFAULT 0,
    is_active      TINYINT(1)    NOT NULL DEFAULT 1,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. inventory_log  (lịch sử nhập hàng - phục vụ tính giá bình quân)
-- ------------------------------------------------------------
CREATE TABLE inventory_log (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED  NOT NULL,
    quantity_in     INT UNSIGNED  NOT NULL,
    unit_price      DECIMAL(15,2) NOT NULL,           -- giá nhập lần này
    avg_price_after DECIMAL(15,2) NOT NULL,           -- giá bình quân sau khi nhập
    note            VARCHAR(255),
    imported_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_invlog_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. orders
-- ------------------------------------------------------------
CREATE TABLE orders (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED  NOT NULL,
    shipping_address TEXT          NOT NULL,
    payment_method   ENUM('cash','transfer','online') NOT NULL DEFAULT 'cash',
    status           ENUM('pending','confirmed','shipping','delivered','cancelled')
                     NOT NULL DEFAULT 'pending',
    total_amount     DECIMAL(15,2) NOT NULL DEFAULT 0,
    note             TEXT,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 6. order_details
-- ------------------------------------------------------------
CREATE TABLE order_details (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id     INT UNSIGNED  NOT NULL,
    product_id   INT UNSIGNED  NOT NULL,
    quantity     INT UNSIGNED  NOT NULL,
    unit_price   DECIMAL(15,2) NOT NULL,   -- selling_price tại thời điểm đặt
    subtotal     DECIMAL(15,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
    CONSTRAINT fk_od_order
        FOREIGN KEY (order_id)   REFERENCES orders(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_od_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Sample data
-- ------------------------------------------------------------
INSERT INTO categories (name, slug) VALUES
('iPhone',    'iphone'),
('Samsung',   'samsung'),
('Xiaomi',    'xiaomi'),
('OPPO',      'oppo');

INSERT INTO users (full_name, email, password, phone, address, role) VALUES
('Admin',          'admin@shop.vn',   '$2y$12$exampleHashAdmin',   '0900000000', '123 Lê Lợi, Q1, TP.HCM', 'admin'),
('Nguyễn Văn A',   'user@shop.vn',    '$2y$12$exampleHashUser',    '0911111111', '456 Nguyễn Huệ, Q1, TP.HCM', 'customer');

INSERT INTO products (category_id, name, slug, import_price, profit_rate, stock_quantity) VALUES
(1, 'iPhone 15 Pro Max 256GB', 'iphone-15-pro-max-256gb', 25000000, 0.18, 0),
(2, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 22000000, 0.20, 0),
(3, 'Xiaomi 14 Ultra',          'xiaomi-14-ultra',          15000000, 0.22, 0);

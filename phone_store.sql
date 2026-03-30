-- ============================================================
--  PHONE STORE DATABASE SCHEMA
--  Hệ thống bán điện thoại - PHP/MySQL
--  Encoding: UTF-8 | Engine: InnoDB | Version: MySQL 8.0+
-- ============================================================

CREATE DATABASE IF NOT EXISTS phone_store
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE phone_store;

-- ============================================================
-- 1. BẢNG PHÂN LOẠI SẢN PHẨM
-- ============================================================
CREATE TABLE categories (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    parent_id     INT UNSIGNED    DEFAULT NULL,                   -- Hỗ trợ danh mục cha/con
    name          VARCHAR(100)    NOT NULL,
    slug          VARCHAR(120)    NOT NULL UNIQUE,                -- URL-friendly
    description   TEXT            DEFAULT NULL,
    image_url     VARCHAR(500)    DEFAULT NULL,
    sort_order    SMALLINT        NOT NULL DEFAULT 0,
    is_active     TINYINT(1)      NOT NULL DEFAULT 1,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_parent   (parent_id),
    INDEX idx_slug     (slug),
    INDEX idx_active   (is_active),

    CONSTRAINT fk_categories_parent
        FOREIGN KEY (parent_id) REFERENCES categories (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Danh mục sản phẩm (hỗ trợ đa cấp)';


-- ============================================================
-- 2. BẢNG SẢN PHẨM
-- ============================================================
CREATE TABLE products (
    id               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    category_id      INT UNSIGNED     NOT NULL,
    sku              VARCHAR(60)      NOT NULL UNIQUE,             -- Mã sản phẩm nội bộ
    name             VARCHAR(255)     NOT NULL,
    slug             VARCHAR(280)     NOT NULL UNIQUE,
    description      TEXT             DEFAULT NULL,
    specifications   JSON             DEFAULT NULL,               -- Thông số kỹ thuật linh hoạt

    -- === GIÁ & LỢI NHUẬN ===
    import_price     DECIMAL(15,2)    NOT NULL DEFAULT 0.00
                     COMMENT 'Giá nhập hiện tại (cập nhật mỗi lần nhập hàng)',
    profit_rate      DECIMAL(5,2)     NOT NULL DEFAULT 20.00
                     COMMENT 'Tỷ lệ lợi nhuận mong muốn (%)',
    selling_price    DECIMAL(15,2)    NOT NULL DEFAULT 0.00
                     COMMENT 'Giá bán = import_price * (1 + profit_rate/100)',
    discount_price   DECIMAL(15,2)    DEFAULT NULL
                     COMMENT 'Giá khuyến mãi (NULL = không KM)',

    -- === TỒN KHO ===
    stock_quantity   INT              NOT NULL DEFAULT 0
                     COMMENT 'Số lượng tồn kho hiện tại',
    min_stock_alert  INT              NOT NULL DEFAULT 5
                     COMMENT 'Ngưỡng cảnh báo hết hàng',
    avg_import_price DECIMAL(15,2)    NOT NULL DEFAULT 0.00
                     COMMENT 'Giá nhập bình quân gia quyền (tính từ inventory_log)',

    -- === HÌNH ẢNH & TRẠNG THÁI ===
    thumbnail        VARCHAR(500)     DEFAULT NULL,
    is_active        TINYINT(1)       NOT NULL DEFAULT 1,
    is_featured      TINYINT(1)       NOT NULL DEFAULT 0,
    created_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_category      (category_id),
    INDEX idx_sku            (sku),
    INDEX idx_slug           (slug),
    INDEX idx_active_feat    (is_active, is_featured),
    INDEX idx_stock          (stock_quantity),
    INDEX idx_selling_price  (selling_price),

    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT chk_profit_rate
        CHECK (profit_rate >= 0 AND profit_rate <= 1000),
    CONSTRAINT chk_stock
        CHECK (stock_quantity >= 0),
    CONSTRAINT chk_import_price
        CHECK (import_price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sản phẩm điện thoại';


-- ============================================================
-- 3. BẢNG LỊCH SỬ NHẬP HÀNG (TÍNH GIÁ BÌNH QUÂN)
-- ============================================================
CREATE TABLE inventory_log (
    id              BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    product_id      INT UNSIGNED     NOT NULL,
    movement_type   ENUM(
                        'IMPORT',       -- Nhập hàng từ nhà cung cấp
                        'RETURN_OUT',   -- Trả hàng cho NCC
                        'ADJUSTMENT',   -- Điều chỉnh tồn kho (kiểm kê)
                        'SALE',         -- Xuất theo đơn hàng (tự động)
                        'RETURN_IN'     -- Khách trả lại
                    ) NOT NULL,
    quantity        INT              NOT NULL
                    COMMENT 'Dương = nhập vào, Âm = xuất ra',
    unit_price      DECIMAL(15,2)    NOT NULL DEFAULT 0.00
                    COMMENT 'Đơn giá nhập tại thời điểm giao dịch',
    total_cost      DECIMAL(15,2)    GENERATED ALWAYS AS (quantity * unit_price) STORED
                    COMMENT 'Thành tiền (tự tính)',

    -- Giá bình quân gia quyền SAU giao dịch này
    avg_price_after DECIMAL(15,2)    NOT NULL DEFAULT 0.00
                    COMMENT 'Giá BQ sau khi cập nhật: (tồn_cũ*giá_BQ_cũ + qty*unit_price) / tồn_mới',
    stock_after     INT              NOT NULL DEFAULT 0
                    COMMENT 'Số lượng tồn kho sau giao dịch',

    supplier        VARCHAR(200)     DEFAULT NULL,
    invoice_no      VARCHAR(100)     DEFAULT NULL
                    COMMENT 'Số phiếu nhập / xuất',
    note            TEXT             DEFAULT NULL,
    created_by      INT UNSIGNED     DEFAULT NULL
                    COMMENT 'FK → users.id (nhân viên thực hiện)',
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_product       (product_id),
    INDEX idx_movement      (movement_type),
    INDEX idx_created_at    (created_at),
    INDEX idx_invoice       (invoice_no),

    CONSTRAINT fk_invlog_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_invlog_user
        FOREIGN KEY (created_by) REFERENCES users (id)   -- Khai báo sau khi tạo users
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Lịch sử nhập/xuất kho - dùng tính giá bình quân gia quyền';
-- ⚠ FK fk_invlog_user sẽ được thêm bằng ALTER TABLE bên dưới
--    vì bảng users chưa tồn tại lúc CREATE TABLE này chạy


-- ============================================================
-- 4. BẢNG NGƯỜI DÙNG
-- ============================================================
CREATE TABLE users (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    full_name       VARCHAR(150)    NOT NULL,
    email           VARCHAR(255)    NOT NULL UNIQUE,
    phone           VARCHAR(20)     DEFAULT NULL,
    password_hash   VARCHAR(255)    NOT NULL
                    COMMENT 'Lưu hash (bcrypt/argon2), KHÔNG lưu plain text',
    role            ENUM('customer','staff','admin') NOT NULL DEFAULT 'customer',
    avatar          VARCHAR(500)    DEFAULT NULL,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    email_verified  TINYINT(1)      NOT NULL DEFAULT 0,
    last_login_at   DATETIME        DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_email     (email),
    INDEX idx_phone     (phone),
    INDEX idx_role      (role),
    INDEX idx_active    (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tài khoản người dùng (khách hàng, nhân viên, quản trị)';


-- Thêm FK bị trì hoãn (inventory_log → users)
ALTER TABLE inventory_log
    ADD CONSTRAINT fk_invlog_user
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE;


-- ============================================================
-- 5. BẢNG ĐỊA CHỈ GIAO HÀNG
-- ============================================================
CREATE TABLE user_addresses (
    id            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED   NOT NULL,
    receiver_name VARCHAR(150)   NOT NULL,
    phone         VARCHAR(20)    NOT NULL,
    province      VARCHAR(100)   NOT NULL,
    district      VARCHAR(100)   NOT NULL,
    ward          VARCHAR(100)   NOT NULL,
    street        VARCHAR(255)   NOT NULL,
    is_default    TINYINT(1)     NOT NULL DEFAULT 0,
    created_at    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_user (user_id),

    CONSTRAINT fk_address_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Địa chỉ giao hàng của khách';


-- ============================================================
-- 6. BẢNG ĐƠN HÀNG
-- ============================================================
CREATE TABLE orders (
    id               BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    order_code       VARCHAR(30)      NOT NULL UNIQUE
                     COMMENT 'Mã đơn hiển thị: ORD-YYYYMMDD-XXXXX',
    user_id          INT UNSIGNED     DEFAULT NULL
                     COMMENT 'NULL = khách vãng lai',

    -- === ĐỊA CHỈ GIAO HÀNG (lưu snapshot) ===
    receiver_name    VARCHAR(150)     NOT NULL,
    receiver_phone   VARCHAR(20)      NOT NULL,
    shipping_address TEXT             NOT NULL,

    -- === THANH TOÁN ===
    payment_method   ENUM(
                         'COD',
                         'BANK_TRANSFER',
                         'MOMO',
                         'ZALOPAY',
                         'VNPAY',
                         'CREDIT_CARD'
                     ) NOT NULL DEFAULT 'COD',
    payment_status   ENUM('pending','paid','failed','refunded')
                     NOT NULL DEFAULT 'pending',
    payment_at       DATETIME         DEFAULT NULL,

    -- === GIÁ TRỊ ===
    subtotal         DECIMAL(15,2)    NOT NULL DEFAULT 0.00
                     COMMENT 'Tổng tiền hàng (chưa phí ship, chưa giảm giá)',
    discount_amount  DECIMAL(15,2)    NOT NULL DEFAULT 0.00,
    shipping_fee     DECIMAL(15,2)    NOT NULL DEFAULT 0.00,
    total_amount     DECIMAL(15,2)    NOT NULL DEFAULT 0.00
                     COMMENT '= subtotal - discount_amount + shipping_fee',

    -- === TRẠNG THÁI ĐƠN ===
    status           ENUM(
                         'pending',       -- Chờ xác nhận
                         'confirmed',     -- Đã xác nhận
                         'processing',    -- Đang đóng gói
                         'shipping',      -- Đang giao
                         'delivered',     -- Đã giao
                         'cancelled',     -- Đã huỷ
                         'returned'       -- Trả hàng
                     ) NOT NULL DEFAULT 'pending',
    cancel_reason    VARCHAR(500)     DEFAULT NULL,
    note             TEXT             DEFAULT NULL,

    created_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_order_code    (order_code),
    INDEX idx_user          (user_id),
    INDEX idx_status        (status),
    INDEX idx_payment_stat  (payment_status),
    INDEX idx_created_at    (created_at),

    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT chk_subtotal       CHECK (subtotal >= 0),
    CONSTRAINT chk_total_amount   CHECK (total_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Đơn đặt hàng';


-- ============================================================
-- 7. BẢNG CHI TIẾT ĐƠN HÀNG
-- ============================================================
CREATE TABLE order_details (
    id               BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    order_id         BIGINT UNSIGNED  NOT NULL,
    product_id       INT UNSIGNED     NOT NULL,

    -- === SNAPSHOT GIÁ TẠI THỜI ĐIỂM MUA ===
    product_name     VARCHAR(255)     NOT NULL
                     COMMENT 'Lưu snapshot tên SP (tránh bị ảnh hưởng khi SP đổi tên)',
    product_sku      VARCHAR(60)      NOT NULL,
    unit_price       DECIMAL(15,2)    NOT NULL
                     COMMENT 'Giá bán tại lúc đặt hàng',
    import_price_at  DECIMAL(15,2)    NOT NULL DEFAULT 0.00
                     COMMENT 'Giá vốn (giá BQ) tại lúc đặt - dùng tính lãi gộp',
    discount_pct     DECIMAL(5,2)     NOT NULL DEFAULT 0.00
                     COMMENT 'Giảm giá dòng sản phẩm (%)',
    quantity         INT              NOT NULL,
    line_total       DECIMAL(15,2)    NOT NULL
                     COMMENT '= unit_price * (1 - discount_pct/100) * quantity',

    created_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_order   (order_id),
    INDEX idx_product (product_id),

    CONSTRAINT fk_od_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_od_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT chk_od_qty       CHECK (quantity > 0),
    CONSTRAINT chk_od_price     CHECK (unit_price >= 0),
    CONSTRAINT chk_od_line      CHECK (line_total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Chi tiết từng dòng sản phẩm trong đơn hàng';


-- ============================================================
-- 8. DỮ LIỆU MẪU
-- ============================================================

-- Danh mục
INSERT INTO categories (name, slug, description, sort_order) VALUES
('Điện thoại Samsung',  'dien-thoai-samsung',  'Các dòng máy Samsung', 1),
('Điện thoại iPhone',   'dien-thoai-iphone',   'iPhone chính hãng Apple', 2),
('Điện thoại Xiaomi',   'dien-thoai-xiaomi',   'Dòng máy Xiaomi / Redmi', 3);

-- Sản phẩm mẫu
INSERT INTO products
  (category_id, sku, name, slug, import_price, profit_rate, selling_price, stock_quantity, avg_import_price)
VALUES
(2, 'IPH16PM-256', 'iPhone 16 Pro Max 256GB',
 'iphone-16-pro-max-256gb', 28500000, 18.00, 33630000, 20, 28500000),

(1, 'SS-S25U-256',  'Samsung Galaxy S25 Ultra 256GB',
 'samsung-galaxy-s25-ultra-256gb', 22000000, 20.00, 26400000, 15, 22000000),

(3, 'XMI-14T-256',  'Xiaomi 14T Pro 256GB',
 'xiaomi-14t-pro-256gb', 9500000, 22.00, 11590000, 30, 9500000);

-- Người dùng mẫu (password: 'Password@123' → bcrypt)
INSERT INTO users (full_name, email, phone, password_hash, role) VALUES
('Admin Hệ Thống', 'admin@phonestore.vn',  '0901000001',
 '$2y$12$exampleHashForAdmin000000000000000000000000000000000', 'admin'),

('Nguyễn Văn An',  'an.nguyen@email.com',  '0912345678',
 '$2y$12$exampleHashForUser1000000000000000000000000000000000', 'customer');

-- Nhập kho mẫu (iPhone 16 Pro Max)
INSERT INTO inventory_log
  (product_id, movement_type, quantity, unit_price, avg_price_after, stock_after,
   supplier, invoice_no, note, created_by)
VALUES
(1, 'IMPORT', 20, 28500000, 28500000, 20,
 'Apple Việt Nam', 'PN-2025-001', 'Nhập lần đầu', 1);


-- ============================================================
-- 9. STORED PROCEDURE: TÍNH GIÁ BÌNH QUÂN GIA QUYỀN
-- ============================================================
DELIMITER $$

CREATE PROCEDURE sp_update_avg_import_price(IN p_product_id INT UNSIGNED)
BEGIN
    -- Tính giá BQ = Σ(qty_nhập * giá_nhập) / Σ(qty_nhập) chỉ cho IMPORT
    DECLARE v_avg_price  DECIMAL(15,2);
    DECLARE v_stock      INT;

    SELECT
        CASE WHEN SUM(CASE WHEN movement_type = 'IMPORT' THEN quantity ELSE 0 END) > 0
             THEN SUM(CASE WHEN movement_type = 'IMPORT' THEN quantity * unit_price ELSE 0 END)
                / SUM(CASE WHEN movement_type = 'IMPORT' THEN quantity ELSE 0 END)
             ELSE 0
        END,
        SUM(quantity)
    INTO v_avg_price, v_stock
    FROM inventory_log
    WHERE product_id = p_product_id;

    UPDATE products
    SET avg_import_price = COALESCE(v_avg_price, 0),
        stock_quantity   = COALESCE(v_stock, 0)
    WHERE id = p_product_id;
END$$

DELIMITER ;


-- ============================================================
-- 10. TRIGGER: TỰ ĐỘNG CẬP NHẬT TỒN KHO & GIÁ BQ SAU NHẬP
-- ============================================================
DELIMITER $$

CREATE TRIGGER trg_after_inventory_insert
AFTER INSERT ON inventory_log
FOR EACH ROW
BEGIN
    CALL sp_update_avg_import_price(NEW.product_id);

    -- Cập nhật import_price (giá nhập hiện tại) chỉ khi NHẬP HÀNG mới
    IF NEW.movement_type = 'IMPORT' THEN
        UPDATE products
        SET import_price  = NEW.unit_price,
            selling_price = NEW.unit_price * (1 + profit_rate / 100)
        WHERE id = NEW.product_id;
    END IF;
END$$

DELIMITER ;


-- ============================================================
-- 11. VIEW HỮU ÍCH
-- ============================================================

-- Xem lãi gộp từng dòng đơn hàng
CREATE OR REPLACE VIEW v_order_profit AS
SELECT
    o.order_code,
    o.created_at                                       AS order_date,
    od.product_sku,
    od.product_name,
    od.quantity,
    od.unit_price,
    od.import_price_at                                 AS cost_price,
    od.line_total,
    (od.unit_price - od.import_price_at) * od.quantity AS gross_profit,
    ROUND(
        (od.unit_price - od.import_price_at) / NULLIF(od.unit_price, 0) * 100,
        2
    )                                                  AS profit_pct
FROM orders o
JOIN order_details od ON od.order_id = o.id
WHERE o.status NOT IN ('cancelled', 'returned');

-- Xem sản phẩm sắp hết hàng
CREATE OR REPLACE VIEW v_low_stock AS
SELECT
    p.id, p.sku, p.name, c.name AS category,
    p.stock_quantity, p.min_stock_alert,
    p.import_price, p.avg_import_price, p.selling_price
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE p.stock_quantity <= p.min_stock_alert
  AND p.is_active = 1
ORDER BY p.stock_quantity ASC;

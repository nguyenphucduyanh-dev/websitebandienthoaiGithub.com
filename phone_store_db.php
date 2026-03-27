CREATE DATABASE IF NOT EXISTS phone_store_db;
USE phone_store_db;

-- 1. Bảng phân loại sản phẩm
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB;

-- 2. Bảng sản phẩm (Lưu ý: gia_nhap và so_luong_ton)
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    gia_nhap DECIMAL(15, 2) DEFAULT 0,        -- Giá nhập bình quân hiện tại
    ty_le_loi_nhuan DECIMAL(5, 2) DEFAULT 10, -- Ví dụ: 10 nghĩa là 10%
    so_luong_ton INT DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

-- 3. Bảng người dùng (Khách hàng)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100),
    phone VARCHAR(15),
    address TEXT,
    email VARCHAR(100)
) ENGINE=InnoDB;

-- 4. Bảng đơn hàng
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_price DECIMAL(15, 2),
    shipping_address TEXT,
    payment_method VARCHAR(50),
    status VARCHAR(50) DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- 5. Chi tiết đơn hàng
CREATE TABLE order_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    quantity INT,
    price_at_purchase DECIMAL(15, 2), -- Giá bán tại thời điểm khách mua
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

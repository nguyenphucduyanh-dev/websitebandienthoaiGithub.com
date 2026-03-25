-- 1. Danh mục sản phẩm (iPhone, iPad, ...)
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL
);

-- 2. Sản phẩm
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    stock_qty INT DEFAULT 0, -- Số lượng tồn
    import_price DECIMAL(15, 2) DEFAULT 0, -- Giá nhập bình quân
    profit_rate DECIMAL(5, 2) DEFAULT 0.1, -- Tỷ lệ lợi nhuận (vd: 0.1 là 10%)
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- 3. Người dùng
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    address TEXT,
    phone VARCHAR(15),
    email VARCHAR(100)
);

-- 4. Đơn hàng và Chi tiết đơn hàng
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(15, 2),
    shipping_address TEXT,
    payment_method VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE order_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    quantity INT,
    price DECIMAL(15, 2),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

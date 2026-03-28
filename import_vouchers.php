-- Bảng Phiếu nhập
CREATE TABLE import_vouchers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(15, 2) DEFAULT 0,
    status ENUM('draft', 'completed') DEFAULT 'draft' 
) ENGINE=InnoDB;

-- Bảng Chi tiết phiếu nhập
CREATE TABLE import_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    voucher_id INT,
    product_id INT,
    quantity INT,
    import_price DECIMAL(15, 2),
    FOREIGN KEY (voucher_id) REFERENCES import_vouchers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

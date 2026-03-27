ALTER TABLE products 
ADD COLUMN status TINYINT(1) DEFAULT 1 COMMENT '1: Hiển thị, 0: Ẩn',
ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 COMMENT '1: Đã xóa, 0: Bình thường';

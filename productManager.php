<?php
class ProductManager {
    private $db;

    public function __construct($db_connection) {
        $this->db = $db_connection;
    }

    /**
     * Cập nhật giá nhập theo quy tắc BÌNH QUÂN khi nhập hàng mới
     * Công thức: (Tồn * Giá hiện tại + Nhập mới * Giá mới) / (Tồn + Nhập mới)
     */
    public function updateStockAndPrice($productId, $newQty, $newImportPrice) {
        // 1. Lấy dữ liệu hiện tại từ Database
        $stmt = $this->db->prepare("SELECT gia_nhap, so_luong_ton FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) return false;

        $currentQty = $product['so_luong_ton'];
        $currentImportPrice = $product['gia_nhap'];

        // 2. Tính toán giá nhập bình quân mới
        $totalQty = $currentQty + $newQty;
        if ($totalQty > 0) {
            $updatedImportPrice = (($currentQty * $currentImportPrice) + ($newQty * $newImportPrice)) / $totalQty;
        } else {
            $updatedImportPrice = $newImportPrice;
        }

        // 3. Cập nhật vào Cơ sở dữ liệu
        $updateStmt = $this->db->prepare("UPDATE products SET gia_nhap = ?, so_luong_ton = ? WHERE id = ?");
        return $updateStmt->execute([$updatedImportPrice, $totalQty, $productId]);
    }

    /**
     * Tính giá bán dựa trên tỷ lệ lợi nhuận
     * Công thức: Giá bán = giá nhập * (100% + tỷ lệ lợi nhuận)
     */
    public function calculateSellingPrice($productId) {
        $stmt = $this->db->prepare("SELECT gia_nhap, ty_le_loi_nhuan FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) return 0;

        $gia_nhap = $product['gia_nhap'];
        $ty_le_loi_nhuan = $product['ty_le_loi_nhuan'] / 100; // Chuyển % về số thập phân

        $gia_ban = $gia_nhap * (1 + $ty_le_loi_nhuan);
        return $gia_ban;
    }
}

// --- VÍ DỤ SỬ DỤNG ---
/*
$pdo = new PDO("mysql:host=localhost;dbname=phone_store_db", "root", "");
$manager = new ProductManager($pdo);

// Giả sử nhập 10 máy với giá 20tr mỗi máy
$manager->updateStockAndPrice(1, 10, 20000000);

// Lấy giá bán để hiển thị ra website cho khách xem
echo "Giá bán hiện tại: " . number_format($manager->calculateSellingPrice(1)) . " VNĐ";
*/
?>

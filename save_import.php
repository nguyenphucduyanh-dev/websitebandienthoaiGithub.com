<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['status']; // draft hoặc completed
    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];
    $prices = $_POST['price'];

    try {
        $pdo->beginTransaction(); // BẮT ĐẦU TRANSACTION

        // 1. Tạo phiếu nhập
        $stmt = $pdo->prepare("INSERT INTO import_vouchers (status) VALUES (?)");
        $stmt->execute([$status]);
        $voucher_id = $pdo->lastInsertId();

        $total_amount = 0;

        // 2. Duyệt qua từng sản phẩm trong phiếu
        foreach ($product_ids as $index => $p_id) {
            $qty_new = $quantities[$index];
            $price_new = $prices[$index];
            $total_amount += ($qty_new * $price_new);

            // Lưu chi tiết phiếu nhập
            $stmt_detail = $pdo->prepare("INSERT INTO import_details (voucher_id, product_id, quantity, import_price) VALUES (?, ?, ?, ?)");
            $stmt_detail->execute([$voucher_id, $p_id, $qty_new, $price_new]);

            // 3. NẾU TRẠNG THÁI LÀ HOÀN THÀNH -> CẬP NHẬT KHO & GIÁ BÌNH QUÂN
            if ($status == 'completed') {
                // Lấy thông tin hiện tại của SP
                $stmt_p = $pdo->prepare("SELECT gia_nhap, so_luong_ton FROM products WHERE id = ? FOR UPDATE");
                $stmt_p->execute([$p_id]);
                $current = $stmt_p->fetch();

                $qty_old = $current['so_luong_ton'];
                $price_old = $current['gia_nhap'];

                // Công thức bình quân: (tồn*giá cũ + nhập*giá mới) / (tồn + nhập)
                $new_qty = $qty_old + $qty_new;
                $new_price = (($qty_old * $price_old) + ($qty_new * $price_new)) / $new_qty;

                // Cập nhật lại bảng products
                $update_p = $pdo->prepare("UPDATE products SET gia_nhap = ?, so_luong_ton = ? WHERE id = ?");
                $update_p->execute([$new_price, $new_qty, $p_id]);
            }
        }

        // Cập nhật tổng tiền cho phiếu nhập
        $pdo->prepare("UPDATE import_vouchers SET total_amount = ? WHERE id = ?")
            ->execute([$total_amount, $voucher_id]);

        $pdo->commit(); // HOÀN TẤT TRANSACTION
        echo "Cập nhật thành công!";

    } catch (Exception $e) {
        $pdo->rollBack(); // HỦY BỎ NẾU CÓ LỖI
        die("Lỗi hệ thống: " . $e->getMessage());
    }
}

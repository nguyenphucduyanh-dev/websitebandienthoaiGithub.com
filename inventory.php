<?php
// functions/inventory.php
// Nghiệp vụ nhập kho: tính giá bình quân & cập nhật sản phẩm

require_once __DIR__ . '/../config/db.php';

/**
 * Nhập hàng vào kho và cập nhật giá nhập bình quân.
 *
 * Công thức giá bình quân:
 *   new_avg = (stock_qty * current_import_price + import_qty * new_price)
 *             / (stock_qty + import_qty)
 *
 * Edge-case: stock_qty = 0  →  new_avg = new_price  (lần nhập đầu tiên)
 *
 * @param  int    $productId    ID sản phẩm
 * @param  int    $importQty    Số lượng nhập mới (> 0)
 * @param  float  $newPrice     Giá nhập lô này (> 0)
 * @param  int|null $adminId    ID admin thực hiện (ghi vào log)
 * @param  string $supplier     Tên nhà cung cấp (tuỳ chọn)
 * @param  string $note         Ghi chú (tuỳ chọn)
 *
 * @return array  ['success' => bool, 'message' => string, 'data' => array]
 */
function importStock(
    int    $productId,
    int    $importQty,
    float  $newPrice,
    ?int   $adminId   = null,
    string $supplier  = '',
    string $note      = ''
): array {
    // ── Validate đầu vào ────────────────────────────────────────────────────
    if ($importQty <= 0) {
        return ['success' => false, 'message' => 'Số lượng nhập phải lớn hơn 0.'];
    }
    if ($newPrice <= 0) {
        return ['success' => false, 'message' => 'Giá nhập phải lớn hơn 0.'];
    }

    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        // ── 1. Lấy thông tin hiện tại của sản phẩm (LOCK FOR UPDATE) ─────────
        $stmt = $pdo->prepare(
            'SELECT id, name, import_price, stock_quantity
               FROM products
              WHERE id = :id
                AND is_active = 1
              FOR UPDATE'
        );
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) {
            $pdo->rollBack();
            return ['success' => false, 'message' => "Sản phẩm ID={$productId} không tồn tại."];
        }

        $currentQty   = (int)   $product['stock_quantity'];
        $currentPrice = (float) $product['import_price'];

        // ── 2. Tính giá bình quân mới ──────────────────────────────────────
        if ($currentQty === 0) {
            // Lần nhập đầu tiên (hoặc kho đang trống) → giá mới = giá lô này
            $newAvgPrice = $newPrice;
        } else {
            $newAvgPrice = (($currentQty * $currentPrice) + ($importQty * $newPrice))
                           / ($currentQty + $importQty);
        }
        $newAvgPrice  = round($newAvgPrice, 2);
        $newStockQty  = $currentQty + $importQty;

        // ── 3. Cập nhật bảng products ─────────────────────────────────────
        $updateStmt = $pdo->prepare(
            'UPDATE products
                SET import_price   = :avg_price,
                    stock_quantity = :new_qty
              WHERE id = :id'
        );
        $updateStmt->execute([
            ':avg_price' => $newAvgPrice,
            ':new_qty'   => $newStockQty,
            ':id'        => $productId,
        ]);

        // ── 4. Ghi inventory_log ──────────────────────────────────────────
        $logStmt = $pdo->prepare(
            'INSERT INTO inventory_log
                (product_id, import_quantity, import_price,
                 stock_before, avg_price_before, avg_price_after,
                 supplier, note, created_by)
             VALUES
                (:product_id, :import_qty, :import_price,
                 :stock_before, :avg_before, :avg_after,
                 :supplier, :note, :created_by)'
        );
        $logStmt->execute([
            ':product_id'   => $productId,
            ':import_qty'   => $importQty,
            ':import_price' => $newPrice,
            ':stock_before' => $currentQty,
            ':avg_before'   => $currentPrice,
            ':avg_after'    => $newAvgPrice,
            ':supplier'     => $supplier,
            ':note'         => $note,
            ':created_by'   => $adminId,
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'message' => "Nhập kho thành công cho sản phẩm \"{$product['name']}\".",
            'data'    => [
                'product_id'      => $productId,
                'imported_qty'    => $importQty,
                'import_price'    => $newPrice,
                'new_avg_price'   => $newAvgPrice,
                'new_stock_qty'   => $newStockQty,
                'selling_price'   => getSellingPrice($productId, $newAvgPrice),
            ],
        ];

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('[importStock Error] ' . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống khi nhập kho.'];
    }
}

/**
 * Tính giá bán từ giá nhập và tỷ lệ lợi nhuận.
 *
 * Có thể truyền $overrideImportPrice để tính trước khi lưu DB.
 *
 * selling_price = import_price * (1 + profit_rate)
 *
 * @param  int        $productId         ID sản phẩm
 * @param  float|null $overrideImportPrice Ghi đè giá nhập (tuỳ chọn)
 * @return float      Giá bán (làm tròn đến đồng)
 */
function getSellingPrice(int $productId, ?float $overrideImportPrice = null): float
{
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT import_price, profit_rate FROM products WHERE id = :id'
    );
    $stmt->execute([':id' => $productId]);
    $row = $stmt->fetch();

    if (!$row) {
        return 0.0;
    }

    $importPrice = $overrideImportPrice ?? (float) $row['import_price'];
    $profitRate  = (float) $row['profit_rate'];

    return round($importPrice * (1 + $profitRate), 0); // làm tròn đến 1 đồng
}

/**
 * Tiện ích: Lấy lịch sử nhập kho của một sản phẩm.
 *
 * @param int $productId
 * @param int $limit
 * @return array
 */
function getInventoryLog(int $productId, int $limit = 20): array
{
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT il.*, u.full_name AS admin_name
           FROM inventory_log il
      LEFT JOIN users u ON u.id = il.created_by
          WHERE il.product_id = :pid
       ORDER BY il.created_at DESC
          LIMIT :lim'
    );
    $stmt->bindValue(':pid', $productId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit,     PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

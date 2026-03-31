<?php
/**
 * function.php - Các hàm nghiệp vụ
 * Tác giả: nguyenphucduyanh-dev
 */

require_once __DIR__ . '/db.php';

// ============================================================
// KHO HÀNG
// ============================================================

/**
 * Nhập hàng vào kho, cập nhật import_price theo công thức bình quân.
 *
 * @param int   $product_id    ID sản phẩm
 * @param int   $qty_in        Số lượng nhập mới
 * @param float $new_price     Giá nhập mới (đơn vị đồng)
 * @param string $note         Ghi chú (tùy chọn)
 * @return array ['success'=>bool, 'message'=>string, 'new_avg'=>float]
 */
function importStock(int $product_id, int $qty_in, float $new_price, string $note = ''): array
{
    if ($qty_in <= 0 || $new_price <= 0) {
        return ['success' => false, 'message' => 'Số lượng và giá nhập phải lớn hơn 0.'];
    }

    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        // Lấy thông tin tồn kho hiện tại
        $stmt = $pdo->prepare("SELECT stock_quantity, import_price FROM products WHERE id = ? FOR UPDATE");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Sản phẩm không tồn tại.'];
        }

        $current_qty   = (int)   $product['stock_quantity'];
        $current_price = (float) $product['import_price'];

        // Tính giá bình quân
        // Nếu tồn = 0 (lần nhập đầu) => giá mới chính là new_price
        if ($current_qty === 0) {
            $new_avg = $new_price;
        } else {
            $new_avg = ($current_qty * $current_price + $qty_in * $new_price)
                     / ($current_qty + $qty_in);
        }

        $new_avg = round($new_avg, 2);
        $new_qty = $current_qty + $qty_in;

        // Cập nhật bảng products
        $update = $pdo->prepare(
            "UPDATE products SET import_price = ?, stock_quantity = ? WHERE id = ?"
        );
        $update->execute([$new_avg, $new_qty, $product_id]);

        // Ghi log nhập hàng
        $log = $pdo->prepare(
            "INSERT INTO inventory_log (product_id, quantity_in, unit_price, avg_price_after, note)
             VALUES (?, ?, ?, ?, ?)"
        );
        $log->execute([$product_id, $qty_in, $new_price, $new_avg, $note]);

        $pdo->commit();
        return [
            'success'  => true,
            'message'  => "Nhập hàng thành công. Giá bình quân mới: " . number_format($new_avg, 0, ',', '.') . " đ",
            'new_avg'  => $new_avg,
            'new_stock'=> $new_qty,
        ];
    } catch (PDOException $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()];
    }
}

/**
 * Tính giá bán (selling_price) = import_price * (1 + profit_rate)
 *
 * @param float $import_price
 * @param float $profit_rate  (vd: 0.20 = 20%)
 * @return float
 */
function getSellingPrice(float $import_price, float $profit_rate): float
{
    return round($import_price * (1 + $profit_rate), 2);
}

/**
 * Lấy selling_price trực tiếp từ product_id
 */
function getSellingPriceById(int $product_id): float
{
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare("SELECT import_price, profit_rate FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $row  = $stmt->fetch();
    if (!$row) return 0.0;
    return getSellingPrice((float)$row['import_price'], (float)$row['profit_rate']);
}

// ============================================================
// GIỎ HÀNG (SESSION)
// ============================================================

function cartInit(): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
}

/**
 * Thêm sản phẩm vào giỏ. Trả về lỗi nếu chưa đăng nhập.
 */
function cartAdd(int $product_id, int $qty = 1): array
{
    cartInit();
    if (empty($_SESSION['user'])) {
        return ['success' => false, 'message' => 'Vui lòng đăng nhập để mua hàng.'];
    }

    $pdo  = getDBConnection();
    $stmt = $pdo->prepare(
        "SELECT id, name, image, import_price, profit_rate, stock_quantity FROM products WHERE id = ? AND is_active = 1"
    );
    $stmt->execute([$product_id]);
    $p = $stmt->fetch();

    if (!$p) return ['success' => false, 'message' => 'Sản phẩm không tồn tại.'];

    $sell_price = getSellingPrice((float)$p['import_price'], (float)$p['profit_rate']);

    if (isset($_SESSION['cart'][$product_id])) {
        $new_qty = $_SESSION['cart'][$product_id]['qty'] + $qty;
        if ($new_qty > $p['stock_quantity']) {
            return ['success' => false, 'message' => 'Vượt quá số lượng tồn kho.'];
        }
        $_SESSION['cart'][$product_id]['qty'] = $new_qty;
    } else {
        if ($qty > $p['stock_quantity']) {
            return ['success' => false, 'message' => 'Vượt quá số lượng tồn kho.'];
        }
        $_SESSION['cart'][$product_id] = [
            'product_id' => $product_id,
            'name'       => $p['name'],
            'image'      => $p['image'],
            'price'      => $sell_price,
            'qty'        => $qty,
        ];
    }
    return ['success' => true, 'message' => 'Đã thêm vào giỏ hàng.', 'cart_count' => cartCount()];
}

function cartUpdate(int $product_id, int $qty): void
{
    cartInit();
    if ($qty <= 0) {
        unset($_SESSION['cart'][$product_id]);
    } else {
        $_SESSION['cart'][$product_id]['qty'] = $qty;
    }
}

function cartRemove(int $product_id): void
{
    cartInit();
    unset($_SESSION['cart'][$product_id]);
}

function cartClear(): void
{
    cartInit();
    $_SESSION['cart'] = [];
}

function cartItems(): array
{
    cartInit();
    return $_SESSION['cart'] ?? [];
}

function cartCount(): int
{
    cartInit();
    return array_sum(array_column($_SESSION['cart'] ?? [], 'qty'));
}

function cartTotal(): float
{
    $total = 0;
    foreach (cartItems() as $item) {
        $total += $item['price'] * $item['qty'];
    }
    return $total;
}

// ============================================================
// ĐẶT HÀNG
// ============================================================

/**
 * Tạo đơn hàng từ giỏ hàng hiện tại.
 *
 * @param int    $user_id
 * @param string $shipping_address
 * @param string $payment_method   'cash'|'transfer'|'online'
 * @param string $note
 * @return array ['success'=>bool, 'order_id'=>int|null, 'message'=>string]
 */
function placeOrder(int $user_id, string $shipping_address, string $payment_method, string $note = ''): array
{
    $items = cartItems();
    if (empty($items)) {
        return ['success' => false, 'message' => 'Giỏ hàng trống.'];
    }

    $pdo = getDBConnection();
    try {
        $pdo->beginTransaction();

        $total = cartTotal();

        $stmt = $pdo->prepare(
            "INSERT INTO orders (user_id, shipping_address, payment_method, total_amount, note)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$user_id, $shipping_address, $payment_method, $total, $note]);
        $order_id = (int) $pdo->lastInsertId();

        $detail_stmt = $pdo->prepare(
            "INSERT INTO order_details (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)"
        );
        $stock_stmt = $pdo->prepare(
            "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?"
        );

        foreach ($items as $item) {
            $detail_stmt->execute([$order_id, $item['product_id'], $item['qty'], $item['price']]);
            $stock_stmt->execute([$item['qty'], $item['product_id'], $item['qty']]);
            if ($stock_stmt->rowCount() === 0) {
                $pdo->rollBack();
                return ['success' => false, 'message' => "Sản phẩm '{$item['name']}' không đủ hàng."];
            }
        }

        $pdo->commit();
        cartClear();
        return ['success' => true, 'order_id' => $order_id, 'message' => 'Đặt hàng thành công!'];
    } catch (PDOException $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()];
    }
}

// ============================================================
// HELPERS
// ============================================================

function isLoggedIn(): bool
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    return !empty($_SESSION['user']);
}

function currentUser(): ?array
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['user'] ?? null;
}

function formatVND(float $amount): string
{
    return number_format($amount, 0, ',', '.') . ' đ';
}

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

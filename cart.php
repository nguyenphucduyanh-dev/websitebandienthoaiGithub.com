<?php
/**
 * cart.php - Giỏ hàng (Session + AJAX)
 * Tác giả: nguyenphucduyanh-dev
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/function.php';

cartInit();

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$action     = $_GET['action'] ?? $_POST['action'] ?? '';
$product_id = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
$qty        = max(1, (int)($_POST['qty'] ?? 1));

// ====== Xử lý hành động ======
if ($action === 'add') {
    $result = cartAdd($product_id, $qty);
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    header('Location: cart.php');
    exit;
}

if ($action === 'update' && $product_id > 0) {
    cartUpdate($product_id, $qty);
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'total' => cartTotal(), 'cart_count' => cartCount()]);
        exit;
    }
    header('Location: cart.php');
    exit;
}

if ($action === 'remove' && $product_id > 0) {
    cartRemove($product_id);
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'total' => cartTotal(), 'cart_count' => cartCount()]);
        exit;
    }
    header('Location: cart.php');
    exit;
}

// ====== Hiển thị giỏ hàng ======
$items = cartItems();
$total = cartTotal();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Giỏ hàng</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4">🛒 Giỏ hàng của bạn</h2>

    <?php if (empty($items)): ?>
        <div class="alert alert-info">Giỏ hàng trống. <a href="search.php">Tiếp tục mua sắm</a></div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table align-middle" id="cartTable">
        <thead class="table-light">
            <tr>
                <th>Sản phẩm</th>
                <th width="160">Giá bán</th>
                <th width="160">Số lượng</th>
                <th width="160">Thành tiền</th>
                <th width="80"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $pid => $item): ?>
        <tr data-id="<?= $pid ?>">
            <td>
                <div class="d-flex align-items-center gap-3">
                    <img src="<?= e($item['image'] ?: 'assets/images/no-image.png') ?>"
                         width="60" alt="<?= e($item['name']) ?>">
                    <span><?= e($item['name']) ?></span>
                </div>
            </td>
            <td class="item-price"><?= formatVND($item['price']) ?></td>
            <td>
                <input type="number" class="form-control form-control-sm qty-input"
                       value="<?= $item['qty'] ?>" min="1" style="width:80px"
                       data-id="<?= $pid ?>" data-price="<?= $item['price'] ?>">
            </td>
            <td class="item-subtotal fw-bold text-danger">
                <?= formatVND($item['price'] * $item['qty']) ?>
            </td>
            <td>
                <button class="btn btn-outline-danger btn-sm btn-remove" data-id="<?= $pid ?>">✕</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <a href="search.php" class="btn btn-outline-secondary">← Tiếp tục mua</a>
        <div class="text-end">
            <p class="fs-5 mb-2">Tổng cộng: <strong class="text-danger" id="cartTotal"><?= formatVND($total) ?></strong></p>
            <?php if (isLoggedIn()): ?>
                <a href="checkout.php" class="btn btn-danger btn-lg">Đặt hàng ngay</a>
            <?php else: ?>
                <a href="login.php?redirect=checkout.php" class="btn btn-danger btn-lg">Đặt hàng ngay</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function formatVND(n) {
    return new Intl.NumberFormat('vi-VN').format(Math.round(n)) + ' đ';
}

// Cập nhật số lượng
document.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('change', function() {
        const id    = this.dataset.id;
        const qty   = parseInt(this.value);
        const price = parseFloat(this.dataset.price);
        const row   = document.querySelector(`tr[data-id="${id}"]`);

        fetch('cart.php?action=update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `product_id=${id}&qty=${qty}`
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                row.querySelector('.item-subtotal').textContent = formatVND(price * qty);
                document.getElementById('cartTotal').textContent = formatVND(d.total);
            }
        });
    });
});

// Xóa sản phẩm
document.querySelectorAll('.btn-remove').forEach(btn => {
    btn.addEventListener('click', function() {
        const id  = this.dataset.id;
        const row = document.querySelector(`tr[data-id="${id}"]`);
        fetch(`cart.php?action=remove&product_id=${id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                row.remove();
                document.getElementById('cartTotal').textContent = formatVND(d.total);
                if (d.cart_count === 0) location.reload();
            }
        });
    });
});
</script>
</body>
</html>

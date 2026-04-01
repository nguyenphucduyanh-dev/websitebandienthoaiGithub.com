<?php
// order_summary.php  —  Trang xác nhận / tóm tắt đơn hàng sau khi đặt thành công

session_start();
require_once __DIR__ . '/config/db.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
if (!$orderId) {
    header('Location: products.php');
    exit;
}

$pdo = getDB();

// Lấy đơn hàng (phải thuộc user đang đăng nhập)
$orderStmt = $pdo->prepare(
    'SELECT o.*, u.full_name, u.email, u.phone
       FROM orders o
       JOIN users  u ON u.id = o.user_id
      WHERE o.id = :oid AND o.user_id = :uid'
);
$orderStmt->execute([':oid' => $orderId, ':uid' => $_SESSION['user']['id']]);
$order = $orderStmt->fetch();

if (!$order) {
    http_response_code(403);
    die('Không tìm thấy đơn hàng.');
}

// Lấy chi tiết đơn hàng
$detailStmt = $pdo->prepare(
    'SELECT od.*, p.name AS product_name, p.image, p.slug
       FROM order_details od
       JOIN products       p  ON p.id = od.product_id
      WHERE od.order_id = :oid'
);
$detailStmt->execute([':oid' => $orderId]);
$details = $detailStmt->fetchAll();

$paymentLabels = [
    'COD'           => '💵 Tiền mặt khi nhận hàng',
    'BANK_TRANSFER' => '🏦 Chuyển khoản ngân hàng',
    'ONLINE'        => '📱 Thanh toán Online',
];
$statusLabels = [
    'pending'   => ['Chờ xác nhận', '#f4a261'],
    'confirmed' => ['Đã xác nhận',  '#2dc653'],
    'shipping'  => ['Đang giao',    '#4361ee'],
    'delivered' => ['Đã giao',      '#2dc653'],
    'cancelled' => ['Đã huỷ',       '#e63946'],
];
[$statusText, $statusColor] = $statusLabels[$order['order_status']] ?? ['N/A', '#888'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Xác nhận đơn hàng #<?= $orderId ?> — Phone Shop</title>
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Segoe UI',sans-serif;background:#f5f6fa;color:#333}
  .container{max-width:760px;margin:0 auto;padding:0 16px 48px}
  header{background:#1a1a2e;color:#fff;padding:16px 0;margin-bottom:28px}
  header h1{font-size:1.3rem}

  /* Success banner */
  .success-banner{background:linear-gradient(135deg,#2dc653,#1ea843);
    color:#fff;border-radius:12px;padding:28px 32px;text-align:center;
    margin-bottom:28px;box-shadow:0 4px 16px rgba(45,198,83,.3)}
  .success-banner .icon{font-size:3rem;margin-bottom:8px}
  .success-banner h2{font-size:1.4rem;font-weight:700;margin-bottom:4px}
  .success-banner p{opacity:.9;font-size:.95rem}

  /* Cards */
  .card{background:#fff;border-radius:10px;padding:22px 26px;margin-bottom:18px;
    box-shadow:0 2px 8px rgba(0,0,0,.07)}
  .card h3{font-size:.95rem;font-weight:700;color:#555;text-transform:uppercase;
    letter-spacing:.8px;margin-bottom:14px;padding-bottom:10px;
    border-bottom:1px solid #f0f0f0}

  /* Info grid */
  .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  @media(max-width:500px){.info-grid{grid-template-columns:1fr}}
  .info-item label{display:block;font-size:.75rem;color:#aaa;
    text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px}
  .info-item p{font-size:.92rem;font-weight:600}

  /* Status badge */
  .badge{display:inline-block;padding:3px 12px;border-radius:20px;
    font-size:.78rem;font-weight:700;color:#fff}

  /* Product rows */
  .item-row{display:flex;gap:14px;padding:10px 0;
    border-bottom:1px solid #f5f5f5;align-items:center}
  .item-row:last-child{border:none}
  .item-row img{width:56px;height:56px;object-fit:cover;border-radius:8px;
    background:#eee;flex-shrink:0}
  .item-name{font-size:.9rem;font-weight:600;flex:1}
  .item-qty{font-size:.8rem;color:#888}
  .item-price{font-size:.9rem;font-weight:700;color:#333;white-space:nowrap}

  /* Total */
  .total-line{display:flex;justify-content:space-between;
    font-size:1.15rem;font-weight:700;padding-top:14px;
    margin-top:6px;border-top:2px solid #eee;color:#e63946}

  /* CTA */
  .cta-group{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}
  .btn{padding:11px 24px;border-radius:8px;font-size:.9rem;
    font-weight:600;text-align:center;cursor:pointer;
    text-decoration:none;border:none;display:inline-block;transition:all .2s}
  .btn-primary{background:#4361ee;color:#fff}
  .btn-primary:hover{background:#3451d1}
  .btn-outline{background:#fff;color:#4361ee;border:1.5px solid #4361ee}
  .btn-outline:hover{background:#f0f2ff}
</style>
</head>
<body>
<header>
  <div class="container"><h1>🛒 Phone Shop</h1></div>
</header>
<div class="container">

  <!-- ── Banner xác nhận ── -->
  <div class="success-banner">
    <div class="icon">🎉</div>
    <h2>Đặt hàng thành công!</h2>
    <p>Đơn hàng <strong>#<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?></strong>
       của bạn đã được ghi nhận. Chúng tôi sẽ liên hệ sớm nhất!</p>
  </div>

  <!-- ── Thông tin đơn hàng ── -->
  <div class="card">
    <h3>Thông tin đơn hàng</h3>
    <div class="info-grid">
      <div class="info-item">
        <label>Mã đơn hàng</label>
        <p>#<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?></p>
      </div>
      <div class="info-item">
        <label>Thời gian đặt</label>
        <p><?= date('H:i, d/m/Y', strtotime($order['created_at'])) ?></p>
      </div>
      <div class="info-item">
        <label>Trạng thái</label>
        <p><span class="badge" style="background:<?= $statusColor ?>">
          <?= $statusText ?></span></p>
      </div>
      <div class="info-item">
        <label>Thanh toán</label>
        <p><?= $paymentLabels[$order['payment_method']] ?? $order['payment_method'] ?></p>
      </div>
    </div>
  </div>

  <!-- ── Thông tin người nhận ── -->
  <div class="card">
    <h3>Người nhận hàng</h3>
    <div class="info-grid">
      <div class="info-item">
        <label>Họ tên</label>
        <p><?= htmlspecialchars($order['full_name']) ?></p>
      </div>
      <div class="info-item">
        <label>Số điện thoại</label>
        <p><?= htmlspecialchars($order['phone'] ?? '—') ?></p>
      </div>
      <div class="info-item" style="grid-column:1/-1">
        <label>Địa chỉ giao hàng</label>
        <p>📍 <?= htmlspecialchars($order['shipping_address']) ?></p>
      </div>
    </div>
    <?php if ($order['note']): ?>
    <div style="margin-top:12px;padding:10px 14px;background:#f8f9ff;
                border-radius:6px;font-size:.85rem;color:#555">
      📝 <em><?= htmlspecialchars($order['note']) ?></em>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── Sản phẩm đã đặt ── -->
  <div class="card">
    <h3>Sản phẩm đã đặt</h3>
    <?php foreach ($details as $d): ?>
    <div class="item-row">
      <?php if ($d['image']): ?>
        <img src="<?= htmlspecialchars($d['image']) ?>"
             alt="<?= htmlspecialchars($d['product_name']) ?>">
      <?php else: ?>
        <div style="width:56px;height:56px;background:#f0f0f0;border-radius:8px;
                    display:flex;align-items:center;justify-content:center">📱</div>
      <?php endif; ?>
      <div class="item-name">
        <a href="product_detail.php?slug=<?= htmlspecialchars($d['slug']) ?>"
           style="color:#333"><?= htmlspecialchars($d['product_name']) ?></a>
        <p class="item-qty">× <?= $d['quantity'] ?></p>
      </div>
      <div class="item-price"><?= number_format($d['subtotal'], 0, ',', '.') ?>₫</div>
    </div>
    <?php endforeach; ?>

    <div class="total-line">
      <span>Tổng thanh toán</span>
      <span><?= number_format($order['total_amount'], 0, ',', '.') ?>₫</span>
    </div>
  </div>

  <!-- ── CTA ── -->
  <div class="cta-group">
    <a href="products.php"        class="btn btn-outline">← Tiếp tục mua sắm</a>
    <a href="order_history.php"   class="btn btn-primary">📋 Xem lịch sử đơn hàng</a>
  </div>

</div><!-- /container -->
</body>
</html>

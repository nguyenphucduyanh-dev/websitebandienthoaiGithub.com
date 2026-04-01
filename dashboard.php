<?php
// ============================================================
//  admin/dashboard.php  –  Trang tổng quan
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$page_title  = 'Dashboard';
$active_menu = 'dashboard';
$pdo         = get_db();

// ── Thống kê nhanh ────────────────────────────────────────────
$stats = [];

// Tổng doanh thu hôm nay
$row = $pdo->query(
    "SELECT COALESCE(SUM(total_amount),0) AS v
     FROM orders WHERE status='completed'
     AND DATE(created_at)=CURDATE()"
)->fetch();
$stats['today_revenue'] = (float)$row['v'];

// Đơn hàng chờ duyệt
$stats['pending_orders'] = (int)$pdo->query(
    "SELECT COUNT(*) FROM orders WHERE status='pending'"
)->fetchColumn();

// Tổng sản phẩm đang bán
$stats['active_products'] = (int)$pdo->query(
    "SELECT COUNT(*) FROM products WHERE status=1"
)->fetchColumn();

// Tổng người dùng
$stats['total_users'] = (int)$pdo->query(
    "SELECT COUNT(*) FROM users WHERE role='customer'"
)->fetchColumn();

// Doanh thu 7 ngày gần nhất (biểu đồ sparkline)
$revenue7d = $pdo->query(
    "SELECT DATE(created_at) AS d, SUM(total_amount) AS total
     FROM orders
     WHERE status='completed'
       AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(created_at)
     ORDER BY d"
)->fetchAll();

// Đơn hàng mới nhất (10 đơn)
$recent_orders = $pdo->query(
    "SELECT o.id, o.order_code, o.status, o.total_amount,
            o.created_at, u.username
     FROM orders o
     JOIN users  u ON u.id = o.user_id
     ORDER BY o.created_at DESC
     LIMIT 10"
)->fetchAll();

// Sản phẩm sắp hết hàng (≤ 5)
$low_stock = $pdo->query(
    "SELECT id, name, stock_quantity FROM products
     WHERE status=1 AND stock_quantity <= 5
     ORDER BY stock_quantity LIMIT 8"
)->fetchAll();

// Nhãn trạng thái
$status_labels = [
    'pending'   => ['Chờ duyệt',   'badge-pending'],
    'confirmed' => ['Đã xác nhận', 'badge-confirmed'],
    'shipping'  => ['Đang giao',   'badge-shipping'],
    'completed' => ['Thành công',  'badge-completed'],
    'cancelled' => ['Đã hủy',      'badge-cancelled'],
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── STAT CARDS ───────────────────────────── -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-graph-up-arrow"></i></div>
      <div>
        <div class="stat-label">Doanh thu hôm nay</div>
        <div class="stat-value"><?= vnd($stats['today_revenue']) ?></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div>
      <div>
        <div class="stat-label">Đơn chờ duyệt</div>
        <div class="stat-value"><?= $stats['pending_orders'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-phone"></i></div>
      <div>
        <div class="stat-label">Sản phẩm đang bán</div>
        <div class="stat-value"><?= $stats['active_products'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon red"><i class="bi bi-people"></i></div>
      <div>
        <div class="stat-label">Tổng khách hàng</div>
        <div class="stat-value"><?= $stats['total_users'] ?></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- ── Đơn hàng mới nhất ── -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-bag-check me-2"></i>Đơn hàng gần đây</span>
        <a href="/admin/orders/index.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Mã đơn</th>
              <th>Khách hàng</th>
              <th>Ngày đặt</th>
              <th>Tổng tiền</th>
              <th>Trạng thái</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($recent_orders as $o): ?>
            <?php [$label, $badge] = $status_labels[$o['status']] ?? ['Unknown', '']; ?>
            <tr>
              <td>
                <a href="/admin/orders/index.php?id=<?= $o['id'] ?>" class="mono text-decoration-none fw-600">
                  <?= e($o['order_code']) ?>
                </a>
              </td>
              <td><?= e($o['username']) ?></td>
              <td class="text-muted" style="font-size:.8rem">
                <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?>
              </td>
              <td class="fw-600"><?= vnd((float)$o['total_amount']) ?></td>
              <td><span class="badge-status <?= $badge ?>"><?= $label ?></span></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($recent_orders)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Chưa có đơn hàng nào</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ── Sản phẩm sắp hết ── -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Sắp hết hàng</span>
        <a href="/admin/inventory/report.php" class="btn btn-sm btn-outline-warning">Báo cáo</a>
      </div>
      <div class="p-3">
        <?php if (empty($low_stock)): ?>
          <p class="text-muted text-center py-3 mb-0">Kho hàng ổn định 👍</p>
        <?php else: ?>
          <?php foreach ($low_stock as $p): ?>
          <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
            <div style="font-size:.85rem;font-weight:500"><?= e($p['name']) ?></div>
            <span class="badge <?= $p['stock_quantity'] == 0 ? 'bg-danger' : 'bg-warning text-dark' ?>">
              <?= $p['stock_quantity'] ?> máy
            </span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Biểu đồ doanh thu 7 ngày -->
    <div class="card mt-3">
      <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Doanh thu 7 ngày</div>
      <div class="p-3">
        <canvas id="revenueChart" height="140"></canvas>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
const r7d = <?= json_encode($revenue7d) ?>;

// Tạo đủ 7 ngày
const labels = [], data = [];
for (let i = 6; i >= 0; i--) {
  const d = new Date(); d.setDate(d.getDate() - i);
  const key = d.toISOString().slice(0, 10);
  labels.push(key.slice(5));   // MM-DD
  const found = r7d.find(x => x.d === key);
  data.push(found ? Number(found.total) : 0);
}

new Chart(document.getElementById('revenueChart'), {
  type: 'bar',
  data: {
    labels,
    datasets: [{
      data,
      backgroundColor: 'rgba(79,142,247,.7)',
      borderRadius: 6,
      borderSkipped: false,
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false }, ticks: { font: { size: 10 } } },
      y: { grid: { color: '#f1f5f9' }, ticks: {
        callback: v => (v/1e6).toFixed(1) + 'M',
        font: { size: 10 }
      }}
    }
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

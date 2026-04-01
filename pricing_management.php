<?php
// ============================================================
//  admin/pricing/index.php  –  Quản lý giá & Lịch sử giá vốn
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title  = 'Quản lý giá';
$active_menu = 'pricing';
$pdo         = get_db();

// ── Cập nhật profit_rate ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profit'])) {
    $updates = $_POST['profit'] ?? [];
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "UPDATE products SET profit_rate=?, updated_at=NOW() WHERE id=?"
        );
        foreach ($updates as $id => $rate) {
            $rate = (float)str_replace('%', '', $rate) / 100;
            if ($rate >= 0 && $rate <= 10) {
                $stmt->execute([$rate, (int)$id]);
            }
        }
        $pdo->commit();
        $_SESSION['flash_success'] = 'Cập nhật tỷ lệ lợi nhuận thành công.';
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = $e->getMessage();
    }
    header('Location: /admin/pricing/index.php'); exit;
}

// ── Lịch sử giá vốn của 1 sản phẩm ──────────────────────────
$history_product_id = (int)($_GET['history'] ?? 0);
$history_data       = [];
if ($history_product_id > 0) {
    $hstmt = $pdo->prepare(
        "SELECT il.*, po.code AS po_code, po.import_date, po.supplier
         FROM inventory_log il
         LEFT JOIN purchase_orders po ON po.id = il.reference_id
                                      AND il.reference_type = 'purchase_order'
         WHERE il.product_id = ? AND il.change_type = 'import'
         ORDER BY il.created_at DESC"
    );
    $hstmt->execute([$history_product_id]);
    $history_data = $hstmt->fetchAll();
}

// ── Danh sách sản phẩm ───────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$where  = $search ? "WHERE p.name LIKE ?" : "";
$params = $search ? ["%{$search}%"] : [];

$list_stmt = $pdo->prepare(
    "SELECT p.id, p.name, p.import_price, p.profit_rate, p.stock_quantity,
            c.name AS category_name,
            ROUND(p.import_price * (1 + p.profit_rate)) AS selling_price,
            (SELECT COUNT(*) FROM inventory_log
             WHERE product_id=p.id AND change_type='import') AS import_count
     FROM products p
     JOIN categories c ON c.id = p.category_id
     {$where}
     WHERE p.status = 1
     ORDER BY c.name, p.name"
);

// Rebuild WHERE properly
$sql = "SELECT p.id, p.name, p.import_price, p.profit_rate, p.stock_quantity,
               c.name AS category_name,
               ROUND(p.import_price * (1 + p.profit_rate)) AS selling_price,
               (SELECT COUNT(*) FROM inventory_log il2
                WHERE il2.product_id=p.id AND il2.change_type='import') AS import_count
        FROM products p
        JOIN categories c ON c.id = p.category_id
        " . ($search ? "WHERE p.name LIKE ? AND p.status=1" : "WHERE p.status=1") . "
        ORDER BY c.name, p.name";

$list_stmt = $pdo->prepare($sql);
$list_stmt->execute($params);
$products = $list_stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="mb-0 fw-700"><?= e($page_title) ?></h5>
</div>

<div class="row g-3">
  <!-- ── Danh sách giá ── -->
  <div class="col-lg-<?= $history_product_id ? '7' : '12' ?>">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-tag me-2"></i>Danh sách sản phẩm & giá</span>
        <form method="GET" class="d-flex gap-2">
          <input type="text" name="q" class="form-control form-control-sm"
                 placeholder="Tìm sản phẩm..." value="<?= e($search) ?>" style="width:220px">
          <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
        </form>
      </div>
      <form method="POST">
        <input type="hidden" name="update_profit" value="1">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Sản phẩm</th>
                <th>Danh mục</th>
                <th>Giá vốn</th>
                <th>LN (%)</th>
                <th>Giá bán</th>
                <th>Tồn</th>
                <th>Lịch sử</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
              <tr>
                <td class="fw-500" style="font-size:.85rem"><?= e($p['name']) ?></td>
                <td class="text-muted" style="font-size:.78rem"><?= e($p['category_name']) ?></td>
                <td class="mono"><?= vnd((float)$p['import_price']) ?></td>
                <td style="width:110px">
                  <div class="input-group input-group-sm">
                    <input type="number" name="profit[<?= $p['id'] ?>]"
                           class="form-control profit-input"
                           data-import="<?= $p['import_price'] ?>"
                           value="<?= number_format($p['profit_rate']*100, 1) ?>"
                           step="0.1" min="0" max="500"
                           onchange="previewSelling(this)">
                    <span class="input-group-text">%</span>
                  </div>
                </td>
                <td class="mono selling-cell text-primary fw-600"
                    id="sell_<?= $p['id'] ?>">
                  <?= vnd((float)$p['selling_price']) ?>
                </td>
                <td>
                  <span class="badge <?= $p['stock_quantity']<=5?'bg-danger':'bg-light text-dark border' ?>">
                    <?= $p['stock_quantity'] ?>
                  </span>
                </td>
                <td>
                  <a href="?history=<?= $p['id'] ?><?= $search?"&q=".urlencode($search):'' ?>"
                     class="btn btn-sm <?= $history_product_id==$p['id']?'btn-primary':'btn-outline-secondary' ?> py-0 px-2"
                     title="Lịch sử giá vốn">
                    <i class="bi bi-clock-history"></i>
                    <?php if ($p['import_count'] > 0): ?>
                      <span class="badge bg-secondary" style="font-size:.65rem"><?= $p['import_count'] ?></span>
                    <?php endif; ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="p-3 border-top">
          <button type="submit" class="btn btn-sm btn-primary">
            <i class="bi bi-check-lg me-1"></i>Lưu tỷ lệ lợi nhuận
          </button>
          <small class="text-muted ms-2">Thay đổi % LN sẽ ảnh hưởng giá bán ngay lập tức.</small>
        </div>
      </form>
    </div>
  </div>

  <!-- ── Lịch sử giá vốn ── -->
  <?php if ($history_product_id && !empty($history_data)): ?>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2"></i>Lịch sử giá vốn</span>
        <a href="?q=<?= urlencode($search) ?>" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-x"></i>
        </a>
      </div>
      <div class="p-3">
        <p class="text-muted mb-3" style="font-size:.82rem">
          Sản phẩm: <strong><?= e($products[array_search($history_product_id, array_column($products,'id'))]['name'] ?? '') ?></strong>
        </p>
        <div class="timeline">
          <?php foreach ($history_data as $h): ?>
          <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
            <div style="min-width:36px;text-align:center">
              <div style="width:10px;height:10px;border-radius:50%;background:#4f8ef7;
                          margin:5px auto 0"></div>
              <div style="width:1px;background:#e2e8f0;height:calc(100% - 20px);
                          margin:4px auto 0"></div>
            </div>
            <div class="flex-1">
              <div class="d-flex justify-content-between">
                <strong class="mono" style="font-size:.82rem">
                  <?= $h['po_code'] ? e($h['po_code']) : 'Điều chỉnh' ?>
                </strong>
                <small class="text-muted">
                  <?= $h['import_date'] ? date('d/m/Y', strtotime($h['import_date'])) : date('d/m/Y', strtotime($h['created_at'])) ?>
                </small>
              </div>
              <?php if ($h['supplier']): ?>
              <small class="text-muted"><?= e($h['supplier']) ?></small>
              <?php endif; ?>
              <div class="row mt-1" style="font-size:.8rem">
                <div class="col-6">
                  <span class="text-muted">Số lượng nhập:</span><br>
                  <strong>+<?= $h['quantity_change'] ?> máy</strong>
                </div>
                <div class="col-6">
                  <span class="text-muted">Giá lô này:</span><br>
                  <strong class="mono"><?= vnd((float)$h['import_price']) ?></strong>
                </div>
                <div class="col-6 mt-1">
                  <span class="text-muted">Giá bq sau:</span><br>
                  <strong class="mono text-primary"><?= vnd((float)$h['avg_price_after']) ?></strong>
                </div>
                <div class="col-6 mt-1">
                  <span class="text-muted">Tồn sau:</span><br>
                  <strong><?= $h['stock_after'] ?> máy</strong>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <?php elseif ($history_product_id): ?>
  <div class="col-lg-5">
    <div class="card p-4 text-center text-muted">
      <i class="bi bi-inbox fs-2 mb-2"></i>
      <p class="mb-0">Chưa có lịch sử nhập hàng cho sản phẩm này.</p>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
function previewSelling(input) {
  const importPrice = parseFloat(input.dataset.import) || 0;
  const rate  = parseFloat(input.value) / 100 || 0;
  const price = Math.round(importPrice * (1 + rate));
  const row   = input.closest('tr');
  const cell  = row.querySelector('.selling-cell');
  if (cell) {
    cell.textContent = price > 0 ? price.toLocaleString('vi-VN') + ' ₫' : '—';
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

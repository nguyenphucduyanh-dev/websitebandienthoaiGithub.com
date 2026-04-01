<?php
// ============================================================
//  admin/purchase_orders/create.php
//  Lập phiếu nhập hàng  –  tạo mới hoặc sửa phiếu nháp
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo        = get_db();
$admin      = current_admin();
$po_id      = (int)($_GET['id'] ?? $_POST['po_id'] ?? 0);
$is_edit    = $po_id > 0;
$errors     = [];
$po         = null;
$po_details = [];

if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE id=?");
    $stmt->execute([$po_id]);
    $po = $stmt->fetch();
    if (!$po) { header('Location: /admin/purchase_orders/index.php'); exit; }

    $po_details = $pdo->prepare(
        "SELECT pod.*, p.name AS product_name
         FROM purchase_order_details pod
         JOIN products p ON p.id = pod.product_id
         WHERE pod.purchase_order_id = ?"
    )->execute([$po_id]) ? $pdo->prepare(
        "SELECT pod.*, p.name AS product_name
         FROM purchase_order_details pod
         JOIN products p ON p.id = pod.product_id
         WHERE pod.purchase_order_id = ?"
    )->execute([$po_id]) && false ? [] : (function() use ($pdo, $po_id) {
        $s = $pdo->prepare("SELECT pod.*, p.name AS product_name
         FROM purchase_order_details pod
         JOIN products p ON p.id = pod.product_id
         WHERE pod.purchase_order_id = ?");
        $s->execute([$po_id]);
        return $s->fetchAll();
    })() : [];

    // Đúng cách
    $s = $pdo->prepare(
        "SELECT pod.*, p.name AS product_name
         FROM purchase_order_details pod
         JOIN products p ON p.id = pod.product_id
         WHERE pod.purchase_order_id = ?"
    );
    $s->execute([$po_id]);
    $po_details = $s->fetchAll();
}

// ── Xử lý lưu phiếu ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_po'])) {
    $import_date = $_POST['import_date'] ?? date('Y-m-d');
    $supplier    = trim($_POST['supplier'] ?? '');
    $note        = trim($_POST['note'] ?? '');
    $product_ids = $_POST['product_id'] ?? [];
    $quantities  = $_POST['quantity']   ?? [];
    $prices      = $_POST['import_price'] ?? [];

    // Validate
    if (empty($product_ids)) $errors[] = 'Cần nhập ít nhất 1 sản phẩm.';
    foreach ($product_ids as $i => $pid) {
        if ((int)$pid <= 0)           $errors[] = "Dòng " . ($i+1) . ": Chưa chọn sản phẩm.";
        if ((int)($quantities[$i] ?? 0) <= 0) $errors[] = "Dòng " . ($i+1) . ": Số lượng phải > 0.";
        if ((float)($prices[$i] ?? 0) <= 0)   $errors[] = "Dòng " . ($i+1) . ": Giá nhập phải > 0.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $total_amount = 0;
            foreach ($product_ids as $i => $pid) {
                $total_amount += (int)$quantities[$i] * (float)$prices[$i];
            }

            if ($is_edit && $po && $po['status'] === 'draft') {
                // Cập nhật header
                $pdo->prepare(
                    "UPDATE purchase_orders
                     SET import_date=?, supplier=?, note=?, total_amount=?
                     WHERE id=?"
                )->execute([$import_date, $supplier, $note, $total_amount, $po_id]);

                // Xóa detail cũ và insert lại
                $pdo->prepare(
                    "DELETE FROM purchase_order_details WHERE purchase_order_id=?"
                )->execute([$po_id]);
            } else {
                // Tạo mới
                $code = generate_po_code($pdo);
                $pdo->prepare(
                    "INSERT INTO purchase_orders
                     (code, import_date, supplier, note, total_amount, status, created_by)
                     VALUES (?,?,?,?,?,'draft',?)"
                )->execute([$code, $import_date, $supplier, $note, $total_amount, $admin['id']]);
                $po_id = (int)$pdo->lastInsertId();
            }

            // Insert details
            $ins = $pdo->prepare(
                "INSERT INTO purchase_order_details
                 (purchase_order_id, product_id, quantity, import_price)
                 VALUES (?,?,?,?)"
            );
            foreach ($product_ids as $i => $pid) {
                $ins->execute([$po_id, (int)$pid, (int)$quantities[$i], (float)$prices[$i]]);
            }

            $pdo->commit();
            $_SESSION['flash_success'] = $is_edit ? 'Đã cập nhật phiếu nháp.' : 'Đã tạo phiếu nháp.';
            header("Location: /admin/purchase_orders/create.php?id={$po_id}"); exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Lỗi: ' . $e->getMessage();
        }
    }
}

$products   = $pdo->query(
    "SELECT id, name, import_price FROM products WHERE status=1 ORDER BY name"
)->fetchAll();
$products_json = json_encode($products);

$page_title  = $is_edit ? "Phiếu nhập: " . ($po['code'] ?? 'Nháp') : 'Lập phiếu nhập hàng';
$active_menu = 'purchase_orders';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
  <a href="/admin/purchase_orders/index.php" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h5 class="mb-0 fw-700"><?= e($page_title) ?></h5>
  <?php if ($po && $po['status'] !== 'draft'): ?>
    <span class="badge-status <?= $po['status']==='completed'?'badge-completed':'badge-cancelled' ?>">
      <?= $po['status']==='completed' ? 'Hoàn thành' : 'Đã hủy' ?>
    </span>
  <?php endif; ?>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $e): ?><div><?= e($e) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<?php $readonly = ($po && $po['status'] !== 'draft') ? 'readonly disabled' : ''; ?>

<form method="POST" id="poForm">
  <input type="hidden" name="po_id" value="<?= $po_id ?>">
  <input type="hidden" name="save_po" value="1">

  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <label class="form-label fw-500">Ngày nhập <span class="text-danger">*</span></label>
      <input type="date" name="import_date" class="form-control"
             value="<?= e($po['import_date'] ?? date('Y-m-d')) ?>" <?= $readonly ?> required>
    </div>
    <div class="col-md-5">
      <label class="form-label fw-500">Nhà cung cấp</label>
      <input type="text" name="supplier" class="form-control"
             placeholder="Tên nhà cung cấp (tùy chọn)"
             value="<?= e($po['supplier'] ?? '') ?>" <?= $readonly ?>>
    </div>
    <div class="col-md-4">
      <label class="form-label fw-500">Ghi chú</label>
      <input type="text" name="note" class="form-control"
             placeholder="Ghi chú..."
             value="<?= e($po['note'] ?? '') ?>" <?= $readonly ?>>
    </div>
  </div>

  <!-- ── Bảng chi tiết sản phẩm ── -->
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-list-ul me-2"></i>Chi tiết hàng nhập</span>
      <?php if (!$readonly): ?>
      <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">
        <i class="bi bi-plus-lg me-1"></i>Thêm dòng
      </button>
      <?php endif; ?>
    </div>
    <div class="table-responsive">
      <table class="table mb-0" id="poTable">
        <thead>
          <tr>
            <th width="40">#</th>
            <th>Sản phẩm</th>
            <th width="120">Số lượng</th>
            <th width="170">Giá nhập (₫)</th>
            <th width="160">Thành tiền</th>
            <?php if (!$readonly): ?><th width="50"></th><?php endif; ?>
          </tr>
        </thead>
        <tbody id="poBody">
        <?php if ($po_details): ?>
          <?php foreach ($po_details as $i => $d): ?>
          <tr>
            <td class="row-num text-muted"><?= $i+1 ?></td>
            <td>
              <select name="product_id[]" class="form-select form-select-sm product-select"
                      onchange="updateSubtotal(this)" <?= $readonly ?>>
                <option value="">— Chọn sản phẩm —</option>
                <?php foreach ($products as $p): ?>
                <option value="<?= $p['id'] ?>"
                        data-price="<?= $p['import_price'] ?>"
                        <?= $p['id'] == $d['product_id'] ? 'selected' : '' ?>>
                  <?= e($p['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </td>
            <td>
              <input type="number" name="quantity[]" class="form-control form-control-sm qty-input"
                     value="<?= (int)$d['quantity'] ?>" min="1" onchange="updateSubtotal(this)"
                     <?= $readonly ?>>
            </td>
            <td>
              <input type="number" name="import_price[]" class="form-control form-control-sm price-input"
                     value="<?= (float)$d['import_price'] ?>" min="0" step="1000"
                     onchange="updateSubtotal(this)" <?= $readonly ?>>
            </td>
            <td class="subtotal mono fw-600 text-primary"><?= vnd((float)$d['subtotal']) ?></td>
            <?php if (!$readonly): ?>
            <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="removeRow(this)">
              <i class="bi bi-trash"></i>
            </button></td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <!-- Dòng trống mặc định -->
        <?php endif; ?>
        </tbody>
        <tfoot>
          <tr class="table-light fw-700">
            <td colspan="<?= $readonly ? 4 : 4 ?>" class="text-end">Tổng cộng:</td>
            <td class="mono text-primary fs-6" id="grandTotal">
              <?= vnd((float)($po['total_amount'] ?? 0)) ?>
            </td>
            <?php if (!$readonly): ?><td></td><?php endif; ?>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <?php if (!$readonly): ?>
  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-floppy me-1"></i>Lưu nháp
    </button>
    <?php if ($is_edit && $po && $po['status'] === 'draft'): ?>
    <a href="/admin/purchase_orders/complete.php?id=<?= $po_id ?>"
       class="btn btn-success"
       onclick="return confirm('Hoàn thành phiếu này? Tồn kho và giá vốn sẽ được cập nhật ngay lập tức.')">
      <i class="bi bi-check-circle me-1"></i>Hoàn thành phiếu
    </a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</form>

<!-- Template dòng sản phẩm -->
<template id="rowTemplate">
  <tr>
    <td class="row-num text-muted"></td>
    <td>
      <select name="product_id[]" class="form-select form-select-sm product-select"
              onchange="updateSubtotal(this)">
        <option value="">— Chọn sản phẩm —</option>
        <?php foreach ($products as $p): ?>
        <option value="<?= $p['id'] ?>" data-price="<?= $p['import_price'] ?>">
          <?= e($p['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </td>
    <td><input type="number" name="quantity[]" class="form-control form-control-sm qty-input"
               value="1" min="1" onchange="updateSubtotal(this)"></td>
    <td><input type="number" name="import_price[]" class="form-control form-control-sm price-input"
               value="" min="0" step="1000" onchange="updateSubtotal(this)"
               placeholder="0"></td>
    <td class="subtotal mono fw-600 text-primary">0 ₫</td>
    <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="removeRow(this)">
      <i class="bi bi-trash"></i>
    </button></td>
  </tr>
</template>

<script>
const PRODUCTS = <?= $products_json ?>;

function addRow() {
  const tmpl = document.getElementById('rowTemplate').content.cloneNode(true);
  document.getElementById('poBody').appendChild(tmpl);
  renumber();
  updateGrandTotal();
}

function removeRow(btn) {
  btn.closest('tr').remove();
  renumber();
  updateGrandTotal();
}

function renumber() {
  document.querySelectorAll('#poBody .row-num').forEach((el, i) => {
    el.textContent = i + 1;
  });
}

function updateSubtotal(el) {
  const row  = el.closest('tr');
  const sel  = row.querySelector('.product-select');
  const qty  = parseInt(row.querySelector('.qty-input')?.value) || 0;
  const price = parseFloat(row.querySelector('.price-input')?.value) || 0;

  // Auto-fill giá nhập khi chọn sản phẩm
  if (el.classList.contains('product-select') && sel.value) {
    const opt   = sel.selectedOptions[0];
    const lastP = parseFloat(opt.dataset.price) || 0;
    if (lastP > 0) row.querySelector('.price-input').value = lastP;
  }

  const sub = qty * (parseFloat(row.querySelector('.price-input')?.value) || 0);
  row.querySelector('.subtotal').textContent =
    sub > 0 ? sub.toLocaleString('vi-VN') + ' ₫' : '0 ₫';
  updateGrandTotal();
}

function updateGrandTotal() {
  let total = 0;
  document.querySelectorAll('#poBody tr').forEach(row => {
    const qty   = parseInt(row.querySelector('.qty-input')?.value) || 0;
    const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
    total += qty * price;
  });
  document.getElementById('grandTotal').textContent =
    total.toLocaleString('vi-VN') + ' ₫';
}

// Thêm 1 dòng mặc định nếu tạo mới
<?php if (!$is_edit): ?>
addRow();
<?php endif; ?>

// Tính lại khi tải trang (sửa)
renumber();
updateGrandTotal();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

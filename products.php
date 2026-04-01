<?php
// products.php  —  Trang danh sách + tìm kiếm sản phẩm
require_once __DIR__ . '/config/db.php';

// Lấy danh sách danh mục cho dropdown
$catStmt = getDB()->query('SELECT id, name FROM categories ORDER BY name');
$categories = $catStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tìm kiếm sản phẩm — Phone Shop</title>
<style>
  /* ── Reset & Base ─────────────────────────────────── */
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', sans-serif; background: #f5f6fa; color: #333; }
  a    { text-decoration: none; color: inherit; }

  /* ── Layout ───────────────────────────────────────── */
  .container   { max-width: 1200px; margin: 0 auto; padding: 0 16px; }
  header       { background: #1a1a2e; color: #fff; padding: 16px 0; }
  header h1    { font-size: 1.4rem; letter-spacing: 1px; }

  /* ── Search form ──────────────────────────────────── */
  .search-bar  { background: #fff; border-radius: 10px; padding: 20px 24px;
                 margin: 24px 0 20px; box-shadow: 0 2px 8px rgba(0,0,0,.08);
                 display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
  .search-bar label { display: block; font-size: .78rem; color: #666;
                      margin-bottom: 4px; font-weight: 600; }
  .search-bar input,
  .search-bar select { width: 100%; padding: 9px 12px; border: 1px solid #ddd;
                       border-radius: 6px; font-size: .9rem; outline: none; }
  .search-bar input:focus,
  .search-bar select:focus { border-color: #4361ee; box-shadow: 0 0 0 3px rgba(67,97,238,.15); }
  .field       { flex: 1 1 180px; min-width: 160px; }
  .field-price { flex: 1 1 140px; min-width: 120px; }
  .btn-search  { padding: 10px 28px; background: #4361ee; color: #fff;
                 border: none; border-radius: 6px; font-size: .9rem;
                 cursor: pointer; transition: background .2s; white-space: nowrap; }
  .btn-search:hover { background: #3451d1; }

  /* ── Grid sản phẩm ────────────────────────────────── */
  .products-grid { display: grid;
                   grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                   gap: 20px; }
  .product-card  { background: #fff; border-radius: 10px; overflow: hidden;
                   box-shadow: 0 2px 8px rgba(0,0,0,.07);
                   transition: transform .2s, box-shadow .2s; }
  .product-card:hover { transform: translateY(-4px);
                        box-shadow: 0 8px 20px rgba(0,0,0,.12); }
  .product-card img    { width: 100%; height: 180px; object-fit: cover;
                         background: #eee; display: block; }
  .product-card .info  { padding: 12px 14px; }
  .product-card .cat   { font-size: .72rem; color: #888; text-transform: uppercase;
                         letter-spacing: .5px; }
  .product-card .name  { font-size: .95rem; font-weight: 600; margin: 4px 0 8px;
                         line-height: 1.3; }
  .product-card .price { font-size: 1.05rem; color: #e63946; font-weight: 700; }
  .product-card .stock { font-size: .78rem; color: #999; margin-top: 2px; }
  .btn-buy     { display: block; margin: 10px 14px 14px; padding: 8px 0;
                 background: #4361ee; color: #fff; border-radius: 6px;
                 text-align: center; font-size: .88rem; font-weight: 600;
                 transition: background .2s; }
  .btn-buy:hover { background: #3451d1; }

  /* ── Phân trang ──────────────────────────────────── */
  .pagination  { display: flex; justify-content: center; gap: 6px;
                 margin: 32px 0 40px; flex-wrap: wrap; }
  .page-btn    { min-width: 38px; height: 38px; padding: 0 10px;
                 border: 1px solid #ddd; border-radius: 6px; background: #fff;
                 font-size: .88rem; cursor: pointer; transition: all .2s; }
  .page-btn:hover       { border-color: #4361ee; color: #4361ee; }
  .page-btn.active      { background: #4361ee; color: #fff; border-color: #4361ee; }
  .page-btn:disabled    { opacity: .4; cursor: default; }

  /* ── Meta / loading ──────────────────────────────── */
  .result-meta  { color: #666; font-size: .85rem; margin-bottom: 16px; }
  #loading      { text-align: center; padding: 60px 0; color: #888;
                  font-size: 1.1rem; display: none; }
  #no-result    { text-align: center; padding: 60px 0; color: #aaa;
                  font-size: 1rem; display: none; }
</style>
</head>
<body>

<header>
  <div class="container">
    <h1>📱 Phone Shop — Tìm kiếm sản phẩm</h1>
  </div>
</header>

<div class="container">

  <!-- ── Thanh tìm kiếm ── -->
  <div class="search-bar">
    <div class="field" style="flex:2 1 240px">
      <label for="keyword">Tên sản phẩm</label>
      <input id="keyword" type="text" placeholder="iPhone 15, Galaxy S24...">
    </div>
    <div class="field">
      <label for="category">Danh mục</label>
      <select id="category">
        <option value="">Tất cả danh mục</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field-price">
      <label for="min-price">Giá bán từ (₫)</label>
      <input id="min-price" type="number" placeholder="0" min="0" step="100000">
    </div>
    <div class="field-price">
      <label for="max-price">đến (₫)</label>
      <input id="max-price" type="number" placeholder="100.000.000" min="0" step="100000">
    </div>
    <button class="btn-search" onclick="doSearch(1)">🔍 Tìm kiếm</button>
  </div>

  <!-- ── Kết quả ── -->
  <p class="result-meta" id="result-meta"></p>
  <div id="loading">⏳ Đang tải...</div>
  <div id="no-result">Không tìm thấy sản phẩm phù hợp.</div>
  <div class="products-grid" id="products-grid"></div>

  <!-- ── Phân trang ── -->
  <div class="pagination" id="pagination"></div>

</div><!-- /container -->

<script>
// ── Trạng thái phân trang hiện tại ────────────────────────────────────────
let currentPage = 1;

// ── Gọi search.php qua AJAX ───────────────────────────────────────────────
function doSearch(page = 1) {
    currentPage = page;

    const keyword    = document.getElementById('keyword').value.trim();
    const categoryId = document.getElementById('category').value;
    const minPrice   = document.getElementById('min-price').value;
    const maxPrice   = document.getElementById('max-price').value;

    const params = new URLSearchParams({ page });
    if (keyword)    params.append('keyword',     keyword);
    if (categoryId) params.append('category_id', categoryId);
    if (minPrice)   params.append('min_price',   minPrice);
    if (maxPrice)   params.append('max_price',   maxPrice);

    // Hiện loading
    document.getElementById('loading').style.display      = 'block';
    document.getElementById('no-result').style.display    = 'none';
    document.getElementById('products-grid').innerHTML    = '';
    document.getElementById('pagination').innerHTML       = '';
    document.getElementById('result-meta').textContent    = '';

    fetch('search.php?' + params.toString())
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(json => {
            document.getElementById('loading').style.display = 'none';

            if (!json.success) {
                alert('Lỗi: ' + json.message);
                return;
            }

            const { data, pagination } = json;
            const { current_page, total_pages, total_items, per_page } = pagination;

            // Hiển thị meta
            const from = (current_page - 1) * per_page + 1;
            const to   = Math.min(current_page * per_page, total_items);
            document.getElementById('result-meta').textContent =
                total_items > 0
                    ? `Hiển thị ${from}–${to} / ${total_items} sản phẩm`
                    : '';

            if (data.length === 0) {
                document.getElementById('no-result').style.display = 'block';
                return;
            }

            // Render sản phẩm
            renderProducts(data);

            // Render phân trang
            renderPagination(current_page, total_pages);
        })
        .catch(err => {
            document.getElementById('loading').style.display = 'none';
            console.error(err);
            alert('Không thể kết nối máy chủ. Vui lòng thử lại.');
        });
}

// ── Render danh sách sản phẩm ─────────────────────────────────────────────
function renderProducts(products) {
    const grid = document.getElementById('products-grid');
    grid.innerHTML = products.map(p => {
        const img   = p.image
            ? `<img src="${escHtml(p.image)}" alt="${escHtml(p.name)}" loading="lazy">`
            : `<div style="height:180px;background:#e9ecef;display:flex;
                align-items:center;justify-content:center;color:#aaa">📱</div>`;

        const price = Number(p.selling_price).toLocaleString('vi-VN') + '₫';
        const stock = p.stock_quantity > 0
            ? `Còn ${p.stock_quantity} máy`
            : '<span style="color:#e63946">Hết hàng</span>';

        return `
        <div class="product-card">
            <a href="product_detail.php?slug=${escHtml(p.slug)}">${img}</a>
            <div class="info">
                <span class="cat">${escHtml(p.category_name)}</span>
                <p class="name">
                    <a href="product_detail.php?slug=${escHtml(p.slug)}">${escHtml(p.name)}</a>
                </p>
                <p class="price">${price}</p>
                <p class="stock">${stock}</p>
            </div>
            <a class="btn-buy" href="cart.php?action=add&product_id=${p.id}"
               onclick="return addToCart(event, ${p.id})">🛒 Thêm vào giỏ</a>
        </div>`;
    }).join('');
}

// ── Render nút phân trang ─────────────────────────────────────────────────
function renderPagination(current, total) {
    if (total <= 1) return;

    const wrap = document.getElementById('pagination');
    let html    = '';

    // Nút « Trước
    html += `<button class="page-btn" onclick="doSearch(${current - 1})"
             ${current === 1 ? 'disabled' : ''}>&#8249; Trước</button>`;

    // Số trang (hiện tối đa 7 nút, dùng dấu "…" khi cần)
    const range = pageRange(current, total);
    range.forEach(p => {
        if (p === '...') {
            html += `<button class="page-btn" disabled>…</button>`;
        } else {
            html += `<button class="page-btn ${p === current ? 'active' : ''}"
                     onclick="doSearch(${p})">${p}</button>`;
        }
    });

    // Nút Sau »
    html += `<button class="page-btn" onclick="doSearch(${current + 1})"
             ${current === total ? 'disabled' : ''}>Sau &#8250;</button>`;

    wrap.innerHTML = html;
}

// Tạo mảng số trang có dấu "..." khi tổng trang > 7
function pageRange(current, total) {
    if (total <= 7) return Array.from({length: total}, (_, i) => i + 1);
    const pages = [1];
    if (current > 3)        pages.push('...');
    for (let p = Math.max(2, current - 1); p <= Math.min(total - 1, current + 1); p++)
        pages.push(p);
    if (current < total - 2) pages.push('...');
    pages.push(total);
    return pages;
}

// ── Thêm vào giỏ hàng (AJAX nhẹ) ─────────────────────────────────────────
function addToCart(e, productId) {
    e.preventDefault();
    fetch(`cart.php?action=add&product_id=${productId}`, { method: 'POST' })
        .then(r => r.json())
        .then(j => {
            if (j.success) {
                alert('✅ Đã thêm vào giỏ hàng!');
            } else if (j.redirect) {
                window.location.href = j.redirect; // chưa đăng nhập → login
            } else {
                alert(j.message || 'Có lỗi xảy ra.');
            }
        })
        .catch(() => alert('Không thể kết nối.'));
    return false;
}

// Escape HTML đơn giản
function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Tìm kiếm khi nhấn Enter ──────────────────────────────────────────────
document.getElementById('keyword').addEventListener('keydown', e => {
    if (e.key === 'Enter') doSearch(1);
});

// Tải kết quả mặc định khi vào trang
doSearch(1);
</script>
</body>
</html>

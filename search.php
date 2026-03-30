<?php
/**
 * search.php - Tìm kiếm sản phẩm (AJAX + hiển thị)
 * Tác giả: nguyenphucduyanh-dev
 */

session_start();
require_once __DIR__ . '/includes/functions.php';

// -----------------------------------------------
// Xử lý AJAX request
// -----------------------------------------------
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');

    $keyword    = trim($_GET['keyword'] ?? '');
    $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
    $priceMin   = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? (float)$_GET['price_min'] : null;
    $priceMax   = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? (float)$_GET['price_max'] : null;
    $page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

    $result = searchProducts($keyword, $categoryId, $priceMin, $priceMax, $page, 12);

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// -----------------------------------------------
// Lấy danh sách categories cho bộ lọc
// -----------------------------------------------
$pdo = getDBConnection();
$categories = $pdo->query("SELECT category_id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm kiếm sản phẩm - Website Bán Điện Thoại</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; background: #f5f5f5; color: #333; }

        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }

        /* Header */
        .page-header { background: #1a73e8; color: #fff; padding: 20px; text-align: center; margin-bottom: 20px; border-radius: 8px; }
        .page-header h1 { font-size: 24px; }

        /* Search Form */
        .search-form { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .search-form .form-row { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
        .search-form .form-group { flex: 1; min-width: 180px; }
        .search-form label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 14px; }
        .search-form input, .search-form select {
            width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px;
            font-size: 14px; transition: border-color 0.2s;
        }
        .search-form input:focus, .search-form select:focus { border-color: #1a73e8; outline: none; }
        .btn-search {
            background: #1a73e8; color: #fff; border: none; padding: 10px 24px;
            border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600;
            transition: background 0.2s;
        }
        .btn-search:hover { background: #1557b0; }

        /* Product Grid */
        .results-info { padding: 10px 0; font-size: 14px; color: #666; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .product-card {
            background: #fff; border-radius: 8px; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: transform 0.2s, box-shadow 0.2s;
        }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 4px 16px rgba(0,0,0,0.15); }
        .product-card .card-img {
            width: 100%; height: 200px; background: #e8e8e8;
            display: flex; align-items: center; justify-content: center;
            font-size: 48px; color: #bbb;
        }
        .product-card .card-body { padding: 16px; }
        .product-card .card-title { font-size: 16px; font-weight: 600; margin-bottom: 6px; line-height: 1.3; }
        .product-card .card-category { font-size: 12px; color: #888; margin-bottom: 8px; }
        .product-card .card-price { font-size: 18px; font-weight: 700; color: #e53935; }
        .product-card .card-stock { font-size: 12px; color: #999; margin-top: 4px; }
        .product-card .btn-add-cart {
            display: block; width: 100%; margin-top: 12px; padding: 10px;
            background: #ff6f00; color: #fff; border: none; border-radius: 6px;
            cursor: pointer; font-weight: 600; font-size: 14px;
            transition: background 0.2s;
        }
        .product-card .btn-add-cart:hover { background: #e65100; }

        /* Pagination */
        .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 30px; flex-wrap: wrap; }
        .pagination button {
            padding: 8px 14px; border: 1px solid #ddd; background: #fff; border-radius: 6px;
            cursor: pointer; font-size: 14px; transition: all 0.2s;
        }
        .pagination button:hover { border-color: #1a73e8; color: #1a73e8; }
        .pagination button.active { background: #1a73e8; color: #fff; border-color: #1a73e8; }
        .pagination button:disabled { opacity: 0.4; cursor: not-allowed; }

        /* Loading & Empty */
        .loading { text-align: center; padding: 40px; font-size: 16px; color: #999; }
        .no-results { text-align: center; padding: 60px 20px; color: #999; }
        .no-results p { font-size: 18px; }

        /* Toast notification */
        .toast {
            position: fixed; bottom: 20px; right: 20px; background: #333; color: #fff;
            padding: 14px 24px; border-radius: 8px; font-size: 14px; z-index: 1000;
            opacity: 0; transition: opacity 0.3s; pointer-events: none;
        }
        .toast.show { opacity: 1; }

        @media (max-width: 600px) {
            .search-form .form-row { flex-direction: column; }
            .product-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="page-header">
        <h1>🔍 Tìm kiếm điện thoại</h1>
    </div>

    <!-- Form tìm kiếm -->
    <div class="search-form">
        <div class="form-row">
            <div class="form-group">
                <label for="keyword">Tên sản phẩm</label>
                <input type="text" id="keyword" placeholder="VD: iPhone, Samsung...">
            </div>
            <div class="form-group">
                <label for="category_id">Danh mục</label>
                <select id="category_id">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>">
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="price_min">Giá từ (₫)</label>
                <input type="number" id="price_min" placeholder="0" min="0">
            </div>
            <div class="form-group">
                <label for="price_max">Giá đến (₫)</label>
                <input type="number" id="price_max" placeholder="50000000" min="0">
            </div>
            <div class="form-group" style="flex: 0 0 auto;">
                <button class="btn-search" onclick="doSearch(1)">Tìm kiếm</button>
            </div>
        </div>
    </div>

    <!-- Kết quả -->
    <div class="results-info" id="resultsInfo"></div>
    <div class="product-grid" id="productGrid"></div>
    <div class="pagination" id="pagination"></div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
/**
 * JavaScript AJAX Search - Website Bán Điện Thoại
 * Tác giả: nguyenphucduyanh-dev
 */

let currentPage = 1;

// Tự động tìm kiếm khi tải trang
document.addEventListener('DOMContentLoaded', function() {
    doSearch(1);

    // Enter để tìm kiếm
    document.getElementById('keyword').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') doSearch(1);
    });
});

/**
 * Hàm tìm kiếm sản phẩm bằng AJAX
 */
function doSearch(page) {
    currentPage = page;

    const keyword    = document.getElementById('keyword').value.trim();
    const categoryId = document.getElementById('category_id').value;
    const priceMin   = document.getElementById('price_min').value;
    const priceMax   = document.getElementById('price_max').value;

    // Xây dựng URL query
    const params = new URLSearchParams({
        ajax: '1',
        keyword: keyword,
        category_id: categoryId,
        price_min: priceMin,
        price_max: priceMax,
        page: page
    });

    // Hiển thị loading
    document.getElementById('productGrid').innerHTML = '<div class="loading">⏳ Đang tìm kiếm...</div>';
    document.getElementById('pagination').innerHTML = '';
    document.getElementById('resultsInfo').innerHTML = '';

    // Gọi AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'search.php?' + params.toString(), true);
    xhr.setRequestHeader('Accept', 'application/json');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    renderProducts(data);
                    renderPagination(data);
                    renderResultsInfo(data);
                } catch (e) {
                    document.getElementById('productGrid').innerHTML =
                        '<div class="no-results"><p>❌ Lỗi xử lý dữ liệu.</p></div>';
                }
            } else {
                document.getElementById('productGrid').innerHTML =
                    '<div class="no-results"><p>❌ Lỗi kết nối server.</p></div>';
            }
        }
    };

    xhr.send();
}

/**
 * Render danh sách sản phẩm
 */
function renderProducts(data) {
    const grid = document.getElementById('productGrid');
    const products = data.products;

    if (!products || products.length === 0) {
        grid.innerHTML = '<div class="no-results"><p>📱 Không tìm thấy sản phẩm nào.</p></div>';
        return;
    }

    let html = '';
    products.forEach(function(p) {
        const price     = formatCurrency(p.selling_price);
        const stockText = p.stock_quantity > 0 ? ('Còn ' + p.stock_quantity + ' sản phẩm') : 'Hết hàng';
        const stockClass = p.stock_quantity > 0 ? '' : 'color: #e53935;';
        const btnDisabled = p.stock_quantity <= 0 ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '';

        html += `
            <div class="product-card">
                <div class="card-img">📱</div>
                <div class="card-body">
                    <div class="card-title">${escapeHtml(p.product_name)}</div>
                    <div class="card-category">${escapeHtml(p.category_name)}</div>
                    <div class="card-price">${price}</div>
                    <div class="card-stock" style="${stockClass}">${stockText}</div>
                    <button class="btn-add-cart" onclick="addToCart(${p.product_id})" ${btnDisabled}>
                        🛒 Thêm vào giỏ
                    </button>
                </div>
            </div>
        `;
    });

    grid.innerHTML = html;
}

/**
 * Render phân trang
 */
function renderPagination(data) {
    const container  = document.getElementById('pagination');
    const totalPages = data.total_pages;
    const current    = data.current_page;

    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '';

    // Nút Previous
    html += `<button onclick="doSearch(${current - 1})" ${current <= 1 ? 'disabled' : ''}>« Trước</button>`;

    // Hiển thị tối đa 7 trang xung quanh trang hiện tại
    let startPage = Math.max(1, current - 3);
    let endPage   = Math.min(totalPages, current + 3);

    if (startPage > 1) {
        html += `<button onclick="doSearch(1)">1</button>`;
        if (startPage > 2) {
            html += `<button disabled>...</button>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        const activeClass = (i === current) ? 'active' : '';
        html += `<button class="${activeClass}" onclick="doSearch(${i})">${i}</button>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<button disabled>...</button>`;
        }
        html += `<button onclick="doSearch(${totalPages})">${totalPages}</button>`;
    }

    // Nút Next
    html += `<button onclick="doSearch(${current + 1})" ${current >= totalPages ? 'disabled' : ''}>Sau »</button>`;

    container.innerHTML = html;
}

/**
 * Render thông tin kết quả
 */
function renderResultsInfo(data) {
    const info = document.getElementById('resultsInfo');
    if (data.total > 0) {
        const from = (data.current_page - 1) * data.per_page + 1;
        const to   = Math.min(data.current_page * data.per_page, data.total);
        info.innerHTML = `Hiển thị ${from} - ${to} trong tổng số <strong>${data.total}</strong> sản phẩm (Trang ${data.current_page}/${data.total_pages})`;
    } else {
        info.innerHTML = '';
    }
}

/**
 * Thêm vào giỏ hàng (AJAX)
 */
function addToCart(productId) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'cart.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                const resp = JSON.parse(xhr.responseText);
                showToast(resp.message, resp.success);
            } catch (e) {
                showToast('Lỗi xử lý.', false);
            }
        }
    };

    xhr.send('action=add&product_id=' + productId + '&quantity=1');
}

/**
 * Hiển thị thông báo toast
 */
function showToast(message, success) {
    const toast = document.getElementById('toast');
    toast.textContent = (success ? '✅ ' : '⚠️ ') + message;
    toast.style.background = success ? '#2e7d32' : '#c62828';
    toast.classList.add('show');
    setTimeout(function() {
        toast.classList.remove('show');
    }, 3000);
}

/**
 * Format tiền VND
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + ' ₫';
}

/**
 * Escape HTML để tránh XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
</script>

</body>
</html>

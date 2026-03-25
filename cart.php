function addToCart(productId) {
    fetch('add_to_cart.php', {
        method: 'POST',
        body: JSON.stringify({ id: productId, quantity: 1 }),
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        alert("Đã thêm vào giỏ hàng!");
        updateCartUI();
    });
}

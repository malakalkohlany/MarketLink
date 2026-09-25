function updateQuantity(productId, action) {

    window.location.href =
        'update_cart.php?product_id='
        + encodeURIComponent(productId)
        + '&action='
        + encodeURIComponent(action);
}


function removeItem(productId) {

    const confirmed =
        confirm('Remove this item from your cart?');

    if (!confirmed) {
        return;
    }

    window.location.href =
        'remove_from_cart.php?product_id='
        + encodeURIComponent(productId);
}
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

const cartFloatingActions = document.getElementById('cartFloatingActions');
const cartSummarySection = document.getElementById('cartSummarySection');

// if (cartFloatingActions && cartSummarySection) {
//     const summaryObserver = new IntersectionObserver(
//         (entries) => {
//             entries.forEach((entry) => {
//                 cartFloatingActions.classList.toggle(
//                     'is-hidden',
//                     entry.isIntersecting
//                 );
//             });
//         },
//         {
//             threshold: 0.1
//         }
//     );

//     summaryObserver.observe(cartSummarySection);
// }
const productSelect =
    document.getElementById('productSelect');

const productOrderSelect =
    document.getElementById('productOrderSelect');


if (productSelect && productOrderSelect) {

    productSelect.addEventListener(
        'change',
        function () {

            const selectedOption =
                productSelect.options[
                    productSelect.selectedIndex
                ];

            const orderId =
                selectedOption.dataset.order || '';

            productOrderSelect.value = '';

            Array.from(
                productOrderSelect.options
            ).forEach(function (option) {

                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                const matches =
                    option.dataset.product ===
                    productSelect.value;

                option.hidden = !matches;

            });

            // Automatically select the matching order
            if (orderId) {
                productOrderSelect.value = orderId;
            }

        }
    );

}


// ==================================================
// KEEP FARMER + ORDER SELECTED TOGETHER
// ==================================================

const farmerSelect =
    document.getElementById('farmerSelect');

const farmerOrderSelect =
    document.getElementById('farmerOrderSelect');


if (farmerSelect && farmerOrderSelect) {

    farmerSelect.addEventListener(
        'change',
        function () {

            const selectedOption =
                farmerSelect.options[
                    farmerSelect.selectedIndex
                ];

            const orderId =
                selectedOption.dataset.order || '';

            farmerOrderSelect.value = '';

            Array.from(
                farmerOrderSelect.options
            ).forEach(function (option) {

                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                const matches =
                    option.dataset.farmer ===
                    farmerSelect.value;

                option.hidden = !matches;

            });

            // Automatically select the matching order
            if (orderId) {
                farmerOrderSelect.value = orderId;
            }

        }
    );

}
function applyProductFilters() {

    const productSearch =
        document.getElementById(
            'productSearch'
        );

    const productCategory =
        document.getElementById(
            'productCategory'
        );

    const productMarket =
        document.getElementById(
            'productMarket'
        );

    const productDay =
        document.getElementById(
            'productDay'
        );

    const minPrice =
        document.getElementById(
            'minPrice'
        );

    const maxPrice =
        document.getElementById(
            'maxPrice'
        );

    const productCards =
        document.querySelectorAll(
            '.product-card'
        );

    const noMatchingProducts =
        document.getElementById(
            'noMatchingProducts'
        );


    const searchTerm =
        productSearch.value
            .trim()
            .toLowerCase();

    const selectedCategory =
        productCategory.value;

    const selectedMarket =
        productMarket.value;

    const selectedDay =
        productDay.value;


    const min =
        minPrice.value === ''
            ? null
            : parseFloat(
                minPrice.value
            );

    const max =
        maxPrice.value === ''
            ? null
            : parseFloat(
                maxPrice.value
            );


    let visibleProducts = 0;


    productCards.forEach(
        function(card) {

            const productName =
                card.querySelector(
                    '.product-name'
                )?.textContent
                    .trim()
                    .toLowerCase()
                || '';


            const farmerName =
                card.querySelector(
                    '.product-farmer'
                )?.textContent
                    .trim()
                    .toLowerCase()
                || '';


            const categoryId =
                card.dataset.categoryId
                || '';


            const marketIds =
                (
                    card.dataset.marketIds
                    || ''
                )
                    .split(',')
                    .map(
                        id => id.trim()
                    )
                    .filter(
                        id => id !== ''
                    );


            const marketDays =
                (
                    card.dataset.marketDays
                    || ''
                )
                    .split(',')
                    .map(
                        day => day.trim()
                    )
                    .filter(
                        day => day !== ''
                    );


            const price =
                parseFloat(
                    card.dataset.price
                    || '0'
                );


            // --------------------------------------------------
            // Search
            // --------------------------------------------------

            const matchesSearch =
                searchTerm === ''
                ||
                productName.includes(
                    searchTerm
                )
                ||
                farmerName.includes(
                    searchTerm
                );


            // --------------------------------------------------
            // Category
            // --------------------------------------------------

            const matchesCategory =
                selectedCategory === ''
                ||
                categoryId ===
                    selectedCategory;


            // --------------------------------------------------
            // Market
            // --------------------------------------------------

            const matchesMarket =
                selectedMarket === ''
                ||
                marketIds.includes(
                    selectedMarket
                );


            // --------------------------------------------------
            // Market Day
            // --------------------------------------------------

            const matchesDay =
                selectedDay === ''
                ||
                marketDays.includes(
                    selectedDay
                );


            // --------------------------------------------------
            // Price
            // --------------------------------------------------

            const matchesMinPrice =
                min === null
                ||
                price >= min;

            const matchesMaxPrice =
                max === null
                ||
                price <= max;


            // --------------------------------------------------
            // Final Match
            // --------------------------------------------------

            const matches =
                matchesSearch
                &&
                matchesCategory
                &&
                matchesMarket
                &&
                matchesDay
                &&
                matchesMinPrice
                &&
                matchesMaxPrice;


            card.style.display =
                matches
                    ? ''
                    : 'none';


            if (matches) {
                visibleProducts++;
            }

        }
    );


    noMatchingProducts.style.display =
        visibleProducts === 0
            ? 'block'
            : 'none';
}


// ==========================================================
// Filter Events
// ==========================================================

document
    .getElementById('productSearch')
    .addEventListener(
        'input',
        applyProductFilters
    );

document
    .getElementById('productCategory')
    .addEventListener(
        'change',
        applyProductFilters
    );

document
    .getElementById('productMarket')
    .addEventListener(
        'change',
        applyProductFilters
    );

document
    .getElementById('productDay')
    .addEventListener(
        'change',
        applyProductFilters
    );

document
    .getElementById('minPrice')
    .addEventListener(
        'input',
        applyProductFilters
    );

document
    .getElementById('maxPrice')
    .addEventListener(
        'input',
        applyProductFilters
    );
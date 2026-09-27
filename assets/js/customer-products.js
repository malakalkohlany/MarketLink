const PRODUCTS_PER_PAGE = 8;

let currentProductPage = 1;
let filteredProductCards = [];

function applyProductFilters(resetPage = true) {
    const productSearch = document.getElementById('productSearch');
    const productCategory = document.getElementById('productCategory');
    const productMarket = document.getElementById('productMarket');
    const productDay = document.getElementById('productDay');
    const minPrice = document.getElementById('minPrice');
    const maxPrice = document.getElementById('maxPrice');
    const productCards = document.querySelectorAll('.customer-product-card');
    const noMatchingProducts = document.getElementById('noMatchingProducts');
    const pagination = document.getElementById('productPagination');

    const searchTerm = productSearch.value
        .trim()
        .toLowerCase();

    const selectedCategory = productCategory.value;
    const selectedMarket = productMarket.value;
    const selectedDay = productDay.value;

    const min = minPrice.value === ''
        ? null
        : parseFloat(minPrice.value);

    const max = maxPrice.value === ''
        ? null
        : parseFloat(maxPrice.value);

    if (resetPage) {
        currentProductPage = 1;
    }

    filteredProductCards = [];

    productCards.forEach(function(card) {
        const productName = (
            card.dataset.productName
            || ''
        ).trim().toLowerCase();

        const farmerName = (
            card.dataset.farmerName
            || ''
        ).trim().toLowerCase();

        const categoryId =
            card.dataset.categoryId || '';

        const marketIds = (
            card.dataset.marketIds || ''
        )
            .split(',')
            .map(id => id.trim())
            .filter(id => id !== '');

        const marketDays = (
            card.dataset.marketDays || ''
        )
            .split(',')
            .map(day => day.trim())
            .filter(day => day !== '');

        const price = parseFloat(
            card.dataset.price || '0'
        );

        const matchesSearch =
            searchTerm === ''
            || productName.includes(searchTerm)
            || farmerName.includes(searchTerm);

        const matchesCategory =
            selectedCategory === ''
            || categoryId === selectedCategory;

        const matchesMarket =
            selectedMarket === ''
            || marketIds.includes(selectedMarket);

        const matchesDay =
            selectedDay === ''
            || marketDays.includes(selectedDay);

        const matchesMinPrice =
            min === null
            || price >= min;

        const matchesMaxPrice =
            max === null
            || price <= max;

        const matches =
            matchesSearch
            && matchesCategory
            && matchesMarket
            && matchesDay
            && matchesMinPrice
            && matchesMaxPrice;

        if (matches) {
            filteredProductCards.push(card);
        }
    });

    const totalPages = Math.ceil(
        filteredProductCards.length /
        PRODUCTS_PER_PAGE
    );

    if (
        totalPages > 0
        && currentProductPage > totalPages
    ) {
        currentProductPage = totalPages;
    }

    productCards.forEach(function(card) {
        card.style.display = 'none';
    });

    if (filteredProductCards.length === 0) {
        noMatchingProducts.style.display = 'block';

        if (pagination) {
            pagination.style.display = 'none';
        }

        return;
    }

    noMatchingProducts.style.display = 'none';

    displayCurrentProductPage();
    renderProductPagination(totalPages);
}

function displayCurrentProductPage() {
    const startIndex =
        (currentProductPage - 1) *
        PRODUCTS_PER_PAGE;

    const endIndex =
        startIndex +
        PRODUCTS_PER_PAGE;

    filteredProductCards.forEach(function(card) {
        card.style.display = 'none';
    });

    filteredProductCards
        .slice(startIndex, endIndex)
        .forEach(function(card) {
            card.style.display = '';
        });
}

function renderProductPagination(totalPages) {
    const pagination =
        document.getElementById('productPagination');

    if (!pagination) {
        return;
    }

    pagination.innerHTML = '';

    if (totalPages <= 1) {
        pagination.style.display = 'none';
        return;
    }

    pagination.style.display = 'flex';

    const previousButton =
        document.createElement('button');

    previousButton.type = 'button';
    previousButton.className =
        'product-pagination-button product-pagination-arrow';
    previousButton.textContent = 'Previous';
    previousButton.disabled =
        currentProductPage === 1;

    previousButton.addEventListener(
        'click',
        function() {
            if (currentProductPage > 1) {
                currentProductPage--;

                displayCurrentProductPage();
                renderProductPagination(totalPages);
                scrollToProducts();
            }
        }
    );

    pagination.appendChild(previousButton);

    for (
        let page = 1;
        page <= totalPages;
        page++
    ) {
        const pageButton =
            document.createElement('button');

        pageButton.type = 'button';
        pageButton.className =
            'product-pagination-button product-pagination-number';

        pageButton.textContent = page;

        if (page === currentProductPage) {
            pageButton.classList.add('active');
        }

        pageButton.addEventListener(
            'click',
            function() {
                currentProductPage = page;

                displayCurrentProductPage();
                renderProductPagination(totalPages);
                scrollToProducts();
            }
        );

        pagination.appendChild(pageButton);
    }

    const nextButton =
        document.createElement('button');

    nextButton.type = 'button';
    nextButton.className =
        'product-pagination-button product-pagination-arrow';
    nextButton.textContent = 'Next';
    nextButton.disabled =
        currentProductPage === totalPages;

    nextButton.addEventListener(
        'click',
        function() {
            if (currentProductPage < totalPages) {
                currentProductPage++;

                displayCurrentProductPage();
                renderProductPagination(totalPages);
                scrollToProducts();
            }
        }
    );

    pagination.appendChild(nextButton);
}

function scrollToProducts() {
    const productGrid =
        document.getElementById('productGrid');

    if (!productGrid) {
        return;
    }

    const navbarHeight = 90;

    const position =
        productGrid.getBoundingClientRect().top
        + window.scrollY
        - navbarHeight;

    window.scrollTo({
        top: position,
        behavior: 'smooth'
    });
}

document
    .getElementById('productSearch')
    .addEventListener(
        'input',
        function() {
            applyProductFilters(true);
        }
    );

document
    .getElementById('productCategory')
    .addEventListener(
        'change',
        function() {
            applyProductFilters(true);
        }
    );

document
    .getElementById('productMarket')
    .addEventListener(
        'change',
        function() {
            applyProductFilters(true);
        }
    );

document
    .getElementById('productDay')
    .addEventListener(
        'change',
        function() {
            applyProductFilters(true);
        }
    );

document
    .getElementById('minPrice')
    .addEventListener(
        'input',
        function() {
            applyProductFilters(true);
        }
    );

document
    .getElementById('maxPrice')
    .addEventListener(
        'input',
        function() {
            applyProductFilters(true);
        }
    );

document.addEventListener(
    'DOMContentLoaded',
    function() {
        applyProductFilters(false);
    }
);
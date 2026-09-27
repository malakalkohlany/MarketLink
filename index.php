<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/constants.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MarketLink — Your Local Market, In One Place</title>

    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/homepage.css">
</head>

<body>

<header class="home-navbar">
    <div class="home-nav-inner">

        <a href="index.php" class="home-brand">
            <span class="brand-mark">M</span>
            <span class="brand-name">MarketLink</span>
        </a>

        <nav class="home-nav-links">
            <a href="#">Home</a>
            <a href="public/about.php">About</a>
            <a href="public/farmers.php">Farmers</a>
            <a href="public/markets.php">Markets</a>
            <a href="public/contact.php">Contact</a>
        </nav>

        <div class="home-nav-actions">
            <a href="auth/login.php" class="home-login">
                Log in
            </a>

            <a href="auth/register.php" class="home-join">
                Join MarketLink
                <span>↗</span>
            </a>
        </div>

    </div>
</header>


<main>

<section class="hero">

    <div class="hero-inner">

        <div class="hero-copy">

            <div class="hero-eyebrow">
                <span></span>
                LOCAL • FRESH • CONNECTED
            </div>

            <h1>
                Your local market,
                <em>in one place.</em>
            </h1>

            <p class="hero-description">
                Discover local farmers, explore nearby markets,
                and find fresh products — all through one simple
                marketplace.
            </p>

            <div class="hero-actions">

                <a href="public/markets.php" class="hero-primary">
                    Explore markets
                    <span>↗</span>
                </a>

                <a href="public/farmers.php" class="hero-secondary">
                    Meet the farmers
                </a>

            </div>

            <div class="hero-note">
                <span class="hero-note-line"></span>
                Supporting local food, one connection at a time.
            </div>

        </div>


        <!-- IMAGE SIDE -->

        <div class="hero-visual">

            <div class="hero-image-frame">

                <img
                    src="assets/images/hero-produce.jpg"
                    alt="Fresh local vegetables arranged at a market"
                >

                <div class="hero-image-overlay"></div>

                <div class="hero-image-label">
                    <span class="label-dot"></span>
                    Fresh from local growers
                </div>

            </div>


            <div class="hero-side-note">
                <span class="side-note-number">01</span>

                <span class="side-note-text">
                    FIND<br>
                    WHAT'S<br>
                    NEARBY
                </span>
            </div>

        </div>

    </div>


    <!-- Bottom information strip -->

    <div class="hero-bottom">

        <div class="hero-bottom-inner">

            <div class="hero-stat">
                <strong>LOCAL</strong>
                <span>Farmers & growers</span>
            </div>

            <div class="hero-stat">
                <strong>FRESH</strong>
                <span>Seasonal products</span>
            </div>

            <div class="hero-stat">
                <strong>NEARBY</strong>
                <span>Markets around you</span>
            </div>

            <div class="hero-scroll">
                <span>SCROLL TO EXPLORE</span>
                <span class="scroll-arrow">↓</span>
            </div>

        </div>

    </div>

</section>

<section class="value-section">

    <div class="value-inner">

        <div class="value-heading">

            <div class="section-marker">
                <span>01</span>
                WHY MARKETLINK
            </div>

            <h2>
                Local food starts
                <span>with a connection.</span>
            </h2>

            <p>
                MarketLink brings customers, farmers, products,
                and markets together in one place.
            </p>

        </div>


        <div class="value-grid">

            <article class="value-card value-card-sage">

                <div class="value-card-top">
                    <span class="value-card-number">01</span>

                    <span class="value-icon">
                        ↗
                    </span>
                </div>

                <div>
                    <h3>Discover</h3>

                    <p>
                        Find farmers and markets around you
                        without the usual searching.
                    </p>
                </div>

            </article>


            <article class="value-card value-card-terracotta">

                <div class="value-card-top">
                    <span class="value-card-number">02</span>

                    <span class="value-icon">
                        ↗
                    </span>
                </div>

                <div>
                    <h3>Explore</h3>

                    <p>
                        Browse fresh products, learn about
                        local growers, and see what's available.
                    </p>
                </div>

            </article>


            <article class="value-card value-card-yellow">

                <div class="value-card-top">
                    <span class="value-card-number">03</span>

                    <span class="value-icon">
                        ↗
                    </span>
                </div>

                <div>
                    <h3>Connect</h3>

                    <p>
                        Make local food easier to find and
                        easier to access.
                    </p>
                </div>

            </article>

        </div>

    </div>

</section>

<section class="explore-section">

    <div class="explore-inner">

        <div class="explore-heading">

            <div>
                <div class="section-marker">
                    <span>02</span>
                    EXPLORE MARKETLINK
                </div>

                <h2>
                    Something fresh
                    <span>is waiting.</span>
                </h2>
            </div>

            <a href="public/markets.php" class="outline-link">
                Explore everything
                <span>↗</span>
            </a>

        </div>


        <div class="explore-grid">

            <a href="public/markets.php"
               class="explore-card explore-market">

                <div class="explore-card-top">
                    <span>01 / MARKETS</span>
                    <span class="card-arrow">↗</span>
                </div>

                <div class="explore-card-bottom">
                    <h3>
                        Find your
                        <em>market.</em>
                    </h3>

                    <p>
                        Discover markets and places
                        where local products come together.
                    </p>
                </div>

            </a>


            <a href="public/farmers.php"
               class="explore-card explore-farmers">

                <div class="explore-card-top">
                    <span>02 / FARMERS</span>
                    <span class="card-arrow">↗</span>
                </div>

                <div class="explore-card-bottom">
                    <h3>
                        Meet the
                        <em>growers.</em>
                    </h3>

                    <p>
                        Learn about the people behind
                        the products you buy.
                    </p>
                </div>

            </a>


            <a href="auth/register.php"
               class="explore-card explore-products">

                <div class="explore-card-top">
                    <span>03 / PRODUCTS</span>
                    <span class="card-arrow">↗</span>
                </div>

                <div class="explore-card-bottom">
                    <h3>
                        Shop what's
                        <em>fresh.</em>
                    </h3>

                    <p>
                        Browse products and discover
                        what's available locally.
                    </p>
                </div>

            </a>

        </div>

    </div>

</section>

<section class="how-section">

    <div class="how-inner">

        <div class="how-heading">

            <div class="section-marker section-marker-light">
                <span>03</span>
                HOW IT WORKS
            </div>

            <h2>
                Simple by design.
            </h2>

            <p>
                MarketLink makes discovering local food
                feel less like searching and more like exploring.
            </p>

        </div>


        <div class="steps">

            <div class="step">

                <div class="step-number">01</div>

                <div class="step-content">
                    <h3>Discover</h3>

                    <p>
                        Find farmers, markets, and fresh
                        products available around you.
                    </p>
                </div>

            </div>


            <div class="step">

                <div class="step-number">02</div>

                <div class="step-content">
                    <h3>Explore</h3>

                    <p>
                        Browse products, compare options,
                        and learn more about local sellers.
                    </p>
                </div>

            </div>


            <div class="step">

                <div class="step-number">03</div>

                <div class="step-content">
                    <h3>Connect</h3>

                    <p>
                        Get closer to the people and places
                        behind your local food.
                    </p>
                </div>

            </div>

        </div>

    </div>

</section>

<section class="final-cta">

    <div class="final-cta-inner">

        <div class="cta-copy">

            <div class="section-marker section-marker-light">
                READY WHEN YOU ARE
            </div>

            <h2>
                Get closer to
                <em>local.</em>
            </h2>

            <p>
                Explore what's growing, selling, and happening
                around you.
            </p>

        </div>


        <div class="cta-actions">

            <a href="auth/register.php" class="cta-button">
                Join MarketLink
                <span>↗</span>
            </a>

            <a href="auth/login.php" class="cta-login">
                Already a member?
                <strong>Log in</strong>
            </a>

        </div>

    </div>

</section>

</main>

<footer class="home-footer">

    <div class="footer-inner">

        <div class="footer-top">

            <div class="footer-brand">

                <a href="index.php" class="home-brand">

                    <span class="brand-mark">
                        M
                    </span>

                    <span class="brand-name">
                        MarketLink
                    </span>

                </a>

                <p>
                    Bringing local food closer
                    to the people who love it.
                </p>

            </div>


            <div class="footer-columns">

                <div>
                    <h4>Explore</h4>

                    <a href="public/markets.php">Markets</a>
                    <a href="public/farmers.php">Farmers</a>
                    <a href="auth/register.php">Products</a>
                </div>

                <div>
                    <h4>MarketLink</h4>

                    <a href="public/about.php">About</a>
                    <a href="public/contact.php">Contact</a>
                    <a href="auth/login.php">Log in</a>
                </div>

            </div>

        </div>


        <div class="footer-bottom">

            <span>
                © <?php echo date('Y'); ?> MarketLink
            </span>

            <span>
                Local food. Local people. One place.
            </span>

        </div>

    </div>

</footer>


</body>
</html>
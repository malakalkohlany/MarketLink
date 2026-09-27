<?php

require_once '../includes/include.php';

/*
|--------------------------------------------------------------------------
| Contact Form
|--------------------------------------------------------------------------
*/

$contactSuccess = '';
$contactError = '';

$email = '';
$subject = 'Question';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF Protection
    |--------------------------------------------------------------------------
    */

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {

        $contactError = 'Your session has expired. Please refresh the page and try again.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Get Form Data
        |--------------------------------------------------------------------------
        */

        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        /*
        |--------------------------------------------------------------------------
        | Validate Form Data
        |--------------------------------------------------------------------------
        */

        if ($email === '') {

            $contactError = 'Please enter your email address.';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $contactError = 'Please enter a valid email address.';

        } elseif ($subject === '') {

            $contactError = 'Please enter a subject.';

        } elseif (strlen($subject) > 255) {

            $contactError = 'The subject is too long. Please keep it under 255 characters.';

        } elseif ($message === '') {

            $contactError = 'Please enter a message.';

        } elseif (strlen($message) > 5000) {

            $contactError = 'Your message is too long. Please keep it under 5000 characters.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Email Configuration
            |--------------------------------------------------------------------------
            */

            $to = 'potentiat@gmail.com';

            /*
            |--------------------------------------------------------------------------
            | Clean Header Values
            |--------------------------------------------------------------------------
            |
            | Prevent header injection by removing CR/LF characters.
            |
            */

            $safeEmail = str_replace(
                ["\r", "\n"],
                '',
                $email
            );

            $safeSubject = str_replace(
                ["\r", "\n"],
                '',
                $subject
            );

            /*
            |--------------------------------------------------------------------------
            | Email Subject
            |--------------------------------------------------------------------------
            */

            $mailSubject = 'MarketLink Contact: ' . $safeSubject;

            /*
            |--------------------------------------------------------------------------
            | Email Body
            |--------------------------------------------------------------------------
            */

            $mailBody =
                "You have received a new message through the MarketLink contact form.\n\n" .
                "----------------------------------------\n" .
                "Sender Email: {$safeEmail}\n" .
                "Subject: {$safeSubject}\n" .
                "----------------------------------------\n\n" .
                "Message:\n\n" .
                $message .
                "\n\n" .
                "----------------------------------------\n" .
                "This message was sent from the MarketLink website.\n";

            /*
            |--------------------------------------------------------------------------
            | Email Headers
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            | Use a fixed From address belonging to your website/domain.
            | Do NOT use the visitor's email as the From address.
            |
            */

            $headers = [];

            $headers[] = 'MIME-Version: 1.0';

            $headers[] = 'Content-Type: text/plain; charset=UTF-8';

            $headers[] = 'From: MarketLink <noreply@marketlink.local>';

            $headers[] = 'Reply-To: ' . $safeEmail;

            $headers[] = 'X-Mailer: PHP/' . phpversion();

            $headersString = implode("\r\n", $headers);

            /*
            |--------------------------------------------------------------------------
            | Send Email
            |--------------------------------------------------------------------------
            */

            $mailSent = mail(
                $to,
                $mailSubject,
                $mailBody,
                $headersString
            );

            if ($mailSent) {

                $contactSuccess =
                    'Your message has been sent successfully. ' .
                    'We will get back to you as soon as possible.';

                /*
                |--------------------------------------------------------------------------
                | Clear Form
                |--------------------------------------------------------------------------
                */

                $email = '';
                $subject = 'Question';

                $message =
                    "Hello MarketLink team,\n\n" .
                    "I have a question about: ";

            } else {

                $contactError =
                    'We could not send your message right now. ' .
                    'Please try again later.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Contact Us - MarketLink</title>

    <link
        rel="stylesheet"
        href="../assets/css/base.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/homepage.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/contact.css"
    >

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

</head>

<body class="contact-page">

    <header class="home-navbar">

        <div class="home-nav-inner">

            <a href="../index.php" class="home-brand">

                <span class="brand-mark">M</span>

                <span class="brand-name">
                    MarketLink
                </span>

            </a>

            <nav class="home-nav-links">

                <a href="../index.php">
                    Home
                </a>

                <a href="about.php">
                    About
                </a>

                <a href="markets.php">
                    Markets
                </a>

                <a href="farmers.php">
                    Farmers
                </a>

                <a href="#" class="active">
                    Contact
                </a>

            </nav>

            <div class="home-nav-actions">

                <a
                    href="../auth/login.php"
                    class="home-login"
                >
                    Login
                </a>

                <a
                    href="../auth/register.php"
                    class="home-join"
                >
                    Join MarketLink
                    <span>↗</span>
                </a>

            </div>

        </div>

    </header>


    <main>

        <!-- =================================================
             HERO
        ================================================== -->

        <section class="contact-hero">

            <div
                class="contact-hero-decoration contact-decoration-left"
            ></div>

            <div
                class="contact-hero-decoration contact-decoration-right"
            ></div>

            <div class="container contact-hero-inner">

                <div class="contact-hero-copy">

                    <span class="contact-eyebrow">
                        GET IN TOUCH
                    </span>

                    <h1>
                        Let's
                        <em>talk.</em>
                    </h1>

                    <p>
                        Questions, feedback, market information,
                        or something not working as expected?
                        We'd love to hear from you.
                    </p>

                    <div class="contact-hero-note">

                        <span>QUESTIONS</span>
                        <span>FEEDBACK</span>
                        <span>SUPPORT</span>

                    </div>

                </div>

                <div class="contact-hero-mark">

                    <span>✦</span>

                </div>

            </div>

        </section>


        <!-- =================================================
             CONTACT SECTION
        ================================================== -->

        <section class="contact-section">

            <div class="container">

                <div class="contact-grid">


                    <!-- =====================================
                         MESSAGE FORM
                    ====================================== -->

                    <div class="contact-form-card">

                        <div class="contact-section-heading">

                            <span class="contact-eyebrow">
                                SEND A MESSAGE
                            </span>

                            <h2>
                                How can we
                                <em>help?</em>
                            </h2>

                            <p>
                                Choose a topic below and tell us
                                what is on your mind.
                            </p>

                        </div>


                        <!-- =================================
                             SUCCESS MESSAGE
                        ================================== -->

                        <?php if ($contactSuccess !== ''): ?>

                            <div
                                class="contact-alert contact-alert-success"
                                role="alert"
                            >
                                <?= e($contactSuccess) ?>
                            </div>

                        <?php endif; ?>


                        <!-- =================================
                             ERROR MESSAGE
                        ================================== -->

                        <?php if ($contactError !== ''): ?>

                            <div
                                class="contact-alert contact-alert-error"
                                role="alert"
                            >
                                <?= e($contactError) ?>
                            </div>

                        <?php endif; ?>


                        <!-- =================================
                             CONTACT TYPE
                        ================================== -->

                        <div class="contact-types">

                            <button
                                type="button"
                                class="contact-type active"
                                onclick="selectContactType('Question', this)"
                            >

                                <span class="contact-type-number">
                                    01
                                </span>

                                <span>
                                    Question
                                </span>

                            </button>


                            <button
                                type="button"
                                class="contact-type"
                                onclick="selectContactType('Report a Problem', this)"
                            >

                                <span class="contact-type-number">
                                    02
                                </span>

                                <span>
                                    Report a Problem
                                </span>

                            </button>


                            <button
                                type="button"
                                class="contact-type"
                                onclick="selectContactType('Market Information', this)"
                            >

                                <span class="contact-type-number">
                                    03
                                </span>

                                <span>
                                    Market Information
                                </span>

                            </button>


                            <button
                                type="button"
                                class="contact-type"
                                onclick="selectContactType('Feedback', this)"
                            >

                                <span class="contact-type-number">
                                    04
                                </span>

                                <span>
                                    Feedback
                                </span>

                            </button>

                        </div>


                        <!-- =================================
                             FORM
                        ================================== -->

                        <form
                            class="contact-form"
                            action=""
                            method="POST"
                        >

                            <?= csrf_field() ?>


                            <div class="contact-form-row">


                                <!-- EMAIL -->

                                <div class="contact-field">

                                    <label for="email">
                                        Your Email
                                    </label>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="<?= e($email) ?>"
                                        placeholder="you@example.com"
                                        maxlength="255"
                                        autocomplete="email"
                                        required
                                    >

                                </div>


                                <!-- SUBJECT -->

                                <div class="contact-field">

                                    <label for="subject">
                                        Subject
                                    </label>

                                    <input
                                        type="text"
                                        id="subject"
                                        name="subject"
                                        value="<?= e($subject) ?>"
                                        placeholder="What is this about?"
                                        maxlength="255"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- MESSAGE -->

                            <div class="contact-field">

                                <label for="message">
                                    Message
                                </label>

                                <textarea
                                    id="message"
                                    name="message"
                                    rows="8"
                                    maxlength="5000"
                                    placeholder="Write your message here..."
                                    required
                                ><?= e($message) ?></textarea>

                            </div>


                            <!-- SUBMIT -->

                            <button
                                type="submit"
                                class="contact-submit"
                            >

                                Send Message

                                <span>↗</span>

                            </button>

                        </form>

                    </div>


                    <!-- =====================================
                         SIDE INFORMATION
                    ====================================== -->

                    <aside class="contact-side">


                        <div class="contact-info-card contact-info-sage">

                            <span class="contact-card-number">
                                01
                            </span>

                            <h3>
                                Questions?
                            </h3>

                            <p>
                                Need help understanding how
                                MarketLink works? Send us a message
                                and we'll point you in the right direction.
                            </p>

                            <span class="contact-card-label">
                                GENERAL SUPPORT
                            </span>

                        </div>


                        <div class="contact-info-card contact-info-marigold">

                            <span class="contact-card-number">
                                02
                            </span>

                            <h3>
                                Found a problem?
                            </h3>

                            <p>
                                Tell us what went wrong and give us
                                as much detail as possible so we can
                                investigate it.
                            </p>

                            <span class="contact-card-label">
                                REPORT AN ISSUE
                            </span>

                        </div>


                        <div class="contact-info-card contact-info-dark">

                            <span class="contact-card-number">
                                03
                            </span>

                            <h3>
                                Local markets.
                            </h3>

                            <p>
                                Want to provide information about a
                                market or suggest an update?
                                We'd love to hear from you.
                            </p>

                            <span class="contact-card-label">
                                MARKET INFORMATION
                            </span>

                        </div>


                    </aside>

                </div>

            </div>

        </section>


        <!-- =================================================
             LOCATION
        ================================================== -->

        <section class="contact-location">

            <div class="container">

                <div class="contact-location-heading">

                    <div>

                        <span class="contact-eyebrow">
                            FIND US
                        </span>

                        <h2>
                            Our
                            <em>location.</em>
                        </h2>

                    </div>

                    <p>
                        MarketLink connects people with local
                        markets and farmers. Our platform is
                        designed around the communities it serves.
                    </p>

                </div>


                <div class="contact-map-wrapper">

                    <div id="map"></div>

                    <div class="contact-map-card">

                        <span class="contact-map-label">
                            MARKETLINK
                        </span>

                        <h3>
                            Boston, Massachusetts
                        </h3>

                        <p>
                            Connecting you with local markets.
                        </p>

                    </div>

                </div>

            </div>

        </section>


        <!-- =================================================
             CTA
        ================================================== -->

        <section class="contact-cta">

            <div class="container contact-cta-inner">

                <div>

                    <span class="contact-eyebrow">
                        MARKETLINK
                    </span>

                    <h2>
                        Local markets,
                        <em>closer to you.</em>
                    </h2>

                </div>


                <a
                    href="../auth/register.php"
                    class="contact-cta-button"
                >

                    Join MarketLink

                    <span>↗</span>

                </a>

            </div>

        </section>

    </main>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <footer class="home-footer">

        <div class="footer-inner">

            <div class="footer-bottom">

                <p>
                    © <?= date('Y') ?> MarketLink.
                    All rights reserved.
                </p>

                <p>
                    Connecting you with local markets.
                </p>

            </div>

        </div>

    </footer>


    <!-- =====================================================
         LEAFLET
    ====================================================== -->

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    ></script>


    <script>

        /*
        ======================================================
        CONTACT TYPE SELECTOR
        ======================================================
        */

        function selectContactType(type, button) {

            const subject =
                document.getElementById('subject');

            const message =
                document.getElementById('message');


            document
                .querySelectorAll('.contact-type')
                .forEach(function (item) {

                    item.classList.remove('active');

                });


            button.classList.add('active');


            subject.value = type;


            if (type === 'Question') {

                message.value =
                    'Hello MarketLink team,\n\n' +
                    'I have a question about: ';

            }

            else if (type === 'Report a Problem') {

                message.value =
                    'Hello MarketLink team,\n\n' +
                    'I would like to report a problem:\n\n';

            }

            else if (type === 'Market Information') {

                message.value =
                    'Hello MarketLink team,\n\n' +
                    'I would like to provide or update market information:\n\n';

            }

            else if (type === 'Feedback') {

                message.value =
                    'Hello MarketLink team,\n\n' +
                    'I would like to share the following feedback:\n\n';

            }


            message.focus();

        }


        /*
        ======================================================
        MAP
        ======================================================
        */

        const marketLinkLatitude = 42.3555;

        const marketLinkLongitude = -71.0565;


        const map = L
            .map('map')
            .setView(
                [
                    marketLinkLatitude,
                    marketLinkLongitude
                ],
                4
            );


        L.tileLayer(
            'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
            {
                maxZoom: 19,

                attribution:
                    '&copy; OpenStreetMap contributors'
            }
        ).addTo(map);


        L.marker([
            marketLinkLatitude,
            marketLinkLongitude
        ])
        .addTo(map)
        .bindPopup(
            '<b>MarketLink</b><br>Boston, Massachusetts'
        )
        .openPopup();

    </script>

</body>
</html>
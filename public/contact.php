<?php

require_once '../config/database.php';
require_once '../includes/functions.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Us - MarketLink</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <style>
        #map {
            width: 100%;
            height: 450px;
        }
    </style>
</head>

<body>
    <h1>Contact Us</h1>

    <p>
        If you have any questions, suggestions, or problems,
        please contact the MarketLink team.
    </p>

    <h2>How Can We Help?</h2>

    <div>

        <button
            type="button"
            onclick="selectContactType('Question')"
        >
            Question
        </button>

        <button
            type="button"
            onclick="selectContactType('Report a Problem')"
        >
            Report a Problem
        </button>

        <button
            type="button"
            onclick="selectContactType('Market Information')"
        >
            Market Information
        </button>

        <button
            type="button"
            onclick="selectContactType('Feedback')"
        >
            Feedback
        </button>

    </div>

    <br>

    <h2>Send Us a Message</h2>

    <form
        action="mailto:potentiat@gmail.com"
        method="POST"
        enctype="text/plain"
    >

        <label for="email">
            Your Email
        </label>

        <br>

        <input
            type="email"
            id="email"
            name="Email"
            placeholder="Enter your email address"
            required
        >

        <br><br>

        <label for="subject">
            Subject
        </label>

        <br>

        <input
            type="text"
            id="subject"
            name="Subject"
            placeholder="Enter subject"
            required
        >

        <br><br>

        <label for="message">
            Message
        </label>

        <br>

        <textarea
            id="message"
            name="Message"
            rows="8"
            cols="50"
            placeholder="Write your message here..."
            required
        ></textarea>
         <br><br>

        <button type="submit">
            Send Message
        </button>

    </form>

    <br><br>

    <h2>Our Location</h2>

    <div id="map"></div>

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    ></script>

    <script>

        function selectContactType(type) {

            const subject =
                document.getElementById('subject');

            const message =
                document.getElementById('message');

            subject.value = type;

            if (type === 'Question') {

                message.value =
                    'Hello MarketLink team,\n\n' +
                    'I have a question about: ';

            } else if (type === 'Report a Problem') {

                message.value =
                    'Hello MarketLink team,\n\n' +
                    'I would like to report a problem:\n\n';

            } else if (type === 'Market Information') {

                message.value =
                    'Hello MarketLink team,\n\n' +
                    'I would like to provide or update market information:\n\n';

            } else if (type === 'Feedback') {

                message.value =
                    'Hello MarketLink team,\n\n' +
                    'I would like to share the following feedback:\n\n';

            }

            message.focus();
        }

        const marketLinkLatitude = 42.3555;
        const marketLinkLongitude = -71.0565;

        const map = L.map('map').setView(
            [marketLinkLatitude, marketLinkLongitude],
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


<?php

require_once '../config/database.php';
require_once '../includes/functions.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Us - MarketLink</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <!-- Leaflet CSS -->
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >
    <style>
        #map {
            width: 100%;
            height: 450px;
        }
    </style>
</head>

<body>

    <h1>Contact Us</h1>

    <p>
        If you have any questions, suggestions, or problems,
        please contact the MarketLink team.
    </p>
        <h2>How Can We Help?</h2>

    <div>

        <button
            type="button"
            onclick="selectContactType('Question')"
        >
            Question
        </button>

        <button
            type="button"
            onclick="selectContactType('Report a Problem')"
        >
            Report a Problem
        </button>

        <button
            type="button"
            onclick="selectContactType('Market Information')"
        >
            Market Information
        </button>

        <button
            type="button"
            onclick="selectContactType('Feedback')"
        >
            Feedback
        </button>

    </div>


    <br>
       <h2>Send Us a Message</h2>

    <form
        action="mailto:YOUR_REGISTERED_EMAIL@example.com"
        method="POST"
        enctype="text/plain"
    >

        <label for="email">
            Your Email
        </label>

        <br>

        <input
            type="email"
            id="email"
            name="Email"
            placeholder="Enter your email address"
            required
        >

        <br><br>


        <label for="subject">
            Subject
        </label>

        <br>

        <input
            type="text"
            id="subject"
            name="Subject"
            placeholder="Enter subject"
            required
        >

        <br><br>


        <label for="message">
            Message
        </label>

        <br>

        <textarea
            id="message"
            name="Message"
            rows="8"
            cols="50"
            placeholder="Write your message here..."
            required
        ></textarea>

        <br><br>


        <button type="submit">
            Send Message
        </button>

    </form>


    <br><br>
 <h2>Our Location</h2>

    <div id="map"></div>


  <!-- Leaflet JavaScript -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script src="../assets/js/contact.js"></script>

</body>
</html>

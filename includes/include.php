<?php

// Configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

// Start session
require_once __DIR__ . '/session.php';

// Authentication & security
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

// Utility functions
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/flash.php';

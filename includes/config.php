<?php
// ============================================================
// includes/config.php — Configuration générale
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'the_legacy_house');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'The legacy House');
define('SITE_TAGLINE', 'Le marché qui a du goût en Auto');
define('BASE_URL', 'http://localhost/the_legacy_house');

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// ============================================================
// includes/db.php — Connexion PDO singleton
// ============================================================

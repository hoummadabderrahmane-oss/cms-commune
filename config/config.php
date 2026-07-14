<?php

define('APP_NAME', 'SGC');

define('APP_VERSION', '1.0');

define('BASE_URL', 'http://localhost/sgc/');

date_default_timezone_set('Africa/Casablanca');

session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
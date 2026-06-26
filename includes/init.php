<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', 'Asia/Kuala_Lumpur');
}

date_default_timezone_set(APP_TIMEZONE);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

start_secure_session();

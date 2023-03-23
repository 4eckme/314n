<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWD', '');
define('DB_DATABASE', '314n');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

error_reporting(E_ALL ^ E_NOTICE);
set_error_handler(function(int $errno, string $errstr) {
  if ((strpos($errstr, 'Undefined array key') === false) && (strpos($errstr, 'Undefined variable') === false)) {
    return false;
  } else {
    return true;
  }
}, E_WARNING);
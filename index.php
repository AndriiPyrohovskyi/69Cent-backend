<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

require_once 'config/config.php';
require_once 'routes/api.php';
require_once 'config/database.php';
?>
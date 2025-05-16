<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

require_once 'controllers/AuthController.php';

$authController = new AuthController();

switch ($uri) {
    case '/':
        echo "API is running";
        break;

    case '/api/register':
        $authController->register();
        break;

    case '/api/login':
        $authController->login();
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
}
?>
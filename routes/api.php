<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

require_once 'controllers/AuthController.php';
require_once 'config/database.php';

$authController = new AuthController($pdo);

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
    case '/api/current_user':
        $authController->get_current_user();
        break;
    case '/api/refresh_token':
        $authController->refresh_token();
        break;
    case '/api/logout':
        $authController->logout();
        break;  
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
}
?>
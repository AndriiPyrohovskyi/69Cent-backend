<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

require_once 'controllers/AuthController.php';
require_once 'controllers/LikeTypeController.php';
require_once 'controllers/CategoryController.php';
require_once 'config/database.php';

$authController = new AuthController($pdo);
$likeTypeController = new LikeTypeController($pdo);
$categoryController = new CategoryController($pdo);

$decodedUri = urldecode($uri);

switch ($decodedUri) {
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

    // LikeType routes
    case '/api/like_types':
        if ($method === 'POST') {
            $likeTypeController->create();
        } elseif ($method === 'GET') {
            $likeTypeController->getAll();
        }
        break;

    case (preg_match('/\/api\/like_types\/([\w\-\p{L}]+)/u', $decodedUri, $matches) ? true : false):
        $name = $matches[1];
        if ($method === 'PUT') {
            $likeTypeController->update($name);
        } elseif ($method === 'DELETE') {
            $likeTypeController->delete($name);
        }
        break;

    // Category routes
    case '/api/categories':
        if ($method === 'POST') {
            $categoryController->create();
        } elseif ($method === 'GET') {
            $categoryController->getAll();
        }
        break;

    case (preg_match('/\/api\/categories\/([\w\-\p{L}\s]+)/u', urldecode($uri), $matches) ? true : false):
        $name = $matches[1];
        if ($method === 'PUT') {
            $categoryController->update($name);
        } elseif ($method === 'DELETE') {
            $categoryController->delete($name);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
}
?>
<?php
function errorHandler($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error $errno: $errstr in $errfile on line $errline");
    return true;
}
set_error_handler('errorHandler');
ini_set('display_errors', 0);
ob_start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

require_once 'controllers/AuthController.php';
require_once 'controllers/LikeTypeController.php';
require_once 'controllers/CategoryController.php';
require_once 'controllers/UserController.php';
require_once 'controllers/PostController.php';
require_once 'config/database.php';

$authController = new AuthController($pdo);
$likeTypeController = new LikeTypeController($pdo);
$categoryController = new CategoryController($pdo);
$userController = new UserController($pdo);
$postController = new PostController($pdo);

$decodedUri = urldecode($uri);

switch ($decodedUri) {
    case '/':
        echo 'error_log path: ' . ini_get('error_log');
        break;

    case '/api/register':
        $authController->register();
        break;

    case '/api/login':
        $authController->login();
        break;

    case '/api/current_user':
        if ($method === 'GET') {
            $authController->get_current_user();
        }
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

    // User routes
    case '/api/users':
        if ($method === 'GET') {
            $userController->getAll();
        }
        break;

    case (preg_match('/\/api\/users\/(\d+)/', $decodedUri, $matches) ? true : false):
        $userId = $matches[1];
        if ($method === 'GET') {
            $userController->getById($userId);
        } elseif ($method === 'PUT') {
            $userController->update($userId);
        } elseif ($method === 'DELETE') {
            $userController->delete($userId);
        }
        break;

    // Post routes
    case '/api/posts':
        $postController->getAll();
    break;

    case '/api/create_post': {
        $postController->create();
        }
    break;
    case (preg_match('/\/api\/posts\/(\d+)\/like/', $decodedUri, $matches) ? true : false):
        $postId = $matches[1];
        // Видаліть усі діагностичні echo
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            error_log("Received like data: " . print_r($data, true));

            // Перевірка наявності необхідних даних
            if (!isset($data['user_id']) || !isset($data['like_type_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                break;
            }
            
            $userId = $data['user_id'];
            $likeTypeId = $data['like_type_id'];
            $postController->like($postId, $userId, $likeTypeId);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed, use POST']);
        }
        break;
    case (preg_match('/\/api\/posts\/(\d+)/', $decodedUri, $matches) ? true : false):
        $postId = $matches[1];
        if ($method === 'GET') {
            $postController->getById($postId);
        } elseif ($method === 'PUT') {
            $postController->update($postId);
        } elseif ($method === 'DELETE') {
            $postController->delete($postId);
        }
        break;

    case (preg_match('/\/api\/posts\/user\/(\d+)/', $decodedUri, $matches) ? true : false):
        $userId = $matches[1];
        if ($method === 'GET') {
            $postController->getByUser($userId);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
}
?>
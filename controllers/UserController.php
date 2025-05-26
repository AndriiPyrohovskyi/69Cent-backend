<?php
require_once 'models/User.php';

class UserController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $users = User::getAll($this->pdo);
        echo json_encode($users);
    }

    public function getById($id) {
        $user = User::getById($this->pdo, $id);
        if ($user) {
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
    }

    public function update($id) {
        try {
            // Перевіряємо права доступу
            $currentUserId = null;
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
                if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                    $token = $matches[1];
                    $userData = verifyJWT($token);
                    if ($userData) {
                        $currentUserId = $userData->id;
                    }
                }
            }
            
            // Тільки користувач може редагувати свій профіль або адміністратор може редагувати будь-який
            $user = User::getById($this->pdo, $id);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }
            
            $currentUser = User::getById($this->pdo, $currentUserId);
            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
            
            if ($currentUserId != $id && $currentUser['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
                return;
            }
            
            // Отримуємо дані з запиту
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Перевіряємо наявність обов'язкових полів
            if (!isset($data['username']) || !isset($data['email'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Username and email are required']);
                return;
            }
            
            // Перевірка зміни пароля
            if (isset($data['new_password'])) {
                if (!isset($data['current_password'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Current password is required to change password']);
                    return;
                }
                
                // Перевіряємо поточний пароль
                if (!password_verify($data['current_password'], $user['password'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Current password is incorrect']);
                    return;
                }
                
                // Хешуємо новий пароль
                $data['password'] = password_hash($data['new_password'], PASSWORD_DEFAULT);
                
                // Видаляємо тимчасові поля
                unset($data['current_password']);
                unset($data['new_password']);
            }
            
            // Оновлюємо дані користувача
            $result = User::update($this->pdo, $id, $data);
            
            if ($result) {
                // Повертаємо оновлені дані користувача
                $updatedUser = User::getById($this->pdo, $id);
                echo json_encode($updatedUser);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Failed to update user data']);
            }
        } catch (Exception $e) {
            error_log('Error in UserController::update: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }

    public function delete($id) {
        $result = User::delete($this->pdo, $id);
        if ($result) {
            echo json_encode(['message' => 'User deleted successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to delete user']);
        }
    }

    public function getPopularAuthors($limit = 5) {
        try {
            $users = User::getPopularAuthors($this->pdo, $limit);
            header('Content-Type: application/json');
            echo json_encode($users);
        } catch (Exception $e) {
            error_log('Error in UserController::getPopularAuthors: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }
}
?>
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
            // Перевіряємо авторизацію користувача
            $currentUser = $this->getCurrentUser();
            
            if (!$currentUser) {
                error_log("Unauthorized update attempt for user ID: $id");
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
            
            // Перевіряємо права: користувач може редагувати лише свій профіль або адмін може редагувати будь-який
            if ($currentUser->id != $id && $currentUser->role !== 'admin') {
                error_log("Forbidden update attempt: user ID {$currentUser->id} tried to update user ID $id");
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
                return;
            }
            
            // Отримуємо дані з запиту
            $data = json_decode(file_get_contents('php://input'), true);
            error_log("Updating user ID $id with data: " . json_encode($data));
            
            // Оновлюємо користувача
            $result = User::update($this->pdo, $id, $data);
            
            if ($result) {
                // Повертаємо оновлені дані користувача
                $user = User::getById($this->pdo, $id);
                unset($user['password']); // Не повертаємо пароль у відповіді
                
                header('Content-Type: application/json');
                echo json_encode($user);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Failed to update user']);
            }
        } catch (Exception $e) {
            error_log("Error in UserController::update: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
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

    // Допоміжний метод для отримання поточного користувача з JWT токена
    private function getCurrentUser() {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            error_log("Authorization header: " . $authHeader);
            
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
                error_log("Token extracted: " . $token);
                
                // Перевірка JWT токена
                require_once 'utils/jwt_utils.php'; // Переконайтеся, що файл з функцією verifyJWT підключений
                $userData = verifyJWT($token);
                
                error_log("Token verification result: " . json_encode($userData));
                
                if ($userData) {
                    return $userData;
                }
            }
        }
        
        return null;
    }
}
?>
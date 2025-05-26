<?php

require_once 'helpers/jwt.php';
require_once 'helpers/response.php';

class AuthController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        if (!$username || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $role = $user['role'] ?? 'user';
                $jwt = generateJWT($user['id'], $user['username'], $role);
                http_response_code(200);
                echo json_encode(['token' => $jwt]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function register() {
        $data = json_decode(file_get_contents("php://input"), true);
        $username = $data['username'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$username || !$email || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword
            ]);
            http_response_code(201);
            echo json_encode(['message' => 'User registered successfully']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }
    public function get_current_user() {
        header('Content-Type: application/json');
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $user_data = verifyJWT($token);
            
            if ($user_data) {
                try {
                    $stmt = $this->pdo->prepare("SELECT id, username, email, role, avatar_url, created_at FROM users WHERE id = :id");
                    $stmt->execute(['id' => $user_data->id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        $karmaStmt = $this->pdo->prepare("
                            SELECT COALESCE(SUM(lt.carma), 0) AS karma
                            FROM users u
                            LEFT JOIN posts p ON u.id = p.user_id
                            LEFT JOIN likes l ON p.id = l.post_id
                            LEFT JOIN like_types lt ON l.like_type_id = lt.id
                            WHERE u.id = :user_id
                            GROUP BY u.id
                        ");
                        $karmaStmt->execute(['user_id' => $user_data->id]);
                        $karmaResult = $karmaStmt->fetch(PDO::FETCH_ASSOC);
                        $user['karma'] = $karmaResult ? intval($karmaResult['karma']) : 0;
                        
                        http_response_code(200);
                        echo json_encode($user);
                    } else {
                        http_response_code(404);
                        echo json_encode(['message' => 'Користувача не знайдено в базі даних']);
                    }
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Помилка бази даних: ' . $e->getMessage()]);
                }
                return;
            } else {
                http_response_code(401);
                echo json_encode(['message' => 'Недійсний токен']);
                return;
            }
        }
        http_response_code(401);
        echo json_encode(['message' => 'Токен не надано']);
    }
    public function refresh_token() {
        header('Content-Type: application/json');
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
    
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $user_data = verifyJWT($token);
    
            if ($user_data) {
                $newToken = generateJWT($user_data->id, $user_data->username, $user_data->role);
                http_response_code(200);
                echo json_encode(['token' => $newToken]);
                return;
            }
        }
    
        http_response_code(401);
        echo json_encode(['message' => 'Не вдалося оновити токен']);
    }
    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/'); // Видаляємо cookie сесії
        http_response_code(200);
        echo json_encode(['message' => 'Вихід виконано успішно']);
    }
}
?>
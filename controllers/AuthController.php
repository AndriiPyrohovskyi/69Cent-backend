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
}
?>
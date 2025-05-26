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
        $data = json_decode(file_get_contents('php://input'), true);
        $result = User::update($this->pdo, $id, $data);
        if ($result) {
            echo json_encode(['message' => 'User updated successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to update user']);
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
}
?>
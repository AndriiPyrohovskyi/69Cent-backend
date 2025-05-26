<?php

require_once 'helpers/response.php';
require_once 'models/LikeType.php';

class LikeTypeController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['name']) || !isset($data['icon_url']) || !isset($data['carma'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Name, icon_url, and carma are required']);
            return;
        }

        if (LikeType::create($this->pdo, $data)) {
            echo json_encode(['message' => 'Like type created']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create like type']);
        }
    }

    public function update($name) {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['name']) || !isset($data['carma']) || !isset($data['icon_url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Name, icon_url, and carma are required']);
            return;
        }

        if (LikeType::update($this->pdo, $name, $data)) {
            echo json_encode(['message' => 'Like type updated']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update like type']);
        }
    }

    public function delete($name) {
        if (LikeType::delete($this->pdo, $name)) {
            echo json_encode(['message' => 'Like type deleted']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete like type']);
        }
    }

    public function getAll() {
        $likeTypes = LikeType::getAll($this->pdo);
        echo json_encode($likeTypes);
    }
}
?>
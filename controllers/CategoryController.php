<?php

require_once 'helpers/response.php';
require_once 'models/Category.php';

class CategoryController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Name is required']);
            return;
        }

        if (Category::create($this->pdo, $data)) {
            echo json_encode(['message' => 'Category created']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create category']);
        }
    }

    public function update($name) {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['new_name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'New name is required']);
            return;
        }

        if (Category::update($this->pdo, $name, $data)) {
            echo json_encode(['message' => 'Category updated']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update category']);
        }
    }

    public function delete($name) {
        if (Category::delete($this->pdo, $name)) {
            echo json_encode(['message' => 'Category deleted']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete category']);
        }
    }

    public function getAll() {
        $categories = Category::getAll($this->pdo);
        echo json_encode($categories);
    }
}
?>
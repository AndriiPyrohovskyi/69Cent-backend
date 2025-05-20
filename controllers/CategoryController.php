<?php

require_once 'helpers/response.php';

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

        $stmt = $this->pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
        $stmt->execute(['name' => $data['name']]);
        echo json_encode(['message' => 'Category created']);
    }

    public function update($name) {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['new_name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'New name is required']);
            return;
        }
        error_log("Category name: " . $name);
        $stmt = $this->pdo->prepare("UPDATE categories SET name = :new_name WHERE name = :name");
        $stmt->execute(['new_name' => $data['new_name'], 'name' => $name]);
        echo json_encode(['message' => 'Category updated']);
    }

    public function delete($name) {
        $stmt = $this->pdo->prepare("DELETE FROM categories WHERE name = :name");
        $stmt->execute(['name' => $name]);
        echo json_encode(['message' => 'Category deleted']);
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM categories");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($categories);
    }
}
?>
<?php

require_once 'helpers/response.php';

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

        $stmt = $this->pdo->prepare("INSERT INTO like_types (name, carma, icon_url) VALUES (:name, :carma, :icon_url)");
        $stmt->execute(['name' => $data['name'], 'carma' => $data['carma'], 'icon_url' => $data['icon_url']]);
        echo json_encode(['message' => 'Like type created']);
    }

    public function update($name) {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['name']) || !isset($data['carma']) || !isset($data['icon_url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Name, icon_url, and carma are required']);
            return;
        }

        $stmt = $this->pdo->prepare("UPDATE like_types SET name = :new_name, icon_url = :icon_url, carma = :carma WHERE name = :name");
        $stmt->execute([
            'new_name' => $data['name'],
            'icon_url' => $data['icon_url'],
            'carma' => $data['carma'],
            'name' => $name
        ]);
        echo json_encode(['message' => 'Like type updated']);
    }

    public function delete($name) {
        $stmt = $this->pdo->prepare("DELETE FROM like_types WHERE name = :name");
        $stmt->execute(['name' => $name]);
        echo json_encode(['message' => 'Like type deleted']);
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM like_types");
        $likeTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($likeTypes);
    }
}
?>
<?php
class Category {
    public static function create($pdo, $data) {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
        return $stmt->execute(['name' => $data['name']]);
    }

    public static function update($pdo, $name, $data) {
        $stmt = $pdo->prepare("UPDATE categories SET name = :new_name WHERE name = :name");
        return $stmt->execute([
            'new_name' => $data['new_name'],
            'name' => $name
        ]);
    }

    public static function delete($pdo, $name) {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE name = :name");
        return $stmt->execute(['name' => $name]);
    }

    public static function getAll($pdo) {
        $stmt = $pdo->query("SELECT * FROM categories");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
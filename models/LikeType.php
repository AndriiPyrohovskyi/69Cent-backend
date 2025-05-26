<?php
class LikeType {
    public static function create($pdo, $data) {
        $stmt = $pdo->prepare("INSERT INTO like_types (name, carma, icon_url) VALUES (:name, :carma, :icon_url)");
        return $stmt->execute([
            'name' => $data['name'],
            'carma' => $data['carma'],
            'icon_url' => $data['icon_url']
        ]);
    }

    public static function update($pdo, $name, $data) {
        $stmt = $pdo->prepare("UPDATE like_types SET name = :new_name, carma = :carma, icon_url = :icon_url WHERE name = :name");
        return $stmt->execute([
            'new_name' => $data['name'],
            'carma' => $data['carma'],
            'icon_url' => $data['icon_url'],
            'name' => $name
        ]);
    }

    public static function delete($pdo, $name) {
        $stmt = $pdo->prepare("DELETE FROM like_types WHERE name = :name");
        return $stmt->execute(['name' => $name]);
    }

    public static function getAll($pdo) {
        $stmt = $pdo->query("SELECT * FROM like_types");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
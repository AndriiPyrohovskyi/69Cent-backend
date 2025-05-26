<?php
class User {
    public static function getAll($pdo) {
        $stmt = $pdo->query("
            SELECT 
                users.id,
                users.username,
                users.email,
                users.role,
                users.avatar_url,
                users.created_at,
                SUM(like_types.carma) AS karma
            FROM users
            LEFT JOIN likes ON users.id = likes.user_id
            LEFT JOIN like_types ON likes.like_type_id = like_types.id
            GROUP BY users.id
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT 
                users.id,
                users.username,
                users.email,
                users.role,
                users.avatar_url,
                users.created_at,
                SUM(like_types.carma) AS karma
            FROM users
            LEFT JOIN likes ON users.id = likes.user_id
            LEFT JOIN like_types ON likes.like_type_id = like_types.id
            WHERE users.id = ?
            GROUP BY users.id
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function update($pdo, $id, $data) {
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;

        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    }

    public static function delete($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>
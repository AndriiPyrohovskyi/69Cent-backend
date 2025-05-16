<?php
class Post {
    public static function getAll() {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM posts");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
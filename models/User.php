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
        try {
            // Спочатку оновлюємо карму
            self::updateKarma($pdo, $id);
            
            // Потім отримуємо користувача з оновленою кармою
            $stmt = $pdo->prepare("
                SELECT id, username, email, role, avatar_url, created_at, karma
                FROM users 
                WHERE id = :id
            ");
            
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error getting user by ID: ' . $e->getMessage());
            return null;
        }
    }

    public static function update($pdo, $id, $data) {
        try {
            // Готуємо SQL-запит з оновленням тільки переданих полів
            $fields = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                if ($key === 'id') continue; // Пропускаємо id
                $fields[] = "$key = :$key";
                $values[$key] = $value;
            }
            
            if (empty($fields)) {
                return true; // Нічого не змінилося
            }
            
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
            $values['id'] = $id;
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log('Error updating user: ' . $e->getMessage());
            return false;
        }
    }

    public static function delete($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function updateKarma($pdo, $userId) {
        try {
            // Запит для розрахунку суми карми з лайків на пости користувача
            $stmt = $pdo->prepare("
                UPDATE users u
                SET karma = (
                    SELECT COALESCE(SUM(lt.carma), 0)
                    FROM posts p
                    JOIN likes l ON p.id = l.post_id
                    JOIN like_types lt ON l.like_type_id = lt.id
                    WHERE p.user_id = :user_id
                )
                WHERE u.id = :user_id
            ");
            
            $stmt->execute(['user_id' => $userId]);
            return true;
        } catch (PDOException $e) {
            error_log('Error updating karma: ' . $e->getMessage());
            return false;
        }
    }

    public static function getPopularAuthors($pdo, $limit = 5) {
        try {
            // Оновлюємо карму для всіх користувачів перед вибіркою
            $pdo->exec("
                UPDATE users u
                SET karma = (
                    SELECT COALESCE(SUM(lt.carma), 0)
                    FROM posts p
                    JOIN likes l ON p.id = l.post_id
                    JOIN like_types lt ON l.like_type_id = lt.id
                    WHERE p.user_id = u.id
                )
            ");
            
            $stmt = $pdo->prepare("
                SELECT 
                    u.id, 
                    u.username, 
                    u.avatar_url, 
                    u.karma,
                    COUNT(p.id) as post_count
                FROM users u
                LEFT JOIN posts p ON u.id = p.user_id 
                GROUP BY u.id, u.username, u.avatar_url, u.karma
                ORDER BY u.karma DESC, post_count DESC
                LIMIT :limit
            ");
            
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $authors;
        } catch (PDOException $e) {
            error_log('Error getting popular authors: ' . $e->getMessage());
            return [];
        }
    }
}
?>
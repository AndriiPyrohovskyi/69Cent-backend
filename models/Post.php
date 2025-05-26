<?php
class Post {
    public static function getAll($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    p.id, p.title as post_title, p.description as post_text, 
                    p.image as post_image, p.created_at as post_created_at,
                    c.name as post_category, u.id as author_id, 
                    u.username as author_name, u.avatar_url as author_avatar
                FROM posts p
                JOIN categories c ON p.category_id = c.id
                JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC
            ");
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($posts as &$post) {
                $post['post_likes'] = self::getPostLikes($pdo, $post['id']);
            }
            
            return $posts;
        } catch (PDOException $e) {
            error_log('Error getting all posts: ' . $e->getMessage());
            return [];
        }
    }

    public static function getById($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT 
                posts.id AS post_id,
                posts.title AS post_title,
                posts.description AS post_text,
                posts.image AS post_image,
                posts.created_at AS post_created_at,
                posts.modified_at AS post_modified_at,
                posts.modified_at IS NOT NULL AS is_modified,
                categories.name AS post_category,
                users.id AS author_id,
                users.username AS author_name,
                users.avatar_url AS author_avatar,
                users.role AS author_role,
                users.created_at AS author_date,
                SUM(like_types.carma) AS author_сarma
            FROM posts
            JOIN categories ON posts.category_id = categories.id
            JOIN users ON posts.id IN (SELECT post_id FROM likes WHERE user_id = users.id)
            LEFT JOIN likes ON posts.id = likes.post_id
            LEFT JOIN like_types ON likes.like_type_id = like_types.id
            WHERE posts.id = ?
            GROUP BY posts.id, users.id
        ");
        $stmt->execute([$id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post) {
            $post['post_likes'] = self::getLikes($pdo, $post['post_id']);
        }

        return $post;
    }

    public static function getByUser($pdo, $userId, $currentUserId = null) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    p.id, p.title as post_title, p.description as post_text, 
                    p.image as post_image, p.created_at as post_created_at,
                    c.name as post_category, u.id as author_id, 
                    u.username as author_name, u.avatar_url as author_avatar
                FROM posts p
                JOIN categories c ON p.category_id = c.id
                JOIN users u ON p.user_id = u.id
                WHERE p.user_id = :user_id
                ORDER BY p.created_at DESC
            ");
            $stmt->execute(['user_id' => $userId]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Додаємо інформацію про лайки для кожного поста
            foreach ($posts as &$post) {
                $post['post_likes'] = self::getPostLikes($pdo, $post['id']);
                $post['user_likes'] = self::getUserLikes($pdo, $post['id'], $currentUserId);
            }
            
            return $posts;
        } catch (PDOException $e) {
            error_log('Error getting posts for user: ' . $e->getMessage());
            return [];
        }
    }

    private static function getLikes($pdo, $postId) {
        $stmt = $pdo->prepare("
            SELECT 
                like_types.name AS type,
                COUNT(likes.id) AS count,
                like_types.icon_url AS icon
            FROM likes
            JOIN like_types ON likes.like_type_id = like_types.id
            WHERE likes.post_id = ?
            GROUP BY like_types.id
        ");
        $stmt->execute([$postId]);
        $likes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($likes as $like) {
            $result[$like['type']] = [$like['count'], $like['icon']];
        }

        return $result;
    }

    public static function getPostLikes($pdo, $postId) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    lt.id, lt.name, COUNT(l.id) as count, lt.icon_url
                FROM like_types lt
                LEFT JOIN likes l ON l.like_type_id = lt.id AND l.post_id = :post_id
                GROUP BY lt.id, lt.name, lt.icon_url
            ");
            $stmt->execute(['post_id' => $postId]);
            $likes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($likes as $like) {
                $result[$like['name']] = [
                    intval($like['count']), 
                    $like['icon_url']
                ];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log('Error getting likes for post: ' . $e->getMessage());
            return [];
        }
    }

    // Додайте новий метод для отримання лайків користувача
    public static function getUserLikes($pdo, $postId, $userId) {
        if (!$userId) return [];
        
        try {
            $stmt = $pdo->prepare("
                SELECT like_type_id 
                FROM likes 
                WHERE post_id = :post_id AND user_id = :user_id
            ");
            $stmt->execute([
                'post_id' => $postId,
                'user_id' => $userId
            ]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log('Error getting user likes: ' . $e->getMessage());
            return [];
        }
    }

    public static function create($pdo, $data) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO posts (user_id, category_id, status_id, title, description, image) 
                VALUES (:user_id, :category_id, :status_id, :title, :description, :image)
            ");
            
            return $stmt->execute([
                'user_id' => $data['user_id'],
                'category_id' => $data['category_id'],
                'status_id' => $data['status_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'image' => $data['image']
            ]);
        } catch (PDOException $e) {
            error_log('Error creating post: ' . $e->getMessage());
            return false;
        }
    }

    public static function update($pdo, $id, $data) {
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;

        $sql = "UPDATE posts SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    }

    public static function delete($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function like($pdo, $postId, $userId, $likeTypeId) {
        try {
            // Логування для діагностики
            error_log("Starting like operation: postId=$postId, userId=$userId, likeTypeId=$likeTypeId");
            
            // Перевіряємо, чи існує пост
            $checkPostStmt = $pdo->prepare("SELECT id FROM posts WHERE id = :post_id");
            $checkPostStmt->execute(['post_id' => $postId]);
            if (!$checkPostStmt->fetch()) {
                error_log("Post $postId not found");
                return ['success' => false, 'error' => 'Post not found'];
            }
            
            // Перевіряємо, чи існує такий тип лайку
            $checkLikeTypeStmt = $pdo->prepare("SELECT id FROM like_types WHERE id = :like_type_id");
            $checkLikeTypeStmt->execute(['like_type_id' => $likeTypeId]);
            if (!$checkLikeTypeStmt->fetch()) {
                error_log("Like type $likeTypeId not found");
                return ['success' => false, 'error' => 'Like type not found'];
            }
            
            // Перевіряємо, чи вже є такий лайк
            $checkLikeStmt = $pdo->prepare("
                SELECT id FROM likes 
                WHERE post_id = :post_id AND user_id = :user_id AND like_type_id = :like_type_id
            ");
            $checkLikeStmt->execute([
                'post_id' => $postId,
                'user_id' => $userId,
                'like_type_id' => $likeTypeId
            ]);
            
            $existingLike = $checkLikeStmt->fetch(PDO::FETCH_ASSOC);
            error_log("Existing like check: " . ($existingLike ? "Found like ID " . $existingLike['id'] : "No like found"));
            
            if ($existingLike) {
                // Якщо лайк вже існує - видаляємо його
                $deleteStmt = $pdo->prepare("DELETE FROM likes WHERE id = :id");
                $success = $deleteStmt->execute(['id' => $existingLike['id']]);
                
                error_log("Delete result: " . ($success ? "success" : "failed"));
                
                return [
                    'success' => $success,
                    'action' => 'unliked'
                ];
            } else {
                // Якщо лайка ще немає - додаємо новий
                $insertStmt = $pdo->prepare("
                    INSERT INTO likes (post_id, user_id, like_type_id)
                    VALUES (:post_id, :user_id, :like_type_id)
                ");
                $success = $insertStmt->execute([
                    'post_id' => $postId,
                    'user_id' => $userId,
                    'like_type_id' => $likeTypeId
                ]);
                
                error_log("Insert result: " . ($success ? "success, new ID: " . $pdo->lastInsertId() : "failed"));
                
                return [
                    'success' => $success,
                    'action' => 'liked'
                ];
            }
        } catch (PDOException $e) {
            error_log('Error in Post::like: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
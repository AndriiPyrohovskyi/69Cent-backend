<?php
require_once 'models/Post.php';

class PostController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $posts = Post::getAll($this->pdo);
        echo json_encode($posts);
    }

    public function getById($id) {
        $post = Post::getById($this->pdo, $id);
        if ($post) {
            echo json_encode($post);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Post not found']);
        }
    }

    public function getByUser($userId) {
        $currentUserId = null;
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
                $user_data = verifyJWT($token);
                if ($user_data) {
                    $currentUserId = $user_data->id;
                }
            }
        }
        
        $posts = Post::getByUser($this->pdo, $userId, $currentUserId);
        echo json_encode($posts);
    }

    public function create() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Валідація даних
            if (!isset($data['user_id']) || !isset($data['category_id']) || 
                !isset($data['status_id']) || !isset($data['title']) || 
                !isset($data['description'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }
            
            $result = Post::create($this->pdo, $data);
            
            if ($result) {
                http_response_code(201);
                echo json_encode(['message' => 'Post created successfully']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Failed to create post']);
            }
        } catch (Exception $e) {
            error_log('Error in PostController::create: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }

    public function update($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        $result = Post::update($this->pdo, $id, $data);
        if ($result) {
            echo json_encode(['message' => 'Post updated successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to update post']);
        }
    }

    public function delete($id) {
        $result = Post::delete($this->pdo, $id);
        if ($result) {
            echo json_encode(['message' => 'Post deleted successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to delete post']);
        }
    }

    public function like($postId, $userId, $likeTypeId) {
        try {
            error_log("PostController::like called with postId=$postId, userId=$userId, likeTypeId=$likeTypeId");
            
            $result = Post::like($this->pdo, $postId, $userId, $likeTypeId);
            error_log("Post::like result: " . print_r($result, true));
            
            // Встановлюємо заголовки відповіді
            header('Content-Type: application/json');
            
            // Перевірка на успішність операції
            if ($result['success']) {
                // Оновлюємо карму автора поста
                require_once 'models/User.php';
                require_once 'models/Post.php';
                
                // Отримуємо інформацію про пост, щоб дізнатися автора
                $post = Post::getById($this->pdo, $postId);
                if ($post && isset($post['author_id'])) {
                    User::updateKarma($this->pdo, $post['author_id']);
                }
                
                $jsonResponse = json_encode([
                    'message' => 'Post ' . $result['action'] . ' successfully',
                    'action' => $result['action']
                ]);
                error_log("Sending JSON response: " . $jsonResponse);
                echo $jsonResponse;
            } else {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Failed to toggle like',
                    'details' => $result['error'] ?? null
                ]);
            }
        } catch (Exception $e) {
            error_log("Exception in like method: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }
}
?>
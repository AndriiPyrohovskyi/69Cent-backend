<?php
class PostController {
    public function getAll() {
        require_once 'models/Post.php';
        $posts = Post::getAll();
        echo json_encode($posts);
    }
}
?>
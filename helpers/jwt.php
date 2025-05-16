<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require_once __DIR__ . '/../vendor/autoload.php';

function generateJWT($userId, $username, $role) {
    $payload = [
        'iss' => 'http://69cent.local',
        'aud' => 'http://69cent.local',
        'iat' => time(),
        'exp' => time() + 3600,
        'data' => [
            'id' => $userId,
            'username' => $username,
            'role' => $role
        ]
    ];

    $secretKey = $_ENV['JWT_SECRET']; 
    return JWT::encode($payload, $secretKey, 'HS256');
}

function verifyJWT($token) {
    try {
        $decoded = JWT::decode($token, new Key('your_super_secret_key', 'HS256'));
        return $decoded->data;
    } catch (Exception $e) {
        return null;
    }
}

?>
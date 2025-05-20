<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require_once __DIR__ . '/../vendor/autoload.php';

function generateJWT($userId, $username, $role) {
    $payload = [
        'iss' => 'http://69centapi.local',
        'aud' => 'http://69centapi.local',
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
        $secretKey = $_ENV['JWT_SECRET']; 
        $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
        return $decoded->data;
    } catch (Exception $e) {
        return null;
    }
}

?>
<?php

namespace Middleware;

use Config\JwtHandler;

function authenticateRequest() {
    $headers = getallheaders();
    
    // Handle case where getallheaders() might not work
    if (!$headers) {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
    }

    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode([
            "status" => "error", 
            "message" => "Authorization header missing",
            "debug" => "Include header: Authorization: Bearer YOUR_JWT_TOKEN"
        ]);
        exit;
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $jwt = new JwtHandler();
    $decoded = $jwt->decode($token);

    if (!$decoded) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid or expired token"]);
        exit;
    }

    // Return decoded payload (e.g., ['user_id' => ..., 'role' => ...])
    return $decoded;
}

function authorizeRoles(array $allowedRoles, $userRole) {
    if (!in_array($userRole, $allowedRoles)) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Access denied for your role"]);
        exit;
    }
}

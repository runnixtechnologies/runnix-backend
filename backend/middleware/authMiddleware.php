<?php


use config\JwtHandler;
function authenticateRequest() {
    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Authorization header missing"]);
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

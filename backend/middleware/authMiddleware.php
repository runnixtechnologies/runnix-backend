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

	$authHeader = $headers['Authorization'] ?? null;
	$token = null;

	// Primary: Authorization header (Bearer ...)
	if ($authHeader) {
		$token = str_replace('Bearer ', '', $authHeader);
	} else {
		// Fallback for some hosts that strip Authorization on GET: allow token via query string
		$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		if (strtoupper($requestMethod) === 'GET' && isset($_GET['token']) && is_string($_GET['token']) && $_GET['token'] !== '') {
			$token = $_GET['token'];
		}
	}

	if (!$token) {
		http_response_code(401);
		echo json_encode([
			"status" => "error",
			"message" => "Authorization token missing",
			"debug" => "Provide header Authorization: Bearer <token> or ?token=<token> for GET"
		]);
		exit;
	}

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

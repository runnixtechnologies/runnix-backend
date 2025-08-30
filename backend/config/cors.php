<?php
// CORS configuration for API endpoints

// Allow requests from any origin (change "*" to specific domain if needed for production)
header("Access-Control-Allow-Origin: *");
//for production 
//header("Access-Control-Allow-Origin: https://runnix.africa");


// Allowed request methods
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Allowed request headers
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

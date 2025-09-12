<?php
// Basic test to isolate the 500 error
header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'Basic PHP test working',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion()
]);

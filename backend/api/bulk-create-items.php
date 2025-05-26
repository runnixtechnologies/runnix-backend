<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\ItemController;

header('Content-Type: application/json');

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $rawData = file_get_contents("php://input");
    $data = json_decode($rawData, true) ?? [];
} else {
    $data = $_POST;
}

$controller = new ItemController();
$response = $controller->addItemsBulk($data);
echo json_encode($response);

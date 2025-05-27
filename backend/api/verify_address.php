<?php
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\StoreController;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$controller = new StoreController();
$response = $controller->verifyStoreAddress($data);

echo json_encode($response);

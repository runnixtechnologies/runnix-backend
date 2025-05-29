<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\ItemController;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$controller = new ItemController();
$response = $controller->deactivateItem($data);
echo json_encode($response);

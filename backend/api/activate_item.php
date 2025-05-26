<?php
// File: public/api/items/activate_item.php

require_once '../../../vendor/autoload.php';
require_once '../../config/cors.php';

use Controller\ItemController;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$controller = new ItemController();
$response = $controller->activateItem($data);
echo json_encode($response);

<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\StoreController;

header('Content-Type: application/json');

$storeTypeId = $_GET['store_type_id'] ?? null;

$storeController = new StoreController();
$response = $storeController->getActiveCategoriesByStoreType($storeTypeId);
echo json_encode($response);

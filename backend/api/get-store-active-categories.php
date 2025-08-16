<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\StoreController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

$user = authenticateRequest();
$storeController = new StoreController();
$response = $storeController->getActiveCategoriesByStoreType($user);
echo json_encode($response);

<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\StoreController;

header('Content-Type: application/json');

$store= new StoreController();
$response = $store->getActiveCategories();
echo json_encode($response);

<?php

require_once "../includes/rider-setup.php";

use App\Facades\FileProcessor;
use App\Requests\HttpRequestHandler;
use App\Requests\RegisterVehicleRequest;
use Controller\OrderController;
use Controller\Riders\RiderVehicleController;

use function Middleware\authenticateRequest;
// Check authentication
$user = authenticateRequest();

$httpHandler = new HttpRequestHandler();
$httpHandler->allowPostMethod();


$riderVehicleController = new RiderVehicleController();
$riderVehicleController->authUser = $user;
$riderVehicleController->uploadAttachements();

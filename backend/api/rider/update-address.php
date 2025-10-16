<?php 

include_once "includes/rider-setup.php";

use function Middleware\authenticateRequest;
use App\Requests\HttpRequestHandler;
// Check authentication
$user = authenticateRequest();

$httpHandler = new HttpRequestHandler();
$httpHandler->allowPostMethod();

$riderController = new \Controller\Riders\RiderController();
$riderController->authUser = $user;
$riderController->updateAddress();
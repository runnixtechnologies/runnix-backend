<?php 

include_once "includes/rider-setup.php";

use function Middleware\authenticateRequest;
use App\Requests\HttpRequestHandler;
// Check authentication
$user = authenticateRequest();

$httpHandler = new HttpRequestHandler();
$httpHandler->allowGetMethod();

$relation = $_GET["relation"] ?? abort(400, "Relation param is required");
$relationId = $_GET["relation-id"] ?? null;
switch($relation){
    case "vehicle":
        $riderVehicleController = new \Controller\Riders\RiderVehicleController();
        $riderVehicleController->authUser = $user;
        $riderVehicleController->getVehicles($relationId);
        break;
    case "document":
        $riderController = new \Controller\Riders\RiderController();
        $riderController->authUser = $user;
        $riderController->getDocuments($relationId);
        break;
    default:
        return abort(400, "Invalid relation");
}
$riderController = new \Controller\Riders\RiderController();
$riderController->authUser = $user;
$riderController->uploadDocuments();
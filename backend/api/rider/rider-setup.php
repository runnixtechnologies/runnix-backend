<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__."/../../../vendor/autoload.php";
require_once __DIR__."/../../config/cors.php";
require_once __DIR__."/../../middleware/authMiddleware.php";

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . "/../../");

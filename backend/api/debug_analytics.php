<?php
// Debug analytics system to identify 500 errors
// This is for development/testing purposes only

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once '../config/Database.php';
    require_once '../model/Analytics.php';
    require_once '../model/Store.php';
    require_once '../controller/AnalyticsController.php';
    
    use Config\Database;
    use Model\Analytics;
    use Model\Store;
    use Controller\AnalyticsController;
    
    $debug = [
        'status' => 'debug',
        'message' => 'Analytics system debug information',
        'checks' => []
    ];
    
    // Check database connection
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $debug['checks']['database_connection'] = 'SUCCESS';
    } catch (Exception $e) {
        $debug['checks']['database_connection'] = 'FAILED: ' . $e->getMessage();
    }
    
    // Check required tables exist
    $requiredTables = ['stores', 'orders', 'order_items', 'food_items', 'items', 'store_types'];
    $tableChecks = [];
    
    if (isset($conn)) {
        foreach ($requiredTables as $table) {
            try {
                $stmt = $conn->prepare("SHOW TABLES LIKE :table");
                $stmt->execute(['table' => $table]);
                $exists = $stmt->fetch();
                $tableChecks[$table] = $exists ? 'EXISTS' : 'MISSING';
            } catch (Exception $e) {
                $tableChecks[$table] = 'ERROR: ' . $e->getMessage();
            }
        }
    }
    $debug['checks']['required_tables'] = $tableChecks;
    
    // Check if we can instantiate models
    try {
        $analyticsModel = new Analytics();
        $debug['checks']['analytics_model'] = 'SUCCESS';
    } catch (Exception $e) {
        $debug['checks']['analytics_model'] = 'FAILED: ' . $e->getMessage();
    }
    
    try {
        $storeModel = new Store();
        $debug['checks']['store_model'] = 'SUCCESS';
    } catch (Exception $e) {
        $debug['checks']['store_model'] = 'FAILED: ' . $e->getMessage();
    }
    
    try {
        $analyticsController = new AnalyticsController();
        $debug['checks']['analytics_controller'] = 'SUCCESS';
    } catch (Exception $e) {
        $debug['checks']['analytics_controller'] = 'FAILED: ' . $e->getMessage();
    }
    
    // Check if we can get stores
    if (isset($storeModel)) {
        try {
            $stores = $storeModel->getAllStores();
            $debug['checks']['get_stores'] = 'SUCCESS - Found ' . count($stores) . ' stores';
            if (!empty($stores)) {
                $debug['sample_store'] = $stores[0];
            }
        } catch (Exception $e) {
            $debug['checks']['get_stores'] = 'FAILED: ' . $e->getMessage();
        }
    }
    
    // Check sample analytics query
    if (isset($analyticsModel) && isset($debug['sample_store'])) {
        try {
            $storeId = $debug['sample_store']['id'];
            $dateRange = [
                'start_date' => date('Y-m-d H:i:s', strtotime('-7 days')),
                'end_date' => date('Y-m-d H:i:s')
            ];
            
            $revenue = $analyticsModel->getTotalRevenue($storeId, $dateRange);
            $debug['checks']['sample_analytics_query'] = 'SUCCESS - Revenue: ' . $revenue;
        } catch (Exception $e) {
            $debug['checks']['sample_analytics_query'] = 'FAILED: ' . $e->getMessage();
        }
    }
    
    echo json_encode($debug, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Debug failed: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}

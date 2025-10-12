<?php
error_log("SIMPLE TEST - Script executed at: " . date('Y-m-d H:i:s'));
echo json_encode(['status' => 'success', 'message' => 'Simple test works', 'timestamp' => date('Y-m-d H:i:s')]);
?>

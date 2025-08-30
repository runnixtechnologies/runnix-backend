<?php
/**
 * Cleanup Script: Remove Expired Sessions
 * This script should be run periodically (e.g., every hour) to clean up expired sessions
 * Can be run manually or via cron job
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/UserActivity.php';

use Model\UserActivity;

try {
    echo "Starting expired sessions cleanup...\n";
    
    $userActivity = new UserActivity();
    
    // Clean up expired sessions
    $cleanedSessions = $userActivity->cleanupExpiredSessions();
    
    if ($cleanedSessions) {
        echo "✅ Successfully cleaned up expired sessions\n";
    } else {
        echo "⚠️  No expired sessions found or cleanup failed\n";
    }
    
    // Get cleanup statistics
    $db = new \Config\Database();
    $conn = $db->getConnection();
    
    // Count active sessions by role
    $sql = "SELECT user_role, COUNT(*) as active_sessions 
            FROM user_activity 
            WHERE is_active = TRUE 
            GROUP BY user_role";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $activeSessions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    echo "\n📊 Current Active Sessions:\n";
    foreach ($activeSessions as $session) {
        echo "- {$session['user_role']}: {$session['active_sessions']} sessions\n";
    }
    
    // Count total inactive sessions
    $sql = "SELECT COUNT(*) as inactive_sessions FROM user_activity WHERE is_active = FALSE";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $inactiveCount = $stmt->fetch(\PDO::FETCH_ASSOC)['inactive_sessions'];
    
    echo "- Inactive sessions: {$inactiveCount}\n";
    
    echo "\n🎉 Cleanup completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Cleanup failed: " . $e->getMessage() . "\n";
    exit(1);
}

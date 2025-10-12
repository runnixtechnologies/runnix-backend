<?php
/**
 * Migration Script: Create Blacklisted Tokens Table
 * This table stores invalidated JWT tokens for immediate logout functionality
 */

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration: Creating blacklisted_tokens table...\n";
    
    // Create blacklisted_tokens table
    $createBlacklistedTokensTable = "
    CREATE TABLE IF NOT EXISTS blacklisted_tokens (
        id INT PRIMARY KEY AUTO_INCREMENT,
        token TEXT NOT NULL,
        blacklisted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        INDEX idx_token_hash (token(255)),
        INDEX idx_blacklisted_at (blacklisted_at),
        INDEX idx_expires_at (expires_at)
    )";
    
    $conn->exec($createBlacklistedTokensTable);
    echo "✅ Created blacklisted_tokens table\n";
    
    // Add cleanup procedure for expired tokens (optional)
    $createCleanupProcedure = "
    CREATE EVENT IF NOT EXISTS cleanup_expired_tokens
    ON SCHEDULE EVERY 1 HOUR
    DO
        DELETE FROM blacklisted_tokens 
        WHERE expires_at IS NOT NULL 
        AND expires_at < NOW()";
    
    try {
        $conn->exec($createCleanupProcedure);
        echo "✅ Created cleanup event for expired tokens\n";
    } catch (Exception $e) {
        echo "⚠️  Cleanup event creation failed (may need EVENT privileges): " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Blacklisted tokens table migration completed successfully!\n";
    echo "This enables immediate logout functionality by invalidating JWT tokens.\n";
    echo "Expired tokens will be automatically cleaned up every hour.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}

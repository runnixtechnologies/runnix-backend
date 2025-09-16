<?php
/**
 * Test path resolution for upload directory
 */

echo "=== PATH RESOLUTION TEST ===\n\n";

echo "Current working directory: " . getcwd() . "\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "Script file: " . __FILE__ . "\n\n";

$uploadDir = __DIR__ . '/../uploads/food-items/';
echo "Upload directory path: " . $uploadDir . "\n";
echo "Real path: " . realpath($uploadDir) . "\n";
echo "Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";

// Try alternative path
$altUploadDir = __DIR__ . '/../../uploads/food-items/';
echo "\nAlternative upload directory path: " . $altUploadDir . "\n";
echo "Alternative real path: " . realpath($altUploadDir) . "\n";
echo "Alternative directory exists: " . (is_dir($altUploadDir) ? 'YES' : 'NO') . "\n";

// Check what's in the uploads directory
$uploadsDir = __DIR__ . '/../uploads/';
echo "\nUploads directory: " . $uploadsDir . "\n";
echo "Uploads directory exists: " . (is_dir($uploadsDir) ? 'YES' : 'NO') . "\n";

if (is_dir($uploadsDir)) {
    echo "Contents of uploads directory:\n";
    $contents = scandir($uploadsDir);
    foreach ($contents as $item) {
        if ($item != '.' && $item != '..') {
            echo "- " . $item . "\n";
        }
    }
}

echo "\n=== PATH TEST COMPLETE ===\n";
?>

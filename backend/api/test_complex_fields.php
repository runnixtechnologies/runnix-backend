<?php
/**
 * Test endpoint for complex fields update
 */

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

error_log("=== COMPLEX FIELDS TEST STARTED ===");

try {
    // Test data parsing
    $testSides = '{"required": true,"max_quantity": 2,"items": [11]}';
    $testPacks = '{"required": false,"max_quantity": 1,"items": [13,14]}';
    $testSections = '[{"section_id": 12, "required": true,"max_quantity": 1,"item_ids": [82, 83]}]';
    
    echo "=== COMPLEX FIELDS PARSING TEST ===\n\n";
    
    echo "Original sides JSON: $testSides\n";
    $parsedSides = json_decode($testSides, true);
    echo "Parsed sides: " . json_encode($parsedSides, JSON_PRETTY_PRINT) . "\n";
    echo "Is array: " . (is_array($parsedSides) ? 'YES' : 'NO') . "\n\n";
    
    echo "Original packs JSON: $testPacks\n";
    $parsedPacks = json_decode($testPacks, true);
    echo "Parsed packs: " . json_encode($parsedPacks, JSON_PRETTY_PRINT) . "\n";
    echo "Is array: " . (is_array($parsedPacks) ? 'YES' : 'NO') . "\n\n";
    
    echo "Original sections JSON: $testSections\n";
    $parsedSections = json_decode($testSections, true);
    echo "Parsed sections: " . json_encode($parsedSections, JSON_PRETTY_PRINT) . "\n";
    echo "Is array: " . (is_array($parsedSections) ? 'YES' : 'NO') . "\n\n";
    
    // Test validation logic
    echo "=== VALIDATION LOGIC TEST ===\n\n";
    
    // Test sides validation
    if (isset($parsedSides['required']) || isset($parsedSides['max_quantity']) || isset($parsedSides['items'])) {
        echo "Sides: Structured format detected\n";
        echo "  - Required: " . ($parsedSides['required'] ? 'true' : 'false') . "\n";
        echo "  - Max quantity: " . $parsedSides['max_quantity'] . "\n";
        echo "  - Items: " . json_encode($parsedSides['items']) . "\n";
    } else {
        echo "Sides: Simple format detected\n";
    }
    
    // Test packs validation
    if (isset($parsedPacks['required']) || isset($parsedPacks['max_quantity']) || isset($parsedPacks['items'])) {
        echo "Packs: Structured format detected\n";
        echo "  - Required: " . ($parsedPacks['required'] ? 'true' : 'false') . "\n";
        echo "  - Max quantity: " . $parsedPacks['max_quantity'] . "\n";
        echo "  - Items: " . json_encode($parsedPacks['items']) . "\n";
    } else {
        echo "Packs: Simple format detected\n";
    }
    
    // Test sections validation
    if (isset($parsedSections[0]) && is_array($parsedSections[0]) && isset($parsedSections[0]['section_id'])) {
        echo "Sections: New preferred format detected\n";
        foreach ($parsedSections as $index => $section) {
            echo "  Section $index:\n";
            echo "    - Section ID: " . $section['section_id'] . "\n";
            echo "    - Required: " . ($section['required'] ? 'true' : 'false') . "\n";
            echo "    - Max quantity: " . $section['max_quantity'] . "\n";
            echo "    - Item IDs: " . json_encode($section['item_ids']) . "\n";
        }
    } elseif (isset($parsedSections['required']) || isset($parsedSections['max_quantity']) || isset($parsedSections['items'])) {
        echo "Sections: Legacy format detected\n";
    } else {
        echo "Sections: Simple format detected\n";
    }
    
    echo "\n=== TEST COMPLETE ===\n";
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Complex fields parsing test completed',
        'results' => [
            'sides' => $parsedSides,
            'packs' => $parsedPacks,
            'sections' => $parsedSections
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Exception in complex fields test: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception occurred during test',
        'error' => $e->getMessage()
    ]);
}

error_log("=== COMPLEX FIELDS TEST COMPLETED ===");
?>

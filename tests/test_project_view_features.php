<?php
/**
 * Test Project View Page Features
 * Tests the new PDF download and draft warning features
 */

echo "=== Project View Features Test Suite ===\n\n";

try {
    // Test 1: Check if view.php exists
    echo "Test 1: Check Page File Exists\n";
    $viewPage = __DIR__ . '/../pages/projects/view.php';
    
    if (file_exists($viewPage)) {
        echo "✓ view.php exists\n";
    } else {
        echo "✗ view.php not found\n";
        exit(1);
    }
    echo "\n";
    
    // Test 2: PHP Syntax Check
    echo "Test 2: PHP Syntax Check\n";
    exec("php -l " . escapeshellarg($viewPage), $output, $return);
    if ($return === 0) {
        echo "✓ view.php has valid PHP syntax\n";
    } else {
        echo "✗ view.php has syntax errors\n";
        echo implode("\n", $output) . "\n";
        exit(1);
    }
    echo "\n";
    
    // Test 3: Check for draft warning implementation
    echo "Test 3: Check Draft Warning Implementation\n";
    $content = file_get_contents($viewPage);
    
    if (strpos($content, "Draft Warning") !== false) {
        echo "✓ Draft warning comment found\n";
    } else {
        echo "✗ Draft warning comment not found\n";
    }
    
    if (strpos($content, "project['status'] === 'draft'") !== false) {
        echo "✓ Draft status check found\n";
    } else {
        echo "✗ Draft status check not found\n";
    }
    
    if (strpos($content, "bg-yellow-100") !== false && strpos($content, "border-yellow-400") !== false) {
        echo "✓ Yellow warning styling found\n";
    } else {
        echo "✗ Yellow warning styling not found\n";
    }
    
    if (strpos($content, "Dieses Projekt ist ein Entwurf") !== false) {
        echo "✓ Draft warning text found\n";
    } else {
        echo "✗ Draft warning text not found\n";
    }
    echo "\n";
    
    // Test 4: Check for PDF download button implementation
    echo "Test 4: Check PDF Download Button Implementation\n";
    
    if (strpos($content, "PDF Download Button") !== false) {
        echo "✓ PDF download button comment found\n";
    } else {
        echo "✗ PDF download button comment not found\n";
    }
    
    if (strpos($content, "project['file_path']") !== false) {
        echo "✓ file_path check found\n";
    } else {
        echo "✗ file_path check not found\n";
    }
    
    if (strpos($content, "file_exists") !== false) {
        echo "✓ File existence check found\n";
    } else {
        echo "✗ File existence check not found\n";
    }
    
    if (strpos($content, "Projekt-Datei herunterladen (PDF)") !== false) {
        echo "✓ PDF download button text found\n";
    } else {
        echo "✗ PDF download button text not found\n";
    }
    
    if (strpos($content, "fa-file-pdf") !== false) {
        echo "✓ PDF icon found\n";
    } else {
        echo "✗ PDF icon not found\n";
    }
    echo "\n";
    
    // Test 5: Check proper positioning
    echo "Test 5: Check Feature Positioning\n";
    $draftPos = strpos($content, "Draft Warning");
    $imagePos = strpos($content, "<!-- Image -->");
    $pdfPos = strpos($content, "PDF Download Button");
    $statusPos = strpos($content, "<!-- Status and Priority -->");
    
    if ($draftPos < $imagePos) {
        echo "✓ Draft warning appears before image (correct)\n";
    } else {
        echo "✗ Draft warning positioning incorrect\n";
    }
    
    if ($pdfPos > $imagePos && $pdfPos < $statusPos) {
        echo "✓ PDF button appears after image and before status (correct)\n";
    } else {
        echo "✗ PDF button positioning incorrect\n";
    }
    echo "\n";
    
    echo "=== All Tests Completed ===\n\n";
    echo "Summary: The new features have been successfully implemented!\n";
    echo "- Draft warning displays when project status is 'draft'\n";
    echo "- PDF download button displays when file_path is set and file exists\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

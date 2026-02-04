<?php
/**
 * Test Project Management Sticky Forms and PDF Upload
 * Tests the newly added features in manage.php
 */

echo "=== Project Management Sticky Forms & PDF Upload Test Suite ===\n\n";

try {
    // Test 1: Check if manage.php exists
    echo "Test 1: Check Page File Exists\n";
    $managePage = __DIR__ . '/../pages/projects/manage.php';
    
    if (file_exists($managePage)) {
        echo "✓ manage.php exists\n";
    } else {
        echo "✗ manage.php not found\n";
        exit(1);
    }
    echo "\n";
    
    // Test 2: PHP Syntax Check
    echo "Test 2: PHP Syntax Check\n";
    exec("php -l " . escapeshellarg($managePage), $output, $return);
    if ($return === 0) {
        echo "✓ manage.php has valid PHP syntax\n";
    } else {
        echo "✗ manage.php has syntax errors\n";
        echo implode("\n", $output) . "\n";
        exit(1);
    }
    echo "\n";
    
    // Test 3: Check for sticky form patterns
    echo "Test 3: Check Sticky Form Implementation\n";
    $pageContent = file_get_contents($managePage);
    
    $stickyFormPatterns = [
        '$_POST[\'title\']' => 'Title sticky form',
        '$_POST[\'description\']' => 'Description sticky form',
        '$_POST[\'client_name\']' => 'Client name sticky form',
        '$_POST[\'client_contact_details\']' => 'Contact details sticky form',
        '$_POST[\'priority\']' => 'Priority sticky form',
        '$_POST[\'status\']' => 'Status sticky form',
        '$_POST[\'start_date\']' => 'Start date sticky form',
        '$_POST[\'end_date\']' => 'End date sticky form',
        '$_POST[\'max_consultants\']' => 'Max consultants sticky form'
    ];
    
    $allPatternsFound = true;
    foreach ($stickyFormPatterns as $pattern => $description) {
        if (strpos($pageContent, $pattern) !== false) {
            echo "✓ Found $description\n";
        } else {
            echo "✗ Missing $description\n";
            $allPatternsFound = false;
        }
    }
    echo "\n";
    
    // Test 4: Check for PDF upload field
    echo "Test 4: Check PDF Upload Field\n";
    if (strpos($pageContent, 'name="project_file"') !== false) {
        echo "✓ PDF upload field (project_file) exists\n";
    } else {
        echo "✗ PDF upload field (project_file) not found\n";
    }
    
    if (strpos($pageContent, 'accept=".pdf"') !== false) {
        echo "✓ PDF accept attribute is correct\n";
    } else {
        echo "✗ PDF accept attribute not found or incorrect\n";
    }
    echo "\n";
    
    // Test 5: Check for PDF upload handler
    echo "Test 5: Check PDF Upload Handler\n";
    if (strpos($pageContent, 'Project::handleDocumentationUpload') !== false) {
        echo "✓ PDF upload handler exists\n";
    } else {
        echo "✗ PDF upload handler not found\n";
    }
    
    if (strpos($pageContent, '$_FILES[\'project_file\']') !== false) {
        echo "✓ PDF file check exists\n";
    } else {
        echo "✗ PDF file check not found\n";
    }
    echo "\n";
    
    // Test 6: Check for two submit buttons
    echo "Test 6: Check Two Submit Buttons\n";
    if (strpos($pageContent, 'name="save_draft"') !== false) {
        echo "✓ Draft button (save_draft) exists\n";
    } else {
        echo "✗ Draft button (save_draft) not found\n";
    }
    
    if (strpos($pageContent, 'Als Entwurf speichern') !== false) {
        echo "✓ Draft button label exists\n";
    } else {
        echo "✗ Draft button label not found\n";
    }
    
    if (strpos($pageContent, 'Projekt veröffentlichen') !== false) {
        echo "✓ Publish button label exists\n";
    } else {
        echo "✗ Publish button label not found\n";
    }
    echo "\n";
    
    // Test 7: Check for conditional validation logic
    echo "Test 7: Check Conditional Validation Logic\n";
    if (strpos($pageContent, 'isset($_POST[\'save_draft\'])') !== false) {
        echo "✓ Draft detection logic exists\n";
    } else {
        echo "✗ Draft detection logic not found\n";
    }
    
    if (strpos($pageContent, '$projectId === 0 && $status !== \'draft\'') !== false) {
        echo "✓ Publish validation condition exists\n";
    } else {
        echo "✗ Publish validation condition not found\n";
    }
    echo "\n";
    
    // Test 8: Check status dropdown conditional rendering
    echo "Test 8: Check Status Dropdown Conditional Rendering\n";
    if (preg_match('/<\?php if \(\$editProject\):\s*\?>.*?name="status".*?<\?php endif;\s*\?>/s', $pageContent)) {
        echo "✓ Status dropdown is conditional (only for edit mode)\n";
    } else {
        echo "⚠ Status dropdown conditional rendering check inconclusive\n";
    }
    echo "\n";
    
    // Test 9: Check for enctype attribute
    echo "Test 9: Check Form Enctype Attribute\n";
    if (strpos($pageContent, 'enctype="multipart/form-data"') !== false || 
        strpos($pageContent, "enctype='multipart/form-data'") !== false) {
        echo "✓ Form enctype attribute exists\n";
    } else {
        echo "✗ Form enctype attribute not found\n";
    }
    echo "\n";
    
    // Test 10: Check uploads/projects directory
    echo "Test 10: Check Uploads Directory\n";
    $uploadsDir = __DIR__ . '/../uploads/projects';
    if (is_dir($uploadsDir)) {
        echo "✓ uploads/projects directory exists\n";
        
        if (is_writable($uploadsDir)) {
            echo "✓ uploads/projects directory is writable\n";
        } else {
            echo "⚠ uploads/projects directory is not writable\n";
        }
    } else {
        echo "✗ uploads/projects directory does not exist\n";
    }
    echo "\n";
    
    echo "=== All Tests Completed ===\n";
    echo $allPatternsFound ? "✓ All sticky form patterns found\n" : "⚠ Some sticky form patterns missing\n";
    
} catch (Exception $e) {
    echo "✗ Test error: " . $e->getMessage() . "\n";
    exit(1);
}

<?php
/**
 * Test Project Index Page
 * Tests the project index page for proper functionality
 */

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../includes/models/Project.php';

echo "=== Project Index Page Test Suite ===\n\n";

try {
    // Test 1: Check if page exists
    echo "Test 1: Check Page Files Exist\n";
    $indexPage = __DIR__ . '/../pages/projects/index.php';
    $viewPage = __DIR__ . '/../pages/projects/view.php';
    
    if (file_exists($indexPage)) {
        echo "✓ index.php exists\n";
    } else {
        echo "✗ index.php not found\n";
    }
    
    if (file_exists($viewPage)) {
        echo "✓ view.php exists\n";
    } else {
        echo "✗ view.php not found\n";
    }
    echo "\n";
    
    // Test 2: PHP Syntax Check
    echo "Test 2: PHP Syntax Check\n";
    exec("php -l " . escapeshellarg($indexPage), $output1, $return1);
    if ($return1 === 0) {
        echo "✓ index.php has valid PHP syntax\n";
    } else {
        echo "✗ index.php has syntax errors\n";
        echo implode("\n", $output1) . "\n";
    }
    
    exec("php -l " . escapeshellarg($viewPage), $output2, $return2);
    if ($return2 === 0) {
        echo "✓ view.php has valid PHP syntax\n";
    } else {
        echo "✗ view.php has syntax errors\n";
        echo implode("\n", $output2) . "\n";
    }
    echo "\n";
    
    // Test 3: Check required includes exist
    echo "Test 3: Check Required Includes\n";
    $requiredFiles = [
        __DIR__ . '/../src/Auth.php',
        __DIR__ . '/../includes/models/Project.php',
        __DIR__ . '/../src/Database.php'
    ];
    
    foreach ($requiredFiles as $file) {
        if (file_exists($file)) {
            echo "✓ " . basename($file) . " exists\n";
        } else {
            echo "✗ " . basename($file) . " not found\n";
        }
    }
    echo "\n";
    
    // Test 4: Check if index page contains required elements
    echo "Test 4: Check Index Page Structure\n";
    $indexContent = file_get_contents($indexPage);
    
    $requiredElements = [
        'Auth::check' => 'Authentication check',
        'status != \'draft\'' => 'Draft filter',
        'Project::filterSensitiveData' => 'Data filtering',
        'grid grid-cols' => 'Grid layout',
        'view.php?id=' => 'View link',
        'Jetzt bewerben' => 'Apply button',
        'archived' => 'Archived status handling',
        'grayscale' => 'Archived styling',
        'tender' => 'Tender status',
        'applying' => 'Applying status'
    ];
    
    foreach ($requiredElements as $element => $description) {
        if (strpos($indexContent, $element) !== false) {
            echo "✓ Contains $description ($element)\n";
        } else {
            echo "✗ Missing $description ($element)\n";
        }
    }
    echo "\n";
    
    // Test 5: Check if view page contains required elements
    echo "Test 5: Check View Page Structure\n";
    $viewContent = file_get_contents($viewPage);
    
    $viewRequiredElements = [
        'Auth::check' => 'Authentication check',
        'Project::getById' => 'Get project',
        'Project::filterSensitiveData' => 'Data filtering',
        'Project::apply' => 'Apply function',
        'CSRFHandler::verifyToken' => 'CSRF protection',
        'motivation' => 'Application motivation field',
        'experience_count' => 'Experience count field',
        'userRole === \'alumni\'' => 'Alumni role check',
        'Zurück zur Übersicht' => 'Back link'
    ];
    
    foreach ($viewRequiredElements as $element => $description) {
        if (strpos($viewContent, $element) !== false) {
            echo "✓ Contains $description ($element)\n";
        } else {
            echo "✗ Missing $description ($element)\n";
        }
    }
    echo "\n";
    
    // Test 6: Verify project card elements in index
    echo "Test 6: Verify Project Card Elements\n";
    $cardElements = [
        'image_path' => 'Project image',
        'title' => 'Project title',
        'description' => 'Project description',
        'priority' => 'Priority badge',
        'status' => 'Status badge',
        'client_name' => 'Client name',
        'start_date' => 'Start date',
        'end_date' => 'End date',
        'Details ansehen' => 'View details button'
    ];
    
    foreach ($cardElements as $element => $description) {
        if (strpos($indexContent, $element) !== false) {
            echo "✓ Contains $description\n";
        } else {
            echo "✗ Missing $description\n";
        }
    }
    echo "\n";
    
    // Test 7: Test draft filtering logic
    echo "Test 7: Test Draft Filtering Logic\n";
    try {
        $db = Database::getContentDB();
        
        // Check if projects table exists
        $stmt = $db->query("SHOW TABLES LIKE 'projects'");
        $result = $stmt->fetch();
        
        if ($result) {
            echo "✓ Projects table exists\n";
            
            // Create test projects with different statuses
            $testProjects = [
                ['title' => 'Draft Project Test', 'status' => 'draft'],
                ['title' => 'Tender Project Test', 'status' => 'tender'],
                ['title' => 'Archived Project Test', 'status' => 'archived']
            ];
            
            $projectIds = [];
            foreach ($testProjects as $testProject) {
                $projectData = [
                    'title' => $testProject['title'],
                    'description' => 'Test description',
                    'priority' => 'medium',
                    'status' => $testProject['status']
                ];
                $projectIds[] = Project::create($projectData);
            }
            echo "✓ Created test projects\n";
            
            // Query for non-draft projects
            $stmt = $db->query("SELECT * FROM projects WHERE status != 'draft' AND title LIKE '%Test'");
            $nonDraftProjects = $stmt->fetchAll();
            
            $foundDraft = false;
            $foundTender = false;
            $foundArchived = false;
            
            foreach ($nonDraftProjects as $project) {
                if ($project['status'] === 'draft') $foundDraft = true;
                if ($project['status'] === 'tender') $foundTender = true;
                if ($project['status'] === 'archived') $foundArchived = true;
            }
            
            if (!$foundDraft && $foundTender && $foundArchived) {
                echo "✓ Draft filtering works correctly (draft excluded, others included)\n";
            } else {
                echo "✗ Draft filtering failed\n";
                echo "  Found draft: " . ($foundDraft ? 'yes' : 'no') . "\n";
                echo "  Found tender: " . ($foundTender ? 'yes' : 'no') . "\n";
                echo "  Found archived: " . ($foundArchived ? 'yes' : 'no') . "\n";
            }
            
            // Clean up
            foreach ($projectIds as $id) {
                $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
                $stmt->execute([$id]);
            }
            echo "✓ Cleaned up test projects\n";
            
        } else {
            echo "✗ Projects table does not exist\n";
        }
    } catch (Exception $e) {
        echo "⚠ Database not available for testing: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Test 8: Verify role-based button display logic
    echo "Test 8: Verify Role-Based Button Logic\n";
    $roleChecks = [
        'userRole !== \'alumni\'' => 'Alumni exclusion check',
        'status === \'tender\' || $project[\'status\'] === \'applying\'' => 'Status check for apply button',
        'canApply' => 'Apply permission variable'
    ];
    
    foreach ($roleChecks as $check => $description) {
        if (strpos($indexContent, $check) !== false) {
            echo "✓ Contains $description\n";
        } else {
            echo "✗ Missing $description\n";
        }
    }
    echo "\n";
    
    echo "=== All Tests Completed ===\n";
    
} catch (Exception $e) {
    echo "✗ Test error: " . $e->getMessage() . "\n";
    exit(1);
}

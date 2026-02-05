<?php
/**
 * Test Project Management Pages
 * Tests the project management pages for proper functionality
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Project.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../includes/handlers/CSRFHandler.php';

echo "=== Project Management Pages Test Suite ===\n\n";

try {
    // Test 1: Check if pages exist
    echo "Test 1: Check Page Files Exist\n";
    $managePage = __DIR__ . '/../pages/projects/manage.php';
    
    if (file_exists($managePage)) {
        echo "✓ manage.php exists\n";
    } else {
        echo "✗ manage.php not found\n";
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
    }
    echo "\n";
    
    // Test 3: Check required includes exist
    echo "Test 3: Check Required Includes\n";
    $requiredFiles = [
        __DIR__ . '/../src/Auth.php',
        __DIR__ . '/../includes/handlers/CSRFHandler.php',
        __DIR__ . '/../includes/models/Project.php',
        __DIR__ . '/../includes/utils/SecureImageUpload.php',
        __DIR__ . '/../src/Database.php'
    ];
    
    $allExist = true;
    foreach ($requiredFiles as $file) {
        if (file_exists($file)) {
            echo "✓ " . basename($file) . " exists\n";
        } else {
            echo "✗ " . basename($file) . " not found\n";
            $allExist = false;
        }
    }
    echo "\n";
    
    // Test 4: Check if page contains required elements
    echo "Test 4: Check Page Structure\n";
    $pageContent = file_get_contents($managePage);
    
    $requiredElements = [
        'Auth::requireRole' => 'Authentication check',
        'CSRFHandler::getToken' => 'CSRF protection',
        'SecureImageUpload::uploadImage' => 'Image upload',
        'Project::create' => 'Project creation',
        'Project::update' => 'Project update',
        'project_applications' => 'Application count query',
        'save_project' => 'Save handler',
        'delete_project' => 'Delete handler'
    ];
    
    foreach ($requiredElements as $element => $description) {
        if (strpos($pageContent, $element) !== false) {
            echo "✓ Contains $description ($element)\n";
        } else {
            echo "✗ Missing $description ($element)\n";
        }
    }
    echo "\n";
    
    // Test 5: Verify form fields exist
    echo "Test 5: Verify Form Fields\n";
    $requiredFields = [
        'name="title"' => 'Title field',
        'name="description"' => 'Description field',
        'name="client_name"' => 'Client name field',
        'name="client_contact_details"' => 'Client contact details field',
        'name="priority"' => 'Priority field',
        'name="status"' => 'Status field',
        'name="start_date"' => 'Start date field',
        'name="end_date"' => 'End date field',
        'name="project_image"' => 'Image upload field'
    ];
    
    foreach ($requiredFields as $field => $description) {
        if (strpos($pageContent, $field) !== false) {
            echo "✓ Contains $description\n";
        } else {
            echo "✗ Missing $description\n";
        }
    }
    echo "\n";
    
    // Test 6: Check Project Model Methods
    echo "Test 6: Check Project Model Methods\n";
    $projectMethods = ['create', 'update', 'getAll', 'getById', 'filterSensitiveData', 'apply', 'assignMember', 'getApplications'];
    
    foreach ($projectMethods as $method) {
        if (method_exists('Project', $method)) {
            echo "✓ Project::$method exists\n";
        } else {
            echo "✗ Project::$method not found\n";
        }
    }
    echo "\n";
    
    // Test 7: Test project creation (if database is available)
    echo "Test 7: Test Project Creation\n";
    try {
        $db = Database::getContentDB();
        
        // Check if projects table exists
        $stmt = $db->query("SHOW TABLES LIKE 'projects'");
        $result = $stmt->fetch();
        
        if ($result) {
            echo "✓ Projects table exists\n";
            
            // Try to create a test project
            $testProjectData = [
                'title' => 'Test Project for Pages',
                'description' => 'Testing project management pages',
                'client_name' => 'Test Client',
                'client_contact_details' => 'test@example.com',
                'priority' => 'medium',
                'status' => 'draft',
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+30 days'))
            ];
            
            $projectId = Project::create($testProjectData);
            echo "✓ Created test project with ID: $projectId\n";
            
            // Verify the project was created
            $project = Project::getById($projectId);
            if ($project && $project['title'] === 'Test Project for Pages') {
                echo "✓ Retrieved test project successfully\n";
            } else {
                echo "✗ Failed to retrieve test project\n";
            }
            
            // Test update
            Project::update($projectId, ['status' => 'applying']);
            $updatedProject = Project::getById($projectId);
            if ($updatedProject['status'] === 'applying') {
                echo "✓ Updated project status successfully\n";
            } else {
                echo "✗ Failed to update project status\n";
            }
            
            // Clean up
            $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            echo "✓ Cleaned up test project\n";
            
        } else {
            echo "✗ Projects table does not exist\n";
        }
    } catch (Exception $e) {
        echo "⚠ Database not available for testing: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    echo "=== All Tests Completed ===\n";
    
} catch (Exception $e) {
    echo "✗ Test error: " . $e->getMessage() . "\n";
    exit(1);
}

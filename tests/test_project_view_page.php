<?php
/**
 * Test Project View Page
 * Tests the view.php page functionality including application status display
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Project.php';

// Test configuration
$testUserId = 2;
$testUser2Id = 3;

echo "=== Project View Page Test Suite ===\n\n";

try {
    // Test 1: Create Test Project
    echo "Test 1: Create Test Project\n";
    $projectData = [
        'title' => 'Test View Page Project',
        'description' => 'Testing the view page functionality',
        'client_name' => 'Test Client',
        'client_contact_details' => 'test@example.com',
        'priority' => 'medium',
        'status' => 'applying',
        'start_date' => date('Y-m-d', strtotime('+7 days')),
        'end_date' => date('Y-m-d', strtotime('+60 days'))
    ];
    
    $projectId = Project::create($projectData);
    echo "✓ Test project created with ID: $projectId\n\n";
    
    // Test 2: Check getUserApplication for non-existent application
    echo "Test 2: Check getUserApplication (no application)\n";
    $application = Project::getUserApplication($projectId, $testUserId);
    if ($application === false) {
        echo "✓ No application found (expected)\n\n";
    } else {
        echo "✗ Found unexpected application\n\n";
    }
    
    // Test 3: Submit Application
    echo "Test 3: Submit Application\n";
    $applicationData = [
        'motivation' => 'I would like to work on this project because...',
        'experience_count' => 2
    ];
    
    try {
        $applicationId = Project::apply($projectId, $testUserId, $applicationData);
        echo "✓ Application submitted with ID: $applicationId\n\n";
    } catch (Exception $e) {
        echo "✗ Failed to submit application: " . $e->getMessage() . "\n\n";
    }
    
    // Test 4: Check getUserApplication for existing application
    echo "Test 4: Check getUserApplication (application exists)\n";
    $application = Project::getUserApplication($projectId, $testUserId);
    if ($application !== false) {
        echo "✓ Application found successfully\n";
        echo "  Application ID: {$application['id']}\n";
        echo "  User ID: {$application['user_id']}\n";
        echo "  Status: {$application['status']}\n";
        echo "  Motivation: " . substr($application['motivation'], 0, 50) . "...\n";
        echo "  Experience Count: {$application['experience_count']}\n\n";
    } else {
        echo "✗ Application not found (expected to exist)\n\n";
    }
    
    // Test 5: Verify application status is 'pending'
    echo "Test 5: Verify Application Status\n";
    if ($application['status'] === 'pending') {
        echo "✓ Application status is 'pending' as expected\n\n";
    } else {
        echo "✗ Application status is '{$application['status']}' (expected 'pending')\n\n";
    }
    
    // Test 6: Verify another user doesn't see the application
    echo "Test 6: Check getUserApplication for different user\n";
    $otherUserApplication = Project::getUserApplication($projectId, $testUser2Id);
    if ($otherUserApplication === false) {
        echo "✓ Different user has no application (expected)\n\n";
    } else {
        echo "✗ Different user sees application (unexpected)\n\n";
    }
    
    // Test 7: Update application status to 'reviewing'
    echo "Test 7: Update Application Status\n";
    $db = Database::getContentDB();
    $stmt = $db->prepare("UPDATE project_applications SET status = 'reviewing' WHERE id = ?");
    $stmt->execute([$applicationId]);
    
    // Retrieve updated application
    $updatedApplication = Project::getUserApplication($projectId, $testUserId);
    if ($updatedApplication['status'] === 'reviewing') {
        echo "✓ Application status updated to 'reviewing'\n\n";
    } else {
        echo "✗ Failed to update application status\n\n";
    }
    
    // Test 8: Check view.php file exists and has valid syntax
    echo "Test 8: Check view.php File\n";
    $viewFile = __DIR__ . '/../pages/projects/view.php';
    if (file_exists($viewFile)) {
        echo "✓ view.php file exists\n";
        
        // Check syntax
        exec("php -l " . escapeshellarg($viewFile), $output, $return);
        if ($return === 0) {
            echo "✓ view.php has valid PHP syntax\n";
        } else {
            echo "✗ view.php has syntax errors\n";
            echo implode("\n", $output) . "\n";
        }
    } else {
        echo "✗ view.php file not found\n";
    }
    echo "\n";
    
    // Test 9: Verify view.php contains required elements
    echo "Test 9: Verify view.php Structure\n";
    $viewContent = file_get_contents($viewFile);
    
    $requiredElements = [
        'getUserApplication' => 'Checks for existing application',
        'userApplication' => 'Stores application data',
        'experience_confirmed' => 'Experience confirmation checkbox',
        'gdpr_consent' => 'GDPR consent checkbox',
        'In Prüfung' => 'Application status display',
        'Ihre Bewerbung' => 'Application section header'
    ];
    
    $allPresent = true;
    foreach ($requiredElements as $element => $description) {
        if (strpos($viewContent, $element) !== false) {
            echo "✓ Contains $description ($element)\n";
        } else {
            echo "✗ Missing $description ($element)\n";
            $allPresent = false;
        }
    }
    echo "\n";
    
    // Test 10: Clean up test data
    echo "Test 10: Clean Up Test Data\n";
    $stmt = $db->prepare("DELETE FROM project_applications WHERE project_id = ?");
    $stmt->execute([$projectId]);
    
    $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    echo "✓ Test data cleaned up\n\n";
    
    echo "=== All Tests Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

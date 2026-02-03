<?php
/**
 * Test Project Model
 * Tests CRUD operations, security filtering, applications and assignments
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Project.php';

// Test configuration
$testBoardUserId = 1;
$testMemberUserId = 2;
$testAlumniUserId = 3;

echo "=== Project Model Test Suite ===\n\n";

try {
    // Test 1: Create Project
    echo "Test 1: Create Project\n";
    $projectData = [
        'title' => 'Test Software Development Project',
        'description' => 'A test project for software development',
        'client_name' => 'ACME Corporation',
        'client_contact_details' => 'contact@acme.com, +1-555-1234',
        'priority' => 'high',
        'status' => 'tender',
        'start_date' => date('Y-m-d', strtotime('+30 days')),
        'end_date' => date('Y-m-d', strtotime('+90 days'))
    ];
    
    $projectId = Project::create($projectData);
    echo "✓ Project created with ID: $projectId\n\n";
    
    // Test 2: Get Project by ID
    echo "Test 2: Get Project by ID\n";
    $project = Project::getById($projectId);
    if ($project && $project['title'] === $projectData['title']) {
        echo "✓ Project retrieved successfully\n";
        echo "  Title: {$project['title']}\n";
        echo "  Client: {$project['client_name']}\n";
        echo "  Status: {$project['status']}\n\n";
    } else {
        echo "✗ Failed to retrieve project\n\n";
    }
    
    // Test 3: Update Project
    echo "Test 3: Update Project\n";
    $updateData = [
        'description' => 'Updated description for the project',
        'status' => 'applying'
    ];
    $updateResult = Project::update($projectId, $updateData);
    if ($updateResult) {
        $updatedProject = Project::getById($projectId);
        if ($updatedProject['description'] === $updateData['description']) {
            echo "✓ Project updated successfully\n";
            echo "  New description: {$updatedProject['description']}\n";
            echo "  New status: {$updatedProject['status']}\n\n";
        } else {
            echo "✗ Update did not persist\n\n";
        }
    } else {
        echo "✗ Failed to update project\n\n";
    }
    
    // Test 4: Get All Projects
    echo "Test 4: Get All Projects\n";
    $allProjects = Project::getAll();
    if (is_array($allProjects) && count($allProjects) > 0) {
        echo "✓ Retrieved " . count($allProjects) . " projects\n\n";
    } else {
        echo "✗ Failed to retrieve projects list\n\n";
    }
    
    // Test 5: Filter Sensitive Data - Board User (should see everything)
    echo "Test 5: Filter Sensitive Data - Board User\n";
    $filteredProject = Project::filterSensitiveData($project, 'board', $testBoardUserId);
    if (isset($filteredProject['client_name']) && isset($filteredProject['client_contact_details'])) {
        echo "✓ Board user can see sensitive data\n";
        echo "  Client Name: {$filteredProject['client_name']}\n";
        echo "  Client Contact: {$filteredProject['client_contact_details']}\n\n";
    } else {
        echo "✗ Board user cannot see sensitive data (FAILED)\n\n";
    }
    
    // Test 6: Filter Sensitive Data - Manager User (should see everything)
    echo "Test 6: Filter Sensitive Data - Manager User\n";
    $filteredProject = Project::filterSensitiveData($project, 'manager', $testBoardUserId);
    if (isset($filteredProject['client_name']) && isset($filteredProject['client_contact_details'])) {
        echo "✓ Manager user can see sensitive data\n\n";
    } else {
        echo "✗ Manager user cannot see sensitive data (FAILED)\n\n";
    }
    
    // Test 7: Filter Sensitive Data - Member User (not assigned, should NOT see)
    echo "Test 7: Filter Sensitive Data - Member User (not assigned)\n";
    $filteredProject = Project::filterSensitiveData($project, 'member', $testMemberUserId);
    if (!isset($filteredProject['client_name']) && !isset($filteredProject['client_contact_details'])) {
        echo "✓ Non-assigned member cannot see sensitive data\n";
        echo "  Available fields: " . implode(', ', array_keys($filteredProject)) . "\n\n";
    } else {
        echo "✗ Non-assigned member can see sensitive data (SECURITY ISSUE)\n\n";
    }
    
    // Test 8: Filter Sensitive Data - Alumni User (should NEVER see)
    echo "Test 8: Filter Sensitive Data - Alumni User\n";
    $filteredProject = Project::filterSensitiveData($project, 'alumni', $testAlumniUserId);
    if (!isset($filteredProject['client_name']) && !isset($filteredProject['client_contact_details'])) {
        echo "✓ Alumni user cannot see sensitive data\n\n";
    } else {
        echo "✗ Alumni user can see sensitive data (CRITICAL SECURITY ISSUE)\n\n";
    }
    
    // Test 9: Apply for Project
    echo "Test 9: Apply for Project\n";
    $applicationData = [
        'motivation' => 'I am very interested in this project and have relevant experience.',
        'experience_count' => 3
    ];
    try {
        $applicationId = Project::apply($projectId, $testMemberUserId, $applicationData);
        echo "✓ Application submitted with ID: $applicationId\n\n";
    } catch (Exception $e) {
        echo "✗ Failed to submit application: " . $e->getMessage() . "\n\n";
    }
    
    // Test 10: Duplicate Application (should fail)
    echo "Test 10: Duplicate Application (should fail)\n";
    try {
        Project::apply($projectId, $testMemberUserId, $applicationData);
        echo "✗ Duplicate application was allowed (SHOULD FAIL)\n\n";
    } catch (Exception $e) {
        echo "✓ Duplicate application prevented: " . $e->getMessage() . "\n\n";
    }
    
    // Test 11: Assign Member to Project
    echo "Test 11: Assign Member to Project\n";
    $assignmentId = Project::assignMember($projectId, $testMemberUserId, 'member');
    echo "✓ Member assigned to project with ID: $assignmentId\n\n";
    
    // Test 12: Filter Sensitive Data - Assigned Member (should see)
    echo "Test 12: Filter Sensitive Data - Assigned Member\n";
    $filteredProject = Project::filterSensitiveData($project, 'member', $testMemberUserId);
    if (isset($filteredProject['client_name']) && isset($filteredProject['client_contact_details'])) {
        echo "✓ Assigned member can see sensitive data\n";
        echo "  Client Name: {$filteredProject['client_name']}\n\n";
    } else {
        echo "✗ Assigned member cannot see sensitive data (FAILED)\n\n";
    }
    
    // Test 13: Update Assignment (change role)
    echo "Test 13: Update Assignment (change role to lead)\n";
    $updatedAssignmentId = Project::assignMember($projectId, $testMemberUserId, 'lead');
    if ($updatedAssignmentId == $assignmentId) {
        echo "✓ Member role updated to lead\n\n";
    } else {
        echo "✗ Failed to update assignment role\n\n";
    }
    
    // Test 14: Get Applications
    echo "Test 14: Get Applications for Project\n";
    $applications = Project::getApplications($projectId);
    if (is_array($applications) && count($applications) > 0) {
        echo "✓ Retrieved " . count($applications) . " applications\n";
        foreach ($applications as $app) {
            echo "  - Application ID: {$app['id']}, User: {$app['user_id']}, Status: {$app['status']}\n";
        }
        echo "\n";
    } else {
        echo "✗ Failed to retrieve applications\n\n";
    }
    
    // Test 15: Assign Second Member
    echo "Test 15: Assign Second Member\n";
    $secondAssignmentId = Project::assignMember($projectId, $testAlumniUserId, 'member');
    echo "✓ Second member assigned with ID: $secondAssignmentId\n\n";
    
    // Test 16: Filter Sensitive Data - Assigned Alumni (still should NOT see)
    echo "Test 16: Filter Sensitive Data - Assigned Alumni (still blocked)\n";
    $filteredProject = Project::filterSensitiveData($project, 'alumni', $testAlumniUserId);
    if (!isset($filteredProject['client_name']) && !isset($filteredProject['client_contact_details'])) {
        echo "✓ Alumni user still cannot see sensitive data even when assigned\n\n";
    } else {
        echo "✗ Assigned alumni can see sensitive data (CRITICAL SECURITY ISSUE)\n\n";
    }
    
    echo "=== All Tests Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

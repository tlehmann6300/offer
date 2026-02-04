<?php
/**
 * Test Project Draft Security
 * Tests that draft projects are only accessible to managers and above
 */

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../includes/models/Project.php';
require_once __DIR__ . '/../src/Auth.php';

echo "=== Project Draft Security Test Suite ===\n\n";

try {
    // Create a test draft project
    echo "Test 1: Create Draft Project\n";
    $projectData = [
        'title' => 'Draft Security Test Project',
        'description' => 'Testing draft project security',
        'client_name' => 'Test Client',
        'client_contact_details' => 'test@example.com',
        'priority' => 'medium',
        'status' => 'draft',
        'start_date' => date('Y-m-d', strtotime('+7 days')),
        'end_date' => date('Y-m-d', strtotime('+60 days'))
    ];
    
    $projectId = Project::create($projectData);
    echo "✓ Draft project created with ID: $projectId\n\n";
    
    // Test 2: Verify Auth::hasPermission works correctly for different roles
    echo "Test 2: Verify Auth::hasPermission for different roles\n";
    
    // Mock session for testing different roles
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Test member role (should not have manager permission)
    $_SESSION['user_role'] = 'member';
    $_SESSION['authenticated'] = true;
    $_SESSION['last_activity'] = time();
    $hasMemberPermission = Auth::hasPermission('manager');
    echo "Member has manager permission: " . ($hasMemberPermission ? "YES" : "NO") . " (expected: NO)\n";
    if (!$hasMemberPermission) {
        echo "✓ Member correctly denied manager permission\n";
    } else {
        echo "✗ Member incorrectly granted manager permission\n";
    }
    
    $hasMemberProjectPermission = Auth::hasPermission('manage_projects');
    echo "Member has manage_projects permission: " . ($hasMemberProjectPermission ? "YES" : "NO") . " (expected: NO)\n";
    if (!$hasMemberProjectPermission) {
        echo "✓ Member correctly denied manage_projects permission\n";
    } else {
        echo "✗ Member incorrectly granted manage_projects permission\n";
    }
    
    // Test manager role (should have manager permission)
    $_SESSION['user_role'] = 'manager';
    $hasManagerPermission = Auth::hasPermission('manager');
    echo "Manager has manager permission: " . ($hasManagerPermission ? "YES" : "NO") . " (expected: YES)\n";
    if ($hasManagerPermission) {
        echo "✓ Manager correctly granted manager permission\n";
    } else {
        echo "✗ Manager incorrectly denied manager permission\n";
    }
    
    $hasManagerProjectPermission = Auth::hasPermission('manage_projects');
    echo "Manager has manage_projects permission: " . ($hasManagerProjectPermission ? "YES" : "NO") . " (expected: YES)\n";
    if ($hasManagerProjectPermission) {
        echo "✓ Manager correctly granted manage_projects permission\n";
    } else {
        echo "✗ Manager incorrectly denied manage_projects permission\n";
    }
    
    // Test board role (should have manager permission)
    $_SESSION['user_role'] = 'board';
    $hasBoardPermission = Auth::hasPermission('manager');
    echo "Board has manager permission: " . ($hasBoardPermission ? "YES" : "NO") . " (expected: YES)\n";
    if ($hasBoardPermission) {
        echo "✓ Board correctly granted manager permission\n";
    } else {
        echo "✗ Board incorrectly denied manager permission\n";
    }
    
    $hasBoardProjectPermission = Auth::hasPermission('manage_projects');
    echo "Board has manage_projects permission: " . ($hasBoardProjectPermission ? "YES" : "NO") . " (expected: YES)\n";
    if ($hasBoardProjectPermission) {
        echo "✓ Board correctly granted manage_projects permission\n";
    } else {
        echo "✗ Board incorrectly denied manage_projects permission\n";
    }
    
    echo "\n";
    
    // Test 3: Verify draft project visibility logic
    echo "Test 3: Verify draft project visibility logic\n";
    $project = Project::getById($projectId);
    
    if ($project && $project['status'] === 'draft') {
        echo "✓ Draft project retrieved successfully\n";
        
        // Simulate the security check using manage_projects permission
        $_SESSION['user_role'] = 'member';
        $canViewAsMember = !(isset($project['status']) && $project['status'] === 'draft' && !Auth::hasPermission('manage_projects'));
        echo "Member can view draft: " . ($canViewAsMember ? "YES" : "NO") . " (expected: NO)\n";
        if (!$canViewAsMember) {
            echo "✓ Member correctly blocked from viewing draft\n";
        } else {
            echo "✗ Member incorrectly allowed to view draft\n";
        }
        
        $_SESSION['user_role'] = 'manager';
        $canViewAsManager = !(isset($project['status']) && $project['status'] === 'draft' && !Auth::hasPermission('manage_projects'));
        echo "Manager can view draft: " . ($canViewAsManager ? "YES" : "NO") . " (expected: YES)\n";
        if ($canViewAsManager) {
            echo "✓ Manager correctly allowed to view draft\n";
        } else {
            echo "✗ Manager incorrectly blocked from viewing draft\n";
        }
    } else {
        echo "✗ Failed to retrieve draft project\n";
    }
    
    echo "\n";
    
    // Cleanup
    echo "Cleanup: Deleting test project\n";
    $db = Database::getContentDB();
    $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    echo "✓ Test project deleted\n\n";
    
    echo "=== All Tests Completed ===\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

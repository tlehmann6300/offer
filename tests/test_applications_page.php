<?php
/**
 * Test Project Applications Page
 * Tests the project applications page for proper functionality
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Project.php';
require_once __DIR__ . '/../includes/models/User.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../includes/handlers/CSRFHandler.php';

echo "=== Project Applications Page Test Suite ===\n\n";

try {
    // Test 1: Check if page exists
    echo "Test 1: Check Page Files Exist\n";
    $applicationsPage = __DIR__ . '/../pages/projects/applications.php';
    
    if (file_exists($applicationsPage)) {
        echo "✓ applications.php exists\n";
    } else {
        echo "✗ applications.php not found\n";
    }
    echo "\n";
    
    // Test 2: PHP Syntax Check
    echo "Test 2: PHP Syntax Check\n";
    exec("php -l " . escapeshellarg($applicationsPage), $output, $return);
    if ($return === 0) {
        echo "✓ applications.php has valid PHP syntax\n";
    } else {
        echo "✗ applications.php has syntax errors\n";
        echo implode("\n", $output) . "\n";
    }
    echo "\n";
    
    // Test 3: Check required includes exist
    echo "Test 3: Check Required Includes\n";
    $requiredFiles = [
        __DIR__ . '/../src/Auth.php',
        __DIR__ . '/../includes/handlers/CSRFHandler.php',
        __DIR__ . '/../includes/models/Project.php',
        __DIR__ . '/../includes/models/User.php',
        __DIR__ . '/../src/Database.php',
        __DIR__ . '/../src/MailService.php'
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
    $pageContent = file_get_contents($applicationsPage);
    
    $requiredElements = [
        'Auth::requireRole' => 'Authentication check',
        'CSRFHandler::getToken' => 'CSRF protection',
        'Project::getById' => 'Get project',
        'Project::getApplications' => 'Get applications',
        'Project::assignMember' => 'Assign member',
        'User::getById' => 'Get user info',
        'MailService::sendProjectAcceptance' => 'Send acceptance email',
        'accept_application' => 'Accept handler',
        'reject_application' => 'Reject handler',
        'project_id' => 'Project ID parameter',
        'acceptModal' => 'Accept modal',
        'rejectModal' => 'Reject modal',
        'name="role"' => 'Role selection'
    ];
    
    foreach ($requiredElements as $element => $description) {
        if (strpos($pageContent, $element) !== false) {
            echo "✓ Contains $description ($element)\n";
        } else {
            echo "✗ Missing $description ($element)\n";
        }
    }
    echo "\n";
    
    // Test 5: Check if page has proper modals
    echo "Test 5: Verify Modal Structure\n";
    $modalElements = [
        'id="acceptModal"' => 'Accept modal',
        'id="rejectModal"' => 'Reject modal',
        'showAcceptModal' => 'Accept modal function',
        'showRejectModal' => 'Reject modal function',
        'closeAcceptModal' => 'Close accept modal function',
        'closeRejectModal' => 'Close reject modal function'
    ];
    
    foreach ($modalElements as $element => $description) {
        if (strpos($pageContent, $element) !== false) {
            echo "✓ Contains $description\n";
        } else {
            echo "✗ Missing $description\n";
        }
    }
    echo "\n";
    
    // Test 6: Check MailService method exists
    echo "Test 6: Check MailService Methods\n";
    require_once __DIR__ . '/../src/MailService.php';
    
    if (method_exists('MailService', 'sendProjectAcceptance')) {
        echo "✓ MailService::sendProjectAcceptance exists\n";
    } else {
        echo "✗ MailService::sendProjectAcceptance not found\n";
    }
    echo "\n";
    
    // Test 7: Test application workflow (if database is available)
    echo "Test 7: Test Application Workflow\n";
    try {
        $db = Database::getContentDB();
        
        // Check if required tables exist
        $stmt = $db->query("SHOW TABLES LIKE 'projects'");
        $hasProjects = $stmt->fetch();
        
        $stmt = $db->query("SHOW TABLES LIKE 'project_applications'");
        $hasApplications = $stmt->fetch();
        
        $stmt = $db->query("SHOW TABLES LIKE 'project_assignments'");
        $hasAssignments = $stmt->fetch();
        
        if ($hasProjects && $hasApplications && $hasAssignments) {
            echo "✓ All required tables exist\n";
            
            // Create a test project
            $testProjectData = [
                'title' => 'Test Project for Applications',
                'description' => 'Testing project applications',
                'priority' => 'medium',
                'status' => 'applying'
            ];
            
            $projectId = Project::create($testProjectData);
            echo "✓ Created test project with ID: $projectId\n";
            
            // Create a test application (simulating a user application)
            $userDb = Database::getUserDB();
            $stmt = $userDb->query("SELECT id FROM users LIMIT 1");
            $testUser = $stmt->fetch();
            
            if ($testUser) {
                $userId = $testUser['id'];
                
                try {
                    Project::apply($projectId, $userId, [
                        'motivation' => 'Test motivation',
                        'experience_count' => 5
                    ]);
                    echo "✓ Created test application\n";
                    
                    // Get applications
                    $applications = Project::getApplications($projectId);
                    if (count($applications) > 0) {
                        echo "✓ Retrieved applications successfully\n";
                        
                        $applicationId = $applications[0]['id'];
                        
                        // Test acceptance flow
                        Project::assignMember($projectId, $userId, 'member');
                        echo "✓ Assigned member to project\n";
                        
                        // Update application status
                        $stmt = $db->prepare("UPDATE project_applications SET status = 'accepted' WHERE id = ?");
                        $stmt->execute([$applicationId]);
                        echo "✓ Updated application status\n";
                        
                        // Verify assignment
                        $stmt = $db->prepare("SELECT * FROM project_assignments WHERE project_id = ? AND user_id = ?");
                        $stmt->execute([$projectId, $userId]);
                        $assignment = $stmt->fetch();
                        
                        if ($assignment) {
                            echo "✓ Assignment created successfully\n";
                        } else {
                            echo "✗ Assignment not found\n";
                        }
                    } else {
                        echo "✗ Failed to retrieve applications\n";
                    }
                    
                } catch (Exception $e) {
                    echo "⚠ Application already exists or error: " . $e->getMessage() . "\n";
                }
            } else {
                echo "⚠ No test user available\n";
            }
            
            // Clean up
            $stmt = $db->prepare("DELETE FROM project_assignments WHERE project_id = ?");
            $stmt->execute([$projectId]);
            
            $stmt = $db->prepare("DELETE FROM project_applications WHERE project_id = ?");
            $stmt->execute([$projectId]);
            
            $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            echo "✓ Cleaned up test data\n";
            
        } else {
            echo "✗ Required tables do not exist\n";
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

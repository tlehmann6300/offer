<?php
/**
 * Test Member Model
 * Tests getAllActive() and getStatistics() methods
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Member.php';
require_once __DIR__ . '/../includes/models/User.php';
require_once __DIR__ . '/../includes/models/Alumni.php';

// Test configuration
$testUserIds = [2001, 2002, 2003, 2004, 2005];

echo "=== Member Model Test Suite ===\n\n";

try {
    $userDb = Database::getUserDB();
    $contentDb = Database::getContentDB();
    
    // Clean up any existing test data
    echo "Cleaning up existing test data...\n";
    $userDb->prepare("DELETE FROM users WHERE id IN (?, ?, ?, ?, ?)")->execute($testUserIds);
    $contentDb->prepare("DELETE FROM alumni_profiles WHERE user_id IN (?, ?, ?, ?, ?)")->execute($testUserIds);
    echo "✓ Cleanup complete\n\n";
    
    // Setup: Create test users with different roles
    echo "Setting up test data...\n";
    
    // Test User 1: Board member
    $userDb->prepare("INSERT INTO users (id, email, password_hash, role, is_alumni_validated) VALUES (?, ?, ?, ?, ?)")
        ->execute([$testUserIds[0], 'board.member@test.com', 'hash1', 'board', 1]);
    Alumni::updateOrCreateProfile($testUserIds[0], [
        'first_name' => 'Alice',
        'last_name' => 'Anderson',
        'email' => 'board.member@test.com',
        'company' => 'Anderson Corp',
        'position' => 'Board Member',
        'industry' => 'Technology'
    ]);
    
    // Test User 2: Regular member
    $userDb->prepare("INSERT INTO users (id, email, password_hash, role, is_alumni_validated) VALUES (?, ?, ?, ?, ?)")
        ->execute([$testUserIds[1], 'regular.member@test.com', 'hash2', 'member', 1]);
    Alumni::updateOrCreateProfile($testUserIds[1], [
        'first_name' => 'Bob',
        'last_name' => 'Brown',
        'email' => 'regular.member@test.com',
        'company' => 'Brown Industries',
        'position' => 'Developer',
        'industry' => 'Software'
    ]);
    
    // Test User 3: Candidate
    $userDb->prepare("INSERT INTO users (id, email, password_hash, role, is_alumni_validated) VALUES (?, ?, ?, ?, ?)")
        ->execute([$testUserIds[2], 'candidate@test.com', 'hash3', 'candidate', 1]);
    Alumni::updateOrCreateProfile($testUserIds[2], [
        'first_name' => 'Charlie',
        'last_name' => 'Clark',
        'email' => 'candidate@test.com',
        'company' => 'Clark Solutions',
        'position' => 'Junior Developer',
        'industry' => 'Technology'
    ]);
    
    // Test User 4: Alumni (should NOT appear in active members)
    $userDb->prepare("INSERT INTO users (id, email, password_hash, role, is_alumni_validated) VALUES (?, ?, ?, ?, ?)")
        ->execute([$testUserIds[3], 'alumni@test.com', 'hash4', 'alumni', 1]);
    Alumni::updateOrCreateProfile($testUserIds[3], [
        'first_name' => 'David',
        'last_name' => 'Davis',
        'email' => 'alumni@test.com',
        'company' => 'Davis Enterprises',
        'position' => 'Alumni',
        'industry' => 'Finance'
    ]);
    
    // Test User 5: Admin
    $userDb->prepare("INSERT INTO users (id, email, password_hash, role, is_alumni_validated) VALUES (?, ?, ?, ?, ?)")
        ->execute([$testUserIds[4], 'admin@test.com', 'hash5', 'admin', 1]);
    Alumni::updateOrCreateProfile($testUserIds[4], [
        'first_name' => 'Eva',
        'last_name' => 'Evans',
        'email' => 'admin@test.com',
        'company' => 'Evans Tech',
        'position' => 'Administrator',
        'industry' => 'Technology'
    ]);
    
    echo "✓ Test data created\n\n";
    
    // Test 1: Get all active members (no filters)
    echo "Test 1: Get All Active Members (No Filters)\n";
    $allActive = Member::getAllActive();
    $testUserCount = 0;
    foreach ($allActive as $member) {
        if (in_array($member['user_id'], $testUserIds)) {
            $testUserCount++;
        }
    }
    
    // Should find 4 test users (board, member, candidate, admin) but NOT alumni
    if ($testUserCount === 4) {
        echo "✓ Found exactly 4 active test members (excluding alumni)\n";
        echo "  Total active members in system: " . count($allActive) . "\n\n";
    } else {
        echo "✗ Expected 4 active test members, found $testUserCount\n\n";
    }
    
    // Test 2: Verify alumni is excluded
    echo "Test 2: Verify Alumni is Excluded\n";
    $alumniFound = false;
    foreach ($allActive as $member) {
        if ($member['user_id'] === $testUserIds[3]) {
            $alumniFound = true;
            break;
        }
    }
    
    if (!$alumniFound) {
        echo "✓ Alumni user correctly excluded from active members\n\n";
    } else {
        echo "✗ Alumni user incorrectly included in active members\n\n";
    }
    
    // Test 3: Search by name
    echo "Test 3: Search by Name\n";
    $searchResults = Member::getAllActive('Charlie');
    $charlieFound = false;
    foreach ($searchResults as $member) {
        if ($member['first_name'] === 'Charlie') {
            $charlieFound = true;
            echo "✓ Found Charlie Clark by name search\n";
            echo "  Name: {$member['first_name']} {$member['last_name']}\n";
            echo "  Role: {$member['role']}\n";
            echo "  Company: {$member['company']}\n\n";
            break;
        }
    }
    
    if (!$charlieFound) {
        echo "✗ Failed to find Charlie by name search\n\n";
    }
    
    // Test 4: Search by company
    echo "Test 4: Search by Company\n";
    $searchResults = Member::getAllActive('Brown Industries');
    $bobFound = false;
    foreach ($searchResults as $member) {
        if ($member['company'] === 'Brown Industries') {
            $bobFound = true;
            echo "✓ Found Bob Brown by company search\n";
            echo "  Name: {$member['first_name']} {$member['last_name']}\n";
            echo "  Company: {$member['company']}\n\n";
            break;
        }
    }
    
    if (!$bobFound) {
        echo "✗ Failed to find Bob by company search\n\n";
    }
    
    // Test 5: Search by industry
    echo "Test 5: Search by Industry\n";
    $searchResults = Member::getAllActive('Technology');
    $techCount = 0;
    foreach ($searchResults as $member) {
        if (in_array($member['user_id'], $testUserIds) && $member['industry'] === 'Technology') {
            $techCount++;
        }
    }
    
    // Alice (board), Charlie (candidate), and Eva (admin) have Technology industry
    if ($techCount === 3) {
        echo "✓ Found 3 test members in Technology industry\n\n";
    } else {
        echo "✗ Expected 3 Technology members, found $techCount\n\n";
    }
    
    // Test 6: Filter by role (candidate)
    echo "Test 6: Filter by Role (Candidate)\n";
    $candidates = Member::getAllActive(null, 'candidate');
    $testCandidateFound = false;
    foreach ($candidates as $member) {
        if ($member['user_id'] === $testUserIds[2]) {
            $testCandidateFound = true;
            echo "✓ Found candidate Charlie Clark\n";
            echo "  Name: {$member['first_name']} {$member['last_name']}\n";
            echo "  Role: {$member['role']}\n\n";
            break;
        }
    }
    
    if (!$testCandidateFound) {
        echo "✗ Failed to find candidate by role filter\n\n";
    }
    
    // Test 7: Combined search and filter
    echo "Test 7: Combined Search and Role Filter\n";
    $results = Member::getAllActive('Technology', 'candidate');
    $found = false;
    foreach ($results as $member) {
        if ($member['user_id'] === $testUserIds[2]) {
            $found = true;
            echo "✓ Found Charlie (candidate in Technology)\n";
            echo "  Name: {$member['first_name']} {$member['last_name']}\n";
            echo "  Role: {$member['role']}\n";
            echo "  Industry: {$member['industry']}\n\n";
            break;
        }
    }
    
    if (!$found) {
        echo "✗ Failed to find Charlie with combined filters\n\n";
    }
    
    // Test 8: Verify ordering by last_name
    echo "Test 8: Verify Ordering by Last Name\n";
    $ordered = Member::getAllActive();
    $testMembers = [];
    foreach ($ordered as $member) {
        if (in_array($member['user_id'], $testUserIds) && $member['role'] !== 'alumni') {
            $testMembers[] = $member['last_name'];
        }
    }
    
    // Should be: Anderson, Brown, Clark, Evans
    $expectedOrder = ['Anderson', 'Brown', 'Clark', 'Evans'];
    $actualOrder = array_slice($testMembers, 0, 4);
    
    if ($actualOrder === $expectedOrder) {
        echo "✓ Members correctly ordered by last_name\n";
        echo "  Order: " . implode(', ', $actualOrder) . "\n\n";
    } else {
        echo "✗ Members not correctly ordered\n";
        echo "  Expected: " . implode(', ', $expectedOrder) . "\n";
        echo "  Actual: " . implode(', ', $actualOrder) . "\n\n";
    }
    
    // Test 9: Get statistics
    echo "Test 9: Get Member Statistics\n";
    $stats = Member::getStatistics();
    
    echo "✓ Statistics retrieved:\n";
    foreach ($stats as $role => $count) {
        echo "  $role: $count\n";
    }
    echo "\n";
    
    // Verify our test data is included
    $hasBoard = isset($stats['board']) && $stats['board'] >= 1;
    $hasMember = isset($stats['member']) && $stats['member'] >= 1;
    $hasCandidate = isset($stats['candidate']) && $stats['candidate'] >= 1;
    $hasAdmin = isset($stats['admin']) && $stats['admin'] >= 1;
    $hasAlumni = isset($stats['alumni']);
    
    if ($hasBoard && $hasMember && $hasCandidate && $hasAdmin && !$hasAlumni) {
        echo "✓ Statistics correctly exclude alumni and include active roles\n\n";
    } else {
        echo "✗ Statistics validation failed\n";
        echo "  Has board: " . ($hasBoard ? 'yes' : 'no') . "\n";
        echo "  Has member: " . ($hasMember ? 'yes' : 'no') . "\n";
        echo "  Has candidate: " . ($hasCandidate ? 'yes' : 'no') . "\n";
        echo "  Has admin: " . ($hasAdmin ? 'yes' : 'no') . "\n";
        echo "  Has alumni: " . ($hasAlumni ? 'yes' : 'no') . "\n\n";
    }
    
    // Cleanup
    echo "Cleaning up test data...\n";
    $userDb->prepare("DELETE FROM users WHERE id IN (?, ?, ?, ?, ?)")->execute($testUserIds);
    $contentDb->prepare("DELETE FROM alumni_profiles WHERE user_id IN (?, ?, ?, ?, ?)")->execute($testUserIds);
    echo "✓ Cleanup complete\n\n";
    
    echo "=== All Tests Completed ===\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Attempt cleanup even on error
    try {
        $userDb = Database::getUserDB();
        $contentDb = Database::getContentDB();
        $userDb->prepare("DELETE FROM users WHERE id IN (?, ?, ?, ?, ?)")->execute($testUserIds);
        $contentDb->prepare("DELETE FROM alumni_profiles WHERE user_id IN (?, ?, ?, ?, ?)")->execute($testUserIds);
    } catch (Exception $cleanupError) {
        echo "Cleanup error: " . $cleanupError->getMessage() . "\n";
    }
    exit(1);
}

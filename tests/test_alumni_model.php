<?php
/**
 * Test Alumni Model
 * Tests profile CRUD operations, search, filtering, and verification
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Alumni.php';

// Test configuration
$testUserId1 = 1001;
$testUserId2 = 1002;
$testUserId3 = 1003;

echo "=== Alumni Model Test Suite ===\n\n";

try {
    // Clean up any existing test data
    $db = Database::getContentDB();
    $db->prepare("DELETE FROM alumni_profiles WHERE user_id IN (?, ?, ?)")->execute([$testUserId1, $testUserId2, $testUserId3]);
    
    // Test 1: Create Profile (Insert)
    echo "Test 1: Create Profile (Insert)\n";
    $profileData1 = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'mobile_phone' => '+49 123 456789',
        'linkedin_url' => 'https://linkedin.com/in/johndoe',
        'xing_url' => 'https://xing.com/profile/johndoe',
        'industry' => 'Technology',
        'company' => 'Tech Corp',
        'position' => 'Senior Developer',
        'image_path' => 'uploads/profiles/johndoe.jpg'
    ];
    
    $result = Alumni::updateOrCreateProfile($testUserId1, $profileData1);
    if ($result) {
        echo "✓ Profile created successfully\n\n";
    } else {
        echo "✗ Failed to create profile\n\n";
    }
    
    // Test 2: Get Profile by User ID
    echo "Test 2: Get Profile by User ID\n";
    $profile = Alumni::getProfileByUserId($testUserId1);
    if ($profile && $profile['first_name'] === 'John' && $profile['last_name'] === 'Doe') {
        echo "✓ Profile retrieved successfully\n";
        echo "  Name: {$profile['first_name']} {$profile['last_name']}\n";
        echo "  Email: {$profile['email']}\n";
        echo "  Company: {$profile['company']}\n";
        echo "  Position: {$profile['position']}\n";
        echo "  Industry: {$profile['industry']}\n\n";
    } else {
        echo "✗ Failed to retrieve profile\n\n";
    }
    
    // Test 3: Update Existing Profile (Upsert)
    echo "Test 3: Update Existing Profile (Upsert)\n";
    $updateData = [
        'position' => 'Lead Developer',
        'company' => 'Tech Corp International',
        'mobile_phone' => '+49 987 654321'
    ];
    
    $result = Alumni::updateOrCreateProfile($testUserId1, $updateData);
    if ($result) {
        $updatedProfile = Alumni::getProfileByUserId($testUserId1);
        if ($updatedProfile['position'] === 'Lead Developer' && 
            $updatedProfile['company'] === 'Tech Corp International') {
            echo "✓ Profile updated successfully\n";
            echo "  New Position: {$updatedProfile['position']}\n";
            echo "  New Company: {$updatedProfile['company']}\n";
            echo "  New Phone: {$updatedProfile['mobile_phone']}\n\n";
        } else {
            echo "✗ Update did not persist\n\n";
        }
    } else {
        echo "✗ Failed to update profile\n\n";
    }
    
    // Test 4: Create More Test Profiles
    echo "Test 4: Create Additional Test Profiles\n";
    
    $profileData2 = [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane.smith@example.com',
        'industry' => 'Finance',
        'company' => 'Finance Solutions',
        'position' => 'Financial Analyst'
    ];
    Alumni::updateOrCreateProfile($testUserId2, $profileData2);
    
    $profileData3 = [
        'first_name' => 'Bob',
        'last_name' => 'Johnson',
        'email' => 'bob.johnson@example.com',
        'industry' => 'Technology',
        'company' => 'Tech Innovations',
        'position' => 'CTO'
    ];
    Alumni::updateOrCreateProfile($testUserId3, $profileData3);
    
    echo "✓ Additional profiles created\n\n";
    
    // Test 5: Search Profiles by Name
    echo "Test 5: Search Profiles by Name\n";
    $searchResults = Alumni::searchProfiles(['search' => 'John']);
    if (count($searchResults) > 0) {
        echo "✓ Found " . count($searchResults) . " profile(s) matching 'John'\n";
        foreach ($searchResults as $result) {
            echo "  - {$result['first_name']} {$result['last_name']} ({$result['company']})\n";
        }
        echo "\n";
    } else {
        echo "✗ No profiles found\n\n";
    }
    
    // Test 6: Search Profiles by Industry
    echo "Test 6: Search Profiles by Industry\n";
    $industryResults = Alumni::searchProfiles(['industry' => 'Technology']);
    if (count($industryResults) >= 2) {
        echo "✓ Found " . count($industryResults) . " profile(s) in Technology industry\n";
        foreach ($industryResults as $result) {
            echo "  - {$result['first_name']} {$result['last_name']} ({$result['company']})\n";
        }
        echo "\n";
    } else {
        echo "✗ Expected at least 2 profiles in Technology industry\n\n";
    }
    
    // Test 7: Search Profiles by Company
    echo "Test 7: Search Profiles by Company\n";
    $companyResults = Alumni::searchProfiles(['company' => 'Tech Corp International']);
    if (count($companyResults) === 1) {
        echo "✓ Found " . count($companyResults) . " profile(s) at Tech Corp International\n";
        foreach ($companyResults as $result) {
            echo "  - {$result['first_name']} {$result['last_name']} ({$result['position']})\n";
        }
        echo "\n";
    } else {
        echo "✗ Expected 1 profile at Tech Corp International\n\n";
    }
    
    // Test 8: Combined Search Filters
    echo "Test 8: Combined Search Filters (Name + Industry)\n";
    $combinedResults = Alumni::searchProfiles([
        'search' => 'Doe',
        'industry' => 'Technology'
    ]);
    if (count($combinedResults) === 1 && $combinedResults[0]['first_name'] === 'John') {
        echo "✓ Combined filter search works correctly\n";
        echo "  Found: {$combinedResults[0]['first_name']} {$combinedResults[0]['last_name']}\n\n";
    } else {
        echo "✗ Combined filter search failed\n\n";
    }
    
    // Test 9: Get All Industries
    echo "Test 9: Get All Industries\n";
    $industries = Alumni::getAllIndustries();
    if (count($industries) >= 2) {
        echo "✓ Found " . count($industries) . " unique industries\n";
        foreach ($industries as $industry) {
            echo "  - $industry\n";
        }
        echo "\n";
    } else {
        echo "✗ Expected at least 2 industries\n\n";
    }
    
    // Test 10: Verify Profile
    echo "Test 10: Verify Profile\n";
    sleep(1); // Ensure timestamp will be different
    $verifyResult = Alumni::verifyProfile($testUserId1);
    if ($verifyResult) {
        $verifiedProfile = Alumni::getProfileByUserId($testUserId1);
        echo "✓ Profile verified successfully\n";
        echo "  Last verified at: {$verifiedProfile['last_verified_at']}\n\n";
    } else {
        echo "✗ Failed to verify profile\n\n";
    }
    
    // Test 11: Get Outdated Profiles
    echo "Test 11: Get Outdated Profiles\n";
    // Artificially set last_verified_at to old date for testing
    $db->prepare("UPDATE alumni_profiles SET last_verified_at = DATE_SUB(NOW(), INTERVAL 13 MONTH) WHERE user_id = ?")->execute([$testUserId2]);
    
    $outdatedProfiles = Alumni::getOutdatedProfiles(12);
    if (count($outdatedProfiles) >= 1) {
        echo "✓ Found " . count($outdatedProfiles) . " outdated profile(s)\n";
        foreach ($outdatedProfiles as $outdated) {
            echo "  - {$outdated['first_name']} {$outdated['last_name']} (last verified: {$outdated['last_verified_at']})\n";
        }
        echo "\n";
    } else {
        echo "✗ Expected at least 1 outdated profile\n\n";
    }
    
    // Test 12: Image Path Sanitization
    echo "Test 12: Image Path Sanitization\n";
    $maliciousPath = '../../../etc/passwd';
    $updateWithMaliciousPath = [
        'image_path' => $maliciousPath
    ];
    Alumni::updateOrCreateProfile($testUserId3, $updateWithMaliciousPath);
    $sanitizedProfile = Alumni::getProfileByUserId($testUserId3);
    
    if (!str_contains($sanitizedProfile['image_path'], '../')) {
        echo "✓ Image path sanitization works correctly\n";
        echo "  Malicious input: $maliciousPath\n";
        echo "  Sanitized output: {$sanitizedProfile['image_path']}\n\n";
    } else {
        echo "✗ Image path sanitization failed\n\n";
    }
    
    // Test 13: Missing Required Fields
    echo "Test 13: Missing Required Fields\n";
    try {
        $incompleteData = [
            'first_name' => 'Test',
            'last_name' => 'User'
            // Missing required fields: email, company, position
        ];
        Alumni::updateOrCreateProfile(9999, $incompleteData);
        echo "✗ Should have thrown exception for missing required fields\n\n";
    } catch (Exception $e) {
        echo "✓ Exception thrown for missing required fields\n";
        echo "  Error: {$e->getMessage()}\n\n";
    }
    
    // Test 14: Profile Not Found
    echo "Test 14: Profile Not Found\n";
    $nonExistentProfile = Alumni::getProfileByUserId(99999);
    if ($nonExistentProfile === false) {
        echo "✓ Returns false for non-existent profile\n\n";
    } else {
        echo "✗ Should return false for non-existent profile\n\n";
    }
    
    // Clean up test data
    echo "Cleaning up test data...\n";
    $db->prepare("DELETE FROM alumni_profiles WHERE user_id IN (?, ?, ?)")->execute([$testUserId1, $testUserId2, $testUserId3]);
    echo "✓ Test data cleaned up\n\n";
    
    echo "=== All Tests Completed ===\n";
    
} catch (Exception $e) {
    echo "✗ Test Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

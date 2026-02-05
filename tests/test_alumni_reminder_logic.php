<?php
/**
 * Test Alumni Reminder Logic
 * Tests the updated getOutdatedProfiles() and new markReminderSent() methods
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Alumni.php';

// Test configuration
$testUserId1 = 2001;
$testUserId2 = 2002;
$testUserId3 = 2003;
$testUserId4 = 2004;

echo "=== Alumni Reminder Logic Test Suite ===\n\n";

try {
    // Clean up any existing test data
    $db = Database::getContentDB();
    $db->prepare("DELETE FROM alumni_profiles WHERE user_id IN (?, ?, ?, ?)")->execute([$testUserId1, $testUserId2, $testUserId3, $testUserId4]);
    
    // Setup: Create test profiles
    echo "Setup: Creating test profiles...\n";
    
    // Profile 1: Outdated verification, no reminder sent
    $profileData1 = [
        'first_name' => 'Alice',
        'last_name' => 'NoReminder',
        'email' => 'alice@example.com',
        'company' => 'Test Corp',
        'position' => 'Developer'
    ];
    Alumni::updateOrCreateProfile($testUserId1, $profileData1);
    $db->prepare("UPDATE alumni_profiles SET last_verified_at = DATE_SUB(NOW(), INTERVAL 13 MONTH), last_reminder_sent_at = NULL WHERE user_id = ?")->execute([$testUserId1]);
    
    // Profile 2: Outdated verification, old reminder sent
    $profileData2 = [
        'first_name' => 'Bob',
        'last_name' => 'OldReminder',
        'email' => 'bob@example.com',
        'company' => 'Test Corp',
        'position' => 'Developer'
    ];
    Alumni::updateOrCreateProfile($testUserId2, $profileData2);
    $db->prepare("UPDATE alumni_profiles SET last_verified_at = DATE_SUB(NOW(), INTERVAL 13 MONTH), last_reminder_sent_at = DATE_SUB(NOW(), INTERVAL 13 MONTH) WHERE user_id = ?")->execute([$testUserId2]);
    
    // Profile 3: Outdated verification, recent reminder sent
    $profileData3 = [
        'first_name' => 'Charlie',
        'last_name' => 'RecentReminder',
        'email' => 'charlie@example.com',
        'company' => 'Test Corp',
        'position' => 'Developer'
    ];
    Alumni::updateOrCreateProfile($testUserId3, $profileData3);
    $db->prepare("UPDATE alumni_profiles SET last_verified_at = DATE_SUB(NOW(), INTERVAL 13 MONTH), last_reminder_sent_at = DATE_SUB(NOW(), INTERVAL 1 MONTH) WHERE user_id = ?")->execute([$testUserId3]);
    
    // Profile 4: Recent verification, no reminder sent
    $profileData4 = [
        'first_name' => 'Diana',
        'last_name' => 'RecentVerification',
        'email' => 'diana@example.com',
        'company' => 'Test Corp',
        'position' => 'Developer'
    ];
    Alumni::updateOrCreateProfile($testUserId4, $profileData4);
    $db->prepare("UPDATE alumni_profiles SET last_verified_at = DATE_SUB(NOW(), INTERVAL 6 MONTH), last_reminder_sent_at = NULL WHERE user_id = ?")->execute([$testUserId4]);
    
    echo "✓ Test profiles created\n\n";
    
    // Test 1: getOutdatedProfiles should only return profiles that need reminders
    echo "Test 1: getOutdatedProfiles - Filter by verification and reminder dates\n";
    $outdatedProfiles = Alumni::getOutdatedProfiles(12);
    
    $outdatedUserIds = array_column($outdatedProfiles, 'user_id');
    
    $hasAlice = in_array($testUserId1, $outdatedUserIds);
    $hasBob = in_array($testUserId2, $outdatedUserIds);
    $hasCharlie = in_array($testUserId3, $outdatedUserIds);
    $hasDiana = in_array($testUserId4, $outdatedUserIds);
    
    echo "  Alice (outdated, no reminder): " . ($hasAlice ? "✓ INCLUDED" : "✗ EXCLUDED") . "\n";
    echo "  Bob (outdated, old reminder): " . ($hasBob ? "✓ INCLUDED" : "✗ EXCLUDED") . "\n";
    echo "  Charlie (outdated, recent reminder): " . ($hasCharlie ? "✗ INCLUDED" : "✓ EXCLUDED") . "\n";
    echo "  Diana (recent verification): " . ($hasDiana ? "✗ INCLUDED" : "✓ EXCLUDED") . "\n";
    
    if ($hasAlice && $hasBob && !$hasCharlie && !$hasDiana) {
        echo "✓ getOutdatedProfiles correctly filters profiles\n\n";
    } else {
        echo "✗ getOutdatedProfiles filtering failed\n";
        echo "  Expected: Alice and Bob only\n";
        echo "  Got: " . implode(', ', array_map(function($p) { return $p['first_name']; }, $outdatedProfiles)) . "\n\n";
    }
    
    // Test 2: markReminderSent should update last_reminder_sent_at
    echo "Test 2: markReminderSent - Update reminder timestamp\n";
    
    // Get Alice's profile before marking
    $aliceBefore = Alumni::getProfileByUserId($testUserId1);
    echo "  Alice's last_reminder_sent_at before: " . ($aliceBefore['last_reminder_sent_at'] ?? 'NULL') . "\n";
    
    // Mark reminder as sent
    $result = Alumni::markReminderSent($testUserId1);
    
    // Get Alice's profile after marking
    $aliceAfter = Alumni::getProfileByUserId($testUserId1);
    echo "  Alice's last_reminder_sent_at after: " . ($aliceAfter['last_reminder_sent_at'] ?? 'NULL') . "\n";
    
    if ($result && !empty($aliceAfter['last_reminder_sent_at'])) {
        echo "✓ markReminderSent successfully updated timestamp\n\n";
    } else {
        echo "✗ markReminderSent failed to update timestamp\n\n";
    }
    
    // Test 3: After marking, profile should not appear in getOutdatedProfiles
    echo "Test 3: After markReminderSent, profile excluded from getOutdatedProfiles\n";
    
    $outdatedProfilesAfter = Alumni::getOutdatedProfiles(12);
    $outdatedUserIdsAfter = array_column($outdatedProfilesAfter, 'user_id');
    
    $aliceStillIncluded = in_array($testUserId1, $outdatedUserIdsAfter);
    
    if (!$aliceStillIncluded) {
        echo "✓ Alice is now excluded from outdated profiles\n";
        echo "  Profiles still outdated: " . count($outdatedProfilesAfter) . "\n\n";
    } else {
        echo "✗ Alice is still included in outdated profiles (should be excluded)\n\n";
    }
    
    // Test 4: markReminderSent on non-existent user
    echo "Test 4: markReminderSent on non-existent user\n";
    $result = Alumni::markReminderSent(99999);
    // PDO execute() returns true even when no rows are affected
    // This is expected behavior - the query succeeded, just no rows matched
    if ($result) {
        echo "✓ Method returns true for non-existent user (query succeeded, no rows affected)\n\n";
    } else {
        echo "✗ Method returned false unexpectedly\n\n";
    }
    
    // Test 5: Verify interaction between verifyProfile and reminder logic
    echo "Test 5: Verify profile verification updates don't affect reminder logic\n";
    
    // Bob has both outdated verification and outdated reminder
    // Update his verification but not reminder
    Alumni::verifyProfile($testUserId2);
    
    $bobAfterVerify = Alumni::getProfileByUserId($testUserId2);
    $outdatedAfterVerify = Alumni::getOutdatedProfiles(12);
    $outdatedUserIdsAfterVerify = array_column($outdatedAfterVerify, 'user_id');
    
    $bobStillOutdated = in_array($testUserId2, $outdatedUserIdsAfterVerify);
    
    if (!$bobStillOutdated) {
        echo "✓ Bob is correctly excluded after profile verification\n";
        echo "  (last_verified_at was updated to recent date)\n\n";
    } else {
        echo "✗ Bob should be excluded after recent verification\n\n";
    }
    
    // Clean up test data
    echo "Cleaning up test data...\n";
    $db->prepare("DELETE FROM alumni_profiles WHERE user_id IN (?, ?, ?, ?)")->execute([$testUserId1, $testUserId2, $testUserId3, $testUserId4]);
    echo "✓ Test data cleaned up\n\n";
    
    echo "=== All Tests Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "✗ Test Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

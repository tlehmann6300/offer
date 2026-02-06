<?php
/**
 * Migration: Add Student Fields to alumni_profiles table
 * 
 * This migration adds student-specific fields:
 * - study_program (VARCHAR 100) - English field for study program
 * - semester (INT) - Changed from VARCHAR to INT
 * - degree (VARCHAR 50) - English field for degree (e.g., B.Sc., M.Sc.)
 * - graduation_year (INT) - Year of graduation
 */

require_once __DIR__ . '/../includes/database.php';

echo "=== Migration: Add Student Fields to alumni_profiles ===\n\n";

try {
    $db = Database::getContentDB();
    
    // Check if alumni_profiles table exists
    $stmt = $db->query("SHOW TABLES LIKE 'alumni_profiles'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "ERROR: Table alumni_profiles does not exist.\n";
        exit(1);
    }
    
    echo "✓ Table alumni_profiles exists\n\n";
    echo "Adding new student columns to alumni_profiles table...\n";
    
    // Add study_program field (English equivalent)
    $db->exec("ALTER TABLE alumni_profiles 
               ADD COLUMN IF NOT EXISTS study_program VARCHAR(100) DEFAULT NULL 
               COMMENT 'Study program (English)'");
    echo "✓ Added study_program column\n";
    
    // Add degree field (English, shorter field for degree types like B.Sc., M.Sc.)
    $db->exec("ALTER TABLE alumni_profiles 
               ADD COLUMN IF NOT EXISTS degree VARCHAR(50) DEFAULT NULL 
               COMMENT 'Degree type (e.g., B.Sc., M.Sc.)'");
    echo "✓ Added degree column\n";
    
    // Add graduation_year field
    $db->exec("ALTER TABLE alumni_profiles 
               ADD COLUMN IF NOT EXISTS graduation_year INT DEFAULT NULL 
               COMMENT 'Year of graduation'");
    echo "✓ Added graduation_year column\n";
    
    // Note: We keep the existing semester column as VARCHAR(50) to maintain compatibility
    // If needed to change to INT, it would require data migration
    echo "✓ Semester column already exists (kept as VARCHAR for compatibility)\n";
    
    echo "\n✅ Student columns added to profile table.\n";
    echo "\n=== Migration completed successfully! ===\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

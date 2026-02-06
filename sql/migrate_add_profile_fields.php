<?php
/**
 * Migration: Add Profile Fields to alumni_profiles table
 * 
 * This migration adds:
 * - studiengang (for candidates/members)
 * - semester (for candidates/members)
 * - angestrebter_abschluss (for candidates/members)
 * - about_me (for all users)
 * 
 * And makes company and position nullable for candidates/members
 */

require_once __DIR__ . '/../includes/database.php';

echo "=== Migration: Add Profile Fields to alumni_profiles ===\n\n";

try {
    $db = Database::getContentDB();
    
    echo "Adding new columns to alumni_profiles table...\n";
    
    // Add studiengang field for candidates/members
    $db->exec("ALTER TABLE alumni_profiles 
               ADD COLUMN IF NOT EXISTS studiengang VARCHAR(255) DEFAULT NULL 
               COMMENT 'Field of study for candidates and members'");
    echo "✓ Added studiengang column\n";
    
    // Add semester field for candidates/members
    $db->exec("ALTER TABLE alumni_profiles 
               ADD COLUMN IF NOT EXISTS semester VARCHAR(50) DEFAULT NULL 
               COMMENT 'Current semester for candidates and members'");
    echo "✓ Added semester column\n";
    
    // Add angestrebter_abschluss field for candidates/members
    $db->exec("ALTER TABLE alumni_profiles 
               ADD COLUMN IF NOT EXISTS angestrebter_abschluss VARCHAR(255) DEFAULT NULL 
               COMMENT 'Desired degree for candidates and members'");
    echo "✓ Added angestrebter_abschluss column\n";
    
    // Add about_me field for all users
    $db->exec("ALTER TABLE alumni_profiles 
               ADD COLUMN IF NOT EXISTS about_me TEXT DEFAULT NULL 
               COMMENT 'Personal description/bio for all users'");
    echo "✓ Added about_me column\n";
    
    // Make company nullable for candidates/members (they may not have a company yet)
    $db->exec("ALTER TABLE alumni_profiles 
               MODIFY COLUMN company VARCHAR(255) DEFAULT NULL 
               COMMENT 'Company name - required for alumni, optional for candidates/members'");
    echo "✓ Made company column nullable\n";
    
    // Make position nullable for candidates/members
    $db->exec("ALTER TABLE alumni_profiles 
               MODIFY COLUMN position VARCHAR(255) DEFAULT NULL 
               COMMENT 'Job position - required for alumni, optional for candidates/members'");
    echo "✓ Made position column nullable\n";
    
    echo "\n=== Migration completed successfully! ===\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

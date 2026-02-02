<?php
/**
 * Setup Admin Script
 * This script creates the initial admin user for deployment
 * 
 * IMPORTANT: Run this script only once, then DELETE it for security!
 */

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';

// Admin user details
$adminEmail = 'tom.lehmann@business-consulting.de';
$adminPassword = 'Tomi#2004';
$adminRole = 'admin';

try {
    // Connect to User Database
    $db = Database::getUserDB();
    
    // Check if admin user already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "⚠️ Admin-Benutzer existiert bereits. Keine Änderungen vorgenommen.\n";
        echo "Bitte Datei löschen.\n";
        exit(0);
    }
    
    // Hash password with ARGON2ID
    $passwordHash = password_hash($adminPassword, PASSWORD_ARGON2ID);
    
    // Insert admin user
    $stmt = $db->prepare("
        INSERT INTO users (
            email, 
            password_hash, 
            role, 
            tfa_enabled, 
            is_alumni_validated
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        $adminEmail,
        $passwordHash,
        $adminRole,
        0, // tfa_enabled: initial aus
        1  // is_alumni_validated: 1
    ]);
    
    if ($success) {
        echo "✅ Admin angelegt. Bitte Datei löschen.\n";
        echo "\n";
        echo "Details:\n";
        echo "- Email: " . $adminEmail . "\n";
        echo "- Rolle: " . $adminRole . "\n";
        echo "- TFA: Deaktiviert\n";
        echo "- Alumni Validiert: Ja\n";
        echo "\n";
        echo "🔒 WICHTIG: Löschen Sie diese Datei sofort aus Sicherheitsgründen!\n";
    } else {
        echo "❌ Fehler beim Anlegen des Admin-Benutzers.\n";
        exit(1);
    }
    
} catch (PDOException $e) {
    echo "❌ Datenbankfehler: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    exit(1);
}

<?php
/**
 * Setup Admin Script (Fixed Database Method)
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

if (file_exists(__DIR__ . '/src/Database.php')) {
    require_once __DIR__ . '/src/Database.php';
} elseif (file_exists(__DIR__ . '/includes/database.php')) {
    require_once __DIR__ . '/includes/database.php';
} else {
    die("❌ Fehler: Database.php nicht gefunden.");
}

$adminEmail = 'tom.lehmann@business-consulting.de';
$adminPassword = 'Tomi#2004';
$adminRole = 'admin';
$firstName = 'Tom';
$lastName = 'Lehmann';

echo "<h1>Admin Setup</h1>";

try {
    // FIX: Wir nutzen direkt getUserDB(), da diese Methode in deinem Projekt existiert
    if (method_exists('Database', 'getUserDB')) {
        $db = Database::getUserDB();
    } else {
        throw new Exception("Die Methode Database::getUserDB() wurde nicht gefunden. Bitte prüfe deine Database.php");
    }

    // --- SCHRITT 1: User prüfen/erstellen ---
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        echo "<p>⚠️ User existiert bereits (ID: " . $existingUser['id'] . ").</p>";
        $userId = $existingUser['id'];
    } else {
        $passwordHash = password_hash($adminPassword, PASSWORD_ARGON2ID);
        
        // Spaltenname 'password_hash' laut deinem SQL Schema
        $sqlUser = "INSERT INTO users (email, password_hash, role, is_alumni_validated) 
                    VALUES (?, ?, ?, 1)";
        
        $stmt = $db->prepare($sqlUser);
        $stmt->execute([$adminEmail, $passwordHash, $adminRole]);
        
        $userId = $db->lastInsertId();
        echo "<p>✅ User erfolgreich angelegt (ID: $userId).</p>";
    }

    // --- SCHRITT 2: Profil prüfen/erstellen ---
    if ($userId) {
        $stmt = $db->prepare("SELECT id FROM alumni_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        if ($stmt->fetch()) {
            echo "<p>⚠️ Profil existiert bereits.</p>";
        } else {
            $sqlProfile = "INSERT INTO alumni_profiles (
                user_id, first_name, last_name, email, company, position, industry
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($sqlProfile);
            $stmt->execute([
                $userId, 
                $firstName, 
                $lastName, 
                $adminEmail, 
                'IBC e.V.', 
                'Administrator', 
                'Vereinsmanagement'
            ]);
            
            echo "<p>✅ Profil erfolgreich verknüpft.</p>";
        }
    }

    echo "<hr><p style='color: green;'>🎉 Setup erfolgreich!</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
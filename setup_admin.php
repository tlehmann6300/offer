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
$adminRole = 'board';
$firstName = 'Tom';
$lastName = 'Lehmann';

echo "<h1>Admin Setup</h1>";

try {
    // Get separate database connections for user and content databases
    $dbUser = Database::getConnection('user');
    $dbContent = Database::getConnection('content');

    // Safety check: Ensure alumni_profiles table exists in content database
    $createTableSQL = "CREATE TABLE IF NOT EXISTS alumni_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        email VARCHAR(100),
        company VARCHAR(100),
        position VARCHAR(100),
        industry VARCHAR(100),
        image_path VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $dbContent->exec($createTableSQL);

    // --- SCHRITT 1: User prüfen/erstellen ---
    $stmt = $dbUser->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        echo "<p>⚠️ User existiert bereits (ID: " . $existingUser['id'] . ").</p>";
        $userId = $existingUser['id'];
    } else {
        $passwordHash = password_hash($adminPassword, PASSWORD_ARGON2ID);
        
        // Column name 'password' according to database schema
        $sqlUser = "INSERT INTO users (email, password, role, is_alumni_validated) 
                    VALUES (?, ?, ?, 1)";
        
        $stmt = $dbUser->prepare($sqlUser);
        $stmt->execute([$adminEmail, $passwordHash, $adminRole]);
        
        $userId = $dbUser->lastInsertId();
        echo "<p>✅ User erfolgreich angelegt (ID: $userId).</p>";
    }

    // --- SCHRITT 2: Profil prüfen/erstellen ---
    if ($userId) {
        $stmt = $dbContent->prepare("SELECT id FROM alumni_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        if ($stmt->fetch()) {
            echo "<p>⚠️ Profil existiert bereits.</p>";
        } else {
            $sqlProfile = "INSERT INTO alumni_profiles (
                user_id, first_name, last_name, email, company, position, industry
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $dbContent->prepare($sqlProfile);
            $stmt->execute([
                $userId, 
                $firstName, 
                $lastName, 
                $adminEmail, 
                'IBC e.V.', 
                'Vorstand', 
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
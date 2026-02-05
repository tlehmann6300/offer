<?php
/**
 * Production System Reset Script
 * Completely resets databases and creates fresh admin account
 * WARNING: This destroys all existing data!
 */

// Security gate - require specific access parameter
if (!isset($_GET['secure_key']) || $_GET['secure_key'] !== 'MakeItNew2024') {
    die('Access Denied');
}

// Load environment configuration
require_once __DIR__ . '/config/config.php';

// Track operation outcomes
$operationResults = [];

// HTML output styling
$htmlStyles = '
<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .results-container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #4CAF50; color: white; }
    .status-success { color: #4CAF50; font-weight: bold; }
    .status-fail { color: #f44336; font-weight: bold; }
    tr:hover { background-color: #f5f5f5; }
</style>
';

echo '<!DOCTYPE html><html><head><title>System Reset</title>' . $htmlStyles . '</head><body>';
echo '<div class="results-container"><h1>🔄 Production System Reset</h1>';

// Step 1: Connect to MySQL server (without database selection)
try {
    $userServerConn = new PDO(
        "mysql:host=" . DB_USER_HOST . ";charset=utf8mb4",
        DB_USER_USER,
        DB_USER_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $operationResults[] = ['operation' => 'Connected to User DB Server', 'outcome' => 'Success'];
} catch (PDOException $error) {
    $operationResults[] = ['operation' => 'Connected to User DB Server', 'outcome' => 'Failed: ' . $error->getMessage()];
    displayResultsTable($operationResults);
    die();
}

try {
    $contentServerConn = new PDO(
        "mysql:host=" . DB_CONTENT_HOST . ";charset=utf8mb4",
        DB_CONTENT_USER,
        DB_CONTENT_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $operationResults[] = ['operation' => 'Connected to Content DB Server', 'outcome' => 'Success'];
} catch (PDOException $error) {
    $operationResults[] = ['operation' => 'Connected to Content DB Server', 'outcome' => 'Failed: ' . $error->getMessage()];
    displayResultsTable($operationResults);
    die();
}

// Step 2: Drop existing databases
try {
    $userServerConn->exec("DROP DATABASE IF EXISTS " . DB_USER_NAME);
    $operationResults[] = ['operation' => 'Dropped User Database', 'outcome' => 'Success'];
} catch (PDOException $error) {
    $operationResults[] = ['operation' => 'Dropped User Database', 'outcome' => 'Failed: ' . $error->getMessage()];
}

try {
    $contentServerConn->exec("DROP DATABASE IF EXISTS " . DB_CONTENT_NAME);
    $operationResults[] = ['operation' => 'Dropped Content Database', 'outcome' => 'Success'];
} catch (PDOException $error) {
    $operationResults[] = ['operation' => 'Dropped Content Database', 'outcome' => 'Failed: ' . $error->getMessage()];
}

// Step 3: Create fresh databases
try {
    $userServerConn->exec("CREATE DATABASE " . DB_USER_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $operationResults[] = ['operation' => 'Created User Database', 'outcome' => 'Success'];
} catch (PDOException $error) {
    $operationResults[] = ['operation' => 'Created User Database', 'outcome' => 'Failed: ' . $error->getMessage()];
    displayResultsTable($operationResults);
    die();
}

try {
    $contentServerConn->exec("CREATE DATABASE " . DB_CONTENT_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $operationResults[] = ['operation' => 'Created Content Database', 'outcome' => 'Success'];
} catch (PDOException $error) {
    $operationResults[] = ['operation' => 'Created Content Database', 'outcome' => 'Failed: ' . $error->getMessage()];
    displayResultsTable($operationResults);
    die();
}

// Step 4: Reconnect to specific databases for schema import
$userServerConn = null;
$contentServerConn = null;

try {
    $userDbConn = new PDO(
        "mysql:host=" . DB_USER_HOST . ";dbname=" . DB_USER_NAME . ";charset=utf8mb4",
        DB_USER_USER,
        DB_USER_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $error) {
    $operationResults[] = ['operation' => 'Reconnected to User DB', 'outcome' => 'Failed: ' . $error->getMessage()];
    displayResultsTable($operationResults);
    die();
}

try {
    $contentDbConn = new PDO(
        "mysql:host=" . DB_CONTENT_HOST . ";dbname=" . DB_CONTENT_NAME . ";charset=utf8mb4",
        DB_CONTENT_USER,
        DB_CONTENT_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $error) {
    $operationResults[] = ['operation' => 'Reconnected to Content DB', 'outcome' => 'Failed: ' . $error->getMessage()];
    displayResultsTable($operationResults);
    die();
}

// Step 5: Import User DB schema
$userSchemaPath = __DIR__ . '/sql/full_user_schema.sql';
if (!file_exists($userSchemaPath)) {
    $operationResults[] = ['operation' => 'Import User Schema', 'outcome' => 'Failed: Schema file not found'];
} else {
    $userSchemaContent = file_get_contents($userSchemaPath);
    try {
        $userDbConn->exec($userSchemaContent);
        $operationResults[] = ['operation' => 'Import User Schema', 'outcome' => 'Success'];
    } catch (PDOException $error) {
        $operationResults[] = ['operation' => 'Import User Schema', 'outcome' => 'Failed: ' . $error->getMessage()];
        displayResultsTable($operationResults);
        die();
    }
}

// Step 6: Import Content DB schema
$contentSchemaPath = __DIR__ . '/sql/full_content_schema.sql';
if (!file_exists($contentSchemaPath)) {
    $operationResults[] = ['operation' => 'Import Content Schema', 'outcome' => 'Failed: Schema file not found'];
} else {
    $contentSchemaContent = file_get_contents($contentSchemaPath);
    try {
        $contentDbConn->exec($contentSchemaContent);
        $operationResults[] = ['operation' => 'Import Content Schema', 'outcome' => 'Success'];
    } catch (PDOException $error) {
        $operationResults[] = ['operation' => 'Import Content Schema', 'outcome' => 'Failed: ' . $error->getMessage()];
        displayResultsTable($operationResults);
        die();
    }
}

// Step 7: Create super admin account
$superAdminEmail = 'admin@ibc-intranet.de';
$superAdminPass = 'Admin123!';
$superAdminRole = 'admin';

try {
    $hashedPassword = password_hash($superAdminPass, PASSWORD_ARGON2ID);
    
    $insertQuery = $userDbConn->prepare(
        "INSERT INTO users (email, password, role, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())"
    );
    
    $insertQuery->execute([$superAdminEmail, $hashedPassword, $superAdminRole]);
    
    $operationResults[] = ['operation' => 'Created Admin Account', 'outcome' => 'Success (Email: ' . $superAdminEmail . ')'];
} catch (PDOException $error) {
    $operationResults[] = ['operation' => 'Created Admin Account', 'outcome' => 'Failed: ' . $error->getMessage()];
}

// Display all results
displayResultsTable($operationResults);

echo '</div></body></html>';

/**
 * Helper function to render results table
 */
function displayResultsTable($results) {
    echo '<table>';
    echo '<thead><tr><th>Operation</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($results as $result) {
        $statusClass = (strpos($result['outcome'], 'Success') !== false) ? 'status-success' : 'status-fail';
        echo '<tr>';
        echo '<td>' . htmlspecialchars($result['operation']) . '</td>';
        echo '<td class="' . $statusClass . '">' . htmlspecialchars($result['outcome']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
}

<?php
/**
 * Database Reconstruction System
 * DANGER: Destroys all data and rebuilds from scratch
 * DELETE IMMEDIATELY AFTER RUNNING
 * Required: ?secure_key=MakeItNew2024
 */

($_GET['secure_key'] ?? '') === 'MakeItNew2024' or die('Access Denied');

require_once __DIR__ . '/config/config.php';

class SystemRebuilder {
    private $actionLog = [];
    private $pdoOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    
    public function execute() {
        $this->renderPageStart();
        
        $connections = $this->establishServerLinks();
        if (!$connections) return;
        
        $this->purgeExistingDatabases($connections);
        $this->buildFreshDatabases($connections);
        
        $dbLinks = $this->reconnectToDatabases();
        if (!$dbLinks) return;
        
        $this->loadSchemaFiles($dbLinks);
        $this->seedAdministrator($dbLinks['users']);
        
        $this->renderActionLog();
        $this->renderPageEnd();
    }
    
    private function establishServerLinks() {
        $userLink = $this->connectToServer(DB_USER_HOST, DB_USER_USER, DB_USER_PASS, 'User Server');
        $contentLink = $this->connectToServer(DB_CONTENT_HOST, DB_CONTENT_USER, DB_CONTENT_PASS, 'Content Server');
        
        return ($userLink && $contentLink) ? ['user' => $userLink, 'content' => $contentLink] : null;
    }
    
    private function connectToServer($hostname, $username, $credential, $label) {
        try {
            $link = new PDO("mysql:host={$hostname};charset=utf8mb4", $username, $credential, $this->pdoOptions);
            $this->logAction("Connect {$label}", true);
            return $link;
        } catch (PDOException $ex) {
            error_log("{$label} link failed: " . $ex->getMessage());
            $this->logAction("Connect {$label}", false);
            $this->renderActionLog();
            $this->renderPageEnd();
            exit;
        }
    }
    
    private function purgeExistingDatabases($links) {
        $this->executeSQL($links['user'], "DROP DATABASE IF EXISTS " . DB_USER_NAME, 'Remove User DB');
        $this->executeSQL($links['content'], "DROP DATABASE IF EXISTS " . DB_CONTENT_NAME, 'Remove Content DB');
    }
    
    private function buildFreshDatabases($links) {
        $charset = " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        
        if (!$this->executeSQL($links['user'], "CREATE DATABASE " . DB_USER_NAME . $charset, 'Build User DB')) {
            $this->renderActionLog();
            $this->renderPageEnd();
            exit;
        }
        
        if (!$this->executeSQL($links['content'], "CREATE DATABASE " . DB_CONTENT_NAME . $charset, 'Build Content DB')) {
            $this->renderActionLog();
            $this->renderPageEnd();
            exit;
        }
    }
    
    private function reconnectToDatabases() {
        $userDb = $this->connectWithDatabase(DB_USER_HOST, DB_USER_NAME, DB_USER_USER, DB_USER_PASS, 'User DB');
        $contentDb = $this->connectWithDatabase(DB_CONTENT_HOST, DB_CONTENT_NAME, DB_CONTENT_USER, DB_CONTENT_PASS, 'Content DB');
        
        return ($userDb && $contentDb) ? ['users' => $userDb, 'content' => $contentDb] : null;
    }
    
    private function connectWithDatabase($host, $dbname, $user, $pass, $label) {
        try {
            $conn = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, $this->pdoOptions);
            $this->logAction("Attach {$label}", true);
            return $conn;
        } catch (PDOException $ex) {
            error_log("{$label} attach failed: " . $ex->getMessage());
            $this->logAction("Attach {$label}", false);
            $this->renderActionLog();
            $this->renderPageEnd();
            exit;
        }
    }
    
    private function loadSchemaFiles($databases) {
        $userSqlFile = __DIR__ . '/sql/full_user_schema.sql';
        $contentSqlFile = __DIR__ . '/sql/full_content_schema.sql';
        
        if (is_file($userSqlFile)) {
            $sqlStatements = file_get_contents($userSqlFile);
            $this->executeSQL($databases['users'], $sqlStatements, 'Load User Tables', true);
        } else {
            $this->logAction('Load User Tables', false, 'File missing');
        }
        
        if (is_file($contentSqlFile)) {
            $sqlStatements = file_get_contents($contentSqlFile);
            $this->executeSQL($databases['content'], $sqlStatements, 'Load Content Tables', true);
        } else {
            $this->logAction('Load Content Tables', false, 'File missing');
        }
    }
    
    private function seedAdministrator($userDatabase) {
        $adminCreds = [
            'email' => 'admin@ibc-intranet.de',
            'plaintext' => 'Admin123!',
            'privilege' => 'admin'
        ];
        
        $encryptedPass = password_hash($adminCreds['plaintext'], PASSWORD_ARGON2ID);
        
        try {
            $stmt = $userDatabase->prepare(
                "INSERT INTO users (email, password, role, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([$adminCreds['email'], $encryptedPass, $adminCreds['privilege']]);
            $this->logAction('Seed Administrator', true);
        } catch (PDOException $ex) {
            error_log('Admin seed failed: ' . $ex->getMessage());
            $this->logAction('Seed Administrator', false);
        }
    }
    
    private function executeSQL($connection, $query, $description, $critical = false) {
        try {
            $connection->exec($query);
            $this->logAction($description, true);
            return true;
        } catch (PDOException $ex) {
            error_log("{$description} error: " . $ex->getMessage());
            $this->logAction($description, false);
            return !$critical;
        }
    }
    
    private function logAction($action, $success, $note = '') {
        $this->actionLog[] = [
            'task' => $action,
            'success' => $success,
            'message' => $note
        ];
    }
    
    private function renderPageStart() {
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>System Rebuild</title>';
        echo '<style>';
        echo 'body{margin:0;padding:2rem;font:14px/1.6 system-ui,sans-serif;background:#f8f9fa}';
        echo '.wrapper{max-width:900px;margin:0 auto;background:#fff;padding:2rem;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,.07)}';
        echo 'h1{color:#2c3e50;margin:0 0 1.5rem;padding-bottom:1rem;border-bottom:4px solid #27ae60}';
        echo 'table{width:100%;border-collapse:collapse;margin-top:1.5rem}';
        echo 'th,td{text-align:left;padding:1rem;border-bottom:1px solid #e9ecef}';
        echo 'th{background:#27ae60;color:#fff;font-weight:600}';
        echo 'tr:hover{background:#f8f9fa}';
        echo '.ok{color:#27ae60;font-weight:600}';
        echo '.fail{color:#e74c3c;font-weight:600}';
        echo '</style></head><body><div class="wrapper"><h1>🔄 System Reconstruction</h1>';
    }
    
    private function renderActionLog() {
        echo '<table><thead><tr><th>Task</th><th>Result</th></tr></thead><tbody>';
        
        foreach ($this->actionLog as $entry) {
            $styling = $entry['success'] ? 'ok' : 'fail';
            $status = $entry['success'] ? 'Success' : ($entry['message'] ?: 'Failed');
            
            echo '<tr>';
            echo '<td>' . htmlspecialchars($entry['task']) . '</td>';
            echo '<td class="' . $styling . '">' . htmlspecialchars($status) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    private function renderPageEnd() {
        echo '</div></body></html>';
    }
}

$rebuilder = new SystemRebuilder();
$rebuilder->execute();

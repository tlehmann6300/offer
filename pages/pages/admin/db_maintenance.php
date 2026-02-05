<?php
/**
 * Database Maintenance Tool
 * Admin page for database cleanup and maintenance
 * Only accessible by admin and board members
 */

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

// Check if user has admin or board permission
if (!Auth::check() || !Auth::hasPermission('board')) {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';
$error = '';
$actionResult = [];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['clean_logs'])) {
            // Clean old logs
            $userDb = Database::getUserDB();
            $contentDb = Database::getContentDB();
            
            // Delete user_sessions older than 30 days
            $stmt = $userDb->prepare("DELETE FROM user_sessions WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute();
            $sessionsDeleted = $stmt->rowCount();
            
            // Delete system_logs older than 1 year
            $stmt = $contentDb->prepare("DELETE FROM system_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
            $stmt->execute();
            $systemLogsDeleted = $stmt->rowCount();
            
            // Delete inventory_history older than 1 year
            $stmt = $contentDb->prepare("DELETE FROM inventory_history WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
            $stmt->execute();
            $inventoryHistoryDeleted = $stmt->rowCount();
            
            // Delete event_history older than 1 year
            $stmt = $contentDb->prepare("DELETE FROM event_history WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
            $stmt->execute();
            $eventHistoryDeleted = $stmt->rowCount();
            
            $actionResult = [
                'type' => 'success',
                'title' => 'Logs bereinigt',
                'details' => [
                    "User Sessions gelöscht: $sessionsDeleted",
                    "System Logs gelöscht: $systemLogsDeleted",
                    "Inventory History gelöscht: $inventoryHistoryDeleted",
                    "Event History gelöscht: $eventHistoryDeleted"
                ]
            ];
            
            // Log the action
            $stmt = $contentDb->prepare("INSERT INTO system_logs (user_id, action, details, ip_address, timestamp) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $_SESSION['user_id'],
                'cleanup_logs',
                json_encode($actionResult['details']),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
        } elseif (isset($_POST['clear_cache'])) {
            // Clear cache folder
            $cacheDir = __DIR__ . '/../../cache';
            $filesDeleted = 0;
            $spaceFreed = 0;
            
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $spaceFreed += filesize($file);
                        if (unlink($file)) {
                            $filesDeleted++;
                        }
                    }
                }
            }
            
            $actionResult = [
                'type' => 'success',
                'title' => 'Cache geleert',
                'details' => [
                    "Dateien gelöscht: $filesDeleted",
                    "Speicherplatz freigegeben: " . formatBytes($spaceFreed)
                ]
            ];
            
            // Log the action
            $contentDb = Database::getContentDB();
            $stmt = $contentDb->prepare("INSERT INTO system_logs (user_id, action, details, ip_address, timestamp) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $_SESSION['user_id'],
                'clear_cache',
                json_encode($actionResult['details']),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
    } catch (Exception $e) {
        $actionResult = [
            'type' => 'error',
            'title' => 'Fehler',
            'details' => [$e->getMessage()]
        ];
    }
}

/**
 * Format bytes to human-readable size
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Get database table sizes
 */
function getTableSizes() {
    try {
        $userDb = Database::getUserDB();
        $contentDb = Database::getContentDB();
        
        $tables = [];
        
        // Get user database tables
        $stmt = $userDb->prepare("
            SELECT 
                table_name as 'table',
                ROUND((data_length + index_length) / 1024 / 1024, 2) as 'size_mb',
                table_rows as 'rows'
            FROM information_schema.TABLES 
            WHERE table_schema = ?
            ORDER BY (data_length + index_length) DESC
        ");
        $stmt->execute([DB_USER_NAME]);
        $userTables = $stmt->fetchAll();
        
        // Get content database tables
        $stmt = $contentDb->prepare("
            SELECT 
                table_name as 'table',
                ROUND((data_length + index_length) / 1024 / 1024, 2) as 'size_mb',
                table_rows as 'rows'
            FROM information_schema.TABLES 
            WHERE table_schema = ?
            ORDER BY (data_length + index_length) DESC
        ");
        $stmt->execute([DB_CONTENT_NAME]);
        $contentTables = $stmt->fetchAll();
        
        return [
            'user' => $userTables,
            'content' => $contentTables
        ];
    } catch (Exception $e) {
        return [
            'user' => [],
            'content' => [],
            'error' => $e->getMessage()
        ];
    }
}

$tableSizes = getTableSizes();

// Calculate totals
$userDbTotal = array_sum(array_column($tableSizes['user'], 'size_mb'));
$contentDbTotal = array_sum(array_column($tableSizes['content'], 'size_mb'));
$totalSize = $userDbTotal + $contentDbTotal;

$title = 'Datenbank-Wartung - IBC Intranet';
ob_start();
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">
        <i class="fas fa-database text-blue-600 mr-2"></i>
        Datenbank-Wartung
    </h1>
    <p class="text-gray-600">Verwalten Sie Speicherplatz und bereinigen Sie alte Daten</p>
</div>

<?php if (!empty($actionResult)): ?>
<div class="mb-6 p-4 rounded-lg border <?php echo $actionResult['type'] === 'success' ? 'bg-green-50 border-green-400 text-green-700' : 'bg-red-50 border-red-400 text-red-700'; ?>">
    <h3 class="font-semibold mb-2">
        <i class="fas fa-<?php echo $actionResult['type'] === 'success' ? 'check' : 'exclamation'; ?>-circle mr-2"></i>
        <?php echo htmlspecialchars($actionResult['title']); ?>
    </h3>
    <ul class="list-disc list-inside ml-4">
        <?php foreach ($actionResult['details'] as $detail): ?>
        <li><?php echo htmlspecialchars($detail); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Database Overview -->
<div class="card p-6 mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">
        <i class="fas fa-chart-pie text-purple-600 mr-2"></i>
        Datenbank-Übersicht
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg">
            <p class="text-blue-800 font-semibold text-sm">User Database</p>
            <p class="text-2xl font-bold text-blue-600"><?php echo number_format($userDbTotal, 2); ?> MB</p>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
            <p class="text-green-800 font-semibold text-sm">Content Database</p>
            <p class="text-2xl font-bold text-green-600"><?php echo number_format($contentDbTotal, 2); ?> MB</p>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg">
            <p class="text-purple-800 font-semibold text-sm">Gesamt</p>
            <p class="text-2xl font-bold text-purple-600"><?php echo number_format($totalSize, 2); ?> MB</p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- User Database Tables -->
        <div>
            <h3 class="text-lg font-semibold text-gray-700 mb-3">User Database Tabellen</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tabelle</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Zeilen</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Größe</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($tableSizes['user'] as $table): ?>
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($table['table']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-600 text-right"><?php echo number_format($table['rows']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-600 text-right"><?php echo number_format($table['size_mb'], 2); ?> MB</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Content Database Tables -->
        <div>
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Content Database Tabellen</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tabelle</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Zeilen</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Größe</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($tableSizes['content'] as $table): ?>
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($table['table']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-600 text-right"><?php echo number_format($table['rows']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-600 text-right"><?php echo number_format($table['size_mb'], 2); ?> MB</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Actions -->
<div class="card p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">
        <i class="fas fa-tools text-orange-600 mr-2"></i>
        Wartungsaktionen
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Clean Logs -->
        <div class="border border-gray-200 rounded-lg p-4">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                    <i class="fas fa-broom text-yellow-600 mr-2"></i>
                    Logs bereinigen
                </h3>
                <p class="text-sm text-gray-600 mb-3">
                    Löscht alte Log-Einträge zur Freigabe von Speicherplatz:
                </p>
                <ul class="list-disc list-inside text-sm text-gray-600 space-y-1 ml-4">
                    <li>User Sessions älter als 30 Tage</li>
                    <li>System Logs älter als 1 Jahr</li>
                    <li>Inventory History älter als 1 Jahr</li>
                    <li>Event History älter als 1 Jahr</li>
                </ul>
            </div>
            <form method="POST" onsubmit="return confirm('Möchten Sie wirklich alte Logs löschen? Diese Aktion kann nicht rückgängig gemacht werden.');">
                <button type="submit" name="clean_logs" class="w-full btn-primary flex items-center justify-center">
                    <i class="fas fa-trash-alt mr-2"></i>
                    Logs bereinigen
                </button>
            </form>
        </div>
        
        <!-- Clear Cache -->
        <div class="border border-gray-200 rounded-lg p-4">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                    <i class="fas fa-sync-alt text-blue-600 mr-2"></i>
                    Cache leeren
                </h3>
                <p class="text-sm text-gray-600 mb-3">
                    Löscht temporäre Cache-Dateien:
                </p>
                <ul class="list-disc list-inside text-sm text-gray-600 space-y-1 ml-4">
                    <li>Alle Dateien im cache/ Ordner</li>
                    <li>Gibt Speicherplatz frei</li>
                    <li>Beeinflusst keine Datenbanken</li>
                </ul>
            </div>
            <form method="POST" onsubmit="return confirm('Möchten Sie wirklich den Cache leeren?');">
                <button type="submit" name="clear_cache" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition flex items-center justify-center">
                    <i class="fas fa-eraser mr-2"></i>
                    Cache leeren
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Warning Notice -->
<div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
    <p class="text-yellow-800 text-sm">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>Hinweis:</strong> Wartungsaktionen können nicht rückgängig gemacht werden. Stellen Sie sicher, dass Sie vor dem Bereinigen wichtiger Daten ein Backup erstellt haben.
    </p>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
?>

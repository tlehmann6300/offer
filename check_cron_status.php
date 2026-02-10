<?php
/**
 * Cron Job Status Checker
 * 
 * This script checks when cron jobs were last executed by querying the system_logs table.
 * Can be accessed via browser to monitor cron job health.
 * 
 * Usage: Access via browser: https://your-domain.com/check_cron_status.php
 */

// Load required files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database.php';

// Start output buffering for clean HTML
ob_start();

// Get database connection
try {
    $contentDb = Database::getContentDB();
} catch (Exception $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}

// Define cron jobs to monitor
$cronJobs = [
    'cron_birthday_wishes' => [
        'name' => 'Geburtstagswünsche',
        'description' => 'Sendet Geburtstagswünsche an Benutzer',
        'expected_interval' => 'Täglich um 9:00 Uhr',
        'action' => 'cron_birthday_wishes'
    ],
    'cron_alumni_reminders' => [
        'name' => 'Alumni Erinnerungen',
        'description' => 'Sendet Erinnerungen an Alumni zur Profil-Verifizierung',
        'expected_interval' => 'Wöchentlich, Montags um 10:00 Uhr',
        'action' => 'cron_alumni_reminders'
    ],
    'cron_easyverein_sync' => [
        'name' => 'EasyVerein Synchronisation',
        'description' => 'Synchronisiert Inventardaten von EasyVerein',
        'expected_interval' => 'Alle 30 Minuten',
        'action' => 'cron_easyverein_sync'
    ]
];

// Function to get last execution time for a cron job
function getLastExecution($db, $action) {
    try {
        $stmt = $db->prepare("
            SELECT 
                timestamp,
                details,
                id
            FROM system_logs 
            WHERE action = :action 
            ORDER BY timestamp DESC 
            LIMIT 1
        ");
        $stmt->execute(['action' => $action]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

// Function to calculate time difference in human-readable format
function getTimeDifference($timestamp) {
    if (!$timestamp) {
        return 'Nie ausgeführt';
    }
    
    $now = new DateTime();
    $past = new DateTime($timestamp);
    $diff = $now->diff($past);
    
    if ($diff->days > 0) {
        return $diff->days . ' Tag(e) ' . $diff->h . ' Stunde(n) her';
    } elseif ($diff->h > 0) {
        return $diff->h . ' Stunde(n) ' . $diff->i . ' Minute(n) her';
    } elseif ($diff->i > 0) {
        return $diff->i . ' Minute(n) her';
    } else {
        return $diff->s . ' Sekunde(n) her';
    }
}

// Function to determine status color based on time since last run
function getStatusColor($action, $timestamp) {
    if (!$timestamp) {
        return 'red'; // Never run
    }
    
    $now = new DateTime();
    $past = new DateTime($timestamp);
    $diff = $now->diff($past);
    $minutesSince = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    
    // Define thresholds based on cron job type
    switch ($action) {
        case 'cron_birthday_wishes':
            // Should run daily - warn after 36 hours
            return ($minutesSince > 2160) ? 'red' : 'green';
            
        case 'cron_alumni_reminders':
            // Should run weekly - warn after 10 days
            return ($minutesSince > 14400) ? 'red' : 'green';
            
        case 'cron_easyverein_sync':
            // Should run every 30 minutes - warn after 90 minutes
            return ($minutesSince > 90) ? 'red' : 'green';
            
        default:
            return 'gray';
    }
}

// Collect status information for all cron jobs
$statusData = [];
foreach ($cronJobs as $key => $job) {
    $lastRun = getLastExecution($contentDb, $job['action']);
    $timestamp = $lastRun ? $lastRun['timestamp'] : null;
    $details = $lastRun ? $lastRun['details'] : null;
    
    $statusData[] = [
        'name' => $job['name'],
        'description' => $job['description'],
        'expected_interval' => $job['expected_interval'],
        'last_run' => $timestamp,
        'time_ago' => getTimeDifference($timestamp),
        'status_color' => getStatusColor($job['action'], $timestamp),
        'details' => $details
    ];
}

// Get current time
$currentTime = date('Y-m-d H:i:s');

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cron Job Status</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header .timestamp {
            color: #666;
            font-size: 14px;
        }
        
        .cron-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .cron-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        
        .cron-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .cron-card h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }
        
        .status-indicator.green {
            background-color: #10b981;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
        }
        
        .status-indicator.red {
            background-color: #ef4444;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
        }
        
        .status-indicator.gray {
            background-color: #6b7280;
        }
        
        .cron-card .description {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #6b7280;
            font-size: 14px;
            font-weight: 600;
        }
        
        .info-value {
            color: #333;
            font-size: 14px;
            text-align: right;
        }
        
        .info-value.success {
            color: #10b981;
            font-weight: 600;
        }
        
        .info-value.error {
            color: #ef4444;
            font-weight: 600;
        }
        
        .details {
            margin-top: 15px;
            padding: 10px;
            background: #f9fafb;
            border-radius: 5px;
            font-size: 12px;
            color: #666;
            word-wrap: break-word;
        }
        
        .footer {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .refresh-button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
            transition: background 0.2s;
        }
        
        .refresh-button:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🕐 Cron Job Status Monitor</h1>
            <p class="timestamp">Letzter Check: <?php echo htmlspecialchars($currentTime); ?></p>
            <button class="refresh-button" onclick="location.reload()">🔄 Aktualisieren</button>
        </div>
        
        <div class="cron-grid">
            <?php foreach ($statusData as $job): ?>
            <div class="cron-card">
                <h2>
                    <span class="status-indicator <?php echo $job['status_color']; ?>"></span>
                    <?php echo htmlspecialchars($job['name']); ?>
                </h2>
                <p class="description"><?php echo htmlspecialchars($job['description']); ?></p>
                
                <div class="info-row">
                    <span class="info-label">Erwartetes Intervall:</span>
                    <span class="info-value"><?php echo htmlspecialchars($job['expected_interval']); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Letzte Ausführung:</span>
                    <span class="info-value <?php echo $job['last_run'] ? 'success' : 'error'; ?>">
                        <?php echo $job['last_run'] ? htmlspecialchars($job['last_run']) : 'Nie'; ?>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Zeitdifferenz:</span>
                    <span class="info-value <?php echo $job['last_run'] ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($job['time_ago']); ?>
                    </span>
                </div>
                
                <?php if ($job['details']): ?>
                <div class="details">
                    <strong>Details:</strong><br>
                    <?php echo nl2br(htmlspecialchars($job['details'])); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="footer">
            <p>📄 Weitere Informationen zur Einrichtung finden Sie in der <a href="CRON_SETUP.md" target="_blank" rel="noopener noreferrer">CRON_SETUP.md</a></p>
        </div>
    </div>
</body>
</html>

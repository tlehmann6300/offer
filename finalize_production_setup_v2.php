<?php
/**
 * Production Setup Finalization Script v2
 * 
 * This script provides a comprehensive deployment interface for setting up
 * the production databases using the 3 distinct SQL files:
 * - dbs15253086.sql (User DB)
 * - dbs15161271.sql (Main Content DB)
 * - dbs15251284.sql (Invoice DB)
 * 
 * Usage: Navigate to https://your-domain.de/finalize_production_setup_v2.php
 * 
 * IMPORTANT: Delete this file after successful deployment for security!
 */

// Set execution time limit
set_time_limit(300);

// Require database configuration
require_once __DIR__ . '/includes/database.php';

// Define the SQL files to deploy
$sqlFiles = [
    'dbs15253086' => [
        'name' => 'dbs15253086.sql',
        'path' => __DIR__ . '/sql/dbs15253086.sql',
        'description' => 'User Database',
        'tables' => ['users', 'user_invitations', 'email_change_requests'],
        'connection' => 'user_db'
    ],
    'dbs15161271' => [
        'name' => 'dbs15161271.sql',
        'path' => __DIR__ . '/sql/dbs15161271.sql',
        'description' => 'Main Content Database',
        'tables' => ['alumni_profiles', 'projects', 'project_files', 'inventory_items', 
                     'inventory_transactions', 'events', 'event_registrations', 'blog_posts'],
        'connection' => 'content_db'
    ],
    'dbs15251284' => [
        'name' => 'dbs15251284.sql',
        'path' => __DIR__ . '/sql/dbs15251284.sql',
        'description' => 'Invoice Database',
        'tables' => ['invoices'],
        'connection' => 'invoice_db'
    ]
];

// Initialize results storage
$deploymentResults = [];
$hasErrors = false;

// Process deployment if form is submitted
$deployRequested = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deploy'])) {
    $deployRequested = true;
    
    foreach ($sqlFiles as $dbKey => $fileInfo) {
        $result = [
            'name' => $fileInfo['name'],
            'description' => $fileInfo['description'],
            'status' => '',
            'message' => '',
            'tables_created' => [],
            'icon' => ''
        ];
        
        // Check if file exists
        if (!file_exists($fileInfo['path'])) {
            $result['status'] = 'ERROR';
            $result['icon'] = '❌';
            $result['message'] = 'SQL file not found: ' . $fileInfo['path'];
            $hasErrors = true;
            $deploymentResults[] = $result;
            continue;
        }
        
        // Read SQL file content
        $sqlContent = file_get_contents($fileInfo['path']);
        if ($sqlContent === false) {
            $result['status'] = 'ERROR';
            $result['icon'] = '❌';
            $result['message'] = 'Failed to read SQL file';
            $hasErrors = true;
            $deploymentResults[] = $result;
            continue;
        }
        
        // Get the appropriate database connection
        try {
            $conn = null;
            switch ($fileInfo['connection']) {
                case 'user_db':
                    $conn = getUserDB();
                    break;
                case 'content_db':
                    $conn = getContentDB();
                    break;
                case 'invoice_db':
                    $conn = getInvoiceDB();
                    break;
                default:
                    throw new Exception('Unknown database connection: ' . $fileInfo['connection']);
            }
            
            // Execute SQL statements
            // Split by semicolons but preserve semicolons in strings
            $statements = [];
            $currentStatement = '';
            $inString = false;
            $stringChar = '';
            
            for ($i = 0; $i < strlen($sqlContent); $i++) {
                $char = $sqlContent[$i];
                
                // Handle string literals
                if (($char === '"' || $char === "'") && ($i === 0 || $sqlContent[$i-1] !== '\\')) {
                    if (!$inString) {
                        $inString = true;
                        $stringChar = $char;
                    } elseif ($char === $stringChar) {
                        $inString = false;
                    }
                }
                
                $currentStatement .= $char;
                
                // Split on semicolons outside of strings
                if ($char === ';' && !$inString) {
                    $stmt = trim($currentStatement);
                    if (!empty($stmt) && strpos($stmt, '--') !== 0) {
                        $statements[] = $stmt;
                    }
                    $currentStatement = '';
                }
            }
            
            // Add the last statement if it exists
            $stmt = trim($currentStatement);
            if (!empty($stmt) && strpos($stmt, '--') !== 0) {
                $statements[] = $stmt;
            }
            
            // Execute each statement
            $tablesCreated = [];
            foreach ($statements as $statement) {
                // Skip comments and empty statements
                $cleanStmt = trim($statement);
                if (empty($cleanStmt) || strpos($cleanStmt, '--') === 0) {
                    continue;
                }
                
                // Execute the statement
                if (!$conn->query($statement)) {
                    throw new Exception('SQL Error: ' . $conn->error . ' in statement: ' . substr($statement, 0, 100));
                }
                
                // Track table creation
                if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                    $tablesCreated[] = $matches[1];
                }
            }
            
            $result['status'] = 'SUCCESS';
            $result['icon'] = '✅';
            $result['message'] = 'Successfully deployed ' . count($statements) . ' SQL statements';
            $result['tables_created'] = $tablesCreated;
            
        } catch (Exception $e) {
            $result['status'] = 'ERROR';
            $result['icon'] = '❌';
            $result['message'] = 'Deployment failed: ' . $e->getMessage();
            $hasErrors = true;
        }
        
        $deploymentResults[] = $result;
    }
}

// Verify database connections
$connectionStatus = [];
try {
    $userDB = getUserDB();
    $connectionStatus['user_db'] = [
        'name' => 'User Database (dbs15253086)',
        'status' => $userDB ? 'Connected' : 'Failed',
        'icon' => $userDB ? '✅' : '❌'
    ];
} catch (Exception $e) {
    $connectionStatus['user_db'] = [
        'name' => 'User Database (dbs15253086)',
        'status' => 'Error: ' . $e->getMessage(),
        'icon' => '❌'
    ];
}

try {
    $contentDB = getContentDB();
    $connectionStatus['content_db'] = [
        'name' => 'Content Database (dbs15161271)',
        'status' => $contentDB ? 'Connected' : 'Failed',
        'icon' => $contentDB ? '✅' : '❌'
    ];
} catch (Exception $e) {
    $connectionStatus['content_db'] = [
        'name' => 'Content Database (dbs15161271)',
        'status' => 'Error: ' . $e->getMessage(),
        'icon' => '❌'
    ];
}

try {
    $invoiceDB = getInvoiceDB();
    $connectionStatus['invoice_db'] = [
        'name' => 'Invoice Database (dbs15251284)',
        'status' => $invoiceDB ? 'Connected' : 'Failed',
        'icon' => $invoiceDB ? '✅' : '❌'
    ];
} catch (Exception $e) {
    $connectionStatus['invoice_db'] = [
        'name' => 'Invoice Database (dbs15251284)',
        'status' => 'Error: ' . $e->getMessage(),
        'icon' => '❌'
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Setup v2 - IBC Intranet</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section h2 {
            color: #333;
            font-size: 1.8em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .info-box h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.3em;
        }
        
        .info-box ul {
            list-style: none;
            padding-left: 0;
        }
        
        .info-box ul li {
            padding: 5px 0;
            color: #555;
        }
        
        .info-box ul li:before {
            content: "▸ ";
            color: #667eea;
            font-weight: bold;
            margin-right: 5px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .status-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .status-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .status-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.2em;
        }
        
        .status-card .status {
            display: flex;
            align-items: center;
            font-size: 1.1em;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .status .icon {
            margin-right: 10px;
            font-size: 1.5em;
        }
        
        .deploy-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 18px 40px;
            font-size: 1.2em;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            font-weight: 600;
            display: block;
            margin: 30px auto;
        }
        
        .deploy-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .deploy-button:active {
            transform: translateY(0);
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .results-table th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .results-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .results-table tr:last-child td {
            border-bottom: none;
        }
        
        .results-table tr:hover {
            background: #f8f9fa;
        }
        
        .success-banner {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
            font-size: 1.2em;
            font-weight: 600;
        }
        
        .error-banner {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
            font-size: 1.2em;
            font-weight: 600;
        }
        
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .warning strong {
            color: #856404;
        }
        
        .tables-list {
            margin-top: 10px;
            padding-left: 20px;
            font-size: 0.9em;
            color: #666;
        }
        
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Production Setup v2</h1>
            <p>Master Deployment Script for Production Databases</p>
        </div>
        
        <div class="content">
            <?php if ($deployRequested): ?>
                <?php if (!$hasErrors): ?>
                    <div class="success-banner">
                        ✅ Deployment completed successfully!
                    </div>
                <?php else: ?>
                    <div class="error-banner">
                        ❌ Deployment completed with errors. Please review the results below.
                    </div>
                <?php endif; ?>
                
                <div class="section">
                    <h2>Deployment Results</h2>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Database</th>
                                <th>File</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deploymentResults as $result): ?>
                            <tr>
                                <td><?php echo $result['icon']; ?></td>
                                <td><strong><?php echo htmlspecialchars($result['description']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($result['name']); ?></code></td>
                                <td>
                                    <?php echo htmlspecialchars($result['message']); ?>
                                    <?php if (!empty($result['tables_created'])): ?>
                                        <div class="tables-list">
                                            Tables: <?php echo implode(', ', $result['tables_created']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="warning">
                    <strong>⚠️ Security Notice:</strong> Please delete this file (<code>finalize_production_setup_v2.php</code>) immediately after successful deployment for security reasons!
                </div>
            <?php else: ?>
                <div class="section">
                    <h2>Database Connection Status</h2>
                    <div class="status-grid">
                        <?php foreach ($connectionStatus as $conn): ?>
                        <div class="status-card">
                            <h3><?php echo htmlspecialchars($conn['name']); ?></h3>
                            <div class="status">
                                <span class="icon"><?php echo $conn['icon']; ?></span>
                                <span><?php echo htmlspecialchars($conn['status']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="section">
                    <h2>SQL Files to Deploy</h2>
                    <?php foreach ($sqlFiles as $fileInfo): ?>
                    <div class="info-box">
                        <h3><?php echo htmlspecialchars($fileInfo['description']); ?> - <code><?php echo htmlspecialchars($fileInfo['name']); ?></code></h3>
                        <ul>
                            <?php foreach ($fileInfo['tables'] as $table): ?>
                            <li><?php echo htmlspecialchars($table); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="warning">
                    <strong>⚠️ Warning:</strong> This operation will execute SQL statements on your production databases. 
                    Make sure you have backed up your databases before proceeding. This action cannot be undone!
                </div>
                
                <form method="POST" onsubmit="return confirm('Are you sure you want to deploy all SQL files to production databases? This action cannot be undone!');">
                    <button type="submit" name="deploy" value="1" class="deploy-button">
                        🚀 Deploy All SQL Files
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

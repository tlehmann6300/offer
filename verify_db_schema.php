<?php
/**
 * Database Schema Verification Script
 * Run this to verify that all required database columns exist
 * 
 * Usage: Navigate to https://your-domain.de/verify_db_schema.php
 */

require_once __DIR__ . '/includes/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Schema Verification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #00a651;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .info {
            background: #d1ecf1;
            border-left-color: #0c5460;
            color: #0c5460;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .check-icon {
            color: #28a745;
            font-weight: bold;
        }
        .cross-icon {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Schema Verification</h1>
        
        <?php
        try {
            $db = Database::getContentDB();
            
            echo '<div class="status success">✓ Database connection successful</div>';
            
            // Check events table columns
            $stmt = $db->prepare("
                SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'events'
                ORDER BY ORDINAL_POSITION
            ");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($columns)) {
                echo '<div class="status error">✗ Events table does not exist!</div>';
            } else {
                $columnNames = array_column($columns, 'COLUMN_NAME');
                
                // Required columns for new features
                $requiredColumns = [
                    'maps_link' => 'Google Maps link for event location',
                    'registration_start' => 'Event registration start date/time',
                    'registration_end' => 'Event registration end date/time',
                    'image_path' => 'Path to event image upload'
                ];
                
                echo '<h2>Required Columns Check</h2>';
                echo '<table>';
                echo '<tr><th>Column</th><th>Status</th><th>Description</th></tr>';
                
                $allPresent = true;
                foreach ($requiredColumns as $col => $desc) {
                    $exists = in_array($col, $columnNames);
                    $icon = $exists ? '<span class="check-icon">✓</span>' : '<span class="cross-icon">✗</span>';
                    $status = $exists ? 'Present' : 'MISSING';
                    echo "<tr>";
                    echo "<td><strong>{$col}</strong></td>";
                    echo "<td>{$icon} {$status}</td>";
                    echo "<td>{$desc}</td>";
                    echo "</tr>";
                    
                    if (!$exists) {
                        $allPresent = false;
                    }
                }
                echo '</table>';
                
                if ($allPresent) {
                    echo '<div class="status success">';
                    echo '<strong>✓ All required columns are present!</strong><br>';
                    echo 'Your database schema is up to date.';
                    echo '</div>';
                } else {
                    echo '<div class="status error">';
                    echo '<strong>✗ Missing required columns!</strong><br>';
                    echo 'Please run the migration script to add missing columns:<br>';
                    echo '→ <a href="/sql/migrate_add_event_fields.php">Run Migration Script</a>';
                    echo '</div>';
                }
                
                // Check uploads directory
                echo '<h2>Upload Directory Check</h2>';
                $uploadsDir = __DIR__ . '/uploads/events';
                $uploadsDirExists = is_dir($uploadsDir);
                $uploadsWritable = $uploadsDirExists && is_writable($uploadsDir);
                
                if ($uploadsDirExists && $uploadsWritable) {
                    echo '<div class="status success">✓ Upload directory exists and is writable</div>';
                } elseif ($uploadsDirExists && !$uploadsWritable) {
                    echo '<div class="status warning">⚠ Upload directory exists but is NOT writable<br>';
                    echo 'Please set permissions: <code>chmod 755 ' . $uploadsDir . '</code></div>';
                } else {
                    echo '<div class="status error">✗ Upload directory does not exist<br>';
                    echo 'Please create: <code>' . $uploadsDir . '</code></div>';
                }
                
                // Show all columns for reference
                echo '<h2>All Event Table Columns</h2>';
                echo '<details>';
                echo '<summary style="cursor: pointer; color: #0066b3; font-weight: 600;">Click to expand full column list</summary>';
                echo '<table style="margin-top: 10px;">';
                echo '<tr><th>Column</th><th>Type</th><th>Nullable</th><th>Default</th></tr>';
                foreach ($columns as $col) {
                    echo "<tr>";
                    echo "<td>{$col['COLUMN_NAME']}</td>";
                    echo "<td>{$col['COLUMN_TYPE']}</td>";
                    echo "<td>{$col['IS_NULLABLE']}</td>";
                    echo "<td>" . ($col['COLUMN_DEFAULT'] ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
                echo '</table>';
                echo '</details>';
            }
            
        } catch (Exception $e) {
            echo '<div class="status error">';
            echo '<strong>✗ Error:</strong> ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
        
        <div class="status info" style="margin-top: 30px;">
            <strong>ℹ Next Steps:</strong><br>
            1. If columns are missing, run the migration script<br>
            2. Ensure the uploads directory has proper permissions<br>
            3. Delete this verification script from production for security
        </div>
    </div>
</body>
</html>

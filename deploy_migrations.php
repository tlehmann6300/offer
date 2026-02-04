<?php
/**
 * Deploy Migrations Script
 * Executes database migration and helper scripts
 * 
 * This script:
 * - Runs migration scripts in order
 * - Captures output from each script
 * - Displays results in a clear HTML table
 * - Handles errors gracefully
 */

// Set execution time limit
set_time_limit(300);

// Define the migration scripts to execute
$scripts = [
    'fix_event_db.php',
    'fix_event_image.php',
    'migrate_projects.php',
    'sql/migrate_add_event_fields.php',
    'verify_db_schema.php'
];

// Store execution results
$results = [];

// Execute each script
foreach ($scripts as $script) {
    $scriptPath = __DIR__ . '/' . $script;
    $result = [
        'name' => $script,
        'status' => '',
        'output' => '',
        'icon' => ''
    ];
    
    // Check if file exists
    if (!file_exists($scriptPath)) {
        $result['status'] = 'Nicht gefunden';
        $result['icon'] = '⚠️';
        $result['output'] = 'Datei existiert nicht: ' . $scriptPath;
        $results[] = $result;
        continue;
    }
    
    // Execute the script with output buffering
    // Use a subprocess to avoid exit() calls terminating the main script
    try {
        ob_start();
        
        // Capture output from the included script
        // Note: Some scripts may call exit() which would normally terminate execution
        // We handle this by checking the output for error indicators
        $outputFile = tempnam(sys_get_temp_dir(), 'migration_');
        $errorFile = tempnam(sys_get_temp_dir(), 'migration_err_');
        
        // Run script in subprocess to isolate exit() calls
        $cmd = sprintf(
            'php %s > %s 2> %s',
            escapeshellarg($scriptPath),
            escapeshellarg($outputFile),
            escapeshellarg($errorFile)
        );
        
        exec($cmd, $execOutput, $returnCode);
        
        $output = file_get_contents($outputFile);
        $errorOutput = file_get_contents($errorFile);
        
        // Clean up temp files
        unlink($outputFile);
        unlink($errorFile);
        
        ob_end_clean();
        
        // Determine status based on return code and output
        if ($returnCode === 0 && (strpos($output, 'Fehler') === false || strpos($output, 'erfolgreich') !== false)) {
            $result['status'] = 'Erfolg';
            $result['icon'] = '✅';
            $result['output'] = $output;
        } else {
            $result['status'] = 'Fehler';
            $result['icon'] = '❌';
            $result['output'] = $output . ($errorOutput ? "\n\nErrors:\n" . $errorOutput : '');
        }
    } catch (Error $e) {
        ob_end_clean();
        $result['status'] = 'Fehler';
        $result['icon'] = '❌';
        $result['output'] = "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
    } catch (Exception $e) {
        ob_end_clean();
        $result['status'] = 'Fehler';
        $result['icon'] = '❌';
        $result['output'] = "Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString();
    }
    
    $results[] = $result;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deploy Migrations - IBC Intranet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-database text-blue-600 mr-2"></i>
                    Deploy Migrations
                </h1>
                <p class="text-gray-600">
                    Automatische Ausführung aller Datenbankmigrationen und Hilfsskripte
                </p>
            </div>
            
            <!-- Results Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-300">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-300">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Skript-Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-32">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                Server-Antwort
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($results as $result): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <i class="fas fa-file-code text-gray-500 mr-2"></i>
                                    <?php echo htmlspecialchars($result['name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold
                                    <?php 
                                    if ($result['status'] === 'Erfolg') {
                                        echo 'bg-green-100 text-green-800';
                                    } elseif ($result['status'] === 'Fehler') {
                                        echo 'bg-red-100 text-red-800';
                                    } else {
                                        echo 'bg-yellow-100 text-yellow-800';
                                    }
                                    ?>">
                                    <?php echo $result['icon'] . ' ' . htmlspecialchars($result['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-700">
                                    <details class="cursor-pointer">
                                        <summary class="font-medium hover:text-blue-600">
                                            <i class="fas fa-chevron-right mr-1"></i>
                                            Ausgabe anzeigen
                                        </summary>
                                        <pre class="mt-2 p-3 bg-gray-50 rounded border border-gray-200 text-xs overflow-x-auto"><?php echo htmlspecialchars($result['output']); ?></pre>
                                    </details>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary -->
            <div class="mt-6 p-4 bg-blue-50 border-l-4 border-blue-400 rounded">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            Migration abgeschlossen
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>
                                <?php
                                $successCount = count(array_filter($results, function($r) { return $r['status'] === 'Erfolg'; }));
                                $errorCount = count(array_filter($results, function($r) { return $r['status'] === 'Fehler'; }));
                                $notFoundCount = count(array_filter($results, function($r) { return $r['status'] === 'Nicht gefunden'; }));
                                
                                echo "Erfolgreich: <strong>$successCount</strong> | ";
                                echo "Fehler: <strong>$errorCount</strong> | ";
                                echo "Nicht gefunden: <strong>$notFoundCount</strong>";
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Admin Setup Button -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-user-shield text-gray-500 mr-2"></i>
                        Noch keinen Admin-Benutzer angelegt?
                    </div>
                    <a href="setup_admin.php" 
                       class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <i class="fas fa-user-plus mr-2"></i>
                        Admin-Setup starten
                    </a>
                </div>
            </div>
            
            <!-- Security Warning -->
            <div class="mt-6 p-4 bg-red-50 border-l-4 border-red-400 rounded">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">
                            Sicherheitshinweis
                        </h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>
                                Bitte löschen Sie diese Datei nach erfolgreichem Deployment aus Sicherheitsgründen!
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

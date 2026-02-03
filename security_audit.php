<?php
/**
 * Security Audit Script
 * 
 * Prüft, ob sensible Installationsdateien noch auf dem Server existieren
 * und gibt Warnungen aus, wenn diese nicht gelöscht wurden.
 * 
 * Diese Datei kann in das Admin-Dashboard eingebunden werden.
 */

class SecurityAudit {
    
    /**
     * Liste der sensiblen Dateien, die nach der Installation gelöscht werden sollten
     */
    private static $sensitiveFiles = [
        'setup_admin.php' => 'Admin-Setup-Skript',
        'create_admin.php' => 'Admin-Erstellungsskript',
        'cleanup_final.php' => 'Finales Cleanup-Skript',
        'cleanup_structure.php' => 'Struktur-Cleanup-Skript',
        'cleanup_system.php' => 'System-Cleanup-Skript',
        'debug_paths.php' => 'Debug-Pfad-Skript',
        'fix_event_db.php' => 'Event-DB-Fix-Skript',
        'verify_db_schema.php' => 'DB-Schema-Verifikationsskript',
    ];
    
    /**
     * Liste der sensiblen Verzeichnisse, die nach der Installation gelöscht werden sollten
     */
    private static $sensitiveDirectories = [
        'sql/migrations/' => 'SQL-Migrations-Verzeichnis',
    ];
    
    /**
     * Liste der sensiblen Dateien im sql-Verzeichnis
     */
    private static $sqlFiles = [
        'sql/migrate_add_event_fields.php' => 'Event-Felder-Migrationsskript',
    ];
    
    /**
     * Führt den Security Audit durch und gibt ein Array mit den Ergebnissen zurück
     * 
     * @param string $baseDir Das Basisverzeichnis (Standard: __DIR__)
     * @return array Array mit 'warnings' und 'is_secure'
     */
    public static function performAudit($baseDir = null) {
        if ($baseDir === null) {
            $baseDir = __DIR__;
        }
        
        $warnings = [];
        
        // Prüfe sensible Dateien im Root-Verzeichnis
        foreach (self::$sensitiveFiles as $file => $description) {
            $filePath = $baseDir . '/' . $file;
            if (file_exists($filePath)) {
                $warnings[] = [
                    'type' => 'file',
                    'path' => $file,
                    'description' => $description,
                    'severity' => 'high',
                    'message' => "Die Datei '{$file}' ({$description}) sollte gelöscht werden."
                ];
            }
        }
        
        // Prüfe sensible Verzeichnisse
        foreach (self::$sensitiveDirectories as $dir => $description) {
            $dirPath = $baseDir . '/' . $dir;
            if (is_dir($dirPath)) {
                $warnings[] = [
                    'type' => 'directory',
                    'path' => $dir,
                    'description' => $description,
                    'severity' => 'high',
                    'message' => "Das Verzeichnis '{$dir}' ({$description}) sollte gelöscht werden."
                ];
            }
        }
        
        // Prüfe SQL-Dateien
        foreach (self::$sqlFiles as $file => $description) {
            $filePath = $baseDir . '/' . $file;
            if (file_exists($filePath)) {
                $warnings[] = [
                    'type' => 'file',
                    'path' => $file,
                    'description' => $description,
                    'severity' => 'medium',
                    'message' => "Die Datei '{$file}' ({$description}) sollte gelöscht werden."
                ];
            }
        }
        
        return [
            'warnings' => $warnings,
            'is_secure' => empty($warnings),
            'checked_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Gibt eine HTML-Warnung für das Dashboard zurück
     * 
     * @param string $baseDir Das Basisverzeichnis (Standard: __DIR__)
     * @return string HTML-Code für die Warnung oder leerer String wenn alles sicher ist
     */
    public static function getDashboardWarning($baseDir = null) {
        $audit = self::performAudit($baseDir);
        
        if ($audit['is_secure']) {
            return '';
        }
        
        $html = '<div class="mb-8 p-6 bg-red-50 border-l-4 border-red-500 rounded-lg">';
        $html .= '<div class="flex items-start">';
        $html .= '<div class="flex-shrink-0">';
        $html .= '<div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">';
        $html .= '<i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="ml-4 flex-1">';
        $html .= '<h3 class="text-lg font-bold text-red-800 mb-2">';
        $html .= '<i class="fas fa-shield-alt mr-2"></i>Sicherheitswarnung: Sensible Installationsdateien gefunden';
        $html .= '</h3>';
        $html .= '<p class="text-red-700 mb-4">';
        $html .= 'Die folgenden sensiblen Dateien oder Verzeichnisse sollten aus Sicherheitsgründen vom Server gelöscht werden:';
        $html .= '</p>';
        $html .= '<ul class="space-y-2">';
        
        foreach ($audit['warnings'] as $warning) {
            $severityColor = $warning['severity'] === 'high' ? 'red' : 'orange';
            $severityIcon = $warning['severity'] === 'high' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle';
            $severityText = $warning['severity'] === 'high' ? 'Hoch' : 'Mittel';
            
            $html .= '<li class="flex items-start">';
            $html .= '<span class="flex-shrink-0 mr-2">';
            $html .= '<i class="fas ' . $severityIcon . ' text-' . $severityColor . '-600"></i>';
            $html .= '</span>';
            $html .= '<div class="flex-1">';
            $html .= '<span class="font-mono text-sm bg-white px-2 py-1 rounded border border-' . $severityColor . '-200">';
            $html .= htmlspecialchars($warning['path']);
            $html .= '</span>';
            $html .= ' <span class="text-xs text-' . $severityColor . '-600 font-semibold">[' . $severityText . ']</span>';
            $html .= '<span class="text-sm text-gray-700 ml-2">— ' . htmlspecialchars($warning['description']) . '</span>';
            $html .= '</div>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '<div class="mt-4 p-4 bg-white rounded-lg border border-red-200">';
        $html .= '<p class="text-sm text-gray-700">';
        $html .= '<strong>Empfohlene Maßnahme:</strong> Löschen Sie diese Dateien manuell vom Server oder verwenden Sie ein Deployment-Skript, das diese automatisch entfernt.';
        $html .= '</p>';
        $html .= '<p class="text-xs text-gray-600 mt-2">';
        $html .= 'Geprüft am: ' . htmlspecialchars($audit['checked_at']);
        $html .= '</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Gibt eine detaillierte Warnung als Array zurück (z.B. für JSON-API)
     * 
     * @param string $baseDir Das Basisverzeichnis (Standard: __DIR__)
     * @return array Audit-Ergebnisse
     */
    public static function getAuditResults($baseDir = null) {
        return self::performAudit($baseDir);
    }
}

// Wenn das Skript direkt aufgerufen wird, zeige die Ergebnisse als JSON
if (php_sapi_name() === 'cli' || (isset($_SERVER['SCRIPT_FILENAME']) && basename($_SERVER['SCRIPT_FILENAME']) === 'security_audit.php')) {
    header('Content-Type: application/json');
    echo json_encode(SecurityAudit::getAuditResults(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

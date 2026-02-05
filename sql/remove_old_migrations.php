<?php
/**
 * Database Migration Cleanup Utility
 * Removes obsolete migration artifacts after schema consolidation
 */

final class MigrationCleanup {
    private array $removedItems = [];
    private array $errorLog = [];
    
    public function execute(): void {
        $this->displayHeader();
        $this->purgeObsoleteFiles();
        $this->generateReport();
    }
    
    private function displayHeader(): void {
        echo str_repeat('=', 60) . PHP_EOL;
        echo "Database Migration Cleanup Tool" . PHP_EOL;
        echo str_repeat('=', 60) . PHP_EOL . PHP_EOL;
    }
    
    private function purgeObsoleteFiles(): void {
        $rootDirectory = dirname(__DIR__);
        
        // Remove migration directory
        $this->removeMigrationDirectory($rootDirectory . '/sql/migrations');
        
        // Remove PHP migration scripts in sql directory
        $this->removePhpMigrations($rootDirectory . '/sql');
        
        // Remove apply scripts in root
        $this->removeApplyScripts($rootDirectory);
    }
    
    private function removeMigrationDirectory(string $path): void {
        if (!is_dir($path)) {
            $this->logWarning("Directory not found: {$path}");
            return;
        }
        
        echo "Processing migration directory: {$path}" . PHP_EOL;
        
        try {
            $this->recursiveDelete($path);
            $this->removedItems[] = $path;
            echo "✓ Successfully removed migrations directory" . PHP_EOL . PHP_EOL;
        } catch (Exception $err) {
            $this->errorLog[] = "Failed to remove {$path}: {$err->getMessage()}";
            echo "✗ Error removing directory: {$err->getMessage()}" . PHP_EOL . PHP_EOL;
        }
    }
    
    private function recursiveDelete(string $target): void {
        if (!file_exists($target)) {
            return;
        }
        
        if (is_file($target)) {
            if (!unlink($target)) {
                throw new RuntimeException("Cannot delete file: {$target}");
            }
            return;
        }
        
        $entries = scandir($target);
        if ($entries === false) {
            throw new RuntimeException("Cannot read directory: {$target}");
        }
        
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            
            $fullPath = $target . DIRECTORY_SEPARATOR . $entry;
            $this->recursiveDelete($fullPath);
        }
        
        if (!rmdir($target)) {
            throw new RuntimeException("Cannot remove directory: {$target}");
        }
    }
    
    private function removePhpMigrations(string $directory): void {
        echo "Scanning for PHP migration scripts in: {$directory}" . PHP_EOL;
        
        $pattern = $directory . '/migrate_*.php';
        $matches = glob($pattern);
        
        if (empty($matches)) {
            echo "No migration scripts found" . PHP_EOL . PHP_EOL;
            return;
        }
        
        foreach ($matches as $scriptFile) {
            $this->deleteFile($scriptFile);
        }
        
        echo PHP_EOL;
    }
    
    private function removeApplyScripts(string $directory): void {
        echo "Scanning for apply scripts in: {$directory}" . PHP_EOL;
        
        $pattern = $directory . '/apply_*.php';
        $matches = glob($pattern);
        
        if (empty($matches)) {
            echo "No apply scripts found" . PHP_EOL . PHP_EOL;
            return;
        }
        
        foreach ($matches as $scriptFile) {
            $this->deleteFile($scriptFile);
        }
        
        echo PHP_EOL;
    }
    
    private function deleteFile(string $filepath): void {
        $filename = basename($filepath);
        
        try {
            if (!file_exists($filepath)) {
                $this->logWarning("File not found: {$filename}");
                return;
            }
            
            if (!is_writable($filepath)) {
                throw new RuntimeException("File not writable: {$filename}");
            }
            
            if (unlink($filepath)) {
                $this->removedItems[] = $filepath;
                echo "  ✓ Removed: {$filename}" . PHP_EOL;
            } else {
                throw new RuntimeException("Failed to delete: {$filename}");
            }
        } catch (Exception $err) {
            $this->errorLog[] = "Error with {$filename}: {$err->getMessage()}";
            echo "  ✗ Error: {$filename} - {$err->getMessage()}" . PHP_EOL;
        }
    }
    
    private function logWarning(string $message): void {
        echo "  ⚠ Warning: {$message}" . PHP_EOL;
    }
    
    private function generateReport(): void {
        echo str_repeat('=', 60) . PHP_EOL;
        echo "Cleanup Summary" . PHP_EOL;
        echo str_repeat('=', 60) . PHP_EOL;
        
        $totalRemoved = count($this->removedItems);
        $totalErrors = count($this->errorLog);
        
        echo "Items removed: {$totalRemoved}" . PHP_EOL;
        echo "Errors encountered: {$totalErrors}" . PHP_EOL . PHP_EOL;
        
        if ($totalErrors > 0) {
            echo "Error Details:" . PHP_EOL;
            foreach ($this->errorLog as $error) {
                echo "  • {$error}" . PHP_EOL;
            }
            echo PHP_EOL;
        }
        
        if ($totalRemoved > 0) {
            echo "✓ Cleanup completed successfully" . PHP_EOL;
        } else {
            echo "⚠ No items were removed" . PHP_EOL;
        }
    }
}

// Execute cleanup
try {
    $cleanup = new MigrationCleanup();
    $cleanup->execute();
    exit(0);
} catch (Throwable $err) {
    echo PHP_EOL . "FATAL ERROR: {$err->getMessage()}" . PHP_EOL;
    echo "Stack trace:" . PHP_EOL . $err->getTraceAsString() . PHP_EOL;
    exit(1);
}

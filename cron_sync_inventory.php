<?php
/**
 * EasyVerein Inventory Sync Cron Script
 * 
 * Synchronizes inventory data from easyVerein API to local database.
 * Runs as a cron job and outputs text logs only.
 * 
 * Usage: php cron_sync_inventory.php
 */

// Load required files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/MailService.php';

// Configuration
define('EASYVEREIN_API_URL', 'https://easyverein.com/api/v2.0/inventory-object?limit=200');
define('EASYVEREIN_API_TOKEN', '0277d541c6bb7044e901a8a985ea74a9894df724');
define('ALERT_EMAIL', 'tlehmann630@gmail.com');

// Output start message
echo "=== EasyVerein Inventory Sync ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Step 1: Connect to database
    echo "Step 1: Connecting to database...\n";
    $db = Database::getContentDB();
    echo "  ✓ Database connection established\n\n";
    
    // Step 2: Query easyVerein API
    echo "Step 2: Fetching inventory from easyVerein API...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, EASYVEREIN_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Token ' . EASYVEREIN_API_TOKEN,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Check for curl errors
    if ($response === false) {
        throw new Exception("CURL error: " . $curlError);
    }
    
    echo "  API Response Code: {$httpCode}\n";
    
    // Step 3: Error handling for invalid/expired token
    if ($httpCode === 401 || $httpCode === 403) {
        echo "  ✗ ERROR: API token is invalid or expired (HTTP {$httpCode})\n";
        echo "  Sending alert email...\n";
        
        // Send alert email
        $subject = 'ALARM: easyVerein API Token abgelaufen';
        $body = "
            <h2>ALARM: easyVerein API Token abgelaufen</h2>
            <p>Der API-Token für easyVerein ist ungültig oder abgelaufen.</p>
            <p><strong>HTTP Status Code:</strong> {$httpCode}</p>
            <p><strong>Zeit:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p>Bitte erneuern Sie den API-Token in der Datei cron_sync_inventory.php</p>
        ";
        
        $emailSent = MailService::sendEmail(ALERT_EMAIL, $subject, $body);
        
        if ($emailSent) {
            echo "  ✓ Alert email sent to " . ALERT_EMAIL . "\n";
        } else {
            echo "  ✗ Failed to send alert email\n";
            error_log("Failed to send alert email to " . ALERT_EMAIL);
        }
        
        echo "\nSync aborted due to authentication failure.\n";
        exit(1);
    }
    
    // Step 4: Process successful response
    if ($httpCode === 200) {
        echo "  ✓ Successfully fetched data from API\n";
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON decode error: " . json_last_error_msg());
        }
        
        // Get results array (could be in 'results' key or root level)
        $items = isset($data['results']) ? $data['results'] : (is_array($data) ? $data : []);
        
        echo "  Found " . count($items) . " inventory items\n\n";
        
        // Step 5: Sync items to database
        echo "Step 3: Syncing inventory items to database...\n";
        
        $created = 0;
        $updated = 0;
        $errors = 0;
        
        // Prepare INSERT ... ON DUPLICATE KEY UPDATE statement
        $stmt = $db->prepare("
            INSERT INTO inventory (
                easyverein_id, 
                name, 
                description, 
                serial_number, 
                location, 
                image_path,
                last_synced_at
            ) VALUES (
                :easyverein_id,
                :name,
                :description,
                :serial_number,
                :location,
                :image_path,
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                description = VALUES(description),
                serial_number = VALUES(serial_number),
                location = VALUES(location),
                image_path = VALUES(image_path),
                last_synced_at = NOW()
        ");
        
        foreach ($items as $item) {
            try {
                // Map fields from API to database
                $easyvereinId = $item['id'] ?? null;
                $name = $item['name'] ?? 'Unnamed Item';
                $description = $item['note'] ?? null;
                $serialNumber = $item['inventoryNumber'] ?? null;
                $location = $item['storedIn'] ?? null;
                
                // Extract image URL from _embedded or image field
                $imagePath = null;
                if (isset($item['_embedded']['image'])) {
                    // Check if _embedded.image is an object with URL
                    if (is_array($item['_embedded']['image']) && isset($item['_embedded']['image']['url'])) {
                        $imagePath = $item['_embedded']['image']['url'];
                    } elseif (is_string($item['_embedded']['image'])) {
                        $imagePath = $item['_embedded']['image'];
                    }
                } elseif (isset($item['image'])) {
                    // Check if image is a direct URL or object
                    if (is_array($item['image']) && isset($item['image']['url'])) {
                        $imagePath = $item['image']['url'];
                    } elseif (is_string($item['image'])) {
                        $imagePath = $item['image'];
                    }
                }
                
                // Skip items without an ID
                if ($easyvereinId === null) {
                    echo "  ⚠ Skipping item without ID: " . ($name ?? 'unknown') . "\n";
                    $errors++;
                    continue;
                }
                
                // Check if item exists
                $checkStmt = $db->prepare("SELECT id FROM inventory WHERE easyverein_id = :easyverein_id");
                $checkStmt->execute(['easyverein_id' => $easyvereinId]);
                $exists = $checkStmt->fetch();
                
                // Execute insert/update
                $stmt->execute([
                    ':easyverein_id' => $easyvereinId,
                    ':name' => $name,
                    ':description' => $description,
                    ':serial_number' => $serialNumber,
                    ':location' => $location,
                    ':image_path' => $imagePath
                ]);
                
                if ($exists) {
                    $updated++;
                    echo "  ✓ Updated: {$name} (ID: {$easyvereinId})\n";
                } else {
                    $created++;
                    echo "  ✓ Created: {$name} (ID: {$easyvereinId})\n";
                }
                
            } catch (Exception $e) {
                $errors++;
                $itemName = $item['name'] ?? 'unknown';
                echo "  ✗ Error syncing item '{$itemName}': " . $e->getMessage() . "\n";
                error_log("Error syncing inventory item: " . $e->getMessage());
            }
        }
        
        // Output summary
        echo "\n=== Sync Summary ===\n";
        echo "Total items processed: " . count($items) . "\n";
        echo "Created: {$created}\n";
        echo "Updated: {$updated}\n";
        echo "Errors: {$errors}\n";
        
    } else {
        // Unexpected HTTP status code
        echo "  ✗ Unexpected HTTP status code: {$httpCode}\n";
        echo "  Response: " . substr($response, 0, 200) . "...\n";
        throw new Exception("Unexpected API response code: {$httpCode}");
    }
    
} catch (Exception $e) {
    echo "\n✗ FATAL ERROR: " . $e->getMessage() . "\n";
    error_log("EasyVerein Sync Error: " . $e->getMessage());
    exit(1);
}

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
echo "=== Sync Complete ===\n";

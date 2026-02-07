<?php
/**
 * Invoice Setup Verification Script
 * 
 * Checks if the Invoice Module is ready for production:
 * 1. Verifies uploads/invoices directory exists
 * 2. Verifies MailService::send() method accepts 4 arguments (including attachments)
 * 
 * Outputs:
 * - GREEN if safe to go live
 * - RED if issues found
 */

// Track verification results
$checks = [];
$allPassed = true;

// ============================================================
// CHECK 1: uploads/invoices directory exists
// ============================================================
$uploadsInvoicesPath = __DIR__ . '/uploads/invoices';
if (is_dir($uploadsInvoicesPath)) {
    $checks[] = [
        'name' => 'uploads/invoices directory',
        'status' => 'PASS',
        'message' => 'Directory exists at: ' . $uploadsInvoicesPath
    ];
} else {
    $checks[] = [
        'name' => 'uploads/invoices directory',
        'status' => 'FAIL',
        'message' => 'Directory not found at: ' . $uploadsInvoicesPath
    ];
    $allPassed = false;
}

// ============================================================
// CHECK 2: MailService::send() accepts 4 arguments
// ============================================================
$mailServicePath = __DIR__ . '/src/MailService.php';
if (file_exists($mailServicePath)) {
    require_once $mailServicePath;
    
    if (class_exists('MailService')) {
        $reflection = new ReflectionClass('MailService');
        
        if ($reflection->hasMethod('send')) {
            $method = $reflection->getMethod('send');
            $parameters = $method->getParameters();
            $paramCount = count($parameters);
            
            // Check if method has exactly 4 parameters
            // Expected signature: send($to, $subject, $body, $attachments = [])
            if ($paramCount === 4) {
                // Verify the 4th parameter is named 'attachments' and has a default value
                $fourthParam = $parameters[3];
                if ($fourthParam->getName() === 'attachments' && $fourthParam->isDefaultValueAvailable()) {
                    $checks[] = [
                        'name' => 'MailService::send() method signature',
                        'status' => 'PASS',
                        'message' => 'Method accepts 4 arguments: $to, $subject, $body, $attachments = []'
                    ];
                } else {
                    $checks[] = [
                        'name' => 'MailService::send() method signature',
                        'status' => 'FAIL',
                        'message' => '4th parameter exists but not named "attachments" or missing default value'
                    ];
                    $allPassed = false;
                }
            } else {
                $checks[] = [
                    'name' => 'MailService::send() method signature',
                    'status' => 'FAIL',
                    'message' => "Method accepts $paramCount arguments, expected 4"
                ];
                $allPassed = false;
            }
        } else {
            $checks[] = [
                'name' => 'MailService::send() method signature',
                'status' => 'FAIL',
                'message' => 'send() method not found in MailService class'
            ];
            $allPassed = false;
        }
    } else {
        $checks[] = [
            'name' => 'MailService::send() method signature',
            'status' => 'FAIL',
            'message' => 'MailService class not found'
        ];
        $allPassed = false;
    }
} else {
    $checks[] = [
        'name' => 'MailService::send() method signature',
        'status' => 'FAIL',
        'message' => 'MailService.php not found at: ' . $mailServicePath
    ];
    $allPassed = false;
}

// ============================================================
// OUTPUT RESULTS
// ============================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Setup Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .status-banner {
            padding: 30px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: bold;
        }
        .status-banner.green {
            background-color: #4CAF50;
            color: white;
        }
        .status-banner.red {
            background-color: #f44336;
            color: white;
        }
        .check-list {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #ddd;
            background-color: #f9f9f9;
        }
        .check-item.pass {
            border-left-color: #4CAF50;
            background-color: #f1f8f4;
        }
        .check-item.fail {
            border-left-color: #f44336;
            background-color: #fef1f0;
        }
        .check-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .check-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .check-status.pass {
            background-color: #4CAF50;
            color: white;
        }
        .check-status.fail {
            background-color: #f44336;
            color: white;
        }
        .check-message {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <h1>📋 Invoice Setup Verification</h1>
    <p class="subtitle">Checking if Invoice Module is ready for production deployment</p>
    
    <div class="status-banner <?php echo $allPassed ? 'green' : 'red'; ?>">
        <?php echo $allPassed ? '✅ GREEN - SAFE TO GO LIVE' : '❌ RED - ISSUES FOUND'; ?>
    </div>
    
    <div class="check-list">
        <h2>Verification Checks</h2>
        <?php foreach ($checks as $check): ?>
            <div class="check-item <?php echo strtolower($check['status']); ?>">
                <div class="check-name">
                    <?php echo htmlspecialchars($check['name']); ?>
                    <span class="check-status <?php echo strtolower($check['status']); ?>">
                        <?php echo $check['status']; ?>
                    </span>
                </div>
                <div class="check-message">
                    <?php echo htmlspecialchars($check['message']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($allPassed): ?>
        <div style="margin-top: 30px; padding: 20px; background-color: #d4edda; border-left: 4px solid #28a745; border-radius: 5px;">
            <strong>✓ All checks passed!</strong><br>
            The Invoice Module is properly configured and ready for production use.
        </div>
    <?php else: ?>
        <div style="margin-top: 30px; padding: 20px; background-color: #f8d7da; border-left: 4px solid #dc3545; border-radius: 5px;">
            <strong>⚠ Action required:</strong><br>
            Please address the failed checks before deploying to production.
        </div>
    <?php endif; ?>
</body>
</html>

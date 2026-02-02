<?php
/**
 * Test script for Secure Image Upload
 * Tests the SecureImageUpload utility with various file types
 */

echo "=== Testing Secure Image Upload System ===\n\n";

require_once __DIR__ . '/../includes/utils/SecureImageUpload.php';

echo "NOTE: Tests 1, 2, 6, and 7 will show upload failures in CLI mode\n";
echo "because move_uploaded_file() only works with actual HTTP POST uploads.\n";
echo "The security validation tests (3, 4, 5) are what matter most.\n\n";

// Create temporary test directory with cryptographically secure random name
$randomSuffix = bin2hex(random_bytes(8));
$testDir = sys_get_temp_dir() . '/upload_test_' . $randomSuffix;
if (!is_dir($testDir)) {
    mkdir($testDir, 0700, true);
}
echo "Created test directory: $testDir\n\n";

// Test 1: Valid JPEG image
echo "Test 1: Valid JPEG Image\n";
echo "Creating a valid JPEG image...\n";
$validJpegPath = $testDir . '/valid_image.jpg';
$image = imagecreatetruecolor(100, 100);
$bgColor = imagecolorallocate($image, 255, 255, 255);
imagefill($image, 0, 0, $bgColor);
imagejpeg($image, $validJpegPath, 90);
imagedestroy($image);

// Simulate $_FILES array
$validFile = [
    'tmp_name' => $validJpegPath,
    'name' => 'test.jpg',
    'type' => 'image/jpeg',
    'size' => filesize($validJpegPath),
    'error' => UPLOAD_ERR_OK
];

$uploadDir = __DIR__ . '/../assets/uploads/';
$result = SecureImageUpload::uploadImage($validFile, $uploadDir);

if ($result['success']) {
    echo "  ✓ Valid JPEG uploaded successfully\n";
    echo "  ✓ Path: " . $result['path'] . "\n";
    // Verify file exists
    $fullPath = __DIR__ . '/../' . $result['path'];
    if (file_exists($fullPath)) {
        echo "  ✓ File exists on disk\n";
        // Verify it's a valid image
        $imageInfo = @getimagesize($fullPath);
        if ($imageInfo !== false) {
            echo "  ✓ File is a valid image (" . $imageInfo['mime'] . ")\n";
        }
        // Clean up
        unlink($fullPath);
    }
} else {
    echo "  ⚠ EXPECTED: " . $result['error'] . " (move_uploaded_file() requires HTTP POST)\n";
}
echo "\n";

// Test 2: Valid PNG image
echo "Test 2: Valid PNG Image\n";
echo "Creating a valid PNG image...\n";
$validPngPath = $testDir . '/valid_image.png';
$image = imagecreatetruecolor(100, 100);
$bgColor = imagecolorallocate($image, 0, 255, 0);
imagefill($image, 0, 0, $bgColor);
imagepng($image, $validPngPath);
imagedestroy($image);

$validFile = [
    'tmp_name' => $validPngPath,
    'name' => 'test.png',
    'type' => 'image/png',
    'size' => filesize($validPngPath),
    'error' => UPLOAD_ERR_OK
];

$result = SecureImageUpload::uploadImage($validFile, $uploadDir);

if ($result['success']) {
    echo "  ✓ Valid PNG uploaded successfully\n";
    echo "  ✓ Path: " . $result['path'] . "\n";
    $fullPath = __DIR__ . '/../' . $result['path'];
    if (file_exists($fullPath)) {
        echo "  ✓ File exists on disk\n";
        unlink($fullPath);
    }
} else {
    echo "  ⚠ EXPECTED: " . $result['error'] . " (move_uploaded_file() requires HTTP POST)\n";
}
echo "\n";

// Test 3: PHP file disguised as image (MIME type check should catch this)
echo "Test 3: PHP File Disguised as Image (Security Test)\n";
echo "Creating a PHP file with fake image extension...\n";
$phpFilePath = $testDir . '/malicious.php';
file_put_contents($phpFilePath, '<?php system($_GET["cmd"]); ?>');

$maliciousFile = [
    'tmp_name' => $phpFilePath,
    'name' => 'image.jpg',  // Fake extension
    'type' => 'image/jpeg', // Fake MIME type (finfo_file will detect the real type)
    'size' => filesize($phpFilePath),
    'error' => UPLOAD_ERR_OK
];

$result = SecureImageUpload::uploadImage($maliciousFile, $uploadDir);

if (!$result['success']) {
    echo "  ✓ CORRECTLY REJECTED malicious PHP file\n";
    echo "  ✓ Error: " . $result['error'] . "\n";
} else {
    echo "  ✗ SECURITY FAILURE: Malicious file was accepted!\n";
    $fullPath = __DIR__ . '/../' . $result['path'];
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}
echo "\n";

// Test 4: Text file with .jpg extension
echo "Test 4: Text File with .jpg Extension (Security Test)\n";
echo "Creating a text file with .jpg extension...\n";
$textFilePath = $testDir . '/fake_image.jpg';
file_put_contents($textFilePath, 'This is just a text file, not an image.');

$fakeFile = [
    'tmp_name' => $textFilePath,
    'name' => 'image.jpg',
    'type' => 'image/jpeg', // Fake type
    'size' => filesize($textFilePath),
    'error' => UPLOAD_ERR_OK
];

$result = SecureImageUpload::uploadImage($fakeFile, $uploadDir);

if (!$result['success']) {
    echo "  ✓ CORRECTLY REJECTED text file disguised as image\n";
    echo "  ✓ Error: " . $result['error'] . "\n";
} else {
    echo "  ✗ SECURITY FAILURE: Text file was accepted as image!\n";
    $fullPath = __DIR__ . '/../' . $result['path'];
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}
echo "\n";

// Test 5: File too large
echo "Test 5: File Size Validation (6MB file - should be rejected)\n";
echo "Creating a large JPEG image...\n";
$largeImagePath = $testDir . '/large_image.jpg';
$largeImage = imagecreatetruecolor(2000, 2000);
imagejpeg($largeImage, $largeImagePath, 100);
imagedestroy($largeImage);

// Make it appear larger than 5MB by modifying the file array
$largeFile = [
    'tmp_name' => $largeImagePath,
    'name' => 'large.jpg',
    'type' => 'image/jpeg',
    'size' => 6 * 1024 * 1024, // 6MB (fake size for testing)
    'error' => UPLOAD_ERR_OK
];

$result = SecureImageUpload::uploadImage($largeFile, $uploadDir);

if (!$result['success'] && strpos($result['error'], 'zu groß') !== false) {
    echo "  ✓ CORRECTLY REJECTED oversized file\n";
    echo "  ✓ Error: " . $result['error'] . "\n";
} else if (!$result['success']) {
    echo "  ⚠ File rejected but for different reason: " . $result['error'] . "\n";
} else {
    echo "  ✗ FAILURE: Large file was accepted!\n";
    $fullPath = __DIR__ . '/../' . $result['path'];
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}
echo "\n";

// Test 6: Filename randomization
echo "Test 6: Filename Randomization (Security Test)\n";
echo "Uploading file with double extension (shell.php.jpg)...\n";
$doubleExtPath = $testDir . '/shell.php.jpg';
$image = imagecreatetruecolor(50, 50);
imagejpeg($image, $doubleExtPath);
imagedestroy($image);

$doubleExtFile = [
    'tmp_name' => $doubleExtPath,
    'name' => 'shell.php.jpg', // Dangerous original filename
    'type' => 'image/jpeg',
    'size' => filesize($doubleExtPath),
    'error' => UPLOAD_ERR_OK
];

$result = SecureImageUpload::uploadImage($doubleExtFile, $uploadDir);

if ($result['success']) {
    echo "  ✓ File uploaded successfully\n";
    $filename = basename($result['path']);
    echo "  ✓ Generated filename: " . $filename . "\n";
    
    // Check that original filename is NOT used
    if (strpos($filename, 'shell') === false && strpos($filename, 'php') === false) {
        echo "  ✓ SECURE: Original filename not preserved (no 'shell' or 'php' in filename)\n";
    } else {
        echo "  ✗ SECURITY WARNING: Original filename components present!\n";
    }
    
    // Check filename format (32 hex chars from random_bytes(16))
    if (preg_match('/^item_[a-f0-9]{32}\.(jpg|png|webp|gif)$/', $filename)) {
        echo "  ✓ SECURE: Filename follows secure random pattern\n";
    } else {
        echo "  ⚠ WARNING: Filename doesn't match expected pattern: " . $filename . "\n";
    }
    
    $fullPath = __DIR__ . '/../' . $result['path'];
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
} else {
    echo "  ⚠ EXPECTED: " . $result['error'] . " (move_uploaded_file() requires HTTP POST)\n";
}
echo "\n";

// Test 7: Delete image test
echo "Test 7: Delete Image Functionality\n";
echo "Uploading an image and then deleting it...\n";
$deleteTestPath = $testDir . '/delete_test.jpg';
$image = imagecreatetruecolor(50, 50);
imagejpeg($image, $deleteTestPath);
imagedestroy($image);

$deleteTestFile = [
    'tmp_name' => $deleteTestPath,
    'name' => 'delete_test.jpg',
    'type' => 'image/jpeg',
    'size' => filesize($deleteTestPath),
    'error' => UPLOAD_ERR_OK
];

$result = SecureImageUpload::uploadImage($deleteTestFile, $uploadDir);

if ($result['success']) {
    echo "  ✓ Image uploaded: " . $result['path'] . "\n";
    
    // Now delete it
    $deleted = SecureImageUpload::deleteImage($result['path']);
    if ($deleted) {
        echo "  ✓ Image deleted successfully\n";
        
        $fullPath = __DIR__ . '/../' . $result['path'];
        if (!file_exists($fullPath)) {
            echo "  ✓ File no longer exists on disk\n";
        } else {
            echo "  ✗ File still exists after deletion!\n";
        }
    } else {
        echo "  ✗ Delete operation failed\n";
    }
} else {
    echo "  ⚠ EXPECTED: " . $result['error'] . " (move_uploaded_file() requires HTTP POST)\n";
}
echo "\n";

// Clean up test directory
echo "Cleaning up test directory...\n";
$files = glob($testDir . '/*');
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);
    }
}
rmdir($testDir);
echo "Test directory removed.\n\n";

echo "=== All Tests Completed ===\n";

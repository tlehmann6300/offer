<?php
/**
 * Test Blog Edit Page
 * Validates blog edit page structure and security
 */

echo "===== Blog Edit Page Tests =====\n\n";

// Test results tracker
$tests_passed = 0;
$tests_failed = 0;

function test($name, $callback) {
    global $tests_passed, $tests_failed;
    
    try {
        $result = $callback();
        if ($result === true) {
            $tests_passed++;
            echo "✓ $name\n";
        } else {
            $tests_failed++;
            echo "✗ $name - Failed: $result\n";
        }
    } catch (Exception $e) {
        $tests_failed++;
        echo "✗ $name - Exception: " . $e->getMessage() . "\n";
    }
}

// Test 1: Check if edit.php file exists
test("Blog edit.php file exists", function() {
    $filePath = __DIR__ . '/../pages/blog/edit.php';
    return file_exists($filePath) ? true : "File not found at $filePath";
});

// Test 2: Check if edit.php has no syntax errors
test("Blog edit.php has valid PHP syntax", function() {
    $filePath = __DIR__ . '/../pages/blog/edit.php';
    $output = [];
    $returnVar = 0;
    exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $returnVar);
    return $returnVar === 0 ? true : "Syntax error: " . implode("\n", $output);
});

// Test 3: Check if file contains required security checks
test("File contains BlogPost::canAuth() security check", function() {
    $filePath = __DIR__ . '/../pages/blog/edit.php';
    $content = file_get_contents($filePath);
    return strpos($content, 'BlogPost::canAuth') !== false ? true : "Security check not found";
});

// Test 4: Check if file contains Auth::check()
test("File contains Auth::check() authentication check", function() {
    $filePath = __DIR__ . '/../pages/blog/edit.php';
    $content = file_get_contents($filePath);
    return strpos($content, 'Auth::check()') !== false ? true : "Auth check not found";
});

// Test 5: Check if file contains SecureImageUpload
test("File uses SecureImageUpload class", function() {
    $filePath = __DIR__ . '/../pages/blog/edit.php';
    $content = file_get_contents($filePath);
    return strpos($content, 'SecureImageUpload::uploadImage') !== false ? true : "SecureImageUpload not used";
});

// Test 6: Check if file contains CSRF protection
test("File contains CSRF token handling", function() {
    $filePath = __DIR__ . '/../pages/blog/edit.php';
    $content = file_get_contents($filePath);
    return (strpos($content, 'CSRFHandler::verifyToken') !== false && 
            strpos($content, 'CSRFHandler::getToken') !== false) ? true : "CSRF protection incomplete";
});

// Test 7: Check if file handles both create and edit modes
test("File handles both create and edit modes", function() {
    $filePath = __DIR__ . '/../pages/blog/edit.php';
    $content = file_get_contents($filePath);
    return (strpos($content, 'BlogPost::create') !== false && 
            strpos($content, 'BlogPost::update') !== false) ? true : "Create/Edit handling incomplete";
});

// Test 8: Check if all required form fields are present
test("File contains all required form fields", function() {
    $filePath = __DIR__ . '/../pages/blog/edit.php';
    $content = file_get_contents($filePath);
    $requiredFields = ['name="title"', 'name="category"', 'name="content"', 'name="external_link"', 'name="image"'];
    foreach ($requiredFields as $field) {
        if (strpos($content, $field) === false) {
            return "Missing field: $field";
        }
    }
    return true;
});

// Test 9: Check if file contains proper category options
test("File contains proper category options matching DB schema", function() {
    $filePath = __DIR__ . '/../pages/blog/edit.php';
    $content = file_get_contents($filePath);
    $requiredCategories = ['Allgemein', 'IT', 'Marketing', 'Human Resources', 'Qualitätsmanagement', 'Akquise'];
    foreach ($requiredCategories as $category) {
        if (strpos($content, 'value="' . $category . '"') === false) {
            return "Missing category: $category";
        }
    }
    return true;
});

// Test 10: Check if file redirects to index.php on success
test("File redirects to index.php on success", function() {
    $filePath = __DIR__ . '/../pages/blog/edit.php';
    $content = file_get_contents($filePath);
    return strpos($content, "header('Location: index.php')") !== false ? true : "Redirect to index.php not found";
});

// Summary
echo "\n";
echo "===== Test Summary =====\n";
echo "Tests Passed: $tests_passed\n";
echo "Tests Failed: $tests_failed\n";
echo "Total Tests: " . ($tests_passed + $tests_failed) . "\n";

if ($tests_failed === 0) {
    echo "\n✓ All tests passed!\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed.\n";
    exit(1);
}
?>

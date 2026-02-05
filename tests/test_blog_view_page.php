<?php
/**
 * Test Blog View Page
 * Validates blog view page structure, security, and functionality
 */

echo "===== Blog View Page Tests =====\n\n";

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

// Test 1: Check if view.php file exists
test("Blog view.php file exists", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    return file_exists($filePath) ? true : "File not found at $filePath";
});

// Test 2: Check if view.php has no syntax errors
test("Blog view.php has valid PHP syntax", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $output = [];
    $returnVar = 0;
    exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $returnVar);
    return $returnVar === 0 ? true : "Syntax error: " . implode("\n", $output);
});

// Test 3: Check if file contains Auth::check()
test("File contains Auth::check() authentication check", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    return strpos($content, 'Auth::check()') !== false ? true : "Auth check not found";
});

// Test 4: Check if file contains CSRF protection
test("File contains CSRF token handling", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    return (strpos($content, 'CSRFHandler::verifyToken') !== false && 
            strpos($content, 'CSRFHandler::getToken') !== false) ? true : "CSRF protection incomplete";
});

// Test 5: Check if file uses htmlspecialchars for XSS protection
test("File uses htmlspecialchars for output escaping", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    // Count occurrences of htmlspecialchars
    $count = substr_count($content, 'htmlspecialchars');
    return $count >= 10 ? true : "Insufficient htmlspecialchars usage (found: $count)";
});

// Test 6: Check if file contains like functionality
test("File contains like toggle functionality", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    return (strpos($content, 'BlogPost::toggleLike') !== false && 
            strpos($content, 'toggle_like') !== false) ? true : "Like functionality not found";
});

// Test 7: Check if file contains comment functionality
test("File contains comment add functionality", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    return (strpos($content, 'BlogPost::addComment') !== false && 
            strpos($content, 'add_comment') !== false) ? true : "Comment functionality not found";
});

// Test 8: Check if file displays comments
test("File displays existing comments", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    return (strpos($content, '$post[\'comments\']') !== false && 
            strpos($content, 'foreach ($post[\'comments\']') !== false) ? true : "Comment display not found";
});

// Test 9: Check if file has edit button with authorization check
test("File has edit button with authorization check", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    return (strpos($content, '$canEdit') !== false && 
            strpos($content, 'BlogPost::canAuth') !== false) ? true : "Edit authorization check not found";
});

// Test 10: Check if file shows external link when present
test("File shows external link button", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    return (strpos($content, '$post[\'external_link\']') !== false && 
            strpos($content, 'Mehr Informationen') !== false) ? true : "External link button not found";
});

// Test 11: Check if file displays full-width header image
test("File displays full-width header image", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    return (strpos($content, 'w-full h-96') !== false || 
            strpos($content, 'Full Width Header Image') !== false) ? true : "Full-width header image not found";
});

// Test 12: Check if file displays title, date, author, category
test("File displays title, date, author, and category", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    $hasTitle = strpos($content, '$post[\'title\']') !== false;
    $hasDate = strpos($content, '$post[\'created_at\']') !== false;
    $hasAuthor = strpos($content, '$post[\'author_email\']') !== false;
    $hasCategory = strpos($content, '$post[\'category\']') !== false;
    
    if (!$hasTitle) return "Title not displayed";
    if (!$hasDate) return "Date not displayed";
    if (!$hasAuthor) return "Author not displayed";
    if (!$hasCategory) return "Category not displayed";
    
    return true;
});

// Test 13: Check if file displays full text content
test("File displays full text content", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    return strpos($content, '$post[\'content\']') !== false ? true : "Full content not displayed";
});

// Test 14: Check if file has comment form
test("File has write comment form", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    return (strpos($content, 'name="comment_content"') !== false && 
            strpos($content, 'Kommentar schreiben') !== false) ? true : "Comment form not found";
});

// Test 15: Check if file handles missing post ID
test("File handles missing post ID", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    return (strpos($content, 'if (!$postId)') !== false && 
            strpos($content, 'Kein Beitrag angegeben') !== false) ? true : "Missing post ID handling not found";
});

// Test 16: Check if file handles post not found
test("File handles post not found", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    return (strpos($content, 'if (!$post)') !== false && 
            strpos($content, 'Beitrag nicht gefunden') !== false) ? true : "Post not found handling not found";
});

// Test 17: Check if file uses BlogPost::getById()
test("File uses BlogPost::getById() to fetch post", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    return strpos($content, 'BlogPost::getById') !== false ? true : "BlogPost::getById not used";
});

// Test 18: Check if file shows like count
test("File displays like count", function() {
    $filePath = __DIR__ . '/../pages/blog/view.php';
    $content = file_get_contents($filePath);
    return strpos($content, '$post[\'like_count\']') !== false ? true : "Like count not displayed";
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

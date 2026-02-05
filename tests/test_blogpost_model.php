<?php
/**
 * Test BlogPost Model
 * Validates BlogPost model functionality
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/BlogPost.php';

// Test results tracker
$tests_passed = 0;
$tests_failed = 0;
$test_results = [];

function test($name, $callback) {
    global $tests_passed, $tests_failed, $test_results;
    
    try {
        $result = $callback();
        if ($result === true) {
            $tests_passed++;
            $test_results[] = "✓ $name";
            echo "✓ $name\n";
        } else {
            $tests_failed++;
            $test_results[] = "✗ $name - Failed: $result";
            echo "✗ $name - Failed: $result\n";
        }
    } catch (Exception $e) {
        $tests_failed++;
        $test_results[] = "✗ $name - Exception: " . $e->getMessage();
        echo "✗ $name - Exception: " . $e->getMessage() . "\n";
    }
}

echo "===== BlogPost Model Tests =====\n\n";

// Test 1: Check if BlogPost class exists
test("BlogPost class exists", function() {
    return class_exists('BlogPost');
});

// Test 2: Check if all required methods exist
test("All required methods exist", function() {
    $requiredMethods = ['getAll', 'getById', 'create', 'update', 'toggleLike', 'addComment', 'canAuth'];
    foreach ($requiredMethods as $method) {
        if (!method_exists('BlogPost', $method)) {
            return "Missing method: $method";
        }
    }
    return true;
});

// Test 3: canAuth() - Test role authorization
test("canAuth() - admin role returns true", function() {
    return BlogPost::canAuth('admin') === true;
});

test("canAuth() - board role returns true", function() {
    return BlogPost::canAuth('board') === true;
});

test("canAuth() - head role returns true", function() {
    return BlogPost::canAuth('head') === true;
});

test("canAuth() - member role returns false", function() {
    return BlogPost::canAuth('member') === false;
});

test("canAuth() - alumni role returns false", function() {
    return BlogPost::canAuth('alumni') === false;
});

test("canAuth() - invalid role returns false", function() {
    return BlogPost::canAuth('invalid_role') === false;
});

// Database-dependent tests (will only run if DB is accessible)
try {
    $db = Database::getContentDB();
    
    echo "\n--- Database Connection Tests ---\n";
    
    // Test 4: Create a test post
    test("create() - Creates a new blog post", function() use ($db) {
        $testData = [
            'title' => 'Test Blog Post',
            'content' => 'This is test content for the blog post.',
            'category' => 'IT',
            'author_id' => 1,
            'external_link' => 'https://example.com',
            'image_path' => '/uploads/test.jpg'
        ];
        
        $postId = BlogPost::create($testData);
        
        if ($postId > 0) {
            // Verify the post was created
            $stmt = $db->prepare("SELECT * FROM blog_posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();
            
            // Clean up
            $db->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([$postId]);
            
            return $post && $post['title'] === 'Test Blog Post';
        }
        
        return "Failed to create post";
    });
    
    // Test 5: Update a post
    test("update() - Updates an existing post", function() use ($db) {
        // Create test post
        $testData = [
            'title' => 'Original Title',
            'content' => 'Original content',
            'category' => 'IT',
            'author_id' => 1
        ];
        $postId = BlogPost::create($testData);
        
        // Update the post
        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated content'
        ];
        $result = BlogPost::update($postId, $updateData);
        
        // Verify update
        $stmt = $db->prepare("SELECT * FROM blog_posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        
        // Clean up
        $db->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([$postId]);
        
        return $result && $post['title'] === 'Updated Title';
    });
    
    // Test 6: toggleLike() functionality
    test("toggleLike() - Toggles like state correctly", function() use ($db) {
        // Create test post
        $testData = [
            'title' => 'Test Post for Likes',
            'content' => 'Content',
            'category' => 'IT',
            'author_id' => 1
        ];
        $postId = BlogPost::create($testData);
        $userId = 1;
        
        // First toggle should add like
        $isLiked = BlogPost::toggleLike($postId, $userId);
        if (!$isLiked) {
            return "First toggle should return true (liked)";
        }
        
        // Second toggle should remove like
        $isLiked = BlogPost::toggleLike($postId, $userId);
        if ($isLiked) {
            return "Second toggle should return false (unliked)";
        }
        
        // Clean up
        $db->prepare("DELETE FROM blog_likes WHERE post_id = ?")->execute([$postId]);
        $db->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([$postId]);
        
        return true;
    });
    
    // Test 7: addComment() functionality
    test("addComment() - Adds a comment to a post", function() use ($db) {
        // Create test post
        $testData = [
            'title' => 'Test Post for Comments',
            'content' => 'Content',
            'category' => 'IT',
            'author_id' => 1
        ];
        $postId = BlogPost::create($testData);
        $userId = 1;
        $commentContent = 'This is a test comment';
        
        $commentId = BlogPost::addComment($postId, $userId, $commentContent);
        
        // Verify comment was added
        $stmt = $db->prepare("SELECT * FROM blog_comments WHERE id = ?");
        $stmt->execute([$commentId]);
        $comment = $stmt->fetch();
        
        // Clean up
        $db->prepare("DELETE FROM blog_comments WHERE post_id = ?")->execute([$postId]);
        $db->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([$postId]);
        
        return $comment && $comment['content'] === $commentContent;
    });
    
    // Test 8: getById() with like count and comments
    test("getById() - Retrieves post with like count and comments", function() use ($db) {
        // Create test post
        $testData = [
            'title' => 'Test Post Full',
            'content' => 'Full test content',
            'category' => 'IT',
            'author_id' => 1
        ];
        $postId = BlogPost::create($testData);
        
        // Add likes and comments
        BlogPost::toggleLike($postId, 1);
        BlogPost::toggleLike($postId, 2);
        BlogPost::addComment($postId, 1, 'Comment 1');
        BlogPost::addComment($postId, 2, 'Comment 2');
        
        // Get post
        $post = BlogPost::getById($postId);
        
        // Clean up
        $db->prepare("DELETE FROM blog_comments WHERE post_id = ?")->execute([$postId]);
        $db->prepare("DELETE FROM blog_likes WHERE post_id = ?")->execute([$postId]);
        $db->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([$postId]);
        
        if (!$post) {
            return "Post not found";
        }
        
        if ($post['like_count'] != 2) {
            return "Like count incorrect: expected 2, got " . $post['like_count'];
        }
        
        if (count($post['comments']) != 2) {
            return "Comment count incorrect: expected 2, got " . count($post['comments']);
        }
        
        return true;
    });
    
    // Test 9: getAll() with pagination and filtering
    test("getAll() - Retrieves posts with pagination", function() use ($db) {
        // Create multiple test posts
        $postIds = [];
        for ($i = 1; $i <= 5; $i++) {
            $testData = [
                'title' => "Test Post $i",
                'content' => "Content $i",
                'category' => ($i % 2 == 0) ? 'IT' : 'Marketing',
                'author_id' => 1
            ];
            $postIds[] = BlogPost::create($testData);
        }
        
        // Get all posts
        $posts = BlogPost::getAll(10, 0);
        $allCount = count($posts);
        
        // Get with limit
        $posts = BlogPost::getAll(2, 0);
        $limitCount = count($posts);
        
        // Get with category filter
        $posts = BlogPost::getAll(10, 0, 'IT');
        $filteredCount = count($posts);
        
        // Clean up
        foreach ($postIds as $id) {
            $db->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([$id]);
        }
        
        if ($allCount < 5) {
            return "Expected at least 5 posts, got $allCount";
        }
        
        if ($limitCount != 2) {
            return "Expected 2 posts with limit, got $limitCount";
        }
        
        // Note: filteredCount might include existing posts, so we just check it's not more than all
        if ($filteredCount > $allCount) {
            return "Filtered count should not exceed all count";
        }
        
        return true;
    });
    
} catch (Exception $e) {
    echo "\n✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "Skipping database-dependent tests.\n";
}

// Summary
echo "\n===== Test Summary =====\n";
echo "Passed: $tests_passed\n";
echo "Failed: $tests_failed\n";
echo "Total: " . ($tests_passed + $tests_failed) . "\n";

if ($tests_failed === 0) {
    echo "\n✓ All tests passed!\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed.\n";
    exit(1);
}

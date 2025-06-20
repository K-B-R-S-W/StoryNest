<?php
// Include config file
require_once "../config.php";

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to post comments'
    ]);
    exit;
}

// Check if story ID and content are provided
if (!isset($_POST['story_id']) || empty($_POST['story_id']) || !isset($_POST['content']) || empty($_POST['content'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Story ID and comment content are required'
    ]);
    exit;
}

$story_id = $_POST['story_id'];
$user_id = $_SESSION['user_id'];
$content = trim($_POST['content']);
$parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

// Validate story exists
try {
    $stmt = $conn->prepare("SELECT id FROM stories WHERE id = :story_id AND status = 'published'");
    $stmt->bindParam(':story_id', $story_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Story not found or not published'
        ]);
        exit;
    }
    
    // If this is a reply, check that parent comment exists
    if ($parent_id) {
        $stmt = $conn->prepare("SELECT id FROM comments WHERE id = :parent_id AND story_id = :story_id");
        $stmt->bindParam(':parent_id', $parent_id);
        $stmt->bindParam(':story_id', $story_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Parent comment not found'
            ]);
            exit;
        }
    }
    
    // Insert comment
    $stmt = $conn->prepare("INSERT INTO comments (user_id, story_id, parent_id, content) VALUES (:user_id, :story_id, :parent_id, :content)");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':story_id', $story_id);
    $stmt->bindParam(':parent_id', $parent_id);
    $stmt->bindParam(':content', $content);
    $stmt->execute();
    
    $comment_id = $conn->lastInsertId();
    
    // Get comment data including user info
    $stmt = $conn->prepare("
        SELECT c.*, u.username, u.display_name, u.profile_image
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = :comment_id
    ");
    $stmt->bindParam(':comment_id', $comment_id);
    $stmt->execute();
    $comment = $stmt->fetch();
    
    // Get story author to check if comment is from author
    $stmt = $conn->prepare("SELECT user_id FROM stories WHERE id = :story_id");
    $stmt->bindParam(':story_id', $story_id);
    $stmt->execute();
    $story = $stmt->fetch();
    $is_author = ($user_id == $story['user_id']);
    
    echo json_encode([
        'success' => true,
        'comment' => [
            'id' => $comment['id'],
            'content' => htmlspecialchars($comment['content']),
            'created_at' => $comment['created_at'],
            'user_id' => $comment['user_id'],
            'username' => $comment['username'],
            'display_name' => $comment['display_name'] ?: $comment['username'],
            'profile_image' => !empty($comment['profile_image']) ? 'uploads/avatars/' . htmlspecialchars($comment['profile_image']) : '/api/placeholder/40/40',
            'parent_id' => $comment['parent_id'],
            'is_author' => $is_author
        ]
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}
?>
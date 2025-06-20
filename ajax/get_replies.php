<?php
// Include config file
require_once "../config.php";

// Set header to return JSON
header('Content-Type: application/json');

// Check if comment ID is provided
if (!isset($_GET['comment_id']) || empty($_GET['comment_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Comment ID is required'
    ]);
    exit;
}

$comment_id = $_GET['comment_id'];

try {
    // Get parent comment to verify it exists and get story ID
    $stmt = $conn->prepare("
        SELECT c.*, s.user_id as story_author_id 
        FROM comments c
        JOIN stories s ON c.story_id = s.id
        WHERE c.id = :comment_id AND c.parent_id IS NULL
    ");
    $stmt->bindParam(':comment_id', $comment_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Parent comment not found'
        ]);
        exit;
    }
    
    $parent_comment = $stmt->fetch();
    $story_author_id = $parent_comment['story_author_id'];
    
    // Get replies for the comment
    $stmt = $conn->prepare("
        SELECT c.*, u.username, u.display_name, u.profile_image
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.parent_id = :comment_id
        ORDER BY c.created_at ASC
    ");
    $stmt->bindParam(':comment_id', $comment_id);
    $stmt->execute();
    
    $replies = [];
    while ($row = $stmt->fetch()) {
        $replies[] = [
            'id' => $row['id'],
            'content' => htmlspecialchars($row['content']),
            'created_at' => $row['created_at'],
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'display_name' => $row['display_name'] ?: $row['username'],
            'profile_image' => !empty($row['profile_image']) ? 'uploads/avatars/' . htmlspecialchars($row['profile_image']) : '/api/placeholder/32/32',
            'is_author' => ($row['user_id'] == $story_author_id)
        ];
    }
    
    echo json_encode([
        'success' => true,
        'replies' => $replies
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}
?>
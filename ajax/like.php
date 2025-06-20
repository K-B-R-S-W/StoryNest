<?php
// Include config file
require_once "../config.php";

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to like stories'
    ]);
    exit;
}

// Check if story ID is provided
if (!isset($_POST['story_id']) || empty($_POST['story_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Story ID is required'
    ]);
    exit;
}

$story_id = $_POST['story_id'];
$user_id = $_SESSION['user_id'];

try {
    // Check if story exists and is published
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
    
    // Check if user has already liked the story
    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = :user_id AND story_id = :story_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':story_id', $story_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // User has already liked the story, so unlike it
        $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = :user_id AND story_id = :story_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':story_id', $story_id);
        $stmt->execute();
        
        $action = 'unliked';
    } else {
        // User has not liked the story, so like it
        $stmt = $conn->prepare("INSERT INTO likes (user_id, story_id) VALUES (:user_id, :story_id)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':story_id', $story_id);
        $stmt->execute();
        
        $action = 'liked';
        
        // Check if this is the author's first like milestone (10, 50, 100, 500, 1000, etc.)
        $stmt = $conn->prepare("
            SELECT s.user_id, 
                   (SELECT COUNT(*) FROM likes 
                    JOIN stories ON likes.story_id = stories.id 
                    WHERE stories.user_id = s.user_id) as total_likes
            FROM stories s 
            WHERE s.id = :story_id
        ");
        $stmt->bindParam(':story_id', $story_id);
        $stmt->execute();
        $result = $stmt->fetch();
        
        $author_id = $result['user_id'];
        $total_likes = $result['total_likes'];
        
        // Check for like milestones (10, 50, 100, 500, 1000, etc.)
        $milestones = [10, 50, 100, 500, 1000, 5000, 10000];
        foreach ($milestones as $milestone) {
            if ($total_likes == $milestone) {
                // Record achievement if it exists
                $stmt = $conn->prepare("
                    SELECT id FROM achievements WHERE requirement = :requirement
                ");
                $requirement = "likes_count>=$milestone";
                $stmt->bindParam(':requirement', $requirement);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $achievement = $stmt->fetch();
                    $achievement_id = $achievement['id'];
                    
                    // Check if user already has this achievement
                    $stmt = $conn->prepare("
                        SELECT id FROM user_achievements 
                        WHERE user_id = :user_id AND achievement_id = :achievement_id
                    ");
                    $stmt->bindParam(':user_id', $author_id);
                    $stmt->bindParam(':achievement_id', $achievement_id);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() == 0) {
                        // Award achievement
                        $stmt = $conn->prepare("
                            INSERT INTO user_achievements (user_id, achievement_id)
                            VALUES (:user_id, :achievement_id)
                        ");
                        $stmt->bindParam(':user_id', $author_id);
                        $stmt->bindParam(':achievement_id', $achievement_id);
                        $stmt->execute();
                    }
                }
                
                break; // Only process one milestone at a time
            }
        }
    }
    
    // Get updated like count
    $stmt = $conn->prepare("SELECT COUNT(*) as likes_count FROM likes WHERE story_id = :story_id");
    $stmt->bindParam(':story_id', $story_id);
    $stmt->execute();
    $result = $stmt->fetch();
    $likes_count = $result['total_likes'] ?? $result['likes_count'];
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'likes_count' => $likes_count
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}
?>
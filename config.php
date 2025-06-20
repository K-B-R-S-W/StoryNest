<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'storynest';
$db_user = 'root';
$db_pass = '';

// Create connection
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to get current user data
function getCurrentUser() {
    global $conn;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        return $stmt->fetch();
    } catch(PDOException $e) {
        return null;
    }
}

// Helper function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Helper function to redirect
function redirect($url) {
    header("Location: $url");
    exit;
}

// Helper function to generate a random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Flash message handling
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Display flash message
function displayFlashMessage() {
    $message = getFlashMessage();
    if ($message) {
        $type = $message['type'] == 'error' ? 'danger' : $message['type'];
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo $message['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}

// Function to format date
function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

// Function to format time ago
function timeAgo($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$string) {
        return 'just now';
    }
    
    $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Function to truncate text
function truncateText($text, $length = 100, $append = '...') {
    $text = strip_tags($text);
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        $text .= $append;
    }
    return $text;
}

// Function to count words
function countWords($text) {
    return str_word_count(strip_tags($text));
}

// Function to estimate reading time
function readingTime($text, $wpm = 200) {
    $wordCount = countWords($text);
    $minutes = ceil($wordCount / $wpm);
    return $minutes . ' min read';
}

// Function to check if a user has liked a story
function hasLiked($user_id, $story_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = :user_id AND story_id = :story_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':story_id', $story_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    } catch(PDOException $e) {
        return false;
    }
}

// Function to check if a user has bookmarked a story
function hasBookmarked($user_id, $story_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT id FROM bookmarks WHERE user_id = :user_id AND story_id = :story_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':story_id', $story_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    } catch(PDOException $e) {
        return false;
    }
}

// Function to check if a user is following another user
function isFollowing($follower_id, $followed_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT id FROM follows WHERE follower_id = :follower_id AND followed_id = :followed_id");
        $stmt->bindParam(':follower_id', $follower_id);
        $stmt->bindParam(':followed_id', $followed_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    } catch(PDOException $e) {
        return false;
    }
}

// Function to get category by ID
function getCategoryById($category_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->bindParam(':id', $category_id);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch(PDOException $e) {
        return null;
    }
}

// Function to get user by ID
function getUserById($user_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch(PDOException $e) {
        return null;
    }
}

// Function to get user by username
function getUserByUsername($username) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch(PDOException $e) {
        return null;
    }
}

// Function to get story by ID
function getStoryById($story_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT s.*, u.username, u.display_name, u.profile_image, c.name as category_name
            FROM stories s
            JOIN users u ON s.user_id = u.id
            JOIN categories c ON s.category_id = c.id
            WHERE s.id = :id
        ");
        $stmt->bindParam(':id', $story_id);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch(PDOException $e) {
        return null;
    }
}

// Function to get all categories
function getAllCategories() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Function to get popular categories
function getPopularCategories($limit = 5) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT c.*, COUNT(s.id) as story_count
            FROM categories c
            LEFT JOIN stories s ON c.id = s.category_id AND s.status = 'published'
            GROUP BY c.id
            ORDER BY story_count DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Function to get featured stories
function getFeaturedStories($limit = 5) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT s.*, u.username, u.display_name, u.profile_image, c.name as category_name, 
                   (SELECT COUNT(*) FROM likes WHERE story_id = s.id) as likes_count,
                   (SELECT COUNT(*) FROM comments WHERE story_id = s.id) as comments_count
            FROM stories s
            JOIN users u ON s.user_id = u.id
            JOIN categories c ON s.category_id = c.id
            WHERE s.status = 'published'
            ORDER BY s.views DESC, s.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Function to get recent stories
function getRecentStories($limit = 10) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT s.*, u.username, u.display_name, u.profile_image, c.name as category_name, 
                   (SELECT COUNT(*) FROM likes WHERE story_id = s.id) as likes_count,
                   (SELECT COUNT(*) FROM comments WHERE story_id = s.id) as comments_count
            FROM stories s
            JOIN users u ON s.user_id = u.id
            JOIN categories c ON s.category_id = c.id
            WHERE s.status = 'published'
            ORDER BY s.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Function to log errors
function logError($message, $error = null) {
    $errorLog = fopen("logs/error.log", "a");
    $timestamp = date("Y-m-d H:i:s");
    $errorMessage = "[$timestamp] $message";
    
    if ($error) {
        $errorMessage .= ": " . $error->getMessage();
    }
    
    fwrite($errorLog, $errorMessage . "\n");
    fclose($errorLog);
}
?>
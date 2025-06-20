<?php
// Database setup script - Run this once to create tables

// Database configuration
$db_host = 'localhost';
$db_name = 'storynest';
$db_user = 'root';
$db_pass = '';

// Create connection
try {
    // Connect to MySQL without database (to create it if needed)
    $conn = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS $db_name");
    echo "Database created or already exists<br>";
    
    // Connect to the database
    $conn->exec("USE $db_name");
    
    // Create Users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        display_name VARCHAR(100),
        bio TEXT,
        profile_image VARCHAR(255) DEFAULT 'default_avatar.jpg',
        website VARCHAR(255),
        twitter VARCHAR(100),
        instagram VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP,
        reset_token VARCHAR(255),
        reset_token_expiry TIMESTAMP,
        is_admin BOOLEAN DEFAULT 0
    )";
    $conn->exec($sql);
    echo "Users table created<br>";
    
    // Create Categories table
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL,
        description TEXT,
        icon VARCHAR(50)
    )";
    $conn->exec($sql);
    echo "Categories table created<br>";
    
    // Create Stories table
    $sql = "CREATE TABLE IF NOT EXISTS stories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        excerpt TEXT,
        category_id INT,
        series_id INT DEFAULT NULL,
        chapter_number INT DEFAULT NULL,
        word_count INT,
        status ENUM('draft', 'published', 'archived') DEFAULT 'published',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        views INT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (category_id) REFERENCES categories(id)
    )";
    $conn->exec($sql);
    echo "Stories table created<br>";
    
    // Create Series table
    $sql = "CREATE TABLE IF NOT EXISTS series (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        status ENUM('ongoing', 'completed', 'hiatus') DEFAULT 'ongoing',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $conn->exec($sql);
    echo "Series table created<br>";
    
    // Create Comments table
    $sql = "CREATE TABLE IF NOT EXISTS comments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        story_id INT NOT NULL,
        parent_id INT DEFAULT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (story_id) REFERENCES stories(id),
        FOREIGN KEY (parent_id) REFERENCES comments(id)
    )";
    $conn->exec($sql);
    echo "Comments table created<br>";
    
    // Create Likes table
    $sql = "CREATE TABLE IF NOT EXISTS likes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        story_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (user_id, story_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (story_id) REFERENCES stories(id)
    )";
    $conn->exec($sql);
    echo "Likes table created<br>";
    
    // Create Follows table (for user following other users)
    $sql = "CREATE TABLE IF NOT EXISTS follows (
        id INT PRIMARY KEY AUTO_INCREMENT,
        follower_id INT NOT NULL,
        followed_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_follow (follower_id, followed_id),
        FOREIGN KEY (follower_id) REFERENCES users(id),
        FOREIGN KEY (followed_id) REFERENCES users(id)
    )";
    $conn->exec($sql);
    echo "Follows table created<br>";
    
    // Create SeriesFollows table (for users following series)
    $sql = "CREATE TABLE IF NOT EXISTS series_follows (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        series_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_series_follow (user_id, series_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (series_id) REFERENCES series(id)
    )";
    $conn->exec($sql);
    echo "Series Follows table created<br>";
    
    // Create Bookmarks table
    $sql = "CREATE TABLE IF NOT EXISTS bookmarks (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        story_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_bookmark (user_id, story_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (story_id) REFERENCES stories(id)
    )";
    $conn->exec($sql);
    echo "Bookmarks table created<br>";
    
    // Create Challenges table
    $sql = "CREATE TABLE IF NOT EXISTS challenges (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        start_date TIMESTAMP NOT NULL,
        end_date TIMESTAMP NOT NULL,
        created_by INT NOT NULL,
        status ENUM('upcoming', 'active', 'completed') DEFAULT 'upcoming',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )";
    $conn->exec($sql);
    echo "Challenges table created<br>";
    
    // Create ChallengeEntries table
    $sql = "CREATE TABLE IF NOT EXISTS challenge_entries (
        id INT PRIMARY KEY AUTO_INCREMENT,
        challenge_id INT NOT NULL,
        user_id INT NOT NULL,
        story_id INT NOT NULL,
        submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        rank INT DEFAULT NULL,
        FOREIGN KEY (challenge_id) REFERENCES challenges(id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (story_id) REFERENCES stories(id)
    )";
    $conn->exec($sql);
    echo "Challenge Entries table created<br>";
    
    // Create Achievements table
    $sql = "CREATE TABLE IF NOT EXISTS achievements (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        icon VARCHAR(50),
        requirement VARCHAR(255)
    )";
    $conn->exec($sql);
    echo "Achievements table created<br>";
    
    // Create UserAchievements table
    $sql = "CREATE TABLE IF NOT EXISTS user_achievements (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        achievement_id INT NOT NULL,
        earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_achievement (user_id, achievement_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (achievement_id) REFERENCES achievements(id)
    )";
    $conn->exec($sql);
    echo "User Achievements table created<br>";
    
    // Insert default categories
    $categories = [
        ['name' => 'Fantasy', 'description' => 'Stories with magical elements, mythical creatures, and fantastical worlds', 'icon' => 'fa-magic'],
        ['name' => 'Science Fiction', 'description' => 'Stories based on scientific principles and technology', 'icon' => 'fa-rocket'],
        ['name' => 'Mystery', 'description' => 'Stories with puzzles, secrets, and suspense', 'icon' => 'fa-magnifying-glass'],
        ['name' => 'Romance', 'description' => 'Stories focusing on relationships and love', 'icon' => 'fa-heart'],
        ['name' => 'Horror', 'description' => 'Stories designed to frighten and unsettle', 'icon' => 'fa-ghost'],
        ['name' => 'Thriller', 'description' => 'Stories with high tension and excitement', 'icon' => 'fa-bolt'],
        ['name' => 'Historical Fiction', 'description' => 'Stories set in the past with historical elements', 'icon' => 'fa-landmark'],
        ['name' => 'Poetry', 'description' => 'Verse and poetic expressions', 'icon' => 'fa-feather'],
        ['name' => 'Non-Fiction', 'description' => 'Essays, memoirs, and factual writing', 'icon' => 'fa-book']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO categories (name, description, icon) VALUES (:name, :description, :icon)");
    foreach ($categories as $category) {
        $stmt->bindParam(':name', $category['name']);
        $stmt->bindParam(':description', $category['description']);
        $stmt->bindParam(':icon', $category['icon']);
        $stmt->execute();
    }
    echo "Default categories inserted<br>";
    
    // Insert default achievements
    $achievements = [
        ['name' => 'Prolific Writer', 'description' => 'Published 25+ stories', 'icon' => 'fa-pen-fancy', 'requirement' => 'stories_count>=25'],
        ['name' => 'Contest Winner', 'description' => 'Won a writing challenge', 'icon' => 'fa-trophy', 'requirement' => 'challenge_wins>=1'],
        ['name' => 'Reader\'s Favorite', 'description' => 'Received 1000+ likes', 'icon' => 'fa-heart', 'requirement' => 'likes_count>=1000'],
        ['name' => 'Engaged Author', 'description' => 'Responded to 500+ comments', 'icon' => 'fa-comments', 'requirement' => 'comment_responses>=500'],
        ['name' => 'Series Completer', 'description' => 'Finished a multi-part series', 'icon' => 'fa-book', 'requirement' => 'completed_series>=1'],
        ['name' => 'Dedicated Writer', 'description' => 'Posted for 30 consecutive days', 'icon' => 'fa-calendar-alt', 'requirement' => 'consecutive_days>=30'],
        ['name' => 'Community Pillar', 'description' => '1000+ followers milestone', 'icon' => 'fa-users', 'requirement' => 'followers_count>=1000'],
        ['name' => 'Rising Star', 'description' => 'Featured on homepage', 'icon' => 'fa-star', 'requirement' => 'featured_count>=1']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO achievements (name, description, icon, requirement) VALUES (:name, :description, :icon, :requirement)");
    foreach ($achievements as $achievement) {
        $stmt->bindParam(':name', $achievement['name']);
        $stmt->bindParam(':description', $achievement['description']);
        $stmt->bindParam(':icon', $achievement['icon']);
        $stmt->bindParam(':requirement', $achievement['requirement']);
        $stmt->execute();
    }
    echo "Default achievements inserted<br>";
    
    echo "<strong>Database setup complete!</strong>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
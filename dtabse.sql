-- Create database
CREATE DATABASE IF NOT EXISTS storynest;
USE storynest;

-- Users table
CREATE TABLE IF NOT EXISTS users (
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
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    icon VARCHAR(50)
);

-- Series table
CREATE TABLE IF NOT EXISTS series (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('ongoing', 'completed', 'hiatus') DEFAULT 'ongoing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Stories table
CREATE TABLE IF NOT EXISTS stories (
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
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (series_id) REFERENCES series(id)
);

-- Comments table
CREATE TABLE IF NOT EXISTS comments (
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
);

-- Likes table
CREATE TABLE IF NOT EXISTS likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    story_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, story_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (story_id) REFERENCES stories(id)
);

-- Follows table (for user following other users)
CREATE TABLE IF NOT EXISTS follows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    follower_id INT NOT NULL,
    followed_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (follower_id, followed_id),
    FOREIGN KEY (follower_id) REFERENCES users(id),
    FOREIGN KEY (followed_id) REFERENCES users(id)
);

-- SeriesFollows table (for users following series)
CREATE TABLE IF NOT EXISTS series_follows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    series_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_series_follow (user_id, series_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (series_id) REFERENCES series(id)
);

-- Bookmarks table
CREATE TABLE IF NOT EXISTS bookmarks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    story_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_bookmark (user_id, story_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (story_id) REFERENCES stories(id)
);

-- Challenges table
CREATE TABLE IF NOT EXISTS challenges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    created_by INT NOT NULL,
    status ENUM('upcoming', 'active', 'completed') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ChallengeEntries table
CREATE TABLE IF NOT EXISTS challenge_entries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    challenge_id INT NOT NULL,
    user_id INT NOT NULL,
    story_id INT NOT NULL,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rank INT DEFAULT NULL,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (story_id) REFERENCES stories(id)
);

-- Achievements table
CREATE TABLE IF NOT EXISTS achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(50),
    requirement VARCHAR(255)
);

-- UserAchievements table
CREATE TABLE IF NOT EXISTS user_achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (achievement_id) REFERENCES achievements(id)
);

-- Insert default categories
INSERT INTO categories (name, description, icon) VALUES 
('Fantasy', 'Stories with magical elements, mythical creatures, and fantastical worlds', 'fa-magic'),
('Science Fiction', 'Stories based on scientific principles and technology', 'fa-rocket'),
('Mystery', 'Stories with puzzles, secrets, and suspense', 'fa-magnifying-glass'),
('Romance', 'Stories focusing on relationships and love', 'fa-heart'),
('Horror', 'Stories designed to frighten and unsettle', 'fa-ghost'),
('Thriller', 'Stories with high tension and excitement', 'fa-bolt'),
('Historical Fiction', 'Stories set in the past with historical elements', 'fa-landmark'),
('Poetry', 'Verse and poetic expressions', 'fa-feather'),
('Non-Fiction', 'Essays, memoirs, and factual writing', 'fa-book');

-- Insert default achievements
INSERT INTO achievements (name, description, icon, requirement) VALUES
('Prolific Writer', 'Published 25+ stories', 'fa-pen-fancy', 'stories_count>=25'),
('Contest Winner', 'Won a writing challenge', 'fa-trophy', 'challenge_wins>=1'),
('Reader s Favorite', 'Received 1000+ likes', 'fa-heart', 'likes_count>=1000'),
('Engaged Author', 'Responded to 500+ comments', 'fa-comments', 'comment_responses>=500'),
('Series Completer', 'Finished a multi-part series', 'fa-book', 'completed_series>=1'),
('Dedicated Writer', 'Posted for 30 consecutive days', 'fa-calendar-alt', 'consecutive_days>=30'),
('Community Pillar', '1000+ followers milestone', 'fa-users', 'followers_count>=1000'),
('Rising Star', 'Featured on homepage', 'fa-star', 'featured_count>=1');
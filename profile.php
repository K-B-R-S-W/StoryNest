<?php
// Include config file
require_once "config.php";

// Check if username is set
if (!isset($_GET['username']) || empty($_GET['username'])) {
    setFlashMessage("error", "User not found.");
    redirect("index.php");
    exit;
}

$username = $_GET['username'];

// Get user data
try {
    $stmt = $conn->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM stories WHERE user_id = u.id AND status = 'published') as stories_count,
               (SELECT COUNT(*) FROM follows WHERE followed_id = u.id) as followers_count,
               (SELECT COUNT(*) FROM follows WHERE follower_id = u.id) as following_count,
               (SELECT SUM(views) FROM stories WHERE user_id = u.id AND status = 'published') as total_views,
               (SELECT SUM(word_count) FROM stories WHERE user_id = u.id AND status = 'published') as total_words
        FROM users u
        WHERE u.username = :username
    ");
    
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setFlashMessage("error", "User not found.");
        redirect("index.php");
        exit;
    }
    
    $user = $stmt->fetch();
    
    // Get user's published stories
    $stmt = $conn->prepare("
        SELECT s.*, c.name as category_name, c.icon as category_icon,
               (SELECT COUNT(*) FROM likes WHERE story_id = s.id) as likes_count,
               (SELECT COUNT(*) FROM comments WHERE story_id = s.id) as comments_count,
               (SELECT COUNT(*) FROM bookmarks WHERE story_id = s.id) as bookmarks_count,
               (SELECT title FROM series WHERE id = s.series_id) as series_title
        FROM stories s
        LEFT JOIN categories c ON s.category_id = c.id
        WHERE s.user_id = :user_id AND s.status = 'published'
        ORDER BY s.created_at DESC
        LIMIT 10
    ");
    $stmt->bindParam(":user_id", $user['id']);
    $stmt->execute();
    $stories = $stmt->fetchAll();
    
    // Get user's achievements
    $stmt = $conn->prepare("
        SELECT a.* 
        FROM user_achievements ua
        JOIN achievements a ON ua.achievement_id = a.id
        WHERE ua.user_id = :user_id
        ORDER BY ua.earned_at DESC
    ");
    $stmt->bindParam(":user_id", $user['id']);
    $stmt->execute();
    $achievements = $stmt->fetchAll();
    
    // Get user's series
    $stmt = $conn->prepare("
        SELECT s.*, 
               (SELECT COUNT(*) FROM stories WHERE series_id = s.id AND status = 'published') as story_count,
               (SELECT COUNT(*) FROM series_follows WHERE series_id = s.id) as followers_count
        FROM series s
        WHERE s.user_id = :user_id
        ORDER BY s.created_at DESC
        LIMIT 5
    ");
    $stmt->bindParam(":user_id", $user['id']);
    $stmt->execute();
    $series_list = $stmt->fetchAll();
    
    // Check if current user is following this user
    $is_following = false;
    if (isLoggedIn() && $_SESSION['user_id'] != $user['id']) {
        $stmt = $conn->prepare("
            SELECT id FROM follows 
            WHERE follower_id = :follower_id AND followed_id = :followed_id
        ");
        $stmt->bindParam(":follower_id", $_SESSION['user_id']);
        $stmt->bindParam(":followed_id", $user['id']);
        $stmt->execute();
        $is_following = ($stmt->rowCount() > 0);
    }
    
} catch(PDOException $e) {
    setFlashMessage("error", "Error retrieving user profile: " . $e->getMessage());
    redirect("index.php");
    exit;
}

// Set page title
$page_title = $user['display_name'] ?: $user['username'];

// Include header
include_once "includes/header.php";
?>

<section class="profile-header py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-3 text-center mb-4 mb-lg-0">
                <img src="<?php echo !empty($user['profile_image']) ? 'uploads/avatars/' . htmlspecialchars($user['profile_image']) : '/api/placeholder/200/200'; ?>" alt="<?php echo htmlspecialchars($user['username']); ?>'s profile picture" class="profile-avatar mb-3">
                
                <h2 class="mb-1"><?php echo htmlspecialchars($user['display_name'] ?: $user['username']); ?></h2>
                <p class="text-muted mb-2">@<?php echo htmlspecialchars($user['username']); ?></p>
                
                <?php if (isLoggedIn() && $_SESSION['user_id'] != $user['id']): ?>
                    <div class="d-flex justify-content-center mb-3">
                        <button class="btn <?php echo $is_following ? 'btn-primary' : 'btn-outline-primary'; ?> me-2 follow-btn" data-user-id="<?php echo $user['id']; ?>">
                            <?php echo $is_following ? '<i class="fas fa-user-check me-1"></i> Following' : '<i class="fas fa-user-plus me-1"></i> Follow'; ?>
                        </button>
                        <a href="message.php?to=<?php echo urlencode($user['username']); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-envelope me-1"></i> Message
                        </a>
                    </div>
                <?php elseif (isLoggedIn() && $_SESSION['user_id'] == $user['id']): ?>
                    <a href="settings.php" class="btn btn-outline-primary mb-3">
                        <i class="fas fa-edit me-1"></i> Edit Profile
                    </a>
                <?php endif; ?>
                
                <div class="profile-stats d-flex justify-content-center mt-3">
                    <div class="stat-item text-center mx-3">
                        <div class="stat-value"><?php echo $user['stories_count']; ?></div>
                        <div class="stat-label">Stories</div>
                    </div>
                    <div class="stat-item text-center mx-3">
                        <div class="stat-value"><?php echo $user['followers_count']; ?></div>
                        <div class="stat-label">Followers</div>
                    </div>
                    <div class="stat-item text-center mx-3">
                        <div class="stat-value"><?php echo $user['following_count']; ?></div>
                        <div class="stat-label">Following</div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-9">
                <div class="editor-container">
                    <div class="bio-section mb-4">
                        <h3 class="mb-3">About Me</h3>
                        <?php if (!empty($user['bio'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted">This writer hasn't added a bio yet.</p>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['website']) || !empty($user['twitter']) || !empty($user['instagram'])): ?>
                            <div class="social-links mt-3">
                                <?php if (!empty($user['website'])): ?>
                                    <a href="<?php echo htmlspecialchars($user['website']); ?>" class="btn btn-sm btn-outline-secondary me-2" target="_blank">
                                        <i class="fas fa-globe me-1"></i> Website
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($user['twitter'])): ?>
                                    <a href="https://twitter.com/<?php echo htmlspecialchars($user['twitter']); ?>" class="btn btn-sm btn-outline-secondary me-2" target="_blank">
                                        <i class="fab fa-twitter me-1"></i> Twitter
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($user['instagram'])): ?>
                                    <a href="https://instagram.com/<?php echo htmlspecialchars($user['instagram']); ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                        <i class="fab fa-instagram me-1"></i> Instagram
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="writer-stats mb-4">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-eye text-primary mb-2" style="font-size: 2rem;"></i>
                                        <h5 class="card-title"><?php echo number_format($user['total_views'] ?: 0); ?></h5>
                                        <p class="card-text">Total Views</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-book text-primary mb-2" style="font-size: 2rem;"></i>
                                        <h5 class="card-title"><?php echo number_format($user['total_words'] ?: 0); ?></h5>
                                        <p class="card-text">Words Written</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-calendar-alt text-primary mb-2" style="font-size: 2rem;"></i>
                                        <h5 class="card-title"><?php echo timeAgo($user['created_at']); ?></h5>
                                        <p class="card-text">Joined</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-trophy text-primary mb-2" style="font-size: 2rem;"></i>
                                        <h5 class="card-title"><?php echo count($achievements); ?></h5>
                                        <p class="card-text">Achievements</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container mb-5">
    <ul class="nav nav-tabs" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="stories-tab" data-bs-toggle="tab" data-bs-target="#stories" type="button" role="tab" aria-controls="stories" aria-selected="true">Stories</button>
        </li>
        <?php if (count($series_list) > 0): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="series-tab" data-bs-toggle="tab" data-bs-target="#series" type="button" role="tab" aria-controls="series" aria-selected="false">Series</button>
        </li>
        <?php endif; ?>
        <?php if (count($achievements) > 0): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="achievements-tab" data-bs-toggle="tab" data-bs-target="#achievements" type="button" role="tab" aria-controls="achievements" aria-selected="false">Achievements</button>
        </li>
        <?php endif; ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab" aria-controls="activity" aria-selected="false">Activity</button>
        </li>
    </ul>
    
    <div class="tab-content" id="profileTabsContent">
        <!-- Stories Tab -->
        <div class="tab-pane fade show active" id="stories" role="tabpanel" aria-labelledby="stories-tab">
            <div class="editor-container mt-4">
                <?php if (count($stories) > 0): ?>
                    <div class="row">
                        <?php foreach ($stories as $story): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="badge bg-light text-dark">
                                                <i class="fas <?php echo htmlspecialchars($story['category_icon']); ?> me-1"></i>
                                                <?php echo htmlspecialchars($story['category_name']); ?>
                                            </span>
                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($story['created_at'])); ?></small>
                                        </div>
                                        
                                        <h4 class="card-title">
                                            <a href="story.php?id=<?php echo $story['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($story['title']); ?>
                                            </a>
                                        </h4>
                                        
                                        <?php if (!empty($story['series_title'])): ?>
                                            <p class="series-info text-muted mb-2">
                                                <i class="fas fa-books me-1"></i> Part of series: 
                                                <a href="series.php?id=<?php echo $story['series_id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($story['series_title']); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <p class="card-text"><?php echo htmlspecialchars(substr(strip_tags($story['excerpt'] ?: $story['content']), 0, 150) . '...'); ?></p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="me-2"><i class="fas fa-eye me-1"></i> <?php echo $story['views']; ?></span>
                                                <span class="me-2"><i class="fas fa-heart me-1"></i> <?php echo $story['likes_count']; ?></span>
                                                <span><i class="fas fa-comment me-1"></i> <?php echo $story['comments_count']; ?></span>
                                            </div>
                                            <a href="story.php?id=<?php echo $story['id']; ?>" class="btn btn-sm btn-outline-primary">Read</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($user['stories_count'] > count($stories)): ?>
                        <div class="text-center mt-3">
                            <a href="user-stories.php?username=<?php echo urlencode($user['username']); ?>" class="btn btn-outline-primary">View All Stories</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-book text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <h4>No stories yet</h4>
                        <p class="text-muted">This writer hasn't published any stories yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Series Tab -->
        <?php if (count($series_list) > 0): ?>
        <div class="tab-pane fade" id="series" role="tabpanel" aria-labelledby="series-tab">
            <div class="editor-container mt-4">
                <div class="row">
                    <?php foreach ($series_list as $series): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <a href="series.php?id=<?php echo $series['id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($series['title']); ?>
                                        </a>
                                    </h4>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="badge bg-<?php echo $series['status'] == 'ongoing' ? 'primary' : ($series['status'] == 'completed' ? 'success' : 'warning'); ?>">
                                            <?php echo ucfirst($series['status']); ?>
                                        </span>
                                        <small class="text-muted">Started <?php echo date('M j, Y', strtotime($series['created_at'])); ?></small>
                                    </div>
                                    <p class="card-text"><?php echo htmlspecialchars(substr($series['description'], 0, 200) . (strlen($series['description']) > 200 ? '...' : '')); ?></p>
                                    <div class="series-meta mt-3">
                                        <div class="d-flex justify-content-between">
                                            <span><i class="fas fa-book me-1"></i> <?php echo $series['story_count']; ?> <?php echo $series['story_count'] == 1 ? 'chapter' : 'chapters'; ?></span>
                                            <span><i class="fas fa-users me-1"></i> <?php echo $series['followers_count']; ?> followers</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="series.php?id=<?php echo $series['id']; ?>" class="btn btn-sm btn-outline-primary">View Series</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Achievements Tab -->
        <?php if (count($achievements) > 0): ?>
        <div class="tab-pane fade" id="achievements" role="tabpanel" aria-labelledby="achievements-tab">
            <div class="editor-container mt-4">
                <div class="row">
                    <?php foreach ($achievements as $achievement): ?>
                        <div class="col-md-3 col-sm-6 mb-4 text-center">
                            <div class="achievement-badge">
                                <i class="fas <?php echo htmlspecialchars($achievement['icon']); ?>"></i>
                            </div>
                            <h5><?php echo htmlspecialchars($achievement['name']); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($achievement['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Activity Tab -->
        <div class="tab-pane fade" id="activity" role="tabpanel" aria-labelledby="activity-tab">
            <div class="editor-container mt-4">
                <?php
                // In a real implementation, this would query recent user activity
                // like comments, likes, followers, etc.
                ?>
                <div id="activity-feed" class="activity-feed">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading activity...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-avatar {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        border: 5px solid white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        object-fit: cover;
    }
    
    .profile-stats {
        margin-top: 1.5rem;
    }
    
    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: var(--light-text);
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .achievement-badge {
        background-color: var(--primary-color);
        color: white;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 2rem;
    }
    
    .activity-item {
        padding: 1rem 0;
        border-bottom: 1px solid #eee;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .dark-mode .activity-item {
        border-color: #444;
    }
    
    .dark-mode .achievement-badge {
        background-color: #444;
        color: #a395ff;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Follow button functionality
        const followBtn = document.querySelector('.follow-btn');
        if (followBtn) {
            followBtn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                
                fetch('ajax/follow.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${userId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.action === 'followed') {
                            this.innerHTML = '<i class="fas fa-user-check me-1"></i> Following';
                            this.classList.remove('btn-outline-primary');
                            this.classList.add('btn-primary');
                            
                            // Update follower count
                            const followersStat = document.querySelector('.stat-item:nth-child(2) .stat-value');
                            followersStat.textContent = (parseInt(followersStat.textContent) + 1).toString();
                        } else {
                            this.innerHTML = '<i class="fas fa-user-plus me-1"></i> Follow';
                            this.classList.remove('btn-primary');
                            this.classList.add('btn-outline-primary');
                            
                            // Update follower count
                            const followersStat = document.querySelector('.stat-item:nth-child(2) .stat-value');
                            followersStat.textContent = (parseInt(followersStat.textContent) - 1).toString();
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        }
        
        // Simulate loading activity feed (this would be an AJAX call in a real implementation)
        setTimeout(function() {
            const activityFeed = document.getElementById('activity-feed');
            if (activityFeed) {
                // Get user ID and username from the page
                const userId = <?php echo $user['id']; ?>;
                const username = "<?php echo htmlspecialchars($user['username']); ?>";
                const displayName = "<?php echo htmlspecialchars($user['display_name'] ?: $user['username']); ?>";
                
                // Create sample activity items
                let activityHtml = '';
                
                // No activity state
                if (activityHtml === '') {
                    activityHtml = `
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-chart-line text-muted" style="font-size: 3rem;"></i>
                            </div>
                            <h4>No recent activity</h4>
                            <p class="text-muted">Activity will appear here as ${displayName} interacts with the community</p>
                        </div>
                    `;
                }
                
                activityFeed.innerHTML = activityHtml;
            }
        }, 1500);
    });
</script>

<?php
// Include footer
include_once "includes/footer.php";
?>
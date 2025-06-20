<?php
// Include config file
require_once "config.php";

// Set page title
$page_title = "Home";

// Get featured stories
try {
    $stmt = $conn->prepare("
        SELECT s.*, u.username, u.display_name, u.profile_image, c.name as category_name 
        FROM stories s
        JOIN users u ON s.user_id = u.id
        JOIN categories c ON s.category_id = c.id
        WHERE s.status = 'published'
        ORDER BY s.views DESC, s.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $featured_stories = $stmt->fetchAll();
} catch(PDOException $e) {
    $featured_stories = [];
}

// Get recent stories
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
        LIMIT 6
    ");
    $stmt->execute();
    $recent_stories = $stmt->fetchAll();
} catch(PDOException $e) {
    $recent_stories = [];
}

// Get active challenges
try {
    $stmt = $conn->prepare("
        SELECT c.*, u.username, u.display_name, 
               (SELECT COUNT(*) FROM challenge_entries WHERE challenge_id = c.id) as entries_count
        FROM challenges c
        JOIN users u ON c.created_by = u.id
        WHERE c.status = 'active'
        ORDER BY c.end_date ASC
        LIMIT 1
    ");
    $stmt->execute();
    $active_challenge = $stmt->fetch();
} catch(PDOException $e) {
    $active_challenge = null;
}

// Get popular categories
try {
    $stmt = $conn->prepare("
        SELECT c.*, COUNT(s.id) as story_count
        FROM categories c
        LEFT JOIN stories s ON c.id = s.category_id AND s.status = 'published'
        GROUP BY c.id
        ORDER BY story_count DESC
        LIMIT 4
    ");
    $stmt->execute();
    $popular_categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $popular_categories = [];
}

// Get community activity (recent stories, comments, etc.)
try {
    $stmt = $conn->prepare("
        (SELECT 'new_story' as type, s.id, s.title, s.created_at, u.id as user_id, u.username, u.display_name, u.profile_image, c.name as category_name, NULL as story_title
         FROM stories s
         JOIN users u ON s.user_id = u.id
         JOIN categories c ON s.category_id = c.id
         WHERE s.status = 'published')
        UNION
        (SELECT 'new_comment' as type, cm.id, cm.content as title, cm.created_at, u.id as user_id, u.username, u.display_name, u.profile_image, NULL as category_name, s.title as story_title
         FROM comments cm
         JOIN users u ON cm.user_id = u.id
         JOIN stories s ON cm.story_id = s.id
         WHERE s.status = 'published' AND cm.parent_id IS NULL)
        ORDER BY created_at DESC
        LIMIT 4
    ");
    $stmt->execute();
    $community_activity = $stmt->fetchAll();
} catch(PDOException $e) {
    $community_activity = [];
}

// Include header
include_once "includes/header.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/home.css">
    <title>StoryNest</title>
    <body>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="hero-title mb-4">Share Your Stories, Connect with Writers</h1>
                <p class="lead mb-4">A creative community for writers to share, grow, and inspire. Join thousands of storytellers from around the world.</p>
                <a href="<?php echo isLoggedIn() ? 'create-story.php' : 'register.php'; ?>" class="btn btn-primary me-2">
                    <?php echo isLoggedIn() ? 'Start Writing' : 'Join Now'; ?>
                </a>
                <a href="categories.php" class="btn btn-outline-primary">Explore Stories</a>
            </div>
            <div class="col-lg-6">
                <!-- Bootstrap Carousel Slider -->
                <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000">
                  <div class="carousel-inner rounded">
                    <div class="carousel-item active">
                      <img src="assets/images/women.jpg" class="d-block w-100 hero-slider-img" alt="Woman writing 1">
                    </div>
                    <div class="carousel-item">
                      <img src="assets/images/women2.jpg" class="d-block w-100 hero-slider-img" alt="Woman writing 2">
                    </div>
                  </div>
                </div>
                <!-- End Carousel Slider -->
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="container py-5">
    <!-- Categories Section -->
    <section class="mb-5">
        <h2 class="text-center mb-4">Explore Categories</h2>
        <div class="row align-items-stretch">
            <?php foreach ($popular_categories as $category): ?>
                <div class="col-md-3 col-sm-6 mb-4 d-flex">
                    <div class="card text-center p-4 h-100 w-100">
                        <div class="category-icon mx-auto">
                            <i class="fas <?php echo htmlspecialchars($category['icon']); ?>"></i>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars(substr($category['description'], 0, 70) . (strlen($category['description']) > 70 ? '...' : '')); ?></p>
                        <a href="category.php?id=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline-primary">Browse Stories</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="categories.php" class="btn btn-outline-primary">View All Categories</a>
        </div>
    </section>

    <!-- Featured Stories -->
    <section class="mb-5">
        <h2 class="mb-4">Featured Stories</h2>
        <div class="row">
            <div class="col-lg-8">
                <?php if (isset($featured_stories[0])): ?>
                    <div class="card featured-story">
                        <h4><?php echo htmlspecialchars($featured_stories[0]['title']); ?></h4>
                        <p class="story-meta mb-3">
                            <span class="me-3"><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($featured_stories[0]['display_name'] ?: $featured_stories[0]['username']); ?></span>
                            <span class="me-3"><i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($featured_stories[0]['category_name']); ?></span>
                            <span><i class="fas fa-clock me-1"></i> <?php echo ceil($featured_stories[0]['word_count'] / 200); ?> min read</span>
                        </p>
                        <p><?php echo htmlspecialchars($featured_stories[0]['excerpt'] ?: substr(strip_tags($featured_stories[0]['content']), 0, 200) . '...'); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="story.php?id=<?php echo $featured_stories[0]['id']; ?>" class="btn btn-primary">Continue Reading</a>
                            <div>
                                <span class="me-3"><i class="fas fa-heart me-1"></i> <?php echo isset($featured_stories[0]['likes_count']) ? $featured_stories[0]['likes_count'] : '0'; ?></span>
                                <span><i class="fas fa-comment me-1"></i> <?php echo isset($featured_stories[0]['comments_count']) ? $featured_stories[0]['comments_count'] : '0'; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <?php 
                // Skip the first story since it's already displayed
                $side_stories = array_slice($featured_stories, 1, 2);
                foreach ($side_stories as $story): 
                ?>
                    <div class="card mb-4 p-3">
                        <h5><?php echo htmlspecialchars($story['title']); ?></h5>
                        <p class="story-meta mb-2">
                            <span class="me-2"><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($story['display_name'] ?: $story['username']); ?></span>
                            <span><i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($story['category_name']); ?></span>
                        </p>
                        <p class="mb-2"><?php echo htmlspecialchars(substr(strip_tags($story['excerpt'] ?: $story['content']), 0, 100) . '...'); ?></p>
                        <a href="story.php?id=<?php echo $story['id']; ?>" class="btn btn-sm btn-outline-primary">Read More</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Writing Challenges -->
    <section class="mb-5">
        <h2 class="mb-4">Weekly Challenges</h2>
        <?php if ($active_challenge): ?>
            <div class="card p-4">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="mb-3"><?php echo htmlspecialchars($active_challenge['title']); ?></h4>
                        <p><?php echo htmlspecialchars($active_challenge['description']); ?></p>
                        <p class="story-meta mb-3">
                            <span class="me-3"><i class="fas fa-calendar me-1"></i> Ends in <?php echo ceil((strtotime($active_challenge['end_date']) - time()) / (60*60*24)); ?> days</span>
                            <span><i class="fas fa-users me-1"></i> <?php echo $active_challenge['entries_count']; ?> participants</span>
                        </p>
                        <a href="challenge.php?id=<?php echo $active_challenge['id']; ?>" class="btn btn-primary">Join Challenge</a>
                    </div>
                    <div class="col-md-4">
                        <img src="/api/placeholder/300/200" alt="Writing challenge" class="img-fluid rounded">
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card p-4">
                <div class="text-center">
                    <h5>No active challenges right now</h5>
                    <p>Check back soon for new writing challenges, or browse past challenges for inspiration.</p>
                    <a href="challenges.php" class="btn btn-outline-primary">View All Challenges</a>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- Recent Stories -->
    <section class="mb-5">
        <h2 class="mb-4">Recent Stories</h2>
        <div class="row">
            <?php foreach ($recent_stories as $story): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($story['title']); ?></h5>
                            <p class="story-meta mb-3">
                                <span class="me-3"><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($story['display_name'] ?: $story['username']); ?></span>
                                <span><i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($story['category_name']); ?></span>
                            </p>
                            <p class="card-text"><?php echo htmlspecialchars(substr(strip_tags($story['excerpt'] ?: $story['content']), 0, 120) . '...'); ?></p>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="story.php?id=<?php echo $story['id']; ?>" class="btn btn-sm btn-outline-primary">Read</a>
                                <div>
                                    <span class="me-2"><i class="fas fa-heart me-1"></i> <?php echo $story['likes_count']; ?></span>
                                    <span><i class="fas fa-comment me-1"></i> <?php echo $story['comments_count']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-3">
            <a href="stories.php" class="btn btn-outline-primary">View More Stories</a>
        </div>
    </section>

    <!-- Community Activity -->
    <section>
        <h2 class="mb-4">Community Activity</h2>
        <div class="row">
            <?php foreach ($community_activity as $activity): ?>
                <div class="col-md-6 mb-3">
                    <div class="card p-3">
                        <div class="d-flex">
                            <img src="<?php echo !empty($activity['profile_image']) ? 'uploads/avatars/' . htmlspecialchars($activity['profile_image']) : '/api/placeholder/48/48'; ?>" class="rounded-circle me-3" alt="User avatar" width="48" height="48">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($activity['display_name'] ?: $activity['username']); ?></h6>
                                <?php if ($activity['type'] == 'new_story'): ?>
                                    <p class="story-meta mb-2">Posted a new story in <?php echo htmlspecialchars($activity['category_name']); ?></p>
                                    <h5>"<?php echo htmlspecialchars($activity['title']); ?>"</h5>
                                    <a href="story.php?id=<?php echo $activity['id']; ?>" class="btn btn-sm btn-outline-primary">Read Now</a>
                                <?php elseif ($activity['type'] == 'new_comment'): ?>
                                    <p class="story-meta mb-2">Commented on "<?php echo htmlspecialchars($activity['story_title']); ?>"</p>
                                    <p class="mb-2"><?php echo htmlspecialchars(substr(strip_tags($activity['title']), 0, 80) . (strlen(strip_tags($activity['title'])) > 80 ? '...' : '')); ?></p>
                                    <a href="story.php?id=<?php echo $activity['id']; ?>#comments" class="btn btn-sm btn-outline-primary">View Comment</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-3">
            <a href="community.php" class="btn btn-outline-primary">View More Activity</a>
        </div>
    </section>
</div>
<?php
// Include footer - no spaces or extra content after this
include_once "includes/footer.php";
?>

                                </body>
                                </html>
                                
<?php
// Include config file
require_once "config.php";

// Set page title
$page_title = "Categories";

// Get all categories with story counts
try {
    $stmt = $conn->prepare("
        SELECT c.*, COUNT(s.id) as story_count
        FROM categories c
        LEFT JOIN stories s ON c.id = s.category_id AND s.status = 'published'
        GROUP BY c.id
        ORDER BY c.name
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

// Get selected category if provided
$selected_category = null;
$category_stories = [];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $category_id = $_GET['id'];
    
    try {
        // Get category details
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->bindParam(':id', $category_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $selected_category = $stmt->fetch();
            
            // Get stories in this category
            $stmt = $conn->prepare("
                SELECT s.*, u.username, u.display_name, u.profile_image,
                       (SELECT COUNT(*) FROM likes WHERE story_id = s.id) as likes_count,
                       (SELECT COUNT(*) FROM comments WHERE story_id = s.id) as comments_count
                FROM stories s
                JOIN users u ON s.user_id = u.id
                WHERE s.category_id = :category_id AND s.status = 'published'
                ORDER BY s.created_at DESC
                LIMIT 20
            ");
            $stmt->bindParam(':category_id', $category_id);
            $stmt->execute();
            $category_stories = $stmt->fetchAll();
        }
    } catch(PDOException $e) {
        // Silently fail and show empty list
    }
}

// Include header
include_once "includes/header.php";
?>

<div class="container py-5">
    <h1 class="mb-4">Story Categories</h1>
    
    <div class="row">
        <div class="col-lg-3">
            <div class="sidebar-container">
                <h4 class="mb-3">Browse Categories</h4>
                <div class="list-group">
                    <?php foreach ($categories as $category): ?>
                        <a href="categories.php?id=<?php echo $category['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo (isset($selected_category) && $selected_category['id'] == $category['id']) ? 'active' : ''; ?>">
                            <div>
                                <i class="fas <?php echo htmlspecialchars($category['icon']); ?> me-2"></i>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </div>
                            <span class="badge bg-primary rounded-pill"><?php echo $category['story_count']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <?php if ($selected_category): ?>
                <div class="editor-container">
                    <div class="d-flex align-items-center mb-4">
                        <div class="category-icon me-3">
                            <i class="fas <?php echo htmlspecialchars($selected_category['icon']); ?>"></i>
                        </div>
                        <div>
                            <h2 class="mb-1"><?php echo htmlspecialchars($selected_category['name']); ?></h2>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($selected_category['description']); ?></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <?php if (count($category_stories) > 0): ?>
                        <div class="row">
                            <?php foreach ($category_stories as $story): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($story['title']); ?></h5>
                                            <p class="story-meta mb-3">
                                                <span class="me-3"><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($story['display_name'] ?: $story['username']); ?></span>
                                                <span><i class="fas fa-calendar me-1"></i> <?php echo date('M j, Y', strtotime($story['created_at'])); ?></span>
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
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-book-open text-muted" style="font-size: 3rem;"></i>
                            </div>
                            <h4>No stories in this category yet</h4>
                            <p class="text-muted">Be the first to publish a story in this category!</p>
                            <?php if (isLoggedIn()): ?>
                                <a href="create-story.php" class="btn btn-primary mt-2">Write a Story</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary mt-2">Sign In to Write</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <h3>Select a category from the list</h3>
                    <p class="text-muted">Choose a category to see stories in that genre</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .category-icon {
        background-color: var(--primary-color);
        color: white;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .dark-mode .list-group-item {
        background-color: #333;
        border-color: #444;
        color: #f8f9fa;
    }
    
    .dark-mode .list-group-item.active {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
</style>

<?php
// Include footer
include_once "includes/footer.php";
?>
<?php
// Include config file
require_once "config.php";

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage("error", "You must be logged in to view your bookmarks.");
    redirect("login.php");
    exit;
}

// Set page title
$page_title = "My Bookmarks";

// Get user's bookmarked stories
try {
    $stmt = $conn->prepare("
     SELECT s.*, u.username, u.display_name, u.profile_image, c.name as category_name,
               b.created_at as bookmarked_at,
               (SELECT COUNT(*) FROM likes WHERE story_id = s.id) as likes_count,
               (SELECT COUNT(*) FROM comments WHERE story_id = s.id) as comments_count
        FROM bookmarks b
        JOIN stories s ON b.story_id = s.id
        JOIN users u ON s.user_id = u.id
        JOIN categories c ON s.category_id = c.id
        WHERE b.user_id = :user_id AND s.status = 'published'
        ORDER BY b.created_at DESC
    ");
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();
    $bookmarks = $stmt->fetchAll();
} catch(PDOException $e) {
    setFlashMessage("error", "Error retrieving bookmarks: " . $e->getMessage());
    $bookmarks = [];
}

// Include header
include_once "includes/header.php";
?>

<div class="container py-5">
    <h1 class="mb-4">My Bookmarks</h1>
    
    <div class="editor-container">
        <?php if (count($bookmarks) > 0): ?>
            <div class="row">
                <?php foreach ($bookmarks as $bookmark): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h4 class="card-title">
                                    <a href="story.php?id=<?php echo $bookmark['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($bookmark['title']); ?>
                                    </a>
                                </h4>
                                <div class="mb-3">
                                    <a href="profile.php?username=<?php echo urlencode($bookmark['username']); ?>" class="d-flex align-items-center text-decoration-none mb-2">
                                        <img src="<?php echo !empty($bookmark['profile_image']) ? 'uploads/avatars/' . htmlspecialchars($bookmark['profile_image']) : '/api/placeholder/32/32'; ?>" class="rounded-circle me-2" width="32" height="32" alt="Author avatar">
                                        <span class="text-dark"><?php echo htmlspecialchars($bookmark['display_name'] ?: $bookmark['username']); ?></span>
                                    </a>
                                    <div class="story-meta">
                                        <span class="me-3"><i class="fas fa-bookmark me-1"></i> Bookmarked <?php echo date('M j, Y', strtotime($bookmark['bookmarked_at'])); ?></span>
                                        <span><i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($bookmark['category_name']); ?></span>
                                    </div>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars(substr(strip_tags($bookmark['excerpt'] ?: $bookmark['content']), 0, 150) . '...'); ?></p>
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="me-2"><i class="fas fa-heart me-1"></i> <?php echo $bookmark['likes_count']; ?></span>
                                        <span><i class="fas fa-comment me-1"></i> <?php echo $bookmark['comments_count']; ?></span>
                                    </div>
                                    <div>
                                        <a href="story.php?id=<?php echo $bookmark['id']; ?>" class="btn btn-sm btn-outline-primary me-2">Read</a>
                                        <button class="btn btn-sm btn-outline-danger remove-bookmark" data-story-id="<?php echo $bookmark['id']; ?>">
                                            <i class="fas fa-bookmark-slash"></i> Remove
                                        </button>
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
                    <i class="fas fa-bookmark text-muted" style="font-size: 3rem;"></i>
                </div>
                <h4>No bookmarks yet</h4>
                <p class="text-muted">Save stories to read later by clicking the bookmark button while reading</p>
                <a href="categories.php" class="btn btn-primary mt-2">Explore Stories</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle remove bookmark buttons
        document.querySelectorAll('.remove-bookmark').forEach(button => {
            button.addEventListener('click', function() {
                const storyId = this.getAttribute('data-story-id');
                const cardElement = this.closest('.col-lg-6');
                
                fetch('ajax/bookmark.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `story_id=${storyId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.action === 'unbookmarked') {
                        // Animate removal of card
                        cardElement.style.transition = 'opacity 0.3s ease';
                        cardElement.style.opacity = '0';
                        
                        setTimeout(() => {
                            cardElement.remove();
                            
                            // Check if there are any bookmarks left
                            if (document.querySelectorAll('.card').length === 0) {
                                // Reload page to show empty state
                                window.location.reload();
                            }
                        }, 300);
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });
    });
</script>

<?php
// Include footer
include_once "includes/footer.php";
?>
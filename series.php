<?php
// Include config file
require_once "config.php";

// Set page title
$page_title = "Series";

// Check if series ID is set
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage("error", "Series not found.");
    redirect("index.php");
    exit;
}

$series_id = $_GET['id'];

// Get series data
try {
    $stmt = $conn->prepare("
        SELECT s.*, u.username, u.display_name, u.profile_image, 
               (SELECT COUNT(*) FROM stories WHERE series_id = s.id AND status = 'published') as story_count,
               (SELECT COUNT(*) FROM series_follows WHERE series_id = s.id) as followers_count
        FROM series s
        JOIN users u ON s.user_id = u.id
        WHERE s.id = :series_id
    ");
    
    $stmt->bindParam(":series_id", $series_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setFlashMessage("error", "Series not found.");
        redirect("index.php");
        exit;
    }
    
    $series = $stmt->fetch();
    
    // Get stories in the series
    $stmt = $conn->prepare("
        SELECT s.*, 
               (SELECT COUNT(*) FROM likes WHERE story_id = s.id) as likes_count,
               (SELECT COUNT(*) FROM comments WHERE story_id = s.id) as comments_count
        FROM stories s
        WHERE s.series_id = :series_id AND s.status = 'published'
        ORDER BY s.chapter_number ASC, s.created_at ASC
    ");
    $stmt->bindParam(":series_id", $series_id);
    $stmt->execute();
    $stories = $stmt->fetchAll();
    
} catch(PDOException $e) {
    setFlashMessage("error", "Error retrieving series: " . $e->getMessage());
    redirect("index.php");
    exit;
}

// Check if user is following the series
$user_following = false;
if (isLoggedIn()) {
    try {
        $stmt = $conn->prepare("SELECT id FROM series_follows WHERE user_id = :user_id AND series_id = :series_id");
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->bindParam(":series_id", $series_id);
        $stmt->execute();
        $user_following = ($stmt->rowCount() > 0);
    } catch(PDOException $e) {
        // Silently fail
    }
}

// Include header
include_once "includes/header.php";
?>

<div class="container py-5">
    <div class="story-container mb-4">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h1 class="mb-2"><?php echo htmlspecialchars($series['title']); ?></h1>
                <div class="d-flex align-items-center mb-3">
                    <a href="profile.php?username=<?php echo urlencode($series['username']); ?>" class="d-flex align-items-center text-decoration-none">
                        <img src="<?php echo !empty($series['profile_image']) ? 'uploads/avatars/' . htmlspecialchars($series['profile_image']) : '/api/placeholder/32/32'; ?>" class="rounded-circle me-2" width="32" height="32" alt="Author avatar">
                        <span class="text-dark"><?php echo htmlspecialchars($series['display_name'] ?: $series['username']); ?></span>
                    </a>
                </div>
                <div class="story-meta mb-3">
                    <span class="me-3"><i class="fas fa-book me-1"></i> <?php echo $series['story_count']; ?> chapters</span>
                    <span class="me-3"><i class="fas fa-users me-1"></i> <?php echo $series['followers_count']; ?> followers</span>
                    <span class="me-3"><i class="fas fa-calendar me-1"></i> Started <?php echo date('M j, Y', strtotime($series['created_at'])); ?></span>
                    <span><i class="fas fa-flag me-1"></i> Status: <?php echo ucfirst($series['status']); ?></span>
                </div>
            </div>
            
            <?php if (isLoggedIn()): ?>
                <div>
                    <?php if ($series['user_id'] == $_SESSION['user_id']): ?>
                        <a href="create-story.php?series=<?php echo $series['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Add Chapter
                        </a>
                    <?php else: ?>
                        <button class="btn <?php echo $user_following ? 'btn-primary' : 'btn-outline-primary'; ?> follow-series-btn" data-series-id="<?php echo $series['id']; ?>">
                            <i class="fas <?php echo $user_following ? 'fa-check' : 'fa-bookmark'; ?> me-1"></i>
                            <?php echo $user_following ? 'Following' : 'Follow Series'; ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="series-description mb-4">
            <p><?php echo htmlspecialchars($series['description']); ?></p>
        </div>
    </div>
    
    <!-- Series Chapters -->
    <div class="story-container">
        <h2 class="mb-4">Chapters</h2>
        
        <?php if (count($stories) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="10%">#</th>
                            <th width="45%">Title</th>
                            <th width="15%">Published</th>
                            <th width="15%">Engagement</th>
                            <th width="15%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stories as $index => $story): ?>
                            <tr>
                                <td><?php echo $story['chapter_number'] ?: ($index + 1); ?></td>
                                <td><?php echo htmlspecialchars($story['title']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($story['created_at'])); ?></td>
                                <td>
                                    <span class="me-2"><i class="fas fa-heart text-danger"></i> <?php echo $story['likes_count']; ?></span>
                                    <span><i class="fas fa-comment text-primary"></i> <?php echo $story['comments_count']; ?></span>
                                </td>
                                <td>
                                    <a href="story.php?id=<?php echo $story['id']; ?>" class="btn btn-sm btn-primary">Read</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <p class="text-muted">No chapters have been published in this series yet.</p>
                <?php if (isLoggedIn() && $series['user_id'] == $_SESSION['user_id']): ?>
                    <a href="create-story.php?series=<?php echo $series['id']; ?>" class="btn btn-primary mt-2">
                        Add First Chapter
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Series follow button
        const followBtn = document.querySelector('.follow-series-btn');
        if (followBtn) {
            followBtn.addEventListener('click', function() {
                const seriesId = this.getAttribute('data-series-id');
                
                fetch('ajax/follow_series.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `series_id=${seriesId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.action === 'followed') {
                            followBtn.classList.remove('btn-outline-primary');
                            followBtn.classList.add('btn-primary');
                            followBtn.innerHTML = '<i class="fas fa-check me-1"></i> Following';
                        } else {
                            followBtn.classList.remove('btn-primary');
                            followBtn.classList.add('btn-outline-primary');
                            followBtn.innerHTML = '<i class="fas fa-bookmark me-1"></i> Follow Series';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        }
    });
</script>

<?php
// Include footer
include_once "includes/footer.php";
?>
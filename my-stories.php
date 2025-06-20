<?php
// Include config file
require_once "config.php";

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage("error", "You must be logged in to view your stories.");
    redirect("login.php");
    exit;
}

// Set page title
$page_title = "My Stories";

// Get user's stories
try {
    $stmt = $conn->prepare("
        SELECT s.*, c.name as category_name,
               (SELECT COUNT(*) FROM likes WHERE story_id = s.id) as likes_count,
               (SELECT COUNT(*) FROM comments WHERE story_id = s.id) as comments_count,
               (SELECT COUNT(*) FROM bookmarks WHERE story_id = s.id) as bookmarks_count,
               (SELECT title FROM series WHERE id = s.series_id) as series_title
        FROM stories s
        LEFT JOIN categories c ON s.category_id = c.id
        WHERE s.user_id = :user_id
        ORDER BY s.created_at DESC
    ");
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();
    $stories = $stmt->fetchAll();
    
    // Group stories by status
    $published_stories = array_filter($stories, function($story) {
        return $story['status'] == 'published';
    });
    
    $draft_stories = array_filter($stories, function($story) {
        return $story['status'] == 'draft';
    });
    
    $archived_stories = array_filter($stories, function($story) {
        return $story['status'] == 'archived';
    });
    
} catch(PDOException $e) {
    setFlashMessage("error", "Error retrieving stories: " . $e->getMessage());
    $stories = [];
    $published_stories = [];
    $draft_stories = [];
    $archived_stories = [];
}

// Get user's series
try {
    $stmt = $conn->prepare("
        SELECT s.*, 
               (SELECT COUNT(*) FROM stories WHERE series_id = s.id AND status = 'published') as story_count,
               (SELECT COUNT(*) FROM series_follows WHERE series_id = s.id) as followers_count
        FROM series s
        WHERE s.user_id = :user_id
        ORDER BY s.created_at DESC
    ");
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();
    $series_list = $stmt->fetchAll();
} catch(PDOException $e) {
    $series_list = [];
}

// Include header
include_once "includes/header.php";
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>My Stories</h1>
        <a href="create-story.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> Write New Story
        </a>
    </div>
    
    <ul class="nav nav-tabs mb-4" id="storyTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="published-tab" data-bs-toggle="tab" data-bs-target="#published" type="button" role="tab" aria-controls="published" aria-selected="true">
                Published (<?php echo count($published_stories); ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="drafts-tab" data-bs-toggle="tab" data-bs-target="#drafts" type="button" role="tab" aria-controls="drafts" aria-selected="false">
                Drafts (<?php echo count($draft_stories); ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="series-tab" data-bs-toggle="tab" data-bs-target="#series" type="button" role="tab" aria-controls="series" aria-selected="false">
                Series (<?php echo count($series_list); ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="archived-tab" data-bs-toggle="tab" data-bs-target="#archived" type="button" role="tab" aria-controls="archived" aria-selected="false">
                Archived (<?php echo count($archived_stories); ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab" aria-controls="stats" aria-selected="false">
                Statistics
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="storyTabsContent">
        <!-- Published Stories -->
        <div class="tab-pane fade show active" id="published" role="tabpanel" aria-labelledby="published-tab">
            <div class="editor-container">
                <?php if (count($published_stories) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Series</th>
                                    <th>Published</th>
                                    <th>Stats</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($published_stories as $story): ?>
                                    <tr>
                                        <td>
                                            <a href="story.php?id=<?php echo $story['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($story['title']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($story['category_name']); ?></td>
                                        <td>
                                            <?php if ($story['series_id']): ?>
                                                <a href="series.php?id=<?php echo $story['series_id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($story['series_title']); ?>
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($story['created_at'])); ?></td>
                                        <td>
                                            <span class="me-2" title="Views"><i class="fas fa-eye"></i> <?php echo $story['views']; ?></span>
                                            <span class="me-2" title="Likes"><i class="fas fa-heart"></i> <?php echo $story['likes_count']; ?></span>
                                            <span title="Comments"><i class="fas fa-comment"></i> <?php echo $story['comments_count']; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="story.php?id=<?php echo $story['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                <a href="create-story.php?edit=<?php echo $story['id']; ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                                <button type="button" class="btn btn-sm btn-outline-danger archive-story" data-story-id="<?php echo $story['id']; ?>">Archive</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-book text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <h4>No published stories yet</h4>
                        <p class="text-muted">Your published stories will appear here</p>
                        <a href="create-story.php" class="btn btn-primary mt-2">Write a Story</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Draft Stories -->
        <div class="tab-pane fade" id="drafts" role="tabpanel" aria-labelledby="drafts-tab">
            <div class="editor-container">
                <?php if (count($draft_stories) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Series</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($draft_stories as $story): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($story['title'] ?: 'Untitled Draft'); ?></td>
                                        <td><?php echo htmlspecialchars($story['category_name'] ?: 'Uncategorized'); ?></td>
                                        <td>
                                            <?php if ($story['series_id']): ?>
                                                <a href="series.php?id=<?php echo $story['series_id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($story['series_title']); ?>
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($story['updated_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="create-story.php?edit=<?php echo $story['id']; ?>" class="btn btn-sm btn-outline-primary">Continue</a>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-story" data-story-id="<?php echo $story['id']; ?>">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-edit text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <h4>No draft stories</h4>
                        <p class="text-muted">Your draft stories will appear here</p>
                        <a href="create-story.php" class="btn btn-primary mt-2">Start Writing</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Series -->
        <div class="tab-pane fade" id="series" role="tabpanel" aria-labelledby="series-tab">
            <div class="editor-container">
                <?php if (count($series_list) > 0): ?>
                    <div class="row">
                        <?php foreach ($series_list as $series): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($series['title']); ?></h5>
                                        <p class="story-meta mb-3">
                                            <span class="me-3"><i class="fas fa-book me-1"></i> <?php echo $series['story_count']; ?> stories</span>
                                            <span class="me-3"><i class="fas fa-users me-1"></i> <?php echo $series['followers_count']; ?> followers</span>
                                            <span><i class="fas fa-flag me-1"></i> <?php echo ucfirst($series['status']); ?></span>
                                        </p>
                                        <p class="card-text"><?php echo htmlspecialchars(substr($series['description'], 0, 150) . (strlen($series['description']) > 150 ? '...' : '')); ?></p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <a href="series.php?id=<?php echo $series['id']; ?>" class="btn btn-sm btn-outline-primary">View Series</a>
                                            <a href="create-story.php?series=<?php echo $series['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-plus me-1"></i> Add Chapter
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-books text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <h4>No series created yet</h4>
                        <p class="text-muted">Create a series to group related stories together</p>
                        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createSeriesModal">Create Series</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Archived Stories -->
        <div class="tab-pane fade" id="archived" role="tabpanel" aria-labelledby="archived-tab">
            <div class="editor-container">
                <?php if (count($archived_stories) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Series</th>
                                    <th>Archived</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archived_stories as $story): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($story['title']); ?></td>
                                        <td><?php echo htmlspecialchars($story['category_name']); ?></td>
                                        <td>
                                            <?php if ($story['series_id']): ?>
                                                <a href="series.php?id=<?php echo $story['series_id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($story['series_title']); ?>
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($story['updated_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary restore-story" data-story-id="<?php echo $story['id']; ?>">Restore</button>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-story" data-story-id="<?php echo $story['id']; ?>">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-archive text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <h4>No archived stories</h4>
                        <p class="text-muted">Your archived stories will appear here</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="tab-pane fade" id="stats" role="tabpanel" aria-labelledby="stats-tab">
            <div class="editor-container">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Total Stories</h6>
                                <h2 class="card-title"><?php echo count($stories); ?></h2>
                                <p class="card-text small">
                                    <?php echo count($published_stories); ?> published, 
                                    <?php echo count($draft_stories); ?> drafts, 
                                    <?php echo count($archived_stories); ?> archived
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Total Views</h6>
                                <h2 class="card-title">
                                    <?php echo array_sum(array_column($published_stories, 'views')); ?>
                                </h2>
                                <p class="card-text small">Across all published stories</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Total Likes</h6>
                                <h2 class="card-title">
                                    <?php echo array_sum(array_column($published_stories, 'likes_count')); ?>
                                </h2>
                                <p class="card-text small">Across all published stories</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h4 class="mb-3 mt-4">Most Popular Stories</h4>
                <?php 
                // Sort stories by views
                usort($published_stories, function($a, $b) {
                    return $b['views'] - $a['views'];
                });
                
                // Get top 5 stories
                $top_stories = array_slice($published_stories, 0, 5);
                ?>
                
                <?php if (count($top_stories) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Story</th>
                                    <th>Views</th>
                                    <th>Likes</th>
                                    <th>Comments</th>
                                    <th>Published</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_stories as $story): ?>
                                    <tr>
                                        <td>
                                            <a href="story.php?id=<?php echo $story['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($story['title']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo $story['views']; ?></td>
                                        <td><?php echo $story['likes_count']; ?></td>
                                        <td><?php echo $story['comments_count']; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($story['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No published stories to analyze.</p>
                <?php endif; ?>
                
                <h4 class="mb-3 mt-4">Writing Activity</h4>
                <div class="card">
                    <div class="card-body">
                        <div id="activity-chart" style="height: 300px;">
                            <!-- Chart would be rendered here with JS library like Chart.js -->
                            <div class="text-center py-5">
                                <p class="text-muted">Writing activity chart would render here in a full implementation.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Series Modal -->
<div class="modal fade" id="createSeriesModal" tabindex="-1" aria-labelledby="createSeriesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createSeriesModalLabel">Create New Series</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createSeriesForm" action="ajax/create_series.php" method="post">
                    <div class="mb-3">
                        <label for="seriesTitle" class="form-label">Series Title</label>
                        <input type="text" class="form-control" id="seriesTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="seriesDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="seriesDescription" name="description" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="seriesStatus" class="form-label">Status</label>
                        <select class="form-select" id="seriesStatus" name="status">
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="hiatus">On Hiatus</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitSeries">Create Series</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle archive story buttons
        document.querySelectorAll('.archive-story').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to archive this story? It will no longer be visible to readers.')) {
                    const storyId = this.getAttribute('data-story-id');
                    
                    fetch('ajax/update_story_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `story_id=${storyId}&status=archived`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'Error archiving story');
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
            });
        });
        
        // Handle restore story buttons
        document.querySelectorAll('.restore-story').forEach(button => {
            button.addEventListener('click', function() {
                const storyId = this.getAttribute('data-story-id');
                
                fetch('ajax/update_story_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `story_id=${storyId}&status=published`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error restoring story');
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });
        
        // Handle delete story buttons
        document.querySelectorAll('.delete-story').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to permanently delete this story? This action cannot be undone.')) {
                    const storyId = this.getAttribute('data-story-id');
                    
                    fetch('ajax/delete_story.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `story_id=${storyId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'Error deleting story');
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
            });
        });
        
        // Handle create series form
        document.getElementById('submitSeries').addEventListener('click', function() {
            const form = document.getElementById('createSeriesForm');
            
            if (form.checkValidity()) {
                const formData = new FormData(form);
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error creating series');
                    }
                })
                .catch(error => console.error('Error:', error));
            } else {
                form.reportValidity();
            }
        });
    });
</script>

<?php
// Include footer
include_once "includes/footer.php";
?>
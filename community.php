<?php
// Include config file
require_once "config.php";

// Set page title
$page_title = "Community";

// Get community activity (recent stories, comments, likes, follows)
try {
    $stmt = $conn->prepare("
        (SELECT 'new_story' as type, s.id, s.title, s.created_at, u.id as user_id, u.username, u.display_name, u.profile_image, c.name as category_name, NULL as story_title, NULL as target_username
         FROM stories s
         JOIN users u ON s.user_id = u.id
         JOIN categories c ON s.category_id = c.id
         WHERE s.status = 'published'
         ORDER BY s.created_at DESC
         LIMIT 10)
        UNION
        (SELECT 'new_comment' as type, cm.id, cm.content as title, cm.created_at, u.id as user_id, u.username, u.display_name, u.profile_image, NULL as category_name, s.title as story_title, NULL as target_username
         FROM comments cm
         JOIN users u ON cm.user_id = u.id
         JOIN stories s ON cm.story_id = s.id
         WHERE s.status = 'published' AND cm.parent_id IS NULL
         ORDER BY cm.created_at DESC
         LIMIT 10)
        UNION
        (SELECT 'new_follow' as type, f.id, NULL as title, f.created_at, u.id as user_id, u.username, u.display_name, u.profile_image, NULL as category_name, NULL as story_title, t.username as target_username
         FROM follows f
         JOIN users u ON f.follower_id = u.id
         JOIN users t ON f.followed_id = t.id
         ORDER BY f.created_at DESC
         LIMIT 10)
        UNION
        (SELECT 'new_like' as type, l.id, NULL as title, l.created_at, u.id as user_id, u.username, u.display_name, u.profile_image, NULL as category_name, s.title as story_title, NULL as target_username
         FROM likes l
         JOIN users u ON l.user_id = u.id
         JOIN stories s ON l.story_id = s.id
         WHERE s.status = 'published'
         ORDER BY l.created_at DESC
         LIMIT 10)
        ORDER BY created_at DESC
        LIMIT 20
    ");
    
    $stmt->execute();
    $community_activity = $stmt->fetchAll();
} catch(PDOException $e) {
    $community_activity = [];
}

// Get active users (most stories or most engagement)
try {
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.display_name, u.profile_image, u.bio,
               (SELECT COUNT(*) FROM stories WHERE user_id = u.id AND status = 'published') as stories_count,
               (SELECT COUNT(*) FROM follows WHERE followed_id = u.id) as followers_count,
               (SELECT COUNT(*) FROM likes l JOIN stories s ON l.story_id = s.id WHERE s.user_id = u.id) as received_likes
        FROM users u
        WHERE (SELECT COUNT(*) FROM stories WHERE user_id = u.id AND status = 'published') > 0
        ORDER BY received_likes DESC, stories_count DESC
        LIMIT 5
    ");
    
    $stmt->execute();
    $active_users = $stmt->fetchAll();
} catch(PDOException $e) {
    $active_users = [];
}

// Get upcoming events (like writing workshops or challenges)
// In a real implementation, these would come from an events table
$upcoming_events = [
    [
        'id' => 1,
        'title' => 'Weekly Writing Workshop',
        'description' => 'Join us for our weekly writing workshop where writers can share their works-in-progress and receive feedback.',
        'date' => '2025-05-15 19:00:00',
        'type' => 'workshop',
        'host' => 'StoryNest Team'
    ],
    [
        'id' => 2,
        'title' => 'Character Development Masterclass',
        'description' => 'Learn how to create compelling, three-dimensional characters that readers will remember.',
        'date' => '2025-05-20 18:30:00',
        'type' => 'masterclass',
        'host' => 'Professor Smith'
    ],
    [
        'id' => 3,
        'title' => 'Flash Fiction Challenge',
        'description' => 'Write a complete story in just 100 words. Top entries will be featured on our homepage.',
        'date' => '2025-05-25 00:00:00',
        'type' => 'challenge',
        'host' => 'StoryNest Team'
    ]
];

// Get discussion topics (in a real implementation, these would come from a forum table)
$discussion_topics = [
    [
        'id' => 1,
        'title' => 'How do you overcome writer\'s block?',
        'author' => 'creativemind',
        'replies' => 23,
        'last_activity' => '2025-05-08 15:30:00'
    ],
    [
        'id' => 2,
        'title' => 'What\'s your writing routine?',
        'author' => 'wordsmith42',
        'replies' => 17,
        'last_activity' => '2025-05-09 10:15:00'
    ],
    [
        'id' => 3,
        'title' => 'Traditional publishing vs. self-publishing: pros and cons',
        'author' => 'bookworm',
        'replies' => 31,
        'last_activity' => '2025-05-09 14:45:00'
    ],
    [
        'id' => 4,
        'title' => 'Writing tools and software recommendations',
        'author' => 'techwriter',
        'replies' => 19,
        'last_activity' => '2025-05-10 09:20:00'
    ]
];

// Include header
include_once "includes/header.php";
?>

<div class="container py-5">
    <h1 class="mb-4">StoryNest Community</h1>
    
    <div class="row">
        <!-- Main Community Content -->
        <div class="col-lg-8">
            <!-- Community Activity -->
            <div class="editor-container mb-4">
                <h3 class="mb-3">Community Activity</h3>
                
                <div class="activity-feed">
                    <?php if (count($community_activity) > 0): ?>
                        <?php foreach ($community_activity as $activity): ?>
                            <div class="activity-item">
                                <div class="d-flex">
                                    <div class="activity-avatar me-3">
                                        <img src="<?php echo !empty($activity['profile_image']) ? 'uploads/avatars/' . htmlspecialchars($activity['profile_image']) : '/api/placeholder/48/48'; ?>" class="rounded-circle" alt="User avatar" width="48" height="48">
                                    </div>
                                    <div class="activity-content">
                                        <div class="d-flex align-items-center mb-1">
                                            <a href="profile.php?username=<?php echo urlencode($activity['username']); ?>" class="fw-bold text-decoration-none me-2">
                                                <?php echo htmlspecialchars($activity['display_name'] ?: $activity['username']); ?>
                                            </a>
                                            <span class="activity-time text-muted">
                                                <?php echo timeAgo($activity['created_at']); ?>
                                            </span>
                                        </div>
                                        
                                        <?php if ($activity['type'] == 'new_story'): ?>
                                            <p>
                                                Published a new story in 
                                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($activity['category_name']); ?></span>
                                            </p>
                                            <div class="activity-item-content">
                                                <h5>
                                                    <a href="story.php?id=<?php echo $activity['id']; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($activity['title']); ?>
                                                    </a>
                                                </h5>
                                                <a href="story.php?id=<?php echo $activity['id']; ?>" class="btn btn-sm btn-outline-primary mt-2">Read Story</a>
                                            </div>
                                        
                                        <?php elseif ($activity['type'] == 'new_comment'): ?>
                                            <p>
                                                Commented on 
                                                <a href="story.php?id=<?php echo $activity['id']; ?>" class="text-decoration-none">
                                                    "<?php echo htmlspecialchars($activity['story_title']); ?>"
                                                </a>
                                            </p>
                                            <div class="activity-item-content comment-preview">
                                                <p class="mb-0"><?php echo htmlspecialchars(substr(strip_tags($activity['title']), 0, 100) . (strlen(strip_tags($activity['title'])) > 100 ? '...' : '')); ?></p>
                                                <a href="story.php?id=<?php echo $activity['id']; ?>#comments" class="btn btn-sm btn-outline-primary mt-2">View Comment</a>
                                            </div>
                                            
                                        <?php elseif ($activity['type'] == 'new_follow'): ?>
                                            <p>
                                                Started following 
                                                <a href="profile.php?username=<?php echo urlencode($activity['target_username']); ?>" class="text-decoration-none fw-bold">
                                                    <?php echo htmlspecialchars($activity['target_username']); ?>
                                                </a>
                                            </p>
                                            
                                        <?php elseif ($activity['type'] == 'new_like'): ?>
                                            <p>
                                                Liked the story 
                                                <a href="story.php?id=<?php echo $activity['id']; ?>" class="text-decoration-none">
                                                    "<?php echo htmlspecialchars($activity['story_title']); ?>"
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="text-center mt-4">
                            <button class="btn btn-outline-primary" id="load-more-activity">Load More Activity</button>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-users text-muted" style="font-size: 3rem;"></i>
                            </div>
                            <h4>No activity yet</h4>
                            <p class="text-muted">Be the first to contribute to our community!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Discussion Topics -->
            <div class="editor-container mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">Discussion Topics</h3>
                    <?php if (isLoggedIn()): ?>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newTopicModal">
                            <i class="fas fa-plus me-1"></i> New Topic
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="list-group">
                    <?php foreach ($discussion_topics as $topic): ?>
                        <a href="#" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?php echo htmlspecialchars($topic['title']); ?></h5>
                                <small><?php echo timeAgo($topic['last_activity']); ?></small>
                            </div>
                            <p class="mb-1">Started by <?php echo htmlspecialchars($topic['author']); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted"><?php echo $topic['replies']; ?> replies</small>
                                <span class="badge bg-primary rounded-pill">Active</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-3">
                    <a href="#" class="btn btn-outline-primary">View All Discussions</a>
                </div>
            </div>
            
            <!-- Upcoming Events -->
            <div class="editor-container">
                <h3 class="mb-3">Upcoming Events</h3>
                
                <div class="row">
                    <?php foreach ($upcoming_events as $event): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                        <span class="badge bg-<?php echo $event['type'] == 'workshop' ? 'success' : ($event['type'] == 'masterclass' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($event['type']); ?>
                                        </span>
                                    </div>
                                    <p class="card-text"><?php echo htmlspecialchars($event['description']); ?></p>
                                    <div class="mt-3">
                                        <div class="mb-2"><i class="fas fa-calendar-alt me-2"></i> <?php echo date('F j, Y', strtotime($event['date'])); ?></div>
                                        <div class="mb-2"><i class="fas fa-clock me-2"></i> <?php echo date('g:i A', strtotime($event['date'])); ?></div>
                                        <div><i class="fas fa-user me-2"></i> <?php echo htmlspecialchars($event['host']); ?></div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-grid">
                                        <button class="btn btn-outline-primary btn-sm">RSVP</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-3">
                    <a href="#" class="btn btn-outline-primary">View All Events</a>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Community Stats -->
            <div class="sidebar-container mb-4">
                <h4 class="mb-3">Community Stats</h4>
                <div class="row text-center">
                    <div class="col-4">
                        <div class="stat-value">
                            <?php
                            // In a real implementation, these would be queried from the DB
                            echo "1,275";
                            ?>
                        </div>
                        <div class="stat-label">Members</div>
                    </div>
                    <div class="col-4">
                        <div class="stat-value">
                            <?php
                            // In a real implementation, these would be queried from the DB
                            echo "3,842";
                            ?>
                        </div>
                        <div class="stat-label">Stories</div>
                    </div>
                    <div class="col-4">
                        <div class="stat-value">
                            <?php
                            // In a real implementation, these would be queried from the DB
                            echo "12K";
                            ?>
                        </div>
                        <div class="stat-label">Comments</div>
                    </div>
                </div>
            </div>
            
            <!-- Active Writers -->
            <div class="sidebar-container mb-4">
                <h4 class="mb-3">Active Writers</h4>
                
                <?php if (count($active_users) > 0): ?>
                    <?php foreach ($active_users as $user): ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo !empty($user['profile_image']) ? 'uploads/avatars/' . htmlspecialchars($user['profile_image']) : '/api/placeholder/50/50'; ?>" class="rounded-circle me-3" alt="User avatar" width="50" height="50">
                            <div>
                                <h6 class="mb-0">
                                    <a href="profile.php?username=<?php echo urlencode($user['username']); ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($user['display_name'] ?: $user['username']); ?>
                                    </a>
                                </h6>
                                <small class="text-muted">
                                    <?php echo $user['stories_count']; ?> stories &bull; 
                                    <?php echo $user['followers_count']; ?> followers
                                </small>
                            </div>
                            <?php if (isLoggedIn() && $_SESSION['user_id'] != $user['id']): ?>
                                <button class="btn btn-sm btn-outline-primary ms-auto follow-btn" data-user-id="<?php echo $user['id']; ?>">Follow</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No active writers found.</p>
                <?php endif; ?>
            </div>
            
            <!-- Join the Community (for non-logged in users) -->
            <?php if (!isLoggedIn()): ?>
                <div class="sidebar-container mb-4">
                    <h4 class="mb-3">Join the Community</h4>
                    <p>Create an account to publish your stories, participate in challenges, and connect with other writers.</p>
                    <div class="d-grid gap-2">
                        <a href="register.php" class="btn btn-primary">Sign Up</a>
                        <a href="login.php" class="btn btn-outline-primary">Login</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Writing Groups -->
            <div class="sidebar-container mb-4">
                <h4 class="mb-3">Writing Groups</h4>
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        Fiction Writers
                        <span class="badge bg-primary rounded-pill">42</span>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        Poetry Circle
                        <span class="badge bg-primary rounded-pill">28</span>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        Science Fiction & Fantasy
                        <span class="badge bg-primary rounded-pill">35</span>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        Mystery Writers
                        <span class="badge bg-primary rounded-pill">19</span>
                    </a>
                </div>
                <div class="d-grid mt-3">
                    <a href="#" class="btn btn-outline-primary btn-sm">Browse All Groups</a>
                </div>
            </div>
            
            <!-- Community Guidelines -->
            <div class="sidebar-container">
                <h4 class="mb-3">Community Guidelines</h4>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Be respectful to other writers</li>
                    <li class="list-group-item">Provide constructive feedback</li>
                    <li class="list-group-item">Acknowledge sources and inspirations</li>
                    <li class="list-group-item">Respect copyright and intellectual property</li>
                    <li class="list-group-item">Help maintain a positive atmosphere</li>
                </ul>
                <div class="d-grid mt-3">
                    <a href="#" class="btn btn-outline-primary btn-sm">Full Guidelines</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isLoggedIn()): ?>
<!-- New Topic Modal -->
<div class="modal fade" id="newTopicModal" tabindex="-1" aria-labelledby="newTopicModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newTopicModalLabel">Start a New Discussion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="newTopicForm">
                    <div class="mb-3">
                        <label for="topicTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="topicTitle" placeholder="What would you like to discuss?" required>
                    </div>
                    <div class="mb-3">
                        <label for="topicContent" class="form-label">Content</label>
                        <textarea class="form-control" id="topicContent" rows="5" placeholder="Describe your topic or question in detail..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="topicCategory" class="form-label">Category</label>
                        <select class="form-select" id="topicCategory" required>
                            <option value="">Select a category</option>
                            <option value="writing_craft">Writing Craft</option>
                            <option value="publishing">Publishing</option>
                            <option value="critique">Critique & Feedback</option>
                            <option value="inspiration">Inspiration & Prompts</option>
                            <option value="resources">Resources & Tools</option>
                            <option value="general">General Discussion</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="topicTags">
                        <label class="form-check-label" for="topicTags">Enable others to reply</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitTopic">Post Topic</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
    .activity-feed {
        margin-bottom: 0;
    }
    
    .activity-item {
        padding: 1.25rem 0;
        border-bottom: 1px solid #eee;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-content {
        flex: 1;
    }
    
    .activity-item-content {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .comment-preview {
        font-style: italic;
        color: #6c757d;
    }
    
    .activity-time {
        font-size: 0.85rem;
    }
    
    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary-color);
    }
    
    .stat-label {
        font-size: 0.85rem;
        color: #6c757d;
        text-transform: uppercase;
    }
    
    /* Dark mode styles */
    .dark-mode .activity-item {
        border-color: #444;
    }
    
    .dark-mode .activity-item-content {
        background-color: #2a2a2a;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load more activity button
        const loadMoreBtn = document.getElementById('load-more-activity');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                // In a real implementation, this would load more activity via AJAX
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
                
                setTimeout(() => {
                    this.innerHTML = 'No more activity to load';
                    this.disabled = true;
                }, 1500);
            });
        }
        
        // Follow buttons
        const followBtns = document.querySelectorAll('.follow-btn');
        followBtns.forEach(btn => {
            btn.addEventListener('click', function() {
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
                            this.textContent = 'Following';
                            this.classList.remove('btn-outline-primary');
                            this.classList.add('btn-primary');
                        } else {
                            this.textContent = 'Follow';
                            this.classList.remove('btn-primary');
                            this.classList.add('btn-outline-primary');
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });
        
        <?php if (isLoggedIn()): ?>
        // Submit new topic
        const submitTopicBtn = document.getElementById('submitTopic');
        if (submitTopicBtn) {
            submitTopicBtn.addEventListener('click', function() {
                const form = document.getElementById('newTopicForm');
                
                if (form.checkValidity()) {
                    // In a real implementation, this would send data via AJAX
                    alert('Your topic has been posted successfully!');
                    
                    // Close modal and reset form
                    const modal = bootstrap.Modal.getInstance(document.getElementById('newTopicModal'));
                    modal.hide();
                    form.reset();
                } else {
                    form.reportValidity();
                }
            });
        }
        <?php endif; ?>
        
        // Check if users are following
        <?php if (isLoggedIn() && !empty($active_users)): ?>
        fetch('ajax/get_following.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update follow buttons for users that are already being followed
                followBtns.forEach(btn => {
                    const userId = btn.getAttribute('data-user-id');
                    if (data.following.includes(parseInt(userId))) {
                        btn.textContent = 'Following';
                        btn.classList.remove('btn-outline-primary');
                        btn.classList.add('btn-primary');
                    }
                });
            }
        })
        .catch(error => console.error('Error:', error));
        <?php endif; ?>
    });
</script>

<?php
// Include footer
include_once "includes/footer.php";
?>
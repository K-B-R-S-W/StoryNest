<?php
// Include config file
require_once "config.php";

// Check if story ID is set
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage("error", "Story not found.");
    redirect("index.php");
    exit;
}

$story_id = $_GET['id'];

// Get story data
try {
    $stmt = $conn->prepare("
        SELECT s.*, u.username, u.display_name, u.profile_image, u.bio, c.name as category_name, c.icon as category_icon,
               (SELECT COUNT(*) FROM likes WHERE story_id = s.id) as likes_count,
               (SELECT COUNT(*) FROM comments WHERE story_id = s.id) as comments_count,
               (SELECT COUNT(*) FROM stories WHERE user_id = s.user_id AND status = 'published') as author_stories_count,
               sr.title as series_title, sr.id as series_id,
               (SELECT COUNT(*) FROM stories WHERE series_id = sr.id AND status = 'published') as series_stories_count,
               (SELECT MAX(chapter_number) FROM stories WHERE series_id = sr.id AND status = 'published') as series_max_chapter,
               (SELECT id FROM stories WHERE series_id = sr.id AND chapter_number < s.chapter_number AND status = 'published' ORDER BY chapter_number DESC LIMIT 1) as prev_chapter_id,
               (SELECT id FROM stories WHERE series_id = sr.id AND chapter_number > s.chapter_number AND status = 'published' ORDER BY chapter_number ASC LIMIT 1) as next_chapter_id
        FROM stories s
        JOIN users u ON s.user_id = u.id
        JOIN categories c ON s.category_id = c.id
        LEFT JOIN series sr ON s.series_id = sr.id
        WHERE s.id = :story_id AND s.status = 'published'
    ");
    
    $stmt->bindParam(":story_id", $story_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setFlashMessage("error", "Story not found or not published.");
        redirect("index.php");
        exit;
    }
    
    $story = $stmt->fetch();
    
    // Update view count
    $update_stmt = $conn->prepare("UPDATE stories SET views = views + 1 WHERE id = :story_id");
    $update_stmt->bindParam(":story_id", $story_id);
    $update_stmt->execute();
    
} catch(PDOException $e) {
    setFlashMessage("error", "Error retrieving story: " . $e->getMessage());
    redirect("index.php");
    exit;
}

// Check if user has liked the story
$user_liked = false;
if (isLoggedIn()) {
    try {
        $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = :user_id AND story_id = :story_id");
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->bindParam(":story_id", $story_id);
        $stmt->execute();
        $user_liked = ($stmt->rowCount() > 0);
    } catch(PDOException $e) {
        // Silently fail
    }
}

// Get comments
try {
    $stmt = $conn->prepare("
        SELECT c.*, u.username, u.display_name, u.profile_image,
               (SELECT COUNT(*) FROM comments WHERE parent_id = c.id) as replies_count
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.story_id = :story_id AND c.parent_id IS NULL
        ORDER BY c.created_at DESC
    ");
    $stmt->bindParam(":story_id", $story_id);
    $stmt->execute();
    $comments = $stmt->fetchAll();
} catch(PDOException $e) {
    $comments = [];
}

// Set page title
$page_title = $story['title'];

// Include header
include_once "includes/header.php";

// Custom CSS for this page
$custom_css = '
    .story-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 3rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .story-header {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #eee;
    }
    
    .story-title {
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }
    
    .story-meta {
        font-size: 0.9rem;
        color: var(--light-text);
    }
    
    .author-info {
        display: flex;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .author-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        margin-right: 1rem;
    }
    
    .author-name {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .story-content {
        font-size: 1.15rem;
        line-height: 1.8;
        margin-bottom: 3rem;
    }
    
    .story-content p {
        margin-bottom: 1.5rem;
    }
    
    .story-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 0;
        border-top: 1px solid #eee;
        margin-top: 2rem;
    }
    
    .engagement-btn {
        background: none;
        border: none;
        color: var(--light-text);
        display: flex;
        align-items: center;
        font-size: 1rem;
        margin-right: 1.5rem;
        cursor: pointer;
        transition: color 0.3s ease;
    }
    
    .engagement-btn:hover {
        color: var(--primary-color);
    }
    
    .engagement-btn i {
        margin-right: 0.5rem;
    }
    
    .engagement-btn.liked {
        color: var(--accent-color);
    }
    
    .chapter-navigation {
        display: flex;
        justify-content: space-between;
        margin-top: 3rem;
        padding-top: 1.5rem;
        border-top: 1px solid #eee;
    }
    
    .comments-section {
        margin-top: 4rem;
    }
    
    .comment {
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid #eee;
    }
    
    .comment-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .comment-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-right: 1rem;
    }
    
    .comment-meta {
        font-size: 0.85rem;
        color: var(--light-text);
    }
    
    .comment-content {
        margin-bottom: 1rem;
    }
    
    .word-count {
        display: inline-block;
        background-color: var(--primary-color);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 30px;
        font-size: 0.85rem;
        margin-left: 1rem;
    }
    
    /* Dark mode specific styles */
    .dark-mode .story-header,
    .dark-mode .story-actions,
    .dark-mode .chapter-navigation,
    .dark-mode .comment {
        border-color: #444;
    }
';
?>

<!-- Main Content -->
<div class="container py-5">
    <div class="story-container">
        <!-- Story Header -->
        <div class="story-header">
            <h1 class="story-title"><?php echo htmlspecialchars($story['title']); ?></h1>
            <div class="author-info">
                <img src="<?php echo !empty($story['profile_image']) ? 'uploads/avatars/' . htmlspecialchars($story['profile_image']) : '/api/placeholder/60/60'; ?>" alt="Author avatar" class="author-avatar">
                <div>
                    <p class="author-name">
                        <a href="profile.php?username=<?php echo urlencode($story['username']); ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($story['display_name'] ?: $story['username']); ?>
                        </a>
                    </p>
                    <p class="story-meta">
                        <span class="me-3"><i class="fas fa-calendar me-1"></i> <?php echo date('F j, Y', strtotime($story['created_at'])); ?></span>
                        <span class="me-3"><i class="fas fa-book me-1"></i> <?php echo htmlspecialchars($story['category_name']); ?></span>
                        <span><i class="fas fa-clock me-1"></i> <?php echo ceil($story['word_count'] / 200); ?> min read</span>
                        <span class="word-count"><?php echo number_format($story['word_count']); ?> words</span>
                    </p>
                </div>
            </div>
            <div class="d-flex">
                <?php if (isLoggedIn()): ?>
                    <button class="engagement-btn bookmark-btn" data-story-id="<?php echo $story['id']; ?>">
                        <i class="fas fa-bookmark"></i> Save
                    </button>
                <?php endif; ?>
                <div class="dropdown">
                    <button class="engagement-btn" type="button" id="shareDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-share-alt"></i> Share
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="shareDropdown">
                        <li><a class="dropdown-item" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank"><i class="fab fa-facebook-f me-2"></i> Facebook</a></li>
                        <li><a class="dropdown-item" href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($story['title']); ?>" target="_blank"><i class="fab fa-twitter me-2"></i> Twitter</a></li>
                        <li><a class="dropdown-item" href="mailto:?subject=<?php echo urlencode('Check out this story: ' . $story['title']); ?>&body=<?php echo urlencode('I found this great story on WritersHub: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>"><i class="fas fa-envelope me-2"></i> Email</a></li>
                        <li><a class="dropdown-item" href="#" onclick="navigator.clipboard.writeText('https://<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>'); alert('Link copied to clipboard!'); return false;"><i class="fas fa-link me-2"></i> Copy Link</a></li>
                    </ul>
                </div>
            </div>
            
            <?php if ($story['series_id']): ?>
                <div class="alert alert-light mt-3 mb-0">
                    <small>
                        <i class="fas fa-books me-1"></i> This story is part of the series 
                        <a href="series.php?id=<?php echo $story['series_id']; ?>" class="fw-bold text-decoration-none">
                            <?php echo htmlspecialchars($story['series_title']); ?>
                        </a>
                        <?php echo $story['chapter_number'] ? ' - Chapter ' . $story['chapter_number'] . ' of ' . $story['series_max_chapter'] : ''; ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>

        <!-- Story Content -->
        <div class="story-content">
            <?php echo $story['content']; ?>
        </div>

        <!-- Story Actions -->
        <div class="story-actions">
            <div class="d-flex">
                <button class="engagement-btn like-btn <?php echo $user_liked ? 'liked' : ''; ?>" data-story-id="<?php echo $story['id']; ?>">
                    <i class="<?php echo $user_liked ? 'fas' : 'far'; ?> fa-heart"></i> <span class="likes-count"><?php echo $story['likes_count']; ?></span>
                </button>
                <button class="engagement-btn" onclick="document.getElementById('comment-section').scrollIntoView({behavior: 'smooth'});">
                    <i class="far fa-comment"></i> <?php echo $story['comments_count']; ?>
                </button>
            </div>
            <div>
                <?php if ($story['series_id'] && $story['next_chapter_id']): ?>
                    <a href="story.php?id=<?php echo $story['next_chapter_id']; ?>" class="btn btn-primary">Next Chapter</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chapter Navigation for Series -->
        <?php if ($story['series_id']): ?>
            <div class="chapter-navigation">
                <div>
                    <?php if ($story['prev_chapter_id']): ?>
                        <a href="story.php?id=<?php echo $story['prev_chapter_id']; ?>" class="text-decoration-none text-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Previous Chapter
                        </a>
                    <?php else: ?>
                        <span class="text-secondary opacity-50">
                            <i class="fas fa-arrow-left me-2"></i> Previous Chapter
                        </span>
                    <?php endif; ?>
                </div>
                <div class="text-center">
                    <a href="series.php?id=<?php echo $story['series_id']; ?>" class="text-decoration-none">
                        View All Chapters
                    </a>
                </div>
                <div>
                    <?php if ($story['next_chapter_id']): ?>
                        <a href="story.php?id=<?php echo $story['next_chapter_id']; ?>" class="text-decoration-none text-primary">
                            Next Chapter <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    <?php else: ?>
                        <span class="text-secondary opacity-50">
                            Next Chapter <i class="fas fa-arrow-right ms-2"></i>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Author Info -->
    <div class="story-container mt-4">
        <h3 class="mb-3">About the Author</h3>
        <div class="d-flex align-items-center">
            <img src="<?php echo !empty($story['profile_image']) ? 'uploads/avatars/' . htmlspecialchars($story['profile_image']) : '/api/placeholder/80/80'; ?>" alt="Author avatar" class="rounded-circle me-4" style="width: 80px; height: 80px;">
            <div>
                <h4 class="mb-2">
                    <a href="profile.php?username=<?php echo urlencode($story['username']); ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($story['display_name'] ?: $story['username']); ?>
                    </a>
                </h4>
                <p class="mb-3"><?php echo $story['bio'] ? htmlspecialchars($story['bio']) : 'This author has not added a bio yet.'; ?></p>
                <div class="d-flex align-items-center">
                    <a href="profile.php?username=<?php echo urlencode($story['username']); ?>" class="btn btn-sm btn-outline-primary me-3">View Profile</a>
                    <span class="me-3"><i class="fas fa-book me-1"></i> <?php echo $story['author_stories_count']; ?> stories</span>
                    <?php if (isLoggedIn() && $_SESSION['user_id'] != $story['user_id']): ?>
                        <button class="btn btn-sm btn-outline-primary follow-btn" data-user-id="<?php echo $story['user_id']; ?>">Follow</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Comments Section -->
    <div id="comment-section" class="story-container comments-section">
        <h3 class="mb-4">Comments (<?php echo $story['comments_count']; ?>)</h3>
        
        <?php if (isLoggedIn()): ?>
            <!-- Comment Form -->
            <div class="mb-4">
                <form id="comment-form">
                    <input type="hidden" name="story_id" value="<?php echo $story['id']; ?>">
                    <input type="hidden" name="parent_id" value="">
                    <textarea class="form-control mb-3" name="content" rows="3" placeholder="Leave a comment..."></textarea>
                    <button type="submit" class="btn btn-primary">Post Comment</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-4">
                <a href="login.php" class="alert-link">Sign in</a> to leave a comment.
            </div>
        <?php endif; ?>
        
        <!-- Comments -->
        <div id="comments-container">
            <?php foreach ($comments as $comment): ?>
                <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                    <div class="comment-header">
                        <img src="<?php echo !empty($comment['profile_image']) ? 'uploads/avatars/' . htmlspecialchars($comment['profile_image']) : '/api/placeholder/40/40'; ?>" alt="Commenter avatar" class="comment-avatar">
                        <div>
                            <p class="mb-0">
                                <a href="profile.php?username=<?php echo urlencode($comment['username']); ?>" class="text-decoration-none fw-bold">
                                    <?php echo htmlspecialchars($comment['display_name'] ?: $comment['username']); ?>
                                </a>
                                <?php if ($comment['user_id'] == $story['user_id']): ?>
                                    <span class="badge bg-primary">Author</span>
                                <?php endif; ?>
                            </p>
                            <p class="comment-meta"><?php echo date('M j, Y \a\t g:i a', strtotime($comment['created_at'])); ?></p>
                        </div>
                    </div>
                    <div class="comment-content">
                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                    </div>
                    <div>
                        <?php if (isLoggedIn()): ?>
                            <button class="engagement-btn reply-btn" data-comment-id="<?php echo $comment['id']; ?>">
                                <i class="fas fa-reply"></i> Reply
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Reply Form - Hidden by default -->
                    <div class="reply-form-container mt-3" id="reply-form-<?php echo $comment['id']; ?>" style="display: none;">
                        <form class="reply-form">
                            <input type="hidden" name="story_id" value="<?php echo $story['id']; ?>">
                            <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                            <textarea class="form-control mb-2" name="content" rows="2" placeholder="Write a reply..."></textarea>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-sm btn-light me-2 cancel-reply-btn" data-comment-id="<?php echo $comment['id']; ?>">Cancel</button>
                                <button type="submit" class="btn btn-sm btn-primary">Post Reply</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- View Replies Link -->
                    <?php if ($comment['replies_count'] > 0): ?>
                        <div class="mt-2">
                            <button class="btn btn-sm btn-link view-replies-btn" data-comment-id="<?php echo $comment['id']; ?>" data-replies-count="<?php echo $comment['replies_count']; ?>">
                                <i class="fas fa-chevron-down me-1"></i> View <?php echo $comment['replies_count']; ?> <?php echo $comment['replies_count'] == 1 ? 'reply' : 'replies'; ?>
                            </button>
                        </div>
                        <div class="replies-container ms-5 mt-3" id="replies-<?php echo $comment['id']; ?>" style="display: none;">
                            <!-- Replies will be loaded here -->
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($comments)): ?>
                <div class="text-center py-4">
                    <p class="text-muted">No comments yet. Be the first to comment!</p>
                </div>
            <?php endif; ?>
            
            <?php if ($story['comments_count'] > count($comments)): ?>
                <div class="text-center mt-4">
                    <button class="btn btn-outline-primary" id="load-more-comments" data-story-id="<?php echo $story['id']; ?>" data-offset="<?php echo count($comments); ?>">
                        Load More Comments
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Like functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Like button
        const likeBtn = document.querySelector('.like-btn');
        if (likeBtn) {
            likeBtn.addEventListener('click', function() {
                <?php if (!isLoggedIn()): ?>
                    window.location.href = 'login.php';
                    return;
                <?php endif; ?>
                
                const storyId = this.getAttribute('data-story-id');
                const likesCountElement = this.querySelector('.likes-count');
                
                fetch('ajax/like.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `story_id=${storyId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.action === 'liked') {
                            likeBtn.classList.add('liked');
                            likeBtn.querySelector('i').classList.remove('far');
                            likeBtn.querySelector('i').classList.add('fas');
                        } else {
                            likeBtn.classList.remove('liked');
                            likeBtn.querySelector('i').classList.remove('fas');
                            likeBtn.querySelector('i').classList.add('far');
                        }
                        likesCountElement.textContent = data.likes_count;
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        }
        
        // Reply buttons
        const replyBtns = document.querySelectorAll('.reply-btn');
        replyBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const commentId = this.getAttribute('data-comment-id');
                const replyForm = document.getElementById(`reply-form-${commentId}`);
                replyForm.style.display = 'block';
            });
        });
        
        // Cancel reply buttons
        const cancelReplyBtns = document.querySelectorAll('.cancel-reply-btn');
        cancelReplyBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const commentId = this.getAttribute('data-comment-id');
                const replyForm = document.getElementById(`reply-form-${commentId}`);
                replyForm.style.display = 'none';
            });
        });
        
        // View replies buttons
        const viewRepliesBtns = document.querySelectorAll('.view-replies-btn');
        viewRepliesBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const commentId = this.getAttribute('data-comment-id');
                const repliesContainer = document.getElementById(`replies-${commentId}`);
                
                if (repliesContainer.style.display === 'none') {
                    // Load replies if container is empty
                    if (repliesContainer.innerHTML.trim() === '') {
                        repliesContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                        
                        fetch(`ajax/get_replies.php?comment_id=${commentId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                repliesContainer.innerHTML = '';
                                
                                data.replies.forEach(reply => {
                                    const replyHtml = `
                                        <div class="comment mb-3">
                                            <div class="comment-header">
                                                <img src="${reply.profile_image || '/api/placeholder/32/32'}" alt="Commenter avatar" class="comment-avatar" style="width: 32px; height: 32px;">
                                                <div>
                                                    <p class="mb-0">
                                                        <a href="profile.php?username=${encodeURIComponent(reply.username)}" class="text-decoration-none fw-bold">
                                                            ${reply.display_name || reply.username}
                                                        </a>
                                                        ${reply.user_id == <?php echo $story['user_id']; ?> ? '<span class="badge bg-primary">Author</span>' : ''}
                                                    </p>
                                                    <p class="comment-meta">${formatDate(reply.created_at)}</p>
                                                </div>
                                            </div>
                                            <div class="comment-content">
                                                ${reply.content.replace(/\n/g, '<br>')}
                                            </div>
                                        </div>
                                    `;
                                    repliesContainer.innerHTML += replyHtml;
                                });
                            } else {
                                repliesContainer.innerHTML = '<div class="alert alert-danger">Error loading replies</div>';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            repliesContainer.innerHTML = '<div class="alert alert-danger">Error loading replies</div>';
                        });
                    }
                    
                    repliesContainer.style.display = 'block';
                    this.innerHTML = '<i class="fas fa-chevron-up me-1"></i> Hide replies';
                } else {
                    repliesContainer.style.display = 'none';
                    this.innerHTML = `<i class="fas fa-chevron-down me-1"></i> View ${this.getAttribute('data-replies-count')} ${parseInt(this.getAttribute('data-replies-count')) === 1 ? 'reply' : 'replies'}`;
                }
            });
        });
        
        // Helper function to format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric',
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            });
        }
        
        // Comment form submission
        const commentForm = document.getElementById('comment-form');
        if (commentForm) {
            commentForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('ajax/add_comment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear form
                        this.reset();
                        
                        // Refresh comments
                        location.reload();
                    } else {
                        alert(data.message || 'Error posting comment');
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        }
        
        // Reply form submission
        const replyForms = document.querySelectorAll('.reply-form');
        replyForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('ajax/add_comment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear form
                        this.reset();
                        
                        // Hide form
                        this.parentElement.style.display = 'none';
                        
                        // Refresh page to show new reply
                        location.reload();
                    } else {
                        alert(data.message || 'Error posting reply');
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });
        
        // Load more comments functionality
        const loadMoreBtn = document.getElementById('load-more-comments');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                const storyId = this.getAttribute('data-story-id');
                const offset = this.getAttribute('data-offset');
                
                // Show loading indicator
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
                this.disabled = true;
                
                fetch(`ajax/get_more_comments.php?story_id=${storyId}&offset=${offset}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const commentsContainer = document.getElementById('comments-container');
                        
                        // Add new comments before the load more button
                        data.comments.forEach(comment => {
                            const commentHtml = `
                                <div class="comment" id="comment-${comment.id}">
                                    <div class="comment-header">
<img src="${comment.profile_image || '/api/placeholder/40/40'}" alt="Commenter avatar" class="comment-avatar">
                                        <div>
                                            <p class="mb-0">
                                                <a href="profile.php?username=${encodeURIComponent(comment.username)}" class="text-decoration-none fw-bold">
                                                    ${comment.display_name || comment.username}
                                                </a>
                                                ${comment.user_id == <?php echo $story['user_id']; ?> ? '<span class="badge bg-primary">Author</span>' : ''}
                                            </p>
                                            <p class="comment-meta">${formatDate(comment.created_at)}</p>
                                        </div>
                                    </div>
                                    <div class="comment-content">
                                        ${comment.content.replace(/\n/g, '<br>')}
                                    </div>
                                    <div>
                                        ${isLoggedIn ? `
                                            <button class="engagement-btn reply-btn" data-comment-id="${comment.id}">
                                                <i class="fas fa-reply"></i> Reply
                                            </button>
                                        ` : ''}
                                    </div>
                                    
                                    <!-- Reply Form - Hidden by default -->
                                    <div class="reply-form-container mt-3" id="reply-form-${comment.id}" style="display: none;">
                                        <form class="reply-form">
                                            <input type="hidden" name="story_id" value="<?php echo $story['id']; ?>">
                                            <input type="hidden" name="parent_id" value="${comment.id}">
                                            <textarea class="form-control mb-2" name="content" rows="2" placeholder="Write a reply..."></textarea>
                                            <div class="d-flex justify-content-end">
                                                <button type="button" class="btn btn-sm btn-light me-2 cancel-reply-btn" data-comment-id="${comment.id}">Cancel</button>
                                                <button type="submit" class="btn btn-sm btn-primary">Post Reply</button>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- View Replies Link -->
                                    ${comment.replies_count > 0 ? `
                                        <div class="mt-2">
                                            <button class="btn btn-sm btn-link view-replies-btn" data-comment-id="${comment.id}" data-replies-count="${comment.replies_count}">
                                                <i class="fas fa-chevron-down me-1"></i> View ${comment.replies_count} ${comment.replies_count == 1 ? 'reply' : 'replies'}
                                            </button>
                                        </div>
                                        <div class="replies-container ms-5 mt-3" id="replies-${comment.id}" style="display: none;">
                                            <!-- Replies will be loaded here -->
                                        </div>
                                    ` : ''}
                                </div>
                            `;
                            
                            // Insert before load more button
                            const loadMoreContainer = document.querySelector('#load-more-comments').parentNode;
                            loadMoreContainer.insertAdjacentHTML('beforebegin', commentHtml);
                        });
                        
                        // Update offset for next load
                        const newOffset = parseInt(offset) + data.comments.length;
                        this.setAttribute('data-offset', newOffset);
                        
                        // Reset button state
                        this.innerHTML = 'Load More Comments';
                        this.disabled = false;
                        
                        // Hide button if no more comments
                        if (data.comments.length < 10 || newOffset >= <?php echo $story['comments_count']; ?>) {
                            this.parentNode.style.display = 'none';
                        }
                        
                        // Attach event listeners to new elements
                        initializeNewCommentElements();
                    } else {
                        this.innerHTML = 'Error loading comments';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.innerHTML = 'Error loading comments';
                    this.disabled = false;
                });
            });
        }
        
        // Function to initialize event listeners for newly added comments
        function initializeNewCommentElements() {
            // Reply buttons
            document.querySelectorAll('.reply-btn:not([data-initialized])').forEach(btn => {
                btn.addEventListener('click', function() {
                    const commentId = this.getAttribute('data-comment-id');
                    const replyForm = document.getElementById(`reply-form-${commentId}`);
                    replyForm.style.display = 'block';
                });
                btn.setAttribute('data-initialized', 'true');
            });
            
            // Cancel reply buttons
            document.querySelectorAll('.cancel-reply-btn:not([data-initialized])').forEach(btn => {
                btn.addEventListener('click', function() {
                    const commentId = this.getAttribute('data-comment-id');
                    const replyForm = document.getElementById(`reply-form-${commentId}`);
                    replyForm.style.display = 'none';
                });
                btn.setAttribute('data-initialized', 'true');
            });
            
            // View replies buttons
            document.querySelectorAll('.view-replies-btn:not([data-initialized])').forEach(btn => {
                btn.addEventListener('click', function() {
                    const commentId = this.getAttribute('data-comment-id');
                    const repliesContainer = document.getElementById(`replies-${commentId}`);
                    
                    if (repliesContainer.style.display === 'none') {
                        // Load replies if container is empty
                        if (repliesContainer.innerHTML.trim() === '') {
                            repliesContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                            
                            fetch(`ajax/get_replies.php?comment_id=${commentId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    repliesContainer.innerHTML = '';
                                    
                                    data.replies.forEach(reply => {
                                        const replyHtml = `
                                            <div class="comment mb-3">
                                                <div class="comment-header">
                                                    <img src="${reply.profile_image || '/api/placeholder/32/32'}" alt="Commenter avatar" class="comment-avatar" style="width: 32px; height: 32px;">
                                                    <div>
                                                        <p class="mb-0">
                                                            <a href="profile.php?username=${encodeURIComponent(reply.username)}" class="text-decoration-none fw-bold">
                                                                ${reply.display_name || reply.username}
                                                            </a>
                                                            ${reply.user_id == <?php echo $story['user_id']; ?> ? '<span class="badge bg-primary">Author</span>' : ''}
                                                        </p>
                                                        <p class="comment-meta">${formatDate(reply.created_at)}</p>
                                                    </div>
                                                </div>
                                                <div class="comment-content">
                                                    ${reply.content.replace(/\n/g, '<br>')}
                                                </div>
                                            </div>
                                        `;
                                        repliesContainer.innerHTML += replyHtml;
                                    });
                                } else {
                                    repliesContainer.innerHTML = '<div class="alert alert-danger">Error loading replies</div>';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                repliesContainer.innerHTML = '<div class="alert alert-danger">Error loading replies</div>';
                            });
                        }
                        
                        repliesContainer.style.display = 'block';
                        this.innerHTML = '<i class="fas fa-chevron-up me-1"></i> Hide replies';
                    } else {
                        repliesContainer.style.display = 'none';
                        this.innerHTML = `<i class="fas fa-chevron-down me-1"></i> View ${this.getAttribute('data-replies-count')} ${parseInt(this.getAttribute('data-replies-count')) === 1 ? 'reply' : 'replies'}`;
                    }
                });
                btn.setAttribute('data-initialized', 'true');
            });
            
            // Reply form submission
            document.querySelectorAll('.reply-form:not([data-initialized])').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch('ajax/add_comment.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Clear form
                            this.reset();
                            
                            // Hide form
                            this.parentElement.style.display = 'none';
                            
                            // Refresh page to show new reply
                            location.reload();
                        } else {
                            alert(data.message || 'Error posting reply');
                        }
                    })
                    .catch(error => console.error('Error:', error));
                });
                form.setAttribute('data-initialized', 'true');
            });
        }
        
        // Initialize for bookmark button
        const bookmarkBtn = document.querySelector('.bookmark-btn');
        if (bookmarkBtn) {
            bookmarkBtn.addEventListener('click', function() {
                const storyId = this.getAttribute('data-story-id');
                
                fetch('ajax/bookmark.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `story_id=${storyId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.action === 'bookmarked') {
                            bookmarkBtn.innerHTML = '<i class="fas fa-bookmark"></i> Saved';
                            bookmarkBtn.classList.add('bookmarked');
                        } else {
                            bookmarkBtn.innerHTML = '<i class="fas fa-bookmark"></i> Save';
                            bookmarkBtn.classList.remove('bookmarked');
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        }
        
        // Initialize for follow button
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
                            followBtn.textContent = 'Following';
                            followBtn.classList.remove('btn-outline-primary');
                            followBtn.classList.add('btn-primary');
                        } else {
                            followBtn.textContent = 'Follow';
                            followBtn.classList.remove('btn-primary');
                            followBtn.classList.add('btn-outline-primary');
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        }
        
        // Check if user has bookmarked this story
        <?php if (isLoggedIn()): ?>
        fetch('ajax/check_bookmark.php?story_id=<?php echo $story['id']; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.bookmarked) {
                bookmarkBtn.innerHTML = '<i class="fas fa-bookmark"></i> Saved';
                bookmarkBtn.classList.add('bookmarked');
            }
        });
        
        // Check if user is following the author
        fetch('ajax/check_follow.php?user_id=<?php echo $story['user_id']; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.following) {
                followBtn.textContent = 'Following';
                followBtn.classList.remove('btn-outline-primary');
                followBtn.classList.add('btn-primary');
            }
        });
        <?php endif; ?>
    });
    
    // Variable for logged in state to use in dynamically created content
    const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
</script>

<?php
// Include footer
include_once "includes/footer.php";
?>
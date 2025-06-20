<?php
// Include config file
require_once "config.php";

// Set page title
$page_title = "Story Preview";

// Include header
include_once "includes/header.php";
?>

<div class="container py-5">
    <div class="story-container">
        <div class="mb-4">
            <h6 class="text-muted text-uppercase">Preview Mode</h6>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 id="story-title">Story Title</h1>
                <button class="btn btn-outline-secondary" onclick="window.close()">
                    <i class="fas fa-times me-1"></i> Close Preview
                </button>
            </div>
            <div class="author-info">
                <img src="<?php echo !empty($current_user['profile_image']) ? 'uploads/avatars/' . htmlspecialchars($current_user['profile_image']) : '/api/placeholder/60/60'; ?>" alt="Author avatar" class="author-avatar">
                <div>
                    <p class="author-name">
                        <?php echo htmlspecialchars($current_user['display_name'] ?: $current_user['username']); ?>
                    </p>
                    <p class="story-meta">
                        <span class="me-3"><i class="fas fa-calendar me-1"></i> <?php echo date('F j, Y'); ?></span>
                        <span id="word-count"><i class="fas fa-clock me-1"></i> 0 min read</span>
                    </p>
                </div>
            </div>
        </div>
        
        <div id="story-content" class="story-content">
            <!-- Story content will be loaded here -->
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get story preview data from localStorage
        const storyData = JSON.parse(localStorage.getItem('storyPreview') || '{"title":"Story Title","content":"No content available."}');
        
        // Set title
        document.getElementById('story-title').textContent = storyData.title || 'Story Title';
        document.title = storyData.title + ' - Preview - StoryNest';
        
        // Set content
        const contentElement = document.getElementById('story-content');
        contentElement.innerHTML = storyData.content || 'No content available.';
        
        // Calculate word count and reading time
        const text = contentElement.textContent || contentElement.innerText;
        const wordCount = text.trim().split(/\s+/).length || 0;
        const readingTime = Math.ceil(wordCount / 200); // Assuming 200 words per minute reading speed
        
        document.getElementById('word-count').innerHTML = `
            <i class="fas fa-clock me-1"></i> ${readingTime} min read
            <span class="word-count">${wordCount} words</span>
        `;
    });
</script>

<?php
// Include footer
include_once "includes/footer.php";
?>
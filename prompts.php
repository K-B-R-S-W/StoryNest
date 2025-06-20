<?php
// Include config file
require_once "config.php";

// Set page title
$page_title = "Writing Prompts";

// Get recent prompts (most would be stored in a separate table in a real app)
// This is a simplified version with hardcoded prompts
$prompts = [
    [
        'id' => 1,
        'title' => 'The Forgotten Door',
        'description' => 'Your character discovers a door in their house that they\'ve never noticed before. Where does it lead?',
        'category' => 'Fantasy',
        'submissions' => 24,
        'created_at' => '2025-04-15'
    ],
    [
        'id' => 2,
        'title' => 'The Last Message',
        'description' => 'Your character receives a cryptic text message from a number they don\'t recognize. The message reads: "I know what you did. Meet me at sunset."',
        'category' => 'Mystery',
        'submissions' => 18,
        'created_at' => '2025-04-20'
    ],
    [
        'id' => 3,
        'title' => 'Time Capsule',
        'description' => 'Your character unearths a time capsule they buried as a child, but it contains items they don\'t remember putting inside.',
        'category' => 'Science Fiction',
        'submissions' => 15,
        'created_at' => '2025-04-25'
    ],
    [
        'id' => 4,
        'title' => 'The Empty Train',
        'description' => 'Your character boards a train that should be crowded with commuters, but they\'re the only passenger. At the next stop, the doors don\'t open.',
        'category' => 'Horror',
        'submissions' => 22,
        'created_at' => '2025-04-30'
    ],
    [
        'id' => 5,
        'title' => 'The Unexpected Inheritance',
        'description' => 'Your character inherits a remote cabin from a relative they\'ve never heard of. When they arrive to inspect the property, they find something unexpected.',
        'category' => 'Thriller',
        'submissions' => 19,
        'created_at' => '2025-05-05'
    ],
    [
        'id' => 6,
        'title' => 'The Photograph',
        'description' => 'Your character finds an old photograph in an antique store. They recognize themselves in the picture, but it was taken decades before they were born.',
        'category' => 'Historical Fiction',
        'submissions' => 14,
        'created_at' => '2025-05-08'
    ]
];

// Get categories for filter
try {
    $stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

// Include header
include_once "includes/header.php";
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Writing Prompts</h1>
        <?php if (isLoggedIn()): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPromptModal">
                <i class="fas fa-plus me-2"></i> Submit Prompt
            </button>
        <?php endif; ?>
    </div>
    
    <div class="row">
        <div class="col-lg-3">
            <div class="sidebar-container mb-4">
                <h4 class="mb-3">Filter Prompts</h4>
                
                <div class="mb-3">
                    <label for="categoryFilter" class="form-label">Category</label>
                    <select class="form-select" id="categoryFilter">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="sortOrder" class="form-label">Sort By</label>
                    <select class="form-select" id="sortOrder">
                        <option value="newest">Newest First</option>
                        <option value="popular">Most Popular</option>
                    </select>
                </div>
                
                <div class="d-grid">
                    <button class="btn btn-outline-primary" id="applyFilters">Apply Filters</button>
                </div>
            </div>
            
            <div class="sidebar-container">
                <h4 class="mb-3">Need Inspiration?</h4>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary random-prompt-btn">
                        <i class="fas fa-random me-2"></i> Random Prompt
                    </button>
                    <a href="challenges.php" class="btn btn-outline-primary">
                        <i class="fas fa-trophy me-2"></i> Try a Challenge
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="editor-container">
                <div class="prompt-list">
                    <?php foreach ($prompts as $prompt): ?>
                        <div class="card mb-4 prompt-card" data-category="<?php echo htmlspecialchars($prompt['category']); ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h3 class="card-title"><?php echo htmlspecialchars($prompt['title']); ?></h3>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($prompt['category']); ?></span>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars($prompt['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="story-meta">
                                        <span class="me-3"><i class="fas fa-calendar me-1"></i> <?php echo date('M j, Y', strtotime($prompt['created_at'])); ?></span>
                                        <span><i class="fas fa-book me-1"></i> <?php echo $prompt['submissions']; ?> submissions</span>
                                    </div>
                                    <a href="create-story.php?prompt=<?php echo $prompt['id']; ?>" class="btn btn-sm btn-primary">Write Story</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isLoggedIn()): ?>
<!-- Create Prompt Modal -->
<div class="modal fade" id="createPromptModal" tabindex="-1" aria-labelledby="createPromptModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createPromptModalLabel">Submit a Writing Prompt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="promptForm">
                    <div class="mb-3">
                        <label for="promptTitle" class="form-label">Prompt Title</label>
                        <input type="text" class="form-control" id="promptTitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="promptDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="promptDescription" rows="4" required></textarea>
                        <div class="form-text">Write a clear, compelling prompt that inspires creativity.</div>
                    </div>
                    <div class="mb-3">
                        <label for="promptCategory" class="form-label">Category</label>
                        <select class="form-select" id="promptCategory" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitPrompt">Submit Prompt</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filter prompts by category
        const categoryFilter = document.getElementById('categoryFilter');
        const sortOrder = document.getElementById('sortOrder');
        const promptCards = document.querySelectorAll('.prompt-card');
        
        document.getElementById('applyFilters').addEventListener('click', function() {
            const selectedCategory = categoryFilter.value;
            const selectedSort = sortOrder.value;
            
            // Filter by category
            promptCards.forEach(card => {
                const cardCategory = card.getAttribute('data-category');
                
                if (selectedCategory === '' || cardCategory === selectedCategory) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Sort functionality would be implemented here with backend
            // In a real application, this would trigger an AJAX request
        });
        
        // Random prompt button
        document.querySelector('.random-prompt-btn').addEventListener('click', function() {
            const visiblePrompts = Array.from(promptCards).filter(card => 
                card.style.display !== 'none'
            );
            
            if (visiblePrompts.length > 0) {
                const randomIndex = Math.floor(Math.random() * visiblePrompts.length);
                const randomPrompt = visiblePrompts[randomIndex];
                
                // Highlight the random prompt
                promptCards.forEach(card => card.classList.remove('border-primary'));
                randomPrompt.classList.add('border-primary');
                
                // Scroll to the random prompt
                randomPrompt.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        
        <?php if (isLoggedIn()): ?>
        // Submit prompt form
        document.getElementById('submitPrompt').addEventListener('click', function() {
            const form = document.getElementById('promptForm');
            
            // Basic validation
            if (form.checkValidity()) {
                // In a real application, this would submit via AJAX
                alert('Thank you for submitting a prompt! It will be reviewed by our team.');
                
                // Close modal and reset form
                const modal = bootstrap.Modal.getInstance(document.getElementById('createPromptModal'));
                modal.hide();
                form.reset();
            } else {
                form.reportValidity();
            }
        });
        <?php endif; ?>
    });
</script>

<?php
// Include footer
include_once "includes/footer.php";
?>
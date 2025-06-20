<?php
// Include config file
require_once "config.php";

// Set page title
$page_title = "Writing Challenges";

// Get active challenges
try {
    $stmt = $conn->prepare("
        SELECT c.*, u.username, u.display_name, u.profile_image,
               (SELECT COUNT(*) FROM challenge_entries WHERE challenge_id = c.id) as entries_count
        FROM challenges c
        JOIN users u ON c.created_by = u.id
        WHERE c.status = 'active'
        ORDER BY c.end_date ASC
    ");
    $stmt->execute();
    $active_challenges = $stmt->fetchAll();
} catch(PDOException $e) {
    $active_challenges = [];
}

// Get upcoming challenges
try {
    $stmt = $conn->prepare("
        SELECT c.*, u.username, u.display_name 
        FROM challenges c
        JOIN users u ON c.created_by = u.id
        WHERE c.status = 'upcoming'
        ORDER BY c.start_date ASC
        LIMIT 3
    ");
    $stmt->execute();
    $upcoming_challenges = $stmt->fetchAll();
} catch(PDOException $e) {
    $upcoming_challenges = [];
}

// Get completed challenges
try {
    $stmt = $conn->prepare("
        SELECT c.*, u.username, u.display_name,
               (SELECT COUNT(*) FROM challenge_entries WHERE challenge_id = c.id) as entries_count
        FROM challenges c
        JOIN users u ON c.created_by = u.id
        WHERE c.status = 'completed'
        ORDER BY c.end_date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $completed_challenges = $stmt->fetchAll();
} catch(PDOException $e) {
    $completed_challenges = [];
}

// Get user's challenge entries if logged in
$user_entries = [];
if (isLoggedIn()) {
    try {
        $stmt = $conn->prepare("
            SELECT ce.*, c.title as challenge_title, s.title as story_title
            FROM challenge_entries ce
            JOIN challenges c ON ce.challenge_id = c.id
            JOIN stories s ON ce.story_id = s.id
            WHERE ce.user_id = :user_id
            ORDER BY ce.submission_date DESC
        ");
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->execute();
        $user_entries = $stmt->fetchAll();
    } catch(PDOException $e) {
        $user_entries = [];
    }
}

// Include header
include_once "includes/header.php";
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Writing Challenges</h1>
        <?php if (isLoggedIn()): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#suggestChallengeModal">
                <i class="fas fa-lightbulb me-2"></i> Suggest Challenge
            </button>
        <?php endif; ?>
    </div>
    
    <div class="row mb-5">
        <div class="col-md-8">
            <p class="lead">Participate in timed writing challenges to spark your creativity, improve your writing skills, and connect with other writers.</p>
        </div>
        <div class="col-md-4">
            <div class="d-flex justify-content-end">
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="challengeFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-filter me-2"></i> Filter Challenges
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="challengeFilterDropdown">
                        <li><button class="dropdown-item active" data-filter="active">Active Challenges</button></li>
                        <li><button class="dropdown-item" data-filter="upcoming">Upcoming Challenges</button></li>
                        <li><button class="dropdown-item" data-filter="completed">Completed Challenges</button></li>
                        <?php if (isLoggedIn()): ?>
                            <li><button class="dropdown-item" data-filter="my-entries">My Entries</button></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Active Challenges -->
    <div class="challenge-section" id="active-challenges">
        <h2 class="mb-4">Active Challenges</h2>
        
        <?php if (count($active_challenges) > 0): ?>
            <div class="row">
                <?php foreach ($active_challenges as $challenge): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h3 class="card-title"><?php echo htmlspecialchars($challenge['title']); ?></h3>
                                    <div class="badge bg-success">Active</div>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars($challenge['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="challenge-meta">
                                        <div class="mb-2">
                                            <i class="fas fa-calendar-alt me-1"></i> 
                                            <span>Ends in <?php echo ceil((strtotime($challenge['end_date']) - time()) / (60*60*24)); ?> days</span>
                                        </div>
                                        <div>
                                            <i class="fas fa-users me-1"></i> 
                                            <span><?php echo $challenge['entries_count']; ?> participants</span>
                                        </div>
                                    </div>
                                    <a href="challenge.php?id=<?php echo $challenge['id']; ?>" class="btn btn-primary">View Challenge</a>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted">
                                    Created by 
                                    <a href="profile.php?username=<?php echo urlencode($challenge['username']); ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($challenge['display_name'] ?: $challenge['username']); ?>
                                    </a>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="editor-container text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-calendar-times text-muted" style="font-size: 3rem;"></i>
                </div>
                <h4>No active challenges right now</h4>
                <p class="text-muted">Check back soon for new writing challenges!</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Upcoming Challenges -->
    <div class="challenge-section mt-5" id="upcoming-challenges" style="display: none;">
        <h2 class="mb-4">Upcoming Challenges</h2>
        
        <?php if (count($upcoming_challenges) > 0): ?>
            <div class="row">
                <?php foreach ($upcoming_challenges as $challenge): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h3 class="card-title"><?php echo htmlspecialchars($challenge['title']); ?></h3>
                                    <div class="badge bg-info">Upcoming</div>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars($challenge['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="challenge-meta">
                                        <div class="mb-2">
                                            <i class="fas fa-calendar-alt me-1"></i> 
                                            <span>Starts in <?php echo ceil((strtotime($challenge['start_date']) - time()) / (60*60*24)); ?> days</span>
                                        </div>
                                        <div>
                                            <i class="fas fa-hourglass-half me-1"></i> 
                                            <span>Duration: <?php echo ceil((strtotime($challenge['end_date']) - strtotime($challenge['start_date'])) / (60*60*24)); ?> days</span>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-primary remind-me-btn" data-challenge-id="<?php echo $challenge['id']; ?>">
                                        <i class="fas fa-bell me-1"></i> Remind Me
                                    </button>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted">
                                    Created by 
                                    <a href="profile.php?username=<?php echo urlencode($challenge['username']); ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($challenge['display_name'] ?: $challenge['username']); ?>
                                    </a>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="editor-container text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-calendar-plus text-muted" style="font-size: 3rem;"></i>
                </div>
                <h4>No upcoming challenges</h4>
                <p class="text-muted">Check back soon or suggest a challenge!</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Completed Challenges -->
    <div class="challenge-section mt-5" id="completed-challenges" style="display: none;">
        <h2 class="mb-4">Completed Challenges</h2>
        
        <?php if (count($completed_challenges) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Challenge</th>
                            <th>Participants</th>
                            <th>End Date</th>
                            <th>Winner</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completed_challenges as $challenge): ?>
                            <tr>
                                <td>
                                    <a href="challenge.php?id=<?php echo $challenge['id']; ?>" class="text-decoration-none fw-bold">
                                        <?php echo htmlspecialchars($challenge['title']); ?>
                                    </a>
                                </td>
                                <td><?php echo $challenge['entries_count']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($challenge['end_date'])); ?></td>
                                <td>
                                    <?php
                                    // In a real implementation, this would query for the winning entry
                                    echo "View Results";
                                    ?>
                                </td>
                                <td>
                                    <a href="challenge.php?id=<?php echo $challenge['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View Entries
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="text-center mt-4">
                <a href="#" class="btn btn-outline-primary">View All Completed Challenges</a>
            </div>
        <?php else: ?>
            <div class="editor-container text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-trophy text-muted" style="font-size: 3rem;"></i>
                </div>
                <h4>No completed challenges yet</h4>
                <p class="text-muted">Complete challenges will appear here</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- My Entries (visible only when logged in) -->
    <?php if (isLoggedIn()): ?>
    <div class="challenge-section mt-5" id="my-entries" style="display: none;">
        <h2 class="mb-4">My Challenge Entries</h2>
        
        <?php if (count($user_entries) > 0): ?>
            <div class="editor-container">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Challenge</th>
                                <th>Submission</th>
                                <th>Date Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_entries as $entry): ?>
                                <tr>
                                    <td>
                                        <a href="challenge.php?id=<?php echo $entry['challenge_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($entry['challenge_title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="story.php?id=<?php echo $entry['story_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($entry['story_title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($entry['submission_date'])); ?></td>
                                    <td>
                                        <?php if ($entry['rank']): ?>
                                            <span class="badge bg-success">Ranked #<?php echo $entry['rank']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="story.php?id=<?php echo $entry['story_id']; ?>" class="btn btn-sm btn-outline-primary">View Story</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="editor-container text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-file-alt text-muted" style="font-size: 3rem;"></i>
                </div>
                <h4>No challenge entries yet</h4>
                <p class="text-muted">Participate in a writing challenge to see your entries here</p>
                <?php if (count($active_challenges) > 0): ?>
                    <a href="#active-challenges" class="btn btn-primary mt-2 show-active-challenges">View Active Challenges</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- How Challenges Work Section -->
    <div class="editor-container mt-5">
        <h2 class="mb-4">How Writing Challenges Work</h2>
        <div class="row">
            <div class="col-md-3 text-center mb-4">
                <div class="mb-3">
                    <i class="fas fa-search text-primary" style="font-size: 2.5rem;"></i>
                </div>
                <h5>1. Find a Challenge</h5>
                <p>Browse active writing challenges and find one that inspires you.</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="mb-3">
                    <i class="fas fa-pencil-alt text-primary" style="font-size: 2.5rem;"></i>
                </div>
                <h5>2. Write Your Entry</h5>
                <p>Create a new story based on the challenge prompt and requirements.</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="mb-3">
                    <i class="fas fa-paper-plane text-primary" style="font-size: 2.5rem;"></i>
                </div>
                <h5>3. Submit Your Entry</h5>
                <p>Submit your story to the challenge before the deadline.</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="mb-3">
                    <i class="fas fa-award text-primary" style="font-size: 2.5rem;"></i>
                </div>
                <h5>4. Get Recognition</h5>
                <p>Receive feedback and potentially win recognition for your work.</p>
            </div>
        </div>
        <div class="text-center mt-3">
            <a href="community.php" class="btn btn-outline-primary">Learn More About Our Community</a>
        </div>
    </div>
</div>

<?php if (isLoggedIn()): ?>
<!-- Suggest Challenge Modal -->
<div class="modal fade" id="suggestChallengeModal" tabindex="-1" aria-labelledby="suggestChallengeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="suggestChallengeModalLabel">Suggest a Writing Challenge</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="suggestChallengeForm">
                    <div class="mb-3">
                        <label for="challengeTitle" class="form-label">Challenge Title</label>
                        <input type="text" class="form-control" id="challengeTitle" name="title" placeholder="Give your challenge a catchy title" required>
                    </div>
                    <div class="mb-3">
                        <label for="challengeDescription" class="form-label">Challenge Description</label>
                        <textarea class="form-control" id="challengeDescription" name="description" rows="5" placeholder="Describe the challenge, prompt, and any specific requirements or guidelines" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="challengeStartDate" class="form-label">Suggested Start Date</label>
                            <input type="date" class="form-control" id="challengeStartDate" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="challengeDuration" class="form-label">Duration (days)</label>
                            <select class="form-select" id="challengeDuration" name="duration" required>
                                <option value="7">1 week</option>
                                <option value="14" selected>2 weeks</option>
                                <option value="30">1 month</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="challengeCategory" class="form-label">Category</label>
                        <select class="form-select" id="challengeCategory" name="category_id" required>
                            <option value="">Select a category</option>
                            <?php
                            // Get categories from database
                            try {
                                $stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
                                $stmt->execute();
                                $categories = $stmt->fetchAll();
                                
                                foreach ($categories as $category) {
                                    echo '<option value="' . $category['id'] . '">' . htmlspecialchars($category['name']) . '</option>';
                                }
                            } catch(PDOException $e) {
                                // Silently fail
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="challengeRules" class="form-label">Special Rules (Optional)</label>
                        <textarea class="form-control" id="challengeRules" name="rules" rows="3" placeholder="Any specific rules or constraints for the challenge (e.g., word count limits, specific themes to include)"></textarea>
                    </div>
                </form>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Challenge suggestions will be reviewed by our team before being published.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitChallenge">Submit Suggestion</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Challenge filter functionality
        const filterButtons = document.querySelectorAll('[data-filter]');
        const challengeSections = document.querySelectorAll('.challenge-section');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                // Hide all sections
                challengeSections.forEach(section => {
                    section.style.display = 'none';
                });
                
                // Show the selected section
                document.getElementById(filter + '-challenges').style.display = 'block';
                
                // Update active state of buttons
                filterButtons.forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
                
                // Update dropdown button text
                document.getElementById('challengeFilterDropdown').innerHTML = 
                    '<i class="fas fa-filter me-2"></i> ' + 
                    (filter === 'my-entries' ? 'My Entries' : filter.charAt(0).toUpperCase() + filter.slice(1) + ' Challenges');
            });
        });
        
        // Show active challenges button in My Entries section
        const showActiveBtn = document.querySelector('.show-active-challenges');
        if (showActiveBtn) {
            showActiveBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Trigger click on the active challenges filter button
                document.querySelector('[data-filter="active"]').click();
            });
        }
        
        <?php if (isLoggedIn()): ?>
        // Set default date for challenge start date (tomorrow)
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('challengeStartDate').value = tomorrow.toISOString().substr(0, 10);
        
        // Submit challenge suggestion
        document.getElementById('submitChallenge').addEventListener('click', function() {
            const form = document.getElementById('suggestChallengeForm');
            
            if (form.checkValidity()) {
                // In a real implementation, this would send via AJAX
                alert('Thank you for suggesting a challenge! Our team will review your suggestion.');
                
                // Close modal and reset form
                const modal = bootstrap.Modal.getInstance(document.getElementById('suggestChallengeModal'));
                modal.hide();
                form.reset();
                
                // Set default date again
                document.getElementById('challengeStartDate').value = tomorrow.toISOString().substr(0, 10);
            } else {
                form.reportValidity();
            }
        });
        
        // Remind me functionality
        const remindBtns = document.querySelectorAll('.remind-me-btn');
        remindBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const challengeId = this.getAttribute('data-challenge-id');
                
                // In a real implementation, this would send via AJAX
                this.innerHTML = '<i class="fas fa-check me-1"></i> Reminder Set';
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-success');
                this.disabled = true;
            });
        });
        <?php endif; ?>
    });
</script>

<?php
// Include footer
include_once "includes/footer.php";
?>
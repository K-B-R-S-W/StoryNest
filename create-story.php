<?php
// Include config file
require_once "config.php";

// Check if the user is logged in
if (!isLoggedIn()) {
    setFlashMessage("error", "You must be logged in to create a story.");
    redirect("login.php");
    exit;
}

// Define variables and initialize with empty values
$title = $content = $excerpt = $category_id = $series_id = $chapter_number = "";
$title_err = $content_err = $category_id_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate title
    if (empty(trim($_POST["title"]))) {
        $title_err = "Please enter a title for your story.";
    } elseif (strlen(trim($_POST["title"])) > 255) {
        $title_err = "Title cannot exceed 255 characters.";
    } else {
        $title = trim($_POST["title"]);
    }
    
    // Validate content
    if (empty(trim($_POST["content"]))) {
        $content_err = "Please enter some content for your story.";
    } else {
        $content = trim($_POST["content"]);
    }
    
    // Validate category
    if (empty(trim($_POST["category_id"]))) {
        $category_id_err = "Please select a category.";
    } else {
        $category_id = trim($_POST["category_id"]);
    }
    
    // Get excerpt
    $excerpt = trim($_POST["excerpt"]);
    
    // Handle series options
    if (isset($_POST["series_option"]) && $_POST["series_option"] === "series") {
        if ($_POST["series_id"] === "new") {
            // Create a new series first
            if (!empty(trim($_POST["series_title"]))) {
                $series_title = trim($_POST["series_title"]);
                $series_description = trim($_POST["series_description"]);
                
                try {
                    $stmt = $conn->prepare("INSERT INTO series (user_id, title, description) VALUES (:user_id, :title, :description)");
                    $stmt->bindParam(":user_id", $_SESSION["user_id"]);
                    $stmt->bindParam(":title", $series_title);
                    $stmt->bindParam(":description", $series_description);
                    $stmt->execute();
                    
                    $series_id = $conn->lastInsertId();
                } catch(PDOException $e) {
                    setFlashMessage("error", "Error creating series: " . $e->getMessage());
                    $series_id = null;
                }
            }
        } else {
            $series_id = !empty(trim($_POST["series_id"])) ? trim($_POST["series_id"]) : null;
        }
        
        $chapter_number = !empty(trim($_POST["chapter_number"])) ? trim($_POST["chapter_number"]) : null;
    } else {
        $series_id = null;
        $chapter_number = null;
    }
    
    // Handle mature content flag
    $mature_content = isset($_POST["mature_content"]) ? 1 : 0;
    
    // Handle comments allowed flag
    $allow_comments = isset($_POST["allow_comments"]) ? 1 : 0;
    
    // Get the status (draft or published)
    $status = $_POST["action"] === "draft" ? "draft" : "published";
    
    // Calculate word count
    $word_count = str_word_count(strip_tags($content));
    
    // Check input errors before inserting into database
    if (empty($title_err) && empty($content_err) && empty($category_id_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO stories (user_id, title, content, excerpt, category_id, series_id, chapter_number, word_count, status) 
                VALUES (:user_id, :title, :content, :excerpt, :category_id, :series_id, :chapter_number, :word_count, :status)";
        
        try {
            $stmt = $conn->prepare($sql);
            
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":user_id", $_SESSION["user_id"]);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":content", $content);
            $stmt->bindParam(":excerpt", $excerpt);
            $stmt->bindParam(":category_id", $category_id);
            $stmt->bindParam(":series_id", $series_id);
            $stmt->bindParam(":chapter_number", $chapter_number);
            $stmt->bindParam(":word_count", $word_count);
            $stmt->bindParam(":status", $status);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                $story_id = $conn->lastInsertId();
                
                setFlashMessage("success", $status === "published" ? "Your story has been published successfully!" : "Your draft has been saved successfully!");
                redirect("story.php?id=" . $story_id);
            } else {
                setFlashMessage("error", "Oops! Something went wrong. Please try again later.");
            }
            
            // Close statement
            unset($stmt);
        } catch(PDOException $e) {
            setFlashMessage("error", "Error: " . $e->getMessage());
        }
    }
    
    // Close connection
    unset($conn);
}

// Get list of categories for the dropdown
try {
    $stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

// Get user's series for the dropdown
try {
    $stmt = $conn->prepare("SELECT id, title FROM series WHERE user_id = :user_id ORDER BY title");
    $stmt->bindParam(":user_id", $_SESSION["user_id"]);
    $stmt->execute();
    $series_list = $stmt->fetchAll();
} catch(PDOException $e) {
    $series_list = [];
}

// Include header
include_once "includes/header.php";
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-9">
            <div class="editor-container">
                <h2 class="mb-4">Create Your Story</h2>
                
                <?php displayFlashMessage(); ?>
                
                <form id="story-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label for="title" class="form-label">Story Title</label>
                        <input type="text" class="form-control form-control-lg <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" id="title" name="title" placeholder="Enter your story title" value="<?php echo $title; ?>">
                        <div class="invalid-feedback"><?php echo $title_err; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="excerpt" class="form-label">Short Description</label>
                        <textarea class="form-control" id="excerpt" name="excerpt" rows="2" placeholder="Brief description of your story (shown in listings)"><?php echo $excerpt; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select <?php echo (!empty($category_id_err)) ? 'is-invalid' : ''; ?>" id="category" name="category_id">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?php echo $category_id_err; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="series_option" id="standalone" value="standalone" <?php echo (empty($series_id)) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="standalone">Standalone Story</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="series_option" id="series" value="series" <?php echo (!empty($series_id)) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="series">Part of a Series</label>
                        </div>
                    </div>
                    
                    <div class="series-options mb-3" style="display: <?php echo (!empty($series_id)) ? 'block' : 'none'; ?>;">
                        <div class="row">
                            <div class="col-md-8">
                                <select class="form-select" id="series_id" name="series_id">
                                    <option value="">Select a series</option>
                                    <option value="new">Create new series</option>
                                    <?php foreach ($series_list as $series): ?>
                                        <option value="<?php echo $series['id']; ?>" <?php echo ($series_id == $series['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($series['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="number" class="form-control" id="chapter_number" name="chapter_number" min="1" placeholder="Chapter #" value="<?php echo $chapter_number; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="new-series-options mb-3" style="display: none;">
                        <div class="mb-3">
                            <label for="series_title" class="form-label">Series Title</label>
                            <input type="text" class="form-control" id="series_title" name="series_title" placeholder="Enter series title">
                        </div>
                        <div class="mb-3">
                            <label for="series_description" class="form-label">Series Description</label>
                            <textarea class="form-control" id="series_description" name="series_description" rows="2" placeholder="Brief description of your series"></textarea>
                        </div>
                    </div>
                    
                    <div class="editor-toolbar">
                        <div class="save-status">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Ready to write</span>
                        </div>
                        <div class="word-count">
                            <span>0 words</span>
                        </div>
                    </div>
                    
                    <div id="editor-container">
                        <div id="editor"><?php echo $content; ?></div>
                    </div>
                    <input type="hidden" id="content" name="content" value="<?php echo htmlspecialchars($content); ?>">
                    <div class="invalid-feedback d-block"><?php echo $content_err; ?></div>
                    
                    <div class="mt-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="allow_comments" name="allow_comments" checked>
                            <label class="form-check-label" for="allow_comments">Allow comments on this story</label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="mature_content" name="mature_content">
                            <label class="form-check-label" for="mature_content">This story contains mature content</label>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <div>
                                <button type="submit" class="btn btn-outline-primary" name="action" value="draft">Save Draft</button>
                            </div>
                            <div>
                                <button type="button" class="btn btn-light me-2" id="preview">Preview</button>
                                <button type="submit" class="btn btn-primary" name="action" value="publish">Publish Story</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-lg-3">
            <div class="sidebar-container">
                <h4 class="mb-3">Writing Tips</h4>
                
                <div class="card mb-3">
                    <div class="card-header">Getting Started</div>
                    <div class="card-body">
                        <p>Start with a compelling hook that immediately grabs your reader's attention.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">Read More</a>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">Character Development</div>
                    <div class="card-body">
                        <p>Create multi-dimensional characters with clear motivations and flaws.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">Read More</a>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">Show, Don't Tell</div>
                    <div class="card-body">
                        <p>Let readers experience the story through actions, words, thoughts, and sensory details.</p>
                        <a href="#" class="btn btn-sm btn-outline-primary">Read More</a>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <h5 class="mb-3">Need Inspiration?</h5>
                <div class="d-grid gap-2">
                    <a href="prompts.php" class="btn btn-outline-primary btn-sm">Try a Writing Prompt</a>
                    <a href="challenges.php" class="btn btn-outline-primary btn-sm">Join a Challenge</a>
                </div>
                
                <hr class="my-4">
                
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="darkModeToggle">
                    <label class="form-check-label" for="darkModeToggle">Dark Mode</label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Quill Editor JS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>

<script>
    // Initialize Quill editor
    var quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'align': [] }],
                ['link', 'image'],
                ['clean']
            ]
        },
        placeholder: 'Start writing your story...'
    });
    
    // Series options toggle
    document.querySelectorAll('input[name="series_option"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.value === 'series') {
                document.querySelector('.series-options').style.display = 'block';
            } else {
                document.querySelector('.series-options').style.display = 'none';
                document.querySelector('.new-series-options').style.display = 'none';
            }
        });
    });
    
    // New series options toggle
    document.getElementById('series_id').addEventListener('change', function() {
        if (this.value === 'new') {
            document.querySelector('.new-series-options').style.display = 'block';
        } else {
            document.querySelector('.new-series-options').style.display = 'none';
        }
    });
    
    // Word counter
    quill.on('text-change', function() {
        let text = quill.getText();
        // Subtract 1 for the trailing newline that Quill adds
        let wordCount = text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
        document.querySelector('.word-count span').textContent = wordCount + ' words';
        
        // Update hidden input with content
        document.getElementById('content').value = quill.root.innerHTML;
        
        // Update save status
        let saveStatus = document.querySelector('.save-status');
        saveStatus.innerHTML = '<i class="fas fa-circle text-warning"></i><span>Unsaved changes</span>';
    });
    
    // Form submission
    document.getElementById('story-form').addEventListener('submit', function() {
        // Update hidden input with content before form submission
        document.getElementById('content').value = quill.root.innerHTML;
    });
    
    // Dark mode toggle
    document.getElementById('darkModeToggle').addEventListener('change', function() {
        document.body.classList.toggle('dark-mode');
    });
    
    // Preview button
    document.getElementById('preview').addEventListener('click', function() {
        // Store content in localStorage
        localStorage.setItem('storyPreview', JSON.stringify({
            title: document.getElementById('title').value,
            content: quill.root.innerHTML
        }));
        
        // Open preview in new window
        window.open('preview.php', '_blank');
    });
</script>

<?php
// Include footer
include_once "includes/footer.php";
?>
<?php
// Include config file
require_once "config.php";

// Get current page for active navigation
$current_page = basename($_SERVER['PHP_SELF']);

// Get current user if logged in
$current_user = null;
if (isLoggedIn() && isset($conn)) {
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $current_user = $stmt->fetch();
    } catch(PDOException $e) {
        // Silently fail
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - StoryNest' : 'StoryNest'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #6c63ff;
            --secondary-color: #f9f9f9;
            --text-color: #333;
            --light-text: #777;
            --accent-color: #ff6584;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            background-color: #f8f9fa;
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        
        .dropdown-item:hover {
            background-color: var(--secondary-color);
        }
        
        .container {
            max-width: 1200px;
        }
        
        footer {
            background-color: #fff;
            padding: 2rem 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            margin-top: 3rem;
        }
        
        /* Dark mode styles */
        .dark-mode {
            background-color: #222;
            color: #f8f9fa;
        }
        
        .dark-mode .navbar,
        .dark-mode .dropdown-menu,
        .dark-mode footer,
        .dark-mode .card {
            background-color: #333;
            color: #f8f9fa;
        }
        
        .dark-mode .dropdown-item:hover {
            background-color: #444;
        }
        
        .dark-mode .card-header {
            background-color: #444;
        }
        
        .dark-mode .text-dark {
            color: #f8f9fa !important;
        }
        
        .dark-mode .text-secondary {
            color: #adb5bd !important;
        }
        
        .dark-mode .text-muted {
            color: #adb5bd !important;
        }
        
        /* Custom styles for this site */
        .editor-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .sidebar-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 1.5rem;
            height: fit-content;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }
        
        .story-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .story-meta {
            font-size: 0.9rem;
            color: var(--light-text);
        }
        
        .nav-tabs {
            border-bottom: none;
        }
        
        .nav-tabs .nav-link {
            border: none;
            padding: 0.75rem 1.5rem;
            margin: 0 0.5rem;
            color: var(--light-text);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--primary-color);
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .profile-header {
            background: linear-gradient(135deg, #fff, var(--secondary-color));
            padding: 3rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        /* Dark mode editor styles */
        .dark-mode .editor-container,
        .dark-mode .sidebar-container,
        .dark-mode .story-container {
            background-color: #333;
            color: #f8f9fa;
        }
        
        .dark-mode .ql-editor {
            background-color: #333;
            color: #f8f9fa;
        }
        
        .dark-mode .ql-toolbar {
            background-color: #444;
            border-color: #555;
        }
        
        .dark-mode .ql-picker-label,
        .dark-mode .ql-picker-options {
            background-color: #444 !important;
            color: #f8f9fa !important;
        }
        
        .dark-mode .ql-stroke {
            stroke: #f8f9fa !important;
        }
        
        .dark-mode .ql-fill {
            fill: #f8f9fa !important;
        }
        
        .dark-mode .form-control,
        .dark-mode .form-select {
            background-color: #444;
            border-color: #555;
            color: #f8f9fa;
        }
        
        .dark-mode .profile-header {
            background: linear-gradient(135deg, #333, #2d2d2d);
        }
        
    </style>
    <?php if (isset($custom_css)): ?>
    <style>
        <?php echo $custom_css; ?>
    </style>
    <?php endif; ?>
</head>
<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="index.php">StoryNest</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'categories.php') ? 'active' : ''; ?>" href="categories.php">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'prompts.php') ? 'active' : ''; ?>" href="prompts.php">Writing Prompts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'challenges.php') ? 'active' : ''; ?>" href="challenges.php">Challenges</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'community.php') ? 'active' : ''; ?>" href="community.php">Community</a>
                    </li>
                </ul>
                
                <?php if (isLoggedIn() && $current_user): ?>
                    <!-- Logged in user menu -->
                    <div class="d-flex align-items-center">
                        <a href="create-story.php" class="btn btn-primary btn-sm me-3">Write a Story</a>
                        <div class="dropdown">
                            <a href="#" class="text-decoration-none dropdown-toggle d-flex align-items-center" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?php echo !empty($current_user['profile_image']) ? 'uploads/avatars/' . $current_user['profile_image'] : 'assets/images/images.png'; ?>" class="rounded-circle me-2" alt="Profile picture" width="40" height="40">
                                <span class="d-none d-md-inline"><?php echo htmlspecialchars($current_user['username']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="profile.php?username=<?php echo urlencode($current_user['username']); ?>">My Profile</a></li>
                                <li><a class="dropdown-item" href="my-stories.php">My Stories</a></li>
                                <li><a class="dropdown-item" href="bookmarks.php">Bookmarks</a></li>
                                <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Sign Out</a></li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Guest menu -->
                    <div class="d-flex">
                        <a href="login.php" class="btn btn-outline-primary me-2">Sign In</a>
                        <a href="register.php" class="btn btn-primary">Join Now</a>
                    </div>
                <?php endif; ?>
                <!-- Dark Mode Toggle (Top Right) -->
                <div class="ms-3 d-flex align-items-center">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="darkModeToggle" style="cursor:pointer;">
                        <span id="darkModeIcon" class="ms-2"><i class="fa-regular fa-moon"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <?php if (function_exists('displayFlashMessage')): ?>
        <div class="container mt-3">
            <?php displayFlashMessage(); ?>
        </div>
    <?php endif; ?>

        <!-- Bootstrap JS (required for dropdowns) -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function enableDarkMode() {
        document.body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
        updateDarkModeUI(true);
    }
    function disableDarkMode() {
        document.body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
        updateDarkModeUI(false);
    }
    function updateDarkModeUI(isDarkMode) {
        // Update header icon
        const darkModeIcon = document.getElementById('darkModeIcon');
        if (darkModeIcon) {
            darkModeIcon.innerHTML = isDarkMode
                ? '<i class="fa-solid fa-sun"></i>'
                : '<i class="fa-regular fa-moon"></i>';
        }
        // Update header button (if exists)
        const darkModeBtn = document.getElementById('darkModeBtn');
        if (darkModeBtn && darkModeBtn.querySelector('i')) {
            if (isDarkMode) {
                darkModeBtn.querySelector('i').classList.remove('fa-moon');
                darkModeBtn.querySelector('i').classList.add('fa-sun');
            } else {
                darkModeBtn.querySelector('i').classList.remove('fa-sun');
                darkModeBtn.querySelector('i').classList.add('fa-moon');
            }
        }
        // Update sidebar toggle if exists
        const darkModeToggle = document.getElementById('darkModeToggle');
        if (darkModeToggle) {
            darkModeToggle.checked = isDarkMode;
        }
    }
    // Check for saved theme preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        enableDarkMode();
    } else {
        disableDarkMode();
    }
    // Handle dark mode toggle in header
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                enableDarkMode();
            } else {
                disableDarkMode();
            }
        });
    }
});
</script>

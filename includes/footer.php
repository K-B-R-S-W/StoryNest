<?php
/**
 * Footer template for StoryNest
 * 
 * This file contains the footer section that appears at the bottom of every page.
 * It includes copyright information, navigation links, social media links, and JavaScript.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/darkmode.css">
        </main><!-- Close the main content section -->
        
        <!-- Modified footer section with dark mode toggle -->
<footer class="footer mt-auto py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 mb-4 mb-lg-0">
                <h5 class="mb-3">StoryNest</h5>
                <p>A creative community for writers to share, grow, and inspire each other. Join thousands of storytellers from around the world.</p>
                <div class="d-flex social-links">
                    <a href="#" class="me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                <h6 class="mb-3">Explore</h6>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="categories.php" class="nav-link p-0">Categories</a></li>
                    <li class="nav-item mb-2"><a href="stories.php" class="nav-link p-0">Stories</a></li>
                    <li class="nav-item mb-2"><a href="challenges.php" class="nav-link p-0">Challenges</a></li>
                    <li class="nav-item mb-2"><a href="prompts.php" class="nav-link p-0">Writing Prompts</a></li>
                    <li class="nav-item mb-2"><a href="community.php" class="nav-link p-0">Community</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                <h6 class="mb-3">Resources</h6>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="#" class="nav-link p-0">Writing Tips</a></li>
                    <li class="nav-item mb-2"><a href="#" class="nav-link p-0">Grammar Guide</a></li>
                    <li class="nav-item mb-2"><a href="#" class="nav-link p-0">Character Development</a></li>
                    <li class="nav-item mb-2"><a href="#" class="nav-link p-0">Plot Structure</a></li>
                    <li class="nav-item mb-2"><a href="#" class="nav-link p-0">Dialogue Tips</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                <h6 class="mb-3">Company</h6>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="#" class="nav-link p-0">About Us</a></li>
                    <li class="nav-item mb-2"><a href="#" class="nav-link p-0">Contact</a></li>
                    <li class="nav-item mb-2"><a href="#" class="nav-link p-0">Privacy Policy</a></li>
                    <li class="nav-item mb-2"><a href="#" class="nav-link p-0">Terms of Service</a></li>
                    <li class="nav-item mb-2"><a href="#" class="nav-link p-0">FAQ</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-12">
                <div class="row">
                    <div class="col-lg-12 mb-3">
                        <h6 class="mb-3">Subscribe</h6>
                        <form class="newsletter-form">
                            <div class="input-group mb-3">
                                <input type="email" class="form-control" placeholder="Your email" aria-label="Your email">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
            <p>&copy; <?php echo date('Y'); ?> StoryNest. All rights reserved.</p>
            <div>
                <a href="#" class="text-decoration-none me-3">Privacy</a>
                <a href="#" class="text-decoration-none me-3">Terms</a>
                <a href="#" class="text-decoration-none">Contact</a>
            </div>
        </div>
    </div>
</footer>

        <!-- Bootstrap JS Bundle with Popper -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
        
        <!-- Custom theme script that should be included at the end -->
        <?php if (isset($custom_css)): ?>
        <style>
            <?php echo $custom_css; ?>
        </style>
        <?php endif; ?>
        
        <script>
          /**
 * Modified footer.php dark mode section 
 * Replace the script section in your footer.php with this code
 */

<!-- Dark Mode Script - Updated version -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Define dark mode functions for reuse
        function enableDarkMode() {
            document.body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
            
            // Update all UI elements
            updateDarkModeUI(true);
            
            console.log('Dark mode enabled');
        }

        function disableDarkMode() {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
            
            // Update all UI elements
            updateDarkModeUI(false);
            
            console.log('Dark mode disabled');
        }
        
        function updateDarkModeUI(isDarkMode) {
            // Update header button
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
            
            // Update footer toggle if exists
            const footerDarkModeToggle = document.getElementById('footerDarkModeToggle');
            if (footerDarkModeToggle) {
                footerDarkModeToggle.checked = isDarkMode;
            }
        }

        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme');
        console.log('Saved theme preference:', savedTheme);
        
        if (savedTheme === 'dark') {
            enableDarkMode();
        } else {
            disableDarkMode();
        }
        
        // Handle dark mode toggle in header
        const darkModeBtn = document.getElementById('darkModeBtn');
        if (darkModeBtn) {
            darkModeBtn.addEventListener('click', function() {
                console.log('Dark mode button clicked');
                if (document.body.classList.contains('dark-mode')) {
                    disableDarkMode();
                } else {
                    enableDarkMode();
                }
            });
        }
        
        // Handle dark mode toggle checkbox in sidebar
        const darkModeToggle = document.getElementById('darkModeToggle');
        if (darkModeToggle) {
            darkModeToggle.addEventListener('change', function() {
                console.log('Dark mode toggle changed to:', this.checked);
                if (this.checked) {
                    enableDarkMode();
                } else {
                    disableDarkMode();
                }
            });
        }
        
        // Handle dark mode toggle in footer
        const footerDarkModeToggle = document.getElementById('footerDarkModeToggle');
        if (footerDarkModeToggle) {
            footerDarkModeToggle.addEventListener('change', function() {
                console.log('Footer dark mode toggle changed to:', this.checked);
                if (this.checked) {
                    enableDarkMode();
                } else {
                    disableDarkMode();
                }
            });
        }
    });
    
    // Handle flash message auto-close
    const flashAlerts = document.querySelectorAll('.alert-dismissible:not([data-bs-no-auto-close])');
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });
    
    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (tooltipTriggerList.length > 0) {
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
</script>
        </script>
    </body>
</html>
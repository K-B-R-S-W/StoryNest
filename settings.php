<?php
// Include config file
require_once "config.php";

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage("error", "You must be logged in to access settings.");
    redirect("login.php");
    exit;
}

// Set page title
$page_title = "Account Settings";

// Get current user data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch();
} catch(PDOException $e) {
    setFlashMessage("error", "Error retrieving user data: " . $e->getMessage());
    redirect("index.php");
    exit;
}

// Initialize variables
$display_name = $user['display_name'];
$email = $user['email'];
$bio = $user['bio'];
$website = $user['website'];
$twitter = $user['twitter'];
$instagram = $user['instagram'];
$current_password = $new_password = $confirm_password = "";
$profile_image = $user['profile_image'];

// Error variables
$display_name_err = $email_err = $bio_err = $website_err = $twitter_err = $instagram_err = "";
$current_password_err = $new_password_err = $confirm_password_err = $profile_image_err = "";

// Process profile form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    
    // Validate display name (optional)
    if (!empty(trim($_POST["display_name"]))) {
        $display_name = trim($_POST["display_name"]);
    } else {
        $display_name = null; // Use username as display name
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        $email = trim($_POST["email"]);
        // Check if email is changed and already exists
        if ($email != $user['email']) {
            $sql = "SELECT id FROM users WHERE email = :email AND id != :user_id";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bindParam(":email", $email);
                $stmt->bindParam(":user_id", $_SESSION['user_id']);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $email_err = "This email is already taken.";
                    $email = $user['email']; // Revert to original email
                }
            }
        }
    }
    
    // Validate bio (optional)
    $bio = trim($_POST["bio"]);
    
    // Validate website (optional)
    if (!empty(trim($_POST["website"]))) {
        $website = trim($_POST["website"]);
        if (!filter_var($website, FILTER_VALIDATE_URL)) {
            $website_err = "Please enter a valid URL.";
            $website = $user['website']; // Revert to original website
        }
    } else {
        $website = null;
    }
    
    // Validate social handles (optional)
    $twitter = trim($_POST["twitter"]);
    $instagram = trim($_POST["instagram"]);
    
    // Check if there are no errors
    if (empty($display_name_err) && empty($email_err) && empty($bio_err) && 
        empty($website_err) && empty($twitter_err) && empty($instagram_err)) {
        
        // Update user profile
        $sql = "UPDATE users SET display_name = :display_name, email = :email, bio = :bio, 
                website = :website, twitter = :twitter, instagram = :instagram 
                WHERE id = :user_id";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bindParam(":display_name", $display_name);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":bio", $bio);
            $stmt->bindParam(":website", $website);
            $stmt->bindParam(":twitter", $twitter);
            $stmt->bindParam(":instagram", $instagram);
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                setFlashMessage("success", "Your profile has been updated successfully.");
            } else {
                setFlashMessage("error", "Oops! Something went wrong. Please try again later.");
            }
        }
    }
}

// Process password form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    
    // Validate current password
    if (empty(trim($_POST["current_password"]))) {
        $current_password_err = "Please enter your current password.";
    } else {
        $current_password = trim($_POST["current_password"]);
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $current_password_err = "Current password is incorrect.";
        }
    }
    
    // Validate new password
    if (empty(trim($_POST["new_password"]))) {
        $new_password_err = "Please enter a new password.";
    } elseif (strlen(trim($_POST["new_password"])) < 6) {
        $new_password_err = "Password must have at least 6 characters.";
    } else {
        $new_password = trim($_POST["new_password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($new_password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = "Passwords did not match.";
        }
    }
    
    // Check if there are no errors
    if (empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)) {
        
        // Update password
        $sql = "UPDATE users SET password = :password WHERE id = :user_id";
        
        if ($stmt = $conn->prepare($sql)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                setFlashMessage("success", "Your password has been updated successfully.");
            } else {
                setFlashMessage("error", "Oops! Something went wrong. Please try again later.");
            }
        }
    }
}

// Process profile image upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_image'])) {
    
    if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == 0) {
        $allowed_types = ["image/jpeg", "image/jpg", "image/png"];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES["profile_image"]["type"], $allowed_types)) {
            $profile_image_err = "Only JPG, JPEG, and PNG files are allowed.";
        } elseif ($_FILES["profile_image"]["size"] > $max_size) {
            $profile_image_err = "Image size must be less than 2MB.";
        } else {
            // Create uploads directory if it doesn't exist
            if (!file_exists("uploads/avatars")) {
                mkdir("uploads/avatars", 0777, true);
            }
            
            // Generate unique filename
            $filename = $_SESSION['user_id'] . '_' . time() . '_' . basename($_FILES["profile_image"]["name"]);
            $upload_path = "uploads/avatars/" . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $upload_path)) {
                
                // Delete old profile image if it exists and is not the default
                if (!empty($user['profile_image']) && $user['profile_image'] != 'default_avatar.jpg' && file_exists("uploads/avatars/" . $user['profile_image'])) {
                    unlink("uploads/avatars/" . $user['profile_image']);
                }
                
                // Update profile image in database
                $sql = "UPDATE users SET profile_image = :profile_image WHERE id = :user_id";
                
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bindParam(":profile_image", $filename);
                    $stmt->bindParam(":user_id", $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        $profile_image = $filename;
                        setFlashMessage("success", "Your profile image has been updated successfully.");
                    } else {
                        setFlashMessage("error", "Oops! Something went wrong. Please try again later.");
                    }
                }
            } else {
                $profile_image_err = "Error uploading file.";
            }
        }
    } elseif ($_FILES["profile_image"]["error"] != 4) { // Error 4 means no file was uploaded
        $profile_image_err = "Error uploading file: " . $_FILES["profile_image"]["error"];
    }
}

// Process account deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'on') {
        
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Delete user's likes
            $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = :user_id");
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            $stmt->execute();
            
            // Delete user's comments
            $stmt = $conn->prepare("DELETE FROM comments WHERE user_id = :user_id");
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            $stmt->execute();
            
            // Delete user's bookmarks
            $stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = :user_id");
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            $stmt->execute();
            
            // Delete user's follows
            $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = :user_id OR followed_id = :user_id");
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            $stmt->execute();
            
            // Set user's stories to archived
            $stmt = $conn->prepare("UPDATE stories SET status = 'archived' WHERE user_id = :user_id");
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            $stmt->execute();
            
            // Delete user's account
            $stmt = $conn->prepare("DELETE FROM users WHERE id = :user_id");
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Unset session variables and destroy session
            session_unset();
            session_destroy();
            
            // Redirect to home page
            redirect("index.php?msg=account_deleted");
            exit;
            
        } catch(PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            setFlashMessage("error", "Error deleting account: " . $e->getMessage());
        }
    } else {
        setFlashMessage("error", "Please confirm account deletion by checking the box.");
    }
}

// Include header
include_once "includes/header.php";
?>

<div class="container py-5">
    <h1 class="mb-4">Account Settings</h1>
    
    <div class="row">
        <div class="col-lg-3">
            <div class="sidebar-container mb-4">
                <div class="list-group">
                    <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">Profile Information</a>
                    <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="list">Change Password</a>
                    <a href="#image" class="list-group-item list-group-item-action" data-bs-toggle="list">Profile Image</a>
                    <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="list">Notification Settings</a>
                    <a href="#privacy" class="list-group-item list-group-item-action" data-bs-toggle="list">Privacy Settings</a>
                    <a href="#delete" class="list-group-item list-group-item-action list-group-item-danger" data-bs-toggle="list">Delete Account</a>
                </div>
            </div>
            
            <div class="sidebar-container text-center">
                <img src="<?php echo !empty($profile_image) ? 'uploads/avatars/' . htmlspecialchars($profile_image) : '/api/placeholder/150/150'; ?>" alt="Profile Picture" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                <h5><?php echo htmlspecialchars($user['username']); ?></h5>
                <p class="text-muted small">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                <a href="profile.php?username=<?php echo urlencode($user['username']); ?>" class="btn btn-sm btn-outline-primary">View Public Profile</a>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="tab-content">
                <!-- Profile Information -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="editor-container">
                        <h3 class="mb-4">Profile Information</h3>
                        
                        <?php displayFlashMessage(); ?>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <div class="form-text">Username cannot be changed.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="display_name" class="form-label">Display Name</label>
                                <input type="text" class="form-control <?php echo (!empty($display_name_err)) ? 'is-invalid' : ''; ?>" id="display_name" name="display_name" value="<?php echo htmlspecialchars($display_name); ?>">
                                <div class="form-text">This is the name that will be shown to other users.</div>
                                <div class="invalid-feedback"><?php echo $display_name_err; ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                                <div class="invalid-feedback"><?php echo $email_err; ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control <?php echo (!empty($bio_err)) ? 'is-invalid' : ''; ?>" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($bio); ?></textarea>
                                <div class="form-text">Tell other users about yourself.</div>
                                <div class="invalid-feedback"><?php echo $bio_err; ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control <?php echo (!empty($website_err)) ? 'is-invalid' : ''; ?>" id="website" name="website" value="<?php echo htmlspecialchars($website); ?>">
                                <div class="invalid-feedback"><?php echo $website_err; ?></div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="twitter" class="form-label">Twitter Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text">@</span>
                                        <input type="text" class="form-control <?php echo (!empty($twitter_err)) ? 'is-invalid' : ''; ?>" id="twitter" name="twitter" value="<?php echo htmlspecialchars($twitter); ?>">
                                    </div>
                                    <div class="invalid-feedback"><?php echo $twitter_err; ?></div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="instagram" class="form-label">Instagram Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text">@</span>
                                        <input type="text" class="form-control <?php echo (!empty($instagram_err)) ? 'is-invalid' : ''; ?>" id="instagram" name="instagram" value="<?php echo htmlspecialchars($instagram); ?>">
                                    </div>
                                    <div class="invalid-feedback"><?php echo $instagram_err; ?></div>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" name="update_profile">Update Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="tab-pane fade" id="password">
                    <div class="editor-container">
                        <h3 class="mb-4">Change Password</h3>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control <?php echo (!empty($current_password_err)) ? 'is-invalid' : ''; ?>" id="current_password" name="current_password">
                                <div class="invalid-feedback"><?php echo $current_password_err; ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" id="new_password" name="new_password">
                                <div class="form-text">Password must be at least 6 characters long.</div>
                                <div class="invalid-feedback"><?php echo $new_password_err; ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password">
                                <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" name="update_password">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Profile Image -->
                <div class="tab-pane fade" id="image">
                    <div class="editor-container">
                        <h3 class="mb-4">Profile Image</h3>
                        
                        <div class="text-center mb-4">
                            <img src="<?php echo !empty($profile_image) ? 'uploads/avatars/' . htmlspecialchars($profile_image) : '/api/placeholder/200/200'; ?>" alt="Profile Picture" class="img-thumbnail" style="width: 200px; height: 200px; object-fit: cover;">
                        </div>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="profile_image" class="form-label">Upload New Image</label>
                                <input type="file" class="form-control <?php echo (!empty($profile_image_err)) ? 'is-invalid' : ''; ?>" id="profile_image" name="profile_image" accept="image/jpeg, image/jpg, image/png">
                                <div class="form-text">Max file size: 2MB. Allowed formats: JPG, JPEG, PNG.</div>
                                <div class="invalid-feedback"><?php echo $profile_image_err; ?></div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" name="update_image">Upload Image</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Notification Settings -->
                <div class="tab-pane fade" id="notifications">
                    <div class="editor-container">
                        <h3 class="mb-4">Notification Settings</h3>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" checked>
                                    <label class="form-check-label" for="email_notifications">Email Notifications</label>
                                </div>
                                <div class="form-text">Receive email notifications for new comments, likes, and follows.</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="comment_notifications" name="comment_notifications" checked>
                                    <label class="form-check-label" for="comment_notifications">Comment Notifications</label>
                                </div>
                                <div class="form-text">Receive notifications when someone comments on your stories.</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="like_notifications" name="like_notifications" checked>
                                    <label class="form-check-label" for="like_notifications">Like Notifications</label>
                                </div>
                                <div class="form-text">Receive notifications when someone likes your stories.</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="follow_notifications" name="follow_notifications" checked>
                                    <label class="form-check-label" for="follow_notifications">Follow Notifications</label>
                                </div>
                                <div class="form-text">Receive notifications when someone follows you.</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="challenge_notifications" name="challenge_notifications" checked>
                                    <label class="form-check-label" for="challenge_notifications">Challenge Notifications</label>
                                </div>
                                <div class="form-text">Receive notifications about new writing challenges.</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" checked>
                                    <label class="form-check-label" for="newsletter">Writing Newsletter</label>
                                </div>
                                <div class="form-text">Receive our weekly newsletter with writing tips and community highlights.</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" name="update_notifications">Save Preferences</button>
                            </div>
                        </form>
                        
                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i> Notification settings are not currently functional in this demo.
                        </div>
                    </div>
                </div>
                
                <!-- Privacy Settings -->
                <div class="tab-pane fade" id="privacy">
                    <div class="editor-container">
                        <h3 class="mb-4">Privacy Settings</h3>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="public_profile" name="public_profile" checked>
                                    <label class="form-check-label" for="public_profile">Public Profile</label>
                                </div>
                                <div class="form-text">Allow others to view your profile and stories.</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="show_email" name="show_email">
                                    <label class="form-check-label" for="show_email">Show Email</label>
                                </div>
                                <div class="form-text">Display your email address on your public profile.</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="show_social" name="show_social" checked>
                                    <label class="form-check-label" for="show_social">Show Social Links</label>
                                </div>
                                <div class="form-text">Display your social media links on your public profile.</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="allow_messages" name="allow_messages" checked>
                                    <label class="form-check-label" for="allow_messages">Allow Messages</label>
                                </div>
                                <div class="form-text">Allow other users to send you private messages.</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" name="update_privacy">Save Privacy Settings</button>
                            </div>
                        </form>
                        
                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i> Privacy settings are not currently functional in this demo.
                        </div>
                    </div>
                </div>
                
                <!-- Delete Account -->
                <div class="tab-pane fade" id="delete">
                    <div class="editor-container">
                        <h3 class="mb-4">Delete Account</h3>
                        
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i> Warning!</h5>
                            <p>Deleting your account is permanent and cannot be undone. All of your stories will be archived and all of your personal data will be removed from our system.</p>
                        </div>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirm_delete" name="confirm_delete">
                                    <label class="form-check-label" for="confirm_delete">
                                        I understand that deleting my account is permanent and cannot be undone.
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger" name="delete_account">Delete My Account</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show active tab from URL hash
        const hash = window.location.hash;
        if (hash) {
            const tab = document.querySelector(`.list-group-item[href="${hash}"]`);
            if (tab) {
                tab.click();
            }
        }
        
        // Update URL hash when tab changes
        const tabLinks = document.querySelectorAll('.list-group-item');
        tabLinks.forEach(link => {
            link.addEventListener('shown.bs.tab', function(e) {
                window.location.hash = e.target.getAttribute('href');
                
                // Update active class
                tabLinks.forEach(l => l.classList.remove('active'));
                e.target.classList.add('active');
            });
        });
    });
</script>

<?php
// Include footer
include_once "includes/footer.php";
?>
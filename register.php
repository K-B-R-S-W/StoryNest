<?php
// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))) {
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = :username";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $username_err = "This username is already taken.";
                }
            } else {
                setFlashMessage("error", "Oops! Something went wrong. Please try again later.");
            }
            
            // Close statement
            unset($stmt);
        }
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE email = :email";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":email", $param_email);
            
            // Set parameters
            $param_email = trim($_POST["email"]);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $email_err = "This email is already registered.";
                }
            } else {
                setFlashMessage("error", "Oops! Something went wrong. Please try again later.");
            }
            
            // Close statement
            unset($stmt);
        }
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Passwords did not match.";
        }
    }
    
    // Check input errors before inserting in database
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, email, password, display_name) VALUES (:username, :email, :password, :display_name)";
         
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username);
            $stmt->bindParam(":email", $param_email);
            $stmt->bindParam(":password", $param_password);
            $stmt->bindParam(":display_name", $param_display_name);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            $param_email = trim($_POST["email"]);
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_display_name = trim($_POST["username"]); // Use username as display name initially
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Get the user ID of the newly registered user
                $user_id = $conn->lastInsertId();
                
                // Store user in session and redirect to home page
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $param_username;
                
                setFlashMessage("success", "Your account has been created successfully. Welcome to StoryNest!");
                redirect("index.php");
            } else {
                setFlashMessage("error", "Oops! Something went wrong. Please try again later.");
            }
            
            // Close statement
            unset($stmt);
        }
    }
    
    // Close connection
    unset($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - StoryNest</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .register-container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 2rem;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-text {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 2rem;
            margin: 0;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(108, 99, 255, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-weight: 600;
        }
        
        .error-feedback {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        /* Dark mode styles */
        .dark-mode {
            background-color: #222 !important;
            color: #f8f9fa !important;
        }
        .dark-mode .register-container {
            background: #333 !important;
            color: #f8f9fa !important;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .dark-mode .form-control {
            background-color: #444 !important;
            color: #f8f9fa !important;
            border-color: #555 !important;
        }
        .dark-mode .form-control:focus {
            border-color: #6c63ff !important;
            box-shadow: 0 0 0 0.2rem rgba(108,99,255,0.25) !important;
        }
        .dark-mode .btn-primary {
            background-color: #6c63ff !important;
            color: #fff !important;
        }
        .dark-mode .logo-text {
            color: #a9a7ff !important;
        }
        .dark-mode .form-check-label,
        .dark-mode a {
            color: #adb5bd !important;
        }
        .dark-mode .error-feedback {
            color: #ff6584 !important;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <h1 class="logo-text">StoryNest</h1>
            <p>Join our community of writers</p>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo $username; ?>">
                <div class="error-feedback"><?php echo $username_err; ?></div>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo $email; ?>">
                <div class="error-feedback"><?php echo $email_err; ?></div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" id="password" name="password">
                <div class="error-feedback"><?php echo $password_err; ?></div>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password">
                <div class="error-feedback"><?php echo $confirm_password_err; ?></div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                <label class="form-check-label" for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Create Account</button>
            </div>
            
            <div class="text-center mt-3">
                <p>Already have an account? <a href="login.php">Sign In</a></p>
            </div>
        </form>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    // Apply dark mode if user's preference is set
    (function() {
        try {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
        } catch (e) {}
    })();
    </script>
</body>
</html>
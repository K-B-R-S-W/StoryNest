<?php
// Include config file
require_once "config.php";

// Check if the user is already logged in
if (isLoggedIn()) {
    redirect("index.php");
}

// Define variables and initialize with empty values
$username_email = $password = "";
$username_email_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if username/email is empty
    if (empty(trim($_POST["username_email"]))) {
        $username_email_err = "Please enter username or email.";
    } else {
        $username_email = trim($_POST["username_email"]);
    }
    
    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if (empty($username_email_err) && empty($password_err)) {
        // Determine if input is username or email
        $is_email = filter_var($username_email, FILTER_VALIDATE_EMAIL);
        
        // Prepare a select statement based on input type
        if ($is_email) {
            $sql = "SELECT id, username, password FROM users WHERE email = :username_email";
        } else {
            $sql = "SELECT id, username, password FROM users WHERE username = :username_email";
        }
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username_email", $param_username_email);
            
            // Set parameters
            $param_username_email = $username_email;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Check if username/email exists, if yes then verify password
                if ($stmt->rowCount() == 1) {
                    if ($row = $stmt->fetch()) {
                        $id = $row["id"];
                        $username = $row["username"];
                        $hashed_password = $row["password"];
                        
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            
                            // Store data in session variables
                            $_SESSION["user_id"] = $id;
                            $_SESSION["username"] = $username;
                            
                            // Update last login time
                            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                            $update_stmt->bindParam(":id", $id);
                            $update_stmt->execute();
                            
                            // Redirect user to welcome page
                            redirect("index.php");
                        } else {
                            // Password is not valid
                            $login_err = "Invalid username/email or password.";
                        }
                    }
                } else {
                    // Username/email doesn't exist
                    $login_err = "Invalid username/email or password.";
                }
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
    <title>Login - StoryNest</title>
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
        
        .login-container {
            max-width: 450px;
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
        .dark-mode .login-container {
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
    <div class="login-container">
        <div class="logo">
            <h1 class="logo-text">StoryNest</h1>
            <p>Sign in to continue</p>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <?php 
        if (!empty($login_err)) {
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }        
        ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-3">
                <label for="username_email" class="form-label">Username or Email</label>
                <input type="text" class="form-control <?php echo (!empty($username_email_err)) ? 'is-invalid' : ''; ?>" id="username_email" name="username_email" value="<?php echo $username_email; ?>">
                <div class="error-feedback"><?php echo $username_email_err; ?></div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" id="password" name="password">
                <div class="error-feedback"><?php echo $password_err; ?></div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Sign In</button>
            </div>
            
            <div class="text-center mt-3">
                <p><a href="forgot-password.php">Forgot password?</a></p>
                <p>Don't have an account? <a href="register.php">Create Account</a></p>
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
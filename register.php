<?php
require_once 'includes/auth.php';
require_once 'includes/middleware.php';

$auth = new Auth();
$middleware = new Middleware();

// Redirect if already logged in
$middleware->requireGuest();

// Set security headers
$middleware->setSecurityHeaders();

// Handle registration form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $middleware->validateCSRF();
    
    // Rate limiting: 3 attempts per 5 minutes
    $middleware->rateLimit('register_' . $_SERVER['REMOTE_ADDR'], 3, 300);

    // Sanitize inputs
    $username = $middleware->sanitizeInput($_POST['username']);
    $email = $middleware->sanitizeInput($_POST['email']);
    $first_name = $middleware->sanitizeInput($_POST['first_name']);
    $last_name = $middleware->sanitizeInput($_POST['last_name']);
    $password = $_POST['password']; // Don't sanitize password
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    $errors = [];

    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // If no validation errors, proceed with registration
    if (empty($errors)) {
        $result = $auth->register($username, $email, $password, $first_name, $last_name);
        
        if ($result['success']) {
            $success = "Registration successful! You can now login.";
        } else {
            $error = $result['message'];
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Generate CSRF token
$csrf_token = $middleware->generateCSRFToken();

// Set cookie consent
$middleware->setCookieConsent();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - FoodFusion</title>
    <!-- Bootstrap CSS -->
    <link href="bstp/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 2rem auto;
        }
        .cookie-consent {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 1rem;
            z-index: 1000;
        }
        .password-strength {
            height: 5px;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">FoodFusion</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="recipes.php">Recipes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cookbook.php">Cookbook</a>
                    </li>
                </ul>
                <a href="login.php" class="btn btn-outline-primary">Login</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="register-container">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="text-center mb-4">Create an Account</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo $success; ?>
                            <br>
                            <a href="login.php" class="alert-link">Click here to login</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php" id="registerForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required
                                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required
                                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required
                                   pattern="[a-zA-Z0-9_]{3,50}"
                                   title="Username must be between 3 and 50 characters and can only contain letters, numbers, and underscores"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required
                                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                       title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength mt-2"></div>
                            <small class="text-muted">Password must be at least 8 characters long and include uppercase, lowercase, and numbers</small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>

                    <div class="text-center mt-3">
                        Already have an account? <a href="login.php">Login here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($middleware->isPendingCookieConsent()): ?>
    <div class="cookie-consent" id="cookieConsent">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <p class="mb-0">
                        We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies.
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-2 mt-md-0">
                    <button class="btn btn-light me-2" onclick="acceptCookies()">Accept</button>
                    <button class="btn btn-outline-light" onclick="rejectCookies()">Reject</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="bstp/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strength = document.querySelector('.password-strength');
            let score = 0;

            // Length check
            if (password.length >= 8) score++;
            // Uppercase check
            if (/[A-Z]/.test(password)) score++;
            // Lowercase check
            if (/[a-z]/.test(password)) score++;
            // Number check
            if (/\d/.test(password)) score++;
            // Special character check
            if (/[^A-Za-z0-9]/.test(password)) score++;

            // Update strength indicator
            strength.style.width = (score * 20) + '%';
            
            switch(score) {
                case 0:
                case 1:
                    strength.style.backgroundColor = '#dc3545'; // red
                    break;
                case 2:
                case 3:
                    strength.style.backgroundColor = '#ffc107'; // yellow
                    break;
                case 4:
                case 5:
                    strength.style.backgroundColor = '#198754'; // green
                    break;
            }
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;

            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });

        // Cookie consent functions
        function acceptCookies() {
            document.getElementById('cookieConsent').style.display = 'none';
            // Send AJAX request to accept cookies
            fetch('ajax/cookies.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=accept'
            });
        }

        function rejectCookies() {
            document.getElementById('cookieConsent').style.display = 'none';
            // Send AJAX request to reject cookies
            fetch('ajax/cookies.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=reject'
            });
        }
    </script>
</body>
</html> 
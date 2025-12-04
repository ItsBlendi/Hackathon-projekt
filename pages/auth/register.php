<?php
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . 'includes/auth_functions.php';
    require_once PROJECT_ROOT . 'config/database.php';
} else {
    require_once __DIR__ . '/../../includes/auth_functions.php';
    require_once __DIR__ . '/../../config/database.php';
}

$page_title = 'Create Account - GameVerse';
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form inputs
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $agree_terms = isset($_POST['agree_terms']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!$agree_terms) {
        $error = 'You must agree to the Terms of Service and Privacy Policy.';
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username is already taken. Please choose another.';
            $stmt->close();
        } else {
            $stmt->close();
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Email is already registered. Please use another or login.';
                $stmt->close();
            } else {
                $stmt->close();
                
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, email_verified, is_admin, created_at) VALUES (?, ?, ?, ?, ?, 0, 0, NOW())");
                $stmt->bind_param('sssss', $username, $email, $hashed_password, $first_name, $last_name);
                
                if ($stmt->execute()) {
                    $user_id = $stmt->insert_id;
                    $stmt->close();
                    
                    // Auto-login the user
                    session_start();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['logged_in'] = true;
                    
                    // Redirect to dashboard
                    header('Location: ../../index.php?page=dashboard');
                    exit();
                } else {
                    $error = 'Registration failed. Please try again.';
                    $stmt->close();
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Applied professional dark theme styling matching login page */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #0a0a1a;
            color: #f0f0f0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            padding: 2rem 0;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(0, 243, 255, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(188, 19, 254, 0.08) 0%, transparent 50%);
            z-index: 0;
        }

        .register-container {
            width: 100%;
            max-width: 550px;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 2.5rem 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-logo {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }

        .register-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #fff;
        }

        .register-header .highlight {
            color: #00f3ff;
            text-shadow: 0 0 20px rgba(0, 243, 255, 0.4);
        }

        .register-header p {
            color: #b8c2cc;
            font-size: 0.9rem;
        }

        .alert {
            padding: 0.875rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background-color: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #b8c2cc;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group label small {
            color: #8b95a0;
            font-weight: 400;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            color: #fff;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #00f3ff;
            box-shadow: 0 0 0 3px rgba(0, 243, 255, 0.1);
            background: rgba(255, 255, 255, 0.08);
        }

        .form-control.is-invalid {
            border-color: rgba(239, 68, 68, 0.5);
        }

        .form-text {
            display: block;
            margin-top: 0.375rem;
            font-size: 0.8rem;
            color: #8b95a0;
        }

        .password-input {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #b8c2cc;
            cursor: pointer;
            padding: 0.5rem;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: #00f3ff;
        }

        .password-strength {
            margin-top: 0.5rem;
        }

        .progress {
            height: 5px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            margin-bottom: 0.375rem;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background-color: #dc3545;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        .password-strength-text {
            font-size: 0.8rem;
            color: #8b95a0;
        }

        .password-strength-text span {
            font-weight: 600;
        }

        .password-match {
            display: none;
            margin-top: 0.375rem;
        }

        .password-match small {
            font-size: 0.8rem;
            color: #fca5a5;
        }

        .row {
            display: flex;
            gap: 1rem;
            margin: 0;
        }

        .col-md-6 {
            flex: 1;
            min-width: 0;
        }

        .form-check {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            margin-right: 0.75rem;
            margin-top: 0.125rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background-color: rgba(255, 255, 255, 0.05);
            cursor: pointer;
            accent-color: #00f3ff;
            flex-shrink: 0;
        }

        .form-check-label {
            color: #b8c2cc;
            font-size: 0.875rem;
            cursor: pointer;
            line-height: 1.5;
        }

        .form-check-label a {
            color: #00f3ff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .form-check-label a:hover {
            color: #bc13fe;
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            background-color: #00f3ff;
            color: #0a0a1a;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover:not(:disabled) {
            background-color: #00d9e6;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 243, 255, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .auth-footer p {
            color: #b8c2cc;
            font-size: 0.95rem;
        }

        .auth-footer a {
            color: #00f3ff;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .auth-footer a:hover {
            color: #bc13fe;
        }

        .back-home {
            display: inline-block;
            margin-top: 1rem;
            color: #b8c2cc;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .back-home:hover {
            color: #00f3ff;
        }

        .mt-3 {
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .register-container {
                padding: 1rem;
            }

            .register-card {
                padding: 2rem 1.5rem;
            }

            .register-header h1 {
                font-size: 1.5rem;
            }

            .row {
                flex-direction: column;
                gap: 0;
            }

            .col-md-6 {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="register-logo">🎮</div>
                <h1>Create Your <span class="highlight">GameVerse</span> Account</h1>
                <p>Join our gaming community and start your adventure</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-3">
                        <a href="index.php?page=login" class="btn-primary" style="display: inline-block; padding: 0.5rem 1.5rem; width: auto;">Go to Login</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="post" class="register-form" id="registrationForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                                       autofocus>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username <small>(This will be your gamertag)</small></label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               required>
                        <small class="form-text">Only letters, numbers, and underscores allowed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" class="form-control" 
                                   required minlength="8">
                            <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="password-strength-text">Password strength: <span>Very weak</span></small>
                        </div>
                        <small class="form-text">Must be at least 8 characters long</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-input">
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-control" required minlength="8">
                            <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-match">
                            <small>Passwords do not match</small>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
                        <label class="form-check-label" for="agree_terms">
                            I agree to the Terms of Service and Privacy Policy
                        </label>
                    </div>
                    
                    <button type="submit" class="btn-primary">Create Account</button>
                </form>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="index.php?page=login">Sign in</a></p>
                    <a href="index.php?page=home" class="back-home">← Back to Home</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePasswordButtons = document.querySelectorAll('.toggle-password');
            
            togglePasswordButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            });
            
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrength = {
                progressBar: document.querySelector('.progress-bar'),
                text: document.querySelector('.password-strength-text span'),
                levels: {
                    0: { color: '#dc3545', text: 'Very weak' },
                    1: { color: '#ff6b6b', text: 'Weak' },
                    2: { color: '#ffc107', text: 'Moderate' },
                    3: { color: '#4caf50', text: 'Strong' },
                    4: { color: '#28a745', text: 'Very strong' }
                }
            };
            
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                    checkPasswordMatch();
                });
            }
            
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            }
            
            function checkPasswordStrength(password) {
                let strength = 0;
                
                if (password.length >= 8) strength++;
                if (password.match(/[a-z]+/)) strength++;
                if (password.match(/[A-Z]+/)) strength++;
                if (password.match(/[0-9]+/)) strength++;
                if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength++;
                
                strength = Math.min(strength, 4);
                const level = password.length === 0 ? 0 : Math.max(1, strength);
                const width = (level / 4) * 100;
                
                passwordStrength.progressBar.style.width = width + '%';
                passwordStrength.progressBar.style.backgroundColor = passwordStrength.levels[level].color;
                passwordStrength.text.textContent = passwordStrength.levels[level].text;
                passwordStrength.text.style.color = passwordStrength.levels[level].color;
            }
            
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const matchElement = document.querySelector('.password-match');
                
                if (password && confirmPassword) {
                    if (password !== confirmPassword) {
                        matchElement.style.display = 'block';
                        confirmPasswordInput.classList.add('is-invalid');
                    } else {
                        matchElement.style.display = 'none';
                        confirmPasswordInput.classList.remove('is-invalid');
                    }
                } else {
                    matchElement.style.display = 'none';
                    confirmPasswordInput.classList.remove('is-invalid');
                }
            }
            
            const form = document.getElementById('registrationForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    const agreeTerms = document.getElementById('agree_terms').checked;
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        document.querySelector('.password-match').style.display = 'block';
                        document.getElementById('confirm_password').scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return false;
                    }
                    
                    if (!agreeTerms) {
                        e.preventDefault();
                        alert('You must agree to the Terms of Service and Privacy Policy.');
                        return false;
                    }
                    
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.textContent = 'Creating Account...';
                    }
                    
                    return true;
                });
            }
        });
    </script>
</body>
</html>
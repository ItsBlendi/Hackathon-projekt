<?php
// Include necessary files using robust paths
// Database connection is established in index.php via PROJECT_ROOT . 'config/database.php'
// Here we only need the authentication functions
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . 'includes/auth_functions.php';
} else {
    require_once __DIR__ . '/../../includes/auth_functions.php';
}

$page_title = 'Create Account - GameVerse';
$error = '';
$success = '';

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: ../../index.php?page=dashboard');
    exit();
}

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $agree_terms = isset($_POST['agree_terms']);
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!$agree_terms) {
        $error = 'You must agree to the terms and conditions.';
    } else {
        // Attempt to register the user
        $result = registerUser($username, $email, $password, $first_name, $last_name);
        
        if ($result['status'] === 'success') {
            $success = 'Registration successful! A verification email has been sent to your email address. ' .
                      'Please verify your email to activate your account.';
            
            // Clear form
            $_POST = [];
        } else {
            $error = $result['message'];
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card auth-card--compact">
        <div class="auth-header">
            <h2>Create Your <span class="highlight">GameVerse</span> Account</h2>
            <p>Join our gaming community and start your adventure</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <div class="mt-3">
                    <a href="index.php?page=login" class="btn btn-primary">Go to Login</a>
                </div>
            </div>
        <?php else: ?>
            <form action="" method="post" class="auth-form" id="registrationForm">
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
                    <label for="username">Username <small class="text-muted">(This will be your gamertag)</small></label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                           required>
                    <small class="form-text text-muted">Only letters, numbers, and underscores allowed</small>
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
                    <div class="password-strength mt-2">
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <small class="password-strength-text">Password strength: <span>Very weak</span></small>
                    </div>
                    <small class="form-text text-muted">Must be at least 8 characters long</small>
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
                    <div class="password-match mt-1">
                        <small class="text-danger">Passwords do not match</small>
                    </div>
                </div>
                
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
                    <label class="form-check-label" for="agree_terms">
                        I agree to the <a href="#" data-toggle="modal" data-target="#termsModal">Terms of Service</a> 
                        and <a href="#" data-toggle="modal" data-target="#privacyModal">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
                
                <div class="text-center mt-3">
                    <div class="g-recaptcha" data-sitekey="YOUR_RECAPTCHA_SITE_KEY"></div>
                </div>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="index.php?page=login">Sign in</a></p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="auth-illustration auth-illustration--compact">
        <img src="../../assets/images/register-illustration.svg" alt="Gaming Illustration" class="img-fluid">
        <div class="features-list">
            <h4>Why Join GameVerse?</h4>
            <ul>
                <li><i class="fas fa-gamepad"></i> Access to hundreds of games</li>
                <li><i class="fas fa-trophy"></i> Compete on leaderboards</li>
                <li><i class="fas fa-users"></i> Join a house and make friends</li>
                <li><i class="fas fa-award"></i> Earn achievements and badges</li>
                <li><i class="fas fa-gift"></i> Get exclusive rewards</li>
            </ul>
        </div>
    </div>
</div>

<!-- Terms and Privacy Modals -->
<div class="modal fade" id="termsModal" tabindex="-1" role="dialog" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms of Service</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6>1. Acceptance of Terms</h6>
                <p>By accessing or using GameVerse, you agree to be bound by these terms and conditions.</p>
                
                <h6>2. User Accounts</h6>
                <p>You are responsible for maintaining the confidentiality of your account and password.</p>
                
                <h6>3. Code of Conduct</h6>
                <p>You agree not to engage in any activity that may disrupt the service or harm other users.</p>
                
                <h6>4. Content</h6>
                <p>You retain ownership of any content you submit, but grant us a license to use it on our platform.</p>
                
                <h6>5. Termination</h6>
                <p>We reserve the right to terminate or suspend your account for any violation of these terms.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="privacyModal" tabindex="-1" role="dialog" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6>1. Information We Collect</h6>
                <p>We collect information you provide when you create an account, make a purchase, or use our services.</p>
                
                <h6>2. How We Use Your Information</h6>
                <p>We use your information to provide and improve our services, process transactions, and communicate with you.</p>
                
                <h6>3. Data Security</h6>
                <p>We implement security measures to protect your personal information.</p>
                
                <h6>4. Cookies</h6>
                <p>We use cookies to enhance your experience and analyze site traffic.</p>
                
                <h6>5. Third-Party Services</h6>
                <p>We may use third-party services that collect information for analytics and advertising.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add styles for the registration page -->
<style>
/* Registration page layout tweaks to be more compact like login */
.auth-card.auth-card--compact {
    max-width: 520px;
    padding: 2rem;
    margin: 1.5rem;
}

.auth-card.auth-card--compact .auth-header {
    margin-bottom: 1.5rem;
}

.auth-card.auth-card--compact .auth-header h2 {
    font-size: 1.7rem;
}

.auth-card.auth-card--compact .auth-form .form-group {
    margin-bottom: 1.1rem;
}

/* Center labels and helper text in registration form */
.auth-card.auth-card--compact .auth-form label {
    display: block;
    width: 100%;
    text-align: center;
}

.auth-card.auth-card--compact .auth-form .form-text,
.auth-card.auth-card--compact .auth-form small {
    display: block;
    text-align: center;
}

.auth-container {
    gap: 2rem;
}

.auth-illustration.auth-illustration--compact {
    max-width: 420px;
}

/* Additional styles for registration page */
.features-list {
    margin-top: 2rem;
    padding: 1.5rem;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    border-left: 3px solid var(--neon-blue);
}

.features-list h4 {
    color: #fff;
    margin-bottom: 1rem;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
}

.features-list h4::before {
    content: '⭐';
    margin-right: 0.5rem;
}

.features-list ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.features-list li {
    padding: 0.5rem 0;
    color: #b8c2cc;
    display: flex;
    align-items: center;
}

.features-list li i {
    color: var(--neon-blue);
    margin-right: 0.75rem;
    width: 20px;
    text-align: center;
}

.password-strength {
    margin-bottom: 1rem;
}

.progress {
    height: 5px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
    margin-bottom: 0.25rem;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background-color: #dc3545;
    transition: width 0.3s ease, background-color 0.3s ease;
}

.password-strength-text {
    font-size: 0.8rem;
    color: #b8c2cc;
}

.password-strength-text span {
    font-weight: 600;
}

.password-match {
    display: none;
    font-size: 0.8rem;
}

/* Modal styles */
.modal-content {
    background-color: #1a1a2e;
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.close {
    color: #fff;
    text-shadow: none;
    opacity: 0.8;
}

.close:hover {
    color: var(--neon-blue);
    opacity: 1;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .row {
        margin-left: -10px;
        margin-right: -10px;
    }
    
    .col-md-6 {
        padding-left: 10px;
        padding-right: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
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
    
    // Password strength checker
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
        const strengthText = document.querySelector('.password-strength-text');
        
        // Length check
        if (password.length >= 8) strength++;
        // Has lowercase letters
        if (password.match(/[a-z]+/)) strength++;
        // Has uppercase letters
        if (password.match(/[A-Z]+/)) strength++;
        // Has numbers
        if (password.match(/[0-9]+/)) strength++;
        // Has special characters
        if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength++;
        
        // Cap strength at 4
        strength = Math.min(strength, 4);
        
        // Update UI
        const level = password.length === 0 ? 0 : Math.max(1, strength);
        const width = (level / 4) * 100;
        
        passwordStrength.progressBar.style.width = width + '%';
        passwordStrength.progressBar.style.backgroundColor = passwordStrength.levels[level].color;
        passwordStrength.text.textContent = passwordStrength.levels[level].text;
        passwordStrength.text.style.color = passwordStrength.levels[level].color;
        
        // Show/hide strength meter
        if (password.length === 0) {
            strengthText.style.display = 'none';
        } else {
            strengthText.style.display = 'block';
        }
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
    
    // Form validation
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
            
            // Add loading state
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating Account...';
            }
            
            return true;
        });
    }
});
</script>

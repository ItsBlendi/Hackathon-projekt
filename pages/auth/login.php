<?php
// Include necessary files using robust paths
// Database connection is established in index.php via PROJECT_ROOT . 'config/database.php'
// Here we only need the authentication functions
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . 'includes/auth_functions.php';
} else {
    require_once __DIR__ . '/../../includes/auth_functions.php';
}

$page_title = 'Login - GameVerse';
$error = '';

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: ../../index.php?page=dashboard');
    exit();
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username/email and password.';
    } else {
        // Attempt to log in
        $result = loginUser($username, $password);
        
        if ($result['status'] === 'success') {
            // Handle remember me functionality
            if ($remember) {
                $token = generateToken();
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                // Store token in database (you'll need to create a remember_tokens table)
                $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
                $session_id = session_id();
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                $stmt->bind_param("issss", $_SESSION['user_id'], $session_id, $ip_address, $user_agent, $expires);
                $stmt->execute();
                $stmt->close();
                
                // Set cookie
                setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
            }
            
            // Redirect to dashboard or previous page
            $redirect_url = $_SESSION['redirect_url'] ?? 'index.php?page=dashboard';
            unset($_SESSION['redirect_url']);
            
            header('Location: ' . $redirect_url);
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Welcome Back to <span class="highlight">GameVerse</span></h2>
            <p>Login to access your account and continue your gaming journey</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form action="" method="post" class="auth-form">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <div class="d-flex justify-content-between">
                    <label for="password">Password</label>
                    <a href="index.php?page=forgot-password" class="forgot-password">Forgot Password?</a>
                </div>
                <div class="password-input">
                    <input type="password" id="password" name="password" class="form-control" required>
                    <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="index.php?page=register">Sign up</a></p>
        </div>
    </div>
    
    <div class="auth-illustration">
        <img src="../../assets/images/login-illustration.svg" alt="Gaming Illustration" class="img-fluid">
        <div class="featured-games">
            <h4>Featured Games</h4>
            <div class="games-grid">
                <div class="game-card">
                    <img src="../../assets/images/game1.jpg" alt="Game 1">
                    <span>Space Adventure</span>
                </div>
                <div class="game-card">
                    <img src="../../assets/images/game2.jpg" alt="Game 2">
                    <span>Racing Fever</span>
                </div>
                <div class="game-card">
                    <img src="../../assets/images/game3.jpg" alt="Game 3">
                    <span>Puzzle Master</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add some custom styles for the login page -->
<style>
.auth-container {
    display: flex;
    min-height: calc(100vh - 200px);
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background: linear-gradient(135deg, rgba(15, 15, 26, 0.9) 0%, rgba(10, 10, 16, 0.95) 100%);
}

.auth-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    padding: 2.5rem;
    width: 100%;
    max-width: 450px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    z-index: 1;
    position: relative;
    overflow: hidden;
}

.auth-card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(0, 243, 255, 0.1) 0%, rgba(188, 19, 254, 0.1) 100%);
    z-index: -1;
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-header h2 {
    font-size: 1.8rem;
    margin-bottom: 0.5rem;
    color: #fff;
}

.auth-header p {
    color: #b8c2cc;
    margin-bottom: 0;
}

.auth-form .form-group {
    margin-bottom: 1.5rem;
}

.auth-form label {
    display: block;
    margin-bottom: 0.5rem;
    color: #b8c2cc;
    font-weight: 500;
}

.auth-form .form-control {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.05);
    border-radius: 6px;
    color: #fff;
    transition: all 0.3s ease;
}

.auth-form .form-control:focus {
    border-color: #00f3ff;
    box-shadow: 0 0 0 2px rgba(0, 243, 255, 0.2);
    outline: none;
}

.password-input {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #b8c2cc;
    cursor: pointer;
    padding: 5px;
}

.forgot-password {
    font-size: 0.85rem;
    color: #00f3ff;
    text-decoration: none;
    transition: color 0.3s ease;
}

.forgot-password:hover {
    color: #bc13fe;
    text-decoration: underline;
}

.form-check {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
}

.form-check-input {
    margin-right: 0.5rem;
    background-color: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
}

.form-check-input:checked {
    background-color: #00f3ff;
    border-color: #00f3ff;
}

.form-check-label {
    color: #b8c2cc;
    font-size: 0.9rem;
}

.btn-primary {
    width: 100%;
    padding: 0.8rem;
    font-size: 1rem;
    font-weight: 600;
    border: none;
    border-radius: 6px;
    background: linear-gradient(45deg, #00f3ff, #bc13fe);
    color: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 243, 255, 0.3);
}

.auth-footer {
    text-align: center;
    margin-top: 1.5rem;
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
    text-decoration: underline;
}

.auth-illustration {
    display: none;
    max-width: 500px;
    margin-left: 4rem;
}

.auth-illustration img {
    max-width: 100%;
    height: auto;
    margin-bottom: 2rem;
}

.featured-games h4 {
    color: #fff;
    margin-bottom: 1rem;
    font-size: 1.2rem;
}

.games-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.game-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.game-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

.game-card img {
    width: 100%;
    height: 80px;
    object-fit: cover;
}

.game-card span {
    display: block;
    padding: 0.5rem;
    font-size: 0.7rem;
    text-align: center;
    color: #b8c2cc;
}

.alert {
    padding: 0.75rem 1.25rem;
    margin-bottom: 1.5rem;
    border: 1px solid transparent;
    border-radius: 6px;
    font-size: 0.9rem;
}

.alert-danger {
    color: #f8d7da;
    background-color: #842029;
    border-color: #842029;
}

/* Responsive styles */
@media (min-width: 992px) {
    .auth-container {
        padding: 4rem;
    }
    
    .auth-illustration {
        display: block;
    }
}

/* Animation for form elements */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.auth-form .form-group {
    animation: fadeInUp 0.5s ease-out forwards;
    opacity: 0;
}

.auth-form .form-group:nth-child(1) { animation-delay: 0.1s; }
.auth-form .form-group:nth-child(2) { animation-delay: 0.2s; }
.auth-form .form-group:nth-child(3) { animation-delay: 0.3s; }
.auth-form .form-group:nth-child(4) { animation-delay: 0.4s; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.querySelector('.toggle-password');
    const passwordInput = document.querySelector('#password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
    
    // Add floating label effect
    const inputs = document.querySelectorAll('.form-control');
    
    inputs.forEach(input => {
        // Check if input has value on page load
        if (input.value) {
            input.parentElement.classList.add('focused');
        }
        
        // Add focus effect
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        // Add blur effect
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
    });
});
</script>

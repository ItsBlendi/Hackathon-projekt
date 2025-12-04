<?php
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . 'includes/auth_functions.php';
    require_once PROJECT_ROOT . 'config/database.php';
} else {
    require_once __DIR__ . '/../../includes/auth_functions.php';
    require_once __DIR__ . '/../../config/database.php';
}

$page_title = 'Login - GameVerse';
$error = '';

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: ../../index.php?page=dashboard');
    exit();
}

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
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                // Store token in database
                $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) {
                    $session_id = session_id();
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $user_agent = $_SERVER['HTTP_USER_AGENT'];
                    $stmt->bind_param("issss", $_SESSION['user_id'], $session_id, $ip_address, $user_agent, $expires);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Set cookie
                    setcookie('remember_token', $token, time() + (86400 * 30), '/', '', isset($_SERVER['HTTPS']), true);
                }
            }
            
            // Redirect to dashboard or previous page
            $redirect_url = $_SESSION['redirect_url'] ?? '../../index.php?page=dashboard';
            unset($_SESSION['redirect_url']);
            
            header('Location: ' . $redirect_url);
            exit();
        } else {
            $error = $result['message'];
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
            overflow: hidden;
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

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 3rem 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #fff;
        }

        .login-header .highlight {
            color: #00f3ff;
            text-shadow: 0 0 20px rgba(0, 243, 255, 0.4);
        }

        .login-header p {
            color: #b8c2cc;
            font-size: 0.95rem;
        }

        .alert {
            padding: 0.875rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #b8c2cc;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .forgot-password {
            font-size: 0.85rem;
            color: #00f3ff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: #bc13fe;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
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

        .password-input-wrapper {
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

        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            margin-right: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background-color: rgba(255, 255, 255, 0.05);
            cursor: pointer;
            accent-color: #00f3ff;
        }

        .form-check-label {
            color: #b8c2cc;
            font-size: 0.9rem;
            cursor: pointer;
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

        .btn-primary:hover {
            background-color: #00d9e6;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 243, 255, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .auth-footer {
            text-align: center;
            margin-top: 1.75rem;
            padding-top: 1.75rem;
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
            margin-top: 1.5rem;
            color: #b8c2cc;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .back-home:hover {
            color: #00f3ff;
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 1rem;
            }

            .login-card {
                padding: 2rem 1.5rem;
            }

            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">🎮</div>
                <h1>Welcome to <span class="highlight">GameVerse</span></h1>
                <p>Login to continue your gaming journey</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="post" class="login-form">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                        required
                        autocomplete="username"
                    >
                </div>
                
                <div class="form-group">
                    <div class="form-group-header">
                        <label for="password">Password</label>
                        <a href="index.php?page=forgot-password" class="forgot-password">Forgot Password?</a>
                    </div>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me for 30 days</label>
                </div>
                
                <button type="submit" class="btn-primary">Login</button>
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="index.php?page=register">Sign up</a></p>
                <a href="index.php?page=home" class="back-home">← Back to Home</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('.toggle-password');
            const passwordInput = document.querySelector('#password');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>

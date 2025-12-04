<?php
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . 'includes/auth_functions.php';
    require_once PROJECT_ROOT . 'config/database.php';
} else {
    require_once __DIR__ . '/../../includes/auth_functions.php';
    require_once __DIR__ . '/../../config/database.php';
}

$page_title = 'Reset Password - GameVerse';
$error = '';
$success = '';
$token_valid = false;
$user_id = null;

// Check if token is provided
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Invalid reset link.';
} else {
    // Verify token
    $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $reset = $result->fetch_assoc();
            
            // Check if token is expired
            if (strtotime($reset['expires_at']) > time()) {
                $token_valid = true;
                $user_id = $reset['user_id'];
            } else {
                $error = 'This reset link has expired. Please request a new one.';
            }
        } else {
            $error = 'Invalid or expired reset link.';
        }
        $stmt->close();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($password) || empty($confirm_password)) {
        $error = 'Please enter and confirm your new password.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Hash password and update in database
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                // Delete used token
                $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                if ($delete_stmt) {
                    $delete_stmt->bind_param("s", $token);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                }
                
                $success = 'Your password has been reset successfully!';
                $token_valid = false;
            } else {
                $error = 'An error occurred. Please try again.';
            }
            $update_stmt->close();
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

        .reset-container {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .reset-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 3rem 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .reset-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .reset-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #00f3ff;
            text-shadow: 0 0 20px rgba(0, 243, 255, 0.4);
        }

        .reset-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #fff;
        }

        .reset-header p {
            color: #b8c2cc;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .alert {
            padding: 0.875rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .alert-error {
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
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #b8c2cc;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .password-input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 2.75rem 0.875rem 1rem;
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

        .password-requirements {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #9ca3af;
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
            margin-bottom: 1rem;
        }

        .btn-primary:hover {
            background-color: #00d9e6;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 243, 255, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .back-to-login {
            display: block;
            text-align: center;
            color: #b8c2cc;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .back-to-login:hover {
            color: #00f3ff;
        }

        .success-message {
            text-align: center;
        }

        .success-icon {
            font-size: 4rem;
            color: #22c55e;
            margin-bottom: 1.5rem;
            animation: checkmark 0.5s ease-in-out;
        }

        @keyframes checkmark {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }

        .success-message h2 {
            color: #fff;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .success-message p {
            color: #b8c2cc;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .reset-container {
                padding: 1rem;
            }

            .reset-card {
                padding: 2rem 1.5rem;
            }

            .reset-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <?php if ($success): ?>
                <div class="success-message">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2>Password Reset Successful</h2>
                    <p><?php echo htmlspecialchars($success); ?></p>
                    <a href="index.php?page=login" class="btn-primary" style="display: inline-block; text-decoration: none;">Login with New Password</a>
                </div>
            <?php elseif (!$token_valid): ?>
                <div class="reset-header">
                    <div class="reset-icon" style="color: #ef4444;">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h1>Invalid Reset Link</h1>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
                <a href="index.php?page=forgot-password" class="btn-primary" style="display: inline-block; text-decoration: none; text-align: center;">Request New Reset Link</a>
                <a href="index.php?page=login" class="back-to-login">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            <?php else: ?>
                <div class="reset-header">
                    <div class="reset-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h1>Reset Your Password</h1>
                    <p>Enter your new password below.</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <div class="password-input-wrapper">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                required
                                minlength="8"
                                autocomplete="new-password"
                            >
                            <button type="button" class="toggle-password" data-target="password" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-requirements">Must be at least 8 characters long</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-input-wrapper">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-control" 
                                required
                                minlength="8"
                                autocomplete="new-password"
                            >
                            <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary">Reset Password</button>
                    
                    <a href="index.php?page=login" class="back-to-login">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButtons = document.querySelectorAll('.toggle-password');
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const passwordInput = document.getElementById(targetId);
                    
                    if (passwordInput) {
                        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordInput.setAttribute('type', type);
                        
                        const icon = this.querySelector('i');
                        icon.classList.toggle('fa-eye');
                        icon.classList.toggle('fa-eye-slash');
                    }
                });
            });
        });
    </script>
</body>
</html>

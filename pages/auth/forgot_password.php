<?php
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . 'includes/auth_functions.php';
    require_once PROJECT_ROOT . 'config/database.php';
} else {
    require_once __DIR__ . '/../../includes/auth_functions.php';
    require_once __DIR__ . '/../../config/database.php';
}

$page_title = 'Forgot Password - GameVerse';
$error = '';
$success = '';

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: ../../index.php?page=dashboard');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    // Validate input
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email exists in database
        $stmt = $conn->prepare("SELECT user_id, username FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store token in database
                $delete_old = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
                if ($delete_old) {
                    $delete_old->bind_param("i", $user['user_id']);
                    $delete_old->execute();
                    $delete_old->close();
                }
                
                $insert_token = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())");
                if ($insert_token) {
                    $insert_token->bind_param("iss", $user['user_id'], $token, $expires);
                    $insert_token->execute();
                    $insert_token->close();
                    
                    // Create reset link
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
                    
                    // In a real application, send email here
                    // For now, we'll just show success message
                    // mail($email, "Password Reset - GameVerse", "Click this link to reset your password: " . $reset_link);
                    
                    $success = 'Password reset instructions have been sent to your email address.';
                }
            } else {
                // Don't reveal if email exists or not for security
                $success = 'If an account with that email exists, password reset instructions have been sent.';
            }
            $stmt->close();
        } else {
            $error = 'An error occurred. Please try again later.';
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

        .forgot-container {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .forgot-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 3rem 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .forgot-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .forgot-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #00f3ff;
            text-shadow: 0 0 20px rgba(0, 243, 255, 0.4);
        }

        .forgot-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #fff;
        }

        .forgot-header p {
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
            .forgot-container {
                padding: 1rem;
            }

            .forgot-card {
                padding: 2rem 1.5rem;
            }

            .forgot-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-card">
            <?php if ($success): ?>
                <div class="success-message">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2>Check Your Email</h2>
                    <p><?php echo htmlspecialchars($success); ?></p>
                    <p style="font-size: 0.85rem; color: #9ca3af;">The link will expire in 1 hour for security reasons.</p>
                    <a href="index.php?page=login" class="btn-primary" style="display: inline-block; text-decoration: none; margin-top: 1rem;">Return to Login</a>
                </div>
            <?php else: ?>
                <div class="forgot-header">
                    <div class="forgot-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <h1>Forgot Password?</h1>
                    <p>Enter your email address and we'll send you instructions to reset your password.</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-control" 
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                            required
                            autocomplete="email"
                            placeholder="your@email.com"
                        >
                    </div>
                    
                    <button type="submit" class="btn-primary">Send Reset Link</button>
                    
                    <a href="index.php?page=login" class="back-to-login">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

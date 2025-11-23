<?php
require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

$page_title = 'Password Reset - GameVerse';
$message = '';
$error = '';
$show_reset_form = false;

// Check if this is a password reset request or token verification
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        // Handle forgot password form submission
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

        if (empty($email)) {
            $error = 'Please enter your email address.';
        } else {
            $result = sendPasswordResetEmail($email);
            if ($result['status'] === 'success') {
                $message = $result['message'];
                if (isset($result['reset_link'])) {
                    $message .= '<div class="alert alert-info mt-3">Development only - Reset link: <a href="' . $result['reset_link'] . '">' . $result['reset_link'] . '</a></div>';
                }
            } else {
                $error = $result['message'];
            }
        }
    } elseif (isset($_POST['password'])) {
        // Handle password reset form submission
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $token = $_POST['token'] ?? '';

        if (empty($password) || empty($confirm_password)) {
            $error = 'Please fill in all fields.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            $result = resetPassword($token, $password);
            if ($result['status'] === 'success') {
                $message = $result['message'];
                $show_reset_form = false;
            } else {
                $error = $result['message'];
            }
        }
    }
} elseif (!empty($token)) {
    // Verify token and show reset form
    $stmt = $conn->prepare('SELECT user_id FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error = 'Invalid or expired reset token. Please request a new password reset link.';
    } else {
        $show_reset_form = true;
    }
    $stmt->close();
}
?>

<div class="auth-container">
    <div class="auth-card">
        <?php if ($show_reset_form): ?>
            <!-- Reset Password Form -->
            <div class="auth-header">
                <h2>Reset Your <span class="highlight">Password</span></h2>
                <p>Please enter your new password below.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                    <div class="mt-3">
                        <a href="index.php?page=login" class="btn btn-primary">Back to Login</a>
                    </div>
                </div>
            <?php else: ?>
                <form action="" method="post" class="auth-form">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" class="form-control" required
                               minlength="8" placeholder="Enter your new password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required
                               minlength="8" placeholder="Confirm your new password">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                    
                    <div class="text-center mt-3">
                        <a href="index.php?page=login">Back to Login</a>
                    </div>
                </form>
            <?php endif; ?>

        <?php else: ?>
            <!-- Forgot Password Form -->
            <div class="auth-header">
                <h2>Forgot Your <span class="highlight">Password</span>?</h2>
                <p>Enter your email address and we'll send you a link to reset your password.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                    <div class="mt-3">
                        <a href="index.php?page=login" class="btn btn-primary">Back to Login</a>
                    </div>
                </div>
            <?php else: ?>
                <form action="" method="post" class="auth-form">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               placeholder="Enter your email address">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
                    
                    <div class="text-center mt-3">
                        <a href="index.php?page=login">Back to Login</a>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

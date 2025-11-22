<?php
require_once '../../config/database.php';
require_once '../../includes/auth_functions.php';

$page_title = 'Reset Password - GameVerse';
$message = '';
$error = '';
$show_form = false;

$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

if (empty($token)) {
    $error = 'Invalid or missing reset token.';
} else {
    $stmt = $conn->prepare('SELECT user_id FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error = 'Invalid or expired reset token. Please request a new password reset link.';
    } else {
        $show_form = true;
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $show_form) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

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
            $show_form = false;
        } else {
            $error = $result['message'];
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Reset Your <span class="highlight">Password</span></h2>
            <p>Create a new password for your account.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?>
                <div class="mt-3">
                    <a href="index.php?page=login" class="btn btn-primary">Go to Login</a>
                </div>
            </div>
        <?php elseif ($show_form): ?>
            <form action="" method="post" class="auth-form" id="resetPasswordForm">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" class="form-control" required minlength="8">
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-input">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
            </form>
        <?php endif; ?>

        <div class="auth-footer">
            <p>Remember your password? <a href="index.php?page=login">Sign in</a></p>
        </div>
    </div>
</div>

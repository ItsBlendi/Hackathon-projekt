<?php
require_once '../../config/database.php';
require_once '../../includes/auth_functions.php';

$page_title = 'Forgot Password - GameVerse';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Forgot Your <span class="highlight">Password</span>?</h2>
            <p>Enter your email address and we'll send you a link to reset your password.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?>
                <div class="mt-3">
                    <a href="index.php?page=login" class="btn btn-primary">Back to Login</a>
                </div>
            </div>
        <?php else: ?>
            <form action="" method="post" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
            </form>

            <div class="auth-footer">
                <p>Remember your password? <a href="index.php?page=login">Sign in</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

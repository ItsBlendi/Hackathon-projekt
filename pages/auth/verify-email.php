<?php
require_once '../../config/database.php';
require_once '../../includes/auth_functions.php';

$page_title = 'Verify Email - GameVerse';
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

if (empty($token)) {
    $error = 'Invalid verification token.';
} else {
    $result = verifyEmail($token);
    if ($result['status'] === 'success') {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}
?>

<div class="auth-container">
    <div class="auth-card text-center">
        <div class="auth-header">
            <h2>Email <span class="highlight">Verification</span></h2>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-success">
                <h4>Email Verified Successfully!</h4>
                <p><?php echo htmlspecialchars($message); ?></p>
                <div class="mt-4">
                    <a href="index.php?page=dashboard" class="btn btn-primary">Go to Dashboard</a>
                </div>
            </div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-danger">
                <h4>Verification Failed</h4>
                <p><?php echo htmlspecialchars($error); ?></p>
                <div class="mt-4">
                    <a href="index.php?page=login" class="btn btn-primary">Back to Login</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

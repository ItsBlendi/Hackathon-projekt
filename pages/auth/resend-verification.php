<?php
require_once '../../config/database.php';
require_once '../../includes/auth_functions.php';

$page_title = 'Resend Verification - GameVerse';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $stmt = $conn->prepare('SELECT user_id, username, email_verified FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = 'No account found with that email address.';
        } else {
            $user = $result->fetch_assoc();

            if ($user['email_verified']) {
                $message = 'This email address has already been verified. You can now login.';
            } else {
                $verification_token = bin2hex(random_bytes(32));
                $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $update = $conn->prepare('UPDATE users SET verification_token = ?, verification_token_expires = ? WHERE user_id = ?');
                $update->bind_param('ssi', $verification_token, $verification_expires, $user['user_id']);

                if ($update->execute()) {
                    sendVerificationEmail($email, $user['username'], $verification_token);
                    $message = 'A new verification email has been sent to ' . htmlspecialchars($email) . '.';
                } else {
                    $error = 'Failed to generate new verification token. Please try again.';
                }

                $update->close();
            }
        }

        $stmt->close();
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Resend <span class="highlight">Verification Email</span></h2>
            <p>Enter your email address to receive a new verification link.</p>
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
                <button type="submit" class="btn btn-primary btn-block">Resend Verification Email</button>
            </form>

            <div class="auth-footer">
                <p>Remembered your password? <a href="index.php?page=login">Sign in</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

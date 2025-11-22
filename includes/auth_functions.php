<?php
/**
 * Authentication Functions for GameVerse Portal
 */

/**
 * Register a new user
 * 
 * @param string $username Username
 * @param string $email User email
 * @param string $password User password
 * @param string $first_name User first name
 * @param string $last_name User last name
 * @return array Result with status and message
 */
function registerUser($username, $email, $password, $first_name = '', $last_name = '') {
    global $conn;
    
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        return ['status' => 'error', 'message' => 'All fields are required.'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'error', 'message' => 'Invalid email format.'];
    }
    
    if (strlen($password) < 8) {
        return ['status' => 'error', 'message' => 'Password must be at least 8 characters long.'];
    }
    
    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        return ['status' => 'error', 'message' => 'Username or email already exists.'];
    }
    $stmt->close();
    
    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate verification token
    $verification_token = bin2hex(random_bytes(32));
    $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Assign user to a house (round-robin for balance)
    $house_id = assignUserToHouse();
    
    // Insert the new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, house_id, verification_token, verification_token_expires) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssiss", $username, $email, $password_hash, $first_name, $last_name, $house_id, $verification_token, $verification_expires);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();
        
        // Send verification email (to be implemented)
        // sendVerificationEmail($email, $username, $verification_token);
        
        return [
            'status' => 'success', 
            'message' => 'Registration successful! Please check your email to verify your account.',
            'user_id' => $user_id,
            'house_id' => $house_id
        ];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['status' => 'error', 'message' => 'Registration failed. Please try again. Error: ' . $error];
    }
}

/**
 * Assign a user to a house (round-robin for balance)
 * 
 * @return int House ID
 */
function assignUserToHouse() {
    global $conn;
    
    // Get the count of users in each house
    $query = "SELECT house_id, COUNT(*) as user_count 
              FROM users 
              WHERE house_id IS NOT NULL 
              GROUP BY house_id 
              ORDER BY user_count ASC";
    
    $result = $conn->query($query);
    
    if ($result->num_rows < 4) {
        // If any house is empty, assign to the first available
        $houses = [1, 2, 3, 4];
        $assigned_houses = [];
        
        while ($row = $result->fetch_assoc()) {
            $assigned_houses[] = $row['house_id'];
        }
        
        $available_houses = array_diff($houses, $assigned_houses);
        return !empty($available_houses) ? reset($available_houses) : rand(1, 4);
    } else {
        // If all houses have users, assign to the one with the fewest members
        $row = $result->fetch_assoc();
        return $row['house_id'];
    }
}

/**
 * Authenticate a user
 * 
 * @param string $username Username or email
 * @param string $password Password
 * @return array Result with status, message, and user data if successful
 */
function loginUser($username, $password) {
    global $conn;
    
    if (empty($username) || empty($password)) {
        return ['status' => 'error', 'message' => 'Username/Email and password are required.'];
    }
    
    // Check if login is via email or username
    $field = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    
    $stmt = $conn->prepare("SELECT user_id, username, email, password_hash, is_active, email_verified, is_admin, house_id FROM users WHERE $field = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['status' => 'error', 'message' => 'Invalid username/email or password.'];
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        $stmt->close();
        return ['status' => 'error', 'message' => 'Invalid username/email or password.'];
    }
    
    // Check if account is active
    if (!$user['is_active']) {
        $stmt->close();
        return ['status' => 'error', 'message' => 'Your account has been deactivated. Please contact support.'];
    }

    // Email verification check disabled for now so users can log in without verifying email
    
    // Update last login time
    $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $update->bind_param("i", $user['user_id']);
    $update->execute();
    $update->close();
    $stmt->close();
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['is_admin'] = $user['is_admin'];
    $_SESSION['house_id'] = $user['house_id'];
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    return [
        'status' => 'success', 
        'message' => 'Login successful!',
        'user' => [
            'id' => $user['user_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'is_admin' => $user['is_admin']
        ]
    ];
}

/**
 * Log out the current user
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    return ['status' => 'success', 'message' => 'You have been logged out.'];
}

/**
 * Check if a user is logged in
 * 
 * @return bool|array False if not logged in, user data if logged in
 */
function isLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'is_admin' => $_SESSION['is_admin'] ?? false,
            'house_id' => $_SESSION['house_id'] ?? null
        ];
    }
    return false;
}

/**
 * Require user to be logged in
 * Redirects to login page if not logged in
 */
function requireLogin() {
    $user = isLoggedIn();
    if (!$user) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: index.php?page=login');
        exit();
    }
    return $user;
}

/**
 * Require admin privileges
 * Redirects to home page if not an admin
 */
function requireAdmin() {
    $user = requireLogin();
    if (!$user['is_admin']) {
        header('Location: index.php');
        exit();
    }
    return $user;
}

/**
 * Generate a secure token
 * 
 * @param int $length Token length in bytes
 * @return string Hex encoded token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Send password reset email
 * 
 * @param string $email User email
 * @return array Result with status and message
 */
function sendPasswordResetEmail($email) {
    global $conn;
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id, username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['status' => 'success', 'message' => 'If an account with that email exists, a password reset link has been sent.'];
    }
    
    $user = $result->fetch_assoc();
    $token = generateToken();
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Delete any existing reset tokens for this user
    $delete = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
    $delete->bind_param("i", $user['user_id']);
    $delete->execute();
    $delete->close();
    
    // Insert new token
    $insert = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $insert->bind_param("iss", $user['user_id'], $token, $expires);
    
    if ($insert->execute()) {
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?page=reset-password&token=" . $token;
        
        // In a real application, you would send an email here
        // For now, we'll just return the reset link
        return [
            'status' => 'success', 
            'message' => 'Password reset link generated. In a real app, this would be sent via email.',
            'reset_link' => $reset_link // For development only
        ];
    } else {
        return ['status' => 'error', 'message' => 'Failed to generate reset token. Please try again.'];
    }
}

/**
 * Reset user password
 * 
 * @param string $token Reset token
 * @param string $new_password New password
 * @return array Result with status and message
 */
function resetPassword($token, $new_password) {
    global $conn;
    
    if (strlen($new_password) < 8) {
        return ['status' => 'error', 'message' => 'Password must be at least 8 characters long.'];
    }
    
    // Check if token exists and is not expired
    $stmt = $conn->prepare("SELECT user_id FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['status' => 'error', 'message' => 'Invalid or expired reset token.'];
    }
    
    $token_data = $result->fetch_assoc();
    $user_id = $token_data['user_id'];
    $stmt->close();
    
    // Hash the new password
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update user's password
    $update = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    $update->bind_param("si", $password_hash, $user_id);
    
    if ($update->execute()) {
        // Mark token as used
        $mark_used = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
        $mark_used->bind_param("s", $token);
        $mark_used->execute();
        $mark_used->close();
        
        $update->close();
        return ['status' => 'success', 'message' => 'Your password has been reset successfully.'];
    } else {
        $update->close();
        return ['status' => 'error', 'message' => 'Failed to reset password. Please try again.'];
    }
}

/**
 * Verify user email
 * 
 * @param string $token Verification token
 * @return array Result with status and message
 */
function verifyEmail($token) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE verification_token = ? AND verification_token_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['status' => 'error', 'message' => 'Invalid or expired verification token.'];
    }
    
    $user = $result->fetch_assoc();
    
    // Mark email as verified
    $update = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, verification_token_expires = NULL WHERE user_id = ?");
    $update->bind_param("i", $user['user_id']);
    
    if ($update->execute()) {
        $update->close();
        $stmt->close();
        
        // Log the user in
        $user_data = $conn->query("SELECT user_id, username, email, is_admin, house_id FROM users WHERE user_id = " . $user['user_id'])->fetch_assoc();
        
        $_SESSION['user_id'] = $user_data['user_id'];
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['email'] = $user_data['email'];
        $_SESSION['is_admin'] = $user_data['is_admin'];
        $_SESSION['house_id'] = $user_data['house_id'];
        
        return ['status' => 'success', 'message' => 'Your email has been verified successfully!'];
    } else {
        $update->close();
        $stmt->close();
        return ['status' => 'error', 'message' => 'Failed to verify email. Please try again.'];
    }
}

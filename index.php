<?php
// Define project root path
define('PROJECT_ROOT', __DIR__ . DIRECTORY_SEPARATOR);

// Start the session
session_start();

// Include configuration
require_once PROJECT_ROOT . 'config/database.php';

// Set default page to home
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Define valid pages
$valid_pages = [
    'home' => PROJECT_ROOT . 'pages/home.php',
    'about' => PROJECT_ROOT . 'pages/about.php',
    'login' => PROJECT_ROOT . 'pages/auth/login.php',
    'register' => PROJECT_ROOT . 'pages/auth/register.php',
    'dashboard' => PROJECT_ROOT . 'pages/user/dashboard.php',
    'houses' => PROJECT_ROOT . 'pages/houses/houses.php',
    'games' => PROJECT_ROOT . 'pages/games/games.php',
    'play' => PROJECT_ROOT . 'pages/games/play.php',
    'leaderboard' => PROJECT_ROOT . 'pages/leaderboard.php',
    'profile' => PROJECT_ROOT . 'pages/user/profile.php'
];

// Get the requested page or default to home
$page_file = isset($valid_pages[$page]) ? $valid_pages[$page] : PROJECT_ROOT . 'pages/404.php';

// Include header
include PROJECT_ROOT . 'templates/header.php';

// Include the requested page
if (file_exists($page_file)) {
    include $page_file;
} else {
    include PROJECT_ROOT . 'pages/404.php';
}

// Include footer
include PROJECT_ROOT . 'templates/footer.php';

// Close database connection
$conn->close();
?>

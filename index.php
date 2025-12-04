<?php
session_start();

// Get the requested page
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Define the page routes and their corresponding files
$routes = [
    'home' => 'pages/home.php',
    'login' => 'pages/auth/login.php',
    'register' => 'pages/auth/register.php',
    'logout' => 'pages/auth/logout.php',
    'dashboard' => 'pages/dashboard.php',
    'profile' => 'pages/profile.php',
    'leaderboard' => 'pages/leaderboard.php',
    'houses' => 'pages/houses/houses.php',
    'games' => 'pages/games/games.php',
    'play' => 'pages/games/play.php',
];

// Check if the requested page exists in routes
if (array_key_exists($page, $routes)) {
    $file_path = $routes[$page];
    
    // Check if the file actually exists
    if (file_exists($file_path)) {
        include $file_path;
    } else {
        // File not found error
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Page Not Found</title>
        </head>
        <body>
            <h1>Page Not Found</h1>
            <p>The file <strong>$file_path</strong> could not be found.</p>
            <a href='index.php?page=home'>Go to Home</a>
        </body>
        </html>";
    }
} else {
    // Route not found, redirect to home
    header('Location: index.php?page=home');
    exit();
}
?>

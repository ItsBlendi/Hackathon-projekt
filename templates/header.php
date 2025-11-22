<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameVerse Portal - Play. Compete. Level Up.</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <span class="logo-text">Game<span class="highlight">Verse</span></span>
                </a>
            </div>
            
            <div class="menu-toggle" id="mobile-menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
            
            <ul class="nav-menu">
                <li><a href="index.php?page=home" class="nav-link">Home</a></li>
                <li><a href="index.php?page=about" class="nav-link">About</a></li>
                <li><a href="index.php?page=houses" class="nav-link">Houses</a></li>
                <li><a href="index.php?page=games" class="nav-link">Games</a></li>
                <li><a href="index.php?page=leaderboard" class="nav-link">Leaderboards</a></li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> My Account
                        </a>
                        <div class="dropdown-menu" aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="index.php?page=dashboard">Dashboard</a>
                            <a class="dropdown-item" href="index.php?page=profile">Profile</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="includes/logout.php">Logout</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="index.php?page=login" class="nav-link">Login</a></li>
                    <li><a href="index.php?page=register" class="btn btn-primary">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="main-content">

<?php
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . 'includes/auth_functions.php';
} else {
    require_once __DIR__ . '/../../includes/auth_functions.php';
}

$user = requireLogin();
$activeNav = 'games';

// Get user's house info
$houseName = 'Unknown';
$houseId = null;
if (isset($_SESSION['house_id'])) {
    $houseId = $_SESSION['house_id'];
    $houseStmt = $GLOBALS['conn']->prepare("SELECT name FROM houses WHERE house_id = ?");
    $houseStmt->bind_param("i", $houseId);
    $houseStmt->execute();
    $houseResult = $houseStmt->get_result();
    if ($houseRow = $houseResult->fetch_assoc()) {
        $houseName = $houseRow['name'];
    }
    $houseStmt->close();
}

// Define all games
$allGames = [
    'flappy-bird' => [
        'title' => 'Flappy Bird',
        'tagline' => 'Navigate the bird through the pipes!',
        'difficulty' => 'Medium',
        'icon' => '🐦',
        'recommended_for' => ['All Players']
    ],
    'reaction-rush' => [
        'title' => 'Reaction Rush',
        'tagline' => 'Test your reflexes! Click when the screen changes color.',
        'difficulty' => 'Easy',
        'icon' => '⚡',
        'recommended_for' => ['Speedsters', 'Shadows']
    ],
    'number-ninja' => [
        'title' => 'Number Ninja',
        'tagline' => 'Solve math problems under pressure!',
        'difficulty' => 'Medium',
        'icon' => '🔢',
        'recommended_for' => ['Engineers', 'Hipsters']
    ],
    'memory-grid' => [
        'title' => 'Memory Grid',
        'tagline' => 'Match pairs in this memory challenge!',
        'difficulty' => 'Medium',
        'icon' => '🧠',
        'recommended_for' => ['Hipsters', 'Shadows']
    ],
    'dodge-squares' => [
        'title' => 'Dodge Squares',
        'tagline' => 'Avoid the red tiles and survive!',
        'difficulty' => 'Hard',
        'icon' => '🎮',
        'recommended_for' => ['Speedsters', 'Engineers']
    ]
];

// Define house-specific game recommendations
$houseGamesMap = [
    1 => ['flappy-bird', 'number-ninja', 'memory-grid'],      // Hipsters
    2 => ['flappy-bird', 'reaction-rush', 'dodge-squares'],   // Speedsters
    3 => ['flappy-bird', 'number-ninja', 'dodge-squares'],    // Engineers
    4 => ['flappy-bird', 'reaction-rush', 'memory-grid']      // Shadows
];

// Get featured games based on house
$featuredGames = [];
if ($houseId && isset($houseGamesMap[$houseId])) {
    $slugs = $houseGamesMap[$houseId];
    foreach ($slugs as $slug) {
        if (isset($allGames[$slug])) {
            $featuredGames[$slug] = $allGames[$slug];
        }
    }
} else {
    // Fallback: first 2 games as featured
    $featuredGames = array_slice($allGames, 0, 2, true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Library - GameVerse</title>
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
        }

        /* Navbar Styles */
        .main-navbar {
            background: rgba(10, 10, 26, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 243, 255, 0.2);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #00f3ff;
            text-decoration: none;
            text-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
        }

        .navbar-links {
            display: flex;
            gap: 2rem;
            align-items: center;
            list-style: none;
            flex-wrap: wrap;
        }

        .navbar-links a {
            color: #b8c2cc;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .navbar-links a:hover {
            color: #00f3ff;
        }

        .navbar-links a.active {
            color: #00f3ff;
        }

        .logout-btn {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
        }

        /* Games Container */
        .games-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .games-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .games-header h1 {
            color: #fff;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, #00f3ff, #bc13fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .games-header .tagline {
            color: #b8c2cc;
            font-size: 1.1rem;
        }

        /* Featured Games Section */
        .featured-section {
            margin-bottom: 3rem;
            padding: 2rem;
            background: rgba(0, 243, 255, 0.05);
            border-radius: 16px;
            border: 1px solid rgba(0, 243, 255, 0.2);
        }

        .featured-section h2 {
            color: #00f3ff;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        /* All Games Section */
        .all-games-section h2 {
            color: #fff;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        /* Games Grid */
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        /* Game Card */
        .game-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 2rem;
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border-color: rgba(0, 243, 255, 0.3);
        }

        .game-card.featured {
            border: 2px solid rgba(0, 243, 255, 0.4);
            box-shadow: 0 0 20px rgba(0, 243, 255, 0.2);
        }

        .featured-badge {
            position: absolute;
            top: -12px;
            right: 20px;
            background: linear-gradient(135deg, #00f3ff, #bc13fe);
            color: #fff;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 12px rgba(0, 243, 255, 0.4);
        }

        .game-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .game-card h3 {
            color: #fff;
            margin-bottom: 0.5rem;
            font-size: 1.4rem;
            text-align: center;
        }

        .game-tagline {
            color: #b8c2cc;
            margin-bottom: 1.5rem;
            flex-grow: 1;
            font-size: 0.95rem;
            line-height: 1.5;
            text-align: center;
        }

        .game-meta {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .difficulty {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .difficulty-easy {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .difficulty-medium {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .difficulty-hard {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .recommended {
            color: #00f3ff;
            font-size: 0.85rem;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.95rem;
        }

        .btn-primary {
            background-color: #00f3ff;
            color: #0a0a1a;
        }

        .btn-primary:hover {
            background-color: #00d9e6;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 243, 255, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .navbar-container {
                padding: 0 1rem;
            }

            .games-container {
                padding: 1.5rem 1rem;
            }

            .games-header h1 {
                font-size: 2rem;
            }

            .games-grid {
                grid-template-columns: 1fr;
            }

            .featured-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="main-navbar">
        <div class="navbar-container">
            <a href="index.php?page=dashboard" class="navbar-brand">🎮 GameVerse</a>
            <ul class="navbar-links">
                <li><a href="index.php?page=dashboard" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">Dashboard</a></li>
                <li><a href="index.php?page=games" class="<?= $activeNav === 'games' ? 'active' : '' ?>">Games</a></li>
                <li><a href="index.php?page=leaderboard" class="<?= $activeNav === 'leaderboard' ? 'active' : '' ?>">Leaderboard</a></li>
                <li><a href="index.php?page=profile" class="<?= $activeNav === 'profile' ? 'active' : '' ?>">My Account</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="games-container">
        <div class="games-header">
            <h1>Game Library</h1>
            <p class="tagline">Welcome, <?= htmlspecialchars($user['username']) ?> of House <?= htmlspecialchars($houseName) ?>!</p>
        </div>

        <?php if (!empty($featuredGames)): ?>
        <div class="featured-section">
            <h2>Recommended for You</h2>
            <div class="games-grid">
                <?php foreach ($featuredGames as $slug => $game): ?>
                    <div class="game-card featured">
                        <div class="featured-badge">Featured</div>
                        <div class="game-icon"><?= $game['icon'] ?></div>
                        <h3><?= htmlspecialchars($game['title']) ?></h3>
                        <p class="game-tagline"><?= htmlspecialchars($game['tagline']) ?></p>
                        
                        <div class="game-meta">
                            <span class="difficulty difficulty-<?= strtolower($game['difficulty']) ?>">
                                <?= htmlspecialchars($game['difficulty']) ?>
                            </span>
                            <span class="recommended">
                                Best for: <?= implode(', ', $game['recommended_for']) ?>
                            </span>
                        </div>
                        
                        <a href="index.php?page=play&game=<?= urlencode($slug) ?>" class="btn btn-primary">
                            Play Now
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="all-games-section">
            <h2>All Games</h2>
            <div class="games-grid">
                <?php foreach ($allGames as $slug => $game): ?>
                    <div class="game-card">
                        <div class="game-icon"><?= $game['icon'] ?></div>
                        <h3><?= htmlspecialchars($game['title']) ?></h3>
                        <p class="game-tagline"><?= htmlspecialchars($game['tagline']) ?></p>
                        
                        <div class="game-meta">
                            <span class="difficulty difficulty-<?= strtolower($game['difficulty']) ?>">
                                <?= htmlspecialchars($game['difficulty']) ?>
                            </span>
                            <span class="recommended">
                                Best for: <?= implode(', ', $game['recommended_for']) ?>
                            </span>
                        </div>
                        
                        <a href="index.php?page=play&game=<?= urlencode($slug) ?>" class="btn btn-primary">
                            Play Now
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>

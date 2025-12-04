<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config/database.php';

$user = requireLogin();

$pageTitle = 'My Profile';
$activeNav = 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_house'])) {
    // Get random house
    $housesQuery = "SELECT house_id FROM houses ORDER BY RAND() LIMIT 1";
    $housesResult = $conn->query($housesQuery);
    
    if ($housesResult && $housesResult->num_rows > 0 && $randomHouse = $housesResult->fetch_assoc()) {
        $updateStmt = $conn->prepare("UPDATE users SET house_id = ? WHERE user_id = ?");
        $updateStmt->bind_param('ii', $randomHouse['house_id'], $user['user_id']);
        
        if ($updateStmt->execute()) {
            $updateStmt->close();
            // Reload user data to get the new house_id
            $user['house_id'] = $randomHouse['house_id'];
            // Redirect to refresh and show new house
            header('Location: index.php?page=profile&assigned=1');
            exit();
        }
        $updateStmt->close();
    }
}

// Get user's house info
$houseName = 'Unassigned';
$houseColor = '#666666';
$houseDescription = '';
$hasHouse = false;

if (!empty($user['house_id'])) {
    $stmt = $conn->prepare("SELECT name, color, description FROM houses WHERE house_id = ? LIMIT 1");
    $stmt->bind_param('i', $user['house_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $houseName = $row['name'];
        $houseColor = $row['color'];
        $houseDescription = $row['description'];
        $hasHouse = true;
    }
    $stmt->close();
}


// Get user rank
$rankQuery = "SELECT COUNT(*) + 1 as rank FROM users WHERE xp > ?";
$rankStmt = $conn->prepare($rankQuery);
$rankStmt->bind_param('i', $user['xp']);
$rankStmt->execute();
$userRank = $rankStmt->get_result()->fetch_assoc()['rank'];
$rankStmt->close();


// Get user's game statistics
$gamesQuery = "
    SELECT 
        g.title,
        g.game_id,
        COUNT(s.score_id) as plays,
        MAX(s.score) as highest_score,
        AVG(s.score) as avg_score
    FROM scores s
    JOIN games g ON s.game_id = g.game_id
    WHERE s.user_id = ?
    GROUP BY g.game_id
    ORDER BY plays DESC, highest_score DESC
";
$gamesStmt = $conn->prepare($gamesQuery);
$gamesStmt->bind_param('i', $user['user_id']);
$gamesStmt->execute();
$gameStats = $gamesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$gamesStmt->close();


// Get user badges (achievements)
$badgesQuery = "
    SELECT 
        a.name,
        a.description,
        a.icon,
        ua.earned_at
    FROM user_achievements ua
    JOIN achievements a ON ua.achievement_id = a.achievement_id
    WHERE ua.user_id = ?
    ORDER BY ua.earned_at DESC
";
$badgesStmt = $conn->prepare($badgesQuery);
$badgesStmt->bind_param('i', $user['user_id']);
$badgesStmt->execute();
$badges = $badgesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$badgesStmt->close();


// Calculate level
$level = floor($user['xp'] / 100) + 1;
$currentLevelXp = ($level - 1) * 100;
$nextLevelXp = $level * 100;
$progressXp = $user['xp'] - $currentLevelXp;
$progressPercentage = (($progressXp) / 100) * 100;

// Get total plays
$totalPlays = array_sum(array_column($gameStats, 'plays'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - GameVerse</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #0a0a1a;
            color: #f0f0f0;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }


        /* Navigation bar */
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


        /* Profile Container */
        .profile-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .profile-header {
            margin-bottom: 2rem;
        }

        .profile-header h1 {
            font-size: 2.5rem;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .profile-header p {
            color: #b8c2cc;
            font-size: 1.1rem;
        }


        /* Card styling */
        .card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
        }

        .card:hover {
            border-color: rgba(0, 243, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .card h2 {
            color: #fff;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }


        /* Profile Grid */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }


        /* Avatar */
        .avatar-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 3rem;
            color: #fff;
            margin: 0 auto 1.5rem;
            border: 4px solid rgba(0, 243, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .username {
            font-size: 1.8rem;
            font-weight: bold;
            color: #fff;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .user-title {
            text-align: center;
            color: #00f3ff;
            font-size: 1rem;
            margin-bottom: 1rem;
        }


        /* House Badge */
        .house-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.6rem 1.2rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }


        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #00f3ff;
            display: block;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #b8c2cc;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }


        /* Level Progress */
        .level-section {
            margin-bottom: 1.5rem;
        }

        .level-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .level-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(0, 243, 255, 0.1);
            border: 1px solid rgba(0, 243, 255, 0.3);
            border-radius: 999px;
            color: #00f3ff;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .xp-text {
            color: #b8c2cc;
            font-size: 0.9rem;
        }

        .progress-bar-container {
            height: 12px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 999px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #00f3ff, #bc13fe);
            border-radius: 999px;
            transition: width 0.5s ease;
            box-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
        }

        /* Added house assignment styles */
        .house-assignment {
            text-align: center;
            padding: 2rem;
        }

        .house-icon-large {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .assign-house-btn {
            background: linear-gradient(135deg, #00f3ff, #bc13fe);
            color: #fff;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(0, 243, 255, 0.3);
        }

        .assign-house-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 243, 255, 0.5);
        }

        .assign-house-btn:active {
            transform: translateY(0);
        }

        .house-display {
            text-align: center;
            padding: 2rem;
        }

        .house-name-large {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .house-description {
            color: #b8c2cc;
            line-height: 1.6;
            font-size: 1rem;
        }

        .success-message {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }


        /* Game Stats */
        .games-grid {
            display: grid;
            gap: 1rem;
        }

        .game-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.2s ease;
        }

        .game-item:hover {
            background: rgba(0, 243, 255, 0.05);
            border-color: rgba(0, 243, 255, 0.2);
        }

        .game-info h3 {
            color: #fff;
            margin: 0 0 0.25rem 0;
            font-size: 1.1rem;
        }

        .game-meta {
            color: #b8c2cc;
            font-size: 0.85rem;
        }

        .game-scores {
            text-align: right;
        }

        .high-score {
            font-size: 1.3rem;
            font-weight: bold;
            color: #00f3ff;
            display: block;
        }

        .avg-score {
            font-size: 0.85rem;
            color: #b8c2cc;
        }


        /* Badges */
        .badges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }

        .badge-item {
            text-align: center;
            padding: 1.5rem 1rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .badge-item:hover {
            transform: translateY(-5px);
            border-color: rgba(0, 243, 255, 0.3);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }

        .badge-icon {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        .badge-name {
            font-weight: bold;
            color: #fff;
            margin-bottom: 0.25rem;
            font-size: 0.95rem;
        }

        .badge-description {
            color: #b8c2cc;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }

        .badge-date {
            color: #00f3ff;
            font-size: 0.75rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            opacity: 0.5;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .empty-state-text {
            color: #b8c2cc;
        }


        /* Responsive */
        @media (max-width: 968px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .navbar-container {
                padding: 0 1rem;
            }

            .profile-container {
                padding: 1rem;
            }

            .profile-header h1 {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .badges-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
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

    <div class="profile-container">
        <div class="profile-header">
            <h1>My Profile</h1>
            <p>View your stats, achievements, and progress</p>
        </div>

        <?php if (isset($_GET['assigned'])): ?>
        <div class="success-message">
            Congratulations! You've been assigned to <?= htmlspecialchars($houseName) ?>!
        </div>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Left Column -->
            <div>
                <div class="card">
                    <div class="avatar-large">
                        <?= strtoupper(substr($user['username'], 0, 2)) ?>
                    </div>
                    <div class="username"><?= htmlspecialchars($user['username']) ?></div>
                    <div class="user-title">Rank #<?= $userRank ?></div>
                    
                    <?php if ($hasHouse): ?>
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <span class="house-badge" style="background-color: <?= $houseColor ?>">
                            <?= htmlspecialchars($houseName) ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-value"><?= number_format($user['xp']) ?></span>
                            <span class="stat-label">Total XP</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?= $level ?></span>
                            <span class="stat-label">Level</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?= count($gameStats) ?></span>
                            <span class="stat-label">Games Played</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?= $totalPlays ?></span>
                            <span class="stat-label">Total Plays</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                <div class="card">
                    <h2>
                        <span>⭐</span>
                        <span>Level Progress</span>
                    </h2>
                    <div class="level-section">
                        <div class="level-info">
                            <span class="level-badge">Level <?= $level ?></span>
                            <span class="xp-text"><?= $progressXp ?> / 100 XP</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?= $progressPercentage ?>%"></div>
                        </div>
                        <div style="text-align: center; margin-top: 1rem; color: #b8c2cc;">
                            <?= (100 - $progressXp) ?> XP until Level <?= ($level + 1) ?>
                        </div>
                    </div>
                </div>

                <!-- House Assignment/Display Section -->
                <div class="card" style="margin-top: 1.5rem;">
                    <h2>
                        <span>🏠</span>
                        <span>Your House</span>
                    </h2>
                    
                    <?php if (!$hasHouse): ?>
                    <div class="house-assignment">
                        <div class="house-icon-large">🏠</div>
                        <h3 style="color: #fff; margin-bottom: 1rem;">Join a House!</h3>
                        <p style="color: #b8c2cc; margin-bottom: 2rem; line-height: 1.6;">
                            You haven't been assigned to a house yet. Click the button below to be randomly assigned to one of our four gaming houses: Hipsters, Speedsters, Engineers, or Shadows!
                        </p>
                        <form method="POST">
                            <button type="submit" name="assign_house" class="assign-house-btn">
                                Assign Me to a House
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="house-display">
                        <div class="house-icon-large">🏠</div>
                        <div class="house-name-large" style="color: <?= $houseColor ?>">
                            <?= htmlspecialchars($houseName) ?>
                        </div>
                        <p class="house-description">
                            <?= htmlspecialchars($houseDescription) ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <!-- Game Statistics -->
        <div class="card" style="margin-bottom: 2rem;">
            <h2>
                <span>🎮</span>
                <span>Game Statistics</span>
            </h2>
            <?php if (!empty($gameStats)): ?>
            <div class="games-grid">
                <?php foreach ($gameStats as $game): ?>
                <div class="game-item">
                    <div class="game-info">
                        <h3><?= htmlspecialchars($game['title']) ?></h3>
                        <div class="game-meta">
                            <?= $game['plays'] ?> play<?= $game['plays'] != 1 ? 's' : '' ?>
                        </div>
                    </div>
                    <div class="game-scores">
                        <span class="high-score"><?= number_format($game['highest_score']) ?></span>
                        <span class="avg-score">Avg: <?= number_format($game['avg_score'], 0) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎮</div>
                <div class="empty-state-text">No games played yet. Start playing to see your stats!</div>
            </div>
            <?php endif; ?>
        </div>


        <!-- Badges & Achievements -->
        <div class="card">
            <h2>
                <span>🏆</span>
                <span>Badges & Achievements</span>
            </h2>
            <?php if (!empty($badges)): ?>
            <div class="badges-grid">
                <?php foreach ($badges as $badge): ?>
                <div class="badge-item">
                    <div class="badge-icon"><?= htmlspecialchars($badge['icon']) ?></div>
                    <div class="badge-name"><?= htmlspecialchars($badge['name']) ?></div>
                    <div class="badge-description"><?= htmlspecialchars($badge['description']) ?></div>
                    <div class="badge-date">
                        <?= date('M j, Y', strtotime($badge['earned_at'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">🏆</div>
                <div class="empty-state-text">No badges earned yet. Complete challenges to unlock achievements!</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

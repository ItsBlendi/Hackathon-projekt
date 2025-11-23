<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config/database.php';

$user = requireLogin();

$pageTitle = 'Leaderboard';
$activeNav = 'leaderboard';

// Set base URL
$base_url = ''; // This should be set in your config or functions.php

// Get all users with their house and game stats
$query = "
    SELECT 
        u.user_id,
        u.username,
        u.xp,
        h.name as house_name,
        h.color as house_color,
        (
            SELECT g.title 
            FROM scores s 
            JOIN games g ON s.game_id = g.game_id 
            WHERE s.user_id = u.user_id 
            GROUP BY s.game_id 
            ORDER BY COUNT(*) DESC, MAX(s.score) DESC 
            LIMIT 1
        ) as best_game,
        (
            SELECT MAX(score) 
            FROM scores 
            WHERE user_id = u.user_id
        ) as highest_score
    FROM users u
    LEFT JOIN houses h ON u.house_id = h.house_id
    WHERE u.xp > 0
    ORDER BY u.xp DESC, highest_score DESC
    LIMIT 100
";

$stmt = $conn->prepare($query);
$stmt->execute();
$players = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get house rankings
$houseQuery = "
    SELECT 
        h.house_id,
        h.name,
        h.color,
        COALESCE(SUM(u.xp), 0) as total_xp,
        COUNT(DISTINCT u.user_id) as member_count
    FROM houses h
    LEFT JOIN users u ON h.house_id = u.house_id
    GROUP BY h.house_id
    ORDER BY total_xp DESC
";

$houseStmt = $conn->prepare($houseQuery);
$houseStmt->execute();
$houses = $houseStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$houseNameMap = [
    'Gryffindor' => 'Hipsters',
    'Slytherin' => 'Speedsters',
    'Ravenclaw' => 'Engineers',
    'Hufflepuff' => 'Shadows'
];

// Update house names in the results
foreach ($houses as &$house) {
    if (isset($houseNameMap[$house['name']])) {
        $house['name'] = $houseNameMap[$house['name']];
    }
}
unset($house);

// Update player house names
foreach ($players as &$player) {
    if (isset($player['house_name']) && isset($houseNameMap[$player['house_name']])) {
        $player['house_name'] = $houseNameMap[$player['house_name']];
    }
}
unset($player);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - GameVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Added navigation bar styling */
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
            margin: 0;
            padding: 0;
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
            color: #ef4444;
        }

        /* Updated to match dashboard styling with card-based layout */
        body {
            background-color: #0a0a1a;
            color: #f0f0f0;
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .leaderboard-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .leaderboard-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .leaderboard-header h1 {
            color: #fff;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 0 20px rgba(0, 243, 255, 0.3);
        }

        .leaderboard-header p {
            color: #b8c2cc;
            font-size: 1.1rem;
        }

        /* Card styling matching dashboard */
        .card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
            margin-bottom: 2rem;
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

        /* Table Styling */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            color: #f0f0f0;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #00f3ff;
            padding: 1rem;
            background: rgba(0, 243, 255, 0.05);
            border-bottom: 2px solid rgba(0, 243, 255, 0.2);
            white-space: nowrap;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            vertical-align: middle;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background: rgba(0, 243, 255, 0.05);
        }

        /* Rank styling */
        .rank-1 { 
            background: linear-gradient(90deg, rgba(255, 215, 0, 0.1), transparent) !important;
        }
        .rank-2 { 
            background: linear-gradient(90deg, rgba(192, 192, 192, 0.1), transparent) !important;
        }
        .rank-3 { 
            background: linear-gradient(90deg, rgba(205, 127, 50, 0.1), transparent) !important;
        }

        .medal {
            font-size: 1.5rem;
            margin-right: 0.5rem;
            display: inline-block;
        }

        /* Avatar styling */
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            color: #fff;
        }

        /* House badge matching dashboard style */
        .house-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.4rem 0.8rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* House cards grid */
        .house-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .house-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .house-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, currentColor, transparent);
            opacity: 0.5;
        }

        .house-card:hover {
            transform: translateY(-5px);
            border-color: rgba(0, 243, 255, 0.3);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }

        .house-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
        }

        .house-card h3 {
            color: #fff;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .house-xp {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 999px;
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: #fff;
        }

        .house-members {
            color: #b8c2cc;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        /* Progress bar */
        .progress {
            height: 8px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 999px;
            overflow: hidden;
            margin-top: 1rem;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, currentColor, transparent);
            transition: width 0.5s ease;
            border-radius: 999px;
        }

        /* Text utilities */
        .text-muted {
            color: #b8c2cc !important;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .fw-bold {
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .leaderboard-container {
                padding: 1rem;
            }
            
            .leaderboard-header h1 {
                font-size: 2rem;
            }

            .house-grid {
                grid-template-columns: 1fr;
            }

            .table th,
            .table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Added inline navigation bar with My Account link to profile.php -->
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

    <div class="leaderboard-container">
        <div class="leaderboard-header">
            <h1>🏆 Leaderboard</h1>
            <p>Compete with players across all houses</p>
        </div>
        
        <!-- Player Leaderboard -->
        <div class="card">
            <h2>
                <span>👥</span>
                <span>Top Players</span>
            </h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="80">Rank</th>
                            <th>Player</th>
                            <th class="text-center">House</th>
                            <th class="text-end">XP</th>
                            <th class="text-center">Best Game</th>
                            <th class="text-end">High Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($players as $index => $player): 
                            $rank = $index + 1;
                            $rankClass = '';
                            $medal = '';
                            
                            if ($rank === 1) {
                                $rankClass = 'rank-1';
                                $medal = '🥇';
                            } elseif ($rank === 2) {
                                $rankClass = 'rank-2';
                                $medal = '🥈';
                            } elseif ($rank === 3) {
                                $rankClass = 'rank-3';
                                $medal = '🥉';
                            }
                        ?>
                            <tr class="<?= $rankClass ?>">
                                <td>
                                    <span class="medal"><?= $medal ?></span>
                                    <span class="text-muted">#<?= $rank ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div class="avatar">
                                            <?= strtoupper(substr($player['username'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($player['username']) ?></div>
                                            <small class="text-muted">Level <?= floor($player['xp'] / 100) + 1 ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($player['house_name']): ?>
                                        <span class="house-badge" style="background-color: <?= $player['house_color'] ?>">
                                            <?= htmlspecialchars($player['house_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold"><?= number_format($player['xp']) ?></span>
                                    <small class="text-muted"> XP</small>
                                </td>
                                <td class="text-center">
                                    <?= $player['best_game'] ? htmlspecialchars($player['best_game']) : '<span class="text-muted">-</span>' ?>
                                </td>
                                <td class="text-end">
                                    <?= $player['highest_score'] ? number_format($player['highest_score']) : '<span class="text-muted">-</span>' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($players)): ?>
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 3rem;">
                                    <div style="opacity: 0.5;">
                                        <div style="font-size: 3rem; margin-bottom: 1rem;">🎮</div>
                                        <div>No players found. Be the first to play a game!</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- House Leaderboard -->
        <div class="card">
            <h2>
                <span>🏠</span>
                <span>House Rankings</span>
            </h2>
            <div class="house-grid">
                <?php foreach ($houses as $index => $house): 
                    $medal = '';
                    if ($index === 0) $medal = '🥇';
                    elseif ($index === 1) $medal = '🥈';
                    elseif ($index === 2) $medal = '🥉';
                    else $medal = '🏠';
                ?>
                    <div class="house-card" style="color: <?= $house['color'] ?>">
                        <div class="house-icon">
                            <?= $medal ?>
                        </div>
                        <h3><?= htmlspecialchars($house['name']) ?></h3>
                        <div class="house-xp" style="background-color: <?= $house['color'] ?>33; border: 1px solid <?= $house['color'] ?>;">
                            <?= number_format($house['total_xp']) ?> XP
                        </div>
                        <div class="house-members">
                            <?= $house['member_count'] ?> member<?= $house['member_count'] != 1 ? 's' : '' ?>
                        </div>
                        <div class="progress">
                            <?php 
                                $maxXp = !empty($houses) ? max(array_column($houses, 'total_xp')) : 1;
                                $percentage = $maxXp > 0 ? ($house['total_xp'] / $maxXp) * 100 : 0;
                            ?>
                            <div class="progress-bar" 
                                 style="width: <?= $percentage ?>%; color: <?= $house['color'] ?>">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (empty($houses)): ?>
                <div class="text-center" style="padding: 3rem; opacity: 0.5;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🏠</div>
                    <div>No house data available.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

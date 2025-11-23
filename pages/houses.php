<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/config/database.php';

$user = requireLogin();

$pageTitle = 'Houses';
$activeNav = 'houses';

// If user has no house yet, assign one randomly and store in DB + session
if (empty($user['house_id'])) {
    $newHouseId = assignUserToHouse();

    if (!empty($newHouseId)) {
        $stmt = $conn->prepare("UPDATE users SET house_id = ? WHERE user_id = ?");
        $stmt->bind_param('ii', $newHouseId, $user['id']);
        $stmt->execute();
        $stmt->close();

        // Update session and local user array
        $_SESSION['house_id'] = $newHouseId;
        $user['house_id'] = $newHouseId;
    }
}

// Load current house details
$house = null;
if (!empty($user['house_id'])) {
    $stmt = $conn->prepare("SELECT house_id, name, description FROM houses WHERE house_id = ? LIMIT 1");
    $stmt->bind_param('i', $user['house_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $house = $row;
    }
    $stmt->close();
}

// Load all houses for display
$houses = [];
$result = $conn->query("SELECT house_id, name, description FROM houses ORDER BY house_id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $houses[] = $row;
    }
}

// Map house names to updated names
$houseNameMap = [
    'Gryffindor' => 'Hipsters',
    'Slytherin' => 'Speedsters',
    'Ravenclaw' => 'Engineers',
    'Hufflepuff' => 'Shadows'
];

// Update house name
if ($house && isset($houseNameMap[$house['name']])) {
    $house['name'] = $houseNameMap[$house['name']];
}

// Update houses names
foreach ($houses as &$h) {
    if (isset($houseNameMap[$h['name']])) {
        $h['name'] = $houseNameMap[$h['name']];
    }
}
unset($h);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - GameVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Navigation bar styling matching other pages */
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

        body {
            background-color: #0a0a1a;
            color: #f0f0f0;
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .houses-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .houses-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .houses-header h1 {
            color: #fff;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 0 20px rgba(0, 243, 255, 0.3);
        }

        .houses-header p {
            color: #b8c2cc;
            font-size: 1.1rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
        }

        .card:hover {
            border-color: rgba(0, 243, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .current-house {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        .current-house h2 {
            color: #fff;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .current-house .house-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            background: rgba(0, 243, 255, 0.1);
            border: 1px solid rgba(0, 243, 255, 0.3);
            color: #00f3ff;
        }

        .current-house .house-description {
            color: #b8c2cc;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .current-house .house-note {
            color: #9ca3af;
            font-size: 0.9rem;
            font-style: italic;
        }

        .houses-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .house-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
            text-align: center;
        }

        .house-card:hover {
            transform: translateY(-5px);
            border-color: rgba(0, 243, 255, 0.3);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }

        .house-card h3 {
            color: #fff;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .house-card p {
            color: #b8c2cc;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .houses-container {
                padding: 1.5rem 1rem;
            }

            .houses-header h1 {
                font-size: 2rem;
            }

            .houses-list {
                grid-template-columns: 1fr;
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
                <li><a href="index.php?page=houses" class="<?= $activeNav === 'houses' ? 'active' : '' ?>">Houses</a></li>
                <li><a href="index.php?page=profile" class="<?= $activeNav === 'profile' ? 'active' : '' ?>">My Account</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="houses-container">
        <div class="houses-header">
            <h1>🏠 Houses of GameVerse</h1>
            <p>Each player is part of a house. Your house influences your identity and competitions.</p>
        </div>

        <?php if ($house): ?>
            <div class="current-house card">
                <h2>Your House</h2>
                <div class="house-badge">
                    <span><?= htmlspecialchars($house['name']) ?></span>
                </div>
                <?php if (!empty($house['description'])): ?>
                    <p class="house-description"><?= htmlspecialchars($house['description']) ?></p>
                <?php endif; ?>
                <p class="house-note">You were randomly assigned to this house to keep teams balanced.</p>
            </div>
        <?php else: ?>
            <div class="current-house card">
                <h2>Your House</h2>
                <p>No house information available yet.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($houses)): ?>
            <div class="card">
                <h2 style="color: #fff; margin-bottom: 1.5rem; text-align: center;">All Houses</h2>
                <div class="houses-list">
                    <?php foreach ($houses as $h): ?>
                        <div class="house-card">
                            <h3><?= htmlspecialchars($h['name']) ?></h3>
                            <?php if (!empty($h['description'])): ?>
                                <p><?= htmlspecialchars($h['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

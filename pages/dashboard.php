<?php
// Include auth functions and protect page - require user to be logged in
require_once __DIR__ . '/../includes/auth_functions.php';

$user = requireLogin();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

// Fetch the user's house info (if any)
$houseName = 'Unassigned';
$houseDescription = '';

if (!empty($user['house_id'])) {
    $stmt = $conn->prepare("SELECT name, description FROM houses WHERE house_id = ? LIMIT 1");
    $stmt->bind_param('i', $user['house_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $houseName = $row['name'];
        $houseDescription = $row['description'];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - GameVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
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

        .dashboard-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header h1 {
            color: #fff;
            margin-bottom: 0.25rem;
            font-size: 2.5rem;
            text-shadow: 0 0 20px rgba(0, 243, 255, 0.3);
        }

        .dashboard-header p {
            color: #b8c2cc;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1.5fr);
            gap: 1.5rem;
            margin-top: 2rem;
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

        .card h2 {
            color: #fff;
            margin-bottom: 1rem;
        }

        .card-house p {
            color: #b8c2cc;
        }

        .house-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            font-weight: 600;
            margin-bottom: 0.75rem;
            background: rgba(0, 243, 255, 0.1);
            border: 1px solid rgba(0, 243, 255, 0.3);
            color: #00f3ff;
        }

        .house-description {
            color: #b8c2cc;
            margin-bottom: 1rem;
        }

        .card-quick-actions ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .card-quick-actions li + li {
            margin-top: 0.5rem;
        }

        .card-quick-actions .link {
            color: #00f3ff;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .card-quick-actions .link:hover {
            color: #bc13fe;
        }

        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background-color: #00f3ff;
            color: #0a0a1a;
        }

        .btn-primary:hover {
            background-color: #00d9e6;
            color: #0a0a1a;
        }

        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
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
                <li><a href="index.php?page=profile" class="<?= $activeNav === 'profile' ? 'active' : '' ?>">My Account</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h1>
            <p>Your personal GameVerse overview</p>
        </div>

        <div class="dashboard-grid">
            <div class="card card-house">
                <h2>Your House</h2>
                <?php if ($houseName === 'Unassigned'): ?>
                    <p>You don't have a house assigned yet.</p>
                    <a href="index.php?page=houses" class="btn btn-primary">Discover Houses</a>
                <?php else: ?>
                    <div class="house-badge">
                        <span><?php echo htmlspecialchars($houseName); ?></span>
                    </div>
                    <?php if (!empty($houseDescription)): ?>
                        <p class="house-description"><?php echo htmlspecialchars($houseDescription); ?></p>
                    <?php endif; ?>
                    <a href="index.php?page=houses" class="btn btn-secondary">View House Details</a>
                <?php endif; ?>
            </div>

            <div class="card card-quick-actions">
                <h2>Quick Actions</h2>
                <ul>
                    <li><a href="index.php?page=games" class="link">Play Games</a></li>
                    <li><a href="index.php?page=leaderboard" class="link">View Leaderboard</a></li>
                    <li><a href="index.php?page=profile" class="link">Edit Profile</a></li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Include auth functions and protect page - require user to be logged in
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . 'includes/auth_functions.php';
} else {
    require_once __DIR__ . '/../../includes/auth_functions.php';
}

$user = requireLogin();

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

<style>
.dashboard-container {
    padding: 2rem;
}

.dashboard-header h1 {
    color: #fff;
    margin-bottom: 0.25rem;
}

.dashboard-header p {
    color: #b8c2cc;
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
}

.card-quick-actions .link:hover {
    color: #bc13fe;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// Include auth functions and require login
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . 'includes/auth_functions.php';
} else {
    require_once __DIR__ . '/../../includes/auth_functions.php';
}

$user = requireLogin();

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
?>

<div class="houses-container">
    <div class="houses-header">
        <h1>Houses of GameVerse</h1>
        <p>Each player is part of a house. Your house influences your identity and competitions.</p>
    </div>

    <?php if ($house): ?>
        <div class="current-house card">
            <h2>Your House</h2>
            <div class="house-badge">
                <span><?php echo htmlspecialchars($house['name']); ?></span>
            </div>
            <?php if (!empty($house['description'])): ?>
                <p class="house-description"><?php echo htmlspecialchars($house['description']); ?></p>
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
        <div class="houses-list">
            <?php foreach ($houses as $h): ?>
                <div class="house-card card">
                    <h3><?php echo htmlspecialchars($h['name']); ?></h3>
                    <?php if (!empty($h['description'])): ?>
                        <p><?php echo htmlspecialchars($h['description']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.houses-container {
    padding: 2rem;
}

.houses-header h1 {
    color: #fff;
    margin-bottom: 0.25rem;
}

.houses-header p {
    color: #b8c2cc;
}

.current-house {
    margin-top: 2rem;
}

.current-house .house-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1rem;
    border-radius: 999px;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.current-house .house-description {
    color: #b8c2cc;
}

.current-house .house-note {
    color: #9ca3af;
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.houses-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.house-card h3 {
    color: #fff;
    margin-bottom: 0.5rem;
}

.house-card p {
    color: #b8c2cc;
}

.card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(8px);
}

@media (max-width: 768px) {
    .houses-container {
        padding: 1.5rem 1rem;
    }
}
</style>

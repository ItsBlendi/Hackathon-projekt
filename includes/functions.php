<?php
/**
 * Get user data from database
 * @param int $userId
 * @return array|null
 */
function getUserData($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get house name by ID
 * @param int $houseId
 * @return string
 */
function getHouseName($houseId) {
    $houses = [
        1 => 'Gryffindor',
        2 => 'Hufflepuff',
        3 => 'Ravenclaw',
        4 => 'Slytherin'
    ];
    return $houses[$houseId] ?? 'Unknown House';
}

/**
 * Save game score to database
 * @param int $userId
 * @param string $game
 * @param int $score
 * @return bool
 */
function saveScore($userId, $game, $score) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO game_scores (user_id, game, score, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("isi", $userId, $game, $score);
    return $stmt->execute();
}

// Check if database connection exists
if (!isset($conn)) {
    // If not, include the database connection
    require_once __DIR__ . '/config/database.php';
}
?>

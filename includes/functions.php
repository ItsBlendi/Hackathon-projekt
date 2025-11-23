<?php
// In file: includes/functions.php
require_once __DIR__ . '/config/database.php';

function getUserData($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT u.*, h.name as house_name, h.color as house_color 
                           FROM users u 
                           LEFT JOIN houses h ON u.house_id = h.house_id 
                           WHERE u.user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getHouseName($houseId) {
    if (empty($houseId)) return 'No House';
    
    global $conn;
    $stmt = $conn->prepare("SELECT name FROM houses WHERE house_id = ?");
    $stmt->bind_param("i", $houseId);
    $stmt->execute();
    $result = $stmt->get_result();
    $house = $result->fetch_assoc();
    return $house ? $house['name'] : 'No House';
}

function getGameIdBySlug($gameSlug) {
    global $conn;
    $stmt = $conn->prepare("SELECT game_id FROM games WHERE slug = ?");
    $stmt->bind_param("s", $gameSlug);
    $stmt->execute();
    $result = $stmt->get_result();
    $game = $result->fetch_assoc();
    return $game ? $game['game_id'] : null;
}
/**
 * Save game score to database and update user XP
 * @param int $userId
 * @param string $gameSlug
 * @param int $score
 * @return array|bool Returns array with success status and XP earned, or false on failure
 */
function saveScore($userId, $gameSlug, $score) {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get game ID
        $gameId = getGameIdBySlug($gameSlug);
        if (!$gameId) {
            throw new Exception("Game not found");
        }
        
        // Get user's house ID
        $userStmt = $conn->prepare("SELECT house_id FROM users WHERE user_id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $user = $userStmt->get_result()->fetch_assoc();
        $houseId = $user ? $user['house_id'] : null;
        
        // Calculate XP (1 XP per 2 points, max 100 per game)
        $xpEarned = min(100, floor($score / 2));
        
        // Save score
        $stmt = $conn->prepare("INSERT INTO scores (user_id, house_id, game_id, score, xp_earned, played_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiiid", $userId, $houseId, $gameId, $score, $xpEarned);
        $stmt->execute();
        
        // Update user's XP
        $updateStmt = $conn->prepare("UPDATE users SET xp = xp + ? WHERE user_id = ?");
        $updateStmt->bind_param("ii", $xpEarned, $userId);
        $updateStmt->execute();
        
        // Update house's total XP if user is in a house
        if ($houseId) {
            $houseStmt = $conn->prepare("UPDATE houses SET total_xp = total_xp + ? WHERE house_id = ?");
            $houseStmt->bind_param("ii", $xpEarned, $houseId);
            $houseStmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'xp_earned' => $xpEarned,
            'new_xp' => $user ? $user['xp'] + $xpEarned : $xpEarned,
            'level' => floor(($user ? $user['xp'] + $xpEarned : $xpEarned) / 100) + 1
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error saving score: " . $e->getMessage());
        return false;
    }
}

// Check if database connection exists
if (!isset($conn)) {
    // If not, include the database connection
    require_once __DIR__ . '/config/database.php';
}
?>

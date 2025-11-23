<?php
session_start();
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$gameSlug = $data['game'] ?? '';
$score = intval($data['score'] ?? 0);

if (empty($gameSlug) || $score <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

try {
    // Save the score and get XP info
    $result = saveScore($_SESSION['user_id'], $gameSlug, $score);
    
    if ($result === false) {
        throw new Exception('Failed to save score');
    }
    
    // Check if this is a new high score for the user
    $highScoreCheck = $GLOBALS['conn']->prepare(
        "SELECT COUNT(*) as is_high_score FROM scores 
         WHERE user_id = ? AND game_id = (SELECT game_id FROM games WHERE slug = ?) 
         AND score > ?"
    );
    $highScoreCheck->bind_param("isi", $_SESSION['user_id'], $gameSlug, $score);
    $highScoreCheck->execute();
    $isHighScore = $highScoreCheck->get_result()->fetch_assoc()['is_high_score'] == 0;
    
    // Add high score flag to the result
    $result['new_high_score'] = $isHighScore;
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'An error occurred while saving your score',
        'debug' => $e->getMessage()
    ]);
}

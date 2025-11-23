<?php
// Include auth functions and require login
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . 'includes/auth_functions.php';
} else {
    require_once __DIR__ . '/../../includes/auth_functions.php';
}

$user = requireLogin();

// Handle score submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_score') {
    header('Content-Type: application/json');
    
    if (!isset($_POST['game_slug'], $_POST['score'], $_POST['xp'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $gameSlug = $_POST['game_slug'];
    $score = (int)$_POST['score'];
    $xp = (int)$_POST['xp'];
    $userId = $_SESSION['user_id'];
    $houseId = $_SESSION['house_id'] ?? null;
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get game ID
        $gameStmt = $conn->prepare("SELECT game_id FROM games WHERE slug = ?");
        $gameStmt->bind_param("s", $gameSlug);
        $gameStmt->execute();
        $gameResult = $gameStmt->get_result();
        
        if ($gameResult->num_rows === 0) {
            throw new Exception("Game not found");
        }
        
        $gameId = $gameResult->fetch_assoc()['game_id'];
        $gameStmt->close();
        
        // Insert score
        $scoreStmt = $conn->prepare("
            INSERT INTO scores (user_id, house_id, game_id, score, xp_earned)
            VALUES (?, ?, ?, ?, ?)
        ");
        $scoreStmt->bind_param("iiiis", $userId, $houseId, $gameId, $score, $xp);
        $scoreStmt->execute();
        $scoreStmt->close();
        
        // Update user XP
        $updateUser = $conn->prepare("
            UPDATE users 
            SET xp = xp + ? 
            WHERE user_id = ?
        ");
        $updateUser->bind_param("ii", $xp, $userId);
        $updateUser->execute();
        $updateUser->close();
        
        // Update house XP if house exists
        if ($houseId) {
            $updateHouse = $conn->prepare("
                UPDATE houses 
                SET total_xp = total_xp + ? 
                WHERE house_id = ?
            ");
            $updateHouse->bind_param("ii", $xp, $houseId);
            $updateHouse->execute();
            $updateHouse->close();
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Score saved successfully',
            'xp_earned' => $xp,
            'new_total_xp' => $user['xp'] + $xp
        ]);
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save score: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Get the requested game
$gameSlug = $_GET['game'] ?? '';

// Basic metadata for games
$games = [
    'reaction-rush' => [
        'title' => 'Reaction Rush',
        'tagline' => 'Click as fast as you can when the arena lights up.',
    ],
    'number-ninja' => [
        'title' => 'Number Ninja',
        'tagline' => 'Beat the clock by solving quick math challenges.',
    ],
    'memory-grid' => [
        'title' => 'Memory Grid',
        'tagline' => 'Flip and match tiles before time runs out.',
    ],
    'dodge-squares' => [
        'title' => 'Dodge Squares',
        'tagline' => 'Avoid the red tiles and survive as long as possible.',
    ],
    'flappy-bird' => [
        'title' => 'Flappy Bird',
        'tagline' => 'Navigate the bird through the pipes!',
    ],
];

$game = $games[$gameSlug] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game ? $game['title'] . ' - GameVerse' : 'Game Not Found'); ?></title>
    <style>
        .play-container {
            padding: 2rem;
            display: flex;
            justify-content: center;
            min-height: 70vh;
        }

        .play-card {
            max-width: 800px;
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
        }

        .play-card h1 {
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .play-tagline {
            color: #4cc9f0;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .play-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            color: #d1d5db;
            font-size: 0.95rem;
            margin-top: 1.5rem;
        }

        .play-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        #game-root {
            margin-top: 1rem;
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loading {
            color: #9ca3af;
            font-size: 1.2rem;
            animation: pulse 1.5s infinite;
        }

        .error {
            color: #ef4444;
            text-align: center;
            padding: 1rem;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 8px;
            margin: 1rem 0;
        }

        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }

        @media (max-width: 768px) {
            .play-container {
                padding: 1.5rem 1rem;
            }

            .play-card {
                padding: 1.5rem;
            }
        }
        
        /* Button styles */
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        
        .btn-primary {
            background-color: #4f46e5;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #4338ca;
        }
        
        .btn-secondary {
            background-color: #374151;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #1f2937;
        }
    </style>
</head>
<body>
    <div class="play-container">
        <?php if (!$game): ?>
            <div class="play-card">
                <h1>Game Not Found</h1>
                <p>The selected game could not be found.</p>
                <a href="index.php?page=games" class="btn btn-secondary">Back to Games</a>
            </div>
        <?php else: ?>
            <div class="play-card">
                <h1><?php echo htmlspecialchars($game['title']); ?></h1>
                <p class="play-tagline"><?php echo htmlspecialchars($game['tagline']); ?></p>

                <div id="game-root" data-game="<?php echo htmlspecialchars($gameSlug); ?>">
                    <div class="loading">Loading game, please wait...</div>
                </div>

                <div class="play-meta" id="play-meta" style="display:none;">
                    <span><strong>Score:</strong> <span id="score-value">0</span></span>
                    <span><strong>XP Earned:</strong> <span id="xp-value">0</span></span>
                </div>

                <div class="play-actions" id="play-actions" style="display:none;">
                    <a href="index.php?page=games" class="btn btn-secondary">Back to Games</a>
                    <button type="button" class="btn btn-primary" id="play-again-btn">Play Again</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Load game dynamically
    document.addEventListener('DOMContentLoaded', function() {
        const root = document.getElementById('game-root');
        if (!root) return;

        const gameSlug = root.dataset.game;
        if (!gameSlug) {
            root.innerHTML = '<div class="error">No game specified.</div>';
            return;
        }

        // Show loading state
        root.innerHTML = '<div class="loading">Loading game, please wait...</div>';

        // Function to load script
        function loadScript(src) {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src;
                script.onload = resolve;
                script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
                document.head.appendChild(script);
            });
        }

        // Function to convert kebab-case to PascalCase
        function toPascalCase(str) {
            return str.split('-').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
            ).join('');
        }

        // Load required scripts
        async function loadGame() {
            try {
                // Load game functions first
                await loadScript('/GameVerse/Hackathon-projekt/pages/games/includes/game-functions.js');
                
                // Then load the specific game
                await loadScript(`/GameVerse/Hackathon-projekt/pages/games/games/${gameSlug}.js`);
                
                // Get the game class name
                const gameClassName = toPascalCase(gameSlug);
                
                // Check if the game class exists
                if (window[gameClassName]) {
                    // Create and initialize the game
                    const gameInstance = new window[gameClassName](root);
                    if (typeof gameInstance.init === 'function') {
                        gameInstance.init();
                    } else {
                        throw new Error(`Game class ${gameClassName} is missing the init() method`);
                    }
                } else {
                    throw new Error(`Game class ${gameClassName} not found`);
                }
            } catch (error) {
                console.error('Game loading error:', error);
                root.innerHTML = `
                    <div class="error">
                        <p>Failed to load the game. Please try again later.</p>
                        <p>Error: ${error.message}</p>
                    </div>`;
            }
        }

        // Start loading the game
        loadGame();
    });
    </script>

    <script>
    // Function to show game result and save score
    function showResult(score, xp) {
        const scoreValue = document.getElementById('score-value');
        const xpValue = document.getElementById('xp-value');
        const meta = document.getElementById('play-meta');
        const actions = document.getElementById('play-actions');
        const gameSlug = document.getElementById('game-root')?.dataset.game;

        if (scoreValue) scoreValue.textContent = score;
        if (xpValue) xpValue.textContent = xp;
        if (meta) meta.style.display = 'flex';
        if (actions) actions.style.display = 'flex';

        // Send score to server if game slug is available
        if (gameSlug) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'save_score',
                    'game_slug': gameSlug,
                    'score': score,
                    'xp': xp
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Score saved:', data);
                } else {
                    console.error('Failed to save score:', data.message);
                }
            })
            .catch(error => {
                console.error('Error saving score:', error);
            });
        }
    }

    // Function to reset the result display
    function resetResult() {
        const meta = document.getElementById('play-meta');
        const actions = document.getElementById('play-actions');
        if (meta) meta.style.display = 'none';
        if (actions) actions.style.display = 'none';
    }

    // Set up play again button
    const playAgainBtn = document.getElementById('play-again-btn');
    if (playAgainBtn) {
        playAgainBtn.addEventListener('click', function () {
            resetResult();
            // Reload page to reset game state
            window.location.reload();
        });
    }
    </script>
</body>
</html>
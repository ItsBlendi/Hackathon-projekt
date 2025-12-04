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
$activeNav = 'games';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game ? $game['title'] . ' - GameVerse' : 'Game Not Found'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #0a0a1a;
            color: #f0f0f0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

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
            margin: 0;
            padding: 0;
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
            white-space: nowrap;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .play-container {
            padding: 2rem;
            display: flex;
            justify-content: center;
            min-height: calc(100vh - 80px);
            position: relative;
        }

        .play-card {
            max-width: 900px;
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            height: fit-content;
        }

        .play-card h1 {
            color: #fff;
            margin-bottom: 0.5rem;
            font-size: 2rem;
            font-weight: 700;
        }

        .play-tagline {
            color: #00f3ff;
            margin-bottom: 1.5rem;
            font-weight: 500;
            font-size: 1rem;
            text-shadow: 0 0 20px rgba(0, 243, 255, 0.3);
        }

        .play-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            color: #b8c2cc;
            font-size: 0.95rem;
            margin-top: 1.5rem;
            padding: 1rem;
            background: rgba(0, 243, 255, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(0, 243, 255, 0.1);
        }

        .play-meta strong {
            color: #00f3ff;
            font-weight: 600;
        }

        .play-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        #score-value,
        #xp-value {
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .play-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        #game-root {
            margin-top: 1.5rem;
            margin-bottom: 1.5rem;
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .loading {
            color: #00f3ff;
            font-size: 1.2rem;
            animation: pulse 1.5s infinite;
        }

        .error {
            color: #fca5a5;
            text-align: center;
            padding: 1.5rem;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 8px;
            margin: 1rem 0;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .error p {
            margin-bottom: 0.5rem;
        }

        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background-color: #00f3ff;
            color: #0a0a1a;
        }
        
        .btn-primary:hover:not(:disabled) {
            background-color: #00d9e6;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 243, 255, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.15);
            border-color: rgba(0, 243, 255, 0.5);
            color: #00f3ff;
        }

        .btn-secondary:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .navbar-container {
                padding: 0 1rem;
            }

            .navbar-links {
                gap: 1rem;
                font-size: 0.9rem;
            }

            .play-container {
                padding: 1.5rem 1rem;
            }

            .play-card {
                padding: 1.5rem;
            }

            .play-card h1 {
                font-size: 1.5rem;
            }

            .play-meta {
                flex-direction: column;
                gap: 0.75rem;
            }

            .play-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
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

        function toPascalCase(str) {
            return str.split('-').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
            ).join('');
        }

        async function loadGame() {
            try {
                const possiblePaths = [
                    `games/${gameSlug}.js`,
                    `pages/games/${gameSlug}.js`,
                    `pages/games/games/${gameSlug}.js`,
                ];

                let scriptLoaded = false;
                let lastError = null;

                for (const path of possiblePaths) {
                    try {
                        await loadScript(path);
                        scriptLoaded = true;
                        break;
                    } catch (err) {
                        lastError = err;
                        continue;
                    }
                }

                if (!scriptLoaded) {
                    throw lastError || new Error('Could not load game script from any path');
                }

                await new Promise(resolve => setTimeout(resolve, 100));
                
                const gameClassName = toPascalCase(gameSlug);
                
                if (typeof window[gameClassName] !== 'function') {
                    throw new Error(`Game class ${gameClassName} not found. Make sure the game file exports the class to window.`);
                }
                
                root.innerHTML = '';
                
                const gameInstance = new window[gameClassName](root);
                if (typeof gameInstance.init === 'function') {
                    gameInstance.init();
                }
                
                console.log(`Game ${gameClassName} loaded successfully!`);
            } catch (error) {
                console.error('Game loading error:', error);
                root.innerHTML = `
                    <div class="error">
                        <p>Failed to load the game. Please try again later.</p>
                        <p>Error: ${error.message}</p>
                        <p style="margin-top: 1rem; font-size: 0.9rem;">
                            Please make sure the game file (${gameSlug}.js) is in the correct location.
                        </p>
                    </div>`;
            }
        }

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

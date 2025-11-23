<?php
// Include auth functions and require login
if (defined('PROJECT_ROOT')) {
    require_once PROJECT_ROOT . 'includes/auth_functions.php';
} else {
    require_once __DIR__ . '/../../includes/auth_functions.php';
}

$user = requireLogin();

// Get user's house info
$houseName = 'Unknown';
$houseId = null;
if (isset($_SESSION['house_id'])) {
    $houseId = $_SESSION['house_id'];
    $houseStmt = $GLOBALS['conn']->prepare("SELECT name FROM houses WHERE house_id = ?");
    $houseStmt->bind_param("i", $houseId);
    $houseStmt->execute();
    $houseResult = $houseStmt->get_result();
    if ($houseRow = $houseResult->fetch_assoc()) {
        $houseName = $houseRow['name'];
    }
    $houseStmt->close();
}

// Define the 4 shared games
$allGames = [
    'flappy-bird' => [
        'title' => 'Flappy Bird',
        'tagline' => 'Navigate the bird through the pipes!',
        'difficulty' => 'Medium',
        'icon' => '🐦',
        'recommended_for' => ['All Players']
    ],
    'reaction-rush' => [
        'title' => 'Reaction Rush',
        'tagline' => 'Test your reflexes! Click when the screen changes color.',
        'difficulty' => 'Easy',
        'icon' => '⚡',
        'recommended_for' => ['Speedsters', 'Shadows']
    ],
    'number-ninja' => [
        'title' => 'Number Ninja',
        'tagline' => 'Solve math problems under pressure!',
        'difficulty' => 'Medium',
        'icon' => '🔢',
        'recommended_for' => ['Engineers', 'Hipsters']
    ],
    'memory-grid' => [
        'title' => 'Memory Grid',
        'tagline' => 'Match pairs in this memory challenge!',
        'difficulty' => 'Medium',
        'icon' => '🧠',
        'recommended_for' => ['Hipsters', 'Shadows']
    ],
    'dodge-squares' => [
        'title' => 'Dodge Squares',
        'tagline' => 'Avoid the red tiles and survive!',
        'difficulty' => 'Hard',
        'icon' => '🎮',
        'recommended_for' => ['Speedsters', 'Engineers']
    ]
];

// Define house-specific game recommendations
$houseGamesMap = [
    1 => ['flappy-bird', 'reaction-rush', 'memory-grid'],      // Hipsters
    2 => ['flappy-bird', 'number-ninja', 'dodge-squares'],    // Hustlers
    3 => ['flappy-bird', 'memory-grid', 'reaction-rush'],     // Hypebeasts
    4 => ['flappy-bird', 'dodge-squares', 'number-ninja']     // Hackers
];

// Get featured games based on house or fallback
$featuredGames = [];
if ($houseId && isset($houseGamesMap[$houseId])) {
    $slugs = $houseGamesMap[$houseId];
    foreach ($slugs as $slug) {
        if (isset($allGames[$slug])) {
            $featuredGames[$slug] = $allGames[$slug];
        } else {
            // For games not in $allGames, create a basic entry
            $featuredGames[$slug] = [
                'title' => ucwords(str_replace('-', ' ', $slug)),
                'tagline' => 'New game coming soon!',
                'difficulty' => 'Medium',
                'icon' => '🎮',
                'recommended_for' => ['All Players']
            ];
        }
    }
} else {
    // Fallback: first 2 games as featured
    $featuredGames = array_slice($allGames, 0, 2, true);
}

// All games for the main grid
$games = $allGames;
?>

<div class="games-container">
    <div class="games-header">
        <h1>Game Library</h1>
        <p class="tagline">Welcome, <?php echo htmlspecialchars($user['username']); ?> of House <?php echo htmlspecialchars($houseName); ?>!</p>
    </div>

    <?php if (!empty($featuredGames)): ?>
    <div class="featured-games">
        <h2>Recommended for You</h2>
        <div class="games-grid">
            <?php foreach ($featuredGames as $slug => $game): ?>
                <div class="game-card featured">
                    <div class="featured-badge">⭐ Featured</div>
                    <div class="game-icon"><?php echo $game['icon']; ?></div>
                    <h2><?php echo htmlspecialchars($game['title']); ?></h2>
                    <p class="game-tagline"><?php echo htmlspecialchars($game['tagline']); ?></p>
                    
                    <div class="game-meta">
                        <span class="difficulty difficulty-<?php echo strtolower($game['difficulty']); ?>">
                            <?php echo htmlspecialchars($game['difficulty']); ?>
                        </span>
                        <span class="recommended">
                            Best for: <?php echo implode(', ', $game['recommended_for']); ?>
                        </span>
                    </div>
                    
                    <a href="?page=play&game=<?php echo urlencode($slug); ?>" class="btn btn-primary">
                        Play Now
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="all-games">
        <h2>All Games</h2>

    <div class="games-grid">
        <?php foreach ($games as $slug => $game): ?>
            <div class="game-card">
                <div class="game-icon"><?php echo $game['icon']; ?></div>
                <h2><?php echo htmlspecialchars($game['title']); ?></h2>
                <p class="game-tagline"><?php echo htmlspecialchars($game['tagline']); ?></p>
                
                <div class="game-meta">
                    <span class="difficulty difficulty-<?php echo strtolower($game['difficulty']); ?>">
                        <?php echo htmlspecialchars($game['difficulty']); ?>
                    </span>
                    <span class="recommended">
                        Best for: <?php echo implode(', ', $game['recommended_for']); ?>
                    </span>
                </div>
                
                <a href="?page=play&game=<?php echo urlencode($slug); ?>" class="btn btn-primary">
                    Play Now
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.games-container {
    padding: 2rem;
    max-width: var(--max-width);
    margin: 0 auto;
}

.games-header {
    text-align: center;
    margin-bottom: 2rem;
}

.games-header h1 {
    color: var(--text-primary);
    font-family: var(--font-primary);
    margin-bottom: 0.5rem;
    font-size: 2.5rem;
    background: linear-gradient(90deg, var(--neon-blue), var(--neon-pink));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 0 0 10px rgba(0, 243, 255, 0.3);
}

.games-header .tagline {
    color: var(--text-secondary);
    font-size: 1.1rem;
}

.featured-games {
    margin-bottom: 3rem;
    padding: 1.5rem;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.featured-games h2 {
    color: var(--neon-pink);
    font-family: var(--font-primary);
    margin-bottom: 1.5rem;
    font-size: 1.8rem;
    text-align: center;
}

.featured .game-card {
    position: relative;
    border: 2px solid var(--neon-blue);
    box-shadow: 0 0 15px rgba(0, 243, 255, 0.3);
}

.featured-badge {
    position: absolute;
    top: -12px;
    right: 20px;
    background: var(--neon-pink);
    color: #000;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.games-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.all-games h2 {
    color: var(--neon-blue);
    font-family: var(--font-primary);
    margin: 2rem 0 1.5rem;
    font-size: 1.8rem;
    text-align: center;
}

.game-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 1.5rem;
    backdrop-filter: blur(8px);
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    height: 100%;
}

.game-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    border-color: rgba(0, 243, 255, 0.3);
}

.game-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.game-card h2 {
    color: var(--text-primary);
    margin: 0 0 0.5rem;
    font-family: var(--font-primary);
    font-size: 1.5rem;
}

.game-tagline {
    color: var(--text-secondary);
    margin-bottom: 1rem;
    flex-grow: 1;
    font-size: 0.95rem;
    line-height: 1.5;
}

.game-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 1rem 0;
    font-size: 0.85rem;
}

.difficulty {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

.difficulty-easy {
    background: rgba(0, 184, 148, 0.2);
    color: #00b894;
}

.difficulty-medium {
    background: rgba(253, 203, 110, 0.2);
    color: #fdcb6e;
}

.difficulty-hard {
    background: rgba(255, 71, 87, 0.2);
    color: #ff4757;
}

.recommended {
    color: var(--neon-blue);
    font-size: 0.8rem;
}

.btn {
    display: inline-block;
    padding: 0.6rem 1.25rem;
    border-radius: 6px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition);
    font-family: var(--font-primary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
    border: 2px solid transparent;
}

.btn-primary {
    background: var(--neon-blue);
    color: #0a0a10;
    border-color: var(--neon-blue);
}

.btn-primary:hover {
    background: transparent;
    color: var(--neon-blue);
    box-shadow: 0 0 15px rgba(0, 243, 255, 0.5);
}

@media (max-width: 768px) {
    .games-container {
        padding: 1rem;
    }
    
    .games-grid {
        grid-template-columns: 1fr;
    }
}
</style>
    4 => ['strategy-realms', 'puzzle-master'],
];

$featuredGames = [];
if ($houseId && isset($houseGamesMap[$houseId])) {
    $slugs = $houseGamesMap[$houseId];
    foreach ($allGames as $game) {
        if (in_array($game['slug'], $slugs, true)) {
            $featuredGames[] = $game;
        }
    }
} else {
    // Fallback: first 2 games as featured
    $featuredGames = array_slice($allGames, 0, 2);
}
?>


<style>
.games-container {
    padding: 2rem;
}

.games-header h1 {
    color: #fff;
    margin-bottom: 0.25rem;
}

.games-header p {
    color: #b8c2cc;
}

.games-section {
    margin-top: 2rem;
}

.games-section h2 {
    color: #fff;
    margin-bottom: 1rem;
}

.games-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
}

.game-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(8px);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.game-card-body {
    padding: 1.5rem 1.5rem 1rem;
}

.game-card-footer {
    padding: 0 1.5rem 1.25rem;
}

.game-card h3 {
    color: #fff;
    margin-bottom: 0.5rem;
}

.game-card p {
    color: #b8c2cc;
}

.game-card--secondary {
    opacity: 0.9;
}

@media (max-width: 768px) {
    .games-container {
        padding: 1.5rem 1rem;
    }
}
</style>

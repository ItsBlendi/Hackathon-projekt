// Shared game functions
function saveScore(score, xp) {
    const gameSlug = document.getElementById('game-root').dataset.game;
    
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
            // Update XP display if needed
            const xpElement = document.getElementById('xp-value');
            if (xpElement) {
                xpElement.textContent = data.new_total_xp || xp;
            }
            
            // Show score in the UI
            const scoreElement = document.getElementById('score-value');
            if (scoreElement) {
                scoreElement.textContent = score;
            }
            
            // Show the results section
            const meta = document.getElementById('play-meta');
            const actions = document.getElementById('play-actions');
            if (meta) meta.style.display = 'flex';
            if (actions) actions.style.display = 'flex';
        } else {
            console.error('Failed to save score:', data.message);
        }
    })
    .catch(error => {
        console.error('Error saving score:', error);
    });
}

// Helper function to create DOM elements
function createElement(tag, className, text = '') {
    const el = document.createElement(tag);
    if (className) el.className = className;
    if (text) el.textContent = text;
    return el;
}

// Initialize game with common setup
function initGame(container, gameSlug) {
    // Clear previous game
    container.innerHTML = '';
    
    // Create a wrapper for the game
    const wrapper = document.createElement('div');
    wrapper.className = `game-wrapper ${gameSlug}`;
    container.appendChild(wrapper);
    
    return wrapper;
}

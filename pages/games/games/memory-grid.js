// Memory Grid Game
class MemoryGrid {
    constructor(container) {
        this.container = container;
        this.gridSize = 4; // 4x4 grid
        this.cards = [];
        this.flippedCards = [];
        this.moves = 0;
        this.matchesFound = 0;
        this.gameActive = false;
        this.timer = null;
        this.secondsElapsed = 0;
    }
    
    // Initialize the game (required by the game loader)
    init() {
        this.initUI();
        this.setupEventListeners();
        return this; // Allow method chaining
    }
    
    // Array of emoji pairs for the memory game
    emojiPairs = ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁', '🐮', '🐷', '🐸', '🐵', '🐔'];
    
    initUI() {
        this.container.innerHTML = `
            <div class="memory-game">
                <div class="game-header">
                    <div>Moves: <span id="moves">0</span></div>
                    <div>Time: <span id="time">0</span>s</div>
                    <div>Matches: <span id="matches">0</span>/8</div>
                    <button id="start-btn" class="btn btn-primary">Start Game</button>
                </div>
                <div id="game-board" class="game-board"></div>
                <div id="game-over" style="display: none; text-align: center; margin-top: 20px;">
                    <h2>Congratulations!</h2>
                    <p>You won in <span id="final-moves">0</span> moves and <span id="final-time">0</span> seconds!</p>
                    <button id="play-again-btn" class="btn btn-primary">Play Again</button>
                </div>
            </div>
            <style>
                .memory-game {
                    max-width: 600px;
                    margin: 0 auto;
                    text-align: center;
                }
                .game-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 1rem;
                    font-size: 1.1rem;
                    color: var(--text-primary);
                    flex-wrap: wrap;
                    gap: 1rem;
                }
                .game-board {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 10px;
                    max-width: 500px;
                    margin: 0 auto;
                }
                .card {
                    aspect-ratio: 1;
                    background: #2d3748;
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 2rem;
                    cursor: pointer;
                    transform-style: preserve-3d;
                    transition: all 0.5s ease;
                    position: relative;
                }
                .card.flipped {
                    transform: rotateY(180deg);
                    background: #4a5568;
                }
                .card.matched {
                    transform: rotateY(180deg);
                    background: #2f855a;
                    cursor: default;
                }
                .card .front,
                .card .back {
                    position: absolute;
                    backface-visibility: hidden;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 100%;
                    height: 100%;
                    border-radius: 8px;
                }
                .card .back {
                    transform: rotateY(180deg);
                }
                @media (max-width: 600px) {
                    .game-board {
                        grid-template-columns: repeat(4, 1fr);
                        gap: 8px;
                        padding: 0 10px;
                    }
                    .game-header {
                        flex-direction: column;
                        gap: 0.5rem;
                    }
                }
            </style>
        `;
        
        // Cache DOM elements
        this.gameBoard = document.getElementById('game-board');
        this.movesEl = document.getElementById('moves');
        this.timeEl = document.getElementById('time');
        this.matchesEl = document.getElementById('matches');
        this.startBtn = document.getElementById('start-btn');
        this.gameOverEl = document.getElementById('game-over');
        this.finalMovesEl = document.getElementById('final-moves');
        this.finalTimeEl = document.getElementById('final-time');
    }
    
    setupEventListeners() {
        this.startBtn.addEventListener('click', () => this.startGame());
        document.getElementById('play-again-btn')?.addEventListener('click', () => this.startGame());
    }
    
    startGame() {
        // Reset game state
        this.cards = [];
        this.flippedCards = [];
        this.moves = 0;
        this.matchesFound = 0;
        this.secondsElapsed = 0;
        this.gameActive = true;
        
        // Clear previous game
        this.gameBoard.innerHTML = '';
        this.gameOverEl.style.display = 'none';
        
        this.createBoard();
        
        // Update UI
        this.updateUI();
        
        // Start timer
        if (this.timer) clearInterval(this.timer);
        this.timer = setInterval(() => {
            if (this.gameActive) {
                this.secondsElapsed++;
                this.timeEl.textContent = this.secondsElapsed;
            }
        }, 1000);
    }
    
    createBoard() {
        const gameBoard = document.getElementById('game-board');
        gameBoard.innerHTML = '';
        
        // Create pairs of emojis
        const pairsNeeded = (this.gridSize * this.gridSize) / 2;
        const emojis = this.emojiPairs.slice(0, pairsNeeded);
        const cardValues = [...emojis, ...emojis];
        
        // Shuffle the values
        this.shuffleArray(cardValues);
        
        // Create card elements with emoji values
        cardValues.forEach((emoji, index) => {
            const card = document.createElement('div');
            card.className = 'card';
            card.dataset.value = emoji;
            card.dataset.index = index;
            
            const cardInner = document.createElement('div');
            cardInner.className = 'card-inner';
            
            const cardFront = document.createElement('div');
            cardFront.className = 'card-front';
            cardFront.textContent = '?';
            
            const cardBack = document.createElement('div');
            cardBack.className = 'card-back';
            cardBack.textContent = emoji;
            
            cardInner.appendChild(cardFront);
            cardInner.appendChild(cardBack);
            card.appendChild(cardInner);
            
            gameBoard.appendChild(card);
            
            card.addEventListener('click', () => this.flipCard(card));
        });
        
        // Update grid template columns based on grid size
        gameBoard.style.gridTemplateColumns = `repeat(${this.gridSize}, 1fr)`;
        
        // Add card styles if not already added
        if (!document.getElementById('memory-card-styles')) {
            const style = document.createElement('style');
            style.id = 'memory-card-styles';
            style.textContent = `
                .card {
                    aspect-ratio: 1;
                    perspective: 1000px;
                    cursor: pointer;
                    padding: 5px;
                }
                .card-inner {
                    position: relative;
                    width: 100%;
                    height: 100%;
                    text-align: center;
                    transition: transform 0.6s;
                    transform-style: preserve-3d;
                }
                .card.flipped .card-inner {
                    transform: rotateY(180deg);
                }
                .card.matched {
                    opacity: 0.6;
                    pointer-events: none;
                }
                .card-front, .card-back {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    backface-visibility: hidden;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 8px;
                    font-size: 2rem;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                }
                .card-front {
                    background: #4a4a4a;
                    color: white;
                }
                .card-back {
                    background: #f0f0f0;
                    color: #333;
                    transform: rotateY(180deg);
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    flipCard(card) {
        // If already flipped or already matched, do nothing
        if (card.classList.contains('flipped') || card.classList.contains('matched')) {
            return;
        }
        
        // If two cards are already flipped, don't allow more flips
        if (this.flippedCards.length >= 2) {
            return;
        }
        
        // Add flip animation class
        card.classList.add('flipping');
        
        // After a short delay, add the flipped class to show the emoji
        setTimeout(() => {
            card.classList.add('flipped');
            card.classList.remove('flipping');
            this.flippedCards.push(card);
            
            // Check for match if two cards are flipped
            if (this.flippedCards.length === 2) {
                this.checkForMatch();
            }
        }, 100);
    }
    
    checkForMatch() {
        if (this.flippedCards[0].dataset.value === this.flippedCards[1].dataset.value) {
            // Match found
            this.matchesFound++;
            this.moves++;
            this.matchesEl.textContent = this.matchesFound;
            this.movesEl.textContent = this.moves;
            
            // Mark as matched
            this.flippedCards[0].classList.add('matched');
            this.flippedCards[1].classList.add('matched');
            
            // Clear flipped cards
            this.flippedCards = [];
            
            // Check for win
            if (this.matchesFound === (this.gridSize * this.gridSize) / 2) {
                this.gameWon();
            }
        } else {
            // No match, flip back after delay
            this.moves++;
            this.movesEl.textContent = this.moves;
            
            setTimeout(() => {
                this.flippedCards.forEach(card => {
                    card.classList.remove('flipped');
                });
                this.flippedCards = [];
            }, 1000);
        }
    }
    
    // flipCard method is already defined above, remove this duplicate
    
    async gameWon() {
        this.gameActive = false;
        clearInterval(this.timer);
        
        // Show game over screen
        this.finalMovesEl.textContent = this.moves;
        this.finalTimeEl.textContent = this.secondsElapsed;
        this.gameOverEl.style.display = 'block';
        
        // Calculate score based on performance (more points for fewer moves and less time)
        const timeScore = Math.max(10, 100 - Math.floor(this.secondsElapsed / 2));
        const movesScore = Math.max(10, 100 - this.moves);
        const totalScore = Math.floor((timeScore + movesScore) * 0.5);
        
        // Save score to server
        try {
            const response = await fetch('/GameVerse/Hackathon-projekt/pages/games/save_score.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    game: 'memory-grid',
                    score: totalScore
                })
            });

            const result = await response.json();
            
            if (result.success) {
                // Show XP earned notification
                const xpNotification = document.createElement('div');
                xpNotification.className = 'alert alert-success mt-3';
                xpNotification.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="me-2">🎉</div>
                        <div>
                            <strong>+${result.xp_earned} XP Earned!</strong>
                            <div class="small">Level ${result.level} (${result.new_xp % 100}/100 XP to next level)</div>
                        </div>
                    </div>
                `;
                this.gameOverEl.appendChild(xpNotification);
                
                // Update high score display if needed
                if (result.new_high_score) {
                    const highScoreNote = document.createElement('div');
                    highScoreNote.className = 'alert alert-info mt-2';
                    highScoreNote.innerHTML = '🏆 <strong>New High Score!</strong>';
                    this.gameOverEl.insertBefore(highScoreNote, xpNotification);
                }
            }
        } catch (error) {
            console.error('Error saving score:', error);
            // Still show the game over screen even if score save fails
        }
    }
    
    updateUI() {
        this.movesEl.textContent = this.moves;
        this.timeEl.textContent = this.secondsElapsed;
        this.matchesEl.textContent = this.matchesFound;
    }
    
    shuffleArray(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
        return array;
    }
}

// Make the game class available globally
if (typeof window !== 'undefined') {
    window.MemoryGrid = MemoryGrid;
}

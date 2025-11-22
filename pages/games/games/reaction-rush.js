// Reaction Rush Game
class ReactionRush {
    constructor(container) {
        this.container = container;
        this.score = 0;
        this.gameTime = 0;
        this.gameActive = false;
        this.animationId = null;
        this.lastTime = 0;
        this.targets = [];
        this.targetSize = 60;
        this.clickedTargets = 0;
        this.missedTargets = 0;
        this.maxMisses = 3;
        this.totalTargets = 10; // Total targets per round
        this.currentTarget = 0; // Current target in sequence
        this.spawnDelay = 900; // Starting delay in ms
        this.minSpawnDelay = 150; // Minimum delay between targets
        this.decreaseAmount = 120; // How much to decrease delay each target
    }

    // Initialize the game (required by the game loader)
    init() {
        this.initUI();
        this.setupEventListeners();
        this.resizeGame();
        window.addEventListener('resize', this.resizeGame.bind(this));
        return this;
    }

    initUI() {
        this.container.innerHTML = `
            <div class="reaction-game">
                <div class="game-header">
                    <div>Score: <span id="score">0</span></div>
                    <div>Time: <span id="time">0</span>s</div>
                    <div>Target: <span id="target-count">0/${this.totalTargets}</span></div>
                    <div>Misses: <span id="misses">0/${this.maxMisses}</span></div>
                    <button id="start-btn" class="btn btn-primary">Start Game</button>
                </div>
                <div class="game-area" id="game-area">
                    <div id="target" style="display: none;"></div>
                </div>
                <div id="game-over" style="display: none; text-align: center; margin-top: 20px;">
                    <h2>Round Complete!</h2>
                    <p>Your score: <span id="final-score">0</span></p>
                    <p>Reaction Time: <span id="avg-reaction">0</span>ms</p>
                    <p>Accuracy: <span id="accuracy">0%</span></p>
                    <button id="restart-btn" class="btn btn-primary">Play Again</button>
                </div>
            </div>
            <style>
                .reaction-game {
                    max-width: 800px;
                    margin: 0 auto;
                    text-align: center;
                    padding: 1rem;
                    color: #fff;
                }
                .game-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 1rem;
                    font-size: 1.1rem;
                    color: #fff;
                    padding: 0.5rem;
                    background: rgba(0, 0, 0, 0.7);
                    border-radius: 8px;
                    flex-wrap: wrap;
                    gap: 0.5rem;
                }
                .game-area {
                    position: relative;
                    width: 100%;
                    height: 500px;
                    background: #000;
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 0 auto;
                    cursor: crosshair;
                    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
                }
                .target {
                    position: absolute;
                    width: ${this.targetSize}px;
                    height: ${this.targetSize}px;
                    background: #4CAF50;
                    border-radius: 50%;
                    transform: translate(-50%, -50%);
                    cursor: pointer;
                    transition: all 0.2s ease;
                    box-shadow: 0 0 15px rgba(76, 175, 80, 0.8);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-weight: bold;
                    font-size: 1.2rem;
                    z-index: 10;
                }
                .target:hover {
                    transform: translate(-50%, -50%) scale(1.1);
                    box-shadow: 0 0 25px rgba(76, 175, 80, 1);
                }
                .target:active {
                    transform: translate(-50%, -50%) scale(0.95);
                }
                .target.pulse {
                    animation: pulse 1s infinite;
                }
                @keyframes pulse {
                    0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7); }
                    70% { box-shadow: 0 0 0 15px rgba(76, 175, 80, 0); }
                    100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
                }
                #game-over {
                    margin-top: 1rem;
                    background: rgba(0, 0, 0, 0.8);
                    padding: 1.5rem;
                    border-radius: 8px;
                    color: #fff;
                }
                .btn {
                    padding: 0.5rem 1.5rem;
                    border: none;
                    border-radius: 4px;
                    font-size: 1rem;
                    cursor: pointer;
                    transition: all 0.2s;
                    font-weight: bold;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .btn-primary {
                    background: #4CAF50;
                    color: white;
                }
                .btn-primary:hover {
                    background: #45a049;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                }
                .btn-primary:active {
                    transform: translateY(0);
                    box-shadow: none;
                }
            </style>
        `;

        // Cache DOM elements
        this.gameArea = document.getElementById('game-area');
        this.scoreEl = document.getElementById('score');
        this.timeEl = document.getElementById('time');
        this.targetCountEl = document.getElementById('target-count');
        this.missesEl = document.getElementById('misses');
        this.gameOverEl = document.getElementById('game-over');
        this.finalScoreEl = document.getElementById('final-score');
        this.avgReactionEl = document.getElementById('avg-reaction');
        this.accuracyEl = document.getElementById('accuracy');
        this.reactionTimes = [];
    }

    setupEventListeners() {
        // Start button
        document.getElementById('start-btn').addEventListener('click', () => {
            this.startGame();
        });

        // Restart button
        document.getElementById('restart-btn')?.addEventListener('click', () => {
            this.startGame();
        });

        // Click handler for targets (using event delegation)
        this.gameArea.addEventListener('click', (e) => {
            if (!this.gameActive || !e.target.classList.contains('target')) return;
            
            // Target was clicked
            this.handleTargetClick(e.target);
        });
    }

    resizeGame() {
        // Update game area dimensions
        this.gameArea.style.height = `${Math.min(600, window.innerHeight - 200)}px`;
    }

    startGame() {
        // Reset game state
        this.score = 0;
        this.gameTime = 0;
        this.targets = [];
        this.gameActive = true;
        this.clickedTargets = 0;
        this.missedTargets = 0;
        this.currentTarget = 0;
        this.spawnDelay = 1500;
        this.reactionTimes = [];

        // Clear any existing targets
        document.querySelectorAll('.target').forEach(target => target.remove());

        // Update UI
        this.scoreEl.textContent = this.score;
        this.timeEl.textContent = '0';
        this.targetCountEl.textContent = `0/${this.totalTargets}`;
        this.missesEl.textContent = `0/${this.maxMisses}`;
        this.gameOverEl.style.display = 'none';
        document.querySelector('#start-btn').style.display = 'none';

        // Start game loop
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
        this.lastTime = performance.now();
        
        // Start the sequence
        this.startSequence();
    }

    startSequence() {
        if (this.currentTarget >= this.totalTargets || !this.gameActive) {
            this.gameOver();
            return;
        }

        // Spawn the next target after a delay
        setTimeout(() => {
            if (this.gameActive) {
                this.spawnTarget();
            }
        }, this.spawnDelay);
    }

    gameLoop(timestamp) {
        if (!this.gameActive) return;

        const deltaTime = timestamp - this.lastTime;
        this.lastTime = timestamp;

        // Update game time
        this.gameTime += deltaTime / 1000; // Convert to seconds
        this.timeEl.textContent = this.gameTime.toFixed(2);

        // Continue the game loop
        this.animationId = requestAnimationFrame((ts) => this.gameLoop(ts));
    }

    spawnTarget() {
        if (!this.gameActive) return;
        
        this.currentTarget++;
        this.targetCountEl.textContent = `${this.currentTarget}/${this.totalTargets}`;
        
        // Create target element
        const target = document.createElement('div');
        target.className = 'target pulse';
        target.textContent = this.currentTarget;
        
        // Random position within game area (with padding)
        const padding = this.targetSize;
        const x = padding + Math.random() * (this.gameArea.offsetWidth - padding * 2);
        const y = padding + Math.random() * (this.gameArea.offsetHeight - padding * 2);
        
        target.style.left = `${x}px`;
        target.style.top = `${y}px`;
        
        // Random size variation
        const size = this.targetSize * (0.8 + Math.random() * 0.4);
        target.style.width = `${size}px`;
        target.style.height = `${size}px`;
        
        // Add to DOM
        this.gameArea.appendChild(target);
        
        // Store target data
        const targetData = {
            element: target,
            x: x,
            y: y,
            size: size,
            createdAt: performance.now(),
            clicked: false
        };
        
        this.targets.push(targetData);
        
        // Auto-remove after time based on difficulty
        const timeToClick = Math.max(800, 2000 - (this.currentTarget * 150)); // Gets shorter with each target
        this.targetTimeout = setTimeout(() => {
            if (target.parentNode && !targetData.clicked) {
                this.handleMissedTarget(target, targetData);
            }
        }, timeToClick);
    }

    handleTargetClick(targetElement) {
        const targetData = this.targets.find(t => t.element === targetElement);
        if (!targetData || targetData.clicked) return;
        
        // Mark as clicked
        targetData.clicked = true;
        this.clickedTargets++;
        
        // Calculate reaction time
        const reactionTime = performance.now() - targetData.createdAt;
        this.reactionTimes.push(reactionTime);
        
        // Calculate score based on reaction time (faster = more points)
        const points = Math.max(10, Math.floor(5000 / (reactionTime + 100)));
        
        // Update score
        this.score += points;
        this.scoreEl.textContent = this.score;
        
        // Visual feedback
        targetElement.style.background = '#45a049';
        targetElement.style.transform = 'translate(-50%, -50%) scale(1.2)';
        targetElement.classList.remove('pulse');
        
        // Remove target after animation
        setTimeout(() => {
            const index = this.targets.findIndex(t => t.element === targetElement);
            if (index !== -1) {
                this.targets.splice(index, 1);
            }
            targetElement.remove();
            
            // Start next target in sequence
            if (this.gameActive) {
                // Decrease spawn delay for next target (increased difficulty)
                this.spawnDelay = Math.max(this.minSpawnDelay, this.spawnDelay - this.decreaseAmount);
                this.startSequence();
            }
        }, 200);
    }

    handleMissedTarget(targetElement, targetData) {
        if (!this.gameActive || !targetElement.parentNode || targetData.clicked) return;
        
        this.missedTargets++;
        this.missesEl.textContent = `${this.missedTargets}/${this.maxMisses}`;
        
        // Visual feedback for missed target
        targetElement.style.background = '#f44336';
        targetElement.classList.remove('pulse');
        
        // Remove target after delay
        setTimeout(() => {
            if (targetElement.parentNode) {
                const index = this.targets.findIndex(t => t.element === targetElement);
                if (index !== -1) {
                    this.targets.splice(index, 1);
                }
                targetElement.remove();
            }
        }, 500);
        
        if (this.missedTargets >= this.maxMisses) {
            this.gameOver();
        } else if (this.gameActive) {
            // Continue with next target even after a miss
            this.startSequence();
        }
    }

    gameOver() {
        this.gameActive = false;
        if (this.targetTimeout) clearTimeout(this.targetTimeout);
        cancelAnimationFrame(this.animationId);
        
        // Calculate stats
        const totalTargets = this.clickedTargets + this.missedTargets;
        const accuracy = totalTargets > 0 
            ? Math.round((this.clickedTargets / totalTargets) * 100) 
            : 0;
        
        const avgReaction = this.reactionTimes.length > 0
            ? Math.round(this.reactionTimes.reduce((a, b) => a + b, 0) / this.reactionTimes.length)
            : 0;
        
        // Update game over screen
        this.finalScoreEl.textContent = this.score;
        this.avgReactionEl.textContent = avgReaction;
        this.accuracyEl.textContent = `${accuracy}%`;
        this.gameOverEl.style.display = 'block';
        document.querySelector('#start-btn').style.display = 'inline-block';

        // Calculate XP based on score (1 XP per 10 points, max 100)
        const xp = Math.min(100, Math.floor(this.score / 10));

        // Save score
        if (typeof saveScore === 'function') {
            saveScore(this.score, xp);
        }
    }
}

// Make the game class available globally
if (typeof window !== 'undefined') {
    window.ReactionRush = ReactionRush;
}
// Flappy Bird Game
class FlappyBird {
    constructor(container) {
        this.container = container;
        this.gameActive = false;
        this.animationId = null;
        this.gravity = 0.4;
        this.jumpForce = -8;
        this.pipes = [];
        this.pipeGap = 200;
        this.pipeFrequency = 2000; // ms
        this.lastPipeTime = 0;
        this.score = 0;
        this.highScore = localStorage.getItem('flappyBirdHighScore') || 0;
        this.bird = {
            x: 100,
            y: 200,
            width: 40,
            height: 30,
            velocity: 0
        };
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
            <div class="flappy-game">
                <div class="game-header">
                    <div>Score: <span id="score">0</span></div>
                    <div>High Score: <span id="high-score">${this.highScore}</span></div>
                    <button id="start-btn" class="btn btn-primary">Start Game</button>
                </div>
                <div class="game-area" id="game-area">
                    <div id="bird"></div>
                </div>
                <div id="game-over" style="display: none; text-align: center; margin-top: 20px;">
                    <h2>Game Over!</h2>
                    <p>Your score: <span id="final-score">0</span></p>
                    <p>High score: <span id="final-high-score">${this.highScore}</span></p>
                    <button id="restart-btn" class="btn btn-primary">Play Again</button>
                </div>
            </div>
            <style>
                .flappy-game {
                    max-width: 400px;
                    margin: 0 auto;
                    text-align: center;
                    padding: 1rem;
                    color: #333;
                }
                .game-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 1rem;
                    font-size: 1.1rem;
                    padding: 0.5rem;
                    background: #4CAF50;
                    color: white;
                    border-radius: 8px;
                    flex-wrap: wrap;
                    gap: 0.5rem;
                }
                .game-area {
                    position: relative;
                    width: 100%;
                    height: 500px;
                    background: #87CEEB;
                    border-radius: 8px;
                    overflow: hidden;
                    margin: 0 auto;
                    cursor: pointer;
                    box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
                }
                #bird {
                    position: absolute;
                    width: 40px;
                    height: 30px;
                    background: #FFD700;
                    border-radius: 50% 50% 60% 60%;
                    left: 100px;
                    top: 200px;
                    z-index: 10;
                    transition: transform 0.1s;
                }
                #bird:before {
                    content: '';
                    position: absolute;
                    width: 12px;
                    height: 12px;
                    background: #FF4500;
                    border-radius: 50%;
                    top: 8px;
                    right: -4px;
                    transform: rotate(45deg);
                }
                #bird:after {
                    content: '';
                    position: absolute;
                    width: 8px;
                    height: 8px;
                    background: white;
                    border-radius: 50%;
                    top: 10px;
                    right: 20px;
                }
                .pipe {
                    position: absolute;
                    width: 60px;
                    background: #4CAF50;
                    z-index: 5;
                }
                .pipe-top {
                    top: 0;
                    border-radius: 0 0 8px 8px;
                }
                .pipe-bottom {
                    bottom: 0;
                    border-radius: 8px 8px 0 0;
                }
                .score-point {
                    position: absolute;
                    width: 10px;
                    height: 100%;
                    background: transparent;
                    z-index: 6;
                }
                #game-over {
                    margin-top: 1rem;
                    background: rgba(0, 0, 0, 0.8);
                    padding: 1.5rem;
                    border-radius: 8px;
                    color: white;
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
                    background: #FFD700;
                    color: #333;
                }
                .btn-primary:hover {
                    background: #FFC400;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                }
                .btn-primary:active {
                    transform: translateY(0);
                    box-shadow: none;
                }
                .ground {
                    position: absolute;
                    bottom: 0;
                    width: 100%;
                    height: 20px;
                    background: #8B4513;
                    z-index: 7;
                }
                .cloud {
                    position: absolute;
                    background: white;
                    border-radius: 50%;
                    opacity: 0.8;
                }
            </style>
        `;

        // Cache DOM elements
        this.gameArea = document.getElementById('game-area');
        this.scoreEl = document.getElementById('score');
        this.highScoreEl = document.getElementById('high-score');
        this.finalScoreEl = document.getElementById('final-score');
        this.finalHighScoreEl = document.getElementById('final-high-score');
        this.gameOverEl = document.getElementById('game-over');
        this.birdEl = document.getElementById('bird');
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

        // Click/tap to jump
        this.gameArea.addEventListener('click', () => {
            if (this.gameActive) {
                this.bird.velocity = this.jumpForce;
                this.birdEl.style.transform = 'rotate(-30deg)';
                setTimeout(() => {
                    if (this.gameActive) {
                        this.birdEl.style.transform = 'rotate(0)';
                    }
                }, 200);
            }
        });

        // Space/up arrow to jump
        document.addEventListener('keydown', (e) => {
            if ((e.code === 'Space' || e.key === 'ArrowUp') && this.gameActive) {
                e.preventDefault();
                this.bird.velocity = this.jumpForce;
                this.birdEl.style.transform = 'rotate(-30deg)';
                setTimeout(() => {
                    if (this.gameActive) {
                        this.birdEl.style.transform = 'rotate(0)';
                    }
                }, 200);
            }
        });
    }

    resizeGame() {
        // Update game area dimensions
        this.gameArea.style.height = `${Math.min(500, window.innerHeight - 200)}px`;
    }

    startGame() {
        // Reset game state
        this.gameActive = true;
        this.score = 0;
        this.pipes = [];
        this.bird = {
            x: 100,
            y: this.gameArea.offsetHeight / 2 - 15,
            width: 40,
            height: 30,
            velocity: 0
        };
        this.lastPipeTime = 0;

        // Update UI
        this.scoreEl.textContent = this.score;
        this.gameOverEl.style.display = 'none';
        document.querySelector('#start-btn').style.display = 'none';

        // Clear any existing pipes
        document.querySelectorAll('.pipe, .score-point').forEach(el => el.remove());

        // Reset bird position
        this.birdEl.style.top = `${this.bird.y}px`;
        this.birdEl.style.transform = 'rotate(0)';

        // Start game loop
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
        this.lastTime = performance.now();
        this.gameLoop(this.lastTime);
    }

    gameLoop(timestamp) {
        if (!this.gameActive) return;

        const deltaTime = timestamp - this.lastTime;
        this.lastTime = timestamp;

        // Update game state
        this.update(deltaTime);
        
        // Render
        this.render();

        // Continue the game loop
        this.animationId = requestAnimationFrame((ts) => this.gameLoop(ts));
    }

    update(deltaTime) {
        if (!this.gameActive) return;

        // Update bird
        this.bird.velocity += this.gravity;
        this.bird.y += this.bird.velocity;

        // Keep bird in bounds
        if (this.bird.y < 0) {
            this.bird.y = 0;
            this.bird.velocity = 0;
        }

        if (this.bird.y > this.gameArea.offsetHeight - this.bird.height - 20) { // 20 is ground height
            this.gameOver();
            return;
        }

        // Spawn pipes
        if (performance.now() - this.lastPipeTime > this.pipeFrequency) {
            this.spawnPipes();
            this.lastPipeTime = performance.now();
        }

        // Update pipes
        for (let i = this.pipes.length - 1; i >= 0; i--) {
            const pipe = this.pipes[i];
            pipe.x -= 2; // Move pipe to the left

            // Check collision with bird
            if (this.checkCollision(this.bird, pipe)) {
                this.gameOver();
                return;
            }

            // Check if bird passed the pipe
            if (!pipe.passed && pipe.x + pipe.width < this.bird.x) {
                pipe.passed = true;
                this.score++;
                this.scoreEl.textContent = this.score;
            }

            // Remove pipes that are off screen
            if (pipe.x + pipe.width < 0) {
                this.pipes.splice(i, 1);
                document.getElementById(`pipe-${pipe.id}`)?.remove();
                document.getElementById(`score-${pipe.id}`)?.remove();
            }
        }
    }

    spawnPipes() {
        const gapPosition = Math.random() * (this.gameArea.offsetHeight - this.pipeGap - 100) + 50;
        const pipeWidth = 60;
        const pipeId = Date.now();

        // Top pipe
        const topPipe = {
            id: pipeId,
            x: this.gameArea.offsetWidth,
            y: 0,
            width: pipeWidth,
            height: gapPosition,
            type: 'top',
            passed: false
        };

        // Bottom pipe
        const bottomPipe = {
            id: pipeId,
            x: this.gameArea.offsetWidth,
            y: gapPosition + this.pipeGap,
            width: pipeWidth,
            height: this.gameArea.offsetHeight - gapPosition - this.pipeGap - 20, // 20 is ground height
            type: 'bottom',
            passed: false
        };

        // Score point (invisible area between pipes)
        const scorePoint = {
            id: pipeId,
            x: this.gameArea.offsetWidth + pipeWidth,
            y: gapPosition,
            width: 10,
            height: this.pipeGap,
            type: 'score',
            passed: false
        };

        this.pipes.push(topPipe, bottomPipe, scorePoint);

        // Create pipe elements
        this.createPipeElement(topPipe);
        this.createPipeElement(bottomPipe);
        this.createScoreElement(scorePoint);
    }

    createPipeElement(pipe) {
        const pipeEl = document.createElement('div');
        pipeEl.className = `pipe pipe-${pipe.type}`;
        pipeEl.id = `pipe-${pipe.id}-${pipe.type}`;
        pipeEl.style.width = `${pipe.width}px`;
        pipeEl.style.height = `${pipe.height}px`;
        pipeEl.style.left = `${pipe.x}px`;
        
        if (pipe.type === 'top') {
            pipeEl.style.top = '0';
        } else {
            pipeEl.style.bottom = '20px'; // 20 is ground height
        }
        
        this.gameArea.appendChild(pipeEl);
    }

    createScoreElement(score) {
        const scoreEl = document.createElement('div');
        scoreEl.className = 'score-point';
        scoreEl.id = `score-${score.id}`;
        scoreEl.style.left = `${score.x}px`;
        scoreEl.style.top = `${score.y}px`;
        scoreEl.style.height = `${score.height}px`;
        this.gameArea.appendChild(scoreEl);
    }

    checkCollision(bird, pipe) {
        if (pipe.type === 'score') return false;

        return bird.x < pipe.x + pipe.width &&
               bird.x + bird.width > pipe.x &&
               bird.y < pipe.y + pipe.height &&
               bird.y + bird.height > pipe.y;
    }

    render() {
        if (!this.gameActive) return;

        // Update bird position
        this.birdEl.style.top = `${this.bird.y}px`;

        // Update pipe positions
        this.pipes.forEach(pipe => {
            const pipeEl = document.getElementById(`pipe-${pipe.id}-${pipe.type}`);
            if (pipeEl) {
                pipeEl.style.left = `${pipe.x}px`;
            }
            
            const scoreEl = document.getElementById(`score-${pipe.id}`);
            if (scoreEl) {
                scoreEl.style.left = `${pipe.x + 60}px`; // 60 is pipe width
            }
        });
    }

    gameOver() {
        this.gameActive = false;
        cancelAnimationFrame(this.animationId);

        // Update high score
        if (this.score > this.highScore) {
            this.highScore = this.score;
            localStorage.setItem('flappyBirdHighScore', this.highScore);
            this.highScoreEl.textContent = this.highScore;
        }

        // Update game over screen
        this.finalScoreEl.textContent = this.score;
        this.finalHighScoreEl.textContent = this.highScore;
        this.gameOverEl.style.display = 'block';
        document.querySelector('#start-btn').style.display = 'inline-block';

        // Calculate XP (1 XP per 2 points, max 100)
        const xp = Math.min(100, Math.floor(this.score / 2));

        // Save score
        if (typeof saveScore === 'function') {
            saveScore(this.score, xp);
        }
    }
}

// Make the game class available globally
if (typeof window !== 'undefined') {
    window.FlappyBird = FlappyBird;
}
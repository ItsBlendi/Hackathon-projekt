// Dodge Squares Game
class DodgeSquares {
    constructor(container) {
        this.container = container;
        this.player = { 
            x: 0, 
            y: 0, 
            size: 30,
            speed: 8
        };
        this.enemies = [];
        this.score = 0;
        this.gameTime = 0;
        this.gameActive = false;
        this.animationId = null;
        this.lastTime = 0;
        this.enemySpawnRate = 1500; // ms
        this.lastSpawn = 0;
    }

    // Initialize the game (required by the game loader)
    init() {
        this.initUI();
        this.setupEventListeners();
        this.resizeCanvas();
        window.addEventListener('resize', this.resizeCanvas.bind(this));
        return this;
    }

    initUI() {
        this.container.innerHTML = `
            <div class="dodge-game">
                <div class="game-header">
                    <div>Score: <span id="score">0</span></div>
                    <div>Time: <span id="time">0</span>s</div>
                    <button id="start-btn" class="btn btn-primary">Start Game</button>
                </div>
                <div class="game-canvas-container">
                    <canvas id="game-canvas"></canvas>
                </div>
                <div id="game-over" style="display: none; text-align: center; margin-top: 20px;">
                    <h2>Game Over!</h2>
                    <p>Your score: <span id="final-score">0</span></p>
                    <button id="restart-btn" class="btn btn-primary">Play Again</button>
                </div>
            </div>
            <style>
                .dodge-game {
                    max-width: 600px;
                    margin: 0 auto;
                    text-align: center;
                    padding: 1rem;
                }
                .game-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 1rem;
                    font-size: 1.2rem;
                    color: var(--text-primary);
                    padding: 0 1rem;
                    flex-wrap: wrap;
                    gap: 1rem;
                }
                .game-canvas-container {
                    width: 100%;
                    max-width: 500px;
                    margin: 0 auto;
                    border-radius: 8px;
                    overflow: hidden;
                    background: #111;
                    aspect-ratio: 1/1;
                }
                #game-canvas {
                    width: 100%;
                    height: 100%;
                    display: block;
                    cursor: none;
                }
                #game-over {
                    margin-top: 1rem;
                }
            </style>
        `;

        // Cache DOM elements
        this.canvas = document.getElementById('game-canvas');
        this.ctx = this.canvas.getContext('2d');
        this.scoreEl = document.getElementById('score');
        this.timeEl = document.getElementById('time');
        this.gameOverEl = document.getElementById('game-over');
        this.finalScoreEl = document.getElementById('final-score');
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

        // Mouse movement
        this.canvas.addEventListener('mousemove', (e) => {
            if (!this.gameActive) return;
            const rect = this.canvas.getBoundingClientRect();
            this.player.x = e.clientX - rect.left - this.player.size / 2;
            this.player.x = Math.max(0, Math.min(this.player.x, this.canvas.width - this.player.size));
        });

        // Touch movement
        this.canvas.addEventListener('touchmove', (e) => {
            if (!this.gameActive) return;
            e.preventDefault();
            const touch = e.touches[0];
            const rect = this.canvas.getBoundingClientRect();
            this.player.x = touch.clientX - rect.left - this.player.size / 2;
            this.player.x = Math.max(0, Math.min(this.player.x, this.canvas.width - this.player.size));
        }, { passive: false });
    }

    resizeCanvas() {
        const container = this.canvas.parentElement;
        const size = Math.min(container.offsetWidth, 500); // Max 500px width
        this.canvas.width = size;
        this.canvas.height = size;
        
        // Reset player position
        this.player.y = this.canvas.height - this.player.size - 10;
        if (this.player.x === 0) {
            this.player.x = (this.canvas.width - this.player.size) / 2;
        }
    }

    startGame() {
        // Reset game state
        this.score = 0;
        this.gameTime = 0;
        this.enemies = [];
        this.gameActive = true;
        this.lastSpawn = 0;

        // Update UI
        this.scoreEl.textContent = this.score;
        this.timeEl.textContent = '0';
        this.gameOverEl.style.display = 'none';
        document.querySelector('#start-btn').style.display = 'none';

        // Set initial player position
        this.player.x = (this.canvas.width - this.player.size) / 2;
        this.player.y = this.canvas.height - this.player.size - 10;

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

        // Update game time
        this.gameTime += deltaTime / 1000; // Convert to seconds
        this.timeEl.textContent = Math.floor(this.gameTime);

        // Spawn new enemies
        if (this.gameTime * 1000 - this.lastSpawn > this.enemySpawnRate) {
            this.spawnEnemy();
            this.lastSpawn = this.gameTime * 1000;
            
            // Increase difficulty over time
            this.enemySpawnRate = Math.max(300, 1500 - Math.floor(this.gameTime) * 50);
        }

        // Update enemies
        for (let i = this.enemies.length - 1; i >= 0; i--) {
            const enemy = this.enemies[i];
            enemy.y += enemy.speed;

            // Check collision with player
            if (this.checkCollision(this.player, enemy)) {
                this.gameOver();
                return;
            }

            // Remove if off screen
            if (enemy.y > this.canvas.height) {
                this.enemies.splice(i, 1);
                this.score += 10;
                this.scoreEl.textContent = this.score;
            }
        }
    }

    spawnEnemy() {
        const size = Math.random() * 30 + 20;
        const enemy = {
            x: Math.random() * (this.canvas.width - size),
            y: -size,
            size: size,
            speed: 1 + Math.random() * 2 + Math.floor(this.gameTime / 10)
        };
        this.enemies.push(enemy);
    }

    checkCollision(rect1, rect2) {
        return rect1.x < rect2.x + rect2.size &&
               rect1.x + rect1.size > rect2.x &&
               rect1.y < rect2.y + rect2.size &&
               rect1.y + rect1.size > rect2.y;
    }

    render() {
        // Clear canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // Draw player
        this.ctx.fillStyle = '#4CAF50';
        this.ctx.fillRect(this.player.x, this.player.y, this.player.size, this.player.size);

        // Draw enemies
        this.ctx.fillStyle = '#F44336';
        this.enemies.forEach(enemy => {
            this.ctx.fillRect(enemy.x, enemy.y, enemy.size, enemy.size);
        });
    }

    gameOver() {
        this.gameActive = false;
        cancelAnimationFrame(this.animationId);

        // Show game over screen
        this.finalScoreEl.textContent = this.score;
        this.gameOverEl.style.display = 'block';
        document.querySelector('#start-btn').style.display = 'inline-block';

        // Calculate XP (1 XP per 10 seconds survived, max 100)
        const xp = Math.min(100, Math.floor(this.gameTime / 10));

        // Save score
        if (typeof saveScore === 'function') {
            saveScore(this.score, xp);
        }
    }
}

// Make the game class available globally
if (typeof window !== 'undefined') {
    window.DodgeSquares = DodgeSquares;
}
// Flappy Bird Game
class FlappyBird {
  constructor(container) {
    this.container = container
    this.gameActive = false
    this.animationId = null
    this.gravity = 0.4
    this.jumpForce = -8
    this.pipes = []
    this.pipeGap = 200
    this.pipeFrequency = 2000 // ms
    this.lastPipeTime = 0
    this.score = 0
    this.highScore = localStorage.getItem("flappyBirdHighScore") || 0

    this.initialGameWidth = 700 // Reduced from 1100 to 700
    this.minGameWidth = 500 // Reduced from 600 to 500
    this.currentGameWidth = this.initialGameWidth
    this.widthDecreaseAmount = 20
    this.gameArea = null

    this.bird = {
      x: 100,
      y: 200,
      width: 40,
      height: 30,
      velocity: 0,
    }
  }

  // Initialize the game (required by the game loader)
  init() {
    this.initUI()
    this.setupEventListeners()
    this.resizeGame()
    window.addEventListener("resize", this.resizeGame.bind(this))
    return this
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
                    max-width: 1200px;
                    width: 98%;
                    margin: 0 auto;
                    text-align: center;
                    padding: 1rem 0.5rem;
                    color: #333;
                }
                .game-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin: 0 auto 1rem auto;
                    width: 700px;
                    font-size: 1.1rem;
                    padding: 0.8rem 1.5rem;
                    background: #4CAF50;
                    color: white;
                    border-radius: 8px;
                    flex-wrap: wrap;
                    gap: 0.5rem;
                }
                .game-area {
                    position: relative;
                    width: 700px;
                    height: 600px;
                    transition: width 0.3s ease-out;
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
                    width: 80px;
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
        `

    // Cache DOM elements
    this.gameArea = document.getElementById("game-area")
    this.scoreEl = document.getElementById("score")
    this.highScoreEl = document.getElementById("high-score")
    this.finalScoreEl = document.getElementById("final-score")
    this.finalHighScoreEl = document.getElementById("final-high-score")
    this.gameOverEl = document.getElementById("game-over")
    this.birdEl = document.getElementById("bird")
  }

  setupEventListeners() {
    // Start button
    document.getElementById("start-btn").addEventListener("click", () => {
      this.startGame()
    })

    // Restart button
    document.getElementById("restart-btn")?.addEventListener("click", () => {
      this.startGame()
    })

    // Click/tap to jump
    this.gameArea.addEventListener("click", () => {
      if (this.gameActive) {
        this.bird.velocity = this.jumpForce
        this.birdEl.style.transform = "rotate(-30deg)"
        setTimeout(() => {
          if (this.gameActive) {
            this.birdEl.style.transform = "rotate(0)"
          }
        }, 200)
      }
    })

    // Space/up arrow to jump
    document.addEventListener("keydown", (e) => {
      if ((e.code === "Space" || e.key === "ArrowUp") && this.gameActive) {
        e.preventDefault()
        this.bird.velocity = this.jumpForce
        this.birdEl.style.transform = "rotate(-30deg)"
        setTimeout(() => {
          if (this.gameActive) {
            this.birdEl.style.transform = "rotate(0)"
          }
        }, 200)
      }
    })
  }

  resizeGame() {
    // Update game area dimensions
    this.gameArea.style.height = `${Math.min(500, window.innerHeight - 200)}px`
  }

  startGame() {
    // Reset game state
    this.gameActive = true
    this.score = 0
    this.pipes = []
    this.currentGameWidth = this.initialGameWidth // Reset to initial width
    this.gameArea.style.width = `${this.currentGameWidth}px` // Apply initial width
    this.bird = {
      x: 100,
      y: this.gameArea.offsetHeight / 2 - 15,
      width: 40,
      height: 30,
      velocity: 0,
    }
    this.lastPipeTime = 0

    // Update UI
    this.scoreEl.textContent = this.score
    this.gameOverEl.style.display = "none"
    document.querySelector("#start-btn").style.display = "none"

    // Clear any existing pipes
    document.querySelectorAll(".pipe, .score-point").forEach((el) => el.remove())

    // Reset bird position
    this.birdEl.style.top = `${this.bird.y}px`
    this.birdEl.style.transform = "rotate(0)"

    // Start game loop
    if (this.animationId) {
      cancelAnimationFrame(this.animationId)
    }
    this.lastTime = performance.now()
    this.gameLoop(this.lastTime)
  }

  gameLoop(timestamp) {
    if (!this.gameActive) return

    const deltaTime = timestamp - this.lastTime
    this.lastTime = timestamp

    // Update game state
    this.update(deltaTime)

    // Render
    this.render()

    // Continue the game loop
    this.animationId = requestAnimationFrame((ts) => this.gameLoop(ts))
  }

  update(deltaTime) {
    if (!this.gameActive) return

    // Check if bird passed any pipes and update width
    let scored = false
    this.pipes.forEach((pipe) => {
      if (!pipe.passed && pipe.type === "score" && pipe.x + pipe.width < this.bird.x) {
        pipe.passed = true
        this.score++
        this.scoreEl.textContent = this.score
        scored = true
      }
    })

    if (scored) {
      // Decrease game width (but not below minimum)
      if (this.currentGameWidth > this.minGameWidth) {
        this.currentGameWidth = Math.max(this.minGameWidth, this.currentGameWidth - this.widthDecreaseAmount)
        this.gameArea.style.width = `${this.currentGameWidth}px`

        // Visual feedback for width change
        this.gameArea.style.border = "4px solid #ffcc00"
        setTimeout(() => {
          if (this.gameActive) {
            this.gameArea.style.border = "none"
          }
        }, 200)
      }
    }

    // Update bird
    this.bird.velocity += this.gravity
    this.bird.y += this.bird.velocity

    // Keep bird in bounds
    if (this.bird.y < 0) {
      this.bird.y = 0
      this.bird.velocity = 0
    }

    if (this.bird.y > this.gameArea.offsetHeight - this.bird.height - 20) {
      // 20 is ground height
      this.gameOver()
      return
    }

    // Spawn pipes
    if (performance.now() - this.lastPipeTime > this.pipeFrequency) {
      this.spawnPipes()
      this.lastPipeTime = performance.now()
    }

    // Update pipes
    for (let i = this.pipes.length - 1; i >= 0; i--) {
      const pipe = this.pipes[i]
      pipe.x -= 2 // Move pipe to the left

      if (pipe.type !== "score" && this.checkCollision(this.bird, pipe)) {
        this.gameOver()
        return
      }

      // Remove pipes that are off screen
      if (pipe.x + pipe.width < 0) {
        this.pipes.splice(i, 1)
        document.getElementById(`pipe-${pipe.id}`)?.remove()
        document.getElementById(`score-${pipe.id}`)?.remove()
        document.getElementById(`pipe-${pipe.id}-${pipe.type}`)?.remove()
      }
    }
  }

  spawnPipes() {
    const gapPosition = Math.random() * (this.gameArea.offsetHeight - this.pipeGap - 100) + 50
    const pipeWidth = 60
    const pipeId = Date.now()

    // Top pipe
    const topPipe = {
      id: pipeId,
      x: this.currentGameWidth, // Use current game width for pipe position
      y: 0,
      width: pipeWidth,
      height: gapPosition,
      type: "top",
      passed: false,
    }

    // Bottom pipe
    const bottomPipe = {
      id: pipeId,
      x: this.currentGameWidth, // Use current game width for pipe position
      y: gapPosition + this.pipeGap,
      width: pipeWidth,
      height: this.gameArea.offsetHeight - gapPosition - this.pipeGap - 20, // 20 is ground height
      type: "bottom",
      passed: false,
    }

    // Score point (invisible area between pipes)
    const scorePoint = {
      id: pipeId,
      x: this.currentGameWidth + pipeWidth, // Use current game width for pipe position
      y: gapPosition,
      width: 10,
      height: this.pipeGap,
      type: "score",
      passed: false,
    }

    this.pipes.push(topPipe, bottomPipe, scorePoint)

    // Create pipe elements
    this.createPipeElement(topPipe)
    this.createPipeElement(bottomPipe)
    this.createScoreElement(scorePoint)
  }

  createPipeElement(pipe) {
    const pipeEl = document.createElement("div")
    pipeEl.className = `pipe pipe-${pipe.type}`
    pipeEl.id = `pipe-${pipe.id}-${pipe.type}`
    pipeEl.style.width = `${pipe.width}px`
    pipeEl.style.height = `${pipe.height}px`
    pipeEl.style.left = `${pipe.x}px`

    if (pipe.type === "top") {
      pipeEl.style.top = "0"
    } else {
      pipeEl.style.bottom = "20px" // 20 is ground height
    }

    this.gameArea.appendChild(pipeEl)
  }

  createScoreElement(score) {
    const scoreEl = document.createElement("div")
    scoreEl.className = "score-point"
    scoreEl.id = `score-${score.id}`
    scoreEl.style.left = `${score.x}px`
    scoreEl.style.top = `${score.y}px`
    scoreEl.style.height = `${score.height}px`
    this.gameArea.appendChild(scoreEl)
  }

  checkCollision(bird, pipe) {
    const margin = 5
    const birdRight = bird.x + bird.width - margin
    const birdBottom = bird.y + bird.height - margin
    const pipeRight = pipe.x + pipe.width
    const pipeBottom = pipe.y + pipe.height

    // Check if bird hits the sides of the game area
    if (birdRight > this.currentGameWidth || bird.x < 0) {
      return true
    }

    // Check if bird hits the pipes with adjusted margins
    return bird.x + margin < pipeRight && birdRight > pipe.x && bird.y + margin < pipeBottom && birdBottom > pipe.y
  }

  async gameOver() {
    this.gameActive = false
    cancelAnimationFrame(this.animationId)

    // Update high score
    if (this.score > this.highScore) {
      this.highScore = this.score
      localStorage.setItem("flappyBirdHighScore", this.highScore)
      this.highScoreEl.textContent = this.highScore
    }

    // Update game over screen
    this.finalScoreEl.textContent = this.score
    this.finalHighScoreEl.textContent = this.highScore
    this.gameOverEl.style.display = "block"
    document.querySelector("#start-btn").style.display = "inline-block"

    // Save score to server
    try {
      const response = await fetch("/GameVerse/Hackathon-projekt/pages/games/save_score.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          game: "flappy-bird",
          score: this.score,
        }),
      })

      const result = await response.json()

      if (result.success) {
        // Show XP earned notification
        const xpNotification = document.createElement("div")
        xpNotification.className = "alert alert-success mt-3"
        xpNotification.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="me-2">🎉</div>
                        <div>
                            <strong>+${result.xp_earned} XP Earned!</strong>
                            <div class="small">Level ${result.level} (${result.new_xp % 100}/100 XP to next level)</div>
                        </div>
                    </div>
                `
        this.gameOverEl.appendChild(xpNotification)

        // Update high score display if needed
        if (result.new_high_score) {
          const highScoreNote = document.createElement("div")
          highScoreNote.className = "alert alert-info mt-2"
          highScoreNote.innerHTML = "🏆 <strong>New High Score!</strong>"
          this.gameOverEl.insertBefore(highScoreNote, xpNotification)
        }
      }
    } catch (error) {
      console.error("Error saving score:", error)
      // Still show the game over screen even if score save fails
    }
  }
}

// Make the game class available globally
if (typeof window !== "undefined") {
  window.FlappyBird = FlappyBird
}

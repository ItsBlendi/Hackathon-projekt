// Memory Grid Game
class MemoryGrid {
  constructor(container) {
    this.container = container
    this.gridSize = 4
    this.cards = []
    this.flippedCards = []
    this.moves = 0
    this.matchesFound = 0
    this.gameActive = false
    this.timer = null
    this.secondsElapsed = 0
  }

  init() {
    this.initUI()
    this.setupEventListeners()
    return this
  }

  emojiPairs = ["🐶", "🐱", "🐭", "🐹", "🐰", "🦊", "🐻", "🐼", "🐨", "🐯", "🦁", "🐮", "🐷", "🐸", "🐵", "🐔"]

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
                    padding: 1rem;
                }
                .game-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 1.5rem;
                    font-size: 1.1rem;
                    padding: 1rem;
                    background: rgba(0, 243, 255, 0.1);
                    border-radius: 8px;
                    flex-wrap: wrap;
                    gap: 1rem;
                }
                .game-board {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 12px;
                    max-width: 500px;
                    margin: 0 auto;
                }
                .card {
                    aspect-ratio: 1;
                    perspective: 1000px;
                    cursor: pointer;
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
                .card.matched .card-back {
                    background: #2f855a;
                }
                .card-front, .card-back {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    backface-visibility: hidden;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 12px;
                    font-size: 2.5rem;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
                }
                .card-front {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    font-size: 2rem;
                    font-weight: bold;
                }
                .card-back {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                    transform: rotateY(180deg);
                }
                @media (max-width: 600px) {
                    .game-board {
                        gap: 8px;
                        padding: 0 10px;
                    }
                    .card-front, .card-back {
                        font-size: 2rem;
                    }
                }
            </style>
        `

    this.gameBoard = document.getElementById("game-board")
    this.movesEl = document.getElementById("moves")
    this.timeEl = document.getElementById("time")
    this.matchesEl = document.getElementById("matches")
    this.startBtn = document.getElementById("start-btn")
    this.gameOverEl = document.getElementById("game-over")
    this.finalMovesEl = document.getElementById("final-moves")
    this.finalTimeEl = document.getElementById("final-time")
  }

  setupEventListeners() {
    this.startBtn.addEventListener("click", () => this.startGame())
    document.getElementById("play-again-btn")?.addEventListener("click", () => this.startGame())
  }

  startGame() {
    this.cards = []
    this.flippedCards = []
    this.moves = 0
    this.matchesFound = 0
    this.secondsElapsed = 0
    this.gameActive = true

    this.gameBoard.innerHTML = ""
    this.gameOverEl.style.display = "none"

    this.createBoard()
    this.updateUI()

    if (this.timer) clearInterval(this.timer)
    this.timer = setInterval(() => {
      if (this.gameActive) {
        this.secondsElapsed++
        this.timeEl.textContent = this.secondsElapsed
      }
    }, 1000)
  }

  createBoard() {
    const pairsNeeded = (this.gridSize * this.gridSize) / 2
    const emojis = this.emojiPairs.slice(0, pairsNeeded)
    const cardValues = [...emojis, ...emojis]

    this.shuffleArray(cardValues)

    cardValues.forEach((emoji, index) => {
      const card = document.createElement("div")
      card.className = "card"
      card.dataset.value = emoji
      card.dataset.index = index

      const cardInner = document.createElement("div")
      cardInner.className = "card-inner"

      const cardFront = document.createElement("div")
      cardFront.className = "card-front"
      cardFront.textContent = "?"

      const cardBack = document.createElement("div")
      cardBack.className = "card-back"
      cardBack.textContent = emoji

      cardInner.appendChild(cardFront)
      cardInner.appendChild(cardBack)
      card.appendChild(cardInner)

      this.gameBoard.appendChild(card)

      card.addEventListener("click", () => this.flipCard(card))
    })
  }

  flipCard(card) {
    if (card.classList.contains("flipped") || card.classList.contains("matched") || this.flippedCards.length >= 2) {
      return
    }

    card.classList.add("flipped")
    this.flippedCards.push(card)

    if (this.flippedCards.length === 2) {
      setTimeout(() => this.checkForMatch(), 500)
    }
  }

  checkForMatch() {
    this.moves++
    this.movesEl.textContent = this.moves

    const [card1, card2] = this.flippedCards

    if (card1.dataset.value === card2.dataset.value) {
      this.matchesFound++
      this.matchesEl.textContent = this.matchesFound

      card1.classList.add("matched")
      card2.classList.add("matched")

      this.flippedCards = []

      if (this.matchesFound === (this.gridSize * this.gridSize) / 2) {
        setTimeout(() => this.gameWon(), 500)
      }
    } else {
      setTimeout(() => {
        card1.classList.remove("flipped")
        card2.classList.remove("flipped")
        this.flippedCards = []
      }, 1000)
    }
  }

  gameWon() {
    this.gameActive = false
    clearInterval(this.timer)

    this.finalMovesEl.textContent = this.moves
    this.finalTimeEl.textContent = this.secondsElapsed
    this.gameOverEl.style.display = "block"
  }

  updateUI() {
    this.movesEl.textContent = this.moves
    this.timeEl.textContent = this.secondsElapsed
    this.matchesEl.textContent = this.matchesFound
  }

  shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1))
      ;[array[i], array[j]] = [array[j], array[i]]
    }
    return array
  }
}

if (typeof window !== "undefined") {
  window.MemoryGrid = MemoryGrid
}

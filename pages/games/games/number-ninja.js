// Number Ninja Game
class NumberNinja {
    constructor(container) {
        this.container = container;
        this.score = 0;
        this.timeLeft = 60;
        this.gameActive = false;
        this.currentProblem = null;
        this.correctAnswer = null;
        this.timer = null;
    }

    // Initialize the game (required by the game loader)
    init() {
        console.log('NumberNinja init called');
        this.initUI();
        this.setupEventListeners();
        return this;
    }

    initUI() {
        this.container.innerHTML = `
            <div class="number-ninja">
                <div class="game-header">
                    <div>Score: <span id="score">0</span></div>
                    <div>Time: <span id="time">60</span>s</div>
                    <button id="start-btn" class="btn btn-primary">Start Game</button>
                </div>
                <div id="game-area" style="display: none; text-align: center; margin: 2rem 0;">
                    <div id="problem" style="font-size: 2.5rem; margin: 1rem 0; min-height: 3.5rem;"></div>
                    <div style="margin: 1rem 0;">
                        <input type="number" id="answer" placeholder="Your answer" style="font-size: 1.5rem; padding: 0.5rem; width: 200px; text-align: center;">
                        <button id="submit-btn" class="btn btn-primary" style="margin-left: 0.5rem;">Submit</button>
                    </div>
                    <div id="feedback" style="min-height: 1.5rem; margin: 1rem 0; font-weight: bold;"></div>
                </div>
                <div id="game-over" style="display: none; text-align: center; margin-top: 2rem;">
                    <h2>Game Over!</h2>
                    <p>Your score: <span id="final-score">0</span></p>
                    <button id="restart-btn" class="btn btn-primary">Play Again</button>
                </div>
            </div>
            <style>
                .number-ninja {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 1rem;
                }
                .game-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 1.5rem;
                    font-size: 1.2rem;
                    color: var(--text-primary);
                    flex-wrap: wrap;
                    gap: 1rem;
                }
                #problem {
                    font-weight: bold;
                    color: #4CAF50;
                }
                #answer {
                    border: 2px solid #4CAF50;
                    border-radius: 4px;
                }
                #answer:focus {
                    outline: none;
                    border-color: #45a049;
                    box-shadow: 0 0 5px rgba(76, 175, 80, 0.5);
                }
                #feedback {
                    min-height: 1.5rem;
                }
                .correct {
                    color: #4CAF50;
                }
                .incorrect {
                    color: #f44336;
                }
            </style>
        `;

        // Cache DOM elements
        this.scoreEl = document.getElementById('score');
        this.timeEl = document.getElementById('time');
        this.problemEl = document.getElementById('problem');
        this.answerEl = document.getElementById('answer');
        this.feedbackEl = document.getElementById('feedback');
        this.gameArea = document.getElementById('game-area');
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

        // Submit answer on Enter key
        this.answerEl?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.checkAnswer();
            }
        });

        // Submit button
        document.getElementById('submit-btn')?.addEventListener('click', () => {
            this.checkAnswer();
        });
    }

    startGame() {
        // Reset game state
        this.score = 0;
        this.timeLeft = 60;
        this.gameActive = true;

        // Update UI
        this.scoreEl.textContent = this.score;
        this.timeEl.textContent = this.timeLeft;
        this.gameArea.style.display = 'block';
        this.gameOverEl.style.display = 'none';
        document.querySelector('#start-btn').style.display = 'none';

        // Start timer
        if (this.timer) clearInterval(this.timer);
        this.timer = setInterval(() => this.updateTimer(), 1000);

        // Generate first problem
        this.generateProblem();

        // Focus answer input
        this.answerEl.focus();
    }

    generateProblem() {
        // Generate random numbers and operator
        const num1 = Math.floor(Math.random() * 20) + 1;
        const num2 = Math.floor(Math.random() * 20) + 1;
        const operators = ['+', '-', '*', '/'];
        const operator = operators[Math.floor(Math.random() * operators.length)];

        let problem, answer;

        // Ensure whole number answers for division
        if (operator === '/') {
            const divisor = Math.floor(Math.random() * 10) + 1;
            const result = Math.floor(Math.random() * 10) + 1;
            const dividend = divisor * result;
            problem = `${dividend} ÷ ${divisor}`;
            answer = result;
        } else {
            problem = `${num1} ${operator} ${num2}`;
            answer = Math.round(eval(problem) * 100) / 100; // Round to 2 decimal places
        }

        // Update UI
        this.problemEl.textContent = problem + ' = ?';
        this.answerEl.value = '';
        this.feedbackEl.textContent = '';
        this.currentProblem = problem;
        this.correctAnswer = answer;
    }

    checkAnswer() {
        if (!this.gameActive) return;

        const userAnswer = parseFloat(this.answerEl.value);

        if (isNaN(userAnswer)) {
            this.feedbackEl.textContent = 'Please enter a valid number';
            this.feedbackEl.className = 'incorrect';
            return;
        }

        // Round to 2 decimal places for comparison
        const roundedUserAnswer = Math.round(userAnswer * 100) / 100;
        const isCorrect = roundedUserAnswer === this.correctAnswer;

        // Update feedback
        if (isCorrect) {
            this.score += 10;
            this.feedbackEl.textContent = 'Correct! +10 points';
            this.feedbackEl.className = 'correct';

            // Add time bonus for correct answers
            this.timeLeft = Math.min(60, this.timeLeft + 2);
            this.timeEl.textContent = this.timeLeft;
        } else {
            this.feedbackEl.textContent = `Incorrect! The answer was ${this.correctAnswer}`;
            this.feedbackEl.className = 'incorrect';

            // Time penalty for wrong answers
            this.timeLeft = Math.max(0, this.timeLeft - 3);
            this.timeEl.textContent = this.timeLeft;
        }

        // Update score
        this.scoreEl.textContent = this.score;

        // Generate new problem after a short delay
        setTimeout(() => {
            if (this.gameActive) {
                this.generateProblem();
            }
        }, 1500);
    }

    updateTimer() {
        this.timeLeft--;
        this.timeEl.textContent = this.timeLeft;

        if (this.timeLeft <= 0) {
            this.gameOver();
        }
    }

    async gameOver() {
        this.gameActive = false;
        clearInterval(this.timer);

        // Show game over screen
        this.finalScoreEl.textContent = this.score;
        this.gameOverEl.style.display = 'block';
        this.gameArea.style.display = 'none';

        // Save score to server
        try {
            const response = await fetch('/GameVerse/Hackathon-projekt/pages/games/save_score.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    game: 'number-ninja',
                    score: this.score
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
}

// Expose the class to the global scope
if (typeof window !== 'undefined') {
    window.NumberNinja = NumberNinja;
    console.log('NumberNinja class registered');
}
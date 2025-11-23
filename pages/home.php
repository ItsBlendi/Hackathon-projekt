<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameVerse - House Rivalry Mini Games</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            overflow-x: hidden;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(10, 10, 26, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 243, 255, 0.2);
            padding: 1rem 2rem;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        }

        .navbar-links a {
            color: #b8c2cc;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .navbar-links a:hover {
            color: #00f3ff;
        }

        .btn {
            padding: 0.75rem 2rem;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background-color: #00f3ff;
            color: #0a0a1a;
        }

        .btn-primary:hover {
            background-color: #00d9e6;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 243, 255, 0.3);
        }

        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 6rem 2rem 4rem;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(0, 243, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(188, 19, 254, 0.1) 0%, transparent 50%);
            z-index: 0;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 5rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            line-height: 1.1;
            text-shadow: 0 0 30px rgba(0, 243, 255, 0.3);
        }

        .hero-subtitle {
            font-size: 1.5rem;
            color: #b8c2cc;
            margin-bottom: 3rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-cta {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }

        .features-section {
            padding: 6rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: #fff;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: #b8c2cc;
            margin-bottom: 4rem;
        }

        .houses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .house-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
            text-align: center;
        }

        .house-card:hover {
            border-color: rgba(0, 243, 255, 0.3);
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .house-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .house-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #00f3ff;
        }

        .house-description {
            color: #b8c2cc;
            line-height: 1.6;
        }

        .stats-section {
            padding: 6rem 2rem;
            background: rgba(255, 255, 255, 0.02);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .stats-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
            text-align: center;
        }

        .stat-item {
            padding: 2rem;
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 900;
            color: #00f3ff;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: #b8c2cc;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cta-section {
            padding: 8rem 2rem;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-title {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .cta-text {
            font-size: 1.3rem;
            color: #b8c2cc;
            margin-bottom: 3rem;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 3rem;
            }

            .hero-subtitle {
                font-size: 1.2rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .navbar-links {
                gap: 1rem;
            }

            .hero-cta {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="navbar-brand">🎮 GameVerse</a>
        <div class="navbar-links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Show logout button for logged-in users -->
                <a href="index.php?page=dashboard">Dashboard</a>
                <a href="index.php?page=logout" class="btn btn-secondary">Logout</a>
            <?php else: ?>
                <!-- Show login/register buttons for guests -->
                <a href="index.php?page=login">Login</a>
                <a href="index.php?page=register" class="btn btn-primary">Get Started</a>
            <?php endif; ?>
        </div>
    </nav>

    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Battle for House Glory</h1>
            <p class="hero-subtitle">
                Join one of four legendary houses and compete in epic mini-games. 
                Earn points, climb the leaderboard, and prove your house's dominance.
            </p>
            <div class="hero-cta">
                <a href="index.php?page=games" class="btn btn-primary">Join the Competition</a>
                <a href="index.php?page=login" class="btn btn-secondary">Login to Play</a>
            </div>
        </div>
    </section>

    <section class="features-section">
        <h2 class="section-title">Choose Your House</h2>
        <p class="section-subtitle">Each house has its own identity and community. Which will you join?</p>
        
        <div class="houses-grid">
            <div class="house-card">
                <div class="house-icon">🎨</div>
                <h3 class="house-name">Hipsters</h3>
                <p class="house-description">Creative innovators who think outside the box and redefine the meta</p>
            </div>
            
            <div class="house-card">
                <div class="house-icon">⚡</div>
                <h3 class="house-name">Speedsters</h3>
                <p class="house-description">Lightning-fast reflexes and unmatched agility in every challenge</p>
            </div>
            
            <div class="house-card">
                <div class="house-icon">🔧</div>
                <h3 class="house-name">Engineers</h3>
                <p class="house-description">Strategic masterminds who calculate every move to perfection</p>
            </div>
            
            <div class="house-card">
                <div class="house-icon">🌙</div>
                <h3 class="house-name">Shadows</h3>
                <p class="house-description">Mysterious players who strike when least expected with precision</p>
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number">4</div>
                <div class="stat-label">Houses</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">10+</div>
                <div class="stat-label">Mini Games</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">∞</div>
                <div class="stat-label">Glory Points</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">1</div>
                <div class="stat-label">Champion House</div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <h2 class="cta-title">Ready to Prove Yourself?</h2>
        <p class="cta-text">
            Join thousands of players competing for house supremacy. 
            Every game matters. Every point counts. Every victory brings glory.
        </p>
        <a href="index.php?page=games" class="btn btn-primary">Start Playing Now</a>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

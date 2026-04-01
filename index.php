<?php
    require_once 'redirect_helper.php';
    require_user_login('LOGINpage.php');

    if(isset($_GET['logout'])){
        session_destroy();
        unset($_SESSION['username']);
        redirect_to('LOGINpage.php');
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Home</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#0ea5a0">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <link rel="manifest" href="manifest.webmanifest">
        <link rel="apple-touch-icon" href="icons/icon-192.svg">
        <link rel="stylesheet" type="text/css" href="style.css">
        <link rel="stylesheet" type="text/css" href="responsive.css">
    </head>
    <body class="home-page">
        <header class="home-header">
            <h2 class="QUIZ">QUIZ FRIENDS</h2>
            <nav class="navigation">
                <a href="index.php" class="nav-active">Home</a>
                <a href="about1.php">About Us</a>
                <a href="contact2.php">Contact</a>
                <a href="Scoreboard.php">Rankings</a>
                <a class="btnlogin-popup" href="index.php?logout='1'">Logout</a>
            </nav>
        </header>

        <main class="home-main">
            <?php if(isset($_SESSION['success'])): ?>
            <div class="home-alert" role="status" aria-live="polite">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif ?>

            <section class="home-update" aria-label="Latest platform updates">
                <div class="home-update-head">
                    <span class="home-update-badge">2026 UPDATE</span>
                    <h2>New Features Are Live</h2>
                </div>
                <div class="home-update-grid">
                    <article class="home-update-item">
                        <h3>Daily Challenge</h3>
                        <p>Practice one focused challenge every day and keep your streak active.</p>
                    </article>
                    <article class="home-update-item">
                        <h3>Faster Attempt Flow</h3>
                        <p>Quiz loading and transitions are optimized for quicker start on all devices.</p>
                    </article>
                    <article class="home-update-item">
                        <h3>Improved Result Insights</h3>
                        <p>Result screens now highlight where you performed best and what to revise next.</p>
                    </article>
                </div>
                <div class="home-update-actions">
                    <a class="home-btn home-btn-primary" href="welcomequiz.php">Try New Quiz Flow</a>
                    <a class="home-btn home-btn-outline" href="index2.html">View Public Update Page</a>
                </div>
            </section>

            <section class="home-hero">
                <div class="hero-copy">
                    <?php if(isset($_SESSION['username'])): ?>
                    <p class="welcome-tag">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?> 👋</p>
                    <?php endif ?>
                    <h1>Sharpen your mind with fun, fast, and competitive quizzes.</h1>
                    <p>Play timed rounds, track your progress, and challenge your friends on the live scoreboard.</p>
                    <div class="hero-actions">
                        <a class="home-btn home-btn-primary" href="welcomequiz.php">Start Quiz</a>
                        <a class="home-btn home-btn-outline" href="Scoreboard.php">View Ranking</a>
                    </div>
                </div>

                <div class="hero-panel">
                    <h3>Quiz Highlights</h3>
                    <ul>
                        <li>⏱ Timed questions to boost speed.</li>
                        <li>🎯 Instant result and score feedback.</li>
                        <li>🏆 Competitive leaderboard tracking.</li>
                        <li>📱 Smooth experience on all devices.</li>
                    </ul>
                </div>
            </section>

            <section class="home-features">
                <article class="feature-card">
                    <h3>Smart Challenge Mode</h3>
                    <p>Answer consecutive questions and improve your focus under pressure.</p>
                </article>
                <article class="feature-card">
                    <h3>Performance Tracking</h3>
                    <p>Monitor your score trends and keep pushing your personal best.</p>
                </article>
                <article class="feature-card">
                    <h3>Friendly Competition</h3>
                    <p>Compete with your friends and climb higher on the ranking board.</p>
                </article>
            </section>

            <section class="home-steps">
                <h2>How to play</h2>
                <div class="steps-grid">
                    <div class="step-item">
                        <span>1</span>
                        <p>Start a quiz from your dashboard.</p>
                    </div>
                    <div class="step-item">
                        <span>2</span>
                        <p>Answer quickly and carefully before time runs out.</p>
                    </div>
                    <div class="step-item">
                        <span>3</span>
                        <p>Check your score and challenge friends again.</p>
                    </div>
                </div>
            </section>

            <?php
                require_once 'config.php';
                $feedbackResult = mysqli_query($conn, "SELECT name, rating, message, created_at FROM feedback ORDER BY created_at DESC LIMIT 12");
                $feedbackRows = [];
                if ($feedbackResult) {
                    while ($row = mysqli_fetch_assoc($feedbackResult)) {
                        $feedbackRows[] = $row;
                    }
                }
            ?>

            <?php if (!empty($feedbackRows)): ?>
            <section class="home-feedback" aria-label="User testimonials and feedback">
                <div class="feedback-header">
                    <h2>What Users Say</h2>
                    <p>Read feedback from our community</p>
                </div>
                <div class="feedback-grid">
                    <?php foreach($feedbackRows as $feedback): ?>
                    <article class="feedback-card">
                        <div class="feedback-rating">
                            <?php 
                                $rating = (int)$feedback['rating'];
                                for ($i = 0; $i < 5; $i++) {
                                    echo $i < $rating ? '⭐' : '☆';
                                }
                            ?>
                            <span class="rating-value"><?php echo htmlspecialchars($rating); ?>/5</span>
                        </div>
                        <p class="feedback-message"><?php echo htmlspecialchars($feedback['message']); ?></p>
                        <div class="feedback-footer">
                            <p class="feedback-name">— <?php echo htmlspecialchars($feedback['name']); ?></p>
                            <p class="feedback-date"><?php echo date('M d, Y', strtotime($feedback['created_at'])); ?></p>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </main>

        <footer class="home-footer">
            <p>Ready for the next round? <a href="welcomequiz.php">Play now</a></p>
        </footer>
        <script src="pwa-init.js"></script>
    </body>
</html>
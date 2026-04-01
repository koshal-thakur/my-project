
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>About Us | Quiz Competitors</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
    />
    <link rel="stylesheet" href="page.css" />
  </head>
  <body>
    <header>
      <h2 class="QUIZ">QUIZ COMPETITORS</h2>
      <nav class="navigation">
        <a href="index2.html">Home</a>
        <a href="about1.php" class="nav-active">About Us</a>
        <a href="contact2.php">Contact</a>
        <button onclick="location.href='LOGINpage.php';" class="btnlogin-popup">Login</button>
      </nav>
    </header>

    <main class="simple-page-wrap">
      <h1 class="simple-page-title">About Quiz Competitors</h1>

      <section class="page-update-strip" aria-label="2026 platform update">
        <span class="page-update-badge">2026 UPDATE</span>
        <h2>New Season Improvements Are Live</h2>
        <p>We upgraded speed, fairness checks, and feedback flow to make every quiz attempt smoother and more competitive.</p>
        <div class="page-update-mini-grid">
          <div class="page-update-mini">
            <strong>Daily Challenge</strong>
            <span>Practice one focused challenge every day.</span>
          </div>
          <div class="page-update-mini">
            <strong>Faster Sessions</strong>
            <span>Improved loading for quicker quiz start.</span>
          </div>
          <div class="page-update-mini">
            <strong>Better Insights</strong>
            <span>Clearer result understanding after each attempt.</span>
          </div>
        </div>
      </section>

      <div class="info-card info-card-gap">
        <h3>Our Mission</h3>
        <p>
          Quiz Competitors is an interactive learning platform designed to test and improve knowledge through
          engaging quizzes. Participants can compete across subjects like General Knowledge, Science,
          Technology, Mathematics, and Current Affairs.
        </p>
      </div>

      <div class="soft-grid">
        <div class="info-card">
          <h3>What You Can Do</h3>
          <ul class="info-list">
            <li>Take timed quizzes and track progress.</li>
            <li>Compete on leaderboard with other users.</li>
            <li>Improve speed, accuracy, and confidence.</li>
          </ul>
        </div>

        <div class="info-card">
          <h3>Our Focus</h3>
          <ul class="info-list">
            <li>Simple and beautiful user experience.</li>
            <li>Fair and engaging competition flow.</li>
            <li>Continuous improvement from user feedback.</li>
          </ul>
        </div>
      </div>

      <div class="info-card about-team-card">
        <img src="koshal.jpg" alt="Koshal Thakur" class="about-avatar" />
        <h3>Koshal Thakur</h3>
        <p>Developer</p>
        <div class="social-row">
          <a href="https://www.linkedin.com/in/koshal-thakur-641721332/" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
          <a href="https://github.com/koshal-thakur" aria-label="GitHub"><i class="fab fa-github"></i></a>
          <a href="mailto:koshalthakur1902@gmail.com" aria-label="Email"><i class="fas fa-envelope"></i></a>
        </div>
      </div>

      <!-- User Feedback / Testimonials Section -->
      <h2 class="simple-page-title feedback-section-title">⭐ What Participants Say</h2>
      <div id="feedbackList" class="feedback-list-grid">
        <p class="feedback-list-state">Loading feedback…</p>
      </div>

      <!-- Leave Feedback Form -->
      <div class="info-card feedback-form-card">
        <h3>Leave Your Feedback</h3>
        <form id="feedbackForm" class="feedback-form-grid">
          <div class="txt_field">
            <input type="text" id="fbName" placeholder=" " required maxlength="100">
            <label>Your Name</label>
          </div>
          <div class="feedback-rating-row">
            <label class="feedback-rating-label">Rating:</label>
            <select id="fbRating" class="feedback-rating-select">
              <option value="5">⭐⭐⭐⭐⭐ (5)</option>
              <option value="4">⭐⭐⭐⭐ (4)</option>
              <option value="3">⭐⭐⭐ (3)</option>
              <option value="2">⭐⭐ (2)</option>
              <option value="1">⭐ (1)</option>
            </select>
          </div>
          <div class="txt_field">
            <textarea id="fbMessage" placeholder=" " required maxlength="500" rows="3"></textarea>
            <label>Message</label>
          </div>
          <div id="fbStatus" class="feedback-status"></div>
          <button type="submit" class="btn-start feedback-submit-btn">Submit Feedback</button>
        </form>
      </div>
    </main>

    <script>
      (function () {
        var stars = ['', '⭐', '⭐⭐', '⭐⭐⭐', '⭐⭐⭐⭐', '⭐⭐⭐⭐⭐'];

        function loadFeedback() {
          fetch('feedback_fetch.php', { cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (items) {
              var container = document.getElementById('feedbackList');
              if (!container) return;
              if (!items || items.length === 0) {
                container.innerHTML = '<p class="feedback-list-state">No feedback yet. Be the first!</p>';
                return;
              }
              container.innerHTML = items.map(function (item) {
                var rating = parseInt(item.rating, 10) || 5;
                var safeStars = rating >= 1 && rating <= 5 ? stars[rating] : stars[5];
                return '<div class="info-card feedback-item-card">' +
                  '<div class="feedback-item-stars">' + safeStars + '</div>' +
                  '<p class="feedback-item-name">' + escHtml(item.name) + '</p>' +
                  '<p class="feedback-item-message">' + escHtml(item.message) + '</p>' +
                  '</div>';
              }).join('');
            })
            .catch(function () {
              var container = document.getElementById('feedbackList');
              if (container) container.innerHTML = '<p class="feedback-list-state">Could not load feedback.</p>';
            });
        }

        function escHtml(str) {
          return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
        }

        var form = document.getElementById('feedbackForm');
        if (form) {
          form.addEventListener('submit', function (e) {
            e.preventDefault();
            var statusEl = document.getElementById('fbStatus');
            var name = (document.getElementById('fbName').value || '').trim();
            var rating = document.getElementById('fbRating').value;
            var message = (document.getElementById('fbMessage').value || '').trim();

            if (!name || !message) {
              if (statusEl) { statusEl.textContent = 'Name and message are required.'; statusEl.style.color = '#b91c1c'; }
              return;
            }

            var formData = new FormData();
            formData.append('name', name);
            formData.append('rating', rating);
            formData.append('message', message);

            fetch('feedback_submit.php', { method: 'POST', body: formData })
              .then(function (r) { return r.json(); })
              .then(function (data) {
                if (data.success) {
                  if (statusEl) { statusEl.textContent = '✅ ' + data.message; statusEl.style.color = '#15803d'; }
                  form.reset();
                  loadFeedback();
                } else {
                  if (statusEl) { statusEl.textContent = data.message || 'Error submitting.'; statusEl.style.color = '#b91c1c'; }
                }
              })
              .catch(function () {
                if (statusEl) { statusEl.textContent = 'Network error. Please try again.'; statusEl.style.color = '#b91c1c'; }
              });
          });
        }

        loadFeedback();
      })();
    </script>
    <script src="india-time.js"></script>
  </body>
</html>
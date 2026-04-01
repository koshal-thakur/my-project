(function () {
  if (window.__jarvisLoaded) return;
  window.__jarvisLoaded = true;

  var style = document.createElement('style');
  style.textContent = `
    .jarvis-launcher {
      position: fixed;
      right: 22px;
      bottom: 20px;
      z-index: 2100;
      width: 56px;
      height: 56px;
      border: none;
      border-radius: 50%;
      background: linear-gradient(135deg, #0ea5a0 0%, #2563eb 50%, #f97316 100%);
      color: #fff;
      font-size: 24px;
      cursor: pointer;
      box-shadow: 0 14px 34px rgba(15,118,110,.35);
      transition: transform .2s ease, box-shadow .2s ease;
    }
    .jarvis-launcher:hover { transform: translateY(-2px) scale(1.04); box-shadow: 0 18px 40px rgba(37,99,235,.36); }

    .jarvis-panel {
      position: fixed;
      right: 22px;
      bottom: 88px;
      z-index: 2100;
      width: min(92vw, 360px);
      max-height: min(70vh, 560px);
      border-radius: 16px;
      border: 1px solid rgba(13,148,136,.25);
      background: rgba(255,255,255,.96);
      box-shadow: 0 20px 48px rgba(15,23,42,.2);
      backdrop-filter: blur(10px);
      display: none;
      overflow: hidden;
    }
    .jarvis-panel.open { display: grid; grid-template-rows: auto 1fr auto; }

    .jarvis-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 14px;
      background: linear-gradient(135deg, rgba(14,165,160,.12), rgba(37,99,235,.1));
      border-bottom: 1px solid rgba(13,148,136,.16);
    }
    .jarvis-title { font-size: .9rem; font-weight: 800; color: #0f172a; letter-spacing: .02em; }
    .jarvis-close { border: none; background: transparent; font-size: 1rem; cursor: pointer; color: #334155; }
    .jarvis-head-actions { display: inline-flex; align-items: center; gap: 6px; }
    .jarvis-head-btn {
      border: 1px solid rgba(13,148,136,.24);
      background: rgba(255,255,255,.75);
      border-radius: 8px;
      width: 30px;
      height: 30px;
      font-size: .85rem;
      cursor: pointer;
      color: #0f172a;
    }
    .jarvis-head-btn.is-active {
      background: linear-gradient(135deg, rgba(14,165,160,.16), rgba(37,99,235,.16));
      border-color: rgba(13,148,136,.4);
    }

    .jarvis-messages {
      overflow-y: auto;
      padding: 12px;
      display: grid;
      gap: 8px;
      background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    }
    .jarvis-msg {
      max-width: 92%;
      border-radius: 12px;
      padding: 8px 10px;
      font-size: .84rem;
      line-height: 1.45;
      border: 1px solid rgba(13,148,136,.14);
      color: #0f172a;
    }
    .jarvis-msg-user {
      margin-left: auto;
      background: linear-gradient(135deg, rgba(37,99,235,.12), rgba(14,165,160,.1));
    }
    .jarvis-msg-ai {
      margin-right: auto;
      background: #fff;
    }

    .jarvis-quick {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      padding: 0 12px 10px;
      background: #fff;
    }
    .jarvis-chip {
      border: 1px solid rgba(13,148,136,.2);
      background: rgba(14,165,160,.08);
      color: #0f766e;
      border-radius: 999px;
      padding: 5px 10px;
      font-size: .72rem;
      font-weight: 700;
      cursor: pointer;
      flex: 0 0 auto;
    }

    .jarvis-form {
      display: grid;
      grid-template-columns: 1fr auto auto;
      gap: 8px;
      padding: 10px;
      border-top: 1px solid rgba(13,148,136,.12);
      background: #fff;
    }
    .jarvis-input {
      border: 1px solid rgba(13,148,136,.24);
      border-radius: 10px;
      padding: 9px 10px;
      font-size: .84rem;
      outline: none;
    }
    .jarvis-send {
      border: none;
      border-radius: 10px;
      padding: 9px 12px;
      font-weight: 700;
      color: #fff;
      background: linear-gradient(135deg, #0ea5a0, #2563eb);
      cursor: pointer;
    }
    .jarvis-mic {
      border: 1px solid rgba(13,148,136,.24);
      border-radius: 10px;
      background: rgba(14,165,160,.08);
      color: #0f766e;
      font-size: .95rem;
      padding: 0 10px;
      cursor: pointer;
    }
    .jarvis-mic.is-listening {
      color: #fff;
      border-color: rgba(37,99,235,.45);
      background: linear-gradient(135deg, #0ea5a0, #2563eb);
      box-shadow: 0 0 0 3px rgba(37,99,235,.16);
    }

    @media (max-width: 900px) {
      .jarvis-launcher {
        right: 14px;
        bottom: 14px;
      }

      .jarvis-panel {
        right: 12px;
        left: 12px;
        width: auto;
        max-height: min(72vh, 620px);
      }

      .jarvis-msg {
        max-width: 96%;
      }
    }

    @media (max-width: 640px) {
      .jarvis-launcher {
        width: 52px;
        height: 52px;
        font-size: 22px;
        right: 12px;
        bottom: calc(10px + env(safe-area-inset-bottom));
      }

      .jarvis-panel {
        left: 0;
        right: 0;
        bottom: 0;
        width: 100vw;
        max-height: 78vh;
        border-radius: 16px 16px 0 0;
        border-left: none;
        border-right: none;
      }

      .jarvis-head {
        padding: 12px;
      }

      .jarvis-title {
        font-size: .85rem;
      }

      .jarvis-messages {
        padding: 10px;
      }

      .jarvis-quick {
        flex-wrap: nowrap;
        overflow-x: auto;
        padding: 0 10px 8px;
        -webkit-overflow-scrolling: touch;
      }

      .jarvis-form {
        gap: 6px;
        padding: 8px 8px calc(8px + env(safe-area-inset-bottom));
      }

      .jarvis-input {
        font-size: 16px;
        min-height: 40px;
      }

      .jarvis-send,
      .jarvis-mic {
        min-height: 40px;
      }
    }

    body.dark-mode .jarvis-panel {
      background: rgba(15,23,42,.96);
      border-color: rgba(20,184,166,.28);
    }
    body.dark-mode .jarvis-head { border-bottom-color: rgba(20,184,166,.2); }
    body.dark-mode .jarvis-title,
    body.dark-mode .jarvis-close { color: #e2e8f0; }
    body.dark-mode .jarvis-head-btn { background: rgba(30,41,59,.72); color: #e2e8f0; border-color: rgba(20,184,166,.24); }
    body.dark-mode .jarvis-messages { background: linear-gradient(180deg, #111827 0%, #0f172a 100%); }
    body.dark-mode .jarvis-msg-ai { color: #e2e8f0; background: rgba(30,41,59,.85); border-color: rgba(20,184,166,.22); }
    body.dark-mode .jarvis-msg-user { color: #e2e8f0; }
    body.dark-mode .jarvis-form,
    body.dark-mode .jarvis-quick { background: #0f172a; }
    body.dark-mode .jarvis-input { background: rgba(30,41,59,.8); color: #e2e8f0; border-color: rgba(20,184,166,.24); }
    body.dark-mode .jarvis-mic { background: rgba(30,41,59,.72); color: #5eead4; border-color: rgba(20,184,166,.24); }
  `;
  document.head.appendChild(style);

  var launcher = document.createElement('button');
  launcher.className = 'jarvis-launcher';
  launcher.type = 'button';
  launcher.title = 'Open Jarvis Assistant';
  launcher.setAttribute('aria-label', 'Open Jarvis Assistant');
  launcher.textContent = '🤖';

  var panel = document.createElement('section');
  panel.className = 'jarvis-panel';
  panel.innerHTML = `
    <div class="jarvis-head">
      <div class="jarvis-title">JARVIS • AI Assistant</div>
      <div class="jarvis-head-actions">
        <button class="jarvis-head-btn jarvis-voice-toggle" type="button" aria-label="Toggle voice replies" title="Toggle voice replies">🔊</button>
        <button class="jarvis-close" type="button" aria-label="Close Jarvis">✕</button>
      </div>
    </div>
    <div class="jarvis-messages" id="jarvisMessages"></div>
    <div class="jarvis-quick" id="jarvisQuick"></div>
    <form class="jarvis-form" id="jarvisForm">
      <input class="jarvis-input" id="jarvisInput" placeholder="Type a command…" maxlength="300" />
      <button class="jarvis-mic" id="jarvisMic" type="button" aria-label="Speak command" title="Speak command">🎤</button>
      <button class="jarvis-send" type="submit">Send</button>
    </form>
  `;

  document.body.appendChild(panel);
  document.body.appendChild(launcher);

  var messagesEl = panel.querySelector('#jarvisMessages');
  var quickEl = panel.querySelector('#jarvisQuick');
  var form = panel.querySelector('#jarvisForm');
  var input = panel.querySelector('#jarvisInput');
  var closeBtn = panel.querySelector('.jarvis-close');
  var voiceToggleBtn = panel.querySelector('.jarvis-voice-toggle');
  var micBtn = panel.querySelector('#jarvisMic');

  var speechSupported = typeof window.speechSynthesis !== 'undefined';
  var SpeechRecognitionApi = window.SpeechRecognition || window.webkitSpeechRecognition;
  var recognition = null;
  var listening = false;
  var speakingEnabled = localStorage.getItem('jarvisSpeakEnabled') !== '0';
  var speechLang = localStorage.getItem('jarvisSpeechLang') || 'en-US';
  var preferredVoice = null;

  function updateVoiceToggleUi() {
    if (!voiceToggleBtn) return;
    if (!speechSupported) {
      voiceToggleBtn.classList.remove('is-active');
      voiceToggleBtn.textContent = '🚫';
      voiceToggleBtn.setAttribute('title', 'Voice not supported in this browser');
      voiceToggleBtn.disabled = true;
      return;
    }
    voiceToggleBtn.classList.toggle('is-active', speakingEnabled);
    voiceToggleBtn.textContent = speakingEnabled ? '🔊' : '🔇';
    voiceToggleBtn.setAttribute('title', speakingEnabled ? 'Voice replies ON' : 'Voice replies OFF');
  }

  function pickPreferredVoice(targetLang) {
    if (!speechSupported) return null;
    var voices = window.speechSynthesis.getVoices() || [];
    if (!voices.length) return null;

    var maleHints = ['david', 'guy', 'male', 'mark', 'daniel', 'george', 'james', 'alex', 'adam', 'andrew', 'ryan', 'tom', 'john'];
    var lang = String(targetLang || speechLang || 'en-US').toLowerCase();

    var hindiVoices = voices.filter(function (v) {
      var vLang = String(v.lang || '').toLowerCase();
      var vName = String(v.name || '').toLowerCase();
      return vLang.indexOf('hi') === 0 || vName.indexOf('hindi') !== -1;
    });

    var englishVoices = voices.filter(function (v) {
      return /^en[-_]/i.test(v.lang || '') || /english/i.test(v.name || '');
    });

    var pool = voices;
    if (lang.indexOf('hi') === 0 && hindiVoices.length) {
      pool = hindiVoices;
    } else if (lang.indexOf('en') === 0 && englishVoices.length) {
      pool = englishVoices;
    }

    for (var i = 0; i < pool.length; i++) {
      var name = String(pool[i].name || '').toLowerCase();
      for (var j = 0; j < maleHints.length; j++) {
        if (name.indexOf(maleHints[j]) !== -1) {
          return pool[i];
        }
      }
    }

    return pool[0] || null;
  }

  function speakText(text) {
    if (!speechSupported || !speakingEnabled) return;
    var clean = String(text || '').trim();
    if (!clean) return;

    var utter = new SpeechSynthesisUtterance(clean);
    utter.rate = 0.93;
    utter.pitch = 0.86;
    utter.volume = 1;

    if (!preferredVoice) {
      preferredVoice = pickPreferredVoice(speechLang);
    }
    if (preferredVoice) {
      utter.voice = preferredVoice;
      if (preferredVoice.lang) utter.lang = preferredVoice.lang;
    } else {
      utter.lang = speechLang || 'en-US';
    }

    try {
      window.speechSynthesis.cancel();
      window.speechSynthesis.speak(utter);
    } catch (e) {}
  }

  if (speechSupported) {
    window.speechSynthesis.onvoiceschanged = function () {
      preferredVoice = pickPreferredVoice(speechLang);
    };
    preferredVoice = pickPreferredVoice(speechLang);
  }

  function setSpeechLanguage(langCode, spokenLabel) {
    speechLang = langCode;
    localStorage.setItem('jarvisSpeechLang', langCode);
    preferredVoice = pickPreferredVoice(speechLang);
    if (recognition) {
      recognition.lang = speechLang;
    }
    if (spokenLabel) {
      addMessage('Language switched to ' + spokenLabel + '.', 'ai');
    }
  }

  function setListening(state) {
    listening = !!state;
    if (!micBtn) return;
    micBtn.classList.toggle('is-listening', listening);
    micBtn.textContent = listening ? '⏺' : '🎤';
    micBtn.setAttribute('title', listening ? 'Listening...' : 'Speak command');
  }

  function setupRecognition() {
    if (!SpeechRecognitionApi) return;
    recognition = new SpeechRecognitionApi();
    recognition.lang = speechLang;
    recognition.interimResults = false;
    recognition.continuous = false;

    recognition.onstart = function () { setListening(true); };
    recognition.onend = function () { setListening(false); };
    recognition.onerror = function () { setListening(false); };
    recognition.onresult = function (event) {
      var transcript = '';
      if (event.results && event.results[0] && event.results[0][0]) {
        transcript = String(event.results[0][0].transcript || '').trim();
      }
      if (!transcript) return;
      input.value = transcript;
      form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    };
  }

  setupRecognition();
  updateVoiceToggleUi();

  if (micBtn && !SpeechRecognitionApi) {
    micBtn.disabled = true;
    micBtn.textContent = '🚫';
    micBtn.setAttribute('title', 'Voice input not supported in this browser');
  }

  function addMessage(text, role) {
    var msg = document.createElement('div');
    msg.className = 'jarvis-msg ' + (role === 'user' ? 'jarvis-msg-user' : 'jarvis-msg-ai');
    msg.textContent = text;
    messagesEl.appendChild(msg);
    messagesEl.scrollTop = messagesEl.scrollHeight;

    if (role !== 'user') {
      speakText(text);
    }
  }

  function setQuick(suggestions) {
    quickEl.innerHTML = '';
    (suggestions || []).slice(0, 4).forEach(function (item) {
      var chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'jarvis-chip';
      chip.textContent = item;
      chip.addEventListener('click', function () {
        input.value = item;
        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
      });
      quickEl.appendChild(chip);
    });
  }

  function performAction(action) {
    if (!action || !action.type) return;

    var media = Array.prototype.slice.call(document.querySelectorAll('video, audio'));

    function appendAutoplay(url) {
      try {
        var parsed = new URL(url, window.location.origin);
        if (!parsed.searchParams.has('autoplay')) {
          parsed.searchParams.set('autoplay', '1');
        }
        return parsed.toString();
      } catch (e) {
        return url;
      }
    }

    if (action.type === 'navigate' && action.url) {
      window.location.href = action.url;
      return;
    }

    if (action.type === 'openSearch' && action.query) {
      window.open('https://www.google.com/search?q=' + encodeURIComponent(action.query), '_blank', 'noopener');
      return;
    }

    if (action.type === 'openYouTube') {
      var ytQuery = action.query ? String(action.query).trim() : '';
      var ytUrl = action.url ? String(action.url) : '';
      
      // If there's a search query, always use YouTube search instead of base URL
      if (ytQuery) {
        var searchUrl = 'https://www.youtube.com/results?search_query=' + encodeURIComponent(ytQuery);
        window.open(searchUrl, '_blank', 'noopener');
        return;
      }
      
      // If there's a direct URL (e.g., YouTube link), use it
      if (ytUrl && ytUrl !== 'https://www.youtube.com/') {
        window.open(action.autoplay ? appendAutoplay(ytUrl) : ytUrl, '_blank', 'noopener');
        return;
      }

      // Default: open YouTube home
      window.open('https://www.youtube.com/', '_blank', 'noopener');
      return;
    }

    if (action.type === 'playMedia') {
      var played = false;
      media.forEach(function (m) {
        if (!played && typeof m.play === 'function') {
          m.play().catch(function () {});
          played = true;
        }
      });
      if (!played && action.query) {
        window.open('https://www.youtube.com/results?search_query=' + encodeURIComponent(action.query), '_blank', 'noopener');
      }
      return;
    }

    if (action.type === 'pauseMedia') {
      media.forEach(function (m) { if (typeof m.pause === 'function') m.pause(); });
      return;
    }

    if (action.type === 'muteMedia') {
      media.forEach(function (m) { m.muted = true; });
      return;
    }

    if (action.type === 'unmuteMedia') {
      media.forEach(function (m) { m.muted = false; });
      return;
    }

    if (action.type === 'scrollTop') {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  function fallbackLocal(text) {
    var cmd = String(text || '').toLowerCase();
    if (cmd.includes('rank') || cmd.includes('leaderboard') || cmd.includes('scoreboard')) {
      return { reply: 'Opening rankings page.', actions: [{ type: 'navigate', url: 'Scoreboard.php' }] };
    }
    if (cmd.includes('start quiz') || cmd === 'quiz' || cmd.includes('open quiz')) {
      return { reply: 'Opening quiz page.', actions: [{ type: 'navigate', url: 'welcomequiz.php' }] };
    }
    if (cmd.includes('play video')) {
      var query = cmd.replace('play video', '').trim();
      if (/^(any|some|a|an)?\s*(video|videos|music|audio|song|songs|short|shorts)$/.test(query)) {
        query = '';
      }
      return {
        reply: query ? 'Opening YouTube search for: ' + query : 'Opening YouTube Shorts.',
        actions: [{ type: 'openYouTube', query: query, url: query ? 'https://www.youtube.com/' : 'https://www.youtube.com/shorts' }]
      };
    }
    if (cmd.includes('open youtube') || cmd.includes('open you tube')) {
      var ytQuery = cmd
        .replace('open youtube', '')
        .replace('open you tube', '')
        .replace(/^\s*(for|about|on|search|search for)\s+/, '')
        .trim();
      if (/^(any|some|a|an)?\s*(video|videos|music|audio|song|songs|short|shorts)$/.test(ytQuery)) {
        ytQuery = '';
      }
      return {
        reply: ytQuery ? 'Opening YouTube search for: ' + ytQuery : 'Opening YouTube Shorts.',
        actions: [{ type: 'openYouTube', query: ytQuery, url: ytQuery ? 'https://www.youtube.com/' : 'https://www.youtube.com/shorts' }]
      };
    }
    if (cmd.startsWith('what is') || cmd.startsWith('who is') || cmd.startsWith('how to') || cmd.startsWith('explain')) {
      return { reply: 'Opening web search for your question.', actions: [{ type: 'openSearch', query: text }] };
    }
    return {
      reply: 'Try commands like: Open rankings, Start quiz, Play video on Java, What is recursion?, Search Python recursion.',
      actions: [],
      suggestions: ['Open rankings', 'Start quiz', 'Play video on Java', 'What is recursion?']
    };
  }

  async function askJarvis(text) {
    try {
      var res = await fetch('jarvis.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text, page: window.location.pathname })
      });
      if (!res.ok) throw new Error('network');
      return await res.json();
    } catch (e) {
      return fallbackLocal(text);
    }
  }

  function openPanel() {
    panel.classList.add('open');
    setTimeout(function () { input.focus(); }, 20);
  }

  function closePanel() {
    panel.classList.remove('open');
  }

  launcher.addEventListener('click', function () {
    if (panel.classList.contains('open')) closePanel();
    else openPanel();
  });

  closeBtn.addEventListener('click', closePanel);

  if (voiceToggleBtn) {
    voiceToggleBtn.addEventListener('click', function () {
      speakingEnabled = !speakingEnabled;
      localStorage.setItem('jarvisSpeakEnabled', speakingEnabled ? '1' : '0');
      updateVoiceToggleUi();
      if (speakingEnabled) {
        addMessage('Voice replies enabled.', 'ai');
      } else if (speechSupported) {
        try { window.speechSynthesis.cancel(); } catch (e) {}
      }
    });
  }

  if (micBtn) {
    micBtn.addEventListener('click', function () {
      if (!recognition) {
        addMessage('Voice input is not supported in this browser.', 'ai');
        return;
      }

      try {
        if (listening) {
          recognition.stop();
        } else {
          recognition.start();
        }
      } catch (e) {
        setListening(false);
      }
    });
  }

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    var text = input.value.trim();
    if (!text) return;

    addMessage(text, 'user');
    input.value = '';

    var lowerText = String(text || '').toLowerCase();

    if (lowerText === 'speak in hindi' || lowerText === 'hindi mode' || lowerText === 'switch to hindi' || lowerText === 'speak hindi') {
      setSpeechLanguage('hi-IN', 'Hindi');
      return;
    }

    if (lowerText === 'speak in english' || lowerText === 'english mode' || lowerText === 'switch to english' || lowerText === 'speak english') {
      setSpeechLanguage('en-US', 'English');
      return;
    }

    if (lowerText === 'voice off' || lowerText === 'mute jarvis' || lowerText === 'stop talking') {
      speakingEnabled = false;
      localStorage.setItem('jarvisSpeakEnabled', '0');
      updateVoiceToggleUi();
      if (speechSupported) {
        try { window.speechSynthesis.cancel(); } catch (e) {}
      }
      addMessage('Voice replies turned off.', 'ai');
      return;
    }
    if (lowerText === 'voice on' || lowerText === 'speak' || lowerText === 'talk') {
      speakingEnabled = true;
      localStorage.setItem('jarvisSpeakEnabled', '1');
      updateVoiceToggleUi();
      addMessage('Voice replies turned on.', 'ai');
      return;
    }

    var data = await askJarvis(text);
    addMessage(data.reply || 'Done.', 'ai');

    (data.actions || []).forEach(performAction);
    if (Array.isArray(data.suggestions)) setQuick(data.suggestions);
  });

  addMessage('Jarvis online. I can navigate pages, open YouTube videos, search the internet, and answer live website stats.', 'ai');
  setQuick(['Open Start Quiz', 'Top 5 leaderboard', 'Speak in Hindi', 'Speak in English']);

  (function () {
    var path = (window.location.pathname || '').toLowerCase();
    var isHome = path.endsWith('/index2.html') || path === '/' || path.endsWith('/questionsforyou/');
    if (!isHome) return;

    var key = 'jarvisHomeAutoWelcomed';
    var alreadyWelcomed = false;
    try {
      alreadyWelcomed = sessionStorage.getItem(key) === '1';
    } catch (e) {
      alreadyWelcomed = false;
    }

    if (alreadyWelcomed) return;

    try {
      sessionStorage.setItem(key, '1');
    } catch (e) {}

    setTimeout(function () {
      openPanel();
      addMessage('Welcome back. I am Jarvis, your voice assistant. You can speak or type any command.', 'ai');
    }, 550);
  })();
})();

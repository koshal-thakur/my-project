(function () {
  function ensureClockElement() {
    let target = document.querySelector('[data-india-time]');
    if (target) return target;

    const navigation = document.querySelector('header .navigation');
    if (!navigation) return null;

    target = document.createElement('span');
    target.className = 'india-header-time';
    target.setAttribute('data-india-time', 'true');
    target.setAttribute('aria-live', 'polite');
    navigation.appendChild(target);
    return target;
  }

  const clockEl = ensureClockElement();
  if (!clockEl) return;

  const dateFmt = new Intl.DateTimeFormat('en-IN', {
    timeZone: 'Asia/Kolkata',
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });

  const timeFmt = new Intl.DateTimeFormat('en-IN', {
    timeZone: 'Asia/Kolkata',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true
  });

  function updateClock() {
    const now = new Date();
    clockEl.textContent = '🇮🇳 ' + dateFmt.format(now) + ' • ' + timeFmt.format(now) + ' IST';
  }

  updateClock();
  setInterval(updateClock, 1000);
})();

(function () {
  if (window.__jarvisScriptLoading || window.__jarvisLoaded) return;

  function loadJarvis() {
    if (window.__jarvisScriptLoading || window.__jarvisLoaded) return;
    window.__jarvisScriptLoading = true;

    var current = document.currentScript;
    var src = current && current.src ? current.src : '';
    var base = '';
    if (src) {
      base = src.substring(0, src.lastIndexOf('/') + 1);
    }

    var script = document.createElement('script');
    script.src = (base || '') + 'jarvis.js';
    script.defer = true;
    script.onerror = function () {
      window.__jarvisScriptLoading = false;
    };
    document.head.appendChild(script);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadJarvis, { once: true });
  } else {
    loadJarvis();
  }
})();

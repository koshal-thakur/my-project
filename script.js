document.addEventListener('DOMContentLoaded', function () {
    var nav = document.querySelector('.navigation');
    if (!nav) {
        return;
    }

    var links = nav.querySelectorAll('a, button');
    links.forEach(function (item) {
        item.setAttribute('aria-label', item.textContent.trim());
    });
});

/* ── Light / Dark Theme Toggle ── */
(function () {
    var toggle = document.getElementById('themeToggle');
    if (!toggle) return;

    // Apply saved theme immediately (before paint)
    var saved = localStorage.getItem('theme') || 'light';
    if (saved === 'dark') {
        document.body.classList.add('dark-mode');
        toggle.textContent = '☀️';
        toggle.setAttribute('aria-label', 'Switch to light theme');
    }

    toggle.addEventListener('click', function () {
        var isDark = document.body.classList.toggle('dark-mode');
        toggle.textContent = isDark ? '☀️' : '🌙';
        toggle.setAttribute('aria-label', isDark ? 'Switch to light theme' : 'Switch to dark theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });
})();

/* ── PWA Install Prompt ── */
(function () {
    var installBtn = document.getElementById('installAppBtn');
    var installBanner = document.getElementById('installBanner');
    var installBannerBtn = document.getElementById('installBannerBtn');
    var installBannerClose = document.getElementById('installBannerClose');
    var deferredInstallPrompt = null;
    var DISMISS_KEY = 'installBannerDismissed';

    if (!installBtn && !installBanner) {
        return;
    }

    function showInstallUi() {
        if (installBtn) {
            installBtn.hidden = false;
        }

        if (installBanner && localStorage.getItem(DISMISS_KEY) !== '1') {
            installBanner.hidden = false;
        }
    }

    function hideInstallUi() {
        if (installBtn) {
            installBtn.hidden = true;
        }
        if (installBanner) {
            installBanner.hidden = true;
        }
    }

    function triggerInstall() {
        if (!deferredInstallPrompt) {
            return;
        }

        deferredInstallPrompt.prompt();
        deferredInstallPrompt.userChoice.finally(function () {
            deferredInstallPrompt = null;
            hideInstallUi();
        });
    }

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredInstallPrompt = event;
        showInstallUi();
    });

    window.addEventListener('appinstalled', function () {
        hideInstallUi();
        localStorage.setItem(DISMISS_KEY, '1');
    });

    if (installBtn) {
        installBtn.addEventListener('click', triggerInstall);
    }

    if (installBannerBtn) {
        installBannerBtn.addEventListener('click', triggerInstall);
    }

    if (installBannerClose) {
        installBannerClose.addEventListener('click', function () {
            if (installBanner) {
                installBanner.hidden = true;
            }
            localStorage.setItem(DISMISS_KEY, '1');
        });
    }
})();

/* ── Jarvis Loader ── */
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

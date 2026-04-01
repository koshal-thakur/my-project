(function () {
  if (!('serviceWorker' in navigator)) {
    return;
  }

  window.addEventListener('load', function () {
    var scriptEl = document.currentScript;
    var swUrl = scriptEl ? new URL('sw.js', scriptEl.src).toString() : './sw.js';

    navigator.serviceWorker.register(swUrl).catch(function () {
      // Ignore registration errors in unsupported local setups.
    });
  });
})();

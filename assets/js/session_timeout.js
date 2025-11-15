(function () {
  const TIMEOUT_MS = 5 * 60 * 1000; // 5 minutos
  let timerId;

  function redirectLogout() {
    try {
      window.location.href = 'logout.php?session=expired';
    } catch (e) {
      // Fallback silencioso
      window.location = 'logout.php?session=expired';
    }
  }

  function resetTimer() {
    if (timerId) clearTimeout(timerId);
    timerId = setTimeout(redirectLogout, TIMEOUT_MS);
  }

  function startIdleWatcher() {
    const opts = { passive: true };
    ['click', 'mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'touchmove', 'wheel']
      .forEach(evt => document.addEventListener(evt, resetTimer, opts));

    document.addEventListener('visibilitychange', resetTimer, opts);

    // Inicia o contador imediatamente ao carregar
    resetTimer();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startIdleWatcher);
  } else {
    startIdleWatcher();
  }
})();

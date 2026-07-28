(function () {
    var preloader = document.getElementById('preloader');
    if (!preloader) return;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        preloader.style.display = 'none';
        return;
    }

    window.addEventListener('load', function () {
        preloader.classList.add('loaded');
        setTimeout(function () {
            preloader.style.display = 'none';
        }, 500);
    });
})();

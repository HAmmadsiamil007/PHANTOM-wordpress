(function() {
    var storageKey = 'phantom_dark_mode';
    var body = document.body;
    var toggle = document.querySelector('[data-phantom-dark-toggle]');

    function setDarkMode(enabled) {
        if (enabled) {
            body.setAttribute('data-phantom-dark-mode', 'true');
            localStorage.setItem(storageKey, '1');
        } else {
            body.removeAttribute('data-phantom-dark-mode');
            localStorage.setItem(storageKey, '0');
        }
    }

    if (localStorage.getItem(storageKey) === '1') {
        setDarkMode(true);
    } else if (localStorage.getItem(storageKey) === null && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        setDarkMode(true);
    }

    if (toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            var isDark = body.getAttribute('data-phantom-dark-mode') === 'true';
            setDarkMode(!isDark);
        });
    }
})();

/* Comportements légers : tiroir de navigation, confirmations, compteur de caractères. */
(function () {
    'use strict';

    // Tiroir de navigation (mobile)
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebar-backdrop');
    var toggles = document.querySelectorAll('[data-sidebar-toggle]');

    function setSidebar(open) {
        if (!sidebar) { return; }
        sidebar.classList.toggle('is-open', open);
        if (backdrop) { backdrop.classList.toggle('is-open', open); }
        toggles.forEach(function (t) { t.setAttribute('aria-expanded', open ? 'true' : 'false'); });
        if (open) { var first = sidebar.querySelector('a, button'); if (first) { first.focus(); } }
    }
    toggles.forEach(function (t) {
        t.addEventListener('click', function () { setSidebar(!sidebar.classList.contains('is-open')); });
    });
    if (backdrop) { backdrop.addEventListener('click', function () { setSidebar(false); }); }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('is-open')) {
            setSidebar(false);
            if (toggles[0]) { toggles[0].focus(); }
        }
    });

    // Confirmation avant une action destructive : <form data-confirm="Message">
    document.addEventListener('submit', function (e) {
        var message = e.target.getAttribute && e.target.getAttribute('data-confirm');
        if (message && !window.confirm(message)) { e.preventDefault(); }
    });

    // Compteur de caractères : <textarea data-min="20" data-counter="#id">
    document.querySelectorAll('textarea[data-counter]').forEach(function (area) {
        var out = document.querySelector(area.getAttribute('data-counter'));
        var min = parseInt(area.getAttribute('data-min') || '0', 10);
        if (!out) { return; }
        function update() {
            var n = area.value.length;
            out.textContent = n >= min ? n + ' caractères' : n + ' caractères, encore ' + (min - n) + ' pour le minimum de ' + min;
        }
        area.addEventListener('input', update);
        update();
    });
})();

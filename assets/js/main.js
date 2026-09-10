// Newspapers South Africa — site scripts.
(function () {
    'use strict';
    document.documentElement.classList.add('js');

    // Mobile navigation toggle.
    var toggle = document.querySelector('.site-header__toggle');
    var nav = document.getElementById('site-nav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Dismissible cookie notice (shown only when ads are enabled).
    var notice = document.getElementById('cookie-notice');
    if (notice) {
        var KEY = 'nsa.cookie_ok';
        var acknowledged = false;
        try { acknowledged = window.localStorage.getItem(KEY) === '1'; } catch (e) {}

        if (!acknowledged) {
            notice.hidden = false;
        }

        var btn = document.getElementById('cookie-notice-ok');
        if (btn) {
            btn.addEventListener('click', function () {
                notice.hidden = true;
                try { window.localStorage.setItem(KEY, '1'); } catch (e) {}
            });
        }
    }
}());

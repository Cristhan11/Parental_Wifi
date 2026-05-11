/**
 * Short glow pulse on tap/click for child portal landing (local asset, no CDN).
 */
(function () {
    'use strict';

    if (!document.body.classList.contains('portal--landing')) {
        return;
    }

    var root = document.querySelector('.portal-wrap');
    if (!root) {
        return;
    }

    var reduceMotion = false;
    try {
        reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch (e) {
        /* ignore */
    }

    function pulseTarget(el) {
        if (!el || reduceMotion) {
            return;
        }
        el.classList.remove('portal-tap-pulse');
        // reflow so repeated taps retrigger animation
        void el.offsetWidth;
        el.classList.add('portal-tap-pulse');
        window.setTimeout(function () {
            el.classList.remove('portal-tap-pulse');
        }, 400);
    }

    root.addEventListener(
        'pointerdown',
        function (ev) {
            if (ev.button !== undefined && ev.button !== 0) {
                return;
            }
            var interactive = ev.target.closest(
                'a, button, input[type="submit"], input[type="button"], label.portal-mc-label'
            );
            if (!interactive || !root.contains(interactive)) {
                return;
            }
            if (interactive.closest('.portal-type-card--disabled')) {
                return;
            }
            pulseTarget(interactive);
        },
        true
    );
})();

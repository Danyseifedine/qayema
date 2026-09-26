/**
 * What guests do on the menu, for the owner's advanced analytics.
 *
 * Anonymous and fire-and-forget: actions queue up and go in one small batch a
 * few seconds later, or straight away when the guest is leaving, so nobody
 * ever waits on it and a send that fails is simply dropped.
 *
 * Two ways in:
 * - a link with data-track="whatsapp|map|call|social|language", plus
 *   data-track-value for the platform or the language;
 * - a `qayema:track` event, which the cart and the menu navigation dispatch
 *   with { type, dish_id, category_id, value } as its detail. They never call
 *   this file directly, so a menu without it loses nothing.
 */
(function () {
    'use strict';

    var config = window.QAYEMA_TRACK;
    if (!config || !window.fetch) {
        return;
    }

    /** The server takes at most this many in one request. */
    var BATCH = 25;
    var DELAY = 4000;

    var queue = [];
    var timer = null;

    function send(batch) {
        var token = document.querySelector('meta[name="csrf-token"]');

        try {
            // keepalive lets the request finish after the page has gone,
            // which is exactly when a tap on "call" or a language sends it.
            window.fetch(config.url, {
                method: 'POST',
                keepalive: true,
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
                },
                body: JSON.stringify({ events: batch }),
            }).catch(function () {});
        } catch (error) {
            // Analytics are never worth an error on a guest's screen.
        }
    }

    function flush() {
        if (timer) {
            window.clearTimeout(timer);
            timer = null;
        }

        while (queue.length > 0) {
            send(queue.splice(0, BATCH));
        }
    }

    function track(event) {
        queue.push(event);

        if (queue.length >= BATCH) {
            flush();
        } else if (!timer) {
            timer = window.setTimeout(flush, DELAY);
        }
    }

    document.addEventListener('qayema:track', function (event) {
        if (event.detail && event.detail.type) {
            track(event.detail);
        }
    });

    // Capture, so a handler that stops the click cannot hide it from here.
    document.addEventListener('click', function (event) {
        var link = event.target.closest('[data-track]');

        // The language already showing is not a switch.
        if (!link || !link.dataset.track || link.getAttribute('aria-current') === 'true') {
            return;
        }

        track({ type: link.dataset.track, value: link.dataset.trackValue || null });

        // Every tracked link leaves the menu one way or another.
        flush();
    }, true);

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            flush();
        }
    });

    window.addEventListener('pagehide', flush);
})();

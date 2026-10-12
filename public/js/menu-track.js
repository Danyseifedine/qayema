/**
 * The menu's visits and what guests do on it, for the owner's analytics.
 *
 * Anonymous and fire-and-forget: the visit and the actions queue up and go
 * in one small batch a few seconds later, or straight away when the guest
 * is leaving, so nobody ever waits on it and a send that fails is simply
 * dropped.
 *
 * Every batch names the guest's browser by a random id kept in local
 * storage (qayema-visitor, made anew after about 13 months), so the visit and
 * its actions count as one guest.
 * There is no session to do it: the menu stores none (and a page served from
 * a cache could not count its visit anyway).
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
    var VISITOR_KEY = 'qayema-visitor';

    /** About 13 months: how long one browser counts as the same guest. */
    var VISITOR_DAYS = 395;

    var visitor = visitorId();
    var visit = isNewVisit() ? { locale: config.locale, qr: new URLSearchParams(window.location.search).get('qr') === '1' } : null;
    var queue = [];
    var timer = null;

    /**
     * This browser's id, kept as "<id>.<day made>" and made again after
     * VISITOR_DAYS, as audience counting is expected to (cookie policy). A
     * browser that keeps nothing gets one for this page.
     */
    function visitorId() {
        var today = Math.floor(Date.now() / 86400000);
        var kept = null;

        try {
            kept = /^([a-f0-9]{32})\.(\d+)$/.exec(window.localStorage.getItem(VISITOR_KEY) || '');
        } catch (error) {
            // Storage blocked (a private window on some browsers).
        }

        if (kept && today - Number(kept[2]) < VISITOR_DAYS) {
            return kept[1];
        }

        var bytes = new Uint8Array(16);
        window.crypto.getRandomValues(bytes);
        var id = Array.prototype.map.call(bytes, function (byte) {
            return ('0' + byte.toString(16)).slice(-2);
        }).join('');

        try {
            window.localStorage.setItem(VISITOR_KEY, id + '.' + today);
        } catch (error) {
            // It counts this page only.
        }

        return id;
    }

    /**
     * Whether opening this page is a visit: switching the menu's language
     * loads it again from itself, and that is one visit, not two.
     */
    function isNewVisit() {
        var from = document.referrer.split('?')[0].replace(/\/+$/, '');

        return from === '' || from !== String(config.menu).replace(/\/+$/, '');
    }

    function send(batch) {
        var token = document.querySelector('meta[name="menu-token"]');
        var body = { visitor: visitor, events: batch };

        if (visit) {
            body.visit = visit;
            visit = null;
        }

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
                    'X-Menu-Token': token ? token.getAttribute('content') : '',
                },
                body: JSON.stringify(body),
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

        if (visit && queue.length === 0) {
            send([]);
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

    // The visit goes with the first actions, or on its own a moment later.
    if (visit) {
        timer = window.setTimeout(flush, DELAY);
    }
})();

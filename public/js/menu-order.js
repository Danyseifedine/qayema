/**
 * Following an order placed in the menu, without leaving the menu.
 *
 * - A bar at the top of the menu leads back to the order the guest placed,
 *   with where it has got to. It goes once the order is delivered or picked
 *   up, an hour after one is cancelled, and after twelve hours whatever
 *   happens.
 * - The tracking sheet shows the order (drawn by the server:
 *   resources/views/menu/partials/order-tracking.blade.php) and redraws
 *   when it moves on.
 * - "Moves on" comes from Pusher (App\Events\OrderMoved), on a channel named
 *   after the order's long random token. Pusher's library loads only when
 *   there is an order to follow, so a plain visit to the menu never pays
 *   for it. While Pusher cannot be heard (no keys, a network that blocks
 *   it, its limit reached) the order is asked for once a minute instead.
 *
 * menu-cart.js says when an order was sent (`qayema:placed`) and hears
 * where the guest's order stands (`qayema:order-state`): while it is going
 * the cart adds to it rather than starting another. "Change my order"
 * hands the order back to it (`qayema:edit`).
 */
(function () {
    'use strict';

    var config = window.QAYEMA_ORDERS;
    if (!config) {
        return;
    }

    var strings = config.strings;
    var icons = config.icons;
    var sheet = document.getElementById('track-sheet');
    var sheetBody = sheet ? sheet.querySelector('[data-track-body]') : null;

    /** How long the menu keeps pointing at an order placed in it. */
    var SHOWN_FOR = 12 * 60 * 60 * 1000;
    /** How often to ask while Pusher cannot be heard. */
    var FALLBACK_EVERY = 60 * 1000;

    var pusher = null;
    /** Who is waiting while Pusher's library is on its way. */
    var waiting = null;
    var followed = null;
    var fallback = null;
    /** The last answer for each order's address, so its sheet opens at once. */
    var known = {};

    function element(tag, className, text) {
        var node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (text !== undefined) {
            node.textContent = text;
        }
        return node;
    }

    function tokenOf(url) {
        var match = /\/order\/([A-Za-z0-9]{40})/.exec(url || '');
        return match ? match[1] : null;
    }

    // ---- The order the guest placed ----------------------------------------

    function saved() {
        try {
            var order = JSON.parse(window.localStorage.getItem(config.orderKey) || 'null');
            return order && order.url && Date.now() - order.at < SHOWN_FOR ? order : null;
        } catch (error) {
            return null;
        }
    }

    function remember(reference, url) {
        try {
            window.localStorage.setItem(config.orderKey, JSON.stringify({ reference: reference, url: url, at: Date.now() }));
        } catch (error) {
            // The toast's link still opens it.
        }
    }

    function forget() {
        try {
            window.localStorage.removeItem(config.orderKey);
        } catch (error) {
            // Nothing to forget.
        }
        paintBar(null);
        tellCart(null, null);
    }

    /** The cart's one order at a time (menu-cart.js). */
    function tellCart(url, data) {
        document.dispatchEvent(new CustomEvent('qayema:order-state', {
            detail: data && !data.closed
                ? { token: tokenOf(url), reference: data.reference, status: data.status, fulfilment: data.fulfilment }
                : null,
        }));
    }

    function fetchOrder(url) {
        return window
            .fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store' })
            .then(function (response) {
                // Deleted by the restaurant: nothing left to follow.
                if (response.status === 404) {
                    return { data: { gone: true } };
                }
                return response.ok ? response.json() : null;
            })
            .then(function (body) {
                var data = body && body.data ? body.data : null;
                if (data && !data.gone) {
                    known[url] = data;
                } else if (data) {
                    delete known[url];
                }
                return data;
            })
            .catch(function () {
                return null;
            });
    }

    // ---- The bar -----------------------------------------------------------

    /** What the bar says about the order, in the guest's language. */
    function statusText(data) {
        var delivery = data.fulfilment === 'delivery';
        var dineIn = data.fulfilment === 'dine_in';
        switch (data.status) {
            case 'accepted':
                return [strings.statusAccepted, 'moving'];
            case 'ready':
                return [delivery ? strings.statusOnItsWay : dineIn ? strings.statusComing : strings.statusReady, 'moving'];
            case 'done':
                return [delivery ? strings.statusDelivered : dineIn ? strings.statusServed : strings.statusPickedUp, 'done'];
            case 'cancelled':
                return [strings.statusCancelled, 'cancelled'];
            default:
                return [strings.statusWaiting, 'waiting'];
        }
    }

    function paintBar(data) {
        var old = document.querySelector('.order-bar');
        if (old) {
            old.remove();
        }

        var order = saved();
        var main = document.querySelector('main');
        if (!order || !main) {
            return;
        }

        var bar = element('div', 'order-bar');
        var link = element('a', 'order-bar-link');
        link.href = order.url;
        link.setAttribute('data-follow-order', '');
        link.setAttribute('aria-label', strings.yourOrder.replace(':reference', order.reference) + '. ' + strings.track);

        var mark = element('span', 'order-bar-mark');
        var glyph = element('span', 'icon');
        glyph.innerHTML = icons.cart;
        mark.appendChild(glyph);
        link.appendChild(mark);

        var said = data ? statusText(data) : [strings.statusWaiting, 'waiting'];
        var text = element('span', 'order-bar-text');
        text.appendChild(element('strong', null, strings.yourOrder.replace(':reference', order.reference)));
        var state = element('span', 'order-bar-status');
        state.setAttribute('data-tone', said[1]);
        state.appendChild(element('span', 'order-bar-dot'));
        state.appendChild(element('span', null, said[0]));
        text.appendChild(state);
        link.appendChild(text);

        link.appendChild(element('span', 'order-bar-cta', strings.trackShort));

        var close = element('button', 'order-bar-close');
        close.type = 'button';
        close.setAttribute('aria-label', strings.close);
        var cross = element('span', 'icon');
        cross.innerHTML = icons.close;
        close.appendChild(cross);
        close.addEventListener('click', function () {
            stopFollowing();
            forget();
        });

        bar.appendChild(link);
        bar.appendChild(close);
        main.insertBefore(bar, main.firstChild);
    }

    /** Delivered or picked up: done. Cancelled: shown for an hour. */
    function finished(data) {
        var stale = data.closed_at && Date.now() - Date.parse(data.closed_at) > 60 * 60 * 1000;
        return data.status === 'done' || stale;
    }

    // ---- Following it live -------------------------------------------------

    /** Pusher's library, loaded the first time an order is followed. */
    function withPusher(callback) {
        if (!config.pusher) {
            callback(null);
            return;
        }
        if (pusher) {
            callback(pusher);
            return;
        }
        // Already on its way: one library, one connection.
        if (waiting) {
            waiting.push(callback);
            return;
        }
        waiting = [callback];

        function done(client) {
            var callbacks = waiting;
            waiting = null;
            callbacks.forEach(function (each) {
                each(client);
            });
        }

        var script = document.createElement('script');
        script.src = config.pusher.script;
        script.async = true;
        script.onload = function () {
            pusher = new window.Pusher(config.pusher.key, { cluster: config.pusher.cluster, forceTLS: true });
            pusher.connection.bind('state_change', function (states) {
                if (states.current === 'connected') {
                    stopFallback();
                } else {
                    startFallback();
                }
            });
            done(pusher);
        };
        script.onerror = function () {
            done(null);
        };
        document.head.appendChild(script);
    }

    function startFallback() {
        if (fallback === null && followed) {
            fallback = window.setInterval(refresh, FALLBACK_EVERY);
        }
    }

    function stopFallback() {
        window.clearInterval(fallback);
        fallback = null;
    }

    function follow(url) {
        var token = tokenOf(url);
        if (!token || (followed && followed.token === token)) {
            return;
        }

        stopFollowing();
        followed = { token: token, url: url };

        withPusher(function (client) {
            if (!client || !followed || followed.token !== token) {
                startFallback();
                return;
            }
            followed.channel = client.subscribe('order.' + token);
            followed.channel.bind('order.moved', refresh);
            if (client.connection.state !== 'connected') {
                startFallback();
            }
        });
    }

    function stopFollowing() {
        stopFallback();
        if (followed && pusher) {
            pusher.unsubscribe('order.' + followed.token);
        }
        followed = null;
    }

    /** The order followed is the one this guest placed (not a shared link's). */
    function isMine(token) {
        var order = saved();
        return order !== null && tokenOf(order.url) === token;
    }

    /** Ask for the order again: the sheet, if open, and the bar. */
    function refresh() {
        if (!followed) {
            return;
        }
        var url = followed.url;
        var mine = isMine(followed.token);

        fetchOrder(url).then(function (data) {
            if (!data || !followed || followed.url !== url) {
                return;
            }
            if (data.gone) {
                letGo(mine);
                return;
            }
            if (sheet && sheet.open) {
                sheetBody.innerHTML = data.html;
            }
            if (finished(data)) {
                if (mine) {
                    forget();
                }
                if (!(sheet && sheet.open)) {
                    stopFollowing();
                }
                return;
            }
            // Someone else's order, opened from its link, says nothing
            // about this guest's own bar or cart.
            if (mine) {
                paintBar(data);
                tellCart(url, data);
            }
            if (data.closed) {
                stopFallback();
            }
        });
    }

    /** The restaurant deleted the order: the bar, the sheet and the cart let go of it. */
    function letGo(mine) {
        if (mine) {
            forget();
        }
        stopFollowing();
        if (sheet && sheet.open) {
            closeSheet();
        }
    }

    // ---- The sheet ---------------------------------------------------------

    function show(url, data) {
        sheetBody.innerHTML = data.html;
        if (!sheet.open) {
            sheet.classList.remove('closing');
            sheet.showModal();
        }
        if (!data.closed) {
            follow(url);
        }
    }

    /**
     * Opens at once from what the bar already fetched, then asks again for
     * anything newer; with nothing fetched yet, the button that opened it
     * spins until the order arrives.
     */
    function openSheet(url, trigger) {
        if (!sheet) {
            window.location.href = url;
            return;
        }

        if (known[url]) {
            show(url, known[url]);
        } else if (trigger) {
            trigger.classList.add('is-loading');
            trigger.setAttribute('aria-busy', 'true');
        }

        fetchOrder(url).then(function (data) {
            if (trigger) {
                trigger.classList.remove('is-loading');
                trigger.removeAttribute('aria-busy');
            }
            if (data && data.gone) {
                letGo(isMine(tokenOf(url)));
            } else if (data) {
                show(url, data);
            }
        });
    }

    function closeSheet(then) {
        if (!sheet.open || sheet.classList.contains('closing')) {
            return;
        }
        sheet.classList.add('closing');
        var timer = window.setTimeout(finish, 300);

        function finish() {
            window.clearTimeout(timer);
            sheet.removeEventListener('animationend', onEnd);
            sheet.classList.remove('closing');
            sheet.close();
            if (then) {
                then();
            }
            // Delivered while it was open: nothing left to follow. Another
            // order's link was open: back to the guest's own.
            var order = saved();
            if (!order) {
                stopFollowing();
            } else if (!followed || !isMine(followed.token)) {
                follow(order.url);
                refresh();
            }
        }

        function onEnd(event) {
            if (event.target === sheet) {
                finish();
            }
        }

        sheet.addEventListener('animationend', onEnd);
    }

    // ---- Wiring ------------------------------------------------------------

    document.addEventListener('click', function (event) {
        var link = event.target.closest('[data-follow-order]');
        if (link) {
            event.preventDefault();
            openSheet(link.href, link);
            return;
        }

        var edit = event.target.closest('[data-edit-order]');
        if (edit) {
            var token = edit.getAttribute('data-edit-order');
            closeSheet(function () {
                document.dispatchEvent(new CustomEvent('qayema:edit', { detail: { token: token } }));
            });
        }
    });

    if (sheet) {
        sheet.querySelector('[data-track-close]').addEventListener('click', function () {
            closeSheet();
        });
        sheet.addEventListener('click', function (event) {
            if (event.target === sheet) {
                closeSheet();
            }
        });
        sheet.addEventListener('cancel', function (event) {
            event.preventDefault();
            closeSheet();
        });
    }

    document.addEventListener('qayema:placed', function (event) {
        remember(event.detail.reference, event.detail.url);
        paintBar(null);
        follow(event.detail.url);
        // Ready before the guest taps "Track your order".
        refresh();
    });

    // A tab coming back may have missed what happened meanwhile.
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            refresh();
        }
    });

    // An order's own link (/{slug}/order/{token}) lands here with ?track=.
    var asked = new URLSearchParams(window.location.search).get('track');
    if (asked && /^[A-Za-z0-9]{40}$/.test(asked)) {
        var address = new URL(window.location.href);
        address.searchParams.delete('track');
        window.history.replaceState(null, '', address.toString());
        openSheet(config.orderUrl + '/' + asked + window.location.search);
    }

    var order = saved();
    if (order) {
        paintBar(null);
        follow(order.url);
        refresh();
    }
})();

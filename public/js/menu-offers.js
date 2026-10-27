/**
 * The restaurant's offers on the guest's phone. The page may be a copy
 * Cloudflare kept for an hour, so what is live is worked out here, now and
 * every minute, in the restaurant's timezone: the twin of
 * App\Services\Offers\OfferSchedule (change one, change both).
 *
 * - Banner cards show while their offer is live; the carousel hides
 *   while none is.
 * - The banner is a carousel: one card at a time, a dot per live card, and
 *   it slides on by itself while on screen, unless the guest is touching
 *   it or asked the phone for less motion.
 * - A dish on a live discount shows its offer price, the old one struck
 *   through and a badge; its data-price is the price the cart charges
 *   (menu-cart.js reads it, and is told to draw again: qayema:prices).
 * - One offer over the menu a visit: the first live one in the owner's
 *   order that this guest has not seen as often as it allows (show_again).
 *   Its pop-up shows after its delay; an offer that is also an ad shows the
 *   ad once the pop-up is closed and the ad's own wait is up (an ad alone
 *   waits from the menu opening). An ad cannot be skipped: its seconds
 *   count down, then an X lets the guest close it.
 * - An offer's own dish (its deal, data-deal-offer) shows only while the
 *   offer is live, the same check as its banner; a category left with
 *   nothing else hides, heading and tab, and comes back with it
 *   (qayema:deals tells the page's search and tabs, menu-nav.js; the cart
 *   draws again and drops a deal that went, qayema:prices).
 * - A tap on "See the dishes" shows only that offer's dishes until the
 *   guest asks for the whole menu again.
 * - What was shown and tapped goes to the analytics (menu-track.js).
 */
(function () {
    'use strict';

    var config = window.QAYEMA_OFFERS;

    if (!config || !window.Intl) {
        return;
    }

    var DAYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
    var offers = config.offers || [];
    var byId = {};
    offers.forEach(function (offer) {
        byId[offer.id] = offer;
    });

    var clock = new Intl.DateTimeFormat('en-US', {
        timeZone: config.timezone,
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
        weekday: 'short',
        hour: 'numeric',
        minute: 'numeric',
        hourCycle: 'h23',
    });

    /** The restaurant's local date and time at an instant. */
    function local(ms) {
        var parts = {};
        clock.formatToParts(new Date(ms)).forEach(function (part) {
            parts[part.type] = part.value;
        });

        return {
            year: parseInt(parts.year, 10),
            month: parseInt(parts.month, 10),
            day: parseInt(parts.day, 10),
            weekday: DAYS.indexOf(parts.weekday.slice(0, 3).toLowerCase()),
            minutes: (parseInt(parts.hour, 10) % 24) * 60 + parseInt(parts.minute, 10),
        };
    }

    function daysInMonth(year, month) {
        return new Date(Date.UTC(year, month, 0)).getUTCDate();
    }

    /** The local day before, for a window that ran past midnight. */
    function dayBefore(at) {
        var date = new Date(Date.UTC(at.year, at.month - 1, at.day - 1));

        return {
            year: date.getUTCFullYear(),
            month: date.getUTCMonth() + 1,
            day: date.getUTCDate(),
            weekday: date.getUTCDay(),
        };
    }

    function dayMatches(schedule, at) {
        if (schedule.repeat === 'weekly') {
            return schedule.weekdays.indexOf(DAYS[at.weekday]) !== -1;
        }

        if (schedule.repeat === 'monthly') {
            var last = daysInMonth(at.year, at.month);
            var beyond = schedule.month_days.some(function (day) {
                return day > last;
            });

            return schedule.month_days.indexOf(at.day) !== -1 || (at.day === last && beyond);
        }

        return true;
    }

    function minutesOf(time) {
        var parts = time.split(':');

        return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
    }

    /** OfferSchedule::isLiveAt(), at an instant in milliseconds. */
    function scheduleLive(schedule, ms) {
        if (schedule.starts !== null && ms < schedule.starts) {
            return false;
        }

        if (schedule.ends !== null && ms >= schedule.ends) {
            return false;
        }

        var at = local(ms);

        if (!schedule.windows || schedule.windows.length === 0) {
            return dayMatches(schedule, at);
        }

        return schedule.windows.some(function (window) {
            var from = minutesOf(window.from);
            var to = minutesOf(window.to);

            if (to > from) {
                return at.minutes >= from && at.minutes < to && dayMatches(schedule, at);
            }

            return (at.minutes >= from && dayMatches(schedule, at)) || (at.minutes < to && dayMatches(schedule, dayBefore(at)));
        });
    }

    /** Live for the guest: switched on and in its time, or the owner's preview. */
    function isLive(offer, ms) {
        return offer.id === config.preview || (offer.active && scheduleLive(offer.schedule, ms));
    }

    /**
     * OfferDiscount::apply(), in whole cents, half up, never below nothing.
     * An amount off is in the main currency: a dish priced in the second
     * one (`rate`) takes it off at the rate (Offer::discounted()).
     */
    function discounted(cents, discount, rate) {
        if (discount.type === 'percent') {
            return Math.max(0, Math.round((cents * (100 - discount.value)) / 100));
        }

        return Math.max(0, cents - Math.round((discount.value * 100) / (rate || 1)));
    }

    /** As App\Support\Price and the cart's money(): no decimals on a whole amount. */
    function money(cents, symbol) {
        var amount = cents / 100;
        var whole = cents % 100 === 0;

        var sign = (symbol || config.currency);
        // A sign stays on the number ("$5"), letters get a small space ("L.L 89,000"), as App\Support\Price::symbol().
        return (sign.length > 1 ? sign + ' ' : sign) + amount.toLocaleString('en-US', {
            minimumFractionDigits: whole ? 0 : 2,
            maximumFractionDigits: whole ? 0 : 2,
        });
    }

    function badgeOf(discount) {
        return discount.type === 'percent' ? '-' + discount.value + '%' : '-' + money(Math.round(discount.value * 100));
    }

    function track(type, offer) {
        document.dispatchEvent(new CustomEvent('qayema:track', { detail: { type: type, value: String(offer.id) } }));
    }

    // ---- Cards in the menu ------------------------------------------------

    var cards = Array.prototype.slice.call(document.querySelectorAll('.offers-banner .offer'));
    var rows = Array.prototype.slice.call(document.querySelectorAll('[data-offers-row]'));
    var dishes = Array.prototype.slice.call(document.querySelectorAll('.dish[data-full-price]'));

    /** Each dish on a live discount: the cents it costs now and the offer giving it. */
    function deals(ms) {
        var best = {};

        offers.forEach(function (offer) {
            if (!offer.discount || !(offer.active && scheduleLive(offer.schedule, ms))) {
                return;
            }

            offer.dishes.forEach(function (id) {
                var dish = document.querySelector('.dish[data-dish="' + id + '"][data-full-price]');

                if (!dish) {
                    return;
                }

                var cents = discounted(Math.round(parseFloat(dish.dataset.fullPrice) * 100), offer.discount, parseFloat(dish.dataset.rate || '') || null);

                // Taking the whole price, it does not apply: no dish is ever
                // free (LiveOffers::bestAmong()).
                if (cents <= 0) {
                    return;
                }

                if (!best[id] || cents < best[id].cents) {
                    best[id] = { cents: cents, offer: offer };
                }
            });
        });

        return best;
    }

    // Prices the server gave when it refused a cart that cost more than the
    // page showed (menu-cart.js): they win over what this phone works out,
    // whose clock may be wrong. Dish id => price, "12.50".
    var serverPrices = {};

    document.addEventListener('qayema:server-prices', function (event) {
        Object.keys(event.detail || {}).forEach(function (id) {
            serverPrices[id] = String(event.detail[id]);
        });
        paint();
    });

    /**
     * The server's word on a dish, as a deal (or none) like deals() gives. A
     * price above the page's is the dish's own price gone up.
     */
    function serverDeal(dish, deal) {
        var cents = Math.round(parseFloat(serverPrices[dish.dataset.dish]) * 100);
        var full = Math.round(parseFloat(dish.dataset.fullPrice) * 100);

        if (cents >= full) {
            dish.dataset.fullPrice = (cents / 100).toFixed(2);

            return null;
        }

        return { cents: cents, offer: deal ? deal.offer : null };
    }

    function paintDish(dish, deal) {
        var full = Math.round(parseFloat(dish.dataset.fullPrice) * 100);
        var extra = Math.round(parseFloat(dish.dataset.extra || '0') * 100);
        var label = dish.querySelector('.price');
        var group = label && label.parentNode;
        var was = group && group.querySelector('.price-was');
        var badge = dish.querySelector('.dish-badge');
        var price = deal ? (deal.cents / 100).toFixed(2) : dish.dataset.fullPrice;
        var changed = dish.dataset.price !== price;

        dish.dataset.price = price;

        if (label) {
            label.textContent = money((deal ? deal.cents : full) + extra, dish.dataset.currency);
        }

        if (deal && group && !was) {
            was = document.createElement('s');
            was.className = 'price-was';
            group.appendChild(was);
        }

        if (was) {
            if (deal) {
                was.textContent = money(full + extra, dish.dataset.currency);
            } else {
                was.remove();
            }
        }

        // A price the server gave may come with no offer known here to name.
        var named = deal && deal.offer;

        // On the photo's corner, taking no room on the card; a dish without
        // a photo has it beside its price.
        if (named && !badge) {
            badge = document.createElement('span');
            badge.className = 'dish-badge';
            (dish.querySelector('.dish-photo-slot') || group || dish).appendChild(badge);
        }

        if (badge) {
            if (named) {
                // Left to right, so "-$2" keeps its sign in front on an Arabic menu.
                var text = document.createElement('bdi');
                text.dir = 'ltr';
                text.textContent = badgeOf(deal.offer.discount);
                badge.replaceChildren(text);
            } else {
                badge.remove();
            }
        }

        return changed;
    }

    var dealDishes = Array.prototype.slice.call(document.querySelectorAll('.dish[data-deal-offer]'));

    /**
     * Each deal dish shown while its offer is live and hidden otherwise
     * (data-deal-off, as the server draws it: LiveOffers::liveAmong()), and
     * each category with nothing but hidden deals hidden with its tab.
     * Says whether anything changed.
     */
    function paintDeals(ms) {
        var changed = false;

        dealDishes.forEach(function (dish) {
            var offer = byId[dish.dataset.dealOffer];
            var off = !(offer && isLive(offer, ms));

            if (off !== dish.hasAttribute('data-deal-off')) {
                dish.toggleAttribute('data-deal-off', off);
                dish.hidden = off;
                changed = true;
            }
        });

        if (!changed) {
            return false;
        }

        Array.prototype.forEach.call(document.querySelectorAll('.category'), function (section) {
            var items = section.querySelectorAll('.dish');
            var only = items.length > 0 && Array.prototype.every.call(items, function (dish) {
                return dish.hasAttribute('data-deal-off');
            });
            var tab = document.querySelector('.tab[data-tab="' + section.id + '"]');

            if (only !== section.hasAttribute('data-deal-off')) {
                section.toggleAttribute('data-deal-off', only);
                section.hidden = only;
            }

            if (tab) {
                tab.toggleAttribute('data-deal-off', only);
                tab.hidden = only;
            }
        });

        return true;
    }

    function paint() {
        var now = Date.now();
        var dealsChanged = paintDeals(now);

        if (dealsChanged) {
            document.dispatchEvent(new CustomEvent('qayema:deals'));
        }

        cards.forEach(function (card) {
            var offer = byId[card.dataset.offer];
            card.hidden = !(offer && isLive(offer, now));
        });

        rows.forEach(function (row) {
            row.hidden = !Array.prototype.some.call(row.querySelectorAll('.offer'), function (card) {
                return !card.hidden;
            });
        });

        var best = deals(now);
        var changed = false;

        dishes.forEach(function (dish) {
            var deal = best[dish.dataset.dish] || null;
            if (serverPrices[dish.dataset.dish] !== undefined) {
                deal = serverDeal(dish, deal);
            }
            changed = paintDish(dish, deal) || changed;
        });

        if (changed || dealsChanged) {
            document.dispatchEvent(new CustomEvent('qayema:prices'));
        }

        drawDots();
        watchCards();
    }

    // ---- Pictures uploaded before their size was kept ------------------------

    /**
     * Offer::showsPictureWhole(), once the picture is in: far from its card's
     * proportions, it shows whole on its blurred copy instead of cropped.
     */
    function fitPicture(img) {
        var card = img.closest('.offer');
        var box = card && parseFloat(window.getComputedStyle(card).getPropertyValue('--ratio'));

        if (!card || !box || !img.naturalWidth || !img.naturalHeight) {
            return;
        }

        var ratio = img.naturalWidth / img.naturalHeight;
        var apart = Math.max(ratio / box, box / ratio);
        card.classList.toggle('offer-fit-whole', apart > (card.classList.contains('offer-picture-only') ? 1.05 : 1.35));
    }

    Array.prototype.forEach.call(document.querySelectorAll('.offer-photo[data-fit="auto"]'), function (img) {
        if (img.complete) {
            fitPicture(img);
        } else {
            img.addEventListener('load', function () {
                fitPicture(img);
            });
        }
    });

    // ---- The banner carousel --------------------------------------------------

    var carousel = document.querySelector('[data-offers-track]');
    var dotsBox = document.querySelector('[data-offers-dots]');
    var SLIDE_EVERY = 5000;
    // More cards than this get a "3 / 12" count instead of a row of dots.
    var MAX_DOTS = 8;
    // After the guest swipes or taps it, it waits this long before sliding on.
    var HOLD_AFTER_TOUCH = 8000;
    var still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var heldUntil = 0;
    var hovered = false;
    var onScreen = false;
    var active = 0;
    var drawnFor = '';

    function slides() {
        return carousel ? Array.prototype.filter.call(carousel.children, function (card) {
            return !card.hidden;
        }) : [];
    }

    /** How far a card is from where a card rests: the carousel's start, inside its padding. */
    function distanceTo(card) {
        var style = window.getComputedStyle(carousel);
        var pad = parseFloat(style.paddingInlineStart) || 0;
        var box = carousel.getBoundingClientRect();
        var rect = card.getBoundingClientRect();

        return style.direction === 'rtl' ? box.right - pad - rect.right : rect.left - box.left - pad;
    }

    function goTo(index) {
        var card = slides()[index];

        if (!card) {
            return;
        }

        var distance = distanceTo(card);
        var rtl = window.getComputedStyle(carousel).direction === 'rtl';
        carousel.scrollBy({ left: rtl ? -distance : distance, behavior: still ? 'auto' : 'smooth' });
    }

    /** The card resting nearest the start: its dot lit, and its height the carousel's. */
    function markActive() {
        var list = slides();
        var nearest = Infinity;

        list.forEach(function (card, index) {
            var distance = Math.abs(distanceTo(card));
            if (distance < nearest) {
                nearest = distance;
                active = index;
            }
        });

        if (dotsBox) {
            var count = dotsBox.querySelector('.offers-count');
            if (count) {
                count.textContent = (active + 1) + ' / ' + list.length;
            }
            Array.prototype.forEach.call(dotsBox.querySelectorAll('.offers-dot'), function (dot, index) {
                dot.setAttribute('aria-current', index === active ? 'true' : 'false');
            });
            // Every card in view (a wide screen): nothing to slide to. Its
            // room is kept, so nothing moves.
            dotsBox.style.visibility = canSlide() ? '' : 'hidden';
        }

        carousel.style.height = list[active] ? list[active].offsetHeight + 'px' : '';
    }

    /** More cards than fit: something to slide to. */
    function canSlide() {
        return carousel.scrollWidth - carousel.clientWidth > 40;
    }

    function hold() {
        heldUntil = Date.now() + HOLD_AFTER_TOUCH;
    }

    /** A dot per live card, drawn again only when the live cards change. */
    function drawDots() {
        if (!dotsBox) {
            return;
        }

        var list = slides();
        var key = list.map(function (card) {
            return card.dataset.offer;
        }).join(',');

        if (key === drawnFor) {
            return;
        }

        drawnFor = key;
        dotsBox.hidden = list.length < 2;
        if (list.length > MAX_DOTS) {
            var count = document.createElement('span');
            count.className = 'offers-count';
            count.setAttribute('aria-hidden', 'true');
            dotsBox.replaceChildren(count);
            markActive();

            return;
        }

        dotsBox.replaceChildren.apply(dotsBox, list.length < 2 ? [] : list.map(function (card, index) {
            var dot = document.createElement('button');
            var offer = byId[card.dataset.offer];
            dot.type = 'button';
            dot.className = 'offers-dot';
            dot.setAttribute('aria-label', config.strings.showing.replace(':offer', offer ? offer.title : String(index + 1)));
            dot.addEventListener('click', function () {
                hold();
                goTo(index);
            });

            return dot;
        }));
        markActive();
    }

    if (carousel) {
        var queued = false;
        carousel.addEventListener('scroll', function () {
            if (!queued) {
                queued = true;
                window.requestAnimationFrame(function () {
                    queued = false;
                    markActive();
                });
            }
        }, { passive: true });

        window.addEventListener('resize', markActive);

        ['pointerdown', 'wheel', 'keydown', 'focusin'].forEach(function (type) {
            carousel.addEventListener(type, hold, { passive: true });
        });
        carousel.addEventListener('pointerenter', function (event) {
            hovered = event.pointerType === 'mouse';
        });
        carousel.addEventListener('pointerleave', function () {
            hovered = false;
        });

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                onScreen = entries[0].isIntersecting;
            }, { threshold: 0.6 }).observe(carousel);
        }

        window.setInterval(function () {
            var count = slides().length;

            if (still || !onScreen || hovered || document.hidden || Date.now() < heldUntil || count < 2 || !canSlide()) {
                return;
            }

            goTo((active + 1) % count);
        }, SLIDE_EVERY);
    }

    // ---- What guests saw and tapped ----------------------------------------

    var counted = {};

    function shown(offer) {
        if (!counted[offer.id]) {
            counted[offer.id] = true;
            track('offer_shown', offer);
        }
    }

    var watcher = 'IntersectionObserver' in window ? new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                var offer = byId[entry.target.dataset.offer];
                if (offer && !entry.target.hidden) {
                    shown(offer);
                }
                watcher.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 }) : null;

    function watchCards() {
        cards.forEach(function (card) {
            if (card.hidden || counted[card.dataset.offer] || card.dataset.watched) {
                return;
            }
            card.dataset.watched = '1';
            if (watcher) {
                watcher.observe(card);
            } else {
                shown(byId[card.dataset.offer]);
            }
        });
    }

    // ---- The one pop-up or ad of the visit ----------------------------------

    function today() {
        var at = local(Date.now());

        return at.year + '-' + at.month + '-' + at.day;
    }

    function readSeen(storage) {
        try {
            return JSON.parse(storage.getItem(config.seenKey) || '{}') || {};
        } catch (error) {
            return {};
        }
    }

    function writeSeen(storage, seen) {
        try {
            storage.setItem(config.seenKey, JSON.stringify(seen));
        } catch (error) {
            // Private mode: it may show again, which is all.
        }
    }

    /**
     * The menu was opened or refreshed, rather than reloaded by its own
     * language switch (a link from this same menu): only then does an
     * "every visit" offer show again.
     */
    function freshOpen() {
        var navigation = window.performance && performance.getEntriesByType
            ? performance.getEntriesByType('navigation')[0]
            : null;

        if (navigation && navigation.type === 'reload') {
            return true;
        }

        return document.referrer.split(/[?#]/)[0] !== config.menu;
    }

    /** Whether this guest may see it now, by how often it allows. */
    function due(offer) {
        if (offer.id === config.preview) {
            return true;
        }

        // Every time the menu opens or is refreshed: nothing to remember.
        if (offer.show_again === 'every_visit') {
            return freshOpen();
        }

        var seen = readSeen(window.localStorage)[offer.id];

        return offer.show_again === 'once' ? !seen : seen !== today();
    }

    function remember(offer) {
        if (offer.id === config.preview || offer.show_again === 'every_visit') {
            return;
        }

        var seen = readSeen(window.localStorage);
        seen[offer.id] = offer.show_again === 'once' ? 'once' : today();
        writeSeen(window.localStorage, seen);
    }

    /** Its pop-up; `then` runs once the guest has closed it. */
    function openPopup(offer, then) {
        var dialog = document.getElementById('offer-pop-' + offer.id);

        if (!dialog || typeof dialog.showModal !== 'function') {
            return;
        }

        if (then) {
            dialog.addEventListener('close', then, { once: true });
        }

        dialog.addEventListener('click', function (event) {
            if (event.target === dialog || event.target.closest('[data-offer-close]')) {
                dialog.close();
            }
        });
        dialog.showModal();
        remember(offer);
        shown(offer);
    }

    function openAd(offer) {
        var ad = document.getElementById('offer-ad-' + offer.id);

        if (!ad) {
            return;
        }

        var left = offer.seconds;
        var timer = ad.querySelector('[data-offer-timer]');
        var count = ad.querySelector('[data-offer-count]');
        var closer = ad.querySelector('[data-offer-close]');

        function close() {
            ad.classList.add('leaving');
            window.setTimeout(function () {
                ad.hidden = true;
                ad.classList.remove('leaving');
            }, 250);
        }

        // Not to be skipped: the seconds count down, then the X comes.
        ad.style.setProperty('--offer-seconds', offer.seconds + 's');
        count.textContent = String(left);
        timer.hidden = false;
        closer.hidden = true;
        var ticking = window.setInterval(function () {
            left -= 1;

            if (left > 0) {
                count.textContent = String(left);

                return;
            }

            window.clearInterval(ticking);
            timer.hidden = true;
            closer.hidden = false;
        }, 1000);
        closer.addEventListener('click', close);
        ad.hidden = false;
        remember(offer);
        shown(offer);
    }

    /**
     * Its ad, after the ad's own wait: still live, and not while something
     * else is open over the menu (it waits for that to close).
     */
    function adAfterWait(offer) {
        window.setTimeout(function show() {
            if (!isLive(offer, Date.now())) {
                return;
            }

            if (document.querySelector('dialog[open]')) {
                window.setTimeout(show, 1000);

                return;
            }

            openAd(offer);
        }, offer.ad_delay * 1000);
    }

    function openOverlay() {
        var now = Date.now();
        var pick = null;

        // The owner's preview first; otherwise the first live one in order.
        var preview = config.preview !== null ? byId[config.preview] : null;
        var candidates = preview ? [preview] : offers;

        for (var i = 0; i < candidates.length && pick === null; i++) {
            var offer = candidates[i];
            var overlay = offer.placements.indexOf('popup') !== -1 || offer.placements.indexOf('ad') !== -1;

            if (overlay && isLive(offer, now) && due(offer)) {
                pick = offer;
            }
        }

        if (pick === null) {
            return;
        }

        var popup = pick.placements.indexOf('popup') !== -1;
        var ad = pick.placements.indexOf('ad') !== -1;
        var wait = popup ? pick.delay : pick.ad_delay;

        window.setTimeout(function () {
            // Still live when its delay is up, and nothing else over the menu.
            if (!isLive(pick, Date.now()) || document.querySelector('dialog[open]')) {
                return;
            }

            if (popup) {
                openPopup(pick, ad ? function () {
                    adAfterWait(pick);
                } : null);
            } else {
                openAd(pick);
            }
        }, (pick.id === config.preview ? 0 : wait) * 1000 + 400);
    }

    // ---- Taps ---------------------------------------------------------------

    document.addEventListener('click', function (event) {
        var go = event.target.closest('[data-offer-go]');

        if (!go) {
            return;
        }

        var card = go.closest('[data-offer]');
        var offer = card && byId[card.dataset.offer];

        if (!offer) {
            return;
        }

        track('offer_tap', offer);

        if (offer.action === 'dishes') {
            var dialog = go.closest('dialog');
            if (dialog) {
                dialog.close();
            }
            var ad = go.closest('.offer-ad-wrap');
            if (ad) {
                ad.hidden = true;
            }
            // Its dishes under a tab of its own (menu-nav.js).
            if (window.QAYEMA_NAV) {
                window.QAYEMA_NAV.showOffer(config.strings.tab, offer.dishes);
            }
        }
    });

    paint();
    window.setInterval(paint, 60000);

    if (document.readyState === 'complete') {
        openOverlay();
    } else {
        window.addEventListener('load', openOverlay);
    }
})();

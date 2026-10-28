/**
 * Menu navigation: the category filter and the search box.
 *
 * Separate from the cart on purpose: these work on every menu, including one
 * whose package does not include ordering.
 */
(function () {
    'use strict';

    var inner = document.querySelector('.tabs-inner');
    var pill = document.querySelector('.tab-pill');
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.tab'));
    var sections = Array.prototype.slice.call(document.querySelectorAll('.category'));
    var builds = document.querySelector('.builds');
    var inputs = Array.prototype.slice.call(document.querySelectorAll('[data-menu-search]'));
    var noResults = document.getElementById('menu-no-results');

    /** Measures the tabs again when one changes size (set up below). */
    var resizeWatcher = null;

    /** The chosen category; 'all' is the tab the menu opens on. */
    var active = 'all';

    /** The search term, lowercased. The two filters compose. */
    var term = '';

    /** The term as fold() writes it, which is what is matched. */
    var folded = '';

    /** Each dish's searchable text, folded once. */
    var searchable = new WeakMap();

    /**
     * Text as a search compares it: lowercased, without accents ("creme"
     * finds "Crème"), and in Arabic without the short vowels and tatweel,
     * every alef as a bare alef, ة as ه and ى as ي, so "كنافه" finds
     * "كنافة" and "ارز" finds "أرز", the way guests type on a phone.
     */
    function fold(text) {
        return text.toLowerCase().normalize('NFD')
            .replace(/[\u0300-\u036f\u0610-\u061a\u064b-\u065f\u0670\u06d6-\u06ed\u0640]/g, '')
            .replace(/\u0671/g, '\u0627')
            .replace(/\u0629/g, '\u0647')
            .replace(/\u0649/g, '\u064a');
    }

    function searchTextOf(dish) {
        if (!searchable.has(dish)) {
            searchable.set(dish, fold(dish.dataset.search || ''));
        }

        return searchable.get(dish);
    }

    /** The dishes of the offer whose tab is open (QAYEMA_NAV.showOffer). */
    var offerDishes = [];

    /** Tell the analytics (menu-track.js) what a guest did. */
    function track(detail) {
        document.dispatchEvent(new CustomEvent('qayema:track', { detail: detail }));
    }

    /** Whether anything is showing; a search that finds nothing is worth knowing about. */
    function apply() {
        var anyShown = false;

        sections.forEach(function (section) {
            var inCategory = active === 'all' || section.id === active;
            var shown = 0;

            Array.prototype.forEach.call(section.querySelectorAll('.dish'), function (dish) {
                // Under an offer's tab, only its dishes, in whichever category.
                var picked = active === 'offer'
                    ? offerDishes.indexOf(parseInt(dish.dataset.dish, 10)) !== -1
                    : inCategory;
                // An offer's deal dish while the offer is not live (menu-offers.js) never shows.
                var match = !dish.hasAttribute('data-deal-off') && picked && (folded === '' || searchTextOf(dish).indexOf(folded) !== -1);
                dish.hidden = !match;
                if (match) {
                    shown++;
                }
            });

            section.hidden = shown === 0;
            anyShown = anyShown || shown > 0;
        });

        // The dishes guests build sit above the tabs: under an offer's tab
        // only the offer's own stay, and they count as something shown.
        if (builds) {
            var built = 0;
            Array.prototype.forEach.call(builds.querySelectorAll('.dish'), function (dish) {
                var match = active !== 'offer' || offerDishes.indexOf(parseInt(dish.dataset.dish, 10)) !== -1;
                dish.hidden = !match;
                if (match) {
                    built++;
                }
            });
            builds.hidden = built === 0;
            anyShown = anyShown || (active === 'offer' && built > 0);
        }

        if (noResults) {
            noResults.style.display = anyShown ? 'none' : 'block';
        }

        return anyShown;
    }

    // ---- The sliding pill ------------------------------------------------

    function currentTab() {
        for (var i = 0; i < tabs.length; i++) {
            if (tabs[i].getAttribute('aria-current') === 'true') {
                return tabs[i];
            }
        }

        return tabs[0];
    }

    function movePill() {
        var tab = currentTab();

        if (!pill || !tab) {
            return;
        }

        // offsetLeft is physical, and so is translateX, so this lands in the
        // right place on an Arabic menu too.
        pill.style.width = tab.offsetWidth + 'px';
        pill.style.transform = 'translateX(' + tab.offsetLeft + 'px)';
    }

    /** Keep the chosen tab on screen when the row scrolls sideways. */
    function revealTab() {
        var tab = currentTab();

        if (!inner || !tab) {
            return;
        }

        var left = tab.offsetLeft - 12;
        var right = tab.offsetLeft + tab.offsetWidth + 12;

        if (left < inner.scrollLeft) {
            inner.scrollTo({ left: left, behavior: 'smooth' });
        } else if (right > inner.scrollLeft + inner.clientWidth) {
            inner.scrollTo({ left: right - inner.clientWidth, behavior: 'smooth' });
        }
    }

    function select(tab) {
        // Any other tab takes an offer's tab away.
        if (tab.dataset.tab !== 'offer') {
            dropOfferTab();
        }

        tabs.forEach(function (other) {
            other.setAttribute('aria-current', other === tab ? 'true' : 'false');
        });

        active = tab.dataset.tab;

        apply();
        movePill();
        revealTab();

        // Only pull the page back when it is already scrolled past the first
        // section, which then sits under the sticky tabs; picking a tab while
        // the cover is still in view should not make the page jump.
        var first = sections.filter(function (section) {
            return !section.hidden;
        })[0];
        var nav = document.querySelector('.tabs');
        var under = nav ? nav.getBoundingClientRect().bottom - 1 : 0;

        if (first && first.getBoundingClientRect().top < under) {
            first.scrollIntoView({ block: 'start' });
        }
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            select(tab);

            var id = parseInt(tab.dataset.tab.replace('category-', ''), 10);

            if (id) {
                track({ type: 'category_open', category_id: id });
            }
        });
    });

    // ---- An offer's dishes, under a tab of its own -------------------------
    //
    // A tap on an offer that leads to its dishes (menu-offers.js) adds an
    // "Offers" tab beside "All" and opens it: only that offer's dishes show,
    // across the categories. "All" or any category takes the tab away again.

    function dropOfferTab() {
        var tab = inner ? inner.querySelector('.tab-offer') : null;

        if (tab) {
            tab.remove();
            tabs = tabs.filter(function (other) {
                return other !== tab;
            });
        }
    }

    window.QAYEMA_NAV = {
        /**
         * @param {string} label the tab's name ("Offers")
         * @param {number[]} dishes the offer's dishes
         */
        showOffer: function (label, dishes) {
            var all = inner ? inner.querySelector('.tab[data-tab="all"]') : null;

            if (!all) {
                return;
            }

            dropOfferTab();
            offerDishes = dishes;

            var tab = document.createElement('button');
            tab.type = 'button';
            tab.className = 'tab tab-offer';
            tab.dataset.tab = 'offer';
            tab.setAttribute('aria-current', 'false');
            var name = document.createElement('span');
            name.className = 'tab-name';
            name.textContent = label;
            tab.appendChild(name);
            tab.addEventListener('click', function () {
                select(tab);
            });
            inner.insertBefore(tab, all.nextSibling);
            tabs = Array.prototype.slice.call(inner.querySelectorAll('.tab'));
            if (resizeWatcher) {
                resizeWatcher.observe(tab);
            }

            select(tab);

            // Its dishes in sight, whatever the guest was looking at.
            var first = (builds && !builds.hidden ? [builds] : []).concat(sections).filter(function (section) {
                return !section.hidden;
            })[0];
            if (first) {
                first.scrollIntoView({ block: 'start', behavior: 'smooth' });
            }
        },
    };

    // A deal dish came or went with its offer (menu-offers.js), and its
    // category's tab with it: filter again, and leave a tab that hid.
    document.addEventListener('qayema:deals', function () {
        var tab = currentTab();

        if (tab && tab.hidden && tabs[0]) {
            select(tabs[0]);
        } else {
            apply();
            movePill();
        }
    });

    if (pill && tabs.length > 0) {
        movePill();

        window.requestAnimationFrame(function () {
            movePill();
            pill.classList.add('ready');
        });

        // Measure again whenever a tab or the row changes size. The menu's
        // fonts arrive after this script runs (their stylesheet switches on
        // when loaded), so document.fonts.ready has nothing to wait for yet,
        // and the font that lands later changes every tab's width.
        if ('ResizeObserver' in window) {
            resizeWatcher = new ResizeObserver(movePill);

            resizeWatcher.observe(inner);
            tabs.forEach(function (tab) {
                resizeWatcher.observe(tab);
            });
        } else {
            window.addEventListener('resize', movePill);
            window.addEventListener('load', movePill);
        }
    }

    // ---- Popups ----------------------------------------------------------
    //
    // Every dock action opens one, so they are wired once. The dock item it
    // came from stays lit for as long as its popup is up.

    var qrCode = document.querySelector('[data-qr-canvas]');

    /**
     * The owner's QR design, drawn by the same library the dashboard previews
     * with and the printable card uses (qr-code-styling), from the same
     * options (App\Services\Qr\QrStyle). Too big to hold up the menu, so
     * both are fetched once the page has loaded (see the bottom of this
     * section) and the popup opens at once; a tap before then waits for the
     * same requests rather than starting new ones.
     */
    var qrOptions = null;
    var libraryLoading = null;
    var optionsLoading = null;

    function loadLibrary() {
        if (window.QRCodeStyling) {
            return Promise.resolve();
        }

        libraryLoading = libraryLoading || new Promise(function (resolve, reject) {
            var tag = document.createElement('script');
            tag.src = qrCode.dataset.lib;
            tag.onload = resolve;
            tag.onerror = function (error) {
                // A later tap tries again.
                libraryLoading = null;
                tag.remove();
                reject(error);
            };
            document.head.appendChild(tag);
        });

        return libraryLoading;
    }

    function loadOptions() {
        if (qrOptions) {
            return Promise.resolve();
        }

        optionsLoading = optionsLoading || fetch(qrCode.dataset.options, { headers: { Accept: 'application/json' } })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('QR options ' + response.status);
                }

                return response.json();
            })
            .then(function (body) {
                qrOptions = body.data;
            }, function (error) {
                optionsLoading = null;
                throw error;
            });

        return optionsLoading;
    }

    function withGenerator(then, otherwise) {
        // A guest on a bad connection must not be left with a lit button and
        // no popup, so a failed fetch puts the dock back as it was.
        Promise.all([loadLibrary(), loadOptions()]).then(then, otherwise);
    }

    function drawCode() {
        if (qrCode.firstChild) {
            return;
        }

        var options = Object.assign({}, qrOptions, { width: 196, height: 196, type: 'svg' });
        new window.QRCodeStyling(options).append(qrCode);
    }

    // Ready before it is asked for: once the menu has loaded and the phone
    // has a quiet moment, the code is fetched and drawn in the closed popup.
    // A failure here is silent; a tap simply tries again.
    if (qrCode) {
        var prepare = function () {
            withGenerator(drawCode, function () {});
        };
        var whenIdle = function () {
            if (window.requestIdleCallback) {
                window.requestIdleCallback(prepare, { timeout: 2000 });
            } else {
                window.setTimeout(prepare, 300);
            }
        };

        if (document.readyState === 'complete') {
            whenIdle();
        } else {
            window.addEventListener('load', whenIdle, { once: true });
        }
    }

    var dockItems = Array.prototype.slice.call(document.querySelectorAll('.dockitem'));

    /** Only one popup is ever open, so nothing open means nothing lit. */
    function clearDock() {
        dockItems.forEach(function (item) {
            item.classList.remove('is-on');
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('dialog.pop'), function (pop) {
        pop.addEventListener('click', function (event) {
            // The backdrop, the close button, and the action link, which has
            // already opened its tab by the time this runs.
            if (event.target === pop || event.target.closest('[data-pop-close]')) {
                pop.close();
                clearDock();
            }
        });

        // Escape closes the dialog itself; these are the matching tidy-ups.
        pop.addEventListener('cancel', clearDock);
        pop.addEventListener('close', clearDock);
    });

    var photoPop = document.getElementById('pop-photo');
    var photoView = photoPop && photoPop.querySelector('[data-photo-view]');

    /**
     * A dish's full photo, the one its card shows small (the `thumb`). Never
     * the last dish's photo: the card's small one, already loaded, shows at
     * once, at the size the full one takes (the same shape, --ratio), and
     * the full one replaces it once it has arrived, if it is still the photo
     * asked for.
     */
    function showPhoto(dish) {
        if (!photoPop || !dish) {
            return;
        }

        var small = dish.dataset.image;
        var full = dish.dataset.photo || small;

        photoView.removeAttribute('src');
        photoView.dataset.full = full;
        photoView.alt = dish.dataset.name;
        photoView.src = small || full;
        photoPop.setAttribute('aria-label', dish.dataset.name);
        photoPop.showModal();

        if (small && full !== small) {
            whenLoaded(full, function () {
                if (photoView.dataset.full === full) {
                    photoView.src = full;
                }
            });
        }
    }

    /** Fetch a photo in the background, then call back. */
    function whenLoaded(url, then) {
        var loader = new Image();
        loader.onload = then;
        loader.src = url;
    }

    if (photoView) {
        photoView.addEventListener('load', function () {
            if (photoView.naturalWidth && photoView.naturalHeight) {
                photoView.style.setProperty('--ratio', String(photoView.naturalWidth / photoView.naturalHeight));
            }
        });
    }

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-scroll-top]')) {
            // The dock is a phone-only thing, so the window is what scrolls.
            window.scrollTo({ top: 0, behavior: 'smooth' });

            return;
        }

        var photoOpener = event.target.closest('[data-photo-open]');

        if (photoOpener) {
            showPhoto(photoOpener.closest('.dish'));

            return;
        }

        var opener = event.target.closest('[data-pop-open]');

        if (!opener) {
            return;
        }

        var pop = document.getElementById('pop-' + opener.dataset.popOpen);

        if (!pop) {
            return;
        }

        var item = opener.closest('.dockitem');

        if (item) {
            item.classList.add('is-on');
        }

        if (pop.id === 'pop-qr' && qrCode) {
            withGenerator(function () {
                drawCode();
                pop.showModal();
            }, function () {
                if (item) {
                    item.classList.remove('is-on');
                }
            });

            return;
        }

        pop.showModal();
    });

    // ---- Search: filter what is already on the page, no request ----
    //
    // There are two inputs (one in the header for a wide screen, one under the
    // cover for a phone) and only ever one of them is visible. They share a
    // term so switching orientation mid-search does not lose it.
    //
    // A search is counted once the guest stops typing, so "piz", "pizz" and
    // "pizza" are one search for pizza, and the same term twice in a row is
    // not counted again.
    var searchTimer = null;
    var lastSearch = '';

    inputs.forEach(function (input) {
        input.addEventListener('input', function () {
            term = input.value.trim().toLowerCase();
            folded = fold(term);

            inputs.forEach(function (other) {
                if (other !== input) {
                    other.value = input.value;
                }
            });

            var found = apply();

            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(function () {
                if (term.length < 2 || term === lastSearch) {
                    return;
                }

                lastSearch = term;
                track({ type: found ? 'search' : 'search_miss', value: term });
            }, 1500);
        });
    });
})();

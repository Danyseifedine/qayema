/**
 * Menu navigation: the category filter and the search box.
 *
 * Separate from the cart on purpose — these work on every menu, including one
 * whose package does not include ordering.
 */
(function () {
    'use strict';

    var inner = document.querySelector('.tabs-inner');
    var pill = document.querySelector('.tab-pill');
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.tab'));
    var sections = Array.prototype.slice.call(document.querySelectorAll('.category'));
    var inputs = Array.prototype.slice.call(document.querySelectorAll('[data-menu-search]'));
    var noResults = document.getElementById('menu-no-results');

    /** The chosen category; 'all' is the tab the menu opens on. */
    var active = 'all';

    /** The search term, lowercased. The two filters compose. */
    var term = '';

    /** Tell the analytics (menu-track.js) what a guest did. */
    function track(detail) {
        document.dispatchEvent(new CustomEvent('qayema:track', { detail: detail }));
    }

    /** Whether anything is showing — a search that finds nothing is worth knowing about. */
    function apply() {
        var anyShown = false;

        sections.forEach(function (section) {
            var inCategory = active === 'all' || section.id === active;
            var shown = 0;

            Array.prototype.forEach.call(section.querySelectorAll('.dish'), function (dish) {
                var match = inCategory && (term === '' || (dish.dataset.search || '').indexOf(term) !== -1);
                dish.hidden = !match;
                if (match) {
                    shown++;
                }
            });

            section.hidden = shown === 0;
            anyShown = anyShown || shown > 0;
        });

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
        tabs.forEach(function (other) {
            other.setAttribute('aria-current', other === tab ? 'true' : 'false');
        });

        active = tab.dataset.tab;

        apply();
        movePill();
        revealTab();

        // Only pull the page back when it is already scrolled past the first
        // section — picking a tab while the cover is still in view should not
        // make the page jump.
        var first = sections.filter(function (section) {
            return !section.hidden;
        })[0];

        if (first && first.getBoundingClientRect().top < 0) {
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

    if (pill && tabs.length > 0) {
        movePill();

        window.requestAnimationFrame(function () {
            movePill();
            pill.classList.add('ready');
        });

        window.addEventListener('resize', movePill);

        // Web fonts land after first paint and change every tab's width.
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(movePill);
        }
    }

    // ---- Popups ----------------------------------------------------------
    //
    // Every dock action opens one, so they are wired once. The dock item it
    // came from stays lit for as long as its popup is up.

    var qrCode = document.querySelector('[data-qr-canvas]');

    /** Pull in the generator on first use — it is far too big to ship eagerly. */
    function withGenerator(then, otherwise) {
        if (window.qrcode) {
            then();

            return;
        }

        var tag = document.createElement('script');
        tag.src = qrCode.dataset.lib;
        tag.onload = then;
        // A guest on a bad connection must not be left with a lit button and
        // no popup, so a failed fetch puts the dock back as it was.
        tag.onerror = otherwise;
        document.head.appendChild(tag);
    }

    function drawCode() {
        if (qrCode.firstChild) {
            return;
        }

        var code = window.qrcode(0, 'M');
        code.addData(qrCode.dataset.url);
        code.make();
        qrCode.innerHTML = code.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
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

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-scroll-top]')) {
            // The dock is a phone-only thing, so the window is what scrolls.
            window.scrollTo({ top: 0, behavior: 'smooth' });

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
            pop.opener = item;
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
    // There are two inputs — one in the header for a wide screen, one under the
    // cover for a phone — and only ever one of them is visible. They share a
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

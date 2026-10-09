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

    /** Whether anything is showing; a search that finds nothing is worth knowing about. */
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
            var watcher = new ResizeObserver(movePill);

            watcher.observe(inner);
            tabs.forEach(function (tab) {
                watcher.observe(tab);
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

    /** A dish's full photo, the one its card shows small (the `thumb`). */
    function showPhoto(dish) {
        if (!photoPop || !dish) {
            return;
        }

        photoView.src = dish.dataset.photo || dish.dataset.image;
        photoView.alt = dish.dataset.name;
        photoPop.setAttribute('aria-label', dish.dataset.name);
        photoPop.showModal();
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

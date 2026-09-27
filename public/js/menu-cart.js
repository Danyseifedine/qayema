/**
 * The guest's cart on a public menu.
 *
 * Everything here is convenience: the quantities live in localStorage so a
 * reload or a phone locking does not lose the order, and the totals shown are
 * only a preview. The server re-reads every dish and recomputes every price
 * when the order is placed, so nothing in this file is trusted.
 */
(function () {
    'use strict';

    var config = window.QAYEMA_MENU;
    if (!config) {
        return;
    }

    var strings = config.strings;
    var icons = config.icons;
    var sheet = document.getElementById('cart-sheet');
    var foot = document.querySelector('[data-cart-foot]');
    var toggle = document.querySelector('[data-cart-toggle]');
    var panels = Array.prototype.slice.call(document.querySelectorAll('[data-cart-panel]'));

    /** dish id -> quantity. Ids are strings because dataset values are. */
    var cart = load();

    /** What each dish's action slot currently shows, so a repaint that would
     *  change nothing is skipped and only a real change animates. */
    var painted = {};

    /** The last count rendered, so the counters only pulse on a change. */
    var counted = null;

    function load() {
        try {
            var stored = JSON.parse(window.localStorage.getItem(config.storageKey) || '{}');
            return stored && typeof stored === 'object' ? stored : {};
        } catch (error) {
            // Private mode, blocked storage, corrupted value: start empty
            // rather than break the menu.
            return {};
        }
    }

    function save() {
        try {
            window.localStorage.setItem(config.storageKey, JSON.stringify(cart));
        } catch (error) {
            // Not being able to remember the cart is survivable.
        }
    }

    function dishes() {
        return Array.prototype.slice.call(document.querySelectorAll('.dish[data-price]:not([data-price=""])'));
    }

    function money(amount) {
        return config.currency + amount.toFixed(2);
    }

    function priceOf(element) {
        return parseFloat(element.dataset.price || '0') || 0;
    }

    /** Drop anything no longer on the menu, so a stale cart cannot linger. */
    function prune() {
        var live = {};
        dishes().forEach(function (dish) {
            live[dish.dataset.dish] = true;
        });

        Object.keys(cart).forEach(function (id) {
            if (!live[id]) {
                delete cart[id];
            }
        });
    }

    function lines() {
        return dishes()
            .filter(function (dish) {
                return (cart[dish.dataset.dish] || 0) > 0;
            })
            .map(function (dish) {
                var quantity = cart[dish.dataset.dish];
                return {
                    id: dish.dataset.dish,
                    name: dish.dataset.name,
                    image: dish.dataset.image || '',
                    unit: priceOf(dish),
                    quantity: quantity,
                    total: priceOf(dish) * quantity,
                };
            });
    }

    function totals() {
        return lines().reduce(
            function (carry, line) {
                carry.count += line.quantity;
                carry.amount += line.total;
                return carry;
            },
            { count: 0, amount: 0 }
        );
    }

    function setQuantity(id, quantity) {
        // Every step up is one "added to cart" for the owner's analytics.
        if (quantity > (cart[id] || 0)) {
            document.dispatchEvent(new CustomEvent('qayema:track', {
                detail: { type: 'dish_add', dish_id: parseInt(id, 10) },
            }));
        }

        if (quantity > 0) {
            cart[id] = Math.min(quantity, 99);
        } else {
            delete cart[id];
        }

        save();
        render();
    }

    // ---- Rendering -------------------------------------------------------

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

    /** A button whose only child is one of the design's icons. */
    function iconButton(className, markup, label, onClick) {
        var button = element('button', className);
        button.type = 'button';
        button.setAttribute('aria-label', label);

        var glyph = element('span', 'icon');
        glyph.innerHTML = markup;
        button.appendChild(glyph);

        button.addEventListener('click', onClick);

        return button;
    }

    function stepper(id, quantity) {
        var wrap = element('div', 'qty');

        wrap.appendChild(iconButton(null, icons.minus, strings.remove, function () {
            setQuantity(id, quantity - 1);
        }));
        wrap.appendChild(element('output', null, String(quantity)));
        wrap.appendChild(iconButton('plus', icons.plus, strings.add, function () {
            setQuantity(id, quantity + 1);
        }));

        return wrap;
    }

    function renderDishActions() {
        dishes().forEach(function (dish) {
            var slot = dish.querySelector('.dish-action');
            if (!slot) {
                return;
            }

            var id = dish.dataset.dish;
            var quantity = cart[id] || 0;
            var before = painted[id];

            if (before === quantity && slot.firstChild) {
                return;
            }

            // Only swapping the add button for the stepper is worth animating.
            // Stepping 2 → 3 should not make the control jump, and the first
            // paint of the page should not animate at all.
            var entering = before !== undefined && (before === 0) !== (quantity === 0);

            painted[id] = quantity;
            slot.textContent = '';

            var control = quantity > 0
                ? stepper(id, quantity)
                : iconButton('add', icons.plus, strings.add + ' ' + dish.dataset.name, function () {
                    setQuantity(id, 1);
                });

            if (entering) {
                control.classList.add('enter');
            }

            slot.appendChild(control);
        });
    }

    function renderLine(line) {
        var row = element('div', 'cart-line');

        if (line.image) {
            var photo = document.createElement('img');
            photo.className = 'cart-line-photo';
            photo.src = line.image;
            photo.alt = '';
            photo.loading = 'lazy';
            row.appendChild(photo);
        }

        var body = element('div', 'cart-line-body');
        body.appendChild(element('div', 'cart-line-name', line.name));
        body.appendChild(element('div', 'cart-line-each', money(line.unit) + ' ' + strings.each));

        var bottom = element('div', 'cart-line-foot');
        bottom.appendChild(stepper(line.id, line.quantity));
        bottom.appendChild(element('div', 'cart-line-total', money(line.total)));
        body.appendChild(bottom);

        row.appendChild(body);

        return row;
    }

    function renderPanel(panel) {
        var current = lines();
        panel.textContent = '';

        // The sheet keeps Place order in its own footer bar, the way the design
        // does; the desktop column has no footer and keeps it inline.
        var footer = foot && panel.parentNode && panel.parentNode.contains(foot) ? foot : null;
        if (footer) {
            footer.textContent = '';
        }

        if (current.length === 0) {
            panel.appendChild(element('p', 'cart-empty', strings.empty));
            return;
        }

        var sum = totals();

        var noun = sum.count === 1 ? strings.item : strings.items;
        panel.appendChild(element('div', 'cart-items', sum.count + ' ' + noun));

        var list = element('div', 'cart-lines');
        current.forEach(function (line) {
            list.appendChild(renderLine(line));
        });
        panel.appendChild(list);

        var summary = element('div', 'cart-summary');
        var totalRow = element('div', 'cart-total');
        totalRow.appendChild(element('span', null, strings.total));
        totalRow.appendChild(element('span', null, money(sum.amount)));
        summary.appendChild(totalRow);
        panel.appendChild(summary);

        var error = element('p', 'cart-error');
        error.hidden = true;
        panel.appendChild(error);

        var place = element('button', 'place split');
        place.type = 'button';
        place.appendChild(element('span', null, strings.place));
        place.appendChild(element('span', null, money(sum.amount)));
        place.addEventListener('click', function () {
            submit(place, error);
        });
        (footer || panel).appendChild(place);
    }

    function render() {
        prune();
        renderDishActions();

        var sum = totals();

        // The header button wears its count only once there is one.
        if (toggle) {
            toggle.classList.toggle('on', sum.count > 0);
        }

        var changed = counted !== null && counted !== sum.count;
        counted = sum.count;
        Array.prototype.forEach.call(document.querySelectorAll('[data-cart-count]'), function (node) {
            node.textContent = String(sum.count);
            if (changed) {
                pulse(node);
            }
        });
        Array.prototype.forEach.call(document.querySelectorAll('[data-cart-total]'), function (node) {
            node.textContent = money(sum.amount);
        });

        panels.forEach(renderPanel);

        if (sheet && sheet.open && sum.count === 0) {
            closeSheet();
        }
    }

    /** Restart the pulse even on a rapid second tap. */
    function pulse(node) {
        node.classList.remove('pop');
        void node.offsetWidth;
        node.classList.add('pop');
    }

    // ---- Placing ---------------------------------------------------------

    function submit(button, error) {
        var current = lines();
        if (current.length === 0) {
            return;
        }

        var token = document.querySelector('meta[name="csrf-token"]');

        button.disabled = true;
        button.classList.remove('split');
        button.textContent = strings.placing;
        error.hidden = true;

        window
            .fetch(config.orderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    items: current.map(function (line) {
                        return { dish_id: Number(line.id), quantity: line.quantity };
                    }),
                    // The language the guest is reading, so the WhatsApp
                    // message and any error come back in it.
                    locale: config.locale,
                }),
            })
            .then(function (response) {
                return response.json().then(function (body) {
                    return { ok: response.ok, body: body };
                });
            })
            .then(function (result) {
                if (!result.ok) {
                    throw new Error((result.body && result.body.message) || strings.failed);
                }

                // The order is stored; WhatsApp is what actually reaches the
                // owner. Clear first so a back-navigation shows an empty cart.
                cart = {};
                save();
                render();

                var url = result.body.data && result.body.data.whatsapp_url;
                if (url) {
                    window.location.href = url;
                }
            })
            .catch(function (failure) {
                error.textContent = failure.message || strings.failed;
                error.hidden = false;
            })
            .finally(function () {
                button.disabled = false;
                render();
            });
    }

    // ---- Wiring ----------------------------------------------------------

    /**
     * The sheet is the phone's cart. On a wide screen it is hidden and the cart
     * is a column of its own, so opening it there would leave an invisible
     * modal over the page and every real click blocked behind it.
     */
    var wide = window.matchMedia('(min-width: 1024px)');

    function openSheet() {
        if (sheet && !sheet.open && !wide.matches) {
            sheet.classList.remove('closing');
            sheet.showModal();
        }
    }

    // A phone turned sideways, or a window dragged wider, while the sheet is
    // up would strand it the same way, so it closes as the layout changes.
    wide.addEventListener('change', function (event) {
        if (event.matches && sheet && sheet.open) {
            sheet.classList.remove('closing');
            sheet.close();
        }
    });

    /**
     * A dialog closes instantly, which kills any exit animation. So the sheet
     * is held open for the length of the slide-out and closed after it — with
     * a timer in case no animation runs at all.
     */
    function closeSheet() {
        if (!sheet || !sheet.open || sheet.classList.contains('closing')) {
            return;
        }

        sheet.classList.add('closing');

        var timer = window.setTimeout(finish, 400);

        function finish() {
            window.clearTimeout(timer);
            sheet.removeEventListener('animationend', onEnd);
            sheet.classList.remove('closing');
            sheet.close();
        }

        function onEnd(event) {
            // Child animations bubble; only the sheet's own matters here.
            if (event.target === sheet) {
                finish();
            }
        }

        sheet.addEventListener('animationend', onEnd);
    }

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-open-cart]')) {
            openSheet();
        }
        if (event.target.closest('[data-close-cart]')) {
            closeSheet();
        }
    });

    if (sheet) {
        // Tapping the backdrop closes it, the way a sheet should.
        sheet.addEventListener('click', function (event) {
            if (event.target === sheet) {
                closeSheet();
            }
        });

        // Escape would close instantly and skip the animation.
        sheet.addEventListener('cancel', function (event) {
            event.preventDefault();
            closeSheet();
        });
    }

    render();
})();

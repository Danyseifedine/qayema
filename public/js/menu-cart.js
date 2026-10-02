/**
 * The guest's cart on a public menu.
 *
 * Everything here is convenience: the quantities live in localStorage so a
 * reload or a phone locking does not lose the order, and the totals shown are
 * only a preview. The server re-reads every dish and recomputes every price
 * when the order is placed, so nothing in this file is trusted.
 *
 * A line is a dish with the guest's choices (its variants' options and its
 * add-ons, picked in the dish sheet, menu-dish.js), so the same burger can
 * be in the cart as a Small and as a Large.
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

    /** Each dish's choices, by dish id (App\Services\Menu\MenuDishOptions). */
    var choices = readChoices();

    /** line key -> { dish, options, addons, qty }. Dish ids are strings
     *  because dataset values are; choice ids are numbers. */
    var cart = load();

    /** What each dish's action slot currently shows, so a repaint that would
     *  change nothing is skipped and only a real change animates. */
    var painted = {};

    /** The last count rendered, so the counters only pulse on a change. */
    var counted = null;

    function readChoices() {
        var source = document.getElementById('dish-options');
        try {
            return source ? JSON.parse(source.textContent || '{}') : {};
        } catch (error) {
            return {};
        }
    }

    function sorted(ids) {
        return ids.map(Number).sort(function (a, b) {
            return a - b;
        });
    }

    function keyOf(dish, options, addons) {
        return dish + '|' + sorted(options).join(',') + '|' + sorted(addons).join(',');
    }

    function load() {
        var stored;
        try {
            stored = JSON.parse(window.localStorage.getItem(config.storageKey) || '{}');
        } catch (error) {
            // Private mode, blocked storage, corrupted value: start empty
            // rather than break the menu.
            return {};
        }

        var lines = {};
        if (!stored || typeof stored !== 'object') {
            return lines;
        }

        Object.keys(stored).forEach(function (key) {
            var value = stored[key];

            // A cart saved before dishes had choices: dish id -> quantity.
            if (typeof value === 'number') {
                lines[keyOf(key, [], [])] = { dish: key, options: [], addons: [], qty: value };
            } else if (value && typeof value === 'object' && Array.isArray(value.options) && Array.isArray(value.addons)) {
                lines[keyOf(value.dish, value.options, value.addons)] = {
                    dish: String(value.dish),
                    options: sorted(value.options),
                    addons: sorted(value.addons),
                    qty: Number(value.qty) || 0,
                };
            }
        });

        return lines;
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

    function findDish(id) {
        return document.querySelector('.dish[data-dish="' + id + '"][data-price]:not([data-price=""])');
    }

    /**
     * The line's choices as the menu offers them now: one option of each
     * variant and add-ons of this dish, or null when that no longer holds
     * (the owner changed the dish, or switched its choices off).
     */
    function picked(line) {
        var data = choices[line.dish];

        if (!data) {
            return line.options.length === 0 && line.addons.length === 0 ? { extra: 0, labels: [] } : null;
        }

        var extra = 0;
        var labels = [];
        var used = 0;

        for (var v = 0; v < data.variants.length; v++) {
            var hits = data.variants[v].options.filter(function (option) {
                return line.options.indexOf(option.id) !== -1;
            });
            if (hits.length !== 1) {
                return null;
            }
            used++;
            extra += parseFloat(hits[0].price) || 0;
            labels.push(hits[0].name);
        }

        if (used !== line.options.length) {
            return null;
        }

        var addons = data.addons.filter(function (addon) {
            return line.addons.indexOf(addon.id) !== -1;
        });
        if (addons.length !== line.addons.length) {
            return null;
        }
        addons.forEach(function (addon) {
            extra += parseFloat(addon.price) || 0;
            labels.push('+ ' + addon.name);
        });

        return { extra: extra, labels: labels };
    }

    /** Drop anything no longer on the menu, so a stale cart cannot linger. */
    function prune() {
        Object.keys(cart).forEach(function (key) {
            if (!(cart[key].qty > 0) || !findDish(cart[key].dish) || !picked(cart[key])) {
                delete cart[key];
            }
        });
    }

    /** The lines in the menu's order, a dish's lines in the order added. */
    function lines() {
        var result = [];

        dishes().forEach(function (dish) {
            Object.keys(cart).forEach(function (key) {
                var line = cart[key];
                if (line.dish !== dish.dataset.dish) {
                    return;
                }

                var choice = picked(line);
                var unit = priceOf(dish) + choice.extra;
                result.push({
                    key: key,
                    id: line.dish,
                    options: line.options,
                    addons: line.addons,
                    name: dish.dataset.name,
                    choices: choice.labels.join(', '),
                    image: dish.dataset.image || '',
                    unit: unit,
                    quantity: line.qty,
                    total: unit * line.qty,
                });
            });
        });

        return result;
    }

    /** How many of a dish are in the cart, whatever was chosen. */
    function countOf(id) {
        return Object.keys(cart).reduce(function (count, key) {
            return cart[key].dish === id ? count + cart[key].qty : count;
        }, 0);
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

    /**
     * @param {string} key  the line, from keyOf()
     * @param {{dish: string, options: number[], addons: number[]}} what  the line, when it is new
     */
    function setQuantity(key, quantity, what) {
        var line = cart[key] || { dish: what.dish, options: sorted(what.options), addons: sorted(what.addons), qty: 0 };

        // Every step up is one "added to cart" for the owner's analytics.
        if (quantity > line.qty) {
            document.dispatchEvent(new CustomEvent('qayema:track', {
                detail: { type: 'dish_add', dish_id: parseInt(line.dish, 10) },
            }));
        }

        if (quantity > 0) {
            line.qty = Math.min(quantity, 99);
            cart[key] = line;
        } else {
            delete cart[key];
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

    function stepper(key, quantity, what) {
        var wrap = element('div', 'qty');

        wrap.appendChild(iconButton(null, icons.minus, strings.remove, function () {
            setQuantity(key, quantity - 1, what);
        }));
        wrap.appendChild(element('output', null, String(quantity)));
        wrap.appendChild(iconButton('plus', icons.plus, strings.add, function () {
            setQuantity(key, quantity + 1, what);
        }));

        return wrap;
    }

    /**
     * A dish with choices gets the arrow a menu without ordering shows: it
     * opens the dish's sheet (menu-dish.js), where the guest picks and adds.
     * A + would promise an instant add. How many are in the cart already
     * sits on it as a badge.
     */
    function chooseButton(dish, count) {
        var button = iconButton('dish-more', icons.chevron, strings.options + ': ' + dish.dataset.name, function () {});
        button.setAttribute('data-dish-open', '');
        button.setAttribute('aria-haspopup', 'dialog');

        if (count > 0) {
            button.appendChild(element('span', 'add-count', String(count)));
        }

        return button;
    }

    function renderDishActions() {
        dishes().forEach(function (dish) {
            var slot = dish.querySelector('.dish-action');
            if (!slot) {
                return;
            }

            var id = dish.dataset.dish;
            var hasChoices = dish.hasAttribute('data-choices');
            var what = { dish: id, options: [], addons: [] };
            var key = keyOf(id, [], []);
            var quantity = hasChoices ? countOf(id) : (cart[key] ? cart[key].qty : 0);
            var before = painted[id];

            if (before === quantity && slot.firstChild) {
                return;
            }

            // Only swapping the add button for the stepper is worth animating.
            // Stepping 2 → 3 should not make the control jump, and the first
            // paint of the page should not animate at all.
            var entering = !hasChoices && before !== undefined && (before === 0) !== (quantity === 0);

            painted[id] = quantity;
            slot.textContent = '';

            var control;
            if (hasChoices) {
                control = chooseButton(dish, quantity);
            } else if (quantity > 0) {
                control = stepper(key, quantity, what);
            } else {
                control = iconButton('add', icons.plus, strings.add + ' ' + dish.dataset.name, function () {
                    setQuantity(key, 1, what);
                });
            }

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
        if (line.choices) {
            body.appendChild(element('div', 'cart-line-choices', line.choices));
        }
        body.appendChild(element('div', 'cart-line-each', money(line.unit) + ' ' + strings.each));

        var bottom = element('div', 'cart-line-foot');
        bottom.appendChild(stepper(line.key, line.quantity, { dish: line.id, options: line.options, addons: line.addons }));
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
                        return { dish_id: Number(line.id), quantity: line.quantity, options: line.options, addons: line.addons };
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
     * is held open for the length of the slide-out and closed after it, with
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

    // A dish and its choices, from the dish sheet (menu-dish.js).
    document.addEventListener('qayema:add', function (event) {
        var what = event.detail;
        var key = keyOf(what.dish, what.options, what.addons);
        var quantity = cart[key] ? cart[key].qty : 0;

        setQuantity(key, quantity + what.quantity, what);
    });

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

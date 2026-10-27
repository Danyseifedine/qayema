/**
 * The dish sheet: a dish's variants, add-ons and "build your own" (half and
 * half pizzas, three scoops), picked before it goes in the cart. A build is
 * priced by menu-build.js, the server's rule.
 *
 * The choices come from the page (#dish-options, built by
 * App\Services\Menu\MenuDishOptions). Picking is all this file does: a menu
 * that takes orders hands the result to menu-cart.js as a `qayema:add`
 * event, and one that does not just shows the choices and their prices. The
 * price shown here is a preview; the server prices every order itself.
 */
(function () {
    'use strict';

    var config = window.QAYEMA_DISH;
    var sheet = document.getElementById('dish-sheet');
    var source = document.getElementById('dish-options');
    if (!config || !sheet || !source) {
        return;
    }

    var choices;
    try {
        choices = JSON.parse(source.textContent || '{}');
    } catch (error) {
        return;
    }

    var strings = config.strings;
    var icons = config.icons;
    var photo = sheet.querySelector('[data-dish-photo]');
    var title = sheet.querySelector('[data-dish-title]');
    var ingredients = sheet.querySelector('[data-dish-ingredients]');
    var priceTag = sheet.querySelector('[data-dish-price]');
    var groups = sheet.querySelector('[data-dish-groups]');
    var foot = sheet.querySelector('[data-dish-foot]');

    /** What the guest is choosing right now. */
    var state = null;

    function money(amount) {
        // As App\Support\Price: no decimals on a whole amount, two otherwise,
        // in the dish's own currency (a dish priced in the second one).
        var rounded = Math.round(amount * 100) / 100;
        var whole = rounded === Math.floor(rounded);
        var sign = ((state && state.symbol) || config.currency);
        // A sign stays on the number ("$5"), letters get a small space ("L.L 89,000"), as App\Support\Price::symbol().
        return (sign.length > 1 ? sign + ' ' : sign) + rounded.toLocaleString('en-US', {
            minimumFractionDigits: whole ? 0 : 2,
            maximumFractionDigits: whole ? 0 : 2,
        });
    }

    function extra(amount) {
        return '+' + money(amount);
    }

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

    function icon(markup) {
        var glyph = element('span', 'icon');
        glyph.innerHTML = markup;
        return glyph;
    }

    function unitPrice() {
        var total = state.base;
        state.data.variants.forEach(function (variant) {
            variant.options.forEach(function (option) {
                if (state.picked[variant.id] === option.id) {
                    total += parseFloat(option.price) || 0;
                }
            });
        });
        state.data.addons.forEach(function (addon) {
            if (state.addons[addon.id]) {
                total += parseFloat(addon.price) || 0;
            }
        });
        (state.data.builds || []).forEach(function (build) {
            total += window.QAYEMA_BUILD.price(build.shape, chosenOf(build));
        });
        return total;
    }

    /** The choices picked for a build's parts, in order. */
    function chosenOf(build) {
        return state.builds[build.id].parts.map(function (key) {
            return build.choices.filter(function (choice) {
                return choice.key === key;
            })[0];
        });
    }

    // ---- Drawing ---------------------------------------------------------

    function legend(name, tag) {
        var node = element('legend', 'choice-legend');
        node.appendChild(element('span', 'choice-title', name));
        node.appendChild(element('span', 'choice-tag', tag));
        return node;
    }

    /**
     * What a choice adds, or "Free" for one with no price. `full` is for the
     * first variant of a dish with no price of its own: its options are the
     * prices themselves (Small $7.00), not extras.
     */
    function priceLabel(price, full) {
        var amount = parseFloat(price) || 0;
        if (full) {
            return element('span', 'choice-price', money(amount));
        }
        return amount > 0
            ? element('span', 'choice-price', extra(amount))
            : element('span', 'choice-price is-free', strings.free);
    }

    function variantGroup(variant, full) {
        var set = element('fieldset', 'choice-group');
        set.appendChild(legend(variant.name, strings.pickOne));

        var pills = element('div', 'choice-pills');
        variant.options.forEach(function (option) {
            var label = element('label', 'choice-pill');
            var input = document.createElement('input');
            input.type = 'radio';
            input.name = 'variant-' + variant.id;
            input.value = String(option.id);
            input.checked = state.picked[variant.id] === option.id;
            input.addEventListener('change', function () {
                state.picked[variant.id] = option.id;
                refresh();
            });

            label.appendChild(input);
            label.appendChild(element('span', 'choice-name', option.name));
            label.appendChild(priceLabel(option.price, full));
            pills.appendChild(label);
        });

        set.appendChild(pills);
        return set;
    }

    function addonGroup(addons) {
        var set = element('fieldset', 'choice-group');
        set.appendChild(legend(strings.addons, strings.optional));

        var rows = element('div', 'choice-rows');
        addons.forEach(function (addon) {
            var label = element('label', 'choice-row');
            var input = document.createElement('input');
            input.type = 'checkbox';
            input.value = String(addon.id);
            input.checked = !!state.addons[addon.id];
            input.addEventListener('change', function () {
                state.addons[addon.id] = input.checked;
                refresh();
            });

            var box = element('span', 'choice-check');
            box.appendChild(icon(icons.check));

            label.appendChild(input);
            label.appendChild(box);
            label.appendChild(element('span', 'choice-name', addon.name));
            label.appendChild(priceLabel(addon.price));
            rows.appendChild(label);
        });

        set.appendChild(rows);
        return set;
    }

    /** "Whole", "Half & half", "Thirds", "Quarters", "5 parts"; "3 pieces". */
    function countLabel(shape, count) {
        if (shape !== 'split') {
            return strings.pieces.replace(':count', count);
        }
        return [null, strings.whole, strings.halves, strings.thirds, strings.quarters][count] || strings.parts.replace(':count', count);
    }

    /** A pizza cut into `count` parts, each numbered in turn. */
    function pie(count) {
        var ns = 'http://www.w3.org/2000/svg';
        var svg = document.createElementNS(ns, 'svg');
        svg.setAttribute('viewBox', '-52 -52 104 104');
        svg.setAttribute('class', 'build-pie');
        svg.setAttribute('aria-hidden', 'true');

        var disc = document.createElementNS(ns, 'circle');
        disc.setAttribute('r', '48');
        svg.appendChild(disc);

        for (var i = 0; i < count; i++) {
            var angle = (2 * Math.PI * i) / count - Math.PI / 2;
            if (count > 1) {
                var cut = document.createElementNS(ns, 'line');
                cut.setAttribute('x2', String(48 * Math.cos(angle)));
                cut.setAttribute('y2', String(48 * Math.sin(angle)));
                svg.appendChild(cut);
            }

            var middle = angle + Math.PI / count;
            var number = document.createElementNS(ns, 'text');
            number.setAttribute('x', String(count > 1 ? 26 * Math.cos(middle) : 0));
            number.setAttribute('y', String(count > 1 ? 26 * Math.sin(middle) : 0));
            number.textContent = String(i + 1);
            svg.appendChild(number);
        }

        return svg;
    }

    /**
     * A build: how many parts (when it allows more than one count), the
     * pizza cut that way for a split one, and a choice for each part with
     * what that part costs. No heading of its own: the sheet's title is the
     * dish's name, which the build shares; the group is named for a screen
     * reader. Drawn again when the count changes.
     */
    function buildGroup(build) {
        var set = element('fieldset', 'choice-group build-group');
        var picked = state.builds[build.id];
        set.setAttribute('aria-label', build.name);

        if (build.parts.length > 1) {
            var counts = element('div', 'choice-pills');
            build.parts.forEach(function (count) {
                var label = element('label', 'choice-pill');
                var input = document.createElement('input');
                input.type = 'radio';
                input.name = 'build-' + build.id;
                input.value = String(count);
                input.checked = picked.parts.length === count;
                input.addEventListener('change', function () {
                    // Parts already picked stay; new ones start on the first choice.
                    var parts = picked.parts.slice(0, count);
                    while (parts.length < count) {
                        parts.push(build.choices[0].key);
                    }
                    picked.parts = parts;
                    set.replaceWith(buildGroup(build));
                    refresh();
                });
                label.appendChild(input);
                label.appendChild(element('span', 'choice-name', countLabel(build.shape, count)));
                counts.appendChild(label);
            });
            set.appendChild(counts);
        }

        var body = element('div', 'build-body');
        if (build.shape === 'split') {
            body.appendChild(pie(picked.parts.length));
        }
        var rows = element('div', 'build-parts');
        picked.parts.forEach(function (key, index) {
            var row = element('div', 'build-part');
            row.appendChild(partPicker(build, picked, index));
            rows.appendChild(row);
        });

        body.appendChild(rows);
        set.appendChild(body);
        return set;
    }

    /** What a choice costs as one of `count` parts, or "Free". */
    function partPriceLabel(build, choice, count) {
        var part = window.QAYEMA_BUILD.partPrice(build.shape, choice, count);
        return part > 0 ? money(part) : strings.free;
    }

    /** The part picker open now, closed by a tap elsewhere or Escape. */
    var openPicker = null;

    function closePicker(focusButton) {
        if (!openPicker) {
            return;
        }
        var picker = openPicker;
        openPicker = null;
        picker.list.hidden = true;
        picker.button.setAttribute('aria-expanded', 'false');
        if (focusButton) {
            picker.button.focus();
        }
    }

    /**
     * One part's choice: a button showing the part's number, the choice and
     * what it costs,
     * opening a list of every choice (a listbox: arrows, Home and End move,
     * Enter or Space picks, Escape closes it and only it).
     */
    function partPicker(build, picked, index) {
        var wrap = element('div', 'build-pick');
        var count = picked.parts.length;
        var label = strings.part.replace(':number', index + 1) + ', ' + build.name;

        var button = element('button', 'build-pick-button');
        button.type = 'button';
        button.setAttribute('aria-haspopup', 'listbox');
        button.setAttribute('aria-expanded', 'false');
        var shownName = element('span', 'build-pick-name');
        var shownPrice = element('span', 'build-pick-price');
        // The part's number, as its slice is numbered in the pizza above.
        button.appendChild(element('span', 'build-pick-number', String(index + 1)));
        button.appendChild(shownName);
        button.appendChild(shownPrice);
        button.appendChild(icon(config.icons.chevron)).classList.add('build-pick-chevron');

        var list = element('ul', 'build-pick-list');
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', label);
        list.hidden = true;

        var options = build.choices.map(function (choice) {
            var option = element('li', 'build-pick-option');
            option.setAttribute('role', 'option');
            option.tabIndex = -1;
            option.dataset.key = choice.key;
            option.appendChild(element('span', 'build-pick-name', choice.name));
            option.appendChild(element('span', 'build-pick-price', partPriceLabel(build, choice, count)));
            option.appendChild(icon(icons.check)).classList.add('build-pick-check');
            option.addEventListener('click', function () {
                choose(choice.key);
            });
            list.appendChild(option);
            return option;
        });

        function show() {
            var key = picked.parts[index];
            var choice = build.choices.filter(function (each) { return each.key === key; })[0] || build.choices[0];
            shownName.textContent = choice.name;
            shownPrice.textContent = partPriceLabel(build, choice, count);
            button.setAttribute('aria-label', label + ': ' + choice.name + ', ' + shownPrice.textContent);
            options.forEach(function (option) {
                option.setAttribute('aria-selected', option.dataset.key === choice.key ? 'true' : 'false');
            });
        }

        function choose(key) {
            picked.parts[index] = key;
            show();
            refresh();
            closePicker(true);
        }

        function openList() {
            closePicker(false);
            openPicker = { list: list, button: button };
            list.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            // The whole list in sight (the sheet scrolls), its choice in focus.
            list.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            var current = list.querySelector('[aria-selected="true"]') || options[0];
            current.focus({ preventScroll: true });
        }

        button.addEventListener('click', function () {
            if (openPicker && openPicker.list === list) {
                closePicker(true);
            } else {
                openList();
            }
        });
        button.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                openList();
            }
        });
        list.addEventListener('keydown', function (event) {
            var at = options.indexOf(document.activeElement);
            var next = null;
            if (event.key === 'ArrowDown') {
                next = options[Math.min(options.length - 1, at + 1)];
            } else if (event.key === 'ArrowUp') {
                next = options[Math.max(0, at - 1)];
            } else if (event.key === 'Home') {
                next = options[0];
            } else if (event.key === 'End') {
                next = options[options.length - 1];
            } else if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                if (at !== -1) {
                    choose(options[at].dataset.key);
                }
                return;
            } else if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                closePicker(true);
                return;
            } else if (event.key === 'Tab') {
                closePicker(false);
                return;
            }
            if (next) {
                event.preventDefault();
                next.focus();
            }
        });

        show();
        wrap.appendChild(button);
        wrap.appendChild(list);
        return wrap;
    }

    function stepButton(className, markup, label, onClick) {
        var button = element('button', className);
        button.type = 'button';
        button.setAttribute('aria-label', label);
        button.appendChild(icon(markup));
        button.addEventListener('click', onClick);
        return button;
    }

    function drawFoot() {
        foot.textContent = '';

        if (!config.canOrder) {
            return;
        }

        var stepper = element('div', 'qty dish-sheet-qty');
        stepper.setAttribute('role', 'group');
        stepper.setAttribute('aria-label', strings.quantity);
        var minus = stepButton(null, icons.minus, strings.remove, function () {
            state.quantity = Math.max(1, state.quantity - 1);
            refresh();
        });
        state.minus = minus;
        stepper.appendChild(minus);
        state.count = element('output', null, '1');
        stepper.appendChild(state.count);
        stepper.appendChild(stepButton('plus', icons.plus, strings.add, function () {
            state.quantity = Math.min(99, state.quantity + 1);
            refresh();
        }));

        var add = element('button', 'place split dish-sheet-add');
        add.type = 'button';
        add.appendChild(element('span', null, strings.add));
        state.total = element('span', null, '');
        add.appendChild(state.total);
        add.addEventListener('click', addToCart);

        foot.appendChild(stepper);
        foot.appendChild(add);
    }

    /** Only the numbers change as the guest picks, so nothing jumps. */
    function refresh() {
        var unit = unitPrice();
        priceTag.textContent = money(unit);

        if (state.total) {
            state.total.textContent = money(unit * state.quantity);
            state.count.textContent = String(state.quantity);
            state.minus.disabled = state.quantity <= 1;
        }
    }

    // ---- Opening and closing ---------------------------------------------

    function open(dish) {
        var data = choices[dish.dataset.dish];
        if (!data) {
            return;
        }

        openPicker = null;

        state = {
            id: dish.dataset.dish,
            data: data,
            base: parseFloat(dish.dataset.price || '0') || 0,
            symbol: dish.dataset.currency || '',
            // The first option of each variant is the one most guests want.
            picked: {},
            addons: {},
            // Each build starts on its fewest parts, every part its first choice.
            builds: {},
            quantity: 1,
        };
        data.variants.forEach(function (variant) {
            state.picked[variant.id] = variant.options[0].id;
        });
        (data.builds || []).forEach(function (build) {
            state.builds[build.id] = {
                parts: Array.apply(null, Array(build.parts[0])).map(function () {
                    return build.choices[0].key;
                }),
            };
        });

        title.textContent = dish.dataset.name || '';
        ingredients.textContent = dish.dataset.ingredients || '';
        ingredients.hidden = !dish.dataset.ingredients;

        // The full photo, never the last dish's: the card's small one, already
        // loaded, at once (the box is the sheet's width, so it takes the same
        // place), then the full one once it has arrived, if this dish is
        // still the one open.
        var full = dish.dataset.photo;
        var small = dish.dataset.image;
        photo.removeAttribute('src');
        photo.hidden = !full;
        photo.dataset.full = full || '';

        if (full) {
            photo.src = small || full;

            if (small && small !== full) {
                var loader = new Image();
                loader.onload = function () {
                    if (photo.dataset.full === full) {
                        photo.src = full;
                    }
                };
                loader.src = full;
            }
        }

        groups.textContent = '';
        (data.builds || []).forEach(function (build) {
            groups.appendChild(buildGroup(build));
        });
        data.variants.forEach(function (variant, index) {
            groups.appendChild(variantGroup(variant, index === 0 && !data.priced));
        });
        if (data.addons.length > 0) {
            groups.appendChild(addonGroup(data.addons));
        }

        drawFoot();
        refresh();

        sheet.classList.remove('closing');
        sheet.querySelector('.dish-sheet-scroll').scrollTop = 0;
        sheet.showModal();
    }

    function addToCart() {
        var options = [];
        Object.keys(state.picked).forEach(function (variant) {
            options.push(state.picked[variant]);
        });
        var addons = [];
        state.data.addons.forEach(function (addon) {
            if (state.addons[addon.id]) {
                addons.push(addon.id);
            }
        });

        var builds = (state.data.builds || []).map(function (build) {
            return { build: build.id, parts: state.builds[build.id].parts.slice() };
        });

        document.dispatchEvent(new CustomEvent('qayema:add', {
            detail: { dish: state.id, options: options, addons: addons, builds: builds, quantity: state.quantity },
        }));

        close();
    }

    /** Held open for the slide-out, as the cart sheet is (menu-cart.js). */
    function close() {
        if (!sheet.open || sheet.classList.contains('closing')) {
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
            if (event.target === sheet) {
                finish();
            }
        }

        sheet.addEventListener('animationend', onEnd);
    }

    // ---- Wiring ----------------------------------------------------------

    document.addEventListener('click', function (event) {
        var dish = event.target.closest('.dish[data-choices]');
        if (!dish) {
            return;
        }

        // A tap anywhere on the card opens it, and so does any button that
        // says so (the cart's add, "See options"); no other control does.
        var button = event.target.closest('button, a');
        if (button && !button.hasAttribute('data-dish-open')) {
            return;
        }

        open(dish);
    });

    sheet.addEventListener('click', function (event) {
        // A tap outside an open part picker closes it first.
        if (openPicker && !event.target.closest('.build-pick')) {
            closePicker(false);
        }
        if (event.target === sheet || event.target.closest('[data-dish-close]')) {
            close();
        }
    });

    sheet.addEventListener('cancel', function (event) {
        event.preventDefault();
        // Escape with a part picker open closes only the picker.
        if (openPicker) {
            closePicker(true);
            return;
        }
        close();
    });
})();

/**
 * The dish sheet: a dish's variants and add-ons, picked before it goes in the
 * cart.
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
        return config.currency + amount.toFixed(2);
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
        return total;
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

        state = {
            id: dish.dataset.dish,
            data: data,
            base: parseFloat(dish.dataset.price || '0') || 0,
            // The first option of each variant is the one most guests want.
            picked: {},
            addons: {},
            quantity: 1,
        };
        data.variants.forEach(function (variant) {
            state.picked[variant.id] = variant.options[0].id;
        });

        title.textContent = dish.dataset.name || '';
        ingredients.textContent = dish.dataset.ingredients || '';
        ingredients.hidden = !dish.dataset.ingredients;

        // The full photo, not the card's small one.
        if (dish.dataset.photo) {
            photo.src = dish.dataset.photo;
            photo.hidden = false;
        } else {
            photo.removeAttribute('src');
            photo.hidden = true;
        }

        groups.textContent = '';
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

        document.dispatchEvent(new CustomEvent('qayema:add', {
            detail: { dish: state.id, options: options, addons: addons, quantity: state.quantity },
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
        if (event.target === sheet || event.target.closest('[data-dish-close]')) {
            close();
        }
    });

    sheet.addEventListener('cancel', function (event) {
        event.preventDefault();
        close();
    });
})();

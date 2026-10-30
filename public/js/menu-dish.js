/**
 * The dish sheet: a dish's variants, add-ons and "build your own" (half and
 * half pizzas, three scoops), picked before it goes in the cart. Add-ons in a
 * group (from a POS) keep to its fewest and most: past the most the other
 * boxes are off, and short of the fewest the dish is not added. A build is
 * priced by menu-build.js, the server's rule.
 *
 * The choices come from the page (#dish-options, built by
 * App\Services\Menu\MenuDishOptions). Picking is all this file does: a menu
 * that takes orders hands the result to menu-cart.js as a `qayema:add`
 * event, and one that does not just shows the choices and their prices. The
 * price shown here is a preview; the server prices every order itself. A
 * live offer's discount (data-deal-type on the dish, menu-offers.js) comes
 * off the dish's own price and the variants picked, never the add-ons or
 * builds, as the cart and the server take it.
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
    var wasTag = sheet.querySelector('[data-dish-was]');
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

    /**
     * OfferDiscount::apply(), in whole cents, half up, never below nothing
     * (twin of discounted() in menu-cart.js). An amount off is in the main
     * currency: a dish priced in the second one takes it off at the rate.
     */
    function discounted(cents, type, value, rate) {
        if (type === 'percent' || type === 'second') {
            return Math.max(0, Math.round((cents * (100 - value)) / 100));
        }

        return Math.max(0, cents - Math.round((value * 100) / (rate || 1)));
    }

    /** One of what is picked: `unit` with a live discount off, `full` without it. */
    function unitPrice() {
        var total = state.base;
        state.data.variants.forEach(function (variant) {
            variant.options.forEach(function (option) {
                if (state.picked[variant.id] === option.id) {
                    total += parseFloat(option.price) || 0;
                }
            });
        });

        // What was built (a pizza's halves) is discounted with them.
        (state.data.builds || []).forEach(function (build) {
            total += window.QAYEMA_BUILD.price(build.shape, chosenOf(build));
        });

        // The dish's own price, its variants and what was built, less a live
        // discount; a discount that would leave nothing does not apply.
        var full = total;
        var type = state.dish.dataset.dealType;
        if (type) {
            var off = discounted(Math.round(total * 100), type, parseFloat(state.dish.dataset.dealValue) || 0, parseFloat(state.dish.dataset.rate || '') || null);
            if (off > 0) {
                total = off / 100;
            }
        }

        allAddons(state.data).forEach(function (addon) {
            if (state.addons[addon.id]) {
                total += parseFloat(addon.price) || 0;
                full += parseFloat(addon.price) || 0;
            }
        });
        return { unit: total, full: full };
    }

    /** Every add-on of a dish: the ones with no group, then each group's. */
    function allAddons(data) {
        var all = data.addons.slice();
        (data.addon_groups || []).forEach(function (group) {
            all = all.concat(group.addons);
        });
        return all;
    }

    /** How many of a group's add-ons are picked. */
    function pickedIn(group) {
        return group.addons.filter(function (addon) {
            return !!state.addons[addon.id];
        }).length;
    }

    /**
     * Every add-on of the group must be picked (a required choice with a
     * single option, as a POS may send): they come ticked and stay ticked.
     */
    function includedAll(group) {
        return group.min > 0 && group.min >= group.addons.length;
    }

    /** What a group asks for, in words: "Pick up to 2", "Pick at least 1". */
    function ruleOf(group) {
        if (includedAll(group)) {
            return strings.included;
        }
        if (group.max !== null && group.min === group.max) {
            return strings.exactly.replace(':count', group.max);
        }
        if (group.max !== null && group.min > 0) {
            return strings.between.replace(':min', group.min).replace(':max', group.max);
        }
        if (group.max !== null) {
            return strings.upTo.replace(':max', group.max);
        }
        return group.min > 0 ? strings.atLeast.replace(':min', group.min) : strings.optional;
    }

    /** A build's choices at the size picked (all of them for a build with no sizes). */
    function offeredOf(build) {
        return window.QAYEMA_BUILD.forSize(build, state.builds[build.id].size);
    }

    /** The choices picked for a build's parts, in order. */
    function chosenOf(build) {
        return state.builds[build.id].parts.map(function (key) {
            return offeredOf(build).filter(function (choice) {
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
     * What a choice adds, or "Free" for an add-on with no price. `full` is for
     * the first variant of a dish with no price of its own: its options are the
     * prices themselves (Small $7.00), not extras. A variant's option that adds
     * nothing is the dish's own price, so it says nothing (null).
     */
    function priceLabel(price, full, variant) {
        var amount = parseFloat(price) || 0;
        if (full) {
            return element('span', 'choice-price', money(amount));
        }
        if (amount > 0) {
            return element('span', 'choice-price', extra(amount));
        }
        return variant ? null : element('span', 'choice-price is-free', strings.free);
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
            var price = priceLabel(option.price, full, true);
            if (price) {
                label.appendChild(price);
            }
            pills.appendChild(label);
        });

        set.appendChild(pills);
        return set;
    }

    /**
     * A list of add-ons to tick: the dish's own (any number), or a group's,
     * which says its rule and, once its most is picked, turns the rest off.
     */
    function addonGroup(addons, group) {
        var set = element('fieldset', 'choice-group');
        set.appendChild(legend(group ? group.name : strings.addons, group ? ruleOf(group) : strings.optional));
        if (group) {
            set.dataset.addonGroup = String(group.id);
            var note = element('p', 'choice-missing');
            note.setAttribute('role', 'alert');
            note.hidden = true;
            set.appendChild(note);
            state.groupSets[group.id] = { set: set, note: note, inputs: [] };
        }

        var included = !!group && includedAll(group);
        var rows = element('div', 'choice-rows');
        addons.forEach(function (addon) {
            var label = element('label', 'choice-row');
            var input = document.createElement('input');
            input.type = 'checkbox';
            input.value = String(addon.id);
            if (included) {
                state.addons[addon.id] = true;
            }
            input.checked = !!state.addons[addon.id];
            input.addEventListener('change', function () {
                state.addons[addon.id] = input.checked;
                if (group) {
                    limitGroup(group);
                }
                refresh();
            });
            if (group) {
                state.groupSets[group.id].inputs.push(input);
            }

            var box = element('span', 'choice-check');
            box.appendChild(icon(icons.check));

            label.appendChild(input);
            label.appendChild(box);
            label.appendChild(element('span', 'choice-name', addon.name));
            label.appendChild(priceLabel(addon.price));
            rows.appendChild(label);
        });

        set.appendChild(rows);
        if (group) {
            limitGroup(group);
        }
        return set;
    }

    /**
     * Keeps a group within its most (the unticked boxes are off once it is
     * reached) and clears its "pick at least" note once the fewest is met.
     */
    function limitGroup(group) {
        var drawn = state.groupSets[group.id];
        var full = group.max !== null && pickedIn(group) >= group.max;
        var included = includedAll(group);
        drawn.inputs.forEach(function (input) {
            // An included add-on stays ticked; it is not "off", only fixed.
            input.disabled = included || (full && !input.checked);
            input.closest('.choice-row').classList.toggle('is-off', input.disabled && !included);
        });
        if (pickedIn(group) >= group.min) {
            drawn.note.hidden = true;
            drawn.set.classList.remove('is-short');
        }
    }

    /**
     * The first group short of its fewest, told so on the sheet, or null
     * when every group has enough.
     */
    function shortGroup() {
        var short = (state.data.addon_groups || []).filter(function (group) {
            return pickedIn(group) < group.min;
        })[0];
        if (!short) {
            return null;
        }
        var drawn = state.groupSets[short.id];
        drawn.note.textContent = strings.missing.replace(':min', short.min).replace(':group', short.name);
        drawn.note.hidden = false;
        drawn.set.classList.add('is-short');
        drawn.set.scrollIntoView({ block: 'center', behavior: 'smooth' });
        return short;
    }

    /**
     * A heading inside a build, drawn like a variant's legend (a fieldset
     * has one legend, and the build's names the whole group).
     */
    function heading(name, tag, id) {
        var node = element('div', 'choice-legend');
        var text = node.appendChild(element('span', 'choice-title', name));
        if (id) {
            text.id = id;
        }
        if (tag) {
            node.appendChild(element('span', 'choice-tag', tag));
        }
        return node;
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
     * A build: its size (when its choices are priced by size and it has more
     * than one), how many parts (when it allows more than one count), the
     * pizza cut that way for a split one, and a choice for each part with
     * what that part costs at that size. Each row of pills has its heading,
     * as a variant does; the build's name heads the parts only when it is
     * not the sheet's title (the dish's name, which a build usually shares).
     * Drawn again when the count changes.
     */
    function buildGroup(build) {
        var set = element('fieldset', 'choice-group build-group');
        var picked = state.builds[build.id];
        set.setAttribute('aria-label', build.name);

        if ((build.sizes || []).length > 1) {
            set.appendChild(heading(strings.size, strings.pickOne, 'build-size-title-' + build.id));
            var sizes = element('div', 'choice-pills build-sizes');
            sizes.setAttribute('role', 'radiogroup');
            sizes.setAttribute('aria-labelledby', 'build-size-title-' + build.id);
            build.sizes.forEach(function (size) {
                var label = element('label', 'choice-pill');
                var input = document.createElement('input');
                input.type = 'radio';
                input.name = 'build-size-' + build.id;
                input.value = size.key;
                input.checked = picked.size === size.key;
                input.addEventListener('change', function () {
                    picked.size = size.key;
                    // Parts made in this size stay; the rest start on its first choice.
                    var offered = offeredOf(build);
                    picked.parts = picked.parts.map(function (key) {
                        var kept = offered.some(function (choice) { return choice.key === key; });
                        return kept || offered.length === 0 ? key : offered[0].key;
                    });
                    set.replaceWith(buildGroup(build));
                    refresh();
                });
                label.appendChild(input);
                label.appendChild(element('span', 'choice-name', size.name));
                sizes.appendChild(label);
            });
            set.appendChild(sizes);
        }

        if (build.parts.length > 1) {
            set.appendChild(heading(build.shape === 'split' ? strings.split : strings.howMany, strings.pickOne, 'build-count-title-' + build.id));
            var counts = element('div', 'choice-pills');
            counts.setAttribute('role', 'radiogroup');
            counts.setAttribute('aria-labelledby', 'build-count-title-' + build.id);
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
                        parts.push(offeredOf(build)[0].key);
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

        if (build.name && build.name !== state.dish.dataset.name) {
            set.appendChild(heading(build.name));
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

        // The choices at the size picked, each at its price there.
        var offered = offeredOf(build);
        var options = offered.map(function (choice) {
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
            var choice = offered.filter(function (each) { return each.key === key; })[0] || offered[0];
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
        var price = unitPrice();
        var unit = price.unit;
        priceTag.textContent = money(unit);
        // As the card: what it would cost without the offer, struck out.
        wasTag.hidden = !(price.full > unit);
        wasTag.textContent = wasTag.hidden ? '' : money(price.full);

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
            dish: dish,
            data: data,
            base: parseFloat(dish.dataset.price || '0') || 0,
            symbol: dish.dataset.currency || '',
            // The first option of each variant is the one most guests want.
            picked: {},
            addons: {},
            // Each add-on group's fieldset, note and boxes, by group id.
            groupSets: {},
            // Each build starts on its fewest parts, every part its first choice.
            builds: {},
            quantity: 1,
        };
        data.variants.forEach(function (variant) {
            state.picked[variant.id] = variant.options[0].id;
        });
        (data.builds || []).forEach(function (build) {
            // At its first size with two choices, for a build priced by size.
            var size = window.QAYEMA_BUILD.firstSize(build);
            var first = window.QAYEMA_BUILD.forSize(build, size)[0];
            state.builds[build.id] = {
                size: size,
                parts: Array.apply(null, Array(build.parts[0])).map(function () {
                    return first.key;
                }),
            };
        });

        // Isolated, so a name in the other script (an English fallback in
        // an Arabic menu) keeps its punctuation in place.
        title.textContent = '';
        title.appendChild(element('bdi', null, dish.dataset.name || ''));
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
            groups.appendChild(addonGroup(data.addons, null));
        }
        (data.addon_groups || []).forEach(function (group) {
            groups.appendChild(addonGroup(group.addons, group));
        });

        // The card's offer badge ("-20%", "Buy 2, 2nd 50% off") after the price.
        var shownBadge = wasTag.parentNode.querySelector('.dish-badge');
        var cardBadge = dish.querySelector('.dish-badge');
        if (shownBadge) {
            shownBadge.remove();
        }
        if (cardBadge) {
            wasTag.parentNode.appendChild(cardBadge.cloneNode(true));
        }

        drawFoot();
        refresh();

        sheet.classList.remove('closing');
        sheet.querySelector('.dish-sheet-scroll').scrollTop = 0;
        sheet.showModal();
    }

    function addToCart() {
        if (shortGroup()) {
            return;
        }

        var options = [];
        Object.keys(state.picked).forEach(function (variant) {
            options.push(state.picked[variant]);
        });
        var addons = [];
        allAddons(state.data).forEach(function (addon) {
            if (state.addons[addon.id]) {
                addons.push(addon.id);
            }
        });

        var builds = (state.data.builds || []).map(function (build) {
            var picked = state.builds[build.id];
            var line = { build: build.id, parts: picked.parts.slice() };
            if (picked.size) {
                line.size = picked.size;
            }
            return line;
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

    // An offer began or ended while the sheet is open (menu-offers.js).
    document.addEventListener('qayema:prices', function () {
        if (state && sheet.open) {
            refresh();
        }
    });
})();

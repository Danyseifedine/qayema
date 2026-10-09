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
 *
 * The restaurant takes orders one way (config.mode):
 * - `whatsapp`: the order is stored and the guest is sent to WhatsApp with
 *   it written out, an optional note included;
 * - `menu`: the guest leaves their name, a phone number and, for a delivery, an address
 *   (typed, plus their location if they share it), and the order waits on
 *   the owner's Orders page. A toast says it went. A guest who scanned a
 *   table's QR code can order to that table instead ("dine-in", a feature of
 *   its own, config.dineIn): no address, and their name and number become
 *   optional. At a table the page is built for the menu even while delivery
 *   and pickup go to WhatsApp; it then offers dine-in alone.
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

    var inMenu = config.mode === 'menu';

    /** What a WhatsApp cart asks the guest for ({name, phone, address}:
     *  off, optional or required, Restaurant::whatsappAsks()); null in the
     *  menu, which asks its own way. */
    var asks = inMenu ? null : config.asks || null;

    /** How long a table's code is remembered after scanning it: a meal,
     *  not until tomorrow. */
    var TABLE_HOURS = 4;

    /** The table the guest sits at ({code, name}): the one whose QR code
     *  opened the menu, or the one this browser scanned a little earlier
     *  (a reload, the link back from the tracking sheet). The server checks
     *  the code with every order. */
    var table = rememberTable();

    /** What the guest typed, shared by the phone sheet and the wide column.
     *  Kept in the browser until the order goes, so a reload or a locked
     *  phone loses nothing; sending it starts the form over. */
    var details = loadDetails();

    /** The id of what this cart sends (a new order, a change to one, dishes
     *  added to one), made on the first press and kept until it goes, so a
     *  retry can never send it twice. */
    var orderToken = null;

    /** True while an order is on its way, so a repaint leaves the button. */
    var sending = false;

    /** The order being changed ({reference, updateUrl, version, fulfilment, table}), or null. While it
     *  is set the cart and the form hold that order, and nothing is written
     *  over the guest's own cart or details in storage. */
    var editing = null;

    /** The guest's order still going, as menu-order.js last heard it:
     *  {token, reference, status, fulfilment}, or null. One order at a time:
     *  until the restaurant accepts it the cart adds to it; from then on the
     *  cart waits for it to be done. */
    var going = null;

    /** The order the cart adds to, while it can still change. */
    function addingTo() {
        return !editing && going && going.status === 'placed' ? going : null;
    }

    /** The order accepted, on its way or ready: no new one until it is done. */
    function waitingFor() {
        return !editing && going && (going.status === 'accepted' || going.status === 'ready') ? going : null;
    }

    function rememberTable() {
        var now = Date.now();

        try {
            // A table scanned earlier only counts where orders at the table
            // are taken; elsewhere it would offer what cannot be ordered.
            if (config.table || !config.dineIn) {
                if (config.table) {
                    window.localStorage.setItem(config.tableKey, JSON.stringify({ code: config.table.code, name: config.table.name, at: now }));
                }
                return config.table || null;
            }

            var saved = JSON.parse(window.localStorage.getItem(config.tableKey) || 'null');
            if (saved && typeof saved.code === 'string' && typeof saved.name === 'string' && now - saved.at < TABLE_HOURS * 3600000) {
                return { code: saved.code, name: saved.name };
            }
        } catch (error) {
            // No storage: only the scan that opened this page counts.
        }

        return config.table || null;
    }

    /** The table's code no longer opens it (the owner printed a new one). */
    function forgetTable() {
        table = null;
        try {
            window.localStorage.removeItem(config.tableKey);
        } catch (error) {
            // Nothing was kept, then.
        }
    }

    /** The table an order at it goes to: the one being changed keeps its own. */
    function tableName() {
        if (editing && editing.fulfilment === 'dine_in') {
            return editing.table || '';
        }
        return table ? table.name : '';
    }

    /** The menu asks every detail; WhatsApp only those the owner turned on. */
    function asked(key) {
        return inMenu || Boolean(asks && asks[key] && asks[key] !== 'off');
    }

    /** Asked on WhatsApp, but the guest may leave it empty. */
    function optionalOnWhatsApp(key) {
        return !inMenu && asks[key] === 'optional';
    }

    /** Delivery and pickup as the restaurant takes them, and dine-in while it is on. */
    function allTypes() {
        return (config.types || []).concat(config.dineIn ? ['dine_in'] : []);
    }

    /**
     * The kinds of order the guest can pick now. Dine-in needs a table: the
     * one scanned, or the one the order being changed already has.
     */
    function types() {
        return allTypes().filter(function (type) {
            return type !== 'dine_in' || Boolean(table) || Boolean(editing && editing.fulfilment === 'dine_in');
        });
    }

    /** At a table, ordering to it comes first. */
    function firstType() {
        var open = types();
        return open.indexOf('dine_in') !== -1 && table ? 'dine_in' : open[0];
    }

    /** Only dine-in is on, and this guest has no table to order to. */
    function needsTable() {
        return inMenu && types().length === 0;
    }

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
        if (editing) {
            return;
        }
        try {
            window.localStorage.setItem(config.storageKey, JSON.stringify(cart));
        } catch (error) {
            // Not being able to remember the cart is survivable.
        }
    }

    function loadDetails() {
        var saved = {};
        try {
            saved = JSON.parse(window.localStorage.getItem(config.guestKey) || '{}') || {};
        } catch (error) {
            saved = {};
        }

        var open = types();
        var known = (config.countries || []).some(function (country) {
            return country.code === saved.country;
        });

        return {
            // At a table, the order goes to it unless the guest says otherwise.
            fulfilment: table && open.indexOf('dine_in') !== -1
                ? 'dine_in'
                : (open.indexOf(saved.fulfilment) !== -1 ? saved.fulfilment : open[0]),
            name: typeof saved.name === 'string' ? saved.name : '',
            country: known ? saved.country : config.country,
            phone: typeof saved.phone === 'string' ? saved.phone : '',
            address: typeof saved.address === 'string' ? saved.address : '',
            note: '',
            website: '',
            latitude: null,
            longitude: null,
            // idle | locating | added | failed
            location: 'idle',
            // The address line the location filled in, while it is there.
            filled: '',
        };
    }

    /**
     * Once an order is sent the form starts over: the next order may be for
     * someone else, somewhere else.
     */
    function resetDetails() {
        details.name = '';
        details.phone = '';
        details.address = '';
        details.note = '';
        details.website = '';
        details.country = config.country;
        details.fulfilment = firstType();
        details.latitude = null;
        details.longitude = null;
        details.filled = '';
        details.location = 'idle';

        paintDetails();

        try {
            window.localStorage.removeItem(config.guestKey);
        } catch (error) {
            // Nothing was kept, then.
        }
    }

    /** Every box shows what `details` holds now. */
    function paintDetails() {
        ['name', 'phone', 'address', 'note', 'website'].forEach(function (key) {
            mirror(key, null);
        });
        ['fulfilment', 'name', 'phone', 'address', 'note'].forEach(clearError);
        paintFulfilment();
        paintCountry();
        paintLocation();
    }

    function saveDetails() {
        if (editing) {
            return;
        }
        try {
            window.localStorage.setItem(config.guestKey, JSON.stringify({
                fulfilment: details.fulfilment,
                name: details.name,
                country: details.country,
                phone: details.phone,
                address: details.address,
            }));
        } catch (error) {
            // The guest types it again next time.
        }
    }

    function dishes() {
        return Array.prototype.slice.call(document.querySelectorAll('.dish[data-price]:not([data-price=""])'));
    }

    function money(amount) {
        // As App\Support\Price: no decimals on a whole amount, two otherwise.
        var rounded = Math.round(amount * 100) / 100;
        var whole = rounded === Math.floor(rounded);
        return config.currency + rounded.toLocaleString('en-US', {
            minimumFractionDigits: whole ? 0 : 2,
            maximumFractionDigits: whole ? 0 : 2,
        });
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

        // A different cart is a different order.
        orderToken = null;

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

    // ---- The guest's details --------------------------------------------

    /** A labelled field with a place for its error under it. */
    function field(key, label, optional) {
        var wrap = element('div', 'cart-field');
        wrap.setAttribute('data-field', key);

        var caption = element('label', 'cart-label', label);
        if (optional) {
            caption.appendChild(element('span', 'cart-optional', strings.optional));
        }
        wrap.appendChild(caption);

        return wrap;
    }

    function errorSlot(wrap, key) {
        var slot = element('p', 'cart-field-error');
        slot.hidden = true;
        slot.setAttribute('data-error-for', key);
        wrap.appendChild(slot);
    }

    /** Bind a control to details[key], and keep its twin in the other panel
     *  showing the same thing. */
    function bind(control, key) {
        control.setAttribute('data-detail', key);
        control.value = details[key];
        control.addEventListener('input', function () {
            details[key] = control.value;
            mirror(key, control);
            clearError(key);
            saveDetails();
        });
        control.addEventListener('change', function () {
            details[key] = control.value;
            mirror(key, control);
            saveDetails();
        });
    }

    function mirror(key, source) {
        Array.prototype.forEach.call(document.querySelectorAll('[data-detail="' + key + '"]'), function (control) {
            if (control !== source && control.value !== details[key]) {
                control.value = details[key];
            }
        });
    }

    function typesField(id) {
        var wrap = element('fieldset', 'cart-field cart-types');
        wrap.setAttribute('data-field', 'fulfilment');
        wrap.appendChild(element('legend', 'cart-label', strings.how));

        var pills = element('div', 'choice-pills');
        // Every kind is drawn; paintFulfilment() hides the ones not open now.
        allTypes().forEach(function (type) {
            var pill = element('label', 'choice-pill');
            pill.setAttribute('data-type-pill', type);
            var input = document.createElement('input');
            input.type = 'radio';
            input.name = 'fulfilment-' + id;
            input.value = type;
            input.checked = details.fulfilment === type;
            input.setAttribute('data-fulfilment', type);
            input.addEventListener('change', function () {
                details.fulfilment = type;
                saveDetails();
                clearError('fulfilment');
                paintFulfilment();
            });
            pill.appendChild(input);
            pill.appendChild(element('span', 'choice-name', strings[type]));
            if (type === 'dine_in') {
                // Which table: the guest sees where it goes before sending.
                var where = element('span', 'choice-price', tableName());
                where.setAttribute('data-table-name', '');
                pill.appendChild(where);
            }
            pills.appendChild(pill);
        });
        wrap.appendChild(pills);
        errorSlot(wrap, 'fulfilment');

        return wrap;
    }

    /** "Optional" beside a label, shown only for an order at the table. */
    function optionalAtTable(wrap) {
        var tag = element('span', 'cart-optional', strings.optional);
        tag.setAttribute('data-dine-optional', '');
        tag.hidden = true;
        wrap.querySelector('label').appendChild(tag);
    }

    function nameField(id) {
        var wrap = field('name', strings.name, optionalOnWhatsApp('name'));
        wrap.querySelector('label').htmlFor = 'cart-name-' + id;
        if (inMenu) {
            optionalAtTable(wrap);
        }

        var input = document.createElement('input');
        input.id = 'cart-name-' + id;
        input.className = 'cart-input';
        input.type = 'text';
        input.autocomplete = 'name';
        input.maxLength = 60;
        input.placeholder = strings.nameHint;
        bind(input, 'name');
        wrap.appendChild(input);
        errorSlot(wrap, 'name');

        return wrap;
    }

    function countryOf(code) {
        for (var i = 0; i < config.countries.length; i++) {
            if (config.countries[i].code === code) {
                return config.countries[i];
            }
        }
        return config.countries[0];
    }

    /** Lower case, no accents, so "Liban" finds "Líban" and "LEB" finds Lebanon. */
    function folded(text) {
        return String(text).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }

    /** Every country button shows the chosen code, in both panels. */
    function paintCountry() {
        var chosen = countryOf(details.country);
        Array.prototype.forEach.call(document.querySelectorAll('[data-country-button]'), function (button) {
            button.firstChild.textContent = chosen.flag + ' ' + chosen.dial;
            button.setAttribute('aria-label', strings.country + ': ' + chosen.name + ' ' + chosen.dial);
        });
    }

    /**
     * The country code: a button with the flag and the code, opening a list
     * the guest can search by name (in the menu's language or English), by
     * code ("961") or by its letters ("LB"). The browser's own select has no
     * search, and 28 countries is a long scroll on a phone.
     */
    function countryPicker(id, wrap, phoneInput) {
        var listId = 'cart-countries-' + id;

        var button = element('button', 'cart-country');
        button.type = 'button';
        button.setAttribute('data-country-button', '');
        button.setAttribute('aria-haspopup', 'listbox');
        button.setAttribute('aria-expanded', 'false');
        button.appendChild(element('span', null, ''));
        var chevron = element('span', 'icon cart-country-chevron');
        chevron.innerHTML = icons.chevron;
        button.appendChild(chevron);

        var panel = element('div', 'cart-country-panel');
        panel.hidden = true;

        var search = document.createElement('input');
        search.type = 'search';
        search.className = 'cart-country-search';
        search.placeholder = strings.countrySearch;
        search.autocomplete = 'off';
        search.setAttribute('role', 'combobox');
        search.setAttribute('aria-label', strings.countrySearch);
        search.setAttribute('aria-controls', listId);
        search.setAttribute('aria-expanded', 'true');
        search.setAttribute('aria-autocomplete', 'list');
        panel.appendChild(search);

        var list = element('ul', 'cart-country-list');
        list.id = listId;
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', strings.country);
        panel.appendChild(list);

        var empty = element('p', 'cart-country-empty', strings.countryNone);
        empty.hidden = true;
        panel.appendChild(empty);

        var shown = [];
        var active = 0;

        function paintActive() {
            Array.prototype.forEach.call(list.children, function (item, index) {
                item.classList.toggle('active', index === active);
            });
            var current = list.children[active];
            if (current) {
                search.setAttribute('aria-activedescendant', current.id);
                current.scrollIntoView({ block: 'nearest' });
            } else {
                search.removeAttribute('aria-activedescendant');
            }
        }

        function filter() {
            var term = folded(search.value.trim()).replace(/^\+/, '');
            shown = config.countries.filter(function (country) {
                return term === ''
                    || folded(country.name).indexOf(term) !== -1
                    || folded(country.label).indexOf(term) !== -1
                    || country.dial.replace('+', '').indexOf(term) === 0
                    || country.code.toLowerCase() === term;
            });

            list.textContent = '';
            shown.forEach(function (country, index) {
                var item = element('li', 'cart-country-option');
                item.id = listId + '-' + country.code;
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', String(country.code === details.country));
                item.appendChild(element('span', 'cart-country-flag', country.flag));
                item.appendChild(element('span', 'cart-country-name', country.name));
                item.appendChild(element('span', 'cart-country-dial', country.dial));
                // Pressed, not clicked: a click would blur the search first
                // and close the list before it lands.
                item.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                    choose(country);
                });
                item.addEventListener('mouseenter', function () {
                    active = index;
                    paintActive();
                });
                list.appendChild(item);
            });

            empty.hidden = shown.length > 0;
            active = Math.max(0, shown.findIndex(function (country) {
                return country.code === details.country;
            }));
            if (term !== '') {
                active = 0;
            }
            paintActive();
        }

        function open() {
            panel.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            search.value = '';
            filter();
            search.focus({ preventScroll: true });
            // Near the bottom of the sheet or the column, the list would
            // open half out of sight.
            panel.scrollIntoView({ block: 'nearest' });
            document.addEventListener('mousedown', outside, true);
        }

        function close(refocus) {
            if (panel.hidden) {
                return;
            }
            panel.hidden = true;
            button.setAttribute('aria-expanded', 'false');
            document.removeEventListener('mousedown', outside, true);
            if (refocus) {
                button.focus();
            }
        }

        function outside(event) {
            if (!panel.contains(event.target) && !button.contains(event.target)) {
                close(false);
            }
        }

        function choose(country) {
            details.country = country.code;
            saveDetails();
            clearError('phone');
            paintCountry();
            close(false);
            phoneInput.focus();
        }

        button.addEventListener('click', function () {
            if (panel.hidden) {
                open();
            } else {
                close(true);
            }
        });
        search.addEventListener('input', filter);
        search.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                var step = event.key === 'ArrowDown' ? 1 : -1;
                active = Math.min(Math.max(active + step, 0), shown.length - 1);
                paintActive();
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (shown[active]) {
                    choose(shown[active]);
                }
            } else if (event.key === 'Escape') {
                // The list, not the cart sheet around it.
                event.preventDefault();
                event.stopPropagation();
                close(true);
            } else if (event.key === 'Tab') {
                close(false);
            }
        });

        wrap.appendChild(panel);

        return button;
    }

    function phoneField(id) {
        var wrap = field('phone', strings.phone, optionalOnWhatsApp('phone'));
        wrap.classList.add('cart-phone-field');
        wrap.querySelector('label').htmlFor = 'cart-phone-' + id;
        if (inMenu) {
            optionalAtTable(wrap);
        }

        var row = element('div', 'cart-phone');
        // Digits read left to right, on an Arabic menu too.
        row.dir = 'ltr';

        var input = document.createElement('input');
        input.id = 'cart-phone-' + id;
        input.type = 'tel';
        input.inputMode = 'tel';
        input.autocomplete = 'tel-national';
        input.maxLength = 30;
        input.placeholder = strings.phoneHint;
        // Before bind(), so what is kept is already clean: only what a
        // number is written with, as the server accepts it.
        input.addEventListener('input', function () {
            var clean = phoneCharacters(input.value);
            if (clean !== input.value) {
                input.value = clean;
            }
        });
        bind(input, 'phone');

        row.appendChild(countryPicker(id, wrap, input));
        row.appendChild(input);
        // Under the label, above the list the code opens.
        wrap.insertBefore(row, wrap.querySelector('.cart-country-panel'));
        errorSlot(wrap, 'phone');

        return wrap;
    }

    /** Digits, spaces, + ( ) - and dots; Arabic and Persian digits become 0 to 9. */
    function phoneCharacters(text) {
        return text
            .replace(/[\u0660-\u0669]/g, function (digit) {
                return String(digit.charCodeAt(0) - 0x0660);
            })
            .replace(/[\u06f0-\u06f9]/g, function (digit) {
                return String(digit.charCodeAt(0) - 0x06f0);
            })
            .replace(/[^0-9+() .\-]/g, '')
            .replace(/^ +/, '');
    }

    function addressField(id) {
        var wrap = field('address', strings.address, optionalOnWhatsApp('address'));
        wrap.setAttribute('data-delivery-only', '');
        wrap.querySelector('label').htmlFor = 'cart-address-' + id;

        // First, so the address it fills in lands in the box right under it.
        var button = element('button', 'cart-locate-button');
        button.type = 'button';
        button.setAttribute('data-locate', '');
        var glyph = element('span', 'icon');
        glyph.innerHTML = icons.pin;
        button.appendChild(glyph);
        button.appendChild(element('span', null, strings.locate));
        button.addEventListener('click', locateGuest);
        wrap.appendChild(button);

        var status = element('div', 'cart-locate-status');
        status.setAttribute('data-locate-status', '');
        status.hidden = true;
        wrap.appendChild(status);

        var input = document.createElement('textarea');
        input.id = 'cart-address-' + id;
        input.className = 'cart-input';
        input.rows = 2;
        input.maxLength = 500;
        input.autocomplete = 'street-address';
        input.placeholder = strings.addressHint;
        bind(input, 'address');
        wrap.appendChild(input);

        errorSlot(wrap, 'address');

        return wrap;
    }

    function noteField(id) {
        var wrap = field('note', strings.note, true);
        wrap.querySelector('label').htmlFor = 'cart-note-' + id;

        var input = document.createElement('textarea');
        input.id = 'cart-note-' + id;
        input.className = 'cart-input';
        input.rows = 2;
        input.maxLength = 500;
        input.placeholder = strings.noteHint;
        bind(input, 'note');
        wrap.appendChild(input);
        errorSlot(wrap, 'note');

        return wrap;
    }

    /** Left empty by people; a bot filling every box fills this one too. */
    function trapField() {
        var wrap = element('div', 'cart-trap');
        wrap.setAttribute('aria-hidden', 'true');

        var input = document.createElement('input');
        input.type = 'text';
        input.name = 'website';
        input.tabIndex = -1;
        input.autocomplete = 'off';
        bind(input, 'website');
        wrap.appendChild(input);

        return wrap;
    }

    function buildDetails(id) {
        var wrap = element('div', 'cart-details');

        // On WhatsApp the message names the table; the guest sees it first.
        if (!inMenu && table) {
            wrap.appendChild(element('p', 'cart-closed', strings.forTable.replace(':table', table.name)));
        }

        if (inMenu) {
            if (allTypes().length > 1) {
                wrap.appendChild(typesField(id));
            }
            wrap.appendChild(nameField(id));
            wrap.appendChild(phoneField(id));
            wrap.appendChild(addressField(id));
        } else {
            // On WhatsApp only what the owner asks for. An address also asks
            // delivery or pickup, and shows only for a delivery.
            if (asked('address') && allTypes().length > 1) {
                wrap.appendChild(typesField(id));
            }
            if (asked('name')) {
                wrap.appendChild(nameField(id));
            }
            if (asked('phone')) {
                wrap.appendChild(phoneField(id));
            }
            if (asked('address')) {
                wrap.appendChild(addressField(id));
            }
        }
        wrap.appendChild(noteField(id));
        if (inMenu) {
            wrap.appendChild(trapField());
        }

        return wrap;
    }

    /**
     * Pickup and dine-in need no address, so the address leaves with them;
     * at the table the name and number turn optional. Dine-in only shows
     * with a table to order to, and the choice only with two to choose from.
     */
    function paintFulfilment() {
        var open = types();
        Array.prototype.forEach.call(document.querySelectorAll('[data-fulfilment]'), function (input) {
            input.checked = input.value === details.fulfilment;
        });
        Array.prototype.forEach.call(document.querySelectorAll('[data-type-pill]'), function (pill) {
            pill.hidden = open.indexOf(pill.getAttribute('data-type-pill')) === -1;
        });
        Array.prototype.forEach.call(document.querySelectorAll('.cart-types'), function (fieldset) {
            fieldset.hidden = open.length < 2;
        });
        Array.prototype.forEach.call(document.querySelectorAll('[data-table-name]'), function (node) {
            node.textContent = tableName();
        });
        Array.prototype.forEach.call(document.querySelectorAll('[data-delivery-only]'), function (node) {
            node.hidden = details.fulfilment !== 'delivery';
        });
        Array.prototype.forEach.call(document.querySelectorAll('[data-dine-optional]'), function (node) {
            node.hidden = details.fulfilment !== 'dine_in';
        });
    }

    function paintLocation() {
        var state = details.location;

        Array.prototype.forEach.call(document.querySelectorAll('[data-locate]'), function (button) {
            button.disabled = state === 'locating';
            button.hidden = state === 'added';
            button.lastChild.textContent = state === 'locating' ? strings.locating : strings.locate;
        });
        Array.prototype.forEach.call(document.querySelectorAll('[data-locate-status]'), function (status) {
            status.textContent = '';
            status.hidden = state !== 'added' && state !== 'failed';
            status.classList.toggle('added', state === 'added');

            if (state === 'added') {
                var glyph = element('span', 'icon');
                glyph.innerHTML = icons.check;
                status.appendChild(glyph);

                var text = element('span', 'cart-locate-text');
                text.appendChild(element('strong', null, strings.located));
                if (details.filled) {
                    text.appendChild(element('span', null, strings.locatedFilled));
                }
                status.appendChild(text);

                var remove = element('button', 'cart-locate-remove', strings.remove);
                remove.type = 'button';
                remove.addEventListener('click', forgetLocation);
                status.appendChild(remove);
            } else if (state === 'failed') {
                status.textContent = strings.locateFailed;
            }
        });
    }

    /** Back to no location; an address it filled in and nobody changed goes too. */
    function forgetLocation() {
        if (details.filled && details.address === details.filled) {
            details.address = '';
            mirror('address', null);
            saveDetails();
        }
        details.latitude = null;
        details.longitude = null;
        details.filled = '';
        details.location = 'idle';
        paintLocation();
    }

    /**
     * The street, area and town for the guest's position (the server asks
     * OpenStreetMap), into the address box while it is empty or still holds
     * what an earlier tap put there. The guest then adds the building and
     * floor. When nothing comes back, the location still rides along.
     */
    function fillAddress() {
        var url = config.addressUrl
            + '?lat=' + encodeURIComponent(details.latitude)
            + '&lng=' + encodeURIComponent(details.longitude)
            + '&locale=' + encodeURIComponent(config.locale);

        return window
            .fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(function (body) {
                var line = body && body.data && body.data.address;
                var untouched = details.address.trim() === '' || details.address === details.filled;

                if (line && untouched) {
                    details.address = line;
                    details.filled = line;
                    mirror('address', null);
                    saveDetails();
                }
            })
            .catch(function () {
                // The typed address is enough.
            });
    }

    /**
     * Ask the phone where the guest is. Help on top of the typed address,
     * never instead of it: a refusal, no signal or a laptop's guess all
     * leave the address to do the job.
     */
    function locateGuest() {
        if (!navigator.geolocation) {
            details.location = 'failed';
            paintLocation();
            return;
        }

        details.location = 'locating';
        paintLocation();

        navigator.geolocation.getCurrentPosition(
            function (position) {
                details.latitude = Number(position.coords.latitude.toFixed(7));
                details.longitude = Number(position.coords.longitude.toFixed(7));
                clearError('address');
                fillAddress().then(function () {
                    details.location = 'added';
                    paintLocation();
                });
            },
            function () {
                details.location = 'failed';
                paintLocation();
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
        );
    }

    function clearError(key) {
        Array.prototype.forEach.call(document.querySelectorAll('[data-error-for="' + key + '"]'), function (slot) {
            slot.textContent = '';
            slot.hidden = true;
        });
    }

    /** Each problem under its field, in both panels. */
    function showErrors(errors) {
        ['fulfilment', 'name', 'phone', 'address', 'note'].forEach(clearError);

        Object.keys(errors).forEach(function (key) {
            Array.prototype.forEach.call(document.querySelectorAll('[data-error-for="' + key + '"]'), function (slot) {
                slot.textContent = errors[key];
                slot.hidden = false;
            });
        });
    }

    /** The checks the server makes too, so most mistakes never leave the page. */
    function problems() {
        var found = {};

        if (inMenu) {
            // At the table, who ordered is the table: a name and a number
            // help, but are not needed.
            var dineIn = details.fulfilment === 'dine_in';

            if (!dineIn && details.name.trim() === '') {
                found.name = strings.nameMissing;
            }

            var digits = details.phone.replace(/\D+/g, '');
            if (digits === '') {
                if (!dineIn) {
                    found.phone = strings.phoneMissing;
                }
            } else if (digits.length < 6 || digits.length > 15) {
                found.phone = strings.phoneInvalid;
            }

            if (details.fulfilment === 'delivery' && details.address.trim() === '') {
                found.address = strings.addressMissing;
            }
        } else if (asks) {
            // On WhatsApp, what the owner made required.
            if (asks.name === 'required' && details.name.trim() === '') {
                found.name = strings.nameMissing;
            }

            if (asked('phone')) {
                var number = details.phone.replace(/\D+/g, '');
                if (number === '') {
                    if (asks.phone === 'required') {
                        found.phone = strings.phoneMissing;
                    }
                } else if (number.length < 6 || number.length > 15) {
                    found.phone = strings.phoneInvalid;
                }
            }

            if (asks.address === 'required' && details.fulfilment === 'delivery' && details.address.trim() === '') {
                found.address = strings.addressMissing;
            }
        }

        return found;
    }

    /** The server's field errors, under the names the page uses. */
    function fieldErrors(errors) {
        var names = { phone_country: 'phone', latitude: 'address', longitude: 'address' };
        var found = {};

        Object.keys(errors || {}).forEach(function (key) {
            var name = names[key] || key;
            if (['fulfilment', 'name', 'phone', 'address', 'note'].indexOf(name) !== -1 && !found[name]) {
                found[name] = [].concat(errors[key])[0];
            }
        });

        return found;
    }

    // ---- The panel ---------------------------------------------------------

    /**
     * Each panel is built once: the lines are redrawn on every change, the
     * details form never is, so what the guest typed is never wiped.
     */
    function partsOf(panel) {
        if (panel.qayemaParts) {
            return panel.qayemaParts;
        }

        // The sheet keeps Place order in its own footer bar, the way the
        // design does; the desktop column has no footer and keeps it inline.
        var footer = foot && panel.parentNode && panel.parentNode.contains(foot) ? foot : null;

        var parts = {
            empty: element('p', 'cart-empty', strings.empty),
            filled: element('div', 'cart-filled'),
            editing: element('div', 'cart-editing'),
            lines: element('div'),
            error: element('p', 'cart-error'),
            place: element('button', 'place split'),
            footer: footer,
        };

        parts.error.hidden = true;
        parts.error.setAttribute('role', 'alert');
        parts.place.type = 'button';
        parts.place.addEventListener('click', function () {
            submit(parts);
        });

        parts.editing.hidden = true;
        parts.editing.appendChild(element('span', 'cart-editing-text'));
        var keep = element('button', 'cart-editing-stop', strings.keepOrder);
        keep.type = 'button';
        keep.addEventListener('click', function () {
            stopEditing();
            render();
        });
        parts.editing.appendChild(keep);

        parts.filled.appendChild(parts.editing);
        parts.filled.appendChild(parts.lines);
        if (config.closed) {
            parts.filled.appendChild(element('p', 'cart-closed', strings.closedNote));
        } else if (needsTable()) {
            // Only orders at the table are taken, and this guest has none.
            parts.filled.appendChild(element('p', 'cart-closed', strings.needsTable));
        } else {
            parts.details = buildDetails(panel.getAttribute('data-cart-panel'));
            parts.filled.appendChild(parts.details);
        }
        parts.filled.appendChild(parts.error);

        panel.textContent = '';
        panel.appendChild(parts.empty);
        panel.appendChild(parts.filled);
        (footer || parts.filled).appendChild(parts.place);

        panel.qayemaParts = parts;

        return parts;
    }

    function paintPlace(button, amount) {
        button.textContent = '';

        if (config.closed || waitingFor() || (needsTable() && !editing)) {
            button.disabled = true;
            button.classList.remove('split');
            button.textContent = config.closed ? strings.closed : waitingFor() ? strings.oneAtATime : strings.needsTableShort;
            return;
        }

        button.disabled = false;
        button.classList.add('split');
        button.appendChild(element('span', null, editing ? strings.update : addingTo() ? strings.addToOrder : strings.place));
        button.appendChild(element('span', null, money(amount)));
    }

    function renderPanel(panel) {
        var parts = partsOf(panel);
        var current = lines();
        var empty = current.length === 0;

        parts.empty.hidden = !empty;
        // Changing an order, adding to it, or waiting for it: a line above
        // the cart says which, and only a change can be called off.
        var adding = addingTo();
        var waiting = waitingFor();
        parts.editing.hidden = !editing && !adding && !waiting;
        parts.editing.classList.toggle('is-waiting', Boolean(waiting));
        parts.editing.lastChild.hidden = !editing;
        if (editing) {
            parts.editing.firstChild.textContent = strings.editing.replace(':reference', editing.reference);
        } else if (adding) {
            parts.editing.firstChild.textContent = strings.adding.replace(':reference', adding.reference);
        } else if (waiting) {
            var note = waiting.status === 'accepted'
                ? strings.waitingAccepted
                : { delivery: strings.waitingDelivery, dine_in: strings.waitingDineIn }[waiting.fulfilment] || strings.waitingPickup;
            parts.editing.firstChild.textContent = note.replace(':reference', waiting.reference);
        }
        // Adding to an order or waiting for one asks nothing: the order
        // already says who and where.
        if (parts.details) {
            parts.details.hidden = Boolean(adding || waiting);
        }
        parts.filled.hidden = empty;
        parts.place.hidden = empty;
        if (parts.footer) {
            parts.footer.hidden = empty;
        }

        parts.lines.textContent = '';
        if (empty) {
            return;
        }

        var sum = totals();

        var noun = sum.count === 1 ? strings.item : strings.items;
        parts.lines.appendChild(element('div', 'cart-items', sum.count + ' ' + noun));

        var list = element('div', 'cart-lines');
        current.forEach(function (line) {
            list.appendChild(renderLine(line));
        });
        parts.lines.appendChild(list);

        var summary = element('div', 'cart-summary');
        var totalRow = element('div', 'cart-total');
        totalRow.appendChild(element('span', null, strings.total));
        totalRow.appendChild(element('span', null, money(sum.amount)));
        summary.appendChild(totalRow);
        parts.lines.appendChild(summary);

        if (!sending) {
            paintPlace(parts.place, sum.amount);
        }
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

    /** An id for one order, so sending it twice still places it once. */
    function newToken() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }

        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = (Math.random() * 16) | 0;
            return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
        });
    }

    function payload(current) {
        var body = {
            items: current.map(function (line) {
                return { dish_id: Number(line.id), quantity: line.quantity, options: line.options, addons: line.addons };
            }),
            // The language the guest is reading, so the WhatsApp message
            // and any error come back in it.
            locale: config.locale,
            // What this page was built for; the server answers 409 if the
            // restaurant has since changed how it takes orders.
            mode: config.mode,
            note: details.note,
            // The table scanned: an order to it, or on WhatsApp, a label.
            table: table ? table.code : null,
        };

        if (inMenu) {
            var delivery = details.fulfilment === 'delivery';
            body.fulfilment = details.fulfilment;
            body.name = details.name;
            body.phone_country = details.country;
            body.phone = details.phone;
            body.address = delivery ? details.address : null;
            body.latitude = delivery ? details.latitude : null;
            body.longitude = delivery ? details.longitude : null;
            body.client_token = orderToken;
            body.website = details.website;
        } else if (asks) {
            // Only what the owner asks for; the message carries it.
            var toDoor = asked('address') && details.fulfilment === 'delivery';
            if (asked('address')) {
                body.fulfilment = details.fulfilment;
            }
            if (asked('name')) {
                body.name = details.name;
            }
            if (asked('phone')) {
                body.phone_country = details.country;
                body.phone = details.phone;
            }
            if (toDoor) {
                body.address = details.address;
                body.latitude = details.latitude;
                body.longitude = details.longitude;
            }
        }

        // Which version of the order the change was made from: one made
        // meanwhile on another phone is not undone.
        if (editing) {
            body.version = editing.version;
        }

        return body;
    }

    function fail(parts, message) {
        parts.error.textContent = message;
        parts.error.hidden = false;
    }

    function submit(parts) {
        var current = lines();
        if (current.length === 0 || sending || config.closed || waitingFor() || (needsTable() && !editing)) {
            return;
        }

        parts.error.hidden = true;

        if (addingTo()) {
            addToOrder(parts, addingTo(), current);
            return;
        }

        var found = problems();
        if (Object.keys(found).length > 0) {
            showErrors(found);
            fail(parts, strings.check);
            var first = parts.filled.querySelector('[data-field="' + Object.keys(found)[0] + '"] input, [data-field="' + Object.keys(found)[0] + '"] textarea');
            if (first) {
                first.focus();
            }
            return;
        }

        orderToken = orderToken || newToken();

        var token = document.querySelector('meta[name="csrf-token"]');

        sending = true;
        parts.place.disabled = true;
        parts.place.classList.remove('split');
        parts.place.textContent = strings.placing;

        window
            .fetch(editing ? editing.updateUrl : config.orderUrl, {
                method: editing ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload(current)),
            })
            .then(function (response) {
                return response.json().then(function (body) {
                    return { ok: response.ok, status: response.status, body: body };
                });
            })
            .then(function (result) {
                if (!result.ok) {
                    var tableError = result.status === 422 && result.body && result.body.errors && result.body.errors.table;
                    if (tableError) {
                        // The card on the table was replaced: the code this
                        // page has is no table any more.
                        forgetTable();
                        details.fulfilment = firstType();
                        paintFulfilment();
                        throw new Error([].concat(tableError)[0]);
                    }
                    var errors = result.status === 422 ? fieldErrors(result.body && result.body.errors) : {};
                    if (Object.keys(errors).length > 0) {
                        showErrors(errors);
                        throw new Error(strings.check);
                    }
                    throw new Error((result.body && result.body.message) || strings.failed);
                }

                var data = result.body.data || {};

                if (editing) {
                    // Changed: back to the guest's own cart and form.
                    orderToken = null;
                    stopEditing();
                    announce(data);
                    toast(strings.updated, withoutUnavailable(strings.updatedBody, data), data.tracking_url);
                    return;
                }

                // The order is stored. Clear first so a back-navigation shows
                // an empty cart.
                cart = {};
                orderToken = null;
                save();

                if (data.channel === 'menu') {
                    var atTable = details.fulfilment === 'dine_in';
                    var body = atTable
                        ? strings.sentBodyDineIn.replace(':table', tableName()).replace(':reference', data.reference)
                        : strings.sentBody.replace(':reference', data.reference);
                    resetDetails();
                    announce(data);
                    toast(strings.sent, body, data.tracking_url);
                } else if (data.whatsapp_url) {
                    // WhatsApp is what reaches the owner.
                    window.location.href = data.whatsapp_url;
                }
            })
            .catch(function (failure) {
                fail(parts, failure.message || strings.failed);
            })
            .finally(function () {
                sending = false;
                render();
            });
    }

    /** A note at the top of the screen that the order went. */
    function toast(title, body, link) {
        var old = document.querySelector('.menu-toast');
        if (old) {
            old.remove();
        }

        var note = element('div', 'menu-toast');
        note.setAttribute('role', 'status');

        var mark = element('span', 'menu-toast-mark');
        var glyph = element('span', 'icon');
        glyph.innerHTML = icons.check;
        mark.appendChild(glyph);
        note.appendChild(mark);

        var text = element('div', 'menu-toast-text');
        text.appendChild(element('strong', null, title));
        text.appendChild(element('span', null, body));
        if (link) {
            var track = element('a', 'menu-toast-link', strings.track);
            track.href = link;
            // Opens the tracking sheet (menu-order.js) rather than leaving.
            track.setAttribute('data-follow-order', '');
            text.appendChild(track);
        }
        note.appendChild(text);

        var timer;
        function dismiss() {
            window.clearTimeout(timer);
            note.classList.add('leaving');
            window.setTimeout(function () {
                note.remove();
            }, 250);
        }

        note.appendChild(iconButton('menu-toast-close', icons.close, strings.close, dismiss));
        document.body.appendChild(note);
        // Longer with a link in it, to give a thumb time to reach it.
        timer = window.setTimeout(dismiss, link ? 12000 : 8000);
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

    // ---- Changing an order already placed ---------------------------------

    /**
     * The cart's dishes join the guest's order still going: its lines and
     * details come back from the server, the cart's are added to them, and
     * the whole is sent as the order's change. Who and where stay as they
     * were, so nothing is asked again.
     */
    function addToOrder(parts, order, current) {
        sending = true;
        parts.place.disabled = true;
        parts.place.classList.remove('split');
        parts.place.textContent = strings.placing;
        // Kept through a retry: dishes that did go in, with the answer lost
        // on the way back, are not added a second time.
        orderToken = orderToken || newToken();

        var token = document.querySelector('meta[name="csrf-token"]');

        window
            .fetch(config.orderUrl + '/' + order.token + '/cart', { headers: { Accept: 'application/json' }, cache: 'no-store' })
            .then(function (response) {
                return response.ok ? response.json() : Promise.reject(new Error(strings.failed));
            })
            .then(function (body) {
                var data = body.data;
                if (!data.editable) {
                    throw new Error(strings.lockedBody);
                }

                var merged = {};
                data.lines.concat(current.map(function (line) {
                    return { dish: line.id, options: line.options, addons: line.addons, qty: line.quantity };
                })).forEach(function (line) {
                    var key = keyOf(line.dish, line.options, line.addons);
                    merged[key] = merged[key] || { dish_id: Number(line.dish), quantity: 0, options: sorted(line.options), addons: sorted(line.addons) };
                    merged[key].quantity = Math.min(99, merged[key].quantity + line.qty);
                });

                var delivery = data.details.fulfilment === 'delivery';

                return window.fetch(data.update_url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        items: Object.keys(merged).map(function (key) {
                            return merged[key];
                        }),
                        locale: config.locale,
                        mode: 'menu',
                        fulfilment: data.details.fulfilment,
                        name: data.details.name,
                        phone_country: data.details.country || config.country,
                        phone: data.details.phone,
                        address: delivery ? data.details.address : null,
                        note: data.details.note,
                        client_token: orderToken,
                        version: data.version,
                    }),
                });
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
                cart = {};
                orderToken = null;
                save();
                announce(result.body.data);
                toast(strings.added, withoutUnavailable(strings.addedBody, result.body.data), result.body.data.tracking_url);
            })
            .catch(function (failure) {
                fail(parts, failure.message || strings.failed);
            })
            .finally(function () {
                sending = false;
                render();
            });
    }

    /** A change's toast, saying which dishes went because the restaurant
     *  no longer has them. */
    function withoutUnavailable(body, data) {
        var gone = data.unavailable || [];

        return gone.length === 0 ? body : strings.unavailable.replace(':dishes', gone.join(', '));
    }

    /** The tracking sheet (menu-order.js) follows the order just sent. */
    function announce(data) {
        document.dispatchEvent(new CustomEvent('qayema:placed', {
            detail: { reference: data.reference, url: data.tracking_url },
        }));
    }

    /**
     * "Change my order" in the tracking sheet (menu-order.js): the order's
     * lines and details go into the cart, which then sends the change
     * instead of a new order. Only until the restaurant accepts it; the
     * server says so if that has happened.
     */
    function startEditing(token) {
        if (!/^[A-Za-z0-9]{40}$/.test(token)) {
            return;
        }

        window
            .fetch(config.orderUrl + '/' + token + '/cart', { headers: { Accept: 'application/json' }, cache: 'no-store' })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(function (body) {
                var data = body && body.data;
                if (!data) {
                    return;
                }
                if (!data.editable) {
                    toast(strings.locked, strings.lockedBody);
                    return;
                }

                editing = {
                    reference: data.reference,
                    updateUrl: data.update_url,
                    version: data.version,
                    fulfilment: data.details.fulfilment,
                    table: data.details.table,
                };
                orderToken = null;

                cart = {};
                data.lines.forEach(function (line) {
                    cart[keyOf(line.dish, line.options, line.addons)] = {
                        dish: String(line.dish),
                        options: sorted(line.options),
                        addons: sorted(line.addons),
                        qty: line.qty,
                    };
                });

                var open = types();
                details.fulfilment = open.indexOf(data.details.fulfilment) !== -1 ? data.details.fulfilment : firstType();
                details.name = data.details.name;
                details.country = data.details.country || config.country;
                details.phone = data.details.phone;
                details.address = data.details.address;
                details.note = data.details.note;
                details.latitude = null;
                details.longitude = null;
                details.filled = '';
                details.location = 'idle';
                paintDetails();

                render();
                openSheet();
            })
            .catch(function () {
                // The menu works as usual.
            });
    }

    /** Back to the guest's own cart and form, as they were before. */
    function stopEditing() {
        editing = null;
        cart = load();
        details = loadDetails();
        paintDetails();
    }

    render();
    paintFulfilment();
    paintLocation();
    if (asked('phone')) {
        paintCountry();
    }
    if (inMenu) {
        document.addEventListener('qayema:edit', function (event) {
            startEditing(event.detail.token);
        });
        document.addEventListener('qayema:order-state', function (event) {
            going = event.detail;
            render();
        });
    }
})();

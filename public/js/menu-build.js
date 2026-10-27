/**
 * "Build your own" on the menu: the price rule and the words, shared by the
 * dish sheet (menu-dish.js) and the cart (menu-cart.js). The twin of
 * App\Services\Menu\BuildPrice and OrderItem::built(): change one, change
 * both. The server prices every order itself; this is the preview.
 *
 * A choice is {key, name, price, part_prices}; a build is {shape, parts}.
 */
(function () {
    'use strict';

    var FRACTIONS = { 2: '½', 3: '⅓', 4: '¼', 5: '⅕', 6: '⅙', 8: '⅛' };

    function cents(price) {
        return Math.round((parseFloat(price) || 0) * 100);
    }

    /** The price a choice sets for one part of `count`, or null for its share. */
    function setPrice(choice, count) {
        var own = count > 1 && choice.part_prices ? choice.part_prices[String(count)] : undefined;
        return own === undefined || own === null || own === '' || isNaN(parseFloat(own)) ? null : cents(own);
    }

    /**
     * What a build adds, in money: a split build's parts pay their set price
     * for that size, else their share of the whole, the shares added up and
     * rounded once, half up; pieces add up whole.
     */
    function price(shape, choices) {
        var count = choices.length;
        if (count === 0) {
            return 0;
        }

        if (shape !== 'split') {
            return choices.reduce(function (sum, choice) {
                return sum + cents(choice.price);
            }, 0) / 100;
        }

        var shares = 0;
        var set = 0;
        choices.forEach(function (choice) {
            var own = setPrice(choice, count);
            if (own === null) {
                shares += cents(choice.price);
            } else {
                set += own;
            }
        });

        return (Math.floor((2 * shares + count) / (2 * count)) + set) / 100;
    }

    /** One part's price, to show beside the choice: its set price, else its share. */
    function partPrice(shape, choice, count) {
        if (shape !== 'split' || count < 2) {
            return cents(choice.price) / 100;
        }
        var own = setPrice(choice, count);
        return (own === null ? Math.round(cents(choice.price) / count) : own) / 100;
    }

    /** "½ Margherita + ½ Pepperoni", "Margherita", "2 × Vanilla, Chocolate". */
    function text(shape, names) {
        if (shape === 'split' && names.length > 1) {
            var fraction = FRACTIONS[names.length] || '1/' + names.length;
            return names.map(function (name) {
                return fraction + ' ' + name;
            }).join(' + ');
        }

        var counted = [];
        var counts = {};
        names.forEach(function (name) {
            if (!counts[name]) {
                counted.push(name);
            }
            counts[name] = (counts[name] || 0) + 1;
        });

        return counted.map(function (name) {
            return counts[name] > 1 ? counts[name] + ' × ' + name : name;
        }).join(', ');
    }

    /**
     * A cart line's builds checked against the dish's builds as the menu
     * offers them now: their price and words, or null when one is not filled
     * as it allows (the owner changed it since the guest picked).
     *
     * @param {Array} builds  the dish's builds, from #dish-options
     * @param {Array} picked  the line's [{build, parts: [keys]}]
     */
    function priced(builds, picked) {
        var extra = 0;
        var labels = [];

        for (var b = 0; b < builds.length; b++) {
            var build = builds[b];
            var line = (picked || []).filter(function (entry) {
                return entry.build === build.id;
            })[0];
            if (!line || build.parts.indexOf(line.parts.length) === -1) {
                return null;
            }

            var chosen = [];
            for (var p = 0; p < line.parts.length; p++) {
                var choice = build.choices.filter(function (option) {
                    return option.key === line.parts[p];
                })[0];
                if (!choice) {
                    return null;
                }
                chosen.push(choice);
            }

            extra += price(build.shape, chosen);
            labels.push(text(build.shape, chosen.map(function (choice) {
                return choice.name;
            })));
        }

        if ((picked || []).length !== builds.length) {
            return null;
        }

        return { extra: extra, labels: labels };
    }

    window.QAYEMA_BUILD = { price: price, partPrice: partPrice, text: text, priced: priced };
})();

/**
 * "Open now" or "Closed now", and today's hours, worked out on the guest's
 * phone in the restaurant's own timezone, so a copy of the menu that
 * Cloudflare kept from earlier is never wrong about the time. The page is
 * built with the same answer (App\Services\Menu\OpeningHours, the rules this
 * mirrors), so without this file it only goes stale while left open.
 *
 * The cart asks it too (QAYEMA_HOURS.isOpen()), to say when ordering waits
 * for the opening hours. The server still refuses an order placed outside
 * them.
 */
(function () {
    'use strict';

    var config = window.QAYEMA_HOURS;
    if (!config || !window.Intl) {
        return;
    }

    /** As `date('D')` lowercased, Sunday first like Date#getDay. */
    var DAYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    var clock = new Intl.DateTimeFormat('en-US', {
        timeZone: config.timezone,
        weekday: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    });

    /** The restaurant's clock now: the weekday (0 is Sunday) and minutes since midnight. */
    function now() {
        var parts = {};

        clock.formatToParts(new Date()).forEach(function (part) {
            parts[part.type] = part.value;
        });

        return {
            day: DAYS.indexOf(parts.weekday.toLowerCase().slice(0, 3)),
            minutes: (Number(parts.hour) % 24) * 60 + Number(parts.minute),
        };
    }

    function minutesOf(time) {
        var parts = time.split(':');

        return Number(parts[0]) * 60 + Number(parts[1]);
    }

    function shiftsOn(day) {
        return config.week[DAYS[(day + 7) % 7]] || [];
    }

    /**
     * Within a shift, both ends included. One whose close is at or before its
     * open runs past midnight, so yesterday's last shift can still be open.
     */
    function isOpen() {
        var at = now();

        return shiftsOn(at.day).some(function (shift) {
            var opens = minutesOf(shift.open);
            var closes = minutesOf(shift.close);

            return closes <= opens ? at.minutes >= opens : at.minutes >= opens && at.minutes <= closes;
        }) || shiftsOn(at.day - 1).some(function (shift) {
            var closes = minutesOf(shift.close);

            return closes <= minutesOf(shift.open) && at.minutes <= closes;
        });
    }

    function paint() {
        var open = isOpen();
        var today = shiftsOn(now().day).map(function (shift) {
            return shift.open + ' - ' + shift.close;
        }).join(', ');

        document.querySelectorAll('[data-hours-label]').forEach(function (label) {
            label.textContent = open ? config.labels.open : config.labels.closed;
            label.classList.toggle('fact-open', open);
            label.classList.toggle('fact-shut', !open);
        });

        document.querySelectorAll('[data-hours-value]').forEach(function (value) {
            value.textContent = today || config.labels.closedToday;
        });
    }

    config.isOpen = isOpen;
    paint();

    // A menu left open across opening time still turns.
    window.setInterval(paint, 60000);
})();

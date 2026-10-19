# Opening hours

Part of the Qayema backend's architecture notes; CLAUDE.md has the summary.

`restaurants.opening_hours` is a list of shifts per weekday (`null` =
closed): `{mon: [{open: "12:00", close: "15:00"}, {open: "18:00", close:
"23:00"}], ...}`, earliest first, at most `OpeningHours::MAX_SHIFTS` (3).
`restaurants.timezone` is what makes "open now" mean anything.
`App\Services\Menu\OpeningHours` owns the logic: a shift whose close is at
or before its open runs past midnight, and only a day's last shift may;
shifts never overlap (`problemWith()`, checked by `UpdateRestaurantRequest`
in the owner's language). Hours saved before shifts (one `{open, close}` per
day) read as one shift and take the new shape on the next save; a request in
that shape (an app not yet updated) is taken as one shift too. The menu shows
today's shifts ("12:00 - 15:00, 18:00 - 23:00") and is closed between them;
just after midnight, while last night's shift is still going, it shows that
night's shifts instead (`currentShifts()`, twinned in `public/js/menu-hours.js`),
so "Open now" never sits beside the next day's hours or "Closed today";
the search data has one `OpeningHoursSpecification` per shift. The dashboard
(`opening-hours-section.tsx`) and the owner app (`restaurant_screen.dart`)
edit them the same way.

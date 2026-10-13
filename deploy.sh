#!/usr/bin/env bash
#
# Deploys Qayema on the server, from the app's folder:
#
#     bash deploy.sh          only what this release needs
#     bash deploy.sh --full   every step, whatever changed
#
# It looks at what changed between the last commit it deployed (kept in
# storage/framework/deployed-commit) and origin/main, and runs only what that
# needs, so a release that only changes code is a pull and a cache refresh, a
# few seconds with the site up. A `git pull` by hand deploys nothing: this
# still sees those commits as not deployed. Without a record of the last
# deploy (the first run), every step runs:
#
#   always                          pull, `php artisan optimize`, a check of /up
#   composer.json / composer.lock   maintenance mode while `composer install` runs
#   a PHP file added or removed     the class map rebuilt (composer dump-autoload)
#   a migration                     maintenance mode while `migrate` runs
#   the menu page (views, its js
#   and css, translations, menu
#   services)                       every menu Cloudflare keeps is cleared
#
# The queue worker needs nothing: the scheduler starts it fresh every minute.
# Nothing here deletes data. It pulls fast-forward only, so a file edited on
# the server stops it rather than being merged over. A step that fails stops
# it and prints FAILED; the site is never left in maintenance mode by a
# failed step, except when the app no longer starts.
#
# A change to .env alone needs no deploy: `php artisan optimize`.
# Deploy only a commit CI passed (docs/release-checklist.md).

set -euo pipefail

cd "$(dirname "$0")"

PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"
FULL=false
[ "${1:-}" = "--full" ] && FULL=true

DEPLOYED=storage/framework/deployed-commit

git fetch --quiet origin main
NEW="$(git rev-parse origin/main)"
OLD="$(cat "$DEPLOYED" 2>/dev/null || true)"
if ! git cat-file -e "${OLD:-none}^{commit}" 2>/dev/null; then
    OLD="$(git rev-parse HEAD)"
    FULL=true
fi

if [ "$OLD" = "$NEW" ] && [ "$(git rev-parse HEAD)" = "$NEW" ] && ! $FULL; then
    echo "Already deployed $(git rev-parse --short HEAD): nothing to deploy."
    exit 0
fi

# What changed, and which steps that calls for.
changed() { [ -n "$(git diff --name-only "$OLD" "$NEW" -- "$@")" ]; }
added_or_removed_php() { [ -n "$(git diff --name-only --diff-filter=ADR "$OLD" "$NEW" -- app database | grep '\.php$' || true)" ]; }

PACKAGES=$FULL;   changed composer.json composer.lock && PACKAGES=true
CLASSES=$FULL;    added_or_removed_php && CLASSES=true
MIGRATIONS=$FULL; changed database/migrations && MIGRATIONS=true
MENUS=$FULL;      changed resources/views/menu public/js public/css lang \
                          app/Http/Controllers/PublicMenuController.php app/Services/Menu && MENUS=true

DOWN=false
if $PACKAGES || $MIGRATIONS; then DOWN=true; fi

echo "Deploying $(git rev-parse --short "$OLD") -> $(git rev-parse --short "$NEW")" \
    "(packages: $PACKAGES, new classes: $CLASSES, migrations: $MIGRATIONS, menus: $MENUS, maintenance mode: $DOWN)"

if $DOWN; then
    # While packages are swapped or the database changes, a request landing
    # mid-way would fail: guests see "back in a moment" instead. Menus
    # Cloudflare already holds keep being served.
    "$PHP" artisan down --retry=15
    trap '"$PHP" artisan up' EXIT
fi

git merge --ff-only --quiet "$NEW"

# classmap-authoritative: classes load from the map alone, no file lookups;
# so a new class is found only once the map is rebuilt.
if $PACKAGES; then
    "$COMPOSER" install --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative
elif $CLASSES; then
    "$COMPOSER" dump-autoload --no-dev --optimize --classmap-authoritative
fi

if $MIGRATIONS; then
    "$PHP" artisan migrate --force
fi

# Config, routes, events and views cached for the new code (about 120 ms of
# every request without it).
"$PHP" artisan optimize

# The app starts with the new code. If it does not, guests get the "back in
# a moment" page rather than errors.
if ! "$PHP" artisan about --only=environment > /dev/null; then
    trap - EXIT
    "$PHP" artisan down --retry=15 > /dev/null 2>&1 || true
    echo "FAILED: the app does not start (php artisan about). The site is in maintenance mode; see storage/logs, fix, deploy again (or php artisan up)." >&2
    exit 1
fi

if $MENUS; then
    "$PHP" artisan menus:forget-all
fi

if $DOWN; then
    "$PHP" artisan up
    trap - EXIT
fi

git rev-parse HEAD > "$DEPLOYED"

# Asked the way guests ask: the public address, through Cloudflare (which
# adds the origin secret). Not 127.0.0.1: on shared hosting that answers with
# the host's own certificate, not the site's. Retried for a minute and a
# half: on the first deploy with the scheduler check, its heartbeat appears
# within a minute.
HOST="$(grep -E '^APP_URL=' .env | cut -d= -f2- | sed -E 's#^"?https?://(www\.)?##; s#[/"].*$##')"
if ! curl -fsS -o /dev/null --max-time 10 --retry 6 --retry-delay 15 --retry-all-errors \
    "https://$HOST/up"; then
    echo "FAILED: https://$HOST/up does not answer 200. The site is up; see storage/logs (\"Health check failed\")." >&2
    exit 1
fi

echo "Deployed $(git rev-parse --short HEAD); /up answers."

#!/usr/bin/env bash
#
# Deploys Qayema on the server, from the app's folder:
#
#     bash deploy.sh
#
# Guests see Laravel's "back in a moment" page while it runs (about half a
# minute), instead of errors from code half pulled. It stops at the first
# step that fails and puts the site back up either way; nothing it does
# deletes data. Pulls fast-forward only, so a file edited on the server
# stops it rather than being merged over.
#
# Config, routes, events and views are cached at the end. From then on, a
# change to .env takes effect only after `php artisan optimize` (or
# `php artisan config:cache`).

set -euo pipefail

cd "$(dirname "$0")"

PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"

echo "Deploying $(git rev-parse --short HEAD) -> origin/main"

"$PHP" artisan down --retry=15
trap '"$PHP" artisan up' EXIT

git pull --ff-only origin main
"$COMPOSER" install --no-dev --no-interaction --prefer-dist --optimize-autoloader
"$PHP" artisan migrate --force
"$PHP" artisan optimize

echo "Deployed $(git rev-parse --short HEAD)."

# Releasing

The owner releases; Claude never runs any of this (CLAUDE.md, Working Rules).

1. **CI is green** for the commit, in both repos (GitHub, Actions):
   - `qayema`: style, static analysis, every test at 99% coverage, the
     package audit.
   - `qayema-dashboard`: lint, types, formatting, unit tests, the build and
     its size budget, `dist/` matching the source, the package audit, and
     the end-to-end suite on the built dashboard.

   The screenshot tests run only on the owner's machine
   (`npm run e2e -- --project=visual --project=visual-phone`); run them
   when the look of a page changed.
2. **The backend:** on the server, in `~/qayema.com`, run `bash deploy.sh`.
   It pulls and runs only what the release needs: a code-only change is a
   pull and `optimize`, with the site up; new packages or migrations add
   maintenance mode while they install; a change to the menu page clears the
   menus Cloudflare keeps. Then it checks the app starts and asks `/up`. It
   stops at the first step that fails and says FAILED. `--full` runs every
   step.
3. **The dashboard:** upload `dist/` to `~/dashboard.qayema.com`
   (`.htaccess` included).
4. **After:** from outside,
   - `curl -sI https://qayema.com/<a menu>`: no `set-cookie`, and
     `cache-control: max-age=0, public, s-maxage=3600`;
   - an order goes through on a test menu;
   - visits keep arriving in the analytics;
   - the Grafana alerts (docs/grafana-alerts.md) stay quiet.

A change to `.env` alone needs only `php artisan optimize` on the server.

# Grafana alerts

The alerts to set up in Grafana Cloud (Alerting, Alert rules, New alert
rule), so a problem is seen when it starts rather than when someone looks.
Each one below has the query to paste, when it fires, and what to do.

Everything from production carries `deployment_environment_name="prod"`
(docs/grafana-alloy.md). Metrics come from the traces (Alloy's
`spanmetrics`); the label names below are its defaults plus the route and
status code. If Explore shows a label under another name, use that one.

Send them to a contact point that reaches a phone (Grafana's mobile app,
email, or a Slack channel). Evaluate every minute unless said otherwise.

## 1. Errors in the log

The app logged an error: an exception, a failed health check, a purge that
gave up.

```logql
sum(count_over_time({service_name="qayema", deployment_environment_name="prod"} | detected_level="error" [5m]))
```

- Fires: above 0 for 1 minute.
- Then: open the log lines (same query without `sum(count_over_time(...))`);
  each carries `trace_id`, `route`, `restaurant` and `user_id`.

## 2. The site is down or unhealthy

`/up` answers 500 when the database, the cache, the scheduler or the queue
is wrong (App\Services\Health\HealthChecks). Set it up as a Synthetic
Monitoring HTTP check (Testing & synthetics, Synthetics, Add new check):

- URL `https://qayema.com/up`, every 1 minute, from 2 probes (Frankfurt, and
  one more), expecting status 200.
- Alert: the check fails from both probes for 3 minutes.
- Then: the log has a "Health check failed: ..." line naming what is wrong.

## 3. Server errors (5xx)

```promql
sum(rate(traces_span_metrics_calls_total{deployment_environment_name="prod", span_kind="SPAN_KIND_SERVER", http_response_status_code=~"5.."}[5m]))
/
sum(rate(traces_span_metrics_calls_total{deployment_environment_name="prod", span_kind="SPAN_KIND_SERVER"}[5m]))
```

- Fires: above 0.02 (2% of requests) for 5 minutes.
- Then: Explore, Tempo, `{resource.deployment.environment.name="prod" && status=error}`.

## 4. Slow pages

```promql
histogram_quantile(0.95, sum by (le) (rate(traces_span_metrics_duration_milliseconds_bucket{deployment_environment_name="prod", span_kind="SPAN_KIND_SERVER"}[10m])))
```

- Fires: above 1500 (ms) for 10 minutes.
- Then: the slowest traces in Tempo; a slow span in a busy minute may be the
  host (one shared core), not the code.

## 5. A menu purge gave up

Cloudflare refused to clear a menu five times; guests may see an old menu
until it expires (at most an hour).

```logql
sum(count_over_time({service_name="qayema", deployment_environment_name="prod"} |= "Menu cache purge failed" [15m]))
```

- Fires: above 0.
- Earlier warning, not paged: "Menu cache purge refused" (the first refusal,
  retried from the queue).

## 6. Errors in owners' dashboards

What broke in a browser: a page that showed "Something on this page went
wrong", a script error (`POST /api/client-errors`), and what the dashboard's
Content-Security-Policy blocked (`POST /api/csp-report`).

```logql
sum(count_over_time({service_name="qayema", deployment_environment_name="prod"} |= "Dashboard error:" [15m]))
```

- Fires: above 5 in 15 minutes (one owner's bad connection is not an alert).
- CSP: watch `|= "Dashboard CSP blocked:"` for a week before switching the
  dashboard's policy from Report-Only to enforced (public/.htaccess in
  qayema-dashboard); no alert needed.

## 7. Lazy loading in production

A relation read one row at a time (N+1) that the tests missed.

```logql
sum(count_over_time({service_name="qayema", deployment_environment_name="prod"} |= "Lazy loading:" [1h]))
```

- Fires: above 0 (evaluate hourly; a warning, not a page).
- Then: the line names the model and relation; eager load it where the log
  line's `route` reads it.

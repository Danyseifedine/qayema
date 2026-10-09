# Sending traces and logs through Grafana Alloy

## Why

PHP has no background thread, so without an agent every request ends by
sending its traces and logs to Grafana Cloud over the internet. The guest
already has the page by then, but that PHP worker stays busy for 1 to 3
seconds, and under load that means fewer workers free for guests.

Alloy is Grafana's small agent. It runs on the server next to the app:

- PHP hands it the traces and logs on `127.0.0.1:4318` in about a millisecond.
- Alloy batches them and sends them to Grafana Cloud in the background.

If Alloy is ever stopped, PHP gets "connection refused" straight away. Nothing
slows down; that telemetry is lost until Alloy is back.

## 1. Install Alloy (Debian or Ubuntu)

```bash
sudo mkdir -p /etc/apt/keyrings
wget -q -O - https://apt.grafana.com/gpg.key | gpg --dearmor | sudo tee /etc/apt/keyrings/grafana.gpg > /dev/null
echo "deb [signed-by=/etc/apt/keyrings/grafana.gpg] https://apt.grafana.com stable main" | sudo tee /etc/apt/sources.list.d/grafana.list
sudo apt-get update && sudo apt-get install alloy
```

Other systems: https://grafana.com/docs/alloy/latest/set-up/install/

## 2. Give Alloy the Grafana Cloud details

Add these lines to `/etc/default/alloy`. Use the values that are in the app's
`.env` today.

```bash
GRAFANA_CLOUD_OTLP_ENDPOINT=<the app's current GRAFANA_OTLP_ENDPOINT>
GRAFANA_INSTANCE_ID=<GRAFANA_INSTANCE_ID>
GRAFANA_TOKEN=<GRAFANA_TOKEN>
```

## 3. Alloy's config

Replace `/etc/alloy/config.alloy` with:

```alloy
// Qayema: the app sends to Alloy on this machine; Alloy batches and forwards.
otelcol.receiver.otlp "app" {
  http {
    endpoint = "127.0.0.1:4318"
  }

  output {
    traces = [otelcol.processor.batch.app.input]
    logs   = [otelcol.processor.batch.app.input]
  }
}

otelcol.processor.batch "app" {
  output {
    traces = [otelcol.exporter.otlphttp.grafana.input]
    logs   = [otelcol.exporter.otlphttp.grafana.input]
  }
}

otelcol.auth.basic "grafana" {
  username = sys.env("GRAFANA_INSTANCE_ID")
  password = sys.env("GRAFANA_TOKEN")
}

otelcol.exporter.otlphttp "grafana" {
  client {
    endpoint = sys.env("GRAFANA_CLOUD_OTLP_ENDPOINT")
    auth     = otelcol.auth.basic.grafana.handler
  }
}
```

Then:

```bash
sudo systemctl enable --now alloy
sudo systemctl restart alloy
sudo systemctl status alloy   # should say "active (running)"
```

## 4. Point the app at Alloy

In the app's `.env`:

```bash
GRAFANA_OTLP_ENDPOINT=http://127.0.0.1:4318
GRAFANA_AUTH_HEADER=
```

`GRAFANA_INSTANCE_ID` and `GRAFANA_TOKEN` can stay; the app no longer reads
them. Then run `php artisan config:cache`.

With no auth header set, the app sends none (`config/opentelemetry.php`);
Alloy adds Grafana's credentials itself.

## 5. Check it works

1. Open a menu page.
2. Within about a minute, its trace shows in Grafana: Explore, then Tempo,
   then `{resource.service.name="qayema"}`.
3. If nothing arrives, Alloy's own log says why:

   ```bash
   sudo journalctl -u alloy -n 50
   ```

## Production today (A2 shared hosting, no root)

There is no `apt` or `systemctl` on the shared server, so Alloy runs as the
account's own process, all inside the app's folder (git ignores
`storage/app`, and the web cannot reach it):

- `~/qayema.com/storage/app/alloy/alloy`: the official v1.20.1 binary
  (checksum-checked), about 550 MB.
- `config.alloy`: receives on `127.0.0.1:43180` (not 4318: `localhost` is
  shared with other accounts on the machine), status page on
  `127.0.0.1:43181`.
- `alloy.env` (private): `GRAFANA_CLOUD_OTLP_ENDPOINT`, `GRAFANA_INSTANCE_ID`,
  `GRAFANA_TOKEN`, `GOMEMLIMIT=256MiB`. The token ends with `=`; copy it
  whole.
- `run.sh`, started every minute by a crontab line under `flock`, so it is
  back within a minute if it stops. Its output goes to `alloy.log`.

The app's `.env`: `GRAFANA_OTLP_ENDPOINT=http://127.0.0.1:43180`,
`GRAFANA_AUTH_HEADER=` (empty), `OTEL_METRICS_EXPORTER=null`,
`OTEL_RESOURCE_ATTRIBUTES=deployment.environment.name=prod`, then
`php artisan config:cache`.

**Metrics come from the traces**, not from PHP: Alloy's `spanmetrics`
connector turns every span into `traces_span_metrics_calls_total` and
`traces_span_metrics_duration_milliseconds_*` (per route, status code and
database query). PHP can only send its own metrics per request (delta), and
Grafana Cloud refuses delta histograms; the converter that would fix that
(`otelcol.processor.deltatocumulative`) is still experimental in Alloy.

In Grafana everything from production carries
`deployment_environment_name="prod"`:

- Traces: `{resource.deployment.environment.name="prod"}`
- Logs: `{service_name="qayema", deployment_environment_name="prod"}`
- Metrics: `traces_span_metrics_calls_total{deployment_environment_name="prod"}`


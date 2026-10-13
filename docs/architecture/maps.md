# Maps

Part of the Qayema backend's architecture notes; CLAUDE.md has the summary.

`restaurants.google_maps_url` is the only thing stored; there are no lat/lng
columns. `App\Services\Menu\MapPoint` reads the point back out of that URL
(`?q=`, `?ll=`, `?query=`, `/@lat,lng,17z`) and builds a **keyless
OpenStreetMap** embed: Google's needs an API key and a billing account, and
this page is scanned all day. A shortened `maps.app.goo.gl` link hides its
coordinates behind a redirect, so it gets the directions button but no map.

It mirrors `parseMapCoordinates` / `mapEmbedUrlFor` in the dashboard
(`src/features/settings/hooks/use-current-location.ts`); change one, change
both.

**The map frame must send a Referer.** OSM's tile policy requires one and
forbids a restrictive referrer policy, so the iframe sets no `referrerpolicy`
and uses `sandbox="allow-scripts allow-same-origin"`. A bare `allow-scripts`
puts the frame in an opaque origin, the tile requests arrive anonymous, and
every tile comes back **403 Access blocked**. `allow-same-origin` restores
openstreetmap.org's own origin, not ours, so the frame still cannot reach the
page. The dashboard's `location-field.tsx` carries the same pair.

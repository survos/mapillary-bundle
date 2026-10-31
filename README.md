# survos/mapillary-bundle

Thin Symfony integration for `survos/mapillary`, using Survos kit-bundle conventions. PHP 8.5, Symfony 8.1+.

```sh
composer require survos/mapillary-bundle
```

Flex registers `Survos\MapillaryBundle\SurvosMapillaryBundle`; kit is a required bundle. Without Flex, register this bundle and `Survos\Kit\SurvosKitBundle` in `config/bundles.php`.

```yaml
# config/packages/survos_mapillary.yaml
survos_mapillary:
    access_token: '%env(MAPILLARY_ACCESS_TOKEN)%'
    client_id: '%env(MAPILLARY_CLIENT_ID)%' # optional public identifier
    # base_uri: 'https://graph.mapillary.com/'
    # http_client: 'http_client'
    # max_retries: 2
```

Keep the public client ID in `.env`, credentials in `.env.local` or your secret store. The client secret is not used for public image reads. Autowire `Survos\Mapillary\MapillaryClientInterface` in application services.

The bundle owns its serializer, configures bounded Symfony GET retries (including Retry-After), and adds no cache. Set `max_retries: 0` to leave retries to the application. Redirects are disabled to prevent leaking authentication.

```sh
# Central Budapest sample, not all Budapest imagery
php bin/console mapillary:images:search '19.035,47.490,19.065,47.510' --limit=20
```

The command returns source links and photographer credits, not credentials or signed CDN URLs. Panorama-only is the default; `--include-flat` includes perspective images. Bbox searches have no pagination and must be smaller than 0.01 square degrees. See the client contract for limits and supported endpoints.

Install published packages normally, then run `php ../mono/link .` from the consuming project for local source linking; do not add path repositories. No deployment is involved.

Tests: use the mono root PHPUnit runner with `-c bu/mapillary-bundle/phpunit.xml.dist`, or install package dependencies and run its PHPUnit. The minimal Symfony kernel test verifies service wiring and command execution offline.

Nearby search: `nearbyImages(new NearbySearch(47.507, 19.046, radius: 50, limit: 20))` returns at most 100 candidates within 50 meters, ranked by the server with panorama preference. The API does not document a panorama-only filter for radius requests. The command `mapillary:images:nearby 47.507 19.046 --limit=20` filters the returned candidates locally to panoramas; `--include-flat` shows all. Neither radius nor bbox samples guarantee completeness.

Laravel GOG Scanner
===================

A Laravel 11/12 package that scans the GOG catalog using queues and stores a fully normalized product schema (games and related data) in your database. It ships with an Artisan command and queued jobs that paginate through the public listing API and then fetch detailed product information per game.

- Framework: Laravel 11/12
- PHP: 8.1+
- Transport: Laravel HTTP Client (no SDK or API key required)

Features
--------
- Queue-first design: paginated listing scan, then per-product detail scan.
- Fully normalized schema for prices, availability, sales visibility, OS support, genres, languages, companies, categories, gallery, screenshots, and artifacts.
- Sensible defaults with publishable config.
- Package migrations are auto-loaded by the service provider.

Installation
------------
1) Require the package in your application

```
composer require artryazanov/laravel-gog-scanner
```

2) (Optional) Publish the configuration

```
php artisan vendor:publish --provider="Artryazanov\\GogScanner\\GogScannerServiceProvider" --tag=config
```

3) Run your migrations

The package automatically loads its migrations. If you want to explicitly run them:

```
php artisan migrate
```

Configuration
-------------
See `config/gogscanner.php` (publish to override):

- `api_base`: Base URL for Galaxy API (details), default `https://api.gog.com`.
- `embed_base`: Base URL for embed API (listing), default `https://embed.gog.com`.
- `list_endpoint`: Listing endpoint, default `/games/ajax/filtered`.
- `detail_endpoint`: Detail endpoint template, default `/products/{id}`.
- `expand_fields`: Comma-separated sections for details (downloads, description, screenshots, videos, etc.).
- `default_listing_params`: Default query params for listing (e.g., `mediaType => game`, `limit`, `search`, `sort`, etc.).
- `http_timeout`: HTTP timeout in seconds, default `30`.
- `queue.connection` / `queue.queue`: Optional queue connection and name for dispatching jobs.

Example override in `config/gogscanner.php` (after publishing):

```php
return [
    'default_listing_params' => [
        'mediaType' => 'game',
        'limit' => 48,
        'sort'  => 'popularity',
    ],
    'queue' => [
        'connection' => 'redis',
        'queue' => 'gog-scanner',
    ],
];
```

Usage
-----
Kick off a scan with the built-in Artisan command to enqueue the first page of the listing scan:

```
php artisan gog:scan           # starts from page 1
php artisan gog:scan 5         # starts from page 5
```

- The command dispatches `ScanPageJob` for the requested page.
- `ScanPageJob` fetches the listing page, upserts games and listing data, enqueues a `ScanGameDetailJob` per product, and (if more pages are available) enqueues the next page.
- `ScanGameDetailJob` fetches the product detail JSON and writes 1:1 and 1:N relations (compatibility, links, languages, images, descriptions, downloads/artifacts, screenshots, etc.).

Make sure your queue worker is running:

```
php artisan queue:work
```

HTTP Endpoints used
-------------------
- Listing (embed API): `GET {embed_base}/games/ajax/filtered`
  - Common params: `mediaType, page, search, category, system, limit, sort, ...`
- Details (Galaxy API): `GET {api_base}/products/{id}?expand=downloads,expanded_dlcs,description,screenshots,videos,related_products,changelog`

No authentication is required for the endpoints above.

Database Schema
---------------
The package includes migrations for a normalized schema starting with `gog_games` (non-auto-incrementing primary key equals GOG product id). Most 1:1 fields are stored directly on `gog_games` (availability, works-on flags, content compatibility, links, description, in-development flags). Related tables include:

- 1:1: `gog_game_prices`, `gog_game_sales_visibilities`, `gog_game_images`.
- 1:N: `gog_game_galleries`, `gog_game_dlcs`, `gog_game_artifacts` (+ `gog_game_artifact_files`), `gog_game_screenshots` (+ `gog_game_screenshot_images`), `gog_game_videos`.
- Dictionary + pivot: `gog_game_genres` + `gog_game_genre` (genres) and `gog_game_languages` + `gog_game_language` (languages).
- Dictionary + pivot: `gog_game_supported_systems` + `gog_game_supported_system` (supported OS).
- Dictionary: `gog_game_companies`.
- Pivots: `gog_game_developers` and `gog_game_publishers` (allow multiple companies per role).
- Dictionary: `gog_game_categories` with `gog_games.category_id` and `gog_games.original_category_id` referencing it.

Data Mapping
------------
- Developers/Publishers: listing fields `developer` and `publisher` may contain multiple companies separated by commas. They are split, normalized to `gog_game_companies`, and linked via `gog_game_developers` and `gog_game_publishers`.
- Categories: listing `category` and `originalCategory` map to `gog_game_categories` and are referenced by `gog_games.category_id` and `gog_games.original_category_id`.
- OS support: listing `worksOn` booleans are kept on `gog_games` (`works_on_windows/mac/linux`). The array `supportedOperatingSystems` is normalized to dictionary `gog_game_supported_systems` and linked via pivot.
- Languages: detail `languages` map to dictionary `gog_game_languages` and to `gog_game_language` pivot.
- Genres: listing `genres` map to dictionary `gog_game_genres` and to `gog_game_genre` pivot.

See `database/migrations/*create_gog_schema.php` for exact columns and constraints.

Extending & Customizing
-----------------------
- Tweak HTTP behavior via `http_timeout` and `expand_fields`.
- Adjust default listing filters with `default_listing_params`.
- Route jobs to a dedicated connection/queue using `queue.connection` / `queue.queue`.
- Wrap calls with your own rate limiting or retries by extending the jobs.

Testing
-------
This repository includes a Testbench-based test suite:

- Unit/feature tests for service provider, console command, and jobs.
- HTTP calls are stubbed with `Http::fake()`; queued work is asserted via `Bus::fake()`.

Run the tests:

```
composer install
composer test
```

Production Tips
---------------
- Run queue workers with sufficient concurrency to process listing and detail jobs efficiently.
- Consider rate-limiting (e.g., Laravel middleware or external proxies) if you scan large portions of the catalog.
- Use a persistent queue (e.g., Redis) for resilience.

License
-------
Unlicense — see `LICENSE` for details.

Credits
-------
- Author: Artem Ryazanov <artryazanov@gmail.com>

# Game News Starter

A Laravel game news CMS ready for a new site design. It includes posts, categories, tags, games, author profiles, a flexible content builder, view tracking, and an admin panel.

The public Blade templates are retained as a functional reference, including their CSS classes. The previous site's styles, fonts, logos, favicons, social preview image, verification tokens, and advertising identity have been removed. AdminLTE, admin/editor styles, and the shared content-block renderer are retained.

## Starting a new skin

1. Copy `.env.example` to `.env` for a new installation. Set `APP_NAME` and `APP_URL` for the new site and configure its database and services.
2. Install dependencies with `composer install` and `npm ci`, then run `php artisan key:generate`.
3. On a new database, run `php artisan migrate --seed` and `php artisan storage:link`.
4. Add the new public styles and assets. Load styles through the `styles` section in `resources/views/layouts/app.blade.php`, and adapt the existing views for the new design. Preserve `resources/views/components/content-blocks.blade.php` and the vendor views when replacing templates; the vendor pagination markup can be restyled.
5. Run `npm run build` to build the admin Vue components.

The header and footer use `APP_NAME` unless overridden through their CMS `site-name` fields. Open Graph site identity and default canonical URLs use `APP_NAME` and `APP_URL`. Set social preview images through the `meta-image` Blade section, and add favicons and site verification tags for the new site.

Google Analytics is optional: set `GA_MEASUREMENT_ID` to enable it outside the local environment. Configure the new site's advertising file and sitemap URL in `public/robots.txt` when deploying.

Existing databases, uploads, and local `.env` values are not reset by this cleanup. Review their site names, page content, metadata, and service IDs before reusing them for a new site; the updated seeders provide neutral defaults for new installations.

## GTA VI public theme

The public site now has a responsive GTA VI editorial theme. Read [the design plan](docs/design-plan.md) for research, palette, typography, image attribution, and layout decisions, and [the health report](docs/project-health.md) for validation and remaining issues.

Public assets are served directly from `public/css/site.css`, `public/js/site.js`, `public/fonts`, and `public/images/gta-vi`. No running Vite process is required for the public theme; `npm run build` still builds the admin Vue components.

On this workstation the app runs at **http://localhost:8086**. The local `.env` uses `APP_PORT=8086`, `FORWARD_DB_PORT=3308`, `VITE_PORT=5186`, and `APP_URL=http://localhost:8086` to avoid the other running projects. Use `./vendor/bin/sail up -d` to restart it. `DEBUGBAR_ENABLED=false` hides the debug toolbar for visual previews.

For a new Sail installation, use the usual MySQL service credentials (`DB_HOST=mysql`) and set the desired ports in `.env`. Then run:

```sh
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link
npm ci
npm run build
```

Optional local preview content:

```sh
./vendor/bin/sail artisan db:seed --class=GtaNewsPreviewSeeder
```

This seeder runs only in `local` or `testing` and refuses to run if any posts already exist, including deleted posts. It creates seven original starter articles with official source links and visible preview attribution. It does not run during normal production seeding. Publish and edit your own articles through the existing CMS; the latest published news article becomes the home feature.

The existing PHP tests use the separate MySQL `testing` database. Initialize it before the first test run:

```sh
docker compose exec -e APP_ENV=testing -e DB_DATABASE=testing laravel.test php artisan migrate --seed
./vendor/bin/sail artisan test
./vendor/bin/sail artisan view:cache
```

Fonts are self-hosted Barlow Condensed and DM Sans; their SIL Open Font License files are in `public/fonts`. GTA VI promotional artwork is attributed to Rockstar Games in the site footer and design plan. Supply your publication's legal policies and configure reCAPTCHA before launching the contact form with spam protection.

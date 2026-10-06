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

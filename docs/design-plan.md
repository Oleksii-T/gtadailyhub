# GTA VI News design plan

Research and initial health check: 6 October 2026.

## Official references

- [GTA VI](https://www.rockstargames.com/VI): the current home page uses a midnight background, cinematic illustrations and photography, very large Art Deco headings, warm cream text, pink buttons, and spacious sections. Its published styles include `#111117`, `#fff9cb`, and `#f976b0`. It loads Rockstar's proprietary GTAArtDeco font family.
- [Rockstar Newswire](https://www.rockstargames.com/newswire): the editorial reference for image-led stories, dates, and a direct hierarchy between featured and recent news.
- The VI site's people-and-places and media sections inform the topic navigation and imagery. Current launch details should always be checked on the official site; avoid a hardcoded countdown or purchase flow in this independent blog.

## Design direction

An independent publication from Vice City: cinematic atmosphere with the clarity of a news desk. Use an original GTA VI NEWS wordmark, a small LIVE FROM LEONIDA signature, oversized condensed headlines, fine rules, numbered sections, and restrained pink/peach highlights. Keep the reading surface dark and relatively quiet.

| Token | Value | Role |
| --- | --- | --- |
| Night | `#111117` | Main page background |
| Surface | `#1b1b25` | Panels, input backgrounds |
| Cream | `#fff9cb` | Display headings |
| Paper | `#f4f1ed` | Body and UI text |
| Pink | `#f976b0` | Links, focus, active state |
| Peach | `#ffc09a` | Sunset gradients and secondary accents |
| Muted | `#b2adb9` | Supporting text |

Typography: self-hosted Barlow Condensed Bold for headlines and DM Sans for body/UI, both under the SIL Open Font License. These provide a similar condensed editorial character without bundling Rockstar's proprietary fonts. Body text has a generous line height and article text is limited to a comfortable reading width.

## Implementation sequence

1. Resolve the first-install bootstrap/seed problems found in Sail and establish a runnable baseline.
2. Add a shared public stylesheet, original SVG favicon, local fonts, and optimized WebP artwork with provenance below.
3. Replace the home page with a large editorial introduction, dynamic featured article, recent-news grid, topic rail, and an official media feature. Empty databases receive an explicit publishing empty state.
4. Rebuild the shared header/footer with working category/topic links, GET search, keyboard-accessible mobile navigation, and independent-publication attribution.
5. Style article, category, author, game, legal, contact, pagination, and content-block views. Preserve the CMS and admin templates.
6. Provide an optional local-only preview seeder with original source-linked starter articles. Never replace existing posts. Preview articles remain editable through the CMS.
7. Verify production asset build, Blade compilation, existing PHP tests, route responses, searches, filters, pagination, keyboard navigation, desktop/mobile layouts, image loading, and console errors.

## Responsive and accessibility requirements

- Desktop: max-width 1280px, generous gutters, two-column feature and a news grid/sidebar.
- Tablet: reduce display size and collapse the sidebar.
- Mobile: one column, usable menu/search, no horizontal page overflow, 44px interaction targets.
- Visible keyboard focus, skip link, semantic main/section/nav elements, descriptive image alternatives, reduced-motion support, and no autoplay or mandatory animation.
- Features and cards use real CMS URLs; search uses the existing category query. Do not add nonfunctional newsletter subscriptions or decorative controls.

## Image provenance

The local WebP files are optimized copies of Rockstar's publicly displayed GTA VI promotional media. Copyright remains with Rockstar Games. Attribution appears in the footer. The design uses an original publication wordmark rather than presenting the blog as an official Rockstar property.

| Local asset | Official source |
| --- | --- |
| `poster.webp` | `https://www.rockstargames.com/VI/_next/static/media/poster_full.0az_iud2g3y4j.jpg` |
| `trailer2.webp` | `https://www.rockstargames.com/VI/_next/static/media/trailer-2.0l.7aqib91fz6.jpg` |
| `trailer1.webp` | `https://www.rockstargames.com/VI/_next/static/media/trailer-1.06b6paykx247w.jpg` |
| `leonida.webp` | `https://www.rockstargames.com/VI/_next/static/media/Hero_BG.0dwp8ucivdr~j.jpg` |
| `featured.webp` | `https://www.rockstargames.com/VI/_next/static/media/featured.0y5yxwenpsgc3.jpg` |

## Baseline findings

- Laravel 10; Sail uses PHP 8.2 and MySQL 8.0. Host PHP 8.4 emits dependency deprecations and cannot resolve Sail's `mysql` hostname.
- Docker works; ports 3306 and 5173 are occupied by other projects. This project uses HTTP 8086, MySQL 3308, and Vite 5186 locally.
- A fresh database originally prevented Artisan boot because routes queried `redirects` before migration. Add a console-only guard for the initial install.
- `AuthorSeeder` omits required `description` and `description_small` fields. Supply empty defaults and make author seeds repeatable.
- The review-policy footer link had no corresponding default page. Include it in the page seeder.
- The dedicated `testing` database needs migrations and base seeds before the existing tests can run.
- `npm ci` reports 21 existing dependency vulnerabilities (1 low, 4 moderate, 13 high, 3 critical). Dependency upgrades are separate from the theme implementation.

Final validation results are recorded in `docs/project-health.md`.

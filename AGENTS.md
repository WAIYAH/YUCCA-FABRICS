# AGENTS.md — Yucca Fabrics

Instructions for AI coding agents working in this repository.

## Project

Yucca Fabrics is an e-commerce site for upholstery fabrics, upholstery services and interior-design
consultations (Kenya, prices in KES). Customers browse and buy fabrics, request services and book
consultations; staff manage everything from `/admin`.

## Stack

- PHP 8.2 (no framework, no Composer) with PDO, running on XAMPP (Apache 2.4 + MariaDB 10.4).
- Tailwind CSS from the CDN, configured inline in `includes/header.php` and `includes/admin-header.php`.
  Brand colours: `forest` #2E7D32, `sage` #F9A825, `clay` #FF9800, `sand` #F8F9FA, `ink` #333333.
  Fonts: Inter (body) and Poppins (`font-serif`, headings).
- Small hand-written CSS in `assets/css/style.css` and vanilla JS in `assets/js/main.js`.
- M-Pesa (Safaricom Daraja) payments in `payment/`, sandbox by default.

## Layout

| Path | Purpose |
| --- | --- |
| `*.php` (root) | Public pages: home, shop, product, cart, checkout, services, consultations, gallery, blog |
| `includes/` | `functions.php` (helpers), `header.php`/`footer.php`, `product-card.php`, `fabric-collection.php` |
| `account/` | Signed-in customer area |
| `admin/` | Staff back office, one folder per section |
| `api/` | JSON endpoints called by `assets/js/main.js` |
| `auth/` | Login, register, password reset |
| `config/` | `config.php` (reads `.env`) and `database.php` (`db()` PDO factory); web access denied |
| `database/` | `yucca_fabrics.sql` schema and `create_admin.php` (CLI only); web access denied |
| `assets/images/` | Site images tracked in git: logo and `fabrics/` lookbook photos |
| `uploads/` | Admin/customer uploads only; gitignored, PHP execution denied |

## Local setup

1. Copy `.env.example` to `.env` and fill in the database credentials. Never commit `.env`.
2. Import `database/yucca_fabrics.sql` into MariaDB.
3. Create an admin: `C:\xampp\php\php.exe database\create_admin.php "Name" email username`.
4. Serve through XAMPP so `.htaccess` rules apply (sitemap/robots rewrites, denied files).

`php` and `mysql` are not on PATH; use `C:\xampp\php\php.exe` and `C:\xampp\mysql\bin\mysql.exe`.

## Conventions

- Every page starts with `require_once __DIR__ . '/includes/functions.php';`, sets `$pageTitle`
  (optionally `$description`, `$structuredData`), then includes the header and footer.
- Build links and asset paths with `url()` (or `absolute_url()` for canonical/schema URLs); never hard-code
  `/YUCCA-FABRICS`.
- Escape all output with `e()`. Use prepared statements for every query with user input.
- State-changing forms include `csrf_token()` in a hidden `csrf_token` field.
- File uploads go through `save_uploaded_image()`, which validates type and size and stores a random
  filename under `uploads/<folder>/`.
- Keep markup in the existing style: Tailwind utility classes, compact single-line sections.

## Images

- Images of the business's own fabrics live in `assets/images/fabrics/`, named
  `<fabric-name>-<fabric-type>.jpeg` in lowercase kebab-case (for example `santa-boucle.jpeg`).
- The lookbook list is `includes/fabric-collection.php`. When adding a fabric photo, add an entry there with
  its name and type exactly as labelled on the card; the homepage preview and gallery lookbook update
  automatically. Use `fabric_image('file.jpeg')` to build its URL.
- Most lookbook cards are portrait collages with a label in the middle. When cropping one to a wide frame,
  show the top band (`object-top` on `<img>`, or `background-position:center top` for backgrounds).
- `PRODUCT_PLACEHOLDER_IMAGE` in `includes/functions.php` is the fallback for products without a photo.
- Do not put site imagery in `uploads/`; that folder is gitignored and reserved for runtime uploads.
- Every `<img>` needs meaningful `alt` text; add `loading="lazy"` below the fold.

## Verify before reporting done

- Lint every changed PHP file: `C:\xampp\php\php.exe -l path\to\file.php`.
- Load the changed pages in a browser at about 360, 768 and 1280 px wide: no broken images,
  no horizontal scroll, no console errors.
- Do not edit the database schema in place; add numbered `.sql` migration files and review them first.

## Safety

- Ask before destructive database commands, deleting user uploads, or changing `.htaccess` deny rules.
- M-Pesa keys and DB passwords stay in `.env`; never print them or commit them.

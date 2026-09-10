# Newspapers South Africa

A directory and lightweight publishing platform for South African community and
local newspapers. Readers can search and browse newspapers from every province,
read their latest stories, download full-edition PDFs and dig through an archive
of back issues. Newsrooms register a free publisher account to run their own
online edition, and an admin area approves listings, moderates content and holds
the Google AdSense settings.

> **Status:** in active development. See the [Roadmap](#roadmap).

## Tech

- **PHP 8.1+** and **MySQL 5.7+ / MariaDB 10.2+**
- **No server-side dependencies** — no Composer needed on the server. Runs on
  standard cPanel / shared hosting.
- Front-controller routing with clean URLs via `.htaccess`.
- Article bodies are written in a rich-text editor (loaded from a CDN on demand)
  and run through a strict server-side HTML allowlist (`App\Support\HtmlSanitizer`,
  built on ext-dom) before they are ever stored.
- Dev-only tooling (the test runner) is kept out of the deployed code.

## Local development

You need PHP 8.1+ with `pdo_mysql`, `mbstring` and `dom`, plus MySQL or MariaDB.
On Windows the simplest option is [XAMPP](https://www.apachefriends.org/).

```bash
# 1. Configuration
cp config.sample.php config.php
#    edit config.php with your local database credentials and app.url

# 2. Create the database, then apply the schema
mysql -u root -e "CREATE DATABASE newspapers_sa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php db/migrate.php

# 3. Create your admin login
php db/migrate.php create-admin admin "a-strong-password"

# 4. (Optional) load demo content
php db/seed.php

# 5. Run the site
php -S localhost:8000 router.php
```

Open <http://localhost:8000>. Before `config.php` exists or the schema is applied,
the home page shows setup instructions instead of failing.

- **Admin** area: `/admin` (dashboard, newspapers, articles, editions,
  publishers, AdSense & settings).
- **Publisher** area: `/publish` (register, verify email, sign in, then manage
  your newspaper, articles and PDF editions).

`config.php`, `/uploads/*`, `/var/*` and dev tooling are git-ignored and/or
blocked by `.htaccess`.

### Uploads

User uploads live under `/uploads` (PHP execution disabled there by its own
`.htaccess`):

| Folder            | Contents                          | Limit |
|-------------------|-----------------------------------|-------|
| `uploads/logos`   | newspaper mastheads               | 3 MB  |
| `uploads/heroes`  | article hero images               | 3 MB  |
| `uploads/media`   | inline images from the editor     | 3 MB  |
| `uploads/covers`  | edition cover images              | 3 MB  |
| `uploads/pdfs`    | full-edition PDFs                 | 25 MB |

For large PDFs, cPanel's `upload_max_filesize` / `post_max_size` may need
raising via a `.user.ini` at the document root.

### Email

Verification and password-reset emails go through `App\Support\Mailer`. Set
`mail.method` in `config.php`:

- `log` — write the message to `var/mail/` (local dev, no mail server needed).
- `mail` — use PHP's `mail()` (cPanel default). Set SPF/DKIM on the domain for
  deliverability; the mailer stays swappable for SMTP later.

### Tests

```bash
php tests/run.php
```

A dependency-free runner covering the pure logic (slugs, escaping, routing, the
HTML sanitiser, ad config, taxonomy). It runs in CI on PHP 8.1, 8.2 and 8.3.

## Deployment (cPanel)

1. Point the domain's document root at the repository root.
2. Create a MySQL database and user in cPanel and grant access.
3. Copy `config.sample.php` to `config.php`; fill in the production database
   credentials, `app.url`, `mail.*`, and set `app.env` to `production` and
   `app.debug` to `false`.
4. Apply the schema: `php db/migrate.php` over SSH, or import `db/schema.sql`
   through phpMyAdmin.
5. Create the admin login: `php db/migrate.php create-admin <user> <pass>`.
6. Confirm `mod_rewrite` is enabled.

`db/schema.sql` is safe to re-run — every statement uses `IF NOT EXISTS`.

## Project layout

```
index.php              Front controller
router.php             Router for `php -S` (dev only)
.htaccess              Clean URLs, security headers, protected paths
config.sample.php      Copy to config.php (git-ignored)

app/
  bootstrap.php        Config load, error handling, autoloader
  routes.php           Route table
  Router.php           Tiny router
  Database.php         PDO wrapper
  helpers.php          e(), slugify(), url(), view()/render(), masthead helpers…
  Models/              Newspaper, Article, Edition, Setting (+ *Admin later)
  Support/             Taxonomy, Paginator, Validator, Csrf, FormGuard,
                       RateLimiter, Auth, PublisherAuth, Upload, HtmlSanitizer,
                       Mailer, Ads
  Controllers/         + Controllers/Admin/ , Controllers/Publish/
  Views/               + Views/partials/ , Views/admin/ , Views/publish/

assets/                css / js / img
uploads/               logos, heroes, media, covers, pdfs (writable; no PHP)

db/
  schema.sql           Full database schema
  migrate.php          Apply schema; create admin
  seed.php             Load demo newspapers, articles and editions
```

## Roadmap

| PR | Scope | |
|----|-------|--|
| 1 | Scaffold: routing, database schema, admin shell, HTML sanitiser, CI. | ✅ |
| 2 | Public directory: home, browse, newspaper & article pages, editions, search. | ✅ |
| 3 | Publisher accounts: register, email verify, sign in, password reset. | |
| 4 | Publisher dashboard — articles (rich-text editor, media uploads). | |
| 5 | Publisher dashboard — editions & full-edition PDFs. | |
| 6 | Publisher dashboard — newspaper profile / masthead. | |
| 7 | Admin: newspaper approval & CRUD, article/edition moderation, publishers. | |
| 8 | Demo dataset, UI polish, deployment docs. | |

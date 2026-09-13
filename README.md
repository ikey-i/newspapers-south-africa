# Newspapers South Africa

A directory and lightweight publishing platform for South African community and
local newspapers. Readers can search and browse newspapers from every province,
read their latest stories, download full-edition PDFs and dig through an archive
of back issues. Newsrooms register a free publisher account to run their own
online edition, and an admin area approves listings, moderates content and holds
the Google AdSense settings.

> **Status:** feature-complete for a first launch — public directory, publisher
> self-service (articles + PDF editions + profile), and a full admin area with
> AdSense control. See the [Roadmap](#roadmap).

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

# 4. (Optional) load demo content — 5 fictional newspapers, 20 stories, PDFs
php db/seed.php          # add   ·   php db/seed.php --fresh   to reset it

# 5. Run the site
php -S localhost:8000 router.php
```

Open <http://localhost:8000>. Before `config.php` exists or the schema is applied,
the home page shows setup instructions instead of failing.

`php db/seed.php` prints demo publisher logins (all with the password
`demo-password-123`) so you can explore the `/publish` dashboard immediately.

- **Admin** area: `/admin` (dashboard, newspapers, articles, editions,
  publishers, AdSense & settings). Set an **admin notification email** in
  Settings to get an email whenever a newsroom verifies its address and is
  ready for review — otherwise you'll only see it as a count on the dashboard.
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
- `mail` — use PHP's `mail()` (cPanel default). Deliverability depends on the
  host's mail setup — set SPF/DKIM on the domain.
- `smtp` — deliver through a real mailbox or transactional-email provider via
  `App\Support\SmtpClient` (no Composer dependency — a small built-in client
  supporting STARTTLS/implicit TLS and AUTH LOGIN). Fill in `mail.smtp.*`:

  ```php
  'mail' => [
      'method' => 'smtp',
      'from'      => 'no-reply@yourdomain.co.za',
      'from_name' => 'Your Site Name',
      'smtp' => [
          'host'       => 'smtp.yourhost.co.za',
          'port'       => 587,     // 587 = STARTTLS, 465 = implicit TLS, 25 = none
          'encryption' => 'tls',
          'username'   => 'no-reply@yourdomain.co.za',
          'password'   => '…',
      ],
  ],
  ```

  Check it works before inviting publishers: `php scripts/test-mail.php you@example.com`.

### CAPTCHA on newspaper sign-up

Off by default. Turn it on in `/admin/settings` (reCAPTCHA section): register a
**reCAPTCHA v2 ("I'm not a robot" checkbox)** site at
[google.com/recaptcha/admin](https://www.google.com/recaptcha/admin) — add every
domain you'll use, including `localhost` while testing — then paste the site
key and secret key in and check the box. `App\Support\Recaptcha` handles the
widget and server-side verification (`api/siteverify`, fails closed if Google
is unreachable); nothing else needs to change.

### Tests

```bash
php tests/run.php
```

A dependency-free runner covering the pure logic (slugs, escaping, routing, the
HTML sanitiser, ad config, taxonomy). It runs in CI on PHP 8.1, 8.2 and 8.3.

## Deployment (shared hosting: DirectAdmin or cPanel)

No Composer, no build step — the checked-out repo *is* the deployed site. The
steps are the same shape on DirectAdmin and cPanel; panel-specific names are
noted in brackets.

1. **Create the domain/subdomain** in the panel (DirectAdmin: *Domain Setup* →
   *Subdomain Management*, or *Add Additional Domain* if it's not a subdomain
   of one already on the account; cPanel: *Domains* / *Subdomains*) and note
   its document root.
2. **Get the code onto the server**, with the document root pointed at the
   **repository root** (not a `public/` subfolder — this app has none):
   - With SSH (DirectAdmin: *SSH Keys* under *Advanced Features* to enable/add
     a key): `git clone` the repo straight into the document root, so future
     updates are `git pull`.
   - **No SSH available** (some DirectAdmin resellers disable it outright):
     download a zip of the repo from GitHub and extract it into the document
     root via *File Manager*. Everything below works the same either way —
     step 5 below replaces the two CLI commands you'd otherwise run over SSH.
3. **Create a MySQL database and user** (DirectAdmin: *MySQL Management*;
   cPanel: *MySQL® Databases*) and grant the user full privileges on it. Both
   panels prefix the names with your account username
   (`username_newspapers`, `username_dbuser`) — that's normal, just use the
   full prefixed names in `config.php`.
4. **Configure**: `cp config.sample.php config.php` (via File Manager: copy
   `config.sample.php`, rename the copy to `config.php`, then edit it in
   place), then fill in —
   - `app.url` — the site's `https://` address, no trailing slash.
   - `app.env` → `production`, `app.debug` → `false`.
   - `db.*` — the prefixed database name/user/password from step 3.
   - `mail.*` — see [Email](#email) above; a mailbox created in the same
     panel (DirectAdmin/cPanel: *Email Accounts*) works well with
     `mail.method = 'smtp'`.
   - `legal.*` — your operator name/address and POPIA Information Officer
     details (see the comments in `config.sample.php`) — `/privacy` and
     `/terms` show a banner until this is filled in.
   - **No SSH:** also set `app.setup_token` to a long random string — step 5
     needs it.
5. **Apply the schema and create your admin login**:
   - With SSH: `php db/migrate.php`, then
     `php db/migrate.php create-admin <user> <pass>`.
   - **No SSH:** visit `https://yoursite/setup.php?token=<your setup_token>`.
     It applies `db/schema.sql` (safe to reload — every statement uses
     `IF NOT EXISTS`) and shows a form to create the admin account, both over
     plain HTTP. **Delete `setup.php`** (or blank `app.setup_token` again)
     the moment that's done — it refuses to do anything once an admin account
     exists, but there's no reason to leave it reachable.
6. **Enable SSL** for the domain (DirectAdmin: *SSL Certificates* → Let's
   Encrypt, one click; cPanel: *SSL/TLS Status* → AutoSSL) and force HTTPS —
   set `app.url` to `https://` *before* this step so links are correct.
7. **Raise the upload limit** for PDF editions if the panel's default
   `post_max_size`/`upload_max_filesize` is under ~25 MB: most DirectAdmin and
   cPanel setups (PHP-FPM or suPHP) honour a `.user.ini` dropped in the
   document root:
   ```ini
   upload_max_filesize = 30M
   post_max_size = 32M
   ```
8. **Confirm `mod_rewrite`** is on (it almost always is on both panels; if
   clean URLs 404, this is the first thing to check).
9. **Set up email deliverability**: create the mailbox, then in the panel's
   *DNS Management* confirm an SPF record exists for the domain (DirectAdmin
   adds one by default) and turn on **DKIM** (DirectAdmin: one checkbox per
   domain in DNS Management). With SSH, verify with
   `php scripts/test-mail.php you@example.com` before inviting publishers; no
   SSH, just register a test newsroom once the site is live and confirm the
   verification email arrives.
10. **reCAPTCHA / AdSense**: register the *live* domain at
    [google.com/recaptcha/admin](https://www.google.com/recaptcha/admin) and
    in AdSense, then paste the keys into `/admin/settings`.
11. **Back up** the database and `/uploads` regularly — most panels have a
    built-in scheduled backup feature (DirectAdmin: *Admin Backup/Transfer*)
    worth turning on for both.

## Project layout

```
index.php              Front controller
router.php             Router for `php -S` (dev only)
setup.php              No-shell-access web installer (see Deployment)
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
                       Mailer, SmtpClient, Ads, Recaptcha, Migrator
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
| 3 | Publisher accounts: register, email verify, sign in, password reset. | ✅ |
| 4 | Publisher dashboard — articles (rich-text editor, media uploads). | ✅ |
| 5 | Publisher dashboard — editions & full-edition PDFs. | ✅ |
| 6 | Publisher dashboard — newspaper profile / masthead. | ✅ |
| 7 | Admin: newspaper approval & CRUD, article/edition moderation, publishers. | ✅ |
| 8 | Demo dataset, UI polish, deployment docs. | ✅ |

### After launch

- Curate a real directory: approve self-registered newsrooms, or add listings
  in the admin area (they don't need a publisher account to appear).
- Swap `mail.method` to `mail` and set up SPF/DKIM before inviting publishers.
- Review `app/Views/pages/privacy.php` and `terms.php` with your own wording.

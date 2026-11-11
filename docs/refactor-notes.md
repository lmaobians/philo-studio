# Refactor notes — decisions and follow-ups

This file records the non-code outcomes of the cleanup pass. Nothing here is a
schema change or a Git history rewrite; those remain pending approval.

## Performance (slow page loads / cache clear)

Root cause:

- Static assets and database queries are fast (milliseconds).
- The project is bind-mounted from `C:\xampp\htdocs\philostudio` into the Linux
  container. Every file `stat`/`access`/`open` on that mount costs ~5-10 ms,
  versus ~0.02 ms on the container's Linux volume. This is an
  environment/host-filesystem issue, not an application query or template
  problem. `WebProfilerBundle` is not enabled.
- Two things multiplied that cost on every request:
  1. The Composer PSR-4 autoloader called `access()` for every vendor class
     (`vendor/composer/../...`, ~10 ms each) because the classmap only had 33
     project classes.
  2. OPcache was re-validating timestamps, and Symfony's debug mode re-checks
     config/src/template freshness on each request (more `stat` calls).

Actions applied:

- `docker/php/performance.ini` is mounted read-only into the PHP container with:
  `opcache.validate_timestamps=0`, `opcache.revalidate_freq=0`,
  `opcache.memory_consumption=192`, `opcache.interned_strings_buffer=16`,
  `opcache.max_accelerated_files=20000`, `realpath_cache_size=16M`,
  `realpath_cache_ttl=600`.
- `var/` now lives in the named volume `philo_var`, and the container startup
  command creates `var/cache`, `var/log`, `var/sessions` and gives them to
  `www-data`. This moves cache/log/session I/O off the bind mount.
- `composer dump-autoload -o` generated a 4,622-class classmap, and
  `"optimize-autoloader": true` was added to `composer.json` so future installs
  keep it. This removed the per-class `access()` calls and was the largest
  single win. Note: this repository tracks a few `vendor/composer/*` autoload
  files, so the optimized run shows up as changes to those generated files
  (`autoload_classmap.php`, `autoload_static.php`, `installed.json`, etc.).
  Committing or untracking them is a separate repository decision.
- OPcache preloading was tried and deliberately **not** kept: preloading the dev
  container reads hundreds of files from the bind mount before PHP-FPM binds its
  socket, so the site returned 502 for ~30 seconds after every start.

Measured (steady state, `http://localhost:8081`):

| Page | Before | After |
| --- | --- | --- |
| `/` | 8.5 s | 0.38-0.51 s |
| `/packages` | 14.2 s | 0.43-0.46 s |
| `/login` | 8.2 s | 0.42-0.63 s |
| `/contact` | 6.0 s | 0.40-0.46 s |
| `/get-booked-slots` | 14.1 s | 0.26-0.52 s |

Development workflow after these changes:

- After editing PHP, run `docker restart philo_php`. The first request after a
  restart compiles the class set (~5-15 s); later requests are fast.
  `opcache.validate_timestamps=0` is what makes the restart necessary.
- Twig template edits need no restart (Twig still checks template mtimes).
- Config (YAML) changes need
  `docker exec -u www-data philo_php php bin/console cache:clear`.
- After adding new classes or changing `composer.json`, run
  `docker exec -u www-data philo_php composer dump-autoload -o` once.

Recommended (not applied):

- Running the project from a Linux filesystem (WSL2 ext4 or a Docker volume)
  would remove the remaining bind-mount cost entirely.
- A production deployment should keep `opcache.validate_timestamps=0` and use a
  deliberate restart/deploy policy.

## Legacy PHP files (issue 7)

The nginx document root is `/var/www/html/public`, so root-level legacy files
(`login.php`, `process-contact.php`, `includes/config.php`, `admin/*.php`, etc.)
are not executable through the web server. The legacy URLs that must keep
working are handled by explicit 301 redirects in
`docker/nginx/default.conf`.

Verified:

- `GET /login.php` -> 301 to `/login`
- `GET /admin/dashboard.php` -> 301 to `/admin/dashboard`
- `GET /process-contact.php` -> 404
- `GET /includes/config.php` -> 404

No Symfony code includes the legacy files.

## Admin login (issue 9)

The legacy `login.php` accepted a hardcoded fallback password in addition to
`password_verify()`. That fallback was removed, so the `admins.password` bcrypt
hash is now the only source of truth. The `admin@philostudio.com` hash was
reset once to match the operator's known password (data change only, no schema
change).

`/admin/login` reads the `username` field; the same admin account can also sign
in on `/login` through the legacy email path. The `authenticate` CSRF token is
stateless, so automated `curl` tests must send a same-origin `Origin` or
`Referer` header or the request is rejected before the password is checked.

## Walk-in customer accounts (issue 6)

Walk-ins are created by an admin and are admin-managed bookings. The real
`customers.password` column is `NOT NULL`, so a random, intentionally unusable
bcrypt password is stored for new walk-ins. Walk-ins are not intended to have
self-service login access, and registration still rejects an email that already
exists.

A future "claim my account" flow would need email verification or admin approval
so that knowing a walk-in's email address cannot be used to take over the
record. That is a product decision, not a silent auth change.

## Uploads (issue 8)

- Runtime uploads (payment proofs, admin package images) are ignored by Git.
- `public/uploads/.gitignore` keeps only `.gitignore` and `.gitkeep`.
- Four old proof images are still tracked at `uploads/proof_*.jpg` and are
  deleted in the working tree. Removing them from the Git index/history is
  pending approval because it changes repository history.
- Payment proofs are currently served from `public/uploads` when the exact URL
  is known. Moving them outside the document root and serving them through an
  authenticated controller would be a separate security improvement.

## Booking schedule validation (issue 5)

- The backend now validates date/time against the wizard's fixed hourly slots
  (10:00–19:00), rejects Mondays and past dates/times, and uses `Asia/Manila`
  for "today"/"now" comparisons because the PHP container runs in UTC.
- `templates/booking/step2.html.twig` receives `slotMinutes` from
  `BookingController::SLOT_MINUTES` instead of hardcoding `60`.
- `Package::getSlotMinutes()` remains display/metadata only; it is not used for
  availability or overlap calculations.

## SMTP configuration (issue 4)

- Real `SMTP_USER`/`SMTP_PASS` values live in the ignored `.env.local`.
- `.env` contains empty placeholders; `.env.example` documents the keys.
- `process-contact.php` loads `.env`/`.env.local` when used standalone and reads
  the SMTP settings from the environment.
- Rotating the previously committed SMTP credentials and purging them from Git
  history still requires approval.
- `.env.dev` (untracked, contains an `APP_SECRET`) is now ignored by Git. The
  application itself reads `.env` and `.env.local`; do not delete `.env.dev`
  until you confirm nothing local depends on it.

## Sharing via ngrok

The app is published on host port 8081, so the tunnel only needs to point at it:

```bash
ngrok http 8081
```

ngrok terminates HTTPS and forwards plain HTTP to nginx. Two pieces make
Symfony behave correctly behind it:

- `docker/nginx/default.conf` forwards `X-Forwarded-For/Proto/Host/Port` to
  PHP-FPM (nginx does not forward arbitrary headers automatically).
- The ignored `.env.local` sets `SYMFONY_TRUSTED_PROXIES=REMOTE_ADDR` and
  `SYMFONY_TRUSTED_HEADERS=x-forwarded-for,x-forwarded-proto,x-forwarded-host,x-forwarded-port`.
  Symfony then sees the request as HTTPS and the stateless CSRF `Origin` check
  matches the ngrok host.

Because Symfony sees HTTPS, the session cookie is `Secure`: it is sent back
over the ngrok HTTPS URL but not over `http://localhost:8081`. The free ngrok
plan shows an interstitial page once and changes the public URL on each
restart; API clients can send `ngrok-skip-browser-warning: true`.

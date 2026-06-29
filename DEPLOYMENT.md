# Deploying the Admin Panel to CyberPanel (OpenLiteSpeed + PHP 8.2 + MySQL)

This is a **Laravel 9** application. Its web root must point at the `public/`
sub-folder, **not** at `public_html` itself. Two methods for that are documented
below — pick one (the **vhost docRoot method is preferred** on OpenLiteSpeed).

> **Before you start:** read `SECURITY — secrets to rotate` at the bottom. Some
> credentials in this repo were committed to git history and must be rotated
> before this goes live.

---

## 0. Prerequisites checklist

- A domain (or sub-domain) pointed at your VPS IP via an A record.
- CyberPanel installed and reachable at `https://<server-ip>:8090`.
- SSH access to the VPS (root or a sudo user).
- Composer 2.x installed on the server (CyberPanel images usually include it; if
  not: `dnf install composer` / `apt install composer`, or install globally from
  getcomposer.org).
- `npm run build` already run **locally** so `public/build/` exists (see step 9).

---

## 1. Create the Website in CyberPanel

1. Log in to CyberPanel → **Websites → Create Website**.
2. Fill in:
   - **Select Package:** `Default` (or a package with enough disk/bandwidth).
   - **Select Owner:** `admin`.
   - **Domain Name:** `your-domain-here.com`.
   - **Email:** your admin email (used for the Let's Encrypt cert).
   - **Select PHP:** **PHP 8.2**.
   - **Additional Features:** tick **SSL**, **DKIM**, **Open_basedir Protection**
     (you can leave open_basedir off initially if Composer complains).
3. Click **Create Website**.

This creates: `/home/your-domain-here.com/public_html/` (the default docRoot).

---

## 2. Enable required PHP extensions

CyberPanel → **Server → PHP → Edit PHP Configs → (select PHP 8.2) → PHP
Extensions**. Make sure these are enabled (most ship enabled, but verify):

| Extension | Why |
|-----------|-----|
| `pdo_mysql` / `mysqlnd` | Database |
| `mbstring` | Laravel core |
| `openssl` | APP_KEY, HTTPS, JWT/receipt validation |
| `tokenizer` | Laravel core |
| `xml` / `dom` | Laravel core, Swagger |
| `ctype`, `json` | Laravel core |
| `bcmath` | Razorpay / numeric ops |
| `curl` | Guzzle, payment gateways, App Store API |
| `fileinfo` | Intervention/Image uploads |
| `gd` **or** `imagick` | Intervention/Image, BaconQrCode |
| `zip` | Composer, package installs |
| `intl` | jenssegers/date, localization |
| `exif` | Image handling (optional but recommended) |

After enabling, restart LiteSpeed: **Server → Restart LiteSpeed** (or
`systemctl restart lsws`).

---

## 3. Create the MySQL database

CyberPanel → **Databases → Create Database**.

- **Select Website:** `your-domain-here.com`
- **Database Name:** e.g. `wgbackend` (CyberPanel prefixes it, e.g. `yourdom_wgbackend`)
- **Username:** e.g. `wguser` (also prefixed)
- **Password:** generate a strong one

**Record the final prefixed names** — you'll put them in `.env`:
```
DB_DATABASE=yourdom_wgbackend
DB_USERNAME=yourdom_wguser
DB_PASSWORD=the-strong-password
```

---

## 4. Upload the files (SFTP / SSH)

**Upload location:** `/home/your-domain-here.com/public_html/`

Delete the default placeholder `index.html` CyberPanel drops in there first.

### Option A — SFTP (FileZilla / WinSCP)
Connect as the website user (create an FTP account in CyberPanel → **FTP →
Create FTP Account**, or use SSH credentials). Upload the **contents** of the
`Admin Panel` folder into `public_html/`.

**Do NOT upload** (see `.deployignore`):
- `node_modules/` (frontend was built locally)
- `vendor/` (installed on server via Composer in step 5)
- `.git/`
- `.env` (created directly on server in step 6)
- `tests/`, `.vscode/`, `*.p8` keys (upload keys separately, step 8)

### Option B — rsync over SSH (faster, honors excludes)
From your local machine:
```bash
rsync -avz --exclude-from='.deployignore' \
  "./Admin Panel/" \
  user@your-server-ip:/home/your-domain-here.com/public_html/
```

After upload, `public_html/` should contain `app/`, `bootstrap/`, `config/`,
`database/`, `public/`, `resources/`, `routes/`, `storage/`, `artisan`,
`composer.json`, `composer.lock`, etc. (full tree in section 14).

---

## 5. Install Composer dependencies via SSH

```bash
cd /home/your-domain-here.com/public_html
composer install --no-dev --optimize-autoloader
```

- `--no-dev` skips dev tooling (PHPUnit, Faker, Sail) — not needed in production.
- `--optimize-autoloader` builds a class map for speed.

If Composer reports a memory limit error:
```bash
php -d memory_limit=-1 $(which composer) install --no-dev --optimize-autoloader
```

---

## 6. Create and configure `.env` for production

Copy the example and edit:
```bash
cd /home/your-domain-here.com/public_html
cp .env.example .env
nano .env
```

Set at minimum:
```ini
APP_NAME="Solion VPN"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain-here.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yourdom_wgbackend
DB_USERNAME=yourdom_wguser
DB_PASSWORD=the-strong-password

APP_FAST_API_KEY=<new random 64-char string>
MAIL_USERNAME=<your smtp user>
MAIL_PASSWORD=<your smtp password>
MAIL_FROM_ADDRESS=<your from address>
```

> `APP_ENV=production` + `APP_DEBUG=false` are **mandatory** for a live site —
> debug=true leaks stack traces, env vars and secrets to visitors.

---

## 7. App key, migrations, storage link

```bash
cd /home/your-domain-here.com/public_html

php artisan key:generate          # writes a fresh APP_KEY into .env
php artisan migrate --seed        # creates tables + seeds default data (3 seeders)
php artisan storage:link          # symlinks public/storage -> storage/app/public
```

Then cache config/routes for production performance:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
> Re-run `php artisan config:clear` if you later edit `.env`, because
> `config:cache` freezes the current values.

---

## 8. Upload the Apple `.p8` key (separately, not in the repo)

`AuthKey_*.p8` has been removed from git tracking and added to `.gitignore`.
Upload your (rotated) key manually to a location **outside the web root** if the
package allows configuring an absolute path, otherwise into `storage/`:

```bash
# preferred: outside public_html so it can never be served over HTTP
mkdir -p /home/your-domain-here.com/secrets
# scp AuthKey_XXXX.p8 -> /home/your-domain-here.com/secrets/
chmod 600 /home/your-domain-here.com/secrets/AuthKey_XXXX.p8
```
Then point `APPSTORE_PRIVATE_KEY` in `.env` at the filename the package expects
(many Laravel IAP packages look in `storage/` — check `config/liap.php` /
`config/services.php` for the exact path it loads).

---

## 9. Build frontend assets LOCALLY (so node_modules isn't needed on server)

On your **local** machine (Node already installed), from the `Admin Panel`
folder:
```bash
npm install
npm run build      # runs `vite build` -> outputs to public/build/
```
Upload the generated **`public/build/`** folder to the server. The server then
never needs Node or `node_modules`.

> Do this BEFORE the rsync/SFTP upload in step 4, or upload `public/build/`
> afterward as a delta.

---

## 10. Point the web root at `/public` (REQUIRED for Laravel)

CyberPanel's default docRoot is `public_html/`, but Laravel must be served from
`public_html/public/`. Use **one** of these:

### Method A — OpenLiteSpeed vhost docRoot (PREFERRED)

1. CyberPanel → **Websites → List Websites → (your domain) → Manage →
   vHost Conf** (this edits the OLS virtual host config).
2. Find the `docRoot` line and change it to the `public` sub-folder:
   ```
   docRoot                   $VH_ROOT/public_html/public
   ```
   (`$VH_ROOT` resolves to `/home/your-domain-here.com`.)
3. Ensure the rewrite/index block looks like:
   ```
   index  {
     useServer               0
     indexFiles              index.php
   }

   rewrite  {
     enable                  1
     autoLoadHtaccess        1
   }
   ```
4. **Save**, then CyberPanel → **Server → Restart LiteSpeed**.

This is cleaner than the `.htaccess` redirect and avoids exposing files above
`public/`.

### Method B — `.htaccess` redirect in `public_html` (fallback)

If you cannot edit the vhost, keep docRoot at `public_html/` and create
`public_html/.htaccess` that forwards everything into `public/`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

Laravel's own `public/.htaccess` (shipped in the repo) then handles the
front-controller rewrites. OpenLiteSpeed honors `.htaccess` because
`autoLoadHtaccess` is enabled by default.

> **Method B caveat:** files above `public/` (like `.env`, `composer.json`,
> `storage/`) sit inside the docRoot. Make sure they're never directly
> reachable. Method A avoids this entirely — prefer it.

---

## 11. File permissions

Laravel must be able to write to `storage/` and `bootstrap/cache/`:

```bash
cd /home/your-domain-here.com/public_html

# ownership: files served by the lsadm/nobody user but owned by the site user
# (CyberPanel runs PHP as the website user by default)
chown -R your-domain-here.com:your-domain-here.com .

# directories 755, files 644
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# writable runtime dirs
chmod -R 775 storage bootstrap/cache

# keep artisan executable
chmod 755 artisan
```

If you see "Permission denied" on logs/cache after this, confirm the PHP process
user (CyberPanel → vHost) matches the owner, or use `775` + group `nobody`.

---

## 12. Issue SSL via CyberPanel

CyberPanel → **SSL → Manage SSL** (or **Issue SSL**):
- **Select Website:** `your-domain-here.com`
- Click **Issue SSL** — CyberPanel runs Let's Encrypt/ACME and installs the cert.

Then force HTTPS. Easiest: in the vHost Conf `rewrite` block add:
```
rewrite  {
  enable                1
  autoLoadHtaccess      1
  RewriteRule ^(.*)$ https://%{SERVER_NAME}/$1 [R=301,L]
}
```
Restart LiteSpeed. Confirm `APP_URL` in `.env` uses `https://` (already set in
step 6) so generated links and asset URLs are correct.

---

## 13. Queue worker (background jobs) — optional but recommended

`QUEUE_CONNECTION=database` means jobs (emails, receipt validation) need a
worker. The repo ships `laravel-worker.conf` (Supervisor) and `remote.sh`:

```bash
sudo apt install -y supervisor          # or dnf install supervisor
sudo cp laravel-worker.conf /etc/supervisor/conf.d/
# edit the conf: set the correct `command` path & `user` to your site
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

---

## 14. Final folder structure inside `public_html`

After upload + `composer install` + `storage:link` + local `npm run build`:

```
/home/your-domain-here.com/public_html/
├── app/                  # application code (Controllers, Models, Services…)
├── bootstrap/
│   └── cache/            # 775, writable
├── config/
├── database/             # migrations, seeders, factories
├── lang/                 # localization (also resources/lang in some setups)
├── public/               # >>> WEB ROOT points here <<<
│   ├── index.php         # front controller
│   ├── .htaccess         # Laravel rewrites (shipped)
│   ├── build/            # compiled Vite assets (from local npm run build)
│   └── storage -> ../storage/app/public   # symlink from storage:link
├── resources/            # blade views, raw css/js/scss
├── routes/               # web.php, api.php, backend.php, channels.php, console.php
├── storage/              # 775, writable (logs, cache, sessions, uploads)
│   ├── app/
│   ├── framework/
│   └── logs/
├── utils/                # Utils\ namespace helpers
├── vendor/               # Composer deps (installed ON the server)
├── artisan
├── composer.json
├── composer.lock
├── server.php
└── .env                  # created on server, NEVER committed

# NOT present on server: node_modules/, .git/, tests/, *.p8 (uploaded separately)
```

- With **Method A**, the OLS docRoot = `…/public_html/public`.
- With **Method B**, docRoot stays `…/public_html` and the root `.htaccess`
  forwards into `public/`.

---

## 15. Post-deploy smoke test

```bash
php artisan about            # shows env, cache, db status
php artisan migrate:status   # confirms migrations ran
curl -I https://your-domain-here.com   # expect 200/302, valid TLS
```
Visit `https://your-domain-here.com` and the admin login. If you get a 500 with
no detail, temporarily set `APP_DEBUG=true`, reproduce, then **set it back to
false** — never leave debug on in production.

---

## SECURITY — secrets to rotate BEFORE going live

`.env` was committed in early git history (commit `8fd2ae68 "first commit"`, and
`AuthKey_*.p8` is in history too). **Anything that was ever in those files is
compromised and must be rotated.** `.env.example` in the repo has been
sanitized, but the *real* values still exist in history.

Rotate / regenerate ALL of the following:

| Secret | Where | Action |
|--------|-------|--------|
| `APP_KEY` | `.env` | Regenerate with `php artisan key:generate` (done in step 7). |
| `APP_FAST_API_KEY` | `.env` + VPN client app config | Generate new random string; update the Flutter app's matching value. |
| **SMTP password** (`MAIL_PASSWORD`) | mail provider (privateemail.com) | Change the mailbox password; update `.env`. |
| **Apple App Store** `.p8` private key + `APPSTORE_PRIVATE_KEY_ID` + `APPSTORE_PASSWORD` | App Store Connect → Users & Access → Integrations → Keys | **Revoke** the leaked key, issue a new one, re-upload (step 8). |
| **Apple shared secret** (`APPSTORE_PASSWORD`) | App Store Connect → your app → App-Specific Shared Secret | Regenerate. |
| **Google** service-account JSON (`GOOGLE_APPLICATION_CREDENTIALS`) | Google Cloud Console → IAM → Service Accounts → Keys | Delete old key, create new JSON, upload outside webroot. |
| **DB credentials** | CyberPanel | Use brand-new credentials created in step 3 (never reuse the old `homestead/secret`). |
| Payment gateways — **Stripe, PayPal, Razorpay, Mollie** | each provider dashboard | These are entered via the **admin panel UI → settings (DB)**, not in `.env`. If the production DB is a copy of an old one, roll the API keys/secrets in each provider dashboard and re-enter them. Switch all to **live** (not test) keys. |
| **Facebook / Socialite** `FACEBOOK_CLIENT_SECRET` | Meta for Developers | Rotate if it was ever set in the leaked `.env`. |
| AWS / S3-compatible keys (`AWS_*`, `B2_*`, `STORJ_*`, etc.) | respective provider | Rotate any that were populated. |

**Also strongly recommended:** purge the secrets from git history before
pushing anywhere public (e.g. `git filter-repo --invert-paths --path .env
--path AuthKey_U4946UHL2X.p8`), then force-push. Removing the file now only stops
*future* commits — old commits still contain the keys.

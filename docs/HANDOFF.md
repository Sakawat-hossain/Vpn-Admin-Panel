# OvpnPlus: project handoff

Read this first in a new session. Last updated 2026-11-07 (second session).

**User:** writes Banglish (romanized Bengali). Reply in Banglish, keep tech terms in
English. Business: a VPN service whose users are mostly in **China**. The old app
"Solion VPN" (Flutter, Play Store) uses this same panel. The new apps are called
**OvpnPlus**.

**Rules:**
- Never ask for passwords, keys or tokens in chat.
- Do not commit secrets.
- Work on branch `claude/new-session-3l5u10` in both repos; it is not merged to `main` yet.

## Repositories (GitHub, owner `Sakawat-hossain`)

| Repo | What | Branch |
|---|---|---|
| `Vpn-Admin-Panel` | Laravel 9 admin + user panel, client API, OpenVPN deploy | `claude/new-session-3l5u10` |
| `desktop-app` | OvpnPlus desktop app: IVPN desktop v3.16.1 fork (Go daemon + Electron/Vue UI + CLI). `main` = untouched IVPN code | `claude/new-session-3l5u10` |
| `android-app`, `ios-app` | empty; planned from IVPN android/ios (Kotlin/Swift) | — |

## Servers

| Host | Role | Notes |
|---|---|---|
| 141.11.76.19 | **Panel** (Debian, nginx, php-fpm) | code in `/opt/vpn-panel` (git checkout of the branch); `APP_URL=http://141.11.76.19` (no HTTPS yet); queue worker + cron must run |
| 163.227.238.134 | **VPN server** (panel server #4, "Panel-managed OpenVPN", Brahmanbaria) | `openvpn-server@server`, udp/1194, NAT `10.89.0.0/16`. Old "vpnpanel" and "Nexora" panels were removed. Server #3 is a duplicate "OpenVPN profile" entry for the same IP; delete it |
| 43.135.12.23 | **new OvpnPlus panel: https://app.ovpnplus.com** (Tencent Cloud, Hong Kong, Debian 13, 2 CPU / 2 GB) | installed 2026-11-07, see "New panel" below. Private IP 10.5.0.16 behind Tencent NAT: open ports in the Tencent security group. Claude's key: `~/.ssh/ovpnplus_app_ed25519` (root). Root password login is off (`prohibit-password`); bots were brute-forcing it |

Server check on 2026-11-07 (second session):
- 141.11.76.19 did not answer on ports 22 or 80, from the user's PC or from 163.227.238.134. Check it in the provider console.
- 163.227.238.134 now runs the user's **OVPN+** product (`ovpnplus-web`, `openvpn-server@server-tcp` on tcp/443 and `@server-udp` on udp/1398, tls-crypt-v2). There is no `openvpn-server@server` and no `/etc/openvpn/server/server.conf` from this panel. Do not redeploy server #4 there until the user decides which product that box is for.
- `/root/.ssh/authorized_keys` there holds three keys: `nexora-bd-134`, `ovpnplus-vps-access`, `ovpnplus-test-vps`.

Local checkout used by Claude sessions: `D:\My GitHub\OvpnPlus\Admin Panel` (panel) and `D:\My GitHub\OvpnPlus\desktop-app`.

The user's Windows PC: repo at `C:\Users\sakaw\desktop-app`. Go, Node 24, NSIS, CMake,
VS 2022 Build Tools and MSYS2 gcc are installed. The user often types in **CMD** while
the instructions are PowerShell: say which shell each command is for.

## New panel on 43.135.12.23 (2026-11-07)

- URL `https://app.ovpnplus.com`, admin at `/admin`. DNS is a Cloudflare record **not proxied** (points straight at the server). Let's Encrypt cert via certbot (`certbot.timer` renews).
- Code: `/opt/vpn-panel`, git checkout of `claude/new-session-3l5u10`. Update: `git pull && composer install --no-dev -o && php artisan migrate --force && sudo -u www-data php artisan config:cache && systemctl restart ovpnplus-queue`.
- PHP **8.2** from packages.sury.org: `composer.lock` pins packages that refuse PHP 8.4 (Debian 13's own). 56/56 tests pass on the server.
- nginx site `ovpnplus-panel`, php8.2-fpm, MariaDB db/user `ovpnplus`, queue worker = systemd `ovpnplus-queue` (www-data), scheduler in `/etc/cron.d/ovpnplus-panel`.
- Fresh database (no data from 141.11.76.19). Vendor admins and the demo server 38.180.79.202 were removed. Admin login is in `/root/ovpnplus-panel-admin.txt` (root only).
- `APP_FAST_API_KEY` is **new** (only in `/opt/vpn-panel/.env`). The build script asks for it: `grep APP_FAST_API_KEY /opt/vpn-panel/.env`.
- New OpenVPN CA/tls-crypt key in `/opt/vpn-panel/storage/app/openvpn/`; back it up off the server.
- `MAIL_MAILER=log`: no e-mail is sent yet (verification codes, resets). Set SMTP in `.env` or admin settings.
- Desktop build now: `.\build-ovpnplus.ps1 -PanelHost app.ovpnplus.com -PanelScheme https`.
- To put the VPN on this same box through the panel's deploy job, the job needs root password SSH, which is now off; allow it only from the server itself or use another VPS.

## WARNING: 163.227.238.134 shares OpenVPN files (2026-11-07)

The user added .134 to the new panel; the deploy succeeded (`openvpn-server@server`, udp/1194, tls-crypt). The deploy job of that time wrote `ca.crt`, `server.crt` and `server.key` into `/etc/openvpn/server/`, replacing the files OVPN+ uses (`server-tcp.conf`/`server-udp.conf`). OVPN+ keeps working only because its processes (running since 2026-11-02) hold the old keys in memory. **Do not restart OVPN+ or reboot .134** until this is fixed.

- Fixed in the job (commit 217b1a9): it now writes `panel-ca.crt`, `panel-server.crt`, `panel-server.key`, `panel-ta.key`.
- Not done yet, the user said "not now": on .134 copy the panel's current files to the `panel-*` names, point `server.conf` at them, put OVPN+'s originals back from `/etc/openvpn/pki/` (`ca.crt`, `issued/server.crt`, `private/server.key`; byte-identical to `/root/ovpnplus-backup-20261102-094715.tgz`), and restart only `openvpn-server@server`. A redeploy from the panel would also write the `panel-*` files, but would not restore OVPN+'s files.
- Snapshot taken mid-deploy: `/root/ovpnplus-openvpn-backup-*` on .134.

## How it works

1. **Login:** the app sends `POST /api/client/v4/session/new` (email + password; IVPN-compatible JSON). The panel returns a session token plus a per-device VPN username/password (`vpn_sessions` table). The device limit comes from settings `active_device_limit`.
2. **Server list:** `GET /api/client/v5/servers.json` needs header `x-api-key` = `APP_FAST_API_KEY`. It returns only *panel-managed* OpenVPN servers, plus `config.openvpn` (CA, static key, `tls_mode`) and `config.api.ips` (panel IPs, from `CLIENT_API_IPS` or resolved from `APP_URL`).
3. **VPN login check:** each VPN server's `auth-user-pass-verify` script calls `POST /api/client/openvpn/auth` (Bearer server secret + `X-Server-Id`); 204 means allowed. Free users can only use non-premium servers.
4. **Deploy:** Admin → Servers → Panel-managed OpenVPN + root password. Job `app/Jobs/ConfigOpenVpnServer.php` uses sshpass/ssh. It fails clearly if the port is busy.
5. **PKI:** the shared CA and static key live in `storage/app/openvpn/` **on the panel server only, not in git**. Back them up; losing them means re-deploying every server.
6. **tls-crypt is the default** (`OPENVPN_TLS`). With tls-auth, the GFW let the tunnel connect and then dropped all packets after about 25 s.

Main panel files:
- `app/Http/Controllers/Api/Client/*`
- `app/Services/OpenVpnPki.php`, `OpenVpnProfile.php`
- `app/Jobs/ConfigOpenVpnServer.php`
- `config/services.php` (`openvpn.*`)
- Tabler UI in `resources/views/backend|frontend/user`

Run the tests: `APP_KEY=base64:... vendor/bin/phpunit` (56 tests, sqlite).

Main desktop app files:
- `daemon/api/api_panel.go`: panel host, scheme, prefix and app key, set at build time with `-ldflags -X`
- `daemon/vpn/openvpn/config.go`: inline CA and tls-crypt/tls-auth
- `ui/src/config.js`: `VITE_PANEL_URL`
- Branding: `ui/References/branding/` (source PNGs and `make_icons.py`)
- Docs: `docs/PANEL.md`, `docs/BUILD-WINDOWS.md`

## Build the Windows installer (user's PC, PowerShell)

```powershell
cd C:\Users\sakaw\desktop-app; git pull
cd ui\References\Windows
Set-ExecutionPolicy -Scope Process Bypass
$env:ELECTRON_MIRROR = "https://npmmirror.com/mirrors/electron/"
$env:ELECTRON_BUILDER_BINARIES_MIRROR = "https://npmmirror.com/mirrors/electron-builder-binaries/"
.\build-ovpnplus.ps1 -PanelHost 141.11.76.19 -PanelScheme http
```

- The script asks for `APP_FAST_API_KEY`, with hidden input.
- Output: `bin\OvpnPlus-v3.16.1.exe`.
- The Electron mirror is needed from China.
- If `git pull` complains about local changes to the .ps1 (line endings), run `git checkout -- <file>`.
- Logs: `C:\Program Files\OvpnPlus\log\IVPN Agent.log` (enable logging in the app settings).

## Current status / next steps

1. **Not yet tested: tls-crypt.**
   - Panel: `git pull` in `/opt/vpn-panel`, `php artisan config:clear && php artisan queue:restart`.
   - Redeploy server #4, then `grep tls /etc/openvpn/server/server.conf` should show `tls-crypt`.
   - Rebuild and reinstall the app; uninstall the old "LepidVPN" build first.
   - Connect and keep the tunnel up for 2 minutes or more.
2. If China still drops the tunnel: try TCP 443 (`OPENVPN_PROTO=tcp`, `OPENVPN_PORT=443`) or obfs4. The IVPN daemon already supports obfsproxy; the server side is not built yet.
3. UI design: **done in source** (2026-11-07). Gold `#FFB000` accent from the logo on a black/white base, light and dark mode. WireGuard, Multi-Hop and AntiTracker are hidden and forced off through `config.Features` in `ui/src/config.js`. Not yet seen in a real installed build.
4. Panel on a domain with HTTPS. Then rebuild the app with `-PanelHost domain` and redeploy the servers (the auth URL changes). aaPanel/CyberPanel are fine; they need `proc_open` enabled, sshpass, a Supervisor queue worker and cron.
5. Not done yet:
   - code signing
   - macOS/Linux rebrand (the macOS bundle still says IVPN)
   - Android/iOS apps
   - GitHub Actions installer workflow (manual run; needs repo vars `PANEL_HOST`, `PANEL_URL`, `PANEL_SCHEME` and secret `PANEL_APP_KEY`)
   - PR/merge to `main`
6. Security follow-ups:
   - `APP_FAST_API_KEY` was pasted in chat. Rotate it later, together with an app update (the old Solion app uses it too).
   - Review `/root/.ssh/authorized_keys` on 163.227.238.134 (it changed while unknown panels were installed).

# VPN Admin Panel

Laravel 9 admin panel and mobile-app API for a VPN service. It manages users,
plans and subscriptions (web checkout + App Store / Google Play in-app
purchases) and two kinds of VPN servers:

- **WireGuard** servers running [wg-easy](https://github.com/ombapit/wg-easy)
  (the `ombapit` fork). The panel installs wg-easy over SSH and creates one peer
  per user (`wg{userId}`) when the app connects.
- **OpenVPN** servers, for which the admin pastes a client profile (`.ovpn`)
  that is handed to entitled users.

See [DEPLOYMENT.md](DEPLOYMENT.md) for installation, and section 16 there when
upgrading an existing install.

## How the VPN side works

| | WireGuard (wg-easy) | OpenVPN |
|---|---|---|
| Server setup | Admin → Servers → *Install Wg Easy* (or *Redeploy* on Edit). Runs `App\Jobs\ConfigServer` on the queue. | Done by you; paste the client profile into the server form. It must contain `client`, a `remote` and the CA inline. |
| Connect (`GET /api/v1/server/connect/{id}`) | Re-creates the user's peer (fresh keys, so only the latest device works) and returns its config. | Returns the stored profile. |
| Premium servers | Only users with an active paid plan can connect. | Same. |
| Revocation | Peers are removed when the user is deleted/banned, loses premium, or moves to another server; `wg:prune` cleans up daily. | Not possible per user: everyone shares one profile. Use `auth-user-pass` on the OpenVPN server if you need per-user revocation. |

wg-easy API access is protected by a per-server password (sent in the
`Authorization` header) and a firewall rule that only lets the panel's IP reach
port 51821. Each wg-easy server holds at most 253 peers (one /24).

## Development

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve
php artisan queue:work          # server installs, peer revocation
php artisan schedule:work       # subscription expiry, wg:prune
vendor/bin/phpunit              # uses in-memory SQLite
```

Useful commands:

- `php artisan wg:prune --dry-run` – list WireGuard peers that should be removed.
- `php artisan subscriptions:update-expired` – move expired subscriptions to the free plan.

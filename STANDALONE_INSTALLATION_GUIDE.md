# PHPNetMap — Standalone Installation Guide (no Docker)

This guide walks through installing PHPNetMap directly on a server —
Apache + PHP + SQLite — without Docker. It reproduces exactly what the
project's own [`Dockerfile`](Dockerfile) does, so a server that passes
[`requirements-check.php`](requirements-check.php) matches what actually
ships in the Docker image.

If Docker is available on your server, prefer it — see the main
[README.md](README.md). Use this guide when Docker isn't an option (locked-down
host, existing bare-metal LAMP server, etc.).

Tested target: **Debian/Ubuntu with PHP 7.4** (the exact version the Docker
image and CI use). Other Linux distributions and PHP 7.x versions will
likely work but aren't verified here.

## Contents

- [1. Prerequisites](#1-prerequisites)
- [2. Install packages](#2-install-packages)
- [3. Get the code](#3-get-the-code)
- [4. Set up writable directories](#4-set-up-writable-directories)
- [5. Configure Apache](#5-configure-apache)
- [6. Create the HTTP login (.htpasswd)](#6-create-the-http-login-htpasswd)
- [7. Tune php.ini](#7-tune-phpini)
- [8. Verify everything with requirements-check.php](#8-verify-everything-with-requirements-checkphp)
- [9. First run](#9-first-run)
- [10. Updating](#10-updating)
- [11. Troubleshooting](#11-troubleshooting)

## 1. Prerequisites

- A Linux server (Debian 11 "bullseye" or Ubuntu 20.04/22.04 recommended —
  see the PHP version note below) with root/sudo access.
- Network line of sight from this server to the switches/routers you want
  to monitor via SNMP (UDP/161).
- A domain name or IP address to reach the web UI.

**About the PHP version.** This app targets **PHP 7.4**. Debian 11 and
Ubuntu 20.04 ship PHP 7.4 in their default repositories. Newer releases
(Debian 12+, Ubuntu 22.04+) default to PHP 8.x, which is **untested** with
this app (Yii 1.1, a framework from the PHP 5/7 era). On a newer OS, either:

- provision an older base image (Debian 11 container/VM), or
- add the [Ondřej Surý PPA](https://launchpad.net/~ondrej/+archive/ubuntu/php)
  (Ubuntu) or [Sury's Debian repo](https://packages.sury.org/php/) to install
  PHP 7.4 alongside/instead of the distro default:

  ```bash
  # Ubuntu
  sudo add-apt-repository ppa:ondrej/php
  sudo apt-get update

  # Debian
  sudo apt-get install -y apt-transport-https lsb-release ca-certificates curl
  curl -sSLo /usr/share/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg
  echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
    | sudo tee /etc/apt/sources.list.d/php.list
  sudo apt-get update
  ```

## 2. Install packages

```bash
sudo apt-get update
sudo apt-get install -y \
    apache2 \
    php7.4 libapache2-mod-php7.4 \
    php7.4-sqlite3 \
    php7.4-snmp \
    php7.4-apcu \
    php7.4-json \
    snmpd \
    sqlite3 \
    apache2-utils \
    git
```

What each piece is for (mirrors [`requirements-check.php`](requirements-check.php)'s checks):

| Package | Why |
|---|---|
| `apache2` | web server |
| `php7.4`, `libapache2-mod-php7.4` | runs the app under Apache |
| `php7.4-sqlite3` (provides `pdo_sqlite`) | the app's database — a single SQLite file, no separate DB server needed |
| `php7.4-snmp` | queries network devices via SNMP v1/v2c/v3 — the app's core feature |
| `php7.4-apcu` | caches SNMP results (CAM/ARP tables, GET responses); enabled by default in the app's Configuration page |
| `php7.4-json` | usually built in; listed for completeness (AJAX endpoints) |
| `snmpd` | an SNMP daemon *on this server itself* — the app doesn't require this to query *other* devices, but the original reference image ran it, so it's included for parity. Safe to skip if you don't need this host itself pollable. |
| `apache2-utils` | provides the `htpasswd` command (step 6) |
| `git` | to fetch the code (step 3) |

## 3. Get the code

```bash
sudo git clone https://github.com/marcelofmatos/phpnetmap.git /var/www/phpnetmap
cd /var/www/phpnetmap
```

(Or copy a release tarball/checkout there by any other means — anywhere
Apache can serve from works, `/var/www/phpnetmap` is just this guide's
convention.)

## 4. Set up writable directories

The app needs to write to three directories — the SQLite database and
persisted Configuration-page settings, Yii's own runtime cache, and Yii's
published front-end assets:

```bash
cd /var/www/phpnetmap
mkdir -p protected/data protected/runtime assets
sudo chown -R www-data:www-data protected/data protected/runtime assets
```

(`www-data` is Apache's default user on Debian/Ubuntu — adjust if yours differs.)

The SQLite database file (`protected/data/phpnetmap.db`) is created
automatically on first request as long as `protected/data` itself is
writable — you don't need to create it by hand.

## 5. Configure Apache

The app relies entirely on a `.htaccess` file at the project root for URL
rewriting (`mod_rewrite`) *and* the HTTP login (`mod_authn_file` +
`mod_auth_basic`) — so both must be enabled, and the vhost must allow
`.htaccess` to override the config (`AllowOverride All`).

```bash
sudo a2enmod rewrite authn_file auth_basic authz_user
```

Create a vhost, e.g. `/etc/apache2/sites-available/phpnetmap.conf`:

```apache
<VirtualHost *:80>
    ServerName phpnetmap.example.com
    DocumentRoot /var/www/phpnetmap

    <Directory /var/www/phpnetmap>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Enable it and reload Apache:

```bash
sudo a2ensite phpnetmap
sudo systemctl reload apache2
```

## 6. Create the HTTP login (.htpasswd)

Every page sits behind HTTP Basic Auth (see `.htaccess` at the project
root) — without a valid `.htpasswd`, nothing loads.

```bash
cd /var/www/phpnetmap
sudo htpasswd -c .htpasswd admin
# prompts for a password
sudo chown www-data:www-data .htpasswd
```

(`-c` creates a new file — omit it if you're adding a second user to an
existing one.) This is the same mechanism the Docker image's
`set_htpasswd.sh` automates via the `ADMIN_USER`/`ADMIN_PASSWORD`
environment variables — there's no env-var equivalent outside Docker, so
`htpasswd` is how you set it directly.

## 7. Tune php.ini

The app never calls `ini_set()` for any of this (see `index.php`), so it's
entirely at the mercy of your server's `php.ini`
(`/etc/php/7.4/apache2/php.ini` on Debian/Ubuntu). None of these will stop
the app from *loading*, but each has a concrete, observable failure mode:

| Setting | Recommended | Why |
|---|---|---|
| `display_errors` | `Off` | With it `On`, a stray PHP warning/notice gets printed straight into an AJAX response (port info/status/traffic, etc.) and breaks it client-side — the JSON becomes invalid. Pair with `log_errors = On` so you still see errors, just in the log instead of the page. |
| `upload_max_filesize` | `8M` or more | The Host Face editor's "Upload image" lets you attach an equipment photo; the default `2M` is tight for a decent-resolution photo. |
| `post_max_size` | `>= upload_max_filesize` | If smaller, uploads fail silently — the file just never arrives. |
| `memory_limit` | `128M` or more | A full SNMP walk (CAM/ARP table) on a switch with thousands of entries can use more than PHP's older 32–64M defaults. |
| `date.timezone` | your local zone (e.g. `America/Sao_Paulo`) | If unset, PHP silently falls back to UTC — functionally fine, but every timestamp in the UI will read as UTC regardless of where the server actually is. |

Edit the file, then reload Apache:

```bash
sudo nano /etc/php/7.4/apache2/php.ini
sudo systemctl reload apache2
```

## 8. Verify everything with requirements-check.php

```bash
php requirements-check.php
```

This checks PHP version, the extensions above, the php.ini settings from
step 7, directory permissions, `.htpasswd`, and (best-effort) the Apache
modules — everything from steps 1–7 — and tells you exactly what's still
missing. Fix anything reported as
`FAIL` (blocking) before continuing; `WARN` items are worth reading but
won't stop the app from loading. Re-run it any time you're unsure the
environment is still correctly set up (e.g. after an OS upgrade).

## 9. First run

Open `http://<server>/` (or your vhost's `ServerName`) in a browser. You'll
be prompted for the HTTP login from step 6.

From there:

1. Go to **Configuration** and set the admin email, gateway host, and
   cache TTLs to taste (these persist in `protected/data/params.ini`, not
   in the database).
2. Go to **Hosts → Create Host** to add your first device (name, IP, MAC,
   type, and — if it uses SNMP — an **SNMP Template** with the
   community/credentials).
3. Go to **SNMP Templates** first if you haven't already, to define the
   community string (v1/v2c) or v3 credentials your devices use, before
   attaching one to a host.

**About page version.** The Docker image bakes the release tag into a
`VERSION` file at the project root at build time, shown on the **About**
page. A standalone checkout has no such file, so it shows "development"
instead — optionally create one yourself if you want a specific tag to
show there:

```bash
echo "1.26.0" | sudo tee /var/www/phpnetmap/VERSION
```

## 10. Updating

```bash
cd /var/www/phpnetmap
sudo git pull
php requirements-check.php   # confirm nothing changed under you
```

`protected/data/` (the database and `params.ini`) is untouched by `git
pull` as long as it stays out of version control (it already is, via
`.gitignore`), so your data and settings survive an update.

## 11. Troubleshooting

**Blank page or HTTP 500 on every request.**
Almost always one of: `.htpasswd` missing/empty (step 6), `protected/data`
or `protected/runtime` not writable by `www-data` (step 4), or a missing
PHP extension. Run `php requirements-check.php` first — it catches all
three. Then check Apache's error log:

```bash
sudo tail -f /var/log/apache2/error.log
```

**"APC disabled or not installed" exception.**
`protected/components/CacheAPC.php` throws this the moment the app tries
to cache anything, if `apcu.enabled` is off — check `php -i | grep apc`,
and make sure `php7.4-apcu` is installed. If you can't install it, turn
"Cache" off on the Configuration page instead.

**Clean URLs 404, or `.htpasswd` seems to be ignored.**
`mod_rewrite` and/or `AllowOverride All` aren't active for this vhost —
re-check step 5, then `sudo apache2ctl -M | grep -E 'rewrite|authn_file|auth_basic'`.

**SNMP queries never return data.**
This is almost always network reachability (UDP/161 to the device) or a
wrong community/credentials on the host's SNMP Template — not a PHP/server
issue. Confirm from the same server with the `snmpwalk` CLI tool
(`apt-get install snmp` if you don't have it) before assuming the app is
broken:

```bash
snmpwalk -v2c -c <community> <device-ip> system
```

**Still stuck?** Open an issue at
<https://github.com/marcelofmatos/phpnetmap/issues> with the output of
`php requirements-check.php` and the relevant lines from
`/var/log/apache2/error.log`.

# Deployment on Metanet/Plesk

## Hosting model

The complete deployable application is contained in the repository's `public/` folder. No file or directory above the webroot is required.

Upload the **contents** of `public/` into the domain's document root:

- `index.php`
- `.htaccess`
- `.env`
- `composer.json`
- `assets/`
- `app/`
- `setup/`
- `vendor/` after Composer installation

The top-level `.htaccess` denies direct HTTP access to `.env`, Composer files, `app/`, `setup/`, `var/` and `vendor/`.

## Requirements

- PHP 8.2
- Apache with `mod_rewrite` and allowed `.htaccess` overrides
- PDO MySQL extension
- MySQL 5.6 or newer
- HTTPS

There is no PHP framework, Node.js runtime or frontend build process.

## Installation

1. Upload the contents of the repository's `public/` folder to the Plesk document root.
2. In Plesk Composer, use the `composer.json` located in that document root and run install, or run `composer install --no-dev --classmap-authoritative`.
3. Copy `.env.example` to `.env` and enter the production values.
4. Import `setup/schema.sql` through phpMyAdmin, then leave the file protected by `.htaccess`.
5. Enforce HTTPS.
6. Verify that `/.env`, `/composer.json`, `/app/`, `/setup/` and `/vendor/` return HTTP 403 or 404.

## PHP production settings

- `display_errors = Off`
- `log_errors = On`
- `session.cookie_secure = 1`
- `session.cookie_httponly = 1`
- `session.cookie_samesite = Lax`
- `expose_php = Off`

Application secrets must only be stored in `.env` or Plesk environment variables and must never be committed.

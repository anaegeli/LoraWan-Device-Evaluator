# Deployment on Metanet/Plesk

## Requirements

- PHP 8.2
- Apache with `mod_rewrite`
- PDO MySQL extension
- MySQL 5.6 or newer
- HTTPS

The application deliberately has no runtime framework and no Node.js requirement.

## Recommended document root

Point the domain's document root to the repository's `public/` directory. This prevents direct web access to configuration, source code and SQL files.

If Plesk does not allow a custom document root, the root `.htaccess` forwards requests to `public/` and blocks sensitive directories. A dedicated document root is still preferred.

## Installation

1. Upload or check out the release.
2. Run `composer install --no-dev --classmap-authoritative`.
3. Copy `.env.example` to `.env` and enter the production values.
4. Keep `.env` outside public access and restrict its filesystem permissions.
5. Create the database and import `database/schema.sql`.
6. Configure HTTPS and redirect HTTP to HTTPS.
7. Confirm that `/.env`, `/src/`, `/database/` and `/vendor/` return HTTP 403 or 404.

## PHP production settings

- `display_errors = Off`
- `log_errors = On`
- `session.cookie_secure = 1`
- `session.cookie_httponly = 1`
- `session.cookie_samesite = Lax`
- `expose_php = Off`

Application secrets must only be stored in `.env` or Plesk environment variables and must never be committed.

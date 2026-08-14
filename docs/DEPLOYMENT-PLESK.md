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
- cURL, JSON, OpenSSL, DOM and mbstring PHP extensions
- MySQL 5.6 or newer
- HTTPS

There is no PHP framework, Node.js runtime or frontend build process.

## Installation

1. Upload the contents of the repository's `public/` folder to the Plesk document root.
2. In Plesk Composer, use the `composer.json` located in that document root and run install, or run `composer install --no-dev --classmap-authoritative`.
3. Copy `.env.example` to `.env` and enter the production values.
4. Import `setup/schema.sql` through phpMyAdmin, then leave the file protected by `.htaccess`.
5. Configure either OIDC or SAML as described below.
6. Enforce HTTPS.
7. Verify that `/.env`, `/composer.json`, `/app/`, `/setup/` and `/vendor/` return HTTP 403 or 404.

## Authentication

Production access is blocked while `AUTH_DRIVER=none`, unless `AUTH_ALLOW_UNAUTHENTICATED=true` is deliberately set. That exception is intended only for a short installation test.

### OIDC

Set:

- `AUTH_DRIVER=oidc`
- `OIDC_ISSUER`
- `OIDC_CLIENT_ID`
- `OIDC_CLIENT_SECRET`
- `OIDC_REDIRECT_URI`, normally `https://your-domain/auth.php?action=login`

The identity provider must return `sub`; `name` and `email` are used when available.

### SAML

Set:

- `AUTH_DRIVER=saml`
- `SAML_SP_ENTITY_ID`
- `SAML_ACS_URL`, normally `https://your-domain/auth.php?action=acs`
- `SAML_IDP_ENTITY_ID`
- `SAML_IDP_SSO_URL`
- `SAML_IDP_X509_CERT`
- optionally the name and email attribute names

Write a multiline certificate on one line using literal `\\n` sequences. Signed assertions are required.

### Roles

New authenticated users receive `AUTH_DEFAULT_ROLE`, normally `viewer`. Set `AUTH_ADMIN_IDENTITIES` to a comma-separated list of OIDC subjects, SAML NameIDs or email addresses. Matching identities are created as administrators. Existing roles remain stored locally and are not silently downgraded by a later login.

## PHP production settings

- `display_errors = Off`
- `log_errors = On`
- `session.cookie_secure = 1`
- `session.cookie_httponly = 1`
- `session.cookie_samesite = Lax`
- `expose_php = Off`

Application secrets must only be stored in `.env` or Plesk environment variables and must never be committed.

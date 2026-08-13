# Security policy

Please report security issues privately to the repository owner instead of opening a public issue.

## Deployment principles

- expose only the `public/` directory
- enforce HTTPS
- never commit `.env`
- use a dedicated database user with only the required permissions
- keep PHP 8.2 and all Composer packages patched
- configure OIDC or SAML before allowing production access

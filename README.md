# mary.win

Primary personal site, portfolio, writing, contact, and positioning.

Personal site.

## Role

**Should own:** Primary personal site, portfolio, writing, contact, and positioning.

## Status

Scaffolding — repository is being brought online as part of the sifrious portfolio. Content and implementation to follow.

## Authentication

mary.win signs in through the shared Zahir account seam
(`sifrious/accounts-client`), the same one Logres and Burdgen use. There is no
registration, password reset, or email verification here: the identity provider
owns credentials, verification, and recovery, and Zahir owns account identity
and whether an account may use this site.

The public site — portfolio, writing, contact — needs no account and must keep
needing none. Only `/dashboard` and `/settings` sit behind sign-in.

The auth release gate is the shared conformance suite, run against this
application's real routes and session:

```bash
php artisan test --filter AuthenticationConformance
```

Every assertion in it belongs to the package; this repository supplies only the
wiring in `tests/Conformance/MaryWinConsumer.php`.

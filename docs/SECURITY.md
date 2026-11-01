# Security

_Describes the current skeleton (2026-11-01). Update with every security-relevant change._

| Area | Current state |
|---|---|
| Authentication | Not implemented. See [AUTHENTICATION_AND_AUTHORIZATION.md](AUTHENTICATION_AND_AUTHORIZATION.md). |
| Authorisation / roles | Not implemented. All routes are public. |
| Password security | `hashed` cast on `User.password` (bcrypt, cost 12 from `BCRYPT_ROUNDS`). `password` and `remember_token` are hidden from serialisation via `#[Hidden]`. |
| Sessions | Database driver, 120-minute lifetime, `SESSION_ENCRYPT=false` (from `.env.example`). |
| CSRF | Laravel's default `web` middleware group includes CSRF token validation; no exclusions configured in `bootstrap/app.php`. |
| XSS | Blade `{{ }}` escapes output. No user-generated content is rendered yet. Rule: never render user data with `{!! !!}`. |
| SQL injection | No custom queries exist. Rule: use Eloquent / query builder bindings; never interpolate input into `DB::raw` / `whereRaw`. |
| Rate limiting | None configured. |
| File uploads | None exist. |
| Financial operation protection | No financial operations exist. Planned: authorisation + transactions + row locks + idempotency keys. |
| Audit logging | Not implemented. Only the default `stack`/`single` log channel. |
| Sensitive data | Secrets live in `.env` (git-ignored). `APP_DEBUG=true` locally — **must be false in production**. |
| Error responses | JSON for `api/*` or JSON-expecting requests (`bootstrap/app.php`). |
| Dependencies | `composer audit` (2026-11-01): 1 medium advisory, `symfony/mime` CVE-2026-45070. Unresolved. |

## Assumptions

- Production runs over HTTPS behind a correctly configured web server pointing at `public/`.
- `.env` is never committed and `APP_KEY` is unique per environment.

## Known limitations

- No auth, no rate limiting, no audit trail — the application must not be exposed publicly or hold real data in its current state.

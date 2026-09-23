# Architecture overview - Login

Object-oriented, layered, constructor-injected. No static classes, no
procedural function chain -- each layer below depends only on the one above
it via its constructor.

```
LoginConfig            table/column names, security knobs, routes -- one
                        object every other layer takes as a dependency
  -> Database           lazy PDO connection built from LoginConfig
    -> Repositories      UserRepository, AttemptRepository,
                          SecurityAuditRepository, OtpRepository,
                          SettingsRepository -- one table's worth of
                          persistence each, all PDO/prepared statements
      -> Security         SessionManager (session lifecycle, CSRF tokens,
                           idle timeout), AuthManager (login/logout,
                           enforceLogin/requireRole, session bookkeeping),
                           OtpManager (issue/verify one-time codes)
        -> Controllers      LoginController, SelfServiceOtpController,
                             UserController, PasswordController,
                             SettingsController -- each takes a
                             $method/$_POST-shaped call and returns either an
                             AuthResult or a small render-state array
          -> Views            LoginView, OtpLoginView, AddUserView,
                               RemoveUserView, ChangePasswordView,
                               SessionSettingsView -- render(), nothing else
```

A host application's own PHP file (see `examples/`) wires one instance of
each layer together and calls the appropriate Controller method, then hands
its return value to the matching View.

## Two ways to authenticate

**Password.** `LoginController::handleLogin()` -> `AuthManager::login()`:
verifies the password, checks `AttemptRepository` for a brute-force lockout,
regenerates the session ID (fixation), writes `$_SESSION['user_*']`, logs the
event via `SecurityAuditRepository` if one was supplied.

**Passwordless (email OTP).** Two round trips through `SelfServiceOtpController`:
1. `handleCodeRequest()` -> `OtpManager::startChallenge()` generates a code,
   stores only a keyed HMAC-SHA256 of it, hands the plaintext to a host-supplied
   `OtpMailerInterface`. Response is identical whether or not the address is
   already registered -- a first-time address is registered on a *verified*
   code, not on request.
2. `handleCodeVerification()` -> `OtpManager::verifyChallenge()` checks the
   code (single use, attempt-limited, rate-limited), then
   `AuthManager::establishPasswordlessSession()` does the same session
   bookkeeping `login()` does, minus the password check.

This is intentionally the *only* way to reach `establishPasswordlessSession()`
in the library: a verified code and nothing else. An earlier `OtpController`
let a verified code sign in to a pre-registered, password-protected account
as an alternate credential -- removed 2026-09-23, since an "either credential
works" design weakens an account to its weaker factor rather than adding a
second one. A real second factor needs a state this library doesn't have yet
(password verified, second factor still pending, distinct from `isLoggedIn()`)
-- see the `2FA/MFA` TODO item.

## Cross-cutting

- **CSRF**: `SessionManager::generateCsrfToken()` / `validateCsrfToken()` --
  one-time-use token per form render, checked by every Controller that
  handles a POST.
- **Roles**: `AuthManager::requireRole()` / `requireAllRoles()` check
  `$_SESSION['user_roles']` against a role string. `UserController` and
  `SettingsController` gate user/settings management specifically on the
  `system_manager` role (configurable via `LoginConfig::$userManagerRole`).
- **Session timeout**: static by default (`LoginConfig::$sessionTimeoutSeconds`);
  optionally overridden at runtime via `SettingsRepository` (`auth_settings`
  table) if one is passed into `SessionManager` -- see `SettingsController`.
- **Audit log**: every Security-layer action that changes auth state
  (login, logout, lockout, session discrepancy, OTP issue/verify/lock,
  user add/remove, settings change) logs through `SecurityAuditLoggerInterface`
  if one was supplied; it's optional everywhere, never required.

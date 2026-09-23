# Project TODO

## 🔧 In Progress
- [ ] generate architecture md
- [ ] generate readme
- [ ] ensure login method sets all the desired cookies
- [ ] review authmanager.isLoggedIn to check for each of the monitoring / tracking coookies
- [ ] review code for proper and thorough user monitoring method to be used on each page / with each AJAX call
- [ ] 2FA/MFA support -- password AND a code, both required, not either one
      alone. Needs a state AuthManager doesn't have yet: "password verified,
      second factor still pending," distinct from isLoggedIn() -- a genuine
      new flow, not a reuse of anything OTP-related that exists today (see the
      2026-09-23 note below: the one class shaped like this, OtpController, was
      removed specifically because "either credential suffices" weakens an
      account rather than strengthening it -- 2FA needs its own state machine).
- [X] Add email or app-based OTP -- 2026-09-09, hardened + tested 2026-09-10.
      Additive, opt-in. Passwordless: verified code ->
      AuthManager::establishPasswordlessSession().
      Classes: Security\OtpManager (+ OtpChallengeOutcome / OtpVerificationOutcome
      enums), Repositories\OtpRepository, Controllers\SelfServiceOtpController,
      Repositories\PasswordlessUserRepository, Views\OtpLoginView,
      Contracts\{OtpMailerInterface, OtpRepositoryInterface, SecurityAuditLoggerInterface}.
      Abuse controls: single live code (supersede), per-code attempt ceiling,
      resend cooldown, per-identifier hourly send cap. keyed-HMAC digests
      only; constant-time compare; atomic single-use consume().
      Tables: database/create_example_database.php.
      Tests: tests/Security/OtpManagerTest.php (20 cases) + CI (.github/workflows/ci.yml).
      Example: examples/otp_login.php.
- [X] Removed OtpController + TieredOtpController -- 2026-09-23. OtpController
      let a verified one-time code sign in to a pre-registered,
      password-protected account as an alternate credential -- "either
      suffices" weakens the account to whichever factor is weaker (an
      attacker who can't guess the password just takes the code path
      instead), the opposite of what 2FA is supposed to do. Every user
      reachable through it already had a password on file (UserRepository
      requires one), so there was no legitimate case where it was the *only*
      way in. TieredOtpController (routed to it vs. SelfServiceOtpController
      by which store held the address) went with it, since it can't function
      without OtpController. Neither was ever wired into gss88 or Clarassign.
      SelfServiceOtpController is unaffected -- it never checks a password,
      by design, and is the only way to reach
      AuthManager::establishPasswordlessSession() now. examples/otp_login.php
      rewritten to demonstrate it instead.
- [X] Session idle expiration is now runtime-adjustable -- 2026-09-09.
      SettingsRepository + SettingsController + SessionSettingsView store
      `session_timeout_seconds` in auth_settings; SessionManager reads it via
      getEffectiveTimeoutSeconds(), clamped to LoginConfig min/max, falling back
      to the static default when the table/row/repo is absent (so existing
      consumers are unaffected). See examples/session_settings.php.
- [ ] Set appropriate session.gc_maxlifetime alongside the idle timeout logic.
- [ ] Gracefully handle DB errors using logs + generic messages.
- [ ] Consider CSRF tokens if your architecture expands.

## 📌 Next Tasks
- [ ] update readme
- [ ] Enhance rate limiting to track failed attempts by both Username and IP address (OWASP A04)
- [ ] Integrate a PSR-3 compatible logger to track significant security events (OWASP A09)
- [X] Audit `UserRepository.php` dynamic column/table names in SQL queries (OWASP A03)
      -- 2026-09-14. Every interpolated table/column name now passes through a
      private identifier() guard (`^[A-Za-z_][A-Za-z0-9_]*$`, throws otherwise)
      before reaching sprintf(). Not reachable by request input today (values
      come from this app's own LoginConfig), but a real value if LoginConfig
      is ever wired to something less trusted.
- [ ] DB Permissions: Change the current DB user to an "admin" profile, and create a standard DB user who only has SELECT and UPDATE privileges limited strictly to their own user profile data.

## 🧭 Future Enhancements
- [ ] 

## 🧪 Testing Checklist
- [ ] 
- [ ] 

## 🗂 Notes
- 

## Completed
- [X] separate out and rename project files from old code
- [X] add nav constants file
- [X] symlink login.php to public_htmlppleaeswe
- [X] handle redirect to login.php
- [X] generate architecture folders and files
- [X] git init
- [X] rename all classes with boyds-little-login-library-for-php-
- [X] refactor nav constants
- [X] rename all classes with BoydsLittleLoginLibraryForPhp
- [X] review for functionality after refactor
- [X] refactor and rename auth.php to focus on login, create monitoring file for ongoing security checks
- [X] refactor verify_no_existing_user_credentials to use unified method for error message reporting re login data
- [X] refactor method names as necessary
- [X] refactor nav constants
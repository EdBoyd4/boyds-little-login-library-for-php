# Project TODO

## 🔧 In Progress
- [ ] generate architecture md
- [ ] generate readme
- [ ] ensure login method sets all the desired cookies
- [ ] review authmanager.isLoggedIn to check for each of the monitoring / tracking coookies
- [ ] review code for proper and thorough user monitoring method to be used on each page / with each AJAX call

## 📌 Next Tasks
- [ ] update readme
- [ ] Enhance rate limiting to track failed attempts by both Username and IP address (OWASP A04)
- [ ] Integrate a PSR-3 compatible logger to track significant security events (OWASP A09)
- [ ] Audit `UserRepository.php` dynamic column/table names in SQL queries (OWASP A03)

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
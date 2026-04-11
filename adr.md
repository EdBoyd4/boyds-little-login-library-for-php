# Architecture overview - Login
The public folder contains a wrapper from which the Constants, Model, View, Controller, and Validation files are called. The session, of course, is called in the wrapper.  

# Public page
login.php  

# Elements and Methods

## Interaction Flow
on initial load
1. login.php . display_login_page()

on form submissioin
1. login.php . process_login_attempt()
2. boyds-little-login-library-for-php_controller.php . Auth::validateCsrfToken()
3. boyds-little-login-library-for-php_controller.php . verify_no_existing_user_credentials()
4. boyds-little-login-library-for-php_controller.php . verify_entry_of_username_and_password()
5. boyds-little-login-library-for-php_controller.php . validateUser($user_name)
6. boyds-little-login-library-for-php_controller.php . validate_user_login($user_info, $user_password)
7. redirect


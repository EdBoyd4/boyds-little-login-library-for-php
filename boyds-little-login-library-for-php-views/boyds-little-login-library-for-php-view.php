<?php

function do_html_header($title, $detailed_title) {
?>
<!doctype html>
  <html>
  <head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($title);?></title>
    <style>
      body { font-family: Arial, Helvetica, sans-serif; font-size: 13px }
      li, td { font-family: Arial, Helvetica, sans-serif; font-size: 13px }
      hr { color: #3333cc;}
      a { color: #000 }
      div.formblock
         { background: #ccc; width: 300px; padding: 6px; border: 1px solid #000;}
    </style>
  </head>
  <body>
  <div>
      <h1><?php echo htmlspecialchars($title);?></h1>
  </div>
  <hr />
<?php
  if($title) {
    do_html_heading(htmlspecialchars($detailed_title));
  }
}

function do_html_footer() {
?>
  </body>
  </html>
<?php
}

function do_html_heading($heading) {
?>
  <h2><?php echo htmlspecialchars($heading);?></h2>
<?php
}

function do_html_URL($url, $name) {
?>
  <br><a href="<?php echo $url;?>"><?php echo htmlspecialchars($name);?></a><br>
<?php
}

function display_login_form(string $csrfToken) {
?>

  <form name="login" method="post" action="">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

  <div class="formblock">
    <h2>Log In Here</h2>

    <p><label for="username">Username:</label><br/>
    <input type="text" name="username" id="username" required autocomplete="username" />

    <p><label for="password">Password:</label><br/>
    <input type="password" name="password" id="password" required autocomplete="current-password" />

    <button type="submit" value="login" name="login">Log In</button>
  </div>

 </form>
<?php
}

function display_login_page() {
    do_html_header('Clarium Investigations User Portal', 'Investigator Log In');
    $csrfToken = Auth::generateCsrfToken();
    display_login_form($csrfToken);
    do_html_footer();
}
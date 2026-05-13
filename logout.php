<?php

// 1. Remove the JWT cookie by setting
// an expiration time in the past

if (isset($_COOKIE['jwt_token'])) {

    // Set expiration time to one hour ago
    // so the browser deletes the cookie immediately

    setcookie("jwt_token", "", time() - 3600, "/");
}

// 2. Redirect the user to the login page

header("Location: login.php");
exit;

?>
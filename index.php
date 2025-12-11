<?php
// Front controller proxy: serve the app without exposing /public in URLs
$public = __DIR__ . '/public';
chdir($public);
require $public . '/index.php';

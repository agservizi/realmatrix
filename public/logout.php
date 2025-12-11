<?php
require_once __DIR__ . '/../includes/init.php';

// Termina la sessione e reindirizza alla home (o login se presente)
session_unset();
session_destroy();
header('Location: /public/index.php');
exit;

<?php
// Basic layout wrapper
$csrf = hash_hmac('sha256', session_id(), $config['csrf']['secret'] ?? '');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RealMatrix</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
</head>
<body>
<nav class="navbar is-dark" role="navigation">
  <div class="navbar-brand">
    <a class="navbar-item" href="/dashboard">RealMatrix</a>
  </div>
  <div class="navbar-menu">
    <div class="navbar-start">
      <a class="navbar-item" href="/dashboard">Dashboard</a>
      <a class="navbar-item" href="/immobili">Immobili</a>
      <a class="navbar-item" href="/clienti">Clienti</a>
      <a class="navbar-item" href="/collaboratori">Collaboratori</a>
      <a class="navbar-item" href="/sharing">Home Sharing</a>
    </div>
    <div class="navbar-end">
      <div class="navbar-item"><button id="logoutBtn" class="button is-light">Logout</button></div>
    </div>
  </div>
</nav>
<section class="section">
  <div class="container">
    <?php include $templatePath; ?>
  </div>
</section>
<script type="module" src="/assets/js/main.js"></script>
</body>
</html>

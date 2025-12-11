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
    <link rel="stylesheet" href="/public/assets/css/main.css">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
</head>
<body class="has-background-light">
<nav class="navbar is-dark topbar" role="navigation">
  <div class="navbar-brand">
    <a class="navbar-item" href="/dashboard">RealMatrix</a>
  </div>
  <div class="navbar-end pr-4">
    <div class="navbar-item"><button id="logoutBtn" class="button is-light">Logout</button></div>
  </div>
</nav>

<div class="layout-shell container is-fluid">
  <aside class="menu sidebar">
    <p class="menu-label">Navigazione</p>
    <ul class="menu-list">
      <li><a href="/dashboard">Dashboard</a></li>
      <li><a href="/immobili">Immobili</a></li>
      <li><a href="/clienti">Clienti</a></li>
      <li><a href="/collaboratori">Collaboratori</a></li>
      <li><a href="/sharing">Home Sharing</a></li>
    </ul>
  </aside>
  <main class="main-panel">
    <section class="section">
      <div class="container is-fluid">
        <?php include $templatePath; ?>
      </div>
    </section>
  </main>
</div>

<script type="module" src="/public/assets/js/main.js"></script>
</body>
</html>

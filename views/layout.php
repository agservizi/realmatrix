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
    <link href="/public/assets/vendor/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/public/assets/css/main.css">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/dashboard">RealMatrix</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="/dashboard">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="/immobili">Immobili</a></li>
        <li class="nav-item"><a class="nav-link" href="/clienti">Clienti</a></li>
        <li class="nav-item"><a class="nav-link" href="/collaboratori">Collaboratori</a></li>
        <li class="nav-item"><a class="nav-link" href="/sharing">Home Sharing</a></li>
      </ul>
      <button id="logoutBtn" class="btn btn-outline-light">Logout</button>
    </div>
  </div>
</nav>
<main class="py-4">
  <div class="container">
    <?php include $templatePath; ?>
  </div>
</main>
<script src="/public/assets/vendor/bootstrap.bundle.min.js"></script>
<script type="module" src="/public/assets/js/main.js"></script>
</body>
</html>

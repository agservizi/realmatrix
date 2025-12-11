<?php
// includes/topbar.php
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom sticky-top" style="z-index:1040;">
  <div class="container-fluid">
    <a class="navbar-brand" href="/public/">DomusCore</a>
    <button class="btn btn-outline-light d-lg-none" id="sidebarToggleMobile">☰</button>
    <div class="ms-auto d-flex align-items-center">
      <div class="me-3 text-muted small">
        Benvenuto, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Ospite'); ?>
      </div>
      <div class="dropdown">
        <a class="btn btn-sm btn-outline-light dropdown-toggle" href="#" data-bs-toggle="dropdown">Account</a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="/public/profile.php">Profilo</a></li>
          <li><a class="dropdown-item" href="/public/logout.php">Esci</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>

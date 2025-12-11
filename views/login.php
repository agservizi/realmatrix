<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - RealMatrix</title>
  <link href="/public/assets/vendor/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/public/assets/css/main.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 420px;">
  <div class="card shadow-sm">
    <div class="card-body">
      <h1 class="h4 mb-4">Accedi</h1>
      <form id="login-form" class="mb-2">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input class="form-control" name="email" type="email" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input class="form-control" name="password" type="password" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Login</button>
      </form>
      <div class="form-text text-danger" id="login-help"></div>
    </div>
  </div>
</div>
<script src="/public/assets/vendor/bootstrap.bundle.min.js"></script>
<script type="module" src="/public/assets/js/login.js"></script>
</body>
</html>

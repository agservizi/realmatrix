<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - RealMatrix</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
  <link rel="stylesheet" href="/public/assets/css/main.css">
</head>
<body class="has-background-light">
<section class="section">
  <div class="container" style="max-width: 420px;">
    <div class="box">
      <h1 class="title">Accedi</h1>
      <form id="login-form">
        <div class="field">
          <label class="label">Email</label>
          <div class="control">
            <input class="input" name="email" type="email" required>
          </div>
        </div>
        <div class="field">
          <label class="label">Password</label>
          <div class="control">
            <input class="input" name="password" type="password" required>
          </div>
        </div>
        <div class="field">
          <button class="button is-primary is-fullwidth" type="submit">Login</button>
        </div>
      </form>
      <p class="help" id="login-help"></p>
    </div>
  </div>
</section>
<script type="module" src="/public/assets/js/login.js"></script>
</body>
</html>

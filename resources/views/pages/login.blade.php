<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login – SKPI UPR</title>

  @vite(['resources/css/app.css','resources/js/pages/login.js'])
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <meta id="bridge" data-admin-url="{{ url('/admin') }}">
</head>
<body>

<div class="card card-login shadow-sm">
  <div class="card-body p-4">
    <div class="text-center mb-3">
      <div class="fs-2 fw-bold">SKPI UPR</div>
      <div class="text-muted">Masuk ke dashboard</div>
    </div>

    <div id="loginError" class="alert alert-danger d-none"></div>

    <form id="loginForm">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input id="username" type="text" class="form-control" autocomplete="username" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input id="password" type="password" class="form-control" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Masuk</button>
    </form>
  </div>
</div>

</body>
</html>

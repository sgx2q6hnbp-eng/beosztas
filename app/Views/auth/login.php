<?php
require_once BASE_PATH . '/app/Models/User.php';
$csrf = User::generateCsrfToken();
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés – Munkabeosztás</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body class="bg-login d-flex align-items-center justify-content-center min-vh-100">

<div class="login-wrapper">

    <!-- Logó / Cím -->
    <div class="text-center mb-4">
        <div class="login-logo mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#3B82F6" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
            </svg>
        </div>
        <h1 class="h4 fw-bold text-dark">Munkabeosztás</h1>
        <p class="text-muted small">Jelentkezz be a folytatáshoz</p>
    </div>

    <!-- Hibaüzenet -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <small><?= htmlspecialchars($error) ?></small>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Login kártya -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form method="POST" action="/login" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <!-- E-mail -->
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small">E-mail cím</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="nev@domain.hu"
                        autocomplete="email"
                        required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    >
                </div>

                <!-- Jelszó -->
                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold small">Jelszó</label>
                    <div class="input-group">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Emlékezz rám -->
                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label small" for="remember">Emlékezz rám (30 nap)</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    Bejelentkezés
                </button>
            </form>
        </div>
    </div>

    <p class="text-center text-muted small mt-3">
        Elfelejtett jelszó? Keresd az adminisztrátort.
    </p>

</div><!-- /.login-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Jelszó megmutatás/elrejtés
    document.getElementById('togglePassword').addEventListener('click', function() {
        const pwd = document.getElementById('password');
        pwd.type = pwd.type === 'password' ? 'text' : 'password';
    });
</script>
</body>
</html>

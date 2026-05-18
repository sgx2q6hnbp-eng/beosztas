<?php
$currentUri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$isAdmin     = AuthService::isAdmin();
$userName    = $_SESSION['user_name'] ?? 'Felhasználó';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-navbar shadow-sm sticky-top">
    <div class="container">

        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="/dashboard">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
            </svg>
            Beosztás
        </a>

        <!-- Hamburger (mobil) -->
        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-label="Menü">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menü -->
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <!-- Beosztás -->
                <li class="nav-item">
                    <a class="nav-link <?= str_starts_with($currentUri, '/schedule') ? 'active' : '' ?>"
                       href="/schedule">
                        Beosztás
                    </a>
                </li>

                <!-- Szabadság (zöld akcentus) -->
                <li class="nav-item">
                    <a class="nav-link nav-leave <?= str_starts_with($currentUri, '/leave') ? 'active' : '' ?>"
                       href="/leave">
                        🌿 Szabadság
                    </a>
                </li>

                <!-- Csere (lila akcentus) -->
                <li class="nav-item">
                    <a class="nav-link nav-swap <?= str_starts_with($currentUri, '/swap') ? 'active' : '' ?>"
                       href="/swap">
                        🔄 Csere
                    </a>
                </li>

                <!-- Admin menü -->
                <?php if ($isAdmin): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= str_starts_with($currentUri, '/admin') ? 'active' : '' ?>"
                       href="#" data-bs-toggle="dropdown">
                        ⚙ Admin
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="/admin/import">Excel Import</a></li>
                        <li><a class="dropdown-item" href="/admin/employees">Dolgozók</a></li>
                        <li><a class="dropdown-item" href="/admin/leaves">Kérelmek</a></li>
                        <li><?php $__pc=Database::getInstance()->query("SELECT COUNT(*) FROM swap_requests WHERE status IN ('pending','accepted')")->fetchColumn(); ?><a class="dropdown-item" href="/admin/swaps">Csere kérelmek<?php if($__pc>0): ?> <span class="badge bg-danger ms-1"><?php echo $__pc; ?></span><?php endif; ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/admin/logs">Audit napló</a></li>
                        <li><a class="dropdown-item <?= $currentUri === '/admin/settings' ? 'active' : '' ?>" href="/admin/settings">⚙️ Beállítások</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Jobb oldal: felhasználó + kilépés -->
            <div class="d-flex align-items-center gap-3">
                <span class="text-white-50 small d-none d-lg-block">
                    👤 <?= htmlspecialchars($userName) ?>
                </span>
                <a href="/logout" class="btn btn-outline-light btn-sm">Kilépés</a>
            </div>
        </div>

    </div>
</nav>

<?php
$flashMessage = take_flash();
$title = $title ?? 'Sistem Pengaduan Fasilitas';
$activePage = (string) ($_GET['page'] ?? 'dashboard');
ob_start();
require $content;
$pageContent = ob_get_clean();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · Fasilitas Kampus</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="role-<?= e($user['role'] ?? 'guest') ?>">
<?php if (isset($user)): ?>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="index.php?page=dashboard">
                <span class="brand-mark">F</span><span>Fasilitas<span class="brand-light">Kampus</span></span>
            </a>
            <div class="nav-label">MENU UTAMA</div>
            <nav class="nav-links">
                <a class="<?= $activePage === 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard"><span>▦</span> Dashboard</a>
                <?php if ($user['role'] === 'mahasiswa'): ?>
                    <a class="<?= $activePage === 'new' ? 'active' : '' ?>" href="index.php?page=new"><span>＋</span> Buat Pengaduan</a>
                <?php endif; ?>
                <a class="<?= $activePage === 'complaints' ? 'active' : '' ?>" href="index.php?page=complaints"><span>▤</span><?= $user['role'] === 'petugas' ? 'Tugas Saya' : ($user['role'] === 'admin' ? 'Semua Pengaduan' : 'Pengaduan Saya') ?></a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a class="<?= $activePage === 'staff' ? 'active' : '' ?>" href="index.php?page=staff"><span>♙</span> Data Petugas</a>
                    <a class="<?= $activePage === 'students' ? 'active' : '' ?>" href="index.php?page=students"><span>♙</span> Data Mahasiswa</a>
                    <a class="<?= $activePage === 'categories' ? 'active' : '' ?>" href="index.php?page=categories"><span>◇</span> Kategori</a>
                    <a class="<?= $activePage === 'performance' ? 'active' : '' ?>" href="index.php?page=performance"><span>↗</span> Kinerja Petugas</a>
                    <a href="index.php?page=export"><span>⇩</span> Ekspor CSV</a>
                    <a href="index.php?page=report"><span>▧</span> Rekap PDF</a>
                <?php elseif ($user['role'] === 'petugas'): ?>
                    <a class="<?= $activePage === 'calendar' ? 'active' : '' ?>" href="index.php?page=calendar"><span>▦</span> Kalender Tugas</a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-bottom">
                <div class="sidebar-note"><strong>Butuh bantuan?</strong><span>Hubungi administrator kampus untuk bantuan akun.</span></div>
                <form method="post" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="logout">
                    <button class="nav-logout" type="submit"><span>↪</span> Keluar</button>
                </form>
            </div>
        </aside>
        <main class="main-area">
            <header class="topbar">
                <div class="mobile-brand"><span class="brand-mark">F</span> Fasilitas Kampus</div>
                <div class="topbar-spacer"></div>
                <div class="profile">
                    <span class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
                    <span><strong><?= e($user['name']) ?></strong><small><?= e(role_label($user['role'])) ?></small></span>
                </div>
            </header>
            <div class="page-wrap">
                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?></div>
                <?php endif; ?>
                <?= $pageContent ?>
            </div>
            <footer class="footer">Sistem Pengaduan Fasilitas Kampus <span>·</span> Kelompok 7</footer>
        </main>
    </div>
<?php else: ?>
    <main class="auth-shell">
        <?php if ($flashMessage): ?>
            <div class="alert alert-<?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?></div>
        <?php endif; ?>
        <?= $pageContent ?>
    </main>
<?php endif; ?>
</body>
</html>

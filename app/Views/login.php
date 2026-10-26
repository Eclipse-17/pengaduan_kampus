<?php $title = 'Masuk'; ?>
<section class="login-card">
    <a class="brand login-brand" href="index.php?page=login">
        <span class="brand-mark">F</span><span>Fasilitas<span class="brand-light">Kampus</span></span>
    </a>
    <p class="eyebrow">PORTAL PENGADUAN KAMPUS</p>
    <h1>Selamat datang</h1>
    <p class="muted">Masuk untuk melaporkan atau menangani fasilitas kampus.</p>
    <form method="post" action="index.php" class="stack-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="login">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="username" required autofocus placeholder="nama@kampus.ac.id">
        <label for="password">Kata sandi</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Masukkan kata sandi">
        <button class="button button-primary button-full" type="submit">Masuk ke akun <span>→</span></button>
    </form>
    <p class="login-help">Akun demo tersedia setelah menjalankan <code>php database/seed.php</code>.</p>
</section>

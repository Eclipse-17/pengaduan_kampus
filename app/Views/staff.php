<?php $title = 'Data Petugas'; ?>
<section class="page-heading"><p class="eyebrow">PENGELOLAAN PENGGUNA</p><h1>Data Petugas</h1><p class="muted">Kelola akun petugas fasilitas yang menerima tugas perbaikan.</p></section>
<div class="staff-layout">
    <section class="panel staff-create"><div class="panel-heading"><div><h2>Tambah Petugas</h2><p class="muted">Buat akun untuk petugas fasilitas baru.</p></div></div>
        <form method="post" action="index.php" class="stack-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="create_staff">
            <label for="staff-name">Nama lengkap</label><input id="staff-name" name="name" required maxlength="120" placeholder="Nama petugas">
            <label for="staff-email">Email</label><input id="staff-email" name="email" type="email" required maxlength="190" placeholder="petugas@kampus.ac.id">
            <label for="staff-password">Kata sandi awal</label><input id="staff-password" name="password" type="password" minlength="10" required autocomplete="new-password"><small>Minimal 10 karakter. Sampaikan secara aman kepada petugas.</small>
            <button class="button button-primary button-full" type="submit">Tambah Petugas <span>＋</span></button>
        </form>
    </section>
    <section class="panel"><div class="panel-heading"><div><h2>Daftar Petugas</h2><p class="muted"><?= count($staff) ?> akun terdaftar</p></div></div>
        <?php if ($staff): ?><div class="staff-list"><?php foreach ($staff as $person): ?>
            <details class="staff-item"><summary><span class="avatar"><?= e(mb_strtoupper(mb_substr($person['name'], 0, 1))) ?></span><span class="staff-summary"><strong><?= e($person['name']) ?></strong><small><?= e($person['email']) ?></small></span><span class="chevron">⌄</span></summary>
                <form method="post" action="index.php" class="stack-form staff-edit">
                    <?= csrf_field() ?><input type="hidden" name="action" value="update_staff"><input type="hidden" name="staff_id" value="<?= (int) $person['id'] ?>">
                    <label for="name-<?= (int) $person['id'] ?>">Nama</label><input id="name-<?= (int) $person['id'] ?>" name="name" value="<?= e($person['name']) ?>" required maxlength="120">
                    <label for="email-<?= (int) $person['id'] ?>">Email</label><input id="email-<?= (int) $person['id'] ?>" name="email" type="email" value="<?= e($person['email']) ?>" required maxlength="190">
                    <label for="password-<?= (int) $person['id'] ?>">Kata sandi baru <span class="optional">(kosongkan jika tidak diubah)</span></label><input id="password-<?= (int) $person['id'] ?>" name="password" type="password" minlength="10" autocomplete="new-password">
                    <button class="button button-outline" type="submit">Simpan Perubahan</button>
                </form>
            </details>
        <?php endforeach; ?></div><?php else: ?><div class="empty-state"><span>♙</span><h3>Belum ada petugas</h3><p class="muted">Tambahkan petugas untuk mulai menugaskan pengaduan.</p></div><?php endif; ?>
    </section>
</div>

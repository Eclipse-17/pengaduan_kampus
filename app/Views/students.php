<?php $title = 'Data Mahasiswa'; ?>
<section class="page-heading"><p class="eyebrow">PENGELOLAAN PENGGUNA</p><h1>Data Mahasiswa</h1><p class="muted">Kelola akun mahasiswa yang menggunakan layanan pengaduan fasilitas.</p></section>
<div class="staff-layout">
    <section class="panel staff-create"><div class="panel-heading"><div><h2>Tambah Mahasiswa</h2><p class="muted">Buat akun untuk mahasiswa baru.</p></div></div>
        <form method="post" action="index.php" class="stack-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="create_student">
            <label for="student-name">Nama lengkap</label><input id="student-name" name="name" required maxlength="120" placeholder="Nama mahasiswa">
            <label for="student-email">Email</label><input id="student-email" name="email" type="email" required maxlength="190" placeholder="mahasiswa@kampus.ac.id">
            <label for="student-password">Kata sandi awal</label><input id="student-password" name="password" type="password" minlength="10" required autocomplete="new-password"><small>Minimal 10 karakter. Sampaikan secara aman kepada mahasiswa.</small>
            <button class="button button-primary button-full" type="submit">Tambah Mahasiswa <span>＋</span></button>
        </form>
    </section>
    <section class="panel"><div class="panel-heading"><div><h2>Daftar Mahasiswa</h2><p class="muted"><?= count($students) ?> akun terdaftar</p></div></div>
        <?php if ($students): ?><div class="staff-list"><?php foreach ($students as $student): ?>
            <details class="staff-item"><summary><span class="avatar"><?= e(mb_strtoupper(mb_substr($student['name'], 0, 1))) ?></span><span class="staff-summary"><strong><?= e($student['name']) ?></strong><small><?= e($student['email']) ?></small></span><span class="chevron">⌄</span></summary>
                <form method="post" action="index.php" class="stack-form staff-edit">
                    <?= csrf_field() ?><input type="hidden" name="action" value="update_student"><input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
                    <label for="student-name-<?= (int) $student['id'] ?>">Nama</label><input id="student-name-<?= (int) $student['id'] ?>" name="name" value="<?= e($student['name']) ?>" required maxlength="120">
                    <label for="student-email-<?= (int) $student['id'] ?>">Email</label><input id="student-email-<?= (int) $student['id'] ?>" name="email" type="email" value="<?= e($student['email']) ?>" required maxlength="190">
                    <label for="student-password-<?= (int) $student['id'] ?>">Kata sandi baru <span class="optional">(kosongkan jika tidak diubah)</span></label><input id="student-password-<?= (int) $student['id'] ?>" name="password" type="password" minlength="10" autocomplete="new-password">
                    <button class="button button-outline" type="submit">Simpan Perubahan</button>
                </form>
            </details>
        <?php endforeach; ?></div><?php else: ?><div class="empty-state"><span>♙</span><h3>Belum ada mahasiswa</h3><p class="muted">Tambahkan akun mahasiswa untuk mulai menggunakan layanan.</p></div><?php endif; ?>
    </section>
</div>

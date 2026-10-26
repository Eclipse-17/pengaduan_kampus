<?php $title = 'Detail Pengaduan #' . (int) $complaint['id']; ?>
<section class="page-heading">
    <a class="back-link" href="index.php?page=complaints">← Kembali ke daftar</a>
    <div class="heading-row detail-heading"><div><p class="eyebrow">DETAIL LAPORAN</p><h1>Pengaduan #<?= (int) $complaint['id'] ?></h1><p class="muted">Dikirim <?= e(date('d M Y, H:i', strtotime($complaint['created_at']))) ?></p></div><div class="detail-tools"><span class="badge badge-large badge-<?= e(status_class($complaint['status'])) ?>"><?= e($complaint['status']) ?></span><button class="button button-outline" type="button" onclick="window.print()">Cetak / PDF</button></div></div>
</section>
<div class="detail-grid">
    <section class="panel detail-panel">
        <div class="panel-heading"><div><h2>Informasi Pengaduan</h2><p class="muted">Data dan bukti kerusakan fasilitas</p></div></div>
        <dl class="detail-list">
            <div><dt>Kategori</dt><dd><?= e($complaint['category']) ?></dd></div>
            <div><dt>Lokasi</dt><dd><?= e($complaint['location']) ?></dd></div>
            <div><dt>Pelapor</dt><dd><?= e($complaint['reporter_name']) ?></dd></div>
            <div><dt>Email pelapor</dt><dd><?= e($complaint['reporter_email']) ?></dd></div>
            <div class="detail-description"><dt>Deskripsi kerusakan</dt><dd><?= nl2br(e($complaint['description'])) ?></dd></div>
            <div><dt>Petugas</dt><dd><?= e($complaint['staff_name'] ?? 'Belum ditugaskan') ?></dd></div>
            <div><dt>Waktu penugasan</dt><dd><?= $complaint['assigned_at'] ? e(date('d M Y, H:i', strtotime($complaint['assigned_at']))) : 'Belum ditugaskan' ?></dd></div>
            <div><dt>Prioritas</dt><dd><?= e($complaint['priority']) ?></dd></div>
            <div><dt>Tenggat penanganan</dt><dd><?= $complaint['due_at'] ? e(date('d M Y, H:i', strtotime($complaint['due_at']))) : 'Belum ditentukan' ?></dd></div>
            <div><dt>Waktu mulai</dt><dd><?= $complaint['started_at'] ? e(date('d M Y, H:i', strtotime($complaint['started_at']))) : 'Belum dimulai' ?></dd></div>
            <div><dt>Waktu selesai</dt><dd><?= $complaint['completed_at'] ? e(date('d M Y, H:i', strtotime($complaint['completed_at']))) : 'Belum selesai' ?></dd></div>
        </dl>
        <?php if ($complaint['blocked_reason']): ?><div class="alert alert-error"><strong>Tugas terhambat:</strong> <?= e($complaint['blocked_reason']) ?></div><?php endif; ?>
        <?php foreach (['evidence' => 'Foto bukti', 'repair' => 'Foto hasil perbaikan'] as $type => $label): ?>
            <?php $groupPhotos = array_values(array_filter($photos, static fn ($photo): bool => $photo['photo_type'] === $type)); ?>
            <?php if ($groupPhotos): ?><div class="photo-block"><h3><?= e($label) ?></h3><div class="photo-grid"><?php foreach ($groupPhotos as $photo): ?><a href="index.php?page=photo&id=<?= (int) $complaint['id'] ?>&photo=<?= (int) $photo['id'] ?>" target="_blank" rel="noopener"><img src="index.php?page=photo&id=<?= (int) $complaint['id'] ?>&photo=<?= (int) $photo['id'] ?>" alt="<?= e($label) ?> pengaduan #<?= (int) $complaint['id'] ?>"></a><?php endforeach; ?></div></div><?php endif; ?>
        <?php endforeach; ?>
    </section>
    <aside class="detail-side">
        <?php if ($user['role'] === 'admin' && !in_array($complaint['status'], ['Selesai', 'Dibatalkan'], true)): ?>
            <section class="panel action-panel"><h2>Tugaskan Petugas</h2><p class="muted">Pilih petugas fasilitas untuk menangani laporan ini.</p>
                <?php if ($staff): ?><form method="post" action="index.php" class="stack-form">
                    <?= csrf_field() ?><input type="hidden" name="action" value="assign"><input type="hidden" name="complaint_id" value="<?= (int) $complaint['id'] ?>">
                    <label for="staff-id">Petugas fasilitas</label><select id="staff-id" name="staff_id" required><option value="">Pilih petugas</option><?php foreach ($staff as $person): ?><option value="<?= (int) $person['id'] ?>" <?= (int) $complaint['staff_id'] === (int) $person['id'] ? 'selected' : '' ?>><?= e($person['name']) ?></option><?php endforeach; ?></select>
                    <label for="priority">Prioritas</label><select id="priority" name="priority" required><?php foreach (['Rendah', 'Normal', 'Tinggi'] as $priority): ?><option value="<?= e($priority) ?>" <?= $complaint['priority'] === $priority ? 'selected' : '' ?>><?= e($priority) ?></option><?php endforeach; ?></select>
                    <label for="due-at">Tenggat penanganan</label><input id="due-at" type="datetime-local" name="due_at" required value="<?= $complaint['due_at'] ? e(date('Y-m-d\TH:i', strtotime($complaint['due_at']))) : '' ?>">
                    <button class="button button-primary button-full" type="submit">Simpan Penugasan</button>
                </form><?php else: ?><p class="empty-inline">Belum ada akun petugas. Tambahkan melalui menu Data Petugas.</p><?php endif; ?>
            </section>
        <?php endif; ?>
        <?php if (!in_array($complaint['status'], ['Selesai', 'Dibatalkan'], true) && ($user['role'] === 'petugas' || ($user['role'] === 'admin' && $complaint['status'] === 'Menunggu'))): ?>
            <?php $canUpdate = $user['role'] === 'admin' || (int) $complaint['staff_id'] === (int) $user['id']; ?>
            <?php if ($canUpdate): ?><section class="panel action-panel"><h2>Perbarui Status</h2><p class="muted">Perubahan status akan tersimpan pada riwayat laporan.</p>
                <form method="post" action="index.php" class="stack-form">
                    <?= csrf_field() ?><input type="hidden" name="action" value="change_status"><input type="hidden" name="complaint_id" value="<?= (int) $complaint['id'] ?>">
                    <label for="new-status">Status berikutnya</label><select id="new-status" name="status" required><?php if ($complaint['status'] === 'Menunggu'): ?><option value="Diproses">Diproses</option><?php elseif ($user['role'] === 'petugas'): ?><option value="Selesai">Selesai</option><?php else: ?><option value="Diproses">Diproses</option><?php endif; ?></select>
                    <label for="status-note">Catatan <span class="optional">(opsional)</span></label><textarea id="status-note" name="note" rows="3" maxlength="500" placeholder="Catatan perkembangan penanganan"></textarea>
                    <button class="button button-primary button-full" type="submit">Simpan Status</button>
                </form>
            </section><?php endif; ?>
        <?php endif; ?>
        <?php if ($user['role'] === 'mahasiswa' && $complaint['status'] === 'Menunggu'): ?><section class="panel action-panel"><h2>Batalkan Pengaduan</h2><p class="muted">Pembatalan hanya tersedia sebelum laporan mulai diproses.</p><form method="post" action="index.php" onsubmit="return confirm('Batalkan pengaduan ini?')"><?= csrf_field() ?><input type="hidden" name="action" value="cancel_complaint"><input type="hidden" name="complaint_id" value="<?= (int) $complaint['id'] ?>"><button class="button button-outline" type="submit">Batalkan laporan</button></form></section><?php endif; ?>
        <?php if ($user['role'] === 'petugas' && $complaint['status'] === 'Diproses' && (int) $complaint['staff_id'] === (int) $user['id']): ?>
            <section class="panel action-panel"><h2>Hambatan Tugas</h2><p class="muted">Status tetap Diproses; kosongkan alasan untuk menghapus penanda hambatan.</p><form method="post" action="index.php" class="stack-form"><?= csrf_field() ?><input type="hidden" name="action" value="set_blocked"><input type="hidden" name="complaint_id" value="<?= (int) $complaint['id'] ?>"><label for="blocked-reason">Alasan hambatan</label><textarea id="blocked-reason" name="reason" maxlength="500" rows="3"><?= e($complaint['blocked_reason'] ?? '') ?></textarea><button class="button button-outline" type="submit"><?= $complaint['blocked_reason'] ? 'Perbarui / hapus hambatan' : 'Tandai terhambat' ?></button></form></section>
            <section class="panel action-panel"><h2>Foto Hasil Perbaikan</h2><form method="post" action="index.php" enctype="multipart/form-data" class="stack-form"><?= csrf_field() ?><input type="hidden" name="action" value="repair_photos"><input type="hidden" name="complaint_id" value="<?= (int) $complaint['id'] ?>"><label for="repair-photos">Pilih hingga 5 foto</label><input id="repair-photos" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple><small>JPG, PNG, WEBP; maksimal 5 MB per foto.</small><button class="button button-primary" type="submit">Unggah foto</button></form></section>
        <?php endif; ?>
        <?php if ($user['role'] === 'mahasiswa' && $complaint['status'] === 'Selesai'): ?>
            <section class="panel action-panel"><h2>Penilaian Penyelesaian</h2>
                <?php if ($rating): ?><p class="rating-stars" aria-label="Penilaian <?= (int) $rating['rating'] ?> dari 5"><?= str_repeat('★', (int) $rating['rating']) ?><?= str_repeat('☆', 5 - (int) $rating['rating']) ?></p><p><?= e($rating['feedback'] ?? 'Tidak ada catatan.') ?></p>
                <?php else: ?><form method="post" action="index.php" class="stack-form"><?= csrf_field() ?><input type="hidden" name="action" value="rate_complaint"><input type="hidden" name="complaint_id" value="<?= (int) $complaint['id'] ?>"><label for="rating">Nilai penanganan</label><select id="rating" name="rating" required><option value="">Pilih nilai</option><?php for ($score = 5; $score >= 1; $score--): ?><option value="<?= $score ?>"><?= $score ?> dari 5 bintang</option><?php endfor; ?></select><label for="feedback">Ulasan <span class="optional">(opsional)</span></label><textarea id="feedback" name="feedback" maxlength="500" rows="3"></textarea><button class="button button-primary" type="submit">Kirim penilaian</button></form><?php endif; ?>
            </section>
        <?php endif; ?>
        <section class="panel history-panel"><div class="panel-heading"><div><h2>Riwayat Status</h2><p class="muted">Catatan perubahan laporan</p></div></div>
            <ol class="timeline"><?php foreach ($history as $entry): ?><li><span class="timeline-dot"></span><div><strong><?= e($entry['new_status']) ?></strong><p><?= e($entry['note']) ?></p><small><?= e($entry['changed_by_name']) ?> · <?= e(date('d M Y, H:i', strtotime($entry['changed_at']))) ?></small></div></li><?php endforeach; ?></ol>
        </section>
    </aside>
</div>

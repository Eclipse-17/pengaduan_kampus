<?php $title = 'Detail Pengaduan #' . (int) $complaint['id']; ?>
<section class="page-heading">
    <a class="back-link" href="index.php?page=complaints">← Kembali ke daftar</a>
    <div class="heading-row detail-heading"><div><p class="eyebrow">DETAIL LAPORAN</p><h1>Pengaduan #<?= (int) $complaint['id'] ?></h1><p class="muted">Dikirim <?= e(date('d M Y, H:i', strtotime($complaint['created_at']))) ?></p></div><span class="badge badge-large badge-<?= e(status_class($complaint['status'])) ?>"><?= e($complaint['status']) ?></span></div>
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
        </dl>
        <?php if ($complaint['photo_path']): ?><div class="photo-block"><h3>Foto bukti</h3><a href="index.php?page=photo&id=<?= (int) $complaint['id'] ?>" target="_blank" rel="noopener"><img src="index.php?page=photo&id=<?= (int) $complaint['id'] ?>" alt="Foto bukti pengaduan #<?= (int) $complaint['id'] ?>"></a></div><?php endif; ?>
    </section>
    <aside class="detail-side">
        <?php if ($user['role'] === 'admin' && $complaint['status'] !== 'Selesai'): ?>
            <section class="panel action-panel"><h2>Tugaskan Petugas</h2><p class="muted">Pilih petugas fasilitas untuk menangani laporan ini.</p>
                <?php if ($staff): ?><form method="post" action="index.php" class="stack-form">
                    <?= csrf_field() ?><input type="hidden" name="action" value="assign"><input type="hidden" name="complaint_id" value="<?= (int) $complaint['id'] ?>">
                    <label for="staff-id">Petugas fasilitas</label><select id="staff-id" name="staff_id" required><option value="">Pilih petugas</option><?php foreach ($staff as $person): ?><option value="<?= (int) $person['id'] ?>" <?= (int) $complaint['staff_id'] === (int) $person['id'] ? 'selected' : '' ?>><?= e($person['name']) ?></option><?php endforeach; ?></select>
                    <button class="button button-primary button-full" type="submit">Simpan Penugasan</button>
                </form><?php else: ?><p class="empty-inline">Belum ada akun petugas. Tambahkan melalui menu Data Petugas.</p><?php endif; ?>
            </section>
        <?php endif; ?>
        <?php if ($complaint['status'] !== 'Selesai' && ($user['role'] === 'petugas' || ($user['role'] === 'admin' && $complaint['status'] === 'Menunggu'))): ?>
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
        <section class="panel history-panel"><div class="panel-heading"><div><h2>Riwayat Status</h2><p class="muted">Catatan perubahan laporan</p></div></div>
            <ol class="timeline"><?php foreach ($history as $entry): ?><li><span class="timeline-dot"></span><div><strong><?= e($entry['new_status']) ?></strong><p><?= e($entry['note']) ?></p><small><?= e($entry['changed_by_name']) ?> · <?= e(date('d M Y, H:i', strtotime($entry['changed_at']))) ?></small></div></li><?php endforeach; ?></ol>
        </section>
    </aside>
</div>

<?php
$title = 'Dashboard';
$label = match ($user['role']) {
    'admin' => 'Pantau dan kelola seluruh pengaduan fasilitas kampus.',
    'petugas' => 'Lihat tugas perbaikan dan perkembangan penanganan Anda.',
    default => 'Laporkan kerusakan fasilitas dan pantau penyelesaiannya.',
};
?>
<section class="page-heading heading-row">
    <div><p class="eyebrow">RINGKASAN</p><h1>Dashboard</h1><p class="muted"><?= e($label) ?></p></div>
    <?php if ($user['role'] === 'mahasiswa'): ?><a class="button button-primary" href="index.php?page=new"><span>＋</span> Buat Pengaduan</a><?php endif; ?>
</section>
<section class="stat-grid">
    <article class="stat-card"><span class="stat-icon icon-blue">▤</span><span class="stat-label">Total Pengaduan</span><strong><?= $stats['total'] ?></strong><small>Seluruh laporan Anda</small></article>
    <article class="stat-card"><span class="stat-icon icon-amber">◷</span><span class="stat-label">Menunggu</span><strong><?= $stats['waiting'] ?></strong><small>Belum ditindaklanjuti</small></article>
    <article class="stat-card"><span class="stat-icon icon-violet">↻</span><span class="stat-label">Diproses</span><strong><?= $stats['processing'] ?></strong><small>Sedang ditangani</small></article>
    <article class="stat-card"><span class="stat-icon icon-green">✓</span><span class="stat-label">Selesai</span><strong><?= $stats['done'] ?></strong><small>Sudah ditangani</small></article>
</section>
<section class="panel">
    <div class="panel-heading"><div><h2><?= $user['role'] === 'petugas' ? 'Tugas Terbaru' : 'Pengaduan Terbaru' ?></h2><p class="muted">Pembaruan terbaru dari sistem</p></div><a class="text-link" href="index.php?page=complaints">Lihat semua <span>→</span></a></div>
    <?php if ($complaints): ?>
        <div class="table-wrap"><table><thead><tr><th>No. Laporan</th><th>Kategori</th><?php if ($user['role'] !== 'mahasiswa'): ?><th>Pelapor</th><?php endif; ?><th>Lokasi</th><th>Tanggal</th><th>Status</th><th></th></tr></thead><tbody>
        <?php foreach ($complaints as $item): ?><tr>
            <td class="report-id">#<?= (int) $item['id'] ?></td><td><?= e($item['category']) ?></td>
            <?php if ($user['role'] !== 'mahasiswa'): ?><td><?= e($item['reporter_name']) ?></td><?php endif; ?>
            <td><?= e($item['location']) ?></td><td><?= e(date('d M Y', strtotime($item['created_at']))) ?></td>
            <td><span class="badge badge-<?= e(status_class($item['status'])) ?>"><?= e($item['status']) ?></span></td>
            <td><a class="table-link" href="index.php?page=detail&id=<?= (int) $item['id'] ?>">Detail →</a></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
    <?php else: ?><div class="empty-state"><span>▤</span><h3>Belum ada pengaduan</h3><p class="muted">Laporan yang dibuat atau ditugaskan akan tampil di sini.</p></div><?php endif; ?>
</section>

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
<section class="welcome-hero">
    <div><p class="eyebrow"><?= $user['role'] === 'mahasiswa' ? 'PORTAL FASILITAS KAMPUS' : ($user['role'] === 'petugas' ? 'WORKSPACE PETUGAS' : 'PUSAT KENDALI ADMIN') ?></p><h2><?= $user['role'] === 'mahasiswa' ? 'Halo, ' . e($user['name']) : ($user['role'] === 'petugas' ? 'Selamat bertugas, ' . e($user['name']) : 'Ringkasan operasional kampus') ?></h2><p><?= e($label) ?></p></div>
    <span class="hero-mark"><?= $user['role'] === 'mahasiswa' ? '✦' : ($user['role'] === 'petugas' ? '⚙' : '▦') ?></span>
</section>
<section class="stat-grid">
    <article class="stat-card"><span class="stat-icon icon-blue">▤</span><span class="stat-label"><?= $user['role'] === 'petugas' ? 'Total Tugas' : 'Total Pengaduan' ?></span><strong><?= $stats['total'] ?></strong><small><?= $user['role'] === 'admin' ? 'Seluruh laporan kampus' : 'Dalam akun Anda' ?></small></article>
    <article class="stat-card"><span class="stat-icon icon-amber">◷</span><span class="stat-label">Menunggu</span><strong><?= $stats['waiting'] ?></strong><small>Belum ditindaklanjuti</small></article>
    <article class="stat-card"><span class="stat-icon icon-violet">↻</span><span class="stat-label">Diproses</span><strong><?= $stats['processing'] ?></strong><small>Sedang ditangani</small></article>
    <article class="stat-card"><span class="stat-icon icon-green">✓</span><span class="stat-label">Selesai</span><strong><?= $stats['done'] ?></strong><small>Sudah ditangani</small></article>
    <?php if ($user['role'] === 'mahasiswa'): ?><article class="stat-card"><span class="stat-icon icon-red">×</span><span class="stat-label">Dibatalkan</span><strong><?= $stats['cancelled'] ?></strong><small>Tersimpan di riwayat</small></article><?php endif; ?>
</section>
<?php if ($user['role'] === 'petugas'): ?><section class="quick-actions"><a class="quick-action" href="index.php?page=complaints"><span>▤</span><strong>Buka daftar tugas</strong><small>Kelola dan perbarui pekerjaan</small></a><a class="quick-action" href="index.php?page=calendar"><span>▦</span><strong>Lihat kalender</strong><small>Periksa tenggat penanganan</small></a></section><?php endif; ?>
<?php if ($user['role'] === 'admin'): ?><section class="quick-actions"><a class="quick-action" href="index.php?page=complaints"><span>▤</span><strong>Semua pengaduan</strong><small>Tinjau laporan dan penugasan</small></a><a class="quick-action" href="index.php?page=performance"><span>↗</span><strong>Kinerja petugas</strong><small>Bandingkan penyelesaian tugas</small></a><a class="quick-action" href="index.php?page=categories"><span>◇</span><strong>Kelola kategori</strong><small>Atur kategori fasilitas</small></a><a class="quick-action" href="index.php?page=students"><span>♙</span><strong>Data mahasiswa</strong><small>Kelola akun mahasiswa</small></a></section><?php endif; ?>
<section class="panel trend-panel"><div class="panel-heading"><div><h2><?= $user['role'] === 'admin' ? 'Tren laporan kampus' : ($user['role'] === 'petugas' ? 'Tren tugas masuk' : 'Aktivitas laporan Anda') ?></h2><p class="muted">Jumlah laporan per hari · 14 hari terakhir</p></div></div>
    <?php $maxTrend = max(array_map(static fn ($point): int => (int) $point['total'], $trend ?: [['total' => 0]])); ?>
    <?php if ($trend): ?><div class="trend-bars"><?php foreach ($trend as $point): ?><div class="trend-bar-item"><span><?= (int) $point['total'] ?></span><i style="height: <?= max(5, (int) round(((int) $point['total'] / max(1, $maxTrend)) * 120)) ?>px"></i><small><?= e(date('d/m', strtotime($point['day']))) ?></small></div><?php endforeach; ?></div><?php else: ?><p class="muted">Belum ada data laporan pada periode ini.</p><?php endif; ?>
</section>
<section class="panel">
    <div class="panel-heading"><div><h2><?= $user['role'] === 'petugas' ? 'Tugas Terbaru' : 'Pengaduan Terbaru' ?></h2><p class="muted">Pembaruan terbaru dari sistem</p></div><a class="text-link" href="index.php?page=complaints">Lihat semua <span>→</span></a></div>
    <?php if ($complaints): ?>
        <div class="table-wrap"><table><thead><tr><th>No. Laporan</th><th>Kategori</th><?php if ($user['role'] !== 'mahasiswa'): ?><th>Pelapor</th><?php endif; ?><th>Lokasi</th><th>Prioritas</th><th>Tenggat</th><th>Tanggal</th><th>Status</th><th></th></tr></thead><tbody>
        <?php foreach ($complaints as $item): ?><tr>
            <td class="report-id">#<?= (int) $item['id'] ?></td><td><?= e($item['category']) ?></td>
            <?php if ($user['role'] !== 'mahasiswa'): ?><td><?= e($item['reporter_name']) ?></td><?php endif; ?>
            <td><?= e($item['location']) ?></td><td><?= e($item['priority']) ?></td><td><?= $item['due_at'] ? e(date('d M Y', strtotime($item['due_at']))) : '—' ?></td><td><?= e(date('d M Y', strtotime($item['created_at']))) ?></td>
            <td><span class="badge badge-<?= e(status_class($item['status'])) ?>"><?= e($item['status']) ?></span></td>
            <td><a class="table-link" href="index.php?page=detail&id=<?= (int) $item['id'] ?>">Detail →</a></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
    <?php else: ?><div class="empty-state"><span>▤</span><h3>Belum ada pengaduan</h3><p class="muted">Laporan yang dibuat atau ditugaskan akan tampil di sini.</p></div><?php endif; ?>
</section>

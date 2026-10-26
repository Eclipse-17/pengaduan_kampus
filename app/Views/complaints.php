<?php
$title = $user['role'] === 'petugas' ? 'Tugas Saya' : 'Daftar Pengaduan';
$heading = $user['role'] === 'petugas' ? 'Tugas Saya' : ($user['role'] === 'admin' ? 'Semua Pengaduan' : 'Pengaduan Saya');
?>
<section class="page-heading heading-row">
    <div><p class="eyebrow">PENGELOLAAN LAPORAN</p><h1><?= e($heading) ?></h1><p class="muted">Lihat detail dan perkembangan setiap laporan fasilitas.</p></div>
    <?php if ($user['role'] === 'admin'): ?><div class="heading-actions"><a class="button button-outline" href="index.php?page=export">Ekspor CSV</a><a class="button button-primary" href="index.php?page=report">Cetak rekap</a></div><?php endif; ?>
    <?php if ($user['role'] === 'mahasiswa'): ?><a class="button button-primary" href="index.php?page=new"><span>＋</span> Buat Pengaduan</a><?php endif; ?>
</section>
<section class="panel">
    <form method="get" class="filter-bar">
        <input type="hidden" name="page" value="complaints">
        <label class="search-label" for="search">Cari</label><input class="search-input" id="search" name="q" value="<?= e($search) ?>" maxlength="100" placeholder="Nomor, kategori, lokasi, pelapor">
        <label for="status-filter">Filter status</label>
        <select id="status-filter" name="status" onchange="this.form.submit()">
            <option value="">Semua status</option>
            <?php foreach (['Menunggu', 'Diproses', 'Selesai', 'Dibatalkan'] as $status): ?><option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?>
        </select>
        <label for="category-filter">Kategori</label>
        <select id="category-filter" name="category" onchange="this.form.submit()"><option value="">Semua kategori</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) $categoryFilter === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select>
        <noscript><button class="button button-outline" type="submit">Terapkan</button></noscript>
        <span class="filter-count"><?= count($complaints) ?> laporan</span>
    </form>
    <?php if ($complaints): ?>
        <div class="table-wrap"><table><thead><tr><th>No. Laporan</th><th>Kategori</th><?php if ($user['role'] !== 'mahasiswa'): ?><th>Pelapor</th><?php endif; ?><th>Lokasi</th><th>Petugas</th><th>Prioritas</th><th>Tenggat</th><th>Tanggal</th><th>Status</th><th></th></tr></thead><tbody>
        <?php foreach ($complaints as $item): ?><tr>
            <td class="report-id">#<?= (int) $item['id'] ?></td><td><?= e($item['category']) ?></td>
            <?php if ($user['role'] !== 'mahasiswa'): ?><td><?= e($item['reporter_name']) ?></td><?php endif; ?>
            <td><?= e($item['location']) ?><?= $item['blocked_reason'] ? '<br><span class="blocked-label">Terhambat</span>' : '' ?></td><td><?= e($item['staff_name'] ?? 'Belum ditugaskan') ?></td>
            <td><span class="priority priority-<?= e(mb_strtolower($item['priority'])) ?>"><?= e($item['priority']) ?></span></td><td><?= $item['due_at'] ? e(date('d M Y', strtotime($item['due_at']))) : '—' ?></td>
            <td><?= e(date('d M Y', strtotime($item['created_at']))) ?></td>
            <td><span class="badge badge-<?= e(status_class($item['status'])) ?>"><?= e($item['status']) ?></span></td>
            <td><a class="table-link" href="index.php?page=detail&id=<?= (int) $item['id'] ?>">Detail →</a></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
    <?php else: ?><div class="empty-state"><span>⌕</span><h3>Tidak ada laporan ditemukan</h3><p class="muted">Coba pilih filter status lain atau buat pengaduan baru.</p></div><?php endif; ?>
</section>

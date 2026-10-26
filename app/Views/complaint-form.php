<?php $title = 'Buat Pengaduan'; ?>
<section class="page-heading">
    <p class="eyebrow">LAPORKAN MASALAH FASILITAS</p><h1>Buat Pengaduan</h1>
    <p class="muted">Jelaskan kendala yang ditemukan agar petugas dapat menanganinya dengan tepat.</p>
</section>
<form class="panel form-panel" method="post" action="index.php" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="action" value="create_complaint">
    <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
    <div class="form-section"><div class="section-number">01</div><div><h2>Informasi fasilitas</h2><p class="muted">Berikan lokasi dan kategori fasilitas yang bermasalah.</p></div></div>
    <div class="form-grid">
        <div class="field"><label for="category">Kategori fasilitas <span class="required">*</span></label>
            <select id="category" name="category_id" required><option value="">Pilih kategori</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option><?php endforeach; ?></select>
        </div>
        <div class="field"><label for="location">Lokasi fasilitas <span class="required">*</span></label><input id="location" name="location" maxlength="180" required placeholder="Contoh: Gedung A, lantai 2, ruang 204"></div>
    </div>
    <div class="field"><label for="description">Deskripsi kerusakan <span class="required">*</span></label><textarea id="description" name="description" rows="5" maxlength="3000" required placeholder="Jelaskan kondisi fasilitas, kapan masalah terjadi, dan informasi lain yang membantu petugas."></textarea><small>Maksimal 3.000 karakter.</small></div>
    <div class="field"><label for="photo">Foto bukti <span class="optional">(opsional)</span></label><input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"><small>Format JPG, PNG, WEBP. Ukuran maksimal 5 MB.</small></div>
    <div class="form-actions"><a class="button button-outline" href="index.php?page=complaints">Batal</a><button class="button button-primary" type="submit">Kirim Pengaduan <span>→</span></button></div>
</form>

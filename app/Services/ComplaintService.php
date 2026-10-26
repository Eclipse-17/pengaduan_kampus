<?php
declare(strict_types=1);

final class ComplaintService
{
    private const STATUSES = ['Menunggu', 'Diproses', 'Selesai', 'Dibatalkan'];
    private const PRIORITIES = ['Rendah', 'Normal', 'Tinggi'];
    private const MAX_PHOTOS = 5;
    private const MAX_PHOTO_BYTES = 5_242_880;
    private const PHOTO_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private ComplaintRepository $complaints,
        private UserRepository $users
    ) {
    }

    public function create(array $user, array $input, array $file): int
    {
        if ($user['role'] !== 'mahasiswa') {
            throw new RuntimeException('Hanya mahasiswa yang dapat membuat pengaduan.');
        }
        $categoryId = filter_var($input['category_id'] ?? null, FILTER_VALIDATE_INT);
        $location = trim((string) ($input['location'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        if (!$categoryId || $location === '' || $description === '') {
            throw new RuntimeException('Kategori, lokasi, dan deskripsi wajib diisi.');
        }
        $categories = array_column($this->complaints->activeCategories(), 'id');
        if (!in_array((string) $categoryId, array_map('strval', $categories), true)) {
            throw new RuntimeException('Kategori fasilitas tidak valid.');
        }
        if (mb_strlen($location) > 180 || mb_strlen($description) > 3000) {
            throw new RuntimeException('Lokasi maksimal 180 karakter dan deskripsi maksimal 3.000 karakter.');
        }

        $photos = $this->storePhotos($file);
        try {
            return $this->complaints->create((int) $user['id'], $categoryId, $location, $description, $photos);
        } catch (Throwable $error) {
            $this->removePhotos($photos);
            throw $error;
        }
    }

    private function storePhotos(array $files): array
    {
        if (!isset($files['name'])) {
            return [];
        }
        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $temporaryNames = is_array($files['tmp_name'] ?? null) ? $files['tmp_name'] : [$files['tmp_name'] ?? ''];
        $sizes = is_array($files['size'] ?? null) ? $files['size'] : [$files['size'] ?? 0];
        $errors = is_array($files['error'] ?? null) ? $files['error'] : [$files['error'] ?? UPLOAD_ERR_NO_FILE];
        $selected = [];
        foreach ($names as $index => $name) {
            if (($errors[$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $selected[] = [$temporaryNames[$index] ?? '', (int) ($sizes[$index] ?? 0), $errors[$index] ?? UPLOAD_ERR_OK];
            }
        }
        if (count($selected) > self::MAX_PHOTOS) {
            throw new RuntimeException('Maksimal 5 foto dapat diunggah dalam satu tahap.');
        }
        $stored = [];
        try {
            foreach ($selected as [$temporaryName, $size, $errorCode]) {
                if ($errorCode !== UPLOAD_ERR_OK || !is_uploaded_file($temporaryName)) {
                    throw new RuntimeException('Foto gagal diunggah. Pilih ulang file foto.');
                }
                if ($size > self::MAX_PHOTO_BYTES) {
                    throw new RuntimeException('Ukuran setiap foto maksimal 5 MB.');
                }
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryName);
                if (!isset(self::PHOTO_MIMES[$mime])) {
                    throw new RuntimeException('Format foto harus JPG, PNG, atau WEBP.');
                }
                $directory = APP_ROOT . '/storage/uploads';
                if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
                    throw new RuntimeException('Direktori penyimpanan foto tidak dapat dibuat.');
                }
                $filename = bin2hex(random_bytes(16)) . '.' . self::PHOTO_MIMES[$mime];
                if (!move_uploaded_file($temporaryName, $directory . '/' . $filename)) {
                    throw new RuntimeException('Foto tidak dapat disimpan.');
                }
                $stored[] = $filename;
            }
        } catch (Throwable $error) {
            $this->removePhotos($stored);
            throw $error;
        }
        return $stored;
    }

    private function removePhotos(array $photos): void
    {
        foreach ($photos as $photo) {
            $path = APP_ROOT . '/storage/uploads/' . basename($photo);
            if (is_file($path) && !unlink($path)) {
                error_log('Unable to remove orphan complaint photo: ' . $path);
            }
        }
    }

    public function visibleComplaint(int $id, array $user): array
    {
        $complaint = $this->complaints->find($id);
        if ($complaint === null) {
            throw new RuntimeException('Pengaduan tidak ditemukan.');
        }
        if ($user['role'] === 'mahasiswa' && (int) $complaint['reporter_id'] !== (int) $user['id']) {
            throw new RuntimeException('Pengaduan ini bukan milik Anda.');
        }
        if ($user['role'] === 'petugas' && !$this->complaints->hasAssignment($id, (int) $user['id'])) {
            throw new RuntimeException('Pengaduan ini tidak ditugaskan kepada Anda.');
        }
        return $complaint;
    }

    public function assign(int $complaintId, int $staffId, array $input, array $admin): void
    {
        if ($admin['role'] !== 'admin') {
            throw new RuntimeException('Hanya admin yang dapat menugaskan petugas.');
        }
        $complaint = $this->complaints->find($complaintId);
        $staff = $this->users->findById($staffId);
        if ($complaint === null || in_array($complaint['status'], ['Selesai', 'Dibatalkan'], true)) {
            throw new RuntimeException('Pengaduan tidak ditemukan atau sudah selesai.');
        }
        if ($staff === null || $staff['role'] !== 'petugas') {
            throw new RuntimeException('Petugas yang dipilih tidak valid.');
        }
        $priority = (string) ($input['priority'] ?? 'Normal');
        $dueAt = $this->normalizeDueAt($input['due_at'] ?? '');
        if (!in_array($priority, self::PRIORITIES, true)) {
            throw new RuntimeException('Prioritas pengaduan tidak valid.');
        }
        if ($dueAt === null) {
            throw new RuntimeException('Tanggal tenggat wajib ditentukan saat menugaskan petugas.');
        }
        $this->complaints->assign($complaintId, $staffId, (int) $admin['id'], $priority, $dueAt);
    }

    private function normalizeDueAt(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d\TH:i') !== $value) {
            throw new RuntimeException('Tanggal tenggat tidak valid.');
        }
        return $date->format('Y-m-d H:i:s');
    }

    public function changeStatus(int $complaintId, string $newStatus, string $note, array $user): void
    {
        if (!in_array($newStatus, self::STATUSES, true)) {
            throw new RuntimeException('Status pengaduan tidak valid.');
        }
        $complaint = $this->complaints->find($complaintId);
        if ($complaint === null) {
            throw new RuntimeException('Pengaduan tidak ditemukan.');
        }
        $oldStatus = $complaint['status'];
        if ($oldStatus === 'Selesai') {
            throw new RuntimeException('Pengaduan yang sudah selesai tidak dapat diubah kembali.');
        }
        if (in_array($oldStatus, ['Dibatalkan'], true)) {
            throw new RuntimeException('Pengaduan yang sudah dibatalkan tidak dapat diubah kembali.');
        }
        if ($newStatus === $oldStatus || ($oldStatus === 'Menunggu' && $newStatus !== 'Diproses')) {
            throw new RuntimeException('Perubahan status tidak mengikuti alur yang diizinkan.');
        }
        if ($newStatus === 'Selesai' && $user['role'] !== 'petugas') {
            throw new RuntimeException('Hanya petugas yang ditugaskan yang dapat mengonfirmasi penyelesaian.');
        }
        if ($user['role'] === 'petugas' && !$this->complaints->hasAssignment($complaintId, (int) $user['id'])) {
            throw new RuntimeException('Anda tidak ditugaskan pada pengaduan ini.');
        }
        if (!in_array($user['role'], ['admin', 'petugas'], true)) {
            throw new RuntimeException('Anda tidak berwenang mengubah status.');
        }
        if ($newStatus === 'Selesai' && $oldStatus !== 'Diproses') {
            throw new RuntimeException('Pengaduan harus berstatus Diproses sebelum diselesaikan.');
        }
        if (mb_strlen(trim($note)) > 500) {
            throw new RuntimeException('Catatan maksimal 500 karakter.');
        }
        $this->complaints->updateStatus(
            $complaintId,
            $newStatus,
            (int) $user['id'],
            (string) $user['role'],
            trim($note) ?: 'Status diperbarui'
        );
    }

    public function cancel(int $complaintId, array $user): void
    {
        if ($user['role'] !== 'mahasiswa') {
            throw new RuntimeException('Hanya mahasiswa yang dapat membatalkan pengaduan.');
        }
        $complaint = $this->visibleComplaint($complaintId, $user);
        if ($complaint['status'] !== 'Menunggu') {
            throw new RuntimeException('Pengaduan hanya dapat dibatalkan sebelum mulai diproses.');
        }
        $this->complaints->updateStatus(
            $complaintId,
            'Dibatalkan',
            (int) $user['id'],
            'mahasiswa',
            'Pengaduan dibatalkan oleh pelapor'
        );
    }

    public function setBlocked(int $complaintId, string $reason, array $user): void
    {
        if ($user['role'] !== 'petugas') {
            throw new RuntimeException('Hanya petugas yang ditugaskan dapat memperbarui hambatan tugas.');
        }
        $complaint = $this->visibleComplaint($complaintId, $user);
        if ($complaint['status'] !== 'Diproses') {
            throw new RuntimeException('Tugas hanya dapat ditandai terhambat saat berstatus Diproses.');
        }
        $reason = trim($reason);
        if (mb_strlen($reason) > 500) {
            throw new RuntimeException('Alasan hambatan maksimal 500 karakter.');
        }
        $this->complaints->setBlocked($complaintId, (int) $user['id'], $reason !== '' ? $reason : null);
    }

    public function addRepairPhotos(int $complaintId, array $files, array $user): void
    {
        if ($user['role'] !== 'petugas') {
            throw new RuntimeException('Hanya petugas yang ditugaskan dapat mengunggah foto hasil perbaikan.');
        }
        $complaint = $this->visibleComplaint($complaintId, $user);
        if ($complaint['status'] !== 'Diproses') {
            throw new RuntimeException('Foto hasil perbaikan hanya dapat diunggah saat tugas berstatus Diproses.');
        }
        $existingPhotos = array_filter(
            $this->complaints->photos($complaintId),
            static fn (array $photo): bool => $photo['photo_type'] === 'repair'
        );
        if (count($existingPhotos) >= self::MAX_PHOTOS) {
            throw new RuntimeException('Maksimal 5 foto hasil perbaikan dapat disimpan per tugas.');
        }
        $photos = $this->storePhotos($files);
        if ($photos === []) {
            throw new RuntimeException('Pilih setidaknya satu foto hasil perbaikan.');
        }
        if (count($existingPhotos) + count($photos) > self::MAX_PHOTOS) {
            throw new RuntimeException('Total foto hasil perbaikan maksimal 5 per tugas.');
        }
        try {
            $this->complaints->addRepairPhotos($complaintId, (int) $user['id'], $photos);
        } catch (Throwable $error) {
            $this->removePhotos($photos);
            throw $error;
        }
    }

    public function rate(int $complaintId, int $rating, string $feedback, array $user): void
    {
        if ($user['role'] !== 'mahasiswa') {
            throw new RuntimeException('Hanya mahasiswa yang dapat menilai pengaduan.');
        }
        $complaint = $this->visibleComplaint($complaintId, $user);
        if ($complaint['status'] !== 'Selesai' || $this->complaints->rating($complaintId) !== null) {
            throw new RuntimeException('Pengaduan belum dapat dinilai atau sudah pernah dinilai.');
        }
        if ($rating < 1 || $rating > 5 || mb_strlen(trim($feedback)) > 500) {
            throw new RuntimeException('Penilaian harus 1–5 dan ulasan maksimal 500 karakter.');
        }
        $this->complaints->saveRating($complaintId, (int) $user['id'], $rating, trim($feedback));
    }
}

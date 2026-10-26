<?php
declare(strict_types=1);

final class ComplaintService
{
    private const STATUSES = ['Menunggu', 'Diproses', 'Selesai'];
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
        $categories = array_column($this->complaints->categories(), 'id');
        if (!in_array((string) $categoryId, array_map('strval', $categories), true)) {
            throw new RuntimeException('Kategori fasilitas tidak valid.');
        }
        if (mb_strlen($location) > 180 || mb_strlen($description) > 3000) {
            throw new RuntimeException('Lokasi maksimal 180 karakter dan deskripsi maksimal 3.000 karakter.');
        }

        $photoPath = $this->storePhoto($file);
        try {
            return $this->complaints->create((int) $user['id'], $categoryId, $location, $description, $photoPath);
        } catch (Throwable $error) {
            if ($photoPath !== null) {
                $path = APP_ROOT . '/storage/uploads/' . $photoPath;
                if (is_file($path) && !unlink($path)) {
                    error_log('Unable to remove orphan complaint photo: ' . $path);
                }
            }
            throw $error;
        }
    }

    private function storePhoto(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new RuntimeException('Foto gagal diunggah. Pilih ulang file foto.');
        }
        if (($file['size'] ?? 0) > self::MAX_PHOTO_BYTES) {
            throw new RuntimeException('Ukuran foto maksimal 5 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::PHOTO_MIMES[$mime])) {
            throw new RuntimeException('Format foto harus JPG, PNG, atau WEBP.');
        }
        $directory = APP_ROOT . '/storage/uploads';
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Direktori penyimpanan foto tidak dapat dibuat.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . self::PHOTO_MIMES[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
            throw new RuntimeException('Foto tidak dapat disimpan.');
        }
        return $filename;
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

    public function assign(int $complaintId, int $staffId, array $admin): void
    {
        if ($admin['role'] !== 'admin') {
            throw new RuntimeException('Hanya admin yang dapat menugaskan petugas.');
        }
        $complaint = $this->complaints->find($complaintId);
        $staff = $this->users->findById($staffId);
        if ($complaint === null || $complaint['status'] === 'Selesai') {
            throw new RuntimeException('Pengaduan tidak ditemukan atau sudah selesai.');
        }
        if ($staff === null || $staff['role'] !== 'petugas') {
            throw new RuntimeException('Petugas yang dipilih tidak valid.');
        }
        $this->complaints->assign($complaintId, $staffId, (int) $admin['id']);
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
            trim($note) ?: 'Status diperbarui'
        );
    }
}

<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

set_exception_handler(static function (Throwable $error): void {
    error_log((string) $error);
    $statusCode = http_response_code();
    if ($statusCode === false || $statusCode < 400) {
        $statusCode = $error instanceof RuntimeException ? 400 : 500;
    }
    http_response_code($statusCode);
    $message = 'Terjadi kesalahan pada aplikasi. Silakan periksa konfigurasi dan log server.';
    if ($error instanceof RuntimeException) {
        $message = $error->getMessage();
    }
    $title = 'Terjadi kesalahan';
    require APP_ROOT . '/app/Views/error.php';
});

$page = (string) ($_GET['page'] ?? 'dashboard');
$currentUser = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'login') {
            $user = auth_service()->authenticate(
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['password'] ?? '')
            );
            Auth::login($user);
            flash('success', 'Selamat datang, ' . $user['name'] . '.');
            redirect('dashboard');
        }

        if ($action === 'logout') {
            Auth::requireLogin();
            Auth::logout();
            session_start();
            flash('success', 'Anda telah keluar dari aplikasi.');
            redirect('login');
        }

        $currentUser = Auth::requireLogin();
        if ($action === 'create_complaint') {
            $id = complaint_service()->create($currentUser, $_POST, $_FILES['photo'] ?? []);
            flash('success', 'Pengaduan berhasil dikirim dengan nomor #' . $id . '.');
            redirect('detail', ['id' => $id]);
        }
        if ($action === 'assign') {
            complaint_service()->assign((int) ($_POST['complaint_id'] ?? 0), (int) ($_POST['staff_id'] ?? 0), $currentUser);
            flash('success', 'Petugas berhasil ditugaskan.');
            redirect('detail', ['id' => (int) $_POST['complaint_id']]);
        }
        if ($action === 'change_status') {
            complaint_service()->changeStatus(
                (int) ($_POST['complaint_id'] ?? 0),
                (string) ($_POST['status'] ?? ''),
                (string) ($_POST['note'] ?? ''),
                $currentUser
            );
            flash('success', 'Status pengaduan berhasil diperbarui.');
            redirect('detail', ['id' => (int) $_POST['complaint_id']]);
        }
        if ($action === 'create_staff') {
            Auth::requireRole(['admin']);
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
                throw new RuntimeException('Nama, email valid, dan kata sandi minimal 10 karakter wajib diisi.');
            }
            user_repository()->create($name, $email, $password, 'petugas');
            flash('success', 'Akun petugas berhasil ditambahkan.');
            redirect('staff');
        }
        if ($action === 'update_staff') {
            Auth::requireRole(['admin']);
            $id = filter_var($_POST['staff_id'] ?? null, FILTER_VALIDATE_INT);
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = trim((string) ($_POST['password'] ?? ''));
            if (!$id || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
                || ($password !== '' && strlen($password) < 10)) {
                throw new RuntimeException('Periksa kembali data petugas. Kata sandi baru minimal 10 karakter.');
            }
            user_repository()->updateStaff($id, $name, $email, $password === '' ? null : $password);
            flash('success', 'Data petugas berhasil diperbarui.');
            redirect('staff');
        }
        throw new RuntimeException('Aksi tidak dikenal.');
    } catch (RuntimeException $error) {
        flash('error', $error->getMessage());
        $fallback = $action === 'login' ? 'login' : (Auth::user() === null ? 'login' : $page);
        if ($action === 'create_complaint') {
            $fallback = 'new';
        } elseif ($action === 'create_staff' || $action === 'update_staff') {
            $fallback = 'staff';
        }
        $query = isset($_POST['complaint_id']) ? ['id' => (int) $_POST['complaint_id']] : [];
        if ($query !== []) {
            $fallback = 'detail';
        }
        redirect($fallback, $query);
    }
}

if ($page === 'photo') {
    $user = Auth::requireLogin();
    $complaint = complaint_service()->visibleComplaint((int) ($_GET['id'] ?? 0), $user);
    $filename = $complaint['photo_path'];
    $path = APP_ROOT . '/storage/uploads/' . basename((string) $filename);
    if (!$filename || !is_file($path)) {
        http_response_code(404);
        exit('Foto bukti tidak ditemukan.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        http_response_code(415);
        exit('Format foto tidak didukung.');
    }
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=300');
    readfile($path);
    exit;
}

$view = null;
$data = [];

if ($page === 'login') {
    if ($currentUser !== null) {
        redirect('dashboard');
    }
    $view = 'login.php';
} elseif ($page === 'dashboard') {
    $user = Auth::requireLogin();
    $stats = complaint_repository()->statistics((int) $user['id'], $user['role']);
    $rows = match ($user['role']) {
        'mahasiswa' => complaint_repository()->listForStudent((int) $user['id']),
        'petugas' => complaint_repository()->listAssigned((int) $user['id']),
        default => complaint_repository()->listAll(),
    };
    $data = ['user' => $user, 'stats' => $stats, 'complaints' => array_slice($rows, 0, 6)];
    $view = 'dashboard.php';
} elseif ($page === 'complaints') {
    $user = Auth::requireLogin();
    $status = (string) ($_GET['status'] ?? '');
    $search = trim((string) ($_GET['q'] ?? ''));
    $categoryId = ($_GET['category'] ?? '') === '' ? null : filter_var($_GET['category'], FILTER_VALIDATE_INT);
    if ($status !== '' && !in_array($status, ['Menunggu', 'Diproses', 'Selesai'], true)) {
        throw new RuntimeException('Filter status tidak valid.');
    }
    if (mb_strlen($search) > 100 || (($_GET['category'] ?? '') !== '' && !$categoryId)) {
        throw new RuntimeException('Kata kunci atau filter kategori tidak valid.');
    }
    $categories = complaint_repository()->categories();
    if ($categoryId !== null && !in_array((int) $categoryId, array_map(static fn ($row): int => (int) $row['id'], $categories), true)) {
        throw new RuntimeException('Filter kategori tidak valid.');
    }
    $rows = match ($user['role']) {
        'mahasiswa' => complaint_repository()->listForStudent((int) $user['id'], $status, $search, $categoryId),
        'petugas' => complaint_repository()->listAssigned((int) $user['id'], $status, $search, $categoryId),
        default => complaint_repository()->listAll($status, $search, $categoryId),
    };
    $data = [
        'user' => $user, 'complaints' => $rows, 'statusFilter' => $status,
        'search' => $search, 'categoryFilter' => $categoryId, 'categories' => $categories,
    ];
    $view = 'complaints.php';
} elseif ($page === 'new') {
    $user = Auth::requireRole(['mahasiswa']);
    $data = ['user' => $user, 'categories' => complaint_repository()->categories()];
    $view = 'complaint-form.php';
} elseif ($page === 'detail') {
    $user = Auth::requireLogin();
    $complaint = complaint_service()->visibleComplaint((int) ($_GET['id'] ?? 0), $user);
    $data = [
        'user' => $user,
        'complaint' => $complaint,
        'history' => complaint_repository()->history((int) $complaint['id']),
        'staff' => $user['role'] === 'admin' ? user_repository()->staff() : [],
    ];
    $view = 'complaint-detail.php';
} elseif ($page === 'staff') {
    $user = Auth::requireRole(['admin']);
    $data = ['user' => $user, 'staff' => user_repository()->staff()];
    $view = 'staff.php';
} else {
    http_response_code(404);
    throw new RuntimeException('Halaman yang diminta tidak ditemukan.');
}

extract($data, EXTR_SKIP);
$content = APP_ROOT . '/app/Views/' . $view;
require APP_ROOT . '/app/Views/layout.php';

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
            $id = complaint_service()->create($currentUser, $_POST, $_FILES['photos'] ?? []);
            flash('success', 'Pengaduan berhasil dikirim dengan nomor #' . $id . '.');
            redirect('detail', ['id' => $id]);
        }
        if ($action === 'assign') {
            complaint_service()->assign(
                (int) ($_POST['complaint_id'] ?? 0),
                (int) ($_POST['staff_id'] ?? 0),
                $_POST,
                $currentUser
            );
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
        if ($action === 'cancel_complaint') {
            complaint_service()->cancel((int) ($_POST['complaint_id'] ?? 0), $currentUser);
            flash('success', 'Pengaduan berhasil dibatalkan dan tetap tersimpan di riwayat.');
            redirect('detail', ['id' => (int) $_POST['complaint_id']]);
        }
        if ($action === 'rate_complaint') {
            complaint_service()->rate(
                (int) ($_POST['complaint_id'] ?? 0),
                (int) ($_POST['rating'] ?? 0),
                (string) ($_POST['feedback'] ?? ''),
                $currentUser
            );
            flash('success', 'Terima kasih atas penilaian Anda.');
            redirect('detail', ['id' => (int) $_POST['complaint_id']]);
        }
        if ($action === 'repair_photos') {
            complaint_service()->addRepairPhotos(
                (int) ($_POST['complaint_id'] ?? 0),
                $_FILES['photos'] ?? [],
                $currentUser
            );
            flash('success', 'Foto hasil perbaikan berhasil ditambahkan.');
            redirect('detail', ['id' => (int) $_POST['complaint_id']]);
        }
        if ($action === 'set_blocked') {
            complaint_service()->setBlocked(
                (int) ($_POST['complaint_id'] ?? 0),
                (string) ($_POST['reason'] ?? ''),
                $currentUser
            );
            flash('success', 'Informasi hambatan tugas berhasil diperbarui.');
            redirect('detail', ['id' => (int) $_POST['complaint_id']]);
        }
        if ($action === 'create_staff') {
            Auth::requireRole(['admin']);
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            if ($name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)
                || strlen($email) > 190 || strlen($password) < 10) {
                throw new RuntimeException('Nama, email valid, dan kata sandi minimal 10 karakter wajib diisi.');
            }
            if (user_repository()->emailTaken($email)) {
                throw new RuntimeException('Email tersebut sudah digunakan oleh akun lain.');
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
            if (!$id || $name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)
                || strlen($email) > 190
                || ($password !== '' && strlen($password) < 10)) {
                throw new RuntimeException('Periksa kembali data petugas. Kata sandi baru minimal 10 karakter.');
            }
            if (user_repository()->emailTaken($email, $id)) {
                throw new RuntimeException('Email tersebut sudah digunakan oleh akun lain.');
            }
            user_repository()->updateStaff($id, $name, $email, $password === '' ? null : $password);
            flash('success', 'Data petugas berhasil diperbarui.');
            redirect('staff');
        }
        if ($action === 'create_student') {
            Auth::requireRole(['admin']);
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            if ($name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)
                || strlen($email) > 190 || strlen($password) < 10) {
                throw new RuntimeException('Nama, email valid, dan kata sandi minimal 10 karakter wajib diisi.');
            }
            if (user_repository()->emailTaken($email)) {
                throw new RuntimeException('Email tersebut sudah digunakan oleh akun lain.');
            }
            user_repository()->create($name, $email, $password, 'mahasiswa');
            flash('success', 'Akun mahasiswa berhasil ditambahkan.');
            redirect('students');
        }
        if ($action === 'update_student') {
            Auth::requireRole(['admin']);
            $id = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = trim((string) ($_POST['password'] ?? ''));
            if (!$id || $name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)
                || strlen($email) > 190 || ($password !== '' && strlen($password) < 10)) {
                throw new RuntimeException('Periksa kembali data mahasiswa. Kata sandi baru minimal 10 karakter.');
            }
            if (user_repository()->emailTaken($email, $id)) {
                throw new RuntimeException('Email tersebut sudah digunakan oleh akun lain.');
            }
            user_repository()->updateStudent($id, $name, $email, $password === '' ? null : $password);
            flash('success', 'Data mahasiswa berhasil diperbarui.');
            redirect('students');
        }
        if ($action === 'create_category') {
            Auth::requireRole(['admin']);
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 80) {
                throw new RuntimeException('Nama kategori wajib diisi dan maksimal 80 karakter.');
            }
            if (complaint_repository()->categoryNameTaken($name)) {
                throw new RuntimeException('Kategori dengan nama tersebut sudah tersedia.');
            }
            complaint_repository()->createCategory($name);
            flash('success', 'Kategori berhasil ditambahkan.');
            redirect('categories');
        }
        if ($action === 'update_category') {
            Auth::requireRole(['admin']);
            $id = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
            $name = trim((string) ($_POST['name'] ?? ''));
            if (!$id || $name === '' || mb_strlen($name) > 80) {
                throw new RuntimeException('Periksa kembali data kategori.');
            }
            if (complaint_repository()->categoryNameTaken($name, $id)) {
                throw new RuntimeException('Kategori dengan nama tersebut sudah tersedia.');
            }
            complaint_repository()->updateCategory($id, $name, isset($_POST['is_active']));
            flash('success', 'Kategori berhasil diperbarui.');
            redirect('categories');
        }
        throw new RuntimeException('Aksi tidak dikenal.');
    } catch (RuntimeException $error) {
        flash('error', $error->getMessage());
        $fallback = $action === 'login' ? 'login' : (Auth::user() === null ? 'login' : $page);
        if ($action === 'create_complaint') {
            $fallback = 'new';
        } elseif ($action === 'create_staff' || $action === 'update_staff') {
            $fallback = 'staff';
        } elseif ($action === 'create_student' || $action === 'update_student') {
            $fallback = 'students';
        } elseif ($action === 'create_category' || $action === 'update_category') {
            $fallback = 'categories';
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
    $photoId = filter_var($_GET['photo'] ?? null, FILTER_VALIDATE_INT);
    if (array_key_exists('photo', $_GET) && ($photoId === false || $photoId < 1)) {
        http_response_code(404);
        exit('Foto bukti tidak ditemukan.');
    }
    $photos = complaint_repository()->photos((int) $complaint['id']);
    $photo = null;
    foreach ($photos as $candidate) {
        if ($photoId === false || $photoId === null || (int) $candidate['id'] === $photoId) {
            $photo = $candidate;
            if ($photoId !== false && $photoId !== null) {
                break;
            }
        }
    }
    $filename = array_key_exists('photo', $_GET)
        ? ($photo['photo_path'] ?? null)
        : ($photo['photo_path'] ?? $complaint['photo_path']);
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

if ($page === 'export') {
    Auth::requireRole(['admin']);
    $rows = complaint_repository()->listAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan-pengaduan-' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'wb');
    if ($output === false) {
        throw new RuntimeException('File ekspor tidak dapat dibuat.');
    }
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Nomor', 'Pelapor', 'Kategori', 'Lokasi', 'Status', 'Prioritas', 'Tenggat', 'Petugas', 'Tanggal']);
    foreach ($rows as $row) {
        $values = [
            $row['id'], $row['reporter_name'], $row['category'], $row['location'], $row['status'],
            $row['priority'], $row['due_at'], $row['staff_name'], $row['created_at'],
        ];
        fputcsv($output, array_map(static function ($value): string {
            $value = (string) ($value ?? '');
            return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'" . $value : $value;
        }, $values));
    }
    fclose($output);
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
    $data = [
        'user' => $user, 'stats' => $stats, 'complaints' => array_slice($rows, 0, 6),
        'trend' => complaint_repository()->trend((int) $user['id'], $user['role']),
    ];
    $view = 'dashboard.php';
} elseif ($page === 'complaints') {
    $user = Auth::requireLogin();
    $status = (string) ($_GET['status'] ?? '');
    $search = trim((string) ($_GET['q'] ?? ''));
    $categoryId = ($_GET['category'] ?? '') === '' ? null : filter_var($_GET['category'], FILTER_VALIDATE_INT);
    if ($status !== '' && !in_array($status, ['Menunggu', 'Diproses', 'Selesai', 'Dibatalkan'], true)) {
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
    $data = ['user' => $user, 'categories' => complaint_repository()->activeCategories()];
    $view = 'complaint-form.php';
} elseif ($page === 'detail') {
    $user = Auth::requireLogin();
    $complaint = complaint_service()->visibleComplaint((int) ($_GET['id'] ?? 0), $user);
    $data = [
        'user' => $user,
        'complaint' => $complaint,
        'history' => complaint_repository()->history((int) $complaint['id']),
        'photos' => complaint_repository()->photos((int) $complaint['id']),
        'rating' => complaint_repository()->rating((int) $complaint['id']),
        'staff' => $user['role'] === 'admin' ? user_repository()->staff() : [],
    ];
    $view = 'complaint-detail.php';
} elseif ($page === 'staff') {
    $user = Auth::requireRole(['admin']);
    $data = ['user' => $user, 'staff' => user_repository()->staff()];
    $view = 'staff.php';
} elseif ($page === 'students') {
    $user = Auth::requireRole(['admin']);
    $data = ['user' => $user, 'students' => user_repository()->students()];
    $view = 'students.php';
} elseif ($page === 'categories') {
    $user = Auth::requireRole(['admin']);
    $data = ['user' => $user, 'categories' => complaint_repository()->categories()];
    $view = 'categories.php';
} elseif ($page === 'performance') {
    $user = Auth::requireRole(['admin']);
    $from = (string) ($_GET['from'] ?? date('Y-m-01'));
    $to = (string) ($_GET['to'] ?? date('Y-m-d'));
    $validDate = static fn (string $date): bool => DateTimeImmutable::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') === $date;
    if (!$validDate($from) || !$validDate($to) || $from > $to) {
        throw new RuntimeException('Rentang tanggal laporan kinerja tidak valid.');
    }
    $data = ['user' => $user, 'from' => $from, 'to' => $to, 'performance' => complaint_repository()->staffPerformance($from, $to)];
    $view = 'performance.php';
} elseif ($page === 'calendar') {
    $user = Auth::requireRole(['petugas']);
    $month = (string) ($_GET['month'] ?? date('Y-m'));
    $parsedMonth = DateTimeImmutable::createFromFormat('!Y-m', $month);
    if ($parsedMonth === false || $parsedMonth->format('Y-m') !== $month) {
        throw new RuntimeException('Bulan kalender tidak valid.');
    }
    $tasks = complaint_repository()->listAssigned((int) $user['id']);
    $data = ['user' => $user, 'tasks' => $tasks, 'month' => $month];
    $view = 'calendar.php';
} elseif ($page === 'report') {
    Auth::requireRole(['admin']);
    $user = Auth::user();
    $data = ['user' => $user, 'complaints' => complaint_repository()->listAll()];
    $view = 'report.php';
} else {
    http_response_code(404);
    throw new RuntimeException('Halaman yang diminta tidak ditemukan.');
}

extract($data, EXTR_SKIP);
$content = APP_ROOT . '/app/Views/' . $view;
require APP_ROOT . '/app/Views/layout.php';

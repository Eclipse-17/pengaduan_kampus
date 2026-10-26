<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $page, array $query = []): never
{
    $query = ['page' => $page] + $query;
    header('Location: index.php?' . http_build_query($query));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['_csrf'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        throw new RuntimeException('Sesi formulir kedaluwarsa. Muat ulang halaman, lalu coba lagi.');
    }
}

function status_class(string $status): string
{
    return match ($status) {
        'Menunggu' => 'waiting',
        'Diproses' => 'processing',
        'Selesai' => 'done',
        default => 'neutral',
    };
}

function role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Administrator',
        'petugas' => 'Petugas Fasilitas',
        default => 'Mahasiswa',
    };
}

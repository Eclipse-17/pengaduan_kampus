<?php
declare(strict_types=1);

final class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user === null) {
            flash('error', 'Silakan masuk terlebih dahulu.');
            redirect('login');
        }
        return $user;
    }

    public static function requireRole(array $roles): array
    {
        $user = self::requireLogin();
        if (!in_array($user['role'], $roles, true)) {
            http_response_code(403);
            throw new RuntimeException('Anda tidak memiliki akses untuk membuka halaman ini.');
        }
        return $user;
    }
}

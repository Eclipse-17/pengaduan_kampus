<?php
declare(strict_types=1);

final class AuthService
{
    public function __construct(private UserRepository $users)
    {
    }

    public function authenticate(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            throw new RuntimeException('Email atau kata sandi tidak sesuai.');
        }
        return $user;
    }
}

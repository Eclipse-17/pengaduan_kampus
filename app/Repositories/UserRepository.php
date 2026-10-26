<?php
declare(strict_types=1);

final class UserRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => mb_strtolower(trim($email))]);
        return $statement->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT id, name, email, role FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function create(string $name, string $email, string $password, string $role): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
        );
        $statement->execute([
            'name' => trim($name),
            'email' => mb_strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function staff(): array
    {
        return $this->db->query(
            "SELECT id, name, email, created_at FROM users WHERE role = 'petugas' ORDER BY name"
        )->fetchAll();
    }

    public function students(): array
    {
        return $this->db->query(
            "SELECT id, name, email, created_at FROM users WHERE role = 'mahasiswa' ORDER BY name"
        )->fetchAll();
    }

    public function emailTaken(string $email, ?int $exceptUserId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = :email';
        $parameters = ['email' => mb_strtolower(trim($email))];
        if ($exceptUserId !== null) {
            $sql .= ' AND id <> :except_user_id';
            $parameters['except_user_id'] = $exceptUserId;
        }
        $sql .= ' LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchColumn() !== false;
    }

    public function updateStaff(int $id, string $name, string $email, ?string $password): void
    {
        if ($password === null) {
            $statement = $this->db->prepare(
                "UPDATE users SET name = :name, email = :email WHERE id = :id AND role = 'petugas'"
            );
            $statement->execute(['name' => trim($name), 'email' => mb_strtolower(trim($email)), 'id' => $id]);
            return;
        }
        $statement = $this->db->prepare(
            "UPDATE users SET name = :name, email = :email, password_hash = :password_hash
             WHERE id = :id AND role = 'petugas'"
        );
        $statement->execute([
            'name' => trim($name),
            'email' => mb_strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => $id,
        ]);
    }

    public function updateStudent(int $id, string $name, string $email, ?string $password): void
    {
        $exists = $this->db->prepare("SELECT 1 FROM users WHERE id = :id AND role = 'mahasiswa' LIMIT 1");
        $exists->execute(['id' => $id]);
        if ($exists->fetchColumn() === false) {
            throw new RuntimeException('Akun mahasiswa tidak ditemukan.');
        }

        if ($password === null) {
            $statement = $this->db->prepare(
                "UPDATE users SET name = :name, email = :email WHERE id = :id AND role = 'mahasiswa'"
            );
            $statement->execute(['name' => trim($name), 'email' => mb_strtolower(trim($email)), 'id' => $id]);
            return;
        }
        $statement = $this->db->prepare(
            "UPDATE users SET name = :name, email = :email, password_hash = :password_hash
             WHERE id = :id AND role = 'mahasiswa'"
        );
        $statement->execute([
            'name' => trim($name),
            'email' => mb_strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => $id,
        ]);
    }
}

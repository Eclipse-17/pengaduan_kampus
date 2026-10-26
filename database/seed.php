<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    $db = database();
    $demoUsers = [
        ['Admin Kampus', 'admin@kampus.test', 'admin'],
        ['Mahasiswa Demo', 'mahasiswa@kampus.test', 'mahasiswa'],
        ['Petugas Fasilitas', 'petugas@kampus.test', 'petugas'],
    ];
    foreach ($demoUsers as [$name, $email, $role]) {
        if (user_repository()->findByEmail($email) === null) {
            user_repository()->create($name, $email, 'DemoKampus123!', $role);
        }
    }

    $categories = ['AC', 'Lampu', 'Kursi dan Meja', 'Toilet', 'Proyektor', 'Jaringan Internet', 'Lainnya'];
    $insert = $db->prepare('INSERT IGNORE INTO categories (name) VALUES (:name)');
    foreach ($categories as $category) {
        $insert->execute(['name' => $category]);
    }

    fwrite(STDOUT, "Data demo siap digunakan.\n");
    fwrite(STDOUT, "Kata sandi semua akun demo: DemoKampus123!\n");
    foreach ($demoUsers as [, $email]) {
        fwrite(STDOUT, " - {$email}\n");
    }
    fwrite(STDOUT, "Akun tersebut hanya untuk pengembangan lokal; ganti sebelum aplikasi dipublikasikan.\n");
} catch (Throwable $error) {
    fwrite(STDERR, 'Seeder gagal: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}

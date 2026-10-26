CREATE DATABASE IF NOT EXISTS pengaduan_fasilitas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pengaduan_fasilitas;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('mahasiswa', 'admin', 'petugas') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(80) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS complaints (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reporter_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    location VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    photo_path VARCHAR(80) NULL,
    status ENUM('Menunggu', 'Diproses', 'Selesai') NOT NULL DEFAULT 'Menunggu',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_complaints_reporter_created (reporter_id, created_at),
    KEY idx_complaints_status_created (status, created_at),
    CONSTRAINT fk_complaints_reporter FOREIGN KEY (reporter_id) REFERENCES users (id),
    CONSTRAINT fk_complaints_category FOREIGN KEY (category_id) REFERENCES categories (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assignments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id BIGINT UNSIGNED NOT NULL,
    staff_id BIGINT UNSIGNED NOT NULL,
    assigned_by BIGINT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_assignments_complaint_current (complaint_id, assigned_at, id),
    KEY idx_assignments_staff (staff_id, complaint_id),
    CONSTRAINT fk_assignments_complaint FOREIGN KEY (complaint_id) REFERENCES complaints (id),
    CONSTRAINT fk_assignments_staff FOREIGN KEY (staff_id) REFERENCES users (id),
    CONSTRAINT fk_assignments_admin FOREIGN KEY (assigned_by) REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS complaint_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id BIGINT UNSIGNED NOT NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    old_status ENUM('Menunggu', 'Diproses', 'Selesai') NULL,
    new_status ENUM('Menunggu', 'Diproses', 'Selesai') NOT NULL,
    note VARCHAR(500) NOT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_history_complaint_changed (complaint_id, changed_at, id),
    CONSTRAINT fk_history_complaint FOREIGN KEY (complaint_id) REFERENCES complaints (id),
    CONSTRAINT fk_history_user FOREIGN KEY (changed_by) REFERENCES users (id)
) ENGINE=InnoDB;

INSERT IGNORE INTO categories (name) VALUES
    ('AC'),
    ('Lampu'),
    ('Kursi dan Meja'),
    ('Toilet'),
    ('Proyektor'),
    ('Jaringan Internet'),
    ('Lainnya');

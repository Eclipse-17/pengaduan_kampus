USE pengaduan_fasilitas;

ALTER TABLE categories
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE complaints
    MODIFY COLUMN status ENUM('Menunggu', 'Diproses', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Menunggu',
    ADD COLUMN priority ENUM('Rendah', 'Normal', 'Tinggi') NOT NULL DEFAULT 'Normal',
    ADD COLUMN due_at DATETIME NULL,
    ADD COLUMN started_at DATETIME NULL,
    ADD COLUMN completed_at DATETIME NULL,
    ADD COLUMN blocked_reason VARCHAR(500) NULL,
    ADD KEY idx_complaints_due_at (due_at);

ALTER TABLE complaint_history
    MODIFY COLUMN old_status ENUM('Menunggu', 'Diproses', 'Selesai', 'Dibatalkan') NULL,
    MODIFY COLUMN new_status ENUM('Menunggu', 'Diproses', 'Selesai', 'Dibatalkan') NOT NULL;

CREATE TABLE complaint_photos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    photo_path VARCHAR(80) NOT NULL,
    photo_type ENUM('evidence', 'repair') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_complaint_photos_complaint_type (complaint_id, photo_type, id),
    CONSTRAINT fk_complaint_photos_complaint FOREIGN KEY (complaint_id) REFERENCES complaints (id),
    CONSTRAINT fk_complaint_photos_user FOREIGN KEY (uploaded_by) REFERENCES users (id)
) ENGINE=InnoDB;

INSERT INTO complaint_photos (complaint_id, uploaded_by, photo_path, photo_type)
SELECT id, reporter_id, photo_path, 'evidence'
FROM complaints
WHERE photo_path IS NOT NULL;

CREATE TABLE complaint_ratings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    feedback VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_complaint_ratings_complaint (complaint_id),
    CONSTRAINT fk_complaint_ratings_complaint FOREIGN KEY (complaint_id) REFERENCES complaints (id),
    CONSTRAINT fk_complaint_ratings_student FOREIGN KEY (student_id) REFERENCES users (id)
) ENGINE=InnoDB;

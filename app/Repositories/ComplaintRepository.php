<?php
declare(strict_types=1);

final class ComplaintRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function categories(): array
    {
        return $this->db->query('SELECT id, name, is_active FROM categories ORDER BY name')->fetchAll();
    }

    public function activeCategories(): array
    {
        return $this->db->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll();
    }

    public function create(int $reporterId, int $categoryId, string $location, string $description, array $photos): int
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                "INSERT INTO complaints (reporter_id, category_id, location, description, photo_path, status)
                 VALUES (:reporter_id, :category_id, :location, :description, :photo_path, 'Menunggu')"
            );
            $firstPhoto = $photos[0] ?? null;
            $statement->execute([
                'reporter_id' => $reporterId,
                'category_id' => $categoryId,
                'location' => $location,
                'description' => $description,
                'photo_path' => $firstPhoto,
            ]);
            $complaintId = (int) $this->db->lastInsertId();

            $this->insertPhotos($complaintId, $reporterId, $photos, 'evidence');
            $history = $this->db->prepare(
                "INSERT INTO complaint_history (complaint_id, changed_by, old_status, new_status, note)
                 VALUES (:complaint_id, :changed_by, NULL, 'Menunggu', 'Pengaduan dibuat')"
            );
            $history->execute(['complaint_id' => $complaintId, 'changed_by' => $reporterId]);
            $this->db->commit();
            return $complaintId;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function listForStudent(int $studentId, ?string $status = null, string $search = '', ?int $categoryId = null): array
    {
        return $this->list('c.reporter_id = :user_id', $studentId, $status, $search, $categoryId);
    }

    public function listAll(?string $status = null, string $search = '', ?int $categoryId = null): array
    {
        return $this->list('1 = 1', null, $status, $search, $categoryId);
    }

    public function listAssigned(int $staffId, ?string $status = null, string $search = '', ?int $categoryId = null): array
    {
        return $this->list(
            "c.status <> 'Dibatalkan' AND EXISTS (SELECT 1 FROM assignments mine WHERE mine.complaint_id = c.id AND mine.staff_id = :user_id
                    AND mine.id = (SELECT latest.id FROM assignments latest
                                   WHERE latest.complaint_id = c.id ORDER BY latest.assigned_at DESC, latest.id DESC LIMIT 1))",
            $staffId,
            $status,
            $search,
            $categoryId
        );
    }

    private function list(string $scope, ?int $userId, ?string $status, string $search, ?int $categoryId): array
    {
        $sql = "SELECT c.id, c.location, c.status, c.priority, c.due_at, c.blocked_reason, c.created_at, cat.name AS category,
                       reporter.name AS reporter_name, assigned.name AS staff_name
                FROM complaints c
                JOIN categories cat ON cat.id = c.category_id
                JOIN users reporter ON reporter.id = c.reporter_id
                LEFT JOIN assignments current_assignment ON current_assignment.id = (
                    SELECT a.id FROM assignments a WHERE a.complaint_id = c.id ORDER BY a.assigned_at DESC, a.id DESC LIMIT 1
                )
                LEFT JOIN users assigned ON assigned.id = current_assignment.staff_id
                WHERE {$scope}";
        $parameters = [];
        if ($userId !== null) {
            $parameters['user_id'] = $userId;
        }
        if ($status !== null && $status !== '') {
            $sql .= ' AND c.status = :status';
            $parameters['status'] = $status;
        }
        if ($categoryId !== null) {
            $sql .= ' AND c.category_id = :category_id';
            $parameters['category_id'] = $categoryId;
        }
        if ($search !== '') {
            $sql .= ' AND (CAST(c.id AS CHAR) LIKE :search_id
                          OR cat.name LIKE :search_category
                          OR c.location LIKE :search_location
                          OR reporter.name LIKE :search_reporter)';
            $term = '%' . $search . '%';
            $parameters['search_id'] = $term;
            $parameters['search_category'] = $term;
            $parameters['search_location'] = $term;
            $parameters['search_reporter'] = $term;
        }
        $sql .= ' ORDER BY c.created_at DESC, c.id DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            "SELECT c.*, cat.name AS category, reporter.name AS reporter_name, reporter.email AS reporter_email,
                    assigned.id AS staff_id, assigned.name AS staff_name,
                    current_assignment.assigned_at AS assigned_at
             FROM complaints c
             JOIN categories cat ON cat.id = c.category_id
             JOIN users reporter ON reporter.id = c.reporter_id
             LEFT JOIN assignments current_assignment ON current_assignment.id = (
                 SELECT a.id FROM assignments a WHERE a.complaint_id = c.id ORDER BY a.assigned_at DESC, a.id DESC LIMIT 1
             )
             LEFT JOIN users assigned ON assigned.id = current_assignment.staff_id
             WHERE c.id = :id LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function history(int $complaintId): array
    {
        $statement = $this->db->prepare(
            'SELECT h.old_status, h.new_status, h.note, h.changed_at, u.name AS changed_by_name
             FROM complaint_history h JOIN users u ON u.id = h.changed_by
             WHERE h.complaint_id = :id ORDER BY h.changed_at DESC, h.id DESC'
        );
        $statement->execute(['id' => $complaintId]);
        return $statement->fetchAll();
    }

    public function statistics(?int $userId, string $role): array
    {
        $scope = '';
        $parameters = [];
        if ($role === 'mahasiswa') {
            $scope = 'WHERE reporter_id = :user_id';
            $parameters['user_id'] = $userId;
        } elseif ($role === 'petugas') {
            $scope = "WHERE status <> 'Dibatalkan' AND id IN (
                SELECT a.complaint_id FROM assignments a
                WHERE a.staff_id = :user_id AND a.id = (
                    SELECT latest.id FROM assignments latest WHERE latest.complaint_id = a.complaint_id
                    ORDER BY latest.assigned_at DESC, latest.id DESC LIMIT 1
                )
            )";
            $parameters['user_id'] = $userId;
        }
        $statement = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(status = 'Menunggu') AS waiting,
                    SUM(status = 'Diproses') AS processing,
                    SUM(status = 'Selesai') AS done,
                    SUM(status = 'Dibatalkan') AS cancelled
             FROM complaints {$scope}"
        );
        $statement->execute($parameters);
        $result = $statement->fetch();
        return array_map(static fn ($value): int => (int) ($value ?? 0), $result);
    }

    public function assign(int $complaintId, int $staffId, int $adminId, string $priority, ?string $dueAt): void
    {
        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare('SELECT status FROM complaints WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $complaintId]);
            $status = $lock->fetchColumn();
            if ($status === false || in_array($status, ['Selesai', 'Dibatalkan'], true)) {
                throw new RuntimeException('Pengaduan tidak dapat ditugaskan.');
            }
            $statement = $this->db->prepare(
                'INSERT INTO assignments (complaint_id, staff_id, assigned_by) VALUES (:complaint_id, :staff_id, :assigned_by)'
            );
            $statement->execute([
                'complaint_id' => $complaintId,
                'staff_id' => $staffId,
                'assigned_by' => $adminId,
            ]);
            $this->setSchedule($complaintId, $priority, $dueAt);
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function updateStatus(int $complaintId, string $newStatus, int $userId, string $role, string $note): void
    {
        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare('SELECT status FROM complaints WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $complaintId]);
            $oldStatus = $lock->fetchColumn();
            if ($oldStatus === false) {
                throw new RuntimeException('Pengaduan tidak ditemukan.');
            }
            $allowedStatuses = match ($oldStatus) {
                'Menunggu' => ['Diproses', 'Dibatalkan'],
                'Diproses' => ['Selesai'],
                default => [],
            };
            if (!in_array($newStatus, $allowedStatuses, true)) {
                throw new RuntimeException('Perubahan status tidak mengikuti alur yang diizinkan.');
            }
            if ($role === 'petugas') {
                $assignment = $this->db->prepare(
                    'SELECT 1 FROM assignments a WHERE a.complaint_id = :complaint_id AND a.staff_id = :staff_id
                     AND a.id = (SELECT latest.id FROM assignments latest WHERE latest.complaint_id = a.complaint_id
                                 ORDER BY latest.assigned_at DESC, latest.id DESC LIMIT 1) LIMIT 1'
                );
                $assignment->execute(['complaint_id' => $complaintId, 'staff_id' => $userId]);
                if (!$assignment->fetchColumn()) {
                    throw new RuntimeException('Anda tidak lagi ditugaskan pada pengaduan ini.');
                }
            }
            $timestamps = match ($newStatus) {
                'Diproses' => ', started_at = COALESCE(started_at, CURRENT_TIMESTAMP)',
                'Selesai' => ', completed_at = CURRENT_TIMESTAMP, blocked_reason = NULL',
                'Dibatalkan' => '',
                default => '',
            };
            $update = $this->db->prepare('UPDATE complaints SET status = :status' . $timestamps . ' WHERE id = :id');
            $update->execute(['status' => $newStatus, 'id' => $complaintId]);
            $history = $this->db->prepare(
                'INSERT INTO complaint_history (complaint_id, changed_by, old_status, new_status, note)
                 VALUES (:complaint_id, :changed_by, :old_status, :new_status, :note)'
            );
            $history->execute([
                'complaint_id' => $complaintId,
                'changed_by' => $userId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => $note,
            ]);
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function hasAssignment(int $complaintId, int $staffId): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM assignments a
             WHERE a.complaint_id = :complaint_id AND a.staff_id = :staff_id
               AND a.id = (SELECT current_assignment.id FROM assignments current_assignment
                           WHERE current_assignment.complaint_id = a.complaint_id
                           ORDER BY current_assignment.assigned_at DESC, current_assignment.id DESC LIMIT 1)
             LIMIT 1'
        );
        $statement->execute(['complaint_id' => $complaintId, 'staff_id' => $staffId]);
        return (bool) $statement->fetchColumn();
    }

    public function photos(int $complaintId): array
    {
        $statement = $this->db->prepare(
            'SELECT id, photo_path, photo_type, created_at FROM complaint_photos
             WHERE complaint_id = :complaint_id ORDER BY id'
        );
        $statement->execute(['complaint_id' => $complaintId]);
        return $statement->fetchAll();
    }

    public function categoryNameTaken(string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM categories WHERE name = :name';
        $parameters = ['name' => $name];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $parameters['except_id'] = $exceptId;
        }
        $statement = $this->db->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);
        return (bool) $statement->fetchColumn();
    }

    private function insertPhotos(int $complaintId, int $userId, array $photos, string $type): void
    {
        if ($photos === []) {
            return;
        }
        $statement = $this->db->prepare(
            'INSERT INTO complaint_photos (complaint_id, uploaded_by, photo_path, photo_type)
             VALUES (:complaint_id, :uploaded_by, :photo_path, :photo_type)'
        );
        foreach ($photos as $photo) {
            $statement->execute([
                'complaint_id' => $complaintId,
                'uploaded_by' => $userId,
                'photo_path' => $photo,
                'photo_type' => $type,
            ]);
        }
    }

    public function addRepairPhotos(int $complaintId, int $userId, array $photos): void
    {
        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare('SELECT status FROM complaints WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $complaintId]);
            if ($lock->fetchColumn() !== 'Diproses' || !$this->hasAssignment($complaintId, $userId)) {
                throw new RuntimeException('Tugas tidak lagi dapat menerima foto hasil perbaikan.');
            }
            $this->insertPhotos($complaintId, $userId, $photos, 'repair');
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function setSchedule(int $complaintId, string $priority, ?string $dueAt): void
    {
        $statement = $this->db->prepare(
            'UPDATE complaints SET priority = :priority, due_at = :due_at WHERE id = :id'
        );
        $statement->execute(['priority' => $priority, 'due_at' => $dueAt, 'id' => $complaintId]);
    }

    public function setBlocked(int $complaintId, int $staffId, ?string $reason): void
    {
        $statement = $this->db->prepare(
            "UPDATE complaints c SET blocked_reason = :reason
             WHERE c.id = :id AND c.status = 'Diproses'
               AND EXISTS (SELECT 1 FROM assignments a WHERE a.complaint_id = c.id AND a.staff_id = :staff_id
                           AND a.id = (SELECT latest.id FROM assignments latest
                                       WHERE latest.complaint_id = c.id
                                       ORDER BY latest.assigned_at DESC, latest.id DESC LIMIT 1))"
        );
        $statement->execute(['reason' => $reason, 'id' => $complaintId, 'staff_id' => $staffId]);
    }

    public function rating(int $complaintId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT rating, feedback, created_at FROM complaint_ratings WHERE complaint_id = :id LIMIT 1'
        );
        $statement->execute(['id' => $complaintId]);
        return $statement->fetch() ?: null;
    }

    public function saveRating(int $complaintId, int $studentId, int $rating, string $feedback): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO complaint_ratings (complaint_id, student_id, rating, feedback)
             VALUES (:complaint_id, :student_id, :rating, :feedback)'
        );
        $statement->execute([
            'complaint_id' => $complaintId,
            'student_id' => $studentId,
            'rating' => $rating,
            'feedback' => $feedback !== '' ? $feedback : null,
        ]);
    }

    public function trend(?int $userId, string $role, int $days = 14): array
    {
        $scope = '';
        $since = (new DateTimeImmutable())->modify('-' . $days . ' days')->setTime(0, 0)->format('Y-m-d H:i:s');
        $parameters = ['since' => $since];
        if ($role === 'mahasiswa') {
            $scope = 'AND reporter_id = :user_id';
            $parameters['user_id'] = $userId;
        } elseif ($role === 'petugas') {
            $scope = "AND id IN (
                SELECT a.complaint_id FROM assignments a
                WHERE a.staff_id = :user_id AND a.id = (
                    SELECT latest.id FROM assignments latest WHERE latest.complaint_id = a.complaint_id
                    ORDER BY latest.assigned_at DESC, latest.id DESC LIMIT 1
                )
            )";
            $parameters['user_id'] = $userId;
        }
        $statement = $this->db->prepare(
            "SELECT DATE(created_at) AS day, COUNT(*) AS total
             FROM complaints WHERE created_at >= :since {$scope}
             GROUP BY DATE(created_at) ORDER BY day"
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function staffPerformance(string $from, string $to): array
    {
        $statement = $this->db->prepare(
            "SELECT u.id, u.name, COUNT(DISTINCT CASE WHEN c.status <> 'Dibatalkan' THEN a.complaint_id END) AS assigned,
                    SUM(c.status = 'Selesai') AS completed,
                    ROUND(AVG(CASE WHEN c.status = 'Selesai' AND c.completed_at IS NOT NULL
                        THEN TIMESTAMPDIFF(HOUR, c.started_at, c.completed_at) END), 1) AS average_hours
             FROM users u
             LEFT JOIN assignments a ON a.staff_id = u.id
                AND a.id = (SELECT latest.id FROM assignments latest WHERE latest.complaint_id = a.complaint_id
                            ORDER BY latest.assigned_at DESC, latest.id DESC LIMIT 1)
                AND a.assigned_at >= :date_from AND a.assigned_at < DATE_ADD(:date_to, INTERVAL 1 DAY)
             LEFT JOIN complaints c ON c.id = a.complaint_id
             WHERE u.role = 'petugas'
             GROUP BY u.id, u.name ORDER BY completed DESC, u.name"
        );
        $statement->execute(['date_from' => $from, 'date_to' => $to]);
        return $statement->fetchAll();
    }

    public function createCategory(string $name): void
    {
        $statement = $this->db->prepare('INSERT INTO categories (name) VALUES (:name)');
        $statement->execute(['name' => $name]);
    }

    public function updateCategory(int $id, string $name, bool $active): void
    {
        $statement = $this->db->prepare(
            'UPDATE categories SET name = :name, is_active = :is_active WHERE id = :id'
        );
        $statement->execute(['name' => $name, 'is_active' => (int) $active, 'id' => $id]);
    }
}

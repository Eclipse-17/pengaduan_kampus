<?php
declare(strict_types=1);

final class ComplaintRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function categories(): array
    {
        return $this->db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
    }

    public function create(int $reporterId, int $categoryId, string $location, string $description, ?string $photo): int
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                "INSERT INTO complaints (reporter_id, category_id, location, description, photo_path, status)
                 VALUES (:reporter_id, :category_id, :location, :description, :photo_path, 'Menunggu')"
            );
            $statement->execute([
                'reporter_id' => $reporterId,
                'category_id' => $categoryId,
                'location' => $location,
                'description' => $description,
                'photo_path' => $photo,
            ]);
            $complaintId = (int) $this->db->lastInsertId();

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
            'EXISTS (SELECT 1 FROM assignments mine WHERE mine.complaint_id = c.id AND mine.staff_id = :user_id
                    AND mine.id = (SELECT latest.id FROM assignments latest
                                   WHERE latest.complaint_id = c.id ORDER BY latest.assigned_at DESC, latest.id DESC LIMIT 1))',
            $staffId,
            $status,
            $search,
            $categoryId
        );
    }

    private function list(string $scope, ?int $userId, ?string $status, string $search, ?int $categoryId): array
    {
        $sql = "SELECT c.id, c.location, c.status, c.created_at, cat.name AS category,
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
            $scope = "WHERE id IN (
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
                    SUM(status = 'Selesai') AS done
             FROM complaints {$scope}"
        );
        $statement->execute($parameters);
        $result = $statement->fetch();
        return array_map(static fn ($value): int => (int) ($value ?? 0), $result);
    }

    public function assign(int $complaintId, int $staffId, int $adminId): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO assignments (complaint_id, staff_id, assigned_by) VALUES (:complaint_id, :staff_id, :assigned_by)'
        );
        $statement->execute([
            'complaint_id' => $complaintId,
            'staff_id' => $staffId,
            'assigned_by' => $adminId,
        ]);
    }

    public function updateStatus(int $complaintId, string $newStatus, int $userId, string $note): void
    {
        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare('SELECT status FROM complaints WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $complaintId]);
            $oldStatus = $lock->fetchColumn();
            if ($oldStatus === false) {
                throw new RuntimeException('Pengaduan tidak ditemukan.');
            }
            $update = $this->db->prepare('UPDATE complaints SET status = :status WHERE id = :id');
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
}

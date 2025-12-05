<?php

namespace App\Repositories\Appointments;

use App\Models\AppointmentModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Appointments
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class AppointmentRepository
{
    protected AppointmentModel $model;
    protected BaseConnection $db;

    public function __construct(?AppointmentModel $model = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->ensureTable();
        $this->model = $model ?? new AppointmentModel();
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();
        return $this->hydrate($payload);
    }

    public function findById(int $id): ?array
    {
        $row = $this->model->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    public function updateStatus(int $id, string $status): bool
    {
        return (bool) $this->model->update($id, ['status' => $status, 'updated_at' => $this->now()]);
    }

    public function updateSchedule(int $id, array $data): bool
    {
        $payload = [
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'updated_at' => $this->now(),
        ];
        return (bool) $this->model->update($id, $payload);
    }

    public function hasConflict(\DateTimeInterface $start, \DateTimeInterface $end, ?int $ignoreId = null): bool
    {
        $builder = $this->db->table('appointments')
            ->where('status', 'scheduled');

        if ($ignoreId) {
            $builder->where('id !=', $ignoreId);
        }

        $row = $builder
            ->groupStart()
                ->groupStart()
                    ->where('start_time <', $end->format('Y-m-d H:i:s'))
                    ->where('end_time >', $start->format('Y-m-d H:i:s'))
                ->groupEnd()
            ->groupEnd()
            ->get()->getRowArray();
        return ! empty($row);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['customer_id'] = isset($row['customer_id']) ? (int) $row['customer_id'] : null;
        $row['lead_id'] = isset($row['lead_id']) ? (int) $row['lead_id'] : null;
        $row['contract_id'] = isset($row['contract_id']) ? (int) $row['contract_id'] : null;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Ensure appointments table exists in testing to avoid query failures.
     */
    private function ensureTable(): void
    {
        if ($this->db->tableExists('appointments') || ENVIRONMENT !== 'testing') {
            return;
        }
        $this->db->query("CREATE TABLE IF NOT EXISTS appointments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id BIGINT UNSIGNED NULL,
            lead_id BIGINT UNSIGNED NULL,
            contract_id BIGINT UNSIGNED NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            status VARCHAR(30) DEFAULT 'scheduled',
            notes TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_appointment_time (start_time, end_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

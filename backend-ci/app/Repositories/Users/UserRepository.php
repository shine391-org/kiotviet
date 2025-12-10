<?php

namespace App\Repositories\Users;

use CodeIgniter\Database\BaseConnection;

/**
 * User Repository - Database operations for users.
 *
 * @agent-repository: Users
 * @agent-pattern: Repository pattern
 */
class UserRepository
{
    protected BaseConnection $db;
    protected string $table = 'users';

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * Find users by array of IDs and return id => display name map.
     *
     * @param array $userIds Array of user IDs
     * @return array<int, string|null> Map of user_id => display_name
     */
    public function findByIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $users = $this->db->table($this->table)
            ->select('id, full_name, name, username')
            ->whereIn('id', $userIds)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($users as $user) {
            $id = (int) $user['id'];
            $map[$id] = $user['full_name'] ?? $user['name'] ?? $user['username'] ?? null;
        }

        return $map;
    }

    /**
     * Find a single user by ID.
     */
    public function findById(int $id): ?array
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Get users with optional filters.
     */
    public function findAll(array $filters = []): array
    {
        $builder = $this->db->table($this->table);

        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        if (!empty($filters['branch_id'])) {
            $builder->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('username', $search)
                ->orLike('full_name', $search)
                ->orLike('email', $search)
                ->groupEnd();
        }

        $limit = (int) ($filters['limit'] ?? 50);
        $offset = ((int) ($filters['page'] ?? 1) - 1) * $limit;

        return $builder
            ->orderBy('full_name', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }
}

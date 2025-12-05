<?php

namespace App\Repositories\Portal;

use App\Models\PortalUserModel;
use App\Models\PortalAccessTokenModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Portal user repository.
 *
 * @agent-repository: Portal user
 * @agent-pattern: Repository with tokens
 * @agent-reusable: MEDIUM
 */
class PortalUserRepository
{
    protected PortalUserModel $users;
    protected PortalAccessTokenModel $tokens;
    protected BaseConnection $db;

    public function __construct(?PortalUserModel $users = null, ?PortalAccessTokenModel $tokens = null, ?BaseConnection $db = null)
    {
        $this->users = $users ?? new PortalUserModel();
        $this->tokens = $tokens ?? new PortalAccessTokenModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->users->insert($payload);
        $payload['id'] = (int) $this->users->getInsertID();
        return $payload;
    }

    public function update(int $id, array $data): void
    {
        $this->users->update($id, $data + ['updated_at' => $this->now()]);
    }

    public function findByEmail(string $email): ?array
    {
        $row = $this->users->where('email', $email)->first();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $row = $this->users->find($id);
        return $row ?: null;
    }

    public function createToken(int $portalUserId, string $token, ?string $expiresAt = null): array
    {
        $payload = [
            'portal_user_id' => $portalUserId,
            'token' => $token,
            'expires_at' => $expiresAt,
            'created_at' => $this->now(),
        ];
        $this->tokens->insert($payload);
        $payload['id'] = (int) $this->tokens->getInsertID();
        return $payload;
    }

    public function findToken(string $token): ?array
    {
        $row = $this->tokens->where('token', $token)->first();
        return $row ?: null;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

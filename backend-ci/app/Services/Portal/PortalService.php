<?php

namespace App\Services\Portal;

use App\Repositories\Portal\PortalUserRepository;
use App\Validators\PortalValidator;
use RuntimeException;

/**
 * Portal auth/access service.
 *
 * @agent-service: Portal
 * @agent-pattern: Token login
 * @agent-reusable: MEDIUM
 */
class PortalService
{
    protected PortalUserRepository $repo;
    protected PortalValidator $validator;

    public function __construct(?PortalUserRepository $repo = null, ?PortalValidator $validator = null)
    {
        $this->repo = $repo ?? new PortalUserRepository();
        $this->validator = $validator ?? new PortalValidator();
    }

    /** @agent-use: POST /api/portal/login */
    public function login(array $input): array
    {
        $data = $this->validator->validateLogin($input);
        $user = $this->repo->findByEmail($data['email']);
        if (! $user || ! password_verify($data['password'], $user['password_hash'])) {
            throw new RuntimeException('Invalid credentials');
        }
        $token = bin2hex(random_bytes(16));
        $this->repo->createToken((int) $user['id'], $token, date('Y-m-d H:i:s', strtotime('+1 day')));
        $this->repo->update((int) $user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
        return ['success' => true, 'data' => ['token' => $token]];
    }

    /** @agent-use: POST /api/portal/users */
    public function createUser(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $user = $this->repo->findByEmail($data['email']);
        if ($user) {
            throw new RuntimeException('Portal user exists');
        }
        $payload = $data + ['password_hash' => password_hash($data['password'], PASSWORD_BCRYPT)];
        $created = $this->repo->create($payload);
        return ['success' => true, 'data' => $created];
    }

    /** @agent-use: Token verification */
    public function verifyToken(string $token): array
    {
        $row = $this->repo->findToken($token);
        if (! $row) {
            throw new RuntimeException('Invalid token');
        }
        if (! empty($row['expires_at']) && strtotime($row['expires_at']) < time()) {
            throw new RuntimeException('Token expired');
        }
        $user = $this->repo->findById((int) $row['portal_user_id']);
        if (! $user) {
            throw new RuntimeException('User not found');
        }
        return $user;
    }
}

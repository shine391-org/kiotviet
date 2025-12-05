<?php

namespace App\Services\Jobs;

use App\Repositories\Jobs\JobRepository;
use RuntimeException;

/**
 * Job runner service (pull + execute + retry).
 *
 * @agent-service: Job runner
 * @agent-pattern: Worker loop
 * @agent-reusable: MEDIUM
 */
class JobRunnerService
{
    protected JobRepository $repo;
    /** @var array<string,callable> */
    protected array $handlers = [];

    public function __construct(?JobRepository $repo = null)
    {
        $this->repo = $repo ?? new JobRepository();
    }

    /** @agent-use: Register handler callable */
    public function registerHandler(string $name, callable $handler): void
    {
        $this->handlers[$name] = $handler;
    }

    /**
     * Pull next job and run once.
     *
     * @return array|null
     */
    public function runNext(): ?array
    {
        $job = $this->repo->claimNext();
        if (! $job) {
            return null;
        }
        $payload = $job['payload'] ? json_decode($job['payload'], true) : [];
        $attempts = (int) ($job['attempts'] ?? 0) + 1;
        $max = (int) ($job['max_attempts'] ?? 3);
        try {
            $this->execute($job['name'], $payload);
            $this->repo->markSuccess((int) $job['id']);
            $job['status'] = 'done';
        } catch (\Throwable $e) {
            $this->repo->markFailure((int) $job['id'], $e->getMessage(), $attempts, $max);
            $job['status'] = $attempts >= $max ? 'failed' : 'queued';
            $job['last_error'] = $e->getMessage();
        }
        return $job;
    }

    private function execute(string $handler, $payload): void
    {
        if (isset($this->handlers[$handler])) {
            ($this->handlers[$handler])($payload);
            return;
        }
        // default stub: succeed if name ends with .success, fail otherwise
        if (str_contains($handler, 'fail')) {
            throw new RuntimeException('Handler failed');
        }
    }
}

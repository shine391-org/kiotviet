<?php

namespace App\Services\Jobs;

use App\Repositories\Jobs\SchedulerRuleRepository;
use App\Repositories\Jobs\JobRepository;
use App\Validators\SchedulerValidator;
use App\Validators\JobValidator;
use RuntimeException;

/**
 * Scheduler service for cron rules and enqueue.
 *
 * @agent-service: Scheduler
 * @agent-pattern: Rule + enqueue
 * @agent-reusable: MEDIUM
 */
class SchedulerService
{
    protected SchedulerRuleRepository $rules;
    protected JobRepository $jobs;
    protected SchedulerValidator $validator;
    protected JobValidator $jobValidator;

    public function __construct(
        ?SchedulerRuleRepository $rules = null,
        ?JobRepository $jobs = null,
        ?SchedulerValidator $validator = null,
        ?JobValidator $jobValidator = null
    ) {
        $this->rules = $rules ?? new SchedulerRuleRepository();
        $this->jobs = $jobs ?? new JobRepository();
        $this->validator = $validator ?? new SchedulerValidator();
        $this->jobValidator = $jobValidator ?? new JobValidator();
    }

    /** @agent-use: POST /api/scheduler-rules */
    public function createRule(array $input): array
    {
        $data = $this->validator->validateRule($input);
        $rule = $this->rules->create($data + ['next_run_at' => date('Y-m-d H:i:s')]);
        return ['success' => true, 'data' => $rule];
    }

    /** @agent-use: GET /api/scheduler-rules */
    public function listRules(array $filters = []): array
    {
        return ['success' => true, 'data' => $this->rules->list($filters)];
    }

    /**
     * Evaluate due rules and enqueue jobs.
     *
     * @agent-use: cron runner
     */
    public function tick(): array
    {
        $due = $this->rules->dueRules();
        $enqueued = [];
        foreach ($due as $rule) {
            $enqueued[] = $this->enqueueHandler($rule['handler']);
            $nextRun = $this->nextRun($rule['cron_expression']);
            $this->rules->updateSchedule((int) $rule['id'], date('Y-m-d H:i:s'), $nextRun);
        }
        return ['success' => true, 'data' => $enqueued];
    }

    /** @agent-use: internal enqueue */
    public function enqueueHandler(string $handler, array $payload = [], ?string $runAt = null): array
    {
        $data = $this->jobValidator->validateEnqueue([
            'name' => $handler,
            'payload' => json_encode($payload),
            'next_run_at' => $runAt,
        ]);
        $job = $this->jobs->enqueue($data);
        return $job;
    }

    private function nextRun(string $cron): string
    {
        // Simplified: run every cron expression as +5 minutes to avoid complex parser.
        return date('Y-m-d H:i:s', strtotime('+5 minutes'));
    }
}

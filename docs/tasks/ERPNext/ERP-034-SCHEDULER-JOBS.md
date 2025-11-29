ERP-034 - Scheduler/Background Jobs
Bạn là AI backend engineer phụ trách nền tảng job định kỳ/async giống ERPNext.

1. Bối cảnh
- ERPNext có scheduler và background jobs (subscription, email campaign, reminders). LanoCRM chưa có nền tảng chuẩn.

2. Phạm vi & Deliverables
- Migration: job_queue (name, payload, status, attempts, next_run_at), job_logs; scheduler_rules (cron, handler, enabled).
- Validator: SchedulerValidator, JobValidator.
- Repository: JobRepository, SchedulerRuleRepository.
- Service: SchedulerService (register/load rules, enqueue jobs), JobRunnerService (pull/execute/update status/retry/backoff), abstractions for modules (subscription invoicing, email campaign, reminders).
- Controller/API (optional admin): list jobs, retry/cancel, manage scheduler rules.
- Integration: hook từ SubscriptionService, EmailCampaignService, Aging/Reminder to enqueue; ensure idempotency.

3. Testing (DevDatabaseTrait)
- Unit: SchedulerServiceTest (cron → enqueue), JobRunnerServiceTest (execute/retry/backoff, idempotent), repository tests.
- Integration: enqueue from subscription/email campaign, run worker simulate, verify state transitions.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Có scheduler + queue chạy được, retry/backoff, idempotent; module khác có thể enqueue job định kỳ/async.
- Unit + integration tests pass.

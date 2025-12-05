# Session Log - ERP-034 Scheduler/Background Jobs

- **Date:** 2025-11-29
- **Task:** ERP-034 - Scheduler & Job Queue

## What I did
- Thêm schema scheduler_rules, job_queue, job_logs vào golden migration + DevDatabaseTrait; tạo model CI4 cho từng bảng.
- Implement validator/repo/service: SchedulerService (rule + tick enqueue), JobRunnerService (claim/execute/retry), Job/Scheduler validators; admin controllers + routes for scheduler rules, tick, enqueue/run-next; wiring services.
- Viết unit tests cho scheduler và job runner; integration test API tick/enqueue/run flow.

## Tests
- `docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/SchedulerServiceTest.php tests/Services/JobRunnerServiceTest.php tests/Integration/Api/SchedulerApiTest.php`

## Notes / Issues
- Cron parsing đang giản lược: next_run_at mặc định +5 phút; rule mới set next_run_at = now để tick chạy ngay; có thể thay bằng parser/Quartz sau.

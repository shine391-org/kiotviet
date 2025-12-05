ERP-029 - Dự án, Công việc, Timesheet
Bạn là AI backend engineer phụ trách Project/Timesheet như ERPNext.

1. Bối cảnh
- ERPNext có Project, Task, Timesheet, Activity Cost. LanoCRM chưa có.

2. Phạm vi & Deliverables
- Migration: projects, tasks (parent, status, progress), timesheets, timesheet_details (task, hours, billing_rate, cost_rate), activity_types.
- Validator: ProjectValidator, TaskValidator, TimesheetValidator.
- Repository: ProjectRepository, TaskRepository, TimesheetRepository.
- Service: ProjectService (CRUD, progress calc), TaskService (status flow, dependencies optional), TimesheetService (log time, compute billable/cost), ActivityCostService stub.
- Controller API: manage projects/tasks; log/update timesheet; report summary per project.
- Integration: Link timesheet to Sales Invoice/Project billing later; ApprovalService optional.

3. Testing (DevDatabaseTrait)
- Unit: ProjectServiceTest (progress), TaskServiceTest (status), TimesheetServiceTest (hours/billing), repo tests.
- Integration: API create project/task, log timesheet, summary.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Project/Task/Timesheet vận hành, tính giờ/billing cơ bản.
- Unit + integration tests pass.

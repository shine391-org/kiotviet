ERP-031 - Nhân sự & Payroll
Bạn là AI backend engineer phụ trách HR/Payroll như ERPNext.

1. Bối cảnh
- ERPNext có Employee, Leave, Attendance, Payroll Entry, Salary Slip. LanoCRM chưa có HR.

2. Phạm vi & Deliverables
- Migration: employees, leave_types, leave_applications, attendances, payroll_entries, salary_slips, salary_components.
- Validator: EmployeeValidator, LeaveValidator, PayrollValidator.
- Repository: EmployeeRepository, LeaveRepository, AttendanceRepository, PayrollRepository, SalarySlipRepository.
- Service: EmployeeService (CRUD), LeaveService (apply/approve, balance), AttendanceService (log), PayrollService (run payroll entry, generate salary slips, compute earnings/deductions), numbering helper.
- Controller API: manage employee; apply/approve leave; log attendance; run payroll; download salary slip data.
- Integration: AccountingService for GL posting; Tax/Withholding Service for deductions.

3. Testing (DevDatabaseTrait)
- Unit: LeaveServiceTest (balance), PayrollServiceTest (earn/deduct calc, slip gen), AttendanceServiceTest; repo tests.
- Integration: API run payroll, generate slips, GL posting.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- HR cơ bản + payroll chạy được, leave/attendance ghi nhận, salary slip có GL hook.
- Unit + integration tests pass.

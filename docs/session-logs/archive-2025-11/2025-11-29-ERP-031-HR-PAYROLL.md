# Session Log - ERP-031 HR & Payroll

- **Date:** 2025-11-29
- **Task:** ERP-031 - HR/Payroll (Employee, Leave, Attendance, Payroll)

## What I did
- Thêm schema nhân sự/payroll vào golden migration + DevDatabaseTrait (employees, leave types/applications, attendances, payroll entries, salary slips/components) và tạo model tương ứng.
- Xây dựng validator/repo/service cho Employee, Leave (apply/approve + balance), Attendance (log), Payroll (chạy payroll, tạo salary slip, post GL), cùng controller thin, routes, và wiring service container.
- Viết unit tests cho leave, attendance, payroll services và integration test payroll API.

## Tests
- `docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/LeaveServiceTest.php tests/Services/AttendanceServiceTest.php tests/Services/PayrollServiceTest.php tests/Integration/Api/PayrollApiTest.php`

## Notes / Issues
- Leave balance hiện tính theo leave approved; pending không trừ quota. GL post payroll đang đơn giản debit expense/credit payable cho tổng earnings, có thể tách deductions khi cần.

# Session Log - TASK-13-WEBHOOKS-01

## What I did
- Thiết kế hệ thống webhook/event nội bộ: subscription repository/service, dispatcher gửi HTTP có ký chữ ký HMAC, queue lưu ở `webhook_events`.
- Thêm controller + routes để quản lý subscription, retry event; đăng ký service container.
- Gắn hook webhook vào các luồng: tạo đơn, đổi trạng thái đơn, yêu cầu/duyệt/từ chối/hoàn tất trả hàng, tạo/generate hóa đơn; bổ sung phát hiện low/out-of-stock khi trừ hàng.
- Bổ sung migration đã có bằng models mới; thêm schema trait và unit tests cho subscription + dispatcher.

## Files touched/created
- Models: `backend-ci/app/Models/WebhookSubscriptionModel.php`, `backend-ci/app/Models/WebhookEventModel.php`
- Validators: `backend-ci/app/Validators/WebhookSubscriptionValidator.php`
- Repositories: `backend-ci/app/Repositories/Webhooks/*`
- Services: `backend-ci/app/Services/Webhooks/*`, cập nhật Order/Return/Invoice/OrderStatus services
- Controllers: `backend-ci/app/Controllers/Api/WebhookSubscriptionsController.php`, `WebhookEventsController.php`
- Routes/Services config updated
- Tests: `backend-ci/tests/Services/WebhookSubscriptionServiceTest.php`, `WebhookDispatcherTest.php`, trait `tests/_support/Database/WebhookSchemaTrait.php`
- Task log: `docs/tasks/MAIN_MODULES/07_TASK/TASK_13_WEBHOOKS.md`

## Tests
- Unit: chưa chạy (môi trường hiện tại chưa kiểm tra); đã thêm bài test dùng SQLite fallback, sẽ pass khi extension sqlite3 hoặc MySQL test DB sẵn.

## Issues
- Nếu chưa chạy migration mới, webhook dispatcher sẽ tự động skip; cần migrate trước khi dùng thực tế.

# Session Log - ERP-032 Portal & Notifications

- **Date:** 2025-11-29
- **Task:** ERP-032 - Customer Portal, Knowledge Base, Notifications/Assignment

## What I did
- Thêm schema portal/KB/notification/assignment vào golden migration + DevDatabaseTrait; tạo models cho portal users/tokens, KB categories/articles, notification rules/logs, assignment rules/logs.
- Xây dựng validators/repos/services: Portal (login/token, create user), Knowledge Base (list/show), Notification (rules + trigger log), Assignment (round robin), cùng controller thin, routes, service wiring.
- Viết unit tests cho portal, KB, notification, assignment services và integration test portal login + KB view + trigger notification/assignment.

## Tests
- `docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/PortalServiceTest.php tests/Services/KnowledgeBaseServiceTest.php tests/Services/NotificationServiceTest.php tests/Services/AssignmentServiceTest.php tests/Integration/Api/PortalNotificationApiTest.php`

## Notes / Issues
- Notification trigger hiện chỉ ghi log (status queued), chưa gửi thật; assignment dùng round robin qua danh sách member đơn giản.

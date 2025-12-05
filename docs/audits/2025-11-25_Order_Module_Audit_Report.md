# Báo cáo Audit Module "Order Workflow"

**Ngày:** 25/11/2025
**Người audit:** AI Agent Roo
**Phạm vi:** Toàn bộ các task trong `docs/tasks/MAIN_MODULES/07_TASK/` (trừ TASK_01, TASK_02) và file `TEST_CATALOG.md`.

---

## 1. Tóm tắt

Cuộc audit tập trung vào việc đánh giá chất lượng tài liệu, kiến trúc, và độ bao phủ test của module **Order Workflow**. Nhìn chung, module được tài liệu hóa rất tốt, tuân thủ chặt chẽ các nguyên tắc Clean Architecture, và có một bộ test khá toàn diện.

Tuy nhiên, có một số điểm cần cải thiện liên quan đến sự nhất quán của tài liệu, các khía cạnh kỹ thuật cụ thể trong code, và một vấn đề nghiêm trọng trong môi trường test cần được ưu tiên giải quyết.

## 2. Phân tích chi tiết

### 2.1. Chất lượng tài liệu (Documentation Quality)

**Điểm mạnh:**
*   **Rõ ràng và chi tiết:** Mỗi file `TASK_XX.md` đều mô tả rất chi tiết mục tiêu, yêu cầu (functional & non-functional), cấu trúc database (migration), kiến trúc (model, repository, service), và các endpoint API.
*   **Mẫu code (Code Samples):** Việc cung cấp các đoạn code mẫu cho từng lớp (Migration, Model, Service, Controller, Test) là cực kỳ hữu ích, giúp đảm bảo tính nhất quán khi phát triển.
*   **Tiêu chí chấp nhận (Acceptance Criteria):** Mỗi task đều có một checklist rõ ràng về các tiêu chí cần đạt, giúp việc kiểm thử và nghiệm thu trở nên dễ dàng.
*   **`TEST_CATALOG.md`:** File này là một bản tổng hợp tuyệt vời, cung cấp cái nhìn tổng quan về tất cả các test case, giúp đánh giá nhanh chóng độ bao phủ và các kịch bản đã được kiểm thử.

**Điểm cần cải thiện:**
*   **Trạng thái (Status):** Một số task có trạng thái "Done" nhưng vẫn còn các mục chưa được check trong `ACCEPTANCE CRITERIA`. Ví dụ, `TASK_01_PAYMENT_METHODS.md` có mục "`code` must be unique and uppercase" chưa được check. Cần đảm bảo tất cả các mục được check trước khi chuyển status sang "Done".
*   **Sự nhất quán trong tên định danh:** `TASK_03_RETURNS_SCHEMA.md` có `dependencies: "TASK-05-ORDER-CREATE-01"`, nhưng file tương ứng là `TASK_05_ORDER_CREATE.md`. Cần chuẩn hóa lại cách đặt ID và tham chiếu để tránh nhầm lẫn.
*   **Thiếu file `TEST_CATALOG.md` cho các task 01 và 02:** Mặc dù yêu cầu chỉ audit từ task 03, việc thiếu thông tin test cho 2 task đầu tiên trong `TEST_CATALOG.md` tạo ra một khoảng trống trong tài liệu tổng thể.

### 2.2. Kiến trúc & Thiết kế (Architecture & Design)

**Điểm mạnh:**
*   **Tuân thủ Clean Architecture:** Hệ thống phân chia rõ ràng các lớp Controller, Service, Repository, Validator. Controller rất "mỏng", chỉ làm nhiệm vụ routing, trong khi logic nghiệp vụ được đặt hoàn toàn trong Service.
*   **Single Responsibility Principle (SRP):** Mỗi lớp dường như chỉ đảm nhiệm một vai trò duy nhất. Ví dụ: `OrderStatusTransition` chỉ định nghĩa các trạng thái chuyển đổi, `InventoryMovementLogger` chỉ ghi log tồn kho, `VATCalculator` chỉ tính toán VAT.
*   **Sử dụng Transaction:** Các nghiệp vụ quan trọng như tạo đơn hàng, cập nhật trạng thái, và xử lý trả hàng đều được bao bọc trong transaction, đảm bảo tính toàn vẹn dữ liệu.
*   **Xử lý Concurrency:** Việc sử dụng `lockForUpdate` trong `InvoiceRepository::nextNumber` và `lock_version` trong `ReturnService::approve` cho thấy sự quan tâm đến việc xử lý các vấn đề về race condition.

**Điểm cần cải thiện:**
*   **Khóa bi quan (Pessimistic Locking) trong `OrderStatusService`:** `TASK_07_INVENTORY_HOOKS.md` ghi chú rằng `FOR UPDATE` không được CI4 Query Builder hỗ trợ trực tiếp và phải dùng raw query. Tuy nhiên, implementation hiện tại trong `adjustInventory` lại đang dựa vào atomic SQL updates (`SET quantity_on_hand = quantity_on_hand + ...`). Mặc dù cách này hoạt động trong nhiều trường hợp, nó không hoàn toàn an toàn bằng `SELECT ... FOR UPDATE`. Cần xem xét triển khai một giải pháp khóa bi quan thực sự để ngăn chặn tuyệt đối việc overselling trong các kịch bản có độ tương tranh cực cao.
*   **Thiếu sót trong PDF Generation:** `InvoicePDFGenerator` hiện tại chỉ lưu file HTML. Đây là một placeholder và cần được thay thế bằng một thư viện tạo PDF thực thụ (ví dụ: `dompdf/dompdf` hoặc `mpdf/mpdf`).
*   **`db_` prefix trong test schema:** Các schema trait (`ReturnSchemaTrait`, `InvoiceSchemaTrait`, ...) đều tạo ra các bảng với prefix `db_`. Đây có thể là một quy ước của dự án, nhưng nó làm phức tạp hóa các câu query trong test và có thể gây nhầm lẫn. Cần làm rõ lý do hoặc xem xét loại bỏ nếu không thực sự cần thiết.

### 2.3. Chất lượng & Độ bao phủ Test (Test Quality & Coverage)

**Điểm mạnh:**
*   **Phân loại Test rõ ràng:** Hệ thống có cả Unit Test (cho Service/Repository) và Integration/Feature Test (cho API), tuân thủ theo kim tự tháp testing.
*   **Độ bao phủ kịch bản tốt:** `TEST_CATALOG.md` cho thấy một danh sách rất đầy đủ các kịch bản test, từ các luồng chính (happy paths) đến các trường hợp biên (edge cases) và các lỗi dự kiến.
*   **Sử dụng Test Double:** Có dấu hiệu của việc sử dụng mock (ví dụ `Mail::fake()`, `Http::fake()`) trong các bài test webhook/event, giúp cô lập các thành phần và tăng tốc độ test.

**Điểm cần cải thiện:**
*   **VẤN ĐỀ NGHIÊM TRỌNG - Môi trường Test không hoạt động:** Đây là phát hiện quan trọng nhất. Toàn bộ nỗ lực sửa lỗi trong quá trình audit đều thất bại do một lỗi `Fatal error: Trait ... not found` không thể giải quyết. Lỗi này xảy ra ngay cả khi đã sửa `composer.json` và chạy `dump-autoload`. **Đây là một BLOKER** và cần được ưu tiên hàng đầu để đội ngũ lập trình viên con người gỡ lỗi. Môi trường test không ổn định sẽ làm giảm chất lượng và tăng rủi ro cho toàn bộ dự án.
*   **Cảnh báo PSR-4 Autoloading:** Lệnh `composer dump-autoload` hiển thị nhiều cảnh báo về việc các file test không tuân thủ chuẩn PSR-4. Mặc dù Composer vẫn hoạt động, việc này cho thấy sự thiếu nhất quán trong cấu trúc thư mục và namespace. Cần refactor lại các file này để tuân thủ tiêu chuẩn.
*   **Thiếu Test cho Concurrency:** Mặc dù code có cơ chế locking, `TEST_CATALOG.md` không đề cập rõ ràng đến các bài test được thiết kế để mô phỏng sự tương tranh cao (ví dụ: sử dụng `amphp/parallel` hoặc các công cụ tương tự để tạo nhiều request đồng thời). Các bài test hiện tại chỉ "mô phỏng" một cách gián tiếp.

## 3. Đề xuất & Hành động

**Ưu tiên cao (Blockers):**
1.  **Sửa lỗi môi trường Test:** **(Hành động ngay lập tức)** Đội ngũ backend cần tập trung toàn lực để điều tra và khắc phục lỗi `Trait not found` trong môi trường test Docker. Không có môi trường test ổn định, mọi hoạt động phát triển khác đều gặp rủi ro.

**Ưu tiên trung bình:**
2.  **Chuẩn hóa cấu trúc Test:** Refactor lại cấu trúc thư mục và namespace của các file test để tuân thủ chuẩn PSR-4 và loại bỏ các cảnh báo của Composer.
3.  **Implement Pessimistic Locking:** Thay thế phương pháp `SET quantity = quantity - ...` trong `OrderStatusService` bằng một giải pháp `SELECT ... FOR UPDATE` thực sự để đảm bảo an toàn tuyệt đối khi trừ tồn kho.
4.  **Hoàn thiện PDF Generator:** Tích hợp một thư viện tạo PDF chuyên dụng để thay thế cho placeholder hiện tại.
5.  **Rà soát tài liệu:** Cập nhật lại trạng thái và các mục `ACCEPTANCE CRITERIA` trong các file TASK để đảm bảo chúng phản ánh đúng thực tế. Chuẩn hóa lại cách đặt ID và tham chiếu.

**Ưu tiên thấp:**
6.  **Thêm Test cho Concurrency:** Viết các bài test chuyên dụng để kiểm tra các cơ chế locking dưới tải tương tranh cao.
7.  **Làm rõ quy ước `db_` prefix:** Thảo luận trong team về sự cần thiết của prefix `db_` trong test schema và xem xét việc loại bỏ nó để đơn giản hóa.

---
**Ký tên,**

AI Agent Roo
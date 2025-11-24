TASK-002: IMAGE MANAGER – MEDIA LIBRARY + CHỐNG DUPLICATE
MỤC TIÊU
Cho phép hiển thị toàn bộ ảnh từ media library cho user khi chọn ảnh cho sản phẩm.

Khi attach ảnh vào sản phẩm, không được để trùng nhiều lần (duplicate attachment).

Hiển thị rõ message khi có ảnh bị skip vì đã gắn rồi.

YÊU CẦU CHỨC NĂNG
Backend:
API trả về được toàn bộ ảnh có trong library

Với mỗi ảnh, trả về trạng thái: đã gắn vào sản phẩm này hay chưa

Khi attach ảnh vào sản phẩm, API phải kiểm tra duplicate, chỉ thêm ảnh chưa có

Message trả về rõ ràng: số lượng ảnh được attach mới, số ảnh bị skip, danh sách IDs

Support pagination (20 items/page)

Frontend:
Hiển thị tất cả ảnh media library để chọn

Đánh dấu ảnh nào đã có trong sản phẩm vs ảnh chưa có

Chọn "Thêm tất cả" chỉ lấy ảnh chưa attach

Khi user attach, hiện message: "Đã thêm X ảnh, Y ảnh bị bỏ qua (đã tồn tại)"

Pagination hoạt động mượt

TEST CASES
Backend:
API list media library: Trả về đúng số ảnh + flag is_attached đúng

API attach images: Chỉ ảnh chưa có mới được thêm, không duplicate

Response message: Đúng format, đếm số đúng

Performance: Load 1000+ ảnh < 200ms

Frontend:
UI hiển thị đầy đủ ảnh, đánh dấu đúng

Chọn tất cả: Chỉ chọn ảnh chưa attach

Message hiển thị đúng sau khi attach

Pagination hoạt động

✅ DEFINITION OF DONE (BẮT BUỘC)
Backend:
 Code implement xong, reuse codebase hiện có

 Unit tests viết xong cho logic duplicate check

 Tests chạy pass 100%

 API test thủ công pass (Postman/curl)

 Performance test: Load 1000+ ảnh < 200ms

Frontend:
 Code implement xong, reuse components hiện có

 Manual test trên UI: Attach ảnh, message hiển thị đúng

 Không có lỗi console

 Test pagination với nhiều ảnh

Bàn giao:
 Code review approved

 Tests pass (chạy composer test hoặc tương đương)

 Demo live cho lead/PM


⚠️ KHÔNG được bàn giao nếu tests chưa pass hoặc chưa có tests.

LƯU Ý
Xem code quản lý ảnh/media hiện có trước khi viết

Reuse model/repository/service/component đã có

Không copy code mẫu từ nguồn ngoài

Hỏi nếu không chắc về structure
# FE Shipments UI - 2025-11-27

## 🎯 Mục tiêu
- Dựng trang Vận đơn (KiotViet-style) cho Lano CRM front-end.
- Thêm Redux slice + mock API để xem/lọc vận đơn, panel chi tiết, xuất CSV.
- Viết unit tests cho slice và trang.

## ✅ Đã làm
- Tạo constants/status & mock data Vận đơn, API giả lập lọc/phân trang.
- Thêm Redux slice `shipments` + kết nối store.
- Xây UI: bộ lọc trái, bảng vận đơn có ẩn/hiện cột, tổng COD, panel chi tiết (tabs).
- Thêm route `/orders/shipments`, cập nhật Sidebar/Top nav.
- Viết tests: slice + page (React Testing Library + Vitest).

## 📂 Files chính
- `lanocrm/src/constants/shipments.js`
- `lanocrm/src/mock/shipments.js`
- `lanocrm/src/api/shipmentApi.js`
- `lanocrm/src/store/slices/shipmentSlice.js`
- `lanocrm/src/components/shipments/*`
- `lanocrm/src/pages/orders/ShipmentListPage.jsx`
- `lanocrm/src/pages/orders/ShipmentListPage.module.css`
- `lanocrm/src/store/slices/shipmentSlice.test.js`
- `lanocrm/src/pages/orders/ShipmentListPage.test.jsx`
- `lanocrm/src/App.jsx`, `lanocrm/src/components/Layout/Sidebar.jsx`, `lanocrm/src/store/index.js`

## 🧪 Tests
- `npm test -- src/store/slices/shipmentSlice.test.js src/pages/orders/ShipmentListPage.test.jsx`

## ⚠️ Lưu ý
- API đang dùng mock nội bộ; khi backend sẵn, set `USE_MOCK=false` trong `shipmentApi`.
- Cảnh báo antd `Space direction deprecated` xuất hiện trong test (không chặn kết quả).

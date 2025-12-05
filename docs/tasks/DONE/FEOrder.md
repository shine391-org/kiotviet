---
title: "FEOrder - Frontend Order Management Page"
id: "FEORDER-ORDER-MANAGEMENT"
priority: "P1"
status: "In Progress"
module: "Frontend"
type: "Implementation"
tags: ["frontend", "order-management", "ui", "react", "ant-design"]
purpose: "Frontend requirements and specifications for Order Management page in LanoCRM system"
location: "docs/tasks"

# Relationships
dependencies: "TASK-05-ORDER-CREATE-CI4, ORD-003-ORDER-STATUS-MANAGEMENT"
related_to: "FE-CASH, CASH-001, TASK-07-INVENTORY-HOOKS-CI4"
implements: "ORDER-MANAGEMENT-UI-REQUIREMENTS"
part_of: "FRONTEND-ORDER-MODULE"

# Metadata
author: "Frontend Team"
created_date: "2025-11-27"
last_updated: "2025-11-27"
version: "1.0"
estimated_effort: "5 days"
actual_effort: ""
complexity: "Medium"
risk_level: "Low"

# Testing Information
test_coverage: "0%"
test_files: []
integration_tests: "No"

# Deployment Information
deployment_status: "Not Started"
deployment_date: ""
rollback_plan: "Yes"

# Documentation Network
links_to: ["TASK-05-ORDER-CREATE-CI4", "ORD-003-ORDER-STATUS-MANAGEMENT", "FE-CASH"]
linked_from: []
---

# Mô tả Trang "Đặt hàng" cho Team Frontend
Đây là trang quản lý đơn hàng (Order Management) trong hệ thống LanoCRM.​

Header & Navigation
Logo và menu chính ở góc trên trái với link "Phần mềm quản lý bán hàng"

Top bar bao gồm: dropdown chọn theme, link hỗ trợ, link giao diện cũ, selector ngôn ngữ (Tiếng Việt), selector chi nhánh (Lano - HN), và icon thông báo/tài khoản

Sidebar menu với các mục: Tổng quan, Sổ quỹ, Bán online, và nút "Bán hàng" nổi bật

Thanh công cụ chính
Search input với placeholder "Theo mã phiếu đặt"

Nút "+ Đặt hàng" (primary action) - link đến /sale/#/?cart=Order

Nút "Xuất file" để export dữ liệu

Icon cài đặt (link đến Settings) và icon trợ giúp

Panel Filter (Sidebar trái)
Các bộ lọc có thể xóa (delete icon) và xếp theo thứ tự:

Chi nhánh xử lý: Dropdown với giá trị mặc định "Lano - HN"

Thời gian: Tab "Năm nay" / "Tùy chỉnh"

Trạng thái: Multi-select với các pill hiển thị (Phiếu tạm, Đang giao hàng, Hoàn thành) và indicator "+1 khác"

Đối tác giao hàng: Dropdown placeholder

Thời gian giao hàng: Tab "Toàn thời gian" / "Tùy chỉnh"

Khu vực giao hàng: Dropdown "Tỉnh/TP - Quận/Huyện"

Phương thức thanh toán: Dropdown placeholder

Người tạo: Dropdown placeholder

Người nhận đặt: Dropdown placeholder

Kênh bán: Dropdown với link "Tạo mới"

Data Table
Tổng kết trên header: Hiển thị số tiền tổng "19,900,000" và "5,100,000"​

Columns (có sắp xếp):

Checkbox (select all/individual)

Mã đặt hàng (sortable)

Thời gian (sortable)

Khách hàng (sortable)

Khách cần trả (sortable)

Khách đã trả (sortable)

Trạng thái (badge/tag UI)

Action icons (cuối mỗi row)

Sample data rows (7 đơn hàng):

DH000044 - 19/11/2025 - Khách lẻ - 9,000,000đ - 0đ - Phiếu tạm

DH000043 - 29/10/2025 - A.Triệu - 2,950,000đ - 1,500,000đ - Phiếu tạm

DH000042 - 07/09/2025 - Chị Vân - 1,550,000đ - 700,000đ - Phiếu tạm

DH000041 - 24/07/2025 - Anh Chính - 2,850,000đ - 1,000,000đ - Phiếu tạm

DH000040 - 02/07/2025 - A bình - 950,000đ - 300,000đ - Hoàn thành

DH000038 - 17/04/2025 - Anh Đoàn - 900,000đ - 900,000đ - Phiếu tạm

DH000037 - 17/04/2025 - Anh Hà - 1,700,000đ - 700,000đ - Phiếu tạm

Footer
Pagination: "Hiển thị" + dropdown select "15 dòng"​

Ghi chú kỹ thuật
Layout dạng left sidebar filter + main content area

Số tiền format theo VNĐ với dấu phẩy phân cách

Date format: DD/MM/YYYY HH:mm

Trạng thái có 3+ values (cần confirm list đầy đủ từ backend)

Filter panel có thể collapse/expand (cần confirm behavior)
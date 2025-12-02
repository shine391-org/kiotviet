---
title: "ERPNext Integration Hub"
id: "ERPNEXT-HUB-01"
priority: "P1"
status: "Active"
module: "ERPNext Integration"
type: "Navigation"
tags: ["erpnext", "integration", "analysis", "plan", "tasks"]
purpose: "Trung tâm liên kết các tài liệu ERPNext (phân tích, so sánh, kế hoạch) để theo dõi triển khai."
location: "docs/erpnext"
related_to: "AGENT-GUIDE-01, IMPLEMENTATION-PLAN-ERPNEXT-FEATURES"
author: "Documentation Team"
created_date: "2025-11-28"
last_updated: "2025-11-28"
version: "1.0"
complexity: "Low"
risk_level: "Low"
---

# ERPNext Integration Hub

Trung tâm tập hợp tài liệu ERPNext để dễ tra cứu khi triển khai vào LanoCRM.

## Tài liệu hiện có
- [Nguồn tính năng ERPNext](../analysis/ERPNEXT-FEATURES-SOURCES.md)
- [So sánh ERPNext vs LanoCRM](../analysis/ERPNEXT-LANOCRM-COMPARISON.md)
- [Kế hoạch triển khai tính năng ERPNext](../tasks/IMPLEMENTATION-PLAN-ERPNEXT-FEATURES.md)

## Tasks Phase 1 (Inventory/Fulfillment + Approval)
- [ERP-001 - Batch/Serial/Expiry Tracking](../tasks/ERPNext/ERP-001-BATCH-SERIAL.md)
- [ERP-002 - Delivery Note Flow](../tasks/ERPNext/ERP-002-DELIVERY-NOTE.md)
- [ERP-003 - Workflow/Approval Service](../tasks/ERPNext/ERP-003-APPROVAL-WORKFLOW.md)
- [ERP-004 - Stock Ledger & Reconciliation](../tasks/ERPNext/ERP-004-STOCK-LEDGER-RECON.md)

## Tasks Phase 2 (Pricing, Planning, Quality, Templates)
- [ERP-005 - Advanced Pricing](../tasks/ERPNext/ERP-005-ADV-PRICING.md)
- [ERP-006 - Reorder Level Planning](../tasks/ERPNext/ERP-006-REORDER-PLANNING.md)
- [ERP-007 - Quality Management](../tasks/ERPNext/ERP-007-QUALITY-MGMT.md)
- [ERP-008 - Order Templates](../tasks/ERPNext/ERP-008-ORDER-TEMPLATE.md)

## Tasks Phase 3 (Manufacturing & Integrations)
- [ERP-009 - Manufacturing Core](../tasks/ERPNext/ERP-009-MANUFACTURING-CORE.md)
- [ERP-010 - E-commerce Integration](../tasks/ERPNext/ERP-010-ECOM-INTEGRATION.md)
- [ERP-011 - Subscription Management](../tasks/ERPNext/ERP-011-SUBSCRIPTION.md)

## Tasks POS Parity
- [ERP-012 - POS Profile, Payment Split & Shift Closing](../tasks/ERPNext/ERP-012-POS-PROFILE-SHIFT.md)
- [ERP-013 - POS Offline Queue & Sync](../tasks/ERPNext/ERP-013-POS-OFFLINE-SYNC.md)
- [ERP-014 - POS Loyalty & Coupon Discounts](../tasks/ERPNext/ERP-014-POS-LOYALTY-COUPON.md)
- [ERP-015 - POS Payment Entry & Tax/Charge Integration](../tasks/ERPNext/ERP-015-POS-GL-PAYMENT.md)

## Tasks CRM Parity
- [ERP-016 - CRM Lead → Opportunity → Quotation](../tasks/ERPNext/ERP-016-CRM-LEAD-OPP-QUOTE.md)
- [ERP-017 - CRM Campaign & Email Campaign](../tasks/ERPNext/ERP-017-CRM-CAMPAIGN-EMAIL.md)
- [ERP-018 - CRM Contract & Appointment](../tasks/ERPNext/ERP-018-CRM-CONTRACT-APPT.md)
- [ERP-019 - Support Ticket & Communication Log](../tasks/ERPNext/ERP-019-SUPPORT-TICKET-COMMS.md)

## Tasks Accounting Parity
- [ERP-021 - Accounting Core: Chart of Accounts & GL Entry](../tasks/ERPNext/ERP-021-ACCOUNTING-GL-COA.md)
- [ERP-022 - Sales Invoice & Tax Template](../tasks/ERPNext/ERP-022-ACCOUNTING-SALES-INVOICE.md)
- [ERP-023 - Purchase Invoice & Tax](../tasks/ERPNext/ERP-023-ACCOUNTING-PURCHASE-INVOICE.md)
- [ERP-024 - Payment Entry & Bank Reconciliation](../tasks/ERPNext/ERP-024-ACCOUNTING-PAYMENT-BANK.md)
- [ERP-025 - Tax Templates, Withholding, Credit Control](../tasks/ERPNext/ERP-025-ACCOUNTING-TAX-CREDIT.md)
- [ERP-026 - Multi-currency & Aging Reports](../tasks/ERPNext/ERP-026-ACCOUNTING-MULTICURRENCY-AGING.md)

## Tasks Buying & Warehouse nâng cao
- [ERP-027 - Mua hàng: PO, Nhập kho, Landed Cost, Thầu phụ](../tasks/ERPNext/ERP-027-BUYING-GRN-SUBCON.md)
- [ERP-028 - Stock Entry nâng cao (Issue/Receipt/Transfer/Returns)](../tasks/ERPNext/ERP-028-STOCK-ENTRY-ADV.md)

## Tasks Dự án, Tài sản, Nhân sự
- [ERP-029 - Dự án, Công việc, Timesheet](../tasks/ERPNext/ERP-029-PROJECT-TIMESHEET.md)
- [ERP-030 - Tài sản & Bảo trì](../tasks/ERPNext/ERP-030-ASSET-MAINTENANCE.md)
- [ERP-031 - Nhân sự & Payroll](../tasks/ERPNext/ERP-031-HR-PAYROLL.md)

## Tasks Portal, Quyền, Scheduler, Báo cáo, Thuế địa phương
- [ERP-032 - Cổng khách hàng & Thông báo/Assignment](../tasks/ERPNext/ERP-032-PORTAL-NOTIFY.md)
- [ERP-033 - Đa công ty & Phân quyền tài liệu](../tasks/ERPNext/ERP-033-MULTICOMPANY-PERM.md)
- [ERP-034 - Scheduler/Background Jobs](../tasks/ERPNext/ERP-034-SCHEDULER-JOBS.md)
- [ERP-035 - Báo cáo Tài chính & Tồn kho chuẩn](../tasks/ERPNext/ERP-035-REPORTS-FINANCE-STOCK.md)
- [ERP-036 - Tuân thủ thuế địa phương (GST/VAT/Withholding nâng cao)](../tasks/ERPNext/ERP-036-TAX-LOCAL-COMPLIANCE.md)

## Thứ tự ưu tiên (phụ thuộc)
1) Nền tảng: ERP-003 (Approval) → ERP-001 (Batch/Serial) → ERP-004 (Ledger/Reconciliation) → ERP-002 (Delivery Note).
2) Giá/Hoạch định: ERP-005, ERP-006 (Pricing, Reorder) + ERP-007, ERP-008 nếu cần song song.
3) POS: ERP-012 → ERP-015 (profile/shift, offline, loyalty/coupon, payment/tax).
4) CRM: ERP-016 → ERP-019 (lead/opportunity/quotation, campaign, contract, ticket).
5) Accounting: ERP-021 → ERP-026 (COA/GL, sales/purchase invoice, payment/bank, tax/credit, đa tiền tệ/aging).
6) Buying/Warehouse nâng cao: ERP-027 → ERP-028 (PO/GRN/LCV/subcon, stock entry nâng cao).
7) Manufacturing/E-com/Subscription: ERP-009 → ERP-011.
8) Project/Asset/HR: ERP-029 → ERP-031.
9) Portal/Quyền/Nền tảng: ERP-032 → ERP-036 (portal/notify, multi-company/ACL, scheduler, báo cáo, thuế địa phương).

## Quyết định đã chốt
- Ưu tiên Phase 1 (Inventory/Fulfillment) trước CRM; dựng nền tảng trước khi làm module phụ thuộc.
- Workflow/Approval service (ERP-003) làm trước order/delivery; các task Phase 1 đã tách sẵn (ERP-001..004).

## Gợi ý tiếp theo
- Gán owner và kick-off theo thứ tự ưu tiên ở trên; bắt đầu từ ERP-003 → ERP-001 → ERP-004 → ERP-002.
- Với mỗi task: tuân AGENTS.md, viết unit + integration tests (DevDatabaseTrait), cập nhật session log/tài liệu liên quan nếu phát sinh.

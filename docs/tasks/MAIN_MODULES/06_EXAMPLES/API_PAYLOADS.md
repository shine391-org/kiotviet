---
title: "API Payloads Examples - Order Workflow"
id: "API-PAYLOADS-EXAMPLES-01"
module: "Order Workflow"
last_updated: "2025-11-24"
version: "1.0"
type: "API Examples"
tags: ["API", "payloads", "examples", "requests", "responses", "order-workflow"]
purpose: "Provides example API request and response payloads for key operations within the Order Workflow module, including creating orders (POS, Shipping), updating order status, creating/approving returns, creating invoices, and downloading invoice PDFs."
location: "docs/tasks/MAIN_MODULES/06_EXAMPLES"
related_to:
  - id: "ORDERS-TABLE-01"
    description: "Related to order creation and updates."
  - id: "INVOICES-TABLES-01"
    description: "Related to invoice creation and PDF."
  - id: "RETURNS-TABLES-01"
    description: "Related to return creation and approval."
  - id: "ORDER-RULES-01"
    description: "Shows validation error responses for order operations."
  - id: "RETURN-RULES-01"
    description: "Shows validation error responses for return operations."
  - id: "INVOICE-RULES-01"
    description: "Shows validation error responses for invoice operations."
  - id: "SAMPLE-DATA-EXAMPLES-01"
    description: "Complementary sample data for testing these payloads."
  - id: "SQL-QUERIES-EXAMPLES-01"
    description: "Complementary SQL queries for data verification."
---

# API Payloads Examples

**Module:** Order Workflow

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này cung cấp **API request/response examples** cho Order Workflow.

---

## 📭 CREATE ORDER - POS

### **Request**

```json
POST /api/orders
Content-Type: application/json

{
  "customer_id": 1,
  "branch_id": 1,
  "order_type": "POS",
  "payment_method": "CASH",
  "items": [
    {
      "product_id": 101,
      "variant_id": 201,
      "quantity": 1,
      "price": 25000000
    },
    {
      "product_id": 102,
      "variant_id": 202,
      "quantity": 1,
      "price": 5000000
    }
  ],
  "discount": 3000000,
  "notes": "Khách quen, giảm 10%"
}
```

---

### **Response**

```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-000001",
    "customer": {
      "id": 1,
      "name": "Nguyễn Văn A",
      "phone": "0912345678"
    },
    "branch": {
      "id": 1,
      "code": "HN",
      "name": "Chi nhánh Hà Nội"
    },
    "order_type": "POS",
    "payment_method": "CASH",
    "items": [
      {
        "id": 1,
        "product_id": 101,
        "product_name": "iPhone 15 Pro",
        "variant_name": "256GB Tím",
        "sku": "IP15P-256-PUR",
        "quantity": 1,
        "price": 25000000,
        "subtotal": 25000000
      },
      {
        "id": 2,
        "product_id": 102,
        "product_name": "AirPods Pro 2",
        "variant_name": null,
        "sku": "APP2-GEN2",
        "quantity": 1,
        "price": 5000000,
        "subtotal": 5000000
      }
    ],
    "subtotal": 30000000,
    "discount": 3000000,
    "shipping_fee": 0,
    "total": 27000000,
    "paid_amount": 27000000,
    "is_paid": true,
    "debt_amount": 0,
    "status": "completed",
    "created_at": "2024-11-24T10:30:00+07:00",
    "updated_at": "2024-11-24T10:30:00+07:00"
  }
}
```

---

## 🚚 CREATE ORDER - SHIPPING

### **Request**

```json
POST /api/orders
Content-Type: application/json

{
  "customer_id": 2,
  "branch_id": 1,
  "order_type": "SHIPPING",
  "payment_method": "COD",
  "items": [
    {
      "product_id": 103,
      "variant_id": 203,
      "quantity": 1,
      "price": 25000000
    }
  ],
  "shipping": {
    "name": "Trần Thị B",
    "phone": "0923456789",
    "address": "456 Hoàng Hoa Thám",
    "ward": "Phường 12",
    "district": "Quận Tân Bình",
    "city": "TP.HCM"
  },
  "notes": "Giao giờ hành chính"
}
```

---

### **Response**

```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "id": 2,
    "order_number": "ORD-000002",
    "order_type": "SHIPPING",
    "payment_method": "COD",
    "subtotal": 25000000,
    "discount": 0,
    "shipping_fee": 30000,
    "total": 25030000,
    "paid_amount": 0,
    "is_paid": false,
    "debt_amount": 25030000,
    "cod_amount": 25030000,
    "status": "draft",
    "shipping": {
      "name": "Trần Thị B",
      "phone": "0923456789",
      "address": "456 Hoàng Hoa Thám",
      "ward": "Phường 12",
      "district": "Quận Tân Bình",
      "city": "TP.HCM"
    },
    "created_at": "2024-11-24T11:00:00+07:00"
  }
}
```

---

## ✅ UPDATE ORDER STATUS

### **Request**

```json
PATCH /api/orders/2/status
Content-Type: application/json

{
  "status": "confirmed",
  "notes": "Khách xác nhận đặt hàng"
}
```

---

### **Response**

```json
{
  "success": true,
  "message": "Order status updated",
  "data": {
    "id": 2,
    "order_number": "ORD-000002",
    "status": "confirmed",
    "previous_status": "draft",
    "updated_at": "2024-11-24T11:15:00+07:00"
  }
}
```

---

## 🔄 CREATE RETURN

### **Request**

```json
POST /api/returns
Content-Type: application/json

{
  "order_id": 2,
  "reason": "defective",
  "reason_detail": "Chuột bị lỗi, không kết nối được",
  "items": [
    {
      "order_item_id": 4,
      "quantity_returned": 1
    }
  ]
}
```

---

### **Response**

```json
{
  "success": true,
  "message": "Return request created",
  "data": {
    "id": 1,
    "return_number": "TH-2-1",
    "order": {
      "id": 2,
      "order_number": "ORD-000002"
    },
    "reason": "defective",
    "reason_detail": "Chuột bị lỗi, không kết nối được",
    "items": [
      {
        "id": 1,
        "product_name": "Magic Mouse",
        "quantity_returned": 1,
        "price": 2000000
      }
    ],
    "return_amount": 2000000,
    "status": "pending",
    "created_at": "2024-11-24T12:00:00+07:00"
  }
}
```

---

## ✅ APPROVE RETURN

### **Request**

```json
PATCH /api/returns/1/approve
Content-Type: application/json

{
  "refund_shipping_fee": true,
  "refund_method": "bank_transfer",
  "notes": "Đồng ý trả hàng, hoàn tiền trong 3 ngày"
}
```

---

### **Response**

```json
{
  "success": true,
  "message": "Return approved",
  "data": {
    "id": 1,
    "return_number": "TH-2-1",
    "status": "approved",
    "return_amount": 2000000,
    "refund_shipping_fee": true,
    "refund_amount": 2030000,
    "refund_method": "bank_transfer",
    "approved_by": {
      "id": 3,
      "name": "Admin User"
    },
    "approved_at": "2024-11-24T12:30:00+07:00"
  }
}
```

---

## 🧾 CREATE INVOICE

### **Request**

```json
POST /api/invoices
Content-Type: application/json

{
  "customer_id": 4,
  "branch_id": 1,
  "order_ids": [1, 3, 5],
  "issue_date": "2024-11-30",
  "due_date": "2024-12-30",
  "vat_rate": 0.10,
  "notes": "Hóa đơn tháng 11/2024 - Gộp 3 đơn hàng"
}
```

---

### **Response**

```json
{
  "success": true,
  "message": "Invoice created successfully",
  "data": {
    "id": 1,
    "invoice_number": "HD-1-0001",
    "customer": {
      "id": 4,
      "name": "Công ty TNHH ABC",
      "tax_code": "0123456789"
    },
    "branch": {
      "id": 1,
      "name": "Chi nhánh Hà Nội"
    },
    "orders": [
      {
        "id": 1,
        "order_number": "ORD-000001",
        "total": 27000000
      },
      {
        "id": 3,
        "order_number": "ORD-000003",
        "total": 15000000
      },
      {
        "id": 5,
        "order_number": "ORD-000005",
        "total": 8000000
      }
    ],
    "issue_date": "2024-11-30",
    "due_date": "2024-12-30",
    "subtotal": 50000000,
    "vat_rate": 0.10,
    "vat_amount": 5000000,
    "total": 55000000,
    "pdf_path": null,
    "created_at": "2024-11-30T09:00:00+07:00"
  }
}
```

---

## 📄 DOWNLOAD INVOICE PDF

### **Request**

```
GET /api/invoices/1/pdf
```

---

### **Response**

```json
{
  "success": true,
  "message": "PDF generated successfully",
  "data": {
    "pdf_url": "https://storage.example.com/invoices/2024/HD-1-0001.pdf",
    "pdf_path": "invoices/2024/HD-1-0001.pdf",
    "file_size": 245678,
    "generated_at": "2024-11-30T10:00:00+07:00"
  }
}
```

---

## ❌ ERROR RESPONSES

### **Validation Error**

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "customer_id": ["Customer is required"],
    "items": ["Order must have at least one item"],
    "payment_method": ["POS orders must use CASH payment"]
  }
}
```

---

### **Insufficient Stock**

```json
{
  "success": false,
  "message": "Insufficient stock",
  "error_code": "ORD_INSUFFICIENT_STOCK",
  "data": {
    "product_id": 101,
    "product_name": "iPhone 15 Pro",
    "requested": 5,
    "available": 2
  }
}
```

---

### **Invalid Status Transition**

```json
{
  "success": false,
  "message": "Invalid status transition",
  "error_code": "ORD_INVALID_STATUS_TRANSITION",
  "data": {
    "current_status": "completed",
    "requested_status": "processing",
    "allowed_transitions": []
  }
}
```

---

## 🔗 RELATED DOCUMENTS

- [**SAMPLE_[DATA.md](http://DATA.md)]** - Sample data examples
- [**SQL_[QUERIES.md](http://QUERIES.md)]** - Common SQL queries
- [**ORDER_](https://www.notion.so/ORDER_RULES-Order-Validation-Rules-2b69907faaac48bdba1d2159793193c3?pvs=21)[RULES.md](http://RULES.md)** - Validation rules
const XLSX = require('xlsx');
const fs = require('fs');
const path = require('path');

const EXPORT_DIR = '/home/shine/projects/kiotviet/archive/kiotviet-export';
const OUTPUT_DIR = '/home/shine/projects/kiotviet/backend-ci/app/Database/Seeds/Data';

// Create output dir if needed
if (!fs.existsSync(OUTPUT_DIR)) {
    fs.mkdirSync(OUTPUT_DIR, { recursive: true });
}

// Excel date to JS Date
const excelToDate = (serial) => {
    if (!serial || typeof serial !== 'number') return null;
    const d = new Date((serial - 25569) * 86400 * 1000);
    return d.toISOString().slice(0, 19).replace('T', ' ');
};

const excelToDateOnly = (serial) => {
    if (!serial || typeof serial !== 'number') return null;
    const d = new Date((serial - 25569) * 86400 * 1000);
    return d.toISOString().slice(0, 10);
};

// 1. Products
console.log('=== Converting Products ===');
const productWb = XLSX.readFile(path.join(EXPORT_DIR, 'DanhSachSanPham_KV10122025-172048-544.xlsx'));
const productData = XLSX.utils.sheet_to_json(productWb.Sheets[productWb.SheetNames[0]]);
const products = productData.map(r => ({
    code: r['Mã hàng'] || null,
    name: r['Tên hàng'] || null,
    barcode: r['Mã vạch'] || null,
    category: r['Nhóm hàng'] || null,
    unit: r['Đơn vị tính'] || null,
    cost_price: parseFloat(r['Giá vốn']) || 0,
    selling_price: parseFloat(r['Giá bán']) || 0,
    weight: parseFloat(r['Trọng lượng']) || null,
    description: r['Mô tả'] || null,
    status: r['Trạng thái'] === 'Đang kinh doanh' ? 'ACTIVE' : 'INACTIVE',
    inventory_quantity: parseInt(r['Tồn kho']) || 0,
})).filter(r => r.code);
fs.writeFileSync(path.join(OUTPUT_DIR, 'products_export.json'), JSON.stringify(products, null, 2));
console.log(`  Exported ${products.length} products`);

// 2. Suppliers
console.log('\n=== Converting Suppliers ===');
const supplierWb = XLSX.readFile(path.join(EXPORT_DIR, 'DanhSachNhaCungCap_KV10122025-172712-174.xlsx'));
const supplierData = XLSX.utils.sheet_to_json(supplierWb.Sheets[supplierWb.SheetNames[0]]);
const suppliers = supplierData.map(r => ({
    code: r['Mã NCC'] || null,
    name: r['Tên nhà cung cấp'] || r['Tên NCC'] || null,
    phone: r['Điện thoại'] || null,
    email: r['Email'] || null,
    address: r['Địa chỉ'] || null,
    tax_code: r['Mã số thuế'] || null,
    contact_person: r['Người liên hệ'] || null,
    debt_amount: parseFloat(r['Nợ cần trả hiện tại']) || 0,
    status: 'ACTIVE',
    partner_type: 'SUPPLIER',
})).filter(r => r.code || r.name);
fs.writeFileSync(path.join(OUTPUT_DIR, 'suppliers_export.json'), JSON.stringify(suppliers, null, 2));
console.log(`  Exported ${suppliers.length} suppliers`);

// 3. Invoices (Orders) with Shipping Data
console.log('\n=== Converting Invoices ===');
const invoiceWb = XLSX.readFile(path.join(EXPORT_DIR, 'DanhSachHoaDon_KV10122025-173032-523.xlsx'));
const invoiceData = XLSX.utils.sheet_to_json(invoiceWb.Sheets[invoiceWb.SheetNames[0]]);
const invoices = invoiceData.map(r => ({
    code: r['Mã hóa đơn'] || null,
    order_number: r['Mã hóa đơn'] || null,
    customer_code: r['Mã KH'] || null,
    customer_name: r['Khách hàng'] || null,
    customer_phone: r['Điện thoại'] || null,
    branch_name: r['Chi nhánh'] || null,
    total: parseFloat(r['Tổng tiền hàng']) || 0,
    discount: parseFloat(r['Giảm giá']) || 0,
    final_total: parseFloat(r['Khách cần trả']) || parseFloat(r['Tổng tiền hàng']) || 0,
    paid_amount: parseFloat(r['Khách đã trả']) || 0,
    payment_method: r['Phương thức thanh toán'] || 'CASH',
    status: r['Trạng thái'] || 'completed',
    notes: r['Ghi chú'] || null,
    // Shipping data
    tracking_code: r['Mã vận đơn'] || null,
    delivery_status: r['Trạng thái giao hàng'] || null,
    delivery_partner: r['Đối tác giao hàng'] || null,
    delivery_time: excelToDate(r['Thời gian giao hàng']),
    delivery_notes: r['Ghi chú trạng thái giao hàng'] || null,
    order_code: r['Mã đặt hàng'] || null,
    return_code: r['Mã trả hàng'] || null,
    created_at: excelToDate(r['Thời gian']) || excelToDate(r['Thời gian tạo']),
    created_by_name: r['Người tạo'] || null,
    seller_name: r['Người bán'] || null,
})).filter(r => r.code);
fs.writeFileSync(path.join(OUTPUT_DIR, 'invoices_export.json'), JSON.stringify(invoices, null, 2));
console.log(`  Exported ${invoices.length} invoices`);

// 4. Returns
console.log('\n=== Converting Returns ===');
const returnWb = XLSX.readFile(path.join(EXPORT_DIR, 'DanhSachTraHang_KV10122025-173114-162.xlsx'));
const returnData = XLSX.utils.sheet_to_json(returnWb.Sheets[returnWb.SheetNames[0]]);
const returns = returnData.map(r => ({
    code: r['Mã phiếu trả'] || r['Mã trả hàng'] || null,
    invoice_code: r['Mã hóa đơn'] || null,
    customer_code: r['Mã khách hàng'] || null,
    customer_name: r['Tên khách hàng'] || null,
    branch_name: r['Chi nhánh'] || null,
    total_amount: parseFloat(r['Tổng tiền hàng trả']) || parseFloat(r['Tổng tiền trả']) || 0,
    refund_amount: parseFloat(r['Tiền trả khách']) || parseFloat(r['Tiền hoàn']) || 0,
    status: r['Trạng thái'] || 'completed',
    reason: r['Lý do'] || null,
    notes: r['Ghi chú'] || null,
    created_at: excelToDate(r['Thời gian']) || excelToDate(r['Ngày tạo']),
    created_by_name: r['Người tạo'] || null,
})).filter(r => r.code);
fs.writeFileSync(path.join(OUTPUT_DIR, 'returns_export.json'), JSON.stringify(returns, null, 2));
console.log(`  Exported ${returns.length} returns`);

// 5. Purchase Orders
console.log('\n=== Converting Purchase Orders ===');
const poWb = XLSX.readFile(path.join(EXPORT_DIR, 'DanhSachNhapHang_KV10122025-172803-870.xlsx'));
const poData = XLSX.utils.sheet_to_json(poWb.Sheets[poWb.SheetNames[0]]);
const purchaseOrders = poData.map(r => ({
    code: r['Mã nhập hàng'] || r['Mã phiếu nhập'] || null,
    supplier_code: r['Mã NCC'] || null,
    supplier_name: r['Tên nhà cung cấp'] || r['Nhà cung cấp'] || null,
    branch_name: r['Chi nhánh'] || null,
    total: parseFloat(r['Tổng tiền hàng']) || 0,
    paid_amount: parseFloat(r['Đã trả NCC']) || 0,
    status: r['Trạng thái'] || 'completed',
    notes: r['Ghi chú'] || null,
    created_at: excelToDate(r['Thời gian']) || excelToDate(r['Ngày tạo']),
    created_by_name: r['Người tạo'] || null,
})).filter(r => r.code);
fs.writeFileSync(path.join(OUTPUT_DIR, 'purchase_orders_export.json'), JSON.stringify(purchaseOrders, null, 2));
console.log(`  Exported ${purchaseOrders.length} purchase orders`);

console.log('\n=== Conversion Complete ===');
console.log('Files saved to:', OUTPUT_DIR);

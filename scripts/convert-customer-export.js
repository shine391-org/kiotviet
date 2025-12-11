const XLSX = require('xlsx');
const fs = require('fs');

const wb = XLSX.readFile('/home/shine/projects/kiotviet/archive/kiotviet-export/DanhSachKhachHang_KV10122025-173531-038.xlsx');
const ws = wb.Sheets[wb.SheetNames[0]];
const data = XLSX.utils.sheet_to_json(ws);

// Convert Excel date to JS Date
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

// Map customer type
const mapCustomerType = (type) => {
    if (!type) return 'INDIVIDUAL';
    if (type.includes('Công ty')) return 'COMPANY';
    return 'INDIVIDUAL';
};

// Map gender
const mapGender = (gender) => {
    if (!gender) return null;
    if (gender === 'Nam') return 'MALE';
    if (gender === 'Nữ') return 'FEMALE';
    return null;
};

const rows = data.map((r, i) => {
    const code = r['Mã khách hàng'];
    // Skip if no valid code (must start with KH or be a number)
    if (!code || typeof code !== 'string' || (!code.startsWith('KH') && !/^\d+$/.test(code))) {
        return null;
    }

    return {
        code: code,
        name: r['Tên khách hàng'] || null,
        phone: r['Điện thoại'] || null,
        address: r['Địa chỉ'] || null,
        ward: r['Phường/Xã'] || null,
        district: r['Khu vực giao hàng'] || null,
        company_name: r['Công ty'] || null,
        tax_code: r['Mã số thuế'] || null,
        id_number: r['Số CMND/CCCD'] || null,
        birthday: excelToDateOnly(r['Ngày sinh']),
        gender: mapGender(r['Giới tính']),
        email: r['Email'] || null,
        facebook: r['Facebook'] || null,
        customer_group: r['Nhóm khách hàng'] || null,
        note: r['Ghi chú'] || null,
        created_by_name: r['Người tạo'] || null,
        customer_type: mapCustomerType(r['Loại khách']),
        current_debt: parseFloat(r['Nợ cần thu hiện tại']) || 0,
        total_sales: parseFloat(r['Tổng bán']) || 0,
        total_sales_net: parseFloat(r['Tổng bán trừ trả hàng']) || 0,
        status: r['Trạng thái'] === 1 ? 'ACTIVE' : 'INACTIVE',
        created_at: excelToDate(r['Ngày tạo']) || '2025-01-01 00:00:00',
        last_transaction_at: excelToDate(r['Ngày giao dịch cuối']),
    };
}).filter(r => r !== null);

// Create Data folder if needed
if (!fs.existsSync('/home/shine/projects/kiotviet/backend-ci/app/Database/Seeds/Data')) {
    fs.mkdirSync('/home/shine/projects/kiotviet/backend-ci/app/Database/Seeds/Data', { recursive: true });
}

fs.writeFileSync(
    '/home/shine/projects/kiotviet/backend-ci/app/Database/Seeds/Data/customers_export.json',
    JSON.stringify(rows, null, 2)
);
console.log('Exported', rows.length, 'customers to customers_export.json');

// Show sample
console.log('\nSample customers:');
rows.slice(0, 3).forEach(r => console.log(`  ${r.code} - ${r.name} - ${r.phone}`));

// Check KH002150
const kh = rows.find(r => r.code === 'KH002150');
console.log('\nKH002150:', kh ? `${kh.code} - ${kh.name} - ${kh.phone}` : 'NOT FOUND');

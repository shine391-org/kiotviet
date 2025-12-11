const XLSX = require('xlsx');
const fs = require('fs');

const wb = XLSX.readFile('/home/shine/projects/kiotviet/archive/kiotviet-export/SoQuy_KV10122025-173632-669.xlsx');
const ws = wb.Sheets[wb.SheetNames[0]];
const data = XLSX.utils.sheet_to_json(ws);

// Convert Excel date to JS Date
const excelToDate = (serial) => {
    if (!serial || typeof serial !== 'number') return null;
    const d = new Date((serial - 25569) * 86400 * 1000);
    return d.toISOString().slice(0, 19).replace('T', ' ');
};

// Map types
const mapType = (loaiThuChi) => {
    if (!loaiThuChi || typeof loaiThuChi !== 'string') {
        return { type: 'RECEIPT', category: 'other_income', reference_type: 'manual' };
    }
    if (loaiThuChi.startsWith('Phiếu thu')) {
        return {
            type: 'RECEIPT',
            category: 'sales',
            reference_type: loaiThuChi.includes('khách trả') ? 'order' : 'manual'
        };
    }
    if (loaiThuChi.startsWith('Phiếu chi')) {
        if (loaiThuChi.includes('NCC') || loaiThuChi.includes('Nhập Hàng')) {
            return { type: 'PAYMENT', category: 'purchase', reference_type: 'purchase_order' };
        }
        if (loaiThuChi.includes('ĐTGH')) {
            return { type: 'PAYMENT', category: 'shipping_fee', reference_type: 'shipping_settlement' };
        }
        if (loaiThuChi.includes('lương')) {
            return { type: 'PAYMENT', category: 'salary', reference_type: 'expense' };
        }
        if (loaiThuChi.includes('khách')) {
            return { type: 'PAYMENT', category: 'refund', reference_type: 'return_order' };
        }
        return { type: 'PAYMENT', category: 'expense', reference_type: 'expense' };
    }
    if (loaiThuChi.includes('Chi phí') || loaiThuChi.includes('Thu nhập')) {
        return {
            type: loaiThuChi.includes('Thu') ? 'RECEIPT' : 'PAYMENT',
            category: 'other_expense',
            reference_type: 'expense'
        };
    }
    return { type: 'RECEIPT', category: 'other_income', reference_type: 'manual' };
};

const mapStatus = (s) => {
    if (s === 'Đã thanh toán') return 'approved';
    if (s === 'Đã hủy') return 'cancelled';
    return 'pending';
};

const rows = data.map((r, i) => {
    const tm = mapType(r['Loại thu chi']);
    return {
        type: tm.type,
        amount: Math.abs(r['Giá trị'] || 0),
        category: tm.category,
        payment_method: 'cash',
        status: mapStatus(r['Trạng thái']),
        description: r['Loại thu chi'] || '',
        reference_type: tm.reference_type,
        reference_code: r['Mã phiếu'] || null,
        branch_id: r['Chi nhánh'] === 'Lano - HN' ? 1 : (r['Chi nhánh'] === 'LANO - SG' ? 2 : 3),
        created_by: 1,
        created_by_name: r['Người tạo'] || 'import',
        staff_name: r['Nhân viên'] || null,
        payer_code: r['Mã người nộp/nhận'] || null,
        payer_name: r['Người nộp/nhận'] || null,
        payer_phone: r['Số điện thoại'] || null,
        payer_address: (r['Địa chỉ'] || '').toString().replace(/\r\n/g, ', '),
        transfer_note: r['Nội dung chuyển khoản'] || null,
        transaction_date: excelToDate(r['Thời gian'])?.slice(0, 10) || '2025-01-01',
        note: r['Ghi chú'] || null,
        created_at: excelToDate(r['Thời gian tạo']) || '2025-01-01 00:00:00'
    };
}).filter(r => r.amount > 0);

// Create Data folder if needed
if (!fs.existsSync('/home/shine/projects/kiotviet/backend-ci/app/Database/Seeds/Data')) {
    fs.mkdirSync('/home/shine/projects/kiotviet/backend-ci/app/Database/Seeds/Data', { recursive: true });
}

fs.writeFileSync(
    '/home/shine/projects/kiotviet/backend-ci/app/Database/Seeds/Data/cash_export.json',
    JSON.stringify(rows, null, 2)
);
console.log('Exported', rows.length, 'rows to cash_export.json');

// Count by reference_type
const refs = {};
rows.forEach(r => { refs[r.reference_type] = (refs[r.reference_type] || 0) + 1; });
console.log('Reference types:', refs);

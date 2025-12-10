// src/pages/customers/EditReceiptModal.jsx

import React, { useState, useEffect, useCallback } from 'react';
import { Modal, Form, Input, DatePicker, Select, Checkbox, Table, Empty, Spin, Button, Space, message } from 'antd';
import { PrinterOutlined, DeleteOutlined, LinkOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import customerApi from '../../api/customerApi';
import userApi from '../../api/userApi';

/**
 * EditReceiptModal - Sửa phiếu thu (View/Edit customer receipt)
 * Displays receipt details with allocated invoices table
 * @agent-layer: frontend-component
 */
const EditReceiptModal = ({ open, receiptCode, customer, onCancel, onSuccess }) => {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [receiptData, setReceiptData] = useState(null);
    const [showAllocation, setShowAllocation] = useState(true);
    const [collectors, setCollectors] = useState([]);

    // Wrap fetchReceiptDetails in useCallback for stable reference
    const fetchReceiptDetails = useCallback(async () => {
        if (!receiptCode) return;
        setLoading(true);
        try {
            // Try to fetch receipt details from API
            const response = await customerApi.getReceiptByCode(receiptCode);
            const data = response.data || {};
            setReceiptData(data);

            // Populate form with receipt data
            form.setFieldsValue({
                code: data.code || receiptCode,
                created_at: data.created_at ? dayjs(data.created_at) : dayjs(),
                created_by: data.created_by_name || 'Chưa xác định',
                collector: data.collector_id,
                payment_method: data.payment_method || 'bank',
                account_number: data.account_number || '',
                amount: data.value || data.amount || 0,
                notes: data.notes || '',
            });
        } catch (error) {
            console.error('Failed to fetch receipt:', error);
            // Use fallback data from props if API fails
            form.setFieldsValue({
                code: receiptCode,
                created_at: dayjs(),
                created_by: 'Chưa xác định',
                payment_method: 'bank',
                amount: 0,
            });
        } finally {
            setLoading(false);
        }
    }, [receiptCode, form]);

    // Fetch receipt details when modal opens
    useEffect(() => {
        if (open && receiptCode) {
            fetchReceiptDetails();
        }
    }, [open, receiptCode, fetchReceiptDetails]);

    // Fetch collectors when modal opens
    useEffect(() => {
        if (open) {
            const fetchCollectors = async () => {
                try {
                    const response = await userApi.getUsers({ limit: 50, status: 'active' });
                    setCollectors(response.data || []);
                } catch (error) {
                    console.error('Failed to fetch collectors:', error);
                    setCollectors([]);
                }
            };
            fetchCollectors();
        }
    }, [open]);



    // Columns for allocation table - matching KiotViet design
    const allocationColumns = [
        {
            title: 'Mã phiếu',
            dataIndex: 'code',
            key: 'code',
            render: (text) => (
                <Button
                    type="link"
                    style={{ padding: 0, height: 'auto' }}
                    onClick={(e) => {
                        e.stopPropagation();
                        // TODO: Navigate to invoice detail or open modal
                        console.log('View invoice:', text);
                    }}
                >
                    {text}
                </Button>
            ),
        },
        {
            title: 'Thời gian',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (v) => v ? dayjs(v).format('DD/MM/YYYY HH:mm') : '—',
        },
        {
            title: 'Giá trị phiếu',
            dataIndex: 'value',
            key: 'value',
            align: 'right',
            render: (v) => new Intl.NumberFormat('vi-VN').format(v || 0),
        },
        {
            title: 'Đã thu trước',
            dataIndex: 'paid_before',
            key: 'paid_before',
            align: 'right',
            render: (v) => new Intl.NumberFormat('vi-VN').format(v || 0),
        },
        {
            title: 'Tiền thu/chi',
            dataIndex: 'payment_amount',
            key: 'payment_amount',
            align: 'right',
            render: (v) => new Intl.NumberFormat('vi-VN').format(v || 0),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            render: (v) => (
                <span style={{ color: v === 'paid' || v === 'Đã thanh toán' ? '#52c41a' : '#1890ff' }}>
                    {v === 'paid' ? 'Đã thanh toán' : (v || 'Đã thanh toán')}
                </span>
            ),
        },
    ];

    // Get invoices allocation data
    const invoicesData = receiptData?.invoices || receiptData?.allocations || [];

    // Calculate totals
    // BUSINESS RULE: Receipts (phiếu thu) are always positive amounts representing money collected.
    // Refunds/returns are handled separately via payment vouchers (phiếu chi).
    // We normalize totalAmount to ensure consistent positive display.
    const rawAmount = receiptData?.value || receiptData?.amount || 0;
    const totalAmount = Math.abs(rawAmount); // Normalize: receipts are always positive
    const totalAllocated = invoicesData.reduce((sum, inv) => sum + (inv.payment_amount || 0), 0);
    // Unallocated = remaining amount not yet assigned to invoices
    const unallocated = Math.max(0, totalAmount - totalAllocated); // Prevent negative display

    const handleSave = async () => {
        try {
            const values = await form.validateFields();
            setSaving(true);

            // Call API to update receipt
            await customerApi.updateReceipt(receiptCode, {
                created_at: values.created_at ? values.created_at.toISOString() : undefined,
                collector: values.collector,
                payment_method: values.payment_method,
                account_number: values.account_number,
                notes: values.notes,
            });

            message.success('Đã lưu phiếu thu');
            onSuccess?.();
        } catch (err) {
            if (!err.errorFields) {
                console.error('Save error:', err);
                message.error(err.message || 'Lưu thất bại');
            }
        } finally {
            setSaving(false);
        }
    };

    const handlePrint = () => {
        // Build receipt HTML for printing
        const formatCurrency = (val) => new Intl.NumberFormat('vi-VN').format(val || 0);
        const formatDate = (val) => val ? dayjs(val).format('DD/MM/YYYY HH:mm') : '—';

        const paymentMethodLabels = {
            cash: 'Tiền mặt',
            bank: 'Chuyển khoản',
            card: 'Thẻ',
        };

        const invoicesHtml = invoicesData.length > 0
            ? `
                <table class="allocation-table">
                    <thead>
                        <tr>
                            <th>Mã phiếu</th>
                            <th>Thời gian</th>
                            <th style="text-align:right">Giá trị phiếu</th>
                            <th style="text-align:right">Đã thu trước</th>
                            <th style="text-align:right">Tiền thu/chi</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${invoicesData.map(inv => `
                            <tr>
                                <td>${inv.code || '—'}</td>
                                <td>${formatDate(inv.created_at)}</td>
                                <td style="text-align:right">${formatCurrency(inv.value)}</td>
                                <td style="text-align:right">${formatCurrency(inv.paid_before)}</td>
                                <td style="text-align:right">${formatCurrency(inv.payment_amount)}</td>
                                <td style="color:#52c41a">${inv.status === 'paid' ? 'Đã thanh toán' : (inv.status || 'Đã thanh toán')}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                <div class="unallocated">
                    Tiền chưa phân bổ: <strong>${formatCurrency(unallocated)}</strong>
                </div>
            `
            : '<p style="color:#8c8c8c;text-align:center;">Không có hóa đơn phân bổ</p>';

        const receiptHtml = `
            <!DOCTYPE html>
            <html lang="vi">
            <head>
                <meta charset="UTF-8">
                <title>Phiếu thu ${receiptData?.code || receiptCode}</title>
                <style>
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                        font-size: 12px;
                        line-height: 1.5;
                        padding: 20px;
                        color: #333;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 20px;
                        border-bottom: 2px solid #1890ff;
                        padding-bottom: 15px;
                    }
                    .header h1 {
                        font-size: 18px;
                        color: #1890ff;
                        margin-bottom: 5px;
                    }
                    .header .code {
                        font-size: 14px;
                        color: #666;
                    }
                    .customer-info {
                        margin-bottom: 15px;
                        padding: 10px;
                        background: #f5f5f5;
                        border-radius: 4px;
                    }
                    .info-grid {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 10px;
                        margin-bottom: 15px;
                    }
                    .info-item {
                        display: flex;
                        justify-content: space-between;
                        padding: 5px 0;
                        border-bottom: 1px dashed #e8e8e8;
                    }
                    .info-label {
                        color: #666;
                    }
                    .info-value {
                        font-weight: 500;
                    }
                    .total-amount {
                        font-size: 16px;
                        font-weight: 600;
                        color: #1890ff;
                        text-align: right;
                        padding: 12px;
                        background: #f0f5ff;
                        border-radius: 4px;
                        margin-bottom: 15px;
                    }
                    .allocation-section h3 {
                        font-size: 13px;
                        margin-bottom: 10px;
                        color: #333;
                    }
                    .allocation-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 10px;
                    }
                    .allocation-table th,
                    .allocation-table td {
                        border: 1px solid #e8e8e8;
                        padding: 8px;
                        text-align: left;
                        font-size: 11px;
                    }
                    .allocation-table th {
                        background: #fafafa;
                        font-weight: 600;
                    }
                    .unallocated {
                        text-align: right;
                        color: #8c8c8c;
                        margin-top: 8px;
                    }
                    .notes {
                        margin-top: 15px;
                        padding: 10px;
                        background: #fffbe6;
                        border-radius: 4px;
                        border-left: 3px solid #faad14;
                    }
                    .notes-label {
                        font-weight: 500;
                        margin-bottom: 5px;
                    }
                    .footer {
                        margin-top: 30px;
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        text-align: center;
                        gap: 20px;
                    }
                    .signature-box {
                        padding-top: 10px;
                    }
                    .signature-title {
                        font-weight: 500;
                        margin-bottom: 50px;
                    }
                    @media print {
                        body { padding: 10px; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>PHIẾU THU</h1>
                    <div class="code">${receiptData?.code || receiptCode}</div>
                </div>

                ${customer ? `
                    <div class="customer-info">
                        <strong>Khách hàng:</strong> ${customer.name || '—'}
                        ${customer.phone ? ` · SĐT: ${customer.phone}` : ''}
                        ${customer.code ? ` · Mã: ${customer.code}` : ''}
                    </div>
                ` : ''}

                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Thời gian:</span>
                        <span class="info-value">${formatDate(receiptData?.created_at)}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Người tạo:</span>
                        <span class="info-value">${receiptData?.created_by_name || 'Chưa xác định'}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phương thức:</span>
                        <span class="info-value">${paymentMethodLabels[receiptData?.payment_method] || receiptData?.payment_method || '—'}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Số tài khoản:</span>
                        <span class="info-value">${receiptData?.account_number || '—'}</span>
                    </div>
                </div>

                <div class="total-amount">
                    Tổng tiền thu: ${formatCurrency(Math.abs(totalAmount))} VNĐ
                </div>

                ${showAllocation ? `
                    <div class="allocation-section">
                        <h3>Phân bổ vào hóa đơn</h3>
                        ${invoicesHtml}
                    </div>
                ` : ''}

                ${receiptData?.notes ? `
                    <div class="notes">
                        <div class="notes-label">Ghi chú:</div>
                        <div>${receiptData.notes}</div>
                    </div>
                ` : ''}

                <div class="footer">
                    <div class="signature-box">
                        <div class="signature-title">Người nộp tiền</div>
                        <div>(Ký, ghi rõ họ tên)</div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-title">Người thu tiền</div>
                        <div>(Ký, ghi rõ họ tên)</div>
                    </div>
                </div>
            </body>
            </html>
        `;

        // Open new window and print
        try {
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            if (!printWindow) {
                message.error('Không thể mở cửa sổ in. Vui lòng tắt popup blocker.');
                return;
            }

            printWindow.document.open();
            printWindow.document.write(receiptHtml);
            printWindow.document.close();

            // Wait for content to load then print
            printWindow.onload = () => {
                printWindow.focus();
                printWindow.print();
                // Close after print dialog is dismissed
                printWindow.onafterprint = () => printWindow.close();
            };

            // Fallback: auto-print after short delay if onload doesn't fire
            setTimeout(() => {
                if (printWindow && !printWindow.closed) {
                    printWindow.focus();
                    printWindow.print();
                }
            }, 500);
        } catch (err) {
            console.error('Print error:', err);
            message.error('Không thể in phiếu thu');
        }
    };

    const handleDelete = () => {
        const receiptId = receiptData?.id;
        if (!receiptId && !receiptCode) {
            message.error('Không tìm thấy mã phiếu thu để xóa');
            return;
        }

        Modal.confirm({
            title: 'Xóa phiếu thu',
            content: 'Bạn có chắc muốn xóa phiếu thu này? Hành động này không thể hoàn tác.',
            okText: 'Xóa',
            okType: 'danger',
            cancelText: 'Hủy',
            onOk: async () => {
                setDeleting(true);
                try {
                    // Call delete API with receipt ID or code
                    await customerApi.deleteReceipt(receiptId || receiptCode);
                    message.success('Đã xóa phiếu thu thành công');
                    onCancel(); // Close modal
                    onSuccess?.(); // Refresh parent data
                } catch (err) {
                    console.error('Delete error:', err);
                    message.error(err.response?.data?.message || err.message || 'Xóa phiếu thu thất bại');
                    // Do NOT call onSuccess on failure
                } finally {
                    setDeleting(false);
                }
            },
        });
    };

    return (
        <Modal
            title={
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <span>Sửa phiếu thu</span>
                    {customer && (
                        <a
                            href={`/customers?id=${customer.id}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            style={{ fontSize: 12, color: '#1890ff', fontWeight: 400 }}
                        >
                            {customer.name} <LinkOutlined />
                        </a>
                    )}
                </div>
            }
            open={open}
            onCancel={onCancel}
            width={700}
            footer={
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <Button icon={<DeleteOutlined />} danger onClick={handleDelete}>
                        Xóa
                    </Button>
                    <Space>
                        <Button onClick={onCancel}>Bỏ qua</Button>
                        <Button icon={<PrinterOutlined />} onClick={handlePrint}>In</Button>
                        <Button type="primary" onClick={handleSave} loading={saving}>
                            Lưu
                        </Button>
                    </Space>
                </div>
            }
            destroyOnClose
        >
            {loading ? (
                <div style={{ textAlign: 'center', padding: 40 }}>
                    <Spin size="large" />
                </div>
            ) : (
                <Form form={form} layout="vertical" style={{ marginTop: 16 }}>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                        <Form.Item name="code" label="Mã phiếu thu">
                            <Input disabled style={{ backgroundColor: '#f5f5f5' }} />
                        </Form.Item>

                        <Form.Item name="created_by" label="Người tạo">
                            <Input disabled />
                        </Form.Item>
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                        <Form.Item name="created_at" label="Thời gian">
                            <DatePicker
                                showTime
                                format="DD/MM/YYYY HH:mm"
                                style={{ width: '100%' }}
                            />
                        </Form.Item>

                        <Form.Item name="collector" label="Người thu">
                            <Select placeholder="Chọn người thu" allowClear>
                                {collectors.map((user) => (
                                    <Select.Option key={user.id} value={user.id}>
                                        {user.full_name || user.username}
                                    </Select.Option>
                                ))}
                            </Select>
                        </Form.Item>
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                        <Form.Item name="payment_method" label="Phương thức thanh toán">
                            <Select>
                                <Select.Option value="cash">Tiền mặt</Select.Option>
                                <Select.Option value="bank">Chuyển khoản</Select.Option>
                                <Select.Option value="card">Thẻ</Select.Option>
                            </Select>
                        </Form.Item>

                        <Form.Item name="account_number" label="Số tài khoản">
                            <Input placeholder="Nhập số tài khoản" />
                        </Form.Item>
                    </div>

                    <Form.Item label="Tổng tiền thu">
                        <div style={{
                            fontSize: 18,
                            fontWeight: 600,
                            color: '#1890ff',
                            textAlign: 'right',
                            padding: '8px 12px',
                            backgroundColor: '#f0f5ff',
                            borderRadius: 6
                        }}>
                            {new Intl.NumberFormat('vi-VN').format(Math.abs(totalAmount))}
                        </div>
                    </Form.Item>

                    <Form.Item name="notes" label="Ghi chú">
                        <Input.TextArea rows={2} placeholder="Ghi chú..." />
                    </Form.Item>

                    <Checkbox
                        checked={showAllocation}
                        onChange={(e) => setShowAllocation(e.target.checked)}
                    >
                        Phân bổ vào hóa đơn
                    </Checkbox>

                    {showAllocation && (
                        <>
                            <div style={{
                                display: 'flex',
                                justifyContent: 'flex-end',
                                padding: '12px 0 4px',
                                fontWeight: 500
                            }}>
                                {new Intl.NumberFormat('vi-VN').format(Math.abs(totalAmount))}
                            </div>

                            <Table
                                columns={allocationColumns}
                                dataSource={invoicesData}
                                rowKey="code"
                                pagination={false}
                                size="small"
                                locale={{
                                    emptyText: (
                                        <Empty
                                            image={Empty.PRESENTED_IMAGE_SIMPLE}
                                            description="Không có hóa đơn phân bổ"
                                        />
                                    ),
                                }}
                            />

                            <div style={{
                                textAlign: 'right',
                                marginTop: 8,
                                color: '#8c8c8c'
                            }}>
                                Tiền chưa phân bổ: <strong>{new Intl.NumberFormat('vi-VN').format(unallocated)}</strong>
                            </div>
                        </>
                    )}
                </Form>
            )}
        </Modal>
    );
};

export default EditReceiptModal;

import React, { useEffect, useState } from 'react';
import { Modal, Form, Input, Select, DatePicker, InputNumber, Table, Checkbox, Space, Button, Typography, message } from 'antd';
import { ExportOutlined } from '@ant-design/icons';
import { useDispatch } from 'react-redux';
import { updateCashTransaction } from '../../store/slices/cashSlice';
import dayjs from 'dayjs';

/**
 * EditPaymentReceiptModal - Sửa phiếu chi
 * Opens when clicking on a payment receipt code in the payment history
 */
const EditPaymentReceiptModal = ({ open, receipt, returnData, onCancel, onSuccess }) => {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(false);
    const dispatch = useDispatch();

    useEffect(() => {
        if (open && receipt) {
            form.setFieldsValue({
                receipt_code: receipt.receipt_code || '',
                creator_name: receipt.creator_name || '',
                created_at: receipt.created_at ? dayjs(receipt.created_at) : null,
                receiver_name: receipt.receiver_name || '',
                payment_method: receipt.payment_method || 'cash',
                account_number: receipt.account_number || '',
                total_amount: receipt.amount || 0,
                notes: receipt.notes || '',
                allocate_to_return: true,
            });
        }
    }, [open, receipt, form]);

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            setLoading(true);

            const payload = {
                created_at: values.created_at ? values.created_at.format('YYYY-MM-DD HH:mm:ss') : null,
                receiver_name: values.receiver_name,
                payment_method: values.payment_method,
                account_number: values.account_number,
                notes: values.notes,
            };

            const resultAction = await dispatch(updateCashTransaction({ id: receipt.id, data: payload }));

            if (updateCashTransaction.fulfilled.match(resultAction)) {
                message.success('Cập nhật phiếu chi thành công');
                setLoading(false);
                onSuccess();
            } else {
                message.error(resultAction.payload || 'Có lỗi xảy ra khi cập nhật');
                setLoading(false);
            }
        } catch (err) {
            console.error('Form validation failed:', err);
            setLoading(false);
        }
    };

    // Allocation table data
    const allocationData = returnData ? [{
        key: returnData.id,
        return_code: returnData.return_code,
        created_at: returnData.created_at,
        amount: returnData.need_refund || 0,
        previous_paid: 0,
        current_paid: -(receipt?.amount || 0),
        status: 'completed',
    }] : [];

    const allocationColumns = [
        {
            title: 'Mã phiếu',
            dataIndex: 'return_code',
            width: 120,
            render: (code) => <Typography.Link>{code}</Typography.Link>
        },
        {
            title: 'Thời gian',
            dataIndex: 'created_at',
            width: 140,
            render: (v) => v ? dayjs(v).format('DD/MM/YYYY HH:mm') : '—'
        },
        {
            title: 'Giá trị phiếu',
            dataIndex: 'amount',
            align: 'right',
            width: 100,
            render: (v) => (Number(v || 0)).toLocaleString('vi-VN')
        },
        {
            title: 'Đã thu trước',
            dataIndex: 'previous_paid',
            align: 'right',
            width: 100,
            render: (v) => (Number(v || 0)).toLocaleString('vi-VN')
        },
        {
            title: 'Tiền thu/chi',
            dataIndex: 'current_paid',
            align: 'right',
            width: 100,
            render: (v) => (
                <span style={{ color: v < 0 ? '#f5222d' : '#52c41a' }}>
                    {(Number(v || 0)).toLocaleString('vi-VN')}
                </span>
            )
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            width: 120,
            render: (v) => (
                <span style={{ color: v === 'completed' ? '#52c41a' : '#1890ff' }}>
                    {v === 'completed' ? 'Đã thanh toán' : 'Đang xử lý'}
                </span>
            )
        },
    ];

    const unallocatedAmount = 0;

    return (
        <Modal
            title={
                <Space>
                    <span>Sửa phiếu chi</span>
                    <Typography.Link>
                        {returnData?.customer_name || '—'} <ExportOutlined />
                    </Typography.Link>
                </Space>
            }
            open={open}
            onCancel={onCancel}
            width={700}
            footer={
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                    <Button onClick={onCancel}>Hủy</Button>
                    <Space>
                        <Button>Bỏ qua</Button>
                        <Button>In</Button>
                        <Button type="primary" loading={loading} onClick={handleSubmit}>
                            Lưu
                        </Button>
                    </Space>
                </div>
            }
        >
            <Form form={form} layout="vertical" size="small">
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                    <Form.Item label="Mã phiếu chi" name="receipt_code">
                        <Input disabled />
                    </Form.Item>
                    <Form.Item label="Người tạo" name="creator_name">
                        <Input disabled />
                    </Form.Item>
                </div>

                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                    <Form.Item label="Thời gian" name="created_at">
                        <DatePicker showTime format="DD/MM/YYYY HH:mm" style={{ width: '100%' }} />
                    </Form.Item>
                    <Form.Item label="Người thu" name="receiver_name">
                        <Select
                            placeholder="Chọn người thu"
                            options={[
                                { label: receipt?.receiver_name || 'Chưa có', value: receipt?.receiver_name || '' },
                            ]}
                        />
                    </Form.Item>
                </div>

                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                    <Form.Item label="Phương thức thanh toán" name="payment_method">
                        <Select
                            options={[
                                { label: 'Tiền mặt', value: 'cash' },
                                { label: 'Chuyển khoản', value: 'transfer' },
                                { label: 'Thẻ', value: 'card' },
                            ]}
                        />
                    </Form.Item>
                    <Form.Item label="Số tài khoản" name="account_number">
                        <Input />
                    </Form.Item>
                </div>

                <Form.Item label="Tổng tiền chi">
                    <div style={{
                        background: '#f5f5f5',
                        padding: '8px 12px',
                        borderRadius: 6,
                        textAlign: 'right',
                        fontSize: 18,
                        fontWeight: 600,
                        color: '#1890ff'
                    }}>
                        {(receipt?.amount || 0).toLocaleString('vi-VN')}
                    </div>
                </Form.Item>

                <Form.Item label="Ghi chú" name="notes">
                    <Input.TextArea rows={2} placeholder="Ghi chú..." />
                </Form.Item>

                <Form.Item name="allocate_to_return" valuePropName="checked">
                    <Checkbox>Phân bổ vào đơn trả hàng</Checkbox>
                </Form.Item>

                {/* Allocation Table */}
                <Table
                    dataSource={allocationData}
                    columns={allocationColumns}
                    size="small"
                    pagination={false}
                    style={{ marginBottom: 12 }}
                />

                <div style={{ textAlign: 'right', fontSize: 13 }}>
                    <span>Tiền chưa phân bổ: </span>
                    <span style={{ fontWeight: 600 }}>{unallocatedAmount.toLocaleString('vi-VN')}</span>
                </div>
            </Form>
        </Modal>
    );
};

export default EditPaymentReceiptModal;

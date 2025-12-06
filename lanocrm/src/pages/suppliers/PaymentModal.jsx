// src/pages/suppliers/PaymentModal.jsx

import React from 'react';
import { Modal, Form, Input, DatePicker, InputNumber, Select, Checkbox, Table, Empty, message } from 'antd';
import dayjs from 'dayjs';
import supplierApi from '../../api/supplierApi';

/**
 * PaymentModal - Thanh toán công nợ NCC
 * @agent-layer: frontend-component
 */
const PaymentModal = ({ open, supplier, onCancel, onSuccess }) => {
    const [form] = Form.useForm();
    const [loading, setLoading] = React.useState(false);
    const [showAllocation, setShowAllocation] = React.useState(true);

    const currentDebt = supplier?.current_debt || supplier?.debt_amount || 0;
    const payables = supplier?.payables || [];

    // Columns for allocation table
    const allocationColumns = [
        { title: 'Mã hóa đơn', dataIndex: 'code', key: 'code' },
        { title: 'Thời gian', dataIndex: 'time', key: 'time' },
        { title: 'Giá trị phiếu nhập', dataIndex: 'value', key: 'value', align: 'right' },
        { title: 'Đã trả trước', dataIndex: 'paid', key: 'paid', align: 'right' },
        { title: 'Còn cần trả', dataIndex: 'payable', key: 'payable', align: 'right' },
        { title: 'Tiền trả', dataIndex: 'payment', key: 'payment', align: 'right' },
        { title: 'Còn nợ', dataIndex: 'remaining', key: 'remaining', align: 'right' },
    ];

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            setLoading(true);

            await supplierApi.createPayment(supplier.id, {
                amount: values.amount,
                payment_date: values.payment_date?.format('YYYY-MM-DD HH:mm:ss'),
                payment_method: values.payment_method,
                payer: values.payer,
                note: values.note,
            });
            message.success('Thanh toán thành công!');
            onSuccess?.();
            form.resetFields();
            onCancel();
        } catch (err) {
            if (!err.errorFields) {
                message.error(err.message || 'Thanh toán thất bại');
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal
            title={
                <div>
                    <div>Thanh toán</div>
                    <div style={{ fontSize: 12, color: '#8c8c8c', fontWeight: 400 }}>
                        {supplier?.name} · Nợ hiện tại: {new Intl.NumberFormat('vi-VN').format(currentDebt)}
                    </div>
                </div>
            }
            open={open}
            onCancel={onCancel}
            width={800}
            footer={[
                <span key="cancel" style={{ marginRight: 8, cursor: 'pointer', color: '#1890ff' }} onClick={onCancel}>
                    Bỏ qua
                </span>,
                <span key="print" style={{ marginRight: 8, cursor: 'pointer', color: '#1890ff' }}>
                    Tạo phiếu chi & In
                </span>,
                <button
                    key="submit"
                    onClick={handleSubmit}
                    style={{
                        background: '#1890ff',
                        color: '#fff',
                        border: 'none',
                        padding: '6px 16px',
                        borderRadius: 6,
                        cursor: 'pointer',
                    }}
                >
                    {loading ? 'Đang xử lý...' : 'Tạo phiếu chi'}
                </button>,
            ]}
            destroyOnClose
        >
            <Form form={form} layout="vertical" style={{ marginTop: 16 }}>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                    <Form.Item
                        name="payment_date"
                        label="Thời gian"
                        initialValue={dayjs()}
                    >
                        <DatePicker
                            showTime
                            format="DD/MM/YYYY HH:mm"
                            style={{ width: '100%' }}
                        />
                    </Form.Item>

                    <Form.Item name="payer" label="Người chi">
                        <Select placeholder="Chọn người chi" allowClear>
                            <Select.Option value="Trung">Trung</Select.Option>
                            <Select.Option value="Admin">Admin</Select.Option>
                        </Select>
                    </Form.Item>
                </div>

                <Form.Item name="payment_method" label="Phương thức thanh toán" initialValue="cash">
                    <Select>
                        <Select.Option value="cash">Tiền mặt</Select.Option>
                        <Select.Option value="bank">Chuyển khoản</Select.Option>
                        <Select.Option value="card">Thẻ</Select.Option>
                    </Select>
                </Form.Item>

                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                    <Form.Item
                        name="amount"
                        label="Số tiền"
                        rules={[{ required: true, message: 'Vui lòng nhập số tiền' }]}
                    >
                        <InputNumber
                            style={{ width: '100%' }}
                            placeholder="Nhập số tiền thanh toán"
                            formatter={(value) => `${value} `.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                            parser={(value) => value.replace(/\,/g, '')}
                        />
                    </Form.Item>

                    <Form.Item label="Nợ còn">
                        <span style={{ fontWeight: 500, color: '#1890ff' }}>
                            {new Intl.NumberFormat('vi-VN').format(currentDebt)}
                        </span>
                    </Form.Item>
                </div>

                <Form.Item name="note" label="Ghi chú">
                    <Input.TextArea rows={2} placeholder="Nhập ghi chú" />
                </Form.Item>

                <Checkbox checked={showAllocation} onChange={(e) => setShowAllocation(e.target.checked)}>
                    Phân bổ vào phiếu nhập hàng
                </Checkbox>

                {showAllocation && (
                    <Table
                        columns={allocationColumns}
                        dataSource={[]}
                        rowKey="code"
                        pagination={false}
                        size="small"
                        style={{ marginTop: 16 }}
                        locale={{
                            emptyText: (
                                <Empty
                                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                                    description="Không tìm thấy kết quả nào phù hợp"
                                />
                            ),
                        }}
                    />
                )}
            </Form>
        </Modal>
    );
};

export default PaymentModal;

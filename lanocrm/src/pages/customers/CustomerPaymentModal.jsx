// src/pages/customers/CustomerPaymentModal.jsx

import React, { useState, useEffect } from 'react';
import { Modal, Form, Input, DatePicker, InputNumber, Select, Checkbox, Table, Empty, message, Spin } from 'antd';
import dayjs from 'dayjs';
import customerApi from '../../api/customerApi';
import userApi from '../../api/userApi';

/**
 * CustomerPaymentModal - Thanh toán công nợ khách hàng
 * @agent-layer: frontend-component
 */
const CustomerPaymentModal = ({ open, customer, onCancel, onSuccess }) => {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(false);
    const [showAllocation, setShowAllocation] = useState(false); // Start false since dataSource is empty
    const [collectors, setCollectors] = useState([]);
    const [collectorsLoading, setCollectorsLoading] = useState(false);

    const currentDebt = customer?.current_debt || customer?.debt_amount || 0;
    const invoices = customer?.invoices || [];

    // Fetch collectors (users) when modal opens
    useEffect(() => {
        if (open) {
            const fetchCollectors = async () => {
                setCollectorsLoading(true);
                try {
                    const response = await userApi.getUsers({ limit: 50, status: 'active' });
                    const users = response.data || [];
                    setCollectors(users);
                } catch (error) {
                    console.error('Failed to fetch collectors:', error);
                    setCollectors([]);
                } finally {
                    setCollectorsLoading(false);
                }
            };
            fetchCollectors();
        }
    }, [open]);

    // Columns for allocation table - matching the design in the image
    const allocationColumns = [
        { title: 'Mã hóa đơn', dataIndex: 'code', key: 'code' },
        { title: 'Thời gian', dataIndex: 'time', key: 'time' },
        { title: 'Giá trị hóa đơn', dataIndex: 'value', key: 'value', align: 'right' },
        { title: 'Đã thu trước', dataIndex: 'paid', key: 'paid', align: 'right' },
        { title: 'Còn cần thu', dataIndex: 'receivable', key: 'receivable', align: 'right' },
        { title: 'Tiền thu', dataIndex: 'payment', key: 'payment', align: 'right' },
    ];

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            setLoading(true);

            await customerApi.recordPayment(customer.id, {
                amount: values.amount,
                notes: values.note || '',
                payment_date: values.payment_date ? values.payment_date.toISOString() : new Date().toISOString(),
                collector: values.collector || '',
                payment_method: values.payment_method || 'cash',
            });
            message.success('Thanh toán thành công!');
            form.resetFields();
            // Only call onSuccess - parent handles closing the modal
            onSuccess?.();
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
                        {customer?.name} · Nợ hiện tại: {new Intl.NumberFormat('vi-VN').format(currentDebt)}
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
                    Tạo phiếu thu & In
                </span>,
                <button
                    key="submit"
                    onClick={handleSubmit}
                    disabled={loading}
                    style={{
                        background: '#1890ff',
                        color: '#fff',
                        border: 'none',
                        padding: '6px 16px',
                        borderRadius: 6,
                        cursor: 'pointer',
                    }}
                >
                    {loading ? 'Đang xử lý...' : 'Tạo phiếu thu'}
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

                    <Form.Item name="collector" label="Người thu">
                        <Select
                            placeholder="Chọn người thu"
                            allowClear
                            loading={collectorsLoading}
                            notFoundContent={collectorsLoading ? <Spin size="small" /> : 'Không có dữ liệu'}
                        >
                            {collectors.map((user) => (
                                <Select.Option key={user.id} value={user.id}>
                                    {user.full_name || user.username}
                                </Select.Option>
                            ))}
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
                            formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                            parser={(value) => value.replace(/,/g, '')}
                            min={0}
                            max={999999999999}
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
                    Phân bổ vào hóa đơn
                </Checkbox>

                {showAllocation && (
                    <>
                        <Table
                            columns={allocationColumns}
                            dataSource={invoices}
                            rowKey="code"
                            pagination={false}
                            size="small"
                            style={{ marginTop: 16 }}
                            locale={{
                                emptyText: (
                                    <Empty
                                        image={Empty.PRESENTED_IMAGE_SIMPLE}
                                        description="Không tìm thấy bản ghi nào phù hợp"
                                    />
                                ),
                            }}
                        />
                        <div style={{ textAlign: 'right', marginTop: 8, color: '#8c8c8c' }}>
                            Tiền chưa phân bổ: <strong>0</strong>
                        </div>
                    </>
                )}
            </Form>
        </Modal>
    );
};

export default CustomerPaymentModal;

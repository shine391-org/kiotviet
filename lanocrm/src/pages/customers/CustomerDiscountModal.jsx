// src/pages/customers/CustomerDiscountModal.jsx

import React, { useState, useEffect } from 'react';
import { Modal, Form, Input, DatePicker, InputNumber, Select, Checkbox, Table, Empty, message, Spin } from 'antd';
import dayjs from 'dayjs';
import customerApi from '../../api/customerApi';
import userApi from '../../api/userApi';

/**
 * CustomerDiscountModal - Chiết khấu thanh toán công nợ khách hàng
 * @agent-layer: frontend-component
 */
const CustomerDiscountModal = ({ open, customer, onCancel, onSuccess }) => {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(false);
    const [showAllocation, setShowAllocation] = useState(false); // Start false since dataSource is empty
    const [executors, setExecutors] = useState([]);
    const [executorsLoading, setExecutorsLoading] = useState(false);

    const currentDebt = customer?.current_debt || customer?.debt_amount || 0;

    // Fetch executors (users) when modal opens
    useEffect(() => {
        if (open) {
            const fetchExecutors = async () => {
                setExecutorsLoading(true);
                try {
                    const response = await userApi.getUsers({ limit: 50, status: 'active' });
                    const users = response.data || [];
                    setExecutors(users);
                } catch (error) {
                    console.error('Failed to fetch executors:', error);
                    setExecutors([]);
                } finally {
                    setExecutorsLoading(false);
                }
            };
            fetchExecutors();
        }
    }, [open]);

    // Columns for allocation table
    const allocationColumns = [
        { title: 'Mã hóa đơn', dataIndex: 'code', key: 'code' },
        { title: 'Thời gian', dataIndex: 'time', key: 'time' },
        { title: 'Giá trị hóa đơn', dataIndex: 'value', key: 'value', align: 'right' },
        { title: 'Còn cần thu', dataIndex: 'receivable', key: 'receivable', align: 'right' },
        { title: 'Chiết khấu phân bổ', dataIndex: 'discount', key: 'discount', align: 'right' },
        { title: 'Còn nợ', dataIndex: 'remaining', key: 'remaining', align: 'right' },
    ];

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            setLoading(true);

            await customerApi.applyDiscount(customer.id, {
                amount: values.discount_amount,
                notes: values.note || '',
            });
            message.success('Tạo chiết khấu thành công!');
            form.resetFields();
            // Only call onCancel if no onSuccess handler provided (parent handles closing)
            if (onSuccess) {
                onSuccess();
            } else {
                onCancel();
            }
        } catch (err) {
            if (!err.errorFields) {
                message.error(err.message || 'Tạo chiết khấu thất bại');
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal
            title={
                <div>
                    <div>Chiết khấu thanh toán</div>
                    <div style={{ fontSize: 12, color: '#8c8c8c', fontWeight: 400 }}>
                        {customer?.name} · Nợ hiện tại: {new Intl.NumberFormat('vi-VN').format(currentDebt)}
                    </div>
                </div>
            }
            open={open}
            onCancel={onCancel}
            width={800}
            okText="Tạo phiếu"
            cancelText="Bỏ qua"
            onOk={handleSubmit}
            confirmLoading={loading}
            destroyOnClose
        >
            <Form form={form} layout="vertical" style={{ marginTop: 16 }}>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                    <Form.Item
                        name="discount_date"
                        label="Thời gian"
                        initialValue={dayjs()}
                    >
                        <DatePicker
                            showTime
                            format="DD/MM/YYYY HH:mm"
                            style={{ width: '100%' }}
                        />
                    </Form.Item>

                    <Form.Item name="executor" label="Người thực hiện">
                        <Select
                            placeholder="Chọn người thực hiện"
                            allowClear
                            loading={executorsLoading}
                            notFoundContent={executorsLoading ? <Spin size="small" /> : 'Không có dữ liệu'}
                        >
                            {executors.map((user) => (
                                <Select.Option key={user.id} value={user.id}>
                                    {user.full_name || user.username}
                                </Select.Option>
                            ))}
                        </Select>
                    </Form.Item>
                </div>

                <Form.Item
                    name="discount_amount"
                    label="Chiết khấu cho khách hàng"
                    rules={[{ required: true, message: 'Vui lòng nhập số tiền chiết khấu' }]}
                >
                    <InputNumber
                        style={{ width: '100%' }}
                        placeholder="Nhập số tiền chiết khấu"
                        formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                        parser={(value) => value.replace(/,/g, '')}
                        min={0}
                        max={999999999999}
                    />
                </Form.Item>

                <div style={{ textAlign: 'right', marginBottom: 8, color: '#8c8c8c' }}>
                    Còn nợ: {new Intl.NumberFormat('vi-VN').format(currentDebt)}
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
                        <div style={{ textAlign: 'right', marginTop: 8, color: '#8c8c8c' }}>
                            Chiết khấu chưa phân bổ: <strong>0</strong>
                        </div>
                    </>
                )}
            </Form>
        </Modal>
    );
};

export default CustomerDiscountModal;

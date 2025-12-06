// src/pages/suppliers/DiscountModal.jsx

import React from 'react';
import { Modal, Form, Input, DatePicker, InputNumber, Select, Checkbox, Table, Empty, message } from 'antd';
import dayjs from 'dayjs';
import supplierApi from '../../api/supplierApi';

/**
 * DiscountModal - Chiết khấu thanh toán công nợ NCC
 * @agent-layer: frontend-component
 */
const DiscountModal = ({ open, supplier, onCancel, onSuccess }) => {
    const [form] = Form.useForm();
    const [loading, setLoading] = React.useState(false);
    const [showAllocation, setShowAllocation] = React.useState(true);

    const currentDebt = supplier?.current_debt || supplier?.debt_amount || 0;

    // Columns for allocation table
    const allocationColumns = [
        { title: 'Mã nhập hàng', dataIndex: 'code', key: 'code' },
        { title: 'Thời gian', dataIndex: 'time', key: 'time' },
        { title: 'Giá trị phiếu nhập', dataIndex: 'value', key: 'value', align: 'right' },
        { title: 'Còn cần trả', dataIndex: 'payable', key: 'payable', align: 'right' },
        { title: 'Chiết khấu phân bổ', dataIndex: 'discount', key: 'discount', align: 'right' },
        { title: 'Còn nợ', dataIndex: 'remaining', key: 'remaining', align: 'right' },
    ];

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            setLoading(true);

            await supplierApi.createDiscount(supplier.id, {
                discount_amount: values.discount_amount,
                discount_date: values.discount_date?.format('YYYY-MM-DD HH:mm:ss'),
                executor: values.executor,
                note: values.note,
            });
            message.success('Tạo chiết khấu thành công!');
            onSuccess?.();
            form.resetFields();
            onCancel();
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
                        {supplier?.name} · Nợ hiện tại: {new Intl.NumberFormat('vi-VN').format(currentDebt)}
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
                        <Select placeholder="Chọn người thực hiện" allowClear>
                            <Select.Option value="Trung">Trung</Select.Option>
                            <Select.Option value="Admin">Admin</Select.Option>
                        </Select>
                    </Form.Item>
                </div>

                <Form.Item
                    name="discount_amount"
                    label="Chiết khấu từ nhà cung cấp"
                    rules={[{ required: true, message: 'Vui lòng nhập số tiền chiết khấu' }]}
                >
                    <InputNumber
                        style={{ width: '100%' }}
                        placeholder="Nhập số tiền chiết khấu"
                        formatter={(value) => `${value} `.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                        parser={(value) => value.replace(/\,/g, '')}
                    />
                </Form.Item>

                <div style={{ textAlign: 'right', marginBottom: 8, color: '#8c8c8c' }}>
                    Còn nợ: {new Intl.NumberFormat('vi-VN').format(currentDebt)}
                </div>

                <Form.Item name="note" label="Ghi chú">
                    <Input.TextArea rows={2} placeholder="Nhập ghi chú" />
                </Form.Item>

                <Checkbox checked={showAllocation} onChange={(e) => setShowAllocation(e.target.checked)}>
                    Phân bổ vào phiếu nhập hàng
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

export default DiscountModal;

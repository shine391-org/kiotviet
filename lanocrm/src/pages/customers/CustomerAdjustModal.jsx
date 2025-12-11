// src/pages/customers/CustomerAdjustModal.jsx

import React from 'react';
import { Modal, Form, Input, DatePicker, InputNumber, message } from 'antd';
import dayjs from 'dayjs';
import customerApi from '../../api/customerApi';

/**
 * CustomerAdjustModal - Điều chỉnh công nợ khách hàng
 * @agent-layer: frontend-component
 */
const CustomerAdjustModal = ({ open, customer, onCancel, onSuccess }) => {
    const [form] = Form.useForm();
    const [loading, setLoading] = React.useState(false);

    const currentDebt = customer?.current_debt ?? customer?.debt_amount ?? 0;

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            setLoading(true);

            await customerApi.adjustDebt(customer.id, {
                amount: values.adjust_value,
                notes: values.description || '',
                adjust_date: values.adjust_date ? values.adjust_date.toISOString() : new Date().toISOString(),
            });
            message.success('Điều chỉnh công nợ thành công!');
            form.resetFields();
            // Only call onCancel if no onSuccess handler provided (parent handles closing)
            if (onSuccess) {
                onSuccess();
            } else {
                onCancel();
            }
        } catch (err) {
            if (!err.errorFields) {
                message.error(err.message || 'Điều chỉnh thất bại');
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal
            title="Điều chỉnh"
            open={open}
            onCancel={onCancel}
            width={480}
            okText="Chỉnh sửa"
            cancelText="Bỏ qua"
            onOk={handleSubmit}
            confirmLoading={loading}
            destroyOnClose
        >
            <Form form={form} layout="vertical" style={{ marginTop: 16 }}>
                <Form.Item label="Nợ cần thu hiện tại:">
                    <span style={{ fontWeight: 500 }}>
                        {new Intl.NumberFormat('vi-VN').format(currentDebt)}
                    </span>
                </Form.Item>

                <Form.Item
                    name="adjust_date"
                    label="Ngày điều chỉnh"
                    rules={[{ required: true, message: 'Vui lòng chọn ngày' }]}
                    initialValue={dayjs()}
                >
                    <DatePicker
                        showTime
                        format="DD/MM/YYYY HH:mm"
                        style={{ width: '100%' }}
                        placeholder="Chọn ngày điều chỉnh"
                    />
                </Form.Item>

                <Form.Item
                    name="adjust_value"
                    label="Giá trị nợ điều chỉnh"
                    rules={[{ required: true, message: 'Vui lòng nhập giá trị' }]}
                >
                    <InputNumber
                        style={{ width: '100%' }}
                        placeholder="Nhập giá trị điều chỉnh"
                        formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                        parser={(value) => value.replace(/,/g, '')}
                        min={-999999999999}
                        max={999999999999}
                    />
                </Form.Item>

                <Form.Item name="description" label="Mô tả">
                    <Input.TextArea rows={3} placeholder="Nhập mô tả" />
                </Form.Item>
            </Form>
        </Modal>
    );
};

export default CustomerAdjustModal;

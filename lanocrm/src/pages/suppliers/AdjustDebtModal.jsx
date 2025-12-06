// src/pages/suppliers/AdjustDebtModal.jsx

import React from 'react';
import { Modal, Form, Input, DatePicker, InputNumber, message } from 'antd';
import dayjs from 'dayjs';
import supplierApi from '../../api/supplierApi';

/**
 * AdjustDebtModal - Điều chỉnh nợ
 * @agent-layer: frontend-component
 */
const AdjustDebtModal = ({ open, supplier, onCancel, onSuccess }) => {
    const [form] = Form.useForm();
    const [loading, setLoading] = React.useState(false);

    const currentDebt = supplier?.current_debt || supplier?.debt_amount || 0;

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            setLoading(true);

            await supplierApi.adjustDebt(supplier.id, {
                adjust_value: values.adjust_value,
                adjust_date: values.adjust_date?.format('YYYY-MM-DD HH:mm:ss'),
                description: values.description,
            });
            message.success('Điều chỉnh công nợ thành công!');
            onSuccess?.();
            form.resetFields();
            onCancel();
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
                <Form.Item label="Nợ cần trả hiện tại:">
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

export default AdjustDebtModal;

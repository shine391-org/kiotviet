// src/pages/suppliers/EditSupplierModal.jsx

import React, { useEffect } from 'react';
import { Modal, Form, Input, Collapse, Button, Select, message } from 'antd';
import { CaretRightOutlined } from '@ant-design/icons';
import supplierApi from '../../api/supplierApi';

const { Panel } = Collapse;

/**
 * EditSupplierModal - Edit supplier with invoice info section
 * @agent-layer: frontend-component
 * @agent-pattern: form-modal with collapsible sections
 */
const EditSupplierModal = ({ open, supplier, onCancel, onSuccess, supplierGroups = [] }) => {
    const [form] = Form.useForm();
    const [loading, setLoading] = React.useState(false);

    useEffect(() => {
        if (open && supplier) {
            form.setFieldsValue({
                name: supplier.name || '',
                code: supplier.code || '',
                phone: supplier.phone || '',
                email: supplier.email || '',
                address: supplier.address || '',
                province: supplier.province || '',
                district: supplier.district || '',
                ward: supplier.ward || '',
                group: supplier.group || '',
                note: supplier.note || '',
                company: supplier.company || '',
                tax_code: supplier.tax_code || '',
            });
        }
    }, [open, supplier, form]);

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            setLoading(true);

            await supplierApi.updateSupplier(supplier.id, values);
            message.success('Cập nhật nhà cung cấp thành công!');
            onSuccess?.();
            onCancel();
        } catch (err) {
            if (err.errorFields) {
                // Form validation error
                return;
            }
            message.error(err.message || 'Cập nhật thất bại');
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal
            title="Sửa nhà cung cấp"
            open={open}
            onCancel={onCancel}
            width={640}
            footer={[
                <Button key="cancel" onClick={onCancel}>
                    Bỏ qua
                </Button>,
                <Button key="submit" type="primary" loading={loading} onClick={handleSubmit}>
                    Lưu
                </Button>,
            ]}
            destroyOnClose
        >
            <Form form={form} layout="vertical" style={{ marginTop: 16 }}>
                <Collapse
                    defaultActiveKey={['basic', 'invoice']}
                    expandIcon={({ isActive }) => <CaretRightOutlined rotate={isActive ? 90 : 0} />}
                    style={{ background: '#fff' }}
                >
                    {/* Section 1: Basic Info */}
                    <Panel header="Thông tin cơ bản" key="basic" style={{ border: 'none' }}>
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                            <Form.Item
                                name="name"
                                label="Tên nhà cung cấp"
                                rules={[{ required: true, message: 'Vui lòng nhập tên' }]}
                            >
                                <Input placeholder="Nhập tên nhà cung cấp" />
                            </Form.Item>
                            <Form.Item name="code" label="Mã nhà cung cấp">
                                <Input placeholder="Mã tự động" disabled />
                            </Form.Item>
                            <Form.Item name="phone" label="Điện thoại">
                                <Input placeholder="Nhập số điện thoại" />
                            </Form.Item>
                            <Form.Item name="email" label="Email">
                                <Input type="email" placeholder="Nhập email" />
                            </Form.Item>
                        </div>
                    </Panel>

                    {/* Section 2: Address */}
                    <Panel header="Địa chỉ" key="address" style={{ border: 'none' }}>
                        <Form.Item name="address" label="Địa chỉ">
                            <Input placeholder="Nhập địa chỉ" />
                        </Form.Item>
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                            <Form.Item name="province" label="Khu vực">
                                <Input placeholder="Tỉnh/Thành phố - Quận/Huyện" />
                            </Form.Item>
                            <Form.Item name="ward" label="Phường/Xã">
                                <Input placeholder="Tìm Phường/Xã" />
                            </Form.Item>
                        </div>
                    </Panel>

                    {/* Section 3: Group & Notes */}
                    <Panel header="Nhóm nhà cung cấp, ghi chú" key="group" style={{ border: 'none' }}>
                        <Form.Item name="group" label="Nhóm nhà cung cấp">
                            <Select
                                placeholder="Chọn nhóm nhà cung cấp"
                                allowClear
                                options={supplierGroups.map((g) => ({ label: g, value: g }))}
                            />
                        </Form.Item>
                        <Form.Item name="note" label="Ghi chú">
                            <Input.TextArea rows={3} placeholder="Nhập ghi chú" />
                        </Form.Item>
                    </Panel>

                    {/* Section 4: Invoice Info */}
                    <Panel header="Thông tin xuất hóa đơn" key="invoice" style={{ border: 'none' }}>
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                            <Form.Item name="company" label="Tên công ty">
                                <Input placeholder="Nhập tên công ty" />
                            </Form.Item>
                            <Form.Item name="tax_code" label="Mã số thuế">
                                <Input placeholder="Nhập mã số thuế" />
                            </Form.Item>
                        </div>
                    </Panel>
                </Collapse>
            </Form>
        </Modal>
    );
};

export default EditSupplierModal;

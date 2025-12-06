// src/pages/suppliers/CreateSupplierModal.jsx

import React, { useState } from 'react';
import {
    Modal,
    Form,
    Input,
    Select,
    Collapse,
    Row,
    Col,
    Button,
    Space,
} from 'antd';
import { CaretRightOutlined } from '@ant-design/icons';

const { TextArea } = Input;
const { Panel } = Collapse;

/**
 * CreateSupplierModal - Modal to create a new supplier
 * @agent-layer: frontend-component
 * @agent-pattern: form-modal with collapsible sections
 */
const CreateSupplierModal = ({ open, onCancel, onSuccess, supplierGroups = [] }) => {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(false);

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            setLoading(true);

            // TODO: Connect to API
            console.log('Create supplier:', values);

            // Simulate API call
            setTimeout(() => {
                setLoading(false);
                form.resetFields();
                onSuccess?.(values);
                onCancel?.();
            }, 500);
        } catch (error) {
            console.error('Validation failed:', error);
        }
    };

    const handleCancel = () => {
        form.resetFields();
        onCancel?.();
    };

    return (
        <Modal
            title="Tạo nhà cung cấp"
            open={open}
            onCancel={handleCancel}
            width={700}
            footer={
                <Space>
                    <Button onClick={handleCancel}>Bỏ qua</Button>
                    <Button type="primary" loading={loading} onClick={handleSubmit}>
                        Lưu
                    </Button>
                </Space>
            }
            destroyOnClose
        >
            <Form
                form={form}
                layout="vertical"
                requiredMark="optional"
                initialValues={{
                    code: '',
                    name: '',
                    phone: '',
                    email: '',
                }}
            >
                {/* Basic Info Section */}
                <Row gutter={16}>
                    <Col span={12}>
                        <Form.Item
                            name="name"
                            label="Tên nhà cung cấp"
                            rules={[{ required: true, message: 'Vui lòng nhập tên nhà cung cấp' }]}
                        >
                            <Input placeholder="Bắt buộc" />
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item name="code" label="Mã nhà cung cấp">
                            <Input placeholder="Tự động" disabled />
                        </Form.Item>
                    </Col>
                </Row>

                <Row gutter={16}>
                    <Col span={12}>
                        <Form.Item name="phone" label="Điện thoại">
                            <Input placeholder="Nhập số điện thoại" />
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item name="email" label="Email">
                            <Input placeholder="email@gmail.com" />
                        </Form.Item>
                    </Col>
                </Row>

                {/* Collapsible Sections */}
                <Collapse
                    bordered={false}
                    expandIcon={({ isActive }) => <CaretRightOutlined rotate={isActive ? 90 : 0} />}
                    ghost
                    style={{ marginTop: 8 }}
                >
                    {/* Address Section */}
                    <Panel header="Địa chỉ" key="address">
                        <Form.Item name="address" label="Địa chỉ">
                            <Input placeholder="Nhập địa chỉ" />
                        </Form.Item>
                        <Row gutter={16}>
                            <Col span={12}>
                                <Form.Item name="area" label="Khu vực">
                                    <Input placeholder="Tìm Tỉnh/Thành phố - Quận/Huyện" />
                                </Form.Item>
                            </Col>
                            <Col span={12}>
                                <Form.Item name="ward" label="Phường/Xã">
                                    <Input placeholder="Tìm Phường/Xã" />
                                </Form.Item>
                            </Col>
                        </Row>
                    </Panel>

                    {/* Group & Notes Section */}
                    <Panel header="Nhóm nhà cung cấp, ghi chú" key="group">
                        <Form.Item name="group" label="Nhóm nhà cung cấp">
                            <Select
                                placeholder="Chọn nhóm nhà cung cấp"
                                allowClear
                                options={supplierGroups.map((g) => ({ label: g, value: g }))}
                            />
                        </Form.Item>
                        <Form.Item name="note" label="Ghi chú">
                            <TextArea rows={3} placeholder="Nhập ghi chú" />
                        </Form.Item>
                    </Panel>

                    {/* Invoice Info Section */}
                    <Panel header="Thông tin xuất hóa đơn" key="invoice">
                        <Row gutter={16}>
                            <Col span={12}>
                                <Form.Item name="company" label="Tên công ty">
                                    <Input placeholder="Nhập tên công ty" />
                                </Form.Item>
                            </Col>
                            <Col span={12}>
                                <Form.Item name="tax_code" label="Mã số thuế">
                                    <Input placeholder="Nhập mã số thuế" />
                                </Form.Item>
                            </Col>
                        </Row>
                    </Panel>
                </Collapse>
            </Form>
        </Modal>
    );
};

export default CreateSupplierModal;

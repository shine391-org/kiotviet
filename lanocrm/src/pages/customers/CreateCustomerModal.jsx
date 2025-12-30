// src/pages/customers/CreateCustomerModal.jsx

import React, { useEffect, useState } from 'react';
import {
    Modal,
    Form,
    Input,
    Select,
    DatePicker,
    Collapse,
    Radio,
    Row,
    Col,
    Upload,
    Button,
    Space,
    App,
} from 'antd';
import { PlusOutlined, UploadOutlined } from '@ant-design/icons';
import customerApi from '../../api/customerApi';
import styles from './CreateCustomerModal.module.css';

const { Panel } = Collapse;
const { TextArea } = Input;

const GENDERS = [
    { label: 'Chọn giới tính', value: null },
    { label: 'Nam', value: 'MALE' },
    { label: 'Nữ', value: 'FEMALE' },
];

const CUSTOMER_TYPES = [
    { label: 'Cá nhân', value: 'INDIVIDUAL' },
    { label: 'Tổ chức/ Hộ kinh doanh', value: 'COMPANY' },
];

/**
 * CreateCustomerModal - Modal for creating/editing a customer
 * Matches KiotViet design with collapsible sections
 */
const CreateCustomerModal = ({
    open,
    customer,
    onCancel,
    onSuccess,
    customerGroups = [],
    focusSection,
}) => {
    const { message: messageApi } = App.useApp();
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(false);
    const [activeKeys, setActiveKeys] = useState(['address', 'group', 'invoice']);

    const isEdit = !!customer?.id;

    // Handle focus section
    useEffect(() => {
        if (open && focusSection) {
            if (!activeKeys.includes(focusSection)) {
                setActiveKeys((prev) => [...prev, focusSection]);
            }
            // Scroll to section
            setTimeout(() => {
                const element = document.getElementById(`${focusSection}-section`);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 300);
        }
    }, [open, focusSection, activeKeys]);

    // Reset form when modal opens
    useEffect(() => {
        if (open) {
            if (customer) {
                form.setFieldsValue({
                    ...customer,
                    birthday: customer.birthday ? customer.birthday : null,
                    invoice_customer_type: customer.tax_code ? 'COMPANY' : 'INDIVIDUAL',
                });
            } else {
                form.resetFields();
                form.setFieldsValue({
                    invoice_customer_type: 'INDIVIDUAL',
                });
            }
        }
    }, [open, customer, form]);

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            setLoading(true);

            const payload = {
                name: values.name,
                code: values.code || undefined,
                phone: values.phone || undefined,
                phone2: values.phone2 || undefined,
                birthday: values.birthday || undefined,
                gender: values.gender || undefined,
                email: values.email || undefined,
                facebook: values.facebook || undefined,
                address: values.address || undefined,
                province: values.province || undefined,
                ward: values.ward || undefined,
                customer_group_id: values.customer_group_id || undefined,
                notes: values.notes || undefined,
                customer_type: values.invoice_customer_type || 'INDIVIDUAL',
                tax_code: values.tax_code || undefined,
                company_name: values.company_name || undefined,
                invoice_address: values.invoice_address || undefined,
                invoice_province: values.invoice_province || undefined,
                invoice_ward: values.invoice_ward || undefined,
                buyer_name: values.buyer_name || undefined,
                dvqhns_code: values.dvqhns_code || undefined,
                invoice_email: values.invoice_email || undefined,
                invoice_phone: values.invoice_phone || undefined,
                bank_name: values.bank_name || undefined,
                bank_account: values.bank_account || undefined,
                status: 'ACTIVE',
            };

            if (isEdit) {
                await customerApi.updateCustomer(customer.id, payload);
                messageApi.success('Cập nhật khách hàng thành công');
            } else {
                await customerApi.createCustomer(payload);
                messageApi.success('Tạo khách hàng thành công');
            }

            onSuccess?.();
            onCancel?.();
            form.resetFields();
        } catch (err) {
            if (err?.errorFields) {
                // Form validation error
                return;
            }
            messageApi.error(err?.message || 'Có lỗi xảy ra');
        } finally {
            setLoading(false);
        }
    };

    const groupOptions = customerGroups.map((g) => ({
        label: g.name || g.name_vi,
        value: g.id,
    }));

    return (
        <Modal
            title={isEdit ? 'Cập nhật khách hàng' : 'Tạo khách hàng'}
            open={open}
            onCancel={onCancel}
            width={800}
            footer={
                <div className={styles.footer}>
                    <Button onClick={onCancel}>Bỏ qua</Button>
                    <Button type="primary" loading={loading} onClick={handleSubmit}>
                        Lưu
                    </Button>
                </div>
            }
            className={styles.modal}
            destroyOnClose
        >
            <Form form={form} layout="vertical" className={styles.form}>
                {/* Basic Info Section */}
                <div className={styles.section}>
                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item
                                label="Tên khách hàng"
                                name="name"
                                rules={[{ required: true, message: 'Vui lòng nhập tên' }]}
                            >
                                <Input placeholder="Bắt buộc" />
                            </Form.Item>
                        </Col>
                        <Col span={8}>
                            <Form.Item label="Mã khách hàng" name="code">
                                <Input placeholder="Tự động" />
                            </Form.Item>
                        </Col>
                        <Col span={4}>
                            <Form.Item label=" " className={styles.uploadItem}>
                                <Upload
                                    listType="picture-card"
                                    maxCount={1}
                                    showUploadList={false}
                                    beforeUpload={() => false}
                                >
                                    <div className={styles.uploadPlaceholder}>
                                        <PlusOutlined />
                                        <div>Thêm ảnh</div>
                                        <small>Ảnh không được vượt quá 2MB</small>
                                    </div>
                                </Upload>
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={16}>
                        <Col span={6}>
                            <Form.Item label="Điện thoại 1" name="phone">
                                <Input />
                            </Form.Item>
                        </Col>
                        <Col span={6}>
                            <Form.Item label="Điện thoại 2" name="phone2">
                                <Input />
                            </Form.Item>
                        </Col>
                        <Col span={6}>
                            <Form.Item label="Sinh nhật" name="birthday">
                                <Input placeholder="--/--/----" />
                            </Form.Item>
                        </Col>
                        <Col span={6}>
                            <Form.Item label="Giới tính" name="gender">
                                <Select options={GENDERS} placeholder="Chọn giới tính" allowClear />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item label="Email" name="email">
                                <Input placeholder="email@gmail.com" />
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item label="Facebook" name="facebook">
                                <Input placeholder="facebook.com/username" />
                            </Form.Item>
                        </Col>
                    </Row>
                </div>

                {/* Collapsible Sections */}
                <Collapse
                    activeKey={activeKeys}
                    onChange={setActiveKeys}
                    className={styles.collapse}
                    expandIconPosition="end"
                >
                    {/* Address Section */}
                    <Panel header="Địa chỉ" key="address">
                        <Form.Item label="Địa chỉ" name="address">
                            <Input placeholder="Nhập địa chỉ" />
                        </Form.Item>
                        <Row gutter={16}>
                            <Col span={12}>
                                <Form.Item label="Khu vực" name="province">
                                    <Input placeholder="Chọn Tỉnh/Thành phố" />
                                </Form.Item>
                            </Col>
                            <Col span={12}>
                                <Form.Item label="Phường/Xã" name="ward">
                                    <Input placeholder="Chọn Phường/Xã" />
                                </Form.Item>
                            </Col>
                        </Row>
                    </Panel>

                    {/* Group & Notes Section */}
                    <Panel header="Nhóm khách hàng, ghi chú" key="group">
                        <Form.Item label="Nhóm khách hàng" name="customer_group_id">
                            <Select
                                options={groupOptions}
                                placeholder="Chọn nhóm khách hàng"
                                allowClear
                            />
                        </Form.Item>
                        <Form.Item label="Ghi chú" name="notes">
                            <TextArea rows={3} placeholder="Nhập ghi chú" />
                        </Form.Item>
                    </Panel>

                    {/* Invoice Info Section */}
                    <Panel header="Thông tin xuất hóa đơn" key="invoice">
                        <div id="invoice-section">
                        <Form.Item label="Loại khách hàng" name="invoice_customer_type">
                            <Radio.Group>
                                {CUSTOMER_TYPES.map((t) => (
                                    <Radio key={t.value} value={t.value}>
                                        {t.label}
                                    </Radio>
                                ))}
                            </Radio.Group>
                        </Form.Item>

                        <Row gutter={16}>
                            <Col span={12}>
                                <Form.Item label="Mã số thuế" name="tax_code">
                                    <Input placeholder="Bắt buộc" />
                                </Form.Item>
                            </Col>
                            <Col span={12}>
                                <Form.Item label="Tên công ty" name="company_name">
                                    <Input placeholder="Nhập tên công ty" />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Form.Item label="Địa chỉ" name="invoice_address">
                            <Input placeholder="Bắt buộc" />
                        </Form.Item>

                        <Row gutter={16}>
                            <Col span={12}>
                                <Form.Item label="Tỉnh/Thành phố" name="invoice_province">
                                    <Input placeholder="Tìm Tỉnh/Thành phố" />
                                </Form.Item>
                            </Col>
                            <Col span={12}>
                                <Form.Item label="Phường/Xã" name="invoice_ward">
                                    <Input placeholder="Tìm Phường/Xã" />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Row gutter={16}>
                            <Col span={12}>
                                <Form.Item label="Tên người mua" name="buyer_name">
                                    <Input placeholder="Nhập tên người mua" />
                                </Form.Item>
                            </Col>
                            <Col span={12}>
                                <Form.Item label="Mã ĐVQHNS ⓘ" name="dvqhns_code">
                                    <Input placeholder="Nhập mã đơn vị" />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Row gutter={16}>
                            <Col span={12}>
                                <Form.Item label="Email" name="invoice_email">
                                    <Input placeholder="email@gmail.com" />
                                </Form.Item>
                            </Col>
                            <Col span={12}>
                                <Form.Item label="Số điện thoại" name="invoice_phone">
                                    <Input placeholder="Nhập số điện thoại" />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Row gutter={16}>
                            <Col span={12}>
                                <Form.Item label="Ngân hàng" name="bank_name">
                                    <Select placeholder="Chọn ngân hàng" allowClear />
                                </Form.Item>
                            </Col>
                            <Col span={12}>
                                <Form.Item label="Số tài khoản ngân hàng" name="bank_account">
                                    <Input placeholder="Nhập số tài khoản ngân hàng" />
                                </Form.Item>
                            </Col>
                        </Row>
                        </div>
                    </Panel>
                </Collapse>
            </Form>
        </Modal>
    );
};

export default CreateCustomerModal;

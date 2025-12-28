// src/pages/customers/CustomerAddAddressModal.jsx

import React, { useEffect, useState } from 'react';
import { Modal, Form, Input, Button, Row, Col, App } from 'antd';
import customerApi from '../../api/customerApi';

const CustomerAddAddressModal = ({ open, customer, onCancel, onSuccess }) => {
  const { message } = App.useApp();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (open) {
      form.resetFields();
      if (customer) {
        // Pre-fill some data if needed, e.g. recipient name defaults to customer name
        form.setFieldsValue({
          recipient_name: customer.name,
          phone: customer.phone,
        });
      }
    }
  }, [open, customer, form]);

  const handleSubmit = async () => {
    try {
      const values = await form.validateFields();
      setLoading(true);

      await customerApi.createAddress(customer.id, values);

      message.success('Thêm địa chỉ thành công');
      onSuccess?.();
      onCancel?.();
    } catch (err) {
      if (err?.errorFields) return;
      message.error(err?.message || 'Có lỗi xảy ra khi thêm địa chỉ');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal
      title="Thêm địa chỉ nhận hàng"
      open={open}
      onCancel={onCancel}
      footer={[
        <Button key="back" onClick={onCancel}>
          Bỏ qua
        </Button>,
        <Button key="submit" type="primary" loading={loading} onClick={handleSubmit}>
          Lưu
        </Button>,
      ]}
      destroyOnHidden
    >
      <Form form={form} layout="vertical">
        <Form.Item
          label="Tên gợi nhớ"
          name="name"
          tooltip="Ví dụ: Nhà riêng, Văn phòng"
        >
          <Input placeholder="Nhập tên gợi nhớ (không bắt buộc)" />
        </Form.Item>

        <Row gutter={16}>
          <Col span={12}>
            <Form.Item
              label="Tên người nhận"
              name="recipient_name"
              rules={[{ required: true, message: 'Vui lòng nhập tên người nhận' }]}
            >
              <Input placeholder="Tên người nhận" />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              label="Số điện thoại"
              name="phone"
              rules={[{ required: true, message: 'Vui lòng nhập số điện thoại' }]}
            >
              <Input placeholder="Số điện thoại" />
            </Form.Item>
          </Col>
        </Row>

        <Form.Item
          label="Địa chỉ chi tiết"
          name="address"
          rules={[{ required: true, message: 'Vui lòng nhập địa chỉ' }]}
        >
          <Input placeholder="Số nhà, ngõ, tên đường..." />
        </Form.Item>

        {/* Note: In a real app, Province/Ward would likely be Selects fetching from a location API.
            For now we use simple Inputs to match the pattern in CreateCustomerModal's basic address. */}
        <Row gutter={16}>
          <Col span={12}>
            <Form.Item label="Tỉnh/Thành phố" name="province">
              <Input placeholder="Tỉnh/Thành phố" />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item label="Phường/Xã" name="ward">
              <Input placeholder="Phường/Xã" />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </Modal>
  );
};

export default CustomerAddAddressModal;

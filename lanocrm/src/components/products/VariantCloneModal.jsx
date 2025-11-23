import React, { useEffect, useState } from 'react';
import { Modal, Form, Input, InputNumber, Row, Col, Button, App } from 'antd';
import { useNavigate } from 'react-router-dom';
import * as productApi from '../../api/productApi';
import { handleApiError } from '../../utils/apiErrorHandler';

const VariantCloneModal = ({ open, sourceVariant, onCancel }) => {
  const [form] = Form.useForm();
  const [submitting, setSubmitting] = useState(false);
  const navigate = useNavigate();
  const { message } = App.useApp();

  // Reset form mỗi khi mở
  useEffect(() => {
    if (open && sourceVariant) {
      form.setFieldsValue({
        sku: '',
        variant_name: sourceVariant.variant_name || '',
        price: sourceVariant.price || 0,
        cost_price: sourceVariant.cost_price || 0,
        stock_quantity: 0,
        min_stock: sourceVariant.min_stock || 0,
        max_stock: sourceVariant.max_stock || 0,
        barcode: '',
      });
    }
  }, [open, sourceVariant, form]);

  const handleSubmit = async () => {
    try {
      const values = await form.validateFields();
      setSubmitting(true);

      const response = await productApi.createVariant(sourceVariant.product_id, {
        ...values,
        product_id: sourceVariant.product_id,
      });
      if (response.success) {
        message.success('Biến thể mới đã được tạo thành công, chuyển sang trang chỉnh sửa!');
        if (onCancel) onCancel();
        // Chuyển trang edit variant vừa tạo ra
        setTimeout(() => {
          navigate(`/products/variants/edit/${response.data.id}`);
        }, 500);
      } else {
        message.error(response.message || 'Tạo biến thể thất bại');
      }
    } catch (error) {
      handleApiError(error, { messageApi: message, defaultMessage: 'Có lỗi xảy ra khi tạo biến thể' });
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Modal
      open={open}
      title="Sao chép biến thể mới"
      onCancel={onCancel}
      width={800}
      footer={[
        <Button key="cancel" onClick={onCancel} disabled={submitting}>Hủy</Button>,
        <Button key="submit" type="primary" loading={submitting} onClick={handleSubmit}>
          Tạo biến thể mới
        </Button>,
      ]}
    >
      <Form form={form} layout="vertical">
        <Row gutter={16}>
          <Col span={12}>
            <Form.Item label="SKU/Mã biến thể" name="sku" rules={[{ required: true, message: 'Vui lòng nhập SKU' }]}>
              <Input placeholder="Nhập mã SKU mới" />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item label="Tên biến thể" name="variant_name" rules={[{ required: true, message: 'Vui lòng nhập tên biến thể' }]}>
              <Input placeholder="Tên biến thể" />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item label="Giá vốn" name="cost_price">
              <InputNumber min={0} style={{ width: '100%' }} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item label="Giá bán" name="price" rules={[{ required: true, message: 'Vui lòng nhập giá bán' }]}>
              <InputNumber min={0} style={{ width: '100%' }} />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item label="Tồn kho" name="stock_quantity">
              <InputNumber min={0} style={{ width: '100%' }} />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item label="Tồn kho tối thiểu" name="min_stock">
              <InputNumber min={0} style={{ width: '100%' }} />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item label="Tồn kho tối đa" name="max_stock">
              <InputNumber min={0} style={{ width: '100%' }} />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item label="Barcode" name="barcode">
              <Input placeholder="Barcode (tùy chọn)" />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </Modal>
  );
};

export default VariantCloneModal;

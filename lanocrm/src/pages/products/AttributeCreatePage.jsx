/**
 * Attribute Create Page
 * @file src/pages/products/AttributeCreatePage.jsx
 * @description Page for creating new product attributes
 */

import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Card,
  Breadcrumb,
  Button,
  Space,
  message,
  Form,
  Input,
  Select,
  InputNumber,
  Switch,
  Row,
  Col,
  Divider,
  Tag,
  ColorPicker,
  Popconfirm,
} from 'antd';
import {
  ArrowLeftOutlined,
  PlusOutlined,
  DeleteOutlined,
  SaveOutlined,
} from '@ant-design/icons';
import { usePermission } from '../../utils/usePermission';
import * as attributeApi from '../../api/attributeApi';
import styles from './AttributeCreatePage.module.css';

const { Option } = Select;

/**
 * AttributeCreatePage Component
 */
const AttributeCreatePage = () => {
  const navigate = useNavigate();
  const { hasPermission } = usePermission();
  const [form] = Form.useForm();

  const [loading, setLoading] = useState(false);
  const [attributeType, setAttributeType] = useState('select');
  const [options, setOptions] = useState([]);
  const [newOption, setNewOption] = useState({
    option_name: '',
    color_code: '#000000',
    sort_order: 0,
  });

  // Check permission
  useEffect(() => {
    if (!hasPermission('products.create')) {
      message.error('Bạn không có quyền tạo thuộc tính');
      navigate('/products/attributes');
    }
  }, [hasPermission, navigate]);

  /**
   * Handle form submit
   */
  const handleSubmit = async (values) => {
    try {
      setLoading(true);

      console.log('🔵 Form values:', values);
      console.log('🔵 Options:', options);

      // Create attribute first
      const attributeData = {
        name: values.name,
        slug: values.slug || null,
        type: values.type,
        is_required: values.is_required ? 1 : 0,
        is_filterable: values.is_filterable ? 1 : 0,
        sort_order: values.sort_order || 0,
        status: values.status || 'active',
      };

      const response = await attributeApi.createAttribute(attributeData);

      if (response.success) {
        const attributeId = response.data.id;
        console.log('✅ Attribute created:', attributeId);

        // Create options if type is select/color/image
        if (
          ['select', 'color', 'image'].includes(values.type) &&
          options.length > 0
        ) {
          console.log('🔵 Creating options...');

          const optionPromises = options.map((opt) =>
            attributeApi.createAttributeOption(attributeId, {
              option_name: opt.option_name,
              color_code: opt.color_code || null,
              image_url: opt.image_url || null,
              sort_order: opt.sort_order || 0,
            })
          );

          await Promise.all(optionPromises);
          console.log('✅ Options created');
        }

        message.success('Tạo thuộc tính thành công');

        setTimeout(() => {
          navigate('/products/attributes');
        }, 1500);
      } else {
        message.error(response.message || 'Không thể tạo thuộc tính');
      }
    } catch (error) {
      console.error('Create attribute error:', error);
      message.error(error.message || 'Lỗi tạo thuộc tính');
    } finally {
      setLoading(false);
    }
  };

  /**
   * Handle cancel
   */
  const handleCancel = () => {
    navigate('/products/attributes');
  };

  /**
   * Handle type change
   */
  const handleTypeChange = (type) => {
    setAttributeType(type);
    form.setFieldsValue({ type });

    // Clear options if switching to text type
    if (type === 'text') {
      setOptions([]);
    }
  };

  /**
   * Add new option
   */
  const handleAddOption = () => {
    if (!newOption.option_name.trim()) {
      message.warning('Vui lòng nhập tên giá trị');
      return;
    }

    // Check duplicate
    const duplicate = options.find(
      (opt) => opt.option_name.toLowerCase() === newOption.option_name.toLowerCase()
    );

    if (duplicate) {
      message.warning('Giá trị này đã tồn tại');
      return;
    }

    const option = {
      id: Date.now(), // Temporary ID for frontend
      option_name: newOption.option_name,
      color_code: attributeType === 'color' ? newOption.color_code : null,
      image_url: attributeType === 'image' ? newOption.image_url : null,
      sort_order: options.length,
    };

    setOptions([...options, option]);

    // Reset form
    setNewOption({
      option_name: '',
      color_code: '#000000',
      sort_order: 0,
    });

    message.success('Đã thêm giá trị');
  };

  /**
   * Remove option
   */
  const handleRemoveOption = (id) => {
    setOptions(options.filter((opt) => opt.id !== id));
    message.success('Đã xóa giá trị');
  };

  /**
   * Render option input based on type
   */
  const renderOptionInput = () => {
    if (attributeType === 'text') {
      return (
        <div className={styles.optionNote}>
          <Tag color="blue">Loại Text không cần tạo giá trị trước</Tag>
          <p>Người dùng sẽ nhập giá trị tự do khi gán vào sản phẩm</p>
        </div>
      );
    }

    return (
      <div className={styles.optionManager}>
        <Divider orientation="left">Quản lý giá trị thuộc tính</Divider>

        {/* Add new option form */}
        <div className={styles.addOptionForm}>
          <Row gutter={16} align="middle">
            <Col xs={24} sm={12} md={attributeType === 'color' ? 8 : 12}>
              <Input
                placeholder="Tên giá trị (VD: Đỏ, Size M, ...)"
                value={newOption.option_name}
                onChange={(e) =>
                  setNewOption({ ...newOption, option_name: e.target.value })
                }
                onPressEnter={handleAddOption}
              />
            </Col>

            {attributeType === 'color' && (
              <Col xs={24} sm={6} md={4}>
                <ColorPicker
                  value={newOption.color_code}
                  onChange={(color) =>
                    setNewOption({
                      ...newOption,
                      color_code: color.toHexString(),
                    })
                  }
                  showText
                />
              </Col>
            )}

            {attributeType === 'image' && (
              <Col xs={24} sm={12} md={8}>
                <Input
                  placeholder="URL hình ảnh"
                  value={newOption.image_url}
                  onChange={(e) =>
                    setNewOption({ ...newOption, image_url: e.target.value })
                  }
                />
              </Col>
            )}

            <Col>
              <Button
                type="primary"
                icon={<PlusOutlined />}
                onClick={handleAddOption}
              >
                Thêm
              </Button>
            </Col>
          </Row>
        </div>

        {/* Options list */}
        {options.length > 0 ? (
          <div className={styles.optionsList}>
            <div className={styles.optionsHeader}>
              <span>Đã thêm {options.length} giá trị</span>
            </div>

            {options.map((option, index) => (
              <div key={option.id} className={styles.optionItem}>
                <div className={styles.optionInfo}>
                  <span className={styles.optionIndex}>#{index + 1}</span>

                  <span className={styles.optionValue}>
                    {option.option_name}
                  </span>

                  {attributeType === 'color' && option.color_code && (
                    <div
                      className={styles.colorBox}
                      style={{ backgroundColor: option.color_code }}
                      title={option.color_code}
                    />
                  )}

                  {attributeType === 'image' && option.image_url && (
                    <img
                      src={option.image_url}
                      alt={option.option_name}
                      className={styles.optionImage}
                    />
                  )}
                </div>

                <Popconfirm
                  title="Xóa giá trị này?"
                  onConfirm={() => handleRemoveOption(option.id)}
                  okText="Xóa"
                  cancelText="Hủy"
                >
                  <Button
                    type="text"
                    danger
                    size="small"
                    icon={<DeleteOutlined />}
                  />
                </Popconfirm>
              </div>
            ))}
          </div>
        ) : (
          <div className={styles.emptyOptions}>
            <p>Chưa có giá trị nào. Hãy thêm giá trị đầu tiên!</p>
          </div>
        )}
      </div>
    );
  };

  return (
    <div className={styles.container}>
      {/* Header */}
      <div className={styles.header}>
        <Space className={styles.breadcrumb}>
          <Button
            type="text"
            icon={<ArrowLeftOutlined />}
            onClick={handleCancel}
          >
            Quay lại
          </Button>
        </Space>
        <h1>Thêm thuộc tính mới</h1>
      </div>

      {/* Breadcrumb */}
      <Breadcrumb
        items={[
          { title: 'Dashboard', onClick: () => navigate('/dashboard') },
          { title: 'Sản phẩm', onClick: () => navigate('/products') },
          {
            title: 'Thuộc tính',
            onClick: () => navigate('/products/attributes'),
          },
          { title: 'Thêm mới' },
        ]}
        className={styles.breadcrumbNav}
      />

      {/* Form Card */}
      <Card className={styles.card}>
        <Form
          form={form}
          layout="vertical"
          onFinish={handleSubmit}
          initialValues={{
            type: 'select',
            is_required: false,
            is_filterable: true,
            sort_order: 0,
            status: 'active',
          }}
        >
          <Row gutter={24}>
            {/* Left Column - Basic Info */}
            <Col xs={24} md={12}>
              <div className={styles.section}>
                <h3>Thông tin cơ bản</h3>

                {/* Name */}
                <Form.Item
                  label="Tên thuộc tính"
                  name="name"
                  rules={[
                    { required: true, message: 'Vui lòng nhập tên thuộc tính' },
                    { min: 2, message: 'Tên phải có ít nhất 2 ký tự' },
                  ]}
                >
                  <Input placeholder="VD: Màu sắc, Kích thước, ..." />
                </Form.Item>

                {/* Slug */}
                <Form.Item
                  label="Slug (tùy chọn)"
                  name="slug"
                  tooltip="Tự động tạo từ tên nếu để trống"
                >
                  <Input placeholder="VD: color, size, ..." />
                </Form.Item>

                {/* Type */}
                <Form.Item
                  label="Loại thuộc tính"
                  name="type"
                  rules={[{ required: true, message: 'Vui lòng chọn loại' }]}
                >
                  <Select onChange={handleTypeChange}>
                    <Option value="text">
                      <Tag color="blue">Text</Tag> - Nhập tự do
                    </Option>
                    <Option value="select">
                      <Tag color="green">Select</Tag> - Chọn từ danh sách
                    </Option>
                    <Option value="color">
                      <Tag color="orange">Color</Tag> - Chọn màu
                    </Option>
                    <Option value="image">
                      <Tag color="purple">Image</Tag> - Chọn ảnh
                    </Option>
                  </Select>
                </Form.Item>

                {/* Sort Order */}
                <Form.Item
                  label="Thứ tự hiển thị"
                  name="sort_order"
                  tooltip="Số nhỏ hơn hiển thị trước"
                >
                  <InputNumber
                    min={0}
                    style={{ width: '100%' }}
                    placeholder="0"
                  />
                </Form.Item>
              </div>
            </Col>

            {/* Right Column - Settings */}
            <Col xs={24} md={12}>
              <div className={styles.section}>
                <h3>Cài đặt</h3>

                {/* Required */}
                <Form.Item
                  label="Bắt buộc"
                  name="is_required"
                  valuePropName="checked"
                  tooltip="Bắt buộc nhập khi tạo sản phẩm"
                >
                  <Switch checkedChildren="Có" unCheckedChildren="Không" />
                </Form.Item>

                {/* Filterable */}
                <Form.Item
                  label="Cho phép lọc"
                  name="is_filterable"
                  valuePropName="checked"
                  tooltip="Hiển thị trong bộ lọc sản phẩm"
                >
                  <Switch checkedChildren="Có" unCheckedChildren="Không" />
                </Form.Item>

                {/* Status */}
                <Form.Item
                  label="Trạng thái"
                  name="status"
                  rules={[{ required: true }]}
                >
                  <Select>
                    <Option value="active">
                      <Tag color="green">Active</Tag>
                    </Option>
                    <Option value="inactive">
                      <Tag color="red">Inactive</Tag>
                    </Option>
                  </Select>
                </Form.Item>
              </div>
            </Col>
          </Row>

          {/* Options Manager */}
          <Row>
            <Col span={24}>{renderOptionInput()}</Col>
          </Row>

          {/* Form Actions */}
          <Divider />
          <div className={styles.formActions}>
            <Space size="middle">
              <Button
                type="primary"
                htmlType="submit"
                icon={<SaveOutlined />}
                loading={loading}
                size="large"
              >
                Tạo thuộc tính
              </Button>

              <Button onClick={handleCancel} size="large">
                Hủy
              </Button>
            </Space>
          </div>
        </Form>
      </Card>
    </div>
  );
};

export default AttributeCreatePage;
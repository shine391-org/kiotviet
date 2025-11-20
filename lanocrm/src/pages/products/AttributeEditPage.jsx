/**
 * Attribute Edit Page
 * @file src/pages/products/AttributeEditPage.jsx
 * @description Page for editing product attributes and managing options
 */

import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
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
  Spin,
  Modal,
  Table,
  Tooltip,
  App,
} from 'antd';
import {
  ArrowLeftOutlined,
  PlusOutlined,
  DeleteOutlined,
  SaveOutlined,
  EditOutlined,
  LoadingOutlined,
} from '@ant-design/icons';
import { usePermission } from '../../utils/usePermission';
import * as attributeApi from '../../api/attributeApi';
import styles from './AttributeEditPage.module.css';

const { Option } = Select;

/**
 * AttributeEditPage Component
 */
const AttributeEditPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const { hasPermission } = usePermission();
  const [form] = Form.useForm();
  const { modal } = App.useApp();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [attribute, setAttribute] = useState(null);
  const [options, setOptions] = useState([]);
  const [attributeType, setAttributeType] = useState('select');

  // New option form
  const [newOption, setNewOption] = useState({
    option_name: '',
    color_code: '#000000',
    sort_order: 0,
  });

  const [optionUsageModal, setOptionUsageModal] = useState({
    visible: false,
    option: null,
    loading: false,
    products: [],
    includeDeleted: true,
  });

  // Edit option modal
  const [editModalVisible, setEditModalVisible] = useState(false);
  const [editingOption, setEditingOption] = useState(null);

  // Check permission
  useEffect(() => {
    if (!hasPermission('products.edit')) {
      message.error('Bạn không có quyền chỉnh sửa thuộc tính');
      navigate('/products/attributes');
    }
  }, [hasPermission, navigate]);

  useEffect(() => {
    if (id) {
      loadAttributeData();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);  

  /**
   * Load attribute and options
   */
  const loadAttributeData = async () => {
    try {
      setLoading(true);

      // Load attribute
      const attrResponse = await attributeApi.getAttributeDetail(id);

      if (!attrResponse.success || !attrResponse.data) {
        message.error('Không tìm thấy thuộc tính');
        navigate('/products/attributes');
        return;
      }

      const attr = attrResponse.data;
      setAttribute(attr);
      setAttributeType(attr.type);

      // Set form values
      form.setFieldsValue({
        name: attr.name,
        slug: attr.slug,
        type: attr.type,
        is_required: attr.is_required === 1,
        is_filterable: attr.is_filterable === 1,
        sort_order: attr.sort_order || 0,
        status: attr.status,
      });

      // Load options if not text type
      if (['select', 'color', 'image'].includes(attr.type)) {
        const optResponse = await attributeApi.getAttributeOptions(id);
        if (optResponse.success) {
          setOptions(optResponse.data);
        }
      }
    } catch (error) {
      console.error('Load attribute error:', error);
      message.error(error.message || 'Lỗi tải dữ liệu thuộc tính');
    } finally {
      setLoading(false);
    }
  };

  /**
   * Handle form submit
   */
  const handleSubmit = async (values) => {
    try {
      setSaving(true);

      const updateData = {
        name: values.name,
        slug: values.slug || null,
        type: values.type,
        is_required: values.is_required ? 1 : 0,
        is_filterable: values.is_filterable ? 1 : 0,
        sort_order: values.sort_order || 0,
        status: values.status || 'active',
      };

      const response = await attributeApi.updateAttribute(id, updateData);
      if (response.success) {
        modal.success({
          title: 'Cập nhật thành công',
          content: `Đã lưu thay đổi cho thuộc tính #${id}`,
          onOk: () => {
            navigate('/products/attributes');
          },
        });
      } else {
        message.error(response.message || 'Không thể cập nhật');
      }
    } catch (error) {
      console.error('Update attribute error:', error);
      message.error(error.message || 'Lỗi cập nhật thuộc tính');
    } finally {
      setSaving(false);
    }
  };

  /**
   * Add new option
   */
  const handleAddOption = async () => {
    if (!newOption.option_name.trim()) {
      message.warning('Vui lòng nhập tên giá trị');
      return;
    }

    // Check duplicate
    const duplicate = options.find(
      (opt) =>
        opt.option_name.toLowerCase() === newOption.option_name.toLowerCase()
    );

    if (duplicate) {
      message.warning('Giá trị này đã tồn tại');
      return;
    }

    try {
      const optionData = {
        option_name: newOption.option_name,
        color_code: attributeType === 'color' ? newOption.color_code : null,
        image_url: attributeType === 'image' ? newOption.image_url : null,
        sort_order: options.length,
      };

      const response = await attributeApi.createAttributeOption(id, optionData);

      if (response.success) {
        message.success('Thêm giá trị thành công');
        loadAttributeData(); // Reload options

        // Reset form
        setNewOption({
          option_name: '',
          color_code: '#000000',
          sort_order: 0,
        });
      } else {
        message.error(response.message || 'Không thể thêm giá trị');
      }
    } catch (error) {
      console.error('Add option error:', error);
      message.error(error.message || 'Lỗi thêm giá trị');
    }
  };

  /**
   * Open edit option modal
   */
  const handleEditOption = (option) => {
    setEditingOption(option);
    setEditModalVisible(true);
  };

  /**
   * Update option
   */
  const handleUpdateOption = async () => {
    if (!editingOption.option_name.trim()) {
      message.warning('Vui lòng nhập tên giá trị');
      return;
    }

    try {
      const response = await attributeApi.updateAttributeOption(
        editingOption.id,
        {
          option_name: editingOption.option_name,
          color_code:
            attributeType === 'color' ? editingOption.color_code : null,
          image_url:
            attributeType === 'image' ? editingOption.image_url : null,
          sort_order: editingOption.sort_order,
        }
      );

      if (response.success) {
        message.success('Cập nhật giá trị thành công');
        setEditModalVisible(false);
        setEditingOption(null);
        loadAttributeData();
      } else {
        message.error(response.message || 'Không thể cập nhật');
      }
    } catch (error) {
      console.error('Update option error:', error);
      message.error(error.message || 'Lỗi cập nhật giá trị');
    }
  };

  /**
   * Delete option
   */
  const handleDeleteOption = async (optionId) => {
    try {
      const response = await attributeApi.deleteAttributeOption(optionId);

      if (response.success) {
        message.success('Xóa giá trị thành công');
        loadAttributeData();
      } else {
        message.error(response.message || 'Không thể xóa giá trị');
      }
    } catch (error) {
      console.error('Delete option error:', error);
      message.error(error.message || 'Lỗi xóa giá trị');
    }
  };

  // Handler show modal chi tiết usage
const handleShowOptionUsage = async (option) => {
  setOptionUsageModal({ visible: true, option, loading: true, products: [], includeDeleted: true });
  try {
    const response = await attributeApi.getProductsByOption(option.id, true);
    setOptionUsageModal(prev => ({ 
      ...prev, 
      products: response.data || [],  // response.data chính là array products
      loading: false 
    }));
    console.log('Sản phẩm dùng option:', optionUsageModal.products);
  } catch (e) {
    message.error('Lỗi tải danh sách');
    setOptionUsageModal(prev => ({ ...prev, loading: false }));
  }
};

// Toggle active/all
const handleToggleIncludeDeletedOption = async (checked) => {
  setOptionUsageModal(prev => ({ ...prev, loading: true, includeDeleted: checked }));
  try {
    const response = await attributeApi.getProductsByOption(
      optionUsageModal.option.id,
      checked
    );
    setOptionUsageModal(prev => ({ 
      ...prev, 
      products: response.data || [], 
      loading: false 
    }));
  } catch (e) {
    message.error('Lỗi tải danh sách');
    setOptionUsageModal(prev => ({ ...prev, loading: false }));
  }
};

  /**
   * Cancel
   */
  const handleCancel = () => {
    navigate('/products/attributes');
  };

  /**
   * Render option input based on type
   */
  const renderOptionManager = () => {
    if (attributeType === 'text') {
      return (
        <div className={styles.optionNote}>
          <Tag color="blue">Loại Text không cần quản lý giá trị</Tag>
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
                placeholder="Tên giá trị mới"
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
              <span>Có {options.length} giá trị</span>
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

                <Space size="small">
                <Button
                  type="link"
                  style={{ padding: 0, fontWeight: 600 }}
                  onClick={() => handleShowOptionUsage(option)}
                >
                  Đang dùng: {option.usage_count}
                </Button>
                  <Button
                    type="text"
                    size="small"
                    icon={<EditOutlined />}
                    onClick={() => handleEditOption(option)}
                  />

                  <Tooltip title={option.usage_count > 0 
                      ? `Không thể xóa - ${option.usage_count} sản phẩm/biến thể đang sử dụng` 
                      : "Xóa"}>
                    <span>
                      <Popconfirm
                        title="Bạn có chắc muốn xóa giá trị này?"
                        description={`Option: ${option.option_name}`}
                        onConfirm={() => handleDeleteOption(option.id)}
                        okText="Xóa"
                        cancelText="Hủy"
                        okButtonProps={{ danger: true }}
                        disabled={option.usage_count > 0}
                      >
                        <Button
                          type="text"
                          danger
                          size="small"
                          icon={<DeleteOutlined />}
                          disabled={option.usage_count > 0}
                          // KHÔNG gán onClick cho trường hợp disabled
                        />
                      </Popconfirm>
                    </span>
                  </Tooltip>

                </Space>
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

  if (loading) {
    return (
      <div className={styles.container}>
        <div className={styles.loadingContainer}>
          <Spin
            indicator={<LoadingOutlined style={{ fontSize: 48 }} />}
            tip="Đang tải dữ liệu thuộc tính..."
          />
        </div>
      </div>
    );
  }

  if (!attribute) {
    return (
      <div className={styles.container}>
        <div className={styles.errorContainer}>
          <h2>Không tìm thấy thuộc tính</h2>
          <p>ID thuộc tính: {id}</p>
          <Button type="primary" onClick={handleCancel}>
            Quay lại danh sách
          </Button>
        </div>
      </div>
    );
  }

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
        <h1>Chỉnh sửa thuộc tính</h1>
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
          { title: attribute.name },
        ]}
        className={styles.breadcrumbNav}
      />

      {/* Summary Card */}
      <Card className={styles.summaryCard}>
        <Row gutter={16}>
          <Col span={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>ID:</span>
              <span className={styles.value}>{attribute.id}</span>
            </div>
          </Col>
          <Col span={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Loại:</span>
              <Tag
                color={
                  attribute.type === 'text'
                    ? 'blue'
                    : attribute.type === 'select'
                    ? 'green'
                    : attribute.type === 'color'
                    ? 'orange'
                    : 'purple'
                }
              >
                {attribute.type}
              </Tag>
            </div>
          </Col>
          <Col span={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Số giá trị:</span>
              <span className={styles.value}>{options.length}</span>
            </div>
          </Col>
          <Col span={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Trạng thái:</span>
              <Tag color={attribute.status === 'active' ? 'green' : 'red'}>
                {attribute.status}
              </Tag>
            </div>
          </Col>
        </Row>
      </Card>

      {/* Form Card */}
      <Card className={styles.card}>
        <Form form={form} layout="vertical" onFinish={handleSubmit}>
          <Row gutter={24}>
            {/* Left Column */}
            <Col xs={24} md={12}>
              <div className={styles.section}>
                <h3>Thông tin cơ bản</h3>

                <Form.Item
                  label="Tên thuộc tính"
                  name="name"
                  rules={[
                    { required: true, message: 'Vui lòng nhập tên' },
                    { min: 2, message: 'Tên phải có ít nhất 2 ký tự' },
                  ]}
                >
                  <Input placeholder="VD: Màu sắc, Kích thước, ..." />
                </Form.Item>

                <Form.Item
                  label="Slug"
                  name="slug"
                  tooltip="Tự động tạo từ tên nếu để trống"
                >
                  <Input placeholder="VD: color, size, ..." />
                </Form.Item>

                <Form.Item
                  label="Loại thuộc tính"
                  name="type"
                  tooltip="Không thể thay đổi loại sau khi tạo"
                >
                  <Select disabled>
                    <Option value="text">Text</Option>
                    <Option value="select">Select</Option>
                    <Option value="color">Color</Option>
                    <Option value="image">Image</Option>
                  </Select>
                </Form.Item>

                <Form.Item label="Thứ tự hiển thị" name="sort_order">
                  <InputNumber
                    min={0}
                    style={{ width: '100%' }}
                    placeholder="0"
                  />
                </Form.Item>
              </div>
            </Col>

            {/* Right Column */}
            <Col xs={24} md={12}>
              <div className={styles.section}>
                <h3>Cài đặt</h3>

                <Form.Item
                  label="Bắt buộc"
                  name="is_required"
                  valuePropName="checked"
                >
                  <Switch checkedChildren="Có" unCheckedChildren="Không" />
                </Form.Item>

                <Form.Item
                  label="Cho phép lọc"
                  name="is_filterable"
                  valuePropName="checked"
                >
                  <Switch checkedChildren="Có" unCheckedChildren="Không" />
                </Form.Item>

                <Form.Item label="Trạng thái" name="status">
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
            <Col span={24}>{renderOptionManager()}</Col>
          </Row>

          {/* Form Actions */}
          <Divider />
          <div className={styles.formActions}>
            <Space size="middle">
              <Button
                type="primary"
                htmlType="submit"
                icon={<SaveOutlined />}
                loading={saving}
                size="large"
              >
                Lưu thay đổi
              </Button>

              <Button onClick={handleCancel} size="large">
                Hủy
              </Button>
            </Space>
          </div>
        </Form>
      </Card>

      {/* Edit Option Modal */}
      <Modal
        title="Chỉnh sửa giá trị"
        open={editModalVisible}
        onOk={handleUpdateOption}
        onCancel={() => {
          setEditModalVisible(false);
          setEditingOption(null);
        }}
        okText="Lưu"
        cancelText="Hủy"
      >
        {editingOption && (
          <Space direction="vertical" style={{ width: '100%' }} size="middle">
            <div>
              <label style={{ display: 'block', marginBottom: 8 }}>
                Tên giá trị
              </label>
              <Input
                value={editingOption.option_name}
                onChange={(e) =>
                  setEditingOption({
                    ...editingOption,
                    option_name: e.target.value,
                  })
                }
              />
            </div>

            {attributeType === 'color' && (
              <div>
                <label style={{ display: 'block', marginBottom: 8 }}>
                  Mã màu
                </label>
                <ColorPicker
                  value={editingOption.color_code}
                  onChange={(color) =>
                    setEditingOption({
                      ...editingOption,
                      color_code: color.toHexString(),
                    })
                  }
                  showText
                />
              </div>
            )}

            {attributeType === 'image' && (
              <div>
                <label style={{ display: 'block', marginBottom: 8 }}>
                  URL hình ảnh
                </label>
                <Input
                  value={editingOption.image_url}
                  onChange={(e) =>
                    setEditingOption({
                      ...editingOption,
                      image_url: e.target.value,
                    })
                  }
                />
              </div>
            )}

            <div>
              <label style={{ display: 'block', marginBottom: 8 }}>
                Thứ tự
              </label>
              <InputNumber
                value={editingOption.sort_order}
                onChange={(value) =>
                  setEditingOption({
                    ...editingOption,
                    sort_order: value,
                  })
                }
                style={{ width: '100%' }}
              />
            </div>
          </Space>
        )}
      </Modal>
      <Modal
        title={`Sản phẩm/biến thể sử dụng option: ${optionUsageModal.option?.option_name || ''}`}
        open={optionUsageModal.visible}
        onCancel={() => setOptionUsageModal({ ...optionUsageModal, visible: false })}
        width={1000}
        footer={null}
      >
        <div style={{ marginBottom: 8 }}>
          <Switch
            checked={optionUsageModal.includeDeleted}
            onChange={handleToggleIncludeDeletedOption}
            checkedChildren="Hiện cả đã xóa"
            unCheckedChildren="Chỉ hiện active"
          />
        </div>
        <Table
          dataSource={optionUsageModal.products}
          loading={optionUsageModal.loading}
          rowKey={(record) => `${record.product_id}-${record.variant_id || 'simple'}`}
          pagination={{ pageSize: 20 }}
          columns={[
            { title: 'Mã sản phẩm', dataIndex: 'product_code' },
            { title: 'Tên sản phẩm', dataIndex: 'product_name' },
            { title: 'SKU Biến thể', dataIndex: 'variant_sku' },
            { title: 'Tên biến thể', dataIndex: 'variant_name' },
            { title: 'Trạng thái',
              render: (_, record) => 
                record.variant_deleted_at || record.mapping_deleted_at
                  ? <Tag color="red">Đã xoá mềm</Tag>
                  : <Tag color="green">Hoạt động</Tag>,
            }
          ]}
      />
      </Modal>

    </div>
  );
};

export default AttributeEditPage;
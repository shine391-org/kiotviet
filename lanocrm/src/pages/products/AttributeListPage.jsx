/**
 * Attribute List Page
 * @file src/pages/products/AttributeListPage.jsx
 * @description Page for managing product attributes with CRUD operations
 */

import React, { useEffect, useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import {
  Card,
  Table,
  Button,
  Space,
  Input,
  Tag,
  Popconfirm,
  Breadcrumb,
  Tooltip,
  Select,
  Modal,
  Image,
  Switch,
  App,
} from 'antd';
import {
  PlusOutlined,
  EditOutlined,
  DeleteOutlined,
  SearchOutlined,
  ReloadOutlined,
} from '@ant-design/icons';
import { usePermission } from '../../utils/usePermission';
import * as attributeApi from '../../api/attributeApi';
import styles from './AttributeListPage.module.css';

const { Search } = Input;
const { Option } = Select;

/**
 * AttributeListPage Component
 */
const AttributeListPage = () => {
  const navigate = useNavigate();
  const { message } = App.useApp();
  const { hasPermission } = usePermission();
  const [loading, setLoading] = useState(false);
  const [attributes, setAttributes] = useState([]);
  const [filteredAttributes, setFilteredAttributes] = useState([]);
  // State mới
  const [relatedProductsModal, setRelatedProductsModal] = useState({
    visible: false,
    attributeId: null,
    attributeName: '',
    products: [],
    loading: false,
    includeDeleted: false,
  });
  const [searchText, setSearchText] = useState('');
  const [filterType, setFilterType] = useState('all');
  const [filterStatus, setFilterStatus] = useState('all');

  // Load attributes on mount
  useEffect(() => {
    loadAttributes();
  }, []);

  // Filter attributes when search or filters change
  useEffect(() => {
    applyFilters();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [searchText, filterType, filterStatus, attributes]);  

  // Check permissions
  useEffect(() => {
    if (!hasPermission('products.view')) {
      message.error('Bạn không có quyền xem thuộc tính sản phẩm');
      navigate('/dashboard');
    }
  }, [hasPermission, navigate]);

  /**
   * Load all attributes from API
   */
  const loadAttributes = async () => {
    try {
      setLoading(true);
      const response = await attributeApi.getAttributes();

      if (response.success) {
        setAttributes(response.data);
        message.success(response.message || 'Tải danh sách thành công');
      } else {
        message.error(response.message || 'Không thể tải danh sách');
      }
    } catch (error) {
      console.error('Load attributes error:', error);
      message.error(error.message || 'Lỗi tải danh sách thuộc tính');
    } finally {
      setLoading(false);
    }
  };

  /**
   * Apply filters to attributes list
   */
  const applyFilters = () => {
    let filtered = [...attributes];

    // Search filter
    if (searchText) {
      filtered = filtered.filter((attr) =>
        attr.name?.toLowerCase().includes(searchText.toLowerCase()) ||
        attr.slug?.toLowerCase().includes(searchText.toLowerCase())
      );
    }

    // Type filter
    if (filterType !== 'all') {
      filtered = filtered.filter((attr) => attr.type === filterType);
    }

    // Status filter
    if (filterStatus !== 'all') {
      filtered = filtered.filter((attr) => attr.status === filterStatus);
    }

    setFilteredAttributes(filtered);
  };

  /**
   * Handle search
   */
  const handleSearch = (value) => {
    setSearchText(value);
  };

  /**
   * Handle delete attribute
   */
  const handleDelete = async (id) => {
    try {
      const response = await attributeApi.deleteAttribute(id);

      if (response.success) {
        message.success('Xóa thuộc tính thành công');
        loadAttributes();
      } else {
        message.error(response.message || 'Không thể xóa thuộc tính');
      }
    } catch (error) {
      console.error('Delete attribute error:', error);
      message.error(error.message || 'Lỗi xóa thuộc tính');
    }
  };

  /**
   * Navigate to create page
   */
  const handleCreate = () => {
    navigate('/products/attributes/create');
  };

  /**
   * Navigate to edit page
   */
  const handleEdit = (id) => {
    navigate(`/products/attributes/edit/${id}`);
  };

  /**
   * Get type tag color
   */
  const getTypeTagColor = (type) => {
    const colors = {
      text: 'blue',
      select: 'green',
      color: 'orange',
      image: 'purple',
    };
    return colors[type] || 'default';
  };

  /**
   * Get type label
   */
  const getTypeLabel = (type) => {
    const labels = {
      text: 'Text',
      select: 'Select',
      color: 'Color',
      image: 'Image',
    };
    return labels[type] || type;
  };

  // Handler click vào usage_count
const handleShowRelatedProducts = async (attributeId, attributeName) => {
  setRelatedProductsModal({
    visible: true,
    attributeId,
    attributeName,
    products: [],
    loading: true,
    includeDeleted: false,
  });
  
  try {
    const response = await attributeApi.getProductsByAttribute(attributeId, false);
    setRelatedProductsModal(prev => ({
      ...prev,
      products: response.data,
      loading: false,
    }));
  } catch (error) {
    message.error(error.message);
    setRelatedProductsModal(prev => ({ ...prev, loading: false }));
  }
};

// Handler toggle include deleted variants
const handleToggleIncludeDeleted = async (checked) => {
  setRelatedProductsModal(prev => ({ ...prev, loading: true, includeDeleted: checked }));
  
  try {
    const response = await attributeApi.getProductsByAttribute(
      relatedProductsModal.attributeId, 
      checked
    );
    setRelatedProductsModal(prev => ({
      ...prev,
      products: response.data,
      loading: false,
    }));
  } catch (error) {
    message.error(error.message);
    setRelatedProductsModal(prev => ({ ...prev, loading: false }));
  }
};

  /**
   * Table columns configuration
   */
  const columns = [
    {
      title: 'ID',
      dataIndex: 'id',
      key: 'id',
      width: 80,
      sorter: (a, b) => a.id - b.id,
    },
    {
      title: 'Tên thuộc tính',
      dataIndex: 'name',
      key: 'name',
      width: 200,
      sorter: (a, b) => a.name.localeCompare(b.name),
      render: (text, record) => (
        <div>
          <div style={{ fontWeight: 600 }}>{text}</div>
          {record.slug && (
            <div style={{ fontSize: '12px', color: '#999' }}>
              Slug: {record.slug}
            </div>
          )}
        </div>
      ),
    },
    {
      title: 'Loại',
      dataIndex: 'type',
      key: 'type',
      width: 120,
      filters: [
        { text: 'Text', value: 'text' },
        { text: 'Select', value: 'select' },
        { text: 'Color', value: 'color' },
        { text: 'Image', value: 'image' },
      ],
      onFilter: (value, record) => record.type === value,
      render: (type) => (
        <Tag color={getTypeTagColor(type)}>{getTypeLabel(type)}</Tag>
      ),
    },
    {
      title: 'Bắt buộc',
      dataIndex: 'is_required',
      key: 'is_required',
      width: 100,
      align: 'center',
      render: (is_required) => {
        // ✅ FIX: Convert to number and check
        const isRequired = parseInt(is_required, 10);
        return isRequired === 1 ? (
          <Tag color="red">Có</Tag>
        ) : (
          <Tag color="default">Không</Tag>
        );
      },
    },
    {
      title: 'Có thể lọc',
      dataIndex: 'is_filterable',
      key: 'is_filterable',
      width: 120,
      align: 'center',
      render: (is_filterable) => {
        // ✅ FIX: Convert to number and check
        const isFilterable = parseInt(is_filterable, 10);
        return isFilterable === 1 ? (
          <Tag color="green">Có</Tag>
        ) : (
          <Tag color="default">Không</Tag>
        );
      },
    },
    {
      title: 'Thứ tự',
      dataIndex: 'sort_order',
      key: 'sort_order',
      width: 100,
      align: 'center',
      sorter: (a, b) => a.sort_order - b.sort_order,
    },
    {
      title: 'Trạng thái',
      dataIndex: 'status',
      key: 'status',
      width: 120,
      filters: [
        { text: 'Active', value: 'active' },
        { text: 'Inactive', value: 'inactive' },
      ],
      onFilter: (value, record) => record.status === value,
      render: (status) =>
        status === 'active' ? (
          <Tag color="green">Active</Tag>
        ) : (
          <Tag color="red">Inactive</Tag>
        ),
    },
    {
      title: 'Used',
      dataIndex: 'usage_count',
      key: 'usage_count',
      width: 140,
      align: 'center',
      sorter: (a, b) => a.usage_count - b.usage_count,
      render: (count, record) => (
        count > 0 ? (
          <Tooltip title="Bấm để xem danh sách sản phẩm/biến thể">
            <Button
              type="link"
              style={{ padding: 0, fontWeight: 600 }}
              onClick={() => handleShowRelatedProducts(record.id, record.name)}
            >
              {count}
            </Button>
          </Tooltip>
        ) : (
          <Tag color="default">0</Tag>
        )
      ),
    },
    {
      title: 'Hành động',
      key: 'actions',
      width: 150,
      fixed: 'right',
      render: (_, record) => (
        <Space size="small">
          {hasPermission('products.edit') && (
            <Tooltip title="Chỉnh sửa">
              <Button
                type="primary"
                size="small"
                icon={<EditOutlined />}
                onClick={() => handleEdit(record.id)}
              />
            </Tooltip>
          )}

          {hasPermission('products.delete') && (
            <Popconfirm
              title="Bạn có chắc muốn xóa thuộc tính này?"
              description={`Thuộc tính: ${record.name}`}
              onConfirm={() => handleDelete(record.id)}
              okText="Xóa"
              cancelText="Hủy"
              okButtonProps={{ danger: true }}
            >
              <Tooltip 
                title={record.usage_count > 0
                  ? `Không thể xóa - ${record.usage_count} sản phẩm/biến thể đang sử dụng`
                  : "Xóa"}>
                <Button
                  type="primary"
                  danger
                  size="small"
                  icon={<DeleteOutlined />}
                  onClick={() => record.usage_count === 0 && handleDelete(record.id)}
                  disabled={record.usage_count > 0}
                />
              </Tooltip>
            </Popconfirm>
          )}
        </Space>
      ),
    },
  ];

  return (
    <div className={styles.container}>
      {/* Header */}
      <div className={styles.header}>
        <div>
          <h1>Quản lý thuộc tính sản phẩm</h1>
          <Breadcrumb
            items={[
              { title: 'Dashboard', onClick: () => navigate('/dashboard') },
              { title: 'Sản phẩm', onClick: () => navigate('/products') },
              { title: 'Thuộc tính' },
            ]}
            className={styles.breadcrumb}
          />
        </div>

        {hasPermission('products.create') && (
          <Button
            type="primary"
            icon={<PlusOutlined />}
            onClick={handleCreate}
            size="large"
          >
            Thêm thuộc tính
          </Button>
        )}
      </div>

      {/* Filters Card */}
      <Card className={styles.filterCard}>
        <Space size="middle" wrap style={{ width: '100%' }}>
          {/* Search */}
          <Space.Compact>
            <Input
              placeholder="Tìm theo tên hoặc slug..."
              allowClear
              onChange={(e) => setSearchText(e.target.value)}
              style={{ width: 230 }}
              value={searchText}
            />
            <Button
              icon={<SearchOutlined />}
              onClick={() => handleSearch(searchText)}
            />
          </Space.Compact>

          {/* Type Filter */}
          <Select
            placeholder="Lọc theo loại"
            style={{ width: 150 }}
            value={filterType}
            onChange={setFilterType}
          >
            <Option value="all">Tất cả loại</Option>
            <Option value="text">Text</Option>
            <Option value="select">Select</Option>
            <Option value="color">Color</Option>
            <Option value="image">Image</Option>
          </Select>

          {/* Status Filter */}
          <Select
            placeholder="Lọc theo trạng thái"
            style={{ width: 150 }}
            value={filterStatus}
            onChange={setFilterStatus}
          >
            <Option value="all">Tất cả trạng thái</Option>
            <Option value="active">Active</Option>
            <Option value="inactive">Inactive</Option>
          </Select>

          {/* Reload Button */}
          <Button
            icon={<ReloadOutlined />}
            onClick={loadAttributes}
            loading={loading}
          >
            Tải lại
          </Button>

          {/* Stats */}
          <div style={{ marginLeft: 'auto', fontSize: '14px', color: '#999' }}>
            Hiển thị: <strong>{filteredAttributes.length}</strong> / Tổng:{' '}
            <strong>{attributes.length}</strong> thuộc tính
          </div>
        </Space>
      </Card>

      {/* Table Card */}
      <Card className={styles.tableCard}>
        <Table
          columns={columns}
          dataSource={filteredAttributes}
          rowKey="id"
          loading={loading}
          pagination={{
            pageSize: 20,
            showSizeChanger: true,
            showTotal: (total) => `Tổng ${total} thuộc tính`,
            pageSizeOptions: ['10', '20', '50', '100'],
          }}
          scroll={{ x: 1200 }}
          bordered
        />
      </Card>
      
      <Modal
        title={`Sản phẩm/biến thể sử dụng thuộc tính: ${relatedProductsModal.attributeName}`}
        open={relatedProductsModal.visible}
        onCancel={() => setRelatedProductsModal({ ...relatedProductsModal, visible: false })}
        width={1200}
        footer={null}
      >
        <div style={{ marginBottom: 16 }}>
          <Switch
            checked={relatedProductsModal.includeDeleted}
            onChange={handleToggleIncludeDeleted}
            checkedChildren="Hiện cả variant đã xóa"
            unCheckedChildren="Chỉ variant hoạt động"
          />
        </div>
        
        <Table
          dataSource={relatedProductsModal.products}
          loading={relatedProductsModal.loading}
          rowKey={(record) => `${record.product_id}-${record.variant_id || 'simple'}`}
          pagination={{ pageSize: 20 }}
          columns={[
            {
              title: 'Ảnh',
              width: 80,
              render: (_, record) => (
                <Image
                  src={record.product_image || '/placeholder.png'}
                  width={50}
                  height={50}
                  style={{ objectFit: 'cover', borderRadius: 4 }}
                />
              ),
            },
            {
              title: 'Mã sản phẩm',
              dataIndex: 'product_code',
              width: 120,
            },
            {
              title: 'Tên sản phẩm',
              dataIndex: 'product_name',
              ellipsis: true,
            },
            {
              title: 'Loại',
              width: 100,
              render: (_, record) => (
                record.variant_id ? (
                  <Tag color="blue">Biến thể</Tag>
                ) : (
                  <Tag color="green">Đơn giản</Tag>
                )
              ),
            },
            {
              title: 'SKU Biến thể',
              dataIndex: 'variant_sku',
              width: 120,
              render: (sku) => sku || '-',
            },
            {
              title: 'Tên biến thể',
              dataIndex: 'variant_name',
              ellipsis: true,
              render: (name) => name || '-',
            },
            {
              title: 'Giá trị',
              width: 150,
              render: (_, record) => (
                record.option_name || record.value_text || '-'
              ),
            },
            {
              title: 'Trạng thái',
              width: 120,
              render: (_, record) => {
                if (record.variant_deleted_at) {
                  return <Tag color="red">Đã xóa mềm</Tag>;
                }
                return <Tag color="green">Hoạt động</Tag>;
              },
            },
            {
              title: 'Hành động',
              width: 100,
              fixed: 'right',
              render: (_, record) => (
                <Button
                  type="link"
                  size="small"
                  onClick={() => {
                    if (record.variant_id) {
                      navigate(`/products/variants/edit/${record.variant_id}`);
                    } else {
                      navigate(`/products/edit/${record.product_id}`);
                    }
                  }}
                >
                  Xem
                </Button>
              ),
            },
          ]}
        />
      </Modal>
    </div>
  );
};

export default AttributeListPage;
import React, { useEffect, useMemo, useState, useCallback } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Card, Button, Input, Table, Space, Select, App, Row, Col, InputNumber, Tooltip, Popover } from 'antd';
import {
  PlusOutlined,
  ReloadOutlined,
  DeleteOutlined,
  SettingOutlined,
  QuestionCircleOutlined,
  LeftOutlined,
  RightOutlined,
  ImportOutlined,
  ExportOutlined
} from '@ant-design/icons';
import { fetchPriceLists, createPriceList, deletePriceList, resetPriceListState } from '../../store/slices/priceListSlice';
import { fetchProducts, setFilters } from '../../store/slices/productSlice';
import CreatePriceListModal from '../../components/price-lists/CreatePriceListModal';
import PriceFormulaModal from '../../components/price-lists/PriceFormulaModal';
import priceListApi from '../../api/priceListApi';

const PriceListPage = () => {
  const { message } = App.useApp();
  const dispatch = useDispatch();

  // Price list state
  const {
    items: priceLists,
    loading: priceListLoading,
    saving,
    createSuccess,
    error: priceListError
  } = useSelector(state => state.priceList);

  // Product state for table
  const { items: products, loading: productLoading, pagination, filters } = useSelector(state => state.product);

  const [selectedRowKeys, setSelectedRowKeys] = useState([]);
  const [editingKey, setEditingKey] = useState('');
  const [editingPrice, setEditingPrice] = useState(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [formulaModalOpen, setFormulaModalOpen] = useState(false);
  const [formulaProduct, setFormulaProduct] = useState(null);
  const [selectedPriceListId, setSelectedPriceListId] = useState(null);

  // Fetch price lists on mount
  useEffect(() => {
    dispatch(fetchPriceLists({ page: 1, limit: 100 }));
  }, [dispatch]);

  // Fetch products based on filters
  useEffect(() => {
    dispatch(fetchProducts(filters));
  }, [dispatch, filters]);

  // Handle create success
  useEffect(() => {
    if (createSuccess) {
      message.success('Tạo bảng giá thành công');
      setModalOpen(false);
      dispatch(fetchPriceLists({ page: 1, limit: 100 }));
      dispatch(resetPriceListState());
    }
  }, [createSuccess, dispatch, message]);

  // Handle error
  useEffect(() => {
    if (priceListError) {
      message.error(priceListError);
      dispatch(resetPriceListState());
    }
  }, [priceListError, dispatch, message]);

  // When price list is selected, refetch products with that price list
  useEffect(() => {
    if (selectedPriceListId) {
      // Update filters to include price_list_id - backend will calculate adjusted prices
      dispatch(setFilters({ ...filters, price_list_id: selectedPriceListId }));
    } else {
      // Remove price_list_id from filters
      const { price_list_id, ...rest } = filters;
      if (price_list_id) {
        dispatch(setFilters(rest));
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedPriceListId, dispatch]);

  const handleSearch = (value) => {
    dispatch(setFilters({ ...filters, search: value, page: 1 }));
  };

  const handleFilterChange = (key, value) => {
    dispatch(setFilters({ ...filters, [key]: value, page: 1 }));
  };

  const handlePageChange = (page, pageSize) => {
    dispatch(setFilters({ ...filters, page, limit: pageSize }));
  };

  const handleRefresh = () => {
    dispatch(fetchProducts(filters));
    dispatch(fetchPriceLists({ page: 1, limit: 100 }));
  };

  const handleDelete = () => {
    if (selectedRowKeys.length === 0) {
      message.warning('Vui lòng chọn ít nhất một mục để xóa');
      return;
    }
    message.info('Tính năng xóa đang được phát triển');
  };

  const handleSettings = () => {
    message.info('Tính năng cài đặt đang được phát triển');
  };

  const handleHelp = () => {
    message.info('Tính năng trợ giúp đang được phát triển');
  };

  const handleAdd = () => {
    setModalOpen(true);
  };

  const handleModalClose = () => {
    setModalOpen(false);
  };

  const handleModalSave = useCallback((payload) => {
    dispatch(createPriceList(payload));
  }, [dispatch]);

  const handleImport = () => {
    message.info('Tính năng nhập dữ liệu đang được phát triển');
  };

  const handleExport = () => {
    message.info('Tính năng xuất dữ liệu đang được phát triển');
  };

  const openFormulaModal = (record) => {
    setFormulaProduct(record);
    setFormulaModalOpen(true);
  };

  const handleFormulaSave = async (payload) => {
    try {
      if (!selectedPriceListId) return;

      if (payload.apply_to_all) {
        // Batch update
        await priceListApi.applyFormula(selectedPriceListId, payload);
        message.success('Đang áp dụng công thức cho toàn bộ bảng giá...');
      } else {
        // Single update
        await priceListApi.saveItems(selectedPriceListId, [{
          product_id: formulaProduct.id,
          price: payload.calculated_price
        }]);
        message.success('Cập nhật giá thành công');
      }

      setFormulaModalOpen(false);
      setFormulaProduct(null);
      dispatch(fetchProducts(filters));
    } catch (err) {
      console.error(err);
      message.error('Áp dụng công thức thất bại');
    }
  };

  const isEditing = (record) => record.key === editingKey;

  const edit = (record) => {
    setEditingKey(record.key);
    setEditingPrice(record.price);
  };

  const save = async (key) => {
    try {
      if (!selectedPriceListId) {
        message.warning('Vui lòng chọn bảng giá trước khi sửa');
        return;
      }

      const payload = [{
        product_id: key,
        price: editingPrice
      }];

      await priceListApi.saveItems(selectedPriceListId, payload);

      message.success('Cập nhật giá thành công');
      setEditingKey('');
      setEditingPrice(null);
      dispatch(fetchProducts(filters));
    } catch (err) {
      console.error(err);
      message.error('Cập nhật giá thất bại');
    }
  };

  const cancel = () => {
    setEditingKey('');
    setEditingPrice(null);
  };

  // Price list options from API
  const priceListOptions = useMemo(() => {
    return (priceLists || []).map(pl => ({
      label: pl.name,
      value: pl.id,
    }));
  }, [priceLists]);

  const columns = useMemo(() => [
    {
      title: 'Mã hàng',
      dataIndex: 'code',
      key: 'code',
      width: 120,
      sorter: true,
      filterDropdown: ({ setSelectedKeys, selectedKeys, confirm, clearFilters }) => (
        <div style={{ padding: 8 }}>
          <Input
            placeholder="Tìm mã hàng"
            value={selectedKeys[0]}
            onChange={(e) => setSelectedKeys(e.target.value ? [e.target.value] : [])}
            onPressEnter={() => confirm()}
            style={{ width: 188, marginBottom: 8, display: 'block' }}
          />
          <Space>
            <Button
              type="primary"
              onClick={() => confirm()}
              icon={<i className="anticon anticon-search" />}
              size="small"
              style={{ width: 90 }}
            >
              Tìm
            </Button>
            <Button onClick={() => clearFilters()} size="small" style={{ width: 90 }}>
              Reset
            </Button>
          </Space>
        </div>
      ),
      filterIcon: (filtered) => (
        <i className="anticon anticon-search" style={{ color: filtered ? '#1890ff' : undefined }} />
      ),
    },
    {
      title: 'Tên hàng',
      dataIndex: 'name',
      key: 'name',
      sorter: true,
      filterDropdown: ({ setSelectedKeys, selectedKeys, confirm, clearFilters }) => (
        <div style={{ padding: 8 }}>
          <Input
            placeholder="Tìm tên hàng"
            value={selectedKeys[0]}
            onChange={(e) => setSelectedKeys(e.target.value ? [e.target.value] : [])}
            onPressEnter={() => confirm()}
            style={{ width: 188, marginBottom: 8, display: 'block' }}
          />
          <Space>
            <Button
              type="primary"
              onClick={() => confirm()}
              icon={<i className="anticon anticon-search" />}
              size="small"
              style={{ width: 90 }}
            >
              Tìm
            </Button>
            <Button onClick={() => clearFilters()} size="small" style={{ width: 90 }}>
              Reset
            </Button>
          </Space>
        </div>
      ),
      filterIcon: (filtered) => (
        <i className="anticon anticon-search" style={{ color: filtered ? '#1890ff' : undefined }} />
      ),
    },
    {
      title: 'Tồn kho',
      dataIndex: 'stock',
      key: 'stock',
      width: 100,
      render: (stock) => stock || 0,
    },
    {
      title: 'Giá vốn',
      dataIndex: 'cost_price',
      key: 'cost_price',
      width: 120,
      render: (price) => price ? `${price.toLocaleString('vi-VN')}đ` : '0đ',
    },
    {
      title: 'Giá nhập cuối',
      dataIndex: 'last_purchase_price',
      key: 'last_purchase_price',
      width: 120,
      render: (price) => price ? `${price.toLocaleString('vi-VN')}đ` : '0đ',
    },
    {
      title: 'Bảng giá chung',
      dataIndex: 'price',
      key: 'price',
      width: 150,
      editable: true,
      render: (price, record) => {
        const editable = isEditing(record);
        return editable ? (
          <Popover
            content={
              <span>
                Hoặc sử dụng <a onClick={() => openFormulaModal(record)}>Công thức</a>
              </span>
            }
            trigger="focus"
          >
            <InputNumber
              style={{ width: '100%' }}
              value={editingPrice}
              onChange={(value) => setEditingPrice(value)}
              formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
              parser={(value) => value.replace(/\$\s?|(,*)/g, '')}
              addonAfter="đ"
            />
          </Popover>
        ) : (
          <span>{price ? `${price.toLocaleString('vi-VN')}đ` : '0đ'}</span>
        );
      },
    },
    {
      title: 'Thao tác',
      key: 'actions',
      width: 100,
      render: (_, record) => {
        const editable = isEditing(record);
        return editable ? (
          <Space>
            <Button size="small" type="primary" onClick={() => save(record.key)}>
              Lưu
            </Button>
            <Button size="small" onClick={cancel}>
              Hủy
            </Button>
          </Space>
        ) : (
          <Button size="small" type="link" onClick={() => edit(record)}>
            Sửa
          </Button>
        );
      },
    },
  ], [editingKey, editingPrice]);

  // Add adjusted price column when a price list is selected
  const adjustedPriceColumn = useMemo(() => {
    if (!selectedPriceListId) return null;
    const selectedList = priceLists.find(pl => pl.id === selectedPriceListId);
    return {
      title: selectedList ? selectedList.name : 'Giá điều chỉnh',
      dataIndex: 'price_after_discount',
      key: 'price_after_discount',
      width: 150,
      render: (adjustedPrice, record) => {
        if (adjustedPrice === undefined || adjustedPrice === null) {
          return <span style={{ color: '#999' }}>—</span>;
        }
        const basePrice = record.price || 0;
        const diff = adjustedPrice - basePrice;
        const color = diff < 0 ? '#52c41a' : (diff > 0 ? '#ff4d4f' : '#333');
        return (
          <Tooltip title={diff !== 0 ? `Chênh lệch: ${diff.toLocaleString('vi-VN')}đ` : 'Không thay đổi'}>
            <span style={{ color, fontWeight: diff !== 0 ? 'bold' : 'normal' }}>
              {adjustedPrice.toLocaleString('vi-VN')}đ
            </span>
          </Tooltip>
        );
      },
    };
  }, [selectedPriceListId, priceLists]);

  const rowSelection = {
    selectedRowKeys,
    onChange: setSelectedRowKeys,
  };

  const mergedColumns = useMemo(() => {
    let cols = columns.map((col) => {
      if (!col.editable) {
        return col;
      }
      return {
        ...col,
        onCell: (record) => ({
          record,
          title: col.title,
          editing: isEditing(record),
        }),
      };
    });

    // Insert adjusted price column before actions column when price list is selected
    if (adjustedPriceColumn) {
      const actionsIndex = cols.findIndex(c => c.key === 'actions');
      if (actionsIndex > 0) {
        cols.splice(actionsIndex, 0, adjustedPriceColumn);
      } else {
        cols.push(adjustedPriceColumn);
      }
    }

    return cols;
  }, [columns, adjustedPriceColumn]);

  // Transform product data to table format
  const dataSource = useMemo(() => {
    return products.map((item, index) => ({
      key: item.id || index,
      id: item.id,
      code: item.code || '',
      name: item.name || '',
      // API trả về stock_quantity, không phải stock
      stock: item.stock_quantity || item.stock || 0,
      // API trả về purchase_price (giá nhập), không phải cost_price
      cost_price: item.purchase_price || item.cost_price || 0,
      // last_purchase_price có thể không có trong API, dùng purchase_price thay thế
      last_purchase_price: item.last_purchase_price || item.purchase_price || 0,
      // API trả về selling_price, không phải price
      price: item.selling_price || item.price || item.base_price || 0,
      // Giá sau khi áp dụng bảng giá (từ backend khi có price_list_id)
      price_after_discount: item.price_after_discount,
      applied_price_list_name: item.applied_price_list_name,
    }));
  }, [products]);

  const paginationConfig = {
    current: pagination.page,
    pageSize: pagination.limit,
    total: pagination.total,
    showSizeChanger: true,
    showQuickJumper: true,
    showTotal: (total, range) => `Hiển thị ${range[0]}-${range[1]} của ${total} mục`,
    pageSizeOptions: ['10', '20', '50', '100'],
    onChange: handlePageChange,
    onShowSizeChange: handlePageChange,
    itemRender: (current, type, originalElement) => {
      if (type === 'prev') {
        return <Button icon={<LeftOutlined />}>Trước</Button>;
      }
      if (type === 'next') {
        return <Button icon={<RightOutlined />}>Sau</Button>;
      }
      if (type === 'page') {
        return <Button>{current}</Button>;
      }
      return originalElement;
    },
  };

  return (
    <div style={{ height: '100vh', display: 'flex', flexDirection: 'column' }}>
      {/* Header */}
      <div style={{ padding: '12px 24px', background: '#fff', borderBottom: '1px solid #f0f0f0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
          <h2 style={{ margin: 0, fontSize: '20px', fontWeight: 'bold' }}>
            {selectedPriceListId ? priceLists.find(p => p.id === selectedPriceListId)?.name : 'Bảng giá chung'}
          </h2>
          <Input.Search
            placeholder="Theo mã, tên hàng"
            style={{ width: 300 }}
            onSearch={handleSearch}
            allowClear
          />
        </div>
        <Space>
          <Button type="primary" icon={<PlusOutlined />} onClick={handleAdd} />
          <Tooltip title="Nhân bản"><Button icon={<i className="anticon anticon-copy" />} /></Tooltip>
          <Tooltip title="Đổi giao diện"><Button icon={<i className="anticon anticon-table" />} /></Tooltip>
          <Tooltip title="Ẩn hiện cột"><Button icon={<SettingOutlined />} /></Tooltip>
          <Button icon={<SettingOutlined />} href="/man/#/Settings?SettingType=products" />
          <Button icon={<QuestionCircleOutlined />} href="#" />
        </Space>
      </div>

      <div style={{ flex: 1, display: 'flex', overflow: 'hidden' }}>
        {/* Sidebar */}
        <div style={{ width: '280px', background: '#f5f5f5', borderRight: '1px solid #e8e8e8', padding: '16px', overflowY: 'auto' }}>
          {/* Section: Bảng giá */}
          <div style={{ marginBottom: '24px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
              <span style={{ fontWeight: 'bold' }}>Bảng giá</span>
              <a onClick={handleAdd} style={{ color: '#1890ff' }}>Tạo mới</a>
            </div>
            <Select
              style={{ width: '100%' }}
              placeholder="Chọn bảng giá"
              allowClear
              loading={priceListLoading}
              value={selectedPriceListId}
              onChange={(value) => {
                setSelectedPriceListId(value);
                handleFilterChange('price_list_id', value);
              }}
              options={priceListOptions}
            />
            {!selectedPriceListId && (
              <div style={{ marginTop: '8px', padding: '4px 8px', background: '#e6f7ff', border: '1px solid #91d5ff', borderRadius: '2px', display: 'flex', justifyContent: 'space-between' }}>
                <span style={{ color: '#1890ff' }}>Bảng giá chung</span>
                <span style={{ cursor: 'pointer' }}>×</span>
              </div>
            )}
          </div>

          {/* Section: Nhóm hàng */}
          <div style={{ marginBottom: '24px' }}>
            <div style={{ fontWeight: 'bold', marginBottom: '8px' }}>Nhóm hàng</div>
            <Select
              style={{ width: '100%' }}
              placeholder="Chọn nhóm hàng"
              allowClear
              onChange={(value) => handleFilterChange('category_id', value)}
              options={[
                { label: 'Thực phẩm', value: 'food' },
                { label: 'Đồ uống', value: 'beverage' },
                { label: 'Đồ gia dụng', value: 'household' },
              ]}
            />
          </div>

          {/* Section: Tồn kho */}
          <div style={{ marginBottom: '24px' }}>
            <div style={{ fontWeight: 'bold', marginBottom: '8px' }}>Tồn kho</div>
            <Select
              style={{ width: '100%' }}
              placeholder="Tất cả"
              allowClear
              onChange={(value) => handleFilterChange('stock_status', value)}
              options={[
                { label: 'Tất cả', value: 'all' },
                { label: 'Có tồn', value: 'in_stock' },
                { label: 'Hết tồn', value: 'out_of_stock' },
              ]}
            />
          </div>

          {/* Section: Giá bán */}
          <div>
            <div style={{ fontWeight: 'bold', marginBottom: '8px' }}>Giá bán</div>
            <Space direction="vertical" style={{ width: '100%' }}>
              <Select
                style={{ width: '100%' }}
                placeholder="Chọn điều kiện"
                allowClear
                onChange={(value) => handleFilterChange('price_condition', value)}
                options={[
                  { label: 'Nhỏ hơn', value: 'lt' },
                  { label: 'Nhỏ hơn hoặc bằng', value: 'lte' },
                  { label: 'Bằng', value: 'eq' },
                  { label: 'Lớn hơn', value: 'gt' },
                ]}
              />
              <Select
                style={{ width: '100%' }}
                placeholder="Chọn giá so sánh"
                allowClear
                onChange={(value) => handleFilterChange('price_compare', value)}
                options={[
                  { label: 'Giá vốn', value: 'cost' },
                  { label: 'Giá nhập cuối', value: 'purchase' },
                ]}
              />
            </Space>
          </div>
        </div>

        {/* Main Content */}
        <div style={{ flex: 1, padding: '16px', overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
          <Table
            rowSelection={rowSelection}
            columns={mergedColumns}
            dataSource={dataSource}
            loading={productLoading}
            pagination={paginationConfig}
            scroll={{ x: 1000, y: 'calc(100vh - 200px)' }}
            bordered
            size="small"
          />
        </div>
      </div>

      {/* Create Price List Modal */}
      <CreatePriceListModal
        open={modalOpen}
        onClose={handleModalClose}
        onSave={handleModalSave}
        loading={saving}
        basePriceLists={priceLists || []}
      />

      {/* Formula Modal */}
      <PriceFormulaModal
        open={formulaModalOpen}
        onClose={() => setFormulaModalOpen(false)}
        onSave={handleFormulaSave}
        product={formulaProduct}
        priceListId={selectedPriceListId}
        basePriceLists={priceLists || []}
      />
    </div>
  );
};

export default PriceListPage;

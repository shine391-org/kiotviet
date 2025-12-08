import React, { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import {
  Button,
  Input,
  Space,
  Tooltip,
  Typography,
  Select,
  Table,
  InputNumber,
  App,
  Spin,
  Empty,
  Popconfirm,
} from 'antd';
import {
  ArrowLeftOutlined,
  SearchOutlined,
  PrinterOutlined,
  DeleteOutlined,
  PlusOutlined,
} from '@ant-design/icons';
import { fetchBranches } from '../../store/slices/branchSlice';
import { getProducts } from '../../api/productApi';
import transferApi from '../../api/transferApi';
import styles from './TransferCreatePage.module.css';
import dayjs from 'dayjs';

const { Title, Text } = Typography;

const TransferCreatePage = () => {
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const { message } = App.useApp();

  const { branches, loading: branchLoading } = useSelector((s) => s.branch);
  const currentUser = useSelector((s) => s.auth.user);

  const [toBranchId, setToBranchId] = useState(null);
  const [notes, setNotes] = useState('');
  const [items, setItems] = useState([]);
  const [searchText, setSearchText] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [searching, setSearching] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  // Get current branch from user or first branch
  const fromBranchId = currentUser?.branch_id || branches[0]?.id;
  const fromBranch = branches.find((b) => b.id === fromBranchId);

  useEffect(() => {
    dispatch(fetchBranches());
  }, [dispatch]);

  // Search products with debounce
  useEffect(() => {
    if (!searchText.trim()) {
      setSearchResults([]);
      return;
    }

    const timer = setTimeout(async () => {
      setSearching(true);
      try {
        const res = await getProducts({ search: searchText, limit: 10 });
        if (res.success && res.data) {
          // Filter out already added products
          const addedIds = items.map((i) => i.product_id);
          setSearchResults(res.data.filter((p) => !addedIds.includes(p.id)));
        }
      } catch (err) {
        console.error('Search error:', err);
      } finally {
        setSearching(false);
      }
    }, 300);

    return () => clearTimeout(timer);
  }, [searchText, items]);

  const handleAddProduct = useCallback((product) => {
    setItems((prev) => [
      ...prev,
      {
        key: `${product.id}-${Date.now()}`,
        product_id: product.id,
        product_code: product.code || product.sku || `SP${product.id}`,
        product_name: product.name,
        unit: product.unit || 'Cái',
        unit_price: product.selling_price || product.cost_price || 0,
        quantity_sent: 1,
      },
    ]);
    setSearchText('');
    setSearchResults([]);
  }, []);

  const handleQuantityChange = useCallback((key, value) => {
    setItems((prev) =>
      prev.map((item) =>
        item.key === key ? { ...item, quantity_sent: value || 0 } : item
      )
    );
  }, []);

  const handleRemoveItem = useCallback((key) => {
    setItems((prev) => prev.filter((item) => item.key !== key));
  }, []);

  const totalQuantity = items.reduce((sum, i) => sum + (i.quantity_sent || 0), 0);
  const totalValue = items.reduce(
    (sum, i) => sum + (i.quantity_sent || 0) * (i.unit_price || 0),
    0
  );

  const handleSaveDraft = async () => {
    if (!toBranchId) {
      message.error('Vui lòng chọn chi nhánh nhận');
      return;
    }
    if (items.length === 0) {
      message.error('Vui lòng thêm ít nhất một sản phẩm');
      return;
    }

    setSubmitting(true);
    try {
      const payload = {
        from_branch_id: fromBranchId,
        to_branch_id: toBranchId,
        notes,
        items: items.map((i) => ({
          product_id: i.product_id,
          product_code: i.product_code,
          product_name: i.product_name,
          unit: i.unit,
          quantity_sent: i.quantity_sent,
          unit_price: i.unit_price,
        })),
      };

      const res = await transferApi.createTransfer(payload);
      if (res.success) {
        message.success('Đã lưu phiếu nháp');
        navigate('/inventory/transfer');
      } else {
        message.error(res.message || 'Lỗi tạo phiếu');
      }
    } catch (err) {
      message.error(err.response?.data?.message || 'Lỗi tạo phiếu');
    } finally {
      setSubmitting(false);
    }
  };

  const handleSubmit = async () => {
    if (!toBranchId) {
      message.error('Vui lòng chọn chi nhánh nhận');
      return;
    }
    if (items.length === 0) {
      message.error('Vui lòng thêm ít nhất một sản phẩm');
      return;
    }

    setSubmitting(true);
    try {
      // Create then submit
      const payload = {
        from_branch_id: fromBranchId,
        to_branch_id: toBranchId,
        notes,
        items: items.map((i) => ({
          product_id: i.product_id,
          product_code: i.product_code,
          product_name: i.product_name,
          unit: i.unit,
          quantity_sent: i.quantity_sent,
          unit_price: i.unit_price,
        })),
      };

      const createRes = await transferApi.createTransfer(payload);
      if (!createRes.success) {
        message.error(createRes.message || 'Lỗi tạo phiếu');
        return;
      }

      // Submit the created transfer
      const transferId = createRes.data?.id;
      if (transferId) {
        await transferApi.submitTransfer(transferId);
        message.success('Đã tạo và gửi phiếu chuyển hàng');
      } else {
        message.success('Đã tạo phiếu chuyển hàng');
      }
      navigate('/inventory/transfer');
    } catch (err) {
      message.error(err.response?.data?.message || 'Lỗi tạo phiếu');
    } finally {
      setSubmitting(false);
    }
  };

  const columns = [
    {
      title: 'STT',
      width: 50,
      render: (_, __, idx) => idx + 1,
    },
    {
      title: 'Mã hàng',
      dataIndex: 'product_code',
      width: 120,
    },
    {
      title: 'Tên hàng',
      dataIndex: 'product_name',
      ellipsis: true,
    },
    {
      title: 'ĐVT',
      dataIndex: 'unit',
      width: 80,
    },
    {
      title: 'SL chuyển',
      dataIndex: 'quantity_sent',
      width: 100,
      render: (val, record) => (
        <InputNumber
          min={1}
          value={val}
          onChange={(v) => handleQuantityChange(record.key, v)}
          style={{ width: '100%' }}
        />
      ),
    },
    {
      title: 'Đơn giá',
      dataIndex: 'unit_price',
      width: 120,
      render: (val) => val?.toLocaleString('vi-VN') || 0,
    },
    {
      title: 'Thành tiền',
      width: 120,
      render: (_, record) =>
        ((record.quantity_sent || 0) * (record.unit_price || 0)).toLocaleString('vi-VN'),
    },
    {
      title: '',
      width: 50,
      render: (_, record) => (
        <Popconfirm
          title="Xóa sản phẩm này?"
          onConfirm={() => handleRemoveItem(record.key)}
          okText="Xóa"
          cancelText="Hủy"
        >
          <Button type="text" danger icon={<DeleteOutlined />} size="small" />
        </Popconfirm>
      ),
    },
  ];

  const goBack = () => navigate('/inventory/transfer');

  return (
    <div className={styles.page}>
      <div className={styles.topBar}>
        <Space size={12} align="center">
          <Button type="text" icon={<ArrowLeftOutlined />} onClick={goBack} />
          <Title level={5} className={styles.title}>
            Tạo phiếu chuyển hàng
          </Title>
        </Space>

        <div style={{ position: 'relative', maxWidth: 520, flex: 1 }}>
          <Input
            allowClear
            prefix={<SearchOutlined />}
            placeholder="Tìm hàng hóa theo mã hoặc tên (F3)"
            className={styles.search}
            value={searchText}
            onChange={(e) => setSearchText(e.target.value)}
          />
          {(searching || searchResults.length > 0) && searchText && (
            <div
              style={{
                position: 'absolute',
                top: '100%',
                left: 0,
                right: 0,
                background: '#fff',
                border: '1px solid #d9d9d9',
                borderRadius: 6,
                boxShadow: '0 2px 8px rgba(0,0,0,0.15)',
                zIndex: 1000,
                maxHeight: 300,
                overflow: 'auto',
              }}
            >
              {searching ? (
                <div style={{ padding: 16, textAlign: 'center' }}>
                  <Spin size="small" />
                </div>
              ) : searchResults.length > 0 ? (
                searchResults.map((p) => (
                  <div
                    key={p.id}
                    style={{
                      padding: '8px 12px',
                      cursor: 'pointer',
                      borderBottom: '1px solid #f0f0f0',
                    }}
                    onClick={() => handleAddProduct(p)}
                    onMouseEnter={(e) => (e.currentTarget.style.background = '#f5f5f5')}
                    onMouseLeave={(e) => (e.currentTarget.style.background = '#fff')}
                  >
                    <div style={{ fontWeight: 500 }}>{p.name}</div>
                    <div style={{ fontSize: 12, color: '#666' }}>
                      {p.code || p.sku} - {(p.selling_price || 0).toLocaleString('vi-VN')}đ
                    </div>
                  </div>
                ))
              ) : (
                <div style={{ padding: 16, textAlign: 'center', color: '#999' }}>
                  Không tìm thấy sản phẩm
                </div>
              )}
            </div>
          )}
        </div>

        <Space size={8}>
          <Tooltip title="In phiếu">
            <Button icon={<PrinterOutlined />} disabled />
          </Tooltip>
        </Space>
      </div>

      <div className={styles.layout}>
        <div className={styles.tableCard}>
          <div className={styles.tableHeader}>
            <Text strong>Danh sách sản phẩm ({items.length})</Text>
          </div>
          {items.length === 0 ? (
            <div className={styles.tableEmpty}>
              <Empty
                description="Chưa có sản phẩm nào"
                image={Empty.PRESENTED_IMAGE_SIMPLE}
              >
                <Text type="secondary">Tìm kiếm sản phẩm ở ô trên để thêm vào phiếu</Text>
              </Empty>
            </div>
          ) : (
            <Table
              dataSource={items}
              columns={columns}
              rowKey="key"
              pagination={false}
              size="small"
              scroll={{ y: 400 }}
              summary={() => (
                <Table.Summary fixed>
                  <Table.Summary.Row>
                    <Table.Summary.Cell index={0} colSpan={4}>
                      <strong>Tổng cộng</strong>
                    </Table.Summary.Cell>
                    <Table.Summary.Cell index={1}>
                      <strong>{totalQuantity}</strong>
                    </Table.Summary.Cell>
                    <Table.Summary.Cell index={2} />
                    <Table.Summary.Cell index={3}>
                      <strong>{totalValue.toLocaleString('vi-VN')}</strong>
                    </Table.Summary.Cell>
                    <Table.Summary.Cell index={4} />
                  </Table.Summary.Row>
                </Table.Summary>
              )}
            />
          )}
        </div>

        <div className={styles.sidebar}>
          <div className={styles.sideSection}>
            <div className={styles.sideRow}>
              <Text strong>{currentUser?.name || 'Người tạo'}</Text>
              <Input value={dayjs().format('DD/MM/YYYY HH:mm')} disabled />
            </div>
            <div className={styles.sideRow}>
              <Text>Mã chuyển hàng</Text>
              <Input placeholder="Mã phiếu tự động" disabled />
            </div>
            <div className={styles.sideRow}>
              <Text>Trạng thái</Text>
              <Text>Phiếu tạm</Text>
            </div>
            <div className={styles.sideRow}>
              <Text>Từ chi nhánh</Text>
              <Input value={fromBranch?.name || 'Chi nhánh hiện tại'} disabled />
            </div>
            <div className={styles.sideRow}>
              <Text>Chuyển tới</Text>
              <Select
                placeholder="Chọn chi nhánh"
                style={{ width: '100%' }}
                loading={branchLoading}
                value={toBranchId}
                onChange={setToBranchId}
                options={branches
                  .filter((b) => b.id !== fromBranchId)
                  .map((b) => ({ value: b.id, label: b.name }))}
              />
            </div>
            <div className={styles.sideRow}>
              <Text>Tổng số lượng</Text>
              <Text strong>{totalQuantity}</Text>
            </div>
            <div className={styles.sideRow}>
              <Text>Tổng giá trị</Text>
              <Text strong>{totalValue.toLocaleString('vi-VN')}đ</Text>
            </div>
            <div className={styles.sideRow}>
              <Text>Ghi chú</Text>
              <Input.TextArea
                autoSize={{ minRows: 3 }}
                placeholder="Ghi chú"
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
              />
            </div>
          </div>

          <div className={styles.actionRow}>
            <Button
              block
              size="large"
              className={styles.draftBtn}
              onClick={handleSaveDraft}
              loading={submitting}
            >
              Lưu tạm
            </Button>
            <Button
              block
              size="large"
              type="primary"
              className={styles.submitBtn}
              onClick={handleSubmit}
              loading={submitting}
            >
              Hoàn thành
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default TransferCreatePage;

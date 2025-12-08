import React, { useCallback, useMemo, useRef, useState } from 'react';
import {
  App,
  Button,
  Card,
  Flex,
  Input,
  InputNumber,
  Select,
  Space,
  Spin,
  Tabs,
  Tag,
  Typography,
  Upload,
  Table,
} from 'antd';
import {
  ArrowLeftOutlined,
  SearchOutlined,
  UploadOutlined,
  PlusOutlined,
  AppstoreOutlined,
  EyeOutlined,
  EyeInvisibleOutlined,
  LoadingOutlined,
} from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import stockAuditApi from '../../api/stockAuditApi';
import { getProducts } from '../../api/productApi';
import styles from './StockAuditCreatePage.module.css';

const calcRow = (row) => {
  const diff = (Number(row.actual ?? 0) || 0) - (Number(row.stock ?? 0) || 0);
  const diffValue = diff * (Number(row.price ?? 0) || 0);
  let status = 'unchecked';
  if (row.actual !== null && row.actual !== undefined) {
    if (diff === 0) status = 'matched';
    else status = 'mismatch';
  }
  return { ...row, diff, diffValue, status };
};

const tabs = [
  { key: 'all', label: 'Tất cả' },
  { key: 'matched', label: 'Khớp' },
  { key: 'mismatch', label: 'Lệch' },
  { key: 'unchecked', label: 'Chưa kiểm' },
];

const StockAuditCreatePage = () => {
  const navigate = useNavigate();
  const { message } = App.useApp();

  const [items, setItems] = useState([]);
  const [filter, setFilter] = useState('all');
  const [notes, setNotes] = useState('');
  const [showHiddenCols, setShowHiddenCols] = useState(true);
  const [auditId, setAuditId] = useState(null);
  const [auditCode, setAuditCode] = useState(null);
  const [saving, setSaving] = useState(false);
  const [searchLoading, setSearchLoading] = useState(false);
  const [productOptions, setProductOptions] = useState([]);
  const searchTimerRef = useRef(null);

  const filteredItems = useMemo(() => {
    if (filter === 'matched') return items.filter((i) => i.status === 'matched');
    if (filter === 'mismatch') return items.filter((i) => i.status === 'mismatch');
    if (filter === 'unchecked') return items.filter((i) => i.status === 'unchecked');
    return items;
  }, [items, filter]);

  const counts = useMemo(() => ({
    all: items.length,
    matched: items.filter((i) => i.status === 'matched').length,
    mismatch: items.filter((i) => i.status === 'mismatch').length,
    unchecked: items.filter((i) => i.status === 'unchecked').length,
  }), [items]);

  const summary = useMemo(() => ({
    totalActual: items.reduce((sum, r) => sum + Number(r.actual ?? 0), 0),
    totalDiff: items.reduce((sum, r) => sum + Number(r.diff ?? 0), 0),
    totalDiffValue: items.reduce((sum, r) => sum + Number(r.diffValue ?? 0), 0),
  }), [items]);

  const handleActualChange = (code, value) => {
    setItems((prev) => prev.map((r) => (r.code === code ? calcRow({ ...r, actual: value }) : r)));
  };

  const searchProducts = useCallback((keyword) => {
    if (searchTimerRef.current) {
      clearTimeout(searchTimerRef.current);
    }
    if (!keyword || keyword.length < 2) {
      setProductOptions([]);
      return;
    }
    searchTimerRef.current = setTimeout(async () => {
      setSearchLoading(true);
      try {
        const res = await getProducts({ search: keyword, limit: 20 });
        if (res?.success && res.data) {
          const options = res.data.map((p) => ({
            value: p.code,
            label: `${p.code} - ${p.name}`,
            product: p,
          }));
          setProductOptions(options);
        }
      } catch (err) {
        console.error('Product search error:', err);
      } finally {
        setSearchLoading(false);
      }
    }, 400);
  }, []);

  const handleProductSelect = (value, option) => {
    const p = option.product;
    if (!p) return;
    const exists = items.find((i) => i.code === p.code);
    if (exists) {
      message.warning('Sản phẩm đã tồn tại trong danh sách');
      return;
    }
    const newItem = calcRow({
      code: p.code,
      name: p.name,
      uom: p.unit || 'Cái',
      stock: p.quantity ?? p.stock_quantity ?? 0,
      actual: null,
      price: p.cost_price || p.import_price || 0,
    });
    setItems((prev) => [...prev, newItem]);
    setProductOptions([]);
  };

  const handleUpload = () => message.info('Upload sẽ kết nối backend sau');

  const buildPayload = () => ({
    notes,
    items: items.map((i) => ({
      product_code: i.code,
      product_name: i.name,
      current_qty: i.stock,
      counted_qty: i.actual ?? 0,
      unit_cost: i.price,
    })),
  });

  const handleSaveDraft = async () => {
    if (items.length === 0) {
      message.warning('Vui lòng thêm sản phẩm vào phiếu kiểm kho');
      return;
    }
    setSaving(true);
    try {
      const payload = buildPayload();
      let res;
      if (auditId) {
        res = await stockAuditApi.updateAudit(auditId, payload);
      } else {
        res = await stockAuditApi.createAudit(payload);
      }
      if (res?.success) {
        setAuditId(res.data?.id);
        setAuditCode(res.data?.code);
        message.success('Đã lưu tạm phiếu kiểm kho');
      } else {
        message.error(res?.message || 'Lưu thất bại');
      }
    } catch (err) {
      message.error(err?.response?.data?.message || 'Có lỗi xảy ra');
    } finally {
      setSaving(false);
    }
  };

  const handleComplete = async () => {
    if (items.length === 0) {
      message.warning('Vui lòng thêm sản phẩm vào phiếu kiểm kho');
      return;
    }
    setSaving(true);
    try {
      let id = auditId;
      const payload = buildPayload();
      if (!id) {
        const createRes = await stockAuditApi.createAudit(payload);
        if (!createRes?.success) {
          message.error(createRes?.message || 'Tạo phiếu thất bại');
          return;
        }
        id = createRes.data?.id;
        setAuditId(id);
        setAuditCode(createRes.data?.code);
      } else {
        await stockAuditApi.updateAudit(id, payload);
      }
      const res = await stockAuditApi.completeAudit(id);
      if (res?.success) {
        message.success('Đã hoàn thành phiếu kiểm kho');
        navigate('/inventory/audit');
      } else {
        message.error(res?.message || 'Hoàn thành thất bại');
      }
    } catch (err) {
      message.error(err?.response?.data?.message || 'Có lỗi xảy ra');
    } finally {
      setSaving(false);
    }
  };

  const columns = [
    { title: 'STT', dataIndex: 'index', width: 70, render: (_v, _r, idx) => idx + 1 },
    { title: 'Mã hàng', dataIndex: 'code', width: 150 },
    { title: 'Tên hàng', dataIndex: 'name', width: 200 },
    { title: 'ĐVT', dataIndex: 'uom', width: 90 },
    { title: 'Tồn kho', dataIndex: 'stock', align: 'right', width: 110 },
    {
      title: 'Thực tế',
      dataIndex: 'actual',
      align: 'right',
      width: 110,
      render: (v, record) => (
        <InputNumber
          min={0}
          value={v}
          size="small"
          onChange={(value) => handleActualChange(record.code, value)}
          style={{ width: '100%' }}
        />
      ),
    },
    { title: 'SL lệch', dataIndex: 'diff', align: 'right', width: 110 },
    { title: 'Giá trị lệch', dataIndex: 'diffValue', align: 'right', width: 130 },
  ].filter((col) => {
    if (!showHiddenCols && ['diff', 'diffValue'].includes(col.dataIndex)) return false;
    return true;
  });

  const tabItems = tabs.map((t) => ({
    key: t.key,
    label: `${t.label} (${counts[t.key]})`,
  }));

  return (
    <Spin spinning={saving} tip="Đang xử lý...">
    <div className={styles.page}>
      <div className={styles.header}> 
        <Space size={10} wrap>
          <Button icon={<ArrowLeftOutlined />} type="text" onClick={() => navigate(-1)}>
            Kiểm kho
          </Button>
          <Select
            showSearch
            placeholder="Tìm hàng hóa theo mã hoặc tên (F3)"
            className={styles.search}
            style={{ width: 300 }}
            filterOption={false}
            onSearch={searchProducts}
            onSelect={handleProductSelect}
            options={productOptions}
            notFoundContent={searchLoading ? <Spin size="small" /> : null}
            suffixIcon={searchLoading ? <LoadingOutlined /> : <SearchOutlined />}
          />
          <Space size={6}>
            <Button icon={<AppstoreOutlined />} />
            <Button icon={showHiddenCols ? <EyeOutlined /> : <EyeInvisibleOutlined />} onClick={() => setShowHiddenCols((v) => !v)} />
          </Space>
        </Space>
        <Space size={8} wrap>
          <Button icon={<UploadOutlined />} onClick={handleUpload}>
            Chọn file dữ liệu
          </Button>
        </Space>
      </div>

      <div className={styles.body}>
        <div className={styles.main}>
          <Tabs
            items={tabItems}
            activeKey={filter}
            onChange={(key) => setFilter(key)}
            className={styles.tabs}
          />

          <div className={styles.tableBox}>
            {filteredItems.length === 0 ? (
              <div className={styles.emptyState}>
                <Typography.Text strong>Thêm sản phẩm vào phiếu kiểm kho</Typography.Text>
                <Typography.Text type="secondary">
                  Tìm kiếm và chọn sản phẩm từ thanh tìm kiếm bên trên, hoặc import từ file Excel
                </Typography.Text>
                <Space size={10}>
                  <Upload.Dragger
                    beforeUpload={() => false}
                    showUploadList={false}
                    className={styles.uploader}
                    onChange={handleUpload}
                  >
                    <p className="ant-upload-drag-icon">
                      <UploadOutlined />
                    </p>
                    <p className="ant-upload-text">Chọn file dữ liệu</p>
                  </Upload.Dragger>
                </Space>
              </div>
            ) : (
              <Table
                rowKey="code"
                dataSource={filteredItems}
                columns={columns}
                size="small"
                pagination={false}
                scroll={{ x: 900, y: 440 }}
              />
            )}
          </div>
        </div>

        <div className={styles.sidebar}>
          <Card size="small" title={
            <Space>
              <Typography.Text strong>Trung</Typography.Text>
              <Typography.Text type="secondary">28/11/2025 12:16</Typography.Text>
            </Space>
          }>
            <Space direction="vertical" size={8} style={{ width: '100%' }}>
              <div>
                <Typography.Text type="secondary">Mã kiểm kho</Typography.Text>
                <Input placeholder="Mã phiếu tự động" disabled value={auditCode || ''} />
              </div>
              <div>
                <Typography.Text type="secondary">Trạng thái</Typography.Text>
                <div>
                  <Tag color="gold">Phiếu tạm</Tag>
                </div>
              </div>
              <div>
                <Typography.Text type="secondary">Tổng SL thực tế</Typography.Text>
                <Typography.Title level={4} style={{ margin: 0 }}>{summary.totalActual}</Typography.Title>
              </div>
              <Input.TextArea
                rows={3}
                placeholder="Ghi chú"
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
              />
            </Space>
          </Card>

          <Card size="small" title="Kiểm gần đây" className={styles.recentCard}>
            <Typography.Text type="secondary">Chưa có dữ liệu</Typography.Text>
          </Card>

          <Flex gap={12} className={styles.actionBar}>
            <Button block onClick={handleSaveDraft} icon={<UploadOutlined />} loading={saving}>Lưu tạm</Button>
            <Button block type="primary" onClick={handleComplete} icon={<PlusOutlined />} loading={saving}>Hoàn thành</Button>
          </Flex>
        </div>
      </div>
    </div>
    </Spin>
  );
};

export default StockAuditCreatePage;

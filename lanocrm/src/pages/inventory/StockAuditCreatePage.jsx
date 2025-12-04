import React, { useMemo, useState } from 'react';
import {
  App,
  Button,
  Card,
  Col,
  Divider,
  Flex,
  Input,
  InputNumber,
  Row,
  Space,
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
} from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import styles from './StockAuditCreatePage.module.css';

const SAMPLE_ITEMS = [
  { code: 'VDN099-Xanh', name: 'Ví card holder', uom: 'Cái', stock: 10, actual: 9, price: 360000 },
  { code: 'SOMI-TRANG-M', name: 'Sơ mi trắng', uom: 'Chiếc', stock: 15, actual: 15, price: 420000 },
  { code: 'BALO-NEW', name: 'Balo thời trang', uom: 'Cái', stock: 7, actual: 5, price: 680000 },
];

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

  const handleImportSample = () => {
    setItems(SAMPLE_ITEMS.map(calcRow));
    message.success('Đã nạp dữ liệu mẫu');
  };

  const handleUpload = () => message.info('Upload sẽ kết nối backend sau');
  const handleSaveDraft = () => message.success('Đã lưu nháp (mock)');
  const handleComplete = () => message.success('Đã hoàn thành (mock)');

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
    <div className={styles.page}>
      <div className={styles.header}> 
        <Space size={10} wrap>
          <Button icon={<ArrowLeftOutlined />} type="text" onClick={() => navigate(-1)}>
            Kiểm kho
          </Button>
          <Input
            allowClear
            prefix={<SearchOutlined />}
            placeholder="Tìm hàng hóa theo mã hoặc tên (F3)"
            className={styles.search}
          />
          <Space size={6}>
            <Button icon={<AppstoreOutlined />} />
            <Button icon={showHiddenCols ? <EyeOutlined /> : <EyeInvisibleOutlined />} onClick={() => setShowHiddenCols((v) => !v)} />
            <Button icon={<PlusOutlined />} type="dashed" onClick={() => message.info('Thêm dòng thủ công sắp có')} />
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
                <Typography.Text strong>Thêm sản phẩm từ file excel</Typography.Text>
                <Typography.Link onClick={() => message.info('Tải file mẫu đang chuẩn bị')}>
                  Tải về file mẫu: Excel file
                </Typography.Link>
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
                  <Button type="link" onClick={handleImportSample}>
                    Dùng dữ liệu mẫu
                  </Button>
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
                <Input placeholder="Mã phiếu tự động" disabled />
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
            <Button block onClick={handleSaveDraft} icon={<UploadOutlined />}>Lưu tạm</Button>
            <Button block type="primary" onClick={handleComplete} icon={<PlusOutlined />}>Hoàn thành</Button>
          </Flex>
        </div>
      </div>
    </div>
  );
};

export default StockAuditCreatePage;

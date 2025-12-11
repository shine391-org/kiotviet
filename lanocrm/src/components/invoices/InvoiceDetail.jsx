import React from 'react';
import {
  Tabs,
  Tag,
  Space,
  Table,
  Row,
  Col,
  Typography,
  Select,
  Input,
  DatePicker,
  Button,
  Flex,
} from 'antd';
import {
  StarOutlined,
  StarFilled,
  InfoCircleOutlined,
  CopyOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import { formatDateTime, INVOICE_STATUSES, DELIVERY_STATUSES } from '../../constants/invoices';
import styles from './InvoiceDetail.module.css';

const { Text, Link } = Typography;
const { TextArea } = Input;

const statusMap = INVOICE_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});
const deliveryStatusMap = DELIVERY_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

const currency = (v) => (Number(v || 0)).toLocaleString('vi-VN');

// Product items table
const productColumns = [
  { title: 'Mã hàng', dataIndex: 'product_code', width: 100 },
  { title: 'Tên hàng', dataIndex: 'product_name', width: 280 },
  { title: 'Số lượng', dataIndex: 'quantity', width: 80, align: 'center' },
  { title: 'Đơn giá', dataIndex: 'unit_price', width: 120, align: 'right', render: currency },
  { title: 'Giảm giá', dataIndex: 'discount', width: 100, align: 'right', render: currency },
  { title: 'Giá bán', dataIndex: 'final_price', width: 120, align: 'right', render: currency },
  {
    title: 'Thành tiền',
    key: 'total',
    width: 120,
    align: 'right',
    render: (_, r) => currency((r.final_price || r.unit_price || 0) * (r.quantity || 0)),
  },
];

// Payment history table
const paymentColumns = [
  { title: 'Mã phiếu', dataIndex: 'code', width: 120, render: (v) => <Link>{v}</Link> },
  { title: 'Thời gian', dataIndex: 'created_at', width: 150, render: formatDateTime },
  { title: 'Người tạo', dataIndex: 'creator_name', width: 120 },
  { title: 'Giá trị phiếu', dataIndex: 'amount', width: 120, align: 'right', render: currency },
  { title: 'Phương thức', dataIndex: 'payment_method', width: 120 },
  {
    title: 'Trạng thái',
    dataIndex: 'status',
    width: 120,
    render: (v) => <Tag color={v === 'Đã thanh toán' ? 'green' : 'default'}>{v || 'Đã thanh toán'}</Tag>,
  },
  { title: 'Tiền thu/chi', dataIndex: 'cash_amount', width: 120, align: 'right', render: currency },
];

// Delivery history table
const deliveryHistoryColumns = [
  { title: 'Thời gian', dataIndex: 'time', width: 150, render: formatDateTime },
  { title: 'Nơi', dataIndex: 'location', width: 250, ellipsis: true },
  { title: 'Thông tin cập nhật', dataIndex: 'message', ellipsis: true },
];

const InvoiceDetail = ({ invoice }) => {
  if (!invoice) return null;

  const invoiceStatus = statusMap[invoice.invoice_status] || {};
  const deliveryStatus = deliveryStatusMap[invoice.delivery_status] || {};
  const items = invoice.items || [];
  const payments = invoice.payments || [];
  const deliveryHistory = invoice.delivery_history || [];

  // Calculate totals
  const itemTotal = items.reduce((sum, item) => sum + (item.final_price || item.unit_price || 0) * (item.quantity || 0), 0);

  // Tab 1: Thông tin
  const infoTab = (
    <div className={styles.infoTab}>
      {/* Header info */}
      <div className={styles.infoHeader}>
        <Row gutter={[16, 8]}>
          <Col span={6}>
            <Text type="secondary">Người tạo:</Text>
            <div><Link>{invoice.creator_name || 'Admin'}</Link></div>
          </Col>
          <Col span={6}>
            <Text type="secondary">Người bán:</Text>
            <div>
              <Select
                size="small"
                defaultValue={invoice.seller_name || invoice.creator_name}
                style={{ width: '100%' }}
                options={[{ value: invoice.seller_name || invoice.creator_name, label: invoice.seller_name || invoice.creator_name }]}
              />
            </div>
          </Col>
          <Col span={6}>
            <Text type="secondary">Ngày bán:</Text>
            <div>
              <DatePicker
                size="small"
                showTime
                value={invoice.issue_date ? dayjs(invoice.issue_date) : dayjs()}
                format="DD/MM/YYYY HH:mm"
                style={{ width: '100%' }}
              />
            </div>
          </Col>
        </Row>
        <Row gutter={[16, 8]} style={{ marginTop: 8 }}>
          <Col span={6}>
            <Text type="secondary">Kênh bán:</Text>
            <div>
              <Select
                size="small"
                defaultValue={invoice.sales_channel || 'Bán trực tiếp'}
                style={{ width: '100%' }}
                options={[
                  { value: 'Bán trực tiếp', label: 'Bán trực tiếp' },
                  { value: 'Online', label: 'Online' },
                ]}
              />
            </div>
          </Col>
          <Col span={6}>
            <Text type="secondary">Bảng giá:</Text>
            <div>
              <Select
                size="small"
                defaultValue="Sales"
                style={{ width: '100%' }}
                options={[{ value: 'Sales', label: 'Sales' }]}
              />
            </div>
          </Col>
        </Row>
      </div>

      {/* Shipping info */}
      {invoice.address && (
        <div className={styles.shippingInfo}>
          <Text type="secondary">
            <span style={{ marginRight: 8 }}>🚚</span>
            Giao qua {invoice.shipping_partner || 'Đối tác'} đến {' '}
            <Text strong>{invoice.address}</Text>
          </Text>
        </div>
      )}

      {/* Products table */}
      <Table
        dataSource={items}
        columns={productColumns}
        size="small"
        pagination={false}
        rowKey={(r) => r.id || r.product_code}
        style={{ marginTop: 12 }}
      />

      {/* Bottom section - Notes and Summary */}
      <div className={styles.bottomSection}>
        <Row gutter={24}>
          <Col span={12}>
            <TextArea
              placeholder="Ghi chú..."
              defaultValue={invoice.notes || ''}
              rows={4}
              style={{ width: '100%' }}
            />
          </Col>
          <Col span={12}>
            <div className={styles.summary}>
              <div className={styles.summaryRow}>
                <span>Tổng tiền hàng ({items.length})</span>
                <span className={styles.amount}>{currency(itemTotal)}</span>
              </div>
              <div className={styles.summaryRow}>
                <span>Giảm giá hóa đơn</span>
                <span className={styles.amount}>{currency(invoice.discount_total || 0)}</span>
              </div>
              <div className={styles.summaryRow}>
                <span>Khách cần trả</span>
                <span className={styles.amountBold}>{currency(invoice.customer_payable || invoice.total)}</span>
              </div>
              <div className={styles.summaryRow}>
                <span>Khách đã trả</span>
                <span className={styles.amountPaid}>{currency(invoice.customer_paid)}</span>
              </div>
            </div>
          </Col>
        </Row>
      </div>

      {/* Action buttons */}
      <Flex justify="space-between" className={styles.actions}>
        <Space>
          <Button icon={<CopyOutlined />}>Sao chép</Button>
          <Button>📄 Xuất file</Button>
        </Space>
        <Space>
          <Button>📁 Lưu</Button>
          <Button>↩️ Trả hàng</Button>
          <Button type="primary">🖨️ In</Button>
        </Space>
      </Flex>
    </div>
  );

  // Tab 2: Lịch sử giao hàng
  const deliveryTab = (
    <Table
      dataSource={deliveryHistory}
      columns={deliveryHistoryColumns}
      size="small"
      pagination={false}
      rowKey={(r) => r.id || r.time}
      locale={{ emptyText: 'Chưa có lịch sử giao hàng' }}
    />
  );

  // Tab 3: Lịch sử thanh toán
  const paymentTab = (
    <Table
      dataSource={payments}
      columns={paymentColumns}
      size="small"
      pagination={false}
      rowKey={(r) => r.id || r.code}
      locale={{ emptyText: 'Chưa có lịch sử thanh toán' }}
    />
  );

  // Tab 4: Chi tiết giao hàng
  const deliveryDetailTab = (
    <Table
      dataSource={deliveryHistory}
      columns={[
        { title: 'Thời gian', dataIndex: 'time', width: 150, render: formatDateTime },
        { title: 'Nơi', dataIndex: 'location', width: 250, ellipsis: true },
        { title: 'Thông tin cập nhật', dataIndex: 'message' },
      ]}
      size="small"
      pagination={false}
      rowKey={(r) => r.id || r.time}
      locale={{ emptyText: 'Chưa có chi tiết giao hàng' }}
    />
  );

  return (
    <div className={styles.detailPanel}>
      {/* Header row */}
      <div className={styles.header}>
        <div className={styles.headerLeft}>
          <Space>
            <StarOutlined style={{ color: '#faad14' }} />
            <Link strong>{invoice.invoice_code || invoice.invoice_number}</Link>
            <Text copyable={{ text: invoice.shipment_code }}>
              {invoice.shipment_code || '—'}
            </Text>
            <Tag color={deliveryStatus.color}>{deliveryStatus.label || invoice.delivery_status || '—'}</Tag>
            <InfoCircleOutlined />
          </Space>
        </div>
        <div className={styles.headerRight}>
          <Space size="large">
            <span>{formatDateTime(invoice.issue_date)}</span>
            <span>{formatDateTime(invoice.issue_date)}</span>
            <span>{formatDateTime(invoice.delivery_time || invoice.updated_at)}</span>
          </Space>
        </div>
      </div>

      {/* Tabs */}
      <Tabs
        defaultActiveKey="info"
        size="small"
        items={[
          { key: 'info', label: 'Thông tin', children: infoTab },
          { key: 'delivery_history', label: 'Lịch sử giao hàng', children: deliveryTab },
          { key: 'payments', label: 'Lịch sử thanh toán', children: paymentTab },
          { key: 'delivery_detail', label: 'Chi tiết giao hàng', children: deliveryDetailTab },
        ]}
      />
    </div>
  );
};

export default InvoiceDetail;

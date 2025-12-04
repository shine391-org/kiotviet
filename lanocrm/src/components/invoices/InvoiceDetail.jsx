import React from 'react';
import { Card, Descriptions, Table, Tabs, Tag, Space } from 'antd';
import { formatDateTime, INVOICE_STATUSES, DELIVERY_STATUSES } from '../../constants/invoices';

const statusMap = INVOICE_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});
const deliveryStatusMap = DELIVERY_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

const paymentColumns = [
  { title: 'Mã phiếu', dataIndex: 'code', width: 120 },
  { title: 'Thời gian', dataIndex: 'time', width: 170, render: formatDateTime },
  { title: 'Người tạo', dataIndex: 'creator', width: 140 },
  { title: 'Giá trị phiếu', dataIndex: 'amount', width: 140, align: 'right', render: (v) => (Number(v || 0)).toLocaleString('vi-VN') },
  { title: 'Phương thức', dataIndex: 'method', width: 140 },
  { title: 'Trạng thái', dataIndex: 'status', width: 120 },
  { title: 'Tiền thu/chi', dataIndex: 'cash_flow', width: 140, align: 'right', render: (v) => (Number(v || 0)).toLocaleString('vi-VN') },
];

const InvoiceDetail = ({ invoice }) => {
  if (!invoice) {
    return (
      <Card size="small" style={{ marginTop: 12 }}>
        Chọn một hóa đơn để xem chi tiết.
      </Card>
    );
  }

  const invoiceStatus = statusMap[invoice.invoice_status] || {};
  const deliveryStatus = deliveryStatusMap[invoice.delivery_status] || {};

  const infoTab = (
    <Descriptions size="small" bordered column={3}>
      <Descriptions.Item label="Mã hóa đơn">{invoice.invoice_code}</Descriptions.Item>
      <Descriptions.Item label="Khách hàng">{invoice.customer_name || '—'}</Descriptions.Item>
      <Descriptions.Item label="Điện thoại">{invoice.phone || '—'}</Descriptions.Item>
      <Descriptions.Item label="Chi nhánh">{invoice.branch_name || '—'}</Descriptions.Item>
      <Descriptions.Item label="Người bán">{invoice.seller_name || '—'}</Descriptions.Item>
      <Descriptions.Item label="Người tạo">{invoice.creator_name || '—'}</Descriptions.Item>
      <Descriptions.Item label="Thời gian">{formatDateTime(invoice.issued_at)}</Descriptions.Item>
      <Descriptions.Item label="Trạng thái giao hàng">
        <Tag color={deliveryStatus.color}>{deliveryStatus.label || invoice.delivery_status || '—'}</Tag>
      </Descriptions.Item>
      <Descriptions.Item label="Trạng thái">
        <Tag color={invoiceStatus.color}>{invoiceStatus.label || invoice.invoice_status || '—'}</Tag>
      </Descriptions.Item>
      <Descriptions.Item label="Địa chỉ" span={3}>{invoice.address || '—'}</Descriptions.Item>
      <Descriptions.Item label="Ghi chú" span={3}>{invoice.note || '—'}</Descriptions.Item>
      <Descriptions.Item label="Khách cần trả">{(Number(invoice.customer_payable || 0)).toLocaleString('vi-VN')}</Descriptions.Item>
      <Descriptions.Item label="Khách đã trả">{(Number(invoice.customer_paid || 0)).toLocaleString('vi-VN')}</Descriptions.Item>
      <Descriptions.Item label="COD">{(Number(invoice.cod_amount || 0)).toLocaleString('vi-VN')}</Descriptions.Item>
    </Descriptions>
  );

  const paymentTab = (
    <Table
      dataSource={invoice.payments || []}
      columns={paymentColumns}
      size="small"
      pagination={false}
      rowKey={(r) => r.id || r.code}
    />
  );

  return (
    <Card
      size="small"
      title={(
        <Space>
          <span>Chi tiết hóa đơn</span>
          <Tag color={invoiceStatus.color}>{invoiceStatus.label || invoice.invoice_status}</Tag>
        </Space>
      )}
      style={{ marginTop: 12 }}
    >
      <Tabs
        defaultActiveKey="info"
        items={[
          { key: 'info', label: 'Thông tin', children: infoTab },
          { key: 'payments', label: 'Lịch sử thanh toán', children: paymentTab },
        ]}
      />
    </Card>
  );
};

export default InvoiceDetail;

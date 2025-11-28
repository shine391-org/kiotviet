import React from 'react';
import { Card, Descriptions, Table, Tag, Space } from 'antd';
import { formatDate, ORDER_STATUSES } from '../../constants/orders';

const statusMap = ORDER_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});
const columns = [
  { title: 'Mã hàng', dataIndex: 'product_id', width: 100 },
  { title: 'Số lượng', dataIndex: 'quantity', width: 100 },
  { title: 'Đơn giá', dataIndex: 'final_price', render: (v) => (Number(v || 0)).toLocaleString('vi-VN'), width: 120 },
  { title: 'Thành tiền', key: 'line_total', render: (_, r) => (Number(r.final_price || 0) * Number(r.quantity || 0)).toLocaleString('vi-VN'), width: 140 },
];

const OrderDetail = ({ order }) => {
  if (!order) return null;
  const statusMeta = statusMap[order.status] || {};
  return (
    <Card size="small" title={`Thông tin đơn hàng ${order.order_number || ''}`} style={{ marginTop: 12 }}>
      <Descriptions size="small" column={3} bordered>
        <Descriptions.Item label="Khách hàng">{order.customer_name || '—'}</Descriptions.Item>
        <Descriptions.Item label="Điện thoại">{order.shipping_phone || '—'}</Descriptions.Item>
        <Descriptions.Item label="Chi nhánh">{order.branch_name || '—'}</Descriptions.Item>
        <Descriptions.Item label="Thời gian">{formatDate(order.order_date)}</Descriptions.Item>
        <Descriptions.Item label="Địa chỉ" span={2}>{order.shipping_address || '—'}</Descriptions.Item>
        <Descriptions.Item label="Trạng thái">
          <Tag color={statusMeta.color}>{statusMeta.label || order.status}</Tag>
        </Descriptions.Item>
        <Descriptions.Item label="Khách cần trả">{(Number(order.total || 0)).toLocaleString('vi-VN')}</Descriptions.Item>
        <Descriptions.Item label="Khách đã trả">{(Number(order.paid_amount || 0)).toLocaleString('vi-VN')}</Descriptions.Item>
      </Descriptions>

      <Space direction="vertical" style={{ marginTop: 12, width: '100%' }}>
        <Table
          dataSource={order.items || []}
          columns={columns}
          size="small"
          pagination={false}
          rowKey={(r) => r.id || `${r.product_id}-${r.variant_id}`}
        />
      </Space>
    </Card>
  );
};

export default OrderDetail;

import React from 'react';
import { Card, Descriptions, Table, Tag, Space, Typography } from 'antd';
import { RETURN_STATUSES, formatReturnDate } from '../../constants/returns';

const statusMap = RETURN_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

const itemColumns = [
  { title: 'Mã hàng', dataIndex: 'sku', width: 120 },
  { title: 'Tên hàng', dataIndex: 'name', ellipsis: true },
  { title: 'Số lượng', dataIndex: 'quantity', width: 100 },
  { title: 'Giá trả hàng', dataIndex: 'return_price', align: 'right', render: (v) => (Number(v || 0)).toLocaleString('vi-VN'), width: 140 },
  { title: 'Giảm giá', dataIndex: 'discount', align: 'right', render: (v) => (Number(v || 0)).toLocaleString('vi-VN'), width: 120 },
  { title: 'Giá nhập lại', dataIndex: 'restock_price', align: 'right', render: (v) => (Number(v || 0)).toLocaleString('vi-VN'), width: 140 },
  {
    title: 'Thành tiền',
    key: 'line_total',
    align: 'right',
    render: (_, r) => (Number(r.return_price || 0) * Number(r.quantity || 0) - Number(r.discount || 0)).toLocaleString('vi-VN'),
    width: 140,
  },
];

const ReturnDetail = ({ data }) => {
  if (!data) return null;
  const statusMeta = statusMap[data.status] || {};

  const summary = [
    { label: 'Tổng tiền hàng trả', value: data.goods_amount },
    { label: 'Giảm giá phiếu trả', value: data.discount_amount },
    { label: 'Phí trả hàng', value: data.return_fee },
    { label: 'Cần trả khách', value: data.need_refund },
    { label: 'Đã trả khách', value: data.refunded_amount },
  ];

  return (
    <Card size="small" title={`Phiếu trả ${data.return_code || ''}`} style={{ marginTop: 12 }}>
      <Descriptions size="small" column={3} bordered>
        <Descriptions.Item label="Khách hàng" span={2}>{data.customer_name || '—'}</Descriptions.Item>
        <Descriptions.Item label="Trạng thái">
          <Tag color={statusMeta.color}>{statusMeta.label || data.status || '—'}</Tag>
        </Descriptions.Item>
        <Descriptions.Item label="Mã trả hàng">{data.return_code}</Descriptions.Item>
        <Descriptions.Item label="Mã hóa đơn">{data.invoice_code || '—'}</Descriptions.Item>
        <Descriptions.Item label="Mã vận đơn bán">{data.shipping_code || '—'}</Descriptions.Item>
        <Descriptions.Item label="Người bán">{data.seller_name || '—'}</Descriptions.Item>
        <Descriptions.Item label="Người nhận trả">{data.receiver_name || '—'}</Descriptions.Item>
        <Descriptions.Item label="Người tạo">{data.creator_name || '—'}</Descriptions.Item>
        <Descriptions.Item label="Chi nhánh">{data.branch_name || '—'}</Descriptions.Item>
        <Descriptions.Item label="Kênh bán">{data.channel || '—'}</Descriptions.Item>
        <Descriptions.Item label="Ngày trả">{formatReturnDate(data.return_time)}</Descriptions.Item>
        <Descriptions.Item label="Thời gian tạo">{formatReturnDate(data.created_at)}</Descriptions.Item>
      </Descriptions>

      <Space direction="vertical" style={{ marginTop: 12, width: '100%' }}>
        <Table
          dataSource={data.items || []}
          columns={itemColumns}
          size="small"
          pagination={false}
          rowKey={(r) => r.id || r.sku}
        />
      </Space>

      <Space direction="vertical" style={{ marginTop: 12 }}>
        {summary.map((s) => (
          <Typography.Text key={s.label} strong>
            {s.label}: {(Number(s.value || 0)).toLocaleString('vi-VN')} đ
          </Typography.Text>
        ))}
        {data.notes && <Typography.Paragraph>{data.notes}</Typography.Paragraph>}
      </Space>
    </Card>
  );
};

export default ReturnDetail;

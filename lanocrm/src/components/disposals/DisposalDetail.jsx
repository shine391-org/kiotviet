import React from 'react';
import { Card, Descriptions, Table, Tag, Space, Typography } from 'antd';
import { DISPOSAL_STATUSES, formatDisposalDate } from '../../constants/disposals';

const statusMap = DISPOSAL_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

const itemColumns = [
  { title: 'Mã hàng', dataIndex: 'sku', width: 120 },
  { title: 'Tên hàng', dataIndex: 'name', ellipsis: true },
  { title: 'SL hủy', dataIndex: 'quantity', width: 100 },
  {
    title: 'Giá vốn',
    dataIndex: 'cost_price',
    align: 'right',
    width: 120,
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
  },
  {
    title: 'Giá trị hủy',
    dataIndex: 'disposal_value',
    align: 'right',
    width: 140,
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
  },
];

const DisposalDetail = ({ data }) => {
  if (!data) return null;
  const statusMeta = statusMap[data.status] || {};

  const summary = [
    { label: 'Tổng giá trị hủy', value: data.total_value },
    { label: 'Tổng số lượng hủy', value: data.total_quantity },
  ];

  return (
    <Card size="small" title={`Phiếu xuất hủy ${data.dispose_code || ''}`} style={{ marginTop: 12 }}>
      <Descriptions size="small" column={2} bordered>
        <Descriptions.Item label="Chi nhánh">{data.branch_name || '—'}</Descriptions.Item>
        <Descriptions.Item label="Trạng thái">
          <Tag color={statusMeta.color}>{statusMeta.label || data.status || '—'}</Tag>
        </Descriptions.Item>
        <Descriptions.Item label="Người tạo">{data.creator_name || '—'}</Descriptions.Item>
        <Descriptions.Item label="Người xuất hủy">{data.executor_name || '—'}</Descriptions.Item>
        <Descriptions.Item label="Ngày xuất hủy">{formatDisposalDate(data.disposed_at)}</Descriptions.Item>
        <Descriptions.Item label="Thời gian tạo">{formatDisposalDate(data.created_at)}</Descriptions.Item>
        <Descriptions.Item label="Ghi chú" span={2}>{data.notes || '—'}</Descriptions.Item>
      </Descriptions>

      <Space orientation="vertical" style={{ marginTop: 12, width: '100%' }}>
        <Table
          dataSource={data.items || []}
          columns={itemColumns}
          size="small"
          pagination={false}
          rowKey={(r) => r.id || r.sku}
        />
      </Space>

      <Space orientation="vertical" style={{ marginTop: 12 }}>
        {summary.map((s) => (
          <Typography.Text key={s.label} strong>
            {s.label}: {(Number(s.value || 0)).toLocaleString('vi-VN')}
          </Typography.Text>
        ))}
        {data.notes && <Typography.Paragraph>{data.notes}</Typography.Paragraph>}
      </Space>
    </Card>
  );
};

export default DisposalDetail;

import React, { useEffect, useState } from 'react';
import {
  Card,
  Tabs,
  Row,
  Col,
  Input,
  Select,
  DatePicker,
  Checkbox,
  Space,
  Typography,
  Button,
  Table,
  Tag,
  Divider,
  App,
} from 'antd';
import dayjs from 'dayjs';
import {
  SHIPMENT_STATUSES,
  formatDateTime,
} from '../../constants/shipments';

const statusMap = SHIPMENT_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

const historyColumns = [
  { title: 'Thời gian tạo', dataIndex: 'time', width: 170, render: (v) => formatDateTime(v) },
  { title: 'Đối tác giao hàng', dataIndex: 'partner', width: 150 },
  { title: 'Người tạo', dataIndex: 'creator', width: 140 },
  {
    title: 'Trạng thái',
    dataIndex: 'status',
    width: 150,
    render: (v) => {
      const meta = statusMap[v] || {};
      return <Tag color={meta.color}>{meta.label || v}</Tag>;
    },
  },
];

const ShipmentDetail = ({ shipment, loading, onClose }) => {
  const { message } = App.useApp();
  const [formState, setFormState] = useState(null);

  useEffect(() => {
    setFormState(shipment ? { ...shipment } : null);
  }, [shipment]);

  if (!shipment) {
    return (
      <Card size="small" style={{ marginTop: 12 }}>
        Chọn một vận đơn để xem chi tiết.
      </Card>
    );
  }

  const infoTab = (
    <Space direction="vertical" size="middle" style={{ width: '100%' }}>
      <Row gutter={12}>
        <Col span={12}>
          <Typography.Text strong>Thông tin người nhận</Typography.Text>
          <Space direction="vertical" style={{ width: '100%' }}>
            <Input
              placeholder="Người nhận"
              value={formState?.recipient}
              onChange={(e) => setFormState((prev) => ({ ...prev, recipient: e.target.value }))}
            />
            <Input
              placeholder="Điện thoại"
              value={formState?.phone}
              onChange={(e) => setFormState((prev) => ({ ...prev, phone: e.target.value }))}
            />
            <Input.TextArea
              placeholder="Địa chỉ"
              autoSize
              value={formState?.address}
              onChange={(e) => setFormState((prev) => ({ ...prev, address: e.target.value }))}
            />
            <Input
              placeholder="Khu vực (Tỉnh/TP - Quận/Huyện)"
              value={formState?.area_path?.join(' / ') || ''}
              onChange={(e) => setFormState((prev) => ({ ...prev, area_path: [e.target.value] }))}
            />
            <Input
              placeholder="Phường/Xã"
              value={formState?.ward}
              onChange={(e) => setFormState((prev) => ({ ...prev, ward: e.target.value }))}
            />
          </Space>
        </Col>
        <Col span={12}>
          <Typography.Text strong>Thông tin hóa đơn</Typography.Text>
          <div style={{ marginTop: 8, lineHeight: 1.8 }}>
            <div>
              <Typography.Text type="secondary">Mã hóa đơn: </Typography.Text>
              <Typography.Link>{shipment.invoice_code}</Typography.Link>
            </div>
            <div>
              <Typography.Text type="secondary">Chi nhánh: </Typography.Text>
              <Typography.Text>{shipment.branch_name}</Typography.Text>
            </div>
            <div>
              <Typography.Text type="secondary">Khách hàng: </Typography.Text>
              <Typography.Text>{shipment.customer_name}</Typography.Text>
            </div>
            <div>
              <Typography.Text type="secondary">Giá trị: </Typography.Text>
              <Typography.Text strong>
                {(Number(shipment.cod_amount || 0)).toLocaleString('vi-VN')} đ
              </Typography.Text>
            </div>
            <Input.TextArea
              placeholder="Ghi chú..."
              autoSize
              style={{ marginTop: 8 }}
              value={formState?.notes || ''}
              onChange={(e) => setFormState((prev) => ({ ...prev, notes: e.target.value }))}
            />
          </div>
        </Col>
      </Row>

      <Divider style={{ margin: '8px 0' }} />

      <Typography.Text strong>Thông tin vận chuyển</Typography.Text>
      <Row gutter={12}>
        <Col span={8}>
          <Typography.Text type="secondary">Mã vận đơn</Typography.Text>
          <Input value={formState?.code} disabled style={{ marginTop: 4 }} />
        </Col>
        <Col span={8}>
          <Typography.Text type="secondary">Trạng thái giao</Typography.Text>
          <Select
            style={{ width: '100%', marginTop: 4 }}
            options={SHIPMENT_STATUSES.map((s) => ({ label: s.label, value: s.value }))}
            value={formState?.delivery_status}
            onChange={(val) => setFormState((prev) => ({ ...prev, delivery_status: val }))}
          />
        </Col>
        <Col span={8}>
          <Typography.Text type="secondary">Thời gian tạo</Typography.Text>
          <Input value={formatDateTime(shipment.created_at)} disabled style={{ marginTop: 4 }} />
        </Col>
      </Row>

      <Row gutter={12}>
        <Col span={8}>
          <Typography.Text type="secondary">Người tạo</Typography.Text>
          <Input value={shipment.created_by || '—'} disabled style={{ marginTop: 4 }} />
        </Col>
        <Col span={8}>
          <Typography.Text type="secondary">Thời gian giao</Typography.Text>
          <DatePicker
            showTime
            style={{ width: '100%', marginTop: 4 }}
            value={formState?.delivery_time ? dayjs(formState.delivery_time) : null}
            onChange={(val) =>
              setFormState((prev) => ({ ...prev, delivery_time: val ? val.toISOString() : null }))
            }
          />
        </Col>
        <Col span={8}>
          <Typography.Text type="secondary">Người giao / Đối tác</Typography.Text>
          <Select
            style={{ width: '100%', marginTop: 4 }}
            value={formState?.delivery_partner}
            options={[
              { value: 'manual', label: 'KiotViet' },
              { value: 'ghn', label: 'Giao hàng nhanh' },
              { value: 'ahamove', label: 'AhaMove' },
              { value: 'xanh_sm', label: 'Xanh SM' },
            ]}
            onChange={(val) => setFormState((prev) => ({ ...prev, delivery_partner: val }))}
          />
        </Col>
      </Row>

      <Row gutter={12}>
        <Col span={8}>
          <Typography.Text type="secondary">Trọng lượng</Typography.Text>
          <Input
            style={{ marginTop: 4 }}
            suffix={formState?.weight_unit || 'g'}
            value={formState?.weight}
            onChange={(e) => setFormState((prev) => ({ ...prev, weight: e.target.value }))}
          />
        </Col>
        <Col span={16}>
          <Typography.Text type="secondary">Kích thước (Dài x Rộng x Cao)</Typography.Text>
          <Space style={{ marginTop: 4 }} wrap>
            <Input
              style={{ width: 80 }}
              value={formState?.dimensions?.length}
              onChange={(e) =>
                setFormState((prev) => ({
                  ...prev,
                  dimensions: { ...prev.dimensions, length: e.target.value },
                }))
              }
            />
            <Input
              style={{ width: 80 }}
              value={formState?.dimensions?.width}
              onChange={(e) =>
                setFormState((prev) => ({
                  ...prev,
                  dimensions: { ...prev.dimensions, width: e.target.value },
                }))
              }
            />
            <Input
              style={{ width: 80 }}
              value={formState?.dimensions?.height}
              onChange={(e) =>
                setFormState((prev) => ({
                  ...prev,
                  dimensions: { ...prev.dimensions, height: e.target.value },
                }))
              }
            />
          </Space>
        </Col>
      </Row>

      <Row gutter={12}>
        <Col span={8}>
          <Typography.Text type="secondary">Dịch vụ</Typography.Text>
          <Select
            style={{ width: '100%', marginTop: 4 }}
            value={formState?.service}
            options={[
              { value: 'Giao thường', label: 'Giao thường' },
              { value: 'Giao nhanh', label: 'Giao nhanh' },
              { value: 'Tiêu chuẩn', label: 'Tiêu chuẩn' },
            ]}
            onChange={(val) => setFormState((prev) => ({ ...prev, service: val }))}
          />
        </Col>
        <Col span={8}>
          <div style={{ marginTop: 4 }}>
            <Checkbox
              checked={formState?.cod_amount > 0}
              onChange={(e) =>
                setFormState((prev) => ({ ...prev, cod_amount: e.target.checked ? prev.cod_amount || 0 : 0 }))
              }
            >
              Thu hộ tiền (COD)
            </Checkbox>
            <Input
              style={{ marginTop: 4 }}
              value={formState?.cod_amount}
              onChange={(e) =>
                setFormState((prev) => ({ ...prev, cod_amount: Number(e.target.value || 0) }))
              }
              suffix="đ"
            />
          </div>
        </Col>
        <Col span={8}>
          <Typography.Text type="secondary">Phí trả ĐTGH</Typography.Text>
          <Input
            style={{ marginTop: 4 }}
            value={formState?.partner_fee}
            onChange={(e) =>
              setFormState((prev) => ({ ...prev, partner_fee: Number(e.target.value || 0) }))
            }
            suffix="đ"
          />
          <Typography.Text type="secondary" style={{ display: 'block', marginTop: 4 }}>
            Còn cần trả ĐTGH: {(Number(formState?.partner_fee_due || 0)).toLocaleString('vi-VN')} đ
          </Typography.Text>
        </Col>
      </Row>

      <Input.TextArea
        placeholder="Ghi chú giao..."
        autoSize
        value={formState?.status_note || ''}
        onChange={(e) => setFormState((prev) => ({ ...prev, status_note: e.target.value }))}
      />
    </Space>
  );

  const historyTab = (
    <Table
      dataSource={shipment.delivery_history || []}
      columns={historyColumns}
      size="small"
      pagination={false}
      rowKey={(r, idx) => r.time || idx}
    />
  );

  const supportTab = (
    <div style={{ padding: 12, color: '#666' }}>
      Vận đơn chưa có yêu cầu hỗ trợ nào.
    </div>
  );

  const handleSave = () => {
    message.success('Đã lưu thay đổi (mock)');
  };

  return (
    <Card
      size="small"
      loading={loading}
      style={{ marginTop: 12 }}
      title={
        <Space>
          <span>{shipment.code}</span>
          <Tag color={statusMap[shipment.delivery_status]?.color}>
            {statusMap[shipment.delivery_status]?.label || shipment.delivery_status}
          </Tag>
        </Space>
      }
      extra={
        onClose ? (
          <Button size="small" onClick={onClose}>
            Đóng
          </Button>
        ) : null
      }
    >
      <Tabs
        defaultActiveKey="info"
        items={[
          { key: 'info', label: 'Thông tin', children: infoTab },
          { key: 'history', label: 'Lịch sử giao hàng', children: historyTab },
          { key: 'support', label: 'Yêu cầu hỗ trợ', children: supportTab },
        ]}
      />

      <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 12 }}>
        <Button onClick={() => message.info('Mở popup thanh toán (mock)')}>Thanh toán</Button>
        <Button type="primary" onClick={handleSave}>
          Lưu
        </Button>
        <Button onClick={() => message.success('Đã gửi lệnh in (mock)')}>In</Button>
      </div>
    </Card>
  );
};

export default ShipmentDetail;

import React from 'react';
import {
    Tabs,
    Row,
    Col,
    Table,
    Tag,
    Space,
    Typography,
    Button,
} from 'antd';
import {
    SHIPMENT_STATUSES,
    formatDateTime,
} from '../../constants/shipments';

const { Text, Link } = Typography;

const statusMap = SHIPMENT_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

// Delivery history table columns
const historyColumns = [
    {
        title: 'Thời gian tạo',
        dataIndex: 'time',
        width: 170,
        render: (v) => formatDateTime(v)
    },
    {
        title: 'Đối tác giao hàng',
        dataIndex: 'partner',
        width: 150
    },
    {
        title: 'Người tạo',
        dataIndex: 'creator',
        width: 140
    },
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

// Shipping info table columns
const shippingInfoColumns = [
    {
        title: '',
        dataIndex: 'code',
        width: 120,
        render: (v, record) => (
            <Space direction="vertical" size={0}>
                <Link>{v}</Link>
                <Tag color={statusMap[record.delivery_status]?.color || 'default'}>
                    {statusMap[record.delivery_status]?.label || record.delivery_status}
                </Tag>
                <Text type="secondary" style={{ fontSize: 12 }}>
                    {formatDateTime(record.created_at)}
                </Text>
                <Text type="secondary" style={{ fontSize: 12 }}>
                    {record.created_by_name || record.created_by}
                </Text>
                <Text type="secondary" style={{ fontSize: 12 }}>
                    Siêu tốc
                </Text>
                <Text type="secondary" style={{ fontSize: 12 }}>
                    {typeof record.dimensions === 'object'
                        ? `${record.dimensions?.length || 10} × ${record.dimensions?.width || 10} × ${record.dimensions?.height || 10} cm`
                        : (record.dimensions || '10 × 10 × 10 cm')}
                </Text>
            </Space>
        )
    },
    {
        title: '',
        dataIndex: 'partner_info',
        width: 150,
        render: (_, record) => (
            <Space direction="vertical" size={0}>
                <Text type="secondary">0 g</Text>
                <Text type="secondary">Giao trong ngày</Text>
            </Space>
        )
    },
    {
        title: '',
        dataIndex: 'recipient',
        width: 100,
        render: (_, record) => (
            <Space direction="vertical" size={0}>
                <Text>Người nhận</Text>
                <Text>Người nhận</Text>
            </Space>
        )
    },
    {
        title: '',
        dataIndex: 'delivery_partner_col',
        width: 120,
        render: (_, record) => (
            <Space direction="vertical" size={0} align="end">
                <Text>{record.delivery_partner_name || 'AhaMove'}</Text>
                <Tag color="cyan">Hoạt động</Tag>
            </Space>
        )
    },
    {
        title: '',
        dataIndex: 'cod_amount',
        align: 'right',
        width: 100,
        render: (v) => (
            <Space direction="vertical" size={0} align="end">
                <Text>0</Text>
                <Text>{(Number(v || 0)).toLocaleString('vi-VN')}</Text>
                <Text>{(Number(v || 0)).toLocaleString('vi-VN')}</Text>
                <Text>0</Text>
            </Space>
        )
    },
];

const ShipmentExpandedRow = ({ record }) => {
    // Info tab content
    const infoTab = (
        <div style={{ padding: '16px 0' }}>
            <Row gutter={[48, 24]}>
                {/* Recipient Info */}
                <Col span={12}>
                    <Text strong style={{ color: '#1890ff', marginBottom: 12, display: 'block' }}>
                        Thông tin người nhận
                    </Text>
                    <div style={{ lineHeight: 2 }}>
                        <Row>
                            <Col span={8}><Text type="secondary">Người nhận:</Text></Col>
                            <Col span={16}><Text>{record.recipient || record.customer_name || '—'}</Text></Col>
                        </Row>
                        <Row>
                            <Col span={8}><Text type="secondary">Điện thoại:</Text></Col>
                            <Col span={16}><Text>{record.phone || '—'}</Text></Col>
                        </Row>
                        <Row>
                            <Col span={8}><Text type="secondary">Địa chỉ:</Text></Col>
                            <Col span={16}><Text>{record.address || '—'}</Text></Col>
                        </Row>
                        <Row>
                            <Col span={8}><Text type="secondary">Khu vực:</Text></Col>
                            <Col span={16}><Text>{record.area_path?.join(' - ') || record.region || '—'}</Text></Col>
                        </Row>
                        <Row>
                            <Col span={8}><Text type="secondary">Phường/Xã:</Text></Col>
                            <Col span={16}><Text>{record.ward || '—'}</Text></Col>
                        </Row>
                    </div>
                </Col>

                {/* Invoice Info */}
                <Col span={12}>
                    <Text strong style={{ color: '#1890ff', marginBottom: 12, display: 'block' }}>
                        Thông tin hóa đơn
                    </Text>
                    <div style={{ lineHeight: 2 }}>
                        <Row>
                            <Col span={8}><Text type="secondary">Mã hóa đơn:</Text></Col>
                            <Col span={16}><Link>{record.invoice_code || '—'}</Link></Col>
                        </Row>
                        <Row>
                            <Col span={8}><Text type="secondary">Chi nhánh:</Text></Col>
                            <Col span={16}><Text>{record.branch_name || '—'}</Text></Col>
                        </Row>
                        <Row>
                            <Col span={8}><Text type="secondary">Khách hàng:</Text></Col>
                            <Col span={16}><Text>{record.customer_name || '—'}</Text></Col>
                        </Row>
                        <Row>
                            <Col span={8}><Text type="secondary">Số lượng hàng:</Text></Col>
                            <Col span={16}><Text>{record.item_count || 1}</Text></Col>
                        </Row>
                        <Row>
                            <Col span={8}><Text type="secondary">Giá trị:</Text></Col>
                            <Col span={16}>
                                <Text strong style={{ color: '#1890ff' }}>
                                    {(Number(record.cod_amount || record.total || 0)).toLocaleString('vi-VN')}
                                </Text>
                            </Col>
                        </Row>
                    </div>
                </Col>
            </Row>

            {/* Shipping Info Section */}
            <div style={{ marginTop: 24 }}>
                <Text strong style={{ color: '#1890ff', marginBottom: 12, display: 'block' }}>
                    Thông tin vận chuyển
                </Text>
                <Table
                    dataSource={[record]}
                    columns={shippingInfoColumns}
                    size="small"
                    pagination={false}
                    rowKey="id"
                    showHeader={false}
                    style={{ marginBottom: 16 }}
                />
            </div>

            {/* Footer actions */}
            <div style={{
                display: 'flex',
                justifyContent: 'space-between',
                marginTop: 16,
                paddingTop: 16,
                borderTop: '1px solid #f0f0f0'
            }}>
                <Button>Thanh toán</Button>
                <Space>
                    <Button type="link">Cấu hình yêu cầu hỗ trợ</Button>
                    <Button icon={<span>🖨</span>}>In</Button>
                </Space>
            </div>
        </div>
    );

    // Delivery history tab content
    const historyTab = (
        <div style={{ padding: '16px 0' }}>
            <Table
                dataSource={record.delivery_history || [
                    {
                        time: record.created_at,
                        partner: record.delivery_partner_name || 'AhaMove',
                        creator: record.created_by_name || 'staff',
                        status: 'pending',
                    },
                    {
                        time: record.updated_at,
                        partner: record.delivery_partner_name || 'AhaMove',
                        creator: record.created_by_name || 'staff',
                        status: record.delivery_status,
                    },
                ]}
                columns={[
                    {
                        title: 'Thời gian tạo',
                        dataIndex: 'time',
                        width: 170,
                        render: (v) => <Text style={{ color: '#fa8c16' }}>{formatDateTime(v)}</Text>
                    },
                    {
                        title: 'Đối tác giao hàng',
                        dataIndex: 'partner',
                        width: 150,
                        render: (v) => <Text style={{ color: '#1890ff' }}>{v}</Text>
                    },
                    {
                        title: 'Người tạo',
                        dataIndex: 'creator',
                        width: 140
                    },
                    {
                        title: 'Trạng thái',
                        dataIndex: 'status',
                        width: 150,
                        render: (v) => {
                            const meta = statusMap[v] || {};
                            return <Tag color={meta.color}>{meta.label || v}</Tag>;
                        },
                    },
                ]}
                size="small"
                pagination={false}
                rowKey={(r, idx) => idx}
            />
        </div>
    );

    // Support request tab content
    const supportTab = (
        <div style={{ padding: '24px 0', color: '#666' }}>
            Vận đơn chưa có yêu cầu hỗ trợ nào.
        </div>
    );

    return (
        <div style={{
            padding: '0 16px 16px',
            backgroundColor: '#fff',
            border: '2px solid #1890ff',
            borderTop: 'none',
            marginTop: -1
        }}>
            <Tabs
                defaultActiveKey="info"
                size="small"
                items={[
                    { key: 'info', label: 'Thông tin', children: infoTab },
                    { key: 'history', label: 'Lịch sử giao hàng', children: historyTab },
                    { key: 'support', label: 'Yêu cầu hỗ trợ', children: supportTab },
                ]}
            />
        </div>
    );
};

export default ShipmentExpandedRow;

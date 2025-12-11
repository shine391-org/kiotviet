import React from 'react';
import {
    Tabs,
    Descriptions,
    Select,
    Input,
    DatePicker,
    Checkbox,
    Table,
    Button,
    Space,
    Tag,
    Row,
    Col,
    Typography
} from 'antd';
import {
    EnvironmentOutlined,
    CopyOutlined,
    ExportOutlined,
    SaveOutlined,
    PrinterOutlined
} from '@ant-design/icons';
import { formatDate, ORDER_STATUSES } from '../../constants/orders';

const { TextArea } = Input;
const { Text } = Typography;

const statusMap = ORDER_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

// Product items table columns
const productColumns = [
    { title: 'Mã hàng', dataIndex: 'product_code', width: 120 },
    { title: 'Tên hàng', dataIndex: 'product_name', width: 280 },
    { title: 'Số lượng', dataIndex: 'quantity', width: 80, align: 'center' },
    {
        title: 'Đơn giá', dataIndex: 'unit_price', width: 120, align: 'right',
        render: (v) => (Number(v || 0)).toLocaleString('vi-VN')
    },
    {
        title: 'Giảm giá', dataIndex: 'discount', width: 100, align: 'right',
        render: (v) => (Number(v || 0)).toLocaleString('vi-VN')
    },
    {
        title: 'Giá bán', dataIndex: 'final_price', width: 120, align: 'right',
        render: (v) => (Number(v || 0)).toLocaleString('vi-VN')
    },
    {
        title: 'Thành tiền', key: 'line_total', width: 120, align: 'right',
        render: (_, r) => (Number(r.final_price || r.unit_price || 0) * Number(r.quantity || 0)).toLocaleString('vi-VN')
    },
];

const OrderExpandedRow = ({ record, onSave, onCancel, onProcess }) => {
    const statusMeta = statusMap[record.status] || {};

    // Calculate totals
    const items = record.items || [];
    const itemCount = items.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
    const subtotal = items.reduce((sum, item) => sum + (Number(item.final_price || item.unit_price || 0) * Number(item.quantity || 0)), 0);
    const discountTotal = Number(record.discount || 0);
    const totalAmount = subtotal - discountTotal;
    const paidAmount = Number(record.paid_amount || 0);

    return (
        <div style={{ padding: '16px', backgroundColor: '#fafafa' }}>
            <Tabs
                defaultActiveKey="info"
                size="small"
                items={[
                    {
                        key: 'info',
                        label: 'Thông tin',
                        children: (
                            <div>
                                {/* Header with customer name and branch */}
                                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 16 }}>
                                    <div>
                                        <Text strong style={{ fontSize: 16 }}>Khách lẻ</Text>
                                        <Text type="secondary" style={{ marginLeft: 8 }}>{record.order_number}</Text>
                                    </div>
                                    <Text type="secondary">{record.branch_name || 'Lano - HN'}</Text>
                                </div>

                                {/* Order details form */}
                                <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                                    <Col span={8}>
                                        <div style={{ marginBottom: 8 }}>
                                            <Text type="secondary">Người tạo:</Text>
                                            <Select
                                                size="small"
                                                style={{ width: '100%', marginTop: 4 }}
                                                defaultValue={record.created_by || 'Trung'}
                                            >
                                                <Select.Option value="Trung">Trung</Select.Option>
                                            </Select>
                                        </div>
                                    </Col>
                                    <Col span={8}>
                                        <div style={{ marginBottom: 8 }}>
                                            <Text type="secondary">Người nhận đặt:</Text>
                                            <Select
                                                size="small"
                                                style={{ width: '100%', marginTop: 4 }}
                                                defaultValue={record.receiver || 'Trung'}
                                            >
                                                <Select.Option value="Trung">Trung</Select.Option>
                                            </Select>
                                        </div>
                                    </Col>
                                    <Col span={8}>
                                        <div style={{ marginBottom: 8 }}>
                                            <Text type="secondary">Ngày đặt:</Text>
                                            <Input
                                                size="small"
                                                style={{ marginTop: 4 }}
                                                value={formatDate(record.order_date)}
                                                readOnly
                                            />
                                        </div>
                                    </Col>
                                </Row>

                                <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                                    <Col span={8}>
                                        <div style={{ marginBottom: 8 }}>
                                            <Text type="secondary">Kênh bán:</Text>
                                            <Select
                                                size="small"
                                                style={{ width: '100%', marginTop: 4 }}
                                                defaultValue={record.sales_channel || 'Bán trực tiếp'}
                                            >
                                                <Select.Option value="Bán trực tiếp">Bán trực tiếp</Select.Option>
                                            </Select>
                                        </div>
                                    </Col>
                                    <Col span={8}>
                                        <div style={{ marginBottom: 8 }}>
                                            <Text type="secondary">Bảng giá:</Text>
                                            <Select
                                                size="small"
                                                style={{ width: '100%', marginTop: 4 }}
                                                placeholder="Bảng giá chung"
                                            >
                                                <Select.Option value="default">Bảng giá chung</Select.Option>
                                            </Select>
                                        </div>
                                    </Col>
                                    <Col span={8}>
                                        <div style={{ marginBottom: 8 }}>
                                            <Text type="secondary">Chi nhánh xử lý:</Text>
                                            <Input
                                                size="small"
                                                style={{ marginTop: 4 }}
                                                value={record.branch_name || 'Lano - HN'}
                                                readOnly
                                            />
                                        </div>
                                    </Col>
                                </Row>

                                <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                                    <Col span={8}>
                                        <div style={{ marginBottom: 8 }}>
                                            <Text type="secondary">Trạng thái:</Text>
                                            <Select
                                                size="small"
                                                style={{ width: '100%', marginTop: 4 }}
                                                defaultValue={record.status || 'pending'}
                                            >
                                                {ORDER_STATUSES.map(s => (
                                                    <Select.Option key={s.value} value={s.value}>{s.label}</Select.Option>
                                                ))}
                                            </Select>
                                        </div>
                                    </Col>
                                </Row>

                                {/* Shipping address section */}
                                <div style={{
                                    backgroundColor: '#fff',
                                    border: '1px solid #f0f0f0',
                                    borderRadius: 4,
                                    padding: 12,
                                    marginBottom: 16
                                }}>
                                    <div style={{ marginBottom: 8 }}>
                                        <Text type="secondary"><EnvironmentOutlined /> Từ giao đến: </Text>
                                        <Text>{record.shipping_address_from || ''}</Text>
                                    </div>
                                    <div>
                                        <Text type="secondary"><EnvironmentOutlined /> Địa chỉ lấy hàng: </Text>
                                        <Text>{record.shipping_address || ''}</Text>
                                        {record.shipping_phone && (
                                            <Text type="secondary" style={{ marginLeft: 8 }}>- {record.shipping_phone}</Text>
                                        )}
                                    </div>
                                </div>

                                {/* Shipping details form */}
                                <Row gutter={[16, 12]} style={{ marginBottom: 12 }}>
                                    <Col span={8}>
                                        <Text type="secondary">Người nhận:</Text>
                                        <Input size="small" value={record.receiver_name || 'trung'} style={{ marginTop: 4 }} />
                                    </Col>
                                    <Col span={8}>
                                        <Text type="secondary">Mã vận đơn:</Text>
                                        <Input size="small" value={record.tracking_code || ''} style={{ marginTop: 4 }} />
                                    </Col>
                                    <Col span={8}>
                                        <Text type="secondary">Người giao:</Text>
                                        <Select size="small" style={{ width: '100%', marginTop: 4 }} placeholder="Chọn">
                                            <Select.Option value="ha12">ha12</Select.Option>
                                        </Select>
                                    </Col>
                                </Row>

                                <Row gutter={[16, 12]} style={{ marginBottom: 12 }}>
                                    <Col span={8}>
                                        <Text type="secondary">Điện thoại:</Text>
                                        <Input size="small" value={record.shipping_phone || '0966601291'} style={{ marginTop: 4 }} />
                                    </Col>
                                    <Col span={8}>
                                        <Text type="secondary">Trọng lượng:</Text>
                                        <Space style={{ marginTop: 4, display: 'flex' }}>
                                            <Input size="small" style={{ width: 80 }} value="500" suffix="g" />
                                        </Space>
                                    </Col>
                                    <Col span={8}>
                                        <div style={{ marginTop: 24 }}>
                                            <Checkbox defaultChecked>Thu hộ tiền:</Checkbox>
                                        </div>
                                    </Col>
                                </Row>

                                <Row gutter={[16, 12]} style={{ marginBottom: 12 }}>
                                    <Col span={8}>
                                        <Text type="secondary">Địa chỉ:</Text>
                                        <Input size="small" value={record.shipping_address_line || 'dsf'} style={{ marginTop: 4 }} />
                                    </Col>
                                    <Col span={8}>
                                        <Text type="secondary">Kích thước:</Text>
                                        <Space style={{ marginTop: 4, display: 'flex' }} size={4}>
                                            <Input size="small" style={{ width: 60 }} placeholder="D" value="10" />
                                            <Input size="small" style={{ width: 60 }} placeholder="R" value="10" />
                                            <Input size="small" style={{ width: 60 }} placeholder="C" value="10" />
                                        </Space>
                                    </Col>
                                    <Col span={8}>
                                        <Text type="secondary">Phí trả ĐTGH:</Text>
                                        <Input size="small" style={{ marginTop: 4 }} disabled />
                                    </Col>
                                </Row>

                                <Row gutter={[16, 12]} style={{ marginBottom: 12 }}>
                                    <Col span={8}>
                                        <Text type="secondary">Khu vực:</Text>
                                        <Select size="small" style={{ width: '100%', marginTop: 4 }} placeholder="Chọn Tỉnh/Thành phố">
                                            <Select.Option value="hanoi">Hà Nội</Select.Option>
                                        </Select>
                                    </Col>
                                    <Col span={8}>
                                        <Text type="secondary">Dịch vụ:</Text>
                                        <Select size="small" style={{ width: '100%', marginTop: 4 }} defaultValue="standard">
                                            <Select.Option value="standard">Giao thường</Select.Option>
                                        </Select>
                                    </Col>
                                    <Col span={8}>
                                        <Text type="secondary">Thời gian giao hàng:</Text>
                                        <DatePicker size="small" style={{ width: '100%', marginTop: 4 }} showTime />
                                    </Col>
                                </Row>

                                <Row gutter={[16, 12]} style={{ marginBottom: 16 }}>
                                    <Col span={8}>
                                        <Text type="secondary">Phường/Xã:</Text>
                                        <Select size="small" style={{ width: '100%', marginTop: 4 }} placeholder="Chọn Phường/Xã">
                                            <Select.Option value="thanhcong">Phường Thành Công</Select.Option>
                                        </Select>
                                    </Col>
                                </Row>

                                {/* Notes */}
                                <div style={{ marginBottom: 16 }}>
                                    <TextArea
                                        rows={3}
                                        placeholder="Ghi chú..."
                                        defaultValue={record.note || ''}
                                    />
                                </div>

                                {/* Products table */}
                                <Table
                                    dataSource={items}
                                    columns={productColumns}
                                    size="small"
                                    pagination={false}
                                    rowKey={(r) => r.id || `${r.product_id}-${r.variant_id}`}
                                    style={{ marginBottom: 16 }}
                                />

                                {/* Totals section */}
                                <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
                                    <div style={{ width: 300 }}>
                                        <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 4 }}>
                                            <Text>Tổng tiền hàng ({itemCount}):</Text>
                                            <Text style={{ color: '#1890ff' }}>{subtotal.toLocaleString('vi-VN')}</Text>
                                        </div>
                                        <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 4 }}>
                                            <Text>Giảm giá phiếu đặt:</Text>
                                            <Text>{discountTotal.toLocaleString('vi-VN')}</Text>
                                        </div>
                                        <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 4 }}>
                                            <Text strong>Tổng cộng:</Text>
                                            <Text strong style={{ color: '#1890ff' }}>{totalAmount.toLocaleString('vi-VN')}</Text>
                                        </div>
                                        <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                                            <Text>Khách đã trả:</Text>
                                            <Text>{paidAmount.toLocaleString('vi-VN')}</Text>
                                        </div>
                                    </div>
                                </div>

                                {/* Action buttons */}
                                <div style={{
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    marginTop: 16,
                                    paddingTop: 16,
                                    borderTop: '1px solid #f0f0f0'
                                }}>
                                    <Space>
                                        <Button danger onClick={onCancel}>Hủy</Button>
                                        <Button icon={<CopyOutlined />}>Sao chép</Button>
                                        <Button icon={<ExportOutlined />}>Xuất file</Button>
                                    </Space>
                                    <Space>
                                        <Button type="primary" onClick={onProcess}>Xử lý đơn hàng</Button>
                                        <Button icon={<SaveOutlined />} onClick={onSave}>Lưu</Button>
                                        <Button>Kết thúc</Button>
                                        <Button icon={<PrinterOutlined />}>In</Button>
                                    </Space>
                                </div>
                            </div>
                        ),
                    },
                ]}
            />
        </div>
    );
};

export default OrderExpandedRow;

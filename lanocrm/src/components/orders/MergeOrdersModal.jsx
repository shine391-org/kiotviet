import React, { useState } from 'react';
import { Modal, Table, Typography, Empty, Button, Space } from 'antd';
import { formatDate } from '../../constants/orders';

const { Text } = Typography;

const MergeOrdersModal = ({ open, onClose, orders = [], loading = false, onMerge }) => {
    const [selectedRowKeys, setSelectedRowKeys] = useState([]);

    const columns = [
        {
            title: 'Mã đặt hàng',
            dataIndex: 'order_number',
            key: 'order_number',
            width: 120,
        },
        {
            title: 'Thời gian',
            dataIndex: 'order_date',
            key: 'order_date',
            width: 140,
            render: (v) => formatDate(v),
        },
        {
            title: 'Khách cần trả',
            dataIndex: 'total',
            key: 'total',
            width: 120,
            align: 'right',
            render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
        },
        {
            title: 'Khách đã trả',
            dataIndex: 'paid_amount',
            key: 'paid_amount',
            width: 120,
            align: 'right',
            render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
        },
        {
            title: 'Mã khách hàng',
            dataIndex: 'customer_code',
            key: 'customer_code',
            width: 120,
        },
        {
            title: 'Tên khách hàng',
            dataIndex: 'customer_name',
            key: 'customer_name',
            width: 150,
        },
    ];

    const rowSelection = {
        selectedRowKeys,
        onChange: (keys) => setSelectedRowKeys(keys),
    };

    const handleMerge = () => {
        if (onMerge && selectedRowKeys.length >= 2) {
            const selectedOrders = orders.filter(o => selectedRowKeys.includes(o.id));
            onMerge(selectedOrders);
        }
    };

    return (
        <Modal
            title="Gộp phiếu đặt hàng"
            open={open}
            onCancel={onClose}
            width={800}
            footer={
                <Space>
                    <Button onClick={onClose}>Hủy</Button>
                    <Button
                        type="primary"
                        onClick={handleMerge}
                        loading={loading}
                        disabled={selectedRowKeys.length < 2}
                    >
                        Gộp đơn ({selectedRowKeys.length} đã chọn)
                    </Button>
                </Space>
            }
        >
            <div style={{ marginBottom: 16 }}>
                <Text>
                    Hệ thống hỗ trợ gộp các đơn đặt hàng có cùng khách hàng hoặc số điện thoại được tạo trong{' '}
                    <Text strong style={{ color: '#1890ff' }}>7 ngày qua</Text>{' '}
                    thành 1 phiếu đặt hàng
                </Text>
            </div>

            <Table
                rowKey="id"
                dataSource={orders}
                columns={columns}
                loading={loading}
                pagination={false}
                size="small"
                rowSelection={rowSelection}
                locale={{
                    emptyText: (
                        <Empty
                            image={Empty.PRESENTED_IMAGE_SIMPLE}
                            description="Không có đặt hàng nào được tạo trong 7 ngày qua có cùng khách hàng hoặc số điện thoại"
                        />
                    ),
                }}
                scroll={{ y: 300 }}
            />
        </Modal>
    );
};

export default MergeOrdersModal;

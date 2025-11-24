import React, { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Card, Table, Button, Space, Tag } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import { fetchPriceLists } from '../../store/slices/priceListSlice';

const columns = [
  { title: 'Tên bảng giá', dataIndex: 'name', key: 'name' },
  { title: 'Loại', dataIndex: 'type', key: 'type' },
  { title: 'Áp dụng từ', dataIndex: 'start_date', key: 'start_date' },
  { title: 'Đến', dataIndex: 'end_date', key: 'end_date' },
  { title: 'Ưu tiên', dataIndex: 'priority', key: 'priority' },
  {
    title: 'Trạng thái',
    dataIndex: 'status',
    key: 'status',
    render: (status) => {
      const color = status === 'active' ? 'green' : status === 'upcoming' ? 'blue' : 'default';
      return <Tag color={color}>{status || 'unknown'}</Tag>;
    },
  },
];

const PriceListListPage = () => {
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const { items, loading } = useSelector(state => state.priceList);

  useEffect(() => {
    dispatch(fetchPriceLists({ page: 1, limit: 20 }));
  }, [dispatch]);

  return (
    <Card
      title="Bảng giá"
      extra={
        <Space>
          <Button type="primary" icon={<PlusOutlined />} onClick={() => navigate('/price-lists/create')}>
            Tạo bảng giá
          </Button>
        </Space>
      }
    >
      <Table
        rowKey="id"
        dataSource={items}
        columns={columns}
        loading={loading}
        pagination={false}
      />
    </Card>
  );
};

export default PriceListListPage;

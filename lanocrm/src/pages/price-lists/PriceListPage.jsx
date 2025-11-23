import React, { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { Card, Button, Input, Table, Tag, Space, Select, Popconfirm, Badge, App, Tooltip } from 'antd';
import { PlusOutlined, EditOutlined, DeleteOutlined, ReloadOutlined, SearchOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { fetchPriceLists, deletePriceList, resetPriceListState } from '../../store/slices/priceListSlice';

const statusColor = {
  active: 'green',
  expired: 'red',
  upcoming: 'blue',
  inactive: 'default',
};

const PriceListPage = () => {
  const { message } = App.useApp();
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const { items, loading, pagination } = useSelector(state => state.priceList);

  const [filters, setFilters] = useState({ page: 1, limit: 20, search: '', status: null });

  useEffect(() => {
    dispatch(fetchPriceLists(filters));
  }, [dispatch, filters]);

  useEffect(() => () => { dispatch(resetPriceListState()); }, [dispatch]);

  const onDelete = async (id) => {
    const res = await dispatch(deletePriceList(id));
    if (!res.error) {
      message.success('Đã xoá bảng giá');
      dispatch(fetchPriceLists(filters));
    } else {
      message.error(res.payload || 'Xoá thất bại');
    }
  };

  const columns = useMemo(() => [
    {
      title: 'Tên bảng giá',
      dataIndex: 'name',
      key: 'name',
      render: (text, record) => (
        <Space>
          <Button type="link" onClick={() => navigate(`/price-lists/edit/${record.id}`)}>{text}</Button>
          <Tag color="purple">#{record.id}</Tag>
        </Space>
      ),
    },
    {
      title: 'Loại',
      dataIndex: 'type',
      key: 'type',
      render: (type) => <Tag color="blue">{type?.toUpperCase()}</Tag>,
    },
    {
      title: 'Thời gian',
      key: 'dates',
      render: (_, row) => {
        const start = row.start_date ? dayjs(row.start_date).format('DD/MM') : '∞';
        const end = row.end_date ? dayjs(row.end_date).format('DD/MM') : '∞';
        return `${start} - ${end}`;
      },
    },
    {
      title: 'Ưu tiên',
      dataIndex: 'priority',
      key: 'priority',
    },
    {
      title: 'Trạng thái',
      dataIndex: 'status',
      key: 'status',
      render: (status) => <Badge color={statusColor[status] || 'default'} text={status} />,
    },
    {
      title: 'Áp dụng nhóm KH',
      dataIndex: 'apply_to_groups',
      key: 'apply_to_groups',
      render: (groups) => groups?.length ? groups.join(', ') : 'Tất cả',
    },
    {
      title: 'Thao tác',
      key: 'actions',
      render: (_, record) => (
        <Space>
          <Tooltip title="Chỉnh sửa">
            <Button icon={<EditOutlined />} onClick={() => navigate(`/price-lists/edit/${record.id}`)} />
          </Tooltip>
          <Popconfirm title="Xóa bảng giá?" onConfirm={() => onDelete(record.id)}>
            <Button danger icon={<DeleteOutlined />} />
          </Popconfirm>
        </Space>
      ),
    },
  ], [navigate, filters]);

  return (
    <Card
      title="Bảng giá"
      extra={(
        <Space>
          <Input
            placeholder="Tìm kiếm..."
            allowClear
            prefix={<SearchOutlined />}
            onChange={(e) => setFilters({ ...filters, search: e.target.value, page: 1 })}
            style={{ width: 220 }}
          />
          <Select
            placeholder="Trạng thái"
            allowClear
            style={{ width: 150 }}
            onChange={(value) => setFilters({ ...filters, status: value, page: 1 })}
            options={[
              { label: 'Đang áp dụng', value: 'active' },
              { label: 'Sắp áp dụng', value: 'upcoming' },
              { label: 'Hết hạn', value: 'expired' },
              { label: 'Tắt', value: 'inactive' },
            ]}
          />
          <Button icon={<ReloadOutlined />} onClick={() => dispatch(fetchPriceLists(filters))}>Làm mới</Button>
          <Button type="primary" icon={<PlusOutlined />} onClick={() => navigate('/price-lists/create')}>
            Tạo bảng giá
          </Button>
        </Space>
      )}
    >
      <Table
        rowKey="id"
        loading={loading}
        dataSource={items}
        columns={columns}
        pagination={{
          current: pagination.page,
          pageSize: pagination.limit,
          total: pagination.total,
          onChange: (page, pageSize) => setFilters({ ...filters, page, limit: pageSize }),
        }}
      />
    </Card>
  );
};

export default PriceListPage;

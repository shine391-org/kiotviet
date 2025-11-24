import React, { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { Card, Button, Input, Table, Tag, Space, Select, Popconfirm, Badge, App, Tooltip, Row, Col, Form, InputNumber, Divider } from 'antd';
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

  const [filters, setFilters] = useState({
    page: 1,
    limit: 20,
    search: '',
    status: null,
    stock: null,
    price_condition: null,
    price_compare: null,
    price_value: null,
  });

  const [form] = Form.useForm();

  useEffect(() => {
    dispatch(fetchPriceLists(filters));
  }, [dispatch, filters]);

  useEffect(() => {
    form.setFieldsValue(filters);
  }, [filters, form]);

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
  ], [navigate, onDelete, statusColor]);

  const resetFilters = () => {
    const base = {
      page: 1,
      limit: 20,
      search: '',
      status: null,
      stock: null,
      price_condition: null,
      price_compare: null,
      price_value: null,
    };
    setFilters(base);
  };

  return (
    <Card title="Bảng giá">
      <Row gutter={[16, 16]}>
        <Col xs={24} md={8} lg={6}>
          <Card size="small" title="Bộ lọc" bordered={false} style={{ background: '#fafafa' }}>
            <Space direction="vertical" style={{ width: '100%' }} size="middle">
              <Input
                placeholder="Tìm kiếm..."
                allowClear
                prefix={<SearchOutlined />}
                onChange={(e) => setFilters({ ...filters, search: e.target.value, page: 1 })}
              />

              <Select
                placeholder="Trạng thái"
                allowClear
                value={filters.status}
                onChange={(value) => setFilters({ ...filters, status: value, page: 1 })}
                options={[
                  { label: 'Đang áp dụng', value: 'active' },
                  { label: 'Sắp áp dụng', value: 'upcoming' },
                  { label: 'Hết hạn', value: 'expired' },
                  { label: 'Tắt', value: 'inactive' },
                ]}
              />

              <Select
                placeholder="Tồn kho"
                allowClear
                value={filters.stock}
                onChange={(value) => setFilters({ ...filters, stock: value, page: 1 })}
                options={[
                  { label: 'Còn hàng', value: 'in_stock' },
                  { label: 'Hết hàng', value: 'out_of_stock' },
                ]}
              />

              <Divider style={{ margin: '8px 0' }}>Giá bán</Divider>
              <Form
                form={form}
                layout="vertical"
                onFinish={(values) => setFilters({ ...filters, ...values, page: 1 })}
              >
                <Form.Item name="price_condition" label="Điều kiện">
                  <Select
                    placeholder="Chọn điều kiện"
                    options={[
                      { label: 'Lớn hơn', value: 'gt' },
                      { label: 'Nhỏ hơn', value: 'lt' },
                      { label: 'Bằng', value: 'eq' },
                    ]}
                  />
                </Form.Item>
                <Form.Item name="price_compare" label="Giá so sánh">
                  <Select
                    placeholder="Chọn giá so sánh"
                    options={[
                      { label: 'Giá gốc (SP)', value: 'base' },
                      { label: 'Giá bảng giá', value: 'price_list' },
                      { label: 'Giá sau giảm', value: 'final' },
                    ]}
                  />
                </Form.Item>
                <Form.Item name="price_value" label="Giá trị (đ)">
                  <InputNumber style={{ width: '100%' }} min={0} step={1000} placeholder="Nhập giá" />
                </Form.Item>
                <Space>
                  <Button type="primary" htmlType="submit" icon={<SearchOutlined />}>Áp dụng</Button>
                  <Button onClick={resetFilters}>Reset</Button>
                </Space>
              </Form>
            </Space>
          </Card>
        </Col>

        <Col xs={24} md={16} lg={18}>
          <Space style={{ marginBottom: 12 }}>
            <Button icon={<ReloadOutlined />} onClick={() => dispatch(fetchPriceLists(filters))}>Làm mới</Button>
            <Button type="primary" icon={<PlusOutlined />} onClick={() => navigate('/price-lists/create')}>
              Tạo bảng giá
            </Button>
          </Space>
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
        </Col>
      </Row>
    </Card>
  );
};

export default PriceListPage;

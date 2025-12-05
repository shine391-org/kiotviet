import React, { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Card, Table, Button, Space, Tag, Tooltip, Input, Popover, Checkbox } from 'antd';
import {
  PlusOutlined,
  ImportOutlined,
  ExportOutlined,
  UnorderedListOutlined,
  SettingOutlined,
  QuestionCircleOutlined
} from '@ant-design/icons';
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
  const { items, loading, pagination } = useSelector(state => state.priceList);
  const [filters, setFilters] = useState({ page: 1, limit: 20 });

  // Column visibility state
  const defaultCheckedList = columns.map(col => col.key);
  const [checkedList, setCheckedList] = useState(defaultCheckedList);

  useEffect(() => {
    dispatch(fetchPriceLists(filters));
  }, [dispatch, filters]);

  const handleSearch = (value) => {
    setFilters(prev => ({ ...prev, search: value, page: 1 }));
  };

  const handleColumnChange = (list) => {
    setCheckedList(list);
  };

  const visibleColumns = columns.filter(col => checkedList.includes(col.key));

  const columnOptions = columns.map(col => ({ label: col.title, value: col.key }));

  const columnSelector = (
    <div style={{ padding: '8px', minWidth: '150px' }}>
      <Checkbox.Group
        options={columnOptions}
        value={checkedList}
        onChange={handleColumnChange}
        style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}
      />
    </div>
  );

  return (
    <Card
      title="Bảng giá"
      extra={
        <Space>
          <Input.Search
            placeholder="Tìm kiếm bảng giá..."
            onSearch={handleSearch}
            allowClear
            style={{ width: 250 }}
            enterButton
          />
          <Button type="primary" icon={<PlusOutlined />} onClick={() => navigate('/price-lists/create')}>
            Bảng giá
          </Button>
          <Button icon={<ImportOutlined />}>Import</Button>
          <Button icon={<ExportOutlined />}>Xuất file</Button>
          <Popover content={columnSelector} trigger="click" placement="bottomRight" arrow={false}>
            <Button icon={<UnorderedListOutlined />} />
          </Popover>
          <Button icon={<SettingOutlined />} />
          <Button icon={<QuestionCircleOutlined />} />
        </Space>
      }
    >
      <Table
        rowKey="id"
        dataSource={items}
        columns={visibleColumns}
        loading={loading}
        pagination={{
          current: filters.page,
          pageSize: filters.limit,
          total: pagination?.total || 0,
          onChange: (page, pageSize) => setFilters(prev => ({ ...prev, page, limit: pageSize }))
        }}
      />
    </Card>
  );
};

export default PriceListListPage;

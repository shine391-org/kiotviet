import React, { useEffect, useState } from 'react';
import { Card, Select, Radio, DatePicker, Space, Tag, Divider, Typography } from 'antd';
import {
  ORDER_STATUSES,
  PAYMENT_METHODS,
  startOfMonthIso,
  SHIPPING_PARTNERS,
  SALES_CHANNELS,
} from '../../constants/orders';
import dayjs from 'dayjs';

const { RangePicker } = DatePicker;

const OrderFilters = ({ filters, onChange, branches = [] }) => {
  const [dateMode, setDateMode] = useState('month');
  const [customRange, setCustomRange] = useState(null);

  useEffect(() => {
    if (dateMode === 'month') {
      onChange({ date_from: startOfMonthIso(), date_to: dayjs().format('YYYY-MM-DD') });
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [dateMode]);

  const handleStatusChange = (values) => {
    onChange({ status: values, page: 1 });
  };

  const handleRangeChange = (values) => {
    setCustomRange(values);
    if (!values || values.length !== 2) return;
    onChange({
      date_from: values[0]?.format('YYYY-MM-DD'),
      date_to: values[1]?.format('YYYY-MM-DD'),
    });
  };

  return (
    <Card size="small" title="Đặt hàng" style={{ width: 280 }} bodyStyle={{ padding: 12 }}>
      <div className="filter-block">
        <div className="filter-label">Chi nhánh xử lý</div>
        <Select
          allowClear
          style={{ width: '100%' }}
          placeholder="Chọn chi nhánh"
          value={filters.branch_id}
          options={branches.map((b) => ({ value: b.id, label: b.name || b.code }))}
          onChange={(v) => onChange({ branch_id: v })}
        />
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Thời gian</div>
        <Radio.Group
          value={dateMode}
          onChange={(e) => setDateMode(e.target.value)}
          style={{ display: 'flex', flexDirection: 'column', gap: 8 }}
        >
          <Radio value="month">Tháng này</Radio>
          <Radio value="custom">Tùy chỉnh</Radio>
        </Radio.Group>
        {dateMode === 'custom' && (
          <RangePicker
            style={{ width: '100%', marginTop: 8 }}
            value={customRange}
            onChange={handleRangeChange}
            format="DD/MM/YYYY"
          />
        )}
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Trạng thái</div>
        <Select
          mode="multiple"
          allowClear
          maxTagCount="responsive"
          style={{ width: '100%' }}
          value={filters.status}
          options={ORDER_STATUSES.map((s) => ({ value: s.value, label: s.label }))}
          onChange={handleStatusChange}
          placeholder="Chọn trạng thái"
        />
        <Space wrap style={{ marginTop: 8 }}>
          {ORDER_STATUSES.slice(0, 3).map((s) => (
            <Tag key={s.value} color={s.color}>{s.label}</Tag>
          ))}
        </Space>
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Đối tác giao hàng</div>
        <Select
          allowClear
          placeholder="Chọn đối tác giao hàng"
          style={{ width: '100%' }}
          value={filters.shipping_partner}
          options={SHIPPING_PARTNERS.map((p) => ({
            value: p.value,
            label: (
              <Space>
                <span>{p.label}</span>
                <Tag color={p.status === 'active' ? 'green' : 'red'}>{p.status === 'active' ? 'Active' : 'Inactive'}</Tag>
              </Space>
            ),
          }))}
          onChange={(v) => onChange({ shipping_partner: v })}
        />
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Phương thức thanh toán</div>
        <Select
          allowClear
          style={{ width: '100%' }}
          placeholder="Chọn phương thức"
          value={filters.payment_method}
          options={PAYMENT_METHODS}
          onChange={(v) => onChange({ payment_method: v })}
        />
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Người tạo</div>
        <Select
          allowClear
          placeholder="Chọn người tạo"
          style={{ width: '100%' }}
          value={filters.created_by}
          options={[]}
          onChange={(v) => onChange({ created_by: v })}
        />
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Người nhận đặt</div>
        <Select
          allowClear
          placeholder="Chọn người nhận đặt"
          style={{ width: '100%' }}
          value={filters.receiver_id}
          options={[]}
          onChange={(v) => onChange({ receiver_id: v })}
        />
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Kênh bán</div>
        <Select
          allowClear
          placeholder="Chọn kênh bán"
          style={{ width: '100%' }}
          value={filters.channel}
          options={SALES_CHANNELS}
          onChange={(v) => onChange({ channel: v })}
        />
        <Typography.Link style={{ fontSize: 12, marginTop: 4 }}>Tạo mới</Typography.Link>
      </div>
    </Card>
  );
};

export default OrderFilters;

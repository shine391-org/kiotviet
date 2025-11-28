import React, { useEffect, useState } from 'react';
import { Card, Select, Radio, DatePicker, Divider, Checkbox, Space, Typography } from 'antd';
import dayjs from 'dayjs';
import {
  INVOICE_TYPES,
  INVOICE_STATUSES,
  E_INVOICE_STATUSES,
  DELIVERY_STATUSES,
  SHIPPING_PARTNERS,
  PAYMENT_METHODS,
  SALES_CHANNELS,
  startOfMonthIso,
} from '../../constants/invoices';

const { RangePicker } = DatePicker;

const InvoiceFilters = ({ filters, onChange, branches = [] }) => {
  const [dateMode, setDateMode] = useState('month');
  const [dateRange, setDateRange] = useState(null);
  const [deliveryTimeMode, setDeliveryTimeMode] = useState(filters.shipping_time_mode || 'all');
  const [deliveryRange, setDeliveryRange] = useState(null);

  useEffect(() => {
    if (dateMode === 'month') {
      onChange({ date_from: startOfMonthIso(), date_to: dayjs().format('YYYY-MM-DD') });
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [dateMode]);

  useEffect(() => {
    setDeliveryTimeMode(filters.shipping_time_mode || 'all');
  }, [filters.shipping_time_mode]);

  const handleDateRangeChange = (values) => {
    setDateRange(values);
    if (!values || values.length !== 2) return;
    onChange({
      date_from: values[0]?.format('YYYY-MM-DD'),
      date_to: values[1]?.format('YYYY-MM-DD'),
    });
  };

  const handleDeliveryRangeChange = (values) => {
    setDeliveryRange(values);
    if (!values || values.length !== 2) return;
    onChange({
      shipping_time_from: values[0]?.format('YYYY-MM-DD'),
      shipping_time_to: values[1]?.format('YYYY-MM-DD'),
    });
  };

  return (
    <Card
      size="small"
      title="Hóa đơn"
      style={{ width: 280 }}
      styles={{ body: { padding: 12 } }}
    >
      <div className="filter-block">
        <div className="filter-label">Chi nhánh</div>
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
            value={dateRange}
            onChange={handleDateRangeChange}
            format="DD/MM/YYYY"
          />
        )}
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Loại hóa đơn</div>
        <Checkbox.Group
          options={INVOICE_TYPES.map((t) => ({ label: t.label, value: t.value }))}
          value={filters.invoice_types}
          onChange={(values) => onChange({ invoice_types: values })}
          style={{ display: 'flex', flexDirection: 'column', gap: 6 }}
        />
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Trạng thái hóa đơn</div>
        <Checkbox.Group
          options={INVOICE_STATUSES.map((s) => ({ label: s.label, value: s.value }))}
          value={filters.invoice_status}
          onChange={(values) => onChange({ invoice_status: values })}
          style={{ display: 'flex', flexDirection: 'column', gap: 6 }}
        />
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Trạng thái HĐĐT</div>
        <Select
          allowClear
          style={{ width: '100%' }}
          placeholder="Chọn trạng thái"
          value={filters.e_invoice_status}
          options={E_INVOICE_STATUSES}
          onChange={(v) => onChange({ e_invoice_status: v })}
        />
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Trạng thái giao hàng</div>
        <Select
          allowClear
          style={{ width: '100%' }}
          placeholder="Chọn trạng thái giao hàng"
          value={filters.delivery_status}
          options={DELIVERY_STATUSES}
          onChange={(v) => onChange({ delivery_status: v })}
        />
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Đối tác giao hàng</div>
        <Select
          allowClear
          style={{ width: '100%' }}
          placeholder="Chọn đối tác giao hàng"
          value={filters.shipping_partner}
          options={SHIPPING_PARTNERS}
          onChange={(v) => onChange({ shipping_partner: v })}
        />
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Thời gian giao hàng</div>
        <Radio.Group
          value={deliveryTimeMode}
          onChange={(e) => {
            const mode = e.target.value;
            setDeliveryTimeMode(mode);
            onChange({ shipping_time_mode: mode, shipping_time_from: null, shipping_time_to: null });
          }}
          style={{ display: 'flex', flexDirection: 'column', gap: 8 }}
        >
          <Radio value="all">Toàn thời gian</Radio>
          <Radio value="custom">Tùy chỉnh</Radio>
        </Radio.Group>
        {deliveryTimeMode === 'custom' && (
          <RangePicker
            style={{ width: '100%', marginTop: 8 }}
            value={deliveryRange}
            onChange={handleDeliveryRangeChange}
            format="DD/MM/YYYY"
          />
        )}
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Khu vực giao hàng</div>
        <Select
          allowClear
          style={{ width: '100%' }}
          placeholder="Chọn Tỉnh/TP - Quận/Huyện"
          value={filters.region}
          options={[]}
          onChange={(v) => onChange({ region: v })}
        />
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Phương thức thanh toán</div>
        <Select
          allowClear
          style={{ width: '100%' }}
          placeholder="Chọn phương thức thanh toán"
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
          style={{ width: '100%' }}
          placeholder="Chọn người tạo"
          value={filters.created_by}
          options={[]}
          onChange={(v) => onChange({ created_by: v })}
        />
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Người bán</div>
        <Select
          allowClear
          style={{ width: '100%' }}
          placeholder="Chọn người bán"
          value={filters.seller_id}
          options={[]}
          onChange={(v) => onChange({ seller_id: v })}
        />
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Bảng giá</div>
        <Select
          allowClear
          style={{ width: '100%' }}
          placeholder="Chọn bảng giá"
          value={filters.price_book_id}
          options={[]}
          onChange={(v) => onChange({ price_book_id: v })}
        />
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">
          <Space align="center" style={{ width: '100%', justifyContent: 'space-between' }}>
            <span>Kênh bán</span>
            <Typography.Link>Tạo mới</Typography.Link>
          </Space>
        </div>
        <Select
          allowClear
          style={{ width: '100%' }}
          placeholder="Chọn kênh bán"
          value={filters.channel}
          options={SALES_CHANNELS}
          onChange={(v) => onChange({ channel: v })}
        />
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Loại thu khác</div>
        <Select
          allowClear
          style={{ width: '100%' }}
          placeholder="Chọn loại thu khác"
          value={filters.extra_revenue_type}
          options={[]}
          onChange={(v) => onChange({ extra_revenue_type: v })}
        />
      </div>
    </Card>
  );
};

export default InvoiceFilters;

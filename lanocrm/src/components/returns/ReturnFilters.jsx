import React, { useEffect, useState } from 'react';
import { Card, Checkbox, Select, Radio, DatePicker, Divider } from 'antd';
import dayjs from 'dayjs';
import { RETURN_TYPES, RETURN_STATUSES, startOfMonthIso } from '../../constants/returns';

const { RangePicker } = DatePicker;

const ReturnFilters = ({ filters, onChange, branches = [] }) => {
  const [dateMode, setDateMode] = useState('month');
  const [customRange, setCustomRange] = useState(null);

  useEffect(() => {
    if (dateMode === 'month') {
      onChange({ date_from: startOfMonthIso(), date_to: dayjs().format('YYYY-MM-DD') });
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [dateMode]);

  const handleRangeChange = (values) => {
    setCustomRange(values);
    if (!values || values.length !== 2) return;
    onChange({
      date_from: values[0]?.format('YYYY-MM-DD'),
      date_to: values[1]?.format('YYYY-MM-DD'),
    });
  };

  return (
    <Card size="small" title="Trả hàng" style={{ width: 280 }} bodyStyle={{ padding: 12 }}>
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
        <div className="filter-label">Loại trả hàng</div>
        <Checkbox.Group
          style={{ display: 'flex', flexDirection: 'column', gap: 6 }}
          value={filters.return_types}
          onChange={(vals) => onChange({ return_types: vals })}
        >
          {RETURN_TYPES.map((t) => (
            <Checkbox key={t.value} value={t.value}>{t.label}</Checkbox>
          ))}
        </Checkbox.Group>
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Trạng thái</div>
        <Checkbox.Group
          style={{ display: 'flex', flexDirection: 'column', gap: 6 }}
          value={filters.status}
          onChange={(vals) => onChange({ status: vals })}
        >
          {RETURN_STATUSES.filter((s) => ['completed', 'cancelled'].includes(s.value)).map((s) => (
            <Checkbox key={s.value} value={s.value}>{s.label}</Checkbox>
          ))}
        </Checkbox.Group>
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
        <div className="filter-label">Người nhận trả</div>
        <Select
          allowClear
          placeholder="Chọn người nhận trả"
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
          options={[]}
          onChange={(v) => onChange({ channel: v })}
        />
        <div style={{ fontSize: 12, marginTop: 4, color: '#1677ff', cursor: 'pointer' }}>Tạo mới</div>
      </div>

      <Divider style={{ margin: '12px 0' }} />

      <div className="filter-block">
        <div className="filter-label">Loại thu khác</div>
        <Select
          allowClear
          placeholder="Chọn loại thu khác"
          style={{ width: '100%' }}
          value={filters.fee_type}
          options={[]}
          onChange={(v) => onChange({ fee_type: v })}
        />
      </div>
    </Card>
  );
};

export default ReturnFilters;

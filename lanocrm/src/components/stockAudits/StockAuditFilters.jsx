import React, { useMemo } from 'react';
import { Checkbox, DatePicker, Radio, Select, Space, Typography } from 'antd';
import { STOCK_AUDIT_STATUSES } from '../../constants/stockAudits';
import styles from './StockAuditFilters.module.css';

const { RangePicker } = DatePicker;

const StockAuditFilters = ({ filters, creators = [], onChange }) => {
  const statusOptions = STOCK_AUDIT_STATUSES.map((s) => ({ label: s.label, value: s.value }));

  const creatorOptions = useMemo(
    () =>
      Array.from(new Set(creators)).map((name) => ({
        label: name,
        value: name,
      })),
    [creators]
  );

  const handleDateMode = (value) => {
    onChange?.({ dateMode: value, customFrom: null, customTo: null });
  };

  const handleDateRange = (range) => {
    const [start, end] = range || [];
    onChange?.({
      customFrom: start ? start.startOf('day').toISOString() : null,
      customTo: end ? end.endOf('day').toISOString() : null,
    });
  };

  return (
    <div className={styles.panel}>
      <Typography.Title level={5} className={styles.title}>
        Phiếu kiểm kho
      </Typography.Title>

      <div className={styles.field}>
        <label>Ngày tạo</label>
        <Radio.Group value={filters.dateMode} onChange={(e) => handleDateMode(e.target.value)}>
          <Space orientation="vertical">
            <Radio value="this_month">Tháng này</Radio>
            <Radio value="custom">Tùy chỉnh</Radio>
          </Space>
        </Radio.Group>
        <RangePicker
          format="DD/MM/YYYY"
          onChange={handleDateRange}
          disabled={filters.dateMode !== 'custom'}
          allowEmpty={[true, true]}
        />
      </div>

      <div className={styles.field}>
        <label>Trạng thái</label>
        <Checkbox.Group
          options={statusOptions}
          value={filters.statuses}
          onChange={(values) => onChange?.({ statuses: values })}
        />
      </div>

      <div className={styles.field}>
        <label>Người tạo</label>
        <Select
          mode="multiple"
          allowClear
          placeholder="Chọn người tạo"
          options={creatorOptions}
          value={filters.creators}
          onChange={(values) => onChange?.({ creators: values })}
          maxTagCount="responsive"
        />
      </div>
    </div>
  );
};

export default StockAuditFilters;

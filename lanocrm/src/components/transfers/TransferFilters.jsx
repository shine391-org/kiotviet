import React from 'react';
import { Checkbox, DatePicker, Radio, Select, Space, Tag, Button, Divider, Typography } from 'antd';
import { TRANSFER_STATUSES, RECEIVING_STATUS_OPTIONS } from '../../constants/transfers';
import styles from './TransferFilters.module.css';

const { RangePicker } = DatePicker;

const TransferFilters = ({ filters, branches = [], onChange }) => {
  const branchOptions = (branches || []).map((b) => ({
    label: b.name || b.branch_name || b.code || b.id,
    value: b.name || b.branch_name || b.code || b.id,
  }));

  const statusOptions = TRANSFER_STATUSES.map((s) => ({ label: s.label, value: s.value }));

  const handleStatusesChange = (values) => {
    onChange?.({ statuses: values });
  };

  const handleRemoveStatus = (status) => {
    const next = (filters.statuses || []).filter((s) => s !== status);
    onChange?.({ statuses: next });
  };

  const handleDateMode = (value) => {
    onChange?.({ dateMode: value });
  };

  const handleDateRange = (range) => {
    const [start, end] = range || [];
    onChange?.({
      customFrom: start ? start.startOf('day').toISOString() : null,
      customTo: end ? end.endOf('day').toISOString() : null,
    });
  };

  const handleReceivingStatus = (value) => onChange?.({ receivingStatus: value });

  return (
    <div className={styles.panel}>
      <Typography.Title level={5} className={styles.title}>
        Chuyển hàng
      </Typography.Title>

      <div className={styles.field}>
        <label>Chuyển đi</label>
        <Select
          mode="multiple"
          allowClear
          placeholder="Chọn chi nhánh"
          options={branchOptions}
          value={filters.fromBranches}
          onChange={(values) => onChange?.({ fromBranches: values })}
          maxTagCount="responsive"
        />
      </div>

      <div className={styles.field}>
        <label>Nhận về</label>
        <Select
          mode="multiple"
          allowClear
          placeholder="Chọn chi nhánh"
          options={branchOptions}
          value={filters.toBranches}
          onChange={(values) => onChange?.({ toBranches: values })}
          maxTagCount="responsive"
        />
      </div>

      <div className={styles.field}>
        <label>Trạng thái</label>
        <div className={styles.tagBox}>
          {(filters.statuses || []).map((status) => {
            const meta = TRANSFER_STATUSES.find((s) => s.value === status);
            return (
              <Tag
                key={status}
                color={meta?.color === 'default' ? undefined : meta?.color}
                closable
                onClose={() => handleRemoveStatus(status)}
              >
                {meta?.label || status}
              </Tag>
            );
          })}
        </div>
        <Select
          mode="multiple"
          placeholder="Thêm trạng thái"
          options={statusOptions}
          value={filters.statuses}
          onChange={handleStatusesChange}
          popupMatchSelectWidth
          maxTagCount={0}            // ẩn tag mặc định để tránh trùng lặp với tagBox
          tagRender={() => null}
          style={{ width: '100%' }}
        />
      </div>

      <Divider className={styles.divider} />

      <div className={styles.field}>
        <label>Thời gian</label>
        <Space orientation="vertical" size={8}>
          <Checkbox
            checked={filters.transferDateEnabled}
            onChange={(e) => onChange?.({ transferDateEnabled: e.target.checked })}
          >
            Ngày chuyển
          </Checkbox>
          <Checkbox
            checked={filters.receiveDateEnabled}
            onChange={(e) => onChange?.({ receiveDateEnabled: e.target.checked })}
          >
            Ngày nhận
          </Checkbox>

          <Radio.Group
            value={filters.dateMode}
            onChange={(e) => handleDateMode(e.target.value)}
          >
            <Space orientation="vertical">
              <Radio value="this_year">Năm nay</Radio>
              <Radio value="custom">Tùy chỉnh</Radio>
            </Space>
          </Radio.Group>
          <RangePicker
            format="DD/MM/YYYY"
            onChange={handleDateRange}
            disabled={filters.dateMode !== 'custom'}
            allowEmpty={[true, true]}
          />
        </Space>
      </div>

      <Divider className={styles.divider} />

      <div className={styles.field}>
        <label>Tình trạng nhận hàng</label>
        <Radio.Group
          value={filters.receivingStatus}
          onChange={(e) => handleReceivingStatus(e.target.value)}
        >
          <Space orientation="vertical">
            {RECEIVING_STATUS_OPTIONS.map((opt) => (
              <Radio key={opt.value} value={opt.value}>
                {opt.label}
              </Radio>
            ))}
          </Space>
        </Radio.Group>

        <Space className={styles.quickButtons}>
          <Button
            type={filters.receivingStatus === 'all' ? 'primary' : 'default'}
            onClick={() => handleReceivingStatus('all')}
          >
            Tất cả
          </Button>
          <Button
            type={filters.receivingStatus === 'mismatch' ? 'primary' : 'default'}
            onClick={() => handleReceivingStatus('mismatch')}
          >
            Không khớp
          </Button>
          <Button
            type={filters.receivingStatus === 'matched' ? 'primary' : 'default'}
            onClick={() => handleReceivingStatus('matched')}
          >
            Khớp
          </Button>
        </Space>
      </div>
    </div>
  );
};

export default TransferFilters;

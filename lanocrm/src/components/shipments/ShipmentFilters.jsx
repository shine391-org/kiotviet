import React from 'react';
import { Card, Select, Radio, DatePicker, Space, Typography, Cascader } from 'antd';
import dayjs from 'dayjs';
import {
  SHIPMENT_STATUSES,
  DELIVERY_PARTNERS,
  DELIVERY_AREAS,
  COD_FILTERS,
} from '../../constants/shipments';

const { RangePicker } = DatePicker;

const ShipmentFilters = ({ filters, onChange, branches = [] }) => {
  const handleCreatedPreset = (mode) => {
    if (mode === 'month') {
      onChange({
        created_mode: mode,
        created_from: dayjs().startOf('month').format('YYYY-MM-DD'),
        created_to: dayjs().format('YYYY-MM-DD'),
      });
    } else {
      onChange({
        created_mode: mode,
        created_from: null,
        created_to: null,
      });
    }
  };

  const handleCompletedPreset = (mode) => {
    if (mode === 'all') {
      onChange({
        completed_mode: mode,
        completed_from: null,
        completed_to: null,
      });
    } else {
      onChange({
        completed_mode: mode,
        completed_from: dayjs().startOf('month').format('YYYY-MM-DD'),
        completed_to: dayjs().format('YYYY-MM-DD'),
      });
    }
  };

  const areaValue = (filters.areas || []).map((key) => key.split('/'));

  return (
    <Card size="small" title="Vận đơn" styles={{ body: { padding: 12 } }}>
      <Space direction="vertical" size={12} style={{ width: '100%' }}>
        <div>
          <Typography.Text strong>Chi nhánh</Typography.Text>
          <Select
            mode="multiple"
            allowClear
            placeholder="Chọn chi nhánh"
            value={filters.branches || (filters.branch ? [filters.branch] : [])}
            options={(branches || []).map((b) => ({ label: b.name, value: b.name }))}
            style={{ width: '100%', marginTop: 4 }}
            onChange={(val) =>
              onChange({
                branches: val,
                branch: val?.[0] || null,
              })
            }
          />
        </div>

        <div>
          <Typography.Text strong>Trạng thái giao hàng</Typography.Text>
          <Select
            mode="multiple"
            allowClear
            placeholder="Chọn trạng thái"
            value={filters.statuses}
            options={SHIPMENT_STATUSES.map((s) => ({ label: s.label, value: s.value }))}
            style={{ width: '100%', marginTop: 4 }}
            onChange={(val) => onChange({ statuses: val })}
          />
        </div>

        <div>
          <Typography.Text strong>Đối tác giao hàng</Typography.Text>
          <Select
            mode="multiple"
            allowClear
            placeholder="Chọn đối tác giao hàng"
            value={filters.partners}
            options={DELIVERY_PARTNERS}
            style={{ width: '100%', marginTop: 4 }}
            onChange={(val) => onChange({ partners: val })}
          />
        </div>

        <div>
          <Typography.Text strong>Thời gian tạo</Typography.Text>
          <div style={{ marginTop: 4 }}>
            <Radio.Group
              value={filters.created_mode || 'month'}
              onChange={(e) => handleCreatedPreset(e.target.value)}
            >
              <Space direction="vertical">
                <Radio value="month">Tháng này</Radio>
                <Radio value="custom">Tùy chỉnh</Radio>
              </Space>
            </Radio.Group>
            {filters.created_mode === 'custom' && (
              <RangePicker
                style={{ width: '100%', marginTop: 8 }}
                value={
                  filters.created_from && filters.created_to
                    ? [dayjs(filters.created_from), dayjs(filters.created_to)]
                    : null
                }
                onChange={(range) => {
                  onChange({
                    created_from: range?.[0]?.format('YYYY-MM-DD') || null,
                    created_to: range?.[1]?.format('YYYY-MM-DD') || null,
                  });
                }}
              />
            )}
          </div>
        </div>

        <div>
          <Typography.Text strong>Thời gian hoàn thành</Typography.Text>
          <div style={{ marginTop: 4 }}>
            <Radio.Group
              value={filters.completed_mode || 'all'}
              onChange={(e) => handleCompletedPreset(e.target.value)}
            >
              <Space direction="vertical">
                <Radio value="all">Toàn thời gian</Radio>
                <Radio value="custom">Tùy chỉnh</Radio>
              </Space>
            </Radio.Group>
            {filters.completed_mode === 'custom' && (
              <RangePicker
                style={{ width: '100%', marginTop: 8 }}
                value={
                  filters.completed_from && filters.completed_to
                    ? [dayjs(filters.completed_from), dayjs(filters.completed_to)]
                    : null
                }
                onChange={(range) => {
                  onChange({
                    completed_from: range?.[0]?.format('YYYY-MM-DD') || null,
                    completed_to: range?.[1]?.format('YYYY-MM-DD') || null,
                  });
                }}
              />
            )}
          </div>
        </div>

        <div>
          <Typography.Text strong>Khu vực giao hàng</Typography.Text>
          <Cascader
            allowClear
            multiple
            maxTagCount="responsive"
            style={{ width: '100%', marginTop: 4 }}
            options={DELIVERY_AREAS}
            placeholder="Chọn Tỉnh/TP - Quận/Huyện"
            value={areaValue}
            onChange={(val) => {
              const normalized = (val || []).map((arr) => arr.join('/'));
              onChange({ areas: normalized });
            }}
          />
        </div>

        <div>
          <Typography.Text strong>Thu hộ tiền (COD)</Typography.Text>
          <Radio.Group
            style={{ marginTop: 4 }}
            optionType="button"
            buttonStyle="solid"
            value={filters.cod || 'all'}
            options={COD_FILTERS}
            onChange={(e) => onChange({ cod: e.target.value })}
          />
        </div>
      </Space>
    </Card>
  );
};

export default ShipmentFilters;

import React from 'react';
import { Card, Select, Radio, DatePicker, Space, Typography, Cascader, Tag, Divider } from 'antd';
import { CloseOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import {
  SHIPMENT_STATUSES,
  DELIVERY_PARTNERS,
  DELIVERY_AREAS,
  COD_FILTERS,
} from '../../constants/shipments';

const { RangePicker } = DatePicker;
const { Text } = Typography;

const ShipmentFilters = ({ filters, onChange, branches = [] }) => {
  const handleCreatedPreset = (mode) => {
    if (mode === 'today') {
      onChange({
        created_mode: mode,
        created_from: dayjs().format('YYYY-MM-DD'),
        created_to: dayjs().format('YYYY-MM-DD'),
      });
    } else if (mode === 'custom') {
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
    } else if (mode === 'custom') {
      onChange({
        completed_mode: mode,
        completed_from: dayjs().startOf('month').format('YYYY-MM-DD'),
        completed_to: dayjs().format('YYYY-MM-DD'),
      });
    }
  };

  const areaValue = (filters.areas || []).map((key) => key.split('/'));

  const selectedBranches = filters.branches || (filters.branch ? [filters.branch] : []);

  const handleRemoveBranch = (branchToRemove) => {
    const newBranches = selectedBranches.filter(b => b !== branchToRemove);
    onChange({
      branches: newBranches,
      branch: newBranches[0] || null,
    });
  };

  return (
    <Card
      size="small"
      styles={{ body: { padding: 12 } }}
      style={{ width: 260 }}
    >
      <Text strong style={{ fontSize: 16, marginBottom: 12, display: 'block' }}>Vận đơn</Text>

      <Space direction="vertical" size={12} style={{ width: '100%' }}>
        {/* Branch filter */}
        <div>
          <Text type="secondary" style={{ fontSize: 12 }}>Chi nhánh</Text>
          {selectedBranches.length > 0 && (
            <div style={{ marginTop: 4, marginBottom: 8 }}>
              {selectedBranches.map((branch) => (
                <Tag
                  key={branch}
                  color="blue"
                  closable
                  onClose={() => handleRemoveBranch(branch)}
                  style={{ marginBottom: 4 }}
                >
                  {branch}
                </Tag>
              ))}
            </div>
          )}
          <Select
            mode="multiple"
            allowClear
            placeholder="Chọn chi nhánh"
            value={selectedBranches}
            options={(branches || []).map((b) => ({ label: b.name, value: b.name }))}
            style={{ width: '100%' }}
            onChange={(val) =>
              onChange({
                branches: val,
                branch: val?.[0] || null,
              })
            }
            tagRender={() => null}
            maxTagCount={0}
          />
        </div>

        <Divider style={{ margin: '8px 0' }} />

        {/* Delivery status */}
        <div>
          <Text type="secondary" style={{ fontSize: 12 }}>Trạng thái giao hàng</Text>
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

        <Divider style={{ margin: '8px 0' }} />

        {/* Delivery partner */}
        <div>
          <Text type="secondary" style={{ fontSize: 12 }}>Đối tác giao hàng</Text>
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

        <Divider style={{ margin: '8px 0' }} />

        {/* Created time */}
        <div>
          <Text type="secondary" style={{ fontSize: 12 }}>Thời gian tạo</Text>
          <div style={{ marginTop: 4 }}>
            <Radio.Group
              value={filters.created_mode || 'today'}
              onChange={(e) => handleCreatedPreset(e.target.value)}
            >
              <Space direction="vertical">
                <Radio value="today">Hôm nay</Radio>
                <Radio value="custom">Tự chọn</Radio>
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

        <Divider style={{ margin: '8px 0' }} />

        {/* Completed time */}
        <div>
          <Text type="secondary" style={{ fontSize: 12 }}>Thời gian hoàn thành</Text>
          <div style={{ marginTop: 4 }}>
            <Radio.Group
              value={filters.completed_mode || 'all'}
              onChange={(e) => handleCompletedPreset(e.target.value)}
            >
              <Space direction="vertical">
                <Radio value="all">Toàn thời gian</Radio>
                <Radio value="custom">Tự chọn</Radio>
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

        <Divider style={{ margin: '8px 0' }} />

        {/* Delivery area */}
        <div>
          <Text type="secondary" style={{ fontSize: 12 }}>Khu vực giao hàng</Text>
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

        <Divider style={{ margin: '8px 0' }} />

        {/* COD filter */}
        <div>
          <Text type="secondary" style={{ fontSize: 12 }}>Thu hộ tiền (COD)</Text>
          <div style={{ marginTop: 4 }}>
            <Radio.Group
              optionType="button"
              buttonStyle="solid"
              value={filters.cod || 'all'}
              options={COD_FILTERS}
              onChange={(e) => onChange({ cod: e.target.value })}
            />
          </div>
        </div>
      </Space>
    </Card>
  );
};

export default ShipmentFilters;

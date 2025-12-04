import React from 'react';
import { Input, Button, Space, Tooltip, Dropdown } from 'antd';
import {
  SearchOutlined,
  FilterOutlined,
  PlusOutlined,
  DownloadOutlined,
  AppstoreOutlined,
  SettingOutlined,
  QuestionCircleOutlined,
} from '@ant-design/icons';
import styles from './StockAuditToolbar.module.css';

const StockAuditToolbar = ({
  searchText,
  onSearchChange,
  onToggleFilters,
  onCreate,
  onExport,
  columnMenu,
  onSettings,
  onHelp,
}) => {
  return (
    <div className={styles.toolbar}>
      <Input
        allowClear
        prefix={<SearchOutlined />}
        suffix={
          <Tooltip title="Bộ lọc nâng cao">
            <FilterOutlined onClick={onToggleFilters} />
          </Tooltip>
        }
        placeholder="Theo mã phiếu kiểm"
        value={searchText}
        onChange={(e) => onSearchChange?.(e.target.value)}
        className={styles.search}
      />

      <Space size={8} wrap>
        <Button type="primary" icon={<PlusOutlined />} onClick={onCreate}>
          Kiểm kho
        </Button>
        <Button icon={<DownloadOutlined />} onClick={onExport}>
          Xuất file
        </Button>
        <Dropdown menu={columnMenu} trigger={['click']}>
          <Button icon={<AppstoreOutlined />}>Ẩn hiện cột</Button>
        </Dropdown>
        <Button icon={<SettingOutlined />} onClick={onSettings} />
        <Button icon={<QuestionCircleOutlined />} onClick={onHelp} />
      </Space>
    </div>
  );
};

export default StockAuditToolbar;

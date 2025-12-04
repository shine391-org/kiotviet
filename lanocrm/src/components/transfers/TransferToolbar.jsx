import React from 'react';
import { Input, Button, Space, Tooltip, Dropdown } from 'antd';
import {
  SearchOutlined,
  FilterOutlined,
  PlusOutlined,
  UploadOutlined,
  DownloadOutlined,
  AppstoreOutlined,
  SettingOutlined,
  QuestionCircleOutlined,
} from '@ant-design/icons';
import styles from './TransferToolbar.module.css';

const TransferToolbar = ({
  searchText,
  onSearchChange,
  onToggleFilters,
  onCreate,
  onImport,
  onExport,
  columnMenu,
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
        placeholder="Theo mã phiếu chuyển"
        value={searchText}
        onChange={(e) => onSearchChange?.(e.target.value)}
        className={styles.search}
      />

      <Space size={8} wrap>
        <Button type="primary" icon={<PlusOutlined />} onClick={onCreate}>
          Chuyển hàng
        </Button>
        <Button icon={<UploadOutlined />} onClick={onImport}>
          Import file
        </Button>
        <Button icon={<DownloadOutlined />} onClick={onExport}>
          Xuất file
        </Button>
        <Dropdown menu={columnMenu} trigger={['click']}>
          <Button icon={<AppstoreOutlined />}>Ẩn hiện cột</Button>
        </Dropdown>
        <Button icon={<SettingOutlined />} />
        <Button icon={<QuestionCircleOutlined />} />
      </Space>
    </div>
  );
};

export default TransferToolbar;

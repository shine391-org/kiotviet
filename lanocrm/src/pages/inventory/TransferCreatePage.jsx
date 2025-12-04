import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Button, Input, Space, Tooltip, Typography, Select } from 'antd';
import {
  ArrowLeftOutlined,
  SearchOutlined,
  AppstoreOutlined,
  PrinterOutlined,
  EyeOutlined,
  ExclamationOutlined,
} from '@ant-design/icons';
import styles from './TransferCreatePage.module.css';

const { Title, Text, Link } = Typography;

const TransferCreatePage = () => {
  const navigate = useNavigate();

  const goBack = () => navigate('/inventory/transfer');

  return (
    <div className={styles.page}>
      <div className={styles.topBar}>
        <Space size={12} align="center">
          <Button type="text" icon={<ArrowLeftOutlined />} aria-label="arrow-left" onClick={goBack} />
          <Title level={5} className={styles.title}>
            Chuyển hàng
          </Title>
        </Space>

        <Input
          allowClear
          prefix={<SearchOutlined />}
          placeholder="Tìm hàng hóa theo mã hoặc tên (F3)"
          className={styles.search}
        />

        <Space size={8}>
          <Tooltip title="Ẩn/hiện cột">
            <Button icon={<AppstoreOutlined />} />
          </Tooltip>
          <Tooltip title="In phiếu">
            <Button icon={<PrinterOutlined />} />
          </Tooltip>
          <Tooltip title="Xem trước">
            <Button icon={<EyeOutlined />} />
          </Tooltip>
          <Tooltip title="Cảnh báo">
            <Button icon={<ExclamationOutlined />} />
          </Tooltip>
        </Space>
      </div>

      <div className={styles.layout}>
        <div className={styles.tableCard}>
          <div className={styles.tableHeader}>
            <Text strong>Danh sách sản phẩm</Text>
          </div>
          <div className={styles.tableEmpty}>
            <div className={styles.tableEmptyInner}>
              <Text strong>Thêm sản phẩm từ file excel</Text>
              <Text type="secondary">(Tải về file mẫu: <Link>Excel file</Link>)</Text>
              <Button type="primary" size="large" className={styles.uploadBtn}>
                Chọn file dữ liệu
              </Button>
            </div>
          </div>
        </div>

        <div className={styles.sidebar}>
          <div className={styles.sideSection}>
            <div className={styles.sideRow}>
              <Text strong>Trung</Text>
              <Input value="28/11/2025 11:22" disabled />
            </div>
            <div className={styles.sideRow}>
              <Text>Mã chuyển hàng</Text>
              <Input placeholder="Mã phiếu tự động" disabled />
            </div>
            <div className={styles.sideRow}>
              <Text>Trạng thái</Text>
              <Text>Phiếu tạm</Text>
            </div>
            <div className={styles.sideRow}>
              <Text>Tổng số lượng</Text>
              <Text>0</Text>
            </div>
            <div className={styles.sideRow}>
              <Text>Chuyển tới</Text>
              <Select placeholder="Chọn chi nhánh" style={{ width: '100%' }} />
            </div>
            <div className={styles.sideRow}>
              <Text>Ghi chú</Text>
              <Input.TextArea autoSize={{ minRows: 3 }} placeholder="Ghi chú" />
            </div>
          </div>

          <div className={styles.actionRow}>
            <Button block size="large" icon={null} className={styles.draftBtn}>
              Lưu tạm
            </Button>
            <Button block size="large" type="primary" className={styles.submitBtn}>
              Hoàn thành
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default TransferCreatePage;

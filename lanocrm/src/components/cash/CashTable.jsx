import React, { useMemo, useState, useCallback } from 'react';
import { Table, Tag, Dropdown, Checkbox, Space, Button, Tooltip, Descriptions, Typography, Divider, Statistic, Row, Col } from 'antd';
import { ReloadOutlined, SettingOutlined, DeleteOutlined, ExportOutlined, InfoCircleOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { formatCurrency, formatDate, formatDateOnly } from '../../utils/formatters';
import { formatTransactionCode } from '../../constants/cash';
import styles from '../../pages/cash/CashBookPage.module.css';

const statusColors = {
  approved: 'green',
  pending: 'orange',
  cancelled: 'red',
};

const paymentMethodMap = {
  cash: 'Tiền mặt',
  bank: 'Ngân hàng',
  bank_transfer: 'Chuyển khoản',
  ewallet: 'Ví điện tử',
};

const CashTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  onDelete,
  onRefresh,
  onExport,
  branchesMap = {},
  summaryLimited,
  pageTotals,
}) => {
  const [selectedRowKeys, setSelectedRowKeys] = useState([]);
  const [expandedRowKeys, setExpandedRowKeys] = useState([]);
  const [visibleCols, setVisibleCols] = useState({
    code: true,
    transaction_date: true,
    created_at: true,
    created_by_name: true,
    staff_name: true,
    branch: true,
    category: true,
    payment_method: true,
    account_name: true,
    bank_account: false,
    amount: true,
    status: true,
  });

  const toggleColumn = useCallback((key, checked) => {
    setVisibleCols((prev) => ({ ...prev, [key]: checked }));
  }, []);

  const baseColumns = useMemo(() => [
      {
        key: 'code',
        title: 'Mã phiếu',
        dataIndex: 'id',
        render: (_, record) => (
          <Space direction="vertical" size={0}>
            <Typography.Text strong>{formatTransactionCode(record)}</Typography.Text>
            <Tag color={record.type === 'RECEIPT' ? 'green' : 'red'}>
              {record.type === 'RECEIPT' ? 'Thu' : 'Chi'}
            </Tag>
          </Space>
        ),
      },
      {
        key: 'transaction_date',
        title: 'Thời gian',
        dataIndex: 'transaction_date',
        render: (value) => (value ? dayjs(value).format('DD/MM/YYYY') : '—'),
      },
      {
        key: 'created_at',
        title: 'Thời gian tạo',
        dataIndex: 'created_at',
        render: (v) => (v ? formatDate(v) : '—'),
      },
      {
        key: 'created_by_name',
        title: 'Người tạo',
        dataIndex: 'created_by_name',
        render: (v) => v || '—',
      },
      {
        key: 'staff_name',
        title: 'Nhân viên',
        dataIndex: 'staff_name',
        render: (v) => v || '—',
      },
      {
        key: 'branch',
        title: 'Chi nhánh',
        dataIndex: 'branch_id',
        render: (v) => branchesMap[v] || `#${v || '—'}`,
      },
      {
        key: 'category',
        title: 'Loại thu/chi',
        dataIndex: 'category',
        render: (v) => v || '—',
      },
      {
        key: 'payment_method',
        title: 'Loại sổ quỹ',
        dataIndex: 'payment_method',
        render: (v) => (
          <Tag color="blue">{paymentMethodMap[v] || v || '—'}</Tag>
        ),
      },
      {
        key: 'account_name',
        title: 'Tên tài khoản',
        dataIndex: 'account_name',
        render: (v) => v || '—',
      },
      {
        key: 'bank_account',
        title: 'Số tài khoản',
        dataIndex: 'bank_account',
        render: (v) => v || '—',
      },
      {
        key: 'amount',
        title: 'Giá trị',
        dataIndex: 'amount',
        align: 'right',
        render: (_, record) => (
          <span className={record.type === 'RECEIPT' ? styles.amountReceipt : styles.amountPayment}>
            {record.type === 'RECEIPT' ? '+' : '-'}{formatCurrency(record.amount)}đ
          </span>
        ),
      },
      {
        key: 'status',
        title: 'Trạng thái',
        dataIndex: 'status',
        render: (v) => <Tag color={statusColors[v] || 'default'}>{v || '—'}</Tag>,
      },
      {
        key: 'actions',
        title: '',
        render: (_, record) => (
          <Space>
            <Tooltip title="Hủy (soft delete)">
              <Button
                size="small"
                danger
                type="text"
                icon={<DeleteOutlined />}
                onClick={() => onDelete(record)}
              />
            </Tooltip>
          </Space>
        ),
      },
    ],
  [branchesMap, onDelete]);

  const columns = useMemo(
    () => baseColumns.filter((col) => col.key === 'actions' || visibleCols[col.key]),
    [baseColumns, visibleCols]
  );

  const columnMenuItems = useMemo(() => (
    Object.keys(visibleCols).map((key) => {
      const title = baseColumns.find((c) => c.key === key)?.title || key;
      return {
        key,
        label: (
          <Checkbox
            checked={visibleCols[key]}
            onChange={(e) => toggleColumn(key, e.target.checked)}
          >
            {title}
          </Checkbox>
        ),
      };
    })
  ), [visibleCols, baseColumns, toggleColumn]);

  const expandedRowRender = (record) => (
    <div style={{ padding: '8px 6px 4px' }}>
      <Descriptions bordered size="small" column={2}>
        <Descriptions.Item label="Người nộp/nhận">{record.payer_name || '—'}</Descriptions.Item>
        <Descriptions.Item label="SĐT">{record.payer_phone || '—'}</Descriptions.Item>
        <Descriptions.Item label="Địa chỉ">{record.payer_address || '—'}</Descriptions.Item>
        <Descriptions.Item label="Mô tả">{record.description || '—'}</Descriptions.Item>
        <Descriptions.Item label="Ghi chú" span={2}>{record.note || <span className={styles.emptyNote}>Chưa có ghi chú</span>}</Descriptions.Item>
        <Descriptions.Item label="Tham chiếu" span={2}>
          {record.reference_type ? `${record.reference_type} #${record.reference_code || record.reference_id || ''}` : '—'}
        </Descriptions.Item>
        <Descriptions.Item label="Ngân hàng / Nội dung CK" span={2}>
          {record.transfer_note || record.bank_account || '—'}
        </Descriptions.Item>
      </Descriptions>
    </div>
  );

  return (
    <div className={styles.tableCard}>
      <div className={styles.tableHeader}>
        <Space>
          <Button icon={<ReloadOutlined />} onClick={onRefresh}>Làm mới</Button>
          <Dropdown menu={{ items: columnMenuItems }} trigger={['click']} placement="bottomLeft">
            <Button icon={<SettingOutlined />}>Cột hiển thị</Button>
          </Dropdown>
        </Space>
        <Space>
          {summaryLimited && (
            <Tooltip title="Tổng thu/chi chỉ tính trên 500 bản ghi đầu">
              <Tag color="blue">Tổng ≈</Tag>
            </Tooltip>
          )}
          <Button icon={<ExportOutlined />} onClick={onExport}>Xuất CSV</Button>
        </Space>
      </div>

      <Table
        size="middle"
        rowKey="id"
        dataSource={data}
        columns={columns}
        loading={loading}
        expandable={{
          expandedRowKeys,
          onExpandedRowsChange: setExpandedRowKeys,
          expandedRowRender,
        }}
        rowSelection={{
          selectedRowKeys,
          onChange: setSelectedRowKeys,
        }}
        pagination={{
          current: pagination.page,
          pageSize: pagination.limit,
          total: pagination.total,
          showSizeChanger: true,
          pageSizeOptions: ['10', '20', '50', '100'],
        }}
        onChange={(paginationInfo) =>
          onPageChange({
            page: paginationInfo.current,
            limit: paginationInfo.pageSize,
          })
        }
      />

      <Divider style={{ margin: '8px 0 12px' }} />
      <Row gutter={12}>
        <Col span={6}>
          <Statistic
            title="Tổng thu (trang)"
            value={pageTotals.receipt || 0}
            precision={0}
            valueStyle={{ color: '#047857' }}
            suffix="đ"
          />
        </Col>
        <Col span={6}>
          <Statistic
            title="Tổng chi (trang)"
            value={pageTotals.payment || 0}
            precision={0}
            valueStyle={{ color: '#b91c1c' }}
            suffix="đ"
          />
        </Col>
        <Col span={12}>
          <Space>
            <InfoCircleOutlined />
            <span className={styles.warningText}>Số liệu trang dựa trên dữ liệu phân trang hiện tại.</span>
          </Space>
        </Col>
      </Row>
    </div>
  );
};

export default CashTable;

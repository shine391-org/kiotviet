import React, { useMemo, useState } from 'react';
import { Typography, Tag, Space, Divider, Table, Input, Button, Flex } from 'antd';
import { formatDateTime, formatCurrency } from '../../constants/transfers';
import styles from './TransferDetailPanel.module.css';

const { Title, Text, Link } = Typography;

const columns = [
  {
    title: 'Mã hàng',
    dataIndex: 'productCode',
    width: 160,
    render: (v) => <Link>{v}</Link>,
  },
  { title: 'Tên hàng', dataIndex: 'productName', ellipsis: true },
  {
    title: 'Số lượng chuyển',
    dataIndex: 'quantitySent',
    width: 140,
    align: 'right',
    render: (v) => Number(v || 0).toLocaleString('vi-VN'),
  },
  {
    title: 'Giá chuyển/nhận',
    dataIndex: 'unitPrice',
    width: 150,
    align: 'right',
    render: (v) => formatCurrency(Number(v || 0)),
  },
  {
    title: 'Thành tiền chuyển',
    dataIndex: 'totalPrice',
    width: 160,
    align: 'right',
    render: (v) => formatCurrency(Number(v || 0)),
  },
];

const TransferDetailPanel = ({
  transfer,
  onDuplicate,
  onOpen,
  onSaveNotes,
  onExport,
  onPrintLabel,
  onPrint,
  onCancel,
}) => {
  const [codeFilter, setCodeFilter] = useState('');
  const [nameFilter, setNameFilter] = useState('');
  const [notes, setNotes] = useState(transfer?.receivingNotes || transfer?.notes || '');

  const items = transfer?.items || [];
  const filteredItems = useMemo(() => {
    return items.filter((item) => {
      const codeOk = codeFilter
        ? item.productCode?.toLowerCase().includes(codeFilter.toLowerCase())
        : true;
      const nameOk = nameFilter
        ? item.productName?.toLowerCase().includes(nameFilter.toLowerCase())
        : true;
      return codeOk && nameOk;
    });
  }, [items, codeFilter, nameFilter]);

  const summary = transfer?.summary || {};

  return (
    <div className={styles.panel}>
      <div className={styles.header}>
        <Space size={10} align="center" wrap>
          <Title level={5} className={styles.code}>
            {transfer?.code}
          </Title>
          <Tag color={transfer?.status === 'in_transit' ? 'blue' : transfer?.status === 'received' ? 'green' : 'default'}>
            {transfer?.status === 'draft'
              ? 'Phiếu tạm'
              : transfer?.status === 'in_transit'
              ? 'Đang chuyển'
              : 'Đã nhận'}
          </Tag>
          <Link>Sàng chuyển</Link>
        </Space>
        <Text type="secondary">Người tạo: {transfer?.creatorName || '—'}</Text>
      </div>

      <div className={styles.meta}>
        <div>
          <Text strong>Chuyển từ:</Text> <Text>{transfer?.fromBranch}</Text>
          <div className={styles.metaTime}>{formatDateTime(transfer?.transferDate)}</div>
        </div>
        <div>
          <Text strong>Chuyển đến:</Text> <Text>{transfer?.toBranch}</Text>
        </div>
      </div>

      <Divider className={styles.divider} />

      <Space className={styles.searchRow} size={10} wrap>
        <Input
          placeholder="Tìm mã hàng"
          value={codeFilter}
          onChange={(e) => setCodeFilter(e.target.value)}
          style={{ maxWidth: 220 }}
        />
        <Input
          placeholder="Tìm tên hàng"
          value={nameFilter}
          onChange={(e) => setNameFilter(e.target.value)}
          style={{ minWidth: 260 }}
        />
      </Space>

      <Table
        rowKey={(r) => `${r.productCode}-${r.productName}`}
        dataSource={filteredItems}
        columns={columns}
        pagination={false}
        size="small"
      />

      <div className={styles.notesRow}>
        <Space align="start" size={12}>
          <Text type="secondary">Chưa có ghi chú</Text>
        </Space>
        <Input.TextArea
          placeholder="Ghi chú nhận"
          autoSize={{ minRows: 3 }}
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
          maxLength={500}
        />
      </div>

      <div className={styles.summaryBox}>
        <div>
          <Text type="secondary">Tổng số mặt hàng:</Text>
          <Text strong>{summary.totalItems || 0}</Text>
        </div>
        <div>
          <Text type="secondary">Tổng SL chuyển:</Text>
          <Text strong>{summary.totalQtySent || 0}</Text>
        </div>
        <div>
          <Text type="secondary">Tổng giá trị chuyển:</Text>
          <Text strong>{formatCurrency(summary.totalValueSent || 0)}</Text>
        </div>
      </div>

      <Flex gap="8px" justify="space-between" wrap>
        <Space size={8} wrap>
          <Button onClick={() => onCancel?.(transfer?.code)} danger ghost>
            Hủy
          </Button>
          <Button onClick={() => onDuplicate?.(transfer?.code)}>Sao chép</Button>
          <Button onClick={() => onExport?.(transfer?.code)}>Xuất file</Button>
          <Button onClick={() => onPrintLabel?.(transfer?.code)}>In tem mã</Button>
        </Space>
        <Space size={8} wrap>
          <Button type="primary" onClick={() => onOpen?.(transfer?.code)}>
            Mở phiếu
          </Button>
          <Button onClick={() => onPrint?.(transfer?.code)}>In</Button>
          <Button
            type="default"
            onClick={() => onSaveNotes?.(transfer?.code, notes)}
            disabled={!transfer}
          >
            Lưu
          </Button>
        </Space>
      </Flex>
    </div>
  );
};

export default TransferDetailPanel;

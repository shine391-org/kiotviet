import React from 'react';
import { Skeleton, Tag, Tooltip, Space } from 'antd';
import { InfoCircleOutlined } from '@ant-design/icons';
import { formatCurrency } from '../../utils/formatters';
import styles from '../../pages/cash/CashBookPage.module.css';

const SummaryCard = ({ title, value, sub, color, loading, badge }) => (
  <div className={styles.summaryCard}>
    <div className={styles.summaryTitle}>
      <span>{title}</span>
      {badge}
    </div>
    {loading ? (
      <Skeleton active paragraph={false} title={{ width: 80 }} />
    ) : (
      <>
        <div className={styles.summaryValue} style={{ color }}>{formatCurrency(value)} đ</div>
        {sub && <div className={styles.summarySub}>{sub}</div>}
      </>
    )}
  </div>
);

const CashSummary = ({ summary, balance, loading, summaryLimited }) => {
  const { receipt = 0, payment = 0, net = 0, openingBalance } = summary;

  return (
    <div className={styles.summaryGrid}>
      <SummaryCard
        title="Quỹ đầu kỳ"
        value={openingBalance ?? 0}
        sub={openingBalance === null ? 'Chưa có dữ liệu' : 'Số dư từ kỳ trước'}
        color="#0f172a"
        loading={loading}
      />
      <SummaryCard
        title="Tổng thu"
        value={receipt}
        sub="Tính theo bộ lọc hiện tại"
        color="#047857"
        loading={loading}
        badge={summaryLimited && (
          <Tooltip title="Giới hạn 500 bản ghi đầu tiên để tính tổng">
            <Tag color="blue" style={{ marginLeft: 6 }}>≈ Ước tính</Tag>
          </Tooltip>
        )}
      />
      <SummaryCard
        title="Tổng chi"
        value={payment}
        sub="Tính theo bộ lọc hiện tại"
        color="#b91c1c"
        loading={loading}
        badge={summaryLimited && (
          <Tooltip title="Giới hạn 500 bản ghi đầu tiên để tính tổng">
            <Tag color="blue" style={{ marginLeft: 6 }}>≈ Ước tính</Tag>
          </Tooltip>
        )}
      />
      <SummaryCard
        title="Tổng quỹ (tạm tính)"
        value={(openingBalance ?? 0) + net}
        sub={
          <Space size={4}>
            <span>Quỹ đầu kỳ ± (Thu - Chi)</span>
            <Tooltip title="Nếu backend chưa cung cấp số dư đầu kỳ chính xác, số liệu này chỉ mang tính tham khảo.">
              <InfoCircleOutlined />
            </Tooltip>
          </Space>
        }
        color={net >= 0 ? '#047857' : '#b91c1c'}
        loading={loading}
      />
    </div>
  );
};

export default CashSummary;

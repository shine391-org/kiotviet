import React from 'react';
import { Card, Statistic, Row, Col } from 'antd';

const formatNumber = (value) => (Number(value || 0)).toLocaleString('vi-VN');

const DisposalSummary = ({ totals }) => (
  <Card size="small" style={{ marginBottom: 12 }}>
    <Row gutter={16}>
      <Col span={12}>
        <Statistic title="Tổng giá trị hủy" value={formatNumber(totals.total_value)} suffix="đ" />
      </Col>
      <Col span={12}>
        <Statistic title="Tổng số lượng hủy" value={formatNumber(totals.total_quantity)} />
      </Col>
    </Row>
  </Card>
);

export default DisposalSummary;

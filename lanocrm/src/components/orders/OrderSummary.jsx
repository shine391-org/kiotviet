import React from 'react';
import { Card, Statistic, Row, Col } from 'antd';

const currency = (value) => (Number(value || 0)).toLocaleString('vi-VN');

const OrderSummary = ({ totals }) => (
  <Card size="small" style={{ marginBottom: 12 }}>
    <Row gutter={16}>
      <Col span={8}>
        <Statistic title="Tổng tiền hàng" value={currency(totals.total_amount)} suffix="đ" />
      </Col>
      <Col span={8}>
        <Statistic title="Khách đã trả" value={currency(totals.paid_amount)} suffix="đ" />
      </Col>
      <Col span={8}>
        <Statistic title="Khách còn trả" value={currency(totals.debt_amount)} suffix="đ" />
      </Col>
    </Row>
  </Card>
);

export default OrderSummary;

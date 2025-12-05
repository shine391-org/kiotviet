import React from 'react';
import { Card, Statistic, Row, Col } from 'antd';

const currency = (value) => (Number(value || 0)).toLocaleString('vi-VN');

const ReturnSummary = ({ totals }) => (
  <Card size="small" style={{ marginBottom: 12 }}>
    <Row gutter={16}>
      <Col span={8}>
        <Statistic title="Tổng tiền hàng trả" value={currency(totals.goods_total)} suffix="đ" />
      </Col>
      <Col span={8}>
        <Statistic title="Cần trả khách" value={currency(totals.need_refund)} suffix="đ" />
      </Col>
      <Col span={8}>
        <Statistic title="Đã trả khách" value={currency(totals.refunded)} suffix="đ" />
      </Col>
    </Row>
  </Card>
);

export default ReturnSummary;

import React, { useEffect } from 'react';
import { Modal, Form, Input, InputNumber, Select, DatePicker, Row, Col, Divider, Switch } from 'antd';
import dayjs from 'dayjs';
import {
  getCategoriesByType,
  PAYMENT_METHODS,
  REFERENCE_TYPES,
  STATUS_OPTIONS,
} from '../../constants/cash';

const CashTransactionForm = ({
  mode = 'receipt',
  open,
  onCancel,
  onSubmit,
  loading = false,
  branches = [],
}) => {
  const [form] = Form.useForm();
  const isReceipt = mode === 'receipt';

  useEffect(() => {
    if (open) {
      form.setFieldsValue({
        transaction_date: dayjs(),
        payment_method: 'cash',
        status: 'approved',
        branch_id: branches?.[0]?.id,
        affects_profit: true,
      });
    }
  }, [open, branches, form]);

  const handleFinish = (values) => {
    const payload = {
      ...values,
      amount: Number(values.amount || 0),
      transaction_date: values.transaction_date
        ? values.transaction_date.format('YYYY-MM-DD')
        : null,
      reference_id: values.reference_id ? Number(values.reference_id) : undefined,
      type: isReceipt ? 'RECEIPT' : 'PAYMENT',
    };

    onSubmit(payload);
  };

  const categoryOptions = getCategoriesByType(isReceipt ? 'RECEIPT' : 'PAYMENT');

  return (
    <Modal
      open={open}
      onCancel={() => {
        form.resetFields();
        onCancel?.();
      }}
      onOk={() => form.submit()}
      confirmLoading={loading}
      title={isReceipt ? 'Tạo phiếu thu tiền mặt' : 'Tạo phiếu chi tiền mặt'}
      width={760}
      okText="Lưu"
      cancelText="Bỏ qua"
    >
      <Form
        form={form}
        layout="vertical"
        onFinish={handleFinish}
        requiredMark={false}
      >
        <Row gutter={12}>
          <Col span={12}>
            <Form.Item
              label="Số tiền"
              name="amount"
              rules={[{ required: true, message: 'Nhập số tiền' }]}
            >
              <InputNumber
                min={0}
                style={{ width: '100%' }}
                formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                parser={(value) => value.replace(/\s?|(,*)/g, '')}
                prefix={isReceipt ? '+' : '-'}
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              label="Thời gian"
              name="transaction_date"
              rules={[{ required: true, message: 'Chọn thời gian' }]}
            >
              <DatePicker
                format="DD/MM/YYYY"
                style={{ width: '100%' }}
              />
            </Form.Item>
          </Col>
        </Row>

        <Row gutter={12}>
          <Col span={12}>
            <Form.Item
              label={isReceipt ? 'Loại thu' : 'Loại chi'}
              name="category"
              rules={[{ required: true, message: 'Chọn loại' }]}
            >
              <Select
                options={categoryOptions}
                placeholder={isReceipt ? 'Chọn loại thu' : 'Chọn loại chi'}
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              label="Chi nhánh"
              name="branch_id"
              rules={[{ required: true, message: 'Chọn chi nhánh' }]}
            >
              <Select
                showSearch
                optionFilterProp="label"
                options={branches.map((b) => ({
                  value: b.id,
                  label: b.name || b.code || `CN #${b.id}`,
                }))}
                placeholder="Chọn chi nhánh"
              />
            </Form.Item>
          </Col>
        </Row>

        <Row gutter={12}>
          <Col span={12}>
            <Form.Item label="Người nộp/nhận" name="payer_name">
              <Input placeholder="Tên người nộp hoặc nhận" />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item label="Số điện thoại" name="payer_phone">
              <Input placeholder="Số điện thoại" />
            </Form.Item>
          </Col>
        </Row>

        <Row gutter={12}>
          <Col span={12}>
            <Form.Item label="Nhân viên thực hiện" name="staff_name">
              <Input placeholder="Tên nhân viên" />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item label="Phương thức" name="payment_method">
              <Select options={PAYMENT_METHODS} placeholder="Chọn phương thức" />
            </Form.Item>
          </Col>
        </Row>

        <Row gutter={12}>
          <Col span={12}>
            <Form.Item label="Tên tài khoản" name="account_name">
              <Input placeholder="Tên tài khoản / quỹ" />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item label="Số tài khoản" name="bank_account">
              <Input placeholder="Số tài khoản / ví" />
            </Form.Item>
          </Col>
        </Row>

        <Divider style={{ margin: '12px 0' }} />

        <Row gutter={12}>
          <Col span={12}>
            <Form.Item label="Tham chiếu" name="reference_type">
              <Select
                allowClear
                placeholder="Chọn loại chứng từ"
                options={REFERENCE_TYPES}
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item label="Mã tham chiếu" name="reference_code">
              <Input placeholder="VD: HD001, PO001..." />
            </Form.Item>
          </Col>
        </Row>

        <Row gutter={12}>
          <Col span={12}>
            <Form.Item label="ID tham chiếu" name="reference_id">
              <Input placeholder="Nhập ID tham chiếu (nếu có)" />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item label="Trạng thái" name="status">
              <Select options={STATUS_OPTIONS} placeholder="Trạng thái" />
            </Form.Item>
          </Col>
        </Row>

        <Form.Item label="Nội dung thu/chi" name="description">
          <Input.TextArea rows={2} placeholder="Nhập mô tả giao dịch" />
        </Form.Item>

        <Form.Item label="Ghi chú nội bộ" name="note">
          <Input.TextArea rows={2} placeholder="Thêm ghi chú" />
        </Form.Item>

        <Form.Item
          label="Hạch toán kết quả kinh doanh"
          name="affects_profit"
          valuePropName="checked"
          tooltip="Tạm tính trên frontend; backend chưa lưu trường này"
        >
          <Switch />
        </Form.Item>
      </Form>
    </Modal>
  );
};

export default CashTransactionForm;

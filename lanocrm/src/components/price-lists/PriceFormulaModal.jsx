import React, { useState, useEffect, useMemo } from 'react';
import { Modal, Form, Select, InputNumber, Button, Checkbox, Row, Col, Typography, message } from 'antd';
import { PlusOutlined, MinusOutlined } from '@ant-design/icons';

const { Text } = Typography;

const PriceFormulaModal = ({ open, onClose, onSave, product, priceListId, basePriceLists = [] }) => {
    const [form] = Form.useForm();
    const [operator, setOperator] = useState('+');
    const [unit, setUnit] = useState('%');
    const [calculatedPrice, setCalculatedPrice] = useState(0);
    const [applyToAll, setApplyToAll] = useState(false);

    // Initial values
    useEffect(() => {
        if (open && product) {
            form.setFieldsValue({
                base: 'cost',
                value: 0,
                rounding: 'thousand',
            });
            setOperator('+');
            setUnit('%');
            setApplyToAll(false);
            calculatePrice('cost', '+', 0, '%', 'thousand');
        }
    }, [open, product, form]);

    const calculatePrice = (base, op, val, u, rounding) => {
        if (!product) return;

        let basePrice = 0;
        if (base === 'cost') basePrice = product.cost_price || 0;
        else if (base === 'purchase') basePrice = product.last_purchase_price || 0;
        else if (base === 'current') basePrice = product.price || 0;
        // Note: For other price lists, we can't easily get the value here without API, 
        // so we might default to 0 or show a warning that it's calculated on server.
        // For simplicity in this inline modal, let's stick to product fields or current price.

        let result = basePrice;
        const numVal = parseFloat(val) || 0;

        if (u === '%') {
            const amount = basePrice * (numVal / 100);
            result = op === '+' ? basePrice + amount : basePrice - amount;
        } else {
            result = op === '+' ? basePrice + numVal : basePrice - numVal;
        }

        // Rounding
        if (rounding === 'hundred') result = Math.round(result / 100) * 100;
        else if (rounding === 'thousand') result = Math.round(result / 1000) * 1000;
        else if (rounding === 'ten_thousand') result = Math.round(result / 10000) * 10000;

        setCalculatedPrice(result);
    };

    const handleValuesChange = (_, allValues) => {
        calculatePrice(allValues.base, operator, allValues.value, unit, allValues.rounding);
    };

    const handleFinish = (values) => {
        const payload = {
            base: values.base,
            operator: operator,
            value: values.value,
            unit: unit,
            rounding: values.rounding,
            apply_to_all: applyToAll,
            calculated_price: calculatedPrice, // For single item update immediate feedback
        };
        onSave(payload);
    };

    return (
        <Modal
            title="Đặt giá theo công thức"
            open={open}
            onCancel={onClose}
            onOk={() => form.submit()}
            width={500}
            destroyOnClose
        >
            <Form
                form={form}
                layout="vertical"
                onFinish={handleFinish}
                onValuesChange={handleValuesChange}
            >
                <div style={{ marginBottom: 16, padding: '12px', background: '#f5f5f5', borderRadius: '4px' }}>
                    <Row justify="space-between">
                        <Text strong>Giá hiện tại:</Text>
                        <Text>{product?.price?.toLocaleString('vi-VN')}đ</Text>
                    </Row>
                    <Row justify="space-between" style={{ marginTop: 8 }}>
                        <Text strong style={{ color: '#1890ff' }}>Giá mới (tạm tính):</Text>
                        <Text strong style={{ color: '#1890ff', fontSize: '16px' }}>
                            {calculatedPrice.toLocaleString('vi-VN')}đ
                        </Text>
                    </Row>
                </div>

                <Form.Item label="Công thức" style={{ marginBottom: 0 }}>
                    <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                        <Form.Item name="base" noStyle>
                            <Select style={{ width: 140 }}>
                                <Select.Option value="cost">Giá vốn</Select.Option>
                                <Select.Option value="purchase">Giá nhập cuối</Select.Option>
                                <Select.Option value="current">Giá hiện tại</Select.Option>
                            </Select>
                        </Form.Item>

                        <Button
                            icon={operator === '+' ? <PlusOutlined /> : <MinusOutlined />}
                            onClick={() => {
                                const newOp = operator === '+' ? '-' : '+';
                                setOperator(newOp);
                                const values = form.getFieldsValue();
                                calculatePrice(values.base, newOp, values.value, unit, values.rounding);
                            }}
                        />

                        <Form.Item name="value" noStyle>
                            <InputNumber style={{ width: 100 }} min={0} />
                        </Form.Item>

                        <Button
                            type={unit === '%' ? 'primary' : 'default'}
                            onClick={() => {
                                const newUnit = '%';
                                setUnit(newUnit);
                                const values = form.getFieldsValue();
                                calculatePrice(values.base, operator, values.value, newUnit, values.rounding);
                            }}
                        >%</Button>
                        <Button
                            type={unit === 'VND' ? 'primary' : 'default'}
                            onClick={() => {
                                const newUnit = 'VND';
                                setUnit(newUnit);
                                const values = form.getFieldsValue();
                                calculatePrice(values.base, operator, values.value, newUnit, values.rounding);
                            }}
                        >VND</Button>
                    </div>
                </Form.Item>

                <Form.Item name="rounding" label="Làm tròn" style={{ marginTop: 16 }}>
                    <Select>
                        <Select.Option value="none">Không làm tròn</Select.Option>
                        <Select.Option value="hundred">Đến trăm đồng</Select.Option>
                        <Select.Option value="thousand">Đến nghìn đồng</Select.Option>
                        <Select.Option value="ten_thousand">Đến chục nghìn đồng</Select.Option>
                    </Select>
                </Form.Item>

                <Checkbox
                    checked={applyToAll}
                    onChange={(e) => setApplyToAll(e.target.checked)}
                >
                    Áp dụng công thức này cho tất cả hàng hóa trong bảng giá
                </Checkbox>
            </Form>
        </Modal>
    );
};

export default PriceFormulaModal;

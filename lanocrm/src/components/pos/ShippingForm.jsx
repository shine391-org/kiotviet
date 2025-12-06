import React, { useState } from 'react';
import { Input, Button, Select, Switch, Cascader, Tooltip } from 'antd';
import {
    EnvironmentOutlined,
    UserOutlined,
    DropboxOutlined,
    EditOutlined,
    DoubleRightOutlined,
    DoubleLeftOutlined,
} from '@ant-design/icons';
import CustomerHeader from './CustomerHeader';
import styles from './ShippingForm.module.css';

// Mock address data
const VIETNAM_PROVINCES = [
    {
        value: 'hanoi',
        label: 'Hà Nội',
        children: [
            {
                value: 'badinh',
                label: 'Quận Ba Đình',
                children: [
                    { value: 'thanhcong', label: 'Phường Thành Công' },
                    { value: 'langha', label: 'Phường Láng Hạ' },
                ],
            },
            {
                value: 'caugiay',
                label: 'Quận Cầu Giấy',
                children: [
                    { value: 'dichvong', label: 'Phường Dịch Vọng' },
                    { value: 'maidichnew', label: 'Phường Mai Dịch' },
                ],
            },
        ],
    },
    {
        value: 'hcm',
        label: 'TP. Hồ Chí Minh',
        children: [
            {
                value: 'quan1',
                label: 'Quận 1',
                children: [
                    { value: 'benghe', label: 'Phường Bến Nghé' },
                    { value: 'benthanhph', label: 'Phường Bến Thành' },
                ],
            },
        ],
    },
];

const ShippingForm = ({
    customer,
    onCustomerChange,
    totals,
    onPayment,
    onDelivery,
    showDeliveryPanel,
    onToggleDeliveryPanel,
}) => {
    const [formData, setFormData] = useState({
        savedAddress: '',
        recipientName: '',
        phone: '',
        addressDetail: '',
        location: [],
        weight: 500,
        weightUnit: 'gram',
        dimensions: { l: 10, w: 10, h: 10 },
        dimensionUnit: 'cm',
        courierNote: '',
        codEnabled: true,
        codAmount: totals.customerPay,
        customerPaid: 0,
    });

    const handleInputChange = (field, value) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
    };

    const handleDimensionChange = (dim, value) => {
        setFormData((prev) => ({
            ...prev,
            dimensions: { ...prev.dimensions, [dim]: parseInt(value, 10) || 0 },
        }));
    };

    return (
        <div className={styles.shippingForm}>
            {/* Toggle Delivery Partners Panel Button */}
            {onToggleDeliveryPanel && (
                <Tooltip title={showDeliveryPanel ? 'Ẩn đối tác giao hàng' : 'Hiện đối tác giao hàng'}>
                    <Button
                        type="text"
                        className={styles.togglePanelBtn}
                        onClick={onToggleDeliveryPanel}
                    >
                        {showDeliveryPanel ? <DoubleRightOutlined /> : <DoubleLeftOutlined />}
                    </Button>
                </Tooltip>
            )}

            {/* Customer Header with Seller/Channel Dropdowns - using shared component */}
            <CustomerHeader
                customer={customer}
                onCustomerChange={onCustomerChange}
                showDateTime={true}
            />

            {/* Saved Address Dropdown */}
            <div className={styles.formRow}>
                <EnvironmentOutlined className={styles.rowIcon} />
                <Select
                    placeholder="Chọn địa chỉ đã lưu"
                    value={formData.savedAddress || undefined}
                    onChange={(val) => handleInputChange('savedAddress', val)}
                    className={styles.addressSelect}
                    options={[
                        { value: 'addr1', label: '43 ngõ 5 Láng Hạ, Phường Thành Công, Quận Ba Đình, Hà...' },
                    ]}
                />
            </div>

            {/* Recipient Name & Phone */}
            <div className={styles.formRow}>
                <UserOutlined className={styles.rowIcon} />
                <Input
                    placeholder="Tên người nhận"
                    value={formData.recipientName}
                    onChange={(e) => handleInputChange('recipientName', e.target.value)}
                    className={styles.recipientInput}
                />
                <Input
                    placeholder="Số điện thoại"
                    value={formData.phone}
                    onChange={(e) => handleInputChange('phone', e.target.value)}
                    className={styles.phoneInput}
                />
            </div>

            {/* Address Detail */}
            <Input
                placeholder="Địa chỉ chi tiết (Số nhà, ngõ, đường)"
                value={formData.addressDetail}
                onChange={(e) => handleInputChange('addressDetail', e.target.value)}
                className={styles.addressDetailInput}
            />

            {/* Province/District/Ward */}
            <Cascader
                options={VIETNAM_PROVINCES}
                placeholder="Tỉnh/TP - Quận/Huyện"
                value={formData.location}
                onChange={(val) => handleInputChange('location', val)}
                className={styles.locationCascader}
            />

            <Input
                placeholder="Phường/Xã"
                className={styles.wardInput}
            />

            {/* Package Info */}
            <div className={styles.packageRow}>
                <DropboxOutlined className={styles.rowIcon} />
                <Input
                    type="number"
                    value={formData.weight}
                    onChange={(e) => handleInputChange('weight', e.target.value)}
                    className={styles.weightInput}
                />
                <Select
                    value={formData.weightUnit}
                    onChange={(val) => handleInputChange('weightUnit', val)}
                    className={styles.unitSelect}
                    options={[
                        { value: 'gram', label: 'gram' },
                        { value: 'kg', label: 'kg' },
                    ]}
                />
                <span className={styles.dimensionSeparator}>×</span>
                <Input
                    type="number"
                    value={formData.dimensions.l}
                    onChange={(e) => handleDimensionChange('l', e.target.value)}
                    className={styles.dimensionInput}
                />
                <span className={styles.dimensionSeparator}>×</span>
                <Input
                    type="number"
                    value={formData.dimensions.w}
                    onChange={(e) => handleDimensionChange('w', e.target.value)}
                    className={styles.dimensionInput}
                />
                <span className={styles.dimensionSeparator}>×</span>
                <Input
                    type="number"
                    value={formData.dimensions.h}
                    onChange={(e) => handleDimensionChange('h', e.target.value)}
                    className={styles.dimensionInput}
                />
                <Select
                    value={formData.dimensionUnit}
                    onChange={(val) => handleInputChange('dimensionUnit', val)}
                    className={styles.unitSelect}
                    options={[
                        { value: 'cm', label: 'cm' },
                        { value: 'mm', label: 'mm' },
                    ]}
                />
            </div>

            {/* Courier Note */}
            <div className={styles.formRow}>
                <EditOutlined className={styles.rowIcon} />
                <Input
                    placeholder="Ghi chú cho bưu tá"
                    value={formData.courierNote}
                    onChange={(e) => handleInputChange('courierNote', e.target.value)}
                    className={styles.courierNoteInput}
                />
            </div>

            {/* Payment Section */}
            <div className={styles.paymentSection}>
                <div className={styles.paymentRow}>
                    <span>Khách thanh toán</span>
                    <Button type="text" icon={<span>⋮</span>} size="small" />
                    <Input
                        value={formData.customerPaid.toLocaleString('vi-VN')}
                        className={styles.paymentInput}
                    />
                </div>
                <div className={styles.codRow}>
                    <span>Thu hộ tiền (COD)</span>
                    <Switch
                        checked={formData.codEnabled}
                        onChange={(checked) => handleInputChange('codEnabled', checked)}
                    />
                    <span className={styles.codAmount}>
                        {formData.codEnabled ? totals.customerPay.toLocaleString('vi-VN') : 0}
                    </span>
                </div>
            </div>

            {/* Action Buttons */}
            <div className={styles.actionButtons}>
                <Button
                    size="large"
                    className={styles.deliveryBtn}
                    onClick={onDelivery}
                >
                    GIAO HÀNG
                </Button>
                <Button
                    type="primary"
                    size="large"
                    className={styles.payBtn}
                    onClick={onPayment}
                >
                    THANH TOÁN
                </Button>
            </div>
        </div>
    );
};

export default ShippingForm;

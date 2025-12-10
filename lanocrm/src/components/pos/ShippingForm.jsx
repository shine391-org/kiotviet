import React, { useState, useEffect, useCallback } from 'react';
import { Input, Select, Switch, Tooltip, Button } from 'antd';
import {
    EnvironmentOutlined,
    UserOutlined,
    DropboxOutlined,
    EditOutlined,
    DoubleRightOutlined,
    DoubleLeftOutlined,
} from '@ant-design/icons';
import CustomerHeader from './CustomerHeader';
import PaymentButton from './PaymentButton';
import locationApi from '../../api/locationApi';
import styles from './ShippingForm.module.css';

const ShippingForm = ({
    customer,
    onCustomerChange,
    totals,
    showDeliveryPanel,
    onToggleDeliveryPanel,
    onPayment,
}) => {
    const [provinces, setProvinces] = useState([]);
    const [districts, setDistricts] = useState([]);
    const [wards, setWards] = useState([]);
    const [locationsLoading, setLocationsLoading] = useState(false);
    const [formData, setFormData] = useState({
        savedAddress: '',
        recipientName: '',
        phone: '',
        addressDetail: '',
        province: null,
        district: null,
        ward: null,
        weight: 500,
        weightUnit: 'gram',
        dimensions: { l: 10, w: 10, h: 10 },
        dimensionUnit: 'cm',
        courierNote: '',
        codEnabled: true,
        codAmount: totals.customerPay,
        customerPaid: 0,
    });

    // Sync codAmount when totals.customerPay changes
    useEffect(() => {
        if (formData.codEnabled) {
            setFormData((prev) => ({ ...prev, codAmount: totals.customerPay }));
        }
    }, [totals.customerPay, formData.codEnabled]);

    // Fetch provinces on mount
    const fetchProvinces = useCallback(async () => {
        setLocationsLoading(true);
        try {
            const response = await locationApi.getProvinces();
            if (response.success && response.data) {
                setProvinces(response.data.map(p => ({
                    value: p.id,
                    label: p.name,
                })));
            }
        } catch (error) {
            console.error('Failed to fetch provinces:', error);
        } finally {
            setLocationsLoading(false);
        }
    }, []);

    // Fetch districts when province changes
    const fetchDistricts = useCallback(async (provinceId) => {
        if (!provinceId) {
            setDistricts([]);
            setWards([]);
            return;
        }
        try {
            const response = await locationApi.getDistricts(provinceId);
            if (response.success && response.data) {
                setDistricts(response.data.map(d => ({
                    value: d.id,
                    label: d.name,
                })));
            }
        } catch (error) {
            console.error('Failed to fetch districts:', error);
        }
    }, []);

    // Fetch wards when district changes
    const fetchWards = useCallback(async (districtId) => {
        if (!districtId) {
            setWards([]);
            return;
        }
        try {
            const response = await locationApi.getWards(districtId);
            if (response.success && response.data) {
                setWards(response.data.map(w => ({
                    value: w.id,
                    label: w.name,
                })));
            }
        } catch (error) {
            console.error('Failed to fetch wards:', error);
        }
    }, []);

    useEffect(() => {
        fetchProvinces();
    }, [fetchProvinces]);

    const handleProvinceChange = (provinceId) => {
        handleInputChange('province', provinceId);
        handleInputChange('district', null);
        handleInputChange('ward', null);
        setDistricts([]);
        setWards([]);
        fetchDistricts(provinceId);
    };

    const handleDistrictChange = (districtId) => {
        handleInputChange('district', districtId);
        handleInputChange('ward', null);
        setWards([]);
        fetchWards(districtId);
    };

    const handleInputChange = (field, value) => {
        // Convert weight to number
        if (field === 'weight') {
            value = value === '' || value === null ? 0 : parseFloat(value) || 0;
        }
        // Convert customerPaid to number
        if (field === 'customerPaid') {
            value = value === '' || value === null ? 0 : parseFloat(String(value).replace(/,/g, '')) || 0;
        }
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
            <div className={styles.locationRow}>
                <Select
                    placeholder="Tỉnh/Thành phố"
                    value={formData.province || undefined}
                    onChange={handleProvinceChange}
                    options={provinces}
                    loading={locationsLoading}
                    className={styles.provinceSelect}
                    showSearch
                    filterOption={(input, option) =>
                        (option?.label ?? '').toLowerCase().includes(input.toLowerCase())
                    }
                />
                <Select
                    placeholder="Quận/Huyện"
                    value={formData.district || undefined}
                    onChange={handleDistrictChange}
                    options={districts}
                    disabled={!formData.province}
                    className={styles.districtSelect}
                    showSearch
                    filterOption={(input, option) =>
                        (option?.label ?? '').toLowerCase().includes(input.toLowerCase())
                    }
                />
            </div>

            <Select
                placeholder="Phường/Xã"
                value={formData.ward || undefined}
                onChange={(val) => handleInputChange('ward', val)}
                options={wards}
                disabled={!formData.district}
                className={styles.wardSelect}
                showSearch
                filterOption={(input, option) =>
                    (option?.label ?? '').toLowerCase().includes(input.toLowerCase())
                }
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
                        onChange={(e) => handleInputChange('customerPaid', e.target.value)}
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

            {/* Payment Button - only show when DeliveryPanel is closed */}
            {!showDeliveryPanel && (
                <PaymentButton onClick={onPayment} />
            )}
        </div>
    );
};

export default ShippingForm;

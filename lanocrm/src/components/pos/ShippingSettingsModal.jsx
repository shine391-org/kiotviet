import React, { useState, useEffect, useCallback } from 'react';
import { Modal, Tabs, Switch, Radio, Select, Input, Button, Dropdown, App } from 'antd';
import { MoreOutlined, LinkOutlined } from '@ant-design/icons';
import styles from './ShippingSettingsModal.module.css';

const STORAGE_KEY = 'pos_shipping_settings';

// Shipping carriers data with placeholder logos
const SHIPPING_CARRIERS = [
    { id: 'jt', name: 'J&T', logo: 'J&T', color: '#e60012', connected: true },
    { id: 'vnpost', name: 'VNPost', logo: 'VNPost', color: '#d4380d', connected: true },
    { id: 'ems', name: 'EMS', logo: 'EMS', color: '#1890ff', connected: true },
    { id: 'grab', name: 'Grab', logo: 'Grab', color: '#00b14f', connected: true },
    { id: 'spx', name: 'SPX Express', logo: 'SPX', color: '#ee4d2d', connected: true },
    { id: 'ahamove', name: 'AhaMove', logo: 'Aha', color: '#f26522', connected: false },
    { id: 'ghn', name: 'Giao Hàng Nhanh', logo: 'GHN', color: '#f57c00', connected: true },
    { id: 'viettel', name: 'Viettel Post', logo: 'Viettel', color: '#e60012', connected: true },
    { id: 'best', name: 'BEST', logo: 'BEST', color: '#e60012', connected: true },
    { id: 'be', name: 'BE', logo: 'be', color: '#f5c400', connected: true },
    { id: 'xanhsm', name: 'Xanh SM', logo: 'Xanh', color: '#00a651', connected: true },
];

const DELIVERY_NOTES = [
    { value: 'no_view', label: 'Không cho xem hàng' },
    { value: 'allow_view', label: 'Cho xem hàng' },
    { value: 'try_on', label: 'Cho thử hàng' },
];

const DEFAULT_SERVICES = [
    { value: 'standard', label: 'Giao thường' },
    { value: 'express', label: 'Giao nhanh' },
    { value: 'same_day', label: 'Giao trong ngày' },
];

const DEFAULT_SETTINGS = {
    fastReconciliation: false,
    declareValue: false,
    payer: 'sender',
    deliveryNote: 'no_view',
    courierNote: '',
    weight: 500,
    weightUnit: 'gram',
    dimensions: { l: 10, w: 10, h: 10 },
    dimensionUnit: 'cm',
    defaultService: null,
};

const ShippingSettingsModal = ({ open, onClose, onSave }) => {
    const { message } = App.useApp();
    const [activeTab, setActiveTab] = useState('general');

    // General settings state
    const [settings, setSettings] = useState(DEFAULT_SETTINGS);

    // Carrier states
    const [carriers, setCarriers] = useState(SHIPPING_CARRIERS);

    // Load settings from localStorage when modal opens
    useEffect(() => {
        if (open) {
            try {
                const saved = localStorage.getItem(STORAGE_KEY);
                if (saved) {
                    const parsed = JSON.parse(saved);
                    if (parsed.settings) {
                        setSettings({ ...DEFAULT_SETTINGS, ...parsed.settings });
                    }
                    if (parsed.carriers) {
                        setCarriers(parsed.carriers);
                    }
                }
            } catch (error) {
                console.error('Failed to load shipping settings:', error);
            }
        }
    }, [open]);

    const handleSettingChange = (key, value) => {
        setSettings(prev => ({ ...prev, [key]: value }));
    };

    const handleDimensionChange = (dim, value) => {
        setSettings(prev => ({
            ...prev,
            dimensions: { ...prev.dimensions, [dim]: parseInt(value, 10) || 0 },
        }));
    };

    const handleToggleCarrier = (carrierId) => {
        setCarriers(prev =>
            prev.map(c =>
                c.id === carrierId ? { ...c, connected: !c.connected } : c
            )
        );
    };

    const handleSave = useCallback(() => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({ settings, carriers }));
            message.success('Đã lưu thiết lập vận chuyển');
            onSave?.({ settings, carriers });
            onClose?.();
        } catch (error) {
            console.error('Failed to save shipping settings:', error);
            message.error('Lỗi khi lưu thiết lập');
        }
    }, [settings, carriers, onSave, onClose, message]);

    const getCarrierMenuItems = (carrier) => [
        {
            key: 'toggle',
            label: carrier.connected ? 'Tắt hãng này' : 'Bật hãng này',
            onClick: () => handleToggleCarrier(carrier.id),
        },
        {
            key: 'view_connection',
            label: 'Xem kết nối',
        },
    ];

    const tabItems = [
        {
            key: 'general',
            label: 'Thiết lập chung',
            children: (
                <div className={styles.tabContent}>
                    {/* Fast Reconciliation */}
                    <div className={styles.settingRow}>
                        <div className={styles.settingInfo}>
                            <span className={styles.settingLabel}>Đối soát nhanh</span>
                            <p className={styles.settingDesc}>
                                Thanh toán tiền COD ngay khi đơn hàng được giao thành công.{' '}
                                <a href="#" className={styles.learnMore}>Tìm hiểu thêm</a>
                            </p>
                        </div>
                        <Switch
                            checked={settings.fastReconciliation}
                            onChange={(checked) => handleSettingChange('fastReconciliation', checked)}
                        />
                    </div>

                    {/* Declare Value */}
                    <div className={styles.settingRow}>
                        <span className={styles.settingLabel}>Khai giá</span>
                        <Switch
                            checked={settings.declareValue}
                            onChange={(checked) => handleSettingChange('declareValue', checked)}
                        />
                    </div>

                    {/* Payer */}
                    <div className={styles.settingRow}>
                        <span className={styles.settingLabel}>Người trả phí</span>
                        <Radio.Group
                            value={settings.payer}
                            onChange={(e) => handleSettingChange('payer', e.target.value)}
                        >
                            <Radio value="sender">Người gửi</Radio>
                            <Radio value="receiver">Người nhận</Radio>
                        </Radio.Group>
                    </div>

                    {/* Delivery Note */}
                    <div className={styles.settingRow}>
                        <span className={styles.settingLabel}>Lưu ý giao hàng</span>
                        <Select
                            value={settings.deliveryNote}
                            onChange={(val) => handleSettingChange('deliveryNote', val)}
                            options={DELIVERY_NOTES}
                            className={styles.selectField}
                        />
                    </div>

                    {/* Courier Note */}
                    <div className={styles.settingRow}>
                        <span className={styles.settingLabel}>Ghi chú cho bưu tá</span>
                        <Input
                            placeholder="Nhập ghi chú"
                            value={settings.courierNote}
                            onChange={(e) => handleSettingChange('courierNote', e.target.value)}
                            className={styles.inputField}
                        />
                    </div>

                    {/* Weight & Dimensions */}
                    <div className={styles.settingRow}>
                        <span className={styles.settingLabel}>Trọng lượng & Kích thước</span>
                        <div className={styles.dimensionsGroup}>
                            <Input
                                type="number"
                                value={settings.weight}
                                onChange={(e) => handleSettingChange('weight', parseInt(e.target.value, 10) || 0)}
                                className={styles.weightInput}
                            />
                            <Select
                                value={settings.weightUnit}
                                onChange={(val) => handleSettingChange('weightUnit', val)}
                                options={[
                                    { value: 'gram', label: 'gram' },
                                    { value: 'kg', label: 'kg' },
                                ]}
                                className={styles.unitSelect}
                            />
                            <span className={styles.separator}>×</span>
                            <Input
                                type="number"
                                value={settings.dimensions.l}
                                onChange={(e) => handleDimensionChange('l', e.target.value)}
                                className={styles.dimInput}
                            />
                            <span className={styles.separator}>×</span>
                            <Input
                                type="number"
                                value={settings.dimensions.w}
                                onChange={(e) => handleDimensionChange('w', e.target.value)}
                                className={styles.dimInput}
                            />
                            <span className={styles.separator}>×</span>
                            <Input
                                type="number"
                                value={settings.dimensions.h}
                                onChange={(e) => handleDimensionChange('h', e.target.value)}
                                className={styles.dimInput}
                            />
                            <Select
                                value={settings.dimensionUnit}
                                onChange={(val) => handleSettingChange('dimensionUnit', val)}
                                options={[
                                    { value: 'cm', label: 'cm' },
                                    { value: 'mm', label: 'mm' },
                                ]}
                                className={styles.unitSelect}
                            />
                        </div>
                    </div>

                    {/* Default Service */}
                    <div className={styles.settingRow}>
                        <span className={styles.settingLabel}>Dịch vụ mặc định</span>
                        <Select
                            placeholder="Chọn dịch vụ"
                            value={settings.defaultService}
                            onChange={(val) => handleSettingChange('defaultService', val)}
                            options={DEFAULT_SERVICES}
                            className={styles.selectField}
                        />
                    </div>
                </div>
            ),
        },
        {
            key: 'carriers',
            label: 'Đối tác vận chuyển',
            children: (
                <div className={styles.tabContent}>
                    <div className={styles.carriersList}>
                        {carriers.map((carrier) => (
                            <div key={carrier.id} className={styles.carrierRow}>
                                <div className={styles.carrierInfo}>
                                    <div
                                        className={styles.carrierLogo}
                                        style={{
                                            backgroundColor: `${carrier.color}15`,
                                            color: carrier.color,
                                        }}
                                    >
                                        {carrier.logo}
                                    </div>
                                    <span className={styles.carrierName}>{carrier.name}</span>
                                </div>
                                <div className={styles.carrierActions}>
                                    {!carrier.connected && (
                                        <LinkOutlined className={styles.disconnectedIcon} />
                                    )}
                                    <Dropdown
                                        menu={{ items: getCarrierMenuItems(carrier) }}
                                        trigger={['click']}
                                        placement="bottomRight"
                                    >
                                        <Button
                                            type="text"
                                            icon={<MoreOutlined />}
                                            className={styles.moreBtn}
                                        />
                                    </Dropdown>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            ),
        },
    ];

    return (
        <Modal
            title="Thiết lập vận chuyển"
            open={open}
            onCancel={onClose}
            width={700}
            footer={
                <div className={styles.modalFooter}>
                    <Button onClick={onClose}>Bỏ qua</Button>
                    <Button type="primary" onClick={handleSave}>Lưu</Button>
                </div>
            }
            className={styles.modal}
        >
            <div className={styles.modalBody}>
                <Tabs
                    activeKey={activeTab}
                    onChange={setActiveTab}
                    items={tabItems}
                    tabPosition="left"
                    className={styles.tabs}
                />
            </div>
        </Modal>
    );
};

export default ShippingSettingsModal;

import React, { useState, useEffect } from 'react';
import { Modal, Tabs, Switch, Radio, Tooltip } from 'antd';
import { InfoCircleOutlined } from '@ant-design/icons';
import styles from './DisplaySettingsModal.module.css';

// Default settings
const DEFAULT_SETTINGS = {
    // Hiển thị tab
    showProductImage: true,
    showProductCode: true,
    showSellingPrice: true,
    showDiscount: true,
    discountType: 'VND', // 'VND' or '%'
    editSubtotal: true,
    showRecentPrice: true,
    showAddRow: true,
    multiSelectMode: true,

    // Khác tab
    defaultPaymentMethod: 'cash', // 'cash', 'transfer', 'card', 'wallet'
    autoCompleteOnQR: false,
    speakPaymentAmount: true,
    quickScanToAdd: false,
    showStock: false,
    showComboComponents: false,
    showProductNotes: false,
    groupRelatedProducts: true,
    defaultCustomerPayment: true,
    showPriceSetup: false,
    showPriceTree: false,
    dragDropProducts: false,
};

const STORAGE_KEY = 'pos_display_settings';

const DisplaySettingsModal = ({ open, onClose }) => {
    const [settings, setSettings] = useState(() => {
        // Load from localStorage on init
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                return { ...DEFAULT_SETTINGS, ...JSON.parse(saved) };
            }
        } catch (e) {
            console.error('Failed to load display settings:', e);
        }
        return DEFAULT_SETTINGS;
    });

    // Save to localStorage when settings change
    useEffect(() => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
        } catch (e) {
            console.error('Failed to save display settings:', e);
        }
    }, [settings]);

    const updateSetting = (key, value) => {
        setSettings(prev => ({ ...prev, [key]: value }));
    };

    const SettingRow = ({ label, settingKey, tooltip }) => (
        <div className={styles.settingRow}>
            <div className={styles.settingLabel}>
                {label}
                {tooltip && (
                    <Tooltip title={tooltip}>
                        <InfoCircleOutlined className={styles.infoIcon} />
                    </Tooltip>
                )}
            </div>
            <Switch
                checked={settings[settingKey]}
                onChange={(checked) => updateSetting(settingKey, checked)}
            />
        </div>
    );

    const tabItems = [
        {
            key: 'display',
            label: 'Hiển thị',
            children: (
                <div className={styles.tabContent}>
                    <SettingRow label="Ảnh hàng hóa" settingKey="showProductImage" />
                    <SettingRow label="Mã hàng" settingKey="showProductCode" />
                    <SettingRow label="Giá bán" settingKey="showSellingPrice" />

                    <div className={styles.settingRow}>
                        <div className={styles.settingLabel}>
                            Giảm giá
                            <Tooltip title="Hiển thị cột giảm giá trên giỏ hàng">
                                <InfoCircleOutlined className={styles.infoIcon} />
                            </Tooltip>
                        </div>
                        <div className={styles.discountControl}>
                            <Radio.Group
                                value={settings.discountType}
                                onChange={(e) => updateSetting('discountType', e.target.value)}
                                size="small"
                                buttonStyle="solid"
                            >
                                <Radio.Button value="VND">VND</Radio.Button>
                                <Radio.Button value="%">%</Radio.Button>
                            </Radio.Group>
                        </div>
                    </div>

                    <SettingRow label="Chỉnh sửa thành tiền" settingKey="editSubtotal" />
                    <SettingRow
                        label="Xem giá bán gần nhất"
                        settingKey="showRecentPrice"
                        tooltip="Hiển thị giá bán gần nhất của sản phẩm cho khách hàng này"
                    />
                    <SettingRow
                        label="Thêm dòng"
                        settingKey="showAddRow"
                        tooltip="Hiển thị nút thêm dòng sản phẩm mới"
                    />
                    <SettingRow label="Chế độ chọn nhiều hàng hóa" settingKey="multiSelectMode" />
                </div>
            ),
        },
        {
            key: 'other',
            label: 'Khác',
            children: (
                <div className={styles.tabContent}>
                    <div className={styles.settingSection}>
                        <div className={styles.sectionLabel}>Phương thức thanh toán mặc định</div>
                        <Radio.Group
                            value={settings.defaultPaymentMethod}
                            onChange={(e) => updateSetting('defaultPaymentMethod', e.target.value)}
                            className={styles.paymentRadioGroup}
                        >
                            <Radio value="cash">Tiền mặt</Radio>
                            <Radio value="transfer">Chuyển khoản</Radio>
                            <Radio value="card">Thẻ</Radio>
                            <Radio value="wallet">Ví</Radio>
                        </Radio.Group>
                    </div>

                    <div className={styles.divider} />

                    <SettingRow
                        label="Tự động hoàn thành đơn hàng khi quét QR thành công"
                        settingKey="autoCompleteOnQR"
                        tooltip="Tự động đánh dấu đơn hàng là đã hoàn thành khi quét QR thanh toán thành công"
                    />
                    <SettingRow
                        label="Đọc số tiền khách thanh toán qua loa thiết bị"
                        settingKey="speakPaymentAmount"
                        tooltip="Đọc to số tiền khách đưa qua loa"
                    />
                    <SettingRow
                        label="Quét mã để thêm hàng hóa nhanh"
                        settingKey="quickScanToAdd"
                        tooltip="Tự động thêm sản phẩm vào giỏ khi quét barcode"
                    />
                    <SettingRow
                        label="Tồn kho"
                        settingKey="showStock"
                        tooltip="Hiển thị số lượng tồn kho của sản phẩm"
                    />
                    <SettingRow
                        label="Thành phần combo"
                        settingKey="showComboComponents"
                        tooltip="Hiển thị chi tiết thành phần khi bán combo"
                    />
                    <SettingRow
                        label="Ghi chú hàng hóa"
                        settingKey="showProductNotes"
                        tooltip="Hiển thị ghi chú sản phẩm trong giỏ hàng"
                    />
                    <SettingRow label="Gộp hàng hóa liên quan" settingKey="groupRelatedProducts" />
                    <SettingRow
                        label="Mặc định khách thanh toán"
                        settingKey="defaultCustomerPayment"
                        tooltip="Mặc định số tiền khách trả bằng tổng đơn hàng"
                    />
                    <SettingRow
                        label="Thiết lập giá"
                        settingKey="showPriceSetup"
                        tooltip="Cho phép thay đổi giá bán trực tiếp tại POS"
                    />
                    <SettingRow
                        label="Cây hiển thị giá"
                        settingKey="showPriceTree"
                        tooltip="Hiển thị cấu trúc giá của sản phẩm"
                    />
                    <SettingRow label="Kéo thả hàng hóa" settingKey="dragDropProducts" />
                </div>
            ),
        },
    ];

    return (
        <Modal
            title="Thiết lập"
            open={open}
            onCancel={onClose}
            footer={null}
            width={400}
            className={styles.modal}
        >
            <Tabs
                items={tabItems}
                defaultActiveKey="display"
                className={styles.tabs}
            />
        </Modal>
    );
};

// Export settings reader for other components to use
export const usePOSDisplaySettings = () => {
    const [settings, setSettings] = useState(() => {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                return { ...DEFAULT_SETTINGS, ...JSON.parse(saved) };
            }
        } catch (e) {
            console.error('Failed to load display settings:', e);
        }
        return DEFAULT_SETTINGS;
    });

    useEffect(() => {
        const handleStorage = (e) => {
            if (e.key === STORAGE_KEY) {
                try {
                    setSettings({ ...DEFAULT_SETTINGS, ...JSON.parse(e.newValue || '{}') });
                } catch (err) {
                    console.error('Failed to parse settings:', err);
                }
            }
        };
        window.addEventListener('storage', handleStorage);
        return () => window.removeEventListener('storage', handleStorage);
    }, []);

    return settings;
};

export default DisplaySettingsModal;

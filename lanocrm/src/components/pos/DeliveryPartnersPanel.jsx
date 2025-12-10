import React, { useState, useEffect, useCallback } from 'react';
import { Tabs, Button, Select, Input, DatePicker, App } from 'antd';
import { CarOutlined, SendOutlined, SettingOutlined, EditOutlined, PlusOutlined, CalendarOutlined } from '@ant-design/icons';
import deliveryPartnerApi from '../../api/deliveryPartnerApi';
import ShippingSettingsModal from './ShippingSettingsModal';
import DeliveryPartnerModal from './DeliveryPartnerModal';
import PaymentButton from './PaymentButton';
import styles from './DeliveryPartnersPanel.module.css';

// Delivery partner logos (using text placeholders - would be images in production)
const DELIVERY_PARTNERS = [
    { id: 'ghn', name: 'GHN', logo: 'GHN', color: '#f57c00', subtitle: 'GIAO HÀNG NHANH TOÀN QUỐC' },
    { id: 'best', name: 'BEST Express', logo: 'BEST', color: '#1a1a1a', subtitle: 'EXPRESS' },
    { id: 'shopee', name: 'Shopee Xpress', logo: 'SPX', color: '#ee4d2d', subtitle: '' },
    { id: 'viettel', name: 'Viettel Post', logo: 'Viettel', color: '#e60012', subtitle: 'POST' },
    { id: 'vnpost', name: 'Vietnam Post', logo: 'VNPost', color: '#d4380d', subtitle: 'VIETNAM POST' },
    { id: 'ahamove', name: 'Ahamove', logo: 'Aha', color: '#f26522', subtitle: '' },
    { id: 'ems', name: 'EMS Vietnam', logo: 'EMS', color: '#1890ff', subtitle: 'VIETNAM' },
    { id: 'jnt', name: 'J&T Express', logo: 'J&T', color: '#e60012', subtitle: 'EXPRESS' },
    { id: 'ninjavan', name: 'Ninja Van', logo: 'ninja', color: '#c41e3a', subtitle: 'van' },
    { id: 'ghtk', name: 'GHTK', logo: 'GHTK', color: '#00a651', subtitle: '' },
    { id: 'grab', name: 'Grab Express', logo: 'Grab', color: '#00b14f', subtitle: 'Express' },
    { id: 'be', name: 'BE', logo: 'be', color: '#f5c400', subtitle: '' },
    { id: 'xanhsm', name: 'Xanh SM', logo: 'Xanh', color: '#00a651', subtitle: 'SM' },
];

const SERVICE_TYPES = [
    { value: 'standard', label: 'Giao thường' },
    { value: 'express', label: 'Giao nhanh' },
    { value: 'same_day', label: 'Giao trong ngày' },
];

const DELIVERY_STATUS = [
    { value: 'pending', label: 'Chờ xử lý' },
    { value: 'picking', label: 'Đang lấy hàng' },
    { value: 'delivering', label: 'Đang giao' },
    { value: 'delivered', label: 'Đã giao' },
    { value: 'failed', label: 'Giao thất bại' },
    { value: 'returned', label: 'Hoàn hàng' },
];

const DeliveryPartnersPanel = ({
    visible,
    onClose,
    onSelectPartner,
    selectedPartner,
    onPayment,
}) => {
    const { message } = App.useApp();
    const [activeTab, setActiveTab] = useState('kiotviet');
    const [showSettingsModal, setShowSettingsModal] = useState(false);
    const [showPartnerModal, setShowPartnerModal] = useState(false);
    const [partnerModalMode, setPartnerModalMode] = useState('add');
    const [editingPartner, setEditingPartner] = useState(null);
    const [loading, setLoading] = useState(false);

    // Self-delivery partners list from API
    const [selfPartners, setSelfPartners] = useState([]);

    // Fetch delivery partners from API
    const fetchPartners = useCallback(async () => {
        try {
            const response = await deliveryPartnerApi.list({ limit: 100 });
            if (response.success && response.data) {
                setSelfPartners(response.data.map(p => ({
                    value: p.id?.toString(),
                    label: p.name,
                    id: p.id,
                    partnerCode: p.code,
                    name: p.name,
                    phone: p.phone,
                    email: p.email,
                    addressDetail: p.address,
                    partnerType: 'individual',
                    note: p.notes,
                })));
            }
        } catch (error) {
            console.error('Failed to fetch delivery partners:', error);
        }
    }, []);

    useEffect(() => {
        if (visible) {
            fetchPartners();
        }
    }, [visible, fetchPartners]);

    // Self-delivery form state
    const [selfDeliveryData, setSelfDeliveryData] = useState({
        partner: '',
        serviceType: 'standard',
        appliedFee: '',
        trackingCode: '',
        deliveryTime: null,
        status: 'pending',
    });

    const handleSelfDeliveryChange = (field, value) => {
        setSelfDeliveryData(prev => ({ ...prev, [field]: value }));
    };

    const handleSettingsSave = (data) => {
        console.log('Shipping settings saved:', data);
        // TODO: Save settings to backend
    };

    const handleOpenAddPartner = () => {
        setPartnerModalMode('add');
        setEditingPartner(null);
        setShowPartnerModal(true);
    };

    const handleOpenEditPartner = () => {
        const currentPartner = selfPartners.find(p => p.value === selfDeliveryData.partner);
        if (currentPartner) {
            setPartnerModalMode('edit');
            setEditingPartner(currentPartner);
            setShowPartnerModal(true);
        }
    };

    const handlePartnerSave = async (data) => {
        setLoading(true);
        try {
            const payload = {
                name: data.name,
                phone: data.phone || undefined,
                email: data.email || undefined,
                address: data.addressDetail || undefined,
                notes: data.note || undefined,
            };

            if (partnerModalMode === 'add') {
                const response = await deliveryPartnerApi.create(payload);
                if (response.success) {
                    message.success('Thêm đối tác giao hàng thành công');
                    await fetchPartners();
                    setSelfDeliveryData(prev => ({ ...prev, partner: response.data?.id?.toString() }));
                } else {
                    message.error(response.message || 'Không thể thêm đối tác');
                }
            } else {
                const response = await deliveryPartnerApi.update(editingPartner.id, payload);
                if (response.success) {
                    message.success('Cập nhật đối tác thành công');
                    await fetchPartners();
                } else {
                    message.error(response.message || 'Không thể cập nhật đối tác');
                }
            }
        } catch (error) {
            console.error('Save partner error:', error);
            message.error(error.response?.data?.message || 'Lỗi khi lưu đối tác');
        } finally {
            setLoading(false);
        }
    };

    if (!visible) return null;

    const tabItems = [
        {
            key: 'kiotviet',
            label: (
                <span className={styles.tabLabel}>
                    <CarOutlined /> Cổng KiotViet
                </span>
            ),
            children: (
                <div className={styles.tabContent}>
                    <div className={styles.infoBox}>
                        <span className={styles.infoIcon}>💡</span>
                        <p>Sau khi nhập địa chỉ giao hàng, bạn có thể lựa chọn hãng vận chuyển phù hợp với giá tốt nhất</p>
                        <Button
                            type="text"
                            icon={<SettingOutlined />}
                            className={styles.settingBtn}
                            onClick={() => setShowSettingsModal(true)}
                        />
                    </div>

                    <div className={styles.partnersSection}>
                        <h4 className={styles.partnersTitle}>ĐỐI TÁC VẬN CHUYỂN CỦA KIOTVIET</h4>
                        <div className={styles.partnersGrid}>
                            {DELIVERY_PARTNERS.map((partner) => (
                                <div
                                    key={partner.id}
                                    className={`${styles.partnerCard} ${selectedPartner === partner.id ? styles.selected : ''}`}
                                    onClick={() => onSelectPartner?.(partner.id)}
                                >
                                    <div
                                        className={styles.partnerLogo}
                                        style={{
                                            color: partner.color,
                                        }}
                                    >
                                        <span className={styles.logoMain}>{partner.logo}</span>
                                        {partner.subtitle && (
                                            <span className={styles.logoSub}>{partner.subtitle}</span>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            ),
        },
        {
            key: 'self',
            label: (
                <span className={styles.tabLabel}>
                    <SendOutlined /> Tự giao hàng
                </span>
            ),
            children: (
                <div className={styles.tabContent}>
                    <div className={styles.selfDeliveryForm}>
                        {/* Partner Selection */}
                        <div className={styles.formRow}>
                            <label className={styles.formLabel}>Đối tác giao hàng</label>
                            <div className={styles.partnerSelectGroup}>
                                <Select
                                    value={selfDeliveryData.partner}
                                    onChange={(val) => handleSelfDeliveryChange('partner', val)}
                                    options={selfPartners}
                                    className={styles.partnerSelect}
                                />
                                <Button type="text" icon={<EditOutlined />} className={styles.iconBtn} onClick={handleOpenEditPartner} />
                                <Button type="text" icon={<PlusOutlined />} className={styles.iconBtn} onClick={handleOpenAddPartner} />
                            </div>
                        </div>

                        {/* Service Type */}
                        <div className={styles.formRow}>
                            <label className={styles.formLabel}>Loại dịch vụ</label>
                            <Select
                                value={selfDeliveryData.serviceType}
                                onChange={(val) => handleSelfDeliveryChange('serviceType', val)}
                                options={SERVICE_TYPES}
                                className={styles.fullWidthSelect}
                            />
                        </div>

                        {/* Applied Fee */}
                        <div className={styles.formRow}>
                            <label className={styles.formLabel}>Phí áp dụng</label>
                            <Input
                                placeholder=""
                                value={selfDeliveryData.appliedFee}
                                onChange={(e) => handleSelfDeliveryChange('appliedFee', e.target.value)}
                                className={styles.formInput}
                            />
                        </div>

                        {/* Tracking Code */}
                        <div className={styles.formRow}>
                            <label className={styles.formLabel}>Mã vận đơn</label>
                            <Input
                                placeholder=""
                                value={selfDeliveryData.trackingCode}
                                onChange={(e) => handleSelfDeliveryChange('trackingCode', e.target.value)}
                                className={styles.formInput}
                            />
                        </div>

                        {/* Delivery Time */}
                        <div className={styles.formRow}>
                            <label className={styles.formLabel}>Thời gian giao hàng</label>
                            <DatePicker
                                value={selfDeliveryData.deliveryTime}
                                onChange={(val) => handleSelfDeliveryChange('deliveryTime', val)}
                                className={styles.formInput}
                                placeholder=""
                                suffixIcon={<CalendarOutlined />}
                            />
                        </div>

                        {/* Status */}
                        <div className={styles.formRow}>
                            <label className={styles.formLabel}>Trạng thái giao hàng</label>
                            <Select
                                value={selfDeliveryData.status}
                                onChange={(val) => handleSelfDeliveryChange('status', val)}
                                options={DELIVERY_STATUS}
                                className={styles.fullWidthSelect}
                            />
                        </div>
                    </div>
                </div>
            ),
        },
    ];

    return (
        <>
            <div className={styles.panel}>
                <Tabs
                    activeKey={activeTab}
                    onChange={setActiveTab}
                    items={tabItems}
                    className={styles.tabs}
                />
                {/* Single Payment Button at footer */}
                <PaymentButton onClick={onPayment} />
            </div>

            {/* Shipping Settings Modal */}
            <ShippingSettingsModal
                open={showSettingsModal}
                onClose={() => setShowSettingsModal(false)}
                onSave={handleSettingsSave}
            />

            {/* Delivery Partner Add/Edit Modal */}
            <DeliveryPartnerModal
                open={showPartnerModal}
                onClose={() => setShowPartnerModal(false)}
                onSave={handlePartnerSave}
                mode={partnerModalMode}
                partnerData={editingPartner}
            />
        </>
    );
};

export default DeliveryPartnersPanel;

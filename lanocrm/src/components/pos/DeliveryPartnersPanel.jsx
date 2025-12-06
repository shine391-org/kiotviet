import React, { useState } from 'react';
import { Tabs, Button } from 'antd';
import { CarOutlined, SendOutlined, SettingOutlined } from '@ant-design/icons';
import styles from './DeliveryPartnersPanel.module.css';

// Delivery partner logos (using text placeholders - would be images in production)
const DELIVERY_PARTNERS = [
    { id: 'ghn', name: 'GHN', logo: '🚚 GHN' },
    { id: 'best', name: 'BEST Express', logo: '📦 BEST' },
    { id: 'shopee', name: 'Shopee Xpress', logo: '🛒 Shopee' },
    { id: 'viettel', name: 'Viettel Post', logo: '📮 Viettel' },
    { id: 'vnpost', name: 'Vietnam Post', logo: '✉️ VN Post' },
    { id: 'ahamove', name: 'Ahamove', logo: '🏍️ Ahamove' },
    { id: 'ems', name: 'EMS Vietnam', logo: '✈️ EMS' },
    { id: 'jnt', name: 'J&T Express', logo: '📬 J&T' },
    { id: 'ninjavan', name: 'Ninja Van', logo: '🥷 Ninja' },
    { id: 'ghtk', name: 'GHTK', logo: '🚀 GHTK' },
    { id: 'grab', name: 'Grab Express', logo: '🚗 Grab' },
    { id: 'be', name: 'Be', logo: '🛵 be' },
    { id: 'xanhsm', name: 'Xanh SM', logo: '🌿 Xanh' },
];

const DeliveryPartnersPanel = ({
    visible,
    onClose,
    onSelectPartner,
    selectedPartner
}) => {
    const [activeTab, setActiveTab] = useState('kiotviet');

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
                        <Button type="text" icon={<SettingOutlined />} className={styles.settingBtn} />
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
                                    <span className={styles.partnerLogo}>{partner.logo}</span>
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
                    <div className={styles.selfDeliveryContent}>
                        <p>Chọn nhân viên giao hàng từ danh sách hoặc nhập thông tin người giao</p>
                    </div>
                </div>
            ),
        },
    ];

    return (
        <div className={styles.panel}>
            <Tabs
                activeKey={activeTab}
                onChange={setActiveTab}
                items={tabItems}
                className={styles.tabs}
            />
        </div>
    );
};

export default DeliveryPartnersPanel;

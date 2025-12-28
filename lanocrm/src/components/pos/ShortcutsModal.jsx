import React from 'react';
import { Modal } from 'antd';
import styles from './ShortcutsModal.module.css';

const SHORTCUTS = [
    { key: 'F1', label: 'Bán nhanh', section: 'Chế độ bán hàng' },
    { key: 'F2', label: 'Bán thường', section: 'Chế độ bán hàng' },
    { key: 'F5', label: 'Bán giao hàng', section: 'Chế độ bán hàng' },
    { key: 'F3', label: 'Tìm hàng hóa', section: 'Thao tác chung' },
    { key: 'F4', label: 'Tìm khách hàng', section: 'Thao tác chung' },
];

const ShortcutsModal = ({ open, onClose }) => {
    // Group shortcuts by section
    const groupedShortcuts = SHORTCUTS.reduce((acc, curr) => {
        if (!acc[curr.section]) {
            acc[curr.section] = [];
        }
        acc[curr.section].push(curr);
        return acc;
    }, {});

    return (
        <Modal
            title="Danh sách phím tắt"
            open={open}
            onCancel={onClose}
            footer={null}
            width={600}
        >
            <div className={styles.modalContent}>
                {Object.entries(groupedShortcuts).map(([section, items]) => (
                    <div key={section}>
                        <div className={styles.sectionTitle}>{section}</div>
                        <div className={styles.shortcutGrid}>
                            {items.map((item) => (
                                <div key={item.key} className={styles.shortcutItem}>
                                    <span className={styles.shortcutLabel}>{item.label}</span>
                                    <span className={styles.shortcutKey}>{item.key}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </Modal>
    );
};

export default ShortcutsModal;

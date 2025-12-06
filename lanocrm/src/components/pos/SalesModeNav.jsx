import React from 'react';
import { Button } from 'antd';
import {
    ThunderboltOutlined,
    ClockCircleOutlined,
    CarOutlined,
} from '@ant-design/icons';
import styles from './SalesModeNav.module.css';

const SALE_MODES = [
    {
        key: 'quick',
        label: 'Bán nhanh',
        icon: <ThunderboltOutlined />,
        shortcut: 'F1',
    },
    {
        key: 'regular',
        label: 'Bán thường',
        icon: <ClockCircleOutlined />,
        shortcut: 'F2',
    },
    {
        key: 'delivery',
        label: 'Bán giao hàng',
        icon: <CarOutlined />,
        shortcut: 'F5',
    },
];

const SalesModeNav = ({
    currentMode,
    onModeChange,
    onPayment,
    totals,
}) => {
    // Keyboard shortcuts
    React.useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'F1') {
                e.preventDefault();
                onModeChange('quick');
            } else if (e.key === 'F2') {
                e.preventDefault();
                onModeChange('regular');
            } else if (e.key === 'F5') {
                e.preventDefault();
                onModeChange('delivery');
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [onModeChange]);

    return (
        <footer className={styles.footer}>
            {/* Sale Mode Tabs */}
            <nav className={styles.modeTabs}>
                {SALE_MODES.map((mode) => (
                    <button
                        key={mode.key}
                        className={`${styles.modeTab} ${currentMode === mode.key ? styles.active : ''}`}
                        onClick={() => onModeChange(mode.key)}
                    >
                        <span className={styles.modeIcon}>{mode.icon}</span>
                        <span className={styles.modeLabel}>{mode.label}</span>
                    </button>
                ))}
            </nav>

            {/* Spacer */}
            <div className={styles.spacer} />

            {/* Payment Button (shown in Regular mode) */}
            {currentMode === 'regular' && (
                <Button
                    type="primary"
                    size="large"
                    className={styles.paymentBtn}
                    onClick={onPayment}
                >
                    THANH TOÁN
                </Button>
            )}
        </footer>
    );
};

export default SalesModeNav;

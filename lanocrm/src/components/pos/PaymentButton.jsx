import React from 'react';
import { Button } from 'antd';
import styles from './PaymentButton.module.css';

/**
 * Shared Payment Button component used across all sale modes.
 * Displays a single "THANH TOÁN" button at the bottom of the active panel.
 */
const PaymentButton = ({ onClick, className }) => {
    return (
        <div className={`${styles.paymentSection} ${className || ''}`}>
            <Button
                type="primary"
                size="large"
                className={styles.payBtn}
                onClick={onClick}
                block
            >
                THANH TOÁN
            </Button>
        </div>
    );
};

export default PaymentButton;

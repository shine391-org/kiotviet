import React, { useState } from 'react';
import { Modal, Input, Button, Select } from 'antd';
import { DeleteOutlined, PlusOutlined } from '@ant-design/icons';
import styles from './CombinedPaymentModal.module.css';

const PAYMENT_METHODS = [
    { key: 'cash', label: 'Tiền mặt' },
    { key: 'transfer', label: 'Chuyển khoản' },
    { key: 'card', label: 'Thẻ' },
    { key: 'wallet', label: 'Ví' },
    { key: 'voucher', label: 'Voucher' },
];

const CombinedPaymentModal = ({
    open,
    onClose,
    customerPay,
    onConfirm,
}) => {
    const [inputAmount, setInputAmount] = useState(0);
    const [payments, setPayments] = useState([]);

    // Calculate total paid and remaining
    const totalPaid = payments.reduce((sum, p) => sum + p.amount, 0);
    const remaining = customerPay - totalPaid;

    const handleAddPayment = (method) => {
        if (inputAmount <= 0) return;
        setPayments((prev) => [
            ...prev,
            { id: Date.now(), method, amount: inputAmount },
        ]);
        setInputAmount(0);
    };

    const handleRemovePayment = (id) => {
        setPayments((prev) => prev.filter((p) => p.id !== id));
    };

    const handleConfirm = () => {
        onConfirm(payments);
        onClose();
    };

    const handleSkip = () => {
        onClose();
    };

    const getMethodLabel = (key) => {
        return PAYMENT_METHODS.find((m) => m.key === key)?.label || key;
    };

    return (
        <Modal
            title="Thanh toán nhiều phương thức"
            open={open}
            onCancel={onClose}
            footer={null}
            width={500}
            className={styles.modal}
        >
            {/* Amount Input */}
            <div className={styles.inputSection}>
                <span className={styles.label}>Số tiền</span>
                <span className={styles.inputValue}>{inputAmount.toLocaleString('vi-VN')}</span>
            </div>

            <div className={styles.inputRow}>
                <Input
                    type="number"
                    value={inputAmount || ''}
                    onChange={(e) => setInputAmount(parseInt(e.target.value, 10) || 0)}
                    className={styles.amountInput}
                    placeholder="0"
                />
            </div>

            {/* Payment Method Buttons */}
            <div className={styles.methodButtons}>
                {PAYMENT_METHODS.map((method) => (
                    <Button
                        key={method.key}
                        className={styles.methodBtn}
                        onClick={() => handleAddPayment(method.key)}
                    >
                        {method.label}
                    </Button>
                ))}
            </div>

            {/* Customer Pay Amount */}
            <div className={styles.summaryRow}>
                <span>Khách cần trả</span>
                <span className={styles.payAmount}>{(customerPay || 0).toLocaleString('vi-VN')}</span>
            </div>

            {/* Added Payments List */}
            {payments.map((payment) => (
                <div key={payment.id} className={styles.paymentItem}>
                    <Button
                        type="text"
                        icon={<DeleteOutlined />}
                        className={styles.deleteBtn}
                        onClick={() => handleRemovePayment(payment.id)}
                    />
                    <span className={styles.paymentMethod}>{getMethodLabel(payment.method)}</span>
                    <span className={styles.paymentAmount}>
                        {payment.amount.toLocaleString('vi-VN')}
                    </span>
                </div>
            ))}

            {/* Total Customer Payment */}
            <div className={styles.totalRow}>
                <span>Khách thanh toán</span>
                <span className={styles.totalAmount}>{totalPaid.toLocaleString('vi-VN')}</span>
            </div>

            {/* Change Amount */}
            {remaining < 0 && (
                <div className={styles.changeRow}>
                    <span>Tiền thừa trả khách</span>
                    <span className={styles.changeAmount}>
                        {Math.abs(remaining).toLocaleString('vi-VN')}
                    </span>
                </div>
            )}

            {/* Action Buttons */}
            <div className={styles.actions}>
                <Button onClick={handleSkip} className={styles.skipBtn}>
                    Bỏ qua
                </Button>
                <Button type="primary" onClick={handleConfirm} className={styles.confirmBtn}>
                    Xong
                </Button>
            </div>
        </Modal>
    );
};

export default CombinedPaymentModal;

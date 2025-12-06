import React, { useState, useEffect } from 'react';
import { Input, Button, Radio, Tooltip, Dropdown, Select } from 'antd';
import {
    StarOutlined,
    MoreOutlined,
    QrcodeOutlined,
    BankOutlined,
} from '@ant-design/icons';
import CombinedPaymentModal from './CombinedPaymentModal';
import CustomerHeader from './CustomerHeader';
import styles from './PaymentPanel.module.css';

const PaymentPanel = ({
    customer,
    onCustomerChange,
    totals,
    onPayment,
}) => {
    const [paymentMethod, setPaymentMethod] = useState('cash');
    const [customerPayAmount, setCustomerPayAmount] = useState(totals.customerPay);
    const [showCombinedModal, setShowCombinedModal] = useState(false);

    // Sync customerPayAmount with totals
    useEffect(() => {
        setCustomerPayAmount(totals.customerPay);
    }, [totals.customerPay]);

    // F4 shortcut for customer search
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'F4') {
                e.preventDefault();
                customerSearchRef.current?.focus();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

    // Quick payment amounts - unique and sorted
    const baseAmounts = [
        totals.customerPay,
        Math.ceil(totals.customerPay / 10000) * 10000,
        Math.ceil(totals.customerPay / 50000) * 50000,
        Math.ceil(totals.customerPay / 100000) * 100000,
    ];
    const quickAmounts = [...new Set(baseAmounts)].slice(0, 4);

    const handleQuickAmount = (amount) => {
        setCustomerPayAmount(amount);
    };

    const handleCombinedPayment = (payments) => {
        console.log('Combined payments:', payments);
        onPayment();
    };

    // Dropdown menu for more payment options
    const moreMenuItems = [
        {
            key: 'combined',
            label: 'Thanh toán kết hợp',
            onClick: () => setShowCombinedModal(true),
        },
    ];

    return (
        <div className={styles.paymentPanel}>
            {/* Customer Info Section - using shared CustomerHeader */}
            <div className={styles.customerSection}>
                <CustomerHeader
                    customer={customer}
                    onCustomerChange={onCustomerChange}
                    showDateTime={true}
                />
            </div>

            {/* Order Summary */}
            <div className={styles.orderSummary}>
                <div className={styles.summaryRow}>
                    <span className={styles.summaryLabel}>Tổng tiền hàng</span>
                    <span className={styles.itemCount}>{totals.itemCount}</span>
                    <span className={styles.summaryValue}>{totals.subtotal.toLocaleString('vi-VN')}</span>
                </div>
                <div className={styles.summaryRow}>
                    <span className={styles.summaryLabel}>Giảm giá</span>
                    <Input
                        value={totals.discount}
                        variant="borderless"
                        className={styles.editableValue}
                        readOnly
                    />
                </div>
                <div className={styles.summaryRow}>
                    <span className={styles.summaryLabel}>Thu khác</span>
                    <Input
                        value={totals.otherFees}
                        variant="borderless"
                        className={styles.editableValue}
                        readOnly
                    />
                </div>
                <div className={`${styles.summaryRow} ${styles.customerPayRow}`}>
                    <span className={styles.summaryLabel}>Khách cần trả</span>
                    <span className={styles.customerPayValue}>{totals.customerPay.toLocaleString('vi-VN')}</span>
                </div>
            </div>

            {/* Payment Section */}
            <div className={styles.paymentSection}>
                {/* Customer Payment Amount */}
                <div className={styles.paymentAmountRow}>
                    <span className={styles.summaryLabel}>Khách thanh toán</span>
                    <Input
                        value={customerPayAmount.toLocaleString('vi-VN')}
                        onChange={(e) => {
                            const val = parseInt(e.target.value.replace(/\D/g, ''), 10) || 0;
                            setCustomerPayAmount(val);
                        }}
                        className={styles.paymentAmountInput}
                    />
                </div>

                {/* Payment Methods - aligned in row */}
                <div className={styles.paymentMethodsRow}>
                    <Radio.Group
                        value={paymentMethod}
                        onChange={(e) => setPaymentMethod(e.target.value)}
                        className={styles.paymentMethods}
                        buttonStyle="solid"
                    >
                        <Radio.Button value="cash" className={styles.methodBtn}>Tiền mặt</Radio.Button>
                        <Radio.Button value="transfer" className={styles.methodBtn}>Chuyển khoản</Radio.Button>
                        <Radio.Button value="card" className={styles.methodBtn}>Thẻ</Radio.Button>
                        <Radio.Button value="wallet" className={styles.methodBtn}>Ví</Radio.Button>
                    </Radio.Group>
                    <Dropdown
                        menu={{ items: moreMenuItems }}
                        trigger={['click']}
                        placement="bottomRight"
                    >
                        <Button
                            type="text"
                            icon={<MoreOutlined />}
                            className={styles.morePaymentBtn}
                        />
                    </Dropdown>
                </div>

                {/* Dynamic content based on payment method */}
                <div className={styles.paymentMethodContent}>
                    {paymentMethod === 'cash' && (
                        <div className={styles.quickAmounts}>
                            {quickAmounts.map((amount, idx) => (
                                <Button
                                    key={idx}
                                    type={customerPayAmount === amount ? 'primary' : 'default'}
                                    onClick={() => handleQuickAmount(amount)}
                                    className={styles.quickAmountBtn}
                                >
                                    {amount.toLocaleString('vi-VN')}
                                </Button>
                            ))}
                        </div>
                    )}

                    {paymentMethod === 'transfer' && (
                        <div className={styles.transferContent}>
                            <div className={styles.qrSection}>
                                <div className={styles.qrCode}>
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=BIDV-2206331765" alt="QR Code" />
                                </div>
                                <div className={styles.bankInfo}>
                                    <Select
                                        defaultValue="bidv"
                                        className={styles.bankSelect}
                                        options={[
                                            { value: 'bidv', label: 'BIDV - 2206331765 - NGUYEN THI ...' },
                                            { value: 'vcb', label: 'VCB - 1234567890 - NGUYEN THI ...' },
                                        ]}
                                    />
                                    <Button type="link" className={styles.showQrBtn}>
                                        📱 Hiện mã QR
                                    </Button>
                                </div>
                            </div>
                        </div>
                    )}

                    {paymentMethod === 'card' && (
                        <div className={styles.cardContent}>
                            <Select
                                placeholder="Chọn tài khoản ngân hàng"
                                className={styles.fullWidth}
                                options={[
                                    { value: 'bidv', label: 'BIDV - 2206331765 - NGUYEN THI PHUONG ANH' },
                                    { value: 'vcb', label: 'VCB - 1234567890 - NGUYEN THI PHUONG ANH' },
                                ]}
                            />
                        </div>
                    )}

                    {paymentMethod === 'wallet' && (
                        <div className={styles.walletContent}>
                            <div className={styles.emptyWallet}>
                                <p>Bạn chưa có tài khoản ví điện tử</p>
                                <Button type="link" className={styles.addWalletBtn}>
                                    + Thêm ví
                                </Button>
                            </div>
                        </div>
                    )}
                </div>

                {/* Change Amount */}
                {customerPayAmount > totals.customerPay && (
                    <div className={styles.changeRow}>
                        <span className={styles.changeLabel}>Tiền thừa trả khách</span>
                        <span className={styles.changeValue}>
                            {(customerPayAmount - totals.customerPay).toLocaleString('vi-VN')}
                        </span>
                    </div>
                )}
            </div>

            {/* Payment Button */}
            <Button
                type="primary"
                size="large"
                block
                className={styles.payButton}
                onClick={onPayment}
            >
                THANH TOÁN
            </Button>

            {/* Modals */}
            <CombinedPaymentModal
                open={showCombinedModal}
                onClose={() => setShowCombinedModal(false)}
                customerPay={totals.customerPay}
                onConfirm={handleCombinedPayment}
            />
        </div>
    );
};

export default PaymentPanel;

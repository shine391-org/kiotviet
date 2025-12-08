import React, { useState, useEffect, useCallback } from 'react';
import { Input, Button, Radio, Tooltip, Dropdown, Select, Spin } from 'antd';
import {
    StarOutlined,
    MoreOutlined,
    QrcodeOutlined,
    BankOutlined,
} from '@ant-design/icons';
import CombinedPaymentModal from './CombinedPaymentModal';
import CustomerHeader from './CustomerHeader';
import PaymentButton from './PaymentButton';
import bankAccountApi from '../../api/bankAccountApi';
import styles from './PaymentPanel.module.css';

const PaymentPanel = ({
    customer,
    onCustomerChange,
    totals,
    onPayment,
    loading = false,
}) => {
    const [paymentMethod, setPaymentMethod] = useState('cash');
    const [customerPayAmount, setCustomerPayAmount] = useState(totals.customerPay);
    const [showCombinedModal, setShowCombinedModal] = useState(false);
    const [bankAccounts, setBankAccounts] = useState([]);
    const [selectedBankAccount, setSelectedBankAccount] = useState(null);
    const [bankAccountsLoading, setBankAccountsLoading] = useState(false);
    const [qrCode, setQrCode] = useState(null);

    // Fetch bank accounts from API
    const fetchBankAccounts = useCallback(async () => {
        setBankAccountsLoading(true);
        try {
            const response = await bankAccountApi.getAll({ is_active: 1 });
            if (response.data?.success && response.data?.data) {
                const accounts = response.data.data.map(acc => ({
                    value: acc.id,
                    label: `${acc.bank_name} - ${acc.account_number} - ${acc.account_holder}`,
                }));
                setBankAccounts(accounts);
                if (accounts.length > 0 && !selectedBankAccount) {
                    setSelectedBankAccount(accounts[0].value);
                }
            }
        } catch (error) {
            console.error('Failed to fetch bank accounts:', error);
        } finally {
            setBankAccountsLoading(false);
        }
    }, [selectedBankAccount]);

    // Fetch QR code for selected bank account
    const [qrLoading, setQrLoading] = useState(false);
    const fetchQrCode = useCallback(async () => {
        if (!selectedBankAccount) {
            console.warn('No bank account selected');
            return;
        }
        if (totals.customerPay <= 0) {
            console.warn('Customer pay is 0, cannot generate QR');
            return;
        }

        setQrLoading(true);
        try {
            const response = await bankAccountApi.getQR(
                selectedBankAccount,
                totals.customerPay,
                'Thanh toan POS'
            );
            console.log('QR Response:', response);
            // Response from axios: response.data = backend response
            const result = response.data || response;
            if (result?.success && result?.data?.qr_url) {
                setQrCode(result.data.qr_url);
            } else if (result?.qr_url) {
                setQrCode(result.qr_url);
            }
        } catch (error) {
            console.error('Failed to fetch QR code:', error);
        } finally {
            setQrLoading(false);
        }
    }, [selectedBankAccount, totals.customerPay]);

    useEffect(() => {
        fetchBankAccounts();
    }, []);

    useEffect(() => {
        if (paymentMethod === 'transfer' && selectedBankAccount) {
            fetchQrCode();
        }
    }, [paymentMethod, selectedBankAccount, totals.customerPay, fetchQrCode]);

    // Sync customerPayAmount with totals
    useEffect(() => {
        setCustomerPayAmount(totals.customerPay);
    }, [totals.customerPay]);


    // Note: F4 shortcut for customer search is handled in CustomerHeader component

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
        // Pass first payment method for combined payments
        onPayment?.(payments[0]?.payment_method?.toUpperCase() || 'CASH');
    };

    const handlePaymentClick = () => {
        // Pass current payment method to parent, uppercase for backend
        onPayment?.(paymentMethod.toUpperCase());
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
                            {bankAccountsLoading ? (
                                <div className={styles.loadingContainer}><Spin size="small" /></div>
                            ) : bankAccounts.length > 0 ? (
                                <div className={styles.qrSection}>
                                    <div className={styles.qrCode}>
                                        {qrLoading ? (
                                            <Spin size="large" />
                                        ) : qrCode ? (
                                            <img src={qrCode} alt="QR Code" />
                                        ) : (
                                            <QrcodeOutlined style={{ fontSize: 80, color: '#ccc' }} />
                                        )}
                                    </div>
                                    <div className={styles.bankInfo}>
                                        <Select
                                            value={selectedBankAccount}
                                            onChange={(value) => {
                                                setSelectedBankAccount(value);
                                                setQrCode(null); // Reset QR when bank changes
                                            }}
                                            className={styles.bankSelect}
                                            options={bankAccounts}
                                        />
                                        <Button
                                            type="primary"
                                            className={styles.showQrBtn}
                                            onClick={fetchQrCode}
                                            loading={qrLoading}
                                            disabled={!selectedBankAccount || totals.customerPay <= 0}
                                        >
                                            📱 Tạo mã QR
                                        </Button>
                                        {totals.customerPay <= 0 && (
                                            <span className={styles.qrHint}>Thêm sản phẩm để tạo QR</span>
                                        )}
                                    </div>
                                </div>
                            ) : (
                                <div className={styles.emptyWallet}>
                                    <p>Chưa có tài khoản ngân hàng</p>
                                    <Button type="link">+ Thêm tài khoản</Button>
                                </div>
                            )}
                        </div>
                    )}

                    {paymentMethod === 'card' && (
                        <div className={styles.cardContent}>
                            <Select
                                placeholder="Chọn tài khoản ngân hàng"
                                className={styles.fullWidth}
                                value={selectedBankAccount}
                                onChange={setSelectedBankAccount}
                                options={bankAccounts}
                                loading={bankAccountsLoading}
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
            <PaymentButton onClick={handlePaymentClick} loading={loading} />

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

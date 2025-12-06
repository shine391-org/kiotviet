import React, { useState, useCallback } from 'react';
import { App } from 'antd';
import SalesHeader from '../../components/pos/SalesHeader';
import CartPanel from '../../components/pos/CartPanel';
import PaymentPanel from '../../components/pos/PaymentPanel';
import ProductGrid from '../../components/pos/ProductGrid';
import ShippingForm from '../../components/pos/ShippingForm';
import DeliveryPartnersPanel from '../../components/pos/DeliveryPartnersPanel';
import SalesModeNav from '../../components/pos/SalesModeNav';
import PaymentButton from '../../components/pos/PaymentButton';
import styles from './SalesPage.module.css';

/**
 * Sale Mode Types:
 * - 'quick': Bán nhanh - Simple payment panel
 * - 'regular': Bán thường - Product grid + payment
 * - 'delivery': Bán giao hàng - Shipping form + payment
 */
const SALE_MODES = {
    QUICK: 'quick',
    REGULAR: 'regular',
    DELIVERY: 'delivery',
};

const SalesPage = () => {
    const { message } = App.useApp();

    // Current sale mode
    const [saleMode, setSaleMode] = useState(SALE_MODES.QUICK);

    // Cart items state
    const [cartItems, setCartItems] = useState([
        // Mock data for initial development
        {
            id: 1,
            sku: 'SP000011',
            name: 'Túi Jeep vải loại nhỏ',
            quantity: 1,
            unitPrice: 550000,
            total: 550000,
        },
    ]);

    // Customer state
    const [customer, setCustomer] = useState(null);

    // Order notes
    const [orderNote, setOrderNote] = useState('');

    // Tabs (invoices and orders)
    const [tabs, setTabs] = useState([
        { id: 1, type: 'invoice', label: 'Hóa đơn 1' },
    ]);
    const [activeTabId, setActiveTabId] = useState(1);

    // Counter for unique IDs
    const [invoiceCounter, setInvoiceCounter] = useState(1);
    const [orderCounter, setOrderCounter] = useState(0);

    // Delivery partners panel visibility
    const [showDeliveryPanel, setShowDeliveryPanel] = useState(true);
    const [selectedDeliveryPartner, setSelectedDeliveryPartner] = useState(null);

    // Calculate totals
    const totals = {
        itemCount: cartItems.reduce((sum, item) => sum + item.quantity, 0),
        subtotal: cartItems.reduce((sum, item) => sum + item.total, 0),
        discount: 0,
        otherFees: 0,
        get customerPay() {
            return this.subtotal - this.discount + this.otherFees;
        },
    };

    // Handlers
    const handleModeChange = useCallback((mode) => {
        setSaleMode(mode);
    }, []);

    const handleAddProduct = useCallback((product) => {
        setCartItems((prev) => {
            const existing = prev.find((item) => item.sku === product.sku);
            if (existing) {
                return prev.map((item) =>
                    item.sku === product.sku
                        ? { ...item, quantity: item.quantity + 1, total: (item.quantity + 1) * item.unitPrice }
                        : item
                );
            }
            return [...prev, { ...product, quantity: 1, total: product.unitPrice }];
        });
    }, []);

    const handleUpdateQuantity = useCallback((itemId, delta) => {
        setCartItems((prev) =>
            prev
                .map((item) => {
                    if (item.id !== itemId) return item;
                    const newQty = Math.max(0, item.quantity + delta);
                    return { ...item, quantity: newQty, total: newQty * item.unitPrice };
                })
                .filter((item) => item.quantity > 0)
        );
    }, []);

    const handleRemoveItem = useCallback((itemId) => {
        setCartItems((prev) => prev.filter((item) => item.id !== itemId));
    }, []);

    const handlePayment = useCallback(() => {
        message.success('Thanh toán thành công!');
        setCartItems([]);
    }, [message]);

    const handleNewTab = useCallback((type) => {
        const newId = Math.max(...tabs.map((t) => t.id), 0) + 1;
        let label;
        if (type === 'order') {
            const newOrderNum = orderCounter + 1;
            setOrderCounter(newOrderNum);
            label = `Đặt hàng ${newOrderNum}`;
        } else {
            const newInvoiceNum = invoiceCounter + 1;
            setInvoiceCounter(newInvoiceNum);
            label = `Hóa đơn ${newInvoiceNum}`;
        }
        setTabs((prev) => [...prev, { id: newId, type, label }]);
        setActiveTabId(newId);
        setCartItems([]);
    }, [tabs, invoiceCounter, orderCounter]);

    const handleCloseTab = useCallback((tabId) => {
        if (tabs.length === 1) {
            message.warning('Cần giữ ít nhất 1 tab');
            return;
        }
        setTabs((prev) => prev.filter((t) => t.id !== tabId));
        if (activeTabId === tabId) {
            const remaining = tabs.filter((t) => t.id !== tabId);
            setActiveTabId(remaining[0]?.id || 1);
        }
    }, [tabs, activeTabId, message]);

    // Render right panel based on mode
    const renderRightPanel = () => {
        switch (saleMode) {
            case SALE_MODES.QUICK:
                return (
                    <PaymentPanel
                        customer={customer}
                        onCustomerChange={setCustomer}
                        totals={totals}
                        onPayment={handlePayment}
                    />
                );

            case SALE_MODES.REGULAR:
                return (
                    <div className={styles.regularModeLayout}>
                        <ProductGrid onAddProduct={handleAddProduct} />
                        <div className={styles.regularPaymentSummary}>
                            <span>Tổng tiền hàng</span>
                            <span className={styles.itemCount}>{totals.itemCount}</span>
                            <span className={styles.totalAmount}>{totals.subtotal.toLocaleString('vi-VN')}</span>
                        </div>
                        <PaymentButton onClick={handlePayment} />
                    </div>
                );

            case SALE_MODES.DELIVERY:
                return (
                    <ShippingForm
                        customer={customer}
                        onCustomerChange={setCustomer}
                        totals={totals}
                        showDeliveryPanel={showDeliveryPanel}
                        onToggleDeliveryPanel={() => setShowDeliveryPanel(!showDeliveryPanel)}
                        onPayment={handlePayment}
                    />
                );

            default:
                return null;
        }
    };

    return (
        <div className={styles.salesPage}>
            {/* Header */}
            <SalesHeader
                tabs={tabs}
                activeTabId={activeTabId}
                onTabChange={setActiveTabId}
                onNewTab={handleNewTab}
                onCloseTab={handleCloseTab}
            />

            {/* Main Content */}
            <div className={`${styles.mainContent} ${saleMode === SALE_MODES.DELIVERY ? styles.deliveryMode : ''} ${showDeliveryPanel && saleMode === SALE_MODES.DELIVERY ? styles.withDeliveryPanel : ''}`}>
                {/* Left: Cart Panel */}
                <div className={styles.leftPanel}>
                    <CartPanel
                        items={cartItems}
                        saleMode={saleMode}
                        onUpdateQuantity={handleUpdateQuantity}
                        onRemoveItem={handleRemoveItem}
                        orderNote={orderNote}
                        onNoteChange={setOrderNote}
                        totals={totals}
                    />
                </div>

                {/* Middle/Right: Dynamic Panel */}
                <div className={styles.rightPanel}>
                    {renderRightPanel()}
                </div>

                {/* Delivery Partners Panel (only in delivery mode) */}
                {saleMode === SALE_MODES.DELIVERY && (
                    <DeliveryPartnersPanel
                        visible={showDeliveryPanel}
                        selectedPartner={selectedDeliveryPartner}
                        onSelectPartner={setSelectedDeliveryPartner}
                        onClose={() => setShowDeliveryPanel(false)}
                        onPayment={handlePayment}
                    />
                )}
            </div>

            {/* Footer Navigation */}
            <SalesModeNav
                currentMode={saleMode}
                onModeChange={handleModeChange}
                onPayment={handlePayment}
                totals={totals}
            />
        </div>
    );
};

export default SalesPage;

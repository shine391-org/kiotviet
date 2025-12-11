import React, { useState, useCallback } from 'react';
import { useSelector } from 'react-redux';
import { App } from 'antd';
import { selectUser } from '../../store/slices/authSlice';
import posApi from '../../api/posApi';
import SalesHeader from '../../components/pos/SalesHeader';
import CartPanel from '../../components/pos/CartPanel';
import PaymentPanel from '../../components/pos/PaymentPanel';
import ProductGrid from '../../components/pos/ProductGrid';
import ShippingForm from '../../components/pos/ShippingForm';
import DeliveryPartnersPanel from '../../components/pos/DeliveryPartnersPanel';
import SalesModeNav from '../../components/pos/SalesModeNav';
import PaymentButton from '../../components/pos/PaymentButton';
import CustomerHeader from '../../components/pos/CustomerHeader';
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
    const currentUser = useSelector(selectUser);

    // Current sale mode
    const [saleMode, setSaleMode] = useState(SALE_MODES.QUICK);

    // Cart items state - start empty
    const [cartItems, setCartItems] = useState([]);

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

    // Payment state
    const [paymentLoading, setPaymentLoading] = useState(false);
    const [paymentMethod, setPaymentMethod] = useState('CASH');

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
        console.log('🔵 handleAddProduct called with:', product);

        // Normalize product structure (SalesHeader uses code/price, ProductGrid uses sku/unitPrice)
        const normalizedProduct = {
            id: product.id,
            sku: product.sku || product.code,
            name: product.name,
            unitPrice: product.unitPrice || product.price || 0,
            variantId: product.variantId || product.variant_id || null,
            image: product.image || null,
        };

        console.log('🔵 Normalized product:', normalizedProduct);

        setCartItems((prev) => {
            const existing = prev.find((item) => item.sku === normalizedProduct.sku);
            if (existing) {
                return prev.map((item) =>
                    item.sku === normalizedProduct.sku
                        ? { ...item, quantity: item.quantity + 1, total: (item.quantity + 1) * item.unitPrice }
                        : item
                );
            }
            return [...prev, { ...normalizedProduct, quantity: 1, total: normalizedProduct.unitPrice }];
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

    const handlePayment = useCallback(async (method) => {
        // Use passed method if it's a valid string, otherwise fallback to paymentMethod state
        // This handles cases where React event objects are passed (e.g., from direct button onClick)
        const finalMethod = (typeof method === 'string' && method) ? method : paymentMethod;

        if (cartItems.length === 0) {
            message.warning('Vui lòng thêm sản phẩm vào giỏ hàng');
            return;
        }

        setPaymentLoading(true);
        try {
            const itemsPayload = cartItems.map(item => ({
                product_id: item.id,
                variant_id: item.variantId || null,
                quantity: item.quantity,
            }));

            // Step 1: Call preview to get exact backend-calculated total
            const previewResponse = await posApi.previewSale({
                customer_id: customer?.id || null,
                items: itemsPayload,
            });

            if (!previewResponse.success) {
                message.error(previewResponse.message || 'Không thể xem trước đơn hàng');
                return;
            }

            const backendTotal = previewResponse.data.total;

            // Step 2: Create order with backend-calculated total
            const payload = {
                order_type: 'pos',
                branch_id: currentUser?.branch_id || 1,
                user_id: currentUser?.id || 1,
                customer_id: customer?.id || null,
                items: itemsPayload,
                payments: [{
                    payment_method: finalMethod,
                    amount: backendTotal, // Use backend-calculated total
                }],
                notes: orderNote || null,
            };

            console.log('🔵 Creating order with payload:', payload);
            const response = await posApi.createSale(payload);

            if (response.success) {
                message.success(`Thanh toán thành công! Đơn hàng: ${response.data?.order_number || 'N/A'}`);
                setCartItems([]);
                setOrderNote('');
                setCustomer(null);
            } else {
                console.error('Order creation failed:', response);
                message.error(response.message || response.error || 'Không thể tạo đơn hàng');
            }
        } catch (error) {
            console.error('Payment error:', error);
            console.error('Error response:', error.response?.data);

            // Extract error message from various possible locations
            const errorData = error.response?.data;
            let errorMessage = 'Lỗi khi thanh toán';

            if (errorData) {
                // Try different error locations
                if (errorData.messages?.error) {
                    errorMessage = errorData.messages.error;
                } else if (errorData.message) {
                    errorMessage = errorData.message;
                } else if (typeof errorData === 'string') {
                    errorMessage = errorData;
                }
            } else if (error.message) {
                errorMessage = error.message;
            }

            console.error('Validation error details:', errorMessage);
            message.error(errorMessage);
        } finally {
            setPaymentLoading(false);
        }
    }, [cartItems, customer, currentUser, orderNote, paymentMethod, message]);

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
                        loading={paymentLoading}
                    />
                );

            case SALE_MODES.REGULAR:
                return (
                    <div className={styles.regularModeLayout}>
                        <CustomerHeader
                            customer={customer}
                            onCustomerChange={setCustomer}
                        />
                        <ProductGrid onAddProduct={handleAddProduct} />
                        <div className={styles.regularPaymentSummary}>
                            <span>Tổng tiền hàng</span>
                            <span className={styles.itemCount}>{totals.itemCount}</span>
                            <span className={styles.totalAmount}>{totals.subtotal.toLocaleString('vi-VN')}</span>
                        </div>
                        <PaymentButton onClick={handlePayment} loading={paymentLoading} disabled={cartItems.length === 0} />
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
                onAddProduct={handleAddProduct}
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

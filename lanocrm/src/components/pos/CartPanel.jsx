import React from 'react';
import { Input, Button, Tooltip } from 'antd';
import {
    DeleteOutlined,
    PlusOutlined,
    MinusOutlined,
    MoreOutlined,
    EditOutlined,
} from '@ant-design/icons';
import styles from './CartPanel.module.css';

const CartPanel = ({
    items = [],
    saleMode,
    onUpdateQuantity,
    onRemoveItem,
    orderNote = '',
    onNoteChange,
    totals = { itemCount: 0, subtotal: 0, discount: 0, otherFees: 0, customerPay: 0 },
}) => {
    const isDeliveryMode = saleMode === 'delivery';
    const isRegularMode = saleMode === 'regular';

    return (
        <div className={styles.cartPanel}>
            {/* Cart Items List */}
            <div className={styles.itemsList}>
                {items.length === 0 ? (
                    <div className={styles.emptyState}>
                        <p>Chưa có sản phẩm nào</p>
                        <span>Tìm kiếm hoặc quét mã vạch để thêm sản phẩm</span>
                    </div>
                ) : (
                    items.map((item, index) => (
                        <div key={item.id} className={styles.cartItem}>
                            <div className={styles.itemIndex}>{index + 1}</div>

                            <Tooltip title="Xóa sản phẩm">
                                <Button
                                    type="text"
                                    icon={<DeleteOutlined />}
                                    size="small"
                                    className={styles.deleteBtn}
                                    onClick={() => onRemoveItem?.(item.id)}
                                />
                            </Tooltip>

                            <div className={styles.itemInfo}>
                                <span className={styles.itemSku}>{item.sku}</span>
                                <span className={styles.itemName}>{item.name}</span>
                            </div>

                            {/* Quantity Controls */}
                            <div className={styles.quantitySection}>
                                {isRegularMode ? (
                                    <div className={styles.quantityWithButtons}>
                                        <Button
                                            type="text"
                                            icon={<MinusOutlined />}
                                            size="small"
                                            onClick={() => onUpdateQuantity?.(item.id, -1)}
                                        />
                                        <Input
                                            value={item.quantity}
                                            className={styles.quantityInput}
                                            readOnly
                                        />
                                        <Button
                                            type="text"
                                            icon={<PlusOutlined />}
                                            size="small"
                                            onClick={() => onUpdateQuantity?.(item.id, 1)}
                                        />
                                    </div>
                                ) : (
                                    <Input
                                        value={item.quantity}
                                        className={styles.quantityInputSimple}
                                        onChange={(e) => {
                                            // Parse existing quantity (may be string or number)
                                            const parsedOld = parseInt(item.quantity, 10);
                                            const oldQty = Number.isFinite(parsedOld) ? parsedOld : 0;
                                            const newVal = parseInt(e.target.value, 10) || 0;
                                            const delta = newVal - oldQty;
                                            onUpdateQuantity?.(item.id, delta);
                                        }}
                                    />
                                )}
                            </div>

                            <div className={styles.priceSection}>
                                <span className={styles.unitPrice}>
                                    {(item.unitPrice ?? 0).toLocaleString('vi-VN')}
                                </span>
                            </div>

                            <div className={styles.totalSection}>
                                <span className={styles.itemTotal}>
                                    {(item.total ?? 0).toLocaleString('vi-VN')}
                                </span>
                            </div>

                            <Button
                                type="text"
                                icon={<PlusOutlined />}
                                size="small"
                                className={styles.addBtn}
                                onClick={() => onUpdateQuantity?.(item.id, 1)}
                            />

                            <Button
                                type="text"
                                icon={<MoreOutlined />}
                                size="small"
                                className={styles.moreBtn}
                            />
                        </div>
                    ))
                )}
            </div>

            {/* Order Note */}
            <div className={styles.orderNoteSection}>
                <EditOutlined className={styles.noteIcon} />
                <Input
                    placeholder="Ghi chú đơn hàng"
                    value={orderNote}
                    onChange={(e) => onNoteChange?.(e.target.value)}
                    variant="borderless"
                    className={styles.noteInput}
                />
            </div>

            {/* Summary for Delivery Mode */}
            {isDeliveryMode && (
                <div className={styles.deliverySummary}>
                    <div className={styles.summaryRow}>
                        <span>Tổng tiền hàng</span>
                        <span className={styles.itemCount}>
                            {totals.itemCount ?? 0}
                        </span>
                        <span className={styles.summaryTotal}>
                            {(totals.subtotal ?? 0).toLocaleString('vi-VN')}
                        </span>
                    </div>
                    <div className={styles.summaryRow}>
                        <span>Giảm giá</span>
                        <span>{(totals.discount ?? 0).toLocaleString('vi-VN')}</span>
                    </div>
                    <div className={styles.summaryRow}>
                        <span>Thu khác</span>
                        <span>{(totals.otherFees ?? 0).toLocaleString('vi-VN')}</span>
                    </div>
                    <div className={`${styles.summaryRow} ${styles.customerPay}`}>
                        <span>Khách cần trả</span>
                        <span className={styles.payAmount}>
                            {(totals.customerPay ?? 0).toLocaleString('vi-VN')}
                        </span>
                    </div>
                </div>
            )}
        </div>
    );
};

export default CartPanel;

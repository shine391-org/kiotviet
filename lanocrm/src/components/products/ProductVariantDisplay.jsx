// src/components/products/ProductVariantDisplay.jsx

import React from 'react';
import { Button, Tooltip, Space } from 'antd';
import { EditOutlined, DeleteOutlined } from '@ant-design/icons';
import styles from './ProductVariantDisplay.module.css';

const ProductVariantDisplay = ({ 
  variants = [], 
  onEdit, 
  onDelete 
}) => {
  // Format currency
  const formatCurrency = (amount) => {
    if (!amount) return '0₫';
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND'
    }).format(amount);
  };

  // ✅ FIXED: Format variant name - Parse JSON attributes
  const formatVariantName = (variant) => {
    let attrs = variant.attributes;
    
    // Parse JSON string to object
    if (typeof attrs === 'string') {
      try {
        attrs = JSON.parse(attrs);
      } catch (e) {
        console.warn('Invalid JSON attributes:', attrs);
        attrs = {};
      }
    }
    
    // If no attributes, return variant name
    if (!attrs || typeof attrs !== 'object' || Object.keys(attrs).length === 0) {
      return variant.variant_name || 'Biến thể';
    }
    
    // Format: "Variant Name (Attr: Value, Attr: Value)"
    const attrStr = Object.entries(attrs)
      .map(([key, value]) => `${key}: ${value}`)
      .join(', ');
    
    return `${variant.variant_name} (${attrStr})`;
  };

  // Get stock status
  const getStockStatus = (quantity) => {
    if (quantity === 0 || quantity === null || quantity === undefined) {
      return 'outOfStock';
    }
    if (quantity < 10) return 'lowStock';
    return 'inStock';
  };

  // If no variants
  if (!variants || variants.length === 0) {
    return <div className={styles.empty}>Không có biến thể</div>;
  }

  return (
    <div className={styles.container}>
      <div className={styles.header}>
        <span className={styles.count}>{variants.length} biến thể</span>
      </div>

      <div className={styles.list}>
        {variants.map((variant, index) => (
          <div key={variant.id || index} className={styles.item}>
            {/* Index */}
            <span className={styles.index}>{index + 1}</span>

            {/* Info */}
            <div className={styles.info}>
              <span className={styles.name}>{formatVariantName(variant)}</span>
              <span className={styles.sku}>SKU: {variant.sku || 'N/A'}</span>
            </div>

            {/* Price */}
            <div className={styles.price}>
              <span className={styles.label}>Giá:</span>
              <span className={styles.value}>{formatCurrency(variant.price)}</span>
            </div>

            {/* Stock */}
            <div className={styles.stock}>
              <span className={styles.label}>Tồn:</span>
              {/* ✅ FIXED: Changed from variant.quantity to stock_quantity */}
              <span className={styles.value}>{variant.stock_quantity || 0}</span>
              <span className={`${styles.badge} ${styles[getStockStatus(variant.stock_quantity)]}`}>
                {getStockStatus(variant.stock_quantity) === 'inStock' && 'Còn'}
                {getStockStatus(variant.stock_quantity) === 'lowStock' && 'Sắp hết'}
                {getStockStatus(variant.stock_quantity) === 'outOfStock' && 'Hết'}
              </span>
            </div>

            {/* Actions */}
            <div className={styles.actions}>
              <Space size="small">
                {onEdit && (
                  <Tooltip title="Sửa biến thể">
                    <Button
                      type="text"
                      size="small"
                      icon={<EditOutlined />}
                      onClick={() => onEdit(variant)}
                    />
                  </Tooltip>
                )}
                {onDelete && (
                  <Tooltip title="Xóa biến thể">
                    <Button
                      type="text"
                      danger
                      size="small"
                      icon={<DeleteOutlined />}
                      onClick={() => onDelete(variant)}
                    />
                  </Tooltip>
                )}
              </Space>
            </div>

            {/* ✅ FIXED: Inactive badge - check status field */}
            {variant.status === 'inactive' && (
              <span className={styles.inactive}>Tắt</span>
            )}
          </div>
        ))}
      </div>
    </div>
  );
};

export default ProductVariantDisplay;
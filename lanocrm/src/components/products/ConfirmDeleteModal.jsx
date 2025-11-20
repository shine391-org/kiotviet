/**
 * Confirm Delete Modal for Products
 * @file src/components/products/ConfirmDeleteModal.jsx
 * @description Modal to confirm product deletion with warnings
 */

import React, { useState, useEffect } from 'react';
import { Modal, Form, Input, Button, Space, Spin, Alert, message } from 'antd';
import { ExclamationCircleOutlined, LoadingOutlined } from '@ant-design/icons';
import * as productApi from '../../api/productApi';
import styles from './ConfirmDeleteModal.module.css';

/**
 * ConfirmDeleteModal Component
 * @param {Object} props
 * @param {boolean} props.visible - Modal visibility
 * @param {Object} props.product - Product to delete
 * @param {Function} props.onConfirm - Callback on confirm
 * @param {Function} props.onCancel - Callback on cancel
 */
const ConfirmDeleteModal = ({ visible, product, onConfirm, onCancel }) => {
  const [loading, setLoading] = useState(false);
  const [deleteConfirmed, setDeleteConfirmed] = useState(false);
  const [deleteReason, setDeleteReason] = useState('');
  const [checkDependencies, setCheckDependencies] = useState(null);

  // ✅ FIXED: Move early return AFTER all hooks are declared
  // Check for product dependencies (orders, etc)
  const checkProductDependencies = async () => {
    try {
      setLoading(true);
      // TODO: Replace with actual API call to check dependencies
      // For now, mock data
      const dependencies = {
        sales_orders: 0,
        purchase_orders: 0,
        invoices: 0,
        variants: product?.variants_count || 0,
      };
      setCheckDependencies(dependencies);
    } catch (error) {
      message.error('Failed to check dependencies');
    } finally {
      setLoading(false);
    }
  };

  // ✅ FIXED: All hooks MUST be called at the top level, before any returns
  // Reset state when modal opens
  useEffect(() => {
    setDeleteConfirmed(false);
    setDeleteReason('');
  }, []);

  // Load dependencies when modal opens
  useEffect(() => {
    if (!visible || !product) return;
    checkProductDependencies();
  }, [visible, product]);

  // ✅ NOW it's safe to return early if no product
  if (!product) return null;

  const handleConfirmDelete = async () => {
    try {
      setLoading(true);
      
      // Call API with confirmation flag
      const response = await productApi.deleteProduct(product.id, true);
      
      if (response.success) {
        message.success('Sản phẩm đã được xóa thành công');
        onConfirm(product.id);
      } else {
        message.error(response.message || 'Failed to delete product');
      }
    } catch (error) {
      message.error(error.message || 'An error occurred');
    } finally {
      setLoading(false);
      setDeleteConfirmed(false);
      setDeleteReason('');
    }
  };

  const handleCancel = () => {
    setDeleteConfirmed(false);
    setDeleteReason('');
    onCancel();
  };

  const hasDependencies = checkDependencies && (
    checkDependencies.sales_orders > 0 ||
    checkDependencies.purchase_orders > 0 ||
    checkDependencies.invoices > 0 ||
    checkDependencies.variants > 0
  );

  return (
    <Modal
      title={
        <div className={styles.modalTitle}>
          <ExclamationCircleOutlined className={styles.iconWarning} />
          <span>Xác nhận xóa sản phẩm</span>
        </div>
      }
      open={visible}
      onCancel={handleCancel}
      footer={[
        <Button key="cancel" onClick={handleCancel} disabled={loading}>
          Hủy
        </Button>,
        <Button
          key="delete"
          type="primary"
          danger
          onClick={handleConfirmDelete}
          loading={loading}
          disabled={!deleteConfirmed}
        >
          Xác nhận xóa
        </Button>,
      ]}
      className={styles.modal}
      width={500}
    >
      <Spin spinning={loading} indicator={<LoadingOutlined />}>
        <div className={styles.content}>
          {/* Product Info */}
          <div className={styles.productInfo}>
            <h3>{product.name}</h3>
            <p>Mã hàng: <strong>{product.code}</strong></p>
            <p>Giá bán: <strong>{product.selling_price?.toLocaleString('vi-VN')} ₫</strong></p>
          </div>

          {/* Warning */}
          <Alert
            message="⚠️ Cảnh báo"
            description="Hành động này không thể hoàn tác. Sản phẩm sẽ được xóa vĩnh viễn."
            type="warning"
            showIcon
            className={styles.warning}
          />

          {/* Dependencies Check */}
          {checkDependencies && (
            <div className={styles.dependencies}>
              <h4>Kiểm tra liên kết:</h4>
              <ul>
                <li>
                  Đơn bán: <span className={checkDependencies.sales_orders > 0 ? styles.hasData : styles.noData}>
                    {checkDependencies.sales_orders}
                  </span>
                </li>
                <li>
                  Đơn mua: <span className={checkDependencies.purchase_orders > 0 ? styles.hasData : styles.noData}>
                    {checkDependencies.purchase_orders}
                  </span>
                </li>
                <li>
                  Hóa đơn: <span className={checkDependencies.invoices > 0 ? styles.hasData : styles.noData}>
                    {checkDependencies.invoices}
                  </span>
                </li>
                <li>
                  Biến thể: <span className={checkDependencies.variants > 0 ? styles.hasData : styles.noData}>
                    {checkDependencies.variants}
                  </span>
                </li>
              </ul>

              {hasDependencies && (
                <Alert
                  message="Sản phẩm này được sử dụng trong các tài liệu khác"
                  description="Các liên kết sẽ bị hủy khi bạn xóa sản phẩm này."
                  type="error"
                  showIcon
                  className={styles.dependencyWarning}
                />
              )}
            </div>
          )}

          {/* Delete Reason (Optional) */}
          <Form layout="vertical" className={styles.form}>
            <Form.Item label="Lý do xóa (tùy chọn)">
              <Input.TextArea
                placeholder="Nhập lý do xóa sản phẩm này..."
                rows={3}
                value={deleteReason}
                onChange={(e) => setDeleteReason(e.target.value)}
                disabled={loading}
              />
            </Form.Item>
          </Form>

          {/* Confirmation Checkbox */}
          <div className={styles.confirmBox}>
            <label>
              <input
                type="checkbox"
                checked={deleteConfirmed}
                onChange={(e) => setDeleteConfirmed(e.target.checked)}
                disabled={loading}
              />
              <span>
                Tôi hiểu rằng sản phẩm này sẽ bị xóa vĩnh viễn và không thể khôi phục.
              </span>
            </label>
          </div>
        </div>
      </Spin>
    </Modal>
  );
};

export default ConfirmDeleteModal;
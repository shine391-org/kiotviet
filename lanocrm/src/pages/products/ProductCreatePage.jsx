/**
 * Product Create Page
 * @file src/pages/products/ProductCreatePage.jsx
 * @description Page for creating new products
 */

import React, { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { Card, Breadcrumb, Button, Space, message } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import ProductForm from '../../components/products/ProductForm';
import { resetSuccessFlags } from '../../store/slices/productSlice';
import { usePermission } from '../../utils/usePermission';
import styles from './ProductCreatePage.module.css';

/**
 * ProductCreatePage Component
 */
const ProductCreatePage = () => {
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const { hasPermission } = usePermission();
  const createSuccess = useSelector(state => state.product.createSuccess);

  // Check permission
  useEffect(() => {
    if (!hasPermission('products.create')) {
      message.error('Bạn không có quyền tạo sản phẩm');
      navigate('/products');
    }
  }, [hasPermission, navigate]);

  // Handle success
  useEffect(() => {
    if (createSuccess) {
      message.success('Sản phẩm đã được tạo thành công');
      dispatch(resetSuccessFlags());
      
      // Redirect to products list after 1.5 seconds
      setTimeout(() => {
        navigate('/products');
      }, 1500);
    }
  }, [createSuccess, dispatch, navigate]);

  const handleCancel = () => {
    try {
      navigate('/products');
    } catch (error) {
      console.error('Navigation error', error);
      message.error('Không thể điều hướng, vui lòng thử lại');
    }
  };

  return (
    <div className={styles.container}>
      {/* Header */}
      <div className={styles.header}>
        <Space className={styles.breadcrumb}>
          <Button 
            type="text" 
            icon={<ArrowLeftOutlined />}
            aria-label="Quay lại"
            onClick={handleCancel}
          >
            Quay lại
          </Button>
        </Space>
        <h1>Thêm sản phẩm mới</h1>
      </div>

      {/* Breadcrumb */}
      <Breadcrumb
        items={[
          { title: 'Dashboard', onClick: () => navigate('/dashboard') },
          { title: 'Sản phẩm', onClick: () => navigate('/products') },
          { title: 'Thêm mới' },
        ]}
        className={styles.breadcrumbNav}
      />

      {/* Form Card */}
      <Card className={styles.card}>
        <ProductForm
          mode="create"
          onSuccess={() => {
            // Success handling already done via Redux
          }}
          onCancel={handleCancel}
        />
      </Card>
    </div>
  );
};

export default ProductCreatePage;

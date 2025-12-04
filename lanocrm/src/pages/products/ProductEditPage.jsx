/**
 * Product Edit Page
 * @file src/pages/products/ProductEditPage.jsx
 * @description Page for editing existing products with all DB fields
 */

import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { Card, Breadcrumb, Button, Space, message, Spin, Row, Col, Tag, Divider, App } from 'antd';
import { ArrowLeftOutlined, LoadingOutlined } from '@ant-design/icons';
import ProductForm from '../../components/products/ProductForm';
import ProductImageManager from '../../components/products/ProductImageManager';
import VariantList from '../../components/products/VariantList';
import ProductPriceListsTab from '../../components/products/ProductPriceListsTab';
import * as attributeApi from '../../api/attributeApi';
import { fetchProductDetailWithVariants } from '../../api/productApi';
import { fetchProductDetail, resetSuccessFlags } from '../../store/slices/productSlice';
import { usePermission } from '../../utils/usePermission';
import styles from './ProductEditPage.module.css';

/**
 * ProductEditPage Component
 */
const ProductEditPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const { hasPermission } = usePermission();
  const { message } = App.useApp();
  const loading = useSelector(state => state.product.loading);
  const currentProduct = useSelector(state => state.product.currentProduct);
  const updateSuccess = useSelector(state => state.product.updateSuccess);

  // ✅ FIX #1: Add state to force refresh summary
  const [refreshKey, setRefreshKey] = useState(0);
  // ✅ THÊM STATE MỚI
  const [productAttributes, setProductAttributes] = useState([]);
  const [loadingAttributes, setLoadingAttributes] = useState(false);
  const [product, setProduct] = useState(null);
  // ✅ THÊM: Handle images attached from media library
  const handleImagesAttached = async (selectedImageIds) => {
    try {
      console.log('🔗 Images attached from library:', selectedImageIds);
      setRefreshKey(prev => prev + 1);
    } catch (error) {
      console.error('Attach error:', error);
      message.error('Lỗi gắn ảnh từ thư viện');
    }
  };

  const handleCancel = () => {
    navigate('/products');
  };

  /**
   * Load product attributes
  */
  const loadProductAttributes = async (productId) => {
    try {
      setLoadingAttributes(true);
      const response = await attributeApi.getProductAttributeValues(productId);
      
      if (response.success) {
        setProductAttributes(response.data);
      }
    } catch (error) {
      console.error('Load product attributes error:', error);
      // Don't show error message, just fail silently
    } finally {
      setLoadingAttributes(false);
    }
  };

  // ✅ FIX #1: Handle success from form - refresh summary
  const handleFormSuccess = (updatedProduct) => {
    //console.log('✅ Form success callback - updated product:', updatedProduct);
    // ✅ FIX: Fetch latest product detail from server
    if (id) {
      dispatch(fetchProductDetail(id));
      // ✅ THÊM: Reload attributes
      loadProductAttributes(id);
    }
    // Trigger summary refresh
    setRefreshKey(prev => prev + 1);
    // Điều hướng về danh sách sau khi cập nhật xong
    navigate('/products');
  };

  // ✅ FIX: Handle image manager callbacks - DEFINE HERE
  const handleImageAdded = (image) => {
    console.log('✅ Image added:', image);
    setRefreshKey(prev => prev + 1);
  };

  const handleImageDeleted = (imageId) => {
    console.log('✅ Image deleted:', imageId);
    setRefreshKey(prev => prev + 1);
  };

  const handlePrimaryImageSet = (imageId) => {
    console.log('✅ Primary image set:', imageId);
    setRefreshKey(prev => prev + 1);
  };

  // Check permission
  useEffect(() => {
    if (!hasPermission('products.edit')) {
      message.error('Bạn không có quyền chỉnh sửa sản phẩm');
      navigate('/products');
    }
  }, [hasPermission, navigate]);

  // Load product data
  useEffect(() => {
    if (id) {
      dispatch(fetchProductDetail(id));
      loadProductAttributes(id);
    }
  }, [id, dispatch]);

   // ✅ NEW: Load product detail (with variants, attribute_values) để truyền cho VariantList
   useEffect(() => {
    const loadProduct = async () => {
      if (!id) return;
      const res = await fetchProductDetailWithVariants(id);
      if (res.success) {
        setProduct(res.data);
      } else {
        message.error(res.message || 'Lỗi lấy dữ liệu sản phẩm');
      }
    };
    loadProduct();
  }, [id, message]);

  // Handle update success
  useEffect(() => {
    if (updateSuccess) {
      message.success('Sản phẩm đã được cập nhật thành công');
      dispatch(resetSuccessFlags());
      
      setTimeout(() => {
        navigate('/products');
      }, 1500);
    }
  }, [updateSuccess, dispatch, navigate]);

  if (loading) {
    return (
      <div className={styles.container}>
        <div className={styles.loadingContainer}>
          <Spin 
            indicator={<LoadingOutlined style={{ fontSize: 48 }} />} 
          />
        </div>
      </div>
    );
  }

  if (!currentProduct) {
    return (
      <div className={styles.container}>
        <div className={styles.errorContainer}>
          <h2>Không tìm thấy sản phẩm</h2>
          <p>ID sản phẩm: {id}</p>
          <Button type="primary" onClick={handleCancel}>
            Quay lại danh sách
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className={styles.container}>
      {/* Header */}
      <div className={styles.header}>
        <Space className={styles.breadcrumb}>
          <Button 
            type="text" 
            icon={<ArrowLeftOutlined />}
            onClick={handleCancel}
          >
            Quay lại
          </Button>
        </Space>
        <h1>Chỉnh sửa sản phẩm</h1>
      </div>

      {/* Breadcrumb */}
      <Breadcrumb
        items={[
          { title: 'Dashboard', onClick: () => navigate('/dashboard') },
          { title: 'Sản phẩm', onClick: () => navigate('/products') },
          { title: currentProduct?.name || 'Chi tiết', onClick: () => {} },
          { title: 'Chỉnh sửa' },
        ]}
        className={styles.breadcrumbNav}
      />

      {/* ✅ FIX #1: Product Info Summary Card - Add key to force re-render */}
      <Card className={styles.summaryCard} key={refreshKey}>
        <Row gutter={[24, 24]}>
          {/* Basic Info */}
          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Mã hàng:</span>
              <span className={styles.value}>{currentProduct?.code}</span>
            </div>
          </Col>

          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Tên sản phẩm:</span>
              <span className={styles.value} title={currentProduct?.name}>
                {currentProduct?.name?.substring(0, 30)}...
              </span>
            </div>
          </Col>

          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Loại hàng:</span>
              <Tag color="blue">
                {currentProduct?.product_type === 'goods' ? 'Hàng hóa' : 
                 currentProduct?.product_type === 'service' ? 'Dịch vụ' : 'Combo'}
              </Tag>
            </div>
          </Col>

          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Danh mục:</span>
              <span className={styles.value}>{currentProduct.category_names?.length ? currentProduct.category_names.join(', ') : '-'}</span>
            </div>
          </Col>

          {/* Pricing Info */}
          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Giá vốn:</span>
              <span className={styles.value}>
                {currentProduct?.purchase_price?.toLocaleString('vi-VN')} ₫
              </span>
            </div>
          </Col>

          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Giá bán:</span>
              <span className={styles.value}>
                {currentProduct?.selling_price?.toLocaleString('vi-VN')} ₫
              </span>
            </div>
          </Col>

          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Giá bán buôn:</span>
              <span className={styles.value}>
                {currentProduct?.wholesale_price?.toLocaleString('vi-VN')} ₫
              </span>
            </div>
          </Col>

          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Hoa hồng (%):</span>
              <span className={styles.value}>{currentProduct?.commission_percent || 0}%</span>
            </div>
          </Col>

          {/* Stock Info */}
          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Tồn kho hiện tại:</span>
              <span className={styles.value}>{currentProduct?.stock_quantity}</span>
            </div>
          </Col>

          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Tồn nhỏ nhất:</span>
              <span className={styles.value}>{currentProduct?.min_stock_alert || 0}</span>
            </div>
          </Col>

          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Tồn lớn nhất:</span>
              <span className={styles.value}>{currentProduct?.max_stock_alert || 0}</span>
            </div>
          </Col>

          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Đơn vị tính:</span>
              <span className={styles.value}>{currentProduct?.unit || '-'}</span>
            </div>
          </Col>

          {/* Status Info - ✅ FIX #1: Strict boolean check */}
          {/* ✅ FIX #2: Boolean display - support both "1" string and 1 integer */}
          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Trạng thái:</span>
              <Tag color={(currentProduct?.is_active === 1 || currentProduct?.is_active === '1') ? 'green' : 'red'}>
                {(currentProduct?.is_active === 1 || currentProduct?.is_active === '1') ? 'Hoạt động' : 'Ngừng kinh doanh'}
              </Tag>
            </div>
          </Col>

          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Bán online:</span>
              <Tag color={(currentProduct?.is_available_online === 1 || currentProduct?.is_available_online === '1') ? 'green' : 'orange'}>
                {(currentProduct?.is_available_online === 1 || currentProduct?.is_available_online === '1') ? 'Có' : 'Không'}
              </Tag>
            </div>
          </Col>

          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Nổi bật:</span>
              <Tag color={(currentProduct?.is_featured === 1 || currentProduct?.is_featured === '1') ? 'gold' : 'default'}>
                {(currentProduct?.is_featured === 1 || currentProduct?.is_featured === '1') ? 'Có' : 'Không'}
              </Tag>
            </div>
          </Col>

          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Có biến thể:</span>
              <Tag color={(currentProduct?.has_variants === 1 || currentProduct?.has_variants === true) ? 'blue' : 'default'}>
                {(currentProduct?.has_variants === 1 || currentProduct?.has_variants === true) ? 'Có' : 'Không'}
              </Tag>
            </div>
          </Col>

          {/* Product Details - ✅ FIX #2: Update labels */}
          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Thương hiệu:</span>
              <span className={styles.value}>{currentProduct?.brand || '-'}</span>
            </div>
          </Col>

          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Mã vạch:</span>
              <span className={styles.value}>{currentProduct?.barcode || '-'}</span>
            </div>
          </Col>

          {/* ✅ FIX #2: Updated weight label to include unit */}
          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Cân nặng (kg):</span>
              <span className={styles.value}>{currentProduct?.weight || 0}</span>
            </div>
          </Col>

          {/* ✅ FIX #2: Updated warranty label to include unit */}
          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Thời gian bảo hành (tháng):</span>
              <span className={styles.value}>{currentProduct?.warranty_period || 0}</span>
            </div>
          </Col>

          {/* Image Preview */}
          <Col xs={24} sm={12} md={6}>
            <div className={styles.summaryItem}>
              <span className={styles.label}>Hình ảnh:</span>
              {(() => {
                let imageUrl = null;
                if (currentProduct?.primary_image && currentProduct?.primary_image.image_url) {
                  imageUrl = currentProduct.primary_image.image_url;
                } else if (currentProduct?.image) {
                  imageUrl = currentProduct.image;
                }
                
                return imageUrl ? (
                  <img 
                    src={imageUrl} 
                    alt="Product" 
                    style={{ width: '100px', height: '100px', objectFit: 'cover', borderRadius: '4px' }}
                  />
                ) : (
                  <span className={styles.value}>Chưa có ảnh</span>
                );
              })()}
            </div>
          </Col>
        </Row>
      </Card>
      {/* ✅ DANH SÁCH BIẾN THỂ - chỉ hiện nếu sản phẩm đã có variants */}
      {(currentProduct?.has_variants === 1 || currentProduct?.has_variants === true) && (
        <Card 
          className={styles.card} 
          title={
            <span>
              📦 Danh sách biến thể ({product?.variants_v2?.length ?? 0})
            </span>
          }
          style={{ marginBottom: '24px' }}
          extra={
            <Button 
              type="link" 
              onClick={() => dispatch(fetchProductDetail(id))}
            >
              Làm mới
            </Button>
          }
        >
          <VariantList
            productId={parseInt(id)}
            productCode={currentProduct?.code}
            // ✅ Truyền variants từ product local đã có attribute_values (nếu muốn override source), còn không thì giữ nguyên cho đồng nhất codebase hiện tại
            variants={product?.variants_v2 || []}
            onRefresh={() => dispatch(fetchProductDetail(id))}
          />
        </Card>
      )}
      {/* ✅ PRODUCT IMAGE MANAGER CARD */}
      {id && (
        <Card className={styles.card} style={{ marginBottom: '24px' }}>
          <ProductImageManager 
            productId={parseInt(id)}
            productCode={currentProduct?.code}  // ✅ THÊM
            onImageAdded={handleImageAdded}
            onImageDeleted={handleImageDeleted}
            onPrimaryImageSet={handlePrimaryImageSet}
            onImagesAttached={handleImagesAttached}  // ✅ THÊM
          />
        </Card>
      )}

      {/* ✅ PRODUCT PRICE LISTS CARD */}
      <Card className={styles.card}>
        <ProductForm
          mode="edit"
          productId={parseInt(id)}
          onSuccess={handleFormSuccess}  // ✅ FIX #1: Pass callback to refresh
          onCancel={handleCancel}
        />
      </Card>
    </div>
  );
};

export default ProductEditPage;

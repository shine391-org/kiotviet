import React, { useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Card, Breadcrumb, Button, Space, message, Spin, Row, Col } from 'antd';
import { ArrowLeftOutlined, LoadingOutlined } from '@ant-design/icons';
import { usePermission } from '../../utils/usePermission';
import * as productApi from '../../api/productApi';
import ProductImageManager from '../../components/products/ProductImageManager';
import VariantAttributeManager from '../../components/products/VariantAttributeManager';
import styles from './VariantEditPage.module.css';
import { handleApiError } from '../../utils/apiErrorHandler';


const VariantEditPage = () => {
  const { variantId } = useParams();
  const navigate = useNavigate();
  const { hasPermission } = usePermission();

  const [loading, setLoading] = React.useState(false);
  const [variant, setVariant] = React.useState(null);
  const [submitting, setSubmitting] = React.useState(false);
  // State giữ các option id đang chọn trong VariantAttributeManager
  const [variantOptionIds, setVariantOptionIds] = React.useState([]);
  const [formData, setFormData] = React.useState({
    sku: '',
    variant_name: '',
    price: 0,
    cost_price: 0,
    stock: 0,
    min_stock: 0,
    max_stock: 0,
    barcode: '',
    images: []
  });
  const handleOptionIdsChange = (optionIds) => {
    setVariantOptionIds(optionIds);
  };

  // Permission check
  useEffect(() => {
    if (!hasPermission('products.edit')) {
      message.error('Bạn không có quyền chỉnh sửa biến thể');
      navigate('/products');
    }
  }, [hasPermission, navigate]);

  // Load variant details
  useEffect(() => {
    if (variantId) {
      loadVariantData();
    }
    // eslint-disable-next-line
  }, [variantId]);

  const loadVariantData = async () => {
    try {
      setLoading(true);
      const response = await productApi.getVariant(variantId);

      let variant = null;
      if (response.success) {
        variant = response.data;
      } else if (response && response.id) {
        variant = response;
      }

      if (variant && variant.id) {
        setVariant(variant);

        // Lưu ý: images là mảng riêng của variant, nếu API trả về images đúng chuẩn thì gán thẳng.
        setFormData({
          sku: variant.sku || '',
          variant_name: variant.variant_name || '',
          price: parseFloat(variant.price || 0),
          cost_price: parseFloat(variant.cost_price || 0),
          stock: parseInt(variant.stock_quantity || 0),
          min_stock: parseInt(variant.min_stock || 0),
          max_stock: parseInt(variant.max_stock || 0),
          barcode: variant.barcode || '',
          images:
            Array.isArray(variant.images) && variant.images.length > 0
              ? variant.images
              : []
        });
      } else {
        message.error('Dữ liệu biến thể không hợp lệ');
        navigate('/products');
      }
    } catch (error) {
      console.error('Load variant error:', error);
      handleApiError(error, { defaultMessage: 'Không thể tải dữ liệu biến thể' });
      navigate('/products');
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => {
    navigate('/products');
  };

  // Trigger khi thuộc tính variant được lưu thành công
  const handleAttributesSaved = (savedAttributes) => {
    message.success('Thuộc tính biến thể đã được cập nhật');
    // Có thể reload lại variant nếu muốn sync UI ngay
    loadVariantData();
  };

  // Submit cập nhật thông tin cơ bản
  const handleSubmit = async () => {
    try {
      if (!formData.sku.trim()) {
        message.error('Vui lòng nhập SKU');
        return;
      }
      if (!formData.variant_name.trim()) {
        message.error('Vui lòng nhập tên biến thể');
        return;
      }
      if (formData.price <= 0) {
        message.error('Giá bán phải lớn hơn 0');
        return;
      }

      setSubmitting(true);

      const updateData = {
        sku: formData.sku,
        variant_name: formData.variant_name,
        price: parseFloat(formData.price),
        cost_price: parseFloat(formData.cost_price),
        stock_quantity: parseInt(formData.stock),
        min_stock: parseInt(formData.min_stock),
        max_stock: parseInt(formData.max_stock),
        barcode: formData.barcode,
        // lấy đúng duy nhất ảnh chính của variant
        image_url:
          formData.images && formData.images.length > 0
            ? formData.images.find(i => i.is_primary === 1)?.image_url || formData.images[0].image_url
            : '',
        attribute_option_ids: variantOptionIds,  // Gửi mảng option ids
      };

      const response = await productApi.updateVariant(variantId, updateData);

      if (response.success) {
        message.success('Biến thể đã được cập nhật thành công');
        setTimeout(() => {
          navigate('/products');
        }, 1200);
      } else {
        message.error(response.message || 'Không thể cập nhật biến thể');
      }
    } catch (error) {
      handleApiError(error, { defaultMessage: 'Có lỗi xảy ra khi cập nhật biến thể' });
    } finally {
      setSubmitting(false);
    }
  };

  // Ảnh variant: chỉ đồng bộ ảnh của chính variant
  const onUploadSuccess = (images) => {
    setFormData(prev => ({
      ...prev,
      images: Array.isArray(images) ? images : prev.images
    }));
  };

  if (loading) {
    return (
      <div className={styles.container}>
        <div className={styles.loadingContainer}>
          <Spin indicator={<LoadingOutlined style={{ fontSize: 48 }} />} tip="Đang tải thông tin biến thể..." />
        </div>
      </div>
    );
  }

  if (!variant) {
    return (
      <div className={styles.container}>
        <div className={styles.errorContainer}>
          <h2>Không tìm thấy biến thể</h2>
          <p>ID biến thể: {variantId}</p>
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
          <Button type="text" icon={<ArrowLeftOutlined />} onClick={handleCancel}>
            Quay lại
          </Button>
        </Space>
        <h1>Chỉnh sửa biến thể</h1>
      </div>

      <Breadcrumb
        items={[
          { title: 'Dashboard', onClick: () => navigate('/dashboard') },
          { title: 'Sản phẩm', onClick: () => navigate('/products') },
          { title: variant?.sku || 'Chi tiết' },
          { title: 'Chỉnh sửa' }
        ]}
        className={styles.breadcrumbNav}
      />

      {/* Thông tin nhanh */}
      <Card className={styles.summaryCard}>
        <div className={styles.variantSummary}>
          <div className={styles.summaryItem}>
            <span className={styles.label}>SKU:</span>
            <span className={styles.value}>{variant?.sku}</span>
          </div>
          <div className={styles.summaryItem}>
            <span className={styles.label}>Tên:</span>
            <span className={styles.value}>{variant?.variant_name}</span>
          </div>
          <div className={styles.summaryItem}>
            <span className={styles.label}>Giá:</span>
            <span className={styles.value}>{Number(variant?.price).toLocaleString('vi-VN')} ₫</span>
          </div>
          <div className={styles.summaryItem}>
            <span className={styles.label}>Tồn kho:</span>
            <span className={styles.value}>{variant?.stock_quantity}</span>
          </div>
        </div>
      </Card>

      {/* Quản lý ảnh - chỉ ảnh variant */}
      <Card style={{ marginBottom: '24px' }}>
        <ProductImageManager
          variantId={variantId}
          productCode={variant?.sku}
          onImageAdded={onUploadSuccess}
          onImageDeleted={deletedId => setFormData(prev => ({
            ...prev,
            images: prev.images.filter(img => img.id !== deletedId),
          }))}
          onPrimaryImageSet={primaryId => setFormData(prev => {
            const updatedImages = prev.images.map(img => ({
              ...img,
              is_primary: img.id === primaryId ? 1 : 0,
            }));
            return { ...prev, images: updatedImages };
          })}
          images={formData.images}
        />
      </Card>

      {/* Quản lý thuộc tính biến thể */}
      <Card className={styles.card} style={{ marginBottom: '24px' }}>
        <VariantAttributeManager
          mode="variant"
          entityId={parseInt(variantId, 10)}
          onSaved={handleAttributesSaved}
          disabled={submitting}
          onOptionIdsChange={handleOptionIdsChange} 
        />
      </Card>

      {/* Edit thông tin cơ bản */}
      <Card className={styles.card}>
        <div style={{ padding: '24px' }}>
          <Row gutter={[24, 24]}>
            <Col xs={24} sm={12}>
              <div>
                <label style={{ fontWeight: 600 }}>SKU/Mã biến thể *</label>
                <input
                  type="text"
                  placeholder="e.g., SKU-001-RED"
                  value={formData.sku}
                  onChange={e => setFormData({ ...formData, sku: e.target.value })}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d9d9d9',
                    borderRadius: '6px',
                    fontSize: '14px',
                  }}
                />
              </div>
            </Col>
            <Col xs={24} sm={12}>
              <div>
                <label style={{ fontWeight: 600 }}>Tên biến thể *</label>
                <input
                  type="text"
                  placeholder="e.g., Màu đỏ - Size M"
                  value={formData.variant_name}
                  onChange={e => setFormData({ ...formData, variant_name: e.target.value })}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d9d9d9',
                    borderRadius: '6px',
                    fontSize: '14px',
                  }}
                />
              </div>
            </Col>
            <Col xs={24} sm={12}>
              <div>
                <label style={{ fontWeight: 600 }}>Giá vốn</label>
                <input
                  type="number"
                  placeholder="0"
                  value={formData.cost_price}
                  onChange={e => setFormData({ ...formData, cost_price: parseFloat(e.target.value) || 0 })}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d9d9d9',
                    borderRadius: '6px',
                    fontSize: '14px',
                  }}
                />
              </div>
            </Col>
            <Col xs={24} sm={12}>
              <div>
                <label style={{ fontWeight: 600, color: '#f5222d' }}>Giá bán *</label>
                <input
                  type="number"
                  placeholder="0"
                  value={formData.price}
                  onChange={e => setFormData({ ...formData, price: parseFloat(e.target.value) || 0 })}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d9d9d9',
                    borderRadius: '6px',
                    fontSize: '14px',
                  }}
                />
              </div>
            </Col>
            <Col xs={24} sm={8}>
              <div>
                <label style={{ fontWeight: 600 }}>Tồn kho</label>
                <input
                  type="number"
                  placeholder="0"
                  value={formData.stock}
                  onChange={e => setFormData({ ...formData, stock: parseFloat(e.target.value) || 0 })}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d9d9d9',
                    borderRadius: '6px',
                    fontSize: '14px',
                  }}
                />
              </div>
            </Col>
            <Col xs={24} sm={8}>
              <div>
                <label style={{ fontWeight: 600 }}>Tồn kho tối thiểu</label>
                <input
                  type="number"
                  placeholder="0"
                  value={formData.min_stock}
                  onChange={e => setFormData({ ...formData, min_stock: parseFloat(e.target.value) || 0 })}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d9d9d9',
                    borderRadius: '6px',
                    fontSize: '14px',
                  }}
                />
              </div>
            </Col>
            <Col xs={24} sm={8}>
              <div>
                <label style={{ fontWeight: 600 }}>Tồn kho tối đa</label>
                <input
                  type="number"
                  placeholder="0"
                  value={formData.max_stock}
                  onChange={e => setFormData({ ...formData, max_stock: parseFloat(e.target.value) || 0 })}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d9d9d9',
                    borderRadius: '6px',
                    fontSize: '14px',
                  }}
                />
              </div>
            </Col>
            <Col xs={24}>
              <div>
                <label style={{ fontWeight: 600 }}>Barcode</label>
                <input
                  type="text"
                  placeholder="Barcode"
                  value={formData.barcode}
                  onChange={e => setFormData({ ...formData, barcode: e.target.value })}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d9d9d9',
                    borderRadius: '6px',
                    fontSize: '14px',
                  }}
                />
              </div>
            </Col>
            {/* Buttons */}
            <Col xs={24}>
              <Space>
                <Button type="primary" loading={submitting} onClick={handleSubmit}>
                  Cập nhật biến thể
                </Button>
                <Button onClick={handleCancel}>Hủy</Button>
              </Space>
            </Col>
          </Row>
        </div>
      </Card>
    </div>
  );
};

export default VariantEditPage;

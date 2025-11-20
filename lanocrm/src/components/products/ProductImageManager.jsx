import React, { useState, useEffect } from 'react';
import { Spin, message, Button, Popconfirm, Tag, Empty, Space, Tooltip, Tabs } from 'antd';
import { UploadOutlined, DeleteOutlined, StarOutlined, StarFilled, LoadingOutlined, FolderOutlined } from '@ant-design/icons';
import * as productApi from '../../api/productApi';
import styles from './ProductImageManager.module.css';
// ✅ THÊM: MediaLibraryBrowser component
import MediaLibraryBrowser from './MediaLibraryBrowser';
import ImageUploadBatch from './ImageUploadBatch';


const ProductImageManager = ({ productId, variantId, productCode, onImageAdded, onImageDeleted, onPrimaryImageSet }) => {
  const [images, setImages] = useState([]);
  const [loading, setLoading] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [primaryImageId, setPrimaryImageId] = useState(null);
  // ✅ THÊM: State cho Tabs
  const [activeTab, setActiveTab] = useState('upload');
  const [mediaLibraryKey, setMediaLibraryKey] = useState(0);

  // Xác định đang thao tác với variant hay product
  const isVariant = Boolean(variantId);
  // entityId là id tương ứng dùng chung cho gọi API
  const entityId = isVariant ? variantId : productId;

  // ✅ Load images with support for variant or product
  useEffect(() => {
    if (entityId) {
      loadImages();
    }
  }, [entityId]);


  // ✅ Debug mỗi ảnh khi render
  useEffect(() => {
    images.forEach((img) => {
      console.log('📸 Image:', {
        id: img.id,
        url: img.url,
        image_url: img.image_url,
        image_path: img.image_path,
        file_exists: img.image_url ? '?' : 'NO URL'
      });
    });
  }, [images]);


  const loadImages = async () => {
    try {
      setLoading(true);
      console.log(`🔄 Loading images for ${isVariant ? 'variant' : 'product'}:`, entityId);
      let response;

      if (isVariant) {
        // Lấy ảnh variant qua API getVariant, lấy ra images array
        const variantRes = await productApi.getVariant(entityId);
        if (variantRes.success && variantRes.data) {
          setImages(variantRes.data.images || []);
          const primaryImg = (variantRes.data.images || []).find(img => img.is_primary === 1);
          setPrimaryImageId(primaryImg ? primaryImg.id : null);
        } else {
          setImages([]);
          setPrimaryImageId(null);
        }
      } else {
        // Lấy ảnh product
        response = await productApi.getProductImages(entityId);
        if (response.success && Array.isArray(response.data)) {
          setImages(response.data || []);
          const primaryImg = response.data?.find(img => img.is_primary === 1);
          setPrimaryImageId(primaryImg ? primaryImg.id : null);
        } else {
          setImages([]);
          setPrimaryImageId(null);
        }
      }
    } catch (error) {
      console.error('💥 Load images error:', error);
      message.error('Lỗi khi tải danh sách ảnh');
      setImages([]);
      setPrimaryImageId(null);
    } finally {
      setLoading(false);
    }
  };


  // ✅ FIX 1: Refresh media library when switching to library tab
  const handleTabChange = (key) => {
    setActiveTab(key);
    
    // ✅ Force reload media library when switching to it
    if (key === 'library') {
      console.log('🔄 Switching to library tab - Force refresh');
      setMediaLibraryKey(prev => prev + 1);
    }
  };

  // Phần upload ảnh vẫn giữ nguyên, nhưng điều chỉnh gọi đúng với variantId/productId
  // Upload single image - giữ nguyên code (nếu muốn có thể mở rộng cho variant)
  // Nhưng trong code hiện tại upload ảnh đa số xử lý qua ImageUploadBatch nên phần này có thể không được sử dụng nhiều


  // ✅ Sử dụng ImageUploadBatch đã được fix để upload nhiều ảnh, truyền productId hoặc variantId
  

  // ✅ Set as primary image cho sản phẩm hoặc variant
  const handleSetPrimary = async (imageId) => {
    try {
      console.log(`⭐ Setting primary image for ${isVariant ? 'variant' : 'product'}:`, imageId);
      const response = await productApi.setPrimaryImage(imageId);

      if (response.success) {
        message.success('Đặt ảnh chính thành công');
        await loadImages();
        setPrimaryImageId(imageId);
        
        if (onPrimaryImageSet) {
          onPrimaryImageSet(imageId);
        }
      } else {
        message.error(response.message || 'Lỗi');
      }
    } catch (error) {
      console.error('Error:', error);
      message.error('Lỗi đặt ảnh chính');
    }
  };

  // Xóa ảnh (soft delete)
  const handleDelete = async (imageId) => {
    try {
      console.log(`🗑️ Soft deleting image for ${isVariant ? 'variant' : 'product'}:`, imageId);
      
      const response = await productApi.deleteProductImage(imageId, false);
      if (response.success) {
        message.success('Xóa ảnh thành công');
        await loadImages();
        // Remove from list
        setImages(images.filter(img => img.id !== imageId));
        
        if (imageId === primaryImageId) {
          setPrimaryImageId(null);
        }
        
        if (onImageDeleted) {
          onImageDeleted(imageId);
        }
      } else {
        message.error(response.message || 'Lỗi xóa ảnh');
      }
    } catch (error) {
      console.error('❌ Soft delete error:', error);
      message.error(`Lỗi xóa ảnh: ${error.message}`);
    }
  };


  // ✅ THÊM: Handle images attached from media library (gắn nhiều ảnh)
  const handleImagesAttached = async (imageIds) => {
    try {
      console.log('🔗 Attaching images:', imageIds);
      
      // ✅ FIX: Filter out duplicates (images already attached)
      const existingImageIds = images.map(img => img.id);
      const newImageIds = imageIds.filter(id => !existingImageIds.includes(id));
      
      if (newImageIds.length === 0) {
        message.warning('⚠️ Tất cả ảnh đã được gắn rồi');
        return;
      }
      
      const response = await productApi.attachMultipleImages(entityId, newImageIds);
      
      if (response.success) {
        message.success(`✅ Đã gắn ${response.attached_count || newImageIds.length} ảnh thành công`);
        
        // ✅ FIX: Reload images after attach
        await loadImages();
        
        // Switch back to upload tab
        setActiveTab('upload');
      } else {
        message.error(response.message || 'Lỗi gắn ảnh');
      }
    } catch (error) {
      console.error('❌ Attach error:', error);
      message.error(`Lỗi gắn ảnh: ${error.message}`);
    }
  };


  const tabItems = [
    {
      key: 'upload',
      label: (
        <span>
          <UploadOutlined /> 📤 Tải file mới
        </span>
      ),
      children: (
        <div className={styles.uploadSection}>
          <div className={styles.header}>
            <h3>📸 Quản lý ảnh {isVariant ? 'biến thể' : 'sản phẩm'}</h3>
            <span className={styles.count}>({images.length} ảnh)</span>
          </div>

          <div className={styles.uploadArea}>
            <ImageUploadBatch
              productId={isVariant ? null : productId}
              variantId={isVariant ? variantId : null}
              maxFiles={10}
              onUploadSuccess={async (uploadedImages) => {
                console.log('✅ Upload success:', uploadedImages);
                
                // Reload images after upload
                await loadImages();
                
                // Force refresh media library
                setMediaLibraryKey(prev => prev + 1);
                
                if (onImageAdded) {
                  onImageAdded(uploadedImages);
                }
              }}
            />
          </div>

          <div className={styles.imagesList}>
            {loading ? (
              <div className={styles.loading}>
                <Spin indicator={<LoadingOutlined />} tip="Đang tải..." />
              </div>
            ) : images.length === 0 ? (
              <Empty description="Chưa có ảnh nào" />
            ) : (
              <div className={styles.grid}>
                {images.map((image) => (
                  <div
                    key={image.id}
                    className={`${styles.imageItem} ${image.is_primary === 1 ? styles.primaryItem : ''}`}
                  >
                    <div className={styles.imagePreview}>
                      <img
                        src={image.image_url}
                        alt={`Product ${image.product_id}`}
                        onError={(e) => {
                          console.error('❌ Image failed to load:', image.image_url);
                          e.target.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200"%3E%3Crect fill="%23f0f0f0" width="200" height="200"/%3E%3Ctext x="50%" y="50%" text-anchor="middle" dy=".3em" fill="%23999" font-size="14"%3ENo Image%3C/text%3E%3C/svg%3E';
                        }}
                      />
                      {image.is_primary === 1 && (
                        <Tag className={styles.primaryBadge} color="gold">
                          ⭐ CHÍNH
                        </Tag>
                      )}
                    </div>

                    <div className={styles.imageInfo}>
                      <p className={styles.imageName}>
                        {image.file_name || 'Image'}
                      </p>
                    </div>

                    <Space className={styles.actions}>
                      {image.is_primary !== 1 ? (
                        <Tooltip title="Đặt làm ảnh chính">
                          <Button
                            type="text"
                            size="large"
                            icon={<StarOutlined style={{ fontSize: '20px' }} />}
                            onClick={() => handleSetPrimary(image.id)}
                          />
                        </Tooltip>
                      ) : (
                        <Tooltip title="Ảnh chính">
                          <Button
                            type="text"
                            size="large"
                            icon={<StarFilled style={{ fontSize: '20px', color: '#faad14' }} />}
                            disabled
                          />
                        </Tooltip>
                      )}

                      <Popconfirm
                        title="Xóa ảnh"
                        description="Bạn có chắc muốn xóa ảnh này?"
                        onConfirm={() => handleDelete(image.id)}
                        okText="Xóa"
                        cancelText="Hủy"
                      >
                        <Button
                          type="text"
                          danger
                          size="large"
                          icon={<DeleteOutlined style={{ fontSize: '20px' }} />}
                        />
                      </Popconfirm>
                    </Space>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      ),
    },
    {
      key: 'library',
      label: (
        <span>
          <FolderOutlined /> 📚 Thư viện ảnh
        </span>
      ),
      children: (
        <MediaLibraryBrowser
          key={mediaLibraryKey}
          variantId={isVariant ? variantId : null}
          productId={isVariant ? null : productId}
          productCode={productCode}
          onImagesSelected={handleImagesAttached}
          visible={activeTab === 'library'}
        />
      ),
    },
  ];


  return (
    <div className={styles.container}>
      {/* ✅ Thêm Tabs wrapper with handleTabChange */}
      <Tabs
        activeKey={activeTab}
        onChange={handleTabChange}
        items={tabItems}
        type="card"
        className={styles.imageTabs}
      />
    </div>
  );
};


export default ProductImageManager;
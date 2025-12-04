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

  const envApi =
    import.meta.env.VITE_API_URL ||
    import.meta.env.VITE_API_PROXY_TARGET ||
    import.meta.env.VITE_API_BASE_URL ||
    '';

  // Origin cho API/uploads (ưu tiên env, fallback localhost:8000)
  // Origin ưu tiên cho ảnh uploads (ưu tiên env, fallback 8000)
  const uploadsOrigin = (() => {
    let origin = 'http://localhost:8000';
    try {
      if (envApi && envApi.startsWith('http')) {
        const parsed = new URL(envApi.replace(/\/api\/?$/, ''));
        const dockerHost = parsed.hostname?.toLowerCase();
        if (['web', 'api', 'backend'].includes(dockerHost)) {
          origin = `${parsed.protocol}//localhost:${parsed.port || '8000'}`;
        } else {
          origin = parsed.origin;
        }
      } else if (envApi && envApi.startsWith('//')) {
        origin = `${window?.location?.protocol || 'http:'}${envApi.replace(/\/api\/?$/, '')}`;
      } else if (window?.location?.origin) {
        origin = window.location.origin.replace(/\/api\/?$/, '').replace('://web', '://localhost');
      }
    } catch (e) {
      // giữ origin mặc định
    }
    return origin;
  })();

  const rewriteDockerHost = (rawUrl) => {
    try {
      const parsed = new URL(rawUrl);
      const host = parsed.hostname.toLowerCase();
      if (['web', 'api', 'backend'].includes(host)) {
        const uploadParsed = new URL(uploadsOrigin);
        parsed.hostname = uploadParsed.hostname || 'localhost';
        parsed.port = uploadParsed.port || parsed.port || (uploadParsed.protocol === 'https:' ? '443' : '8000');
        parsed.protocol = uploadParsed.protocol || parsed.protocol;
        return parsed.toString();
      }
    } catch (e) {
      return rawUrl;
    }
    return rawUrl;
  };

  // Chuẩn hóa host cho đường dẫn ảnh (BE trả /uploads/..., FE chạy 3000 nên cần prefix host API)
  const resolveImageUrl = (rawUrl) => {
    if (!rawUrl) return '';

    // Đã là absolute
    if (/^https?:\/\//i.test(rawUrl)) {
      return rewriteDockerHost(rawUrl);
    }
    if (rawUrl.startsWith('//')) return `${window?.location?.protocol || 'http:'}${rawUrl}`;

    if (rawUrl.startsWith('/')) {
      return `${uploadsOrigin}${rawUrl}`;
    }
    return rawUrl;
  };

  // Chuẩn hóa mảng ảnh về định dạng thống nhất (xử lý cả string URL)
  const normalizeImageList = (list = []) => {
    if (!Array.isArray(list)) return [];

    return list
      .map((img, idx) => {
        if (!img) return null;

        const base =
          typeof img === 'string'
            ? { image_url: resolveImageUrl(img) }
            : {
                ...img,
                image_url: resolveImageUrl(
                  img.image_url || img.url || img.image_path || img.path || img.file_url || ''
                ),
              };

        const fallbackId = `${Date.now()}-${idx}`;
        const id =
          base.id ||
          base.image_id ||
          base.media_id ||
          base.file_id ||
          (base.image_url ? base.image_url : fallbackId);

        const fileName =
          base.file_name ||
          (base.image_url ? base.image_url.split('/').pop() : null) ||
          `Image-${idx + 1}`;

        return {
          ...base,
          id,
          file_name: fileName,
          is_primary: Number(base.is_primary) === 1 ? 1 : 0,
        };
      })
      .filter(Boolean);
  };

  // Gộp ảnh mới vào danh sách hiện tại, tránh trùng id hoặc url
  const mergeImages = (current = [], incoming = []) => {
    const safeCurrent = Array.isArray(current) ? current : [];
    const normalizedIncoming = normalizeImageList(incoming);

    const existingKeys = new Set(
      safeCurrent.map((img) => `${img.id || img.image_url || ''}`)
    );

    const merged = [...safeCurrent];
    normalizedIncoming.forEach((img) => {
      const key = `${img.id || img.image_url || ''}`;
      if (!existingKeys.has(key)) {
        merged.push(img);
      }
    });

    return merged;
  };

  // ✅ Load images with support for variant or product
  useEffect(() => {
    if (entityId) {
      loadImages();
    }
  }, [entityId, productId]);


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


  const loadImages = async (fallbackImages = []) => {
    try {
      setLoading(true);
      console.log(`🔄 Loading images for ${isVariant ? 'variant' : 'product'}:`, entityId);
      let response;

      if (isVariant) {
        // Lấy ảnh variant qua API getVariant, lấy ra images array
        const variantRes = await productApi.getVariant(entityId);
        const variantData = variantRes?.data && Array.isArray(variantRes.data.images)
          ? variantRes.data
          : (Array.isArray(variantRes?.images) ? { images: variantRes.images } : null);

        if (variantData && Array.isArray(variantData.images)) {
          let normalized = normalizeImageList(variantData.images || []);

          // Nếu API không trả ảnh, ưu tiên dùng fallback (ảnh vừa upload)
          if (normalized.length === 0 && Array.isArray(fallbackImages) && fallbackImages.length > 0) {
            normalized = normalizeImageList(fallbackImages);
          } else if (normalized.length === 0 && productId) {
            const prodImages = await productApi.getProductImages(productId);
            if (prodImages.success && Array.isArray(prodImages.data)) {
              normalized = normalizeImageList(prodImages.data);
            }
          }

          if (!normalized.some((img) => img.is_primary === 1) && normalized.length > 0) {
            normalized = normalized.map((img, idx) => ({ ...img, is_primary: idx === 0 ? 1 : 0 }));
          }
          if (normalized.length === 0) {
            normalized = [
              {
                id: `placeholder-${entityId}`,
                image_url:
                  'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%23f0f0f0 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%23999 font-size=%2214%22%3ENo Image%3C/text%3E%3C/svg%3E',
                file_name: 'No image',
                is_primary: 1,
                product_id: productId,
                variant_id: variantId,
              },
            ];
          }
          setImages(normalized);
          const primaryImg = normalized.find(img => img.is_primary === 1);
          setPrimaryImageId(primaryImg ? primaryImg.id : null);
        } else {
          // Keep existing images if API không trả về danh sách ảnh, hoặc dùng fallback
          if (Array.isArray(fallbackImages) && fallbackImages.length > 0) {
            const normalizedFallback = normalizeImageList(fallbackImages);
            setImages(prev => mergeImages(prev, normalizedFallback));
            const primaryImg = normalizedFallback.find(img => img.is_primary === 1);
            setPrimaryImageId((prevPrimary) => prevPrimary || (primaryImg ? primaryImg.id : null));
          } else if (productId) {
            const prodImages = await productApi.getProductImages(productId);
            if (prodImages.success && Array.isArray(prodImages.data) && prodImages.data.length > 0) {
              const normalized = normalizeImageList(prodImages.data);
              setImages(normalized);
              const primaryImg = normalized.find(img => img.is_primary === 1);
              setPrimaryImageId(primaryImg ? primaryImg.id : null);
            } else {
              setImages([{
                id: `placeholder-${entityId}`,
                image_url:
                  'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%23f0f0f0 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%23999 font-size=%2214%22%3ENo Image%3C/text%3E%3C/svg%3E',
                file_name: 'No image',
                is_primary: 1,
                product_id: productId,
                variant_id: variantId,
              }]);
              setPrimaryImageId(`placeholder-${entityId}`);
            }
          } else {
            setImages([{
              id: `placeholder-${entityId}`,
              image_url:
                'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%23f0f0f0 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%23999 font-size=%2214%22%3ENo Image%3C/text%3E%3C/svg%3E',
              file_name: 'No image',
              is_primary: 1,
              product_id: productId,
              variant_id: variantId,
            }]);
            setPrimaryImageId(`placeholder-${entityId}`);
          }
        }
      } else {
        // Lấy ảnh product
        response = await productApi.getProductImages(entityId);
        if (response.success && Array.isArray(response.data)) {
          let normalized = normalizeImageList(response.data || []);
          if (!normalized.some((img) => img.is_primary === 1) && normalized.length > 0) {
            normalized = normalized.map((img, idx) => ({ ...img, is_primary: idx === 0 ? 1 : 0 }));
          }
          setImages(normalized);
          const primaryImg = normalized.find(img => img.is_primary === 1);
          setPrimaryImageId(primaryImg ? primaryImg.id : null);
        } else {
          setImages([]);
          setPrimaryImageId(null);
        }
      }
    } catch (error) {
      console.error('💥 Load images error:', error);
      message.error('Lỗi khi tải danh sách ảnh');
      if (Array.isArray(fallbackImages) && fallbackImages.length > 0) {
        const normalizedFallback = normalizeImageList(fallbackImages);
        setImages((prev) => mergeImages(prev, normalizedFallback));
        const primaryImg = normalizedFallback.find(img => img.is_primary === 1);
        setPrimaryImageId((prevPrimary) => prevPrimary || (primaryImg ? primaryImg.id : null));
      } else {
        setImages([{
          id: `placeholder-${entityId}`,
          image_url:
            'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%23f0f0f0 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%23999 font-size=%2214%22%3ENo Image%3C/text%3E%3C/svg%3E',
          file_name: 'No image',
          is_primary: 1,
          product_id: productId,
          variant_id: variantId,
        }]);
        setPrimaryImageId(`placeholder-${entityId}`);
      }
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
        // Update UI immediately để tránh chờ reload
        setImages(prev =>
          (prev || []).map((img) => ({
            ...img,
            is_primary: Number(img.id) === Number(imageId) ? 1 : 0,
          }))
        );
        setPrimaryImageId(Number(imageId));
        
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

      // Filter out duplicates on FE for quick UX, BE still guards duplicates
      const existingIds = images.map((img) => img.id);
      const targetIds = imageIds.filter((id) => !existingIds.includes(id));

      if (targetIds.length === 0) {
        message.warning('⚠️ Tất cả ảnh đã được gắn rồi');
        return;
      }

      const response = isVariant
        ? await productApi.attachMultipleImagesToVariant(variantId, targetIds)
        : await productApi.attachMultipleImages(productId, targetIds);

      if (response.success) {
        const attached = response.attached_count ?? targetIds.length;
        const skipped = response.skipped_count ?? 0;
        const msg = `Đã thêm ${attached} ảnh, bỏ qua ${skipped} ảnh đã tồn tại${response.missing_count ? `, ${response.missing_count} ảnh không tìm thấy` : ''}`;
        message.success(msg);

        await loadImages();               // refresh "My Files"
        setMediaLibraryKey((prev) => prev + 1); // refresh library flags
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

                const normalizedUploaded = normalizeImageList(uploadedImages);
                let variantBackup = normalizedUploaded;

                // Với biến thể: nếu API chưa trả URL chuẩn, thử lấy ảnh product để có đường dẫn uploads
                if (isVariant && productId) {
                  try {
                    const prodImages = await productApi.getProductImages(productId);
                    if (prodImages.success && Array.isArray(prodImages.data) && prodImages.data.length > 0) {
                      variantBackup = normalizeImageList(prodImages.data);
                    }
                  } catch (e) {
                    console.warn('⚠️ Fallback lấy ảnh product cho variant thất bại:', e?.message);
                  }
                }

                // Hiển thị ngay ảnh vừa upload để UI/test bắt được
                if (normalizedUploaded.length > 0) {
                  setImages((prev) => mergeImages(prev, normalizedUploaded));
                  const primary = normalizedUploaded.find((img) => img.is_primary === 1) || normalizedUploaded[0];
                  setPrimaryImageId((prev) => prev || (primary?.id ?? null));
                } else if (variantBackup.length > 0) {
                  setImages((prev) => mergeImages(prev, variantBackup));
                  const primary = variantBackup.find((img) => img.is_primary === 1) || variantBackup[0];
                  setPrimaryImageId((prev) => prev || (primary?.id ?? null));
                }

                // Reload từ BE (có fallback) để đồng bộ id/primary
                await loadImages(normalizedUploaded.length > 0 ? normalizedUploaded : variantBackup);

                // Force refresh media library
                setMediaLibraryKey(prev => prev + 1);

                if (onImageAdded) {
                  onImageAdded(uploadedImages);
                }
              }}
            />
          </div>

          <div className={styles.imagesList}>
            {loading && images.length === 0 ? (
              <div className={styles.loading}>
                <Spin indicator={<LoadingOutlined />} />
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

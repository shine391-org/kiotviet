import React, { useState, useEffect, useCallback, useRef } from 'react';
import {
  Row,
  Col,
  Input,
  Select,
  Button,
  Checkbox,
  Pagination,
  Empty,
  Spin,
  Card,
  Space,
  message,
  Tooltip,
  Tag,
} from 'antd';
import {
  SearchOutlined,
  FilterOutlined,
  CheckCircleOutlined,
  LoadingOutlined,
  DeleteOutlined,
  EyeOutlined,
} from '@ant-design/icons';
import * as productApi from '../../api/productApi';
import styles from './MediaLibraryBrowser.module.css';
import ConfirmImageDeleteModal from './ConfirmImageDeleteModal';
import ConfirmAttachResultModal from './ConfirmAttachResultModal';

const MediaLibraryBrowser = ({ productId, variantId, productCode, onImagesSelected, visible }) => {
  const [images, setImages] = useState([]);
  const [selectedImages, setSelectedImages] = useState(new Set());
  const [loading, setLoading] = useState(false);
  const [searchSku, setSearchSku] = useState('');
  const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
  const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 12,
    total: 0,
  });
  const [filterMode, setFilterMode] = useState('all');
  const [availableYears, setAvailableYears] = useState([]);
  const [availableMonths, setAvailableMonths] = useState([]);
  const [deleteImageId, setDeleteImageId] = useState(null);
  const [deleteImageData, setDeleteImageData] = useState(null);
  const [deleteConfirmVisible, setDeleteConfirmVisible] = useState(false);

  const [isDeleting, setIsDeleting] = useState(false);
  const deletingRef = useRef(false);

  const [attachResultVisible, setAttachResultVisible] = useState(false);
  const [attachResult, setAttachResult] = useState(null);

  // Phân loại entityId ưu tiên variantId nếu có
  const entityId = variantId || productId;

  const handleApiOk = (response) => {
    const counts = {
      attached_count: response.attached_count ?? 0,
      skipped_count: response.skipped_count ?? response.duplicate_count ?? 0,
      missing_count: response.missing_count ?? 0,
      message:
        response.message ||
        `Đã thêm ${response.attached_count ?? 0} ảnh, bỏ qua ${response.skipped_count ?? 0} ảnh`,
    };
    setAttachResult({ ...counts });
    setAttachResultVisible(true);
  };

  const handleCloseModal = () => {
    setAttachResult(null);
    setAttachResultVisible(false);
  };

  useEffect(() => {
    const currentYear = new Date().getFullYear();
    let years = [];
    for (let i = currentYear - 5; i <= currentYear; i++) {
      years.push(i);
    }
    setAvailableYears(years);
  }, []);

  useEffect(() => {
    const months = Array.from({ length: 12 }, (_, i) => i + 1);
    setAvailableMonths(months);
  }, []);

  // ✅ FIX 1: Auto reload on modal open
  useEffect(() => {
    if (visible) loadImages();
  }, [visible]);

  // ✅ FIX 2: Reset delete state when modal closes
  useEffect(() => {
    if (!deleteConfirmVisible) {
      deletingRef.current = false;
      setIsDeleting(false);
    }
  }, [deleteConfirmVisible]);

  const loadImages = useCallback(async () => {
    try {
      setLoading(true);
      const offset = (pagination.current - 1) * pagination.pageSize;
      let response;

      if (filterMode === 'date') {
        if (!selectedYear || !selectedMonth) {
          message.warning('Vui lòng chọn năm và tháng');
          setLoading(false);
          return;
        }
        response = await productApi.getMediaByDate(selectedYear, selectedMonth, pagination.pageSize, offset, entityId);
      } else if (filterMode === 'sku') {
        if (!searchSku || searchSku.trim().length < 2) {
          message.warning('Nhập mã sản phẩm (tối thiểu 2 ký tự)');
          setLoading(false);
          return;
        }
        response = await productApi.searchMediaBySku(searchSku, pagination.pageSize, offset, entityId);
      } else {
        response = await productApi.getMediaLibrary(pagination.pageSize, offset, { entity_id: entityId });
      }

      if (!response || !response.data || response.data.length === 0) {
        setImages([]);
        setPagination((prev) => ({ ...prev, total: 0 }));
        if (filterMode === 'date') {
          message.info(`Không có ảnh trong tháng ${selectedMonth}/${selectedYear}`);
        } else if (filterMode === 'sku') {
          message.info(`Không tìm thấy ảnh với mã: ${searchSku}`);
        } else {
          message.info('Không có ảnh nào được tìm thấy');
        }
        setLoading(false);
        return;
      }
      // mark attached flag fallback for UI indicator
      const normalized = (response.data || []).map((img) => ({
        ...img,
        is_attached:
          img.is_attached ??
          ((productId && img.product_id === productId) ||
            (variantId && (img.variant_id === variantId || img.variant_product_id === variantId))),
      }));

      setImages(normalized);
      setPagination((prev) => ({ ...prev, total: response.total || 0 }));
    } catch (error) {
      message.error(error.message || 'Lỗi tải ảnh từ thư viện');
      setImages([]);
      setPagination((prev) => ({ ...prev, total: 0 }));
    } finally {
      setLoading(false);
    }
  }, [
    filterMode,
    searchSku,
    selectedYear,
    selectedMonth,
    pagination.current,
    pagination.pageSize,
    entityId,
  ]);

  useEffect(() => {
    if (filterMode === 'sku' && searchSku.trim().length < 2) {
      setImages([]);
      return;
    }
    const timer = setTimeout(loadImages, 300);
    return () => clearTimeout(timer);
  }, [loadImages]);

  const isAttached = useCallback(
    (img) => {
      if (img.is_attached === true || img.is_attached === 1) return true;
      if (productId && img.product_id === productId) return true;
      if (variantId && img.variant_id === variantId) return true;
      if (variantId && img.product_id === productId) return true;
      if (variantId && img.variant_product_id === productId) return true;
      return false;
    },
    [productId, variantId]
  );

  const handleImageToggle = useCallback(
    (image) => {
      if (isAttached(image)) {
        message.info('Ảnh này đã gắn vào sản phẩm');
        return;
      }
      const imageId = image.id;
      setSelectedImages((prev) => {
        const newSet = new Set(prev);
        if (newSet.has(imageId)) newSet.delete(imageId);
        else newSet.add(imageId);
        return newSet;
      });
    },
    [isAttached]
  );

  const handleSelectAll = useCallback(() => {
    const selectable = images.filter((img) => !isAttached(img));
    if (selectedImages.size === selectable.length && selectable.length > 0) {
      setSelectedImages(new Set());
    } else {
      setSelectedImages(new Set(selectable.map((img) => img.id)));
    }
  }, [images, selectedImages, isAttached]);

  const handleClearSelection = useCallback(() => {
    setSelectedImages(new Set());
  }, []);

  // import { attachMultipleImages, attachMultipleImagesToVariant } from '...productApi';

  const handleAttachImages = useCallback(async () => {
    if (selectedImages.size === 0) {
      message.warning('Vui lòng chọn ít nhất 1 ảnh');
      return;
    }
    try {
      setLoading(true);
      const imageIds = Array.from(selectedImages);

      let response;
      // Ưu tiên variant nếu có
      if (variantId) {
        console.log('Calling attachMultipleImagesToVariant with:', { variantId, imageIds });
        response = await productApi.attachMultipleImagesToVariant(variantId, imageIds);
      } else if (productId) {
        response = await productApi.attachMultipleImages(productId, imageIds);
      } else {
        message.error('Thiếu productId hoặc variantId');
        setLoading(false);
        return;
      }

      if (response.success) {
        handleApiOk(response);
        setSelectedImages(new Set());
        if (onImagesSelected) onImagesSelected(imageIds);
        setFilterMode('all');
        setSearchSku('');
        setPagination((prev) => ({ ...prev, current: 1 }));
        await loadImages(); // refresh list state to reflect attached flags immediately
      } else {
        message.error(response.message || 'Lỗi gắn ảnh');
      }
    } catch (error) {
      message.error('Lỗi gắn ảnh: ' + (error.message || 'Unknown error'));
    } finally {
      setLoading(false);
    }
  }, [selectedImages, variantId, productId, onImagesSelected, loadImages]);

  const handleHardDeleteImage = async (imageId) => {
    if (isDeleting || deletingRef.current) return;
    try {
      setIsDeleting(true);
      deletingRef.current = true;
      setLoading(true);
      const response = await productApi.deleteProductImage(imageId, true);
      if (response.success) {
        message.success('Ảnh đã được xóa vĩnh viễn');
        setImages((prev) => prev.filter((img) => img.id !== imageId));
        setPagination((prev) => ({ ...prev, total: Math.max(0, prev.total - 1) }));
      } else {
        message.error(response.message || 'Lỗi xóa ảnh');
      }
    } catch (error) {
      if (error.response?.status === 404 || error.status === 404) {
        message.warning('Ảnh không tồn tại hoặc đã bị xóa trước đó');
        setImages((prev) => prev.filter((img) => img.id !== imageId));
        setPagination((prev) => ({ ...prev, total: Math.max(0, prev.total - 1) }));
      } else {
        message.error('Lỗi xóa ảnh: ' + (error.message || 'Unknown error'));
      }
    } finally {
      setDeleteImageId(null);
      setDeleteImageData(null);
      setDeleteConfirmVisible(false);
      setLoading(false);
      setIsDeleting(false);
      deletingRef.current = false;
    }
  };

  const handlePageChange = useCallback((page) => {
    setPagination((prev) => ({ ...prev, current: page }));
  }, []);

  const handleSkuSearch = useCallback((value) => {
    setSearchSku(value);
    setPagination((prev) => ({ ...prev, current: 1 }));
    if (value.trim()) {
      setFilterMode('sku');
    }
  }, []);

  const handleDateFilter = useCallback(() => {
    setPagination((prev) => ({ ...prev, current: 1 }));
    setFilterMode('date');
  }, []);

  const handleClearFilters = useCallback(() => {
    setSearchSku('');
    setSelectedYear(new Date().getFullYear());
    setSelectedMonth(new Date().getMonth() + 1);
    setFilterMode('all');
    setPagination((prev) => ({ ...prev, current: 1 }));
    setSelectedImages(new Set());
  }, []);

  const formatDate = (dateStr) => {
    try {
      return new Date(dateStr).toLocaleDateString('vi-VN');
    } catch {
      return dateStr;
    }
  };

  const isAllSelected = selectedImages.size === images.length && images.length > 0;
  const hasFilters = filterMode === 'sku' || filterMode === 'date' || searchSku.trim() !== '';

  return (
    <div className={styles['media-library-browser']}>
      <Space direction="vertical" style={{ width: '100%' }} size="large">
        <Card
          className={styles['filter-panel']}
          title={
            <Space>
              <FilterOutlined />
              <span>Bộ lọc & Tìm kiếm</span>
            </Space>
          }
          size="small"
        >
          <Row gutter={[16, 16]}>
            <Col xs={24} sm={12} md={8}>
              <Input
                placeholder="Tìm kiếm theo SKU/Mã sản phẩm"
                prefix={<SearchOutlined />}
                value={searchSku}
                onChange={(e) => handleSkuSearch(e.target.value)}
                allowClear
              />
            </Col>
            <Col xs={24} sm={12} md={4}>
              <Select
                placeholder="Chọn năm"
                value={selectedYear}
                onChange={setSelectedYear}
                options={availableYears.map((year) => ({
                  label: `Năm ${year}`,
                  value: year,
                }))}
                style={{ width: '100%' }}
              />
            </Col>
            <Col xs={24} sm={12} md={4}>
              <Select
                placeholder="Chọn tháng"
                value={selectedMonth}
                onChange={setSelectedMonth}
                options={availableMonths.map((month) => ({
                  label: `Tháng ${month}`,
                  value: month,
                }))}
                style={{ width: '100%' }}
              />
            </Col>
            <Col xs={24} sm={12} md={8}>
              <Space>
                <Button type="primary" onClick={handleDateFilter} icon={<FilterOutlined />}>
                  🔍 Lọc theo ngày
                </Button>
                {hasFilters && (
                  <Button type="default" onClick={handleClearFilters} danger>
                    🔄 Xóa bộ lọc
                  </Button>
                )}
              </Space>
            </Col>
          </Row>
        </Card>

        {images.length > 0 && (
          <Card className={styles['selection-actions']} size="small">
            <Row justify="space-between" align="middle">
              <Col>
                <Space>
                  <Checkbox
                    indeterminate={selectedImages.size > 0 && selectedImages.size < images.length}
                    checked={isAllSelected}
                    onChange={handleSelectAll}
                  >
                    Chọn tất cả ({selectedImages.size}/{images.length})
                  </Checkbox>
                  {selectedImages.size > 0 && <Tag color="blue">✓ Đã chọn: {selectedImages.size}</Tag>}
                </Space>
              </Col>
              <Col>
                <Space>
                  {selectedImages.size > 0 && (
                    <Button type="default" onClick={handleClearSelection} danger>
                      Hủy chọn
                    </Button>
                  )}
                  <Button
                    type="primary"
                    onClick={handleAttachImages}
                    disabled={selectedImages.size === 0}
                    loading={loading}
                    icon={<CheckCircleOutlined />}
                  >
                    🔗 Gắn {selectedImages.size} ảnh
                  </Button>
                </Space>
              </Col>
            </Row>
          </Card>
        )}

        <Spin spinning={loading} indicator={<LoadingOutlined style={{ fontSize: 48 }} />}>
          {images.length > 0 ? (
            <div>
              <div className={styles['image-grid']}>
                {images.map((image) => (
                  <div
                    key={image.id}
                    style={{ flex: '0 0 auto', width: '100%', display: 'flex', flexDirection: 'column', position: 'relative' }}
                    className={isAttached(image) ? styles['attached'] : ''}
                  >
                    <div style={{ position: 'absolute', top: 6, right: 6, zIndex: 10 }}>
                      <Button
                        type="text"
                        danger
                        size="small"
                        icon={<DeleteOutlined />}
                        onClick={(e) => {
                          e.stopPropagation();
                          if (isDeleting) return;
                          setDeleteImageId(image.id);
                          setDeleteImageData({
                            id: image.id,
                            name: image.image_path?.split('/').pop() || 'Image',
                            image_url: image.image_url,
                          });
                          setDeleteConfirmVisible(true);
                        }}
                        title="🗑️ Xóa vĩnh viễn"
                        style={{ backgroundColor: 'rgba(255,255,255,0.95)', borderRadius: 4, padding: '4px 6px', boxShadow: '0 2px 4px rgba(0,0,0,0.1)' }}
                        disabled={isDeleting}
                      />
                    </div>
                    <Card
                      hoverable
                      className={`${styles['image-card']} ${selectedImages.has(image.id) ? styles['selected'] : ''}`}
                      onClick={() => handleImageToggle(image)}
                      cover={
                        <div className={styles['image-cover']}>
                          <img
                            src={image.image_url}
                            alt="Product"
                            className={`${styles['image-thumbnail']} ${isAttached(image) ? styles['imageAttached'] : ''}`}
                          />
                          <div className={styles['image-overlay']}>
                            <Checkbox
                              checked={selectedImages.has(image.id)}
                              disabled={isAttached(image)}
                              onClick={(e) => e.stopPropagation()}
                              onChange={() => handleImageToggle(image)}
                              style={{ fontSize: 20 }}
                            />
                          </div>
                          {isAttached(image) && (
                            <Tag color="blue" className={styles['attachedBadge']} style={{ position: 'absolute', left: 8, top: 8, zIndex: 3 }}>
                              ĐÃ GẮN
                            </Tag>
                          )}
                          {image.is_primary === 1 && (
                            <Tag color="gold" className={styles['primary-badge']} style={{ position: 'absolute', top: 8, right: 8, zIndex: 3 }}>
                              ⭐ Chính
                            </Tag>
                          )}
                          {image.deleted_at && image.deleted_at !== null && image.deleted_at !== 'null' && (
                            <Tag color="orange" style={{ position: 'absolute', top: 40, right: 8, zIndex: 3 }}>
                              🗑️ Đã xóa
                            </Tag>
                          )}
                        </div>
                      }
                    >
                      <Card.Meta
                        title={
                          <Tooltip title={image.image_path}>
                            <span className={styles['image-filename']} style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', display: 'block' }}>
                              {image.image_path?.split('/').pop() || 'Unknown'}
                            </span>
                          </Tooltip>
                        }
                        description={
                          <Space direction="vertical" size={0}>
                            <small style={{ color: '#999' }}>📅 {formatDate(image.created_at)}</small>
                            <small style={{ color: '#999' }}>📦 SKU: {image.product_code || '---'}</small>
                          </Space>
                        }
                      />
                    </Card>
                  </div>
                ))}
              </div>
              <div style={{ textAlign: 'center', marginTop: 24 }}>
                <div style={{ marginBottom: 16, display: 'flex', justifyContent: 'center', gap: 16, alignItems: 'center' }}>
                  <span style={{ fontSize: 14, fontWeight: 500, color: '#262626' }}>📊 Tổng {pagination.total} ảnh</span>
                  <Select value={pagination.pageSize} onChange={(value) => setPagination((prev) => ({ ...prev, pageSize: value, current: 1 }))} options={[
                    { label: '12 / trang', value: 12 },
                    { label: '24 / trang', value: 24 },
                    { label: '48 / trang', value: 48 },
                  ]} style={{ width: 150 }} />
                </div>
                <Pagination current={pagination.current} pageSize={pagination.pageSize} total={pagination.total} onChange={handlePageChange} showSizeChanger={false} showTotal={(total) => `Trang ${pagination.current} / ${Math.ceil(total / pagination.pageSize)}`} />
              </div>
            </div>
          ) : (
            <Empty description={loading ? 'Đang tải...' : 'Không có ảnh nào'} style={{ padding: '48px 0' }} />
          )}
        </Spin>
      </Space>

      <ConfirmImageDeleteModal visible={deleteConfirmVisible} image={deleteImageData} isHardDelete onConfirm={() => {
          if (deleteImageId) handleHardDeleteImage(deleteImageId);
          else setDeleteConfirmVisible(false);
        }} onCancel={() => {
          if (isDeleting) return;
          setDeleteConfirmVisible(false);
          setDeleteImageId(null);
          setDeleteImageData(null);
        }} />

      <ConfirmAttachResultModal key={JSON.stringify(attachResult || {})} visible={attachResultVisible} result={attachResult} onClose={handleCloseModal} />
      
    </div>
  );
};

export default MediaLibraryBrowser;

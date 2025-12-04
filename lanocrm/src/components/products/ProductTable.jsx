// src/components/products/ProductTable.jsx

import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { App, Table, Image, Tag, Space, Button, Divider, Row, Col } from 'antd';
import { EditOutlined, DeleteOutlined, EyeOutlined, CopyOutlined, StopOutlined } from '@ant-design/icons';
import { usePermission } from '../../utils/usePermission'; // permission gate for action buttons
import { getImageUrl } from '../../utils/imageUrl';
import styles from './ProductTable.module.css';

const ProductTable = ({
  products,
  loading,
  pagination,
  highlightedProductId,
  // ✅ NEW: Variant selection props từ parent
  selectedVariantId,
  onVariantSelect,
  expandedProductId,
  onProductExpand,
  expandedRowKeys,
  setExpandedRowKeys,
  onEditVariant,
  onDeleteVariant,
  onDeleteProduct,
  onSortChange,
  onPaginationChange,
  // ✅ Thêm onCloneVariant
  onCloneVariant,
  onShowDeletedVariants,
  deletedVariantsCount,
}) => {
  const navigate = useNavigate();
  const { hasPermission } = usePermission();
  //const [expandedRowKeys, setExpandedRowKeys] = useState([]);
  //const [selectedVariant, setSelectedVariant] = useState(null);
  const [lastExpandedProduct, setLastExpandedProduct] = useState(null);

  const getSelectedVariant = (productVariants) => {
    if (!selectedVariantId || !productVariants) return null;
    return productVariants.find(v => v.id === selectedVariantId);
  };

  const { message } = App.useApp();
  // Format currency
  const formatCurrency = (amount) => {
    if (!amount) return '0₫';
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND'
    }).format(amount);
  };

  // Get product type label
  const getProductTypeLabel = (type) => {
    const types = {
      'single': 'Hàng hóa',
      'goods': 'Hàng hóa',
      'combo': 'Combo',
      'service': 'Dịch vụ'
    };
    return types[type] || type;
  };

  // Get product type color
  const getProductTypeColor = (type) => {
    const colors = {
      'single': 'blue',
      'goods': 'blue',
      'combo': 'purple',
      'service': 'green'
    };
    return colors[type] || 'default';
  };

  // Get status tag
  const getStatusTag = (status) => {
    if (status === 'active' || status === '1' || status === 1) {
      return <Tag color="success">Hoạt động</Tag>;
    }
    return <Tag color="error">Ngừng kinh doanh</Tag>;
  };

  const ProductThumb = ({ record }) => {
    const [src, setSrc] = useState(
      record.image ||
      (Array.isArray(record.images) && record.images[0]?.image_url) ||
      (Array.isArray(record.variants) && record.variants[0]?.image) ||
      ''
    );

    useEffect(() => {
      let mounted = true;
      const hydrate = async () => {
        if (src || !record.id) return;
        try {
          const res = await import('../../api/productApi');
          const imgs = await res.getProductImages(record.id);
          if (mounted && imgs.success && Array.isArray(imgs.data) && imgs.data.length > 0) {
            const first = imgs.data[0];
            setSrc(first.image_url || first.url || first.path || first.image_path || '');
          }
        } catch (e) {
          // ignore
        }
      };
      hydrate();
      return () => {
        mounted = false;
      };
    }, [record.id, src]);

    // Use getImageUrl to handle Docker hostnames and relative paths
    const finalSrc = getImageUrl(src);

    return (
      <Image
        src={finalSrc}
        alt={record.name}
        width={50}
        height={50}
        style={{ objectFit: 'cover', borderRadius: '4px' }}
        fallback="/placeholder-product.png"
        preview={{}}
      />
    );
  };

  // ✅ KIOTVIET STYLE - Expandable row renderer
  const expandedRowRender = (record) => {
    // ✅ SIMPLE PRODUCT (NO VARIANTS)
    if (!record.variants || record.variants.length === 0) {
      return (
        <div style={{ padding: '20px', backgroundColor: '#fafafa', borderRadius: '6px' }}>
          <Row gutter={[24, 24]}>
            {/* Product Image - Left */}
            <Col xs={24} sm={24} md={6}>
              <div style={{
                borderRadius: '8px',
                overflow: 'hidden',
                boxShadow: '0 1px 4px rgba(0,0,0,0.08)',
                backgroundColor: '#fff'
              }}>
                <Image
                  src={record.image || '/placeholder-product.png'}
                  alt={record.name}
                  style={{
                    width: '100%',
                    height: '280px',
                    objectFit: 'cover',
                    display: 'block'
                  }}
                  fallback="/placeholder-product.png"
                  preview={{
                    mask: <EyeOutlined style={{ fontSize: '24px', color: '#fff' }} />
                  }}
                />
              </div>
            </Col>

            {/* Product Info - Right */}
            <Col xs={24} sm={24} md={18}>
              <div style={{
                backgroundColor: '#fff',
                borderRadius: '8px',
                padding: '20px',
                boxShadow: '0 1px 4px rgba(0,0,0,0.08)'
              }}>
                {/* Title */}
                <h3 style={{
                  margin: '0 0 16px 0',
                  fontSize: '18px',
                  fontWeight: 600,
                  color: '#262626'
                }}>
                  {record.name}
                </h3>

                {/* Info Grid - KiotViet Style */}
                <Row gutter={[16, 16]} style={{ marginBottom: '20px' }}>
                  <Col xs={12} sm={8} md={6}>
                    <div style={{
                      padding: '12px',
                      backgroundColor: '#f5f5f5',
                      borderRadius: '6px',
                      border: '1px solid #f0f0f0'
                    }}>
                      <div style={{
                        fontSize: '11px',
                        fontWeight: 600,
                        color: '#8c8c8c',
                        textTransform: 'uppercase',
                        marginBottom: '6px'
                      }}>
                        Mã hàng
                      </div>
                      <div style={{
                        fontSize: '16px',
                        fontWeight: 600,
                        color: '#1890ff'
                      }}>
                        {record.code}
                      </div>
                    </div>
                  </Col>

                  <Col xs={12} sm={8} md={6}>
                    <div style={{
                      padding: '12px',
                      backgroundColor: '#f5f5f5',
                      borderRadius: '6px',
                      border: '1px solid #f0f0f0'
                    }}>
                      <div style={{
                        fontSize: '11px',
                        fontWeight: 600,
                        color: '#8c8c8c',
                        textTransform: 'uppercase',
                        marginBottom: '6px'
                      }}>
                        Tồn kho
                      </div>
                      <div style={{
                        fontSize: '16px',
                        fontWeight: 600,
                        color: (record.stock_quantity || 0) > 0 ? '#52c41a' : '#f5222d'
                      }}>
                        {record.stock_quantity || 0}
                      </div>
                    </div>
                  </Col>

                  <Col xs={12} sm={8} md={6}>
                    <div style={{
                      padding: '12px',
                      backgroundColor: '#f5f5f5',
                      borderRadius: '6px',
                      border: '1px solid #f0f0f0'
                    }}>
                      <div style={{
                        fontSize: '11px',
                        fontWeight: 600,
                        color: '#8c8c8c',
                        textTransform: 'uppercase',
                        marginBottom: '6px'
                      }}>
                        Giá vốn
                      </div>
                      <div style={{
                        fontSize: '16px',
                        fontWeight: 600,
                        color: '#262626'
                      }}>
                        {formatCurrency(record.cost_price || 0)}
                      </div>
                    </div>
                  </Col>

                  <Col xs={12} sm={8} md={6}>
                    <div style={{
                      padding: '12px',
                      backgroundColor: '#f5f5f5',
                      borderRadius: '6px',
                      border: '1px solid #f0f0f0'
                    }}>
                      <div style={{
                        fontSize: '11px',
                        fontWeight: 600,
                        color: '#8c8c8c',
                        textTransform: 'uppercase',
                        marginBottom: '6px'
                      }}>
                        Giá bán
                      </div>
                      <div style={{
                        fontSize: '16px',
                        fontWeight: 600,
                        color: '#f5222d'
                      }}>
                        {formatCurrency(record.selling_price || 0)}
                      </div>
                    </div>
                  </Col>

                  <Col xs={12} sm={8} md={6}>
                    <div style={{
                      padding: '12px',
                      backgroundColor: '#f5f5f5',
                      borderRadius: '6px',
                      border: '1px solid #f0f0f0'
                    }}>
                      <div style={{
                        fontSize: '11px',
                        fontWeight: 600,
                        color: '#8c8c8c',
                        textTransform: 'uppercase',
                        marginBottom: '6px'
                      }}>
                        Danh mục
                      </div>
                      <div style={{
                        fontSize: '16px',
                        fontWeight: 600,
                        color: '#262626'
                      }}>
                        {record.category_names?.length
                          ? record.category_names.join(', ')
                          : '-'}
                      </div>
                    </div>
                  </Col>

                  <Col xs={12} sm={8} md={6}>
                    <div style={{
                      padding: '12px',
                      backgroundColor: '#f5f5f5',
                      borderRadius: '6px',
                      border: '1px solid #f0f0f0'
                    }}>
                      <div style={{
                        fontSize: '11px',
                        fontWeight: 600,
                        color: '#8c8c8c',
                        textTransform: 'uppercase',
                        marginBottom: '6px'
                      }}>
                        Trạng thái
                      </div>
                      <div>
                        {getStatusTag(record.status)}
                      </div>
                    </div>
                  </Col>
                </Row>

                <Divider style={{ margin: '16px 0' }} />

                {/* ✅ Action Buttons */}
                <Space>
                  <Button
                    type="primary"
                    icon={<EditOutlined />}
                    onClick={() => {
                      //console.log('Edit product:', record);
                      navigate(`/products/edit/${record.id}`);
                    }}
                  >
                    Chỉnh sửa
                  </Button>
                  <Button
                    danger
                    icon={<DeleteOutlined />}
                    onClick={() => {
                      // ✅ SAME PATTERN AS VARIANT - Just call callback!
                      if (onDeleteProduct) {
                        onDeleteProduct(record);  // ✅ Pass product object
                      } else {
                        message.error(`Xóa sản phẩm: ${record.name}`);
                      }
                    }}
                  >
                    Xóa
                  </Button>
                  {/* THÊM NÚT NÀY */}

                  <Button
                    danger
                    ghost
                    style={{ color: '#ff4d4f', borderColor: '#ff4d4f' }}
                    onClick={() => onShowDeletedVariants && onShowDeletedVariants(record.id)}
                    disabled={!deletedVariantsCount?.[record.id]}
                  >
                    Xem biến thể đã xoá
                  </Button>

                </Space>
              </div>
            </Col>
          </Row>
        </div>
      );
    }

    // ✅ PRODUCT WITH VARIANTS
    return (
      <div style={{ padding: '20px', backgroundColor: '#fafafa', borderRadius: '6px' }}>
        {/* Variants List */}
        <div style={{ marginBottom: '24px' }}>
          <h4 style={{
            margin: '0 0 16px 0',
            fontSize: '16px',
            fontWeight: 600,
            color: '#262626'
          }}>
            Danh sách biến thể ({record.variants.length})
          </h4>

          <div style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fill, minmax(240px, 1fr))',
            gap: '12px'
          }}>
            {record.variants.map((variant, idx) => (
              <div
                key={variant.id || idx}
                id={`variant-card-${variant.id}`}  // ✅ ADD: ID for scroll
                onClick={() => {
                  // ✅ CHANGED: Call parent handler
                  if (onVariantSelect) {
                    onVariantSelect(variant.id);
                  }
                }}
                className={selectedVariantId === variant.id ? 'variant-card-selected' : 'variant-card'}
                style={{
                  padding: '12px',
                  border: selectedVariantId === variant.id ? '2px solid #1890ff' : '1px solid #f0f0f0',
                  borderRadius: '8px',
                  cursor: 'pointer',
                  backgroundColor: selectedVariantId === variant.id ? '#e6f7ff' : '#fff',
                  transition: 'all 0.3s ease',
                  boxShadow: selectedVariantId === variant.id ? '0 2px 8px rgba(24,144,255,0.15)' : '0 1px 2px rgba(0,0,0,0.06)',
                  display: 'flex',
                  gap: '12px',
                }}
                onMouseEnter={(e) => {
                  if (selectedVariantId !== variant.id) {
                    e.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                  }
                }}
                onMouseLeave={(e) => {
                  if (selectedVariantId !== variant.id) {
                    e.currentTarget.style.boxShadow = '0 1px 2px rgba(0,0,0,0.06)';
                  }
                }}
              >
                {/* Thumbnail */}
                <div style={{
                  width: '80px',
                  height: '80px',
                  flexShrink: 0,
                  borderRadius: '6px',
                  overflow: 'hidden',
                  backgroundColor: '#f5f5f5'
                }}>
                  <Image
                    src={getImageUrl(variant.image_url || variant.image || (Array.isArray(variant.images) && variant.images[0]?.image_url) || record.image)}
                    alt={variant.sku}
                    width="100%"
                    height="100%"
                    style={{
                      objectFit: 'cover',
                      display: 'block'
                    }}
                    fallback="/placeholder-product.png"
                    preview={{
                      mask: <EyeOutlined style={{ fontSize: '16px', color: '#fff' }} />
                    }}
                  />
                </div>

                {/* Info */}
                <div style={{ flex: 1, display: 'flex', flexDirection: 'column', justifyContent: 'space-between' }}>
                  <div>
                    <div style={{
                      fontSize: '13px',
                      fontWeight: 700,
                      color: '#1890ff',
                      marginBottom: '4px'
                    }}>
                      {variant.sku || variant.code || '-'}
                    </div>
                    <div style={{
                      fontSize: '12px',
                      color: '#595959',
                      lineHeight: '1.4'
                    }}>
                      {variant.variant_name || variant.name || '-'}
                    </div>
                  </div>

                  <div>
                    <div style={{
                      fontSize: '13px',
                      fontWeight: 600,
                      color: '#f5222d',
                      marginBottom: '4px'
                    }}>
                      {formatCurrency(variant.price || variant.selling_price || 0)}
                    </div>
                    <div style={{ fontSize: '12px', color: '#8c8c8c' }}>
                      Tồn: <span style={{
                        fontWeight: 600,
                        color: (variant.stock || variant.stock_quantity || 0) > 0 ? '#52c41a' : '#f5222d'
                      }}>
                        {variant.stock || variant.stock_quantity || 0}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* ✅ Selected Variant Detail - KiotViet Style */}
        {(() => {
          const selectedVariant = getSelectedVariant(record.variants);
          if (!selectedVariant) return null;

          return (
            <div
              id="variant-detail-panel"  // ✅ ADD: ID for scroll
              style={{
                padding: '20px',
                backgroundColor: '#fff',
                borderRadius: '8px',
                border: '2px solid #1890ff',
                boxShadow: '0 2px 8px rgba(24,144,255,0.15)',
                marginTop: '16px'  // ✅ ADD: Spacing
              }}
            >
              <h4 style={{
                margin: '0 0 20px 0',
                fontSize: '16px',
                fontWeight: 600,
                color: '#262626',
                paddingBottom: '12px',
                borderBottom: '2px solid #1890ff'
              }}>
                📋 Chi tiết biến thể
              </h4>

              <Row gutter={[24, 24]}>
                {/* Variant Image */}
                <Col xs={24} sm={24} md={6}>
                  <div style={{
                    borderRadius: '8px',
                    overflow: 'hidden',
                    boxShadow: '0 1px 4px rgba(0,0,0,0.08)'
                  }}>
                    <Image
                      src={getImageUrl(selectedVariant.image_url || selectedVariant.image || (Array.isArray(selectedVariant.images) && selectedVariant.images[0]?.image_url) || record.image)}
                      alt={selectedVariant.sku}
                      style={{
                        width: '100%',
                        height: '250px',
                        objectFit: 'cover',
                        display: 'block'
                      }}
                      fallback="/placeholder-product.png"
                      preview={{
                        mask: <EyeOutlined style={{ fontSize: '24px', color: '#fff' }} />
                      }}
                    />
                  </div>
                </Col>

                {/* Variant Info */}
                <Col xs={24} sm={24} md={18}>
                  <Row gutter={[12, 12]}>
                    <Col xs={12} sm={8} md={6}>
                      <div style={{
                        padding: '12px',
                        backgroundColor: '#f5f5f5',
                        borderRadius: '6px',
                        border: '1px solid #f0f0f0'
                      }}>
                        <div style={{
                          fontSize: '11px',
                          fontWeight: 600,
                          color: '#8c8c8c',
                          textTransform: 'uppercase',
                          marginBottom: '6px'
                        }}>
                          SKU/Mã
                        </div>
                        <div style={{
                          fontSize: '14px',
                          fontWeight: 600,
                          color: '#1890ff'
                        }}>
                          {selectedVariant.sku || selectedVariant.code || '-'}
                        </div>
                      </div>
                    </Col>

                    <Col xs={12} sm={8} md={6}>
                      <div style={{
                        padding: '12px',
                        backgroundColor: '#f5f5f5',
                        borderRadius: '6px',
                        border: '1px solid #f0f0f0'
                      }}>
                        <div style={{
                          fontSize: '11px',
                          fontWeight: 600,
                          color: '#8c8c8c',
                          textTransform: 'uppercase',
                          marginBottom: '6px'
                        }}>
                          Tồn kho
                        </div>
                        <div style={{
                          fontSize: '14px',
                          fontWeight: 600,
                          color: (selectedVariant.stock || selectedVariant.stock_quantity || 0) > 0 ? '#52c41a' : '#f5222d'
                        }}>
                          {selectedVariant.stock || selectedVariant.stock_quantity || 0}
                        </div>
                      </div>
                    </Col>

                    <Col xs={12} sm={8} md={6}>
                      <div style={{
                        padding: '12px',
                        backgroundColor: '#f5f5f5',
                        borderRadius: '6px',
                        border: '1px solid #f0f0f0'
                      }}>
                        <div style={{
                          fontSize: '11px',
                          fontWeight: 600,
                          color: '#8c8c8c',
                          textTransform: 'uppercase',
                          marginBottom: '6px'
                        }}>
                          Giá vốn
                        </div>
                        <div style={{
                          fontSize: '14px',
                          fontWeight: 600,
                          color: '#262626'
                        }}>
                          {formatCurrency(selectedVariant.cost_price || 0)}
                        </div>
                      </div>
                    </Col>

                    <Col xs={12} sm={8} md={6}>
                      <div style={{
                        padding: '12px',
                        backgroundColor: '#f5f5f5',
                        borderRadius: '6px',
                        border: '1px solid #f0f0f0'
                      }}>
                        <div style={{
                          fontSize: '11px',
                          fontWeight: 600,
                          color: '#8c8c8c',
                          textTransform: 'uppercase',
                          marginBottom: '6px'
                        }}>
                          Giá bán
                        </div>
                        <div style={{
                          fontSize: '14px',
                          fontWeight: 600,
                          color: '#f5222d'
                        }}>
                          {formatCurrency(selectedVariant.price || selectedVariant.selling_price || 0)}
                        </div>
                      </div>
                    </Col>

                    <Col xs={12} sm={8} md={6}>
                      <div style={{
                        padding: '12px',
                        backgroundColor: '#f5f5f5',
                        borderRadius: '6px',
                        border: '1px solid #f0f0f0'
                      }}>
                        <div style={{
                          fontSize: '11px',
                          fontWeight: 600,
                          color: '#8c8c8c',
                          textTransform: 'uppercase',
                          marginBottom: '6px'
                        }}>
                          Min Stock
                        </div>
                        <div style={{
                          fontSize: '14px',
                          fontWeight: 600,
                          color: '#262626'
                        }}>
                          {selectedVariant.min_stock || 0}
                        </div>
                      </div>
                    </Col>

                    <Col xs={12} sm={8} md={6}>
                      <div style={{
                        padding: '12px',
                        backgroundColor: '#f5f5f5',
                        borderRadius: '6px',
                        border: '1px solid #f0f0f0'
                      }}>
                        <div style={{
                          fontSize: '11px',
                          fontWeight: 600,
                          color: '#8c8c8c',
                          textTransform: 'uppercase',
                          marginBottom: '6px'
                        }}>
                          Max Stock
                        </div>
                        <div style={{
                          fontSize: '14px',
                          fontWeight: 600,
                          color: '#262626'
                        }}>
                          {selectedVariant.max_stock || 'Không giới hạn'}
                        </div>
                      </div>
                    </Col>

                    {selectedVariant.barcode && (
                      <Col xs={24}>
                        <div style={{
                          padding: '12px',
                          backgroundColor: '#f5f5f5',
                          borderRadius: '6px',
                          border: '1px solid #f0f0f0'
                        }}>
                          <div style={{
                            fontSize: '11px',
                            fontWeight: 600,
                            color: '#8c8c8c',
                            textTransform: 'uppercase',
                            marginBottom: '6px'
                          }}>
                            Barcode
                          </div>
                          <div style={{
                            fontSize: '14px',
                            fontWeight: 600,
                            color: '#262626',
                            fontFamily: 'monospace'
                          }}>
                            {selectedVariant.barcode}
                          </div>
                        </div>
                      </Col>
                    )}
                  </Row>

                  <Divider style={{ margin: '16px 0' }} />

                  {/* ✅ FIXED: Action Buttons for Variant - Now call props correctly */}
                  <Space wrap>
                    <Button
                      type="primary"
                      icon={<EditOutlined />}
                      onClick={() => {
                        // ✅ FIX: Call onEditVariant from props
                        if (onEditVariant) {
                          onEditVariant(selectedVariant);
                        } else {
                          message.info(`Chỉnh sửa biến thể: ${selectedVariant.sku}`);
                          navigate(`/products/variants/edit/${selectedVariant.id}`);
                        }
                      }}
                    >
                      Chỉnh sửa
                    </Button>
                    <Button
                      icon={<CopyOutlined />}
                      onClick={() => {
                        if (onCloneVariant) {
                          onCloneVariant(selectedVariant);
                        } else {
                          message.info(`Sao chép biến thể: ${selectedVariant.sku}`);
                          console.log('Clone variant:', selectedVariant);
                        }
                      }}
                    >
                      Sao chép
                    </Button>
                    <Button
                      danger
                      ghost
                      style={{ color: '#ff4d4f', borderColor: '#ff4d4f' }}
                      onClick={() => onShowDeletedVariants && onShowDeletedVariants(record.id)}
                      disabled={!deletedVariantsCount[record.id]}
                    >
                      Xem biến thể đã xoá
                    </Button>
                    <Button
                      icon={<StopOutlined />}
                      onClick={() => {
                        message.warning(`Ngừng biến thể: ${selectedVariant.sku}`);
                        console.log('Stop variant:', selectedVariant);
                      }}
                    >
                      Ngừng
                    </Button>
                    <Button
                      danger
                      icon={<DeleteOutlined />}
                      onClick={() => {
                        if (onDeleteVariant) {
                          // ✅ FIX: Pass both variant AND productId
                          onDeleteVariant(selectedVariant, record.id);
                        } else {
                          message.error(`Xóa biến thể: ${selectedVariant.sku}`);
                        }
                      }}
                    >
                      Xóa
                    </Button>

                  </Space>
                </Col>
              </Row>
            </div>
          );
        })()}
      </div>
    );
  };

  // ✅ Handle table change (sorting, pagination)
  const handleTableChange = (pag, filters, sorter) => {
    if (pag.current !== pagination.page || pag.pageSize !== pagination.limit) {
      if (onPaginationChange) {
        onPaginationChange({
          page: pag.current,
          limit: pag.pageSize
        });
      }
      return;
    }

    let sortBy = 'p.id';
    let sortOrder = 'DESC';

    if (sorter && sorter.field) {
      const fieldMap = {
        'code': 'p.code',
        'name': 'p.name',
        'product_type': 'p.product_type',
        'category_name': 'pc.name',
        'selling_price': 'p.selling_price',
        'stock_quantity': 'p.stock_quantity'
      };

      sortBy = fieldMap[sorter.field] || sortBy;
      sortOrder = sorter.order === 'ascend' ? 'ASC' : 'DESC';
    }

    if (onSortChange) {
      onSortChange({
        sortBy,
        sortOrder,
        page: pag.current,
        limit: pag.pageSize
      });
    }
  };

  const handleExpandedRowsChange = (newExpandedRowKeys) => {
    if (newExpandedRowKeys.length > 0) {
      const newExpandedId = newExpandedRowKeys[newExpandedRowKeys.length - 1];
      const oldExpandedId = expandedRowKeys.length > 0 ? expandedRowKeys[0] : null;

      // ✅ Find product object
      const expandedProduct = products.find(p => p.id === newExpandedId);

      // ✅ Call parent handler
      if (onProductExpand && expandedProduct) {
        onProductExpand(true, expandedProduct);
      }

      setExpandedRowKeys([newExpandedId]);
    } else {
      // Collapse - notify parent
      const collapsedProduct = products.find(p => p.id === expandedRowKeys[0]);
      if (onProductExpand && collapsedProduct) {
        onProductExpand(false, collapsedProduct);
      }

      setExpandedRowKeys([]);
    }
  };

  // Table columns
  const columns = [
    {
      title: 'Ảnh',
      dataIndex: 'image',
      key: 'image',
      width: 80,
      align: 'center',
      render: (_, record) => <ProductThumb record={record} />,
    },
    {
      title: 'Mã hàng',
      dataIndex: 'code',
      key: 'code',
      width: 120,
      sorter: true,
      sortDirections: ['ascend', 'descend'],
      render: (code) => (
        <span style={{ fontWeight: 500, color: '#1890ff' }}>{code || '-'}</span>
      ),
    },
    {
      title: 'Tên hàng',
      dataIndex: 'name',
      key: 'name',
      width: 250,
      sorter: true,
      sortDirections: ['ascend', 'descend'],
      render: (name, record) => (
        <div>
          <div style={{ fontWeight: 500 }}>{name}</div>
          {record.description && (
            <div style={{
              fontSize: '12px',
              color: '#8c8c8c',
              overflow: 'hidden',
              textOverflow: 'ellipsis',
              whiteSpace: 'nowrap',
              maxWidth: '230px'
            }}>
              {record.description}
            </div>
          )}
        </div>
      ),
    },
    {
      title: 'Loại',
      dataIndex: 'product_type',
      key: 'product_type',
      width: 120,
      align: 'center',
      sorter: true,
      sortDirections: ['ascend', 'descend'],
      render: (type) => (
        <Tag color={getProductTypeColor(type)}>
          {getProductTypeLabel(type)}
        </Tag>
      ),
    },
    {
      title: 'Nhóm hàng',
      dataIndex: 'category_names',  // ✅ MỚI - lấy field mảng nhiều-nhiều
      key: 'category_names',
      width: 150,
      sorter: true,
      sortDirections: ['ascend', 'descend'],
      render: (names) =>
        Array.isArray(names) && names.length
          ? names.join(', ')  // ✅ Nối mảng thành chuỗi
          : '-',
    },
    {
      title: 'Giá bán',
      dataIndex: 'selling_price',
      key: 'selling_price',
      width: 130,
      align: 'right',
      sorter: true,
      sortDirections: ['ascend', 'descend'],
      render: (price) => (
        <span style={{ fontWeight: 500, color: '#f5222d' }}>
          {formatCurrency(price)}
        </span>
      ),
    },
    {
      title: 'Tồn kho',
      dataIndex: 'stock_quantity',
      key: 'stock_quantity',
      width: 100,
      align: 'center',
      sorter: true,
      sortDirections: ['ascend', 'descend'],
      render: (stock) => {
        const value = stock || 0;
        const color = value > 10 ? '#52c41a' : value > 0 ? '#faad14' : '#f5222d';
        return <span style={{ fontWeight: 500, color }}>{value}</span>;
      },
    },
    {
      title: 'Trạng thái',
      dataIndex: 'status',
      key: 'status',
      width: 120,
      align: 'center',
      render: (status) => getStatusTag(status),
    },
    {
      title: 'Thao tác',
      key: 'action',
      width: 90,
      align: 'center',
      render: (_, record) => {
        // Kiểm tra sản phẩm cha hoặc sản phẩm đơn giản
        // FE gate: only users with edit/update permission see button
        const hv = record.has_variants;
        const isVariantProduct = hv === 1 || hv === '1' || hv === true;
        const isSimpleProduct = hv === 0 || hv === '0' || hv === false || hv === null || hv === undefined;
        const canEdit = hasPermission('products.edit') || hasPermission('products.update');
        const showEditButton = (isVariantProduct || isSimpleProduct) && canEdit;

        return (
          <Space>
            {showEditButton && (
              <Button
                type="text"
                icon={<EditOutlined />}
                onClick={() => navigate(`/products/edit/${record.id}`)}
                title="Chỉnh sửa sản phẩm"
              />
            )}
            {/* Các nút khác nếu có */}
          </Space>
        );
      },
    }
  ];

  return (
    <Table
      columns={columns}
      dataSource={products}
      rowKey="id"
      loading={loading}
      onChange={handleTableChange}
      pagination={{
        current: pagination.page,
        pageSize: pagination.limit,
        total: pagination.total,
        totalBoundaryShowSizeChanger: 20,
        showSizeChanger: true,
        showQuickJumper: true,
        showTotal: (total, range) =>
          `${range[0]}-${range[1]} của ${total} sản phẩm`,
        pageSizeOptions: ['10', '20', '50', '100'],
        placement: 'bottomRight',
      }}
      rowClassName={(record) => {
        // ✅ Highlight animation khi product được chọn
        const classes = [];
        if (record.id === highlightedProductId) {
          classes.push('product-row-highlighted');
        }
        return classes.join(' ');
      }}
      onRow={(record) => ({
        id: `product-row-${record.id}`, // ✅ NEW: Thêm id cho scroll
      })}
      scroll={{ x: 1200 }}
      size="middle"
      bordered
      style={{ backgroundColor: '#fff' }}
      expandable={{
        expandedRowRender,
        expandedRowKeys,
        onExpandedRowsChange: (keys) => {
          setExpandedRowKeys(keys);
          // Notify parent
          if (onProductExpand && keys.length) {
            const expandedProduct = products.find(p => p.id === keys[keys.length - 1]);
            if (expandedProduct) onProductExpand(true, expandedProduct);
          } else if (onProductExpand && !keys.length) {
            const collapsedProduct = products.find(p => p.id === expandedProductId);
            if (collapsedProduct) onProductExpand(false, collapsedProduct);
          }
        },
        expandRowByClick: true,
        expandIcon: ({ expanded, onExpand, record }) => {
          return (
            <Button
              type="link"
              size="small"
              onClick={(e) => {
                e.stopPropagation();
                onExpand(record, e);
              }}
              style={{ padding: '0 8px', color: '#1890ff' }}
            >
              {expanded ? '▼' : '▶'} {record.variants?.length ? record.variants.length : 'Xem'}
            </Button>
          );
        }
      }}
    />
  );
};

export default ProductTable;

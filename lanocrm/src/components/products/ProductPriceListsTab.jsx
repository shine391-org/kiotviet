/**
 * Product Price Lists Tab Component
 * @file src/components/products/ProductPriceListsTab.jsx
 * @description Display product prices across different price lists
 * @agent-component: ProductPriceListsTab - Price lists integration UI
 * @agent-pattern: Table display with API integration
 * @agent-reusable: HIGH
 */

import React, { useState, useEffect } from 'react';
import { Table, Tag, Spin, message, Button, Space, Empty } from 'antd';
import { ReloadOutlined, DollarOutlined } from '@ant-design/icons';
import * as priceListApi from '../../api/priceListApi';
import * as productApi from '../../api/productApi';
import styles from './ProductPriceListsTab.module.css';

/**
 * ProductPriceListsTab Component
 * @param {Object} props
 * @param {number} props.productId - Product ID
 */
const ProductPriceListsTab = ({ productId }) => {
  const [loading, setLoading] = useState(false);
  const [priceLists, setPriceLists] = useState([]);
  const [productPrices, setProductPrices] = useState([]);
  const [refreshKey, setRefreshKey] = useState(0);

  /**
   * Format currency to VND
   * @param {number} amount - Amount to format
   * @returns {string} Formatted currency string
   */
  const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) {
      return '-';
    }
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND'
    }).format(amount);
  };

  /**
   * Format date range
   * @param {string} startDate - Start date
   * @param {string} endDate - End date
   * @returns {string} Formatted date range
   */
  const formatDateRange = (startDate, endDate) => {
    if (!startDate && !endDate) return '-';
    
    const format = (date) => {
      if (!date) return '';
      return new Date(date).toLocaleDateString('vi-VN');
    };
    
    const start = format(startDate);
    const end = format(endDate);
    
    if (start && end) {
      return `${start} → ${end}`;
    } else if (start) {
      return `Từ ${start}`;
    } else if (end) {
      return `Đến ${end}`;
    }
    
    return '-';
  };

  /**
   * Get status tag color and text
   * @param {string} status - Status value
   * @param {string} startDate - Start date
   * @param {string} endDate - End date
   * @returns {Object} { color, text }
   */
  const getStatusInfo = (status, startDate, endDate) => {
    const now = new Date();
    const start = startDate ? new Date(startDate) : null;
    const end = endDate ? new Date(endDate) : null;
    
    if (status === 'inactive') {
      return { color: 'default', text: 'Không hoạt động' };
    }
    
    if (start && start > now) {
      return { color: 'blue', text: 'Sắp diễn ra' };
    }
    
    if (end && end < now) {
      return { color: 'default', text: 'Đã hết hạn' };
    }
    
    return { color: 'green', text: 'Đang hoạt động' };
  };

  /**
   * Calculate discount percentage
   * @param {number} basePrice - Base price
   * @param {number} finalPrice - Final price
   * @returns {string} Discount percentage
   */
  const calculateDiscount = (basePrice, finalPrice) => {
    if (!basePrice || !finalPrice || basePrice === 0) {
      return '-';
    }
    
    if (finalPrice > basePrice) {
      const increase = ((finalPrice - basePrice) / basePrice * 100).toFixed(1);
      return `+${increase}%`;
    }
    
    if (finalPrice < basePrice) {
      const discount = ((basePrice - finalPrice) / basePrice * 100).toFixed(1);
      return `-${discount}%`;
    }
    
    return '0%';
  };

  /**
   * Load price lists and product prices
   */
  const loadData = async () => {
    if (!productId) {
      message.error('Thiếu Product ID');
      return;
    }

    try {
      setLoading(true);
      
      // Load all price lists
      const priceListsResponse = await priceListApi.getPriceLists();
      if (!priceListsResponse.success) {
        throw new Error(priceListsResponse.message || 'Lỗi tải danh sách bảng giá');
      }
      
      const lists = priceListsResponse.data || [];
      setPriceLists(lists);
      
      // Load product price for each price list
      const pricePromises = lists.map(async (priceList) => {
        try {
          // Use proper params object instead of query string
          const productResponse = await productApi.getProductDetail(productId, {
            params: { price_list_id: priceList.id }
          });
          
          return {
            priceListId: priceList.id,
            priceListName: priceList.name,
            basePrice: productResponse.data?.base_price || productResponse.data?.selling_price || 0,
            finalPrice: productResponse.data?.final_price || productResponse.data?.selling_price || 0,
            appliedPrice: productResponse.data?.applied_price || 0,
            status: priceList.status,
            startDate: priceList.start_date,
            endDate: priceList.end_date,
            customerGroups: priceList.customer_groups || [],
            hasPrice: productResponse.data?.final_price != null || productResponse.data?.selling_price != null
          };
        } catch (error) {
          console.warn(`Failed to load price for price list ${priceList.id}:`, error);
          return {
            priceListId: priceList.id,
            priceListName: priceList.name,
            basePrice: 0,
            finalPrice: 0,
            appliedPrice: 0,
            status: priceList.status,
            startDate: priceList.start_date,
            endDate: priceList.end_date,
            customerGroups: priceList.customer_groups || [],
            hasPrice: false
          };
        }
      });
      
      const prices = await Promise.all(pricePromises);
      setProductPrices(prices);
      
    } catch (error) {
      console.error('Load price lists error:', error);
      message.error(error.message || 'Lỗi tải dữ liệu bảng giá');
    } finally {
      setLoading(false);
    }
  };

  /**
   * Load data on component mount and when productId/refreshKey changes
   */
  useEffect(() => {
    if (productId) {
      loadData();
    }
  }, [productId, refreshKey, loadData]);

  /**
   * Define table columns
   */
  const columns = [
    {
      title: 'Tên bảng giá',
      dataIndex: 'priceListName',
      key: 'priceListName',
      sorter: (a, b) => a.priceListName.localeCompare(b.priceListName),
      render: (text, record) => (
        <div>
          <div style={{ fontWeight: 'bold' }}>{text}</div>
          {!record.hasPrice && (
            <div style={{ fontSize: '12px', opacity: 0.6 }}>Chưa có giá</div>
          )}
        </div>
      ),
    },
    {
      title: 'Giá gốc',
      dataIndex: 'basePrice',
      key: 'basePrice',
      align: 'right',
      sorter: (a, b) => a.basePrice - b.basePrice,
      render: (price) => (
        <span style={{ opacity: 0.75 }}>
          {formatCurrency(price)}
        </span>
      ),
    },
    {
      title: 'Giá bán',
      dataIndex: 'finalPrice',
      key: 'finalPrice',
      align: 'right',
      sorter: (a, b) => a.finalPrice - b.finalPrice,
      render: (price, record) => (
        <span style={{ 
          fontWeight: 'bold', 
          color: record.hasPrice ? '#1890ff' : undefined,
          opacity: record.hasPrice ? 1 : 0.6
        }}>
          {formatCurrency(price)}
        </span>
      ),
    },
    {
      title: 'Giảm giá',
      key: 'discount',
      align: 'right',
      sorter: (a, b) => {
        const discountA = calculateDiscount(a.basePrice, a.finalPrice);
        const discountB = calculateDiscount(b.basePrice, b.finalPrice);
        
        const toNumeric = (discount) => {
          if (discount === '-') return 0;
          if (discount.startsWith('+')) return parseFloat(discount.slice(1, -1));
          if (discount.startsWith('-')) return -parseFloat(discount.slice(1, -1));
          return parseFloat(discount.slice(0, -1));
        };
        
        return toNumeric(discountA) - toNumeric(discountB);
      },
      render: (_, record) => {
        const discount = calculateDiscount(record.basePrice, record.finalPrice);
        let color = undefined;
        let fontWeight = 'normal';
        let opacity = 0.6;
        
        if (discount !== '-') {
          opacity = 1;
          fontWeight = 'bold';
          if (discount.startsWith('+')) {
            color = '#52c41a'; // green for price increases
          } else if (discount.startsWith('-')) {
            color = '#f5222d'; // red for price decreases
          } else {
            color = '#1890ff'; // blue for zero
          }
        }
        
        return (
          <span style={{ 
            color,
            fontWeight,
            opacity
          }}>
            {discount}
          </span>
        );
      },
    },
    {
      title: 'Áp dụng từ-đến',
      key: 'dateRange',
      render: (_, record) => (
        <span style={{ fontSize: '12px' }}>
          {formatDateRange(record.startDate, record.endDate)}
        </span>
      ),
    },
    {
      title: 'Nhóm khách hàng',
      dataIndex: 'customerGroups',
      key: 'customerGroups',
      render: (groups) => {
        if (!groups || groups.length === 0) {
          return <span style={{ opacity: 0.6 }}>Tất cả</span>;
        }
        
        return (
          <Space size={4} wrap>
            {groups.slice(0, 2).map((group, index) => (
              <Tag key={index} size="small" color="blue">
                {group}
              </Tag>
            ))}
            {groups.length > 2 && (
              <Tag size="small" color="default">
                +{groups.length - 2}
              </Tag>
            )}
          </Space>
        );
      },
    },
    {
      title: 'Trạng thái',
      key: 'status',
      render: (_, record) => {
        const statusInfo = getStatusInfo(record.status, record.startDate, record.endDate);
        return <Tag color={statusInfo.color}>{statusInfo.text}</Tag>;
      },
    },
  ];

  /**
   * Handle refresh button click
   */
  const handleRefresh = () => {
    setRefreshKey(prev => prev + 1);
  };

  return (
    <div className={styles.container}>
      {/* Header */}
      <div className={styles.header}>
        <div className={styles.title}>
          <DollarOutlined style={{ marginRight: 8 }} />
          Bảng giá sản phẩm
        </div>
        <Button 
          type="text" 
          icon={<ReloadOutlined />} 
          onClick={handleRefresh}
          loading={loading}
        >
          Làm mới
        </Button>
      </div>

      {/* Content */}
      <div className={styles.content}>
        <Spin spinning={loading}>
          {productPrices.length === 0 && !loading ? (
            <Empty 
              description="Chưa có dữ liệu bảng giá"
              image={Empty.PRESENTED_IMAGE_SIMPLE}
            />
          ) : (
            <Table
              dataSource={productPrices}
              columns={columns}
              rowKey="priceListId"
              pagination={{
                pageSize: 10,
                showSizeChanger: true,
                showTotal: (total, range) => 
                  `Hiển thị ${range[0]}-${range[1]} của ${total} bảng giá`,
              }}
              scroll={{ x: 1000 }}
              size="small"
            />
          )}
        </Spin>
      </div>
    </div>
  );
};

export default ProductPriceListsTab;

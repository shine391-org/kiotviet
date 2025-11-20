import React, { useEffect, useState } from 'react';
import { Table, Button, Tag, Space, message } from 'antd';
import { EditOutlined, CopyOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import * as productApi from '../../api/productApi';
import VariantCloneModal from './VariantCloneModal';

const VariantList = ({ productId, productCode, onRefresh, variants: variantsProp }) => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [variants, setVariants] = useState(Array.isArray(variantsProp) ? variantsProp : []);

  // State cho modal clone
  const [cloneModalOpen, setCloneModalOpen] = useState(false);
  const [sourceVariant, setSourceVariant] = useState(null);

  useEffect(() => {
    if (Array.isArray(variantsProp) && variantsProp.length) {
      // Ưu tiên dùng prop truyền vào (FE truyền từ cha xuống, đã có attribute_values)
      setVariants(variantsProp);
    } else if (Array.isArray(variantsProp) && variantsProp.length === 0) {
      // Nếu rõ ràng truyền mảng rỗng, thì giữ nguyên (KHÔNG fetch lại - tránh flicker)
      setVariants([]); 
    } else if (productId && !variantsProp) {
      // Chỉ fetch từ API khi KHÔNG truyền prop variants
      loadVariants();
    }
  }, [productId, variantsProp]);
      

  const loadVariants = async () => {
    try {
      setLoading(true);
      const response = await productApi.getVariantsByProduct(productId);
      let arr = [];
      if (response && response.success) {
        arr = Array.isArray(response.data?.variants) ? response.data.variants : [];
      }
      setVariants(arr);
    } catch (error) {
      message.error('Lỗi tải danh sách biến thể');
      setVariants([]);
    } finally {
      setLoading(false);
    }
  };

  // Hàm mở modal clone khi click
  const handleOpenCloneModal = (variant) => {
    setSourceVariant(variant);
    setCloneModalOpen(true);
  };

  // Đóng modal, có thể refresh lại biến thể
  const handleCloseModal = () => {
    setCloneModalOpen(false);
    setSourceVariant(null);
    // Không cần reload lại bảng, flow sẽ chuyển sang trang edit luôn
    if (onRefresh) onRefresh();
  };

  const columns = [
    {
      title: 'Mã biến thể',
      dataIndex: 'sku',
      key: 'sku',
      width: 150,
    },
    {
      title: 'Tên biến thể',
      dataIndex: 'variant_name',
      key: 'variant_name',
      width: 350,
    },
    {
      title: 'Thuộc tính',
      key: 'attributes',
      render: (_, record) => (
        <Space>
          {(Array.isArray(record.attribute_values) ? record.attribute_values : []).map((attr, idx) => (
            <Tag key={idx} color="blue">
              {attr.attribute_name}: {attr.option_name}
            </Tag>
          ))}
        </Space>
      ),
    },
    {
      title: 'Giá vốn',
      dataIndex: 'cost_price',
      key: 'cost_price',
      width: 120,
      render: (cost_price) => `${parseInt(cost_price, 10).toLocaleString()} ₫`,
    },
    {
      title: 'Giá bán',
      dataIndex: 'price',
      key: 'price',
      width: 120,
      render: (price) => `${parseInt(price, 10).toLocaleString()} ₫`,
    },
    {
      title: 'Tồn kho',
      dataIndex: 'stock_quantity',
      key: 'stock_quantity',
      width: 100,
    },
    {
      title: 'Thao tác',
      key: 'action',
      width: 90,
      render: (_, record) => (
        <Space>
          <Button
            type="text"
            icon={<EditOutlined />}
            onClick={() => navigate(`/products/variants/edit/${record.id}`)}
            title="Sửa"
          />
          <Button
            type="text"
            icon={<CopyOutlined />}
            onClick={() => handleOpenCloneModal(record)}
            title="Sao chép"
          />
        </Space>
      ),
    },
  ];

  return (
    <>
      <Table
        columns={columns}
        dataSource={Array.isArray(variants) ? variants : []}
        loading={loading}
        rowKey="id"
        pagination={{ pageSize: 10 }}
      />
      <VariantCloneModal
        open={cloneModalOpen}
        sourceVariant={sourceVariant}
        onCancel={handleCloseModal}
      />
    </>
  );
};

export default VariantList;
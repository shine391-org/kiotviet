import React, { useEffect, useState, useMemo } from 'react';
import { Drawer, Table, Badge, Typography, Space, Divider, Button, Input } from 'antd';
import { EyeOutlined } from '@ant-design/icons';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { fetchCategoryProducts } from '../../store/slices/categorySlice';

const { Text } = Typography;
const { Compact } = Space;

const CategoryProductDrawer = ({ category, onClose }) => {
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const products = useSelector(state => state.category.categoryProducts || []);
  const loading = useSelector(state => state.category.categoryProductsLoading || false);
  //console.log('PRODUCTS DRAWER:', products);

  // State lưu giá trị tìm kiếm cho filter
  const [searchText, setSearchText] = useState('');

  useEffect(() => {
    if (category?.id) {
      dispatch(fetchCategoryProducts(category.id));
      setSearchText(''); // reset search khi thay đổi danh mục
    }
  }, [category, dispatch]);

  // ==== XÂY DỰNG TREE DATA ====
  function buildProductTree(flatProducts) {
    const mainProducts = {};
    flatProducts.forEach(item => {
      if (!item.variant_id) {
        mainProducts[item.id] = { ...item }; // KHÔNG gán children ở đây
      }
    });
    flatProducts.forEach(item => {
      if (item.variant_id) {
        const pid = item.id || item.product_id;
        if (mainProducts[pid]) {
          if (!mainProducts[pid].children) mainProducts[pid].children = [];
          mainProducts[pid].children.push(item);
        }
      }
    });
    // Trả về: chỉ sản phẩm chính, chỉ có property children khi thực sự có children
    return Object.values(mainProducts);
  }  

  // Filter rồi build lại tree
  const filteredProductsFlat = useMemo(() => {
    const kw = searchText.trim().toLowerCase();
    if (!kw) return products;
    return products.filter(item =>
      (item.sku && item.sku.toLowerCase().includes(kw)) ||
      (item.code && item.code.toLowerCase().includes(kw)) ||
      (item.name && item.name.toLowerCase().includes(kw)) ||
      (item.variant_name && item.variant_name.toLowerCase().includes(kw))
    );
  }, [products, searchText]);

  const treeData = useMemo(() => buildProductTree(filteredProductsFlat), [filteredProductsFlat]);

  // Xử lý chuyển trang khi bấm nút xem
  const handleView = (record) => {
    if (record.variant_id) {
      navigate(`/products/variants/edit/${record.variant_id}`);
    } else {
      navigate(`/products/edit/${record.id}`);
    }
  };

  // Các cột hiển thị cho bảng
  const columns = [
    {
      title: 'Mã hàng',
      dataIndex: 'sku',
      key: 'sku',
      width: 180,
      //align: 'center',
      render: (text, record) =>
        <span style={{
          fontWeight: record.variant_id ? 500 : 700,
          fontSize: record.variant_id ? 12 : 14,
          color: record.variant_id ? '#555' : '#1d39c4'
        }}>
          {record.variant_id ? record.sku : record.code}
        </span>
    },
    {
      title: 'Tên hàng',
      dataIndex: 'name',
      key: 'name',
      ellipsis: true,
      render: (text, record) => record.variant_id ? record.variant_name : record.name
    },
    {
      title: 'Giá bán',
      dataIndex: 'price',
      key: 'price',
      width: 120,
      align: 'right',
      render: (text, record) => {
        const price = record.variant_id ? record.variant_price : record.selling_price;
        return price ? `${parseInt(price).toLocaleString('vi-VN')} ₫` : '-';
      }
    },
    {
      title: 'Trạng thái',
      dataIndex: 'status',
      key: 'status',
      width: 100,
      align: 'center',
      render: (status, record) =>
        record.deleted_at ? (
          <Badge color="default" text="Đã xóa" />
        ) : status === 'active' ? (
          <Badge color="green" text="Active" />
        ) : (
          <Badge color="default" text="Inactive" />
        )
    },
    {
      title: 'Thao tác',
      key: 'action',
      width: 100,
      align: 'center',
      render: (_, record) => (
        <Button
          type="link"
          icon={<EyeOutlined />}
          onClick={() => handleView(record)}
        >
          Xem
        </Button>
      )
    }
  ];

  return (
    <Drawer
      title={
        <Space direction="vertical" size={0}>
          <Text strong style={{ fontSize: 16 }}>{category?.name || 'Danh mục'}</Text>
          <Text type="secondary" style={{ fontSize: 13 }}>Tổng số sản phẩm: {products.length}</Text>
        </Space>
      }
      width={900}
      onClose={onClose}
      open={!!category}
      destroyOnClose
    >
      {category && (
        <>
          <Space direction="vertical" size="small" style={{ marginBottom: 16 }}>
            <Text type="secondary"><strong>ID:</strong> {category.id}</Text>
            <Text type="secondary"><strong>Code:</strong> {category.code}</Text>
            {category.product_count > 0 && (
              <Text type="secondary">
                <strong>Sản phẩm (bao gồm danh mục con):</strong> {category.product_count}
              </Text>
            )}
          </Space>
          <Divider />
        </>
      )}
      {/* Dùng Space.Compact với Input và Button để search */}
      <Compact size="middle" style={{ marginBottom: 16, width: '100%' }}>
        <Input
          placeholder="Tìm kiếm mã hàng hoặc tên hàng"
          allowClear
          value={searchText}
          onChange={e => setSearchText(e.target.value)}
          onPressEnter={() => {}}
          style={{ width: 'calc(100% - 110px)' }}
        />
        <Button type="primary" onClick={() => { /* optional */ }}>
          Tìm
        </Button>
      </Compact>
      <Table
        rowKey={record => record.variant_id ? `v-${record.variant_id}` : `p-${record.id}`}
        columns={columns}
        dataSource={treeData}
        loading={loading}
        expandable={{
          expandRowByClick: true,
          indentSize: 20,
          rowExpandable: record => record.children && record.children.length > 0
        }}
        pagination={{
          pageSize: 10,
          showSizeChanger: true,
          showTotal: total => `Tổng: ${total}`
        }}
        size="small"
      />
    </Drawer>
  );
};

export default CategoryProductDrawer;
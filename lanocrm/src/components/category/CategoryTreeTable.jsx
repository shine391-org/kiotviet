import React, { useEffect, useState, useMemo } from 'react';
import {
  Table, Button, Badge, Tooltip, Space, Input, Breadcrumb, App, Pagination
} from 'antd';
import {
  EditOutlined, DeleteOutlined, SearchOutlined,
  FolderOpenOutlined, FolderOutlined, FileOutlined, PlusOutlined, MinusOutlined
} from '@ant-design/icons';
import { useDispatch, useSelector } from 'react-redux';
import CategoryProductDrawer from './CategoryProductDrawer';
import {
  fetchCategoryTree,
  deleteCategory,
  hardDeleteCategoryThunk,
  restoreCategoryThunk
} from '../../store/slices/categorySlice';

const PAGE_SIZE = 50;

// Giải pháp: Bỏ children khi push vào mảng flat
const flattenTree = (tree, search = '', parentExpanded = true, result = [], level = 0, expandedRowKeys = []) => {
  tree.forEach(node => {
    const visible = (!search || (node.name || '').toLowerCase().includes(search.toLowerCase())) && parentExpanded;
    // Tạo node mới, bỏ trường children và chỉ giữ prop childrenCount phục vụ expand
    // hoặc chỉ dùng row.childrenCount (nếu có children thì set = node.children.length)
    const hasChildren = node.children && node.children.length > 0;
    const nodeForTable = { ...node, level, childrenCount: hasChildren ? node.children.length : 0 };
    delete nodeForTable.children;
    if (visible) {
      result.push(nodeForTable);
    }
    const isExpanded = expandedRowKeys.includes(node.id);
    if (hasChildren) {
      flattenTree(node.children, search, visible && isExpanded, result, level + 1, expandedRowKeys);
    }
  });
  return result;
};

const CategoryTreeTable = ({ onEditCategory }) => {
  const dispatch = useDispatch();
  const categoryTree = useSelector(state => state.category.categoryTree);
  const deleteLoading = useSelector(state => state.category.deleteLoading);
  const deleteSuccess = useSelector(state => state.category.deleteSuccess);
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [expandedRowKeys, setExpandedRowKeys] = useState([]);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);

  const { modal, message } = App.useApp();

  useEffect(() => {
    dispatch(fetchCategoryTree({ include_deleted: true }));
  }, [dispatch]);

  useEffect(() => {
    if (deleteSuccess) {
      message.success('Xóa danh mục thành công');
      dispatch(fetchCategoryTree({ include_deleted: true }));
    }
  }, [deleteSuccess, dispatch, message]);

  const handleRestore = (record) => {
    modal.confirm({
      title: 'Khôi phục danh mục',
      content: `Bạn có chắc chắn muốn khôi phục danh mục "${record.name}"?`,
      onOk: async () => {
        try {
          await dispatch(restoreCategoryThunk(record.id)).unwrap();
          message.success('Khôi phục thành công');
          dispatch(fetchCategoryTree({ include_deleted: true }));
        } catch (error) {
          message.error('Khôi phục thất bại: ' + error);
        }
      }
    });
  };

  const handleHardDelete = (record) => {
    modal.confirm({
      title: 'Xóa vĩnh viễn danh mục',
      content: (
        <div>
          <b>Bạn chắc chắn muốn xóa vĩnh viễn không thể khôi phục?</b>
          <p style={{ color: 'red', margin: 0 }}>
            Hành động này sẽ xóa bỏ dữ liệu khỏi hệ thống vĩnh viễn, không thể phục hồi!
          </p>
        </div>
      ),
      okType: 'danger',
      okText: 'Xóa vĩnh viễn',
      cancelText: 'Hủy',
      onOk: async () => {
        try {
          await dispatch(hardDeleteCategoryThunk(record.id)).unwrap();
          message.success('Xóa vĩnh viễn thành công');
          dispatch(fetchCategoryTree({ include_deleted: true }));
        } catch (error) {
          message.error('Xóa vĩnh viễn thất bại: ' + error);
        }
      }
    });
  };

  const handleDelete = (record) => {
    const hasChildren = record.children && record.children.length > 0;
    const hasProducts = record.product_count > 0;
    if (hasChildren) return message.error('Không thể xóa danh mục còn danh mục con!');
    if (hasProducts) return message.error('Không thể xóa danh mục còn sản phẩm!');
    modal.confirm({
      title: 'Xác nhận xóa',
      content: `Bạn có chắc muốn xóa danh mục "${record.name}"?`,
      okText: 'Xóa',
      okType: 'danger',
      cancelText: 'Hủy',
      onOk: () => {
        dispatch(deleteCategory(record.id));
      }
    });
  };

    const handleExpandRow = (record) => {
    const isExpanded = expandedRowKeys.includes(record.id);

    if (isExpanded) {
      // Collapse node hiện tại
      setExpandedRowKeys(prev =>
        prev.filter(id => id !== record.id)
      );
    } else {
      // Accordion: giữ lại chỉ node cùng cấp cha hiện tại và các cha phía trên
      setExpandedRowKeys(prev => {
        // Tìm các node đang expand cùng cấp cha, loại hết ra khỏi mảng
        const parentId = record.parent_id || null; // Hoặc key phụ dùng để xác định cha
        // Flatten list lấy đủ dữ liệu để làm so sánh (hoặc lưu ý record phải có parent_id ở data)
        const next = prev
          .map(id => {
            const node = flatCategoryList.find(x => x.id === id);
            return { id, parentId: node ? node.parent_id : null };
          })
          .filter(x => x.parentId !== parentId) // Loại cùng cấp cha với record
          .map(x => x.id);
        // Thêm node đang expand vào danh sách, giữ lại toàn bộ các cha cũ trên
        return [...next, record.id];
      });
    }
  };

  // Flatten tree for pagination & search
  const flatCategoryList = useMemo(() =>
    flattenTree(categoryTree || [], search, true, [], 0, expandedRowKeys), [categoryTree, search, expandedRowKeys]);

  // Pagination
  const pagedData = flatCategoryList.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

  const columns = [
    {
      title: '',
      width: 40,
      align: 'center',
      render: (_, row) => {
        // Dùng row.childrenCount (không còn row.children!)
        const isParent = row.childrenCount > 0;
        if (!isParent) return null;
        const isExpanded = expandedRowKeys.includes(row.id);
        return (
          <Button
            type="text"
            size="small"
            icon={isExpanded
              ? <MinusOutlined style={{ color: '#1890ff', fontSize: 18 }} />
              : <PlusOutlined style={{ color: '#1890ff', fontSize: 18 }} />
            }
            onClick={() => handleExpandRow(row)}
            style={{
              padding: 0,
              minWidth: 24,
              height: 24,
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              background: 'none'
            }}
          />
        );
      }
    },
    {
      title: 'ID',
      dataIndex: 'id',
      width: 80,
      align: 'center',
      sorter: (a, b) => Number(a.id) - Number(b.id),
      render: (id, row) => {
        let bg = '#1890ff', color = '#fff', fontWeight = 700, fontSize = 16;
        if (row.deleted_at) {
          bg = '#ff4d4f';      // đỏ cho danh mục soft delete
          color = '#fff';
          fontWeight = 700;
        } else if (row.level === 1) {
          bg = '#9e7cff';
          color = '#fff';
          fontWeight = 600;
          fontSize = 15;
        } else if (row.level >= 2) {
          bg = '#f6f6f8';
          color = '#444';
          fontWeight = 500;
          fontSize = 15;
        }
        return (
          <span
            style={{
              fontWeight,
              color,
              background: bg,
              borderRadius: 8,
              minWidth: 38,
              minHeight: 32,
              width: 38,
              height: 32,
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              fontSize
            }}
          >{id}</span>
        );
      }
    },    
    {
      title: 'Tên danh mục',
      dataIndex: 'name',
      key: 'name',
      sorter: (a, b) => (a.name || '').localeCompare(b.name || ''),
      render: (name, row) => {
        const isParent = row.children && row.children.length > 0;
        const isOpen = expandedRowKeys.includes(row.id);
        const TreeIcon = isParent
          ? (isOpen ? <FolderOpenOutlined style={{ color: '#1890ff' }} /> : <FolderOutlined style={{ color: '#1890ff' }} />)
          : <FileOutlined style={{ color: '#b7b7b7' }} />;

        return (
          <span style={{
            paddingLeft: row.level ? `${row.level * 24}px` : 0,
            display: 'flex',
            alignItems: 'center',
            fontWeight: row.level === 0 ? 600 : row.level === 1 ? 500 : 400,
            color: row.level === 0 ? '#222' : row.level === 1 ? '#473cae' : '#555',
            position: 'relative',
            cursor: isParent ? 'pointer' : 'unset',
            userSelect: 'none'
          }}
            onClick={isParent ? () => handleExpandRow(row) : undefined}
          >
            {row.level > 0 && (
              <span style={{
                position: 'absolute',
                left: `${row.level * 14 - 18}px`,
                height: '100%',
                width: 12,
                borderLeft: '2px solid #ececec'
              }} />
            )}
            <span style={{ marginRight: 8, fontSize: 17 }}>{TreeIcon}</span>
            {name}
          </span>
        );
      },
    },
    {
      title: 'Trạng thái',
      dataIndex: 'status',
      align: 'center',
      sorter: (a, b) => (a.status || '').localeCompare(b.status || ''),
      render: status =>
        status === 'active'
          ? <Badge color="green" text="Active" />
          : <Badge color="default" text="Inactive" />
    },
    {
      title: 'Used',
      dataIndex: 'product_count',
      align: 'center',
      sorter: (a, b) => Number(a.product_count) - Number(b.product_count),
      render: (count, record) => (
        <Button
          size="small"
          type="link"
          style={{ padding: 0, fontWeight: 600, ...(record.deleted_at ? { opacity: 0.5 } : {}) }}
          onClick={() => !record.deleted_at && setSelectedCategory(record)}
          disabled={!!record.deleted_at}
        >
          {count || 0}
        </Button>
      )
    },
    {
      title: 'Hành động',
      align: 'center',
      width: 140,
      render: (_, record) => {
        if (record.deleted_at) {
          return (
            <Space>
              <Tooltip title="Khôi phục">
                <Button
                  type="link"
                  onClick={() => handleRestore(record)}
                  style={{ color: '#1890ff', fontWeight: 600 }}
                >Khôi phục</Button>
              </Tooltip>
              <Tooltip title="Xóa vĩnh viễn (không thể phục hồi)">
                <Button
                  danger
                  type="link"
                  onClick={() => handleHardDelete(record)}
                >Xóa vĩnh viễn</Button>
              </Tooltip>
            </Space>
          )
        }
        const hasChildren = record.children && record.children.length > 0;
        const hasProducts = record.product_count > 0;
        const canDelete = !hasChildren && !hasProducts;

        let deleteTooltip = 'Xóa danh mục';
        if (hasChildren && hasProducts) {
          deleteTooltip = 'Không thể xóa khi còn danh mục con và sản phẩm';
        } else if (hasChildren) {
          deleteTooltip = 'Không thể xóa khi còn danh mục con';
        } else if (hasProducts) {
          deleteTooltip = 'Không thể xóa khi còn sản phẩm';
        }

        return (
          <Space>
            <Tooltip title="Chỉnh sửa">
              <Button
                type="link"
                onClick={() => onEditCategory(record)}
                icon={<EditOutlined />}
                disabled={!!record.deleted_at}
                style={{ opacity: record.deleted_at ? 0.5 : 1 }}
              />
            </Tooltip>
            <Tooltip title={deleteTooltip}>
              <Button
                type="link"
                danger
                disabled={!canDelete || record.deleted_at}
                loading={deleteLoading}
                style={{ opacity: canDelete ? 1 : 0.5 }}
                onClick={() => handleDelete(record)}
                icon={<DeleteOutlined />}
              />
            </Tooltip>
          </Space>
        );
      }
    }
  ];

  return (
    <>
      <style>{`
        .category-soft-deleted-row {
          background-color: #fffbe6 !important;
          color: #666 !important;
          opacity: 0.7;
          pointer-events: none;
        }
        .category-soft-deleted-row button {
          pointer-events: auto !important;
        }
        .category-id-cell:focus {
          outline: none !important;
        }
      `}</style>
      <div style={{ marginBottom: 16 }}>
        <Breadcrumb
          items={[
            { title: 'Sản phẩm' },
            { title: 'Danh mục' }
          ]}
        />
      </div>
      <div style={{ display: 'flex', marginBottom: 16, alignItems: 'center' }}>
      <Space.Compact>
        <Input
          placeholder="Tìm theo tên danh mục..."
          allowClear
          style={{ width: 270 }}
          value={search}
          onChange={e => {
            setSearch(e.target.value);
            setPage(1);
          }}
          onPressEnter={e => setSearch(e.target.value)}
        />
        <Button
          icon={<SearchOutlined />}
          onClick={() => setSearch(search)}
          type="primary"
        />
      </Space.Compact>
      </div>
      <Table
        rowKey="id"
        rowClassName={record => record.deleted_at ? 'category-soft-deleted-row' : ''}
        columns={columns}
        dataSource={pagedData}
        size="middle"
        bordered
        showSorterTooltip
        pagination={false}
        scroll={{ x: 'max-content' }}
      />
      <div style={{ textAlign: 'right', marginTop: 8 }}>
        <Pagination
          pageSize={PAGE_SIZE}
          current={page}
          total={flatCategoryList.length}
          onChange={p => setPage(p)}
          showTotal={(total, range) => `Hiển thị: ${range[0]} - ${range[1]} / ${total}`}
          showQuickJumper
        />
      </div>
      <CategoryProductDrawer
        category={selectedCategory}
        onClose={() => setSelectedCategory(null)}
      />
    </>
  );
};

export default CategoryTreeTable;
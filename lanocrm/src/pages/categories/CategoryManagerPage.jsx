import React, { useState } from 'react';
import { Button } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import CategoryTreeTable from '../../components/category/CategoryTreeTable';
import CategoryEditModal from '../../components/category/CategoryEditModal';

const CategoryManagerPage = () => {
  const [editingCategory, setEditingCategory] = useState(null);
  const [isModalOpen, setIsModalOpen] = useState(false);

  const handleOpenCreateModal = () => {
    setEditingCategory(null); // null = tạo mới
    setIsModalOpen(true);
  };

  const handleOpenEditModal = (category) => {
    setEditingCategory(category);
    setIsModalOpen(true);
  };

  const handleCloseModal = () => {
    setIsModalOpen(false);
    setEditingCategory(null);
  };

  return (
    <div style={{ padding: '20px' }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
        <h2>Quản lý danh mục sản phẩm</h2>
        <Button type="primary" icon={<PlusOutlined />} onClick={handleOpenCreateModal}>
          Thêm danh mục
        </Button>
      </div>

      <CategoryTreeTable onEditCategory={handleOpenEditModal} />

      {isModalOpen && (
        <CategoryEditModal
          category={editingCategory}
          onClose={handleCloseModal}
        />
      )}
    </div>
  );
};

export default CategoryManagerPage;
import React, { useEffect } from 'react';
import { Modal, Form, Input, TreeSelect, Button, App } from 'antd';
import { useDispatch, useSelector } from 'react-redux';
import { createCategory, updateCategory, fetchCategoryTree, resetSuccessFlags } from '../../store/slices/categorySlice';

const getDescendantIds = (node) => {
  let ids = [];
  if (node.children && node.children.length > 0) {
    node.children.forEach(child => {
      ids.push(child.id);
      ids = ids.concat(getDescendantIds(child));
    });
  }
  return ids;
};

const filterTree = (tree, excludeIds) =>
  tree
    .filter(cat => !excludeIds.includes(cat.id))
    .map(cat => ({
      ...cat,
      children: cat.children ? filterTree(cat.children, excludeIds) : [],
    }));

const mapTreeForTreeSelect = (tree) => tree.map(cat => ({
  title: cat.name,
  value: cat.id,
  key: cat.id,
  children: cat.children && cat.children.length > 0 ? mapTreeForTreeSelect(cat.children) : [],
}));

const CategoryEditModal = ({ category, onClose }) => {
  const [form] = Form.useForm();
  const dispatch = useDispatch();
  const { message } = App.useApp();
  const categoryTree = useSelector(state => state.category.categoryTree);

  const isEdit = !!category?.id;

  // Loại bỏ bản thân và toàn bộ con ra khỏi lựa chọn parent
  const excludeIds = isEdit ? [category.id, ...getDescendantIds(category)] : [];
  const filteredTreeData = mapTreeForTreeSelect(filterTree(categoryTree || [], excludeIds));

  // Lấy trạng thái thành công, loading từ slice
  const createSuccess = useSelector(state => state.category.createSuccess);
  const createLoading = useSelector(state => state.category.createLoading);

  useEffect(() => {
    dispatch(fetchCategoryTree({ include_deleted: true }));
  }, [dispatch]);

  useEffect(() => {
    if (isEdit) {
      form.setFieldsValue({ name: category.name, parent_id: category.parent_id || null });
    } else {
      form.resetFields();
    }
  }, [category, form, isEdit]);

  // Lắng nghe create thành công để thông báo, reload, đóng modal
  useEffect(() => {
    if (createSuccess) {
      message.success(`Tạo danh mục "${form.getFieldValue('name')}" thành công`);
      dispatch(fetchCategoryTree({ include_deleted: true }));
      onClose();
      setTimeout(() => {
        dispatch(resetSuccessFlags());
      }, 300); // reset flag để không hiện lại message khi mở lại modal
    }
  }, [createSuccess, dispatch, onClose, form]);

  const onFinish = (values) => {
    if (isEdit) {
      dispatch(updateCategory({ id: category.id, data: values }));
    } else {
      dispatch(createCategory(values));
    }
  };
  
  return (
    <Modal
      title={isEdit ? 'Chỉnh sửa nhóm hàng' : 'Thêm nhóm hàng mới'}
      open={true}
      onCancel={onClose}
      footer={null}
      destroyOnHidden={true}
    >
      <Form form={form} layout="vertical" onFinish={onFinish}>
        <Form.Item
          label="Tên nhóm hàng"
          name="name"
          rules={[{ required: true, message: 'Vui lòng nhập tên nhóm hàng' }]}
        >
          <Input placeholder="Nhập tên nhóm hàng" />
        </Form.Item>
        <Form.Item label="Danh mục cha" name="parent_id">
          <TreeSelect
            allowClear
            treeDefaultExpandAll
            treeData={filteredTreeData}
            placeholder="Chọn danh mục cha (bỏ trống nếu cấp cao nhất)"
          />
        </Form.Item>
        <Form.Item>
          <Button type="primary" htmlType="submit" loading={createLoading} block>
            {isEdit ? 'Cập nhật' : 'Tạo mới'}
          </Button>
        </Form.Item>
      </Form>
    </Modal>
  );
};

export default CategoryEditModal;
/**
 * Product Form Component - Reusable for Create/Edit
 * @file src/components/products/ProductForm.jsx
 * @description Form component for creating and editing products
 */

import React, { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useFormik } from 'formik';
import { Form, Input, InputNumber, Button, Space, Spin, Select, Row, Col, Checkbox, App, TreeSelect } from 'antd';
import { LoadingOutlined, SettingOutlined } from '@ant-design/icons';
import styles from './ProductForm.module.css';
// ✅ THÊM IMPORT MỚI
import AttributeSelector from './AttributeSelector';
import VariantSetupModal from './VariantSetupModal';
import * as attributeApi from '../../api/attributeApi';
import { productSchema } from '../../utils/validators';
import * as productApi from '../../api/productApi';
import { fetchCategoryTree } from '../../store/slices/categorySlice';
import { fetchProducts } from '../../store/slices/productSlice';

/**
 * ProductForm Component
 * @param {Object} props
 * @param {string} props.mode - 'create' or 'edit'
 * @param {number} props.productId - Product ID (for edit mode)
 * @param {Function} props.onSuccess - Callback on success
 * @param {Function} props.onCancel - Callback on cancel
 */
const ProductForm = ({ mode = 'create', productId, onSuccess, onCancel }) => {
const dispatch = useDispatch();
const categoryTree = useSelector(state => state.category.categoryTree);
const [treeData, setTreeData] = React.useState([]);
const [loadingCategories, setLoadingCategories] = React.useState(false);
const messageApi = App.useApp().message;
const [loading, setLoading] = useState(false);
const [imageFile, setImageFile] = useState(null);
const [imagePreview, setImagePreview] = useState(null);
const [categories, setCategories] = useState([]);
//const [categoriesLoading, setCategoriesLoading] = useState(false);
const [variantSetupModalVisible, setVariantSetupModalVisible] = useState(false);
  // ✅ THÊM STATE MỚI
const [attributeValues, setAttributeValues] = useState([]);
const [hasVariants, setHasVariants] = useState(0);

  const createLoading = useSelector(state => state.product.createLoading);
  const updateLoading = useSelector(state => state.product.updateLoading);
  
  // Formik setup
  const formik = useFormik({
    initialValues: {
      code: '',
      name: '',
      category_id: null,
      product_type: 'goods',
      unit: 'cái',
      purchase_price: 0,
      selling_price: 0,
      wholesale_price: 0,
      stock_quantity: 0,
      min_stock_alert: 0,
      max_stock_alert: 0,
      barcode: '',
      brand: '',
      weight: 0,
      //weight_unit: 'kg',
      description: '',
      note_template: '',
      warranty_period: 0,
      //warranty_unit: 'day',
      is_active: true,
      is_featured: false,
      is_available_online: true,
      attribute_values: [],
    },
    validationSchema: productSchema,
    validateOnChange: true,
    validateOnBlur: true,
    onSubmit: async (values) => {
      await handleSubmit(values);
    },
  });

  // Load product data for edit mode
  // eslint-disable-next-line react-hooks/exhaustive-deps
  useEffect(() => {
    if (mode === 'edit' && productId) {
      loadProductData();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [mode, productId]);

  // Load categories
  useEffect(() => {
    const loadCategories = async () => {
      setLoadingCategories(true);
      try {
        await dispatch(fetchCategoryTree()).unwrap();
      } catch (error) {
        console.error('Error loading categories:', error);
      } finally {
        setLoadingCategories(false);
      }
    };
    loadCategories();
  }, [dispatch]);

  useEffect(() => {
    const mapTreeForTreeSelect = (tree) => {
      if (!tree || !Array.isArray(tree)) return [];
      
      return tree.map(cat => ({
        title: cat.name,
        value: cat.id,
        key: cat.id,
        children: cat.children && cat.children.length > 0 
          ? mapTreeForTreeSelect(cat.children) 
          : undefined
      }));
    };

    setTreeData(mapTreeForTreeSelect(categoryTree));
  }, [categoryTree]);
  
  // Load product data for edit
  const loadProductData = async () => {
    try {
      setLoading(true);
      const response = await productApi.getProductDetail(productId);
      
      if (response.success) {
        const product = response.data;
        
        let productImage = null;
        if (product.primary_image && product.primary_image.image_url) {
          productImage = product.primary_image.image_url;
        } else if (product.image) {
          productImage = product.image;
        }
        
        if (productImage) {
          setImagePreview(productImage);
        }
        
        // ✅ FIX: Store old values as string/number for comparison
        const is_active_bool = product.is_active === 1 || product.is_active === '1';
        const is_featured_bool = product.is_featured === 1 || product.is_featured === '1';
        const is_available_online_bool = product.is_available_online === 1 || product.is_available_online === '1';
        setHasVariants(product.has_variants === 1 || product.has_variants === '1');
        
        //console.log('📥 Loaded booleans:', { is_active_bool, is_featured_bool, is_available_online_bool });
        
        formik.setValues({
          code: product.code || '',
          name: product.name || '',
          category_id: product.category_ids || [],
          product_type: product.product_type || 'goods',
          unit: product.unit || 'cái',
          purchase_price: product.purchase_price || 0,
          selling_price: product.selling_price || 0,
          wholesale_price: product.wholesale_price || 0,
          stock_quantity: product.stock_quantity || 0,
          min_stock_alert: product.min_stock_alert || 0,
          max_stock_alert: product.max_stock_alert || 0,
          barcode: product.barcode || '',
          brand: product.brand || '',
          weight: product.weight || 0,
          description: product.description || '',
          note_template: product.note_template || '',
          warranty_period: product.warranty_period || 0,
          is_active: is_active_bool,
          is_featured: is_featured_bool,
          is_available_online: is_available_online_bool,
        });
        // ✅ THÊM: Load attributes cho product
        if (product.id) {
          try {
            const attrResponse = await attributeApi.getProductAttributeValues(product.id);
            if (attrResponse.success && attrResponse.data) {
              setAttributeValues(attrResponse.data);
            }
          } catch (error) {
            console.error('Load attributes error:', error);
          }
        }
      }
    } catch (error) {
      messageApi.error('Failed to load product data');
      console.error('Load product error:', error);
    } finally {
      setLoading(false);
    }
  };

  // Handle form submit
  const handleSubmit = async (values) => {
    try {
      //console.log('🔥 HANDLE SUBMIT CALLED!', values); 
      // Upload image if selected
      let imageUrl = imagePreview;
      if (imageFile) {
        const uploadResponse = await productApi.uploadProductImage(imageFile);
        if (uploadResponse.success) {
          imageUrl = uploadResponse.data.url;
          console.log('✅ Image uploaded:', imageUrl);
        } else {
          messageApi.error('Cập nhật ảnh thất bại');
          return;
        }
      }
  
      // Convert boolean → 1/0 for API
      const submitData = {
        ...values,
        is_active: values.is_active === true ? 1 : 0,
        is_featured: values.is_featured === true ? 1 : 0,
        is_available_online: values.is_available_online === true ? 1 : 0,
        ...(imageUrl && { image: imageUrl }),
        // ✅ THÊM: Attributes
        attribute_values: attributeValues.map(av => ({
          attribute_id: av.attribute_id,
          value_text: av.value_text || null,
          option_id: av.option_id || null,
        })),
      };
  
      //console.log('📤 Submitting:', submitData);
  
      let response;
      if (mode === 'create') {
        response = await productApi.createProduct(submitData);
      } else {
        response = await productApi.updateProduct(productId, submitData);
      }
  
      // ✅ FIX #1: Check condition properly
      if (response && response.success) {
        const hasUpdatedFields = response.updated_fields && 
                                Object.keys(response.updated_fields).length > 0;
        
        //console.log('✅ SUCCESS - Has updated fields:', hasUpdatedFields);
        //console.log('📊 Updated fields keys:', Object.keys(response.updated_fields || {}));
        
        // ✅ FIX #3: Show message with updated fields list
        if (mode === 'edit' && hasUpdatedFields) {
          //console.log('🎯 SHOWING DETAILED MESSAGE WITH FIELDS');
          //console.log('UPDATED FIELDS:', response.updated_fields);
          
          const fieldLabels = {
            'is_active': 'Trạng thái',
            'is_featured': 'Sản phẩm nổi bật',
            'is_available_online': 'Bán online',
            'updated_at': 'Ngày cập nhật',
            'updated_by': 'Người cập nhật',
            'code': 'Mã hàng',
            'name': 'Tên sản phẩm',
            'category_id': 'Danh mục',
            'category_ids': 'Danh mục',  // <-- THÊM DÒNG NÀY
            'product_type': 'Loại sản phẩm',
            'unit': 'Đơn vị tính',
            'purchase_price': 'Giá vốn',
            'selling_price': 'Giá bán',
            'wholesale_price': 'Giá bán buôn',
            'stock_quantity': 'Tồn kho hiện tại',
            'min_stock_alert': 'Tồn kho tối thiểu',
            'max_stock_alert': 'Tồn kho tối đa',
            'barcode': 'Mã vạch',
            'brand': 'Thương hiệu',
            'weight': 'Cân nặng (kg)',
            'warranty_period': 'Thời gian bảo hành (tháng)',
            'description': 'Mô tả',
            'note_template': 'Ghi chú',
            'image': 'Hình ảnh',
          };

          const fieldsList = Object.entries(response.updated_fields)
            .map(([field, values]) => {
              const label = fieldLabels[field] || field;
              let oldVal, newVal;
              if (field === 'category_ids') {
                // Chuyển ID sang tên từ tree redux
                const oldNames = (values.old_names || []).join(', ');
                const newNames = (values.new_names || []).join(', ');
                oldVal = oldNames;
                newVal = newNames;
              } else {
                oldVal = String(values.old || '(trống)').substring(0, 40);
                newVal = String(values.new || '(trống)').substring(0, 40);
              }
              return `• ${label}: ${oldVal} → ${newVal}`;
            })
            .join('\n');
          //console.log('🎯 Fields list prepared:', fieldsList);

          // ✅ FIX #3: Use messageApi.success() with custom content
          messageApi.success({
            content: (
              <div className={styles.updateMessage}>
                <div className={styles.messageHeader}>
                  <span style={{ fontSize: '20px' }}>✅</span>
                  <div>
                    <p className={styles.messageTitle}>
                      Cập nhật sản phẩm thành công
                    </p>
                    <p className={styles.messageSubtitle}>
                      ID: <span style={{ fontWeight: '600', color: '#1890ff' }}>{productId}</span>
                    </p>
                  </div>
                </div>
                <div className={styles.messageDivider} />
                <div className={styles.fieldsContainer}>
                  <div className={styles.fieldsLabel}>
                    📝 Các trường được cập nhật ({Object.keys(response.updated_fields).length}):
                  </div>
                  <div className={styles.fieldsList}>
                    {fieldsList}
                  </div>
                </div>
              </div>
            ),
            duration: 4,
            top: '10vh'
          });
        } 
        else if (mode === 'create') {
          messageApi.success('✅ Tạo mới sản phẩm thành công', 3);
        }
        else {
          messageApi.success(response.message || '✅ Cập nhật sản phẩm thành công', 3);
        }
  
        // ✅ THÊM: Update attributes nếu có
        if (attributeValues.length > 0) {
          try {
            const savedProductId = mode === 'create' ? response.data.id : productId;
            const payload = { attribute_values: attributeValues };
            await attributeApi.updateProductAttributeValues(savedProductId, payload);        
            console.log('✅ Attributes saved successfully');
          } catch (attrError) {
            console.error('❌ Save attributes error:', attrError);
            messageApi.warning('Sản phẩm đã lưu nhưng không thể cập nhật thuộc tính');
          }
        }        
        dispatch(fetchProducts());
        
        if (onSuccess) {
          //console.log('📞 CALLING ONSUCCESS CALLBACK');
          onSuccess(response.data);
        }
  
      } else {
        console.log('❌ ERROR BRANCH');
        messageApi.error(response?.message || 'Cập nhật thất bại', 3);
      }
  
    } catch (error) {
      console.error('💥 CATCH ERROR:', error);
      messageApi.error(error.message || 'Có lỗi xảy ra', 3);
    }
  };  

  const getFieldError = (fieldName) => {
    return formik.touched[fieldName] && formik.errors[fieldName];
  };

  const isSubmitting = mode === 'create' ? createLoading : updateLoading;

  if (loading) {
    return <Spin indicator={<LoadingOutlined />} />;
  }

  return (
    <>
    <Form className={styles.productForm} layout="vertical" onFinish={formik.handleSubmit}>
      <Row gutter={24}>
        {/* Left Column */}
        <Col xs={24} sm={24} md={16}>
          {/* Basic Information */}
          <div className={styles.section}>
            <h3>Thông tin cơ bản</h3>

            {/* Code */}
            <Form.Item
              label="Mã hàng"
              required
              validateStatus={getFieldError('code') ? 'error' : ''}
              help={getFieldError('code')}
            >
              <Input
                placeholder="e.g., SKU-001"
                name="code"
                value={formik.values.code}
                onChange={formik.handleChange}
                onBlur={formik.handleBlur}
                disabled={mode === 'edit'}
              />
            </Form.Item>

            {/* Name */}
            <Form.Item
              label="Tên sản phẩm"
              required
              validateStatus={getFieldError('name') ? 'error' : ''}
              help={getFieldError('name')}
            >
              <Input
                placeholder="Product name"
                name="name"
                value={formik.values.name}
                onChange={formik.handleChange}
                onBlur={formik.handleBlur}
              />
            </Form.Item>

            {/* Category */}
            <Form.Item
              label="Danh mục"
              required
              validateStatus={formik.errors.category_id && formik.touched.category_id ? 'error' : ''}
              help={formik.errors.category_id && formik.touched.category_id ? formik.errors.category_id : ''}
            >
              <TreeSelect
                treeCheckable
                treeCheckStrictly
                showCheckedStrategy={TreeSelect.SHOW_PARENT}
                placeholder="Chọn danh mục (có thể chọn nhiều)"
                loading={loadingCategories}
                treeData={treeData}
                value={formik.values.category_id || []}
                onChange={(checkedNodes) => {
                  // checkedNodes sẽ là array object { value, label, ... }

                  // 1. Lấy ra toàn bộ giá trị value của tất cả node được tick (bao gồm cha và con)
                  const allCheckedIds = Array.isArray(checkedNodes)
                    ? checkedNodes.map(item => (item && typeof item === 'object' ? item.value : item))
                    : [];

                  // 2. Có thể optional: filter unique nếu bị trùng do cơ chế tick cha-con
                  const uniqIds = [...new Set(allCheckedIds)];

                  // 3. Gán vào formik
                  formik.setFieldValue('category_id', uniqIds);
                }}
                treeDefaultExpandAll={false}
                showSearch
                filterTreeNode={(input, treeNode) =>
                  treeNode.title.toLowerCase().includes(input.toLowerCase())
                }
                style={{ width: '100%' }}
                classNames={{
                  popup: {
                    root: 'category-tree-popup'
                  }
                }}
                maxTagCount={3}
                maxTagPlaceholder={omittedValues => `+${omittedValues.length} danh mục`}
                allowClear
              />
            </Form.Item>

            {/* Product Type */}
            <Form.Item
              label="Loại sản phẩm"
              required
              validateStatus={getFieldError('product_type') ? 'error' : ''}
              help={getFieldError('product_type')}
            >
              <Select
                placeholder="Select type"
                value={formik.values.product_type}
                onChange={(value) => formik.setFieldValue('product_type', value)}
                options={[
                  { label: 'Hàng hóa', value: 'goods' },
                  { label: 'Combo', value: 'combo' },
                  { label: 'Dịch vụ', value: 'service' },
                ]}
              />
            </Form.Item>

            {/* Unit */}
            <Form.Item
              label="Đơn vị tính"
              required
              validateStatus={getFieldError('unit') ? 'error' : ''}
              help={getFieldError('unit')}
            >
              <Select
                placeholder="Select unit"
                value={formik.values.unit}
                onChange={(value) => formik.setFieldValue('unit', value)}
                options={[
                  { label: 'Cái', value: 'cái' },
                  { label: 'Bộ', value: 'bộ' },
                  { label: 'Hộp', value: 'hộp' },
                  { label: 'Kg', value: 'kg' },
                  { label: 'Lít', value: 'lít' },
                ]}
              />
            </Form.Item>

            {/* Description */}
            <Form.Item
              label="Mô tả"
              validateStatus={getFieldError('description') ? 'error' : ''}
              help={getFieldError('description')}
            >
              <Input.TextArea
                placeholder="Product description"
                rows={4}
                name="description"
                value={formik.values.description}
                onChange={formik.handleChange}
                onBlur={formik.handleBlur}
              />
            </Form.Item>

            {/* note_template */}
            <Form.Item label="Ghi chú">
              <Input.TextArea
                placeholder="Additional note_template"
                rows={3}
                name="note_template"
                value={formik.values.note_template}
                onChange={formik.handleChange}
                onBlur={formik.handleBlur}
              />
            </Form.Item>
          </div>

          {/* Pricing Section */}
          <div className={styles.section}>
            <h3>Giá cả</h3>

            <Row gutter={16}>
              <Col xs={24} sm={12}>
                <Form.Item
                  label="Giá vốn"
                  required
                  validateStatus={getFieldError('purchase_price') ? 'error' : ''}
                  help={getFieldError('purchase_price')}
                >
                  <InputNumber
                    placeholder="0"
                    formatter={(value) => `₫ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                    parser={(value) => parseFloat(value.replace(/₫\s?|(,*)/g, ''))}
                    min={0}
                    name="purchase_price"
                    value={formik.values.purchase_price}
                    onChange={(value) => formik.setFieldValue('purchase_price', value || 0)}
                    className={styles.fullWidth}
                  />
                </Form.Item>
              </Col>

              <Col xs={24} sm={12}>
                <Form.Item
                  label="Giá bán"
                  required
                  validateStatus={getFieldError('selling_price') ? 'error' : ''}
                  help={getFieldError('selling_price')}
                >
                  <InputNumber
                    placeholder="0"
                    formatter={(value) => `₫ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                    parser={(value) => parseFloat(value.replace(/₫\s?|(,*)/g, ''))}
                    min={0}
                    name="selling_price"
                    value={formik.values.selling_price}
                    onChange={(value) => formik.setFieldValue('selling_price', value || 0)}
                    className={styles.fullWidth}
                  />
                </Form.Item>
              </Col>

              <Col xs={24} sm={12}>
                <Form.Item label="Giá bán buôn">
                  <InputNumber
                    placeholder="0"
                    formatter={(value) => `₫ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                    parser={(value) => parseFloat(value.replace(/₫\s?|(,*)/g, ''))}
                    min={0}
                    name="wholesale_price"
                    value={formik.values.wholesale_price}
                    onChange={(value) => formik.setFieldValue('wholesale_price', value || 0)}
                    className={styles.fullWidth}
                  />
                </Form.Item>
              </Col>
            </Row>
          </div>

          {/* Stock Section */}
          <div className={styles.section}>
            <h3>Tồn kho</h3>

            <Row gutter={16}>
              <Col xs={24} sm={8}>
                <Form.Item
                  label="Tồn kho hiện tại"
                  required
                  validateStatus={getFieldError('stock_quantity') ? 'error' : ''}
                  help={getFieldError('stock_quantity')}
                >
                  <InputNumber
                    placeholder="0"
                    min={0}
                    name="stock_quantity"
                    value={formik.values.stock_quantity}
                    onChange={(value) => formik.setFieldValue('stock_quantity', value || 0)}
                    className={styles.fullWidth}
                  />
                </Form.Item>
              </Col>

              <Col xs={24} sm={8}>
                <Form.Item label="Tồn kho tối thiểu">
                  <InputNumber
                    placeholder="0"
                    min={0}
                    name="min_stock_alert"
                    value={formik.values.min_stock_alert}
                    onChange={(value) => formik.setFieldValue('min_stock_alert', value || 0)}
                    className={styles.fullWidth}
                  />
                </Form.Item>
              </Col>

              <Col xs={24} sm={8}>
                <Form.Item label="Tồn kho tối đa">
                  <InputNumber
                    placeholder="0"
                    min={0}
                    name="max_stock_alert"
                    value={formik.values.max_stock_alert}
                    onChange={(value) => formik.setFieldValue('max_stock_alert', value || 0)}
                    className={styles.fullWidth}
                  />
                </Form.Item>
              </Col>
            </Row>
          </div>
        </Col>

        {/* Right Column */}
        <Col xs={24} sm={24} md={8}>
          {/* Additional Info */}
          <div className={styles.section}>
            <h3>Thông tin thêm</h3>

            <Form.Item label="Thương hiệu">
              <Input
                placeholder="Brand name"
                name="brand"
                value={formik.values.brand}
                onChange={formik.handleChange}
              />
            </Form.Item>

            <Form.Item label="Mã vạch">
              <Input
                placeholder="Barcode"
                name="barcode"
                value={formik.values.barcode}
                onChange={formik.handleChange}
              />
            </Form.Item>

                        {/* ✅ FIX #2: Remove weight_unit - updated label */}
            <Form.Item label="Cân nặng (kg)">
              <InputNumber
                placeholder="0"
                min={0}
                name="weight"
                value={formik.values.weight}
                onChange={(value) => formik.setFieldValue('weight', value || 0)}
                className={styles.fullWidth}
              />
            </Form.Item>

            {/* ✅ FIX #2: Remove warranty_unit dropdown - updated label */}
            <Form.Item label="Thời gian bảo hành (tháng)">
              <InputNumber
                placeholder="0"
                min={0}
                name="warranty_period"
                value={formik.values.warranty_period}
                onChange={(value) => formik.setFieldValue('warranty_period', value || 0)}
                className={styles.fullWidth}
              />
            </Form.Item>

            {/* Status checkboxes */}
            <Form.Item>
              <Checkbox
                checked={formik.values.is_active}
                onChange={(e) => formik.setFieldValue('is_active', e.target.checked)}
              >
                Hoạt động
              </Checkbox>
            </Form.Item>

            <Form.Item>
              <Checkbox
                checked={formik.values.is_featured}
                onChange={(e) => formik.setFieldValue('is_featured', e.target.checked)}
              >
                Sản phẩm nổi bật
              </Checkbox>
            </Form.Item>

            <Form.Item>
              <Checkbox
                checked={formik.values.is_available_online}
                onChange={(e) => formik.setFieldValue('is_available_online', e.target.checked)}
              >
                Có bán được
              </Checkbox>
            </Form.Item>
          </div>

          {/* Form Actions */}
          <div className={styles.section}>
            <Space direction="vertical" style={{ width: '100%' }}>
              <Button
                type="primary"
                htmlType="submit"
                loading={isSubmitting}
                block
              >
                {mode === 'create' ? 'Thêm sản phẩm' : 'Cập nhật sản phẩm'}
              </Button>

              {/* ✅ NÚT THIẾT LẬP BIẾN THỂ - chỉ hiện với sản phẩm simple */}
              {mode === 'edit' && !hasVariants && (
                <Button
                  type="default"
                  icon={<SettingOutlined />}
                  onClick={() => setVariantSetupModalVisible(true)}
                  block
                  style={{
                    borderColor: '#1890ff',
                    color: '#1890ff',
                    fontWeight: 500,
                  }}
                >
                  Thiết lập biến thể
                </Button>
              )}

              <Button onClick={onCancel} block>
                Hủy
              </Button>
            </Space>
          </div>
        </Col>
      </Row>
    </Form>
    {/* ✅ MODAL - ĐẶT TRONG RETURN, SAU </Form> */}
    {mode === 'edit' && productId && (
      <VariantSetupModal
        visible={variantSetupModalVisible}
        onClose={() => {
          console.log('🔴 Modal closing...');
          setVariantSetupModalVisible(false);
        }}
        onSuccess={() => {
          loadProductData();
          messageApi.success('Đã chuyển sang sản phẩm biến thể');
          if (onSuccess) onSuccess();
        }}
        productId={productId}
        productCode={formik.values.code}
      />
    )}
    </>
  );
};

export default ProductForm;
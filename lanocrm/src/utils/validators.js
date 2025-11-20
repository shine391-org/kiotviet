/**
 * Validation Schemas (Yup)
 * @file src/utils/validators.js
 * @description Form validation schemas for products and categories
 */

import * as yup from 'yup';

/**
 * Product Form Validation Schema
 * @type {yup.ObjectSchema}
 */
export const productSchema = yup.object().shape({
  // Basic Info
  code: yup
    .string()
    .required('Mã hàng là bắt buộc')
    .min(1, 'Mã hàng tối thiểu 1 ký tự')
    .max(50, 'Mã hàng tối đa 50 ký tự')
    .matches(/^[a-zA-Z0-9_-]+$/, 'Mã hàng chỉ chứa chữ, số, gạch dưới và gạch ngang'),
  
  name: yup
    .string()
    .required('Tên hàng là bắt buộc')
    .min(3, 'Tên hàng tối thiểu 3 ký tự')
    .max(255, 'Tên hàng tối đa 255 ký tự')
    .trim(),
  
  /*category_id: yup
    .number()
    .required('Nhóm hàng là bắt buộc')
    .positive('Vui lòng chọn nhóm hàng')
    .integer('ID nhóm hàng không hợp lệ')
    .min(1, 'Phải chọn ít nhất 1 danh mục'),
  */
  category_id: yup
  .array()
  .min(1, 'Phải chọn ít nhất 1 danh mục')
  .of(
    yup.number()
      .positive('ID nhóm hàng không hợp lệ')
      .integer('ID nhóm hàng không hợp lệ')
  )
  .required('Nhóm hàng là bắt buộc'),  
  
  product_type: yup
    .string()
    .oneOf(['goods', 'combo', 'service'], 'Loại hàng không hợp lệ')
    .required('Loại hàng là bắt buộc'),
  
  unit: yup
    .string()
    .required('Đơn vị tính là bắt buộc')
    .max(20, 'Đơn vị tính tối đa 20 ký tự')
    .trim(),
  
  // Pricing
  selling_price: yup
    .number()
    .required('Giá bán là bắt buộc')
    .min(0, 'Giá bán phải >= 0')
    .max(999999999, 'Giá bán tối đa 999,999,999')
    .typeError('Giá bán phải là số'),
  
  cost_price: yup
    .number()
    .min(0, 'Giá vốn phải >= 0')
    .max(999999999, 'Giá vốn tối đa 999,999,999')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    )
    .typeError('Giá vốn phải là số'),
  
  wholesale_price: yup
    .number()
    .min(0, 'Giá sỉ phải >= 0')
    .max(999999999, 'Giá sỉ tối đa 999,999,999')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    )
    .typeError('Giá sỉ phải là số'),
  
  // Inventory
  stock_quantity: yup
    .number()
    .min(0, 'Tồn kho phải >= 0')
    .integer('Tồn kho phải là số nguyên')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    )
    .typeError('Tồn kho phải là số'),
  
  min_stock_threshold: yup
    .number()
    .min(0, 'Định mức tồn thấp nhất phải >= 0')
    .integer('Định mức tồn phải là số nguyên')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    )
    .typeError('Định mức tồn thấp nhất phải là số'),
  
  max_stock_threshold: yup
    .number()
    .min(0, 'Định mức tồn cao nhất phải >= 0')
    .integer('Định mức tồn phải là số nguyên')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    )
    .test(
      'max-greater-than-min',
      'Định mức tồn cao nhất phải >= thấp nhất',
      function(value) {
        const { min_stock_threshold } = this.parent;
        if (!value || !min_stock_threshold) return true;
        return value >= min_stock_threshold;
      }
    )
    .typeError('Định mức tồn cao nhất phải là số'),
  
  barcode: yup
    .string()
    .max(100, 'Mã vạch tối đa 100 ký tự')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    ),
  
  // Other
  brand: yup
    .string()
    .max(100, 'Thương hiệu tối đa 100 ký tự')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    ),
  
  weight: yup
    .number()
    .min(0, 'Trọng lượng phải >= 0')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    )
    .typeError('Trọng lượng phải là số'),
  
  description: yup
    .string()
    .max(5000, 'Mô tả tối đa 5000 ký tự')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    ),
  
  notes: yup
    .string()
    .max(1000, 'Ghi chú tối đa 1000 ký tự')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    ),
  
  warranty_period: yup
    .number()
    .min(0, 'Thời gian bảo hành phải >= 0')
    .integer('Thời gian bảo hành phải là số nguyên')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    )
    .typeError('Thời gian bảo hành phải là số'),
  
  is_active: yup
    .boolean()
    .default(true),
  
  is_featured: yup
    .boolean()
    .default(false),
  
  is_sellable: yup
    .boolean()
    .default(true),
  
  // SEO (optional)
  meta_title: yup
    .string()
    .max(255, 'Meta title tối đa 255 ký tự')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    ),
  
  meta_description: yup
    .string()
    .max(500, 'Meta description tối đa 500 ký tự')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    ),
  
  meta_keywords: yup
    .string()
    .max(255, 'Meta keywords tối đa 255 ký tự')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    ),
});

/**
 * Category Form Validation Schema
 * @type {yup.ObjectSchema}
 */
export const categorySchema = yup.object().shape({
  name: yup
    .string()
    .required('Tên nhóm hàng là bắt buộc')
    .min(2, 'Tên nhóm hàng tối thiểu 2 ký tự')
    .max(255, 'Tên nhóm hàng tối đa 255 ký tự')
    .trim(),
  
  code: yup
    .string()
    .max(50, 'Mã nhóm hàng tối đa 50 ký tự')
    .matches(/^[a-zA-Z0-9_-]*$/, 'Mã nhóm hàng chỉ chứa chữ, số, gạch dưới và gạch ngang')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    ),
  
  parent_id: yup
    .number()
    .positive('ID cha không hợp lệ')
    .integer('ID cha không hợp lệ')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    ),
  
  description: yup
    .string()
    .max(1000, 'Mô tả tối đa 1000 ký tự')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    ),
  
  sort_order: yup
    .number()
    .min(0, 'Thứ tự phải >= 0')
    .integer('Thứ tự phải là số nguyên')
    .nullable()
    .transform((value, originalValue) => 
      originalValue === '' ? null : value
    ),
  
  is_active: yup
    .boolean()
    .default(true),
});

/**
 * Product Filter Validation Schema
 * @type {yup.ObjectSchema}
 */
export const filterSchema = yup.object().shape({
  search: yup.string().max(255).nullable(),
  category_id: yup
  .array()
  .of(
    yup.number()
      .positive('ID nhóm hàng không hợp lệ')
      .integer('ID nhóm hàng không hợp lệ')
  )
  .nullable()  // ✅ NULLABLE - không bắt buộc chọn
  .transform((value, originalValue) => 
    originalValue === '' || (Array.isArray(originalValue) && originalValue.length === 0) 
      ? null 
      : value
  ),  // ✅ Chuyển [] hoặc '' thành null

  product_type: yup.string().oneOf(['single', 'combo', 'service']).nullable(),
  status: yup.string().oneOf(['active', 'inactive', 'out_of_stock']).nullable(),
  is_active: yup.boolean().nullable(),
  brand: yup.string().max(100).nullable(),
  price_from: yup.number().min(0).nullable(),
  price_to: yup.number().min(0).nullable(),
  stock_from: yup.number().min(0).integer().nullable(),
  stock_to: yup.number().min(0).integer().nullable(),
});

export default {
  productSchema,
  categorySchema,
  filterSchema,
};
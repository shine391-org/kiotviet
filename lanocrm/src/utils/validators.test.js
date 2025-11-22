import { describe, it, expect } from 'vitest';
import { productSchema, categorySchema } from './validators';

describe('validators', () => {
  describe('productSchema', () => {
    it('should validate a valid product', async () => {
      const validProduct = {
        code: 'PROD001',
        name: 'Test Product',
        category_id: [1],
        product_type: 'goods',
        unit: 'pcs',
        selling_price: 100000,
        cost_price: 80000,
        stock_quantity: 10,
        is_active: true,
      };

      await expect(productSchema.validate(validProduct)).resolves.toBeDefined();
    });

    it('should fail if required fields are missing', async () => {
      const invalidProduct = {
        code: '',
        name: '',
      };

      await expect(productSchema.validate(invalidProduct)).rejects.toThrow();
    });

    it('should validate price constraints', async () => {
      const productWithInvalidPrice = {
        code: 'PROD001',
        name: 'Test',
        category_id: [1],
        product_type: 'goods',
        unit: 'pcs',
        selling_price: -100, // Invalid
      };

      await expect(productSchema.validate(productWithInvalidPrice)).rejects.toThrow('Giá bán phải >= 0');
    });

    it('should validate stock thresholds', async () => {
         const productWithInvalidStock = {
            code: 'PROD001',
            name: 'Test',
            category_id: [1],
            product_type: 'goods',
            unit: 'pcs',
            selling_price: 10000,
            min_stock_threshold: 10,
            max_stock_threshold: 5, // Invalid, less than min
         };

         await expect(productSchema.validate(productWithInvalidStock)).rejects.toThrow('Định mức tồn cao nhất phải >= thấp nhất');
    });
  });

  describe('categorySchema', () => {
    it('should validate a valid category', async () => {
      const validCategory = {
        name: 'Category 1',
        code: 'CAT001',
      };
      await expect(categorySchema.validate(validCategory)).resolves.toBeDefined();
    });

    it('should fail if name is missing', async () => {
      const invalidCategory = {
        code: 'CAT001',
      };
      await expect(categorySchema.validate(invalidCategory)).rejects.toThrow('Tên nhóm hàng là bắt buộc');
    });
  });
});

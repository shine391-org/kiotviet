import { describe, it, expect } from 'vitest';
import { productSchema, categorySchema, filterSchema } from './validators';

describe('validators', () => {
    describe('productSchema', () => {
        describe('code field', () => {
            it('should validate valid product code', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'Test Product',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 100,
                };
                await expect(productSchema.validate(data)).resolves.toBeDefined();
            });

            it('should reject empty code', async () => {
                const data = {
                    code: '',
                    name: 'Test Product',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 100,
                };
                await expect(productSchema.validate(data)).rejects.toThrow('Mã hàng là bắt buộc');
            });

            it('should reject code with special characters', async () => {
                const data = {
                    code: 'PROD@001',
                    name: 'Test Product',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 100,
                };
                await expect(productSchema.validate(data)).rejects.toThrow(
                    'Mã hàng chỉ chứa chữ, số, gạch dưới và gạch ngang'
                );
            });

            it('should reject code exceeding max length', async () => {
                const data = {
                    code: 'A'.repeat(51),
                    name: 'Test Product',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 100,
                };
                await expect(productSchema.validate(data)).rejects.toThrow('Mã hàng tối đa 50 ký tự');
            });
        });

        describe('name field', () => {
            it('should validate valid product name', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'Test Product',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 100,
                };
                await expect(productSchema.validate(data)).resolves.toBeDefined();
            });

            it('should reject name shorter than 3 characters', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'AB',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 100,
                };
                await expect(productSchema.validate(data)).rejects.toThrow('Tên hàng tối thiểu 3 ký tự');
            });

            it('should trim whitespace from name', async () => {
                const data = {
                    code: 'PROD-001',
                    name: '  Test Product  ',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 100,
                };
                const result = await productSchema.validate(data);
                expect(result.name).toBe('Test Product');
            });
        });

        describe('category_id field', () => {
            it('should validate array of category IDs', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'Test Product',
                    category_id: [1, 2, 3],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 100,
                };
                await expect(productSchema.validate(data)).resolves.toBeDefined();
            });

            it('should reject empty category array', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'Test Product',
                    category_id: [],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 100,
                };
                await expect(productSchema.validate(data)).rejects.toThrow('Phải chọn ít nhất 1 danh mục');
            });

            it('should reject negative category ID', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'Test Product',
                    category_id: [-1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 100,
                };
                await expect(productSchema.validate(data)).rejects.toThrow('ID nhóm hàng không hợp lệ');
            });
        });

        describe('product_type field', () => {
            it('should validate valid product types', async () => {
                for (const type of ['goods', 'combo', 'service']) {
                    const data = {
                        code: 'PROD-001',
                        name: 'Test Product',
                        category_id: [1],
                        product_type: type,
                        unit: 'pcs',
                        selling_price: 100,
                    };
                    await expect(productSchema.validate(data)).resolves.toBeDefined();
                }
            });

            it('should reject invalid product type', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'Test Product',
                    category_id: [1],
                    product_type: 'invalid',
                    unit: 'pcs',
                    selling_price: 100,
                };
                await expect(productSchema.validate(data)).rejects.toThrow('Loại hàng không hợp lệ');
            });
        });

        describe('selling_price field', () => {
            it('should validate positive price', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'Test Product',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 99999,
                };
                await expect(productSchema.validate(data)).resolves.toBeDefined();
            });

            it('should accept zero price', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'Test Product',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 0,
                };
                await expect(productSchema.validate(data)).resolves.toBeDefined();
            });

            it('should reject negative price', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'Test Product',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: -100,
                };
                await expect(productSchema.validate(data)).rejects.toThrow('Giá bán phải >= 0');
            });

            it('should reject non-numeric price', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'Test Product',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 'invalid',
                };
                await expect(productSchema.validate(data)).rejects.toThrow('Giá bán phải là số');
            });
        });

        describe('stock_quantity field', () => {
            it('should accept integer stock quantity', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'Test Product',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 100,
                    stock_quantity: 50,
                };
                await expect(productSchema.validate(data)).resolves.toBeDefined();
            });

            it('should reject decimal stock quantity', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'Test Product',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 100,
                    stock_quantity: 50.5,
                };
                await expect(productSchema.validate(data)).rejects.toThrow('Tồn kho phải là số nguyên');
            });

            it('should transform empty string to null', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'Test Product',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 100,
                    stock_quantity: '',
                };
                const result = await productSchema.validate(data);
                expect(result.stock_quantity).toBeNull();
            });
        });

        describe('max_stock_threshold validation', () => {
            it('should validate max >= min threshold', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'Test Product',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 100,
                    min_stock_threshold: 10,
                    max_stock_threshold: 100,
                };
                await expect(productSchema.validate(data)).resolves.toBeDefined();
            });

            it('should reject max < min threshold', async () => {
                const data = {
                    code: 'PROD-001',
                    name: 'Test Product',
                    category_id: [1],
                    product_type: 'goods',
                    unit: 'pcs',
                    selling_price: 100,
                    min_stock_threshold: 100,
                    max_stock_threshold: 10,
                };
                await expect(productSchema.validate(data)).rejects.toThrow(
                    'Định mức tồn cao nhất phải >= thấp nhất'
                );
            });
        });
    });

    describe('categorySchema', () => {
        describe('name field', () => {
            it('should validate valid category name', async () => {
                const data = {
                    name: 'Electronics',
                };
                await expect(categorySchema.validate(data)).resolves.toBeDefined();
            });

            it('should reject name shorter than 2 characters', async () => {
                const data = {
                    name: 'A',
                };
                await expect(categorySchema.validate(data)).rejects.toThrow(
                    'Tên nhóm hàng tối thiểu 2 ký tự'
                );
            });

            it('should trim whitespace', async () => {
                const data = {
                    name: '  Electronics  ',
                };
                const result = await categorySchema.validate(data);
                expect(result.name).toBe('Electronics');
            });
        });

        describe('code field', () => {
            it('should validate valid code', async () => {
                const data = {
                    name: 'Electronics',
                    code: 'ELEC-001',
                };
                await expect(categorySchema.validate(data)).resolves.toBeDefined();
            });

            it('should reject code with special characters', async () => {
                const data = {
                    name: 'Electronics',
                    code: 'ELEC@001',
                };
                await expect(categorySchema.validate(data)).rejects.toThrow(
                    'Mã nhóm hàng chỉ chứa chữ, số, gạch dưới và gạch ngang'
                );
            });

            it('should transform empty string to null', async () => {
                const data = {
                    name: 'Electronics',
                    code: '',
                };
                const result = await categorySchema.validate(data);
                expect(result.code).toBeNull();
            });
        });

        describe('parent_id field', () => {
            it('should accept positive integer', async () => {
                const data = {
                    name: 'Subcategory',
                    parent_id: 5,
                };
                await expect(categorySchema.validate(data)).resolves.toBeDefined();
            });

            it('should reject negative parent_id', async () => {
                const data = {
                    name: 'Subcategory',
                    parent_id: -1,
                };
                await expect(categorySchema.validate(data)).rejects.toThrow('ID cha không hợp lệ');
            });

            it('should transform empty string to null', async () => {
                const data = {
                    name: 'Category',
                    parent_id: '',
                };
                const result = await categorySchema.validate(data);
                expect(result.parent_id).toBeNull();
            });
        });
    });

    describe('filterSchema', () => {
        it('should validate empty filter', async () => {
            const data = {};
            await expect(filterSchema.validate(data)).resolves.toBeDefined();
        });

        it('should validate search filter', async () => {
            const data = {
                search: 'laptop',
            };
            await expect(filterSchema.validate(data)).resolves.toBeDefined();
        });

        it('should validate category_id array', async () => {
            const data = {
                category_id: [1, 2, 3],
            };
            await expect(filterSchema.validate(data)).resolves.toBeDefined();
        });

        it('should transform empty array to null', async () => {
            const data = {
                category_id: [],
            };
            const result = await filterSchema.validate(data);
            expect(result.category_id).toBeNull();
        });

        it('should validate price range', async () => {
            const data = {
                price_from: 100,
                price_to: 1000,
            };
            await expect(filterSchema.validate(data)).resolves.toBeDefined();
        });

        it('should validate stock range', async () => {
            const data = {
                stock_from: 10,
                stock_to: 100,
            };
            await expect(filterSchema.validate(data)).resolves.toBeDefined();
        });

        it('should validate product_type filter', async () => {
            for (const type of ['single', 'combo', 'service']) {
                const data = {
                    product_type: type,
                };
                await expect(filterSchema.validate(data)).resolves.toBeDefined();
            }
        });

        it('should validate status filter', async () => {
            for (const status of ['active', 'inactive', 'out_of_stock']) {
                const data = {
                    status,
                };
                await expect(filterSchema.validate(data)).resolves.toBeDefined();
            }
        });
    });
});

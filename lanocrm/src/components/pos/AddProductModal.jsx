import React, { useState, useEffect, useCallback } from 'react';
import { Modal, Input, Select, Button, Upload, App } from 'antd';
import { PlusOutlined, PictureOutlined } from '@ant-design/icons';
import posApi from '../../api/posApi';
import categoryApi from '../../api/categoryApi';
import styles from './AddProductModal.module.css';

const AddProductModal = ({ open, onClose, onSave, initialName = '' }) => {
    const { message } = App.useApp();
    const [loading, setLoading] = useState(false);
    const [categories, setCategories] = useState([]);
    const [categoriesLoading, setCategoriesLoading] = useState(false);
    const [formData, setFormData] = useState({
        productCode: '',
        productName: initialName,
        category: null,
        costPrice: '',
        salePrice: '',
        stock: '',
        unit: 'Cái',
        images: [],
    });

    // Fetch categories from API
    const fetchCategories = useCallback(async () => {
        setCategoriesLoading(true);
        try {
            const response = await categoryApi.getCategories({ limit: 100 });
            if (response.success && response.data) {
                setCategories(response.data.map(c => ({
                    value: c.id,
                    label: c.name,
                })));
            }
        } catch (error) {
            console.error('Failed to fetch categories:', error);
        } finally {
            setCategoriesLoading(false);
        }
    }, []);

    // Reset form when modal opens with new initialName
    useEffect(() => {
        if (open) {
            setFormData(prev => ({
                ...prev,
                productName: initialName,
                productCode: '',
            }));
            fetchCategories();
        }
    }, [open, initialName, fetchCategories]);

    const handleChange = (field, value) => {
        setFormData(prev => ({ ...prev, [field]: value }));
    };

    const handleSave = async () => {
        if (!formData.productName?.trim()) {
            message.error('Vui lòng nhập tên sản phẩm');
            return;
        }

        setLoading(true);
        try {
            const response = await posApi.createProduct({
                productCode: formData.productCode || undefined,
                productName: formData.productName,
                category: formData.category,
                costPrice: parseFloat(formData.costPrice) || 0,
                salePrice: parseFloat(formData.salePrice) || 0,
                stock: parseInt(formData.stock) || 0,
                unit: formData.unit || 'Cái',
            });

            if (response.success) {
                message.success('Thêm sản phẩm thành công');
                onSave?.({
                    id: response.data.id,
                    sku: response.data.code,
                    name: response.data.name,
                    unitPrice: parseFloat(response.data.selling_price) || 0,
                });
                onClose?.();
            } else {
                message.error(response.message || 'Không thể thêm sản phẩm');
            }
        } catch (error) {
            console.error('Create product error:', error);
            message.error(error.response?.data?.message || 'Lỗi khi thêm sản phẩm');
        } finally {
            setLoading(false);
        }
    };

    const handleImageUpload = (info) => {
        // Handle image upload - mock for now
        console.log('Upload:', info);
    };

    return (
        <Modal
            title="Thêm mới hàng hóa"
            open={open}
            onCancel={onClose}
            width={700}
            footer={
                <div className={styles.modalFooter}>
                    <Button onClick={onClose}>Bỏ qua</Button>
                    <Button type="primary" onClick={handleSave} loading={loading}>Lưu</Button>
                </div>
            }
            className={styles.modal}
        >
            <div className={styles.form}>
                <div className={styles.formColumns}>
                    {/* Left Column */}
                    <div className={styles.formColumn}>
                        {/* Product Code */}
                        <div className={styles.formRow}>
                            <label className={styles.label}>Mã hàng</label>
                            <Input
                                placeholder="Mã hàng tự động"
                                value={formData.productCode}
                                onChange={(e) => handleChange('productCode', e.target.value)}
                                className={styles.input}
                            />
                        </div>

                        {/* Product Name */}
                        <div className={styles.formRow}>
                            <label className={styles.label}>Tên hàng</label>
                            <Input
                                value={formData.productName}
                                onChange={(e) => handleChange('productName', e.target.value)}
                                className={styles.input}
                            />
                        </div>

                        {/* Category */}
                        <div className={styles.formRow}>
                            <label className={styles.label}>Nhóm hàng</label>
                            <div className={styles.selectWithAdd}>
                                <Select
                                    placeholder="---Lựa chọn---"
                                    value={formData.category}
                                    onChange={(val) => handleChange('category', val)}
                                    options={categories}
                                    loading={categoriesLoading}
                                    className={styles.select}
                                    showSearch
                                    filterOption={(input, option) =>
                                        (option?.label ?? '').toLowerCase().includes(input.toLowerCase())
                                    }
                                />
                                <Button type="text" icon={<PlusOutlined />} className={styles.addBtn} />
                            </div>
                        </div>
                    </div>

                    {/* Right Column */}
                    <div className={styles.formColumn}>
                        {/* Cost Price */}
                        <div className={styles.formRow}>
                            <label className={styles.label}>Giá vốn</label>
                            <Input
                                value={formData.costPrice}
                                onChange={(e) => handleChange('costPrice', e.target.value)}
                                className={styles.input}
                            />
                        </div>

                        {/* Sale Price */}
                        <div className={styles.formRow}>
                            <label className={styles.label}>Giá bán</label>
                            <Input
                                value={formData.salePrice}
                                onChange={(e) => handleChange('salePrice', e.target.value)}
                                className={styles.input}
                            />
                        </div>

                        {/* Stock */}
                        <div className={styles.formRow}>
                            <label className={styles.label}>Tồn kho</label>
                            <Input
                                value={formData.stock}
                                onChange={(e) => handleChange('stock', e.target.value)}
                                className={styles.input}
                            />
                        </div>

                        {/* Unit */}
                        <div className={styles.formRow}>
                            <label className={styles.label}>Đơn vị cơ bản</label>
                            <Input
                                value={formData.unit}
                                onChange={(e) => handleChange('unit', e.target.value)}
                                className={styles.input}
                            />
                        </div>
                    </div>
                </div>

                {/* Image Upload Section */}
                <div className={styles.imageSection}>
                    {[1, 2, 3, 4, 5].map((idx) => (
                        <div key={idx} className={styles.imageUpload}>
                            <PictureOutlined className={styles.uploadIcon} />
                        </div>
                    ))}
                </div>
            </div>
        </Modal>
    );
};

export default AddProductModal;

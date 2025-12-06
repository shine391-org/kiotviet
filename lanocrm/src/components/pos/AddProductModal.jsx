import React, { useState, useEffect } from 'react';
import { Modal, Input, Select, Button, Upload, App } from 'antd';
import { PlusOutlined, PictureOutlined } from '@ant-design/icons';
import posApi from '../../api/posApi';
import styles from './AddProductModal.module.css';

// Product categories - có thể fetch từ API
const PRODUCT_CATEGORIES = [
    { value: 1, label: 'Túi xách' },
    { value: 2, label: 'Ví da' },
    { value: 3, label: 'Thắt lưng' },
    { value: 4, label: 'Phụ kiện' },
];

const AddProductModal = ({ open, onClose, onSave, initialName = '' }) => {
    const { message } = App.useApp();
    const [loading, setLoading] = useState(false);
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

    // Reset form when modal opens with new initialName
    useEffect(() => {
        if (open) {
            setFormData(prev => ({
                ...prev,
                productName: initialName,
                productCode: '',
            }));
        }
    }, [open, initialName]);

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
                                    options={PRODUCT_CATEGORIES}
                                    className={styles.select}
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

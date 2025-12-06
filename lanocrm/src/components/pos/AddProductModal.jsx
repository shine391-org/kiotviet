import React, { useState } from 'react';
import { Modal, Input, Select, Button, Upload } from 'antd';
import { PlusOutlined, PictureOutlined } from '@ant-design/icons';
import styles from './AddProductModal.module.css';

// Mock product categories
const PRODUCT_CATEGORIES = [
    { value: 'bags', label: 'Túi xách' },
    { value: 'wallets', label: 'Ví da' },
    { value: 'belts', label: 'Thắt lưng' },
    { value: 'accessories', label: 'Phụ kiện' },
];

const AddProductModal = ({ open, onClose, onSave, initialName = '' }) => {
    const [formData, setFormData] = useState({
        productCode: '',
        productName: initialName,
        category: null,
        costPrice: '',
        salePrice: '',
        stock: '',
        unit: '',
        images: [],
    });

    // Reset form when modal opens with new initialName
    React.useEffect(() => {
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

    const handleSave = () => {
        onSave?.(formData);
        onClose?.();
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
                    <Button type="primary" onClick={handleSave}>Lưu</Button>
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

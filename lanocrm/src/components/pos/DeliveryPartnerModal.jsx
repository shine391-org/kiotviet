import React, { useState, useEffect } from 'react';
import { Modal, Radio, Input, Select, Button } from 'antd';
import styles from './DeliveryPartnerModal.module.css';

// Mock partner groups
const PARTNER_GROUPS = [
    { value: 'group1', label: 'Nhóm đối tác 1' },
    { value: 'group2', label: 'Nhóm đối tác 2' },
    { value: 'internal', label: 'Nhân viên nội bộ' },
];

// Generate next partner code
const generatePartnerCode = () => {
    const num = Math.floor(Math.random() * 100000);
    return `DT${String(num).padStart(6, '0')}`;
};

const DeliveryPartnerModal = ({ open, onClose, onSave, mode = 'add', partnerData = null }) => {
    const [formData, setFormData] = useState({
        partnerType: 'individual',
        partnerCode: '',
        name: '',
        phone: '',
        email: '',
        addressDetail: '',
        province: '',
        district: '',
        ward: '',
        partnerGroup: null,
        note: '',
    });

    useEffect(() => {
        if (mode === 'add') {
            // Reset form for new partner
            setFormData({
                partnerType: 'individual',
                partnerCode: generatePartnerCode(),
                name: '',
                phone: '',
                email: '',
                addressDetail: '',
                province: '',
                district: '',
                ward: '',
                partnerGroup: null,
                note: '',
            });
        } else if (mode === 'edit' && partnerData) {
            // Fill form with partner data
            setFormData({
                partnerType: partnerData.partnerType || 'individual',
                partnerCode: partnerData.partnerCode || '',
                name: partnerData.name || '',
                phone: partnerData.phone || '',
                email: partnerData.email || '',
                addressDetail: partnerData.addressDetail || '',
                province: partnerData.province || '',
                district: partnerData.district || '',
                ward: partnerData.ward || '',
                partnerGroup: partnerData.partnerGroup || null,
                note: partnerData.note || '',
            });
        }
    }, [mode, partnerData, open]);

    const handleChange = (field, value) => {
        setFormData(prev => ({ ...prev, [field]: value }));
    };

    const handleSave = () => {
        onSave?.(formData);
        onClose?.();
    };

    const title = mode === 'add' ? 'Thêm đối tác giao hàng' : 'Sửa thông tin đối tác';

    return (
        <Modal
            title={title}
            open={open}
            onCancel={onClose}
            width={600}
            footer={
                <div className={styles.modalFooter}>
                    <Button onClick={onClose}>Bỏ qua</Button>
                    <Button type="primary" onClick={handleSave}>Lưu</Button>
                </div>
            }
            className={styles.modal}
        >
            <div className={styles.form}>
                {/* Partner Type */}
                <div className={styles.formRow}>
                    <label className={styles.label}>Loại đối tác</label>
                    <Radio.Group
                        value={formData.partnerType}
                        onChange={(e) => handleChange('partnerType', e.target.value)}
                    >
                        <Radio value="individual">Cá nhân</Radio>
                        <Radio value="company">Công ty</Radio>
                    </Radio.Group>
                </div>

                {/* Partner Code */}
                <div className={styles.formRow}>
                    <label className={styles.label}>Mã đối tác</label>
                    <Input
                        value={formData.partnerCode}
                        placeholder={mode === 'add' ? 'Mã mặc định' : ''}
                        onChange={(e) => handleChange('partnerCode', e.target.value)}
                        className={styles.input}
                        disabled={mode === 'edit'}
                    />
                </div>

                {/* Partner Name */}
                <div className={styles.formRow}>
                    <label className={styles.label}>
                        Tên đối tác<span className={styles.required}>*</span>
                    </label>
                    <Input
                        value={formData.name}
                        onChange={(e) => handleChange('name', e.target.value)}
                        className={styles.input}
                    />
                </div>

                {/* Phone */}
                <div className={styles.formRow}>
                    <label className={styles.label}>Điện thoại</label>
                    <Input
                        value={formData.phone}
                        onChange={(e) => handleChange('phone', e.target.value)}
                        className={styles.input}
                    />
                </div>

                {/* Email */}
                <div className={styles.formRow}>
                    <label className={styles.label}>Email</label>
                    <Input
                        value={formData.email}
                        onChange={(e) => handleChange('email', e.target.value)}
                        className={styles.input}
                    />
                </div>

                {/* Address Detail */}
                <div className={styles.formRow}>
                    <label className={styles.label}>Địa chỉ chi tiết</label>
                    <Input
                        value={formData.addressDetail}
                        placeholder="Số nhà, tòa nhà, ngõ, đường"
                        onChange={(e) => handleChange('addressDetail', e.target.value)}
                        className={styles.input}
                    />
                </div>

                {/* Province/District */}
                <div className={styles.formRow}>
                    <label className={styles.label}>Tỉnh/TP - Quận/Huyện</label>
                    <Input
                        value={formData.province}
                        onChange={(e) => handleChange('province', e.target.value)}
                        className={styles.input}
                    />
                </div>

                {/* Ward */}
                <div className={styles.formRow}>
                    <label className={styles.label}>Phường/Xã</label>
                    <Input
                        value={formData.ward}
                        onChange={(e) => handleChange('ward', e.target.value)}
                        className={styles.input}
                    />
                </div>

                {/* Partner Group */}
                <div className={styles.formRow}>
                    <label className={styles.label}>Nhóm đối tác</label>
                    <Select
                        value={formData.partnerGroup}
                        placeholder="Chọn nhóm đối tác"
                        onChange={(val) => handleChange('partnerGroup', val)}
                        options={PARTNER_GROUPS}
                        className={styles.select}
                        allowClear
                    />
                </div>

                {/* Note */}
                <div className={styles.formRow}>
                    <label className={styles.label}>Ghi chú</label>
                    <Input.TextArea
                        value={formData.note}
                        onChange={(e) => handleChange('note', e.target.value)}
                        className={styles.input}
                        rows={2}
                    />
                </div>
            </div>
        </Modal>
    );
};

export default DeliveryPartnerModal;

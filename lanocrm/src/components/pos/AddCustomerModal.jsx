import React, { useState, useEffect, useCallback } from 'react';
import { Modal, Tabs, Input, Select, Radio, Button, Upload, DatePicker, Cascader, App, Spin } from 'antd';
import { UserOutlined, CameraOutlined } from '@ant-design/icons';
import posApi from '../../api/posApi';
import locationApi from '../../api/locationApi';
import customerApi from '../../api/customerApi';
import styles from './AddCustomerModal.module.css';

const BANKS = [
    { value: 'vcb', label: 'Vietcombank' },
    { value: 'tcb', label: 'Techcombank' },
    { value: 'mb', label: 'MB Bank' },
    { value: 'bidv', label: 'BIDV' },
];

const AddCustomerModal = ({
    open,
    onClose,
    onSave,
    branchName = 'Lano - HN',
}) => {
    const { message } = App.useApp();
    const [activeTab, setActiveTab] = useState('general');
    const [loading, setLoading] = useState(false);
    const [provinces, setProvinces] = useState([]);
    const [districts, setDistricts] = useState([]);
    const [wards, setWards] = useState([]);
    const [customerGroups, setCustomerGroups] = useState([]);
    const [locationsLoading, setLocationsLoading] = useState(false);
    const initialFormData = {
        // General Info
        customerCode: '',
        customerName: '',
        phone: '',
        address: '',
        province: '',
        district: '',
        ward: '',
        group: '',
        birthday: null,
        gender: null,
        email: '',
        facebook: '',
        note: '',
        avatar: null,
        // Invoice Info
        customerType: 'individual', // 'individual' | 'business'
        buyerName: '',
        taxCode: '',
        invoiceAddress: '',
        invoiceCity: '',
        invoiceWard: '',
        idNumber: '',
        passport: '',
        invoiceEmail: '',
        invoicePhone: '',
        bankName: '',
        bankAccount: '',
    };
    const [formData, setFormData] = useState(initialFormData);

    const resetForm = useCallback(() => {
        setFormData(initialFormData);
        setDistricts([]);
        setWards([]);
        setActiveTab('general');
    }, []);

    const handleChange = (field, value) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
    };

    // Fetch provinces on mount
    const fetchProvinces = useCallback(async () => {
        setLocationsLoading(true);
        try {
            const response = await locationApi.getProvinces();
            if (response.success && response.data) {
                setProvinces(response.data.map(p => ({
                    value: p.id,
                    label: p.name,
                })));
            }
        } catch (error) {
            console.error('Failed to fetch provinces:', error);
        } finally {
            setLocationsLoading(false);
        }
    }, []);

    // Fetch districts when province changes
    const fetchDistricts = useCallback(async (provinceId) => {
        if (!provinceId) {
            setDistricts([]);
            setWards([]);
            return;
        }
        try {
            const response = await locationApi.getDistricts(provinceId);
            if (response.success && response.data) {
                setDistricts(response.data.map(d => ({
                    value: d.id,
                    label: d.name,
                })));
            }
        } catch (error) {
            console.error('Failed to fetch districts:', error);
        }
    }, []);

    // Fetch wards when district changes
    const fetchWards = useCallback(async (districtId) => {
        if (!districtId) {
            setWards([]);
            return;
        }
        try {
            const response = await locationApi.getWards(districtId);
            if (response.success && response.data) {
                setWards(response.data.map(w => ({
                    value: w.id,
                    label: w.name,
                })));
            }
        } catch (error) {
            console.error('Failed to fetch wards:', error);
        }
    }, []);

    // Fetch customer groups
    const fetchCustomerGroups = useCallback(async () => {
        try {
            const response = await customerApi.getGroups({ limit: 100 });
            if (response.success && response.data) {
                setCustomerGroups(response.data.map(g => ({
                    value: g.id,
                    label: g.name,
                })));
            }
        } catch (error) {
            console.error('Failed to fetch customer groups:', error);
        }
    }, []);

    useEffect(() => {
        if (open) {
            resetForm();
            fetchProvinces();
            fetchCustomerGroups();
        }
    }, [open, fetchProvinces, fetchCustomerGroups, resetForm]);

    const handleProvinceChange = (provinceId) => {
        handleChange('province', provinceId);
        handleChange('district', null);
        handleChange('ward', null);
        setDistricts([]);
        setWards([]);
        fetchDistricts(provinceId);
    };

    const handleDistrictChange = (districtId) => {
        handleChange('district', districtId);
        handleChange('ward', null);
        setWards([]);
        fetchWards(districtId);
    };

    const handleSave = async () => {
        if (!formData.customerName?.trim()) {
            message.error('Vui lòng nhập tên khách hàng');
            return;
        }

        setLoading(true);
        try {
            const payload = {
                name: formData.customerName,
                phone: formData.phone || undefined,
                email: formData.email || undefined,
                address: formData.address || undefined,
                province: formData.province || undefined,
                district: formData.district || undefined,
                ward: formData.ward || undefined,
                gender: formData.gender?.toUpperCase() || undefined,
                birthday: formData.birthday?.format('YYYY-MM-DD') || undefined,
                customer_type: formData.customerType === 'business' ? 'BUSINESS' : 'INDIVIDUAL',
                tax_code: formData.taxCode || undefined,
                notes: formData.note || undefined,
                facebook: formData.facebook || undefined,
            };

            const response = await posApi.createCustomer(payload);
            if (response.success) {
                message.success('Thêm khách hàng thành công');
                onSave?.({
                    ...response.data,
                    customerName: response.data.name,
                });
                onClose();
            } else {
                message.error(response.message || 'Không thể thêm khách hàng');
            }
        } catch (error) {
            console.error('Create customer error:', error);
            message.error(error.response?.data?.message || 'Lỗi khi thêm khách hàng');
        } finally {
            setLoading(false);
        }
    };

    const handleSkip = () => {
        onClose();
    };

    const tabItems = [
        {
            key: 'general',
            label: 'Thông tin chung',
            children: (
                <div className={styles.tabContent}>
                    <div className={styles.formGrid}>
                        {/* Left Column - Avatar */}
                        <div className={styles.avatarSection}>
                            <div className={styles.avatar}>
                                {formData.avatar ? (
                                    <img src={formData.avatar} alt="Avatar" />
                                ) : (
                                    <UserOutlined className={styles.avatarIcon} />
                                )}
                            </div>
                            <Button type="link" className={styles.uploadBtn}>
                                Chọn ảnh
                            </Button>
                        </div>

                        {/* Middle Column */}
                        <div className={styles.formColumn}>
                            <div className={styles.formRow}>
                                <label>Mã khách hàng</label>
                                <Input
                                    placeholder="Mã mặc định"
                                    value={formData.customerCode}
                                    onChange={(e) => handleChange('customerCode', e.target.value)}
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Tên khách hàng</label>
                                <Input
                                    placeholder="Bắt buộc"
                                    value={formData.customerName}
                                    onChange={(e) => handleChange('customerName', e.target.value)}
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Điện thoại</label>
                                <Input
                                    value={formData.phone}
                                    onChange={(e) => handleChange('phone', e.target.value)}
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Địa chỉ</label>
                                <Input
                                    placeholder="Số nhà, tòa nhà, ngõ, đường"
                                    value={formData.address}
                                    onChange={(e) => handleChange('address', e.target.value)}
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Tỉnh/Thành phố</label>
                                <Select
                                    placeholder="Chọn Tỉnh/TP"
                                    value={formData.province || undefined}
                                    onChange={handleProvinceChange}
                                    className={styles.fullWidth}
                                    options={provinces}
                                    loading={locationsLoading}
                                    showSearch
                                    filterOption={(input, option) =>
                                        (option?.label ?? '').toLowerCase().includes(input.toLowerCase())
                                    }
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Quận/Huyện</label>
                                <Select
                                    placeholder="Chọn Quận/Huyện"
                                    value={formData.district || undefined}
                                    onChange={handleDistrictChange}
                                    className={styles.fullWidth}
                                    options={districts}
                                    disabled={!formData.province}
                                    showSearch
                                    filterOption={(input, option) =>
                                        (option?.label ?? '').toLowerCase().includes(input.toLowerCase())
                                    }
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Phường/Xã</label>
                                <Select
                                    placeholder="Chọn Phường/Xã"
                                    value={formData.ward || undefined}
                                    onChange={(val) => handleChange('ward', val)}
                                    className={styles.fullWidth}
                                    options={wards}
                                    disabled={!formData.district}
                                    showSearch
                                    filterOption={(input, option) =>
                                        (option?.label ?? '').toLowerCase().includes(input.toLowerCase())
                                    }
                                />
                            </div>
                        </div>

                        {/* Right Column */}
                        <div className={styles.formColumn}>
                            <div className={styles.formRow}>
                                <label>Nhóm</label>
                                <Select
                                    placeholder="Chọn nhóm"
                                    value={formData.group || undefined}
                                    onChange={(val) => handleChange('group', val)}
                                    className={styles.fullWidth}
                                    options={customerGroups}
                                    showSearch
                                    filterOption={(input, option) =>
                                        (option?.label ?? '').toLowerCase().includes(input.toLowerCase())
                                    }
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Ngày sinh</label>
                                <div className={styles.birthdayRow}>
                                    <DatePicker
                                        placeholder=""
                                        value={formData.birthday}
                                        onChange={(val) => handleChange('birthday', val)}
                                        className={styles.dateInput}
                                    />
                                    <Radio.Group
                                        value={formData.gender}
                                        onChange={(e) => handleChange('gender', e.target.value)}
                                    >
                                        <Radio value="male">Nam</Radio>
                                        <Radio value="female">Nữ</Radio>
                                    </Radio.Group>
                                </div>
                            </div>
                            <div className={styles.formRow}>
                                <label>Email</label>
                                <Input
                                    value={formData.email}
                                    onChange={(e) => handleChange('email', e.target.value)}
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Facebook</label>
                                <Input
                                    value={formData.facebook}
                                    onChange={(e) => handleChange('facebook', e.target.value)}
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Ghi chú</label>
                                <Input.TextArea
                                    value={formData.note}
                                    onChange={(e) => handleChange('note', e.target.value)}
                                    rows={2}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            ),
        },
        {
            key: 'invoice',
            label: 'Thông tin xuất hóa đơn',
            children: (
                <div className={styles.tabContent}>
                    <div className={styles.invoiceGrid}>
                        {/* Left Column */}
                        <div className={styles.formColumn}>
                            <div className={styles.formRow}>
                                <label>Loại khách hàng</label>
                                <Radio.Group
                                    value={formData.customerType}
                                    onChange={(e) => handleChange('customerType', e.target.value)}
                                >
                                    <Radio value="individual">Cá nhân</Radio>
                                    <Radio value="business">Tổ chức/ Hộ kinh doanh</Radio>
                                </Radio.Group>
                            </div>
                            <div className={styles.formRow}>
                                <label>Tên người mua</label>
                                <Input
                                    placeholder="Nhập tên người mua"
                                    value={formData.buyerName}
                                    onChange={(e) => handleChange('buyerName', e.target.value)}
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Mã số thuế</label>
                                <Input
                                    placeholder="Nhập mã số thuế"
                                    value={formData.taxCode}
                                    onChange={(e) => handleChange('taxCode', e.target.value)}
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Địa chỉ</label>
                                <Input
                                    value={formData.invoiceAddress}
                                    onChange={(e) => handleChange('invoiceAddress', e.target.value)}
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Tỉnh/Thành phố</label>
                                <Select
                                    placeholder="Chọn Tỉnh/Thành phố"
                                    value={formData.invoiceCity || undefined}
                                    onChange={(val) => handleChange('invoiceCity', val)}
                                    className={styles.fullWidth}
                                    options={[
                                        { value: 'hanoi', label: 'Hà Nội' },
                                        { value: 'hcm', label: 'TP. Hồ Chí Minh' },
                                    ]}
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Phường/Xã</label>
                                <Select
                                    placeholder="Chọn Phường/Xã"
                                    value={formData.invoiceWard || undefined}
                                    onChange={(val) => handleChange('invoiceWard', val)}
                                    className={styles.fullWidth}
                                    options={[
                                        { value: 'p1', label: 'Phường 1' },
                                        { value: 'p2', label: 'Phường 2' },
                                    ]}
                                />
                            </div>
                        </div>

                        {/* Right Column */}
                        <div className={styles.formColumn}>
                            <div className={styles.formRow}>
                                <label>Số CMND/CCCD</label>
                                <Input
                                    placeholder="Nhập CCCD/CMND"
                                    value={formData.idNumber}
                                    onChange={(e) => handleChange('idNumber', e.target.value)}
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Số hộ chiếu</label>
                                <Input
                                    placeholder="Nhập số hộ chiếu"
                                    value={formData.passport}
                                    onChange={(e) => handleChange('passport', e.target.value)}
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Email</label>
                                <Input
                                    placeholder="email@example.com"
                                    value={formData.invoiceEmail}
                                    onChange={(e) => handleChange('invoiceEmail', e.target.value)}
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Số điện thoại</label>
                                <Input
                                    placeholder="Nhập số điện thoại"
                                    value={formData.invoicePhone}
                                    onChange={(e) => handleChange('invoicePhone', e.target.value)}
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>Tên ngân hàng</label>
                                <Select
                                    placeholder="Chọn ngân hàng"
                                    value={formData.bankName || undefined}
                                    onChange={(val) => handleChange('bankName', val)}
                                    className={styles.fullWidth}
                                    options={BANKS}
                                />
                            </div>
                            <div className={styles.formRow}>
                                <label>STK ngân hàng</label>
                                <Input
                                    placeholder="Nhập số tài khoản"
                                    value={formData.bankAccount}
                                    onChange={(e) => handleChange('bankAccount', e.target.value)}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            ),
        },
    ];

    return (
        <Modal
            title={
                <div className={styles.modalTitle}>
                    <span>Thêm khách hàng</span>
                    <span className={styles.branchName}>Chi nhánh tạo: {branchName}</span>
                </div>
            }
            open={open}
            onCancel={onClose}
            footer={null}
            width={800}
            className={styles.modal}
        >
            <Tabs
                activeKey={activeTab}
                onChange={setActiveTab}
                items={tabItems}
                className={styles.tabs}
            />

            <div className={styles.actions}>
                <Button onClick={handleSkip} className={styles.skipBtn}>
                    Bỏ qua
                </Button>
                <Button type="primary" onClick={handleSave} loading={loading} className={styles.saveBtn}>
                    Lưu
                </Button>
            </div>
        </Modal>
    );
};

export default AddCustomerModal;

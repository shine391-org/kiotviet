import React, { useState, useRef, useEffect, useCallback } from 'react';
import { Input, Button, Select, Dropdown, Spin, Empty } from 'antd';
import {
    SearchOutlined,
    PlusOutlined,
    CaretDownOutlined,
    UserSwitchOutlined,
    FacebookOutlined,
    InstagramOutlined,
    ShoppingCartOutlined,
    ShopOutlined,
    CheckOutlined,
} from '@ant-design/icons';
import posApi from '../../api/posApi';
import AddCustomerModal from './AddCustomerModal';
import styles from './CustomerHeader.module.css';

// Mock data for sellers - có thể fetch từ API users/staff
const SELLERS = [
    { id: 1, name: 'Trung', phone: '01666100999' },
    { id: 2, name: 'Chị Phương Anh', phone: '' },
    { id: 3, name: 'nhung', phone: '' },
];

// Sales channels
const SALES_CHANNELS = [
    { key: 'direct', label: 'Bán trực tiếp', icon: <UserSwitchOutlined /> },
    { key: 'facebook', label: 'Facebook', icon: <FacebookOutlined style={{ color: '#1877f2' }} /> },
    { key: 'instagram', label: 'Instagram', icon: <InstagramOutlined style={{ color: '#e4405f' }} /> },
    { key: 'cod', label: 'COD', icon: <ShoppingCartOutlined /> },
    { key: 'other', label: 'Khác', icon: <ShopOutlined /> },
];

const CustomerHeader = ({
    customer,
    onCustomerChange,
    showDateTime = true,
}) => {
    const [showAddCustomerModal, setShowAddCustomerModal] = useState(false);
    const [selectedSeller, setSelectedSeller] = useState(SELLERS[0]);
    const [selectedChannel, setSelectedChannel] = useState(SALES_CHANNELS[0]);
    const [sellerSearch, setSellerSearch] = useState('');
    const [channelSearch, setChannelSearch] = useState('');
    const [customerSearch, setCustomerSearch] = useState('');
    const [customerResults, setCustomerResults] = useState([]);
    const [customerLoading, setCustomerLoading] = useState(false);
    const [showCustomerDropdown, setShowCustomerDropdown] = useState(false);
    const customerSearchRef = useRef(null);
    const searchTimeoutRef = useRef(null);

    // F4 shortcut for customer search
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'F4') {
                e.preventDefault();
                customerSearchRef.current?.focus();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

    // Search customers with debounce
    const searchCustomers = useCallback(async (keyword) => {
        if (!keyword || keyword.length < 2) {
            setCustomerResults([]);
            setShowCustomerDropdown(false);
            return;
        }
        setCustomerLoading(true);
        try {
            const response = await posApi.searchCustomers(keyword);
            if (response.success) {
                setCustomerResults(response.data || []);
                setShowCustomerDropdown(true);
            }
        } catch (error) {
            console.error('Customer search error:', error);
        } finally {
            setCustomerLoading(false);
        }
    }, []);

    const handleCustomerSearchChange = (e) => {
        const value = e.target.value;
        setCustomerSearch(value);
        
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }
        searchTimeoutRef.current = setTimeout(() => {
            searchCustomers(value);
        }, 300);
    };

    const handleSelectCustomer = (cust) => {
        onCustomerChange?.({
            id: cust.id,
            name: cust.name,
            phone: cust.phone,
            email: cust.email,
            address: cust.address,
        });
        setCustomerSearch(cust.name);
        setShowCustomerDropdown(false);
    };

    // Filter sellers
    const filteredSellers = SELLERS.filter(
        (s) => s.name.toLowerCase().includes(sellerSearch.toLowerCase()) ||
            s.phone.includes(sellerSearch)
    );

    // Filter channels
    const filteredChannels = SALES_CHANNELS.filter(
        (c) => c.label.toLowerCase().includes(channelSearch.toLowerCase())
    );

    const handleAddCustomer = (customerData) => {
        console.log('New customer:', customerData);
        onCustomerChange?.({ name: customerData.customerName });
        setShowAddCustomerModal(false);
    };

    return (
        <>
            {/* Header Row with Seller/Channel/DateTime */}
            <div className={styles.headerRow}>
                {/* Seller Dropdown */}
                <Dropdown
                    trigger={['click']}
                    dropdownRender={() => (
                        <div className={styles.dropdownContent}>
                            <Input
                                placeholder="Tìm nhân viên..."
                                value={sellerSearch}
                                onChange={(e) => setSellerSearch(e.target.value)}
                                className={styles.dropdownSearch}
                                prefix={<SearchOutlined />}
                            />
                            <div className={styles.dropdownList}>
                                {filteredSellers.map((seller) => (
                                    <div
                                        key={seller.id}
                                        className={`${styles.dropdownItem} ${selectedSeller.id === seller.id ? styles.dropdownItemActive : ''}`}
                                        onClick={() => {
                                            setSelectedSeller(seller);
                                            setSellerSearch('');
                                        }}
                                    >
                                        <span>{seller.name}</span>
                                        {seller.phone && <span className={styles.sellerPhone}>{seller.phone}</span>}
                                        {selectedSeller.id === seller.id && <CheckOutlined className={styles.checkmark} />}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                >
                    <Button type="text" className={styles.sellerBtn}>
                        {selectedSeller.name} <CaretDownOutlined />
                    </Button>
                </Dropdown>

                {/* Channel Dropdown */}
                <Dropdown
                    trigger={['click']}
                    dropdownRender={() => (
                        <div className={styles.dropdownContent}>
                            <Input
                                placeholder="Tìm kênh..."
                                value={channelSearch}
                                onChange={(e) => setChannelSearch(e.target.value)}
                                className={styles.dropdownSearch}
                                prefix={<SearchOutlined />}
                            />
                            <div className={styles.dropdownList}>
                                {filteredChannels.map((channel) => (
                                    <div
                                        key={channel.key}
                                        className={`${styles.dropdownItem} ${selectedChannel.key === channel.key ? styles.dropdownItemActive : ''}`}
                                        onClick={() => {
                                            setSelectedChannel(channel);
                                            setChannelSearch('');
                                        }}
                                    >
                                        <span className={styles.channelIcon}>{channel.icon}</span>
                                        <span>{channel.label}</span>
                                        {selectedChannel.key === channel.key && <CheckOutlined className={styles.checkmark} />}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                >
                    <Button type="text" className={styles.channelBtn}>
                        {selectedChannel.icon} <CaretDownOutlined />
                    </Button>
                </Dropdown>

                {showDateTime && (
                    <span className={styles.dateTime}>
                        {new Date().toLocaleDateString('vi-VN')} {new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })}
                    </span>
                )}
            </div>

            {/* Customer Search Row */}
            <div className={styles.searchRow}>
                <div className={styles.customerSearchWrapper}>
                    <Input
                        ref={customerSearchRef}
                        placeholder="Tìm khách hàng (F4)"
                        prefix={<SearchOutlined />}
                        suffix={
                            customerLoading ? <Spin size="small" /> :
                            <PlusOutlined
                                className={styles.addIcon}
                                onClick={() => setShowAddCustomerModal(true)}
                            />
                        }
                        className={styles.searchInput}
                        value={customerSearch}
                        onChange={handleCustomerSearchChange}
                        onFocus={() => customerResults.length > 0 && setShowCustomerDropdown(true)}
                        onBlur={() => setTimeout(() => setShowCustomerDropdown(false), 200)}
                    />
                    {showCustomerDropdown && (
                        <div className={styles.customerDropdown}>
                            {customerResults.length > 0 ? (
                                customerResults.map((cust) => (
                                    <div
                                        key={cust.id}
                                        className={styles.customerItem}
                                        onMouseDown={() => handleSelectCustomer(cust)}
                                    >
                                        <div className={styles.customerName}>{cust.name}</div>
                                        {cust.phone && <div className={styles.customerPhone}>{cust.phone}</div>}
                                    </div>
                                ))
                            ) : (
                                <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Không tìm thấy khách hàng" />
                            )}
                        </div>
                    )}
                </div>
                <Select
                    defaultValue="default"
                    className={styles.priceListSelect}
                    options={[{ value: 'default', label: 'Bảng giá chung' }]}
                />
            </div>

            {/* Add Customer Modal */}
            <AddCustomerModal
                open={showAddCustomerModal}
                onClose={() => setShowAddCustomerModal(false)}
                onSave={handleAddCustomer}
            />
        </>
    );
};

export default CustomerHeader;

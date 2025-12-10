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
import priceListApi from '../../api/priceListApi';
import AddCustomerModal from './AddCustomerModal';
import styles from './CustomerHeader.module.css';

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
    onPriceListChange,
}) => {
    const [showAddCustomerModal, setShowAddCustomerModal] = useState(false);
    const [sellers, setSellers] = useState([]);
    const [selectedSeller, setSelectedSeller] = useState(null);
    const [selectedChannel, setSelectedChannel] = useState(SALES_CHANNELS[0]);
    const [sellerSearch, setSellerSearch] = useState('');
    const [channelSearch, setChannelSearch] = useState('');
    const [customerSearch, setCustomerSearch] = useState('');
    const [customerResults, setCustomerResults] = useState([]);
    const [customerLoading, setCustomerLoading] = useState(false);
    const [showCustomerDropdown, setShowCustomerDropdown] = useState(false);
    const [priceLists, setPriceLists] = useState([]);
    const [selectedPriceList, setSelectedPriceList] = useState('default');
    const customerSearchRef = useRef(null);
    const searchTimeoutRef = useRef(null);

    // Fetch sellers from API
    useEffect(() => {
        const fetchSellers = async () => {
            try {
                const response = await posApi.getSellers();
                if (response.success && response.data) {
                    const sellerList = response.data.map(u => ({
                        id: u.id,
                        name: u.full_name || u.username,
                        phone: u.phone || '',
                    }));
                    setSellers(sellerList);
                    if (sellerList.length > 0) {
                        setSelectedSeller(sellerList[0]);
                    }
                }
            } catch (error) {
                console.error('Failed to fetch sellers:', error);
            }
        };
        fetchSellers();
    }, []);

    // Fetch price lists from API
    useEffect(() => {
        const fetchPriceLists = async () => {
            try {
                const response = await priceListApi.getPriceLists({ is_active: 1, limit: 100 });
                if (response.success && response.data) {
                    const lists = response.data.map(p => ({
                        value: p.id,
                        label: p.name,
                    }));
                    setPriceLists([{ value: 'default', label: 'Bảng giá chung' }, ...lists]);
                }
            } catch (error) {
                console.error('Failed to fetch price lists:', error);
            }
        };
        fetchPriceLists();
    }, []);

    const handlePriceListChange = (value) => {
        setSelectedPriceList(value);
        onPriceListChange?.(value === 'default' ? null : value);
    };

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

    // Cleanup search timeout on unmount
    useEffect(() => {
        return () => {
            if (searchTimeoutRef.current) {
                clearTimeout(searchTimeoutRef.current);
                searchTimeoutRef.current = null;
            }
        };
    }, []);

    // Search customers with debounce - also fetches recent customers when keyword is empty
    const searchCustomers = useCallback(async (keyword) => {
        setCustomerLoading(true);
        try {
            // Pass keyword or undefined to get all/recent customers
            const response = await posApi.searchCustomers(keyword || '');
            console.log('🔵 Customer search response:', response);

            // Handle both response.success and direct data response
            if (response.success && response.data) {
                setCustomerResults(response.data);
                setShowCustomerDropdown(true);
            } else if (Array.isArray(response.data)) {
                // Fallback: response might be {data: [...], pagination: {...}}
                setCustomerResults(response.data);
                setShowCustomerDropdown(true);
            } else if (Array.isArray(response)) {
                // Direct array response
                setCustomerResults(response);
                setShowCustomerDropdown(true);
            } else {
                console.warn('🔴 Unexpected customer search response format:', response);
                setCustomerResults([]);
            }
        } catch (error) {
            console.error('Customer search error:', error);
            setCustomerResults([]);
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
    const filteredSellers = sellers.filter(
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
                    popupRender={() => (
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
                                        className={`${styles.dropdownItem} ${selectedSeller?.id === seller.id ? styles.dropdownItemActive : ''}`}
                                        onClick={() => {
                                            setSelectedSeller(seller);
                                            setSellerSearch('');
                                        }}
                                    >
                                        <span>{seller.name}</span>
                                        {seller.phone && <span className={styles.sellerPhone}>{seller.phone}</span>}
                                        {selectedSeller?.id === seller.id && <CheckOutlined className={styles.checkmark} />}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                >
                    <Button type="text" className={styles.sellerBtn}>
                        {selectedSeller?.name || 'Chọn NV'} <CaretDownOutlined />
                    </Button>
                </Dropdown>

                {/* Channel Dropdown */}
                <Dropdown
                    trigger={['click']}
                    popupRender={() => (
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
                        onFocus={() => searchCustomers(customerSearch)}
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
                    value={selectedPriceList}
                    onChange={handlePriceListChange}
                    className={styles.priceListSelect}
                    options={priceLists.length > 0 ? priceLists : [{ value: 'default', label: 'Bảng giá chung' }]}
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

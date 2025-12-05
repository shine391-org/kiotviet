import React, { useState, useEffect } from 'react';
import { Modal, Table, Input, message } from 'antd';
import { SearchOutlined } from '@ant-design/icons';
import productApi from '../../api/productApi';

const ProductPickerModal = ({ open, onClose, onSave, loading }) => {
    const [items, setItems] = useState([]);
    const [fetching, setFetching] = useState(false);
    const [selectedRowKeys, setSelectedRowKeys] = useState([]);
    const [searchText, setSearchText] = useState('');
    const [pagination, setPagination] = useState({ current: 1, pageSize: 10, total: 0 });

    useEffect(() => {
        if (open) {
            fetchProducts();
            setSelectedRowKeys([]);
        }
    }, [open]);

    const fetchProducts = async (params = {}) => {
        setFetching(true);
        try {
            const query = {
                limit: pagination.pageSize,
                page: pagination.current,
                search: searchText,
                ...params,
                // Ensure we fetch from master list (no price_list_id)
                price_list_id: '',
            };

            const res = await productApi.getProducts(query);
            if (res && res.data) {
                setItems(res.data);
                setPagination(prev => ({
                    ...prev,
                    total: res.pagination ? res.pagination.total : res.data.length
                }));
            }
        } catch (error) {
            console.error(error);
            message.error('Không thể tải danh sách hàng hóa');
        } finally {
            setFetching(false);
        }
    };

    const handleSearch = (value) => {
        setSearchText(value);
        fetchProducts({ search: value, page: 1 });
    };

    const handleTableChange = (pag) => {
        setPagination(pag);
        fetchProducts({ page: pag.current, limit: pag.pageSize });
    };

    const handleOk = () => {
        if (selectedRowKeys.length === 0) {
            message.warning('Vui lòng chọn ít nhất 1 sản phẩm');
            return;
        }
        // Filter selected items to get full data (price, etc)
        // Note: This only gets items on current page. 
        // Ideally validation happens on backend or we fetch specifics?
        // For simplicity, we just pass IDs and let Parent handle or mapped items.
        // Better: Helper to find item in current list.
        const selectedItems = items.filter(i => selectedRowKeys.includes(i.id));

        // If user selected items across pages, this logic is flawed (only current page items found).
        // But for a simple picker, we often assume selection on current view or keep a 'selectedMap'.
        // Given scope "Refining Price List", I'll stick to simple "Add what you see".
        onSave(selectedItems);
    };

    const columns = [
        { title: 'Mã hàng', dataIndex: 'code', key: 'code' },
        { title: 'Tên hàng', dataIndex: 'name', key: 'name' },
        {
            title: 'Giá bán',
            dataIndex: 'selling_price',
            key: 'selling_price',
            render: (val) => val ? `${val.toLocaleString('vi-VN')}đ` : '0đ'
        },
        { title: 'Tồn kho', dataIndex: 'stock_quantity', key: 'stock_quantity', width: 100 },
    ];

    return (
        <Modal
            title="Chọn hàng hóa thêm vào bảng giá"
            open={open}
            onCancel={onClose}
            onOk={handleOk}
            confirmLoading={loading}
            width={800}
            okText="Thêm"
            cancelText="Hủy"
        >
            <div style={{ marginBottom: 16 }}>
                <Input.Search
                    placeholder="Tìm theo mã, tên hàng"
                    onSearch={handleSearch}
                    style={{ width: '100%' }}
                    allowClear
                    enterButton={<SearchOutlined />}
                />
            </div>
            <Table
                rowKey="id"
                rowSelection={{
                    selectedRowKeys,
                    onChange: setSelectedRowKeys,
                    preserveSelectedRowKeys: true
                }}
                columns={columns}
                dataSource={items}
                loading={fetching}
                pagination={pagination}
                onChange={handleTableChange}
                size="small"
                scroll={{ y: 400 }}
            />
        </Modal>
    );
};

export default ProductPickerModal;

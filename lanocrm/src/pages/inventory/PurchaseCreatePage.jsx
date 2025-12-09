// src/pages/inventory/PurchaseCreatePage.jsx
import React, { useState, useCallback, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useSelector, useDispatch } from 'react-redux';
import {
    Button,
    Input,
    Space,
    Typography,
    Select,
    Table,
    InputNumber,
    App,
    Empty,
    Popconfirm,
    DatePicker,
    Divider,
    Upload,
} from 'antd';
import {
    ArrowLeftOutlined,
    PlusOutlined,
    PrinterOutlined,
    EyeOutlined,
    SettingOutlined,
    DeleteOutlined,
    UploadOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import { getProducts } from '../../api/productApi';
import purchaseApi from '../../api/purchaseApi';
import { fetchBranches } from '../../store/slices/branchSlice';
import styles from './PurchaseCreatePage.module.css';

const { Title, Text, Link } = Typography;

const currency = (v) => (v === null || v === undefined ? '0' : Number(v).toLocaleString('vi-VN'));

/**
 * PurchaseCreatePage - Create new purchase order
 * @agent-layer: frontend-page
 * @agent-pattern: create-form with items table
 */
const PurchaseCreatePage = () => {
    const navigate = useNavigate();
    const dispatch = useDispatch();
    const { message } = App.useApp();

    const { branches } = useSelector((s) => s.branch);
    const users = useSelector((s) => s.user?.items || []);

    const [branchId, setBranchId] = useState(null);
    const [supplierId, setSupplierId] = useState(null);
    const [supplierSearch, setSupplierSearch] = useState('');
    const [orderDate, setOrderDate] = useState(dayjs());
    const [notes, setNotes] = useState('');
    const [items, setItems] = useState([]);
    const [discount, setDiscount] = useState(0);
    const [submitting, setSubmitting] = useState(false);

    const [searchText, setSearchText] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [searching, setSearching] = useState(false);
    const searchTimerRef = useRef(null);

    useEffect(() => {
        dispatch(fetchBranches());
    }, [dispatch]);

    // Search products
    const handleSearch = useCallback(async (value) => {
        setSearchText(value);
        if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
        if (!value || value.length < 2) {
            setSearchResults([]);
            return;
        }
        searchTimerRef.current = setTimeout(async () => {
            setSearching(true);
            try {
                const res = await getProducts({ search: value, limit: 10 });
                const products = res?.data || [];
                setSearchResults(
                    products.map((p) => ({
                        value: p.id,
                        label: `${p.code} - ${p.name}`,
                        product: p,
                    }))
                );
            } catch (err) {
                console.error('Search error:', err);
            } finally {
                setSearching(false);
            }
        }, 300);
    }, []);

    const handleProductSelect = (value, option) => {
        const p = option.product;
        if (!p) return;
        const exists = items.find((i) => i.product_id === p.id);
        if (exists) {
            message.warning('Sản phẩm đã có trong danh sách');
            return;
        }
        const newItem = {
            key: `${p.id}-${Date.now()}`,
            product_id: p.id,
            product_code: p.code,
            product_name: p.name,
            unit: p.unit || 'Cái',
            quantity: 1,
            unit_price: p.purchase_price || p.cost_price || 0,
            discount: 0,
            import_price: p.purchase_price || p.cost_price || 0,
        };
        newItem.amount = newItem.quantity * newItem.import_price;
        setItems((prev) => [...prev, newItem]);
        setSearchText('');
        setSearchResults([]);
    };

    const handleQuantityChange = (key, value) => {
        setItems((prev) =>
            prev.map((i) =>
                i.key === key
                    ? { ...i, quantity: value, amount: value * i.import_price }
                    : i
            )
        );
    };

    const handlePriceChange = (key, field, value) => {
        setItems((prev) =>
            prev.map((i) => {
                if (i.key !== key) return i;
                const updated = { ...i, [field]: value };
                updated.import_price = updated.unit_price - (updated.discount || 0);
                updated.amount = updated.quantity * updated.import_price;
                return updated;
            })
        );
    };

    const handleRemoveItem = (key) => {
        setItems((prev) => prev.filter((i) => i.key !== key));
    };

    const totalQty = items.reduce((sum, i) => sum + (i.quantity || 0), 0);
    const totalAmount = items.reduce((sum, i) => sum + (i.amount || 0), 0);
    const grandTotal = totalAmount - discount;
    const needToPay = grandTotal;

    const columns = [
        { title: 'STT', width: 50, render: (_, __, idx) => idx + 1 },
        { title: 'Mã hàng', dataIndex: 'product_code', width: 100 },
        { title: 'Tên hàng', dataIndex: 'product_name', ellipsis: true },
        { title: 'ĐVT', dataIndex: 'unit', width: 70 },
        {
            title: 'Số lượng',
            dataIndex: 'quantity',
            width: 90,
            render: (val, record) => (
                <InputNumber
                    min={1}
                    value={val}
                    onChange={(v) => handleQuantityChange(record.key, v)}
                    style={{ width: '100%' }}
                    size="small"
                />
            ),
        },
        {
            title: 'Đơn giá',
            dataIndex: 'unit_price',
            width: 110,
            render: (val, record) => (
                <InputNumber
                    min={0}
                    value={val}
                    onChange={(v) => handlePriceChange(record.key, 'unit_price', v)}
                    style={{ width: '100%' }}
                    size="small"
                    formatter={(v) => `${v}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                    parser={(v) => v.replace(/,/g, '')}
                />
            ),
        },
        {
            title: 'Giảm giá',
            dataIndex: 'discount',
            width: 100,
            render: (val, record) => (
                <InputNumber
                    min={0}
                    value={val}
                    onChange={(v) => handlePriceChange(record.key, 'discount', v)}
                    style={{ width: '100%' }}
                    size="small"
                    formatter={(v) => `${v}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                    parser={(v) => v.replace(/,/g, '')}
                />
            ),
        },
        {
            title: 'Thành tiền',
            dataIndex: 'amount',
            width: 120,
            align: 'right',
            render: (val) => currency(val),
        },
        {
            title: '',
            width: 50,
            render: (_, record) => (
                <Popconfirm
                    title="Xóa sản phẩm này?"
                    onConfirm={() => handleRemoveItem(record.key)}
                    okText="Xóa"
                    cancelText="Hủy"
                >
                    <Button type="text" danger icon={<DeleteOutlined />} size="small" />
                </Popconfirm>
            ),
        },
    ];

    const handleSaveDraft = async () => {
        if (items.length === 0) {
            message.warning('Vui lòng thêm ít nhất một sản phẩm');
            return;
        }
        setSubmitting(true);
        try {
            const payload = {
                branch_id: branchId,
                partner_id: supplierId,
                order_date: orderDate?.format('YYYY-MM-DD HH:mm:ss'),
                notes,
                discount,
                status: 'draft',
                items: items.map((i) => ({
                    product_id: i.product_id,
                    quantity: i.quantity,
                    rate: i.unit_price,
                    unit_price: i.unit_price,
                    amount: i.amount,
                })),
            };
            const res = await purchaseApi.createPurchase(payload);
            if (res.success) {
                message.success('Đã lưu tạm phiếu nhập hàng');
                navigate('/inventory/purchase');
            } else {
                message.error(res.message || 'Lỗi tạo phiếu');
            }
        } catch (err) {
            message.error(err.response?.data?.message || 'Lỗi tạo phiếu');
        } finally {
            setSubmitting(false);
        }
    };

    const handleSubmit = async () => {
        if (items.length === 0) {
            message.warning('Vui lòng thêm ít nhất một sản phẩm');
            return;
        }
        setSubmitting(true);
        try {
            const payload = {
                branch_id: branchId,
                partner_id: supplierId,
                order_date: orderDate?.format('YYYY-MM-DD HH:mm:ss'),
                notes,
                discount,
                status: 'completed',
                items: items.map((i) => ({
                    product_id: i.product_id,
                    quantity: i.quantity,
                    rate: i.unit_price,
                    unit_price: i.unit_price,
                    amount: i.amount,
                })),
            };
            const res = await purchaseApi.createPurchase(payload);
            if (res.success) {
                message.success('Đã tạo phiếu nhập hàng thành công');
                navigate('/inventory/purchase');
            } else {
                message.error(res.message || 'Lỗi tạo phiếu');
            }
        } catch (err) {
            message.error(err.response?.data?.message || 'Lỗi tạo phiếu');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className={styles.page}>
            {/* Top bar */}
            <div className={styles.topBar}>
                <Space>
                    <Button
                        type="text"
                        icon={<ArrowLeftOutlined />}
                        onClick={() => navigate('/inventory/purchase')}
                    />
                    <span className={styles.title}>Nhập hàng</span>
                </Space>

                <Select
                    showSearch
                    allowClear
                    placeholder="Tìm hàng hóa theo mã hoặc tên (F3)"
                    className={styles.search}
                    value={searchText || undefined}
                    onSearch={handleSearch}
                    onChange={handleProductSelect}
                    options={searchResults}
                    loading={searching}
                    filterOption={false}
                    notFoundContent={searching ? 'Đang tìm...' : null}
                    suffixIcon={<PlusOutlined />}
                />

                <Space>
                    <Button icon={<SettingOutlined />} />
                    <Button icon={<PrinterOutlined />} />
                    <Button icon={<EyeOutlined />} />
                </Space>
            </div>

            {/* Main layout */}
            <div className={styles.layout}>
                {/* Left - Products table */}
                <div className={styles.tableCard}>
                    {items.length > 0 ? (
                        <Table
                            dataSource={items}
                            columns={columns}
                            rowKey="key"
                            pagination={false}
                            size="small"
                            scroll={{ y: 450 }}
                        />
                    ) : (
                        <div className={styles.tableEmpty}>
                            <div className={styles.tableEmptyInner}>
                                <Empty description="Thêm sản phẩm từ file excel" />
                                <Text type="secondary">
                                    (Tải về file mẫu: <Link href="#">Excel file</Link>)
                                </Text>
                                <Upload showUploadList={false} accept=".xlsx,.xls">
                                    <Button
                                        type="primary"
                                        icon={<UploadOutlined />}
                                        className={styles.uploadBtn}
                                    >
                                        Chọn file dữ liệu
                                    </Button>
                                </Upload>
                            </div>
                        </div>
                    )}
                </div>

                {/* Right - Sidebar form */}
                <div className={styles.sidebar}>
                    <div className={styles.sideSection}>
                        <Space style={{ justifyContent: 'space-between', width: '100%' }}>
                            <Select
                                placeholder="Trung"
                                style={{ width: 120 }}
                                value={branchId}
                                onChange={setBranchId}
                                options={branches.map((b) => ({ label: b.name, value: b.id }))}
                            />
                            <DatePicker
                                showTime
                                format="DD/MM/YYYY HH:mm"
                                value={orderDate}
                                onChange={setOrderDate}
                                style={{ width: 150 }}
                            />
                        </Space>

                        <Select
                            showSearch
                            allowClear
                            placeholder="Tìm nhà cung cấp"
                            style={{ width: '100%' }}
                            value={supplierId}
                            onSearch={setSupplierSearch}
                            onChange={setSupplierId}
                            filterOption={false}
                            suffixIcon={<PlusOutlined />}
                            notFoundContent={supplierSearch ? 'Không tìm thấy' : null}
                        />
                    </div>

                    <Divider style={{ margin: '12px 0' }} />

                    <div className={styles.sideSection}>
                        <div className={styles.sideRow}>
                            <span className={styles.sideLabel}>Mã phiếu nhập</span>
                            <span className={styles.sideValue}>Mã phiếu tự động</span>
                        </div>
                        <div className={styles.sideRow}>
                            <span className={styles.sideLabel}>Mã đặt hàng nhập</span>
                            <span className={styles.sideValue}>—</span>
                        </div>
                        <div className={styles.sideRow}>
                            <span className={styles.sideLabel}>Trạng thái</span>
                            <span className={styles.sideValue}>Phiếu tạm</span>
                        </div>
                    </div>

                    <Divider style={{ margin: '12px 0' }} />

                    <div className={styles.sideSection}>
                        <div className={styles.sideRow}>
                            <span className={styles.sideLabel}>Tổng tiền hàng ({totalQty})</span>
                            <span className={styles.sideValue}>{currency(totalAmount)}</span>
                        </div>
                        <div className={styles.sideRow}>
                            <span className={styles.sideLabel}>Giảm giá</span>
                            <InputNumber
                                min={0}
                                value={discount}
                                onChange={setDiscount}
                                formatter={(v) => `${v}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                                parser={(v) => v.replace(/,/g, '')}
                                size="small"
                                style={{ width: 100 }}
                            />
                        </div>
                        <div className={styles.sideRow}>
                            <span className={styles.sideLabel}>Cần trả nhà cung cấp</span>
                            <span className={`${styles.sideValue} ${styles.totalAmount}`}>
                                {currency(needToPay)}
                            </span>
                        </div>
                    </div>

                    <Divider style={{ margin: '12px 0' }} />

                    <Input.TextArea
                        placeholder="Ghi chú..."
                        rows={3}
                        value={notes}
                        onChange={(e) => setNotes(e.target.value)}
                    />

                    {/* Actions */}
                    <div className={styles.actionRow}>
                        <Button
                            type="primary"
                            className={styles.draftBtn}
                            onClick={handleSaveDraft}
                            loading={submitting}
                            size="large"
                        >
                            Lưu tạm
                        </Button>
                        <Button
                            type="primary"
                            className={styles.submitBtn}
                            onClick={handleSubmit}
                            loading={submitting}
                            size="large"
                        >
                            Hoàn thành
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default PurchaseCreatePage;

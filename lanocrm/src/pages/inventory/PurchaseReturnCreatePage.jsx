// src/pages/inventory/PurchaseReturnCreatePage.jsx
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
    SaveOutlined,
    CheckOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import { getProducts } from '../../api/productApi';
import purchaseReturnApi from '../../api/purchaseReturnApi';
import supplierApi from '../../api/supplierApi';
import { fetchBranches } from '../../store/slices/branchSlice';
import styles from './PurchaseCreatePage.module.css';

const { Text, Link } = Typography;

const currency = (v) => (v === null || v === undefined ? '0' : Number(v).toLocaleString('vi-VN'));

/**
 * PurchaseReturnCreatePage - Create new purchase return (Trả hàng nhập)
 * @agent-layer: frontend-page
 * @agent-pattern: create-form with items table
 */
const PurchaseReturnCreatePage = () => {
    const navigate = useNavigate();
    const dispatch = useDispatch();
    const { message } = App.useApp();

    const { branches } = useSelector((s) => s.branch);
    const users = useSelector((s) => s.user?.items || []);

    const [branchId, setBranchId] = useState(null);
    const [supplierId, setSupplierId] = useState(null);
    const [returnDate, setReturnDate] = useState(dayjs());
    const [notes, setNotes] = useState('');
    const [items, setItems] = useState([]);
    const [discount, setDiscount] = useState(0);
    const [supplierPaid, setSupplierPaid] = useState(0);
    const [submitting, setSubmitting] = useState(false);

    const [searchText, setSearchText] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [searching, setSearching] = useState(false);
    const searchTimerRef = useRef(null);

    // Supplier search state
    const [suppliers, setSuppliers] = useState([]);
    const [supplierOptions, setSupplierOptions] = useState([]);
    const [loadingSuppliers, setLoadingSuppliers] = useState(false);
    const supplierTimerRef = useRef(null);

    useEffect(() => {
        dispatch(fetchBranches());
        // Load initial suppliers
        loadSuppliers('');

        // Cleanup function to clear timers on unmount
        return () => {
            if (supplierTimerRef.current) {
                clearTimeout(supplierTimerRef.current);
                supplierTimerRef.current = null;
            }
            if (searchTimerRef.current) {
                clearTimeout(searchTimerRef.current);
                searchTimerRef.current = null;
            }
        };
    }, [dispatch]);

    // Load suppliers from API
    const loadSuppliers = async (search) => {
        setLoadingSuppliers(true);
        try {
            const res = await supplierApi.getSuppliers({ search, limit: 20 });
            const list = res.data || [];
            setSuppliers(list);
            setSupplierOptions(list.map((s) => ({
                value: s.id,
                label: `${s.code} - ${s.name}`,
                supplier: s,
            })));
        } catch (err) {
            console.error('Load suppliers error:', err);
        } finally {
            setLoadingSuppliers(false);
        }
    };

    // Handle supplier search with debounce
    const handleSupplierSearch = (value) => {
        if (supplierTimerRef.current) clearTimeout(supplierTimerRef.current);
        supplierTimerRef.current = setTimeout(() => {
            loadSuppliers(value);
        }, 300);
    };

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
            import_price: p.purchase_price || p.cost_price || 0,
            return_price: p.purchase_price || p.cost_price || 0,
        };
        newItem.amount = newItem.quantity * newItem.return_price;
        setItems((prev) => [...prev, newItem]);
        setSearchText('');
        setSearchResults([]);
    };

    const handleQuantityChange = (key, value) => {
        setItems((prev) =>
            prev.map((i) =>
                i.key === key
                    ? { ...i, quantity: value, amount: value * i.return_price }
                    : i
            )
        );
    };

    const handlePriceChange = (key, field, value) => {
        setItems((prev) =>
            prev.map((i) => {
                if (i.key !== key) return i;
                const updated = { ...i, [field]: value };
                updated.amount = updated.quantity * updated.return_price;
                return updated;
            })
        );
    };

    const handleRemoveItem = (key) => {
        setItems((prev) => prev.filter((i) => i.key !== key));
    };

    const totalQty = items.reduce((sum, i) => sum + (i.quantity || 0), 0);
    const totalAmount = items.reduce((sum, i) => sum + (i.amount || 0), 0);
    const nccCanTra = totalAmount - discount;
    const tinhVaoCongNo = nccCanTra - supplierPaid;

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
            title: 'Giá nhập',
            dataIndex: 'import_price',
            width: 110,
            align: 'right',
            render: (val) => currency(val),
        },
        {
            title: 'Giá trả lại',
            dataIndex: 'return_price',
            width: 110,
            render: (val, record) => (
                <InputNumber
                    min={0}
                    value={val}
                    onChange={(v) => handlePriceChange(record.key, 'return_price', v)}
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

    /**
     * Shared helper for saving purchase return (draft or final)
     */
    const savePurchaseReturn = async (status, successMessage) => {
        // Validate required fields
        if (!branchId) {
            message.warning('Vui lòng chọn chi nhánh');
            return;
        }
        if (!supplierId) {
            message.warning('Vui lòng chọn nhà cung cấp');
            return;
        }
        if (items.length === 0) {
            message.warning('Vui lòng thêm ít nhất một sản phẩm');
            return;
        }

        setSubmitting(true);
        try {
            const payload = {
                branch_id: branchId,
                partner_id: supplierId,
                return_date: returnDate?.format('YYYY-MM-DD HH:mm:ss'),
                notes,
                discount,
                ncc_da_tra: supplierPaid,
                status,
                items: items.map((i) => ({
                    product_id: i.product_id,
                    product_code: i.product_code,
                    product_name: i.product_name,
                    quantity: i.quantity,
                    import_price: i.import_price,
                    return_price: i.return_price,
                    amount: i.amount,
                })),
            };
            const res = await purchaseReturnApi.createPurchaseReturn(payload);
            if (res.data) {
                message.success(successMessage);
                navigate('/inventory/purchase-returns');
            } else {
                message.error(res.message || 'Lỗi tạo phiếu');
            }
        } catch (err) {
            message.error(err.response?.data?.message || 'Lỗi tạo phiếu');
        } finally {
            setSubmitting(false);
        }
    };

    const handleSaveDraft = () => savePurchaseReturn('draft', 'Đã lưu tạm phiếu trả hàng nhập');

    const handleSubmit = () => savePurchaseReturn('returned', 'Đã tạo phiếu trả hàng nhập thành công');

    return (
        <div className={styles.page}>
            {/* Top bar */}
            <div className={styles.topBar}>
                <Space>
                    <Button
                        type="text"
                        icon={<ArrowLeftOutlined />}
                        onClick={() => navigate('/inventory/purchase-returns')}
                    />
                    <span className={styles.title}>Trả hàng nhập</span>
                </Space>

                <Select
                    showSearch
                    allowClear
                    placeholder="Tìm hàng hóa theo mã hoặc tên (F3)"
                    className={styles.search}
                    value={searchText || undefined}
                    onSearch={handleSearch}
                    onSelect={(value, option) => handleProductSelect(value, option)}
                    options={searchResults}
                    loading={searching}
                    filterOption={false}
                    notFoundContent={searching ? 'Đang tìm...' : (searchText ? 'Không tìm thấy' : null)}
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
                                value={returnDate}
                                onChange={setReturnDate}
                                style={{ width: 150 }}
                            />
                        </Space>

                        <Select
                            showSearch
                            allowClear
                            placeholder="Tìm nhà cung cấp"
                            style={{ width: '100%', marginTop: 12 }}
                            value={supplierId}
                            onSearch={handleSupplierSearch}
                            onChange={setSupplierId}
                            options={supplierOptions}
                            loading={loadingSuppliers}
                            filterOption={false}
                            suffixIcon={<PlusOutlined />}
                            notFoundContent={loadingSuppliers ? 'Đang tải...' : 'Không tìm thấy'}
                        />
                    </div>

                    <Divider style={{ margin: '12px 0' }} />

                    <div className={styles.sideSection}>
                        <div className={styles.sideRow}>
                            <span className={styles.sideLabel}>Mã trả hàng nhập</span>
                            <span className={styles.sideValue}>Mã phiếu tự động</span>
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
                    </div>

                    <Divider style={{ margin: '12px 0' }} />

                    <div className={styles.sideSection}>
                        <div className={styles.sideRow}>
                            <span className={styles.sideLabel}>Nhà cung cấp cần trả</span>
                            <span className={`${styles.sideValue} ${styles.totalAmount}`}>
                                {currency(nccCanTra)}
                            </span>
                        </div>
                        <div className={styles.sideRow}>
                            <span className={styles.sideLabel}>Tiền nhà cung cấp trả (F8)</span>
                            <InputNumber
                                min={0}
                                value={supplierPaid}
                                onChange={setSupplierPaid}
                                formatter={(v) => `${v}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                                parser={(v) => v.replace(/,/g, '')}
                                size="small"
                                style={{ width: 100 }}
                            />
                        </div>
                        <div className={styles.sideRow}>
                            <span className={styles.sideLabel} style={{ paddingLeft: 12 }}>Tiền mặt</span>
                            <span className={styles.sideValue}></span>
                        </div>
                        <div className={styles.sideRow}>
                            <span className={styles.sideLabel}>Tính vào công nợ</span>
                            <span className={styles.sideValue}>{currency(tinhVaoCongNo)}</span>
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
                            icon={<SaveOutlined />}
                        >
                            Lưu tạm
                        </Button>
                        <Button
                            type="primary"
                            className={styles.submitBtn}
                            onClick={handleSubmit}
                            loading={submitting}
                            size="large"
                            icon={<CheckOutlined />}
                        >
                            Hoàn thành
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default PurchaseReturnCreatePage;

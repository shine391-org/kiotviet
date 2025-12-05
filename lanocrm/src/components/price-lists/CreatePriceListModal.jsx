import React, { useState, useEffect } from 'react';
import { Modal, Tabs, Form, Input, DatePicker, Radio, Select, InputNumber, Button, Checkbox, Space, Tooltip } from 'antd';
import { InfoCircleOutlined, CalendarOutlined, ClockCircleOutlined, PlusOutlined, MinusOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import styles from './CreatePriceListModal.module.css';

const { TabPane } = Tabs;

const CreatePriceListModal = ({ open, onClose, onSave, loading, basePriceLists = [] }) => {
    const [form] = Form.useForm();
    const [activeTab, setActiveTab] = useState('info');
    const [operator, setOperator] = useState('+');
    const [unit, setUnit] = useState('VND');
    const [roundingRule, setRoundingRule] = useState('thousand');
    const [allowAddItems, setAllowAddItems] = useState(true);

    // Scope selection states
    const [branchScope, setBranchScope] = useState('all');
    const [customerGroupScope, setCustomerGroupScope] = useState('all');
    const [creatorScope, setCreatorScope] = useState('all');

    // Options from API
    const [branchOptions, setBranchOptions] = useState([]);
    const [employeeOptions, setEmployeeOptions] = useState([]);
    // Options from API (fetched when modal opens)
    const [customerGroupOptions, setCustomerGroupOptions] = useState([]);

    // Fetch branches and employees when modal opens
    useEffect(() => {
        if (open) {
            // Fetch branches
            fetch('/api/branches', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'application/json'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.data) {
                        setBranchOptions(data.data.map(b => ({ value: b.id, label: b.name })));
                    }
                })
                .catch(err => console.error('Error fetching branches:', err));

            // Fetch employees
            fetch('/api/employees', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'application/json'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.data) {
                        setEmployeeOptions(data.data.map(e => ({ value: e.id, label: e.full_name || e.username })));
                    }
                })
                .catch(err => console.error('Error fetching employees:', err));

            // Fetch customer groups
            fetch('/api/customer-groups', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'application/json'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.data) {
                        setCustomerGroupOptions(data.data.map(g => ({ value: g.id, label: g.name })));
                    }
                })
                .catch(err => console.error('Error fetching customer groups:', err));
        }
    }, [open]);

    useEffect(() => {
        if (open) {
            // Reset form with defaults when modal opens
            const now = dayjs();
            form.setFieldsValue({
                name: '',
                startDate: now,
                endDate: now.add(1, 'year'),
                status: 'active',
                basePriceListId: undefined,
                formulaValue: 0,
                allowAddItemsNotInList: true,
                warnWhenAddItemsNotInList: false,
                branchScope: 'all',
                specificBranches: [],
                customerGroupScope: 'all',
                specificCustomerGroups: [],
                transactionCreatorScope: 'all',
                specificCreators: [],
            });
            setOperator('+');
            setUnit('VND');
            setRoundingRule('thousand');
            setAllowAddItems(true);
            setBranchScope('all');
            setCustomerGroupScope('all');
            setCreatorScope('all');
            setActiveTab('info');
        }
    }, [open, form]);

    const handleFinish = (values) => {
        console.log('Form values:', values);

        // Handle basePriceListId - it can be 'cost', 'purchase', or a numeric id
        let basePriceListId = null;
        let formulaBase = null;
        if (values.basePriceListId) {
            if (values.basePriceListId === 'cost') {
                formulaBase = 'cost_price';
            } else if (values.basePriceListId === 'purchase') {
                formulaBase = 'purchase_price';
            } else if (typeof values.basePriceListId === 'number') {
                basePriceListId = values.basePriceListId;
                formulaBase = 'base';
            }
        }

        // Build formula string
        let formula = null;
        if (formulaBase && values.formulaValue) {
            formula = `${formulaBase} ${operator} ${values.formulaValue}${unit === '%' ? '%' : ''}`;
        }

        const payload = {
            name: values.name,
            start_date: values.startDate?.format('YYYY-MM-DD'),
            end_date: values.endDate?.format('YYYY-MM-DD'),
            is_active: values.status === 'active',
            base_price_list_id: basePriceListId,
            formula: formula,
            rounding_rule: roundingRule,
            config: {
                allow_add_items_not_in_list: allowAddItems,
                warn_when_add_items_not_in_list: values.warnWhenAddItemsNotInList,
                scope_branch: values.branchScope,
                specific_branches: values.branchScope === 'specific' ? values.specificBranches : [],
                scope_customer_group: values.customerGroupScope,
                specific_customer_groups: values.customerGroupScope === 'specific' ? values.specificCustomerGroups : [],
                scope_creator: values.transactionCreatorScope,
                specific_creators: values.transactionCreatorScope === 'specific' ? values.specificCreators : [],
            }
        };

        console.log('Payload to save:', payload);
        onSave(payload);
    };

    const handleCancel = () => {
        form.resetFields();
        onClose();
    };

    return (
        <Modal
            title="Tạo bảng giá"
            open={open}
            onCancel={handleCancel}
            width={700}
            footer={null}
            destroyOnClose
            className={styles.modal}
        >
            <Form
                form={form}
                layout="vertical"
                onFinish={handleFinish}
                onFinishFailed={(errorInfo) => {
                    console.log('Form validation failed:', errorInfo);
                }}
                initialValues={{
                    status: 'active',
                    allowAddItemsNotInList: true,
                    warnWhenAddItemsNotInList: false,
                    branchScope: 'all',
                    customerGroupScope: 'all',
                    transactionCreatorScope: 'all',
                }}
            >
                <Tabs activeKey={activeTab} onChange={setActiveTab}>
                    {/* TAB 1: Thông tin */}
                    <TabPane tab="Thông tin" key="info">
                        <div className={styles.tabContent}>
                            {/* Tên bảng giá */}
                            <Form.Item
                                label="Tên bảng giá"
                                name="name"
                                rules={[{ required: true, message: 'Vui lòng nhập tên bảng giá' }]}
                            >
                                <Input placeholder="Nhập tên bảng giá" />
                            </Form.Item>

                            {/* Section: Hiệu lực */}
                            <div className={styles.section}>
                                <div className={styles.sectionHeader}>
                                    <span className={styles.sectionTitle}>Hiệu lực</span>
                                </div>
                                <div className={styles.sectionContent}>
                                    <div className={styles.dateRow}>
                                        <span className={styles.dateLabel}>Hiệu lực</span>
                                        <Form.Item name="startDate" noStyle>
                                            <DatePicker
                                                showTime
                                                format="DD/MM/YYYY HH:mm"
                                                placeholder="Chọn ngày bắt đầu"
                                                suffixIcon={<CalendarOutlined />}
                                                className={styles.datePicker}
                                            />
                                        </Form.Item>
                                        <span className={styles.dateLabel}>đến</span>
                                        <Form.Item name="endDate" noStyle>
                                            <DatePicker
                                                showTime
                                                format="DD/MM/YYYY HH:mm"
                                                placeholder="Chọn ngày kết thúc"
                                                suffixIcon={<CalendarOutlined />}
                                                className={styles.datePicker}
                                            />
                                        </Form.Item>
                                    </div>
                                    <div className={styles.statusRow}>
                                        <span className={styles.dateLabel}>Trạng thái</span>
                                        <Form.Item name="status" noStyle>
                                            <Radio.Group>
                                                <Radio value="active">Áp dụng</Radio>
                                                <Radio value="inactive">Chưa áp dụng</Radio>
                                            </Radio.Group>
                                        </Form.Item>
                                    </div>
                                </div>
                            </div>

                            {/* Section: Công thức giá */}
                            <div className={styles.section}>
                                <div className={styles.sectionHeader}>
                                    <span className={styles.sectionTitle}>Công thức giá</span>
                                </div>
                                <div className={styles.sectionContent}>
                                    <div className={styles.formulaDesc}>
                                        Tạo công thức dựa trên giá vốn, giá nhập hoặc giá bán ở các bảng giá khác
                                    </div>
                                    <div className={styles.formulaRow}>
                                        <span className={styles.formulaLabel}>Giá mới =</span>
                                        <Form.Item name="basePriceListId" noStyle>
                                            <Select
                                                placeholder="Chọn bảng giá"
                                                allowClear
                                                style={{ width: 180 }}
                                                options={[
                                                    { label: 'Giá vốn', value: 'cost' },
                                                    { label: 'Giá nhập', value: 'purchase' },
                                                    ...basePriceLists.map(pl => ({ label: pl.name, value: pl.id }))
                                                ]}
                                            />
                                        </Form.Item>
                                        <div className={styles.operatorButtons}>
                                            <Button
                                                type={operator === '+' ? 'primary' : 'default'}
                                                icon={<PlusOutlined />}
                                                onClick={() => setOperator('+')}
                                                className={styles.operatorBtn}
                                            />
                                            <Button
                                                type={operator === '-' ? 'primary' : 'default'}
                                                icon={<MinusOutlined />}
                                                onClick={() => setOperator('-')}
                                                className={styles.operatorBtn}
                                            />
                                        </div>
                                        <Form.Item name="formulaValue" noStyle>
                                            <InputNumber
                                                min={0}
                                                placeholder="0"
                                                style={{ width: 100 }}
                                                className={styles.formulaValue}
                                            />
                                        </Form.Item>
                                        <div className={styles.unitButtons}>
                                            <Button
                                                type={unit === 'VND' ? 'primary' : 'default'}
                                                onClick={() => setUnit('VND')}
                                                className={styles.unitBtn}
                                            >
                                                VND
                                            </Button>
                                            <Button
                                                type={unit === '%' ? 'primary' : 'default'}
                                                onClick={() => setUnit('%')}
                                                className={styles.unitBtn}
                                            >
                                                %
                                            </Button>
                                        </div>
                                    </div>
                                    <div className={styles.formulaRow} style={{ marginTop: 12 }}>
                                        <span className={styles.formulaLabel}>Làm tròn đến</span>
                                        <Select
                                            style={{ width: 180 }}
                                            value={roundingRule}
                                            onChange={(value) => setRoundingRule(value)}
                                            options={[
                                                { label: 'Không làm tròn', value: 'none' },
                                                { label: 'Trăm đồng', value: 'hundred' },
                                                { label: 'Nghìn đồng', value: 'thousand' },
                                                { label: 'Chục nghìn đồng', value: 'ten_thousand' },
                                            ]}
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Section: Khi thu ngân lên đơn */}
                            <div className={styles.section}>
                                <div className={styles.sectionHeader}>
                                    <span className={styles.sectionTitle}>Khi thu ngân lên đơn với bảng giá này</span>
                                </div>
                                <div className={styles.sectionContent}>
                                    <Form.Item name="allowAddItemsNotInList" noStyle>
                                        <Radio.Group
                                            onChange={(e) => setAllowAddItems(e.target.value)}
                                            value={allowAddItems}
                                        >
                                            <Space direction="vertical">
                                                <Radio value={true}>
                                                    Được phép thêm hàng hóa không có trong bảng giá
                                                </Radio>
                                                {allowAddItems && (
                                                    <div className={styles.nestedCheckbox}>
                                                        <Form.Item name="warnWhenAddItemsNotInList" valuePropName="checked" noStyle>
                                                            <Checkbox>
                                                                Gửi cảnh báo khi thêm hàng hóa không có trong bảng giá
                                                            </Checkbox>
                                                        </Form.Item>
                                                    </div>
                                                )}
                                                <Radio value={false}>
                                                    Chỉ được thêm hàng hóa có trong bảng giá này
                                                    <Tooltip title="Nếu chọn, thu ngân chỉ có thể thêm sản phẩm được liệt kê trong bảng giá này">
                                                        <InfoCircleOutlined className={styles.infoIcon} />
                                                    </Tooltip>
                                                </Radio>
                                            </Space>
                                        </Radio.Group>
                                    </Form.Item>
                                </div>
                            </div>
                        </div>
                    </TabPane>

                    {/* TAB 2: Phạm vi áp dụng */}
                    <TabPane tab="Phạm vi áp dụng" key="scope">
                        <div className={styles.tabContent}>
                            {/* Section: Chi nhánh */}
                            <div className={styles.section}>
                                <div className={styles.sectionHeader}>
                                    <span className={styles.sectionTitle}>Chi nhánh</span>
                                </div>
                                <div className={styles.sectionContent}>
                                    <Form.Item name="branchScope" noStyle>
                                        <Radio.Group onChange={(e) => setBranchScope(e.target.value)}>
                                            <Space direction="vertical">
                                                <Radio value="all">Toàn hệ thống</Radio>
                                                <Radio value="specific">Chi nhánh cụ thể</Radio>
                                            </Space>
                                        </Radio.Group>
                                    </Form.Item>
                                    {branchScope === 'specific' && (
                                        <Form.Item name="specificBranches" style={{ marginTop: 12, marginLeft: 24 }}>
                                            <Select
                                                mode="multiple"
                                                placeholder="Chọn chi nhánh"
                                                options={branchOptions}
                                                style={{ width: '100%' }}
                                            />
                                        </Form.Item>
                                    )}
                                </div>
                            </div>

                            {/* Section: Nhóm khách hàng */}
                            <div className={styles.section}>
                                <div className={styles.sectionHeader}>
                                    <span className={styles.sectionTitle}>Nhóm khách hàng</span>
                                </div>
                                <div className={styles.sectionContent}>
                                    <Form.Item name="customerGroupScope" noStyle>
                                        <Radio.Group onChange={(e) => setCustomerGroupScope(e.target.value)}>
                                            <Space direction="vertical">
                                                <Radio value="all">Tất cả</Radio>
                                                <Radio value="specific">Nhóm khách hàng cụ thể</Radio>
                                            </Space>
                                        </Radio.Group>
                                    </Form.Item>
                                    {customerGroupScope === 'specific' && (
                                        <Form.Item name="specificCustomerGroups" style={{ marginTop: 12, marginLeft: 24 }}>
                                            <Select
                                                mode="multiple"
                                                placeholder="Chọn nhóm khách hàng"
                                                options={customerGroupOptions}
                                                style={{ width: '100%' }}
                                            />
                                        </Form.Item>
                                    )}
                                </div>
                            </div>

                            {/* Section: Người tạo giao dịch */}
                            <div className={styles.section}>
                                <div className={styles.sectionHeader}>
                                    <span className={styles.sectionTitle}>Người tạo giao dịch</span>
                                </div>
                                <div className={styles.sectionContent}>
                                    <Form.Item name="transactionCreatorScope" noStyle>
                                        <Radio.Group onChange={(e) => setCreatorScope(e.target.value)}>
                                            <Space direction="vertical">
                                                <Radio value="all">Tất cả</Radio>
                                                <Radio value="specific">Người tạo giao dịch cụ thể</Radio>
                                            </Space>
                                        </Radio.Group>
                                    </Form.Item>
                                    {creatorScope === 'specific' && (
                                        <Form.Item name="specificCreators" style={{ marginTop: 12, marginLeft: 24 }}>
                                            <Select
                                                mode="multiple"
                                                placeholder="Chọn người tạo giao dịch"
                                                options={employeeOptions}
                                                style={{ width: '100%' }}
                                            />
                                        </Form.Item>
                                    )}
                                </div>
                            </div>
                        </div>
                    </TabPane>
                </Tabs>

                {/* Footer */}
                <div className={styles.footer}>
                    <Button onClick={handleCancel}>Bỏ qua</Button>
                    <Button
                        type="primary"
                        loading={loading}
                        onClick={() => {
                            console.log('Lưu button clicked');
                            form.submit();
                        }}
                    >
                        Lưu
                    </Button>
                </div>
            </Form>
        </Modal>
    );
};

export default CreatePriceListModal;

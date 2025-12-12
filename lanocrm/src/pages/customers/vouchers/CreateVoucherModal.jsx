// src/pages/customers/vouchers/CreateVoucherModal.jsx

import React, { useState, useEffect, useCallback, useRef } from 'react';
import {
    Modal,
    Form,
    Input,
    InputNumber,
    DatePicker,
    Radio,
    Select,
    Checkbox,
    Tabs,
    Divider,
    Button,
    Space,
    Collapse,
    App,
} from 'antd';
import { InfoCircleOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import couponApi from '../../../api/couponApi';
import branchApi from '../../../api/branchApi';
import customerApi from '../../../api/customerApi';
import userApi from '../../../api/userApi';

const { TextArea } = Input;
const { Panel } = Collapse;

const CreateVoucherModal = ({ open, voucher, onCancel, onSuccess }) => {
    const [form] = Form.useForm();
    const { message } = App.useApp();
    const [activeTab, setActiveTab] = useState('info');
    const [loading, setLoading] = useState(false);

    // Dropdown data from API
    const [branches, setBranches] = useState([]);
    const [customerGroups, setCustomerGroups] = useState([]);
    const [users, setUsers] = useState([]);
    const [usersLoading, setUsersLoading] = useState(false);
    const userSearchDebounceRef = useRef(null);

    // Validity type state
    const [validityType, setValidityType] = useState('date_range');

    // Scope states
    const [branchScope, setBranchScope] = useState('all');
    const [customerGroupScope, setCustomerGroupScope] = useState('all');
    const [creatorScope, setCreatorScope] = useState('all');

    const isEditing = !!voucher;

    // Fetch users with server-side search
    const fetchUsers = useCallback(async (searchQuery = '') => {
        setUsersLoading(true);
        try {
            const userRes = await userApi.getUsers({
                search: searchQuery,
                limit: 50 // Fetch in pages of 50
            });
            const userData = userRes?.data || userRes || [];
            setUsers(Array.isArray(userData) ? userData : []);
        } catch (err) {
            console.error('Failed to load users:', err);
            setUsers([]);
        } finally {
            setUsersLoading(false);
        }
    }, []);

    // Handle user search with debounce
    const handleUserSearch = useCallback((searchValue) => {
        if (userSearchDebounceRef.current) {
            clearTimeout(userSearchDebounceRef.current);
        }
        userSearchDebounceRef.current = setTimeout(() => {
            fetchUsers(searchValue);
        }, 300);
    }, [fetchUsers]);

    // Cleanup debounce timeout on unmount
    useEffect(() => {
        return () => {
            if (userSearchDebounceRef.current) {
                clearTimeout(userSearchDebounceRef.current);
                userSearchDebounceRef.current = null;
            }
        };
    }, []);

    const loadDropdownData = useCallback(async () => {
        try {
            // Load branches
            const branchRes = await branchApi.getBranches();
            const branchData = branchRes?.data || branchRes || [];
            setBranches(Array.isArray(branchData) ? branchData : []);

            // Load customer groups
            const groupRes = await customerApi.getGroups();
            const groupData = groupRes?.data || groupRes || [];
            setCustomerGroups(Array.isArray(groupData) ? groupData : []);

            // Load initial users (first page)
            await fetchUsers('');
        } catch (err) {
            console.error('Failed to load dropdown data:', err);
        }
    }, [fetchUsers]);

    // Load dropdown data on modal open
    useEffect(() => {
        if (open) {
            loadDropdownData();
        }
    }, [open, loadDropdownData]);

    useEffect(() => {
        if (open) {
            if (voucher) {
                // Populate form for editing
                form.setFieldsValue({
                    name: voucher.name,
                    code: voucher.code,
                    discount_value: voucher.discount_value,
                    start_date: voucher.start_date ? dayjs(voucher.start_date) : null,
                    expiry_date: voucher.expiry_date ? dayjs(voucher.expiry_date) : null,
                    status: voucher.status || 'active',
                    min_amount: voucher.min_amount,
                    description: voucher.description,
                    is_combinable: voucher.is_combinable || false,
                    branch_id: voucher.branch_id,
                    customer_group_id: voucher.customer_group_id,
                    creator_id: voucher.creator_id,
                });
                setValidityType(voucher.validity_type || 'date_range');
                setBranchScope(voucher.branch_id ? 'specific' : 'all');
                setCustomerGroupScope(voucher.customer_group_id ? 'specific' : 'all');
                setCreatorScope(voucher.creator_id ? 'specific' : 'all');
            } else {
                // Reset form for creating
                form.resetFields();
                form.setFieldsValue({
                    status: 'active',
                    start_date: dayjs(),
                    expiry_date: dayjs().add(1, 'year'),
                });
                setValidityType('date_range');
                setBranchScope('all');
                setCustomerGroupScope('all');
                setCreatorScope('all');
            }
            setActiveTab('info');
        }
    }, [open, voucher, form]);

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            setLoading(true);

            const payload = {
                ...values,
                start_date: values.start_date?.format('YYYY-MM-DD'),
                expiry_date: values.expiry_date?.format('YYYY-MM-DD'),
                validity_type: validityType,
                branch_id: branchScope === 'all' ? null : values.branch_id,
                customer_group_id: customerGroupScope === 'all' ? null : values.customer_group_id,
                creator_id: creatorScope === 'all' ? null : values.creator_id,
            };

            if (isEditing) {
                await couponApi.update(voucher.id, payload);
            } else {
                await couponApi.create(payload);
            }

            onSuccess?.();
        } catch (err) {
            if (err.errorFields) {
                message.error('Vui lòng kiểm tra lại thông tin');
            } else {
                message.error(err.response?.data?.message || err.message || 'Không thể lưu voucher');
            }
        } finally {
            setLoading(false);
        }
    };

    const formatNumber = (value) => {
        if (!value) return '';
        return String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };

    const parseNumber = (value) => {
        if (!value) return '';
        return value.replace(/,/g, '');
    };

    // Convert dropdown data to options
    const branchOptions = branches.map((b) => ({ label: b.name, value: b.id }));
    const customerGroupOptions = customerGroups.map((g) => ({ label: g.name, value: g.id }));
    const userOptions = users.map((u) => ({ label: u.name || u.username || u.email, value: u.id }));

    const tabItems = [
        {
            key: 'info',
            label: 'Thông tin',
            children: (
                <div style={{ padding: '16px 0' }}>
                    {/* Basic Info Row */}
                    <div style={{ display: 'flex', gap: 16, marginBottom: 16 }}>
                        <Form.Item
                            name="name"
                            label="Tên đợt phát hành"
                            rules={[{ required: true, message: 'Nhập tên đợt phát hành' }]}
                            style={{ flex: 2 }}
                        >
                            <Input placeholder="Tên đợt phát hành voucher" />
                        </Form.Item>
                        <Form.Item
                            name="code"
                            label={
                                <span>
                                    Mã đợt phát hành{' '}
                                    <Tooltip title="Mã duy nhất định danh đợt phát hành voucher">
                                        <InfoCircleOutlined style={{ color: '#1890ff' }} />
                                    </Tooltip>
                                </span>
                            }
                            rules={[{ required: true, message: 'Nhập mã đợt phát hành' }]}
                            style={{ flex: 1 }}
                        >
                            <Input placeholder="Mã đợt" />
                        </Form.Item>
                        <Form.Item
                            name="discount_value"
                            label={
                                <span>
                                    Mệnh giá{' '}
                                    <Tooltip title="Giá trị giảm giá cho mỗi voucher">
                                        <InfoCircleOutlined style={{ color: '#1890ff' }} />
                                    </Tooltip>
                                </span>
                            }
                            rules={[{ required: true, message: 'Nhập mệnh giá' }]}
                            style={{ flex: 1 }}
                        >
                            <InputNumber
                                style={{ width: '100%' }}
                                placeholder="0"
                                min={0}
                                formatter={formatNumber}
                                parser={parseNumber}
                            />
                        </Form.Item>
                    </div>

                    {/* Validity Section */}
                    <Collapse
                        defaultActiveKey={['validity']}
                        ghost
                        style={{ background: '#fafbfc', borderRadius: 8, marginBottom: 16 }}
                    >
                        <Panel header={<span style={{ fontWeight: 600 }}>Hiệu lực</span>} key="validity">
                            <Radio.Group
                                value={validityType}
                                onChange={(e) => setValidityType(e.target.value)}
                                style={{ marginBottom: 12 }}
                            >
                                <Radio value="date_range">
                                    <Space>
                                        <span>Hiệu lực</span>
                                        <Form.Item name="start_date" noStyle>
                                            <DatePicker placeholder="Từ" format="DD/MM/YYYY" />
                                        </Form.Item>
                                        <span>đến</span>
                                        <Form.Item name="expiry_date" noStyle>
                                            <DatePicker placeholder="Đến" format="DD/MM/YYYY" />
                                        </Form.Item>
                                    </Space>
                                </Radio>
                            </Radio.Group>
                            <br />
                            <Radio.Group
                                value={validityType}
                                onChange={(e) => setValidityType(e.target.value)}
                            >
                                <Radio value="period">
                                    <Space>
                                        <span>Trong</span>
                                        <Form.Item
                                            name="validity_period"
                                            noStyle
                                            rules={[
                                                {
                                                    validator: (_, value) => {
                                                        if (validityType === 'period' && !value) {
                                                            return Promise.reject('Vui lòng chọn thời hạn');
                                                        }
                                                        return Promise.resolve();
                                                    }
                                                }
                                            ]}
                                        >
                                            <Select
                                                style={{ width: 100 }}
                                                placeholder="ngày"
                                                options={[
                                                    { label: '7 ngày', value: 7 },
                                                    { label: '30 ngày', value: 30 },
                                                    { label: '60 ngày', value: 60 },
                                                    { label: '90 ngày', value: 90 },
                                                    { label: '180 ngày', value: 180 },
                                                    { label: '365 ngày', value: 365 },
                                                ]}
                                            />
                                        </Form.Item>
                                        <span>kể từ ngày phát hành</span>
                                    </Space>
                                </Radio>
                            </Radio.Group>

                            <Divider style={{ margin: '16px 0' }} />

                            <Form.Item name="status" label="Trạng thái" style={{ marginBottom: 0 }}>
                                <Radio.Group>
                                    <Radio value="active">
                                        <span style={{ color: '#1890ff' }}>Đang kích hoạt</span>
                                    </Radio>
                                    <Radio value="inactive">Chưa kích hoạt</Radio>
                                </Radio.Group>
                            </Form.Item>
                        </Panel>
                    </Collapse>

                    {/* Purchase Condition */}
                    <Collapse
                        defaultActiveKey={['condition']}
                        ghost
                        style={{ background: '#fafbfc', borderRadius: 8, marginBottom: 16 }}
                    >
                        <Panel header={<span style={{ fontWeight: 600 }}>Điều kiện mua hàng</span>} key="condition">
                            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 12 }}>
                                <span>Tổng tiền hàng tối thiểu từ</span>
                                <Form.Item name="min_amount" noStyle>
                                    <InputNumber
                                        style={{ width: 150 }}
                                        placeholder="0"
                                        min={0}
                                        formatter={formatNumber}
                                        parser={parseNumber}
                                    />
                                </Form.Item>
                                <span>VNĐ</span>
                            </div>
                        </Panel>
                    </Collapse>

                    {/* Note */}
                    <Form.Item name="description" label="Ghi chú">
                        <TextArea rows={2} placeholder="Nhập ghi chú..." />
                    </Form.Item>

                    {/* Combinable */}
                    <Form.Item name="is_combinable" valuePropName="checked">
                        <Checkbox>
                            Cho phép gộp nhiều voucher trên một hóa đơn{' '}
                            <Tooltip title="Cho phép kết hợp nhiều voucher trong một giao dịch">
                                <InfoCircleOutlined style={{ color: '#1890ff' }} />
                            </Tooltip>
                        </Checkbox>
                    </Form.Item>
                </div>
            ),
        },
        {
            key: 'scope',
            label: 'Phạm vi áp dụng',
            children: (
                <div style={{ padding: '16px 0' }}>
                    {/* Branch Scope */}
                    <Collapse
                        defaultActiveKey={['branch']}
                        ghost
                        style={{ background: '#fafbfc', borderRadius: 8, marginBottom: 16 }}
                    >
                        <Panel header={<span style={{ fontWeight: 600, color: '#1890ff' }}>Chi nhánh</span>} key="branch">
                            <Radio.Group
                                value={branchScope}
                                onChange={(e) => setBranchScope(e.target.value)}
                                style={{ display: 'flex', flexDirection: 'column', gap: 8 }}
                            >
                                <Radio value="all">
                                    <span style={{ color: '#1890ff' }}>Toàn hệ thống</span>
                                </Radio>
                                <Radio value="specific">
                                    Chi nhánh cụ thể
                                    {branchScope === 'specific' && (
                                        <Form.Item name="branch_id" noStyle>
                                            <Select
                                                style={{ width: 250, marginLeft: 12 }}
                                                placeholder="Chọn chi nhánh"
                                                options={branchOptions}
                                                allowClear
                                            />
                                        </Form.Item>
                                    )}
                                </Radio>
                            </Radio.Group>
                        </Panel>
                    </Collapse>

                    {/* Customer Group Scope */}
                    <Collapse
                        defaultActiveKey={['customer_group']}
                        ghost
                        style={{ background: '#fafbfc', borderRadius: 8, marginBottom: 16 }}
                    >
                        <Panel header={<span style={{ fontWeight: 600, color: '#1890ff' }}>Nhóm khách hàng</span>} key="customer_group">
                            <Radio.Group
                                value={customerGroupScope}
                                onChange={(e) => setCustomerGroupScope(e.target.value)}
                                style={{ display: 'flex', flexDirection: 'column', gap: 8 }}
                            >
                                <Radio value="all">
                                    <span style={{ color: '#1890ff' }}>Tất cả</span>
                                </Radio>
                                <Radio value="specific">
                                    Nhóm khách hàng cụ thể
                                    {customerGroupScope === 'specific' && (
                                        <Form.Item name="customer_group_id" noStyle>
                                            <Select
                                                style={{ width: 250, marginLeft: 12 }}
                                                placeholder="Chọn nhóm khách hàng"
                                                options={customerGroupOptions}
                                                allowClear
                                            />
                                        </Form.Item>
                                    )}
                                </Radio>
                            </Radio.Group>
                        </Panel>
                    </Collapse>

                    {/* Creator Scope */}
                    <Collapse
                        defaultActiveKey={['creator']}
                        ghost
                        style={{ background: '#fafbfc', borderRadius: 8, marginBottom: 16 }}
                    >
                        <Panel header={<span style={{ fontWeight: 600, color: '#1890ff' }}>Người tạo giao dịch</span>} key="creator">
                            <Radio.Group
                                value={creatorScope}
                                onChange={(e) => setCreatorScope(e.target.value)}
                                style={{ display: 'flex', flexDirection: 'column', gap: 8 }}
                            >
                                <Radio value="all">
                                    <span style={{ color: '#1890ff' }}>Tất cả</span>
                                </Radio>
                                <Radio value="specific">
                                    Người tạo giao dịch cụ thể
                                    {creatorScope === 'specific' && (
                                        <Form.Item name="creator_id" noStyle>
                                            <Select
                                                style={{ width: 250, marginLeft: 12 }}
                                                placeholder="Tìm người tạo..."
                                                options={userOptions}
                                                allowClear
                                                showSearch
                                                loading={usersLoading}
                                                onSearch={handleUserSearch}
                                                filterOption={false}
                                                notFoundContent={usersLoading ? 'Đang tải...' : 'Không tìm thấy'}
                                            />
                                        </Form.Item>
                                    )}
                                </Radio>
                            </Radio.Group>
                        </Panel>
                    </Collapse>
                </div>
            ),
        },
    ];

    return (
        <Modal
            title={isEditing ? 'Sửa đợt phát hành voucher' : 'Tạo đợt phát hành voucher'}
            open={open}
            onCancel={onCancel}
            width={680}
            destroyOnClose
            footer={
                <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8 }}>
                    <Button onClick={onCancel}>Bỏ qua</Button>
                    <Button type="primary" onClick={handleSubmit} loading={loading}>
                        Lưu (F9)
                    </Button>
                </div>
            }
        >
            <Form form={form} layout="vertical">
                <Tabs
                    activeKey={activeTab}
                    onChange={setActiveTab}
                    items={tabItems}
                    style={{ marginTop: -8 }}
                />
            </Form>
        </Modal>
    );
};

export default CreateVoucherModal;

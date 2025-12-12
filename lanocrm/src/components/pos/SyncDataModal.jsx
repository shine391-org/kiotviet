import React, { useState, useEffect, useCallback } from 'react';
import { Modal, Select, Table, Button, Empty, Spin, App, Alert } from 'antd';
import { WifiOutlined, DisconnectOutlined, InboxOutlined, SyncOutlined } from '@ant-design/icons';
import posApi from '../../api/posApi';
import styles from './SyncDataModal.module.css';

const SyncDataModal = ({ open, onClose, onSyncAll }) => {
    const { message } = App.useApp();
    const [docType, setDocType] = useState('all');
    const [loading, setLoading] = useState(false);
    const [syncing, setSyncing] = useState(false);
    const [pendingDocs, setPendingDocs] = useState([]);
    const [fetchError, setFetchError] = useState(null);
    const [isOnline, setIsOnline] = useState(navigator.onLine);

    // Monitor online status
    useEffect(() => {
        const handleOnline = () => setIsOnline(true);
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    // Fetch pending/draft orders from API
    const fetchPendingDocs = useCallback(async () => {
        if (!open) return;

        setLoading(true);
        setFetchError(null);
        try {
            const response = await posApi.getOrders({
                status: 'draft,pending',
                limit: 50,
            });

            if (response.success && response.data) {
                const docs = response.data.map(order => ({
                    id: order.id,
                    type: order.order_type === 'return' ? 'Trả hàng' :
                        order.order_type === 'order' ? 'Đặt hàng' : 'Hóa đơn',
                    typeKey: order.order_type || 'invoice',
                    code: order.order_number || order.code,
                    time: order.order_date || order.created_at,
                    value: parseFloat(order.total) || 0,
                }));
                setPendingDocs(docs);
            }
        } catch (error) {
            console.error('Failed to fetch pending documents:', error);
            const errorMsg = isOnline
                ? 'Không thể tải danh sách phiếu. Vui lòng thử lại.'
                : 'Không có kết nối Internet. Vui lòng kiểm tra và thử lại.';
            setFetchError(errorMsg);
            message.error(errorMsg);
            setPendingDocs([]);
        } finally {
            setLoading(false);
        }
    }, [open, isOnline, message]);

    useEffect(() => {
        fetchPendingDocs();
    }, [fetchPendingDocs, docType]);

    // Filter by document type
    const filteredDocs = docType === 'all'
        ? pendingDocs
        : pendingDocs.filter(doc => doc.typeKey === docType);

    const handleSyncAll = async () => {
        if (!isOnline) {
            message.warning('Vui lòng kết nối Internet để đồng bộ');
            return;
        }

        if (filteredDocs.length === 0) {
            message.info('Không có phiếu nào cần đồng bộ');
            return;
        }

        if (!onSyncAll) {
            message.warning('Chức năng đồng bộ chưa được cấu hình');
            return;
        }

        setSyncing(true);
        try {
            await onSyncAll(filteredDocs);
            message.success(`Đã đồng bộ ${filteredDocs.length} phiếu thành công`);
            await fetchPendingDocs(); // Refresh list
        } catch (error) {
            console.error('Sync failed:', error);
            message.error('Lỗi khi đồng bộ phiếu');
        } finally {
            setSyncing(false);
        }
    };

    const columns = [
        {
            title: 'Loại phiếu',
            dataIndex: 'type',
            key: 'type',
        },
        {
            title: 'Mã phiếu',
            dataIndex: 'code',
            key: 'code',
        },
        {
            title: 'Thời gian',
            dataIndex: 'time',
            key: 'time',
            sorter: (a, b) => new Date(a.time) - new Date(b.time),
        },
        {
            title: 'Giá trị',
            dataIndex: 'value',
            key: 'value',
            align: 'right',
            render: (val) => val?.toLocaleString('vi-VN') || '0',
        },
    ];

    return (
        <Modal
            title="Đồng bộ phiếu"
            open={open}
            onCancel={onClose}
            footer={null}
            width={700}
            className={styles.modal}
        >
            <div className={styles.header}>
                <div className={styles.filterRow}>
                    <span className={styles.label}>Loại phiếu</span>
                    <Select
                        value={docType}
                        onChange={setDocType}
                        options={[
                            { value: 'all', label: '--Tất cả--' },
                            { value: 'invoice', label: 'Hóa đơn' },
                            { value: 'order', label: 'Đặt hàng' },
                            { value: 'return', label: 'Trả hàng' },
                        ]}
                        className={styles.select}
                    />
                </div>
                <div className={styles.connectionStatus}>
                    <span className={styles.label}>Trạng thái kết nối:</span>
                    {isOnline ? (
                        <span className={styles.connected}>
                            Có Internet <WifiOutlined />
                        </span>
                    ) : (
                        <span className={styles.disconnected}>
                            Không có kết nối <DisconnectOutlined />
                        </span>
                    )}
                </div>
            </div>

            {fetchError && (
                <Alert
                    message={fetchError}
                    type="error"
                    showIcon
                    closable
                    onClose={() => setFetchError(null)}
                    action={
                        <Button size="small" onClick={fetchPendingDocs}>
                            Thử lại
                        </Button>
                    }
                    style={{ marginBottom: 16 }}
                />
            )}

            <Spin spinning={loading}>
                <Table
                    columns={columns}
                    dataSource={filteredDocs}
                    rowKey="id"
                    pagination={false}
                    size="small"
                    locale={{
                        emptyText: (
                            <Empty
                                image={<InboxOutlined className={styles.emptyIcon} />}
                                description="Không có phiếu nào cần đồng bộ"
                            />
                        ),
                    }}
                />
            </Spin>

            <div className={styles.footer}>
                <span className={styles.countLabel}>
                    {filteredDocs.length > 0 && `${filteredDocs.length} phiếu chờ đồng bộ`}
                </span>
                <Button
                    type="primary"
                    onClick={handleSyncAll}
                    loading={syncing}
                    disabled={!isOnline || filteredDocs.length === 0}
                    icon={<SyncOutlined />}
                    className={styles.syncBtn}
                >
                    Đồng bộ tất cả
                </Button>
            </div>
        </Modal>
    );
};

export default SyncDataModal;

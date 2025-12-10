// src/pages/customers/CustomerQRModal.jsx

import React from 'react';
import { Modal, Form, Input, Select, message, Button, Space } from 'antd';
import { QrcodeOutlined, CopyOutlined, DownloadOutlined } from '@ant-design/icons';
import { QRCodeCanvas } from 'qrcode.react';

/**
 * CustomerQRModal - Tạo QR code thanh toán cho khách hàng
 * Generates VietQR-compatible bank transfer QR codes
 * @agent-layer: frontend-component
 */
const CustomerQRModal = ({ open, customer, onCancel }) => {
    const [form] = Form.useForm();
    const [loading, setLoading] = React.useState(false);
    const [qrGenerated, setQrGenerated] = React.useState(false);
    const [qrPayload, setQrPayload] = React.useState('');

    const currentDebt = customer?.current_debt || customer?.debt_amount || 0;

    // Bank BIN codes for VietQR
    const bankBins = {
        vietcombank: '970436',
        techcombank: '970407',
        mbbank: '970422',
        vpbank: '970432',
        acb: '970416',
    };

    const handleGenerateQR = async () => {
        try {
            const values = await form.validateFields();
            setLoading(true);

            // Build VietQR payload
            const bank = values.bank || 'vietcombank';
            const accountNumber = values.account_number || '';
            const amount = values.amount || 0;
            const content = values.content || `Thanh toan ${customer?.code || ''}`;

            // VietQR format: https://www.vietqr.io/portal-service/download/documents/VietQR_4.0.pdf
            const bin = bankBins[bank] || '970436';
            const payload = `https://img.vietqr.io/image/${bin}-${accountNumber}-compact.png?amount=${amount}&addInfo=${encodeURIComponent(content)}`;

            setQrPayload(payload);
            setQrGenerated(true);
            message.success('Đã tạo mã QR!');
        } catch (err) {
            if (!err.errorFields) {
                console.error('QR generation error:', err);
                message.error('Tạo QR thất bại');
            }
        } finally {
            setLoading(false);
        }
    };

    const handleCopy = () => {
        if (!qrPayload) {
            message.error('Không có nội dung để sao chép');
            return;
        }

        try {
            // Modern Clipboard API
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                navigator.clipboard.writeText(qrPayload)
                    .then(() => message.success('Đã sao chép link thanh toán!'))
                    .catch(() => {
                        // Fallback if clipboard API fails (e.g., permissions denied)
                        legacyCopy();
                    });
            } else {
                // Legacy fallback for older browsers
                legacyCopy();
            }
        } catch (err) {
            console.error('Copy error:', err);
            message.error('Không thể sao chép');
        }

        function legacyCopy() {
            try {
                const textarea = document.createElement('textarea');
                textarea.value = qrPayload;
                // Make textarea offscreen
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                textarea.style.top = '-9999px';
                textarea.style.opacity = '0';
                textarea.setAttribute('readonly', '');
                document.body.appendChild(textarea);
                textarea.select();
                textarea.setSelectionRange(0, qrPayload.length); // For mobile devices

                const success = document.execCommand('copy');
                document.body.removeChild(textarea);

                if (success) {
                    message.success('Đã sao chép link thanh toán!');
                } else {
                    message.error('Không thể sao chép');
                }
            } catch (err) {
                console.error('Legacy copy error:', err);
                message.error('Không thể sao chép');
            }
        }
    };

    const handleDownload = () => {
        const canvas = document.querySelector('#qr-canvas-container canvas');
        if (canvas) {
            const link = document.createElement('a');
            link.download = `QR_${customer?.code || 'payment'}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
            message.success('Đã tải mã QR!');
        } else {
            message.error('Không thể tải mã QR');
        }
    };

    return (
        <Modal
            title={
                <div>
                    <QrcodeOutlined style={{ marginRight: 8 }} />
                    Tạo QR thanh toán
                </div>
            }
            open={open}
            onCancel={() => {
                setQrGenerated(false);
                form.resetFields();
                onCancel();
            }}
            width={500}
            footer={null}
            destroyOnClose
        >
            <div style={{ marginBottom: 16, color: '#8c8c8c' }}>
                Khách hàng: <strong>{customer?.name}</strong> ·
                Nợ hiện tại: <strong style={{ color: '#1890ff' }}>{new Intl.NumberFormat('vi-VN').format(currentDebt)}</strong>
            </div>

            <Form form={form} layout="vertical">
                <Form.Item name="bank" label="Ngân hàng" initialValue="vietcombank">
                    <Select>
                        <Select.Option value="vietcombank">Vietcombank</Select.Option>
                        <Select.Option value="techcombank">Techcombank</Select.Option>
                        <Select.Option value="mbbank">MB Bank</Select.Option>
                        <Select.Option value="vpbank">VPBank</Select.Option>
                        <Select.Option value="acb">ACB</Select.Option>
                    </Select>
                </Form.Item>

                <Form.Item
                    name="account_number"
                    label="Số tài khoản"
                    rules={[{ required: true, message: 'Vui lòng nhập số tài khoản' }]}
                >
                    <Input placeholder="Nhập số tài khoản" />
                </Form.Item>

                <Form.Item name="account_name" label="Tên tài khoản">
                    <Input placeholder="Nhập tên tài khoản" />
                </Form.Item>

                <Form.Item
                    name="amount"
                    label="Số tiền"
                    initialValue={currentDebt}
                >
                    <Input
                        placeholder="Nhập số tiền"
                        suffix="VNĐ"
                    />
                </Form.Item>

                <Form.Item name="content" label="Nội dung chuyển khoản">
                    <Input placeholder={`Thanh toán ${customer?.code || ''}`} />
                </Form.Item>

                {!qrGenerated ? (
                    <Button
                        type="primary"
                        block
                        icon={<QrcodeOutlined />}
                        onClick={handleGenerateQR}
                        loading={loading}
                    >
                        Tạo mã QR
                    </Button>
                ) : (
                    <div style={{ textAlign: 'center' }}>
                        {qrPayload ? (
                            <div
                                id="qr-canvas-container"
                                style={{
                                    padding: 16,
                                    background: '#fff',
                                    display: 'inline-block',
                                    margin: '0 auto 16px',
                                    borderRadius: 8,
                                    border: '1px solid #d9d9d9',
                                }}
                            >
                                <QRCodeCanvas
                                    value={qrPayload}
                                    size={200}
                                    level="M"
                                    includeMargin
                                />
                            </div>
                        ) : (
                            <div
                                style={{
                                    width: 200,
                                    height: 200,
                                    background: '#f0f0f0',
                                    margin: '0 auto 16px',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    border: '1px dashed #d9d9d9',
                                    borderRadius: 8,
                                }}
                            >
                                <QrcodeOutlined style={{ fontSize: 80, color: '#8c8c8c' }} />
                            </div>
                        )}
                        <Space>
                            <Button icon={<CopyOutlined />} onClick={handleCopy}>
                                Sao chép link
                            </Button>
                            <Button type="primary" icon={<DownloadOutlined />} onClick={handleDownload}>
                                Tải xuống
                            </Button>
                        </Space>
                    </div>
                )}
            </Form>
        </Modal>
    );
};

export default CustomerQRModal;
